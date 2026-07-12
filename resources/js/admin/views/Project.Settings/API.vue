<template>
    <div class="admin__project-settings-api relative h-full flex flex-col">
        <project-header :project="project"></project-header>

        <div class="flex flex-1 overflow-y-auto">
            <div class="w-3/12 bg-white overflow-x-hidden">
                <settings-nav :project="project"></settings-nav>
            </div>
            <div class="w-9/12 overflow-x-hidden">
                <div class="p-4">
                    <h4 class="mb-2 p-2 font-bold text-xl">API Access</h4>

                    <div class="w-full bg-white mt-2 rounded-md p-4">
                        <div>
                            <div class="text-lg font-bold">Project UUID</div>
                            <div
                                class="mt-1 flex rounded-md cursor-pointer"
                                @click="copyToClipboard(project.uuid)"
                            >
                                <span class="inline-flex items-center px-3 rounded-l-sm border border-r-0 border-gray-300 bg-gray-50 text-gray-500 text-sm cursor-pointer">
                                    <i class="far fa-copy"></i>
                                </span>
                                <input
                                    type="text"
                                    readonly
                                    disabled
                                    :value="project.uuid"
                                    v-forminput
                                    class="rounded-l-none cursor-pointer"
                                />
                            </div>
                        </div>

                        <div class="mt-5">
                            <div class="text-lg font-bold">Project Slug</div>
                            <div
                                class="mt-1 flex rounded-md cursor-pointer"
                                @click="copyToClipboard(project.slug)"
                            >
                                <span class="inline-flex items-center px-3 rounded-l-sm border border-r-0 border-gray-300 bg-gray-50 text-gray-500 text-sm cursor-pointer">
                                    <i class="far fa-copy"></i>
                                </span>
                                <input
                                    type="text"
                                    readonly
                                    disabled
                                    :value="project.slug"
                                    v-forminput
                                    class="rounded-l-none cursor-pointer"
                                />
                            </div>
                        </div>

                        <div class="mt-5">
                            <div class="text-lg font-bold">
                                Content API Endpoint
                            </div>
                            <div
                                class="mt-1 flex rounded-md cursor-pointer"
                                @click="copyToClipboard(endpointUrl)"
                            >
                                <span class="inline-flex items-center px-3 rounded-l-sm border border-r-0 border-gray-300 bg-gray-50 text-gray-500 text-sm cursor-pointer">
                                    <i class="far fa-copy"></i>
                                </span>
                                <input
                                    type="text"
                                    readonly
                                    disabled
                                    :value="endpointUrl"
                                    v-forminput
                                    class="rounded-l-none cursor-pointer"
                                />
                            </div>
                        </div>

                        <div class="mt-10">
                            <label
                                for="togglePublic"
                                class="p-5 border border-gray-300 rounded-md text-sm flex items-center space-x-2 cursor-pointer"
                                v-if="project.public_api"
                            >
                                <div class="relative">
                                    <input
                                        type="checkbox"
                                        id="togglePublic"
                                        class="sr-only"
                                        v-model="enable_public_access"
                                        @click.prevent="disablePublicAccess"
                                    />
                                    <div class="block bg-gray-600 w-14 h-8 rounded-full"></div>
                                    <div class="dot absolute left-1 top-1 bg-white w-6 h-6 rounded-full transition"></div>
                                </div>
                                <span>Disable Public API Access</span>
                            </label>
                            <label
                                for="togglePublic"
                                class="p-5 border border-gray-300 rounded-md text-sm flex items-center space-x-2 cursor-pointer"
                                v-else-if="!project.public_api"
                            >
                                <div class="relative">
                                    <input
                                        type="checkbox"
                                        id="togglePublic"
                                        class="sr-only"
                                        v-model="enable_public_access"
                                        @click.prevent="enablePublicAccess"
                                    />
                                    <div class="block bg-gray-600 w-14 h-8 rounded-full"></div>
                                    <div class="dot absolute left-1 top-1 bg-white w-6 h-6 rounded-full transition"></div>
                                </div>
                                <span>Enable Public API Access</span>
                            </label>
                        </div>

                        <div class="mt-10">
                            <div class="w-full flex justify-between">
                                <div class="text-lg font-bold">
                                    Access Tokens
                                </div>

                                <div>
                                    <div
                                        class="cursor-pointer text-indigo-700"
                                        @click="openNewTokenModal = true"
                                    >
                                        Create New Token
                                    </div>
                                </div>
                            </div>
                            <div class="overflow-x-auto mt-1 flex border rounded-md">
                                <table class="min-w-full divide-y divide-gray-200">
                                    <thead class="bg-gray-100">
                                        <tr>
                                            <th
                                                scope="col"
                                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                                            >
                                                Name
                                            </th>
                                            <th
                                                scope="col"
                                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                                            >
                                                Permissions
                                            </th>
                                            <th
                                                scope="col"
                                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-px"
                                            ></th>
                                            <th
                                                scope="col"
                                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-px"
                                            ></th>
                                            <th
                                                scope="col"
                                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-px"
                                            ></th>
                                        </tr>
                                    </thead>

                                    <tbody class="bg-white divide-y divide-gray-200">
                                        <tr
                                            v-for="token in tokens"
                                            :key="token.id"
                                        >
                                            <td class="px-6 py-3 text-sm whitespace-nowrap">
                                                {{ token.name }}
                                            </td>
                                            <td class="px-6 py-3 text-sm whitespace-nowrap">
                                                <span
                                                    class="text-gray-500 text-sm rounded-md bg-gray-100 py-1 px-3 mr-2"
                                                    v-for="perm in token.abilities"
                                                    :key="perm"
                                                >
                                                    {{ perm }}
                                                </span>
                                            </td>
                                            <td class="px-6 py-3 text-sm whitespace-nowrap">
                                                <span v-show="token.last_used_at !== null">
                                                    Last Used at {{ token.last_used_at | date("D MMM YYYY, H:mm") }}
                                                </span>
                                            </td>
                                            <td class="px-6 py-3 text-sm">
                                                <div
                                                    class="cursor-pointer text-indigo-500"
                                                    @click="editToken(token)"
                                                >
                                                    Edit
                                                </div>
                                            </td>
                                            <td class="px-6 py-3 text-sm">
                                                <div
                                                    class="cursor-pointer text-red-700"
                                                    @click="deleteToken(token)"
                                                >
                                                    Revoke
                                                </div>
                                            </td>
                                        </tr>
                                        <tr v-if="tokens != undefined && tokens.length === 0">
                                            <td
                                                colspan="100%"
                                                class="text-center text-sm text-gray-500 p-5"
                                            >
                                                This project does not have tokens yet. In order to access to the API create a new token.
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        
                        <div class="mt-10">
                            <div class="text-lg font-bold mb-3">
                                Domain Whitelist
                            </div>
                            <div class="text-sm text-gray-600 mb-4">
                                Add the domains of client applications that call this project's API. Requests from these origins are permitted when used together with a valid Access Token (or Public API for read-only access).
                            </div>

                            <!-- Domain List -->
                            <div class="space-y-2 mb-4">
                                <div 
                                    v-for="(domain, index) in domain_whitelist" 
                                    :key="index"
                                    class="flex items-center space-x-2"
                                >
                                    <input
                                        type="url"
                                        v-model="domain_whitelist[index]"
                                        placeholder="https://example.com"
                                        v-forminput
                                        class="flex-1"
                                    />
                                    <button
                                        type="button"
                                        @click="removeDomain(index)"
                                        class="px-3 py-2 bg-red-500 hover:bg-red-600 text-white rounded-md text-sm"
                                    >
                                        <i class="fa fa-trash"></i>
                                    </button>
                                </div>
                            </div>

                            <!-- Add Domain Button -->
                            <button
                                type="button"
                                @click="addDomain"
                                class="inline-flex items-center px-4 py-2 bg-indigo-500 hover:bg-indigo-600 text-white rounded-md text-sm"
                            >
                                <i class="fa fa-plus mr-2"></i>
                                Add Domain
                            </button>

                            <!-- Save Button -->
                            <div class="mt-4 flex justify-end">
                                <button
                                    type="button"
                                    @click="saveDomainWhitelist"
                                    class="px-4 py-2 bg-green-500 hover:bg-green-600 text-white rounded-md text-sm"
                                    :disabled="savingDomains"
                                >
                                    <span v-if="savingDomains">
                                        <i class="fa fa-spinner fa-spin mr-2"></i>
                                        Saving...
                                    </span>
                                    <span v-else>
                                        <i class="fa fa-save mr-2"></i>
                                        Save Domains
                                    </span>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <ui-modal :show="openNewTokenModal" @close="closeNewTokenModal">
            <template #title>{{ !editStatus ? "Create New Token" : "Update Token" }}</template>

            <template #content>
                <div class="mt-4">
                    <div v-if="showToken">
                        <div class="rounded bg-orange-100 p-4 text-orange-800 flex items-center items-stretch text-sm leading-5">
                            <span class="mr-3">
                                <i class="fa fa-exclamation-circle"></i>
                            </span>
                            <span>
                                Please copy your new API token. For your security, it won't be shown again. If you lose this token you can generate a new one.
                            </span>
                        </div>
                        <div
                            class="mt-5 flex rounded-md cursor-pointer"
                            @click="copyToClipboard(createdToken)"
                        >
                            <span class="inline-flex items-center px-3 rounded-l-sm border border-r-0 border-gray-300 bg-gray-50 text-gray-500 text-sm cursor-pointer">
                                <i class="far fa-copy"></i>
                            </span>
                            <input
                                type="text"
                                readonly
                                disabled
                                :value="createdToken"
                                v-forminput
                                class="rounded-l-none cursor-pointer"
                            />
                        </div>
                    </div>
                    <div v-if="!showToken">
                        <form @submit.prevent="createNewTokenSubmit">
                            <div>
                                <label v-formlabel>Name</label>

                                <input
                                    type="text"
                                    v-model="new_token.name"
                                    v-forminput
                                />

                                <p
                                    class="text-sm text-red-600 mt-1"
                                    v-if="new_token.errors.name"
                                >
                                    {{ new_token.errors.name[0] }}
                                </p>
                            </div>
                            <div class="mt-5">
                                <label v-formlabel>Permissions</label>

                                <div class="grid grid-cols-2">
                                    <div class="col-span-1">
                                        <div class="flex items-start">
                                            <div class="flex items-center h-5">
                                                <input
                                                    v-model="new_token.permissions"
                                                    :value="'create'"
                                                    id="create"
                                                    type="checkbox"
                                                    v-formcheckbox
                                                />
                                            </div>
                                            <div class="ml-3 text-sm">
                                                <label
                                                    for="create"
                                                    class="font-medium text-gray-700"
                                                >
                                                    Create
                                                </label>
                                                <p class="text-gray-500">
                                                    Can create new content
                                                </p>
                                            </div>
                                        </div>
                                        <div class="flex items-start mt-3">
                                            <div class="flex items-center h-5">
                                                <input
                                                    v-model="new_token.permissions"
                                                    :value="'update'"
                                                    id="update"
                                                    type="checkbox"
                                                    v-formcheckbox
                                                />
                                            </div>
                                            <div class="ml-3 text-sm">
                                                <label
                                                    for="update"
                                                    class="font-medium text-gray-700"
                                                >
                                                    Update
                                                </label>
                                                <p class="text-gray-500">
                                                    Can update existing content
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-span-1">
                                        <div class="flex items-start">
                                            <div class="flex items-center h-5">
                                                <input
                                                    v-model="new_token.permissions"
                                                    :value="'read'"
                                                    id="read"
                                                    type="checkbox"
                                                    v-formcheckbox
                                                />
                                            </div>
                                            <div class="ml-3 text-sm">
                                                <label
                                                    for="read"
                                                    class="font-medium text-gray-700"
                                                >
                                                    Read
                                                </label>
                                                <p class="text-gray-500">
                                                    Can read content
                                                </p>
                                            </div>
                                        </div>
                                        <div class="flex items-start mt-3">
                                            <div class="flex items-center h-5">
                                                <input
                                                    v-model="new_token.permissions"
                                                    :value="'delete'"
                                                    id="delete"
                                                    type="checkbox"
                                                    v-formcheckbox
                                                />
                                            </div>
                                            <div class="ml-3 text-sm">
                                                <label
                                                    for="delete"
                                                    class="font-medium text-gray-700"
                                                >
                                                    Delete
                                                </label>
                                                <p class="text-gray-500">
                                                    Can delete content
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </template>

            <template #footer>
                <ui-button
                    color="gray-200"
                    hover="gray-300"
                    @click.native="closeNewTokenModal"
                >
                    <span class="text-gray-800">Cancel</span>
                </ui-button>
                <ui-button
                    v-if="!showToken"
                    color="indigo-500"
                    @click.native="createNewTokenSubmit"
                >
                    {{ !editStatus ? "Create Token" : "Save Changes" }}
                </ui-button>
            </template>
        </ui-modal>
    </div>
