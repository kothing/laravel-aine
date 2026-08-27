<?php

namespace Tests\Feature;

use App\Http\Controllers\PreviewController;
use App\Models\Collection;
use App\Models\CollectionField;
use App\Models\Content;
use App\Models\ContentMeta;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Spatie\Permission\Exceptions\UnauthorizedException;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| PreviewTest — draft content preview
|--------------------------------------------------------------------------
|
| Two halves: an admin/editor mints a time-limited preview token for a draft
| content item, and a non-authenticated reviewer reads the draft back through
| /preview/{token} using only the token. Setup stays identical to the project's
| other integration tests: direct controller invocation, RefreshDatabase.
*/

class PreviewTest extends TestCase
{
    use RefreshDatabase;

    private Project $project;
    private Collection $collection;
    private Content $content;

    protected function setUp(): void
    {
        parent::setUp();

        $user = User::create(['name' => 'Admin', 'email' => 'a@t.local', 'password' => bcrypt('password')]);
        Role::firstOrCreate(['name' => 'super_admin']);
        $user->assignRole('super_admin');
        $this->actingAs($user);

        $this->project = Project::create(['name' => 'Demo', 'slug' => 'demo', 'status' => 1, 'public_api' => 1]);
        $this->collection = Collection::create(['name' => 'Articles', 'slug' => 'articles', 'project_id' => $this->project->id]);
        CollectionField::create([
            'type' => 'text', 'label' => 'Title', 'name' => 'title',
            'options' => '{}', 'validations' => '{}',
            'project_id' => $this->project->id, 'collection_id' => $this->collection->id, 'order' => 1,
        ]);

        $this->content = Content::create([
            'project_id' => $this->project->id, 'collection_id' => $this->collection->id,
            'locale' => 'en', 'published_at' => null,
        ]);
        ContentMeta::create([
            'project_id' => $this->project->id, 'collection_id' => $this->collection->id,
            'content_id' => $this->content->id, 'field_name' => 'title', 'value' => 'Draft Headline',
        ]);
    }

    public function test_generate_creates_preview_token_and_expiry(): void
    {
        $resp = (new PreviewController())->generate($this->project->id, $this->collection->id, $this->content->id);
        $data = $resp->getData(true);

        $this->assertTrue($data['success']);
        $this->assertNotNull($data['token']);
        $this->assertNotNull($data['expires_at']);
        $this->assertDatabaseHas('content', ['id' => $this->content->id, 'preview_token' => $data['token']]);
    }

    public function test_show_returns_draft_content_by_valid_token(): void
    {
        $this->content->preview_token = Str::uuid()->toString();
        $this->content->preview_expires_at = Carbon::now()->addHours(24);
        $this->content->save();

        $resp = (new PreviewController())->show($this->content->preview_token);
        $data = $resp->getData(true);

        $this->assertTrue($data['success']);
        $this->assertSame('Draft Headline', $data['data']['title'] ?? null);
    }

    public function test_show_expired_token_returns_403(): void
    {
        $this->content->preview_token = Str::uuid()->toString();
        $this->content->preview_expires_at = Carbon::now()->subHour();
        $this->content->save();

        $resp = (new PreviewController())->show($this->content->preview_token);
        $this->assertSame(403, $resp->getStatusCode());
    }

    public function test_show_unknown_token_returns_404(): void
    {
        $resp = (new PreviewController())->show(Str::uuid()->toString());
        $this->assertSame(404, $resp->getStatusCode());
    }

    public function test_generate_requires_project_writer_role(): void
    {
        $stranger = User::create(['name' => 'Stranger', 'email' => 's@t.local', 'password' => bcrypt('password')]);
        $this->actingAs($stranger);

        $this->expectException(UnauthorizedException::class);
        (new PreviewController())->generate($this->project->id, $this->collection->id, $this->content->id);
    }
}