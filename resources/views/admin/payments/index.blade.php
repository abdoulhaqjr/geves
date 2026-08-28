<x-layouts.admin title="Paiements" subtitle="Encaissements · FedaPay / Mobile Money">
    <x-slot:actions>
        <a href="{{ route('admin.payments.export', request()->only('edition_id', 'status', 'method', 'search', 'from', 'to')) }}" class="ukc-btn ukc-btn--ghost ukc-btn--sm">
            <x-ukc.icon name="download" :size="16" /> Exporter CSV
        </a>
        <a href="{{ route('admin.payments.create') }}" class="ukc-btn ukc-btn--blue ukc-btn--sm">
            <x-ukc.icon name="plus" :size="16" /> Paiement manuel
        </a>
    </x-slot:actions>

    <div class="admin-page admin-payments">
        <div class="ukc-stats">
            <div class="ukc-stat-card"><div class="lbl">Encaissé</div><div class="val green">{{ number_format($stats['paid'], 0, ',', ' ') }}</div><div class="hint">{{ $currency }} · {{ $stats['paidCount'] }} paiement(s)</div></div>
            <div class="ukc-stat-card"><div class="lbl">En attente</div><div class="val warn">{{ number_format($stats['pending'], 0, ',', ' ') }}</div><div class="hint">{{ $currency }} · {{ $stats['pendingCount'] }} transaction(s)</div></div>
            <div class="ukc-stat-card"><div class="lbl">Échoués</div><div class="val" style="color:#dc2626;">{{ $stats['failed'] }}</div><div class="hint">à relancer / vérifier</div></div>
            <div class="ukc-stat-card"><div class="lbl">Taux de conversion</div><div class="val blue">{{ $stats['rate'] }} %</div><div class="hint">inscriptions → payées</div></div>
        </div>

        {{-- Filtres (GET) --}}
        <form method="GET" class="filter-bar">
            <select name="status" onchange="this.form.submit()">
                <option value="">Tous les statuts</option>
                @foreach ($statuses as $key => $label)<option value="{{ $key }}" @selected($status === $key)>{{ $label }}</option>@endforeach
            </select>
            <select name="method" onchange="this.form.submit()">
                <option value="">Tous les moyens</option>
                @foreach ($methods as $key => $label)<option value="{{ $key }}" @selected($method === $key)>{{ $label }}</option>@endforeach
            </select>
            <select name="edition_id" onchange="this.form.submit()">
                <option value="">Toutes les éditions</option>
                @foreach ($editions as $ed)<option value="{{ $ed->id }}" @selected((string) $ed->id === $edition_id)>{{ $ed->title ?: $ed->year }}</option>@endforeach
            </select>
            <input type="search" name="search" value="{{ $search }}" placeholder="Nom, e-mail, référence…" style="min-width:200px;"
                   onkeydown="if(event.key==='Enter'){this.form.submit();}">
            <span class="sep" style="width:1px;height:24px;background:var(--ukc-border);align-self:center;"></span>
            <input type="date" name="from" value="{{ $from }}" title="Du" onchange="this.form.submit()">
            <input type="date" name="to" value="{{ $to }}" title="Au" onchange="this.form.submit()">
            @if ($search !== '' || $status !== '' || $edition_id !== '' || $method !== '' || $from !== '' || $to !== '')
                <a href="{{ route('admin.payments.index') }}" class="ukc-btn ukc-btn--ghost ukc-btn--sm">Réinitialiser</a>
            @endif
        </form>

        <div class="ukc-card" style="padding:0;">
            @if ($payments->count())
                <div class="ukc-table-wrap">
                    <table class="ukc-table">
                        <thead>
                            <tr>
                                <th>Référence</th><th>Participant</th><th>Catégorie</th><th>Montant</th><th>Moyen</th><th>Date</th><th>Statut</th><th style="text-align:right;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($payments as $p)
                                <tr>
                                    <td style="font-family:ui-monospace,monospace;">{{ $p->receipt_no ?: '—' }}</td>
                                    <td>
                                        <div class="title">{{ $p->participant?->full_name ?: '—' }}</div>
                                        <div class="meta">{{ $p->participant?->email }}</div>
                                    </td>
                                    <td>{{ $p->participant?->title ?: '—' }}</td>
                                    <td style="font-weight:700;">{{ number_format((float) $p->amount, 0, ',', ' ') }} {{ $p->currency }}</td>
                                    <td>{{ $p->methodLabel() }}</td>
                                    <td style="white-space:nowrap;">{{ $p->created_at?->format('d/m/Y') }}</td>
                                    <td><span class="ukc-pill ukc-pill--{{ $p->statusPill() }}">{{ $p->statusLabel() }}</span></td>
                                    <td style="text-align:right;white-space:nowrap;">
                                        <div class="ukc-actions">
                                            @if ($p->status === 'pending')
                                                <form method="POST" action="{{ route('admin.payments.mark-paid', $p) }}"
                                                      onsubmit="return confirm('Marquer ce paiement comme réglé ? Un reçu sera envoyé.');">
                                                    @csrf
                                                    <button type="submit" class="ukc-btn ukc-btn--green ukc-btn--sm"><x-ukc.icon name="check" :size="14" /> Marquer réglé</button>
                                                </form>
                                            @endif
                                            @if ($p->status === 'paid')
                                                <a href="{{ route('admin.payments.receipt', $p) }}" target="_blank" class="ukc-btn ukc-btn--outline ukc-btn--sm">
                                                    <x-ukc.icon name="receipt" :size="14" /> Reçu
                                                </a>
                                                <form method="POST" action="{{ route('admin.payments.refund', $p) }}"
                                                      onsubmit="return confirm('Rembourser ce paiement ?');">
                                                    @csrf
                                                    <button type="submit" class="ukc-btn ukc-btn--danger ukc-btn--sm">Rembourser</button>
                                                </form>
                                            @endif
                                        </div>
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
                    <a href="{{ route('admin.payments.create') }}" class="ukc-btn ukc-btn--blue">
                        <x-ukc.icon name="plus" :size="18" /> Enregistrer un encaissement
                    </a>
                </div>
            @endif
        </div>
    </div>
</x-layouts.admin>
