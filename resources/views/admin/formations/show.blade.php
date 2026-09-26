<x-layouts.admin :title="$f->title" subtitle="Formation - formateurs, inscrits, visio et attestations.">
    <x-slot:actions>
        <a href="{{ route('admin.formations', ['edition_id' => $f->edition_id]) }}" class="ukc-btn ukc-btn--ghost ukc-btn--sm">
            <x-ukc.icon name="chevron" :size="16" style="transform:rotate(180deg);" /> Toutes les formations
        </a>
    </x-slot:actions>

    <style>
        /* Ligne « formateur » : tout sur une ligne, repliée en 2 puis 1 colonne. */
        .trainer-grid { display:grid; gap:8px; align-items:center;
            grid-template-columns:auto 100px minmax(0,1fr) minmax(0,1fr) minmax(0,1.1fr) minmax(0,.9fr) auto; }
        .trainer-grid--edit { grid-template-columns:100px minmax(0,1fr) minmax(0,1fr) minmax(0,1.1fr) minmax(0,.9fr) auto auto; }
        @media (max-width: 1400px) {
            .trainer-grid, .trainer-grid--edit { grid-template-columns:repeat(2, minmax(0,1fr)); }
            .trainer-grid > .tg-avatar { grid-column:1/-1; }
        }
        @media (max-width: 720px) {
            .trainer-grid, .trainer-grid--edit { grid-template-columns:minmax(0,1fr); }
        }
        .trainer-hint { grid-column:1/-1; font-size:12px; margin:0; }
        .trainer-hint.ok { color:var(--ukc-green,#009933); }
        .trainer-hint.info { color:var(--ukc-muted); }
        .f-hero { position:relative; border-radius:14px; overflow:hidden; aspect-ratio:16/4; min-height:150px;
                  background:linear-gradient(135deg,var(--ukc-blue,#223ea4),var(--ukc-green,#009933)); margin-bottom:18px; }
        .f-hero__body { position:absolute; inset:auto 0 0 0; padding:18px 20px;
                        background:linear-gradient(to top, rgba(0,0,0,.72), rgba(0,0,0,0)); color:#fff; }
    </style>

    @php($avatar = 'width:42px;height:42px;border-radius:50%;overflow:hidden;display:flex;align-items:center;justify-content:center;flex-shrink:0;position:relative;')
    @php($tin = 'border:1.5px solid var(--ukc-border,#cbd5e1);border-radius:10px;padding:9px 12px;font-size:13.5px;background:#fff;min-width:0;width:100%;')
    @php($inp = 'padding:7px 10px;border:1px solid var(--ukc-border,#e5e7eb);border-radius:8px;')

    {{-- Onglets : l'ancre pilote l'onglet, et il est mémorisé pour la session -
         les actions (ajout de formateur, envoi d'e-mail…) repassent par `back()`,
         qui perd l'ancre, et doivent revenir sur l'onglet d'où elles partent. --}}
    <div class="admin-page admin-formations"
         x-data="{
            editOpen: false,
            tab: (location.hash || '').substring(1) || (() => { try { return sessionStorage.getItem('fTab{{ $f->id }}'); } catch (e) { return null; } })() || 'formateurs',
            go(t) { this.tab = t; history.replaceState(null, '', '#' + t); try { sessionStorage.setItem('fTab{{ $f->id }}', t); } catch (e) {} },
         }">
        {{-- Bandeau --}}
        <div class="f-hero">
            @if ($f->flyerUrl())
                <img src="{{ $f->flyerUrl() }}" alt="" style="position:absolute;inset:0;width:100%;height:100%;object-fit:cover;">
            @endif
            <div class="f-hero__body">
                <div style="display:flex;justify-content:space-between;align-items:flex-end;gap:14px;flex-wrap:wrap;">
                    <div style="min-width:0;">
                        <h1 style="margin:0;font-size:22px;color:#fff;">{{ $f->title }}</h1>
                        @if ($f->theme)<div style="font-size:13.5px;opacity:.85;">{{ $f->theme }}</div>@endif
                        <div style="font-size:12.5px;opacity:.8;margin-top:6px;">
                            @if ($f->starts_at){{ $f->starts_at->format('d/m/Y H:i') }}@if ($f->ends_at) - {{ $f->ends_at->format('d/m/Y H:i') }}@endif @endif
                            @if ($f->location) · {{ $f->location }}@endif @if ($f->room) · {{ $f->room }}@endif
                        </div>
                    </div>
                    <div style="display:flex;gap:8px;align-items:center;flex-shrink:0;">
                        <button type="button" class="ukc-btn ukc-btn--blue ukc-btn--sm" @click="editOpen = true">
                            <x-ukc.icon name="pen" :size="14" /> Modifier
                        </button>
                        <form method="POST" action="{{ route('admin.formations.destroy', $f) }}" data-confirm="Supprimer cette formation ?" style="margin:0;">
                            @csrf @method('DELETE')
                            <button type="submit" class="ukc-btn ukc-btn--danger ukc-btn--sm"><x-ukc.icon name="trash" :size="14" /> Supprimer</button>
                        </form>
                    </div>
                </div>
                <div style="margin-top:8px;display:flex;gap:6px;flex-wrap:wrap;">
                    <span class="ukc-pill {{ $f->published ? 'ukc-pill--ok' : 'ukc-pill--muted' }}">{{ $f->published ? 'Active' : 'Brouillon' }}</span>
                    @if ($f->public)<span class="ukc-pill ukc-pill--info">Publique</span>@endif
                    <span class="ukc-pill {{ $f->isFree() ? 'ukc-pill--ok' : 'ukc-pill--muted' }}">{{ $f->isFree() ? 'Gratuite' : number_format((float) $f->fee, 0, ',', ' ').' F' }}</span>
                    @if ($f->isLive())<span class="ukc-pill ukc-pill--err">EN DIRECT</span>@endif
                </div>
            </div>
        </div>

        {{-- Indicateurs --}}
        <div class="ukc-stats">
            <div class="ukc-stat-card tw-stat"><div class="tw-stat__icon tw-stat__icon--blue"><x-ukc.icon name="users" :size="20" /></div><div class="lbl">Inscrits</div><div class="val blue">{{ $stats['enrolled'] }}</div><div class="hint">{{ $f->capacity ? 'sur '.$f->capacity.' place(s)' : 'sans limite de places' }}</div></div>
            <div class="ukc-stat-card tw-stat"><div class="tw-stat__icon tw-stat__icon--green"><x-ukc.icon name="check-circle" :size="20" /></div><div class="lbl">Réglés</div><div class="val green">{{ $stats['paid'] }}</div><div class="hint">{{ $f->isFree() ? 'formation gratuite' : 'inscriptions payées' }}</div></div>
            <div class="ukc-stat-card tw-stat"><div class="tw-stat__icon tw-stat__icon--amber"><x-ukc.icon name="card" :size="20" /></div><div class="lbl">Recette</div><div class="val">{{ number_format($stats['revenue'], 0, ',', ' ') }}</div><div class="hint">{{ config('payments.currency', 'XOF') }} encaissés</div></div>
            <div class="ukc-stat-card tw-stat"><div class="tw-stat__icon tw-stat__icon--blue"><x-ukc.icon name="mic" :size="20" /></div><div class="lbl">Formateurs</div><div class="val">{{ $stats['trainers'] }}</div><div class="hint">{{ $f->trainers->where('is_moderator', true)->count() }} modérateur(s)</div></div>
        </div>

        {{-- Onglets « pilules », même design que admin/projet (.ukc-nav-docs global). --}}
        <div class="ukc-nav-docs">
            <button type="button" @click="go('formateurs')" :class="{ active: tab==='formateurs' }">Formateurs <span class="nav-count">{{ $stats['trainers'] }}</span></button>
            <button type="button" @click="go('inscrits')" :class="{ active: tab==='inscrits' }">Inscrits <span class="nav-count">{{ $stats['enrolled'] }}</span></button>
            <button type="button" @click="go('communication')" :class="{ active: tab==='communication' }">Communication</button>
            <button type="button" @click="go('visio')" :class="{ active: tab==='visio' }">Visio</button>
        </div>

        {{-- Formateurs --}}
        <div class="ukc-card" x-show="tab==='formateurs'" x-cloak>
            <h2>Formateurs <span class="ukc-badge-count">{{ $f->trainers->count() }}</span></h2>

            @forelse ($f->trainers as $t)
                <div x-data="formationTrainer({ photo: @js($t->photoUrl()), uploadUrl: '{{ route('admin.formations.trainers.photo', [$f, $t]) }}', updateUrl: '{{ route('admin.formations.trainers.update', [$f, $t]) }}', displayName: @js(trim(($t->title ? $t->title.' ' : '').$t->name)), sub: @js(collect([$t->specialty, $t->email])->filter()->join(' · ')), moderator: {{ $t->is_moderator ? 'true' : 'false' }} })"
                     style="padding:8px 0;border-bottom:1px solid var(--ukc-border,#f1f1f1);">
                    <div style="display:flex;align-items:center;gap:10px;">
                        <label title="Cliquer pour changer la photo" style="{{ $avatar }}cursor:pointer;background:linear-gradient(135deg,var(--ukc-blue,#223ea4),var(--ukc-green,#009933));color:#fff;font-weight:700;">
                            <template x-if="photo"><img :src="photo" alt="" style="width:100%;height:100%;object-fit:cover;"></template>
                            <template x-if="!photo"><span style="font-size:15px;">{{ $t->initials() }}</span></template>
                            <input type="file" accept="image/png,image/jpeg" @change="uploadPhoto($event)" :disabled="uploading" style="position:absolute;inset:0;opacity:0;cursor:pointer;">
                            <div x-show="uploading" x-cloak style="position:absolute;inset:0;">
                                <div style="position:absolute;inset:0;background:rgba(255,255,255,.72);display:flex;align-items:center;justify-content:center;">
                                    <div style="width:72%;height:5px;border-radius:3px;background:rgba(0,0,0,.18);overflow:hidden;">
                                        <div :style="'height:100%;background:var(--ukc-green,#009933);width:'+progress+'%;transition:width .15s;'"></div>
                                    </div>
                                </div>
                            </div>
                        </label>
                        <div style="flex:1;min-width:0;">
                            <div style="font-size:13.5px;"><span x-text="displayName"></span> <span class="ukc-pill ukc-pill--info" style="margin-left:2px;" x-show="moderator" x-cloak>modérateur</span></div>
                            <div style="font-size:12px;color:var(--ukc-light);" x-text="sub"></div>
                            <div x-show="error" x-cloak x-text="error" style="font-size:11.5px;color:var(--ukc-danger,#dc2626);margin-top:2px;"></div>
                        </div>
                        <button type="button" class="ukc-btn--icon" title="Modifier" aria-label="Modifier le formateur" @click="editing = !editing"><x-ukc.icon name="pen" :size="15" /></button>
                        <form method="POST" action="{{ route('admin.formations.trainers.destroy', [$f, $t]) }}" data-confirm="Retirer ce formateur ?" style="margin:0;">
                            @csrf @method('DELETE')
                            <button type="submit" class="ukc-btn--icon" title="Retirer" aria-label="Retirer le formateur"><x-ukc.icon name="close" :size="14" /></button>
                        </form>
                    </div>
                    <div x-show="editing" x-cloak style="margin-top:8px;"
                         x-data="trainerLookup({ lookupUrl: '{{ route('admin.formations.trainers.lookup') }}', linked: {{ $t->user_id ? 'true' : 'false' }} })">
                        <form method="POST" action="{{ route('admin.formations.trainers.update', [$f, $t]) }}" @submit.prevent="saveInfo($event)"
                              class="trainer-grid trainer-grid--edit">
                            @csrf @method('PUT')
                            <input type="text" name="title" value="{{ $t->title }}" placeholder="Grade" list="trainerTitles" maxlength="40" style="{{ $tin }}">
                            <input type="text" name="name" value="{{ $t->name }}" placeholder="Nom *" required maxlength="200" :readonly="linked" :style="linked ? '{{ $tin }}background:var(--ukc-bg,#f6f7fb);' : '{{ $tin }}'" style="{{ $tin }}">
                            <input type="text" name="specialty" value="{{ $t->specialty }}" placeholder="Spécialité / fonction" maxlength="200" style="{{ $tin }}">
                            <input type="email" name="email" value="{{ $t->email }}" placeholder="E-mail" maxlength="200" style="{{ $tin }}" @change="lookup()">
                            <x-ukc.phone name="phone" :value="$t->phone" placeholder="Téléphone" style="{{ $tin }}" @change="lookup()" />
                            <label title="Modérateur (visio)" style="display:flex;align-items:center;gap:6px;font-size:12.5px;color:var(--ukc-muted);white-space:nowrap;">
                                <input type="hidden" name="is_moderator" value="0">
                                <input type="checkbox" name="is_moderator" value="1" @checked($t->is_moderator)> Modér.
                            </label>
                            <button type="submit" class="ukc-btn ukc-btn--blue ukc-btn--sm" style="white-space:nowrap;" :disabled="saving" x-text="saving ? 'Enregistrement…' : 'Enregistrer'">Enregistrer</button>
                            <p class="trainer-hint" :class="hintType" x-show="hint" x-cloak x-text="hint"></p>
                        </form>
                    </div>
                </div>
            @empty
                <p style="font-size:13px;color:var(--ukc-light);margin:0 0 8px;">Aucun formateur pour l’instant.</p>
            @endforelse

            {{-- Ajouter un formateur (rapprochement compte par e-mail / téléphone) --}}
            <div x-data="trainerLookup({ lookupUrl: '{{ route('admin.formations.trainers.lookup') }}' })" style="margin-top:14px;">
                <form method="POST" action="{{ route('admin.formations.trainers.store', $f) }}" enctype="multipart/form-data" class="trainer-grid"
                      x-data="{ ph: '', pick(e) { const fl = e.target.files[0]; this.ph = fl ? URL.createObjectURL(fl) : ''; } }">
                    @csrf
                    <label class="tg-avatar" title="Photo du formateur" style="{{ $avatar }}cursor:pointer;background:var(--ukc-bg,#eef2ff);border:1px dashed var(--ukc-border,#cbd5e1);color:var(--ukc-muted);">
                        <template x-if="ph"><img :src="ph" alt="" style="width:100%;height:100%;object-fit:cover;"></template>
                        <template x-if="!ph"><x-ukc.icon name="user" :size="20" /></template>
                        <input type="file" name="photo" accept="image/png,image/jpeg" @change="pick($event)" style="position:absolute;inset:0;opacity:0;cursor:pointer;">
                    </label>
                    <input type="text" name="title" placeholder="Grade" list="trainerTitles" maxlength="40" style="{{ $tin }}">
                    <input type="text" name="name" placeholder="Nom *" required maxlength="200" :readonly="linked" :style="linked ? '{{ $tin }}background:var(--ukc-bg,#f6f7fb);' : '{{ $tin }}'" style="{{ $tin }}">
                    <input type="text" name="specialty" placeholder="Spécialité / fonction" maxlength="200" style="{{ $tin }}">
                    <input type="email" name="email" placeholder="E-mail" maxlength="200" style="{{ $tin }}" @change="lookup()">
                    <x-ukc.phone name="phone" placeholder="Téléphone" style="{{ $tin }}" @change="lookup()" />
                    <button type="submit" class="ukc-btn ukc-btn--outline ukc-btn--sm" style="white-space:nowrap;"><x-ukc.icon name="plus" :size="14" /> Ajouter</button>
                    <p class="trainer-hint" :class="hintType" x-show="hint" x-cloak x-text="hint"></p>
                </form>
            </div>
            <datalist id="trainerTitles"><option value="M."></option><option value="Mme"></option><option value="Pr."></option><option value="Dr."></option><option value="Professeur"></option><option value="Docteur"></option></datalist>
        </div>

        {{-- Inscrits : la vraie table, impossible à loger dans une carte --}}
        <div class="ukc-card" style="padding:0;" x-show="tab==='inscrits'" x-cloak>
            <div style="display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap;padding:16px 18px 0;">
                <h2 style="margin:0;">Inscrits <span class="ukc-badge-count">{{ $stats['enrolled'] }}</span></h2>
                <form method="POST" action="{{ route('admin.formations.attestations', $f) }}" style="margin:0;"
                      data-confirm="Émettre les attestations (participation aux inscrits + animation aux formateurs) ?">
                    @csrf
                    <button type="submit" class="ukc-btn ukc-btn--outline ukc-btn--sm"><x-ukc.icon name="award" :size="14" /> Émettre les attestations</button>
                </form>
            </div>

            @if ($f->enrollments->isNotEmpty())
                <div class="ukc-table-wrap" style="margin-top:12px;">
                    <table class="ukc-table">
                        <thead>
                            <tr><th>Participant</th><th>E-mail</th><th>Téléphone</th><th style="text-align:right;">Montant</th><th>Paiement</th><th>Inscrit le</th><th>Statut</th></tr>
                        </thead>
                        <tbody>
                            @foreach ($f->enrollments as $e)
                                <tr>
                                    <td class="title">{{ $e->user?->name ?: '-' }}</td>
                                    <td class="meta">{{ $e->user?->email ?: '-' }}</td>
                                    <td class="meta">{{ $e->user?->phone ?: '-' }}</td>
                                    <td style="text-align:right;font-weight:700;white-space:nowrap;">{{ number_format((float) $e->amount, 0, ',', ' ') }}</td>
                                    <td class="meta" style="font-family:var(--ukc-mono);">{{ $e->payment?->reference ?: '-' }}</td>
                                    <td style="white-space:nowrap;font-size:12.5px;">{{ $e->created_at?->format('d/m/Y') }}</td>
                                    <td><span class="ukc-pill {{ $e->status === 'paid' ? 'ukc-pill--ok' : 'ukc-pill--warn' }}">{{ $e->status === 'paid' ? 'Réglé' : 'Inscrit' }}</span></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="ukc-empty" style="margin:12px 0 0;"><h3>Aucun inscrit</h3><p>Les inscriptions apparaîtront ici.</p></div>
            @endif
        </div>

        <div>
            {{-- Contacter les inscrits --}}
            <div class="ukc-card" x-show="tab==='communication'" x-cloak>
                <h2>Contacter les inscrits</h2>
                <form method="POST" action="{{ route('admin.formations.message', $f) }}">
                    @csrf
                    <div class="ukc-form-row">
                        <label>Objet</label>
                        <input type="text" name="subject" placeholder="Convocation, rappel…" required maxlength="200">
                    </div>
                    <div class="ukc-form-row">
                        <label>Message</label>
                        <textarea name="body" rows="4" placeholder="Convocation, rappel, lien visio…" required maxlength="5000"></textarea>
                    </div>
                    <div style="display:flex;gap:10px;align-items:center;flex-wrap:wrap;">
                        <select name="audience" style="{{ $inp }}">
                            <option value="all">Tous les inscrits</option>
                            <option value="paid">Inscrits ayant réglé</option>
                        </select>
                        <button type="submit" class="ukc-btn ukc-btn--blue ukc-btn--sm" @disabled($f->enrollments->isEmpty())>
                            <x-ukc.icon name="mail" :size="14" /> Envoyer
                        </button>
                    </div>
                </form>
            </div>

            {{-- Visio --}}
            <div class="ukc-card" x-show="tab==='visio'" x-cloak>
                <h2>Visio</h2>
                <p style="font-size:12.5px;color:var(--ukc-muted);margin:0 0 12px;">
                    Les formateurs rejoignent en modérateur ; les inscrits ayant réglé en participant.
                </p>
                <form method="POST" action="{{ route('admin.formations.meeting', $f) }}">
                    @csrf
                    <div style="display:grid;grid-template-columns:minmax(0,1fr) minmax(0,1fr);gap:10px;">
                        <div class="ukc-form-row">
                            <label>Fournisseur</label>
                            <select name="meeting_provider">
                                <option value="">Par défaut</option>
                                <option value="livekit" @selected($f->meeting_provider === 'livekit')>LiveKit</option>
                                <option value="jitsi" @selected($f->meeting_provider === 'jitsi')>Jitsi</option>
                                <option value="custom" @selected($f->meeting_provider === 'custom')>Lien externe</option>
                            </select>
                        </div>
                        <div class="ukc-form-row">
                            <label>État</label>
                            <select name="meeting_status">
                                @foreach (['none' => 'Aucune', 'scheduled' => 'Programmée', 'live' => 'En direct', 'ended' => 'Terminée'] as $sv => $sl)
                                    <option value="{{ $sv }}" @selected(($f->meeting_status ?: 'none') === $sv)>{{ $sl }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="ukc-form-row">
                        <label>Nom de salle <span class="hint">(auto si vide)</span></label>
                        <input type="text" name="meeting_room" value="{{ $f->meeting_room }}">
                    </div>
                    <div class="ukc-form-row">
                        <label>Lien externe <span class="hint">(fournisseur « Lien externe »)</span></label>
                        <input type="url" name="meeting_url" value="{{ $f->meeting_url }}">
                    </div>
                    <div style="display:flex;gap:10px;align-items:center;flex-wrap:wrap;">
                        <select name="meeting_visibility" style="{{ $inp }}">
                            <option value="participants" @selected(($f->meeting_visibility ?: 'participants') === 'participants')>Réservée aux inscrits</option>
                            <option value="public" @selected($f->meeting_visibility === 'public')>Publique</option>
                        </select>
                        <button type="submit" class="ukc-btn ukc-btn--blue ukc-btn--sm"><x-ukc.icon name="video" :size="14" /> Enregistrer</button>
                        @if ($f->isLive())
                            <a href="{{ route('account.formations.visio', $f) }}" target="_blank" rel="noopener" class="ukc-btn ukc-btn--green ukc-btn--sm">
                                <x-ukc.icon name="external" :size="14" /> Rejoindre
                            </a>
                        @endif
                    </div>
                </form>
            </div>
        </div>

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
</x-layouts.admin>
