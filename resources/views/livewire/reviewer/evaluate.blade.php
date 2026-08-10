<div style="max-width:760px;margin:0 auto;padding:clamp(24px,4vw,48px) clamp(16px,4vw,24px);">
    @php($sub = $review->submission)

    <a href="{{ route('reviewer.portal', $reviewer->access_token) }}" wire:navigate
       style="display:inline-flex;align-items:center;gap:6px;font-size:.9rem;color:var(--ukc-muted);margin-bottom:14px;">
        <x-ukc.icon name="chevron" :size="16" style="transform:rotate(180deg);" /> Mes relectures
    </a>

    @if (session('ukc_status'))
        <div class="ukc-alert ukc-alert--ok">
            <span class="ico"><x-ukc.icon name="check-circle" :size="18" /></span>
            <div>{{ session('ukc_status') }}</div>
        </div>
    @endif

    {{-- Communication (en aveugle : pas d'auteurs) --}}
    <div class="ukc-card" style="margin-bottom:18px;">
        <div style="font-family:ui-monospace,monospace;font-weight:700;color:var(--ukc-muted);">{{ $sub?->ref_code }}</div>
        <h2 style="margin-top:8px;">{{ $sub?->title }}</h2>
        <div style="display:flex;gap:8px;flex-wrap:wrap;margin-top:8px;">
            @if ($sub?->track)<span class="ukc-pill ukc-pill--info">{{ $sub->track }}</span>@endif
            <span class="ukc-pill ukc-pill--muted">{{ $sub?->type === 'poster' ? 'Poster' : 'Oral' }}</span>
        </div>
        <p style="line-height:1.7;white-space:pre-line;margin-top:14px;">{{ $sub?->abstract }}</p>
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
    <form wire:submit="submitEvaluation" class="ukc-card">
        <h3><x-ukc.icon name="pen" :size="20" /> Votre évaluation</h3>

        <div class="ukc-form-grid" style="margin-top:8px;">
            <div class="ukc-field">
                <label for="recommendation">Recommandation <span class="req">*</span></label>
                <select id="recommendation" wire:model="recommendation">
                    <option value="">— Choisir —</option>
                    @foreach ($recommendations as $key => $label)
                        <option value="{{ $key }}">{{ $label }}</option>
                    @endforeach
                </select>
                @error('recommendation') <span class="error">{{ $message }}</span> @enderror
            </div>
            <div class="ukc-field">
                <label for="score">Note /20</label>
                <input type="number" id="score" wire:model="score" min="0" max="20" step="0.5">
                @error('score') <span class="error">{{ $message }}</span> @enderror
            </div>

            <div class="ukc-field full">
                <label for="comments">Commentaires à l’auteur</label>
                <textarea id="comments" wire:model="comments" rows="5"
                          placeholder="Retour constructif communiqué à l’auteur."></textarea>
                @error('comments') <span class="error">{{ $message }}</span> @enderror
            </div>

            <div class="ukc-field full">
                <label for="comments_private">Commentaires confidentiels <span class="hint">(comité uniquement)</span></label>
                <textarea id="comments_private" wire:model="comments_private" rows="4"
                          placeholder="Remarques réservées au comité."></textarea>
                @error('comments_private') <span class="error">{{ $message }}</span> @enderror
            </div>
        </div>

        <div class="ukc-form-actions">
            <button type="submit" class="ukc-btn ukc-btn--primary" wire:loading.attr="disabled" wire:target="submitEvaluation">
                <span wire:loading.remove wire:target="submitEvaluation"><x-ukc.icon name="check" :size="18" /> Enregistrer mon évaluation</span>
                <span wire:loading wire:target="submitEvaluation">Enregistrement…</span>
            </button>
        </div>
    </form>
</div>
