<div class="admin-page admin-submission-detail">
    @php($s = $submission)
    @php($initials = fn ($name) => \Illuminate\Support\Str::of($name ?: '?')->explode(' ')->filter()->take(2)->map(fn ($w) => mb_strtoupper(mb_substr($w, 0, 1)))->implode('') ?: '?')

    {{-- Barre méta --}}
    <div class="ukc-card metabar">
        <div class="info">
            <strong>{{ $s->ref_code }}</strong>
            · {{ $s->type === 'poster' ? 'Poster' : 'Orale' }}
            @if ($s->track) · {{ $s->track }} @endif
            · Déposée le {{ $s->created_at?->format('d/m/Y') }}
            @if ($s->version > 1) · <span class="ukc-pill ukc-pill--info">v{{ $s->version }}</span> @endif
        </div>
        <div class="acts">
            <a class="ukc-btn ukc-btn--ghost ukc-btn--sm" href="{{ route('admin.submissions.index') }}" wire:navigate>
                <x-ukc.icon name="chevron" :size="14" style="transform:rotate(180deg);" /> Retour à la liste
            </a>
            @if ($s->file_url)
                <a class="ukc-btn ukc-btn--sm" href="{{ $s->file_url }}" target="_blank">
                    <x-ukc.icon name="download" :size="14" /> Télécharger le fichier
                </a>
            @endif
        </div>
    </div>

    <div class="layout">
        {{-- COLONNE PRINCIPALE --}}
        <div>
            {{-- Résumé & auteurs --}}
            <div class="ukc-card">
                <div style="display:flex;align-items:center;justify-content:space-between;gap:10px;flex-wrap:wrap;margin-bottom:6px;">
                    <span style="font-family:ui-monospace,monospace;font-weight:700;color:var(--ukc-muted);font-size:.9rem;">{{ $s->ref_code }}</span>
                    <span class="ukc-pill ukc-pill--{{ $s->statusPill() }}">{{ $s->statusLabel() }}</span>
                </div>
                <div class="paper-title">{{ $s->title }}</div>
                <div class="meta-row">
                    <span><strong>Type :</strong> {{ $s->type === 'poster' ? 'Poster' : 'Orale' }}</span>
                    @if ($s->track)<span><strong>Axe :</strong> {{ $s->track }}</span>@endif
                    <span><strong>Déposée :</strong> {{ $s->created_at?->format('d/m/Y') }}</span>
                    <span><strong>Édition :</strong> {{ $s->edition?->title }}</span>
                </div>

                <h3>Auteurs</h3>
                <ul class="authors-list">
                    @foreach (($s->authors ?? []) as $author)
                        <li>
                            <div class="av">{{ $initials($author['name'] ?? '?') }}</div>
                            <div>
                                <div class="name">{{ $author['name'] ?? '—' }}
                                    @if (!empty($author['corresponding']))
                                        <span class="ukc-pill ukc-pill--info" style="margin-left:6px;">Correspondant</span>
                                    @endif
                                </div>
                                @if (!empty($author['affiliation']))
                                    <div class="aff">{{ $author['affiliation'] }}@if (!empty($author['grade'])) · {{ $author['grade'] }}@endif</div>
                                @endif
                                @if (!empty($author['email']))<div class="aff">{{ $author['email'] }}</div>@endif
                            </div>
                        </li>
                    @endforeach
                </ul>

                <h3>Résumé</h3>
                <div class="abstract-box ukc-rte">{!! $s->abstract ? \App\Support\Html::render($s->abstract) : '—' !!}</div>
                @if ($s->keywords)
                    <div class="keywords">
                        @foreach (preg_split('/[,;]+/', $s->keywords, -1, PREG_SPLIT_NO_EMPTY) as $kw)
                            <span>{{ trim($kw) }}</span>
                        @endforeach
                    </div>
                @endif
            </div>

            {{-- Fichiers joints --}}
            @if ($s->file_url)
                @php($ext = strtoupper(pathinfo(parse_url($s->file_url, PHP_URL_PATH) ?? '', PATHINFO_EXTENSION) ?: 'FICH'))
                <div class="ukc-card">
                    <h2>Fichiers joints</h2>
                    <div class="file-row">
                        <div class="ico" @if (in_array($ext, ['DOC', 'DOCX'])) style="background:#eff6ff;color:var(--ukc-blue);" @endif>{{ \Illuminate\Support\Str::limit($ext, 4, '') }}</div>
                        <div class="name">
                            <strong>{{ basename(parse_url($s->file_url, PHP_URL_PATH) ?? $s->file_url) }}</strong>
                            <div class="sub">Résumé déposé le {{ $s->created_at?->format('d/m/Y') }}</div>
                        </div>
                        <a class="ukc-btn ukc-btn--ghost ukc-btn--sm" href="{{ $s->file_url }}" target="_blank">Télécharger</a>
                    </div>
                </div>
            @endif

            {{-- Réponses complémentaires --}}
            @if (! empty($s->meta['custom_fields']))
                <div class="ukc-card">
                    <h2>Réponses complémentaires</h2>
                    @foreach ($s->meta['custom_fields'] as $cf)
                        <p class="cf-row"><strong>{{ $cf['label'] ?? '' }} :</strong> {{ $cf['value'] ?? '' }}</p>
                    @endforeach
                </div>
            @endif

            {{-- Décision --}}
            <div class="ukc-card">
                <h2><span><x-ukc.icon name="check-circle" :size="18" /> Décision du comité</span></h2>
                <p class="desc">Le message ci-dessous est inclus dans l’e-mail envoyé automatiquement à l’auteur correspondant.</p>

                <div class="ukc-field">
                    <label for="note">Message à l’auteur <span class="hint">(optionnel)</span></label>
                    <textarea id="note" wire:model="note" rows="4" placeholder="Précisions, motif, corrections attendues…"></textarea>
                    @error('note') <span class="error">{{ $message }}</span> @enderror
                </div>

                <div class="decision-bar">
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
            </div>

            {{-- Historique --}}
            <div class="ukc-card">
                <h2>Historique</h2>
                <ul class="timeline">
                    @foreach ($timeline as $ev)
                        <li @class(['green' => $ev['done']])>
                            <div class="when">{{ $ev['when']?->format('d/m/Y H:i') }}</div>
                            <div class="what">{{ $ev['what'] }}</div>
                        </li>
                    @endforeach
                </ul>
            </div>
        </div>

        {{-- COLONNE LATÉRALE --}}
        <div>
            {{-- Relecteurs --}}
            <div class="ukc-card">
                <h2><span><x-ukc.icon name="mic" :size="18" /> Relecteurs</span></h2>

                @if ($completedCount)
                    <div class="ukc-alert ukc-alert--info" style="margin-bottom:12px;">
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
                    <div class="reviewer-item" wire:key="review-{{ $review->id }}">
                        <div class="av">{{ $initials($review->reviewer?->full_name) }}</div>
                        <div class="info">
                            <strong>{{ $review->reviewer?->full_name ?: '—' }}</strong>
                            <span class="sub">{{ $review->reviewer?->email }}</span>
                            @if ($review->isCompleted())
                                <div class="rev-detail">
                                    <em>{{ $review->recommendationLabel() }}</em>@if ($review->score !== null) · {{ number_format((float) $review->score, 1, ',', ' ') }}/20 @endif
                                    @if ($review->comments)<div style="margin-top:4px;"><em style="font-weight:600;">À l’auteur :</em> {{ $review->comments }}</div>@endif
                                    @if ($review->comments_private)<div style="margin-top:2px;"><em style="font-weight:600;">Confidentiel :</em> {{ $review->comments_private }}</div>@endif
                                </div>
                            @endif
                        </div>
                        <div style="display:flex;flex-direction:column;align-items:flex-end;gap:6px;white-space:nowrap;">
                            @if ($review->isCompleted())
                                <span class="ukc-pill ukc-pill--ok">Fait</span>
                            @else
                                <span class="ukc-pill ukc-pill--warn">En cours</span>
                            @endif
                            <button type="button" class="ukc-btn ukc-btn--danger ukc-btn--sm"
                                    wire:click="removeReview({{ $review->id }})"
                                    wire:confirm="Retirer cette assignation ?">
                                <x-ukc.icon name="close" :size="14" />
                            </button>
                        </div>
                    </div>
                @empty
                    <p style="font-size:.9rem;color:var(--ukc-muted);">Aucun relecteur assigné.</p>
                @endforelse

                {{-- Assigner --}}
                <div style="margin-top:14px;padding-top:14px;border-top:1px solid var(--ukc-border);">
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
                    <button type="button" class="ukc-btn ukc-btn--blue ukc-btn--sm" style="width:100%;" wire:click="assignReviewer">
                        <x-ukc.icon name="user-plus" :size="16" /> Assigner et inviter
                    </button>
                    <p class="hint" style="margin-top:8px;font-size:12px;color:var(--ukc-light);">Le relecteur reçoit un e-mail avec le lien de son portail d’évaluation en aveugle.</p>
                </div>
            </div>

            {{-- Informations --}}
            <div class="ukc-card">
                <h2>Informations</h2>
                <div class="info-list">
                    <div><span class="k">Référence</span><br><code>{{ $s->ref_code }}</code></div>
                    <div><span class="k">Édition</span><br>{{ $s->edition?->title ?? '—' }}</div>
                    <div>
                        <span class="k">Statut paiement auteur</span><br>
                        @if ($authorPaid)
                            <span class="ukc-pill ukc-pill--ok">Payé</span>
                        @else
                            <span class="ukc-pill ukc-pill--muted">Non réglé</span>
                        @endif
                    </div>
                    @if ($s->participant)
                        <div>
                            <span class="k">Espace participant</span><br>
                            <a href="{{ route('admin.participants.preview', $s->participant) }}" wire:navigate>Ouvrir le profil →</a>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
