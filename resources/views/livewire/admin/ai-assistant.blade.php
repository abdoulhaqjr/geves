<div class="admin-page">
    <div class="gesco-chat" style="max-width:860px;">
        <div class="chat-hero">
            <div class="av" style="background:linear-gradient(145deg,#1a3080,#223ea4);"><x-ukc.icon name="sparkles" :size="24" /></div>
            <div>
                <h1>Copilote d’administration</h1>
                <p>Synthèses, brouillons et points de vigilance sur l’édition active. Il <strong>propose</strong> — vous décidez et exécutez.</p>
            </div>
        </div>

        <div class="chat-shell">
            <div class="chat-messages" id="adminChatLog">
                @foreach ($messages as $m)
                    <div class="msg {{ $m['role'] === 'user' ? 'user' : 'bot' }}">
                        <div class="who">{{ $m['role'] === 'user' ? 'Vous' : 'Copilote' }}</div>
                        {!! nl2br(e($m['text'])) !!}
                    </div>
                @endforeach
                <div class="msg bot typing" wire:loading wire:target="send,ask">Le copilote rédige…</div>
            </div>

            @if (count($messages) <= 1)
                <div class="suggestions">
                    <button type="button" wire:click="ask('Résume l’état de l’édition active en quelques points.')">Résumé de l’édition</button>
                    <button type="button" wire:click="ask('Rédige un brouillon d’e-mail de relance pour les paiements en attente.')">Brouillon de relance</button>
                    <button type="button" wire:click="ask('Quels sont les points de vigilance avant la clôture des soumissions ?')">Points de vigilance</button>
                </div>
            @endif

            <div class="chat-input" x-data>
                <textarea wire:model="input" rows="1" placeholder="Demandez une synthèse, un brouillon…"
                          @keydown.enter.prevent="if (!$event.shiftKey) { $wire.send(); }"
                          wire:loading.attr="disabled" wire:target="send,ask"></textarea>
                <button type="button" class="send" wire:click="send" aria-label="Envoyer" wire:loading.attr="disabled" wire:target="send,ask">
                    <svg viewBox="0 0 24 24" fill="currentColor"><path d="M2.01 21L23 12 2.01 3 2 10l15 2-15 2z"/></svg>
                </button>
            </div>
        </div>

        <p style="text-align:center;font-size:12px;color:var(--ukc-light);">
            Suggestions générées par IA — aucune action n’est réalisée automatiquement (suppression, validation, envoi restent manuels).
        </p>
    </div>

    @script
    <script>
        const el = document.getElementById('adminChatLog');
        if (el) {
            const scroll = () => { el.scrollTop = el.scrollHeight; };
            scroll();
            new MutationObserver(scroll).observe(el, { childList: true, subtree: true });
        }
    </script>
    @endscript
</div>
