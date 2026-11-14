<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full bg-slate-50">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title inertia>{{ config('app.name', 'BlogVerse') }}</title>

    <link rel="icon" type="image/svg+xml" href="/favicon.svg">

    <meta name="description" content="BlogVerse Pro - platform publikasi artikel multi-tier: baca artikel teknologi, pendidikan, gaya hidup, bisnis, dan opini tanpa login, atau daftar untuk mulai menulis.">

    @viteReactRefresh
    @vite(['resources/css/app.css', 'resources/js/app.jsx', "resources/js/Pages/{$page['component']}.jsx"])
    @inertiaHead
</head>
<body class="h-full font-sans antialiased text-slate-800 bg-slate-50">
    @inertia
</body>
</html>

