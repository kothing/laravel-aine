<template>
    <div class="admin__project-settings-language relative h-full flex flex-col">
        <project-header :project="project"></project-header>

        <div class="flex flex-1 overflow-y-auto">
            <div class="w-3/12 bg-white overflow-x-hidden">
                <settings-sidebar :project="project"></settings-sidebar>
            </div>

            <div class="w-9/12 overflow-x-hidden">
                <div class="p-4">
                    <!-- 板块切换：Localization / Translations -->
                    <div class="mb-4 border-b border-gray-200">
                        <div class="flex items-center gap-1">
                            <button
                                type="button"
                                @click="tab = 'locales'"
                                :class="tabClass('locales')"
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

                    <locales v-if="tab === 'locales'" embedded />
                    <project-translations v-else embedded />
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import ProjectHeader from "../components/ProjectHeader.vue";
import SettingsSidebar from "./sections/SettingsSidebar.vue";
import Locales from "./Locales.vue";
import ProjectTranslations from "./ProjectTranslations.vue";
import projectBreadcrumb from "../../mixins/projectBreadcrumb";
import { useAdminStore } from "../../store";

export default {
    name: "ProjectLanguage",
    components: {
        ProjectHeader,
        SettingsSidebar,
        Locales,
        ProjectTranslations,
    },
    mixins: [projectBreadcrumb],
    data() {
        return {
            tab: "locales",
            project: useAdminStore().currentProject || {},
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
