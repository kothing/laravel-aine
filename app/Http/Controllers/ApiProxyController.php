<?php

namespace App\Http\Controllers;

use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class ApiProxyController extends Controller
{
    public function proxy(Request $request, $uuid, $path = null)
    {
        $project = Project::where('uuid', $uuid)->firstOrFail();
        
        $referer = $request->header('referer');
        if ($referer && !empty($project->domain_whitelist)) {
            $refererHost = parse_url($referer, PHP_URL_HOST);
            $whitelistedDomains = collect($project->domain_whitelist)
                ->map(fn($domain) => parse_url($domain, PHP_URL_HOST))
                ->toArray();
            
            if (!in_array($refererHost, $whitelistedDomains)) {
                return response()->json(['error' => 'Domain not in whitelist'], 403);
            }
        }
        
        $apiUrl = config('app.url') . "/api/{$uuid}" . ($path ? "/{$path}" : "");
        
        $headers = [];
        $authorization = $request->header('Authorization');
        if ($authorization) {
            $headers['Authorization'] = str_starts_with(strtolower($authorization), 'bearer ')
                ? $authorization
                : 'Bearer ' . $authorization;
        }

        $response = Http::withHeaders($headers)->send($request->method(), $apiUrl, [
            'query' => $request->query(),
            'json' => $request->except('_token'),
        ]);
        
        return response(
            $response->body(),
            $response->status()
        )->header('Content-Type', 'application/json');
    }
}