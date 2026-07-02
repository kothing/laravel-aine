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
    public function show($uuid){
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
     * Get project by domain (no UUID required)
     * Automatically resolve project by domain, frontend does not need to pass UUID
     *
     * @param \Illuminate\Http\Request $request
     * @return \App\Http\Resources\ProjectResource
     */
    public function showByDomain(Request $request){
        // ⭐ Get the resolved project from request attributes set by middleware
        $project = $request->attributes->get('resolved_project');
        
        if (!$project) {
            return $this->notFound('Project not resolved');
        }
        
        // Reuse the original show method
        return $this->show($project->uuid);
    }
}
