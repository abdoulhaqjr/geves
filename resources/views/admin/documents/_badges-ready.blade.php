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
</form>

{{-- ⚠️ La sélection ET la pagination vivent DANS la région échangée : changer de
     filtre change la liste, donc la sélection repart de zéro - cocher des gens
     qu'on ne voit plus serait pire. (La pagination n'était rattachée à aucune
     région : son compteur ne suivait pas les filtres.) --}}
<div data-filter-part="badges-table">
    <form method="POST" action="{{ route('admin.documents.badges.selection', ['event' => $event_id]) }}"
          x-data="{ refs: [], pageRefs: {{ \Illuminate\Support\Js::from($badgeReady->pluck('ref')->values()) }} }">
        @csrf
        <input type="hidden" name="type" value="{{ $badgeType }}">

        <div class="ukc-toolbar" style="justify-content:flex-end;">
            <span style="flex:1;font-size:12.5px;color:var(--ukc-muted);">
                Cochez des destinataires pour n’imprimer qu’eux, ou sortez la planche complète de la catégorie.
            </span>
            <button type="submit" class="ukc-btn ukc-btn--blue ukc-btn--sm" :disabled="refs.length === 0">
                <x-ukc.icon name="download" :size="16" /> Imprimer la sélection (<span x-text="refs.length">0</span>)
            </button>
            @php($badgeLots = (int) ceil($badgeReady->total() / \App\Documents\BadgeSheet::LOT))
            @if ($badgeLots > 1)
                @include('admin.documents._badge-lots', ['lots' => $badgeLots, 'total' => $badgeReady->total(), 'routeName' => 'admin.documents.badges', 'params' => ['event' => $event_id, 'type' => $badgeType]])
            @else
                <a href="{{ route('admin.documents.badges', ['event' => $event_id, 'type' => $badgeType]) }}"
                   class="ukc-btn ukc-btn--ghost ukc-btn--sm">
                    <x-ukc.icon name="download" :size="16" /> Planche complète ({{ $badgeReady->total() }})
                </a>
            @endif
        </div>

        <div class="ukc-card" style="padding:0;">
            <div class="ukc-table-wrap">
                <table class="ukc-table">
                    <thead>
                        <tr>
                            <th style="width:38px;">
                                <input type="checkbox" aria-label="Tout cocher sur cette page"
                                       :checked="pageRefs.length > 0 && refs.length === pageRefs.length"
                                       @change="refs = $event.target.checked ? [...pageRefs] : []">
                            </th>
                            <th>Destinataire</th><th>E-mail</th><th>Badge</th><th>Institution</th><th>Source</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($badgeReady as $person)
                            <tr>
                                <td><input type="checkbox" name="refs[]" value="{{ $person['ref'] }}" x-model="refs" aria-label="Imprimer le badge de {{ $person['name'] }}"></td>
                                <td class="title">{{ $person['name'] }}</td>
                                <td class="meta">{{ $person['email'] ?: '-' }}</td>
                                <td>{{ \App\Support\BadgeCatalog::role($badgeType) }}</td>
                                <td class="meta">{{ $person['institution'] ?: '-' }}</td>
                                <td class="meta">{{ \App\Support\BadgeCatalog::TYPES[$badgeType]['source'] }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="6" style="padding:28px;text-align:center;color:var(--ukc-muted);">
                                @if ($badgeSearch !== '')
                                    Aucun destinataire ne correspond à cette recherche.
                                @elseif (in_array($badgeType, ['moderation', 'rapporteur'], true))
                                    Aucun {{ $badgeType === 'moderation' ? 'modérateur' : 'rapporteur' }} avec une adresse e-mail renseignée dans le programme.
                                @else
                                    Aucun destinataire pour cette catégorie.
                                @endif
                            </td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </form>

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
</div>
