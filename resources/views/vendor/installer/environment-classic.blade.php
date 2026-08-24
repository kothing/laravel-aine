@extends('vendor.installer.layouts.master')

@section('template_title')
    {{ trans('installer_messages.environment.classic.templateTitle') }}
@endsection

@section('title')
    {{ trans('installer_messages.environment.classic.title') }}
@endsection

@section('container')
    <form method="post" action="{{ route('LaravelInstaller::environmentSaveClassic') }}">
        @csrf
        <textarea name="envConfig" class="block w-full rounded-lg ring-1 ring-slate-300 font-mono text-xs text-slate-800 bg-slate-50 p-4 focus:ring-2 focus:ring-primary-500 focus:outline-none" rows="18">{{ $envConfig }}</textarea>
        <div class="mt-4 flex justify-end">
            <button type="submit" class="inline-flex items-center gap-2 rounded-lg bg-primary-600 px-5 py-2.5 text-sm font-bold text-white shadow-sm transition hover:bg-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2">
                <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M16.5 3.75V16.5L12 21l-4.5-4.5V3.75m9 0H7.5m9 0H21M7.5 3.75H3" /></svg>
                {!! trans('installer_messages.environment.classic.save') !!}
            </button>
        </div>
    </form>

    @if (! isset($environment['errors']))
        <div class="mt-6 flex items-center justify-between">
            <a class="inline-flex items-center gap-2 rounded-lg bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 ring-1 ring-slate-300 transition hover:bg-slate-50" href="{{ route('LaravelInstaller::environmentWizard') }}">
                <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18" /></svg>
                {!! trans('installer_messages.environment.classic.back') !!}
            </a>
            <a class="inline-flex items-center gap-2 rounded-lg bg-primary-600 px-5 py-2.5 text-sm font-bold text-white shadow-sm transition hover:bg-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2" href="{{ route('LaravelInstaller::database') }}">
                {!! trans('installer_messages.environment.classic.install') !!}
                <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3" /></svg>
            </a>
        </div>
    @endif
@endsection
