<x-layouts.participant :title="__('Formations')">
    @php
        $me = auth('account')->user();
        $currency = config('payments.currency', 'XOF');
        $profileMissing = $me?->missingProfileFields() ?? [];
        $metaStyle = 'display:inline-flex;align-items:center;gap:6px;font-size:13px;color:var(--ukc-muted);';
    @endphp

    <style>
        /* Cartes larges : visuel à gauche, contenu à droite ; empilées sur mobile. */
        .fcard { display:grid; grid-template-columns:minmax(0,clamp(340px,34%,520px)) minmax(0,1fr); background:#fff;
                 border:1px solid var(--ukc-border,#e5e7eb); border-radius:16px; overflow:hidden;
                 box-shadow:0 1px 2px rgba(16,24,40,.04); margin-bottom:20px; }
        .fcard__media { position:relative; min-height:240px; background:linear-gradient(135deg,var(--ukc-blue,#223ea4),var(--ukc-green,#009933)); }
        .fcard__body { padding:22px 24px; display:flex; flex-direction:column; gap:14px; min-width:0; }
        .fcard__foot { border-top:1px solid var(--ukc-border,#eef0f4); margin:0 -24px -22px; padding:14px 24px;
                       background:#fcfcfd; display:flex; flex-wrap:wrap; gap:12px; align-items:center; justify-content:space-between; }
        @media (max-width: 860px) {
            .fcard { grid-template-columns:minmax(0,1fr); }
            .fcard__media { min-height:0; aspect-ratio:16/6; }
        }
    </style>

    <div class="admin-page account-formations">
        <div class="ukc-page-head" style="margin-bottom:20px;">
            <h1 style="margin:0;">{{ __('Formations') }}</h1>
            <p style="color:var(--ukc-muted);margin:4px 0 0;font-size:14px;">
                {{ __('Formations proposées par') }}
                <strong>{{ $edition->colloque?->name ?: $edition->title }}</strong>@if ($edition->year) · {{ $edition->year }}@endif.
            </p>
        </div>

        @if (session('ukc_status'))<div class="ukc-alert ukc-alert--ok" style="margin-bottom:14px;">{{ session('ukc_status') }}</div>@endif
        @if (session('ukc_error'))<div class="ukc-alert ukc-alert--err" style="margin-bottom:14px;">{{ session('ukc_error') }}</div>@endif
        @if (session('pay_instructions'))<div class="ukc-alert ukc-alert--info" style="margin-bottom:14px;">{{ session('pay_instructions') }}</div>@endif
        @error('pay')<div class="ukc-alert ukc-alert--err" style="margin-bottom:14px;">{{ $message }}</div>@enderror

        @if ($profileMissing && $formations->contains(fn ($f) => ! $f->isFree()))
            <div class="ukc-alert ukc-alert--warn" style="margin-bottom:16px;display:flex;flex-wrap:wrap;gap:10px;align-items:center;justify-content:space-between;">
                <span>
                    {{ __('Complétez votre profil pour pouvoir régler une formation') }}
                    ({{ implode(', ', $profileMissing) }}).
                </span>
                <a href="{{ route('account.profile') }}" class="ukc-btn ukc-btn--outline ukc-btn--sm">
                    <x-ukc.icon name="user" :size="14" /> {{ __('Compléter mon profil') }}
                </a>
            </div>
        @endif

        @forelse ($formations as $f)
            @php
                $enr = $enrollments->get($f->id);
                $isTrainer = $f->isTrainer($me);
                $isPaid = $enr && $enr->status === 'paid';
                $isEnrolled = $enr && ($isPaid || $f->isFree());
                $canJoin = $f->isLive() && $f->canJoin($me);
                $needsProfile = ! $f->isFree() && $profileMissing !== [];
            @endphp
            <article class="fcard">
                {{-- Visuel --}}
                <div class="fcard__media">
                    @if ($f->flyerUrl())
                        <img src="{{ $f->flyerUrl() }}" alt="" style="position:absolute;inset:0;width:100%;height:100%;object-fit:cover;display:block;">
                    @else
                        <span style="position:absolute;inset:0;display:flex;align-items:center;justify-content:center;color:rgba(255,255,255,.85);">
                            <x-ukc.icon name="book" :size="40" />
                        </span>
                    @endif
                </div>

                {{-- Contenu --}}
                <div class="fcard__body">
                    <div style="display:flex;gap:16px;align-items:flex-start;justify-content:space-between;flex-wrap:wrap;">
                        <div style="min-width:0;flex:1;">
                            <h2 style="margin:0;font-size:18px;line-height:1.35;">{{ $f->title }}</h2>
                            @if ($f->theme)
                                <div style="font-size:13.5px;color:var(--ukc-muted);margin-top:3px;">{{ $f->theme }}</div>
                            @endif
                        </div>
                        {{-- Tarif et état, à droite du titre (plus sur la bannière) --}}
                        <div style="text-align:right;flex-shrink:0;">
                            <div style="font-size:20px;font-weight:700;line-height:1.2;color:{{ $f->isFree() ? 'var(--ukc-green,#009933)' : 'var(--ukc-blue,#223ea4)' }};">
                                {{ $f->isFree() ? __('Gratuite') : number_format((float) $f->fee, 0, ',', ' ') }}
                            </div>
                            @unless ($f->isFree())
                                <div style="font-size:11.5px;color:var(--ukc-light);letter-spacing:.04em;">{{ $currency }}</div>
                            @endunless
                            @if ($f->isLive())
                                <span class="ukc-pill ukc-pill--err" style="margin-top:6px;display:inline-flex;align-items:center;gap:5px;">
                                    <span style="width:7px;height:7px;border-radius:50%;background:currentColor;"></span> {{ __('EN DIRECT') }}
                                </span>
                            @endif
                        </div>
                    </div>

                    @if ($f->starts_at || $f->location || $f->room)
                        <div style="display:flex;flex-wrap:wrap;gap:8px 20px;">
                            @if ($f->starts_at)
                                <span style="{{ $metaStyle }}">
                                    <x-ukc.icon name="calendar" :size="15" />
                                    {{ $f->starts_at->translatedFormat('d M Y, H:i') }}@if ($f->ends_at) - {{ $f->ends_at->translatedFormat('H:i') }}@endif
                                </span>
                            @endif
                            @if ($f->location)
                                <span style="{{ $metaStyle }}"><x-ukc.icon name="pin" :size="15" /> {{ $f->location }}</span>
                            @endif
                            @if ($f->room)
                                <span style="{{ $metaStyle }}"><x-ukc.icon name="grid" :size="15" /> {{ $f->room }}</span>
                            @endif
                        </div>
                    @endif

                    @if ($f->objective)
                        <p style="font-size:14px;line-height:1.6;margin:0;max-width:75ch;">{{ $f->objective }}</p>
                    @endif

                    @if ($f->trainers->isNotEmpty())
                        <div>
                            <div style="font-size:11.5px;font-weight:600;text-transform:uppercase;letter-spacing:.04em;color:var(--ukc-light);margin-bottom:8px;">{{ __('Formateur(s)') }}</div>
                            <div style="display:flex;flex-wrap:wrap;gap:10px;">
                                @foreach ($f->trainers as $t)
                                    <span style="display:inline-flex;align-items:center;gap:8px;background:var(--ukc-bg,#f6f7fb);border-radius:999px;padding:4px 14px 4px 4px;">
                                        <span style="width:28px;height:28px;border-radius:50%;overflow:hidden;display:inline-flex;align-items:center;justify-content:center;background:linear-gradient(135deg,var(--ukc-blue,#223ea4),var(--ukc-green,#009933));color:#fff;font-size:11.5px;font-weight:700;flex-shrink:0;">
                                            @if ($t->photoUrl())
                                                <img src="{{ $t->photoUrl() }}" alt="" style="width:100%;height:100%;object-fit:cover;">
                                            @else
                                                {{ $t->initials() }}
                                            @endif
                                        </span>
                                        <span style="font-size:13px;">{{ trim(($t->title ? $t->title.' ' : '').$t->name) }}</span>
                                    </span>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    {{-- Pied : statut + action --}}
                    <div class="fcard__foot" style="margin-top:auto;">
                        <span style="font-size:13px;color:var(--ukc-muted);display:inline-flex;align-items:center;gap:6px;">
                            @if ($isTrainer)
                                <span class="ukc-pill ukc-pill--info">{{ __('Vous animez cette formation') }}</span>
                            @elseif ($isPaid)
                                <x-ukc.icon name="check-circle" :size="15" /> {{ __('Inscription réglée') }}
                            @elseif ($isEnrolled)
                                <x-ukc.icon name="check-circle" :size="15" /> {{ __('Inscription confirmée') }}
                            @elseif ($enr)
                                <span style="color:#b45309;font-weight:600;">{{ __('Frais à régler') }}</span>
                                @if ($f->isLive())
                                    <span style="color:var(--ukc-light);">· {{ __('la formation est réservée aux inscrits ayant réglé') }}</span>
                                @endif
                            @else
                                {{ $f->isFree() ? __('Inscription libre') : __('Inscription avec frais') }}
                            @endif
                        </span>

                        <span style="display:flex;gap:10px;align-items:center;flex-wrap:wrap;">
                            @if ($canJoin)
                                <a href="{{ route('account.formations.visio', $f) }}" class="ukc-btn ukc-btn--green">
                                    <x-ukc.icon name="video" :size="15" /> {{ __('Rejoindre la visio') }}
                                </a>
                            @endif

                            @if ($isTrainer || $isEnrolled)
                                {{-- Formateur ou inscription acquise : aucune action de paiement --}}
                            @elseif ($needsProfile)
                                <a href="{{ route('account.profile') }}" class="ukc-btn ukc-btn--outline">
                                    <x-ukc.icon name="user" :size="15" /> {{ __('Compléter mon profil') }}
                                </a>
                            @elseif ($enr)
                                @include('account.formations._pay', ['f' => $f, 'label' => __('Régler'), 'gateways' => $gateways])
                            @elseif ($f->isFree())
                                <form method="POST" action="{{ route('account.formations.enroll', $f) }}" style="margin:0;">
                                    @csrf
                                    <button type="submit" class="ukc-btn ukc-btn--blue"><x-ukc.icon name="check" :size="15" /> {{ __('S’inscrire') }}</button>
                                </form>
                            @else
                                @include('account.formations._pay', ['f' => $f, 'label' => __('S’inscrire'), 'gateways' => $gateways])
                            @endif
                        </span>
                    </div>
                </div>
            </article>
        @empty
            <div class="ukc-empty">
                <h3>{{ __('Aucune formation disponible') }}</h3>
                <p>{{ __('Aucune formation n’est ouverte à l’inscription pour cet événement.') }}</p>
            </div>
        @endforelse
    </div>
</x-layouts.participant>
