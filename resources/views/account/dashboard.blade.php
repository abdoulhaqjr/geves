@php
    // ⚠️ UN SEUL bloc de code PHP dans cette vue, et il vient en premier : Blade
    // extrait les blocs bruts AVANT de compiler le reste (piège documenté dans
    // CLAUDE.md). ⚠️⚠️ Et on n'écrit JAMAIS la forme EN LIGNE de cette directive
    // en toutes lettres dans un commentaire : Blade la reconnaît dans le texte,
    // sort du bloc à cet endroit, et tout ce qui suit s'imprime tel quel en haut
    // de la page. Ce fichier l'a payé une fois.
    $moments = [
        'avant' => __('Préparez votre participation'),
        'pendant' => __('Le colloque se déroule en ce moment'),
        'apres' => __('Le colloque est terminé'),
    ];
    $sousTitre = $jour
        ? __('Jour :n sur :total', ['n' => $jour['n'], 'total' => $jour['total']])
        : ($moments[$vue] ?? '');

    $dates = null;
    if ($event && $event->date_start) {
        $d1 = $event->date_start;
        $d2 = $event->date_end;
        $dates = $d2 && $d2->ne($d1)
            ? $d1->translatedFormat('j').' - '.$d2->translatedFormat('j F Y')
            : $d1->translatedFormat('j F Y');
    }

    $passage = $prochainPassage;
    $passagePasse = $passage && ($passage->ends_at ?? $passage->starts_at)?->isPast();
    $axes = $communications->pluck('track')->filter()->unique()->values();

    // ⚠️ Préparé ICI plutôt qu'en ligne plus bas dans la vue : ce fichier porte
    // déjà un bloc de code, et la convention du projet est d'employer la même
    // forme partout dans un tel fichier.
    $rapportsARediger = $reportedSessions
        ->filter(fn ($s) => ! $s->report || ! $s->report->isSubmitted())
        ->count();
@endphp

