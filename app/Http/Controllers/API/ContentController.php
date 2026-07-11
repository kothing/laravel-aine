<?php

namespace App\Http\Controllers\API;

use App\Models\Content;
use App\Models\Project;
use App\Models\Collection;
use App\Models\ContentMeta;
use Illuminate\Http\Request;
use App\Models\CollectionField;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Hash;
use App\Http\Resources\ContentResource;
use App\Http\Resources\ProjectResource;
use Illuminate\Support\Facades\Validator;
use App\Http\Controllers\API\Concerns\AuthorizesProjectApi;

class ContentController extends Controller {

    use AuthorizesProjectApi;

    /**
     * Get all content
     *
     * @param string $uuid
     * @param string $slug
     * @param \Illuminate\Http\Request $request
     * @return \App\Http\Resources\ContentResource
     */
    public function getContentListByUuid($uuid, $slug, Request $request){
        $project = Project::where('uuid', $uuid)->first();

        if(!$project){
            return $this->notFound('Project not found');
        }
        if ($response = $this->authorizeProjectRead($project)) {
            return $response;
        }

        $collection = Collection::where('project_id', $project->id)->where('slug', $slug)->first();
        if(!$collection) {
            return $this->notFound('Collection not found');
        }

        $content =  Content::with(['meta', 'collection'])
                        ->where('project_id', $project->id)
                        ->where('collection_id', $collection->id);

        if($request->has('where')){
            $where = $request->get('where');
            if(!is_array($where)) {
                return $this->validationError('Incorrect where statement. See documentation: #where-clauses');
            }

            if (!is_numeric(array_key_first($where)) || array_key_first($where) != 'or') {
                $multiDim = false;
            } else {
                $multiDim = true;
            }

            //Combining where clauses
            if($multiDim){
                $metaSql = 'SELECT c.id as content_id FROM content c,';

                $num = 1;
                foreach ($where as $w) {
                    $metaSql .= ' content_meta m'.$num.',';
                    $num++;
                }
                $metaSql = rtrim($metaSql, ',');
                $metaSql .= ' WHERE ';
                $num = 1;
                foreach ($where as $w) {
                    $metaSql .= ' m'.$num.".project_id='".$project->id."' AND m".$num.".collection_id='".$collection->id."' AND ";
                    $num++;
                }
                $metaSql = rtrim($metaSql, ' AND ');
                $num = 1;
                foreach ($where as $w) {
                    $metaSql .= ' AND c.id = m'.$num.'.content_id';
                    $num++;
                }
                $metaSql .= " AND (";
                $num = 1;
                foreach ($where as $k => $w) {
                    foreach ($w as $key => $value) {
                        if($key == 'id' || $key == 'locale' || $key == 'created_at' || $key == 'updated_at' || $key == 'published_at'){
                            if(is_array($value)){
                                if(isset($value['not'])){
                                    $content = $content->where($key, '!=', $value['not']);
                                } elseif(isset($value['in'])){
                                    $content = $content->whereIn($key, explode(',', $value['in']));
                                } elseif(isset($value['not_in'])){
                                    $content = $content->whereNotIn($key, explode(',', $value['not_in']));
                                } elseif(isset($value['lt'])){
                                    if($key == 'created_at' || $key == 'updated_at' || $key == 'published_at'){
                                        $content = $content->whereDate($key, '<', $value['lt']);
                                    } else {
                                        $content = $content->where($key, '<', $value['lt']);
                                    }
                                } elseif(isset($value['lte'])){
                                    if($key == 'created_at' || $key == 'updated_at' || $key == 'published_at'){
                                        $content = $content->whereDate($key, '<=', $value['lte']);
                                    } else {
                                        $content = $content->where($key, '<=', $value['lte']);
                                    }
                                } elseif(isset($value['gt'])){
                                    if($key == 'created_at' || $key == 'updated_at' || $key == 'published_at'){
                                        $content = $content->whereDate($key, '>', $value['gt']);
                                    } else {
                                        $content = $content->where($key, '>', $value['gt']);
                                    }
                                } elseif(isset($value['gte'])){
                                    if($key == 'created_at' || $key == 'updated_at' || $key == 'published_at'){
                                        $content = $content->whereDate($key, '>=', $value['gte']);
                                    } else {
                                        $content = $content->where($key, '>=', $value['gte']);
                                    }
                                } elseif(isset($value['between'])){
                                    if(count(explode(',', $value['between'])) <= 1 || count(explode(',', $value['between'])) > 2) return $this->validationError('Incorrect where statement');

                                    $content = $content->whereBetween($key, explode(',', $value['between']));
                                } elseif(isset($value['not_between'])){
                                    if(count(explode(',', $value['not_between'])) <= 1 || count(explode(',', $value['not_between'])) > 2) return $this->validationError('Incorrect where statement');

                                    $content = $content->whereNotBetween($key, explode(',', $value['not_between']));
                                }
                            } else {
                                if($key == 'created_at' || $key == 'updated_at' || $key == 'published_at'){
                                    $content = $content->whereDate($key, $value);
                                } else {
                                    $content = $content->where($key, $value);
                                }
                            }
                        } else {
                            $bind[] = $key;

                            if($num != 1 && $k == 'or'){
                                $metaSql .= " OR ";
                            }
                            if($num > 1 && $k != 'or'){
                                $metaSql .= " AND ";
                            }

                            if(is_array($value)){
                                $metaSql .= "(m".$num.".field_name= ? AND ";
                                if(isset($value['like'])){
                                    $bind[] = "%$value[like]%";
                                    $metaSql .= "m".$num.".value LIKE ?)";
                                } elseif(isset($value['not'])){
                                    $bind[] = $value['not'];
                                    $metaSql .= "m".$num.".value != ?)";
                                } elseif(isset($value['in'])){
                                    $inValues = array_map('trim', explode(',', $value['in']));
                                    $placeholders = implode(', ', array_fill(0, count($inValues), '?'));
                                    $metaSql .= "m".$num.".value IN ( ".$placeholders." ))";
                                    foreach ($inValues as $inValue) {
                                        $bind[] = $inValue;
                                    }
                                } elseif(isset($value['not_in'])){
                                    $notInValues = array_map('trim', explode(',', $value['not_in']));
                                    $placeholders = implode(', ', array_fill(0, count($notInValues), '?'));
                                    $metaSql .= "m".$num.".value NOT IN ( ".$placeholders." ))";
                                    foreach ($notInValues as $notInValue) {
                                        $bind[] = $notInValue;
                                    }
                                } elseif(isset($value['lt'])){
                                    $bind[] = $value['lt'];
                                    $metaSql .= "m".$num.".value < ?)";
                                } elseif(isset($value['lte'])){
                                    $bind[] = $value['lte'];
                                    $metaSql .= "m".$num.".value <= ?)";
                                } elseif(isset($value['gt'])){
                                    $bind[] = $value['gt'];
                                    $metaSql .= "m".$num.".value > ?)";
                                } elseif(isset($value['gte'])){
                                    $bind[] = $value['gte'];
                                    $metaSql .= "m".$num.".value >= ?)";
                                } elseif(isset($value['between'])){
                                    $expBetween = array_map('trim', explode(',', $value['between']));
                                    if(count($expBetween) <= 1 || count($expBetween) > 2) return $this->validationError('Incorrect where statement');

                                    $metaSql .= "m".$num.".value BETWEEN ? AND ?)";
                                    $bind[] = $expBetween[0];
                                    $bind[] = $expBetween[1];
                                } elseif(isset($value['not_between'])){
                                    $expBetween = array_map('trim', explode(',', $value['not_between']));
                                    if(count($expBetween) <= 1 || count($expBetween) > 2) return $this->validationError('Incorrect where statement');

                                    $metaSql .= "m".$num.".value NOT BETWEEN ? AND ?)";
                                    $bind[] = $expBetween[0];
                                    $bind[] = $expBetween[1];
                                }
                            } else {
                                if($value == 'null'){
                                    $meta = ContentMeta::where('project_id', $project->id)->where('collection_id', $collection->id)->where('field_name', $key)->where('value', '!=', '')->get(['content_id']);

                                    $in_str = "";
                                    foreach ($meta as $m) {
                                        $in_str .= $m->content_id.",";
                                    }
                                    $in_str = rtrim($in_str, ',');

                                    $metaSql .= "m".$num.".content_id NOT IN ( ".$in_str." )";

                                } elseif($value == 'not_null'){
                                    $meta = ContentMeta::where('project_id', $project->id)->where('collection_id', $collection->id)->where('field_name', $key)->where('value', '!=', '')->get(['content_id']);

                                    $in_str = "";
                                    foreach ($meta as $m) {
                                        $in_str .= $m->content_id.",";
                                    }
                                    $in_str = rtrim($in_str, ',');

                                    $metaSql .= "m".$num.".content_id IN ( ".$in_str." )";
                                } else {
                                    $field = CollectionField::where('project_id', $project->id)->where('collection_id', $collection->id)->where('name', $key)->first();

                                    if(!$field){
                                        return $this->validationError('Field not found ['.$key.']');
                                    }

                                    if($field->type == "relation"){
                                        $metaSql .= "(m".$num.".field_name= ? AND ";
                                        $metaSql .= "FIND_IN_SET(?, cast(m".$num.".value as char)) > 0)";
                                        $bind[] = $value;
                                    } else {
                                        $bind[] = $value;
                                        $metaSql .= "(m".$num.".field_name= ? AND ";
                                        $metaSql .= "m".$num.".value= ?)";
                                    }
                                }
                            }
                        }
                    }
                    $num++;
                }
                $metaSql .= ")";
                $num = 1;
                foreach ($where as $w) {
                    $metaSql .= " AND m".$num.".deleted_at is null";
                    $num++;
                }

                $query = DB::select($metaSql, $bind);

                $ids = [];
                foreach ($query as $q) {
                    $ids[] = $q->content_id;
                }

                $content =  $content->whereIn('id', $ids);
            } else {
                //Single Where
                $meta = ContentMeta::where('project_id', $project->id)->where('collection_id', $collection->id);
                foreach ($where as $key => $value) {
                    if($key == 'id' || $key == 'locale' || $key == 'created_at' || $key == 'updated_at' || $key == 'published_at'){
                        if(is_array($value)){
                            if(isset($value['not'])){
                                $content = $content->where($key, '!=', $value['not']);
                            } elseif(isset($value['in'])){
                                $content = $content->whereIn($key, explode(',', $value['in']));
                            } elseif(isset($value['not_in'])){
                                $content = $content->whereNotIn($key, explode(',', $value['not_in']));
                            } elseif(isset($value['lt'])){
                                if($key == 'created_at' || $key == 'updated_at' || $key == 'published_at'){
                                    $content = $content->whereDate($key, '<', $value['lt']);
                                } else {
                                    $content = $content->where($key, '<', $value['lt']);
                                }
                            } elseif(isset($value['lte'])){
                                if($key == 'created_at' || $key == 'updated_at' || $key == 'published_at'){
                                    $content = $content->whereDate($key, '<=', $value['lte']);
                                } else {
                                    $content = $content->where($key, '<=', $value['lte']);
                                }
                            } elseif(isset($value['gt'])){
                                if($key == 'created_at' || $key == 'updated_at' || $key == 'published_at'){
                                    $content = $content->whereDate($key, '>', $value['gt']);
                                } else {
                                    $content = $content->where($key, '>', $value['gt']);
                                }
                            } elseif(isset($value['gte'])){
                                if($key == 'created_at' || $key == 'updated_at' || $key == 'published_at'){
                                    $content = $content->whereDate($key, '>=', $value['gte']);
                                } else {
                                    $content = $content->where($key, '>=', $value['gte']);
                                }
                            } elseif(isset($value['between'])){
                                if(count(explode(',', $value['between'])) <= 1 || count(explode(',', $value['between'])) > 2) return $this->validationError('Incorrect where statement');

                                $content = $content->whereBetween($key, explode(',', $value['between']));
                            } elseif(isset($value['not_between'])){
                                if(count(explode(',', $value['not_between'])) <= 1 || count(explode(',', $value['not_between'])) > 2) return $this->validationError('Incorrect where statement');

                                $content = $content->whereNotBetween($key, explode(',', $value['not_between']));
                            }
                        } else {
                            if($key == 'created_at' || $key == 'updated_at' || $key == 'published_at'){
                                $content = $content->whereDate($key, $value);
                            } else {
                                $content = $content->where($key, $value);
                            }
                        }
                    } else {
                        if(is_array($value)){
                            if(isset($value['like'])){
                                $meta = $meta->where('field_name', $key)->where('value', 'LIKE', "%$value[like]%");
                            } elseif(isset($value['not'])){
                                $meta = $meta->where('field_name', $key)->where('value', '!=', "$value[not]");
                            } elseif(isset($value['in'])){
                                $meta = $meta->where('field_name', $key)->whereIn('value', explode(',', $value['in']));
                            } elseif(isset($value['not_in'])){
                                $meta = $meta->where('field_name', $key)->whereNotIn('value', explode(',', $value['not_in']));
                            } elseif(isset($value['lt'])){
                                $meta = $meta->where('field_name', $key)->where('value', '<', $value['lt']);
                            } elseif(isset($value['lte'])){
                                $meta = $meta->where('field_name', $key)->where('value', '<=', $value['lte']);
                            } elseif(isset($value['gt'])){
                                $meta = $meta->where('field_name', $key)->where('value', '>', $value['gt']);
                            } elseif(isset($value['gte'])){
                                $meta = $meta->where('field_name', $key)->where('value', '>=', $value['gte']);
                            } elseif(isset($value['between'])){
                                if(count(explode(',', $value['between'])) <= 1 || count(explode(',', $value['between'])) > 2) {
                                    return $this->validationError('Incorrect where statement');
                                }

                                $meta = $meta->where('field_name', $key)->whereBetween('value', explode(',', $value['between']));
                            } elseif(isset($value['not_between'])){
                                if(count(explode(',', $value['not_between'])) <= 1 || count(explode(',', $value['not_between'])) > 2) {
                                    return $this->validationError('Incorrect where statement');
                                }

                                $meta = $meta->where('field_name', $key)->whereNotBetween('value', explode(',', $value['not_between']));
                            }
                        } else {
                            if($value == 'null'){
                                $copy = clone $meta;
                                $copy = $copy->where('field_name', $key)->where('value', '!=', '')->get(['content_id']);
                                $meta = $meta->whereNotIn('content_id', $copy);
                            } elseif($value == 'not_null'){
                                $copy = clone $meta;
                                $copy = $copy->where('field_name', $key)->where('value', '!=', '')->get(['content_id']);
                                $meta = $meta->whereIn('content_id', $copy);
                            } else {
                                $field = CollectionField::where('project_id', $project->id)->where('collection_id', $collection->id)->where('name', $key)->first();

                                if(!$field){
                                    return $this->validationError('Field not found ['.$key.']');
                                }

                                if($field->type == "relation"){
                                    $meta = $meta->where('field_name', $key)->whereRaw('FIND_IN_SET(?, cast(value as char)) > 0', [$value]);
                                } else {
                                    $meta = $meta->where('field_name', $key)->where('value', $value);
                                }
                            }
                        }
                    }
                }
                $meta = $meta->get(['content_id']);
                $content =  $content->whereIn('id', $meta);
            }
        }

        if($request->has('whereRelation')){
            $whereRelation = $request->get('whereRelation');
            if(!is_array($whereRelation)) {
                return $this->validationError('Incorrect whereRelation statement. See documentation: #where-through-relation');
            }
            
            foreach ($whereRelation as $key => $value) {
                $mainField = CollectionField::where('project_id', $project->id)->where('collection_id', $collection->id)->where('name', $key)->first();

                if(!$mainField){
                    return $this->validationError('Field not found ['.$key.']');
                }
                if($mainField->type !== "relation"){
                    return $this->validationError('This field is not a relation type field.');
                }

                $relationOptions = json_decode($mainField->options);

                foreach($value as $rKey => $rValue){
                    $relationField = CollectionField::where('project_id', $project->id)->where('collection_id', $relationOptions->relation->collection)->where('name', $rKey)->first();

                    if(!$relationField){
                        return $this->validationError('Relation field not found ['.$rKey.']');
                    }

                    $relationMeta = ContentMeta::where('project_id', $project->id)->where('collection_id', $relationOptions->relation->collection)->where('field_name', $rKey)->where('value', 'LIKE', "%$rValue%")->first(['content_id']);

                    if(!$relationMeta){
                        return $this->notFound('Record not found');
                    }

                    $metaThroughRelation = ContentMeta::where('project_id', $project->id)->where('collection_id', $collection->id)->where('field_name', $key)->whereRaw('FIND_IN_SET(?, cast(value as char)) > 0', [$relationMeta->content_id]);

                    $metaThroughRelation = $metaThroughRelation->get(['content_id']);

                    $content =  $content->whereIn('id', $metaThroughRelation);
                }
            }
        }

        if($request->has('sort')){
            $sortM = explode(',', $request->get('sort'));

            foreach ($sortM as $s) {
                $sort = explode(':', $s);
                if(count($sort) <= 1 || count($sort) > 2) {
                    return $this->validationError('Incorrect sort statement');
                }

                if($sort[0] == 'id' || $sort[0] == 'locale' || $sort[0] == 'created_at' || $sort[0] == 'updated_at' || $sort[0] == 'published_at'){
                    $content = $content->orderBy($sort[0], $sort[1]);
                } else {
                    $content = $content->orderBy(
                        ContentMeta::select('value')
                            ->whereColumn('content_meta.content_id', 'content.id')
                            ->where('field_name', $sort[0])
                            ->latest()
                            ->take(1),
                            $sort[1]
                    );
                }
            }
        }

        if($request->has('state')){
            if($request->get('state') == 'only_draft'){
                $content = $content->whereNull('published_at');
            }
        } else {
            $content = $content->whereNotNull('published_at');
        }

        if($request->has('offset') && !$request->has('limit')){
            return $this->validationError('Incorrect offset statement. Offset must be used with limit. Documentation: #limit');
        }

        if($request->has('offset')){
            $content = $content->offset($request->get('offset'));
        }
        if($request->has('limit')){
            $content = $content->limit($request->get('limit'));
        }

        if($request->has('count')){
            return $this->success($content->count(), 'Success');
        } else {
            $selectFields = ['id', 'project_id', 'collection_id', 'locale'];

            if($request->has('timestamps')){
                $selectFields[] = 'created_at';
                $selectFields[] = 'updated_at';
                $selectFields[] = 'published_at';
            }
            $content = $content->select($selectFields);

            if($request->has('first')){
                $content = $content->first();
                if(!$content) return $this->notFound('Not found');

                return $this->success(new ContentResource($content), 'Success');
            } else {
                $content =  $content->get();
                return $this->success(ContentResource::collection($content), 'Success');
            }
        }
    }

