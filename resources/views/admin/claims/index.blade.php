<x-layouts.admin title="Réclamations & incidents" subtitle="Suivi et traitement des signalements des participants">
    <div class="admin-page">

        <div class="ukc-stats" style="margin-bottom:18px;">
            <div class="ukc-stat-card tw-stat"><div class="tw-stat__icon tw-stat__icon--red"><x-ukc.icon name="inbox" :size="20" /></div><div class="lbl">Ouvertes</div><div class="val">{{ $stats['open'] }}</div></div>
            <div class="ukc-stat-card tw-stat"><div class="tw-stat__icon tw-stat__icon--amber"><x-ukc.icon name="refresh" :size="20" /></div><div class="lbl">En traitement</div><div class="val">{{ $stats['in_progress'] }}</div></div>
            <div class="ukc-stat-card tw-stat"><div class="tw-stat__icon tw-stat__icon--green"><x-ukc.icon name="check-circle" :size="20" /></div><div class="lbl">Résolues</div><div class="val green">{{ $stats['resolved'] }}</div></div>
            <div class="ukc-stat-card tw-stat"><div class="tw-stat__icon tw-stat__icon--slate"><x-ukc.icon name="list" :size="20" /></div><div class="lbl">Total</div><div class="val">{{ $stats['total'] }}</div></div>
        </div>

        <form method="GET" class="ukc-card" style="display:flex;gap:10px;flex-wrap:wrap;align-items:flex-end;margin-bottom:16px;">
            <div class="ukc-field" style="margin:0;min-width:160px;">
                <label>Statut</label>
                <select name="status" onchange="this.form.submit()">
                    <option value="">Tous</option>
                    @foreach ($statuses as $k => $l)<option value="{{ $k }}" @selected($status === $k)>{{ $l }}</option>@endforeach
                </select>
            </div>
            <div class="ukc-field" style="margin:0;min-width:150px;">
                <label>Type</label>
                <select name="type" onchange="this.form.submit()">
                    <option value="">Tous</option>
                    @foreach ($types as $k => $l)<option value="{{ $k }}" @selected($type === $k)>{{ $l }}</option>@endforeach
                </select>
            </div>
            <div class="ukc-field" style="margin:0;flex:1;min-width:200px;">
                <label>Recherche</label>
                <input type="text" name="search" value="{{ $search }}" placeholder="Référence, objet, nom, e-mail…">
            </div>
            <button type="submit" class="ukc-btn ukc-btn--blue ukc-btn--sm"><x-ukc.icon name="search" :size="15" /> Filtrer</button>
        </form>

        <div class="ukc-card" style="padding:0;">
            @if ($claims->isNotEmpty())
                <div class="ukc-table-wrap">
                    <table class="ukc-table">
                        <thead><tr><th>Référence</th><th>Type</th><th>Objet</th><th>Déclarant</th><th>Statut</th><th>Assigné</th><th>Reçue</th></tr></thead>
                        <tbody>
                            @foreach ($claims as $c)
                                <tr style="cursor:pointer;" onclick="window.location='{{ route('admin.claims.show', $c) }}'">
                                    <td style="font-family:var(--ukc-mono);font-weight:700;">{{ $c->reference }}</td>
                                    <td><span class="ukc-pill ukc-pill--{{ $c->type === 'incident' ? 'warn' : 'info' }}">{{ $c->typeLabel() }}</span></td>
                                    <td class="title">
                                        {{ \Illuminate\Support\Str::limit($c->subject, 60) }}
                                        <div style="font-size:12px;color:var(--ukc-muted);">{{ $c->event?->title ?: ($c->event?->year ?: 'Général') }}@if ($c->category) · {{ $c->category }}@endif</div>
                                    </td>
                                    <td>{{ $c->name ?: '-' }}<div style="font-size:12px;color:var(--ukc-muted);">{{ $c->email }}</div></td>
                                    <td><span class="ukc-pill ukc-pill--{{ $c->statusPill() }}">{{ $c->statusLabel() }}</span></td>
                                    <td>{{ $c->assignee?->name ?: '-' }}</td>
                                    <td>{{ $c->created_at?->translatedFormat('d/m/Y') }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div style="padding:14px 18px;">{{ $claims->links() }}</div>
            @else
                <div class="ukc-empty"><h3>Aucune réclamation</h3><p>Les signalements des participants (via l’assistante {{ \App\Support\AiAgents::publicName() }} ou saisis) apparaîtront ici.</p></div>
            @endif
        </div>
    </div>
</x-layouts.admin>
