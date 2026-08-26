<?php

namespace App\Aine;

use App\Models\Collection;
use App\Models\Content;
use App\Models\Media;
use App\Models\Project;

/**
 * Batch preloader for the public content serialisation.
 *
 * ContentResource serialises each content by resolving its collection,
 * media values and relation values. Naively this costs one query per
 * value (and per relation, recursively) — an N+1 that explodes on list
 * endpoints. This class preloads every collection, media row and related
 * content referenced by a batch of Content models in a handful of
 * WHERE IN queries, caching the results in memory for the duration of the
 * request. ContentResource reads from these maps, falling back to a single
 * lazy query when something was not preloaded (correctness first).
 *
 * The in-memory maps are intentionally tied to a single request: the app
 * resets them via the `terminating` callback (AppServiceProvider).
 */
class ContentSerializer
{
    /**
     * How many levels of relations-of-relations to follow when preloading.
     * A limit is required because relation graphs can be cyclic.
     */
    const MAX_RELATION_DEPTH = 3;

    /** @var array<int, Collection> collection id => collection (fields eager loaded) */
    private static $collectionMap = [];

    /** @var array<string, Media|null> "projectId:mediaId" => Media (null = not found) */
    private static $mediaMap = [];

    /** @var array<string, Content|null> "projectId:contentId" => Content (null = not found / unpublished) */
    private static $relationMap = [];

    /** @var array<int, Project> project id => project */
    private static $projectMap = [];

    /**
     * Drop every cached row. Registered as a `terminating` callback so
     * long-lived processes (Octane, phpunit suites, ...) never serve stale
     * model state across requests.
     *
     * @return void
     */
    public static function reset(): void
    {
        self::$collectionMap = [];
        self::$mediaMap = [];
        self::$relationMap = [];
        self::$projectMap = [];
    }

    /**
     * Preload collections, media and related content (recursively) for a
     * single Content model or a collection of them.
     *
     * @param \App\Models\Content|\Traversable|array $contents
     * @return void
     */
    public static function preload($contents): void
    {
        self::loadFor(self::normalize($contents), 0);
    }

    /**
     * @param array<int, Content> $contents
     * @param int $depth
     * @return void
     */
    private static function loadFor(array $contents, int $depth): void
    {
        if ($depth > self::MAX_RELATION_DEPTH || ! $contents) {
            return;
        }

        // 1. Batch-load the collections (with fields) of every content.
        self::loadCollections($contents);

        // 2. Scan meta values to collect media and relation references.
        $mediaRefs = [];    // projectId => [mediaId, ...]
        $relationRefs = []; // projectId => [collectionId => [contentId, ...]]
        $nextBatch = [];

        foreach ($contents as $content) {
            if (! $content instanceof Content || ! $content->project_id || $content->collection_id === null) {
                continue;
            }

            $collection = self::collectionFor($content->collection_id);
            if (! $collection) {
                continue;
            }

            $fields = $collection->fields->keyBy('name');

            foreach ($content->meta as $m) {
                $field = $fields->get($m->field_name);
                if (! $field || $m->value === null || $m->value === '') {
                    continue;
                }

                $options = json_decode($field->options);
                if (! $options) {
                    continue;
                }

                if ($field->type === 'media' && isset($options->media->type)) {
                    if ((int) $options->media->type === 1) {
                        $mediaRefs[$content->project_id][] = (int) $m->value;
                    } else {
                        foreach (explode(',', $m->value) as $id) {
                            if (is_numeric($id)) {
                                $mediaRefs[$content->project_id][] = (int) $id;
                            }
                        }
                    }
                } elseif ($field->type === 'relation' && isset($options->relation->collection)) {
                    $targetCollection = (int) $options->relation->collection;
                    if ((int) ($options->relation->type ?? 1) === 1) {
                        $relationRefs[$content->project_id][$targetCollection][] = (int) $m->value;
                    } else {
                        foreach (explode(',', $m->value) as $id) {
                            if (is_numeric($id)) {
                                $relationRefs[$content->project_id][$targetCollection][] = (int) $id;
                            }
                        }
                    }
                }
            }
        }

        // 3. Batch-load all referenced media.
        foreach ($mediaRefs as $projectId => $ids) {
            self::loadMedia((int) $projectId, array_values(array_unique($ids)));
        }

        // 4. Batch-load all referenced content (published only); the rows
        //    become the next recursion batch (relations of relations).
        foreach ($relationRefs as $projectId => $collections) {
            foreach ($collections as $collectionId => $ids) {
                foreach (self::loadRelations((int) $projectId, (int) $collectionId, array_values(array_unique($ids))) as $row) {
                    $nextBatch[] = $row;
                }
            }
        }

        // 5. Recurse one level for relations of relations.
        if ($nextBatch) {
            self::loadFor($nextBatch, $depth + 1);
        }
    }

    /**
     * @param array<int, Content> $contents
     * @return void
     */
    private static function loadCollections(array $contents): void
    {
        $ids = [];
        foreach ($contents as $content) {
            if ($content instanceof Content && $content->collection_id !== null) {
                $ids[] = (int) $content->collection_id;
            }
        }

        $missing = [];
        foreach (array_unique($ids) as $id) {
            if (! array_key_exists($id, self::$collectionMap)) {
                $missing[] = $id;
            }
        }

        if (! $missing) {
            return;
        }

        foreach (Collection::with('fields')->whereIn('id', $missing)->get() as $collection) {
            self::$collectionMap[$collection->id] = $collection;
        }
    }

