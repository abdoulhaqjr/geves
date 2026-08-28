<x-layouts.auth title="Réinitialiser le mot de passe">
    <x-slot:heading>Nouveau mot de passe</x-slot:heading>
    <x-slot:subheading>Choisissez un nouveau mot de passe pour votre compte.</x-slot:subheading>

    <form method="POST" action="{{ route('account.password.update') }}" class="ukc-form-grid" style="gap:16px;">
        @csrf
        <input type="hidden" name="token" value="{{ $token }}">

        <div class="ukc-field full">
            <label for="email">Adresse e-mail</label>
            <input type="email" id="email" name="email" value="{{ old('email', $email) }}" autocomplete="email">
            @error('email') <span class="error">{{ $message }}</span> @enderror
        </div>

        <div class="ukc-field full" x-data="{ show: false }">
            <label for="password">Nouveau mot de passe</label>
            <div style="position:relative;">
                <input :type="show ? 'text' : 'password'" id="password" name="password"
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
            <label for="password_confirmation">Confirmer le mot de passe</label>
            <input type="password" id="password_confirmation" name="password_confirmation" autocomplete="new-password">
        </div>

        <div class="ukc-field full">
            <button type="submit" class="ukc-btn ukc-btn--blue" style="width:100%;">Réinitialiser</button>
        </div>
    </form>
</x-layouts.auth>
