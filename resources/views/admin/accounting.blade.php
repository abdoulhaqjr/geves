@php($money = fn ($n) => number_format((float) $n, 0, ',', ' ').' '.$currency)
<x-layouts.admin title="Comptabilité" subtitle="Suivi des encaissements · recettes & rapprochements">
    <div class="admin-page">

        {{-- Indicateurs financiers --}}
        <div class="ukc-stats" style="margin-bottom:18px;">
            <div class="ukc-stat-card tw-stat"><div class="tw-stat__icon tw-stat__icon--green"><x-ukc.icon name="card" :size="20" /></div><div class="lbl">Recettes encaissées</div><div class="val green">{{ $money($stats['revenue']) }}</div></div>
            <div class="ukc-stat-card tw-stat"><div class="tw-stat__icon tw-stat__icon--amber"><x-ukc.icon name="clock" :size="20" /></div><div class="lbl">En attente ({{ $stats['pending_count'] }})</div><div class="val">{{ $money($stats['pending']) }}</div></div>
            <div class="ukc-stat-card tw-stat"><div class="tw-stat__icon tw-stat__icon--violet"><x-ukc.icon name="inbox" :size="20" /></div><div class="lbl">Reste à encaisser ({{ $stats['outstanding_count'] }})</div><div class="val">{{ $money($stats['outstanding']) }}</div></div>
            <div class="ukc-stat-card tw-stat"><div class="tw-stat__icon tw-stat__icon--red"><x-ukc.icon name="refresh" :size="20" /></div><div class="lbl">Remboursés</div><div class="val">{{ $money($stats['refunded']) }}</div></div>
        </div>

        @if ($editions->isEmpty())
            <div class="ukc-empty"><h3>Aucune édition accessible</h3><p>Vous n'êtes rattaché à aucune édition avec accès aux paiements.</p></div>
        @else
            <div class="ukc-grid-2" style="align-items:start;">
                {{-- ===== Répartition par moyen de paiement ===== --}}
                <div class="ukc-card">
                    <h3>Recettes par moyen de paiement</h3>
                    <p style="font-size:13px;color:var(--ukc-muted);margin:4px 0 14px;">Ventilation des encaissements réglés (l'agrégateur FedaPay couvre Mobile Money et carte).</p>
                    @forelse ($byMethod as $m)
                        <div style="margin-bottom:12px;">
                            <div style="display:flex;justify-content:space-between;gap:10px;font-size:13.5px;margin-bottom:5px;">
                                <span style="font-weight:600;">{{ $m['label'] }} <span style="color:var(--ukc-muted);font-weight:400;">· {{ $m['count'] }}</span></span>
                                <span style="font-weight:700;">{{ $money($m['total']) }}</span>
                            </div>
                            <div style="height:8px;border-radius:6px;background:rgba(148,163,184,.18);overflow:hidden;">
                                <div style="height:100%;width:{{ max(3, $m['pct']) }}%;border-radius:6px;background:var(--ukc-blue);"></div>
                            </div>
                        </div>
                    @empty
                        <p style="font-size:.9rem;color:var(--ukc-muted);">Aucun encaissement pour l'instant.</p>
                    @endforelse
                </div>

                {{-- ===== Accès rapides ===== --}}
                <div class="ukc-card">
                    <h3>Actions</h3>
                    <div style="display:flex;flex-direction:column;gap:8px;margin-top:10px;">
                        <a href="{{ route('admin.payments.index') }}" class="ukc-btn ukc-btn--outline ukc-btn--sm" style="justify-content:flex-start;">
                            <x-ukc.icon name="list" :size="16" /> Liste des paiements
                        </a>
                        <a href="{{ route('admin.payments.index') }}?status=pending" class="ukc-btn ukc-btn--outline ukc-btn--sm" style="justify-content:flex-start;">
                            <x-ukc.icon name="clock" :size="16" /> Paiements en attente
                        </a>
                        <a href="{{ route('admin.payments.export') }}" class="ukc-btn ukc-btn--outline ukc-btn--sm" style="justify-content:flex-start;">
                            <x-ukc.icon name="download" :size="16" /> Export CSV (comptable)
                        </a>
                    </div>
                    <p style="font-size:12px;color:var(--ukc-muted);margin-top:12px;">La comptabilité consulte et rapproche les paiements ; la modification des tarifs relève des réglages de l'édition.</p>
                </div>
            </div>

            {{-- ===== Derniers encaissements ===== --}}
            <div class="ukc-card" style="padding:0;margin-top:18px;">
                <div style="padding:16px 18px 0;"><h3 style="margin:0;">Derniers encaissements</h3></div>
                @if ($recent->isNotEmpty())
                    <div class="ukc-table-wrap" style="margin-top:12px;">
                        <table class="ukc-table">
                            <thead><tr><th>Référence</th><th>Participant</th><th>Édition</th><th>Moyen</th><th>Montant</th><th>Date</th></tr></thead>
                            <tbody>
                                @foreach ($recent as $p)
                                    <tr>
                                        <td style="font-family:ui-monospace,monospace;font-weight:700;">{{ $p->reference ?: '—' }}</td>
                                        <td class="title">{{ $p->participant?->full_name ?: '—' }}</td>
                                        <td>{{ $p->edition?->title ?: $p->edition?->year }}</td>
                                        <td>{{ $p->methodLabel() }}</td>
                                        <td style="font-weight:700;">{{ $money($p->amount) }}</td>
                                        <td>{{ ($p->paid_at ?? $p->created_at)?->translatedFormat('d/m/Y') }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="ukc-empty" style="border:none;"><p style="margin:12px 0 18px;">Aucun encaissement enregistré.</p></div>
                @endif
            </div>
        @endif
    </div>
</x-layouts.admin>
