<div>
    <form wire:submit="save">
        {{-- Rattachement --}}
        <div class="ukc-card" style="margin-bottom:18px;">
            <h2><x-ukc.icon name="folder" :size="20" /> Colloque</h2>
            <div class="ukc-form-grid">
                <div class="ukc-field">
                    <label for="colloque_id">Colloque existant</label>
                    <select id="colloque_id" wire:model="colloque_id">
                        <option value="">— Choisir —</option>
                        @foreach ($colloques as $c)
                            <option value="{{ $c->id }}">{{ $c->name }}</option>
                        @endforeach
                    </select>
                    @error('colloque_id') <span class="error">{{ $message }}</span> @enderror
                </div>
                <div class="ukc-field">
                    <label for="new_colloque">…ou nouveau colloque</label>
                    <input type="text" id="new_colloque" wire:model="new_colloque" placeholder="Nom du nouveau colloque">
                    <span class="hint">Renseignez ceci uniquement pour créer une nouvelle série.</span>
                </div>
            </div>
        </div>

        {{-- Informations générales --}}
        <div class="ukc-card" style="margin-bottom:18px;">
            <h2><x-ukc.icon name="info" :size="20" /> Informations générales</h2>
            <div class="ukc-form-grid">
                <div class="ukc-field full">
                    <label for="title">Titre de l’édition <span class="req">*</span></label>
                    <input type="text" id="title" wire:model="title" placeholder="Colloque international de l’Université de Kara — 2026">
                    @error('title') <span class="error">{{ $message }}</span> @enderror
                </div>

                <div class="ukc-field">
                    <label for="edition_label">Libellé d’édition</label>
                    <input type="text" id="edition_label" wire:model="edition_label" placeholder="3e édition">
                    @error('edition_label') <span class="error">{{ $message }}</span> @enderror
                </div>
                <div class="ukc-field">
                    <label for="year">Année</label>
                    <input type="number" id="year" wire:model="year" min="2000" max="2100">
                    @error('year') <span class="error">{{ $message }}</span> @enderror
                </div>

                <div class="ukc-field full">
                    <label for="subtitle">Sous-titre</label>
                    <input type="text" id="subtitle" wire:model="subtitle">
                    @error('subtitle') <span class="error">{{ $message }}</span> @enderror
                </div>

                <div class="ukc-field full">
                    <label for="theme">Thème</label>
                    <textarea id="theme" wire:model="theme" rows="2"></textarea>
                    @error('theme') <span class="error">{{ $message }}</span> @enderror
                </div>

                <div class="ukc-field">
                    <label for="date_start">Date de début</label>
                    <input type="date" id="date_start" wire:model="date_start">
                    @error('date_start') <span class="error">{{ $message }}</span> @enderror
                </div>
                <div class="ukc-field">
                    <label for="date_end">Date de fin</label>
                    <input type="date" id="date_end" wire:model="date_end">
                    @error('date_end') <span class="error">{{ $message }}</span> @enderror
                </div>

                <div class="ukc-field full">
                    <label for="location">Lieu</label>
                    <input type="text" id="location" wire:model="location" placeholder="Campus de l’Université de Kara, Togo">
                    @error('location') <span class="error">{{ $message }}</span> @enderror
                </div>
            </div>
        </div>

        {{-- Publication & soumissions --}}
        <div class="ukc-card" style="margin-bottom:18px;">
            <h2><x-ukc.icon name="external" :size="20" /> Publication & soumissions</h2>
            <div class="ukc-form-grid">
                <div class="ukc-field">
                    <label for="status">Statut</label>
                    <select id="status" wire:model="status">
                        <option value="draft">Brouillon</option>
                        <option value="open">Ouverte</option>
                        <option value="closed">Clôturée</option>
                        <option value="archived">Archivée</option>
                    </select>
                    <span class="hint">La page publique n’est visible qu’à partir de « Ouverte ».</span>
                    @error('status') <span class="error">{{ $message }}</span> @enderror
                </div>
                <div class="ukc-field">
                    <label for="slug">Slug de la page (URL)</label>
                    <input type="text" id="slug" wire:model="slug" placeholder="auto depuis le titre">
                    <span class="hint">Laissez vide pour générer automatiquement.</span>
                    @error('slug') <span class="error">{{ $message }}</span> @enderror
                </div>

                <div class="ukc-field">
                    <label style="display:flex;align-items:center;gap:8px;cursor:pointer;font-weight:600;">
                        <input type="checkbox" wire:model="submission_open" style="width:auto;padding:0;">
                        Soumissions ouvertes
                    </label>
                </div>
                <div class="ukc-field">
                    <label for="submission_deadline">Date limite de soumission</label>
                    <input type="datetime-local" id="submission_deadline" wire:model="submission_deadline">
                    @error('submission_deadline') <span class="error">{{ $message }}</span> @enderror
                </div>

                <div class="ukc-field">
                    <label for="max_communications">Communications max. par personne</label>
                    <input type="number" id="max_communications" wire:model="max_communications" min="1" max="10">
                    @error('max_communications') <span class="error">{{ $message }}</span> @enderror
                </div>
            </div>
        </div>

        {{-- Contenu public --}}
        <div class="ukc-card" style="margin-bottom:18px;">
            <h2><x-ukc.icon name="book" :size="20" /> Contenu public</h2>
            <div class="ukc-form-grid">
                <div class="ukc-field full">
                    <label for="intro">Argumentaire / présentation</label>
                    <textarea id="intro" wire:model="intro" rows="4"></textarea>
                    @error('intro') <span class="error">{{ $message }}</span> @enderror
                </div>
                <div class="ukc-field full">
                    <label for="tracks">Axes thématiques <span class="hint">(un par ligne)</span></label>
                    <textarea id="tracks" wire:model="tracks" rows="5"
                              placeholder="Sciences et technologies&#10;Sciences de la santé&#10;…"></textarea>
                    @error('tracks') <span class="error">{{ $message }}</span> @enderror
                </div>
            </div>

            {{-- Frais --}}
            <div style="margin-top:8px;">
                <label style="font-size:13px;font-weight:600;">Frais d’inscription (FCFA)</label>
                <div style="display:flex;flex-direction:column;gap:8px;margin-top:8px;">
                    @foreach ($fees as $i => $fee)
                        <div wire:key="fee-{{ $i }}" style="display:flex;gap:8px;align-items:center;">
                            <input type="text" wire:model="fees.{{ $i }}.label" placeholder="Profil (ex. Doctorant)"
                                   style="flex:2;border:1.5px solid var(--ukc-border);border-radius:10px;padding:10px 14px;font-size:14px;">
                            <input type="number" wire:model="fees.{{ $i }}.amount" placeholder="Montant" min="0"
                                   style="flex:1;border:1.5px solid var(--ukc-border);border-radius:10px;padding:10px 14px;font-size:14px;">
                            <button type="button" class="ukc-btn ukc-btn--danger ukc-btn--sm" wire:click="removeFee({{ $i }})">
                                <x-ukc.icon name="close" :size="16" />
                            </button>
                        </div>
                    @endforeach
                </div>
                <button type="button" class="ukc-btn ukc-btn--ghost ukc-btn--sm" wire:click="addFee" style="margin-top:10px;">
                    <x-ukc.icon name="plus" :size="16" /> Ajouter un tarif
                </button>
            </div>
        </div>

        <div class="ukc-form-actions" style="margin-top:0;border-top:none;padding-top:0;">
            <button type="submit" class="ukc-btn ukc-btn--primary" wire:loading.attr="disabled">
                <span wire:loading.remove wire:target="save">Enregistrer</span>
                <span wire:loading wire:target="save">Enregistrement…</span>
            </button>
            <a href="{{ route('admin.editions.index') }}" wire:navigate class="ukc-btn ukc-btn--ghost">Annuler</a>
        </div>
    </form>
</div>
