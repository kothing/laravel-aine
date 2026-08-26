<?php

namespace Tests\Feature;

use App\Events\ContentPublished;
use App\Http\Controllers\Admin\ContentController;
use App\Models\Collection;
use App\Models\CollectionField;
use App\Models\Content;
use App\Models\ContentMeta;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Event;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Integration tests for F3: scheduled publishing.
 * Content with a future scheduled_at stays a draft until the due time,
 * then a scheduler command publishes it automatically.
 */
class ScheduledPublishingTest extends TestCase
{
    use RefreshDatabase;

    private Project $project;

    protected function setUp(): void
    {
        parent::setUp();

        $this->project = Project::create(['name' => 'Demo', 'slug' => 'demo', 'status' => 1, 'public_api' => 1]);

        $collection = Collection::create(['name' => 'Articles', 'slug' => 'articles', 'project_id' => $this->project->id]);

        $validations = json_encode([
            'required' => ['status' => false, 'message' => null],
            'charcount' => ['status' => false, 'type' => '', 'min' => null, 'max' => null],
            'unique' => ['status' => false, 'message' => null],
        ]);

        CollectionField::create([
            'type' => 'text', 'label' => 'Title', 'name' => 'title',
            'options' => json_encode(['text' => ['type' => 1]]),
            'validations' => $validations,
            'project_id' => $this->project->id, 'collection_id' => $collection->id, 'order' => 1,
        ]);
    }

    private function createScheduledContent(?string $scheduledAt): Content
    {
        return Content::create([
            'project_id' => $this->project->id,
            'collection_id' => Collection::where('project_id', $this->project->id)->first()->id,
            'locale' => 'en',
            'published_at' => null,
            'scheduled_at' => $scheduledAt,
        ]);
    }

    public function test_due_content_is_published_by_the_scheduler(): void
    {
        $content = $this->createScheduledContent(now()->subMinute()->toDateTimeString());

        $this->artisan('aine:publish_scheduled')->assertExitCode(0);

        $content->refresh();
        $this->assertNotNull($content->published_at);
        $this->assertNull($content->scheduled_at);
    }

    public function test_future_content_is_not_published_yet(): void
    {
        $content = $this->createScheduledContent(now()->addHour()->toDateTimeString());

        $this->artisan('aine:publish_scheduled')->assertExitCode(0);

        $content->refresh();
        $this->assertNull($content->published_at);
        $this->assertNotNull($content->scheduled_at);
    }

    public function test_scheduler_dispatches_publish_event(): void
    {
        Event::fake();

        $this->createScheduledContent(now()->subMinute()->toDateTimeString());

        $this->artisan('aine:publish_scheduled')->assertExitCode(0);

        Event::assertDispatched(ContentPublished::class);
    }

    public function test_update_can_set_future_schedule_keeping_draft(): void
    {
        $user = User::create(['name' => 'Admin', 'email' => 'admin@test.local', 'password' => bcrypt('password')]);
        $role = Role::firstOrCreate(['name' => 'super_admin']);
        $user->assignRole($role);
        $this->actingAs($user);

        $collection = Collection::where('project_id', $this->project->id)->first();
        $content = $this->createScheduledContent(null);

        $future = now()->addDays(2)->format('Y-m-d H:i:s');

        $controller = new ContentController();
        $controller->update($this->project->id, $collection->id, $content->id,
            Request::create('/x', 'POST', [
                'locale' => 'en',
                'data' => ['title' => 'Scheduled post'],
                'scheduled_at' => $future,
            ]));

        $content->refresh();
        $this->assertNull($content->published_at);
        $this->assertNotNull($content->scheduled_at);
    }

    public function test_update_publish_clears_pending_schedule(): void
    {
        $user = User::create(['name' => 'Admin', 'email' => 'admin@test.local', 'password' => bcrypt('password')]);
        $role = Role::firstOrCreate(['name' => 'super_admin']);
        $user->assignRole($role);
        $this->actingAs($user);

        $collection = Collection::where('project_id', $this->project->id)->first();
        $content = $this->createScheduledContent(now()->addDay()->toDateTimeString());

        $controller = new ContentController();
        $controller->update($this->project->id, $collection->id, $content->id,
            Request::create('/x', 'POST', [
                'locale' => 'en',
                'data' => ['title' => 'Publish now'],
                'published' => true,
                'scheduled_at' => now()->addDay()->format('Y-m-d H:i:s'),
            ]));

        $content->refresh();
        $this->assertNotNull($content->published_at);
        $this->assertNull($content->scheduled_at);
    }

    public function test_update_empty_schedule_cancels_it(): void
    {
        $user = User::create(['name' => 'Admin', 'email' => 'admin@test.local', 'password' => bcrypt('password')]);
        $role = Role::firstOrCreate(['name' => 'super_admin']);
        $user->assignRole($role);
        $this->actingAs($user);

        $collection = Collection::where('project_id', $this->project->id)->first();
        $content = $this->createScheduledContent(now()->addDay()->toDateTimeString());

        $controller = new ContentController();
        $controller->update($this->project->id, $collection->id, $content->id,
            Request::create('/x', 'POST', [
                'locale' => 'en',
                'data' => ['title' => 'Draft'],
                'scheduled_at' => '',
            ]));

        $content->refresh();
        $this->assertNull($content->published_at);
        $this->assertNull($content->scheduled_at);
    }
}
