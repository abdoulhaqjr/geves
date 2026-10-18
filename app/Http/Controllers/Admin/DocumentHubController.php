<?php

namespace App\Http\Controllers\Admin;

use App\Actions\IssueAttestation;
use App\AI\AiManager;
use App\Documents\AttestationCertifier;
use App\Documents\LayoutSanitizer;
use App\Documents\LivretAppearance;
use App\Documents\LivretDocx;
use App\Documents\LivretTemplate;
use App\Http\Controllers\Controller;
use App\Models\Attestation;
use App\Models\DocumentTemplate;
use App\Models\Event;
use App\Models\Formation;
use App\Models\Participant;
use App\Models\Review;
use App\Models\Submission;
use App\Support\AttestationPolicy;
use App\Support\AttestationQuery;
use App\Support\BadgeCatalog;
use App\Support\CommunicationAttestations;
use App\Support\EventSettings;
use App\Support\EventStorage;
use App\Support\ListSort;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

/**
 * Hub Documents d'un événement (contrôleur + Blade, fidèle au mockup
 * admin-documents) : trois volets - Attestations (émission par type + options),
 * Badges (génération + options) et Livre des résumés (avant-propos, apparence).
 * Options persistées dans les réglages. Volets/onglets pilotés par Alpine.
 */
class DocumentHubController extends Controller
{
    use Concerns\ScopesEditionAccess;

