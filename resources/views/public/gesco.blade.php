@php
    $seed = __('Bonjour ! Je suis :name, l’assistante des événements scientifiques de l’Université de Kara. Comment puis-je vous aider aujourd’hui ?', ['name' => $agentName]);
@endphp
<x-layouts.app :title="__('Assistante :name', ['name' => $agentName])">
    @include('partials.public-header')

    <section class="ukc-section ukc-section--tight">
        <div class="ukc-container" style="max-width:820px;">
            <div class="gesco-chat" x-data="gescoChat(@js($seed), '{{ route('gesco.ask') }}', @js($agentName), @js((string) (auth('account')->id() ?? 'guest')))">
                <div class="chat-hero">
                    <div class="av">
                        @if ($agentAvatar)
                            <img src="{{ $agentAvatar }}" alt="{{ $agentName }}" style="width:100%;height:100%;object-fit:cover;">
                        @else
                            {{ \Illuminate\Support\Str::substr($agentName, 0, 1) }}
                        @endif
                    </div>
                    <div>
                        <h1>{{ $agentName }} - {{ __('votre assistante événements') }}</h1>
                        <p>{{ __('Questions sur les soumissions, l’inscription, les tarifs, le programme et les documents.') }}</p>
                    </div>
                    <button type="button" class="ukc-btn ukc-btn--ghost ukc-btn--sm" style="margin-left:auto;align-self:center;flex-shrink:0;"
                            x-show="messages.length > 1" @click="reset()" :title="'{{ __('Nouvelle discussion') }}'">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 12a9 9 0 1 0 3-6.7L3 8"/><path d="M3 3v5h5"/></svg>
                        {{ __('Nouvelle discussion') }}
                    </button>
                </div>

                <div class="chat-shell">
                    <div class="chat-messages" x-ref="log">
                        <template x-for="(m, i) in messages" :key="i">
                            <div class="chat-row" :class="m.role === 'user' ? 'user' : 'bot'">
                                <div class="msg" :class="m.role === 'user' ? 'user' : 'bot'">
                                    <div class="who" x-text="m.role === 'user' ? '{{ __('Vous') }}' : agentName"></div>
                                    <template x-if="m.role === 'user'"><div style="white-space:pre-wrap;" x-text="m.text"></div></template>
                                    <template x-if="m.role !== 'user'"><div class="chat-md" x-html="render(m.text)"></div></template>
                                </div>
                                <div class="ukc-chat-act">
                                    <button type="button" @click="copy(i, m.text)" :title="'{{ __('Copier') }}'">
                                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="9" y="9" width="12" height="12" rx="2"/><path d="M5 15V5a2 2 0 0 1 2-2h10"/></svg>
                                        <span x-text="copiedIndex === i ? '{{ __('Copié') }}' : '{{ __('Copier') }}'"></span>
                                    </button>
                                    <template x-if="m.role === 'user'">
                                        <button type="button" @click="editUser(i)" :title="'{{ __('Modifier et relancer') }}'">
                                            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 20h9"/><path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4Z"/></svg>
                                            <span>{{ __('Modifier') }}</span>
                                        </button>
                                    </template>
                                </div>
                            </div>
                        </template>
                        <div class="msg bot typing" x-show="sending" aria-label="{{ '…' }}">
                            <span class="typing-dots"><span></span><span></span><span></span></span>
                        </div>
                    </div>

                    <div class="suggestions" x-show="messages.length <= 1">
                        <button type="button" @click="ask('{{ __('Comment soumettre une communication ?') }}')">{{ __('Soumettre une communication') }}</button>
                        <button type="button" @click="ask('{{ __('Quels sont les tarifs d’inscription ?') }}')">{{ __('Tarifs d’inscription') }}</button>
                        <button type="button" @click="ask('{{ __('Où voir mon badge et mon attestation ?') }}')">{{ __('Badge & attestation') }}</button>
                        <button type="button" @click="ask('{{ __('Quel est le programme de l’événement ?') }}')">{{ __('Programme') }}</button>
                    </div>

                    <div class="chat-input" :class="{ 'is-big': big, 'has-grow': showGrow || big }" x-data="{ big: false }">
                        <button type="button" class="grow" x-show="showGrow || big" x-cloak @click="big = !big" :aria-label="big ? '{{ __('Réduire la zone de saisie') }}' : '{{ __('Agrandir la zone de saisie') }}'" :title="big ? '{{ __('Réduire') }}' : '{{ __('Agrandir') }}'">
                            <svg x-show="!big" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M15 3h6v6"/><path d="M9 21H3v-6"/><path d="M21 3l-7 7"/><path d="M3 21l7-7"/></svg>
                            <svg x-show="big" x-cloak width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 14h6v6"/><path d="M20 10h-6V4"/><path d="M14 10l7-7"/><path d="M3 21l7-7"/></svg>
                        </button>
                        <textarea x-model="input" rows="1" x-ref="input" placeholder="{{ __('Écrivez votre message…') }}"
                                  @keydown.enter="if (!$event.shiftKey) { $event.preventDefault(); send(); }"
                                  @input="autogrow($event.target)"
                                  :disabled="sending"></textarea>
                        <button type="button" class="send" @click="send()" aria-label="{{ __('Envoyer') }}" :disabled="sending">
                            <svg viewBox="0 0 24 24" fill="currentColor"><path d="M2.01 21L23 12 2.01 3 2 10l15 2-15 2z"/></svg>
                        </button>
                    </div>
                </div>

                <p class="hint" style="text-align:center;font-size:12.5px;color:var(--ukc-muted);margin-top:10px;">
                    {{ $agentName }} {{ __('s’appuie sur les informations officielles de l’événement. Pour une demande sensible, contactez le secrétariat.') }}
                </p>
            </div>
        </div>
    </section>

    @include('partials.public-footer')
</x-layouts.app>
