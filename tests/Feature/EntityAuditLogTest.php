<?php

namespace Tests\Feature;

use App\Http\Controllers\Admin\CollectionsController;
use App\Http\Controllers\Admin\CollectionFieldsController;
use App\Http\Controllers\Admin\ProjectsController;
use App\Http\Controllers\Admin\SettingsController;
use App\Http\Controllers\Admin\UsersController;
use App\Models\AuditLog;
use App\Models\Collection;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| EntityAuditLogTest
|--------------------------------------------------------------------------
|
| Audit logging was previously wired only into the admin content controller.
| This suite pins the AuditLogger::log calls added to the other writable
| entities — project / collection / collection field / settings / user — so a
| super admin's writes outside of content are traceable too. Style matches the
| existing AuditLogTest (direct controller invocation + Request::create).
*/

class EntityAuditLogTest extends TestCase
{
    use RefreshDatabase;

    private Project $project;
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::create(['name' => 'Admin', 'email' => 'admin@test.local', 'password' => bcrypt('password')]);
        Role::firstOrCreate(['name' => 'super_admin']);
        $this->user->assignRole('super_admin');
        $this->actingAs($this->user);

        $this->project = Project::create(['name' => 'Demo', 'slug' => 'demo', 'status' => 1, 'public_api' => 1]);
    }

    public function test_project_create_is_audited(): void
    {
        (new ProjectsController())->store(Request::create('/x', 'POST', [
            'name' => 'New Project',
            'default_locale' => 'en',
        ]));

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'create',
            'entity_type' => 'project',
            'user_id' => $this->user->id,
        ]);
    }

    public function test_project_update_is_audited(): void
    {
        (new ProjectsController())->update($this->project->id, Request::create('/x', 'POST', [
            'name' => 'Updated',
            'slug' => $this->project->slug,
            'description' => 'd',
            'disk' => 'local',
            'status' => 1,
        ]));

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'update',
            'entity_type' => 'project',
            'entity_id' => $this->project->id,
        ]);
    }

    public function test_collection_store_is_audited(): void
    {
        (new CollectionsController())->store($this->project->id, Request::create('/x', 'POST', [
            'name' => 'Posts',
            'slug' => 'posts',
        ]));

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'create',
            'entity_type' => 'collection',
            'project_id' => $this->project->id,
        ]);
    }

    public function test_collection_field_store_is_audited(): void
    {
        $collectionId = Collection::create([
            'name' => 'Articles', 'slug' => 'articles', 'project_id' => $this->project->id,
        ])->id;

        (new CollectionFieldsController())->store($this->project->id, $collectionId, Request::create('/x', 'POST', [
            'label' => 'Excerpt',
            'name' => 'excerpt',
            'type' => 'text',
            'options' => [],
            'validations' => [
                'charcount' => ['type' => 'Between', 'min' => null, 'max' => null, 'status' => false],
                'required' => ['status' => false, 'message' => null],
                'unique' => ['status' => false, 'message' => null],
            ],
        ]));

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'create',
            'entity_type' => 'field',
            'project_id' => $this->project->id,
        ]);
    }

    public function test_settings_update_is_audited(): void
    {
        (new SettingsController())->update(Request::create('/x', 'POST', [
            'name' => 'My Site',
            'description' => 'd',
            'version' => '1',
        ]));

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'update',
            'entity_type' => 'settings',
            'user_id' => $this->user->id,
        ]);
    }

    public function test_user_name_update_is_audited(): void
    {
        (new UsersController())->updateName(Request::create('/x', 'POST', [
            'name' => 'Renamed Admin',
        ]));

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'update',
            'entity_type' => 'user',
            'entity_id' => $this->user->id,
        ]);
    }
}