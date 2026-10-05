<x-layouts.admin title="Formations" subtitle="Formations de l'événement - ouvrez une formation pour ses formateurs, ses inscrits et sa visio.">
    <div class="admin-page admin-formations" x-data="{ formOpen: false }">
        <div style="display:flex;justify-content:space-between;align-items:flex-end;gap:12px;flex-wrap:wrap;margin-bottom:18px;">
            <div class="ukc-field" style="max-width:460px;width:100%;margin:0;">
                <label>Événement</label>
                <select onchange="window.location='{{ route('admin.formations') }}?event_id='+this.value">
                    <option value="">- Choisir -</option>
                    @foreach ($events as $ed)
                        <option value="{{ $ed->id }}" @selected((string) $ed->id === $event_id)>{{ $ed->title ?: $ed->year }}</option>
                    @endforeach
                </select>
            </div>
            @if ($event && $moduleEnabled)
                <button type="button" class="ukc-btn ukc-btn--blue ukc-btn--sm" @click="formOpen = true"><x-ukc.icon name="plus" :size="14" /> Nouvelle formation</button>
            @endif
        </div>

        @if ($event_id === '' || ! $event)
            <div class="ukc-empty"><h3>Choisissez une événement</h3><p>Sélectionnez une événement pour gérer ses formations.</p></div>
        @elseif (! $moduleEnabled)
            <div class="ukc-empty">
                <h3>Module Formations désactivé</h3>
                <p>Activez-le dans les <a href="{{ route('admin.evenements.edit', $event) }}" style="color:var(--ukc-blue);font-weight:600;">paramètres de l’événement</a> (onglet « Options ») pour proposer des formations.</p>
            </div>
        @else
            <div class="ukc-card" style="padding:0;">
                @if ($formations->count())
                    <div class="ukc-table-wrap">
                        <table class="ukc-table">
                            <thead>
                                <tr>
                                    <th>Formation</th>
                                    <th>Dates</th>
                                    <th>Lieu</th>
                                    <th>État</th>
                                    <th style="text-align:right;">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($formations as $f)
                                    <tr>
                                        <td>
                                            <div style="display:flex;align-items:center;gap:10px;min-width:0;">
                                                <span style="width:56px;height:38px;border-radius:7px;overflow:hidden;flex-shrink:0;display:inline-flex;align-items:center;justify-content:center;background:linear-gradient(135deg,var(--ukc-blue,#223ea4),var(--ukc-green,#009933));color:#fff;">
                                                    @if ($f->flyerUrl())
                                                        <img src="{{ $f->flyerUrl() }}" alt="" style="width:100%;height:100%;object-fit:cover;">
                                                    @else
                                                        <x-ukc.icon name="book" :size="16" />
                                                    @endif
                                                </span>
                                                <div style="min-width:0;">
                                                    <div class="title"><a href="{{ route('admin.formations.show', $f) }}">{{ $f->title }}</a></div>
                                                    @if ($f->theme)<div class="meta">{{ $f->theme }}</div>@endif
                                                </div>
                                            </div>
                                        </td>
                                        <td style="white-space:nowrap;font-size:12.5px;">
                                            @if ($f->starts_at)
                                                {{ $f->starts_at->format('d/m/Y H:i') }}
                                                @if ($f->ends_at)<div class="meta">→ {{ $f->ends_at->format('d/m/Y H:i') }}</div>@endif
                                            @else
                                                <span class="meta">-</span>
                                            @endif
                                        </td>
                                        <td style="font-size:12.5px;">
                                            {{ $f->location ?: '-' }}
                                            @if ($f->room)<div class="meta">{{ $f->room }}</div>@endif
                                        </td>
                                        <td style="white-space:nowrap;">
                                            <span class="ukc-pill {{ $f->published ? 'ukc-pill--ok' : 'ukc-pill--muted' }}">{{ $f->published ? 'Active' : 'Brouillon' }}</span>
                                            @if ($f->public)<span class="ukc-pill ukc-pill--info">Publique</span>@endif
                                            @if ($f->isLive())<span class="ukc-pill ukc-pill--err">EN DIRECT</span>@endif
                                        </td>
                                        <td style="text-align:right;white-space:nowrap;">
                                            <div class="ukc-actions">
                                                <a href="{{ route('admin.formations.show', $f) }}" class="ukc-btn ukc-btn--outline ukc-btn--sm">Ouvrir</a>
                                                <form method="POST" action="{{ route('admin.formations.destroy', $f) }}" data-confirm="Supprimer cette formation ?" style="margin:0;">
                                                    @csrf @method('DELETE')
                                                    <button type="submit" class="ukc-btn--icon" title="Supprimer" aria-label="Supprimer la formation"><x-ukc.icon name="trash" :size="16" /></button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="ukc-empty" style="margin:0;">
                        <h3>Aucune formation</h3>
                        <p>Cliquez sur « Nouvelle formation » pour en créer une.</p>
                    </div>
                @endif
            </div>

            {{-- Slideover : nouvelle formation (+ paramétrage assisté IA) --}}
            <x-ukc.slideover open="formOpen" onClose="formOpen = false" width="520px">
                <x-slot:header>Nouvelle formation</x-slot:header>
                <form method="POST" action="{{ route('admin.formations.store', $event) }}" enctype="multipart/form-data" id="formationCreate">
                    @csrf
                    @if ($aiAvailable)
                        <div x-data="{ prompt: '', busy: false, msg: '', async run() { const form = $root.closest('form'); const fd = new FormData(); const files = $refs.aiFiles.files; for (const f of files) fd.append('aiFiles[]', f); if (this.prompt.trim()) fd.append('aiPrompt', this.prompt); if (!files.length && !this.prompt.trim()) { this.msg = 'Ajoutez un document ou une consigne.'; return; } this.busy = true; this.msg = ''; window.ukcAiVeil?.show({ title: 'Analyse du document…', steps: ['Lecture de votre document…', 'Repérage du thème et des dates…', 'Relevé des formateurs et du tarif…', 'Préparation des champs…'] }); try { const t = form.querySelector('input[name=_token]')?.value; const r = await fetch('{{ route('admin.formations.ai') }}', { method: 'POST', headers: { 'X-CSRF-TOKEN': t, 'Accept': 'application/json' }, body: fd }); const d = await r.json(); if (d.ok) { for (const [k, v] of Object.entries(d.fields || {})) { const el = form.querySelector('[name=\'' + k + '\']'); if (el) el.value = v; } this.msg = d.message || 'Appliqué.'; window.ukcAiVeil?.done('Champs renseignés.'); } else { this.msg = d.message || 'Échec.'; window.ukcAiVeil?.done(this.msg, false); } } catch (e) { this.msg = 'Erreur réseau.'; window.ukcAiVeil?.done('Erreur réseau.', false); } setTimeout(() => window.ukcAiVeil?.hide(), 900); this.busy = false; } }"
                             style="border:1px solid var(--ukc-border,#e5e7eb);border-radius:10px;padding:12px;margin-bottom:14px;background:#faf9ff;">
                            <div style="font-weight:600;display:flex;align-items:center;gap:6px;margin-bottom:4px;"><x-ukc.icon name="sparkles" :size="16" /> Paramétrage assisté (IA)</div>
                            <p style="font-size:12.5px;color:var(--ukc-muted);margin:0 0 8px;">Analysez un document (flyer, programme…) ou décrivez la formation pour pré-remplir les champs ci-dessous.</p>
                            <input type="file" x-ref="aiFiles" multiple accept=".pdf,.png,.jpg,.jpeg,.webp" style="font-size:12.5px;margin-bottom:6px;display:block;">
                            <textarea x-model="prompt" rows="2" placeholder="Ex. : titre, objectifs, dates, lieu…" style="width:100%;padding:6px 8px;border:1px solid var(--ukc-border,#e5e7eb);border-radius:8px;margin-bottom:6px;"></textarea>
                            <div style="display:flex;align-items:center;gap:8px;">
                                <button type="button" class="ukc-btn ukc-btn--outline ukc-btn--sm" @click="run()" :disabled="busy"><x-ukc.icon name="sparkles" :size="14" /> <span x-text="busy ? 'Analyse…' : 'Analyser'"></span></button>
                                <span style="font-size:12px;color:var(--ukc-muted);" x-text="msg"></span>
                            </div>
                        </div>
                    @endif
                    @include('admin.formations._fields', ['f' => null])
                </form>
                <x-slot:footer>
                    <span style="flex:1;"></span>
                    <button type="button" class="ukc-btn ukc-btn--ghost" @click="formOpen = false">Annuler</button>
                    <button type="submit" form="formationCreate" class="ukc-btn ukc-btn--blue"><x-ukc.icon name="book" :size="15" /> Créer</button>
                </x-slot:footer>
            </x-ukc.slideover>
        @endif
    </div>
</x-layouts.admin>
