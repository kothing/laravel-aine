<?php

namespace App\Models;

use Illuminate\Support\Str;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Project extends Model
{
    use HasApiTokens, HasFactory;

    protected $table = "projects";

    protected $fillable = ['name', 'slug', 'description', 'default_locale', 'locales', 'disk', 'public_api', 'domain_whitelist', 'status'];

    protected $hidden = ['deleted_at'];

    protected $casts = [
        'public_api' => 'boolean',
        'domain_whitelist' => 'array',
        'status' => 'boolean',
    ];

    protected static function boot(){
        parent::boot();

        static::creating(function  ($model)  {
            $model->uuid = (string) Str::uuid()->getHex();
            
            if (empty($model->slug)) {
                $model->slug = Str::slug($model->name);
            }
        });
    }

    /**
     * Whether the project is active (enabled).
     */
    public function isActive(): bool
    {
        return (bool) $this->status;
    }

    /**
     * Scope a query to active (enabled) projects only.
     */
    public function scopeActive($query)
    {
        return $query->where('status', 1);
    }

    /**
     * Scope a query to inactive (disabled) projects only.
     */
    public function scopeInactive($query)
    {
        return $query->where('status', 0);
    }

    public function collections(){
        return $this->hasMany('App\Models\Collection')->orderBy('order', 'ASC');
    }

    public function fields(){
        return $this->hasMany('App\Models\CollectionField');
    }

    public function content(){
        return $this->hasMany('App\Models\Content');
    }

    public function meta(){
        return $this->hasMany('App\Models\ContentMeta');
    }

    public function media(){
        return $this->hasMany('App\Models\Media');
    }

    public function webhooks()
    {
        return $this->hasMany('App\Models\Webhook');
    }

    public function webhook_logs(){
        return $this->hasMany('App\Models\WebhookLog', 'project_uuid', 'uuid');
    }

    public function forms(){
        return $this->hasMany('App\Models\Form');
    }
}
