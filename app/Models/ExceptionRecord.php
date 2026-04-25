<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ExceptionRecord extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'exceptions';

    const STATUS_OPEN = 'OPEN';

    const STATUS_READ = 'READ';

    const STATUS_FIXED = 'FIXED';

    const STATUS_DONE = 'DONE';

    protected $appends = [
        'issue_code',
    ];

    /**
     * @var list<string>
     */
    protected $hidden = [
        'raw_exception',
    ];

    protected $fillable = [
        'project_id',
        'host',
        'method',
        'full_url',
        'exception',
        'fingerprint',
        'error',
        'line',
        'file',
        'class',
        'file_type',
        'status',
        'mailed',
        'publish_hash',
        'published_at',
        'executor',
        'storage',
        'additional',
        'user',
        'http',
        'raw_exception',
        'issue_prefix',
        'issue_number',
    ];

    protected function casts(): array
    {
        return [
            'mailed' => 'boolean',
            'published_at' => 'datetime',
            'executor' => 'array',
            'storage' => 'array',
            'additional' => 'array',
            'user' => 'array',
            'http' => 'array',
            'raw_exception' => 'array',
        ];
    }

    public function getIssueCodeAttribute(): ?string
    {
        if ($this->issue_number === null || $this->issue_prefix === null) {
            return null;
        }

        return '#'.$this->issue_prefix.$this->issue_number;
    }

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function statusEvents(): HasMany
    {
        return $this->hasMany(ExceptionStatusEvent::class, 'exception_id')->orderBy('created_at');
    }

    public function occurrences()
    {
        return $this->hasMany(ExceptionRecord::class, 'exception', 'exception')
            ->where('project_id', $this->project_id)
            ->where('created_at', '>=', now()->subMonth());
    }

    public function scopeOpen($query)
    {
        return $query->where('status', self::STATUS_OPEN);
    }

    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    public function isPublished(): bool
    {
        return $this->publish_hash !== null;
    }

    public function publish(): void
    {
        $this->update([
            'publish_hash' => Str::random(40),
            'published_at' => now(),
        ]);
    }

    public function unpublish(): void
    {
        $this->update([
            'publish_hash' => null,
            'published_at' => null,
        ]);
    }

    /**
     * @return bool True if the status was updated, false if it was already the requested value
     */
    public function markAs(
        string $status,
        ?User $actor = null,
        ?string $comment = null,
        bool $recordEvent = true,
        string $source = ExceptionStatusEvent::SOURCE_PANEL
    ): bool {
        if ($this->status === $status) {
            return false;
        }

        $from = $this->status;
        $this->update(['status' => $status]);

        if ($recordEvent) {
            $note = $comment !== null && trim($comment) !== '' ? trim($comment) : null;
            $this->statusEvents()->create([
                'user_id' => $actor?->id,
                'source' => $source,
                'from_status' => $from,
                'to_status' => $status,
                'comment' => $note,
            ]);
        }

        return true;
    }

    public static function statuses(): array
    {
        return [
            self::STATUS_OPEN,
            self::STATUS_READ,
            self::STATUS_FIXED,
            self::STATUS_DONE,
        ];
    }

    public static function isOutageExceptionType(?string $exceptionClass): bool
    {
        return $exceptionClass === 'ProjectOfflineException';
    }

    /**
     * @return array{issue_prefix: string, issue_number: int}
     */
    public static function nextIssueForProject(Project $project, bool $isOutage): array
    {
        $prefix = self::issuePrefixFor($project, $isOutage);

        return DB::transaction(function () use ($project, $isOutage, $prefix) {
            if ($project->group_id) {
                $g = ProjectGroup::query()->lockForUpdate()->findOrFail($project->group_id);
                $key = $isOutage ? 'next_issue_outage' : 'next_issue_error';
                $num = (int) $g->getAttribute($key);
                $g->increment($key);
            } else {
                $p = Project::query()->lockForUpdate()->findOrFail($project->id);
                $key = $isOutage ? 'next_issue_outage' : 'next_issue_error';
                $num = (int) $p->getAttribute($key);
                $p->increment($key);
            }

            return [
                'issue_prefix' => $prefix,
                'issue_number' => $num,
            ];
        });
    }

    protected static function issuePrefixFor(Project $project, bool $isOutage): string
    {
        if ($isOutage) {
            return 'OUT';
        }

        $raw = $project->group?->issue_prefix;
        if ($raw === null || trim((string) $raw) === '') {
            return 'BUG';
        }

        $p = strtoupper(preg_replace('/[^A-Z0-9]/i', '', (string) $raw));

        if ($p === '' || $p === 'OUT') {
            return 'BUG';
        }

        return $p;
    }
}
