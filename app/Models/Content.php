<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Content extends Model
{
    use SoftDeletes;

    protected $table = "content";

    protected $fillable = [
        'project_id', 'collection_id', 'locale', 'form_id',
        'draft_parent_id',
        'created_by', 'updated_by',
        'published_at', 'published_by', 'scheduled_at',
        'preview_token', 'preview_expires_at',
        'workflow_state', 'reviewer_comment',
    ];

    protected $casts = [
        'project_id' => 'integer',
        'collection_id' => 'integer',
        'form_id' => 'integer',
        'draft_parent_id' => 'integer',
        'preview_expires_at' => 'datetime',
        // NOTE: created_by/updated_by/published_by are intentionally NOT
        // cast — ContentController@index temporarily replaces them with
        // User objects before serialization.
    ];

    protected $hidden = ['deleted_at'];

    // -----------------------------------------------------------------
    // Relationships
    // -----------------------------------------------------------------

    public function meta(){
        return $this->hasMany('App\Models\ContentMeta');
    }

    public function collection(){
        return $this->belongsTo('App\Models\Collection');
    }

    public function form(){
        return $this->belongsTo('App\Models\Form');
    }

    public function creator(){
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(){
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function publisher(){
        return $this->belongsTo(User::class, 'published_by');
    }

    /**
     * The main/published row this draft branch belongs to.
     */
    public function draftParent(): BelongsTo
    {
        return $this->belongsTo(Content::class, 'draft_parent_id');
    }

    /**
     * The draft branch that was created from this main row (if any).
     */
    public function draftChild(): HasOne
    {
        return $this->hasOne(Content::class, 'draft_parent_id');
    }

    // -----------------------------------------------------------------
    // Draft-branch helpers
    // -----------------------------------------------------------------

    /**
     * Whether this row is a draft branch of another (published) row.
     */
    public function isDraftBranch(): bool
    {
        return $this->draft_parent_id !== null;
    }

    /**
     * Whether this main row has a pending draft branch that hasn't been
     * published or discarded yet.
     */
    public function hasPendingDraft(): bool
    {
        return $this->draftChild()->exists();
    }

    /**
     * Whether this content is currently published (visible to the public).
     */
    public function isPublished(): bool
    {
        return $this->published_at !== null;
    }
}