    /**
     * Get content by id
     *
     * @param string $uuid
     * @param string $slug
     * @param int $id
     * @return \App\Http\Resources\ContentResource
     */
    public function getContentByUuid($uuid, $slug, $slug_id, Request $request){
        $project = Project::where('uuid', $uuid)->first();
        if(!$project){
            return response(['error' => 'Project not found!'], 404);
        }
        if ($response = $this->authorizeProjectRead($project)) {
            return $response;
        }

        $collection = Collection::where('project_id', $project->id)->where('slug', $slug)->first();

        if(!$collection) {
            return response(['error' => 'Collection not found!'], 404);
        }

        $content =  Content::with('meta')
                        ->where('project_id', $project->id)
                        ->where('collection_id', $collection->id)
                        ->whereNotNull('published_at');

        $selectFields = ['id', 'project_id', 'collection_id', 'locale'];

        if($request->has('timestamps')){
            $selectFields[] = 'created_at';
            $selectFields[] = 'updated_at';
            $selectFields[] = 'published_at';
        }
        $content = $content->select($selectFields)->find($slug_id);

        if(!$content) {
            return $this->notFound('Not found');
        }

        return $this->success(new ContentResource($content), 'Success');
    }

    /**
     * Create new content
     *
     * @param string $uuid
     * @param string $slug
     * @param \Illuminate\Http\Request $request
     * @return \App\Http\Resources\ContentResource
     */
    public function createContentByUuid($uuid, $slug, Request $request){
        if ($response = $this->authorizeProjectAbility('create', $uuid)) {
            return $response;
        }

        $auth = auth('sanctum')->user();
        $project = Project::find($auth->id);
        if(!$project) {
            return $this->notFound('Project not found');
        }

        $collection = Collection::with(['fields'])->where('project_id', $project->id)->where('slug', $slug)->first();
        if(!$collection) {
            return $this->notFound('Collection not found');
        }

        $rules = [];
        $messages = [];

        foreach ($collection->fields as $field) {
            $validations = json_decode($field->validations);
            $options = json_decode($field->options);

            //Check if repeatable fields are array
            if(isset($options->repeatable) && $options->repeatable) {
                if($request->has($field->name)){
                    $repeatableField = $request->get($field->name);
                    if(!is_array($repeatableField)){
                        return $this->validationError('Repeatable field '.$field->name.' must be an array!');
                    }
                }
            }

            if($validations->required->status){
                if(isset($options->repeatable) && $options->repeatable) {
                    if($request->has($field->name)){
                        $repeatableField = $request->get($field->name);
                        foreach($repeatableField as $rf_key => $rf_value){
                            $rules[$field->name.'.*'][] = 'required';
                            $messages[$field->name.'.*'.'.required'] = 'The '.$field->name.' field is required.';

                            if($validations->required->message != null){
                                $messages[$field->name.'.*'.'.required'] = $validations->required->message;
                            }
                        }
                    }
                } else {
                    $rules[$field->name][] = 'required';
                    $messages[$field->name.'.required'] = 'The '.$field->name.' field is required.';

                    if($validations->required->message != null){
                        $messages[$field->name.'.required'] = $validations->required->message;
                    }
                }
            }

            if($field->type == "email"){
                if(isset($options->repeatable) && $options->repeatable) {
                    if($request->has($field->name)){
                        $repeatableField = $request->get($field->name);
                        foreach($repeatableField as $rf_value){
                            $rules[$field->name.'.*'][] = 'nullable';
                            $rules[$field->name.'.*'][] = 'email';
                            $messages[$field->name.'.*'.'.email'] = 'The '.$field->name.' must be a valid email address.';
                        }
                    }
                } else {
                    $rules[$field->name][] = 'email';
                    $messages[$field->name.'.email'] = 'The '.$field->name.' must be a valid email address.';
                }
            }
            if($field->type == "number"){
                if(isset($options->repeatable) && $options->repeatable) {
                    if($request->has($field->name)){
                        $repeatableField = $request->get($field->name);
                        foreach($repeatableField as $rf_value){
                            $rules[$field->name.'.*'][] = 'nullable';
                            $rules[$field->name.'.*'][] = 'numeric';
                            $messages[$field->name.'.*'.'.numeric'] = 'The '.$field->name.' must be numeric.';
                        }
                    }
                } else {
                    $rules[$field->name][] = 'numeric';
                    $messages[$field->name.'.numeric'] = 'The '.$field->name.' must be numeric.';
                }
            }
            if ($field->type == 'color') {
                if(isset($options->repeatable) && $options->repeatable) {
                    if($request->has($field->name)){
                        $repeatableField = $request->get($field->name);
                        foreach($repeatableField as $rf_key => $rf_value){
                            $rules[$field->name.'.*'][] = 'nullable';
                            $rules[$field->name.'.*'][] = 'color';
                            $messages[$field->name.'.*'.'.color'] = 'The '.$field->name.' must be a color string.';
                        }
                    }
                } else {
                    $rules[$field->name][] = 'color';
                    $messages[$field->name.'.color'] = 'The '.$field->name.' must be a color string.';
                }
            }

            if($validations->charcount->status){
                if($validations->charcount->type == "Between"){
                    if(isset($options->repeatable) && $options->repeatable) {
                        if($request->has($field->name)){
                            $repeatableField = $request->get($field->name);
                            foreach($repeatableField as $rf_key => $rf_value){
                                $rules[$field->name.'.*'][] = 'between:'.$validations->charcount->min.','.$validations->charcount->max;
                                $messages[$field->name.'.*'.'.between'] = 'The '.$field->name.' must be between '.$validations->charcount->min.' and '.$validations->charcount->max;

                                if($field->type != 'number'){
                                    $messages[$field->name.'.*'.'.between'] .= ' characters.';
                                }
                            }
                        }
                    } else {
                        $rules[$field->name][] = 'between:'.$validations->charcount->min.','.$validations->charcount->max;
                        $messages[$field->name.'.between'] = 'The '.$field->name.' must be between '.$validations->charcount->min.' and '.$validations->charcount->max;

                        if($field->type != 'number'){
                            $messages[$field->name.'.between'] .= ' characters.';
                        }
                    }
                } elseif($validations->charcount->type == "Min") {
                    if(isset($options->repeatable) && $options->repeatable) {
                        if($request->has($field->name)){
                            $repeatableField = $request->get($field->name);
                            foreach($repeatableField as $rf_key => $rf_value){
                                $rules[$field->name.'.*'][] = 'min:'.$validations->charcount->min;
                                $messages[$field->name.'.*'.'.min'] = 'The '.$field->name.' must be at least '.$validations->charcount->min;

                                if($field->type != 'number'){
                                    $messages[$field->name.'.*'.'.min'] .= ' characters.';
                                }
                            }
                        }
                    } else {
                        $rules[$field->name][] = 'min:'.$validations->charcount->min;
                        $messages[$field->name.'.min'] = 'The '.$field->name.' must be at least '.$validations->charcount->min;

                        if($field->type != 'number'){
                            $messages[$field->name.'.min'] .= ' characters.';
                        }
                    }
                } elseif($validations->charcount->type == "Max") {
                    if(isset($options->repeatable) && $options->repeatable) {
                        if($request->has($field->name)){
                            $repeatableField = $request->get($field->name);
                            foreach($repeatableField as $rf_key => $rf_value){
                                $rules[$field->name.'.*'][] = 'max:'.$validations->charcount->max;
                                $messages[$field->name.'.*'.'.max'] = 'The '.$field->name.' may not be greater than '.$validations->charcount->max;

                                if($field->type != 'number'){
                                    $messages[$field->name.'.*'.'.max'] .= ' characters.';
                                }
                            }
                        }
                    } else {
                        $rules[$field->name][] = 'max:'.$validations->charcount->max;
                        $messages[$field->name.'.max'] = 'The '.$field->name.' may not be greater than '.$validations->charcount->max;

                        if($field->type != 'number'){
                            $messages[$field->name.'.max'] .= ' characters.';
                        }
                    }
                }
            }
        }

        Validator::extend('color', function ($attribute, $value, $parameters, $validator) {
            $color_regex = "/(#(?:[0-9a-f]{2}){2,4}$|(#[0-9a-f]{3}$)|(rgb|hsl)a?\((-?\d*\.?\d*+%?[,\s]+){2,3}\s*[\d\.]+%?\)$|black$|silver$|gray$|whitesmoke$|maroon$|red$|purple$|fuchsia$|green$|lime$|olivedrab$|yellow$|navy$|blue$|teal$|aquamarine$|orange$|aliceblue$|antiquewhite$|aqua$|azure$|beige$|bisque$|blanchedalmond$|blueviolet$|brown$|burlywood$|cadetblue$|chartreuse$|chocolate$|coral$|cornflowerblue$|cornsilk$|crimson$|currentcolor$|darkblue$|darkcyan$|darkgoldenrod$|darkgray$|darkgreen$|darkgrey$|darkkhaki$|darkmagenta$|darkolivegreen$|darkorange$|darkorchid$|darkred$|darksalmon$|darkseagreen$|darkslateblue$|darkslategray$|darkslategrey$|darkturquoise$|darkviolet$|deeppink$|deepskyblue$|dimgray$|dimgrey$|dodgerblue$|firebrick$|floralwhite$|forestgreen$|gainsboro$|ghostwhite$|goldenrod$|gold$|greenyellow$|grey$|honeydew$|hotpink$|indianred$|indigo$|ivory$|khaki$|lavenderblush$|lavender$|lawngreen$|lemonchiffon$|lightblue$|lightcoral$|lightcyan$|lightgoldenrodyellow$|lightgray$|lightgreen$|lightgrey$|lightpink$|lightsalmon$|lightseagreen$|lightskyblue$|lightslategray$|lightslategrey$|lightsteelblue$|lightyellow$|limegreen$|linen$|mediumaquamarine$|mediumblue$|mediumorchid$|mediumpurple$|mediumseagreen$|mediumslateblue$|mediumspringgreen$|mediumturquoise$|mediumvioletred$|midnightblue$|mintcream$|mistyrose$|moccasin$|navajowhite$|oldlace$|olive$|orangered$|orchid$|palegoldenrod$|palegreen$|paleturquoise$|palevioletred$|papayawhip$|peachpuff$|peru$|pink$|plum$|powderblue$|rosybrown$|royalblue$|saddlebrown$|salmon$|sandybrown$|seagreen$|seashell$|sienna$|skyblue$|slateblue$|slategray$|slategrey$|snow$|springgreen$|steelblue$|tan$|thistle$|tomato$|transparent$|turquoise$|violet$|wheat$|white$|yellowgreen$|rebeccapurple$)/i";

            return preg_match($color_regex, $value);
        });

        $validator = Validator::make($request->except(['locale']), $rules, $messages);
        $validator->validate();

        $uniqueErrors = [];

        foreach ($collection->fields as $field) {
            $validations = json_decode($field->validations);
            if($validations->unique->status){
                if($request->has($field->name)){
                    $data = ContentMeta::where('collection_id', $collection->id)->where('field_name', $field->name)->where('value', $request->get($field->name))->count();

                    if($data !== 0){
                        $uniqueErrors['errors'][$field->name] = ['The '.$field->name.' has already been taken.'];

                        if($validations->unique->message != null){
                            $uniqueErrors['errors'][$field->name] = [$validations->unique->message];
                        }
                    }
                }
            }
        }
        if(count($uniqueErrors) !== 0){
            return response($uniqueErrors, 422);
        }

        $content = Content::create([
            'project_id' => $project->id,
            'collection_id' => $collection->id,
            'locale' => $request->has('locale') ? $request->get('locale') : $project->default_locale,
            'created_by' => null,
            'published_at' => $request->has('draft') && $request->get('draft') == 1 ? null : now(),
            'published_by' => null
        ]);

        $content_data = $request->all();

        foreach ($content_data as $key => $value) {
            $val = $value;

            foreach ($collection->fields as $field) {
                if($field->name == $key){
                    $field_type = $field->type;
                    $field_options = json_decode($field->options);
                }
            }

            if(!empty($value) && $key !== 'locale' && $key !== 'draft'){
                if($field_type == 'password'){
                    $val = Hash::make($value);
                }
                if ($field_type == 'enumeration') {
                    if (isset($field_options->multiple) && $field_options->multiple && is_array($value)) {
                        $str = '';
                        foreach ($value as $vv) {
                            $str .= $vv.',';
                        }
                        $val = rtrim($str, ',');
                    } else {
                        $val = $value;
                    }
                }
                if($field_type == 'media'){
                    $str = '';
                    foreach ($value as $file) {
                        $str .= $file.',';
                    }
                    $val = rtrim($str, ',');
                }
                if($field_type == 'relation'){
                    $rl = explode(',', $value);
                    $str = '';
                    foreach ($rl as $relation) {
                        $str .= $relation.',';
                    }
                    $val = rtrim($str, ',');
                }
                if($field_type == 'json'){
                    $val = json_encode($value);
                }

                if(isset($field_options->repeatable) && $field_options->repeatable){
                    foreach($value as $rf_item){
                        if(!empty($rf_item)){
                            $content_meta = ContentMeta::create([
                                'project_id' => $project->id,
                                'collection_id' => $collection->id,
                                'content_id' => $content->id,
                                'field_name' => $key,
                                'value' => $rf_item
                            ]);
                        }
                    }
                } else {
                    $content_meta = ContentMeta::create([
                        'project_id' => $project->id,
                        'collection_id' => $collection->id,
                        'content_id' => $content->id,
                        'field_name' => $key,
                        'value' => $val
                    ]);
                }
            }
        }

        \App\Events\ContentCreated::dispatch([
            'source' => 'API',
            'content' => $content
        ]);

        return $this->created(new ContentResource($content), 'Content created successfully');
    }

