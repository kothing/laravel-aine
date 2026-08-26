<template>
    <div class="admin__project-settings-audit-logs relative h-full flex flex-col">
        <project-header :project="project"></project-header>

        <div class="flex flex-1 overflow-y-auto">
            <div class="w-3/12 bg-white overflow-x-hidden">
                <settings-sidebar :project="project"></settings-sidebar>
            </div>
            <div class="w-9/12 overflow-x-hidden">
                <div class="p-4">
                    <div class="flex justify-between p-2 items-center">
                        <h4 class="mb-2 font-bold text-xl">
                            {{ __('Audit Logs') }}
                        </h4>

                        <div class="flex space-x-2">
                            <select
                                v-model="filterAction"
                                class="border border-gray-300 rounded-md px-3 py-2 text-sm bg-white"
                                @change="getLogs(1)"
                            >
                                <option value="">{{ __('All Actions') }}</option>
                                <option
                                    v-for="action in actionOptions"
                                    :key="action.value"
                                    :value="action.value"
                                >
                                    {{ action.label }}
                                </option>
                            </select>
                            <ui-button color="gray-200" hover="gray-300" @click="refreshLogs">
                                <i class="fa fa-sync-alt"></i> {{ __('Refresh') }}
                            </ui-button>
                        </div>
                    </div>

                    <div class="w-full bg-white mt-2 rounded-md p-4">
                        <div class="mt-2">
                            <div class="overflow-x-auto mt-2 flex border rounded-md">
                                <table
                                    v-if="logs.data != undefined"
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
                                                {{ __('User') }}
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
                                                {{ __('Target') }}
                                            </th>
                                            <th
                                                scope="col"
                                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-px"
                                            >
                                                {{ __('Details') }}
                                            </th>
                                        </tr>
                                    </thead>

                                    <tbody class="bg-white divide-y divide-gray-200">
                                        <tr
                                            v-for="log in logs.data"
                                            :key="log.id"
                                        >
                                            <td class="px-6 py-3 text-sm whitespace-nowrap">
                                                {{ $filters.date(log.created_at, "D MMM YYYY, H:mm") }}
                                            </td>
                                            <td class="px-6 py-3 text-sm whitespace-nowrap">
                                                <span v-if="log.user">
                                                    {{ log.user.name }}
                                                    <span class="text-gray-400 text-xs">({{ log.user.email }})</span>
                                                </span>
                                                <span v-else class="text-gray-400">
                                                    {{ __('System') }}
                                                </span>
                                            </td>
                                            <td class="px-6 py-3 text-sm whitespace-nowrap">
                                                <span
                                                    class="px-2 py-1 rounded-md text-xs font-bold"
                                                    :class="actionBadgeClass(log.action)"
                                                >
                                                    {{ actionLabel(log.action) }}
                                                </span>
                                            </td>
                                            <td class="px-6 py-3 text-sm whitespace-nowrap">
                                                <span class="text-gray-500">{{ log.entity_type }}</span>
                                                <span class="text-indigo-600 font-medium">{{ log.entity_label }}</span>
                                            </td>
                                            <td class="px-6 py-3 text-sm whitespace-nowrap text-center">
                                                <span
                                                    v-if="log.details"
                                                    class="text-indigo-500 cursor-pointer hover:bg-gray-100 rounded-md p-2"
                                                    @click="showDetails(log)"
                                                >
                                                    <i class="fas fa-align-center"></i>
                                                </span>
                                            </td>
                                        </tr>
                                        <tr v-if="logs.data.length == 0">
                                            <td
                                                colspan="5"
                                                class="px-6 py-8 text-center text-sm text-gray-400"
                                            >
                                                {{ __('No audit logs found.') }}
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
                                    @pagination-change-page="getLogs"
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
                    <div class="text-xl font-bold">{{ logDetails.entity_label }}</div>
                    <div class="text-sm text-gray-500 mt-1">
                        {{ actionLabel(logDetails.action) }} · {{ $filters.date(logDetails.created_at, "D MMM YYYY, H:mm") }}
                    </div>
                    <div class="w-full flex space-x-4 mt-5">
                        <div class="w-full">
                            <div class="border border-gray-200 rounded-md">
                                <textarea
                                    :value="logDetailsJson"
                                    readonly
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

    data() {
        return {
            project: useAdminStore().currentProject || {},
            logs: { data: [] },
            openDetailModal: false,
            logDetails: {},
            filterAction: "",
            actionOptions: [
                { value: "create", label: "Create" },
                { value: "update", label: "Update" },
                { value: "publish", label: "Publish" },
                { value: "unpublish", label: "Unpublish" },
                { value: "trash", label: "Trash" },
                { value: "restore", label: "Restore" },
                { value: "restore_revision", label: "Restore Revision" },
                { value: "delete", label: "Delete" },
                { value: "import", label: "Import" },
                { value: "export", label: "Export" },
            ],
        };
    },

    computed: {
        paginationInfo() {
            return __('{total} records, {from} - {to} showing', {
                total: this.logs.total,
                from: this.logs.from,
                to: this.logs.to,
            });
        },

        logDetailsJson() {
            if (!this.logDetails.details) return "{}";
            if (typeof this.logDetails.details === "string") {
                try {
                    return JSON.stringify(JSON.parse(this.logDetails.details), null, 2);
                } catch (e) {
                    return this.logDetails.details;
                }
            }
            return JSON.stringify(this.logDetails.details, null, 2);
        },
    },

    methods: {
        getLogs(page) {
            if (typeof page === "undefined") {
                page = 1;
            }

            let url = "admin-api/audit-logs/project/" + this.$route.params.project_id + "?page=" + page;
            if (this.filterAction) {
                url += "&action=" + encodeURIComponent(this.filterAction);
            }

            axios
                .get(url)
                .then((response) => {
                    this.logs = response.data;
                });
        },

        refreshLogs() {
            this.getLogs(1);
        },

        showDetails(log) {
            this.logDetails = log;
            this.openDetailModal = true;
        },

        closeDetailModal() {
            this.logDetails = {};
            this.openDetailModal = false;
        },

        actionLabel(action) {
            const labels = {
                create: "Create",
                update: "Update",
                publish: "Publish",
                unpublish: "Unpublish",
                trash: "Trash",
                restore: "Restore",
                restore_revision: "Restore Revision",
                delete: "Delete",
                import: "Import",
                export: "Export",
            };
            return labels[action] || action;
        },

        actionBadgeClass(action) {
            const classes = {
                create: "bg-green-100 text-green-700",
                update: "bg-blue-100 text-blue-700",
                publish: "bg-emerald-100 text-emerald-700",
                unpublish: "bg-yellow-100 text-yellow-700",
                trash: "bg-orange-100 text-orange-700",
                restore: "bg-teal-100 text-teal-700",
                restore_revision: "bg-teal-100 text-teal-700",
                delete: "bg-red-100 text-red-700",
                import: "bg-purple-100 text-purple-700",
                export: "bg-gray-200 text-gray-700",
            };
            return classes[action] || "bg-gray-100 text-gray-700";
        },
    },

    mounted() {
        this.getLogs();
    },
};
</script>
