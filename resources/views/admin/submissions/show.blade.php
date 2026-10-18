<x-layouts.admin :title="'Soumission '.$submission->ref_code" subtitle="Relecture, décision & historique">
    <div class="admin-page admin-submission-detail" data-ajax-root="submission">
        @php($s = $submission)
        @php($initials = fn ($name) => \Illuminate\Support\Str::of($name ?: '?')->explode(' ')->filter()->take(2)->map(fn ($w) => mb_strtoupper(mb_substr($w, 0, 1)))->implode('') ?: '?')

        {{-- Barre méta --}}
        <div class="ukc-card metabar">
            <div class="info">
                <strong>{{ $s->ref_code }}</strong>
                - {{ $s->type === 'poster' ? 'Poster' : 'Orale' }}
                @if ($s->track) - {{ $s->track }} @endif
                - Déposée le {{ $s->created_at?->format('d/m/Y') }}
                @if ($s->version > 1) - <span class="ukc-pill ukc-pill--info">v{{ $s->version }}</span> @endif
            </div>
            <div class="acts">
                <a class="ukc-btn ukc-btn--ghost ukc-btn--sm" href="{{ route('admin.submissions.index') }}">
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
                        <span style="font-family:var(--ukc-mono);font-weight:700;color:var(--ukc-muted);font-size:.9rem;">{{ $s->ref_code }}</span>
                        <span class="ukc-pill ukc-pill--{{ $s->statusPill() }}">{{ $s->statusLabel() }}</span>
                    </div>
                    <div class="paper-title">{{ $s->title }}</div>
                    <div class="meta-row">
                        <span><strong>Type de communication :</strong> {{ $s->type === 'poster' ? 'Poster' : 'Orale' }}</span>
                        <span><strong>Mode de présentation :</strong> {{ $s->presentationModeLabel() ?? '-' }}</span>
                        <span><strong>Participation au colloque :</strong> {{ $s->participant?->participationModeLabel() ?? '-' }}</span>
                        @if ($s->track)<span><strong>Axe :</strong> {{ $s->track }}</span>@endif
                        <span><strong>Déposée :</strong> {{ $s->created_at?->format('d/m/Y') }}</span>
                        <span><strong>Événement :</strong> {{ $s->event?->title }}</span>
                    </div>

                    <h3>Auteurs</h3>
                    @php($links = $authorLinks->keyBy('author_index'))
                    @php($matchLabels = ['email' => 'rapproché par e-mail', 'phone' => 'rapproché par téléphone', 'name' => 'rapproché par nom', 'manual' => 'rattaché manuellement'])
                    <ul class="authors-list">
                        @foreach (($s->authors ?? []) as $i => $author)
                            @php($link = $links[$i] ?? null)
                            <li>
                                <div class="av">{{ $initials($author['name'] ?? '?') }}</div>
                                <div>
                                    <div class="name">{{ $author['name'] ?? '-' }}
                                        @if (!empty($author['corresponding']))
                                            <span class="ukc-pill ukc-pill--info" style="margin-left:6px;">Correspondant</span>
                                        @endif
                                        @if ($link)
                                            @php([$pill, $txt] = match ($link->status) {
                                                'linked' => $link->match_type === 'self' ? ['muted', 'Déposant'] : ['ok', 'Compte rattaché'],
                                                'invited' => ['warn', 'Invité(e)'],
                                                'declined' => ['err', 'Association déclinée'],
                                                default => ['muted', 'Non rattaché'],
                                            })
                                            <span class="ukc-pill ukc-pill--{{ $pill }}" style="margin-left:6px;">{{ $txt }}</span>
                                        @endif
                                    </div>
                                    @if (!empty($author['affiliation']))
                                        <div class="aff">{{ $author['affiliation'] }}@if (!empty($author['grade'])) - {{ $author['grade'] }}@endif</div>
                                    @endif
                                    @if (!empty($author['email']))<div class="aff">{{ $author['email'] }}</div>@endif
                                    @if ($link && $link->user)
                                        <div class="aff" style="color:var(--ukc-green);">
                                            <x-ukc.icon name="check" :size="13" /> {{ $link->user->full_name ?: $link->user->name }}
                                            @if ($link->match_type && ($matchLabels[$link->match_type] ?? null))
                                                <span style="color:var(--ukc-muted);">- {{ $matchLabels[$link->match_type] }}</span>
                                            @endif
                                        </div>
                                    @elseif ($link && $link->status === 'invited')
                                        <div class="aff" style="color:var(--ukc-muted);">Invitation envoyée le {{ $link->invited_at?->format('d/m/Y') }} - en attente d’inscription</div>
                                    @endif

                                    {{-- Rattachement manuel à un compte (correction d'un rapprochement) --}}
                                    @if ($link && $link->match_type !== 'self')
                                        <div x-data="{ open: false }" style="margin-top:6px;">
                                            @if ($link->user)
                                                <form method="POST" action="{{ route('admin.submissions.author.detach', [$s, $link]) }}" style="display:inline;">
                                                    @csrf
                                                    <button type="submit" class="ukc-btn ukc-btn--ghost ukc-btn--sm"><x-ukc.icon name="close" :size="13" /> Détacher le compte</button>
                                                </form>
                                            @else
                                                <button type="button" class="ukc-btn ukc-btn--ghost ukc-btn--sm" @click="open = !open" x-show="!open"><x-ukc.icon name="user-plus" :size="13" /> Rattacher à un compte</button>
                                                <form method="POST" action="{{ route('admin.submissions.author.link', [$s, $link]) }}" x-show="open" x-cloak
                                                      style="display:flex;gap:6px;flex-wrap:wrap;align-items:center;margin-top:4px;">
                                                    @csrf
                                                    <input type="email" name="email" value="{{ $link->email }}" placeholder="e-mail du compte"
                                                           style="border:1.5px solid var(--ukc-border);border-radius:8px;padding:6px 10px;font-size:13px;min-width:220px;">
                                                    <button type="submit" class="ukc-btn ukc-btn--blue ukc-btn--sm">Rattacher</button>
                                                    <button type="button" class="ukc-btn ukc-btn--ghost ukc-btn--sm" @click="open = false">Annuler</button>
                                                </form>
                                            @endif
                                        </div>
                                    @endif
                                </div>
                            </li>
                        @endforeach
                    </ul>

                    <h3>Résumé</h3>
                    <div class="abstract-box ukc-rte">{!! $s->abstract ? \App\Support\Html::render($s->abstract) : '-' !!}</div>
                    @if ($s->keywords)
                        <div class="keywords">
                            @foreach (\App\Support\SubmissionKeywords::parse($s->keywords, (new \App\Support\SubmissionFormConfig($s->event))->keywordSeparator()) as $kw)
                                <span>{{ $kw }}</span>
                            @endforeach
                        </div>
                    @endif
                </div>

                {{-- Similarité interne (anti-plagiat gratuit) --}}
                @if (! empty($similar))
                    @php($selfPlain = trim((string) preg_replace('/\s+/', ' ', strip_tags((string) $s->abstract))))
                    <div class="ukc-card" x-data="simCompare(@js($selfPlain))">
                        <h2><span><x-ukc.icon name="shield" :size="18" /> Similarité interne</span></h2>
                        <p style="color:var(--ukc-muted);font-size:13px;margin:-4px 0 12px;">
                            Comparé aux autres résumés de la plateforme (événements accessibles). Un score élevé peut signaler un doublon,
                            un résumé recyclé ou un auto-plagiat - <strong>cliquez pour comparer côte à côte</strong>.
                        </p>
                        <div style="display:flex;flex-direction:column;gap:8px;">
                            @foreach ($similar as $m)
                                @php($pct = $m['percent'])
                                @php($col = $pct >= 70 ? '#b4231c' : ($pct >= 50 ? '#8a5a00' : 'var(--ukc-muted)'))
                                @php($simData = [
                                    'percent' => $pct,
                                    'ref' => $m['submission']->ref_code,
                                    'title' => $m['submission']->title,
                                    'abstract' => trim((string) preg_replace('/\s+/', ' ', strip_tags((string) $m['submission']->abstract))),
                                    'url' => route('admin.submissions.show', $m['submission']->id),
                                ])
                                <button type="button" @click="open(@js($simData))"
                                        style="display:flex;align-items:center;gap:12px;padding:10px 12px;border:1px solid var(--ukc-border);border-radius:10px;background:#fff;cursor:pointer;width:100%;text-align:left;">
                                    <span style="flex-shrink:0;font-weight:800;font-size:15px;color:{{ $col }};min-width:48px;">{{ $pct }}%</span>
                                    <span style="flex:1;min-width:0;">
                                        <span style="display:block;font-weight:600;color:var(--ukc-text);">{{ \Illuminate\Support\Str::limit($m['submission']->title, 70) }}</span>
                                        <span style="font-size:12px;color:var(--ukc-muted);font-family:var(--ukc-mono);">{{ $m['submission']->ref_code }}</span>
                                    </span>
                                    <x-ukc.icon name="search" :size="16" />
                                </button>
                            @endforeach
                        </div>

                        {{-- Modale de comparaison côte à côte --}}
                        <div x-show="sim" x-cloak @click.self="sim = null" @keydown.escape.window="sim = null" class="sim-modal">
                            <div class="sim-modal__box">
                                <div class="sim-modal__head">
                                    <div style="font-weight:800;">Comparaison de similarité - <span x-text="(sim?.percent ?? 0) + '%'"></span></div>
                                    <button type="button" class="ukc-btn--icon" @click="sim = null"><x-ukc.icon name="close" :size="18" /></button>
                                </div>
                                <div class="sim-compare-grid">
                                    <div>
                                        <div style="font-weight:700;">{{ \Illuminate\Support\Str::limit($s->title, 90) }}</div>
                                        <div style="font-family:var(--ukc-mono);font-size:12px;color:var(--ukc-muted);margin-bottom:8px;">{{ $s->ref_code }} - cette soumission</div>
                                        <div class="sim-abstract" x-html="sim?.aHtml"></div>
                                    </div>
                                    <div>
                                        <div style="font-weight:700;" x-text="sim?.title"></div>
                                        <div style="font-family:var(--ukc-mono);font-size:12px;color:var(--ukc-muted);margin-bottom:8px;"><span x-text="sim?.ref"></span> - soumission proche</div>
                                        <div class="sim-abstract" x-html="sim?.bHtml"></div>
                                    </div>
                                </div>
                                <div class="sim-modal__foot">
                                    <a :href="sim?.url" class="ukc-btn ukc-btn--ghost">Ouvrir la fiche proche</a>
                                    <button type="button" class="ukc-btn ukc-btn--blue" @click="sim = null">Fermer</button>
                                </div>
                            </div>
                        </div>
                    </div>
                @endif

                {{-- Anti-plagiat web (couche 2, à la demande) --}}
                @if ($webPlagProvider || $webPlag)
                    <div class="ukc-card">
                        <h2><span><x-ukc.icon name="globe" :size="18" /> Anti-plagiat web</span></h2>
                        @if ($webPlag)
                            @php($pp = (int) round($webPlag['plagPercent'] ?? 0))
                            @php($col = $pp >= 30 ? '#b4231c' : ($pp >= 15 ? '#8a5a00' : 'var(--ukc-green)'))
                            <div style="display:flex;align-items:baseline;gap:14px;">
                                <span style="font-size:1.6rem;font-weight:800;color:{{ $col }};">{{ $pp }}%</span>
                                <span style="color:var(--ukc-muted);font-size:13px;">de similarité web - {{ (int) round($webPlag['uniquePercent'] ?? 0) }}% unique - via {{ $webPlag['provider'] ?? 'externe' }}</span>
                            </div>
                            @if (! empty($webPlag['sources']))
                                <div style="display:flex;flex-direction:column;gap:6px;margin-top:12px;">
                                    @foreach ($webPlag['sources'] as $src)
                                        <div style="display:flex;gap:10px;font-size:13px;align-items:baseline;">
                                            <span style="font-weight:700;min-width:44px;color:var(--ukc-muted);">{{ (int) round($src['percent'] ?? 0) }}%</span>
                                            <a href="{{ $src['link'] }}" target="_blank" rel="noopener" style="color:var(--ukc-blue);word-break:break-all;">{{ \Illuminate\Support\Str::limit($src['link'], 80) }}</a>
                                        </div>
                                    @endforeach
                                </div>
                            @endif
                            <div style="font-size:11.5px;color:var(--ukc-light);margin-top:10px;">Dernière vérification : {{ \Illuminate\Support\Carbon::parse($webPlag['checked_at'])->format('d/m/Y H:i') }}</div>
                        @else
                            <p style="color:var(--ukc-muted);font-size:13px;margin:-4px 0 0;">
                                Vérifie le résumé contre le web via <strong>{{ $webPlagProvider->label() }}</strong> - à la demande (consomme votre quota).
                            </p>
                        @endif
                        @if ($webPlagProvider)
                            <form method="POST" action="{{ route('admin.submissions.plagiarism', $s->id) }}" style="margin-top:12px;">
                                @csrf
                                <button type="submit" class="ukc-btn ukc-btn--outline ukc-btn--sm">{{ $webPlag ? 'Relancer la vérification' : 'Vérifier le plagiat (web)' }}</button>
                            </form>
                        @endif
                    </div>
                @endif

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

                    @if (in_array($s->status, ['submitted', 'under_review'], true) && $reviews->isEmpty())
                        <div class="ukc-alert ukc-alert--info" style="margin-bottom:12px;">
                            <span class="ico"><x-ukc.icon name="info" :size="16" /></span>
                            <div>Aucune relecture n’a été lancée : vous pouvez <strong>décider directement</strong> (accepter, refuser, demander une correction) sans passer par les relecteurs.</div>
                        </div>
                    @endif

                    <form method="POST" action="{{ route('admin.submissions.status', $s) }}" data-ajax data-ajax-msg="Décision appliquée.">
                        @csrf
                        <div class="ukc-field">
                            <label for="note">Message à l’auteur <span class="hint">(optionnel)</span></label>
                            <textarea id="note" name="note" rows="4" placeholder="Précisions, motif, corrections attendues…">{{ old('note') }}</textarea>
                            @error('note') <span class="error">{{ $message }}</span> @enderror
                        </div>

                        <div class="decision-bar">
                            @foreach ($actions as $key => $meta)
                                <button type="submit" name="status" value="{{ $key }}" class="ukc-btn ukc-btn--{{ $meta[1] }}"
                                        data-confirm="Confirmer : « {{ $meta[0] }} » ? Un e-mail sera envoyé à l’auteur."
                                        @disabled($s->status === $key)>
                                    {{ $meta[0] }}
                                    @if ($s->status === $key) <span style="font-size:.8rem;opacity:.7;">(actuel)</span> @endif
                                </button>
                            @endforeach
                        </div>
                    </form>
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
                {{-- Conflits d'intérêts --}}
                @php($coiTag = fn ($lvl) => ['high' => ['Élevé', 'high'], 'medium' => ['Moyen', 'med'], 'low' => ['Faible', 'low']][$lvl] ?? ['OK', 'clear'])
                <div class="ukc-card">
                    <h2><span><x-ukc.icon name="shield" :size="18" /> Conflits d’intérêts</span></h2>
                    <p class="desc">Détection automatique indicative entre relecteurs et auteurs. La décision reste à votre main.</p>
                    @forelse ($reviews as $review)
                        @php($cois = $coiByReview[$review->id] ?? [])
                        @if (count($cois))
                            @foreach ($cois as $co)
                                <div class="coi-item risk">
                                    <span class="coi-tag {{ $coiTag($co['level'])[1] }}">{{ $coiTag($co['level'])[0] }}</span>
                                    <div>
                                        <strong>{{ $co['label'] }}</strong> - {{ $review->reviewer?->full_name }}<br>
                                        <span style="color:var(--ukc-muted);font-size:12px;">{{ $co['detail'] }}</span>
                                    </div>
                                </div>
                            @endforeach
                        @else
                            <div class="coi-item">
                                <span class="coi-tag clear">OK</span>
                                <div><strong>{{ $review->reviewer?->full_name }}</strong> - aucun conflit détecté.</div>
                            </div>
                        @endif
                    @empty
                        <p style="font-size:.88rem;color:var(--ukc-muted);">Assignez des relecteurs pour vérifier les conflits potentiels.</p>
                    @endforelse
                    @php($coiIgnoresInstitution = \App\Support\ConflictOfInterest::ignoresSameInstitution($s))
                    <p class="hint" style="margin-top:10px;font-size:12px;color:var(--ukc-light);">
                        Règles : {{ $coiIgnoresInstitution ? '' : 'même institution - ' }}co-auteur &lt; 5 ans - homonymie (lien familial possible) → vérification manuelle.
                        {{ $coiIgnoresInstitution ? 'La règle « même institution » est ignorée pour cet événement (onglet Options).' : '' }}
                    </p>
                </div>

                {{-- Relecteurs --}}
                <div class="ukc-card">
                    <h2><span><x-ukc.icon name="mic" :size="18" /> Relecteurs</span></h2>
                    <p class="desc">{{ \App\Models\Review::summarize($reviews) }}</p>

                    @if ($completedCount)
                        @if ($averageScore !== null)
                            @php($scoreTone = $averageScore >= 14 ? 'green' : ($averageScore >= 10 ? 'amber' : 'red'))
                            <div class="tw-score tw-score--{{ $scoreTone }}">
                                <div class="tw-score__num">{{ number_format((float) $averageScore, 1, ',', ' ') }}<span>/20</span></div>
                                <div>
                                    <div class="tw-score__label">Note moyenne</div>
                                    <div class="tw-score__sub">{{ $completedCount }} relecture(s) rendue(s)</div>
                                </div>
                            </div>
                        @else
                            <div class="ukc-alert ukc-alert--info" style="margin-bottom:12px;">
                                <span class="ico"><x-ukc.icon name="chart" :size="18" /></span>
                                <div>{{ $completedCount }} relecture(s) rendue(s)</div>
                            </div>
                        @endif
                    @endif

                    @forelse ($reviews as $review)
                        <div class="reviewer-item">
                            <div class="av">{{ $initials($review->reviewer?->full_name) }}</div>
                            <div class="info">
                                <strong>{{ $review->reviewer?->full_name ?: '-' }}</strong>
                                <span class="sub">{{ $review->reviewer?->email }}</span>
                                <div class="rev-state">
                                    <span class="ukc-pill ukc-pill--{{ $review->statusPill() }}">{{ $review->statusLabel() }}</span>
                                    @if ($review->isLate())
                                        <span class="ukc-pill ukc-pill--err">{{ $review->daysLate() }} j de retard</span>
                                    @elseif ($review->due_at && $review->isPending())
                                        <span class="due">échéance {{ $review->due_at->format('d/m/Y') }}</span>
                                    @endif
                                </div>
                                @if ($review->status === 'declined' && $review->decline_reason)
                                    <span class="sub" style="color:#b4231c;">Motif du refus : {{ $review->decline_reason }}</span>
                                @elseif ($review->assign_reason)
                                    <span class="sub">{{ $review->assign_reason }}</span>
                                @endif
                                @if ($review->isCompleted())
                                    <div class="rev-detail">
                                        <em>{{ $review->recommendationLabel() }}</em>@if ($review->score !== null) - {{ number_format((float) $review->score, 1, ',', ' ') }}/20 @endif
                                        @php($critScores = $review->criterionScores())
                                        @if (! empty($critScores))
                                            <div style="margin-top:6px;display:flex;flex-wrap:wrap;gap:4px;">
                                                @foreach ($reviewCriteria as $c)
                                                    @if (isset($critScores[$c['key']]))
                                                        <span class="ukc-pill ukc-pill--muted" style="font-size:11px;" title="{{ $c['label'] }}">{{ \Illuminate\Support\Str::limit($c['label'], 18) }} : {{ $critScores[$c['key']] }}/{{ $reviewScale }}</span>
                                                    @endif
                                                @endforeach
                                            </div>
                                        @endif
                                        @if ($review->comments)<div style="margin-top:4px;"><em style="font-weight:600;">À l’auteur :</em> {{ $review->comments }}</div>@endif
                                        @if ($review->comments_private)<div style="margin-top:2px;"><em style="font-weight:600;">Confidentiel :</em> {{ $review->comments_private }}</div>@endif
                                    </div>
                                @endif
                            </div>
                            @if ($review->status !== 'cancelled')
                                <form method="POST" action="{{ route('admin.submissions.review.remove', [$s, $review]) }}"
                                      data-ajax data-ajax-msg="Assignation annulée." data-confirm="Retirer cette assignation ? La relecture passe « annulée » : elle reste dans l’historique mais ne compte plus.">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="rev-remove" title="Retirer l’assignation">
                                        <x-ukc.icon name="close" :size="14" />
                                    </button>
                                </form>
                            @endif
                        </div>
                    @empty
                        <p style="font-size:.9rem;color:var(--ukc-muted);">Aucun relecteur assigné.</p>
                    @endforelse

                    {{-- Assigner --}}
                    <form method="POST" action="{{ route('admin.submissions.review.assign', $s) }}"
                          data-ajax data-ajax-msg="Relecteur assigné."
                          style="margin-top:14px;padding-top:14px;border-top:1px solid var(--ukc-border);">
                        @csrf
                        @if ($existingReviewers->count())
                            <div class="ukc-field" style="margin-bottom:8px;">
                                <label for="existingReviewerId">Relecteur existant</label>
                                <select id="existingReviewerId" name="existingReviewerId" data-tom data-placeholder="Rechercher un relecteur…">
                                    <option value="">- Nouveau relecteur -</option>
                                    @foreach ($existingReviewers as $rev)
                                        @php($worst = $coiByReviewer[$rev->id] ?? null)
                                        <option value="{{ $rev->id }}">{{ $worst === 'high' ? '⛔ ' : ($worst ? '⚠ ' : '') }}{{ $rev->full_name }} ({{ $rev->email }})</option>
                                    @endforeach
                                </select>
                                <span class="hint">⚠ / ⛔ = conflit d’intérêts potentiel détecté (voir ci-dessus).</span>
                            </div>
                        @endif
                        <div class="ukc-field" style="margin-bottom:8px;">
                            <label for="reviewerName">Nom</label>
                            <input type="text" id="reviewerName" name="reviewerName" value="{{ old('reviewerName') }}" placeholder="Prénom Nom">
                            @error('reviewerName') <span class="error">{{ $message }}</span> @enderror
                        </div>
                        <div class="ukc-field" style="margin-bottom:10px;">
                            <label for="reviewerEmail">E-mail</label>
                            <input type="email" id="reviewerEmail" name="reviewerEmail" value="{{ old('reviewerEmail') }}" placeholder="relecteur@exemple.tg">
                            @error('reviewerEmail') <span class="error">{{ $message }}</span> @enderror
                        </div>
                        <button type="submit" class="ukc-btn ukc-btn--blue ukc-btn--sm" style="width:100%;">
                            <x-ukc.icon name="user-plus" :size="16" /> Assigner et inviter
                        </button>
                        <p class="hint" style="margin-top:8px;font-size:12px;color:var(--ukc-light);">Le relecteur reçoit un e-mail avec le lien de son portail d’évaluation en aveugle.</p>
                    </form>
                </div>

                {{-- Phase de réponse aux relecteurs (rebuttal) --}}
                @if ($completedCount)
                    <div class="ukc-card">
                        <h2><span><x-ukc.icon name="pen" :size="18" /> Réponse de l’auteur</span></h2>
                        <p class="desc">Ouvrez la phase de réponse pour révéler les commentaires « à l’auteur » et lui permettre d’y répondre avant la décision.</p>

                        @if ($s->rebuttalOpen())
                            <span class="ukc-pill ukc-pill--info" style="margin-bottom:10px;display:inline-block;">Phase ouverte</span>
                            @if ($s->hasRebuttal())
                                @php($reb = $s->rebuttal())
                                <div style="background:#f5f7ff;border:1px solid #c7d2fe;border-radius:10px;padding:10px 12px;margin:8px 0;font-size:.9rem;">
                                    <div style="font-weight:700;margin-bottom:4px;">Réponse de l’auteur{{ ! empty($reb['submitted_at']) ? ' - '.\Illuminate\Support\Carbon::parse($reb['submitted_at'])->translatedFormat('d/m/Y H:i') : '' }}</div>
                                    <div style="white-space:pre-line;">{{ $s->rebuttalText() }}</div>
                                </div>
                            @else
                                <p style="font-size:.88rem;color:var(--ukc-muted);">En attente de la réponse de l’auteur.</p>
                            @endif
                        @else
                            <p style="font-size:.88rem;color:var(--ukc-muted);">Phase de réponse fermée.</p>
                        @endif

                        <form method="POST" action="{{ route('admin.submissions.rebuttal.toggle', $s) }}" data-ajax data-ajax-msg="Phase de réponse mise à jour." style="margin-top:8px;">
                            @csrf
                            <button type="submit" class="ukc-btn ukc-btn--{{ $s->rebuttalOpen() ? 'ghost' : 'blue' }} ukc-btn--sm">
                                {{ $s->rebuttalOpen() ? 'Fermer la phase de réponse' : 'Ouvrir la phase de réponse' }}
                            </button>
                        </form>
                    </div>
                @endif

                {{-- Informations --}}
                <div class="ukc-card">
                    <h2>Informations</h2>
                    <div class="info-list">
                        <div><span class="k">Référence</span><br><code>{{ $s->ref_code }}</code></div>
                        <div><span class="k">Événement</span><br>{{ $s->event?->title ?? '-' }}</div>
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
                                <a href="{{ route('admin.participants.preview', $s->participant) }}">Ouvrir le profil →</a>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-layouts.admin>
