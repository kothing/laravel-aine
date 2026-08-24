<template>
    <div class="admin__project-collection-index relative h-full flex flex-col">
        <project-header :project="project"></project-header>

        <div class="w-96 flex flex-1 bg-white overflow-y-auto">
            <collection-sidebar :project="project"></collection-sidebar>
        </div>
    </div>
</template>

<script>
import ProjectHeader from "../components/ProjectHeader.vue";
import CollectionSidebar from "./sections/CollectionSidebar.vue";
import projectBreadcrumb from "../../mixins/projectBreadcrumb";
import { useAdminStore } from "../../store";

export default {
    components: {
        CollectionSidebar,
        ProjectHeader,
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
