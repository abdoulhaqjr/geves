<x-layouts.admin title="E-mails" subtitle="Campagnes programmées & modèles">
    @php
        $edLabel = optional($editions->firstWhere('id', (int) $edition_id))->title;
        $campStatus = [
            'queued' => ['Programmé', 'warn'],
            'sent' => ['Envoyé', 'ok'],
            'partial' => ['Échec partiel', 'err'],
            'failed' => ['Échec', 'err'],
            'cancelled' => ['Annulé', 'muted'],
        ];
    @endphp

    <div class="admin-page admin-emails" x-data="{
            tab: 'scheduled', pv: null, sched: false, tpv: null, tedit: null, log: null, tnew: false,
            mergeTags: {{ \Illuminate\Support\Js::from(\App\Support\EmailTemplates::MERGE_TAGS) }},
            triggerOptions: {{ \Illuminate\Support\Js::from(\App\Support\EmailTemplates::TRIGGER_OPTIONS) }},
            insertTag(t) {
                const el = this.$refs.tbody;
                if (! el) { this.tedit.body += t; return; }
                const s = el.selectionStart, e = el.selectionEnd;
                this.tedit.body = this.tedit.body.slice(0, s) + t + this.tedit.body.slice(e);
                this.$nextTick(() => { el.focus(); el.selectionStart = el.selectionEnd = s + t.length; });
            }
         }"
         @open-schedule.window="sched = true"
         @keydown.escape.window="pv = null; sched = false; tpv = null; tedit = null; log = null; tnew = false">

        <x-slot:actions>
            @if ($queuedDue > 0)
                <form method="POST" action="{{ route('admin.emails.send-due') }}" style="display:inline;"
                      data-ajax data-ajax-target="emails" data-ajax-msg="Envoi des e-mails à échéance lancé."
                      data-confirm="Envoyer maintenant les {{ $queuedDue }} e-mail(s) arrivés à échéance ?">
                    @csrf
                    <button type="submit" class="ukc-btn ukc-btn--outline ukc-btn--sm"><x-ukc.icon name="clock" :size="14" /> Envoyer les dus ({{ $queuedDue }})</button>
                </form>
            @endif
            <button type="button" class="ukc-btn ukc-btn--blue ukc-btn--sm" onclick="window.dispatchEvent(new CustomEvent('open-schedule'))"><x-ukc.icon name="plus" :size="16" /> Programmer un envoi</button>
        </x-slot:actions>

        {{-- Onglets --}}
        <div class="ukc-nav-docs">
            <button type="button" @click="tab='scheduled'" :class="{ active: tab==='scheduled' }">E-mails programmés</button>
            <button type="button" @click="tab='templates'" :class="{ active: tab==='templates' }">Modèles e-mails</button>
        </div>

        {{-- KPI --}}
        <div class="ukc-stats">
            <div class="ukc-stat-card tw-stat"><div class="tw-stat__icon tw-stat__icon--blue"><x-ukc.icon name="mail" :size="20" /></div><div class="lbl">Total</div><div class="val blue">{{ $stats['total'] }}</div></div>
            <div class="ukc-stat-card tw-stat"><div class="tw-stat__icon tw-stat__icon--amber"><x-ukc.icon name="clock" :size="20" /></div><div class="lbl">En file</div><div class="val warn">{{ $stats['queued'] }}</div></div>
            <div class="ukc-stat-card tw-stat"><div class="tw-stat__icon tw-stat__icon--green"><x-ukc.icon name="check-circle" :size="20" /></div><div class="lbl">Envoyés</div><div class="val green">{{ $stats['sent'] }}</div></div>
            <div class="ukc-stat-card tw-stat"><div class="tw-stat__icon tw-stat__icon--red"><x-ukc.icon name="close" :size="20" /></div><div class="lbl">Échecs</div><div class="val" style="color:#dc2626;">{{ $stats['failed'] }}</div></div>
        </div>

        {{-- ===================== PROGRAMMÉS ===================== --}}
        <div x-show="tab==='scheduled'">
            <div class="filter-bar">
                <select onchange="window.location='{{ route('admin.emails.index') }}?edition_id='+this.value+'&status={{ $status }}'">
                    <option value="">— Édition —</option>
                    @foreach ($editions as $ed)<option value="{{ $ed->id }}" @selected((string) $ed->id === $edition_id)>{{ $ed->title ?: $ed->year }}</option>@endforeach
                </select>
                <select onchange="window.location='{{ route('admin.emails.index') }}?edition_id={{ $edition_id }}&status='+this.value">
                    <option value="">Tous les statuts</option>
                    <option value="queued" @selected($status === 'queued')>Programmé</option>
                    <option value="sent" @selected($status === 'sent')>Envoyé</option>
                    <option value="failed" @selected($status === 'failed')>Échec</option>
                    <option value="cancelled" @selected($status === 'cancelled')>Annulé</option>
                </select>
            </div>

            <div class="ukc-card" style="padding:0;" data-ajax-root="emails">
                <div class="ukc-table-wrap">
                    <table class="ukc-table">
                        <thead>
                            <tr>
                                <x-ukc.th sort="label" :current="$sort" :dir="$dir">Campagne</x-ukc.th>
                                <x-ukc.th sort="audience" :current="$sort" :dir="$dir">Audience</x-ukc.th>
                                <x-ukc.th sort="scheduled_at" :current="$sort" :dir="$dir">Programmé le</x-ukc.th>
                                <x-ukc.th sort="total" :current="$sort" :dir="$dir">Destinataires</x-ukc.th>
                                <x-ukc.th sort="status" :current="$sort" :dir="$dir">Statut</x-ukc.th>
                                <th style="text-align:right;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($campaigns as $c)
                                @php([$sLabel, $sPill] = $campStatus[$c['status']] ?? ['—', 'muted'])
                                <tr>
                                    <td>
                                        <strong>{{ \Illuminate\Support\Str::limit($c['label'], 60) }}</strong>
                                        <div style="font-size:12px;color:var(--ukc-muted);">{{ $c['is_campaign'] ? 'Campagne ciblée' : 'Auto · '.\App\Support\EmailTemplates::label($c['template']) }}</div>
                                    </td>
                                    <td>{{ $c['audience'] }}</td>
                                    <td style="white-space:nowrap;">{{ $c['scheduled_at']?->format('d/m/Y H:i') ?? '—' }}</td>
                                    <td><span class="ukc-stat" style="font-weight:700;">{{ $c['sent'] ?: $c['total'] }}</span>@if ($c['sent'] && $c['sent'] < $c['total']) / {{ $c['total'] }}@endif</td>
                                    <td><span class="ukc-pill ukc-pill--{{ $sPill }}">{{ $sLabel }}</span></td>
                                    <td>
                                        <div class="ukc-actions" style="justify-content:flex-end;">
                                            <button type="button" class="ukc-btn--icon" title="Aperçu"
                                                    @click="pv = {{ \Illuminate\Support\Js::from([
                                                        'label' => $c['label'], 'audience' => $c['audience'],
                                                        'scheduled' => $c['scheduled_at']?->format('d/m/Y H:i') ?? '—',
                                                        'total' => $c['total'], 'sent' => $c['sent'], 'status' => $sLabel,
                                                        'auto' => ! $c['is_campaign'],
                                                    ]) }}"><x-ukc.icon name="eye" :size="16" /></button>
                                            <button type="button" class="ukc-btn--icon" title="Journal d’envoi"
                                                    @click="log = {{ \Illuminate\Support\Js::from([
                                                        'label' => $c['label'], 'total' => $c['total'], 'rows' => $c['log'],
                                                    ]) }}"><x-ukc.icon name="list" :size="16" /></button>
                                            @if (in_array($c['status'], ['failed', 'partial'], true))
                                                <form method="POST" action="{{ route('admin.emails.retry-batch') }}" data-ajax data-ajax-target="emails" data-ajax-msg="Relance des échecs lancée." data-confirm="Relancer les e-mails en échec de cette campagne ?">
                                                    @csrf
                                                    <input type="hidden" name="batch_id" value="{{ $c['batch_id'] }}">
                                                    <input type="hidden" name="template" value="{{ $c['template'] }}">
                                                    <input type="hidden" name="edition_id" value="{{ $edition_id }}">
                                                    <button type="submit" class="ukc-btn--icon" title="Relancer les échecs"><x-ukc.icon name="refresh" :size="16" /></button>
                                                </form>
                                            @endif
                                            @if ($c['is_campaign'] && $c['status'] === 'queued')
                                                <form method="POST" action="{{ route('admin.emails.cancel-batch') }}" data-ajax data-ajax-target="emails" data-ajax-msg="Campagne annulée." data-confirm="Annuler toute cette campagne programmée ?">
                                                    @csrf
                                                    <input type="hidden" name="batch_id" value="{{ $c['batch_id'] }}">
                                                    <button type="submit" class="ukc-btn--icon" title="Annuler" style="color:#dc2626;"><x-ukc.icon name="trash" :size="16" /></button>
                                                </form>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="6" style="padding:24px;text-align:center;color:var(--ukc-muted);">Aucune campagne. Cliquez « Programmer un envoi » pour composer une campagne.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            <p class="ukc-hint" style="margin-top:12px;font-size:12px;color:var(--ukc-light);">
                Les envois « auto » (soumission, paiement, décision) sont déclenchés par événements. Les campagnes « programmées » partent à la date/heure définie.
            </p>
        </div>

        {{-- ===================== MODÈLES ===================== --}}
        <div x-show="tab==='templates'" x-cloak>
            <div class="ukc-toolbar" style="margin-bottom:12px;">
                <button type="button" class="ukc-btn ukc-btn--blue ukc-btn--sm" @click="tnew = true"><x-ukc.icon name="plus" :size="16" /> Nouveau modèle</button>
                <span class="sep"></span>
                <span style="font-size:13px;color:var(--ukc-muted);">Balises de fusion · objet &amp; corps · déclencheurs</span>
            </div>
            <div class="ukc-card" style="padding:0;">
                <div class="ukc-table-wrap">
                    <table class="ukc-table">
                        <thead><tr><th>Modèle</th><th>Déclencheur</th><th>Objet</th><th>Statut</th><th style="text-align:right;">Actions</th></tr></thead>
                        <tbody>
                            @foreach ($templates as $t)
                                <tr>
                                    <td>
                                        <strong>{{ $t['label'] }}</strong>
                                        @if ($t['custom'])<span class="ukc-pill ukc-pill--info" style="margin-left:6px;font-size:10px;">Perso</span>@endif
                                        <div style="font-size:12px;color:var(--ukc-muted);font-family:ui-monospace,monospace;">{{ $t['key'] }}</div>
                                    </td>
                                    <td>{{ $t['trigger'] }}</td>
                                    <td><div style="color:var(--ukc-muted);font-size:13px;">{{ \Illuminate\Support\Str::limit($t['subject'], 52) }}</div></td>
                                    <td>
                                        @if ($t['active'])
                                            <span class="ukc-pill ukc-pill--ok">Actif</span>
                                        @else
                                            <span class="ukc-pill ukc-pill--muted">Inactif</span>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="ukc-actions" style="justify-content:flex-end;">
                                            <button type="button" class="ukc-btn--icon" title="Éditer le modèle"
                                                    @click="tedit = {{ \Illuminate\Support\Js::from([
                                                        'key' => $t['key'], 'label' => $t['label'], 'trigger' => $t['trigger'],
                                                        'subject' => $t['subject'], 'body' => $t['body'], 'active' => $t['active'],
                                                    ]) }}"><x-ukc.icon name="pen" :size="16" /></button>
                                            <button type="button" class="ukc-btn--icon" title="Aperçu"
                                                    @click="tpv = {{ \Illuminate\Support\Js::from([
                                                        'subject' => $t['subject'], 'body' => $t['body'],
                                                    ]) }}"><x-ukc.icon name="eye" :size="16" /></button>
                                            <form method="POST" action="{{ route('admin.emails.templates.store') }}" style="display:inline;">
                                                @csrf
                                                <input type="hidden" name="label" value="Copie de {{ \Illuminate\Support\Str::limit($t['label'], 100, '') }}">
                                                <input type="hidden" name="trigger" value="{{ in_array($t['trigger'], \App\Support\EmailTemplates::TRIGGER_OPTIONS, true) ? $t['trigger'] : \App\Support\EmailTemplates::TRIGGER_OPTIONS[0] }}">
                                                <input type="hidden" name="subject" value="{{ $t['subject'] }}">
                                                <input type="hidden" name="body" value="{{ $t['body'] }}">
                                                <input type="hidden" name="active" value="0">
                                                <button type="submit" class="ukc-btn--icon" title="Dupliquer"><x-ukc.icon name="layers" :size="16" /></button>
                                            </form>
                                            @if ($t['custom'])
                                                <form method="POST" action="{{ route('admin.emails.templates.destroy', $t['key']) }}" onsubmit="return confirm('Supprimer ce modèle personnalisé ?')" style="display:inline;">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="ukc-btn--icon" title="Supprimer" style="color:#dc2626;"><x-ukc.icon name="trash" :size="16" /></button>
                                                </form>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- ===== Modale : Programmer un envoi (fidèle au mockup) ===== --}}
        <div class="ukc-modal" x-show="sched" x-cloak @click.self="sched = false" style="display:none;">
            <div class="box narrow" x-data="{ audience: '{{ old('audience', 'all') }}', counts: @js($recipientCounts) }">
                <div class="box-head">
                    <span>Programmer un envoi</span>
                    <button type="button" @click="sched = false" style="font-size:22px;color:var(--ukc-muted);background:none;border:none;cursor:pointer;">&times;</button>
                </div>
                <div class="box-body">
                    <form method="POST" action="{{ route('admin.emails.send') }}">
                        @csrf
                        <input type="hidden" name="edition_id" value="{{ $edition_id }}">
                        <div class="ukc-form-row">
                            <label>Modèle e-mail</label>
                            <select name="template">
                                @foreach ($templates as $t)@if ($t['active'])<option value="{{ $t['key'] }}" @selected(old('template') === $t['key'])>{{ $t['label'] }}</option>@endif @endforeach
                            </select>
                            @error('template')<span class="error">{{ $message }}</span>@enderror
                        </div>
                        <div class="ukc-form-row">
                            <label>Audience</label>
                            <select name="audience" x-model="audience">
                                @foreach ($audiences as $key => $label)<option value="{{ $key }}">{{ $label }}</option>@endforeach
                            </select>
                            <p class="ukc-hint"><x-ukc.icon name="users" :size="13" /> <span x-text="counts[audience] ?? 0"></span> destinataire(s) pour l’édition sélectionnée.</p>
                        </div>
                        <div class="ukc-form-grid">
                            <div class="ukc-form-row">
                                <label>Date</label>
                                <input type="date" name="date" value="{{ old('date') }}">
                                @error('date')<span class="error">{{ $message }}</span>@enderror
                            </div>
                            <div class="ukc-form-row">
                                <label>Heure</label>
                                <input type="time" name="heure" value="{{ old('heure', '09:00') }}">
                                @error('heure')<span class="error">{{ $message }}</span>@enderror
                            </div>
                        </div>
                        <label class="ukc-check"><input type="checkbox" name="test" value="1"> Envoyer un e-mail test à l’admin d’abord</label>
                        <p class="ukc-hint" style="margin-top:0;">Laisser la date vide envoie immédiatement.</p>
                        <div class="box-foot">
                            <button type="button" class="ukc-btn ukc-btn--ghost" @click="sched = false">Annuler</button>
                            <button type="submit" class="ukc-btn ukc-btn--blue">Programmer</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        {{-- ===== Modale : Éditer le modèle (fidèle au mockup) ===== --}}
        <div class="ukc-modal" x-show="tedit" x-cloak @click.self="tedit = null" style="display:none;">
            <div class="box wide">
                <div class="box-head">
                    <span>Éditer le modèle</span>
                    <button type="button" @click="tedit = null" style="font-size:22px;color:var(--ukc-muted);background:none;border:none;cursor:pointer;">&times;</button>
                </div>
                <div class="box-body">
                    <form method="POST" action="{{ route('admin.emails.templates.update') }}">
                        @csrf
                        @method('PUT')
                        <template x-if="tedit">
                            <div>
                                <div class="ukc-form-grid">
                                    <div class="ukc-form-row">
                                        <label>Nom interne</label>
                                        <input type="text" x-model="tedit.label" readonly>
                                    </div>
                                    <div class="ukc-form-row">
                                        <label>Déclencheur</label>
                                        <select x-model="tedit.trigger">
                                            <template x-for="tr in triggerOptions" :key="tr"><option :value="tr" x-text="tr"></option></template>
                                            <option x-show="!triggerOptions.includes(tedit.trigger)" :value="tedit.trigger" x-text="tedit.trigger"></option>
                                        </select>
                                    </div>
                                </div>
                                <div class="ukc-form-row">
                                    <label>Objet du message</label>
                                    <input type="text" :name="'templates['+tedit.key+'][subject]'" x-model="tedit.subject" maxlength="200">
                                </div>
                                <div class="ukc-form-row">
                                    <label>Corps du message</label>
                                    <textarea x-ref="tbody" :name="'templates['+tedit.key+'][body]'" x-model="tedit.body" rows="7" maxlength="5000"></textarea>
                                    <div class="merge-tags">
                                        <template x-for="tag in mergeTags" :key="tag">
                                            <button type="button" @click="insertTag(tag)" x-text="tag"></button>
                                        </template>
                                    </div>
                                    <p class="ukc-hint">Cliquez une balise pour l’insérer. Laisser le corps vide restaure le texte par défaut.</p>
                                </div>
                                <input type="hidden" :name="'templates['+tedit.key+'][active]'" value="0">
                                <label class="ukc-check"><input type="checkbox" :name="'templates['+tedit.key+'][active]'" value="1" x-model="tedit.active"> Modèle actif</label>
                            </div>
                        </template>
                        <div class="box-foot">
                            <button type="button" class="ukc-btn ukc-btn--ghost" @click="tpv = { subject: tedit.subject, body: tedit.body }">Aperçu</button>
                            <button type="button" class="ukc-btn ukc-btn--ghost" @click="tedit = null">Annuler</button>
                            <button type="submit" class="ukc-btn ukc-btn--blue">Enregistrer</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        {{-- ===== Modale : Nouveau modèle (création) ===== --}}
        <div class="ukc-modal" x-show="tnew" x-cloak @click.self="tnew = false" style="display:none;">
            <div class="box wide" x-data="{
                    f: { label: '', trigger: triggerOptions[0], subject: '', body: '', active: true },
                    ins(t) {
                        const el = $refs.nbody;
                        const s = el.selectionStart, e = el.selectionEnd;
                        this.f.body = this.f.body.slice(0, s) + t + this.f.body.slice(e);
                        this.$nextTick(() => { el.focus(); el.selectionStart = el.selectionEnd = s + t.length; });
                    }
                 }">
                <div class="box-head">
                    <span>Nouveau modèle</span>
                    <button type="button" @click="tnew = false" style="font-size:22px;color:var(--ukc-muted);background:none;border:none;cursor:pointer;">&times;</button>
                </div>
                <div class="box-body">
                    <form method="POST" action="{{ route('admin.emails.templates.store') }}">
                        @csrf
                        <div class="ukc-form-grid">
                            <div class="ukc-form-row">
                                <label>Nom interne</label>
                                <input type="text" name="label" x-model="f.label" maxlength="120" required placeholder="Ex. Rappel J−7">
                                @error('label')<span class="error">{{ $message }}</span>@enderror
                            </div>
                            <div class="ukc-form-row">
                                <label>Déclencheur</label>
                                <select name="trigger" x-model="f.trigger">
                                    <template x-for="tr in triggerOptions" :key="tr"><option :value="tr" x-text="tr"></option></template>
                                </select>
                            </div>
                        </div>
                        <div class="ukc-form-row">
                            <label>Objet du message</label>
                            <input type="text" name="subject" x-model="f.subject" maxlength="200">
                        </div>
                        <div class="ukc-form-row">
                            <label>Corps du message</label>
                            <textarea x-ref="nbody" name="body" x-model="f.body" rows="7" maxlength="5000"></textarea>
                            <div class="merge-tags">
                                <template x-for="tag in mergeTags" :key="tag">
                                    <button type="button" @click="ins(tag)" x-text="tag"></button>
                                </template>
                            </div>
                            <p class="ukc-hint">Cliquez une balise pour l’insérer dans le corps du message.</p>
                        </div>
                        <input type="hidden" name="active" value="0">
                        <label class="ukc-check"><input type="checkbox" name="active" value="1" x-model="f.active"> Modèle actif</label>
                        <div class="box-foot">
                            <button type="button" class="ukc-btn ukc-btn--ghost" @click="tpv = { subject: f.subject, body: f.body }">Aperçu</button>
                            <button type="button" class="ukc-btn ukc-btn--ghost" @click="tnew = false">Annuler</button>
                            <button type="submit" class="ukc-btn ukc-btn--blue">Créer</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        {{-- ===== Modale : Aperçu e-mail (fidèle au mockup) ===== --}}
        <div class="ukc-modal" x-show="tpv" x-cloak @click.self="tpv = null" style="display:none;">
            <div class="box narrow">
                <div class="box-head">
                    <span>Aperçu e-mail</span>
                    <button type="button" @click="tpv = null" style="font-size:22px;color:var(--ukc-muted);background:none;border:none;cursor:pointer;">&times;</button>
                </div>
                <div class="box-body">
                    <div class="mail-preview">
                        <div class="subj" x-text="tpv?.subject || 'Objet'"></div>
                        <div class="body" x-text="tpv?.body || 'Corps'"></div>
                    </div>
                    <div class="box-foot">
                        <button type="button" class="ukc-btn ukc-btn--blue" @click="tpv = null">Fermer</button>
                    </div>
                </div>
            </div>
        </div>

        {{-- ===== Modale : Aperçu d'une campagne ===== --}}
        <div class="ukc-modal" x-show="pv" x-cloak @click.self="pv = null" style="display:none;">
            <div class="box narrow">
                <div class="box-head">
                    <span>Aperçu de la campagne</span>
                    <button type="button" @click="pv = null" style="font-size:22px;color:var(--ukc-muted);background:none;border:none;cursor:pointer;">&times;</button>
                </div>
                <div class="box-body">
                    <div class="detail-grid">
                        <div class="k">Objet</div><div class="v" x-text="pv?.label"></div>
                        <div class="k">Audience</div><div class="v" x-text="pv?.audience"></div>
                        <div class="k">Programmé le</div><div class="v" x-text="pv?.scheduled"></div>
                        <div class="k">Destinataires</div><div class="v" x-text="pv?.total"></div>
                        <div class="k">Envoyés</div><div class="v" x-text="pv?.sent"></div>
                        <div class="k">Statut</div><div class="v" x-text="pv?.status"></div>
                    </div>
                    <p class="ukc-hint" x-show="pv?.auto">Envoi automatique déclenché par un événement (non programmable).</p>
                    <div class="box-foot">
                        <button type="button" class="ukc-btn ukc-btn--blue" @click="pv = null">Fermer</button>
                    </div>
                </div>
            </div>
        </div>

        {{-- ===== Modale : Journal d'envoi (par destinataire) ===== --}}
        <div class="ukc-modal" x-show="log" x-cloak @click.self="log = null" style="display:none;">
            <div class="box">
                <div class="box-head">
                    <span>Journal d’envoi</span>
                    <button type="button" @click="log = null" style="font-size:22px;color:var(--ukc-muted);background:none;border:none;cursor:pointer;">&times;</button>
                </div>
                <div class="box-body">
                    <p style="font-size:13px;color:var(--ukc-muted);margin-bottom:12px;">
                        <strong x-text="log?.label"></strong> — <span x-text="log?.total"></span> destinataire(s)
                        <template x-if="log && log.total > log.rows.length"><span> · <span x-text="log.rows.length"></span> premiers affichés</span></template>
                    </p>
                    <div class="log-wrap">
                        <table class="log-table">
                            <thead><tr><th>Destinataire</th><th>Statut</th><th>Date</th></tr></thead>
                            <tbody>
                                <template x-for="(row, i) in (log?.rows || [])" :key="i">
                                    <tr>
                                        <td x-text="row.to"></td>
                                        <td>
                                            <span class="ukc-pill" :class="{
                                                'ukc-pill--ok': row.status==='sent',
                                                'ukc-pill--warn': row.status==='queued',
                                                'ukc-pill--err': row.status==='failed',
                                                'ukc-pill--muted': row.status==='cancelled',
                                            }" x-text="{sent:'Envoyé',queued:'En file',failed:'Échec',cancelled:'Annulé'}[row.status] || row.status"></span>
                                        </td>
                                        <td x-text="row.at"></td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>
                    <div class="box-foot">
                        <button type="button" class="ukc-btn ukc-btn--blue" @click="log = null">Fermer</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-layouts.admin>
