<?php

namespace App\Http\Controllers\API;

use App\Aine\ContentSerializer;
use App\Aine\PublicCache;
use App\Events\ContentCreated;
use App\Events\ContentTrashed;
use App\Events\ContentUpdated;
use App\Models\Content;
use App\Models\Project;
use App\Models\Collection;
use App\Models\ContentMeta;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Models\CollectionField;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Hash;
use App\Http\Resources\ContentResource;
use App\Http\Resources\ProjectResource;
use Illuminate\Support\Facades\Validator;
use App\Http\Controllers\API\Concerns\AuthorizesProjectApi;
use App\Http\Controllers\API\Concerns\HandlesBrowserCache;

class ContentController extends Controller {

    use AuthorizesProjectApi, HandlesBrowserCache;

    /**
     * Public API response cache lifetime in seconds.
     * Public GET content is read-only data; a 10-minute TTL is safe and
     * makes repeated page loads (SPA navigation) hit the cache instead of
     * querying the database every time.
     */
    const PUBLIC_CACHE_TTL = 600;

    /**
     * Portal content skeleton limits. These mirror the values the SPA
     * frontend used to apply locally (see config.js) so the /portal endpoint
     * keeps the exact same display as before the consolidation.
     */
    const PORTAL_CATEGORY_LIMIT = 8;
    const PORTAL_CATEGORY_RELATED_LIMIT = 50;
    const PORTAL_FEATURED_LIMIT = 8;
    const PORTAL_RECOMMENDED_LIMIT = 8;
    const PORTAL_SLIDER_LIMIT = 5;
    const PORTAL_LATEST_LIMIT = 10;

    /**
     * Pagination guards for the public API (DoS protection).
     *
     * `limit` is clamped to MAX_PAGE_LIMIT so an absurd value such as
     * `limit=1000000` can never force the API to materialise the whole
     * collection; `offset` beyond MAX_PAGE_OFFSET is rejected outright
     * (a huge offset is never legitimate).
     */
    const MAX_PAGE_LIMIT = 100;
    const MAX_PAGE_OFFSET = 10000;

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

        // Authorize BEFORE the cache lookup: private projects must never
        // serve cached data to unauthenticated callers.
        if ($response = $this->authorizeProjectRead($project)) {
            return $response;
        }

        $cacheKey = $this->publicCacheKey($project, 'list', $slug, $request);

