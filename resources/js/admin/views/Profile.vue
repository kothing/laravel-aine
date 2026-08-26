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

            <div class="w-full bg-white rounded-md shadow-sm p-4 mt-5 mb-5">
                <div class="form-item">
                    <label v-formlabel>{{ __('Two-Factor Authentication') }}</label>

                    <div v-if="!twoFactorEnabled" class="mt-2">
                        <p class="text-sm text-gray-600">
                            {{ __('Add an extra layer of security to your account by requiring an authentication code in addition to your password.') }}
                        </p>
                        <ui-button
                            type="button"
                            :color="'indigo-500'"
                            class="mt-3"
                            @click="enableTwoFactor()"
                        >
                            {{ __('Enable 2FA') }}
                        </ui-button>
                    </div>

                    <div v-else class="mt-2">
                        <div class="flex items-center space-x-2">
                            <i class="fas fa-check-circle text-green-500"></i>
                            <span class="text-sm font-semibold text-green-700">
                                {{ __('Two-factor authentication is enabled.') }}
                            </span>
                        </div>
                        <div class="flex space-x-2 mt-3">
                            <ui-button
                                type="button"
                                color="gray-200"
                                hover="gray-300"
                                @click="showRecoveryCodes()"
                            >
                                <i class="fa fa-key"></i> {{ __('Recovery Codes') }}
                            </ui-button>
                            <ui-button
                                type="button"
                                color="red-100"
                                hover="red-200"
                                @click="openDisableModal()"
                            >
                                <i class="fa fa-ban"></i> {{ __('Disable 2FA') }}
                            </ui-button>
                        </div>
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

        <ui-modal
            :show="enableModal"
            @close="enableModal = false"
            maxWidth="md"
        >
            <template #content>
                <div class="mt-4">
                    <h3 class="text-lg font-bold mb-1">
                        {{ __('Two-Factor Authentication Setup') }}
                    </h3>
                    <p class="text-sm text-gray-600 mb-4">
                        {{ __('Scan the QR code with your authenticator app (e.g. Google Authenticator, Authy) or enter the secret manually.') }}
                    </p>

                    <div class="flex justify-center mb-4">
                        <img
                            v-if="qrDataUrl"
                            :src="qrDataUrl"
                            class="w-48 h-48 border border-gray-200 rounded-md"
                            alt="QR Code"
                        />
                    </div>

                    <div class="bg-gray-50 border border-gray-200 rounded-md p-3 mb-4">
                        <div class="flex justify-between items-center">
                            <span class="text-xs text-gray-500 font-mono">{{ twoFactorSecret }}</span>
                            <button
                                type="button"
                                class="text-indigo-500 text-xs font-semibold hover:underline"
                                @click="copySecret"
                            >
                                {{ __('Copy') }}
                            </button>
                        </div>
                    </div>

                    <label class="text-sm text-gray-700">{{ __('Enter the 6-digit code from your app') }}</label>
                    <input
                        type="text"
                        v-model="confirmCode"
                        v-forminput
                        maxlength="6"
                        class="mt-1"
                        :placeholder="__('123456')"
                    />
                    <p
                        v-if="twoFactorError"
                        class="text-sm text-red-600 mt-1"
                    >
                        {{ twoFactorError }}
                    </p>
                </div>
            </template>

            <template #footer>
                <div class="flex space-x-2">
                    <ui-button color="gray-200" hover="gray-300" @click="enableModal = false">
                        <span class="text-gray-800">{{ __('Cancel') }}</span>
                    </ui-button>
                    <ui-button color="indigo-500" @click="confirmTwoFactor()" :loading="twoFactorBusy">
                        {{ __('Confirm') }}
                    </ui-button>
                </div>
            </template>
        </ui-modal>

        <ui-modal
            :show="recoveryModal"
            @close="recoveryModal = false"
            maxWidth="md"
        >
            <template #content>
                <div class="mt-4">
                    <h3 class="text-lg font-bold mb-1">
                        {{ __('Recovery Codes') }}
                    </h3>
                    <p class="text-sm text-gray-600 mb-4">
                        {{ __('Store these codes somewhere safe. Each code can only be used once.') }}
                    </p>

                    <div class="grid grid-cols-2 gap-2">
                        <div
                            v-for="(code, index) in recoveryCodes"
                            :key="index"
                            class="bg-gray-50 border border-gray-200 rounded-md p-2 text-center font-mono text-sm"
                        >
                            {{ code }}
                        </div>
                    </div>
                </div>
            </template>

            <template #footer>
                <div class="flex space-x-2">
                    <ui-button color="gray-200" hover="gray-300" @click="recoveryModal = false">
                        <span class="text-gray-800">{{ __('Close') }}</span>
                    </ui-button>
                    <ui-button color="indigo-500" @click="regenerateRecoveryCodes()">
                        {{ __('Regenerate') }}
                    </ui-button>
                </div>
            </template>
        </ui-modal>

        <ui-modal
            :show="disableModal"
            @close="disableModal = false"
            maxWidth="sm"
        >
            <template #content>
                <div class="mt-4">
                    <h3 class="text-lg font-bold mb-1">
                        {{ __('Disable Two-Factor Authentication') }}
                    </h3>
                    <p class="text-sm text-gray-600 mb-4">
                        {{ __('Enter your password to confirm disabling 2FA.') }}
                    </p>

                    <input
                        type="password"
                        v-model="disablePassword"
                        v-forminput
                        class="mt-1"
                        :placeholder="__('Current password')"
                    />
                    <p
                        v-if="twoFactorError"
                        class="text-sm text-red-600 mt-1"
                    >
                        {{ twoFactorError }}
                    </p>
                </div>
            </template>

            <template #footer>
                <div class="flex space-x-2">
                    <ui-button color="gray-200" hover="gray-300" @click="disableModal = false">
                        <span class="text-gray-800">{{ __('Cancel') }}</span>
                    </ui-button>
                    <ui-button color="red-500" @click="disableTwoFactor()" :loading="twoFactorBusy">
                        {{ __('Disable') }}
                    </ui-button>
                </div>
            </template>
        </ui-modal>
    </div>
