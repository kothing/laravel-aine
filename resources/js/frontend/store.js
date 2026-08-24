import { defineStore } from "pinia";
import http from "./http";
import { api, setApiLocale } from "./api";
import { PROJECTS, COLLECTIONS } from "./config";

const STORAGE_KEY = "aine_frontend_locale";

// In-flight de-duplication for requests issued directly via axios
// (settings / project info). Multiple callers (e.g. router guard + layout)
// sharing the same promise issue a single HTTP request.
let settingsRequest = null;
const projectRequests = {};

export const useFrontendStore = defineStore("frontend", {
    state: () => ({
        settings: {
            name: null,
            description: null,
            version: "0.0.1",
        },
        settingsLoaded: false,

        // Project info per identifier (locales, default_locale, ...)
        projectsInfo: {},

        // Currently displayed language (applies to both systems).
        locale: localStorage.getItem(STORAGE_KEY) || null,
        localeReady: false,

        // CMS pages (navigation + page routes under /content)
        pages: [],
        pagesLoaded: false,
    }),

    actions: {
        async loadSettings() {
            if (this.settingsLoaded) {
                return this.settings;
            }
            if (settingsRequest) {
                return settingsRequest;
            }

            settingsRequest = (async () => {
                // /settings returns a plain object (not the API envelope):
                // { name, description, version }
                const response = await http.get("/settings");
                if (response && response.data && typeof response.data === "object") {
                    this.settings = {
                        name: response.data.name || null,
                        description: response.data.description || null,
                        version: response.data.version || "0.0.1",
                    };
                    this.settingsLoaded = true;
                }
                return this.settings;
            })().finally(() => {
                settingsRequest = null;
            });

            return settingsRequest;
        },

        /**
         * Load project info (locales / default language) from the API and
         * cache it per identifier.
         */
        async loadProject(identifier) {
            if (this.projectsInfo[identifier]) {
                return this.projectsInfo[identifier];
            }
            if (projectRequests[identifier]) {
                return projectRequests[identifier];
            }

            projectRequests[identifier] = (async () => {
                // /api/project/{identifier} returns the API envelope, so the
                // project info lives at response.data.data.
                const response = await http.get(`/api/project/${identifier}`);
                this.projectsInfo[identifier] = (response && response.data && response.data.data) || null;
                return this.projectsInfo[identifier];
            })().finally(() => {
                delete projectRequests[identifier];
            });

            return projectRequests[identifier];
        },

        /**
         * Load both projects and settle the language. Uses the CMS project's
         * locales as the site language list.
         */
        async initLocale() {
            const cms = await this.loadProject(PROJECTS.cms.identifier);
            await this.loadProject(PROJECTS.directory.identifier);

            const locales = this.cmsProjectLocales;
            if (!this.locale || !locales.includes(this.locale)) {
                this.locale = (cms && cms.default_locale) || locales[0] || "en";
                localStorage.setItem(STORAGE_KEY, this.locale);
            }

            setApiLocale(this.locale);
            this.localeReady = true;
        },

        /**
         * Switch the displayed language and persist the choice.
         */
        setLocale(locale) {
            if (!locale || locale === this.locale) return;
            this.locale = locale;
            localStorage.setItem(STORAGE_KEY, locale);
            setApiLocale(locale);
        },

        async loadPages(force = false) {
            if (this.pagesLoaded && !force) {
                return this.pages;
            }
            this.pages = (await api.collection(PROJECTS.cms.identifier, COLLECTIONS.pages, { timestamps: true })) || [];
            this.pagesLoaded = true;
            return this.pages;
        },
    },

    getters: {
        cmsProject: (state) => state.projectsInfo[PROJECTS.cms.identifier] || null,
        cmsProjectLocales: (state) => {
            const p = state.projectsInfo[PROJECTS.cms.identifier];
            if (p && Array.isArray(p.locales) && p.locales.length) return p.locales;
            return ["en"];
        },
        defaultLocale: (state) => {
            const p = state.projectsInfo[PROJECTS.cms.identifier];
            return (p && p.default_locale) || "en";
        },
    },
});
