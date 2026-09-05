<?php

namespace Tests\Feature;

use App\Models\Media;
use App\Models\Project;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| MediaTransformTest — on-the-fly image transform
|--------------------------------------------------------------------------
|
| Coverage focuses on the error/guard paths so the tests stay GD-free: unknown
| media (404), non-image media (422), oversized dimension (422), non-numeric
| dimension (422), and a known image whose source file is missing (404).
| The success path (resize + encode) uses the same Intervention GD pipeline the
| upload code already relies on for thumbnails.
*/

class MediaTransformTest extends TestCase
{
    use RefreshDatabase;

    private Project $project;

    protected function setUp(): void
    {
        parent::setUp();
        $this->project = Project::create(['name' => 'Demo', 'slug' => 'demo', 'status' => 1, 'public_api' => 1]);
    }

    private function makeMedia(array $overrides = []): Media
    {
        return Media::create(array_merge([
            'project_id' => $this->project->id,
            'name' => 'photo.jpg',
            'type' => 'jpg',
            'size' => 100,
            'width' => 100,
            'height' => 100,
            'disk' => 'local',
        ], $overrides));
    }

    public function test_transform_unknown_media_returns_not_found(): void
    {
        $this->getJson('/media/transform/99999')->assertStatus(404);
    }

    public function test_transform_non_image_is_rejected(): void
    {
        $media = $this->makeMedia(['name' => 'document.pdf', 'type' => 'pdf']);

        $this->getJson('/media/transform/' . $media->id)->assertStatus(422);
    }

    public function test_transform_oversize_dimension_is_rejected(): void
    {
        $media = $this->makeMedia(['name' => 'photo.jpg', 'type' => 'jpg']);

        $this->getJson('/media/transform/' . $media->id . '?w=5000')->assertStatus(422);
    }

    public function test_transform_non_numeric_dimension_is_rejected(): void
    {
        $media = $this->makeMedia(['name' => 'photo.jpg', 'type' => 'jpg']);

        $this->getJson('/media/transform/' . $media->id . '?w=abc')->assertStatus(422);
    }

    public function test_transform_missing_source_file_returns_not_found(): void
    {
        $media = $this->makeMedia(['name' => 'ghost.jpg', 'type' => 'jpg']);

        // Valid image media but no actual file on disk — should 404 before GD.
        $this->getJson('/media/transform/' . $media->id . '?w=100')->assertStatus(404);
    }
}