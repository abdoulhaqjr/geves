<x-layouts.participant title="Détail de la soumission" subtitle="Suivi, retours & resoumission">
    @php
        $s = $submission;
        $words = preg_match_all('/\p{L}+/u', strip_tags((string) $s->abstract));
        $decided = in_array($s->status, ['accepted', 'rejected'], true);
        $authors = collect($s->authors ?? []);
        $mainAuthor = $authors->firstWhere('corresponding', true) ?? $authors->first();
        $coauthors = $authors->filter(fn ($a) => empty($a['corresponding']));
        $fmtAuthor = fn ($a) => trim(($a['name'] ?? '').(filled($a['affiliation'] ?? null) ? ' ('.$a['affiliation'].')' : ''));
        $kw = collect(is_array($s->keywords) ? $s->keywords : preg_split('/\s*,\s*/', (string) $s->keywords, -1, PREG_SPLIT_NO_EMPTY))->filter();
    @endphp

    <div class="account-submission-detail" x-data="{ slotModal: false }" @keydown.escape.window="slotModal = false">
        {{-- Barre de retour + statut --}}
        <div style="display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap;margin-bottom:14px;">
            <a href="{{ route('account.submissions.index') }}" class="ukc-btn ukc-btn--ghost ukc-btn--sm">← Mes soumissions</a>
            <div style="display:flex;gap:8px;flex-wrap:wrap;">
                <span class="ukc-pill ukc-pill--{{ $s->statusPill() }}">{{ $s->statusLabel() }}</span>
                <span class="ukc-pill ukc-pill--muted" style="font-family:ui-monospace,monospace;">{{ $s->ref_code }}</span>
            </div>
        </div>

        {{-- En-tête + parcours --}}
        <div class="ukc-card" style="margin-bottom:18px;">
            <div class="sub-header">
                <div class="title-block">
                    <h1>{{ $s->title }}</h1>
                    <div class="meta">
                        <span>{{ $s->type === 'poster' ? 'Poster' : 'Orale' }}</span><span>·</span>
                        <span>{{ $s->track ?: 'Axe non précisé' }}</span><span>·</span>
                        <span>Déposée le {{ $s->created_at?->format('d/m/Y') ?? '—' }}</span>
                    </div>
                </div>
            </div>

            <div class="status-track" aria-label="Parcours de la soumission">
                <div class="step done"><div class="lbl">Déposée</div></div>
                <div class="step done"><div class="lbl">Reçue</div></div>
                <div @class(['step', 'done' => $decided || $s->status === 'revision', 'active' => $s->status === 'under_review'])><div class="lbl">En évaluation</div></div>
                <div @class(['step', 'done' => $decided, 'active' => $s->status === 'revision'])><div class="lbl">Décision</div></div>
                <div @class(['step', 'done' => (bool) $slot, 'active' => $s->status === 'accepted' && ! $slot])><div class="lbl">Créneau</div></div>
                <div class="step"><div class="lbl">Présentée</div></div>
            </div>
            <p class="hint">
                @if ($s->status === 'accepted')
                    Communication <strong>acceptée</strong>. Finalisez votre inscription et choisissez votre créneau.
                @elseif ($s->status === 'rejected')
                    Après examen, votre communication n’a pas été retenue pour cette édition.
                @elseif ($s->status === 'revision')
                    Une <strong>correction</strong> est demandée : modifiez puis renvoyez votre communication.
                @else
                    Vous recevrez un e-mail à chaque changement d’état de votre soumission.
                @endif
            </p>
        </div>

        {{-- Correction demandée : resoumission (fonctionnalité réelle) --}}
        @if ($s->canBeRevised())
            <div class="ukc-card" style="margin-bottom:18px;border-color:#fde68a;background:#fffbeb;">
                <div class="ukc-alert ukc-alert--warn" style="margin-bottom:16px;">
                    <span class="ico"><x-ukc.icon name="pen" :size="18" /></span>
                    <div>Une <strong>correction</strong> vous est demandée. Modifiez votre communication ci-dessous puis renvoyez-la.</div>
                </div>
                <a href="#resubmit" class="ukc-btn ukc-btn--blue ukc-btn--sm">Corriger &amp; renvoyer</a>
            </div>
        @endif

        {{-- Version définitive (camera-ready) — après acceptation --}}
        @if ($s->status === 'accepted')
            @php($cr = $s->cameraReady())
            <div class="ukc-card" style="margin-bottom:18px;" id="camera-ready">
                <h2><x-ukc.icon name="file" :size="18" /> Version définitive</h2>
                <p style="color:var(--ukc-muted);font-size:14px;margin:-4px 0 14px;">
                    Votre communication est acceptée : déposez la <strong>version finale</strong> (« prête à publier ») conforme au gabarit, pour le livre des résumés / les actes.
                </p>

                @if ($cr)
                    <div class="ukc-alert ukc-alert--ok" style="margin-bottom:14px;">
                        <span class="ico"><x-ukc.icon name="check-circle" :size="18" /></span>
                        <div>
                            Version définitive déposée le {{ \Illuminate\Support\Carbon::parse($cr['submitted_at'])->translatedFormat('d F Y à H:i') }}.
                            <a href="{{ $cr['file_url'] }}" target="_blank" rel="noopener" style="color:var(--ukc-blue);font-weight:600;">Voir le fichier</a>
                        </div>
                    </div>
                @endif

                <form method="POST" action="{{ route('account.submissions.camera-ready', $s) }}" enctype="multipart/form-data">
                    @csrf
                    <div class="ukc-field" style="max-width:520px;">
                        <label for="cr_file">Fichier de la version définitive <span class="hint">(PDF, DOC ou DOCX — 8 Mo max)</span></label>
                        <input type="file" id="cr_file" name="file" accept=".pdf,.doc,.docx" required>
                        @error('file') <span class="error">{{ $message }}</span> @enderror
                    </div>

                    <div style="display:flex;flex-direction:column;gap:8px;margin:14px 0;">
                        @foreach (\App\Models\Submission::CAMERA_READY_CHECKLIST as $key => $label)
                            <label style="display:flex;align-items:flex-start;gap:9px;font-size:13.5px;cursor:pointer;">
                                <input type="checkbox" name="checklist[{{ $key }}]" value="1" style="margin-top:2px;flex-shrink:0;" required>
                                <span>{{ $label }}</span>
                            </label>
                        @endforeach
                        @error('checklist.*') <span class="error">{{ $message }}</span> @enderror
                    </div>

                    <button type="submit" class="ukc-btn ukc-btn--blue ukc-btn--sm">{{ $cr ? 'Remplacer la version définitive' : 'Déposer la version définitive' }}</button>
                </form>
            </div>
        @endif

        <div class="layout-2">
            {{-- Colonne principale --}}
            <div style="display:flex;flex-direction:column;gap:18px;">
                <div class="ukc-card">
                    <h2>Informations</h2>
                    <dl class="kv">
                        <dt>Référence</dt><dd><strong>{{ $s->ref_code }}</strong></dd>
                        <dt>Type</dt><dd>{{ $s->type === 'poster' ? 'Poster' : 'Communication orale' }}</dd>
                        <dt>Atelier / axe</dt><dd>{{ $s->track ?: '—' }}</dd>
                        <dt>Langue</dt><dd>{{ $s->meta['language'] ?? 'Français' }}</dd>
                        <dt>Mots (résumé)</dt><dd>{{ $words }} / {{ $abstractMax }}</dd>
                        <dt>Auteur principal</dt><dd>{{ $mainAuthor ? $fmtAuthor($mainAuthor) : ($s->correspondingAuthorName() ?: '—') }}</dd>
                        @if ($coauthors->isNotEmpty())
                            <dt>Co-auteurs</dt><dd>{{ $coauthors->map($fmtAuthor)->filter()->implode(', ') }}</dd>
                        @endif
                    </dl>

                    <h3 style="font-size:14px;font-weight:800;margin:16px 0 8px;">Résumé</h3>
                    @if (filled($s->abstract))
                        <div class="abstract">{{ strip_tags((string) $s->abstract) }}</div>
                    @else
                        <div class="abstract" style="color:var(--ukc-muted);font-style:italic;">Aucun résumé n’a été enregistré pour cette communication.</div>
                    @endif

                    <h3 style="font-size:14px;font-weight:800;margin:16px 0 8px;">Mots-clés</h3>
                    @if ($kw->isNotEmpty())
                        <div class="keywords">@foreach ($kw as $k)<span>{{ $k }}</span>@endforeach</div>
                    @else
                        <p class="hint" style="margin:0;">Aucun mot-clé renseigné.</p>
                    @endif
                </div>

                <div class="ukc-card">
                    <h2>Fichiers</h2>
                    @if ($s->file_url)
                        <div class="file-row">
                            <div class="ico"><x-ukc.icon name="file" :size="20" /></div>
                            <div style="flex:1;min-width:0;">
                                <div class="name" style="word-break:break-all;">{{ basename(parse_url($s->file_url, PHP_URL_PATH) ?: $s->file_url) }}</div>
                                <div class="meta">PDF · Déposé le {{ $s->created_at?->format('d/m/Y') }}</div>
                            </div>
                            <a href="{{ $s->file_url }}" target="_blank" class="ukc-btn ukc-btn--outline ukc-btn--sm">Télécharger</a>
                        </div>
                    @endif
                    <div class="file-row">
                        <div class="ico"><x-ukc.icon name="diploma" :size="20" /></div>
                        <div style="flex:1;min-width:0;">
                            <div class="name">Attestation de soumission</div>
                            <div class="meta">PDF · Générée automatiquement · QR de vérification</div>
                        </div>
                        @if ($soumissionAtt)
                            <div style="display:flex;gap:6px;flex-wrap:wrap;">
                                <a href="{{ route('attestation.document', $soumissionAtt->uuid) }}" target="_blank" class="ukc-btn ukc-btn--outline ukc-btn--sm">Télécharger</a>
                                <a href="{{ route('attestation.verify', $soumissionAtt->uuid) }}" target="_blank" class="ukc-btn ukc-btn--ghost ukc-btn--sm">Vérifier</a>
                            </div>
                        @else
                            <span class="ukc-pill ukc-pill--muted">En attente</span>
                        @endif
                    </div>
                    <p class="hint">En cas d’erreur matérielle sur le titre, les auteurs ou le fichier, contactez le secrétariat avant la décision du comité.</p>
                </div>

                <div class="ukc-card">
                    <h2>Messages du comité</h2>
                    @forelse ($feedback as $fb)
                        <div class="msg-box">
                            <div class="from">{{ $fb['label'] ?? 'Comité scientifique' }}</div>
                            <p>{{ $fb['comments'] }}</p>
                        </div>
                    @empty
                        <div class="msg-box">
                            <div class="from">Secrétariat scientifique</div>
                            <p>Votre soumission a bien été enregistrée et transmise aux relecteurs. Aucune action n’est requise de votre part pour le moment.</p>
                        </div>
                    @endforelse
                    <p class="hint">Les avis détaillés des relecteurs restent confidentiels. Seule la décision finale et d’éventuels commentaires destinés aux auteurs vous sont communiqués.</p>
                </div>
            </div>

            {{-- Colonne latérale --}}
            <div style="display:flex;flex-direction:column;gap:18px;">
                <div class="ukc-card">
                    <h2>Historique</h2>
                    <ul class="timeline">
                        <li>
                            <div class="when">{{ $s->created_at?->format('d/m/Y · H:i') }}</div>
                            <div class="what">Soumission déposée</div>
                            <div class="detail">Formulaire validé@if($s->file_url) · fichier résumé joint @endif</div>
                        </li>
                        <li>
                            <div class="when">{{ $s->created_at?->format('d/m/Y · H:i') }}</div>
                            <div class="what">Accusé de réception</div>
                            <div class="detail">E-mail envoyé@if($soumissionAtt) · attestation de soumission générée @endif</div>
                        </li>
                        <li @class(['muted' => $s->status === 'submitted'])>
                            <div class="when">{{ $s->status === 'submitted' ? 'À venir' : 'En cours' }}</div>
                            <div class="what">Évaluation scientifique</div>
                            <div class="detail">{{ $decided ? 'Avis rendus' : 'En attente des avis des relecteurs' }}</div>
                        </li>
                        <li @class(['muted' => ! $decided && $s->status !== 'revision'])>
                            <div class="when">{{ $decided ? $s->updated_at?->format('d/m/Y') : 'À venir' }}</div>
                            <div class="what">Notification de décision</div>
                            <div class="detail">
                                @if ($s->status === 'accepted') Acceptée
                                @elseif ($s->status === 'rejected') Non retenue
                                @elseif ($s->status === 'revision') Corrections demandées
                                @else Acceptée / refusée / révisions @endif
                            </div>
                        </li>
                        <li @class(['muted' => ! $slot])>
                            <div class="when">{{ $slot ? 'Confirmé' : 'À venir' }}</div>
                            <div class="what">Affectation de créneau</div>
                            <div class="detail">Après acceptation et paiement de l’inscription</div>
                        </li>
                    </ul>
                </div>

                <div class="ukc-card">
                    <h2>Créneau de passage</h2>
                    @if ($slot)
                        <div class="slot-card">
                            <strong>{{ $slot->session_label ?: 'Présentation' }}</strong>
                            @if ($slot->starts_at){{ $slot->starts_at->translatedFormat('d F, H:i') }}@endif
                            @if ($slot->room) · Salle {{ $slot->room }}@endif
                            @if ($slot->chosen_by_participant) <span class="ukc-pill ukc-pill--info" style="margin-left:6px;">Choisi</span>@endif
                        </div>
                        @if ($slot->starts_at)
                            <div style="display:flex;flex-wrap:wrap;gap:8px;margin-top:12px;">
                                @if ($googleCal)
                                    <a href="{{ $googleCal }}" target="_blank" rel="noopener" class="ukc-btn ukc-btn--outline ukc-btn--sm">
                                        <x-ukc.icon name="calendar" :size="15" /> Ajouter à Google Agenda
                                    </a>
                                @endif
                                <a href="{{ route('account.agenda.ics') }}" class="ukc-btn ukc-btn--ghost ukc-btn--sm">
                                    <x-ukc.icon name="download" :size="15" /> Mon agenda (.ics)
                                </a>
                            </div>
                        @endif
                        @if ($availableSlots->count())
                            <button type="button" class="ukc-btn ukc-btn--outline ukc-btn--sm" style="margin-top:12px;" @click="slotModal = true">
                                <x-ukc.icon name="calendar" :size="16" /> Changer de créneau
                            </button>
                        @endif
                    @elseif ($s->status === 'accepted' && $availableSlots->count())
                        <p class="hint" style="margin-top:0;">Aucun créneau choisi pour l’instant.</p>
                        <button type="button" class="ukc-btn ukc-btn--blue ukc-btn--sm" style="margin-top:8px;" @click="slotModal = true">
                            <x-ukc.icon name="calendar" :size="16" /> Choisir un créneau
                        </button>
                    @else
                        <div class="slot-card">
                            <strong>Pas encore attribué</strong>
                            Un créneau (jour, horaire, session, salle) sera proposé après acceptation et règlement des frais d’inscription, selon l’axe de votre communication.
                        </div>
                    @endif
                </div>

                <div class="ukc-card">
                    <h2>Documents liés</h2>
                    <div style="display:flex;flex-direction:column;gap:8px;">
                        <a href="{{ route('account.documents') }}" class="ukc-btn ukc-btn--outline ukc-btn--sm" style="justify-content:flex-start;">Attestations &amp; reçus</a>
                        <a href="{{ route('account.payment', $s->edition) }}" class="ukc-btn ukc-btn--outline ukc-btn--sm" style="justify-content:flex-start;">Inscription &amp; paiement</a>
                    </div>
                </div>
            </div>
        </div>

        {{-- Formulaire de resoumission (correction) --}}
        @if ($s->canBeRevised())
            <div class="ukc-card" id="resubmit" style="margin-top:18px;">
                <h2>Corriger &amp; renvoyer</h2>
                <form method="POST" action="{{ route('account.submissions.resubmit', $s) }}" enctype="multipart/form-data" class="ukc-form-grid">
                    @csrf
                    <div class="ukc-field full">
                        <label for="title">Titre <span class="req">*</span></label>
                        <input type="text" id="title" name="title" value="{{ old('title', $s->title) }}">
                        @error('title') <span class="error">{{ $message }}</span> @enderror
                    </div>
                    <div class="ukc-field full">
                        <label for="abstract">Résumé <span class="req">*</span></label>
                        <textarea id="abstract" name="abstract" rows="6">{{ old('abstract', $s->abstract) }}</textarea>
                        @error('abstract') <span class="error">{{ $message }}</span> @enderror
                    </div>
                    <div class="ukc-field full">
                        <label for="keywords">Mots-clés <span class="hint">(séparés par des virgules)</span></label>
                        <input type="text" id="keywords" name="keywords" value="{{ old('keywords', is_array($s->keywords) ? implode(', ', $s->keywords) : $s->keywords) }}">
                        @error('keywords') <span class="error">{{ $message }}</span> @enderror
                    </div>
                    <div class="ukc-field full">
                        <label for="file">Nouveau fichier (PDF, optionnel)</label>
                        <input type="file" id="file" name="file" accept=".pdf">
                        @error('file') <span class="error">{{ $message }}</span> @enderror
                    </div>
                    <div class="ukc-field full">
                        <button type="submit" class="ukc-btn ukc-btn--blue"><x-ukc.icon name="upload" :size="16" /> Renvoyer la communication</button>
                    </div>
                </form>
            </div>
        @endif

        {{-- Modale : choix / changement de créneau --}}
        @if ($availableSlots->count() && ($slot || $s->status === 'accepted'))
            <div class="ukc-modal" x-show="slotModal" x-cloak @click.self="slotModal = false" style="display:none;">
                <div class="box">
                    <div class="box-head">
                        <span>Choisir un créneau de passage</span>
                        <button type="button" @click="slotModal = false" style="font-size:22px;color:var(--ukc-muted);background:none;border:none;cursor:pointer;">&times;</button>
                    </div>
                    <div class="box-body">
                        @if ($slot)
                            <p style="font-size:.88rem;color:var(--ukc-muted);margin:0 0 12px;">
                                Créneau actuel : <strong>{{ $slot->session_label ?: 'Présentation' }}@if ($slot->starts_at) — {{ $slot->starts_at->translatedFormat('d F, H:i') }}@endif</strong>. Sélectionnez-en un autre ci-dessous.
                            </p>
                        @endif
                        <div style="max-height:56vh;overflow-y:auto;display:flex;flex-direction:column;gap:8px;padding-right:2px;">
                            @foreach ($availableSlots as $av)
                                <form method="POST" action="{{ route('account.submissions.choose-slot', $s) }}">
                                    @csrf
                                    <input type="hidden" name="slot_id" value="{{ $av->id }}">
                                    <button type="submit" class="ukc-btn ukc-btn--outline ukc-btn--sm" style="justify-content:space-between;width:100%;text-align:left;">
                                        <span>
                                            <strong>{{ $av->session_label ?: 'Créneau' }}</strong>
                                            @if ($av->starts_at)<span style="color:var(--ukc-muted);"> — {{ $av->starts_at->translatedFormat('d F, H:i') }}</span>@endif
                                            @if ($av->room)<span style="color:var(--ukc-muted);"> · Salle {{ $av->room }}</span>@endif
                                        </span>
                                        <x-ukc.icon name="chevron" :size="16" />
                                    </button>
                                </form>
                            @endforeach
                        </div>
                    </div>
                    <div class="box-foot">
                        <button type="button" class="ukc-btn ukc-btn--ghost" @click="slotModal = false">Fermer</button>
                    </div>
                </div>
            </div>
        @endif
    </div>
</x-layouts.participant>
