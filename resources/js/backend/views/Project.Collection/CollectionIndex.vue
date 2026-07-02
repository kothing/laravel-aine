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

export default {
    components: {
        CollectionSidebar,
        ProjectHeader,
    },

    data() {
        return {
            project: {},
        };
    },

    methods: {
        getProject() {
            axios.get("/admin/collections/project/" + this.$route.params.project_id).then((response) => {
                this.project = response.data;
            });
        },
    },

    mounted() {
        this.getProject();
    },
};
</script>
