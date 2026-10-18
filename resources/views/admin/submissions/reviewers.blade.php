<x-layouts.admin title="Évaluateurs & expertise" subtitle="Axes évaluables par évaluateur et charge courante">
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
            <div class="ukc-card"><p style="margin:0;color:var(--ukc-muted);">Sélectionnez un événement pour gérer l’expertise des évaluateurs.</p></div>
        @else
            <div class="ukc-stats">
                <div class="ukc-stat-card tw-stat"><div class="tw-stat__icon tw-stat__icon--blue"><x-ukc.icon name="mic" :size="20" /></div><div class="lbl">Évaluateurs</div><div class="val blue">{{ $reviewers->count() }}</div></div>
                <div class="ukc-stat-card tw-stat"><div class="tw-stat__icon tw-stat__icon--slate"><x-ukc.icon name="tag" :size="20" /></div><div class="lbl">Axes de l’événement</div><div class="val">{{ count($reviewerAxes) }}</div></div>
                <div class="ukc-stat-card tw-stat">
                    <div class="tw-stat__icon tw-stat__icon--{{ count($uncoveredAxes) ? 'red' : 'green' }}"><x-ukc.icon name="shield" :size="20" /></div>
                    <div class="lbl">Axes sans évaluateur</div>
                    <div class="val" style="{{ count($uncoveredAxes) ? 'color:#dc2626;' : 'color:var(--ukc-green);' }}">{{ count($uncoveredAxes) }}</div>
                </div>
                <div class="ukc-stat-card tw-stat"><div class="tw-stat__icon tw-stat__icon--amber"><x-ukc.icon name="chart" :size="20" /></div><div class="lbl">Charge moyenne</div><div class="val">{{ number_format($avgLoad, 1, ',', ' ') }}</div></div>
            </div>

            @if ($canWrite)
                <form method="POST" action="{{ route('admin.evenements.reviewers.store', $event) }}" class="ukc-card" style="margin-bottom:16px;">
                    @csrf
                    <h2 style="margin:0 0 6px;font-size:1.05rem;">Ajouter un évaluateur à cet événement</h2>
                    <p style="margin:0 0 16px;color:var(--ukc-muted);">Enregistrez-le ici avant de lui attribuer une soumission. L'invitation à évaluer sera envoyée lors de l'attribution.</p>
                    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(210px,1fr));gap:12px;">
                        <div class="ukc-field">
                            <label for="reviewerName">Nom complet</label>
                            <input id="reviewerName" name="reviewerName" type="text" value="{{ old('reviewerName') }}" required maxlength="200" autocomplete="name" placeholder="Prénom Nom">
                            @error('reviewerName') <span class="error">{{ $message }}</span> @enderror
                        </div>
                        <div class="ukc-field">
                            <label for="reviewerEmail">E-mail</label>
                            <input id="reviewerEmail" name="reviewerEmail" type="email" value="{{ old('reviewerEmail') }}" required maxlength="200" autocomplete="email" placeholder="evaluateur@exemple.tg">
                            @error('reviewerEmail') <span class="error">{{ $message }}</span> @enderror
                        </div>
                        <div class="ukc-field">
                            <label for="reviewerInstitution">Institution <span style="color:var(--ukc-muted);font-weight:400;">(facultatif)</span></label>
                            <input id="reviewerInstitution" name="reviewerInstitution" type="text" value="{{ old('reviewerInstitution') }}" maxlength="255" placeholder="Université ou organisme">
                            @error('reviewerInstitution') <span class="error">{{ $message }}</span> @enderror
                        </div>
                    </div>
                    @if ($reviewerAxes !== [])
                        <div class="ukc-field" style="margin-top:14px;">
                            <label>Axes d'expertise <span style="color:var(--ukc-muted);font-weight:400;">(facultatif)</span></label>
                            <div class="reviewer-axes reviewer-axes--chips">
                                @foreach ($reviewerAxes as $axis)
                                    <label class="axis-check" title="{{ $axis }}">
                                        <input type="checkbox" name="reviewerTracks[]" value="{{ $axis }}" @checked(in_array($axis, (array) old('reviewerTracks', []), true))>
                                        <span>{{ \Illuminate\Support\Str::limit($axis, 34) }}</span>
                                    </label>
                                @endforeach
                            </div>
                            @if ($errors->has('reviewerTracks.*')) <span class="error">{{ $errors->first('reviewerTracks.*') }}</span> @endif
                        </div>
                    @endif
                    <div style="display:flex;justify-content:flex-end;margin-top:16px;">
                        <button type="submit" class="ukc-btn ukc-btn--blue"><x-ukc.icon name="user-plus" :size="16" /> Ajouter l'évaluateur</button>
                    </div>
                </form>
            @endif

            @if (count($uncoveredAxes))
                <div class="ukc-alert ukc-alert--warn" style="margin-bottom:16px;">
                    <span class="ico"><x-ukc.icon name="info" :size="18" /></span>
                    <div>
                        <strong>{{ count($uncoveredAxes) }} axe(s) sans évaluateur déclaré.</strong>
                        L’attribution automatique y proposera un évaluateur « hors axe ».
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
                        <strong>Évaluateurs de cet événement</strong>
                        <span style="color:var(--ukc-muted);font-size:13px;">- gérez leur expertise avant de les attribuer aux soumissions.</span>
                    </div>

                    @if ($reviewers->isEmpty())
                        <div style="padding:22px;color:var(--ukc-muted);">
                            @if ($canWrite)
                                Aucun évaluateur sur cet événement. Ajoutez-en un avec le formulaire ci-dessus pour le rendre disponible lors de l'attribution.
                            @else
                                Aucun évaluateur sur cet événement. Demandez à un responsable de l'événement d'en ajouter.
                            @endif
                        </div>
                    @else
                        @if ($reviewerAxes === [])
                            <p style="padding:14px 18px;margin:0;color:var(--ukc-muted);">
                                Aucun axe thématique défini. Vous pouvez déjà attribuer ces évaluateurs aux soumissions ;
                                renseignez les axes dans <a href="{{ route('admin.evenements.edit', $event) }}" style="color:var(--ukc-blue);font-weight:600;">Appel &amp; contenu</a> pour préciser leur expertise.
                            </p>
                        @endif
                        <div class="ukc-table-wrap">
                            <table class="ukc-table">
                                <thead><tr><th style="min-width:200px;">Évaluateur</th><th>Axes d’expertise</th><th style="text-align:center;">Charge</th></tr></thead>
                                <tbody>
                                    @foreach ($reviewers as $rev)
                                        <tr>
                                            <td>
                                                <strong>{{ $rev['name'] }}</strong>
                                                <div style="color:var(--ukc-muted);font-size:12px;">{{ $rev['email'] }}</div>
                                                <div style="color:var(--ukc-muted);font-size:12px;">{{ $rev['institution'] ?: '-' }}</div>
                                                <input type="hidden" name="reviewer_ids[]" value="{{ $rev['id'] }}">
                                            </td>
                                            <td>
                                                @if ($reviewerAxes === [])
                                                    <span style="color:var(--ukc-muted);">Aucun axe défini</span>
                                                @else
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
                                                @endif
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
                    Un évaluateur sans aucun axe coché est traité comme <em>généraliste</em>.
                    La <strong>charge</strong> ne compte que les évaluations en cours ou rendues.
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
