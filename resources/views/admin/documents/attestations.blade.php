<x-layouts.admin title="Documents" :subtitle="$edition ? 'Édition active · '.($edition->title ?: $edition->year) : 'Attestations, badges & livre des résumés'">
    <div class="admin-page admin-documents" x-data="{ page: new URLSearchParams(window.location.search).get('tab') || 'attestations', att: 'list', badge: 'list', livre: 'main' }">
        {{-- Sélecteur d'édition partagé + vérification publique --}}
        <div style="display:flex;align-items:flex-end;justify-content:space-between;gap:12px;flex-wrap:wrap;margin-bottom:16px;">
            <div class="ukc-field" style="max-width:340px;margin:0;">
                <label>Édition</label>
                <select onchange="window.location='{{ route('admin.documents.attestations') }}?edition_id='+this.value">
                    <option value="">— Choisir —</option>
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
            <div class="ukc-nav-docs">
                <button type="button" @click="page='attestations'" :class="{ active: page==='attestations' }">Attestations</button>
                <button type="button" @click="page='badges'" :class="{ active: page==='badges' }">Badges</button>
                <button type="button" @click="page='livre'" :class="{ active: page==='livre' }">Livre des résumés</button>
            </div>

            {{-- ======================= ATTESTATIONS ======================= --}}
            <div x-show="page==='attestations'">
                <div class="ukc-tabs">
                    <button type="button" @click="att='list'" :class="{ active: att==='list' }">Liste des attestations</button>
                    <button type="button" @click="att='options'" :class="{ active: att==='options' }">Options générales</button>
                </div>

                <div x-show="att==='list'">
                    <div class="ukc-toolbar">
                        <a class="ukc-btn ukc-btn--blue ukc-btn--sm" href="{{ route('admin.documents.templates', ['edition_id' => $edition->id, 'kind' => 'attestation']) }}">
                            <x-ukc.icon name="plus" :size="16" /> Nouveau template
                        </a>
                        <span class="sep"></span>
                        <span style="font-size:13px;color:var(--ukc-muted);">Templates liés à l’édition · génération PDF + QR de vérification. Émission idempotente.</span>
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
                                        <td><span class="ukc-stat">{{ $row['eligible'] }}</span></td>
                                        <td>Oui</td>
                                        <td>@if ($row['status'] === 'Actif')<span class="ukc-pill ukc-pill--ok">Actif</span>@else<span class="ukc-pill ukc-pill--muted">Brouillon</span>@endif</td>
                                        <td>
                                            <div class="ukc-actions" style="justify-content:flex-end;">
                                                <form method="POST" action="{{ route('admin.documents.attestations.issue') }}" style="display:contents;"
                                                      onsubmit="return confirm('Émettre / générer les attestations « {{ $row['title'] }} » ({{ $row['eligible'] }} destinataire(s)) ?')">
                                                    @csrf
                                                    <input type="hidden" name="edition_id" value="{{ $edition->id }}">
                                                    <input type="hidden" name="type" value="{{ $key }}">
                                                    <button type="submit" name="mode" value="zip" class="ukc-btn--icon" title="Émettre &amp; générer les PDF" @disabled($row['eligible'] === 0)>
                                                        <svg viewBox="0 0 24 24" fill="currentColor" width="18" height="18"><path d="M19 9h-4V3H9v6H5l7 7 7-7zM5 18v2h14v-2H5z"/></svg>
                                                    </button>
                                                    <button type="submit" name="mode" value="send" class="ukc-btn--icon" title="Émettre &amp; notifier les destinataires" @disabled($row['eligible'] === 0)>
                                                        <svg viewBox="0 0 24 24" fill="currentColor" width="18" height="18"><path d="M2.01 21L23 12 2.01 3 2 10l15 2-15 2z"/></svg>
                                                    </button>
                                                </form>
                                                <a class="ukc-btn--icon" title="Éditer le template" href="{{ route('admin.documents.templates', ['edition_id' => $edition->id, 'kind' => 'attestation', 'type' => $key]) }}"><svg viewBox="0 0 24 24" fill="currentColor" width="18" height="18"><path d="M3 17.25V21h3.75L17.81 9.94l-3.75-3.75L3 17.25zM20.71 7.04c.39-.39.39-1.02 0-1.41l-2.34-2.34a.9959.9959 0 00-1.41 0l-1.83 1.83 3.75 3.75 1.83-1.83z"/></svg></a>
                                                <a class="ukc-btn--icon" title="Aperçu" href="{{ route('admin.documents.templates', ['edition_id' => $edition->id, 'kind' => 'attestation', 'type' => $key]) }}"><svg viewBox="0 0 24 24" fill="currentColor" width="18" height="18"><path d="M12 4.5C7 4.5 2.73 7.61 1 12c1.73 4.39 6 7.5 11 7.5s9.27-3.11 11-7.5c-1.73-4.39-6-7.5-11-7.5zM12 17c-2.76 0-5-2.24-5-5s2.24-5 5-5 5 2.24 5 5-2.24 5-5 5zm0-8c-1.66 0-3 1.34-3 3s1.34 3 3 3 3-1.34 3-3-1.34-3-3-3z"/></svg></a>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <p class="ukc-hint" style="margin-top:12px;">
                        Les templates « par communication » sont générés <strong>autant de fois</strong> que le participant a de communications éligibles.
                        <svg viewBox="0 0 24 24" fill="currentColor" width="13" height="13" style="vertical-align:-2px;"><path d="M19 9h-4V3H9v6H5l7 7 7-7zM5 18v2h14v-2H5z"/></svg> = génère les PDF (QR/DID) ·
                        <svg viewBox="0 0 24 24" fill="currentColor" width="13" height="13" style="vertical-align:-2px;"><path d="M2.01 21L23 12 2.01 3 2 10l15 2-15 2z"/></svg> = notifie les destinataires par e-mail.
                    </p>

                    <div class="ukc-card" style="margin-top:16px;display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap;">
                        <div>
                            <h3 style="margin:0 0 2px;">Attestations délivrées</h3>
                            <div style="font-size:13px;color:var(--ukc-muted);">{{ $counts['issued'] }} attestation(s) valide(s) pour cette édition — consultez, vérifiez ou révoquez sur la page dédiée.</div>
                        </div>
                        <a href="{{ route('admin.attestations.index', ['edition_id' => $edition->id]) }}" class="ukc-btn ukc-btn--blue ukc-btn--sm">
                            <x-ukc.icon name="diploma" :size="16" /> Voir les attestations délivrées
                        </a>
                    </div>
                </div>

                {{-- Options générales attestations --}}
                <div x-show="att==='options'" x-cloak>
                    <form method="POST" action="{{ route('admin.documents.attestations.options') }}" class="ukc-card" style="max-width:640px;">
                        @csrf
                        <h3>Options générales · Attestations</h3>
                        <div class="desc">Paramètres de génération, DIDs et notifications pour cette édition.</div>
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
                                @foreach (['Ed25519Signature2020', 'JsonWebSignature2020'] as $suite)
                                    <option @selected($opts['attCryptoSuite'] === $suite)>{{ $suite }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="ukc-field" style="margin-bottom:12px;">
                            <label>Préfixe des références</label>
                            <input type="text" name="attPrefix" value="{{ $opts['attPrefix'] }}">
                        </div>
                        <label class="ukc-check"><input type="checkbox" name="attAuto" value="1" @checked($opts['attAuto'])> Générer automatiquement après paiement / fin de colloque</label>
                        <label class="ukc-check"><input type="checkbox" name="attNotify" value="1" @checked($opts['attNotify'])> Notifier le participant par e-mail</label>
                        <label class="ukc-check"><input type="checkbox" name="attAllowBeforeValidation" value="1" @checked($opts['attAllowBeforeValidation'])> Autoriser le téléchargement avant validation admin</label>
                        <label class="ukc-check"><input type="checkbox" name="attIncludeQr" value="1" @checked($opts['attIncludeQr'])> Inclure le QR de vérification par défaut</label>
                        <label class="ukc-check"><input type="checkbox" name="attRequirePresence" value="1" @checked($opts['attRequirePresence'])> Attestation de participation réservée aux présents (pointés à l’accueil ou à une séance)</label>
                        <div style="margin-top:16px;">
                            <button type="submit" class="ukc-btn ukc-btn--blue ukc-btn--sm"><x-ukc.icon name="check" :size="16" /> Enregistrer les options</button>
                        </div>
                    </form>
                </div>
            </div>

            {{-- ======================= BADGES ======================= --}}
            <div x-show="page==='badges'" x-cloak>
                <div class="ukc-tabs">
                    <button type="button" @click="badge='list'" :class="{ active: badge==='list' }">Liste des badges</button>
                    <button type="button" @click="badge='options'" :class="{ active: badge==='options' }">Options générales</button>
                </div>

                <div x-show="badge==='list'">
                    <div class="ukc-toolbar">
                        <a class="ukc-btn ukc-btn--blue ukc-btn--sm" href="{{ route('admin.documents.templates', ['edition_id' => $edition->id, 'kind' => 'badge']) }}"><x-ukc.icon name="plus" :size="16" /> Nouveau template badge</a>
                        <span class="sep"></span>
                        <span style="font-size:13px;color:var(--ukc-muted);">Formats A6 / A7 / CR80 · QR → pointage badges</span>
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
                                        <td>@if ($b['status'] === 'Actif')<span class="ukc-pill ukc-pill--ok">Actif</span>@else<span class="ukc-pill ukc-pill--muted">Brouillon</span>@endif</td>
                                        <td>
                                            <div class="ukc-actions" style="justify-content:flex-end;">
                                                <a class="ukc-btn--icon" title="Télécharger la planche (PDF)" href="{{ route('admin.documents.badges', $edition) }}" target="_blank"><svg viewBox="0 0 24 24" fill="currentColor" width="18" height="18"><path d="M19 9h-4V3H9v6H5l7 7 7-7zM5 18v2h14v-2H5z"/></svg></a>
                                                <a class="ukc-btn--icon" title="Pointage / envoi" href="{{ route('admin.badges.scan') }}"><svg viewBox="0 0 24 24" fill="currentColor" width="18" height="18"><path d="M2.01 21L23 12 2.01 3 2 10l15 2-15 2z"/></svg></a>
                                                <a class="ukc-btn--icon" title="Éditer le template" href="{{ route('admin.documents.templates', ['edition_id' => $edition->id, 'kind' => 'badge']) }}"><svg viewBox="0 0 24 24" fill="currentColor" width="18" height="18"><path d="M3 17.25V21h3.75L17.81 9.94l-3.75-3.75L3 17.25zM20.71 7.04c.39-.39.39-1.02 0-1.41l-2.34-2.34a.9959.9959 0 00-1.41 0l-1.83 1.83 3.75 3.75 1.83-1.83z"/></svg></a>
                                                <a class="ukc-btn--icon" title="Aperçu" href="{{ route('admin.documents.badges', $edition) }}" target="_blank"><svg viewBox="0 0 24 24" fill="currentColor" width="18" height="18"><path d="M12 4.5C7 4.5 2.73 7.61 1 12c1.73 4.39 6 7.5 11 7.5s9.27-3.11 11-7.5c-1.73-4.39-6-7.5-11-7.5zM12 17c-2.76 0-5-2.24-5-5s2.24-5 5-5 5 2.24 5 5-2.24 5-5 5zm0-8c-1.66 0-3 1.34-3 3s1.34 3 3 3 3-1.34 3-3-1.34-3-3-3z"/></svg></a>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="ukc-toolbar" style="margin-top:14px;">
                        <a class="ukc-btn ukc-btn--ghost ukc-btn--sm" href="{{ route('admin.badges.scan') }}"><x-ukc.icon name="video" :size="16" /> Pointage à l’accueil</a>
                    </div>
                </div>

                <div x-show="badge==='options'" x-cloak>
                    <form method="POST" action="{{ route('admin.documents.badges.options') }}" class="ukc-card" style="max-width:640px;">
                        @csrf
                        <h3>Options générales · Badges</h3>
                        <div class="desc">Génération, formats d’impression et contrôle d’accès.</div>
                        <div class="ukc-field" style="margin-bottom:12px;">
                            <label>Préfixe des références badge</label>
                            <input type="text" name="badgePrefix" value="{{ $opts['badgePrefix'] }}">
                        </div>
                        <div class="ukc-field" style="margin-bottom:12px;">
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
                        <label class="ukc-check"><input type="checkbox" name="badgeGenerateAfterPayment" value="1" @checked($opts['badgeGenerateAfterPayment'])> Générer après confirmation de paiement</label>
                        <label class="ukc-check"><input type="checkbox" name="badgePhotoRequired" value="1" @checked($opts['badgePhotoRequired'])> Photo participant obligatoire sur le badge</label>
                        <label class="ukc-check"><input type="checkbox" name="badgeQrAccess" value="1" @checked($opts['badgeQrAccess'])> QR de contrôle d’accès (badge-scan)</label>
                        <label class="ukc-check"><input type="checkbox" name="badgeBatchPrint" value="1" @checked($opts['badgeBatchPrint'])> Impression batch automatique (PDF planche)</label>
                        <div style="margin-top:16px;">
                            <button type="submit" class="ukc-btn ukc-btn--blue ukc-btn--sm"><x-ukc.icon name="check" :size="16" /> Enregistrer les options</button>
                        </div>
                    </form>
                </div>
            </div>

            {{-- ======================= LIVRE DES RÉSUMÉS ======================= --}}
            <div x-show="page==='livre'" x-cloak>
                <div class="ukc-tabs">
                    <button type="button" @click="livre='main'" :class="{ active: livre==='main' }">Livre des résumés</button>
                    <button type="button" @click="livre='modeles'" :class="{ active: livre==='modeles' }">Modèles de document</button>
                    <button type="button" @click="livre='apparence'" :class="{ active: livre==='apparence' }">Apparence du livre</button>
                </div>

                {{-- ---------- Livre : génération + état + avant-propos ---------- --}}
                <div x-show="livre==='main'">
                    <div class="ukc-card-grid">
                        <div class="ukc-card">
                            <h3>Générer le livre</h3>
                            <div class="desc">Compilation des résumés acceptés de l’édition, groupés par axe : aperçu HTML imprimable ou export Word (.docx) à la charte.</div>
                            <div style="display:flex;flex-wrap:wrap;gap:8px;">
                                <a class="ukc-btn ukc-btn--blue ukc-btn--sm" href="{{ route('admin.documents.livret', $edition) }}?format=docx"><x-ukc.icon name="file" :size="16" /> Générer le livre (.docx)</a>
                                <a class="ukc-btn ukc-btn--ghost ukc-btn--sm" href="{{ route('admin.documents.livret', $edition) }}" target="_blank"><x-ukc.icon name="book" :size="16" /> Aperçu</a>
                                <a class="ukc-btn ukc-btn--green ukc-btn--sm" href="{{ route('admin.documents.livret', $edition) }}?format=docx&harmonize=1"><x-ukc.icon name="sparkles" :size="16" /> Télécharger avec harmonisation IA</a>
                                <a class="ukc-btn ukc-btn--ghost ukc-btn--sm" href="{{ route('admin.documents.sessions', $edition) }}" target="_blank"><x-ukc.icon name="layers" :size="16" /> Fiches de session</a>
                            </div>
                            <p class="ukc-hint" style="margin-top:10px;">L’harmonisation IA normalise les noms d’auteurs, affiliations et accents avant export. Le .docx reprend le modèle actif et les réglages d’apparence.</p>
                        </div>
                        <div class="ukc-card">
                            <h3>État de compilation</h3>
                            <div class="desc">Dernière génération et statistiques.</div>
                            <p style="font-size:13.5px;line-height:1.7;">
                                <strong>Dernière génération :</strong> {{ $livreState['lastGen'] ? \Illuminate\Support\Carbon::parse($livreState['lastGen'])->format('d/m/Y H:i') : '—' }}<br>
                                <strong>Résumés inclus :</strong> {{ $counts['accepted'] }}<br>
                                <strong>Axes :</strong> {{ $counts['axes'] }} · <strong>Sessions :</strong> {{ $counts['sessions'] }}<br>
                                <strong>Modèle actif :</strong> {{ $opts['livretTemplate'] === 'default' ? 'Modèle par défaut (Apparence)' : $opts['livretTemplate'] }}<br>
                                <strong>Avant-propos :</strong> {{ trim($opts['forewordText']) !== '' ? 'défini' : 'non défini' }}
                                @if ($livreState['file'])<br><strong>Fichier :</strong> <code>{{ $livreState['file'] }}</code>@endif
                            </p>
                        </div>
                    </div>

                    <form method="POST" action="{{ route('admin.documents.livret.options') }}" class="ukc-card">
                        @csrf
                        <h3>Avant-propos du livre</h3>
                        <div class="desc">Texte introductif signé (Président du comité / autorités), inséré après la page de garde.</div>
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
                            <input type="text" name="forewordSignature" value="{{ $opts['forewordSignature'] }}" placeholder="Prof. … — Président du Comité d’Organisation">
                        </div>
                        <div style="display:flex;gap:8px;flex-wrap:wrap;">
                            <button type="submit" class="ukc-btn ukc-btn--blue ukc-btn--sm"><x-ukc.icon name="check" :size="16" /> Enregistrer l’avant-propos</button>
                            @if ($aiAvailable)
                                <button type="submit" formaction="{{ route('admin.documents.livret.foreword-ai', ['edition_id' => $edition->id]) }}"
                                        class="ukc-btn ukc-btn--green ukc-btn--sm"
                                        onclick="return confirm('Générer l’avant-propos par IA à partir des métadonnées de l’édition ? Le texte actuel sera remplacé.')">
                                    <x-ukc.icon name="sparkles" :size="16" /> Générer par IA
                                </button>
                            @endif
                        </div>
                        <p class="ukc-hint" style="margin-top:8px;">L’IA rédige un avant-propos à partir du colloque, du thème, des axes et du nombre de résumés retenus. Vérifiez, ajustez puis enregistrez.</p>
                    </form>
                </div>

                {{-- ---------- Modèles de document ---------- --}}
                <div x-show="livre==='modeles'" x-cloak>
                    <form method="POST" action="{{ route('admin.documents.livret.template') }}" class="ukc-card">
                        @csrf
                        <h3>Modèle global pour cette édition</h3>
                        <div class="desc">Définir le modèle Word utilisé pour compiler le livre des résumés.</div>
                        <div class="ukc-field" style="margin-bottom:14px;max-width:420px;">
                            <label>Modèle actif</label>
                            <select name="livretTemplate">
                                <option value="default" @selected($opts['livretTemplate'] === 'default')>Modèle par défaut (Apparence du livre)</option>
                                <option value="base" @selected($opts['livretTemplate'] === 'base')>Modèle de base UK Colloques</option>
                                @foreach ($templates as $tpl)
                                    <option value="{{ $tpl['name'] }}" @selected($opts['livretTemplate'] === $tpl['name'])>{{ $tpl['name'] }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div style="display:flex;flex-wrap:wrap;gap:8px;">
                            <a href="{{ route('admin.documents.livret.base-template') }}" class="ukc-btn ukc-btn--ghost ukc-btn--sm"><x-ukc.icon name="download" :size="16" /> Télécharger un modèle de base</a>
                            <button type="submit" class="ukc-btn ukc-btn--sm"><x-ukc.icon name="check" :size="16" /> Enregistrer le modèle actif</button>
                        </div>
                    </form>

                    <div class="ukc-card">
                        <h3>Ajouter un modèle personnalisé</h3>
                        <div class="desc">Déposez un fichier .docx contenant les balises de fusion (axes, résumés, auteurs…).</div>
                        <form method="POST" action="{{ route('admin.documents.livret.template') }}" enctype="multipart/form-data">
                            @csrf
                            <label class="ukc-drop">
                                Déposer un modèle .docx<br>
                                <span style="font-size:11px;">Balises : @{{edition_name}}, @{{abstract_title}}, @{{authors}}, @{{axis}}…</span>
                                <input type="file" name="templateFile" accept=".doc,.docx" style="display:none;" onchange="this.form.submit()">
                            </label>
                            @error('templateFile') <span class="error">{{ $message }}</span> @enderror
                        </form>

                        <div style="margin-top:14px;">
                            <div class="ukc-tpl-item">
                                <div class="ico">DOC</div>
                                <div class="meta"><strong>Modèle par défaut</strong><span>Généré depuis l’onglet Apparence · non modifiable ici</span></div>
                                <span class="ukc-pill ukc-pill--ok">Par défaut</span>
                            </div>
                            <div class="ukc-tpl-item">
                                <div class="ico">DOC</div>
                                <div class="meta"><strong>Modèle de base UK Colloques</strong><span>ukc-livre-base.doc · balises de fusion</span></div>
                                <div style="display:flex;gap:6px;flex-wrap:wrap;">
                                    <a href="{{ route('admin.documents.livret.base-template') }}" class="ukc-btn ukc-btn--ghost ukc-btn--sm">Télécharger</a>
                                    <form method="POST" action="{{ route('admin.documents.livret.template') }}" style="display:inline;">
                                        @csrf
                                        <input type="hidden" name="livretTemplate" value="base">
                                        <button type="submit" class="ukc-btn ukc-btn--sm">Utiliser</button>
                                    </form>
                                </div>
                            </div>
                            @forelse ($templates as $tpl)
                                <div class="ukc-tpl-item">
                                    <div class="ico">DOC</div>
                                    <div class="meta"><strong>{{ $tpl['name'] }}</strong><span>{{ number_format(($tpl['size'] ?? 0) / 1024, 0) }} Ko · importé le {{ $tpl['date'] ?? '—' }}</span></div>
                                    <div style="display:flex;gap:6px;flex-wrap:wrap;">
                                        <a href="{{ \Illuminate\Support\Facades\Storage::url($tpl['path']) }}" class="ukc-btn ukc-btn--ghost ukc-btn--sm" target="_blank">Télécharger</a>
                                        @php($isActive = $opts['livretTemplate'] === $tpl['name'])
                                        <form method="POST" action="{{ route('admin.documents.livret.template') }}" style="display:inline;">
                                            @csrf
                                            <input type="hidden" name="livretTemplate" value="{{ $tpl['name'] }}">
                                            <button type="submit" class="ukc-btn ukc-btn--sm" @disabled($isActive)>{{ $isActive ? 'Actif' : 'Utiliser' }}</button>
                                        </form>
                                    </div>
                                </div>
                            @empty
                            @endforelse
                        </div>
                    </div>
                </div>

                {{-- ---------- Apparence du livre ---------- --}}
                <div x-show="livre==='apparence'" x-cloak>
                    <form method="POST" action="{{ route('admin.documents.livret.apparence') }}" class="ukc-card" enctype="multipart/form-data">
                        @csrf
                        <h3>Apparence du livre · modèle par défaut</h3>
                        <div class="desc">Ces paramètres construisent le « modèle par défaut » proposé dans l’onglet Modèles de document.</div>

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
                                    @foreach (['Centré', 'Gauche', 'Justifié'] as $al)<option @selected($opts['livretAlignTitles'] === $al)>{{ $al }}</option>@endforeach
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
                                    <span class="ukc-hint">Logo actuel — déposez-en un nouveau pour le remplacer.</span>
                                </div>
                            @endif
                            @error('livretCoverLogo') <span class="error">{{ $message }}</span> @enderror
                        </div>
                        <label class="ukc-check"><input type="checkbox" name="livretShowTheme" value="1" @checked($opts['livretShowTheme'])> Afficher le thème de l’édition sur la couverture</label>
                        <label class="ukc-check"><input type="checkbox" name="livretShowDates" value="1" @checked($opts['livretShowDates'])> Afficher les dates et le lieu</label>
                        <label class="ukc-check"><input type="checkbox" name="livretNumber" value="1" @checked($opts['livretNumber'])> Numérotation des résumés</label>

                        <div style="margin-top:16px;display:flex;gap:8px;flex-wrap:wrap;">
                            <button type="submit" class="ukc-btn"><x-ukc.icon name="check" :size="16" /> Enregistrer l’apparence</button>
                            <a class="ukc-btn ukc-btn--ghost" href="{{ route('admin.documents.livret', $edition) }}" target="_blank"><x-ukc.icon name="eye" :size="16" /> Aperçu couverture</a>
                        </div>
                    </form>
                </div>
            </div>
        @endif
    </div>
</x-layouts.admin>
