@php($v = fn ($k) => old($k, $init[$k]))
<x-layouts.admin :title="$edition ? 'Modifier l’édition' : 'Nouvelle édition'" subtitle="Paramètres, programme, contenu, tarifs, apparence & documents">
    <div class="admin-page admin-edition-detail"
         x-data="editionForm({
            tab: '{{ session('edition_tab', request('tab', 'general')) }}',
            aiUrl: '{{ route('admin.editions.ai-assist-json') }}',
            csrf: @js(csrf_token()),
            f: {
                title: @js($v('title')), edition_label: @js($v('edition_label')), subtitle: @js($v('subtitle')),
                theme: @js($v('theme')), year: @js($v('year')), date_start: @js($v('date_start')), date_end: @js($v('date_end')),
                submission_deadline: @js($v('submission_deadline')), location: @js($v('location')), slug: @js($v('slug')),
                status: @js($v('status')), intro: @js($v('intro')), short_name: @js($v('short_name')), short_desc: @js($v('short_desc')),
                format: @js($v('format')), main_language: @js($v('main_language')), languages: @js(old('languages', $languages)),
                submission_open: @js((bool) $v('submission_open')),
            },
            arrays: @js(['fees' => $fees, 'partners' => $partners, 'faq' => $faq, 'comOrg' => $comOrg, 'comSci' => $comSci, 'comSup' => $comSup, 'keyDates' => $keyDates, 'programme' => $programme, 'axes' => $axes, 'why' => $why, 'infoCards' => $infoCards, 'stats' => $stats])
         })">

        {{-- ===== En-tête ===== --}}
        <header class="ukc-admin-head" style="background:transparent;border:none;padding:0;margin-bottom:16px;flex-wrap:nowrap;align-items:flex-start;">
            <div style="flex:1;min-width:0;">
                <div style="font-size:12px;margin-bottom:4px;color:var(--ukc-light);">
                    <a href="{{ route('admin.editions.index') }}" style="color:var(--ukc-blue);font-weight:600;">Éditions</a>
                    <span> / </span><span style="color:var(--ukc-muted);">{{ $edition ? 'Modifier' : 'Nouvelle' }}</span>
                    <span> · </span><code x-text="f.year || '-'"></code>
                </div>
                <h1 style="font-size:1.2rem;font-weight:800;" x-text="f.title || '{{ $edition ? 'Édition' : 'Nouvelle édition' }}'"></h1>
                <div class="sub" style="font-size:13px;color:var(--ukc-muted);margin-top:4px;">
                    <span class="ukc-pill" :class="statusLive() ? 'ukc-pill--live' : 'ukc-pill--muted'" x-text="statusLabel()"></span>
                    &nbsp; Paramétrage complet · assisté IA
                </div>
            </div>
            <div style="display:flex;gap:8px;flex-wrap:wrap;justify-content:flex-end;flex-shrink:0;" x-data="{}">
                <a class="ukc-btn ukc-btn--ghost ukc-btn--sm" href="{{ route('admin.editions.index') }}">← Liste</a>
                @if ($edition)
                    {{-- Importer la configuration dans cette édition (fichier JSON) --}}
                    <form method="POST" action="{{ route('admin.editions.import', $edition) }}" enctype="multipart/form-data" x-ref="importForm" style="display:none;">
                        @csrf
                        <input type="file" name="file" accept=".json,application/json" x-ref="importFile" @change="$refs.importForm.submit()">
                    </form>
                    <button type="button" class="ukc-btn ukc-btn--ghost ukc-btn--sm" @click="$refs.importFile.click()" title="Importer une configuration (JSON) dans cette édition">
                        <x-ukc.icon name="upload" :size="15" /> Importer
                    </button>
                    <a class="ukc-btn ukc-btn--ghost ukc-btn--sm" href="{{ route('admin.editions.export', $edition) }}" title="Exporter cette édition (JSON)">
                        <x-ukc.icon name="download" :size="15" /> Exporter
                    </a>
                @endif
                <button type="submit" form="editionForm" class="ukc-btn ukc-btn--blue ukc-btn--sm">
                    <x-ukc.icon name="check" :size="16" /> Enregistrer
                </button>
            </div>
        </header>

        <div class="layout">
            {{-- ===================== COLONNE PRINCIPALE ===================== --}}
            <div>
                <div class="ukc-tabs">
                    <button type="button" @click="tab='general'" :class="{ active: tab==='general' }">Général</button>
                    <button type="button" @click="tab='content'" :class="{ active: tab==='content' }">Appel &amp; contenu</button>
                    <button type="button" @click="tab='submissions'" :class="{ active: tab==='submissions' }">Soumissions &amp; tarifs</button>
                    <button type="button" @click="tab='committees'" :class="{ active: tab==='committees' }">Comités</button>
                    @if ($edition)<button type="button" @click="tab='payment'" :class="{ active: tab==='payment' }">Paiement</button>@endif
                    @if ($edition)<button type="button" @click="tab='team'" :class="{ active: tab==='team' }">Équipe</button>@endif
                    <button type="button" @click="tab='partners'" :class="{ active: tab==='partners' }">Partenaires</button>
                    <button type="button" @click="tab='appearance'" :class="{ active: tab==='appearance' }">Apparence</button>
                    @if ($edition)<button type="button" @click="tab='translations'" :class="{ active: tab==='translations' }">Traductions</button>@endif
                    @if ($edition)<button type="button" @click="tab='options'" :class="{ active: tab==='options' }">Options</button>@endif
                </div>

                <form id="editionForm" method="POST" action="{{ $edition ? route('admin.editions.update', $edition) : route('admin.editions.store') }}">
                    @csrf
                    @if ($edition) @method('PUT') @endif
                    <input type="hidden" name="active_tab" :value="tab">

                    {{-- ===== GÉNÉRAL ===== --}}
                    <div class="ukc-panel" :class="{ active: tab==='general' }">
                        <div class="ukc-card">
                            <h2>Colloque de rattachement</h2>
                            <div class="ukc-form-grid">
                                <div class="ukc-form-row">
                                    <label for="colloque_id">Colloque existant</label>
                                    <select id="colloque_id" name="colloque_id">
                                        <option value="">- Choisir -</option>
                                        @foreach ($colloques as $c)
                                            <option value="{{ $c->id }}" @selected((int) $v('colloque_id') === $c->id)>{{ $c->name }}</option>
                                        @endforeach
                                    </select>
                                    @error('colloque_id') <span class="error">{{ $message }}</span> @enderror
                                </div>
                                <div class="ukc-form-row">
                                    <label for="new_colloque">…ou nouveau colloque</label>
                                    <input type="text" id="new_colloque" name="new_colloque" value="{{ old('new_colloque') }}" placeholder="Nom de la nouvelle série">
                                    <p class="ukc-hint">Renseignez ceci uniquement pour créer une nouvelle série.</p>
                                </div>
                            </div>
                        </div>

                        <div class="ukc-card">
                            <h2>Informations générales</h2>
                            <div class="ukc-form-grid">
                                <div class="ukc-form-row">
                                    <label for="title">Nom de l’édition <span class="req">*</span></label>
                                    <input type="text" id="title" name="title" x-model="f.title" placeholder="Colloque international de l’Université de Kara - 2026">
                                    @error('title') <span class="error">{{ $message }}</span> @enderror
                                </div>
                                <div class="ukc-form-row">
                                    <label for="year">Année</label>
                                    <input type="number" id="year" name="year" x-model="f.year" min="2000" max="2100">
                                    @error('year') <span class="error">{{ $message }}</span> @enderror
                                </div>
                            </div>
                            <div class="ukc-form-grid">
                                <div class="ukc-form-row">
                                    <label for="edition_label">Libellé d’édition</label>
                                    <input type="text" id="edition_label" name="edition_label" x-model="f.edition_label" placeholder="3e édition">
                                </div>
                                <div class="ukc-form-row">
                                    <label for="short_name">Nom court <span class="ukc-hint" style="display:inline;">(en-têtes, badges)</span></label>
                                    <input type="text" id="short_name" name="short_name" x-model="f.short_name" placeholder="CSIUK 2026">
                                </div>
                            </div>
                            <div class="ukc-form-row">
                                <label for="subtitle">Accroche / sous-titre</label>
                                <input type="text" id="subtitle" name="subtitle" x-model="f.subtitle" placeholder="Université de Kara · en collaboration avec…">
                            </div>
                            <div class="ukc-form-row">
                                <label for="theme">Thème</label>
                                <input type="text" id="theme" name="theme" x-model="f.theme" placeholder="Formation, recherche et innovation face aux défis contemporains">
                            </div>
                            <div class="ukc-form-row">
                                <label for="short_desc">Description courte</label>
                                <textarea id="short_desc" name="short_desc" rows="2" x-model="f.short_desc" placeholder="Résumé d’une ou deux phrases affiché en aperçu."></textarea>
                            </div>
                            <div class="ukc-form-grid">
                                <div class="ukc-form-row">
                                    <label for="location">Lieu</label>
                                    <input type="text" id="location" name="location" x-model="f.location" placeholder="Université de Kara - Campus Sud, Kara, Togo">
                                </div>
                                <div class="ukc-form-row">
                                    <label for="format">Format</label>
                                    <select id="format" name="format" x-model="f.format">
                                        <option>Présentiel</option>
                                        <option>Présentiel &amp; hybride</option>
                                        <option>En ligne</option>
                                    </select>
                                </div>
                                <div class="ukc-form-row">
                                    <label for="date_start">Date de début</label>
                                    <input type="date" id="date_start" name="date_start" x-model="f.date_start">
                                    @error('date_start') <span class="error">{{ $message }}</span> @enderror
                                </div>
                                <div class="ukc-form-row">
                                    <label for="date_end">Date de fin</label>
                                    <input type="date" id="date_end" name="date_end" x-model="f.date_end">
                                    @error('date_end') <span class="error">{{ $message }}</span> @enderror
                                </div>
                            </div>
                            <div class="ukc-form-grid">
                                <div class="ukc-form-row">
                                    <label for="slug">Slug URL</label>
                                    <input type="text" id="slug" name="slug" x-model="f.slug" placeholder="auto depuis le titre">
                                    <p class="ukc-hint">https://univkara.tg/colloque/<span x-text="f.slug || '…'"></span></p>
                                </div>
                                <div class="ukc-form-row">
                                    <label for="status">Statut</label>
                                    <select id="status" name="status" x-model="f.status">
                                        <option value="draft">Brouillon</option>
                                        <option value="open">Soumissions ouvertes</option>
                                        <option value="closed">Clôturée</option>
                                        <option value="archived">Archivée</option>
                                    </select>
                                    @error('status') <span class="error">{{ $message }}</span> @enderror
                                </div>
                            </div>

                            <h3>Langues</h3>
                            <div class="desc" style="margin-bottom:10px;">Langues de soumission et de communication acceptées.</div>
                            <div class="lang-chips">
                                @foreach (['Français', 'Anglais', 'Espagnol', 'Autre'] as $lang)
                                    <label><input type="checkbox" name="languages[]" value="{{ $lang }}" x-model="f.languages"> {{ $lang }}</label>
                                @endforeach
                            </div>
                            <div class="ukc-form-row" style="margin-top:12px;">
                                <label for="main_language">Langue principale de l’interface publique</label>
                                <select id="main_language" name="main_language" x-model="f.main_language">
                                    <option>Français</option>
                                    <option>Anglais</option>
                                </select>
                            </div>
                        </div>

                        <div class="ukc-card">
                            <h2>Dates clés</h2>
                            <div class="desc">Chronologie affichée sur la page du colloque (appel, soumission, notification, inscriptions, tenue…).</div>
                            <template x-for="(d, i) in keyDates" :key="i">
                                <div class="list-item">
                                    <div class="fields">
                                        <div class="row2">
                                            <input type="date" :name="'keyDates['+i+'][date]'" x-model="d.date">
                                            <input type="text" :name="'keyDates['+i+'][label]'" x-model="d.label" placeholder="Ex. Date limite de soumission">
                                        </div>
                                    </div>
                                    <button type="button" class="ukc-btn--icon" @click="removeKeyDate(i)" aria-label="Retirer">&times;</button>
                                </div>
                            </template>
                            <button type="button" class="ukc-btn ukc-btn--ghost ukc-btn--sm" @click="addKeyDate()">+ Ajouter une date clé</button>
                        </div>

                        <div class="ukc-card">
                            <h2>Programme</h2>
                            <div class="desc">Déroulé éditorial du colloque (jour, horaire, type, intitulé, intervenant, salle) affiché sur la page publique. La planification opérationnelle des créneaux se gère dans <a href="{{ $edition ? route('admin.program.index').'?edition_id='.$edition->id : route('admin.program.index') }}">le module Programme</a>.</div>
                            <div class="prog-head">
                                <span>Jour</span><span>Horaire</span><span>Type</span><span>Intitulé</span><span>Intervenant</span><span>Salle</span><span></span>
                            </div>
                            <template x-for="(p, i) in programme" :key="i">
                                <div class="prog-row">
                                    <input type="text" :name="'programme['+i+'][day]'" x-model="p.day" placeholder="Jour 1">
                                    <input type="text" :name="'programme['+i+'][time]'" x-model="p.time" placeholder="09:00–10:30">
                                    <select :name="'programme['+i+'][type]'" x-model="p.type">
                                        <option>Ouverture</option><option>Conférence</option><option>Session</option>
                                        <option>Pause</option><option>Atelier</option><option>Clôture</option>
                                    </select>
                                    <input type="text" :name="'programme['+i+'][title]'" x-model="p.title" placeholder="Intitulé">
                                    <input type="text" :name="'programme['+i+'][speaker]'" x-model="p.speaker" placeholder="Intervenant">
                                    <input type="text" :name="'programme['+i+'][room]'" x-model="p.room" placeholder="Salle">
                                    <button type="button" class="ukc-btn--icon" @click="removeProg(i)" aria-label="Retirer">&times;</button>
                                </div>
                            </template>
                            <button type="button" class="ukc-btn ukc-btn--ghost ukc-btn--sm" @click="addProg()" style="margin-top:10px;">+ Ajouter une ligne</button>
                        </div>
                    </div>

                    {{-- ===== APPEL & CONTENU ===== --}}
                    <div class="ukc-panel" :class="{ active: tab==='content' }">
                        <div class="ukc-card">
                            <h2>Axes thématiques</h2>
                            <div class="desc">Axes du formulaire de soumission, du programme et du livre des résumés.</div>
                            <template x-for="(a, i) in axes" :key="i">
                                <div class="list-item">
                                    <div class="fields"><input type="text" :name="'axes['+i+'][label]'" x-model="a.label" placeholder="Ex. Sciences de la santé"></div>
                                    <button type="button" class="ukc-btn--icon" @click="removeAxis(i)" aria-label="Retirer">&times;</button>
                                </div>
                            </template>
                            <button type="button" class="ukc-btn ukc-btn--ghost ukc-btn--sm" @click="addAxis()">+ Ajouter un axe</button>
                        </div>

                        <div class="ukc-card">
                            <h2>Argumentaire / présentation</h2>
                            <div class="desc">Texte d’introduction affiché sur la page publique du colloque.</div>
                            <div class="ukc-form-row"><textarea id="intro" name="intro" rows="5" x-model="f.intro"></textarea></div>
                        </div>

                        <div class="ukc-card">
                            <h2>Pourquoi soumettre ?</h2>
                            <div class="desc">Arguments affichés sur la page d’appel à communications.</div>
                            <template x-for="(w, i) in why" :key="i">
                                <div class="list-item">
                                    <div class="fields">
                                        <input type="text" :name="'why['+i+'][title]'" x-model="w.title" placeholder="Titre">
                                        <textarea :name="'why['+i+'][text]'" x-model="w.text" rows="2" placeholder="Description"></textarea>
                                    </div>
                                    <button type="button" class="ukc-btn--icon" @click="removeWhy(i)" aria-label="Retirer">&times;</button>
                                </div>
                            </template>
                            <button type="button" class="ukc-btn ukc-btn--ghost ukc-btn--sm" @click="addWhy()">+ Ajouter un argument</button>
                        </div>

                        <div class="ukc-card">
                            <h2>FAQ</h2>
                            <div class="desc">Questions fréquentes affichées sur la page publique.</div>
                            <template x-for="(q, i) in faq" :key="i">
                                <div class="list-item">
                                    <div class="fields">
                                        <input type="text" :name="'faq['+i+'][question]'" x-model="q.question" placeholder="Question">
                                        <textarea :name="'faq['+i+'][answer]'" x-model="q.answer" rows="2" placeholder="Réponse"></textarea>
                                    </div>
                                    <button type="button" class="ukc-btn--icon" @click="removeFaq(i)" aria-label="Retirer">&times;</button>
                                </div>
                            </template>
                            <button type="button" class="ukc-btn ukc-btn--ghost ukc-btn--sm" @click="addFaq()">+ Ajouter une FAQ</button>
                        </div>

                        <div class="ukc-card">
                            <h2>Infos pratiques (cartes)</h2>
                            <div class="desc">Cartes type « Lieu », « Transport », « Hébergement », « Contact »…</div>
                            <template x-for="(c, i) in infoCards" :key="i">
                                <div class="list-item">
                                    <div class="fields">
                                        <input type="text" :name="'infoCards['+i+'][title]'" x-model="c.title" placeholder="Titre de la carte">
                                        <textarea :name="'infoCards['+i+'][text]'" x-model="c.text" rows="2" placeholder="Contenu"></textarea>
                                    </div>
                                    <button type="button" class="ukc-btn--icon" @click="removeInfo(i)" aria-label="Retirer">&times;</button>
                                </div>
                            </template>
                            <button type="button" class="ukc-btn ukc-btn--ghost ukc-btn--sm" @click="addInfo()">+ Ajouter une carte</button>
                        </div>

                        <div class="ukc-card">
                            <h2>Contact &amp; réseaux sociaux</h2>
                            <div class="ukc-form-grid">
                                <div class="ukc-form-row">
                                    <label for="contact_email">E-mail de contact</label>
                                    <input type="email" id="contact_email" name="contact_email" value="{{ $v('contact_email') }}" placeholder="colloque@univkara.tg">
                                    @error('contact_email') <span class="error">{{ $message }}</span> @enderror
                                </div>
                                <div class="ukc-form-row">
                                    <label for="contact_phone">Téléphone</label>
                                    <x-ukc.phone name="contact_phone" :value="$v('contact_phone')" />
                                </div>
                            </div>
                            <div class="ukc-form-row">
                                <label for="contact_address">Adresse</label>
                                <input type="text" id="contact_address" name="contact_address" value="{{ $v('contact_address') }}" placeholder="Université de Kara, BP …">
                            </div>
                            <div class="desc" style="margin:6px 0 4px;">Réseaux sociaux - laissez vide pour utiliser ceux du site.</div>
                            <div class="ukc-form-grid">
                                <div class="ukc-form-row"><label for="social_facebook">Facebook</label><input type="url" id="social_facebook" name="social_facebook" value="{{ $v('social_facebook') }}" placeholder="https://facebook.com/…"></div>
                                <div class="ukc-form-row"><label for="social_x">X (Twitter)</label><input type="url" id="social_x" name="social_x" value="{{ $v('social_x') }}" placeholder="https://x.com/…"></div>
                                <div class="ukc-form-row"><label for="social_youtube">YouTube</label><input type="url" id="social_youtube" name="social_youtube" value="{{ $v('social_youtube') }}" placeholder="https://youtube.com/…"></div>
                                <div class="ukc-form-row"><label for="social_linkedin">LinkedIn</label><input type="url" id="social_linkedin" name="social_linkedin" value="{{ $v('social_linkedin') }}" placeholder="https://linkedin.com/…"></div>
                            </div>
                        </div>
                    </div>

                    {{-- ===== SOUMISSIONS & TARIFS ===== --}}
                    <div class="ukc-panel" :class="{ active: tab==='submissions' }">
                        <div class="ukc-card">
                            <h2>Soumissions</h2>
                            <label class="ukc-check"><input type="checkbox" name="submission_open" value="1" x-model="f.submission_open"> Ouvrir les soumissions de résumés</label>
                            <div class="ukc-form-grid" style="margin-top:8px;">
                                <div class="ukc-form-row">
                                    <label for="submission_deadline">Date limite de soumission</label>
                                    <input type="datetime-local" id="submission_deadline" name="submission_deadline" x-model="f.submission_deadline">
                                    @error('submission_deadline') <span class="error">{{ $message }}</span> @enderror
                                </div>
                                <div class="ukc-form-row">
                                    <label for="max_communications">Communications max. par personne</label>
                                    <input type="number" id="max_communications" name="max_communications" value="{{ $v('max_communications') }}" min="1" max="10">
                                    @error('max_communications') <span class="error">{{ $message }}</span> @enderror
                                </div>
                            </div>
                        </div>

                        <div class="ukc-card">
                            <h2>Préfixe des références</h2>
                            <div class="desc">Identifiants des soumissions (ex. UKC-S-0142).</div>
                            <div class="ukc-form-row">
                                <label for="ref_prefix_mode">Mode</label>
                                <select id="ref_prefix_mode" name="ref_prefix_mode" x-model="refMode">
                                    <option value="global">Préfixe global</option>
                                    <option value="axis">Préfixe par axe thématique</option>
                                </select>
                            </div>
                            <div class="ukc-form-row" x-show="refMode==='global'">
                                <label for="ref_prefix">Préfixe global</label>
                                <input type="text" id="ref_prefix" name="ref_prefix" value="{{ $v('ref_prefix') }}" placeholder="UKC-S-">
                            </div>
                            <div x-show="refMode==='axis'" style="display:none;">
                                <template x-for="(a, i) in axes" :key="i">
                                    <div class="ukc-form-row">
                                        <label x-text="'Axe ' + (i+1) + ' - ' + (a.label || '')"></label>
                                        <input type="text" :name="'ref_axes['+i+']'" :value="(refAxes[i] || '')" @input="refAxes[i] = $event.target.value" placeholder="UKC-A1-">
                                    </div>
                                </template>
                            </div>
                        </div>

                        <div class="ukc-card">
                            <h2>Frais d’inscription</h2>
                            <div class="desc">Catégories tarifaires liées à cette édition (FCFA).</div>
                            <div class="tarif-row" style="font-size:10.5px;font-weight:700;text-transform:uppercase;color:var(--ukc-light);">
                                <span>Catégorie</span><span>Montant</span><span></span>
                            </div>
                            <template x-for="(fee, i) in fees" :key="i">
                                <div style="border-bottom:1px solid var(--ukc-border,#f1f1f1);padding-bottom:10px;margin-bottom:10px;">
                                    <div class="tarif-row">
                                        <input type="text" :name="'fees['+i+'][label]'" x-model="fee.label" placeholder="Intitulé (ex. Doctorants et assimilés)">
                                        <input type="number" :name="'fees['+i+'][amount]'" x-model="fee.amount" placeholder="Montant" min="0">
                                        <button type="button" class="ukc-btn--icon" @click="removeFee(i)" aria-label="Retirer">&times;</button>
                                    </div>
                                    {{-- ⚠️ L'intitulé est libre : c'est ce rattachement qui permet de
                                         retrouver le tarif d'un participant à partir de son grade. --}}
                                    <div style="display:flex;flex-wrap:wrap;gap:6px 16px;align-items:center;margin-top:8px;padding-left:2px;">
                                        <span style="font-size:11.5px;font-weight:600;text-transform:uppercase;letter-spacing:.03em;color:var(--ukc-light);">Grades concernés</span>
                                        @foreach (\App\Support\ParticipantTypes::all() as $gradeOption)
                                            <label style="display:inline-flex;align-items:center;gap:5px;font-size:12.5px;">
                                                <input type="checkbox" :name="'fees['+i+'][grades][]'" value="{{ $gradeOption }}"
                                                       :checked="(fee.grades || []).includes(@js($gradeOption))"
                                                       @change="toggleFeeGrade(i, @js($gradeOption), $event.target.checked)">
                                                {{ $gradeOption }}
                                            </label>
                                        @endforeach
                                        <label style="display:inline-flex;align-items:center;gap:5px;font-size:12.5px;color:var(--ukc-muted);"
                                               title="Tarif appliqué quand aucun grade ne correspond">
                                            <input type="hidden" :name="'fees['+i+'][default]'" value="0">
                                            <input type="checkbox" :name="'fees['+i+'][default]'" value="1" x-model="fee.default">
                                            Tarif par défaut
                                        </label>
                                    </div>
                                    <p x-show="fee.label && !(fee.grades || []).length && !fee.default" x-cloak
                                       style="font-size:12px;color:#b45309;margin:6px 0 0;">
                                        Cette catégorie ne couvre aucun grade : elle ne sera jamais appliquée automatiquement.
                                    </p>
                                </div>
                            </template>
                            <button type="button" class="ukc-btn ukc-btn--ghost ukc-btn--sm" @click="addFee()" style="margin-top:8px;">+ Catégorie</button>
                        </div>

                        <div class="ukc-card">
                            <h2>Attestations &amp; co-auteurs</h2>
                            <div class="ukc-form-grid">
                                <div class="ukc-form-row">
                                    <label for="attestation_max">Attestations de communication max. / personne</label>
                                    <input type="number" id="attestation_max" name="attestation_max" value="{{ $v('attestation_max') }}" min="1" max="50">
                                    <p class="ukc-hint">Une attestation par communication acceptée / présentée.</p>
                                </div>
                                <div class="ukc-form-row">
                                    <label for="coauthors_max">Co-auteurs max. / communication</label>
                                    <input type="number" id="coauthors_max" name="coauthors_max" value="{{ $v('coauthors_max') }}" min="1" max="30">
                                </div>
                            </div>
                            <label class="ukc-check"><input type="checkbox" name="gen_participation" value="1" @checked($v('gen_participation'))> Générer attestation de participation (1 / participant payé)</label>
                            <label class="ukc-check"><input type="checkbox" name="gen_communication" value="1" @checked($v('gen_communication'))> Générer attestation de communication (par communication)</label>
                        </div>
                    </div>

                    {{-- ===== COMITÉS (une carte par comité, fidèle au mockup) ===== --}}
                    <div class="ukc-panel" :class="{ active: tab==='committees' }">
                        @foreach (['comOrg' => ['Comité d’organisation', 'committees_org', null], 'comSci' => ['Comité scientifique', 'committees_sci', null], 'comSup' => ['Comité de supervision', 'committees_sup', 'Optionnel - autorités / supervision institutionnelle.']] as $group => [$heading, $inputName, $note])
                            <div class="ukc-card">
                                <h2>{{ $heading }}</h2>
                                @if ($note)<div class="desc">{{ $note }}</div>@endif
                                <template x-for="(c, i) in {{ $group }}" :key="i">
                                    <div class="list-item">
                                        <div class="fields">
                                            <div class="row2">
                                                <input type="text" :name="'{{ $inputName }}['+i+'][person_name]'" x-model="c.person_name" placeholder="Nom Prénom">
                                                <input type="text" :name="'{{ $inputName }}['+i+'][person_title]'" x-model="c.person_title" placeholder="Fonction">
                                            </div>
                                            <input type="text" :name="'{{ $inputName }}['+i+'][institution]'" x-model="c.institution" placeholder="Affiliation">
                                        </div>
                                        <button type="button" class="ukc-btn--icon" @click="removeCom('{{ $group }}', i)" aria-label="Retirer">&times;</button>
                                    </div>
                                </template>
                                <button type="button" class="ukc-btn ukc-btn--ghost ukc-btn--sm" @click="addCom('{{ $group }}')">+ Membre</button>
                            </div>
                        @endforeach
                    </div>

                    {{-- ===== PARTENAIRES ===== --}}
                    <div class="ukc-panel" :class="{ active: tab==='partners' }">
                        <div class="ukc-card">
                            <h2>Partenaires</h2>
                            <div class="desc">Logos et liens affichés sur la page publique du colloque.</div>
                            <template x-for="(p, i) in partners" :key="i">
                                <div class="partner-item">
                                    <div class="logo-ph">
                                        <template x-if="p.logo_url"><img :src="p.logo_url" alt=""></template>
                                        <template x-if="!p.logo_url"><span>LOGO</span></template>
                                    </div>
                                    <div class="meta">
                                        <input type="text" :name="'partners['+i+'][name]'" x-model="p.name" placeholder="Nom">
                                        <input type="url" :name="'partners['+i+'][url]'" x-model="p.url" placeholder="https://site-du-partenaire">
                                        <div style="display:grid;grid-template-columns:1fr 120px;gap:4px;">
                                            <input type="text" :name="'partners['+i+'][logo_url]'" x-model="p.logo_url" placeholder="URL du logo">
                                            <input type="text" :name="'partners['+i+'][level]'" x-model="p.level" placeholder="Niveau">
                                        </div>
                                    </div>
                                    <button type="button" class="ukc-btn--icon" @click="removePartner(i)" aria-label="Retirer">&times;</button>
                                </div>
                            </template>
                            <button type="button" class="ukc-btn ukc-btn--ghost ukc-btn--sm" @click="addPartner()">+ Partenaire</button>
                        </div>
                    </div>

                    {{-- ===== APPARENCE ===== --}}
                    <div class="ukc-panel" :class="{ active: tab==='appearance' }">
                        <div class="ukc-card">
                            <h2>Apparence du site public</h2>

                            <h3>Bandeau (hero)</h3>
                            <div class="banner-preview" @if (trim($v('hero_image')) !== '') style="background-image:url('{{ e($v('hero_image')) }}')" @endif></div>
                            <div class="ukc-form-grid">
                                <div class="ukc-form-row">
                                    <label for="hero_style">Style</label>
                                    <select id="hero_style" name="hero_style" value="{{ $v('hero_style') }}">
                                        @foreach (['Image + dégradé', 'Image seule', 'Couleur unie', 'Vidéo de fond'] as $st)
                                            <option @selected($v('hero_style') === $st)>{{ $st }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="ukc-form-row">
                                    <label for="hero_align">Alignement du texte</label>
                                    <select id="hero_align" name="hero_align">
                                        <option @selected($v('hero_align') === 'Centre')>Centre</option>
                                        <option @selected($v('hero_align') === 'Gauche')>Gauche</option>
                                    </select>
                                </div>
                            </div>
                            <div class="ukc-form-row">
                                <label for="hero_image">Image du hero <span class="ukc-hint" style="display:inline;">(URL)</span></label>
                                <input type="url" id="hero_image" name="hero_image" value="{{ $v('hero_image') }}" placeholder="https://…/banniere.jpg">
                            </div>
                            <div class="ukc-form-grid">
                                <div class="ukc-form-row">
                                    <label for="color_primary">Couleur principale</label>
                                    <div class="color-row">
                                        <input type="color" value="{{ $v('color_primary') ?: '#223ea4' }}" @input="document.getElementById('color_primary').value = $event.target.value">
                                        <input type="text" id="color_primary" name="color_primary" value="{{ $v('color_primary') }}" style="flex:1;" placeholder="#223ea4">
                                    </div>
                                </div>
                                <div class="ukc-form-row">
                                    <label for="color_secondary">Couleur secondaire</label>
                                    <div class="color-row">
                                        <input type="color" value="{{ $v('color_secondary') ?: '#009933' }}" @input="document.getElementById('color_secondary').value = $event.target.value">
                                        <input type="text" id="color_secondary" name="color_secondary" value="{{ $v('color_secondary') }}" style="flex:1;" placeholder="#009933">
                                    </div>
                                </div>
                            </div>
                            <label class="ukc-check"><input type="checkbox" name="banner_enabled" value="1" @checked($v('banner_enabled'))> Afficher la bannière officielle sur la page du colloque</label>

                            <h3>Vidéo de fond du hero</h3>
                            <div class="ukc-form-row">
                                <label for="hero_video">URL vidéo (MP4)</label>
                                <input type="url" id="hero_video" name="hero_video" value="{{ $v('hero_video') }}" placeholder="https://…/hero.mp4">
                            </div>
                            <label class="ukc-check"><input type="checkbox" name="hero_video_enabled" value="1" @checked($v('hero_video_enabled'))> Activer la vidéo de fond (remplace l’image si disponible)</label>
                            <label class="ukc-check"><input type="checkbox" name="hero_video_muted" value="1" @checked($v('hero_video_muted'))> Muet · boucle · lecture auto</label>

                            <h3>Actualité (encart du hero)</h3>
                            <label class="ukc-check"><input type="checkbox" name="news_enabled" value="1" @checked($v('news_enabled'))> Afficher un encart d’actualité sur le hero</label>
                            <div class="ukc-form-row">
                                <label for="news_text">Texte de l’encart</label>
                                <input type="text" id="news_text" name="news_text" value="{{ $v('news_text') }}" placeholder="Soumissions ouvertes jusqu’au 15 juillet 2026">
                            </div>
                            <div class="ukc-form-row">
                                <label for="news_url">Lien (optionnel)</label>
                                <input type="url" id="news_url" name="news_url" value="{{ $v('news_url') }}" placeholder="https://…">
                            </div>

                            <h3>Statistiques (bandeau public)</h3>
                            <div class="desc">Chiffres affichés sous le hero ou en section dédiée.</div>
                            <template x-for="(st, i) in stats" :key="i">
                                <div class="list-item">
                                    <div class="fields">
                                        <div class="row2">
                                            <input type="text" :name="'stats['+i+'][label]'" x-model="st.label" placeholder="Libellé (ex. Soumissions)">
                                            <input type="text" :name="'stats['+i+'][value]'" x-model="st.value" placeholder="Valeur (ex. 142)">
                                        </div>
                                    </div>
                                    <button type="button" class="ukc-btn--icon" @click="removeStat(i)" aria-label="Retirer">&times;</button>
                                </div>
                            </template>
                            <button type="button" class="ukc-btn ukc-btn--ghost ukc-btn--sm" @click="addStat()">+ Ajouter un chiffre</button>
                            <label class="ukc-check" style="margin-top:12px;"><input type="checkbox" name="stats_enabled" value="1" @checked($v('stats_enabled'))> Afficher le bandeau statistiques</label>
                        </div>
                    </div>

                    {{-- ===== OPTIONS (mode édition) ===== --}}
                    @if ($edition)
                        <div class="ukc-panel" :class="{ active: tab==='options' }">
                            <div class="ukc-card">
                                <h2>Options de l’édition</h2>
                                <label class="ukc-check"><input type="checkbox" name="opt_show_public" value="1" @checked($v('opt_show_public'))> Afficher sur le site public</label>
                                <label class="ukc-check"><input type="checkbox" name="opt_hybrid" value="1" @checked($v('opt_hybrid'))> Sessions hybrides</label>
                                <label class="ukc-check"><input type="checkbox" name="opt_livret" value="1" @checked($v('opt_livret'))> Livre des résumés activé</label>
                                <label class="ukc-check"><input type="checkbox" name="opt_badges" value="1" @checked($v('opt_badges'))> Badges participants activés</label>
                                <label class="ukc-check"><input type="checkbox" name="opt_formation" value="1" @checked($v('opt_formation'))> Activer le Module Formations</label>
                                <label class="ukc-check"><input type="checkbox" name="opt_coi_ignore_institution" value="1" @checked($v('opt_coi_ignore_institution'))> Ignorer le conflit d’intérêts entre relecteur et auteur de la même institution</label>

                                <h3>Liens rapides</h3>
                                <div class="quick-links">
                                    <a href="{{ route('admin.editions.form', $edition) }}" class="ql"><x-ukc.icon name="list" :size="18" /> Formulaire de soumission</a>
                                    <a href="{{ route('admin.editions.documents', $edition) }}" class="ql"><x-ukc.icon name="folder" :size="18" /> Documents & ressources</a>
                                    <a href="{{ route('admin.submissions.index') }}" class="ql"><x-ukc.icon name="inbox" :size="18" /> Soumissions</a>
                                    <a href="{{ route('admin.participants.index') }}" class="ql"><x-ukc.icon name="users" :size="18" /> Participants</a>
                                    <a href="{{ route('admin.payments.index') }}" class="ql"><x-ukc.icon name="card" :size="18" /> Paiements</a>
                                    <a href="{{ route('admin.emails.index') }}" class="ql"><x-ukc.icon name="mail" :size="18" /> E-mails</a>
                                    <a href="{{ route('admin.reports.index') }}" class="ql"><x-ukc.icon name="chart" :size="18" /> Rapports</a>
                                    <a href="{{ route('admin.documents.livret', $edition) }}" target="_blank" class="ql"><x-ukc.icon name="book" :size="18" /> Livre des résumés</a>
                                    <a href="{{ route('admin.documents.badges', $edition) }}" target="_blank" class="ql"><x-ukc.icon name="card" :size="18" /> Badges</a>
                                    <a href="{{ route('admin.program.slots') }}?edition_id={{ $edition->id }}" class="ql"><x-ukc.icon name="calendar" :size="18" /> Programme & créneaux</a>
                                </div>
                            </div>
                        </div>
                    @endif

                    {{-- Enregistrer/Annuler du formulaire principal : masqués sur les onglets
                         à sauvegarde dédiée (Relecteurs, Équipe, Traductions) qui ont leurs
                         propres boutons. --}}
                    <div class="ukc-form-actions" style="margin-top:4px;border-top:none;padding-top:0;"
                         x-show="!['payment','team','translations'].includes(tab)">
                        <button type="submit" class="ukc-btn ukc-btn--blue">Enregistrer</button>
                        <a href="{{ route('admin.editions.index') }}" class="ukc-btn ukc-btn--ghost">Annuler</a>
                    </div>
                </form>

                {{-- ===== PAIEMENT (propre à l'édition : sauvegarde dédiée) ===== --}}
                @if ($edition)
                    @php($pay = $edition->settings['payment'] ?? [])
                    <div class="ukc-panel" :class="{ active: tab==='payment' }">
                        <div class="ukc-card">
                            <h2>Passerelle de paiement de cette édition</h2>
                            <p style="color:var(--ukc-muted);font-size:14px;margin:-4px 0 12px;">
                                Renseignez ici les identifiants propres à cette édition. <strong>Tout champ laissé vide utilise la configuration
                                par défaut de la plateforme</strong> (réglages globaux). Utile pour encaisser sur un compte marchand dédié à l’édition.
                            </p>
                            <form method="POST" action="{{ route('admin.editions.payment', $edition) }}" class="ukc-form-grid">
                                @csrf
                                <div class="ukc-field full">
                                    <label for="pay-gateway">Passerelle par défaut de l’édition</label>
                                    <select id="pay-gateway" name="gateway">
                                        <option value="">- Utiliser la passerelle par défaut de la plateforme -</option>
                                        <option value="manual" @selected(($pay['gateway'] ?? '') === 'manual')>Paiement manuel (hors ligne)</option>
                                        <option value="fedapay" @selected(($pay['gateway'] ?? '') === 'fedapay')>FedaPay</option>
                                        <option value="cashpay" @selected(($pay['gateway'] ?? '') === 'cashpay')>CashPay (SEMOA)</option>
                                    </select>
                                </div>

                                <div class="ukc-field full"><h3 style="font-size:14px;font-weight:800;margin:6px 0 0;">FedaPay</h3></div>
                                <div class="ukc-field">
                                    <label for="fp-secret">Clé secrète</label>
                                    <input type="text" id="fp-secret" name="fedapay[secret]" value="{{ $pay['fedapay']['secret'] ?? '' }}" autocomplete="off" placeholder="sk_live_… (laisser vide = défaut)">
                                </div>
                                <div class="ukc-field">
                                    <label for="fp-mode">Mode</label>
                                    <select id="fp-mode" name="fedapay[mode]">
                                        <option value="">- Défaut -</option>
                                        <option value="test" @selected(($pay['fedapay']['mode'] ?? '') === 'test')>Test (sandbox)</option>
                                        <option value="live" @selected(($pay['fedapay']['mode'] ?? '') === 'live')>Live</option>
                                    </select>
                                </div>
                                <div class="ukc-field full">
                                    <label for="fp-wh">Secret de webhook</label>
                                    <input type="text" id="fp-wh" name="fedapay[webhook_secret]" value="{{ $pay['fedapay']['webhook_secret'] ?? '' }}" autocomplete="off" placeholder="wh_… (laisser vide = défaut)">
                                    <p class="ukc-hint">Copié depuis le tableau de bord FedaPay (section Webhooks). <strong>Indispensable</strong> : sans lui, une notification de paiement ne peut pas être authentifiée et le règlement resterait « en attente ».</p>
                                </div>
                                <x-ukc.webhook-url :url="route('payment.webhook.edition', ['gateway' => 'fedapay', 'edition' => $edition])"
                                                   label="URL du webhook FedaPay à déclarer" />

                                <div class="ukc-field full"><h3 style="font-size:14px;font-weight:800;margin:6px 0 0;">CashPay (SEMOA)</h3></div>
                                <div class="ukc-field">
                                    <label for="cp-base">Base URL</label>
                                    <input type="text" id="cp-base" name="cashpay[base_url]" value="{{ $pay['cashpay']['base_url'] ?? '' }}" autocomplete="off" placeholder="https://… (laisser vide = défaut)">
                                </div>
                                <div class="ukc-field">
                                    <label for="cp-user">Utilisateur</label>
                                    <input type="text" id="cp-user" name="cashpay[username]" value="{{ $pay['cashpay']['username'] ?? '' }}" autocomplete="off">
                                </div>
                                <div class="ukc-field">
                                    <label for="cp-pass">Mot de passe</label>
                                    <input type="text" id="cp-pass" name="cashpay[password]" value="{{ $pay['cashpay']['password'] ?? '' }}" autocomplete="off">
                                </div>
                                <div class="ukc-field">
                                    <label for="cp-cid">Client ID</label>
                                    <input type="text" id="cp-cid" name="cashpay[client_id]" value="{{ $pay['cashpay']['client_id'] ?? '' }}" autocomplete="off">
                                </div>
                                <div class="ukc-field">
                                    <label for="cp-csecret">Client secret</label>
                                    <input type="text" id="cp-csecret" name="cashpay[client_secret]" value="{{ $pay['cashpay']['client_secret'] ?? '' }}" autocomplete="off">
                                </div>
                                <div class="ukc-field">
                                    <label for="cp-api">Clé API (callback)</label>
                                    <input type="text" id="cp-api" name="cashpay[api_key]" value="{{ $pay['cashpay']['api_key'] ?? '' }}" autocomplete="off">
                                </div>
                                <x-ukc.webhook-url :url="route('payment.webhook.edition', ['gateway' => 'cashpay', 'edition' => $edition])"
                                                   label="URL du callback CashPay à déclarer" />

                                <div class="ukc-form-actions full">
                                    <button type="submit" class="ukc-btn ukc-btn--blue">Enregistrer la passerelle</button>
                                </div>
                            </form>
                        </div>
                    </div>
                @endif

                @if ($edition)
                    {{-- ===== ÉQUIPE (staff affecté à CETTE édition) ===== --}}
                    <div class="ukc-panel" :class="{ active: tab==='team' }">
                        <div class="ukc-card">
                            <h2>Équipe du colloque</h2>
                            <p style="color:var(--ukc-muted);font-size:14px;margin:-4px 0 12px;">
                                Attribuez à chaque personne un ou plusieurs <strong>rôles dans ce colloque</strong>
                                (gestionnaire, comité d’organisation, secrétariat, comptabilité, accueil…) - les rôles se cumulent.
                                Chaque colloque a sa propre équipe. La relecture scientifique se gère dans l’onglet <strong>Relecteurs</strong>.
                            </p>

                            <form method="POST" action="{{ route('admin.editions.team.attach', $edition) }}" class="team-add">
                                @csrf
                                <select name="user_id" required data-tom data-placeholder="Rechercher un membre du staff…">
                                    <option value="">- Choisir un membre du staff -</option>
                                    @foreach ($assignableUsers as $u)
                                        <option value="{{ $u->id }}">{{ $u->name }} · {{ $u->email }}</option>
                                    @endforeach
                                </select>
                                <select name="role" required>
                                    <option value="">- Rôle dans ce colloque -</option>
                                    @foreach ($teamRoles as $key => $label)
                                        <option value="{{ $key }}">{{ $label }}</option>
                                    @endforeach
                                </select>
                                <button type="submit" class="ukc-btn ukc-btn--blue">Ajouter</button>
                            </form>

                            @if ($team->isEmpty())
                                <div class="ukc-empty" style="padding:24px 0;">
                                    <p style="margin:0;">Aucun membre affecté à cette édition pour l’instant.</p>
                                </div>
                            @else
                                <div class="ukc-table-wrap" style="margin-top:16px;">
                                    <table class="ukc-table">
                                        <thead><tr><th>Membre</th><th>Rôle dans ce colloque</th><th style="text-align:right;">Action</th></tr></thead>
                                        <tbody>
                                            @foreach ($team as $m)
                                                <tr>
                                                    <td><strong>{{ $m['name'] }}</strong><div style="font-size:12px;color:var(--ukc-muted);">{{ $m['email'] }}</div></td>
                                                    <td>
                                                        <span class="ukc-pill ukc-pill--{{ $m['pill'] }}">{{ $m['role_label'] }}</span>
                                                        @if (! empty($m['caps']))<div style="font-size:11.5px;color:var(--ukc-muted);margin-top:4px;">{{ $m['caps'] }}</div>@endif
                                                    </td>
                                                    <td style="text-align:right;">
                                                        <form method="POST" action="{{ route('admin.editions.team.detach', $edition) }}"
                                                              onsubmit="return confirm('Retirer {{ $m['name'] }} ({{ $m['role_label'] }}) de cette édition ?')" style="display:inline;">
                                                            @csrf @method('DELETE')
                                                            <input type="hidden" name="user_id" value="{{ $m['user_id'] }}">
                                                            <input type="hidden" name="role" value="{{ $m['role'] }}">
                                                            <button type="submit" class="ukc-btn ukc-btn--ghost ukc-btn--sm">Retirer</button>
                                                        </form>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @endif
                        </div>
                    </div>

                    {{-- ===== TRADUCTIONS (i18n : contenu par langue) ===== --}}
                    <div class="ukc-panel" :class="{ active: tab==='translations' }">
                        @php($fallback = config('app.fallback_locale'))
                        @php($fieldLabels = ['title' => 'Titre', 'theme' => 'Thème', 'edition_label' => 'Libellé de l’édition', 'location' => 'Lieu'])
                        <div class="ukc-card">
                            <h2>Traductions du contenu</h2>
                            <p style="color:var(--ukc-muted);font-size:14px;margin:-4px 0 10px;">
                                Version française = contenu de base (onglets Général / Appel). Renseignez ici les
                                autres langues ; un champ laissé vide affiche le français.
                            </p>
                            @foreach (\App\Support\Locales::available() as $code => $label)
                                @continue($code === $fallback)
                                <form method="POST" action="{{ route('admin.editions.translations', $edition) }}"
                                      style="margin-bottom:18px;padding:14px;border:1px solid var(--ukc-border);border-radius:12px;">
                                    @csrf
                                    <input type="hidden" name="locale" value="{{ $code }}">
                                    <h3 style="margin-top:0;">{{ $label }} <span class="ukc-hint" style="display:inline;">({{ strtoupper($code) }})</span></h3>
                                    @foreach ($fieldLabels as $field => $flabel)
                                        <div class="ukc-form-row">
                                            <label>{{ $flabel }}
                                                <span class="ukc-hint" style="display:inline;">- FR : {{ \Illuminate\Support\Str::limit((string) ($edition->{$field} ?? '-'), 60) }}</span>
                                            </label>
                                            <input type="text" name="tr[{{ $field }}]" maxlength="500"
                                                   value="{{ $edition->translations($code)[$field] ?? '' }}"
                                                   placeholder="{{ $flabel }} en {{ $label }}">
                                        </div>
                                    @endforeach
                                    <div class="ukc-form-actions" style="margin-top:12px;">
                                        <button type="submit" class="ukc-btn ukc-btn--blue">Enregistrer ({{ strtoupper($code) }})</button>
                                    </div>
                                </form>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>

            {{-- ===================== SIDEBAR STICKY ===================== --}}
            <div>
                {{-- Panneau IA (Analyser → suggestions → Appliquer) --}}
                @if ($aiAvailable)
                    <div class="ai-panel">
                        <h2><span>✨</span> Paramétrage assisté (IA)</h2>
                        <div class="desc">Remplir automatiquement les champs à partir de documents (appel, affiche, programme…) ou en décrivant l’édition.</div>
                        <div class="ai-drop" @click="$refs.aiFile.click()" x-text="fileLabel()"></div>
                        <input type="file" x-ref="aiFile" multiple accept=".pdf,.png,.jpg,.jpeg,.webp" style="display:none;" @change="onFiles($event)">
                        <textarea x-model="aiPrompt" rows="4" placeholder="Ex. : dates du colloque, thème, axes, public cible…"></textarea>
                        <div class="ai-actions">
                            <button type="button" class="ukc-btn ukc-btn--ai" style="width:100%;" @click="analyze()" :disabled="aiBusy">
                                <span x-show="!aiBusy">Analyser</span><span x-show="aiBusy" style="display:none;">Analyse en cours…</span>
                            </button>
                        </div>
                        <div class="ai-actions" x-show="aiSuggestions" style="margin-top:8px;display:none;">
                            <button type="button" class="ukc-btn ukc-btn--ghost ukc-btn--sm" style="width:100%;" @click="applyAi()">Appliquer au formulaire</button>
                        </div>
                        <span class="ukc-hint" x-text="aiMsg" x-show="aiMsg" style="display:block;margin-top:8px;"></span>
                        <div class="ai-suggestions" x-show="aiSuggestions" style="display:none;">
                            <template x-for="(item, k) in suggestionList()" :key="k">
                                <div class="ai-sug-item"><strong x-text="item.label"></strong><span x-text="item.value"></span></div>
                            </template>
                        </div>
                    </div>
                @endif

                {{-- Résumé live --}}
                <div class="ukc-card" style="margin-bottom:0;">
                    <h2 style="font-size:14px;">Résumé</h2>
                    <div class="side-summary">
                        <div><span class="k">Statut</span><br><span class="ukc-pill" :class="statusLive() ? 'ukc-pill--live' : 'ukc-pill--muted'" x-text="statusLabel()"></span></div>
                        <div><span class="k">Édition</span><br><strong x-text="f.title || '-'"></strong></div>
                        <div><span class="k">Thème</span><br><span x-text="f.theme || '-'"></span></div>
                        <div><span class="k">Dates</span><br><strong x-text="datesLabel()"></strong></div>
                        <div><span class="k">Lieu</span><br><span x-text="(f.location || '-').split('\n')[0]"></span></div>
                        <div><span class="k">Format</span><br><span x-text="f.format || '-'"></span></div>
                        <div><span class="k">Soumissions</span><br><span x-text="f.submission_open ? ('Ouvertes' + (f.submission_deadline ? ' · limite ' + f.submission_deadline.slice(0,10) : '')) : 'Fermées'"></span></div>
                        <div><span class="k">Langues</span><br><span x-text="(f.languages && f.languages.length) ? f.languages.join(', ') : '-'"></span></div>
                        <div style="margin-bottom:0;"><span class="k">Chiffres</span><br><span x-text="statsLabel()"></span></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('editionForm', (cfg) => ({
                tab: cfg.tab,
                f: cfg.f,
                refMode: @js($v('ref_prefix_mode')),
                refAxes: @js(old('ref_axes', $refAxes)),
                aiUrl: cfg.aiUrl, csrf: cfg.csrf,
                aiPrompt: '', aiBusy: false, aiMsg: '', aiSuggestions: null, aiFiles: [],
                comOrg: cfg.arrays.comOrg || [],
                comSci: cfg.arrays.comSci || [],
                comSup: cfg.arrays.comSup || [],
                addCom(g) { this[g].push({ person_name: '', person_title: '', institution: '' }); },
                removeCom(g, i) { this[g].splice(i, 1); },
                ...window.__editionArrays(cfg.arrays),
                statusLabel() { return { draft:'Brouillon', open:'Soumissions ouvertes', closed:'Clôturée', archived:'Archivée' }[this.f.status] || '-'; },
                statusLive() { return this.f.status === 'open'; },
                datesLabel() {
                    if (this.f.date_start && this.f.date_end) return this.f.date_start + ' → ' + this.f.date_end;
                    return this.f.date_start || this.f.date_end || '-';
                },
                statsLabel() {
                    const s = (this.stats || []).filter(x => x.label || x.value);
                    return s.length ? s.map(x => (x.value + ' ' + x.label).trim()).join(' · ') : '-';
                },
                fileLabel() { return this.aiFiles.length ? (this.aiFiles.length + ' fichier(s) sélectionné(s)') : 'Déposer un document (PDF, image)'; },
                onFiles(e) { this.aiFiles = Array.from(e.target.files || []); },
                suggestionList() {
                    const s = this.aiSuggestions || {}, labels = { title:'Nom', theme:'Thème', subtitle:'Accroche', location:'Lieu', year:'Année', date_start:'Début', date_end:'Fin', submission_deadline:'Limite', intro:'Présentation' }, out = {};
                    Object.keys(labels).forEach(k => { if (s[k]) out[k] = { label: labels[k], value: String(s[k]).slice(0,120) }; });
                    if (Array.isArray(s.tracks) && s.tracks.length) out.tracks = { label:'Axes', value: s.tracks.join(' · ') };
                    if (Array.isArray(s.fees) && s.fees.length) out.fees = { label:'Tarifs', value: s.fees.length + ' catégorie(s)' };
                    if (Array.isArray(s.committees) && s.committees.length) out.committees = { label:'Comités', value: s.committees.length + ' membre(s)' };
                    if (Array.isArray(s.partners) && s.partners.length) out.partners = { label:'Partenaires', value: s.partners.length };
                    if (Array.isArray(s.faq) && s.faq.length) out.faq = { label:'FAQ', value: s.faq.length + ' question(s)' };
                    return out;
                },
                async analyze() {
                    if (!this.aiFiles.length && !this.aiPrompt.trim()) { this.aiMsg = 'Ajoutez un document ou une consigne.'; return; }
                    this.aiBusy = true; this.aiMsg = 'Analyse en cours…'; this.aiSuggestions = null;
                    try {
                        const fd = new FormData();
                        fd.append('aiPrompt', this.aiPrompt || '');
                        this.aiFiles.forEach(f => fd.append('aiFiles[]', f));
                        const r = await fetch(this.aiUrl, { method:'POST', headers: { 'X-CSRF-TOKEN': this.csrf, 'Accept':'application/json' }, body: fd });
                        const j = await r.json();
                        this.aiMsg = j.message || '';
                        if (j.ok && j.suggestions && Object.keys(j.suggestions).length) this.aiSuggestions = j.suggestions;
                    } catch (e) { this.aiMsg = 'Échec de l’analyse.'; }
                    this.aiBusy = false;
                },
                applyAi() {
                    const s = this.aiSuggestions || {};
                    ['title','edition_label','subtitle','theme','year','date_start','date_end','submission_deadline','location','intro'].forEach(k => { if (s[k] !== undefined && s[k] !== '') this.f[k] = s[k]; });
                    if (Array.isArray(s.tracks)) this.axes = s.tracks.map(t => ({ label: t }));
                    if (Array.isArray(s.fees)) this.fees = s.fees.map(x => ({ label:x.label||'', amount:x.amount||'', grades:Array.isArray(x.grades)?x.grades:[], default:!!x.default }));
                    if (Array.isArray(s.committees)) {
                        this.comOrg = s.committees.filter(c => (c.role || 'organisation') === 'organisation');
                        this.comSci = s.committees.filter(c => c.role === 'scientifique');
                        this.comSup = s.committees.filter(c => c.role === 'supervision');
                    }
                    if (Array.isArray(s.partners)) this.partners = s.partners;
                    if (Array.isArray(s.faq)) this.faq = s.faq;
                    this.aiMsg = 'Appliqué au formulaire.'; this.aiSuggestions = null;
                },
            }));
        });
    </script>
    @endpush
</x-layouts.admin>
