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
// The params may also be an array (legacy positional form) — each value is
// then filled into the placeholders in order of appearance. This keeps old
// `__('{{ ... }} items', [n])` call sites working during the migration.
//
// The "{{ ... }}" pattern-key machinery below is retained for dictionaries
// that still hold legacy "{{ ... }}" source strings (older seeded data):
// the engine matches a rendered text against pattern keys and fills the
// captured values back into the translation.

const PLACEHOLDER_RE = /\{\{\s*\.\.\.\s*\}\}/;
// A named placeholder: {name} or {name_with_underscores}. Not "{ ... }" (the
// legacy positional marker) and not "{{" (Vue interpolation).
const NAMED_RE = /\{([a-zA-Z_][a-zA-Z0-9_]*)\}/g;

let patternIndex = null;
let patternIndexDict = null;

function escapeRegExp(s) {
    return s.replace(/[.*+?^${}()|[\]\\]/g, "\\$&");
}

/**
 * Precompile pattern keys ("{{ ... }}" placeholders) of a dictionary into
 * regexes. Cached until the dictionary object changes. Longer (more
 * specific) patterns are matched first.
 */
function getPatternIndex(dict) {
    if (patternIndex && patternIndexDict === dict) return patternIndex;
    const entries = [];
    for (const key of Object.keys(dict)) {
        if (!key.includes("{{")) continue;
        const parts = key.split(PLACEHOLDER_RE);
        const source = parts.map(escapeRegExp).join("([\\s\\S]*?)");
        try {
            entries.push({ key, regex: new RegExp("^" + source + "$") });
        } catch (error) {
            // Skip malformed pattern keys.
        }
    }
    entries.sort((a, b) => b.key.length - a.key.length);
    patternIndex = entries;
    patternIndexDict = dict;
    return entries;
}

function matchPatternKey(trimmed, dict) {
    if (!dict) return null;
    for (const entry of getPatternIndex(dict)) {
        const match = entry.regex.exec(trimmed);
        if (match) return { translation: dict[entry.key], match };
    }
    return null;
}

/**
 * Resolve a key to a translation. Tries the exact key first (project layer,
 * then UI/base layer); falls back to pattern keys with "{{ ... }}"
 * placeholders (e.g. `Language "{{ ... }}" added.` against the rendered
 * text `Language "zh" added.`).
 */
function lookup(key) {
    const direct = state.projectDict[key] || state.dict[key];
    if (direct && direct !== key) {
        return { translation: direct, isPattern: false, match: null };
    }
    const pattern =
        matchPatternKey(key, state.projectDict) || matchPatternKey(key, state.dict);
    if (pattern) {
        return { translation: pattern.translation, isPattern: true, match: pattern.match };
    }
    return null;
}

/**
 * Fill the "{{ ... }}" placeholders of a pattern translation with the values
 * captured from the source text (in order).
 */
function applyPattern(translation, match) {
    let index = 0;
    return translation.replace(/\{\{\s*\.\.\.\s*\}\}/g, () => {
        const value = match[++index];
        return value === undefined ? "" : value;
    });
}

/**
 * Fill named `{name}` placeholders in a translation from the given params.
 *
 * - If `params` is a plain object, each `{name}` is replaced by
 *   `params[name]` (missing → emptied).
 * - If `params` is an array (legacy positional form), values are filled into
 *   the placeholders in order of appearance.
 *
 * Legacy "{{ ... }}" positional placeholders are also filled from an array
 * so old call sites keep working during the migration.
 */
function fillPlaceholders(translation, params) {
    if (params == null) return translation;

    if (Array.isArray(params)) {
        // Legacy positional form: fill both named {name} and legacy {{ ... }}
        // placeholders in order of appearance.
        let i = 0;
        const byOrder = () => {
            const v = params[i++];
            return v === undefined ? "" : v;
        };
        let out = translation.replace(NAMED_RE, byOrder);
        out = out.replace(/\{\{\s*\.\.\.\s*\}\}/g, byOrder);
        return out;
    }

    if (typeof params === "object") {
        // Named form: replace each {name} with params[name].
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
 * Named placeholders are filled from the params object (or array, legacy):
 *
 *   {{ __('Language "{languageName}" added.', { languageName: code }) }}
 *   {{ __('{total} records, {from} - {to} showing', { total, from, to }) }}
 *
 * Legacy "{{ ... }}" pattern keys are still matched against rendered text,
 * and legacy positional array params still fill placeholders in order.
 */
export function __(key, ...args) {
    if (!key || typeof key !== "string") return key;
    let translation = dictView.project[key] || dictView.ui[key];
    if (!translation || translation === key) {
        const pattern =
            matchPatternKey(key, dictView.project) || matchPatternKey(key, dictView.ui);
        if (pattern) translation = applyPattern(pattern.translation, pattern.match);
        else return key;
    }
    if (args.length) {
        // The first arg is the params object/array; later args are ignored
        // (kept for forward-compat with future overloads).
        translation = fillPlaceholders(translation, args[0]);
    }
    return translation;
}

export { BASE_LOCALE };
