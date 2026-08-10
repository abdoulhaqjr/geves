<div>
    @php($s = $submission)

    <a href="{{ route('admin.submissions.index') }}" wire:navigate
       style="display:inline-flex;align-items:center;gap:6px;font-size:.9rem;color:var(--ukc-muted);margin-bottom:14px;">
        <x-ukc.icon name="chevron" :size="16" style="transform:rotate(180deg);" /> Retour aux soumissions
    </a>

    <div class="ukc-grid-2" style="align-items:start;">
        {{-- Détail --}}
        <div style="display:flex;flex-direction:column;gap:18px;">
            <div class="ukc-card">
                <div style="display:flex;align-items:center;justify-content:space-between;gap:10px;flex-wrap:wrap;">
                    <span style="font-family:ui-monospace,monospace;font-weight:700;color:var(--ukc-muted);">{{ $s->ref_code }}</span>
                    <span class="ukc-pill ukc-pill--{{ $s->statusPill() }}">{{ $s->statusLabel() }}</span>
                </div>
                <h2 style="margin-top:10px;font-size:1.25rem;display:block;">{{ $s->title }}</h2>
                <div style="display:flex;gap:8px;flex-wrap:wrap;margin-top:10px;">
                    @if ($s->track)<span class="ukc-pill ukc-pill--info">{{ $s->track }}</span>@endif
                    <span class="ukc-pill ukc-pill--muted">{{ $s->type === 'poster' ? 'Poster' : 'Oral' }}</span>
                    <span class="ukc-pill ukc-pill--muted">{{ $s->edition?->title }}</span>
                </div>
            </div>

            <div class="ukc-card">
                <h3>Résumé</h3>
                <p style="color:var(--ukc-text);line-height:1.7;white-space:pre-line;">{{ $s->abstract ?: '—' }}</p>
                @if ($s->keywords)
                    <p style="margin-top:12px;font-size:.9rem;color:var(--ukc-muted);">
                        <strong>Mots-clés :</strong> {{ $s->keywords }}
                    </p>
                @endif
                @if ($s->file_url)
                    <a href="{{ $s->file_url }}" target="_blank" class="ukc-btn ukc-btn--outline ukc-btn--sm" style="margin-top:14px;">
                        <x-ukc.icon name="file" :size="16" /> Fichier joint
                    </a>
                @endif
            </div>

            <div class="ukc-card">
                <h3>Auteurs</h3>
                @foreach (($s->authors ?? []) as $author)
                    <div style="display:flex;align-items:flex-start;gap:10px;padding:8px 0;border-bottom:1px solid var(--ukc-border);">
                        <x-ukc.icon name="user" :size="18" />
                        <div>
                            <div style="font-weight:700;">{{ $author['name'] ?? '—' }}
                                @if (!empty($author['corresponding']))
                                    <span class="ukc-pill ukc-pill--ok" style="margin-left:4px;">Correspondant</span>
                                @endif
                            </div>
                            <div style="font-size:.85rem;color:var(--ukc-muted);">
                                {{ $author['email'] ?? '' }}
                                @if (!empty($author['affiliation'])) — {{ $author['affiliation'] }} @endif
                                @if (!empty($author['grade'])) ({{ $author['grade'] }}) @endif
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- Workflow --}}
        <div class="ukc-card">
            <h3><x-ukc.icon name="check-circle" :size="20" /> Décision</h3>
            <p style="font-size:.88rem;color:var(--ukc-muted);line-height:1.6;margin-bottom:14px;">
                Le message ci-dessous est inclus dans l’e-mail envoyé à l’auteur correspondant.
            </p>

            <div class="ukc-field">
                <label for="note">Message à l’auteur <span class="hint">(optionnel)</span></label>
                <textarea id="note" wire:model="note" rows="4"
                          placeholder="Précisions, motif, corrections attendues…"></textarea>
                @error('note') <span class="error">{{ $message }}</span> @enderror
            </div>

            <div style="display:flex;flex-direction:column;gap:10px;margin-top:16px;">
                @foreach ($actions as $key => $meta)
                    <button type="button" class="ukc-btn ukc-btn--{{ $meta[1] }}"
                            wire:click="applyStatus('{{ $key }}')"
                            wire:confirm="Confirmer : « {{ $meta[0] }} » ? Un e-mail sera envoyé à l’auteur."
                            @disabled($s->status === $key)>
                        {{ $meta[0] }}
                        @if ($s->status === $key) <span style="font-size:.8rem;opacity:.7;">(actuel)</span> @endif
                    </button>
                @endforeach
            </div>

            {{-- Relecteurs --}}
            <div style="margin-top:24px;padding-top:18px;border-top:1px solid var(--ukc-border);">
                <h3 style="margin-bottom:6px;"><x-ukc.icon name="mic" :size="20" /> Relecteurs</h3>

                @if ($completedCount)
                    <div class="ukc-alert ukc-alert--info" style="margin:10px 0;">
                        <span class="ico"><x-ukc.icon name="chart" :size="18" /></span>
                        <div>
                            {{ $completedCount }} relecture(s) rendue(s)
                            @if ($averageScore !== null)
                                — note moyenne <strong>{{ number_format((float) $averageScore, 1, ',', ' ') }}/20</strong>
                            @endif
                        </div>
                    </div>
                @endif

                @forelse ($reviews as $review)
                    <div wire:key="review-{{ $review->id }}"
                         style="padding:12px 0;border-bottom:1px solid var(--ukc-border);">
                        <div style="display:flex;justify-content:space-between;gap:10px;align-items:flex-start;">
                            <div style="min-width:0;">
                                <div style="font-weight:700;font-size:.92rem;">{{ $review->reviewer?->full_name ?: '—' }}</div>
                                <div style="font-size:.82rem;color:var(--ukc-muted);">{{ $review->reviewer?->email }}</div>
                            </div>
                            <div style="text-align:right;white-space:nowrap;">
                                @if ($review->isCompleted())
                                    <span class="ukc-pill ukc-pill--ok">Rendue</span>
                                @else
                                    <span class="ukc-pill ukc-pill--warn">En attente</span>
                                @endif
                                <button type="button" class="ukc-btn ukc-btn--danger ukc-btn--sm" style="margin-left:4px;"
                                        wire:click="removeReview({{ $review->id }})"
                                        wire:confirm="Retirer cette assignation ?">
                                    <x-ukc.icon name="close" :size="14" />
                                </button>
                            </div>
                        </div>
                        @if ($review->isCompleted())
                            <div style="margin-top:8px;font-size:.88rem;">
                                <div><strong>{{ $review->recommendationLabel() }}</strong>
                                    @if ($review->score !== null) — {{ number_format((float) $review->score, 1, ',', ' ') }}/20 @endif
                                </div>
                                @if ($review->comments)
                                    <div style="margin-top:6px;color:var(--ukc-muted);">
                                        <em>À l’auteur :</em> {{ $review->comments }}
                                    </div>
                                @endif
                                @if ($review->comments_private)
                                    <div style="margin-top:4px;color:var(--ukc-muted);">
                                        <em>Confidentiel :</em> {{ $review->comments_private }}
                                    </div>
                                @endif
                            </div>
                        @endif
                    </div>
                @empty
                    <p style="font-size:.88rem;color:var(--ukc-muted);">Aucun relecteur assigné.</p>
                @endforelse

                {{-- Assigner --}}
                <div style="margin-top:14px;">
                    @if ($existingReviewers->count())
                        <div class="ukc-field" style="margin-bottom:8px;">
                            <label for="existingReviewerId">Relecteur existant</label>
                            <select id="existingReviewerId" wire:model="existingReviewerId">
                                <option value="">— Nouveau relecteur —</option>
                                @foreach ($existingReviewers as $rev)
                                    <option value="{{ $rev->id }}">{{ $rev->full_name }} ({{ $rev->email }})</option>
                                @endforeach
                            </select>
                        </div>
                    @endif
                    <div class="ukc-field" style="margin-bottom:8px;">
                        <label for="reviewerName">Nom</label>
                        <input type="text" id="reviewerName" wire:model="reviewerName" placeholder="Prénom Nom">
                        @error('reviewerName') <span class="error">{{ $message }}</span> @enderror
                    </div>
                    <div class="ukc-field" style="margin-bottom:10px;">
                        <label for="reviewerEmail">E-mail</label>
                        <input type="email" id="reviewerEmail" wire:model="reviewerEmail" placeholder="relecteur@exemple.tg">
                        @error('reviewerEmail') <span class="error">{{ $message }}</span> @enderror
                    </div>
                    <button type="button" class="ukc-btn ukc-btn--primary ukc-btn--sm" wire:click="assignReviewer">
                        <x-ukc.icon name="user-plus" :size="16" /> Assigner et inviter
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
