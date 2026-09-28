<x-layouts.admin title="Secrétariat" subtitle="Rapports de séance à valider · suivi administratif">
    @php($me = auth('web')->user())
    <x-slot:actions>
        <a href="{{ route('admin.secretariat.reports') }}" class="ukc-btn ukc-btn--outline ukc-btn--sm"><x-ukc.icon name="sheet" :size="15" /> Rapports</a>
        @if ($me?->canAnyEdition('participants'))
            <a href="{{ route('admin.participants.index') }}" class="ukc-btn ukc-btn--outline ukc-btn--sm"><x-ukc.icon name="users" :size="15" /> Participants</a>
        @endif
        @if ($me?->canAnyEdition('documents'))
            <a href="{{ route('admin.documents.attestations') }}" class="ukc-btn ukc-btn--outline ukc-btn--sm"><x-ukc.icon name="diploma" :size="15" /> Documents</a>
        @endif
        @if ($me?->canAnyEdition('emails'))
            <a href="{{ route('admin.emails.index') }}" class="ukc-btn ukc-btn--outline ukc-btn--sm"><x-ukc.icon name="mail" :size="15" /> E-mails</a>
        @endif
    </x-slot:actions>

    <div class="admin-page" x-data="{ tab: '{{ (session('sec_doc') || session('sec_error')) ? 'assist' : 'reports' }}' }">

        <div class="ukc-nav-docs">
            <button type="button" @click="tab='reports'" :class="{ active: tab==='reports' }"><x-ukc.icon name="sheet" :size="15" /> Rapports de séance</button>
            <button type="button" @click="tab='assist'" :class="{ active: tab==='assist' }"><x-ukc.icon name="sparkles" :size="15" /> Secrétariat assisté</button>
        </div>

        <div x-show="tab==='reports'">
        {{-- Indicateurs --}}
        <div class="ukc-stats" style="margin-bottom:18px;">
            <div class="ukc-stat-card tw-stat"><div class="tw-stat__icon tw-stat__icon--amber"><x-ukc.icon name="check-circle" :size="20" /></div><div class="lbl">Rapports à valider</div><div class="val">{{ $stats['to_validate'] }}</div></div>
            <div class="ukc-stat-card tw-stat"><div class="tw-stat__icon tw-stat__icon--slate"><x-ukc.icon name="pen" :size="20" /></div><div class="lbl">En attente de rédaction</div><div class="val">{{ $stats['awaiting'] }}</div></div>
            <div class="ukc-stat-card tw-stat"><div class="tw-stat__icon tw-stat__icon--green"><x-ukc.icon name="check" :size="20" /></div><div class="lbl">Rapports validés</div><div class="val green">{{ $stats['validated'] }}</div></div>
        </div>

        @if ($editions->isEmpty())
            <div class="ukc-empty"><h3>Aucune édition accessible</h3><p>Vous n'êtes rattaché à aucune édition pour le moment.</p></div>
        @else
            {{-- ===== Rapports à valider ===== --}}
            <div class="ukc-card" style="padding:0;margin-bottom:18px;">
                <div style="padding:16px 18px 0;">
                    <h3 style="margin:0;">Rapports à valider</h3>
                    <p style="font-size:13px;color:var(--ukc-muted);margin:4px 0 0;">Rapports soumis par les rapporteurs. La validation verrouille le rapport (le rapporteur ne peut plus le modifier).</p>
                </div>
                @if ($toValidate->isNotEmpty())
                    <div class="ukc-table-wrap" style="margin-top:12px;">
                        <table class="ukc-table">
                            <thead><tr><th>Séance</th><th>Rapporteur</th><th>Soumis le</th><th style="text-align:right;">Action</th></tr></thead>
                            <tbody>
                                @foreach ($toValidate as $s)
                                    <tr>
                                        <td class="title">
                                            {{ $s->label }}
                                            <div style="font-size:12px;color:var(--ukc-muted);">{{ $s->edition?->title ?: $s->edition?->year }}@if ($s->room) · {{ $s->room }}@endif</div>
                                        </td>
                                        <td>{{ $s->rapporteur_name ?: $s->rapporteur_email }}</td>
                                        <td>{{ $s->report?->submitted_at?->translatedFormat('d/m/Y H:i') ?: '-' }}</td>
                                        <td style="text-align:right;">
                                            <div style="display:inline-flex;gap:8px;flex-wrap:wrap;justify-content:flex-end;">
                                                <a href="{{ route('admin.secretariat.reports') }}?edition_id={{ $s->edition_id }}" class="ukc-btn ukc-btn--ghost ukc-btn--sm"><x-ukc.icon name="eye" :size="15" /> Lire / améliorer</a>
                                                <form method="POST" action="{{ route('admin.program.reports.save', $s) }}" onsubmit="return confirm('Valider ce rapport ? Le rapporteur ne pourra plus le modifier.')">
                                                    @csrf
                                                    <button type="submit" class="ukc-btn ukc-btn--green ukc-btn--sm"><x-ukc.icon name="check" :size="15" /> Valider</button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="ukc-empty" style="border:none;"><p style="margin:12px 0 18px;">Aucun rapport en attente de validation. 🎉</p></div>
                @endif
            </div>

            <div class="ukc-grid-2" style="align-items:start;">
                {{-- ===== En attente de rédaction ===== --}}
                <div class="ukc-card">
                    <h3>En attente de rédaction</h3>
                    <p style="font-size:13px;color:var(--ukc-muted);margin:4px 0 12px;">Séances commencées dont le rapport n'est pas encore soumis.</p>
                    @forelse ($awaiting as $s)
                        <div style="padding:10px 0;border-top:1px solid var(--ukc-border);display:flex;justify-content:space-between;gap:10px;flex-wrap:wrap;align-items:center;">
                            <div>
                                <div style="font-weight:600;font-size:.92rem;">{{ $s->label }}</div>
                                <div style="font-size:12.5px;color:var(--ukc-muted);">
                                    {{ $s->starts_at?->translatedFormat('d/m H:i') }} · {{ $s->rapporteur_name ?: $s->rapporteur_email }}
                                    @if ($s->report && $s->report->status === 'draft') · <span class="ukc-pill ukc-pill--warn">brouillon</span>@endif
                                </div>
                            </div>
                            @if (filled($s->rapporteur_email))
                                <a href="mailto:{{ $s->rapporteur_email }}?subject={{ rawurlencode('Rapport de séance : '.$s->label) }}" class="ukc-btn ukc-btn--outline ukc-btn--sm"><x-ukc.icon name="mail" :size="15" /> Relancer</a>
                            @endif
                        </div>
                    @empty
                        <p style="font-size:.9rem;color:var(--ukc-muted);">Rien à relancer.</p>
                    @endforelse
                </div>

                {{-- ===== Récemment validés ===== --}}
                <div class="ukc-card">
                    <h3>Récemment validés</h3>
                    @forelse ($validated as $s)
                        <div style="padding:10px 0;border-top:1px solid var(--ukc-border);">
                            <div style="font-weight:600;font-size:.92rem;">{{ $s->label }}</div>
                            <div style="font-size:12.5px;color:var(--ukc-muted);">Validé le {{ $s->report?->validated_at?->translatedFormat('d/m/Y H:i') }}</div>
                        </div>
                    @empty
                        <p style="font-size:.9rem;color:var(--ukc-muted);">Aucun rapport validé pour l'instant.</p>
                    @endforelse
                </div>
            </div>

        @endif
        </div>{{-- /onglet Rapports de séance --}}

        {{-- ===== Secrétariat assisté ===== --}}
        <div x-show="tab==='assist'" x-cloak>
            @if ($docEditions->isEmpty())
                <div class="ukc-empty"><h3>Aucune édition</h3><p>Vous n'avez accès aux documents d'aucune édition.</p></div>
            @else
                <div class="ukc-grid-2" style="align-items:start;">
                    {{-- Générateur --}}
                    <div class="ukc-card"
                         x-data="{
                            presets: @js($presets),
                            preset: '', prompt: '', format: 'pdf', dataset: 'none',
                            pick(k){ const p=this.presets[k]; if(!p) return; this.preset=k; this.prompt=p.prompt; this.format=p.format; this.dataset=p.dataset; }
                         }">
                        <h3><x-ukc.icon name="sparkles" :size="18" /> Générer un document</h3>
                        <p style="font-size:13px;color:var(--ukc-muted);margin:4px 0 12px;">Choisissez un modèle, décrivez le document ou joignez un exemple. L'IA produit un <strong>brouillon</strong> à partir des données réelles de l'édition (participants, programme) - à relire avant diffusion.</p>

                        <form method="POST" action="{{ route('admin.secretariat.assist') }}" enctype="multipart/form-data">
                            @csrf
                            <div class="ukc-field">
                                <label>Colloque</label>
                                <select name="edition_id">
                                    @foreach ($docEditions as $ed)
                                        <option value="{{ $ed->id }}" @selected((string) $ed->id === (string) $currentDocEditionId)>{{ $ed->colloque?->name ? $ed->colloque->name.' - ' : '' }}{{ $ed->title ?: $ed->year }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="ukc-field">
                                <label>Modèles</label>
                                <div style="display:flex;gap:6px;flex-wrap:wrap;">
                                    @foreach ($presets as $key => $p)
                                        <button type="button" class="ukc-btn ukc-btn--sm" :class="preset==='{{ $key }}' ? 'ukc-btn--blue' : 'ukc-btn--outline'" @click="pick('{{ $key }}')">{{ $p['label'] }}</button>
                                    @endforeach
                                </div>
                            </div>

                            <input type="hidden" name="preset" :value="preset">
                            <div class="ukc-field">
                                <label>Description du document</label>
                                <textarea name="prompt" rows="5" x-model="prompt" placeholder="Ex. : Liste de présence de la séance d'ouverture, colonnes Nom, Institution, Signature."></textarea>
                            </div>

                            <div class="ukc-form-grid">
                                <div class="ukc-field"><label>Format</label>
                                    <select name="format" x-model="format">
                                        <option value="pdf">PDF (imprimable)</option>
                                        <option value="docx">Word (.docx)</option>
                                        <option value="xlsx">Excel (.xlsx)</option>
                                        <option value="csv">CSV</option>
                                    </select>
                                </div>
                                <div class="ukc-field"><label>Données jointes</label>
                                    <select name="dataset" x-model="dataset">
                                        <option value="none">Aucune (document narratif)</option>
                                        <option value="presence">Liste des participants (présence)</option>
                                    </select>
                                </div>
                            </div>

                            <div class="ukc-field">
                                <label>Modèle d'exemple (optionnel)</label>
                                <input type="file" name="example" accept=".pdf,.doc,.docx,.xls,.xlsx,.csv,.txt">
                            </div>

                            <div class="ukc-field">
                                <label>Fournisseur IA</label>
                                <select name="aiProvider">
                                    <option value="default">Par défaut (réglages)</option>
                                    <option value="claude">Claude</option>
                                    <option value="openai">OpenAI</option>
                                    <option value="gemini">Gemini</option>
                                    <option value="grok">Grok</option>
                                </select>
                            </div>

                            <button type="submit" class="ukc-btn ukc-btn--blue"><x-ukc.icon name="sparkles" :size="16" /> Générer</button>
                        </form>

                        @if (session('sec_error'))
                            <div class="ukc-alert ukc-alert--err" style="margin-top:14px;"><span class="ico"><x-ukc.icon name="info" :size="18" /></span><div>{{ session('sec_error') }}</div></div>
                        @endif

                        @if (session('sec_doc'))
                            @php($doc = session('sec_doc'))
                            <div style="margin-top:16px;border-top:1px solid var(--ukc-border);padding-top:14px;">
                                <h4 style="font-weight:900;margin-bottom:8px;">{{ $doc['title'] }}</h4>
                                @foreach ($doc['sections'] as $section)
                                    @if (! empty($section['heading']))<h5 style="font-weight:800;margin:10px 0 4px;">{{ $section['heading'] }}</h5>@endif
                                    <div style="white-space:pre-line;line-height:1.6;">{{ $section['body'] }}</div>
                                @endforeach
                                <a href="{{ route('admin.secretariat.assist.download', [$doc['edition_id'], $doc['id']]) }}" @if ($doc['format'] === 'pdf') target="_blank" @endif class="ukc-btn ukc-btn--green ukc-btn--sm" style="margin-top:12px;"><x-ukc.icon name="download" :size="15" /> Télécharger ({{ strtoupper($doc['format']) }})</a>
                            </div>
                        @endif
                    </div>

                    {{-- Historique --}}
                    <div class="ukc-card">
                        <h3>Documents récents</h3>
                        @forelse ($assistHistory as $h)
                            <div style="padding:10px 0;border-top:1px solid var(--ukc-border);display:flex;justify-content:space-between;gap:10px;align-items:center;flex-wrap:wrap;">
                                <div>
                                    <div style="font-weight:600;font-size:.9rem;">{{ $h['title'] }}</div>
                                    <div style="font-size:12px;color:var(--ukc-muted);">{{ strtoupper($h['format'] ?? 'pdf') }} · {{ $h['created_at'] ?? '' }}</div>
                                </div>
                                <a href="{{ route('admin.secretariat.assist.download', [$h['edition_id'], $h['id']]) }}" @if (($h['format'] ?? '') === 'pdf') target="_blank" @endif class="ukc-btn ukc-btn--ghost ukc-btn--sm"><x-ukc.icon name="download" :size="15" /></a>
                            </div>
                        @empty
                            <p style="font-size:.9rem;color:var(--ukc-muted);">Aucun document généré pour l'instant.</p>
                        @endforelse
                    </div>
                </div>
            @endif
        </div>{{-- /onglet Secrétariat assisté --}}
    </div>
</x-layouts.admin>
