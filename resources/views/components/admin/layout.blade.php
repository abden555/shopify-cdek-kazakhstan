<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ isset($title) ? $title.' · ' : '' }}{{ config('app.name', 'Shopify CDEK Kazakhstan') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
    <div class="admin-shell">
        <x-admin.sidebar />

        <div class="admin-main">
            <x-admin.header :title="$title ?? 'Dashboard'" />

            <main class="admin-content">
                {{ $slot }}
            </main>
        </div>
    </div>
</body>
</html>
