<div>
    <div style="display:flex;gap:10px;flex-wrap:wrap;align-items:flex-end;margin-bottom:18px;">
        <div class="ukc-field" style="min-width:200px;">
            <label>Édition</label>
            <select wire:model.live="edition_id">
                <option value="">— Choisir —</option>
                @foreach ($editions as $ed)<option value="{{ $ed->id }}">{{ $ed->title ?: $ed->year }}</option>@endforeach
            </select>
        </div>
        <div class="ukc-field" style="min-width:150px;">
            <label>Type de document</label>
            <select wire:model.live="kind">
                <option value="attestation">Attestation</option>
                <option value="badge">Badge</option>
            </select>
        </div>
        @if ($kind === 'attestation')
            <div class="ukc-field" style="min-width:160px;">
                <label>Attestation</label>
                <select wire:model.live="type">
                    @foreach ($attestationTypes as $key => $label)<option value="{{ $key }}">{{ $label }}</option>@endforeach
                </select>
            </div>
        @endif
    </div>

    <div class="ukc-grid-2" style="align-items:start;grid-template-columns:1.1fr 1fr;">
        {{-- Aperçu --}}
        <div class="ukc-card">
            <h3><x-ukc.icon name="eye" :size="20" /> Aperçu</h3>
            <div style="overflow:auto;background:var(--ukc-bg);border:1px solid var(--ukc-border);border-radius:12px;padding:16px;">
                <div style="transform-origin:top left;">{!! $preview !!}</div>
            </div>
            <p style="font-size:.8rem;color:var(--ukc-muted);margin-top:8px;">Aperçu avec des données d’exemple. Le QR de vérification est réel au rendu final.</p>
        </div>

        {{-- Contrôles --}}
        <div style="display:flex;flex-direction:column;gap:18px;">
            @if ($aiAvailable)
                <div class="ukc-card" style="background:linear-gradient(135deg,rgba(34,62,164,.05),rgba(0,153,51,.05));">
                    <h3><x-ukc.icon name="sparkles" :size="20" /> Générer par IA</h3>
                    <div class="ukc-field">
                        <label for="prompt">Consigne de style</label>
                        <textarea id="prompt" wire:model="prompt" rows="3"
                                  placeholder="Ex. sobre et institutionnel, dominante bleu Université de Kara, titre centré, QR en bas à droite."></textarea>
                        @error('prompt') <span class="error">{{ $message }}</span> @enderror
                    </div>
                    <div class="ukc-field">
                        <label for="inspiration">Image d’inspiration (optionnel)</label>
                        <input type="file" id="inspiration" wire:model="inspiration" accept=".png,.jpg,.jpeg,.webp">
                        <div wire:loading wire:target="inspiration" class="hint">Téléversement…</div>
                    </div>
                    <button type="button" class="ukc-btn ukc-btn--primary ukc-btn--sm" wire:click="generate"
                            wire:loading.attr="disabled" wire:target="generate,inspiration">
                        <span wire:loading.remove wire:target="generate"><x-ukc.icon name="sparkles" :size="16" /> Générer la mise en page</span>
                        <span wire:loading wire:target="generate">Génération…</span>
                    </button>
                </div>
            @else
                <div class="ukc-alert ukc-alert--info">
                    <span class="ico"><x-ukc.icon name="info" :size="18" /></span>
                    <div>Configurez un fournisseur d’IA dans les <a href="{{ route('admin.settings') }}" wire:navigate style="text-decoration:underline;">Réglages</a> pour la génération assistée. Vous pouvez déjà éditer la mise en page manuellement.</div>
                </div>
            @endif

            <div class="ukc-card">
                <h3><x-ukc.icon name="palette" :size="20" /> Ajustements</h3>
                <div style="display:flex;gap:10px;align-items:flex-end;flex-wrap:wrap;">
                    <div class="ukc-field" style="max-width:160px;">
                        <label for="accent">Couleur d’accent</label>
                        <input type="color" id="accent" wire:model="accent" style="height:42px;padding:4px;">
                    </div>
                    <button type="button" class="ukc-btn ukc-btn--ghost ukc-btn--sm" wire:click="applyAccent">Appliquer</button>
                </div>

                <div class="ukc-field" style="margin-top:12px;">
                    <label for="layoutJson">Mise en page (JSON)</label>
                    <textarea id="layoutJson" wire:model="layoutJson" rows="10"
                              style="font-family:ui-monospace,monospace;font-size:12px;"></textarea>
                    @error('layoutJson') <span class="error">{{ $message }}</span> @enderror
                </div>
                <button type="button" class="ukc-btn ukc-btn--ghost ukc-btn--sm" wire:click="applyJson">Appliquer le JSON</button>
            </div>

            <div class="ukc-form-actions" style="border-top:none;padding-top:0;margin-top:0;">
                <button type="button" class="ukc-btn ukc-btn--primary" wire:click="save">
                    <x-ukc.icon name="check" :size="18" /> Enregistrer le modèle
                </button>
            </div>
        </div>
    </div>
</div>
