<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AdminTranslationDefault extends Model
{
    protected $table = 'admin_translation_defaults';

    protected $fillable = ['locale', 'source', 'value'];
}
