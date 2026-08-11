{{-- Rendu public d'une session avec ses communications. Attend : $session, $commsBySession --}}
@php($comms = $commsBySession[$session->id] ?? collect())
<div style="padding:14px 16px;background:var(--ukc-bg);border:1px solid var(--ukc-border);border-radius:12px;margin-bottom:12px;">
    <div style="display:flex;justify-content:space-between;gap:10px;flex-wrap:wrap;align-items:baseline;">
        <div style="font-weight:800;display:flex;align-items:center;gap:8px;">
            {{ $session->label }}
            @if ($session->isLive())
                <span class="ukc-pill ukc-pill--err" style="display:inline-flex;align-items:center;gap:5px;">
                    <span style="width:7px;height:7px;border-radius:50%;background:currentColor;"></span> EN DIRECT
                </span>
            @endif
        </div>
        <div style="font-size:.82rem;color:var(--ukc-muted);">
            @if ($session->session_day){{ $session->session_day->translatedFormat('d F') }}@endif
            @if ($session->starts_at) · {{ $session->starts_at->format('H:i') }}@if ($session->ends_at)–{{ $session->ends_at->format('H:i') }}@endif @endif
            @if ($session->room) · Salle {{ $session->room }} @endif
        </div>
    </div>
    @if ($session->isLive())
        <div style="margin-top:8px;">
            <a href="{{ route('session.meeting', $session) }}" class="ukc-btn ukc-btn--green ukc-btn--sm">
                <x-ukc.icon name="video" :size="15" /> Rejoindre la visio
            </a>
        </div>
    @endif
    @if ($session->moderator_name || $session->rapporteur_name)
        <div style="font-size:.8rem;color:var(--ukc-muted);margin-top:4px;">
            @if ($session->moderator_name) Modérateur : {{ $session->moderator_name }} @endif
            @if ($session->rapporteur_name) · Rapporteur : {{ $session->rapporteur_name }} @endif
        </div>
    @endif
    @if ($comms->count())
        <ul style="list-style:none;margin-top:10px;display:flex;flex-direction:column;gap:6px;">
            @foreach ($comms as $slot)
                @php($sub = $slot->submission)
                <li style="display:flex;gap:8px;align-items:baseline;font-size:.9rem;">
                    @if ($slot->starts_at)<span style="font-family:ui-monospace,monospace;color:var(--ukc-blue);font-weight:700;flex-shrink:0;">{{ $slot->starts_at->format('H:i') }}</span>@endif
                    <span>
                        <span style="font-weight:600;">{{ $sub?->title }}</span>
                        @if ($sub && filled($sub->authors))
                            <span style="color:var(--ukc-muted);"> — {{ collect($sub->authors)->pluck('name')->filter()->join(', ') }}</span>
                        @endif
                    </span>
                </li>
            @endforeach
        </ul>
    @endif
</div>
