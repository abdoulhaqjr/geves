<x-layouts.participant :title="__('Mes séances à rapporter')" :subtitle="__('Espace rapporteur - rapports de séance')">
<div class="account-reports">
    @forelse ($sessions as $session)
        @php($report = $session->report)
        @php($started = $session->hasStarted())
        @php($validated = $report && $report->isValidated())
        <div class="ukc-card" style="margin-bottom:14px;display:flex;align-items:flex-start;justify-content:space-between;gap:12px;flex-wrap:wrap;">
            <div>
                <div style="font-size:12px;color:var(--ukc-muted);text-transform:uppercase;letter-spacing:.04em;">
                    {{ $session->event?->series?->name ?: __('Colloque') }} - {{ $session->event?->title ?: $session->event?->year }}
                </div>
                <h3 style="margin:2px 0 4px;">{{ $session->label }}</h3>
                <div style="font-size:13px;color:var(--ukc-muted);">
                    @if ($session->starts_at){{ $session->starts_at->translatedFormat('l j F - H:i') }}@endif
                    @if ($session->room) - {{ $session->room }}@endif
                </div>
                @unless ($started)
                    <div style="font-size:12.5px;color:var(--ukc-muted);margin-top:6px;display:inline-flex;align-items:center;gap:5px;">
                        <x-ukc.icon name="clock" :size="14" /> {{ __('La rédaction ouvrira au début de la séance.') }}
                    </div>
                @endunless
            </div>
            <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap;">
                @if ($validated)
                    <span class="ukc-pill ukc-pill--ok">{{ __('Validé par le secrétariat') }}</span>
                @elseif ($report && $report->isSubmitted())
                    <span class="ukc-pill ukc-pill--ok">{{ __('Rapport soumis') }}</span>
                @elseif ($report)
                    <span class="ukc-pill ukc-pill--warn">{{ __('Brouillon') }}</span>
                @elseif ($started)
                    <span class="ukc-pill ukc-pill--warn">{{ __('À rédiger') }}</span>
                @else
                    <span class="ukc-pill ukc-pill--muted">{{ __('À venir') }}</span>
                @endif

                {{-- Le rapporteur pointe aussi la présence : dans beaucoup de
                     séances, c'est lui qui tient la liste pendant que le
                     modérateur anime. --}}
                @if ($started)
                    <a href="{{ route('account.attendance.edit', $session) }}" class="ukc-btn ukc-btn--outline ukc-btn--sm">
                        <x-ukc.icon name="users" :size="16" /> {{ __('Présence') }}
                    </a>
                @endif

                @if ($validated)
                    <a href="{{ route('account.reports.edit', $session) }}" class="ukc-btn ukc-btn--outline ukc-btn--sm">
                        <x-ukc.icon name="eye" :size="16" /> {{ __('Consulter') }}
                    </a>
                @elseif ($started)
                    <a href="{{ route('account.reports.edit', $session) }}" class="ukc-btn ukc-btn--blue ukc-btn--sm">
                        <x-ukc.icon name="pen" :size="16" /> {{ $report ? __('Modifier le rapport') : __('Rédiger le rapport') }}
                    </a>
                @else
                    <button type="button" class="ukc-btn ukc-btn--blue ukc-btn--sm" disabled style="opacity:.5;cursor:not-allowed;">
                        <x-ukc.icon name="lock" :size="16" /> {{ __('Rédiger le rapport') }}
                    </button>
                @endif
            </div>
        </div>
    @empty
        <div class="ukc-empty">
            <span class="ico"><x-ukc.icon name="sheet" :size="28" /></span>
            <h3>{{ __('Aucune séance à rapporter') }}</h3>
            <p>{{ __('Les séances dont vous êtes le rapporteur apparaîtront ici, avec le formulaire de rapport.') }}</p>
        </div>
    @endforelse
</div>
</x-layouts.participant>
