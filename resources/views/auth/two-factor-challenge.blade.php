<x-auth-layout>
    <x-auth-card>
        <x-slot name="logo">
            <a href="/" class="block">
                <x-app-logo class="w-20 h-20 fill-current text-gray-500" />
            </a>
        </x-slot>

        <x-auth-session-status class="mb-4" :status="session('status')" />

        <x-auth-validation-errors class="mb-4" :errors="$errors" />

        <div class="mb-4 text-sm text-gray-600">
            {{ __('Two factor authentication is enabled on your account. Enter the code from your authenticator app to continue, or use a recovery code.') }}
        </div>

        <form method="POST" action="{{ route('two-factor.challenge') }}">
            @csrf

            <div class="block">
                <x-label for="code" :value="__('Code')" />
                <x-input
                    id="code"
                    class="block w-full mt-1"
                    type="text"
                    name="code"
                    placeholder="123456"
                    maxlength="6"
                    autofocus
                    autocomplete="one-time-code"
                />
            </div>

            <div class="block mt-4">
                <x-label for="recovery_code" :value="__('Recovery code')" />
                <x-input
                    id="recovery_code"
                    class="block w-full mt-1"
                    type="text"
                    name="recovery_code"
                    placeholder="XXXX-XXXX-XXXX"
                    autocomplete="off"
                />
            </div>

            <div class="flex items-center justify-end mt-4">
                <x-button class="ml-3 bg-indigo-500 hover:bg-indigo-600 active:bg-indigo-400">
                    {{ __('Verify') }}
                </x-button>
            </div>
        </form>
    </x-auth-card>
</x-auth-layout>
