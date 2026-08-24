<template>
    <div class="admin__project-content-forms relative h-full flex flex-col">
        <project-header :project="project"></project-header>

        <div class="flex flex-1 overflow-y-auto">
            <div class="w-3/12 overflow-x-hidden bg-white">
                <content-sidebar :project="project"></content-sidebar>
            </div>

            <div class="w-9/12 p-4 overflow-x-auto">
                <div class="mb-2 py-2 font-bold text-lg flex justify-end">
                    <div class="flex-1">
                        {{ collection.name }}
                        <small class="text-gray-500 font-normal"> {{ __('/ Forms') }}</small>
                    </div>
                </div>

                <div class="space-y-10">
                    <div
                        class="grid grid-cols-1 gap-4 sm:grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4"
                    >
                        <div
                            @click="openNewFormModal = true"
                            class="bg-green-500 hover:bg-green-600 text-white cursor-pointer items-center flex justify-center py-2 rounded-md"
                        >
                            <i class="fas fa-plus-circle md:mr-4 text-sm md:text-xl"></i>
                            {{ __('Create a New Form') }}
                        </div>

                        <router-link
                            :to="{
                                name: 'projects.content.forms.detail',
                                params: {
                                    project_id: $route.params.project_id,
                                    col_id: $route.params.col_id,
                                    form_id: form.id,
                                },
                            }"
                            v-for="form in forms"
                            :key="form.id"
                            class="bg-white hover:bg-gray-100 px-10 py-6 text-gray-900 border border-gray-100 cursor-pointer items-center rounded-md relative"
                        >
                            <span class="font-bold mt-3 block text-lg">{{form.name}}</span>
                            <span class="text-sm block">{{form.description}}</span>
                        </router-link>
                    </div>
                </div>
            </div>
        </div>

        <ui-modal :show="openNewFormModal" @close="closeNewFormModal">
            <template #title> {{ __('Create a New Form') }} </template>

            <template #content>
                <div class="mt-4 pb-4">
                    <div class="mt-2">
                        <label v-formlabel>{{ __('Form Name') }}</label>
                        <input
                            type="text"
                            v-model="new_form.name"
                            autofocus
                            v-forminput
                            :placeholder="__('Form Name')"
                        />
                        <p class="text-sm text-red-600 mt-2">
                            {{ new_form.errors.name[0] }}
                        </p>
                    </div>
                    <div class="mt-6">
                        <label v-formlabel>{{ __('Description') }}</label>
                        <input
                            type="text"
                            v-model="new_form.description"
                            v-forminput
                            :placeholder="__('Description')"
                        />
                    </div>
                </div>
            </template>

            <template #footer>
                <ui-button
                    color="gray-200"
                    hover="gray-300"
                    @click="closeNewFormModal"
                >
                    <span class="text-gray-800">{{ __('Cancel') }}</span>
                </ui-button>

                <ui-button
                    color="indigo-500"
                    @click="saveNew"
                    :disabled="processing_new_form"
                    class="w-40"
                >
                    <i
                        v-if="processing_new_form"
                        class="fas fa-spinner fa-spin"
                    ></i>
                    {{ __('Create New Form') }}
                </ui-button>
            </template>
        </ui-modal>
    </div>
</template>

<script>
import { __ } from '../../translations/engine';

import UiButton from "../../../components/Button.vue";
import UiModal from "../../../components/Modal.vue";
import UiDropdown from "../../../components/Dropdown.vue";

import ProjectHeader from "../components/ProjectHeader.vue";
import ContentSidebar from "./sections/ContentSidebar.vue";
import ContentFormsSidebar from "./sections/ContentFormsSidebar.vue";
import projectBreadcrumb from "../../mixins/projectBreadcrumb";
import { useAdminStore } from "../../store";

export default {
    components: {
        ProjectHeader,
        ContentSidebar,
        UiButton,
        UiModal,
        UiDropdown,
        ContentFormsSidebar,
    },

    mixins: [projectBreadcrumb],

    computed: {
    },

    data() {
        return {
            // Pre-seed from the store (loaded by the router guard before
            // this page renders) so the shell never flashes blank while the
            // page's own getForms() refreshes the data.
            project: useAdminStore().currentProject || {},
            collection: useAdminStore().currentCollection || {},
            forms: {},
            openNewFormModal: false,
            new_form: {
                errors: {
                    name: "",
                },
            },
            processing_new_form: false,
        };
    },

    methods: {
        getForms() {
            axios
                .get(
                    "content/forms/" + this.$route.params.project_id + "/" + this.$route.params.col_id
                )
                .then((response) => {
                    this.project = response.data.project;
                    this.collection = response.data.collection;
                    this.forms = response.data.forms;
                    this.new_form.project_id = response.data.project.id;
                    this.new_form.collection_id = response.data.collection.id;

                    this.collection.fields.forEach((element) => {
                        element.options = JSON.parse(element.options);
                    });
                });
        },

        closeNewFormModal() {
            this.openNewFormModal = false;
            this.new_form = {
                errors: {
                    name: "",
                },
            };
            this.processing_new_form = false;
        },

        saveNew() {
            axios
                .post(
                    "content/forms/" + this.$route.params.project_id + "/" + this.$route.params.col_id,
                    this.new_form
                )
                .then(
                    (response) => {
                        this.closeNewFormModal();
                        this.$toast.success(__('New form created.'));
                        this.$router.push({
                            name: "projects.content.forms.detail",
                            params: {
                                project_id: this.project.id,
                                col_id: this.collection.id,
                                form_id: response.data.id,
                            },
                        });
                    },
                    (error) => {
                        if (error.response && error.response.status == 422) {
                            this.new_form.errors = error.response.data.errors;
                        }
                    }
                );
        },
    },

    mounted() {
        this.getForms();
    },

    watch: {
        "$route.params.col_id"(newId, oldId) {
            this.getForms();
        },
    },
};
</script>
