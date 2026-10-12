@props(['url'])
{{-- En-tête des e-mails : logo de l'Université de Kara (réglé dans Réglages ›
     Général, servi en URL absolue par Branding) sur le bandeau bleu de la charte.
     Le lockup large « blanc » est utilisé car le bandeau est sombre ; le fichier
     livré par défaut est déjà dessiné en blanc. Repli sur le nom de la plateforme
     si aucun logo n'est disponible. --}}
<tr>
<td class="header">
<a href="{{ $url }}" style="display: inline-block;">
@php($logo = \App\Support\Branding::wideWhite())
@if ($logo)
<img src="{{ $logo }}" alt="{{ config('app.name') }}" class="logo{{ \App\Support\Branding::whitensOnDark(\App\Support\Branding::WIDE) ? ' logo-auto-white' : '' }}">
@else
{{ config('app.name') }}
@endif
</a>
</td>
</tr>
