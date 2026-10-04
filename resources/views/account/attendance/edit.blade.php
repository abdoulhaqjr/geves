{{-- Pointage de la présence d'une séance, par son modérateur ou son rapporteur.
     ⚠️ La liste est celle des AUTEURS des communications programmées dans la
     séance - pas l'effectif de l'édition. Elle tient donc sur un écran, sans
     recherche ni pagination : on appelle des gens, on ne fouille pas un annuaire. --}}
<x-layouts.participant :title="__('Présence de la séance')" :subtitle="$session->label">
<div class="account-attendance" style="display:grid;grid-template-columns:minmax(0,1fr);gap:16px;">
    <div style="display:flex;gap:8px;flex-wrap:wrap;align-items:center;">
        <a href="{{ route($backRoute) }}" class="ukc-btn ukc-btn--ghost ukc-btn--sm">
            <x-ukc.icon name="chevron" :size="16" />
            {{ $backRoute === 'account.moderation' ? __('Mes séances à animer') : __('Mes séances à rapporter') }}
        </a>
        {{-- ⚠️ `target` nommé : si la salle est déjà ouverte dans un onglet, le
             navigateur la RAMÈNE au lieu d'en ouvrir une seconde - deux
             connexions, deux vignettes et de l'écho (cf. MeetableRegistry). --}}
        @if ($meetingUrl)
            <a href="{{ $meetingUrl }}" id="retourVisio"
               @if ($meetingTab) target="{{ $meetingTab }}" @endif
               class="ukc-btn ukc-btn--green ukc-btn--sm">
                <x-ukc.icon name="video" :size="16" /> {{ __('Retour dans la visio') }}
            </a>
        @endif
        @if ($validated)
            <span class="ukc-pill ukc-pill--ok">{{ __('Rapport validé · présence figée') }}</span>
        @elseif ($frozen)
            <span class="ukc-pill ukc-pill--muted">{{ __('Édition archivée · lecture seule') }}</span>
        @endif
    </div>

    <div class="ukc-card">
        <div style="font-size:12px;color:var(--ukc-muted);text-transform:uppercase;letter-spacing:.04em;">
            {{ $session->edition?->colloque?->name ?: __('Colloque') }} · {{ $session->edition?->title ?: $session->edition?->year }}
        </div>
        <h3 style="margin:2px 0 4px;">{{ $session->label }}</h3>
        <div style="font-size:13px;color:var(--ukc-muted);">
            @if ($session->starts_at){{ $session->starts_at->translatedFormat('l j F · H:i') }}@endif
            @if ($session->ends_at) – {{ $session->ends_at->translatedFormat('H:i') }}@endif
            @if ($session->room) · {{ $session->room }}@endif
        </div>

        @if ($roster)
            <div style="display:flex;flex-wrap:wrap;gap:8px;margin-top:14px;">
                <span class="ukc-pill ukc-pill--ok">{{ __('Présents') }} : <strong>{{ $counts['present'] }}</strong></span>
                <span class="ukc-pill ukc-pill--err">{{ __('Absents') }} : <strong>{{ $counts['absent'] }}</strong></span>
                <span class="ukc-pill ukc-pill--muted">{{ __('Non pointés') }} : <strong>{{ $counts['unmarked'] }}</strong></span>
                <span class="ukc-pill ukc-pill--muted">{{ __('Attendus') }} : <strong>{{ $counts['expected'] }}</strong></span>
            </div>
        @endif
    </div>

    @if (! $roster)
        {{-- Pas de communication programmée : il n'y a personne à appeler. Le dire,
             plutôt que de retomber sur la liste des inscrits de l'édition. --}}
        <div class="ukc-card" style="text-align:center;">
            <span class="ico" style="color:var(--ukc-muted);"><x-ukc.icon name="users" :size="28" /></span>
            <h3 style="justify-content:center;">{{ __('Aucune communication programmée') }}</h3>
            <p style="color:var(--ukc-muted);margin-top:6px;">
                {{ __('La présence se pointe sur les auteurs des communications de la séance. Dès qu’une communication y sera programmée, ses auteurs apparaîtront ici.') }}
            </p>
        </div>
    @elseif ($notStarted)
        <div class="ukc-card" style="text-align:center;">
            <span class="ico" style="color:var(--ukc-muted);"><x-ukc.icon name="clock" :size="28" /></span>
            <h3 style="justify-content:center;">{{ __('Pointage pas encore ouvert') }}</h3>
            <p style="color:var(--ukc-muted);margin-top:6px;">
                {{ __('Vous pourrez pointer la présence dès le début de la séance') }}
                @if ($session->starts_at) - {{ $session->starts_at->translatedFormat('l j F Y à H:i') }}@endif.
            </p>
        </div>
    @else
        <form method="POST" action="{{ route('account.attendance.update', $session) }}" class="ukc-card">
            @csrf

            @if (! $canWrite)
                <p style="color:var(--ukc-muted);font-size:13px;margin:0 0 12px;display:flex;align-items:center;gap:6px;">
                    <x-ukc.icon name="lock" :size="16" />
                    {{ $frozen
                        ? __('Cette édition est archivée : la présence reste consultable mais n’est plus modifiable.')
                        : __('Le rapport de séance a été validé par le secrétariat : la présence est figée avec lui.') }}
                </p>
            @endif

            <div style="overflow-x:auto;">
                <table class="ukc-table" style="width:100%;border-collapse:collapse;">
                    <thead>
                        <tr>
                            <th style="text-align:left;">{{ __('Auteur') }}</th>
                            <th style="text-align:left;">{{ __('Communication') }}</th>
                            <th style="text-align:left;width:1%;white-space:nowrap;">{{ __('Présence') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($roster as $ligne)
                            @php($participant = $ligne['participant'])
                            @php($verdict = $participant ? ($verdicts[$participant->id] ?? '') : '')
                            <tr>
                                <td>
                                    <strong>{{ $ligne['name'] }}</strong>
                                    <div style="font-size:12.5px;color:var(--ukc-muted);">
                                        {{ $ligne['corresponding'] ? __('Auteur correspondant') : __('Co-auteur') }}
                                        @if ($ligne['institution']) · {{ $ligne['institution'] }}@endif
                                    </div>
                                    {{-- ⚠️ Le modérateur doit voir ce qu'il n'a PAS posé
                                         lui-même : il endosse ces chiffres dans le PV. --}}
                                    @if ($participant && in_array($participant->id, $viaVisio, true))
                                        <div style="font-size:12px;color:var(--ukc-muted);display:inline-flex;align-items:center;gap:4px;margin-top:3px;">
                                            <x-ukc.icon name="video" :size="13" /> {{ __('Entré en visio') }}
                                        </div>
                                    @endif
                                </td>
                                <td style="font-size:13px;color:var(--ukc-muted);">
                                    @foreach ($ligne['communications'] as $titre)
                                        <div>{{ $titre }}</div>
                                    @endforeach
                                </td>
                                <td style="white-space:nowrap;">
                                    @if ($participant)
                                        {{-- Un groupe de radios : « non pointé » est une
                                             valeur comme les autres, pour pouvoir revenir
                                             sur une erreur de pointage. --}}
                                        <div class="ukc-presence" role="group"
                                             aria-label="{{ __('Présence de :nom', ['nom' => $ligne['name']]) }}">
                                            @foreach (['present' => __('Présent'), 'absent' => __('Absent'), '' => __('Non pointé')] as $valeur => $libelle)
                                                <label class="ukc-presence__opt ukc-presence__opt--{{ $valeur ?: 'none' }}">
                                                    <input type="radio" name="presence[{{ $participant->id }}]" value="{{ $valeur }}"
                                                           @checked($verdict === $valeur) @disabled(! $canWrite)>
                                                    <span>{{ $libelle }}</span>
                                                </label>
                                            @endforeach
                                        </div>
                                    @else
                                        {{-- ⚠️ Co-auteur sans inscription sur l'édition : il
                                             est bien auteur de la communication (le cacher
                                             ferait croire à une liste d'auteurs tronquée),
                                             mais il n'y a aucune inscription à laquelle
                                             rattacher une présence. --}}
                                        <span class="ukc-pill ukc-pill--muted" title="{{ __('Cette personne n’a pas d’inscription sur cette édition.') }}">
                                            {{ __('Non inscrit') }}
                                        </span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if ($canWrite)
                <div style="display:flex;gap:8px;flex-wrap:wrap;align-items:center;margin-top:16px;">
                    <button type="submit" class="ukc-btn ukc-btn--green">
                        <x-ukc.icon name="check" :size="16" /> {{ __('Enregistrer la présence') }}
                    </button>
                    <span style="font-size:12.5px;color:var(--ukc-muted);">
                        {{ __('Vous pouvez enregistrer plusieurs fois au cours de la séance : rien n’est effacé.') }}
                    </span>
                </div>
            @endif
        </form>
    @endif
</div>

@if ($meetingUrl)
<script>
    // ⚠️ Revenir dans la salle SANS la recharger. Cette page s'ouvre depuis la
    // salle dans un nouvel onglet : recharger la salle en ferait sortir puis
    // rentrer le modérateur sous les yeux de toute l'assistance. Si l'onglet
    // d'origine vit encore, on le remet simplement devant.
    (function () {
        var lien = document.getElementById('retourVisio');
        if (!lien) return;
        lien.addEventListener('click', function (e) {
            var salle = null;
            // Une poignée morte (onglet fermé, autre origine) ne vaut rien : on
            // laisse alors le lien faire son travail, son `target` nommé retrouve
            // l'onglet de la salle ou en ouvre un.
            try { salle = window.opener && !window.opener.closed ? window.opener : null; } catch (err) { salle = null; }
            if (!salle) return;
            e.preventDefault();
            try { salle.focus(); } catch (err) { /* refusé : la vérification ci-dessous rattrape */ }
            try { window.close(); } catch (err) { /* le navigateur ne ferme que ce qu'il a ouvert */ }
            // ⚠️ VÉRIFIÉ, pas supposé : certains navigateurs ignorent `focus()` sur
            // un autre onglet. S'il avait pris, cette page serait masquée ; elle est
            // encore visible, donc le clic n'a rien fait - et un bouton qui ne fait
            // rien est pire que le rechargement qu'on voulait éviter.
            setTimeout(function () {
                if (document.visibilityState === 'visible') window.location.href = lien.href;
            }, 400);
        });
    })();
</script>
@endif
</x-layouts.participant>
