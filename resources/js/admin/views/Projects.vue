<template>
    <div class="admin__projects-list h-full flex flex-col m-3 p-3">
        <div class="admin__projects-search flex justify-between pb-4">
            <div class="search-input relative flex flex-wrap items-stretch">
                <span class="h-full leading-snug font-normal absolute text-center text-gray-400 absolute bg-transparent rounded-md text-base items-center justify-center w-8 pl-3 py-3">
                    <i class="fas fa-search"></i>
                </span>
                <input
                    type="text"
                    v-model="search"
                    @keyup="getProjects()"
                    placeholder="Search projects..."
                    class="px-3 py-3 placeholder-gray-400 text-gray-700 bg-white rounded-md text-sm w-full pl-10 border-gray-200 focus:border-gray-300"
                />
            </div>
            <div
                v-if="checkRole(['super_admin'])"
                @click="openNewProjectModal = true"
                class="flex justify-center items-center bg-green-500 hover:bg-green-600 text-white mx-2 my-1 px-2 py-1 rounded-md cursor-pointer shadow-sm"
            >
                <i class="fas fa-plus-circle text-sm md:text-2xl"></i>
            </div>
        </div>
        <div class="admin__projects-list grid grid-cols-1 gap-4 sm:grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 overflow-y-auto overflow-y-auto">
            <div
                v-for="project in projects"
                :key="project.id"
                class="bg-white text-gray-900 cursor-pointer items-center rounded-md shadow-sm hover:shadow-dm"
            >
                <router-link
                    :to="{
                        name: 'projects.index',
                        params: { project_id: project.id },
                    }"
                    class="block p-6"
                >
                    <span class="font-bold">{{ project.name }}</span>
                    <span class="text-sm block">{{ project.description }}</span>
                </router-link>
            </div>
        </div>

        <ui-modal :show="openNewProjectModal" @close="closeNewProjectModal">
            <template #title> Create New Project </template>

            <template #content>
                <div class="mt-4 pb-4">
                    <form @submit.prevent="handleNewProjectSubmit">
                        <div class="mt-2">
                            <label v-formlabel>Project Name</label>
                            <input
                                type="text"
                                v-model="new_project.name"
                                autofocus
                                v-forminput
                                placeholder="Project name"
                                @input="generateSlugFromName"
                            />
                            <p class="text-sm text-red-600 mt-2">
                                {{ new_project.errors.name[0] }}
                            </p>
                        </div>
                        <div class="mt-6">
                            <label v-formlabel>Project Slug</label>
                            <input
                                type="text"
                                v-model="new_project.slug"
                                v-forminput
                                placeholder="project-slug"
                                pattern="[a-z0-9\-]+"
                                @blur="checkSlug"
                                @input="clearSlugError"
                            />
                            <p class="text-sm text-gray-500 mt-1">
                                Only lowercase letters, numbers, and hyphens allowed
                            </p>
                            <p v-if="new_project.slugExists" class="text-sm text-red-600 mt-2">
                                This slug is already in use by another project.
                            </p>
                            <p v-else class="text-sm text-red-600 mt-2">
                                {{ new_project.errors.slug[0] }}
                            </p>
                        </div>
                        <div class="mt-6">
                            <label v-formlabel>Description</label>
                            <input
                                type="text"
                                v-model="new_project.description"
                                v-forminput
                                placeholder="Project description"
                            />
                        </div>
                        <div class="mt-6">
                            <label v-formlabel>Default Locale</label>
                            <v-select
                                :options="locales"
                                :get-option-key="(o) => o.id"
                                :get-option-label="(o) => o.id + ' - ' + o.name"
                                :reduce="(o) => o.id"
                                :clearable="false"
                                class="v-select"
                                :value="(option) => option[0]"
                                placeholder="Select Locale"
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
                                            <div class="ml-2">Blank</div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-span-1">
                                    <div class="p-5 border border-gray-300 rounded-md text-sm space-x-2 h-32 relative">
                                        <label for="blog_template" class="absolute inset-0 w-full h-full cursor-pointer"></label>
                                        <div class="flex mb-2">
                                            <input
                                                type="radio"
                                                id="blog_template"
                                                v-model="new_project.type"
                                                value="2"
                                            />
                                            <div class="ml-2">
                                                Blog Template
                                            </div>
                                        </div>
                                        <div class="block">
                                            (Pages, Posts, Categories, Authors, Tags, Comments, Globals)
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
                    @click.native="closeNewProjectModal"
                >
                    <span class="text-gray-800">Cancel</span>
                </ui-button>

                <ui-button
                    color="indigo-500"
                    @click.native="handleNewProjectSubmit"
                    :class="{ 'opacity-25': processing }"
                    :disabled="processing"
                >
                    Create New Project
                </ui-button>
            </template>
        </ui-modal>
    </div>
</template>

<script>
import UiModal from "../../components/Modal.vue";
import UiButton from "../../components/Button.vue";

import checkRole from "../../utils/checkrole";
import localesJson from "../../locales.json";

export default {
    components: {
        UiModal,
        UiButton,
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
            },
            projects: {},
            processing: false,
            search: "",
            locales: [],
        };
    },

    methods: {
        checkRole,

        generateSlugFromName() {
            if (!this.new_project.slug || this.new_project.slug === '') {
                let name = this.new_project.name || '';
                this.new_project.slug = name.toLowerCase()
                    .replace(/[^a-z0-9]+/g, '-')
                    .replace(/^-+|-+$/g, '');
            }
        },

        checkSlug() {
            let slug = this.new_project.slug;
            if (!slug || slug === '') return;

            axios.get("/admin/projects/check-slug/" + slug)
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

            axios.post("/admin/projects", this.new_project).then(
                (response) => {
                    this.$toast.success("New project created.");
                    this.closeNewProjectModal();
                    this.projects.unshift(response.data);
                },
                (error) => {
                    if (error.response.status == 422) {
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
            };
            this.processing = false;
        },

        getProjects() {
            axios
                .get("/admin/projects", { params: { search: this.search } })
                .then((response) => {
                    this.projects = response.data;
                });
        },
    },

    created() {
        this.$store.commit('SET_TOPBAR_CONTENT', { 
            page: 'projects',
            type: 'projectList', 
            title: 'Project List',
            breadcrumb: [
                { name: 'Dashboard', url: '/', icon: 'fa fa-tachometer-alt' },
                { name: 'Project List', icon: 'fas fa-list' },
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
