<template>
    <div class="admin__projects-dashboard m-3 p-3 overflow-y-auto">
        <p class="py-3">{{ __('Welcome Dashboard') }}</p>
        <hr />
        <p class="font-bold py-3 text-gray-600">{{ settings.name || 'Aine' }}</p>
        <p class="text-gray-600">{{ __('Description:') }} {{ settings.description || '' }}</p>
        <p class="text-gray-600">{{ __('Version:') }} {{ settings.version || '0.0.1' }}</p>
    </div>
</template>

<script>
    import { useAdminStore } from '../store';

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
                .get("settings")
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
            useAdminStore().setTopbarContent({ 
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