<x-layouts.participant :title="__('Tableau de bord')" :subtitle="$sousTitre">
<div class="account-dashboard tdb tdb--{{ $vue }}">

    {{-- ===================================================================
         Un compte sans participation ni appel ouvert : l'espace est vide, et le
         dire vaut mieux qu'une rangée de cartes à zéro.
    =================================================================== --}}
    @if ($vierge)
        <div class="ukc-empty">
            <h3>{{ __('Votre espace se remplira bientôt') }}</h3>
            <p>{{ __('Communications, paiements et attestations apparaîtront ici au fil de votre participation.') }}</p>
            {{-- ⚠️ **Ne jamais réclamer ce qui est déjà fait.** Ce bouton était
                 « Compléter mon profil » pour TOUT LE MONDE, profil complet
                 compris : on envoyait corriger une page qui n'avait rien à
                 corriger. Il suit désormais ce qui manque RÉELLEMENT (`aFaire`,
                 la même liste que la carte « À faire »), et quand il ne manque
                 rien on le dit au lieu d'inventer une tâche. --}}
            @if ($aFaire !== [])
                <a href="{{ $aFaire[0]['url'] }}" class="ukc-btn ukc-btn--blue">
                    <x-ukc.icon name="user" :size="18" /> {{ $aFaire[0]['titre'] }}
                </a>
            @else
                <p class="tdb-ok tdb-ok--centre">
                    <x-ukc.icon name="check-circle" :size="16" />
                    {{ __('Votre profil est complet : il ne manque qu’une participation à un colloque.') }}
                </p>
                <a href="{{ route('home') }}" class="ukc-btn ukc-btn--blue">
                    <x-ukc.icon name="globe" :size="18" /> {{ __('Voir les colloques') }}
                </a>
            @endif
        </div>
    @else

    {{-- ===================================================================
         1. EN DIRECT - une séance en cours passe AVANT TOUT LE RESTE.
         ⚠️ Elle était rendue en quatrième position, sous deux bandeaux et la
         rangée d'indicateurs : la chose la plus urgente de l'écran se trouvait
         sous ce qui ne presse pas.
    =================================================================== --}}
    @if ($liveSessions->isNotEmpty())
        <section class="tdb-live" aria-label="{{ __('Séances en direct') }}">
            @foreach ($liveSessions as $s)
                <div class="tdb-live__row">
                    <div class="b">
                        <div class="h">
                            <span class="ukc-pill ukc-pill--err tdb-live__tag">
                                <span class="dot" aria-hidden="true"></span>{{ __('EN DIRECT') }}
                            </span>
                            <span class="w">{{ $s->atelier?->title ?: $s->label }}@if ($s->room) · {{ $s->room }}@endif</span>
                        </div>
                        <p class="t">{{ __('Cette séance vous concerne et elle a commencé.') }}</p>
                    </div>
                    <a href="{{ route('session.meeting', $s) }}" class="ukc-btn ukc-btn--green">
                        <x-ukc.icon name="video" :size="16" /> {{ __('Rejoindre la salle') }}
                    </a>
                </div>
            @endforeach
        </section>
    @endif

    {{-- ===================================================================
         2. La carte haute : ce qui vient. Son contenu EST la bascule.
    =================================================================== --}}
    <div class="tdb-top">

        @if (! $event)
            {{-- ⚠️ Un modérateur, un rapporteur ou un relecteur est rapproché par
                 E-MAIL : il peut n'avoir aucune inscription, donc aucun colloque à
                 sélectionner. Lui annoncer une échéance de dépôt n'aurait aucun
                 sens ; ce qu'on lui doit, ce sont ses séances. --}}
            <article class="tdb-next tdb-next--calme">
                <div class="tdb-next__eyebrow">
                    <x-ukc.icon name="users" :size="16" /> {{ __('Mon espace') }}
                </div>
                <div class="tdb-next__lede">
                    <span class="n">{{ __('Mes fonctions') }}</span>
                </div>
                <p class="tdb-next__title">
                    {{ __('Vous n’êtes inscrit à aucun colloque pour l’instant. Les séances et les relectures que l’organisation vous a confiées restent accessibles ci-dessous.') }}
                </p>
                <div class="tdb-next__acts">
                    @if ($moderatedSessions->isNotEmpty())
                        <a href="{{ route('account.moderation') }}" class="tdb-btn-light">
                            <x-ukc.icon name="mic" :size="15" /> {{ __('Séances à modérer') }}
                        </a>
                    @endif
                    @if ($reportedSessions->isNotEmpty())
                        <a href="{{ route('account.reports') }}" class="tdb-btn-ghost">
                            <x-ukc.icon name="sheet" :size="15" /> {{ __('Séances à rapporter') }}
                        </a>
                    @endif
                    @if ($isReviewer)
                        <a href="{{ route('account.reviews') }}" class="tdb-btn-ghost">
                            <x-ukc.icon name="inbox" :size="15" /> {{ __('Mes relectures') }}
                        </a>
                    @endif
                </div>
            </article>

        @elseif ($vue === 'pendant' && $passage)
            {{-- ⚠️ Le contrôleur calculait déjà ces créneaux pour n'en garder qu'un
                 booléen : l'écran savait quand et où le participant présente, et ne
                 le disait pas - il offrait seulement un bouton de téléchargement. --}}
            <article class="tdb-next">
                <div class="tdb-next__eyebrow">
                    <x-ukc.icon name="calendar" :size="16" />
                    {{ $passagePasse ? __('Mon passage') : __('Mon prochain passage') }}
                </div>
                @if ($tdb->roleSur($passage) === 'coauteur')
                    {{-- ⚠️ Un co-auteur ne présente pas seul, mais il co-présente : le
                         créneau le concerne, et le lui cacher reviendrait à ne rien lui
                         dire du moment où sa communication passe. Son rôle est nommé. --}}
                    <div class="tdb-next__role">{{ __('Vous co-présentez cette communication.') }}</div>
                @endif
                <div class="tdb-next__lede">
                    <span class="n">{{ $passage->starts_at->format('H\hi') }}</span>
                    <span class="w">
                        {{ \Illuminate\Support\Str::ucfirst($passage->starts_at->translatedFormat('l j F')) }}
                        · {{ $passage->starts_at->diffForHumans() }}
                    </span>
                </div>
                <p class="tdb-next__title">{{ $passage->submission?->title ?: __('Ma communication') }}</p>
                <div class="tdb-chips">
                    @if ($passage->room || $passage->session?->room)
                        <span class="tdb-chip"><x-ukc.icon name="pin" :size="13" /> {{ $passage->room ?: $passage->session->room }}</span>
                    @endif
                    @if ($passage->session?->label || $passage->session_label)
                        <span class="tdb-chip">{{ $passage->session?->label ?: $passage->session_label }}</span>
                    @endif
                    @if ($passage->starts_at && $passage->ends_at)
                        <span class="tdb-chip">{{ (int) $passage->starts_at->diffInMinutes($passage->ends_at) }} {{ __('min') }}</span>
                    @endif
                    @if ($passage->submission?->ref_code)
                        <span class="tdb-chip tdb-chip--gold">{{ $passage->submission->ref_code }}</span>
                    @endif
                    @if ($passage->wasPresented())
                        <span class="tdb-chip tdb-chip--gold">{{ __('Passage constaté') }}</span>
                    @endif
                </div>
                <div class="tdb-next__acts">
                    @if ($passage->session && $passage->session->isLive())
                        <a href="{{ route('session.meeting', $passage->session) }}" class="ukc-btn ukc-btn--green">
                            <x-ukc.icon name="video" :size="16" /> {{ __('Rejoindre ma séance') }}
                        </a>
                    @endif
                    @if ($passage->submission)
                        <a href="{{ route('account.submissions.show', $passage->submission) }}" class="tdb-btn-light">
                            <x-ukc.icon name="file" :size="15" /> {{ __('Ma communication') }}
                        </a>
                    @endif
                    <a href="{{ route('account.agenda.ics') }}" class="tdb-btn-ghost">
                        <x-ukc.icon name="download" :size="15" /> {{ __('Ajouter à mon agenda (.ics)') }}
                    </a>
                </div>
            </article>

        @elseif ($vue === 'apres')
            <article class="tdb-next tdb-next--calme">
                <div class="tdb-next__eyebrow">
                    <x-ukc.icon name="award" :size="16" /> {{ __('Ce qu’il vous reste à retirer') }}
                </div>
                <div class="tdb-next__lede">
                    <span class="n">{{ count($documents) }}</span>
                    <span class="w">{{ trans_choice('document disponible|documents disponibles', count($documents)) }}</span>
                </div>
                <p class="tdb-next__title">
                    {{ __('Le colloque est terminé. Vos reçus, attestations et le livre des résumés restent accessibles ici, et vos attestations restent vérifiables publiquement.') }}
                </p>
                <div class="tdb-next__acts">
                    <a href="{{ route('account.documents') }}" class="tdb-btn-light">
                        <x-ukc.icon name="folder" :size="15" /> {{ __('Mes documents') }}
                    </a>
                    <a href="{{ route('account.testimonials') }}" class="tdb-btn-ghost">
                        <x-ukc.icon name="sparkles" :size="15" /> {{ __('Laisser un témoignage') }}
                    </a>
                </div>
            </article>

        @else
            {{-- Avant le colloque : l'échéance et la place restante. --}}
            <article class="tdb-next">
                <div class="tdb-next__eyebrow">
                    <x-ukc.icon name="clock" :size="16" /> {{ __('Ma prochaine étape') }}
                </div>
                @if ($fraisDus)
                    <div class="tdb-next__lede">
                        <span class="n">{{ __('À régler') }}</span>
                        <span class="w">{{ __('vos frais d’inscription') }}</span>
                    </div>
                    <p class="tdb-next__title">{{ __('Votre communication est acceptée pour « :event ». Réglez vos frais pour finaliser votre participation.', ['event' => $fraisDus->title]) }}</p>
                    <div class="tdb-next__acts">
                        <a href="{{ route('account.payment', $fraisDus) }}" class="tdb-btn-gold">
                            <x-ukc.icon name="card" :size="16" /> {{ __('Payer mes frais') }}
                        </a>
                    </div>
                @elseif ($submitEvent && $submitEvent->submission_open)
                    <div class="tdb-next__lede">
                        @if ($deadline && $deadline->isFuture())
                            <span class="n">J-{{ (int) ceil(now()->diffInDays($deadline, false)) }}</span>
                            <span class="w">{{ __('clôture des dépôts le :date', ['date' => $deadline->translatedFormat('j F Y')]) }}</span>
                        @else
                            <span class="n">{{ __('Appel ouvert') }}</span>
                            <span class="w">{{ $submitEvent->title }}</span>
                        @endif
                    </div>
                    <p class="tdb-next__title">
                        @if ($submitRemaining > 0)
                            {{ trans_choice(
                                'Il vous reste :count dépôt possible dans ce colloque.|Il vous reste :count dépôts possibles dans ce colloque.',
                                $submitRemaining, ['count' => $submitRemaining]
                            ) }}
                        @else
                            {{ __('Vous avez atteint le nombre maximum de communications pour ce colloque. Vos résumés suivent leur relecture.') }}
                        @endif
                    </p>
                    <div class="tdb-next__acts">
                        @if ($submitRemaining > 0)
                            <a href="{{ route('account.submissions.create', $submitEvent->slug) }}" class="tdb-btn-gold">
                                <x-ukc.icon name="upload" :size="16" /> {{ __('Déposer un résumé') }}
                            </a>
                        @endif
                        <a href="{{ route('account.submissions.index') }}" class="tdb-btn-ghost">
                            <x-ukc.icon name="file" :size="15" /> {{ __('Mes communications') }}
                        </a>
                    </div>
                @else
                    <div class="tdb-next__lede">
                        <span class="n">{{ __('En relecture') }}</span>
                        @if ($dates)<span class="w">{{ __('rendez-vous du :dates', ['dates' => $dates]) }}</span>@endif
                    </div>
                    <p class="tdb-next__title">{{ __('Les dépôts sont clos. Vous serez prévenu dès que le comité scientifique aura rendu sa décision.') }}</p>
                    <div class="tdb-next__acts">
                        <a href="{{ route('account.submissions.index') }}" class="tdb-btn-light">
                            <x-ukc.icon name="file" :size="15" /> {{ __('Mes communications') }}
                        </a>
                    </div>
                @endif
            </article>
        @endif

        {{-- Le colloque : dates, lieu, axes. Un repère, pas une alerte. --}}
        <aside class="ukc-card tdb-ctx">
            <div class="tdb-ctx__head">
                <span class="e">{{ __('Le colloque') }}</span>
                @if ($phase)
                    <span class="ukc-pill ukc-pill--{{ $phase->pill() }}">{{ $phase->label() }}</span>
                @endif
            </div>
            @if ($event)
                <p class="tdb-ctx__title">{{ $event->title ?: $event->series?->name }}</p>
                <dl class="tdb-facts">
                    @if ($dates)
                        <div><dt>{{ __('Dates') }}</dt><dd>{{ $dates }}</dd></div>
                    @endif
                    @if ($event->location)
                        <div><dt>{{ __('Lieu') }}</dt><dd>{{ \Illuminate\Support\Str::limit(strip_tags($event->location), 70) }}</dd></div>
                    @endif
                    @if ($axes->isNotEmpty())
                        <div><dt>{{ trans_choice('Mon axe|Mes axes', $axes->count()) }}</dt>
                            <dd>{{ $axes->map(fn ($a) => \Illuminate\Support\Str::limit($a, 48))->implode(' · ') }}</dd></div>
                    @endif
                </dl>
                <a href="{{ route('public.colloque', $event->slug) }}" class="tdb-link tdb-link--foot">
                    {{ __('Page du colloque') }} <x-ukc.icon name="chevron" :size="14" />
                </a>
            @else
                <p class="tdb-empty">{{ __('Aucun colloque sélectionné.') }}</p>
            @endif
        </aside>
    </div>

    {{-- ===================================================================
         3. Indicateurs : QUATRE CHIFFRES.
         ⚠️ Deux des quatre cartes portaient une coche (« Profil complet ✓ ») ou
         des points de suspension : une tâche n'est pas une grandeur, elle ne se
         compare à rien et elle n'était pas cliquable. Elle est passée dans
         « À faire ».
    =================================================================== --}}
    <div class="ukc-stats tdb-kpi">
        @foreach ($indicateurs as $i)
            <div class="ukc-stat-card tw-stat">
                <div class="tw-stat__icon tw-stat__icon--{{ $i['ton'] }}"><x-ukc.icon :name="$i['icone']" :size="20" /></div>
                <div class="lbl">{{ $i['libelle'] }}</div>
                <div class="val val--{{ $i['ton'] }}">{{ $i['valeur'] }}@if ($i['unite'] !== '')<span class="u">{{ $i['unite'] }}</span>@endif</div>
                @if ($i['detail'] !== '')<div class="hint">{{ $i['detail'] }}</div>@endif
            </div>
        @endforeach
    </div>

    {{-- ===================================================================
         4. Deux colonnes. La latérale passe dessous sous 960px.
    =================================================================== --}}
    <div class="tdb-cols">
        <div class="tdb-main">

            @if ($vue === 'apres')
                @include('account._tdb-documents', ['grand' => true])
            @elseif ($agenda !== [])
                @include('account._tdb-agenda')
            @endif

            {{-- Le parcours en entier : il RENSEIGNE avant le colloque (il dit ce
                 qui vient). Pendant et après, il n'annoncerait qu'un travail déjà
                 fait - c'est « À faire » qui prend le relais. --}}
            @if ($vue === 'avant')
                <section class="ukc-card tdb-steps">
                    <h3><x-ukc.icon name="list" :size="20" /> {{ __('Mon parcours') }}</h3>
                    <p class="tdb-sub">{{ __('De la création du compte à l’attestation.') }}</p>
                    <ol class="tdb-steps__list">
                        @foreach ($etapes as $k => $etape)
                            <li @class(['tdb-step', 'is-'.$etape['etat']])>
                                <div class="g">
                                    <span class="p">
                                        @if ($etape['etat'] === 'fait')
                                            <x-ukc.icon name="check" :size="13" />
                                        @else
                                            {{ $k + 1 }}
                                        @endif
                                    </span>
                                    @unless ($loop->last)<span class="r" aria-hidden="true"></span>@endunless
                                </div>
                                <div class="b">
                                    <div class="t">{{ $etape['titre'] }}</div>
                                    @if ($etape['detail'] !== '')<div class="d">{{ $etape['detail'] }}</div>@endif
                                </div>
                            </li>
                        @endforeach
                    </ol>
                </section>
            @endif

            {{-- Mes communications : déposées ET en co-auteur.
                 ⚠️ Cette carte ne montrait que les communications déposées, alors
                 que /compte/communications y ajoute celles dont il est co-auteur :
                 le tableau de bord annonçait donc un chiffre plus petit que la page
                 qu'il résume. --}}
            <section class="ukc-card tdb-comms">
                <h3>
                    <x-ukc.icon name="file" :size="20" /> {{ __('Mes communications') }}
                    <a href="{{ route('account.submissions.index') }}" class="tdb-link">{{ __('Gérer mes soumissions') }}</a>
                </h3>
                <div class="ukc-table-wrap">
                    <table class="ukc-table">
                        <caption class="tdb-sub">{{ __('Celles que vous avez déposées et celles dont vous êtes co-auteur.') }}</caption>
                        <thead>
                            <tr>
                                <th>{{ __('Communication') }}</th>
                                <th>{{ __('Mon rôle') }}</th>
                                <th>{{ __('État') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($communications as $s)
                                <tr>
                                    <td>
                                        <div class="title">
                                            <a href="{{ route('account.submissions.show', $s) }}">{{ \Illuminate\Support\Str::limit($s->title, 64) }}</a>
                                        </div>
                                        <div class="meta">
                                            {{ collect([
                                                $s->ref_code,
                                                $s->type === 'poster' ? __('Poster') : __('Orale'),
                                                $s->track ? \Illuminate\Support\Str::limit($s->track, 34) : null,
                                            ])->filter()->implode(' · ') }}
                                        </div>
                                    </td>
                                    <td>
                                        @if ($tdb->estCorrespondant($s))
                                            <span class="ukc-pill ukc-pill--info">{{ __('Auteur correspondant') }}</span>
                                        @else
                                            <span class="ukc-pill ukc-pill--muted">{{ __('Co-auteur') }}</span>
                                        @endif
                                    </td>
                                    <td><span class="ukc-pill ukc-pill--{{ $s->statusPill() }}">{{ $s->statusLabel() }}</span></td>
                                </tr>
                            @empty
                                <tr><td colspan="3" class="tdb-empty">{{ __('Aucune communication pour l’instant.') }}</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>
        </div>

        <div class="tdb-side">

            {{-- À faire : seulement ce qui RESTE, et chaque ligne est cliquable. --}}
            <section class="ukc-card tdb-todo">
                <h3>
                    <x-ukc.icon name="check-circle" :size="20" /> {{ __('À faire') }}
                    @if ($aFaire !== [])<span class="ukc-pill ukc-pill--warn">{{ count($aFaire) }}</span>@endif
                </h3>
                <div class="tdb-bar" role="img"
                     aria-label="{{ __(':faites étapes franchies sur :total', ['faites' => $avancement['faites'], 'total' => $avancement['total']]) }}">
                    <span style="width: {{ round(100 * $avancement['faites'] / max(1, $avancement['total'])) }}%"></span>
                </div>
                <p class="tdb-sub">{{ __(':faites étapes sur :total sont derrière vous.', ['faites' => $avancement['faites'], 'total' => $avancement['total']]) }}</p>

                @forelse ($aFaire as $tache)
                    <a href="{{ $tache['url'] }}" @class(['tdb-todo__item', 'is-'.$tache['ton']])>
                        <span class="i"><x-ukc.icon name="info" :size="13" /></span>
                        <span class="b">
                            <span class="t">{{ $tache['titre'] }}</span>
                            <span class="d">{{ $tache['detail'] }}</span>
                        </span>
                    </a>
                @empty
                    <p class="tdb-ok"><x-ukc.icon name="check-circle" :size="16" /> {{ __('Rien ne vous attend. Tout est à jour.') }}</p>
                @endforelse
            </section>

            {{-- Mes fonctions : ce que le colloque vous a confié. --}}
            @if ($moderatedSessions->isNotEmpty() || $reportedSessions->isNotEmpty() || $isReviewer || $committees->isNotEmpty())
                <section class="ukc-card tdb-roles">
                    <h3><x-ukc.icon name="users" :size="20" /> {{ __('Mes fonctions') }}</h3>
                    <p class="tdb-sub">{{ __('Ce que le colloque vous a confié.') }}</p>

                    @if ($moderatedSessions->isNotEmpty())
                        <div class="tdb-role">
                            <span class="i"><x-ukc.icon name="mic" :size="17" /></span>
                            <span class="b">
                                <span class="t">{{ __('Modérateur') }}</span>
                                <span class="d">{{ trans_choice(':count séance à modérer|:count séances à modérer', $moderatedSessions->count(), ['count' => $moderatedSessions->count()]) }}</span>
                            </span>
                            <a href="{{ route('account.moderation') }}" class="ukc-btn ukc-btn--outline ukc-btn--sm">{{ __('Ouvrir') }}</a>
                        </div>
                    @endif
                    @if ($reportedSessions->isNotEmpty())
                        <div class="tdb-role">
                            <span class="i"><x-ukc.icon name="sheet" :size="17" /></span>
                            <span class="b">
                                <span class="t">{{ __('Rapporteur') }}</span>
                                <span class="d">
                                    @if ($rapportsARediger > 0)
                                        {{ trans_choice(':count rapport à rédiger|:count rapports à rédiger', $rapportsARediger, ['count' => $rapportsARediger]) }}
                                    @else
                                        {{ __('Rapports à jour') }}
                                    @endif
                                </span>
                            </span>
                            <a href="{{ route('account.reports') }}" class="ukc-btn ukc-btn--outline ukc-btn--sm">{{ __('Ouvrir') }}</a>
                        </div>
                    @endif
                    @if ($isReviewer)
                        <div class="tdb-role">
                            <span class="i"><x-ukc.icon name="inbox" :size="17" /></span>
                            <span class="b">
                                <span class="t">{{ __('Relecteur') }}</span>
                                <span class="d">
                                    @if ($pendingReviews > 0)
                                        {{ trans_choice(':count relecture à évaluer|:count relectures à évaluer', $pendingReviews, ['count' => $pendingReviews]) }}
                                    @else
                                        {{ __('Relectures à jour') }}
                                    @endif
                                </span>
                            </span>
                            <a href="{{ route('account.reviews') }}" class="ukc-btn ukc-btn--outline ukc-btn--sm">{{ __('Ouvrir') }}</a>
                        </div>
                    @endif
                    @foreach ($committees as $c)
                        <div class="tdb-role">
                            <span class="i"><x-ukc.icon name="award" :size="17" /></span>
                            <span class="b">
                                <span class="t">{{ __('Comité') }}</span>
                                <span class="d">{{ $c->person_title ?: __('Membre') }} · {{ $c->event?->title }}</span>
                            </span>
                        </div>
                    @endforeach
                </section>
            @endif

            @if ($vue !== 'apres')
                @include('account._tdb-documents', ['grand' => false])
            @endif

            {{-- Documents de l'organisation (appel à communications, gabarits…). --}}
            @if ($media->isNotEmpty())
                <section class="ukc-card tdb-docs">
                    <h3><x-ukc.icon name="book" :size="20" /> {{ __('Documents du colloque') }}</h3>
                    @foreach ($media as $m)
                        <a href="{{ $m->url }}" target="_blank" rel="noopener" class="tdb-doc">
                            <span class="i"><x-ukc.icon name="file" :size="16" /></span>
                            <span class="t">{{ $m->title ?: $m->typeLabel() }}</span>
                            <span class="f"><x-ukc.icon name="external" :size="13" /></span>
                        </a>
                    @endforeach
                </section>
            @endif

            <section class="ukc-card tdb-help">
                <h3><x-ukc.icon name="sparkles" :size="20" /> {{ __('Une question ?') }}</h3>
                <p class="tdb-sub">{{ __('L’assistante connaît le programme, les salles, les échéances et votre dossier.') }}</p>
                <div class="tdb-help__acts">
                    <a href="{{ route('public.gesco') }}" class="ukc-btn ukc-btn--blue ukc-btn--sm">
                        <x-ukc.icon name="sparkles" :size="15" /> {{ __('Demander à l’assistante') }}
                    </a>
                    <a href="{{ route('account.onboarding') }}" class="ukc-btn ukc-btn--outline ukc-btn--sm">
                        {{ __('Prise en main') }}
                    </a>
                </div>
            </section>
        </div>
    </div>

    @endif
</div>
</x-layouts.participant>
