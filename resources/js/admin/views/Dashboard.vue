<template>
    <div class="admin__projects-dashboard m-3 p-3 overflow-y-auto">
        <p class="py-3">Welcome Dashboard</p>
        <hr />
        <p class="font-bold py-3 text-gray-600">{{ settings.name || 'AineCMS' }}</p>
        <p class="text-gray-600">Description: {{ settings.description || '' }}</p>
        <p class="text-gray-600">Version: {{ settings.version || '0.0.1' }}</p>
    </div>
</template>

<script>
    export default {
        components: {},

        data() {
            return {
                locales: [],
                settings: {
                    name: null,
                    description: null,
                    version: null
                },
            };
        },

        methods: {
            getSettings() {
                axios
                .get("/admin/settings")
                .then(
                    (response) => {
                        this.settings = response.data;
                    },
                    (error) => {
                        console.warn(error);
                    }
                );
            },
        },

        created() {
            this.$store.commit('SET_TOPBAR_CONTENT', { 
                page: 'dashboard',
                type: 'dashboard', 
                title: 'Dashboard',
                breadcrumb: [
                    { name: 'Dashboard', url: '/', icon: 'fa fa-tachometer-alt' },
                ],
            });
        },

        mounted() {
            this.getSettings();
        },
    };
</script>