        return $this->rememberPublicJson($cacheKey, function () use ($uuid, $slug, $request) {
            return $this->resolveContentListByUuid($uuid, $slug, $request);
        }, $project->public_api);
    }

    private function resolveContentListByUuid($uuid, $slug, Request $request){
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

        $content =  Content::query()->with(['meta', 'collection.fields'])
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
                $bind = [];

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
                                        $metaSql .= "(m".$num.".field_name= ? AND (m".$num.".value = ? OR m".$num.".value LIKE ? OR m".$num.".value LIKE ? OR m".$num.".value LIKE ?))";
                                        $bind[] = $value;
                                        $bind[] = $value;
                                        $bind[] = $value.',%';
                                        $bind[] = '%,'.$value;
                                        $bind[] = '%,'.$value.',%';
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
                                    $meta = $meta->where('field_name', $key)->where($this->relationValueMatcher($value));
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

                    $metaThroughRelation = ContentMeta::where('project_id', $project->id)->where('collection_id', $collection->id)->where('field_name', $key)->where($this->relationValueMatcher($relationMeta->content_id));

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

        if($paginationError = $this->validatePagination($request)){
            return $paginationError;
        }

        if($request->has('offset')){
            $content = $content->offset(min((int) $request->get('offset'), self::MAX_PAGE_OFFSET));
        }
        if($request->has('limit')){
            $content = $content->limit(min((int) $request->get('limit'), self::MAX_PAGE_LIMIT));
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
            // Eager-load relations and batch-preload media/relation rows so
            // serialisation stays O(1) instead of N+1 per item.
            $content = $content->with(['meta', 'collection.fields'])->select($selectFields);

            if($request->has('first')){
                $content = $content->first();
                if(!$content) return $this->notFound('Not found');

                ContentSerializer::preload($content);

                return $this->success(new ContentResource($content), 'Success');
            } else {
                $content =  $content->get();
                ContentSerializer::preload($content);

                return $this->success(ContentResource::collection($content), 'Success');
            }
        }
    }

    /**
     * Get portal content for a project (fixed skeleton).
     *
     * Bundles several list queries into a single request: categories (each
     * with its top items + tags), flagged/featured/latest items of any
     * collection, and pages. The portal pages (home, featured, ...) fetch
     * their building blocks from this endpoint.
     *
     * The frontend decides which blocks to render per project (see the
     * PROJECTS config in the SPA), so this endpoint always returns the full
     * skeleton regardless of the project.
     *
     * @param string $project_identifier
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getPortalContent($project_identifier, Request $request)
    {
        $project = $request->attributes->get('resolved_project');

        if (!$project) {
            return $this->notFound('Project not resolved');
        }

        if ($response = $this->authorizeProjectRead($project)) {
            return $response;
        }

        $collectionSlug = $request->get('collection', 'articles');
        $cacheKey = $this->publicCacheKey($project, 'portal', $collectionSlug, $request);

        return $this->rememberPublicJson($cacheKey, function () use ($project, $collectionSlug, $request) {
            return $this->resolvePortalContent($project, $collectionSlug, $request);
        }, $project->public_api);
    }

    /**
     * Assemble the portal content skeleton (one cache entry covers the
     * whole tree).
     *
     * @param \App\Models\Project $project
     * @param string $collectionSlug
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    private function resolvePortalContent($project, $collectionSlug, Request $request)
    {
        $uuid = $project->uuid;

        // 1. Categories (fixed skeleton).
        $categories = $this->portalContentList($uuid, 'categories', $request, []);

        // 2. Per-category items + tags (the same logic the SPA used to run
        //    one request per category, now executed server-side).
        $sections = [];
        foreach ($categories as $category) {
            $related = $this->portalRelated($project, $category['id'] ?? null, $collectionSlug, $request, [
                'limit' => self::PORTAL_CATEGORY_RELATED_LIMIT,
                'sort' => 'published_at:desc',
                'timestamps' => true,
                'state' => 'only_published',
            ]);

            $tagMap = [];
            foreach ($related as $item) {
                foreach ($item['tags'] ?? [] as $tag) {
                    $tagMap[$tag['id']] = $tag;
                }
            }

            $sections[] = [
                'category' => $category,
                'items' => array_slice($related, 0, self::PORTAL_CATEGORY_LIMIT),
                'tags' => array_values($tagMap),
            ];
        }

        // 3. Featured / recommended / slider / latest from the content collection.
        $featured = $this->portalContentList($uuid, $collectionSlug, $request, [
            'where' => ['featured' => 1],
            'limit' => self::PORTAL_FEATURED_LIMIT,
            'sort' => 'published_at:desc',
            'timestamps' => true,
        ]);
        $recommended = $this->portalContentList($uuid, $collectionSlug, $request, [
            'where' => ['recommended' => 1],
            'limit' => self::PORTAL_RECOMMENDED_LIMIT,
            'sort' => 'published_at:desc',
            'timestamps' => true,
        ]);
        $slider = $this->portalContentList($uuid, $collectionSlug, $request, [
            'where' => ['slider' => 1],
            'limit' => self::PORTAL_SLIDER_LIMIT,
            'sort' => 'published_at:desc',
            'timestamps' => true,
        ]);
        $latest = $this->portalContentList($uuid, $collectionSlug, $request, [
            'limit' => self::PORTAL_LATEST_LIMIT,
            'sort' => 'published_at:desc',
            'timestamps' => true,
        ]);

        // 4. Pages (fixed skeleton).
        $pages = $this->portalContentList($uuid, 'pages', $request, [
            'timestamps' => true,
        ]);

        return response()->json([
            'success' => true,
            'code' => 200,
            'message' => 'Success',
            'data' => [
                'categories' => $sections,
                'featured' => $featured,
                'recommended' => $recommended,
                'slider' => $slider,
                'latest' => $latest,
                'pages' => $pages,
            ],
        ]);
    }

    /**
     * Resolve a content list for one block of the portal skeleton.
     *
     * @param string $uuid
     * @param string $slug
     * @param \Illuminate\Http\Request $request
     * @param array $overrides
     * @return array
     */
    private function portalContentList($uuid, $slug, Request $request, array $overrides): array
    {
        $response = $this->resolveContentListByUuid($uuid, $slug, $this->portalSubRequest($request, $overrides));

        return $response instanceof JsonResponse ? ($response->getData(true)['data'] ?? []) : [];
    }

    /**
     * Resolve related content for one category of the portal skeleton.
     *
     * @param \App\Models\Project $project
     * @param mixed $categoryId
     * @param string $collectionSlug
     * @param \Illuminate\Http\Request $request
     * @param array $overrides
     * @return array
     */
    private function portalRelated($project, $categoryId, $collectionSlug, Request $request, array $overrides): array
    {
        if (!$categoryId) {
            return [];
        }

        $response = $this->resolveProjectContentByRelation(
            $project->identifier,
            'categories',
            $categoryId,
            $collectionSlug,
            $this->portalSubRequest($request, $overrides)
        );

        return $response instanceof JsonResponse ? ($response->getData(true)['data'] ?? []) : [];
    }

    /**
     * Build a sub-request for one portal skeleton query, keeping the original
     * query params (e.g. locale) and merging block-specific overrides.
     *
     * @param \Illuminate\Http\Request $request
     * @param array $overrides
     * @return \Illuminate\Http\Request
     */
    private function portalSubRequest(Request $request, array $overrides): Request
    {
        $params = $request->query();

        foreach ($overrides as $key => $value) {
            if ($key === 'where' && isset($params['where']) && is_array($params['where'])) {
                $params['where'] = array_merge($params['where'], (array) $value);
            } else {
                $params[$key] = $value;
            }
        }

        $sub = Request::create('/', 'GET', $params);
        $sub->attributes->set('resolved_project', $request->attributes->get('resolved_project'));

        return $sub;
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

        // Authorize BEFORE the cache lookup: private projects must never
        // serve cached data to unauthenticated callers.
        if ($response = $this->authorizeProjectRead($project)) {
            return $response;
        }

        $cacheKey = $this->publicCacheKey($project, 'single', $slug.'/'.$slug_id, $request);

        return $this->rememberPublicJson($cacheKey, function () use ($uuid, $slug, $slug_id, $request) {
            return $this->resolveContentByUuid($uuid, $slug, $slug_id, $request);
        }, $project->public_api);
    }

    private function resolveContentByUuid($uuid, $slug, $slug_id, Request $request){
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

        $content =  Content::query()->with(['meta', 'collection.fields'])
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

        ContentSerializer::preload($content);

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

        // Resolve the project by the UUID from the URL — NOT by the user id
        // (Project::find($auth->id) would target a wrong project or 404).
        $project = Project::where('uuid', $uuid)->first();
        if(!$project) {
            return $this->notFound('Project not found');
        }

        $collection = Collection::query()->with(['fields'])->where('project_id', $project->id)->where('slug', $slug)->first();
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

        ContentCreated::dispatch([
            'source' => 'API',
            'content' => $content
        ]);

        ContentSerializer::preload($content);

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

        // Resolve the project by the UUID from the URL — NOT by the user id.
        $project = Project::where('uuid', $uuid)->first();
        if(!$project) {
            return $this->notFound('Project not found');
        }

        $collection = Collection::query()->with(['fields'])->where('project_id', $project->id)->where('slug', $slug)->first();
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

        ContentUpdated::dispatch([
            'source' => 'API',
            'content' => $content
        ]);

        ContentSerializer::preload($content);

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

        // Resolve the project by the UUID from the URL — NOT by the user id.
        $project = Project::where('uuid', $uuid)->first();
        if(!$project) {
            return $this->notFound('Project not found');
        }

        $collection = Collection::query()->with(['fields'])->where('project_id', $project->id)->where('slug', $slug)->first();
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
            ContentTrashed::dispatch([
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
     * Search content in a collection using an explicit project identifier (UUID or slug)
     * Project is resolved by ValidateProjectAccess middleware and set on request attributes
     *
     * GET /api/project/{project_identifier}/{slug}/search?query=...
     *
     * @param string $project_identifier Project UUID or slug
     * @param string $slug Collection slug
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function searchContent($project_identifier, $slug, Request $request){
        $project = $request->attributes->get('resolved_project');

        if (!$project) {
            return $this->notFound('Project not resolved');
        }

        return $this->searchContentByUuid($project->uuid, $slug, $request);
    }

    /**
     * Search content in a collection by project UUID
     *
     * GET /api/{uuid}/{slug}/search?query=...
     *
     * @param string $uuid Project UUID
     * @param string $slug Collection slug
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function searchContentByUuid($uuid, $slug, Request $request){
        $project = Project::where('uuid', $uuid)->first();

        if(!$project){
            return $this->notFound('Project not found');
        }
        if ($response = $this->authorizeProjectRead($project)) {
            return $response;
        }

        $query = trim((string) $request->get('query', ''));
        $queryLength = mb_strlen($query);

        if($queryLength < 2){
            return $this->validationError('Search query must be at least 2 characters.');
        }
        if($queryLength > 100){
            return $this->validationError('Search query cannot exceed 100 characters.');
        }

        $collection = Collection::where('project_id', $project->id)->where('slug', $slug)->first();
        if(!$collection){
            return $this->notFound('Collection not found');
        }

        $limit = $request->has('limit') ? (int) $request->get('limit') : 20;
        $offset = $request->has('offset') ? (int) $request->get('offset') : 0;
        if($limit < 1 || $limit > 100){
            $limit = 20;
        }
        if($offset < 0){
            $offset = 0;
        }

        //Find content ids whose meta values contain the query
        $contentIds = ContentMeta::query()
            ->where('project_id', $project->id)
            ->where('collection_id', $collection->id)
            ->where('value', 'LIKE', '%'.$query.'%')
            ->pluck('content_id')
            ->unique();

        //Only published content is exposed unless explicitly asking for drafts
        if($request->get('state') !== 'only_draft'){
            $contentIds = Content::whereIn('id', $contentIds)
                ->whereNotNull('published_at')
                ->pluck('id');
        } else {
            $contentIds = Content::whereIn('id', $contentIds)
                ->whereNull('published_at')
                ->pluck('id');
        }

        $total = $contentIds->count();
        $pageIds = $contentIds->slice($offset, $limit)->values();

        $responseData = [];
        if($pageIds->isNotEmpty()){
            $contents = Content::query()
                ->with(['meta', 'collection.fields'])
                ->select(['id', 'project_id', 'collection_id', 'locale'])
                ->whereIn('id', $pageIds)
                ->get();

            ContentSerializer::preload($contents);

            $responseData = json_decode(ContentResource::collection($contents)->toJson(), true);
        }

        return response()->json([
            'success' => true,
            'code' => 200,
            'message' => 'Success',
            'data' => $responseData,
            'total' => $total,
            'limit' => $limit,
            'offset' => $offset,
        ], 200);
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
     * For example: get all articles in a specific category.
     * 
     * @param string $project_identifier Project UUID or slug
     * @param string $slug Source collection slug (e.g., 'categories')
     * @param int $slug_id Source content ID (e.g., category ID)
     * @param string $related_slug Related collection slug (e.g., 'articles')
     * @param \Illuminate\Http\Request $request
     * @return \App\Http\Resources\ContentResource
     */
    public function getProjectContentByRelation($project_identifier, $slug, $slug_id, $related_slug, Request $request){
        $project = $request->attributes->get('resolved_project');
        
        if (!$project) {
            return $this->notFound('Project not resolved');
        }

        $cacheKey = $this->publicCacheKey($project, 'related', $slug.'/'.$slug_id.'/'.$related_slug, $request);

        return $this->rememberPublicJson($cacheKey, function () use ($project_identifier, $slug, $slug_id, $related_slug, $request) {
            return $this->resolveProjectContentByRelation($project_identifier, $slug, $slug_id, $related_slug, $request);
        }, $project->public_api);
    }

    private function resolveProjectContentByRelation($project_identifier, $slug, $slug_id, $related_slug, Request $request){
        $project = $request->attributes->get('resolved_project');
        
        if (!$project) {
            return $this->notFound('Project not resolved');
        }
        
        $sourceCollection = Collection::where('project_id', $project->id)->where('slug', $slug)->first();
        if(!$sourceCollection) {
            return $this->notFound('Source collection "'.$slug.'" not found in project');
        }

        $relatedCollection = Collection::where('project_id', $project->id)->where('slug', $related_slug)->first();
        if(!$relatedCollection) {
            return $this->notFound('Related collection "'.$related_slug.'" not found in project');
        }

        $relationFields = CollectionField::where('project_id', $project->id)
            ->where('collection_id', $relatedCollection->id)
            ->where('type', 'relation')
            ->get();

        $relationField = null;
        $sourceCollectionId = $sourceCollection->id;

        foreach ($relationFields as $field) {
            $options = json_decode($field->options, true);
            if (isset($options['relation']['collection'])) {
                $targetCollectionId = $options['relation']['collection'];
                if ($targetCollectionId == $sourceCollectionId || (string)$targetCollectionId == (string)$sourceCollectionId) {
                    $relationField = $field;
                    break;
                }
            }
        }

        if(!$relationField) {
            $debugInfo = [];
            foreach ($relationFields as $field) {
                $debugInfo[] = [
                    'field_name' => $field->name,
                    'field_id' => $field->id,
                    'options' => $field->options,
                ];
            }
            return response()->json([
                'success' => false,
                'code' => 404,
                'message' => 'No relation field found from collection "'.$related_slug.'" to collection "'.$slug.'". Please check if there is a relation field in the "'.$related_slug.'" collection that points to the "'.$slug.'" collection.',
                'debug' => [
                    'source_collection_name' => $slug,
                    'source_collection_id' => $sourceCollectionId,
                    'related_collection_name' => $related_slug,
                    'related_collection_id' => $relatedCollection->id,
                    'relation_fields_found' => $debugInfo,
                ],
            ], 404);
        }

        $content = Content::query()->with(['meta', 'collection.fields'])
            ->where('project_id', $project->id)
            ->where('collection_id', $relatedCollection->id);

        $metaThroughRelation = ContentMeta::where('project_id', $project->id)
            ->where('collection_id', $relatedCollection->id)
            ->where('field_name', $relationField->name)
            ->where($this->relationValueMatcher($slug_id));

        $metaThroughRelation = $metaThroughRelation->get(['content_id']);

        $content = $content->whereIn('id', $metaThroughRelation);

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
            if(!$multiDim) {
                $where = [$where];
            }
            foreach($where as $where_item) {
                if(array_key_exists('or', $where_item)) {
                    $content->where(function($query) use ($where_item) {
                        foreach($where_item['or'] as $or_item) {
                            $key = key($or_item);
                            $value = $or_item[$key];
                            if(in_array($key, ['id', 'locale', 'created_at', 'updated_at', 'published_at'])){
                                $query->orWhere($key, $value);
                            } else {
                                $query->orWhereHas('meta', function ($query) use ($key, $value) {
                                    $query->where('field_name', $key)->where('value', $value);
                                });
                            }
                        }
                    });
                } else {
                    $key = key($where_item);
                    $value = $where_item[$key];
                    if(in_array($key, ['id', 'locale', 'created_at', 'updated_at', 'published_at'])){
                        $content->where($key, $value);
                    } else {
                        $content->whereHas('meta', function ($query) use ($key, $value) {
                            $query->where('field_name', $key)->where('value', $value);
                        });
                    }
                }
            }
        }

        if($request->has('sort')){
            $sort = $request->get('sort');
            if(is_string($sort)) {
                $sort = [$sort];
            }
            foreach($sort as $sort_item){
                $sort_field = explode(':', $sort_item)[0];
                $sort_direction = explode(':', $sort_item)[1] ?? 'asc';
                if($sort_field === 'created_at' || $sort_field === 'updated_at' || $sort_field === 'published_at') {
                    $content->orderBy($sort_field, $sort_direction);
                } else {
                    $content->orderByMeta($sort_field, $sort_direction);
                }
            }
        } else {
            $content->orderBy('created_at', 'desc');
        }

        if($paginationError = $this->validatePagination($request)){
            return $paginationError;
        }

        if($request->has('offset')){
            $content->skip(min((int) $request->get('offset'), self::MAX_PAGE_OFFSET));
        }

        if($request->has('limit')){
            $content->limit(min((int) $request->get('limit'), self::MAX_PAGE_LIMIT));
        }

        if($request->has('state')){
            switch($request->get('state')) {
                case 'only_draft':
                    $content->whereNull('published_at');
                    break;
                case 'only_published':
                    $content->whereNotNull('published_at');
                    break;
                case 'all':
                    break;
            }
        }

        if($request->has('timestamps')){
            $selectFields = ['id', 'project_id', 'collection_id', 'locale', 'created_at', 'updated_at', 'published_at'];
        } else {
            $selectFields = ['id', 'project_id', 'collection_id', 'locale'];
        }

        $content = $content->select($selectFields)->get();

        ContentSerializer::preload($content);

        if($request->has('first') && $request->get('first')){
            $content = $content->first();
            if(!$content){
                return $this->notFound('Not found');
            }
            return $this->success(new ContentResource($content), 'Success');
        }

        if($request->has('count') && $request->get('count')){
            return $this->success($content->count(), 'Success');
        }

        return $this->success(ContentResource::collection($content), 'Success');
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
     * For example: get all articles in a specific category.
     * 
     * @param string $uuid Project UUID
     * @param string $slug Source collection slug (e.g., 'categories')
     * @param int $id Source content ID (e.g., category ID)
     * @param string $relatedSlug Related collection slug (e.g., 'articles')
     * @param \Illuminate\Http\Request $request
     * @return \App\Http\Resources\ContentResource
     */
    public function getContentByRelationByUuid($uuid, $slug, $slug_id, $relatedSlug, Request $request){
        $project = Project::where('uuid', $uuid)->first();

        if(!$project){
            return $this->notFound('Project not found');
        }

        // Authorize BEFORE the cache lookup: private projects must never
        // serve cached data to unauthenticated callers.
        if ($response = $this->authorizeProjectRead($project)) {
            return $response;
        }

        $cacheKey = $this->publicCacheKey($project, 'related', $slug.'/'.$slug_id.'/'.$relatedSlug, $request);

        return $this->rememberPublicJson($cacheKey, function () use ($uuid, $slug, $slug_id, $relatedSlug, $request) {
            return $this->resolveContentByRelationByUuid($uuid, $slug, $slug_id, $relatedSlug, $request);
        }, $project->public_api);
    }

    private function resolveContentByRelationByUuid($uuid, $slug, $slug_id, $relatedSlug, Request $request){
        $project = Project::where('uuid', $uuid)->first();

        if(!$project){
            return $this->notFound('Project not found');
        }
        if ($response = $this->authorizeProjectRead($project)) {
            return $response;
        }

        $sourceCollection = Collection::where('project_id', $project->id)->where('slug', $slug)->first();
        if(!$sourceCollection) {
            return $this->notFound('Source collection "'.$slug.'" not found in project');
        }

        $relatedCollection = Collection::where('project_id', $project->id)->where('slug', $relatedSlug)->first();
        if(!$relatedCollection) {
            return $this->notFound('Related collection "'.$relatedSlug.'" not found in project');
        }

        $relationFields = CollectionField::where('project_id', $project->id)
            ->where('collection_id', $relatedCollection->id)
            ->where('type', 'relation')
            ->get();

        $relationField = null;
        $sourceCollectionId = $sourceCollection->id;

        foreach ($relationFields as $field) {
            $options = json_decode($field->options, true);
            if (isset($options['relation']['collection'])) {
                $targetCollectionId = $options['relation']['collection'];
                if ($targetCollectionId == $sourceCollectionId || (string)$targetCollectionId == (string)$sourceCollectionId) {
                    $relationField = $field;
                    break;
                }
            }
        }

        if(!$relationField) {
            return $this->notFound('No relation field found from collection "'.$relatedSlug.'" to collection "'.$slug.'". Please check if there is a relation field in the "'.$relatedSlug.'" collection that points to the "'.$slug.'" collection.');
        }

        $content = Content::query()->with(['meta', 'collection.fields'])
            ->where('project_id', $project->id)
            ->where('collection_id', $relatedCollection->id);

        $metaThroughRelation = ContentMeta::where('project_id', $project->id)
            ->where('collection_id', $relatedCollection->id)
            ->where('field_name', $relationField->name)
            ->where($this->relationValueMatcher($slug_id));

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

        if($paginationError = $this->validatePagination($request)){
            return $paginationError;
        }

        if($request->has('offset')){
            $content = $content->offset(min((int) $request->get('offset'), self::MAX_PAGE_OFFSET));
        }
        if($request->has('limit')){
            $content = $content->limit(min((int) $request->get('limit'), self::MAX_PAGE_LIMIT));
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

                ContentSerializer::preload($content);

                return $this->success(new ContentResource($content), 'Success');
            } else {
                $content = $content->get();
                ContentSerializer::preload($content);

                return $this->success(ContentResource::collection($content), 'Success');
            }
        }
    }

    /**
     * Build a query that matches a single id inside a comma-separated list
     * stored in content_meta.value (e.g. "5" or "9,12").
     *
     * Portable replacement for the MySQL-only FIND_IN_SET() so the same API
     * also works on SQLite.
     *
     * @param int|string $id
     * @return \Closure
     */
    private function relationValueMatcher($id): \Closure
    {
        return function ($query) use ($id) {
            $query->where('value', (string) $id)
                ->orWhere('value', 'like', (string) $id.',%')
                ->orWhere('value', 'like', '%,'.(string) $id)
                ->orWhere('value', 'like', '%,'.(string) $id.',%');
        };
    }

    /**
     * Run the given response builder and cache the successful JSON response.
     *
     * Only 2xx responses are cached (error responses are never stored).
     * The cached payload is the plain JSON body + status code, which is
     * trivially serializable on any cache driver (including `file`).
     *
     * For public projects reached anonymously the response also carries
     * browser-cache headers (ETag + `Cache-Control: no-cache`), so clients
     * revalidate cheaply with a 304 instead of re-downloading the body.
     * The ETag is derived from the cache key (project cache version +
     * endpoint + normalized query), therefore any content write rotates it
     * and clients instantly receive a fresh 200.
     *
     * @param string $cacheKey
     * @param callable $builder returns \Illuminate\Http\JsonResponse
     * @param bool $browserCacheable true when the project is public
     * @return \Illuminate\Http\JsonResponse
     */
    private function rememberPublicJson($cacheKey, callable $builder, bool $browserCacheable = false)
    {
        $etag = $this->publicApiEtag($cacheKey);

        // Browser caching is only offered to public projects reached without
        // authentication. Private or token-authenticated responses must never
        // be stored by a client-side cache.
        $browserCache = $browserCacheable && !auth('sanctum')->check();

        // 304 fast path: the client already holds the current representation.
        if ($browserCache && $this->ifNoneMatchMatches(request(), $etag)) {
            return $this->respondNotModified($etag);
        }

        $cached = $this->cacheGet($cacheKey);
        if ($cached !== null) {
            $response = response($cached['body'], $cached['status'])
                ->header('Content-Type', 'application/json');
            if ($browserCache) {
                $response->header('ETag', $etag);
                $response->header('Cache-Control', 'no-cache, must-revalidate');
            }
            return $response;
        }

        $response = $builder();

        if ($response instanceof JsonResponse && $response->getStatusCode() >= 200 && $response->getStatusCode() < 300) {
            $this->cachePut($cacheKey, [
                'status' => $response->getStatusCode(),
                'body' => $response->getContent(),
            ], self::PUBLIC_CACHE_TTL);

            if ($browserCache) {
                $response->header('ETag', $etag);
                $response->header('Cache-Control', 'no-cache, must-revalidate');
            }
        }

        return $response;
    }

    /**
     * Build a deterministic cache key for a public content endpoint.
     * Includes the per-project cache version so writes invalidate instantly.
     *
     * The cache payload embeds absolute media URLs built from the request
     * host (scheme + host + port), so the key must also be scoped to that
     * host. Without this, a cached response generated on one port/domain
     * would keep serving image URLs pointing at that old address after the
     * site is opened on a different port/domain — the images then 404.
     *
     * @param \App\Models\Project $project
     * @param string $endpoint 'list' | 'single' | 'related'
     * @param string $slugPath e.g. 'articles' or 'categories/5/articles'
     * @param \Illuminate\Http\Request $request
     * @return string
     */
    private function publicCacheKey($project, $endpoint, $slugPath, Request $request)
    {
        $query = $request->query();
        $this->ksortRecursive($query);

        return implode(':', [
            'public_content',
            $this->publicCacheVersion($project->id),
            $project->id,
            $request->getSchemeAndHttpHost(),
            $endpoint,
            $slugPath,
            md5(json_encode($query)),
        ]);
    }

    /**
     * Current cache version for a project. Bumping it invalidates every
     * cached public response of that project (old keys simply fall out of
     * cache lookups and expire via their own TTL).
     */
    private function publicCacheVersion($projectId): int
    {
        return PublicCache::version((int) $projectId);
    }

    /**
     * Cache read with graceful degradation.
     *
     * A cache failure (e.g. Redis not running, file cache not writable)
     * must never take the public API down — return the default and serve
     * the request without caching instead.
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    private function cacheGet($key, $default = null)
    {
        try {
            return Cache::get($key, $default);
        } catch (\Throwable $e) {
            return $default;
        }
    }

    /**
     * Cache write with graceful degradation. Failures are swallowed so a
     * broken cache driver can never break the request.
     *
     * @param string $key
     * @param mixed $value
     * @param int $ttl
     * @return void
     */
    private function cachePut($key, $value, $ttl)
    {
        try {
            Cache::put($key, $value, $ttl);
        } catch (\Throwable $e) {
            // Cache is an optimisation only — never fail the request over it.
        }
    }

    /**
     * Recursively sort an array by key so query params hash consistently
     * regardless of their arrival order.
     */
    private function ksortRecursive(&$array)
    {
        if (!is_array($array)) {
            return;
        }
        ksort($array);
        foreach ($array as &$value) {
            if (is_array($value)) {
                $this->ksortRecursive($value);
            }
        }
        unset($value);
    }

    /**
     * Validate the `limit` / `offset` pagination query parameters.
     *
     * Returns a JSON error response when a value is invalid (non-numeric,
     * negative, or offset beyond MAX_PAGE_OFFSET), otherwise null. The
     * `limit` value itself is not rejected when it exceeds MAX_PAGE_LIMIT —
     * callers clamp it with min() so existing clients sending generous
     * limits keep working while abusive values are neutralised.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse|null
     */
    private function validatePagination(Request $request)
    {
        if ($request->has('limit')) {
            $limit = $request->get('limit');
            if (!is_numeric($limit) || (int) $limit < 1) {
                return $this->validationError('Invalid limit parameter. Limit must be a positive integer.');
            }
        }

        if ($request->has('offset')) {
            $offset = $request->get('offset');
            if (!is_numeric($offset) || (int) $offset < 0) {
                return $this->validationError('Invalid offset parameter. Offset must be a non-negative integer.');
            }
            if ((int) $offset > self::MAX_PAGE_OFFSET) {
                return $this->validationError('Offset cannot exceed '.self::MAX_PAGE_OFFSET.'.');
            }
        }

        return null;
    }
}
