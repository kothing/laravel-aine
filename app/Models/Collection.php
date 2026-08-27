<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Collection extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = "collections";

    protected $fillable = ['name', 'slug', 'project_id', 'order'];

    protected $casts = [
        'project_id' => 'integer',
    ];

    public function project(){
        return $this->belongsTo('App\Models\Project', 'project_id');
    }

    public function fields(){
        return $this->hasMany('App\Models\CollectionField', 'collection_id')->orderBy('order', 'ASC');
    }

    public function content(){
        return $this->hasMany('App\Models\Content');
    }

    public function meta(){
        return $this->hasMany('App\Models\ContentMeta');
    }
}
