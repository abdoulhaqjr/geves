{{--
    Programme PERSONNEL du participant, jour par jour.

    ⚠️ Ce n'est PAS le programme du colloque (qui a sa page publique) : seules ses
    communications, les séances qui le concernent et ses formations. Six salles en
    parallèle sur cinq jours rendraient la liste aussi illisible que le programme
    complet, et il faudrait la chercher.

    ⚠️ Les onglets de jour filtrent SANS rechargement (Alpine), comme Programme >
    Ateliers côté back-office : on compare deux jours en allant et venant, et
    chaque aller-retour serveur ferait perdre la position de défilement.

    Reçoit : $agenda (jour « Y-m-d » => entrées), $jourOuvert, $event.
--}}
@php
    $aujourdhui = \Illuminate\Support\Carbon::today()->format('Y-m-d');
@endphp
<section class="ukc-card tdb-agenda" x-data="{ jour: @js($jourOuvert) }">
    <h3>
        <x-ukc.icon name="calendar" :size="20" />
        {{ __('Mon programme') }}
        @if ($event)
            <a href="{{ route('public.colloque', $event->slug) }}#programme" class="tdb-link">{{ __('Programme complet') }}</a>
        @endif
    </h3>
    <p class="tdb-sub">{{ __('Vos passages, les séances qui vous concernent et vos formations.') }}</p>

    <div class="tdb-days" role="tablist" aria-label="{{ __('Jours du colloque') }}">
        @foreach ($agenda as $cle => $entrees)
            <button type="button" role="tab"
                    :aria-selected="jour === @js($cle) ? 'true' : 'false'"
                    :class="{ 'is-on': jour === @js($cle) }"
                    class="tdb-day"
                    @click="jour = @js($cle)">
                <span class="d">
                    @if ($cle === 'sans-date')
                        {{ __('Date à préciser') }}
                    @else
                        {{ \Illuminate\Support\Str::ucfirst(\Illuminate\Support\Carbon::parse($cle)->locale('fr')->translatedFormat('D j M')) }}
                    @endif
                </span>
                @if ($cle === $aujourdhui)
                    <span class="t">{{ __('aujourd’hui') }}</span>
                @endif
            </button>
        @endforeach
    </div>

    @foreach ($agenda as $cle => $entrees)
        <ol class="tdb-ag" x-show="jour === @js($cle)" x-cloak>
            @foreach ($entrees as $e)
                <li @class(['tdb-ag__row', 'is-mine' => $e['genre'] === 'passage'])>
                    <div class="h">
                        <span class="d">{{ optional($e['debut'])->format('H:i') ?: '--:--' }}</span>
                        @if ($e['fin'])<span class="f">{{ $e['fin']->format('H:i') }}</span>@endif
                    </div>
                    <span class="rail" aria-hidden="true"></span>
                    <div class="b">
                        <div class="t">
                            @if ($e['genre'] === 'passage')
                                {{-- ⚠️ Un co-auteur ne présente pas seul, mais il est en salle
                                     et peut co-présenter : son créneau figure ici comme celui
                                     de l'auteur correspondant, avec son rôle NOMMÉ. --}}
                                <span @class(['tdb-ag__me', 'is-co' => $e['role'] === 'coauteur'])>
                                    {{ $e['role'] === 'coauteur' ? __('Je co-présente') : __('Je présente') }}
                                </span>
                            @endif
                            @if ($e['url'])
                                <a href="{{ $e['url'] }}">{{ \Illuminate\Support\Str::limit($e['titre'], 90) }}</a>
                            @else
                                {{ \Illuminate\Support\Str::limit($e['titre'], 90) }}
                            @endif
                        </div>
                        <div class="m">
                            {{ collect([
                                $e['lieu'] !== '' ? $e['lieu'] : null,
                                $e['detail'] !== '' ? $e['detail'] : null,
                                $e['ref'] !== '' ? $e['ref'] : null,
                            ])->filter()->implode(' - ') }}
                        </div>
                    </div>
                    <div class="a">
                        @if ($e['visio'])
                            <a href="{{ $e['visio'] }}" class="ukc-btn ukc-btn--green ukc-btn--sm">
                                <x-ukc.icon name="video" :size="14" /> {{ __('Rejoindre') }}
                            </a>
                        @elseif ($e['fait'])
                            <span class="ukc-pill ukc-pill--ok">{{ __('Passage constaté') }}</span>
                        @elseif ($e['genre'] === 'formation')
                            <span class="ukc-pill ukc-pill--info">{{ __('Formation') }}</span>
                        @elseif ($e['genre'] === 'pleniere')
                            <span class="ukc-pill ukc-pill--muted">{{ __('Plénière') }}</span>
                        @endif
                    </div>
                </li>
            @endforeach
        </ol>
    @endforeach
</section>
