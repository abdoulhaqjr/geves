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

        {{-- ===== Appel à candidatures (bidding) ===== --}}
        @if ($biddable->isNotEmpty())
            <div style="margin-top:32px;">
                <h2 style="font-size:1.15rem;font-weight:800;color:var(--ukc-text);">Appel à candidatures</h2>
                <p style="color:var(--ukc-muted);font-size:14px;margin:4px 0 16px;">
                    Indiquez les communications que vous souhaitez évaluer. Vos préférences aident le comité à répartir les relectures.
                    Signalez tout conflit d’intérêt (auteur que vous pensez reconnaître, même laboratoire…).
                </p>

                <form method="POST" action="{{ route('reviewer.bids.save', $reviewer->access_token) }}">
                    @csrf
                    <div style="display:flex;flex-direction:column;gap:12px;">
                        @foreach ($biddable as $sub)
                            @php($bid = $bids[$sub->id] ?? null)
                            <div class="ukc-card" style="padding:16px 18px;">
                                <div style="display:flex;gap:10px;align-items:baseline;flex-wrap:wrap;">
                                    <span style="font-family:ui-monospace,monospace;font-weight:700;font-size:12.5px;color:var(--ukc-blue);">{{ $sub->ref_code }}</span>
                                    @if ($sub->track)<span class="ukc-pill ukc-pill--info">{{ \Illuminate\Support\Str::limit($sub->track, 28) }}</span>@endif
                                </div>
                                <div style="font-weight:700;color:var(--ukc-text);margin:6px 0 4px;line-height:1.35;">{{ $sub->title }}</div>
                                <p style="color:var(--ukc-muted);font-size:13.5px;line-height:1.55;margin:0 0 12px;">{{ \Illuminate\Support\Str::limit(strip_tags((string) $sub->abstract), 260) }}</p>

                                <div style="display:flex;flex-wrap:wrap;gap:8px 18px;align-items:center;">
                                    @foreach ($interests as $key => $label)
                                        <label style="display:inline-flex;align-items:center;gap:6px;font-size:13.5px;cursor:pointer;">
                                            <input type="radio" name="bids[{{ $sub->id }}][interest]" value="{{ $key }}"
                                                @checked(($bid?->interest ?? 'neutral') === $key)>
                                            {{ $label }}
                                        </label>
                                    @endforeach
                                    <span style="flex:1;"></span>
                                    <label style="display:inline-flex;align-items:center;gap:6px;font-size:13.5px;cursor:pointer;color:#b4231c;">
                                        <input type="checkbox" name="bids[{{ $sub->id }}][conflict]" value="1" @checked($bid?->conflict)>
                                        Conflit d’intérêt
                                    </label>
                                </div>
                            </div>
                        @endforeach
                    </div>
                    <div style="margin-top:16px;text-align:right;">
                        <button type="submit" class="ukc-btn ukc-btn--blue">Enregistrer mes préférences</button>
                    </div>
                </form>
            </div>
        @endif
    </div>
</x-layouts.app>
