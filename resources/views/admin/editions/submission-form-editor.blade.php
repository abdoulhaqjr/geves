<x-layouts.admin title="Formulaire de soumission" :subtitle="'Champs, ordre & règles · '.($edition->title ?: $edition->year)">
    <x-slot:actions>
        <a href="{{ route('admin.editions.index') }}" class="ukc-btn ukc-btn--ghost ukc-btn--sm">← Éditions</a>
        <a href="{{ route('account.submissions.create', $edition->slug) }}" target="_blank" class="ukc-btn ukc-btn--outline ukc-btn--sm">
            <x-ukc.icon name="external" :size="15" /> Aperçu public
        </a>
        <button type="submit" form="formEditorForm" class="ukc-btn ukc-btn--blue ukc-btn--sm">
            <x-ukc.icon name="check" :size="16" /> Enregistrer
        </button>
    </x-slot:actions>

    @php
        $fieldMeta = [
            'title' => 'Texte court · obligatoire',
            'track' => 'Liste · axes de l’édition · '.($fields['track']['required'] ? 'obligatoire' : 'optionnel'),
            'type' => 'Liste · Orale / Poster · '.($fields['type']['required'] ? 'obligatoire' : 'optionnel'),
            'keywords' => 'Texte · '.$rules['keywords_min'].' à '.$rules['keywords_max'].' mots-clés',
            'abstract' => 'Zone de texte · '.$rules['word_min'].' à '.$rules['word_limit'].' mots · obligatoire',
            'file' => 'Upload · '.$rules['file_formats'].' · max '.$rules['file_max_mb'].' Mo',
        ];
    @endphp

    <div class="admin-page admin-submission-forms" x-data="{ tab: 'fields', ...formEditor(@js($custom)) }">
        <div class="ukc-tabs">
            <button type="button" @click="tab='fields'" :class="{ active: tab==='fields' }">Champs</button>
            <button type="button" @click="tab='rules'" :class="{ active: tab==='rules' }">Règles &amp; limites</button>
        </div>

        <form id="formEditorForm" method="POST" action="{{ route('admin.editions.form.update', $edition) }}">
            @csrf
            @method('PUT')

            {{-- ===================== CHAMPS ===================== --}}
            <div x-show="tab==='fields'">
                <div class="layout-edit">
                    {{-- Colonne principale : liste ordonnée des champs --}}
                    <div class="ukc-card" style="margin-bottom:0;">
                        <h3 style="font-size:14px;font-weight:800;margin-bottom:6px;">Champs (ordre d’affichage)</h3>
                        <div class="desc" style="font-size:13px;color:var(--ukc-muted);margin-bottom:14px;">
                            L’identité de l’auteur (nom, e-mail, affiliation) provient du compte. Les champs système ne sont pas supprimables.
                        </div>

                        {{-- Champs standard --}}
                        @foreach ($fields as $key => $conf)
                            <div x-data="{ open: false }">
                                <div class="field-item">
                                    <span class="handle" aria-hidden="true">⠿</span>
                                    <div class="meta">
                                        <strong>{{ $conf['label'] }} <code style="font-weight:600;">{{ $key }}</code></strong>
                                        <span>{{ $fieldMeta[$key] ?? '' }}</span>
                                    </div>
                                    @if (in_array($key, ['title', 'abstract', 'track']))
                                        <span class="ukc-pill ukc-pill--info">Système</span>
                                    @endif
                                    <button type="button" class="ukc-btn--icon" @click="open = !open" title="Éditer le champ">
                                        <x-ukc.icon name="pen" :size="15" />
                                    </button>
                                </div>

                                {{-- Panneau d'édition inline (inputs toujours dans le DOM → soumis) --}}
                                <div class="field-edit" x-show="open" x-cloak>
                                    <div class="ukc-form-grid">
                                        <div class="ukc-form-row">
                                            <label>Libellé</label>
                                            <input type="text" name="fields[{{ $key }}][label]" value="{{ old('fields.'.$key.'.label', $conf['label']) }}">
                                            @error("fields.{$key}.label") <span class="error">{{ $message }}</span> @enderror
                                        </div>
                                        <div class="ukc-form-row">
                                            <label>Aide (sous le champ)</label>
                                            <input type="text" name="fields[{{ $key }}][help]" value="{{ old('fields.'.$key.'.help', $conf['help']) }}">
                                        </div>
                                    </div>
                                    <div style="display:flex;gap:18px;flex-wrap:wrap;align-items:center;">
                                        @if (in_array($key, ['type', 'keywords', 'file']))
                                            <label class="ukc-check"><input type="checkbox" name="fields[{{ $key }}][enabled]" value="1" @checked($conf['enabled'])> Afficher ce champ</label>
                                        @endif
                                        @if (! in_array($key, ['title', 'abstract']))
                                            <label class="ukc-check"><input type="checkbox" name="fields[{{ $key }}][required]" value="1" @checked($conf['required'])> Obligatoire</label>
                                        @endif
                                        @if ($key === 'abstract')
                                            <span class="ukc-hint" style="font-size:12px;color:var(--ukc-light);">La limite de mots du résumé se règle dans l’onglet <strong>Règles &amp; limites</strong>.</span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @endforeach

                        {{-- Champ système « Auteurs » (fidèle au mockup, non éditable ici) --}}
                        <div class="field-item">
                            <span class="handle" aria-hidden="true">⠿</span>
                            <div class="meta">
                                <strong>Auteurs</strong>
                                <span>Répéteur · nom, affiliation, e-mail · min 1</span>
                            </div>
                            <span class="ukc-pill ukc-pill--info">Système</span>
                        </div>

                        {{-- Champs personnalisés (réordonnables) --}}
                        <template x-for="(co, i) in custom" :key="co.key">
                            <div>
                                <input type="hidden" :name="'custom['+i+'][key]'" :value="co.key">
                                <div class="field-item">
                                    <span class="handle" aria-hidden="true" style="display:flex;flex-direction:column;line-height:.7;">
                                        <button type="button" @click="move(i, -1)" :disabled="i === 0" aria-label="Monter" style="background:none;border:none;cursor:pointer;color:var(--ukc-light);">▲</button>
                                        <button type="button" @click="move(i, 1)" :disabled="i === custom.length - 1" aria-label="Descendre" style="background:none;border:none;cursor:pointer;color:var(--ukc-light);">▼</button>
                                    </span>
                                    <div class="meta">
                                        <strong x-text="co.label || 'Champ personnalisé'"></strong>
                                        <span x-text="({{ \Illuminate\Support\Js::from($customTypes) }})[co.type] + (co.required ? ' · obligatoire' : ' · optionnel')"></span>
                                    </div>
                                    <button type="button" class="ukc-btn--icon" @click="co.open = !co.open" title="Éditer"><x-ukc.icon name="pen" :size="15" /></button>
                                    <button type="button" class="ukc-btn--icon" @click="remove(i)" title="Supprimer" style="color:#dc2626;"><x-ukc.icon name="close" :size="15" /></button>
                                </div>

                                <div class="field-edit" x-show="co.open" x-cloak>
                                    <div class="ukc-form-grid">
                                        <div class="ukc-form-row"><label>Libellé</label><input type="text" :name="'custom['+i+'][label]'" x-model="co.label" placeholder="Ex. Numéro ORCID"></div>
                                        <div class="ukc-form-row">
                                            <label>Type</label>
                                            <select :name="'custom['+i+'][type]'" x-model="co.type">
                                                @foreach ($customTypes as $val => $lbl)<option value="{{ $val }}">{{ $lbl }}</option>@endforeach
                                            </select>
                                        </div>
                                    </div>
                                    <div class="ukc-form-row"><label>Aide</label><input type="text" :name="'custom['+i+'][help]'" x-model="co.help"></div>
                                    <label class="ukc-check"><input type="checkbox" :name="'custom['+i+'][required]'" value="1" x-model="co.required"> Obligatoire</label>
                                    <div class="ukc-form-row" x-show="co.type === 'select'">
                                        <label>Options (une par ligne)</label>
                                        <textarea :name="'custom['+i+'][options_text]'" x-model="co.options_text" rows="3" placeholder="Option A&#10;Option B"></textarea>
                                    </div>
                                </div>
                            </div>
                        </template>
                    </div>

                    {{-- Colonne latérale : palette de types de champs --}}
                    <div class="ukc-card" style="margin-bottom:0;">
                        <h3 style="font-size:14px;font-weight:800;margin-bottom:6px;">Types de champs disponibles</h3>
                        <div class="desc" style="font-size:13px;color:var(--ukc-muted);margin-bottom:10px;">À ajouter au formulaire.</div>
                        @foreach (['text' => 'Texte court', 'textarea' => 'Texte long', 'select' => 'Liste déroulante', 'checkbox' => 'Case à cocher', 'email' => 'E-mail', 'number' => 'Nombre', 'date' => 'Date'] as $t => $lbl)
                            <button type="button" class="ukc-btn ukc-btn--ghost ukc-btn--sm" style="width:100%;margin-bottom:6px;justify-content:flex-start;" @click="add('{{ $t }}')">
                                <x-ukc.icon name="plus" :size="14" /> {{ $lbl }}
                            </button>
                        @endforeach
                    </div>
                </div>
            </div>

            {{-- ===================== RÈGLES & LIMITES ===================== --}}
            <div x-show="tab==='rules'" x-cloak>
                <div class="ukc-card" style="max-width:640px;">
                    <h3 style="font-size:14px;font-weight:800;margin-bottom:6px;">Règles &amp; limites</h3>
                    <div class="desc" style="font-size:13px;color:var(--ukc-muted);margin-bottom:14px;">Appliquées au formulaire de dépôt de cette édition.</div>
                    <div class="ukc-form-grid">
                        <div class="ukc-form-row">
                            <label>Résumé — minimum de mots</label>
                            <input type="number" name="rules[word_min]" value="{{ old('rules.word_min', $rules['word_min']) }}" min="0" max="5000">
                        </div>
                        <div class="ukc-form-row">
                            <label>Résumé — maximum de mots</label>
                            <input type="number" name="rules[word_limit]" value="{{ old('rules.word_limit', $rules['word_limit']) }}" min="1" max="5000">
                        </div>
                        <div class="ukc-form-row">
                            <label>Mots-clés — minimum</label>
                            <input type="number" name="rules[keywords_min]" value="{{ old('rules.keywords_min', $rules['keywords_min']) }}" min="0" max="50">
                        </div>
                        <div class="ukc-form-row">
                            <label>Mots-clés — maximum</label>
                            <input type="number" name="rules[keywords_max]" value="{{ old('rules.keywords_max', $rules['keywords_max']) }}" min="1" max="50">
                        </div>
                        <div class="ukc-form-row">
                            <label>Nombre max d’auteurs</label>
                            <input type="number" name="rules[max_authors]" value="{{ old('rules.max_authors', $rules['max_authors']) }}" min="1" max="30">
                        </div>
                        <div class="ukc-form-row">
                            <label>Taille max fichier (Mo)</label>
                            <input type="number" name="rules[file_max_mb]" value="{{ old('rules.file_max_mb', $rules['file_max_mb']) }}" min="1" max="50">
                        </div>
                        <div class="ukc-form-row">
                            <label>Formats acceptés</label>
                            <input type="text" name="rules[file_formats]" value="{{ old('rules.file_formats', $rules['file_formats']) }}" placeholder="pdf, docx">
                        </div>
                    </div>
                    <label class="ukc-check"><input type="checkbox" name="rules[require_account]" value="1" @checked($rules['require_account'])> Compte participant requis pour soumettre</label>
                    <label class="ukc-check"><input type="checkbox" name="rules[no_duplicate]" value="1" @checked($rules['no_duplicate'])> Un seul dépôt par titre (anti-doublon)</label>
                    <label class="ukc-check"><input type="checkbox" name="rules[live_counter]" value="1" @checked($rules['live_counter'])> Afficher le compteur de mots en direct</label>
                    <label class="ukc-check"><input type="checkbox" name="rules[captcha]" value="1" @checked($rules['captcha'])> Captcha sur le formulaire public</label>
                </div>
            </div>

            <div style="display:flex;justify-content:flex-end;margin-top:16px;">
                <button type="submit" class="ukc-btn ukc-btn--blue">
                    <x-ukc.icon name="check" :size="18" /> Enregistrer le formulaire
                </button>
            </div>
        </form>
    </div>
</x-layouts.admin>
