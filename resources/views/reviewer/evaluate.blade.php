<x-layouts.app title="Évaluer une communication">
    <div style="max-width:760px;margin:0 auto;padding:clamp(24px,4vw,48px) clamp(16px,4vw,24px);">
        @php($sub = $review->submission)

        <a href="{{ route('reviewer.portal', $reviewer->access_token) }}"
           style="display:inline-flex;align-items:center;gap:6px;font-size:.9rem;color:var(--ukc-muted);margin-bottom:14px;">
            <x-ukc.icon name="chevron" :size="16" style="transform:rotate(180deg);" /> Mes relectures
        </a>


        {{-- Communication (en aveugle : pas d'auteurs) --}}
        <div class="ukc-card" style="margin-bottom:18px;">
            <div style="font-family:ui-monospace,monospace;font-weight:700;color:var(--ukc-muted);">{{ $sub?->ref_code }}</div>
            <h2 style="margin-top:8px;">{{ $sub?->title }}</h2>
            <div style="display:flex;gap:8px;flex-wrap:wrap;margin-top:8px;">
                @if ($sub?->track)<span class="ukc-pill ukc-pill--info">{{ $sub->track }}</span>@endif
                <span class="ukc-pill ukc-pill--muted">{{ $sub?->type === 'poster' ? 'Poster' : 'Oral' }}</span>
            </div>
            <div class="ukc-rte" style="margin-top:14px;">{!! \App\Support\Html::render($sub?->abstract) !!}</div>
            @if ($sub?->keywords)
                <p style="margin-top:10px;font-size:.9rem;color:var(--ukc-muted);"><strong>Mots-clés :</strong> {{ $sub->keywords }}</p>
            @endif
            @if ($sub?->file_url)
                <a href="{{ $sub->file_url }}" target="_blank" class="ukc-btn ukc-btn--outline ukc-btn--sm" style="margin-top:12px;">
                    <x-ukc.icon name="file" :size="16" /> Fichier joint
                </a>
            @endif
        </div>

        {{-- Évaluation --}}
        <form method="POST" action="{{ route('reviewer.evaluate.submit', [$reviewer->access_token, $review->id]) }}" class="ukc-card">
            @csrf
            <h3><x-ukc.icon name="pen" :size="20" /> Votre évaluation</h3>

            {{-- Grille de critères notés (0–{{ $scale }}). La note /20 en est dérivée. --}}
            @php($savedScores = old('scores', $review->criterionScores()))
            <div class="review-grid"
                 x-data="{ scores: {{ Illuminate\Support\Js::from((object) $savedScores) }}, scale: {{ $scale }},
                           get overall() {
                               const v = Object.values(this.scores).map(Number).filter(n => !isNaN(n));
                               if (!v.length) return null;
                               return Math.round((v.reduce((a,b)=>a+b,0)/v.length)/this.scale*20*100)/100;
                           } }">
                <div style="display:flex;justify-content:space-between;align-items:baseline;flex-wrap:wrap;gap:8px;margin:6px 0 12px;">
                    <p style="color:var(--ukc-muted);font-size:.9rem;margin:0;">Notez chaque critère de 0 à {{ $scale }}.</p>
                    <div style="font-size:.9rem;">Note globale : <strong x-text="overall === null ? '—' : overall + '/20'"></strong></div>
                </div>

                @foreach ($criteria as $c)
                    <div class="review-criterion" style="display:flex;justify-content:space-between;gap:14px;align-items:center;padding:10px 0;border-bottom:1px solid var(--ukc-border);flex-wrap:wrap;">
                        <label for="crit-{{ $c['key'] }}" style="font-weight:600;flex:1;min-width:200px;">{{ $c['label'] }}</label>
                        <div style="display:flex;align-items:center;gap:8px;">
                            @for ($n = 0; $n <= $scale; $n++)
                                <label class="axis-check" style="cursor:pointer;">
                                    <input type="radio" name="scores[{{ $c['key'] }}]" value="{{ $n }}"
                                           x-model.number="scores['{{ $c['key'] }}']">
                                    <span>{{ $n }}</span>
                                </label>
                            @endfor
                        </div>
                        @error('scores.'.$c['key']) <span class="error" style="flex-basis:100%;">{{ $message }}</span> @enderror
                    </div>
                @endforeach
            </div>

            <div class="ukc-form-grid" style="margin-top:16px;">
                <div class="ukc-field full">
                    <label for="recommendation">Recommandation <span class="req">*</span></label>
                    <select id="recommendation" name="recommendation">
                        <option value="">— Choisir —</option>
                        @foreach ($recommendations as $key => $label)
                            <option value="{{ $key }}" @selected(old('recommendation', $review->recommendation) === $key)>{{ $label }}</option>
                        @endforeach
                    </select>
                    @error('recommendation') <span class="error">{{ $message }}</span> @enderror
                </div>

                <div class="ukc-field full">
                    <label for="comments">Commentaires à l’auteur</label>
                    <textarea id="comments" name="comments" rows="5"
                              placeholder="Retour constructif communiqué à l’auteur.">{{ old('comments', $review->comments) }}</textarea>
                    @error('comments') <span class="error">{{ $message }}</span> @enderror
                </div>

                <div class="ukc-field full">
                    <label for="comments_private">Commentaires confidentiels <span class="hint">(comité uniquement)</span></label>
                    <textarea id="comments_private" name="comments_private" rows="4"
                              placeholder="Remarques réservées au comité.">{{ old('comments_private', $review->comments_private) }}</textarea>
                    @error('comments_private') <span class="error">{{ $message }}</span> @enderror
                </div>
            </div>

            <div class="ukc-form-actions">
                <button type="submit" class="ukc-btn ukc-btn--blue">
                    <x-ukc.icon name="check" :size="18" /> Enregistrer mon évaluation
                </button>
            </div>
        </form>
    </div>
</x-layouts.app>
