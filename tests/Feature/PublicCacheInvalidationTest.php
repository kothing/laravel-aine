<?php

namespace Tests\Feature;

use App\Aine\PublicCache;
use App\Events\ContentCreated;
use App\Events\ContentDeleted;
use App\Events\ContentPublished;
use App\Events\ContentRestored;
use App\Events\ContentTrashed;
use App\Events\ContentUnpublished;
use App\Events\ContentUpdated;
use App\Listeners\BumpPublicCache;
use App\Models\Content;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

/**
 * Regression tests for P0-1: every content write (admin panel included)
 * must invalidate the public API cache by bumping the project version.
 */
class PublicCacheInvalidationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
    }

    public function test_version_starts_at_zero(): void
    {
        $this->assertSame(0, PublicCache::version(1));
    }

    public function test_bump_increments_version(): void
    {
        PublicCache::bump(1);
        PublicCache::bump(1);
        $this->assertSame(2, PublicCache::version(1));
    }

    public function test_bump_is_scoped_per_project(): void
    {
        PublicCache::bump(1);
        PublicCache::bump(3);
        $this->assertSame(1, PublicCache::version(1));
        $this->assertSame(1, PublicCache::version(3));
        $this->assertSame(0, PublicCache::version(2));
    }

    public function test_listener_bumps_on_content_model_event(): void
    {
        $content = new Content();
        $content->project_id = 7;

        (new BumpPublicCache())->handle(new ContentUpdated([
            'source' => 'User',
            'content' => $content,
        ]));

        $this->assertSame(1, PublicCache::version(7));
    }

    public function test_listener_handles_plain_array_content(): void
    {
        (new BumpPublicCache())->handle(new ContentDeleted([
            'source' => 'User',
            'content' => [
                'project_id' => 9,
                'collection_id' => 3,
                'item_id' => 5,
            ],
        ]));

        $this->assertSame(1, PublicCache::version(9));
    }

    public function test_listener_ignores_content_without_project_id(): void
    {
        (new BumpPublicCache())->handle(new ContentDeleted([
            'source' => 'User',
            'content' => [],
        ]));

        $this->assertSame(0, PublicCache::version(1));
    }

    public function test_all_content_events_are_bound_to_the_listener(): void
    {
        Event::fake();

        Event::assertListening(ContentCreated::class, BumpPublicCache::class);
        Event::assertListening(ContentUpdated::class, BumpPublicCache::class);
        Event::assertListening(ContentTrashed::class, BumpPublicCache::class);
        Event::assertListening(ContentDeleted::class, BumpPublicCache::class);
        Event::assertListening(ContentPublished::class, BumpPublicCache::class);
        Event::assertListening(ContentUnpublished::class, BumpPublicCache::class);
        Event::assertListening(ContentRestored::class, BumpPublicCache::class);
    }
}
