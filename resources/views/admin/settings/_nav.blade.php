{{-- Sous-navigation des Réglages, en onglets « pilules » comme Programme et
     Soumissions. Source unique : ajouter un onglet = éditer ce seul fichier.

     ⚠️ Deux régimes, parce que les six premiers onglets vivent dans UN SEUL
     formulaire : sur la page des réglages ce sont des boutons Alpine (une
     navigation par requête ne posterait que l'onglet affiché et perdrait les
     autres), tandis que depuis une page voisine ce sont des liens qui rouvrent
     les réglages sur le bon onglet (`?tab=`). « Agents IA » est à l'inverse une
     vraie page : toujours un lien, sauf quand on y est.

     $current : null sur la page des réglages, 'agents' sur celle des agents. --}}
@php($settingsTabs = [
    'general' => 'Général',
    'payments' => 'Paiements',
    'email' => 'E-mail',
    'ai' => 'Intelligence artificielle',
    'meetings' => 'Visio',
    'integrations' => 'Intégrations',
])
<div class="ukc-nav-docs">
    @foreach ($settingsTabs as $key => $label)
        @if (($current ?? null) === null)
            <button type="button" @click="tab='{{ $key }}'" :class="{ active: tab==='{{ $key }}' }">{{ $label }}</button>
        @else
            <a href="{{ route('admin.settings', ['tab' => $key]) }}">{{ $label }}</a>
        @endif
    @endforeach

    @if (($current ?? null) === 'agents')
        <button type="button" class="active">Agents IA</button>
    @else
        <a href="{{ route('admin.settings.agents') }}">Agents IA</a>
    @endif
</div>
