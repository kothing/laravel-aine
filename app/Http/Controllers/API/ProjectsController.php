<?php

namespace App\Http\Controllers\API;

use App\Models\Project;
use App\Http\Controllers\Controller;
use App\Http\Resources\ProjectResource;
use App\Http\Controllers\API\Concerns\AuthorizesProjectApi;
use Illuminate\Http\Request;

class ProjectsController extends Controller {

    use AuthorizesProjectApi;

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

        return $this->success(new ProjectResource($project), 'Success');
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
