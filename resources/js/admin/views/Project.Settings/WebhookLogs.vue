<template>
    <div class="admin__project-settings-webhook-logs relative h-full flex flex-col">
        <project-header :project="project"></project-header>

        <div class="flex flex-1 overflow-y-auto">
            <div class="w-3/12 bg-white overflow-x-hidden">
                <settings-sidebar :project="project"></settings-sidebar>
            </div>
            <div class="w-9/12 overflow-x-hidden">
                <div class="p-4">
                    <div class="flex justify-between p-2 items-center">
                        <h4 class="mb-2 font-bold text-xl">
                            {{ __('Webhooks') }} <small>/ {{ webhook.name }} / {{ __('Logs') }}</small>
                        </h4>

                        <ui-button color="indigo-500" @click="clearLogs">
                            <i class="fa fa-trash-restore"></i> {{ __('Clear Logs') }}
                        </ui-button>
                    </div>

                    <div class="w-full bg-white mt-2 rounded-md p-4">
                        <div class="mt-2">
                            <div
                                class="overflow-x-auto mt-2 flex border rounded-md"
                            >
                                <table
                                    v-if="logs != undefined"
                                    class="w-full divide-y divide-gray-200"
                                >
                                    <thead class="bg-gray-100">
                                        <tr>
                                            <th
                                                scope="col"
                                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                                            >
                                                {{ __('Date') }}
                                            </th>
                                            <th
                                                scope="col"
                                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                                            >
                                                {{ __('Collection') }}
                                            </th>
                                            <th
                                                scope="col"
                                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                                            >
                                                {{ __('Action') }}
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
                                            >
                                                {{ __('Request / Response') }}
                                            </th>
                                        </tr>
                                    </thead>

                                    <tbody
                                        class="bg-white divide-y divide-gray-200"
                                    >
                                        <tr
                                            v-for="log in logs.data"
                                            :key="log.id"
                                        >
                                            <td
                                                class="px-6 py-3 text-sm whitespace-nowrap"
                                            >
                                                {{ $filters.date(log.created_at, "D MMM YYYY, H:mm") }}
                                            </td>
                                            <td
                                                class="px-6 py-3 text-sm whitespace-nowrap"
                                            >
                                                {{ JSON.parse(log.request).collection }}
                                            </td>
                                            <td class="px-6 py-3 text-sm whitespace-nowrap">
                                                {{ log.action }}
                                            </td>
                                            <td
                                                class="px-6 py-3 text-sm whitespace-nowrap font-bold"
                                            >
                                                <span
                                                    v-if="log.status == 'success'"
                                                    class="text-green-600"
                                                >
                                                    {{ log.status }}
                                                </span
                                                >
                                                <span
                                                    v-else-if="log.status == 'failed'"
                                                    class="text-red-600"
                                                >
                                                    {{ log.status }}
                                                </span>
                                            </td>
                                            <td
                                                class="px-6 py-3 text-sm whitespace-nowrap text-center"
                                            >
                                                <span
                                                    class="text-indigo-500 cursor-pointer hover:bg-gray-100 rounded-md p-2"
                                                    @click="showText(log)"
                                                >
                                                    <i class="fas fa-align-center"></i>
                                                </span>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>

                            <div
                                class="mt-2 clear-both flex justify-between items-center"
                            >
                                <pagination
                                    :data="logs"
                                    size="small"
                                    :limit="3"
                                    @pagination-change-page="getProject"
                                ></pagination>

                                <div class="text-sm italic text-gray-500">
                                    {{ paginationInfo }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <ui-modal
            :show="openDetailModal"
            @close="closeDetailModal"
            maxWidth="3xl"
        >
            <template #content>
                <div class="mt-4">
                    <div class="w-full flex">
                        <div class="text-indigo-700">
                            <span class="text-gray-500">{{ __('URL:') }}</span>
                            {{ logDetails.url }}
                        </div>
                    </div>

                    <div class="w-full flex space-x-4 mt-5">
                        <div class="w-1/2">
                            <div class="text-xl">{{ __('Request') }}</div>
                            <div class="border border-gray-200">
                                <textarea
                                    v-model="logDetails.request"
                                    class="w-full h-64 border-0 text-xs font-mono"
                                ></textarea>
                            </div>
                        </div>
                        <div class="w-1/2">
                            <div class="text-xl">{{ __('Response') }}</div>
                            <div class="border border-gray-200">
                                <textarea
                                    v-model="logDetails.response"
                                    class="w-full h-64 border-0 text-xs font-mono"
                                ></textarea>
                            </div>
                        </div>
                    </div>
                </div>
            </template>

            <template #footer>
                <ui-button
                    color="gray-200"
                    hover="gray-300"
                    @click="closeDetailModal"
                >
                    <span class="text-gray-800">{{ __('Close') }}</span>
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
            webhook: {},
            logs: {},
            openDetailModal: false,
            detailType: "",
            logDetails: {
                request: "",
                response: "",
                url: "",
            },

            cmOptions: {
                mode: {
                    name: "javascript",
                    json: true,
                },
                readOnly: true,
                lineWrapping: true,
                autoRefresh: true,
            },
        };
    },

    methods: {
        getProject(page) {
            if (typeof page === "undefined") {
                page = 1;
            }

            axios
                .get(
                    "projects/settings/webhooks/" + this.$route.params.project_id + "/logs/" + this.$route.params.webhook_id + "?page=" + page
                )
                .then((response) => {
                    this.project = response.data.project;
                    this.webhook = response.data.webhook;
                    this.logs = response.data.logs;
                });
        },

        showText(log) {
            this.logDetails = {
                request: log.request,
                response: log.response,
                url: log.url,
            };
            this.openDetailModal = true;
        },

        closeDetailModal() {
            this.logDetails = {
                request: null,
                response: null,
                url: "",
            };
            this.openDetailModal = false;
        },

        clearLogs() {
            this.$swal
                .fire({
                    title: __('Are you sure'),
                    text: __('you want to delete all logs?'),
                })
                .then((result) => {
                    if (result.isConfirmed) {
                        axios
                            .delete(
                                "projects/settings/webhooks/" + this.$route.params.project_id + "/logs/" + this.$route.params.webhook_id
                            )
                            .then((response) => {
                                this.$toast.success(
                                    __('All logs has been deleted.')
                                );
                                this.getProject();
                            });
                    }
                });
        },
    },

    computed: {
        paginationInfo() {
            return __('{total} records, {from} - {to} showing', { total: this.logs.total, from: this.logs.from, to: this.logs.to });
        },
    },

    mounted() {
        this.getProject();
    },
};
</script>
