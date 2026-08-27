<?php

namespace App\Http\Controllers\Admin;

use App\Aine\AuditLogger;
use App\Aine\ProjectTemplates;
use App\Http\Controllers\Controller;
use App\Models\Collection;
use App\Models\CollectionField;
use App\Models\Project;
use App\Models\User;
use App\Models\Webhook;
use App\Models\WebhookLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Laravel\Sanctum\PersonalAccessToken;
use Spatie\Permission\Exceptions\UnauthorizedException;
use Spatie\Permission\Models\Role;

class ProjectsController extends Controller
{

    /**
     * Get projects
     *
     * @param \Illuminate\Http\Request $request
     * @return \App\Model\Project
     */
    public function index(Request $request){
        /** @var User $user */
        $user = Auth::user();

        $projects = Project::when($request->get('search'), function($q)use($request){
            $searchItem = $request->get('search');
            return $q->where('name', 'LIKE', "%$searchItem%");
        });

        if(!$user->isSuperAdmin()){
            $roles = $user->roles;

            $arr = [];

            foreach ($roles as $role) {
                $ex = explode('admin', $role->name);

                if(isset($ex[1]) && !in_array($ex[1], $arr)) {
                    $arr[] = $ex[1];
                }
                    
                $ex = explode('editor', $role->name);

                if(isset($ex[1]) && !in_array($ex[1], $arr)) {
                    $arr[] = $ex[1];
                }
            }

            $projects = $projects->whereIn('id', $arr);
        }

        $projects = $projects->orderBy('created_at', 'DESC')->get();

        return response($projects, 200);
    }

    /**
     * Check if slug exists
     *
     * @param string $slug
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function checkSlug($slug, Request $request){
        $query = Project::where('slug', $slug);
        
        $excludeId = $request->get('exclude_id');
        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }
        
        $exists = $query->exists();
        
        return response([
            'available' => !$exists
        ], 200);
    }

    /**
     * Create a new project
     *
     * @param \Illuminate\Http\Request $request
     * @return \App\Model\Project
     */
    public function store(Request $request){

        $request->validate([
            'name' => ['required', 'string', 'max:255', 'not_regex:/[#$%^&*()+=\-\[\]\';,\/{}|":<>?~\\\\]/'],
            'slug' => 'nullable|regex:/^[a-z0-9-]+$/|max:60|unique:projects,slug',
            'default_locale' => 'required|max:255',
        ],[
            'slug.regex' => __('Slug can only contain lowercase letters, numbers, and hyphens'),
            'slug.unique' => __('Slug already exists')
        ]);

        $slug = $request->get('slug');
        if (empty($slug)) {
            $slug = Str::slug($request->get('name'));
        }

        $project = Project::create([
        	'name' => $request->get('name'),
        	'slug' => $slug,
        	'description' => $request->get('description'),
        	'default_locale' => $request->get('default_locale'),
        	'locales' => $request->get('default_locale'),
        ]);

        Role::create(['name' => 'admin'.$project->id]);
        Role::create(['name' => 'editor'.$project->id]);

        // Apply a preset template (CMS / Business Directory) when selected.
        $templateType = (int) $request->get('type');
        if ($templateType && $template = ProjectTemplates::get($templateType)) {
            ProjectTemplates::apply($project, $template);
        }

        AuditLogger::log('create', 'project', $project->id, $project->name, null, $project->id);

        return response($project, 200);
    }

    /**
     * Get project by id
     *
     * @param int $id
     * @return \App\Models\Project
     */
    public function show($id){
        /** @var User $user */
        $user = Auth::user();

        if(!$user->isSuperAdmin() && !$user->hasRole('admin'.$id) && !$user->hasRole('editor'.$id)){
            throw UnauthorizedException::forRoles(['admin'.$id]);
        }

        $project = Project::with('collections')->findOrFail($id);

        $project->s3 = false;
        //Check if AWS S3 has been configured
        if(config('filesystems.disks.s3.key') && config('filesystems.disks.s3.secret') && config('filesystems.disks.s3.region') && config('filesystems.disks.s3.bucket')){
            $project->s3 = true;
        }

        return $project;
    }

