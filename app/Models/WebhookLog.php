<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WebhookLog extends Model
{
    protected $fillable = [
        'project_uuid',
        'webhook_id',
        'action',
        'url',
        'status',
        'request',
        'response',
    ];

    protected $casts = [
        'webhook_id' => 'integer',
    ];
}
