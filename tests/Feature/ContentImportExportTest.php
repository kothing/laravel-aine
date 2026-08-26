<?php

namespace Tests\Feature;

use App\Http\Controllers\Admin\ContentController;
use App\Models\Collection;
use App\Models\CollectionField;
use App\Models\Content;
use App\Models\ContentMeta;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Integration tests for F4: content import/export (JSON and CSV).
 */
class ContentImportExportTest extends TestCase
{
    use RefreshDatabase;

    private Project $project;
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::create(['name' => 'Admin', 'email' => 'admin@test.local', 'password' => bcrypt('password')]);
        $role = Role::firstOrCreate(['name' => 'super_admin']);
        $this->user->assignRole($role);
        $this->actingAs($this->user);

        $this->project = Project::create(['name' => 'Demo', 'slug' => 'demo', 'status' => 1, 'public_api' => 1]);

        $collection = Collection::create(['name' => 'Articles', 'slug' => 'articles', 'project_id' => $this->project->id]);

        CollectionField::create([
            'type' => 'text', 'label' => 'Title', 'name' => 'title',
            'options' => json_encode(['text' => ['type' => 1]]),
            'validations' => json_encode(['required' => ['status' => false, 'message' => null]]),
            'project_id' => $this->project->id, 'collection_id' => $collection->id, 'order' => 1,
        ]);
    }

    private function collectionId(): int
    {
        return Collection::where('project_id', $this->project->id)->first()->id;
    }

    private function createContent(string $title, bool $published = true): Content
    {
        $content = Content::create([
            'project_id' => $this->project->id,
            'collection_id' => $this->collectionId(),
            'locale' => 'en',
            'published_at' => $published ? now() : null,
        ]);

        ContentMeta::create([
            'project_id' => $this->project->id,
            'collection_id' => $this->collectionId(),
            'content_id' => $content->id,
            'field_name' => 'title',
            'value' => $title,
        ]);

        return $content;
    }

    public function test_export_json_returns_all_rows_with_meta(): void
    {
        $this->createContent('Post One');
        $this->createContent('Post Two', false);

        $response = $this->controller()->exportContent($this->project->id, $this->collectionId(), Request::create('/x', 'GET', ['format' => 'json']));

        $this->assertSame(200, $response->getStatusCode());
        $rows = json_decode($response->getContent(), true);

        $this->assertCount(2, $rows);
        $this->assertSame('Post One', $rows[0]['data']['title']);
        $this->assertSame('en', $rows[0]['locale']);
        $this->assertNotSame('', $rows[0]['published_at']);
    }

    public function test_export_csv_has_headers_and_rows(): void
    {
        $this->createContent('CSV Title');

        $response = $this->controller()->exportContent($this->project->id, $this->collectionId(), Request::create('/x', 'GET', ['format' => 'csv']));

        $this->assertSame(200, $response->getStatusCode());
        $this->assertStringContainsString('text/csv', $response->headers->get('Content-Type'));

        $csv = $response->getContent();
        $this->assertStringContainsString('locale', $csv);
        $this->assertStringContainsString('published_at', $csv);
        $this->assertStringContainsString('title', $csv);
        $this->assertStringContainsString('CSV Title', $csv);
    }

    public function test_export_unsupported_format_is_rejected(): void
    {
        $response = $this->controller()->exportContent($this->project->id, $this->collectionId(), Request::create('/x', 'GET', ['format' => 'xml']));

        $this->assertSame(422, $response->getStatusCode());
    }

    public function test_import_json_creates_content(): void
    {
        $file = UploadedFile::fake()->createWithContent('import.json', json_encode([
            ['locale' => 'en', 'published' => true, 'data' => ['title' => 'Imported One']],
            ['locale' => 'en', 'published' => false, 'data' => ['title' => 'Imported Draft']],
        ]));

        $request = Request::create('/x', 'POST', [], [], ['file' => $file]);

        $response = $this->controller()->importContent($this->project->id, $this->collectionId(), $request);

        $this->assertSame(200, $response->getStatusCode());
        $json = json_decode($response->getContent(), true);
        $this->assertSame(2, $json['created']);

        $this->assertDatabaseHas('content', ['collection_id' => $this->collectionId(), 'published_at' => now()->format('Y-m-d H:i:s')]);
        $this->assertDatabaseHas('content_meta', ['field_name' => 'title', 'value' => 'Imported One']);

        //An initial revision should be recorded for imported rows
        $this->assertDatabaseHas('content_revisions', ['note' => 'Imported']);
    }

    public function test_import_csv_creates_content(): void
    {
        $csv = "locale,published_at,title\nen,,CSV Imported\nen,,CSV Imported Draft\n";
        $file = UploadedFile::fake()->createWithContent('import.csv', $csv);

        $request = Request::create('/x', 'POST', [], [], ['file' => $file]);

        $response = $this->controller()->importContent($this->project->id, $this->collectionId(), $request);

        $this->assertSame(200, $response->getStatusCode());
        $json = json_decode($response->getContent(), true);
        $this->assertSame(2, $json['created']);

        $this->assertDatabaseHas('content_meta', ['field_name' => 'title', 'value' => 'CSV Imported']);
    }

    public function test_import_invalid_file_type_is_rejected(): void
    {
        $file = UploadedFile::fake()->createWithContent('data.txt', 'plain text');

        $request = Request::create('/x', 'POST', [], [], ['file' => $file]);

        $response = $this->controller()->importContent($this->project->id, $this->collectionId(), $request);

        $this->assertSame(422, $response->getStatusCode());
    }

    public function test_import_without_file_is_rejected(): void
    {
        $response = $this->controller()->importContent($this->project->id, $this->collectionId(), Request::create('/x', 'POST'));

        $this->assertSame(422, $response->getStatusCode());
    }

    public function test_non_project_member_cannot_export(): void
    {
        $this->expectException(\Spatie\Permission\Exceptions\UnauthorizedException::class);

        $regular = User::create(['name' => 'Regular', 'email' => 'regular@test.local', 'password' => bcrypt('password')]);
        $this->actingAs($regular);

        $this->controller()->exportContent($this->project->id, $this->collectionId(), Request::create('/x', 'GET', ['format' => 'json']));
    }

    private function controller(): ContentController
    {
        return new ContentController();
    }
}
