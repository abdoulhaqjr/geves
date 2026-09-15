<x-layouts.participant :title="__('Mes séances à rapporter')" :subtitle="__('Espace rapporteur · rapports de séance')">
<div class="account-reports">
    @forelse ($sessions as $session)
        @php($report = $session->report)
        <div class="ukc-card" style="margin-bottom:14px;display:flex;align-items:flex-start;justify-content:space-between;gap:12px;flex-wrap:wrap;">
            <div>
                <div style="font-size:12px;color:var(--ukc-muted);text-transform:uppercase;letter-spacing:.04em;">
                    {{ $session->edition?->colloque?->name ?: __('Colloque') }} · {{ $session->edition?->title ?: $session->edition?->year }}
                </div>
                <h3 style="margin:2px 0 4px;">{{ $session->label }}</h3>
                <div style="font-size:13px;color:var(--ukc-muted);">
                    @if ($session->starts_at){{ $session->starts_at->translatedFormat('l j F · H:i') }}@endif
                    @if ($session->room) · {{ $session->room }}@endif
                </div>
            </div>
            <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap;">
                @if ($report && $report->isSubmitted())
                    <span class="ukc-pill ukc-pill--ok">{{ __('Rapport soumis') }}</span>
                @elseif ($report)
                    <span class="ukc-pill ukc-pill--warn">{{ __('Brouillon') }}</span>
                @else
                    <span class="ukc-pill ukc-pill--muted">{{ __('À rédiger') }}</span>
                @endif
                <a href="{{ route('account.reports.edit', $session) }}" class="ukc-btn ukc-btn--blue ukc-btn--sm">
                    <x-ukc.icon name="pen" :size="16" /> {{ $report ? __('Modifier le rapport') : __('Rédiger le rapport') }}
                </a>
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
