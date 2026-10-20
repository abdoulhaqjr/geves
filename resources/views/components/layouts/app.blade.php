{{-- `htmlClass` se pose sur <html> et non sur <body> : le back-office s'en
     sert pour régler sa densité au zoom, et un zoom posé ailleurs que sur
     la racine diviserait les unités de viewport qu'il contient. --}}
@props(['title' => null, 'description' => null, 'htmlClass' => ''])
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}"@if ($htmlClass) class="{{ $htmlClass }}"@endif>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? config('app.name', 'GEVES') }}</title>
    <meta name="description" content="{{ $description ?? 'Gestion des colloques scientifiques - Université de Kara.' }}">
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('img/favicon-32.png') }}">
    <link rel="icon" type="image/png" sizes="16x16" href="{{ asset('img/favicon-16.png') }}">
    {{-- L'icône d'accueil est carrée : c'est exactement le monogramme réglé en
         back-office (son repli est d'ailleurs le fichier livré ci-dessous).
         Les favicons 16/32 restent figés : ils exigent ces tailles précises. --}}
    <link rel="apple-touch-icon" href="{{ \App\Support\Branding::mark() }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('head')
</head>
<body class="ukc-scope antialiased">
    {{ $slot }}

    {{-- Notifications en popups fermables (toasts) - global, toutes zones. --}}
    <x-ukc.toast />

    {{-- Widget flottant de l'assistante - public + espace participant ; pas dans le
         back-office, la salle visio, ni la page chat dédiée. --}}
    @unless (request()->routeIs('admin.*') || request()->routeIs('session.meeting') || request()->routeIs('account.formations.visio') || request()->routeIs('public.gesco'))
        <x-ukc.gesco-widget />
    @endunless

    {{-- Information sur les cookies : pages PUBLIQUES seulement. Le back-office,
         l'espace participant et la salle de visio sont des espaces de travail
         ouverts avec un compte - on n'y pose pas un bandeau d'accueil. --}}
    @unless (request()->routeIs('admin.*') || request()->routeIs('account.*') || request()->routeIs('session.meeting') || request()->routeIs('public.cookies'))
        <x-ukc.cookie-notice />
    @endunless

    @stack('scripts')
</body>
</html>