    /**
     * Update a content
     *
     * @param string $uuid
     * @param string $slug
     * @param int $id
     * @param \Illuminate\Http\Request $request
     * @return \App\Http\Resources\ContentResource
     */
    public function updateContentByUuid($uuid, $slug, $slug_id, Request $request){
        if ($response = $this->authorizeProjectAbility('update', $uuid)) {
            return $response;
        }

        $auth = auth('sanctum')->user();
        $project = Project::find($auth->id);
        if(!$project) {
            return $this->notFound('Project not found');
        }

        $collection = Collection::with(['fields'])->where('project_id', $project->id)->where('slug', $slug)->first();
        if(!$collection) {
            return $this->notFound('Collection not found');
        }

        $content = Content::where('project_id', $project->id)->where('collection_id', $collection->id)->where('id', $slug_id)->first();
        if(!$content) {
            return $this->notFound('Record not found');
        }

        $rules = [];
        $messages = [];

        foreach ($collection->fields as $field) {
            $validations = json_decode($field->validations);
            $options = json_decode($field->options);

            //Check if repeatable fields are array
            if(isset($options->repeatable) && $options->repeatable) {
                if($request->has($field->name)){
                    $repeatableField = $request->get($field->name);
                    if(!is_array($repeatableField)){
                        return $this->validationError('Repeatable field '.$field->name.' must be an array!');
                    }
                }
            }

            if($validations->required->status){
                if(isset($options->repeatable) && $options->repeatable) {
                    if($request->has($field->name)){
                        $repeatableField = $request->get($field->name);
                        foreach($repeatableField as $rf_key => $rf_value){
                            $rules[$field->name.'.*'][] = 'required';
                            $messages[$field->name.'.*'.'.required'] = 'The '.$field->name.' field is required.';

                            if($validations->required->message != null){
                                $messages[$field->name.'.*'.'.required'] = $validations->required->message;
                            }
                        }
                    }
                } else {
                    $rules[$field->name][] = 'required';
                    $messages[$field->name.'.required'] = 'The '.$field->name.' field is required.';

                    if($validations->required->message != null){
                        $messages[$field->name.'.required'] = $validations->required->message;
                    }
                }
            }

            if($field->type == "email"){
                if(isset($options->repeatable) && $options->repeatable) {
                    if($request->has($field->name)){
                        $repeatableField = $request->get($field->name);
                        foreach($repeatableField as $rf_key => $rf_value){
                            $rules[$field->name.'.*'][] = 'nullable';
                            $rules[$field->name.'.*'][] = 'email';
                            $messages[$field->name.'.*'.'.email'] = 'The '.$field->name.' must be a valid email address.';
                        }
                    }
                } else {
                    $rules[$field->name][] = 'email';
                    $messages[$field->name.'.email'] = 'The '.$field->name.' must be a valid email address.';
                }
            }
            if($field->type == "number"){
                if(isset($options->repeatable) && $options->repeatable) {
                    if($request->has($field->name)){
                        $repeatableField = $request->get($field->name);
                        foreach($repeatableField as $rf_key => $rf_value){
                            $rules[$field->name.'.*'][] = 'nullable';
                            $rules[$field->name.'.*'][] = 'numeric';
                            $messages[$field->name.'.*'.'.numeric'] = 'The '.$field->name.' must be numeric.';
                        }
                    }
                } else {
                    $rules[$field->name][] = 'numeric';
                    $messages[$field->name.'.numeric'] = 'The '.$field->name.' must be numeric.';
                }
            }
            if ($field->type == 'color') {
                if(isset($options->repeatable) && $options->repeatable) {
                    if($request->has($field->name)){
                        $repeatableField = $request->get($field->name);
                        foreach($repeatableField as $rf_key => $rf_value){
                            $rules[$field->name.'.*'][] = 'nullable';
                            $rules[$field->name.'.*'][] = 'color';
                            $messages[$field->name.'.*'.'.color'] = 'The '.$field->name.' must be a color string.';
                        }
                    }
                } else {
                    $rules[$field->name][] = 'color';
                    $messages[$field->name.'.color'] = 'The '.$field->name.' must be a color string.';
                }
            }

            if($validations->charcount->status){
                if($validations->charcount->type == "Between"){
                    if(isset($options->repeatable) && $options->repeatable) {
                        if($request->has($field->name)){
                            $repeatableField = $request->get($field->name);
                            foreach($repeatableField as $rf_key => $rf_value){
                                $rules[$field->name.'.*'][] = 'between:'.$validations->charcount->min.','.$validations->charcount->max;
                                $messages[$field->name.'.*'.'.between'] = 'The '.$field->name.' must be between '.$validations->charcount->min.' and '.$validations->charcount->max;

                                if($field->type != 'number'){
                                    $messages[$field->name.'.*'.'.between'] .= ' characters.';
                                }
                            }
                        }
                    } else {
                        $rules[$field->name][] = 'between:'.$validations->charcount->min.','.$validations->charcount->max;
                        $messages[$field->name.'.between'] = 'The '.$field->name.' must be between '.$validations->charcount->min.' and '.$validations->charcount->max;

                        if($field->type != 'number'){
                            $messages[$field->name.'.between'] .= ' characters.';
                        }
                    }
                } elseif($validations->charcount->type == "Min") {
                    if(isset($options->repeatable) && $options->repeatable) {
                        if($request->has($field->name)){
                            $repeatableField = $request->get($field->name);
                            foreach($repeatableField as $rf_key => $rf_value){
                                $rules[$field->name.'.*'][] = 'min:'.$validations->charcount->min;
                                $messages[$field->name.'.*'.'.min'] = 'The '.$field->name.' must be at least '.$validations->charcount->min;

                                if($field->type != 'number'){
                                    $messages[$field->name.'.*'.'.min'] .= ' characters.';
                                }
                            }
                        }
                    } else {
                        $rules[$field->name][] = 'min:'.$validations->charcount->min;
                        $messages[$field->name.'.min'] = 'The '.$field->name.' must be at least '.$validations->charcount->min;

                        if($field->type != 'number'){
                            $messages[$field->name.'.min'] .= ' characters.';
                        }
                    }
                } elseif($validations->charcount->type == "Max") {
                    if(isset($options->repeatable) && $options->repeatable) {
                        if($request->has($field->name)){
                            $repeatableField = $request->get($field->name);
                            foreach($repeatableField as $rf_key => $rf_value){
                                $rules[$field->name.'.*'][] = 'max:'.$validations->charcount->max;
                                $messages[$field->name.'.*'.'.max'] = 'The '.$field->name.' may not be greater than '.$validations->charcount->max;

                                if($field->type != 'number'){
                                    $messages[$field->name.'.*'.'.max'] .= ' characters.';
                                }
                            }
                        }
                    } else {
                        $rules[$field->name][] = 'max:'.$validations->charcount->max;
                        $messages[$field->name.'.max'] = 'The '.$field->name.' may not be greater than '.$validations->charcount->max;

                        if($field->type != 'number'){
                            $messages[$field->name.'.max'] .= ' characters.';
                        }
                    }
                }
            }
        }

        Validator::extend('color', function ($attribute, $value, $parameters, $validator) {
            $color_regex = "/(#(?:[0-9a-f]{2}){2,4}$|(#[0-9a-f]{3}$)|(rgb|hsl)a?\((-?\d*\.?\d*+%?[,\s]+){2,3}\s*[\d\.]+%?\)$|black$|silver$|gray$|whitesmoke$|maroon$|red$|purple$|fuchsia$|green$|lime$|olivedrab$|yellow$|navy$|blue$|teal$|aquamarine$|orange$|aliceblue$|antiquewhite$|aqua$|azure$|beige$|bisque$|blanchedalmond$|blueviolet$|brown$|burlywood$|cadetblue$|chartreuse$|chocolate$|coral$|cornflowerblue$|cornsilk$|crimson$|currentcolor$|darkblue$|darkcyan$|darkgoldenrod$|darkgray$|darkgreen$|darkgrey$|darkkhaki$|darkmagenta$|darkolivegreen$|darkorange$|darkorchid$|darkred$|darksalmon$|darkseagreen$|darkslateblue$|darkslategray$|darkslategrey$|darkturquoise$|darkviolet$|deeppink$|deepskyblue$|dimgray$|dimgrey$|dodgerblue$|firebrick$|floralwhite$|forestgreen$|gainsboro$|ghostwhite$|goldenrod$|gold$|greenyellow$|grey$|honeydew$|hotpink$|indianred$|indigo$|ivory$|khaki$|lavenderblush$|lavender$|lawngreen$|lemonchiffon$|lightblue$|lightcoral$|lightcyan$|lightgoldenrodyellow$|lightgray$|lightgreen$|lightgrey$|lightpink$|lightsalmon$|lightseagreen$|lightskyblue$|lightslategray$|lightslategrey$|lightsteelblue$|lightyellow$|limegreen$|linen$|mediumaquamarine$|mediumblue$|mediumorchid$|mediumpurple$|mediumseagreen$|mediumslateblue$|mediumspringgreen$|mediumturquoise$|mediumvioletred$|midnightblue$|mintcream$|mistyrose$|moccasin$|navajowhite$|oldlace$|olive$|orangered$|orchid$|palegoldenrod$|palegreen$|paleturquoise$|palevioletred$|papayawhip$|peachpuff$|peru$|pink$|plum$|powderblue$|rosybrown$|royalblue$|saddlebrown$|salmon$|sandybrown$|seagreen$|seashell$|sienna$|skyblue$|slateblue$|slategray$|slategrey$|snow$|springgreen$|steelblue$|tan$|thistle$|tomato$|transparent$|turquoise$|violet$|wheat$|white$|yellowgreen$|rebeccapurple$)/i";

            return preg_match($color_regex, $value);
        });

        $validator = Validator::make($request->all(), $rules, $messages);
        $validator->validate();

        $uniqueErrors = [];
        foreach ($collection->fields as $field) {
            $validations = json_decode($field->validations);
            if($validations->unique->status){
                if($request->has($field->name)){
                    $data = ContentMeta::where('content_id', '!=', $content->id)->where('collection_id', $collection->id)->where('field_name', $field->name)->where('value', $request->get($field->name))->count();

                    if($data !== 0){
                        $uniqueErrors['errors'][$field->name] = ['The '.$field->name.' has already been taken.'];

                        if($validations->unique->message != null){
                            $uniqueErrors['errors'][$field->name] = [$validations->unique->message];
                        }
                    }
                }
            }
        }
        if(count($uniqueErrors) !== 0){
            return response($uniqueErrors, 422);
        }

        $content->update([
            'locale' => $request->get('locale'),
            'published_at' => $request->has('draft') && $request->get('draft') == 1 ? null : now(),
        ]);

        $content_data = $request->all();

        foreach ($content_data as $key => $value) {
            $val = $value;

            foreach ($collection->fields as $field) {
                if($field->name == $key){
                    $field_type = $field->type;
                    $field_options = json_decode($field->options);
                }
            }

            if(!empty($value) && $key !== 'locale' && $key !== 'draft'){
                if($field_type == 'password'){
                    $password = ContentMeta::where('content_id', $content->id)->where('field_name', $key)->first();

                    if(!$password){
                        $val = Hash::make($value);
                    } else {
                        if(empty($value)){
                            $val = $password->value;
                        } else {
                            $val = Hash::make($value);
                        }
                    }
                }
                if ($field_type == 'enumeration') {
                    if (isset($field_options->multiple) && $field_options->multiple && is_array($value)) {
                        $str = '';
                        foreach ($value as $vv) {
                            $str .= $vv.',';
                        }
                        $val = rtrim($str, ',');
                    } else {
                        $val = $value;
                    }
                }
                if($field_type == 'media'){
                    $str = '';
                    foreach ($value as $file) {
                        $str .= $file.',';
                    }
                    $val = rtrim($str, ',');
                }
                if($field_type == 'relation'){
                    $rl = explode(',', $value);
                    $str = '';
                    foreach ($rl as $relation) {
                        $str .= $relation.',';
                    }
                    $val = rtrim($str, ',');
                }
                if($field_type == 'json'){
                    $val = json_encode($value);
                }
            }

            $content_meta = ContentMeta::where('content_id', $content->id)->where('field_name', $key)->first();

            if(isset($field_options->repeatable) && $field_options->repeatable){
                foreach($value as $rf_item){
                    if(!empty($rf_item)){
                        $content_meta = ContentMeta::create([
                            'project_id' => $project->id,
                            'collection_id' => $collection->id,
                            'content_id' => $content->id,
                            'field_name' => $key,
                            'value' => $rf_item
                        ]);
                    }
                }
            } else {
                $content_meta = ContentMeta::where('content_id', $content->id)->where('field_name', $key)->first();

                if($content_meta){
                    $content_meta->update([
                        'value' => $val
                    ]);
                } else {
                    if(!empty($value)){
                        $content_meta = ContentMeta::create([
                            'project_id' => $content->project_id,
                            'collection_id' => $content->collection_id,
                            'content_id' => $content->id,
                            'field_name' => $key,
                            'value' => $val
                        ]);
                    }
                }
            }
        }

        \App\Events\ContentUpdated::dispatch([
            'source' => 'API',
            'content' => $content
        ]);

        return $this->updated(new ContentResource($content), 'Content updated successfully');
    }

