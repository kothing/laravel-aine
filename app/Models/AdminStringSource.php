<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AdminStringSource extends Model
{
    protected $table = 'admin_string_sources';

    protected $fillable = ['source'];
}