    public function index(Request $request): View
    {
        $event = $this->currentEdition($request);

        $attestations = $event
            ? Attestation::where('event_id', $event->id)->latest('id')->get()
            : collect();

        // Types proposés PAR DÉFAUT. ⚠️ « soumission » et « acceptation » ne sont
        // plus offerts d'office (ils restent définis dans Attestation::TYPES pour
        // les données existantes, mais ne sont ni créés ni listés ici).
        // ⚠️ Modération / rapporteur = UNE par personne et par événement : le
        // bénéficiaire est une personne qui a modéré (resp. rapporté) AU MOINS une
        // séance, pas une attestation par séance (cf. targetsFor + idempotence).
        $typeMeta = [
            'communication' => ['title' => 'Attestation de communication', 'sub' => 'Orale / poster', 'scope' => 'Par communication'],
            'participation' => ['title' => 'Attestation de participation', 'sub' => 'Une par participant', 'scope' => 'Participant'],
            'moderation' => ['title' => 'Attestation de modération', 'sub' => 'Modérateurs de séance', 'scope' => 'Une par modérateur'],
            'rapporteur' => ['title' => 'Attestation de rapporteur', 'sub' => 'Rapporteurs de séance', 'scope' => 'Une par rapporteur'],
            'organisation' => ['title' => 'Attestation de participation à l’organisation', 'sub' => 'Équipe d’appui au comité d’organisation', 'scope' => 'Hors comités'],
            'comite_organisation' => ['title' => 'Attestation du comité d’organisation', 'sub' => 'Membres du comité d’organisation', 'scope' => 'Comité d’organisation'],
            'instruction_resumes' => ['title' => 'Attestation d’instruction de résumés', 'sub' => 'Relecteurs ayant évalué des résumés', 'scope' => 'Une par relecteur'],
            // Module Formation : les modèles se conçoivent ici, mais l'émission se
            // fait formation par formation (cf. Admin\FormationController).
            'formation' => ['title' => 'Attestation de formation', 'sub' => 'Inscrits aux formations', 'scope' => 'Par formation'],
            'formation_animation' => ['title' => 'Attestation d’animation', 'sub' => 'Formateurs', 'scope' => 'Par formation'],
        ];

        $rows = [];
        if ($event) {
            $formationCounts = $this->formationTargetCounts($event);
            // Chaque type a un modèle ENREGISTRÉ (créé ici au besoin à partir de la
            // mise en page standard) : il n'y a plus de repli implicite, donc tous
            // sont visibles, modifiables et supprimables de la même façon.
            foreach (array_keys($typeMeta) as $key) {
                DocumentTemplate::ensureFor((int) $event->id, 'attestation', $key, $typeMeta[$key]['title']);
            }

            $templates = DocumentTemplate::where('event_id', $event->id)
                ->where('kind', 'attestation')
                ->get()->keyBy('type');
            $customTitles = $templates->map(fn (DocumentTemplate $t) => $t->title);

            foreach ($typeMeta as $key => $meta) {
                // ⚠️ Ce que l'émission ferait VRAIMENT, écarts compris : le bouton
                // engageait un lot sans jamais dire ce qu'il contenait. Avec la
                // réserve aux présents, un nombre qui baisse sans explication se lit
                // comme une panne - et on réimprime pour rien.
                $apercu = $key === 'communication' ? CommunicationAttestations::preview($event) : null;
                $eligible = match ($key) {
                    'communication' => $apercu['retenus'],
                    'formation', 'formation_animation' => $formationCounts[$key],
                    default => $this->targetsFor($event, $key)->count(),
                };
                $rows[$key] = [
                    'label' => Attestation::TYPES[$key],
                    'title' => filled($customTitles[$key] ?? null) ? (string) $customTitles[$key] : $meta['title'],
                    'sub' => $meta['sub'],
                    'scope' => $meta['scope'],
                    'eligible' => $eligible,
                    // Écartés faute de présence (attestation de communication).
                    'excluded' => $apercu['ecartes'] ?? 0,
                    'issued' => $attestations->where('type', $key)->where('revoked', false)->count(),
                    // Non pas un état qu'on bascule : un modèle est « prêt » dès
                    // qu'il a des destinataires éligibles. Le libellé le dit.
                    'status' => $eligible > 0 ? 'Actif' : 'Sans destinataire',
                    // Émission déportée : ces attestations s'émettent depuis la page
                    // de chaque formation, pas globalement pour l'événement.
                    'issueFrom' => in_array($key, ['formation', 'formation_animation'], true)
                        ? route('admin.formations', ['event_id' => $event->id])
                        : null,
                    // Modèle réellement enregistré (sinon c'est le défaut à la charte,
                    // qui n'a rien à supprimer).
                    'template' => $templates[$key] ?? null,
                ];
            }
        }

        $counts = $event ? [
            'participants' => Participant::where('event_id', $event->id)->count(),
            'accepted' => Submission::where('event_id', $event->id)->where('revision_of', 0)->where('status', 'accepted')->count(),
            'issued' => $attestations->where('revoked', false)->count(),
            'sessions' => $event->sessions()->count(),
            'axes' => count($event->settings['tracks'] ?? []),
        ] : ['participants' => 0, 'accepted' => 0, 'issued' => 0, 'sessions' => 0, 'axes' => 0];

        $badgeRows = [];
        if ($event) {
            foreach (BadgeCatalog::TYPES as $type => $meta) {
                $template = DocumentTemplate::ensureFor((int) $event->id, 'badge', $type, $meta['title']);
                $layout = LayoutSanitizer::sanitize((array) $template->layout, 'badge', $type);
                $dimensions = $layout['width'].'×'.$layout['height'];
                $badgeRows[] = [
                    'key' => $type,
                    'type' => $meta['label'],
                    'title' => (string) $template->title,
                    'sub' => $meta['source'],
                    'format' => match ($dimensions) {
                        '397×560' => 'A6 Portrait',
                        '560×397' => 'A6 Paysage',
                        '280×397' => 'A7 Portrait',
                        '340×208' => 'Carte 90 × 55 mm',
                        '324×204' => 'CR80',
                        default => $dimensions.' px',
                    },
                    'count' => BadgeCatalog::recipients($event, $type)->count(),
                    'qr' => collect($layout['elements'] ?? [])->contains(fn ($element) => ($element['type'] ?? null) === 'qr'),
                ];
            }
        }
        $badgeReadyCount = array_sum(array_column($badgeRows, 'count'));

        // Onglet et sous-onglet portés par l'URL (?tab=&stab=) pour que chaque
        // onglet ait son lien. Le jeu de sous-onglets dépend de l'onglet.
        $tab = (string) $request->query('tab', 'attestations');
        $tab = in_array($tab, ['attestations', 'badges', 'livre'], true) ? $tab : 'attestations';

        $allowed = match ($tab) {
            'badges' => ['modeles', 'prets', 'options'],
            'attestations' => ['modeles', 'delivrees', 'options'],
            'livre' => ['main', 'modeles', 'apparence'],
        };
        $defaultStab = $tab === 'livre' ? 'main' : 'modeles';
        $stab = (string) $request->query('stab', $defaultStab);
        $stab = in_array($stab, $allowed, true) ? $stab : $defaultStab;

        return view('admin.documents.attestations', [
            'events' => $this->accessibleEditions($request, 'documents'),
            'event' => $event,
            'event_id' => $event ? (string) $event->id : '',
            'types' => Attestation::TYPES,
            'attestations' => $attestations,
            'rows' => $rows,
            'counts' => $counts,
            'badgeReadyCount' => $badgeReadyCount,
            'tab' => $tab,
            'stab' => $stab,
            ...($tab === 'attestations' && $stab === 'delivrees' ? $this->issuedList($request, $event) : []),
            ...($tab === 'badges' && $stab === 'prets' ? $this->badgeReadyList($request, $event) : []),
            // ⚠️ La page qui ÉMET doit dire ce que l'émission déclenche : en ligne,
            // chaque attestation part par e-mail ; sur papier, rien ne part.
            'attDelivery' => AttestationPolicy::delivery($event),
            'attDeliveryLabel' => AttestationPolicy::DELIVERIES[AttestationPolicy::delivery($event)],
            'attPaper' => AttestationPolicy::isPaper($event),
            'opts' => [
                'attPrefix' => (string) EventSettings::get($event, 'attestations.prefix', 'UKC-ATT-2026-'),
                // Signataire porté par les jetons [signatory.name] / [signatory.role]
                // des modèles : sans lui, la ligne de signature sortait vide.
                'attSignatory' => (string) EventSettings::get($event, 'attestations.signatory', ''),
                'attSignatoryRole' => (string) EventSettings::get($event, 'attestations.signatory_role', 'Président du comité d’organisation'),
                'attAuto' => (bool) EventSettings::get($event, 'attestations.auto', true),
                'attNotify' => (bool) EventSettings::get($event, 'attestations.notify', true),
                'attRequirePresence' => (bool) EventSettings::get($event, 'attestations.require_presence', false),
                // Comment l'événement délivre : en ligne (envoi + téléchargement) ou
                // sur papier (le secrétariat imprime et remet).
                'attDelivery' => AttestationPolicy::delivery($event),
                'attDeliveries' => AttestationPolicy::DELIVERIES,
                'attCommunicationScope' => AttestationPolicy::communicationScope($event),
                'attScopes' => AttestationPolicy::SCOPES,
                'attSms' => (bool) EventSettings::get($event, 'attestations.sms', false),
                'attIncludeQr' => (bool) EventSettings::get($event, 'attestations.include_qr', true),
                'attAllowBeforeValidation' => (bool) EventSettings::get($event, 'attestations.allow_before_validation', false),
                // Identité vérifiable (DIDs) - signature EdDSA / VC-JWT effective.
                'attDidIssuer' => (string) EventSettings::get($event, 'attestations.did_issuer', 'did:web:univkara.tg:colloques'),
                'attDidMethod' => (string) EventSettings::get($event, 'attestations.did_method', 'did:web:univkara.tg:colloques#key-ed25519-1'),
                'attCryptoSuite' => (string) EventSettings::get($event, 'attestations.crypto_suite', 'EdDSA (VC-JWT)'),
                'attCertEnabled' => app(AttestationCertifier::class)->enabled(),
                'badgePrefix' => (string) EventSettings::get($event, 'badges.prefix', 'UKC-2026-'),
                'badgeFormat' => (string) EventSettings::get($event, 'badges.format', 'A6 Portrait'),
                'badgeQrAccess' => (bool) EventSettings::get($event, 'badges.qr_access', true),
                'badgeBatchPrint' => (bool) EventSettings::get($event, 'badges.batch_print', false),
                'forewordTitle' => (string) EventSettings::get($event, 'livret.foreword_title', 'Avant-propos'),
                'forewordText' => (string) EventSettings::get($event, 'livret.foreword_text', ''),
                'forewordSignature' => (string) EventSettings::get($event, 'livret.signature', ''),
                // Sections de tête génériques du livret (masquées si vides).
                'livretPatronage' => (string) EventSettings::get($event, 'livret.patronage', ''),
                'livretAckTitle' => (string) EventSettings::get($event, 'livret.ack_title', 'Remerciements'),
                'livretAcknowledgements' => (string) EventSettings::get($event, 'livret.acknowledgements', ''),
                'livretWelcomeTitle' => (string) EventSettings::get($event, 'livret.welcome_title', 'Mot de bienvenue'),
                'livretWelcomeText' => (string) EventSettings::get($event, 'livret.welcome_text', ''),
                'livretWelcomeSignature' => (string) EventSettings::get($event, 'livret.welcome_signature', ''),
                'livretPresidentTitle' => (string) EventSettings::get($event, 'livret.president_title', 'Mot du président'),
                'livretPresidentText' => (string) EventSettings::get($event, 'livret.president_text', ''),
                'livretPresidentSignature' => (string) EventSettings::get($event, 'livret.president_signature', ''),
                'livretShowTheme' => (bool) LivretAppearance::get($event, 'show_theme', true),
                'livretShowDates' => (bool) LivretAppearance::get($event, 'show_dates', true),
                'livretNumber' => (bool) LivretAppearance::get($event, 'number_abstracts', false),
                'livretAuthorIndex' => (bool) LivretAppearance::get($event, 'author_index', true),
                'livretPageNumbers' => (bool) LivretAppearance::get($event, 'page_numbers', true),
                // Apparence propre au modèle de livre actuellement actif.
                'livretFontBody' => (string) LivretAppearance::get($event, 'font_body', 'Times New Roman'),
                'livretFontHead' => (string) LivretAppearance::get($event, 'font_head', 'Jost'),
                'livretSizeBody' => (int) LivretAppearance::get($event, 'size_body', 11),
                'livretSizeCover' => (int) LivretAppearance::get($event, 'size_cover', 24),
                'livretSizeAxis' => (int) LivretAppearance::get($event, 'size_axis', 16),
                'livretSizeAbstract' => (int) LivretAppearance::get($event, 'size_abstract', 12),
                'livretSizeAuthors' => (int) LivretAppearance::get($event, 'size_authors', 10),
                'livretLineHeight' => (string) LivretAppearance::get($event, 'line_height', '1.15'),
                'livretColorPrimary' => (string) LivretAppearance::get($event, 'color_primary', '#223ea4'),
                'livretColorSecondary' => (string) LivretAppearance::get($event, 'color_secondary', '#009933'),
                'livretAlignTitles' => (string) LivretAppearance::get($event, 'align_titles', 'Centré'),
                'livretAlignAxis' => (string) LivretAppearance::get($event, 'align_axis', 'Centré'),
                'livretAlignAuthors' => (string) LivretAppearance::get($event, 'align_authors', 'Centré'),
                'livretAlignBody' => (string) LivretAppearance::get($event, 'align_body', 'Justifié'),
                'livretCoverMention' => (string) LivretAppearance::get($event, 'cover_mention', ''),
                'livretCoverLogo' => (string) LivretAppearance::get($event, 'cover_logo', ''),
                'livretTemplate' => (string) EventSettings::get($event, 'livret.template', 'default'),
            ],
            'templates' => is_array(EventSettings::get($event, 'livret.templates')) ? EventSettings::get($event, 'livret.templates') : [],
            // Données du livre préparées côté contrôleur (la vue mélange des @php
            // en ligne : y ajouter un bloc @php casse la compilation Blade).
            ...$this->livretViewExtras($event),
            'badgeRows' => $badgeRows,
            'livreState' => [
                'lastGen' => EventSettings::get($event, 'livret.last_generated_at'),
                'file' => (string) EventSettings::get($event, 'livret.last_file', ''),
            ],
            'aiAvailable' => app(AiManager::class)->available() !== [],
        ]);
    }

