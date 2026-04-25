<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
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

    public function getOpenExceptionsCountAttribute(): int
    {
        return $this->exceptions()->where('status', 'OPEN')->count();
    }

    public function regenerateApiToken(): void
    {
        $this->update(['api_token' => Str::random(60)]);
    }
}
