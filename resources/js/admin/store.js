import { defineStore } from 'pinia';
import axios from 'axios';
import {
    setLocale,
    setProjectDict,
    setBaseUiDict,
    BASE_LOCALE,
    loadCachedUiDict,
    saveCachedUiDict,
    loadCachedProjectDict,
    saveCachedProjectDict,
} from './translations/engine';

/**
 * Reveal the admin shell once the boot UI language has been applied. The
 * `data-ui-pending` attribute (set by admin.js before mount) keeps the app
 * hidden until then, so a page refresh never flashes the English UI before
 * the saved language's translations arrive.
 */
function markUiReady() {
    const el = document.getElementById('admin');
    if (el) el.removeAttribute('data-ui-pending');
}

/**
 * The globally configured default UI language (Settings → Localization →
 * "Set as default") is the ONLY way to change the admin's display language —
 * the per-user topbar switcher was removed. The last known default is cached
 * so a page refresh can apply its dictionary synchronously (no network) and
 * never flashes the base language before translations arrive.
 */
const UI_BASE_CACHE_KEY = 'aine_admin_ui_base';

function loadCachedUiBase() {
    try {
        return localStorage.getItem(UI_BASE_CACHE_KEY) || null;
    } catch (error) {
        return null;
    }
}

function saveCachedUiBase(baseLocale) {
    try {
        if (baseLocale) localStorage.setItem(UI_BASE_CACHE_KEY, baseLocale);
        else localStorage.removeItem(UI_BASE_CACHE_KEY);
    } catch (error) {
        // Storage unavailable: the cache is a nicety, never fatal.
    }
}

