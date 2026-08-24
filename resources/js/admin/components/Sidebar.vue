<template>
    <div class="flex flex-col h-full overflow-y-auto">
        <div class="admin__brand text-center border-b border-gray-800">
            <brand size="md" mode="light" imgclass="w-20"></brand>
        </div>
        <nav class="admin__main-menu flex flex-col flex-1 overflow-y-auto">
            <router-link
                :to="{ name: 'dashboard' }"
                :exact-active-class="'bg-blue-500'"
                class="admin__main-menu-item flex flex-nowrap items-center px-8 py-4 hover:bg-blue-500"
            >
                <i class="admin__menu-item-icon pr-4 fas fa-tv"></i>
                <span class="text-xs">{{ __('Dashboard') }}</span>
            </router-link>
            <div class="admin__menu-group">
                <router-link
                    :to="{ name: 'projects' }"
                    :class="['admin__main-menu-item flex flex-nowrap items-center px-8 py-4 hover:bg-blue-400', { 'bg-blue-500': isProjectsActive }]"
                >
                    <i class="admin__menu-item-icon pr-4 fas fa-list"></i>
                    <span class="text-xs">{{ __('Projects') }}</span>
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
                            :class="['admin__sub-menu-item flex flex-nowrap items-center ml-4 pl-10 px-6 py-4 hover:text-blue-600', { 'text-blue-500': isCollectionsActive }]"
                        >
                            <i class="admin__menu-item-icon pr-4 fas fa-table"></i>
                            <span class="text-xs">{{ __('Collections') }}</span>
                        </router-link>
                        <router-link
                            :to="{
                                name: 'projects.content',
                                params: { project_id: $route.params.project_id },
                            }"
                            :class="['admin__sub-menu-item flex flex-nowrap items-center ml-4 pl-10 px-6 py-4 hover:text-blue-600', { 'text-blue-500': isContentActive }]"
                        >
                            <i class="admin__menu-item-icon pr-4 fas fa-edit"></i>
                            <span class="text-xs">{{ __('Content') }}</span>
                        </router-link>
                        <router-link
                            v-if="checkRole(['super_admin'])"
                            :to="{
                                name: 'projects.settings',
                                params: { project_id: $route.params.project_id },
                            }"
                            :class="['admin__sub-menu-item flex flex-nowrap items-center ml-4 pl-10 px-6 py-4 hover:text-blue-600', { 'text-blue-500': isSettingsActive }]"
                        >
                            <i class="admin__menu-item-icon pr-4 fas fa-cog"></i>
                            <span class="text-xs">{{ __('Settings') }}</span>
                        </router-link>
                    </div>
                </div>
            </div>
        </nav>
        <nav class="admin__footer-menu border-t border-gray-700">
            <router-link
                :to="{ name: 'settings' }"
                :active-class="'bg-blue-500'"
                class="admin__footer-menu-item flex flex-nowrap items-center px-8 py-4 hover:bg-blue-500 cursor-pointer"
            >
                <i class="admin__menu-item-icon pr-4 fas fa-cogs"></i>
                <span class="text-xs">{{ __('Setting') }}</span>
            </router-link>
            <router-link
                :to="{ name: 'language' }"
                :class="['admin__footer-menu-item flex flex-nowrap items-center px-8 py-4 hover:bg-blue-500 cursor-pointer', { 'bg-blue-500': isLanguageActive }]"
            >
                <i class="admin__menu-item-icon pr-4 fas fa-language"></i>
                <span class="text-xs">{{ __('Language') }}</span>
            </router-link>
            <router-link
                :to="{ name: 'profile' }"
                :active-class="'bg-blue-500'"
                class="admin__footer-menu-item flex flex-nowrap items-center px-8 py-4 hover:bg-blue-500 cursor-pointer"
            >
                <i class="admin__menu-item-icon pr-4 fas fa-user"></i>
                <span class="text-xs">{{ __('My Profile') }}</span>
            </router-link>
            <div
                @click="logout()"
                :active-class="'bg-blue-500'"
                class="admin__footer-menu-item flex flex-nowrap items-center px-8 py-4 hover:bg-blue-500 cursor-pointer"
            >
                <i class="admin__menu-item-icon pr-4 fas fa-sign-out-alt"></i>
                <span class="text-xs">{{ __('Logout') }}</span>
            </div>
        </nav>
    </div>
</template>

<script>
import Brand from "./Brand.vue";
import UiDropdown from "../../components/Dropdown.vue";

import checkRole from "../../utils/checkrole";
import { useAdminStore } from "../store";

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
            const store = useAdminStore();
            store.logout();

            // Override the global axios baseURL (/admin-api) so the request
            // goes to the real web route POST /logout, and carry the CSRF token.
            const csrfToken = document.head.querySelector('meta[name="csrf-token"]');
            axios
                .post("/logout", {}, {
                    baseURL: "",
                    headers: csrfToken ? { "X-CSRF-TOKEN": csrfToken.content } : {},
                })
                .then((response) => {
                    location.reload();
                })
                .catch((error) => {
                    location.reload();
                });
        },
    },

    computed: {
        currentProject() {
            return useAdminStore().currentProject;
        },

        isProjectsActive() {
            const name = this.$route.name;
            return name === 'projects' || (name && name.startsWith('projects.'));
        },

        isLanguageActive() {
            return this.$route.name === 'language';
        },

        // The project sub-menu routes are flat siblings (not nested
        // children), so vue-router's active-class never matches a sub-page
        // against its parent link. Match by route-name prefix instead.
        isCollectionsActive() {
            const name = this.$route.name;
            return name === 'projects.collections' || (name && name.startsWith('projects.collections.'));
        },

        isContentActive() {
            const name = this.$route.name;
            return name === 'projects.content' || (name && name.startsWith('projects.content.'));
        },

        isSettingsActive() {
            const name = this.$route.name;
            return name === 'projects.settings' || (name && name.startsWith('projects.settings.'));
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
