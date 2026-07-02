<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Form extends Model
{
    protected $table = 'forms';

    protected $fillable = ['name', 'description', 'project_id', 'collection_id', 'submit_btn_text'];

    protected $casts = [
        'project_id' => 'integer',
        'collection_id' => 'integer',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            $model->uuid = (string) Str::uuid()->getHex();
        });
    }
}
