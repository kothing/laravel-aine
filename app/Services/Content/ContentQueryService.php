<?php

namespace App\Services\Content;

use App\Models\Collection;
use App\Models\CollectionField;
use App\Models\Content;
use App\Models\ContentMeta;
use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

/**
 * Shared read-side helpers for content queries consumed by the public
 * API. The complex "where" clause builder is intentionally left in the
 * controller because its parameter binding is tightly coupled to the
 * request shape — it would need a dedicated query-builder abstraction
 * that is out of scope for this refactoring round.
 */
class ContentQueryService
{
    /**
     * Portal skeleton limits (mirrors the SPA's config.js values).
     */
    public const CATEGORY_LIMIT          = 8;
    public const CATEGORY_RELATED_LIMIT  = 50;
    public const FEATURED_LIMIT          = 8;
    public const RECOMMENDED_LIMIT       = 8;
    public const SLIDER_LIMIT            = 5;
    public const LATEST_LIMIT            = 10;

    /**
     * Pagination guards for the public API (DoS protection).
     */
    public const MAX_PAGE_LIMIT  = 100;
    public const MAX_PAGE_OFFSET = 10000;

    /**
     * Build a portal content skeleton for one project.
     *
     * @return array  Structured ['categories', 'featured', 'recommended',
     *                'slider', 'latest', 'pages'] ready for JSON.
     */
    public function portalSkeleton(
        Project $project,
        string $collectionSlug,
        callable $listResolver
    ): array {
        $categories = $listResolver($project->uuid, 'categories', []);

        $sections = [];
        foreach ($categories as $category) {
            $related = $listResolver(
                $project->uuid,
                'categories',
                $category['id'] ?? null,
                $collectionSlug,
                [
                    'limit'      => self::CATEGORY_RELATED_LIMIT,
                    'sort'       => 'published_at:desc',
                    'timestamps' => true,
                    'state'      => 'only_published',
                ]
            );

            $tagMap = [];
            foreach ($related as $item) {
                foreach ($item['tags'] ?? [] as $tag) {
                    $tagMap[$tag['id']] = $tag;
                }
            }

            $sections[] = [
                'category' => $category,
                'items'    => array_slice($related, 0, self::CATEGORY_LIMIT),
                'tags'     => array_values($tagMap),
            ];
        }

        $params = fn (array $overrides) => [
            'sort'       => 'published_at:desc',
            'timestamps' => true,
        ] + $overrides;

        return [
            'categories'  => $sections,
            'featured'    => $listResolver($project->uuid, $collectionSlug, $params(['where' => ['featured' => 1], 'limit' => self::FEATURED_LIMIT])),
            'recommended' => $listResolver($project->uuid, $collectionSlug, $params(['where' => ['recommended' => 1], 'limit' => self::RECOMMENDED_LIMIT])),
            'slider'      => $listResolver($project->uuid, $collectionSlug, $params(['where' => ['slider' => 1], 'limit' => self::SLIDER_LIMIT])),
            'latest'      => $listResolver($project->uuid, $collectionSlug, $params(['limit' => self::LATEST_LIMIT])),
            'pages'       => $listResolver($project->uuid, 'pages', ['timestamps' => true]),
        ];
    }

    /**
     * Search content within a collection by full-text match on meta values.
     *
     * @return array{tokens: Content[], total: int, limit: int, offset: int}
     */
    public function search(
        Project $project,
        string $slug,
        string $query,
        int $limit = 20,
        int $offset = 0,
        ?string $state = null
    ): array {
        $collection = Collection::where('project_id', $project->id)
            ->where('slug', $slug)
            ->first();

        if (! $collection) {
            return ['tokens' => [], 'total' => 0, 'limit' => $limit, 'offset' => $offset];
        }

        // Clamp
        $limit  = max(1, min($limit, 100));
        $offset = max(0, $offset);

        $contentIds = ContentMeta::query()
            ->where('project_id', $project->id)
            ->where('collection_id', $collection->id)
            ->where('value', 'LIKE', '%' . $query . '%')
            ->pluck('content_id')
            ->unique();

        if ($state === 'only_draft') {
            $contentIds = Content::whereIn('id', $contentIds)
                ->whereNull('published_at')
                ->whereNull('draft_parent_id')
                ->pluck('id');
        } else {
            $contentIds = Content::whereIn('id', $contentIds)
                ->whereNotNull('published_at')
                ->whereNull('draft_parent_id')
                ->pluck('id');
        }

        $total   = $contentIds->count();
        $pageIds = $contentIds->slice($offset, $limit)->values();

        $contents = collect();
        if ($pageIds->isNotEmpty()) {
            $contents = Content::query()
                ->with(['meta', 'collection.fields'])
                ->select(['id', 'project_id', 'collection_id', 'locale'])
                ->whereIn('id', $pageIds)
                ->get();
        }

        return [
            'tokens' => $contents,
            'total'  => $total,
            'limit'  => $limit,
            'offset' => $offset,
        ];
    }

    /**
     * Resolve a project by UUID and optionally authorize read access.
     *
     * @return Project|null
     */
    public function resolveProjectByUuid(string $uuid): ?Project
    {
        return Project::where('uuid', $uuid)->first();
    }

    /**
     * Resolve a collection by project id and slug.
     *
     * @return Collection|null
     */
    public function resolveCollection(int $projectId, string $slug): ?Collection
    {
        return Collection::with(['fields'])
            ->where('project_id', $projectId)
            ->where('slug', $slug)
            ->first();
    }

    /**
     * Build a "where value matches a relation" SQL expression for
     * comma-separated relation lists.
     *
     * A relation value like "5,12,8" stores multiple related-entity ids
     * as a CSV string. We need to match "5" against `= '5'` or `LIKE
     * '5,%'` or `LIKE '%,5'` or `LIKE '%,5,%'`.
     */
    public function relationValueCallback($value): callable
    {
        return function ($query) use ($value) {
            $query->where('value', $value)
                  ->orWhere('value', 'LIKE', $value . ',%')
                  ->orWhere('value', 'LIKE', '%,' . $value)
                  ->orWhere('value', 'LIKE', '%,' . $value . ',%');
        };
    }

    /**
     * Validate pagination parameters against the configured guards.
     *
     * @return JsonResponse|null  Null = valid; otherwise an error response.
     */
    public function validatePagination(Request $request): ?JsonResponse
    {
        if ($request->has('offset') && ! $request->has('limit')) {
            return response()->json([
                'success' => false,
                'code'    => 422,
                'message' => 'Incorrect offset statement. Offset must be used with limit.',
                'data'    => null,
            ], 422);
        }

        if ($request->has('limit') && (int) $request->get('limit') < 1) {
            return response()->json([
                'success' => false,
                'code'    => 422,
                'message' => 'Limit must be at least 1.',
                'data'    => null,
            ], 422);
        }

        if ($request->has('offset') && (int) $request->get('offset') > self::MAX_PAGE_OFFSET) {
            return response()->json([
                'success' => false,
                'code'    => 422,
                'message' => 'Offset may not exceed ' . self::MAX_PAGE_OFFSET . '.',
                'data'    => null,
            ], 422);
        }

        return null;
    }
}