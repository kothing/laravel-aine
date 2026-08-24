<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProjectTranslation extends Model
{
    protected $table = 'project_translations';

    protected $fillable = ['project_id', 'locale', 'source', 'value'];

    protected $casts = [
        'project_id' => 'integer',
    ];

    public function project()
    {
        return $this->belongsTo(Project::class);
    }
}
