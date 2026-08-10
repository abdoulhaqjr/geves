<div>
    @if ($sent)
        <div class="ukc-alert ukc-alert--ok" style="margin:0;">
            <span class="ico"><x-ukc.icon name="check-circle" :size="18" /></span>
            <div>Merci, votre message a bien été envoyé. Nous vous répondrons rapidement.</div>
        </div>
    @else
        <form wire:submit="submit" style="display:flex;flex-direction:column;gap:12px;">
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
                <div class="ukc-field" style="margin:0;">
                    <label for="c_name">Votre nom</label>
                    <input type="text" id="c_name" wire:model="name">
                    @error('name') <span class="error">{{ $message }}</span> @enderror
                </div>
                <div class="ukc-field" style="margin:0;">
                    <label for="c_email">Votre e-mail</label>
                    <input type="email" id="c_email" wire:model="email">
                    @error('email') <span class="error">{{ $message }}</span> @enderror
                </div>
            </div>
            <div class="ukc-field" style="margin:0;">
                <label for="c_message">Message</label>
                <textarea id="c_message" wire:model="message" rows="5"></textarea>
                @error('message') <span class="error">{{ $message }}</span> @enderror
            </div>

            {{-- Honeypot anti-spam : masqué, doit rester vide. --}}
            <div style="position:absolute;left:-9999px;" aria-hidden="true">
                <label>Ne pas remplir <input type="text" wire:model="website" tabindex="-1" autocomplete="off"></label>
            </div>

            <div>
                <button type="submit" class="ukc-btn ukc-btn--primary" wire:loading.attr="disabled" wire:target="submit">
                    <x-ukc.icon name="mail" :size="18" /> Envoyer le message
                </button>
            </div>
        </form>
    @endif
</div>
