<?php

namespace Tests\Feature;

use App\Models\Collection;
use App\Models\CollectionField;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Tests for F6-2: collection schema export/import.
 */
class CollectionSchemaTest extends TestCase
{
    use RefreshDatabase;

    private Project $project;
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::create(['name' => 'Admin', 'email' => 'admin@schema.test', 'password' => bcrypt('password')]);
        $role = Role::firstOrCreate(['name' => 'super_admin']);
        $this->user->assignRole($role);
        $this->actingAs($this->user);

        $this->project = Project::create(['name' => 'Demo', 'slug' => 'demo', 'status' => 1, 'public_api' => 1]);
    }

    private function createCollectionWithFields(): Collection
    {
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

        return $collection;
    }

    public function test_export_schema_returns_structure(): void
    {
        $collection = $this->createCollectionWithFields();

        $response = $this->getJson('/admin-api/collections/export-schema/' . $this->project->id . '/' . $collection->id);

        $response->assertStatus(200);
        $response->assertJson([
            'collection' => ['name' => 'Articles', 'slug' => 'articles'],
        ]);
        $response->assertJsonStructure([
            'collection' => ['name', 'slug'],
            'fields' => [
                '*' => ['type', 'label', 'name', 'options', 'validations', 'order'],
            ],
        ]);
        $this->assertCount(1, $response->json('fields'));
    }

    public function test_import_schema_creates_collection_and_fields(): void
    {
        $schema = [
            'collection' => ['name' => 'Products', 'slug' => 'products'],
            'fields' => [
                [
                    'type' => 'text', 'label' => 'Name', 'name' => 'name',
                    'options' => ['text' => ['type' => 1]],
                    'validations' => ['required' => ['status' => true]],
                    'order' => 1,
                ],
                [
                    'type' => 'number', 'label' => 'Price', 'name' => 'price',
                    'options' => [],
                    'validations' => [],
                    'order' => 2,
                ],
            ],
        ];

        $file = UploadedFile::fake()->createWithContent('schema.json', json_encode($schema));

        $response = $this->post('/admin-api/collections/import-schema/' . $this->project->id, ['file' => $file]);

        $response->assertStatus(200);
        $response->assertJson([
            'fields_created' => 2,
            'fields_updated' => 0,
        ]);

        $this->assertDatabaseHas('collections', ['project_id' => $this->project->id, 'slug' => 'products']);
        $this->assertDatabaseHas('collection_fields', ['collection_id' => $response->json('collection_id'), 'name' => 'name']);
        $this->assertDatabaseHas('collection_fields', ['collection_id' => $response->json('collection_id'), 'name' => 'price']);
    }

    public function test_import_schema_updates_existing_collection(): void
    {
        $collection = $this->createCollectionWithFields();

        $schema = [
            'collection' => ['name' => 'Articles Renamed', 'slug' => 'articles'],
            'fields' => [
                [
                    'type' => 'text', 'label' => 'Title', 'name' => 'title',
                    'options' => ['text' => ['type' => 1]],
                    'validations' => ['required' => ['status' => true]],
                    'order' => 1,
                ],
                [
                    'type' => 'rich_text', 'label' => 'Body', 'name' => 'body',
                    'options' => [],
                    'validations' => [],
                    'order' => 2,
                ],
            ],
        ];

        $file = UploadedFile::fake()->createWithContent('schema.json', json_encode($schema));

        $response = $this->post('/admin-api/collections/import-schema/' . $this->project->id, ['file' => $file]);

        $response->assertStatus(200);
        $response->assertJson([
            'fields_created' => 1,
            'fields_updated' => 1,
        ]);

        $collection->refresh();
        $this->assertSame('Articles Renamed', $collection->name);
        $this->assertDatabaseHas('collection_fields', ['collection_id' => $collection->id, 'name' => 'body']);
    }

    public function test_import_schema_rejects_invalid_payload(): void
    {
        $file = UploadedFile::fake()->createWithContent('schema.json', '{"foo": "bar"}');

        $this->post('/admin-api/collections/import-schema/' . $this->project->id, ['file' => $file])
            ->assertStatus(422);
    }
}
