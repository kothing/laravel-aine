@php
    $installerSteps = [
        ['welcome', ['LaravelInstaller::welcome']],
        ['requirements', ['LaravelInstaller::requirements']],
        ['permissions', ['LaravelInstaller::permissions']],
        ['environment', ['LaravelInstaller::environment', 'LaravelInstaller::environmentWizard', 'LaravelInstaller::environmentClassic']],
        ['confirm', ['LaravelInstaller::confirm']],
        ['database', ['LaravelInstaller::database']],
        ['final', ['LaravelInstaller::final']],
    ];
    $currentRoute = Route::currentRouteName();
    $currentStepIndex = 0;
    foreach ($installerSteps as $index => $step) {
        if (in_array($currentRoute, $step[1])) {
            $currentStepIndex = $index;
            break;
        }
    }
    $stepLabels = [
        trans('installer_messages.steps.welcome'),
        trans('installer_messages.steps.requirements'),
        trans('installer_messages.steps.permissions'),
        trans('installer_messages.steps.environment'),
        trans('installer_messages.steps.confirm'),
        trans('installer_messages.steps.database'),
        trans('installer_messages.steps.final'),
    ];
    $installerLocales = config('installer.locales', ['en' => 'English']);
    $installerCurrentLocaleLabel = $installerLocales[app()->getLocale()] ?? 'English';
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@if (trim($__env->yieldContent('template_title')))@yield('template_title') | @endif {{ trans('installer_messages.title') }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Nunito+Sans:wght@400;600;700;800&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: {
                            50: '#f0f9ff', 100: '#e0f2fe', 200: '#bae6fd', 300: '#7dd3fc',
                            400: '#38bdf8', 500: '#0ea5e9', 600: '#0284c7', 700: '#0369a1',
                            800: '#075985', 900: '#0c4a6e'
                        }
                    },
                    fontFamily: {
                        sans: ['"Nunito Sans"', 'ui-sans-serif', 'system-ui', 'sans-serif']
                    }
                }
            }
        }
    </script>
    <link rel="icon" type="image/png" href="{{ asset('installer/img/favicon/favicon-32x32.png') }}" sizes="32x32"/>
    @yield('style')
