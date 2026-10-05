<x-layouts.participant :title="__('Mes relectures')" :subtitle="__('Espace relecteur · évaluations confiées')">
<div class="account-reviews" x-data="ukcReviews(@js($reviewsData), @js($recommendations), {{ $scale }})">

    {{-- Sous-navigation : elle n'a de sens que s'il y a réellement deux tâches. --}}
    @if ($biddable->isNotEmpty() || $tab === 'candidatures')
    <div class="ukc-nav-docs">
        <a href="{{ route('account.reviews') }}" @class(['active' => $tab === 'relectures'])>
            {{ __('Mes relectures') }}
            @if ($reviews->count())<span class="nav-count">{{ $reviews->count() }}</span>@endif
        </a>
        <a href="{{ route('account.reviews', ['tab' => 'candidatures']) }}" @class(['active' => $tab === 'candidatures'])>
            {{ __('Appel à candidatures') }}
            @if ($biddable->isNotEmpty())<span class="nav-count">{{ $biddable->count() }}</span>@endif
        </a>
    </div>
    @endif

    @if ($tab === 'relectures')
    {{-- Indicateurs --}}
    <div class="ukc-stats" style="margin-bottom:16px;">
        <div class="ukc-stat-card tw-stat"><div class="tw-stat__icon tw-stat__icon--slate"><x-ukc.icon name="inbox" :size="20" /></div><div class="lbl">{{ __('Relectures') }}</div><div class="val">{{ $reviews->count() }}</div></div>
        <div class="ukc-stat-card tw-stat">
            <div class="tw-stat__icon tw-stat__icon--blue"><x-ukc.icon name="mail" :size="20" /></div>
            <div class="lbl">{{ __('À confirmer') }}</div>
            <div class="val">{{ $invitedCount }}</div>
            @if ($lateInvited > 0)
                <div class="hint" style="color:#b4231c;font-weight:600;">{{ $lateInvited }} {{ __('en retard') }}</div>
            @endif
        </div>
        <div class="ukc-stat-card tw-stat">
            <div class="tw-stat__icon tw-stat__icon--amber"><x-ukc.icon name="pen" :size="20" /></div>
            <div class="lbl">{{ __('À évaluer') }}</div>
            <div class="val">{{ $acceptedCount }}</div>
            <div class="hint" style="{{ $lateAccepted > 0 ? 'color:#b4231c;font-weight:600;' : '' }}">
                {{ $lateAccepted > 0 ? $lateAccepted.' '.__('en retard') : __('dans les délais') }}
            </div>
        </div>
        <div class="ukc-stat-card tw-stat"><div class="tw-stat__icon tw-stat__icon--green"><x-ukc.icon name="check-circle" :size="20" /></div><div class="lbl">{{ __('Rendues') }}</div><div class="val green">{{ $doneCount }}</div></div>
        <div class="ukc-stat-card tw-stat">
            <div class="tw-stat__icon tw-stat__icon--slate"><x-ukc.icon name="close" :size="20" /></div>
            <div class="lbl">{{ __('Refusées') }}</div>
            <div class="val">{{ $declinedCount }}</div>
            @if ($cancelledCount > 0)
                <div class="hint">{{ $cancelledCount }} {{ __('retirée(s) par le comité') }}</div>
            @endif
        </div>
    </div>

    {{-- Filtres (même grammaire que les listes du back-office : on filtre la
         liste, jamais les compteurs ci-dessus). --}}
    @if (count($reviewsData) || $status !== '' || $search !== '' || $event_id !== '' || $track !== '')
        <form method="GET" class="filter-bar filter-bar--inline">
            <select name="status" onchange="this.form.submit()" title="{{ __('État de la relecture') }}">
                <option value="">{{ __('Tous les états') }}</option>
                @foreach ($statuses as $key => $label)
                    <option value="{{ $key }}" @selected($status === $key)>{{ $label }}</option>
                @endforeach
            </select>
            @if ($events->count() > 1)
                <select name="event_id" onchange="this.form.submit()" title="{{ __('Événement') }}">
                    <option value="">{{ __('Tous les événements') }}</option>
                    @foreach ($events as $ed)
                        <option value="{{ $ed->id }}" @selected($event_id === (string) $ed->id)>{{ $ed->title ?: $ed->year }}</option>
                    @endforeach
                </select>
            @endif
            @if ($tracks->isNotEmpty())
                <select name="track" onchange="this.form.submit()" data-tom title="{{ __('Axe thématique') }}">
                    <option value="">{{ __('Tous les axes') }}</option>
                    @foreach ($tracks as $t)<option value="{{ $t }}" @selected($track === $t)>{{ $t }}</option>@endforeach
                </select>
            @endif
            <input type="search" name="search" value="{{ $search }}" placeholder="{{ __('Référence ou titre…') }}"
                   style="width:230px;" onkeydown="if(event.key==='Enter'){this.form.submit();}">
            @if ($status !== '' || $search !== '' || $event_id !== '' || $track !== '')
                <a href="{{ route('account.reviews') }}" class="ukc-btn ukc-btn--ghost ukc-btn--sm">{{ __('Réinitialiser') }}</a>
            @endif
        </form>
    @endif
    {{-- Liste des relectures (table Tailwise) --}}
    <div class="ukc-card" style="padding:0;">
        @if (count($reviewsData))
            <div class="ukc-table-wrap">
                <table class="ukc-table">
                    <thead>
                        <tr>
                            <th>{{ __('Réf.') }}</th>
                            <th>{{ __('Titre') }}</th>
                            <th>{{ __('Axe') }}</th>
                            <th>{{ __('Recommandation') }}</th>
                            <th>{{ __('Échéance') }}</th>
                            <th>{{ __('État') }}</th>
                            <th style="text-align:right;">{{ __('Action') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        <template x-for="r in reviews" :key="r.id">
                            <tr>
                                <td style="font-family:var(--ukc-mono);font-weight:700;" x-text="r.ref || '-'"></td>
                                <td class="title" x-text="r.title"></td>
                                <td x-text="r.track || '-'"></td>
                                <td>
                                    <template x-if="r.completed">
                                        <span style="font-size:13px;">
                                            <span x-text="recoLabel(r.recommendation) || '-'"></span>
                                            <template x-if="r.score !== null"><strong style="color:var(--ukc-blue);" x-text="' · ' + r.score + '/20'"></strong></template>
                                        </span>
                                    </template>
                                    <template x-if="!r.completed"><span style="color:var(--ukc-muted);">-</span></template>
                                </td>
                                <td>
                                    <template x-if="r.due">
                                        <span style="font-size:13px;">
                                            <span x-text="r.due"></span>
                                            <template x-if="r.late">
                                                <em style="display:block;font-size:12px;color:#b4231c;font-style:normal;"
                                                    x-text="'{{ __('en retard de') }} ' + r.daysLate + ' {{ __('j') }}'"></em>
                                            </template>
                                        </span>
                                    </template>
                                    <template x-if="!r.due"><span style="color:var(--ukc-muted);">-</span></template>
                                </td>
                                <td>
                                    <span class="ukc-pill" :class="'ukc-pill--' + r.statusPill" x-text="r.statusLabel"></span>
                                    <template x-if="r.declineReason">
                                        <em style="display:block;font-size:12px;color:var(--ukc-muted);font-style:normal;margin-top:3px;" x-text="r.declineReason"></em>
                                    </template>
                                </td>
                                <td style="text-align:right;white-space:nowrap;">
                                    {{-- Événement archivée : plus d'action possible, mais le
                                         relecteur garde l'accès à ce qu'il a rendu. --}}
                                    <template x-if="r.frozen">
                                        <span style="display:inline-flex;align-items:center;gap:8px;justify-content:flex-end;">
                                            <span class="ukc-pill ukc-pill--muted" title="{{ __('Événement archivée : lecture seule.') }}">{{ __('Archivée') }}</span>
                                            <template x-if="r.completed">
                                                <button type="button" class="ukc-btn ukc-btn--outline ukc-btn--sm" @click="edit(r)">
                                                    <x-ukc.icon name="eye" :size="15" /> {{ __('Consulter') }}
                                                </button>
                                            </template>
                                        </span>
                                    </template>
                                    {{-- Invitation : on répond d'abord (accepter / refuser). --}}
                                    <template x-if="! r.frozen && r.status === 'invited'">
                                        <span style="display:inline-flex;gap:8px;justify-content:flex-end;">
                                            <form method="POST" :action="r.respondUrl" style="display:inline;">
                                                @csrf
                                                <input type="hidden" name="answer" value="accept">
                                                <button type="submit" class="ukc-btn ukc-btn--green ukc-btn--sm">
                                                    <x-ukc.icon name="check" :size="15" /> {{ __('Accepter') }}
                                                </button>
                                            </form>
                                            <button type="button" class="ukc-btn ukc-btn--outline ukc-btn--sm" @click="askDecline(r)">
                                                <x-ukc.icon name="close" :size="15" /> {{ __('Refuser') }}
                                            </button>
                                        </span>
                                    </template>
                                    {{-- Acceptée ou rendue : on évalue / on revoit. --}}
                                    <template x-if="! r.frozen && (r.status === 'accepted' || r.completed)">
                                        <button type="button" :class="r.completed ? 'ukc-btn ukc-btn--outline ukc-btn--sm' : 'ukc-btn ukc-btn--blue ukc-btn--sm'" @click="edit(r)">
                                            <x-ukc.icon name="pen" :size="15" /> <span x-text="r.completed ? '{{ __('Revoir') }}' : '{{ __('Évaluer') }}'"></span>
                                        </button>
                                    </template>
                                    <template x-if="! r.frozen && (r.status === 'declined' || r.status === 'cancelled')">
                                        <span style="color:var(--ukc-muted);font-size:13px;">-</span>
                                    </template>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>
        @elseif ($status !== '' || $search !== '' || $event_id !== '' || $track !== '')
            <div class="ukc-empty" style="border:none;">
                <span class="ico"><x-ukc.icon name="search" :size="28" /></span>
                <h3>{{ __('Aucune relecture pour ce filtre') }}</h3>
                <p>{{ __('Élargissez la recherche ou réinitialisez les filtres.') }}</p>
            </div>
        @else
            <div class="ukc-empty" style="border:none;">
                <span class="ico"><x-ukc.icon name="inbox" :size="28" /></span>
                <h3>{{ __('Aucune relecture confiée') }}</h3>
                <p>{{ __('Les communications qui vous sont confiées pour évaluation apparaîtront ici.') }}</p>
            </div>
        @endif
    </div>

    @endif

    {{-- ===== Appel à candidatures (préférences + conflits) ===== --}}
    @if ($tab === 'candidatures' && $biddable->isEmpty())
        <div class="ukc-card">
            <div class="ukc-empty" style="border:none;">
                <span class="ico"><x-ukc.icon name="inbox" :size="28" /></span>
                <h3>{{ __('Aucune communication à candidater') }}</h3>
                <p>{{ __('Les communications ouvertes à l’évaluation et qui ne vous sont pas déjà confiées apparaîtront ici.') }}</p>
            </div>
        </div>
    @elseif ($tab === 'candidatures')
        <div>
            <p style="color:var(--ukc-muted);font-size:13.5px;margin:0 0 14px;">
                {{ __('Indiquez les communications que vous souhaitez évaluer et signalez tout conflit d’intérêt. Vos préférences aident le comité à répartir les relectures.') }}
            </p>
            <form method="POST" action="{{ route('account.reviews.bids') }}">
                @csrf
                <div style="display:flex;flex-direction:column;gap:12px;">
                    @foreach ($biddable as $sub)
                        @php($bid = $bids[$sub->id] ?? null)
                        <div class="ukc-card" style="padding:16px 18px;">
                            <div style="display:flex;gap:10px;align-items:baseline;flex-wrap:wrap;">
                                <span style="font-family:var(--ukc-mono);font-weight:700;font-size:12.5px;color:var(--ukc-blue);">{{ $sub->ref_code }}</span>
                                @if ($sub->track)<span class="ukc-pill ukc-pill--info">{{ \Illuminate\Support\Str::limit($sub->track, 28) }}</span>@endif
                            </div>
                            <div style="font-weight:700;margin:6px 0 4px;line-height:1.35;">{{ $sub->title }}</div>
                            <p style="color:var(--ukc-muted);font-size:13.5px;line-height:1.55;margin:0 0 12px;">{{ \Illuminate\Support\Str::limit(strip_tags((string) $sub->abstract), 260) }}</p>
                            <div style="display:flex;flex-wrap:wrap;gap:8px 18px;align-items:center;">
                                @foreach ($interests as $key => $label)
                                    <label style="display:inline-flex;align-items:center;gap:6px;font-size:13.5px;cursor:pointer;">
                                        <input type="radio" name="bids[{{ $sub->id }}][interest]" value="{{ $key }}" @checked(($bid?->interest ?? 'neutral') === $key)>
                                        {{ $label }}
                                    </label>
                                @endforeach
                                <span style="flex:1;"></span>
                                <label style="display:inline-flex;align-items:center;gap:6px;font-size:13.5px;cursor:pointer;color:#b4231c;">
                                    <input type="checkbox" name="bids[{{ $sub->id }}][conflict]" value="1" @checked($bid?->conflict)>
                                    {{ __('Conflit d’intérêt') }}
                                </label>
                            </div>
                        </div>
                    @endforeach
                </div>
                <div style="margin-top:14px;text-align:right;">
                    <button type="submit" class="ukc-btn ukc-btn--blue">{{ __('Enregistrer mes préférences') }}</button>
                </div>
            </form>
        </div>
    @endif

    {{-- ===== Refuser une relecture (motif communiqué au comité) ===== --}}
    <x-ukc.slideover open="declineOpen" onClose="declineOpen = false" width="460px">
        <x-slot:header>{{ __('Refuser cette relecture') }}</x-slot:header>
        <p style="color:var(--ukc-muted);font-size:13.5px;line-height:1.55;">
            {{ __('Votre refus libère la place : le comité pourra solliciter un autre relecteur. Un motif, même bref, l’aide à mieux répartir les relectures suivantes.') }}
        </p>
        <div class="ukc-card" style="padding:12px 14px;margin:12px 0;">
            <div style="font-family:var(--ukc-mono);font-weight:700;font-size:12.5px;color:var(--ukc-muted);" x-text="declining.ref"></div>
            <div style="font-weight:700;line-height:1.35;" x-text="declining.title"></div>
        </div>
        <form method="POST" :action="declining.respondUrl" id="declineForm">
            @csrf
            <input type="hidden" name="answer" value="decline">
            <div class="ukc-form-row">
                <label>{{ __('Motif') }} <span class="hint">({{ __('facultatif') }})</span></label>
                <textarea name="decline_reason" rows="3" maxlength="500"
                          placeholder="{{ __('Hors de mon domaine, indisponibilité, conflit d’intérêt…') }}"></textarea>
            </div>
        </form>
        <x-slot:footer>
            <span style="flex:1;"></span>
            <button type="button" class="ukc-btn ukc-btn--ghost" @click="declineOpen = false">{{ __('Annuler') }}</button>
            <button type="submit" form="declineForm" class="ukc-btn ukc-btn--danger">
                <x-ukc.icon name="close" :size="16" /> {{ __('Confirmer le refus') }}
            </button>
        </x-slot:footer>
    </x-ukc.slideover>

    {{-- ===== Panneau d'évaluation (en place, sans changer de page) ===== --}}
    <x-ukc.slideover open="open" onClose="open = false" width="560px">
        <x-slot:header><span x-text="cur.frozen ? '{{ __('Évaluation rendue (événement archivée)') }}' : (cur.completed ? '{{ __('Revoir mon évaluation') }}' : '{{ __('Évaluer la communication') }}')"></span></x-slot:header>

        {{-- Communication (en aveugle : pas d'auteurs) --}}
        <div style="margin-bottom:16px;">
            <div style="font-family:var(--ukc-mono);font-weight:700;color:var(--ukc-muted);font-size:12.5px;" x-text="cur.ref"></div>
            <h3 style="margin:6px 0;" x-text="cur.title"></h3>
            <div style="display:flex;gap:8px;flex-wrap:wrap;margin-bottom:8px;">
                <template x-if="cur.track"><span class="ukc-pill ukc-pill--info" x-text="cur.track"></span></template>
                <span class="ukc-pill ukc-pill--muted" x-text="cur.type"></span>
                <span class="ukc-pill ukc-pill--muted" x-text="cur.edition"></span>
            </div>
            <div class="ukc-rte" style="font-size:13.5px;" x-html="cur.abstract"></div>
            <template x-if="cur.keywords">
                <p style="margin-top:8px;font-size:12.5px;color:var(--ukc-muted);"><strong>{{ __('Mots-clés') }} :</strong> <span x-text="cur.keywords"></span></p>
            </template>
            <template x-if="cur.fileUrl">
                <a :href="cur.fileUrl" target="_blank" class="ukc-btn ukc-btn--outline ukc-btn--sm" style="margin-top:10px;"><x-ukc.icon name="file" :size="15" /> {{ __('Fichier joint') }}</a>
            </template>
        </div>

        <form @submit.prevent="save()" id="reviewForm" :inert="cur.frozen">
            {{-- Grille de critères notés (0–scale). Note /20 dérivée. --}}
            <div class="ukc-form-row">
                <label style="display:flex;justify-content:space-between;align-items:baseline;gap:8px;flex-wrap:wrap;">
                    <span>{{ __('Grille d’évaluation') }}</span>
                    <span style="font-weight:500;font-size:12.5px;">{{ __('Note globale') }} : <strong x-text="overall() === null ? '-' : overall() + '/20'"></strong></span>
                </label>
                <div>
                    <template x-for="c in (cur.criteria || [])" :key="c.key">
                        <div style="display:flex;justify-content:space-between;gap:14px;align-items:center;padding:9px 0;border-bottom:1px solid var(--ukc-border);flex-wrap:wrap;">
                            <span style="font-weight:600;flex:1;min-width:180px;font-size:13.5px;" x-text="c.label"></span>
                            <div style="display:flex;align-items:center;gap:8px;">
                                <template x-for="n in (scale + 1)" :key="n">
                                    <label class="axis-check" style="cursor:pointer;">
                                        <input type="radio" :name="'scores[' + c.key + ']'" :value="n - 1" x-model.number="cur.scores[c.key]">
                                        <span x-text="n - 1"></span>
                                    </label>
                                </template>
                            </div>
                        </div>
                    </template>
                </div>
            </div>

            <div class="ukc-form-row">
                <label>{{ __('Recommandation') }} <span class="req">*</span></label>
                <select x-model="cur.recommendation" required>
                    <option value="">{{ __('- Choisir -') }}</option>
                    <template x-for="(label, key) in recommendations" :key="key">
                        <option :value="key" x-text="label"></option>
                    </template>
                </select>
            </div>

            <div class="ukc-form-row">
                <label>{{ __('Commentaires à l’auteur') }}</label>
                <textarea rows="4" x-model="cur.comments" placeholder="{{ __('Retour constructif communiqué à l’auteur.') }}"></textarea>
            </div>

            <div class="ukc-form-row">
                <label>{{ __('Commentaires confidentiels') }} <span class="hint">({{ __('comité uniquement') }})</span></label>
                <textarea rows="3" x-model="cur.comments_private" placeholder="{{ __('Remarques réservées au comité.') }}"></textarea>
            </div>
        </form>

        <x-slot:footer>
            <span style="flex:1;"></span>
            <button type="button" class="ukc-btn ukc-btn--ghost" @click="open = false">{{ __('Annuler') }}</button>
            <button type="submit" form="reviewForm" class="ukc-btn ukc-btn--blue" :disabled="saving" x-show="! cur.frozen">
                <x-ukc.icon name="check" :size="16" /> {{ __('Enregistrer mon évaluation') }}
            </button>
        </x-slot:footer>
    </x-ukc.slideover>
</div>

@push('scripts')
<script>
function ukcReviews(reviews, recommendations, scale) {
    return {
        reviews: reviews,
        recommendations: recommendations,
        scale: scale,
        open: false,
        declineOpen: false,
        declining: {},
        saving: false,
        cur: { scores: {}, criteria: [] },

        recoLabel(key) {
            return key ? (this.recommendations[key] || '') : '';
        },

        overall() {
            const c = this.cur;
            if (!c.criteria || !c.criteria.length) return null;
            const vals = c.criteria
                .map(cr => Number(c.scores ? c.scores[cr.key] : undefined))
                .filter(n => !Number.isNaN(n));
            if (!vals.length) return null;
            return Math.round((vals.reduce((a, b) => a + b, 0) / vals.length) / this.scale * 20 * 100) / 100;
        },

        askDecline(r) {
            this.declining = r;
            this.declineOpen = true;
        },

        edit(r) {
            this.cur = JSON.parse(JSON.stringify(r));
            if (!this.cur.scores) this.cur.scores = {};
            this.open = true;
        },

        async save() {
            if (this.saving) return;
            this.saving = true;
            try {
                const res = await fetch(this.cur.submitUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                    },
                    body: JSON.stringify({
                        recommendation: this.cur.recommendation || '',
                        scores: this.cur.scores || {},
                        comments: this.cur.comments || '',
                        comments_private: this.cur.comments_private || '',
                    }),
                });
                if (!res.ok) {
                    const err = await res.json().catch(() => ({}));
                    const first = err.errors ? Object.values(err.errors)[0]?.[0] : null;
                    window.ukcToast?.(first || '{{ __('Vérifiez le formulaire.') }}', 'error');
                    return;
                }
                const data = await res.json();
                const idx = this.reviews.findIndex(x => x.id === data.review.id);
                if (idx >= 0) this.reviews[idx] = Object.assign({}, this.reviews[idx], data.review);
                this.open = false;
                window.ukcToast?.('{{ __('Votre évaluation a été enregistrée. Merci !') }}', 'success');
            } catch (e) {
                window.ukcToast?.('{{ __('Une erreur est survenue.') }}', 'error');
            } finally {
                this.saving = false;
            }
        },
    };
}
</script>
@endpush
</x-layouts.participant>
