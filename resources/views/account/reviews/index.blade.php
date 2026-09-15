<x-layouts.participant :title="__('Mes relectures')" :subtitle="__('Espace relecteur · évaluations confiées')">
<div class="account-reviews">
    @if ($reviewers->isNotEmpty())
        <div class="ukc-card" style="margin-bottom:14px;display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap;">
            <p style="margin:0;font-size:13.5px;color:var(--ukc-muted);">
                {{ __('L’évaluation se fait en aveugle dans le portail relecteur (l’identité des auteurs est masquée).') }}
            </p>
            @foreach ($reviewers as $rp)
                <a href="{{ route('reviewer.portal', $rp->access_token) }}" class="ukc-btn ukc-btn--outline ukc-btn--sm">
                    <x-ukc.icon name="external" :size="16" /> {{ __('Portail de relecture') }}{{ $reviewers->count() > 1 ? ' · '.($rp->edition?->title ?: $rp->edition?->year) : '' }}
                </a>
            @endforeach
        </div>
    @endif

    @forelse ($reviews as $review)
        @php($rp = $reviewers->get($review->reviewer_id))
        <div class="ukc-card" style="margin-bottom:12px;display:flex;align-items:flex-start;justify-content:space-between;gap:12px;flex-wrap:wrap;">
            <div>
                <div style="font-size:12px;color:var(--ukc-muted);text-transform:uppercase;letter-spacing:.04em;">
                    {{ $review->submission?->edition?->title ?: $review->submission?->edition?->year }}
                    @if ($review->submission?->ref_code) · {{ $review->submission->ref_code }}@endif
                </div>
                <h3 style="margin:2px 0 4px;">{{ $review->submission?->title ?: __('Communication') }}</h3>
                @if ($review->isCompleted() && $review->recommendationLabel())
                    @php($noteTxt = $review->score ? ' · '.__('Note').' '.rtrim(rtrim(number_format((float) $review->score, 2, ',', ' '), '0'), ',').'/20' : '')
                    <div style="font-size:13px;color:var(--ukc-muted);">{{ __('Recommandation') }} : {{ $review->recommendationLabel() }}{{ $noteTxt }}</div>
                @endif
            </div>
            <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap;">
                @if ($review->isCompleted())
                    <span class="ukc-pill ukc-pill--ok">{{ __('Évaluée') }}</span>
                @else
                    <span class="ukc-pill ukc-pill--warn">{{ __('À évaluer') }}</span>
                @endif
                @if ($rp)
                    <a href="{{ route('reviewer.evaluate', [$rp->access_token, $review]) }}" class="ukc-btn ukc-btn--blue ukc-btn--sm">
                        <x-ukc.icon name="pen" :size="16" /> {{ $review->isCompleted() ? __('Revoir') : __('Évaluer') }}
                    </a>
                @endif
            </div>
        </div>
    @empty
        <div class="ukc-empty">
            <span class="ico"><x-ukc.icon name="inbox" :size="28" /></span>
            <h3>{{ __('Aucune relecture confiée') }}</h3>
            <p>{{ __('Les communications qui vous sont confiées pour évaluation apparaîtront ici.') }}</p>
        </div>
    @endforelse
</div>
</x-layouts.participant>
