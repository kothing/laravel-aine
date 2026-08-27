<?php

namespace App\Http\Controllers\Admin;

use App\Aine\AuditLogger;
use App\Aine\HtmlSanitizer;
use App\Events\ContentCreated;
use App\Events\ContentDeleted;
use App\Events\ContentPublished;
use App\Events\ContentRestored;
use App\Events\ContentTrashed;
use App\Events\ContentUnpublished;
use App\Events\ContentUpdated;
use App\Http\Controllers\Controller;
use App\Models\Collection;
use App\Models\Content;
use App\Models\ContentMeta;
use App\Models\ContentRevision;
use App\Models\Form;
use App\Models\Media;
use App\Models\Project;
use App\Models\User;
use App\Notifications\ContentNotification;
use App\Services\Content\ContentMutationService;
use App\Services\Content\ContentValidationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Validator;
use Spatie\Permission\Exceptions\UnauthorizedException;

class ContentController extends Controller
{
    protected ContentValidationService $validation;
    protected ContentMutationService  $mutations;

    public function __construct()
    {
        $this->validation = new ContentValidationService();
        $this->mutations  = new ContentMutationService($this->validation);
    }

    // =================================================================
    // Authorisation helpers
    // =================================================================

    /** Assert the current user is admin or editor of the project. */
    private function authorizeEditor(Project $project): void
    {
        /** @var User $user */
        $user = Auth::user();
        if (! $user->isSuperAdmin()
            && ! $user->hasRole('admin' . $project->id)
            && ! $user->hasRole('editor' . $project->id)) {
            throw UnauthorizedException::forRoles(['admin' . $project->id]);
        }
    }

    /** Assert the current user is admin of the project (no editors). */
    private function authorizeAdmin(Project $project): void
    {
        /** @var User $user */
        $user = Auth::user();
        if (! $user->isSuperAdmin()
            && ! $user->hasRole('admin' . $project->id)) {
            throw UnauthorizedException::forRoles(['admin' . $project->id]);
        }
    }

    // =================================================================
    // Notifications (best-effort — never fail the request)
    // =================================================================

    /**
     * Notify project admins about a content action, throttled to one
     * notification per {project, action, collection} tuple per 30 seconds.
     *
     * This prevents notification floods during bulk imports / scripted batch
     * operations that create or update many items in quick succession.
     */
    private function notifyProjectAdmins(int $projectId, array $payload): void
    {
        try {
            $action = $payload['action'] ?? 'unknown';
            $collectionId = $payload['collection_id'] ?? 0;
            $throttleKey = "notification_throttle:{$projectId}:{$action}:{$collectionId}";

            if (Cache::has($throttleKey)) {
                return;
            }

            Cache::put($throttleKey, true, now()->addSeconds(30));

            $admins = User::whereHas('roles', function ($q) use ($projectId) {
                $q->whereIn('name', ['super_admin', 'admin' . $projectId]);
            })->get();

            if ($admins->isNotEmpty()) {
                Notification::send($admins, new ContentNotification($payload));
            }
        } catch (\Throwable $e) {
            // Best-effort only.
        }
    }

    // =================================================================
    // Revision helper
    // =================================================================

    private function createRevision(Content $content, string $note = 'Updated'): void
    {
        $data = [];
        foreach (ContentMeta::where('content_id', $content->id)->get() as $meta) {
            $data[$meta->field_name] = $meta->value;
        }
        ContentRevision::create([
            'project_id'    => $content->project_id,
            'collection_id' => $content->collection_id,
            'content_id'    => $content->id,
            'locale'        => $content->locale,
            'data'          => $data,
            'note'          => $note,
            'created_by'    => Auth::id(),
        ]);
    }

    // =================================================================
    // Decode field JSON metadata (shared by validation + mutation)
    // =================================================================

    private function decodeFields($fields): array
    {
        $out = [];
        foreach ($fields as $field) {
            $f = clone $field;
            $f->validations = json_decode($f->validations);
            $f->options     = json_decode($f->options);
            $out[] = $f;
        }
        return $out;
    }

    // =================================================================
    // Project
    // =================================================================

    public function project($id)
    {
        $project = Project::with('collections')->findOrFail($id);
        $this->authorizeEditor($project);
        return $project;
    }

    // =================================================================
    // Index / list
    // =================================================================

