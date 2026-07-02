<?php

namespace App\Http\Controllers\API;

use Image;
use App\Models\Media;
use App\Models\Project;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Resources\MediaResource;
use Illuminate\Support\Facades\Storage;
use App\Http\Controllers\API\Concerns\AuthorizesProjectApi;

class MediaController extends Controller {

    use AuthorizesProjectApi;

    /**
     * Get project files
     *
     * @param string $uuid
     * @return \App\Http\Resources\MediaResource
     */
    public function getProjectMedia($uuid){
        $project = Project::where('uuid', $uuid)->first();
        if(!$project){
            return $this->notFound('Project not found');
        }
        if ($response = $this->authorizeProjectRead($project)) {
            return $response;
        }

        $files = Media::where('project_id', $project->id)->get();

        return $this->success(MediaResource::collection($files), 'Success');
    }

    /**
     * Get file by ID
     *
     * @param string $uuid
     * @param int $id
     * @return \App\Http\Resources\MediaResource
     */
    public function getFileByID($uuid, $id){
        $project = Project::where('uuid', $uuid)->first();
        if(!$project){
            return $this->notFound('Project not found');
        }
        if ($response = $this->authorizeProjectRead($project)) {
            return $response;
        }

        $file = Media::where('project_id', $project->id)->find($id);

        if(!$file) {
            return $this->notFound('File not found');
        }

        return $this->success(new MediaResource($file), 'Success');
    }

    /**
     * Get file by name
     *
     * @param string $uuid
     * @param string $name
     * @return \App\Http\Resources\MediaResource
     */
    public function getFileByName($uuid, $name){
        $project = Project::where('uuid', $uuid)->first();
        if(!$project){
            return $this->notFound('Project not found');
        }
        if ($response = $this->authorizeProjectRead($project)) {
            return $response;
        }

        $file = Media::where('project_id', $project->id)->where('name', $name)->first();

        if(!$file) {
            return $this->notFound('File not found');
        }

        return $this->success(new MediaResource($file), 'Success');
    }

