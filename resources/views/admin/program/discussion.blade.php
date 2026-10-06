<x-layouts.admin title="Fil de discussion" :subtitle="$session->label">
    <x-slot:actions>
        <a href="{{ route('admin.program.index', ['event_id' => $event_id, 'tab' => 'discussions']) }}" class="ukc-btn ukc-btn--outline ukc-btn--sm">
            <x-ukc.icon name="chevron" :size="16" /> Retour aux discussions
        </a>
        @if ($messages->isNotEmpty())
            <a href="{{ route('admin.program.discussions.export', $session) }}" class="ukc-btn ukc-btn--sm">
                <x-ukc.icon name="download" :size="16" /> Télécharger (.txt)
            </a>
        @endif
    </x-slot:actions>

    <div class="admin-page" style="max-width:900px;">
        <div class="ukc-card" style="margin-bottom:16px;">
            <div style="font-size:12px;color:var(--ukc-muted);text-transform:uppercase;letter-spacing:.04em;">
                {{ $session->event?->series?->name ?: 'Colloque' }} · {{ $session->event?->title ?: $session->event?->year }}
            </div>
            <h3 style="margin:2px 0 4px;">{{ $session->label }}</h3>
            <div style="font-size:13px;color:var(--ukc-muted);">
                @if ($session->starts_at){{ $session->starts_at->translatedFormat('l j F · H:i') }}@endif
                @if ($session->ends_at) – {{ $session->ends_at->translatedFormat('H:i') }}@endif
                @if ($session->room) · {{ $session->room }}@endif
                · {{ $messages->count() }} message(s)
            </div>
        </div>

        <div class="ukc-card">
            @forelse ($messages as $m)
                <div style="padding:10px 0;border-bottom:1px solid var(--ukc-border);">
                    <div style="display:flex;justify-content:space-between;gap:12px;align-items:baseline;">
                        <strong style="font-size:13.5px;">{{ $m->author_name }}</strong>
                        <span style="font-size:12px;color:var(--ukc-muted);white-space:nowrap;">
                            {{ $m->created_at?->translatedFormat('j M · H:i') }}
                        </span>
                    </div>
                    @if ($m->reply_uid)
                        <div style="font-size:12px;color:var(--ukc-muted);border-left:2px solid var(--ukc-border);padding-left:8px;margin:4px 0;">
                            en réponse à <strong>{{ $m->reply_author }}</strong> « {{ $m->reply_excerpt }} »
                        </div>
                    @endif
                    <div style="font-size:13.5px;white-space:pre-wrap;margin-top:2px;">{{ $m->body }}</div>
                    @php($reacts = $m->reactions->groupBy('emoji')->map(fn ($g) => $g->count()))
                    @if ($reacts->isNotEmpty())
                        <div style="font-size:12.5px;color:var(--ukc-muted);margin-top:4px;display:flex;gap:8px;flex-wrap:wrap;">
                            @foreach ($reacts as $emoji => $n)
                                <span>{{ $emoji }} {{ $n }}</span>
                            @endforeach
                        </div>
                    @endif
                </div>
            @empty
                <div class="ukc-empty">
                    <span class="ico"><x-ukc.icon name="message" :size="28" /></span>
                    <h3>Aucun message</h3>
                    <p>Cette séance n'a pas de message dans son fil de discussion.</p>
                </div>
            @endforelse
        </div>
    </div>
</x-layouts.admin>
