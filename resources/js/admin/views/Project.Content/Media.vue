<template>
    <div class="admin__project-content-media-library relative h-screen overflow-auto">
        <project-header :project="project"></project-header>

        <div class="flex flex-1 overflow-y-auto">
            <div class="w-3/12 bg-white overflow-x-hidden">
                <content-sidebar :project="project"></content-sidebar>
            </div>

            <div class="w-9/12 p-4 overflow-x-auto">
                <media-library :project="project"></media-library>
            </div>
        </div>
    </div>
</template>

<script>
import ProjectHeader from "../components/ProjectHeader.vue";
import MediaLibrary from "../components/MediaLibrary.vue";

import ContentSidebar from "./sections/ContentSidebar.vue";
import projectBreadcrumb from "../../mixins/projectBreadcrumb";
import { useAdminStore } from "../../store";

export default {
    components: {
        ProjectHeader,
        ContentSidebar,
        MediaLibrary,
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
