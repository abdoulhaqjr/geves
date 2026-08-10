<div>
    @php
        $statusMeta = [
            'draft' => ['Brouillon', 'muted'],
            'open' => ['Ouverte', 'ok'],
            'closed' => ['Clôturée', 'warn'],
            'archived' => ['Archivée', 'info'],
        ];
    @endphp

    <div style="display:flex;align-items:center;justify-content:space-between;gap:16px;flex-wrap:wrap;margin-bottom:18px;">
        <div style="display:flex;gap:10px;flex-wrap:wrap;align-items:center;">
            <div class="ukc-field" style="min-width:220px;">
                <input type="search" wire:model.live.debounce.300ms="search" placeholder="Rechercher une édition…">
            </div>
            <div class="ukc-field" style="min-width:150px;">
                <select wire:model.live="status">
                    <option value="">Tous les statuts</option>
                    @foreach ($statusMeta as $key => $meta)
                        <option value="{{ $key }}">{{ $meta[0] }}</option>
                    @endforeach
                </select>
            </div>
        </div>
        <a href="{{ route('admin.editions.create') }}" wire:navigate class="ukc-btn ukc-btn--primary">
            <x-ukc.icon name="plus" :size="18" /> Nouvelle édition
        </a>
    </div>

    <div class="ukc-card" style="padding:0;">
        @if ($editions->count())
            <div class="ukc-table-wrap">
                <table class="ukc-table">
                    <thead>
                        <tr>
                            <th>Édition</th>
                            <th>Année</th>
                            <th>Dates</th>
                            <th>Statut</th>
                            <th>Soumissions</th>
                            <th style="text-align:right;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($editions as $edition)
                            @php($meta = $statusMeta[$edition->status] ?? ['—', 'muted'])
                            <tr wire:key="edition-{{ $edition->id }}">
                                <td>
                                    <div class="title">{{ $edition->title ?: '(sans titre)' }}</div>
                                    <div class="meta">{{ $edition->colloque?->name }} · {{ $edition->edition_label }}</div>
                                </td>
                                <td>{{ $edition->year ?: '—' }}</td>
                                <td>
                                    @if ($edition->date_start)
                                        {{ $edition->date_start->format('d/m/Y') }}
                                        @if ($edition->date_end) → {{ $edition->date_end->format('d/m/Y') }} @endif
                                    @else — @endif
                                </td>
                                <td><span class="ukc-pill ukc-pill--{{ $meta[1] }}">{{ $meta[0] }}</span></td>
                                <td>
                                    @if ($edition->submission_open)
                                        <span class="ukc-pill ukc-pill--ok">Ouvertes</span>
                                    @else
                                        <span class="ukc-pill ukc-pill--muted">Fermées</span>
                                    @endif
                                </td>
                                <td style="text-align:right;white-space:nowrap;">
                                    @if ($edition->isPublished() && $edition->slug)
                                        <a href="{{ route('public.colloque', $edition->slug) }}" target="_blank"
                                           class="ukc-btn ukc-btn--ghost ukc-btn--sm" title="Voir la page publique">
                                            <x-ukc.icon name="external" :size="16" />
                                        </a>
                                    @endif
                                    <a href="{{ route('admin.documents.livret', $edition) }}" target="_blank"
                                       class="ukc-btn ukc-btn--ghost ukc-btn--sm" title="Livret des résumés">
                                        <x-ukc.icon name="book" :size="16" />
                                    </a>
                                    <a href="{{ route('admin.documents.badges', $edition) }}" target="_blank"
                                       class="ukc-btn ukc-btn--ghost ukc-btn--sm" title="Badges">
                                        <x-ukc.icon name="user" :size="16" />
                                    </a>
                                    <a href="{{ route('admin.editions.form', $edition) }}" wire:navigate
                                       class="ukc-btn ukc-btn--ghost ukc-btn--sm" title="Formulaire de soumission">
                                        <x-ukc.icon name="list" :size="16" />
                                    </a>
                                    <a href="{{ route('admin.editions.edit', $edition) }}" wire:navigate
                                       class="ukc-btn ukc-btn--outline ukc-btn--sm">
                                        <x-ukc.icon name="pen" :size="16" /> Modifier
                                    </a>
                                    <button type="button" class="ukc-btn ukc-btn--danger ukc-btn--sm"
                                            wire:click="delete({{ $edition->id }})"
                                            wire:confirm="Supprimer définitivement cette édition et ses données liées ?">
                                        <x-ukc.icon name="trash" :size="16" />
                                    </button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div style="padding:14px 18px;">{{ $editions->links() }}</div>
        @else
            <div class="ukc-empty">
                <h3>Aucune édition</h3>
                <p>Créez votre première édition pour commencer.</p>
                <a href="{{ route('admin.editions.create') }}" wire:navigate class="ukc-btn ukc-btn--primary">
                    <x-ukc.icon name="plus" :size="18" /> Nouvelle édition
                </a>
            </div>
        @endif
    </div>
</div>
