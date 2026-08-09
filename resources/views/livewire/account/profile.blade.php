<div>
    @if (session('ukc_status'))
        <div class="ukc-alert ukc-alert--ok">
            <span class="ico"><x-ukc.icon name="check-circle" :size="18" /></span>
            <div>{{ session('ukc_status') }}</div>
        </div>
    @endif

    <div class="ukc-card">
        <h2><x-ukc.icon name="user" :size="20" /> Mon profil</h2>

        <form wire:submit="save" class="ukc-form-grid">
            <div class="ukc-field">
                <label for="civility">Civilité</label>
                <select id="civility" wire:model="civility">
                    <option value="">—</option>
                    @foreach ($civilities as $c)
                        <option value="{{ $c }}">{{ $c }}</option>
                    @endforeach
                </select>
                @error('civility') <span class="error">{{ $message }}</span> @enderror
            </div>

            <div class="ukc-field">
                <label for="grade">Grade / titre</label>
                <input type="text" id="grade" wire:model="grade" placeholder="Doctorant, Maître-assistant…">
                @error('grade') <span class="error">{{ $message }}</span> @enderror
            </div>

            <div class="ukc-field">
                <label for="first_name">Prénom <span class="req">*</span></label>
                <input type="text" id="first_name" wire:model="first_name">
                @error('first_name') <span class="error">{{ $message }}</span> @enderror
            </div>

            <div class="ukc-field">
                <label for="last_name">Nom <span class="req">*</span></label>
                <input type="text" id="last_name" wire:model="last_name">
                @error('last_name') <span class="error">{{ $message }}</span> @enderror
            </div>

            <div class="ukc-field">
                <label for="phone">Téléphone</label>
                <input type="text" id="phone" wire:model="phone" placeholder="+228…">
                @error('phone') <span class="error">{{ $message }}</span> @enderror
            </div>

            <div class="ukc-field">
                <label for="country">Pays</label>
                <input type="text" id="country" wire:model="country" placeholder="Togo">
                @error('country') <span class="error">{{ $message }}</span> @enderror
            </div>

            <div class="ukc-field full">
                <label for="affiliation">Affiliation <span class="req">*</span> <span class="hint">(requise pour soumettre)</span></label>
                <input type="text" id="affiliation" wire:model="affiliation"
                       placeholder="Laboratoire / Département — Université de Kara">
                @error('affiliation') <span class="error">{{ $message }}</span> @enderror
            </div>

            <div class="ukc-field full">
                <label for="bio">Bio</label>
                <textarea id="bio" wire:model="bio" rows="4" placeholder="Quelques lignes sur vous (optionnel)."></textarea>
                @error('bio') <span class="error">{{ $message }}</span> @enderror
            </div>

            <div class="ukc-form-actions full">
                <button type="submit" class="ukc-btn ukc-btn--primary" wire:loading.attr="disabled">
                    <span wire:loading.remove wire:target="save">Enregistrer</span>
                    <span wire:loading wire:target="save">Enregistrement…</span>
                </button>
            </div>
        </form>
    </div>
</div>
