<template>
    <header class="admin__topbar flex items-center py-3 px-3 border-b bg-white ">
        <div class="flex items-center bg-white border border-gray-200 rounded z-10 lg:hidden">
            <button @click="$emit('toggle-sidebar')" class="text-gray-500 focus:outline-none lg:hidden">
                <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M4 6H20M4 12H20M4 18H11" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"></path>
                </svg>
            </button>
        </div>
        <Breadcrumb :items="topbar && topbar.breadcrumb" />
        <!-- <div v-if="topbar && topbar.title" class="text-sm font-semibold text-gray-700 ml-2">
            {{ topbar.title }}
        </div> -->
    </header>
</template>

<script>
import Breadcrumb from './Breadcrumb.vue';
export default {
    name: 'Topbar',
    components: { Breadcrumb },
    computed: {
        topbar() {
            return this.$store.state.topbarContent;
        }
    }
}
</script>
