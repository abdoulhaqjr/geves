<x-layouts.admin title="Secrétariat assisté" subtitle="Documents sur mesure générés par l'IA, à relire avant diffusion">
    <div class="admin-page">
        @include('admin.secretariat._nav', ['current' => 'assist'])

        @if ($docEditions->isEmpty())
            <div class="ukc-empty"><h3>Aucun événement</h3><p>Vous n'avez accès aux documents d'aucun événement.</p></div>
        @else
            {{-- Bloc rafraîchi après génération : le document produit et
                 l'historique changent d'un coup. --}}
            <div class="ukc-grid-2" style="align-items:start;" data-ajax-root="sec-doc">
                {{-- Générateur --}}
                <div class="ukc-card"
                     x-data="{
                        presets: @js($presets),
                        preset: @js(old('preset', '')), prompt: @js(old('prompt', '')),
                        format: @js(old('format', 'pdf')), dataset: @js(old('dataset', 'none')),
                        pick(k){ const p=this.presets[k]; if(!p) return; this.preset=k; this.prompt=p.prompt; this.format=p.format; this.dataset=p.dataset; }
                     }">
                    <h3><x-ukc.icon name="sparkles" :size="18" /> Générer un document</h3>
                    <p style="font-size:13px;color:var(--ukc-muted);margin:4px 0 12px;">Décrivez le document ou joignez un exemple. L'IA produit un <strong>brouillon</strong> à partir des données réelles de l'événement (participants, programme) - à relire avant diffusion. Pour les documents standard (listes, émargement, ordre du jour…), l'onglet <a href="{{ route('admin.secretariat.documents') }}" style="color:var(--ukc-blue);font-weight:600;">Documents</a> est plus rapide.</p>

                    <form method="POST" action="{{ route('admin.secretariat.assist') }}" enctype="multipart/form-data"
                          data-ajax data-ajax-target="sec-doc" data-ajax-msg="Document généré."
                          data-ai data-ai-title="Rédaction du document…"
                          data-ai-steps="Lecture de votre consigne…|Collecte des données de l’événement…|Rédaction du brouillon…|Mise en forme du document…">
                        @csrf
                        <div class="ukc-field">
                            <label>Colloque</label>
                            <select name="event_id">
                                @foreach ($docEditions as $ed)
                                    <option value="{{ $ed->id }}" @selected((string) $ed->id === (string) $currentDocEditionId)>{{ $ed->series?->name ? $ed->series->name.' - ' : '' }}{{ $ed->title ?: $ed->year }}</option>
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
                            <textarea name="prompt" rows="5" x-model="prompt" placeholder="Ex. : Note de service pour les chefs d'atelier, ton formel, une page."></textarea>
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
                            <a href="{{ route('admin.secretariat.assist.download', [$doc['event_id'], $doc['id']]) }}" @if ($doc['format'] === 'pdf') target="_blank" @endif class="ukc-btn ukc-btn--green ukc-btn--sm" style="margin-top:12px;"><x-ukc.icon name="download" :size="15" /> Télécharger ({{ strtoupper($doc['format']) }})</a>
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
                            <a href="{{ route('admin.secretariat.assist.download', [$h['event_id'], $h['id']]) }}" @if (($h['format'] ?? '') === 'pdf') target="_blank" @endif class="ukc-btn ukc-btn--ghost ukc-btn--sm"><x-ukc.icon name="download" :size="15" /></a>
                        </div>
                    @empty
                        <p style="font-size:.9rem;color:var(--ukc-muted);">Aucun document généré pour l'instant.</p>
                    @endforelse
                </div>
            </div>
        @endif
    </div>
</x-layouts.admin>