    /**
     * Delete a content
     *
     * @param string $uuid
     * @param string $slug
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function deleteContentByUuid($uuid, $slug, $slug_id){
        if ($response = $this->authorizeProjectAbility('delete', $uuid)) {
            return $response;
        }

        $auth = auth('sanctum')->user();
        $project = Project::find($auth->id);
        if(!$project) {
            return $this->notFound('Project not found');
        }

        $collection = Collection::with(['fields'])->where('project_id', $project->id)->where('slug', $slug)->first();
        if(!$collection) {
            return $this->notFound('Collection not found');
        }

        $content = Content::where('project_id', $project->id)
            ->where('collection_id', $collection->id)
            ->find($slug_id);
        if(!$content) {
            return $this->notFound('Record not found');
        }

        $content->meta()->delete();

        if($content->delete()){
            \App\Events\ContentTrashed::dispatch([
                'source' => 'API',
                'content' => $content
            ]);

            return $this->deleted('Record deleted');
        } else {
            return $this->notFound('Failed to delete record');
        }
    }

    /**
     * Get all content by explicit project identifier (UUID or slug)
     * Project is resolved by ValidateProjectAccess middleware and set on request attributes
     *
     * @param string $projectIdentifier Project UUID or slug
     * @param string $slug Collection slug
     * @param \Illuminate\Http\Request $request
     * @return \App\Http\Resources\ContentResource
     */
    public function getContentList($projectIdentifier, $slug, Request $request){
        $project = $request->attributes->get('resolved_project');
        
        if (!$project) {
            return $this->notFound('Project not resolved');
        }
        
        return $this->getContentListByUuid($project->uuid, $slug, $request);
    }

