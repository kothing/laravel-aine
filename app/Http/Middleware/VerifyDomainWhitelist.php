<?php

namespace App\Http\Middleware;

use Closure;
use App\Models\Project;
use Illuminate\Http\Request;

class VerifyDomainWhitelist
{
    /**
     * Verify that the request Origin/Referer is in the project's domain whitelist.
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

        if (empty($project->domain_whitelist)) {
            return $next($request);
        }

        $referer = $request->header('referer');
        $origin = $request->header('origin');
        $hostToCheck = $origin ?: $referer;

        if (!$hostToCheck) {
            return $next($request);
        }

        $parsedUrl = parse_url($hostToCheck);
        $host = $parsedUrl['host'] ?? null;

        if (!$host) {
            return response()->json([
                'error' => 'Invalid Origin or Referer header',
                'message' => 'Please provide a valid Origin or Referer header from a whitelisted domain, or use a valid Bearer token for this project',
            ], 403);
        }

        $whitelistedDomains = collect($project->domain_whitelist)
            ->map(fn ($domain) => parse_url($domain, PHP_URL_HOST))
            ->filter()
            ->toArray();

        if (!in_array($host, $whitelistedDomains)) {
            return response()->json([
                'error' => 'Domain not in whitelist',
                'message' => "Domain '{$host}' is not in this project's domain whitelist",
                'domain' => $host,
            ], 403);
        }

        return $next($request);
    }
}