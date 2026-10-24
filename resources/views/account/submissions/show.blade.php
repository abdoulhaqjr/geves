<x-layouts.participant :title="__('Détail de la soumission')" :subtitle="__('Suivi, retours & resoumission')">
    @php
        $s = $submission;
        $words = preg_match_all('/\p{L}+/u', strip_tags((string) $s->abstract));
        $decided = in_array($s->status, ['accepted', 'rejected'], true);
        $authors = collect($s->authors ?? []);
        $mainAuthor = $authors->firstWhere('corresponding', true) ?? $authors->first();
        $coauthors = $authors->filter(fn ($a) => empty($a['corresponding']));
        $fmtAuthor = fn ($a) => trim(\App\Support\EventNameFormat::author($s->event, $a).(filled($a['affiliation'] ?? null) ? ' ('.$a['affiliation'].')' : ''));
        $keywordSeparator = (new \App\Support\SubmissionFormConfig($s->event))->keywordSeparator();
        $kw = collect(\App\Support\SubmissionKeywords::parse($s->keywords, $keywordSeparator));
        $keywordHelp = match ($keywordSeparator) { 'semicolon' => 'Entrée ou point-virgule pour ajouter', 'both' => 'Entrée, virgule ou point-virgule pour ajouter', default => 'Entrée ou virgule pour ajouter' };
        $presentationModes = (new \App\Support\SubmissionFormConfig($s->event))->participationModes();
    @endphp

    <div class="account-submission-detail" x-data="{ slotModal: false }" @keydown.escape.window="slotModal = false">
        {{-- Barre de retour + statut --}}
        <div style="display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap;margin-bottom:14px;">
            <a href="{{ route('account.submissions.index') }}" class="ukc-btn ukc-btn--ghost ukc-btn--sm">← {{ __('Mes soumissions') }}</a>
            <div style="display:flex;gap:8px;flex-wrap:wrap;">
                <span class="ukc-pill ukc-pill--{{ $s->statusPill() }}">{{ $s->statusLabel() }}</span>
                <span class="ukc-pill ukc-pill--muted" style="font-family:var(--ukc-mono);">{{ $s->ref_code }}</span>
            </div>
        </div>

        {{-- Bandeau lecture seule pour un auteur rattaché qui n'est pas le déposant --}}
        @unless ($isOwner)
            <div class="ukc-alert ukc-alert--info" style="margin-bottom:14px;">
                <span class="ico"><x-ukc.icon name="users" :size="18" /></span>
                <div>{!! __('Vous êtes rattaché(e) à cette communication en tant qu’<strong>auteur</strong>. Seul le déposant peut la modifier.') !!}</div>
            </div>
        @endunless

        {{-- En-tête + parcours --}}
        <div class="ukc-card" style="margin-bottom:18px;">
            <div class="sub-header">
                <div class="title-block">
                    <h1>{{ $s->title }}</h1>
                    <div class="meta">
                        <span>{{ $s->type === 'poster' ? __('Poster') : __('Orale') }}</span><span>-</span>
                        <span>{{ $s->track ?: __('Axe non précisé') }}</span><span>-</span>
                        <span>{{ __('Déposée le') }} {{ $s->created_at?->format('d/m/Y') ?? '-' }}</span>
                    </div>
                </div>
            </div>

            <div class="status-track" aria-label="{{ __('Parcours de la soumission') }}">
                <div class="step done"><div class="lbl">{{ __('Déposée') }}</div></div>
                <div class="step done"><div class="lbl">{{ __('Reçue') }}</div></div>
                <div @class(['step', 'done' => $decided || $s->status === 'revision', 'active' => $s->status === 'under_review'])><div class="lbl">{{ __('En évaluation') }}</div></div>
                <div @class(['step', 'done' => $decided, 'active' => $s->status === 'revision'])><div class="lbl">{{ __('Décision') }}</div></div>
                <div @class(['step', 'done' => (bool) $slot, 'active' => $s->status === 'accepted' && ! $slot])><div class="lbl">{{ __('Créneau') }}</div></div>
                <div class="step"><div class="lbl">{{ __('Présentée') }}</div></div>
            </div>
            <p class="hint">
                @if ($s->status === 'accepted')
                    {!! __('Communication <strong>acceptée</strong>. Finalisez votre inscription et choisissez votre créneau.') !!}
                @elseif ($s->status === 'rejected')
                    {{ __('Après examen, votre communication n’a pas été retenue pour cet événement.') }}
                @elseif ($s->status === 'revision')
                    {!! __('Une <strong>correction</strong> est demandée : modifiez puis renvoyez votre communication.') !!}
                @else
                    {{ __('Vous recevrez un e-mail à chaque changement d’état de votre soumission.') }}
                @endif
            </p>
        </div>

        {{-- Correction libre du contenu tant qu'aucun évaluateur / comité / gestionnaire
             n'y a touché (statut « déposée », aucune évaluation). Déposant seulement. --}}
        @if ($isOwner && $s->isAuthorEditable())
            <div class="ukc-card" style="margin-bottom:18px;" x-data="{ editing: {{ $errors->any() ? 'true' : 'false' }} }">
                <div class="ukc-alert ukc-alert--info" style="margin:0;">
                    <span class="ico"><x-ukc.icon name="info" :size="18" /></span>
                    <div>{!! __('Votre communication n’a pas encore été évaluée : vous pouvez <strong>corriger votre résumé</strong> librement. Après le début de l’évaluation, les corrections passeront par le comité.') !!}</div>
                </div>
                <div style="text-align:center;margin-top:14px;" x-show="!editing">
                    <button type="button" class="ukc-btn ukc-btn--outline ukc-btn--sm" @click="editing = true">
                        <x-ukc.icon name="pen" :size="16" /> {{ __('Modifier mon résumé') }}
                    </button>
                </div>

                <div x-show="editing" x-cloak style="margin-top:16px;">
                    <form method="POST" action="{{ route('account.submissions.update', $s) }}" class="ukc-form-grid">
                        @csrf
                        <div class="ukc-field full">
                            <label>{{ __('Auteurs') }}</label>
                            <div style="display:flex;flex-direction:column;gap:6px;">
                                @foreach ($authors as $a)
                                    <div style="font-size:13.5px;">
                                        <strong>{{ \App\Support\EventNameFormat::author($s->event, $a) ?: '-' }}</strong>
                                        @if (!empty($a['corresponding']))<span class="ukc-pill ukc-pill--info" style="margin-left:6px;">{{ __('Correspondant') }}</span>@endif
                                        @if (!empty($a['affiliation']))<span style="color:var(--ukc-muted);"> - {{ $a['affiliation'] }}@if (!empty($a['grade'])) - {{ $a['grade'] }}@endif</span>@endif
                                    </div>
                                @endforeach
                            </div>
                            <span class="hint">{{ __('La correction en place porte sur le titre, le résumé et les mots-clés. Pour modifier les auteurs, contactez l’organisation.') }}</span>
                        </div>
                        <div class="ukc-field full">
                            <label for="edit-title">{{ __('Titre') }} <span class="req">*</span></label>
                            <input type="text" id="edit-title" name="title" value="{{ old('title', $s->title) }}">
                            @error('title') <span class="error">{{ $message }}</span> @enderror
                        </div>
                        <div class="ukc-field full">
                            <label>{{ __('Résumé') }} <span class="req">*</span></label>
                            @include('partials.abstract-editor', ['name' => 'abstract', 'initial' => old('abstract', $s->abstract), 'min' => $abstractMin, 'max' => $abstractMax, 'help' => null])
                            @error('abstract') <span class="error">{{ $message }}</span> @enderror
                        </div>
                        <div class="ukc-field full">
                            <label for="edit-keywords">{{ __('Mots-clés') }} <span class="hint">({{ $keywordHelp }})</span></label>
                            <input type="text" id="edit-keywords" name="keywords" data-tom data-tom-create data-keyword-separator="{{ $keywordSeparator }}" data-placeholder="{{ __('Saisir un mot-clé…') }}"
                                   value="{{ old('keywords', \App\Support\SubmissionKeywords::join($s->keywords, $keywordSeparator)) }}">
                            @error('keywords') <span class="error">{{ $message }}</span> @enderror
                        </div>
                        <div style="grid-column:1 / -1;display:flex;flex-direction:row;gap:8px;flex-wrap:wrap;">
                            <button type="submit" class="ukc-btn ukc-btn--blue"><x-ukc.icon name="check" :size="16" /> {{ __('Enregistrer les modifications') }}</button>
                            <button type="button" class="ukc-btn ukc-btn--ghost" @click="editing = false">{{ __('Annuler') }}</button>
                        </div>
                    </form>
                </div>
            </div>
        @endif

        {{-- Correction demandée : la resoumission a sa PAGE.
             ⚠️ Le bouton était une ancre `#resubmit` vers un formulaire posé tout
             en bas de cette fiche, après l'historique et le créneau de passage :
             on changeait de tâche sans quitter une page de consultation, et une
             erreur de validation renvoyait l'auteur chercher le bloc. --}}
        @if ($isOwner && $s->canBeRevised())
            <div class="ukc-card" style="margin-bottom:18px;border-color:#fde68a;background:#fffbeb;">
                <div class="ukc-alert ukc-alert--warn" style="margin-bottom:16px;">
                    <span class="ico"><x-ukc.icon name="pen" :size="18" /></span>
                    <div>{!! __('Une <strong>correction</strong> vous est demandée. Ouvrez le formulaire de correction pour modifier votre communication, puis renvoyez-la.') !!}</div>
                </div>
                <a href="{{ route('account.submissions.revise', $s) }}" class="ukc-btn ukc-btn--blue ukc-btn--sm">{{ __('Corriger & renvoyer') }}</a>
            </div>
        @endif

        <div class="layout-2">
            {{-- Colonne principale --}}
            <div style="display:flex;flex-direction:column;gap:18px;">
                <div class="ukc-card">
                    <h2>{{ __('Informations') }}</h2>
                    <dl class="kv">
                        <dt>{{ __('Référence') }}</dt><dd><strong>{{ $s->ref_code }}</strong></dd>
                        <dt>{{ __('Type de communication') }}</dt><dd>{{ $s->type === 'poster' ? __('Poster') : __('Orale') }}</dd>
                        <dt>{{ __('Mode de présentation') }}</dt><dd>{{ $s->presentationModeLabel() ? __($s->presentationModeLabel()) : '-' }}</dd>
                        <dt>{{ __('Participation au colloque') }}</dt><dd>{{ $s->participant?->participationModeLabel() ? __($s->participant->participationModeLabel()) : '-' }}@if ($isOwner) <a href="{{ route('account.payment', $s->event) }}#mode-participation">{{ __('Modifier') }}</a>@endif</dd>
                        <dt>{{ __('Atelier / axe') }}</dt><dd>{{ $s->track ?: '-' }}</dd>
                        <dt>{{ __('Langue') }}</dt><dd>{{ $s->meta['language'] ?? 'Français' }}</dd>
                        <dt>{{ __('Mots (résumé)') }}</dt><dd>{{ $words }} / {{ $abstractMax }}</dd>
                        <dt>{{ __('Auteur principal') }}</dt><dd>{{ $mainAuthor ? $fmtAuthor($mainAuthor) : ($s->correspondingAuthorName() ?: '-') }}</dd>
                        @if ($coauthors->isNotEmpty())
                            <dt>{{ __('Co-auteurs') }}</dt><dd>{{ $coauthors->map($fmtAuthor)->filter()->implode(', ') }}</dd>
                        @endif
                    </dl>

                    @if ($isOwner && ! \App\Support\EventLock::frozen($s->event))
                        <form method="POST" action="{{ route('account.submissions.presentation-mode.update', $s) }}" class="ukc-field" style="margin-top:16px;max-width:520px;">
                            @csrf
                            @method('PUT')
                            <label for="presentation_mode">{{ __('Modifier le mode de présentation de cette communication') }}</label>
                            <div style="display:flex;gap:10px;flex-wrap:wrap;">
                                <select id="presentation_mode" name="presentation_mode" required style="flex:1;min-width:180px;">
                                    @if (! $s->presentation_mode && count($presentationModes) > 1)<option value="">{{ __('Choisir un mode') }}</option>@endif
                                    @foreach ($presentationModes as $value => $label)
                                        <option value="{{ $value }}" @selected(old('presentation_mode', $s->presentation_mode) === $value)>{{ __($label) }}</option>
                                    @endforeach
                                </select>
                                <button class="ukc-btn ukc-btn--blue" type="submit">{{ __('Enregistrer') }}</button>
                            </div>
                            @error('presentation_mode') <span class="error">{{ $message }}</span> @enderror
                        </form>
                    @endif

                    <h3 style="font-size:14px;font-weight:800;margin:16px 0 8px;">{{ __('Résumé') }}</h3>
                    @if (filled($s->abstract))
                        <div class="abstract">{!! \App\Support\Html::render($s->abstract) !!}</div>
                    @else
                        <div class="abstract" style="color:var(--ukc-muted);font-style:italic;">{{ __('Aucun résumé n’a été enregistré pour cette communication.') }}</div>
                    @endif

                    <h3 style="font-size:14px;font-weight:800;margin:16px 0 8px;">{{ __('Mots-clés') }}</h3>
                    @if ($kw->isNotEmpty())
                        <div class="ukc-keywords">@foreach ($kw as $k)<span>{{ $k }}</span>@endforeach</div>
                    @else
                        <p class="hint" style="margin:0;">{{ __('Aucun mot-clé renseigné.') }}</p>
                    @endif
                </div>

                <div class="ukc-card">
                    <h2>{{ __('Fichiers') }}</h2>
                    @if ($s->file_url)
                        <div class="file-row">
                            <div class="ico"><x-ukc.icon name="file" :size="20" /></div>
                            <div style="flex:1;min-width:0;">
                                <div class="name" style="word-break:break-all;">{{ basename(parse_url($s->file_url, PHP_URL_PATH) ?: $s->file_url) }}</div>
                                <div class="meta">{{ __('PDF - Déposé le') }} {{ $s->created_at?->format('d/m/Y') }}</div>
                            </div>
                            <a href="{{ $s->file_url }}" target="_blank" class="ukc-btn ukc-btn--outline ukc-btn--sm">{{ __('Télécharger') }}</a>
                        </div>
                    @endif
                    <p class="hint">{{ __('En cas d’erreur matérielle sur le titre, les auteurs ou le fichier, contactez le secrétariat avant la décision du comité.') }}</p>
                </div>

                <div class="ukc-card">
                    <h2>{{ __('Messages du comité') }}</h2>
                    @forelse ($feedback as $fb)
                        <div class="msg-box">
                            <div class="from">{{ $fb['label'] ?? __('Comité scientifique') }}</div>
                            <p>{{ $fb['comments'] }}</p>
                        </div>
                    @empty
                        <div class="msg-box">
                            <div class="from">{{ __('Secrétariat scientifique') }}</div>
                            <p>{{ __('Votre soumission a bien été enregistrée et transmise aux évaluateurs. Aucune action n’est requise de votre part pour le moment.') }}</p>
                        </div>
                    @endforelse
                    <p class="hint">{{ __('Les évaluations détaillées des évaluateurs restent confidentielles. Seule la décision finale et d’éventuels commentaires destinés aux auteurs vous sont communiqués.') }}</p>
                </div>

                {{-- Phase de réponse aux évaluateurs (rebuttal) - déposant seulement --}}
                @if ($isOwner && $s->rebuttalOpen())
                    @php($reb = $s->rebuttal())
                    <div class="ukc-card" style="margin-top:18px;border-color:#c7d2fe;background:#f5f7ff;" id="rebuttal">
                        <h2><x-ukc.icon name="pen" :size="18" /> {{ __('Votre réponse aux évaluateurs') }}</h2>
                        <p style="color:var(--ukc-muted);font-size:.9rem;margin:-2px 0 12px;">
                            {{ __('Le comité vous invite à répondre aux retours ci-dessus (préciser, corriger un malentendu, détailler les modifications prévues) avant la décision finale.') }}
                        </p>

                        @if ($s->hasRebuttal())
                            <div class="msg-box" style="background:#fff;">
                                <div class="from">{{ __('Réponse transmise') }}{{ ! empty($reb['submitted_at']) ? ' '.__('le').' '.\Illuminate\Support\Carbon::parse($reb['submitted_at'])->translatedFormat('d F Y à H:i') : '' }}</div>
                                <p style="white-space:pre-line;">{{ $s->rebuttalText() }}</p>
                            </div>
                        @endif

                        <form method="POST" action="{{ route('account.submissions.rebuttal', $s) }}" style="margin-top:12px;">
                            @csrf
                            <div class="ukc-field">
                                <label for="rebuttal">{{ $s->hasRebuttal() ? __('Modifier votre réponse') : __('Votre réponse') }} <span class="req">*</span></label>
                                <textarea id="rebuttal" name="rebuttal" rows="6"
                                          placeholder="{{ __('Répondez point par point aux remarques des évaluateurs.') }}">{{ old('rebuttal', $s->rebuttalText()) }}</textarea>
                                @error('rebuttal') <span class="error">{{ $message }}</span> @enderror
                            </div>
                            <div class="ukc-form-actions" style="margin-top:12px;">
                                <button type="submit" class="ukc-btn ukc-btn--blue">
                                    <x-ukc.icon name="check" :size="16" /> {{ $s->hasRebuttal() ? __('Mettre à jour ma réponse') : __('Envoyer ma réponse') }}
                                </button>
                            </div>
                        </form>
                    </div>
                @endif
            </div>

            {{-- Colonne latérale --}}
            <div style="display:flex;flex-direction:column;gap:18px;">
                <div class="ukc-card">
                    <h2>{{ __('Historique') }}</h2>
                    <ul class="timeline">
                        <li>
                            <div class="when">{{ $s->created_at?->format('d/m/Y - H:i') }}</div>
                            <div class="what">{{ __('Soumission déposée') }}</div>
                            <div class="detail">{{ __('Formulaire validé') }}@if($s->file_url) - {{ __('fichier résumé joint') }} @endif</div>
                        </li>
                        <li>
                            <div class="when">{{ $s->created_at?->format('d/m/Y - H:i') }}</div>
                            <div class="what">{{ __('Accusé de réception') }}</div>
                            <div class="detail">{{ __('E-mail envoyé') }}</div>
                        </li>
                        <li @class(['muted' => $s->status === 'submitted'])>
                            <div class="when">{{ $s->status === 'submitted' ? __('À venir') : __('En cours') }}</div>
                            <div class="what">{{ __('Évaluation scientifique') }}</div>
                            <div class="detail">{{ $decided ? __('Évaluations rendues') : __('En attente des évaluations') }}</div>
                        </li>
                        <li @class(['muted' => ! $decided && $s->status !== 'revision'])>
                            <div class="when">{{ $decided ? $s->updated_at?->format('d/m/Y') : __('À venir') }}</div>
                            <div class="what">{{ __('Notification de décision') }}</div>
                            <div class="detail">
                                @if ($s->status === 'accepted') {{ __('Acceptée') }}
                                @elseif ($s->status === 'rejected') {{ __('Non retenue') }}
                                @elseif ($s->status === 'revision') {{ __('Corrections demandées') }}
                                @else {{ __('Acceptée / refusée / révisions') }} @endif
                            </div>
                        </li>
                        <li @class(['muted' => ! $slot])>
                            <div class="when">{{ $slot ? __('Confirmé') : __('À venir') }}</div>
                            <div class="what">{{ __('Affectation de créneau') }}</div>
                            <div class="detail">{{ __('Après acceptation et paiement de l’inscription') }}</div>
                        </li>
                    </ul>
                </div>

                <div class="ukc-card">
                    <h2>{{ __('Créneau de passage') }}</h2>
                    @if ($slot)
                        <div class="slot-card">
                            <strong>{{ $slot->session_label ?: __('Présentation') }}</strong>
                            @if ($slot->starts_at){{ $slot->starts_at->translatedFormat('d F, H:i') }}@endif
                            {{-- ⚠️ `room` nomme déjà le lieu : pas de préfixe, sinon « Salle Salle 5 ». --}}
                            @if ($slot->room) - {{ $slot->room }}@endif
                            @if ($slot->chosen_by_participant) <span class="ukc-pill ukc-pill--info" style="margin-left:6px;">{{ __('Choisi') }}</span>@endif
                        </div>
                        @if ($slot->starts_at)
                            <div style="display:flex;flex-wrap:wrap;gap:8px;margin-top:12px;">
                                @if ($googleCal)
                                    <a href="{{ $googleCal }}" target="_blank" rel="noopener" class="ukc-btn ukc-btn--outline ukc-btn--sm">
                                        <x-ukc.icon name="calendar" :size="15" /> {{ __('Ajouter à Google Agenda') }}
                                    </a>
                                @endif
                                <a href="{{ route('account.agenda.ics') }}" class="ukc-btn ukc-btn--ghost ukc-btn--sm">
                                    <x-ukc.icon name="download" :size="15" /> {{ __('Mon agenda (.ics)') }}
                                </a>
                            </div>
                        @endif
                        @if ($isOwner && $availableSlots->count())
                            <button type="button" class="ukc-btn ukc-btn--outline ukc-btn--sm" style="margin-top:12px;" @click="slotModal = true">
                                <x-ukc.icon name="calendar" :size="16" /> {{ __('Changer de créneau') }}
                            </button>
                        @endif
                    @elseif ($s->status === 'accepted' && $availableSlots->count())
                        <p class="hint" style="margin-top:0;">{{ __('Aucun créneau choisi pour l’instant.') }}</p>
                        @if ($isOwner)
                            <button type="button" class="ukc-btn ukc-btn--blue ukc-btn--sm" style="margin-top:8px;" @click="slotModal = true">
                                <x-ukc.icon name="calendar" :size="16" /> {{ __('Choisir un créneau') }}
                            </button>
                        @endif
                    @else
                        <div class="slot-card">
                            <strong>{{ __('Pas encore attribué') }}</strong>
                            {{ __('Un créneau (jour, horaire, session, salle) sera proposé après acceptation et règlement des frais d’inscription, selon l’axe de votre communication.') }}
                        </div>
                    @endif
                </div>

                <div class="ukc-card">
                    <h2>{{ __('Documents liés') }}</h2>
                    <div style="display:flex;flex-direction:column;gap:8px;">
                        <a href="{{ route('account.documents') }}" class="ukc-btn ukc-btn--outline ukc-btn--sm" style="justify-content:flex-start;">{{ __('Attestations & reçus') }}</a>
                        <a href="{{ route('account.payment', $s->event) }}" class="ukc-btn ukc-btn--outline ukc-btn--sm" style="justify-content:flex-start;">{{ __('Inscription & paiement') }}</a>
                    </div>
                </div>
            </div>
        </div>

        {{-- Formulaire de resoumission (correction) - déposant seulement --}}
        {{-- ⚠️ **Plus de formulaire de resoumission ici.** Il vivait sous cette
             ligne, et c'est précisément ce qui a été corrigé : la correction se
             fait sur `account.submissions.revise`, où les remarques du comité
             sont à côté des champs. Ne pas le réintroduire - deux formulaires
             pour le même geste, c'est deux endroits à tenir d'accord. --}}

        {{-- Modale : choix / changement de créneau --}}
        @if ($isOwner && $availableSlots->count() && ($slot || $s->status === 'accepted'))
            <div class="ukc-modal" x-show="slotModal" x-cloak @click.self="slotModal = false" style="display:none;">
                <div class="box">
                    <div class="box-head">
                        <span>{{ __('Choisir un créneau de passage') }}</span>
                        <button type="button" @click="slotModal = false" style="font-size:22px;color:var(--ukc-muted);background:none;border:none;cursor:pointer;">&times;</button>
                    </div>
                    <div class="box-body">
                        @if ($slot)
                            <p style="font-size:.88rem;color:var(--ukc-muted);margin:0 0 12px;">
                                {{ __('Créneau actuel :') }} <strong>{{ $slot->session_label ?: __('Présentation') }}@if ($slot->starts_at) - {{ $slot->starts_at->translatedFormat('d F, H:i') }}@endif</strong>. {{ __('Sélectionnez-en un autre ci-dessous.') }}
                            </p>
                        @endif
                        <div style="max-height:56vh;overflow-y:auto;display:flex;flex-direction:column;gap:8px;padding-right:2px;">
                            @foreach ($availableSlots as $av)
                                <form method="POST" action="{{ route('account.submissions.choose-slot', $s) }}">
                                    @csrf
                                    <input type="hidden" name="slot_id" value="{{ $av->id }}">
                                    <button type="submit" class="ukc-btn ukc-btn--outline ukc-btn--sm" style="justify-content:space-between;width:100%;text-align:left;">
                                        <span>
                                            <strong>{{ $av->session_label ?: __('Créneau') }}</strong>
                                            @if ($av->starts_at)<span style="color:var(--ukc-muted);"> - {{ $av->starts_at->translatedFormat('d F, H:i') }}</span>@endif
                                            @if ($av->room)<span style="color:var(--ukc-muted);"> - {{ $av->room }}</span>@endif
                                        </span>
                                        <x-ukc.icon name="chevron" :size="16" />
                                    </button>
                                </form>
                            @endforeach
                        </div>
                    </div>
                    <div class="box-foot">
                        <button type="button" class="ukc-btn ukc-btn--ghost" @click="slotModal = false">{{ __('Fermer') }}</button>
                    </div>
                </div>
            </div>
        @endif
    </div>
</x-layouts.participant>
