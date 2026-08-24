<template>
    <div class="admin__profile relative h-full flex flex-col overflow-y-auto">
        <div class="w-full p-4 border-b bg-white">
            <div class="text-xl font-bold">{{ __('Settings') }}</div>
        </div>
        
        <div class="w-full flex flex-col flex-1 an__xxl:w-3/4 m-auto p-4 overflow-y-auto">
            <div class="w-full bg-white rounded-md shadow-sm p-4">
                <form class="space-y-6">
                    <div class="form-item">
                        <label v-formlabel>{{ __('App Name') }}</label>
                        <div class="mt-1 relative">
                            <input
                                type="text"
                                v-model="settings.name"
                                autofocus
                                v-forminput
                                :placeholder="__('App name')"
                            />
                            <p
                                class="text-sm text-red-600 mt-1"
                                v-if="settings.errors && settings.errors.name"
                            >
                                {{ settings.errors.name }}
                            </p>
                        </div>
                    </div>
                    <div class="form-item">
                        <label v-formlabel>{{ __('Description') }}</label>
                        <div class="mt-1 relative">
                            <input
                                type="text"
                                v-model="settings.description"
                                autofocus
                                v-forminput
                                :placeholder="__('App description')"
                            />
                            <p
                                class="text-sm text-red-600 mt-1"
                                v-if="settings.errors && settings.errors.description"
                            >
                                {{ settings.errors.description }}
                            </p>
                        </div>
                    </div>
                    <div class="form-item">
                        <label v-formlabel>{{ __('Version') }}</label>
                        <div class="mt-1 relative">
                            <input
                                type="text"
                                v-model="settings.version"
                                v-forminput
                                :placeholder="__('Version')"
                            />
                            <p
                                class="text-sm text-red-600 mt-1"
                                v-if="settings.errors && settings.errors.version"
                            >
                                {{ settings.errors.version }}
                            </p>
                        </div>
                    </div>
                    <div class="form-button">
                        <label v-formlabel></label>
                        <div class="mt-1 relative">
                            <ui-button
                                :color="'indigo-500'"
                                @click="updateSettings()"
                            >
                                {{ __('Update Settings') }}
                            </ui-button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>

<script>
import { __ } from '../translations/engine';

import UiButton from "../../components/Button.vue";
import { useAdminStore } from '../store';

export default {
    components: {
        UiButton,
    },

    data() {
        return {
            settings: {
                name: null,
                description: null,
                version: null,
                errors: {},
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

        updateSettings() {
            axios.post("settings/update", this.settings).then(
                (response) => {
                    this.$toast.success(__('Saved.'));
                    this.settings.errors = {};
                },
                (error) => {
                    if (error.response && error.response.status == 422) {
                        this.settings.errors = error.response.data.errors;
                    }
                }
            );
        },
    },

    created() {
        useAdminStore().setTopbarContent({ 
            page: 'settings',
            type: 'settings', 
            title: 'Settings',
            breadcrumb: [
                { name: 'Dashboard', url: '/', icon: 'fa fa-tachometer-alt' },
                { name: 'Settings', icon: 'fa fa-cog' },
            ],
        });
    },

    mounted() {
        this.getSettings();
    },
};
</script>
