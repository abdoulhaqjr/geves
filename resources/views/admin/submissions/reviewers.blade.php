<x-layouts.admin title="Relecteurs & expertise" subtitle="Axes évaluables par relecteur et charge courante">
    <div class="admin-page admin-submissions">

        <div class="page-head" style="display:flex;align-items:flex-end;justify-content:space-between;gap:16px;flex-wrap:wrap;margin-bottom:14px;">
            <div class="ukc-field" style="min-width:320px;max-width:460px;margin:0;">
                <label for="event_id">Événement</label>
                <select id="event_id" name="event_id" onchange="window.location = '{{ route('admin.submissions.reviewers') }}?event_id=' + this.value">
                    <option value="">- Choisir -</option>
                    @foreach ($events as $ed)<option value="{{ $ed->id }}" @selected((string) $ed->id === $event_id)>{{ $ed->title ?: $ed->year }}</option>@endforeach
                </select>
            </div>
        </div>

        @include('admin.submissions._nav', ['current' => 'relecteurs'])

        @if (! $event)
            <div class="ukc-card"><p style="margin:0;color:var(--ukc-muted);">Sélectionnez un événement pour gérer l’expertise des relecteurs.</p></div>
        @else
            <div class="ukc-stats">
                <div class="ukc-stat-card tw-stat"><div class="tw-stat__icon tw-stat__icon--blue"><x-ukc.icon name="mic" :size="20" /></div><div class="lbl">Relecteurs</div><div class="val blue">{{ $reviewers->count() }}</div></div>
                <div class="ukc-stat-card tw-stat"><div class="tw-stat__icon tw-stat__icon--slate"><x-ukc.icon name="tag" :size="20" /></div><div class="lbl">Axes de l’événement</div><div class="val">{{ count($reviewerAxes) }}</div></div>
                <div class="ukc-stat-card tw-stat">
                    <div class="tw-stat__icon tw-stat__icon--{{ count($uncoveredAxes) ? 'red' : 'green' }}"><x-ukc.icon name="shield" :size="20" /></div>
                    <div class="lbl">Axes sans relecteur</div>
                    <div class="val" style="{{ count($uncoveredAxes) ? 'color:#dc2626;' : 'color:var(--ukc-green);' }}">{{ count($uncoveredAxes) }}</div>
                </div>
                <div class="ukc-stat-card tw-stat"><div class="tw-stat__icon tw-stat__icon--amber"><x-ukc.icon name="chart" :size="20" /></div><div class="lbl">Charge moyenne</div><div class="val">{{ number_format($avgLoad, 1, ',', ' ') }}</div></div>
            </div>

            @if (count($uncoveredAxes))
                <div class="ukc-alert ukc-alert--warn" style="margin-bottom:16px;">
                    <span class="ico"><x-ukc.icon name="info" :size="18" /></span>
                    <div>
                        <strong>{{ count($uncoveredAxes) }} axe(s) sans relecteur déclaré.</strong>
                        L’attribution automatique y proposera un relecteur « hors axe ».
                        <div style="margin-top:6px;display:flex;flex-wrap:wrap;gap:6px;">
                            @foreach ($uncoveredAxes as $axis)
                                <span class="axis-tag" style="background:#fee2e2;color:#991b1b;">{{ \Illuminate\Support\Str::limit($axis, 42) }}</span>
                            @endforeach
                        </div>
                    </div>
                </div>
            @endif
            <form method="POST" action="{{ route('admin.evenements.reviewers.expertise', $event) }}">
                @csrf
                <div class="ukc-card" style="padding:0;">
                    <div style="padding:14px 18px;border-bottom:1px solid var(--ukc-border);">
                        <strong>Axes évaluables par relecteur</strong>
                        <span style="color:var(--ukc-muted);font-size:13px;">- l’attribution automatique priorise un relecteur dont l’expertise couvre l’axe de la soumission.</span>
                    </div>

                    @if (empty($reviewerAxes))
                        <div style="padding:22px;color:var(--ukc-muted);">
                            Aucun axe thématique défini pour cet événement. Renseignez les axes dans l’onglet
                            <strong>Appel &amp; contenu</strong> de la
                            <a href="{{ route('admin.evenements.edit', $event) }}" style="color:var(--ukc-blue);font-weight:600;">fiche d’événement</a>,
                            puis revenez ici.
                        </div>
                    @elseif ($reviewers->isEmpty())
                        <div style="padding:22px;color:var(--ukc-muted);">
                            Aucun relecteur sur cet événement. Assignez un relecteur depuis une soumission
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
                                                <div class="reviewer-axes reviewer-axes--chips">
                                                    @foreach ($reviewerAxes as $axis)
                                                        <label class="axis-check" title="{{ $axis }}">
                                                            <input type="checkbox" name="expertise[{{ $rev['id'] }}][]" value="{{ $axis }}"
                                                                   @checked(\App\Support\ReviewerExpertise::covers($rev['tracks'], $axis))
                                                                   @disabled(! $canWrite)>
                                                            <span>{{ \Illuminate\Support\Str::limit($axis, 34) }}</span>
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

                <p class="hint" style="margin:10px 2px 0;font-size:12.5px;color:var(--ukc-muted);">
                    Un relecteur sans aucun axe coché est traité comme <em>généraliste</em>.
                    La <strong>charge</strong> ne compte que les relectures en cours ou rendues.
                </p>

                @if ($canWrite && ! empty($reviewerAxes) && $reviewers->isNotEmpty())
                    <div class="ukc-form-actions" style="margin-top:14px;text-align:right;">
                        <button type="submit" class="ukc-btn ukc-btn--blue">Enregistrer les expertises</button>
                    </div>
                @elseif (! $canWrite)
                    <p class="hint" style="margin-top:10px;font-size:12.5px;color:var(--ukc-muted);">
                        Lecture seule : modifier l’expertise demande le droit d’écriture sur l’événement.
                    </p>
                @endif
            </form>
        @endif
    </div>
</x-layouts.admin>
