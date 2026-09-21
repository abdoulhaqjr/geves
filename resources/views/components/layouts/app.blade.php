<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? config('app.name', 'GEVES') }}</title>
    <meta name="description" content="{{ $description ?? 'Gestion des colloques scientifiques — Université de Kara.' }}">
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('img/favicon-32.png') }}">
    <link rel="icon" type="image/png" sizes="16x16" href="{{ asset('img/favicon-16.png') }}">
    <link rel="apple-touch-icon" href="{{ asset('img/apple-touch-icon.png') }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('head')
</head>
<body class="ukc-scope antialiased">
    {{ $slot }}

    {{-- Notifications en popups fermables (toasts) — global, toutes zones. --}}
    <x-ukc.toast />

    {{-- Widget flottant Gesco — public + espace participant ; pas dans le
         back-office, la salle visio, ni la page chat dédiée. --}}
    @unless (request()->routeIs('admin.*') || request()->routeIs('session.meeting') || request()->routeIs('public.gesco'))
        <x-ukc.gesco-widget />
    @endunless

    @stack('scripts')
</body>
</html>
