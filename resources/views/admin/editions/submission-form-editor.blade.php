<x-layouts.admin :title="'Formulaire — '.($edition->title ?: $edition->year)" subtitle="Champs, règles et types de dépôt">
    <div class="admin-page admin-submission-forms" style="max-width:820px;">
        <div style="display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap;margin-bottom:16px;">
            <a href="{{ route('admin.editions.index') }}" class="ukc-btn ukc-btn--ghost ukc-btn--sm">← Éditions</a>
            <a href="{{ route('account.submissions.create', $edition->slug) }}" target="_blank" class="ukc-btn ukc-btn--outline ukc-btn--sm">
                <x-ukc.icon name="external" :size="16" /> Aperçu public
            </a>
        </div>

        <form method="POST" action="{{ route('admin.editions.form.update', $edition) }}" x-data="formEditor(@js($custom))">
            @csrf
            @method('PUT')
            {{-- Champs standard --}}
            <div class="ukc-card" style="margin-bottom:18px;">
                <h2 style="font-size:1.05rem;margin:0 0 4px;">Champs standard</h2>
                <p class="ukc-muted" style="font-size:.85rem;margin:0 0 16px;">
                    Personnalisez les libellés et aides. L’identité de l’auteur (nom, e-mail, affiliation, grade) provient du compte et n’est pas éditée ici.
                </p>

                @foreach ($fields as $key => $conf)
                    <div style="padding:14px 0;border-top:1px solid var(--ukc-border);">
                        <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
                            <div class="ukc-field" style="margin:0;">
                                <label>Libellé — <code>{{ $key }}</code></label>
                                <input type="text" name="fields[{{ $key }}][label]" value="{{ old('fields.'.$key.'.label', $conf['label']) }}">
                                @error("fields.{$key}.label") <span class="error">{{ $message }}</span> @enderror
                            </div>
                            <div class="ukc-field" style="margin:0;">
                                <label>Aide (sous le champ)</label>
                                <input type="text" name="fields[{{ $key }}][help]" value="{{ old('fields.'.$key.'.help', $conf['help']) }}">
                            </div>
                        </div>

                        <div style="display:flex;gap:18px;flex-wrap:wrap;margin-top:10px;align-items:center;">
                            @if (in_array($key, ['type', 'keywords', 'file']))
                                <label style="display:inline-flex;align-items:center;gap:6px;font-size:.9rem;">
                                    <input type="checkbox" name="fields[{{ $key }}][enabled]" value="1" @checked($conf['enabled'])> Afficher ce champ
                                </label>
                            @endif
                            @if (! in_array($key, ['title', 'abstract']))
                                <label style="display:inline-flex;align-items:center;gap:6px;font-size:.9rem;">
                                    <input type="checkbox" name="fields[{{ $key }}][required]" value="1" @checked($conf['required'])> Obligatoire
                                </label>
                            @endif
                            @if ($key === 'abstract')
                                <div class="ukc-field" style="margin:0;max-width:130px;">
                                    <label>Min. caractères</label>
                                    <input type="number" name="fields[abstract][min]" value="{{ old('fields.abstract.min', $conf['min']) }}" min="0">
                                </div>
                                <div class="ukc-field" style="margin:0;max-width:130px;">
                                    <label>Max. caractères</label>
                                    <input type="number" name="fields[abstract][max]" value="{{ old('fields.abstract.max', $conf['max']) }}" min="1">
                                </div>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>

            {{-- Champs personnalisés --}}
            <div class="ukc-card" style="margin-bottom:18px;">
                <div style="display:flex;align-items:center;justify-content:space-between;gap:10px;margin-bottom:6px;">
                    <h2 style="font-size:1.05rem;margin:0;">Champs personnalisés</h2>
                    <button type="button" class="ukc-btn ukc-btn--ghost ukc-btn--sm" @click="add()">
                        <x-ukc.icon name="plus" :size="16" /> Ajouter un champ
                    </button>
                </div>
                <p class="ukc-muted" style="font-size:.85rem;margin:0 0 12px;">
                    Champs supplémentaires demandés au dépôt (stockés avec la communication).
                    L’<strong>ordre ci-dessous = l’ordre d’affichage</strong> dans le formulaire public.
                </p>

                <template x-for="(co, i) in custom" :key="i">
                    <div style="padding:14px 0;border-top:1px solid var(--ukc-border);">
                        <input type="hidden" :name="'custom['+i+'][key]'" :value="co.key">
                        <div style="display:grid;grid-template-columns:auto 1.4fr 1fr auto;gap:12px;align-items:end;">
                            <div style="display:flex;flex-direction:column;align-items:center;gap:2px;padding-bottom:4px;">
                                <button type="button" class="ukc-btn ukc-btn--ghost ukc-btn--sm" style="padding:2px 6px;line-height:1;"
                                        @click="move(i, -1)" :disabled="i === 0" aria-label="Monter">
                                    <x-ukc.icon name="chevron" :size="14" style="transform:rotate(-90deg);" />
                                </button>
                                <span style="font-size:.75rem;font-weight:700;color:var(--ukc-muted);" x-text="i + 1"></span>
                                <button type="button" class="ukc-btn ukc-btn--ghost ukc-btn--sm" style="padding:2px 6px;line-height:1;"
                                        @click="move(i, 1)" :disabled="i === custom.length - 1" aria-label="Descendre">
                                    <x-ukc.icon name="chevron" :size="14" style="transform:rotate(90deg);" />
                                </button>
                            </div>
                            <div class="ukc-field" style="margin:0;">
                                <label>Libellé</label>
                                <input type="text" :name="'custom['+i+'][label]'" x-model="co.label" placeholder="Ex. Numéro ORCID">
                            </div>
                            <div class="ukc-field" style="margin:0;">
                                <label>Type</label>
                                <select :name="'custom['+i+'][type]'" x-model="co.type">
                                    @foreach ($customTypes as $val => $lbl)
                                        <option value="{{ $val }}">{{ $lbl }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <button type="button" class="ukc-btn ukc-btn--danger ukc-btn--sm" @click="remove(i)">
                                <x-ukc.icon name="close" :size="16" />
                            </button>
                        </div>

                        <div style="display:grid;grid-template-columns:1fr auto;gap:12px;margin-top:10px;align-items:end;">
                            <div class="ukc-field" style="margin:0;">
                                <label>Aide</label>
                                <input type="text" :name="'custom['+i+'][help]'" x-model="co.help">
                            </div>
                            <label style="display:inline-flex;align-items:center;gap:6px;font-size:.9rem;padding-bottom:10px;">
                                <input type="checkbox" :name="'custom['+i+'][required]'" value="1" x-model="co.required"> Obligatoire
                            </label>
                        </div>

                        <div class="ukc-field" style="margin:10px 0 0;" x-show="co.type === 'select'">
                            <label>Options (une par ligne)</label>
                            <textarea :name="'custom['+i+'][options_text]'" x-model="co.options_text" rows="3" placeholder="Option A&#10;Option B"></textarea>
                        </div>
                    </div>
                </template>
                <div class="ukc-empty" style="padding:20px;" x-show="custom.length === 0"><p style="margin:0;">Aucun champ personnalisé.</p></div>
            </div>

            <div style="display:flex;justify-content:flex-end;">
                <button type="submit" class="ukc-btn ukc-btn--blue">
                    <x-ukc.icon name="check" :size="18" /> Enregistrer le formulaire
                </button>
            </div>
        </form>
    </div>
</x-layouts.admin>
