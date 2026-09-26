<x-layouts.admin title="Formations" subtitle="Formations d'une édition - formateurs, frais, lieu, dates, inscriptions, e-mails, attestations et visio.">
    <div class="admin-page admin-formations" x-data="{ formOpen: false }">
        <div style="display:flex;justify-content:space-between;align-items:flex-end;gap:12px;flex-wrap:wrap;margin-bottom:18px;">
            <div class="ukc-field" style="max-width:460px;width:100%;margin:0;">
                <label>Édition</label>
                <select onchange="window.location='{{ route('admin.formations') }}?edition_id='+this.value">
                    <option value="">- Choisir -</option>
                    @foreach ($editions as $ed)
                        <option value="{{ $ed->id }}" @selected((string) $ed->id === $edition_id)>{{ $ed->title ?: $ed->year }}</option>
                    @endforeach
                </select>
            </div>
            @if ($edition && $moduleEnabled)
                <button type="button" class="ukc-btn ukc-btn--blue ukc-btn--sm" @click="formOpen = true"><x-ukc.icon name="plus" :size="14" /> Nouvelle formation</button>
            @endif
        </div>

        @if ($edition_id === '' || ! $edition)
            <div class="ukc-empty"><h3>Choisissez une édition</h3><p>Sélectionnez une édition pour gérer ses formations.</p></div>
        @elseif (! $moduleEnabled)
            <div class="ukc-empty">
                <h3>Module Formations désactivé</h3>
                <p>Activez-le dans les <a href="{{ route('admin.editions.edit', $edition) }}" style="color:var(--ukc-blue);font-weight:600;">paramètres de l’édition</a> (onglet « Options ») pour proposer des formations.</p>
            </div>
        @else
            <div class="ukc-card">
                <h2>Formations <span class="ukc-badge-count">{{ $formations->count() }}</span></h2>
                @forelse ($formations as $f)
                    <div class="ukc-subcard" x-data="{ editOpen: false }" style="border:1px solid var(--ukc-border,#e5e7eb);border-radius:10px;padding:14px;margin-bottom:14px;">
                        @if ($f->flyerUrl())
                            <img src="{{ $f->flyerUrl() }}" alt="Bannière" style="width:100%;height:130px;object-fit:cover;border-radius:8px;margin-bottom:12px;">
                        @endif
                        <div style="display:flex;justify-content:space-between;gap:10px;align-items:flex-start;">
                            <div>
                                <strong style="font-size:15px;">{{ $f->title }}</strong>
                                @if ($f->theme)<div style="font-size:13px;color:var(--ukc-muted);">{{ $f->theme }}</div>@endif
                                <div style="margin-top:6px;display:flex;gap:6px;flex-wrap:wrap;">
                                    <span class="ukc-pill {{ $f->published ? 'ukc-pill--ok' : 'ukc-pill--muted' }}">{{ $f->published ? 'Active' : 'Brouillon' }}</span>
                                    @if ($f->public)<span class="ukc-pill ukc-pill--info">Publique</span>@endif
                                    <span class="ukc-pill {{ $f->isFree() ? 'ukc-pill--ok' : 'ukc-pill--muted' }}">{{ $f->isFree() ? 'Gratuite' : number_format((float) $f->fee, 0, ',', ' ').' F' }}</span>
                                    @if ($f->isLive())<span class="ukc-pill ukc-pill--err">EN DIRECT</span>@endif
                                </div>
                                <div style="font-size:12.5px;color:var(--ukc-light);margin-top:6px;">
                                    @if ($f->starts_at){{ $f->starts_at->format('d/m/Y H:i') }}@if ($f->ends_at) - {{ $f->ends_at->format('d/m/Y H:i') }}@endif @endif
                                    @if ($f->location) · {{ $f->location }}@endif @if ($f->room) · {{ $f->room }}@endif
                                </div>
                            </div>
                            <div style="display:flex;gap:6px;align-items:center;flex-shrink:0;">
                                <button type="button" class="ukc-btn ukc-btn--outline ukc-btn--sm" @click="editOpen = true">Modifier</button>
                                <form method="POST" action="{{ route('admin.formations.destroy', $f) }}" onsubmit="return confirm('Supprimer cette formation ?')">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="ukc-btn--icon" title="Supprimer" aria-label="Supprimer la formation"><x-ukc.icon name="trash" :size="16" /></button>
                                </form>
                            </div>
                        </div>

                        {{-- Formateurs --}}
                        <div style="margin-top:12px;">
                            <div style="font-size:12.5px;font-weight:600;color:var(--ukc-muted);margin-bottom:6px;">Formateurs</div>
                            @php($avatar = 'width:42px;height:42px;border-radius:50%;overflow:hidden;display:flex;align-items:center;justify-content:center;flex-shrink:0;position:relative;')
                            @php($tin = 'border:1.5px solid var(--ukc-border,#cbd5e1);border-radius:10px;padding:9px 12px;font-size:13.5px;background:#fff;min-width:0;width:100%;')
                            @forelse ($f->trainers as $t)
                                <div x-data="formationTrainer({ photo: @js($t->photoUrl()), uploadUrl: '{{ route('admin.formations.trainers.photo', [$f, $t]) }}', updateUrl: '{{ route('admin.formations.trainers.update', [$f, $t]) }}', displayName: @js(trim(($t->title ? $t->title.' ' : '').$t->name)), sub: @js(collect([$t->specialty, $t->email])->filter()->join(' · ')), moderator: {{ $t->is_moderator ? 'true' : 'false' }} })" style="padding:6px 0;">
                                    <div style="display:flex;align-items:center;gap:10px;">
                                        {{-- Avatar cliquable : change la photo (AJAX + barre de progression) --}}
                                        <label title="Cliquer pour changer la photo" style="{{ $avatar }}cursor:pointer;background:linear-gradient(135deg,var(--ukc-blue,#223ea4),var(--ukc-green,#009933));color:#fff;font-weight:700;">
                                            <template x-if="photo"><img :src="photo" alt="" style="width:100%;height:100%;object-fit:cover;"></template>
                                            <template x-if="!photo"><span style="font-size:15px;">{{ $t->initials() }}</span></template>
                                            <input type="file" accept="image/png,image/jpeg" @change="uploadPhoto($event)" :disabled="uploading" style="position:absolute;inset:0;opacity:0;cursor:pointer;">
                                            {{-- Overlay de progression (vert institution, centré) - x-show sur wrapper --}}
                                            <div x-show="uploading" x-cloak style="position:absolute;inset:0;">
                                                <div style="position:absolute;inset:0;background:rgba(255,255,255,.72);display:flex;align-items:center;justify-content:center;">
                                                    <div style="width:72%;height:5px;border-radius:3px;background:rgba(0,0,0,.18);overflow:hidden;">
                                                        <div :style="'height:100%;background:var(--ukc-green,#009933);width:'+progress+'%;transition:width .15s;'"></div>
                                                    </div>
                                                </div>
                                            </div>
                                        </label>
                                        <div style="flex:1;min-width:0;">
                                            <div style="font-size:13px;"><span x-text="displayName"></span> <span class="ukc-pill ukc-pill--info" style="margin-left:2px;" x-show="moderator" x-cloak>modérateur</span></div>
                                            <div style="font-size:12px;color:var(--ukc-light);" x-text="sub"></div>
                                            <div x-show="error" x-cloak x-text="error" style="font-size:11.5px;color:var(--ukc-danger,#dc2626);margin-top:2px;"></div>
                                        </div>
                                        <button type="button" class="ukc-btn--icon" title="Modifier" aria-label="Modifier le formateur" @click="editing = !editing"><x-ukc.icon name="pen" :size="15" /></button>
                                        <form method="POST" action="{{ route('admin.formations.trainers.destroy', [$f, $t]) }}" onsubmit="return confirm('Retirer ce formateur ?')" style="margin:0;">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="ukc-btn--icon" title="Retirer" aria-label="Retirer le formateur"><x-ukc.icon name="close" :size="14" /></button>
                                        </form>
                                    </div>
                                    {{-- Édition en ligne (une seule ligne, comme l'ajout) - x-show sur wrapper pour garder le grid --}}
                                    <div x-show="editing" x-cloak style="margin-top:8px;">
                                        <form method="POST" action="{{ route('admin.formations.trainers.update', [$f, $t]) }}" @submit.prevent="saveInfo($event)"
                                              style="display:grid;grid-template-columns:110px minmax(0,1fr) minmax(0,1.2fr) minmax(0,1.3fr) auto auto;gap:8px;align-items:center;">
                                            @csrf @method('PUT')
                                            <input type="text" name="title" value="{{ $t->title }}" placeholder="Grade" list="trainerTitles{{ $f->id }}" maxlength="40" style="{{ $tin }}">
                                            <input type="text" name="name" value="{{ $t->name }}" placeholder="Nom *" required maxlength="200" style="{{ $tin }}">
                                            <input type="text" name="specialty" value="{{ $t->specialty }}" placeholder="Spécialité / fonction" maxlength="200" style="{{ $tin }}">
                                            <input type="email" name="email" value="{{ $t->email }}" placeholder="E-mail (facultatif)" maxlength="200" style="{{ $tin }}">
                                            <label title="Modérateur (visio)" style="display:flex;align-items:center;gap:6px;font-size:12.5px;color:var(--ukc-muted);white-space:nowrap;">
                                                <input type="hidden" name="is_moderator" value="0">
                                                <input type="checkbox" name="is_moderator" value="1" @checked($t->is_moderator)> Modér.
                                            </label>
                                            <button type="submit" class="ukc-btn ukc-btn--blue ukc-btn--sm" style="white-space:nowrap;" :disabled="saving" x-text="saving ? 'Enregistrement…' : 'Enregistrer'">Enregistrer</button>
                                        </form>
                                    </div>
                                </div>
                            @empty
                                <p style="font-size:12.5px;color:var(--ukc-light);margin:0;">Aucun formateur.</p>
                            @endforelse

                            {{-- Ajouter un formateur : tout sur une ligne (façon co-auteur du formulaire de soumission) --}}
                            <form method="POST" action="{{ route('admin.formations.trainers.store', $f) }}" enctype="multipart/form-data"
                                  style="margin-top:12px;border-top:1px solid var(--ukc-border,#f1f1f1);padding-top:12px;display:grid;grid-template-columns:auto 110px minmax(0,1fr) minmax(0,1.2fr) minmax(0,1.3fr) auto;gap:8px;align-items:center;"
                                  x-data="{ ph: '', pick(e) { const fl = e.target.files[0]; this.ph = fl ? URL.createObjectURL(fl) : ''; } }">
                                @csrf
                                <label title="Photo du formateur" style="{{ $avatar }}cursor:pointer;background:var(--ukc-bg,#eef2ff);border:1px dashed var(--ukc-border,#cbd5e1);color:var(--ukc-muted);">
                                    <template x-if="ph"><img :src="ph" alt="" style="width:100%;height:100%;object-fit:cover;"></template>
                                    <template x-if="!ph"><x-ukc.icon name="user" :size="20" /></template>
                                    <input type="file" name="photo" accept="image/png,image/jpeg" @change="pick($event)" style="position:absolute;inset:0;opacity:0;cursor:pointer;">
                                </label>
                                <input type="text" name="title" placeholder="Grade" list="trainerTitles{{ $f->id }}" maxlength="40" style="{{ $tin }}">
                                <input type="text" name="name" placeholder="Nom *" required maxlength="200" style="{{ $tin }}">
                                <input type="text" name="specialty" placeholder="Spécialité / fonction" maxlength="200" style="{{ $tin }}">
                                <input type="email" name="email" placeholder="E-mail (facultatif)" maxlength="200" style="{{ $tin }}">
                                <button type="submit" class="ukc-btn ukc-btn--outline ukc-btn--sm" style="white-space:nowrap;"><x-ukc.icon name="plus" :size="14" /> Ajouter</button>
                                <datalist id="trainerTitles{{ $f->id }}"><option value="M."></option><option value="Mme"></option><option value="Pr."></option><option value="Dr."></option><option value="Professeur"></option><option value="Docteur"></option></datalist>
                            </form>
                        </div>

                        {{-- Inscrits + message (convocation / rappel) --}}
                        <div style="margin-top:12px;">
                            <div style="display:flex;justify-content:space-between;align-items:center;gap:8px;margin-bottom:4px;">
                                <span style="font-size:12.5px;font-weight:600;color:var(--ukc-muted);">
                                    Inscrits : {{ $f->enrollments_count }}@if (! $f->isFree()) · {{ $f->paid_count }} réglé(s)@endif
                                </span>
                                <form method="POST" action="{{ route('admin.formations.attestations', $f) }}"
                                      onsubmit="return confirm('Émettre les attestations (participation aux inscrits + animation aux formateurs) ?')">
                                    @csrf
                                    <button type="submit" class="ukc-btn ukc-btn--outline ukc-btn--sm" title="Émettre & envoyer les attestations">
                                        <x-ukc.icon name="award" :size="14" /> Attestations
                                    </button>
                                </form>
                            </div>
                            @if ($f->enrollments_count > 0)
                                <details style="margin-bottom:6px;">
                                    <summary style="cursor:pointer;font-size:13px;color:var(--ukc-blue,#223ea4);">Liste des inscrits ({{ $f->enrollments_count }})</summary>
                                    <div style="margin-top:6px;">
                                        @foreach ($f->enrollments as $e)
                                            <div style="display:flex;justify-content:space-between;align-items:center;gap:8px;font-size:12.5px;padding:3px 0;border-bottom:1px solid var(--ukc-border,#f1f1f1);">
                                                <span>{{ $e->user?->name ?: '—' }}@if ($e->user?->email) <span style="color:var(--ukc-light);">· {{ $e->user->email }}</span>@endif</span>
                                                <span class="ukc-pill {{ $e->status === 'paid' ? 'ukc-pill--ok' : 'ukc-pill--muted' }}">{{ $e->status === 'paid' ? 'Réglé' : 'Inscrit' }}</span>
                                            </div>
                                        @endforeach
                                    </div>
                                </details>
                                <details>
                                    <summary style="cursor:pointer;font-size:13px;color:var(--ukc-blue,#223ea4);">Contacter les inscrits</summary>
                                    <form method="POST" action="{{ route('admin.formations.message', $f) }}" style="margin-top:8px;">
                                        @csrf
                                        <input type="text" name="subject" placeholder="Objet" required maxlength="200" style="width:100%;margin-bottom:6px;padding:6px 8px;border:1px solid var(--ukc-border,#e5e7eb);border-radius:8px;">
                                        <textarea name="body" rows="3" placeholder="Message (convocation, rappel, lien visio…)" required maxlength="5000" style="width:100%;margin-bottom:6px;padding:6px 8px;border:1px solid var(--ukc-border,#e5e7eb);border-radius:8px;"></textarea>
                                        <div style="display:flex;gap:8px;align-items:center;">
                                            <select name="audience" style="padding:6px 8px;border:1px solid var(--ukc-border,#e5e7eb);border-radius:8px;">
                                                <option value="all">Tous les inscrits</option>
                                                <option value="paid">Inscrits ayant réglé</option>
                                            </select>
                                            <button type="submit" class="ukc-btn ukc-btn--blue ukc-btn--sm"><x-ukc.icon name="mail" :size="14" /> Envoyer</button>
                                        </div>
                                    </form>
                                </details>
                            @endif
                        </div>

                        {{-- Visio de la formation --}}
                        @php($inp = 'padding:6px 8px;border:1px solid var(--ukc-border,#e5e7eb);border-radius:8px;')
                        <details style="margin-top:8px;">
                            <summary style="cursor:pointer;font-size:13px;color:var(--ukc-blue,#223ea4);">Visio</summary>
                            <form method="POST" action="{{ route('admin.formations.meeting', $f) }}" style="margin-top:8px;display:flex;flex-direction:column;gap:6px;max-width:520px;">
                                @csrf
                                <div style="display:flex;gap:6px;flex-wrap:wrap;">
                                    <select name="meeting_provider" style="{{ $inp }}flex:1;min-width:130px;">
                                        <option value="">Fournisseur par défaut</option>
                                        <option value="livekit" @selected($f->meeting_provider === 'livekit')>LiveKit</option>
                                        <option value="jitsi" @selected($f->meeting_provider === 'jitsi')>Jitsi</option>
                                        <option value="custom" @selected($f->meeting_provider === 'custom')>Lien externe</option>
                                    </select>
                                    <select name="meeting_status" style="{{ $inp }}flex:1;min-width:130px;">
                                        @foreach (['none' => 'Aucune', 'scheduled' => 'Programmée', 'live' => 'En direct', 'ended' => 'Terminée'] as $sv => $sl)
                                            <option value="{{ $sv }}" @selected(($f->meeting_status ?: 'none') === $sv)>{{ $sl }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <input type="text" name="meeting_room" value="{{ $f->meeting_room }}" placeholder="Nom de salle (auto si vide)" style="{{ $inp }}">
                                <input type="url" name="meeting_url" value="{{ $f->meeting_url }}" placeholder="Lien externe (si fournisseur « Lien externe »)" style="{{ $inp }}">
                                <div style="display:flex;gap:8px;align-items:center;">
                                    <select name="meeting_visibility" style="{{ $inp }}">
                                        <option value="participants" @selected(($f->meeting_visibility ?: 'participants') === 'participants')>Réservée aux inscrits</option>
                                        <option value="public" @selected($f->meeting_visibility === 'public')>Publique</option>
                                    </select>
                                    <button type="submit" class="ukc-btn ukc-btn--blue ukc-btn--sm"><x-ukc.icon name="video" :size="14" /> Enregistrer</button>
                                </div>
                            </form>
                        </details>

                        {{-- Slideover : modifier la formation --}}
                        <x-ukc.slideover open="editOpen" onClose="editOpen = false" width="520px">
                            <x-slot:header>Modifier la formation</x-slot:header>
                            <form method="POST" action="{{ route('admin.formations.update', $f) }}" enctype="multipart/form-data" id="formEdit{{ $f->id }}">
                                @csrf @method('PUT')
                                @include('admin.formations._fields', ['f' => $f])
                            </form>
                            <x-slot:footer>
                                <span style="flex:1;"></span>
                                <button type="button" class="ukc-btn ukc-btn--ghost" @click="editOpen = false">Annuler</button>
                                <button type="submit" form="formEdit{{ $f->id }}" class="ukc-btn ukc-btn--blue">Enregistrer</button>
                            </x-slot:footer>
                        </x-ukc.slideover>
                    </div>
                @empty
                    <p style="font-size:13.5px;color:var(--ukc-light);">Aucune formation pour l’instant. Cliquez sur « Nouvelle formation » pour en créer une.</p>
                @endforelse
            </div>

            {{-- Slideover : nouvelle formation (+ paramétrage assisté IA) --}}
            <x-ukc.slideover open="formOpen" onClose="formOpen = false" width="520px">
                <x-slot:header>Nouvelle formation</x-slot:header>
                <form method="POST" action="{{ route('admin.formations.store', $edition) }}" enctype="multipart/form-data" id="formationCreate">
                    @csrf
                    @if ($aiAvailable)
                        <div x-data="{ prompt: '', busy: false, msg: '', async run() { const form = $root.closest('form'); const fd = new FormData(); const files = $refs.aiFiles.files; for (const f of files) fd.append('aiFiles[]', f); if (this.prompt.trim()) fd.append('aiPrompt', this.prompt); if (!files.length && !this.prompt.trim()) { this.msg = 'Ajoutez un document ou une consigne.'; return; } this.busy = true; this.msg = ''; try { const t = form.querySelector('input[name=_token]')?.value; const r = await fetch('{{ route('admin.formations.ai') }}', { method: 'POST', headers: { 'X-CSRF-TOKEN': t, 'Accept': 'application/json' }, body: fd }); const d = await r.json(); if (d.ok) { for (const [k, v] of Object.entries(d.fields || {})) { const el = form.querySelector('[name=\'' + k + '\']'); if (el) el.value = v; } this.msg = d.message || 'Appliqué.'; } else { this.msg = d.message || 'Échec.'; } } catch (e) { this.msg = 'Erreur réseau.'; } this.busy = false; } }"
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
