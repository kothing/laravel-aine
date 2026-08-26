<?php

namespace App\Http\Resources;

use App\Aine\ContentSerializer;
use App\Models\Content;
use Illuminate\Http\Resources\Json\JsonResource;

class ContentResource extends JsonResource
{
    /**
     * @var array<string, bool> "projectId:contentId" of content currently
     * being expanded down the current serialisation path. Used as a cycle
     * guard: when a relation points back at an ancestor, we emit a minimal
     * stub instead of recursing forever (A -> B -> A would otherwise blow
     * the JSON encoder's stack).
     */
    private static $expanding = [];

    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        return $this->buildContent($this->resource);
    }

    /**
     * Recursively build the plain-array representation of a content row.
     * Relations are expanded eagerly here (not as lazy JsonResource
     * instances) so the recursion depth and cycle guard below are real:
     * json_encode would otherwise expand nested resources lazily, outside
     * any depth tracking.
     *
     * @param  \App\Models\Content|null  $content
     * @return array
     */
    private function buildContent($content): array
    {
        $result = [
            'id' => $content->id ?? null,
            'locale' => $content->locale ?? null,
        ];

        // A null resource (e.g. a dangling relation id) mirrors the legacy
        // output shape: an object with null id/locale, nothing more.
        if (! $content instanceof Content) {
            return $result;
        }

        if($content->created_at !== null){
            $result['created_at'] = $content->created_at->toDateTimeString();
        }
        if($content->updated_at !== null){
            $result['updated_at'] = $content->updated_at->toDateTimeString();
        }
        if($content->published_at !== null){
            $result['published_at'] = $content->published_at;
        }

        $collection = $content->collection;
        $meta = $content->meta;

        foreach ($collection->fields as $field) {
            foreach ($meta as $m) {
                if($field->name == $m->field_name){
                    $options = json_decode($field->options);

                    if(!@$options->hiddenInAPI){
                        if(isset($options->repeatable) && $options->repeatable) {
                            if($field->type == 'number'){
                                $result[$m->field_name][] = (float)$m->value;
                            } else {
                                $result[$m->field_name][] = $m->value;
                            }
                        } else {
                            if($field->type == 'boolean'){
                                $result[$m->field_name] = $m->value == 1 ? true : false;
                            } elseif($field->type == 'password'){
                            } elseif($field->type == 'number'){
                                $result[$m->field_name] = (float)$m->value;
                            } elseif($field->type == 'media'){
                                if($options->media->type == 1){
                                    // Single-value media: read from the batch
                                    // preloader (ContentSerializer::preload).
                                    $media = ContentSerializer::mediaFor($content->project_id, (int)$m->value);
                                    $result[$m->field_name] = new MediaResource($media);
                                } else {
                                    $files_arr = explode(',', $m->value);
                                    $mediaItems = [];
                                    foreach ($files_arr as $media_id) {
                                        $item = ContentSerializer::mediaFor($content->project_id, (int)$media_id);
                                        if ($item !== null) {
                                            $mediaItems[] = $item;
                                        }
                                    }
                                    $result[$m->field_name] = MediaResource::collection($mediaItems);
                                }
                            } elseif($field->type == 'relation'){
                                if (!isset($options->relation) || !is_object($options->relation)) {
                                    $result[$m->field_name] = $m->value;
                                    continue;
                                }

                                if($options->relation->type == 1){
                                    $relation = ContentSerializer::relationFor($content->project_id, (int)$m->value);

                                    if ($relation === null) {
                                        // Mirrors the legacy output shape for a
                                        // dangling single relation: an object
                                        // with null id/locale.
                                        $result[$m->field_name] = ['id' => null, 'locale' => null];
                                    } else {
                                        $result[$m->field_name] = $this->expandRelation($content, (int)$m->value);
                                    }
                                } else {
                                    $relation_arr = explode(',', $m->value);

                                    $relationItems = [];
                                    foreach ($relation_arr as $relation_id) {
                                        if (!is_numeric($relation_id)) {
                                            continue;
                                        }
                                        $item = $this->expandRelation($content, (int)$relation_id);
                                        if ($item !== null) {
                                            $relationItems[] = $item;
                                        }
                                    }

                                    $result[$m->field_name] = $relationItems;
                                }
                            } else {
                                $result[$m->field_name] = $m->value;
                            }
                        }
                    }
                }
            }
        }

        return $result;
    }

    /**
     * Expand a single relation value into a plain array.
     *
     * Returns null when the relation target is not found (dangling id /
     * unpublished content) so multi-value lists can skip it; returns a
     * minimal id/locale stub when the target is already on the current
     * expansion path (cyclic relation).
     *
     * @param  \App\Models\Content  $parent
     * @param  int  $relationId
     * @return array|null
     */
    private function expandRelation(Content $parent, int $relationId): ?array
    {
        $relation = ContentSerializer::relationFor($parent->project_id, $relationId);

        if ($relation === null) {
            return null;
        }

        $key = $relation->project_id.':'.$relation->id;

        if (isset(self::$expanding[$key])) {
            // Cycle detected: emit a stub instead of recursing forever.
            return ['id' => $relation->id, 'locale' => $relation->locale];
        }

        self::$expanding[$key] = true;

        try {
            return $this->buildContent($relation);
        } finally {
            unset(self::$expanding[$key]);
        }
    }
}
