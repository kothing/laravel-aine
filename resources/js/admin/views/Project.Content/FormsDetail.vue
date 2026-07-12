<template>
    <div class="admin__project-content-forms-detail relative h-full flex flex-col">
        <project-header :project="project"></project-header>

        <div class="flex flex-1 overflow-y-auto">
            <div class="w-3/12 overflow-x-hidden bg-white">
                <content-sidebar :project="project"></content-sidebar>
            </div>

            <div class="w-9/12 p-4 overflow-x-auto">
                <div class="mb-2 py-2 font-bold text-lg flex justify-end">
                    <div class="flex-1">
                        {{ collection.name }}
                        <small class="text-gray-500 font-normal"> / Forms</small>
                    </div>
                    <div>
                        <ui-button :color="'green-500'" class="rounded-r-none" @click.native="openNewFormModal = true"> + Create a New Form </ui-button>
                    </div>
                </div>

                <div class="grid grid-cols-6 space-x-4 h-full">
                    <div class="col-span-4 p-5 bg-white mt-2 rounded-md overflow-x-hidden">
                        <div class="flex justify-end">
                            <div class="flex-1 border p-4">
                                <input type="text" v-model="form.name" placeholder="Form Name" class="text-lg border-0 mb-2 pl-0" v-forminput />
                                <input type="text" v-model="form.description" placeholder="Description" class="border-0 pl-0" v-forminput />

                                <form class="mt-5 w-full">
                                    <draggable :list="form.fields" v-bind="dragOptions" handle=".handle">
                                        <transition-group type="transition" class="space-y-6">
                                            <div class="bg-gray-50 p-2 w-full relative handle" v-for="field in form.fields" :key="field.id">
                                                <label v-formlabel>
                                                    <input type="text" class="p-0 m-0 bg-transparent border-0 w-1/2" v-model="field.label" />
                                                </label>
                                                <div class="mt-1 relative">
                                                    <div v-if="field.type == 'text'">
                                                        <input type="text" :placeholder="field.placeholder" v-forminput disabled />
                                                    </div>
                                                    <div v-if="field.type == 'longtext'">
                                                        <textarea :placeholder="field.placeholder" v-forminput disabled></textarea>
                                                    </div>
                                                    <div v-if="field.type == 'richtext'" class="w-full relative">
                                                        <tiny-editor
                                                            :value="field.value"
                                                            :placeholder="field.placeholder"
                                                            height="120px"
                                                            :disabled="true"
                                                        />
                                                    </div>
                                                    <div v-if="field.type == 'email'">
                                                        <div class="mt-1 flex rounded-md shadow-sm">
                                                            <span class="inline-flex items-center px-3 rounded-l-sm border border-r-0 border-gray-300 bg-gray-50 text-gray-500 text-sm">
                                                                <i class="fa fa-at"></i>
                                                            </span>
                                                            <input type="email" :placeholder="field.placeholder" v-forminput class="rounded-l-none" disabled />
                                                        </div>
                                                    </div>
                                                    <div v-if="field.type == 'password'">
                                                        <div class="mt-1 flex rounded-md shadow-sm">
                                                            <span class="inline-flex items-center px-3 rounded-l-sm border border-r-0 border-gray-300 bg-gray-50 text-gray-500 text-sm">
                                                                <i class="fa fa-lock"></i>
                                                            </span>
                                                            <input type="password" v-forminput class="rounded-l-none" disabled />
                                                        </div>
                                                    </div>
                                                    <div v-if="field.type == 'number'">
                                                        <input type="number" step="any" v-forminput disabled />
                                                    </div>
                                                    <div class="w-full" v-if="field.type == 'enumeration'">
                                                        <v-select :options="field.options.enumeration" class="v-select bg-white" placeholder="Select" disabled></v-select>
                                                    </div>
                                                    <div v-if="field.type == 'boolean'">
                                                        <label for="toggleB" class="flex w-1 items-center cursor-pointer">
                                                            <div class="relative">
                                                                <input type="checkbox" id="toggleB" class="sr-only" disabled />
                                                                <div class="block bg-gray-600 w-14 h-8 rounded-full"></div>
                                                                <div class="dot absolute left-1 top-1 bg-white w-6 h-6 rounded-full transition"></div>
                                                            </div>
                                                        </label>
                                                    </div>
                                                    <div v-if="field.type == 'color'" class="w-full xl:w-1/4">
                                                        <colorpicker disabled />
                                                    </div>
                                                    <div v-if="field.type == 'date'" class="w-full xl:w-1/3">
                                                        <v-date-picker v-model="field.value" :mode="field.options.timepicker ? 'dateTime' : 'date'">
                                                            <template v-slot="{ inputValue }">
                                                                <div class="mt-1 flex rounded-md shadow-sm">
                                                                    <span
                                                                        class="inline-flex items-center px-3 rounded-l-sm border border-r-0 border-gray-300 bg-gray-50 text-gray-500 text-sm cursor-pointer"
                                                                    >
                                                                        <i class="fa fa-calendar-alt"></i>
                                                                    </span>
                                                                    <input type="text" v-forminput :value="inputValue" disabled />
                                                                </div>
                                                            </template>
                                                        </v-date-picker>
                                                    </div>
                                                    <div v-if="field.type == 'time'" class="w-1/6">
                                                        <input type="time" v-forminput disabled />
                                                    </div>
                                                    <div v-if="field.type == 'media'" class="w-full">
                                                        <input type="file" v-forminput disabled />
                                                    </div>
                                                    <div class="clear-both"></div>

                                                    <small class="text-gray-500">
                                                        <input type="text" class="p-0 m-0 bg-transparent border-0 w-full text-sm" v-model="field.description" placeholder="Description (optional)" />
                                                    </small>
                                                </div>
                                                <i class="fas fa-arrows-alt mr-4 text-gray-500 cursor-pointer absolute top-0 right-0 p-2 mr-8 handle"></i>
                                                <i class="fa fa-trash-alt text-red-500 cursor-pointer absolute top-0 right-0 p-2" @click="removeField(field)"></i>
                                            </div>
                                        </transition-group>
                                    </draggable>
                                </form>

                                <div class="w-full text-center p-4 mt-5 border border-dashed cursor-pointer text-gray-400 hover:text-gray-500" @click="openAddFieldsModal = true">
                                    + Add a field to this form
                                </div>

                                <ui-button color="indigo-500" class="mt-5">
                                    <input type="text" v-model="form.submit_btn_text" class="border-0 bg-transparent p-0 m-0 text-center focus:bg-indigo-400" />
                                </ui-button>
                            </div>
                            <div class="pl-4 w-52">
                                <ui-button :color="'indigo-500'" class="w-full text-center" :disabled="!isSavingEnable" @click.native="saveForm">
                                    <i class="fas fa-save"></i>
                                    Save Changes
                                </ui-button>
                                <a
                                    :href="'/forms/preview/' + form.uuid"
                                    target="_blank"
                                    class="p-3 block items-center border border-transparent rounded-md text-sm text-white focus:outline-none transition ease-in-out duration-150 bg-green-500 w-full text-center mt-2"
                                >
                                    <i class="fas fa-code"></i>
                                    Preview & Embed
                                    <i class="fas fa-external-link-alt text-xs ml-2"></i>
                                </a>
                                <hr class="m-5 mt-7" />
                                <ui-button :color="'red-500'" class="w-full text-center mt-2" @click.native="deleteForm">
                                    <i class="fas fa-trash-alt"></i>
                                    Delete Form
                                </ui-button>
                            </div>
                        </div>

                        <div class="clear-both h-32"></div>
                    </div>

                    <div class="col-span-2 mt-2 ml-2">
                        <content-forms-sidebar :forms="forms"></content-forms-sidebar>

                        <div class="bg-white mb-2 rounded-md">
                            <router-link
                                :to="{
                                    name: 'projects.content.list',
                                    params: {
                                        project_id: $route.params.project_id,
                                        col_id: $route.params.col_id,
                                    },
                                }"
                                class="bg-gray-200 text-gray-700 hover:bg-gray-300 hover:text-gray-800 items-center px-4 py-2 rounded-md text-sm text-center focus:outline-none transition ease-in-out duration-150 block"
                            >
                                <i class="fa fa-angle-double-left"> </i> Back to content list
                            </router-link>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <ui-modal :show="openNewFormModal" @close="closeNewFormModal">
            <template #title> Create a New Form </template>

            <template #content>
                <div class="mt-4 pb-4">
                    <div class="mt-2">
                        <label v-formlabel>Form Name</label>
                        <input type="text" v-model="new_form.name" autofocus v-forminput placeholder="Form Name" />
                        <p class="text-sm text-red-600 mt-2">
                            {{ new_form.errors.name[0] }}
                        </p>
                    </div>
                    <div class="mt-6">
                        <label v-formlabel>Description</label>
                        <input type="text" v-model="new_form.description" v-forminput placeholder="Description" />
                    </div>
                </div>
            </template>

            <template #footer>
                <ui-button color="gray-200" hover="gray-300" @click.native="closeNewFormModal">
                    <span class="text-gray-800">Cancel</span>
                </ui-button>

                <ui-button color="indigo-500" @click.native="saveNew" :disabled="processing_new_form" class="w-48">
                    <i v-if="processing_new_form" class="fas fa-spinner fa-spin"></i>
                    Create New Form
                </ui-button>
            </template>
        </ui-modal>

        <ui-modal :show="openAddFieldsModal" @close="closeAddFieldsModal" v-if="openAddFieldsModal">
            <template #title>
                Add Fields to <strong>{{ form.name }}</strong>
            </template>

            <template #content>
                <ul>
                    <li class="w-full mb-2" v-for="field in collection.fields" :key="field.id">
                        <div
                            v-if="field.type !== 'slug' && field.type !== 'relation' && field.type !== 'json' && form.fields.find((element) => element.id == field.id) == undefined"
                            class="flex items-center w-full bg-gray-50 rounded-md p-4"
                        >
                            <div :class="fieldDetails[field.type].bg" class="mr-4 text-gray-100 rounded-md text-xl items-center text-center flex field_icon_xl">
                                <i :class="fieldDetails[field.type].icon" class="w-full"></i>
                            </div>
                            <div class="items-center w-full">
                                <div class="text-lg">{{ field.label }}</div>

                                <div class="w-full flex justify-between">
                                    <div class="flex space-x-1 items-center">
                                        <span class="text-blue-900 text-sm rounded-md bg-gray-200 px-3">#{{ field.name }}</span>
                                        <span class="text-blue-900 text-sm rounded-md bg-indigo-200 px-3">
                                            {{ field.type }}
                                            <span v-if="field.type == 'enumeration'">
                                                <span v-if="field.options.multiple">: multiple</span>
                                            </span>
                                            <span v-if="field.type == 'date'">
                                                <span v-if="field.options.timepicker">: time</span>
                                            </span>
                                            <span v-if="field.type == 'media'">
                                                <span v-if="field.options.media.type == 1">: single</span>
                                                <span v-else-if="field.options.media.type == 2">: multiple</span>
                                            </span>
                                            <span v-else-if="field.type == 'relation'">
                                                <span v-if="field.options.relation.type == 1">: one-to-one</span>
                                                <span v-else-if="field.options.relation.type == 2">: one-to-many</span>
                                            </span>
                                        </span>
                                        <span class="text-blue-900 text-sm rounded-md bg-gray-100 px-3" v-if="field.validations.required.status">
                                            <i class="fas fa-star-of-life text-xs"></i> required
                                        </span>
                                        <span class="text-blue-900 text-sm rounded-md bg-gray-100 px-3" v-if="field.validations.unique.status"><i class="fas fa-fingerprint text-xs"></i> unique</span>
                                        <span class="text-blue-900 text-sm rounded-md bg-gray-100 px-3" v-if="field.options.repeatable"><i class="fas fa-redo text-xs"></i> repeatable</span>
                                        <span class="text-blue-900 text-sm rounded-md bg-gray-100 px-3" v-if="field.options.hideInContentList">hide in content list</span>
                                        <span class="text-blue-900 text-sm rounded-md bg-gray-100 px-3" v-if="field.options.hiddenInAPI">hidden in api</span>
                                    </div>

                                    <div class="flex justify-between">
                                        <a @click="addField(field)" class="inline-block text-white text-sm rounded-md bg-indigo-500 px-2 cursor-pointer hover:bg-indigo-600 whitespace-nowrap">+ Add This Field</a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </li>
                </ul>
            </template>

            <template #footer></template>
        </ui-modal>

        <ui-modal :show="openEmbedModal" @close="closeEmbedModal">
            <template #title> Embed Form </template>

            <template #content>
                <textarea rows="4" class="cursor-pointer border" v-forminput @click="copyToClipboard(embedCode)" :value="embedCode"></textarea>
                <ui-button :color="'indigo-500'" @click.native="copyToClipboard(embedCode)">Copy Embed Code</ui-button>
            </template>

            <template #footer></template>
        </ui-modal>
    </div>
