@extends('vendor.installer.layouts.master')

@section('template_title')
    {{ trans('installer_messages.confirm.templateTitle') }}
@endsection

@section('title')
    {{ trans('installer_messages.confirm.title') }}
@endsection

@section('container')
    <p class="text-center text-slate-600">{{ trans('installer_messages.confirm.intro') }}</p>

    <div class="mt-6 overflow-hidden rounded-lg ring-1 ring-slate-200 bg-white">
        <dl class="divide-y divide-slate-100 text-sm">
            <div class="flex items-center justify-between px-4 py-3">
                <dt class="font-semibold text-slate-500">{{ trans('installer_messages.confirm.app_name') }}</dt>
                <dd class="font-medium text-slate-900">{{ $summary['app_name'] ?? '-' }}</dd>
            </div>
            <div class="flex items-center justify-between px-4 py-3">
                <dt class="font-semibold text-slate-500">{{ trans('installer_messages.confirm.app_environment') }}</dt>
                <dd class="font-medium text-slate-900">{{ $summary['environment'] ?? '-' }}</dd>
            </div>
            <div class="flex items-center justify-between px-4 py-3">
                <dt class="font-semibold text-slate-500">{{ trans('installer_messages.confirm.app_url') }}</dt>
                <dd class="font-medium text-slate-900">{{ $summary['app_url'] ?? '-' }}</dd>
            </div>
            <div class="flex items-center justify-between px-4 py-3">
                <dt class="font-semibold text-slate-500">{{ trans('installer_messages.confirm.database') }}</dt>
                <dd class="font-medium text-slate-900">
                    {{ $summary['database_connection'] ?? '-' }}
                    @if (! empty($summary['database_name']))
                        <span class="ml-1 text-slate-400">({{ $summary['database_name'] }})</span>
                    @endif
                </dd>
            </div>
            <div class="flex items-center justify-between px-4 py-3">
                <dt class="font-semibold text-slate-500">{{ trans('installer_messages.confirm.admin_email') }}</dt>
                <dd class="font-medium text-slate-900">{{ $summary['admin_email'] ?? '-' }}</dd>
            </div>
        </dl>
    </div>

    @if (empty($summary['admin_email']))
        <div class="mt-6 rounded-lg bg-amber-50 border border-amber-200 px-4 py-3 text-sm text-amber-800">
            {{ trans('installer_messages.confirm.admin_email_missing') }}
        </div>
    @endif

    <div class="mt-6 flex items-center justify-between">
        <a href="{{ route('LaravelInstaller::environmentWizard') }}" id="installer-back-btn" class="inline-flex items-center gap-2 rounded-lg border border-slate-200 bg-white px-5 py-2.5 text-sm font-semibold text-slate-600 shadow-sm transition hover:bg-slate-50">
            <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18" /></svg>
            {{ trans('installer_messages.confirm.back') }}
        </a>
        <a href="{{ route('LaravelInstaller::database') }}" id="installer-install-btn" data-install-loading="{{ trans('installer_messages.confirm.install_loading') }}" class="inline-flex items-center gap-2 rounded-lg bg-green-600 px-5 py-2.5 text-sm font-bold text-white shadow-sm transition hover:bg-green-500 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2">
            <svg id="installer-spinner" class="h-4 w-4 hidden animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path></svg>
            <span id="installer-install-label">{{ trans('installer_messages.confirm.install') }}</span>
            <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3" /></svg>
        </a>
    </div>
@endsection

@section('scripts')
    <script>
        (function () {
            var installBtn = document.getElementById('installer-install-btn');
            var backBtn = document.getElementById('installer-back-btn');
            var spinner = document.getElementById('installer-spinner');
            var label = document.getElementById('installer-install-label');
            if (!installBtn || !label) return;

            var loadingText = installBtn.dataset.installLoading || 'Installing...';

            function lock() {
                [installBtn, backBtn].forEach(function (btn) {
                    if (!btn) return;
                    btn.classList.add('pointer-events-none', 'opacity-60', 'cursor-not-allowed');
                    btn.setAttribute('tabindex', '-1');
                });
                if (spinner) spinner.classList.remove('hidden');
                label.textContent = loadingText;
            }

            installBtn.addEventListener('click', function (e) {
                // 同步锁定两个按钮，防止连点或中途后退
                lock();
                // 不阻止默认行为：继续跳转执行安装
            });
        })();
    </script>
@endsection
