<div class="admin-page admin-payments">
    <div class="ukc-stats">
        <div class="ukc-stat-card"><div class="lbl">Encaissé ({{ $currency }})</div><div class="val green">{{ number_format($stats['paid'], 0, ',', ' ') }}</div></div>
        <div class="ukc-stat-card"><div class="lbl">En attente ({{ $currency }})</div><div class="val warn">{{ number_format($stats['pending'], 0, ',', ' ') }}</div></div>
        <div class="ukc-stat-card"><div class="lbl">Échoués</div><div class="val" style="color:#dc2626;">{{ $stats['failed'] }}</div></div>
        <div class="ukc-stat-card"><div class="lbl">Taux de conversion</div><div class="val blue">{{ $stats['rate'] }} %</div></div>
    </div>

    <div style="display:flex;align-items:center;justify-content:space-between;gap:16px;flex-wrap:wrap;margin-bottom:18px;">
        <div style="display:flex;gap:10px;flex-wrap:wrap;align-items:center;">
            <div class="ukc-field" style="min-width:220px;">
                <input type="search" wire:model.live.debounce.300ms="search" placeholder="Reçu ou participant…">
            </div>
            <div class="ukc-field" style="min-width:180px;">
                <select wire:model.live="edition_id">
                    <option value="">Toutes les éditions</option>
                    @foreach ($editions as $ed)
                        <option value="{{ $ed->id }}">{{ $ed->title ?: $ed->year }}</option>
                    @endforeach
                </select>
            </div>
            <div class="ukc-field" style="min-width:150px;">
                <select wire:model.live="status">
                    <option value="">Tous les statuts</option>
                    @foreach ($statuses as $key => $label)
                        <option value="{{ $key }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>
        </div>
        <a href="{{ route('admin.payments.create') }}" wire:navigate class="ukc-btn ukc-btn--blue">
            <x-ukc.icon name="plus" :size="18" /> Enregistrer un encaissement
        </a>
    </div>

    <div class="ukc-card" style="padding:0;">
        @if ($payments->count())
            <div class="ukc-table-wrap">
                <table class="ukc-table">
                    <thead>
                        <tr>
                            <th>Participant</th>
                            <th>Montant</th>
                            <th>Moyen</th>
                            <th>Reçu</th>
                            <th>Statut</th>
                            <th style="text-align:right;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($payments as $p)
                            <tr wire:key="pay-{{ $p->id }}">
                                <td>
                                    <div class="title">{{ $p->participant?->full_name ?: '—' }}</div>
                                    <div class="meta">{{ $p->participant?->email }}</div>
                                </td>
                                <td style="font-weight:700;">{{ number_format((float) $p->amount, 0, ',', ' ') }} {{ $p->currency }}</td>
                                <td>{{ $p->methodLabel() }}</td>
                                <td style="font-family:ui-monospace,monospace;">{{ $p->receipt_no ?: '—' }}</td>
                                <td><span class="ukc-pill ukc-pill--{{ $p->statusPill() }}">{{ $p->statusLabel() }}</span></td>
                                <td style="text-align:right;white-space:nowrap;">
                                    @if ($p->status === 'pending')
                                        <button type="button" class="ukc-btn ukc-btn--green ukc-btn--sm"
                                                wire:click="markPaid({{ $p->id }})"
                                                wire:confirm="Marquer ce paiement comme réglé ? Un reçu sera envoyé.">
                                            <x-ukc.icon name="check" :size="14" /> Marquer réglé
                                        </button>
                                    @endif
                                    @if ($p->status === 'paid')
                                        <a href="{{ route('admin.payments.receipt', $p) }}" target="_blank"
                                           class="ukc-btn ukc-btn--outline ukc-btn--sm">
                                            <x-ukc.icon name="receipt" :size="14" /> Reçu
                                        </a>
                                        <button type="button" class="ukc-btn ukc-btn--danger ukc-btn--sm"
                                                wire:click="refund({{ $p->id }})"
                                                wire:confirm="Rembourser ce paiement ?">Rembourser</button>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div style="padding:14px 18px;">{{ $payments->links() }}</div>
        @else
            <div class="ukc-empty">
                <h3>Aucun paiement</h3>
                <p>Enregistrez un encaissement pour commencer.</p>
                <a href="{{ route('admin.payments.create') }}" wire:navigate class="ukc-btn ukc-btn--blue">
                    <x-ukc.icon name="plus" :size="18" /> Enregistrer un encaissement
                </a>
            </div>
        @endif
    </div>
</div>
