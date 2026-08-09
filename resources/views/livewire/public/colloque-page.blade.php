<div>
    @php
        $e = $edition;
        $dateLabel = null;
        if ($e->date_start) {
            $dateLabel = $e->date_start->translatedFormat('d F Y');
            if ($e->date_end && $e->date_end->ne($e->date_start)) {
                $dateLabel = $e->date_start->translatedFormat('d') . '–' . $e->date_end->translatedFormat('d F Y');
            }
        }
    @endphp

    {{-- Barre de navigation --}}
    <header style="position:sticky;top:0;z-index:30;background:#fff;border-bottom:1px solid var(--ukc-border);">
        <div style="height:3px;display:flex;">
            <span style="flex:1;background:var(--ukc-blue);"></span>
            <span style="flex:1;background:var(--ukc-gold);"></span>
            <span style="flex:1;background:var(--ukc-green);"></span>
        </div>
        <div style="max-width:1100px;margin:0 auto;display:flex;align-items:center;justify-content:space-between;
                    gap:16px;padding:12px clamp(16px,4vw,32px);">
            <div>
                <div style="font-weight:900;color:var(--ukc-blue);font-size:1rem;line-height:1.2;">{{ $e->colloque?->name }}</div>
                <div style="font-size:.78rem;color:var(--ukc-muted);font-weight:600;">Université de Kara</div>
            </div>
            <nav style="display:flex;gap:8px;align-items:center;">
                <a href="#presentation" class="ukc-btn ukc-btn--ghost ukc-btn--sm">Présentation</a>
                <a href="#axes" class="ukc-btn ukc-btn--ghost ukc-btn--sm">Axes</a>
                <a href="{{ route('account.login') }}" class="ukc-btn ukc-btn--primary ukc-btn--sm">Espace participant</a>
            </nav>
        </div>
    </header>

    {{-- Hero --}}
    <section style="position:relative;color:#fff;overflow:hidden;
                    background:linear-gradient(135deg, var(--ukc-blue-dark) 0%, var(--ukc-blue) 55%, #2b52c9 100%);">
        <div style="max-width:1100px;margin:0 auto;padding:clamp(48px,7vw,88px) clamp(16px,4vw,32px);">
            @if ($e->edition_label)
                <span class="ukc-pill" style="background:rgba(255,255,255,.16);color:#fff;margin-bottom:18px;">
                    {{ $e->edition_label }}
                </span>
            @endif
            <h1 style="font-size:clamp(1.9rem,5vw,3rem);font-weight:900;line-height:1.12;letter-spacing:-.02em;max-width:18ch;">
                {{ $e->title }}
            </h1>
            @if ($e->subtitle)
                <p style="font-size:clamp(1rem,2.2vw,1.25rem);margin-top:14px;max-width:60ch;opacity:.95;">{{ $e->subtitle }}</p>
            @endif

            <div style="display:flex;flex-wrap:wrap;gap:12px;margin-top:24px;">
                @if ($dateLabel)
                    <span class="ukc-pill" style="background:rgba(255,255,255,.16);color:#fff;font-size:13px;padding:8px 14px;">
                        <x-ukc.icon name="calendar" :size="16" /> {{ $dateLabel }}
                    </span>
                @endif
                @if ($e->location)
                    <span class="ukc-pill" style="background:rgba(255,255,255,.16);color:#fff;font-size:13px;padding:8px 14px;">
                        <x-ukc.icon name="pin" :size="16" /> {{ $e->location }}
                    </span>
                @endif
            </div>

            <div style="display:flex;flex-wrap:wrap;gap:12px;margin-top:30px;">
                @if ($e->submission_open)
                    <a href="{{ route('account.submissions.create', $e->slug) }}" class="ukc-btn ukc-btn--gold">
                        <x-ukc.icon name="upload" :size="18" /> Soumettre une communication
                    </a>
                @endif
                <a href="{{ route('account.login') }}" class="ukc-btn ukc-btn--outline"
                   style="background:transparent;color:#fff;border-color:rgba(255,255,255,.5);">
                    <x-ukc.icon name="user" :size="18" /> S’inscrire pour assister
                </a>
            </div>

            @if ($e->submission_open && $e->submission_deadline)
                <p style="margin-top:18px;font-size:.9rem;opacity:.9;">
                    <x-ukc.icon name="clock" :size="15" /> Clôture des soumissions le
                    {{ $e->submission_deadline->translatedFormat('d F Y') }}.
                </p>
            @endif
        </div>
    </section>

    <div style="background:var(--ukc-bg);">
        <div style="max-width:1000px;margin:0 auto;padding:clamp(32px,5vw,56px) clamp(16px,4vw,32px);display:flex;flex-direction:column;gap:28px;">

            {{-- Présentation --}}
            @if ($e->theme || $intro)
                <section id="presentation" class="ukc-card">
                    <h2 style="font-size:1.3rem;font-weight:800;color:var(--ukc-blue);">Présentation</h2>
                    @if ($e->theme)
                        <p style="font-weight:700;font-size:1.05rem;margin:6px 0 12px;">{{ $e->theme }}</p>
                    @endif
                    @if ($intro)
                        <p style="color:var(--ukc-muted);line-height:1.7;white-space:pre-line;">{{ $intro }}</p>
                    @endif
                </section>
            @endif

            {{-- Axes thématiques --}}
            @if (count($tracks))
                <section id="axes" class="ukc-card">
                    <h2 style="font-size:1.3rem;font-weight:800;color:var(--ukc-blue);margin-bottom:16px;">Axes thématiques</h2>
                    <div class="ukc-grid-2" style="grid-template-columns:repeat(auto-fit,minmax(240px,1fr));">
                        @foreach ($tracks as $i => $track)
                            <div style="display:flex;gap:12px;align-items:flex-start;padding:14px 16px;
                                        background:var(--ukc-bg);border:1px solid var(--ukc-border);border-radius:12px;">
                                <span style="flex-shrink:0;width:28px;height:28px;border-radius:8px;display:flex;
                                             align-items:center;justify-content:center;background:var(--ukc-blue);color:#fff;
                                             font-weight:800;font-size:13px;">{{ $i + 1 }}</span>
                                <span style="font-weight:600;font-size:.95rem;">{{ $track }}</span>
                            </div>
                        @endforeach
                    </div>
                </section>
            @endif

            {{-- Frais d'inscription --}}
            @if (count($fees))
                <section id="tarifs" class="ukc-card">
                    <h2 style="font-size:1.3rem;font-weight:800;color:var(--ukc-blue);margin-bottom:16px;">Frais d’inscription</h2>
                    <div class="ukc-grid-3" style="grid-template-columns:repeat(auto-fit,minmax(180px,1fr));">
                        @foreach ($fees as $fee)
                            <div style="text-align:center;padding:20px 16px;border:1px solid var(--ukc-border);
                                        border-radius:14px;background:#fff;">
                                <div style="font-size:.85rem;font-weight:700;color:var(--ukc-muted);text-transform:uppercase;
                                            letter-spacing:.04em;">{{ $fee['label'] }}</div>
                                <div style="font-size:1.6rem;font-weight:900;color:var(--ukc-blue);margin-top:8px;">
                                    {{ number_format((float) ($fee['amount'] ?? 0), 0, ',', ' ') }}
                                    <span style="font-size:.9rem;font-weight:700;">FCFA</span>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </section>
            @endif

            {{-- CTA final --}}
            <section class="ukc-card" style="text-align:center;background:
                     linear-gradient(135deg, rgba(34,62,164,.06), rgba(0,153,51,.06));">
                <h2 style="font-size:1.4rem;font-weight:900;">Prêt à participer ?</h2>
                <p style="color:var(--ukc-muted);margin:8px 0 20px;">Créez votre compte pour soumettre une communication ou vous inscrire.</p>
                <div style="display:flex;gap:12px;justify-content:center;flex-wrap:wrap;">
                    <a href="{{ route('account.register') }}" class="ukc-btn ukc-btn--primary">
                        <x-ukc.icon name="user-plus" :size="18" /> Créer mon compte
                    </a>
                    <a href="{{ route('account.login') }}" class="ukc-btn ukc-btn--outline">
                        <x-ukc.icon name="login" :size="18" /> Se connecter
                    </a>
                </div>
            </section>
        </div>
    </div>

    <footer style="padding:24px;text-align:center;font-size:.82rem;color:var(--ukc-light);background:var(--ukc-bg);">
        © {{ date('Y') }} {{ $e->colloque?->name }} — Université de Kara
    </footer>
</div>
