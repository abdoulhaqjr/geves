<x-layouts.admin title="Relecteurs & expertise" subtitle="Axes évaluables par relecteur et charge courante">
    <div class="admin-page admin-submissions">

        <div class="page-head" style="display:flex;align-items:flex-end;justify-content:space-between;gap:16px;flex-wrap:wrap;margin-bottom:14px;">
            <div class="ukc-field" style="min-width:320px;max-width:460px;margin:0;">
                <label for="edition_id">Édition</label>
                <select id="edition_id" name="edition_id" onchange="window.location = '{{ route('admin.submissions.reviewers') }}?edition_id=' + this.value">
                    <option value="">- Choisir -</option>
                    @foreach ($editions as $ed)<option value="{{ $ed->id }}" @selected((string) $ed->id === $edition_id)>{{ $ed->title ?: $ed->year }}</option>@endforeach
                </select>
            </div>
        </div>

        @include('admin.submissions._nav', ['current' => 'relecteurs'])

        @if (! $edition)
            <div class="ukc-card"><p style="margin:0;color:var(--ukc-muted);">Sélectionnez une édition pour gérer l’expertise des relecteurs.</p></div>
        @else
            <form method="POST" action="{{ route('admin.editions.reviewers.expertise', $edition) }}">
                @csrf
                <div class="ukc-card" style="padding:0;">
                    <div style="padding:14px 18px;border-bottom:1px solid var(--ukc-border);">
                        <strong>Relecteurs &amp; expertise</strong>
                        <span style="color:var(--ukc-muted);font-size:13px;">
                            - cochez les axes que chaque relecteur peut évaluer : l’attribution automatique priorise
                            un relecteur dont l’expertise couvre l’axe de la soumission. Un relecteur sans aucun axe
                            est traité comme <em>généraliste</em>. La <strong>charge</strong> ne compte que les
                            relectures en cours ou rendues.
                        </span>
                    </div>

                    @if (empty($reviewerAxes))
                        <div style="padding:22px;color:var(--ukc-muted);">
                            Aucun axe thématique défini pour cette édition. Renseignez les axes dans l’onglet
                            <strong>Appel &amp; contenu</strong> de la
                            <a href="{{ route('admin.editions.edit', $edition) }}" style="color:var(--ukc-blue);font-weight:600;">fiche d’édition</a>,
                            puis revenez ici.
                        </div>
                    @elseif ($reviewers->isEmpty())
                        <div style="padding:22px;color:var(--ukc-muted);">
                            Aucun relecteur sur cette édition. Assignez un relecteur depuis une soumission
                            (ou depuis la page d’attribution), il apparaîtra ici.
                        </div>
                    @else
                        <div class="ukc-table-wrap">
                            <table class="ukc-table">
                                <thead><tr><th style="min-width:200px;">Relecteur</th><th>Axes d’expertise</th><th style="text-align:center;">Charge</th></tr></thead>
                                <tbody>
                                    @foreach ($reviewers as $rev)
                                        <tr>
                                            <td>
                                                <strong>{{ $rev['name'] }}</strong>
                                                <div style="color:var(--ukc-muted);font-size:12px;">{{ $rev['institution'] ?: '-' }}</div>
                                                <input type="hidden" name="reviewer_ids[]" value="{{ $rev['id'] }}">
                                            </td>
                                            <td>
                                                <div class="reviewer-axes">
                                                    @foreach ($reviewerAxes as $axis)
                                                        <label class="axis-check">
                                                            <input type="checkbox" name="expertise[{{ $rev['id'] }}][]" value="{{ $axis }}"
                                                                   @checked(\App\Support\ReviewerExpertise::covers($rev['tracks'], $axis))
                                                                   @disabled(! $canWrite)>
                                                            <span>{{ $axis }}</span>
                                                        </label>
                                                    @endforeach
                                                </div>
                                            </td>
                                            <td style="text-align:center;font-weight:700;">{{ $rev['load'] }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>

                @if ($canWrite && ! empty($reviewerAxes) && $reviewers->isNotEmpty())
                    <div class="ukc-form-actions" style="margin-top:14px;text-align:right;">
                        <button type="submit" class="ukc-btn ukc-btn--blue">Enregistrer les expertises</button>
                    </div>
                @elseif (! $canWrite)
                    <p class="hint" style="margin-top:10px;font-size:12.5px;color:var(--ukc-muted);">
                        Lecture seule : modifier l’expertise demande le droit d’écriture sur l’édition.
                    </p>
                @endif
            </form>
        @endif
    </div>
</x-layouts.admin>
