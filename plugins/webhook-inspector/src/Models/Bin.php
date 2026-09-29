<?php

namespace Techysavvy\WebhookInspector\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Bin extends Model
{
    protected $table = 'webhook_inspector_bins';

    protected $fillable = [
        'bin_id',
        'view_token',
        'expires_at',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
    ];

    public function requests(): HasMany
    {
        return $this->hasMany(CapturedRequest::class, 'bin_id');
    }

    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }
}
