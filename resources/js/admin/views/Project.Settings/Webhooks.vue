<template>
    <div class="admin__project-settings-webhooks relative h-full flex flex-col">
        <project-header :project="project"></project-header>

        <div class="flex flex-1 overflow-y-auto">
            <div class="w-3/12 bg-white overflow-x-hidden">
                <settings-sidebar :project="project"></settings-sidebar>
            </div>

            <div class="w-9/12 overflow-x-hidden">
                <div class="p-4">
                    <div class="flex justify-between p-2 items-center">
                        <h4 class="mb-2 font-bold text-xl">{{ __('Webhooks') }}</h4>

                        <ui-button
                            color="indigo-500"
                            @click="openNewWebhookModal = true"
                        >
                            {{ __('+ Create a New Webhook') }}
                        </ui-button>
                    </div>

                    <div class="bg-white mt-2 rounded-md p-4 w-full">
                        <div class="mt-2">
                            <div
                                class="overflow-x-auto mt-1 flex border rounded-md"
                            >
                                <table
                                    v-if="webhooks != undefined"
                                    class="w-full divide-y divide-gray-200"
                                >
                                    <thead class="bg-gray-100">
                                        <tr>
                                            <th
                                                scope="col"
                                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                                            >
                                                {{ __('Name') }}
                                            </th>
                                            <th
                                                scope="col"
                                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                                            >
                                                {{ __('URL') }}
                                            </th>
                                            <th
                                                scope="col"
                                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                                            >
                                                {{ __('Collections') }}
                                            </th>
                                            <th
                                                scope="col"
                                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                                            >
                                                {{ __('Events') }}
                                            </th>
                                            <th
                                                scope="col"
                                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                                            >
                                                {{ __('Sources') }}
                                            </th>
                                            <th
                                                scope="col"
                                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                                            >
                                                {{ __('Status') }}
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

                                    <tbody
                                        class="bg-white divide-y divide-gray-200"
                                    >
                                        <tr v-for="wh in webhooks" :key="wh.id">
                                            <td
                                                class="px-6 py-3 text-sm whitespace-nowrap"
                                            >
                                                {{ wh.name }}
                                            </td>
                                            <td
                                                class="px-6 py-3 text-sm whitespace-nowrap"
                                            >
                                                {{ wh.url }}
                                            </td>
                                            <td class="px-6 py-3 text-sm">
                                                <div
                                                    class="text-gray-500 text-sm rounded-md bg-gray-100 py-1 px-3 mb-1"
                                                    v-for="(
                                                        col, i
                                                    ) in wh.collections"
                                                    :key="i"
                                                >
                                                    {{ col.name }}
                                                </div>
                                            </td>
                                            <td class="px-6 py-3 text-sm">
                                                <div
                                                    class="text-gray-500 text-sm rounded-md bg-gray-100 py-1 px-3 mb-1"
                                                    v-for="ev in wh.events"
                                                    :key="ev"
                                                >
                                                    {{ ev }}
                                                </div>
                                            </td>
                                            <td class="px-6 py-3 text-sm">
                                                <div
                                                    class="text-gray-500 text-sm rounded-md bg-gray-100 py-1 px-3 mb-1"
                                                    v-for="src in wh.sources"
                                                    :key="src"
                                                >
                                                    {{ src }}
                                                </div>
                                            </td>
                                            <td
                                                class="px-6 py-3 text-sm whitespace-nowrap text-center text-xl"
                                            >
                                                <i
                                                    v-if="wh.status"
                                                    class="fas fa-toggle-on text-green-500"
                                                ></i>
                                                <i
                                                    v-else
                                                    class="fas fa-toggle-off text-gray-500"
                                                ></i>
                                            </td>
                                            <td
                                                class="px-6 py-3 text-sm whitespace-nowrap"
                                            >
                                                <router-link
                                                    :to="{
                                                        name: 'projects.settings.webhooks.logs',
                                                        params: {
                                                            project_id:
                                                                project.id,
                                                            webhook_id: wh.id,
                                                        },
                                                    }"
                                                    class="text-yellow-500 flex items-center"
                                                >
                                                    <i class="fas fa-history mr-2"></i>
                                                    {{ __('View Logs') }}
                                                </router-link>
                                            </td>
                                            <td class="px-6 py-3 text-sm">
                                                <div
                                                    class="cursor-pointer text-indigo-500"
                                                    @click="editWebhook(wh)"
                                                >
                                                    {{ __('Edit') }}
                                                </div>
                                            </td>
                                            <td class="px-6 py-3 text-sm">
                                                <div
                                                    class="cursor-pointer text-red-700"
                                                    @click="deleteWebhook(wh)"
                                                >
                                                    {{ __('Delete') }}
                                                </div>
                                            </td>
                                        </tr>
                                        <tr v-if="webhooks != undefined && webhooks.length === 0">
                                            <td
                                                colspan="100%"
                                                class="text-center text-sm text-gray-500 p-5"
                                            >
                                                {{ __('This project does not have webhooks yet.') }}
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <ui-modal :show="openNewWebhookModal" @close="closeNewWebhookModal">
            <template #title>{{ !editStatus ? "Create New Webhook" : "Update Webhook" }}</template>

            <template #content>
                <div class="mt-4">
                    <div>
                        <form @submit.prevent="createNewWebhookSubmit">
                            <div>
                                <label v-formlabel>{{ __('Name') }}</label>
                                <input
                                    type="text"
                                    v-model="new_webhook.data.name"
                                    v-forminput
                                    :placeholder="__('Name')"
                                />
                                <p
                                    class="text-sm text-red-600 mt-1"
                                    v-if="new_webhook.errors.name"
                                >
                                    {{ new_webhook.errors.name[0] }}
                                </p>
                            </div>
                            <div class="mt-5">
                                <label v-formlabel>{{ __('Description') }}</label>
                                <input
                                    type="text"
                                    v-model="new_webhook.data.description"
                                    v-forminput
                                    :placeholder="__('Description')"
                                />
                            </div>
                            <div class="mt-5">
                                <label v-formlabel>{{ __('URL') }}</label>
                                <input
                                    type="text"
                                    v-model="new_webhook.data.url"
                                    placeholder="https://"
                                    v-forminput
                                />
                                <p
                                    class="text-sm text-red-600 mt-1"
                                    v-if="new_webhook.errors.url"
                                >
                                    {{ new_webhook.errors.url[0] }}
                                </p>
                            </div>
                            <div class="mt-5">
                                <label v-formlabel>{{ __('Secret') }}</label>
                                <div class="mt-1 flex rounded-md">
                                    <span class="inline-flex items-center px-3 rounded-l-sm border border-r-0 border-gray-300 bg-gray-50 text-gray-500 text-sm">
                                        <i class="fa fa-lock"></i>
                                    </span>
                                    <input
                                        :type="secretShow ? 'text' : 'password'"
                                        v-model="new_webhook.data.secret"
                                        v-forminput
                                        :placeholder="__('Secret')"
                                        class="rounded-l-none"
                                    />
                                    <span
                                        class="inline-flex items-center px-3 rounded-r-sm border border-l-0 border-gray-300 bg-gray-50 text-gray-500 text-sm cursor-pointer"
                                        @click="secretShow = !secretShow"
                                    >
                                        <i class="fa fa-eye"></i>
                                    </span>

                                    <span
                                        class="inline-flex items-center px-3 rounded-r-sm border border-l-0 border-gray-300 bg-gray-50 text-gray-500 text-sm cursor-pointer"
                                        @click="generateSecret()"
                                    >
                                        {{ __('Generate') }}
                                    </span>
                                </div>
                                <p
                                    class="text-sm text-red-600 mt-1"
                                    v-if="new_webhook.errors.secret"
                                >
                                    {{ new_webhook.errors.secret[0] }}
                                </p>
                            </div>
                            <div class="mt-5">
                                <label v-formlabel>{{ __('Collections') }}</label>
                                <v-select
                                    :options="project.collections"
                                    :get-option-key="(o) => o.id"
                                    :get-option-label="(o) => o.name"
                                    :reduce="(o) => o.id"
                                    class="v-select"
                                    :value="(option) => option[0]"
                                    :placeholder="__('Select Collections')"
                                    multiple
                                    :clearable="true"
                                    :selectable="(selected) => !new_webhook.data.collection_ids.includes(selected.id)"
                                    v-model="new_webhook.data.collection_ids"
                                ></v-select>

                                <p
                                    class="text-sm text-red-600 mt-1"
                                    v-if="new_webhook.errors.collection_ids"
                                >
                                    {{ new_webhook.errors.collection_ids[0] }}
                                </p>
                            </div>
                            <div class="mt-5">
                                <label v-formlabel>{{ __('Events') }}</label>
                                <v-select
                                    :options="events"
                                    class="v-select"
                                    :value="(option) => option[0]"
                                    :placeholder="__('Select Events')"
                                    multiple
                                    :clearable="true"
                                    :selectable="(selected) => !new_webhook.data.events.includes(selected)"
                                    v-model="new_webhook.data.events"
                                ></v-select>

                                <p
                                    class="text-sm text-red-600 mt-1"
                                    v-if="new_webhook.errors.events"
                                >
                                    {{ new_webhook.errors.events[0] }}
                                </p>
                            </div>
                            <div class="mt-5">
                                <label v-formlabel>{{ __('Source') }}</label>
                                <v-select
                                    :options="sources"
                                    class="v-select"
                                    :value="(option) => option[0]"
                                    :placeholder="__('Select Sources')"
                                    multiple
                                    :clearable="true"
                                    :selectable="(selected) => !new_webhook.data.sources.includes(selected)"
                                    v-model="new_webhook.data.sources"
                                ></v-select>

                                <p
                                    class="text-sm text-red-600 mt-1"
                                    v-if="new_webhook.errors.sources"
                                >
                                    {{ new_webhook.errors.sources[0] }}
                                </p>
                            </div>
                            <div class="mt-5">
                                <label
                                    for="togglePayload"
                                    class="flex w-64 items-center cursor-pointer"
                                >
                                    <div class="relative">
                                        <input
                                            type="checkbox"
                                            id="togglePayload"
                                            class="sr-only"
                                            v-model="new_webhook.data.payload"
                                        />
                                        <div class="block bg-gray-600 w-10 h-6 rounded-full"></div>
                                        <div class="dot absolute left-1 top-1 bg-white w-4 h-4 rounded-full transition"></div>
                                    </div>
                                    <span class="ml-2">{{ __('Include payload') }}</span>
                                </label>
                            </div>
                            <div class="mt-5">
                                <label
                                    for="toggleActive"
                                    class="flex w-64 items-center cursor-pointer"
                                >
                                    <div class="relative">
                                        <input
                                            type="checkbox"
                                            id="toggleActive"
                                            class="sr-only"
                                            v-model="new_webhook.data.status"
                                        />
                                        <div class="block bg-gray-600 w-10 h-6 rounded-full"></div>
                                        <div class="dot absolute left-1 top-1 bg-white w-4 h-4 rounded-full transition"></div>
                                    </div>
                                    <span class="ml-2">{{ __('Active') }}</span>
                                </label>
                            </div>
                        </form>
                    </div>
                </div>
            </template>

            <template #footer>
                <ui-button
                    color="gray-200"
                    hover="gray-300"
                    @click="closeNewWebhookModal"
                >
                    <span class="text-gray-800">{{ __('Cancel') }}</span>
                </ui-button>
                <ui-button
                    color="indigo-500"
                    :disabled="progress"
                    @click="createNewWebhookSubmit"
                >
                    {{ !editStatus ? __('Create Webhook') : __('Save Changes') }}
                </ui-button>
            </template>
        </ui-modal>
    </div>
