<template>
    <div class="admin__project-settings-locales relative h-full flex flex-col">
        <project-header v-if="!embedded" :project="project"></project-header>

        <div class="flex flex-1 overflow-y-auto">
            <div v-if="!embedded" class="w-3/12 bg-white overflow-x-hidden">
                <settings-sidebar :project="project"></settings-sidebar>
            </div>
            <div class="overflow-x-hidden" :class="[embedded ? 'w-full' : 'w-9/12']">
                <div class="p-4">
                    <h4 v-if="!embedded" class="mb-2 p-2 font-bold text-xl">{{ __('Localization') }}</h4>

                    <div class="w-full bg-white mt-2 rounded-md p-4">
                        <div>
                            <div class="w-full flex justify-between">
                                <div class="text-lg font-bold">
                                    {{ __('Available Locales') }}
                                </div>
                            </div>
                            <div class="overflow-x-auto mt-1 flex border rounded-md">
                                <!-- Loading skeleton: keeps the layout stable
                                     while the locales request is in flight. -->
                                <div v-if="loading" class="w-full p-4">
                                    <div
                                        v-for="n in 3"
                                        :key="n"
                                        class="h-10 mb-2 bg-gray-100 animate-pulse rounded-md"
                                    ></div>
                                </div>
                                <table v-else class="min-w-full divide-y divide-gray-200">
                                    <tbody class="bg-white divide-y divide-gray-200">
                                        <tr
                                            v-for="locale in localeList"
                                            :key="locale"
                                        >
                                            <td class="px-6 py-3 text-sm align-top">
                                                {{ locale }}
                                            </td>
                                            <td class="px-6 py-3 text-sm align-top">
                                                {{ getLocale(locale) }}
                                            </td>
                                            <td class="px-6 py-3 text-sm w-px text-center font-bold whitespace-nowrap">
                                                <div v-if="locale == project.default_locale">
                                                    {{ __('Default') }}
                                                </div>
                                                <div
                                                    v-else
                                                    class="ml-2 cursor-pointer text-indigo-500 py-1 px-3 rounded-md hover:bg-gray-100"
                                                    @click="setDefaultLocale(locale)"
                                                >
                                                    {{ __('Set as default') }}
                                                </div>
                                            </td>
                                            <td
                                                class="px-6 py-3 text-sm w-px text-center"
                                            >
                                                <div
                                                    v-if="locale != project.default_locale"
                                                    class="ml-2 cursor-pointer text-red-500 py-1 px-3 rounded-md hover:bg-gray-100"
                                                    @click="deleteLocale(locale)"
                                                >
                                                    <i class="fa fa-trash-alt"></i>
                                                </div>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <div class="mt-10">
                            <div class="w-full flex justify-between">
                                <div class="text-lg font-bold">
                                    {{ __('Add a Locale') }}
                                </div>
                            </div>

                            <div class="flex">
                                <div class="w-1/2 mr-2">
                                    <v-select
                                        :options="locales"
                                        :get-option-key="(o) => o.id"
                                        :get-option-label="(o) => o.id + ' - ' + o.name"
                                        :reduce="(o) => o.id"
                                        :clearable="false"
                                        class="v-select"
                                        :value="(option) => option[0]"
                                        :placeholder="__('Select Locale')"
                                        v-model="addLocaleData"
                                    ></v-select>
                                </div>

                                <ui-button
                                    color="indigo-500"
                                    padding="h-9 flex items-center px-3"
                                    @click="addLocale"
                                >
                                    {{ __('+ Add') }}
                                </ui-button>
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

import UiButton from "../../../components/Button.vue";

import ProjectHeader from "../components/ProjectHeader.vue";

import SettingsSidebar from "./sections/SettingsSidebar.vue";

import localesJson from "../../../locales.json";
import { LOCALES, localeDisplayName } from "../../utils/locales";
import projectBreadcrumb from "../../mixins/projectBreadcrumb";
import { useAdminStore } from "../../store";

