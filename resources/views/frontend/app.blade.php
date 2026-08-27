<!DOCTYPE html>
<html>
    <head>
        <meta charset="utf-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0" />
        <meta name="APP_URL" content="{{ config('app.url') }}">
        <meta name="cms-project-identifier" content="{{ config('app.frontend_cms_project', 'cms') }}">
        <meta name="directory-project-identifier" content="{{ config('app.frontend_directory_project', 'business-directory') }}">

        <!-- <title>{{ env("APP_NAME") }}</title> -->
        <title>{{ config('app.name', 'Aine') }}</title>

        <link rel="icon" type="image/svg+xml" href="{{ config('app.url') . '/images/favicon.svg'}}">
        <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700&display=swap">

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>

    <body>
        <div id="app" v-cloak>
            <app></app>
        </div>
    </body>
</html>