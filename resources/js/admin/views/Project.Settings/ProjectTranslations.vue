<template>
    <div class="admin__project-settings-translations relative h-full flex flex-col">
        <project-header v-if="!embedded" :project="project"></project-header>

        <div class="flex flex-1 overflow-y-auto">
            <div v-if="!embedded" class="w-3/12 bg-white overflow-x-hidden">
                <settings-sidebar :project="project"></settings-sidebar>
            </div>

            <div class="overflow-x-hidden" :class="[embedded ? 'w-full' : 'w-9/12']">
                <div class="p-4">
                    <!-- Header -->
                    <div class="flex flex-wrap items-center justify-between gap-3 pb-4">
                        <p class="text-sm text-gray-500">
                            {{ __('Translate this project\'s own strings (collection names, field labels, placeholders). Source language:') }}
                            <strong>{{ baseLocale.toUpperCase() }}</strong>
                            <template v-if="locale !== baseLocale">
                                {{ __('— fill in the') }} <strong>{{ locale.toUpperCase() }}</strong> {{ __('translations below.') }}
                            </template>
                            <template v-else>
                                {{ __('— edit the default-language labels; saved values override the raw source strings in the admin UI.') }}
                            </template>
                        </p>
                    </div>
                    <!-- Project locale dropdown -->
                    <div class="flex items-center gap-2 mb-3">
                        <language-select v-model="locale" :locales="locales" @change="load" />
                    </div>

                    <!-- Add custom string -->
                    <div class="mb-4 flex flex-wrap items-center gap-2 rounded-lg border border-gray-200 bg-white p-3">
                        <input
                            v-model="newSource"
                            type="text"
                            :placeholder="__('New project string (default language)')"
                            class="flex-1 min-w-40 rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none"
                            @keyup.enter="addString"
                        />
                        <input
                            v-model="newValue"
                            type="text"
                            :placeholder="
                                locale === baseLocale
                                    ? __('Value (same as source)')
                                    : __('Translation ({locale})', { locale: locale.toUpperCase() })
                            "
                            class="flex-1 min-w-40 rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none"
                            @keyup.enter="addString"
                        />
                        <button
                            type="button"
                            class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-500"
                            @click="addString"
                        >
                            {{ __('Add string') }}
                        </button>
                    </div>

                    <!-- Search + save -->
                    <div class="mb-4 flex items-center gap-2">
                        <div class="relative flex-1 max-w-md">
                            <input
                                v-model="search"
                                type="text"
                                :placeholder="__('Search strings...')"
                                class="w-full rounded-md border border-gray-300 py-2 pl-9 pr-3 text-sm focus:border-indigo-500 focus:outline-none"
                            />
                            <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-xs"></i>
                        </div>
                        <span class="text-sm text-gray-500">
                            {{ translatedCount }} / {{ strings.length }} translated
                        </span>
                        <button
                            type="button"
                            class="ml-auto rounded-md bg-green-600 px-5 py-2 text-sm font-medium text-white hover:bg-green-500 disabled:opacity-50"
                            :disabled="saving || hasPlaceholderErrors"
                            @click="saveAll"
                        >
                            {{ saving ? 'Saving…' : 'Save translations' }}
                        </button>
                    </div>

                    <!-- Strings table -->
                    <!-- Placeholder hint for translators. -->
                    <p v-if="!loading && filtered.length" class="mb-2 text-xs text-gray-500">
                        <span class="rounded bg-red-100 px-1 font-mono text-red-700">{{ '{{ ... }}' }}</span>
                        {{ __('marks a runtime value — keep the same number in your translation.') }}
                    </p>

                    <div v-if="loading" class="space-y-2">
                        <div v-for="n in 8" :key="n" class="h-12 animate-pulse rounded-lg bg-gray-100"></div>
                    </div>

                    <div v-else-if="!filtered.length" class="rounded-lg border border-dashed border-gray-300 py-16 text-center text-gray-500">
                        {{ __('No strings found — add fields or collections first.') }}
                    </div>

                    <div v-else class="overflow-hidden rounded-lg border border-gray-200 bg-white">
                        <div
                            v-for="(item, index) in filtered"
                            :key="item.source"
                            class="border-b border-gray-100 px-4 py-2.5 last:border-b-0 hover:bg-gray-50"
                        >
                            <div class="flex items-center gap-3">
                                <span class="w-6 text-xs text-gray-400 select-none">{{ index + 1 }}</span>

                                <div class="flex-1 min-w-0">
                                    <!-- Source string with placeholders highlighted in red. -->
                                    <div class="truncate text-sm font-medium text-gray-800" :title="item.source">
                                        <span v-for="(seg, i) in splitPlaceholders(item.source)" :key="i">
                                            <span v-if="seg.placeholder" class="rounded bg-red-100 px-1 font-mono text-red-700">{{ seg.text }}</span>
                                            <span v-else>{{ seg.text }}</span>
                                        </span>
                                    </div>
                                </div>

                                <input
                                    v-model="item.value"
                                    type="text"
                                    class="w-1/2 min-w-48 rounded-md border px-3 py-1.5 text-sm focus:outline-none"
                                    :class="placeholderError(item) ? 'border-red-500 bg-red-50' : 'border-gray-300 focus:border-indigo-500'"
                                    :placeholder="locale === baseLocale ? item.source : __('Not translated')"
                                />
                            </div>
                            <!-- Placeholder validation error message. -->
                            <div v-if="placeholderError(item)" class="mt-1 pl-9 text-xs text-red-600">
                                {{ placeholderError(item) }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import { __ } from '../../translations/engine';

import ProjectHeader from "../components/ProjectHeader.vue";
import SettingsSidebar from "./sections/SettingsSidebar.vue";
import LanguageSelect from "../../components/LanguageSelect.vue";
import { useAdminStore } from "../../store";

export default {
    name: "ProjectTranslations",
    props: {
        // Boolean type is required: `embedded` written as a bare attribute
        // arrives as "" and only Boolean-typed props get cast to true.
        embedded: {
            type: Boolean,
            default: false,
        },
    },
    components: {
        ProjectHeader,
        SettingsSidebar,
        LanguageSelect,
    },
    data() {
        return {
            // Pre-seed from the store (loaded by the router guard before
            // this page renders) so the shell never flashes blank while
            // mounted() refreshes the project data.
            project: useAdminStore().currentProject || {},
            locale: "en",
            baseLocale: "en",
            locales: ["en"],
            strings: [],
            search: "",
            loading: true,
            saving: false,
            newSource: "",
            newValue: "",
        };
    },
    computed: {
        filtered() {
            if (!this.search.trim()) return this.strings;
            const q = this.search.toLowerCase();
            return this.strings.filter((s) => s.source.toLowerCase().includes(q));
        },
        translatedCount() {
            return this.strings.filter((s) => s.value && s.value.trim()).length;
        },
        // True when ANY translation (not just the search-filtered view) has a
        // placeholder mismatch. Checks all strings because saveAll() submits
        // the full list — a mismatch hidden by the search box must still block.
        hasPlaceholderErrors() {
            return this.strings.some((item) => this.placeholderError(item));
        },
    },
    async mounted() {
        // The router guard already loaded the project into the store before
        // this page rendered (data() seeds it); only the page's own data
        // needs loading here.
        this.locale = this.project.default_locale || "en";
        await this.load();
    },
    methods: {
        labelOf(l) {
            if (l === "en") return "English (en)";
            if (l === "zh") return "中文 (zh)";
            return l.toUpperCase() + " (" + l + ")";
        },

        // --- Placeholder helpers ------------------------------------------------
        // Same logic as the global Translations.vue: project strings (field
        // labels, placeholders, descriptions) may contain named "{name}"
        // runtime placeholders (or legacy "{{ ... }}"), and a translation
        // must keep the same names/count.

        splitPlaceholders(str) {
            if (!str) return [];
            const re = /\{\{\s*\.\.\.\s*\}\}|\{[a-zA-Z_][a-zA-Z0-9_]*\}/g;
            const segments = [];
            let last = 0;
            let m;
            while ((m = re.exec(str)) !== null) {
                if (m.index > last) segments.push({ text: str.slice(last, m.index), placeholder: false });
                segments.push({ text: m[0], placeholder: true });
                last = re.lastIndex;
            }
            if (last < str.length) segments.push({ text: str.slice(last), placeholder: false });
            return segments;
        },

        extractPlaceholders(str) {
            if (!str) return [];
            const re = /\{\{\s*\.\.\.\s*\}\}|\{([a-zA-Z_][a-zA-Z0-9_]*)\}/g;
            const names = [];
            let m;
            while ((m = re.exec(str)) !== null) {
                names.push(m[1] || "_pos");
            }
            return names;
        },

        placeholderError(item) {
            if (!item.value || !item.value.trim()) return null;
            const src = this.extractPlaceholders(item.source);
            const val = this.extractPlaceholders(item.value);
            if (src.length !== val.length) {
                return __('Source has {sourceCount} placeholders, translation has {valueCount}.', { sourceCount: src.length, valueCount: val.length });
            }
            const srcNamed = src.filter((n) => n !== "_pos").sort();
            const valNamed = val.filter((n) => n !== "_pos").sort();
            if (srcNamed.length === src.length && valNamed.length === val.length) {
                const mismatch = srcNamed.some((n, i) => n !== valNamed[i]);
                if (mismatch) {
                    return __('Placeholder names in the translation must match the source.');
                }
            }
            return null;
        },
        async load() {
            this.loading = true;
            try {
                const { data } = await axios.get(
                    `projects/settings/translations/${this.$route.params.project_id}`,
                    { params: { locale: this.locale } }
                );
                this.baseLocale = data.base_locale;
                this.locales = data.locales || [this.baseLocale];
                // In the default-language view, strings without an explicit
                // value show the source text itself — the input is pre-filled
                // (not just a placeholder) so it can be edited in place.
                // Other locales start empty ("Not translated").
                this.strings = (data.strings || []).map((s) => ({
                    source: s.source,
                    value: s.value ?? (this.locale === data.base_locale ? s.source : null),
                }));
            } catch (error) {
                console.error("Failed to load project translations:", error);
            } finally {
                this.loading = false;
            }
        },
        async saveAll() {
            if (this.hasPlaceholderErrors) {
                this.$toast.error(__('Some translations have placeholder mismatches. Please fix them before saving.'));
                return;
            }
            this.saving = true;
            try {
                const items = this.strings.map((s) => ({
                    source: s.source,
                    value: (s.value || "").trim(),
                }));
                const { data } = await axios.post(
                    `projects/settings/translations/${this.$route.params.project_id}/save`,
                    { locale: this.locale, items }
                );
                this.$toast.success(__('Saved {translationsCount} translations.', { translationsCount: data.saved }));
                // Refresh the project translation dict so open pages update immediately.
                await useAdminStore().loadProjectTranslationsDict();
            } catch (error) {
                console.error("Failed to save project translations:", error);
                this.$toast.error(__('Failed to save translations.'));
            } finally {
                this.saving = false;
            }
        },
        async addString() {
            const source = this.newSource.trim();
            if (!source) return;

            // Validate placeholder count when a translation is provided.
            if (this.newValue.trim()) {
                const item = { source, value: this.newValue };
                const err = this.placeholderError(item);
                if (err) {
                    this.$toast.error(err);
                    return;
                }
            }

            try {
                await axios.post(
                    `projects/settings/translations/${this.$route.params.project_id}/add`,
                    { source }
                );
                if (this.newValue.trim()) {
                    await axios.post(
                        `projects/settings/translations/${this.$route.params.project_id}/save`,
                        { locale: this.locale, items: [{ source, value: this.newValue.trim() }] }
                    );
                }
                this.newSource = "";
                this.newValue = "";
                this.$toast.success(__('String added.'));
                await this.load();
                await useAdminStore().loadProjectTranslationsDict();
            } catch (error) {
                console.error("Failed to add string:", error);
                this.$toast.error(__('Failed to add string.'));
            }
        },
    },
};
</script>
