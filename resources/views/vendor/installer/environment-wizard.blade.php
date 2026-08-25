@extends('vendor.installer.layouts.master')

@section('template_title')
    {{ trans('installer_messages.environment.wizard.templateTitle') }}
@endsection

@section('title')
    {!! trans('installer_messages.environment.wizard.title') !!}
@endsection

@section('style')
    <style>
        .wizard-tab { color: #64748b; }
        .wizard-tab:hover { color: #334155; }
        .wizard-tab.is-active { color: #0284c7; border-color: #0284c7; }
    </style>
@endsection

@section('container')
    @php
        $labelClass = 'block text-sm font-semibold text-slate-700';
        $inputClass = 'mt-1 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm shadow-sm focus:border-primary-500 focus:ring-2 focus:ring-primary-500/30 focus:outline-none';
        $errorBlock = 'mt-1 flex items-center gap-1 text-xs text-red-600';
    @endphp

    <div data-tabs>
        <div class="flex border-b border-slate-200">
            <button type="button" data-tab="envPanel" class="wizard-tab is-active -mb-px border-b-2 border-transparent px-4 py-2.5 text-sm font-semibold transition">
                {{ trans('installer_messages.environment.wizard.tabs.environment') }}
            </button>
            <button type="button" data-tab="dbPanel" class="wizard-tab -mb-px border-b-2 border-transparent px-4 py-2.5 text-sm font-semibold transition">
                {{ trans('installer_messages.environment.wizard.tabs.database') }}
            </button>
            <button type="button" data-tab="adminPanel" class="wizard-tab -mb-px border-b-2 border-transparent px-4 py-2.5 text-sm font-semibold transition">
                {{ trans('installer_messages.environment.wizard.form.app_tabs.admin_label') }}
            </button>
            <button type="button" data-tab="otherPanel" class="wizard-tab -mb-px border-b-2 border-transparent px-4 py-2.5 text-sm font-semibold transition">
                {{ trans('installer_messages.environment.wizard.form.app_tabs.other_label') }}
            </button>
        </div>

        <form method="post" action="{{ route('LaravelInstaller::environmentSaveWizard') }}" class="mt-6">
            @csrf

            {{-- Environment --}}
            <div id="envPanel" data-panel class="space-y-4">
                <div class="{{ $errors->has('app_name') ? 'has-error' : '' }}">
                    <label for="app_name" class="{{ $labelClass }}">{{ trans('installer_messages.environment.wizard.form.app_name_label') }}</label>
                    <input type="text" name="app_name" id="app_name" value="{{ old('app_name') }}" placeholder="{{ trans('installer_messages.environment.wizard.form.app_name_placeholder') }}" class="{{ $inputClass }}" />
                    @if ($errors->has('app_name'))
                        <span class="{{ $errorBlock }}"><svg class="h-3.5 w-3.5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z" /></svg>{{ $errors->first('app_name') }}</span>
                    @endif
                </div>

                <div class="{{ $errors->has('environment') ? 'has-error' : '' }}">
                    <label for="environment" class="{{ $labelClass }}">{{ trans('installer_messages.environment.wizard.form.app_environment_label') }}</label>
                    <select name="environment" id="environment" class="{{ $inputClass }}" onchange="checkEnvironment(this.value);">
                        <option value="local" {{ old('environment') == 'local' ? 'selected' : '' }}>{{ trans('installer_messages.environment.wizard.form.app_environment_label_local') }}</option>
                        <option value="development" {{ old('environment') == 'development' ? 'selected' : '' }}>{{ trans('installer_messages.environment.wizard.form.app_environment_label_developement') }}</option>
                        <option value="qa" {{ old('environment') == 'qa' ? 'selected' : '' }}>{{ trans('installer_messages.environment.wizard.form.app_environment_label_qa') }}</option>
                        <option value="production" {{ old('environment', 'production') == 'production' ? 'selected' : '' }}>{{ trans('installer_messages.environment.wizard.form.app_environment_label_production') }}</option>
                        <option value="other" {{ old('environment') == 'other' ? 'selected' : '' }}>{{ trans('installer_messages.environment.wizard.form.app_environment_label_other') }}</option>
                    </select>
                    <div id="environment_text_input" class="mt-2" style="display: none;">
                        <input type="text" name="environment_custom" id="environment_custom" value="{{ old('environment_custom') }}" placeholder="{{ trans('installer_messages.environment.wizard.form.app_environment_placeholder_other') }}" class="{{ $inputClass }}" />
                    </div>
                    @if ($errors->has('environment'))
                        <span class="{{ $errorBlock }}"><svg class="h-3.5 w-3.5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z" /></svg>{{ $errors->first('environment') }}</span>
                    @endif
                </div>

                <div class="{{ $errors->has('app_debug') ? 'has-error' : '' }}">
                    <span class="{{ $labelClass }}">{{ trans('installer_messages.environment.wizard.form.app_debug_label') }}</span>
                    <div class="mt-2 flex gap-6">
                        <label class="inline-flex items-center gap-2 text-sm text-slate-700">
                            <input type="radio" name="app_debug" value="true" {{ old('app_debug', 'true') == 'true' ? 'checked' : '' }} class="text-primary-600 focus:ring-primary-500" />
                            {{ trans('installer_messages.environment.wizard.form.app_debug_label_true') }}
                        </label>
                        <label class="inline-flex items-center gap-2 text-sm text-slate-700">
                            <input type="radio" name="app_debug" value="false" {{ old('app_debug') == 'false' ? 'checked' : '' }} class="text-primary-600 focus:ring-primary-500" />
                            {{ trans('installer_messages.environment.wizard.form.app_debug_label_false') }}
                        </label>
                    </div>
                    @if ($errors->has('app_debug'))
                        <span class="{{ $errorBlock }}">{{ $errors->first('app_debug') }}</span>
                    @endif
                </div>

                <div class="{{ $errors->has('log_level') ? 'has-error' : '' }}">
                    <label for="log_level" class="{{ $labelClass }}">{{ trans('installer_messages.environment.wizard.form.log_level_label') }}</label>
                    <select name="log_level" id="log_level" class="{{ $inputClass }}">
                        @php $logLevels = ['debug','info','notice','warning','error','critical','alert','emergency']; @endphp
                        @foreach ($logLevels as $level)
                            <option value="{{ $level }}" {{ old('log_level', 'debug') == $level ? 'selected' : '' }}>{{ trans('installer_messages.environment.wizard.form.log_level_label_' . $level) }}</option>
                        @endforeach
                    </select>
                    @if ($errors->has('log_level'))
                        <span class="{{ $errorBlock }}">{{ $errors->first('log_level') }}</span>
                    @endif
                </div>

                <div class="{{ $errors->has('app_url') ? 'has-error' : '' }}">
                    <label for="app_url" class="{{ $labelClass }}">{{ trans('installer_messages.environment.wizard.form.app_url_label') }}</label>
                    <input type="url" name="app_url" id="app_url" value="{{ old('app_url', 'http://localhost') }}" placeholder="{{ trans('installer_messages.environment.wizard.form.app_url_placeholder') }}" class="{{ $inputClass }}" />
                    @if ($errors->has('app_url'))
                        <span class="{{ $errorBlock }}">{{ $errors->first('app_url') }}</span>
                    @endif
                </div>
            </div>

            {{-- Database --}}
            <div id="dbPanel" data-panel class="hidden space-y-4">
                <div class="{{ $errors->has('database_connection') ? 'has-error' : '' }}">
                    <label for="database_connection" class="{{ $labelClass }}">{{ trans('installer_messages.environment.wizard.form.db_connection_label') }}</label>
                    <select name="database_connection" id="database_connection" class="{{ $inputClass }}">
                        <option value="mysql" {{ old('database_connection') == 'mysql' ? 'selected' : '' }}>{{ trans('installer_messages.environment.wizard.form.db_connection_label_mysql') }}</option>
                        <option value="sqlite" {{ old('database_connection') == 'sqlite' ? 'selected' : '' }}>{{ trans('installer_messages.environment.wizard.form.db_connection_label_sqlite') }}</option>
                        <option value="pgsql" {{ old('database_connection') == 'pgsql' ? 'selected' : '' }}>{{ trans('installer_messages.environment.wizard.form.db_connection_label_pgsql') }}</option>
                        <option value="sqlsrv" {{ old('database_connection') == 'sqlsrv' ? 'selected' : '' }}>{{ trans('installer_messages.environment.wizard.form.db_connection_label_sqlsrv') }}</option>
                    </select>
                    @if ($errors->has('database_connection'))
                        <span class="{{ $errorBlock }}">{{ $errors->first('database_connection') }}</span>
                    @endif
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    <div class="{{ $errors->has('database_hostname') ? 'has-error' : '' }}">
                        <label for="database_hostname" class="{{ $labelClass }}">{{ trans('installer_messages.environment.wizard.form.db_host_label') }}</label>
                        <input type="text" name="database_hostname" id="database_hostname" value="{{ old('database_hostname', '127.0.0.1') }}" class="{{ $inputClass }}" />
                    </div>
                    <div class="{{ $errors->has('database_port') ? 'has-error' : '' }}">
                        <label for="database_port" class="{{ $labelClass }}">{{ trans('installer_messages.environment.wizard.form.db_port_label') }}</label>
                        <input type="number" name="database_port" id="database_port" value="{{ old('database_port', '3306') }}" class="{{ $inputClass }}" />
                    </div>
                </div>

                <div class="{{ $errors->has('database_name') ? 'has-error' : '' }}">
                    <label for="database_name" class="{{ $labelClass }}">{{ trans('installer_messages.environment.wizard.form.db_name_label') }}</label>
                    <input type="text" name="database_name" id="database_name" value="{{ old('database_name') }}" placeholder="{{ trans('installer_messages.environment.wizard.form.db_name_placeholder') }}" data-sqlite-placeholder="{{ trans('installer_messages.environment.wizard.form.sqlite_path_placeholder') }}" class="{{ $inputClass }}" />
                    @if ($errors->has('database_name'))
                        <span class="{{ $errorBlock }}">{{ $errors->first('database_name') }}</span>
                    @endif
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    <div class="{{ $errors->has('database_username') ? 'has-error' : '' }}">
                        <label for="database_username" class="{{ $labelClass }}">{{ trans('installer_messages.environment.wizard.form.db_username_label') }}</label>
                        <input type="text" name="database_username" id="database_username" value="{{ old('database_username') }}" class="{{ $inputClass }}" />
                    </div>
                    <div class="{{ $errors->has('database_password') ? 'has-error' : '' }}">
                        <label for="database_password" class="{{ $labelClass }}">{{ trans('installer_messages.environment.wizard.form.db_password_label') }}</label>
                        <input type="password" name="database_password" id="database_password" value="{{ old('database_password') }}" class="{{ $inputClass }}" />
                    </div>
                </div>
            </div>

            {{-- Admin Account --}}
            <div id="adminPanel" data-panel class="hidden space-y-4">
                <div class="grid gap-4 sm:grid-cols-2">
                    <div class="{{ $errors->has('admin_name') ? 'has-error' : '' }}"><label for="admin_name" class="{{ $labelClass }}">{{ trans('installer_messages.environment.wizard.form.app_tabs.admin_name_label') }}</label><input type="text" name="admin_name" id="admin_name" value="{{ old('admin_name') }}" placeholder="{{ trans('installer_messages.environment.wizard.form.app_tabs.admin_name_placeholder') }}" class="{{ $inputClass }}" />@if ($errors->has('admin_name'))<span class="{{ $errorBlock }}"><svg class="h-3.5 w-3.5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z" /></svg>{{ $errors->first('admin_name') }}</span>@endif</div>
                    <div class="{{ $errors->has('admin_email') ? 'has-error' : '' }}"><label for="admin_email" class="{{ $labelClass }}">{{ trans('installer_messages.environment.wizard.form.app_tabs.admin_email_label') }}</label><input type="email" name="admin_email" id="admin_email" value="{{ old('admin_email') }}" placeholder="{{ trans('installer_messages.environment.wizard.form.app_tabs.admin_email_placeholder') }}" class="{{ $inputClass }}" />@if ($errors->has('admin_email'))<span class="{{ $errorBlock }}"><svg class="h-3.5 w-3.5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z" /></svg>{{ $errors->first('admin_email') }}</span>@endif</div>
                    <div class="sm:col-span-2 {{ $errors->has('admin_password') ? 'has-error' : '' }}"><label for="admin_password" class="{{ $labelClass }}">{{ trans('installer_messages.environment.wizard.form.app_tabs.admin_password_label') }}</label><input type="password" name="admin_password" id="admin_password" placeholder="{{ trans('installer_messages.environment.wizard.form.app_tabs.admin_password_placeholder') }}" class="{{ $inputClass }}" />@if ($errors->has('admin_password'))<span class="{{ $errorBlock }}"><svg class="h-3.5 w-3.5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z" /></svg>{{ $errors->first('admin_password') }}</span>@endif</div>
                </div>
            </div>

            {{-- Other --}}
            <div id="otherPanel" data-panel class="hidden space-y-6">
                {{-- Broadcasting / Cache / Session / Queue --}}
                <div class="space-y-4">
                    <h3 class="text-sm font-semibold text-slate-700">{{ trans('installer_messages.environment.wizard.form.app_tabs.broadcasting_title') }}</h3>
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div><label for="broadcast_driver" class="{{ $labelClass }}">{{ trans('installer_messages.environment.wizard.form.app_tabs.broadcasting_label') }}</label><input type="text" name="broadcast_driver" id="broadcast_driver" value="{{ old('broadcast_driver', 'log') }}" class="{{ $inputClass }}" /></div>
                        <div><label for="cache_driver" class="{{ $labelClass }}">{{ trans('installer_messages.environment.wizard.form.app_tabs.cache_label') }}</label><input type="text" name="cache_driver" id="cache_driver" value="{{ old('cache_driver', 'file') }}" class="{{ $inputClass }}" /></div>
                        <div><label for="session_driver" class="{{ $labelClass }}">{{ trans('installer_messages.environment.wizard.form.app_tabs.session_label') }}</label><input type="text" name="session_driver" id="session_driver" value="{{ old('session_driver', 'file') }}" class="{{ $inputClass }}" /></div>
                        <div><label for="queue_connection" class="{{ $labelClass }}">{{ trans('installer_messages.environment.wizard.form.app_tabs.queue_label') }}</label><input type="text" name="queue_connection" id="queue_connection" value="{{ old('queue_connection', 'sync') }}" class="{{ $inputClass }}" /></div>
                    </div>
                </div>

                <hr class="border-slate-200">

                {{-- Redis --}}
                <div class="space-y-4">
                    <h3 class="text-sm font-semibold text-slate-700">{{ trans('installer_messages.environment.wizard.form.app_tabs.redis_label') }}</h3>
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div class="sm:col-span-2"><label for="redis_hostname" class="{{ $labelClass }}">{{ trans('installer_messages.environment.wizard.form.app_tabs.redis_host') }}</label><input type="text" name="redis_hostname" id="redis_hostname" value="{{ old('redis_hostname', '127.0.0.1') }}" class="{{ $inputClass }}" /></div>
                        <div><label for="redis_password" class="{{ $labelClass }}">{{ trans('installer_messages.environment.wizard.form.app_tabs.redis_password') }}</label><input type="password" name="redis_password" id="redis_password" value="{{ old('redis_password', 'null') }}" class="{{ $inputClass }}" /></div>
                        <div><label for="redis_port" class="{{ $labelClass }}">{{ trans('installer_messages.environment.wizard.form.app_tabs.redis_port') }}</label><input type="number" name="redis_port" id="redis_port" value="{{ old('redis_port', '6379') }}" class="{{ $inputClass }}" /></div>
                    </div>
                </div>

                <hr class="border-slate-200">

                {{-- Mail (all fields optional) --}}
                <div class="space-y-4">
                    <h3 class="text-sm font-semibold text-slate-700">{{ trans('installer_messages.environment.wizard.form.app_tabs.mail_label') }}</h3>
                    <div class="rounded-lg bg-slate-50 border border-slate-200 px-4 py-3 text-xs text-slate-600">
                        {{ trans('installer_messages.environment.wizard.form.app_tabs.mail_optional_hint') }}
                    </div>
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div class="sm:col-span-2">
                            <label for="mail_mailer" class="{{ $labelClass }}">{{ trans('installer_messages.environment.wizard.form.app_tabs.mail_driver_label') }}</label>
                            <select name="mail_mailer" id="mail_mailer" class="{{ $inputClass }}" onchange="toggleSmtpFields(this.value);">
                                <option value="log" {{ old('mail_mailer', 'log') == 'log' ? 'selected' : '' }}>{{ trans('installer_messages.environment.wizard.form.app_tabs.mail_option_log') }}</option>
                                <option value="smtp" {{ old('mail_mailer') == 'smtp' ? 'selected' : '' }}>{{ trans('installer_messages.environment.wizard.form.app_tabs.mail_option_smtp') }}</option>
                                <option value="sendmail" {{ old('mail_mailer') == 'sendmail' ? 'selected' : '' }}>{{ trans('installer_messages.environment.wizard.form.app_tabs.mail_option_sendmail') }}</option>
                            </select>
                        </div>
                        <div class="sm:col-span-2 smtp-only" style="display:none;"><label for="mail_host" class="{{ $labelClass }}">{{ trans('installer_messages.environment.wizard.form.app_tabs.mail_host_label') }}</label><input type="text" name="mail_host" id="mail_host" value="{{ old('mail_host', 'smtp.mailtrap.io') }}" class="{{ $inputClass }}" /></div>
                        <div class="smtp-only" style="display:none;"><label for="mail_port" class="{{ $labelClass }}">{{ trans('installer_messages.environment.wizard.form.app_tabs.mail_port_label') }}</label><input type="number" name="mail_port" id="mail_port" value="{{ old('mail_port', '2525') }}" class="{{ $inputClass }}" /></div>
                        <div class="smtp-only" style="display:none;"><label for="mail_encryption" class="{{ $labelClass }}">{{ trans('installer_messages.environment.wizard.form.app_tabs.mail_encryption_label') }}</label><input type="text" name="mail_encryption" id="mail_encryption" value="{{ old('mail_encryption', 'tls') }}" class="{{ $inputClass }}" /></div>
                        <div class="smtp-only" style="display:none;"><label for="mail_username" class="{{ $labelClass }}">{{ trans('installer_messages.environment.wizard.form.app_tabs.mail_username_label') }}</label><input type="text" name="mail_username" id="mail_username" value="{{ old('mail_username') }}" class="{{ $inputClass }}" /></div>
                        <div class="smtp-only" style="display:none;"><label for="mail_password" class="{{ $labelClass }}">{{ trans('installer_messages.environment.wizard.form.app_tabs.mail_password_label') }}</label><input type="password" name="mail_password" id="mail_password" value="{{ old('mail_password') }}" class="{{ $inputClass }}" /></div>
                        <div class="sm:col-span-2"><label for="mail_from_address" class="{{ $labelClass }}">{{ trans('installer_messages.environment.wizard.form.app_tabs.mail_from_address_label') }}</label><input type="email" name="mail_from_address" id="mail_from_address" value="{{ old('mail_from_address', 'noreply@example.com') }}" class="{{ $inputClass }}" /></div>
                        <div class="sm:col-span-2"><label for="mail_from_name" class="{{ $labelClass }}">{{ trans('installer_messages.environment.wizard.form.app_tabs.mail_from_name_label') }}</label><input type="text" name="mail_from_name" id="mail_from_name" value="{{ old('mail_from_name', 'Aine') }}" class="{{ $inputClass }}" /></div>
                    </div>
                </div>

                <hr class="border-slate-200">

                {{-- Pusher --}}
                <div class="space-y-4">
                    <h3 class="text-sm font-semibold text-slate-700">{{ trans('installer_messages.environment.wizard.form.app_tabs.pusher_label') }}</h3>
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div class="sm:col-span-2"><label for="pusher_app_id" class="{{ $labelClass }}">{{ trans('installer_messages.environment.wizard.form.app_tabs.pusher_app_id_label') }}</label><input type="text" name="pusher_app_id" id="pusher_app_id" value="{{ old('pusher_app_id') }}" class="{{ $inputClass }}" /></div>
                        <div><label for="pusher_app_key" class="{{ $labelClass }}">{{ trans('installer_messages.environment.wizard.form.app_tabs.pusher_app_key_label') }}</label><input type="text" name="pusher_app_key" id="pusher_app_key" value="{{ old('pusher_app_key') }}" class="{{ $inputClass }}" /></div>
                        <div><label for="pusher_app_secret" class="{{ $labelClass }}">{{ trans('installer_messages.environment.wizard.form.app_tabs.pusher_app_secret_label') }}</label><input type="password" name="pusher_app_secret" id="pusher_app_secret" value="{{ old('pusher_app_secret') }}" class="{{ $inputClass }}" /></div>
                    </div>
                </div>
            </div>

            <div class="flex justify-end pt-6">
                <button type="submit" class="inline-flex items-center gap-2 rounded-lg bg-green-600 px-5 py-2.5 text-sm font-bold text-white shadow-sm transition hover:bg-green-500 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2">
                    {{ trans('installer_messages.environment.wizard.form.buttons.install') }}
                    <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3" /></svg>
                </button>
            </div>
        </form>

        <div class="mt-4 flex justify-start">
            <a href="{{ route('LaravelInstaller::permissions') }}" class="inline-flex items-center gap-2 rounded-lg border border-slate-200 bg-white px-5 py-2.5 text-sm font-semibold text-slate-600 shadow-sm transition hover:bg-slate-50">
                <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18" /></svg>
                {{ trans('installer_messages.back') }}
            </a>
        </div>
    </div>
@endsection

@section('scripts')
    <script>
        function checkEnvironment(val) {
            var el = document.getElementById('environment_text_input');
            if (val === 'other') {
                el.style.display = 'block';
            } else {
                el.style.display = 'none';
            }
        }

        function toggleSmtpFields(val) {
            var show = val === 'smtp';
            document.querySelectorAll('.smtp-only').forEach(function (el) {
                el.style.display = show ? '' : 'none';
            });
        }

        (function () {
            function activate(group, targetId) {
                group.querySelectorAll('[data-tab]').forEach(function (btn) {
                    if (btn.closest('[data-tabs]') !== group) return;
                    if (btn.dataset.tab === targetId) {
                        btn.classList.add('is-active');
                    } else {
                        btn.classList.remove('is-active');
                    }
                });
                group.querySelectorAll('[data-panel]').forEach(function (panel) {
                    if (panel.closest('[data-tabs]') !== group) return;
                    if (panel.id === targetId) {
                        panel.classList.remove('hidden');
                    } else {
                        panel.classList.add('hidden');
                    }
                });
            }

            document.querySelectorAll('[data-tabs]').forEach(function (group) {
                group.querySelectorAll('[data-tab]').forEach(function (btn) {
                    if (btn.closest('[data-tabs]') !== group) return;
                    btn.addEventListener('click', function () {
                        activate(group, btn.dataset.tab);
                    });
                });
            });

            document.querySelectorAll('[data-goto]').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    var wizard = btn.closest('[data-tabs]');
                    if (wizard) {
                        activate(wizard, btn.dataset.goto);
                    }
                });
            });

            // Show the environment_custom field on load if "other" was selected.
            var env = document.getElementById('environment');
            if (env && env.value === 'other') {
                checkEnvironment('other');
            }

            // Show SMTP fields on load if smtp was selected.
            var mailer = document.getElementById('mail_mailer');
            if (mailer) {
                toggleSmtpFields(mailer.value);
            }

            // SQLite needs no server credentials — hide the host/port/user
            // fields and turn the database name into an optional file path.
            function toggleSqliteFields(val) {
                var isSqlite = val === 'sqlite';
                ['database_hostname', 'database_port', 'database_username', 'database_password'].forEach(function (id) {
                    var el = document.getElementById(id);
                    if (el) {
                        var wrap = el.closest('div');
                        if (wrap) wrap.style.display = isSqlite ? 'none' : '';
                    }
                });
                var dbName = document.getElementById('database_name');
                if (dbName) {
                    dbName.placeholder = isSqlite
                        ? (dbName.dataset.sqlitePlaceholder || 'database/database.sqlite')
                        : dbName.dataset.originalPlaceholder || dbName.placeholder;
                    if (!dbName.dataset.originalPlaceholder) dbName.dataset.originalPlaceholder = dbName.placeholder;
                }
            }
            var dbConn = document.getElementById('database_connection');
            if (dbConn) {
                dbConn.addEventListener('change', function () { toggleSqliteFields(dbConn.value); });
                toggleSqliteFields(dbConn.value);
            }
        })();
    </script>
@endsection
