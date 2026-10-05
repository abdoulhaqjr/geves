<x-layouts.app :title="'Échanges - ' . $session->label">
    <div style="max-width:760px;margin:0 auto;padding:clamp(20px,4vw,40px) clamp(14px,4vw,22px);"
         x-data="sessionEngagement({
            urls: {
                questions: '{{ route('session.engagement.questions', $session) }}',
                ask: '{{ route('session.engagement.ask', $session) }}',
                vote: '{{ url('session/'.$session->id.'/echanges/questions') }}',
                moderate: '{{ url('session/'.$session->id.'/echanges/questions') }}',
                polls: '{{ route('session.engagement.polls', $session) }}',
                createPoll: '{{ route('session.engagement.polls.create', $session) }}',
                pollBase: '{{ url('session/'.$session->id.'/echanges/sondages') }}',
            },
            canPost: {{ $canPost ? 'true' : 'false' }},
            canModerate: {{ $canModerate ? 'true' : 'false' }},
         })"
         x-init="init()">

        <div style="display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap;margin-bottom:6px;">
            <a href="{{ route('session.meeting', $session) }}"
               style="display:inline-flex;align-items:center;gap:6px;font-size:.9rem;color:var(--ukc-muted);">
                <x-ukc.icon name="chevron" :size="16" style="transform:rotate(180deg);" /> {{ __('Retour à la visio') }}
            </a>
            @if ($canModerate)<span class="ukc-pill ukc-pill--info">{{ __('Modérateur') }}</span>@endif
        </div>

        <h1 style="font-size:1.5rem;margin:4px 0 2px;">{{ __('Échanges') }}</h1>
        <p style="color:var(--ukc-muted);margin:0 0 20px;">{{ $session->label }}@if ($session->event?->title) · {{ $session->event->title }}@endif</p>

        {{-- ========================= Sondages ========================= --}}
        <div class="ukc-card" style="margin-bottom:18px;">
            <h2><x-ukc.icon name="chart" :size="20" /> {{ __('Sondages') }}</h2>

            @if ($canModerate)
                <form @submit.prevent="createPoll()" style="margin:10px 0 16px;padding:12px;border:1px dashed var(--ukc-border);border-radius:12px;">
                    <div class="ukc-field">
                        <label>{{ __('Nouveau sondage') }}</label>
                        <input type="text" x-model="pollDraft.question" maxlength="300" placeholder="{{ __('Question posée au public') }}">
                    </div>
                    <template x-for="(opt, i) in pollDraft.options" :key="i">
                        <div style="display:flex;gap:8px;align-items:center;margin-top:6px;">
                            <input type="text" x-model="pollDraft.options[i]" maxlength="150" :placeholder="'{{ __('Option') }} ' + (i+1)" style="flex:1;">
                            <button type="button" class="ukc-btn ukc-btn--ghost ukc-btn--sm" @click="pollDraft.options.splice(i,1)" x-show="pollDraft.options.length > 2">
                                <x-ukc.icon name="close" :size="14" />
                            </button>
                        </div>
                    </template>
                    <div style="display:flex;justify-content:space-between;gap:10px;margin-top:10px;">
                        <button type="button" class="ukc-btn ukc-btn--outline ukc-btn--sm" @click="pollDraft.options.push('')" x-show="pollDraft.options.length < 6">
                            <x-ukc.icon name="plus" :size="14" /> {{ __('Ajouter une option') }}
                        </button>
                        <button type="submit" class="ukc-btn ukc-btn--blue ukc-btn--sm" :disabled="!pollReady()">
                            <x-ukc.icon name="check" :size="15" /> {{ __('Lancer le sondage') }}
                        </button>
                    </div>
                </form>
            @endif

            <div style="display:flex;flex-direction:column;gap:14px;">
                <template x-for="p in polls" :key="p.id">
                    <div style="border:1px solid var(--ukc-border);border-radius:12px;padding:14px;">
                        <div style="display:flex;justify-content:space-between;gap:10px;align-items:baseline;">
                            <strong x-text="p.question" style="flex:1;"></strong>
                            <template x-if="p.status === 'closed'"><span class="ukc-pill ukc-pill--muted">{{ __('Clos') }}</span></template>
                        </div>
                        <div style="margin-top:10px;display:flex;flex-direction:column;gap:8px;">
                            <template x-for="(opt, idx) in p.options" :key="idx">
                                <div>
                                    <button type="button" class="poll-opt"
                                            @click="votePoll(p, idx)"
                                            :disabled="!canPost || p.status !== 'open'"
                                            :class="{ 'is-mine': p.mine === idx }"
                                            style="width:100%;text-align:left;border:1px solid var(--ukc-border);border-radius:10px;padding:8px 12px;background:var(--ukc-bg);cursor:pointer;position:relative;overflow:hidden;">
                                        <span style="position:absolute;inset:0;background:rgba(34,62,164,.10);transform-origin:left;"
                                              :style="'width:' + (p.total ? Math.round(p.counts[idx]/p.total*100) : 0) + '%'"></span>
                                        <span style="position:relative;display:flex;justify-content:space-between;gap:10px;">
                                            <span x-text="opt"></span>
                                            <strong x-show="p.mine !== null || p.status === 'closed' || canModerate"
                                                    x-text="(p.total ? Math.round(p.counts[idx]/p.total*100) : 0) + '% (' + p.counts[idx] + ')'"></strong>
                                        </span>
                                    </button>
                                </div>
                            </template>
                        </div>
                        <div style="display:flex;justify-content:space-between;align-items:center;margin-top:8px;">
                            <span class="hint" x-text="p.total + ' {{ __('vote(s)') }}'"></span>
                            <button type="button" x-show="canModerate" class="ukc-btn ukc-btn--ghost ukc-btn--sm"
                                    @click="togglePoll(p)" x-text="p.status === 'open' ? '{{ __('Clore') }}' : '{{ __('Rouvrir') }}'"></button>
                        </div>
                    </div>
                </template>
                <p x-show="!polls.length" style="color:var(--ukc-muted);text-align:center;padding:12px 0;">
                    {{ $canModerate ? __('Aucun sondage. Lancez-en un ci-dessus.') : __('Aucun sondage en cours.') }}
                </p>
            </div>
        </div>

        {{-- ========================= Q&A ========================= --}}
        <div class="ukc-card">
            <h2><x-ukc.icon name="inbox" :size="20" /> {{ __('Questions du public') }}</h2>

            @if ($canPost)
                <form @submit.prevent="ask()" style="margin:10px 0 6px;">
                    <div class="ukc-field">
                        <textarea x-model="draft" rows="2" maxlength="600"
                                  placeholder="{{ __('Posez votre question au présentateur…') }}"></textarea>
                    </div>
                    <div style="display:flex;justify-content:space-between;align-items:center;gap:10px;margin-top:6px;">
                        <span class="hint" x-text="(600 - draft.length) + ' {{ __('caractères restants') }}'"></span>
                        <button type="submit" class="ukc-btn ukc-btn--blue ukc-btn--sm" :disabled="!draft.trim()">
                            <x-ukc.icon name="login" :size="15" /> {{ __('Envoyer') }}
                        </button>
                    </div>
                </form>
            @elseif ($isGuest)
                <p class="ukc-alert ukc-alert--info" style="margin:10px 0;">
                    <span class="ico"><x-ukc.icon name="info" :size="18" /></span>
                    <span><a href="{{ route('account.login') }}" style="color:inherit;text-decoration:underline;font-weight:700;">{{ __('Connectez-vous') }}</a> {{ __('pour poser une question et voter.') }}</span>
                </p>
            @endif

            <div style="margin-top:14px;display:flex;flex-direction:column;gap:10px;">
                <template x-for="q in questions" :key="q.id">
                    <div class="qa-item" :class="{ 'qa-answered': q.status === 'answered', 'qa-hidden': q.status === 'hidden' }"
                         style="display:flex;gap:12px;padding:12px;border:1px solid var(--ukc-border);border-radius:12px;">
                        <button type="button" @click="toggleVote(q)" :disabled="!canPost"
                                class="qa-vote" :class="{ 'is-voted': q.voted }"
                                style="display:flex;flex-direction:column;align-items:center;justify-content:center;min-width:46px;padding:6px;border:1px solid var(--ukc-border);border-radius:10px;background:var(--ukc-bg);cursor:pointer;">
                            <x-ukc.icon name="chevron" :size="16" style="transform:rotate(-90deg);" />
                            <strong x-text="q.votes" style="font-size:1rem;"></strong>
                        </button>
                        <div style="flex:1;min-width:0;">
                            <div style="white-space:pre-line;" x-text="q.body"></div>
                            <div style="font-size:.8rem;color:var(--ukc-muted);margin-top:4px;">
                                <span x-text="q.author"></span>
                                <template x-if="q.status === 'answered'"><span class="ukc-pill ukc-pill--ok" style="margin-left:6px;">{{ __('Répondue') }}</span></template>
                                <template x-if="q.status === 'hidden'"><span class="ukc-pill ukc-pill--muted" style="margin-left:6px;">{{ __('Masquée') }}</span></template>
                            </div>
                            <div x-show="canModerate" style="display:flex;gap:6px;flex-wrap:wrap;margin-top:8px;">
                                <button type="button" class="ukc-btn ukc-btn--ghost ukc-btn--sm" @click="moderate(q, q.status === 'answered' ? 'pending' : 'answered')"
                                        x-text="q.status === 'answered' ? '{{ __('Rouvrir') }}' : '{{ __('Marquer répondue') }}'"></button>
                                <button type="button" class="ukc-btn ukc-btn--ghost ukc-btn--sm" @click="moderate(q, q.status === 'hidden' ? 'pending' : 'hidden')"
                                        x-text="q.status === 'hidden' ? '{{ __('Réafficher') }}' : '{{ __('Masquer') }}'"></button>
                            </div>
                        </div>
                    </div>
                </template>
                <p x-show="!questions.length" style="color:var(--ukc-muted);text-align:center;padding:18px 0;">
                    {{ __('Aucune question pour l’instant. Soyez le premier à en poser une !') }}
                </p>
            </div>
        </div>
    </div>
</x-layouts.app>
