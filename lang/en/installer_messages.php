<?php

return [

    /*
     *
     * Shared translations.
     *
     */
    'title' => 'Laravel Installer',
    'next' => 'Next Step',
    'back' => 'Previous',
    'finish' => 'Install',
    'forms' => [
        'errorTitle' => 'The Following errors occurred:',
    ],

    /*
     *
     * Home page translations.
     *
     */
    'back' => 'Back',

    'steps' => [
        'welcome' => 'Welcome',
        'requirements' => 'Requirements',
        'permissions' => 'Permissions',
        'environment' => 'Environment',
        'confirm' => 'Confirm',
        'database' => 'Database',
        'final' => 'Finalize',
    ],

    'welcome' => [
        'templateTitle' => 'Welcome',
        'title'   => 'Laravel Installer',
        'message' => 'Easy Installation and Setup Wizard.',
        'next'    => 'Check Requirements',
    ],

    'confirm' => [
        'templateTitle' => 'Confirm Installation',
        'title'   => 'Ready to Install',
        'intro'   => 'Please review the configuration below. Clicking "Start Installation" will run the database migration and create the admin account.',
        'app_name'       => 'Application Name',
        'app_environment'=> 'Environment',
        'app_url'        => 'Application URL',
        'database'       => 'Database',
        'admin_email'    => 'Admin Email',
        'back'           => 'Back to Configuration',
        'install'        => 'Start Installation',
        'admin_email_missing' => 'The admin account details were lost (session expired). Please go back and re-submit the configuration form.',
    ],

    /*
     *
     * Requirements page translations.
     *
     */
    'requirements' => [
        'templateTitle' => 'Step 1 | Server Requirements',
        'title' => 'Server Requirements',
        'next'    => 'Check Permissions',
    ],

    /*
     *
     * Permissions page translations.
     *
     */
    'permissions' => [
        'templateTitle' => 'Step 2 | Permissions',
        'title' => 'Permissions',
        'next' => 'Configure Environment',
    ],

    /*
     *
     * Environment page translations.
     *
     */
    'environment' => [
        'menu' => [
            'templateTitle' => 'Step 3 | Environment Settings',
            'title' => 'Environment Settings',
            'desc' => 'Please select how you want to configure the apps <code>.env</code> file.',
            'wizard-button' => 'Form Wizard Setup',
            'classic-button' => 'Classic Text Editor',
        ],
        'wizard' => [
            'templateTitle' => 'Step 3 | Environment Settings | Guided Wizard',
            'title' => 'Guided <code>.env</code> Wizard',
            'tabs' => [
                'environment' => 'Environment',
                'database' => 'Database',
                'application' => 'Application',
            ],
            'form' => [
                'name_required' => 'An environment name is required.',
                'app_name_label' => 'App Name',
                'app_name_placeholder' => 'App Name',
                'app_environment_label' => 'App Environment',
                'app_environment_label_local' => 'Local',
                'app_environment_label_developement' => 'Development',
                'app_environment_label_qa' => 'Qa',
                'app_environment_label_production' => 'Production',
                'app_environment_label_other' => 'Other',
                'app_environment_placeholder_other' => 'Enter your environment...',
                'app_debug_label' => 'App Debug',
                'app_debug_label_true' => 'True',
                'app_debug_label_false' => 'False',
                'log_level_label' => 'App Log Level',
                'log_level_label_debug' => 'debug',
                'log_level_label_info' => 'info',
                'log_level_label_notice' => 'notice',
                'log_level_label_warning' => 'warning',
                'log_level_label_error' => 'error',
                'log_level_label_critical' => 'critical',
                'log_level_label_alert' => 'alert',
                'log_level_label_emergency' => 'emergency',
                'app_url_label' => 'App Url',
                'app_url_placeholder' => 'App Url',
                'db_connection_failed' => 'Could not connect to the database.',
                'db_connection_label' => 'Database Connection',
                'db_connection_label_mysql' => 'mysql',
                'db_connection_label_sqlite' => 'sqlite',
                'db_connection_label_pgsql' => 'pgsql',
                'db_connection_label_sqlsrv' => 'sqlsrv',
                'db_host_label' => 'Database Host',
                'db_host_placeholder' => 'Database Host',
                'db_port_label' => 'Database Port',
                'db_port_placeholder' => 'Database Port',
                'db_name_label' => 'Database Name',
                'db_name_placeholder' => 'Database Name',
                'sqlite_path_placeholder' => '(optional) e.g. database/installer.sqlite - blank uses the default database/database.sqlite',
                'db_username_label' => 'Database User Name',
                'db_username_placeholder' => 'Database User Name',
                'db_password_label' => 'Database Password',
                'db_password_placeholder' => 'Database Password',

                'app_tabs' => [
                    'more_info' => 'More Info',
                    'broadcasting_title' => 'Broadcasting, Caching, Session, &amp; Queue',
                    'broadcasting_label' => 'Broadcast Driver',
                    'broadcasting_placeholder' => 'Broadcast Driver',
                    'cache_label' => 'Cache Driver',
                    'cache_placeholder' => 'Cache Driver',
                    'session_label' => 'Session Driver',
                    'session_placeholder' => 'Session Driver',
                    'queue_label' => 'Queue Driver',
                    'queue_placeholder' => 'Queue Driver',
                    'redis_label' => 'Redis Driver',
                    'redis_host' => 'Redis Host',
                    'redis_password' => 'Redis Password',
                    'redis_port' => 'Redis Port',

                    'mail_label' => 'Mail',
                    'mail_driver_label' => 'Mail Driver',
                    'mail_option_log' => 'Log (no sending)',
                    'mail_option_smtp' => 'SMTP',
                    'mail_option_sendmail' => 'Sendmail',
                    'mail_driver_placeholder' => 'Mail Driver',
                    'mail_host_label' => 'Mail Host',
                    'mail_host_placeholder' => 'Mail Host',
                    'mail_port_label' => 'Mail Port',
                    'mail_port_placeholder' => 'Mail Port',
                    'mail_username_label' => 'Mail Username',
                    'mail_username_placeholder' => 'Mail Username',
                    'mail_password_label' => 'Mail Password',
                    'mail_password_placeholder' => 'Mail Password',
                    'mail_encryption_label' => 'Mail Encryption',
                    'mail_encryption_placeholder' => 'Mail Encryption',
                    'mail_optional_hint' => 'Mail configuration is optional. Leave as "Log" to skip SMTP setup — you can configure it later in .env',
                    'mail_from_address_label' => 'Mail From Address',
                    'mail_from_name_label' => 'Mail From Name',

                    'pusher_label' => 'Pusher',
                    'pusher_app_id_label' => 'Pusher App Id',
                    'pusher_app_id_palceholder' => 'Pusher App Id',
                    'pusher_app_key_label' => 'Pusher App Key',
                    'pusher_app_key_palceholder' => 'Pusher App Key',
                    'pusher_app_secret_label' => 'Pusher App Secret',
                    'pusher_app_secret_palceholder' => 'Pusher App Secret',


                    'other_label' => 'Other',
                    'admin_label' => 'Admin Account',
                    'admin_name_label' => 'Admin Name',
                    'admin_name_placeholder' => 'Admin Name',
                    'admin_email_label' => 'Admin Email',
                    'admin_email_placeholder' => 'admin@example.com',
                    'admin_password_label' => 'Admin Password',
                    'admin_password_placeholder' => 'At least 8 characters',
                ],
                'buttons' => [
                    'setup_application' => 'Setup Application',
                    'install' => 'Install',
                ],
            ],
        ],
        'classic' => [
            'templateTitle' => 'Step 3 | Environment Settings | Classic Editor',
            'title' => 'Classic Environment Editor',
            'save' => 'Save .env',
            'back' => 'Use Form Wizard',
            'install' => 'Save and Install',
        ],
        'success' => 'Your .env file settings have been saved.',
        'errors' => 'Unable to save the .env file, Please create it manually.',
    ],

    'install' => 'Install',

    /*
     *
     * Installed Log translations.
     *
     */
    'installed' => [
        'success_log_message' => 'Laravel Installer successfully INSTALLED on ',
    ],

    /*
     *
     * Final page translations.
     *
     */
    'final' => [
        'title' => 'Installation Finished',
        'templateTitle' => 'Installation Finished',
        'finished' => 'Application has been successfully installed.',
        'migration' => 'Migration &amp; Seed Console Output:',
        'console' => 'Application Console Output:',
        'log' => 'Installation Log Entry:',
        'env' => 'Final .env File:',
        'exit' => 'Click here to exit',
    ],

    /*
     *
     * Update specific translations
     *
     */
    'updater' => [
        'steps' => [
            'welcome' => 'Welcome',
            'overview' => 'Overview',
            'final' => 'Finalize',
        ],

        /*
         *
         * Shared translations.
         *
         */
        'title' => 'Laravel Updater',

        /*
         *
         * Welcome page translations for update feature.
         *
         */
        'welcome' => [
            'title'   => 'Welcome To The Updater',
            'message' => 'Welcome to the update wizard.',
        ],

        /*
         *
         * Welcome page translations for update feature.
         *
         */
        'overview' => [
            'title'   => 'Overview',
            'message' => 'There is 1 update.|There are :number updates.',
            'install_updates' => 'Install Updates',
        ],

        /*
         *
         * Final page translations.
         *
         */
        'final' => [
            'title' => 'Finished',
            'finished' => 'Application\'s database has been successfully updated.',
            'exit' => 'Click here to exit',
        ],

        'log' => [
            'success_message' => 'Laravel Installer successfully UPDATED on ',
        ],
    ],
];
