<?php

namespace App\Http\Middleware;

use Closure;
use App\Models\Project;
use Illuminate\Http\Request;

class ResolveProjectByApiAllowedDomain
{
    /**
     * Resolve the project from the request Origin/Referer against api_allowed_domains.
     *
     * Client applications listed in API Allowed Domains can call /api/project/*
     * without passing a project UUID.
     */
    public function handle(Request $request, Closure $next)
    {
        $origin = $request->header('origin');
        $referer = $request->header('referer');
        $hostToCheck = $origin ?: $referer;

        if (!$hostToCheck) {
            return response()->json([
                'error' => 'Missing required headers',
                'message' => 'Please provide Origin or Referer header from an API allowed domain',
            ], 403);
        }

        $parsedUrl = parse_url($hostToCheck);
        $host = $parsedUrl['host'] ?? null;

        if (!$host) {
            return response()->json([
                'error' => 'Invalid domain',
                'message' => 'Could not parse domain from Origin/Referer',
            ], 403);
        }

        $project = Project::where(function ($query) use ($host) {
            $query->whereJsonContains('api_allowed_domains', "https://{$host}")
                ->orWhereJsonContains('api_allowed_domains', "http://{$host}")
                ->orWhereJsonContains('api_allowed_domains', "{$host}");
        })->first();

        if (!$project) {
            return response()->json([
                'error' => 'No project configured for this domain',
                'message' => "Domain '{$host}' is not listed in any project's API allowed domains",
                'domain' => $host,
            ], 404);
        }

        $request->attributes->set('resolved_project', $project);
        $request->attributes->set('resolved_project_uuid', $project->uuid);
        $request->attributes->set('resolved_project_id', $project->id);

        return $next($request);
    }
}
