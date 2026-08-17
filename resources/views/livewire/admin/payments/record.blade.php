<div>
    <a href="{{ route('admin.payments.index') }}" wire:navigate
       style="display:inline-flex;align-items:center;gap:6px;font-size:.9rem;color:var(--ukc-muted);margin-bottom:14px;">
        <x-ukc.icon name="chevron" :size="16" style="transform:rotate(180deg);" /> Retour aux paiements
    </a>

    <form wire:submit="save" class="ukc-card" style="max-width:720px;">
        <h2><x-ukc.icon name="card" :size="20" /> Nouvel encaissement</h2>

        <div class="ukc-form-grid">
            <div class="ukc-field full">
                <label for="edition_id">Édition <span class="req">*</span></label>
                <select id="edition_id" wire:model.live="edition_id">
                    <option value="">— Choisir —</option>
                    @foreach ($editions as $ed)
                        <option value="{{ $ed->id }}">{{ $ed->title ?: $ed->year }}</option>
                    @endforeach
                </select>
                @error('edition_id') <span class="error">{{ $message }}</span> @enderror
            </div>

            <div class="ukc-field full">
                <label for="participant_id">Participant existant</label>
                <select id="participant_id" wire:model.live="participant_id">
                    <option value="">— Nouveau participant —</option>
                    @foreach ($participants as $part)
                        <option value="{{ $part->id }}">{{ $part->full_name }} ({{ $part->email }})</option>
                    @endforeach
                </select>
                <span class="hint">Sélectionner pré-remplit le montant selon le profil.</span>
            </div>

            @if ($participant_id === '')
                <div class="ukc-field">
                    <label for="first_name">Prénom</label>
                    <input type="text" id="first_name" wire:model="first_name">
                    @error('first_name') <span class="error">{{ $message }}</span> @enderror
                </div>
                <div class="ukc-field">
                    <label for="last_name">Nom</label>
                    <input type="text" id="last_name" wire:model="last_name">
                    @error('last_name') <span class="error">{{ $message }}</span> @enderror
                </div>
                <div class="ukc-field full">
                    <label for="email">E-mail</label>
                    <input type="email" id="email" wire:model="email">
                    @error('email') <span class="error">{{ $message }}</span> @enderror
                </div>
            @endif

            <div class="ukc-field">
                <label for="amount">Montant (FCFA) <span class="req">*</span></label>
                <input type="number" id="amount" wire:model="amount" min="0" step="500">
                @error('amount') <span class="error">{{ $message }}</span> @enderror
            </div>
            <div class="ukc-field">
                <label for="method">Moyen <span class="req">*</span></label>
                <select id="method" wire:model="method">
                    @foreach ($methods as $key => $label)
                        <option value="{{ $key }}">{{ $label }}</option>
                    @endforeach
                </select>
                @error('method') <span class="error">{{ $message }}</span> @enderror
            </div>

            <div class="ukc-field full">
                <label for="note">Note</label>
                <textarea id="note" wire:model="note" rows="2"></textarea>
                @error('note') <span class="error">{{ $message }}</span> @enderror
            </div>

            <div class="ukc-field full">
                <label style="display:flex;align-items:center;gap:8px;cursor:pointer;font-weight:600;">
                    <input type="checkbox" wire:model="markPaidNow" style="width:auto;padding:0;">
                    Marquer comme réglé immédiatement (génère le reçu + e-mail)
                </label>
            </div>
        </div>

        <div class="ukc-form-actions">
            <button type="submit" class="ukc-btn ukc-btn--blue" wire:loading.attr="disabled" wire:target="save">
                <span wire:loading.remove wire:target="save">Enregistrer</span>
                <span wire:loading wire:target="save">Enregistrement…</span>
            </button>
            <a href="{{ route('admin.payments.index') }}" wire:navigate class="ukc-btn ukc-btn--ghost">Annuler</a>
        </div>
    </form>
</div>
