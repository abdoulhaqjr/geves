<div>
    @include('partials.public-header')

    <section class="ukc-section ukc-section--tight">
        <div class="ukc-container" style="max-width:820px;">
            <div class="gesco-chat">
                <div class="chat-hero">
                    <div class="av">G</div>
                    <div>
                        <h1>Assistante Gesco</h1>
                        <p>Soumissions, inscriptions, tarifs, programme, documents — je vous oriente. Je n’effectue aucune action à votre place.</p>
                    </div>
                </div>

                <div class="chat-shell">
                    <div class="chat-messages" id="chatLog">
                        @foreach ($messages as $m)
                            <div class="msg {{ $m['role'] === 'user' ? 'user' : 'bot' }}">
                                <div class="who">{{ $m['role'] === 'user' ? 'Vous' : 'Gesco' }}</div>
                                {!! nl2br(e($m['text'])) !!}
                            </div>
                        @endforeach
                        <div class="msg bot typing" wire:loading wire:target="send,ask">Gesco écrit…</div>
                    </div>

                    @if (count($messages) <= 1)
                        <div class="suggestions">
                            <button type="button" wire:click="ask('Comment soumettre une communication ?')">Comment soumettre ?</button>
                            <button type="button" wire:click="ask('Quels sont les tarifs et moyens de paiement ?')">Tarifs &amp; paiement</button>
                            <button type="button" wire:click="ask('Où trouver le programme du colloque ?')">Le programme</button>
                            <button type="button" wire:click="ask('Comment vérifier une attestation ?')">Vérifier une attestation</button>
                        </div>
                    @endif

                    <div class="chat-input" x-data>
                        <textarea wire:model="input" rows="1" placeholder="Écrivez votre question…"
                                  @keydown.enter.prevent="if (!$event.shiftKey) { $wire.send(); }"
                                  wire:loading.attr="disabled" wire:target="send,ask"></textarea>
                        <button type="button" class="send" wire:click="send" aria-label="Envoyer"
                                wire:loading.attr="disabled" wire:target="send,ask">
                            <svg viewBox="0 0 24 24" fill="currentColor"><path d="M2.01 21L23 12 2.01 3 2 10l15 2-15 2z"/></svg>
                        </button>
                    </div>
                </div>

                <p style="text-align:center;font-size:12px;color:var(--ukc-light);">
                    Réponses générées par IA à titre indicatif — pour toute décision officielle, contactez la DRI.
                </p>
            </div>
        </div>
    </section>

    @include('partials.public-footer')

    @script
    <script>
        const el = document.getElementById('chatLog');
        if (el) {
            const scroll = () => { el.scrollTop = el.scrollHeight; };
            scroll();
            new MutationObserver(scroll).observe(el, { childList: true, subtree: true });
        }
    </script>
    @endscript
</div>
