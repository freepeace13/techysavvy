<?php

namespace Techysavvy\DropShare\Models;

use Illuminate\Database\Eloquent\Model;

class SharedFile extends Model
{
    protected $table = 'drop_share_uploads';

    protected $fillable = [
        'phrase',
        'disk_path',
        'original_name',
        'mime_type',
        'size',
        'expires_at',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'size' => 'integer',
    ];
}
