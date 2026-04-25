<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExceptionStatusEvent extends Model
{
    use HasUuids;

    public const SOURCE_PANEL = 'panel';

    public const SOURCE_API = 'api';

    public const SOURCE_BULK = 'bulk';

    public const SOURCE_SYSTEM = 'system';

    public const SOURCE_VIEW = 'view';

    protected $table = 'exception_status_events';

    protected $fillable = [
        'exception_id',
        'user_id',
        'source',
        'from_status',
        'to_status',
        'comment',
    ];

    public function exception(): BelongsTo
    {
        return $this->belongsTo(ExceptionRecord::class, 'exception_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
