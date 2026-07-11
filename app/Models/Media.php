<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Media extends Model
{
    use HasFactory;

    protected $table = 'media';

    protected $fillable = ['project_id', 'name', 'type', 'size', 'width', 'height', 'caption', 'disk'];

    protected $casts = [
        'project_id' => 'integer',
        'size' => 'integer',
        'width' => 'integer',
        'height' => 'integer',
    ];

    public function getFullUrlAttribute($value) {
        if($this->disk === 'local' || $this->disk === 'public'){
            return asset('storage/'.$this->project->uuid.'/'.$this->name);
        } elseif($this->disk === 's3') {
            return Storage::disk('s3')->url('public/'.$this->project->uuid.'/'.$this->name);
        }
    }
    public function getFullUrlThumbAttribute($value) {
        if($this->disk === 'local' || $this->disk === 'public'){
            return asset('storage/'.$this->project->uuid.'/thumbnails/'.$this->name);
        } elseif($this->disk === 's3') {
            return Storage::disk('s3')->url('public/'.$this->project->uuid.'/thumbnails/'.$this->name);
        }
    }

    protected $appends = ['full_url', 'full_url_thumb'];

    public function project() {
        return $this->belongsTo(Project::class);
    }
}
