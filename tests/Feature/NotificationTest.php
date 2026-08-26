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
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Tests for F6-3: in-app notifications on content events.
 */
class NotificationTest extends TestCase
{
    use RefreshDatabase;

    private Project $project;
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::create(['name' => 'Admin', 'email' => 'admin@notify.test', 'password' => bcrypt('password')]);
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

    private function createContent(string $title, bool $published = false): Content
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

    public function test_publishing_content_notifies_project_admins(): void
    {
        $content = $this->createContent('Publish Me');

        $this->controller()->publishSelected($this->project->id, $this->collectionId(), Request::create('/x', 'POST', [
            'selected' => [$content->id],
        ]));

        $this->user->refresh();
        $notification = $this->user->notifications()->first();

        $this->assertNotNull($notification);
        $this->assertSame('publish', $notification->data['action']);
        $this->assertSame($content->id, $notification->data['content_id']);
    }

    public function test_moving_to_trash_notifies_project_admins(): void
    {
        $content = $this->createContent('Trash Me');

        $this->controller()->moveToTrash($this->project->id, $this->collectionId(), $content->id);

        $this->user->refresh();
        $notification = $this->user->notifications()->first();

        $this->assertNotNull($notification);
        $this->assertSame('trash', $notification->data['action']);
        $this->assertSame($content->id, $notification->data['content_id']);
    }

    public function test_plain_edit_does_not_notify(): void
    {
        $content = $this->createContent('Edit Me');

        $this->controller()->update($this->project->id, $this->collectionId(), $content->id, Request::create('/x', 'POST', [
            'locale' => 'en',
            'data' => ['title' => 'Edited'],
        ]));

        $this->user->refresh();
        $this->assertCount(0, $this->user->notifications);
    }

    public function test_notifications_api_lists_and_counts_unread(): void
    {
        $content = $this->createContent('API Notify');
        $this->controller()->publishSelected($this->project->id, $this->collectionId(), Request::create('/x', 'POST', [
            'selected' => [$content->id],
        ]));

        $this->user->refresh();

        $response = $this->getJson('/admin-api/notifications');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => [
                '*' => ['id', 'data', 'read_at', 'created_at'],
            ],
            'unread_count',
        ]);
        $this->assertSame(1, $response->json('unread_count'));
    }

    public function test_mark_read_endpoint(): void
    {
        $content = $this->createContent('Read Me');
        $this->controller()->publishSelected($this->project->id, $this->collectionId(), Request::create('/x', 'POST', [
            'selected' => [$content->id],
        ]));

        $this->user->refresh();
        $notificationId = $this->user->notifications()->first()->id;

        $response = $this->postJson('/admin-api/notifications/read', [
            'ids' => [$notificationId],
        ]);

        $response->assertStatus(200);
        $this->assertSame(0, $this->user->unreadNotifications()->count());
    }

    public function test_mark_all_read_endpoint(): void
    {
        $content = $this->createContent('Read All');
        $this->controller()->publishSelected($this->project->id, $this->collectionId(), Request::create('/x', 'POST', [
            'selected' => [$content->id],
        ]));

        $this->user->refresh();

        $response = $this->postJson('/admin-api/notifications/read');

        $response->assertStatus(200);
        $this->assertSame(0, $this->user->unreadNotifications()->count());
    }
}