</template>

<script>
import Vue from "vue";
import Clipboard from "v-clipboard";

import UiButton from "../../../components/Button.vue";
import UiModal from "../../../components/Modal.vue";

import ProjectHeader from "../components/ProjectHeader.vue";

import SettingsNav from "./sections/SettingsNav.vue";

Vue.use(Clipboard);

export default {
    components: {
        ProjectHeader,
        SettingsNav,
        UiButton,
        UiModal,
    },

    data() {
        return {
            project: {},
            tokens: {},
            openNewTokenModal: false,
            new_token: {
                errors: {},
                permissions: ["read"],
            },
            showToken: false,
            createdToken: null,
            editStatus: false,
            enable_public_access: false,
            domain_whitelist: [],
            savingDomains: false,
        };
    },

    methods: {
        getProject() {
            axios
                .get("/admin/projects/settings/api/" + this.$route.params.project_id)
                .then((response) => {
                    this.project = response.data.project;
                    this.tokens = response.data.tokens;
                    this.enable_public_access = response.data.project.public_api;
                    this.domain_whitelist = response.data.project.domain_whitelist || [];
                });
        },

        copyToClipboard(str) {
            this.$clipboard(str);
            this.$toast.success("Copied to clipboard");
        },

        closeNewTokenModal() {
            this.openNewTokenModal = false;
            (this.new_token = {
                errors: {},
                permissions: ["read"],
            }),
                (this.showToken = false),
                (this.createdToken = null);
            this.editStatus = false;
        },

        createNewTokenSubmit() {
            if (this.editStatus) {
                axios
                    .post(
                        "/admin/projects/settings/api/update-token/" + this.project.id,
                        this.new_token
                    )
                    .then(
                        (response) => {
                            this.$toast.success("Token updated!");
                            this.getProject();
                            this.closeNewTokenModal();
                        },
                        (error) => {
                            if (error.response.status == 422) {
                                this.new_token.errors = error.response.data.errors;
                            }
                        }
                    );
            } else {
                axios
                    .post(
                        "/admin/projects/settings/api/new-token/" + this.project.id,
                        this.new_token
                    )
                    .then(
                        (response) => {
                            this.$toast.success("Token created!");
                            this.getProject();
                            this.showToken = true;
                            this.createdToken = response.data;
                        },
                        (error) => {
                            if (error.response.status == 422) {
                                this.new_token.errors = error.response.data.errors;
                            }
                        }
                    );
            }
        },

        editToken(token) {
            this.new_token = {
                id: token.id,
                name: token.name,
                errors: {},
                permissions: token.abilities,
            };
            this.editStatus = true;
            this.openNewTokenModal = true;
        },

        deleteToken(token) {
            this.$swal
                .fire({
                    title: "Are you sure",
                    text: "you want to delete this token? Any applications using this token will not be able to connect to the API!",
                })
                .then((result) => {
                    if (result.value) {
                        axios
                            .post(
                                "/admin/projects/settings/api/delete-token/" + this.project.id,
                                token
                            )
                            .then((response) => {
                                this.$toast.success("Token deleted.");
                                this.getProject();
                            });
                    }
                });
        },

        enablePublicAccess() {
            this.$swal
                .fire({
                    title: "Are you sure",
                    text: "you want to enable public API access for get(read) requests in this project? You're still going to need an access token for post and delete (create, update, delete) requests.",
                })
                .then((result) => {
                    if (result.value) {
                        axios
                            .post(
                                "/admin/projects/settings/api/enable_public_access/" + this.project.id
                            )
                            .then((response) => {
                                this.$toast.success(
                                    "Public API Access has been enabled."
                                );
                                this.getProject();
                            });
                    }
                });
        },

        disablePublicAccess() {
            this.$swal
                .fire({
                    title: "Are you sure",
                    text: "you want to disable public API access for this project?",
                })
                .then((result) => {
                    if (result.value) {
                        axios
                            .post(
                                "/admin/projects/settings/api/disable_public_access/" + this.project.id
                            )
                            .then((response) => {
                                this.$toast.success(
                                    "Public API Access has been disabled."
                                );
                                this.getProject();
                            });
                    }
                });
        },

        addDomain() {
            this.domain_whitelist.push('');
        },

        removeDomain(index) {
            this.domain_whitelist.splice(index, 1);
        },

        saveDomainWhitelist() {
            // Filter out empty domains
            const domains = this.domain_whitelist.filter(domain => domain.trim() !== '');
            
            if (domains.length === 0) {
                this.$toast.warning('Please add at least one domain to the whitelist');
                return;
            }

            this.savingDomains = true;
            
            axios
                .post(
                    "/admin/projects/settings/api/update-domain-whitelist/" + this.project.id,
                    { domain_whitelist: domains }
                )
                .then((response) => {
                    this.$toast.success('Domain whitelist saved successfully');
                    this.getProject();
                })
                .catch((error) => {
                    if (error.response && error.response.status === 422) {
                        this.$toast.error('Invalid URL format. Please check your domains.');
                    } else {
                        this.$toast.error('Failed to save domains');
                    }
                })
                .finally(() => {
                    this.savingDomains = false;
                });
        },
    },

    computed: {
        endpointUrl() {
            let APP_URL = document.querySelector(
                'meta[name="APP_URL"]'
            ).content;
            return APP_URL + "/api/" + this.project.uuid;
        },
    },

    mounted() {
        this.getProject();
    },
};
</script>
