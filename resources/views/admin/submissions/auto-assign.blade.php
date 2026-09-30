{{-- ⚠️ `subtitle` est une valeur PHP, échappée à l'affichage : y écrire « &amp; »
     produit un « &amp;amp; » visible. Mettre le caractère tel quel. --}}
<x-layouts.admin title="Attribution automatique" subtitle="Relecteurs - matching par axe, conflits d’intérêts & charge">
    <div class="admin-page">
        {{-- Sélecteur d'édition en tête, AVANT les onglets (convention des pages
             Documents, Participants, Paiements et Soumissions). --}}
        <div style="max-width:340px;margin-bottom:16px;">
            <div class="ukc-field" style="margin:0;">
                <label>Édition</label>
                <select onchange="window.location='{{ route('admin.submissions.auto-assign') }}?edition_id='+this.value">
                    <option value="">- Choisir -</option>
                    @foreach ($editions as $ed)<option value="{{ $ed->id }}" @selected((string) $ed->id === $edition_id)>{{ $ed->title ?: $ed->year }}</option>@endforeach
                </select>
            </div>
        </div>

        @include('admin.submissions._nav', ['current' => 'attribution'])

        {{-- Règles appliquées (elles se changent dans l'onglet Évaluation : ce sont
             des réglages de l'édition, pas des paramètres de cette page). --}}
        @if ($edition)
            <p style="margin:-4px 0 16px;font-size:13px;color:var(--ukc-muted);">
                Règles appliquées : <strong>{{ $target }}</strong> relecteur(s) par soumission ·
                charge max <strong>{{ $maxLoad > 0 ? $maxLoad : 'illimitée' }}</strong> ·
                <a href="{{ route('admin.submissions.options', ['edition_id' => $edition_id]) }}"
                   style="color:var(--ukc-blue);font-weight:600;">modifier</a>
            </p>
        @endif
        @if (! $edition)
            <div class="ukc-card"><p style="margin:0;color:var(--ukc-muted);">Sélectionnez une édition pour lancer l’attribution.</p></div>
        @elseif (empty($preview['reviewers']))
            <div class="ukc-card">
                <p style="margin:0;color:var(--ukc-muted);">Aucun relecteur sur cette édition. Assignez d’abord un relecteur depuis une soumission,
                    ou invitez des relecteurs, puis revenez ici.</p>
            </div>
        @else
            {{-- Indicateurs du plan --}}
            <div class="ukc-stats">
                <div class="ukc-stat-card tw-stat"><div class="tw-stat__icon tw-stat__icon--blue"><x-ukc.icon name="mic" :size="20" /></div><div class="lbl">Relecteurs</div><div class="val blue">{{ count($preview['reviewers']) }}</div></div>
                <div class="ukc-stat-card tw-stat"><div class="tw-stat__icon tw-stat__icon--amber"><x-ukc.icon name="inbox" :size="20" /></div><div class="lbl">À couvrir</div><div class="val warn">{{ $preview['needing'] }}</div></div>
                <div class="ukc-stat-card tw-stat"><div class="tw-stat__icon tw-stat__icon--green"><x-ukc.icon name="check-circle" :size="20" /></div><div class="lbl">Proposées</div><div class="val green">{{ count($preview['proposals']) }}</div></div>
                <div class="ukc-stat-card tw-stat"><div class="tw-stat__icon tw-stat__icon--{{ $preview['uncovered'] ? 'red' : 'green' }}"><x-ukc.icon name="close" :size="20" /></div><div class="lbl">Non couvertes</div><div class="val" style="color:{{ $preview['uncovered'] ? '#dc2626' : 'var(--ukc-green)' }};">{{ $preview['uncovered'] }}</div></div>
            </div>

            <div style="display:grid;grid-template-columns:1fr;gap:18px;">
                {{-- Plan proposé --}}
                <div class="ukc-card" style="padding:0;">
                    <div style="padding:14px 18px;border-bottom:1px solid var(--ukc-border);display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap;">
                        <div><strong>Plan proposé</strong> <span style="color:var(--ukc-muted);font-size:13px;">- décochez ce que vous ne voulez pas ; conflits d’intérêts élevés déjà exclus. « Appliquer » traite la page affichée.</span></div>
                    </div>

                    @if (empty($preview['proposals']))
                        <div style="padding:18px;color:var(--ukc-muted);">
                            Rien à proposer : toutes les soumissions éligibles sont déjà couvertes, ou aucun relecteur disponible sans conflit d’intérêt élevé.
                        </div>
                    @else
                        {{-- `n` = paires cochées sur la PAGE courante (la sélection ne
                             traverse pas les pages : chaque page s'applique séparément). --}}
                        <form method="POST" action="{{ route('admin.submissions.auto-assign.apply') }}"
                              x-data="{ n: {{ $proposalsPage->count() }} }">
                            @csrf
                            <input type="hidden" name="edition_id" value="{{ $edition->id }}">
                            <div class="ukc-table-wrap">
                                <table class="ukc-table">
                                    <thead>
                                        <tr>
                                            <th style="width:34px;"><input type="checkbox" checked
                                                @change="document.querySelectorAll('.pair-cb').forEach(c=>c.checked=$event.target.checked); n=$event.target.checked?{{ $proposalsPage->count() }}:0"></th>
                                            <x-ukc.th sort="ref" :current="$sort" :dir="$dir">Réf.</x-ukc.th>
                                            <x-ukc.th sort="title" :current="$sort" :dir="$dir">Communication</x-ukc.th>
                                            <x-ukc.th sort="track" :current="$sort" :dir="$dir">Axe</x-ukc.th>
                                            <x-ukc.th sort="reviewer" :current="$sort" :dir="$dir">Relecteur proposé</x-ukc.th>
                                            <th>Motif</th>
                                            <x-ukc.th sort="coi" :current="$sort" :dir="$dir">COI</x-ukc.th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($proposalsPage as $p)
                                            <tr>
                                                <td><input type="checkbox" class="pair-cb" name="pairs[]" value="{{ $p['submission_id'] }}:{{ $p['reviewer_id'] }}" checked
                                                    @change="n += $event.target.checked ? 1 : -1"></td>
                                                <td><code>{{ $p['submission_ref'] }}</code></td>
                                                <td class="title-cell"><strong>{{ \Illuminate\Support\Str::limit($p['submission_title'], 56) }}</strong></td>
                                                <td>@if ($p['track'])<span class="axis-tag">{{ \Illuminate\Support\Str::limit($p['track'], 22) }}</span>@else - @endif</td>
                                                <td>{{ $p['reviewer_name'] }}</td>
                                                <td style="font-size:13px;color:var(--ukc-muted);">
                                                    {{ $p['reason'] }}
                                                    {{-- La justification suit la paire : elle est archivée sur la relecture (review.assign_reason). --}}
                                                    <input type="hidden" name="reasons[{{ $p['submission_id'] }}:{{ $p['reviewer_id'] }}]" value="{{ $p['reason'] }}">
                                                </td>
                                                <td>
                                                    @if ($p['coi'] === \App\Support\ConflictOfInterest::MEDIUM)
                                                        <span class="ukc-pill ukc-pill--warn">COI moyen</span>
                                                    @elseif ($p['coi'] === \App\Support\ConflictOfInterest::LOW)
                                                        <span class="ukc-pill ukc-pill--muted">COI faible</span>
                                                    @else
                                                        <span style="color:var(--ukc-light);">-</span>
                                                    @endif
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                            <div style="padding:12px 18px 0;">{{ $proposalsPage->links() }}</div>
                            <div style="padding:14px 18px;border-top:1px solid var(--ukc-border);display:flex;justify-content:flex-end;">
                                <button type="submit" class="ukc-btn ukc-btn--primary" :disabled="n===0">
                                    <x-ukc.icon name="sparkles" :size="16" /> Appliquer <span x-text="n"></span> assignation(s)
                                </button>
                            </div>
                        </form>
                    @endif
                </div>

                {{-- Charge des relecteurs : ce qui éclaire le plan (dont « Proposé »).
                     L'expertise elle-même se règle dans l'onglet dédié. --}}
                <div class="ukc-card" style="padding:0;">
                    <div style="padding:14px 18px;border-bottom:1px solid var(--ukc-border);display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap;">
                        <div><strong>Charge des relecteurs</strong> <span style="color:var(--ukc-muted);font-size:13px;">- la charge ne compte que les relectures en cours ou rendues.</span></div>
                        <a class="ukc-btn ukc-btn--ghost ukc-btn--sm" href="{{ route('admin.submissions.reviewers', ['edition_id' => $edition_id]) }}">Gérer l’expertise</a>
                    </div>
                    <div class="ukc-table-wrap">
                        <table class="ukc-table">
                            <thead><tr><th>Relecteur</th><th>Axes d’expertise</th><th style="text-align:center;">Charge</th><th style="text-align:center;">Proposé</th></tr></thead>
                            <tbody>
                                @forelse ($preview['reviewers'] as $rev)
                                    <tr>
                                        <td><strong>{{ $rev['name'] }}</strong></td>
                                        <td>
                                            @forelse ($rev['tracks'] as $t)<span class="axis-tag">{{ \Illuminate\Support\Str::limit($t, 26) }}</span> @empty
                                                <span style="color:var(--ukc-light);font-size:13px;">Généraliste</span>
                                            @endforelse
                                        </td>
                                        <td style="text-align:center;">{{ $rev['current'] }}</td>
                                        <td style="text-align:center;">@if ($rev['proposed'])<span class="ukc-pill ukc-pill--info">+{{ $rev['proposed'] }}</span>@else - @endif</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="4" style="color:var(--ukc-muted);">Aucun relecteur.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        @endif
    </div>
</x-layouts.admin>
