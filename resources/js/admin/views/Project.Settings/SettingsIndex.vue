<template>
    <div class="admin__project-settings-index relative h-full flex flex-col">
        <project-header :project="project"></project-header>

        <div class="flex flex-1 overflow-y-auto">
            <div class="w-3/12 bg-white overflow-x-hidden">
                <settings-sidebar :project="project"></settings-sidebar>
            </div>
            <div class="w-9/12 overflow-x-hidden">
                <div class="p-4">
                    <h4 class="mb-2 p-2 font-bold text-xl">{{ __('Project Details') }}</h4>

                    <div class="w-full bg-white mt-2 rounded-md p-4">
                        <div class="grid grid-cols-1 gap-4">
                            <div>
                                <label v-formlabel>{{ __('Project Name') }}</label>
                                <div class="mt-1 relative">
                                    <input
                                        type="text"
                                        v-model="editProjectData.name"
                                        autofocus
                                        v-forminput
                                        :placeholder="__('Project Name')"
                                        @input="generateSlugFromName"
                                    />
                                    <p class="text-sm text-red-600 mt-2">
                                        {{ editProjectData.errors.name[0] }}
                                    </p>
                                </div>
                            </div>

                            <div>
                                <label v-formlabel>{{ __('Project Slug') }}</label>
                                <div class="mt-1 relative">
                                    <input
                                        type="text"
                                        v-model="editProjectData.slug"
                                        v-forminput
                                        placeholder="project-slug"
                                        pattern="[a-z0-9\-]+"
                                        @blur="checkSlug"
                                        @input="onSlugInput"
                                    />
                                    <p class="text-sm text-gray-500 mt-1">
                                        {{ __('Only lowercase letters, numbers, and hyphens allowed') }}
                                    </p>
                                    <p v-if="editProjectData.slugExists" class="text-sm text-red-600 mt-2">
                                        {{ __('This slug is already in use by another project.') }}
                                    </p>
                                    <p v-else class="text-sm text-red-600 mt-2">
                                        {{ editProjectData.errors.slug[0] }}
                                    </p>
                                </div>
                            </div>

                            <div>
                                <label v-formlabel>{{ __('Description') }}</label>
                                <div class="mt-1 relative">
                                    <input
                                        type="text"
                                        v-model="editProjectData.description"
                                        v-forminput
                                        :placeholder="__('Description')"
                                    />
                                </div>
                            </div>

                            <div v-if="project.s3">
                                <label v-formlabel>{{ __('Default Upload Disk') }}</label>
                                <div class="grid grid-cols-4 space-x-2">
                                    <div class="col-span-1">
                                        <label
                                            for="default_disk_local"
                                            class="p-5 border border-gray-300 rounded-md text-sm flex items-center space-x-2 cursor-pointer"
                                        >
                                            <input
                                                name="default_disk"
                                                id="default_disk_local"
                                                type="radio"
                                                v-model="editProjectData.disk"
                                                value="local"
                                            />
                                            <span>{{ __('Local (storage folder)') }}</span>
                                        </label>
                                    </div>
                                    <div class="col-span-1">
                                        <label
                                            for="default_disk_s3"
                                            class="p-5 border border-gray-300 rounded-md text-sm flex items-center space-x-2 cursor-pointer"
                                        >
                                            <input
                                                name="default_disk"
                                                id="default_disk_s3"
                                                type="radio"
                                                v-model="editProjectData.disk"
                                                value="s3"
                                            />
                                            <span>{{ __('AWS S3') }}</span>
                                        </label>
                                    </div>
                                </div>
                            </div>
                            <div>
                                <label v-formlabel></label>
                                <div class="mt-1 relative">
                                    <ui-button
                                        :color="'indigo-500'"
                                        @click="saveEdit()"
                                    >
                                        {{ __('Update Project') }}
                                    </ui-button>
                                </div>
                            </div>
                        </div>

                        <hr class="clear-both mt-5 mb-5" />

                        <ui-button
                            v-if="checkRole(['super_admin'])"
                            :color="'red-500'"
                            class="float-right"
                            @click="deleteProject()"
                        >
                            <i class="fa fa-exclamation-triangle"></i> {{ __('Delete Project') }}
                        </ui-button>

                        <div class="clear-both"></div>
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

