<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UptimeCheck extends Model
{
    protected $fillable = [
        'project_id',
        'success',
        'response_time_ms',
        'http_status',
        'checked_at',
    ];

    protected function casts(): array
    {
        return [
            'success' => 'boolean',
            'checked_at' => 'datetime',
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }
}
