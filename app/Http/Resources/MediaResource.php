<?php

namespace App\Http\Resources;

use App\Aine\ContentSerializer;
use Illuminate\Http\Resources\Json\JsonResource;

class MediaResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        // Project lookups are memoised per request (ContentSerializer) so
        // serialising many media rows does not hit the database repeatedly.
        $project = ContentSerializer::projectFor($this->project_id);

        $media = [
            'id' => $this->id,
            'file_name' => $this->name,
            'full_url' => $this->full_url,
        ];

        $image_types = ['jpg', 'jpeg', 'png', 'bmp', 'gif', 'webp'];
        if(in_array($this->type, $image_types)){
            $media['full_url_thumb'] = $this->full_url_thumb;
        }

        $media['caption'] = $this->caption;
        $media['size'] = $this->size;

        if(in_array($this->type, $image_types)){
            $media['width'] = $this->width;
            $media['height'] = $this->height;
        }

        return $media;
    }
}
