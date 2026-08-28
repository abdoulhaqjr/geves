@php
    $seed = 'Bonjour, je suis Gesco, l’assistante des colloques de l’Université de Kara. '
        .'Posez-moi vos questions sur les soumissions, inscriptions, tarifs, programme ou documents.';
@endphp
<x-layouts.app title="Assistante Gesco">
    @include('partials.public-header')

    <section class="ukc-section ukc-section--tight">
        <div class="ukc-container" style="max-width:820px;">
            <div class="gesco-chat" x-data="gescoChat(@js($seed))">
                <div class="chat-hero">
                    <div class="av">G</div>
                    <div>
                        <h1>Assistante Gesco</h1>
                        <p>Soumissions, inscriptions, tarifs, programme, documents — je vous oriente. Je n’effectue aucune action à votre place.</p>
                    </div>
                </div>

                <div class="chat-shell">
                    <div class="chat-messages" x-ref="log">
                        <template x-for="(m, i) in messages" :key="i">
                            <div class="msg" :class="m.role === 'user' ? 'user' : 'bot'">
                                <div class="who" x-text="m.role === 'user' ? 'Vous' : 'Gesco'"></div>
                                <div style="white-space:pre-wrap;" x-text="m.text"></div>
                            </div>
                        </template>
                        <div class="msg bot typing" x-show="sending">Gesco écrit…</div>
                    </div>

                    <div class="suggestions" x-show="messages.length <= 1">
                        <button type="button" @click="ask('Comment soumettre une communication ?')">Comment soumettre ?</button>
                        <button type="button" @click="ask('Quels sont les tarifs et moyens de paiement ?')">Tarifs &amp; paiement</button>
                        <button type="button" @click="ask('Où trouver le programme du colloque ?')">Le programme</button>
                        <button type="button" @click="ask('Comment vérifier une attestation ?')">Vérifier une attestation</button>
                    </div>

                    <div class="chat-input">
                        <textarea x-model="input" rows="1" placeholder="Écrivez votre question…"
                                  @keydown.enter.prevent="if (!$event.shiftKey) { send(); }"
                                  :disabled="sending"></textarea>
                        <button type="button" class="send" @click="send()" aria-label="Envoyer" :disabled="sending">
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
</x-layouts.app>
