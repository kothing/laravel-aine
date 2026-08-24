@extends('vendor.installer.layouts.master-update')

@section('title', trans('installer_messages.updater.overview.title'))

@section('container')
    <div class="text-center">
        <p class="text-slate-600">{{ trans_choice('installer_messages.updater.overview.message', $numberOfUpdatesPending, ['number' => $numberOfUpdatesPending]) }}</p>
        <div class="mt-8">
            <a href="{{ route('LaravelUpdater::database') }}" class="inline-flex items-center gap-2 rounded-lg bg-primary-600 px-5 py-2.5 text-sm font-bold text-white shadow-sm transition hover:bg-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2">
                {{ trans('installer_messages.updater.overview.install_updates') }}
            </a>
        </div>
    </div>
@stop
