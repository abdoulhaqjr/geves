{{-- Sous-onglet « Badges prêts » (?tab=badges&stab=prets). Un badge n'est pas
     stocké : il est rendu à la demande depuis le modèle, « prêt » = participant
     éligible à l'impression. --}}
@php($bAll = ['event_id' => $event_id, 'tab' => 'badges', 'stab' => 'prets'])

@unless ($badgeTemplate)
    <div class="ukc-alert ukc-alert--warn" style="margin-bottom:14px;">
        <span class="ico"><x-ukc.icon name="info" :size="18" /></span>
        <div>Aucun modèle de badge n’est encore conçu pour cette événement : l’impression utilisera la mise en page par défaut.</div>
    </div>
@endunless

<form method="GET" class="filter-bar">
    <input type="hidden" name="event_id" value="{{ $event_id }}">
    <input type="hidden" name="tab" value="badges">
    <input type="hidden" name="stab" value="prets">
    <select name="paid" onchange="this.form.submit()">
        <option value="1" @selected($badgePaidOnly)>Inscriptions réglées</option>
        <option value="0" @selected(! $badgePaidOnly)>Tous les participants</option>
    </select>
    <input type="search" name="bsearch" value="{{ $badgeSearch }}" placeholder="Nom ou e-mail du participant…" style="min-width:230px;"
           onkeydown="if(event.key==='Enter'){this.form.submit();}">
    @if ($badgeSearch !== '' || ! $badgePaidOnly)
        <a href="{{ route('admin.documents.attestations', $bAll) }}" class="ukc-btn ukc-btn--ghost ukc-btn--sm">Réinitialiser</a>
    @endif
    <span style="flex:1;"></span>
    <a href="{{ route('admin.documents.badges', ['event' => $event_id, 'paid' => $badgePaidOnly ? 1 : 0]) }}"
       target="_blank" rel="noopener" class="ukc-btn ukc-btn--blue ukc-btn--sm">
        <x-ukc.icon name="download" :size="16" /> Planche à imprimer
    </a>
</form>

<div class="ukc-card" style="padding:0;">
    <div class="ukc-table-wrap">
        <table class="ukc-table">
            <thead>
                <tr><th>Participant</th><th>E-mail</th><th>Rôle</th><th>Institution</th><th>Paiement</th></tr>
            </thead>
            <tbody>
                @forelse ($badgeReady as $p)
                    <tr>
                        <td class="title">{{ $p->full_name }}</td>
                        <td class="meta">{{ $p->email ?: '-' }}</td>
                        <td>{{ $p->roleLabel() }}</td>
                        <td class="meta">{{ $p->institution ?: '-' }}</td>
                        <td>
                            @if ($p->participationPayments()->where('status', 'paid')->exists())
                                <span class="ukc-pill ukc-pill--ok">Réglé</span>
                            @else
                                <span class="ukc-pill ukc-pill--warn">En attente</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" style="padding:28px;text-align:center;color:var(--ukc-muted);">
                        Aucun participant pour ce filtre.
                    </td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div style="margin-top:14px;display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap;">
    <span style="font-size:12.5px;color:var(--ukc-muted);">
        @if ($badgeReady->total() > 0)
            {{ $badgeReady->firstItem() }}–{{ $badgeReady->lastItem() }} sur {{ $badgeReady->total() }} badge(s) prêt(s)
        @else
            Aucun badge prêt pour ce filtre.
        @endif
    </span>
    @if ($badgeReady->hasPages())
        <div>{{ $badgeReady->links() }}</div>
    @endif
</div>
