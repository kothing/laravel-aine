/**
 * Shared list of all known world locales (code -> display name).
 *
 * Used by BOTH the project Localization page (Add a Locale) and the global
 * Localization page (admin UI languages) so the two always offer the exact
 * same language options.
 */
import localesJson from "../../locales.json";

/**
 * [{ id: "zh", name: "Chinese" }, ...] — the v-select options list.
 */
export const LOCALES = Object.entries(localesJson).map(([code, name]) => ({
    id: code,
    name,
}));

/**
 * Display name of a locale code (null when unknown).
 */
export function localeDisplayName(code) {
    return localesJson[code] || null;
}