    public function index($project_id, $collection_id, Request $request)
    {
        $project = Project::with('collections')->findOrFail($project_id);
        $this->authorizeEditor($project);

        $collection = Collection::with(['fields'])
            ->where('project_id', $project_id)->where('id', $collection_id)->firstOrFail();

        foreach ($collection->fields as $field) {
            $field->validations = json_decode($field->validations);
            $field->options     = json_decode($field->options);
        }

        $data['collection'] = $collection;

        $content_items = Content::with(['meta', 'form'])
            ->where('collection_id', $collection_id);

        if ($request->get('search') != '') {
            $q    = $request->get('search');
            $meta = ContentMeta::where('value', 'LIKE', "%{$q}%")->get(['content_id']);
            $content_items->whereIn('id', $meta);
        }

        $orderBy  = $request->get('orderBy', 'created_at');
        $criteria = $request->get('cr', 'ASC');
        $each     = $request->get('each', 15);

        if ($request->get('sbm')) {
            $content_items->orderBy(
                ContentMeta::select('value')->whereColumn('content_meta.content_id', 'content.id')
                    ->where('field_name', $orderBy)->latest()->take(1),
                $criteria
            );
        } else {
            if (in_array($orderBy, ['created_by', 'updated_by', 'published_by'])) {
                $content_items->orderBy(
                    User::select('email')->whereColumn('users.id', 'content.' . $orderBy)->latest()->take(1),
                    $criteria
                );
            } else {
                $content_items->orderBy($orderBy, $criteria);
            }
        }

        $count1 = clone $content_items; $count2 = clone $content_items;
        $count3 = clone $content_items; $count4 = clone $content_items;

        $getItems = $request->get('getItems');
        if ($getItems === 'all')       $content_items = $content_items->whereNull('draft_parent_id')->paginate($each);
        elseif ($getItems === 'published') $content_items = $content_items->whereNotNull('published_at')->whereNull('draft_parent_id')->paginate($each);
        elseif ($getItems === 'draft')     $content_items = $content_items->whereNull('published_at')->whereNull('draft_parent_id')->paginate($each);
        elseif ($getItems === 'trashed')   $content_items = $content_items->with(['meta' => fn ($q) => $q->withTrashed()])->onlyTrashed()->paginate($each);
        else                               $content_items = $content_items->paginate($each);

        // Batch-load users to avoid N+1.
        $userIds = collect($content_items->items())
            ->flatMap(fn ($c) => array_filter([$c->created_by, $c->updated_by, $c->published_by]))
            ->unique()->values();
        $users = $userIds->isEmpty() ? collect() : User::whereIn('id', $userIds)->get()->keyBy('id');

        // Preload draft-branch parent IDs so has_pending_draft is free.
        $itemIds = collect($content_items->items())->pluck('id')->filter()->unique()->values();
        $draftParentIds = $itemIds->isNotEmpty()
            ? Content::whereIn('draft_parent_id', $itemIds)
                ->whereNull('deleted_at')
                ->pluck('draft_parent_id')
                ->unique()
            : collect();

        foreach ($content_items as $c) {
            $c->created_by   = $users->get($c->created_by);
            $c->updated_by   = $users->get($c->updated_by);
            $c->published_by = $users->get($c->published_by);
            $c->has_pending_draft = $c->isPublished() && $draftParentIds->contains($c->id);
        }

        $data['content']     = $content_items;
        $data['totalCount']  = $count1->whereNull('draft_parent_id')->count();
        $data['published']   = $count2->whereNotNull('published_at')->whereNull('draft_parent_id')->count();
        $data['draft']       = $count3->whereNull('published_at')->whereNull('draft_parent_id')->count();
        $data['trashed']     = $count4->onlyTrashed()->count();
        $data['project']     = $project;
        $data['forms']       = Form::where('project_id', $project->id)->where('collection_id', $collection_id)->count();

        return $data;
    }

    // =================================================================
    // New / Edit (form endpoints)
    // =================================================================

    public function new($project_id, $collection_id)
    {
        $project = Project::with('collections')->findOrFail($project_id);

        $project->s3 = config('filesystems.disks.s3.key')
            && config('filesystems.disks.s3.secret')
            && config('filesystems.disks.s3.region')
            && config('filesystems.disks.s3.bucket');

        $this->authorizeEditor($project);

        $collection = Collection::with(['fields'])
            ->where('project_id', $project->id)->where('id', $collection_id)->firstOrFail();

        return ['project' => $project, 'collection' => $collection];
    }

    public function edit($project_id, $collection_id, $content_id)
    {
        $project = Project::with('collections')->findOrFail($project_id);
        $this->authorizeEditor($project);

        $collection = Collection::with(['fields'])
            ->where('project_id', $project->id)->where('id', $collection_id)->firstOrFail();

        $content = Content::with('meta')
            ->where('project_id', $project->id)
            ->where('collection_id', $collection->id)
            ->where('id', $content_id)->firstOrFail();

        // If this content has a pending draft branch, transparently redirect
        // the editor to the draft so they always work on the latest version.
        if ($content->isPublished() && $content->hasPendingDraft()) {
            $draft = $content->draftChild()->with('meta')->first();
            $content = $draft;
        }

        return ['project' => $project, 'collection' => $collection, 'content' => $content];
    }

