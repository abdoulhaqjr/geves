<div>
    <div style="display:flex;gap:10px;flex-wrap:wrap;align-items:center;margin-bottom:18px;">
        <div class="ukc-field" style="min-width:240px;">
            <input type="search" wire:model.live.debounce.300ms="search" placeholder="Réf., titre ou auteur…">
        </div>
        <div class="ukc-field" style="min-width:180px;">
            <select wire:model.live="edition_id">
                <option value="">Toutes les éditions</option>
                @foreach ($editions as $ed)
                    <option value="{{ $ed->id }}">{{ $ed->title ?: $ed->year }}</option>
                @endforeach
            </select>
        </div>
        <div class="ukc-field" style="min-width:160px;">
            <select wire:model.live="status">
                <option value="">Tous les statuts</option>
                @foreach ($statuses as $key => $label)
                    <option value="{{ $key }}">{{ $label }}</option>
                @endforeach
            </select>
        </div>
    </div>

    <div class="ukc-card" style="padding:0;">
        @if ($submissions->count())
            <div class="ukc-table-wrap">
                <table class="ukc-table">
                    <thead>
                        <tr>
                            <th>Réf.</th>
                            <th>Titre</th>
                            <th>Auteur</th>
                            <th>Axe</th>
                            <th>Type</th>
                            <th>Statut</th>
                            <th style="text-align:right;">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($submissions as $s)
                            <tr wire:key="asub-{{ $s->id }}">
                                <td style="font-family:ui-monospace,monospace;font-weight:700;">{{ $s->ref_code }}</td>
                                <td>
                                    <div class="title">{{ \Illuminate\Support\Str::limit($s->title, 70) }}</div>
                                    <div class="meta">{{ $s->edition?->edition_label ?: $s->edition?->year }}</div>
                                </td>
                                <td>{{ $s->correspondingAuthorName() ?: '—' }}</td>
                                <td>{{ $s->track ?: '—' }}</td>
                                <td>{{ $s->type === 'poster' ? 'Poster' : 'Oral' }}</td>
                                <td><span class="ukc-pill ukc-pill--{{ $s->statusPill() }}">{{ $s->statusLabel() }}</span></td>
                                <td style="text-align:right;white-space:nowrap;">
                                    <a href="{{ route('admin.submissions.show', $s) }}" wire:navigate
                                       class="ukc-btn ukc-btn--outline ukc-btn--sm">
                                        <x-ukc.icon name="eye" :size="16" /> Ouvrir
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div style="padding:14px 18px;">{{ $submissions->links() }}</div>
        @else
            <div class="ukc-empty">
                <h3>Aucune soumission</h3>
                <p>Les communications déposées apparaîtront ici.</p>
            </div>
        @endif
    </div>
</div>