</template>

<script>
import Vue from "vue";
import Clipboard from "v-clipboard";
import draggable from "vuedraggable";
import { diff } from "deep-diff";

import { codemirror } from "vue-codemirror";
import "codemirror/lib/codemirror.css";
import "codemirror/mode/javascript/javascript.js";
import "codemirror/addon/edit/closebrackets.js";
import "codemirror/addon/edit/matchbrackets.js";
import "codemirror/theme/solarized.css";
import "codemirror/addon/display/autorefresh.js";

import TinyEditor from "../../../components/TinyEditor.vue";

import UiButton from "../../../components/Button.vue";
import UiModal from "../../../components/Modal.vue";

import ProjectHeader from "../components/ProjectHeader.vue";
import ContentSidebar from "./sections/ContentSidebar.vue";
import ContentFormsSidebar from "./sections/ContentFormsSidebar.vue";

Vue.use(Clipboard);

export default {
    components: {
        ProjectHeader,
        ContentSidebar,
        UiButton,
        UiModal,
        ContentFormsSidebar,
        codemirror,
        draggable,
        TinyEditor,
    },

    data() {
        return {
            project: {},
            collection: {},
            forms: {},
            openNewFormModal: false,
            new_form: {
                errors: {
                    name: "",
                },
            },
            processing_new_form: false,

            openEmbedModal: false,

            form: {},
            formClone: {},
            openAddFieldsModal: false,
            fieldDetails: {
                text: {
                    label: "Text",
                    desc: "Single line text (headings, titles)",
                    icon: "fas fa-font",
                    bg: "bg-yellow-400",
                },
                longtext: {
                    label: "Long Text",
                    desc: "Multi line text like descriptions",
                    icon: "fas fa-align-left",
                    bg: "bg-yellow-600",
                },
                richtext: {
                    label: "Rich Text",
                    desc: "Rich text editor with formatting",
                    icon: "fas fa-outdent",
                    bg: "bg-yellow-900",
                },
                slug: {
                    label: "Slug",
                    desc: "Like Urls and permalinks",
                    icon: "fas fa-link",
                    bg: "bg-green-400",
                },
                email: {
                    label: "E-mail",
                    desc: "Email field with validation",
                    icon: "fas fa-at",
                    bg: "bg-red-400",
                },
                password: {
                    label: "Password",
                    desc: "Password field with encryption",
                    icon: "fas fa-lock",
                    bg: "bg-blue-400",
                },
                number: {
                    label: "Number",
                    desc: "Integer, decimal, float numbers",
                    icon: "fas fa-sort-numeric-up",
                    bg: "bg-pink-600",
                },
                enumeration: {
                    label: "Enumeration",
                    desc: "List of values",
                    icon: "fas fa-list-ol",
                    bg: "bg-green-600",
                },
                boolean: {
                    label: "Boolean",
                    desc: "True or false",
                    icon: "fas fa-check-square",
                    bg: "bg-red-600",
                },
                color: {
                    label: "Color",
                    desc: "Color picker",
                    icon: "fas fa-tint",
                    bg: "bg-orange-400",
                },
                date: {
                    label: "Date",
                    desc: "Calendar date picker",
                    icon: "fas fa-calendar-alt",
                    bg: "bg-indigo-600",
                },
                time: {
                    label: "Time",
                    desc: "Time picker",
                    icon: "fas fa-calendar-check",
                    bg: "bg-purple-600",
                },
                media: {
                    label: "Media",
                    desc: "Files, images, videos from the library",
                    icon: "fas fa-photo-video",
                    bg: "bg-gray-500",
                },
                relation: {
                    label: "Relation",
                    desc: "Collection relations",
                    icon: "fas fa-exchange-alt",
                    bg: "bg-pink-400",
                },
                json: {
                    label: "JSON",
                    desc: "Data in JSON format",
                    icon: "fas fa-code",
                    bg: "bg-red-700",
                },
            },
        };
    },

    methods: {
        getForms() {
            axios.get("/admin/content/forms/" + this.$route.params.project_id + "/" + this.$route.params.col_id).then((response) => {
                this.project = response.data.project;
                this.collection = response.data.collection;
                this.forms = response.data.forms;
                this.new_form.project_id = response.data.project.id;
                this.new_form.collection_id = response.data.collection.id;

                this.collection.fields.forEach((element) => {
                    element.options = JSON.parse(element.options);
                    element.validations = JSON.parse(element.validations);
                });
                this.forms.forEach((form) => {
                    if (form.id == this.$route.params.form_id) {
                        this.form = form;
                        this.form.fields = this.form.fields !== null ? JSON.parse(this.form.fields) : [];

                        this.formClone = JSON.parse(JSON.stringify(this.form));
                        this.$forceUpdate();
                    }
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
            axios.post("/admin/content/forms/" + this.$route.params.project_id + "/" + this.$route.params.col_id, this.new_form).then(
                (response) => {
                    this.closeNewFormModal();
                    this.$toast.success("New form created.");
                    this.$router.push({
                        name: "projects.content.forms.detail",
                        params: {
                            project_id: this.project.id,
                            collection_id: this.collection.id,
                            form_id: response.data.id,
                        },
                    });
                },
                (error) => {
                    if (error.response.status == 422) {
                        this.new_form.errors = error.response.data.errors;
                    }
                }
            );
        },

        closeAddFieldsModal() {
            this.openAddFieldsModal = false;
        },

        addField(field) {
            this.form.fields.push(field);
            this.closeAddFieldsModal();
        },

        removeField(field) {
            this.form.fields.splice(this.form.fields.indexOf(field), 1);
            this.$forceUpdate();
        },

        saveForm() {
            axios.post("/admin/content/forms/save/" + this.$route.params.project_id + "/" + this.$route.params.col_id + "/" + this.form.id, this.form).then((response) => {
                this.$toast.success("Form updated.");
                this.formClone = JSON.parse(JSON.stringify(this.form));
            });
        },

        closeEmbedModal() {
            this.openEmbedModal = false;
        },

        copyToClipboard(str) {
            this.$clipboard(str);
            this.$toast.success("Copied to clipboard");
        },

        deleteForm() {
            this.$swal
                .fire({
                    title: "Are you sure",
                    text: "you want to delete this form permanently?",
                })
                .then((result) => {
                    if (result.value) {
                        this.formClone = JSON.parse(JSON.stringify(this.form));

                        axios.delete("/admin/content/forms/delete/" + this.$route.params.project_id + "/" + this.$route.params.col_id + "/" + this.$route.params.form_id).then((response) => {
                            this.$toast.success("Form deleted.");
                            this.$router.push({
                                name: "projects.content.forms",
                                params: {
                                    project_id: this.$route.params.project_id,
                                    col_id: this.$route.params.col_id,
                                },
                            });
                        });
                    }
                });
        },
    },

    mounted() {
        this.getForms();
    },

    computed: {
        embedCode() {
            let APP_URL = document.querySelector('meta[name="APP_URL"]').content;
            let url = APP_URL + "/forms/" + this.form.uuid;
            return '<iframe width="100%" height="100%"  style="border:none;" src="' + url + '"></iframe>';
        },
        dragOptions() {
            return {
                animation: 200,
            };
        },
        isSavingEnable() {
            return diff(this.form, this.formClone);
        },
    },

    watch: {
        "$route.params.form_id"(newId, oldId) {
            this.getForms();
        },
    },

    beforeRouteLeave(to, from, next) {
        if (this.isSavingEnable) {
            this.$swal
                .fire({
                    title: "Are you sure",
                    text: "you want to leave? You have unsaved changes?",
                })
                .then((result) => {
                    if (result.value) {
                        next();
                    }
                });
        } else {
            next();
        }
    },
};
</script>
