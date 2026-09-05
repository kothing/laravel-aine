<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\API\Concerns\AuthorizesProjectApi;
use App\Http\Controllers\Controller;
use App\Models\Collection;
use App\Models\Content;
use App\Models\ContentMeta;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

/*
|--------------------------------------------------------------------------
| sitemap.xml + RSS feed
|--------------------------------------------------------------------------
|
| GET /api/project/{identifier}/sitemap.xml
| GET /api/project/{identifier}/feed.xml?collection=<slug>&limit=20
|
| Standard exposure feeds for a project. Whichever-mode:-style path segments:
|   - 'pages' collection            → /<prefix>/<url>                 (PageDetail)
|   - 'articles' / 'listings'       → /<prefix>/<category_url>/<url>  (ArticleDetail / ListingDetail)
|   - 'categories'/'tags'/'locations' → /<prefix>/<kind>/<url>        (archive route)
|   - anything else                 → /<prefix>/<url>
| The category segment is resolved through the article's `category` relation
| meta. `prefix` is auto-detected (project with a `listings` collection =
| directory, otherwise content), overridable via ?prefix=content|directory.
|
| Both endpoints go through the whitelist middleware + AuthorizesProjectApi,
| exactly like the list endpoint — public projects anonymous, others via
| whitelist Origin or token. Returns XML, cached by `bumpPublicCache`.
*/

class FeedController extends Controller
{
    use AuthorizesProjectApi;

    /** Cap each feed at this many items (and the sitemap too). */
    private const MAX_ITEMS = 50;

    /** Projection kept narrow — only the content columns read. */
    private static array $contentColumns = ['id', 'collection_id', 'project_id', 'locale', 'created_at', 'updated_at', 'published_at'];

    /**
     * RSS 2.0 feed. Optional ?collection= restricts one collection.
     */
    public function rss($project_identifier, Request $request)
    {
        $project = $request->attributes->get('resolved_project');
        if (! $project) {
            return $this->xmlNotFound();
        }
        if ($response = $this->authorizeProjectRead($project)) {
            return $response;
        }

        $contents = Content::with('collection:id,slug')
            ->where('project_id', $project->id)
            ->whereNotNull('published_at')
            ->whereNull('draft_parent_id')
            ->when($request->get('collection'), function ($q) use ($project, $request) {
                $collection = Collection::where('project_id', $project->id)->where('slug', $request->get('collection'))->first();
                return $collection ? $q->where('collection_id', $collection->id) : $q->whereRaw('1 = 0');
            })
            ->orderByDesc('updated_at')
            ->limit(self::MAX_ITEMS)
            ->get(self::$contentColumns);

        $base = rtrim((string) config('app.url', 'http://localhost'), '/');
        $prefix = $this->resolvePrefix($request, $project->id);

        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<rss version="2.0"><channel>' . "\n";
        $xml .= '<title>' . $this->esc($project->name) . '</title>' . "\n";
        $xml .= '<link>' . $this->esc($base) . '</link>' . "\n";
        $xml .= '<description>' . $this->esc((string) ($project->description ?? '')) . '</description>' . "\n";
        $xml .= '<lastBuildDate>' . Carbon::now()->toRfc822String() . '</lastBuildDate>' . "\n";

        foreach ($contents as $content) {
            $link = $base . $this->contentPath($content, $prefix);
            $title = $this->metaString($content->id, 'title') ?: ('Item #' . $content->id);
            $desc = $this->metaString($content->id, 'excerpt');

            $xml .= '<item>' . "\n";
            $xml .= '<title>' . $this->esc($title) . '</title>' . "\n";
            $xml .= '<link>' . $this->esc($link) . '</link>' . "\n";
            $xml .= '<description>' . $this->esc($desc) . '</description>' . "\n";
            $xml .= '<guid isPermaLink="true">' . $this->esc($link) . '</guid>' . "\n";
            $xml .= '<pubDate>' . $this->dateRfc822($content) . '</pubDate>' . "\n";
            $xml .= '</item>' . "\n";
        }

        $xml .= '</channel></rss>';

        return response($xml, 200, ['Content-Type' => 'application/rss+xml; charset=UTF-8']);
    }