export const useAdminStore = defineStore('admin', {
    state: () => ({
        user: {},
        settings: {},
        currentProject: null,
        currentCollection: null,
        // True while the router is navigating to another route (lazy chunk
        // + async guard data still loading). The Layout shell shows a
        // transition overlay while this is set, so route switches never
        // flash a half-rendered page.
        routeLoading: false,
        // Timestamp of the last successful project load; the router guard
        // uses it to decide when a silent background refresh is needed.
        currentProjectLoadedAt: 0,
        topbarContent: null,
        columnSettings: [],
        // The admin UI language equals the globally configured default
        // (Localization → "Set as default"); there is no per-user choice.
        uiLocale: BASE_LOCALE,
        uiBaseLocale: BASE_LOCALE,
        // Monotonic counter so stale async responses (project dicts fetched
        // for a previous language) are discarded after a faster switch.
        _localeSeq: 0,
        // Monotonic counter for project loads: discards late-arriving
        // background refreshes / failures after a project switch.
        _projectSeq: 0,
    }),

    actions: {
        /**
         * Re-apply the current UI language's dictionary and refresh its
         * cache — used after translations are saved so open pages update
         * immediately. This never changes the UI language itself: the global
         * default (Localization → "Set as default") is the only way to do
         * that, via initUiLocale().
         */
        async refreshUiLocale() {
            const seq = ++this._localeSeq;
            // The UI is authored in English and dictionary keys are English
            // source strings, so the engine base is always English — never
            // the database base_locale.
            const dict = await setLocale(this.uiLocale, BASE_LOCALE);
            if (seq !== this._localeSeq) return;
            if (dict && Object.keys(dict).length) saveCachedUiDict(this.uiLocale, dict);
            // Base-language UI: fetch its dictionary too so edited English
            // strings override the built-in labels.
            if (this.uiLocale === BASE_LOCALE) {
                await this.loadBaseUiDict();
            }
            await this.loadProjectTranslationsDict();
        },
        /**
         * Apply the global default UI language. `expectedBase` is optional:
         * callers that already know the new default (Localization → "Set as
         * default") pass it so a stale cached apply is skipped.
         */
        async initUiLocale(expectedBase = null) {
            const seq = ++this._localeSeq;

            // Drop the legacy per-user language preference (old topbar
            // switcher) — the global default is the single source of truth.
            try {
                localStorage.removeItem('aine_admin_locale');
            } catch (error) {
                // ignore
            }

            // Phase 1 — synchronous, no network. Apply the cached dictionary
            // for the global default language in the same task as the app
            // mount, so a page refresh never flashes the base language
            // before translations arrive. The shell is revealed immediately;
            // the fresh dictionary below then refreshes the cache.
            const bootBase = expectedBase || loadCachedUiBase() || BASE_LOCALE;
            if (bootBase !== BASE_LOCALE) {
                const cached = loadCachedUiDict(bootBase);
                if (cached) {
                    this.uiLocale = bootBase;
                    await setLocale(this.uiLocale, BASE_LOCALE, cached);
                    markUiReady();
                }
            } else {
                // Base-language UI: cached English overrides apply
                // synchronously too (edited strings from the Translations
                // page).
                const cached = loadCachedUiDict(BASE_LOCALE);
                if (cached) {
                    setBaseUiDict(cached);
                    markUiReady();
                }
            }

            // Phase 2 — network: authoritative locale list + fresh dictionary.
            await this.loadUiLocales();
            if (seq !== this._localeSeq) return;

            this.uiLocale = this.uiBaseLocale || BASE_LOCALE;
            // Cold start (nothing cached): the app stays hidden until this
            // resolves, so even the very first paint is in the right
            // language. Cache hit: refreshes the dictionary and re-translates
            // if translations changed.
            const dict = await setLocale(this.uiLocale, BASE_LOCALE);
            if (seq !== this._localeSeq) return;
            if (dict && Object.keys(dict).length) saveCachedUiDict(this.uiLocale, dict);
            // Base-language UI: also load its overlay dictionary.
            if (this.uiLocale === BASE_LOCALE) {
                await this.loadBaseUiDict();
                if (seq !== this._localeSeq) return;
            }

            markUiReady();
            await this.loadProjectTranslationsDict();
        },
        /**
         * Fetch the base-language UI dictionary and apply it as an overlay
         * (admin UI in the base language). Base-language strings are edited
         * on the Translations page; saved values override the built-in
         * labels.
         */
        async loadBaseUiDict() {
            try {
                const { data } = await axios.get('translations/dict', { params: { locale: BASE_LOCALE } });
                const dict = (data && data.dict) || {};
                if (Object.keys(dict).length) saveCachedUiDict(BASE_LOCALE, dict);
                setBaseUiDict(dict);
                return dict;
            } catch (error) {
                console.warn('Failed to load base UI translations:', error);
                return {};
            }
        },
        async loadUiLocales() {
            let locales = [];
            try {
                const { data } = await axios.get('translations/locales');
                if (data) {
                    this.uiBaseLocale = data.base_locale || BASE_LOCALE;
                    // The UI language always follows the global default.
                    this.uiLocale = this.uiBaseLocale;
                    if (Array.isArray(data.locales) && data.locales.length) {
                        locales = data.locales;
                    }
                    saveCachedUiBase(this.uiBaseLocale);
                }
            } catch (error) {
                console.warn('Failed to load UI locales:', error);
            }
            return locales;
        },
        async loadProjectTranslationsDict() {
            const projectId = this.currentProject?.id;
            if (!projectId) {
                setProjectDict({});
                return {};
            }
            const seq = this._localeSeq;

            // Synchronous: a cached dictionary for this project + language
            // applies immediately (before the page renders), so refreshing a
            // project page never flashes its untranslated strings either.
            const cached = loadCachedProjectDict(projectId, this.uiLocale);
            if (cached) setProjectDict(cached);

            try {
                const { data } = await axios.get(
                    `projects/settings/translations/${projectId}/dict`,
                    { params: { locale: this.uiLocale } }
                );
                // Double guard: the language seq protects against fast locale
                // switches, the project id against a project switch while this
                // fire-and-forget request was in flight (its late response
                // must never overwrite the dictionary of the current project).
                if (seq !== this._localeSeq) return {};
                if (this.currentProject?.id !== projectId) return {};
                const dict = (data && data.dict) || {};
                setProjectDict(dict);
                if (Object.keys(dict).length) saveCachedProjectDict(projectId, this.uiLocale, dict);
                return dict;
            } catch (error) {
                if (seq !== this._localeSeq) return {};
                if (this.currentProject?.id !== projectId) return {};
                console.warn('Failed to load project translations:', error);
                // Keep the cached dictionary (if any) so the page stays
                // translated; only clear when there was nothing to fall back on.
                if (!cached) setProjectDict({});
                return {};
            }
        },
        async getUser() {
            return await axios
                .get('user')
                .then((response) => { this.user = response.data });
        },
        async setCurrentProject(projectId, options = {}) {
            const seq = ++this._projectSeq;

            if (!projectId) {
                this.currentProject = null;
                this.currentCollection = null;
                this.currentProjectLoadedAt = 0;
                setProjectDict({});
                return;
            }
            try {
                // `silent: true` (background re-validation) skips the
                // NProgress bar via the axios interceptors.
                const response = await axios.get('projects/' + projectId, { silent: options.silent });
                // Discard late-arriving responses (e.g. a silent refresh that
                // raced a switch to another project) so they never overwrite
                // newer state.
                if (seq !== this._projectSeq) return;
                this.currentProject = response.data;
                this.currentProjectLoadedAt = Date.now();
                // The translation dictionary is not on the critical path for
                // rendering the page shell — load it in the background.
                this.loadProjectTranslationsDict();
            } catch (error) {
                console.error('Failed to load project:', error);
                // Stale failure from an older load: never touch newer state.
                if (seq !== this._projectSeq) return;
                // On a background refresh failure keep whatever data we had
                // (a fresh foreground load has nothing cached anyway) and
                // reset the timestamp so the next navigation retries. When
                // the cached project is kept, its translation dictionary
                // stays valid too.
                this.currentProjectLoadedAt = 0;
                if (this.currentProject?.id !== projectId) {
                    this.currentProject = null;
                    this.currentCollection = null;
                    setProjectDict({});
                }
            }
        },
        async setCurrentCollection({ projectId, colId }) {
            if (!colId) {
                this.currentCollection = null;
                return;
            }
            try {
                const response = await axios.get('collections/show/' + projectId + '/' + colId);
                this.currentCollection = response.data.collection;
            } catch (error) {
                console.error('Failed to load collection:', error);
                this.currentCollection = null;
            }
        },
        setColumns(obj) {
            this.columnSettings.push(obj);
        },
        updateColumn(obj) {
            const found = this.columnSettings.find(
                (o) => o.project_id === obj.project_id && o.collection_id === obj.collection_id
            );
            if (found) {
                found.columns = obj.columns;
            }
        },
        setTopbarContent(component) {
            this.topbarContent = component;
        },
        clearTopbarContent() {
            this.topbarContent = null;
        },
        logout() {
            this.$reset();
        },
    },

    getters: {
        userRoles: (state) => state.user?.roles || [],
    },
});
