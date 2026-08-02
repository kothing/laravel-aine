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
            <Topbar @toggle-sidebar="sidebarOpen = !sidebarOpen" />
            <router-view></router-view>
        </div>
    </div>
</template>

<script>
import Sidebar from './components/Sidebar.vue';
import Topbar from './components/Topbar.vue';

export default {
    components: {
        Sidebar,
        Topbar,
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
