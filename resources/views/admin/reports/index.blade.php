<x-layouts.admin title="Rapports" subtitle="Statistiques, exports et bilans par événement">
    @php
        // Onglets visibles selon la fonction du membre du staff (capacités par événement).
        $rStaff = auth('web')->user();
        $rCan = fn ($c) => $rStaff?->canAnyEdition($c) ?? false;
        $cManage = $rCan('submissions_list') || $rCan('participants');
        $reportTabs = [
            'overview' => $cManage,
            'submissions' => $rCan('submissions_list'),
            'participants' => $rCan('participants'),
            'payments' => $rCan('payments'),
            'reviews' => $rCan('evaluate') || $rCan('assign_reviewers'),
            'exports' => $cManage,
            'ai' => $cManage,
        ];
        $firstReportTab = collect($reportTabs)->filter()->keys()->first() ?? 'payments';
        $defaultReportTab = ((session('ai_report') || session('ai_error')) && ($reportTabs['ai'] ?? false)) ? 'ai' : $firstReportTab;
    @endphp
    <div class="admin-page admin-reports" x-data="{
            tab: '{{ $defaultReportTab }}',
            aiMode: '{{ old('mode', 'describe') }}',
            aiFileName: '',
            pickFile() { const i = this.$refs.aiFile; if (i.files && i.files[0]) this.aiFileName = i.files[0].name; },
            dropFile(e) { const dt = e.dataTransfer; if (dt && dt.files && dt.files[0]) { this.$refs.aiFile.files = dt.files; this.aiFileName = dt.files[0].name; this.aiMode = 'template'; } },
            clearFile() { this.$refs.aiFile.value = ''; this.aiFileName = ''; }
         }">
        {{-- Barre : sélecteur d'événement + exports globaux --}}
        <div style="display:flex;align-items:center;justify-content:space-between;gap:16px;flex-wrap:wrap;margin-bottom:16px;">
            <div class="ukc-field" style="min-width:220px;margin:0;">
                <select onchange="window.location='{{ route('admin.reports.index') }}?event_id='+this.value">
                    <option value="">- Événement -</option>
                    @foreach ($events as $ed)
                        <option value="{{ $ed->id }}" @selected((string) $ed->id === $event_id)>{{ $ed->title ?: $ed->year }}</option>
                    @endforeach
                </select>
            </div>

            @if ($event && ($rStaff?->canAnyEdition('submissions_list') || $rStaff?->canAnyEdition('participants')))
                <div style="display:flex;gap:8px;flex-wrap:wrap;">
                    <a href="{{ route('admin.reports.xlsx', $event) }}" class="ukc-btn ukc-btn--green ukc-btn--sm">
                        <x-ukc.icon name="sheet" :size="16" /> Excel (.xlsx)
                    </a>
                    <a href="{{ route('admin.reports.pdf', $event) }}" target="_blank" class="ukc-btn ukc-btn--blue ukc-btn--sm">
                        <x-ukc.icon name="file" :size="16" /> Rapport PDF
                    </a>
                </div>
            @endif
        </div>

        @if ($summary && $event)
            {{-- Données des graphiques (lues par charts.js) --}}
            <script type="application/json" id="dashboardData">@json($chartData)</script>

            <div class="ukc-nav-docs">
                @if ($reportTabs['overview'])<button type="button" @click="tab='overview'" :class="{ active: tab==='overview' }">Vue d’ensemble</button>@endif
                @if ($reportTabs['submissions'])<button type="button" @click="tab='submissions'" :class="{ active: tab==='submissions' }">Soumissions</button>@endif
                @if ($reportTabs['participants'])<button type="button" @click="tab='participants'" :class="{ active: tab==='participants' }">Participants</button>@endif
                @if ($reportTabs['payments'])<button type="button" @click="tab='payments'" :class="{ active: tab==='payments' }">Paiements</button>@endif
                @if ($reportTabs['reviews'])<button type="button" @click="tab='reviews'" :class="{ active: tab==='reviews' }">Relectures</button>@endif
                @if ($reportTabs['exports'])<button type="button" @click="tab='exports'" :class="{ active: tab==='exports' }">Exports prêts</button>@endif
                @if ($reportTabs['ai'])<button type="button" @click="tab='ai'" :class="{ active: tab==='ai' }">Export personnalisé (IA)</button>@endif
            </div>

            @php
                $byStatus = $summary['submissionsByStatus'];
                $enRevue = ($byStatus['under_review'] ?? 0) + ($byStatus['revision'] ?? 0);
                $rejected = $byStatus['rejected'] ?? 0;
                $decided = $summary['accepted'] + $rejected;
                $tauxAccept = $decided > 0 ? (int) round($summary['accepted'] / $decided * 100) : 0;
                $rev = $report->reviewStats();
                $revTotal = $rev['done'] + $rev['pending'];
                $revRate = $revTotal > 0 ? (int) round($rev['done'] / $revTotal * 100) : 0;
            @endphp

            @if ($reportTabs['overview'])
            {{-- Vue d'ensemble --}}
            <div class="ukc-panel" :class="{ active: tab==='overview' }">
                <div class="ukc-stats">
                    <div class="ukc-stat-card tw-stat"><div class="tw-stat__icon tw-stat__icon--blue"><x-ukc.icon name="inbox" :size="20" /></div><div class="lbl">Soumissions</div><div class="val blue">{{ $summary['submissions'] }}</div><div class="hint">{{ $enRevue }} en revue - {{ $summary['accepted'] }} acceptées</div></div>
                    <div class="ukc-stat-card tw-stat"><div class="tw-stat__icon tw-stat__icon--violet"><x-ukc.icon name="users" :size="20" /></div><div class="lbl">Participants</div><div class="val">{{ $summary['participants'] }}</div><div class="hint">{{ $summary['paidCount'] }} payés</div></div>
                    <div class="ukc-stat-card tw-stat"><div class="tw-stat__icon tw-stat__icon--green"><x-ukc.icon name="card" :size="20" /></div><div class="lbl">Recettes</div><div class="val green">{{ number_format($summary['collected'], 0, ',', ' ') }} <span style="font-size:.9rem;">{{ $summary['currency'] }}</span></div><div class="hint">{{ number_format($summary['pending'], 0, ',', ' ') }} en attente</div></div>
                    <div class="ukc-stat-card tw-stat"><div class="tw-stat__icon tw-stat__icon--amber"><x-ukc.icon name="check-circle" :size="20" /></div><div class="lbl">Taux d’acceptation</div><div class="val">{{ $tauxAccept }} %</div><div class="hint">{{ $summary['accepted'] }} / {{ $decided }} décidées</div></div>
                    <div class="ukc-stat-card tw-stat"><div class="tw-stat__icon tw-stat__icon--slate"><x-ukc.icon name="mic" :size="20" /></div><div class="lbl">Relectures rendues</div><div class="val warn">{{ $revRate }} %</div><div class="hint">{{ $rev['pending'] }} en attente{{ $rev['late'] > 0 ? ' - '.$rev['late'].' en retard' : '' }}</div></div>
                    <div class="ukc-stat-card tw-stat"><div class="tw-stat__icon tw-stat__icon--green"><x-ukc.icon name="award" :size="20" /></div><div class="lbl">Attestations</div><div class="val">{{ $counts['attestations'] }}</div><div class="hint">Émises</div></div>
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

            @endif
            @if ($reportTabs['submissions'])
            {{-- Soumissions --}}
            <div class="ukc-panel" :class="{ active: tab==='submissions' }">
                <div class="ukc-card">
                    <h2>Rapport soumissions
                        <span class="card-actions">
                            <a href="{{ route('admin.reports.export', [$event, 'submissions', 'csv']) }}" class="ukc-btn ukc-btn--ghost ukc-btn--sm">CSV</a>
                            <a href="{{ route('admin.reports.export', [$event, 'submissions', 'xlsx']) }}" class="ukc-btn ukc-btn--ghost ukc-btn--sm">Excel</a>
                        </span>
                    </h2>
                    <p class="desc">Synthèse par statut, type de communication et axe.</p>
                    <div class="ukc-stats" style="margin-bottom:14px;">
                        <div class="ukc-stat-card"><div class="lbl">Reçues</div><div class="val blue">{{ $summary['submissions'] }}</div></div>
                        <div class="ukc-stat-card"><div class="lbl">En revue</div><div class="val warn">{{ $enRevue }}</div></div>
                        <div class="ukc-stat-card"><div class="lbl">Acceptées</div><div class="val green">{{ $summary['accepted'] }}</div></div>
                        <div class="ukc-stat-card"><div class="lbl">Refusées</div><div class="val">{{ $rejected }}</div></div>
                        <div class="ukc-stat-card"><div class="lbl">Soumises</div><div class="val">{{ $byStatus['submitted'] ?? 0 }}</div></div>
                    </div>
                    <div class="ukc-table-wrap">
                        <table class="ukc-table">
                            <thead><tr><th>Axe</th><th class="num">Soumissions</th><th class="num">En revue</th><th class="num">Acceptées</th><th class="num">Refusées</th><th class="num">Taux accept.</th></tr></thead>
                            <tbody>
                                @forelse ($report->submissionsByAxis() as $r)
                                    <tr>
                                        <td>{{ $r['axe'] }}</td>
                                        <td class="num">{{ $r['total'] }}</td>
                                        <td class="num">{{ $r['review'] }}</td>
                                        <td class="num">{{ $r['accepted'] }}</td>
                                        <td class="num">{{ $r['rejected'] }}</td>
                                        <td class="num">{{ $r['rate'] }} %</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="6" style="text-align:center;color:var(--ukc-muted);padding:20px;">Aucune soumission.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="ukc-card">
                    <h2>Types de communication</h2>
                    <div class="ukc-table-wrap">
                        <table class="ukc-table">
                            <thead><tr><th>Type</th><th class="num">Nombre</th><th class="num">Acceptés</th><th class="num">Part</th></tr></thead>
                            <tbody>
                                @forelse ($report->submissionsByType() as $r)
                                    <tr><td>{{ $r['type'] }}</td><td class="num">{{ $r['total'] }}</td><td class="num">{{ $r['accepted'] }}</td><td class="num">{{ $r['part'] }} %</td></tr>
                                @empty
                                    <tr><td colspan="4" style="text-align:center;color:var(--ukc-muted);padding:20px;">Aucune donnée.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            @endif
            @if ($reportTabs['participants'])
            {{-- Participants --}}
            <div class="ukc-panel" :class="{ active: tab==='participants' }">
                <div class="ukc-card">
                    <h2>Rapport participants
                        <span class="card-actions">
                            <a href="{{ route('admin.reports.export', [$event, 'participants', 'csv']) }}" class="ukc-btn ukc-btn--ghost ukc-btn--sm">CSV</a>
                            <a href="{{ route('admin.reports.export', [$event, 'participants', 'xlsx']) }}" class="ukc-btn ukc-btn--ghost ukc-btn--sm">Excel</a>
                        </span>
                    </h2>
                    <p class="desc">Inscriptions, catégories et provenance.</p>
                    <div class="ukc-stats" style="margin-bottom:14px;">
                        <div class="ukc-stat-card"><div class="lbl">Total inscrits</div><div class="val blue">{{ $summary['participants'] }}</div></div>
                        <div class="ukc-stat-card"><div class="lbl">Payés</div><div class="val green">{{ $summary['paidCount'] }}</div></div>
                        <div class="ukc-stat-card"><div class="lbl">Sans paiement</div><div class="val warn">{{ max(0, $summary['participants'] - $summary['paidCount']) }}</div></div>
                        <div class="ukc-stat-card"><div class="lbl">Pays représentés</div><div class="val">{{ count($report->participantsByCountry()) }}</div></div>
                    </div>
                    <div class="layout-2">
                        <div>
                            <h3 class="sub-h">Par catégorie</h3>
                            <div class="ukc-table-wrap">
                                <table class="ukc-table" style="min-width:0;">
                                    <thead><tr><th>Catégorie</th><th class="num">Inscrits</th><th class="num">Payés</th></tr></thead>
                                    <tbody>
                                        @forelse ($report->participantsByRole() as $r)
                                            <tr><td>{{ $r['role'] }}</td><td class="num">{{ $r['count'] }}</td><td class="num">{{ $r['paid'] }}</td></tr>
                                        @empty
                                            <tr><td colspan="3" style="text-align:center;color:var(--ukc-muted);padding:16px;">Aucun participant.</td></tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        <div>
                            <h3 class="sub-h">Par pays (top 6)</h3>
                            <div class="ukc-table-wrap">
                                <table class="ukc-table" style="min-width:0;">
                                    <thead><tr><th>Pays</th><th class="num">Participants</th><th class="num">Part</th></tr></thead>
                                    <tbody>
                                        @forelse ($report->participantsByCountry() as $r)
                                            <tr><td>{{ $r['country'] }}</td><td class="num">{{ $r['count'] }}</td><td class="num">{{ $r['part'] }} %</td></tr>
                                        @empty
                                            <tr><td colspan="3" style="text-align:center;color:var(--ukc-muted);padding:16px;">Aucune donnée.</td></tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            @endif
            @if ($reportTabs['payments'])
            {{-- Paiements --}}
            <div class="ukc-panel" :class="{ active: tab==='payments' }">
                @php($payStats = $report->paymentStats())
                <div class="ukc-card">
                    <h2>Rapport paiements
                        <span class="card-actions">
                            <a href="{{ route('admin.reports.export', [$event, 'payments', 'csv']) }}" class="ukc-btn ukc-btn--ghost ukc-btn--sm">CSV</a>
                            <a href="{{ route('admin.reports.export', [$event, 'payments', 'xlsx']) }}" class="ukc-btn ukc-btn--ghost ukc-btn--sm">Excel</a>
                        </span>
                    </h2>
                    <p class="desc">Recettes, passerelles et statut des transactions.</p>
                    <div class="ukc-stats" style="margin-bottom:14px;">
                        <div class="ukc-stat-card"><div class="lbl">Recettes totales</div><div class="val green">{{ number_format($summary['collected'], 0, ',', ' ') }}</div><div class="hint">{{ $summary['currency'] }}</div></div>
                        <div class="ukc-stat-card"><div class="lbl">Transactions OK</div><div class="val blue">{{ $payStats['ok'] }}</div></div>
                        <div class="ukc-stat-card"><div class="lbl">En attente</div><div class="val warn">{{ $payStats['pending'] }}</div></div>
                        <div class="ukc-stat-card"><div class="lbl">Échouées / annulées</div><div class="val">{{ $payStats['failed'] }}</div></div>
                    </div>
                    <div class="layout-2">
                        <div>
                            <h3 class="sub-h">Par passerelle</h3>
                            <div class="ukc-table-wrap">
                                <table class="ukc-table" style="min-width:0;">
                                    <thead><tr><th>Passerelle</th><th class="num">Nb</th><th class="num">Montant ({{ $summary['currency'] }})</th></tr></thead>
                                    <tbody>
                                        @foreach ($report->accountingRows() as $r)
                                            <tr @if($r[0]==='Total') style="font-weight:800;background:#f8fafc;" @endif>
                                                <td>{{ $r[0] }}</td><td class="num">{{ $r[1] }}</td><td class="num">{{ number_format($r[2], 0, ',', ' ') }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        <div>
                            <h3 class="sub-h">Par tarif</h3>
                            <div class="ukc-table-wrap">
                                <table class="ukc-table" style="min-width:0;">
                                    <thead><tr><th>Tarif</th><th class="num">Nb</th><th class="num">Montant</th></tr></thead>
                                    <tbody>
                                        @forelse ($report->paymentsByTarif() as $r)
                                            <tr><td>{{ number_format($r['tarif'], 0, ',', ' ') }} {{ $summary['currency'] }}</td><td class="num">{{ $r['count'] }}</td><td class="num">{{ number_format($r['total'], 0, ',', ' ') }}</td></tr>
                                        @empty
                                            <tr><td colspan="3" style="text-align:center;color:var(--ukc-muted);padding:16px;">Aucun paiement réglé.</td></tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            @endif
            @if ($reportTabs['reviews'])
            {{-- Relectures --}}
            <div class="ukc-panel" :class="{ active: tab==='reviews' }">
                <div class="ukc-card">
                    <h2>Rapport relectures</h2>
                    <p class="desc">Charge des relecteurs et délais moyens.</p>
                    <div class="ukc-stats" style="margin-bottom:14px;">
                        <div class="ukc-stat-card"><div class="lbl">Relecteurs actifs</div><div class="val blue">{{ $rev['reviewers'] }}</div></div>
                        <div class="ukc-stat-card"><div class="lbl">Rendues</div><div class="val">{{ $rev['done'] }}</div></div>
                        <div class="ukc-stat-card"><div class="lbl">En attente</div><div class="val warn">{{ $rev['pending'] }}</div></div>
                        <div class="ukc-stat-card"><div class="lbl">En retard</div><div class="val" style="{{ $rev['late'] > 0 ? 'color:#b4231c;' : '' }}">{{ $rev['late'] }}</div></div>
                        <div class="ukc-stat-card"><div class="lbl">Délai moyen</div><div class="val">{{ number_format($rev['avgDays'], 1, ',', ' ') }} j</div></div>
                    </div>
                    <div class="ukc-table-wrap">
                        <table class="ukc-table">
                            <thead><tr><th>Relecteur</th><th class="num">Assignées</th><th class="num">Rendues</th><th class="num">En attente</th><th class="num">En retard</th><th>Complétion</th></tr></thead>
                            <tbody>
                                @forelse ($report->reviewsByReviewer() as $r)
                                    <tr>
                                        <td>
                                            {{ $r['name'] }}
                                            @if ($r['declined'] > 0)<span class="ukc-pill ukc-pill--muted" style="font-size:11px;margin-left:6px;">{{ $r['declined'] }} refus</span>@endif
                                        </td>
                                        <td class="num">{{ $r['assigned'] }}</td>
                                        <td class="num">{{ $r['done'] }}</td>
                                        <td class="num">{{ $r['pending'] }}</td>
                                        <td class="num" style="{{ $r['late'] > 0 ? 'color:#b4231c;font-weight:700;' : '' }}">{{ $r['late'] }}</td>
                                        <td style="min-width:120px;">
                                            <div style="font-size:12px;font-weight:700;">{{ $r['completion'] }} %</div>
                                            <div class="progress-bar"><span style="width:{{ $r['completion'] }}%;@if($r['completion']>=100)background:var(--ukc-green);@elseif($r['completion']<80)background:#d97706;@endif"></span></div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="6" style="text-align:center;color:var(--ukc-muted);padding:20px;">Aucune relecture assignée.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            @endif
            @if ($reportTabs['exports'])
            {{-- Exports prêts --}}
            <div class="ukc-panel" :class="{ active: tab==='exports' }">
                <div class="ukc-card">
                    <h2>Exports prêts à télécharger</h2>
                    <p class="desc">Fichiers générés à la demande à partir des données réelles de l’événement. Formats CSV, Excel et PDF.</p>

                    <div class="report-list">
                        <div class="report-item">
                            <h3>Liste complète des soumissions</h3>
                            <p>Référence, titre, auteur correspondant, axe, type, statut, note moyenne.</p>
                            <div class="meta">CSV - XLSX - ~{{ $counts['submissions'] }} lignes</div>
                            <div class="dl">
                                <a href="{{ route('admin.reports.export', [$event, 'submissions', 'csv']) }}" class="ukc-btn ukc-btn--sm">CSV</a>
                                <a href="{{ route('admin.reports.export', [$event, 'submissions', 'xlsx']) }}" class="ukc-btn ukc-btn--ghost ukc-btn--sm">Excel</a>
                            </div>
                        </div>
                        <div class="report-item">
                            <h3>Liste des participants</h3>
                            <p>Identité, catégorie, institution, pays, paiement, montant, invité.</p>
                            <div class="meta">CSV - XLSX - ~{{ $counts['participants'] }} lignes</div>
                            <div class="dl">
                                <a href="{{ route('admin.reports.export', [$event, 'participants', 'csv']) }}" class="ukc-btn ukc-btn--sm">CSV</a>
                                <a href="{{ route('admin.reports.export', [$event, 'participants', 'xlsx']) }}" class="ukc-btn ukc-btn--ghost ukc-btn--sm">Excel</a>
                            </div>
                        </div>
                        <div class="report-item">
                            <h3>Journal des paiements</h3>
                            <p>Transactions FedaPay / CashPay : reçu, montant, passerelle, statut, date.</p>
                            <div class="meta">CSV - XLSX - ~{{ $counts['payments'] }} lignes</div>
                            <div class="dl">
                                <a href="{{ route('admin.reports.export', [$event, 'payments', 'csv']) }}" class="ukc-btn ukc-btn--sm">CSV</a>
                                <a href="{{ route('admin.reports.export', [$event, 'payments', 'xlsx']) }}" class="ukc-btn ukc-btn--ghost ukc-btn--sm">Excel</a>
                            </div>
                        </div>
                        <div class="report-item">
                            <h3>Bilan scientifique (PDF)</h3>
                            <p>Synthèse : participants, communications, taux d’acceptation, paiements.</p>
                            <div class="meta">PDF - {{ $event->title ?: $event->year }}</div>
                            <div class="dl">
                                <a href="{{ route('admin.reports.pdf', $event) }}" target="_blank" class="ukc-btn ukc-btn--sm">Télécharger PDF</a>
                            </div>
                        </div>
                        <div class="report-item">
                            <h3>Attestations émises</h3>
                            <p>Attestations générées avec UUID de vérification publique et statut.</p>
                            <div class="meta">CSV - ~{{ $counts['attestations'] }} lignes</div>
                            <div class="dl">
                                <a href="{{ route('admin.reports.export', [$event, 'attestations', 'csv']) }}" class="ukc-btn ukc-btn--sm">CSV</a>
                            </div>
                        </div>
                        <div class="report-item">
                            <h3>Export comptable</h3>
                            <p>Récapitulatif des recettes réglées par passerelle, avec total.</p>
                            <div class="meta">XLSX - {{ $summary['currency'] }}</div>
                            <div class="dl">
                                <a href="{{ route('admin.reports.export', [$event, 'accounting', 'xlsx']) }}" class="ukc-btn ukc-btn--sm">Excel</a>
                            </div>
                        </div>
                        <div class="report-item">
                            <h3>Rapports de séance</h3>
                            <p>Synthèses des séances rédigées par les rapporteurs : état, affluence, points saillants.</p>
                            <div class="meta">CSV - XLSX - ~{{ $counts['session_reports'] }} lignes</div>
                            <div class="dl">
                                <a href="{{ route('admin.reports.export', [$event, 'session_reports', 'csv']) }}" class="ukc-btn ukc-btn--sm">CSV</a>
                                <a href="{{ route('admin.reports.export', [$event, 'session_reports', 'xlsx']) }}" class="ukc-btn ukc-btn--ghost ukc-btn--sm">Excel</a>
                            </div>
                        </div>
                    </div>
                    <p class="ukc-hint" style="margin-top:14px;">Le classeur Excel « complet » (toutes les feuilles) reste disponible via <a href="{{ route('admin.reports.xlsx', $event) }}">Excel (.xlsx)</a> en haut de page.</p>
                </div>
            </div>

            @endif
            @if ($reportTabs['ai'])
            {{-- Export personnalisé (IA) --}}
            <div class="ukc-panel" :class="{ active: tab==='ai' }">
                <div class="ukc-card">
                    <h2><span><x-ukc.icon name="sparkles" :size="18" /> Export personnalisé assisté par l’IA</span></h2>
                    <p class="desc">
                        Décrivez ce que vous voulez obtenir <strong>ou</strong> joignez un modèle existant (PDF, Word, Excel).
                        L’IA travaille sur les <strong>données réelles</strong> du périmètre choisi et rend un
                        <strong>brouillon</strong> à la forme du format demandé - la décision et la diffusion restent à votre main.
                        Soyez précis : « liste des communications acceptées, colonnes Réf., Titre, Axe, Auteur correspondant, triée par axe ».
                    </p>

                    {{-- data-ajax : seul le bloc de résultat est rafraîchi, la description saisie
                         reste donc à l'écran si l'IA échoue. data-ai lève le voile. --}}
                    <form method="POST" action="{{ route('admin.reports.generate') }}" enctype="multipart/form-data" x-ref="aiForm"
                          data-ajax data-ajax-target="ai-report" data-ajax-msg="Rapport généré."
                          data-ai data-ai-title="Rédaction du rapport…"
                          data-ai-note="Jusqu’à deux minutes pour une longue liste. Le résultat est conservé dans l’historique."
                          data-ai-steps="Lecture de vos instructions…|Collecte des données de l’événement…|Rédaction du brouillon…|Mise en forme du document…">
                        @csrf
                        <input type="hidden" name="event_id" value="{{ $event->id }}">
                        <input type="hidden" name="mode" :value="aiMode">

                        <div class="ai-mode-tabs">
                            <button type="button" :class="{ active: aiMode==='describe' }" @click="aiMode='describe'">Décrire le rapport</button>
                            <button type="button" :class="{ active: aiMode==='template' }" @click="aiMode='template'">Partir d’un modèle</button>
                        </div>

                        {{-- Mode description --}}
                        <div x-show="aiMode==='describe'">
                            <div class="ukc-form-row">
                                <label for="aiBrief">Que voulez-vous comme rapport ?</label>
                                <textarea id="aiBrief" name="aiBrief" rows="6"
                                          placeholder="Ex. : Tableau des participants payés par pays et catégorie, colonnes Nom, Institution, Pays, Catégorie, Montant, Date. Inclure un résumé en tête.">{{ old('aiBrief') }}</textarea>
                                @error('aiBrief') <span class="error">{{ $message }}</span> @enderror
                                <p class="ukc-hint">Soyez précis sur les colonnes, le regroupement, les filtres et le format de sortie.</p>
                            </div>
                        </div>

                        {{-- Mode modèle --}}
                        <div x-show="aiMode==='template'" x-cloak>
                            <div class="ukc-form-row">
                                <label>Modèle de rapport (exemple)</label>
                                <div class="ai-dropzone" @click="$refs.aiFile.click()"
                                     @dragover.prevent="$el.classList.add('dragover')"
                                     @dragleave.prevent="$el.classList.remove('dragover')"
                                     @drop.prevent="$el.classList.remove('dragover'); dropFile($event)">
                                    <div class="icon">📄</div>
                                    <strong>Glissez un fichier ici ou cliquez pour parcourir</strong>
                                    <p>PDF, Word (.docx), Excel (.xlsx) - max. 10 Mo</p>
                                    <input type="file" x-ref="aiFile" name="aiExample" accept=".pdf,.doc,.docx,.xls,.xlsx,.csv" style="display:none;" @change="pickFile()">
                                </div>
                                <div class="ai-file-chip" x-show="aiFileName" x-cloak style="display:none;">
                                    <span x-text="aiFileName"></span>
                                    <button type="button" @click="clearFile()" title="Retirer">&times;</button>
                                </div>
                                @error('aiExample') <span class="error">{{ $message }}</span> @enderror
                            </div>
                            <div class="ukc-form-row">
                                <label for="aiNotes">Instructions complémentaires (optionnel)</label>
                                <textarea id="aiNotes" name="aiNotes" rows="4"
                                          placeholder="Ex. : Reprendre la structure et les en-têtes du modèle, remplacer les données par celles de l’événement.">{{ old('aiNotes') }}</textarea>
                            </div>
                        </div>

                        <div class="ai-form-grid">
                            <div class="ukc-form-row">
                                <label for="aiFormat">Format de sortie</label>
                                <select id="aiFormat" name="format">
                                    <option value="xlsx" @selected(old('format')==='xlsx')>Excel (.xlsx)</option>
                                    <option value="csv" @selected(old('format')==='csv')>CSV</option>
                                    <option value="pdf" @selected(old('format')==='pdf')>PDF</option>
                                    <option value="docx" @selected(old('format')==='docx')>Word (.docx)</option>
                                </select>
                                <span class="ukc-hint">Excel et CSV produisent un <strong>tableau</strong> (une ligne par enregistrement) ; PDF et Word, un <strong>texte rédigé</strong>.</span>
                            </div>
                            <div class="ukc-form-row">
                                <label for="aiScope">Périmètre des données</label>
                                <select id="aiScope" name="scope">
                                    <option value="event" @selected(old('scope')==='event')>Événement (participants + communications + paiements + rapports de séance)</option>
                                    <option value="subs" @selected(old('scope')==='subs')>Soumissions uniquement</option>
                                    <option value="part" @selected(old('scope')==='part')>Participants uniquement</option>
                                    <option value="pay" @selected(old('scope')==='pay')>Paiements uniquement</option>
                                    <option value="reports" @selected(old('scope')==='reports')>Rapports de séance uniquement</option>
                                </select>
                                <span class="ukc-hint">Les lignes de ce périmètre sont <strong>transmises au fournisseur d’IA</strong> choisi, pour qu’il réponde sur des données réelles. Choisissez le plus étroit qui suffise.</span>
                            </div>
                        </div>

                        <div class="ukc-form-row">
                            <label for="aiProvider">Fournisseur IA</label>
                            <select id="aiProvider" name="aiProvider">
                                <option value="default">Par défaut (réglages globaux)</option>
                                <option value="claude" @selected(old('aiProvider')==='claude')>Claude</option>
                                <option value="openai" @selected(old('aiProvider')==='openai')>OpenAI</option>
                                <option value="gemini" @selected(old('aiProvider')==='gemini')>Gemini</option>
                                <option value="grok" @selected(old('aiProvider')==='grok')>Grok</option>
                            </select>
                        </div>

                        <div style="display:flex;gap:8px;flex-wrap:wrap;margin-top:4px;">
                            <button type="submit" class="ukc-btn ukc-btn--blue"><x-ukc.icon name="sparkles" :size="16" /> Générer avec l’IA</button>
                            <button type="reset" class="ukc-btn ukc-btn--ghost" @click="clearFile(); aiMode='describe'">Réinitialiser</button>
                        </div>
                    </form>
                </div>

                {{-- Bloc rafraîchi après génération : erreur, rapport produit et
                     historique, les trois changeant d'un coup. --}}
                <div data-ajax-root="ai-report">
                    @if (session('ai_error'))
                        <div class="ukc-alert ukc-alert--err" style="margin-top:14px;">
                            <span class="ico"><x-ukc.icon name="info" :size="18" /></span>
                            <div>{{ session('ai_error') }}</div>
                        </div>
                    @endif

                    @if (session('ai_report'))
                        @php($aiReport = session('ai_report'))
                        <div class="ai-result">
                            <h3>Rapport généré</h3>
                            <p style="font-size:13px;color:#166534;margin-bottom:10px;">
                                Brouillon produit selon vos instructions et les données réelles de l’événement -
                                à relire avant diffusion. Format : <strong>{{ strtoupper($aiReport['format'] ?? 'xlsx') }}</strong>.
                            </p>
                            <div class="actions" style="display:flex;gap:8px;flex-wrap:wrap;">
                                <a href="{{ route('admin.reports.ai-download', [$event, $aiReport['id']]) }}" @if(in_array(($aiReport['format'] ?? ''), ['pdf'], true)) target="_blank" @endif class="ukc-btn ukc-btn--sm">Télécharger</a>
                            </div>
                            <div style="margin-top:14px;border-top:1px solid #bbf7d0;padding-top:12px;">
                                <h4 style="font-size:1.1rem;font-weight:900;margin-bottom:10px;color:var(--ukc-text);">{{ $aiReport['title'] }}</h4>

                                {{-- Aperçu du tableau demandé : on le voit AVANT de
                                     télécharger, limité aux 30 premières lignes. --}}
                                @if (! empty($aiReport['table']))
                                    @php($aiTable = $aiReport['table'])
                                    <div class="ukc-table-wrap" style="margin-bottom:12px;">
                                        <table class="ukc-table" style="font-size:12.5px;">
                                            <thead>
                                                <tr>@foreach ($aiTable['columns'] as $col)<th>{{ $col }}</th>@endforeach</tr>
                                            </thead>
                                            <tbody>
                                                @foreach (array_slice($aiTable['rows'], 0, 30) as $row)
                                                    <tr>@foreach ($row as $cell)<td>{{ $cell }}</td>@endforeach</tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                    <p style="font-size:12px;color:#166534;margin-bottom:10px;">
                                        {{-- ⚠️ Ternaire et non @if : une directive collée à un mot
                                             (« ci-dessus@endif ») n'est pas compilée, et le @if
                                             resté ouvert fait tomber toute la page. --}}
                                        {{ count($aiTable['rows']) }} ligne(s){{ count($aiTable['rows']) > 30 ? ', dont les 30 premières ci-dessus' : '' }}.
                                        Le fichier téléchargé les contient toutes.
                                    </p>
                                @endif

                                @foreach ($aiReport['sections'] as $section)
                                    @if ($section['heading'])<h5 style="font-weight:800;margin:12px 0 6px;color:var(--ukc-text);">{{ $section['heading'] }}</h5>@endif
                                    <div style="white-space:pre-line;color:var(--ukc-text);line-height:1.7;">{{ $section['body'] }}</div>
                                @endforeach
                            </div>
                        </div>
                    @endif

                {{-- Historique des exports IA --}}
                <div class="ukc-card">
                    <h2>Historique des exports IA</h2>
                    <p class="desc">Dernières générations pour cet événement (stockées côté serveur).</p>
                    @forelse ($aiHistory as $h)
                        <div class="ai-history-item">
                            <div>
                                <div class="title">{{ $h['title'] }}</div>
                                <div class="meta">{{ strtoupper($h['format'] ?? 'xlsx') }} - {{ $h['created_at'] ?? '' }} -
                                    {{ ['describe' => 'Description libre', 'template' => 'À partir d’un modèle'][$h['mode'] ?? 'describe'] ?? 'Description libre' }}</div>
                            </div>
                            <a href="{{ route('admin.reports.ai-download', [$event, $h['id']]) }}" @if(($h['format'] ?? '')==='pdf') target="_blank" @endif class="ukc-btn ukc-btn--ghost ukc-btn--sm">Télécharger</a>
                        </div>
                    @empty
                        <p class="ukc-hint">Aucune génération pour l’instant. Décrivez un rapport ci-dessus pour commencer.</p>
                    @endforelse
                </div>
                </div>
            </div>
            @endif
        @else
            <div class="ukc-empty"><h3>Aucun événement</h3><p>Sélectionnez un événement pour générer ses rapports.</p></div>
        @endif
    </div>
</x-layouts.admin>
