<div>
    <div style="display:flex;align-items:center;justify-content:space-between;gap:16px;flex-wrap:wrap;margin-bottom:18px;">
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
                <a href="{{ route('admin.reports.xlsx', $edition) }}" class="ukc-btn ukc-btn--green">
                    <x-ukc.icon name="sheet" :size="18" /> Exporter Excel (.xlsx)
                </a>
                <a href="{{ route('admin.reports.pdf', $edition) }}" target="_blank" class="ukc-btn ukc-btn--blue">
                    <x-ukc.icon name="file" :size="18" /> Rapport PDF
                </a>
            </div>
        @endif
    </div>

    @if ($summary)
        <div class="ukc-grid-2" style="grid-template-columns:repeat(4,1fr);gap:12px;margin-bottom:14px;">
            <div class="ukc-stat-card"><div class="num">{{ $summary['participants'] }}</div><div class="lbl">Participants</div></div>
            <div class="ukc-stat-card"><div class="num">{{ $summary['submissions'] }}</div><div class="lbl">Communications</div></div>
            <div class="ukc-stat-card"><div class="num">{{ $summary['accepted'] }}</div><div class="lbl">Acceptées</div></div>
            <div class="ukc-stat-card"><div class="num">{{ $summary['paidCount'] }}</div><div class="lbl">Ont payé</div></div>
        </div>
        <div class="ukc-grid-2" style="grid-template-columns:1fr 1fr;gap:12px;margin-bottom:18px;">
            <div class="ukc-stat-card"><div class="num" style="color:var(--ukc-green);">{{ number_format($summary['collected'], 0, ',', ' ') }} {{ $summary['currency'] }}</div><div class="lbl">Encaissé</div></div>
            <div class="ukc-stat-card"><div class="num">{{ number_format($summary['pending'], 0, ',', ' ') }} {{ $summary['currency'] }}</div><div class="lbl">En attente</div></div>
        </div>

        <div class="ukc-card">
            <h2 style="margin:0 0 12px;font-size:1rem;color:var(--ukc-blue);">Communications par statut</h2>
            <div style="display:flex;gap:8px;flex-wrap:wrap;">
                @foreach ($statuses as $key => $label)
                    <span class="ukc-pill ukc-pill--{{ \App\Models\Submission::statusPills()[$key] ?? 'muted' }}">
                        {{ $label }} : {{ $summary['submissionsByStatus'][$key] ?? 0 }}
                    </span>
                @endforeach
            </div>
            <p class="ukc-muted" style="font-size:.85rem;margin:16px 0 0;">
                Le classeur Excel contient les feuilles <strong>Synthèse</strong>, <strong>Participants</strong>,
                <strong>Communications</strong> et <strong>Paiements</strong>. Le rapport PDF est imprimable (Imprimer → Enregistrer en PDF).
            </p>
        </div>
    @else
        <div class="ukc-empty"><h3>Aucune édition</h3><p>Sélectionnez une édition pour générer ses rapports.</p></div>
    @endif
</div>
