<?php

namespace App\Http\Controllers\API;

use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver as GdDriver;
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
    public function getMediaListByUuid($uuid){
        $project = Project::where('uuid', $uuid)->first();
        if(!$project){
            return $this->notFound('Project not found');
        }
        if ($response = $this->authorizeProjectRead($project)) {
            return $response;
        }

        $files = Media::with('project')->where('project_id', $project->id)->get();

        return $this->success(MediaResource::collection($files), 'Success');
    }

    /**
     * Get media by ID using UUID
     *
     * @param string $uuid
     * @param int $media_id
     * @return \App\Http\Resources\MediaResource
     */
    public function getMediaByUuid($uuid, $media_id){
        $project = Project::where('uuid', $uuid)->first();
        if(!$project){
            return $this->notFound('Project not found');
        }
        if ($response = $this->authorizeProjectRead($project)) {
            return $response;
        }

        $file = Media::where('project_id', $project->id)->find($media_id);

        if(!$file) {
            return $this->notFound('Media not found');
        }

        return $this->success(new MediaResource($file), 'Success');
    }

    /**
     * Get media by name using UUID
     *
     * @param string $uuid
     * @param string $media_name
     * @return \App\Http\Resources\MediaResource
     */
    public function getMediaByNameByUuid($uuid, $media_name){
        $project = Project::where('uuid', $uuid)->first();
        if(!$project){
            return $this->notFound('Project not found');
        }
        if ($response = $this->authorizeProjectRead($project)) {
            return $response;
        }

        $file = Media::where('project_id', $project->id)->where('name', $media_name)->first();

        if(!$file) {
            return $this->notFound('Media not found');
        }

        return $this->success(new MediaResource($file), 'Success');
    }

    /**
     * Delete media using UUID
     *
     * @param string $uuid
     * @param int $media_id
     * @return \Illuminate\Http\Response
     */
    public function deleteMediaByUuid($uuid, $media_id){
        if ($response = $this->authorizeProjectAbility('delete', $uuid)) {
            return $response;
        }

        // Resolve the project by the UUID from the URL — NOT by the user id.
        $project = Project::where('uuid', $uuid)->first();
        if(!$project) {
            return $this->notFound('Project not found');
        }

        $file = Media::where('project_id', $project->id)->find($media_id);

        if(!$file) {
            return $this->notFound('Media not found');
        }

        $storagePath = $file->disk === 'public' ? $project->uuid : 'public/'.$project->uuid;
        
        $original = $storagePath.'/'.$file->name;
        if(Storage::disk($file->disk)->exists($original)){
            Storage::disk($file->disk)->delete($original);
        }

        $thumb = $storagePath.'/thumbnails/'.$file->name;
        if(Storage::disk($file->disk)->exists($thumb)){
            Storage::disk($file->disk)->delete($thumb);
        }

        if($file->delete()){
            return $this->deleted('Media deleted');
        } else {
            return $this->notFound('Failed to delete media');
        }
    }

    /**
     * Upload media using UUID
     *
     * @param string $uuid
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function uploadMediaByUuid($uuid, Request $request){
        if ($response = $this->authorizeProjectAbility('create', $uuid)) {
            return $response;
        }

        // Resolve the project by the UUID from the URL — NOT by the user id.
        $project = Project::where('uuid', $uuid)->first();
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
                'file' => 'required|file|mimes:jpg,jpeg,png,bmp,gif,webp,pdf,doc,docx,xls,xlsx,ppt,pptx,txt,csv,zip,rar,7z,tar,gz,mp3,wav,ogg,mp4,webm,mov,avi,json|max:'.($max_file_size / 1024),
            ]);

            $file = $request->file('file');

            $file_name = $this->renameFile($file->getClientOriginalName(), $project->uuid, $file, $project->disk);

            $storagePath = $project->disk === 'public' ? $project->uuid : 'public/'.$project->uuid;

            Storage::disk($project->disk)->putFileAs($storagePath, $request->file('file'), $file_name);

            $extension = $file->getClientOriginalExtension();

            $image_types = ['jpg', 'jpeg', 'png', 'bmp', 'gif', 'webp'];
            if(in_array($extension, $image_types)){
                $manager = new ImageManager(new GdDriver());
                $thumb = $manager->read($file)->scale(height: 600)->encodeByExtension($extension);

                Storage::disk($project->disk)->put($storagePath.'/thumbnails/'.$file_name, (string)$thumb, 'public');
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
        $storagePath = $disk === 'public' ? $project_uuid : 'public/'.$project_uuid;

        $path = $storagePath.'/'.$file_name;

        $i = 1;
        while(Storage::disk($disk)->exists($path)){
            $name = explode('.', $file->getClientOriginalName());
            $file_name = $name[0] . '('. $i .')' . '.' . $file->getClientOriginalExtension();
            $path = $storagePath.'/'.$file_name;
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
     * Get project media by explicit project identifier (UUID or slug)
     * Project is resolved by ValidateProjectAccess middleware and set on request attributes
     *
     * @param string $projectIdentifier Project UUID or slug
     * @param \Illuminate\Http\Request $request
     * @return \App\Http\Resources\MediaResource
     */
    public function getMediaList($projectIdentifier, Request $request){
        $project = $request->attributes->get('resolved_project');
        
        if (!$project) {
            return $this->notFound('Project not resolved');
        }
        
        return $this->getMediaListByUuid($project->uuid);
    }

    /**
     * Get media by ID using explicit project identifier (UUID or slug)
     * Project is resolved by ValidateProjectAccess middleware and set on request attributes
     *
     * @param string $projectIdentifier Project UUID or slug
     * @param int $media_id Media ID
     * @param \Illuminate\Http\Request $request
     * @return \App\Http\Resources\MediaResource
     */
    public function getMediaByID($project_identifier, $media_id, Request $request){
        $project = $request->attributes->get('resolved_project');
        
        if (!$project) {
            return $this->notFound('Project not resolved');
        }
        
        return $this->getMediaByUuid($project->uuid, $media_id);
    }

    /**
     * Get media by name using explicit project identifier
     * 
     * @param string $project_identifier Project UUID or slug
     * @param string $media_name File name
     * @param \Illuminate\Http\Request $request
     * @return \App\Http\Resources\MediaResource
     */
    public function getMediaByName($project_identifier, $media_name, Request $request){
        $project = $request->attributes->get('resolved_project');
        
        if (!$project) {
            return $this->notFound('Project not resolved');
        }
        
        return $this->getMediaByNameByUuid($project->uuid, $media_name);
    }

    /**
     * Delete media using explicit project identifier
     * 
     * @param string $project_identifier Project UUID or slug
     * @param int $media_id Media ID
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function deleteMedia($project_identifier, $media_id, Request $request){
        $project = $request->attributes->get('resolved_project');
        
        if (!$project) {
            return $this->notFound('Project not resolved');
        }
        
        return $this->deleteMediaByUuid($project->uuid, $media_id);
    }

    /**
     * Upload media using explicit project identifier
     * 
     * @param string $project_identifier Project UUID or slug
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function uploadMedia($project_identifier, Request $request){
        $project = $request->attributes->get('resolved_project');
        
        if (!$project) {
            return $this->notFound('Project not resolved');
        }
        
        return $this->uploadMediaByUuid($project->uuid, $request);
    }
}
