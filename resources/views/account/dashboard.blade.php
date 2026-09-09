<x-layouts.participant :title="__('Tableau de bord')" :subtitle="__('Votre parcours, communications & documents')">
<div class="account-dashboard">
    {{-- Bandeau : prochaine étape --}}
    @if (! $account->hasVerifiedEmail())
        <div class="ukc-alert ukc-alert--warn">
            <span class="ico"><x-ukc.icon name="mail" :size="18" /></span>
            <div style="flex:1;">
                <strong>{{ __('Confirmez votre adresse e-mail') }}</strong> {{ __('pour pouvoir soumettre une communication.') }}
                <a href="{{ route('account.verification.notice') }}"
                   style="color:inherit;text-decoration:underline;font-weight:700;">{{ __('Renvoyer le lien') }}</a>
            </div>
        </div>
    @elseif (blank($account->affiliation))
        <div class="ukc-alert ukc-alert--info">
            <span class="ico"><x-ukc.icon name="info" :size="18" /></span>
            <div style="flex:1;">
                <strong>{{ __('Complétez votre profil') }}</strong> {{ __('(affiliation) pour finaliser votre inscription.') }}
                <a href="{{ route('account.profile') }}"
                   style="color:inherit;text-decoration:underline;font-weight:700;">{{ __('Compléter mon profil') }}</a>
            </div>
        </div>
    @endif

    {{-- Bandeau d'accueil (fidèle au mockup) : bienvenue + dates & lieu du colloque --}}
    @php
        $colloqueLine = null;
        if ($contextEdition && $contextEdition->date_start) {
            $d1 = $contextEdition->date_start;
            $d2 = $contextEdition->date_end;
            $dates = $d2 && $d2->ne($d1)
                ? $d1->translatedFormat('j').' – '.$d2->translatedFormat('j F Y')
                : $d1->translatedFormat('j F Y');
            $colloqueLine = trim($dates.($contextEdition->location ? ' · '.\Illuminate\Support\Str::limit(strip_tags($contextEdition->location), 60) : ''));
        }
    @endphp
    <div class="ukc-alert ukc-alert--info" style="margin-bottom:22px;">
        <span class="ico"><x-ukc.icon name="info" :size="18" /></span>
        <div style="flex:1;">
            {{ __('Bonjour') }} <strong>{{ $account->first_name }}</strong>. {{ __('Suivez l’état de vos soumissions, finalisez votre inscription et téléchargez vos documents.') }}
            @if ($colloqueLine)<br><strong>{{ __('Colloque :') }} {{ $colloqueLine }}</strong>@endif
        </div>
    </div>

    {{-- Indicateurs synthétiques (rangée KPI, fidèle au mockup) --}}
    <div class="ukc-grid-4" style="margin-bottom:20px;">
        <div class="ukc-stat-card">
            <div class="num">{{ $kpi['communications'] }}</div>
            <div class="lbl">{{ __('Communications') }}</div>
        </div>
        <div class="ukc-stat-card">
            @if ($kpi['profileComplete'])
                <div class="num" style="color:var(--ukc-green);">✓</div>
            @else
                <div class="num" style="color:#b45309;">…</div>
            @endif
            <div class="lbl">{{ __('Profil complet') }}</div>
        </div>
        <div class="ukc-stat-card">
            @if ($kpi['hasPaid'])
                <div class="num" style="color:var(--ukc-green);">✓</div>
            @else
                <div class="num" style="color:#b45309;">…</div>
            @endif
            <div class="lbl">{{ __('Paiement') }}</div>
        </div>
        <div class="ukc-stat-card">
            <div class="num">{{ $kpi['daysRemaining'] ?? '—' }}</div>
            <div class="lbl">{{ __('Jours restants') }}</div>
        </div>
    </div>

    {{-- Mon agenda : passages datés → export calendrier (ICS / Google) --}}
    @if ($hasAgenda)
        <div class="ukc-card" style="margin-bottom:20px;display:flex;justify-content:space-between;gap:14px;align-items:center;flex-wrap:wrap;">
            <div style="min-width:0;">
                <h3 style="margin:0;"><x-ukc.icon name="calendar" :size="20" /> {{ __('Mon agenda') }}</h3>
                <p style="font-size:.86rem;color:var(--ukc-muted);margin:4px 0 0;">
                    {{ __('Vos créneaux de passage, avec un rappel une heure avant. Importez-les dans votre agenda.') }}
                </p>
            </div>
            <a href="{{ route('account.agenda.ics') }}" class="ukc-btn ukc-btn--blue ukc-btn--sm" style="flex-shrink:0;">
                <x-ukc.icon name="download" :size="15" /> {{ __('Télécharger (.ics)') }}
            </a>
        </div>
    @endif

    {{-- Sessions en direct maintenant --}}
    @if ($liveSessions->isNotEmpty())
        <div class="ukc-card" style="margin-bottom:20px;border-color:#fecaca;background:#fff5f5;">
            <h3 style="display:flex;align-items:center;gap:8px;">
                <span class="ukc-pill ukc-pill--err" style="display:inline-flex;align-items:center;gap:5px;">
                    <span style="width:7px;height:7px;border-radius:50%;background:currentColor;"></span> {{ __('EN DIRECT') }}
                </span>
                {{ __('Sessions en cours') }}
            </h3>
            @foreach ($liveSessions as $s)
                <div style="display:flex;justify-content:space-between;gap:10px;align-items:center;padding:8px 0;border-top:1px solid var(--ukc-border);">
                    <div style="min-width:0;">
                        <div style="font-weight:700;">{{ $s->label }}</div>
                        <div style="font-size:.82rem;color:var(--ukc-muted);">{{ $s->edition?->title }}</div>
                    </div>
                    <a href="{{ route('session.meeting', $s) }}" class="ukc-btn ukc-btn--green ukc-btn--sm" style="flex-shrink:0;">
                        <x-ukc.icon name="video" :size="15" /> {{ __('Rejoindre') }}
                    </a>
                </div>
            @endforeach
        </div>
    @endif

    {{-- Bloc principal (fidèle au mockup) : Mes soumissions (table) + Prochaines étapes --}}
    @php
        $hasSubs = $submissions->isNotEmpty();
        $decided = $submissions->contains(fn ($s) => in_array($s->status, ['accepted', 'rejected'], true));
    @endphp
    <div class="ukc-grid-2" style="align-items:start;margin-bottom:22px;">
        {{-- Mes soumissions --}}
        <div class="ukc-card">
            <h3>{{ __('Mes soumissions') }}</h3>
            <div class="ukc-table-wrap">
                <table class="ukc-table">
                    <thead>
                        <tr><th>{{ __('Titre') }}</th><th>{{ __('Atelier') }}</th><th>{{ __('Statut') }}</th></tr>
                    </thead>
                    <tbody>
                        @forelse ($submissions as $s)
                            <tr>
                                <td>
                                    <div class="title"><a href="{{ route('account.submissions.show', $s) }}" style="color:var(--ukc-blue);font-weight:600;">{{ \Illuminate\Support\Str::limit($s->title, 46) }}</a></div>
                                    <div class="meta" style="font-size:12px;color:var(--ukc-muted);">{{ $s->type === 'poster' ? __('Poster') : __('Orale') }} · {{ $s->ref_code }}@if ($s->created_at) · {{ __('Déposée le') }} {{ $s->created_at->translatedFormat('j M Y') }}@endif</div>
                                </td>
                                <td>{{ $s->track ?: '—' }}</td>
                                <td><span class="ukc-pill ukc-pill--{{ $s->statusPill() }}">{{ $s->statusLabel() }}</span></td>
                            </tr>
                        @empty
                            <tr><td colspan="3" style="padding:18px;text-align:center;color:var(--ukc-muted);">{{ __('Aucune communication déposée.') }}</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div style="margin-top:16px;">
                <a href="{{ route('account.submissions.index') }}" class="ukc-btn ukc-btn--blue ukc-btn--sm">{{ __('Gérer les soumissions') }}</a>
            </div>
        </div>

        {{-- Prochaines étapes --}}
        <div class="ukc-card">
            <h3>{{ __('Prochaines étapes') }}</h3>
            <div class="ukc-steps" style="flex-direction:column;">
                <div class="ukc-step done">1. {{ __('Compte créé') }}</div>
                <div @class(['ukc-step', 'done' => $account->hasVerifiedEmail(), 'active' => ! $account->hasVerifiedEmail()])>2. {{ __('E-mail vérifié') }}</div>
                <div @class(['ukc-step', 'done' => filled($account->affiliation), 'active' => $account->hasVerifiedEmail() && blank($account->affiliation)])>3. {{ __('Profil renseigné') }}</div>
                <div @class(['ukc-step', 'done' => $hasSubs, 'active' => $account->hasVerifiedEmail() && filled($account->affiliation) && ! $hasSubs])>4. {{ __('Résumé déposé') }}</div>
                <div @class(['ukc-step', 'done' => $decided, 'active' => $hasSubs && ! $decided])>5. {{ __('Notification d’acceptation') }}</div>
                <div @class(['ukc-step', 'done' => $payments->isNotEmpty(), 'active' => $payableEdition && $payments->isEmpty()])>6. {{ __('Inscription & paiement') }}</div>
            </div>
            @if ($kpi['deadline'])
                <p style="font-size:13px;color:var(--ukc-muted);margin-top:6px;">
                    {{ __('Échéance de soumission :') }} <strong>{{ $kpi['deadline']->translatedFormat('j F Y') }}</strong>.
                </p>
            @endif
        </div>
    </div>

    {{-- Mes rôles & activité (dérivés inter-éditions) --}}
    @if (count($roles) || $moderatedSessions->isNotEmpty() || $reportedSessions->isNotEmpty() || $committees->isNotEmpty())
        <div class="ukc-card" style="margin-bottom:18px;">
            <h3><x-ukc.icon name="role" :size="20" /> {{ __('Mes rôles & activité') }}</h3>
            @if (count($roles))
                <div style="display:flex;flex-wrap:wrap;gap:6px;margin:10px 0 6px;">
                    @foreach ($roles as $role)
                        <span class="ukc-pill ukc-pill--info">{{ $role }}</span>
                    @endforeach
                </div>
                <p class="ukc-muted" style="font-size:.8rem;margin:0 0 8px;">{{ __('Rôles dérivés de votre activité sur l’ensemble des éditions.') }}</p>
            @endif

            @if ($moderatedSessions->isNotEmpty() || $reportedSessions->isNotEmpty())
                <div style="margin-top:8px;">
                    @foreach ($moderatedSessions as $s)
                        <div style="padding:6px 0;font-size:.9rem;border-top:1px solid var(--ukc-border);">
                            <span class="ukc-pill ukc-pill--muted">{{ __('Modérateur') }}</span>
                            {{ $s->label ?: __('Session') }} <span class="ukc-muted">— {{ $s->edition?->title }}</span>
                        </div>
                    @endforeach
                    @foreach ($reportedSessions as $s)
                        <div style="padding:6px 0;font-size:.9rem;border-top:1px solid var(--ukc-border);">
                            <span class="ukc-pill ukc-pill--muted">{{ __('Rapporteur') }}</span>
                            {{ $s->label ?: __('Session') }} <span class="ukc-muted">— {{ $s->edition?->title }}</span>
                        </div>
                    @endforeach
                </div>
            @endif

            @if ($committees->isNotEmpty())
                <div style="margin-top:8px;">
                    @foreach ($committees as $c)
                        <div style="padding:6px 0;font-size:.9rem;border-top:1px solid var(--ukc-border);">
                            <span class="ukc-pill ukc-pill--muted">{{ __('Comité') }}</span>
                            {{ $c->person_title ?: __('Membre') }} <span class="ukc-muted">— {{ $c->edition?->title }}</span>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    @endif

    @if ($payableEdition)
        <div class="ukc-card" style="text-align:center;background:linear-gradient(135deg,rgba(0,153,51,.08),rgba(34,62,164,.06));margin-bottom:18px;">
            <h3 style="justify-content:center;">{{ __('Communication acceptée') }}</h3>
            <p style="color:var(--ukc-muted);margin:6px 0 18px;">{{ __('Réglez vos frais d’inscription pour finaliser votre participation à') }} « {{ $payableEdition->title }} ».</p>
            <a href="{{ route('account.payment', $payableEdition) }}" class="ukc-btn ukc-btn--green">
                <x-ukc.icon name="card" :size="18" /> {{ __('Payer mes frais') }}
            </a>
        </div>
    @endif

    @if ($openEdition)
        <div class="ukc-card" style="text-align:center;background:linear-gradient(135deg,rgba(34,62,164,.06),rgba(0,153,51,.06));margin-bottom:20px;">
            <h3 style="justify-content:center;">{{ __('Appel à communications ouvert') }}</h3>
            <p style="color:var(--ukc-muted);margin:6px 0 18px;">{{ $openEdition->title }}</p>
            <a href="{{ route('account.submissions.create', $openEdition->slug) }}" class="ukc-btn ukc-btn--blue">
                <x-ukc.icon name="upload" :size="18" /> {{ __('Soumettre une communication') }}
            </a>
        </div>
    @endif

    @php($hasContent = $submissions->isNotEmpty() || $payments->isNotEmpty() || $attestations->isNotEmpty())

    @if ($hasContent)
        <div class="ukc-grid-2" style="align-items:start;">
            {{-- Paiements & reçus --}}
                <div class="ukc-card">
                    <h3><x-ukc.icon name="receipt" :size="20" /> {{ __('Paiements & reçus') }}</h3>
                    @forelse ($payments as $p)
                        <div style="display:flex;justify-content:space-between;gap:10px;padding:8px 0;border-bottom:1px solid var(--ukc-border);align-items:center;">
                            <span style="font-size:.88rem;">
                                <strong>{{ number_format((float) $p->amount, 0, ',', ' ') }} {{ $p->currency }}</strong>
                                <span style="color:var(--ukc-muted);"> · {{ $p->edition?->year }}</span>
                            </span>
                            <a href="{{ route('account.receipt', $p) }}" target="_blank" class="ukc-btn ukc-btn--outline ukc-btn--sm">
                                <x-ukc.icon name="download" :size="14" /> {{ __('Reçu') }}
                            </a>
                        </div>
                    @empty
                        <p style="font-size:.88rem;color:var(--ukc-muted);">{{ __('Aucun paiement réglé.') }}</p>
                    @endforelse
                </div>

                <div class="ukc-card">
                    <h3><x-ukc.icon name="award" :size="20" /> {{ __('Mes attestations') }}</h3>
                    @forelse ($attestations as $a)
                        <div style="display:flex;justify-content:space-between;gap:10px;padding:8px 0;border-bottom:1px solid var(--ukc-border);align-items:center;">
                            <span style="font-size:.88rem;">{{ $a->typeLabel() }}
                                <span style="color:var(--ukc-muted);"> · {{ $a->edition?->year }}</span>
                            </span>
                            <a href="{{ route('attestation.document', $a->uuid) }}" target="_blank" class="ukc-btn ukc-btn--outline ukc-btn--sm">
                                <x-ukc.icon name="diploma" :size="14" /> {{ __('Télécharger') }}
                            </a>
                        </div>
                    @empty
                        <p style="font-size:.88rem;color:var(--ukc-muted);">{{ __('Aucune attestation pour le moment.') }}</p>
                    @endforelse
                </div>
        </div>
    @elseif (! $openEdition)
        <div class="ukc-empty">
            <h3>{{ __('Votre espace se remplira bientôt') }}</h3>
            <p>{{ __('Communications, paiements et attestation apparaîtront ici au fil de votre participation.') }}</p>
            <a href="{{ route('account.profile') }}" class="ukc-btn ukc-btn--blue">
                <x-ukc.icon name="user" :size="18" /> {{ __('Compléter mon profil') }}
            </a>
        </div>
    @endif

    {{-- Documents de mes éditions --}}
    @if ($editionDocuments->isNotEmpty())
        <div class="ukc-card" style="margin-top:18px;">
            <h3><x-ukc.icon name="folder" :size="20" /> {{ __('Documents de mes éditions') }}</h3>
            @foreach ($editionDocuments as $doc)
                <div style="padding:12px 0;border-top:1px solid var(--ukc-border);">
                    <div style="font-weight:700;margin-bottom:8px;">{{ $doc['edition']->title ?: $doc['edition']->year }}</div>
                    <div style="display:flex;flex-wrap:wrap;gap:8px;">
                        @if ($doc['hasBooklet'])
                            <a href="{{ route('account.documents.livret', $doc['edition']) }}" target="_blank"
                               class="ukc-btn ukc-btn--outline ukc-btn--sm">
                                <x-ukc.icon name="book" :size="14" /> {{ __('Livret des résumés') }}
                            </a>
                        @endif
                        @foreach ($doc['media'] as $m)
                            <a href="{{ $m->url }}" target="_blank" class="ukc-btn ukc-btn--ghost ukc-btn--sm">
                                <x-ukc.icon name="file" :size="14" /> {{ $m->title ?: $m->typeLabel() }}
                            </a>
                        @endforeach
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>

</x-layouts.participant>