    // =================================================================
    // Store (create)
    // =================================================================

    public function store($project_id, $collection_id, Request $request)
    {
        $project = Project::findOrFail($project_id);
        $this->authorizeEditor($project);

        $collection = Collection::with(['fields'])
            ->where('project_id', $project->id)->where('id', $collection_id)->firstOrFail();

        $fields = $this->decodeFields($collection->fields);

        // Build & run validation (prefix "data" → admin UI shape).
        [$rules, $messages] = $this->validation->buildFieldValidationRules($fields, 'data');
        ContentValidationService::registerCustomValidators();
        Validator::make($request->all(), $rules, $messages)->validate();

        // Unique validation.
        $input = $request->get('data', []);
        if ($uniqErrors = $this->validation->validateUniqueFields($fields, $input, $collection->id)) {
            return response($uniqErrors, 422);
        }

        // Workflow gate.
        if ($project->workflow_enabled && $request->get('published')) {
            return $this->workflowPublishBlocked();
        }

        // Pre-process richtext values before mutation.
        $processedData = $this->preProcessData($input, $collection->fields);

        $content = $this->mutations->create(
            $project, $collection, $processedData,
            $request->get('locale'),
            published: (bool) $request->get('published'),
            createdBy: Auth::id()
        );

        // Admin side-effects.
        $this->createRevision($content, 'Created');
        AuditLogger::log('create', 'content', $content->id, 'Content #' . $content->id, [
            'collection_id' => $collection->id,
            'locale'        => $content->locale,
            'published'     => (bool) $request->get('published'),
        ], $project->id);

        ContentCreated::dispatch(['source' => 'User', 'content' => $content]);

        return response($content, 200);
    }

    // =================================================================
    // Update
    // =================================================================

