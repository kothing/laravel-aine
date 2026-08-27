<?php

namespace App\Http\Controllers\API;

use App\Aine\ContentSerializer;
use App\Aine\HtmlSanitizer;
use App\Aine\PublicCache;
use App\Events\ContentCreated;
use App\Events\ContentTrashed;
use App\Events\ContentUpdated;
use App\Http\Controllers\API\Concerns\AuthorizesProjectApi;
use App\Http\Controllers\API\Concerns\HandlesBrowserCache;
use App\Http\Controllers\Controller;
use App\Http\Resources\ContentResource;
use App\Models\Collection;
use App\Models\CollectionField;
use App\Models\Content;
use App\Models\ContentMeta;
use App\Models\Project;
use App\Services\Content\ContentMutationService;
use App\Services\Content\ContentQueryService;
use App\Services\Content\ContentValidationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class ContentController extends Controller
{
    use AuthorizesProjectApi, HandlesBrowserCache;

    /** Public API response cache lifetime in seconds. */
    const PUBLIC_CACHE_TTL = 600;

    const MAX_PAGE_LIMIT  = 100;
    const MAX_PAGE_OFFSET = 10000;

    // -----------------------------------------------------------------
    // Service-layer dependencies
    // -----------------------------------------------------------------

    protected ContentQueryService      $queryService;
    protected ContentMutationService   $mutationService;
    protected ContentValidationService $validationService;

    public function __construct()
    {
        $this->validationService = new ContentValidationService();
        $this->mutationService   = new ContentMutationService($this->validationService);
        $this->queryService      = new ContentQueryService();
    }

    // =================================================================
    // Content list
    // =================================================================

    private function getContentListByUuid($uuid, $slug, Request $request)
    {
        $project = Project::where('uuid', $uuid)->first();
        if (! $project) return $this->notFound('Project not found');
        if ($response = $this->authorizeProjectRead($project)) return $response;

        $cacheKey = $this->publicCacheKey($project, 'list', $slug, $request);
        return $this->rememberPublicJson($cacheKey, function () use ($uuid, $slug, $request) {
            return $this->resolveContentListByUuid($uuid, $slug, $request);
        }, $project->public_api);
    }

    public function getContentList($project_identifier, $slug, Request $request)
    {
        $project = $request->attributes->get('resolved_project');
        if (! $project) return $this->notFound('Project not resolved');
        return $this->getContentListByUuid($project->uuid, $slug, $request);
    }

    // =================================================================
    // Content detail
    // =================================================================

    private function getContentByUuid($uuid, $slug, $slug_id, Request $request)
    {
        $project = Project::where('uuid', $uuid)->first();
        if (! $project) return response(['error' => 'Project not found!'], 404);
        if ($response = $this->authorizeProjectRead($project)) return $response;

        $cacheKey = $this->publicCacheKey($project, 'single', $slug . '/' . $slug_id, $request);
        return $this->rememberPublicJson($cacheKey, function () use ($uuid, $slug, $slug_id, $request) {
            return $this->resolveContentByUuid($uuid, $slug, $slug_id, $request);
        }, $project->public_api);
    }

    public function getProjectContentByID($project_identifier, $slug, $slug_id, Request $request)
    {
        $project = $request->attributes->get('resolved_project');
        if (! $project) return $this->notFound('Project not resolved');
        return $this->getContentByUuid($project->uuid, $slug, $slug_id, $request);
    }

    private function resolveContentByUuid($uuid, $slug, $slug_id, Request $request)
    {
        $project = Project::where('uuid', $uuid)->first();
        if (! $project) return response(['error' => 'Project not found!'], 404);
        if ($response = $this->authorizeProjectRead($project)) return $response;

        $collection = Collection::where('project_id', $project->id)->where('slug', $slug)->first();
        if (! $collection) return response(['error' => 'Collection not found!'], 404);

        $selectFields = ['id', 'project_id', 'collection_id', 'locale'];
        if ($request->has('timestamps')) {
            $selectFields = array_merge($selectFields, ['created_at', 'updated_at', 'published_at']);
        }

        $content = Content::query()->with(['meta', 'collection.fields'])
            ->where('project_id', $project->id)
            ->where('collection_id', $collection->id)
            ->whereNotNull('published_at')
            ->select($selectFields)->find($slug_id);

        if (! $content) return $this->notFound('Not found');

        ContentSerializer::preload($content);
        return $this->success(new ContentResource($content), 'Success');
    }

    // =================================================================
    // Content by relation
    // =================================================================

    private function getContentByRelationByUuid($uuid, $slug, $slug_id, $relatedSlug, Request $request)
    {
        $project = $request->attributes->get('resolved_project')
            ?? Project::where('uuid', $uuid)->first();

        if (! $project) return $this->notFound('Project not found');
        if ($response = $this->authorizeProjectRead($project)) return $response;

        $cacheKey = $this->publicCacheKey($project, 'related', $slug . '/' . $slug_id . '/' . $relatedSlug, $request);
        return $this->rememberPublicJson($cacheKey, function () use ($project, $slug, $slug_id, $relatedSlug, $request) {
            return $this->resolveContentByRelation($project, $slug, $slug_id, $relatedSlug, $request);
        }, $project->public_api);
    }

    public function getProjectContentByRelation($project_identifier, $slug, $slug_id, $related_slug, Request $request)
    {
        $project = $request->attributes->get('resolved_project');
        if (! $project) return $this->notFound('Project not resolved');
        return $this->getContentByRelationByUuid($project->uuid, $slug, $slug_id, $related_slug, $request);
    }

    private function resolveContentByRelation(Project $project, $slug, $slug_id, $relatedSlug, Request $request)
    {
        $sourceCollection = Collection::where('project_id', $project->id)->where('slug', $slug)->first();
        if (! $sourceCollection) return $this->notFound('Source collection "' . $slug . '" not found in project');

        $relatedCollection = Collection::where('project_id', $project->id)->where('slug', $relatedSlug)->first();
        if (! $relatedCollection) return $this->notFound('Related collection "' . $relatedSlug . '" not found in project');

        $relationFields = CollectionField::where('project_id', $project->id)
            ->where('collection_id', $relatedCollection->id)->where('type', 'relation')->get();

        $relationField = null;
        foreach ($relationFields as $field) {
            $options = json_decode($field->options, true);
            if (isset($options['relation']['collection'])
                && ((string) $options['relation']['collection'] === (string) $sourceCollection->id)
            ) {
                $relationField = $field;
                break;
            }
        }

        if (! $relationField) {
            return $this->notFound('No relation field found from collection "' . $relatedSlug . '" to collection "' . $slug . '".');
        }

        $content = Content::query()->with(['meta', 'collection.fields'])
            ->where('project_id', $project->id)->where('collection_id', $relatedCollection->id);

        $metaThroughRelation = ContentMeta::where('project_id', $project->id)
            ->where('collection_id', $relatedCollection->id)
            ->where('field_name', $relationField->name)
            ->where($this->relationValueMatcher($slug_id));

        $content->whereIn('id', $metaThroughRelation->get(['content_id']));

        // --- sort ---
        if ($request->has('sort')) {
            foreach (explode(',', $request->get('sort')) as $s) {
                $sort = explode(':', $s);
                if (count($sort) < 2) return $this->validationError('Incorrect sort statement');
                if (in_array($sort[0], ['id', 'locale', 'created_at', 'updated_at', 'published_at'])) {
                    $content->orderBy($sort[0], $sort[1]);
                } else {
                    $content->orderBy(
                        ContentMeta::select('value')->whereColumn('content_meta.content_id', 'content.id')
                            ->where('field_name', $sort[0])->latest()->take(1),
                        $sort[1]
                    );
                }
            }
        }

        // --- state filter ---
        if ($request->has('state')) {
            if ($request->get('state') === 'only_draft') $content->whereNull('published_at');
        } else {
            $content->whereNotNull('published_at');
        }

        if ($request->has('offset') && ! $request->has('limit')) {
            return $this->validationError('Incorrect offset statement.');
        }
        if ($paginationError = $this->validatePagination($request)) return $paginationError;

        if ($request->has('offset')) $content->offset(min((int) $request->get('offset'), self::MAX_PAGE_OFFSET));
        if ($request->has('limit'))  $content->limit(min((int) $request->get('limit'), self::MAX_PAGE_LIMIT));

        if ($request->has('count')) return $this->success($content->count(), 'Success');

        $selectFields = ['id', 'project_id', 'collection_id', 'locale'];
        if ($request->has('timestamps')) {
            $selectFields = array_merge($selectFields, ['created_at', 'updated_at', 'published_at']);
        }
        $content = $content->select($selectFields);

        if ($request->has('first')) {
            $content = $content->first();
            if (! $content) return $this->notFound('Not found');
            ContentSerializer::preload($content);
            return $this->success(new ContentResource($content), 'Success');
        }

        $content = $content->get();
        ContentSerializer::preload($content);
        return $this->success(ContentResource::collection($content), 'Success');
    }

    // =================================================================
    // Search
    // =================================================================

    public function searchContent($project_identifier, $slug, Request $request)
    {
        $project = $request->attributes->get('resolved_project');
        if (! $project) return $this->notFound('Project not resolved');
        return $this->searchContentByUuid($project->uuid, $slug, $request);
    }

    private function searchContentByUuid($uuid, $slug, Request $request)
    {
        $project = Project::where('uuid', $uuid)->first();
        if (! $project) return $this->notFound('Project not found');
        if ($response = $this->authorizeProjectRead($project)) return $response;

        $query = trim((string) $request->get('query', ''));
        $queryLen = mb_strlen($query);
        if ($queryLen < 2)   return $this->validationError('Search query must be at least 2 characters.');
        if ($queryLen > 100) return $this->validationError('Search query cannot exceed 100 characters.');

        $result = $this->queryService->search(
            $project, $slug, $query,
            (int) ($request->get('limit') ?: 20),
            (int) ($request->get('offset') ?: 0),
            $request->get('state')
        );

        $responseData = [];
        if ($result['tokens']->isNotEmpty()) {
            ContentSerializer::preload($result['tokens']);
            $responseData = json_decode(ContentResource::collection($result['tokens'])->toJson(), true);
        }

        return response()->json([
            'success' => true, 'code' => 200, 'message' => 'Success',
            'data' => $responseData, 'total' => $result['total'],
            'limit' => $result['limit'], 'offset' => $result['offset'],
        ], 200);
    }

    // =================================================================
    // Create content (API)
    // =================================================================

    public function createContent($project_identifier, $slug, Request $request)
    {
        $project = $request->attributes->get('resolved_project');
        if (! $project) return $this->notFound('Project not resolved');
        return $this->createContentByUuid($project->uuid, $slug, $request);
    }

    private function createContentByUuid($uuid, $slug, Request $request)
    {
        if ($response = $this->authorizeProjectAbility('create', $uuid)) return $response;

        $project = Project::where('uuid', $uuid)->first();
        if (! $project) return $this->notFound('Project not found');

        $collection = Collection::query()->with(['fields'])
            ->where('project_id', $project->id)->where('slug', $slug)->first();
        if (! $collection) return $this->notFound('Collection not found');

        $fields = $this->decodeFieldMeta($collection->fields);

        // Check repeatable fields are arrays.
        foreach ($fields as $field) {
            $opts = $field->options;
            if (! (isset($opts->repeatable) && $opts->repeatable)) continue;
            if (! $request->has($field->name)) continue;
            if (! is_array($request->get($field->name))) {
                return $this->validationError('Repeatable field ' . $field->name . ' must be an array!');
            }
        }

        // Build + run validation rules (API prefix: "" — no "data." wrapper).
        [$rules, $messages] = $this->validationService->buildFieldValidationRules($fields, '');
        ContentValidationService::registerCustomValidators();
        Validator::make($request->except(['locale']), $rules, $messages)->validate();

        // Unique validation.
        $input = $request->except(['locale', 'draft']);

        // Sanitize richtext fields (same as the admin path).
        $input = $this->sanitizeRichtextInput($input, $collection->fields);

        if ($uniqErrors = $this->validationService->validateUniqueFields($fields, $input, $collection->id)) {
            return response($uniqErrors, 422);
        }

        // Create via service.
        $content = $this->mutationService->create(
            $project, $collection, $input,
            $request->get('locale') ?: $project->default_locale,
            published: ! ($request->has('draft') && $request->get('draft') == 1),
            createdBy: null
        );

        ContentCreated::dispatch(['source' => 'API', 'content' => $content]);
        ContentSerializer::preload($content);
        return $this->created(new ContentResource($content), 'Content created successfully');
    }

    // =================================================================
    // Update content (API)
    // =================================================================

    public function updateContent($project_identifier, $slug, $slug_id, Request $request)
    {
        $project = $request->attributes->get('resolved_project');
        if (! $project) return $this->notFound('Project not resolved');
        return $this->updateContentByUuid($project->uuid, $slug, $slug_id, $request);
    }

    private function updateContentByUuid($uuid, $slug, $slug_id, Request $request)
    {
        if ($response = $this->authorizeProjectAbility('update', $uuid)) return $response;

        $project = Project::where('uuid', $uuid)->first();
        if (! $project) return $this->notFound('Project not found');

        $collection = Collection::query()->with(['fields'])
            ->where('project_id', $project->id)->where('slug', $slug)->first();
        if (! $collection) return $this->notFound('Collection not found');

        $content = Content::where('project_id', $project->id)
            ->where('collection_id', $collection->id)->where('id', $slug_id)->first();
        if (! $content) return $this->notFound('Record not found');

        $fields = $this->decodeFieldMeta($collection->fields);

        // Check repeatable fields.
        foreach ($fields as $field) {
            $opts = $field->options;
            if (! (isset($opts->repeatable) && $opts->repeatable)) continue;
            if (! $request->has($field->name)) continue;
            if (! is_array($request->get($field->name))) {
                return $this->validationError('Repeatable field ' . $field->name . ' must be an array!');
            }
        }

        [$rules, $messages] = $this->validationService->buildFieldValidationRules($fields, '');
        ContentValidationService::registerCustomValidators();
        Validator::make($request->all(), $rules, $messages)->validate();

        // Sanitize richtext fields.
        $input = $request->except(['locale', 'draft']);
        $input = $this->sanitizeRichtextInput($input, $collection->fields);

        // Unique check (excluding current content).
        if ($uniqErrors = $this->validationService->validateUniqueFields($fields, $input, $collection->id, $content->id)) {
            return response($uniqErrors, 422);
        }

        // Update via service.
        $this->mutationService->update(
            $content, $collection, $input,
            $request->get('locale'),
            published: ! ($request->has('draft') && $request->get('draft') == 1),
            updatedBy: auth('sanctum')->id(),
            scheduledAtRaw: $request->has('scheduled_at') ? $request->get('scheduled_at') : null,
            deletedMetaIds: $request->get('deleted', []),
        );

        ContentUpdated::dispatch(['source' => 'API', 'content' => $content]);
        ContentSerializer::preload($content);
        return $this->updated(new ContentResource($content), 'Content updated successfully');
    }

    // =================================================================
    // Delete content (API)
    // =================================================================

    public function deleteContent($project_identifier, $slug, $slug_id, Request $request)
    {
        $project = $request->attributes->get('resolved_project');
        if (! $project) return $this->notFound('Project not resolved');
        return $this->deleteContentByUuid($project->uuid, $slug, $slug_id);
    }

    private function deleteContentByUuid($uuid, $slug, $slug_id)
    {
        if ($response = $this->authorizeProjectAbility('delete', $uuid)) return $response;

        $project = Project::where('uuid', $uuid)->first();
        if (! $project) return $this->notFound('Project not found');

        $collection = Collection::query()->with(['fields'])
            ->where('project_id', $project->id)->where('slug', $slug)->first();
        if (! $collection) return $this->notFound('Collection not found');

        $content = Content::where('project_id', $project->id)
            ->where('collection_id', $collection->id)->find($slug_id);
        if (! $content) return $this->notFound('Record not found');

        $this->mutationService->delete($content);
        ContentTrashed::dispatch(['source' => 'API', 'content' => $content]);
        return $this->deleted('Record deleted');
    }

    // =================================================================
    // Portal
    // =================================================================

    public function getPortalContent($project_identifier, Request $request)
    {
        $project = $request->attributes->get('resolved_project');
        if (! $project) return $this->notFound('Project not resolved');
        if ($response = $this->authorizeProjectRead($project)) return $response;

        $collectionSlug = $request->get('collection', 'articles');
        $cacheKey = $this->publicCacheKey($project, 'portal', $collectionSlug, $request);

        return $this->rememberPublicJson($cacheKey, function () use ($project, $collectionSlug, $request) {
            return $this->resolvePortalContent($project, $collectionSlug, $request);
        }, $project->public_api);
    }

    private function resolvePortalContent(Project $project, string $collectionSlug, Request $request): JsonResponse
    {
        $listFn = function (string $uuidOrId, string $slug, array $overrides,
                            ?string $relSlug = null, $relId = null) use ($project, $request) {
            $sub = $this->portalSubRequest($request, $overrides);
            if ($relSlug !== null && $relId !== null) {
                $resp = $this->resolveContentByRelation($project, $relSlug, $relId, $slug, $sub);
            } else {
                $resp = $this->resolveContentListByUuid($uuidOrId, $slug, $sub);
            }
            return $resp instanceof JsonResponse ? ($resp->getData(true)['data'] ?? []) : [];
        };

        // Categories
        $categories = $listFn($project->uuid, 'categories', []);
        $sections = [];
        foreach ($categories as $category) {
            $related = $listFn(
                $project->identifier, $collectionSlug,
                ['limit' => 50, 'sort' => 'published_at:desc', 'timestamps' => true, 'state' => 'only_published'],
                'categories', $category['id'] ?? null
            );
            $tagMap = [];
            foreach ($related as $item) {
                foreach ($item['tags'] ?? [] as $tag) $tagMap[$tag['id']] = $tag;
            }
            $sections[] = ['category' => $category, 'items' => array_slice($related, 0, 8), 'tags' => array_values($tagMap)];
        }

        $skel = fn (array $o) => ['sort' => 'published_at:desc', 'timestamps' => true] + $o;

        return response()->json([
            'success' => true, 'code' => 200, 'message' => 'Success',
            'data' => [
                'categories'  => $sections,
                'featured'    => $listFn($project->uuid, $collectionSlug, $skel(['where' => ['featured' => 1], 'limit' => 8])),
                'recommended' => $listFn($project->uuid, $collectionSlug, $skel(['where' => ['recommended' => 1], 'limit' => 8])),
                'slider'      => $listFn($project->uuid, $collectionSlug, $skel(['where' => ['slider' => 1], 'limit' => 5])),
                'latest'      => $listFn($project->uuid, $collectionSlug, $skel(['limit' => 10])),
                'pages'       => $listFn($project->uuid, 'pages', ['timestamps' => true]),
            ],
        ]);
    }

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

    // =================================================================
    // Core query builder: content list by UUID (where / sort / paginate)
    // =================================================================

    private function resolveContentListByUuid($uuid, $slug, Request $request)
    {
        $project = Project::where('uuid', $uuid)->first();
        if (! $project) return $this->notFound('Project not found');
        if ($response = $this->authorizeProjectRead($project)) return $response;

        $collection = Collection::where('project_id', $project->id)->where('slug', $slug)->first();
        if (! $collection) return $this->notFound('Collection not found');

        $content = Content::query()->with(['meta', 'collection.fields'])
            ->where('project_id', $project->id)->where('collection_id', $collection->id);

        // --- where clause ---
        if ($request->has('where')) {
            $where = $request->get('where');
            if (! is_array($where)) {
                return $this->validationError('Incorrect where statement.');
            }
            $result = $this->applyWhereClause($content, $where, $project, $collection, $request);
            if ($result instanceof JsonResponse) return $result;
        }

        // --- whereRelation ---
        if ($request->has('whereRelation')) {
            $result = $this->applyWhereRelation($content, $request->get('whereRelation'), $project, $collection);
            if ($result instanceof JsonResponse) return $result;
        }

        // --- sort ---
        if ($request->has('sort')) {
            foreach (explode(',', $request->get('sort')) as $s) {
                $sort = explode(':', $s);
                if (count($sort) < 2) return $this->validationError('Incorrect sort statement');
                if (in_array($sort[0], ['id', 'locale', 'created_at', 'updated_at', 'published_at'])) {
                    $content->orderBy($sort[0], $sort[1]);
                } else {
                    $content->orderBy(
                        ContentMeta::select('value')->whereColumn('content_meta.content_id', 'content.id')
                            ->where('field_name', $sort[0])->latest()->take(1),
                        $sort[1]
                    );
                }
            }
        }

        // --- state filter ---
        if ($request->has('state')) {
            if ($request->get('state') === 'only_draft') $content->whereNull('published_at');
        } else {
            $content->whereNotNull('published_at');
        }

        if ($request->has('offset') && ! $request->has('limit')) {
            return $this->validationError('Incorrect offset statement.');
        }
        if ($paginationError = $this->validatePagination($request)) return $paginationError;

        if ($request->has('offset')) $content->offset(min((int) $request->get('offset'), self::MAX_PAGE_OFFSET));
        if ($request->has('limit'))  $content->limit(min((int) $request->get('limit'), self::MAX_PAGE_LIMIT));

        if ($request->has('count')) return $this->success($content->count(), 'Success');

        $selectFields = ['id', 'project_id', 'collection_id', 'locale'];
        if ($request->has('timestamps')) {
            $selectFields = array_merge($selectFields, ['created_at', 'updated_at', 'published_at']);
        }
        $content->with(['meta', 'collection.fields'])->select($selectFields);

        if ($request->has('first')) {
            $content = $content->first();
            if (! $content) return $this->notFound('Not found');
            ContentSerializer::preload($content);
            return $this->success(new ContentResource($content), 'Success');
        }

        $content = $content->get();
        ContentSerializer::preload($content);
        return $this->success(ContentResource::collection($content), 'Success');
    }

    // =================================================================
    // Where-clause engine
    // =================================================================

    private function applyWhereClause($content, array $where, Project $project, Collection $collection, Request $request)
    {
        $isMultiDim = is_numeric(array_key_first($where)) && array_key_first($where) === 0;

        if ($isMultiDim) {
            return $this->applyMultiWhere($content, $where, $project, $collection);
        }
        return $this->applySingleWhere($content, $where, $project, $collection);
    }

    private function applySingleWhere($content, array $where, Project $project, Collection $collection)
    {
        $meta = ContentMeta::where('project_id', $project->id)->where('collection_id', $collection->id);

        foreach ($where as $key => $value) {
            if (in_array($key, ['id', 'locale', 'created_at', 'updated_at', 'published_at'])) {
                $this->applyDirectWhere($content, $key, $value);
            } else {
                $this->applyMetaWhere($meta, $key, $value, $project, $collection);
            }
        }
        $content->whereIn('id', $meta->get(['content_id']));
        return null;
    }

    private function applyMultiWhere($content, array $where, Project $project, Collection $collection)
    {
        $metaSql = 'SELECT c.id as content_id FROM content c,';
        $bind = []; $num = 1;

        foreach ($where as $w) { $metaSql .= ' content_meta m' . $num . ','; $num++; }
        $metaSql = rtrim($metaSql, ',') . ' WHERE ';
        $num = 1;
        foreach ($where as $w) {
            $metaSql .= ' m' . $num . ".project_id='" . $project->id . "' AND m" . $num . ".collection_id='" . $collection->id . "' AND ";
            $num++;
        }
        $metaSql = rtrim($metaSql, ' AND ');
        $num = 1;
        foreach ($where as $w) { $metaSql .= ' AND c.id = m' . $num . '.content_id'; $num++; }
        $metaSql .= ' AND (';
        $num = 1;

        foreach ($where as $k => $w) {
            foreach ($w as $key => $value) {
                if (in_array($key, ['id', 'locale', 'created_at', 'updated_at', 'published_at'])) continue;

                if ($num != 1 && $k === 'or') $metaSql .= ' OR ';
                if ($num > 1 && $k !== 'or') $metaSql .= ' AND ';

                if (is_array($value)) {
                    $metaSql .= '(m' . $num . '.field_name= ? AND '; $bind[] = $key;
                    if (isset($value['like'])) {
                        $bind[] = "%{$value['like']}%"; $metaSql .= 'm' . $num . '.value LIKE ?)';
                    } elseif (isset($value['not'])) {
                        $bind[] = $value['not']; $metaSql .= 'm' . $num . '.value != ?)';
                    } elseif (isset($value['in'])) {
                        $inV = array_map('trim', explode(',', $value['in']));
                        $metaSql .= 'm' . $num . '.value IN (' . implode(',', array_fill(0, count($inV), '?')) . '))';
                        foreach ($inV as $iv) $bind[] = $iv;
                    } elseif (isset($value['not_in'])) {
                        $niV = array_map('trim', explode(',', $value['not_in']));
                        $metaSql .= 'm' . $num . '.value NOT IN (' . implode(',', array_fill(0, count($niV), '?')) . '))';
                        foreach ($niV as $nv) $bind[] = $nv;
                    } elseif (isset($value['lt'])) {
                        $bind[] = $value['lt']; $metaSql .= 'm' . $num . '.value < ?)';
                    } elseif (isset($value['lte'])) {
                        $bind[] = $value['lte']; $metaSql .= 'm' . $num . '.value <= ?)';
                    } elseif (isset($value['gt'])) {
                        $bind[] = $value['gt']; $metaSql .= 'm' . $num . '.value > ?)';
                    } elseif (isset($value['gte'])) {
                        $bind[] = $value['gte']; $metaSql .= 'm' . $num . '.value >= ?)';
                    } elseif (isset($value['between'])) {
                        $exp = array_map('trim', explode(',', $value['between']));
                        if (count($exp) < 2 || count($exp) > 2) return $this->validationError('Incorrect where statement');
                        $metaSql .= 'm' . $num . '.value BETWEEN ? AND ?)'; $bind[] = $exp[0]; $bind[] = $exp[1];
                    } elseif (isset($value['not_between'])) {
                        $exp = array_map('trim', explode(',', $value['not_between']));
                        if (count($exp) < 2 || count($exp) > 2) return $this->validationError('Incorrect where statement');
                        $metaSql .= 'm' . $num . '.value NOT BETWEEN ? AND ?)'; $bind[] = $exp[0]; $bind[] = $exp[1];
                    }
                } else {
                    if ($value === 'null') {
                        $notNull = ContentMeta::where('project_id', $project->id)
                            ->where('collection_id', $collection->id)->where('field_name', $key)
                            ->where('value', '!=', '')->get(['content_id']);
                        $ids = $notNull->pluck('content_id')->implode(',') ?: '-1';
                        $metaSql .= 'm' . $num . '.content_id NOT IN (' . $ids . ')';
                    } elseif ($value === 'not_null') {
                        $notNull = ContentMeta::where('project_id', $project->id)
                            ->where('collection_id', $collection->id)->where('field_name', $key)
                            ->where('value', '!=', '')->get(['content_id']);
                        $ids = $notNull->pluck('content_id')->implode(',') ?: '-1';
                        $metaSql .= 'm' . $num . '.content_id IN (' . $ids . ')';
                    } else {
                        $field = CollectionField::where('project_id', $project->id)
                            ->where('collection_id', $collection->id)->where('name', $key)->first();
                        if (! $field) return $this->validationError('Field not found [' . $key . ']');
                        if ($field->type === 'relation') {
                            $metaSql .= '(m' . $num . '.field_name= ? AND (m' . $num . '.value = ? OR m' . $num . '.value LIKE ? OR m' . $num . '.value LIKE ? OR m' . $num . '.value LIKE ?))';
                            $bind[] = $key; $bind[] = $value; $bind[] = $value . ',%'; $bind[] = '%,' . $value; $bind[] = '%,' . $value . ',%';
                        } else {
                            $bind[] = $key; $bind[] = $value;
                            $metaSql .= '(m' . $num . '.field_name= ? AND m' . $num . '.value= ?)';
                        }
                    }
                }
            }
            $num++;
        }
        $metaSql .= ')';
        $num = 1;
        foreach ($where as $w) { $metaSql .= ' AND m' . $num . '.deleted_at is null'; $num++; }

        $query = DB::select($metaSql, $bind);
        $ids = array_map(fn ($q) => $q->content_id, $query);
        $content->whereIn('id', $ids);
        return null;
    }

    private function applyDirectWhere($content, string $key, $value): void
    {
        if (! is_array($value)) {
            if (in_array($key, ['created_at', 'updated_at', 'published_at'])) {
                $content->whereDate($key, $value);
            } else {
                $content->where($key, $value);
            }
            return;
        }

        $isDate = in_array($key, ['created_at', 'updated_at', 'published_at']);

        if (isset($value['not']))  $content->where($key, '!=', $value['not']);
        if (isset($value['in']))   $content->whereIn($key, explode(',', $value['in']));
        if (isset($value['not_in'])) $content->whereNotIn($key, explode(',', $value['not_in']));

        foreach (['lt', 'lte', 'gt', 'gte'] as $op) {
            if (isset($value[$op])) {
                $method = $isDate ? 'whereDate' : 'where';
                $content->$method($key, $op === 'lt' ? '<' : ($op === 'lte' ? '<=' : ($op === 'gt' ? '>' : '>=')), $value[$op]);
            }
        }

        if (isset($value['between'])) {
            $exp = explode(',', $value['between']);
            if (count($exp) === 2) $content->whereBetween($key, $exp);
        }
        if (isset($value['not_between'])) {
            $exp = explode(',', $value['not_between']);
            if (count($exp) === 2) $content->whereNotBetween($key, $exp);
        }
    }

    private function applyMetaWhere($meta, string $key, $value, Project $project, Collection $collection): void
    {
        if (! is_array($value)) {
            if ($value === 'null') {
                $copy = (clone $meta)->where('field_name', $key)->where('value', '!=', '')->get(['content_id']);
                $meta->whereNotIn('content_id', $copy);
            } elseif ($value === 'not_null') {
                $copy = (clone $meta)->where('field_name', $key)->where('value', '!=', '')->get(['content_id']);
                $meta->whereIn('content_id', $copy);
            } else {
                $field = CollectionField::where('project_id', $project->id)
                    ->where('collection_id', $collection->id)->where('name', $key)->first();
                if (! $field) return;
                if ($field->type === 'relation') {
                    $meta->where('field_name', $key)->where($this->relationValueMatcher($value));
                } else {
                    $meta->where('field_name', $key)->where('value', $value);
                }
            }
            return;
        }

        $meta->where('field_name', $key);
        if (isset($value['like']))       $meta->where('value', 'LIKE', "%{$value['like']}%");
        if (isset($value['not']))        $meta->where('value', '!=', $value['not']);
        if (isset($value['in']))         $meta->whereIn('value', array_map('trim', explode(',', $value['in'])));
        if (isset($value['not_in']))     $meta->whereNotIn('value', array_map('trim', explode(',', $value['not_in'])));
        if (isset($value['lt']))         $meta->where('value', '<', $value['lt']);
        if (isset($value['lte']))        $meta->where('value', '<=', $value['lte']);
        if (isset($value['gt']))         $meta->where('value', '>', $value['gt']);
        if (isset($value['gte']))        $meta->where('value', '>=', $value['gte']);
        if (isset($value['between']))    { $exp = explode(',', $value['between']); if (count($exp) === 2) $meta->whereBetween('value', $exp); }
        if (isset($value['not_between'])){ $exp = explode(',', $value['not_between']); if (count($exp) === 2) $meta->whereNotBetween('value', $exp); }
    }

    private function applyWhereRelation($content, array $whereRelation, Project $project, Collection $collection)
    {
        foreach ($whereRelation as $key => $value) {
            $mainField = CollectionField::where('project_id', $project->id)
                ->where('collection_id', $collection->id)->where('name', $key)->first();
            if (! $mainField) return $this->validationError('Field not found [' . $key . ']');
            if ($mainField->type !== 'relation') return $this->validationError('This field is not a relation type field.');

            $relationOptions = json_decode($mainField->options);
            foreach ($value as $rKey => $rValue) {
                $relationField = CollectionField::where('project_id', $project->id)
                    ->where('collection_id', $relationOptions->relation->collection)
                    ->where('name', $rKey)->first();
                if (! $relationField) return $this->validationError('Relation field not found [' . $rKey . ']');

                $relationMeta = ContentMeta::where('project_id', $project->id)
                    ->where('collection_id', $relationOptions->relation->collection)
                    ->where('field_name', $rKey)->where('value', 'LIKE', "%{$rValue}%")
                    ->first(['content_id']);
                if (! $relationMeta) return $this->notFound('Record not found');

                $meta = ContentMeta::where('project_id', $project->id)
                    ->where('collection_id', $collection->id)->where('field_name', $key)
                    ->where($this->relationValueMatcher($relationMeta->content_id));
                $content->whereIn('id', $meta->get(['content_id']));
            }
        }
        return null;
    }

    // =================================================================
    // Helpers
    // =================================================================

    private function relationValueMatcher($id): \Closure
    {
        return function ($query) use ($id) {
            $query->where('value', (string) $id)
                ->orWhere('value', 'like', (string) $id . ',%')
                ->orWhere('value', 'like', '%,' . (string) $id)
                ->orWhere('value', 'like', '%,' . (string) $id . ',%');
        };
    }

    /** Sanitize richtext field values before storage (mirrors admin preProcessData). */
    private function sanitizeRichtextInput(array $input, $fields): array
    {
        $richtextFields = [];
        foreach ($fields as $field) {
            if ($field->type === 'richtext') {
                $richtextFields[] = $field->name;
            }
        }
        foreach ($richtextFields as $name) {
            if (isset($input[$name]) && is_string($input[$name])) {
                $input[$name] = HtmlSanitizer::sanitize($input[$name]);
            }
        }
        return $input;
    }

    private function decodeFieldMeta($fields): array
    {
        $decoded = [];
        foreach ($fields as $field) {
            $f = clone $field;
            $f->validations = json_decode($f->validations);
            $f->options     = json_decode($f->options);
            $decoded[] = $f;
        }
        return $decoded;
    }

    // =================================================================
    // Caching helpers
    // =================================================================

    private function rememberPublicJson($cacheKey, callable $builder, bool $browserCacheable = false)
    {
        $etag = $this->publicApiEtag($cacheKey);
        $browserCache = $browserCacheable && ! auth('sanctum')->check();

        if ($browserCache && $this->ifNoneMatchMatches(request(), $etag)) {
            return $this->respondNotModified($etag);
        }

        $cached = $this->cacheGet($cacheKey);
        if ($cached !== null) {
            $response = response($cached['body'], $cached['status'])->header('Content-Type', 'application/json');
            if ($browserCache) {
                $response->header('ETag', $etag)->header('Cache-Control', 'no-cache, must-revalidate');
            }
            return $response;
        }

        $response = $builder();
        if ($response instanceof JsonResponse && $response->getStatusCode() >= 200 && $response->getStatusCode() < 300) {
            $this->cachePut($cacheKey, [
                'status' => $response->getStatusCode(),
                'body'   => $response->getContent(),
            ], self::PUBLIC_CACHE_TTL);
            if ($browserCache) {
                $response->header('ETag', $etag)->header('Cache-Control', 'no-cache, must-revalidate');
            }
        }
        return $response;
    }

    private function publicCacheKey($project, $endpoint, $slugPath, Request $request)
    {
        $query = $request->query();
        $this->ksortRecursive($query);
        return implode(':', [
            'public_content', $this->publicCacheVersion($project->id), $project->id,
            $request->getSchemeAndHttpHost(), $endpoint, $slugPath, md5(json_encode($query)),
        ]);
    }

    private function publicCacheVersion($projectId): int
    {
        return PublicCache::version((int) $projectId);
    }

    private function cacheGet($key, $default = null)
    {
        try { return Cache::get($key, $default); } catch (\Throwable $e) { return $default; }
    }

    private function cachePut($key, $value, $ttl)
    {
        try { Cache::put($key, $value, $ttl); } catch (\Throwable $e) { /* best-effort */ }
    }

    private function ksortRecursive(&$array)
    {
        if (! is_array($array)) return;
        ksort($array);
        foreach ($array as &$value) { if (is_array($value)) $this->ksortRecursive($value); }
        unset($value);
    }

    private function validatePagination(Request $request)
    {
        if ($request->has('limit')) {
            $limit = $request->get('limit');
            if (! is_numeric($limit) || (int) $limit < 1) {
                return $this->validationError('Invalid limit parameter.');
            }
        }
        if ($request->has('offset')) {
            $offset = $request->get('offset');
            if (! is_numeric($offset) || (int) $offset < 0) {
                return $this->validationError('Invalid offset parameter.');
            }
            if ((int) $offset > self::MAX_PAGE_OFFSET) {
                return $this->validationError('Offset cannot exceed ' . self::MAX_PAGE_OFFSET . '.');
            }
        }
        return null;
    }
}