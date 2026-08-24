<template>
    <div class="admin__language">
        <!-- 板块切换：Localization / Translations -->
        <div class="border-b border-gray-200 px-3 pt-3">
            <div class="flex items-center gap-1">
                <button
                    type="button"
                    @click="tab = 'localization'"
                    :class="tabClass('localization')"
                >
                    <i class="fas fa-globe mr-1.5"></i>{{ __('Localization') }}
                </button>
                <button
                    type="button"
                    @click="tab = 'translations'"
                    :class="tabClass('translations')"
                >
                    <i class="fas fa-language mr-1.5"></i>{{ __('Translations') }}
                </button>
            </div>
        </div>

        <localization v-if="tab === 'localization'" embedded />
        <translations v-else embedded />
    </div>
</template>

<script>
import Localization from "./Localization.vue";
import Translations from "./Translations.vue";

export default {
    name: "Language",
    components: { Localization, Translations },
    data() {
        return {
            tab: "localization",
        };
    },
    methods: {
        tabClass(name) {
            return name === this.tab
                ? "-mb-px inline-flex items-center border-b-2 border-indigo-600 px-4 py-2.5 text-sm font-semibold text-indigo-600 transition"
                : "-mb-px inline-flex items-center border-b-2 border-transparent px-4 py-2.5 text-sm font-semibold text-gray-500 transition hover:border-gray-300 hover:text-gray-700";
        },
    },
};
</script>
