<x-layouts.app :title="'Échanges — ' . $session->label">
    <div style="max-width:760px;margin:0 auto;padding:clamp(20px,4vw,40px) clamp(14px,4vw,22px);"
         x-data="sessionEngagement({
            urls: {
                questions: '{{ route('session.engagement.questions', $session) }}',
                ask: '{{ route('session.engagement.ask', $session) }}',
                vote: '{{ url('session/'.$session->id.'/echanges/questions') }}',
                moderate: '{{ url('session/'.$session->id.'/echanges/questions') }}',
            },
            canPost: {{ $canPost ? 'true' : 'false' }},
            canModerate: {{ $canModerate ? 'true' : 'false' }},
         })"
         x-init="init()">

        <div style="display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap;margin-bottom:6px;">
            <a href="{{ route('session.meeting', $session) }}"
               style="display:inline-flex;align-items:center;gap:6px;font-size:.9rem;color:var(--ukc-muted);">
                <x-ukc.icon name="chevron" :size="16" style="transform:rotate(180deg);" /> Retour à la visio
            </a>
            @if ($canModerate)<span class="ukc-pill ukc-pill--info">Modérateur</span>@endif
        </div>

        <h1 style="font-size:1.5rem;margin:4px 0 2px;">Échanges</h1>
        <p style="color:var(--ukc-muted);margin:0 0 20px;">{{ $session->label }}@if ($session->edition?->title) · {{ $session->edition->title }}@endif</p>

        {{-- ========================= Q&A ========================= --}}
        <div class="ukc-card">
            <h2><x-ukc.icon name="inbox" :size="20" /> Questions du public</h2>

            @if ($canPost)
                <form @submit.prevent="ask()" style="margin:10px 0 6px;">
                    <div class="ukc-field">
                        <textarea x-model="draft" rows="2" maxlength="600"
                                  placeholder="Posez votre question au présentateur…"></textarea>
                    </div>
                    <div style="display:flex;justify-content:space-between;align-items:center;gap:10px;margin-top:6px;">
                        <span class="hint" x-text="(600 - draft.length) + ' caractères restants'"></span>
                        <button type="submit" class="ukc-btn ukc-btn--blue ukc-btn--sm" :disabled="!draft.trim()">
                            <x-ukc.icon name="login" :size="15" /> Envoyer
                        </button>
                    </div>
                </form>
            @elseif ($isGuest)
                <p class="ukc-alert ukc-alert--info" style="margin:10px 0;">
                    <span class="ico"><x-ukc.icon name="info" :size="18" /></span>
                    <span><a href="{{ route('account.login') }}" style="color:inherit;text-decoration:underline;font-weight:700;">Connectez-vous</a> pour poser une question et voter.</span>
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
                                <template x-if="q.status === 'answered'"><span class="ukc-pill ukc-pill--ok" style="margin-left:6px;">Répondue</span></template>
                                <template x-if="q.status === 'hidden'"><span class="ukc-pill ukc-pill--muted" style="margin-left:6px;">Masquée</span></template>
                            </div>
                            <div x-show="canModerate" style="display:flex;gap:6px;flex-wrap:wrap;margin-top:8px;">
                                <button type="button" class="ukc-btn ukc-btn--ghost ukc-btn--sm" @click="moderate(q, q.status === 'answered' ? 'pending' : 'answered')"
                                        x-text="q.status === 'answered' ? 'Rouvrir' : 'Marquer répondue'"></button>
                                <button type="button" class="ukc-btn ukc-btn--ghost ukc-btn--sm" @click="moderate(q, q.status === 'hidden' ? 'pending' : 'hidden')"
                                        x-text="q.status === 'hidden' ? 'Réafficher' : 'Masquer'"></button>
                            </div>
                        </div>
                    </div>
                </template>
                <p x-show="!questions.length" style="color:var(--ukc-muted);text-align:center;padding:18px 0;">
                    Aucune question pour l’instant. Soyez le premier à en poser une !
                </p>
            </div>
        </div>
    </div>
</x-layouts.app>
