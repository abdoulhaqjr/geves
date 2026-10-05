<x-layouts.admin title="Rapports de séance" subtitle="Lecture, amélioration et validation des synthèses">
    <x-slot:actions>
        <a href="{{ route('admin.secretariat') }}" class="ukc-btn ukc-btn--ghost ukc-btn--sm">← Secrétariat</a>
    </x-slot:actions>

    <div class="admin-page admin-program" x-data="{ open: false, cur: {} }">
        @include('admin.secretariat._nav', ['current' => 'dashboard'])

        {{-- Sélecteur d'événement, comme partout ailleurs. --}}
        <div class="ukc-field" style="min-width:320px;max-width:460px;margin:0 0 14px;">
            <label for="event_id">Événement</label>
            <select id="event_id" name="event_id" onchange="window.location = '{{ route('admin.secretariat.reports') }}?event_id=' + this.value">
                <option value="">- Choisir -</option>
                @foreach ($events as $ed)<option value="{{ $ed->id }}" @selected((string) $ed->id === $event_id)>{{ $ed->title ?: $ed->year }}</option>@endforeach
            </select>
        </div>

        @if ($event_id === '')
            <div class="ukc-empty"><h3>Choisissez un événement</h3><p>Sélectionnez un événement pour consulter les rapports de séance.</p></div>
        @else
            @php
                $reportStats = [
                    'sessions' => $sessions->count(),
                    'with_rapporteur' => $sessions->filter(fn ($s) => filled($s->rapporteur_email))->count(),
                    'submitted' => $sessions->filter(fn ($s) => $s->report && $s->report->isSubmitted())->count(),
                ];
            @endphp

            <div class="ukc-stats">
                <div class="ukc-stat-card tw-stat"><div class="tw-stat__icon tw-stat__icon--violet"><x-ukc.icon name="layers" :size="20" /></div><div class="lbl">Séances</div><div class="val">{{ $reportStats['sessions'] }}</div></div>
                <div class="ukc-stat-card tw-stat"><div class="tw-stat__icon tw-stat__icon--blue"><x-ukc.icon name="user" :size="20" /></div><div class="lbl">Avec rapporteur</div><div class="val blue">{{ $reportStats['with_rapporteur'] }}</div></div>
                <div class="ukc-stat-card tw-stat"><div class="tw-stat__icon tw-stat__icon--green"><x-ukc.icon name="check-circle" :size="20" /></div><div class="lbl">Rapports soumis</div><div class="val green">{{ $reportStats['submitted'] }}</div></div>
            </div>

            @forelse ($sessions as $session)
                @php($report = $session->report)
                <div class="ukc-card" style="margin-bottom:14px;">
                    <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:12px;flex-wrap:wrap;">
                        <div>
                            <h3 style="margin:0 0 4px;">{{ $session->label }}</h3>
                            <div style="font-size:13px;color:var(--ukc-muted);">
                                @if ($session->starts_at){{ $session->starts_at->translatedFormat('l j F · H:i') }}@endif
                                @if ($session->room) · {{ $session->room }}@endif
                                @if (filled($session->rapporteur_name) || filled($session->rapporteur_email))
                                    · Rapporteur : {{ $session->rapporteur_name ?: $session->rapporteur_email }}
                                @endif
                            </div>
                        </div>
                        @if ($report && $report->isValidated())
                            <span class="ukc-pill ukc-pill--ok">Validé{{ $report->validated_at ? ' · '.$report->validated_at->translatedFormat('d/m/Y H:i') : '' }}</span>
                        @elseif ($report && $report->isSubmitted())
                            <span class="ukc-pill ukc-pill--info">Soumis{{ $report->submitted_at ? ' · '.$report->submitted_at->translatedFormat('d/m/Y H:i') : '' }}</span>
                        @elseif ($report)
                            <span class="ukc-pill ukc-pill--warn">Brouillon</span>
                        @elseif (filled($session->rapporteur_email))
                            <span class="ukc-pill ukc-pill--muted">À rédiger</span>
                        @else
                            <span class="ukc-pill ukc-pill--muted">Sans rapporteur</span>
                        @endif
                    </div>

                    @if ($report && (filled($report->summary) || filled($report->highlights) || $report->attendance !== null))
                        <div style="margin-top:12px;border-top:1px solid var(--ukc-border);padding-top:12px;display:flex;flex-direction:column;gap:12px;">
                            @if (filled($report->summary))
                                <div>
                                    <div style="font-size:12px;font-weight:600;color:var(--ukc-muted);margin-bottom:4px;">Synthèse</div>
                                    <div style="font-size:14px;line-height:1.55;white-space:pre-line;">{{ $report->summary }}</div>
                                </div>
                            @endif
                            @if (filled($report->highlights))
                                <div>
                                    <div style="font-size:12px;font-weight:600;color:var(--ukc-muted);margin-bottom:4px;">Points saillants & recommandations</div>
                                    <div style="font-size:14px;line-height:1.55;white-space:pre-line;">{{ $report->highlights }}</div>
                                </div>
                            @endif
                            @if ($report->attendance !== null)
                                <div style="font-size:13px;color:var(--ukc-muted);">Affluence estimée : <strong>{{ $report->attendance }}</strong></div>
                            @endif
                        </div>
                    @endif

                    {{-- Secrétariat : améliorer + valider (panneau latéral), ou rouvrir. --}}
                    <div style="margin-top:12px;display:flex;gap:8px;flex-wrap:wrap;">
                        <button type="button" class="ukc-btn ukc-btn--blue ukc-btn--sm"
                                @click="cur = {
                                    action: '{{ route('admin.program.reports.save', $session) }}',
                                    label: @js($session->label),
                                    summary: @js((string) $report?->summary),
                                    highlights: @js((string) $report?->highlights),
                                    attendance: @js($report?->attendance),
                                    validated: {{ $report && $report->isValidated() ? 'true' : 'false' }}
                                }; open = true">
                            <x-ukc.icon name="pen" :size="15" /> {{ $report && $report->isValidated() ? 'Améliorer' : 'Améliorer / valider' }}
                        </button>
                        @if ($report && $report->isValidated())
                            <form method="POST" action="{{ route('admin.program.reports.reopen', $session) }}" data-confirm="Rouvrir ce rapport au rapporteur ?">
                                @csrf
                                <button type="submit" class="ukc-btn ukc-btn--outline ukc-btn--sm"><x-ukc.icon name="refresh" :size="15" /> Rouvrir au rapporteur</button>
                            </form>
                        @endif
                    </div>
                </div>
            @empty
                <div class="ukc-empty"><h3>Aucune séance</h3><p>Cet événement n'a pas encore de séance programmée.</p></div>
            @endforelse

            {{-- ===== Panneau latéral : améliorer / valider un rapport ===== --}}
            <x-ukc.slideover open="open" onClose="open=false" width="520px">
                <x-slot:header><span x-text="cur.validated ? 'Améliorer le rapport' : 'Améliorer / valider le rapport'"></span></x-slot:header>

                <form method="POST" :action="cur.action" id="reportForm" @submit="open=false" style="display:flex;flex-direction:column;gap:14px;">
                    @csrf
                    <div style="font-weight:700;" x-text="cur.label"></div>
                    <div class="ukc-field">
                        <label>Synthèse de la séance</label>
                        <textarea name="summary" rows="10" x-model="cur.summary"></textarea>
                    </div>
                    <div class="ukc-field">
                        <label>Points saillants & recommandations</label>
                        <textarea name="highlights" rows="5" x-model="cur.highlights"></textarea>
                    </div>
                    <div class="ukc-field" style="max-width:220px;">
                        <label>Affluence estimée</label>
                        <input type="number" name="attendance" min="0" x-model="cur.attendance">
                    </div>
                    <p style="font-size:12.5px;color:var(--ukc-muted);margin:0;">La validation enregistre vos modifications et <strong>verrouille</strong> le rapport : le rapporteur ne pourra plus le modifier.</p>
                </form>

                <x-slot:footer>
                    <button type="button" class="ukc-btn ukc-btn--ghost" @click="open=false">Annuler</button>
                    <button type="submit" form="reportForm" class="ukc-btn ukc-btn--green"><x-ukc.icon name="check" :size="16" /> Valider le rapport</button>
                </x-slot:footer>
            </x-ukc.slideover>
        @endif
    </div>
</x-layouts.admin>
