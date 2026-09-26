{{-- Sous-onglet « Attestations délivrées » (?tab=attestations&stab=delivrees).
     Filtres, tri et pagination restent sur cette URL. --}}
@php($base = ['edition_id' => $edition_id, 'tab' => 'attestations', 'stab' => 'delivrees'])

<div class="ukc-stats">
    <div class="ukc-stat-card tw-stat"><div class="tw-stat__icon tw-stat__icon--blue"><x-ukc.icon name="diploma" :size="20" /></div><div class="lbl">Délivrées</div><div class="val blue">{{ $issuedStats['total'] }}</div></div>
    <div class="ukc-stat-card tw-stat"><div class="tw-stat__icon tw-stat__icon--green"><x-ukc.icon name="check-circle" :size="20" /></div><div class="lbl">Valides</div><div class="val green">{{ $issuedStats['valid'] }}</div></div>
    <div class="ukc-stat-card tw-stat"><div class="tw-stat__icon tw-stat__icon--red"><x-ukc.icon name="close" :size="20" /></div><div class="lbl">Révoquées</div><div class="val" style="color:#dc2626;">{{ $issuedStats['revoked'] }}</div></div>
    <div class="ukc-stat-card tw-stat"><div class="tw-stat__icon tw-stat__icon--slate"><x-ukc.icon name="layers" :size="20" /></div><div class="lbl">Types couverts</div><div class="val">{{ $issuedStats['byType']->count() }}</div></div>
</div>

<form method="GET" class="filter-bar">
    <input type="hidden" name="edition_id" value="{{ $edition_id }}">
    <input type="hidden" name="tab" value="attestations">
    <input type="hidden" name="stab" value="delivrees">
    <select name="type" onchange="this.form.submit()">
        <option value="">Tous les types</option>
        @foreach ($types as $key => $label)<option value="{{ $key }}" @selected($type === $key)>{{ $label }}</option>@endforeach
    </select>
    <select name="state" onchange="this.form.submit()">
        <option value="">Tous les états</option>
        <option value="valid" @selected($state === 'valid')>Valides</option>
        <option value="revoked" @selected($state === 'revoked')>Révoquées</option>
    </select>
    @if ($type !== '' || $state !== '')
        <a href="{{ route('admin.documents.attestations', $base) }}" class="ukc-btn ukc-btn--ghost ukc-btn--sm">Réinitialiser</a>
    @endif
</form>

<div class="ukc-card" style="padding:0;" data-ajax-root="attestations">
    <div class="ukc-table-wrap">
        <table class="ukc-table">
            <thead>
                <tr>
                    <x-ukc.th sort="recipient_name" :current="$sort" :dir="$dir">Bénéficiaire</x-ukc.th>
                    <x-ukc.th sort="type" :current="$sort" :dir="$dir">Type</x-ukc.th>
                    <x-ukc.th sort="uuid" :current="$sort" :dir="$dir">Référence</x-ukc.th>
                    <x-ukc.th sort="issued_at" :current="$sort" :dir="$dir">Émise le</x-ukc.th>
                    <x-ukc.th sort="revoked" :current="$sort" :dir="$dir">État</x-ukc.th>
                    <th style="text-align:right;">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($issued as $a)
                    <tr>
                        <td><strong>{{ $a->recipient_name ?: $a->participant?->full_name }}</strong><div style="font-size:12px;color:var(--ukc-muted);">{{ $a->recipient_email ?: $a->participant?->email }}</div></td>
                        <td><span class="ukc-pill ukc-pill--info">{{ $a->typeLabel() }}</span></td>
                        <td style="font-family:var(--ukc-mono);font-size:12px;">{{ \Illuminate\Support\Str::limit($a->uuid, 18) }}</td>
                        <td style="white-space:nowrap;">{{ $a->issued_at?->format('d/m/Y H:i') ?? '-' }}</td>
                        <td>@if ($a->revoked)<span class="ukc-pill ukc-pill--err">Révoquée</span>@else<span class="ukc-pill ukc-pill--ok">Valide</span>@endif</td>
                        <td>
                            <div class="ukc-actions" style="justify-content:flex-end;">
                                <a href="{{ route('attestation.document', $a->uuid) }}" target="_blank" class="ukc-btn--icon" title="Document"><x-ukc.icon name="diploma" :size="16" /></a>
                                <a href="{{ route('attestation.verify', $a->uuid) }}" target="_blank" class="ukc-btn--icon" title="Vérifier"><x-ukc.icon name="check-circle" :size="16" /></a>
                                @if ($a->revoked)
                                    <form method="POST" action="{{ route('admin.documents.attestations.restore', $a) }}" data-ajax data-ajax-msg="Attestation rétablie." style="display:inline;">
                                        @csrf
                                        <button type="submit" class="ukc-btn ukc-btn--ghost ukc-btn--sm">Rétablir</button>
                                    </form>
                                @else
                                    <form method="POST" action="{{ route('admin.documents.attestations.revoke', $a) }}" data-ajax data-ajax-msg="Attestation révoquée." data-confirm="Révoquer cette attestation ?" style="display:inline;">
                                        @csrf
                                        <button type="submit" class="ukc-btn ukc-btn--danger ukc-btn--sm">Révoquer</button>
                                    </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" style="padding:28px;text-align:center;color:var(--ukc-muted);">
                        Aucune attestation délivrée pour ce filtre. Émettez-en depuis l’onglet
                        <a href="{{ route('admin.documents.attestations', ['edition_id' => $edition_id, 'tab' => 'attestations', 'stab' => 'modeles']) }}">Modèles</a>.
                    </td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@if ($issued->hasPages())
    <div style="margin-top:16px;">{{ $issued->links() }}</div>
@endif
