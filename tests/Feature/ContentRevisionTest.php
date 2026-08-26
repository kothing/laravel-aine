<?php

namespace Tests\Feature;

use App\Http\Controllers\Admin\ContentController;
use App\Models\Collection;
use App\Models\CollectionField;
use App\Models\Content;
use App\Models\ContentMeta;
use App\Models\ContentRevision;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Integration tests for F1: content revision history.
 * Every content create/update stores a snapshot; revisions can be
 * listed and restored from the admin API.
 */
class ContentRevisionTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::create([
            'name' => 'Admin',
            'email' => 'admin@test.local',
            'password' => bcrypt('password'),
        ]);
        $role = Role::firstOrCreate(['name' => 'super_admin']);
        $this->user->assignRole($role);
        $this->actingAs($this->user);
    }

    private function createFixture(): Project
    {
        $project = Project::create(['name' => 'Demo', 'slug' => 'demo', 'status' => 1, 'public_api' => 1]);

        $collection = Collection::create(['name' => 'Articles', 'slug' => 'articles', 'project_id' => $project->id]);

        $validations = json_encode([
            'required' => ['status' => false, 'message' => null],
            'charcount' => ['status' => false, 'type' => '', 'min' => null, 'max' => null],
            'unique' => ['status' => false, 'message' => null],
        ]);

        CollectionField::create([
            'type' => 'text', 'label' => 'Title', 'name' => 'title',
            'options' => json_encode(['text' => ['type' => 1]]),
            'validations' => $validations,
            'project_id' => $project->id, 'collection_id' => $collection->id, 'order' => 1,
        ]);
        CollectionField::create([
            'type' => 'text', 'label' => 'Body', 'name' => 'body',
            'options' => json_encode(['text' => ['type' => 1]]),
            'validations' => $validations,
            'project_id' => $project->id, 'collection_id' => $collection->id, 'order' => 2,
        ]);

        return $project;
    }

    private function newController(): ContentController
    {
        return new ContentController();
    }

    public function test_store_creates_initial_revision(): void
    {
        $project = $this->createFixture();
        $collection = Collection::where('project_id', $project->id)->first();

        $request = Request::create('/admin-api/content/store/1/1', 'POST', [
            'locale' => 'en',
            'published' => true,
            'data' => ['title' => 'Hello World'],
        ]);

        $this->newController()->store($project->id, $collection->id, $request);
        $content = Content::where('project_id', $project->id)->first();

        $this->assertDatabaseHas('content_revisions', [
            'content_id' => $content->id,
            'note' => 'Created',
        ]);

        $revision = ContentRevision::where('content_id', $content->id)->first();
        $this->assertSame(['title' => 'Hello World'], $revision->data);
    }

    public function test_update_creates_revision_with_new_snapshot(): void
    {
        $project = $this->createFixture();
        $collection = Collection::where('project_id', $project->id)->first();

        $this->newController()->store($project->id, $collection->id,
            Request::create('/x', 'POST', ['locale' => 'en', 'data' => ['title' => 'Hello World']]));
        $content = Content::where('project_id', $project->id)->first();

        $this->newController()->update($project->id, $collection->id, $content->id,
            Request::create('/x', 'POST', ['data' => ['title' => 'Hello World v2', 'body' => 'New body']]));

        $revisions = ContentRevision::where('content_id', $content->id)->orderByDesc('id')->get();

        $this->assertCount(2, $revisions);
        $this->assertSame('Updated', $revisions->first()->note);
        $this->assertSame(['title' => 'Hello World v2', 'body' => 'New body'], $revisions->first()->data);
    }

    public function test_revisions_lists_all_versions(): void
    {
        $project = $this->createFixture();
        $collection = Collection::where('project_id', $project->id)->first();

        $this->newController()->store($project->id, $collection->id,
            Request::create('/x', 'POST', ['locale' => 'en', 'data' => ['title' => 'v1']]));
        $content = Content::where('project_id', $project->id)->first();
        $this->newController()->update($project->id, $collection->id, $content->id,
            Request::create('/x', 'POST', ['data' => ['title' => 'v2']]));

        $response = $this->newController()->revisions($project->id, $collection->id, $content->id);

        $json = json_decode($response->getContent(), true);

        $this->assertCount(2, $json);
        $this->assertSame('Updated', $json[0]['note']);
        $this->assertSame('Created', $json[1]['note']);
        $this->assertSame('Admin', $json[0]['user']['name'] ?? null);
    }

    public function test_restore_revision_rolls_back_meta(): void
    {
        $project = $this->createFixture();
        $collection = Collection::where('project_id', $project->id)->first();

        $this->newController()->store($project->id, $collection->id,
            Request::create('/x', 'POST', ['locale' => 'en', 'data' => ['title' => 'Original', 'body' => 'Original body']]));
        $content = Content::where('project_id', $project->id)->first();
        $this->newController()->update($project->id, $collection->id, $content->id,
            Request::create('/x', 'POST', ['data' => ['title' => 'Changed', 'body' => 'Changed body']]));

        $firstRevision = ContentRevision::where('content_id', $content->id)->orderBy('id')->first();

        $response = $this->newController()->restoreRevision($project->id, $collection->id, $content->id, $firstRevision->id);
        $this->assertSame(200, $response->getStatusCode());

        $meta = ContentMeta::where('content_id', $content->id)->pluck('value', 'field_name');
        $this->assertSame('Original', $meta['title']);
        $this->assertSame('Original body', $meta['body']);

        // A "Restored" revision must be recorded on top
        $this->assertDatabaseHas('content_revisions', [
            'content_id' => $content->id,
            'note' => 'Restored from revision #'.$firstRevision->id,
        ]);
    }

    public function test_restore_removes_fields_missing_from_snapshot(): void
    {
        $project = $this->createFixture();
        $collection = Collection::where('project_id', $project->id)->first();

        $this->newController()->store($project->id, $collection->id,
            Request::create('/x', 'POST', ['locale' => 'en', 'data' => ['title' => 'A', 'body' => 'B']]));
        $content = Content::where('project_id', $project->id)->first();
        $this->newController()->update($project->id, $collection->id, $content->id,
            Request::create('/x', 'POST', ['data' => ['title' => 'A2', 'body' => 'B2', 'extra' => 'X']]));

        $this->assertDatabaseHas('content_meta', ['content_id' => $content->id, 'field_name' => 'extra']);

        $firstRevision = ContentRevision::where('content_id', $content->id)->orderBy('id')->first();
        $this->newController()->restoreRevision($project->id, $collection->id, $content->id, $firstRevision->id);

        $this->assertDatabaseMissing('content_meta', ['content_id' => $content->id, 'field_name' => 'extra']);
        $this->assertSame('A', ContentMeta::where('content_id', $content->id)->where('field_name', 'title')->value('value'));
    }

    public function test_non_project_member_cannot_access_revisions(): void
    {
        $this->expectException(\Spatie\Permission\Exceptions\UnauthorizedException::class);

        $project = $this->createFixture();
        $collection = Collection::where('project_id', $project->id)->first();
        $this->newController()->store($project->id, $collection->id,
            Request::create('/x', 'POST', ['locale' => 'en', 'data' => ['title' => 'A']]));

        // A regular (non-super-admin, no project role) user
        $regular = User::create([
            'name' => 'Regular',
            'email' => 'regular@test.local',
            'password' => bcrypt('password'),
        ]);
        $this->actingAs($regular);

        $content = Content::where('project_id', $project->id)->first();
        $this->newController()->revisions($project->id, $collection->id, $content->id);
    }
}