    /**
     * Update project
     *
     * @param int $id
     * @param \Illuminate\Http\Request $request
     * @return \App\Models\Project
     */
    public function update($id, Request $request){
        /** @var User $user */
        $user = Auth::user();

        if(!$user->isSuperAdmin() && !$user->hasRole('admin'.$id)){
            throw UnauthorizedException::forRoles(['admin'.$id]);
        }

        $project = Project::findOrFail($id);

        $request->validate([
            'name' => 'required|max:255',
            'slug' => 'required|regex:/^[a-z0-9-]+$/|max:60|unique:projects,slug,' . $id,
        ],[
            'name.required' => __('Project name is required'),
            'slug.required' => __('Project slug is required'),
            'slug.regex' => __('Slug can only contain lowercase letters, numbers, and hyphens'),
            'slug.unique' => __('Slug already exists')
        ]);

        $slug = $request->get('slug');
        if (empty($slug)) {
            $slug = Str::slug($request->get('name'));
        }

        $project->update([
        	'name' => $request->get('name'),
        	'slug' => $slug,
        	'description' => $request->get('description'),
        	'disk' => $request->get('disk'),
            'status' => $request->get('status', $project->status),
        ]);

        AuditLogger::log('update', 'project', $project->id, $project->name, null, $project->id);

        return response($project, 200);
    }

    /**
     * Toggle the project's active/inactive status.
     *
     * @param int $id
     * @return \App\Models\Project
     */
    public function toggleStatus($id){
        /** @var User $user */
        $user = Auth::user();

        if(!$user->isSuperAdmin() && !$user->hasRole('admin'.$id)){
            throw UnauthorizedException::forRoles(['admin'.$id]);
        }

        $project = Project::findOrFail($id);

        $project->status = ! $project->isActive();
        $project->save();

        AuditLogger::log('toggle_status', 'project', $project->id, $project->name, null, $project->id);

        return response($project, 200);
    }

    /**
     * Delete a project
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function delete($id){
        /** @var User $user */
        $user = Auth::user();

        if(!$user->isSuperAdmin()){
            throw UnauthorizedException::forRoles(['admin'.$id]);
        }

        $project = Project::findOrFail($id);

        $project->collections()->delete();
        $project->fields()->delete();
        $project->content()->forceDelete();
        $project->meta()->forceDelete();

        $project->media()->delete();
        Storage::deleteDirectory($project->uuid);

        $project->tokens()->delete();

        foreach ($project->webhooks as $webhook) {
            $webhook->collections()->detach();
        }
        $project->webhooks()->delete();
        $project->webhook_logs()->delete();
        $project->forms()->delete();

        $admin_role = Role::where('name', 'admin'.$id)->delete();
        $editor_role = Role::where('name', 'editor'.$id)->delete();

