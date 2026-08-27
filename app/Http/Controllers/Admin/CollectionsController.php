<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Content;
use App\Models\Project;
use App\Models\Collection;
use App\Models\CollectionField;
use App\Aine\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Spatie\Permission\Exceptions\UnauthorizedException;

class CollectionsController extends Controller
{
    /**
     * Get project by id
     * 
     * @param int $id
     * @return \App\Models\Project
     */
    public function project($id){
        $project = Project::with('collections')->findOrFail($id);

        /** @var \App\Models\User $user */
        $user = Auth::user();
        if(!$user->isSuperAdmin() && !$user->hasRole('admin'.$project->id)){
            throw UnauthorizedException::forRoles(['admin'.$project->id]);
        }

        return $project;
    }

    /**
     * Store a new collection
     * 
     * @param int $project_id
     * @param \Illuminate\Http\Request  $request
     * @return \App\Models\Collection
     */
    public function store($project_id, Request $request){
        $project = Project::findOrFail($project_id);

        /** @var \App\Models\User $user */
        $user = Auth::user();
        if(!$user->isSuperAdmin() && !$user->hasRole('admin'.$project->id)){
            throw UnauthorizedException::forRoles(['admin'.$project->id]);
        }

        $request->validate([
            'name' => 'required',
            'slug' => ['required','not_in:project-media', Rule::unique('collections')->where(function ($query) use ($project) {
                return $query->where('project_id', $project->id);
            })]
        ],[
            'slug.not_in' => 'This is a reserved slug. Type a different slug.'
        ]);

        $collection = Collection::create([
            'name' => $request->get('name'),
            'slug' => $request->get('slug'),
            'project_id' => $project->id,
        ]);

        $collection->order = $collection->id;
        $collection->save();

        AuditLogger::log('create', 'collection', $collection->id, $collection->name, null, $project->id);

        return response($collection, 200);
    }

    /**
     * Update collection list order
     * 
     * @param int $project_id
     * @param \Illuminate\Http\Request  $request
     * @return void
     */
    public function updateOrder($project_id, Request $request){
        $project = Project::findOrFail($project_id);

        /** @var \App\Models\User $user */
        $user = Auth::user();
        if(!$user->isSuperAdmin() && !$user->hasRole('admin'.$project->id)){
            throw UnauthorizedException::forRoles(['admin'.$project->id]);
        }

        foreach($request->all() as $item){
            $collection =  Collection::where('project_id', $project->id)->where('id', $item['id'])->firstOrFail();

            $collection->order = $item['order'];
            $collection->save();
        }
    }

    /**
     * Get collection by id
     * 
     * @param int $project_id
     * @param int $collection_id
     * @return \App\Models\Project
     * @return \App\Models\Collection
     */
    public function show($project_id, $collection_id){
        $project = Project::with('collections')->findOrFail($project_id);

        /** @var \App\Models\User $user */
        $user = Auth::user();
        if(!$user->isSuperAdmin() && !$user->hasRole('admin'.$project->id)){
            throw UnauthorizedException::forRoles(['admin'.$project->id]);
        }

        $collection = Collection::with(['project', 'project.collections', 'project.collections.fields', 'fields'])
                                    ->where('project_id', $project->id)
                                    ->where('id', $collection_id)->firstOrFail();

        foreach ($collection->fields as $field) {
            $field->validations = json_decode($field->validations);
            $field->options = json_decode($field->options);
        }

        $data['project'] = $project;
        $data['collection'] = $collection;

        return $data;
    }

    /**
     * Update collection
     * 
     * @param int $project_id
     * @param int $collection_id
     * @return \App\Models\Collection
     */
    public function update($project_id, $collection_id, Request $request){
        $project = Project::findOrFail($project_id);

        /** @var \App\Models\User $user */
        $user = Auth::user();
        if(!$user->isSuperAdmin() && !$user->hasRole('admin'.$project->id)){
            throw UnauthorizedException::forRoles(['admin'.$project->id]);
        }

        $collection = Collection::where('project_id', $project->id)->where('id', $collection_id)->firstOrFail();

        $request->validate([
            'name' => 'required',
            'slug' => ['required', 'not_in:project-media', Rule::unique('collections')->where(function ($query) use ($project) {
                return $query->where('project_id', $project->id);
            })->ignore($collection->id)]
        ], [
            'slug.not_in' => 'This is a reserved slug. Type a different slug.'
        ]);

        $collection->update([
            'name' => $request->get('name'),
            'slug' => $request->get('slug'),
        ]);

        AuditLogger::log('update', 'collection', $collection->id, $collection->name, null, $project->id);

        return response($collection, 200);
    }

