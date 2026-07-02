<?php

namespace App\Http\Middleware;

use Closure;
use App\Models\Project;
use Illuminate\Http\Request;

class VerifyApiAllowedDomain
{
    /**
     * Verify that the request Origin/Referer is in the project's API allowed domains list.
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
            ], 404);
        }

        if (empty($project->api_allowed_domains)) {
            return $next($request);
        }

        $referer = $request->header('referer');
        $origin = $request->header('origin');
        $hostToCheck = $origin ?: $referer;

        if (!$hostToCheck) {
            if ($this->hasValidProjectToken($request, $project)) {
                return $next($request);
            }

            if ($project->public_api) {
                return response()->json([
                    'error' => 'Missing required headers: Origin or Referer',
                    'message' => 'Please provide a valid Origin or Referer header from an API allowed domain',
                ], 403);
            }

            return response()->json([
                'error' => 'Missing required headers',
                'message' => 'Please provide a valid Origin or Referer header from an API allowed domain, or use a valid Bearer token for this project',
            ], 403);
        }

        $parsedUrl = parse_url($hostToCheck);
        $host = $parsedUrl['host'] ?? null;

        if (!$host) {
            return response()->json([
                'error' => 'Invalid Origin or Referer header',
            ], 403);
        }

        $allowedDomains = collect($project->api_allowed_domains)
            ->map(fn ($domain) => parse_url($domain, PHP_URL_HOST))
            ->filter()
            ->toArray();

        if (!in_array($host, $allowedDomains)) {
            return response()->json([
                'error' => 'Unauthorized domain',
                'message' => "Domain '{$host}' is not in this project's API allowed domains",
            ], 403);
        }

        return $next($request);
    }

    /**
     * Verify the request carries a Sanctum token scoped to the given project.
     */
    protected function hasValidProjectToken(Request $request, Project $project): bool
    {
        $authHeader = $request->header('authorization');

        if (!$authHeader || !str_starts_with(strtolower($authHeader), 'bearer ')) {
            return false;
        }

        if (!auth('sanctum')->check()) {
            return false;
        }

        $tokenProject = auth('sanctum')->user();

        return $tokenProject instanceof Project && $tokenProject->uuid === $project->uuid;
    }
}
