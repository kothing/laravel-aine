<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <meta name="APP_URL" content="{{ env('APP_URL') }}">
        
        <!-- <title>{{ env("APP_NAME") }}</title> -->
        <title>{{ config('app.name', 'AineCMS') }}</title>

        <link rel="icon" type="image/svg+xml" href="{{ config('app.url') . '/images/favicon.svg'}}">
        <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700&display=swap">
        <link rel="stylesheet" href="{{ mix('css/admin.css') }}">

        <script src="{{ mix('js/admin.js') }}" defer></script>
    </head>
    <body class="font-sans antialiased">
        <div id="admin" v-cloak>
            <admin></admin>
        </div>
    </body>
</html>
