<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Server Requirements
    |--------------------------------------------------------------------------
    |
    | This is the default Laravel server requirements, you can add as many
    | as your application require, we check if the extension is enabled
    | by looping through the array and run "extension_loaded" on it.
    |
    */
    'core' => [
        'minPhpVersion' => '8.3.0',
    ],

    /*
    |--------------------------------------------------------------------------
    | Installer UI languages
    |--------------------------------------------------------------------------
    |
    | Locales available in the installer language selector. Add a new entry
    | here and drop the matching installer_messages.php file into lang/<code>/
    | to support another language.
    |
    */
    'locales' => [
        'en' => 'English',
        'zh_CN' => '中文',
    ],
    'final' => [
        'key' => true,
        'publish' => false,
    ],
    'requirements' => [
        'php' => [
            'openssl',
            'pdo',
            'mbstring',
            'tokenizer',
            'JSON',
            'cURL',
            'ctype',
            'xml',
            'fileinfo',
            'gd',
        ],
        'apache' => [
            'mod_rewrite',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Folders Permissions
    |--------------------------------------------------------------------------
    |
    | This is the default Laravel folders permissions, if your application
    | requires more permissions just add them to the array list bellow.
    |
    */
    'permissions' => [
        'storage/framework/'     => '755',
        'storage/logs/'          => '755',
        'bootstrap/cache/'       => '755',
        // SQLite deployments write the database file into this folder.
        'database/'              => '755',
    ],

    /*
    |--------------------------------------------------------------------------
    | Artisan Command
    |--------------------------------------------------------------------------
    |
    | Set the artisan commands that you want to run after migrations
    |
    |
    */
    // Database migrations run automatically; the admin account, roles and
    // settings are created by the installer (DatabaseController) from the
    // wizard form. DemoProjectsSeeder seeds the admin UI languages +
    // translation registry (AdminTranslationsSeeder) and the two demo
    // projects (demo-cms + demo-directory) with content, media,
    // translations, an API token and a webhook — it skips the admin account
    // when one already exists. Set this to [] to install without any
    // seed data.
    'artisan_command' => [
        'db:seed' => ['--class' => 'Database\Seeders\DemoProjectsSeeder', '--force' => true],
    ],

    /*
    |--------------------------------------------------------------------------
    | Environment Form Wizard Validation Rules & Messages
    |--------------------------------------------------------------------------
    |
    | This are the default form field validation rules. Available Rules:
    | https://laravel.com/docs/5.4/validation#available-validation-rules
    |
    */
    'environment' => [
        'form' => [
            'rules' => [
                'app_name'              => 'required|string|max:50',
                'environment'           => 'required|string|max:50',
                'environment_custom'    => 'required_if:environment,other|max:50',
                'app_debug'             => 'required|string',
                'log_level'             => 'required|string|max:50',
                'app_url'               => 'required|url',
                'database_connection'   => 'required|string|max:50',
                'database_hostname'     => 'required|string|max:50',
                'database_port'         => 'required|numeric',
                'database_name'         => 'required|string|max:50',
                'database_username'     => 'required|string|max:50',
                'database_password'     => 'nullable|string|max:50',
                'broadcast_driver'      => 'required|string|max:50',
                'cache_driver'          => 'required|string|max:50',
                'session_driver'        => 'required|string|max:50',
                'queue_connection'      => 'required|string|max:50',
                'redis_hostname'        => 'nullable|string|max:50',
                'redis_password'        => 'nullable|string|max:50',
                'redis_port'            => 'nullable|numeric',
                'mail_mailer'           => 'nullable|string|max:50',
                'mail_host'             => 'nullable|string|max:50',
                'mail_port'             => 'nullable|string|max:50',
                'mail_username'         => 'nullable|string|max:50',
                'mail_password'         => 'nullable|string|max:50',
                'mail_encryption'       => 'nullable|string|max:50',
                'mail_from_address'     => 'nullable|email|max:100',
                'mail_from_name'        => 'nullable|string|max:50',
                'pusher_app_id'         => 'max:50',
                'pusher_app_key'        => 'max:50',
                'pusher_app_secret'     => 'max:50',
                'admin_name'            => 'required|string|max:50',
                'admin_email'           => 'required|email|max:100',
                'admin_password'        => 'required|string|min:8|max:100',
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Installed Middleware Options
    |--------------------------------------------------------------------------
    | Different available status switch configuration for the
    | canInstall middleware located in `canInstall.php`.
    |
    */
    'installed' => [
        'redirectOptions' => [
            'route' => [
                'name' => 'welcome',
                'data' => [],
            ],
            'abort' => [
                'type' => '404',
            ],
            'dump' => [
                'data' => 'Dumping a not found message.',
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Selected Installed Middleware Option
    |--------------------------------------------------------------------------
    | The selected option fo what happens when an installer instance has been
    | Default output is to `/resources/views/error/404.blade.php` if none.
    | The available middleware options include:
    | route, abort, dump, 404, default, ''
    |
    */
    'installedAlreadyAction' => '',

    /*
    |--------------------------------------------------------------------------
    | Updater Enabled
    |--------------------------------------------------------------------------
    | Can the application run the '/update' route with the migrations.
    | The default option is set to False if none is present.
    | Boolean value
    |
    */
    'updaterEnabled' => 'true',

];
