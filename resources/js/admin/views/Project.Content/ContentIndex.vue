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

export default {
    components: {
        ProjectHeader,
        ContentSidebar,
    },

    data() {
        return {
            project: {},
        };
    },

    methods: {
        getProject() {
            axios.get("/admin/content/project/" + this.$route.params.project_id).then((response) => {
                this.project = response.data;
            });
        },
    },

    mounted() {
        this.getProject();
    },
};
</script>
