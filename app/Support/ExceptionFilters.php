<?php

namespace App\Support;

use App\Models\ExceptionRecord;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Http\Request;

/**
 * Filters of the project exception list, shared by the list itself, the detail page
 * (newer / older navigation) and the "update & open next" triage flow.
 */
final class ExceptionFilters
{
    public const STATUSES = ['ALL', 'OPEN', 'READ', 'FIXED', 'DONE'];

    public const KINDS = ['all', 'outage', 'bug', 'group'];

    public function __construct(
        public readonly string $status = 'ALL',
        public readonly ?string $search = null,
        public readonly ?string $dateFrom = null,
        public readonly ?string $dateTo = null,
        public readonly string $kind = 'all',
        public readonly bool $grouped = false,
        public readonly ?string $fingerprint = null,
    ) {}

    public static function fromRequest(Request $request): self
    {
        return self::fromArray($request->query());
    }

    /**
     * @param  array<string, mixed>  $input
     */
    public static function fromArray(array $input): self
    {
        $string = static fn (string $key): ?string => is_string($input[$key] ?? null) && trim($input[$key]) !== ''
            ? trim($input[$key])
            : null;

        $date = static function (string $key) use ($string): ?string {
            $value = $string($key);

            return $value !== null && strtotime($value) !== false ? date('Y-m-d', strtotime($value)) : null;
        };

        $status = strtoupper($string('status') ?? 'ALL');
        $kind = $string('kind') ?? 'all';
        $dateFrom = $date('date_from');
        $dateTo = $date('date_to');

        if ($dateFrom && $dateTo && $dateFrom > $dateTo) {
            [$dateFrom, $dateTo] = [$dateTo, $dateFrom];
        }

        $fingerprint = $string('fingerprint');

        return new self(
            status: in_array($status, self::STATUSES, true) ? $status : 'ALL',
            search: $string('search'),
            dateFrom: $dateFrom,
            dateTo: $dateTo,
            kind: in_array($kind, self::KINDS, true) ? $kind : 'all',
            grouped: filter_var($input['grouped'] ?? false, FILTER_VALIDATE_BOOL),
            fingerprint: $fingerprint !== null && preg_match('/^[a-f0-9]{64}$/', $fingerprint) ? $fingerprint : null,
        );
    }

    public function withStatus(string $status): self
    {
        return new self($status, $this->search, $this->dateFrom, $this->dateTo, $this->kind, $this->grouped, $this->fingerprint);
    }

    /**
     * Apply every filter except ordering.
     */
    public function apply(Builder $query, bool $withStatus = true): Builder
    {
        return $query
            ->when($withStatus && $this->status !== 'ALL', fn ($q) => $q->where('status', $this->status))
            ->when($this->dateFrom, fn ($q) => $q->whereDate('created_at', '>=', $this->dateFrom))
            ->when($this->dateTo, fn ($q) => $q->whereDate('created_at', '<=', $this->dateTo))
            ->when($this->kind === 'outage', fn ($q) => $q->where('issue_prefix', 'OUT'))
            ->when($this->kind === 'bug', fn ($q) => $q->where('issue_prefix', 'BUG'))
            ->when($this->kind === 'group', fn ($q) => $q->whereNotIn('issue_prefix', ['OUT', 'BUG']))
            ->when($this->fingerprint, fn ($q) => $q->where('fingerprint', $this->fingerprint))
            ->when($this->search, fn ($q) => self::applySearch($q, $this->search))
            ->when($this->grouped, fn ($q) => self::latestPerFingerprint($q));
    }

    /**
     * Newest first, with a stable tie-breaker so newer / older navigation never skips rows.
     */
    public static function order(Builder $query, string $direction = 'desc'): Builder
    {
        return $query->orderBy('created_at', $direction)->orderBy('id', $direction);
    }

    /**
     * Free text search, or an exact match when the term looks like an issue code (#BUG42, out3…).
     */
    public static function applySearch(Builder $query, string $term): Builder
    {
        $term = trim($term);

        if (preg_match('/^#?([A-Za-z0-9]*[A-Za-z])(\d+)$/', $term, $m)) {
            return $query->where('issue_prefix', strtoupper($m[1]))
                ->where('issue_number', (int) $m[2]);
        }

        $like = '%'.str_replace(['%', '_'], ['\%', '\_'], $term).'%';

        return $query->where(function ($q) use ($like): void {
            $q->where('exception', 'like', $like)
                ->orWhere('error', 'like', $like)
                ->orWhere('file', 'like', $like)
                ->orWhere('full_url', 'like', $like)
                ->orWhere('host', 'like', $like)
                ->orWhere('class', 'like', $like);
        });
    }

    /**
     * Keep only the most recent occurrence of each error (same fingerprint).
     */
    private static function latestPerFingerprint(Builder $query): Builder
    {
        $table = (new ExceptionRecord)->getTable();

        return $query->whereNotExists(function ($sub) use ($table): void {
            $sub->selectRaw('1')
                ->from($table.' as newer')
                ->whereColumn('newer.project_id', $table.'.project_id')
                ->whereColumn('newer.fingerprint', $table.'.fingerprint')
                ->where(function ($q) use ($table): void {
                    $q->whereColumn('newer.created_at', '>', $table.'.created_at')
                        ->orWhere(function ($q) use ($table): void {
                            $q->whereColumn('newer.created_at', $table.'.created_at')
                                ->whereColumn('newer.id', '>', $table.'.id');
                        });
                });
        });
    }

    /**
     * Query string for links — only values that differ from the defaults.
     *
     * @return array<string, string>
     */
    public function toQuery(): array
    {
        return array_filter([
            'status' => $this->status !== 'ALL' ? $this->status : null,
            'search' => $this->search,
            'date_from' => $this->dateFrom,
            'date_to' => $this->dateTo,
            'kind' => $this->kind !== 'all' ? $this->kind : null,
            'grouped' => $this->grouped ? '1' : null,
            'fingerprint' => $this->fingerprint,
        ], fn ($value) => $value !== null);
    }

    /**
     * True when anything besides the status tab narrows the list.
     */
    public function isNarrowed(): bool
    {
        return array_diff_key($this->toQuery(), ['status' => true]) !== [];
    }

    public static function sessionKey(string $projectId): string
    {
        return 'panel.exceptions.filters.'.$projectId;
    }
}
