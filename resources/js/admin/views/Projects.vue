<template>
    <div class="admin__projects-list flex flex-col m-3 p-3">
        <div class="admin__projects-search flex justify-between pb-4">
            <div class="search-input relative flex flex-wrap items-stretch">
                <span class="h-full leading-snug font-normal absolute text-center text-gray-400 absolute bg-transparent rounded-md text-base items-center justify-center w-8 pl-3 py-2">
                    <i class="fas fa-search"></i>
                </span>
                <input
                    type="text"
                    v-model="search"
                    @keyup="getProjects()"
                    :placeholder="__('Search projects...')"
                    class="px-3 py-2 placeholder-gray-400 text-gray-700 bg-white rounded-md text-sm w-full pl-10 border-gray-200 focus:border-gray-300"
                />
            </div>
            <div
                v-if="isSuperAdmin"
                @click="openNewProjectModal = true"
                class="flex justify-center items-center bg-green-500 hover:bg-green-600 text-white mx-2 h-9 w-9 rounded-md cursor-pointer shadow-sm"
            >
                <i class="fas fa-plus-circle text-sm md:text-2xl"></i>
            </div>
        </div>
        <div class="admin__projects-list grid grid-cols-1 gap-4 sm:grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 overflow-y-auto">
            <div
                v-for="project in projects"
                :key="project.id"
                class="flex flex-col bg-white text-gray-900 p-5 rounded-md shadow-sm hover:shadow-dm"
                :class="{ 'opacity-70': !project.status }"
            >
                <div class="flex items-center justify-between shrink-0">
                    <span class="font-bold">{{ project.name }}</span>
                    <span
                        v-if="!project.status"
                        v-tooltip="__('Disabled projects only block external API calls (404). Admin editing remains available.')"
                        class="text-xs font-semibold uppercase tracking-wide text-red-600 bg-red-50 px-2 py-0.5 rounded-full"
                    >
                        {{ __('Inactive') }}
                    </span>
                </div>
                <div class="flex-1 py-2 text-sm overflow-y-auto">{{ project.description }}</div>
                <div class="flex items-center justify-between shrink-0">
                    <div class="flex space-x-2">
                        <router-link
                            :to="{
                                name: 'projects.index',
                                params: { project_id: project.id },
                            }"
                            class="inline-flex items-center px-3 py-1.5 text-xs font-medium text-white bg-indigo-500 hover:bg-indigo-600 rounded-md"
                        >
                            {{ __('Details') }}
                        </router-link>
                    </div>
                    <div class="flex items-center space-x-2">
                        <span class="text-xs text-gray-500">
                            {{ __(project.status ? "Active" : "Inactive") }}
                        </span>
                        <ui-switch
                            :model-value="Boolean(project.status)"
                            :label="__(project.status ? 'Active' : 'Inactive')"
                            @change="(value) => toggleStatus(project, value)"
                        />
                    </div>
                </div>
            </div>
        </div>

        <ui-modal :show="openNewProjectModal" @close="closeNewProjectModal">
            <template #title> {{ __('Create New Project') }} </template>

            <template #content>
                <div class="mt-4 pb-4">
                    <form @submit.prevent="handleNewProjectSubmit">
                        <div class="mt-2">
                            <label v-formlabel>{{ __('Project Name') }}</label>
                            <input
                                type="text"
                                v-model="new_project.name"
                                autofocus
                                v-forminput
                                :placeholder="__('Project name')"
                                @input="generateSlugFromName"
                            />
                            <p class="text-sm text-red-600 mt-2">
                                {{ new_project.errors.name[0] }}
                            </p>
                        </div>
                        <div class="mt-6">
                            <label v-formlabel>{{ __('Project Slug') }}</label>
                            <input
                                type="text"
                                v-model="new_project.slug"
                                v-forminput
                                placeholder="project-slug"
                                pattern="[a-z0-9\-]+"
                                @blur="checkSlug"
                                @input="onSlugInput"
                            />
                            <p class="text-sm text-gray-500 mt-1">
                                {{ __('Only lowercase letters, numbers, and hyphens allowed') }}
                            </p>
                            <p v-if="new_project.slugExists" class="text-sm text-red-600 mt-2">
                                {{ __('This slug is already in use by another project.') }}
                            </p>
                            <p v-else class="text-sm text-red-600 mt-2">
                                {{ new_project.errors.slug[0] }}
                            </p>
                        </div>
                        <div class="mt-6">
                            <label v-formlabel>{{ __('Description') }}</label>
                            <input
                                type="text"
                                v-model="new_project.description"
                                v-forminput
                                :placeholder="__('Project description')"
                            />
                        </div>
                        <div class="mt-6">
                            <label v-formlabel>{{ __('Default Locale') }}</label>
                            <v-select
                                :options="locales"
                                :get-option-key="localeKey"
                                :get-option-label="localeLabel"
                                :reduce="(o) => o.id"
                                :clearable="false"
                                class="v-select"
                                :placeholder="__('Select Locale')"
                                v-model="new_project.default_locale"
                            ></v-select>
                        </div>
                        <div class="mt-6">
                            <div class="grid grid-cols-2 gap-4">
                                <div class="col-span-1">
                                    <div class="p-5 border border-gray-300 rounded-md text-sm space-x-2 h-32 relative">
                                        <label for="blank_project" class="absolute inset-0 w-full h-full cursor-pointer"></label>
                                        <div class="flex">
                                            <input
                                                type="radio"
                                                id="blank_project"
                                                v-model="new_project.type"
                                                value="1"
                                            />
                                            <div class="ml-2">{{ __('Blank') }}</div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-span-1">
                                    <div class="p-5 border border-gray-300 rounded-md text-sm space-x-2 h-32 relative">
                                        <label for="cms_template" class="absolute inset-0 w-full h-full cursor-pointer"></label>
                                        <div class="flex mb-2">
                                            <input
                                                type="radio"
                                                id="cms_template"
                                                v-model="new_project.type"
                                                value="2"
                                            />
                                            <div class="ml-2">
                                                {{ __('CMS Template') }}
                                            </div>
                                        </div>
                                        <div class="block">
                                            {{ __('Content Management System (Pages, Articles, Categories, Authors, Tags, Comments, Globals)') }}
                                        </div>
                                    </div>
                                </div>
                                <div class="col-span-1">
                                    <div class="p-5 border border-gray-300 rounded-md text-sm space-x-2 h-32 relative">
                                        <label for="directory_template" class="absolute inset-0 w-full h-full cursor-pointer"></label>
                                        <div class="flex mb-2">
                                            <input
                                                type="radio"
                                                id="directory_template"
                                                v-model="new_project.type"
                                                value="3"
                                            />
                                            <div class="ml-2">
                                                {{ __('Business Directory Template') }}
                                            </div>
                                        </div>
                                        <div class="block">
                                            {{ __('Business Directory (Listings, Categories, Tags, Locations, Reviews, Globals)') }}
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </template>

            <template #footer>
                <ui-button
                    color="gray-200"
                    hover="gray-300"
                    @click="closeNewProjectModal"
                >
                    <span class="text-gray-800">{{ __('Cancel') }}</span>
                </ui-button>

                <ui-button
                    color="indigo-500"
                    @click="handleNewProjectSubmit"
                    :class="{ 'opacity-25': processing }"
                    :disabled="processing"
                >
                    {{ __('Create New Project') }}
                </ui-button>
            </template>
        </ui-modal>
    </div>
