<x-layouts.admin title="Secrétariat" subtitle="Rapports de séance · suivi administratif">
    @php($me = auth('web')->user())
    <x-slot:actions>
        @if ($me?->canAnyEdition('participants'))
            <a href="{{ route('admin.participants.index') }}" class="ukc-btn ukc-btn--outline ukc-btn--sm"><x-ukc.icon name="users" :size="15" /> Participants</a>
        @endif
        @if ($me?->canAnyEdition('documents'))
            <a href="{{ route('admin.documents.attestations') }}" class="ukc-btn ukc-btn--outline ukc-btn--sm"><x-ukc.icon name="diploma" :size="15" /> Attestations</a>
        @endif
        @if ($me?->canAnyEdition('emails'))
            <a href="{{ route('admin.emails.index') }}" class="ukc-btn ukc-btn--outline ukc-btn--sm"><x-ukc.icon name="mail" :size="15" /> E-mails</a>
        @endif
    </x-slot:actions>

    <div class="admin-page">
        @include('admin.secretariat._nav', ['current' => 'dashboard'])

        @if ($events->isEmpty())
            <div class="ukc-empty"><h3>Aucun événement accessible</h3><p>Vous n'êtes rattaché à aucun événement pour le moment.</p></div>
        @else
            <div x-data="{
                f: {{ $stats['to_validate'] > 0 ? "'to_validate'" : "'all'" }},
                counts: {
                    all: {{ $stats['total'] }},
                    to_validate: {{ $stats['to_validate'] }},
                    awaiting: {{ $stats['awaiting'] }},
                    scheduled: {{ $stats['scheduled'] }},
                    validated: {{ $stats['validated'] }},
                },
                get shown() { return this.f === 'all' ? this.counts.all : this.counts[this.f]; },
                visible(status) { return this.f === 'all' || this.f === status; },
            }">
                {{-- Cartes KPI = filtres cliquables. Cliquer « Validés » affiche TOUS les rapports validés. --}}
                <div class="ukc-stats sec-filters" style="margin-bottom:18px;">
                    <button type="button" class="ukc-stat-card tw-stat sec-filter" :class="{ 'is-active': f === 'to_validate' }" @click="f = 'to_validate'">
                        <div class="tw-stat__icon tw-stat__icon--amber"><x-ukc.icon name="check-circle" :size="20" /></div>
                        <div class="lbl">À valider</div><div class="val">{{ $stats['to_validate'] }}</div>
                    </button>
                    <button type="button" class="ukc-stat-card tw-stat sec-filter" :class="{ 'is-active': f === 'awaiting' }" @click="f = 'awaiting'">
                        <div class="tw-stat__icon tw-stat__icon--slate"><x-ukc.icon name="pen" :size="20" /></div>
                        <div class="lbl">En attente de rédaction</div><div class="val">{{ $stats['awaiting'] }}</div>
                    </button>
                    <button type="button" class="ukc-stat-card tw-stat sec-filter" :class="{ 'is-active': f === 'validated' }" @click="f = 'validated'">
                        <div class="tw-stat__icon tw-stat__icon--green"><x-ukc.icon name="check" :size="20" /></div>
                        <div class="lbl">Rapports validés</div><div class="val green">{{ $stats['validated'] }}</div>
                    </button>
                    <button type="button" class="ukc-stat-card tw-stat sec-filter" :class="{ 'is-active': f === 'all' }" @click="f = 'all'">
                        <div class="tw-stat__icon tw-stat__icon--violet"><x-ukc.icon name="layers" :size="20" /></div>
                        <div class="lbl">Toutes les séances</div><div class="val">{{ $stats['total'] }}</div>
                    </button>
                </div>

                <div class="ukc-card" style="padding:0;">
                    <div style="padding:16px 18px;display:flex;align-items:flex-start;justify-content:space-between;gap:12px;flex-wrap:wrap;border-bottom:1px solid var(--ukc-border);">
                        <div>
                            <h3 style="margin:0;" x-text="({
                                to_validate: 'Rapports à valider',
                                awaiting: 'En attente de rédaction',
                                validated: 'Rapports validés',
                                scheduled: 'Séances programmées',
                                all: 'Toutes les séances',
                            })[f]"></h3>
                            <p style="font-size:13px;color:var(--ukc-muted);margin:4px 0 0;" x-text="({
                                to_validate: 'Rapports soumis par les rapporteurs. La validation verrouille le rapport.',
                                awaiting: 'Séances commencées dont le rapport n\'est pas encore soumis.',
                                validated: 'Rapports validés et verrouillés. Le rapporteur ne peut plus les modifier.',
                                scheduled: 'Séances à venir avec un rapporteur désigné.',
                                all: 'Toutes les séances avec un rapporteur, tous statuts confondus.',
                            })[f]"></p>
                        </div>
                        <span class="ukc-pill ukc-pill--muted" style="flex:none;"><span x-text="shown"></span> séance<span x-show="shown > 1">s</span></span>
                    </div>

                    <div class="ukc-table-wrap">
                        <table class="ukc-table">
                            <thead>
                                <tr>
                                    <th>Séance</th>
                                    <th>Rapporteur</th>
                                    <th>Statut</th>
                                    <th>Date</th>
                                    <th style="text-align:right;">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($rows as $row)
                                    @php($s = $row->s)
                                    <tr x-show="visible('{{ $row->status }}')">
                                        <td class="title">
                                            {{ $s->label }}
                                            <div style="font-size:12px;color:var(--ukc-muted);">{{ $s->event?->title ?: $s->event?->year }}@if ($s->room) · {{ $s->room }}@endif</div>
                                        </td>
                                        <td>
                                            {{ $s->rapporteur_name ?: ($s->rapporteur_email ?: '-') }}
                                            @if (filled($s->rapporteur_name) && filled($s->rapporteur_email))
                                                <div style="font-size:12px;color:var(--ukc-muted);">{{ $s->rapporteur_email }}</div>
                                            @endif
                                        </td>
                                        <td>
                                            @switch($row->status)
                                                @case('to_validate')<span class="ukc-pill ukc-pill--info">À valider</span>@break
                                                @case('awaiting')<span class="ukc-pill ukc-pill--warn">À rédiger</span>@break
                                                @case('validated')<span class="ukc-pill ukc-pill--ok">Validé</span>@break
                                                @default<span class="ukc-pill ukc-pill--muted">Programmée</span>
                                            @endswitch
                                        </td>
                                        <td style="font-size:13px;color:var(--ukc-muted);white-space:nowrap;">
                                            @switch($row->status)
                                                @case('to_validate')Soumis le {{ $s->report?->submitted_at?->translatedFormat('d/m/Y H:i') ?: '-' }}@break
                                                @case('validated')Validé le {{ $s->report?->validated_at?->translatedFormat('d/m/Y H:i') ?: '-' }}@break
                                                @default{{ $s->starts_at?->translatedFormat('d/m/Y H:i') ?: '-' }}
                                            @endswitch
                                        </td>
                                        <td style="text-align:right;">
                                            <div style="display:inline-flex;gap:8px;flex-wrap:wrap;justify-content:flex-end;">
                                                @if ($row->status === 'to_validate')
                                                    <a href="{{ route('admin.secretariat.reports') }}?event_id={{ $s->event_id }}" class="ukc-btn ukc-btn--ghost ukc-btn--sm"><x-ukc.icon name="eye" :size="15" /> Lire / améliorer</a>
                                                    <form method="POST" action="{{ route('admin.program.reports.save', $s) }}" data-confirm="Valider ce rapport ? Le rapporteur ne pourra plus le modifier.">
                                                        @csrf
                                                        <button type="submit" class="ukc-btn ukc-btn--green ukc-btn--sm"><x-ukc.icon name="check" :size="15" /> Valider</button>
                                                    </form>
                                                @elseif ($row->status === 'validated')
                                                    <a href="{{ route('admin.secretariat.reports') }}?event_id={{ $s->event_id }}" class="ukc-btn ukc-btn--ghost ukc-btn--sm"><x-ukc.icon name="eye" :size="15" /> Consulter</a>
                                                @elseif (filled($s->rapporteur_email))
                                                    <a href="mailto:{{ $s->rapporteur_email }}?subject={{ rawurlencode('Rapport de séance : '.$s->label) }}" class="ukc-btn ukc-btn--outline ukc-btn--sm"><x-ukc.icon name="mail" :size="15" /> Relancer</a>
                                                @else
                                                    <span style="font-size:12.5px;color:var(--ukc-muted);">-</span>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <div x-show="shown === 0" class="ukc-empty" style="border:none;">
                        <p style="margin:18px 0;display:flex;align-items:center;justify-content:center;gap:6px;">
                            <x-ukc.icon name="check-circle" :size="16" />
                            <span x-text="({
                                to_validate: 'Aucun rapport en attente de validation.',
                                awaiting: 'Aucune séance en attente de rédaction.',
                                validated: 'Aucun rapport validé pour l\'instant.',
                                scheduled: 'Aucune séance programmée.',
                                all: 'Aucune séance avec rapporteur.',
                            })[f]"></span>
                        </p>
                    </div>
                </div>
            </div>
        @endif
    </div>
</x-layouts.admin>
