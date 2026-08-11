<x-layouts.app :title="$edition->title">
    <style>
        html { scroll-behavior: smooth; }
        section[id] { scroll-margin-top: 84px; }
        nav [data-spy].spy-active { background: var(--ukc-blue); color: #fff; }
    </style>
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

    {{-- Bannière promo --}}
    @if (! empty($promo['text']))
        <div style="background:var(--ukc-gold);color:var(--ukc-blue-dark);font-weight:700;text-align:center;
                    padding:10px 16px;font-size:.92rem;display:flex;gap:12px;align-items:center;justify-content:center;flex-wrap:wrap;">
            <span>{{ $promo['text'] }}</span>
            @if (! empty($promo['url']))
                <a href="{{ $promo['url'] }}" target="_blank" rel="noopener"
                   style="background:var(--ukc-blue);color:#fff;padding:5px 14px;border-radius:999px;font-size:.85rem;white-space:nowrap;">
                    {{ $promo['cta'] ?: 'En savoir plus' }}
                </a>
            @endif
        </div>
    @endif

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
            <nav x-data="ukcScrollspy()" style="display:flex;gap:8px;align-items:center;flex-wrap:wrap;">
                <a href="#presentation" data-spy class="ukc-btn ukc-btn--ghost ukc-btn--sm">Présentation</a>
                <a href="#axes" data-spy class="ukc-btn ukc-btn--ghost ukc-btn--sm">Axes</a>
                @if ($plenaries->count() || $ateliers->count())
                    <a href="#programme" data-spy class="ukc-btn ukc-btn--ghost ukc-btn--sm">Programme</a>
                @endif
                @if ($committees->count())
                    <a href="#comites" data-spy class="ukc-btn ukc-btn--ghost ukc-btn--sm">Comités</a>
                @endif
                @if (count($faq))
                    <a href="#faq" data-spy class="ukc-btn ukc-btn--ghost ukc-btn--sm">FAQ</a>
                @endif
                @if (! empty($practical['venue']) || ! empty($practical['accommodation']) || ! empty($practical['transport']))
                    <a href="#infos" data-spy class="ukc-btn ukc-btn--ghost ukc-btn--sm">Infos pratiques</a>
                @endif
                <a href="#contact" data-spy class="ukc-btn ukc-btn--ghost ukc-btn--sm">Contact</a>
                <a href="{{ route('account.login') }}" class="ukc-btn ukc-btn--primary ukc-btn--sm">Espace participant</a>
            </nav>
        </div>
    </header>

    {{-- Hero --}}
    <section style="position:relative;color:#fff;overflow:hidden;
                    background:linear-gradient(135deg, var(--ukc-blue-dark) 0%, var(--ukc-blue) 55%, #2b52c9 100%);">
        @if ($heroVideo)
            <video autoplay muted loop playsinline
                   style="position:absolute;inset:0;width:100%;height:100%;object-fit:cover;z-index:0;">
                <source src="{{ $heroVideo }}" type="video/mp4">
            </video>
            <div style="position:absolute;inset:0;z-index:1;background:linear-gradient(135deg, rgba(27,49,130,.86), rgba(43,82,201,.78));"></div>
        @elseif ($heroImage)
            <img src="{{ $heroImage }}" alt="" aria-hidden="true"
                 style="position:absolute;inset:0;width:100%;height:100%;object-fit:cover;z-index:0;">
            <div style="position:absolute;inset:0;z-index:1;background:linear-gradient(135deg, rgba(27,49,130,.88), rgba(43,82,201,.72));"></div>
        @endif
        <div style="position:relative;z-index:2;max-width:1100px;margin:0 auto;padding:clamp(48px,7vw,88px) clamp(16px,4vw,32px);">
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

            {{-- Compte à rebours --}}
            @if ($e->date_start && $e->date_start->isFuture())
                <div style="margin-top:28px;" x-data="ukcCountdown('{{ $e->date_start->toIso8601String() }}')" x-cloak>
                    <div style="display:flex;gap:12px;flex-wrap:wrap;">
                        @foreach (['days' => 'Jours', 'hours' => 'Heures', 'minutes' => 'Min', 'seconds' => 'Sec'] as $k => $lbl)
                            <div style="min-width:70px;text-align:center;background:rgba(255,255,255,.14);border-radius:12px;padding:10px 8px;">
                                <div style="font-size:1.6rem;font-weight:900;line-height:1;" x-text="String({{ $k }}).padStart(2,'0')">00</div>
                                <div style="font-size:.7rem;text-transform:uppercase;letter-spacing:.06em;opacity:.85;margin-top:4px;">{{ $lbl }}</div>
                            </div>
                        @endforeach
                    </div>
                </div>
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

            {{-- Programme (sessions plénières + ateliers) --}}
            @if ($plenaries->count() || $ateliers->count())
                <section id="programme" class="ukc-card">
                    <h2 style="font-size:1.3rem;font-weight:800;color:var(--ukc-blue);margin-bottom:16px;">Programme</h2>

                    @if ($plenaries->count())
                        <h3 style="font-size:1.05rem;font-weight:800;margin:6px 0 12px;display:flex;align-items:center;gap:8px;">
                            <x-ukc.icon name="mic" :size="18" /> Sessions plénières
                        </h3>
                        @foreach ($plenaries as $session)
                            @include('livewire.public._session', ['session' => $session, 'commsBySession' => $commsBySession])
                        @endforeach
                    @endif

                    @foreach ($ateliers as $atelier)
                        <div style="margin-top:18px;">
                            <h3 style="font-size:1.05rem;font-weight:800;margin-bottom:6px;display:flex;align-items:center;gap:8px;">
                                <x-ukc.icon name="layers" :size="18" /> {{ $atelier->title }}
                            </h3>
                            @if (count($atelier->tracks ?? []))
                                <div style="display:flex;gap:6px;flex-wrap:wrap;margin-bottom:10px;">
                                    @foreach ($atelier->tracks as $t)<span class="ukc-pill ukc-pill--info">{{ $t }}</span>@endforeach
                                </div>
                            @endif
                            @forelse ($atelier->sessions as $session)
                                @include('livewire.public._session', ['session' => $session, 'commsBySession' => $commsBySession])
                            @empty
                                <p style="font-size:.88rem;color:var(--ukc-muted);">Sessions à venir.</p>
                            @endforelse
                        </div>
                    @endforeach
                </section>
            @endif

            {{-- Comités --}}
            @if ($committees->count())
                @php($comLabels = ['organisation' => 'Comité d’organisation', 'scientifique' => 'Comité scientifique', 'supervision' => 'Comité de supervision'])
                <section id="comites" class="ukc-card">
                    <h2 style="font-size:1.3rem;font-weight:800;color:var(--ukc-blue);margin-bottom:16px;">Comités</h2>
                    @foreach ($comLabels as $role => $label)
                        @php($members = $committees[$role] ?? collect())
                        @if ($members->count())
                            <h3 style="font-size:1.05rem;font-weight:800;margin:14px 0 10px;">{{ $label }}</h3>
                            <div class="ukc-grid-3" style="grid-template-columns:repeat(auto-fit,minmax(200px,1fr));">
                                @foreach ($members as $m)
                                    <div style="display:flex;gap:10px;align-items:center;padding:10px 12px;background:var(--ukc-bg);border:1px solid var(--ukc-border);border-radius:12px;">
                                        <div style="width:44px;height:44px;border-radius:50%;flex-shrink:0;overflow:hidden;background:linear-gradient(135deg,var(--ukc-blue),var(--ukc-green));display:flex;align-items:center;justify-content:center;color:#fff;font-weight:800;">
                                            @if ($m->photo_url)<img src="{{ $m->photo_url }}" alt="" style="width:100%;height:100%;object-fit:cover;">@else{{ mb_substr($m->person_name, 0, 1) }}@endif
                                        </div>
                                        <div style="min-width:0;">
                                            <div style="font-weight:700;font-size:.9rem;">{{ $m->person_name }}</div>
                                            <div style="font-size:.78rem;color:var(--ukc-muted);">{{ $m->person_title }}@if ($m->institution) · {{ $m->institution }}@endif</div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    @endforeach
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
                    <p style="margin:16px 0 0;font-size:.9rem;color:var(--ukc-muted);text-align:center;">
                        Co-auteur d’une communication acceptée ?
                        <a href="{{ route('public.coauthor', $edition->slug) }}" style="color:var(--ukc-blue);font-weight:700;">Réglez vos frais ici</a>.
                    </p>
                </section>
            @endif

            {{-- Partenaires --}}
            @if (count($partners))
                <section id="partenaires" class="ukc-card">
                    <h2 style="font-size:1.3rem;font-weight:800;color:var(--ukc-blue);margin-bottom:16px;">Partenaires</h2>
                    <div style="display:flex;flex-wrap:wrap;gap:16px;align-items:center;justify-content:center;">
                        @foreach ($partners as $p)
                            @php($inner = !empty($p['logo_url'])
                                ? '<img src="' . e($p['logo_url']) . '" alt="' . e($p['name']) . '" style="max-height:56px;max-width:150px;object-fit:contain;">'
                                : '<span style="font-weight:700;color:var(--ukc-text);">' . e($p['name']) . '</span>')
                            @if (!empty($p['url']))
                                <a href="{{ $p['url'] }}" target="_blank" rel="noopener" title="{{ $p['name'] }}"
                                   style="padding:12px 16px;background:#fff;border:1px solid var(--ukc-border);border-radius:12px;display:flex;align-items:center;">{!! $inner !!}</a>
                            @else
                                <div style="padding:12px 16px;background:#fff;border:1px solid var(--ukc-border);border-radius:12px;display:flex;align-items:center;">{!! $inner !!}</div>
                            @endif
                        @endforeach
                    </div>
                </section>
            @endif

            {{-- FAQ --}}
            @if (count($faq))
                <section id="faq" class="ukc-card">
                    <h2 style="font-size:1.3rem;font-weight:800;color:var(--ukc-blue);margin-bottom:16px;">Questions fréquentes</h2>
                    @foreach ($faq as $item)
                        <details style="border-bottom:1px solid var(--ukc-border);padding:12px 0;">
                            <summary style="font-weight:700;cursor:pointer;list-style:none;display:flex;justify-content:space-between;gap:10px;">
                                {{ $item['question'] }}
                                <x-ukc.icon name="chevron" :size="16" style="transform:rotate(90deg);flex-shrink:0;" />
                            </summary>
                            <p style="color:var(--ukc-muted);line-height:1.6;margin-top:8px;white-space:pre-line;">{{ $item['answer'] }}</p>
                        </details>
                    @endforeach
                </section>
            @endif

            {{-- Infos pratiques --}}
            @if (! empty($practical['venue']) || ! empty($practical['accommodation']) || ! empty($practical['transport']))
                <section id="infos" class="ukc-card">
                    <h2 style="font-size:1.3rem;font-weight:800;color:var(--ukc-blue);margin-bottom:16px;">Infos pratiques</h2>
                    <div class="ukc-grid-3" style="grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:16px;">
                        @php($infos = [
                            ['pin', 'Lieu', $practical['venue'] ?? null],
                            ['bed', 'Hébergement', $practical['accommodation'] ?? null],
                            ['bus', 'Transport', $practical['transport'] ?? null],
                        ])
                        @foreach ($infos as [$icon, $label, $value])
                            @if (! empty($value))
                                <div style="padding:16px;border:1px solid var(--ukc-border);border-radius:12px;background:#fff;">
                                    <div style="display:flex;align-items:center;gap:8px;font-weight:700;color:var(--ukc-blue);margin-bottom:8px;">
                                        <x-ukc.icon :name="$icon" :size="18" /> {{ $label }}
                                    </div>
                                    <p style="color:var(--ukc-muted);line-height:1.6;white-space:pre-line;margin:0;font-size:.92rem;">{{ $value }}</p>
                                </div>
                            @endif
                        @endforeach
                    </div>
                </section>
            @endif

            {{-- Contact --}}
            <section id="contact" class="ukc-card">
                <h2 style="font-size:1.3rem;font-weight:800;color:var(--ukc-blue);margin-bottom:16px;">Contact</h2>
                <div class="ukc-grid-2" style="grid-template-columns:1fr 1.2fr;gap:24px;align-items:start;">
                    <div style="display:flex;flex-direction:column;gap:12px;">
                        @if (!empty($contact['email']))
                            <div style="display:flex;gap:10px;align-items:center;">
                                <x-ukc.icon name="mail" :size="18" />
                                <a href="mailto:{{ $contact['email'] }}" style="color:var(--ukc-text);">{{ $contact['email'] }}</a>
                            </div>
                        @endif
                        @if (!empty($contact['phone']))
                            <div style="display:flex;gap:10px;align-items:center;">
                                <x-ukc.icon name="phone" :size="18" /> <span>{{ $contact['phone'] }}</span>
                            </div>
                        @endif
                        @if (!empty($contact['address']))
                            <div style="display:flex;gap:10px;align-items:flex-start;">
                                <x-ukc.icon name="pin" :size="18" /> <span>{{ $contact['address'] }}</span>
                            </div>
                        @endif
                        @if (empty($contact['email']) && empty($contact['phone']) && empty($contact['address']))
                            <p style="color:var(--ukc-muted);margin:0;">Envoyez-nous un message via le formulaire.</p>
                        @endif
                    </div>
                    <div>
                        @livewire('public.contact-form', ['editionId' => $e->id], key('contact-'.$e->id))
                    </div>
                </div>
            </section>

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
</x-layouts.app>