    /**
     * @param int $projectId
     * @param array<int> $ids
     * @return void
     */
    private static function loadMedia(int $projectId, array $ids): void
    {
        $missing = [];
        foreach ($ids as $id) {
            if (! array_key_exists($projectId.':'.$id, self::$mediaMap)) {
                $missing[] = $id;
            }
        }

        if (! $missing) {
            return;
        }

        $found = [];
        // `project` is eager loaded because Media appends full_url/full_url_thumb,
        // both of which resolve the owning project's uuid.
        foreach (Media::with('project')->where('project_id', $projectId)->whereIn('id', $missing)->get() as $media) {
            self::$mediaMap[$projectId.':'.$media->id] = $media;
            $found[$media->id] = true;
        }

        // Record misses so dangling ids are not re-queried.
        foreach ($missing as $id) {
            if (! isset($found[$id])) {
                self::$mediaMap[$projectId.':'.$id] = null;
            }
        }
    }

    /**
     * Load related content (published only) for a project/collection and
     * return the loaded models so the caller can recurse into them.
     *
     * @param int $projectId
     * @param int $collectionId
     * @param array<int> $ids
     * @return array<int, Content>
     */
    private static function loadRelations(int $projectId, int $collectionId, array $ids): array
    {
        $missing = [];
        foreach ($ids as $id) {
            if (! array_key_exists($projectId.':'.$id, self::$relationMap)) {
                $missing[] = $id;
            }
        }

        if (! $missing) {
            // Everything is cached — hand back the models for recursion.
            $rows = [];
            foreach ($ids as $id) {
                $row = self::$relationMap[$projectId.':'.$id] ?? null;
                if ($row instanceof Content) {
                    $rows[] = $row;
                }
            }

            return $rows;
        }

        $rows = Content::with(['meta', 'collection.fields'])
            ->where('project_id', $projectId)
            ->where('collection_id', $collectionId)
            ->whereNotNull('published_at')
            ->whereIn('id', $missing)
            ->select(['id', 'project_id', 'collection_id', 'locale', 'created_at', 'updated_at', 'published_at'])
            ->get();

        $found = [];
        foreach ($rows as $row) {
            self::$relationMap[$projectId.':'.$row->id] = $row;
            $found[$row->id] = true;
        }

        foreach ($missing as $id) {
            if (! isset($found[$id])) {
                self::$relationMap[$projectId.':'.$id] = null;
            }
        }

        return $rows->all();
    }

    // ------------------------------------------------------------------
    // Lazy accessors. Used by ContentResource/MediaResource: return the
    // preloaded row, or fall back to a single query (and cache it).
    // ------------------------------------------------------------------

    /**
     * @param int $collectionId
     * @return \App\Models\Collection|null
     */
    public static function collectionFor(int $collectionId): ?Collection
    {
        if (! array_key_exists($collectionId, self::$collectionMap)) {
            self::$collectionMap[$collectionId] = Collection::with('fields')->find($collectionId);
        }

        return self::$collectionMap[$collectionId];
    }

    /**
     * @param int $projectId
     * @param int $mediaId
     * @return \App\Models\Media|null
     */
    public static function mediaFor(int $projectId, int $mediaId): ?Media
    {
        $key = $projectId.':'.$mediaId;

        if (! array_key_exists($key, self::$mediaMap)) {
            self::$mediaMap[$key] = Media::with('project')->where('project_id', $projectId)->where('id', $mediaId)->first();
        }

        return self::$mediaMap[$key];
    }

    /**
     * @param int $projectId
     * @param int $contentId
     * @return \App\Models\Content|null
     */
    public static function relationFor(int $projectId, int $contentId): ?Content
    {
        $key = $projectId.':'.$contentId;

        if (! array_key_exists($key, self::$relationMap)) {
            self::$relationMap[$key] = Content::with(['meta', 'collection.fields'])
                ->where('project_id', $projectId)
                ->whereNotNull('published_at')
                ->where('id', $contentId)
                ->select(['id', 'project_id', 'collection_id', 'locale', 'created_at', 'updated_at', 'published_at'])
                ->first();
        }

        return self::$relationMap[$key];
    }

    /**
     * @param int $projectId
     * @return \App\Models\Project|null
     */
    public static function projectFor(int $projectId): ?Project
    {
        if (! array_key_exists($projectId, self::$projectMap)) {
            self::$projectMap[$projectId] = Project::find($projectId);
        }

        return self::$projectMap[$projectId];
    }

    /**
     * @param \App\Models\Content|\Traversable|array|null $contents
     * @return array<int, Content>
     */
    private static function normalize($contents): array
    {
        if ($contents instanceof Content) {
            return [$contents];
        }

        if (is_array($contents)) {
            return array_values($contents);
        }

        if ($contents instanceof \Traversable) {
            return array_values(iterator_to_array($contents, false));
        }

        return [];
    }
}
