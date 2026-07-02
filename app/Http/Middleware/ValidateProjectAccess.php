<?php

namespace App\Http\Middleware;

use Closure;
use App\Models\Project;
use Illuminate\Http\Request;

class ValidateProjectAccess
{
    /**
     * Validate project access by project identifier and domain whitelist.
     *
     * This middleware:
     * 1. Validates that a project identifier (UUID or slug) is provided in the URL
     * 2. Resolves the project by the provided identifier
     * 3. Validates that the request Origin/Referer domain is in the project's domain whitelist
     * 4. Sets the resolved project on the request attributes for downstream use
     */
    public function handle(Request $request, Closure $next)
    {
        $projectIdentifier = $request->route('projectIdentifier');

        if (!$projectIdentifier) {
            return response()->json([
                'error' => 'Missing project identifier',
                'message' => 'Please provide a project identifier (UUID or slug) in the URL',
            ], 400);
        }

        $project = $this->resolveProjectByIdentifier($projectIdentifier);

        if (!$project) {
            return response()->json([
                'error' => 'Project not found',
                'message' => "Project with identifier '{$projectIdentifier}' not found",
            ], 404);
        }

        $origin = $request->header('origin');
        $referer = $request->header('referer');
        $hostToCheck = $origin ?: $referer;

        if ($hostToCheck) {
            $parsedUrl = parse_url($hostToCheck);
            $host = $parsedUrl['host'] ?? null;

            if ($host && !$this->isDomainInWhitelist($project, $host)) {
                return response()->json([
                    'error' => 'Domain not in whitelist',
                    'message' => "Domain '{$host}' is not in whitelist to access project '{$projectIdentifier}'",
                    'domain' => $host,
                ], 403);
            }
        }

        $request->attributes->set('resolved_project', $project);
        $request->attributes->set('resolved_project_uuid', $project->uuid);
        $request->attributes->set('resolved_project_id', $project->id);

        return $next($request);
    }

    /**
     * Resolve project by UUID or slug.
     */
    protected function resolveProjectByIdentifier(string $identifier): ?Project
    {
        return Project::where('uuid', $identifier)
            ->orWhere('slug', $identifier)
            ->first();
    }

    /**
     * Check if domain is in project's domain_whitelist.
     */
    protected function isDomainInWhitelist(Project $project, string $host): bool
    {
        if (empty($project->domain_whitelist)) {
            return true;
        }

        foreach ($project->domain_whitelist as $whitelistedDomain) {
            $whitelistedHost = parse_url($whitelistedDomain, PHP_URL_HOST);
            if ($whitelistedHost === $host) {
                return true;
            }
        }

        return false;
    }
}