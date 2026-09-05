<?php

namespace App\Http\Controllers;

use App\Http\Resources\ContentResource;
use App\Models\Content;
use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Spatie\Permission\Exceptions\UnauthorizedException;

/*
|--------------------------------------------------------------------------
| Draft content preview
|--------------------------------------------------------------------------
|
| Two endpoints:
|   - GET  /preview/{token}                                  (public, frontend.php)
|     Read a single draft / unpublished content item by presenting a valid
|     preview token. Returns the content as authored (regardless of publish
|     state) so a reviewer can approve a draft before it goes live.
|   - POST /admin-api/content/preview-token/{p}/{c}/{content_id}   (admin)
|     Mint a fresh, time-limited preview token for a content item.
|
| The public read is authorized by the token itself — not by session or
| project whitelist — so a reviewer needs no account. The token is a random
| UUID; it is replaceable (minting a new one revokes the old) and expires.
*/

class PreviewController extends Controller
{
    /**
     * Public preview read. GET /preview/{token}
     */
    public function show(string $token)
    {
        $content = Content::with(['meta', 'collection.fields'])
            ->where('preview_token', $token)
            ->first();

        if (! $content) {
            return response()->json([
                'success' => false,
                'code' => 404,
                'message' => 'Preview not found or the link has been revoked.',
                'data' => null,
            ], 404);
        }

        if ($content->preview_expires_at && now()->gt($content->preview_expires_at)) {
            return response()->json([
                'success' => false,
                'code' => 403,
                'message' => 'Preview link has expired.',
                'data' => null,
            ], 403);
        }

        return response()->json([
            'success' => true,
            'code' => 200,
            'message' => 'Success',
            'data' => new ContentResource($content),
        ]);
    }

    /**
     * Mint (or replace) a preview token for a content item. 24h lifetime.
     *
     * POST /admin-api/content/preview-token/{project_id}/{collection_id}/{content_id}
     */
    public function generate(int $project_id, int $collection_id, int $content_id)
    {
        $project = Project::findOrFail($project_id);

        $user = Auth::user();
        if (! $user->isSuperAdmin()
            && ! $user->hasRole('admin' . $project->id)
            && ! $user->hasRole('editor' . $project->id)) {
            throw UnauthorizedException::forRoles(['admin' . $project->id]);
        }

        $content = Content::where('project_id', $project->id)
            ->where('collection_id', $collection_id)
            ->where('id', $content_id)
            ->firstOrFail();

        $content->preview_token = Str::uuid()->toString();
        $content->preview_expires_at = now()->addHours(24);
        $content->save();

        return response()->json([
            'success' => true,
            'token' => $content->preview_token,
            'expires_at' => $content->preview_expires_at->toDateTimeString(),
        ]);
    }
}