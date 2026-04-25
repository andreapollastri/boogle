<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ExceptionNotificationState extends Model
{
    protected $fillable = [
        'user_id',
        'project_id',
        'fingerprint',
        'last_notified_at',
    ];

    protected function casts(): array
    {
        return [
            'last_notified_at' => 'datetime',
        ];
    }
}