export default {
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
        UiButton,
    },

    mixins: [projectBreadcrumb],

    data() {
        const storeProject = useAdminStore().currentProject || {};
        return {
            // Pre-seed from the store (loaded by the router guard before
            // this page renders) so the shell never flashes blank while the
            // page's own getProject() refreshes the data. `loading` only
            // starts true when the store had nothing to seed from.
            project: storeProject,
            loading: !Object.keys(storeProject).length,
            locales: [],
            addLocaleData: null,
        };
    },

    computed: {
        // The store's currentProject.locales arrives as a comma-separated
        // string (raw model attribute), while getProject() splits it into
        // an array. Normalize so v-for always iterates a real array — a
        // string would render one row per character and flash on refresh.
        localeList() {
            const raw = this.project.locales;
            if (Array.isArray(raw)) return raw;
            if (raw && typeof raw === "string") {
                return raw.split(",").filter((l) => l !== "");
            }
            return [];
        },
    },

    methods: {
        getProject(options = {}) {
            // `background: true` (used after add/delete/set-default) refreshes
            // the data without flashing the skeleton — the table already has
            // content at that point.
            if (!options.background) this.loading = true;
            axios
                .get(
                    "projects/settings/locales/" +
                        this.$route.params.project_id
                )
                .then((response) => {
                    this.project = response.data;
                    if (this.project.locales !== null)
                        this.project.locales = response.data.locales.split(",");
                })
                .catch((error) => {
                    console.warn("Failed to load project locales:", error);
                })
                .finally(() => {
                    this.loading = false;
                });
        },

        getLocale(locale) {
            return localeDisplayName(locale) || locale;
        },

        addLocale() {
            axios
                .post(
                    "projects/settings/locales/add/" +
                        this.$route.params.project_id,
                    { locale: this.addLocaleData }
                )
                .then(
                    (response) => {
                        this.$toast.success(__('Locale added to the project.'));
                        this.getProject({ background: true });
                        this.addLocaleData = null;
                    },
                    (error) => {
                        if (error.response && error.response.status == 422) {
                            this.$toast.error(__('Locale has already added.'));
                        }
                    }
                );
        },

        setDefaultLocale(locale) {
            this.$swal
                .fire({
                    title: __('Are you sure'),
                    text: __('you want to change the default locale for this project?'),
                })
                .then((result) => {
                    if (result.isConfirmed) {
                        axios
                            .post(
                                "projects/settings/locales/change-default-locale/" +
                                    this.project.id,
                                { locale: locale }
                            )
                            .then((response) => {
                                this.$toast.success(
                                    __('Default locale has been changed.')
                                );
                                this.getProject({ background: true });
                            });
                    }
                });
        },

        deleteLocale(locale) {
            this.$swal
                .fire({
                    title: __('Are you sure'),
                    text: __('you want to delete this locale?'),
                })
                .then((result) => {
                    if (result.isConfirmed) {
                        axios
                            .post(
                                "projects/settings/locales/delete-locale/" +
                                    this.project.id,
                                { locale: locale }
                            )
                            .then(
                                (response) => {
                                    this.$toast.success(__('Locale deleted.'));
                                    this.getProject({ background: true });
                                },
                                (error) => {
                                    if (error.response && error.response.status == 422) {
                                        this.$toast.error(
                                            __('Default locale can not be deleted.')
                                        );
                                    }
                                }
                            );
                    }
                });
        },
    },

    mounted() {
        this.getProject();

        // Shared with the global Localization page — same language options.
        this.locales = LOCALES;
    },
};
</script>

<style>
/* Slim trigger matching the 36px "Add language" button height.
   Scoped by the page container so other v-select instances are untouched.
   Content (search input / selected label) must be compressed too, otherwise
   it pushes the trigger above 36px. !important beats the global admin.css
   rules (padding: 0.6rem / margin: 10px). */
.admin__localization .v-select .vs__dropdown-toggle,
.admin__project-settings-locales .v-select .vs__dropdown-toggle {
    min-height: 36px;
    padding-top: 4px !important;
    padding-bottom: 4px !important;
}
.admin__localization .v-select .vs__selected,
.admin__localization .v-select .vs__search,
.admin__project-settings-locales .v-select .vs__selected,
.admin__project-settings-locales .v-select .vs__search {
    font-size: 14px;
    margin: 2px 0 !important;
    padding: 1px 4px !important;
}
.admin__localization .v-select .vs__open-indicator,
.admin__project-settings-locales .v-select .vs__open-indicator {
    display: none;
}
.admin__localization .v-select .vs__actions::after,
.admin__project-settings-locales .v-select .vs__actions::after {
    content: "";
    display: inline-block;
    width: 14px;
    height: 14px;
    margin-left: 4px;
    background: url("data:image/svg+xml;charset=utf-8,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke-width='2' stroke='%236b7280'%3E%3Cpath stroke-linecap='round' stroke-linejoin='round' d='m6 9 6 6 6-6'/%3E%3C/svg%3E") no-repeat center / contain;
    pointer-events: none;
    transition: transform 0.15s ease;
}
.admin__localization .v-select.vs--open .vs__actions::after,
.admin__project-settings-locales .v-select.vs--open .vs__actions::after {
    transform: rotate(180deg);
}
</style>
