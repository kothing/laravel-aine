@php
    $updaterSteps = [
        ['LaravelUpdater::welcome'],
        ['LaravelUpdater::overview'],
        ['LaravelUpdater::final'],
    ];
    $updaterLabels = [
        trans('installer_messages.updater.steps.welcome'),
        trans('installer_messages.updater.steps.overview'),
        trans('installer_messages.updater.steps.final'),
    ];
    $currentRoute = Route::currentRouteName();
    $currentStepIndex = 0;
    foreach ($updaterSteps as $index => $routes) {
        if (in_array($currentRoute, $routes)) {
            $currentStepIndex = $index;
            break;
        }
    }
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@if (trim($__env->yieldContent('template_title')))@yield('template_title') | @endif {{ trans('installer_messages.updater.title') }}</title>
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
                        <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0l3.181 3.183a8.25 8.25 0 0013.803-3.7M4.031 9.865a8.25 8.25 0 0113.803-3.7l3.181 3.182m0-4.991v4.99" />
                    </svg>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-sm ring-1 ring-slate-200 overflow-hidden">
                <div class="px-6 sm:px-8 py-5 border-b border-slate-200 bg-slate-50/70">
                    <h1 class="text-lg sm:text-xl font-bold text-slate-900">@yield('title')</h1>
                </div>

                <div class="px-6 sm:px-8 py-5 border-b border-slate-200">
                    <ol class="flex items-center">
                        @foreach ($updaterSteps as $index => $step)
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
                                            <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="3" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" /></svg>
                                        @else
                                            {{ $index + 1 }}
                                        @endif
                                    </span>
                                    <span class="mt-1.5 hidden sm:block text-[11px] font-semibold {{ $isActive ? 'text-primary-700' : 'text-slate-400' }}">{{ $updaterLabels[$index] }}</span>
                                </div>
                            </li>
                            @if ($index < count($updaterSteps) - 1)
                                <li class="flex-1 h-0.5 mx-1 sm:mx-2 rounded {{ $index < $currentStepIndex ? 'bg-primary-600' : 'bg-slate-200' }}"></li>
                            @endif
                        @endforeach
                    </ol>
                </div>

                <div class="px-6 sm:px-8 py-6 sm:py-8">
                    @yield('container')
                </div>
            </div>

            <p class="mt-6 text-center text-xs text-slate-400">{{ trans('installer_messages.updater.title') }}</p>
        </div>
    </div>
    @yield('scripts')
</body>
</html>
