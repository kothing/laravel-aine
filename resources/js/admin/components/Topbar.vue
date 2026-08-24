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
        </div>
    </header>
</template>

<script>
import Breadcrumb from './Breadcrumb.vue';
import { useAdminStore } from '../store';
export default {
    name: 'Topbar',
    components: { Breadcrumb },
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
    },
}
</script>
