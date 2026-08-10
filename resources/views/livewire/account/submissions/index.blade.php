<div>
    <div class="ukc-card" style="padding:0;">
        @if ($submissions->count())
            <div class="ukc-table-wrap">
                <table class="ukc-table">
                    <thead>
                        <tr>
                            <th>Réf.</th>
                            <th>Titre</th>
                            <th>Édition</th>
                            <th>Axe</th>
                            <th>Type</th>
                            <th>Statut</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($submissions as $s)
                            <tr wire:key="sub-{{ $s->id }}">
                                <td style="font-family:ui-monospace,monospace;font-weight:700;">
                                    <a href="{{ route('account.submissions.show', $s) }}" wire:navigate
                                       style="color:var(--ukc-blue);">{{ $s->ref_code }}</a>
                                </td>
                                <td>
                                    <a href="{{ route('account.submissions.show', $s) }}" wire:navigate class="title">{{ $s->title }}</a>
                                    @if ($s->status === 'revision')
                                        <div class="meta" style="color:#b45309;font-weight:700;">Correction demandée</div>
                                    @endif
                                </td>
                                <td>{{ $s->edition?->edition_label ?: $s->edition?->year }}</td>
                                <td>{{ $s->track ?: '—' }}</td>
                                <td>{{ $s->type === 'poster' ? 'Poster' : 'Oral' }}</td>
                                <td><span class="ukc-pill ukc-pill--{{ $s->statusPill() }}">{{ $s->statusLabel() }}</span></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="ukc-empty">
                <h3>Aucune communication</h3>
                <p>Vous n’avez pas encore soumis de communication.</p>
            </div>
        @endif
    </div>
</div>
