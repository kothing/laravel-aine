<?php

namespace Tests\Feature;

use App\Aine\ContentSerializer;
use App\Http\Resources\ContentResource;
use App\Models\Collection;
use App\Models\CollectionField;
use App\Models\Content;
use App\Models\ContentMeta;
use App\Models\Media;
use App\Models\Project;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Integration tests for P1-1: batch preloading of media/relation rows so
 * ContentResource serialisation does not degenerate into N+1 queries.
 */
class ContentSerializerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        ContentSerializer::reset();
    }

    /**
     * Build a small fixture: articles collection with media (single + multi)
     * and relation (single + multi) fields, plus an authors collection.
     *
     * @return array<string, mixed>
     */
    private function createFixture(): array
    {
        $project = Project::create(['name' => 'Demo', 'slug' => 'demo', 'status' => 1, 'public_api' => 1]);

        $articles = Collection::create(['name' => 'Articles', 'slug' => 'articles', 'project_id' => $project->id]);
        $authors = Collection::create(['name' => 'Authors', 'slug' => 'authors', 'project_id' => $project->id]);

        $fields = [
            ['type' => 'text', 'label' => 'Title', 'name' => 'title', 'options' => json_encode(['text' => ['type' => 1]]), 'project_id' => $project->id, 'collection_id' => $articles->id, 'order' => 1],
            ['type' => 'media', 'label' => 'Cover', 'name' => 'cover', 'options' => json_encode(['media' => ['type' => 1]]), 'project_id' => $project->id, 'collection_id' => $articles->id, 'order' => 2],
            ['type' => 'media', 'label' => 'Gallery', 'name' => 'gallery', 'options' => json_encode(['media' => ['type' => 2]]), 'project_id' => $project->id, 'collection_id' => $articles->id, 'order' => 3],
            ['type' => 'relation', 'label' => 'Author', 'name' => 'author', 'options' => json_encode(['relation' => ['type' => 1, 'collection' => $authors->id]]), 'project_id' => $project->id, 'collection_id' => $articles->id, 'order' => 4],
            ['type' => 'relation', 'label' => 'Related', 'name' => 'related', 'options' => json_encode(['relation' => ['type' => 2, 'collection' => $articles->id]]), 'project_id' => $project->id, 'collection_id' => $articles->id, 'order' => 5],
        ];
        foreach ($fields as $f) {
            CollectionField::create($f);
        }

        CollectionField::create([
            'type' => 'text',
            'label' => 'Name',
            'name' => 'name',
            'options' => json_encode(['text' => ['type' => 1]]),
            'project_id' => $project->id,
            'collection_id' => $authors->id,
            'order' => 1,
        ]);

        $media1 = Media::create(['project_id' => $project->id, 'name' => 'cover.jpg', 'type' => 'image', 'disk' => 'public']);
        $media2 = Media::create(['project_id' => $project->id, 'name' => 'g1.jpg', 'type' => 'image', 'disk' => 'public']);
        $media3 = Media::create(['project_id' => $project->id, 'name' => 'g2.jpg', 'type' => 'image', 'disk' => 'public']);

        $author1 = Content::create(['project_id' => $project->id, 'collection_id' => $authors->id, 'locale' => 'en', 'published_at' => now()]);
        $author2 = Content::create(['project_id' => $project->id, 'collection_id' => $authors->id, 'locale' => 'en']); // unpublished
        ContentMeta::create(['project_id' => $project->id, 'collection_id' => $authors->id, 'content_id' => $author1->id, 'field_name' => 'name', 'value' => 'Alice']);
        ContentMeta::create(['project_id' => $project->id, 'collection_id' => $authors->id, 'content_id' => $author2->id, 'field_name' => 'name', 'value' => 'Bob']);

        $articlesContent = [];
        for ($i = 1; $i <= 3; $i++) {
            $c = Content::create(['project_id' => $project->id, 'collection_id' => $articles->id, 'locale' => 'en', 'published_at' => now()]);
            ContentMeta::create(['project_id' => $project->id, 'collection_id' => $articles->id, 'content_id' => $c->id, 'field_name' => 'title', 'value' => "Article {$i}"]);
            ContentMeta::create(['project_id' => $project->id, 'collection_id' => $articles->id, 'content_id' => $c->id, 'field_name' => 'cover', 'value' => (string) $media1->id]);
            ContentMeta::create(['project_id' => $project->id, 'collection_id' => $articles->id, 'content_id' => $c->id, 'field_name' => 'gallery', 'value' => $media2->id.','.$media3->id]);
            ContentMeta::create(['project_id' => $project->id, 'collection_id' => $articles->id, 'content_id' => $c->id, 'field_name' => 'author', 'value' => (string) $author1->id]);

            $articlesContent[] = $c;
        }

        // Related articles — set after all articles exist so references can
        // point at already-created rows. Some ids deliberately dangle (they
        // must be tolerated and skipped during serialisation):
        $relatedRefs = [
            0 => [999999, $articlesContent[1]->id],                // Article 1: one dangling, one valid
            1 => [$articlesContent[0]->id, $articlesContent[2]->id], // Article 2: both valid
            2 => [$articlesContent[0]->id, $articlesContent[1]->id], // Article 3: both valid
        ];
        foreach ($articlesContent as $idx => $c) {
            ContentMeta::create([
                'project_id' => $project->id,
                'collection_id' => $articles->id,
                'content_id' => $c->id,
                'field_name' => 'related',
                'value' => implode(',', $relatedRefs[$idx]),
            ]);
        }

        return compact('project', 'articles', 'authors', 'media1', 'media2', 'media3', 'author1', 'author2', 'articlesContent');
    }

    private function loadContents(array $contents)
    {
        $ids = array_map(fn ($c) => $c->id, $contents);

        return Content::with(['meta', 'collection.fields'])->whereIn('id', $ids)->get();
    }

    /**
     * Serialise a collection and return a plain, recursively-resolved array
     * (json round-trip mirrors exactly what the HTTP response body would
     * contain).
     */
    private function serialize($contents): array
    {
        try {
            return json_decode(ContentResource::collection($contents)->toJson(), true);
        } catch (\Throwable $e) {
            fwrite(STDERR, "\n>>> ".$e->getMessage()."\n".$e->getTraceAsString()."\n");

            throw $e;
        }
    }

    public function test_preload_and_serialization_issue_few_queries(): void
    {
        $f = $this->createFixture();
        $contents = $this->loadContents($f['articlesContent']);

        DB::flushQueryLog();
        DB::enableQueryLog();

        ContentSerializer::preload($contents);
        $json = $this->serialize($contents);

        $queryCount = count(DB::getQueryLog());
        DB::disableQueryLog();

        // Without batching, serialising 3 articles with 1 single media,
        // 2 gallery media, 1 single relation and 2 related articles would
        // cost dozens of queries. Batch preloading must keep it tiny.
        $this->assertLessThan(20, $queryCount, "Serialisation issued {$queryCount} queries — expected a batch-preloaded flow.");
        $this->assertCount(3, $json);
    }

    public function test_single_media_field_is_resolved(): void
    {
        $f = $this->createFixture();
        $contents = $this->loadContents([$f['articlesContent'][2]]);

        ContentSerializer::preload($contents);
        $json = $this->serialize($contents);

        $this->assertSame('cover.jpg', $json[0]['cover']['file_name'] ?? null);
        $this->assertSame($f['media1']->id, $json[0]['cover']['id'] ?? null);
    }

    public function test_multi_media_field_follows_value_order(): void
    {
        $f = $this->createFixture();
        $contents = $this->loadContents([$f['articlesContent'][2]]);

        ContentSerializer::preload($contents);
        $json = $this->serialize($contents);

        $names = array_column($json[0]['gallery'] ?? [], 'file_name');
        $this->assertSame(['g1.jpg', 'g2.jpg'], $names);
    }

    public function test_single_relation_is_expanded(): void
    {
        $f = $this->createFixture();
        $contents = $this->loadContents([$f['articlesContent'][2]]);

        ContentSerializer::preload($contents);
        $json = $this->serialize($contents);

        $this->assertSame('Alice', $json[0]['author']['name'] ?? null);
        $this->assertSame($f['author1']->id, $json[0]['author']['id'] ?? null);
    }

    public function test_multi_relation_expands_published_only_in_value_order(): void
    {
        $f = $this->createFixture();
        $contents = $this->loadContents([$f['articlesContent'][2]]);

        ContentSerializer::preload($contents);
        $json = $this->serialize($contents);

        $titles = array_column($json[0]['related'] ?? [], 'title');
        $this->assertSame(['Article 1', 'Article 2'], $titles);
    }

    public function test_dangling_relation_id_is_tolerated(): void
    {
        $f = $this->createFixture();
        $contents = $this->loadContents([$f['articlesContent'][0]]);

        ContentSerializer::preload($contents);
        $json = $this->serialize($contents);

        // Article 1 references id 999999 (non-existent) as its first related
        // article; it must be skipped without error.
        $titles = array_column($json[0]['related'] ?? [], 'title');
        $this->assertSame(['Article 2'], $titles);
    }

    public function test_unpublished_relation_is_not_expanded(): void
    {
        $f = $this->createFixture();

        // Point article 3's author at the unpublished author.
        ContentMeta::where('content_id', $f['articlesContent'][2]->id)
            ->where('field_name', 'author')
            ->update(['value' => (string) $f['author2']->id]);

        $contents = $this->loadContents([$f['articlesContent'][2]]);

        ContentSerializer::preload($contents);
        $json = $this->serialize($contents);

        // Mirrors the previous behaviour: the relation resolves to nothing.
        $this->assertArrayNotHasKey('name', $json[0]['author'] ?? []);
        $this->assertNull($json[0]['author']['id'] ?? null);
    }

    public function test_serialization_works_without_preload_fallback(): void
    {
        // Even without preload(), the lazy accessors must produce the same
        // output (correctness first).
        $f = $this->createFixture();
        $contents = $this->loadContents([$f['articlesContent'][2]]);

        $json = $this->serialize($contents);

        $this->assertSame('cover.jpg', $json[0]['cover']['file_name'] ?? null);
        $this->assertSame('Alice', $json[0]['author']['name'] ?? null);
    }

    public function test_reset_clears_maps(): void
    {
        $f = $this->createFixture();
        ContentSerializer::preload($this->loadContents($f['articlesContent']));
        ContentSerializer::reset();

        $json = $this->serialize($this->loadContents([$f['articlesContent'][2]]));
        $this->assertSame('Article 3', $json[0]['title'] ?? null);
    }
}
