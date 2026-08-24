@extends('vendor.installer.layouts.master')

@section('template_title')
    {{ trans('installer_messages.final.templateTitle') }}
@endsection

@section('title')
    {{ trans('installer_messages.final.title') }}
@endsection

@section('container')
    <div class="flex flex-col items-center text-center">
        <span class="flex h-14 w-14 items-center justify-center rounded-full bg-green-100 text-green-600">
            <svg class="h-8 w-8" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
        </span>
        <p class="mt-4 text-slate-600">{{ trans('installer_messages.final.finished') }}</p>
    </div>

    <div class="mt-6 space-y-4">
        @if (session('message')['dbOutputLog'] ?? null)
            <div>
                <p class="text-xs font-bold text-slate-500 uppercase tracking-wide">{{ trans('installer_messages.final.migration') }}</p>
                <pre class="mt-1 overflow-auto rounded-lg bg-slate-900 p-4 text-xs text-slate-100 ring-1 ring-slate-800"><code>{{ session('message')['dbOutputLog'] }}</code></pre>
            </div>
        @endif

        <div>
            <p class="text-xs font-bold text-slate-500 uppercase tracking-wide">{{ trans('installer_messages.final.console') }}</p>
            <pre class="mt-1 overflow-auto rounded-lg bg-slate-900 p-4 text-xs text-slate-100 ring-1 ring-slate-800"><code>{{ $finalMessages }}</code></pre>
        </div>

        <div>
            <p class="text-xs font-bold text-slate-500 uppercase tracking-wide">{{ trans('installer_messages.final.log') }}</p>
            <pre class="mt-1 overflow-auto rounded-lg bg-slate-900 p-4 text-xs text-slate-100 ring-1 ring-slate-800"><code>{{ $finalStatusMessage }}</code></pre>
        </div>

    </div>

    <div class="mt-6 flex justify-center">
        <a href="{{ url('/') }}" class="inline-flex items-center gap-2 rounded-lg bg-primary-600 px-5 py-2.5 text-sm font-bold text-white shadow-sm transition hover:bg-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2">
            {{ trans('installer_messages.final.exit') }}
        </a>
    </div>
@endsection
