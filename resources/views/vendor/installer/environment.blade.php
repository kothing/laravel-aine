@extends('vendor.installer.layouts.master')

@section('template_title')
    {{ trans('installer_messages.environment.menu.templateTitle') }}
@endsection

@section('title')
    {!! trans('installer_messages.environment.menu.title') !!}
@endsection

@section('container')
    <p class="text-center text-slate-600">{!! trans('installer_messages.environment.menu.desc') !!}</p>

    <div class="mt-8 grid gap-4 sm:grid-cols-2">
        <a href="{{ route('LaravelInstaller::environmentWizard') }}" class="group flex flex-col items-center rounded-lg ring-1 ring-slate-200 bg-white px-6 py-8 text-center transition hover:ring-primary-300 hover:shadow-sm">
            <span class="flex h-12 w-12 items-center justify-center rounded-lg bg-primary-50 text-primary-600 transition group-hover:bg-primary-100">
                <svg class="h-6 w-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6A2.25 2.25 0 016 3.75h2.25A2.25 2.25 0 0110.5 6v2.25a2.25 2.25 0 01-2.25 2.25H6a2.25 2.25 0 01-2.25-2.25V6zM3.75 15.75A2.25 2.25 0 016 13.5h2.25a2.25 2.25 0 012.25 2.25V18a2.25 2.25 0 01-2.25 2.25H6A2.25 2.25 0 013.75 18v-2.25zM13.5 6a2.25 2.25 0 012.25-2.25H18A2.25 2.25 0 0120.25 6v2.25A2.25 2.25 0 0118 10.5h-2.25a2.25 2.25 0 01-2.25-2.25V6zM13.5 15.75a2.25 2.25 0 012.25-2.25H18a2.25 2.25 0 012.25 2.25V18A2.25 2.25 0 0118 20.25h-2.25A2.25 2.25 0 0113.5 18v-2.25z" /></svg>
            </span>
            <span class="mt-4 text-sm font-bold text-slate-900">{{ trans('installer_messages.environment.menu.wizard-button') }}</span>
        </a>

        <a href="{{ route('LaravelInstaller::environmentClassic') }}" class="group flex flex-col items-center rounded-lg ring-1 ring-slate-200 bg-white px-6 py-8 text-center transition hover:ring-primary-300 hover:shadow-sm">
            <span class="flex h-12 w-12 items-center justify-center rounded-lg bg-slate-100 text-slate-600 transition group-hover:bg-slate-200">
                <svg class="h-6 w-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M17.25 6.75L22.5 12l-5.25 5.25m-10.5 0L1.5 12l5.25-5.25m7.5-3l-4.5 16.5" /></svg>
            </span>
            <span class="mt-4 text-sm font-bold text-slate-900">{{ trans('installer_messages.environment.menu.classic-button') }}</span>
        </a>
    </div>

    <div class="mt-6 flex justify-start">
        <a href="{{ route('LaravelInstaller::permissions') }}" class="inline-flex items-center gap-2 rounded-lg border border-slate-200 bg-white px-5 py-2.5 text-sm font-semibold text-slate-600 shadow-sm transition hover:bg-slate-50">
            <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18" /></svg>
            {{ trans('installer_messages.back') }}
        </a>
    </div>
@endsection