</template>

<script>
import { __ } from '../../translations/engine';

import UiButton from "../../../components/Button.vue";
import UiModal from "../../../components/Modal.vue";

import ProjectHeader from "../components/ProjectHeader.vue";
import SettingsSidebar from "./sections/SettingsSidebar.vue";
import projectBreadcrumb from "../../mixins/projectBreadcrumb";
import { useAdminStore } from "../../store";

export default {
    components: {
        ProjectHeader,
        SettingsSidebar,
        UiButton,
        UiModal,
    },

    mixins: [projectBreadcrumb],

    computed: {
    },

    data() {
        return {
            // Pre-seed from the store (loaded by the router guard before
            // this page renders) so the shell never flashes blank while the
            // page's own getProject() refreshes the data.
            project: useAdminStore().currentProject || {},
            webhooks: {},
            progress: false,
            openNewWebhookModal: false,
            new_webhook: {
                data: {
                    secret: null,
                    collection_ids: [],
                    events: [],
                    sources: [],
                    payload: true,
                    status: true,
                },
                errors: {},
            },
            secretShow: false,
            events: [
                "content.created",
                "content.updated",
                "content.trashed",
                "content.deleted",
                "content.published",
                "content.unpublished",
                "content.restored",
                "form.submitted",
            ],
            sources: ["User", "API"],
            editStatus: false,
        };
    },

    methods: {
        getProject() {
            axios
                .get(
                    "projects/settings/webhooks/" +
                        this.$route.params.project_id
                )
                .then((response) => {
                    this.project = response.data;
                    this.webhooks = response.data.webhooks;
                });
        },

        copyToClipboard(str) {
            navigator.clipboard.writeText(str);
            this.$toast.success(__('Copied to clipboard'));
        },

        closeNewWebhookModal() {
            this.openNewWebhookModal = false;
            this.new_webhook = {
                data: {
                    secret: null,
                    collection_ids: [],
                    events: [],
                    sources: [],
                    payload: true,
                    status: true,
                },
                errors: {},
            };
            this.editStatus = false;
        },

        createNewWebhookSubmit() {
            this.progress = true;

            if (this.editStatus) {
                axios
                    .post(
                        "projects/settings/webhooks/update/" + this.project.id,
                        this.new_webhook.data
                    )
                    .then(
                        (response) => {
                            this.$toast.success(__('Webhook updated!'));
                            this.getProject();
                            this.closeNewWebhookModal();
                            this.progress = false;
                        },
                        (error) => {
                            if (error.response && error.response.status == 422) {
                                this.new_webhook.errors = error.response.data.errors;
                            }
                            this.progress = false;
                        }
                    );
            } else {
                axios
                    .post(
                        "projects/settings/webhooks/new/" + this.project.id,
                        this.new_webhook.data
                    )
                    .then(
                        (response) => {
                            this.$toast.success(__('Webhook created!'));
                            this.getProject();
                            this.closeNewWebhookModal();
                            this.progress = false;
                        },
                        (error) => {
                            if (error.response && error.response.status == 422) {
                                this.new_webhook.errors = error.response.data.errors;
                            }
                            this.progress = false;
                        }
                    );
            }
        },

        editWebhook(webhook) {
            this.new_webhook = {
                data: JSON.parse(JSON.stringify(webhook)),
                errors: {},
            };
            this.editStatus = true;
            this.openNewWebhookModal = true;
        },

        deleteWebhook(webhook) {
            this.$swal
                .fire({
                    title: __('Are you sure'),
                    text: __('you want to delete this webhook?'),
                })
                .then((result) => {
                    if (result.isConfirmed) {
                        axios
                            .post(
                                "projects/settings/webhooks/delete/" + this.project.id,
                                webhook
                            )
                            .then((response) => {
                                this.$toast.success(__('Webhook deleted.'));
                                this.getProject();
                            });
                    }
                });
        },

        generateSecret() {
            let CharacterSet = "";
            let secret = "";

            CharacterSet += "abcdefghijklmnopqrstuvwxyz";
            CharacterSet += "ABCDEFGHIJKLMNOPQRSTUVWXYZ";
            CharacterSet += "0123456789";
            CharacterSet += "![]{}()%&*$#^<>~@|";

            for (let i = 0; i < 12; i++) {
                secret += CharacterSet.charAt(
                    Math.floor(Math.random() * CharacterSet.length)
                );
            }
            this.new_webhook.data.secret = secret;
            this.secretShow = true;
        },
    },

    mounted() {
        this.getProject();
    },
};
</script>
