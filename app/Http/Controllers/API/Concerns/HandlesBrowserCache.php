<?php

namespace App\Http\Controllers\API\Concerns;

use Illuminate\Http\Request;

/**
 * Shared helpers for browser-level caching (ETag / 304) on public API
 * responses.
 */
trait HandlesBrowserCache
{
    /**
     * Build a strong ETag from a deterministic value (e.g. the cache key,
     * which embeds the project cache version, endpoint and normalized query,
     * or the serialized response body).
     */
    protected function publicApiEtag(string $value): string
    {
        return '"' . md5($value) . '"';
    }

    /**
     * Whether the request's If-None-Match matches the given strong ETag.
     * Tolerates comma-separated lists and the weak-validator "W/" prefix.
     */
    protected function ifNoneMatchMatches(Request $request, string $etag): bool
    {
        $header = $request->header('If-None-Match');
        if ($header === null || $header === '') {
            return false;
        }

        foreach (explode(',', $header) as $candidate) {
            $candidate = trim((string) preg_replace('/^W\//', '', (string) $candidate));
            if ($candidate === $etag) {
                return true;
            }
        }

        return false;
    }

    /**
     * Respond 304 Not Modified, carrying the ETag (RFC 7232: a 304 response
     * SHOULD include the same ETag so the client can update its cache entry)
     * plus the no-cache directive so the entry keeps revalidating.
     */
    protected function respondNotModified(string $etag)
    {
        return response()->noContent(304, [
            'ETag' => $etag,
            'Cache-Control' => 'no-cache, must-revalidate',
        ]);
    }
}
