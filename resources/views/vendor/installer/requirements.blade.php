@extends('vendor.installer.layouts.master')

@section('template_title')
    {{ trans('installer_messages.requirements.templateTitle') }}
@endsection

@section('title')
    {{ trans('installer_messages.requirements.title') }}
@endsection

@section('container')
    @foreach ($requirements['requirements'] as $type => $requirement)
        <div class="overflow-hidden rounded-lg ring-1 ring-slate-200 mb-5">
            <div class="flex items-center justify-between px-4 py-3 {{ $phpSupportInfo['supported'] ? 'bg-green-50' : 'bg-red-50' }}">
                <div class="flex items-center gap-2">
                    <span class="text-sm font-bold text-slate-900">{{ $type === 'php' ? 'PHP' : ucfirst($type) }}</span>
                    @if ($type == 'php')
                        <span class="text-xs text-slate-500">(version {{ $phpSupportInfo['minimum'] }} required)</span>
                    @endif
                </div>
                @if ($type == 'php')
                    <div class="flex items-center gap-2 text-sm font-semibold text-slate-700">
                        <span>{{ $phpSupportInfo['current'] }}</span>
                        @if ($phpSupportInfo['supported'])
                            <svg class="h-5 w-5 text-green-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                        @else
                            <svg class="h-5 w-5 text-red-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z" /></svg>
                        @endif
                    </div>
                @endif
            </div>
            <ul class="divide-y divide-slate-100 bg-white">
                @foreach ($requirements['requirements'][$type] as $extension => $enabled)
                    <li class="flex items-center justify-between px-4 py-2.5 text-sm">
                        <span class="text-slate-700">{{ $extension }}</span>
                        @if ($enabled)
                            <svg class="h-5 w-5 text-green-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                        @else
                            <svg class="h-5 w-5 text-red-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z" /></svg>
                        @endif
                    </li>
                @endforeach
            </ul>
        </div>
    @endforeach

    @if (! isset($requirements['errors']) && $phpSupportInfo['supported'])
        <div class="mt-6 flex items-center justify-between">
            <a href="{{ route('LaravelInstaller::welcome') }}" class="inline-flex items-center gap-2 rounded-lg border border-slate-200 bg-white px-5 py-2.5 text-sm font-semibold text-slate-600 shadow-sm transition hover:bg-slate-50">
                <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18" /></svg>
                {{ trans('installer_messages.back') }}
            </a>
            <a class="inline-flex items-center gap-2 rounded-lg bg-primary-600 px-5 py-2.5 text-sm font-bold text-white shadow-sm transition hover:bg-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2" href="{{ route('LaravelInstaller::permissions') }}">
                {{ trans('installer_messages.requirements.next') }}
                <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3" /></svg>
            </a>
        </div>
    @endif
@endsection
