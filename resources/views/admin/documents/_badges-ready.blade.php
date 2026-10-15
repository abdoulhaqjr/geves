{{-- Les badges sont rendus à la demande, par catégorie et modèle. --}}
@php($bAll = ['event_id' => $event_id, 'tab' => 'badges', 'stab' => 'prets', 'btype' => $badgeType])

{{-- ⚠️ Filtrage en AJAX (`data-filter`, cf. app.js) : la recherche s'applique à
     la frappe, sans valider ni recharger la page. --}}
<form method="GET" class="filter-bar" data-filter="badges">
    <input type="hidden" name="event_id" value="{{ $event_id }}">
    <input type="hidden" name="tab" value="badges">
    <input type="hidden" name="stab" value="prets">
    <label for="badge-type-filter">Catégorie</label>
    <select id="badge-type-filter" name="btype">
        @foreach (\App\Support\BadgeCatalog::TYPES as $key => $meta)
            <option value="{{ $key }}" @selected($badgeType === $key)>{{ $meta['label'] }}</option>
        @endforeach
    </select>
    <input type="search" name="bsearch" value="{{ $badgeSearch }}" placeholder="Nom ou e-mail…" style="min-width:230px;">
    {{-- Le lien dépend des filtres : sa propre région échangée. --}}
    <span data-filter-part="badges-reset">
        @if ($badgeSearch !== '')
            <a href="{{ route('admin.documents.attestations', $bAll) }}" class="ukc-btn ukc-btn--ghost ukc-btn--sm">Réinitialiser</a>
        @endif
    </span>
    <span style="flex:1;"></span>
    <a href="{{ route('admin.documents.badges', ['event' => $event_id, 'type' => $badgeType]) }}"
       target="_blank" rel="noopener" class="ukc-btn ukc-btn--blue ukc-btn--sm">
        <x-ukc.icon name="download" :size="16" /> Planche à imprimer
    </a>
</form>

<div class="ukc-card" style="padding:0;" data-filter-part="badges-table">
    <div class="ukc-table-wrap">
        <table class="ukc-table">
            <thead>
                <tr><th>Destinataire</th><th>E-mail</th><th>Badge</th><th>Institution</th><th>Source</th></tr>
            </thead>
            <tbody>
                @forelse ($badgeReady as $person)
                    <tr>
                        <td class="title">{{ $person['name'] }}</td>
                        <td class="meta">{{ $person['email'] ?: '-' }}</td>
                        <td>{{ \App\Support\BadgeCatalog::role($badgeType) }}</td>
                        <td class="meta">{{ $person['institution'] ?: '-' }}</td>
                        <td class="meta">{{ \App\Support\BadgeCatalog::TYPES[$badgeType]['source'] }}</td>
                    </tr>
                @empty
                    <tr><td colspan="5" style="padding:28px;text-align:center;color:var(--ukc-muted);">
                        Aucun destinataire pour cette catégorie.
                    </td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div style="margin-top:14px;display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap;">
    <span style="font-size:12.5px;color:var(--ukc-muted);">
        @if ($badgeReady->total() > 0)
            {{ $badgeReady->firstItem() }}-{{ $badgeReady->lastItem() }} sur {{ $badgeReady->total() }} badge(s) prêt(s)
        @else
            Aucun badge prêt pour ce filtre.
        @endif
    </span>
    @if ($badgeReady->hasPages())
        <div>{{ $badgeReady->links() }}</div>
    @endif
</div>
