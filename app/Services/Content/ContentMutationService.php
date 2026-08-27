<?php

namespace App\Services\Content;

use App\Models\Collection;
use App\Models\Content;
use App\Models\ContentMeta;
use App\Models\Project;

/**
 * Shared content CRUD operations consumed by both the admin panel and
 * the public API. This service is a pure domain object — it returns
 * models / booleans, never HTTP responses.
 */
class ContentMutationService
{
    protected ContentValidationService $validation;

    public function __construct(ContentValidationService $validation)
    {
        $this->validation = $validation;
    }

    /**
     * Create a Content row and its meta rows.
     *
     * @return Content  The persisted (but not preloaded) content.
     */
    public function create(
        Project $project,
        Collection $collection,
        array $data,
        ?string $locale = null,
        bool $published = false,
        ?int $createdBy = null
    ): Content {
        $content = Content::create([
            'project_id'    => $project->id,
            'collection_id' => $collection->id,
            'locale'        => $locale ?? $project->default_locale,
            'created_by'    => $createdBy,
            'published_at'  => $published ? now() : null,
            'published_by'  => $published ? $createdBy : null,
        ]);

        $this->upsertMeta($content, $collection->fields, $data);

        return $content;
    }

    /**
     * Update an existing content's meta.
     *
     * $scheduledAt is the *only* place that resolves the publish-state
     * machine.  Callers simply pass the raw request value:
     *  - null        → not sent — keep existing schedule untouched
     *  - ''          → explicitly cleared
     *  - '2026-…'    → set to this value (Carbon-parsed)
     *
     * @param string|null $scheduledAtRaw  Raw request value or null when absent.
     * @return Content  The updated (but not preloaded) content.
     */
    public function update(
        Content $content,
        Collection $collection,
        array $data,
        ?string $locale = null,
        bool $published = false,
        ?int $updatedBy = null,
        ?string $scheduledAtRaw = null,
        ?string $publishedAtRaw = null,
        array $deletedMetaIds = []
    ): Content {
        $updatePayload = [
            'locale'     => $locale ?? $content->locale,
            'updated_by' => $updatedBy,
        ];

        if ($published) {
            $updatePayload['published_at'] = now();
            $updatePayload['published_by'] = $updatedBy;
            $updatePayload['scheduled_at'] = null;
        } else {
            // Unpublish unless the caller is just saving a draft while
            // leaving the publish state untouched (no publish/schedule keys).
            $updatePayload['published_at'] = null;
            $updatePayload['published_by'] = null;

            if ($scheduledAtRaw !== null) {
                $updatePayload['scheduled_at'] = $scheduledAtRaw === ''
                    ? null
                    : \Illuminate\Support\Carbon::parse($scheduledAtRaw);
            }
            // else: $scheduledAtRaw === null → keep existing schedule
        }

        $content->update($updatePayload);

        // Clean up meta rows that were flagged for deletion (repeatable fields).
        if (! empty($deletedMetaIds)) {
            ContentMeta::where('content_id', $content->id)
                ->whereIn('id', $deletedMetaIds)
                ->forceDelete();
        }

        $this->upsertMeta($content, $collection->fields, $data, true);

        return $content;
    }

    /**
     * Upsert the EAV meta rows for a content item.
     *
     * @param Content $content
     * @param iterable $fields  CollectionField models (with decoded options).
     * @param array   $data     Field-name → raw-value payload.
     * @param bool    $isUpdate When true, existing rows are updated
     *                          instead of blindly created.
     */
    public function upsertMeta(
        Content $content,
        iterable $fields,
        array $data,
        bool $isUpdate = false
    ): void {
        // Build a name → type/options lookup from the collection fields.
        // Decode JSON options (they are stored as raw strings on the model).
        $fieldMap = [];
        foreach ($fields as $field) {
            $fieldMap[$field->name] = [
                'type'    => $field->type,
                'options' => is_string($field->options) ? json_decode($field->options) : ($field->options ?? null),
            ];
        }

        foreach ($data as $key => $value) {
            $meta = $fieldMap[$key] ?? null;
            if ($meta === null) {
                continue;
            }

            // Password fields on update: skip the empty() guard so the
            // keep-old-hash-on-empty logic can actually run.
            $isPassword = $meta['type'] === 'password';
            if (! $isPassword && empty($value) && $value !== '0' && $value !== 0) {
                continue;
            }

            // Password fields on update: keep the old hash when value is empty.
            $existingPw = null;
            if ($isPassword && $isUpdate) {
                $existing = ContentMeta::where('content_id', $content->id)
                    ->where('field_name', $key)->first();
                $existingPw = $existing?->value;
            }

            $sanitised = $this->validation->sanitizeFieldValue(
                $meta['type'],
                $value,
                $meta['options'],
                isUpdate: $isUpdate,
                existingPasswordValue: $existingPw
            );

            // Repeatable fields: each element is its own ContentMeta row.
            if (isset($meta['options']->repeatable) && $meta['options']->repeatable) {
                foreach ($value as $rfItem) {
                    if (empty($rfItem['value']) && $rfItem['value'] !== '0' && $rfItem['value'] !== 0) {
                        continue;
                    }

                    if ($isUpdate && ! empty($rfItem['id'])) {
                        // Try to update existing repeatable row.
                        $existing = ContentMeta::where('id', $rfItem['id'])
                            ->where('field_name', $key)
                            ->first();
                        if ($existing) {
                            $existing->update(['value' => $rfItem['value']]);
                            continue;
                        }
                    }

                    // Create new repeatable row.
                    ContentMeta::create([
                        'project_id'    => $content->project_id,
                        'collection_id' => $content->collection_id,
                        'content_id'    => $content->id,
                        'field_name'    => $key,
                        'value'         => $rfItem['value'],
                    ]);
                }
                continue;
            }

            // Non-repeatable field: upsert or create.
            if ($isUpdate) {
                $existing = ContentMeta::where('content_id', $content->id)
                    ->where('field_name', $key)
                    ->first();

                if ($existing) {
                    $existing->update(['value' => $sanitised]);
                } else {
                    ContentMeta::create([
                        'project_id'    => $content->project_id,
                        'collection_id' => $content->collection_id,
                        'content_id'    => $content->id,
                        'field_name'    => $key,
                        'value'         => $sanitised,
                    ]);
                }
            } else {
                ContentMeta::create([
                    'project_id'    => $content->project_id,
                    'collection_id' => $content->collection_id,
                    'content_id'    => $content->id,
                    'field_name'    => $key,
                    'value'         => $sanitised,
                ]);
            }
        }
    }

    /**
     * Delete a content record and all its meta rows.
     */
    public function delete(Content $content): bool
    {
        $content->meta()->delete();
        return (bool) $content->delete();
    }
}