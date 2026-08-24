/**
 * Admin UI translation helper (dictionary layer only).
 *
 * The admin UI is authored in the base language (en) and every visible
 * string goes through the explicit `__()` helper:
 *
 *   {{ __('Save') }}                        — static strings
 *   {{ __('Language "{languageName}" added.', { languageName: code }) }}  — strings with runtime values
 *
 * This module only:
 *   1. holds the active dictionaries (global UI + per-project overlay),
 *   2. exposes the reactive `__()` helper for templates and script code,
 *   3. caches dictionaries in localStorage so the boot path can apply the
 *      saved language synchronously (no flash of the base-language UI).
 *
 * The former MutationObserver-based DOM scanning was removed: components are
 * expected to wrap every visible string with `__()` themselves. Any raw
 * string left unwrapped simply stays in the base language — there is no
 * automatic patch pass anymore.
 */
import axios from "axios";
import { reactive } from "vue";

const BASE_LOCALE = "en";

const state = {
    locale: BASE_LOCALE,
    dict: {},
    // Per-project translations take precedence over the global UI dict.
    projectDict: {},
    active: false,
};

// Reactive mirror of the active dictionaries, used by the explicit __()
// helper below. Components that render `{{ __('...') }}` read from these
// objects, so they re-render automatically when the language changes or a
// translation is saved.
const dictView = reactive({
    ui: {},
    project: {},
});

// Guards against out-of-order responses: only the newest setLocale() call
// may apply its dictionary (fast zh->en->zh clicks otherwise race).
let localeSeq = 0;

// --- localStorage dictionaries ------------------------------------------------
// The boot path applies the saved UI language synchronously from these
// caches (see store.initUiLocale), so refreshing the admin never flashes the
// base-language UI while the dictionary is fetched over the network. Every
// successful fetch refreshes the cache.

function readCache(key) {
    try {
        const raw = localStorage.getItem(key);
        if (!raw) return null;
        const value = JSON.parse(raw);
        return value && typeof value === "object" ? value : null;
    } catch (error) {
        return null;
    }
}

function writeCache(key, value) {
    try {
        localStorage.setItem(key, JSON.stringify(value));
    } catch (error) {
        // Full storage / private browsing: the cache is a nicety, never fatal.
    }
}

export function loadCachedUiDict(locale) {
    return locale ? readCache("aine_ui_dict_" + locale) : null;
}

export function saveCachedUiDict(locale, dict) {
    if (locale && dict) writeCache("aine_ui_dict_" + locale, dict);
}

export function loadCachedProjectDict(projectId, locale) {
    return projectId && locale
        ? readCache("aine_project_dict_" + projectId + "_" + locale)
        : null;
}

export function saveCachedProjectDict(projectId, locale, dict) {
    if (projectId && locale && dict) {
        writeCache("aine_project_dict_" + projectId + "_" + locale, dict);
    }
}

/**
 * Set (or clear, with {}) the translation dictionary of the currently open
 * project. Project strings (collection names, field labels, ...) are matched
 * before the global UI dictionary by `__()`.
 *
 * The project layer also works while the admin UI itself is in the base
 * language: the project's default-language strings are editable on Project →
 * Settings → Language → Translations, and the saved values overlay the raw
 * source strings in the admin UI.
 */
export function setProjectDict(projectDict) {
    state.projectDict = projectDict || {};
    dictView.project = state.projectDict;
}

/**
 * Activate translations for a locale. Pass the base locale (the language the
 * UI is authored in) to disable translations.
 *
 * When `preloadedDict` is provided, the dictionary is applied synchronously
 * (no network request) — used at boot so the first paint is already in the
 * right language. Returns the dictionary that was applied ({} when the base
 * locale is active or the fetch failed).
 *
 * NOTE: the dictionary keys are always the English source strings (see
 * TranslationsController::dict), so the engine's "base" must be English —
 * pass BASE_LOCALE here, not the database base_locale.
 */
export async function setLocale(locale, baseLocale = BASE_LOCALE, preloadedDict = null) {
    const seq = ++localeSeq;

    if (!locale || locale === baseLocale) {
        // Clear any stale dictionary from a previous non-base locale first.
        state.dict = {};
        dictView.ui = {};
        state.locale = baseLocale;
        state.active = false;
        return {};
    }

    let dict;
    if (preloadedDict && typeof preloadedDict === "object") {
        // Synchronous path: the caller already has the dictionary (boot
        // cache), so there is no network round trip.
        dict = preloadedDict;
    } else {
        try {
            const { data } = await axios.get("translations/dict", { params: { locale } });
            if (seq !== localeSeq) return {}; // superseded by a newer switch
            dict = (data && data.dict) || {};
        } catch (error) {
            if (seq !== localeSeq) return {}; // superseded by a newer switch
            console.warn("Failed to load translations:", error);
            return {}; // keep the current language state untouched on failure
        }
    }

    state.locale = locale;
    state.dict = dict;
    dictView.ui = dict;
    state.active = true;
    return dict;
}

/**
 * Apply the base-language UI dictionary as an overlay while the engine is
 * inactive (admin UI in the base language). Used when the admin edits the
 * base language's strings on the Translations page: saved values override
 * the built-in base-language labels. No-op while the engine is active
 * (setLocale() owns state.dict then).
 */
export function setBaseUiDict(dict) {
    if (state.active) return;
    state.dict = dict || {};
    dictView.ui = state.dict;
}

// --- Named placeholders ("{name}") -------------------------------------------
// Source strings use named placeholders like `{languageName}` or `{count}`.
// `__()` fills them from the params object passed as the second argument:
//
//   __('Language "{languageName}" added.', { languageName: code })
//
// A missing param empties the placeholder rather than leaving a raw
// `{name}` token on screen.

const NAMED_RE = /\{([a-zA-Z_][a-zA-Z0-9_]*)\}/g;

/**
 * Fill named `{name}` placeholders in a translation from the params object.
 * Each `{name}` is replaced by `params[name]` (missing → emptied).
 */
function fillPlaceholders(translation, params) {
    if (params == null) return translation;
    if (typeof params === "object") {
        return translation.replace(NAMED_RE, (_, name) => {
            const v = params[name];
            return v === undefined ? "" : v;
        });
    }
    return translation;
}

/**
 * Explicit translation helper for templates and script code.
 *
 * `{{ __('Save') }}` renders the current language's translation (or the
 * source string itself when no translation exists). It reads through the
 * reactive `dictView`, so any component that renders `__()` re-renders
 * automatically when the language changes or a translation is saved.
 *
 * Named placeholders are filled from the params object:
 *
 *   {{ __('Language "{languageName}" added.', { languageName: code }) }}
 *   {{ __('{total} records, {from} - {to} showing', { total, from, to }) }}
 */
export function __(key, ...args) {
    if (!key || typeof key !== "string") return key;
    const translation = dictView.project[key] || dictView.ui[key];
    if (!translation || translation === key) return key;
    if (args.length) {
        // The first arg is the params object; later args are ignored (kept
        // for forward-compat with future overloads).
        return fillPlaceholders(translation, args[0]);
    }
    return translation;
}

export { BASE_LOCALE };
