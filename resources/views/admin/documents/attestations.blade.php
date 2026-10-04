<x-layouts.admin title="Documents" :subtitle="$edition ? 'Édition active · '.($edition->title ?: $edition->year) : 'Attestations, badges & livre des résumés'">
    <div class="admin-page admin-documents" x-data="{ livre: 'main', newAtt: false, newBadge: false, del: null, typed: '' }">
        {{-- Sélecteur d'édition partagé + vérification publique --}}
        <div style="display:flex;align-items:flex-end;justify-content:space-between;gap:12px;flex-wrap:wrap;margin-bottom:16px;">
            <div class="ukc-field" style="max-width:340px;margin:0;">
                <label>Édition</label>
                <select onchange="window.location='{{ route('admin.documents.attestations') }}?edition_id='+this.value">
                    <option value="">- Choisir -</option>
                    @foreach ($editions as $ed)<option value="{{ $ed->id }}" @selected((string) $ed->id === $edition_id)>{{ $ed->title ?: $ed->year }}</option>@endforeach
                </select>
            </div>
            <a class="ukc-btn ukc-btn--ghost ukc-btn--sm" href="{{ route('attestation.verify.landing') }}" target="_blank">
                <x-ukc.icon name="check-circle" :size="16" /> Vérification publique
            </a>
        </div>

        @if (! $edition)
            <div class="ukc-empty"><h3>Choisissez une édition</h3><p>Sélectionnez une édition pour gérer ses documents.</p></div>
        @else
            {{-- Onglets principaux : des liens, comme les sous-onglets. Les panneaux
                 sont rendus côté serveur d'après ?tab=, l'URL doit donc changer. --}}
            <div class="ukc-nav-docs">
                <a href="{{ route('admin.documents.attestations', ['edition_id' => $edition->id, 'tab' => 'attestations', 'stab' => 'modeles']) }}" @class(['active' => $tab === 'attestations'])>Attestations</a>
                <a href="{{ route('admin.documents.attestations', ['edition_id' => $edition->id, 'tab' => 'badges', 'stab' => 'modeles']) }}" @class(['active' => $tab === 'badges'])>Badges</a>
                <a href="{{ route('admin.documents.attestations', ['edition_id' => $edition->id, 'tab' => 'livre']) }}" @class(['active' => $tab === 'livre'])>Livre des résumés</a>
            </div>

            {{-- ======================= ATTESTATIONS ======================= --}}
            <div @if ($tab !== 'attestations') style="display:none;" @endif>
                {{-- Sous-onglets portés par l'URL (?tab=attestations&stab=…) : chaque
                     onglet a son lien, et « délivrées » garde filtres/tri/pagination. --}}
                @php($stabBase = ['edition_id' => $edition->id, 'tab' => 'attestations'])
                <div class="ukc-tabs">
                    <a href="{{ route('admin.documents.attestations', $stabBase + ['stab' => 'modeles']) }}" @class(['active' => $stab === 'modeles'])>Modèles</a>
                    <a href="{{ route('admin.documents.attestations', $stabBase + ['stab' => 'delivrees']) }}" @class(['active' => $stab === 'delivrees'])>
                        Attestations délivrées <span class="nav-count">{{ $counts['issued'] }}</span>
                    </a>
                    <a href="{{ route('admin.documents.attestations', $stabBase + ['stab' => 'options']) }}" @class(['active' => $stab === 'options'])>Options générales</a>
                </div>

                @if ($stab === 'delivrees')
                    @include('admin.documents._issued')
                @endif

                @if ($stab === 'modeles')
                    <div class="ukc-toolbar">
                        <button type="button" class="ukc-btn ukc-btn--blue ukc-btn--sm" @click="newAtt = true">
                            <x-ukc.icon name="plus" :size="16" /> Nouveau modèle
                        </button>
                        <span class="sep"></span>
                        <span style="font-size:13px;color:var(--ukc-muted);">Templates liés à l’édition · génération PDF + QR de vérification. Émission idempotente.</span>
                    </div>

                    {{-- ⚠️ On est sur l'écran qui ÉMET : il doit dire ce que l'émission
                         déclenche. En ligne, chaque attestation part chez son
                         bénéficiaire ; sur papier, rien ne part et c'est l'export qui
                         prend le relais. Sans ce bandeau, le même clic a deux effets
                         très différents sans que rien ne le signale. --}}
                    <div class="ukc-card" style="margin-bottom:14px;display:flex;gap:12px;align-items:flex-start;flex-wrap:wrap;">
                        <span class="ukc-pill {{ $attPaper ? 'ukc-pill--warn' : 'ukc-pill--ok' }}">{{ $attDeliveryLabel }}</span>
                        <div style="font-size:13px;color:var(--ukc-muted);flex:1;min-width:260px;line-height:1.5;">
                            @if ($attPaper)
                                Rien n’est envoyé : émettez le lot ici, puis imprimez-le depuis
                                <a href="{{ route('admin.documents.attestations', ['edition_id' => $edition->id, 'tab' => 'attestations', 'stab' => 'delivrees']) }}">Attestations délivrées</a>
                                (lot PDF + liste d’émargement). L’attestation reste consultable dans l’espace du participant.
                            @else
                                Chaque attestation émise part chez son bénéficiaire (notification, e-mail, SMS si activé)
                                et apparaît dans son espace. Les attestations de communication s’émettent aussi
                                <strong>au fil des passages</strong>, quand le modérateur les valide en séance.
                            @endif
                            <br>Attestation de communication : {{ \Illuminate\Support\Str::lower($attScopeLabel) }}.
                            <a href="{{ route('admin.documents.attestations', ['edition_id' => $edition->id, 'tab' => 'attestations', 'stab' => 'options']) }}">Changer</a>
                        </div>
                    </div>

                    <div class="ukc-table-wrap">
                        <table class="ukc-table">
                            <thead>
                                <tr><th>Type</th><th>Libellé</th><th>Portée</th><th>Destinataires</th><th>QR / DIDs</th><th>Statut</th><th style="text-align:right;">Actions</th></tr>
                            </thead>
                            <tbody>
                                @foreach ($rows as $key => $row)
                                    <tr>
                                        <td><span class="ukc-pill ukc-pill--info">{{ $row['label'] }}</span></td>
                                        <td><strong>{{ $row['title'] }}</strong><div style="font-size:12px;color:var(--ukc-muted);">{{ $row['sub'] }}</div></td>
                                        <td>{{ $row['scope'] }}</td>
                                        <td>
                                            <span class="ukc-stat">{{ $row['eligible'] }}</span>
                                            {{-- ⚠️ Un nombre qui baisse sans explication se lit comme une
                                                 panne : on dit POURQUOI il manque des gens. --}}
                                            @if ($row['excluded'] > 0)
                                                <div style="font-size:11.5px;color:var(--ukc-muted);margin-top:2px;"
                                                     title="Auteurs non pointés présents à la séance où leur communication est programmée.">
                                                    {{ $row['excluded'] }} écarté(s) · absents
                                                </div>
                                            @endif
                                        </td>
                                        <td>Oui</td>
                                        <td>
                                            @if ($row['status'] === 'Actif')<span class="ukc-pill ukc-pill--ok" title="Des destinataires sont éligibles : vous pouvez émettre.">Prêt</span>@else<span class="ukc-pill ukc-pill--muted" title="Le modèle est complet et modifiable ; aucun destinataire n’est éligible pour l’instant.">Sans destinataire</span>@endif
                                        </td>
                                        <td>
                                            <div class="ukc-actions" style="justify-content:flex-end;">
                                                @if ($row['issueFrom'])
                                                    {{-- Émission par formation, pas globale pour l'édition. --}}
                                                    <a href="{{ $row['issueFrom'] }}" class="ukc-btn ukc-btn--ghost ukc-btn--sm" title="Ces attestations s’émettent depuis chaque formation">
                                                        <x-ukc.icon name="book" :size="14" /> Émettre par formation
                                                    </a>
                                                @else
                                                    <form method="POST" action="{{ route('admin.documents.attestations.issue') }}" style="display:contents;"
                                                          onsubmit="return confirm(@js(
                                                              'Émettre les attestations « '.$row['title'].' » : '.$row['eligible'].' destinataire(s)'
                                                              .($row['excluded'] > 0 ? ', '.$row['excluded'].' écarté(s) faute de présence' : '').'.'
                                                              .($attPaper
                                                                  ? ' Rien ne sera envoyé : la délivrance de cette édition est physique.'
                                                                  : ' Autant de notifications partiront chez les bénéficiaires.')
                                                              .' Continuer ?'))">
                                                        @csrf
                                                        <input type="hidden" name="edition_id" value="{{ $edition->id }}">
                                                        <input type="hidden" name="type" value="{{ $key }}">
                                                        <button type="submit" name="mode" value="zip" class="ukc-btn--icon" title="Émettre, puis choisir le lot PDF à imprimer" @disabled($row['eligible'] === 0)>
                                                            <svg viewBox="0 0 24 24" fill="currentColor" width="18" height="18"><path d="M19 9h-4V3H9v6H5l7 7 7-7zM5 18v2h14v-2H5z"/></svg>
                                                        </button>
                                                        <button type="submit" name="mode" value="send" class="ukc-btn--icon" title="Émettre (les destinataires sont prévenus si la délivrance est en ligne)" @disabled($row['eligible'] === 0)>
                                                            <svg viewBox="0 0 24 24" fill="currentColor" width="18" height="18"><path d="M2.01 21L23 12 2.01 3 2 10l15 2-15 2z"/></svg>
                                                        </button>
                                                    </form>
                                                @endif
                                                <a class="ukc-btn--icon" title="Éditer le template" href="{{ route('admin.documents.templates', ['edition_id' => $edition->id, 'kind' => 'attestation', 'type' => $key]) }}"><svg viewBox="0 0 24 24" fill="currentColor" width="18" height="18"><path d="M3 17.25V21h3.75L17.81 9.94l-3.75-3.75L3 17.25zM20.71 7.04c.39-.39.39-1.02 0-1.41l-2.34-2.34a.9959.9959 0 00-1.41 0l-1.83 1.83 3.75 3.75 1.83-1.83z"/></svg></a>
                                                <a class="ukc-btn--icon" title="Aperçu (valeurs d’exemple)" target="_blank" href="{{ route('admin.documents.templates.preview', ['edition_id' => $edition->id, 'kind' => 'attestation', 'type' => $key]) }}"><svg viewBox="0 0 24 24" fill="currentColor" width="18" height="18"><path d="M12 4.5C7 4.5 2.73 7.61 1 12c1.73 4.39 6 7.5 11 7.5s9.27-3.11 11-7.5c-1.73-4.39-6-7.5-11-7.5zM12 17c-2.76 0-5-2.24-5-5s2.24-5 5-5 5 2.24 5 5-2.24 5-5 5zm0-8c-1.66 0-3 1.34-3 3s1.34 3 3 3 3-1.34 3-3-1.34-3-3-3z"/></svg></a>
                                                @if ($row['template'])
                                                    <button type="button" class="ukc-btn--icon" title="Supprimer le modèle"
                                                            @click="del = { id: {{ $row['template']->id }}, title: @js($row['title']), code: @js(\App\Http\Controllers\Admin\TemplateDesignerController::deleteCode($row['template'])) }; typed = ''">
                                                        <x-ukc.icon name="trash" :size="16" />
                                                    </button>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif

                {{-- Options générales attestations --}}
                <div @if ($stab !== 'options') style="display:none;" @endif>
                    <form method="POST" action="{{ route('admin.documents.attestations.options') }}" data-ajax data-ajax-msg="Options des attestations enregistrées." class="ukc-card">
                        <input type="hidden" name="edition_id" value="{{ $edition->id }}">
                        @csrf
                        <h3>Options générales · Attestations</h3>
                        <x-ukc.scope-edition :edition="$edition" />
                        <div class="desc">Paramètres de génération, identité vérifiable (DID) et notifications pour cette édition.</div>
                        {{-- ⚠️ LE choix qui commande les autres : en ligne, le passage d'une
                             communication émet et envoie les attestations de ses auteurs ;
                             sur papier, le secrétariat émet le lot et l'imprime. --}}
                        <div class="ukc-field" style="margin:12px 0;max-width:520px;">
                            <label>Mode de délivrance</label>
                            <select name="attDelivery">
                                @foreach ($opts['attDeliveries'] as $cle => $libelle)
                                    <option value="{{ $cle }}" @selected($opts['attDelivery'] === $cle)>{{ $libelle }}</option>
                                @endforeach
                            </select>
                            <div class="desc">
                                <strong>En ligne</strong> : dès que le modérateur valide le passage d'une communication,
                                ses auteurs reçoivent leur attestation et la retrouvent dans leur espace.<br>
                                <strong>Physique</strong> : rien n'est envoyé. Le secrétariat émet le lot ici, puis
                                l'imprime et le remet - l'attestation reste consultable dans l'espace du participant.
                            </div>
                        </div>
                        <div class="ukc-field" style="margin:12px 0;max-width:520px;">
                            <label>Qui reçoit une attestation de communication</label>
                            <select name="attCommunicationScope">
                                @foreach ($opts['attScopes'] as $cle => $libelle)
                                    <option value="{{ $cle }}" @selected($opts['attCommunicationScope'] === $cle)>{{ $libelle }}</option>
                                @endforeach
                            </select>
                            <div class="desc">
                                « Présents » s'appuie sur le pointage de la séance où la communication est programmée.
                                Une communication sans séance au programme n'est jamais écartée par ce filtre.
                            </div>
                        </div>
                        @if ($opts['attCertEnabled'])
                            <div class="ukc-pill--ok" style="display:inline-flex;align-items:center;gap:6px;margin-bottom:12px;">
                                <x-ukc.icon name="shield" :size="14" /> Certification active - clé de signature configurée
                            </div>
                        @else
                            <div class="ukc-pill--warn" style="display:inline-flex;align-items:center;gap:6px;margin-bottom:12px;">
                                <x-ukc.icon name="info" :size="14" /> Certification inactive - attestations émises sans signature
                            </div>
                            <div class="desc" style="margin-bottom:12px;">
                                Générez une clé émetteur avec <code>php artisan attestations:keygen</code>, ajoutez
                                <code>ATTESTATION_SIGNING_KEY</code> au <code>.env</code>, puis signez le stock existant
                                avec <code>php artisan attestations:sign</code>.
                            </div>
                        @endif
                        {{-- Deux colonnes : identité vérifiable à gauche, comportement à droite. --}}
                        <div class="ukc-opts-grid">
                            <div>
                                <div class="ukc-field" style="margin-bottom:12px;">
                                    <label>DID émetteur</label>
                                    <input type="text" name="attDidIssuer" value="{{ $opts['attDidIssuer'] }}">
                                </div>
                                <div class="ukc-field" style="margin-bottom:12px;">
                                    <label>Méthode de vérification</label>
                                    <input type="text" name="attDidMethod" value="{{ $opts['attDidMethod'] }}">
                                </div>
                                <div class="ukc-field" style="margin-bottom:12px;">
                                    <label>Suite cryptographique</label>
                                    <select name="attCryptoSuite">
                                        @foreach (['EdDSA (VC-JWT)', 'JsonWebSignature2020'] as $suite)
                                            <option @selected($opts['attCryptoSuite'] === $suite)>{{ $suite }}</option>
                                        @endforeach
                                    </select>
                                    <div class="desc">Signature Ed25519 d’un Verifiable Credential W3C porté par un JWS (VC-JWT).</div>
                                </div>
                                <div class="ukc-field" style="margin-bottom:12px;">
                                    <label>Préfixe des références</label>
                                    <input type="text" name="attPrefix" value="{{ $opts['attPrefix'] }}">
                                </div>
                                {{-- Signataire : ce que remplacent [signatory.name] et [signatory.role]
                                     sur les modèles. Laissé vide, la ligne de signature sort vide. --}}
                                <div class="ukc-field" style="margin-bottom:12px;">
                                    <label>Signataire des attestations</label>
                                    <input type="text" name="attSignatory" value="{{ $opts['attSignatory'] }}" placeholder="Pr. Prénom NOM">
                                    <div class="desc">Remplace <code>[signatory.name]</code> sur les modèles.</div>
                                </div>
                                <div class="ukc-field">
                                    <label>Qualité du signataire</label>
                                    <input type="text" name="attSignatoryRole" value="{{ $opts['attSignatoryRole'] }}" placeholder="Président du comité d’organisation">
                                    <div class="desc">Remplace <code>[signatory.role]</code>.</div>
                                </div>
                            </div>
                            <div>
                                <label class="ukc-check"><input type="checkbox" name="attAuto" value="1" @checked($opts['attAuto'])> Générer automatiquement après paiement / fin de colloque</label>
                                <label class="ukc-check"><input type="checkbox" name="attNotify" value="1" @checked($opts['attNotify'])> Notifier le participant par e-mail</label>
                                {{-- ⚠️ Fermé par défaut : chaque SMS est facturé, et une édition de
                                     deux cents communications en enverrait autant. --}}
                                <label class="ukc-check"><input type="checkbox" name="attSms" value="1" @checked($opts['attSms'])> Envoyer aussi un SMS (passerelle requise - facturé à l’envoi)</label>
                                <label class="ukc-check"><input type="checkbox" name="attAllowBeforeValidation" value="1" @checked($opts['attAllowBeforeValidation'])> Autoriser le téléchargement avant validation admin</label>
                                <label class="ukc-check"><input type="checkbox" name="attIncludeQr" value="1" @checked($opts['attIncludeQr'])> Inclure le QR de vérification par défaut</label>
                                <label class="ukc-check"><input type="checkbox" name="attRequirePresence" value="1" @checked($opts['attRequirePresence'])> Attestation de participation réservée aux présents (pointés à l’accueil ou à une séance)</label>
                            </div>
                        </div>
                        <div style="margin-top:16px;">
                            <button type="submit" class="ukc-btn ukc-btn--blue ukc-btn--sm"><x-ukc.icon name="check" :size="16" /> Enregistrer les options</button>
                        </div>
                    </form>
                </div>
            </div>

            {{-- ======================= BADGES ======================= --}}
            <div @if ($tab !== 'badges') style="display:none;" @endif>
                {{-- Mêmes URL que les attestations : ?tab=badges&stab=… --}}
                @php($bBase = ['edition_id' => $edition->id, 'tab' => 'badges'])
                <div class="ukc-tabs">
                    <a href="{{ route('admin.documents.attestations', $bBase + ['stab' => 'modeles']) }}" @class(['active' => $tab === 'badges' && $stab === 'modeles'])>Modèles</a>
                    <a href="{{ route('admin.documents.attestations', $bBase + ['stab' => 'prets']) }}" @class(['active' => $tab === 'badges' && $stab === 'prets'])>
                        Badges prêts @isset($badgeReady)<span class="nav-count">{{ $badgeReady->total() }}</span>@endisset
                    </a>
                    <a href="{{ route('admin.documents.attestations', $bBase + ['stab' => 'options']) }}" @class(['active' => $tab === 'badges' && $stab === 'options'])>Options générales</a>
                </div>

                @if ($tab === 'badges' && $stab === 'prets')
                    @include('admin.documents._badges-ready')
                @endif

                <div @if ($tab !== 'badges' || $stab !== 'modeles') style="display:none;" @endif>
                    <div class="ukc-toolbar">
                        <button type="button" class="ukc-btn ukc-btn--blue ukc-btn--sm" @click="newBadge = true"><x-ukc.icon name="plus" :size="16" /> Nouveau modèle de badge</button>
                        <span class="sep"></span>
                        <span style="font-size:13px;color:var(--ukc-muted);">Formats A6 / A7 / CR80 · QR d’identification</span>
                    </div>

                    <div class="ukc-table-wrap">
                        <table class="ukc-table">
                            <thead><tr><th>Type</th><th>Libellé</th><th>Format</th><th>Destinataires</th><th>QR</th><th>Statut</th><th style="text-align:right;">Actions</th></tr></thead>
                            <tbody>
                                @foreach ($badgeRows as $b)
                                    <tr>
                                        <td><span class="ukc-pill ukc-pill--info">{{ $b['type'] }}</span></td>
                                        <td><strong>{{ $b['title'] }}</strong><div style="font-size:12px;color:var(--ukc-muted);">{{ $b['sub'] }}</div></td>
                                        <td>{{ $b['format'] }}</td>
                                        <td><span class="ukc-stat">{{ $b['count'] }}</span></td>
                                        <td>Oui</td>
                                        <td>@if ($b['count'] > 0)<span class="ukc-pill ukc-pill--ok">Prêt</span>@else<span class="ukc-pill ukc-pill--muted" title="Aucun destinataire pour ce format pour l’instant.">Sans destinataire</span>@endif</td>
                                        <td>
                                            <div class="ukc-actions" style="justify-content:flex-end;">
                                                <a class="ukc-btn--icon" title="Télécharger la planche (PDF)" href="{{ route('admin.documents.badges', $edition) }}" target="_blank"><svg viewBox="0 0 24 24" fill="currentColor" width="18" height="18"><path d="M19 9h-4V3H9v6H5l7 7 7-7zM5 18v2h14v-2H5z"/></svg></a>
                                                <a class="ukc-btn--icon" title="Éditer le template" href="{{ route('admin.documents.templates', ['edition_id' => $edition->id, 'kind' => 'badge']) }}"><svg viewBox="0 0 24 24" fill="currentColor" width="18" height="18"><path d="M3 17.25V21h3.75L17.81 9.94l-3.75-3.75L3 17.25zM20.71 7.04c.39-.39.39-1.02 0-1.41l-2.34-2.34a.9959.9959 0 00-1.41 0l-1.83 1.83 3.75 3.75 1.83-1.83z"/></svg></a>
                                                <a class="ukc-btn--icon" title="Aperçu (valeurs d’exemple)" target="_blank" href="{{ route('admin.documents.templates.preview', ['edition_id' => $edition->id, 'kind' => 'badge']) }}"><svg viewBox="0 0 24 24" fill="currentColor" width="18" height="18"><path d="M12 4.5C7 4.5 2.73 7.61 1 12c1.73 4.39 6 7.5 11 7.5s9.27-3.11 11-7.5c-1.73-4.39-6-7.5-11-7.5zM12 17c-2.76 0-5-2.24-5-5s2.24-5 5-5 5 2.24 5 5-2.24 5-5 5zm0-8c-1.66 0-3 1.34-3 3s1.34 3 3 3 3-1.34 3-3-1.34-3-3-3z"/></svg></a>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>

                <div @if ($tab !== 'badges' || $stab !== 'options') style="display:none;" @endif>
                    <form method="POST" action="{{ route('admin.documents.badges.options') }}" data-ajax data-ajax-msg="Options des badges enregistrées." class="ukc-card">
                        <input type="hidden" name="edition_id" value="{{ $edition->id }}">
                        @csrf
                        <h3>Options générales · Badges</h3>
                        <x-ukc.scope-edition :edition="$edition" />
                        <div class="desc">Génération, formats d’impression et contrôle d’accès.</div>
                        <div class="ukc-opts-grid">
                            <div>
                                <div class="ukc-field" style="margin-bottom:12px;">
                                    <label>Préfixe des références badge</label>
                                    <input type="text" name="badgePrefix" value="{{ $opts['badgePrefix'] }}">
                                </div>
                                <div class="ukc-field">
                                    <label>Format d’impression par défaut</label>
                                    <select name="badgeFormat">
                                        @foreach ([
                                            'A6 Portrait' => 'A6 Portrait (105 × 148 mm)',
                                            'A6 Paysage' => 'A6 Paysage (148 × 105 mm)',
                                            'A7 Portrait' => 'A7 Portrait (74 × 105 mm)',
                                            'Carte 90 × 55 mm' => 'Carte 90 × 55 mm',
                                            'CR80' => 'CR80 (85,6 × 54 mm)',
                                        ] as $val => $fmtLabel)
                                            <option value="{{ $val }}" @selected($opts['badgeFormat'] === $val)>{{ $fmtLabel }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div>
                                <label class="ukc-check"><input type="checkbox" name="badgeGenerateAfterPayment" value="1" @checked($opts['badgeGenerateAfterPayment'])> Générer après confirmation de paiement</label>
                                <label class="ukc-check"><input type="checkbox" name="badgePhotoRequired" value="1" @checked($opts['badgePhotoRequired'])> Photo participant obligatoire sur le badge</label>
                                <label class="ukc-check"><input type="checkbox" name="badgeQrAccess" value="1" @checked($opts['badgeQrAccess'])> QR d’identification sur le badge</label>
                                <label class="ukc-check"><input type="checkbox" name="badgeBatchPrint" value="1" @checked($opts['badgeBatchPrint'])> Impression batch automatique (PDF planche)</label>
                            </div>
                        </div>
                        <div style="margin-top:16px;">
                            <button type="submit" class="ukc-btn ukc-btn--blue ukc-btn--sm"><x-ukc.icon name="check" :size="16" /> Enregistrer les options</button>
                        </div>
                    </form>
                </div>
            </div>

            {{-- ======================= LIVRE DES RÉSUMÉS ======================= --}}
            <div @if ($tab !== 'livre') style="display:none;" @endif>
                <div class="ukc-tabs">
                    <button type="button" @click="livre='main'" :class="{ active: livre==='main' }">Livre des résumés</button>
                    <button type="button" @click="livre='modeles'" :class="{ active: livre==='modeles' }">Modèles de document</button>
                    <button type="button" @click="livre='apparence'" :class="{ active: livre==='apparence' }">Apparence du livre</button>
                </div>

                {{-- ---------- Livre : génération + état + contenu ---------- --}}
                @php($secBox = 'border:1px solid var(--ukc-border);border-radius:12px;padding:16px;margin-bottom:14px;')
                <div x-show="livre==='main'">
                    {{-- Panneau de génération : action principale + modèle actif --}}
                    <div class="ukc-card">
                        <div style="display:flex;flex-wrap:wrap;align-items:flex-start;justify-content:space-between;gap:12px;">
                            <div>
                                <h3 style="margin:0 0 4px;">Générer le livre des résumés</h3>
                                <div class="desc" style="margin:0;">Compilation des {{ $counts['accepted'] }} résumé(s) accepté(s), groupés par axe. Word (.docx) et PDF reprennent le modèle actif et les réglages d’apparence.</div>
                            </div>
                            <span class="ukc-pill ukc-pill--info" style="white-space:nowrap;">
                                <x-ukc.icon name="book" :size="14" /> {{ $livretActiveLabel }}
                            </span>
                        </div>
                        <div style="display:flex;flex-wrap:wrap;gap:8px;margin-top:14px;">
                            <a class="ukc-btn ukc-btn--blue ukc-btn--sm" href="{{ route('admin.documents.livret', $edition) }}?format=docx"><x-ukc.icon name="file" :size="16" /> Word (.docx)</a>
                            <a class="ukc-btn ukc-btn--outline ukc-btn--sm" href="{{ route('admin.documents.livret', $edition) }}?format=pdf"><x-ukc.icon name="download" :size="16" /> PDF</a>
                            <a class="ukc-btn ukc-btn--ghost ukc-btn--sm" href="{{ route('admin.documents.livret', $edition) }}?format=pdf&inline=1" target="_blank"><x-ukc.icon name="eye" :size="16" /> Aperçu (rendu final)</a>
                            <a class="ukc-btn ukc-btn--green ukc-btn--sm" href="{{ route('admin.documents.livret', $edition) }}?format=docx&harmonize=1"><x-ukc.icon name="sparkles" :size="16" /> .docx + harmonisation IA</a>
                            <a class="ukc-btn ukc-btn--ghost ukc-btn--sm" href="{{ route('admin.documents.sessions', $edition) }}" target="_blank"><x-ukc.icon name="layers" :size="16" /> Fiches de session</a>
                        </div>
                        <p class="ukc-hint" style="margin-top:10px;">L’harmonisation IA normalise les noms d’auteurs, affiliations et accents avant export. Changez de modèle dans l’onglet <strong>Modèles de document</strong>, ajustez la typographie dans <strong>Apparence du livre</strong>.</p>
                    </div>

                    {{-- Récapitulatif chiffré --}}
                    <div class="ukc-stats" style="margin-bottom:16px;">
                        <div class="ukc-stat-card"><div class="lbl">Résumés inclus</div><div class="val">{{ $counts['accepted'] }}</div><div class="hint">communications acceptées</div></div>
                        <div class="ukc-stat-card"><div class="lbl">Axes</div><div class="val">{{ $counts['axes'] }}</div><div class="hint">{{ $counts['sessions'] }} session(s)</div></div>
                        <div class="ukc-stat-card"><div class="lbl">Sections de tête</div><div class="val">{{ $livretSectionsFilled }}/{{ $livretSectionCount }}</div><div class="hint">renseignées</div></div>
                        <div class="ukc-stat-card"><div class="lbl">Dernière génération</div><div class="val" style="font-size:15px;">{{ $livreState['lastGen'] ? \Illuminate\Support\Carbon::parse($livreState['lastGen'])->format('d/m/Y') : '—' }}</div><div class="hint">{{ $livreState['lastGen'] ? \Illuminate\Support\Carbon::parse($livreState['lastGen'])->format('H:i') : 'jamais compilé' }}</div></div>
                    </div>

                    <form method="POST" action="{{ route('admin.documents.livret.options') }}" class="ukc-card">
                        <input type="hidden" name="edition_id" value="{{ $edition->id }}">
                        @csrf
                        <div style="display:flex;align-items:center;justify-content:space-between;gap:10px;flex-wrap:wrap;">
                            <h3 style="margin:0;">Contenu du livre</h3>
                            <span class="ukc-pill ukc-pill--info">{{ $livretSectionsFilled }}/{{ $livretSectionCount }} section(s) renseignée(s)</span>
                        </div>
                        <x-ukc.scope-edition :edition="$edition" />
                        <div class="desc">Pages de tête du livret, dans l’ordre d’apparition. <strong>Une section laissée vide n’apparaît pas</strong> dans le livre généré.</div>

                        {{-- Avant-propos --}}
                        <div style="{{ $secBox }}">
                            <div style="display:flex;align-items:center;justify-content:space-between;gap:10px;margin-bottom:12px;">
                                <strong style="color:var(--ukc-blue);">Avant-propos</strong>
                                @if (trim($opts['forewordText']) !== '')<span class="ukc-pill ukc-pill--ok">Affiché</span>@else<span class="ukc-pill ukc-pill--muted">Masqué (vide)</span>@endif
                            </div>
                            <div class="ukc-field" style="margin-bottom:12px;">
                                <label>Titre de la section</label>
                                <input type="text" name="forewordTitle" value="{{ $opts['forewordTitle'] }}">
                            </div>
                            <div class="ukc-field" style="margin-bottom:12px;">
                                <label>Texte de l’avant-propos</label>
                                <textarea rows="6" name="forewordText" placeholder="Le comité d’organisation a le plaisir de présenter…">{{ $opts['forewordText'] }}</textarea>
                            </div>
                            <div class="ukc-field" style="margin-bottom:12px;">
                                <label>Signature (nom + fonction)</label>
                                <input type="text" name="forewordSignature" value="{{ $opts['forewordSignature'] }}" placeholder="Prof. … - Président du Comité d’Organisation">
                            </div>
                            @if ($aiAvailable)
                                {{-- data-ai : le voile de travail se lève après la confirmation
                                     et tient jusqu'au rechargement, l'IA prenant une dizaine
                                     de secondes sans quoi rien n'indiquerait qu'elle travaille. --}}
                                <button type="submit" formaction="{{ route('admin.documents.livret.foreword-ai', ['edition_id' => $edition->id]) }}"
                                        class="ukc-btn ukc-btn--green ukc-btn--sm"
                                        data-ai data-ai-title="Rédaction de l’avant-propos…"
                                        data-ai-steps="Lecture des métadonnées de l’édition…|Relevé des axes et des résumés retenus…|Rédaction du texte…"
                                        data-confirm="Générer l’avant-propos par IA à partir des métadonnées de l’édition ? Le texte actuel sera remplacé.">
                                    <x-ukc.icon name="sparkles" :size="16" /> Rédiger par IA
                                </button>
                                <p class="ukc-hint" style="margin-top:8px;">L’IA rédige l’avant-propos à partir du colloque, du thème, des axes et du nombre de résumés retenus.</p>
                            @endif
                        </div>

                        {{-- Haut patronage --}}
                        <div style="{{ $secBox }}">
                            <div style="display:flex;align-items:center;justify-content:space-between;gap:10px;margin-bottom:12px;">
                                <strong style="color:var(--ukc-blue);">Haut patronage</strong>
                                @if (trim($opts['livretPatronage']) !== '')<span class="ukc-pill ukc-pill--ok">Affiché</span>@else<span class="ukc-pill ukc-pill--muted">Masqué (vide)</span>@endif
                            </div>
                            <div class="ukc-field">
                                <label>Une ligne par élément (centré sur la page)</label>
                                <textarea rows="3" name="livretPatronage" placeholder="Sous le haut patronage du Ministre de l’Enseignement Supérieur et de la Recherche&#10;Prof …">{{ $opts['livretPatronage'] }}</textarea>
                            </div>
                        </div>

                        {{-- Remerciements --}}
                        <div style="{{ $secBox }}">
                            <div style="display:flex;align-items:center;justify-content:space-between;gap:10px;margin-bottom:12px;">
                                <strong style="color:var(--ukc-blue);">Remerciements</strong>
                                @if (trim($opts['livretAcknowledgements']) !== '')<span class="ukc-pill ukc-pill--ok">Affiché</span>@else<span class="ukc-pill ukc-pill--muted">Masqué (vide)</span>@endif
                            </div>
                            <div class="ukc-field" style="margin-bottom:12px;">
                                <label>Titre de la section</label>
                                <input type="text" name="livretAckTitle" value="{{ $opts['livretAckTitle'] }}" placeholder="Remerciements">
                            </div>
                            <div class="ukc-field">
                                <label>Une ligne par destinataire (→ liste à puces)</label>
                                <textarea rows="4" name="livretAcknowledgements" placeholder="Monsieur le Président de l’Université&#10;Monsieur le Préfet…">{{ $opts['livretAcknowledgements'] }}</textarea>
                            </div>
                        </div>

                        {{-- Mot de bienvenue --}}
                        <div style="{{ $secBox }}">
                            <div style="display:flex;align-items:center;justify-content:space-between;gap:10px;margin-bottom:12px;">
                                <strong style="color:var(--ukc-blue);">Mot de bienvenue</strong>
                                @if (trim($opts['livretWelcomeText']) !== '')<span class="ukc-pill ukc-pill--ok">Affiché</span>@else<span class="ukc-pill ukc-pill--muted">Masqué (vide)</span>@endif
                            </div>
                            <div class="ukc-field" style="margin-bottom:12px;">
                                <label>Titre de la section</label>
                                <input type="text" name="livretWelcomeTitle" value="{{ $opts['livretWelcomeTitle'] }}" placeholder="Mot de bienvenue">
                            </div>
                            <div class="ukc-field" style="margin-bottom:12px;">
                                <label>Texte</label>
                                <textarea rows="5" name="livretWelcomeText" placeholder="Chères participantes, chers participants…">{{ $opts['livretWelcomeText'] }}</textarea>
                            </div>
                            <div class="ukc-field">
                                <label>Signature (nom + fonction)</label>
                                <input type="text" name="livretWelcomeSignature" value="{{ $opts['livretWelcomeSignature'] }}" placeholder="Prof. … - Doyen de la Faculté">
                            </div>
                        </div>

                        {{-- Mot du président --}}
                        <div style="{{ $secBox }}">
                            <div style="display:flex;align-items:center;justify-content:space-between;gap:10px;margin-bottom:12px;">
                                <strong style="color:var(--ukc-blue);">Mot du président</strong>
                                @if (trim($opts['livretPresidentText']) !== '')<span class="ukc-pill ukc-pill--ok">Affiché</span>@else<span class="ukc-pill ukc-pill--muted">Masqué (vide)</span>@endif
                            </div>
                            <div class="ukc-field" style="margin-bottom:12px;">
                                <label>Titre de la section</label>
                                <input type="text" name="livretPresidentTitle" value="{{ $opts['livretPresidentTitle'] }}" placeholder="Mot du président">
                            </div>
                            <div class="ukc-field" style="margin-bottom:12px;">
                                <label>Texte</label>
                                <textarea rows="5" name="livretPresidentText" placeholder="C’est avec plaisir que nous vous accueillons…">{{ $opts['livretPresidentText'] }}</textarea>
                            </div>
                            <div class="ukc-field">
                                <label>Signature (nom + fonction)</label>
                                <input type="text" name="livretPresidentSignature" value="{{ $opts['livretPresidentSignature'] }}" placeholder="… - Président du Comité d’Organisation">
                            </div>
                        </div>

                        <div style="display:flex;gap:8px;flex-wrap:wrap;">
                            <button type="submit" class="ukc-btn ukc-btn--blue"><x-ukc.icon name="check" :size="16" /> Enregistrer le contenu</button>
                        </div>
                    </form>
                </div>

                {{-- ---------- Modèles de document ---------- --}}
                <div x-show="livre==='modeles'" x-cloak>
                    <div class="ukc-card">
                        <div style="display:flex;align-items:center;justify-content:space-between;gap:10px;flex-wrap:wrap;">
                            <div>
                                <h3 style="margin:0 0 4px;">Modèle utilisé pour cette édition</h3>
                                <div class="desc" style="margin:0;">Choisissez le modèle qui sert à compiler le <strong>Word (.docx)</strong>. L’aperçu HTML et le PDF utilisent toujours la mise en page par défaut.</div>
                            </div>
                            <span class="ukc-pill ukc-pill--info" style="white-space:nowrap;"><x-ukc.icon name="check" :size="14" /> Actif : {{ $livretActiveLabel }}</span>
                        </div>
                        <x-ukc.scope-edition :edition="$edition" />

                        <h4 style="margin:16px 0 8px;color:var(--ukc-muted);text-transform:uppercase;letter-spacing:.04em;font-size:12px;">Modèles intégrés</h4>
                        @foreach ($livretBuiltins as $m)
                            @php($isActive = $opts['livretTemplate'] === $m['key'])
                            <div class="ukc-tpl-item" @if ($isActive) style="border-color:var(--ukc-blue);box-shadow:0 0 0 1px var(--ukc-blue) inset;" @endif>
                                <div class="ico">DOC</div>
                                <div class="meta"><strong>{{ $m['name'] }}</strong><span>{{ $m['desc'] }}</span></div>
                                <div style="display:flex;gap:6px;flex-wrap:wrap;align-items:center;">
                                    @if ($m['download'])
                                        <a href="{{ $m['download'] }}" class="ukc-btn ukc-btn--ghost ukc-btn--sm"><x-ukc.icon name="download" :size="15" /> Télécharger</a>
                                    @endif
                                    @if ($isActive)
                                        <span class="ukc-pill ukc-pill--ok">Actif</span>
                                    @else
                                        <form method="POST" action="{{ route('admin.documents.livret.template') }}" style="display:inline;">
                                            @csrf
                                            <input type="hidden" name="edition_id" value="{{ $edition->id }}">
                                            <input type="hidden" name="livretTemplate" value="{{ $m['key'] }}">
                                            <button type="submit" class="ukc-btn ukc-btn--sm">Utiliser</button>
                                        </form>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <div class="ukc-card">
                        <h3>Modèles personnalisés (.docx)</h3>
                        <x-ukc.scope-edition :edition="$edition" />
                        <div class="desc">Partez du <a href="{{ route('admin.documents.livret.base-template') }}">modèle de base</a>, personnalisez-le dans Word <strong>en conservant les balises</strong>, puis redéposez-le ici.</div>
                        <form method="POST" action="{{ route('admin.documents.livret.template') }}" enctype="multipart/form-data">
                            @csrf
                            <input type="hidden" name="edition_id" value="{{ $edition->id }}">
                            <label class="ukc-drop">
                                <x-ukc.icon name="upload" :size="20" /><br>
                                Déposer un modèle .docx<br>
                                <span style="font-size:11px;">Balises : [[TITRE]], [[RESUMES]], [[PROGRAMME]], [[COMITES]], [[AVANT_PROPOS]], [[SOMMAIRE]]…</span>
                                <input type="file" name="templateFile" accept=".doc,.docx" style="display:none;" onchange="this.form.submit()">
                            </label>
                            @error('templateFile') <span class="error">{{ $message }}</span> @enderror
                        </form>

                        <div style="margin-top:14px;">
                            @forelse ($templates as $tpl)
                                @php($isActive = $opts['livretTemplate'] === $tpl['name'])
                                <div class="ukc-tpl-item" @if ($isActive) style="border-color:var(--ukc-blue);box-shadow:0 0 0 1px var(--ukc-blue) inset;" @endif>
                                    <div class="ico">DOC</div>
                                    <div class="meta"><strong>{{ $tpl['name'] }}</strong><span>{{ number_format(($tpl['size'] ?? 0) / 1024, 0) }} Ko · importé le {{ $tpl['date'] ?? '-' }}</span></div>
                                    <div style="display:flex;gap:6px;flex-wrap:wrap;align-items:center;">
                                        <a href="{{ \Illuminate\Support\Facades\Storage::url($tpl['path']) }}" class="ukc-btn ukc-btn--ghost ukc-btn--sm" target="_blank"><x-ukc.icon name="download" :size="15" /> Télécharger</a>
                                        @if ($isActive)
                                            <span class="ukc-pill ukc-pill--ok">Actif</span>
                                        @else
                                            <form method="POST" action="{{ route('admin.documents.livret.template') }}" style="display:inline;">
                                                @csrf
                                                <input type="hidden" name="edition_id" value="{{ $edition->id }}">
                                                <input type="hidden" name="livretTemplate" value="{{ $tpl['name'] }}">
                                                <button type="submit" class="ukc-btn ukc-btn--sm">Utiliser</button>
                                            </form>
                                        @endif
                                        <form method="POST" action="{{ route('admin.documents.livret.template.delete') }}" style="display:inline;"
                                              data-confirm="Supprimer le modèle « {{ $tpl['name'] }} » ? @if ($isActive)Le modèle par défaut redeviendra actif.@endif">
                                            @csrf
                                            <input type="hidden" name="edition_id" value="{{ $edition->id }}">
                                            <input type="hidden" name="name" value="{{ $tpl['name'] }}">
                                            <button type="submit" class="ukc-btn ukc-btn--danger ukc-btn--sm" title="Supprimer"><x-ukc.icon name="trash" :size="15" /></button>
                                        </form>
                                    </div>
                                </div>
                            @empty
                                <div class="ukc-empty" style="padding:20px;text-align:center;color:var(--ukc-muted);">
                                    Aucun modèle personnalisé pour l’instant. Déposez un .docx ci-dessus pour en ajouter un.
                                </div>
                            @endforelse
                        </div>
                    </div>
                </div>

                {{-- ---------- Apparence du livre ---------- --}}
                <div x-show="livre==='apparence'" x-cloak>
                    <form method="POST" action="{{ route('admin.documents.livret.apparence') }}" data-ajax data-ajax-msg="Apparence du livre enregistrée." class="ukc-card" enctype="multipart/form-data">
                        <input type="hidden" name="edition_id" value="{{ $edition->id }}">
                        @csrf
                        <h3>Apparence du livre · modèles intégrés</h3>
                        <x-ukc.scope-edition :edition="$edition" />
                        <div class="desc">Typographie, couleurs et page de garde appliquées aux <strong>deux modèles intégrés</strong> (par défaut et Journées scientifiques). Les modèles .docx importés gardent leur propre mise en forme.</div>

                        <div class="ukc-form-grid">
                            <div class="ukc-form-row">
                                <label>Police du corps</label>
                                <select name="livretFontBody">
                                    @foreach (['Times New Roman', 'Jost', 'Georgia', 'Arial', 'Garamond'] as $f)<option @selected($opts['livretFontBody'] === $f)>{{ $f }}</option>@endforeach
                                </select>
                            </div>
                            <div class="ukc-form-row">
                                <label>Police des titres</label>
                                <select name="livretFontHead">
                                    @foreach (['Jost', 'Playfair Display', 'Arial', 'Georgia'] as $f)<option @selected($opts['livretFontHead'] === $f)>{{ $f }}</option>@endforeach
                                </select>
                            </div>
                        </div>

                        <h3 style="font-size:13px;margin:16px 0 10px;color:var(--ukc-muted);text-transform:uppercase;letter-spacing:.04em;">Tailles (points)</h3>
                        <div class="ukc-form-grid">
                            <div class="ukc-form-row"><label>Corps</label><input type="number" name="livretSizeBody" value="{{ $opts['livretSizeBody'] }}" min="8" max="16"></div>
                            <div class="ukc-form-row"><label>Titre couverture</label><input type="number" name="livretSizeCover" value="{{ $opts['livretSizeCover'] }}" min="14" max="48"></div>
                            <div class="ukc-form-row"><label>Titre d’axe</label><input type="number" name="livretSizeAxis" value="{{ $opts['livretSizeAxis'] }}" min="12" max="28"></div>
                            <div class="ukc-form-row"><label>Titre de résumé</label><input type="number" name="livretSizeAbstract" value="{{ $opts['livretSizeAbstract'] }}" min="10" max="18"></div>
                            <div class="ukc-form-row"><label>Auteurs</label><input type="number" name="livretSizeAuthors" value="{{ $opts['livretSizeAuthors'] }}" min="8" max="14"></div>
                            <div class="ukc-form-row">
                                <label>Interligne corps</label>
                                <select name="livretLineHeight">
                                    @foreach (['1.0', '1.15', '1.5', '2.0'] as $lh)<option @selected($opts['livretLineHeight'] === $lh)>{{ $lh }}</option>@endforeach
                                </select>
                            </div>
                        </div>

                        <h3 style="font-size:13px;margin:16px 0 10px;color:var(--ukc-muted);text-transform:uppercase;letter-spacing:.04em;">Couleurs &amp; alignements</h3>
                        <div class="ukc-form-grid">
                            <div class="ukc-form-row" x-data="{ c: '{{ $opts['livretColorPrimary'] }}' }">
                                <label>Couleur principale</label>
                                <div class="color-row">
                                    <input type="color" x-model="c">
                                    <input type="text" name="livretColorPrimary" x-model="c" style="flex:1;">
                                </div>
                            </div>
                            <div class="ukc-form-row" x-data="{ c: '{{ $opts['livretColorSecondary'] }}' }">
                                <label>Couleur secondaire</label>
                                <div class="color-row">
                                    <input type="color" x-model="c">
                                    <input type="text" name="livretColorSecondary" x-model="c" style="flex:1;">
                                </div>
                            </div>
                            <div class="ukc-form-row">
                                <label>Alignement titres</label>
                                <select name="livretAlignTitles">
                                    @foreach (['Centré', 'Gauche', 'Droite', 'Justifié'] as $al)<option @selected($opts['livretAlignTitles'] === $al)>{{ $al }}</option>@endforeach
                                </select>
                            </div>
                            <div class="ukc-form-row">
                                <label>Alignement titres d’axe</label>
                                <select name="livretAlignAxis">
                                    @foreach (['Centré', 'Gauche', 'Droite'] as $al)<option @selected($opts['livretAlignAxis'] === $al)>{{ $al }}</option>@endforeach
                                </select>
                            </div>
                            <div class="ukc-form-row">
                                <label>Alignement auteurs</label>
                                <select name="livretAlignAuthors">
                                    @foreach (['Centré', 'Gauche', 'Droite'] as $al)<option @selected($opts['livretAlignAuthors'] === $al)>{{ $al }}</option>@endforeach
                                </select>
                            </div>
                            <div class="ukc-form-row">
                                <label>Alignement corps</label>
                                <select name="livretAlignBody">
                                    @foreach (['Justifié', 'Gauche'] as $al)<option @selected($opts['livretAlignBody'] === $al)>{{ $al }}</option>@endforeach
                                </select>
                            </div>
                        </div>

                        <h3 style="font-size:13px;margin:16px 0 10px;color:var(--ukc-muted);text-transform:uppercase;letter-spacing:.04em;">Page de garde</h3>
                        <div class="ukc-form-row">
                            <label>Mention sur la page de garde</label>
                            <input type="text" name="livretCoverMention" value="{{ $opts['livretCoverMention'] }}" placeholder="Livre des résumés · Colloque … · Université de Kara">
                        </div>
                        <div class="ukc-form-row">
                            <label>Logo(s) page de garde</label>
                            <label class="ukc-drop" style="padding:14px;">
                                Ajouter logo UK / UP / partenaires<br>
                                <span style="font-size:11px;">PNG, SVG · fond transparent recommandé</span>
                                <input type="file" name="livretCoverLogo" accept=".png,.jpg,.jpeg,.webp,.svg" style="display:none;">
                            </label>
                            @if ($opts['livretCoverLogo'])
                                <div style="margin-top:8px;display:flex;align-items:center;gap:10px;">
                                    <img src="{{ \Illuminate\Support\Facades\Storage::url($opts['livretCoverLogo']) }}" alt="Logo couverture" style="height:40px;border:1px solid var(--ukc-border);border-radius:6px;padding:4px;background:#fff;">
                                    <span class="ukc-hint">Logo actuel - déposez-en un nouveau pour le remplacer.</span>
                                </div>
                            @endif
                            @error('livretCoverLogo') <span class="error">{{ $message }}</span> @enderror
                        </div>
                        <label class="ukc-check"><input type="checkbox" name="livretShowTheme" value="1" @checked($opts['livretShowTheme'])> Afficher le thème de l’édition sur la couverture</label>
                        <label class="ukc-check"><input type="checkbox" name="livretShowDates" value="1" @checked($opts['livretShowDates'])> Afficher les dates et le lieu</label>
                        <label class="ukc-check"><input type="checkbox" name="livretNumber" value="1" @checked($opts['livretNumber'])> Numérotation des résumés</label>
                        <label class="ukc-check"><input type="checkbox" name="livretAuthorIndex" value="1" @checked($opts['livretAuthorIndex'])> Index des auteurs (en fin de livret)</label>
                        <label class="ukc-check"><input type="checkbox" name="livretPageNumbers" value="1" @checked($opts['livretPageNumbers'])> Numéros de page en pied de page</label>

                        <div style="margin-top:16px;display:flex;gap:8px;flex-wrap:wrap;">
                            <button type="submit" class="ukc-btn"><x-ukc.icon name="check" :size="16" /> Enregistrer l’apparence</button>
                            <a class="ukc-btn ukc-btn--ghost" href="{{ route('admin.documents.livret', $edition) }}?format=pdf&inline=1" target="_blank"><x-ukc.icon name="eye" :size="16" /> Aperçu (rendu final)</a>
                        </div>
                    </form>
                </div>
            </div>
            {{-- Suppression d'un modèle : conséquences annoncées + code à retaper.
                 Le code est aussi revérifié côté serveur (le JS ne protège rien). --}}
            <x-ukc.slideover open="del" onClose="del = null" width="470px">
                <x-slot:header>Supprimer un modèle</x-slot:header>
                <template x-if="del">
                    <form method="POST" :action="'{{ url('admin/documents/modeles') }}/' + del.id" id="delTplForm">
                        @csrf @method('DELETE')
                        <p style="font-size:14px;margin:0 0 12px;">
                            Vous êtes sur le point de supprimer le modèle <strong x-text="del.title"></strong>.
                        </p>
                        <div class="ukc-alert ukc-alert--warn" style="margin-bottom:14px;">
                            <span class="ico"><x-ukc.icon name="info" :size="18" /></span>
                            <div style="font-size:13px;">
                                <strong>Ce que cela fait :</strong>
                                <ul style="margin:6px 0 0;padding-left:18px;">
                                    <li>votre mise en page est <strong>définitivement perdue</strong> ;</li>
                                    <li>le modèle est <strong>réinitialisé</strong> à la mise en page standard, à reconcevoir ensuite ;</li>
                                    <li>les attestations <strong>déjà délivrées ne sont pas modifiées</strong> et restent vérifiables.</li>
                                </ul>
                            </div>
                        </div>
                        <div class="ukc-form-row">
                            <label for="delConfirm">
                                Pour confirmer, tapez <code x-text="del.code" style="user-select:all;"></code>
                            </label>
                            <input type="text" id="delConfirm" name="confirm" x-model="typed" autocomplete="off"
                                   placeholder="Code de confirmation" required>
                            @error('confirm') <span class="error">{{ $message }}</span> @enderror
                        </div>
                    </form>
                </template>
                <x-slot:footer>
                    <span style="flex:1;"></span>
                    <button type="button" class="ukc-btn ukc-btn--ghost" @click="del = null">Annuler</button>
                    <button type="submit" form="delTplForm" class="ukc-btn ukc-btn--danger"
                            :disabled="!del || typed.trim().toUpperCase() !== del.code">
                        Supprimer définitivement
                    </button>
                </x-slot:footer>
            </x-ukc.slideover>

            {{-- Création d'un modèle : on identifie le modèle AVANT d'ouvrir le
                 concepteur (libellé, type, portée déduite, format pour un badge). --}}
            <x-ukc.slideover open="newAtt" onClose="newAtt = false" width="480px">
                <x-slot:header>Nouveau modèle d’attestation</x-slot:header>
                <form method="POST" action="{{ route('admin.documents.templates.create') }}" id="newAttForm"
                      x-data="{ t: 'participation', scopes: @js(\App\Http\Controllers\Admin\TemplateDesignerController::SCOPES), labels: @js($types) }">
                    @csrf
                    <input type="hidden" name="edition_id" value="{{ $edition->id }}">
                    <input type="hidden" name="kind" value="attestation">
                    <div class="ukc-form-row">
                        <label for="att-type">Type <span class="req">*</span></label>
                        <select id="att-type" name="type" x-model="t" required>
                            @foreach ($types as $key => $label)
                                <option value="{{ $key }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="ukc-form-row">
                        <label for="att-title">Libellé</label>
                        <input type="text" id="att-title" name="title" maxlength="150"
                               :placeholder="'Attestation de ' + (labels[t] || '').toLowerCase()">
                        <span class="hint">Laissez vide pour reprendre le libellé du type.</span>
                    </div>
                    <div class="ukc-form-row">
                        <label>Portée</label>
                        <input type="text" :value="scopes[t] || 'Participant'" readonly
                               style="background:var(--ukc-bg);cursor:not-allowed;">
                        <span class="hint">Déduite du type : c’est elle qui détermine les destinataires.</span>
                    </div>
                </form>
                <x-slot:footer>
                    <span style="flex:1;"></span>
                    <button type="button" class="ukc-btn ukc-btn--ghost" @click="newAtt = false">Annuler</button>
                    <button type="submit" form="newAttForm" class="ukc-btn ukc-btn--blue">Créer &amp; concevoir</button>
                </x-slot:footer>
            </x-ukc.slideover>

            <x-ukc.slideover open="newBadge" onClose="newBadge = false" width="480px">
                <x-slot:header>Nouveau modèle de badge</x-slot:header>
                <form method="POST" action="{{ route('admin.documents.templates.create') }}" id="newBadgeForm">
                    @csrf
                    <input type="hidden" name="edition_id" value="{{ $edition->id }}">
                    <input type="hidden" name="kind" value="badge">
                    <input type="hidden" name="type" value="default">
                    <div class="ukc-form-row">
                        <label for="badge-title">Libellé</label>
                        <input type="text" id="badge-title" name="title" maxlength="150" placeholder="Badge participant">
                    </div>
                    <div class="ukc-form-row">
                        <label for="badge-format">Format <span class="req">*</span></label>
                        <select id="badge-format" name="format" required>
                            @foreach (\App\Http\Controllers\Admin\TemplateDesignerController::BADGE_FORMATS as $key => $label)
                                <option value="{{ $key }}" @selected($key === 'a6')>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="ukc-form-row">
                        <label>Portée</label>
                        <input type="text" value="Participant" readonly style="background:var(--ukc-bg);cursor:not-allowed;">
                    </div>
                </form>
                <x-slot:footer>
                    <span style="flex:1;"></span>
                    <button type="button" class="ukc-btn ukc-btn--ghost" @click="newBadge = false">Annuler</button>
                    <button type="submit" form="newBadgeForm" class="ukc-btn ukc-btn--blue">Créer &amp; concevoir</button>
                </x-slot:footer>
            </x-ukc.slideover>
        @endif
    </div>
</x-layouts.admin>
