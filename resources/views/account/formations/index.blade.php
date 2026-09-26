<x-layouts.participant :title="__('Formations')">
    <div class="admin-page account-formations">
        <div class="ukc-page-head" style="margin-bottom:16px;">
            <h1 style="margin:0;">{{ __('Formations') }}</h1>
            <p style="color:var(--ukc-muted);margin:4px 0 0;">{{ __('Inscrivez-vous aux formations proposées par les événements.') }}</p>
        </div>

        @if (session('ukc_status'))<div class="ukc-alert ukc-alert--ok" style="margin-bottom:14px;">{{ session('ukc_status') }}</div>@endif
        @if (session('ukc_error'))<div class="ukc-alert ukc-alert--err" style="margin-bottom:14px;">{{ session('ukc_error') }}</div>@endif
        @if (session('pay_instructions'))<div class="ukc-alert ukc-alert--info" style="margin-bottom:14px;">{{ session('pay_instructions') }}</div>@endif
        @error('pay')<div class="ukc-alert ukc-alert--err" style="margin-bottom:14px;">{{ $message }}</div>@enderror

        @forelse ($formations as $f)
            @php($enr = $enrollments->get($f->id))
            <div class="ukc-card" style="margin-bottom:14px;">
                @if ($f->flyerUrl())
                    <img src="{{ $f->flyerUrl() }}" alt="{{ $f->title }}" style="width:100%;height:140px;object-fit:cover;border-radius:8px;margin-bottom:12px;">
                @endif
                <div style="display:flex;justify-content:space-between;gap:12px;flex-wrap:wrap;align-items:flex-start;">
                    <div style="flex:1;min-width:220px;">
                        <strong style="font-size:16px;">{{ $f->title }}</strong>
                        @if ($f->theme)<div style="color:var(--ukc-muted);font-size:13.5px;">{{ $f->theme }}</div>@endif
                        <div style="font-size:12.5px;color:var(--ukc-light);margin-top:6px;">
                            {{ $f->edition?->colloque?->name ?: $f->edition?->title }}
                            @if ($f->starts_at) · {{ $f->starts_at->format('d/m/Y H:i') }}@endif
                            @if ($f->location) · {{ $f->location }}@endif @if ($f->room) · {{ $f->room }}@endif
                        </div>
                        @if ($f->objective)<p style="font-size:13.5px;margin:10px 0 0;">{{ $f->objective }}</p>@endif
                        @if ($f->trainers->isNotEmpty())
                            <div style="font-size:12.5px;color:var(--ukc-muted);margin-top:8px;">
                                {{ __('Formateur(s)') }} : {{ $f->trainers->pluck('name')->join(', ') }}
                            </div>
                        @endif
                    </div>
                    <div style="text-align:right;min-width:150px;">
                        <div style="margin-bottom:8px;">
                            <span class="ukc-pill {{ $f->isFree() ? 'ukc-pill--ok' : 'ukc-pill--muted' }}">
                                {{ $f->isFree() ? __('Gratuite') : number_format((float) $f->fee, 0, ',', ' ').' '.config('payments.currency', 'XOF') }}
                            </span>
                        </div>

                        @if ($f->isLive())
                            <a href="{{ route('account.formations.visio', $f) }}" class="ukc-btn ukc-btn--green" style="margin-bottom:8px;">
                                <x-ukc.icon name="video" :size="14" /> {{ __('EN DIRECT · Rejoindre') }}
                            </a>
                        @endif

                        @if ($enr && $enr->status === 'paid')
                            <span class="ukc-pill ukc-pill--ok"><x-ukc.icon name="check" :size="12" /> {{ __('Inscrit·e (réglé)') }}</span>
                        @elseif ($enr && $f->isFree())
                            <span class="ukc-pill ukc-pill--ok"><x-ukc.icon name="check" :size="12" /> {{ __('Inscrit·e') }}</span>
                        @elseif ($enr)
                            {{-- Inscription enregistrée, frais à régler --}}
                            <div style="font-size:12px;color:var(--ukc-warn,#b45309);margin-bottom:6px;">{{ __('Inscription à régler') }}</div>
                            @include('account.formations._pay', ['f' => $f, 'label' => __('Régler'), 'gateways' => $gateways])
                        @elseif ($f->isFree())
                            <form method="POST" action="{{ route('account.formations.enroll', $f) }}">
                                @csrf
                                <button type="submit" class="ukc-btn ukc-btn--green"><x-ukc.icon name="check" :size="14" /> {{ __('S’inscrire') }}</button>
                            </form>
                        @else
                            @include('account.formations._pay', ['f' => $f, 'label' => __('S’inscrire et payer'), 'gateways' => $gateways])
                        @endif
                    </div>
                </div>
            </div>
        @empty
            <div class="ukc-empty"><h3>{{ __('Aucune formation disponible') }}</h3><p>{{ __('Aucune formation n’est ouverte à l’inscription pour le moment.') }}</p></div>
        @endforelse
    </div>
</x-layouts.participant>
