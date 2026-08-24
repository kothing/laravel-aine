<x-auth-layout>
    <x-auth-card>
        <x-slot name="logo">
            <a href="/" class="block">
                <x-app-logo class="w-20 h-20 fill-current text-gray-500" />                
            </a>
        </x-slot>

        <x-auth-session-status class="mb-4" :status="session('status')" />

        <x-auth-validation-errors class="mb-4" :errors="$errors" />

        <form method="POST" action="{{ route('login') }}">
            @csrf

            <div class="block">
                <x-label for="email" :value="__('Email')" />
                <x-input 
                    id="email" 
                    class="block w-full mt-1" 
                    type="email" 
                    name="email" 
                    placeholder="Email"
                    required 
                    autofocus
                />
            </div>

            <div class="block mt-4">
                <x-label for="password" :value="__('Password')" />
                <x-input 
                    id="password"
                    class="block w-full mt-1"
                    type="password"
                    name="password"
                    placeholder="Password"
                    required 
                    autocomplete="current-password"
                />
            </div>

            <div class="block mt-4">
                <label for="remember_me" class="inline-flex items-center">
                    <input 
                        id="remember_me" 
                        type="checkbox" 
                        class="border-gray-300 text-indigo-600 rounded-md shadow-sm cursor-pointer focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50" 
                        name="remember"
                    >
                    <span class="ml-2 text-sm text-gray-600">{{ __('Remember me') }}</span>
                </label>
            </div>

            <div class="flex items-center justify-end mt-4">
                @if (Route::has('password.request'))
                    <a class="underline text-sm text-gray-600 hover:text-gray-900" href="{{ route('password.request') }}">
                        {{ __('Forgot your password?') }}
                    </a>
                @endif

                <x-button class="ml-3 bg-indigo-500 hover:bg-indigo-600 active:bg-indigo-400">
                    {{ __('Log in') }}
                </x-button>
            </div>
        </form>
    </x-auth-card>
</x-auth-layout>
