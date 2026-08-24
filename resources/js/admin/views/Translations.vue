<template>
    <div class="admin__translations m-3 p-3">
        <!-- Header -->
        <div class="flex flex-wrap items-center justify-between gap-3 pb-4">
            <p class="text-sm text-gray-500 mt-1">
                {{ __('Translate the admin interface. The source language is') }}
                <strong>{{ sourceLocale.toUpperCase() }}</strong>
                <template v-if="locale !== sourceLocale">
                    {{ __('— fill in the') }} <strong>{{ locale.toUpperCase() }}</strong> {{ __('translations below.') }}
                </template>
                <template v-else>
                    {{ __('— edit the English labels; saved values override the built-in UI strings.') }}
                </template>
            </p>
        </div>
        <div class="flex items-center gap-2 mb-3">
            <language-select v-model="locale" :locales="locales" @change="load" />
        </div>

        <!-- Add new string -->
        <div class="mb-4 flex flex-wrap items-center gap-2 rounded-lg border border-gray-200 bg-white p-3">
            <input
                v-model="newSource"
                type="text"
                :placeholder="__('New UI string (English)')"
                class="flex-1 min-w-40 rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none"
                @keyup.enter="addString"
            />
            <input
                v-model="newValue"
                type="text"
                :placeholder="
                    locale === sourceLocale
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

        <!-- Search -->
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
                {{ translatedCount }} / {{ strings.length }} {{ __('translated') }}
            </span>
            <button
                type="button"
                class="ml-auto rounded-md bg-green-600 px-5 py-2 text-sm font-medium text-white hover:bg-green-500 disabled:opacity-50"
                :disabled="saving || hasPlaceholderErrors"
                @click="saveAll"
            >
                {{ saving ? __('Saving…') : __('Save translations') }}
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
            {{ __('No strings found.') }}
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
                        :placeholder="locale === sourceLocale ? item.source : __('Not translated')"
                    />
                </div>
                <!-- Placeholder validation error message. -->
                <div v-if="placeholderError(item)" class="mt-1 pl-9 text-xs text-red-600">
                    {{ placeholderError(item) }}
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import { __ } from '../translations/engine';

import { useAdminStore } from "../store";
import LanguageSelect from "../components/LanguageSelect.vue";

export default {
    name: "Translations",
    props: {
        // Boolean type is required: `embedded` written as a bare attribute
        // arrives as "" and only Boolean-typed props get cast to true.
        embedded: {
            type: Boolean,
            default: false,
        },
    },
    components: {
        LanguageSelect,
    },
    data() {
        return {
            store: useAdminStore(),
            locale: "en",
            baseLocale: "en",
            // The language the UI strings are authored in (always English).
            // Unlike baseLocale (the default display language, which the
            // admin can change), only the source language is read-only in
            // the editor.
            sourceLocale: "en",
            locales: ["en", "zh"],
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
        // placeholder mismatch. Used to disable the bulk "Save translations"
        // button so bad data never lands. Checks all strings, not `filtered`,
        // because saveAll() submits the full list — a mismatch hidden by the
        // search box must still block the save.
        hasPlaceholderErrors() {
            return this.strings.some((item) => this.placeholderError(item));
        },
    },
    created() {
        useAdminStore().setTopbarContent({
            page: "translations",
            type: "settings",
            title: "Translations",
            breadcrumb: [
                { name: "Dashboard", url: "/", icon: "fa fa-tachometer-alt" },
                { name: "Translations", icon: "fas fa-language" },
            ],
        });
    },
    async mounted() {
        // loadUiLocales() syncs the store's UI language to the globally
        // configured default (Localization → "Set as default"), which is the
        // language whose strings are edited here by default.
        await useAdminStore().loadUiLocales();
        this.locale = useAdminStore().uiLocale || "en";
        await this.load();
    },
    methods: {
        labelOf(l) {
            if (l === "en") return "English (en)";
            if (l === "zh") return "中文 (zh)";
            return l.toUpperCase() + " (" + l + ")";
        },

        // --- Placeholder helpers ------------------------------------------------
        // The source strings use "{{ ... }}" as a runtime-value placeholder
        // (see extract-admin-strings.js). Translators must keep the same
        // number of placeholders in their translation; otherwise the engine
        // can't fill the values back in. These helpers power the in-editor
        // highlight + save-time validation so bad data never lands.

        // Split a string into alternating { text, placeholder } segments so
        // the template can render placeholders in red. Recognizes both the
        // named "{name}" form and the legacy "{{ ... }}" form.
        splitPlaceholders(str) {
            if (!str) return [];
            // Match {name} (named) OR {{ ... }} (legacy positional).
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

        // Extract placeholder names from a string. Returns an array of names
        // (e.g. ["languageName", "count"]); legacy "{{ ... }}" yields "_pos".
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

        // Return an error message if the translation's placeholders don't
        // match the source, otherwise null. Empty translations are skipped.
        // Match is by NAME SET: every placeholder name in the source must
        // appear in the translation, with the same count (no drops, no
        // renames, no duplicates). Legacy "{{ ... }}" is matched by count.
        placeholderError(item) {
            if (!item.value || !item.value.trim()) return null;
            const src = this.extractPlaceholders(item.source);
            const val = this.extractPlaceholders(item.value);
            if (src.length !== val.length) {
                return __('Source has {sourceCount} placeholders, translation has {valueCount}.', { sourceCount: src.length, valueCount: val.length });
            }
            // For named placeholders, verify the name sets match (no renames).
            const srcNamed = src.filter((n) => n !== "_pos").sort();
            const valNamed = val.filter((n) => n !== "_pos").sort();
            if (srcNamed.length === src.length && valNamed.length === val.length) {
                // All named: compare sorted name arrays.
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
                const { data } = await axios.get("translations", { params: { locale: this.locale } });
                this.baseLocale = data.base_locale;
                this.sourceLocale = data.source_locale || "en";
                this.locales = data.locales || ["en", "zh"];
                // In the source-language view, strings without an explicit
                // value show the source text itself (pre-filled, editable).
                // Other locales start empty ("Not translated").
                this.strings = (data.strings || []).map((s) => ({
                    source: s.source,
                    value: s.value ?? (this.locale === this.sourceLocale ? s.source : null),
                }));
            } catch (error) {
                console.error("Failed to load translations:", error);
            } finally {
                this.loading = false;
            }
        },
        async saveAll() {
            // Block the save when any visible translation has a placeholder
            // mismatch — the translator must fix those first.
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
                const { data } = await axios.post("translations/save", { locale: this.locale, items });
                this.$toast.success(__('Saved {translationsCount} translations.', { translationsCount: data.saved }));
                // Refresh the UI engine so open pages update immediately.
                await useAdminStore().refreshUiLocale();
            } catch (error) {
                console.error("Failed to save translations:", error);
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
                await axios.post("translations/add", { source });
                if (this.newValue.trim()) {
                    await axios.post("translations/save", {
                        locale: this.locale,
                        items: [{ source, value: this.newValue.trim() }],
                    });
                }
                this.newSource = "";
                this.newValue = "";
                this.$toast.success(__('String added.'));
                await this.load();
                await useAdminStore().refreshUiLocale();
            } catch (error) {
                console.error("Failed to add string:", error);
                this.$toast.error(__('Failed to add string.'));
            }
        },
    },
};
</script>
