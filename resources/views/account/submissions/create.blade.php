@php
    $fileEnabled = $config->enabled('file');
    // Options de grade : liste de référence + le grade actuel du compte s'il en sort.
    $gradeChoices = $gradeOptions;
    if (filled($account->grade) && ! in_array($account->grade, $gradeChoices, true)) {
        $gradeChoices[] = $account->grade;
    }
    $presentationModes = $config->participationModes();
    $defaultMode = array_key_exists('onsite', $presentationModes) ? 'onsite' : array_key_first($presentationModes);
    $initialMode = old('presentation_mode', $defaultMode);
    if (! array_key_exists($initialMode, $presentationModes)) {
        $initialMode = $defaultMode;
    }
    $coreFieldCount = (count($tracks) > 0 ? 1 : 0) + ($config->enabled('type') ? 1 : 0) + 1;
    $wizardInit = [
        'title' => old('title', ''),
        'track' => old('track', $tracks[0] ?? ''),
        'type' => old('type', 'oral'),
        'presentationMode' => $initialMode,
        'keywords' => \App\Support\SubmissionKeywords::parse(old('keywords', ''), $config->keywordSeparator()),
        'coauthors' => array_values(old('coauthors', [])),
        'errorStep' => (int) session('submission_error_step', 1),
        'lookupUrl' => route('account.authors.check'),
        'suggestUrl' => route('account.affiliations'),
        'coauthorsMax' => (int) ($event->settings['coauthors_max'] ?? 8),
        // Auteur correspondant : prérempli avec le compte connecté, éditable.
        // Le grade saisi ici est enregistré POUR CE COLLOQUE (grade par événement).
        'account' => [
            'name' => $account->full_name,
            'first_name' => $account->first_name,
            'last_name' => $account->last_name,
            'email' => $account->email,
            'phone' => $account->phone,
            'affiliation' => $account->affiliation,
            'grade' => $account->grade,
        ],
        'corr' => [
            'self' => old('corresponding.self', '1') !== '0',
            'first_name' => old('corresponding.first_name', $account->first_name),
            'last_name' => old('corresponding.last_name', $account->last_name),
            'email' => old('corresponding.email', $account->email),
            'phone' => old('corresponding.phone', $account->phone),
            'affiliation' => old('corresponding.affiliation', $account->affiliation),
            'grade' => old('corresponding.grade', $account->grade),
        ],
        // Règles de validation côté client : bloquent le passage à l'étape suivante
        // tant que les champs obligatoires de l'étape courante ne sont pas remplis.
        'rules' => [
            'titleMin' => $config->titleMin(),
            'trackRequired' => count($tracks) > 0 && $config->required('track'),
            'abstractMin' => $config->abstractMin(),
            'keywordsRequired' => $config->enabled('keywords') && $config->required('keywords'),
            'keywordsMin' => $config->enabled('keywords') ? $config->keywordsMin() : 0,
            'fileStep' => $fileEnabled,
            'fileRequired' => $fileEnabled && $config->required('file'),
            'lastStep' => $fileEnabled ? 5 : 4,
        ],
    ];