    public function update($project_id, $collection_id, $content_id, Request $request)
    {
        $project = Project::findOrFail($project_id);
        $this->authorizeEditor($project);

        $collection = Collection::with(['fields'])
            ->where('project_id', $project->id)->where('id', $collection_id)->firstOrFail();

        $content = Content::with('meta')
            ->where('project_id', $project->id)
            ->where('collection_id', $collection->id)
            ->where('id', $content_id)->firstOrFail();

        $wasPublished = $content->isPublished();
        $fields = $this->decodeFields($collection->fields);

        // Build & run validation.
        [$rules, $messages] = $this->validation->buildFieldValidationRules($fields, 'data');
        ContentValidationService::registerCustomValidators();
        Validator::make($request->all(), $rules, $messages)->validate();

        // Unique validation (exclude current content; for draft branches
        // the unique check is against the draft's own meta, which is fine
        // because the main row's meta is untouched until publish).
        $input = $request->get('data', []);
        if ($uniqErrors = $this->validation->validateUniqueFields($fields, $input, $collection->id, $content->id)) {
            return response($uniqErrors, 422);
        }

        // Pre-process richtext values.
        $processedData = $this->preProcessData($input, $collection->fields);

        // ─────────────────────────────────────────────────────
        // Draft-branch flow: editing published content as draft
        // ─────────────────────────────────────────────────────
        $isDraftSave = ! (bool) $request->get('published');

        if ($isDraftSave && $wasPublished && ! $content->isDraftBranch()) {
            // The editor wants to save a draft of a live piece of content
            // without unpublishing the current public version.
            // → Clone a draft branch and apply changes there instead.
            $draft = $content->draftChild()->first();
            if (! $draft) {
                $draft = $this->mutations->createDraftBranch($content, Auth::id());
            }

            $this->mutations->update(
                $draft, $collection, $processedData,
                $request->get('locale'),
                published: false,
                updatedBy: Auth::id(),
                scheduledAtRaw: $request->has('scheduled_at') ? $request->get('scheduled_at') : null,
                deletedMetaIds: $request->get('deleted', [])
            );

            $this->createRevision($draft, 'Draft updated');

            AuditLogger::log('update', 'content', $draft->id,
                'Draft branch #' . $draft->id . ' (of #' . $content_id . ')',
                ['collection_id' => $collection->id, 'draft_parent_id' => $content_id],
                $project->id);

            ContentUpdated::dispatch(['source' => 'User', 'content' => $draft]);

            return response($draft, 200);
        }

        // ─────────────────────────────────────────────────────
        // Normal flow (direct publish, or editing an existing
        // draft that was never published, or editing a draft
        // branch that was already created).
        // ─────────────────────────────────────────────────────

        // If this IS a draft branch and the editor is trying to publish
        // directly via the update endpoint, redirect to the merge flow
        // instead of independently publishing the branch.
        if ($content->isDraftBranch() && $request->get('published')) {
            // Workflow gate.
            if ($project->workflow_enabled) {
                return $this->workflowPublishBlocked();
            }

            $draftId = $content->id;

            $this->mutations->update(
                $content, $collection, $processedData,
                $request->get('locale'),
                published: false,
                updatedBy: Auth::id(),
                scheduledAtRaw: $request->has('scheduled_at') ? $request->get('scheduled_at') : null,
                deletedMetaIds: $request->get('deleted', [])
            );

            $main = $this->mutations->publishDraftBranch($content, Auth::id());
            $this->createRevision($main, 'Published from draft branch');
            ContentPublished::dispatch(['source' => 'User', 'content' => $main]);

            AuditLogger::log('publish_draft', 'content', $main->id, 'Content #' . $main->id, [
                'collection_id'  => $collection->id,
                'draft_branch_id' => $draftId,
            ], $project->id);

            $this->notifyProjectAdmins($project->id, [
                'action' => 'publish', 'entity_label' => 'Content #' . $main->id,
                'collection_id' => $collection_id, 'content_id' => $main->id,
            ]);

            return response($main, 200);
        }

        // Workflow gate.
        if ($project->workflow_enabled && $request->get('published')) {
            return $this->workflowPublishBlocked();
        }

        // Publish event.
        if (! $wasPublished && $request->get('published')) {
            ContentPublished::dispatch(['source' => 'User', 'content' => $content]);
        }

        $this->mutations->update(
            $content, $collection, $processedData,
            $request->get('locale'),
            published: (bool) $request->get('published'),
            updatedBy: Auth::id(),
            scheduledAtRaw: $request->has('scheduled_at') ? $request->get('scheduled_at') : null,
            deletedMetaIds: $request->get('deleted', [])
        );

        $action = $request->get('published') ? 'publish' : 'update';
        if ($isDraftSave && $wasPublished) {
            $action = 'unpublish';
            ContentUnpublished::dispatch(['source' => 'User', 'content' => $content]);
        }

        AuditLogger::log($action, 'content', $content->id, 'Content #' . $content->id, [
            'collection_id' => $collection->id,
            'scheduled_at'  => $request->has('scheduled_at') ? $request->get('scheduled_at') : null,
        ], $project->id);

        if (in_array($action, ['publish', 'unpublish'])) {
            $this->notifyProjectAdmins($project->id, [
                'action' => $action, 'entity_label' => 'Content #' . $content->id,
                'collection_id' => $collection->id, 'content_id' => $content->id,
            ]);
        }

        ContentUpdated::dispatch(['source' => 'User', 'content' => $content]);
        $this->createRevision($content, 'Updated');
    }

    // =================================================================
    // Data pre-processing (richtext sanitization for admin UI)
    // =================================================================

    private function preProcessData(array $data, $fields): array
    {
        $fieldMap = [];
        foreach ($fields as $f) {
            $fieldMap[$f->name] = ['type' => $f->type, 'options' => json_decode($f->options)];
        }

        foreach ($data as $key => &$value) {
            $meta = $fieldMap[$key] ?? null;
            if (! $meta) continue;

            // Richtext: sanitize HTML.
            if ($meta['type'] === 'richtext' && is_string($value)) {
                $value = HtmlSanitizer::sanitize($value);
            }

            // Enumeration (multiple) / Media / Relation: join array to string
            // (sanitizeFieldValue in the mutation service will do it for the
            // API path; do it here too so the admin path stays consistent
            // regardless of whether the service processes it).
            if (in_array($meta['type'], ['enumeration', 'media', 'relation']) && is_array($value)) {
                $value = implode(',', $value);
            }

            // JSON: let sanitizeFieldValue handle encoding — do NOT
            // pre-encode here or the service will double-encode it.
        }
        unset($value);
        return $data;
    }

    // =================================================================
    // Revisions
    // =================================================================

    public function revisions($project_id, $collection_id, $content_id)
    {
        $project = Project::findOrFail($project_id);
        $this->authorizeEditor($project);

        $content = Content::where('project_id', $project->id)
            ->where('collection_id', $collection_id)->where('id', $content_id)->firstOrFail();

        $revisions = ContentRevision::with('user:id,name,email')
            ->where('project_id', $project->id)
            ->where('collection_id', $collection_id)
            ->where('content_id', $content_id)
            ->orderByDesc('id')->get();

        return response()->json($revisions, 200);
    }

