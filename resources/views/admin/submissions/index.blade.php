<x-layouts.admin title="Soumissions" subtitle="Communications & résumés">
    <x-slot:actions>
        {{-- ⚠️ L'adresse est construite par le CONTRÔLEUR, depuis les mêmes
             filtres que la liste : écrite ici à la main, elle avait déjà oublié
             deux paramètres (cf. SubmissionController::index). --}}
        <span data-filter-part="submissions-export">
            <a class="ukc-btn ukc-btn--ghost ukc-btn--sm" href="{{ $exportUrl }}">
                <x-ukc.icon name="download" :size="14" /> CSV
            </a>
        </span>
        <a class="ukc-btn ukc-btn--ghost ukc-btn--sm"
           href="{{ route('admin.submissions.bibliography', ['format' => 'bibtex'] + request()->only('event_id')) }}"
           title="Actes des communications acceptées (BibTeX)">
            <x-ukc.icon name="book" :size="14" /> BibTeX
        </a>
        <a class="ukc-btn ukc-btn--ghost ukc-btn--sm"
           href="{{ route('admin.submissions.bibliography', ['format' => 'ris'] + request()->only('event_id')) }}"
           title="Actes des communications acceptées (RIS)">
            <x-ukc.icon name="book" :size="14" /> RIS
        </a>
    </x-slot:actions>

    <div class="admin-page admin-submissions" x-data="{ pv: null }" @keydown.escape.window="pv = null">
        {{-- Sélecteur d'événement en tête, comme les pages Paiements et Participants. --}}
        <div style="max-width:340px;margin-bottom:16px;">
            <div class="ukc-field" style="margin:0;">
                <label>Événement</label>
                <select onchange="window.location='{{ route('admin.submissions.index') }}?event_id='+this.value">
                    <option value="">Toutes les événements</option>
                    @foreach ($events as $ed)<option value="{{ $ed->id }}" @selected((string) $ed->id === $event_id)>{{ $ed->title ?: $ed->year }}</option>@endforeach
                </select>
            </div>
        </div>

        @include('admin.submissions._nav', ['current' => 'liste'])

        {{-- Indicateurs CLIQUABLES : chaque carte ouvre la liste filtrée sur ce
             qu'elle compte (cf. SubmissionController::indicateurs). La carte
             allumée dit où l'on est ; cliquer dessus en ressort.
             ⚠️⚠️ **LA RANGÉE EST UNE RÉGION ÉCHANGÉE**, parce que les liens des
             cartes EMPORTENT les autres filtres : sans cela, filtrer un axe en
             AJAX laisserait les six cartes pointer vers l'axe précédent, et
             cliquer l'une d'elles ramènerait le filtre qu'on venait de quitter.
             Les CHIFFRES, eux, ne bougent pas - ils sont comptés sur le
             périmètre de l'événement, pas sur les filtres courants. --}}
        <div data-filter-part="submissions-kpis">
            <div class="ukc-stats">
                @foreach ($kpis as $kpi)
                    <a href="{{ $kpi['url'] }}" class="ukc-stat-card tw-stat{{ $kpi['active'] ? ' is-on' : '' }}" @if ($kpi['active']) aria-current="true" @endif>
                        <div class="tw-stat__icon tw-stat__icon--{{ $kpi['teinte'] }}"><x-ukc.icon :name="$kpi['icone']" :size="20" /></div>
                        <div class="lbl">{{ $kpi['label'] }}</div>
                        <div class="val {{ $kpi['val'] }}">{{ $kpi['valeur'] }}</div>
                        @if ($kpi['cle'] === 'total')<x-ukc.trend :trend="$trendTotal" period="7 j" />@endif
                    </a>
                @endforeach
            </div>

        </div>

        {{-- Filtres (GET, auto-submit) --}}
        {{-- Les filtres restent sur une ligne quand l'espace le permet et se replient
             sinon ; l'événement reste en champ caché pour préserver le périmètre. --}}
        {{-- ⚠️ Filtrage en AJAX (`data-filter`, cf. app.js) : la recherche
             s'applique à la frappe, sans valider ni recharger la page. --}}
        <form method="GET" class="filter-bar filter-bar--inline" data-filter="submissions" data-filter-preserve-empty="event_id">
            <input type="hidden" name="event_id" value="{{ $event_id }}">
            {{-- ⚠️⚠️ LE STATUT SE CHOISIT SUR LES CARTES, PAS DANS UNE LISTE
                 DÉROULANTE : les six indicateurs du haut le disent déjà, en
                 annonçant leur nombre. Mais il reste un CHAMP CACHÉ, comme
                 l'événement : la barre de filtres poste tout le formulaire, et
                 sans lui, taper une recherche ou changer d'axe REPERDRAIT le
                 statut qu'on vient de choisir sur une carte. --}}
            <input type="hidden" name="status" value="{{ $status }}">
            @if (request()->filled('sort'))
                <input type="hidden" name="sort" value="{{ $sort }}">
                <input type="hidden" name="dir" value="{{ $dir }}">
            @endif
            <select name="reviews" aria-label="Filtrer par avancement des évaluations">
                <option value="">Toutes les évaluations</option>
                @foreach ($reviewStates as $key => $label)<option value="{{ $key }}" @selected($reviews === $key)>{{ $label }}</option>@endforeach
            </select>
            <select name="type">
                <option value="">Tous les types</option>
                <option value="oral" @selected($type === 'oral')>Orale</option>
                <option value="poster" @selected($type === 'poster')>Poster</option>
            </select>
            <select name="presentation_mode" aria-label="Filtrer par mode de présentation">
                <option value="">Toutes les présentations</option>
                <option value="online" @selected($presentationMode === 'online')>En ligne</option>
                <option value="onsite" @selected($presentationMode === 'onsite')>En présentiel</option>
            </select>
            <div class="ukc-submissions-track">
                <select name="track" data-tom data-tom-dropdown-search aria-label="Filtrer par axe thématique">
                    <option value="">Tous les axes</option>
                    @foreach ($tracks as $t)<option value="{{ $t }}" @selected($track === $t)>{{ $t }}</option>@endforeach
                </select>
            </div>
            <input type="search" name="search" value="{{ $search }}" placeholder="Titre, auteur, réf.…" style="min-width:200px;">
            <label style="display:inline-flex;align-items:center;gap:6px;font-size:13px;white-space:nowrap;cursor:pointer;">
                <input type="checkbox" name="unlinked" value="1" @checked($unlinked)> Co-auteurs non rattachés
            </label>
            {{-- ⚠️⚠️ **« RÉINITIALISER » A SA PROPRE RÉGION ÉCHANGÉE** : il
                 n'apparaît que selon les filtres, et il vivait HORS de toute
                 région - donc il ne se montrait qu'au prochain rechargement
                 complet. Mesuré : taper « eau » filtrait bien la liste et
                 l'URL, et le lien restait absent - on ne pouvait plus sortir du
                 filtre sans vider les champs un par un.
                 ⚠️ Une région IMBRIQUÉE dans le formulaire est sans danger :
                 `app.js` ne remplace que le CONTENU de la région (innerHTML),
                 donc aucun champ n'est touché et le curseur de qui tape reste
                 en place - c'est la raison même de la règle « le formulaire
                 reste hors des régions échangées ». --}}
            <span data-filter-part="submissions-reset">
                @if ($filtreActif)
                    <a href="{{ $resetUrl }}" class="ukc-btn ukc-btn--ghost ukc-btn--sm">Réinitialiser</a>
                @endif
            </span>
        </form>

        <div class="ukc-submissions-result-count" data-filter-part="submissions-count">
            <strong>{{ $submissions->total() }}</strong> {{ $submissions->total() === 1 ? 'soumission' : 'soumissions' }}{{ $filtreActif ? ($submissions->total() === 1 ? ' correspond aux filtres.' : ' correspondent aux filtres.') : ' dans la liste.' }}
        </div>

        <div class="ukc-card" style="padding:0;" data-filter-part="submissions-table">
            <div class="ukc-table-wrap">
                <table class="ukc-table">
                    <thead>
                        <tr>
                            <x-ukc.th sort="ref_code" :current="$sort" :dir="$dir">Réf.</x-ukc.th>
                            <x-ukc.th sort="title" :current="$sort" :dir="$dir">Communication</x-ukc.th>
                            <x-ukc.th sort="author" :current="$sort" :dir="$dir">Déposant</x-ukc.th>
                            <th>Présentation</th>
                            <x-ukc.th sort="track" :current="$sort" :dir="$dir">Axe</x-ukc.th>
                            <x-ukc.th sort="created_at" :current="$sort" :dir="$dir">Déposée</x-ukc.th>
                            <x-ukc.th sort="status" :current="$sort" :dir="$dir">Statut</x-ukc.th>
                            <th>Évaluations</th>
                            <th style="text-align:right;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($submissions as $s)
                            <tr>
                                <td><code>{{ $s->ref_code }}</code></td>
                                <td class="title-cell">
                                    <strong>{{ \Illuminate\Support\Str::limit($s->title, 64) }}</strong>
                                    @php $words = str_word_count(strip_tags((string) $s->abstract)); @endphp
                                    <div class="meta">
                                        {{ $s->type === 'poster' ? 'Poster' : 'Orale' }}
                                        - {{ count($s->authors ?? []) ?: 1 }} auteur(s){{ $words ? ' - '.$words.' mots' : '' }}
                                    </div>
                                </td>
                                <td>{{ $s->participant?->full_name ?? ($s->correspondingAuthorName() ?: '-') }}
                                    @if (($s->unlinked_authors_count ?? 0) > 0)
                                        <span class="ukc-pill ukc-pill--warn" style="margin-left:6px;" title="Co-auteur(s) non rattaché(s) à un compte">{{ $s->unlinked_authors_count }} non rattaché(s)</span>
                                    @endif
                                </td>
                                <td>
                                    @if ($s->presentationModeLabel())
                                        <span class="ukc-pill ukc-pill--{{ $s->presentation_mode === 'online' ? 'info' : 'ok' }}">{{ $s->presentationModeLabel() }}</span>
                                    @else
                                        <span style="color:var(--ukc-light);">-</span>
                                    @endif
                                </td>
                                <td>@if ($s->track)<span class="axis-tag">{{ \Illuminate\Support\Str::limit($s->track, 22) }}</span>@else - @endif</td>
                                <td style="white-space:nowrap;">{{ $s->created_at?->format('d/m/Y') }}</td>
                                <td><span class="ukc-pill ukc-pill--{{ $statusPills[$s->status] ?? 'muted' }}">{{ $statuses[$s->status] ?? $s->status }}</span></td>
                                {{-- Avancement de l’évaluation : rendues / demandées, et le retard
                                     éventuel. Sans cela, le comité devait ouvrir chaque fiche. --}}
                                @php $rev = \App\Models\Review::progressOf($s->reviews); @endphp
                                <td style="white-space:nowrap;">
                                    @if ($rev['total'] === 0)
                                        <span style="color:var(--ukc-light);">-</span>
                                    @else
                                        <span style="font-weight:700;">{{ $rev['done'] }}/{{ $rev['total'] }}</span>
                                        @if ($rev['late'] > 0)
                                            <span class="ukc-pill ukc-pill--err" style="margin-left:6px;"
                                                  title="{{ $rev['late'] }} évaluation(s) en retard">+{{ $rev['lateDays'] }} j</span>
                                        @endif
                                    @endif
                                </td>
                                <td>
                                    @php
                                        $pvData = [
                                            'ref' => $s->ref_code,
                                            'author' => $s->participant?->full_name ?? ($s->correspondingAuthorName() ?: '-'),
                                            'correspondingAuthor' => $s->correspondingAuthorName() ?: '-',
                                            'type' => $s->type === 'poster' ? 'Poster' : 'Orale',
                                            'presentationMode' => $s->presentationModeLabel() ?? '-',
                                            'participationMode' => $s->participant?->participationModeLabel() ?? '-',
                                            'axis' => $s->track ?: '-',
                                            'status' => $statuses[$s->status] ?? $s->status,
                                            'statusPill' => $statusPills[$s->status] ?? 'muted',
                                            'date' => $s->created_at?->format('d/m/Y') ?? '-',
                                            'title' => $s->title,
                                            'coauthors' => collect($s->authors ?? [])->filter(fn ($a) => empty($a['corresponding']))
                                                ->map(fn ($a) => \App\Support\EventNameFormat::author($s->event, $a))->filter()->values()->all(),
                                            'keywords' => \App\Support\SubmissionKeywords::join($s->keywords, (new \App\Support\SubmissionFormConfig($s->event))->keywordSeparator()) ?? '',
                                            'abstract' => trim((string) preg_replace('/\s+/', ' ', strip_tags((string) $s->abstract))),
                                            'url' => route('admin.submissions.show', $s),
                                        ];
                                    @endphp
                                    <div class="ukc-actions" style="justify-content:flex-end;">
                                        <button type="button" class="ukc-btn--icon" title="Voir l’aperçu" @click="pv = {{ \Illuminate\Support\Js::from($pvData) }}"><x-ukc.icon name="eye" :size="16" /></button>
                                        <a class="ukc-btn--icon" title="Ouvrir la fiche" href="{{ route('admin.submissions.show', $s) }}"><x-ukc.icon name="external" :size="16" /></a>
                                        @if (auth()->user()->canOnEdition('editions_write', $s->event))
                                            <form method="POST" action="{{ route('admin.submissions.destroy', $s) }}"
                                                  data-confirm="Supprimer définitivement le résumé {{ $s->ref_code }} « {{ \Illuminate\Support\Str::limit($s->title, 80) }} » et ses versions ? Les évaluations liées seront supprimées, les créneaux libérés et les attestations concernées révoquées.">
                                                @csrf @method('DELETE')
                                                <button type="submit" class="ukc-btn--icon ukc-btn--danger" title="Supprimer le résumé {{ $s->ref_code }}" aria-label="Supprimer le résumé {{ $s->ref_code }}">
                                                    <x-ukc.icon name="trash" :size="16" />
                                                </button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="9" style="padding:24px;color:var(--ukc-muted);text-align:center;">Aucune soumission ne correspond aux filtres.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div style="margin-top:12px;" data-filter-part="submissions-foot">{{ $submissions->links() }}</div>

        {{-- Aperçu de la soumission (panneau latéral Tailwise) --}}
        <x-ukc.slideover open="pv" onClose="pv = null" width="460px">
            <x-slot:header><span x-text="pv?.ref"></span> - Aperçu</x-slot:header>
            <div class="detail-grid">
                <div class="k">Référence</div><div class="v" x-text="pv?.ref"></div>
                <div class="k">Déposant</div><div class="v" x-text="pv?.author"></div>
                <div class="k">Auteur correspondant</div><div class="v" x-text="pv?.correspondingAuthor"></div>
                <div class="k">Type de communication</div><div class="v" x-text="pv?.type"></div>
                <div class="k">Mode de présentation</div><div class="v" x-text="pv?.presentationMode"></div>
                <div class="k">Participation au colloque</div><div class="v" x-text="pv?.participationMode"></div>
                <div class="k">Axe</div><div class="v" x-text="pv?.axis"></div>
                <div class="k">Statut</div><div class="v"><span class="ukc-pill" :class="'ukc-pill--' + (pv?.statusPill)" x-text="pv?.status"></span></div>
                <div class="k">Déposée le</div><div class="v" x-text="pv?.date"></div>
            </div>
            <div class="ukc-form-row">
                <label>Titre</label>
                <div style="font-weight:700;font-size:14px;" x-text="pv?.title"></div>
            </div>
            <div class="ukc-form-row" x-show="pv?.coauthors && pv.coauthors.length" x-cloak>
                <label>Co-auteurs</label>
                <div style="font-size:13px;display:flex;flex-direction:column;gap:2px;">
                    <template x-for="(c, i) in (pv?.coauthors || [])" :key="i"><div x-text="c"></div></template>
                </div>
            </div>
            <div class="ukc-form-row" x-show="pv?.keywords">
                <label>Mots-clés</label>
                <div style="font-size:13px;color:var(--ukc-muted);" x-text="pv?.keywords"></div>
            </div>
            <div class="ukc-form-row">
                <label>Résumé</label>
                <div class="abstract-box abstract-box--full" x-text="pv?.abstract || '-'"></div>
            </div>
            <x-slot:footer>
                <a class="ukc-btn ukc-btn--ghost" :href="pv?.url">Ouvrir la fiche</a>
                <button type="button" class="ukc-btn ukc-btn--blue" @click="pv = null">Fermer</button>
            </x-slot:footer>
        </x-ukc.slideover>
    </div>
</x-layouts.admin>
