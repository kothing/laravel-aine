<?php

namespace Tests\Feature;

use App\Http\Controllers\Admin\ContentController;
use App\Models\AuditLog;
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
 * Integration tests for F5: audit logging (create/update/publish/trash/restore/delete/import).
 */
class AuditLogTest extends TestCase
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
            'validations' => json_encode([
                'required' => ['status' => false, 'message' => null],
                'charcount' => ['status' => false, 'type' => '', 'min' => null, 'max' => null],
                'unique' => ['status' => false, 'message' => null],
            ]),
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

    private function controller(): ContentController
    {
        return new ContentController();
    }

    public function test_store_logs_create_action(): void
    {
        $this->controller()->store($this->project->id, $this->collectionId(), Request::create('/x', 'POST', [
            'locale' => 'en',
            'data' => ['title' => 'New item'],
        ]));

        $log = AuditLog::where('project_id', $this->project->id)->where('action', 'create')->first();

        $this->assertNotNull($log);
        $this->assertSame('content', $log->entity_type);
        $this->assertSame('Content #' . $log->entity_id, $log->entity_label);
        $this->assertSame($this->user->id, $log->user_id);
    }

    public function test_update_logs_update_action(): void
    {
        // Draft content (not published) so the edit does not trigger an unpublish.
        $content = $this->createContent('Original', false);

        $this->controller()->update($this->project->id, $this->collectionId(), $content->id, Request::create('/x', 'POST', [
            'locale' => 'en',
            'data' => ['title' => 'Updated'],
        ]));

        $log = AuditLog::where('project_id', $this->project->id)->where('action', 'update')->latest()->first();

        $this->assertNotNull($log);
        $this->assertSame($content->id, (int) $log->entity_id);
    }

    public function test_update_with_published_flag_logs_publish_action(): void
    {
        $content = $this->createContent('Draft', false);

        $this->controller()->update($this->project->id, $this->collectionId(), $content->id, Request::create('/x', 'POST', [
            'locale' => 'en',
            'data' => ['title' => 'Now live'],
            'published' => true,
        ]));

        $log = AuditLog::where('project_id', $this->project->id)->where('action', 'publish')->latest()->first();

        $this->assertNotNull($log);
        $this->assertSame($content->id, (int) $log->entity_id);
    }

    public function test_update_unpublishing_logs_unpublish_action(): void
    {
        $content = $this->createContent('Live', true);

        $this->controller()->update($this->project->id, $this->collectionId(), $content->id, Request::create('/x', 'POST', [
            'locale' => 'en',
            'data' => ['title' => 'Taken down'],
            'unpublished' => true,
        ]));

        $log = AuditLog::where('project_id', $this->project->id)->where('action', 'unpublish')->latest()->first();

        $this->assertNotNull($log);
        $this->assertSame($content->id, (int) $log->entity_id);
    }

    public function test_publish_selected_logs_publish_action(): void
    {
        $content = $this->createContent('Draft', false);

        $this->controller()->publishSelected($this->project->id, $this->collectionId(), Request::create('/x', 'POST', [
            'selected' => [$content->id],
        ]));

        $log = AuditLog::where('project_id', $this->project->id)->where('action', 'publish')->latest()->first();

        $this->assertNotNull($log);
        $this->assertSame($content->id, (int) $log->entity_id);
    }

    public function test_unpublish_logs_unpublish_action(): void
    {
        $content = $this->createContent('Live', true);

        $this->controller()->unpublish($this->project->id, $this->collectionId(), $content->id);

        $log = AuditLog::where('project_id', $this->project->id)->where('action', 'unpublish')->latest()->first();

        $this->assertNotNull($log);
        $this->assertSame($content->id, (int) $log->entity_id);
    }

    public function test_move_to_trash_logs_trash_action(): void
    {
        $content = $this->createContent('To Trash');

        $this->controller()->moveToTrash($this->project->id, $this->collectionId(), $content->id);

        $log = AuditLog::where('project_id', $this->project->id)->where('action', 'trash')->latest()->first();

        $this->assertNotNull($log);
        $this->assertSame($content->id, (int) $log->entity_id);
    }

    public function test_restore_selected_logs_restore_action(): void
    {
        $content = $this->createContent('To Restore');
        $content->delete();

        $this->controller()->restoreSelected($this->project->id, $this->collectionId(), Request::create('/x', 'POST', [
            'selected' => [$content->id],
        ]));

        $log = AuditLog::where('project_id', $this->project->id)->where('action', 'restore')->latest()->first();

        $this->assertNotNull($log);
        $this->assertSame($content->id, (int) $log->entity_id);
    }

    public function test_delete_logs_delete_action(): void
    {
        $content = $this->createContent('To Delete');
        $content->delete();

        $this->controller()->delete($this->project->id, $this->collectionId(), $content->id);

        $log = AuditLog::where('project_id', $this->project->id)->where('action', 'delete')->latest()->first();

        $this->assertNotNull($log);
        $this->assertSame($content->id, (int) $log->entity_id);
    }

    public function test_import_logs_import_action(): void
    {
        $file = UploadedFile::fake()->createWithContent('import.json', json_encode([
            ['locale' => 'en', 'published' => true, 'data' => ['title' => 'Imported']],
        ]));

        $this->controller()->importContent($this->project->id, $this->collectionId(), Request::create('/x', 'POST', [], [], ['file' => $file]));

        // The summary log has no entity_id (per-item logs carry the content id).
        $log = AuditLog::where('project_id', $this->project->id)->where('action', 'import')->whereNull('entity_id')->first();

        $this->assertNotNull($log);
        $this->assertStringContainsString('1 content item(s)', $log->entity_label);
        $this->assertNull($log->entity_id);
    }

    public function test_export_logs_export_action(): void
    {
        $this->createContent('Export Me');

        $this->controller()->exportContent($this->project->id, $this->collectionId(), Request::create('/x', 'GET', ['format' => 'json']));

        $log = AuditLog::where('project_id', $this->project->id)->where('action', 'export')->latest()->first();

        $this->assertNotNull($log);
        $this->assertSame('json', $log->details['format']);
    }

    public function test_audit_logs_api_lists_logs(): void
    {
        $this->createContent('API Item');

        $response = $this->getJson('/admin-api/audit-logs/project/' . $this->project->id);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => [
                '*' => [
                    'id', 'action', 'entity_type', 'entity_id', 'entity_label',
                    'user' => ['id', 'name', 'email'],
                    'created_at',
                ],
            ],
            'meta' => ['total'],
        ]);
    }

    public function test_audit_logs_api_filters_by_action(): void
    {
        // Create a log entry through the controller so an audit record exists.
        $this->controller()->store($this->project->id, $this->collectionId(), Request::create('/x', 'POST', [
            'locale' => 'en',
            'data' => ['title' => 'Filtered'],
        ]));

        $response = $this->getJson('/admin-api/audit-logs/project/' . $this->project->id . '?action=create');

        $response->assertStatus(200);
        $this->assertCount(1, $response->json('data'));
        $this->assertSame('create', $response->json('data.0.action'));
    }

    public function test_audit_logs_api_denies_non_project_admin(): void
    {
        $other = User::create(['name' => 'Other', 'email' => 'other@test.local', 'password' => bcrypt('password')]);
        $editorRole = Role::firstOrCreate(['name' => 'editor' . $this->project->id]);
        $other->assignRole($editorRole);
        $this->actingAs($other);

        $this->getJson('/admin-api/audit-logs/project/' . $this->project->id)->assertStatus(403);
    }
}
