<template>
    <div class="admin__profile relative h-full flex flex-col overflow-y-auto">
        <div class="w-full p-4 border-b bg-white">
            <div class="text-xl font-bold">{{ __('My Profile') }}</div>
        </div>
        
        <form
            class="w-full flex flex-col flex-1 an__xxl:w-3/4 m-auto p-4 overflow-y-auto"
            @submit.prevent="saveProfile()"
        >
            <div class="w-full bg-white rounded-md shadow-sm p-4">
                <div class="form-item">
                    <label v-formlabel>{{ __('Name') }}</label>
                    <div class="mt-1 relative">
                        <input
                            type="text"
                            v-model="user.name"
                            autofocus
                            v-forminput
                            :placeholder="__('User name')"
                        />
                        <p
                            class="text-sm text-red-600 mt-1"
                            v-if="user.errors.name"
                        >
                            {{ user.errors.name[0] }}
                        </p>
                    </div>
                </div>
            </div>

            <div class="w-full bg-white rounded-md shadow-sm p-4 mt-5">
                <div class="form-item">
                    <label v-formlabel>{{ __('E-mail') }}</label>
                    <div class="mt-1 relative">
                        <input
                            type="text"
                            v-model="user.email"
                            autofocus
                            v-forminput
                            :placeholder="__('Email')"
                        />
                        <p
                            class="text-sm text-red-600 mt-1"
                            v-if="user.errors.email"
                        >
                            {{ user.errors.email[0] }}
                        </p>
                    </div>
                </div>
            </div>

            <div class="w-full bg-white rounded-md shadow-sm p-4 mt-5 mb-5">
                <div class="form-item">
                    <label v-formlabel>{{ __('Current Password') }}</label>
                    <div class="mt-1 relative">
                        <input
                            type="password"
                            v-model="user.current_password"
                            v-forminput
                            :placeholder="__('Current password')"
                        />
                        <p
                            class="text-sm text-red-600 mt-1"
                            v-if="user.errors.current_password"
                        >
                            {{ user.errors.current_password[0] }}
                        </p>
                    </div>
                </div>
                <div class="form-item mt-2">
                    <label v-formlabel>{{ __('Password') }}</label>
                    <div class="mt-1 relative">
                        <input
                            type="password"
                            v-model="user.password"
                            autofocus
                            v-forminput
                            :placeholder="__('Password')"
                        />
                        <p
                            class="text-sm text-red-600 mt-1"
                            v-if="user.errors.password"
                        >
                            {{ user.errors.password[0] }}
                        </p>
                    </div>
                </div>
                <div class="form-item mt-2">
                    <label v-formlabel>{{ __('Confirm Password') }}</label>
                    <div class="mt-1 relative">
                        <input
                            type="password"
                            v-model="user.password_confirmation"
                            autofocus
                            v-forminput
                            :placeholder="__('Password')"
                        />
                    </div>
                </div>
            </div>

            <div class="w-full form-button text-right">
                <label v-formlabel></label>
                <div class="mt-1 relative">
                    <ui-button
                        type="submit"
                        :color="'indigo-500'"
                    >
                        {{ __('Save Changes') }}
                    </ui-button>
                </div>
            </div>
        </form>
    </div>
</template>

<script>
import { __ } from '../translations/engine';

import { useAdminStore } from "../store";
import UiButton from "../../components/Button.vue";

export default {
    components: {
        UiButton,
    },

    data() {
        const store = useAdminStore();
        const current = store.user || {};
        return {
            user: {
                name: current.name || "",
                email: current.email || "",
                current_password: "",
                errors: {},
            },
        };
    },

    methods: {
        saveProfile() {
            const store = useAdminStore();
            const current = store.user || {};

            // Build the payload with only the fields that actually changed,
            // so untouched fields are never sent or updated server-side.
            const payload = {};
            if (this.user.name && this.user.name !== current.name) {
                payload.name = this.user.name;
            }
            if (this.user.email && this.user.email !== current.email) {
                payload.email = this.user.email;
            }
            if (this.user.password) {
                payload.password = this.user.password;
                payload.password_confirmation = this.user.password_confirmation;
            }
            if (payload.email || payload.password) {
                payload.current_password = this.user.current_password;
            }

            // Nothing changed: nothing to save.
            if (Object.keys(payload).length === 0) {
                this.$toast.info(__('Nothing to save.'));
                return;
            }

            const confirm = () => {
                axios.post("user/update_profile", payload).then(
                    (response) => {
                        this.$toast.success(__('Saved.'));
                        this.user.errors = {};
                        this.user.current_password = "";
                        this.user.password = "";
                        this.user.password_confirmation = "";

                        // Keep the sidebar / topbar user info in sync.
                        if (payload.name) {
                            store.user.name = payload.name;
                        }
                        if (payload.email) {
                            store.user.email = payload.email;
                        }
                    },
                    (error) => {
                        if (error.response && error.response.status == 422) {
                            this.user.errors =
                                (error.response.data && error.response.data.errors) || {};
                        }
                    }
                );
            };

            if (payload.email) {
                this.$swal
                    .fire({
                        title: __('Are you sure'),
                        text: __('you want to change your e-mail address'),
                    })
                    .then((result) => {
                        if (result.isConfirmed) {
                            confirm();
                        }
                    });
            } else {
                confirm();
            }
        },
    },

    created() {
        useAdminStore().setTopbarContent({ 
            page: 'profile',
            type: 'profile', 
            title: 'Profile',
            breadcrumb: [
                { name: 'Dashboard', url: '/', icon: 'fa fa-tachometer-alt' },
                { name: 'Profile', icon: 'fa fa-user' },
            ],
        });
    },
};
</script>
