<?php

namespace Tests\Feature;

use App\Models\Collection;
use App\Models\CollectionField;
use App\Models\Content;
use App\Models\ContentMeta;
use App\Models\Project;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Integration tests for F2: full-text search API.
 * Searches content_meta values and only exposes published content by default.
 */
class ContentSearchTest extends TestCase
{
    use RefreshDatabase;

    private Project $project;

    protected function setUp(): void
    {
        parent::setUp();

        $this->project = Project::create([
            'name' => 'Demo',
            'slug' => 'demo',
            'status' => 1,
            'public_api' => 1,
        ]);

        $collection = Collection::create(['name' => 'Articles', 'slug' => 'articles', 'project_id' => $this->project->id]);

        CollectionField::create([
            'type' => 'text', 'label' => 'Title', 'name' => 'title',
            'options' => json_encode(['text' => ['type' => 1]]),
            'validations' => json_encode(['required' => ['status' => false, 'message' => null]]),
            'project_id' => $this->project->id, 'collection_id' => $collection->id, 'order' => 1,
        ]);
        CollectionField::create([
            'type' => 'text', 'label' => 'Body', 'name' => 'body',
            'options' => json_encode(['text' => ['type' => 1]]),
            'validations' => json_encode(['required' => ['status' => false, 'message' => null]]),
            'project_id' => $this->project->id, 'collection_id' => $collection->id, 'order' => 2,
        ]);

        // Two published articles, one draft
        $publishedA = $this->createContent($collection->id, now(), ['title' => 'Hello Laravel World', 'body' => 'An intro to the framework.']);
        $publishedB = $this->createContent($collection->id, now(), ['title' => 'Another Post', 'body' => 'This one mentions laravel too.']);
        $this->createContent($collection->id, null, ['title' => 'Laravel Draft', 'body' => 'Not visible yet.']);
    }

    private function createContent(int $collectionId, $publishedAt, array $fields): Content
    {
        $content = Content::create([
            'project_id' => $this->project->id,
            'collection_id' => $collectionId,
            'locale' => 'en',
            'published_at' => $publishedAt,
        ]);

        foreach ($fields as $name => $value) {
            ContentMeta::create([
                'project_id' => $this->project->id,
                'collection_id' => $collectionId,
                'content_id' => $content->id,
                'field_name' => $name,
                'value' => $value,
            ]);
        }

        return $content;
    }

    public function test_search_returns_matching_published_content(): void
    {
        $response = $this->get('/api/project/demo/articles/search?query=laravel');

        $response->assertStatus(200);
        $response->assertJsonPath('success', true);
        $response->assertJsonPath('total', 2);

        $data = $response->json('data');
        $this->assertCount(2, $data);

        $titles = array_column($data, 'title');
        $this->assertContains('Hello Laravel World', $titles);
        $this->assertContains('Another Post', $titles);
    }

    public function test_search_excludes_drafts_by_default(): void
    {
        $response = $this->get('/api/project/demo/articles/search?query=laravel');

        $response->assertStatus(200);
        $this->assertSame(2, $response->json('total'));

        $titles = array_column($response->json('data'), 'title');
        $this->assertNotContains('Laravel Draft', $titles);
    }

    public function test_search_can_query_only_drafts(): void
    {
        $response = $this->get('/api/project/demo/articles/search?query=laravel&state=only_draft');

        $response->assertStatus(200);
        $this->assertSame(1, $response->json('total'));

        $titles = array_column($response->json('data'), 'title');
        $this->assertContains('Laravel Draft', $titles);
    }

    public function test_search_no_results_returns_empty_list(): void
    {
        $response = $this->get('/api/project/demo/articles/search?query=zzzznothing');

        $response->assertStatus(200);
        $response->assertJsonPath('success', true);
        $response->assertJsonPath('total', 0);
        $this->assertSame([], $response->json('data'));
    }

    public function test_search_short_query_is_rejected(): void
    {
        $response = $this->get('/api/project/demo/articles/search?query=a');

        $response->assertStatus(422);
        $response->assertJsonPath('success', false);
    }

    public function test_search_missing_query_is_rejected(): void
    {
        $response = $this->get('/api/project/demo/articles/search');

        $response->assertStatus(422);
        $response->assertJsonPath('success', false);
    }

    public function test_search_respects_limit(): void
    {
        $response = $this->get('/api/project/demo/articles/search?query=laravel&limit=1');

        $response->assertStatus(200);
        $this->assertSame(2, $response->json('total'));
        $this->assertCount(1, $response->json('data'));
        $this->assertSame(1, $response->json('limit'));
    }

    public function test_search_works_with_project_uuid(): void
    {
        //The UUID routes require a token via validate.project.access + auth:sanctum
        //(which is outside the scope of this test), so exercise the controller directly.
        $controller = new \App\Http\Controllers\API\ContentController();
        $request = \Illuminate\Http\Request::create('/api/' . $this->project->uuid . '/articles/search', 'GET', ['query' => 'laravel']);
        $request->attributes->set('resolved_project', $this->project);

        $response = $controller->searchContentByUuid($this->project->uuid, 'articles', $request);

        $this->assertSame(200, $response->getStatusCode());
        $json = json_decode($response->getContent(), true);
        $this->assertSame(2, $json['total']);
    }

    public function test_search_route_is_not_captured_as_content_id(): void
    {
        //The "search" segment must match the dedicated route, not {slug_id}
        $response = $this->get('/api/project/demo/articles/search?query=laravel');
        $response->assertStatus(200);
        $response->assertJsonPath('success', true);
        $this->assertIsArray($response->json('data'));
    }
}
