<?php

namespace App\Http\Controllers\API;

use App\Models\Project;
use App\Http\Controllers\Controller;
use App\Http\Resources\ProjectResource;
use App\Http\Controllers\API\Concerns\AuthorizesProjectApi;
use App\Http\Controllers\API\Concerns\HandlesBrowserCache;
use Illuminate\Http\Request;

class ProjectsController extends Controller {

    use AuthorizesProjectApi, HandlesBrowserCache;

    /**
     * Get project
     *
     * @param string $uuid
     * @return \App\Http\Resources\ProjectResource
     */
    public function getProjectByUuid($uuid){
        $project = Project::where('uuid', $uuid)->first();

        if(!$project){
            return $this->notFound('Project not found');
        }
        if ($response = $this->authorizeProjectRead($project)) {
            return $response;
        }

        $response = $this->success(new ProjectResource($project), 'Success');

        // Browser caching only for public projects reached anonymously —
        // private or token-authenticated responses must never be stored by
        // a client-side cache.
        if ($project->public_api && !auth('sanctum')->check()) {
            $etag = $this->publicApiEtag($response->getContent());
            if ($this->ifNoneMatchMatches(request(), $etag)) {
                return $this->respondNotModified($etag);
            }
            $response->header('ETag', $etag);
            $response->header('Cache-Control', 'no-cache, must-revalidate');
        }

        return $response;
    }

    /**
     * Get project by explicit project identifier (UUID or slug)
     * Project is resolved by ValidateProjectAccess middleware and set on request attributes
     *
     * @param string $projectIdentifier Project UUID or slug
     * @param \Illuminate\Http\Request $request
     * @return \App\Http\Resources\ProjectResource
     */
    public function getProject($projectIdentifier, Request $request){
        $project = $request->attributes->get('resolved_project');
        
        if (!$project) {
            return $this->notFound('Project not resolved');
        }
        
        return $this->getProjectByUuid($project->uuid);
    }
}