    /**
     * Get content by ID using explicit project identifier (UUID or slug)
     * Project is resolved by ValidateProjectAccess middleware and set on request attributes
     *
     * @param string $projectIdentifier Project UUID or slug
     * @param string $slug Collection slug
     * @param int $slug_id Content ID
     * @param \Illuminate\Http\Request $request
     * @return \App\Http\Resources\ContentResource
     */
    public function getProjectContentByID($project_identifier, $slug, $slug_id, Request $request){
        $project = $request->attributes->get('resolved_project');
        
        if (!$project) {
            return $this->notFound('Project not resolved');
        }
        
        return $this->getContentByUuid($project->uuid, $slug, $slug_id, $request);
    }

    /**
     * Get content by related content using explicit project identifier
     * 
     * Query content from a related collection that is related to a specific content in a source collection.
     * For example: get all posts in a specific category.
     * 
     * @param string $project_identifier Project UUID or slug
     * @param string $slug Source collection slug (e.g., 'categories')
     * @param int $slug_id Source content ID (e.g., category ID)
     * @param string $related_slug Related collection slug (e.g., 'posts')
     * @param \Illuminate\Http\Request $request
     * @return \App\Http\Resources\ContentResource
     */
    public function getProjectContentByRelation($project_identifier, $slug, $slug_id, $related_slug, Request $request){
        $project = $request->attributes->get('resolved_project');
        
        if (!$project) {
            return $this->notFound('Project not resolved');
        }
        
        return $this->getContentByRelationByUuid($project->uuid, $slug, $slug_id, $related_slug, $request);
    }

