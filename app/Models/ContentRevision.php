<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContentRevision extends Model
{
    protected $fillable = [
        'project_id',
        'collection_id',
        'content_id',
        'locale',
        'data',
        'note',
        'created_by',
    ];

    protected $casts = [
        'data' => 'array',
    ];

    /**
     * The user who created this revision.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * The content this revision belongs to.
     */
    public function content(): BelongsTo
    {
        return $this->belongsTo(Content::class, 'content_id');
    }
}