        if($project->delete()){
            AuditLogger::log('delete', 'project', $id, $project->name ?? null);
            return response([], 200);
        } else {
            return response([], 404);
        }
    }

    /**
     * Get project locales
     *
     * @param int $id
     * @return \App\Models\Project
     */
    public function locales($id){
        return Project::findOrFail($id);
    }

    /**
     * Add new locale to project
     *
     * @param int $id
     * @param \Illuminate\Http\Request $request
     * @return void
     */
    public function addLocale($id, Request $request){
        $project = Project::findOrFail($id);

        $project_locales = explode(',', $project->locales);

        if(in_array($request->get('locale'), $project_locales)){
            return response([], 422);
        }

        if(!in_array($request->get('locale'), $project_locales)){
            if($project->locales === null){
                $project->locales = $request->get('locale');
            } else {
                $project->locales = $project->locales.",".$request->get('locale');
            }
        }

        $project->save();
    }

    /**
     * Change default locale of the project
     *
     * @param int $id
     * @param \Illuminate\Http\Request $request
     * @return void
     */
    public function changeDefaultLocale($id, Request $request){
        $project = Project::findOrFail($id);

        $project->default_locale = $request->get('locale');
        $project->save();
    }

    /**
     * Delete a locale from project
     *
     * @param int $id
     * @param \Illuminate\Http\Request $request
     * @return void
     */
    public function deleteLocale($id, Request $request){
        $project = Project::findOrFail($id);

        if($request->get('locale') == $project->default_locale){
            return response([], 422);
        }

        $project_locales = explode(',', $project->locales);

        $localesStr = '';
        foreach ($project_locales as $locale) {
            if($locale != $request->get('locale')){
                $localesStr .= $locale.',';
            }
        }
        $project->locales = rtrim($localesStr, ',');
        $project->save();
    }

    /**
     * Get users
     *
     * @param int $id
     * @return mixed
     */
    public function users($id){
        $project = Project::findOrFail($id);

        $super_admins = User::whereHas('roles', function($q){ $q->where('name', 'super_admin'); })->get();
        $users = User::whereDoesntHave('roles', function($q){ $q->where('name', 'super_admin'); })->get();

        $admins = User::whereHas('roles', function($q)use($project){ $q->where('name', 'admin'.$project->id); })->get();
        $editors = User::whereHas('roles', function($q)use($project){ $q->where('name', 'editor'.$project->id); })->get();

        $data['project'] = $project;
        $data['super_admins'] = $super_admins;
        $data['admins'] = $admins;
        $data['editors'] = $editors;
        $data['users'] = $users;

        return $data;
    }

    /**
     * Assign user to the project
     *
     * @param int id
     * @param \Illuminate\Http\Request $request
     * @return void
     */
    public function assignUser($id, Request $request){
        $project = Project::findOrFail($id);

        $user = User::findOrFail($request->get('user_id'));

        $role = Role::where('name', $request->get('role').$project->id)->first();

        if($role){
            $user->assignRole($role);
        } else {
            $admin = Role::create(['name' => 'admin'.$project->id]);
            $editor = Role::create(['name' => 'editor'.$project->id]);

            $role = Role::where('name', $request->get('role').$project->id)->first();
            $user->assignRole($role);
        }
    }

    /**
     * Remove user from project
     *
     * @param int id
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function removeUser($id, Request $request){
        $project = Project::findOrFail($id);

        $user = User::findOrFail($request->get('user_id'));

        $role = Role::where('name', $request->get('role').$project->id)->first();

        if($user->hasRole($role)){
            $user->removeRole($role);

            return response([], 200);
        } else {
            return response([], 404);
        }
    }

    /**
     * Create new user
     *
     * @param int $id
     * @param \Illuminate\Http\Request $request
     * @return void
     */
    public function newUser($id, Request $request){
        $project = Project::findOrFail($id);

        $request->validate([
            'name' => 'required|max:255',
            'email' => 'required|email|unique:users',
            'password' => 'required|min:8',
        ]);

        $user = User::create([
            'name' => $request->get('name'),
            'email' => $request->get('email'),
            'password' => Hash::make($request->get('password'))
        ]);
    }

    /**
     * Get api settings
     *
     * @param int $id
     * @return mixed
     */
    public function api($id){
        $project = Project::findOrFail($id);

        $data['project'] = $project;
        $data['tokens'] = $project->tokens;

        return $data;
    }

    /**
     * Crate a new API token
     *
     * @param int $id
     * @param \Illuminate\Http\Request $request
     * @return string $token->plainTextToken
     */
    public function newToken($id, Request $request){
        $project = Project::findOrFail($id);

        $request->validate([
            'name' => 'required'
        ]);

        $token = $project->createToken(
            $request->get('name'),
            $request->input('permissions', [])
        );

        return explode('|', $token->plainTextToken, 2)[1];
    }

    /**
     * Update a token
     *
     * @param int $id
     * @param \Illuminate\Http\Request $request
     * @return void
     */
    public function updateToken($id, Request $request){
        $project = Project::findOrFail($id);

        $token_id = $request->get('id');
        // Scope the token lookup to this project so a token id belonging to
        // another project can never be updated through this endpoint.
        $token = PersonalAccessToken::where('tokenable_id', $project->id)
            ->where('tokenable_type', Project::class)
            ->findOrFail($token_id);

        $request->validate([
            'name' => 'required'
        ]);

        $token->update([
            'name' => $request->get('name'),
            'abilities' => $request->get('permissions'),
        ]);
    }

    /**
     * Delete token
     *
     * @param int $id
     * @param \Illuminate\Http\Request $request
     * @return void
     */
    public function deleteToken($id, Request $request){
        $project = Project::findOrFail($id);

        $project->tokens()->where('id', $request->get('id'))->delete();
    }

    /**
     * Enable Public API Access
     *
     * @param int $id
     * @return void
     */
    public function enablePublicAPIAccess($id){
        $project = Project::findOrFail($id);

        $project->public_api = true;
        $project->save();
    }

    /**
     * Disable Public API Access
     *
     * @param int $id
     * @return void
     */
    public function disablePublicAPIAccess($id){
        $project = Project::findOrFail($id);

        $project->public_api = false;
        $project->save();
    }

    /**
     * Update domain whitelist for client applications that may call this project's API.
     *
     * @param int $id
     * @param \Illuminate\Http\Request $request
     * @return void
     */
    public function updateDomainWhitelist($id, Request $request){
        $project = Project::findOrFail($id);

        $request->validate([
            'domain_whitelist' => 'array',
            'domain_whitelist.*' => 'url'
        ]);

        $project->domain_whitelist = $request->get('domain_whitelist', []);
        $project->save();

        return response()->json(['message' => 'Domain whitelist updated successfully']);
    }

    /**
     * Get webhook settings
     *
     * @param  int  $project_id
     * @return \App\Models\Project
     */
    public function webhooks($project_id)
    {
        return Project::with(['collections', 'webhooks', 'webhooks.collections'])->findOrFail($project_id);
    }

    /**
     * Crate a new Webhook
     *
     * @param  int  $project_id
     * @param  \Illuminate\Http\Request  $request
     * @return \App\Models\Webhook $webhook
     */
    public function newWebhook($project_id, Request $request)
    {
        $project = Project::findOrFail($project_id);

        /** @var User $user */
        $user = Auth::user();

        $request->validate([
            'name' => 'required|string|max:255',
            'url' => 'required|regex:/^(https):\/\/[a-z0-9]+([\-\.]{1}[a-z0-9]+)*\.[a-z]{2,5}(:[0-9]{1,5})?(\/.*)?$/',
            'secret' => 'string|nullable|max:255|min:12',
            'collection_ids' => 'required|array',
            'events' => 'required|array',
            'sources' => 'required|array',
        ]);

        $webhook = Webhook::create([
            'project_id' => $project->id,
            'name' => $request->get('name'),
            'description' => $request->get('description'),
            'url' => $request->get('url'),
            'secret' => $request->get('secret'),
            'collection_ids' => $request->get('collection_ids'),
            'events' => $request->get('events'),
            'sources' => $request->get('sources'),
            'payload' => $request->get('payload'),
            'status' => $request->get('status'),
            'created_by' => $user->id,
        ]);

        $webhook->collections()->sync($request->get('collection_ids'));

        return $webhook;
    }

    /**
     * Update a webhook
     *
     * @param  int  $project_id
     * @param  \Illuminate\Http\Request  $request
     * @return \App\Models\Webhook $webhook
     */
    public function updateWebhook($project_id, Request $request)
    {
        $project = Project::findOrFail($project_id);
        $webhook = Webhook::findOrFail($request->get('id'));

        $request->validate([
            'name' => 'required|string|max:255',
            'url' => 'required|regex:/^(https):\/\/[a-z0-9]+([\-\.]{1}[a-z0-9]+)*\.[a-z]{2,5}(:[0-9]{1,5})?(\/.*)?$/',
            'secret' => 'string|nullable|max:255|min:12',
            'collection_ids' => 'required|array',
            'events' => 'required|array',
            'sources' => 'required|array',
        ]);

        $webhook->update([
            'name' => $request->get('name'),
            'description' => $request->get('description'),
            'url' => $request->get('url'),
            'secret' => $request->get('secret'),
            'collection_ids' => $request->get('collection_ids'),
            'events' => $request->get('events'),
            'sources' => $request->get('sources'),
            'payload' => $request->get('payload'),
            'status' => $request->get('status'),
        ]);

        $webhook->collections()->sync($request->get('collection_ids'));

        return $webhook;
    }

    /**
     * Delete webhook
     *
     * @param  int  $project_id
     * @param  \Illuminate\Http\Request  $request
     * @return void
     */
    public function deleteWebhook($project_id, Request $request)
    {
        $project = Project::findOrFail($project_id);
        $webhook = Webhook::findOrFail($request->get('id'));

        $webhook->logs()->delete();
        $webhook->collections()->detach();
        $webhook->delete();

        return response([], 200);
    }

    /**
     * Get webhook logs
     *
     * @param  int  $project_id
     * @param  int  $webhook_id
     * @return mixed
     */
    public function webhookLogs($project_id, $webhook_id)
    {
        $data['project'] = Project::with(['collections'])->findOrFail($project_id);
        $data['webhook'] = Webhook::findOrFail($webhook_id);
        $data['logs'] = WebhookLog::where('webhook_id', $webhook_id)->paginate(25);

        return $data;
    }

    /**
     * Delete webhook logs
     *
     * @param  int  $project_id
     * @param  int  $webhook_id
     * @return void
     */
    public function deleteWebhookLogs($project_id, $webhook_id)
    {
        $project = Project::with(['collections'])->findOrFail($project_id);
        $webhook = Webhook::findOrFail($webhook_id);
        $logs = WebhookLog::where('webhook_id', $webhook_id)->delete();

        return response([], 200);
    }
}