    /**
     * sitemap.xml for the project. Lists every published content item.
     */
    public function sitemap($project_identifier, Request $request)
    {
        $project = $request->attributes->get('resolved_project');
        if (! $project) {
            return $this->xmlNotFound();
        }
        if ($response = $this->authorizeProjectRead($project)) {
            return $response;
        }

        $contents = Content::with('collection:id,slug')
            ->where('project_id', $project->id)
            ->whereNotNull('published_at')
            ->whereNull('draft_parent_id')
            ->orderByDesc('updated_at')
            ->limit(self::MAX_ITEMS)
            ->get(self::$contentColumns);

        $base = rtrim((string) config('app.url', 'http://localhost'), '/');
        $prefix = $this->resolvePrefix($request, $project->id);

        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
        $xml .= '<url><loc>' . $this->esc($base) . '</loc>'
            . '<lastmod>' . Carbon::now()->toDateString() . '</lastmod>'
            . '<priority>1.0</priority></url>' . "\n";

        foreach ($contents as $content) {
            $link = $base . $this->contentPath($content, $prefix);
            $xml .= '<url><loc>' . $this->esc($link) . '</loc><lastmod>' . $this->dateW3c($content) . '</lastmod></url>' . "\n";
        }

        $xml .= '</urlset>';

        return response($xml, 200, ['Content-Type' => 'application/xml; charset=UTF-8']);
    }

    /**
     * SPA link path for a content item, matching frontend routes.js + config.js.
     */
    private function contentPath(Content $content, string $prefix): string
    {
        $collectionSlug = $content->collection?->slug ?? 'content';
        $url = $this->metaString($content->id, 'url') ?: ('id-' . $content->id);
        $segUrl = rawurlencode($url);
        $base = '/' . $prefix . '/';

        if ($collectionSlug === 'pages') {
            return $base . $segUrl;
        }

        if (in_array($collectionSlug, ['articles', 'listings'], true)) {
            $categoryUrl = $this->categoryUrlOf($content);

            return $base . rawurlencode($categoryUrl ?: 'uncategorized') . '/' . $segUrl;
        }

        if ($collectionSlug === 'categories') {
            return $base . 'category/' . $segUrl;
        }
        if ($collectionSlug === 'tags') {
            return $base . 'tag/' . $segUrl;
        }
        if ($collectionSlug === 'locations') {
            return $base . 'location/' . $segUrl;
        }

        return $base . $segUrl;
    }

    /**
     * Resolve the `url` meta of the content this item's `category` relation
     * points at, so feeds link to /<prefix>/<categorySlug>/<article>.
     */
    private function categoryUrlOf(Content $content): ?string
    {
        $raw = ContentMeta::where('content_id', $content->id)
            ->where('field_name', 'category')
            ->where('value', '!=', '')
            ->value('value');
        if (! $raw) {
            return null;
        }

        $ids = array_values(array_filter(explode(',', (string) $raw), 'is_numeric'));
        if (! $ids) {
            return null;
        }

        $categoryUrl = ContentMeta::where('content_id', (int) $ids[0])
            ->where('field_name', 'url')
            ->value('value');

        return $categoryUrl ? (string) $categoryUrl : null;
    }

    /**
     * Choose the front-end path prefix (/content or /directory). Auto-detects
     * from the project's collections (listings => directory), overridable.
     */
    private function resolvePrefix(Request $request, int $projectId): string
    {
        $prefix = (string) $request->get('prefix');
        if (in_array($prefix, ['content', 'directory'], true)) {
            return $prefix;
        }

        $isDirectory = Collection::where('project_id', $projectId)
            ->where('slug', 'listings')
            ->exists();

        return $isDirectory ? 'directory' : 'content';
    }

    private function metaString(int $contentId, string $field): string
    {
        return (string) (ContentMeta::where('content_id', $contentId)
            ->where('field_name', $field)
            ->value('value') ?? '');
    }

    private function dateRfc822(Content $content): string
    {
        $date = $content->published_at ?: ($content->updated_at ?: $content->created_at);

        return $date ? Carbon::parse($date)->toRfc822String() : Carbon::now()->toRfc822String();
    }

    private function dateW3c(Content $content): string
    {
        $date = $content->updated_at ?: ($content->published_at ?: $content->created_at);

        return $date ? Carbon::parse($date)->toDateString() : Carbon::now()->toDateString();
    }

    private function esc(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_XML1, 'UTF-8');
    }

    private function xmlNotFound()
    {
        return response('<?xml version="1.0" encoding="UTF-8"?><error>Project not resolved</error>', 404, [
            'Content-Type' => 'application/xml; charset=UTF-8',
        ]);
    }
}