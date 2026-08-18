<div>
    @if ($successRef)
        {{-- Carte de succès --}}
        <div class="ukc-card" style="text-align:center;max-width:620px;margin:0 auto;">
            <div style="width:64px;height:64px;border-radius:50%;margin:0 auto 16px;background:#dcfce7;color:#166534;
                        display:flex;align-items:center;justify-content:center;">
                <x-ukc.icon name="check" :size="32" />
            </div>
            <h2 style="justify-content:center;">Communication reçue</h2>
            <p style="color:var(--ukc-muted);line-height:1.6;">
                Votre soumission a bien été enregistrée sous la référence
                <strong style="color:var(--ukc-text);font-family:ui-monospace,monospace;">{{ $successRef }}</strong>.
                Vous pouvez suivre son statut depuis « Mes communications ».
            </p>
            <div style="display:flex;gap:12px;justify-content:center;flex-wrap:wrap;margin-top:20px;">
                <a href="{{ route('account.submissions.index') }}" wire:navigate class="ukc-btn ukc-btn--blue">
                    <x-ukc.icon name="inbox" :size="18" /> Mes communications
                </a>
                @if ($this->remaining() > 0)
                    <a href="{{ route('account.submissions.create', $edition->slug) }}" class="ukc-btn ukc-btn--outline">
                        <x-ukc.icon name="plus" :size="18" /> Soumettre une autre
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
                {{ $this->remaining() }} communication(s) restante(s) sur {{ $this->maxCommunications() }}.
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
                        {{ $account->email }} — {{ $account->affiliation ?: 'affiliation manquante' }}
                    </div>
                </div>
                <a href="{{ route('account.profile') }}" wire:navigate class="ukc-btn ukc-btn--ghost ukc-btn--sm">
                    <x-ukc.icon name="pen" :size="16" /> Modifier mon profil
                </a>
            </div>
        </div>

        {{-- Gating --}}
        @if (! $account->canSubmit())
            <div class="ukc-alert ukc-alert--warn">
                <span class="ico"><x-ukc.icon name="lock" :size="18" /></span>
                <div>
                    Pour soumettre, votre <strong>e-mail doit être vérifié</strong> et votre
                    <strong>profil complété</strong> (affiliation).
                    @if (! $account->hasVerifiedEmail())
                        <a href="{{ route('account.verification.notice') }}" wire:navigate style="text-decoration:underline;font-weight:700;">Vérifier mon e-mail</a>
                    @endif
                    @if (blank($account->affiliation))
                        <a href="{{ route('account.profile') }}" wire:navigate style="text-decoration:underline;font-weight:700;">Compléter mon profil</a>
                    @endif
                </div>
            </div>
        @elseif (! $edition->submission_open)
            <div class="ukc-alert ukc-alert--err">
                <span class="ico"><x-ukc.icon name="info" :size="18" /></span>
                <div>Les soumissions sont actuellement <strong>fermées</strong> pour cette édition.</div>
            </div>
        @elseif ($this->remaining() <= 0)
            <div class="ukc-alert ukc-alert--info">
                <span class="ico"><x-ukc.icon name="info" :size="18" /></span>
                <div>Vous avez atteint le nombre maximum de communications pour cette édition.</div>
            </div>
        @else
            {{-- Formulaire multi-étapes (wizard) --}}
            <div x-data="{ step: 1 }" @submission-invalid.window="step = $event.detail.step">
                <div class="form-steps">
                    <div class="s" :class="{ active: step===1, done: step>1 }" @click="step=1"><span class="n">1</span> Général</div>
                    <div class="s" :class="{ active: step===2, done: step>2 }" @click="step=2"><span class="n">2</span> Auteurs</div>
                    <div class="s" :class="{ active: step===3, done: step>3 }" @click="step=3"><span class="n">3</span> Résumé</div>
                    <div class="s" :class="{ active: step===4, done: step>4 }" @click="step=4"><span class="n">4</span> Fichiers</div>
                    <div class="s" :class="{ active: step===5 }" @click="step=5"><span class="n">5</span> Validation</div>
                </div>

                <form wire:submit="submit" class="ukc-card">
                    {{-- Étape 1 : Général --}}
                    <div class="step-panel" :class="{ active: step===1 }">
                        <h2><x-ukc.icon name="info" :size="20" /> Informations générales</h2>
                        <div class="ukc-form-grid">
                            <div class="ukc-field full">
                                <label for="title">{{ $config->label('title') }} <span class="req">*</span></label>
                                <input type="text" id="title" wire:model="title">
                                @if ($config->help('title'))<span class="hint">{{ $config->help('title') }}</span>@endif
                                @error('title') <span class="error">{{ $message }}</span> @enderror
                            </div>

                            @if (count($this->tracks()))
                                <div class="ukc-field">
                                    <label for="track">{{ $config->label('track') }} @if ($config->required('track'))<span class="req">*</span>@endif</label>
                                    <select id="track" wire:model="track">
                                        @foreach ($this->tracks() as $t)
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
                                    <select id="type" wire:model="type">
                                        <option value="oral">Communication orale</option>
                                        <option value="poster">Poster</option>
                                    </select>
                                    @if ($config->help('type'))<span class="hint">{{ $config->help('type') }}</span>@endif
                                    @error('type') <span class="error">{{ $message }}</span> @enderror
                                </div>
                            @endif

                            @if ($config->enabled('keywords'))
                                <div class="ukc-field full"
                                     x-data="ukcTags(@js($keywords))"
                                     x-init="$watch('tags', v => $wire.set('keywords', v.join(', '), false))">
                                    <label for="keywords-input">{{ $config->label('keywords') }}
                                        @if ($config->required('keywords'))<span class="req">*</span>@endif
                                    </label>
                                    <div class="ukc-tags" @click="$refs.kwinput.focus()">
                                        <template x-for="(tag, i) in tags" :key="tag">
                                            <span class="ukc-tag">
                                                <span x-text="tag"></span>
                                                <button type="button" @click="remove(i)" aria-label="Retirer">&times;</button>
                                            </span>
                                        </template>
                                        <input type="text" id="keywords-input" x-ref="kwinput" x-model="draft"
                                               @keydown.enter.prevent="add()" @keydown.comma.prevent="add()"
                                               @keydown.backspace="backspace()" @blur="add()"
                                               placeholder="Saisir un mot-clé puis Entrée…">
                                    </div>
                                    <span class="hint">{{ $config->help('keywords') ?: 'Entrée ou virgule pour ajouter un mot-clé.' }}</span>
                                    @error('keywords') <span class="error">{{ $message }}</span> @enderror
                                </div>
                            @endif
                        </div>
                    </div>

                    {{-- Étape 2 : Auteurs --}}
                    <div class="step-panel" :class="{ active: step===2 }">
                        <h2><x-ukc.icon name="users" :size="20" /> Auteurs</h2>
                        <p class="ukc-muted" style="font-size:.88rem;margin-bottom:12px;">
                            Vous êtes l’auteur correspondant. Ajoutez ici les co-auteurs de la communication.
                        </p>
                        <div style="display:flex;flex-direction:column;gap:10px;">
                            @foreach ($coauthors as $i => $co)
                                <div wire:key="co-{{ $i }}" style="display:grid;grid-template-columns:1.2fr 1.2fr 1.4fr .8fr auto;gap:8px;align-items:center;">
                                    <input type="text" wire:model="coauthors.{{ $i }}.name" placeholder="Nom Prénom"
                                           style="border:1.5px solid var(--ukc-border);border-radius:10px;padding:9px 12px;font-size:13.5px;">
                                    <input type="email" wire:model="coauthors.{{ $i }}.email" placeholder="E-mail"
                                           style="border:1.5px solid var(--ukc-border);border-radius:10px;padding:9px 12px;font-size:13.5px;">
                                    <input type="text" wire:model="coauthors.{{ $i }}.affiliation" placeholder="Affiliation"
                                           style="border:1.5px solid var(--ukc-border);border-radius:10px;padding:9px 12px;font-size:13.5px;">
                                    <input type="text" wire:model="coauthors.{{ $i }}.grade" placeholder="Grade"
                                           style="border:1.5px solid var(--ukc-border);border-radius:10px;padding:9px 12px;font-size:13.5px;">
                                    <button type="button" class="ukc-btn ukc-btn--danger ukc-btn--sm" wire:click="removeCoauthor({{ $i }})">
                                        <x-ukc.icon name="close" :size="16" />
                                    </button>
                                </div>
                            @endforeach
                        </div>
                        <button type="button" class="ukc-btn ukc-btn--ghost ukc-btn--sm" wire:click="addCoauthor" style="margin-top:10px;">
                            <x-ukc.icon name="user-plus" :size="16" /> Ajouter un co-auteur
                        </button>
                        @error('coauthors.*.email') <span class="error" style="display:block;margin-top:6px;">Un e-mail de co-auteur est invalide.</span> @enderror
                    </div>

                    {{-- Étape 3 : Résumé --}}
                    <div class="step-panel" :class="{ active: step===3 }">
                        <h2><x-ukc.icon name="pen" :size="20" /> Résumé</h2>
                        <div class="ukc-field full">
                            <label>{{ $config->label('abstract') }} <span class="req">*</span></label>
                            @include('partials.abstract-editor', ['initial' => $abstract, 'min' => $config->abstractMin(), 'help' => $config->help('abstract') ?: null])
                            @error('abstract') <span class="error">{{ $message }}</span> @enderror
                        </div>

                        @if (count($config->customFields()))
                            <div class="ukc-form-grid" style="margin-top:12px;">
                                @foreach ($config->customFields() as $field)
                                    <div class="ukc-field full" wire:key="cf-{{ $field['key'] }}">
                                        @if ($field['type'] === 'checkbox')
                                            <label style="display:inline-flex;align-items:center;gap:8px;">
                                                <input type="checkbox" wire:model="customAnswers.{{ $field['key'] }}">
                                                {{ $field['label'] }} @if ($field['required'])<span class="req">*</span>@endif
                                            </label>
                                        @else
                                            <label>{{ $field['label'] }} @if ($field['required'])<span class="req">*</span>@endif</label>
                                            @if ($field['type'] === 'textarea')
                                                <textarea rows="4" wire:model="customAnswers.{{ $field['key'] }}"></textarea>
                                            @elseif ($field['type'] === 'select')
                                                <select wire:model="customAnswers.{{ $field['key'] }}">
                                                    <option value="">— Choisir —</option>
                                                    @foreach ($field['options'] as $opt)
                                                        <option value="{{ $opt }}">{{ $opt }}</option>
                                                    @endforeach
                                                </select>
                                            @else
                                                <input type="{{ in_array($field['type'], ['number', 'email', 'date']) ? $field['type'] : 'text' }}"
                                                       wire:model="customAnswers.{{ $field['key'] }}">
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
                        <h2><x-ukc.icon name="file" :size="20" /> Fichier joint</h2>
                        @if ($config->enabled('file'))
                            <div class="ukc-field">
                                <label for="file">{{ $config->label('file') }}
                                    @if ($config->required('file'))<span class="req">*</span>@endif
                                    <span class="hint">({{ $config->help('file') ?: 'PDF/DOC/DOCX, max 8 Mo' }})</span>
                                </label>
                                <input type="file" id="file" wire:model="file" accept=".pdf,.doc,.docx">
                                <div wire:loading wire:target="file" class="hint">Téléversement…</div>
                                @error('file') <span class="error">{{ $message }}</span> @enderror
                            </div>
                        @else
                            <p class="ukc-muted" style="font-size:.9rem;">Aucun fichier requis pour cette édition. Vous pouvez passer à la validation.</p>
                        @endif
                    </div>

                    {{-- Étape 5 : Validation --}}
                    <div class="step-panel" :class="{ active: step===5 }">
                        <h2><x-ukc.icon name="check-circle" :size="20" /> Validation</h2>
                        <p class="ukc-muted" style="font-size:.88rem;margin-bottom:14px;">Vérifiez le récapitulatif avant l’envoi.</p>
                        <div class="recap-list">
                            <div class="r"><span class="k">Titre</span><span class="v" x-text="$wire.title || '—'"></span></div>
                            @if (count($this->tracks()))
                                <div class="r"><span class="k">Axe</span><span class="v" x-text="$wire.track || '—'"></span></div>
                            @endif
                            @if ($config->enabled('type'))
                                <div class="r"><span class="k">Type</span><span class="v" x-text="$wire.type === 'poster' ? 'Poster' : 'Communication orale'"></span></div>
                            @endif
                            @if ($config->enabled('keywords'))
                                <div class="r"><span class="k">{{ $config->label('keywords') }}</span><span class="v" x-text="$wire.keywords || '—'"></span></div>
                            @endif
                            <div class="r"><span class="k">Résumé</span><span class="v" x-text="($wire.abstract && $wire.abstract.replace(/<[^>]*>/g,'').trim()) ? 'Rédigé' : 'À compléter'"></span></div>
                            <div class="r"><span class="k">Co-auteurs</span><span class="v" x-text="(Array.isArray($wire.coauthors) ? $wire.coauthors.filter(c => (c.name||'').trim() || (c.email||'').trim()).length : 0) + ' co-auteur(s)'"></span></div>
                            @if ($config->enabled('file'))
                                <div class="r"><span class="k">Fichier joint</span><span class="v" x-text="$wire.file ? 'Ajouté' : 'Aucun'"></span></div>
                            @endif
                        </div>
                    </div>

                    {{-- Navigation du wizard --}}
                    <div class="wizard-nav">
                        <button type="button" class="ukc-btn ukc-btn--ghost" @click="step--" :style="step > 1 ? '' : 'visibility:hidden'">
                            <x-ukc.icon name="chevron" :size="16" style="transform:rotate(180deg);" /> Précédent
                        </button>
                        <div style="display:flex;gap:8px;">
                            <button type="button" class="ukc-btn ukc-btn--blue" @click="step++" x-show="step < 5">
                                Suivant <x-ukc.icon name="chevron" :size="16" />
                            </button>
                            <button type="submit" class="ukc-btn ukc-btn--green" x-show="step === 5" x-cloak
                                    wire:loading.attr="disabled" wire:target="submit">
                                <span wire:loading.remove wire:target="submit"><x-ukc.icon name="check" :size="18" /> Envoyer ma communication</span>
                                <span wire:loading wire:target="submit">Envoi…</span>
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        @endif
    @endif
</div>