    /**
     * Export a collection's schema (structure only, no content) as JSON.
     *
     * @param int $project_id
     * @param int $collection_id
     * @return \Illuminate\Http\JsonResponse
     */
    public function exportSchema($project_id, $collection_id){
        $project = Project::findOrFail($project_id);

        /** @var \App\Models\User $user */
        $user = Auth::user();
        if(!$user->isSuperAdmin() && !$user->hasRole('admin'.$project->id)){
            throw UnauthorizedException::forRoles(['admin'.$project->id]);
        }

        $collection = Collection::with('fields')->where('project_id', $project->id)->where('id', $collection_id)->firstOrFail();

        $fields = $collection->fields->sortBy('order')->values()->map(function ($field) {
            return [
                'type' => $field->type,
                'label' => $field->label,
                'name' => $field->name,
                'description' => $field->description,
                'placeholder' => $field->placeholder,
                'options' => json_decode($field->options, true),
                'validations' => json_decode($field->validations, true),
                'order' => $field->order,
            ];
        });

        $schema = [
            'collection' => [
                'name' => $collection->name,
                'slug' => $collection->slug,
            ],
            'fields' => $fields,
        ];

        return response()->json($schema, 200);
    }

    /**
     * Import a collection schema from an uploaded JSON file. Creates the
     * collection (and its fields) if the slug does not exist yet; otherwise
     * updates the existing collection's fields.
     *
     * @param int $project_id
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function importSchema($project_id, Request $request){
        $project = Project::findOrFail($project_id);

        /** @var \App\Models\User $user */
        $user = Auth::user();
        if(!$user->isSuperAdmin() && !$user->hasRole('admin'.$project->id)){
            throw UnauthorizedException::forRoles(['admin'.$project->id]);
        }

        $request->validate([
            'file' => 'required|file|max:2048',
        ]);

        $content = file_get_contents($request->file('file')->getRealPath());
        $data = json_decode($content, true);

        if(!is_array($data) || !isset($data['collection']) || !isset($data['fields'])){
            return response()->json(['message' => 'Invalid schema file. Expected { "collection": {...}, "fields": [...] }.'], 422);
        }

        $collectionName = $data['collection']['name'] ?? null;
        $collectionSlug = $data['collection']['slug'] ?? null;

        if(!$collectionName || !$collectionSlug){
            return response()->json(['message' => 'Schema must include a collection name and slug.'], 422);
        }

        $collection = Collection::where('project_id', $project->id)->where('slug', $collectionSlug)->first();

        if(!$collection){
            $collection = Collection::create([
                'name' => $collectionName,
                'slug' => $collectionSlug,
                'project_id' => $project->id,
            ]);
            $collection->order = $collection->id;
            $collection->save();
        } else {
            $collection->update(['name' => $collectionName]);
        }

        $created = 0;
        $updated = 0;

        foreach($data['fields'] as $fieldData){
            if(!isset($fieldData['name'], $fieldData['label'], $fieldData['type'])){
                continue;
            }

            $existing = CollectionField::where('collection_id', $collection->id)->where('name', $fieldData['name'])->first();

            $payload = [
                'type' => $fieldData['type'],
                'label' => $fieldData['label'],
                'name' => $fieldData['name'],
                'description' => $fieldData['description'] ?? null,
                'placeholder' => $fieldData['placeholder'] ?? null,
                'options' => json_encode($fieldData['options'] ?? []),
                'validations' => json_encode($fieldData['validations'] ?? []),
                'project_id' => $project->id,
                'collection_id' => $collection->id,
            ];

            if($existing){
                $existing->update($payload);
                $updated++;
            } else {
                $field = CollectionField::create($payload);
                $field->order = $field->id;
                $field->save();
                $created++;
            }
        }

        return response()->json([
            'message' => 'Schema imported.',
            'collection_id' => $collection->id,
            'fields_created' => $created,
            'fields_updated' => $updated,
        ], 200);
    }

    /** 
     * Delete collection
     * 
     * @param int $project_id
     * @param int $collection_id
     * @return \Illuminate\Http\Response
     */
    public function delete($project_id, $collection_id){
        $project = Project::findOrFail($project_id);

        /** @var \App\Models\User $user */
        $user = Auth::user();
        if(!$user->isSuperAdmin() && !$user->hasRole('admin'.$project->id)){
            throw UnauthorizedException::forRoles(['admin'.$project->id]);
        }

        $collection = Collection::where('project_id', $project->id)->where('id', $collection_id)->firstOrFail();

        $collection->fields()->delete();
        $collection->content()->forceDelete();
        $collection->meta()->forceDelete();

        if($collection->delete()){
            AuditLogger::log('delete', 'collection', $collection_id, $collection->name ?? null, null, $project->id);
            return response([], 200);
        } else {
            return response([], 404);
        }
    }
}
