<x-layouts.app title="Portail relecteur">
    <div style="max-width:820px;margin:0 auto;padding:clamp(24px,4vw,48px) clamp(16px,4vw,24px);">
        <div style="text-align:center;margin-bottom:8px;">
            <div style="height:4px;width:80px;margin:0 auto 16px;display:flex;border-radius:4px;overflow:hidden;">
                <span style="flex:1;background:var(--ukc-blue);"></span>
                <span style="flex:1;background:var(--ukc-gold);"></span>
                <span style="flex:1;background:var(--ukc-green);"></span>
            </div>
            <h1 style="font-size:1.5rem;font-weight:900;color:var(--ukc-text);">Portail de relecture</h1>
            <p style="color:var(--ukc-muted);margin-top:6px;">
                Bonjour {{ $reviewer->full_name }} — relecture <strong>en aveugle</strong> (les auteurs ne sont pas affichés).
            </p>
        </div>

        <div class="ukc-card" style="padding:0;margin-top:20px;">
            @if ($reviews->count())
                <div class="ukc-table-wrap">
                    <table class="ukc-table">
                        <thead>
                            <tr>
                                <th>Réf.</th>
                                <th>Titre</th>
                                <th>Axe</th>
                                <th>État</th>
                                <th style="text-align:right;">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($reviews as $review)
                                @php($sub = $review->submission)
                                <tr>
                                    <td style="font-family:ui-monospace,monospace;font-weight:700;">{{ $sub?->ref_code }}</td>
                                    <td class="title">{{ \Illuminate\Support\Str::limit($sub?->title, 70) }}</td>
                                    <td>{{ $sub?->track ?: '—' }}</td>
                                    <td>
                                        @if ($review->isCompleted())
                                            <span class="ukc-pill ukc-pill--ok">Rendue</span>
                                        @else
                                            <span class="ukc-pill ukc-pill--warn">À faire</span>
                                        @endif
                                    </td>
                                    <td style="text-align:right;">
                                        <a href="{{ route('reviewer.evaluate', [$reviewer->access_token, $review->id]) }}"
                                           class="ukc-btn ukc-btn--outline ukc-btn--sm">
                                            {{ $review->isCompleted() ? 'Revoir' : 'Évaluer' }}
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="ukc-empty">
                    <h3>Aucune communication à relire</h3>
                    <p>Vous n’avez pas de relecture en attente pour le moment.</p>
                </div>
            @endif
        </div>
    </div>
</x-layouts.app>
