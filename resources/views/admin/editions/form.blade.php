@php($v = fn ($k) => old($k, $init[$k]))
<x-layouts.admin :title="$edition ? 'Modifier l’édition' : 'Nouvelle édition'" subtitle="Paramètres, axes, tarifs, page publique & documents">
    <div class="admin-page admin-edition-detail"
         x-data="{ tab: '{{ session('edition_tab', 'general') }}', ...editionArrays(@js(['fees' => $fees, 'committees' => $committees, 'partners' => $partners, 'faq' => $faq])) }">

        {{-- En-tête (mode édition) --}}
        @if ($edition)
            <div style="display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap;margin-bottom:14px;">
                <div style="font-size:13px;color:var(--ukc-muted);">
                    <a href="{{ route('admin.editions.index') }}" style="color:var(--ukc-muted);">Éditions</a>
                    <span> / </span><strong style="color:var(--ukc-text);">{{ $v('title') ?: 'Édition' }}</strong>
                </div>
                <a href="{{ route('admin.editions.index') }}" class="ukc-btn ukc-btn--ghost ukc-btn--sm">← Liste</a>
            </div>
        @endif

        {{-- Paramétrage assisté par IA (formulaire séparé : il pré-remplit plusieurs sections) --}}
        @if ($aiAvailable)
            <form method="POST" action="{{ route('admin.editions.ai-assist') }}" enctype="multipart/form-data"
                  class="ukc-card" style="margin-bottom:18px;background:linear-gradient(135deg,rgba(34,62,164,.05),rgba(0,153,51,.05));">
                @csrf
                <h2><span><x-ukc.icon name="sparkles" :size="20" /> Paramétrage assisté (IA)</span></h2>
                <p style="font-size:.9rem;color:var(--ukc-muted);margin-bottom:12px;">
                    Déposez l’appel à communications ou l’affiche (PDF ou images, 6 max). L’IA pré-remplit les champs
                    trouvés ; rien n’est enregistré tant que vous n’avez pas vérifié et cliqué « Enregistrer ».
                </p>
                <div style="display:flex;gap:10px;align-items:center;flex-wrap:wrap;">
                    <input type="file" name="aiFiles[]" multiple accept=".pdf,.png,.jpg,.jpeg,.webp" style="font-size:.9rem;">
                    <button type="submit" class="ukc-btn ukc-btn--blue ukc-btn--sm"><x-ukc.icon name="sparkles" :size="16" /> Analyser</button>
                </div>
                @error('aiFiles') <span class="error" style="display:block;margin-top:6px;">{{ $message }}</span> @enderror
                @error('aiFiles.*') <span class="error" style="display:block;margin-top:6px;">{{ $message }}</span> @enderror
            </form>
        @endif

        <div class="ukc-tabs">
            <button type="button" @click="tab='general'" :class="{ active: tab==='general' }">Général</button>
            <button type="button" @click="tab='content'" :class="{ active: tab==='content' }">Appel &amp; contenu</button>
            <button type="button" @click="tab='submissions'" :class="{ active: tab==='submissions' }">Soumissions &amp; tarifs</button>
            <button type="button" @click="tab='committees'" :class="{ active: tab==='committees' }">Comités</button>
            <button type="button" @click="tab='partners'" :class="{ active: tab==='partners' }">Partenaires &amp; FAQ</button>
            @if ($edition)<button type="button" @click="tab='options'" :class="{ active: tab==='options' }">Options</button>@endif
        </div>

        <form method="POST" action="{{ $edition ? route('admin.editions.update', $edition) : route('admin.editions.store') }}">
            @csrf
            @if ($edition) @method('PUT') @endif

            {{-- Général --}}
            <div class="ukc-panel" :class="{ active: tab==='general' }">
                <div class="ukc-card" style="margin-bottom:18px;">
                    <h2><span><x-ukc.icon name="folder" :size="20" /> Colloque</span></h2>
                    <div class="ukc-form-grid">
                        <div class="ukc-field">
                            <label for="colloque_id">Colloque existant</label>
                            <select id="colloque_id" name="colloque_id">
                                <option value="">— Choisir —</option>
                                @foreach ($colloques as $c)
                                    <option value="{{ $c->id }}" @selected((int) $v('colloque_id') === $c->id)>{{ $c->name }}</option>
                                @endforeach
                            </select>
                            @error('colloque_id') <span class="error">{{ $message }}</span> @enderror
                        </div>
                        <div class="ukc-field">
                            <label for="new_colloque">…ou nouveau colloque</label>
                            <input type="text" id="new_colloque" name="new_colloque" value="{{ old('new_colloque') }}" placeholder="Nom du nouveau colloque">
                            <span class="hint">Renseignez ceci uniquement pour créer une nouvelle série.</span>
                        </div>
                    </div>
                </div>

                <div class="ukc-card" style="margin-bottom:18px;">
                    <h2><span><x-ukc.icon name="info" :size="20" /> Informations générales</span></h2>
                    <div class="ukc-form-grid">
                        <div class="ukc-field full">
                            <label for="title">Titre de l’édition <span class="req">*</span></label>
                            <input type="text" id="title" name="title" value="{{ $v('title') }}" placeholder="Colloque international de l’Université de Kara — 2026">
                            @error('title') <span class="error">{{ $message }}</span> @enderror
                        </div>
                        <div class="ukc-field">
                            <label for="edition_label">Libellé d’édition</label>
                            <input type="text" id="edition_label" name="edition_label" value="{{ $v('edition_label') }}" placeholder="3e édition">
                            @error('edition_label') <span class="error">{{ $message }}</span> @enderror
                        </div>
                        <div class="ukc-field">
                            <label for="year">Année</label>
                            <input type="number" id="year" name="year" value="{{ $v('year') }}" min="2000" max="2100">
                            @error('year') <span class="error">{{ $message }}</span> @enderror
                        </div>
                        <div class="ukc-field full">
                            <label for="subtitle">Sous-titre</label>
                            <input type="text" id="subtitle" name="subtitle" value="{{ $v('subtitle') }}">
                            @error('subtitle') <span class="error">{{ $message }}</span> @enderror
                        </div>
                        <div class="ukc-field full">
                            <label for="theme">Thème</label>
                            <textarea id="theme" name="theme" rows="2">{{ $v('theme') }}</textarea>
                            @error('theme') <span class="error">{{ $message }}</span> @enderror
                        </div>
                        <div class="ukc-field">
                            <label for="date_start">Date de début</label>
                            <input type="date" id="date_start" name="date_start" value="{{ $v('date_start') }}">
                            @error('date_start') <span class="error">{{ $message }}</span> @enderror
                        </div>
                        <div class="ukc-field">
                            <label for="date_end">Date de fin</label>
                            <input type="date" id="date_end" name="date_end" value="{{ $v('date_end') }}">
                            @error('date_end') <span class="error">{{ $message }}</span> @enderror
                        </div>
                        <div class="ukc-field full">
                            <label for="location">Lieu <span class="hint">(plusieurs lignes possibles)</span></label>
                            <textarea id="location" name="location" rows="3" placeholder="Université de Kara — Campus Sud&#10;Route de l’ex-ENI, BP 43&#10;Kara — Togo">{{ $v('location') }}</textarea>
                            @error('location') <span class="error">{{ $message }}</span> @enderror
                        </div>
                    </div>
                </div>
            </div>

            {{-- Appel & contenu --}}
            <div class="ukc-panel" :class="{ active: tab==='content' }">
                <div class="ukc-card" style="margin-bottom:18px;">
                    <h2><span><x-ukc.icon name="book" :size="20" /> Contenu public</span></h2>
                    <div class="ukc-form-grid">
                        <div class="ukc-field full">
                            <label for="intro">Argumentaire / présentation</label>
                            <textarea id="intro" name="intro" rows="4">{{ $v('intro') }}</textarea>
                            @error('intro') <span class="error">{{ $message }}</span> @enderror
                        </div>
                        <div class="ukc-field full">
                            <label for="tracks">Axes thématiques <span class="hint">(un par ligne)</span></label>
                            <textarea id="tracks" name="tracks" rows="5" placeholder="Sciences et technologies&#10;Sciences de la santé&#10;…">{{ $v('tracks') }}</textarea>
                            @error('tracks') <span class="error">{{ $message }}</span> @enderror
                        </div>
                        <div class="ukc-field">
                            <label for="hero_image">Image du hero <span class="hint">(URL, optionnel)</span></label>
                            <input type="url" id="hero_image" name="hero_image" value="{{ $v('hero_image') }}" placeholder="https://…/banniere.jpg">
                            @error('hero_image') <span class="error">{{ $message }}</span> @enderror
                        </div>
                        <div class="ukc-field">
                            <label for="hero_video">Vidéo de fond du hero <span class="hint">(URL mp4, optionnel)</span></label>
                            <input type="url" id="hero_video" name="hero_video" value="{{ $v('hero_video') }}" placeholder="https://…/intro.mp4">
                            @error('hero_video') <span class="error">{{ $message }}</span> @enderror
                        </div>
                        <div class="ukc-field full">
                            <label style="display:flex;align-items:center;gap:8px;cursor:pointer;font-weight:600;">
                                <input type="checkbox" name="banner_enabled" value="1" @checked($v('banner_enabled')) style="width:auto;padding:0;">
                                Afficher la bannière officielle sur la page du colloque
                            </label>
                            <span class="hint">Décochez pour masquer complètement la section bannière.</span>
                        </div>
                    </div>

                    <div class="ukc-form-grid" style="margin-top:8px;">
                        <div class="ukc-field">
                            <label for="contact_email">E-mail de contact</label>
                            <input type="email" id="contact_email" name="contact_email" value="{{ $v('contact_email') }}" placeholder="colloque@univkara.tg">
                            @error('contact_email') <span class="error">{{ $message }}</span> @enderror
                        </div>
                        <div class="ukc-field">
                            <label for="contact_phone">Téléphone de contact</label>
                            <input type="text" id="contact_phone" name="contact_phone" value="{{ $v('contact_phone') }}" placeholder="+228 …">
                            @error('contact_phone') <span class="error">{{ $message }}</span> @enderror
                        </div>
                        <div class="ukc-field full">
                            <label for="contact_address">Adresse</label>
                            <input type="text" id="contact_address" name="contact_address" value="{{ $v('contact_address') }}" placeholder="Université de Kara, BP …">
                            @error('contact_address') <span class="error">{{ $message }}</span> @enderror
                        </div>
                    </div>

                    <div class="ukc-form-grid" style="margin-top:8px;">
                        <div class="ukc-field full">
                            <label>Réseaux sociaux du colloque <span class="hint">(laissez vide pour utiliser ceux du site)</span></label>
                        </div>
                        <div class="ukc-field">
                            <label for="social_facebook">Facebook</label>
                            <input type="url" id="social_facebook" name="social_facebook" value="{{ $v('social_facebook') }}" placeholder="https://facebook.com/…">
                            @error('social_facebook') <span class="error">{{ $message }}</span> @enderror
                        </div>
                        <div class="ukc-field">
                            <label for="social_x">X (Twitter)</label>
                            <input type="url" id="social_x" name="social_x" value="{{ $v('social_x') }}" placeholder="https://x.com/…">
                            @error('social_x') <span class="error">{{ $message }}</span> @enderror
                        </div>
                        <div class="ukc-field">
                            <label for="social_youtube">YouTube</label>
                            <input type="url" id="social_youtube" name="social_youtube" value="{{ $v('social_youtube') }}" placeholder="https://youtube.com/…">
                            @error('social_youtube') <span class="error">{{ $message }}</span> @enderror
                        </div>
                        <div class="ukc-field">
                            <label for="social_linkedin">LinkedIn</label>
                            <input type="url" id="social_linkedin" name="social_linkedin" value="{{ $v('social_linkedin') }}" placeholder="https://linkedin.com/…">
                            @error('social_linkedin') <span class="error">{{ $message }}</span> @enderror
                        </div>
                    </div>

                    <div class="ukc-form-grid" style="margin-top:8px;">
                        <div class="ukc-field full">
                            <label for="promo_text">Bannière promo <span class="hint">(texte affiché en haut de la page ; vide = masquée)</span></label>
                            <input type="text" id="promo_text" name="promo_text" value="{{ $v('promo_text') }}" placeholder="Inscriptions early-bird jusqu’au 30 septembre !">
                            @error('promo_text') <span class="error">{{ $message }}</span> @enderror
                        </div>
                        <div class="ukc-field">
                            <label for="promo_url">Lien de la bannière <span class="hint">(optionnel)</span></label>
                            <input type="url" id="promo_url" name="promo_url" value="{{ $v('promo_url') }}" placeholder="https://…">
                            @error('promo_url') <span class="error">{{ $message }}</span> @enderror
                        </div>
                        <div class="ukc-field">
                            <label for="promo_cta">Libellé du bouton <span class="hint">(optionnel)</span></label>
                            <input type="text" id="promo_cta" name="promo_cta" value="{{ $v('promo_cta') }}" placeholder="En profiter">
                            @error('promo_cta') <span class="error">{{ $message }}</span> @enderror
                        </div>
                    </div>

                    <div class="ukc-form-grid" style="margin-top:8px;">
                        <div class="ukc-field full">
                            <label for="practical_venue">Infos pratiques — Lieu</label>
                            <textarea id="practical_venue" name="practical_venue" rows="2" placeholder="Campus de l’Université de Kara, amphi …">{{ $v('practical_venue') }}</textarea>
                            @error('practical_venue') <span class="error">{{ $message }}</span> @enderror
                        </div>
                        <div class="ukc-field full">
                            <label for="practical_accommodation">Infos pratiques — Hébergement</label>
                            <textarea id="practical_accommodation" name="practical_accommodation" rows="2" placeholder="Hôtels partenaires, tarifs négociés …">{{ $v('practical_accommodation') }}</textarea>
                            @error('practical_accommodation') <span class="error">{{ $message }}</span> @enderror
                        </div>
                        <div class="ukc-field full">
                            <label for="practical_transport">Infos pratiques — Transport</label>
                            <textarea id="practical_transport" name="practical_transport" rows="2" placeholder="Aéroport, navettes, accès routier …">{{ $v('practical_transport') }}</textarea>
                            @error('practical_transport') <span class="error">{{ $message }}</span> @enderror
                        </div>
                    </div>
                </div>
            </div>

            {{-- Soumissions & tarifs --}}
            <div class="ukc-panel" :class="{ active: tab==='submissions' }">
                <div class="ukc-card" style="margin-bottom:18px;">
                    <h2><span><x-ukc.icon name="external" :size="20" /> Publication & soumissions</span></h2>
                    <div class="ukc-form-grid">
                        <div class="ukc-field">
                            <label for="status">Statut</label>
                            <select id="status" name="status">
                                @foreach (['draft' => 'Brouillon', 'open' => 'Ouverte', 'closed' => 'Clôturée', 'archived' => 'Archivée'] as $key => $label)
                                    <option value="{{ $key }}" @selected($v('status') === $key)>{{ $label }}</option>
                                @endforeach
                            </select>
                            <span class="hint">La page publique n’est visible qu’à partir de « Ouverte ».</span>
                            @error('status') <span class="error">{{ $message }}</span> @enderror
                        </div>
                        <div class="ukc-field">
                            <label for="slug">Slug de la page (URL)</label>
                            <input type="text" id="slug" name="slug" value="{{ $v('slug') }}" placeholder="auto depuis le titre">
                            <span class="hint">Laissez vide pour générer automatiquement.</span>
                            @error('slug') <span class="error">{{ $message }}</span> @enderror
                        </div>
                        <div class="ukc-field">
                            <label style="display:flex;align-items:center;gap:8px;cursor:pointer;font-weight:600;">
                                <input type="checkbox" name="submission_open" value="1" @checked($v('submission_open')) style="width:auto;padding:0;">
                                Soumissions ouvertes
                            </label>
                        </div>
                        <div class="ukc-field">
                            <label for="submission_deadline">Date limite de soumission</label>
                            <input type="datetime-local" id="submission_deadline" name="submission_deadline" value="{{ $v('submission_deadline') }}">
                            @error('submission_deadline') <span class="error">{{ $message }}</span> @enderror
                        </div>
                        <div class="ukc-field">
                            <label for="max_communications">Communications max. par personne</label>
                            <input type="number" id="max_communications" name="max_communications" value="{{ $v('max_communications') }}" min="1" max="10">
                            @error('max_communications') <span class="error">{{ $message }}</span> @enderror
                        </div>
                    </div>

                    {{-- Frais --}}
                    <div style="margin-top:12px;">
                        <label style="font-size:13px;font-weight:600;">Frais d’inscription (FCFA)</label>
                        <div style="display:flex;flex-direction:column;gap:8px;margin-top:8px;">
                            <template x-for="(fee, i) in fees" :key="i">
                                <div style="display:flex;gap:8px;align-items:center;">
                                    <input type="text" :name="'fees['+i+'][label]'" x-model="fee.label" placeholder="Profil (ex. Doctorant)"
                                           style="flex:2;border:1.5px solid var(--ukc-border);border-radius:10px;padding:10px 14px;font-size:14px;">
                                    <input type="number" :name="'fees['+i+'][amount]'" x-model="fee.amount" placeholder="Montant" min="0"
                                           style="flex:1;border:1.5px solid var(--ukc-border);border-radius:10px;padding:10px 14px;font-size:14px;">
                                    <button type="button" class="ukc-btn ukc-btn--danger ukc-btn--sm" @click="removeFee(i)"><x-ukc.icon name="close" :size="16" /></button>
                                </div>
                            </template>
                        </div>
                        <button type="button" class="ukc-btn ukc-btn--ghost ukc-btn--sm" @click="addFee()" style="margin-top:10px;">
                            <x-ukc.icon name="plus" :size="16" /> Ajouter un tarif
                        </button>
                    </div>
                </div>
            </div>

            {{-- Comités --}}
            <div class="ukc-panel" :class="{ active: tab==='committees' }">
                <div class="ukc-card" style="margin-bottom:18px;">
                    <h2><span><x-ukc.icon name="users" :size="20" /> Comités</span></h2>
                    <div style="display:flex;flex-direction:column;gap:8px;">
                        <template x-for="(c, i) in committees" :key="i">
                            <div style="display:grid;grid-template-columns:1fr 1.2fr 1fr 1.2fr auto;gap:8px;align-items:center;">
                                <select :name="'committees['+i+'][role]'" x-model="c.role" style="border:1.5px solid var(--ukc-border);border-radius:10px;padding:9px 10px;font-size:13px;">
                                    @foreach ($committeeRoles as $key => $label)<option value="{{ $key }}">{{ $label }}</option>@endforeach
                                </select>
                                <input type="text" :name="'committees['+i+'][person_name]'" x-model="c.person_name" placeholder="Nom Prénom" style="border:1.5px solid var(--ukc-border);border-radius:10px;padding:9px 12px;font-size:13px;">
                                <input type="text" :name="'committees['+i+'][person_title]'" x-model="c.person_title" placeholder="Fonction/grade" style="border:1.5px solid var(--ukc-border);border-radius:10px;padding:9px 12px;font-size:13px;">
                                <input type="text" :name="'committees['+i+'][institution]'" x-model="c.institution" placeholder="Institution" style="border:1.5px solid var(--ukc-border);border-radius:10px;padding:9px 12px;font-size:13px;">
                                <button type="button" class="ukc-btn ukc-btn--danger ukc-btn--sm" @click="removeCommittee(i)"><x-ukc.icon name="close" :size="14" /></button>
                            </div>
                        </template>
                    </div>
                    <button type="button" class="ukc-btn ukc-btn--ghost ukc-btn--sm" @click="addCommittee()" style="margin-top:10px;">
                        <x-ukc.icon name="plus" :size="16" /> Ajouter un membre
                    </button>
                </div>
            </div>

            {{-- Partenaires & FAQ --}}
            <div class="ukc-panel" :class="{ active: tab==='partners' }">
                <div class="ukc-card" style="margin-bottom:18px;">
                    <h2><span><x-ukc.icon name="external" :size="20" /> Partenaires</span></h2>
                    <div style="display:flex;flex-direction:column;gap:8px;">
                        <template x-for="(p, i) in partners" :key="i">
                            <div style="display:grid;grid-template-columns:1.2fr 1.4fr 1.4fr .8fr auto;gap:8px;align-items:center;">
                                <input type="text" :name="'partners['+i+'][name]'" x-model="p.name" placeholder="Nom" style="border:1.5px solid var(--ukc-border);border-radius:10px;padding:9px 12px;font-size:13px;">
                                <input type="text" :name="'partners['+i+'][url]'" x-model="p.url" placeholder="https://…" style="border:1.5px solid var(--ukc-border);border-radius:10px;padding:9px 12px;font-size:13px;">
                                <input type="text" :name="'partners['+i+'][logo_url]'" x-model="p.logo_url" placeholder="URL du logo" style="border:1.5px solid var(--ukc-border);border-radius:10px;padding:9px 12px;font-size:13px;">
                                <input type="text" :name="'partners['+i+'][level]'" x-model="p.level" placeholder="Niveau" style="border:1.5px solid var(--ukc-border);border-radius:10px;padding:9px 12px;font-size:13px;">
                                <button type="button" class="ukc-btn ukc-btn--danger ukc-btn--sm" @click="removePartner(i)"><x-ukc.icon name="close" :size="14" /></button>
                            </div>
                        </template>
                    </div>
                    <button type="button" class="ukc-btn ukc-btn--ghost ukc-btn--sm" @click="addPartner()" style="margin-top:10px;">
                        <x-ukc.icon name="plus" :size="16" /> Ajouter un partenaire
                    </button>
                </div>

                <div class="ukc-card" style="margin-bottom:18px;">
                    <h2><span><x-ukc.icon name="info" :size="20" /> FAQ</span></h2>
                    <div style="display:flex;flex-direction:column;gap:12px;">
                        <template x-for="(f, i) in faq" :key="i">
                            <div style="display:flex;gap:8px;align-items:flex-start;">
                                <div style="flex:1;display:flex;flex-direction:column;gap:6px;">
                                    <input type="text" :name="'faq['+i+'][question]'" x-model="f.question" placeholder="Question" style="border:1.5px solid var(--ukc-border);border-radius:10px;padding:9px 12px;font-size:13px;">
                                    <textarea :name="'faq['+i+'][answer]'" x-model="f.answer" placeholder="Réponse" rows="2" style="border:1.5px solid var(--ukc-border);border-radius:10px;padding:9px 12px;font-size:13px;"></textarea>
                                </div>
                                <button type="button" class="ukc-btn ukc-btn--danger ukc-btn--sm" @click="removeFaq(i)"><x-ukc.icon name="close" :size="14" /></button>
                            </div>
                        </template>
                    </div>
                    <button type="button" class="ukc-btn ukc-btn--ghost ukc-btn--sm" @click="addFaq()" style="margin-top:10px;">
                        <x-ukc.icon name="plus" :size="16" /> Ajouter une question
                    </button>
                </div>
            </div>

            {{-- Options : liens rapides (mode édition) --}}
            @if ($edition)
                <div class="ukc-panel" :class="{ active: tab==='options' }">
                    <div class="ukc-card" style="margin-bottom:18px;">
                        <h2><span><x-ukc.icon name="grid" :size="20" /> Liens rapides</span></h2>
                        <p class="desc">Accès direct aux écrans liés à cette édition.</p>
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
                            @if ($v('status') === 'open')
                                <a href="{{ route('public.colloque', $edition->slug) }}" target="_blank" class="ql"><x-ukc.icon name="external" :size="18" /> Page publique</a>
                            @endif
                        </div>
                    </div>
                </div>
            @endif

            <div class="ukc-form-actions" style="margin-top:0;border-top:none;padding-top:0;">
                <button type="submit" class="ukc-btn ukc-btn--blue">Enregistrer</button>
                <a href="{{ route('admin.editions.index') }}" class="ukc-btn ukc-btn--ghost">Annuler</a>
            </div>
        </form>
    </div>
</x-layouts.admin>
