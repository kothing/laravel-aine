<?php

namespace App\Http\Middleware;

use Closure;
use App\Models\Project;
use Illuminate\Http\Request;

class VerifyDomainWhitelist
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
        $projectIdentifier = $request->route('project_identifier') ?? $request->route('projectIdentifier');

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

        // Inactive (disabled) projects are not exposed through the public API.
        if (! $project->isActive()) {
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

            // The CMS itself serves the frontend, so same-origin requests
            // must always be allowed; the whitelist only restricts
            // cross-origin consumers. Same-origin = the request's Origin
            // matches the host the request was actually sent to (covers
            // both http://127.0.0.1:8000 and http://localhost:8000).
            $isSameOrigin = $host !== null
                && strtolower($host) === strtolower($request->getHost())
                && (! $request->getPort() || (int) ($parsedUrl['port'] ?? 0) === (int) $request->getPort());

            if ($host && !$isSameOrigin && !$this->isDomainInWhitelist($project, $host)) {
                return response()->json([
                    'error' => 'Domain not in whitelist',
                    'message' => "Domain '{$host}' is not in whitelist to access project '{$projectIdentifier}'",
                    'domain' => $host,
                ], 403);
            }
        } elseif (! $project->public_api) {
            // No Origin/Referer at all (curl, CLI, server-to-server, native
            // apps): only public projects are readable anonymously. Anything
            // else must present an API token via the authenticated routes —
            // otherwise any anonymous request could read non-public content
            // (including drafts) by simply omitting the Origin header.
            return response()->json([
                'error' => 'Origin required',
                'message' => "Project '{$projectIdentifier}' is not public. Anonymous access requires a verified Origin/Referer; use an API token instead.",
            ], 403);
        }

        $request->attributes->set('resolved_project', $project);
        $request->attributes->set('resolved_project_uuid', $project->uuid);
        $request->attributes->set('resolved_project_id', $project->id);
        // Explicit marker: the origin check above passed. authorizeProjectRead
        // only trusts this marker (not the mere presence of resolved_project)
        // to skip the token check for public/token-less reads.
        $request->attributes->set('resolved_project_origin_verified', true);

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
            // No whitelist configured: only allow cross-origin reads when
            // the project is public. Otherwise a non-public project with an
            // empty whitelist would be readable by any origin.
            return (bool) $project->public_api;
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