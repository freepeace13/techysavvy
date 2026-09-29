<?php

namespace Techysavvy\WebhookInspector\Models;

use Illuminate\Database\Eloquent\Model;

class CapturedRequest extends Model
{
    protected $table = 'webhook_inspector_requests';

    public $timestamps = false;

    protected $fillable = [
        'method',
        'path',
        'query',
        'headers',
        'body',
        'content_type',
        'body_size',
        'truncated',
        'is_binary',
        'ip',
        'received_at',
    ];

    protected $casts = [
        'headers' => 'array',
        'body_size' => 'integer',
        'truncated' => 'boolean',
        'is_binary' => 'boolean',
        'received_at' => 'datetime',
    ];
}