    /**
     * Données du volet « Livre des résumés » calculées côté contrôleur (modèle
     * actif, état des sections de tête, catalogue des modèles intégrés). On évite
     * un bloc @php dans la vue : elle contient déjà des @php en ligne, et le mélange
     * casse la compilation Blade (undefined variable plus loin dans la page).
     *
     * @return array<string, mixed>
     */
    private function livretViewExtras(?Event $event): array
    {
        $active = (string) EventSettings::get($event, 'livret.template', 'default');
        $activeLabel = LivretDocx::STYLES[$active] ?? ($active === 'base' ? 'Modèle de base' : $active);

        $sections = [
            'Avant-propos' => trim((string) EventSettings::get($event, 'livret.foreword_text', '')) !== '',
            'Haut patronage' => trim((string) EventSettings::get($event, 'livret.patronage', '')) !== '',
            'Remerciements' => trim((string) EventSettings::get($event, 'livret.acknowledgements', '')) !== '',
            'Mot de bienvenue' => trim((string) EventSettings::get($event, 'livret.welcome_text', '')) !== '',
            'Mot du président' => trim((string) EventSettings::get($event, 'livret.president_text', '')) !== '',
        ];

        return [
            'livretActiveLabel' => $activeLabel,
            'livretSectionCount' => count($sections),
            'livretSectionsFilled' => count(array_filter($sections)),
            'livretBuiltins' => [
                // ⚠️ Chaque style intégré se télécharge en .docx pour l'événement
                // courant, via `?template=<clé>` (cf. DocumentController::templateKey) :
                // on obtient le Word d'un style SANS avoir à le rendre actif d'abord.
                ['key' => 'default', 'name' => 'Modèle par défaut', 'desc' => 'Couverture sobre - commissions numérotées - programme par jour', 'download' => $this->livretStyleDownload($event, 'default')],
                ['key' => 'fss', 'name' => 'Modèle 2 - Journées scientifiques', 'desc' => 'Couverture sobre - comités simples - programme par jour, salle et séance', 'download' => $this->livretStyleDownload($event, 'fss')],
                ['key' => 'general', 'name' => 'Modèle 3 - Document général du colloque', 'desc' => 'Comité d’organisation en tableau - programme synthétique - communications en ateliers - résumés par axe', 'download' => $this->livretStyleDownload($event, 'general')],
                // 'base' = gabarit à balises de fusion (vide), indépendant de l'événement.
                ['key' => 'base', 'name' => 'Modèle de base '.config('app.name'), 'desc' => 'Document .docx à balises de fusion, à personnaliser dans Word', 'download' => route('admin.documents.livret.base-template')],
            ],
        ];
    }

