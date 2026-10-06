<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class Project extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $fillable = [
        'title',
        'url',
        'uptime_enabled',
        'uptime_url',
        'description',
        'key',
        'api_token',
        'group_id',
        'last_error_at',
        'total_exceptions',
    ];

    protected function casts(): array
    {
        return [
            'last_error_at' => 'datetime',
            'uptime_enabled' => 'boolean',
        ];
    }

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (self $project) {
            if (! $project->key) {
                $project->key = Str::random(32);
            }

            if (! $project->api_token) {
                $project->api_token = Str::random(60);
            }
        });
    }

    public function users()
    {
        return $this->belongsToMany(User::class, 'project_user')
            ->withPivot('is_owner')
            ->withTimestamps();
    }

    public function owner()
    {
        return $this->belongsToMany(User::class, 'project_user')
            ->wherePivot('is_owner', true);
    }

    public function exceptions()
    {
        return $this->hasMany(ExceptionRecord::class);
    }

    public function uptimeChecks()
    {
        return $this->hasMany(UptimeCheck::class);
    }

    public function group()
    {
        return $this->belongsTo(ProjectGroup::class);
    }

    /**
     * Add open_count, unresolved_count and is_offline (an outage still in progress).
     */
    public function scopeWithHealth(Builder $query): Builder
    {
        return $query
            ->withCount([
                'exceptions as open_count' => fn ($q) => $q->where('status', ExceptionRecord::STATUS_OPEN),
                'exceptions as unresolved_count' => fn ($q) => $q->whereIn('status', [ExceptionRecord::STATUS_OPEN, ExceptionRecord::STATUS_READ]),
            ])
            ->withExists([
                'exceptions as is_offline' => fn ($q) => $q->where('exception', 'ProjectOfflineException')
                    ->whereIn('status', [ExceptionRecord::STATUS_OPEN, ExceptionRecord::STATUS_READ, ExceptionRecord::STATUS_FIXED]),
            ]);
    }

    /**
     * online | offline | disabled — needs scopeWithHealth() to avoid an extra query.
     */
    public function healthStatus(): string
    {
        if (! $this->uptime_enabled) {
            return 'disabled';
        }

        $offline = $this->is_offline ?? $this->exceptions()
            ->where('exception', 'ProjectOfflineException')
            ->whereIn('status', [ExceptionRecord::STATUS_OPEN, ExceptionRecord::STATUS_READ, ExceptionRecord::STATUS_FIXED])
            ->exists();

        return $offline ? 'offline' : 'online';
    }

    public function getOpenExceptionsCountAttribute(): int
    {
        return $this->exceptions()->where('status', 'OPEN')->count();
    }

    public function regenerateApiToken(): void
    {
        $this->update(['api_token' => Str::random(60)]);
    }

    public static function decrementTotalExceptionsBy(string $projectId, int $amount): void
    {
        if ($amount <= 0) {
            return;
        }

        // CASE instead of GREATEST(): portable across MySQL, PostgreSQL and SQLite.
        $amount = (int) $amount;

        self::query()->whereKey($projectId)->update([
            'total_exceptions' => DB::raw("CASE WHEN COALESCE(total_exceptions, 0) > {$amount} THEN COALESCE(total_exceptions, 0) - {$amount} ELSE 0 END"),
        ]);
    }

    /**
     * Set last_error_at to the latest remaining exception's created_at, or null if none.
     */
    public static function syncLastErrorAtFromExceptions(string $projectId): void
    {
        $latest = ExceptionRecord::query()
            ->where('project_id', $projectId)
            ->max('created_at');

        self::query()->whereKey($projectId)->update([
            'last_error_at' => $latest,
        ]);
    }
}
