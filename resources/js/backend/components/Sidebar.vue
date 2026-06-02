<template>
    <div class="flex flex-col h-full overflow-y-auto">
        <div class="admin__brand text-center">
            <brand size="md" mode="light" imgclass="w-20"></brand>
        </div>
        <nav class="admin__main-menu flex flex-col flex-1 overflow-y-auto">
            <router-link
                :to="{ name: 'dashboard' }"
                :exact-active-class="'bg-gray-700'"
                class="admin__main-menu-item flex flex-nowrap items-center my-2 px-8 py-4 block hover:bg-gray-700"
            >
                <i class="admin__menu-item-icon pr-4 fas fa-tv"></i>
                <span class="text-xs">Dashboard</span>
            </router-link>
            <router-link
                :to="{ name: 'projects' }"
                :active-class="'bg-gray-700'"
                class="admin__main-menu-item flex flex-nowrap items-center my-2 px-8 py-4 block hover:bg-gray-700"
            >
                <i class="admin__menu-item-icon pr-4 fas fa-list"></i>
                <span class="text-xs">Projects</span>
            </router-link>
            <div
                v-if="isProjectPage"
                class="admin__project-name px-8 py-3 bg-gray-800 border-t border-b border-gray-700"
            >
                <!-- <div class="text-xs text-gray-400 mb-1">Current Project</div> -->
                <div class="text-sm font-bold text-white truncate" :title="currentProjectName">
                    {{ currentProjectName }}
                </div>
            </div>
            <div
                v-if="isProjectPage"
                class="admin__sub-menu pl-10 bg-gray-800"
            >
                <router-link
                    v-if="checkRole(['admin' + $route.params.project_id])"
                    :to="{
                        name: 'projects.collections',
                        params: { project_id: $route.params.project_id },
                    }"
                    :active-class="'bg-gray-700'"
                    class="admin__sub-menu-item flex flex-nowrap items-center px-6 py-4 block hover:bg-gray-700"
                >
                    <i class="admin__menu-item-icon pr-4 fas fa-table"></i>
                    <span class="text-xs">Collections</span>
                </router-link>
                <router-link
                    :to="{
                        name: 'projects.content',
                        params: { project_id: $route.params.project_id },
                    }"
                    :active-class="'bg-gray-700'"
                    class="admin__sub-menu-item flex flex-nowrap items-center px-6 py-4 block hover:bg-gray-700"
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
                    :active-class="'bg-gray-700'"
                    class="admin__sub-menu-item flex flex-nowrap items-center px-6 py-4 block hover:bg-gray-700"
                >
                    <i class="admin__menu-item-icon pr-4 fas fa-cog"></i>
                    <span class="text-xs">Settings</span>
                </router-link>
            </div>
        </nav>
        <nav class="admin__footer-menu">
            <router-link
                :to="{ name: 'settings' }"
                :active-class="'bg-gray-700'"
                class="admin__footer-menu-item flex flex-nowrap items-center px-8 py-4 block hover:bg-gray-700 cursor-pointer"
            >
                <i class="admin__menu-item-icon pr-4 fas fa-cogs"></i>
                <span class="text-xs">Setting</span>
            </router-link>
            <router-link
                :to="{ name: 'profile' }"
                :active-class="'bg-gray-700'"
                class="admin__footer-menu-item flex flex-nowrap items-center px-8 py-4 block hover:bg-gray-700 cursor-pointer"
            >
                <i class="admin__menu-item-icon pr-4 fas fa-user"></i>
                <span class="text-xs">My Profile</span>
            </router-link>
            <div
                @click="logout()"
                class="admin__footer-menu-item flex flex-nowrap items-center px-8 py-4 block hover:bg-gray-700 cursor-pointer"
            >
                <i class="admin__menu-item-icon pr-4 fas fa-sign-out-alt"></i>
                <span class="text-xs">Logout</span>
            </div>
        </nav>
    </div>
</template>

<script>
import Brand from "./Brand.vue";
import UiDropdown from "../../UI/Dropdown.vue";

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
};
</script>
