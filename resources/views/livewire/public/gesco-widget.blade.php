@php
    // Mascotte Gesco (dame au chapeau académique) — SVG autonome.
    $mascot = '<svg viewBox="0 0 80 80" xmlns="http://www.w3.org/2000/svg" aria-hidden="true" style="width:100%;height:100%;">'
        .'<circle cx="40" cy="40" r="40" fill="#e0e7ff"/><ellipse cx="40" cy="72" rx="22" ry="10" fill="#223ea4"/>'
        .'<path d="M22 70c2-14 10-22 18-22s16 8 18 22" fill="#1a3080"/><circle cx="40" cy="36" r="16" fill="#f5d0b5"/>'
        .'<path d="M24 34c2-12 10-16 16-16s14 4 16 16c-4-3-10-5-16-5s-12 2-16 5z" fill="#3d2914"/>'
        .'<path d="M18 28l22-10 22 10-22 8z" fill="#1a2332"/><rect x="36" y="18" width="8" height="4" rx="1" fill="#ffd700"/>'
        .'<path d="M58 28v10l-4 2v-8z" fill="#ffd700"/><circle cx="34" cy="36" r="1.6" fill="#1a2332"/>'
        .'<circle cx="46" cy="36" r="1.6" fill="#1a2332"/><path d="M36 42c2 2 6 2 8 0" stroke="#c4785a" stroke-width="1.4" fill="none" stroke-linecap="round"/></svg>';
@endphp

<div class="ukc-cb-root" x-data="{ open: false, expanded: false }" @keydown.escape.window="open = false">
    <div class="ukc-cb-panel" :class="{ 'open': open, 'expanded': expanded }">
        <div class="ukc-cb-head">
            <div class="av">{!! $mascot !!}</div>
            <div class="meta">
                <strong>Gesco</strong>
                <span>Assistante colloque · en ligne</span>
            </div>
            <div class="tools">
                <button type="button" @click="expanded = !expanded" title="Agrandir / réduire" aria-label="Agrandir">
                    <svg viewBox="0 0 24 24" fill="currentColor"><path d="M7 14H5v5h5v-2H7v-3zm-2-4h2V7h3V5H5v5zm12 7h-3v2h5v-5h-2v3zM14 5v2h3v3h2V5h-5z"/></svg>
                </button>
                <button type="button" @click="open = false" title="Réduire" aria-label="Fermer">
                    <svg viewBox="0 0 24 24" fill="currentColor"><path d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"/></svg>
                </button>
            </div>
        </div>

        <div class="ukc-cb-body" id="cbLog">
            @foreach ($messages as $m)
                <div class="ukc-cb-msg {{ $m['role'] === 'user' ? 'user' : 'bot' }}">{!! nl2br(e($m['text'])) !!}</div>
            @endforeach
            <div class="ukc-cb-msg bot" wire:loading wire:target="send,ask" style="font-style:italic;opacity:.75;">Gesco écrit…</div>
        </div>

        <div class="ukc-cb-foot">
            <input type="text" wire:model="input" placeholder="Votre message…"
                   @keydown.enter="$wire.send()" wire:loading.attr="disabled" wire:target="send,ask">
            <button type="button" wire:click="send" wire:loading.attr="disabled" wire:target="send,ask">Envoyer</button>
        </div>
    </div>

    <button type="button" class="ukc-cb-fab" @click="open = !open" title="Ouvrir Gesco" aria-label="Ouvrir le chat Gesco">
        <span class="pulse"></span>
        <span class="mascot">{!! $mascot !!}</span>
    </button>

    @script
    <script>
        const log = document.getElementById('cbLog');
        if (log) {
            const scroll = () => { log.scrollTop = log.scrollHeight; };
            scroll();
            new MutationObserver(scroll).observe(log, { childList: true, subtree: true });
        }
    </script>
    @endscript
</div>
