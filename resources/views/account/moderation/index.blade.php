<x-layouts.participant :title="__('Mes séances à modérer')" :subtitle="__('Espace modérateur - présence et passage des communications')">
<div class="account-moderation">
    @forelse ($sessions as $session)
        @php
            $comms = $session->scheduledCommunications();
            $live = $session->isLive();
            $started = $session->hasStarted();
            $presence = $started ? $session->presenceCounts() : null;
        @endphp
        <div class="ukc-card" style="margin-bottom:16px;">
            <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:12px;flex-wrap:wrap;">
                <div>
                    <div style="font-size:12px;color:var(--ukc-muted);text-transform:uppercase;letter-spacing:.04em;">
                        {{ $session->event?->series?->name ?: __('Colloque') }} - {{ $session->event?->title ?: $session->event?->year }}
                    </div>
                    <h3 style="margin:2px 0 4px;">{{ $session->label }}</h3>
                    <div style="font-size:13px;color:var(--ukc-muted);">
                        @if ($session->starts_at){{ $session->starts_at->translatedFormat('l j F - H:i') }}@endif
                        @if ($session->ends_at) - {{ $session->ends_at->translatedFormat('H:i') }}@endif
                        @if ($session->room) - {{ $session->room }}@endif
                    </div>
                </div>
                @if ($live)
                    <span class="ukc-pill ukc-pill--err" style="align-self:center;">● {{ __('En direct') }}</span>
                @elseif ($session->hasMeeting())
                    {{-- Statut réel (programmée / terminée) : une visio clôturée ne doit
                         pas s'annoncer « programmée ». --}}
                    <span class="ukc-pill ukc-pill--{{ $session->meetingStatusEnum()->pill() }}" style="align-self:center;">{{ $session->meetingStatusEnum()->label() }}</span>
                @endif
            </div>

            {{-- ⚠️ La carte NE LISTE PLUS les communications (ce serait long) : elles
                 vivent sur leur propre page « Communications programmées », qui sert
                 aussi à en valider le passage. --}}

            <div style="display:flex;flex-wrap:wrap;gap:8px;margin-top:14px;">
                @if ($session->hasMeeting())
                    <a href="{{ route('session.meeting', $session) }}" class="ukc-btn ukc-btn--outline ukc-btn--sm">
                        <x-ukc.icon name="video" :size="16" /> {{ __('Rejoindre la visio') }}
                    </a>
                @endif
                {{-- Présence : ouverte au début de la séance, comme le rapport - on ne
                     pointe pas une séance pas encore commencée. --}}
                @if ($started)
                    <a href="{{ route('account.attendance.edit', $session) }}" class="ukc-btn ukc-btn--outline ukc-btn--sm">
                        <x-ukc.icon name="users" :size="16" /> {{ __('Présence') }}
                        @if ($presence['present'] > 0)
                            <span class="ukc-pill ukc-pill--ok" style="margin-left:6px;">{{ $presence['present'] }}</span>
                        @endif
                    </a>
                @else
                    <button type="button" class="ukc-btn ukc-btn--outline ukc-btn--sm" disabled style="opacity:.5;cursor:not-allowed;"
                            title="{{ __('Le pointage ouvrira au début de la séance.') }}">
                        <x-ukc.icon name="lock" :size="16" /> {{ __('Présence') }}
                    </button>
                @endif
                {{-- « Communications programmées » : toujours consultable (on peut voir le
                     programme de sa séance avant qu'elle commence) ; la VALIDATION du
                     passage, elle, n'ouvre qu'une fois la séance commencée (sur la page). --}}
                @if ($comms)
                    <a href="{{ route('account.passage.edit', $session) }}" class="ukc-btn ukc-btn--outline ukc-btn--sm">
                        <x-ukc.icon name="mic" :size="16" /> {{ __('Communications programmées') }}
                        <span class="ukc-pill ukc-pill--muted" style="margin-left:6px;">{{ count($comms) }}</span>
                    </a>
                @endif
            </div>
        </div>
    @empty
        <div class="ukc-empty">
            <span class="ico"><x-ukc.icon name="mic" :size="28" /></span>
            <h3>{{ __('Aucune séance à modérer') }}</h3>
            <p>{{ __('Les séances dont vous êtes le modérateur apparaîtront ici, avec les outils de modération en direct.') }}</p>
        </div>
    @endforelse
</div>
</x-layouts.participant>
