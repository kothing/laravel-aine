<template>
    <div class="admin__project-header w-full border-b bg-white an__sticky top-0 z-1">
        <div class="px-4 py-2 border-b">
            <div class="text-xl font-bold">
                Project: {{ project.name }}
            </div>
            <div class="text-sm">{{ project.description || "Project description" }}</div>
        </div>
        <div class="flex items-center px-4 py-2 text-gray-600">
            <span class="px-1">
                <router-link :to="{ name: 'projects' }">
                    <i class="fas fa-list text-sm text-gray-500"></i> Projects List
                </router-link>
            </span>
            <span class="px-1">
                <i class="px-1 text-sm">/</i>
                <router-link :to="{ name: 'projects.index', params: { project_id: project.id } }">
                    <i class="fa fa-cubes text-sm text-gray-500"></i> Project
                </router-link>
            </span>
            <template v-if="getPageType === 'collections' || getPageType === 'content' || getPageType === 'settings'">
                <span class="px-0">
                    <template v-if="getPageType === 'collections' && checkRole(['admin' + $route.params.project_id])">
                        <i class="px-1 text-sm">/</i>
                        <router-link :to="{ name: 'projects.collections', params: { project_id:$route.params.project_id }}">
                            <i class="fa fa-table text-sm text-gray-500"></i> Collections
                        </router-link>
                    </template>
                    <template v-if="getPageType === 'content'">
                        <i class="px-1 text-sm">/</i>
                        <router-link :to="{ name: 'projects.content', params: { project_id: $route.params.project_id }}">
                            <i class="fa fa-edit text-sm text-gray-500"></i> Content
                        </router-link>
                    </template>
                    <template v-if="getPageType === 'settings' && checkRole(['super_admin'])">
                        <i class="px-1 text-sm">/</i>
                        <router-link :to="{ name: 'projects.settings', params: { project_id: $route.params.project_id }}">
                            <i class="fa fa-cog text-sm text-gray-500"></i> Settings
                        </router-link>
                    </template>
                </span>
            </template>
        </div>
    </div>
</template>

<script>
import checkRole from "../../../utils/checkrole";

export default {
    props: ["project"],

    methods: {
        checkRole,
    },

    computed: {
        isProjectPage() {
            const fullPath = this.$route.fullPath;
            const projectPath = "/project/";
            if (
                fullPath.includes(projectPath) &&
                fullPath.length > projectPath.length
            ) {
                return true;
            }
            return false;
        },
        getPageType() {
            const hasPathSplit = location.hash.split("/");
            const level1Page = hasPathSplit[0] || null;
            const level2Page = hasPathSplit[3] || null;
            if(level1Page && level2Page) {
                return level2Page;
            }
            return level1Page;
        }
    },
};
</script>
