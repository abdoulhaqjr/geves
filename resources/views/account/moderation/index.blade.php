<x-layouts.participant :title="__('Mes séances à modérer')" :subtitle="__('Espace modérateur · Q&A et sondages en direct')">
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
                        {{ $session->event?->series?->name ?: __('Colloque') }} · {{ $session->event?->title ?: $session->event?->year }}
                    </div>
                    <h3 style="margin:2px 0 4px;">{{ $session->label }}</h3>
                    <div style="font-size:13px;color:var(--ukc-muted);">
                        @if ($session->starts_at){{ $session->starts_at->translatedFormat('l j F · H:i') }}@endif
                        @if ($session->ends_at) – {{ $session->ends_at->translatedFormat('H:i') }}@endif
                        @if ($session->room) · {{ $session->room }}@endif
                    </div>
                </div>
                @if ($live)
                    <span class="ukc-pill ukc-pill--err" style="align-self:center;">● {{ __('En direct') }}</span>
                @elseif ($session->hasMeeting())
                    <span class="ukc-pill ukc-pill--muted" style="align-self:center;">{{ __('Visio programmée') }}</span>
                @endif
            </div>

            @if ($comms)
                <div style="margin-top:12px;">
                    <div style="font-size:12px;font-weight:600;color:var(--ukc-muted);margin-bottom:6px;">{{ __('Communications programmées') }}</div>
                    {{-- ⚠️ Le passage se constate ICI aussi, et pas seulement dans la visio :
                         une séance tenue en salle n'a pas de visio, et c'est pourtant là que
                         les communications ont lieu. Même porte serveur, même geste. --}}
                    <ol style="margin:0;padding-left:18px;display:flex;flex-direction:column;gap:8px;font-size:13.5px;">
                        @foreach ($comms as $c)
                            <li>
                                <strong>{{ $c['title'] }}</strong> - {{ $c['name'] }}
                                @if ($c['done'])
                                    <span class="ukc-pill ukc-pill--ok" style="margin-left:6px;">{{ __('Passage validé') }}</span>
                                @elseif (($passageOuvert[$session->id] ?? false) && $c['slotId'])
                                    <form method="POST" action="{{ route('session.passage', [$session, $c['slotId']]) }}"
                                          style="display:inline;margin-left:6px;"
                                          onsubmit="return confirm(@js(($passageEmet[$session->id] ?? false)
                                              ? __('Constater le passage de cette communication ? Ses auteurs recevront aussitôt leur attestation, et ce constat ne se retire pas.')
                                              : __('Constater le passage de cette communication ? Ce constat ne se retire pas.')));">
                                        @csrf
                                        <button type="submit" class="ukc-btn ukc-btn--outline ukc-btn--sm">
                                            <x-ukc.icon name="check" :size="14" /> {{ __('Valider le passage') }}
                                        </button>
                                    </form>
                                @endif
                            </li>
                        @endforeach
                    </ol>
                </div>
            @endif

            <div style="display:flex;flex-wrap:wrap;gap:8px;margin-top:14px;">
                <a href="{{ route('session.engagement', $session) }}" class="ukc-btn ukc-btn--blue ukc-btn--sm">
                    <x-ukc.icon name="mic" :size="16" /> {{ __('Espace d’échange (Q&A / sondages)') }}
                </a>
                @if ($session->hasMeeting())
                    <a href="{{ route('session.meeting', $session) }}" class="ukc-btn ukc-btn--outline ukc-btn--sm">
                        <x-ukc.icon name="video" :size="16" /> {{ __('Rejoindre la visio') }}
                    </a>
                @endif
                {{-- Présence : ouverte au début de la séance, comme le rapport -
                     on ne pointe pas une salle qui n'est pas encore entrée. --}}
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
