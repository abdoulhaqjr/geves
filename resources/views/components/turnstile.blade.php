@props(['action'])

<div {{ $attributes }}>
    @if (\App\Support\Turnstile::configured())
        <div class="cf-turnstile"
             data-sitekey="{{ config('services.turnstile.site_key') }}"
             data-action="{{ $action }}"
             data-theme="light"></div>
        @pushOnce('scripts', 'cloudflare-turnstile')
            <script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>
        @endPushOnce
    @else
        <p role="alert" class="error">La vérification de sécurité est temporairement indisponible.</p>
    @endif
    @error('cf-turnstile-response') <span class="error" role="alert">{{ $message }}</span> @enderror
</div>
