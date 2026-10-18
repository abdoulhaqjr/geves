<x-layouts.admin title="Paiements" subtitle="Encaissements - participation, formations et co-auteurs">
    <x-slot:actions>
        <a href="{{ route('admin.payments.receipt.sample', ['event_id' => $event_id]) }}" target="_blank" class="ukc-btn ukc-btn--ghost ukc-btn--sm">
            <x-ukc.icon name="receipt" :size="16" /> Aperçu du reçu
        </a>
        <a href="{{ route('admin.payments.export', array_merge(request()->only('status', 'method', 'flow', 'search', 'from', 'to'), ['event_id' => $event_id])) }}" class="ukc-btn ukc-btn--ghost ukc-btn--sm">
            <x-ukc.icon name="download" :size="16" /> Exporter CSV
        </a>
        <a href="{{ route('admin.payments.create') }}" class="ukc-btn ukc-btn--blue ukc-btn--sm">
            <x-ukc.icon name="plus" :size="16" /> Paiement manuel
        </a>
    </x-slot:actions>

    <div class="admin-page admin-payments">
        {{-- Sélecteur d'événement en tête, comme la page Projet. --}}
        <div style="max-width:340px;margin-bottom:16px;">
            <div class="ukc-field" style="margin:0;">
                <label>Événement</label>
                <select onchange="window.location='{{ route('admin.payments.index') }}?event_id='+this.value">
                    <option value="">Toutes les événements</option>
                    @foreach ($events as $ed)<option value="{{ $ed->id }}" @selected((string) $ed->id === $event_id)>{{ $ed->title ?: $ed->year }}</option>@endforeach
                </select>
            </div>
        </div>

        <div class="ukc-stats">
            <div class="ukc-stat-card tw-stat"><div class="tw-stat__icon tw-stat__icon--green"><x-ukc.icon name="card" :size="20" /></div><div class="lbl">Encaissé</div><div class="val green">{{ number_format($stats['paid'], 0, ',', ' ') }}</div><div class="hint">{{ $currency }} - {{ $stats['paidCount'] }} paiement(s)</div><x-ukc.trend :trend="$trendPaid" period="7 j" /></div>
            <div class="ukc-stat-card tw-stat"><div class="tw-stat__icon tw-stat__icon--amber"><x-ukc.icon name="clock" :size="20" /></div><div class="lbl">En attente</div><div class="val warn">{{ number_format($stats['pending'], 0, ',', ' ') }}</div><div class="hint">{{ $currency }} - {{ $stats['pendingCount'] }} transaction(s)</div></div>
            <div class="ukc-stat-card tw-stat"><div class="tw-stat__icon tw-stat__icon--red"><x-ukc.icon name="close" :size="20" /></div><div class="lbl">Échoués</div><div class="val" style="color:#dc2626;">{{ $stats['failed'] }}</div><div class="hint">à relancer / vérifier</div></div>
            <div class="ukc-stat-card tw-stat"><div class="tw-stat__icon tw-stat__icon--blue"><x-ukc.icon name="chart" :size="20" /></div><div class="lbl">Taux de conversion</div><div class="val blue">{{ $stats['rate'] }} %</div><div class="hint">inscriptions → payées</div></div>
        </div>

        {{-- Filtres (GET) - une seule ligne, qui défile plutôt que de se replier. --}}
        {{-- ⚠️ Filtrage en AJAX (`data-filter`, cf. app.js) : la recherche
             s'applique à la frappe, sans valider ni recharger la page. --}}
        <form method="GET" class="filter-bar filter-bar--inline" data-filter="payments">
            <input type="hidden" name="event_id" value="{{ $event_id }}">
            <select name="status">
                <option value="">Tous les statuts</option>
                @foreach ($statuses as $key => $label)<option value="{{ $key }}" @selected($status === $key)>{{ $label }}</option>@endforeach
            </select>
            <select name="flow" title="Nature du paiement">
                <option value="">Toutes les natures</option>
                @foreach ($flows as $key => $label)<option value="{{ $key }}" @selected($flow === $key)>{{ $label }}</option>@endforeach
            </select>
            <select name="method">
                <option value="">Tous les moyens</option>
                @foreach ($methodGroups as $group => $items)
                    <optgroup label="{{ $group }}">
                        @foreach ($items as $key => $label)<option value="{{ $key }}" @selected($method === $key)>{{ $label }}</option>@endforeach
                    </optgroup>
                @endforeach
            </select>
            <input type="search" name="search" value="{{ $search }}" placeholder="Nom, e-mail, référence…" style="min-width:200px;flex:1;">
            <input type="date" name="from" value="{{ $from }}" title="Émis à partir du">
            <input type="date" name="to" value="{{ $to }}" title="Émis jusqu’au">
            {{-- Le lien dépend des filtres : sa propre région échangée. --}}
            <span data-filter-part="payments-reset">
                @if ($search !== '' || $status !== '' || $method !== '' || $flow !== '' || $from !== '' || $to !== '')
                    <a href="{{ route('admin.payments.index', ['event_id' => $event_id]) }}" class="ukc-btn ukc-btn--ghost ukc-btn--sm">Réinitialiser</a>
                @endif
            </span>
        </form>

        <div class="ukc-card" style="padding:0;" data-ajax-root="payments" data-filter-part="payments-table">
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
                                @php($gw = $p->gatewayDetail())
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
                                    <td style="font-weight:700;">{{ number_format((float) $p->amount, 0, ',', ' ') }} {{ $p->currencyLabel() }}</td>
                                    <td>
                                        {{ $p->methodLabel() }}
                                        {{-- ⚠️ L'opérateur EMPLOYÉ et le numéro DÉBITÉ, qui démentent parfois
                                             celui de l'inscription : c'est par là qu'on retrouve un paiement
                                             contesté. --}}
                                        @if (($gw['operator_label'] ?? '') !== '')
                                            <div class="meta">{{ $gw['operator_label'] }}</div>
                                        @endif
                                        @if (($gw['payer_phone'] ?? '') !== '')
                                            <div class="meta">payé depuis {{ $gw['payer_phone'] }}</div>
                                        @endif
                                        @if (($gw['operator_ref'] ?? '') !== '')
                                            <div class="meta" style="font-family:var(--ukc-mono);">réf. {{ $gw['operator_ref'] }}</div>
                                        @endif
                                    </td>
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
                                                {{-- ⚠️ Le dire AVANT le clic : pour un paiement en ligne, GEVES ne
                                                     renvoie pas l'argent - le prestataire ne rembourse que depuis
                                                     son tableau de bord. --}}
                                                <form method="POST" action="{{ route('admin.payments.refund', $p) }}"
                                                      data-ajax data-ajax-msg="Paiement marqué remboursé."
                                                      data-confirm="@if ($p->gateway !== 'manual' && filled($p->gateway_ref))Marquer ce paiement remboursé ? GEVES ne renvoie pas l'argent : le virement se fait depuis le tableau de bord du prestataire.@else Rembourser ce paiement ? @endif">
                                                    @csrf
                                                    <button type="submit" class="ukc-btn ukc-btn--danger ukc-btn--sm">Rembourser</button>
                                                </form>
                                            @endif
                                            @if (in_array($p->status, ['pending', 'failed'], true))
                                                {{-- Un paiement réglé ne se supprime pas : il porte un reçu
                                                     et ouvre des droits, il se rembourse. --}}
                                                <form method="POST" action="{{ route('admin.payments.destroy', $p) }}"
                                                      data-confirm="Supprimer définitivement ce paiement ({{ $p->reference }}) ? Cette tentative disparaîtra de l'historique.">
                                                    @csrf @method('DELETE')
                                                    <button type="submit" class="ukc-btn--icon" title="Supprimer ce paiement" aria-label="Supprimer ce paiement">
                                                        <x-ukc.icon name="trash" :size="16" />
                                                    </button>
                                                </form>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div style="padding:14px 18px;display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap;">
                    <span style="font-size:12.5px;color:var(--ukc-muted);">
                        {{ $payments->firstItem() }}-{{ $payments->lastItem() }} sur {{ $payments->total() }} paiement(s)
                    </span>
                    @if ($payments->hasPages())<div>{{ $payments->links() }}</div>@endif
                </div>
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