    /**
     * URL de téléchargement .docx d'un style intégré pour cet événement, ou null
     * sans événement de contexte (la route a besoin de l'événement).
     */
    private function livretStyleDownload(?Event $event, string $style): ?string
    {
        return $event
            ? route('admin.documents.livret', ['event' => $event, 'format' => 'docx', 'template' => $style])
            : null;
    }

    /** Émet les attestations d'un type pour son audience éligible (idempotent). */
    public function issueType(Request $request, IssueAttestation $action): RedirectResponse
    {
        $event = Event::find($request->input('event_id'));
        $type = (string) $request->input('type');

        if (! $event || ! isset(Attestation::TYPES[$type])) {
            return back();
        }

        $count = 0;
        if ($type === 'communication') {
            // Par communication : auteur correspondant + co-auteurs payés, dans la
            // limite des plafonds (coauthors_max, attestation_max - priorité auteur
            // correspondant). Une attestation à leur nom par communication associée.
            foreach (CommunicationAttestations::entitlements($event) as $ent) {
                $action->handle($ent['participant'], 'communication', $ent['submission']);
                $count++;
            }
        } else {
            foreach ($this->targetsFor($event, $type) as $participant) {
                $action->handle($participant, $type);
                $count++;
            }
        }

        $message = $count.' attestation(s) « '.Attestation::TYPES[$type].' » émise(s).';

        // ⚠️ Le bouton « Émettre et exporter » ne générait AUCUN PDF : il émettait
        // et rendait la main, laissant croire que le lot était prêt. Il mène
        // maintenant à la liste délivrée, filtrée sur ce type, où les lots PDF et
        // la liste d'émargement attendent.
        if ((string) $request->input('mode') === 'zip') {
            return redirect()->route('admin.documents.attestations', [
                'event_id' => $event->id, 'tab' => 'attestations', 'stab' => 'delivrees',
                'type' => $type, 'state' => 'valid',
            ])->with('ukc_status', $message.' Choisissez un lot à imprimer ci-dessous.');
        }

        return back()->with('ukc_status', $message);
    }

    public function revoke(Attestation $attestation): RedirectResponse
    {
        $attestation->update(['revoked' => true]);

        return back()->with('ukc_status', 'Attestation révoquée.');
    }

    public function restore(Attestation $attestation): RedirectResponse
    {
        $attestation->update(['revoked' => false]);

        return back()->with('ukc_status', 'Attestation rétablie.');
    }

    public function saveAttestationOptions(Request $request): RedirectResponse
    {
        $event = $this->currentEdition($request);
        if (! $event) {
            return back()->with('ukc_error', 'Sélectionnez un événement.');
        }

        EventSettings::setMany($event, [
            'attestations.prefix' => (string) $request->input('attPrefix'),
            'attestations.signatory' => (string) $request->input('attSignatory'),
            'attestations.signatory_role' => (string) $request->input('attSignatoryRole'),
            'attestations.auto' => $request->boolean('attAuto'),
            'attestations.notify' => $request->boolean('attNotify'),
            'attestations.require_presence' => $request->boolean('attRequirePresence'),
            'attestations.delivery' => (string) $request->input('attDelivery'),
            'attestations.communication_scope' => (string) $request->input('attCommunicationScope'),
            'attestations.sms' => $request->boolean('attSms'),
            'attestations.include_qr' => $request->boolean('attIncludeQr'),
            'attestations.allow_before_validation' => $request->boolean('attAllowBeforeValidation'),
            'attestations.did_issuer' => (string) $request->input('attDidIssuer'),
            'attestations.did_method' => (string) $request->input('attDidMethod'),
            'attestations.crypto_suite' => (string) $request->input('attCryptoSuite'),
        ]);

        return back()->with('ukc_status', 'Options des attestations enregistrées.');
    }

    public function saveBadgeOptions(Request $request): RedirectResponse
    {
        $event = $this->currentEdition($request);
        if (! $event) {
            return back()->with('ukc_error', 'Sélectionnez un événement.');
        }

        EventSettings::setMany($event, [
            'badges.prefix' => (string) $request->input('badgePrefix'),
            'badges.format' => (string) $request->input('badgeFormat'),
            'badges.qr_access' => $request->boolean('badgeQrAccess'),
            'badges.batch_print' => $request->boolean('badgeBatchPrint'),
        ]);

        return back()->with('ukc_status', 'Options des badges enregistrées.');
    }

