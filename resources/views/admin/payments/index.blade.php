<x-layouts.admin title="Paiements" subtitle="Encaissements - participation, formations et co-auteurs">
    <x-slot:actions>
        <a href="{{ route('admin.payments.export', array_merge(request()->only('status', 'method', 'flow', 'search', 'from', 'to'), ['edition_id' => $edition_id])) }}" class="ukc-btn ukc-btn--ghost ukc-btn--sm">
            <x-ukc.icon name="download" :size="16" /> Exporter CSV
        </a>
        <a href="{{ route('admin.payments.create') }}" class="ukc-btn ukc-btn--blue ukc-btn--sm">
            <x-ukc.icon name="plus" :size="16" /> Paiement manuel
        </a>
    </x-slot:actions>

    <div class="admin-page admin-payments">
        <div class="ukc-stats">
            <div class="ukc-stat-card tw-stat"><div class="tw-stat__icon tw-stat__icon--green"><x-ukc.icon name="card" :size="20" /></div><div class="lbl">Encaissé</div><div class="val green">{{ number_format($stats['paid'], 0, ',', ' ') }}</div><div class="hint">{{ $currency }} · {{ $stats['paidCount'] }} paiement(s)</div><x-ukc.trend :trend="$trendPaid" period="7 j" /></div>
            <div class="ukc-stat-card tw-stat"><div class="tw-stat__icon tw-stat__icon--amber"><x-ukc.icon name="clock" :size="20" /></div><div class="lbl">En attente</div><div class="val warn">{{ number_format($stats['pending'], 0, ',', ' ') }}</div><div class="hint">{{ $currency }} · {{ $stats['pendingCount'] }} transaction(s)</div></div>
            <div class="ukc-stat-card tw-stat"><div class="tw-stat__icon tw-stat__icon--red"><x-ukc.icon name="close" :size="20" /></div><div class="lbl">Échoués</div><div class="val" style="color:#dc2626;">{{ $stats['failed'] }}</div><div class="hint">à relancer / vérifier</div></div>
            <div class="ukc-stat-card tw-stat"><div class="tw-stat__icon tw-stat__icon--blue"><x-ukc.icon name="chart" :size="20" /></div><div class="lbl">Taux de conversion</div><div class="val blue">{{ $stats['rate'] }} %</div><div class="hint">inscriptions → payées</div></div>
        </div>

        {{-- Filtres (GET) --}}
        <form method="GET" class="filter-bar">
            <select name="status" onchange="this.form.submit()">
                <option value="">Tous les statuts</option>
                @foreach ($statuses as $key => $label)<option value="{{ $key }}" @selected($status === $key)>{{ $label }}</option>@endforeach
            </select>
            <select name="flow" onchange="this.form.submit()" title="Nature du paiement">
                <option value="">Toutes les natures</option>
                @foreach ($flows as $key => $label)<option value="{{ $key }}" @selected($flow === $key)>{{ $label }}</option>@endforeach
            </select>
            <select name="method" onchange="this.form.submit()">
                <option value="">Tous les moyens</option>
                @foreach ($methodGroups as $group => $items)
                    <optgroup label="{{ $group }}">
                        @foreach ($items as $key => $label)<option value="{{ $key }}" @selected($method === $key)>{{ $label }}</option>@endforeach
                    </optgroup>
                @endforeach
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
            @if ($search !== '' || $status !== '' || $edition_id !== '' || $method !== '' || $flow !== '' || $from !== '' || $to !== '')
                <a href="{{ route('admin.payments.index') }}" class="ukc-btn ukc-btn--ghost ukc-btn--sm">Réinitialiser</a>
            @endif
        </form>

        <div class="ukc-card" style="padding:0;" data-ajax-root="payments">
            @if ($payments->count())
                <div class="ukc-table-wrap">
                    <table class="ukc-table">
                        <thead>
                            <tr>
                                <x-ukc.th sort="reference" :current="$sort" :dir="$dir">Référence</x-ukc.th>
                                <th>Participant</th><th>Objet</th>
                                <x-ukc.th sort="amount" :current="$sort" :dir="$dir">Montant</x-ukc.th>
                                <x-ukc.th sort="method" :current="$sort" :dir="$dir">Moyen</x-ukc.th>
                                <x-ukc.th sort="created_at" :current="$sort" :dir="$dir">Date</x-ukc.th>
                                <x-ukc.th sort="status" :current="$sort" :dir="$dir">Statut</x-ukc.th>
                                <th style="text-align:right;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($payments as $p)
                                <tr>
                                    <td style="font-family:var(--ukc-mono);">
                                        {{ $p->reference ?: '-' }}
                                        @if ($p->receipt_no)<div style="font-size:11px;color:var(--ukc-muted);">Reçu {{ $p->receipt_no }}</div>@endif
                                    </td>
                                    <td>
                                        <div class="title">{{ $p->participant?->full_name ?: '-' }}</div>
                                        <div class="meta">{{ $p->participant?->email }}</div>
                                    </td>
                                    <td>
                                        <span class="ukc-pill ukc-pill--{{ $p->displayFlow() === 'formation' ? 'info' : 'muted' }}">{{ $p->flowLabel() }}</span>
                                        @if ($detail = $p->flowDetail())
                                            <div class="meta" style="margin-top:3px;">{{ $detail }}</div>
                                        @elseif ($p->participant?->title)
                                            <div class="meta" style="margin-top:3px;">{{ $p->participant->title }}</div>
                                        @endif
                                    </td>
                                    <td style="font-weight:700;">{{ number_format((float) $p->amount, 0, ',', ' ') }} {{ $p->currency }}</td>
                                    <td>{{ $p->methodLabel() }}</td>
                                    <td style="white-space:nowrap;">{{ $p->created_at?->format('d/m/Y') }}</td>
                                    <td><span class="ukc-pill ukc-pill--{{ $p->statusPill() }}">{{ $p->statusLabel() }}</span></td>
                                    <td style="text-align:right;white-space:nowrap;">
                                        <div class="ukc-actions">
                                            @if ($p->status === 'pending' && filled($p->gateway_ref) && $p->gateway !== 'manual')
                                                {{-- Le participant affirme avoir payé : revérifier auprès de la passerelle. --}}
                                                <form method="POST" action="{{ route('admin.payments.recheck', $p) }}"
                                                      data-ajax data-ajax-msg="Vérification effectuée.">
                                                    @csrf
                                                    <button type="submit" class="ukc-btn ukc-btn--outline ukc-btn--sm" title="Interroger la passerelle pour connaître le statut réel">
                                                        <x-ukc.icon name="refresh" :size="14" /> Revérifier
                                                    </button>
                                                </form>
                                            @endif
                                            @if ($p->status === 'pending')
                                                <form method="POST" action="{{ route('admin.payments.mark-paid', $p) }}"
                                                      data-ajax data-ajax-msg="Paiement marqué réglé."
                                                      data-confirm="Marquer ce paiement comme réglé ? Un reçu sera envoyé.">
                                                    @csrf
                                                    <button type="submit" class="ukc-btn ukc-btn--green ukc-btn--sm"><x-ukc.icon name="check" :size="14" /> Marquer réglé</button>
                                                </form>
                                            @endif
                                            @if ($p->status === 'paid')
                                                <a href="{{ route('admin.payments.receipt', $p) }}" target="_blank" class="ukc-btn ukc-btn--outline ukc-btn--sm">
                                                    <x-ukc.icon name="receipt" :size="14" /> Reçu
                                                </a>
                                                <form method="POST" action="{{ route('admin.payments.refund', $p) }}"
                                                      data-ajax data-ajax-msg="Paiement remboursé."
                                                      data-confirm="Rembourser ce paiement ?">
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

        {{-- ===== Relances de paiement ===== --}}
        <div class="ukc-card" style="padding:0;margin-top:18px;">
            <div style="padding:16px 18px 0;">
                <h3 style="margin:0;">Relances de paiement</h3>
                <p style="font-size:13px;color:var(--ukc-muted);margin:4px 0 0;">Communications acceptées dont les frais d'inscription ne sont pas encore réglés (périmètre du filtre ci-dessus).</p>
            </div>
            @if ($toRemind->isNotEmpty())
                <div class="ukc-table-wrap" style="margin-top:12px;">
                    <table class="ukc-table">
                        <thead><tr><th>Participant</th><th>Édition</th><th>Montant dû</th><th>Dernière relance</th><th style="text-align:right;">Action</th></tr></thead>
                        <tbody>
                            @foreach ($toRemind as $row)
                                @php($p = $row['participant'])
                                <tr>
                                    <td class="title">
                                        {{ $p->full_name }}
                                        <div style="font-size:12px;color:var(--ukc-muted);">{{ $p->email }}</div>
                                    </td>
                                    <td>{{ $p->edition?->title ?: $p->edition?->year }}</td>
                                    <td>{{ $row['amount'] ? number_format($row['amount'], 0, ',', ' ').' '.$currency : '-' }}</td>
                                    <td>
                                        @if ($row['remindedAt'])
                                            <span class="ukc-pill ukc-pill--muted">{{ \Illuminate\Support\Carbon::parse($row['remindedAt'])->translatedFormat('d/m H:i') }}</span>
                                        @else
                                            <span style="color:var(--ukc-muted);">-</span>
                                        @endif
                                    </td>
                                    <td style="text-align:right;">
                                        <form method="POST" action="{{ route('admin.payments.remind', $p) }}">
                                            @csrf
                                            <button type="submit" class="ukc-btn {{ $row['remindedAt'] ? 'ukc-btn--ghost' : 'ukc-btn--blue' }} ukc-btn--sm">
                                                <x-ukc.icon name="mail" :size="15" /> {{ $row['remindedAt'] ? 'Relancer à nouveau' : 'Relancer' }}
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="ukc-empty" style="border:none;"><p style="margin:12px 0 18px;">Aucune inscription à relancer - tout est réglé. 🎉</p></div>
            @endif
        </div>
    </div>
</x-layouts.admin>
