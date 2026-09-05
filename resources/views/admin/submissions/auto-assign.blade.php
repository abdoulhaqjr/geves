<x-layouts.admin title="Attribution automatique" subtitle="Relecteurs — matching par axe, conflits d’intérêts &amp; charge">
    <x-slot:actions>
        <a class="ukc-btn ukc-btn--ghost ukc-btn--sm" href="{{ route('admin.submissions.index', ['edition_id' => $edition_id]) }}">
            ← Retour aux soumissions
        </a>
    </x-slot:actions>

    <div class="admin-page">
        <div class="ukc-nav-docs">
            <a href="{{ route('admin.submissions.index', ['edition_id' => $edition_id]) }}">Soumissions</a>
            <button type="button" class="active">Attribution auto</button>
        </div>

        {{-- Réglages : édition, nombre de relecteurs visé, plafond par relecteur --}}
        <form method="GET" class="filter-bar">
            <select name="edition_id" onchange="this.form.submit()">
                @foreach ($editions as $ed)<option value="{{ $ed->id }}" @selected((string) $ed->id === $edition_id)>{{ $ed->title ?: $ed->year }}</option>@endforeach
            </select>
            <label style="display:flex;align-items:center;gap:6px;font-size:13px;color:var(--ukc-muted);">
                Relecteurs / soumission
                <input type="number" name="target" min="1" max="5" value="{{ $target }}" style="width:64px;" onchange="this.form.submit()">
            </label>
            <label style="display:flex;align-items:center;gap:6px;font-size:13px;color:var(--ukc-muted);">
                Charge max / relecteur
                <input type="number" name="max_load" min="0" max="50" value="{{ $maxLoad }}" style="width:64px;" onchange="this.form.submit()" title="0 = illimitée">
            </label>
        </form>

        @if (! $edition)
            <div class="ukc-card"><p style="margin:0;color:var(--ukc-muted);">Sélectionnez une édition pour lancer l’attribution.</p></div>
        @elseif ($reviewers->isEmpty())
            <div class="ukc-card">
                <p style="margin:0;color:var(--ukc-muted);">Aucun relecteur sur cette édition. Assignez d’abord un relecteur depuis une soumission,
                    ou invitez des relecteurs, puis revenez ici.</p>
            </div>
        @else
            {{-- Indicateurs du plan --}}
            <div class="ukc-stats">
                <div class="ukc-stat-card"><div class="lbl">Relecteurs</div><div class="val blue">{{ $reviewers->count() }}</div></div>
                <div class="ukc-stat-card"><div class="lbl">À couvrir</div><div class="val warn">{{ $preview['needing'] }}</div></div>
                <div class="ukc-stat-card"><div class="lbl">Proposées</div><div class="val green">{{ count($preview['proposals']) }}</div></div>
                <div class="ukc-stat-card"><div class="lbl">Non couvertes</div><div class="val" style="color:{{ $preview['uncovered'] ? '#dc2626' : 'var(--ukc-green)' }};">{{ $preview['uncovered'] }}</div></div>
            </div>

            <div style="display:grid;grid-template-columns:1fr;gap:18px;">
                {{-- Plan proposé --}}
                <div class="ukc-card" style="padding:0;">
                    <div style="padding:14px 18px;border-bottom:1px solid var(--ukc-border);display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap;">
                        <div><strong>Plan proposé</strong> <span style="color:var(--ukc-muted);font-size:13px;">— décochez ce que vous ne voulez pas ; conflits d’intérêts élevés déjà exclus.</span></div>
                    </div>

                    @if (empty($preview['proposals']))
                        <div style="padding:18px;color:var(--ukc-muted);">
                            Rien à proposer : toutes les soumissions éligibles sont déjà couvertes, ou aucun relecteur disponible sans conflit d’intérêt élevé.
                        </div>
                    @else
                        <form method="POST" action="{{ route('admin.submissions.auto-assign.apply') }}"
                              x-data="{ n: {{ count($preview['proposals']) }} }">
                            @csrf
                            <input type="hidden" name="edition_id" value="{{ $edition->id }}">
                            <div class="ukc-table-wrap">
                                <table class="ukc-table">
                                    <thead>
                                        <tr>
                                            <th style="width:34px;"><input type="checkbox" checked
                                                @change="document.querySelectorAll('.pair-cb').forEach(c=>c.checked=$event.target.checked); n=$event.target.checked?{{ count($preview['proposals']) }}:0"></th>
                                            <th>Réf.</th><th>Communication</th><th>Axe</th><th>Relecteur proposé</th><th>Motif</th><th>COI</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($preview['proposals'] as $p)
                                            <tr>
                                                <td><input type="checkbox" class="pair-cb" name="pairs[]" value="{{ $p['submission_id'] }}:{{ $p['reviewer_id'] }}" checked
                                                    @change="n += $event.target.checked ? 1 : -1"></td>
                                                <td><code>{{ $p['submission_ref'] }}</code></td>
                                                <td class="title-cell"><strong>{{ \Illuminate\Support\Str::limit($p['submission_title'], 56) }}</strong></td>
                                                <td>@if ($p['track'])<span class="axis-tag">{{ \Illuminate\Support\Str::limit($p['track'], 22) }}</span>@else — @endif</td>
                                                <td>{{ $p['reviewer_name'] }}</td>
                                                <td style="font-size:13px;color:var(--ukc-muted);">{{ $p['reason'] }}</td>
                                                <td>
                                                    @if ($p['coi'] === \App\Support\ConflictOfInterest::MEDIUM)
                                                        <span class="ukc-pill ukc-pill--warn">COI moyen</span>
                                                    @elseif ($p['coi'] === \App\Support\ConflictOfInterest::LOW)
                                                        <span class="ukc-pill ukc-pill--muted">COI faible</span>
                                                    @else
                                                        <span style="color:var(--ukc-light);">—</span>
                                                    @endif
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                            <div style="padding:14px 18px;border-top:1px solid var(--ukc-border);display:flex;justify-content:flex-end;">
                                <button type="submit" class="ukc-btn ukc-btn--primary" :disabled="n===0">
                                    <x-ukc.icon name="sparkles" :size="16" /> Appliquer <span x-text="n"></span> assignation(s)
                                </button>
                            </div>
                        </form>
                    @endif
                </div>

                {{-- Expertise des relecteurs (affine le matching) --}}
                <div class="ukc-card" style="padding:0;">
                    <div style="padding:14px 18px;border-bottom:1px solid var(--ukc-border);">
                        <strong>Expertise des relecteurs</strong>
                        <span style="color:var(--ukc-muted);font-size:13px;">— cochez les axes que chaque relecteur peut évaluer, puis relancez l’aperçu.
                        @if ($axes->isEmpty()) <em>Aucun axe défini pour cette édition.</em> @endif</span>
                    </div>
                    <div class="ukc-table-wrap">
                        <table class="ukc-table">
                            <thead><tr><th>Relecteur</th><th>Charge</th><th>Proposé</th><th>Axes d’expertise</th><th></th></tr></thead>
                            <tbody>
                                @php $loadById = collect($preview['reviewers'])->keyBy('id'); @endphp
                                @foreach ($reviewers as $rev)
                                    @php $info = $loadById[$rev->id] ?? ['current' => 0, 'proposed' => 0, 'tracks' => []]; @endphp
                                    <tr>
                                        <td><strong>{{ $rev->full_name }}</strong><div class="meta" style="font-size:12px;color:var(--ukc-muted);">{{ $rev->institution ?: '—' }}</div></td>
                                        <td>{{ $info['current'] }}</td>
                                        <td>@if ($info['proposed'])<span class="ukc-pill ukc-pill--info">+{{ $info['proposed'] }}</span>@else — @endif</td>
                                        <td colspan="2">
                                            @if ($axes->isEmpty())
                                                <span style="color:var(--ukc-light);">—</span>
                                            @else
                                                <form method="POST" action="{{ route('admin.submissions.reviewer.expertise', $rev->id) }}"
                                                      style="display:flex;flex-wrap:wrap;gap:8px 14px;align-items:center;">
                                                    @csrf
                                                    @php $mine = collect($info['tracks']); @endphp
                                                    @foreach ($axes as $axis)
                                                        <label style="display:inline-flex;align-items:center;gap:5px;font-size:13px;">
                                                            <input type="checkbox" name="tracks[]" value="{{ $axis }}" @checked($mine->contains($axis))>
                                                            {{ \Illuminate\Support\Str::limit($axis, 30) }}
                                                        </label>
                                                    @endforeach
                                                    <button type="submit" class="ukc-btn ukc-btn--ghost ukc-btn--sm">Enregistrer</button>
                                                </form>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        @endif
    </div>
</x-layouts.admin>
