<template>
    <div class="admin__profile relative h-full flex flex-col overflow-y-auto">
        <div class="w-full p-4 border-b bg-white">
            <div class="text-xl font-bold">{{ __('My Profile') }}</div>
        </div>
        
        <div class="w-full flex flex-col flex-1 an__xxl:w-3/4 m-auto p-4 overflow-y-auto">
            <div class="w-full bg-white rounded-md shadow-sm p-4">
                <form class="space-y-6" @submit.prevent="saveName()">
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
                    <div class="form-button">
                        <label v-formlabel></label>
                        <div class="mt-1 relative">
                            <ui-button
                                :color="'indigo-500'"
                                @click="saveName()"
                            >
                                {{ __('Update Name') }}
                            </ui-button>
                        </div>
                    </div>
                </form>
            </div>

            <div class="w-full bg-white rounded-md shadow-sm p-4 mt-5">
                <form class="space-y-6" @submit.prevent="saveEmail()">
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
                    <div class="form-item">
                        <label v-formlabel>{{ __('Current Password') }}</label>
                        <div class="mt-1 relative">
                            <input
                                type="password"
                                v-model="user.current_password"
                                v-forminput
                                :placeholder="__('Current password')"
                            />
                        </div>
                    </div>
                    <div class="form-button">
                        <label v-formlabel></label>
                        <div class="mt-1 relative">
                            <ui-button
                                :color="'indigo-500'"
                                @click="saveEmail()"
                            >
                                {{ __('Update E-mail') }}
                            </ui-button>
                        </div>
                    </div>
                </form>
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
                    </div>
                </div>
                <div class="form-item">
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
                <div class="mt-2">
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
                <div>
                    <label v-formlabel></label>
                    <div class="mt-1 relative">
                        <ui-button
                            :color="'indigo-500'"
                            @click="savePassword()"
                        >
                            {{ __('Update Password') }}
                        </ui-button>
                    </div>
                </div>
            </div>
        </div>
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
        return {
            user: {
                name: store.user.name,
                email: store.user.email,
                current_password: "",
                errors: {},
            },
        };
    },

    methods: {
        saveName() {
            axios.post("user/update_name", this.user).then(
                (response) => {
                    this.$toast.success(__('Saved.'));
                    this.user.errors = {};
                },
                (error) => {
                    if (error.response && error.response.status == 422) {
                        this.user.errors = error.response.data.errors;
                    }
                }
            );
        },

        saveEmail() {
            const store = useAdminStore();
            if (this.user.email !== store.user.email) {
                this.$swal
                    .fire({
                        title: __('Are you sure'),
                        text: __('you want to change your e-mail address'),
                    })
                    .then((result) => {
                        if (result.isConfirmed) {
                            axios
                                .post("user/update_email", this.user)
                                .then(
                                    (response) => {
                                        this.$toast.success(__('Saved.'));
                                        this.user.errors = {};
                                        this.user.current_password = "";
                                    },
                                    (error) => {
                                        if (error.response && error.response.status == 422) {
                                            this.user.errors =
                                                error.response.data.errors;
                                        }
                                    }
                                );
                        }
                    });
            } else {
                axios.post("user/update_email", this.user).then(
                    (response) => {
                        this.$toast.success(__('Saved.'));
                        this.user.errors = {};
                        this.user.current_password = "";
                    },
                    (error) => {
                        if (error.response && error.response.status == 422) {
                            this.user.errors = error.response.data.errors;
                        }
                    }
                );
            }
        },

        savePassword() {
            axios.post("user/update_password", this.user).then(
                (response) => {
                    this.$toast.success(__('Saved.'));
                    this.user.errors = {};
                    this.user.password = "";
                    this.user.password_confirmation = "";
                    this.user.current_password = "";
                },
                (error) => {
                    if (error.response && error.response.status == 422) {
                        this.user.errors = error.response.data.errors;
                    }
                }
            );
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