    public function restoreRevision($project_id, $collection_id, $content_id, $revision_id)
    {
        $project = Project::findOrFail($project_id);
        $this->authorizeEditor($project);

        $content  = Content::where('project_id', $project->id)
            ->where('collection_id', $collection_id)->where('id', $content_id)->firstOrFail();

        $revision = ContentRevision::where('project_id', $project->id)
            ->where('collection_id', $collection_id)->where('content_id', $content_id)
            ->where('id', $revision_id)->firstOrFail();

        $snapshot = is_array($revision->data) ? $revision->data : json_decode($revision->data, true);
        if (! is_array($snapshot)) {
            return response()->json(['message' => 'Revision data is corrupted.'], 422);
        }

        DB::transaction(function () use ($content, $snapshot, $revision) {
            $current = ContentMeta::where('content_id', $content->id)->pluck('id', 'field_name');
            foreach ($snapshot as $fieldName => $value) {
                if (isset($current[$fieldName])) {
                    ContentMeta::where('id', $current[$fieldName])->update(['value' => $value]);
                    unset($current[$fieldName]);
                } else {
                    ContentMeta::create([
                        'project_id'    => $content->project_id,
                        'collection_id' => $content->collection_id,
                        'content_id'    => $content->id,
                        'field_name'    => $fieldName,
                        'value'         => $value,
                    ]);
                }
            }
            if ($current->isNotEmpty()) {
                ContentMeta::whereIn('id', $current->values())->forceDelete();
            }
        });

        $this->createRevision($content, 'Restored from revision #' . $revision->id);

        ContentUpdated::dispatch(['source' => 'User', 'content' => $content]);

        AuditLogger::log('restore_revision', 'content', $content->id, 'Content #' . $content->id, [
            'collection_id' => $collection_id, 'revision_id' => $revision_id,
        ], $project->id);

        return response()->json(['message' => 'Revision restored successfully.'], 200);
    }

    // =================================================================
    // Export / Import
    // =================================================================

