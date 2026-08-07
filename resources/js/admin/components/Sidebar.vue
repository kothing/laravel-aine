<template>
    <div class="flex flex-col h-full overflow-y-auto">
        <div class="admin__brand text-center border-b border-gray-800">
            <brand size="md" mode="light" imgclass="w-20"></brand>
        </div>
        <nav class="admin__main-menu flex flex-col flex-1 overflow-y-auto">
            <router-link
                :to="{ name: 'dashboard' }"
                :exact-active-class="'bg-gray-700'"
                class="admin__main-menu-item flex flex-nowrap items-center px-8 py-4 border-b border-gray-800 hover:bg-gray-700"
            >
                <i class="admin__menu-item-icon pr-4 fas fa-tv"></i>
                <span class="text-xs">Dashboard</span>
            </router-link>
            <div class="admin__menu-group">
                <router-link
                    :to="{ name: 'projects' }"
                    :class="['admin__main-menu-item flex flex-nowrap items-center px-8 py-4 hover:bg-blue-500', { 'bg-blue-500': isProjectsActive }]"
                >
                    <i class="admin__menu-item-icon pr-4 fas fa-list"></i>
                    <span class="text-xs">Projects</span>
                </router-link>
                <div v-if="isProjectPage" class="admin__project-group bg-gray-800">
                    <div class="admin__project-name-row flex items-center px-8 py-3 border-b border-gray-700">
                        <span
                            @click="projectExpanded = !projectExpanded"
                            class="cursor-pointer mr-2 w-4 text-center text-gray-400 hover:text-white select-none"
                        >
                            <i :class="projectExpanded ? 'fas fa-minus' : 'fas fa-plus'"></i>
                        </span>
                        <router-link
                            :to="{
                                name: 'projects.index',
                                params: { project_id: $route.params.project_id },
                            }"
                            :active-class="'text-blue-500'"
                            class="admin__project-name flex items-center flex-1 hover:text-blue-600"
                        >
                            <i class="admin__menu-item-icon pr-4 fas fa-cubes"></i>
                            <span class="text-sm font-bold truncate" :title="currentProjectName">
                                {{ currentProjectName }}
                            </span>
                        </router-link>
                    </div>
                    <div v-show="projectExpanded" class="admin__project-sub-menu">
                        <router-link
                            v-if="checkRole(['admin' + $route.params.project_id])"
                            :to="{
                                name: 'projects.collections',
                                params: { project_id: $route.params.project_id },
                            }"
                            :active-class="'text-blue-500'"
                            class="admin__sub-menu-item flex flex-nowrap items-center ml-4 pl-10 px-6 py-4 hover:text-blue-600"
                        >
                            <i class="admin__menu-item-icon pr-4 fas fa-table"></i>
                            <span class="text-xs">Collections</span>
                        </router-link>
                        <router-link
                            :to="{
                                name: 'projects.content',
                                params: { project_id: $route.params.project_id },
                            }"
                            :active-class="'text-blue-500'"
                            class="admin__sub-menu-item flex flex-nowrap items-center ml-4 pl-10 px-6 py-4 hover:text-blue-600"
                        >
                            <i class="admin__menu-item-icon pr-4 fas fa-edit"></i>
                            <span class="text-xs">Content</span>
                        </router-link>
                        <router-link
                            v-if="checkRole(['super_admin'])"
                            :to="{
                                name: 'projects.settings',
                                params: { project_id: $route.params.project_id },
                            }"
                            :active-class="'text-blue-500'"
                            class="admin__sub-menu-item flex flex-nowrap items-center ml-4 pl-10 px-6 py-4 hover:text-blue-600"
                        >
                            <i class="admin__menu-item-icon pr-4 fas fa-cog"></i>
                            <span class="text-xs">Settings</span>
                        </router-link>
                    </div>
                </div>
            </div>
        </nav>
        <nav class="admin__footer-menu border-t border-gray-700">
            <router-link
                :to="{ name: 'settings' }"
                :active-class="'bg-gray-700'"
                class="admin__footer-menu-item flex flex-nowrap items-center px-8 py-4 hover:bg-gray-700 cursor-pointer"
            >
                <i class="admin__menu-item-icon pr-4 fas fa-cogs"></i>
                <span class="text-xs">Setting</span>
            </router-link>
            <router-link
                :to="{ name: 'profile' }"
                :active-class="'bg-gray-700'"
                class="admin__footer-menu-item flex flex-nowrap items-center px-8 py-4 hover:bg-gray-700 cursor-pointer"
            >
                <i class="admin__menu-item-icon pr-4 fas fa-user"></i>
                <span class="text-xs">My Profile</span>
            </router-link>
            <div
                @click="logout()"
                class="admin__footer-menu-item flex flex-nowrap items-center px-8 py-4 hover:bg-gray-700 cursor-pointer"
            >
                <i class="admin__menu-item-icon pr-4 fas fa-sign-out-alt"></i>
                <span class="text-xs">Logout</span>
            </div>
        </nav>
    </div>
</template>

<script>
import Brand from "./Brand.vue";
import UiDropdown from "../../components/Dropdown.vue";

import checkRole from "../../utils/checkrole";
import store from "../store";
import { mapGetters } from 'vuex';

export default {
    components: {
        Brand,
        UiDropdown,
    },
    data() {
        return {
            sidebarOpen: false,
            projectExpanded: true,
        };
    },

    methods: {
        checkRole,

        logout() {
            store.commit("LOGOUT");

            axios
                .post("/logout")
                .then((response) => {
                    location.reload();
                })
                .catch((error) => {
                    location.reload();
                });
        },
    },

    computed: {
        ...mapGetters(['currentProject']),

        isProjectsActive() {
            const name = this.$route.name;
            return name === 'projects' || (name && name.startsWith('projects.'));
        },

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

        currentProjectName() {
            if (this.currentProject) {
                return this.currentProject.name;
            }
            return 'Loading...';
        },
    },

    watch: {
        '$route'(to) {
            const name = to.name;
            if (name && (name.startsWith('projects.collections') || name.startsWith('projects.content') || name.startsWith('projects.settings'))) {
                this.projectExpanded = true;
            }
        },
    },
};
</script>
