<x-layouts.participant :title="__('Rapport de séance')" :subtitle="$session->label">
<div class="account-report-edit" style="display:grid;grid-template-columns:minmax(0,1fr);gap:16px;">
    <div style="display:flex;gap:8px;flex-wrap:wrap;align-items:center;">
        <a href="{{ route('account.reports') }}" class="ukc-btn ukc-btn--ghost ukc-btn--sm">
            <x-ukc.icon name="chevron" :size="16" /> {{ __('Mes séances à rapporter') }}
        </a>
        @if ($report->exists && $report->isSubmitted())
            <span class="ukc-pill ukc-pill--ok">{{ __('Rapport soumis') }} @if ($report->submitted_at)· {{ $report->submitted_at->translatedFormat('d/m/Y H:i') }}@endif</span>
        @elseif ($report->exists)
            <span class="ukc-pill ukc-pill--warn">{{ __('Brouillon') }}</span>
        @endif
    </div>

    <div class="ukc-card">
        <div style="font-size:12px;color:var(--ukc-muted);text-transform:uppercase;letter-spacing:.04em;">
            {{ $session->edition?->colloque?->name ?: __('Colloque') }} · {{ $session->edition?->title ?: $session->edition?->year }}
        </div>
        <h3 style="margin:2px 0 4px;">{{ $session->label }}</h3>
        <div style="font-size:13px;color:var(--ukc-muted);">
            @if ($session->starts_at){{ $session->starts_at->translatedFormat('l j F · H:i') }}@endif
            @if ($session->room) · {{ $session->room }}@endif
        </div>

        @if ($communications)
            <div style="margin-top:12px;">
                <div style="font-size:12px;font-weight:600;color:var(--ukc-muted);margin-bottom:6px;">{{ __('Communications de la séance') }}</div>
                <ol style="margin:0;padding-left:18px;display:flex;flex-direction:column;gap:4px;font-size:13.5px;">
                    @foreach ($communications as $c)
                        <li><strong>{{ $c['title'] }}</strong> — {{ $c['name'] }}</li>
                    @endforeach
                </ol>
            </div>
        @endif
    </div>

    <form method="POST" action="{{ route('account.reports.update', $session) }}" class="ukc-card" style="display:flex;flex-direction:column;gap:16px;">
        @csrf

        <div class="ukc-field">
            <label for="summary">{{ __('Synthèse de la séance') }}</label>
            <p style="font-size:12.5px;color:var(--ukc-muted);margin:2px 0 6px;">{{ __('Déroulé, temps forts des présentations, échanges et questions du public.') }}</p>
            <textarea id="summary" name="summary" rows="10">{{ old('summary', $report->summary) }}</textarea>
            @error('summary') <span class="error">{{ $message }}</span> @enderror
        </div>

        <div class="ukc-field">
            <label for="highlights">{{ __('Points saillants & recommandations') }}</label>
            <p style="font-size:12.5px;color:var(--ukc-muted);margin:2px 0 6px;">{{ __('Conclusions, décisions et recommandations à transmettre au comité (optionnel).') }}</p>
            <textarea id="highlights" name="highlights" rows="5">{{ old('highlights', $report->highlights) }}</textarea>
            @error('highlights') <span class="error">{{ $message }}</span> @enderror
        </div>

        <div class="ukc-field" style="max-width:240px;">
            <label for="attendance">{{ __('Affluence estimée') }}</label>
            <input id="attendance" name="attendance" type="number" min="0"
                   value="{{ old('attendance', $report->attendance) }}" placeholder="{{ __('Nombre de participants') }}">
            @error('attendance') <span class="error">{{ $message }}</span> @enderror
        </div>

        <div style="display:flex;gap:8px;flex-wrap:wrap;">
            <button type="submit" name="action" value="submit" class="ukc-btn ukc-btn--green">
                <x-ukc.icon name="check" :size="16" /> {{ __('Soumettre le rapport') }}
            </button>
            <button type="submit" name="action" value="draft" class="ukc-btn ukc-btn--outline">
                {{ __('Enregistrer le brouillon') }}
            </button>
        </div>
    </form>
</div>
</x-layouts.participant>
