@php
    use App\Support\AiAgents;
    use Illuminate\Support\Facades\Storage;

    // Avatar de l'agent public (participants) configuré dans les réglages IA.
    $agent = collect(AiAgents::all())->firstWhere('audience', 'participants') ?? AiAgents::find('gesco');
    $agentName = $agent['name'] ?? 'Gesco';
    $agentAvatar = ($agent['avatar'] ?? '') !== '' ? Storage::url($agent['avatar']) : null;

    // Mascotte Gesco (dame au chapeau académique) — SVG autonome.
    $mascot = '<svg viewBox="0 0 80 80" xmlns="http://www.w3.org/2000/svg" aria-hidden="true" style="width:100%;height:100%;">'
        .'<circle cx="40" cy="40" r="40" fill="#e0e7ff"/><ellipse cx="40" cy="72" rx="22" ry="10" fill="#223ea4"/>'
        .'<path d="M22 70c2-14 10-22 18-22s16 8 18 22" fill="#1a3080"/><circle cx="40" cy="36" r="16" fill="#f5d0b5"/>'
        .'<path d="M24 34c2-12 10-16 16-16s14 4 16 16c-4-3-10-5-16-5s-12 2-16 5z" fill="#3d2914"/>'
        .'<path d="M18 28l22-10 22 10-22 8z" fill="#1a2332"/><rect x="36" y="18" width="8" height="4" rx="1" fill="#ffd700"/>'
        .'<path d="M58 28v10l-4 2v-8z" fill="#ffd700"/><circle cx="34" cy="36" r="1.6" fill="#1a2332"/>'
        .'<circle cx="46" cy="36" r="1.6" fill="#1a2332"/><path d="M36 42c2 2 6 2 8 0" stroke="#c4785a" stroke-width="1.4" fill="none" stroke-linecap="round"/></svg>';

    $avatarHtml = $agentAvatar
        ? '<img src="'.e($agentAvatar).'" alt="'.e($agentName).'" style="width:100%;height:100%;object-fit:cover;border-radius:50%;">'
        : $mascot;

    // Même message d'accueil que la page dédiée /assistante.
    $seed = 'Bonjour ! Je suis '.$agentName.', l’assistante du colloque de l’Université de Kara. '
        .'Comment puis-je vous aider aujourd’hui ?';
@endphp

<div class="ukc-cb-root" x-data="{ open: false, expanded: false, ...gescoChat(@js($seed), '{{ route('gesco.ask') }}', @js($agentName), @js((string) (auth('account')->id() ?? 'guest'))) }"
     @keydown.escape.window="open = false">
    <div class="ukc-cb-panel" :class="{ 'open': open, 'expanded': expanded }">
        <div class="ukc-cb-head">
            <div class="av">{!! $avatarHtml !!}</div>
            <div class="meta">
                <strong>{{ $agentName }}</strong>
                <span>Assistante colloque · en ligne</span>
            </div>
            <div class="tools">
                <button type="button" @click="reset()" x-show="messages.length > 1" title="Nouvelle discussion" aria-label="Nouvelle discussion">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 12a9 9 0 1 0 3-6.7L3 8"/><path d="M3 3v5h5"/></svg>
                </button>
                <button type="button" @click="expanded = !expanded" title="Agrandir / réduire" aria-label="Agrandir">
                    <svg viewBox="0 0 24 24" fill="currentColor"><path d="M7 14H5v5h5v-2H7v-3zm-2-4h2V7h3V5H5v5zm12 7h-3v2h5v-5h-2v3zM14 5v2h3v3h2V5h-5z"/></svg>
                </button>
                <button type="button" @click="open = false" title="Réduire" aria-label="Fermer">
                    <svg viewBox="0 0 24 24" fill="currentColor"><path d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"/></svg>
                </button>
            </div>
        </div>

        <div class="ukc-cb-body" x-ref="log">
            <template x-for="(m, i) in messages" :key="i">
                <div class="chat-row" :class="m.role === 'user' ? 'user' : 'bot'">
                    <div class="ukc-cb-msg" :class="m.role === 'user' ? 'user' : 'bot'">
                        <template x-if="m.role === 'user'"><div style="white-space:pre-wrap;" x-text="m.text"></div></template>
                        <template x-if="m.role !== 'user'"><div class="chat-md" x-html="render(m.text)"></div></template>
                    </div>
                    <div class="ukc-chat-act">
                        <button type="button" @click="copy(i, m.text)" title="Copier">
                            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="9" y="9" width="12" height="12" rx="2"/><path d="M5 15V5a2 2 0 0 1 2-2h10"/></svg>
                            <span x-text="copiedIndex === i ? 'Copié' : 'Copier'"></span>
                        </button>
                        <template x-if="m.role === 'user'">
                            <button type="button" @click="editUser(i)" title="Modifier et relancer">
                                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 20h9"/><path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4Z"/></svg>
                                <span>Modifier</span>
                            </button>
                        </template>
                    </div>
                </div>
            </template>
            <div class="ukc-cb-msg bot typing" x-show="sending">
                <span class="typing-dots"><span></span><span></span><span></span></span>
            </div>
        </div>

        <div class="ukc-cb-foot" :class="{ 'is-big': big, 'has-grow': showGrow || big }" x-data="{ big: false }">
            <button type="button" class="grow" x-show="showGrow || big" x-cloak @click="big = !big" :title="big ? 'Réduire' : 'Agrandir'" :aria-label="big ? 'Réduire la zone de saisie' : 'Agrandir la zone de saisie'">
                <svg x-show="!big" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M15 3h6v6"/><path d="M9 21H3v-6"/><path d="M21 3l-7 7"/><path d="M3 21l7-7"/></svg>
                <svg x-show="big" x-cloak width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 14h6v6"/><path d="M20 10h-6V4"/><path d="M14 10l7-7"/><path d="M3 21l7-7"/></svg>
            </button>
            <textarea x-model="input" rows="1" x-ref="input" placeholder="Votre message…"
                      @keydown.enter="if (!$event.shiftKey) { $event.preventDefault(); send(); }" @input="autogrow($event.target)" :disabled="sending"></textarea>
            <button type="button" class="send" @click="send()" :disabled="sending" aria-label="Envoyer">
                <svg viewBox="0 0 24 24" fill="currentColor"><path d="M2.01 21L23 12 2.01 3 2 10l15 2-15 2z"/></svg>
            </button>
        </div>
    </div>

    <button type="button" class="ukc-cb-fab" @click="open = !open" title="Ouvrir Gesco" aria-label="Ouvrir le chat Gesco">
        <span class="pulse"></span>
        <span class="mascot">{!! $avatarHtml !!}</span>
    </button>
</div>
