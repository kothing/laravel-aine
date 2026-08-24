<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UiLocale extends Model
{
    protected $table = 'ui_locales';

    protected $fillable = ['code', 'name', 'is_default'];

    protected $casts = [
        'is_default' => 'boolean',
    ];
}
