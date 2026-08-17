<div class="admin-page admin-reports" x-data="{ tab: 'overview' }">
    {{-- Barre : sélecteur d'édition + exports globaux --}}
    <div style="display:flex;align-items:center;justify-content:space-between;gap:16px;flex-wrap:wrap;margin-bottom:16px;">
        <div class="ukc-field" style="min-width:220px;margin:0;">
            <select wire:model.live="edition_id">
                <option value="">— Édition —</option>
                @foreach ($editions as $ed)
                    <option value="{{ $ed->id }}">{{ $ed->title ?: $ed->year }}</option>
                @endforeach
            </select>
        </div>

        @if ($edition)
            <div style="display:flex;gap:8px;flex-wrap:wrap;">
                <a href="{{ route('admin.reports.xlsx', $edition) }}" class="ukc-btn ukc-btn--green ukc-btn--sm">
                    <x-ukc.icon name="sheet" :size="16" /> Excel (.xlsx)
                </a>
                <a href="{{ route('admin.reports.pdf', $edition) }}" target="_blank" class="ukc-btn ukc-btn--blue ukc-btn--sm">
                    <x-ukc.icon name="file" :size="16" /> Rapport PDF
                </a>
            </div>
        @endif
    </div>

    @if ($summary && $edition)
        {{-- Données des graphiques (lues par charts.js) --}}
        <script type="application/json" id="dashboardData">@json($chartData)</script>

        <div class="ukc-tabs">
            <button type="button" @click="tab='overview'" :class="{ active: tab==='overview' }">Vue d’ensemble</button>
            <button type="button" @click="tab='submissions'" :class="{ active: tab==='submissions' }">Soumissions</button>
            <button type="button" @click="tab='participants'" :class="{ active: tab==='participants' }">Participants</button>
            <button type="button" @click="tab='payments'" :class="{ active: tab==='payments' }">Paiements</button>
            <button type="button" @click="tab='exports'" :class="{ active: tab==='exports' }">Exports</button>
            <button type="button" @click="tab='ai'" :class="{ active: tab==='ai' }">Export IA</button>
        </div>

        {{-- Vue d'ensemble --}}
        <div class="ukc-panel" :class="{ active: tab==='overview' }">
            <div class="ukc-stats">
                <div class="ukc-stat-card"><div class="lbl">Participants</div><div class="val blue">{{ $summary['participants'] }}</div></div>
                <div class="ukc-stat-card"><div class="lbl">Communications</div><div class="val">{{ $summary['submissions'] }}</div></div>
                <div class="ukc-stat-card"><div class="lbl">Acceptées</div><div class="val green">{{ $summary['accepted'] }}</div></div>
                <div class="ukc-stat-card"><div class="lbl">Ont payé</div><div class="val">{{ $summary['paidCount'] }}</div></div>
                <div class="ukc-stat-card"><div class="lbl">Encaissé</div><div class="val green">{{ number_format($summary['collected'], 0, ',', ' ') }} <span style="font-size:.9rem;">{{ $summary['currency'] }}</span></div></div>
                <div class="ukc-stat-card"><div class="lbl">En attente</div><div class="val warn">{{ number_format($summary['pending'], 0, ',', ' ') }} <span style="font-size:.9rem;">{{ $summary['currency'] }}</span></div></div>
            </div>

            <div class="charts-grid">
                <div class="ukc-card">
                    <h2>Répartition des statuts</h2>
                    <div class="chart-wrap chart-wrap--donut"><canvas id="chartStatus"></canvas></div>
                </div>
                <div class="ukc-card">
                    <h2>Soumissions par mois</h2>
                    <div class="chart-wrap" style="height:220px;"><canvas id="chartSubsMonth"></canvas></div>
                </div>
                <div class="ukc-card">
                    <h2>Recettes cumulées</h2>
                    <div class="chart-wrap" style="height:220px;"><canvas id="chartPayments"></canvas></div>
                </div>
                <div class="ukc-card">
                    <h2>Axes thématiques</h2>
                    <div class="chart-wrap" style="height:220px;"><canvas id="chartAxes"></canvas></div>
                </div>
            </div>
        </div>

        {{-- Soumissions --}}
        <div class="ukc-panel" :class="{ active: tab==='submissions' }">
            @include('livewire.admin.reports._table', ['title' => 'Communications', 'headers' => $report->submissionHeaders(), 'rows' => $report->submissionRows()])
        </div>

        {{-- Participants --}}
        <div class="ukc-panel" :class="{ active: tab==='participants' }">
            @include('livewire.admin.reports._table', ['title' => 'Participants', 'headers' => $report->participantHeaders(), 'rows' => $report->participantRows()])
        </div>

        {{-- Paiements --}}
        <div class="ukc-panel" :class="{ active: tab==='payments' }">
            @include('livewire.admin.reports._table', ['title' => 'Paiements', 'headers' => $report->paymentHeaders(), 'rows' => $report->paymentRows()])
        </div>

        {{-- Exports --}}
        <div class="ukc-panel" :class="{ active: tab==='exports' }">
            <div class="ukc-card">
                <h2>Exports prêts</h2>
                <p class="desc">
                    Le classeur Excel contient les feuilles <strong>Synthèse</strong>, <strong>Participants</strong>,
                    <strong>Communications</strong> et <strong>Paiements</strong>. Le rapport PDF est imprimable
                    (Imprimer → Enregistrer en PDF).
                </p>
                <div style="display:flex;gap:10px;flex-wrap:wrap;">
                    <a href="{{ route('admin.reports.xlsx', $edition) }}" class="ukc-btn ukc-btn--green">
                        <x-ukc.icon name="sheet" :size="18" /> Exporter Excel (.xlsx)
                    </a>
                    <a href="{{ route('admin.reports.pdf', $edition) }}" target="_blank" class="ukc-btn ukc-btn--blue">
                        <x-ukc.icon name="file" :size="18" /> Rapport PDF
                    </a>
                </div>
            </div>
        </div>

        {{-- Export personnalisé (IA) --}}
        <div class="ukc-panel" :class="{ active: tab==='ai' }">
            <div class="ukc-card">
                <h2><span><x-ukc.icon name="sparkles" :size="18" /> Export personnalisé (IA)</span></h2>
                <p class="desc">
                    Décrivez le rapport souhaité (ou joignez un exemple à reproduire). L’IA rédige un
                    <strong>brouillon</strong> à partir des chiffres réels de l’édition — <strong>la décision et la
                    diffusion restent à votre main</strong>.
                </p>

                <div class="ukc-form-grid">
                    <div class="ukc-field full">
                        <label for="aiBrief">Que doit contenir le rapport ?</label>
                        <textarea id="aiBrief" wire:model="aiBrief" rows="3"
                                  placeholder="Ex. : synthèse pour le conseil scientifique — chiffres clés, taux d’acceptation, recommandations."></textarea>
                        @error('aiBrief') <span class="error">{{ $message }}</span> @enderror
                    </div>
                    <div class="ukc-field full">
                        <label for="aiExample">Exemple de rapport <span class="hint">(optionnel — PDF/DOC/DOCX/TXT, l’IA reproduit sa structure)</span></label>
                        <input type="file" id="aiExample" wire:model="aiExample" accept=".pdf,.doc,.docx,.txt">
                        <div wire:loading wire:target="aiExample" class="hint">Téléversement…</div>
                        @error('aiExample') <span class="error">{{ $message }}</span> @enderror
                    </div>
                </div>

                <div class="ukc-form-actions">
                    <button type="button" class="ukc-btn ukc-btn--blue" wire:click="generateReport"
                            wire:loading.attr="disabled" wire:target="generateReport,aiExample">
                        <span wire:loading.remove wire:target="generateReport"><x-ukc.icon name="sparkles" :size="18" /> Générer le brouillon</span>
                        <span wire:loading wire:target="generateReport">Génération…</span>
                    </button>
                    @if ($aiSections)
                        <button type="button" class="ukc-btn ukc-btn--ghost ukc-btn--sm" wire:click="resetReport">Effacer</button>
                    @endif
                </div>

                @if ($aiError)
                    <div class="ukc-alert ukc-alert--err" style="margin-top:14px;">
                        <span class="ico"><x-ukc.icon name="info" :size="18" /></span>
                        <div>{{ $aiError }}</div>
                    </div>
                @endif

                @if ($aiSections)
                    <div style="margin-top:18px;border-top:1px solid var(--ukc-border);padding-top:18px;">
                        <div class="ukc-alert ukc-alert--warn" style="margin-bottom:14px;">
                            <span class="ico"><x-ukc.icon name="info" :size="18" /></span>
                            <div>Brouillon généré par IA — <strong>à relire et valider</strong> avant toute diffusion officielle.</div>
                        </div>
                        <article class="ukc-rte">
                            <h3 style="font-size:1.15rem;font-weight:900;margin-bottom:12px;">{{ $aiTitle }}</h3>
                            @foreach ($aiSections as $section)
                                @if ($section['heading'])<h4 style="font-weight:800;margin:14px 0 6px;">{{ $section['heading'] }}</h4>@endif
                                <div style="white-space:pre-line;color:var(--ukc-text);line-height:1.7;">{{ $section['body'] }}</div>
                            @endforeach
                        </article>
                    </div>
                @endif
            </div>
        </div>
    @else
        <div class="ukc-empty"><h3>Aucune édition</h3><p>Sélectionnez une édition pour générer ses rapports.</p></div>
    @endif
</div>
