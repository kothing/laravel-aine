<?php

namespace Tests\Feature;

use App\Models\Collection;
use App\Models\CollectionField;
use App\Models\Content;
use App\Models\ContentMeta;
use App\Models\Project;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| FeedTest — sitemap.xml + RSS feed
|--------------------------------------------------------------------------
|
| Returns XML — tests use $this->get (not getJson) and assert on the body.
| The content URL scheme matches the frontend SPA routes
| (resources/js/frontend/routes.js + config.js PROJECTS):
|   articles/listings  → /<prefix>/<category_url>/<url>   (ArticleDetail / ListingDetail)
|   pages              → /<prefix>/<url>                   (PageDetail)
|   categories/tags    → /<prefix>/category|tag/<url>       (archive routes)
|
| Pub/non-pub auth via the whitelist middleware, same as the list endpoint.
*/

class FeedTest extends TestCase
{
    use RefreshDatabase;

    private Project $project;
    private Collection $collection;

    protected function setUp(): void
    {
        parent::setUp();

        $this->project = Project::create(['name' => 'Blog', 'slug' => 'blog', 'status' => 1, 'public_api' => 1]);
        $this->collection = Collection::create(['name' => 'Articles', 'slug' => 'articles', 'project_id' => $this->project->id]);
        $this->addUrlField($this->collection);
    }

    private function addContent(Collection $collection, array $metaMap, bool $published = true): Content
    {
        $content = Content::create([
            'project_id' => $this->project->id,
            'collection_id' => $collection->id,
            'locale' => 'en',
            'published_at' => $published ? now() : null,
        ]);
        foreach ($metaMap as $field => $value) {
            ContentMeta::create([
                'project_id' => $this->project->id, 'collection_id' => $collection->id,
                'content_id' => $content->id, 'field_name' => $field, 'value' => $value,
            ]);
        }

        return $content;
    }

    private function addUrlField(Collection $collection): void
    {
        CollectionField::create([
            'type' => 'slug', 'label' => 'Path', 'name' => 'url',
            'options' => '{}', 'validations' => '{}',
            'project_id' => $this->project->id, 'collection_id' => $collection->id, 'order' => 1,
        ]);
    }

    public function test_rss_lists_published_articles_with_two_segment_url(): void
    {
        $this->addContent($this->collection, ['title' => 'Hello', 'url' => 'headline', 'category' => 'tech']);
        $this->addContent($this->collection, ['title' => 'Hidden', 'url' => 'hidden', 'category' => 'tech'], false);

        $resp = $this->get('/api/project/blog/feed.xml?collection=articles');
        $resp->assertStatus(200);
        $body = $resp->getContent();

        $this->assertStringContainsString('<rss version="2.0">', $body);
        $this->assertStringContainsString('/content/uncategorized/headline', $body);
        $this->assertStringNotContainsString('uncategorized/hidden', $body);
    }

    public function test_rss_resolves_category_via_relation(): void
    {
        $catColl = Collection::create(['name' => 'Categories', 'slug' => 'categories', 'project_id' => $this->project->id]);
        $this->addUrlField($catColl);
        CollectionField::create([
            'type' => 'text', 'label' => 'Title', 'name' => 'title',
            'options' => '{}', 'validations' => '{}',
            'project_id' => $this->project->id, 'collection_id' => $catColl->id, 'order' => 2,
        ]);
        $category = $this->addContent($catColl, ['title' => 'News', 'url' => 'news']);

        $this->addContent($this->collection, [
            'title' => 'Headline', 'url' => 'headline', 'category' => (string) $category->id,
        ]);

        $resp = $this->get('/api/project/blog/feed.xml?collection=articles');
        $resp->assertStatus(200);

        $this->assertStringContainsString('/content/news/headline', $resp->getContent());
    }

    public function test_pages_collection_uses_single_segment_url(): void
    {
        $pagesColl = Collection::create(['name' => 'Pages', 'slug' => 'pages', 'project_id' => $this->project->id]);
        $this->addUrlField($pagesColl);
        CollectionField::create([
            'type' => 'text', 'label' => 'Title', 'name' => 'title',
            'options' => '{}', 'validations' => '{}',
            'project_id' => $this->project->id, 'collection_id' => $pagesColl->id, 'order' => 2,
        ]);
        $this->addContent($pagesColl, ['title' => 'About', 'url' => 'about']);

        $resp = $this->get('/api/project/blog/feed.xml?collection=pages');
        $resp->assertStatus(200);
        $body = $resp->getContent();

        $this->assertStringContainsString('/content/about', $body);
        $this->assertStringNotContainsString('/content/pages/', $body);
    }

    public function test_sitemap_lists_published_with_correct_path(): void
    {
        $this->addContent($this->collection, ['title' => 'Hello', 'url' => 'headline', 'category' => 'tech']);
        $this->addContent($this->collection, ['title' => 'Hidden', 'url' => 'hidden', 'category' => 'tech'], false);

        $resp = $this->get('/api/project/blog/sitemap.xml');
        $resp->assertStatus(200);
        $body = $resp->getContent();

        $this->assertStringContainsString('<urlset', $body);
        $this->assertStringContainsString('/content/uncategorized/headline', $body);
        $this->assertStringNotContainsString('uncategorized/hidden', $body);
    }

    public function test_feed_non_public_without_origin_is_forbidden(): void
    {
        $project = Project::create([
            'name' => 'Secret', 'status' => 1, 'public_api' => 0,
            'domain_whitelist' => ['https://example.com'],
        ]);

        $this->get('/api/project/' . $project->slug . '/feed.xml')->assertStatus(403);
    }
}