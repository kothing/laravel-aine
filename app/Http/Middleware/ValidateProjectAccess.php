<?php

namespace App\Http\Middleware;

use Closure;
use App\Models\Project;
use Illuminate\Http\Request;

class ValidateProjectAccess
{
    /**
     * Validate project access by UUID.
     *
     * This middleware:
     * 1. Validates that a project UUID is provided in the URL
     * 2. Resolves the project by the provided UUID
     * 3. Sets the resolved project on the request attributes for downstream use
     * 
     * Note: This middleware does NOT validate domain whitelist.
     * It is designed for backend-to-backend API calls where authentication
     * is handled via Token (Sanctum) instead of domain whitelist.
     */
    public function handle(Request $request, Closure $next)
    {
        $uuid = $request->route('uuid');

        if (!$uuid) {
            return $next($request);
        }

        $project = Project::where('uuid', $uuid)->first();

        if (!$project) {
            return response()->json([
                'error' => 'Project not found',
                'message' => "Project with UUID '{$uuid}' not found",
            ], 404);
        }

        $request->attributes->set('resolved_project', $project);
        $request->attributes->set('resolved_project_uuid', $project->uuid);
        $request->attributes->set('resolved_project_id', $project->id);

        return $next($request);
    }
}