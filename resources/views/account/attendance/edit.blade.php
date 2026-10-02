{{-- Pointage de la présence d'une séance, par son modérateur ou son rapporteur.
     Pensé pour être tenu PENDANT la séance : trois boutons par personne, une
     recherche, et un enregistrement qui ne touche que ce qui est à l'écran. --}}
<x-layouts.participant :title="__('Présence de la séance')" :subtitle="$session->label">
<div class="account-attendance" style="display:grid;grid-template-columns:minmax(0,1fr);gap:16px;">
    <div style="display:flex;gap:8px;flex-wrap:wrap;align-items:center;">
        <a href="{{ route($backRoute) }}" class="ukc-btn ukc-btn--ghost ukc-btn--sm">
            <x-ukc.icon name="chevron" :size="16" />
            {{ $backRoute === 'account.moderation' ? __('Mes séances à animer') : __('Mes séances à rapporter') }}
        </a>
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

        {{-- Le comptage porte sur la séance ENTIÈRE, pas sur la page affichée :
             « 12 présents » ne doit pas changer quand on tourne la page. --}}
        <div style="display:flex;flex-wrap:wrap;gap:8px;margin-top:14px;">
            <span class="ukc-pill ukc-pill--ok">{{ __('Présents') }} : <strong>{{ $counts['present'] }}</strong></span>
            <span class="ukc-pill ukc-pill--err">{{ __('Absents') }} : <strong>{{ $counts['absent'] }}</strong></span>
            <span class="ukc-pill ukc-pill--warn">{{ __('Excusés') }} : <strong>{{ $counts['excused'] }}</strong></span>
            <span class="ukc-pill ukc-pill--muted">{{ __('Non pointés') }} : <strong>{{ $counts['unmarked'] }}</strong></span>
            <span class="ukc-pill ukc-pill--muted">{{ __('Inscrits') }} : <strong>{{ $counts['expected'] }}</strong></span>
        </div>
    </div>

    @if ($notStarted)
        {{-- La séance n'a pas commencé : il n'y a encore personne à pointer. --}}
        <div class="ukc-card" style="text-align:center;">
            <span class="ico" style="color:var(--ukc-muted);"><x-ukc.icon name="clock" :size="28" /></span>
            <h3 style="justify-content:center;">{{ __('Pointage pas encore ouvert') }}</h3>
            <p style="color:var(--ukc-muted);margin-top:6px;">
                {{ __('Vous pourrez pointer la présence dès le début de la séance') }}
                @if ($session->starts_at) - {{ $session->starts_at->translatedFormat('l j F Y à H:i') }}@endif.
            </p>
        </div>
    @else
        <div class="ukc-card">
            <form method="GET" action="{{ route('account.attendance.edit', $session) }}"
                  style="display:flex;gap:8px;flex-wrap:wrap;align-items:flex-end;margin-bottom:4px;">
                <div class="ukc-field" style="margin:0;flex:1 1 240px;">
                    <label for="q">{{ __('Rechercher une personne') }}</label>
                    <input id="q" name="q" type="search" value="{{ $recherche }}"
                           placeholder="{{ __('Nom, prénom, e-mail, institution') }}">
                </div>
                <button type="submit" class="ukc-btn ukc-btn--outline ukc-btn--sm">
                    <x-ukc.icon name="search" :size="16" /> {{ __('Chercher') }}
                </button>
                @if ($recherche !== '')
                    <a href="{{ route('account.attendance.edit', $session) }}" class="ukc-btn ukc-btn--ghost ukc-btn--sm">
                        {{ __('Effacer') }}
                    </a>
                @endif
            </form>
        </div>

        <form method="POST" action="{{ route('account.attendance.update', $session) }}" class="ukc-card">
            @csrf
            <input type="hidden" name="q" value="{{ $recherche }}">

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
                            <th style="text-align:left;">{{ __('Participant') }}</th>
                            <th style="text-align:left;">{{ __('Qualité') }}</th>
                            <th style="text-align:left;width:1%;white-space:nowrap;">{{ __('Présence') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($participants as $p)
                            @php($verdict = $verdicts[$p->id] ?? '')
                            <tr>
                                <td>
                                    <strong>{{ $p->full_name }}</strong>
                                    @if ($p->institution)
                                        <div style="font-size:12.5px;color:var(--ukc-muted);">{{ $p->institution }}</div>
                                    @endif
                                </td>
                                <td style="font-size:13px;color:var(--ukc-muted);">{{ $p->roleLabel() }}</td>
                                <td style="white-space:nowrap;">
                                    {{-- Un groupe de radios : « non pointé » est une
                                         valeur comme les autres, pour pouvoir revenir
                                         sur une erreur de pointage. --}}
                                    <div class="ukc-presence" role="group"
                                         aria-label="{{ __('Présence de :nom', ['nom' => $p->full_name]) }}">
                                        @foreach (['present' => __('Présent'), 'absent' => __('Absent'), 'excused' => __('Excusé'), '' => __('Non pointé')] as $valeur => $libelle)
                                            <label class="ukc-presence__opt ukc-presence__opt--{{ $valeur ?: 'none' }}">
                                                <input type="radio" name="presence[{{ $p->id }}]" value="{{ $valeur }}"
                                                       @checked($verdict === $valeur) @disabled(! $canWrite)>
                                                <span>{{ $libelle }}</span>
                                            </label>
                                        @endforeach
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" style="color:var(--ukc-muted);padding:14px 6px;">
                                    {{ $recherche !== ''
                                        ? __('Aucun participant ne correspond à cette recherche.')
                                        : __('Aucun participant inscrit sur cette édition.') }}
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($participants->hasPages())
                <div style="margin-top:12px;">{{ $participants->links() }}</div>
            @endif

            @if ($canWrite && count($participants) > 0)
                <div style="display:flex;gap:8px;flex-wrap:wrap;align-items:center;margin-top:16px;">
                    <button type="submit" class="ukc-btn ukc-btn--green">
                        <x-ukc.icon name="check" :size="16" /> {{ __('Enregistrer la présence') }}
                    </button>
                    {{-- ⚠️ Dit explicitement que l'enregistrement est partiel : on
                         peut pointer par lots sans craindre d'effacer le reste. --}}
                    <span style="font-size:12.5px;color:var(--ukc-muted);">
                        {{ __('Seules les personnes affichées sont enregistrées : les autres gardent leur pointage.') }}
                    </span>
                </div>
            @endif
        </form>
    @endif
</div>
</x-layouts.participant>
