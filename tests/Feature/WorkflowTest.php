<?php

namespace Tests\Feature;

use App\Http\Controllers\Admin\ContentController;
use App\Http\Controllers\Admin\WorkflowController;
use App\Models\AuditLog;
use App\Models\Collection;
use App\Models\CollectionField;
use App\Models\Content;
use App\Models\ContentMeta;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Spatie\Permission\Exceptions\UnauthorizedException;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class WorkflowTest extends TestCase
{
    use RefreshDatabase;

    private Project $project;
    private Collection $collection;
    private User $admin;
    private User $editor;
    private int $collectionId;

    protected function setUp(): void
    {
        parent::setUp();

        $this->project = Project::create(['name' => 'Demo', 'slug' => 'demo', 'status' => 1, 'public_api' => 1, 'workflow_enabled' => true]);

        $this->collection = Collection::create(['name' => 'Articles', 'slug' => 'articles', 'project_id' => $this->project->id]);
        $this->collectionId = $this->collection->id;

        CollectionField::create([
            'type' => 'text', 'label' => 'Title', 'name' => 'title',
            'options' => '{}', 'validations' => json_encode([
                'required' => ['status' => false, 'message' => null],
                'charcount' => ['status' => false, 'type' => '', 'min' => null, 'max' => null],
                'unique' => ['status' => false, 'message' => null],
            ]),
            'project_id' => $this->project->id, 'collection_id' => $this->collection->id, 'order' => 1,
        ]);

        $this->admin = User::create(['name' => 'Admin', 'email' => 'admin@test.local', 'password' => bcrypt('password')]);
        Role::firstOrCreate(['name' => 'super_admin']);
        $this->admin->assignRole('super_admin');

        Role::firstOrCreate(['name' => 'editor' . $this->project->id]);
        $this->editor = User::create(['name' => 'Editor', 'email' => 'editor@test.local', 'password' => bcrypt('password')]);
        $this->editor->assignRole('editor' . $this->project->id);
    }

    private function makeDraft(): Content
    {
        $content = Content::create([
            'project_id' => $this->project->id, 'collection_id' => $this->collectionId,
            'locale' => 'en', 'published_at' => null, 'workflow_state' => 'draft',
        ]);
        ContentMeta::create(['project_id' => $this->project->id, 'collection_id' => $this->collectionId, 'content_id' => $content->id, 'field_name' => 'title', 'value' => 'Draft']);
        return $content;
    }

    public function test_submit_review_moves_draft_to_in_review(): void
    {
        $content = $this->makeDraft();
        $this->actingAs($this->editor);

        (new WorkflowController())->submitReview($this->project->id, $this->collectionId, $content->id);

        $this->assertSame('in_review', $content->fresh()->workflow_state);
    }

    public function test_submit_review_requires_project_role(): void
    {
        $content = $this->makeDraft();
        $stranger = User::create(['name' => 's', 'email' => 's@t.local', 'password' => bcrypt('password')]);
        $this->actingAs($stranger);

        $this->expectException(UnauthorizedException::class);
        (new WorkflowController())->submitReview($this->project->id, $this->collectionId, $content->id);
    }

    public function test_submit_review_rejects_already_published(): void
    {
        $content = $this->makeDraft();
        $content->workflow_state = 'published';
        $content->published_at = now();
        $content->save();
        $this->actingAs($this->editor);

        $resp = (new WorkflowController())->submitReview($this->project->id, $this->collectionId, $content->id);
        $this->assertSame(422, $resp->getStatusCode());
    }

    public function test_approve_publishes_in_review_content(): void
    {
        $content = $this->makeDraft();
        $content->workflow_state = 'in_review';
        $content->save();
        $this->actingAs($this->admin);

        (new WorkflowController())->approve($this->project->id, $this->collectionId, $content->id);

        $fresh = $content->fresh();
        $this->assertSame('published', $fresh->workflow_state);
        $this->assertNotNull($fresh->published_at);
    }

    public function test_approve_requires_reviewer_role(): void
    {
        $content = $this->makeDraft();
        $content->workflow_state = 'in_review';
        $content->save();
        $this->actingAs($this->editor);

        $this->expectException(UnauthorizedException::class);
        (new WorkflowController())->approve($this->project->id, $this->collectionId, $content->id);
    }

    public function test_approve_only_in_review(): void
    {
        $content = $this->makeDraft();
        $this->actingAs($this->admin);

        $resp = (new WorkflowController())->approve($this->project->id, $this->collectionId, $content->id);
        $this->assertSame(422, $resp->getStatusCode());
    }

    public function test_reject_returns_to_rejected_with_reason(): void
    {
        $content = $this->makeDraft();
        $content->workflow_state = 'in_review';
        $content->save();
        $this->actingAs($this->admin);

        (new WorkflowController())->reject(
            new Request(['reason' => 'Please rewrite the intro']),
            $this->project->id, $this->collectionId, $content->id
        );

        $fresh = $content->fresh();
        $this->assertSame('rejected', $fresh->workflow_state);
        $this->assertSame('Please rewrite the intro', $fresh->reviewer_comment);
        $this->assertNull($fresh->published_at);
    }

    public function test_workflow_enforced_store_rejects_direct_publish(): void
    {
        $this->actingAs($this->editor);
        $resp = (new ContentController())->store($this->project->id, $this->collectionId, Request::create('/x', 'POST', [
            'locale' => 'en', 'published' => 1, 'data' => ['title' => 'New'],
        ]));
        $this->assertSame(422, $resp->getStatusCode());
    }

    public function test_workflow_enforced_update_rejects_direct_publish(): void
    {
        $content = $this->makeDraft();
        $this->actingAs($this->editor);
        $resp = (new ContentController())->update($this->project->id, $this->collectionId, $content->id, Request::create('/x', 'POST', [
            'locale' => 'en', 'published' => 1, 'data' => ['title' => 'Updated'],
        ]));
        $this->assertSame(422, $resp->getStatusCode());
    }

    public function test_workflow_disabled_allows_direct_publish(): void
    {
        $this->project->update(['workflow_enabled' => false]);
        $content = $this->makeDraft();
        $this->actingAs($this->editor);
        (new ContentController())->update($this->project->id, $this->collectionId, $content->id, Request::create('/x', 'POST', [
            'locale' => 'en', 'published' => 1, 'data' => ['title' => 'Updated'],
        ]));
        // No exception = pass
        $this->assertSame('Updated', ContentMeta::where('content_id', $content->id)->where('field_name', 'title')->value('value'));
    }

    public function test_workflow_actions_are_audited(): void
    {
        $content = $this->makeDraft();
        $content->workflow_state = 'in_review';
        $content->save();
        $this->actingAs($this->admin);

        (new WorkflowController())->approve($this->project->id, $this->collectionId, $content->id);

        $this->assertDatabaseHas('audit_logs', [
            'entity_type' => 'content',
            'entity_id' => $content->id,
            'action' => 'publish',
        ]);
    }
}