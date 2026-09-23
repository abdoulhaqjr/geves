<x-layouts.admin title="Rapports de séance" subtitle="Synthèses rédigées par les rapporteurs">
    <div class="admin-page admin-program">
        <div style="display:flex;justify-content:space-between;align-items:flex-end;gap:12px;flex-wrap:wrap;margin-bottom:18px;">
            <div class="ukc-field" style="max-width:320px;margin:0;">
                <label>Édition</label>
                <select onchange="window.location='{{ route('admin.program.reports') }}?edition_id='+this.value">
                    <option value="">- Choisir -</option>
                    @foreach ($editions as $ed)
                        <option value="{{ $ed->id }}" @selected((string) $ed->id === $edition_id)>{{ $ed->title ?: $ed->year }}</option>
                    @endforeach
                </select>
            </div>
            @if ($edition_id !== '')
                <div style="display:flex;gap:8px;">
                    <a href="{{ route('admin.program.sessions') }}?edition_id={{ $edition_id }}" class="ukc-btn ukc-btn--ghost ukc-btn--sm">
                        <x-ukc.icon name="list" :size="16" /> Programme
                    </a>
                </div>
            @endif
        </div>

        @if ($edition_id === '')
            <div class="ukc-empty"><h3>Choisissez une édition</h3><p>Sélectionnez une édition pour consulter les rapports de séance.</p></div>
        @else
            <div class="ukc-stats">
                <div class="ukc-stat-card tw-stat"><div class="tw-stat__icon tw-stat__icon--violet"><x-ukc.icon name="layers" :size="20" /></div><div class="lbl">Sessions</div><div class="val">{{ $stats['sessions'] }}</div></div>
                <div class="ukc-stat-card tw-stat"><div class="tw-stat__icon tw-stat__icon--blue"><x-ukc.icon name="user" :size="20" /></div><div class="lbl">Avec rapporteur</div><div class="val blue">{{ $stats['with_rapporteur'] }}</div></div>
                <div class="ukc-stat-card tw-stat"><div class="tw-stat__icon tw-stat__icon--green"><x-ukc.icon name="check-circle" :size="20" /></div><div class="lbl">Rapports soumis</div><div class="val green">{{ $stats['submitted'] }}</div></div>
            </div>

            @forelse ($sessions as $session)
                @php($report = $session->report)
                <div class="ukc-card" style="margin-bottom:14px;" x-data="{ edit: false }">
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
                        <div x-show="!edit" style="margin-top:12px;border-top:1px solid var(--ukc-border);padding-top:12px;display:flex;flex-direction:column;gap:12px;">
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

                    {{-- Secrétariat : améliorer + valider (verrouille le rapporteur), ou rouvrir. --}}
                    <div style="margin-top:12px;display:flex;gap:8px;flex-wrap:wrap;">
                        <button type="button" class="ukc-btn ukc-btn--ghost ukc-btn--sm" @click="edit = !edit">
                            <x-ukc.icon name="pen" :size="15" /> <span x-text="edit ? 'Fermer' : '{{ $report && $report->isValidated() ? 'Améliorer' : 'Améliorer / valider' }}'"></span>
                        </button>
                        @if ($report && $report->isValidated())
                            <form method="POST" action="{{ route('admin.program.reports.reopen', $session) }}" onsubmit="return confirm('Rouvrir ce rapport au rapporteur ?')">
                                @csrf
                                <button type="submit" class="ukc-btn ukc-btn--outline ukc-btn--sm"><x-ukc.icon name="refresh" :size="15" /> Rouvrir au rapporteur</button>
                            </form>
                        @endif
                    </div>

                    <form method="POST" action="{{ route('admin.program.reports.save', $session) }}" x-show="edit" x-cloak style="margin-top:12px;border-top:1px solid var(--ukc-border);padding-top:12px;display:flex;flex-direction:column;gap:12px;">
                        @csrf
                        <div class="ukc-field">
                            <label>Synthèse de la séance</label>
                            <textarea name="summary" rows="8">{{ $report?->summary }}</textarea>
                        </div>
                        <div class="ukc-field">
                            <label>Points saillants & recommandations</label>
                            <textarea name="highlights" rows="4">{{ $report?->highlights }}</textarea>
                        </div>
                        <div class="ukc-field" style="max-width:240px;">
                            <label>Affluence estimée</label>
                            <input type="number" name="attendance" min="0" value="{{ $report?->attendance }}">
                        </div>
                        <p style="font-size:12.5px;color:var(--ukc-muted);margin:0;">La validation enregistre vos modifications et <strong>verrouille</strong> le rapport : le rapporteur ne pourra plus le modifier.</p>
                        <div>
                            <button type="submit" class="ukc-btn ukc-btn--green"><x-ukc.icon name="check" :size="16" /> Valider le rapport</button>
                        </div>
                    </form>
                </div>
            @empty
                <div class="ukc-empty"><h3>Aucune séance</h3><p>Cette édition n'a pas encore de séance programmée.</p></div>
            @endforelse
        @endif
    </div>
</x-layouts.admin>
