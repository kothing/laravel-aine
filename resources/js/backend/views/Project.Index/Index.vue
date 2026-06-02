<template>
    <div class="admin__project-centre relative h-full flex flex-col">
        <project-header :project="project"></project-header>
        <div class="grid grid-cols-1 sm:grid-cols-2 pt-4 overflow-y-auto">
            <div class="col p-4" v-if="checkRole(['admin' + project.id])">
                <div class="inline-flex mb-5">
                    <div class="mr-4 text-gray-100 bg-yellow-900 rounded-md text-xl p-4 h-full items-center content-center">
                        <i class="fas fa-table"></i>
                    </div>
                    <div>
                        <h3 class="font-bold text-lg">Set up Collections</h3>
                        <div class="text-sm">
                            Add new collections to your projects, edit fields to create your schema.
                        </div>
                    </div>
                </div>
                <collection-sidebar :project="project" class="shadow-md rounded-md"></collection-sidebar>
            </div>
            <div class="col p-4">
                <div class="inline-flex mb-5">
                    <div class="mr-4 text-gray-100 bg-green-400 rounded-md text-xl p-4 h-full items-center content-center">
                        <i class="fas fa-edit"></i>
                    </div>
                    <div class="block">
                        <h3 class="font-bold text-lg">Create Content</h3>
                        <div class="text-sm">
                            Using the collection schema structure create content for your project.
                        </div>
                    </div>
                </div>
                <content-sidebar :project="project" class="shadow-md rounded-md"></content-sidebar>
            </div>
        </div>
    </div>
</template>

<script>
import ProjectHeader from "../components/ProjectHeader.vue";
import CollectionSidebar from "../Project.Collection/sections/CollectionSidebar.vue";
import ContentSidebar from "../Project.Content/sections/ContentSidebar.vue";
import checkRole from "../../../utils/checkrole";

export default {
    components: {
        ProjectHeader,
        CollectionSidebar,
        ContentSidebar,
    },

    data() {
        return {
            project: {},
        };
    },

    methods: {
        checkRole,

        getProject() {
            axios
            .get("/admin/projects/" + this.$route.params.project_id)
            .then((response) => {
                this.project = response.data;
            });
        },
    },

    mounted() {
        this.getProject();
    },
};
</script>
