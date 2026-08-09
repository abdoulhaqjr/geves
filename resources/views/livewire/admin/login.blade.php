<div>
    <x-slot:heading>Back-office</x-slot:heading>
    <x-slot:subheading>Espace de gestion des colloques.</x-slot:subheading>

    <form wire:submit="authenticate" class="ukc-form-grid" style="gap:16px;">
        <div class="ukc-field full">
            <label for="email">Adresse e-mail</label>
            <input type="email" id="email" wire:model="email" autocomplete="email" autofocus>
            @error('email') <span class="error">{{ $message }}</span> @enderror
        </div>

        <div class="ukc-field full" x-data="{ show: false }">
            <label for="password">Mot de passe</label>
            <div style="position:relative;">
                <input :type="show ? 'text' : 'password'" id="password" wire:model="password"
                       autocomplete="current-password" style="width:100%;padding-right:44px;">
                <button type="button" @click="show = !show"
                        style="position:absolute;right:8px;top:50%;transform:translateY(-50%);background:none;border:none;cursor:pointer;color:var(--ukc-light);padding:6px;">
                    <span x-show="!show"><x-ukc.icon name="eye" :size="18" /></span>
                    <span x-show="show" x-cloak><x-ukc.icon name="eye-off" :size="18" /></span>
                </button>
            </div>
            @error('password') <span class="error">{{ $message }}</span> @enderror
        </div>

        <div class="ukc-field full">
            <label style="display:flex;align-items:center;gap:8px;font-weight:500;cursor:pointer;">
                <input type="checkbox" wire:model="remember" style="width:auto;padding:0;">
                Se souvenir de moi
            </label>
        </div>

        <div class="ukc-field full">
            <button type="submit" class="ukc-btn ukc-btn--primary" style="width:100%;" wire:loading.attr="disabled">
                <span wire:loading.remove wire:target="authenticate">Se connecter</span>
                <span wire:loading wire:target="authenticate">Connexion…</span>
            </button>
        </div>
    </form>
</div>
