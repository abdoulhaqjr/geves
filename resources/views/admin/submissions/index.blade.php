<x-layouts.admin title="Soumissions" subtitle="Communications & résumés">
    <x-slot:actions>
        <a class="ukc-btn ukc-btn--ghost ukc-btn--sm"
           href="{{ route('admin.submissions.export', request()->only('edition_id', 'status', 'type', 'track', 'search')) }}">
            <x-ukc.icon name="download" :size="14" /> Exporter
        </a>
    </x-slot:actions>

    <div class="admin-page admin-submissions" x-data="{ pv: null }" @keydown.escape.window="pv = null">
        {{-- Sous-navigation (fidèle au mockup admin-submissions) --}}
        <div class="ukc-nav-docs">
            <button type="button" class="active">Soumissions</button>
            <a href="{{ $edition_id !== '' ? route('admin.editions.form', $edition_id) : route('admin.editions.index') }}">Formulaires de soumissions</a>
        </div>

        {{-- Indicateurs --}}
        <div class="ukc-stats">
            <div class="ukc-stat-card"><div class="lbl">Total</div><div class="val blue">{{ $stats['total'] }}</div></div>
            <div class="ukc-stat-card"><div class="lbl">En revue</div><div class="val warn">{{ $stats['underReview'] }}</div></div>
            <div class="ukc-stat-card"><div class="lbl">Acceptées</div><div class="val green">{{ $stats['accepted'] }}</div></div>
            <div class="ukc-stat-card"><div class="lbl">Corrections</div><div class="val warn">{{ $stats['revision'] }}</div></div>
            <div class="ukc-stat-card"><div class="lbl">Refusées</div><div class="val" style="color:#dc2626;">{{ $stats['rejected'] }}</div></div>
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
            <select name="track" onchange="this.form.submit()">
                <option value="">Tous les axes</option>
                @foreach ($tracks as $t)<option value="{{ $t }}" @selected($track === $t)>{{ $t }}</option>@endforeach
            </select>
            <input type="search" name="search" value="{{ $search }}" placeholder="Titre, auteur, réf.…" style="min-width:200px;"
                   onkeydown="if(event.key==='Enter'){this.form.submit();}">
            @if ($search !== '' || $status !== '' || $edition_id !== '' || $type !== '' || $track !== '')
                <a href="{{ route('admin.submissions.index') }}" class="ukc-btn ukc-btn--ghost ukc-btn--sm">Réinitialiser</a>
            @endif
        </form>

        <div class="ukc-card" style="padding:0;">
            <div class="ukc-table-wrap">
                <table class="ukc-table">
                    <thead>
                        <tr>
                            <th>Réf.</th><th>Communication</th><th>Auteur(s)</th><th>Type</th><th>Axe</th><th>Déposée</th><th>Statut</th><th style="text-align:right;">Actions</th>
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
                                <td>{{ $s->participant?->full_name ?? ($s->authors[0]['name'] ?? '—') }}</td>
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
                                            'keywords' => is_array($s->keywords) ? implode(', ', $s->keywords) : (string) $s->keywords,
                                            'abstract' => \Illuminate\Support\Str::limit(trim(preg_replace('/\s+/', ' ', strip_tags((string) $s->abstract))), 900),
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

        {{-- Aperçu de la soumission (modale, fidèle au mockup admin-submissions) --}}
        <div class="ukc-modal" x-show="pv" x-cloak @click.self="pv = null" style="display:none;">
            <div class="box">
                <div class="box-head">
                    <span>Aperçu de la soumission</span>
                    <button type="button" @click="pv = null" style="font-size:22px;color:var(--ukc-muted);background:none;border:none;cursor:pointer;">&times;</button>
                </div>
                <div class="box-body">
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
                    <div class="ukc-form-row" x-show="pv?.keywords">
                        <label>Mots-clés</label>
                        <div style="font-size:13px;color:var(--ukc-muted);" x-text="pv?.keywords"></div>
                    </div>
                    <div class="ukc-form-row">
                        <label>Résumé</label>
                        <div class="abstract-box" x-text="pv?.abstract || '—'"></div>
                    </div>
                    <div class="box-foot">
                        <a class="ukc-btn ukc-btn--ghost" :href="pv?.url">Ouvrir la fiche</a>
                        <button type="button" class="ukc-btn ukc-btn--blue" @click="pv = null">Fermer</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-layouts.admin>
