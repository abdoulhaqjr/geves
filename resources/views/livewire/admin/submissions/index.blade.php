<div class="admin-page admin-submissions">
    {{-- Indicateurs --}}
    <div class="ukc-stats">
        <div class="ukc-stat-card"><div class="lbl">Total</div><div class="val blue">{{ $stats['total'] }}</div></div>
        <div class="ukc-stat-card"><div class="lbl">En revue</div><div class="val warn">{{ $stats['underReview'] }}</div></div>
        <div class="ukc-stat-card"><div class="lbl">Acceptées</div><div class="val green">{{ $stats['accepted'] }}</div></div>
        <div class="ukc-stat-card"><div class="lbl">Corrections</div><div class="val warn">{{ $stats['revision'] }}</div></div>
        <div class="ukc-stat-card"><div class="lbl">Refusées</div><div class="val" style="color:#dc2626;">{{ $stats['rejected'] }}</div></div>
    </div>

    {{-- Filtres --}}
    <div class="filter-bar">
        <select wire:model.live="edition_id">
            <option value="">Toutes les éditions</option>
            @foreach ($editions as $ed)<option value="{{ $ed->id }}">{{ $ed->title ?: $ed->year }}</option>@endforeach
        </select>
        <select wire:model.live="status">
            <option value="">Tous les statuts</option>
            @foreach ($statuses as $key => $label)<option value="{{ $key }}">{{ $label }}</option>@endforeach
        </select>
        <select wire:model.live="type">
            <option value="">Tous les types</option>
            <option value="oral">Orale</option>
            <option value="poster">Poster</option>
        </select>
        <select wire:model.live="track">
            <option value="">Tous les axes</option>
            @foreach ($tracks as $t)<option value="{{ $t }}">{{ $t }}</option>@endforeach
        </select>
        <input type="search" wire:model.live.debounce.300ms="search" placeholder="Titre, auteur, réf.…" style="min-width:200px;">
    </div>

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
                        <tr wire:key="sub-{{ $s->id }}">
                            <td><code>{{ $s->ref_code }}</code></td>
                            <td class="title-cell">
                                <strong>{{ \Illuminate\Support\Str::limit($s->title, 64) }}</strong>
                                <div class="meta">{{ count($s->authors ?? []) ?: 1 }} auteur(s)</div>
                            </td>
                            <td>{{ $s->participant?->full_name ?? ($s->authors[0]['name'] ?? '—') }}</td>
                            <td>{{ $s->type === 'poster' ? 'Poster' : 'Orale' }}</td>
                            <td>@if ($s->track)<span class="axis-tag">{{ \Illuminate\Support\Str::limit($s->track, 22) }}</span>@else — @endif</td>
                            <td style="white-space:nowrap;">{{ $s->created_at?->format('d/m/Y') }}</td>
                            <td><span class="ukc-pill ukc-pill--{{ $statusPills[$s->status] ?? 'muted' }}">{{ $statuses[$s->status] ?? $s->status }}</span></td>
                            <td>
                                <div class="ukc-actions">
                                    <a class="ukc-btn ukc-btn--ghost ukc-btn--sm" href="{{ route('admin.submissions.show', $s) }}" wire:navigate><x-ukc.icon name="eye" :size="14" /> Fiche</a>
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
</div>
