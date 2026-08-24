<x-auth-layout>
    <x-auth-card>
        <x-slot name="logo">
            <a href="/">
                <x-app-logo class="w-20 h-20 fill-current text-gray-500" />
            </a>
        </x-slot>

        <div class="mb-4 text-sm text-gray-600">
            {{ __('Forgot your password? No problem. Just let us know your email address and we will email you a password reset link that will allow you to choose a new one.') }}
        </div>

        <x-auth-session-status class="mb-4" :status="session('status')" />

        <x-auth-validation-errors class="mb-4" :errors="$errors" />

        <form method="POST" action="{{ route('password.email') }}">
            @csrf

            <div class="block">
                <x-label for="email" :value="__('Email')" />
                <x-input 
                    id="email" 
                    class="block mt-1 w-full" 
                    type="email" 
                    name="email" 
                    :value="old('email')" 
                    required 
                    autofocus 
                />
            </div>

            <div class="flex items-center justify-end mt-4">
                <x-button class="bg-indigo-500 hover:bg-indigo-600 active:bg-indigo-400">
                    {{ __('Email Password Reset Link') }}
                </x-button>
            </div>
        </form>
    </x-auth-card>
</x-auth-layout>
