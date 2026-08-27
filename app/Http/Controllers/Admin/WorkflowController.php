<?php

namespace App\Http\Controllers\Admin;

use App\Events\ContentPublished;
use App\Events\ContentUpdated;
use App\Http\Controllers\Controller;
use App\Models\Content;
use App\Models\Project;
use App\Services\Content\ContentMutationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Spatie\Permission\Exceptions\UnauthorizedException;

class WorkflowController extends Controller
{
    protected ContentMutationService $mutations;

    public function __construct()
    {
        $this->mutations = new ContentMutationService(new \App\Services\Content\ContentValidationService());
    }
    public function submitReview(int $project_id, int $collection_id, int $content_id)
    {
        $project = Project::findOrFail($project_id);
        $this->authorizeWriter($project);

        $content = Content::where('project_id', $project->id)
            ->where('collection_id', $collection_id)
            ->where('id', $content_id)
            ->firstOrFail();

        if (in_array($content->workflow_state, ['published', 'in_review'], true)) {
            return response()->json(['success' => false, 'code' => 422, 'message' => 'Content is already published or already under review.', 'data' => null], 422);
        }

        $content->workflow_state = 'in_review';
        $content->updated_by = Auth::id();
        $content->save();

        event(new ContentUpdated(['source' => 'User', 'content' => $content->fresh()]));

        return response()->json(['success' => true, 'message' => 'Submitted for review.', 'data' => ['workflow_state' => $content->workflow_state]]);
    }

    public function approve(int $project_id, int $collection_id, int $content_id)
    {
        $project = Project::findOrFail($project_id);
        $this->authorizeReviewer($project);

        $content = Content::where('project_id', $project->id)
            ->where('collection_id', $collection_id)
            ->where('id', $content_id)
            ->firstOrFail();

        if ($content->workflow_state !== 'in_review') {
            return response()->json(['success' => false, 'code' => 422, 'message' => 'Only content currently under review can be approved.', 'data' => null], 422);
        }

        // If this is a draft branch, merge it back into the main row.
        if ($content->isDraftBranch()) {
            $main = $this->mutations->publishDraftBranch($content, Auth::id());
            $publishedContent = $main;
        } else {
            $content->workflow_state = 'published';
            $content->published_at = now();
            $content->published_by = Auth::id();
            $content->updated_by = Auth::id();
            $content->save();
            $publishedContent = $content->fresh();
        }

        $this->bumpPublicCacheVersion($publishedContent->project_id);
        event(new ContentPublished(['source' => 'User', 'content' => $publishedContent]));

        return response()->json(['success' => true, 'message' => 'Approved and published.', 'data' => ['workflow_state' => 'published']]);
    }

    public function reject(Request $request, int $project_id, int $collection_id, int $content_id)
    {
        $project = Project::findOrFail($project_id);
        $this->authorizeReviewer($project);

        $content = Content::where('project_id', $project->id)
            ->where('collection_id', $collection_id)
            ->where('id', $content_id)
            ->firstOrFail();

        if ($content->workflow_state !== 'in_review') {
            return response()->json(['success' => false, 'code' => 422, 'message' => 'Only content currently under review can be rejected.', 'data' => null], 422);
        }

        $content->workflow_state = 'rejected';
        $content->reviewer_comment = $request->get('reason') ?: null;
        $content->updated_by = Auth::id();
        $content->save();

        $this->bumpPublicCacheVersion($content->project_id);
        event(new ContentUpdated(['source' => 'User', 'content' => $content->fresh()]));

        return response()->json(['success' => true, 'message' => 'Rejected.', 'data' => ['workflow_state' => $content->workflow_state, 'reviewer_comment' => $content->reviewer_comment]]);
    }

    private function authorizeWriter(Project $project): void
    {
        $user = Auth::user();
        if (! $user->isSuperAdmin()
            && ! $user->hasRole('admin' . $project->id)
            && ! $user->hasRole('editor' . $project->id)) {
            throw UnauthorizedException::forRoles(['admin' . $project->id, 'editor' . $project->id]);
        }
    }

    private function authorizeReviewer(Project $project): void
    {
        $user = Auth::user();
        if (! $user->isSuperAdmin() && ! $user->hasRole('admin' . $project->id)) {
            throw UnauthorizedException::forRoles(['admin' . $project->id]);
        }
    }

    private function bumpPublicCacheVersion(?int $projectId): void
    {
        if ($projectId === null) return;
        try {
            $key = 'public_content_version:' . $projectId;
            Cache::put($key, (int) Cache::get($key, 0) + 1, 7 * 86400);
        } catch (\Throwable $e) { /* best-effort */ }
    }
}