</template>

<script>
import { __ } from '../translations/engine';
import QRCode from "qrcode";

import { useAdminStore } from "../store";
import UiButton from "../../components/Button.vue";
import UiModal from "../../components/Modal.vue";

export default {
    components: {
        UiButton,
        UiModal,
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
            twoFactorEnabled: !!current.two_factor_enabled,
            enableModal: false,
            twoFactorBusy: false,
            twoFactorError: "",
            qrDataUrl: "",
            twoFactorSecret: "",
            confirmCode: "",
            recoveryModal: false,
            recoveryCodes: [],
            disableModal: false,
            disablePassword: "",
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

        async enableTwoFactor() {
            this.twoFactorBusy = true;
            this.twoFactorError = "";
            try {
                const { data } = await axios.post("user/2fa/enable");
                this.twoFactorSecret = data.secret;
                this.qrDataUrl = await QRCode.toDataURL(data.provisioning_uri, {
                    width: 400,
                    margin: 1,
                });
                this.confirmCode = "";
                this.enableModal = true;
            } catch (error) {
                this.twoFactorError =
                    (error.response && error.response.data && error.response.data.message) ||
                    __("Failed to enable two factor authentication.");
            } finally {
                this.twoFactorBusy = false;
            }
        },

        async confirmTwoFactor() {
            if (!this.confirmCode) return;
            this.twoFactorBusy = true;
            this.twoFactorError = "";
            try {
                const { data } = await axios.post("user/2fa/confirm", {
                    code: this.confirmCode,
                });
                this.enableModal = false;
                this.recoveryCodes = data.recovery_codes;
                this.recoveryModal = true;
                this.twoFactorEnabled = true;
                useAdminStore().user.two_factor_enabled = true;
                this.$toast.success(__("Two factor authentication enabled."));
            } catch (error) {
                this.twoFactorError =
                    (error.response && error.response.data && error.response.data.message) ||
                    __("The provided code was invalid.");
            } finally {
                this.twoFactorBusy = false;
            }
        },

        async showRecoveryCodes() {
            this.twoFactorError = "";
            try {
                const { data } = await axios.post("user/2fa/recovery-codes");
                this.recoveryCodes = data.recovery_codes;
                this.recoveryModal = true;
            } catch (error) {
                this.$toast.error(
                    (error.response && error.response.data && error.response.data.message) ||
                        __("Failed to load recovery codes.")
                );
            }
        },

        async regenerateRecoveryCodes() {
            try {
                const { data } = await axios.post("user/2fa/recovery-codes");
                this.recoveryCodes = data.recovery_codes;
                this.$toast.success(__("Recovery codes regenerated."));
            } catch (error) {
                this.$toast.error(__("Failed to regenerate recovery codes."));
            }
        },

        openDisableModal() {
            this.twoFactorError = "";
            this.disablePassword = "";
            this.disableModal = true;
        },

        async disableTwoFactor() {
            if (!this.disablePassword) return;
            this.twoFactorBusy = true;
            this.twoFactorError = "";
            try {
                await axios.post("user/2fa/disable", {
                    password: this.disablePassword,
                });
                this.disableModal = false;
                this.disablePassword = "";
                this.twoFactorEnabled = false;
                useAdminStore().user.two_factor_enabled = false;
                this.$toast.success(__("Two factor authentication disabled."));
            } catch (error) {
                this.twoFactorError =
                    (error.response && error.response.data && error.response.data.message) ||
                    __("The provided password is incorrect.");
            } finally {
                this.twoFactorBusy = false;
            }
        },

        async copySecret() {
            try {
                await navigator.clipboard.writeText(this.twoFactorSecret);
                this.$toast.success(__("Secret copied."));
            } catch (e) {
                this.$toast.error(__("Could not copy the secret."));
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
