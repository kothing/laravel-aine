<?php

return [

    /*
     *
     * Shared translations.
     *
     */
    'title' => 'Laravel安装程序',
    'next' => '下一步',
    'finish' => '安装',
    'forms' => [
        'errorTitle' => '发生以下错误:',
    ],

    /*
     *
     * Home page translations.
     *
     */
    'back' => '上一步',

    'steps' => [
        'welcome' => '欢迎',
        'requirements' => '环境要求',
        'permissions' => '权限',
        'environment' => '环境设置',
        'confirm' => '确认',
        'database' => '数据库',
        'final' => '完成',
    ],

    'welcome' => [
        'templateTitle' => '欢迎',
        'title' => '欢迎来到Laravel安装程序',
        'message' => '欢迎来到安装向导.',
        'next' => '检查环境要求',
    ],

    'confirm' => [
        'templateTitle' => '确认安装',
        'title' => '准备安装',
        'intro' => '请确认以下配置。点击"开始安装"后将执行数据库迁移并创建管理员账号。',
        'app_name' => '应用名称',
        'app_environment' => '环境',
        'app_url' => '应用地址',
        'database' => '数据库',
        'admin_email' => '管理员邮箱',
        'back' => '返回修改配置',
        'install' => '开始安装',
        'admin_email_missing' => '管理员账号信息已丢失（会话过期），请返回并重新提交配置表单。',
    ],

    /*
     *
     * Requirements page translations.
     *
     */
    'requirements' => [
        'templateTitle' => '第一步 | 环境要求',
        'title' => '环境要求',
        'next' => '检查权限',
    ],

    /*
     *
     * Permissions page translations.
     *
     */
    'permissions' => [
        'templateTitle' => '第二步 | 权限',
        'title' => '权限',
        'next' => '配置环境',
    ],

    /*
     *
     * Environment page translations.
     *
     */
    'environment' => [
        'menu' => [
            'templateTitle' => '第三步 | 环境设置',
            'title' => '环境设置',
            'desc' => '请选择您的应用程序的<code> .env </code>文件的配置方式.',
            'wizard-button' => '表单向导设置',
            'classic-button' => '经典文本编辑器',
        ],
        'wizard' => [
            'templateTitle' => '第三步 | 环境设置 | 向导引导',
            'title' => '向导引导 <code>.env</code>文件',
            'tabs' => [
                'environment' => '环境',
                'database' => '数据库',
                'application' => '应用程序',
            ],
            'form' => [
                'name_required' => '环境名称是必需的.',
                'app_name_label' => '应用程序名称',
                'app_name_placeholder' => '应用程序名称',
                'app_environment_label' => '应用程序环境',
                'app_environment_label_local' => '本地',
                'app_environment_label_developement' => '开发',
                'app_environment_label_qa' => 'Qa',
                'app_environment_label_production' => '生产',
                'app_environment_label_other' => '其他',
                'app_environment_placeholder_other' => '输入您的环境...',
                'app_debug_label' => '应用程序调试',
                'app_debug_label_true' => '真',
                'app_debug_label_false' => '假',
                'log_level_label' => '应用程序日志级别',
                'log_level_label_debug' => '调试',
                'log_level_label_info' => '信息',
                'log_level_label_notice' => '注意',
                'log_level_label_warning' => '警告',
                'log_level_label_error' => '错误',
                'log_level_label_critical' => '严重',
                'log_level_label_alert' => '警报',
                'log_level_label_emergency' => '紧急',
                'app_url_label' => '应用程序URL',
                'app_url_placeholder' => '应用程序URL',
                'db_connection_failed' => '无法连接到数据库.',
                'db_connection_label' => '数据库连接',
                'db_connection_label_mysql' => 'mysql',
                'db_connection_label_sqlite' => 'sqlite',
                'db_connection_label_pgsql' => 'pgsql',
                'db_connection_label_sqlsrv' => 'sqlsrv',
                'db_host_label' => '数据库主机',
                'db_host_placeholder' => '数据库主机',
                'db_port_label' => '数据库端口',
                'db_port_placeholder' => '数据库端口',
                'db_name_label' => '数据库名称',
                'db_name_placeholder' => '数据库名称',
                'sqlite_path_placeholder' => '（可选）如 database/installer.sqlite，留空则使用默认 database/database.sqlite',
                'db_username_label' => '数据库用户名',
                'db_username_placeholder' => '数据库用户名',
                'db_password_label' => '数据库密码',
                'db_password_placeholder' => '数据库密码',

                'app_tabs' => [
                    'more_info' => '更多信息',
                    'broadcasting_title' => '广播，缓存，会话和队列',
                    'broadcasting_label' => '广播驱动程序',
                    'broadcasting_placeholder' => '广播驱动程序',
                    'cache_label' => '缓存驱动程序',
                    'cache_placeholder' => '缓存驱动程序',
                    'session_label' => '会话驱动程序',
                    'session_placeholder' => '会话驱动程序',
                    'queue_label' => '队列驱动程序',
                    'queue_placeholder' => '队列驱动程序',
                    'redis_label' => 'Redis 驱动程序',
                    'redis_host' => 'Redis 主机',
                    'redis_password' => 'Redis 密码',
                    'redis_port' => 'Redis 端口',

                    'mail_label' => '邮件',
                    'mail_driver_label' => '邮件驱动程序',
                    'mail_option_log' => '日志（不发送）',
                    'mail_option_smtp' => 'SMTP',
                    'mail_option_sendmail' => 'Sendmail',
                    'mail_driver_placeholder' => '邮件驱动程序',
                    'mail_host_label' => '邮件主机',
                    'mail_host_placeholder' => '邮件主机',
                    'mail_port_label' => '邮件端口',
                    'mail_port_placeholder' => '邮件端口',
                    'mail_username_label' => '邮件用户名',
                    'mail_username_placeholder' => '邮件用户名',
                    'mail_password_label' => '邮件密码',
                    'mail_password_placeholder' => '邮件密码',
                    'mail_encryption_label' => '邮件加密',
                    'mail_encryption_placeholder' => '邮件加密',
                    'mail_optional_hint' => '邮件配置为可选项。保持 "Log" 即可跳过 SMTP 设置，之后可在 .env 中配置',
                    'mail_from_address_label' => '发件地址',
                    'mail_from_name_label' => '发件人名称',

                    'pusher_label' => '推送',
                    'pusher_app_id_label' => 'Pusher 应用程序 Id',
                    'pusher_app_id_palceholder' => 'Pusher 应用程序 Id',
                    'pusher_app_key_label' => 'Pusher 应用程序 Key',
                    'pusher_app_key_palceholder' => 'Pusher 应用程序 Key',
                    'pusher_app_secret_label' => 'Pusher 应用程序 Secret',
                    'pusher_app_secret_palceholder' => 'Pusher 应用程序 Secret',


                    'other_label' => '其他',
                    'admin_label' => '管理员账号',
                    'admin_name_label' => '管理员姓名',
                    'admin_name_placeholder' => '管理员姓名',
                    'admin_email_label' => '管理员邮箱',
                    'admin_email_placeholder' => 'admin@example.com',
                    'admin_password_label' => '管理员密码',
                    'admin_password_placeholder' => '至少 8 个字符',
                ],
                'buttons' => [
                    'setup_application' => '设置应用程序',
                    'install' => '安装',
                ],
            ],
        ],
        'classic' => [
            'templateTitle' => '步骤 3 | 环境设置 | 经典编辑器',
            'title' => '经典环境编辑器',
            'save' => '保存 .env',
            'back' => '使用向导表单',
            'install' => '安装',
        ],
        'success' => '您的 .env 文件设置已保存。',
        'errors' => '无法保存 .env 文件，请手动创建它。',
    ],

    'install' => '安装',

    /*
     *
     * Installed Log translations.
     *
     */
    'installed' => [
        'success_log_message' => 'Laravel 安装程序成功安装于 ',
    ],

    /*
     *
     * Final page translations.
     *
     */
    'final' => [
        'title' => '安装完成',
        'templateTitle' => '安装完成',
        'finished' => '应用程序已成功安装。',
        'migration' => '迁移控制台输出:',
        'console' => '应用程序控制台输出:',
        'log' => '安装日志记录:',
        'env' => '最终 .env 文件:',
        'exit' => '点击这里退出',
    ],

    /*
     *
     * Update specific translations
     *
     */
    'updater' => [
        'steps' => [
            'welcome' => '欢迎',
            'overview' => '概述',
            'final' => '完成',
        ],

        /*
         *
         * Shared translations.
         *
         */
        'title' => 'Laravel 更新程序',

        /*
         *
         * Welcome page translations for update feature.
         *
         */
        'welcome' => [
            'title' => '欢迎来到更新向导',
            'message' => '欢迎来到更新向导。',
        ],

        /*
         *
         * Welcome page translations for update feature.
         *
         */
        'overview' => [
            'title' => '概述',
            'message' => '有 1 个更新.|有 :number 个更新.',
            'install_updates' => '安装更新',
        ],

        /*
         *
         * Final page translations.
         *
         */
        'final' => [
            'title' => '完成',
            'finished' => '应用程序的数据库已成功更新。',
            'exit' => '点击这里退出',
        ],

        'log' => [
            'success_message' => 'Laravel 安装程序成功更新于 ',
        ],
    ],
];
