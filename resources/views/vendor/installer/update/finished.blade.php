@extends('vendor.installer.layouts.master-update')

@section('title', trans('installer_messages.updater.final.title'))

@section('container')
    <div class="flex flex-col items-center text-center">
        <span class="flex h-14 w-14 items-center justify-center rounded-full bg-green-100 text-green-600">
            <svg class="h-8 w-8" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
        </span>
        <p class="mt-4 text-slate-600">{{ session('message')['message'] ?? '' }}</p>
        <div class="mt-8">
            <a href="{{ url('/') }}" class="inline-flex items-center gap-2 rounded-lg bg-primary-600 px-5 py-2.5 text-sm font-bold text-white shadow-sm transition hover:bg-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2">
                {{ trans('installer_messages.updater.final.exit') }}
            </a>
        </div>
    </div>
@stop
