/**
 * Thin wrapper around the headless content API.
 *
 * Uses the shared axios instance (./http) directly — its response interceptor
 * turns network / HTTP / business failures into `null` (with a matching hint),
 * so these calls never reject and views can render empty/fallback states.
 *
 * Caching strategy:
 *   - NO in-memory cache is kept here. Content freshness is owned by the
 *     backend (versioned Laravel cache, invalidated on every write) and by the
 *     browser HTTP cache (every public response carries ETag + no-cache, so
 *     repeat requests revalidate with a cheap 304 instead of a full body).
 *   - This module only merges concurrent identical requests (in-flight
 *     de-duplication) so N components mounting at once issue ONE HTTP call.
 */

import http from "./http";

let currentLocale = null;

const inflight = new Map(); // key -> Promise

/**
 * Set the language that subsequent requests should be scoped to.
 */
export function setApiLocale(locale) {
    currentLocale = locale || null;
    clearInflight();
}

/**
 * Drop any pending in-flight request merging (e.g. after a locale switch so
 * requests for the previous locale are never shared with the new one).
 */
export function clearInflight() {
    inflight.clear();
}

/**
 * Merge the locale filter into the request params.
 */
function scoped(params = {}) {
    const merged = { ...params };
    if (currentLocale) {
        merged.where = { locale: currentLocale, ...(merged.where || {}) };
    }
    return merged;
}

/**
 * Recursively sort an object's keys so that params with the same content but
 * a different key order produce the same in-flight key (and therefore share
 * a single HTTP call).
 */
function sortParams(value) {
    if (value && typeof value === "object" && !Array.isArray(value)) {
        const sorted = {};
        for (const key of Object.keys(value).sort()) {
            sorted[key] = sortParams(value[key]);
        }
        return sorted;
    }
    return value;
}

function buildKey(url, params) {
    return `${url}?${JSON.stringify(sortParams(params || {}))}`;
}

/**
 * GET with in-flight merging only:
 * - concurrent identical requests share a single HTTP call
 * - repeat reads are served by the browser HTTP cache (ETag/304)
 * - failures are never thrown: resolves the backend envelope
 *   { success, code, message, data } or `null` (already reported).
 */
export async function cachedGet(url, params = {}) {
    const key = buildKey(url, scoped(params));

    if (inflight.has(key)) {
        return inflight.get(key);
    }

    const request = http.get(url, { params: scoped(params) }).then((response) => {
        // The interceptor resolves `null` for network / HTTP / business
        // failures, so `response.data` here is the envelope — or null.
        return response ? response.data : null;
    });
    inflight.set(key, request);
    try {
        return await request;
    } finally {
        inflight.delete(key);
    }
}

export const api = {
    /**
     * Fetch a content list of a collection in a project.
     * GET /api/project/{identifier}/{slug}
     * Resolves with the list (array) or `null` on failure.
     */
    async collection(projectIdentifier, slug, params = {}) {
        const data = await cachedGet(`/api/project/${projectIdentifier}/${slug}`, params);
        if (!data) {
            return null;
        }
        return data.data;
    },

    /**
     * Fetch content of a related collection through a relation.
     * GET /api/project/{identifier}/{source}/{id}/{related}
     * e.g. listings of a category: related("bdl", "categories", 5, "listings")
     * Resolves with the list (array) or `null` on failure.
     */
    async related(projectIdentifier, sourceSlug, id, relatedSlug, params = {}) {
        const data = await cachedGet(`/api/project/${projectIdentifier}/${sourceSlug}/${id}/${relatedSlug}`, params);
        if (!data) {
            return null;
        }
        return data.data;
    },
};