    public function saveLivretOptions(Request $request): RedirectResponse
    {
        $event = $this->currentEdition($request);
        if (! $event) {
            return back()->with('ukc_error', 'Sélectionnez un événement.');
        }

        EventSettings::setMany($event, [
            'livret.foreword_title' => trim((string) $request->input('forewordTitle')),
            'livret.foreword_text' => trim((string) $request->input('forewordText')),
            'livret.signature' => trim((string) $request->input('forewordSignature')),
            // Sections de tête génériques (masquées si vides).
            'livret.patronage' => trim((string) $request->input('livretPatronage')),
            'livret.ack_title' => trim((string) $request->input('livretAckTitle')),
            'livret.acknowledgements' => trim((string) $request->input('livretAcknowledgements')),
            'livret.welcome_title' => trim((string) $request->input('livretWelcomeTitle')),
            'livret.welcome_text' => trim((string) $request->input('livretWelcomeText')),
            'livret.welcome_signature' => trim((string) $request->input('livretWelcomeSignature')),
            'livret.president_title' => trim((string) $request->input('livretPresidentTitle')),
            'livret.president_text' => trim((string) $request->input('livretPresidentText')),
            'livret.president_signature' => trim((string) $request->input('livretPresidentSignature')),
            'livret.show_theme' => $request->boolean('livretShowTheme'),
            'livret.show_dates' => $request->boolean('livretShowDates'),
            'livret.number_abstracts' => $request->boolean('livretNumber'),
        ]);

        return back()->with('ukc_status', 'Contenu du livre enregistré.');
    }

    /**
     * Génère l'avant-propos du livret par IA à partir des métadonnées de l'événement
     * (logique du projet uk-colloques), et le persiste dans les réglages `livret.*`.
     */
    public function generateForewordAi(Request $request, AiManager $ai): RedirectResponse
    {
        $event = $this->currentEdition($request);

        if (! $event) {
            return back()->with('ukc_error', 'Sélectionnez un événement.');
        }
        if (! $ai->resolve()) {
            return back()->with('ukc_error', 'Aucun fournisseur IA configuré (Réglages → Intelligence artificielle).');
        }

        $nbAccepted = $event->submissions()->where('revision_of', 0)->where('status', 'accepted')->count();
        $tracks = is_array($event->settings['tracks'] ?? null) ? $event->settings['tracks'] : [];

        $facts = array_filter([
            'Événement : '.($event->series?->name ?: $event->title),
            $event->title ? 'Événement : '.$event->title : null,
            $event->event_label ? 'Libellé : '.$event->event_label : null,
            $event->theme ? 'Thème : '.$event->theme : null,
            $event->date_start ? 'Dates : '.$event->date_start->format('d/m/Y').($event->date_end ? ' - '.$event->date_end->format('d/m/Y') : '') : null,
            $event->location ? 'Lieu : '.$event->location : null,
            $tracks ? 'Axes thématiques : '.implode(' ; ', $tracks) : null,
            'Nombre de résumés retenus : '.$nbAccepted,
            trim((string) EventSettings::get($event, 'livret.signature', '')) ? 'Signataire : '.EventSettings::get($event, 'livret.signature') : null,
        ]);

        $instruction = "Tu es un rédacteur académique francophone. Tu écris l'avant-propos (préface) d'un livre des résumés "
            .'de colloque scientifique. Registre soutenu, sobre et chaleureux, sans emphase excessive, en 3 à 4 paragraphes '
            ."séparés par une ligne vide, sans titre, sans puces, sans Markdown.\n\n"
            ."Rédige l'avant-propos à partir de ces éléments :\n".implode("\n", $facts)."\n\n"
            ."L'avant-propos doit situer le colloque et son thème, souligner l'importance scientifique des contributions, "
            .'remercier les auteurs, les comités et les partenaires, et souhaiter de fructueux échanges. '
            ."N'invente aucun chiffre dont tu n'es pas sûr.\n\n"
            .'Réponds UNIQUEMENT par un objet JSON de la forme {"foreword": "…texte de l\'avant-propos avec des sauts de ligne…"}.';

        try {
            $res = $ai->extract($instruction, [], 'deep');
        } catch (\Throwable $e) {
            report($e);

            return back()->with('ukc_error', 'Échec de la génération IA : '.$e->getMessage());
        }

        $text = trim((string) ($res['foreword'] ?? ''));
        if ($text === '') {
            return back()->with('ukc_error', 'L’IA n’a pas renvoyé d’avant-propos exploitable.');
        }

        EventSettings::setMany($event, [
            'livret.foreword_text' => $text,
            'livret.foreword_title' => trim((string) EventSettings::get($event, 'livret.foreword_title', 'Avant-propos')) ?: 'Avant-propos',
        ]);

        return back()->with('ukc_status', 'Avant-propos généré par IA - vérifiez puis enregistrez.');
    }

