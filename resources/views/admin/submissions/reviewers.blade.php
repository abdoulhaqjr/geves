<x-layouts.admin title="Évaluateurs & expertise" subtitle="Axes évaluables par évaluateur et charge courante">
    <div class="admin-page admin-submissions" x-data="{
        reviewerOpen: @js($errors->has('reviewerFirstName') || $errors->has('reviewerLastName') || $errors->has('reviewerEmail') || $errors->has('reviewerInstitution') || $errors->has('reviewerTracks') || $errors->has('reviewerTracks.*')),
        query: '', email: @js(old('reviewerEmail', '')), firstName: @js(old('reviewerFirstName', '')),
        lastName: @js(old('reviewerLastName', '')), institution: @js(old('reviewerInstitution', '')),
        selectedId: '', selectedName: '', alreadyRegistered: false,
        lockedFirstName: false, lockedLastName: false,
        results: [], searching: false, searched: false,
        lookupSeq: 0,
        searchPeople() {
            const q = this.query.trim();
            const seq = ++this.lookupSeq;
            if (this.selectedId && q === this.email) return;
            if (this.selectedId) {
                this.selectedId = ''; this.selectedName = ''; this.alreadyRegistered = false;
                this.lockedFirstName = false; this.lockedLastName = false;
                this.email = ''; this.firstName = ''; this.lastName = ''; this.institution = '';
            }
            if (q.includes('@')) this.email = q;
            if (q.length < 2) { this.results = []; this.searching = false; this.searched = false; return; }
            this.searching = true; this.searched = false;
            fetch(@js($event ? route('admin.evenements.reviewers.people', $event) : '' ) + '?q=' + encodeURIComponent(q), { headers: { Accept: 'application/json' } })
                .then(r => r.ok ? r.json() : Promise.reject())
                .then(d => { if (seq === this.lookupSeq) { this.results = d.people || []; this.searched = true; } })
                .catch(() => { if (seq === this.lookupSeq) { this.results = []; this.searched = false; } })
                .finally(() => { if (seq === this.lookupSeq) this.searching = false; });
        },
        pickPerson(person) {
            this.lookupSeq++;
            this.selectedId = person.id;
            this.selectedName = person.name;
            this.alreadyRegistered = !!person.already_registered;
            this.query = person.email;
            this.email = person.email;
            this.firstName = person.first_name;
            this.lastName = person.last_name;
            this.lockedFirstName = !!person.first_name;
            this.lockedLastName = !!person.last_name;
            this.institution = person.institution || this.institution;
            this.results = [];
            this.searching = false; this.searched = false;
        },
        clearPerson() {
            this.selectedId = ''; this.selectedName = ''; this.alreadyRegistered = false; this.query = '';
            this.lockedFirstName = false; this.lockedLastName = false;
            this.email = ''; this.firstName = ''; this.lastName = '';
            this.institution = ''; this.results = []; this.searching = false; this.searched = false;
            this.$nextTick(() => this.$refs.reviewerSearch?.focus());
        },
        confirmReviewerRemoval(event) {
            const button = event.currentTarget;
            window.ukcConfirm(
                `Retirer « ${button.dataset.reviewerName} » des évaluateurs de cet événement ? Les affectations en attente seront annulées. Les évaluations rendues resteront dans l’historique.`,
                { title: 'Retirer un évaluateur', confirmLabel: 'Retirer', danger: true, wide: true },
            ).then(confirmed => {
                if (confirmed) document.getElementById(button.dataset.formId)?.requestSubmit();
            });
        },
    }">

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
            @if ($canWrite)
                <div style="display:flex;justify-content:flex-end;margin-bottom:14px;">
                    <button type="button" class="ukc-btn ukc-btn--blue" @click="reviewerOpen = true; $nextTick(() => $refs.reviewerSearch?.focus())">
                        <x-ukc.icon name="user-plus" :size="16" /> Ajouter un évaluateur
                    </button>
                </div>
            @endif

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

            @if ($pendingApplications->isNotEmpty())
                <div class="ukc-card" style="margin:18px 0;">
                    <h2>Demandes d’évaluateurs <span class="ukc-pill">{{ $pendingApplications->count() }} en attente</span></h2>
                    <p class="desc">Ces participants ne peuvent pas encore recevoir de soumissions à évaluer.</p>
                    <div class="ukc-table-wrap">
                        <table class="ukc-table">
                            <thead><tr><th>Participant</th><th>Grade</th><th>Statut</th><th>Action</th></tr></thead>
                            <tbody>
                                @foreach ($pendingApplications as $application)
                                    <tr>
                                        <td><strong>{{ $application->user?->name }}</strong><div style="color:var(--ukc-muted);font-size:12px;">{{ $application->user?->email }}</div></td>
                                        <td>{{ $application->grade }}</td>
                                        <td><span class="ukc-pill">Non confirmé - en attente d’acceptation</span></td>
                                        <td>
                                            @if ($canWrite)
                                                <form method="POST" action="{{ route('admin.evenements.reviewers.applications.accept', [$event, $application]) }}">
                                                    @csrf
                                                    <button class="ukc-btn ukc-btn--blue ukc-btn--sm" type="submit">Accepter</button>
                                                </form>
                                            @else
                                                <span style="color:var(--ukc-muted);">Lecture seule</span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
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
                                Aucun évaluateur sur cet événement. Utilisez « Ajouter un évaluateur » pour le rendre disponible lors de l'attribution.
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
                                <thead><tr><th style="min-width:200px;">Évaluateur</th><th>Axes d’expertise</th><th>Statut</th><th style="text-align:center;">Charge</th>@if ($canWrite)<th>Action</th>@endif</tr></thead>
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
                                            <td><span class="ukc-pill">Confirmé</span></td>
                                            <td style="text-align:center;font-weight:700;">{{ $rev['load'] }}</td>
                                            @if ($canWrite)
                                                <td style="white-space:nowrap;">
                                                    <button type="submit" form="reviewer-resend-{{ $rev['id'] }}" class="ukc-btn ukc-btn--ghost ukc-btn--sm">Renvoyer l’e-mail</button>
                                                    <button type="button" class="ukc-btn ukc-btn--danger ukc-btn--sm"
                                                            data-reviewer-name="{{ $rev['name'] }}" data-form-id="reviewer-remove-{{ $rev['id'] }}"
                                                            @click="confirmReviewerRemoval($event)">Retirer</button>
                                                </td>
                                            @endif
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

            @if ($canWrite)
                @foreach ($reviewers as $rev)
                    <form id="reviewer-resend-{{ $rev['id'] }}" method="POST" action="{{ route('admin.evenements.reviewers.resend', [$event, $rev['id']]) }}">
                        @csrf
                    </form>
                    <form id="reviewer-remove-{{ $rev['id'] }}" method="POST" action="{{ route('admin.evenements.reviewers.remove', [$event, $rev['id']]) }}">
                        @csrf @method('DELETE')
                    </form>
                @endforeach
            @endif

            @if ($canWrite)
                <x-ukc.slideover open="reviewerOpen" onClose="reviewerOpen = false" title="Ajouter un évaluateur" width="500px">
                    <form method="POST" action="{{ route('admin.evenements.reviewers.store', $event) }}" id="reviewerForm">
                        @csrf
                        <p style="margin:0 0 18px;color:var(--ukc-muted);">Recherchez un compte par e-mail, nom ou prénom. Si aucun compte ne correspond, renseignez les champs manuellement.</p>

                        <div class="ukc-form-row">
                            <label for="reviewerSearch">Rechercher une personne</label>
                            <div class="ukc-combo" style="position:relative;" @click.outside="results = []">
                                <input id="reviewerSearch" x-ref="reviewerSearch" type="text" x-model="query" autocomplete="off"
                                       placeholder="E-mail, nom ou prénom" @input.debounce.250ms="searchPeople()"
                                       @focus="if (query.trim().length >= 2 && !selectedId) searchPeople()">
                                <ul class="ukc-combo-menu" x-show="results.length > 0" x-cloak>
                                    <template x-for="person in results" :key="person.id">
                                        <li @mousedown.prevent="pickPerson(person)">
                                            <strong x-text="person.name"></strong>
                                            <span x-text="person.email"></span>
                                            <span x-show="person.already_registered" style="color:var(--ukc-green);">Déjà enregistré</span>
                                        </li>
                                    </template>
                                </ul>
                            </div>
                            <small x-show="searching" x-cloak>Recherche en cours…</small>
                            <small x-show="searched && !searching && results.length === 0 && !selectedId" x-cloak>Aucun compte correspondant. Vous pouvez renseigner les champs ci-dessous.</small>
                            <small x-show="selectedId" x-cloak style="color:var(--ukc-green);">
                                Compte sélectionné : <span x-text="selectedName"></span>.
                                <button type="button" class="ukc-link" style="color:var(--ukc-blue);font-weight:700;text-decoration:underline;text-underline-offset:2px;cursor:pointer;background:none;border:0;padding:0;" @click="clearPerson()">Changer</button>
                            </small>
                            <small x-show="alreadyRegistered" x-cloak style="color:var(--ukc-muted);">Cette personne figure déjà parmi les évaluateurs de cet événement.</small>
                        </div>

                        <input type="hidden" name="reviewerUserId" x-model="selectedId">
                        <div class="ukc-form-row">
                            <label for="reviewerEmail">E-mail</label>
                            <input id="reviewerEmail" name="reviewerEmail" type="email" x-model="email" required maxlength="200"
                                   :readonly="!!selectedId" @input="selectedId = ''; selectedName = ''" placeholder="evaluateur@exemple.tg">
                            @error('reviewerEmail') <span class="error">{{ $message }}</span> @enderror
                        </div>
                        <div class="ukc-form-grid">
                            <div class="ukc-form-row">
                                <label for="reviewerFirstName">Prénom</label>
                                <input id="reviewerFirstName" name="reviewerFirstName" type="text" x-model="firstName" required maxlength="150"
                                       :readonly="!!selectedId && lockedFirstName" placeholder="Prénom">
                                @error('reviewerFirstName') <span class="error">{{ $message }}</span> @enderror
                            </div>
                            <div class="ukc-form-row">
                                <label for="reviewerLastName">Nom</label>
                                <input id="reviewerLastName" name="reviewerLastName" type="text" x-model="lastName" required maxlength="150"
                                       :readonly="!!selectedId && lockedLastName" placeholder="Nom">
                                @error('reviewerLastName') <span class="error">{{ $message }}</span> @enderror
                            </div>
                        </div>
                        <div class="ukc-form-row">
                            <label for="reviewerInstitution">Institution <span style="color:var(--ukc-muted);font-weight:400;">(facultatif)</span></label>
                            <input id="reviewerInstitution" name="reviewerInstitution" type="text" x-model="institution" maxlength="255" placeholder="Université ou organisme">
                            @error('reviewerInstitution') <span class="error">{{ $message }}</span> @enderror
                        </div>
                        @if ($reviewerAxes !== [])
                            <div class="ukc-form-row">
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
                        <p class="hint" style="font-size:12.5px;color:var(--ukc-muted);">L'invitation à évaluer sera envoyée lorsqu'une soumission lui sera attribuée.</p>
                    </form>

                    <x-slot:footer>
                        <span style="flex:1;"></span>
                        <button type="button" class="ukc-btn ukc-btn--ghost" @click="reviewerOpen = false">Annuler</button>
                        <button type="submit" form="reviewerForm" class="ukc-btn ukc-btn--blue" :disabled="alreadyRegistered">Ajouter l'évaluateur</button>
                    </x-slot:footer>
                </x-ukc.slideover>
            @endif
        @endif
    </div>
</x-layouts.admin>
