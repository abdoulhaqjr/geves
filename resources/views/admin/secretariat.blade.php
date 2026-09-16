<x-layouts.admin title="Secrétariat" subtitle="Rapports de séance à valider · suivi administratif">
    @php($me = auth('web')->user())
    <x-slot:actions>
        <a href="{{ route('admin.program.reports') }}" class="ukc-btn ukc-btn--outline ukc-btn--sm"><x-ukc.icon name="sheet" :size="15" /> Rapports</a>
        @if ($me?->canAnyEdition('participants'))
            <a href="{{ route('admin.participants.index') }}" class="ukc-btn ukc-btn--outline ukc-btn--sm"><x-ukc.icon name="users" :size="15" /> Participants</a>
        @endif
        @if ($me?->canAnyEdition('documents'))
            <a href="{{ route('admin.documents.attestations') }}" class="ukc-btn ukc-btn--outline ukc-btn--sm"><x-ukc.icon name="diploma" :size="15" /> Documents</a>
        @endif
        @if ($me?->canAnyEdition('emails'))
            <a href="{{ route('admin.emails.index') }}" class="ukc-btn ukc-btn--outline ukc-btn--sm"><x-ukc.icon name="mail" :size="15" /> E-mails</a>
        @endif
    </x-slot:actions>

    <div class="admin-page">

        {{-- Indicateurs --}}
        <div class="ukc-stats" style="margin-bottom:18px;">
            <div class="ukc-stat-card tw-stat"><div class="tw-stat__icon tw-stat__icon--amber"><x-ukc.icon name="check-circle" :size="20" /></div><div class="lbl">Rapports à valider</div><div class="val">{{ $stats['to_validate'] }}</div></div>
            <div class="ukc-stat-card tw-stat"><div class="tw-stat__icon tw-stat__icon--slate"><x-ukc.icon name="pen" :size="20" /></div><div class="lbl">En attente de rédaction</div><div class="val">{{ $stats['awaiting'] }}</div></div>
            <div class="ukc-stat-card tw-stat"><div class="tw-stat__icon tw-stat__icon--green"><x-ukc.icon name="check" :size="20" /></div><div class="lbl">Rapports validés</div><div class="val green">{{ $stats['validated'] }}</div></div>
        </div>

        @if ($editions->isEmpty())
            <div class="ukc-empty"><h3>Aucune édition accessible</h3><p>Vous n'êtes rattaché à aucune édition pour le moment.</p></div>
        @else
            {{-- ===== Rapports à valider ===== --}}
            <div class="ukc-card" style="padding:0;margin-bottom:18px;">
                <div style="padding:16px 18px 0;">
                    <h3 style="margin:0;">Rapports à valider</h3>
                    <p style="font-size:13px;color:var(--ukc-muted);margin:4px 0 0;">Rapports soumis par les rapporteurs. La validation verrouille le rapport (le rapporteur ne peut plus le modifier).</p>
                </div>
                @if ($toValidate->isNotEmpty())
                    <div class="ukc-table-wrap" style="margin-top:12px;">
                        <table class="ukc-table">
                            <thead><tr><th>Séance</th><th>Rapporteur</th><th>Soumis le</th><th style="text-align:right;">Action</th></tr></thead>
                            <tbody>
                                @foreach ($toValidate as $s)
                                    <tr>
                                        <td class="title">
                                            {{ $s->label }}
                                            <div style="font-size:12px;color:var(--ukc-muted);">{{ $s->edition?->title ?: $s->edition?->year }}@if ($s->room) · {{ $s->room }}@endif</div>
                                        </td>
                                        <td>{{ $s->rapporteur_name ?: $s->rapporteur_email }}</td>
                                        <td>{{ $s->report?->submitted_at?->translatedFormat('d/m/Y H:i') ?: '—' }}</td>
                                        <td style="text-align:right;">
                                            <div style="display:inline-flex;gap:8px;flex-wrap:wrap;justify-content:flex-end;">
                                                <a href="{{ route('admin.program.reports') }}?edition_id={{ $s->edition_id }}" class="ukc-btn ukc-btn--ghost ukc-btn--sm"><x-ukc.icon name="eye" :size="15" /> Lire / améliorer</a>
                                                <form method="POST" action="{{ route('admin.program.reports.save', $s) }}" onsubmit="return confirm('Valider ce rapport ? Le rapporteur ne pourra plus le modifier.')">
                                                    @csrf
                                                    <button type="submit" class="ukc-btn ukc-btn--green ukc-btn--sm"><x-ukc.icon name="check" :size="15" /> Valider</button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="ukc-empty" style="border:none;"><p style="margin:12px 0 18px;">Aucun rapport en attente de validation. 🎉</p></div>
                @endif
            </div>

            <div class="ukc-grid-2" style="align-items:start;">
                {{-- ===== En attente de rédaction ===== --}}
                <div class="ukc-card">
                    <h3>En attente de rédaction</h3>
                    <p style="font-size:13px;color:var(--ukc-muted);margin:4px 0 12px;">Séances commencées dont le rapport n'est pas encore soumis.</p>
                    @forelse ($awaiting as $s)
                        <div style="padding:10px 0;border-top:1px solid var(--ukc-border);display:flex;justify-content:space-between;gap:10px;flex-wrap:wrap;align-items:center;">
                            <div>
                                <div style="font-weight:600;font-size:.92rem;">{{ $s->label }}</div>
                                <div style="font-size:12.5px;color:var(--ukc-muted);">
                                    {{ $s->starts_at?->translatedFormat('d/m H:i') }} · {{ $s->rapporteur_name ?: $s->rapporteur_email }}
                                    @if ($s->report && $s->report->status === 'draft') · <span class="ukc-pill ukc-pill--warn">brouillon</span>@endif
                                </div>
                            </div>
                            @if (filled($s->rapporteur_email))
                                <a href="mailto:{{ $s->rapporteur_email }}?subject={{ rawurlencode('Rapport de séance : '.$s->label) }}" class="ukc-btn ukc-btn--outline ukc-btn--sm"><x-ukc.icon name="mail" :size="15" /> Relancer</a>
                            @endif
                        </div>
                    @empty
                        <p style="font-size:.9rem;color:var(--ukc-muted);">Rien à relancer.</p>
                    @endforelse
                </div>

                {{-- ===== Récemment validés ===== --}}
                <div class="ukc-card">
                    <h3>Récemment validés</h3>
                    @forelse ($validated as $s)
                        <div style="padding:10px 0;border-top:1px solid var(--ukc-border);">
                            <div style="font-weight:600;font-size:.92rem;">{{ $s->label }}</div>
                            <div style="font-size:12.5px;color:var(--ukc-muted);">Validé le {{ $s->report?->validated_at?->translatedFormat('d/m/Y H:i') }}</div>
                        </div>
                    @empty
                        <p style="font-size:.9rem;color:var(--ukc-muted);">Aucun rapport validé pour l'instant.</p>
                    @endforelse
                </div>
            </div>

        @endif
    </div>
</x-layouts.admin>