import checkRole from "../../../utils/checkrole";
import projectBreadcrumb from "../../mixins/projectBreadcrumb";
import { useAdminStore } from "../../store";

export default {
    components: {
        ProjectHeader,
        SettingsSidebar,
        UiButton,
    },

    mixins: [projectBreadcrumb],

    computed: {
    },

    data() {
        // Pre-seed from the store (loaded by the router guard before this
        // page renders) so the shell and the edit form never flash blank
        // while the page's own getProject() refreshes the data.
        const currentProject = useAdminStore().currentProject || {};

        return {
            project: currentProject,
            editProjectData: {
                id: currentProject.id,
                name: currentProject.name,
                slug: currentProject.slug,
                description: currentProject.description,
                disk: currentProject.disk,
                errors: {
                    name: [],
                    slug: [],
                    description: [],
                },
                slugExists: false,
                slugManuallyEdited: false,
            },
        };
    },

    methods: {
        checkRole,

        generateSlugFromName() {
            // Regenerate the slug on every keystroke, but never clobber a
            // slug the user edited by hand (once the slug field is touched,
            // auto-fill stops).
            if (this.editProjectData.slugManuallyEdited) return;

            // $slugify converts Chinese input to pinyin automatically.
            this.editProjectData.slug = this.$slugify(this.editProjectData.name || '');
        },

        onSlugInput() {
            this.editProjectData.slugManuallyEdited = true;
            this.clearSlugError();
        },

        checkSlug() {
            let slug = this.editProjectData.slug;
            if (!slug || slug === '') return;

            let url = "projects/check-slug/" + slug;
            if (this.project.id) {
                url += "?exclude_id=" + this.project.id;
            }

            axios.get(url)
                .then((response) => {
                    if (!response.data.available) {
                        this.editProjectData.slugExists = true;
                    } else {
                        this.editProjectData.slugExists = false;
                    }
                });
        },

        clearSlugError() {
            this.editProjectData.slugExists = false;
        },

        saveEdit() {
            axios
                .post(
                    "projects/update/" + this.project.id,
                    this.editProjectData
                )
                .then(
                    (response) => {
                        this.$toast.success(__('Project updated!'));
                        this.editProjectData.errors = {
                            name: [],
                            slug: [],
                            description: [],
                        };
                        this.editProjectData.slugExists = false;
                        // Apply the server response directly (no extra
                        // request) and keep the store — and the main sidebar
                        // project name — in sync with the saved values.
                        const saved = response.data;
                        this.project = saved;
                        this.editProjectData.id = saved.id;
                        this.editProjectData.name = saved.name;
                        this.editProjectData.slug = saved.slug;
                        this.editProjectData.description = saved.description;
                        this.editProjectData.disk = saved.disk;
                        this.editProjectData.slugManuallyEdited = false;
                        useAdminStore().setCurrentProject(saved.id);
                    },
                    (error) => {
                        if (error.response && error.response.status == 422) {
                            this.editProjectData.errors = error.response.data.errors;
                        }
                    }
                );
        },

        deleteProject() {
            this.$swal
                .fire({
                    title: __('Are you sure'),
                    text: __('you want to delete this project? All the collections and the content will be lost. You won\'t be able to revert this!'),
                })
                .then((result) => {
                    if (result.isConfirmed) {
                        axios
                            .delete("projects/delete/" + this.project.id)
                            .then((response) => {
                                this.$toast.success(__('Project deleted.'));
                                this.$router.push({ name: "dashboard" });
                            });
                    }
                });
        },
    },
};
</script>