    /** Apparence du livre (typographie, couleurs, page de garde) du modèle actif. */
    public function saveLivretApparence(Request $request): RedirectResponse
    {
        $event = $this->currentEdition($request);
        if (! $event) {
            return back()->with('ukc_error', 'Sélectionnez un événement.');
        }

        if ($request->input('livretModel') !== LivretAppearance::activeModel($event)) {
            return back()->with('ukc_error', 'Le modèle actif a changé. Rechargez la page avant d’enregistrer son apparence.');
        }

        $request->validate([
            'livretFontBody' => ['required', 'in:Times New Roman,Jost,Georgia,Arial,Garamond'],
            'livretFontHead' => ['required', 'in:Jost,Playfair Display,Times New Roman,Arial,Georgia'],
            'livretSizeBody' => ['required', 'integer', 'between:8,16'],
            'livretSizeCover' => ['required', 'integer', 'between:14,48'],
            'livretSizeAxis' => ['required', 'integer', 'between:12,28'],
            'livretSizeAbstract' => ['required', 'integer', 'between:10,18'],
            'livretSizeAuthors' => ['required', 'integer', 'between:8,14'],
            'livretLineHeight' => ['required', 'in:1.0,1.15,1.5,2.0'],
            'livretColorPrimary' => ['required', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'livretColorSecondary' => ['required', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'livretAlignTitles' => ['required', 'in:Centré,Gauche,Droite,Justifié'],
            'livretAlignAxis' => ['required', 'in:Centré,Gauche,Droite'],
            'livretAlignAuthors' => ['required', 'in:Centré,Gauche,Droite'],
            'livretAlignBody' => ['required', 'in:Justifié,Gauche'],
            'livretCoverMention' => ['nullable', 'string', 'max:255'],
            'livretCoverLogo' => ['nullable', 'image', 'mimes:png,jpg,jpeg,webp,svg', 'max:4096'],
        ]);

        $values = [
            'font_body' => (string) $request->input('livretFontBody'),
            'font_head' => (string) $request->input('livretFontHead'),
            'size_body' => (int) $request->input('livretSizeBody'),
            'size_cover' => (int) $request->input('livretSizeCover'),
            'size_axis' => (int) $request->input('livretSizeAxis'),
            'size_abstract' => (int) $request->input('livretSizeAbstract'),
            'size_authors' => (int) $request->input('livretSizeAuthors'),
            'line_height' => (string) $request->input('livretLineHeight'),
            'color_primary' => (string) $request->input('livretColorPrimary'),
            'color_secondary' => (string) $request->input('livretColorSecondary'),
            'align_titles' => (string) $request->input('livretAlignTitles'),
            'align_axis' => (string) $request->input('livretAlignAxis'),
            'align_authors' => (string) $request->input('livretAlignAuthors'),
            'align_body' => (string) $request->input('livretAlignBody'),
            'cover_mention' => trim((string) $request->input('livretCoverMention')),
            'show_theme' => $request->boolean('livretShowTheme'),
            'show_dates' => $request->boolean('livretShowDates'),
            'number_abstracts' => $request->boolean('livretNumber'),
            'author_index' => $request->boolean('livretAuthorIndex'),
            'page_numbers' => $request->boolean('livretPageNumbers'),
        ];
        if ($request->hasFile('livretCoverLogo')) {
            $values['cover_logo'] = $request->file('livretCoverLogo')
                ->store(EventStorage::in(EventStorage::LIVRET, $event), 'public');
        }
        LivretAppearance::save($event, $values);

        return back()->with('ukc_status', 'Apparence du livre enregistrée.');
    }

    /** Modèle actif + import optionnel d'un modèle .docx personnalisé. */
    public function saveLivretTemplate(Request $request): RedirectResponse
    {
        $event = $this->currentEdition($request);
        if (! $event) {
            return back()->with('ukc_error', 'Sélectionnez un événement.');
        }

        $request->validate([
            'livretTemplate' => ['nullable', 'string', 'max:100'],
            'templateFile' => ['nullable', 'file', 'mimes:doc,docx', 'max:5120'],
        ]);

        $templates = is_array(EventSettings::get($event, 'livret.templates')) ? EventSettings::get($event, 'livret.templates') : [];

        if ($request->hasFile('templateFile')) {
            $file = $request->file('templateFile');
            $path = $file->store(EventStorage::in(EventStorage::LIVRET_TEMPLATES, $event), 'public');
            $templates[] = [
                'name' => $file->getClientOriginalName(),
                'path' => $path,
                'size' => $file->getSize(),
                'date' => now()->format('d/m/Y'),
            ];
            EventSettings::set($event, 'livret.templates', array_values($templates));
        }

        if ($request->filled('livretTemplate')) {
            LivretAppearance::captureLegacy($event);
            EventSettings::set($event, 'livret.template', (string) $request->input('livretTemplate'));
        }

        return back()->with('ukc_status', 'Modèle du livre enregistré.');
    }

    /**
     * Supprime un modèle .docx personnalisé : retire le fichier du disque et de la
     * liste. Si c'était le modèle actif, on retombe sur le modèle par défaut.
     */
    public function deleteLivretTemplate(Request $request): RedirectResponse
    {
        $event = $this->currentEdition($request);
        if (! $event) {
            return back()->with('ukc_error', 'Sélectionnez un événement.');
        }

        $name = (string) $request->input('name');
        $templates = is_array(EventSettings::get($event, 'livret.templates')) ? EventSettings::get($event, 'livret.templates') : [];

        $kept = [];
        foreach ($templates as $tpl) {
            if (($tpl['name'] ?? null) === $name) {
                if (! empty($tpl['path'])) {
                    Storage::disk('public')->delete((string) $tpl['path']);
                }

                continue;
            }
            $kept[] = $tpl;
        }
        EventSettings::set($event, 'livret.templates', $kept);

        // Le modèle supprimé était actif → repli sur le modèle par défaut.
        if ((string) EventSettings::get($event, 'livret.template', 'default') === $name) {
            LivretAppearance::captureLegacy($event);
            EventSettings::set($event, 'livret.template', 'default');
        }
        LivretAppearance::forget($event, $name);

        return back()->with('ukc_status', 'Modèle personnalisé supprimé.');
    }

    /**
     * Page dédiée « Attestations délivrées » : liste des attestations émises pour
     * l'événement (bénéficiaire, type, date, état) avec révocation / rétablissement.
     * L'émission et les templates restent gérés dans le hub Documents.
     */
    /**
     * Données du sous-onglet « Attestations délivrées » (liste filtrable, triable
     * et paginée). Servi par la page Documents pour que l'onglet ait une URL.
     *
     * @return array<string, mixed>
     */
    private function issuedList(Request $request, ?Event $event): array
    {
        // ⚠️ Les filtres viennent d'AttestationQuery, partagés avec les exports :
        // on imprime ce qu'on regarde. Deux constructions de requête finiraient
        // par diverger, et le lot ne correspondrait plus à la liste.
        $query = AttestationQuery::filtered($request, $event);

        $type = (string) $request->query('type', '');
        $state = (string) $request->query('state', '');
        $delivery = (string) $request->query('delivery', '');
        $search = trim((string) $request->query('search', ''));
        $from = (string) $request->query('from', '');
        $to = (string) $request->query('to', '');
        $sort = ListSort::resolve(
            $request,
            ['recipient_name', 'type', 'uuid', 'issued_at', 'revoked', 'delivered_at'],
            'issued_at',
            'desc'
        );

        $issued = $query->with('participant')
            ->orderBy($sort['sort'], $sort['dir'])
            ->orderBy('id', 'desc')
            ->paginate(20)->withQueryString();

        $stats = $event ? [
            'total' => Attestation::where('event_id', $event->id)->count(),
            'valid' => Attestation::where('event_id', $event->id)->where('revoked', false)->count(),
            'revoked' => Attestation::where('event_id', $event->id)->where('revoked', true)->count(),
            // Délivrance papier : « ce qui reste à remettre » est la question du
            // guichet, et elle n'a de sens que sur les attestations valides.
            'delivered' => Attestation::where('event_id', $event->id)->whereNotNull('delivered_at')->count(),
            'pending' => Attestation::where('event_id', $event->id)->where('revoked', false)->whereNull('delivered_at')->count(),
            'byType' => Attestation::where('event_id', $event->id)->where('revoked', false)
                ->selectRaw('type, count(*) as c')->groupBy('type')->pluck('c', 'type'),
        ] : ['total' => 0, 'valid' => 0, 'revoked' => 0, 'delivered' => 0, 'pending' => 0, 'byType' => collect()];

        return [
            'issued' => $issued,
            'issuedStats' => $stats,
            'type' => $type,
            'state' => $state,
            'delivery' => $delivery,
            // Reportés tels quels sur les liens d'export : ils doivent porter sur
            // le même ensemble que la liste affichée.
            'exportParams' => AttestationQuery::params($request),
            'exportLot' => AttestationExportController::LOT,
            'search' => $search,
            'from' => $from,
            'to' => $to,
            'sort' => $sort['sort'],
            'dir' => $sort['dir'],
        ];
    }

    /**
     * Télécharge un modèle de base .docx (starter) pour le livre des résumés :
     * document Word minimal montrant la structure et les balises de fusion.
     */
    public function baseTemplate(): Response
    {
        // Vrai .docx à balises [[...]], aligné sur le moteur de fusion (LivretTemplate) :
        // l'admin le personnalise dans Word puis le re-téléverse comme modèle.
        return LivretTemplate::downloadBase('modele-livret-uk.docx');
    }

    /** @return array<string, mixed> */
    private function badgeReadyList(Request $request, ?Event $event): array
    {
        $badgeType = (string) $request->query('btype', 'default');
        if (! BadgeCatalog::valid($badgeType)) {
            $badgeType = 'default';
        }
        $people = $event ? BadgeCatalog::recipients($event, $badgeType) : collect();
        $badgeSearch = trim((string) $request->query('bsearch', ''));
        if ($badgeSearch !== '') {
            $people = $people->filter(fn (array $person) => mb_stripos($person['name'].' '.$person['email'], $badgeSearch) !== false)->values();
        }
        $page = LengthAwarePaginator::resolveCurrentPage();

        return [
            'badgeReady' => (new LengthAwarePaginator($people->forPage($page, 20)->values(), $people->count(), 20, $page))
                ->withPath($request->url())->appends($request->query()),
            'badgeType' => $badgeType,
            'badgeSearch' => $badgeSearch,
        ];
    }

    /**
     * Destinataires potentiels des attestations du module Formation, toutes
     * formations de l'événement confondues : inscrits éligibles (réglés, ou
     * inscrits si la formation est gratuite) et formateurs.
     *
     * @return array{formation: int, formation_animation: int}
     */
    private function formationTargetCounts(Event $event): array
    {
        $trainees = 0;
        $trainers = 0;

        foreach (Formation::where('event_id', $event->id)->get() as $formation) {
            $trainees += $formation->enrollments()
                ->where(fn ($q) => $q->where('status', 'paid')
                    ->when($formation->isFree(), fn ($q2) => $q2->orWhere('status', 'registered')))
                ->count();
            $trainers += $formation->trainers()->count();
        }

        return ['formation' => $trainees, 'formation_animation' => $trainers];
    }

    /**
     * Participants éligibles pour un type d'attestation donné.
     *
     * @return Collection<int, Participant>
     */
    private function targetsFor(Event $event, string $type): Collection
    {
        $base = fn () => Participant::where('event_id', $event->id);

        return match ($type) {
            'soumission' => $base()->whereHas('submissions', fn ($q) => $q->where('revision_of', 0))->get(),
            'acceptation' => $this->acceptedCorrespondingTargets($event),
            // 'communication' est géré à part (par communication) via CommunicationAttestations.
            // ⚠️ UNE par personne : le bénéficiaire a modéré (resp. rapporté) au
            // moins une séance, et l'émission est idempotente par (événement,
            // participant, type) - pas une attestation par séance.
            'moderation' => $this->sessionRoleTargets($event, 'moderator_email'),
            'rapporteur' => $this->sessionRoleTargets($event, 'rapporteur_email'),
            'organisation' => $this->organisationTargets($event),
            'comite_organisation' => $this->teamRoleTargets($event, ['comite_organisation']),
            'instruction_resumes' => $this->reviewerTargets($event),
            // 'formation' et 'formation_animation' sont gérées par formation
            // (émission depuis la page Formations, cf. Admin\FormationController).
            'participation' => $this->participationTargets($event),
            default => $base()->whereHas('participationPayments', fn ($q) => $q->where('status', 'paid'))->get(),
        };
    }

    /**
     * Bénéficiaires d'une attestation de modération / rapporteur : les personnes
     * (participants) qui ont modéré (resp. rapporté) AU MOINS une séance de
     * l'événement. Rapprochement par e-mail (comme les espaces de fonction),
     * dédoublonné : une personne qui modère trois séances n'a qu'une attestation.
     */
    private function sessionRoleTargets(Event $event, string $column): Collection
    {
        $emails = $event->sessions()
            ->whereNotNull($column)->where($column, '!=', '')
            ->pluck($column)
            ->map(fn ($e) => mb_strtolower(trim((string) $e)))
            ->filter()->unique()->values();

        if ($emails->isEmpty()) {
            return new Collection;
        }

        $placeholders = implode(',', array_fill(0, $emails->count(), '?'));

        return Participant::where('event_id', $event->id)
            ->whereRaw("LOWER(email) IN ($placeholders)", $emails->all())
            ->get();
    }

    /**
     * Bénéficiaires de l'attestation d'instruction de résumés : les participants qui
     * ont RENDU au moins une évaluation (relecture `completed`) sur une soumission de
     * l'événement. Dédoublonné : une attestation par relecteur, quel que soit le
     * nombre de résumés instruits. On s'en tient aux relectures rendues (le travail a
     * eu lieu), pas aux simples invitations ou aux refus.
     */
    private function reviewerTargets(Event $event): Collection
    {
        $ids = Review::where('status', 'completed')
            ->whereHas('submission', fn ($q) => $q->where('event_id', $event->id))
            ->pluck('reviewer_id')
            ->filter()
            ->unique()
            ->values();

        if ($ids->isEmpty()) {
            return new Collection;
        }

        return Participant::where('event_id', $event->id)
            ->whereIn('id', $ids->all())
            ->get();
    }

    /**
     * Bénéficiaires de l'attestation de participation à l'organisation : les membres
     * de l'ÉQUIPE de l'événement (pivot `event_user`) qui ont travaillé en appui, soit
     * TOUTE l'équipe SAUF les trois comités (organisation, scientifique, supervision).
     * Un compte membre d'un de ces comités est exclu, même s'il porte aussi un rôle
     * d'appui.
     */
    private function organisationTargets(Event $event): Collection
    {
        return $this->teamRoleTargets($event, exclude: ['scientific', 'comite_supervision', 'comite_organisation']);
    }

    /**
     * Bénéficiaires d'une attestation adossée à l'ÉQUIPE de l'événement (pivot
     * `event_user`), rapprochés vers les `Participant` par `user_id` (une attestation
     * vise un participant).
     *
     * - `$include` non vide : le compte doit porter AU MOINS un de ces rôles.
     * - `$exclude` non vide : le compte ne doit porter AUCUN de ces rôles (l'exclusion
     *   l'emporte sur l'inclusion - un membre d'un comité exclu est écarté même s'il a
     *   aussi un rôle d'appui).
     * - Sans `$include`, tout membre d'équipe convient (sous réserve de `$exclude`).
     *
     * @param  list<string>  $include
     * @param  list<string>  $exclude
     */
    private function teamRoleTargets(Event $event, array $include = [], array $exclude = []): Collection
    {
        $rolesByUser = $event->team()->get()
            ->groupBy('id')
            ->map(fn ($rows) => $rows->pluck('pivot.role')->filter()->values()->all());

        $userIds = $rolesByUser
            ->filter(function (array $roles) use ($include, $exclude) {
                if ($roles === []) {
                    return false;
                }
                if ($exclude !== [] && array_intersect($roles, $exclude) !== []) {
                    return false;
                }

                return $include === [] || array_intersect($roles, $include) !== [];
            })
            ->keys()->all();

        if ($userIds === []) {
            return new Collection;
        }

        return Participant::where('event_id', $event->id)
            ->whereIn('user_id', $userIds)->get();
    }

    /**
     * Participants éligibles à l'attestation de participation : réglés, et - si
     * l'option « présence requise » est active - effectivement présents (pointés
     * à l'accueil ou à au moins une séance).
     */
    private function participationTargets(Event $event): Collection
    {
        $regles = Participant::where('event_id', $event->id)
            ->whereHas('participationPayments', fn ($q) => $q->where('status', 'paid'));

        if (! (bool) EventSettings::get($event, 'attestations.require_presence', false)) {
            return $regles->get();
        }

        // ⚠️ Même règle que `Participant::wasPresent()`, mais en SQL : filtrer la
        // collection appelait un `exists()` par participant (un événement en compte
        // des centaines).
        return $regles->where(fn ($q) => $q->whereNotNull('checked_in_at')
            ->orWhereHas('sessionAttendances', fn ($a) => $a->where('status', 'present')))
            ->get();
    }

    /** Auteurs correspondants d'une communication acceptée (lettre d'acceptation). */
    private function acceptedCorrespondingTargets(Event $event): Collection
    {
        $accepted = Submission::where('event_id', $event->id)
            ->where('revision_of', 0)->where('status', 'accepted')->get();

        return Participant::where('event_id', $event->id)
            ->whereIn('id', $accepted->pluck('participant_id')->filter()->unique())->get();
    }

    private function currentEdition(Request $request): ?Event
    {
        $events = $this->accessibleEditions($request, 'documents');
        // `input` (et non `query`) pour résoudre aussi l'event_id posté en POST
        // par les formulaires d'options (réglages documents scopés par événement).
        $id = (string) $request->input('event_id', '');

        if ($id === '') {
            return $events->firstWhere('status', 'open') ?? $events->first();
        }

        return $events->firstWhere('id', (int) $id);
    }
}