    /**
     * Create content using explicit project identifier
     * 
     * @param string $project_identifier Project UUID or slug
     * @param string $slug Collection slug
     * @param \Illuminate\Http\Request $request
     * @return \App\Http\Resources\ContentResource
     */
    public function createContent($project_identifier, $slug, Request $request){
        $project = $request->attributes->get('resolved_project');
        
        if (!$project) {
            return $this->notFound('Project not resolved');
        }
        
        return $this->createContentByUuid($project->uuid, $slug, $request);
    }

    /**
     * Update content using explicit project identifier
     * 
     * @param string $project_identifier Project UUID or slug
     * @param string $slug Collection slug
     * @param int $slug_id Content ID
     * @param \Illuminate\Http\Request $request
     * @return \App\Http\Resources\ContentResource
     */
    public function updateContent($project_identifier, $slug, $slug_id, Request $request){
        $project = $request->attributes->get('resolved_project');
        
        if (!$project) {
            return $this->notFound('Project not resolved');
        }
        
        return $this->updateContentByUuid($project->uuid, $slug, $slug_id, $request);
    }

    /**
     * Delete content using explicit project identifier
     * 
     * @param string $project_identifier Project UUID or slug
     * @param string $slug Collection slug
     * @param int $slug_id Content ID
     * @return \Illuminate\Http\Response
     */
    public function deleteContent($project_identifier, $slug, $slug_id, Request $request){
        $project = $request->attributes->get('resolved_project');
        
        if (!$project) {
            return $this->notFound('Project not resolved');
        }
        
        return $this->deleteContentByUuid($project->uuid, $slug, $slug_id);
    }

