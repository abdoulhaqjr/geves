<div class="admin-dash">
    @php
        $fmtRevenue = function (float $r) {
            if ($r >= 1_000_000) return number_format($r / 1_000_000, 1, ',', ' ').' M';
            if ($r >= 1000) return round($r / 1000).' k';
            return number_format($r, 0, ',', ' ');
        };
        $statusLabels = \App\Models\Submission::statuses();
        $statusPills = \App\Models\Submission::statusPills();
    @endphp

    {{-- Bannière édition active --}}
    @if ($edition)
        <div class="edition-banner">
            <div class="body">
                <h2>{{ $edition->title ?: $edition->colloque?->name }}
                    @if ($edition->status === 'open')<span class="ukc-pill ukc-pill--open">Active</span>@endif
                </h2>
                @if ($edition->theme)<p>{{ $edition->theme }}</p>@endif
                <div class="meta">
                    @if ($edition->date_start){{ $edition->date_start->translatedFormat('d M') }} – {{ $edition->date_end?->translatedFormat('d M Y') }} · @endif
                    {{ $edition->location ?: 'Lieu à préciser' }}
                    @if ($edition->submission_open) · Soumissions ouvertes{{ '' }}@if ($edition->submission_deadline) jusqu’au {{ $edition->submission_deadline->translatedFormat('d/m') }}@endif @endif
                </div>
            </div>
            <div class="actions">
                <a class="ukc-btn ukc-btn--solid ukc-btn--sm" href="{{ route('admin.submissions.index') }}" wire:navigate>Soumissions</a>
                <a class="ukc-btn ukc-btn--sm" href="{{ route('admin.editions.edit', $edition) }}" wire:navigate>Paramètres</a>
                <a class="ukc-btn ukc-btn--sm" href="{{ route('admin.editions.index') }}" wire:navigate>Toutes les éditions</a>
            </div>
        </div>
    @endif

    {{-- Indicateurs --}}
    <div class="ukc-stats">
        <div class="ukc-stat-card"><div class="lbl">Soumissions</div><div class="val blue">{{ $stats['submissions'] }}</div><div class="hint">édition active</div></div>
        <div class="ukc-stat-card"><div class="lbl">En revue</div><div class="val warn">{{ $stats['underReview'] }}</div><div class="hint">avis en cours</div></div>
        <div class="ukc-stat-card"><div class="lbl">Acceptées</div><div class="val green">{{ $stats['accepted'] }}</div><div class="hint">{{ $stats['oral'] }} orales · {{ $stats['poster'] }} posters</div></div>
        <div class="ukc-stat-card"><div class="lbl">Participants payés</div><div class="val blue">{{ $stats['paidParticipants'] }}</div><div class="hint">inscriptions réglées</div></div>
        <div class="ukc-stat-card"><div class="lbl">Recettes (FCFA)</div><div class="val">{{ $fmtRevenue($stats['revenue']) }}</div><div class="hint">paiements validés</div></div>
        <div class="ukc-stat-card"><div class="lbl">E-mails (7 j.)</div><div class="val">{{ $stats['emails7'] }}</div><div class="hint">envoyés récemment</div></div>
    </div>

    <div class="layout">
        {{-- Colonne principale --}}
        <div>
            <div class="ukc-card">
                <h2>Pipeline des soumissions <a href="{{ route('admin.submissions.index') }}" wire:navigate style="font-size:12.5px;font-weight:700;">Voir tout →</a></h2>
                <div class="desc">Répartition par statut — édition active</div>
                <div class="pipeline-grid">
                    <div>
                        <div class="status-row"><span>Soumises (nouvelles)</span><span class="count">{{ $pipeline['submitted'] }} <span class="ukc-pill ukc-pill--info">Nouvelles</span></span></div>
                        <div class="status-row"><span>En revue</span><span class="count">{{ $pipeline['under_review'] }} <span class="ukc-pill ukc-pill--review">Revue</span></span></div>
                        <div class="status-row"><span>Corrections demandées</span><span class="count">{{ $pipeline['revision'] }} <span class="ukc-pill ukc-pill--warn">Corrections</span></span></div>
                        <div class="status-row"><span>Acceptées</span><span class="count">{{ $pipeline['accepted'] }} <span class="ukc-pill ukc-pill--ok">OK</span></span></div>
                        <div class="status-row"><span>Refusées</span><span class="count">{{ $pipeline['rejected'] }} <span class="ukc-pill ukc-pill--err">Refus</span></span></div>
                    </div>
                    <div class="chart-wrap chart-wrap--donut"><canvas id="chartStatus"></canvas></div>
                </div>
                <div style="margin-top:16px;">
                    <div class="desc" style="margin-bottom:8px;">Volume de soumissions (mois)</div>
                    <div class="chart-wrap" style="height:150px;"><canvas id="chartSubsMonth"></canvas></div>
                </div>
            </div>

            <div class="ukc-card">
                <h2>Évolution des paiements</h2>
                <div class="desc">Recettes cumulées et paiements validés (FCFA)</div>
                <div class="chart-wrap" style="height:160px;"><canvas id="chartPayments"></canvas></div>
            </div>

            <div class="ukc-card">
                <h2>Dernières soumissions <a href="{{ route('admin.submissions.index') }}" wire:navigate style="font-size:12.5px;font-weight:700;">Liste →</a></h2>
                <div class="ukc-table-wrap">
                    <table class="ukc-table">
                        <thead><tr><th>Réf.</th><th>Titre</th><th>Statut</th><th>Date</th></tr></thead>
                        <tbody>
                            @forelse ($recent as $s)
                                <tr>
                                    <td><code>{{ $s->ref_code }}</code></td>
                                    <td><a href="{{ route('admin.submissions.show', $s) }}" wire:navigate>{{ \Illuminate\Support\Str::limit($s->title, 46) }}</a></td>
                                    <td><span class="ukc-pill ukc-pill--{{ $statusPills[$s->status] ?? 'muted' }}">{{ $statusLabels[$s->status] ?? $s->status }}</span></td>
                                    <td>{{ $s->created_at?->format('d/m') }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="4" style="color:var(--ukc-muted);">Aucune soumission.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- Colonne latérale --}}
        <div>
            <div class="ukc-card">
                <h2>Accès rapides</h2>
                <div class="quick-grid">
                    <a class="quick-link" href="{{ route('admin.submissions.index') }}" wire:navigate>Soumissions<span>Liste &amp; relecture</span></a>
                    <a class="quick-link" href="{{ route('admin.participants.index') }}" wire:navigate>Participants<span>Comptes &amp; invitations</span></a>
                    <a class="quick-link" href="{{ route('admin.payments.index') }}" wire:navigate>Paiements<span>FedaPay / Mobile Money</span></a>
                    <a class="quick-link" href="{{ route('admin.documents.attestations') }}" wire:navigate>Documents<span>Attestations, badges</span></a>
                    <a class="quick-link" href="{{ route('admin.emails.index') }}" wire:navigate>E-mails<span>Modèles &amp; rappels</span></a>
                    <a class="quick-link" href="{{ route('admin.program.slots') }}" wire:navigate>Créneaux<span>Programme &amp; sessions</span></a>
                </div>
            </div>

            <div class="ukc-card">
                <h2>À traiter</h2>
                <div class="desc">Actions prioritaires pour l’équipe</div>
                @forelse ($todos as $t)
                    <div class="todo-item">
                        <input type="checkbox">
                        <div class="txt">{{ $t['txt'] }}</div>
                        <span class="prio"><span class="ukc-pill ukc-pill--{{ $t['pill'] }}">{{ $t['lbl'] }}</span></span>
                    </div>
                @empty
                    <div style="font-size:13.5px;color:var(--ukc-muted);">Rien d’urgent. Tout est à jour 👌</div>
                @endforelse
            </div>

            <div class="ukc-card">
                <h2>Communications par axe</h2>
                <div class="chart-wrap" style="height:180px;"><canvas id="chartAxes"></canvas></div>
            </div>

            <div class="ukc-card">
                <h2>Activité récente</h2>
                <ul class="activity-list">
                    @forelse ($activity as $a)
                        <li>
                            <span class="dot {{ $a['dot'] }}"></span>
                            <div>{{ $a['txt'] }}<div class="when">{{ $a['when'] }}</div></div>
                        </li>
                    @empty
                        <li><div style="color:var(--ukc-muted);">Aucune activité récente.</div></li>
                    @endforelse
                </ul>
            </div>
        </div>
    </div>

    <script type="application/json" id="dashboardData">@json($dashboardData)</script>
</div>
