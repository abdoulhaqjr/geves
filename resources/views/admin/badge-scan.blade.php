<x-layouts.admin title="Pointage des badges" subtitle="Contrôle d’accès à l’entrée du colloque">
    <div x-data="badgeScan({{ $checkedCount }}, {{ $totalCount }}, {{ Illuminate\Support\Js::from($sessions) }})" @scan.window="lookup($event.detail)">
        <div style="display:flex;align-items:center;justify-content:space-between;gap:16px;flex-wrap:wrap;margin-bottom:18px;">
            <p style="color:var(--ukc-muted);margin:0;">
                <template x-if="!sessionId"><span>Scannez le QR d’un badge (ou saisissez son code) pour enregistrer la présence à l’accueil.</span></template>
                <template x-if="sessionId"><span>Mode séance : chaque scan enregistre la présence <strong>à la séance sélectionnée</strong>.</span></template>
            </p>
            <span class="ukc-pill ukc-pill--info"><span x-text="checkedCount"></span> / <span x-text="totalCount"></span> pointé(s) à l’accueil</span>
        </div>

        {{-- Sélecteur du mode de pointage : accueil (global) ou séance (présence effective) --}}
        @if (count($sessions))
            <div class="ukc-card" style="margin-bottom:18px;">
                <div class="ukc-field" style="margin:0;">
                    <label for="session-mode"><x-ukc.icon name="calendar" :size="16" /> Mode de pointage</label>
                    <select id="session-mode" x-model.number="sessionId" style="max-width:520px;">
                        <option value="0">Accueil — présence globale au colloque</option>
                        @foreach ($sessions as $s)
                            <option value="{{ $s['id'] }}">Séance · {{ $s['edition'] }} — {{ $s['label'] }}</option>
                        @endforeach
                    </select>
                    <p class="hint" style="margin-top:6px;">La présence à une séance vaut présence à l’accueil et sert de preuve pour l’attestation de participation.</p>
                </div>
            </div>
        @endif

        <div class="ukc-alert" x-show="message" x-cloak :class="messageType === 'ok' ? 'ukc-alert--ok' : 'ukc-alert--err'">
            <span class="ico">
                <template x-if="messageType === 'ok'"><span><x-ukc.icon name="check-circle" :size="18" /></span></template>
                <template x-if="messageType !== 'ok'"><span><x-ukc.icon name="info" :size="18" /></span></template>
            </span>
            <div x-text="message"></div>
        </div>

        <div class="ukc-grid-2">
            {{-- Scanner + saisie manuelle --}}
            <div class="ukc-card">
                <h2><span><x-ukc.icon name="video" :size="20" /> Scanner un badge</span></h2>
                <div id="badge-scanner" class="badge-scanner"></div>
                <div class="ukc-field" style="margin-top:14px;">
                    <label for="code">Saisie manuelle du code <span class="hint">(sous le QR, ex. 3-42)</span></label>
                    <form @submit.prevent="lookup()" style="display:flex;gap:8px;">
                        <input type="text" id="code" x-model="code" placeholder="édition-participant" style="flex:1;">
                        <button type="submit" class="ukc-btn ukc-btn--blue"><x-ukc.icon name="search" :size="16" /> Chercher</button>
                    </form>
                </div>
            </div>

            {{-- Résultat --}}
            <div class="ukc-card">
                <h2><span><x-ukc.icon name="user" :size="20" /> Participant</span></h2>
                <template x-if="found">
                    <div>
                        <div style="display:flex;align-items:center;gap:14px;margin-bottom:12px;">
                            <div class="ukc-user-chip"><div class="av" x-text="found.name.split(' ').map(w => w.substr(0,1)).slice(0,2).join('')"></div></div>
                            <div style="min-width:0;">
                                <div style="font-weight:800;font-size:1.05rem;" x-text="found.name"></div>
                                <div style="font-size:.85rem;color:var(--ukc-muted);" x-text="found.role + (found.institution ? ' · ' + found.institution : '')"></div>
                            </div>
                        </div>
                        <div style="display:flex;flex-wrap:wrap;gap:6px;margin-bottom:14px;">
                            <template x-if="found.paid"><span class="ukc-pill ukc-pill--ok">Réglé</span></template>
                            <template x-if="!found.paid"><span class="ukc-pill ukc-pill--muted">Paiement en attente</span></template>
                            <template x-if="found.checkedIn"><span class="ukc-pill ukc-pill--info">Accueil : pointé à <span x-text="found.checkedAt"></span></span></template>
                            <template x-if="!found.checkedIn"><span class="ukc-pill ukc-pill--soon">Non pointé</span></template>
                            <template x-if="sessionId && found.inSession"><span class="ukc-pill ukc-pill--ok">Présent à cette séance</span></template>
                            <template x-if="sessionId && found.sessionMismatch"><span class="ukc-pill ukc-pill--err">Autre édition</span></template>
                        </div>
                        <div style="display:flex;gap:8px;flex-wrap:wrap;">
                            {{-- Mode accueil --}}
                            <template x-if="!sessionId">
                                <button type="button" class="ukc-btn ukc-btn--green" @click="checkIn()" :disabled="found.checkedIn">
                                    <x-ukc.icon name="check" :size="18" /> <span x-text="found.checkedIn ? 'Déjà présent' : 'Marquer présent'"></span>
                                </button>
                            </template>
                            {{-- Mode séance --}}
                            <template x-if="sessionId">
                                <button type="button" class="ukc-btn ukc-btn--green" @click="checkIn()" :disabled="found.inSession || found.sessionMismatch">
                                    <x-ukc.icon name="check" :size="18" /> <span x-text="found.inSession ? 'Déjà à la séance' : 'Pointer à la séance'"></span>
                                </button>
                            </template>
                            <button type="button" class="ukc-btn ukc-btn--ghost ukc-btn--sm" @click="clearFound()">Suivant</button>
                        </div>
                    </div>
                </template>
                <template x-if="!found">
                    <div class="ukc-empty" style="padding:28px 12px;">
                        <p style="margin:0;">Aucun badge scanné pour l’instant. Présentez un QR à la caméra ou saisissez un code.</p>
                    </div>
                </template>

                <div x-show="recent.length" style="margin-top:16px;border-top:1px solid var(--ukc-border);padding-top:12px;">
                    <div style="font-size:12px;font-weight:700;text-transform:uppercase;letter-spacing:.04em;color:var(--ukc-light);margin-bottom:8px;">Derniers pointages</div>
                    <template x-for="(r, i) in recent" :key="i">
                        <div style="display:flex;justify-content:space-between;font-size:13.5px;padding:4px 0;">
                            <span x-text="r.name"></span><span style="color:var(--ukc-muted);" x-text="r.at"></span>
                        </div>
                    </template>
                </div>
            </div>
        </div>
    </div>
</x-layouts.admin>
