@php
    $seed = 'Bonjour. Je suis '.$agentName.', votre assistant d’administration pour UK Colloques. '
        ."Je peux analyser les soumissions, repérer les anomalies de paiement, préparer des e-mails, "
        ."suggérer des créneaux ou des rapports.\n\n"
        .'Dites-moi ce que vous souhaitez examiner — je proposerai, vous déciderez.';
@endphp
<x-layouts.admin title="Administration assistée" subtitle="Copilote IA · propositions uniquement — vous validez chaque action">
    <x-slot:actions>
        <span class="ukc-pill ukc-pill--info">Agent : {{ $agentName }}</span>
        <a href="{{ route('admin.settings.agents') }}" class="ukc-btn ukc-btn--outline ukc-btn--sm">Configurer l’agent</a>
    </x-slot:actions>

    <div class="admin-page admin-ai"
         x-data="adminCopilot(@js($seed), '{{ route('admin.ai.ask') }}', '{{ route('admin.ai.decide') }}', @js($decisionLog), @js($agentName), @js((string) $currentEditionId))">
        @if ($editions->isNotEmpty())
            <div style="display:flex;flex-wrap:wrap;margin-bottom:16px;">
                <div class="ukc-field" style="min-width:220px;margin:0;">
                    <select x-model="editionId">
                        @foreach ($editions as $ed)
                            <option value="{{ $ed->id }}">{{ $ed->colloque?->name ? $ed->colloque->name.' — ' : '' }}{{ $ed->title ?: $ed->year }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        @endif
        <div class="layout">

            {{-- Avatar --}}
            <div class="ukc-card avatar-panel">
                <div class="avatar-stage" :class="{ speaking: sending }" aria-hidden="true">
                    @if ($agentAvatar)
                    <img src="{{ $agentAvatar }}" alt="Avatar de {{ $agentName }}" style="width:100%;height:100%;object-fit:cover;">
                    @else
                    <svg viewBox="0 0 200 200" xmlns="http://www.w3.org/2000/svg">
                        <defs>
                            <linearGradient id="aibg" x1="0" y1="0" x2="1" y2="1"><stop offset="0%" stop-color="#dbe4f8"/><stop offset="100%" stop-color="#c5d3f0"/></linearGradient>
                            <linearGradient id="aiskin" x1="0" y1="0" x2="0" y2="1"><stop offset="0%" stop-color="#e8b892"/><stop offset="100%" stop-color="#c98a5c"/></linearGradient>
                            <linearGradient id="aisuit" x1="0" y1="0" x2="0" y2="1"><stop offset="0%" stop-color="#2a3a5c"/><stop offset="100%" stop-color="#1a2744"/></linearGradient>
                        </defs>
                        <circle cx="100" cy="100" r="100" fill="url(#aibg)"/>
                        <ellipse cx="100" cy="195" rx="72" ry="48" fill="url(#aisuit)"/>
                        <path d="M55 170 Q100 150 145 170 L150 200 L50 200 Z" fill="url(#aisuit)"/>
                        <path d="M88 168 L100 188 L112 168 Z" fill="#f5f7fb"/>
                        <path d="M95 175 L100 195 L105 175" stroke="#c5cdd8" stroke-width="1" fill="none"/>
                        <rect x="88" y="118" width="24" height="28" rx="8" fill="url(#aiskin)"/>
                        <ellipse cx="100" cy="88" rx="42" ry="48" fill="url(#aiskin)"/>
                        <path d="M58 85 Q55 45 100 38 Q145 45 142 85 Q130 55 100 52 Q70 55 58 85 Z" fill="#2c1810"/>
                        <path d="M58 78 Q62 95 70 100 Q65 85 58 78 Z" fill="#2c1810"/>
                        <path d="M142 78 Q138 95 130 100 Q135 85 142 78 Z" fill="#2c1810"/>
                        <ellipse cx="58" cy="90" rx="7" ry="11" fill="#c98a5c"/>
                        <ellipse cx="142" cy="90" rx="7" ry="11" fill="#c98a5c"/>
                        <path d="M72 78 Q82 74 90 78" stroke="#3d2914" stroke-width="2.2" fill="none" stroke-linecap="round"/>
                        <path d="M110 78 Q118 74 128 78" stroke="#3d2914" stroke-width="2.2" fill="none" stroke-linecap="round"/>
                        <ellipse cx="81" cy="88" rx="6" ry="5.5" fill="#fff"/>
                        <ellipse cx="119" cy="88" rx="6" ry="5.5" fill="#fff"/>
                        <circle cx="82" cy="89" r="3.2" fill="#3d2914"/>
                        <circle cx="120" cy="89" r="3.2" fill="#3d2914"/>
                        <circle cx="83" cy="87.5" r="1.1" fill="#fff"/>
                        <circle cx="121" cy="87.5" r="1.1" fill="#fff"/>
                        <path d="M100 90 L96 105 Q100 108 104 105 Z" fill="#b87a52"/>
                        <path d="M88 115 Q100 124 112 115" stroke="#a05d45" stroke-width="2.2" fill="none" stroke-linecap="round"/>
                        <circle cx="81" cy="88" r="11" fill="none" stroke="#4a5568" stroke-width="1.5" opacity=".55"/>
                        <circle cx="119" cy="88" r="11" fill="none" stroke="#4a5568" stroke-width="1.5" opacity=".55"/>
                        <line x1="92" y1="88" x2="108" y2="88" stroke="#4a5568" stroke-width="1.5" opacity=".55"/>
                    </svg>
                    @endif
                </div>
                <div class="avatar-name">{{ $agentName }}</div>
                <div class="avatar-role">Copilote d’administration</div>
                <div class="avatar-status" :class="{ busy: sending }">
                    <span class="dot"></span>
                    <span x-text="sending ? 'Analyse en cours…' : 'En ligne — à votre écoute'"></span>
                </div>
                <div class="disclaimer">
                    <strong>Principe de contrôle humain</strong><br>
                    {{ $agentName }} propose des actions et des analyses. Aucune modification (validation de paiement,
                    e-mail massif, publication, suppression…) n’est exécutée sans votre confirmation explicite.
                </div>
            </div>

            {{-- Chat --}}
            <div class="ukc-card chat-shell">
                <div class="chat-messages" x-ref="log">
                    <template x-for="(m, i) in messages" :key="i">
                        <div class="msg" :class="m.role === 'user' ? 'user' : 'bot'">
                            <div class="who" x-text="m.role === 'user' ? 'Vous' : (agentName + ' · copilote')"></div>
                            <template x-if="m.role === 'user'"><div style="white-space:pre-wrap;" x-text="m.text"></div></template>
                            <template x-if="m.role !== 'user'"><div class="chat-md" x-html="render(m.text)"></div></template>
                            <div class="ukc-chat-act">
                                <button type="button" @click="copy(i, m.text)" title="Copier">
                                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="9" y="9" width="12" height="12" rx="2"/><path d="M5 15V5a2 2 0 0 1 2-2h10"/></svg>
                                    <span x-text="copiedIndex === i ? 'Copié' : 'Copier'"></span>
                                </button>
                                <template x-if="m.role === 'user'">
                                    <button type="button" @click="editUser(i)" title="Modifier et relancer">
                                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 20h9"/><path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4Z"/></svg>
                                        <span>Modifier</span>
                                    </button>
                                </template>
                            </div>

                            {{-- Proposition d'action à décider --}}
                            <template x-if="m.proposal">
                                <div class="proposal">
                                    <strong x-text="m.proposal.title"></strong>
                                    <p x-show="m.proposal.detail" x-text="m.proposal.detail"></p>
                                    <div class="impact" x-show="m.proposal.impact" x-text="'Impact : ' + m.proposal.impact"></div>
                                    <div class="actions">
                                        <template x-if="!m.decision">
                                            <div style="display:flex;flex-wrap:wrap;gap:6px;">
                                                <button type="button" class="ukc-btn ukc-btn--sm ukc-btn--green" @click="decide(i, 'approve')">Approuver</button>
                                                <button type="button" class="ukc-btn ukc-btn--sm ukc-btn--outline" @click="decide(i, 'edit')">Modifier avant</button>
                                                <button type="button" class="ukc-btn ukc-btn--sm" style="background:#fee2e2;color:#991b1b;" @click="decide(i, 'reject')">Refuser</button>
                                            </div>
                                        </template>
                                        <template x-if="m.decision">
                                            <span class="ukc-pill" :class="'ukc-pill--' + decisionPill(m.decision)" x-text="decisionLabel(m.decision)"></span>
                                        </template>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </template>
                    <div class="msg bot typing" x-show="sending">
                        <span class="typing-dots"><span></span><span></span><span></span></span>
                    </div>
                </div>

                <div class="chat-suggestions" x-show="messages.length <= 1">
                    <button type="button" @click="ask('Quelles soumissions attendent une décision depuis plus de 7 jours ?')">Soumissions en retard</button>
                    <button type="button" @click="ask('Y a-t-il des paiements en attente ou suspects ?')">Paiements à vérifier</button>
                    <button type="button" @click="ask('Prépare un brouillon d’e-mail de rappel aux auteurs non inscrits')">Brouillon e-mail</button>
                    <button type="button" @click="ask('Résume l’état du colloque pour le comité')">Synthèse comité</button>
                    <button type="button" @click="ask('Propose une répartition des créneaux libres')">Créneaux libres</button>
                </div>

                <div class="chat-input">
                    <textarea x-model="input" rows="1" x-ref="input" placeholder="Demandez une analyse ou une proposition d’action… (Maj+Entrée = saut de ligne)"
                              @keydown.enter="if (!$event.shiftKey) { $event.preventDefault(); send(); }" :disabled="sending"></textarea>
                    <button type="button" class="send" @click="send()" aria-label="Envoyer" :disabled="sending">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor"><path d="M2.01 21L23 12 2.01 3 2 10l15 2-15 2z"/></svg>
                    </button>
                </div>
            </div>

            {{-- Contexte : raccourcis + journal --}}
            <div class="side-col">
                <div class="ukc-card">
                    <h2>Raccourcis d’analyse</h2>
                    <div class="tool-list">
                        <button type="button" class="tool-item" @click="ask('Liste les 10 dernières soumissions en évaluation avec leur délai.')">
                            <strong>Pipeline scientifique</strong>
                            <span>Délais, ateliers, relecteurs</span>
                        </button>
                        <button type="button" class="tool-item" @click="ask('Contrôle les écarts entre inscriptions payées et badges émis.')">
                            <strong>Contrôle paiements / badges</strong>
                            <span>Cohérence financière</span>
                        </button>
                        <button type="button" class="tool-item" @click="ask('Quels ateliers n’ont pas encore de modérateur ou de rapporteur ?')">
                            <strong>Sessions incomplètes</strong>
                            <span>Modérateurs &amp; rapporteurs</span>
                        </button>
                        <button type="button" class="tool-item" @click="ask('Génère un plan d’actions prioritaires pour les 7 prochains jours.')">
                            <strong>Plan 7 jours</strong>
                            <span>Priorités organisateur</span>
                        </button>
                    </div>
                </div>

                <div class="ukc-card">
                    <h2>Journal des décisions</h2>
                    <div>
                        <template x-for="entry in decisionLog" :key="entry.id">
                            <div class="log-item">
                                <div class="when" x-text="entry.when"></div>
                                <div class="what" x-text="entry.what"></div>
                                <div class="status"><span class="ukc-pill" :class="'ukc-pill--' + entry.pill" x-text="entry.label"></span></div>
                            </div>
                        </template>
                        <p x-show="decisionLog.length === 0" style="font-size:12.5px;color:var(--ukc-light);">
                            Aucune décision pour l’instant. Vos approbations et refus de propositions apparaîtront ici.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-layouts.admin>
