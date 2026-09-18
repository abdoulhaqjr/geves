@php
    $wizardInit = [
        'title' => old('title', ''),
        'track' => old('track', $tracks[0] ?? ''),
        'type' => old('type', 'oral'),
        'keywords' => array_values(array_filter(array_map('trim', explode(',', (string) old('keywords', ''))))),
        'coauthors' => array_values(old('coauthors', [])),
        'errorStep' => (int) session('submission_error_step', 1),
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
                    <a href="{{ route('account.submissions.create', $edition->slug) }}" class="ukc-btn ukc-btn--outline">
                        <x-ukc.icon name="plus" :size="18" /> {{ __('Soumettre une autre') }}
                    </a>
                @endif
            </div>
        </div>
    @else
        {{-- Contexte édition --}}
        <div class="ukc-card" style="margin-bottom:18px;">
            <div style="font-size:.8rem;font-weight:700;color:var(--ukc-muted);text-transform:uppercase;letter-spacing:.04em;">
                {{ $edition->edition_label }}
            </div>
            <div style="font-weight:800;font-size:1.1rem;margin-top:2px;">{{ $edition->title }}</div>
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
                        <span style="font-weight:500;color:var(--ukc-muted);">· {{ $account->grade }}</span>
                    </div>
                    <div style="font-size:.85rem;color:var(--ukc-muted);">
                        {{ $account->email }} — {{ $account->affiliation ?: __('affiliation manquante') }}
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
        @elseif (! $edition->submission_open)
            <div class="ukc-alert ukc-alert--err">
                <span class="ico"><x-ukc.icon name="info" :size="18" /></span>
                <div>{!! __('Les soumissions sont actuellement <strong>fermées</strong> pour cette édition.') !!}</div>
            </div>
        @elseif ($remaining <= 0)
            <div class="ukc-alert ukc-alert--info">
                <span class="ico"><x-ukc.icon name="info" :size="18" /></span>
                <div>{{ __('Vous avez atteint le nombre maximum de communications pour cette édition.') }}</div>
            </div>
        @else
            {{-- Formulaire multi-étapes (wizard) --}}
            <div x-data="submissionWizard(@js($wizardInit))">
                <div class="form-steps">
                    <div class="s" :class="{ active: step===1, done: step>1 }" @click="step=1"><span class="n">1</span> {{ __('Général') }}</div>
                    <div class="s" :class="{ active: step===2, done: step>2 }" @click="step=2"><span class="n">2</span> {{ __('Auteurs') }}</div>
                    <div class="s" :class="{ active: step===3, done: step>3 }" @click="step=3"><span class="n">3</span> {{ __('Résumé') }}</div>
                    <div class="s" :class="{ active: step===4, done: step>4 }" @click="step=4"><span class="n">4</span> {{ __('Fichiers') }}</div>
                    <div class="s" :class="{ active: step===5 }" @click="step=5"><span class="n">5</span> {{ __('Validation') }}</div>
                </div>

                <form method="POST" action="{{ route('account.submissions.store', $edition->slug) }}" enctype="multipart/form-data" class="ukc-card">
                    @csrf
                    {{-- Étape 1 : Général --}}
                    <div class="step-panel" :class="{ active: step===1 }">
                        <h2><x-ukc.icon name="info" :size="20" /> {{ __('Informations générales') }}</h2>
                        <div class="ukc-form-grid">
                            <div class="ukc-field full">
                                <label for="title">{{ $config->label('title') }} <span class="req">*</span></label>
                                <input type="text" id="title" name="title" x-model="title">
                                @if ($config->help('title'))<span class="hint">{{ $config->help('title') }}</span>@endif
                                @error('title') <span class="error">{{ $message }}</span> @enderror
                            </div>

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
                                    <label for="type">{{ $config->label('type') }} <span class="req">*</span></label>
                                    <select id="type" name="type" x-model="type">
                                        <option value="oral">{{ __('Communication orale') }}</option>
                                        <option value="poster">{{ __('Poster') }}</option>
                                    </select>
                                    @if ($config->help('type'))<span class="hint">{{ $config->help('type') }}</span>@endif
                                    @error('type') <span class="error">{{ $message }}</span> @enderror
                                </div>
                            @endif

                            @if ($config->enabled('keywords'))
                                <div class="ukc-field full">
                                    <input type="hidden" name="keywords" :value="keywordsString">
                                    <label for="keywords-input">{{ $config->label('keywords') }}
                                        @if ($config->required('keywords'))<span class="req">*</span>@endif
                                    </label>
                                    <div class="ukc-tags" @click="$refs.kwinput.focus()">
                                        <template x-for="(tag, i) in keywords" :key="tag">
                                            <span class="ukc-tag">
                                                <span x-text="tag"></span>
                                                <button type="button" @click="removeTag(i)" aria-label="Retirer">&times;</button>
                                            </span>
                                        </template>
                                        <input type="text" id="keywords-input" x-ref="kwinput" x-model="kwDraft"
                                               @keydown.enter.prevent="addTag()" @keydown.comma.prevent="addTag()"
                                               @keydown.backspace="tagBackspace()" @blur="addTag()"
                                               placeholder="{{ __('Saisir un mot-clé puis Entrée…') }}">
                                    </div>
                                    <span class="hint">{{ $config->help('keywords') ?: __('Entrée ou virgule pour ajouter un mot-clé.') }}</span>
                                    @error('keywords') <span class="error">{{ $message }}</span> @enderror
                                </div>
                            @endif
                        </div>
                    </div>

                    {{-- Étape 2 : Auteurs --}}
                    <div class="step-panel" :class="{ active: step===2 }">
                        <h2><x-ukc.icon name="users" :size="20" /> {{ __('Auteurs') }}</h2>
                        <p class="ukc-muted" style="font-size:.88rem;margin-bottom:12px;">
                            {{ __('Vous êtes l’auteur correspondant. Ajoutez ici les co-auteurs de la communication.') }}
                        </p>
                        <div style="display:flex;flex-direction:column;gap:10px;">
                            <template x-for="(co, i) in coauthors" :key="i">
                                <div style="display:grid;grid-template-columns:1.2fr 1.2fr 1.4fr .8fr auto;gap:8px;align-items:center;">
                                    <input type="text" :name="'coauthors['+i+'][name]'" x-model="co.name" placeholder="{{ __('Nom Prénom') }}"
                                           style="border:1.5px solid var(--ukc-border);border-radius:10px;padding:9px 12px;font-size:13.5px;">
                                    <input type="email" :name="'coauthors['+i+'][email]'" x-model="co.email" placeholder="{{ __('E-mail') }}"
                                           style="border:1.5px solid var(--ukc-border);border-radius:10px;padding:9px 12px;font-size:13.5px;">
                                    <input type="text" :name="'coauthors['+i+'][affiliation]'" x-model="co.affiliation" placeholder="{{ __('Affiliation') }}"
                                           style="border:1.5px solid var(--ukc-border);border-radius:10px;padding:9px 12px;font-size:13.5px;">
                                    <input type="text" :name="'coauthors['+i+'][grade]'" x-model="co.grade" placeholder="{{ __('Grade') }}"
                                           style="border:1.5px solid var(--ukc-border);border-radius:10px;padding:9px 12px;font-size:13.5px;">
                                    <button type="button" class="ukc-btn ukc-btn--danger ukc-btn--sm" @click="removeCoauthor(i)">
                                        <x-ukc.icon name="close" :size="16" />
                                    </button>
                                </div>
                            </template>
                        </div>
                        <button type="button" class="ukc-btn ukc-btn--ghost ukc-btn--sm" @click="addCoauthor()" style="margin-top:10px;">
                            <x-ukc.icon name="user-plus" :size="16" /> {{ __('Ajouter un co-auteur') }}
                        </button>
                        @error('coauthors.*.email') <span class="error" style="display:block;margin-top:6px;">{{ __('Un e-mail de co-auteur est invalide.') }}</span> @enderror
                    </div>

                    {{-- Étape 3 : Résumé --}}
                    <div class="step-panel" :class="{ active: step===3 }">
                        <h2><x-ukc.icon name="pen" :size="20" /> {{ __('Résumé') }}</h2>
                        <div class="ukc-field full">
                            <label>{{ $config->label('abstract') }} <span class="req">*</span></label>
                            @include('partials.abstract-editor', ['name' => 'abstract', 'initial' => old('abstract', ''), 'min' => $config->abstractMin(), 'help' => $config->help('abstract') ?: null])
                            @error('abstract') <span class="error">{{ $message }}</span> @enderror
                        </div>

                        @if (count($config->customFields()))
                            <div class="ukc-form-grid" style="margin-top:12px;">
                                @foreach ($config->customFields() as $field)
                                    <div class="ukc-field full">
                                        @if ($field['type'] === 'checkbox')
                                            <label style="display:inline-flex;align-items:center;gap:8px;">
                                                <input type="checkbox" name="customAnswers[{{ $field['key'] }}]" value="1" @checked(old('customAnswers.'.$field['key']))>
                                                {{ $field['label'] }} @if ($field['required'])<span class="req">*</span>@endif
                                            </label>
                                        @else
                                            <label>{{ $field['label'] }} @if ($field['required'])<span class="req">*</span>@endif</label>
                                            @if ($field['type'] === 'textarea')
                                                <textarea rows="4" name="customAnswers[{{ $field['key'] }}]">{{ old('customAnswers.'.$field['key']) }}</textarea>
                                            @elseif ($field['type'] === 'select')
                                                <select name="customAnswers[{{ $field['key'] }}]">
                                                    <option value="">— {{ __('Choisir') }} —</option>
                                                    @foreach ($field['options'] as $opt)
                                                        <option value="{{ $opt }}" @selected(old('customAnswers.'.$field['key']) === $opt)>{{ $opt }}</option>
                                                    @endforeach
                                                </select>
                                            @else
                                                <input type="{{ in_array($field['type'], ['number', 'email', 'date']) ? $field['type'] : 'text' }}"
                                                       name="customAnswers[{{ $field['key'] }}]" value="{{ old('customAnswers.'.$field['key']) }}">
                                            @endif
                                        @endif
                                        @if ($field['help'])<span class="hint">{{ $field['help'] }}</span>@endif
                                        @error('customAnswers.' . $field['key']) <span class="error">{{ $message }}</span> @enderror
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>

                    {{-- Étape 4 : Fichier --}}
                    <div class="step-panel" :class="{ active: step===4 }">
                        <h2><x-ukc.icon name="file" :size="20" /> {{ __('Fichier joint') }}</h2>
                        @if ($config->enabled('file'))
                            <div class="ukc-field">
                                <label for="file">{{ $config->label('file') }}
                                    @if ($config->required('file'))<span class="req">*</span>@endif
                                    <span class="hint">({{ $config->help('file') ?: __('PDF/DOC/DOCX, max 8 Mo') }})</span>
                                </label>
                                <x-ukc.filedrop name="file" id="file" accept=".pdf,.doc,.docx" :hint="__('PDF/DOC/DOCX, max 8 Mo')" />
                                @error('file') <span class="error">{{ $message }}</span> @enderror
                            </div>
                        @else
                            <p class="ukc-muted" style="font-size:.9rem;">{{ __('Aucun fichier requis pour cette édition. Vous pouvez passer à la validation.') }}</p>
                        @endif
                    </div>

                    {{-- Étape 5 : Validation --}}
                    <div class="step-panel" :class="{ active: step===5 }">
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
                                        <span class="recap-chip recap-chip--type" x-text="type === 'poster' ? '{{ __('Poster') }}' : '{{ __('Communication orale') }}'"></span>
                                    @endif
                                </div>
                            </div>

                            <div class="recap-doc__body">
                                {{-- Auteur correspondant (identité du compte) --}}
                                <div class="recap-field">
                                    <div class="recap-field__label"><x-ukc.icon name="user" :size="15" /> {{ __('Auteur correspondant') }}</div>
                                    <div class="recap-author">
                                        <span class="recap-author__av">{{ $account->initials }}</span>
                                        <div style="min-width:0;">
                                            <div class="recap-author__name">{{ $account->full_name }}</div>
                                            <div class="recap-author__meta">{{ $account->affiliation ?: __('affiliation manquante') }}</div>
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

                                @if ($config->enabled('keywords'))
                                    <div class="recap-field">
                                        <div class="recap-field__label"><x-ukc.icon name="tag" :size="15" /> {{ $config->label('keywords') }}</div>
                                        <p class="recap-empty" x-show="keywords.length === 0">—</p>
                                        <div class="recap-tags" x-show="keywords.length" x-cloak>
                                            <template x-for="(k, i) in keywords" :key="i"><span class="recap-tag" x-text="k"></span></template>
                                        </div>
                                    </div>
                                @endif

                                {{-- Statuts résumé + fichier --}}
                                <div class="recap-statuses">
                                    <div class="recap-status" :class="abstractFilled() ? 'is-ok' : 'is-todo'">
                                        <x-ukc.icon name="file" :size="16" />
                                        <div>
                                            <div class="recap-status__k">{{ __('Résumé') }}</div>
                                            <div class="recap-status__v" x-text="abstractFilled() ? (abstractWords() + ' {{ __('mots') }}') : '{{ __('à compléter') }}'"></div>
                                        </div>
                                    </div>
                                    @if ($config->enabled('file'))
                                        <div class="recap-status" :class="fileChosen() ? 'is-ok' : 'is-neutral'">
                                            <x-ukc.icon name="upload" :size="16" />
                                            <div>
                                                <div class="recap-status__k">{{ __('Fichier joint') }}</div>
                                                <div class="recap-status__v" x-text="fileChosen() ? '{{ __('Ajouté') }}' : '{{ __('Aucun') }}'"></div>
                                            </div>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Navigation du wizard --}}
                    <div class="wizard-nav">
                        <button type="button" class="ukc-btn ukc-btn--ghost" @click="step--" :style="step > 1 ? '' : 'visibility:hidden'">
                            <x-ukc.icon name="chevron" :size="16" style="transform:rotate(180deg);" /> {{ __('Précédent') }}
                        </button>
                        <div style="display:flex;gap:8px;">
                            <button type="button" class="ukc-btn ukc-btn--blue" @click="step++" x-show="step < 5">
                                {{ __('Suivant') }} <x-ukc.icon name="chevron" :size="16" />
                            </button>
                            <button type="submit" class="ukc-btn ukc-btn--green" x-show="step === 5" x-cloak>
                                <x-ukc.icon name="check" :size="18" /> {{ __('Envoyer ma communication') }}
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        @endif
    @endif
</x-layouts.participant>
