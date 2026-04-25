<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'settings',
        'is_admin',
        'google2fa_secret',
        'google2fa_enabled_at',
        'google2fa_recovery_codes',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'google2fa_secret',
        'google2fa_recovery_codes',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'settings' => 'array',
            'is_admin' => 'boolean',
            'google2fa_enabled_at' => 'datetime',
            'google2fa_recovery_codes' => 'array',
        ];
    }

    public function projects()
    {
        return $this->belongsToMany(Project::class, 'project_user')
            ->withPivot('is_owner')
            ->withTimestamps();
    }

    public function ownedProjects()
    {
        return $this->belongsToMany(Project::class, 'project_user')
            ->wherePivot('is_owner', true)
            ->withTimestamps();
    }

    public function projectGroups()
    {
        return $this->hasMany(ProjectGroup::class);
    }

    public function assignedGroups(): BelongsToMany
    {
        return $this->belongsToMany(ProjectGroup::class, 'project_group_user')
            ->withTimestamps();
    }

    public function isAdmin(): bool
    {
        return (bool) $this->is_admin;
    }

    public function visibleGroupsQuery(): Builder
    {
        if ($this->isAdmin()) {
            return ProjectGroup::query();
        }

        return ProjectGroup::query()->where(function (Builder $query): void {
            $query->where('user_id', $this->id)
                ->orWhereIn('id', $this->assignedGroups()->select('project_groups.id'));
        });
    }

    public function accessibleProjectsQuery(): Builder
    {
        if ($this->isAdmin()) {
            return Project::query();
        }

        return Project::query()->where(function (Builder $query): void {
            $query->whereHas('users', fn (Builder $q) => $q->where('users.id', $this->id))
                ->orWhereIn('group_id', $this->visibleGroupsQuery()->select('project_groups.id'));
        });
    }

    public function canAccessProject(Project $project): bool
    {
        if ($this->isAdmin()) {
            return true;
        }

        return $this->accessibleProjectsQuery()
            ->where('projects.id', $project->id)
            ->exists();
    }

    public function notificationSettings(): array
    {
        $defaults = ['mail' => true, 'snooze_minutes' => 15];

        return array_merge($defaults, ($this->settings['notifications'] ?? []));
    }

    public function hasTwoFactorEnabled(): bool
    {
        return ! empty($this->google2fa_secret) && $this->google2fa_enabled_at !== null;
    }
}
