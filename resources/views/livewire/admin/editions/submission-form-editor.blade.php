<div style="max-width:820px;">
    <div style="display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap;margin-bottom:16px;">
        <a href="{{ route('admin.editions.index') }}" wire:navigate class="ukc-btn ukc-btn--ghost ukc-btn--sm">← Éditions</a>
        <a href="{{ route('account.submissions.create', $edition->slug) }}" target="_blank" class="ukc-btn ukc-btn--outline ukc-btn--sm">
            <x-ukc.icon name="external" :size="16" /> Aperçu public
        </a>
    </div>

    <form wire:submit="save">
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
                            <input type="text" wire:model="fields.{{ $key }}.label">
                            @error("fields.{$key}.label") <span class="error">{{ $message }}</span> @enderror
                        </div>
                        <div class="ukc-field" style="margin:0;">
                            <label>Aide (sous le champ)</label>
                            <input type="text" wire:model="fields.{{ $key }}.help">
                        </div>
                    </div>

                    <div style="display:flex;gap:18px;flex-wrap:wrap;margin-top:10px;align-items:center;">
                        @if (in_array($key, ['type', 'keywords', 'file']))
                            <label style="display:inline-flex;align-items:center;gap:6px;font-size:.9rem;">
                                <input type="checkbox" wire:model="fields.{{ $key }}.enabled"> Afficher ce champ
                            </label>
                        @endif
                        @if (! in_array($key, ['title', 'abstract']))
                            <label style="display:inline-flex;align-items:center;gap:6px;font-size:.9rem;">
                                <input type="checkbox" wire:model="fields.{{ $key }}.required"> Obligatoire
                            </label>
                        @endif
                        @if ($key === 'abstract')
                            <div class="ukc-field" style="margin:0;max-width:130px;">
                                <label>Min. caractères</label>
                                <input type="number" wire:model="fields.abstract.min" min="0">
                            </div>
                            <div class="ukc-field" style="margin:0;max-width:130px;">
                                <label>Max. caractères</label>
                                <input type="number" wire:model="fields.abstract.max" min="1">
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
                <button type="button" class="ukc-btn ukc-btn--ghost ukc-btn--sm" wire:click="addCustom">
                    <x-ukc.icon name="plus" :size="16" /> Ajouter un champ
                </button>
            </div>
            <p class="ukc-muted" style="font-size:.85rem;margin:0 0 12px;">
                Champs supplémentaires demandés au dépôt (stockés avec la communication).
            </p>

            @forelse ($custom as $i => $c)
                <div wire:key="custom-{{ $i }}" style="padding:14px 0;border-top:1px solid var(--ukc-border);">
                    <div style="display:grid;grid-template-columns:1.4fr 1fr auto;gap:12px;align-items:end;">
                        <div class="ukc-field" style="margin:0;">
                            <label>Libellé</label>
                            <input type="text" wire:model="custom.{{ $i }}.label" placeholder="Ex. Numéro ORCID">
                        </div>
                        <div class="ukc-field" style="margin:0;">
                            <label>Type</label>
                            <select wire:model.live="custom.{{ $i }}.type">
                                @foreach ($customTypes as $val => $lbl)
                                    <option value="{{ $val }}">{{ $lbl }}</option>
                                @endforeach
                            </select>
                        </div>
                        <button type="button" class="ukc-btn ukc-btn--danger ukc-btn--sm" wire:click="removeCustom({{ $i }})">
                            <x-ukc.icon name="close" :size="16" />
                        </button>
                    </div>

                    <div style="display:grid;grid-template-columns:1fr auto;gap:12px;margin-top:10px;align-items:end;">
                        <div class="ukc-field" style="margin:0;">
                            <label>Aide</label>
                            <input type="text" wire:model="custom.{{ $i }}.help">
                        </div>
                        <label style="display:inline-flex;align-items:center;gap:6px;font-size:.9rem;padding-bottom:10px;">
                            <input type="checkbox" wire:model="custom.{{ $i }}.required"> Obligatoire
                        </label>
                    </div>

                    @if (($c['type'] ?? 'text') === 'select')
                        <div class="ukc-field" style="margin:10px 0 0;">
                            <label>Options (une par ligne)</label>
                            <textarea wire:model="custom.{{ $i }}.options_text" rows="3" placeholder="Option A&#10;Option B"></textarea>
                        </div>
                    @endif
                </div>
            @empty
                <div class="ukc-empty" style="padding:20px;"><p style="margin:0;">Aucun champ personnalisé.</p></div>
            @endforelse
        </div>

        <div style="display:flex;justify-content:flex-end;">
            <button type="submit" class="ukc-btn ukc-btn--primary">
                <x-ukc.icon name="check" :size="18" /> Enregistrer le formulaire
            </button>
        </div>
    </form>
</div>
