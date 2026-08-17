<div>
    {{-- Paramétrage assisté par IA --}}
    @if ($aiAvailable)
        <div class="ukc-card" style="margin-bottom:18px;background:linear-gradient(135deg,rgba(34,62,164,.05),rgba(0,153,51,.05));">
            <h2><x-ukc.icon name="sparkles" :size="20" /> Paramétrage assisté (IA)</h2>
            <p style="font-size:.9rem;color:var(--ukc-muted);margin-bottom:12px;">
                Déposez l’appel à communications ou l’affiche (PDF ou images, 6 max). L’IA pré-remplit les champs
                trouvés ; rien n’est enregistré tant que vous n’avez pas vérifié et cliqué « Enregistrer ».
            </p>
            <div style="display:flex;gap:10px;align-items:center;flex-wrap:wrap;">
                <input type="file" wire:model="aiFiles" multiple accept=".pdf,.png,.jpg,.jpeg,.webp"
                       style="font-size:.9rem;">
                <button type="button" class="ukc-btn ukc-btn--blue ukc-btn--sm"
                        wire:click="runAiAssist" wire:loading.attr="disabled" wire:target="runAiAssist,aiFiles">
                    <span wire:loading.remove wire:target="runAiAssist"><x-ukc.icon name="sparkles" :size="16" /> Analyser</span>
                    <span wire:loading wire:target="runAiAssist">Analyse en cours…</span>
                </button>
                <span wire:loading wire:target="aiFiles" class="hint">Téléversement…</span>
            </div>
            @error('aiFiles') <span class="error" style="display:block;margin-top:6px;">{{ $message }}</span> @enderror
            @error('aiFiles.*') <span class="error" style="display:block;margin-top:6px;">{{ $message }}</span> @enderror
            @if (count($aiSummary))
                <div class="ukc-alert ukc-alert--ok" style="margin:12px 0 0;">
                    <span class="ico"><x-ukc.icon name="check" :size="18" /></span>
                    <div>Pré-rempli : {{ implode(', ', $aiSummary) }}. Vérifiez ci-dessous puis enregistrez.</div>
                </div>
            @endif
        </div>
    @endif

    <form wire:submit="save">
        {{-- Rattachement --}}
        <div class="ukc-card" style="margin-bottom:18px;">
            <h2><x-ukc.icon name="folder" :size="20" /> Colloque</h2>
            <div class="ukc-form-grid">
                <div class="ukc-field">
                    <label for="colloque_id">Colloque existant</label>
                    <select id="colloque_id" wire:model="colloque_id">
                        <option value="">— Choisir —</option>
                        @foreach ($colloques as $c)
                            <option value="{{ $c->id }}">{{ $c->name }}</option>
                        @endforeach
                    </select>
                    @error('colloque_id') <span class="error">{{ $message }}</span> @enderror
                </div>
                <div class="ukc-field">
                    <label for="new_colloque">…ou nouveau colloque</label>
                    <input type="text" id="new_colloque" wire:model="new_colloque" placeholder="Nom du nouveau colloque">
                    <span class="hint">Renseignez ceci uniquement pour créer une nouvelle série.</span>
                </div>
            </div>
        </div>

        {{-- Informations générales --}}
        <div class="ukc-card" style="margin-bottom:18px;">
            <h2><x-ukc.icon name="info" :size="20" /> Informations générales</h2>
            <div class="ukc-form-grid">
                <div class="ukc-field full">
                    <label for="title">Titre de l’édition <span class="req">*</span></label>
                    <input type="text" id="title" wire:model="title" placeholder="Colloque international de l’Université de Kara — 2026">
                    @error('title') <span class="error">{{ $message }}</span> @enderror
                </div>

                <div class="ukc-field">
                    <label for="edition_label">Libellé d’édition</label>
                    <input type="text" id="edition_label" wire:model="edition_label" placeholder="3e édition">
                    @error('edition_label') <span class="error">{{ $message }}</span> @enderror
                </div>
                <div class="ukc-field">
                    <label for="year">Année</label>
                    <input type="number" id="year" wire:model="year" min="2000" max="2100">
                    @error('year') <span class="error">{{ $message }}</span> @enderror
                </div>

                <div class="ukc-field full">
                    <label for="subtitle">Sous-titre</label>
                    <input type="text" id="subtitle" wire:model="subtitle">
                    @error('subtitle') <span class="error">{{ $message }}</span> @enderror
                </div>

                <div class="ukc-field full">
                    <label for="theme">Thème</label>
                    <textarea id="theme" wire:model="theme" rows="2"></textarea>
                    @error('theme') <span class="error">{{ $message }}</span> @enderror
                </div>

                <div class="ukc-field">
                    <label for="date_start">Date de début</label>
                    <input type="date" id="date_start" wire:model="date_start">
                    @error('date_start') <span class="error">{{ $message }}</span> @enderror
                </div>
                <div class="ukc-field">
                    <label for="date_end">Date de fin</label>
                    <input type="date" id="date_end" wire:model="date_end">
                    @error('date_end') <span class="error">{{ $message }}</span> @enderror
                </div>

                <div class="ukc-field full">
                    <label for="location">Lieu</label>
                    <input type="text" id="location" wire:model="location" placeholder="Campus de l’Université de Kara, Togo">
                    @error('location') <span class="error">{{ $message }}</span> @enderror
                </div>
            </div>
        </div>

        {{-- Publication & soumissions --}}
        <div class="ukc-card" style="margin-bottom:18px;">
            <h2><x-ukc.icon name="external" :size="20" /> Publication & soumissions</h2>
            <div class="ukc-form-grid">
                <div class="ukc-field">
                    <label for="status">Statut</label>
                    <select id="status" wire:model="status">
                        <option value="draft">Brouillon</option>
                        <option value="open">Ouverte</option>
                        <option value="closed">Clôturée</option>
                        <option value="archived">Archivée</option>
                    </select>
                    <span class="hint">La page publique n’est visible qu’à partir de « Ouverte ».</span>
                    @error('status') <span class="error">{{ $message }}</span> @enderror
                </div>
                <div class="ukc-field">
                    <label for="slug">Slug de la page (URL)</label>
                    <input type="text" id="slug" wire:model="slug" placeholder="auto depuis le titre">
                    <span class="hint">Laissez vide pour générer automatiquement.</span>
                    @error('slug') <span class="error">{{ $message }}</span> @enderror
                </div>

                <div class="ukc-field">
                    <label style="display:flex;align-items:center;gap:8px;cursor:pointer;font-weight:600;">
                        <input type="checkbox" wire:model="submission_open" style="width:auto;padding:0;">
                        Soumissions ouvertes
                    </label>
                </div>
                <div class="ukc-field">
                    <label for="submission_deadline">Date limite de soumission</label>
                    <input type="datetime-local" id="submission_deadline" wire:model="submission_deadline">
                    @error('submission_deadline') <span class="error">{{ $message }}</span> @enderror
                </div>

                <div class="ukc-field">
                    <label for="max_communications">Communications max. par personne</label>
                    <input type="number" id="max_communications" wire:model="max_communications" min="1" max="10">
                    @error('max_communications') <span class="error">{{ $message }}</span> @enderror
                </div>
            </div>
        </div>

        {{-- Contenu public --}}
        <div class="ukc-card" style="margin-bottom:18px;">
            <h2><x-ukc.icon name="book" :size="20" /> Contenu public</h2>
            <div class="ukc-form-grid">
                <div class="ukc-field full">
                    <label for="intro">Argumentaire / présentation</label>
                    <textarea id="intro" wire:model="intro" rows="4"></textarea>
                    @error('intro') <span class="error">{{ $message }}</span> @enderror
                </div>
                <div class="ukc-field full">
                    <label for="tracks">Axes thématiques <span class="hint">(un par ligne)</span></label>
                    <textarea id="tracks" wire:model="tracks" rows="5"
                              placeholder="Sciences et technologies&#10;Sciences de la santé&#10;…"></textarea>
                    @error('tracks') <span class="error">{{ $message }}</span> @enderror
                </div>
                <div class="ukc-field">
                    <label for="hero_image">Image du hero <span class="hint">(URL, optionnel)</span></label>
                    <input type="url" id="hero_image" wire:model="hero_image" placeholder="https://…/banniere.jpg">
                    @error('hero_image') <span class="error">{{ $message }}</span> @enderror
                </div>
                <div class="ukc-field">
                    <label for="hero_video">Vidéo de fond du hero <span class="hint">(URL mp4, optionnel)</span></label>
                    <input type="url" id="hero_video" wire:model="hero_video" placeholder="https://…/intro.mp4">
                    @error('hero_video') <span class="error">{{ $message }}</span> @enderror
                </div>
            </div>

            {{-- Contact --}}
            <div class="ukc-form-grid" style="margin-top:8px;">
                <div class="ukc-field">
                    <label for="contact_email">E-mail de contact</label>
                    <input type="email" id="contact_email" wire:model="contact_email" placeholder="colloque@univkara.tg">
                    @error('contact_email') <span class="error">{{ $message }}</span> @enderror
                </div>
                <div class="ukc-field">
                    <label for="contact_phone">Téléphone de contact</label>
                    <input type="text" id="contact_phone" wire:model="contact_phone" placeholder="+228 …">
                    @error('contact_phone') <span class="error">{{ $message }}</span> @enderror
                </div>
                <div class="ukc-field full">
                    <label for="contact_address">Adresse</label>
                    <input type="text" id="contact_address" wire:model="contact_address" placeholder="Université de Kara, BP …">
                    @error('contact_address') <span class="error">{{ $message }}</span> @enderror
                </div>
            </div>

            {{-- Bannière promo --}}
            <div class="ukc-form-grid" style="margin-top:8px;">
                <div class="ukc-field full">
                    <label for="promo_text">Bannière promo <span class="hint">(texte affiché en haut de la page ; vide = masquée)</span></label>
                    <input type="text" id="promo_text" wire:model="promo_text" placeholder="Inscriptions early-bird jusqu’au 30 septembre !">
                    @error('promo_text') <span class="error">{{ $message }}</span> @enderror
                </div>
                <div class="ukc-field">
                    <label for="promo_url">Lien de la bannière <span class="hint">(optionnel)</span></label>
                    <input type="url" id="promo_url" wire:model="promo_url" placeholder="https://…">
                    @error('promo_url') <span class="error">{{ $message }}</span> @enderror
                </div>
                <div class="ukc-field">
                    <label for="promo_cta">Libellé du bouton <span class="hint">(optionnel)</span></label>
                    <input type="text" id="promo_cta" wire:model="promo_cta" placeholder="En profiter">
                    @error('promo_cta') <span class="error">{{ $message }}</span> @enderror
                </div>
            </div>

            {{-- Infos pratiques --}}
            <div class="ukc-form-grid" style="margin-top:8px;">
                <div class="ukc-field full">
                    <label for="practical_venue">Infos pratiques — Lieu</label>
                    <textarea id="practical_venue" wire:model="practical_venue" rows="2" placeholder="Campus de l’Université de Kara, amphi …"></textarea>
                    @error('practical_venue') <span class="error">{{ $message }}</span> @enderror
                </div>
                <div class="ukc-field full">
                    <label for="practical_accommodation">Infos pratiques — Hébergement</label>
                    <textarea id="practical_accommodation" wire:model="practical_accommodation" rows="2" placeholder="Hôtels partenaires, tarifs négociés …"></textarea>
                    @error('practical_accommodation') <span class="error">{{ $message }}</span> @enderror
                </div>
                <div class="ukc-field full">
                    <label for="practical_transport">Infos pratiques — Transport</label>
                    <textarea id="practical_transport" wire:model="practical_transport" rows="2" placeholder="Aéroport, navettes, accès routier …"></textarea>
                    @error('practical_transport') <span class="error">{{ $message }}</span> @enderror
                </div>
            </div>

            {{-- Frais --}}
            <div style="margin-top:8px;">
                <label style="font-size:13px;font-weight:600;">Frais d’inscription (FCFA)</label>
                <div style="display:flex;flex-direction:column;gap:8px;margin-top:8px;">
                    @foreach ($fees as $i => $fee)
                        <div wire:key="fee-{{ $i }}" style="display:flex;gap:8px;align-items:center;">
                            <input type="text" wire:model="fees.{{ $i }}.label" placeholder="Profil (ex. Doctorant)"
                                   style="flex:2;border:1.5px solid var(--ukc-border);border-radius:10px;padding:10px 14px;font-size:14px;">
                            <input type="number" wire:model="fees.{{ $i }}.amount" placeholder="Montant" min="0"
                                   style="flex:1;border:1.5px solid var(--ukc-border);border-radius:10px;padding:10px 14px;font-size:14px;">
                            <button type="button" class="ukc-btn ukc-btn--danger ukc-btn--sm" wire:click="removeFee({{ $i }})">
                                <x-ukc.icon name="close" :size="16" />
                            </button>
                        </div>
                    @endforeach
                </div>
                <button type="button" class="ukc-btn ukc-btn--ghost ukc-btn--sm" wire:click="addFee" style="margin-top:10px;">
                    <x-ukc.icon name="plus" :size="16" /> Ajouter un tarif
                </button>
            </div>
        </div>

        {{-- Comités --}}
        <div class="ukc-card" style="margin-bottom:18px;">
            <h2><x-ukc.icon name="users" :size="20" /> Comités</h2>
            <div style="display:flex;flex-direction:column;gap:8px;">
                @foreach ($committees as $i => $c)
                    <div wire:key="com-{{ $i }}" style="display:grid;grid-template-columns:1fr 1.2fr 1fr 1.2fr auto;gap:8px;align-items:center;">
                        <select wire:model="committees.{{ $i }}.role" style="border:1.5px solid var(--ukc-border);border-radius:10px;padding:9px 10px;font-size:13px;">
                            @foreach ($committeeRoles as $key => $label)<option value="{{ $key }}">{{ $label }}</option>@endforeach
                        </select>
                        <input type="text" wire:model="committees.{{ $i }}.person_name" placeholder="Nom Prénom" style="border:1.5px solid var(--ukc-border);border-radius:10px;padding:9px 12px;font-size:13px;">
                        <input type="text" wire:model="committees.{{ $i }}.person_title" placeholder="Fonction/grade" style="border:1.5px solid var(--ukc-border);border-radius:10px;padding:9px 12px;font-size:13px;">
                        <input type="text" wire:model="committees.{{ $i }}.institution" placeholder="Institution" style="border:1.5px solid var(--ukc-border);border-radius:10px;padding:9px 12px;font-size:13px;">
                        <button type="button" class="ukc-btn ukc-btn--danger ukc-btn--sm" wire:click="removeCommittee({{ $i }})"><x-ukc.icon name="close" :size="14" /></button>
                    </div>
                @endforeach
            </div>
            <button type="button" class="ukc-btn ukc-btn--ghost ukc-btn--sm" wire:click="addCommittee" style="margin-top:10px;">
                <x-ukc.icon name="plus" :size="16" /> Ajouter un membre
            </button>
        </div>

        {{-- Partenaires --}}
        <div class="ukc-card" style="margin-bottom:18px;">
            <h2><x-ukc.icon name="external" :size="20" /> Partenaires</h2>
            <div style="display:flex;flex-direction:column;gap:8px;">
                @foreach ($partners as $i => $p)
                    <div wire:key="par-{{ $i }}" style="display:grid;grid-template-columns:1.2fr 1.4fr 1.4fr .8fr auto;gap:8px;align-items:center;">
                        <input type="text" wire:model="partners.{{ $i }}.name" placeholder="Nom" style="border:1.5px solid var(--ukc-border);border-radius:10px;padding:9px 12px;font-size:13px;">
                        <input type="text" wire:model="partners.{{ $i }}.url" placeholder="https://…" style="border:1.5px solid var(--ukc-border);border-radius:10px;padding:9px 12px;font-size:13px;">
                        <input type="text" wire:model="partners.{{ $i }}.logo_url" placeholder="URL du logo" style="border:1.5px solid var(--ukc-border);border-radius:10px;padding:9px 12px;font-size:13px;">
                        <input type="text" wire:model="partners.{{ $i }}.level" placeholder="Niveau" style="border:1.5px solid var(--ukc-border);border-radius:10px;padding:9px 12px;font-size:13px;">
                        <button type="button" class="ukc-btn ukc-btn--danger ukc-btn--sm" wire:click="removePartner({{ $i }})"><x-ukc.icon name="close" :size="14" /></button>
                    </div>
                @endforeach
            </div>
            <button type="button" class="ukc-btn ukc-btn--ghost ukc-btn--sm" wire:click="addPartner" style="margin-top:10px;">
                <x-ukc.icon name="plus" :size="16" /> Ajouter un partenaire
            </button>
        </div>

        {{-- FAQ --}}
        <div class="ukc-card" style="margin-bottom:18px;">
            <h2><x-ukc.icon name="info" :size="20" /> FAQ</h2>
            <div style="display:flex;flex-direction:column;gap:12px;">
                @foreach ($faq as $i => $f)
                    <div wire:key="faq-{{ $i }}" style="display:flex;gap:8px;align-items:flex-start;">
                        <div style="flex:1;display:flex;flex-direction:column;gap:6px;">
                            <input type="text" wire:model="faq.{{ $i }}.question" placeholder="Question" style="border:1.5px solid var(--ukc-border);border-radius:10px;padding:9px 12px;font-size:13px;">
                            <textarea wire:model="faq.{{ $i }}.answer" placeholder="Réponse" rows="2" style="border:1.5px solid var(--ukc-border);border-radius:10px;padding:9px 12px;font-size:13px;"></textarea>
                        </div>
                        <button type="button" class="ukc-btn ukc-btn--danger ukc-btn--sm" wire:click="removeFaq({{ $i }})"><x-ukc.icon name="close" :size="14" /></button>
                    </div>
                @endforeach
            </div>
            <button type="button" class="ukc-btn ukc-btn--ghost ukc-btn--sm" wire:click="addFaq" style="margin-top:10px;">
                <x-ukc.icon name="plus" :size="16" /> Ajouter une question
            </button>
        </div>

        <div class="ukc-form-actions" style="margin-top:0;border-top:none;padding-top:0;">
            <button type="submit" class="ukc-btn ukc-btn--blue" wire:loading.attr="disabled">
                <span wire:loading.remove wire:target="save">Enregistrer</span>
                <span wire:loading wire:target="save">Enregistrement…</span>
            </button>
            <a href="{{ route('admin.editions.index') }}" wire:navigate class="ukc-btn ukc-btn--ghost">Annuler</a>
        </div>
    </form>
</div>
