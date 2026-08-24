<?php

namespace App\Http\Controllers\API\Concerns;

use App\Models\Project;
use Illuminate\Http\JsonResponse;

trait AuthorizesProjectApi
{
    /**
     * Authorize read access to a project via public API flag, domain whitelist validation, or Sanctum token.
     */
    protected function authorizeProjectRead(Project $project): ?JsonResponse
    {
        if ($project->public_api) {
            return null;
        }

        // The domain-whitelist middleware sets an explicit marker after its
        // own origin check passed — skip the token check only for those
        // token-less public reads. The mere presence of resolved_project is
        // NOT enough (the project-access middleware sets it too). Authenticated
        // requests MUST prove their token belongs to this project, otherwise
        // any valid token could read any project (incl. drafts).
        if (request()->attributes->get('resolved_project_origin_verified') && !auth('sanctum')->check()) {
            return null;
        }

        if (!auth('sanctum')->check()) {
            return response()->json([
                'success' => false,
                'code' => 401,
                'message' => 'Unauthenticated',
                'data' => null,
            ], 401);
        }

        $tokenProject = auth('sanctum')->user();

        if ($tokenProject->uuid !== $project->uuid) {
            return response()->json([
                'success' => false,
                'code' => 403,
                'message' => 'API token is not valid for this project',
                'data' => null,
            ], 403);
        }

        if (!$tokenProject->tokenCan('read')) {
            return response()->json([
                'success' => false,
                'code' => 403,
                'message' => 'API token does not have the required permissions',
                'data' => null,
            ], 403);
        }

        return null;
    }

    /**
     * Authorize a write ability for the project identified by UUID.
     */
    protected function authorizeProjectAbility(string $ability, string $uuid): ?JsonResponse
    {
        $auth = auth('sanctum')->user();

        if (!$auth) {
            return response()->json([
                'success' => false,
                'code' => 401,
                'message' => 'Unauthenticated',
                'data' => null,
            ], 401);
        }

        if (!$auth->tokenCan($ability)) {
            return response()->json([
                'success' => false,
                'code' => 403,
                'message' => 'API token does not have the required permissions',
                'data' => null,
            ], 403);
        }

        if ($auth->uuid !== $uuid) {
            return response()->json([
                'success' => false,
                'code' => 404,
                'message' => 'Project not found',
                'data' => null,
            ], 404);
        }

        return null;
    }
}
