<div>
    @php
        $statusMeta = [
            'submitted' => ['Soumise', 'info'],
            'under_review' => ['En relecture', 'warn'],
            'revision' => ['Correction demandée', 'warn'],
            'accepted' => ['Acceptée', 'ok'],
            'rejected' => ['Refusée', 'err'],
        ];
    @endphp

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
                            @php($meta = $statusMeta[$s->status] ?? [$s->status, 'muted'])
                            <tr wire:key="sub-{{ $s->id }}">
                                <td style="font-family:ui-monospace,monospace;font-weight:700;">{{ $s->ref_code }}</td>
                                <td>
                                    <div class="title">{{ $s->title }}</div>
                                    @if ($s->file_url)
                                        <div class="meta"><a href="{{ $s->file_url }}" target="_blank"
                                            style="color:var(--ukc-blue);">Fichier joint</a></div>
                                    @endif
                                </td>
                                <td>{{ $s->edition?->edition_label ?: $s->edition?->year }}</td>
                                <td>{{ $s->track ?: '—' }}</td>
                                <td>{{ $s->type === 'poster' ? 'Poster' : 'Oral' }}</td>
                                <td><span class="ukc-pill ukc-pill--{{ $meta[1] }}">{{ $meta[0] }}</span></td>
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
