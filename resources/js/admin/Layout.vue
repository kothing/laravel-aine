<template>
    <div class="admin__container flex h-screen overflow-hidden bg-gray-50">
        <div 
            :class="sidebarOpen ? 'block' : 'hidden'" 
            @click="sidebarOpen = false" 
            class="fixed z-10 inset-0 bg-black opacity-50 transition-opacity lg:hidden"
        ></div>

        <!-- Fixed sidebar: always pinned to the left; only the right-hand
             content column scrolls vertically. -->
        <div 
            :class="sidebarOpen ? 'translate-x-0 ease-out' : '-translate-x-full ease-in'"
            class="admin__sidebar w-64 fixed inset-y-0 left-0 z-50 bg-gray-900 transition duration-300 transform lg:translate-x-0 text-white"
        >
            <sidebar></sidebar>
        </div>

        <div class="admin__content w-full flex flex-col flex-1" :class="screenWidth < 1024 ? 'sidebar-close' : ''">
            <Topbar @toggle-sidebar="sidebarOpen = !sidebarOpen" />
            <!-- Single scroll container for the right-hand content. Pages that
                 manage their own internal scroll keep it; the wrapper is the
                 fallback so nothing is ever clipped. -->
            <div class="admin__page relative flex-1 min-h-0 overflow-y-auto overflow-x-hidden">
                <router-view></router-view>
                <!-- Route transition overlay: shown while the router loads a
                     lazy chunk and resolves the async guard data, so switches
                     never flash a blank or half-rendered page. -->
                <transition name="route-fade">
                    <div v-if="routeLoadingVisible" class="admin__route-loading">
                        <div class="admin__route-loading-inner">
                            <div class="admin__route-spinner"></div>
                            <span>{{ __('Loading...') }}</span>
                        </div>
                    </div>
                </transition>
            </div>
        </div>
    </div>
</template>

<script>
import Sidebar from './components/Sidebar.vue';
import Topbar from './components/Topbar.vue';
import { useAdminStore } from './store';

export default {
    components: {
        Sidebar,
        Topbar,
    },
    setup() {
        return {
            store: useAdminStore(),
        };
    },
    data() {
        return {
            screenWidth: document.body.clientWidth,
            sidebarOpen: document.body.clientWidth >= 1024 ? true : false,
            // The overlay appears only when a navigation takes long enough to
            // matter (see the routeLoading watcher), so quick switches never
            // flash it.
            routeLoadingVisible: false,
            routeLoadingTimer: null,
        }
    },
    methods: {
        onResize() {
            this.screenWidth = document.body.clientWidth;
            this.sidebarOpen = document.body.clientWidth >= 1024 ? true : false;
        },
    },
    watch: {
        'store.routeLoading'(value) {
            clearTimeout(this.routeLoadingTimer);
            if (value) {
                // Wait ~150ms before showing the overlay: transitions that
                // resolve faster are imperceptible and stay clean.
                this.routeLoadingTimer = setTimeout(() => {
                    this.routeLoadingVisible = true;
                }, 150);
            } else {
                this.routeLoadingVisible = false;
            }
        },
    },
    mounted() {
        window.addEventListener('resize', this.onResize);
    },
    beforeUnmount() {
        window.removeEventListener('resize', this.onResize);
    },
}
</script>

<style scoped>
.admin__route-loading {
    position: absolute;
    inset: 0;
    z-index: 40;
    display: flex;
    align-items: center;
    justify-content: center;
    background-color: rgba(249, 250, 251, 0.75);
    backdrop-filter: blur(2px);
}

.admin__route-loading-inner {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 0.75rem;
    color: #6b7280;
    font-size: 0.875rem;
    font-weight: 500;
}

.admin__route-spinner {
    width: 2.25rem;
    height: 2.25rem;
    border: 3px solid #e5e7eb;
    border-top-color: #6366f1;
    border-radius: 9999px;
    animation: admin-route-spin 0.7s linear infinite;
}

@keyframes admin-route-spin {
    to {
        transform: rotate(360deg);
    }
}

.route-fade-enter-active,
.route-fade-leave-active {
    transition: opacity 0.18s ease;
}

.route-fade-enter-from,
.route-fade-leave-to {
    opacity: 0;
}
</style>
