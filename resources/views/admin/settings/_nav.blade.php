{{-- Sous-navigation des Réglages, en onglets « pilules » comme Programme et
     Soumissions. Source unique : ajouter un onglet = éditer ce seul fichier.

     ⚠️ Les onglets sont pilotés par l'URL (`?tab=`) mais SANS navigation : un
     clic réécrit l'URL et bascule le panneau. Les six premiers vivent dans un
     seul formulaire - recharger la page perdrait les saisies non enregistrées
     des autres onglets. Les liens restent de vrais `href`, donc partageables et
     ouvrables dans un nouvel onglet. --}}
<div class="ukc-nav-docs">
    @foreach (\App\Http\Controllers\Admin\SettingController::TAB_LABELS as $key => $label)
        <a href="{{ route('admin.settings', ['tab' => $key]) }}"
           :class="{ active: tab==='{{ $key }}' }"
           @click.prevent="go('{{ $key }}')">{{ $label }}</a>
    @endforeach
</div>