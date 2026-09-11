<x-layouts.admin title="Attestations délivrées" :subtitle="$edition ? 'Édition · '.($edition->title ?: $edition->year) : 'Attestations émises et vérifiables'">
    <x-slot:actions>
        <a class="ukc-btn ukc-btn--outline ukc-btn--sm" href="{{ route('admin.documents.attestations', ['edition_id' => $edition_id]) }}">
            <x-ukc.icon name="settings" :size="16" /> Émission &amp; templates
        </a>
        <a class="ukc-btn ukc-btn--ghost ukc-btn--sm" href="{{ route('attestation.verify.landing') }}" target="_blank">
            <x-ukc.icon name="check-circle" :size="16" /> Vérification publique
        </a>
    </x-slot:actions>

    <div class="admin-page admin-attestations">
        {{-- Sélecteur d'édition --}}
        <div style="display:flex;align-items:flex-end;gap:12px;flex-wrap:wrap;margin-bottom:16px;">
            <div class="ukc-field" style="max-width:340px;margin:0;">
                <label>Édition</label>
                <select onchange="window.location='{{ route('admin.attestations.index') }}?edition_id='+this.value">
                    <option value="">— Choisir —</option>
                    @foreach ($editions as $ed)<option value="{{ $ed->id }}" @selected((string) $ed->id === $edition_id)>{{ $ed->title ?: $ed->year }}</option>@endforeach
                </select>
            </div>
        </div>

        @if (! $edition)
            <div class="ukc-empty"><h3>Choisissez une édition</h3><p>Sélectionnez une édition pour consulter les attestations délivrées.</p></div>
        @else
            {{-- KPI --}}
            <div class="ukc-stats">
                <div class="ukc-stat-card tw-stat"><div class="tw-stat__icon tw-stat__icon--blue"><x-ukc.icon name="diploma" :size="20" /></div><div class="lbl">Délivrées</div><div class="val blue">{{ $stats['total'] }}</div></div>
                <div class="ukc-stat-card tw-stat"><div class="tw-stat__icon tw-stat__icon--green"><x-ukc.icon name="check-circle" :size="20" /></div><div class="lbl">Valides</div><div class="val green">{{ $stats['valid'] }}</div></div>
                <div class="ukc-stat-card tw-stat"><div class="tw-stat__icon tw-stat__icon--red"><x-ukc.icon name="close" :size="20" /></div><div class="lbl">Révoquées</div><div class="val" style="color:#dc2626;">{{ $stats['revoked'] }}</div></div>
                <div class="ukc-stat-card tw-stat"><div class="tw-stat__icon tw-stat__icon--slate"><x-ukc.icon name="layers" :size="20" /></div><div class="lbl">Types couverts</div><div class="val">{{ $stats['byType']->count() }}</div></div>
            </div>

            {{-- Filtres --}}
            <div class="filter-bar">
                <select onchange="window.location='{{ route('admin.attestations.index') }}?edition_id={{ $edition_id }}&state={{ $state }}&type='+this.value">
                    <option value="">Tous les types</option>
                    @foreach ($types as $key => $label)<option value="{{ $key }}" @selected($type === $key)>{{ $label }}</option>@endforeach
                </select>
                <select onchange="window.location='{{ route('admin.attestations.index') }}?edition_id={{ $edition_id }}&type={{ $type }}&state='+this.value">
                    <option value="">Tous les états</option>
                    <option value="valid" @selected($state === 'valid')>Valides</option>
                    <option value="revoked" @selected($state === 'revoked')>Révoquées</option>
                </select>
            </div>

            <div class="ukc-card" style="padding:0;">
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
                            @forelse ($attestations as $a)
                                <tr>
                                    <td><strong>{{ $a->recipient_name ?: $a->participant?->full_name }}</strong><div style="font-size:12px;color:var(--ukc-muted);">{{ $a->recipient_email ?: $a->participant?->email }}</div></td>
                                    <td><span class="ukc-pill ukc-pill--info">{{ $a->typeLabel() }}</span></td>
                                    <td style="font-family:ui-monospace,monospace;font-size:12px;">{{ \Illuminate\Support\Str::limit($a->uuid, 18) }}</td>
                                    <td style="white-space:nowrap;">{{ $a->issued_at?->format('d/m/Y H:i') ?? '—' }}</td>
                                    <td>@if ($a->revoked)<span class="ukc-pill ukc-pill--err">Révoquée</span>@else<span class="ukc-pill ukc-pill--ok">Valide</span>@endif</td>
                                    <td>
                                        <div class="ukc-actions" style="justify-content:flex-end;">
                                            <a href="{{ route('attestation.document', $a->uuid) }}" target="_blank" class="ukc-btn--icon" title="Document"><x-ukc.icon name="diploma" :size="16" /></a>
                                            <a href="{{ route('attestation.verify', $a->uuid) }}" target="_blank" class="ukc-btn--icon" title="Vérifier"><x-ukc.icon name="check-circle" :size="16" /></a>
                                            @if ($a->revoked)
                                                <form method="POST" action="{{ route('admin.documents.attestations.restore', $a) }}" style="display:inline;">
                                                    @csrf
                                                    <button type="submit" class="ukc-btn ukc-btn--ghost ukc-btn--sm">Rétablir</button>
                                                </form>
                                            @else
                                                <form method="POST" action="{{ route('admin.documents.attestations.revoke', $a) }}" onsubmit="return confirm('Révoquer cette attestation ?')" style="display:inline;">
                                                    @csrf
                                                    <button type="submit" class="ukc-btn ukc-btn--danger ukc-btn--sm">Révoquer</button>
                                                </form>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="6" style="padding:28px;text-align:center;color:var(--ukc-muted);">
                                    Aucune attestation délivrée pour ce filtre. Rendez-vous dans
                                    <a href="{{ route('admin.documents.attestations', ['edition_id' => $edition_id]) }}">Documents · Émission</a> pour en générer.
                                </td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            @if ($attestations->hasPages())
                <div style="margin-top:16px;">{{ $attestations->links() }}</div>
            @endif
        @endif
    </div>
</x-layouts.admin>
