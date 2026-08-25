<template>
    <header class="admin__topbar flex items-center justify-between py-3 px-3 border-b bg-white">
        <div class="flex items-center bg-white border border-gray-200 rounded z-10 lg:hidden">
            <button @click="$emit('toggle-sidebar')" class="text-gray-500 focus:outline-none lg:hidden">
                <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M4 6H20M4 12H20M4 18H11" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"></path>
                </svg>
            </button>
        </div>
        <Breadcrumb :items="breadcrumbItems" />

        <!-- The admin UI language follows the global default (Settings →
             Localization → "Set as default"); the per-user language switcher
             was removed. -->
        <div class="flex items-center gap-2">
            <a
                :href="appUrl"
                target="_blank"
                rel="noopener"
                v-tooltip="__('Open website')"
                class="flex h-[34px] w-[34px] items-center justify-center rounded-md text-gray-500 transition hover:bg-gray-100 hover:text-indigo-600 focus:outline-none"
            >
                <i class="fas fa-globe"></i>
            </a>

            <ui-dropdown align="right" width="48">
                <template #trigger>
                    <button
                        class="flex items-center gap-1 px-2 py-1 rounded-md text-gray-700 hover:bg-gray-100 focus:outline-none"
                    >
                        <span
                            class="h-8 w-8 rounded-full text-gray-500 flex items-center justify-center"
                        >
                            <i class="fas fa-user"></i>
                        </span>
                        <span class="hidden sm:inline text-sm font-medium">{{ userName }}</span>
                        <i class="fas fa-chevron-down text-xs text-gray-400"></i>
                    </button>
                </template>

                <template #content>
                    <div class="divide-y divide-gray-100">
                        <router-link
                            :to="{ name: 'profile' }"
                            class="flex items-center gap-2 px-4 py-2 text-sm text-gray-700 hover:bg-gray-100"
                        >
                            <i class="fas fa-user text-gray-400 w-4"></i>
                            {{ __('My Profile') }}
                        </router-link>
                        <button
                            @click="logout"
                            class="w-full flex items-center gap-2 px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 text-left"
                        >
                            <i class="fas fa-sign-out-alt text-gray-400 w-4"></i>
                            {{ __('Logout') }}
                        </button>
                    </div>
                </template>
            </ui-dropdown>
        </div>
    </header>
</template>

<script>
import Breadcrumb from './Breadcrumb.vue';
import UiDropdown from '../../components/Dropdown.vue';
import { useAdminStore } from '../store';
export default {
    name: 'Topbar',
    components: { Breadcrumb, UiDropdown },
    computed: {
        appUrl() {
            return document.querySelector('meta[name="APP_URL"]')?.content || '/';
        },
        topbar() {
            return useAdminStore().topbarContent;
        },
        breadcrumbItems() {
            return (this.topbar && this.topbar.breadcrumb) || [];
        },
        user() {
            return useAdminStore().user || {};
        },
        userName() {
            return this.user.name || 'User';
        },

    },
    methods: {
        logout() {
            const store = useAdminStore();
            store.logout();

            const csrfToken = document.head.querySelector('meta[name="csrf-token"]');
            axios
                .post('/logout', {}, {
                    baseURL: '',
                    headers: csrfToken ? { 'X-CSRF-TOKEN': csrfToken.content } : {},
                })
                .then(() => location.reload())
                .catch(() => location.reload());
        },
    },
}
</script>