</template>

<script>
import UiModal from "../../components/Modal.vue";
import UiButton from "../../components/Button.vue";
import UiSwitch from "../../components/UiSwitch.vue";

import checkRole from "../../utils/checkrole";
import localesJson from "../../locales.json";
import { useAdminStore } from '../store';

export default {
    components: {
        UiModal,
        UiButton,
        UiSwitch,
    },

    data() {
        return {
            openNewProjectModal: false,
            new_project: {
                default_locale: "en",
                type: 1,
                errors: {
                    name: [],
                    slug: [],
                },
                slugExists: false,
                slugManuallyEdited: false,
            },
            projects: {},
            processing: false,
            search: "",
            locales: [],
        };
    },

    computed: {
        isSuperAdmin() {
            return checkRole(['super_admin']);
        },
    },

    methods: {
        checkRole,

        generateSlugFromName() {
            // Regenerate the slug on every keystroke, but never clobber a
            // slug the user edited by hand (once the slug field is touched,
            // auto-fill stops until the modal is reopened).
            if (this.new_project.slugManuallyEdited) return;

            // $slugify converts Chinese input to pinyin automatically.
            this.new_project.slug = this.$slugify(this.new_project.name || '');
        },

        onSlugInput() {
            this.new_project.slugManuallyEdited = true;
            this.clearSlugError();
        },

        // vue-select sometimes renders the raw (reduced) value instead of the
        // matched option object — e.g. when the model value is set before the
        // options arrive. Those helpers tolerate both shapes so the field
        // never displays "undefined": strings are looked up in the option
        // list first and fall back to the raw code.
        localeKey(option) {
            return typeof option === 'string' ? option : option.id;
        },

        localeLabel(option) {
            if (typeof option === 'string') {
                const found = this.locales.find((l) => l.id === option);
                return found ? found.id + ' - ' + found.name : option;
            }
            return option.id + ' - ' + option.name;
        },

        checkSlug() {
            let slug = this.new_project.slug;
            if (!slug || slug === '') return;

            axios.get("projects/check-slug/" + slug)
                .then((response) => {
                    if (!response.data.available) {
                        this.new_project.slugExists = true;
                    } else {
                        this.new_project.slugExists = false;
                    }
                });
        },

        clearSlugError() {
            this.new_project.slugExists = false;
        },

        handleNewProjectSubmit() {
            this.processing = true;

            axios.post("projects", this.new_project).then(
                (response) => {
                    this.$toast.success(this.__("New project created."));
                    this.closeNewProjectModal();
                    this.projects.unshift(response.data);
                },
                (error) => {
                    if (error.response && error.response.status == 422) {
                        this.new_project.errors = error.response.data.errors;
                        this.processing = false;
                    }
                }
            );
        },

        closeNewProjectModal() {
            this.openNewProjectModal = false;
            this.new_project = {
                default_locale: "en",
                type: 1,
                errors: {
                    name: [],
                    slug: [],
                },
                slugExists: false,
                slugManuallyEdited: false,
            };
            this.processing = false;
        },

        getProjects() {
            axios
                .get("projects", { params: { search: this.search } })
                .then((response) => {
                    this.projects = response.data;
                });
        },

        toggleStatus(project, value) {
            const applyToggle = () => {
                axios
                    .post(`projects/toggle-status/${project.id}`)
                    .then((response) => {
                        const updated = response.data;
                        const index = this.projects.findIndex(
                            (p) => p.id === updated.id
                        );
                        if (index !== -1) this.projects[index] = updated;
                        this.$toast.success(
                            updated.status
                                ? this.__("Project enabled.")
                                : this.__("Project disabled.")
                        );
                    })
                    .catch((error) => {
                        const message =
                            error.response?.data?.message ||
                            this.__("Failed to update project status.");
                        this.$toast.error(message);
                        this.getProjects();
                    });
            };

            if (value === false) {
                this.$swal
                    .fire({
                        title: this.__("Disable this project?"),
                        text: this.__("External API calls will return 404. Admin editing remains available."),
                        icon: "warning",
                        showCancelButton: true,
                        confirmButtonText: this.__("Disable"),
                        cancelButtonText: this.__("Cancel"),
                    })
                    .then((result) => {
                        if (result.isConfirmed) {
                            applyToggle();
                        }
                    });
                return;
            }

            applyToggle();
        },
    },

    created() {
        useAdminStore().setTopbarContent({ 
            page: 'projects',
            type: 'projectList', 
            title: this.__('Project List'),
            breadcrumb: [
                { name: this.__('Dashboard'), url: '/', icon: 'fa fa-tachometer-alt' },
                { name: this.__('Project List'), icon: 'fas fa-list' },
            ],
        });
    },

    mounted() {
        this.getProjects();

        Object.entries(localesJson).forEach((item, key) => {
            this.locales.push({ id: item[0], name: item[1] });
        });
    },
};
</script>