    public function exportContent($project_id, $collection_id, Request $request)
    {
        $project = Project::findOrFail($project_id);
        $this->authorizeEditor($project);

        $format = strtolower($request->get('format', 'json'));
        if (! in_array($format, ['json', 'csv'])) {
            return response()->json(['message' => 'Unsupported export format.'], 422);
        }

        $contents = Content::with('meta')
            ->where('project_id', $project->id)
            ->where('collection_id', $collection_id)->get();

        $rows = [];
        foreach ($contents as $content) {
            $data = [];
            foreach ($content->meta as $meta) {
                $data[$meta->field_name] = $meta->value;
            }
            $rows[] = [
                'locale'       => $content->locale,
                'published_at' => $content->published_at ? (string) $content->published_at : '',
                'data'         => $data,
            ];
        }

        $filename = 'content_' . $project->id . '_' . $collection_id . '.' . $format;

        AuditLogger::log('export', 'content', null, 'Exported collection #' . $collection_id, [
            'collection_id' => $collection_id, 'format' => $format, 'count' => count($rows),
        ], $project->id);

        if ($format === 'csv') {
            $allFields = collect($rows)->flatMap(fn ($r) => array_keys($r['data']))->unique()->values();
            $headers   = array_merge(['locale', 'published_at'], $allFields->toArray());

            $temp = fopen('php://temp', 'r+');
            fputcsv($temp, $headers);
            foreach ($rows as $row) {
                $line = [$row['locale'], $row['published_at']];
                foreach ($allFields as $f) $line[] = $row['data'][$f] ?? '';
                fputcsv($temp, $line);
            }
            rewind($temp);
            $csv = stream_get_contents($temp);
            fclose($temp);

            return response($csv, 200, [
                'Content-Type'        => 'text/csv; charset=UTF-8',
                'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            ]);
        }

        return response()->json($rows, 200, [
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }

    public function importContent($project_id, $collection_id, Request $request)
    {
        $project = Project::findOrFail($project_id);
        $this->authorizeEditor($project);

        if (! $request->hasFile('file')) {
            return response()->json(['message' => 'No file uploaded.'], 422);
        }

        $file      = $request->file('file');
        $extension = strtolower($file->getClientOriginalExtension());
        if (! in_array($extension, ['json', 'csv'])) {
            return response()->json(['message' => 'Only .json and .csv files are supported.'], 422);
        }

        if ($extension === 'csv') {
            $rows = $this->parseCsvFile($file->getRealPath());
        } else {
            $rows = json_decode(file_get_contents($file->getRealPath()), true);
            if (! is_array($rows)) {
                return response()->json(['message' => 'Invalid JSON file.'], 422);
            }
        }

        $created = 0; $errors = [];

        DB::beginTransaction();
        try {
            foreach ($rows as $index => $row) {
                if (! is_array($row)) {
                    $errors[] = 'Row ' . ($index + 1) . ': invalid row.';
                    continue;
                }
                $data = isset($row['data']) && is_array($row['data']) ? $row['data'] : $row;
                unset($data['locale'], $data['published_at'], $data['published']);

                $content = Content::create([
                    'project_id'    => $project->id,
                    'collection_id' => $collection_id,
                    'locale'        => $row['locale'] ?? 'en',
                    'published_at'  => ! empty($row['published_at']) ? $row['published_at']
                        : (! empty($row['published']) ? now() : null),
                    'published_by'  => ! empty($row['published_at']) || ! empty($row['published'])
                        ? Auth::id() : null,
                    'created_by' => Auth::id(),
                    'updated_by' => Auth::id(),
                ]);

                foreach ($data as $fieldName => $value) {
                    ContentMeta::create([
                        'project_id'    => $project->id,
                        'collection_id' => $collection_id,
                        'content_id'    => $content->id,
                        'field_name'    => $fieldName,
                        'value'         => $value,
                    ]);
                }

                $this->createRevision($content, 'Imported');
                ContentCreated::dispatch(['source' => 'Import', 'content' => $content]);
                AuditLogger::log('import', 'content', $content->id, 'Content #' . $content->id, [
                    'collection_id' => $collection_id, 'source' => 'Import',
                ], $project->id);
                $created++;
            }
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Import failed: ' . $e->getMessage()], 422);
        }

        AuditLogger::log('import', 'content', null, 'Imported ' . $created . ' content item(s)', [
            'collection_id' => $collection_id, 'created' => $created,
        ], $project->id);

        return response()->json([
            'message' => $created . ' content item(s) imported.',
            'created' => $created, 'errors' => $errors,
        ], 200);
    }

    private function parseCsvFile(string $path): array
    {
        $rows    = [];
        $handle  = fopen($path, 'r');
        $headers = null;

        while (($line = fgetcsv($handle)) !== false) {
            if ($headers === null) { $headers = $line; continue; }
            $row = [];
            foreach ($headers as $i => $header) {
                $row[$header] = $line[$i] ?? '';
            }
            $rows[] = $row;
        }
        fclose($handle);
        return $rows;
    }

    // =================================================================
    // Single-record actions (unpublish / trash / delete)
    // =================================================================

    /**
     * Publish a draft branch, merging its data back into the main row.
     */
    public function publishDraft($project_id, $collection_id, $content_id)
    {
        $project = Project::findOrFail($project_id);
        $this->authorizeEditor($project);

        $draft = Content::where('project_id', $project->id)
            ->where('collection_id', $collection_id)
            ->where('id', $content_id)->firstOrFail();

        if (! $draft->isDraftBranch()) {
            return response()->json([
                'success' => false, 'code' => 422,
                'message' => 'This content is not a draft branch.',
            ], 422);
        }

        // Workflow gate.
        if ($project->workflow_enabled) {
            return $this->workflowPublishBlocked();
        }

        $main = $this->mutations->publishDraftBranch($draft, Auth::id());

        $this->createRevision($main, 'Published from draft branch');
        ContentPublished::dispatch(['source' => 'User', 'content' => $main]);

        AuditLogger::log('publish_draft', 'content', $main->id, 'Content #' . $main->id, [
            'collection_id'  => $collection_id,
            'draft_branch_id' => $draft->id,
        ], $project->id);

        $this->notifyProjectAdmins($project->id, [
            'action' => 'publish', 'entity_label' => 'Content #' . $main->id,
            'collection_id' => $collection_id, 'content_id' => $main->id,
        ]);

        return response($main, 200);
    }

    /**
     * Discard a draft branch without publishing.
     */
    public function discardDraft($project_id, $collection_id, $content_id)
    {
        $project = Project::findOrFail($project_id);
        $this->authorizeEditor($project);

        $draft = Content::where('project_id', $project->id)
            ->where('collection_id', $collection_id)
            ->where('id', $content_id)->firstOrFail();

        if (! $draft->isDraftBranch()) {
            return response()->json([
                'success' => false, 'code' => 422,
                'message' => 'This content is not a draft branch.',
            ], 422);
        }

        $mainId = $draft->draft_parent_id;
        $draftId = $draft->id;
        $this->mutations->discardDraftBranch($draft);

        AuditLogger::log('discard_draft', 'content', $mainId, 'Content #' . $mainId, [
            'collection_id'   => $collection_id,
            'discarded_draft' => $draftId,
        ], $project->id);

        return response()->json(['success' => true, 'message' => 'Draft discarded.'], 200);
    }

    public function unpublish($project_id, $collection_id, $content_id)
    {
        $project = Project::findOrFail($project_id);
        $this->authorizeEditor($project);

        $content = Content::where('project_id', $project->id)
            ->where('collection_id', $collection_id)->where('id', $content_id)->firstOrFail();

        $content->published_at = null;
        $content->published_by = null;
        $content->save();

        ContentUnpublished::dispatch(['source' => 'User', 'content' => $content]);
        AuditLogger::log('unpublish', 'content', $content->id, 'Content #' . $content->id, [
            'collection_id' => $collection_id,
        ], $project->id);
        $this->notifyProjectAdmins($project->id, [
            'action' => 'unpublish', 'entity_label' => 'Content #' . $content->id,
            'collection_id' => $collection_id, 'content_id' => $content->id,
        ]);

        return response([], 200);
    }

    public function moveToTrash($project_id, $collection_id, $content_id)
    {
        $project = Project::findOrFail($project_id);
        $this->authorizeEditor($project);

        $content = Content::where('project_id', $project->id)
            ->where('collection_id', $collection_id)->where('id', $content_id)->firstOrFail();

        // If trashing a draft branch, also clean up the parent's pending-draft state.
        // If trashing a main row, also remove any pending draft branch.
        if ($content->isDraftBranch()) {
            // No cascade needed — the parent just loses the pending draft indicator.
        } else {
            // Clean up any draft branch that belongs to this main row.
            if ($draft = $content->draftChild()->first()) {
                $draft->meta()->forceDelete();
                $draft->forceDelete();
            }
        }

        $content->meta()->delete();

        if ($content->delete()) {
            ContentTrashed::dispatch(['source' => 'User', 'content' => $content]);
            AuditLogger::log('trash', 'content', $content->id, 'Content #' . $content->id, [
                'collection_id' => $collection_id,
            ], $project->id);
            $this->notifyProjectAdmins($project->id, [
                'action' => 'trash', 'entity_label' => 'Content #' . $content->id,
                'collection_id' => $collection_id, 'content_id' => $content->id,
            ]);
            return response([], 200);
        }
        return response([], 404);
    }

    public function delete($project_id, $collection_id, $content_id)
    {
        $project = Project::findOrFail($project_id);
        $this->authorizeAdmin($project);

        $content = Content::withTrashed()->where('project_id', $project->id)
            ->where('collection_id', $collection_id)->where('id', $content_id)->firstOrFail();

        // Clean up any orphaned draft branch.
        if (! $content->isDraftBranch() && $draft = $content->draftChild()->withTrashed()->first()) {
            $draft->meta()->forceDelete();
            $draft->forceDelete();
        }

        $content->meta()->forceDelete();

        if ($content->forceDelete()) {
            ContentDeleted::dispatch(['source' => 'User', 'content' => [
                'project_id' => $project->id, 'collection_id' => $collection_id, 'item_id' => $content_id,
            ]]);
            AuditLogger::log('delete', 'content', $content_id, 'Content #' . $content_id, [
                'collection_id' => $collection_id,
            ], $project->id);
            $this->notifyProjectAdmins($project->id, [
                'action' => 'delete', 'entity_label' => 'Content #' . $content_id,
                'collection_id' => $collection_id, 'content_id' => $content_id,
            ]);
            return response([], 200);
        }
        return response([], 404);
    }

    // =================================================================
    // Get selected records / files
    // =================================================================

    public function getSelectedRecords($project_id, Request $request)
    {
        $project = Project::findOrFail($project_id);
        $this->authorizeEditor($project);

        $selected      = $request->get('data')['selected'];
        $collection_id = $request->get('data')['collection_id'];

        return [
            'collection' => Collection::with('fields')
                ->where('project_id', $project->id)->where('id', $collection_id)->first(),
            'content'    => Content::with(['meta'])
                ->where('project_id', $project->id)->whereIn('id', $selected)->get(),
        ];
    }

    public function getSelectedFiles($project_id, Request $request)
    {
        $project = Project::findOrFail($project_id);
        $this->authorizeEditor($project);
        return Media::where('project_id', $project->id)->whereIn('id', $request->get('data'))->get();
    }

    // =================================================================
    // Bulk operations
    // =================================================================

    public function publishSelected($project_id, $collection_id, Request $request)
    {
        $project = Project::findOrFail($project_id);
        $this->authorizeEditor($project);

        // Workflow gate — publishing is gated when workflow is enabled.
        if ($project->workflow_enabled) {
            return $this->workflowPublishBlocked();
        }

        foreach ($request->get('selected') as $id) {
            $content = Content::where('project_id', $project->id)
                ->where('collection_id', $collection_id)
                ->whereNull('draft_parent_id')
                ->where('id', $id)->first();
            if (! $content || $content->published_at !== null) continue;

            $content->published_at = now();
            $content->published_by = Auth::id();
            $content->save();

            ContentPublished::dispatch(['source' => 'User', 'content' => $content]);
            AuditLogger::log('publish', 'content', $content->id, 'Content #' . $content->id, [
                'collection_id' => $collection_id,
            ], $project->id);
            $this->notifyProjectAdmins($project->id, [
                'action' => 'publish', 'entity_label' => 'Content #' . $content->id,
                'collection_id' => $collection_id, 'content_id' => $content->id,
            ]);
        }
        return response([], 200);
    }

    public function unPublishSelected($project_id, $collection_id, Request $request)
    {
        $project = Project::findOrFail($project_id);
        $this->authorizeEditor($project);

        foreach ($request->get('selected') as $id) {
            $content = Content::where('project_id', $project->id)
                ->where('collection_id', $collection_id)->where('id', $id)->first();
            if (! $content || $content->published_at === null) continue;

            $content->published_at = null;
            $content->published_by = null;
            $content->save();

            ContentUnpublished::dispatch(['source' => 'User', 'content' => $content]);
            AuditLogger::log('unpublish', 'content', $content->id, 'Content #' . $content->id, [
                'collection_id' => $collection_id,
            ], $project->id);
            $this->notifyProjectAdmins($project->id, [
                'action' => 'unpublish', 'entity_label' => 'Content #' . $content->id,
                'collection_id' => $collection_id, 'content_id' => $content->id,
            ]);
        }
        return response([], 200);
    }

    public function moveToTrashSelected($project_id, $collection_id, Request $request)
    {
        $project = Project::findOrFail($project_id);
        $this->authorizeEditor($project);

        foreach ($request->get('selected') as $id) {
            $content = Content::where('project_id', $project->id)
                ->where('collection_id', $collection_id)->where('id', $id)->first();
            if (! $content) continue;

            $content->meta()->delete();
            $content->delete();

            ContentTrashed::dispatch(['source' => 'User', 'content' => $content]);
            AuditLogger::log('trash', 'content', $content->id, 'Content #' . $content->id, [
                'collection_id' => $collection_id,
            ], $project->id);
            $this->notifyProjectAdmins($project->id, [
                'action' => 'trash', 'entity_label' => 'Content #' . $content->id,
                'collection_id' => $collection_id, 'content_id' => $content->id,
            ]);
        }
        return response([], 200);
    }

    public function deleteSelected($project_id, $collection_id, Request $request)
    {
        $project = Project::findOrFail($project_id);
        $this->authorizeAdmin($project);

        foreach ($request->get('selected') as $id) {
            $content = Content::withTrashed()->where('project_id', $project->id)
                ->where('collection_id', $collection_id)->where('id', $id)->first();
            if (! $content) continue;

            $content->meta()->forceDelete();
            $content->forceDelete();

            ContentDeleted::dispatch(['source' => 'User', 'content' => [
                'project_id' => $project->id, 'collection_id' => $collection_id, 'item_id' => $id,
            ]]);
            AuditLogger::log('delete', 'content', $id, 'Content #' . $id, [
                'collection_id' => $collection_id,
            ], $project->id);
            $this->notifyProjectAdmins($project->id, [
                'action' => 'delete', 'entity_label' => 'Content #' . $id,
                'collection_id' => $collection_id, 'content_id' => $id,
            ]);
        }
        return response([], 200);
    }

    public function restoreSelected($project_id, $collection_id, Request $request)
    {
        $project = Project::findOrFail($project_id);
        $this->authorizeEditor($project);

        foreach ($request->get('selected') as $id) {
            $content = Content::onlyTrashed()->where('project_id', $project->id)
                ->where('collection_id', $collection_id)->where('id', $id)->first();
            if (! $content) continue;

            $content->meta()->restore();
            $content->restore();

            ContentRestored::dispatch(['source' => 'User', 'content' => $content]);
            AuditLogger::log('restore', 'content', $content->id, 'Content #' . $content->id, [
                'collection_id' => $collection_id,
            ], $project->id);
            $this->notifyProjectAdmins($project->id, [
                'action' => 'restore', 'entity_label' => 'Content #' . $content->id,
                'collection_id' => $collection_id, 'content_id' => $content->id,
            ]);
        }
        return response([], 200);
    }

    // =================================================================
    // Workflow gate helper
    // =================================================================

    private function workflowPublishBlocked()
    {
        return response()->json([
            'success' => false, 'code' => 422,
            'message' => 'This project has the editorial workflow enabled — submit the content for review and approve it via the workflow endpoints instead of publishing directly.',
            'data'    => null,
        ], 422);
    }
}