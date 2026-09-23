<x-layouts.app :title="'Rapport projet - ' . ($edition->title ?: $edition->year)">
    <div style="max-width:1000px;margin:0 auto;padding:28px 20px;">
        <div style="display:flex;justify-content:space-between;align-items:center;gap:12px;margin-bottom:16px;flex-wrap:wrap;" class="no-print">
            <a href="{{ route('admin.project.index', ['edition_id' => $edition->id]) }}" class="ukc-btn ukc-btn--ghost ukc-btn--sm">← Projet</a>
            <div style="display:flex;gap:8px;">
                <a href="{{ route('admin.project.export', ['edition_id' => $edition->id]) }}" class="ukc-btn ukc-btn--green ukc-btn--sm"><x-ukc.icon name="sheet" :size="16" /> Excel (.xlsx)</a>
                <button onclick="window.print()" class="ukc-btn ukc-btn--blue ukc-btn--sm"><x-ukc.icon name="download" :size="16" /> Imprimer / PDF</button>
            </div>
        </div>

        <div class="ukc-card" style="padding:32px;">
            <div style="height:5px;display:flex;margin:-32px -32px 24px;">
                <span style="flex:1;background:var(--ukc-blue);"></span>
                <span style="flex:1;background:var(--ukc-gold);"></span>
                <span style="flex:1;background:var(--ukc-green);"></span>
            </div>

            <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:16px;flex-wrap:wrap;">
                <div>
                    <div style="font-weight:900;color:var(--ukc-blue);font-size:1.2rem;">{{ $edition->title ?: $edition->year }}</div>
                    <div style="font-size:.85rem;color:var(--ukc-muted);">Université de Kara - Rapport de projet</div>
                </div>
                <div style="text-align:right;font-size:.8rem;color:var(--ukc-muted);">Généré le {{ now()->format('d/m/Y H:i') }}</div>
            </div>

            <hr style="border:none;border-top:1px solid var(--ukc-border);margin:22px 0;">

            {{-- Synthèse --}}
            <h2 style="font-size:1rem;color:var(--ukc-blue);margin:0 0 12px;">Synthèse</h2>
            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(150px,1fr));gap:12px;margin-bottom:8px;">
                <div class="rep-kpi"><div class="v">{{ $progress }}%</div><div class="l">Avancement</div></div>
                <div class="rep-kpi"><div class="v">{{ $total - $done }}</div><div class="l">Tâches ouvertes</div></div>
                <div class="rep-kpi"><div class="v">{{ $done }}/{{ $total }}</div><div class="l">Terminées</div></div>
                <div class="rep-kpi"><div class="v">{{ $overdue->count() }}</div><div class="l">En retard</div></div>
                <div class="rep-kpi"><div class="v">{{ $msDone }}/{{ $milestones->count() }}</div><div class="l">Jalons atteints</div></div>
            </div>
            <div style="height:12px;border-radius:999px;background:#eef2f7;overflow:hidden;margin:6px 0 22px;">
                <div style="height:100%;background:linear-gradient(90deg,#223ea4,#3b82f6);width:{{ $progress }}%;"></div>
            </div>

            {{-- Répartition par statut --}}
            <h2 style="font-size:1rem;color:var(--ukc-blue);margin:0 0 10px;">Tâches par statut</h2>
            <table class="rep-table">
                <thead><tr><th>Statut</th><th style="text-align:right;">Nombre</th></tr></thead>
                <tbody>
                    @foreach ($byStatus as $row)
                        <tr><td>{{ $row['label'] }}</td><td style="text-align:right;">{{ $row['count'] }}</td></tr>
                    @endforeach
                </tbody>
            </table>

            {{-- Répartition par phase --}}
            @if ($byPhase->isNotEmpty())
                <h2 style="font-size:1rem;color:var(--ukc-blue);margin:22px 0 10px;">Avancement par phase</h2>
                <table class="rep-table">
                    <thead><tr><th>Phase</th><th style="text-align:right;">Terminées / total</th></tr></thead>
                    <tbody>
                        @foreach ($byPhase as $phase => $d)
                            <tr><td>{{ $phase }}</td><td style="text-align:right;">{{ $d['done'] }} / {{ $d['total'] }}</td></tr>
                        @endforeach
                    </tbody>
                </table>
            @endif

            {{-- Tâches en retard --}}
            @if ($overdue->isNotEmpty())
                <h2 style="font-size:1rem;color:#dc2626;margin:22px 0 10px;">Tâches en retard ({{ $overdue->count() }})</h2>
                <table class="rep-table">
                    <thead><tr><th>Tâche</th><th>Responsable</th><th>Échéance</th></tr></thead>
                    <tbody>
                        @foreach ($overdue as $t)
                            <tr><td>{{ $t->title }}</td><td>{{ $t->assignee?->name ?: '-' }}</td><td>{{ $t->due_date?->format('d/m/Y') }}</td></tr>
                        @endforeach
                    </tbody>
                </table>
            @endif

            {{-- Jalons --}}
            <h2 style="font-size:1rem;color:var(--ukc-blue);margin:22px 0 10px;">Jalons</h2>
            <table class="rep-table">
                <thead><tr><th>Jalon</th><th>Échéance</th><th style="text-align:right;">État</th></tr></thead>
                <tbody>
                    @forelse ($milestones as $m)
                        <tr>
                            <td>{{ $m->title }}</td>
                            <td>{{ $m->due_date?->format('d/m/Y') ?: '-' }}</td>
                            <td style="text-align:right;">{{ $m->isDone() ? 'Atteint' : ($m->isOverdue() ? 'En retard' : 'À venir') }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="3" style="color:var(--ukc-muted);">Aucun jalon.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <style>
        .rep-kpi { border:1px solid var(--ukc-border); border-radius:10px; padding:12px 14px; }
        .rep-kpi .v { font-size:1.4rem; font-weight:800; color:var(--ukc-blue); }
        .rep-kpi .l { font-size:.78rem; color:var(--ukc-muted); text-transform:uppercase; letter-spacing:.03em; }
        .rep-table { width:100%; border-collapse:collapse; font-size:.9rem; }
        .rep-table th, .rep-table td { border-bottom:1px solid var(--ukc-border); padding:7px 8px; text-align:left; }
        .rep-table th { font-size:.75rem; text-transform:uppercase; letter-spacing:.03em; color:var(--ukc-muted); }
        @media print { .no-print { display:none !important; } body { background:#fff; } }
    </style>
</x-layouts.app>