</head>
<body class="min-h-screen bg-slate-100 font-sans text-slate-700 antialiased">
    <div class="min-h-screen flex flex-col items-center justify-center px-4 py-10">
        <div class="w-full max-w-3xl">

            <div class="flex flex-col items-center mb-8">
                <div class="h-12 w-12 rounded-xl bg-primary-600 flex items-center justify-center shadow-sm ring-1 ring-primary-700/20">
                    <svg class="h-7 w-7 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 7.5l3 2.25-3 2.25m4.5 0h3m-9 8.25h13.5A2.25 2.25 0 0021 18V6a2.25 2.25 0 00-2.25-2.25H5.25A2.25 2.25 0 003 6v12a2.25 2.25 0 002.25 2.25z" />
                    </svg>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-sm ring-1 ring-slate-200 overflow-hidden">
                <div class="px-6 sm:px-8 py-5 border-b border-slate-200 bg-slate-50/70 flex items-center justify-between gap-4">
                    <h1 class="text-lg sm:text-xl font-bold text-slate-900">@yield('title')</h1>
                    <div class="flex items-center">
                        {{-- Custom language dropdown: renders its own option panel so
                             it never overlaps the trigger (native selects can't be styled). --}}
                        <div class="relative" id="installer-lang">
                            <button
                                type="button"
                                id="installer-lang-trigger"
                                class="flex cursor-pointer items-center gap-1 rounded-md border border-slate-300 bg-white py-1 pl-2 pr-1.5 text-xs font-semibold text-slate-700 shadow-sm transition hover:border-slate-400 focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/30"
                            >
                                <span>{{ $installerCurrentLocaleLabel }}</span>
                                <svg id="installer-lang-chevron" class="h-3 w-3 text-slate-400 transition-transform duration-150" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" /></svg>
                            </button>

                            <div id="installer-lang-menu" class="absolute right-0 top-full z-30 mt-1 hidden w-28 overflow-hidden rounded-md border border-slate-200 bg-white py-0.5 shadow-lg">
                                @foreach (config('installer.locales', ['en' => 'English']) as $code => $label)
                                    <button
                                        type="button"
                                        data-locale="{{ $code }}"
                                        class="flex w-full cursor-pointer items-center gap-1 px-2 py-1.5 text-left text-xs transition hover:bg-slate-50 {{ app()->getLocale() === $code ? 'font-semibold text-primary-600' : 'text-slate-700' }}"
                                    >
                                        <span class="inline-block w-3.5 text-[10px]">{{ app()->getLocale() === $code ? '✓' : '' }}</span>
                                        {{ $label }}
                                    </button>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>

                <div class="px-6 sm:px-8 py-5 border-b border-slate-200">
                    <ol class="flex items-center">
                        @foreach ($installerSteps as $index => $step)
                            @php
                                $isCompleted = $index < $currentStepIndex;
                                $isActive = $index === $currentStepIndex;
                                $circleClass = $isActive
                                    ? 'bg-primary-600 text-white ring-4 ring-primary-100'
                                    : ($isCompleted
                                        ? 'bg-primary-600 text-white'
                                        : 'bg-slate-100 text-slate-400 ring-1 ring-slate-200');
                            @endphp
                            <li class="flex items-center">
                                <div class="flex flex-col items-center">
                                    <span class="flex h-9 w-9 items-center justify-center rounded-full text-xs font-bold transition {{ $circleClass }}">
                                        @if ($isCompleted)
                                            <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="3" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                                            </svg>
                                        @else
                                            {{ $index + 1 }}
                                        @endif
                                    </span>
                                    <span class="mt-1.5 hidden sm:block text-[11px] font-semibold {{ $isActive ? 'text-primary-700' : 'text-slate-400' }}">{{ $stepLabels[$index] }}</span>
                                </div>
                            </li>
                            @if ($index < count($installerSteps) - 1)
                                <li class="flex-1 h-0.5 mx-1 sm:mx-2 rounded {{ $index < $currentStepIndex ? 'bg-primary-600' : 'bg-slate-200' }}"></li>
                            @endif
                        @endforeach
                    </ol>
                </div>

                <div class="px-6 sm:px-8 py-6 sm:py-8">
                    @if (session('message'))
                        <div class="mb-6 rounded-lg bg-primary-50 border border-primary-200 px-4 py-3 text-sm text-primary-800">
                            <strong>
                                @if (is_array(session('message')))
                                    {{ session('message')['message'] ?? '' }}
                                @else
                                    {{ session('message') }}
                                @endif
                            </strong>
                        </div>
                    @endif
                    @if (session()->has('errors'))
                        <div class="mb-6 rounded-lg bg-red-50 border border-red-200 px-4 py-3" id="error_alert">
                            <div class="flex items-start gap-3">
                                <svg class="h-5 w-5 flex-none text-red-400 mt-0.5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
                                </svg>
                                <div class="flex-1">
                                    <p class="font-semibold text-red-800">{{ trans('installer_messages.forms.errorTitle') }}</p>
                                    <ul class="mt-1 list-disc list-inside text-red-700">
                                        @foreach ($errors->all() as $error)
                                            <li>{{ $error }}</li>
                                        @endforeach
                                    </ul>
                                </div>
                                <button type="button" id="close_alert" class="text-red-400 hover:text-red-600" aria-label="Close">
                                    <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                                    </svg>
                                </button>
                            </div>
                        </div>
                    @endif
                    @yield('container')
                </div>
            </div>

            <p class="mt-6 text-center text-xs text-slate-400">{{ trans('installer_messages.title') }}</p>
        </div>
    </div>
    <script>
        (function () {
            var wrap = document.getElementById('installer-lang');
            var trigger = document.getElementById('installer-lang-trigger');
            var menu = document.getElementById('installer-lang-menu');
            var chevron = document.getElementById('installer-lang-chevron');
            if (!wrap || !trigger || !menu) return;

            trigger.addEventListener('click', function (e) {
                e.stopPropagation();
                var open = menu.classList.toggle('hidden') === false;
                if (chevron) chevron.style.transform = open ? 'rotate(180deg)' : '';
            });

            document.addEventListener('click', function (e) {
                if (!wrap.contains(e.target)) {
                    menu.classList.add('hidden');
                    if (chevron) chevron.style.transform = '';
                }
            });

            menu.querySelectorAll('[data-locale]').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    window.location = '?locale=' + encodeURIComponent(btn.dataset.locale);
                });
            });
        })();
    </script>
    @yield('scripts')
    @if (session()->has('errors'))
        <script>
            (function () {
                var alert = document.getElementById('error_alert');
                var btn = document.getElementById('close_alert');
                if (alert && btn) {
                    btn.onclick = function () { alert.style.display = 'none'; };
                }
            })();
        </script>
    @endif
</body>
</html>
