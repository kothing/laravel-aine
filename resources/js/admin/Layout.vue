<template>
    <div class="admin__container flex h-screen bg-gray-50">
        <div 
            :class="sidebarOpen ? 'block' : 'hidden'" 
            @click="sidebarOpen = false" 
            class="fixed z-10 inset-0 bg-black opacity-50 transition-opacity lg:hidden"
        ></div>

        <div 
            :class="sidebarOpen ? 'translate-x-0 ease-out' : '-translate-x-full ease-in'"
            class="admin__sidebar w-64 fixed inset-y-0 left-0 z-50 bg-gray-900 transition duration-300 transform lg:translate-x-0 lg:static lg:inset-0 text-white"
        >
            <sidebar></sidebar>
        </div>

        <div class="admin__content w-full flex flex-col flex-1 verflow-auto" :class="screenWidth < 1024 ? 'sidebar-close' : ''">
            <header class="admin__topbar flex items-center py-1 px-3 absolute top-0 bg-white border-b border-gray-200 lg:hidden z-10">
                <div class="flex items-center m-r-2">
                    <button @click="sidebarOpen = !sidebarOpen" class="text-gray-500 focus:outline-none lg:hidden">
                        <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M4 6H20M4 12H20M4 18H11" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"></path>
                        </svg>
                    </button>
                </div>
            </header>

            <router-view></router-view>
        </div>
    </div>
</template>

<script>
import Sidebar from './components/Sidebar.vue';

export default {
    components: {
        Sidebar,
    },
    data() {
        return {
            screenWidth: document.body.clientWidth,
            sidebarOpen: document.body.clientWidth >= 1024 ? true : false,
        }
    },
    mounted() {
        const _this = this;
        window.addEventListener('resize', function () {
            _this.$data.screenWidth = document.body.clientWidth;
            _this.$data.sidebarOpen = document.body.clientWidth >= 1024 ? true : false;
        });
    },
}
</script>
