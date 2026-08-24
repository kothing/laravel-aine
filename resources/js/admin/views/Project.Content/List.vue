<template>
    <div class="admin__project-content-list relative h-full flex flex-col">
        <project-header :project="project"></project-header>
        
        <div class="flex flex-1 overflow-y-auto">
            <div class="w-3/12 bg-white overflow-x-hidden">
                <content-sidebar :project="project"></content-sidebar>
            </div>
            
            <div class="w-9/12 p-4 overflow-x-auto">
                <content-table v-if="$route.params.col_id !== undefined" :collection_id="parseInt($route.params.col_id)"></content-table>
            </div>
        </div>
    </div>
</template>

<script>
import ProjectHeader from "../components/ProjectHeader.vue";
import ContentSidebar from "./sections/ContentSidebar.vue";
import ContentTable from "./sections/ContentTable.vue";
import projectBreadcrumb from "../../mixins/projectBreadcrumb";
import { useAdminStore } from "../../store";

export default {
    components: {
        ProjectHeader,
        ContentSidebar,
        ContentTable,
    },

    mixins: [projectBreadcrumb],

    data() {
        return {
            // Project data comes from the store — the router guard loads it
            // before this page renders, so no extra request is needed.
            project: useAdminStore().currentProject || {},
        };
    },
};
</script>
