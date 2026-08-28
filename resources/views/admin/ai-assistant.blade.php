@php
    $seed = 'Bonjour, je suis le copilote d’administration. Je peux résumer l’état de l’édition, '
        .'rédiger des brouillons (e-mails, synthèses) et signaler des points de vigilance. Je propose — vous décidez.';
@endphp
<x-layouts.admin title="Assistant IA" subtitle="Copilote IA · propositions uniquement — décision humaine">
    <div class="admin-page">
        <div class="gesco-chat" style="max-width:860px;" x-data="gescoChat(@js($seed), '{{ route('admin.ai.ask') }}')">
            <div class="chat-hero">
                <div class="av" style="background:linear-gradient(145deg,#1a3080,#223ea4);"><x-ukc.icon name="sparkles" :size="24" /></div>
                <div>
                    <h1>Copilote d’administration</h1>
                    <p>Synthèses, brouillons et points de vigilance sur l’édition active. Il <strong>propose</strong> — vous décidez et exécutez.</p>
                </div>
            </div>

            <div class="chat-shell">
                <div class="chat-messages" x-ref="log">
                    <template x-for="(m, i) in messages" :key="i">
                        <div class="msg" :class="m.role === 'user' ? 'user' : 'bot'">
                            <div class="who" x-text="m.role === 'user' ? 'Vous' : 'Copilote'"></div>
                            <div style="white-space:pre-wrap;" x-text="m.text"></div>
                        </div>
                    </template>
                    <div class="msg bot typing" x-show="sending">Le copilote rédige…</div>
                </div>

                <div class="suggestions" x-show="messages.length <= 1">
                    <button type="button" @click="ask('Résume l’état de l’édition active en quelques points.')">Résumé de l’édition</button>
                    <button type="button" @click="ask('Rédige un brouillon d’e-mail de relance pour les paiements en attente.')">Brouillon de relance</button>
                    <button type="button" @click="ask('Quels sont les points de vigilance avant la clôture des soumissions ?')">Points de vigilance</button>
                </div>

                <div class="chat-input">
                    <textarea x-model="input" rows="1" placeholder="Demandez une synthèse, un brouillon…"
                              @keydown.enter.prevent="if (!$event.shiftKey) { send(); }" :disabled="sending"></textarea>
                    <button type="button" class="send" @click="send()" aria-label="Envoyer" :disabled="sending">
                        <svg viewBox="0 0 24 24" fill="currentColor"><path d="M2.01 21L23 12 2.01 3 2 10l15 2-15 2z"/></svg>
                    </button>
                </div>
            </div>

            <p style="text-align:center;font-size:12px;color:var(--ukc-light);">
                Suggestions générées par IA — aucune action n’est réalisée automatiquement (suppression, validation, envoi restent manuels).
            </p>
        </div>
    </div>
</x-layouts.admin>
