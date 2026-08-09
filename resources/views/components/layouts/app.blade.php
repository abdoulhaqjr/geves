<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? config('app.name', 'GESCO') }}</title>
    <meta name="description" content="{{ $description ?? 'Gestion des colloques scientifiques — Université de Kara.' }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
    @stack('head')
</head>
<body class="ukc-scope antialiased">
    {{ $slot }}
    @livewireScripts
    @stack('scripts')
</body>
</html>
