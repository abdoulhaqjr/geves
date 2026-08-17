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

        {{-- Export personnalisé (IA) --}}
        <div class="ukc-card" style="margin-top:16px;">
            <h2 style="margin:0 0 6px;font-size:1rem;color:var(--ukc-blue);">
                <span><x-ukc.icon name="sparkles" :size="18" /> Export personnalisé (IA)</span>
            </h2>
            <p class="ukc-muted" style="font-size:.85rem;margin:0 0 14px;">
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
    @else
        <div class="ukc-empty"><h3>Aucune édition</h3><p>Sélectionnez une édition pour générer ses rapports.</p></div>
    @endif
</div>
