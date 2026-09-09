@php
    $seed = __('Bonjour ! Je suis :name, l’assistante du colloque de l’Université de Kara. Comment puis-je vous aider aujourd’hui ?', ['name' => $agentName]);
@endphp
<x-layouts.app :title="__('Assistante :name', ['name' => $agentName])">
    @include('partials.public-header')

    <section class="ukc-section ukc-section--tight">
        <div class="ukc-container" style="max-width:820px;">
            <div class="gesco-chat" x-data="gescoChat(@js($seed), '{{ route('gesco.ask') }}', @js($agentName))">
                <div class="chat-hero">
                    <div class="av">
                        @if ($agentAvatar)
                            <img src="{{ $agentAvatar }}" alt="{{ $agentName }}" style="width:100%;height:100%;object-fit:cover;">
                        @else
                            {{ \Illuminate\Support\Str::substr($agentName, 0, 1) }}
                        @endif
                    </div>
                    <div>
                        <h1>{{ $agentName }} — {{ __('votre assistante colloque') }}</h1>
                        <p>{{ __('Questions sur les soumissions, l’inscription, les tarifs, le programme et les documents.') }}</p>
                    </div>
                </div>

                <div class="chat-shell">
                    <div class="chat-messages" x-ref="log">
                        <template x-for="(m, i) in messages" :key="i">
                            <div class="msg" :class="m.role === 'user' ? 'user' : 'bot'">
                                <div class="who" x-text="m.role === 'user' ? '{{ __('Vous') }}' : agentName"></div>
                                <template x-if="m.role === 'user'"><div style="white-space:pre-wrap;" x-text="m.text"></div></template>
                                <template x-if="m.role !== 'user'"><div class="chat-md" x-html="render(m.text)"></div></template>
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
                        <button type="button" @click="ask('{{ __('Quel est le programme du colloque ?') }}')">{{ __('Programme') }}</button>
                    </div>

                    <div class="chat-input">
                        <textarea x-model="input" rows="1" placeholder="{{ __('Écrivez votre message…') }}"
                                  @keydown.enter.prevent="if (!$event.shiftKey) { send(); }"
                                  :disabled="sending"></textarea>
                        <button type="button" class="send" @click="send()" aria-label="Envoyer" :disabled="sending">
                            <svg viewBox="0 0 24 24" fill="currentColor"><path d="M2.01 21L23 12 2.01 3 2 10l15 2-15 2z"/></svg>
                        </button>
                    </div>
                </div>

                <p class="hint" style="text-align:center;font-size:12.5px;color:var(--ukc-muted);margin-top:10px;">
                    {{ $agentName }} {{ __('s’appuie sur les informations officielles du colloque. Pour une demande sensible, contactez le secrétariat.') }}
                </p>
            </div>
        </div>
    </section>

    @include('partials.public-footer')
</x-layouts.app>
