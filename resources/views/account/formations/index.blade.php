<x-layouts.participant :title="__('Formations')">
    @php
        $currency = config('payments.currency', 'XOF');
        $cardStyle = 'background:#fff;border:1px solid var(--ukc-border,#e5e7eb);border-radius:14px;overflow:hidden;display:flex;flex-direction:column;box-shadow:0 1px 2px rgba(16,24,40,.04);';
        $metaStyle = 'display:inline-flex;align-items:center;gap:5px;font-size:12.5px;color:var(--ukc-muted);';
    @endphp

    <div class="admin-page account-formations">
        <div class="ukc-page-head" style="margin-bottom:18px;">
            <h1 style="margin:0;">{{ __('Formations') }}</h1>
            <p style="color:var(--ukc-muted);margin:4px 0 0;font-size:13.5px;">
                {{ __('Formations proposées par') }}
                <strong>{{ $edition->colloque?->name ?: $edition->title }}</strong>@if ($edition->year) · {{ $edition->year }}@endif.
            </p>
        </div>

        @if (session('ukc_status'))<div class="ukc-alert ukc-alert--ok" style="margin-bottom:14px;">{{ session('ukc_status') }}</div>@endif
        @if (session('ukc_error'))<div class="ukc-alert ukc-alert--err" style="margin-bottom:14px;">{{ session('ukc_error') }}</div>@endif
        @if (session('pay_instructions'))<div class="ukc-alert ukc-alert--info" style="margin-bottom:14px;">{{ session('pay_instructions') }}</div>@endif
        @error('pay')<div class="ukc-alert ukc-alert--err" style="margin-bottom:14px;">{{ $message }}</div>@enderror

        <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(min(100%,360px),1fr));gap:18px;align-items:start;">
            @forelse ($formations as $f)
                @php
                    $enr = $enrollments->get($f->id);
                    $isPaid = $enr && $enr->status === 'paid';
                    $isEnrolled = $enr && ($isPaid || $f->isFree());
                @endphp
                <article style="{{ $cardStyle }}">
                    {{-- Visuel : bannière (ratio stable) ou bandeau de repli à la charte --}}
                    <div style="position:relative;aspect-ratio:16/6;background:linear-gradient(135deg,var(--ukc-blue,#223ea4),var(--ukc-green,#009933));">
                        @if ($f->flyerUrl())
                            <img src="{{ $f->flyerUrl() }}" alt="" style="position:absolute;inset:0;width:100%;height:100%;object-fit:cover;display:block;">
                        @else
                            <span style="position:absolute;inset:0;display:flex;align-items:center;justify-content:center;color:rgba(255,255,255,.85);">
                                <x-ukc.icon name="book" :size="34" />
                            </span>
                        @endif
                        {{-- Pastilles superposées : tarif + direct --}}
                        <div style="position:absolute;top:10px;left:10px;display:flex;gap:6px;flex-wrap:wrap;">
                            <span style="background:rgba(255,255,255,.95);border-radius:999px;padding:4px 10px;font-size:12px;font-weight:700;color:{{ $f->isFree() ? 'var(--ukc-green,#009933)' : 'var(--ukc-blue,#223ea4)' }};box-shadow:0 1px 3px rgba(0,0,0,.18);">
                                {{ $f->isFree() ? __('Gratuite') : number_format((float) $f->fee, 0, ',', ' ').' '.$currency }}
                            </span>
                            @if ($f->isLive())
                                <span style="background:#e11d48;color:#fff;border-radius:999px;padding:4px 10px;font-size:12px;font-weight:700;display:inline-flex;align-items:center;gap:5px;box-shadow:0 1px 3px rgba(0,0,0,.18);">
                                    <span style="width:7px;height:7px;border-radius:50%;background:currentColor;"></span> {{ __('EN DIRECT') }}
                                </span>
                            @endif
                        </div>
                        @if ($isEnrolled)
                            <span style="position:absolute;top:10px;right:10px;background:rgba(255,255,255,.95);color:var(--ukc-green,#009933);border-radius:999px;padding:4px 10px;font-size:12px;font-weight:700;display:inline-flex;align-items:center;gap:4px;box-shadow:0 1px 3px rgba(0,0,0,.18);">
                                <x-ukc.icon name="check" :size="12" /> {{ __('Inscrit·e') }}
                            </span>
                        @endif
                    </div>

                    {{-- Corps --}}
                    <div style="padding:16px 16px 12px;flex:1;display:flex;flex-direction:column;gap:10px;">
                        <div>
                            <h2 style="margin:0;font-size:16px;line-height:1.35;">{{ $f->title }}</h2>
                            @if ($f->theme)
                                <div style="font-size:13px;color:var(--ukc-muted);margin-top:2px;">{{ $f->theme }}</div>
                            @endif
                        </div>

                        @if ($f->starts_at || $f->location || $f->room)
                            <div style="display:flex;flex-wrap:wrap;gap:6px 14px;">
                                @if ($f->starts_at)
                                    <span style="{{ $metaStyle }}">
                                        <x-ukc.icon name="calendar" :size="14" />
                                        {{ $f->starts_at->translatedFormat('d M Y, H:i') }}@if ($f->ends_at) - {{ $f->ends_at->translatedFormat('H:i') }}@endif
                                    </span>
                                @endif
                                @if ($f->location)
                                    <span style="{{ $metaStyle }}"><x-ukc.icon name="pin" :size="14" /> {{ $f->location }}</span>
                                @endif
                                @if ($f->room)
                                    <span style="{{ $metaStyle }}"><x-ukc.icon name="grid" :size="14" /> {{ $f->room }}</span>
                                @endif
                            </div>
                        @endif

                        @if ($f->objective)
                            <p style="font-size:13.5px;line-height:1.55;margin:0;color:var(--ukc-text,#1f2937);">{{ $f->objective }}</p>
                        @endif

                        @if ($f->trainers->isNotEmpty())
                            <div style="margin-top:auto;padding-top:6px;">
                                <div style="font-size:11.5px;font-weight:600;text-transform:uppercase;letter-spacing:.03em;color:var(--ukc-light);margin-bottom:6px;">{{ __('Formateur(s)') }}</div>
                                <div style="display:flex;flex-wrap:wrap;gap:10px;">
                                    @foreach ($f->trainers as $t)
                                        <span style="display:inline-flex;align-items:center;gap:7px;background:var(--ukc-bg,#f6f7fb);border-radius:999px;padding:3px 12px 3px 3px;">
                                            <span style="width:26px;height:26px;border-radius:50%;overflow:hidden;display:inline-flex;align-items:center;justify-content:center;background:linear-gradient(135deg,var(--ukc-blue,#223ea4),var(--ukc-green,#009933));color:#fff;font-size:11px;font-weight:700;flex-shrink:0;">
                                                @if ($t->photoUrl())
                                                    <img src="{{ $t->photoUrl() }}" alt="" style="width:100%;height:100%;object-fit:cover;">
                                                @else
                                                    {{ $t->initials() }}
                                                @endif
                                            </span>
                                            <span style="font-size:12.5px;">{{ trim(($t->title ? $t->title.' ' : '').$t->name) }}</span>
                                        </span>
                                    @endforeach
                                </div>
                            </div>
                        @endif
                    </div>

                    {{-- Pied : action principale --}}
                    <div style="border-top:1px solid var(--ukc-border,#eef0f4);padding:12px 16px;background:#fcfcfd;display:flex;flex-wrap:wrap;gap:10px;align-items:center;justify-content:space-between;">
                        <span style="font-size:12.5px;color:var(--ukc-muted);">
                            @if ($isPaid)
                                <x-ukc.icon name="check-circle" :size="14" /> {{ __('Inscription réglée') }}
                            @elseif ($isEnrolled)
                                <x-ukc.icon name="check-circle" :size="14" /> {{ __('Inscription confirmée') }}
                            @elseif ($enr)
                                <span style="color:#b45309;font-weight:600;">{{ __('Frais à régler') }}</span>
                            @else
                                {{ $f->isFree() ? __('Inscription libre') : __('Inscription avec frais') }}
                            @endif
                        </span>

                        <span style="display:flex;gap:8px;align-items:center;flex-wrap:wrap;">
                            @if ($f->isLive() && $f->canJoin(auth('account')->user()))
                                <a href="{{ route('account.formations.visio', $f) }}" class="ukc-btn ukc-btn--green ukc-btn--sm">
                                    <x-ukc.icon name="video" :size="14" /> {{ __('Rejoindre la visio') }}
                                </a>
                            @endif

                            @if ($isEnrolled)
                                {{-- rien de plus : l'inscription est acquise --}}
                            @elseif ($enr)
                                @include('account.formations._pay', ['f' => $f, 'label' => __('Régler'), 'gateways' => $gateways])
                            @elseif ($f->isFree())
                                <form method="POST" action="{{ route('account.formations.enroll', $f) }}" style="margin:0;">
                                    @csrf
                                    <button type="submit" class="ukc-btn ukc-btn--blue ukc-btn--sm"><x-ukc.icon name="check" :size="14" /> {{ __('S’inscrire') }}</button>
                                </form>
                            @else
                                @include('account.formations._pay', ['f' => $f, 'label' => __('S’inscrire'), 'gateways' => $gateways])
                            @endif
                        </span>
                    </div>
                </article>
            @empty
                <div class="ukc-empty" style="grid-column:1/-1;">
                    <h3>{{ __('Aucune formation disponible') }}</h3>
                    <p>{{ __('Aucune formation n’est ouverte à l’inscription pour cet événement.') }}</p>
                </div>
            @endforelse
        </div>
    </div>
</x-layouts.participant>
