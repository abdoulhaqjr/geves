<div>
    <x-slot:heading>Créer un compte</x-slot:heading>
    <x-slot:subheading>Un seul compte pour toutes les éditions du colloque.</x-slot:subheading>

    <form wire:submit="register" class="ukc-form-grid" style="gap:16px;">
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
            <input type="text" id="first_name" wire:model="first_name" autocomplete="given-name">
            @error('first_name') <span class="error">{{ $message }}</span> @enderror
        </div>

        <div class="ukc-field">
            <label for="last_name">Nom <span class="req">*</span></label>
            <input type="text" id="last_name" wire:model="last_name" autocomplete="family-name">
            @error('last_name') <span class="error">{{ $message }}</span> @enderror
        </div>

        <div class="ukc-field full">
            <label for="email">Adresse e-mail <span class="req">*</span></label>
            <input type="email" id="email" wire:model="email" autocomplete="email" placeholder="vous@exemple.tg">
            @error('email') <span class="error">{{ $message }}</span> @enderror
        </div>

        <div class="ukc-field full" x-data="{ show: false }">
            <label for="password">Mot de passe <span class="req">*</span></label>
            <div style="position:relative;">
                <input :type="show ? 'text' : 'password'" id="password" wire:model="password"
                       autocomplete="new-password" style="width:100%;padding-right:44px;">
                <button type="button" @click="show = !show"
                        style="position:absolute;right:8px;top:50%;transform:translateY(-50%);background:none;border:none;cursor:pointer;color:var(--ukc-light);padding:6px;">
                    <span x-show="!show"><x-ukc.icon name="eye" :size="18" /></span>
                    <span x-show="show" x-cloak><x-ukc.icon name="eye-off" :size="18" /></span>
                </button>
            </div>
            @error('password') <span class="error">{{ $message }}</span> @enderror
        </div>

        <div class="ukc-field full">
            <label for="password_confirmation">Confirmer le mot de passe <span class="req">*</span></label>
            <input type="password" id="password_confirmation" wire:model="password_confirmation"
                   autocomplete="new-password">
        </div>

        <div class="ukc-field full">
            <button type="submit" class="ukc-btn ukc-btn--blue" style="width:100%;" wire:loading.attr="disabled">
                <span wire:loading.remove wire:target="register">Créer mon compte</span>
                <span wire:loading wire:target="register">Création…</span>
            </button>
        </div>
    </form>

    <p style="text-align:center;font-size:.9rem;color:var(--ukc-muted);margin-top:18px;">
        Déjà inscrit ?
        <a href="{{ route('account.login') }}" wire:navigate style="color:var(--ukc-blue);font-weight:700;">Se connecter</a>
    </p>
</div>
