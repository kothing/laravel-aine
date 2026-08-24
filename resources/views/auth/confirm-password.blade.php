<x-auth-layout>
    <x-auth-card>
        <x-slot name="logo">
            <a href="/">
                <x-app-logo class="w-20 h-20 fill-current text-gray-500" />
            </a>
        </x-slot>

        <div class="mb-4 text-sm text-gray-600">
            {{ __('This is a secure area of the application. Please confirm your password before continuing.') }}
        </div>

        <x-auth-validation-errors class="mb-4" :errors="$errors" />

        <form method="POST" action="{{ route('password.confirm') }}">
            @csrf

            <div class="block">
                <x-label for="password" :value="__('Password')" />

                <x-input 
                    id="password" 
                    class="block mt-1 w-full"
                    type="password"
                    name="password"
                    required autocomplete="current-password"
                />
            </div>

            <div class="flex justify-end mt-4">
                <x-button class="bg-indigo-500 hover:bg-indigo-600 active:bg-indigo-400">
                    {{ __('Confirm') }}
                </x-button>
            </div>
        </form>
    </x-auth-card>
</x-auth-layout>