@endphp
<x-layouts.participant :title="__('Soumettre une communication')" :subtitle="__('Dépôt d’un résumé')">
    @if ($successRef)
        {{-- Carte de succès --}}
        <div class="ukc-card" style="text-align:center;max-width:620px;margin:0 auto;">
            <div style="width:64px;height:64px;border-radius:50%;margin:0 auto 16px;background:#dcfce7;color:#166534;
                        display:flex;align-items:center;justify-content:center;">
                <x-ukc.icon name="check" :size="32" />
            </div>
            <h2 style="justify-content:center;">{{ __('Communication reçue') }}</h2>
            <p style="color:var(--ukc-muted);line-height:1.6;">
                {{ __('Votre soumission a bien été enregistrée sous la référence') }}
                <strong style="color:var(--ukc-text);font-family:var(--ukc-mono);">{{ $successRef }}</strong>.
                {{ __('Vous pouvez suivre son statut depuis « Mes communications ».') }}
            </p>
            <div style="display:flex;gap:12px;justify-content:center;flex-wrap:wrap;margin-top:20px;">
                <a href="{{ route('account.submissions.index') }}" class="ukc-btn ukc-btn--blue">
                    <x-ukc.icon name="inbox" :size="18" /> {{ __('Mes communications') }}
                </a>
                @if ($remaining > 0)
                    <a href="{{ route('account.submissions.create', $event->slug) }}" class="ukc-btn ukc-btn--outline">
                        <x-ukc.icon name="plus" :size="18" /> {{ __('Soumettre une autre') }}
                    </a>
                @endif
            </div>
        </div>
    @else
        {{-- Contexte événement --}}
        <div class="ukc-card" style="margin-bottom:18px;">
            <div style="font-size:.8rem;font-weight:700;color:var(--ukc-muted);text-transform:uppercase;letter-spacing:.04em;">
                {{ $event->event_label }}
            </div>
            <div style="font-weight:800;font-size:1.1rem;margin-top:2px;">{{ $event->title }}</div>
            <div style="font-size:.85rem;color:var(--ukc-muted);margin-top:6px;">
                {{ __(':count communication(s) restante(s) sur :max.', ['count' => $remaining, 'max' => $maxCommunications]) }}
            </div>
        </div>

        {{-- Bandeau compte (identité imposée) --}}
        <div class="ukc-card" style="margin-bottom:18px;">
            <div style="display:flex;align-items:center;gap:14px;flex-wrap:wrap;">
                <div class="ukc-user-chip" style="gap:12px;">
                    <div class="av" style="background:linear-gradient(135deg,var(--ukc-blue),var(--ukc-green));">
                        {{ $account->initials }}
                    </div>
                </div>
                <div style="min-width:0;flex:1;">
                    <div style="font-weight:800;">{{ $account->full_name }}
                        <span style="font-weight:500;color:var(--ukc-muted);">- {{ $account->grade }}</span>
                    </div>
                    <div style="font-size:.85rem;color:var(--ukc-muted);">
                        {{ $account->email }} - {{ $account->affiliation ?: __('affiliation manquante') }}
                    </div>
                </div>
                <a href="{{ route('account.profile') }}" class="ukc-btn ukc-btn--ghost ukc-btn--sm">
                    <x-ukc.icon name="pen" :size="16" /> {{ __('Modifier mon profil') }}
                </a>
            </div>
        </div>

        {{-- Gating --}}
        @if (! $account->canSubmit())
            <div class="ukc-alert ukc-alert--warn">
                <span class="ico"><x-ukc.icon name="lock" :size="18" /></span>
                <div>
                    {!! __('Pour soumettre, votre <strong>e-mail doit être vérifié</strong> et votre <strong>profil complété</strong> (affiliation).') !!}
                    @if (! $account->hasVerifiedEmail())
                        <a href="{{ route('account.verification.notice') }}" style="text-decoration:underline;font-weight:700;">{{ __('Vérifier mon e-mail') }}</a>
                    @endif
                    @if (blank($account->affiliation))
                        <a href="{{ route('account.profile') }}" style="text-decoration:underline;font-weight:700;">{{ __('Compléter mon profil') }}</a>
                    @endif
                </div>
            </div>
        @elseif (! $event->submission_open)
            <div class="ukc-alert ukc-alert--err">
                <span class="ico"><x-ukc.icon name="info" :size="18" /></span>
                <div>{!! __('Les soumissions sont actuellement <strong>fermées</strong> pour cet événement.') !!}</div>
            </div>
        @elseif ($remaining <= 0)
            <div class="ukc-alert ukc-alert--info">
                <span class="ico"><x-ukc.icon name="info" :size="18" /></span>
                <div>{{ __('Vous avez atteint le nombre maximum de communications pour cet événement.') }}</div>
            </div>
        @else
            {{-- Formulaire multi-étapes (wizard). L'étape « Fichiers » n'existe que si
                 le dépôt de fichier est activé pour cet événement. --}}
            @php
                $fileStep = $config->enabled('file');
                $validationStep = $fileStep ? 5 : 4;
                $lastStep = $validationStep;
            @endphp
            <div x-data="submissionWizard(@js($wizardInit))">
                <div class="form-steps">
                    <div class="s" :class="{ active: step===1, done: step>1 }" @click="goTo(1)"><span class="n">1</span> {{ __('Général') }}</div>
                    <div class="s" :class="{ active: step===2, done: step>2 }" @click="goTo(2)"><span class="n">2</span> {{ __('Auteurs') }}</div>
                    <div class="s" :class="{ active: step===3, done: step>3 }" @click="goTo(3)"><span class="n">3</span> {{ __('Résumé') }}</div>
                    @if ($fileStep)
                        <div class="s" :class="{ active: step===4, done: step>4 }" @click="goTo(4)"><span class="n">4</span> {{ __('Fichiers') }}</div>
                    @endif
                    <div class="s" :class="{ active: step==={{ $validationStep }} }" @click="goTo({{ $validationStep }})"><span class="n">{{ $validationStep }}</span> {{ __('Validation') }}</div>
                </div>

                <form method="POST" action="{{ route('account.submissions.store', $event->slug) }}" enctype="multipart/form-data" class="ukc-card">
                    @csrf
                    {{-- Étape 1 : Général --}}
                    <div class="step-panel" :class="{ active: step===1 }">
                        <h2><x-ukc.icon name="info" :size="20" /> {{ __('Informations générales') }}</h2>
                        <div class="ukc-form-grid">
                            <div class="ukc-field full">
                                <label for="title">{{ $config->label('title') }} <span class="req">*</span></label>
                                <input type="text" id="title" name="title" x-model="title" maxlength="{{ $config->titleMax() }}">
                                <div style="display:flex;justify-content:space-between;gap:12px;">
                                    <span class="hint">
                                        @if ($config->help('title')){{ $config->help('title') }}
                                        @elseif ($config->titleMin() > 0){{ __('Minimum :min caractères - maximum :max.', ['min' => $config->titleMin(), 'max' => $config->titleMax()]) }}
                                        @else {{ __('Maximum :max caractères.', ['max' => $config->titleMax()]) }}@endif
                                    </span>
                                    <span class="hint"><span x-text="title.length"></span>/{{ $config->titleMax() }}</span>
                                </div>
                                @error('title') <span class="error">{{ $message }}</span> @enderror
                            </div>

                        </div>
                        <div class="submission-core-fields" style="--submission-field-count: {{ $coreFieldCount }};">
                                @if (count($tracks))
                                    <div class="ukc-field">
                                        <label for="track">{{ $config->label('track') }} @if ($config->required('track'))<span class="req">*</span>@endif</label>
                                        <select id="track" name="track" x-model="track">
                                            @foreach ($tracks as $t)
                                                <option value="{{ $t }}">{{ $t }}</option>
                                            @endforeach
                                        </select>
                                        @if ($config->help('track'))<span class="hint">{{ $config->help('track') }}</span>@endif
                                        @error('track') <span class="error">{{ $message }}</span> @enderror
                                    </div>
                                @endif

                                @if ($config->enabled('type'))
                                    <div class="ukc-field">
                                        <label for="type">{{ __($config->label('type')) }} <span class="req">*</span></label>
                                        <select id="type" name="type" x-model="type">
                                            <option value="oral">{{ __('Orale') }}</option>
                                            <option value="poster">{{ __('Poster') }}</option>
                                        </select>
                                        @if ($config->help('type'))<span class="hint">{{ $config->help('type') }}</span>@endif
                                        @error('type') <span class="error">{{ $message }}</span> @enderror
                                    </div>
                                @endif

                                <div class="ukc-field">
                                    <label for="presentation_mode">{{ __($config->label('participation_mode')) }} <span class="req">*</span></label>
                                    <select id="presentation_mode" name="presentation_mode" x-model="presentationMode" required>
                                        @foreach ($presentationModes as $value => $label)
                                            <option value="{{ $value }}">{{ __($label) }}</option>
                                        @endforeach
                                    </select>
                                    @error('presentation_mode') <span class="error">{{ $message }}</span> @enderror
                                </div>
                        </div>
                    </div>

                    {{-- Étape 2 : Auteurs --}}
                    <div class="step-panel" :class="{ active: step===2 }">
                        <h2><x-ukc.icon name="users" :size="20" /> {{ __('Auteur correspondant') }}</h2>

                        {{-- Auteur correspondant : prérempli avec le compte connecté ; le grade
                             saisi vaut POUR CE COLLOQUE (il peut évoluer d'un événement à l'autre). --}}
                        <input type="hidden" name="corresponding[self]" :value="corr.self ? 1 : 0">
                        <label class="ukc-switch">
                            <input type="checkbox" x-model="corr.self" @change="onSelfToggle()">
                            <span class="ukc-switch__track"><span class="ukc-switch__knob"></span></span>
                            <span class="ukc-switch__label">{{ __('Je suis l’auteur correspondant') }}</span>
                        </label>
                        <div class="ukc-form-grid-3">
                            <div class="ukc-field">
                                <label for="corr-last">{{ __('Nom') }} <span class="req">*</span></label>
                                <input type="text" id="corr-last" name="corresponding[last_name]" x-model="corr.last_name" data-uppercase
                                       :readonly="corr.self" :class="corr.self ? 'is-locked' : ''" placeholder="{{ __('NOM') }}">
                                <span class="hint" x-show="corr.self" x-cloak>{{ __('Repris de votre profil (identité vérifiée).') }}</span>
                            </div>
                            <div class="ukc-field">
                                <label for="corr-first">{{ __('Prénom') }} <span class="req">*</span></label>
                                <input type="text" id="corr-first" name="corresponding[first_name]" x-model="corr.first_name"
                                       :readonly="corr.self" :class="corr.self ? 'is-locked' : ''" placeholder="{{ __('Prénom') }}">
                            </div>
                            <div class="ukc-field">
                                <label for="corr-email">{{ __('E-mail') }} <span class="req">*</span></label>
                                <input type="email" id="corr-email" name="corresponding[email]" x-model="corr.email" @blur="lookupCorr()" @input.debounce.400ms="lookupCorr()"
                                       :readonly="corr.self" :class="corr.self ? 'is-locked' : ''" placeholder="{{ __('E-mail') }}">
                                @error('corresponding.email') <span class="error">{{ $message }}</span> @enderror
                            </div>
                            <div class="ukc-field">
                                <label for="corr-phone">{{ __('Téléphone') }}</label>
                                <input type="tel" id="corr-phone" name="corresponding[phone]" value="{{ old('corresponding.phone', $account->phone) }}"
                                       data-ukc-tel autocomplete="tel"
                                       x-init="$nextTick(() => window.initPhoneInputs && window.initPhoneInputs())">
                            </div>
                            <div class="ukc-field">
                                <label for="corr-affiliation">{{ __('Affiliation') }}</label>
                                <input type="text" id="corr-affiliation" name="corresponding[affiliation]" x-model="corr.affiliation" list="ukc-affiliations"
                                       @input.debounce.350ms="affiliationSuggest($event.target.value)" autocomplete="off"
                                       placeholder="{{ __('Établissement / laboratoire') }}">
                                @error('corresponding.affiliation') <span class="error">{{ $message }}</span> @enderror
                            </div>
                            <div class="ukc-field">
                                <label for="corr-grade">{{ __('Grade') }} <span class="hint">{{ __('(pour ce colloque)') }}</span></label>
                                <select id="corr-grade" name="corresponding[grade]" x-model="corr.grade">
                                    <option value="">- {{ __('Choisir') }} -</option>
                                    @foreach ($gradeChoices as $g)
                                        <option value="{{ $g }}">{{ $g }}</option>
                                    @endforeach
                                </select>
                                @error('corresponding.grade') <span class="error">{{ $message }}</span> @enderror
                            </div>
                        </div>

                        {{-- Indice discret : le compte est-il connu ? (sans révéler l'identité d'autrui) --}}
                        <div x-show="!corr.self && corr.hint" x-cloak style="margin-top:8px;">
                            <span class="author-hint" :class="'author-hint--'+corr.hintType">
                                <x-ukc.icon name="info" :size="14" /> <span x-text="corr.hint"></span>
                            </span>
                        </div>

                        <h3 style="font-size:14px;font-weight:800;margin:20px 0 6px;">{{ __('Co-auteurs') }}</h3>
                        <p class="ukc-muted" style="font-size:.88rem;margin-bottom:12px;">
                            {{ __('Ajoutez ici les co-auteurs de la communication.') }}
                        </p>
                        <div style="display:flex;flex-direction:column;gap:12px;">
                            <template x-for="(co, i) in coauthors" :key="i">
                                <div>
                                    <div class="coauthor-row">
                                        <input type="text" :name="'coauthors['+i+'][last_name]'" x-model="co.last_name" data-uppercase placeholder="{{ __('NOM *') }}">
                                        <input type="text" :name="'coauthors['+i+'][first_name]'" x-model="co.first_name" placeholder="{{ __('Prénom *') }}">
                                        <input type="email" :name="'coauthors['+i+'][email]'" x-model="co.email"
                                               @blur="lookupCoauthor(i)" @input.debounce.400ms="lookupCoauthor(i)" placeholder="{{ __('E-mail *') }}">
                                        <input type="tel" :name="'coauthors['+i+'][phone]'" data-ukc-tel autocomplete="tel"
                                               x-init="$el.value = co.phone || ''; $nextTick(() => window.initPhoneInputs && window.initPhoneInputs())">
                                        <input type="text" :name="'coauthors['+i+'][affiliation]'" x-model="co.affiliation" list="ukc-affiliations" autocomplete="off"
                                               @input.debounce.350ms="affiliationSuggest($event.target.value)" placeholder="{{ __('Affiliation *') }}">
                                        <select :name="'coauthors['+i+'][grade]'" x-model="co.grade">
                                            <option value="">{{ __('Grade') }}</option>
                                            @foreach ($gradeOptions as $g)
                                                <option value="{{ $g }}">{{ $g }}</option>
                                            @endforeach
                                        </select>
                                        <button type="button" class="ukc-btn ukc-btn--danger ukc-btn--sm" @click="removeCoauthor(i)" title="{{ __('Retirer ce co-auteur') }}">
                                            <x-ukc.icon name="close" :size="15" />
                                        </button>
                                    </div>
                                    {{-- Indice discret (existence d'un compte) --}}
                                    <div x-show="co.hint" x-cloak style="margin-top:5px;">
                                        <span class="author-hint" :class="'author-hint--'+co.hintType">
                                            <x-ukc.icon name="info" :size="14" /> <span x-text="co.hint"></span>
                                        </span>
                                    </div>
                                </div>
                            </template>
                        </div>
                        <button type="button" class="ukc-btn ukc-btn--ghost ukc-btn--sm" @click="addCoauthor()" style="margin-top:10px;">
                            <x-ukc.icon name="user-plus" :size="16" /> {{ __('Ajouter un co-auteur') }}
                        </button>
                        @error('coauthors') <span class="error" style="display:block;margin-top:6px;">{{ $message }}</span> @enderror
                        @error('coauthors.*.email') <span class="error" style="display:block;margin-top:6px;">{{ __('Un e-mail de co-auteur est invalide.') }}</span> @enderror

                        {{-- Autocomplétion des affiliations : le datalist est alimenté en direct
                             par l'API ROR (référentiel mondial des organisations de recherche). --}}
                        <datalist id="ukc-affiliations"></datalist>
                    </div>

                    {{-- Étape 3 : Résumé --}}
                    <div class="step-panel" :class="{ active: step===3 }">
                        <h2><x-ukc.icon name="pen" :size="20" /> {{ __('Résumé') }}</h2>
                        <div class="ukc-field full">
                            <label>{{ $config->label('abstract') }} <span class="req">*</span></label>
                            @include('partials.abstract-editor', ['name' => 'abstract', 'initial' => old('abstract', ''), 'min' => $config->abstractMin(), 'max' => $config->abstractMax(), 'help' => $config->help('abstract') ?: null])
                            @error('abstract') <span class="error">{{ $message }}</span> @enderror
                        </div>

                        @if ($config->enabled('keywords'))
                            <div class="ukc-field full" style="margin-top:14px;">
                                <label for="keywords">{{ $config->label('keywords') }} @if ($config->required('keywords'))<span class="req">*</span>@endif</label>
                                <input type="text" id="keywords" name="keywords" value="{{ old('keywords') }}"
                                       data-tom data-tom-create data-tom-max="{{ $config->keywordsMax() }}" data-keyword-separator="{{ $config->keywordSeparator() }}"
                                       placeholder="{{ __('Saisir un mot-clé puis Entrée…') }}">
                                <span class="hint">
                                    @if ($config->help('keywords')){{ $config->help('keywords') }}
                                    @elseif ($config->keywordsMin() > 0){{ __('De :min à :max mots-clés.', ['min' => $config->keywordsMin(), 'max' => $config->keywordsMax()]) }}
                                    @else Jusqu’à {{ $config->keywordsMax() }} mots-clés - {{ match ($config->keywordSeparator()) { 'semicolon' => 'séparez-les par un point-virgule', 'both' => 'séparez-les par une virgule ou un point-virgule', default => 'séparez-les par une virgule' } }}. @endif
                                </span>
                                @error('keywords') <span class="error">{{ $message }}</span> @enderror
                            </div>
                        @endif

                        @if (count($config->customFields()))
                            <div class="ukc-form-grid" style="margin-top:12px;">
                                @foreach ($config->customFields() as $field)
                                    <div class="ukc-field full">
                                        @php($reqAttr = $field['required'] ? 'data-custom-req=1' : '')
                                        @if ($field['type'] === 'checkbox')
                                            <label style="display:inline-flex;align-items:center;gap:8px;">
                                                <input type="checkbox" name="customAnswers[{{ $field['key'] }}]" value="1" @checked(old('customAnswers.'.$field['key'])) {{ $reqAttr }}>
                                                {{ $field['label'] }} @if ($field['required'])<span class="req">*</span>@endif
                                            </label>
                                        @else
                                            <label>{{ $field['label'] }} @if ($field['required'])<span class="req">*</span>@endif</label>
                                            @if ($field['type'] === 'textarea')
                                                <textarea rows="4" name="customAnswers[{{ $field['key'] }}]" {{ $reqAttr }}>{{ old('customAnswers.'.$field['key']) }}</textarea>
                                            @elseif ($field['type'] === 'select')
                                                <select name="customAnswers[{{ $field['key'] }}]" {{ $reqAttr }}>
                                                    <option value="">- {{ __('Choisir') }} -</option>
                                                    @foreach ($field['options'] as $opt)
                                                        <option value="{{ $opt }}" @selected(old('customAnswers.'.$field['key']) === $opt)>{{ $opt }}</option>
                                                    @endforeach
                                                </select>
                                            @else
                                                <input type="{{ in_array($field['type'], ['number', 'email', 'date']) ? $field['type'] : 'text' }}"
                                                       name="customAnswers[{{ $field['key'] }}]" value="{{ old('customAnswers.'.$field['key']) }}" {{ $reqAttr }}>
                                            @endif
                                        @endif
                                        @if ($field['help'])<span class="hint">{{ $field['help'] }}</span>@endif
                                        @error('customAnswers.' . $field['key']) <span class="error">{{ $message }}</span> @enderror
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>

                    @if ($fileStep)
                        {{-- Étape 4 : Fichier (seulement si le dépôt est activé) --}}
                        <div class="step-panel" :class="{ active: step===4 }">
                            <h2><x-ukc.icon name="file" :size="20" /> {{ __('Fichier joint') }}</h2>
                            <div class="ukc-field">
                                <label for="file">{{ $config->label('file') }}
                                    @if ($config->required('file'))<span class="req">*</span>@endif
                                    <span class="hint">({{ $config->help('file') ?: __('PDF/DOC/DOCX, max 8 Mo') }})</span>
                                </label>
                                <x-ukc.filedrop name="file" id="file" accept=".pdf,.doc,.docx" :hint="__('PDF/DOC/DOCX, max 8 Mo')" />
                                @error('file') <span class="error">{{ $message }}</span> @enderror
                            </div>
                        </div>
                    @endif

                    {{-- Étape Validation --}}
                    <div class="step-panel" :class="{ active: step==={{ $validationStep }} }">
                        <div class="recap-head">
                            <span class="recap-head__icon"><x-ukc.icon name="check-circle" :size="24" /></span>
                            <div>
                                <h2 style="margin:0;">{{ __('Validation') }}</h2>
                                <p class="ukc-muted" style="font-size:.88rem;margin-top:2px;">{{ __('Relisez votre communication avant l’envoi définitif. Cliquez une étape ci-dessus pour corriger.') }}</p>
                            </div>
                        </div>

                        <div class="recap-doc">
                            <div class="recap-doc__head">
                                <h3 class="recap-doc__title" x-text="title || '{{ __('Titre non renseigné') }}'"></h3>
                                <div class="recap-doc__chips">
                                    @if (count($tracks))
                                        <span class="recap-chip" x-show="track" x-cloak x-text="track"></span>
                                    @endif
                                    @if ($config->enabled('type'))
                                        <span class="recap-chip recap-chip--type" x-text="type === 'poster' ? '{{ __('Poster') }}' : '{{ __('Orale') }}'"></span>
                                    @endif
                                    <span class="recap-chip" x-show="presentationMode" x-cloak x-text="presentationMode === 'online' ? '{{ __('En ligne') }}' : '{{ __('En présentiel') }}'"></span>
                                </div>
                            </div>

                            <div class="recap-doc__body">
                                {{-- Auteur correspondant (identité du compte) --}}
                                <div class="recap-field">
                                    <div class="recap-field__label"><x-ukc.icon name="user" :size="15" /> {{ __('Auteur correspondant') }}</div>
                                    <div class="recap-author">
                                        <span class="recap-author__av" x-text="corrInitials()"></span>
                                        <div style="min-width:0;">
                                            <div class="recap-author__name" x-text="corrName() || '{{ __('Nom non renseigné') }}'"></div>
                                            <div class="recap-author__meta" x-text="corrMeta()"></div>
                                        </div>
                                    </div>
                                </div>

                                {{-- Co-auteurs --}}
                                <div class="recap-field">
                                    <div class="recap-field__label"><x-ukc.icon name="users" :size="15" /> {{ __('Co-auteurs') }}
                                        <span class="recap-badge-count" x-text="coauthorCount"></span>
                                    </div>
                                    <p class="recap-empty" x-show="coauthorCount === 0">{{ __('Aucun co-auteur.') }}</p>
                                    <ul class="recap-colist" x-show="coauthorCount > 0" x-cloak>
                                        <template x-for="(c, i) in coauthorList()" :key="i">
                                            <li>
                                                <span class="recap-colist__name" x-text="c.name || c.email"></span>
                                                <span class="recap-colist__aff" x-show="c.affiliation" x-text="c.affiliation"></span>
                                            </li>
                                        </template>
                                    </ul>
                                </div>

                                {{-- Résumé : contenu réellement saisi dans l'éditeur (pas le fichier) --}}
                                <div class="recap-field">
                                    <div class="recap-field__label"><x-ukc.icon name="pen" :size="15" /> {{ __('Résumé') }}
                                        <span class="recap-field__meta" x-show="abstractFilled()" x-cloak><span x-text="abstractWords()"></span> {{ __('mots') }}</span>
                                    </div>
                                    <p class="recap-empty" x-show="!abstractFilled()">{{ __('À compléter dans l’onglet Résumé.') }}</p>
                                    <div class="recap-abstract chat-md" x-show="abstractFilled()" x-cloak x-html="abstractPreview()"></div>
                                </div>

                                {{-- Mots-clés (sous le résumé) --}}
                                @if ($config->enabled('keywords'))
                                    <div class="recap-field">
                                        <div class="recap-field__label"><x-ukc.icon name="tag" :size="15" /> {{ $config->label('keywords') }}</div>
                                        <p class="recap-empty" x-show="keywordList().length === 0">-</p>
                                        <div class="recap-tags" x-show="keywordList().length" x-cloak>
                                            <template x-for="(k, i) in keywordList()" :key="i"><span class="recap-tag" x-text="k"></span></template>
                                        </div>
                                    </div>
                                @endif

                                @if ($fileStep)
                                    {{-- Fichier joint --}}
                                    <div class="recap-field">
                                        <div class="recap-field__label"><x-ukc.icon name="file" :size="15" /> {{ __('Fichier joint') }}</div>
                                        <div class="recap-status" :class="fileChosen() ? 'is-ok' : 'is-neutral'" style="max-width:300px;">
                                            <x-ukc.icon name="upload" :size="18" />
                                            <div>
                                                <div class="recap-status__k">{{ __('Fichier') }}</div>
                                                <div class="recap-status__v" x-text="fileChosen() ? '{{ __('Ajouté') }}' : '{{ __('Aucun') }}'"></div>
                                            </div>
                                        </div>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>

                    {{-- Navigation du wizard (les erreurs de validation d'étape
                         s'affichent en toast flottant, cf. submissionWizard.next). --}}
                    <div class="wizard-nav">
                        <button type="button" class="ukc-btn ukc-btn--ghost" @click="prev()" :style="step > 1 ? '' : 'visibility:hidden'">
                            <x-ukc.icon name="chevron" :size="16" style="transform:rotate(180deg);" /> {{ __('Précédent') }}
                        </button>
                        <div style="display:flex;gap:8px;">
                            <button type="button" class="ukc-btn ukc-btn--blue" @click="next()" x-show="step < {{ $lastStep }}">
                                {{ __('Suivant') }} <x-ukc.icon name="chevron" :size="16" />
                            </button>
                            <button type="submit" class="ukc-btn ukc-btn--green" x-show="step === {{ $lastStep }}" x-cloak>
                                <x-ukc.icon name="check" :size="18" /> {{ __('Envoyer ma communication') }}
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        @endif
    @endif
</x-layouts.participant>
