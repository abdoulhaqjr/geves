<x-layouts.admin title="Formulaire de soumission" subtitle="Champs et ordre d’affichage">
    <x-slot:actions>
        <a href="{{ route('account.submissions.create', $event->slug) }}" target="_blank" class="ukc-btn ukc-btn--outline ukc-btn--sm">
            <x-ukc.icon name="external" :size="15" /> Aperçu public
        </a>
    </x-slot:actions>

    @php
        $systemKeys = ['title', 'abstract', 'track'];
        $fieldMeta = [
            'title' => 'Texte court · obligatoire',
            'track' => 'Liste · axes de l’événement',
            'type' => 'Liste · Orale / Poster',
            'keywords' => 'Texte · '.$rules['keywords_min'].' à '.$rules['keywords_max'].' mots-clés',
            'abstract' => 'Zone de texte · '.$rules['word_min'].' à '.$rules['word_limit'].' mots · obligatoire',
            'file' => 'Upload · '.$rules['file_formats'].' · max '.$rules['file_max_mb'].' Mo',
        ];

        // Liste ordonnée des champs standard, avec leurs contraintes d'événement.
        $standardList = [];
        foreach ($fields as $key => $conf) {
            $standardList[] = [
                'key' => $key,
                'label' => $conf['label'],
                'help' => $conf['help'],
                'separator' => $key === 'keywords' ? $conf['separator'] : null,
                'enabled' => (bool) $conf['enabled'],
                'required' => (bool) $conf['required'],
                'system' => in_array($key, $systemKeys, true),
                'canEnabled' => in_array($key, ['type', 'keywords', 'file'], true),
                'canRequired' => ! in_array($key, ['title', 'abstract'], true),
                'labelRequired' => in_array($key, ['title', 'abstract'], true),
                'isAbstract' => $key === 'abstract',
                'meta' => $fieldMeta[$key] ?? '',
            ];
        }

        $editorConfig = [
            'standard' => $standardList,
            'custom' => $custom,
            'customTypes' => $customTypes,
            'saveUrl' => route('admin.evenements.form.update', $event),
            'csrf' => csrf_token(),
        ];
    @endphp

    <div class="admin-page admin-submission-forms" x-data="formEditor(@js($editorConfig))">
        <div class="page-head" style="display:flex;align-items:flex-end;justify-content:space-between;gap:16px;flex-wrap:wrap;margin-bottom:14px;">
            <div class="ukc-field" style="min-width:320px;max-width:460px;margin:0;">
                <label for="event_id">Événement</label>
                <select id="event_id" name="event_id" onchange="window.location = '{{ url('admin/editions') }}/' + this.value + '/formulaire'">
                    @foreach ($events as $ed)<option value="{{ $ed->id }}" @selected((string) $ed->id === $event_id)>{{ $ed->title ?: $ed->year }}</option>@endforeach
                </select>
            </div>
        </div>

        @include('admin.submissions._nav', ['current' => 'formulaire'])

        {{-- ===================== CHAMPS ===================== --}}
        <div class="layout-edit">
            {{-- Colonne principale : liste ordonnée des champs --}}
            <div class="ukc-card" style="margin-bottom:0;">
                <h3 style="font-size:14px;font-weight:800;margin-bottom:6px;">Champs (ordre d’affichage)</h3>
                <div class="desc" style="font-size:13px;color:var(--ukc-muted);margin-bottom:14px;">
                    L’identité de l’auteur (nom, e-mail, affiliation) provient du compte. Les champs système ne sont pas supprimables.
                    Toute modification (événement, ajout, réordonnancement) est <strong>enregistrée automatiquement</strong>.
                </div>

                {{-- Champs standard --}}
                <template x-for="(f, i) in standard" :key="f.key">
                    <div class="field-item">
                        <span class="handle" aria-hidden="true">⠿</span>
                        <div class="meta">
                            <strong x-text="f.label"></strong> <code style="font-weight:600;" x-text="f.key"></code>
                            <span x-text="metaFor(f)"></span>
                        </div>
                        <span class="ukc-pill ukc-pill--info" x-show="f.system" x-cloak>Système</span>
                        <span class="ukc-pill ukc-pill--muted" x-show="f.canEnabled && !f.enabled" x-cloak>Masqué</span>
                        <button type="button" class="ukc-btn--icon" @click="openEditStandard(i)" title="Éditer le champ">
                            <x-ukc.icon name="pen" :size="15" />
                        </button>
                    </div>
                </template>

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
                    <div class="field-item">
                        <span class="handle" aria-hidden="true" style="display:flex;flex-direction:column;line-height:.7;">
                            <button type="button" @click="move(i, -1)" :disabled="i === 0" aria-label="Monter" style="background:none;border:none;cursor:pointer;color:var(--ukc-light);">▲</button>
                            <button type="button" @click="move(i, 1)" :disabled="i === custom.length - 1" aria-label="Descendre" style="background:none;border:none;cursor:pointer;color:var(--ukc-light);">▼</button>
                        </span>
                        <div class="meta">
                            <strong x-text="co.label || 'Champ personnalisé'"></strong>
                            <span x-text="customMeta(co)"></span>
                        </div>
                        <button type="button" class="ukc-btn--icon" @click="openEditCustom(i)" title="Éditer"><x-ukc.icon name="pen" :size="15" /></button>
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

        {{-- ============= Panneau d'événement d'un champ (comme « Nouvelle tâche ») ============= --}}
        <x-ukc.slideover open="editOpen" onClose="editOpen = false" width="440px">
            <x-slot:header><span x-text="creating ? 'Nouveau champ' : 'Modifier le champ'"></span></x-slot:header>

            <form @submit.prevent="saveEdit()" id="fieldEditForm">
                <div class="ukc-form-row">
                    <label>Libellé <span style="color:#dc2626;">*</span></label>
                    <input type="text" x-model="draft.label" maxlength="120" placeholder="Ex. Numéro ORCID">
                </div>

                {{-- Type (champs personnalisés uniquement) --}}
                <div class="ukc-form-row" x-show="editKind === 'custom'" x-cloak>
                    <label>Type</label>
                    <select x-model="draft.type">
                        @foreach ($customTypes as $val => $lbl)<option value="{{ $val }}">{{ $lbl }}</option>@endforeach
                    </select>
                </div>

                <div class="ukc-form-row">
                    <label>Aide (sous le champ)</label>
                    <input type="text" x-model="draft.help" maxlength="200">
                </div>

                <div class="ukc-form-row" x-show="editKind === 'standard' && draft.key === 'keywords'" x-cloak>
                    <label>Séparateur des mots-clés</label>
                    <select x-model="draft.separator">
                        @foreach (\App\Support\SubmissionKeywords::SEPARATORS as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                    <span class="ukc-hint">Utilisé pour les badges, les imports et les nouveaux dépôts de cet événement.</span>
                </div>

                {{-- Options (liste déroulante personnalisée) --}}
                <div class="ukc-form-row" x-show="editKind === 'custom' && draft.type === 'select'" x-cloak>
                    <label>Options (une par ligne)</label>
                    <textarea x-model="draft.options_text" rows="3" placeholder="Option A&#10;Option B"></textarea>
                </div>

                <div style="display:flex;gap:18px;flex-wrap:wrap;align-items:center;margin-top:2px;">
                    <label class="ukc-check" x-show="editKind === 'standard' && draft.canEnabled" x-cloak>
                        <input type="checkbox" x-model="draft.enabled"> Afficher ce champ
                    </label>
                    <label class="ukc-check" x-show="editKind === 'custom' || draft.canRequired" x-cloak>
                        <input type="checkbox" x-model="draft.required"> Obligatoire
                    </label>
                </div>

                <p class="ukc-hint" x-show="draft.isAbstract" x-cloak style="font-size:12px;color:var(--ukc-light);margin-top:8px;">
                    La limite de mots du résumé se règle dans l’onglet <strong>Options</strong>.
                </p>
            </form>

            <x-slot:footer>
                <button type="button" class="ukc-btn ukc-btn--ghost" x-show="editKind === 'custom' && !creating" x-cloak @click="deleteField()" style="color:#dc2626;">
                    <x-ukc.icon name="trash" :size="15" /> Supprimer
                </button>
                <span style="flex:1;"></span>
                <button type="button" class="ukc-btn ukc-btn--ghost" @click="editOpen = false">Annuler</button>
                <button type="submit" form="fieldEditForm" class="ukc-btn ukc-btn--blue" :disabled="saving">Enregistrer</button>
            </x-slot:footer>
        </x-ukc.slideover>
    </div>
</x-layouts.admin>
