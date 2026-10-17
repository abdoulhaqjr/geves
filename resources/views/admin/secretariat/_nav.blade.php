{{-- ⚠️ Défaut « documents » : c'est l'onglet d'arrivée du module. --}}
@props(['current' => 'documents'])
{{-- Sous-navigation du Secrétariat (pilules portées par l'URL). --}}
<div class="ukc-nav-docs" style="margin-bottom:18px;">
    <a href="{{ route('admin.secretariat.documents') }}" @class(['active' => $current === 'documents'])><x-ukc.icon name="folder" :size="15" /> Documents</a>
    <a href="{{ route('admin.secretariat.assistant') }}" @class(['active' => $current === 'assist'])><x-ukc.icon name="sparkles" :size="15" /> Secrétariat assisté</a>
    <a href="{{ route('admin.secretariat.reports') }}" @class(['active' => $current === 'dashboard'])><x-ukc.icon name="sheet" :size="15" /> Rapports de séance</a>
</div>
