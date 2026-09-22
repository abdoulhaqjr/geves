<x-layouts.admin title="Soumissions" subtitle="Communications & résumés">
    <x-slot:actions>
        <a class="ukc-btn ukc-btn--ghost ukc-btn--sm"
           href="{{ route('admin.submissions.export', request()->only('edition_id', 'status', 'type', 'track', 'search')) }}">
            <x-ukc.icon name="download" :size="14" /> CSV
        </a>
        <a class="ukc-btn ukc-btn--ghost ukc-btn--sm"
           href="{{ route('admin.submissions.bibliography', ['format' => 'bibtex'] + request()->only('edition_id')) }}"
           title="Actes des communications acceptées (BibTeX)">
            <x-ukc.icon name="book" :size="14" /> BibTeX
        </a>
        <a class="ukc-btn ukc-btn--ghost ukc-btn--sm"
           href="{{ route('admin.submissions.bibliography', ['format' => 'ris'] + request()->only('edition_id')) }}"
           title="Actes des communications acceptées (RIS)">
            <x-ukc.icon name="book" :size="14" /> RIS
        </a>
    </x-slot:actions>

    <div class="admin-page admin-submissions" x-data="{ pv: null }" @keydown.escape.window="pv = null">
        {{-- Sous-navigation (fidèle au mockup admin-submissions) --}}
        <div class="ukc-nav-docs">
            <button type="button" class="active">Soumissions</button>
            <a href="{{ $edition_id !== '' ? route('admin.editions.form', $edition_id) : route('admin.editions.index') }}">Formulaires de soumissions</a>
            <a href="{{ route('admin.submissions.auto-assign', ['edition_id' => $edition_id]) }}">Attribution auto</a>
        </div>

        {{-- Indicateurs --}}
        <div class="ukc-stats">
            <div class="ukc-stat-card tw-stat"><div class="tw-stat__icon tw-stat__icon--blue"><x-ukc.icon name="inbox" :size="20" /></div><div class="lbl">Total</div><div class="val blue">{{ $stats['total'] }}</div><x-ukc.trend :trend="$trendTotal" period="7 j" /></div>
            <div class="ukc-stat-card tw-stat"><div class="tw-stat__icon tw-stat__icon--amber"><x-ukc.icon name="clock" :size="20" /></div><div class="lbl">En revue</div><div class="val warn">{{ $stats['underReview'] }}</div></div>
            <div class="ukc-stat-card tw-stat"><div class="tw-stat__icon tw-stat__icon--green"><x-ukc.icon name="check-circle" :size="20" /></div><div class="lbl">Acceptées</div><div class="val green">{{ $stats['accepted'] }}</div></div>
            <div class="ukc-stat-card tw-stat"><div class="tw-stat__icon tw-stat__icon--violet"><x-ukc.icon name="pen" :size="20" /></div><div class="lbl">Corrections</div><div class="val warn">{{ $stats['revision'] }}</div></div>
            <div class="ukc-stat-card tw-stat"><div class="tw-stat__icon tw-stat__icon--red"><x-ukc.icon name="close" :size="20" /></div><div class="lbl">Refusées</div><div class="val" style="color:#dc2626;">{{ $stats['rejected'] }}</div></div>
        </div>

        {{-- Filtres (GET, auto-submit) --}}
        <form method="GET" class="filter-bar">
            <select name="edition_id" onchange="this.form.submit()">
                <option value="">Toutes les éditions</option>
                @foreach ($editions as $ed)<option value="{{ $ed->id }}" @selected((string) $ed->id === $edition_id)>{{ $ed->title ?: $ed->year }}</option>@endforeach
            </select>
            <select name="status" onchange="this.form.submit()">
                <option value="">Tous les statuts</option>
                @foreach ($statuses as $key => $label)<option value="{{ $key }}" @selected($status === $key)>{{ $label }}</option>@endforeach
            </select>
            <select name="type" onchange="this.form.submit()">
                <option value="">Tous les types</option>
                <option value="oral" @selected($type === 'oral')>Orale</option>
                <option value="poster" @selected($type === 'poster')>Poster</option>
            </select>
            <select name="track" onchange="this.form.submit()" data-tom data-placeholder="Axe…">
                <option value="">Tous les axes</option>
                @foreach ($tracks as $t)<option value="{{ $t }}" @selected($track === $t)>{{ $t }}</option>@endforeach
            </select>
            <input type="search" name="search" value="{{ $search }}" placeholder="Titre, auteur, réf.…" style="min-width:200px;"
                   onkeydown="if(event.key==='Enter'){this.form.submit();}">
            <label style="display:inline-flex;align-items:center;gap:6px;font-size:13px;white-space:nowrap;cursor:pointer;">
                <input type="checkbox" name="unlinked" value="1" @checked($unlinked) onchange="this.form.submit()"> Co-auteurs non rattachés
            </label>
            @if ($search !== '' || $status !== '' || $edition_id !== '' || $type !== '' || $track !== '' || $unlinked)
                <a href="{{ route('admin.submissions.index') }}" class="ukc-btn ukc-btn--ghost ukc-btn--sm">Réinitialiser</a>
            @endif
        </form>

        <div class="ukc-card" style="padding:0;">
            <div class="ukc-table-wrap">
                <table class="ukc-table">
                    <thead>
                        <tr>
                            <x-ukc.th sort="ref_code" :current="$sort" :dir="$dir">Réf.</x-ukc.th>
                            <x-ukc.th sort="title" :current="$sort" :dir="$dir">Communication</x-ukc.th>
                            <th>Auteur(s)</th>
                            <x-ukc.th sort="type" :current="$sort" :dir="$dir">Type</x-ukc.th>
                            <x-ukc.th sort="track" :current="$sort" :dir="$dir">Axe</x-ukc.th>
                            <x-ukc.th sort="created_at" :current="$sort" :dir="$dir">Déposée</x-ukc.th>
                            <x-ukc.th sort="status" :current="$sort" :dir="$dir">Statut</x-ukc.th>
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
                                    <div class="meta">{{ count($s->authors ?? []) ?: 1 }} auteur(s)@if ($words) · {{ $words }} mots @endif</div>
                                </td>
                                <td>{{ $s->participant?->full_name ?? ($s->authors[0]['name'] ?? '—') }}
                                    @if (($s->unlinked_authors_count ?? 0) > 0)
                                        <span class="ukc-pill ukc-pill--warn" style="margin-left:6px;" title="Co-auteur(s) non rattaché(s) à un compte">{{ $s->unlinked_authors_count }} non rattaché·s</span>
                                    @endif
                                </td>
                                <td>{{ $s->type === 'poster' ? 'Poster' : 'Orale' }}</td>
                                <td>@if ($s->track)<span class="axis-tag">{{ \Illuminate\Support\Str::limit($s->track, 22) }}</span>@else — @endif</td>
                                <td style="white-space:nowrap;">{{ $s->created_at?->format('d/m/Y') }}</td>
                                <td><span class="ukc-pill ukc-pill--{{ $statusPills[$s->status] ?? 'muted' }}">{{ $statuses[$s->status] ?? $s->status }}</span></td>
                                <td>
                                    @php
                                        $pvData = [
                                            'ref' => $s->ref_code,
                                            'author' => $s->participant?->full_name ?? ($s->authors[0]['name'] ?? '—'),
                                            'type' => $s->type === 'poster' ? 'Poster' : 'Orale',
                                            'axis' => $s->track ?: '—',
                                            'status' => $statuses[$s->status] ?? $s->status,
                                            'statusPill' => $statusPills[$s->status] ?? 'muted',
                                            'date' => $s->created_at?->format('d/m/Y') ?? '—',
                                            'title' => $s->title,
                                            'coauthors' => collect($s->authors ?? [])->filter(fn ($a) => empty($a['corresponding']))
                                                ->map(fn ($a) => trim((string) ($a['name'] ?? '')))->filter()->values()->all(),
                                            'keywords' => is_array($s->keywords) ? implode(', ', $s->keywords) : (string) $s->keywords,
                                            'abstract' => trim((string) preg_replace('/\s+/', ' ', strip_tags((string) $s->abstract))),
                                            'url' => route('admin.submissions.show', $s),
                                        ];
                                    @endphp
                                    <div class="ukc-actions" style="justify-content:flex-end;">
                                        <button type="button" class="ukc-btn--icon" title="Voir l’aperçu" @click="pv = {{ \Illuminate\Support\Js::from($pvData) }}"><x-ukc.icon name="eye" :size="16" /></button>
                                        <a class="ukc-btn--icon" title="Ouvrir la fiche" href="{{ route('admin.submissions.show', $s) }}"><x-ukc.icon name="external" :size="16" /></a>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="8" style="padding:24px;color:var(--ukc-muted);text-align:center;">Aucune soumission ne correspond aux filtres.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div style="margin-top:12px;">{{ $submissions->links() }}</div>

        {{-- Aperçu de la soumission (panneau latéral Tailwise) --}}
        <x-ukc.slideover open="pv" onClose="pv = null" width="460px">
            <x-slot:header><span x-text="pv?.ref"></span> · Aperçu</x-slot:header>
            <div class="detail-grid">
                <div class="k">Référence</div><div class="v" x-text="pv?.ref"></div>
                <div class="k">Auteur principal</div><div class="v" x-text="pv?.author"></div>
                <div class="k">Type</div><div class="v" x-text="pv?.type"></div>
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
                <div class="abstract-box abstract-box--full" x-text="pv?.abstract || '—'"></div>
            </div>
            <x-slot:footer>
                <a class="ukc-btn ukc-btn--ghost" :href="pv?.url">Ouvrir la fiche</a>
                <button type="button" class="ukc-btn ukc-btn--blue" @click="pv = null">Fermer</button>
            </x-slot:footer>
        </x-ukc.slideover>
    </div>
</x-layouts.admin>
