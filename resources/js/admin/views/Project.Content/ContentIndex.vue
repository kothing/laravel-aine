<template>
    <div class="admin__project-content-index relative h-full flex flex-col">
        <project-header :project="project"></project-header>
        <div class="w-96 flex flex-1 bg-white overflow-y-auto">
            <content-sidebar :project="project" class="h-full"></content-sidebar>
        </div>
    </div>
</template>

<script>
import ProjectHeader from "../components/ProjectHeader.vue";
import ContentSidebar from "./sections/ContentSidebar.vue";
import projectBreadcrumb from "../../mixins/projectBreadcrumb";
import { useAdminStore } from "../../store";

export default {
    components: {
        ProjectHeader,
        ContentSidebar,
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
