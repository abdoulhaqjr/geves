{{-- Contrôles visio d'une session (back-office). Attend : $s (EventSession) --}}
@php($st = $s->meetingStatusEnum())
@php($vis = $s->meetingVisibilityEnum())
<div style="margin-top:8px;padding-top:8px;border-top:1px dashed var(--ukc-border);display:flex;flex-wrap:wrap;gap:8px;align-items:center;">
    <span style="font-size:.72rem;color:var(--ukc-muted);text-transform:uppercase;letter-spacing:.05em;font-weight:700;">Visio</span>
    <span class="ukc-pill ukc-pill--{{ $st->pill() }}">{{ $st->label() }}</span>

    <div style="display:inline-flex;gap:4px;flex-wrap:wrap;">
        @foreach (['scheduled' => 'Programmer', 'live' => 'En direct', 'ended' => 'Terminer', 'none' => 'Aucune'] as $val => $lbl)
            <button type="button" wire:click="setMeetingStatus({{ $s->id }}, '{{ $val }}')"
                    @class(['ukc-btn', 'ukc-btn--sm', 'ukc-btn--primary' => $st->value === $val, 'ukc-btn--ghost' => $st->value !== $val])>
                {{ $lbl }}
            </button>
        @endforeach
    </div>

    <span style="width:1px;height:20px;background:var(--ukc-border);"></span>

    <span style="font-size:.72rem;color:var(--ukc-muted);">Accès</span>
    <div style="display:inline-flex;gap:4px;">
        @foreach (['public' => 'Public', 'participants' => 'Participants'] as $val => $lbl)
            <button type="button" wire:click="setMeetingVisibility({{ $s->id }}, '{{ $val }}')"
                    @class(['ukc-btn', 'ukc-btn--sm', 'ukc-btn--primary' => $vis->value === $val, 'ukc-btn--ghost' => $vis->value !== $val])>
                {{ $lbl }}
            </button>
        @endforeach
    </div>

    @if ($s->isLive())
        <a href="{{ route('session.meeting', $s) }}" target="_blank" class="ukc-btn ukc-btn--green ukc-btn--sm">
            <x-ukc.icon name="video" :size="14" /> Rejoindre (modérateur)
        </a>
    @endif
</div>
