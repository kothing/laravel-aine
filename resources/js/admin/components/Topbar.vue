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

            <ui-dropdown align="right" width="80">
                <template #trigger>
                    <button
                        class="relative flex h-[34px] w-[34px] items-center justify-center rounded-md text-gray-500 transition hover:bg-gray-100 hover:text-indigo-600 focus:outline-none"
                    >
                        <i class="fas fa-bell"></i>
                        <span
                            v-if="unreadCount > 0"
                            class="absolute -top-0.5 -right-0.5 flex h-4 min-w-4 items-center justify-center rounded-full bg-red-500 px-1 text-[10px] font-bold text-white"
                        >
                            {{ unreadCount > 9 ? '9+' : unreadCount }}
                        </span>
                    </button>
                </template>

                <template #content>
                    <div class="w-full">
                        <div class="flex items-center justify-between px-4 py-2 border-b border-gray-100">
                            <span class="text-sm font-semibold text-gray-700">
                                {{ __('Notifications') }}
                            </span>
                            <button
                                v-if="unreadCount > 0"
                                class="text-xs text-indigo-500 font-semibold hover:underline"
                                @click="markAllRead"
                            >
                                {{ __('Mark all as read') }}
                            </button>
                        </div>

                        <div class="max-h-80 overflow-y-auto divide-y divide-gray-100">
                            <div
                                v-for="notification in notifications"
                                :key="notification.id"
                                class="flex items-start gap-2 px-4 py-3 hover:bg-gray-50 cursor-pointer"
                                @click="markRead(notification)"
                            >
                                <span
                                    class="mt-1.5 h-2 w-2 rounded-full shrink-0"
                                    :class="notification.read_at ? 'bg-transparent' : 'bg-indigo-500'"
                                ></span>
                                <div class="flex-1 min-w-0">
                                    <div class="text-sm text-gray-800">
                                        <span class="font-semibold">{{ actionLabel(notification.data.action) }}</span>
                                        <span class="text-gray-500"> · {{ notification.data.entity_label }}</span>
                                    </div>
                                    <div class="text-xs text-gray-400 mt-0.5">
                                        {{ $filters.date(notification.created_at, "D MMM YYYY, H:mm") }}
                                    </div>
                                </div>
                            </div>

                            <div
                                v-if="notifications.length === 0"
                                class="px-4 py-8 text-center text-sm text-gray-400"
                            >
                                {{ __('No notifications yet.') }}
                            </div>
                        </div>
                    </div>
                </template>
            </ui-dropdown>

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
    data() {
        return {
            notifications: [],
            unreadCount: 0,
        };
    },
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
        fetchNotifications() {
            axios
                .get("notifications")
                .then((response) => {
                    this.notifications = response.data.data || [];
                    this.unreadCount = response.data.unread_count || 0;
                })
                .catch(() => {});
        },

        markRead(notification) {
            if (notification.read_at) return;
            axios
                .post("notifications/read", {
                    ids: [notification.id],
                })
                .then(() => {
                    notification.read_at = new Date().toISOString();
                    this.unreadCount = Math.max(0, this.unreadCount - 1);
                })
                .catch(() => {});
        },

        markAllRead() {
            axios
                .post("notifications/read", {})
                .then(() => {
                    this.notifications.forEach((n) => (n.read_at = new Date().toISOString()));
                    this.unreadCount = 0;
                })
                .catch(() => {});
        },

        actionLabel(action) {
            const labels = {
                publish: "Published",
                unpublish: "Unpublished",
                trash: "Moved to trash",
                restore: "Restored",
                delete: "Deleted",
                create: "Created",
                update: "Updated",
            };
            return labels[action] || action || "";
        },

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
    mounted() {
        this.fetchNotifications();
    },
}
</script>
