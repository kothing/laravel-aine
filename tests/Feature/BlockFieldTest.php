<?php

namespace Tests\Feature;

use App\Http\Resources\ContentResource;
use App\Models\Collection;
use App\Models\CollectionField;
use App\Models\Content;
use App\Models\ContentMeta;
use App\Models\Project;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BlockFieldTest extends TestCase
{
    use RefreshDatabase;

    public function test_block_field_decodes_json_in_repeatable_mode(): void
    {
        $project = Project::create(['name' => 'Demo', 'slug' => 'demo', 'status' => 1]);
        $collection = Collection::create(['name' => 'Articles', 'slug' => 'articles', 'project_id' => $project->id]);
        CollectionField::create([
            'type' => 'block', 'label' => 'Sections', 'name' => 'sections',
            'options' => json_encode(['repeatable' => true, 'fields' => [
                ['name' => 'heading', 'type' => 'text'],
                ['name' => 'body', 'type' => 'richtext'],
            ]]),
            'validations' => '{}',
            'project_id' => $project->id, 'collection_id' => $collection->id, 'order' => 1,
        ]);

        $content = Content::create(['project_id' => $project->id, 'collection_id' => $collection->id, 'locale' => 'en']);
        ContentMeta::create(['project_id' => $project->id, 'collection_id' => $collection->id, 'content_id' => $content->id, 'field_name' => 'sections', 'value' => json_encode(['heading' => 'Hello', 'body' => '<p>World</p>'])]);
        ContentMeta::create(['project_id' => $project->id, 'collection_id' => $collection->id, 'content_id' => $content->id, 'field_name' => 'sections', 'value' => json_encode(['heading' => 'Second', 'body' => '<p>Block</p>'])]);

        $resource = new ContentResource($content->load(['meta', 'collection.fields']));
        $data = $resource->toArray(request());

        $this->assertIsArray($data['sections']);
        $this->assertCount(2, $data['sections']);
        $this->assertSame('Hello', $data['sections'][0]['heading']);
        $this->assertSame('<p>World</p>', $data['sections'][0]['body']);
    }

    public function test_block_field_decodes_json_in_single_mode(): void
    {
        $project = Project::create(['name' => 'Demo', 'slug' => 'demo', 'status' => 1]);
        $collection = Collection::create(['name' => 'Pages', 'slug' => 'pages', 'project_id' => $project->id]);
        CollectionField::create([
            'type' => 'block', 'label' => 'Hero', 'name' => 'hero',
            'options' => json_encode(['fields' => [
                ['name' => 'title', 'type' => 'text'],
                ['name' => 'subtitle', 'type' => 'text'],
            ]]),
            'validations' => '{}',
            'project_id' => $project->id, 'collection_id' => $collection->id, 'order' => 1,
        ]);

        $content = Content::create(['project_id' => $project->id, 'collection_id' => $collection->id, 'locale' => 'en']);
        ContentMeta::create(['project_id' => $project->id, 'collection_id' => $collection->id, 'content_id' => $content->id, 'field_name' => 'hero', 'value' => json_encode(['title' => 'Welcome', 'subtitle' => 'to our site'])]);

        $resource = new ContentResource($content->load(['meta', 'collection.fields']));
        $data = $resource->toArray(request());

        $this->assertIsArray($data['hero']);
        $this->assertSame('Welcome', $data['hero']['title']);
        $this->assertSame('to our site', $data['hero']['subtitle']);
    }
}