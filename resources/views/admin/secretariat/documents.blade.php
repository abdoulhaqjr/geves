<x-layouts.admin title="Documents" subtitle="Documents logistiques prêts à imprimer, remplis depuis l'événement">
    <div class="admin-page" x-data="{ panel: null }">
        @include('admin.secretariat._nav', ['current' => 'documents'])

        @if ($events->isEmpty())
            <div class="ukc-empty"><h3>Aucun événement</h3><p>Vous n'avez accès aux documents d'aucun événement.</p></div>
        @else
            {{-- Sélecteur d'événement (recharge la page sur le choix) --}}
            <form method="GET" class="ukc-field" style="max-width:520px;margin-bottom:18px;">
                <label>Événement</label>
                <select name="doc_event" onchange="this.form.submit()">
                    @foreach ($events as $ed)
                        <option value="{{ $ed->id }}" @selected($event && (int) $ed->id === (int) $event->id)>{{ $ed->series?->name ? $ed->series->name.' - ' : '' }}{{ $ed->title ?: $ed->year }}</option>
                    @endforeach
                </select>
            </form>

            @if (! $event)
                <div class="ukc-empty"><h3>Choisissez un événement</h3><p>Sélectionnez un événement pour imprimer ses documents.</p></div>
            @else
                <p style="font-size:13px;color:var(--ukc-muted);margin:-6px 0 14px;">Chaque document s'ouvre dans un nouvel onglet, prêt à imprimer (ou à enregistrer en PDF). Renseignez quelques paramètres pour l'adapter à votre besoin.</p>

                {{-- En-tête commun des documents : conçu une fois, il coiffe les documents imprimables. --}}
                <div class="ukc-card" style="margin-bottom:16px;display:flex;align-items:flex-start;justify-content:space-between;gap:14px;flex-wrap:wrap;">
                    <div>
                        <h3 style="margin:0 0 4px;">En-tête des documents</h3>
                        <p style="font-size:12.5px;color:var(--ukc-muted);margin:0;max-width:580px;">Coiffe les documents imprimables de cet événement (liste de présence, feuille d'émargement, ordre du jour) : organisation, mention et logo. Conçu une fois.</p>
                        <div style="margin-top:10px;font-size:13px;display:flex;align-items:center;gap:8px;flex-wrap:wrap;">
                            @if ($header['logo'])<img src="{{ \App\Support\Branding::mark() }}" alt="" style="height:24px;width:auto;">@endif
                            <strong>{{ $header['org'] }}</strong>
                            @if ($header['subtitle'])<span style="color:var(--ukc-muted);">· {{ $header['subtitle'] }}</span>@endif
                            <span class="ukc-pill ukc-pill--muted">{{ $header['align'] === 'center' ? 'centré' : 'aligné à gauche' }}</span>
                        </div>
                    </div>
                    <button type="button" class="ukc-btn ukc-btn--outline ukc-btn--sm" @click="panel='header'"><x-ukc.icon name="pen" :size="15" /> Personnaliser</button>
                </div>

                <div class="ukc-stats" style="grid-template-columns:repeat(auto-fill,minmax(240px,1fr));">
                    @foreach ($types as $key => $meta)
                        <button type="button" class="ukc-card sec-doc-card" @click="panel='{{ $key }}'" style="text-align:left;cursor:pointer;border:1px solid var(--ukc-border);">
                            <div style="display:flex;align-items:center;gap:10px;">
                                <span class="tw-stat__icon tw-stat__icon--slate"><x-ukc.icon :name="$meta['icon']" :size="18" /></span>
                                <span style="font-weight:700;">{{ $meta['label'] }}</span>
                            </div>
                            <p style="font-size:12.5px;color:var(--ukc-muted);margin:10px 0 0;line-height:1.45;">{{ $meta['hint'] }}</p>
                        </button>
                    @endforeach
                </div>

                @php($pr = fn ($type) => route('admin.secretariat.documents.print', ['event' => $event, 'type' => $type]))

                {{-- ===== En-tête des documents (conception) ===== --}}
                <x-ukc.slideover open="panel==='header'" onClose="panel=null" title="En-tête des documents">
                    <form method="POST" action="{{ route('admin.secretariat.documents.header', ['event' => $event]) }}" id="docHeaderForm">
                        @csrf
                        <p style="font-size:13px;color:var(--ukc-muted);margin:0 0 12px;">Cet en-tête apparaît en haut de chaque document imprimable de l'événement.</p>
                        <div class="ukc-field">
                            <label>Organisation (surtitre)</label>
                            <input type="text" name="doc_org" maxlength="160" value="{{ $header['org'] }}" placeholder="{{ config('app.name') }}">
                            <p class="ukc-hint">Petite ligne au-dessus du titre. Ex. : Université de Kara - Direction de la Recherche et de l'Innovation.</p>
                        </div>
                        <div class="ukc-field">
                            <label>Mention (optionnel)</label>
                            <input type="text" name="doc_subtitle" maxlength="160" value="{{ $header['subtitle'] }}" placeholder="Ex. : Comité d'organisation">
                        </div>
                        <div class="ukc-field">
                            <label>Alignement</label>
                            <select name="doc_align">
                                <option value="left" @selected($header['align'] === 'left')>Aligné à gauche</option>
                                <option value="center" @selected($header['align'] === 'center')>Centré</option>
                            </select>
                        </div>
                        <label style="display:flex;align-items:center;gap:8px;font-size:13.5px;cursor:pointer;margin-top:4px;">
                            <input type="checkbox" name="doc_logo" value="1" @checked($header['logo']) style="width:16px;height:16px;flex:none;">
                            Afficher le logo de la plateforme en haut
                        </label>
                    </form>
                    <x-slot:footer>
                        <button type="button" class="ukc-btn ukc-btn--ghost" @click="panel=null">Annuler</button>
                        <button type="submit" form="docHeaderForm" class="ukc-btn ukc-btn--green"><x-ukc.icon name="check" :size="16" /> Enregistrer</button>
                    </x-slot:footer>
                </x-ukc.slideover>

                {{-- ===== Liste de présence ===== --}}
                <x-ukc.slideover open="panel==='presence'" onClose="panel=null" title="Liste de présence">
                    <form method="GET" target="_blank" action="{{ $pr('presence') }}" id="docPresence" @submit="panel=null" x-data="{ blank: false }">
                        <p style="font-size:13px;color:var(--ukc-muted);margin:0 0 12px;">Participants de l'événement, triés par nom, avec une colonne d'émargement à signer.</p>
                        <div class="ukc-field">
                            <label>En-tête libre (optionnel)</label>
                            <input type="text" name="note" maxlength="140" placeholder="Ex. : Séance d'ouverture - 5 octobre 2026">
                        </div>
                        <label style="display:flex;align-items:center;gap:8px;font-size:13.5px;cursor:pointer;margin:2px 0 10px;">
                            <input type="checkbox" name="blank" value="1" x-model="blank" style="width:16px;height:16px;flex:none;">
                            Liste vierge (colonnes à remplir à la main, sans pré-remplir les participants)
                        </label>
                        <div class="ukc-field" x-show="blank">
                            <label>Nombre de lignes</label>
                            <input type="number" name="rows" min="1" max="80" value="25">
                        </div>
                    </form>
                    <x-slot:footer>
                        <button type="button" class="ukc-btn ukc-btn--ghost" @click="panel=null">Annuler</button>
                        <button type="submit" form="docPresence" class="ukc-btn ukc-btn--blue"><x-ukc.icon name="eye" :size="15" /> Ouvrir / imprimer</button>
                    </x-slot:footer>
                </x-ukc.slideover>

                {{-- ===== Feuille d'émargement ===== --}}
                <x-ukc.slideover open="panel==='emargement'" onClose="panel=null" title="Feuille d'émargement">
                    <form method="GET" target="_blank" action="{{ $pr('emargement') }}" id="docEmargement" @submit="panel=null" x-data="{ blank: false }">
                        <p style="font-size:13px;color:var(--ukc-muted);margin:0 0 12px;">Feuille à signer : en-tête à compléter à la main et colonne Signature.</p>
                        <div class="ukc-field">
                            <label>En-tête libre (séance / date)</label>
                            <input type="text" name="note" maxlength="140" placeholder="Séance : _______  Date : _______">
                        </div>
                        <label style="display:flex;align-items:center;gap:8px;font-size:13.5px;cursor:pointer;margin:2px 0 10px;">
                            <input type="checkbox" name="blank" value="1" x-model="blank" style="width:16px;height:16px;flex:none;">
                            Liste vierge (sans pré-remplir les participants)
                        </label>
                        <div class="ukc-field" x-show="! blank">
                            <label>Lignes vierges supplémentaires</label>
                            <input type="number" name="extra" min="0" max="40" value="8">
                            <p class="ukc-hint">Ajoutées sous les participants inscrits, pour les personnes qui se présentent.</p>
                        </div>
                        <div class="ukc-field" x-show="blank">
                            <label>Nombre de lignes</label>
                            <input type="number" name="rows" min="1" max="80" value="25">
                        </div>
                    </form>
                    <x-slot:footer>
                        <button type="button" class="ukc-btn ukc-btn--ghost" @click="panel=null">Annuler</button>
                        <button type="submit" form="docEmargement" class="ukc-btn ukc-btn--blue"><x-ukc.icon name="eye" :size="15" /> Ouvrir / imprimer</button>
                    </x-slot:footer>
                </x-ukc.slideover>

                {{-- ===== Ordre du jour ===== --}}
                <x-ukc.slideover open="panel==='ordre_jour'" onClose="panel=null" title="Ordre du jour">
                    <form method="GET" target="_blank" action="{{ $pr('ordre_jour') }}" id="docOrdre" @submit="panel=null">
                        <p style="font-size:13px;color:var(--ukc-muted);margin:0 0 12px;">Déroulé d'une journée : séances, horaires, salles, modérateurs et rapporteurs.</p>
                        <div class="ukc-field">
                            <label>Jour</label>
                            @if ($days->isEmpty())
                                <p class="ukc-hint">Aucune séance datée au programme de cet événement.</p>
                            @else
                                <select name="day">
                                    @foreach ($days as $d)
                                        <option value="{{ $d }}">{{ \Illuminate\Support\Carbon::parse($d)->translatedFormat('l j F Y') }}</option>
                                    @endforeach
                                </select>
                            @endif
                        </div>
                    </form>
                    <x-slot:footer>
                        <button type="button" class="ukc-btn ukc-btn--ghost" @click="panel=null">Annuler</button>
                        <button type="submit" form="docOrdre" class="ukc-btn ukc-btn--blue" @disabled($days->isEmpty())><x-ukc.icon name="eye" :size="15" /> Ouvrir / imprimer</button>
                    </x-slot:footer>
                </x-ukc.slideover>

                {{-- ===== Affiche directionnelle ===== --}}
                <x-ukc.slideover open="panel==='affiche'" onClose="panel=null" title="Affiche directionnelle">
                    <form method="GET" target="_blank" action="{{ $pr('affiche') }}" id="docAffiche" @submit="panel=null">
                        <p style="font-size:13px;color:var(--ukc-muted);margin:0 0 12px;">Un panneau par salle (« Atelier → Salle »), en gros caractères, pour orienter les participants.</p>
                        <div class="ukc-field">
                            <label>Jour</label>
                            <select name="day">
                                <option value="">Toutes les salles</option>
                                @foreach ($days as $d)
                                    <option value="{{ $d }}">{{ \Illuminate\Support\Carbon::parse($d)->translatedFormat('l j F Y') }}</option>
                                @endforeach
                            </select>
                        </div>
                        @if ($rooms->isEmpty())
                            <p class="ukc-hint">Aucune salle renseignée sur les séances de cet événement.</p>
                        @endif
                    </form>
                    <x-slot:footer>
                        <button type="button" class="ukc-btn ukc-btn--ghost" @click="panel=null">Annuler</button>
                        <button type="submit" form="docAffiche" class="ukc-btn ukc-btn--blue" @disabled($rooms->isEmpty())><x-ukc.icon name="eye" :size="15" /> Ouvrir / imprimer</button>
                    </x-slot:footer>
                </x-ukc.slideover>

                {{-- ===== Chevalets nominatifs ===== --}}
                <x-ukc.slideover open="panel==='chevalets'" onClose="panel=null" title="Chevalets nominatifs">
                    <form method="GET" target="_blank" action="{{ $pr('chevalets') }}" id="docChevalets" @submit="panel=null">
                        <p style="font-size:13px;color:var(--ukc-muted);margin:0 0 12px;">Cartes pliables (nom + fonction), à poser devant chaque intervenant.</p>
                        <div class="ukc-field">
                            <label>Pour qui</label>
                            <select name="who">
                                <option value="both">Modérateurs et rapporteurs</option>
                                <option value="moderators">Modérateurs seulement</option>
                                <option value="rapporteurs">Rapporteurs seulement</option>
                            </select>
                        </div>
                    </form>
                    <x-slot:footer>
                        <button type="button" class="ukc-btn ukc-btn--ghost" @click="panel=null">Annuler</button>
                        <button type="submit" form="docChevalets" class="ukc-btn ukc-btn--blue"><x-ukc.icon name="eye" :size="15" /> Ouvrir / imprimer</button>
                    </x-slot:footer>
                </x-ukc.slideover>
            @endif
        @endif
    </div>
</x-layouts.admin>