    /**
     * Get content by related content
     * 
     * Query content from a related collection that is related to a specific content in a source collection.
     * For example: get all posts in a specific category.
     * 
     * @param string $uuid Project UUID
     * @param string $slug Source collection slug (e.g., 'categories')
     * @param int $id Source content ID (e.g., category ID)
     * @param string $relatedSlug Related collection slug (e.g., 'posts')
     * @param \Illuminate\Http\Request $request
     * @return \App\Http\Resources\ContentResource
     */
    public function getContentByRelationByUuid($uuid, $slug, $slug_id, $relatedSlug, Request $request){
        $project = Project::where('uuid', $uuid)->first();

        if(!$project){
            return $this->notFound('Project not found');
        }
        if ($response = $this->authorizeProjectRead($project)) {
            return $response;
        }

        $sourceCollection = Collection::where('project_id', $project->id)->where('slug', $slug)->first();
        if(!$sourceCollection) {
            return $this->notFound('Source collection not found');
        }

        $sourceContent = Content::where('project_id', $project->id)
            ->where('collection_id', $sourceCollection->id)
            ->where('id', $slug_id)
            ->first();
        if(!$sourceContent) {
            return $this->notFound('Source content not found');
        }

        $relatedCollection = Collection::where('project_id', $project->id)->where('slug', $relatedSlug)->first();
        if(!$relatedCollection) {
            return $this->notFound('Related collection not found');
        }

        $relationField = CollectionField::where('project_id', $project->id)
            ->where('collection_id', $relatedCollection->id)
            ->where('type', 'relation')
            ->whereRaw('JSON_CONTAINS(options, ?)', ['{"relation":{"collection":'.$sourceCollection->id.'}}'])
            ->first();

        if(!$relationField) {
            return $this->notFound('No relation field found between source and related collections');
        }

        $content = Content::with(['meta', 'collection'])
            ->where('project_id', $project->id)
            ->where('collection_id', $relatedCollection->id);

        $metaThroughRelation = ContentMeta::where('project_id', $project->id)
            ->where('collection_id', $relatedCollection->id)
            ->where('field_name', $relationField->name)
            ->whereRaw('FIND_IN_SET(?, cast(value as char)) > 0', [$sourceContent->id]);

        $metaThroughRelation = $metaThroughRelation->get(['content_id']);

        $content = $content->whereIn('id', $metaThroughRelation);

        if($request->has('sort')){
            $sortM = explode(',', $request->get('sort'));

            foreach ($sortM as $s) {
                $sort = explode(':', $s);
                if(count($sort) <= 1 || count($sort) > 2) {
                    return $this->validationError('Incorrect sort statement');
                }

                if($sort[0] == 'id' || $sort[0] == 'locale' || $sort[0] == 'created_at' || $sort[0] == 'updated_at' || $sort[0] == 'published_at'){
                    $content = $content->orderBy($sort[0], $sort[1]);
                } else {
                    $content = $content->orderBy(
                        ContentMeta::select('value')
                            ->whereColumn('content_meta.content_id', 'content.id')
                            ->where('field_name', $sort[0])
                            ->latest()
                            ->take(1),
                        $sort[1]
                    );
                }
            }
        }

        if($request->has('state')){
            if($request->get('state') == 'only_draft'){
                $content = $content->whereNull('published_at');
            }
        } else {
            $content = $content->whereNotNull('published_at');
        }

        if($request->has('offset') && !$request->has('limit')){
            return $this->validationError('Incorrect offset statement. Offset must be used with limit.');
        }

        if($request->has('offset')){
            $content = $content->offset($request->get('offset'));
        }
        if($request->has('limit')){
            $content = $content->limit($request->get('limit'));
        }

        if($request->has('count')){
            return $this->success($content->count(), 'Success');
        } else {
            $selectFields = ['id', 'project_id', 'collection_id', 'locale'];

            if($request->has('timestamps')){
                $selectFields[] = 'created_at';
                $selectFields[] = 'updated_at';
                $selectFields[] = 'published_at';
            }
            $content = $content->select($selectFields);

            if($request->has('first')){
                $content = $content->first();
                if(!$content) return $this->notFound('Not found');

                return $this->success(new ContentResource($content), 'Success');
            } else {
                $content = $content->get();
                return $this->success(ContentResource::collection($content), 'Success');
            }
        }
    }
}