    /**
     * Delete a file
     *
     * @param string $uuid
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function deleteFile($uuid, $id){
        if ($response = $this->authorizeProjectAbility('delete', $uuid)) {
            return $response;
        }

        $auth = auth('sanctum')->user();
        $project = Project::find($auth->id);
        if(!$project) {
            return $this->notFound('Project not found');
        }

        $file = Media::where('project_id', $project->id)->find($id);

        if(!$file) {
            return $this->notFound('File not found');
        }

        $original = 'public/'.$project->uuid.'/'.$file->name;
        if(Storage::disk($file->disk)->exists($original)){
            Storage::disk($file->disk)->delete($original);
        }

        $thumb = 'public/'.$project->uuid.'/thumbnails/'.$file->name;
        if(Storage::disk($file->disk)->exists($thumb)){
            Storage::disk($file->disk)->delete($thumb);
        }

        if($file->delete()){
            return $this->deleted('File deleted');
        } else {
            return $this->notFound('Failed to delete file');
        }
    }

    /**
     * Upload a file
     *
     * @param string $uuid
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function uploadFile($uuid, Request $request){
        if ($response = $this->authorizeProjectAbility('create', $uuid)) {
            return $response;
        }

        $auth = auth('sanctum')->user();
        $project = Project::find($auth->id);
        if(!$project) {
            return $this->notFound('Project not found');
        }

        if($request->has('file')){

            $php_post_max_size = $this->return_bytes(ini_get('post_max_size'));
            $php_upload_max_filesize = $this->return_bytes(ini_get('upload_max_filesize'));
            $env_max_file_size = $this->return_bytes(env('MAX_FILE_SIZE', ));

            if($php_post_max_size < $php_upload_max_filesize){
                $max_file_size = $php_post_max_size;
            } else {
                if($php_upload_max_filesize < $env_max_file_size){
                    $max_file_size = $php_upload_max_filesize;
                } else {
                    $max_file_size = $env_max_file_size;
                }
            }

            $request->validate([
                'file' => 'required|file|max:'.($max_file_size / 1024),
            ]);

            $file = $request->file('file');

            $file_name = $this->renameFile($file->getClientOriginalName(), $project->uuid, $file, $project->disk);

            Storage::disk($project->disk)->putFileAs('public/'.$project->uuid, $request->file('file'), $file_name);

            $extension = $file->getClientOriginalExtension();

            $image_types = ['jpg', 'jpeg', 'png', 'bmp', 'gif', 'webp'];
            if(in_array($extension, $image_types)){
                $thumb = Image::make($file)->resize(null, 600, function ($constraint) {
                    $constraint->aspectRatio();
                })->encode($extension);

                Storage::disk($project->disk)->put('public/'.$project->uuid.'/thumbnails/'.$file_name, (string)$thumb, 'public');
            }

            $image = getimagesize($file);

            $new_file = Media::create([
                'project_id' => $project->id,
                'name' => $file_name,
                'type' => $file->getClientOriginalExtension(),
                'size' => $file->getSize(),
                'width' => isset($image[0]) ? $image[0] : null,
                'height' => isset($image[1]) ? $image[1] : null,
                'disk' => $project->disk,
            ]);

            return $this->created(new MediaResource($new_file), 'File uploaded successfully');
        } else {
            return $this->validationError('File not found! Attach a file to your request.');
        }
    }

    /**
     * Rename a file
     *
     * @param string $file_name
     * @param uuid $project_uuid
     * @param file $file
     * @param string $disk
     * @return string $file_name
     */
    private function renameFile($file_name, $project_uuid, $file, $disk){

        $path = 'public/'.$project_uuid.'/'.$file_name;

        $i = 1;
        while(Storage::disk($disk)->exists($path)){
            $name = explode('.', $file->getClientOriginalName());
            $file_name = $name[0] . '('. $i .')' . '.' . $file->getClientOriginalExtension();
            $path = 'public/'.$project_uuid.'/'.$file_name;
            $i++;
        }

        return $file_name;
    }

    /**
     * Return size in bytes
     *
     * @param string $val
     * @return int $val
    */
    private function return_bytes ($val) {
        if(empty($val)) {
            return 0;
        }

        $val = trim($val);

        preg_match('#([0-9]+)[\s]*([a-z]+)#i', $val, $matches);

        $last = '';
        if(isset($matches[2])){
            $last = $matches[2];
        }

        if(isset($matches[1])){
            $val = (int) $matches[1];
        }

        switch (strtolower($last))
        {
            case 'g':
            case 'gb':
                $val *= 1024;
            case 'm':
            case 'mb':
                $val *= 1024;
            case 'k':
            case 'kb':
                $val *= 1024;
        }

        return (int) $val;
    }

    /**
     * Get project media by domain (no UUID required)
     * Automatically resolve project by domain, frontend does not need to pass UUID
     *
     * @param \Illuminate\Http\Request $request
     * @return \App\Http\Resources\MediaResource
     */
    public function getProjectMediaByDomain(Request $request){
        // ⭐ Get the resolved project from request attributes set by middleware
        $project = $request->attributes->get('resolved_project');
        
        if (!$project) {
            return $this->notFound('Project not resolved');
        }
        
        // Reuse the original getProjectMedia method
        return $this->getProjectMedia($project->uuid);
    }

    /**
     * Get file by ID by domain (no UUID required)
     * Automatically resolve project by domain, frontend does not need to pass UUID
     *
     * @param int $id
     * @param \Illuminate\Http\Request $request
     * @return \App\Http\Resources\MediaResource
     */
    public function getFileByIDByDomain($id, Request $request){
        // ⭐ Get the resolved project from request attributes set by middleware
        $project = $request->attributes->get('resolved_project');
        
        if (!$project) {
            return $this->notFound('Project not resolved');
        }
        
        // Reuse the original getFileByID method
        return $this->getFileByID($project->uuid, $id);
    }

}
