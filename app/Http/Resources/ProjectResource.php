<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ProjectResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'slug' => $this->slug,
            'name' => $this->name,
            'description' => $this->description,
            'default_locale' => $this->default_locale ?? 'en',
            'locales' => $this->locales ? explode(',', $this->locales) : [$this->default_locale ?? 'en'],
        ];
    }
}
