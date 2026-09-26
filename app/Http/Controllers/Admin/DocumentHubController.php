<?php

namespace App\Http\Controllers\Admin;

use App\Actions\IssueAttestation;
use App\AI\AiManager;
use App\Documents\AttestationCertifier;
use App\Http\Controllers\Controller;
use App\Models\Attestation;
use App\Models\DocumentTemplate;
use App\Models\Edition;
use App\Models\Formation;
use App\Models\Participant;
use App\Models\Submission;
use App\Support\CommunicationAttestations;
use App\Support\EditionSettings;
use App\Support\ListSort;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

/**
 * Hub Documents d'une édition (contrôleur + Blade, fidèle au mockup
 * admin-documents) : trois volets - Attestations (émission par type + options),
 * Badges (génération + options) et Livre des résumés (avant-propos, apparence).
 * Options persistées dans les réglages. Volets/onglets pilotés par Alpine.
 */
class DocumentHubController extends Controller
{
    use Concerns\ScopesEditionAccess;

    public function index(Request $request): View
    {
        $edition = $this->currentEdition($request);

        $attestations = $edition
            ? Attestation::where('edition_id', $edition->id)->latest('id')->get()
            : collect();

        // Ordre & libellés fidèles au mockup admin-documents (6 types).
        $typeMeta = [
            'soumission' => ['title' => 'Attestation de soumission', 'sub' => 'Par communication', 'scope' => 'Par communication'],
            'acceptation' => ['title' => 'Lettre d’acceptation', 'sub' => 'Par communication', 'scope' => 'Par communication'],
            'communication' => ['title' => 'Attestation de communication', 'sub' => 'Orale / poster', 'scope' => 'Par communication'],
            'participation' => ['title' => 'Attestation de participation', 'sub' => 'Une par participant', 'scope' => 'Participant'],
            'moderation' => ['title' => 'Attestation de modération', 'sub' => 'Sessions', 'scope' => 'Par session'],
            'rapporteur' => ['title' => 'Attestation de rapporteur', 'sub' => 'Sessions', 'scope' => 'Par session'],
            // Module Formation : les modèles se conçoivent ici, mais l'émission se
            // fait formation par formation (cf. Admin\FormationController).
            'formation' => ['title' => 'Attestation de formation', 'sub' => 'Inscrits aux formations', 'scope' => 'Par formation'],
            'formation_animation' => ['title' => 'Attestation d’animation', 'sub' => 'Formateurs', 'scope' => 'Par formation'],
        ];

        $rows = [];
        if ($edition) {
            $formationCounts = $this->formationTargetCounts($edition);
            // Modèles enregistrés (libellé personnalisé + suppression possible).
            $templates = DocumentTemplate::where('edition_id', $edition->id)
                ->where('kind', 'attestation')
                ->get()->keyBy('type');
            $customTitles = $templates->map(fn (DocumentTemplate $t) => $t->title);

            foreach ($typeMeta as $key => $meta) {
                $eligible = match ($key) {
                    'communication' => CommunicationAttestations::count($edition),
                    'formation', 'formation_animation' => $formationCounts[$key],
                    default => $this->targetsFor($edition, $key)->count(),
                };
                $rows[$key] = [
                    'label' => Attestation::TYPES[$key],
                    'title' => filled($customTitles[$key] ?? null) ? (string) $customTitles[$key] : $meta['title'],
                    'sub' => $meta['sub'],
                    'scope' => $meta['scope'],
                    'eligible' => $eligible,
                    'issued' => $attestations->where('type', $key)->where('revoked', false)->count(),
                    'status' => $eligible > 0 ? 'Actif' : 'Brouillon',
                    // Émission déportée : ces attestations s'émettent depuis la page
                    // de chaque formation, pas globalement pour l'édition.
                    'issueFrom' => in_array($key, ['formation', 'formation_animation'], true)
                        ? route('admin.formations', ['edition_id' => $edition->id])
                        : null,
                    // Modèle réellement enregistré (sinon c'est le défaut à la charte,
                    // qui n'a rien à supprimer).
                    'template' => $templates[$key] ?? null,
                ];
            }
        }

        $counts = $edition ? [
            'participants' => Participant::where('edition_id', $edition->id)->count(),
            'accepted' => Submission::where('edition_id', $edition->id)->where('revision_of', 0)->where('status', 'accepted')->count(),
            'issued' => $attestations->where('revoked', false)->count(),
            'sessions' => $edition->sessions()->count(),
            'axes' => count($edition->settings['tracks'] ?? []),
        ] : ['participants' => 0, 'accepted' => 0, 'issued' => 0, 'sessions' => 0, 'axes' => 0];

        // Onglet et sous-onglet portés par l'URL (?tab=&stab=) pour que chaque
        // onglet ait son lien. Le jeu de sous-onglets dépend de l'onglet.
        $tab = (string) $request->query('tab', 'attestations');
        $tab = in_array($tab, ['attestations', 'badges', 'livre'], true) ? $tab : 'attestations';

        $allowed = match ($tab) {
            'badges' => ['modeles', 'prets', 'options'],
            'attestations' => ['modeles', 'delivrees', 'options'],
            default => ['modeles'],
        };
        $stab = (string) $request->query('stab', 'modeles');
        $stab = in_array($stab, $allowed, true) ? $stab : 'modeles';

        return view('admin.documents.attestations', [
            'editions' => $this->accessibleEditions($request, 'documents'),
            'edition' => $edition,
            'edition_id' => $edition ? (string) $edition->id : '',
            'types' => Attestation::TYPES,
            'attestations' => $attestations,
            'rows' => $rows,
            'counts' => $counts,
            'tab' => $tab,
            'stab' => $stab,
            ...($tab === 'attestations' && $stab === 'delivrees' ? $this->issuedList($request, $edition) : []),
            ...($tab === 'badges' && $stab === 'prets' ? $this->badgeReadyList($request, $edition) : []),
            'opts' => [
                'attPrefix' => (string) EditionSettings::get($edition, 'attestations.prefix', 'UKC-ATT-2026-'),
                'attAuto' => (bool) EditionSettings::get($edition, 'attestations.auto', true),
                'attNotify' => (bool) EditionSettings::get($edition, 'attestations.notify', true),
                'attRequirePresence' => (bool) EditionSettings::get($edition, 'attestations.require_presence', false),
                'attIncludeQr' => (bool) EditionSettings::get($edition, 'attestations.include_qr', true),
                'attAllowBeforeValidation' => (bool) EditionSettings::get($edition, 'attestations.allow_before_validation', false),
                // Identité vérifiable (DIDs) - signature EdDSA / VC-JWT effective.
                'attDidIssuer' => (string) EditionSettings::get($edition, 'attestations.did_issuer', 'did:web:univkara.tg:colloques'),
                'attDidMethod' => (string) EditionSettings::get($edition, 'attestations.did_method', 'did:web:univkara.tg:colloques#key-ed25519-1'),
                'attCryptoSuite' => (string) EditionSettings::get($edition, 'attestations.crypto_suite', 'EdDSA (VC-JWT)'),
                'attCertEnabled' => app(AttestationCertifier::class)->enabled(),
                'badgePrefix' => (string) EditionSettings::get($edition, 'badges.prefix', 'UKC-2026-'),
                'badgeFormat' => (string) EditionSettings::get($edition, 'badges.format', 'A6 Portrait'),
                'badgePhotoRequired' => (bool) EditionSettings::get($edition, 'badges.photo_required', true),
                'badgeQrAccess' => (bool) EditionSettings::get($edition, 'badges.qr_access', true),
                'badgeGenerateAfterPayment' => (bool) EditionSettings::get($edition, 'badges.generate_after_payment', true),
                'badgeBatchPrint' => (bool) EditionSettings::get($edition, 'badges.batch_print', false),
                'forewordTitle' => (string) EditionSettings::get($edition, 'livret.foreword_title', 'Avant-propos'),
                'forewordText' => (string) EditionSettings::get($edition, 'livret.foreword_text', ''),
                'forewordSignature' => (string) EditionSettings::get($edition, 'livret.signature', ''),
                'livretShowTheme' => (bool) EditionSettings::get($edition, 'livret.show_theme', true),
                'livretShowDates' => (bool) EditionSettings::get($edition, 'livret.show_dates', true),
                'livretNumber' => (bool) EditionSettings::get($edition, 'livret.number_abstracts', false),
                // Apparence du livre (modèle par défaut) - mockup admin-documents.
                'livretFontBody' => (string) EditionSettings::get($edition, 'livret.font_body', 'Times New Roman'),
                'livretFontHead' => (string) EditionSettings::get($edition, 'livret.font_head', 'Jost'),
                'livretSizeBody' => (int) EditionSettings::get($edition, 'livret.size_body', 11),
                'livretSizeCover' => (int) EditionSettings::get($edition, 'livret.size_cover', 24),
                'livretSizeAxis' => (int) EditionSettings::get($edition, 'livret.size_axis', 16),
                'livretSizeAbstract' => (int) EditionSettings::get($edition, 'livret.size_abstract', 12),
                'livretSizeAuthors' => (int) EditionSettings::get($edition, 'livret.size_authors', 10),
                'livretLineHeight' => (string) EditionSettings::get($edition, 'livret.line_height', '1.15'),
                'livretColorPrimary' => (string) EditionSettings::get($edition, 'livret.color_primary', '#223ea4'),
                'livretColorSecondary' => (string) EditionSettings::get($edition, 'livret.color_secondary', '#009933'),
                'livretAlignTitles' => (string) EditionSettings::get($edition, 'livret.align_titles', 'Centré'),
                'livretAlignBody' => (string) EditionSettings::get($edition, 'livret.align_body', 'Justifié'),
                'livretCoverMention' => (string) EditionSettings::get($edition, 'livret.cover_mention', ''),
                'livretCoverLogo' => (string) EditionSettings::get($edition, 'livret.cover_logo', ''),
                'livretTemplate' => (string) EditionSettings::get($edition, 'livret.template', 'default'),
            ],
            'templates' => is_array(EditionSettings::get($edition, 'livret.templates')) ? EditionSettings::get($edition, 'livret.templates') : [],
            // Badges : 4 types fidèles au mockup, destinataires réels.
            'badgeRows' => $edition ? [
                ['type' => 'Participant', 'title' => 'Badge participant', 'sub' => 'Bandeau bleu UK', 'format' => 'A6 Portrait', 'count' => $counts['participants'], 'status' => 'Actif'],
                ['type' => 'Communicant', 'title' => 'Badge communicant', 'sub' => 'Bandeau vert', 'format' => 'A6 Portrait', 'count' => $counts['accepted'], 'status' => 'Actif'],
                ['type' => 'Staff', 'title' => 'Badge organisation', 'sub' => 'Staff / orga', 'format' => 'A6 Portrait', 'count' => Participant::where('edition_id', $edition->id)->whereIn('role', ['committee', 'staff', 'moderator', 'rapporteur'])->count(), 'status' => 'Actif'],
                ['type' => 'Carte', 'title' => 'Badge carte CR80', 'sub' => '85,6 × 54 mm', 'format' => 'CR80', 'count' => 0, 'status' => 'Brouillon'],
            ] : [],
            'livreState' => [
                'lastGen' => EditionSettings::get($edition, 'livret.last_generated_at'),
                'file' => (string) EditionSettings::get($edition, 'livret.last_file', ''),
            ],
            'aiAvailable' => app(AiManager::class)->available() !== [],
        ]);
    }

    /** Émet les attestations d'un type pour son audience éligible (idempotent). */
    public function issueType(Request $request, IssueAttestation $action): RedirectResponse
    {
        $edition = Edition::find($request->input('edition_id'));
        $type = (string) $request->input('type');

        if (! $edition || ! isset(Attestation::TYPES[$type])) {
            return back();
        }

        $count = 0;
        if ($type === 'communication') {
            // Par communication : auteur correspondant + co-auteurs payés, dans la
            // limite des plafonds (coauthors_max, attestation_max - priorité auteur
            // correspondant). Une attestation à leur nom par communication associée.
            foreach (CommunicationAttestations::entitlements($edition) as $ent) {
                $action->handle($ent['participant'], 'communication', $ent['submission']);
                $count++;
            }
        } else {
            foreach ($this->targetsFor($edition, $type) as $participant) {
                $action->handle($participant, $type);
                $count++;
            }
        }

        return back()->with('ukc_status', $count.' attestation(s) « '.Attestation::TYPES[$type].' » émise(s).');
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
        $edition = $this->currentEdition($request);
        if (! $edition) {
            return back()->with('ukc_error', 'Sélectionnez une édition.');
        }

        EditionSettings::setMany($edition, [
            'attestations.prefix' => (string) $request->input('attPrefix'),
            'attestations.auto' => $request->boolean('attAuto'),
            'attestations.notify' => $request->boolean('attNotify'),
            'attestations.require_presence' => $request->boolean('attRequirePresence'),
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
        $edition = $this->currentEdition($request);
        if (! $edition) {
            return back()->with('ukc_error', 'Sélectionnez une édition.');
        }

        EditionSettings::setMany($edition, [
            'badges.prefix' => (string) $request->input('badgePrefix'),
            'badges.format' => (string) $request->input('badgeFormat'),
            'badges.photo_required' => $request->boolean('badgePhotoRequired'),
            'badges.qr_access' => $request->boolean('badgeQrAccess'),
            'badges.generate_after_payment' => $request->boolean('badgeGenerateAfterPayment'),
            'badges.batch_print' => $request->boolean('badgeBatchPrint'),
        ]);

        return back()->with('ukc_status', 'Options des badges enregistrées.');
    }

    public function saveLivretOptions(Request $request): RedirectResponse
    {
        $edition = $this->currentEdition($request);
        if (! $edition) {
            return back()->with('ukc_error', 'Sélectionnez une édition.');
        }

        EditionSettings::setMany($edition, [
            'livret.foreword_title' => trim((string) $request->input('forewordTitle')),
            'livret.foreword_text' => trim((string) $request->input('forewordText')),
            'livret.signature' => trim((string) $request->input('forewordSignature')),
            'livret.show_theme' => $request->boolean('livretShowTheme'),
            'livret.show_dates' => $request->boolean('livretShowDates'),
            'livret.number_abstracts' => $request->boolean('livretNumber'),
        ]);

        return back()->with('ukc_status', 'Avant-propos enregistré.');
    }

    /**
     * Génère l'avant-propos du livret par IA à partir des métadonnées de l'édition
     * (logique du projet uk-colloques), et le persiste dans les réglages `livret.*`.
     */
    public function generateForewordAi(Request $request, AiManager $ai): RedirectResponse
    {
        $edition = $this->currentEdition($request);

        if (! $edition) {
            return back()->with('ukc_error', 'Sélectionnez une édition.');
        }
        if (! $ai->resolve()) {
            return back()->with('ukc_error', 'Aucun fournisseur IA configuré (Réglages → Intelligence artificielle).');
        }

        $nbAccepted = $edition->submissions()->where('revision_of', 0)->where('status', 'accepted')->count();
        $tracks = is_array($edition->settings['tracks'] ?? null) ? $edition->settings['tracks'] : [];

        $facts = array_filter([
            'Colloque : '.($edition->colloque?->name ?: $edition->title),
            $edition->title ? 'Édition : '.$edition->title : null,
            $edition->edition_label ? 'Libellé : '.$edition->edition_label : null,
            $edition->theme ? 'Thème : '.$edition->theme : null,
            $edition->date_start ? 'Dates : '.$edition->date_start->format('d/m/Y').($edition->date_end ? ' – '.$edition->date_end->format('d/m/Y') : '') : null,
            $edition->location ? 'Lieu : '.$edition->location : null,
            $tracks ? 'Axes thématiques : '.implode(' ; ', $tracks) : null,
            'Nombre de résumés retenus : '.$nbAccepted,
            trim((string) EditionSettings::get($edition, 'livret.signature', '')) ? 'Signataire : '.EditionSettings::get($edition, 'livret.signature') : null,
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

        EditionSettings::setMany($edition, [
            'livret.foreword_text' => $text,
            'livret.foreword_title' => trim((string) EditionSettings::get($edition, 'livret.foreword_title', 'Avant-propos')) ?: 'Avant-propos',
        ]);

        return back()->with('ukc_status', 'Avant-propos généré par IA - vérifiez puis enregistrez.');
    }

    /** Apparence du livre (typographie, couleurs, page de garde) → modèle par défaut. */
    public function saveLivretApparence(Request $request): RedirectResponse
    {
        $edition = $this->currentEdition($request);
        if (! $edition) {
            return back()->with('ukc_error', 'Sélectionnez une édition.');
        }

        EditionSettings::setMany($edition, [
            'livret.font_body' => (string) $request->input('livretFontBody', 'Times New Roman'),
            'livret.font_head' => (string) $request->input('livretFontHead', 'Jost'),
            'livret.size_body' => (int) $request->input('livretSizeBody', 11),
            'livret.size_cover' => (int) $request->input('livretSizeCover', 24),
            'livret.size_axis' => (int) $request->input('livretSizeAxis', 16),
            'livret.size_abstract' => (int) $request->input('livretSizeAbstract', 12),
            'livret.size_authors' => (int) $request->input('livretSizeAuthors', 10),
            'livret.line_height' => (string) $request->input('livretLineHeight', '1.15'),
            'livret.color_primary' => (string) $request->input('livretColorPrimary', '#223ea4'),
            'livret.color_secondary' => (string) $request->input('livretColorSecondary', '#009933'),
            'livret.align_titles' => (string) $request->input('livretAlignTitles', 'Centré'),
            'livret.align_body' => (string) $request->input('livretAlignBody', 'Justifié'),
            'livret.cover_mention' => trim((string) $request->input('livretCoverMention')),
            'livret.show_theme' => $request->boolean('livretShowTheme'),
            'livret.show_dates' => $request->boolean('livretShowDates'),
            'livret.number_abstracts' => $request->boolean('livretNumber'),
        ]);

        // Logo(s) de la page de garde (optionnel).
        if ($request->hasFile('livretCoverLogo')) {
            $request->validate(['livretCoverLogo' => ['image', 'mimes:png,jpg,jpeg,webp,svg', 'max:4096']]);
            EditionSettings::set($edition, 'livret.cover_logo', $request->file('livretCoverLogo')->store('livret', 'public'));
        }

        return back()->with('ukc_status', 'Apparence du livre enregistrée.');
    }

    /** Modèle actif + import optionnel d'un modèle .docx personnalisé. */
    public function saveLivretTemplate(Request $request): RedirectResponse
    {
        $edition = $this->currentEdition($request);
        if (! $edition) {
            return back()->with('ukc_error', 'Sélectionnez une édition.');
        }

        $request->validate([
            'livretTemplate' => ['nullable', 'string', 'max:100'],
            'templateFile' => ['nullable', 'file', 'mimes:doc,docx', 'max:5120'],
        ]);

        $templates = is_array(EditionSettings::get($edition, 'livret.templates')) ? EditionSettings::get($edition, 'livret.templates') : [];

        if ($request->hasFile('templateFile')) {
            $file = $request->file('templateFile');
            $path = $file->store('livret-templates', 'public');
            $templates[] = [
                'name' => $file->getClientOriginalName(),
                'path' => $path,
                'size' => $file->getSize(),
                'date' => now()->format('d/m/Y'),
            ];
            EditionSettings::set($edition, 'livret.templates', array_values($templates));
        }

        if ($request->filled('livretTemplate')) {
            EditionSettings::set($edition, 'livret.template', (string) $request->input('livretTemplate'));
        }

        return back()->with('ukc_status', 'Modèle du livre enregistré.');
    }

    /**
     * Page dédiée « Attestations délivrées » : liste des attestations émises pour
     * l'édition (bénéficiaire, type, date, état) avec révocation / rétablissement.
     * L'émission et les templates restent gérés dans le hub Documents.
     */
    /**
     * Données du sous-onglet « Attestations délivrées » (liste filtrable, triable
     * et paginée). Servi par la page Documents pour que l'onglet ait une URL.
     *
     * @return array<string, mixed>
     */
    private function issuedList(Request $request, ?Edition $edition): array
    {
        $query = $edition ? Attestation::where('edition_id', $edition->id) : Attestation::query()->whereRaw('1 = 0');

        if (($type = (string) $request->query('type', '')) !== '' && array_key_exists($type, Attestation::TYPES)) {
            $query->where('type', $type);
        }
        if (($state = (string) $request->query('state', '')) === 'valid') {
            $query->where('revoked', false);
        } elseif ($state === 'revoked') {
            $query->where('revoked', true);
        }

        // Recherche nom / e-mail : sur l'attestation (figés à l'émission) ET sur le
        // participant lié, car une attestation peut viser quelqu'un sans compte.
        $search = trim((string) $request->query('search', ''));
        if ($search !== '') {
            $term = '%'.$search.'%';
            $query->where(fn ($q) => $q
                ->where('recipient_name', 'like', $term)
                ->orWhere('recipient_email', 'like', $term)
                ->orWhereHas('participant', fn ($p) => $p
                    ->where('first_name', 'like', $term)
                    ->orWhere('last_name', 'like', $term)
                    ->orWhere('email', 'like', $term)));
        }

        // Période d'émission.
        $from = (string) $request->query('from', '');
        $to = (string) $request->query('to', '');
        if ($from !== '') {
            $query->whereDate('issued_at', '>=', $from);
        }
        if ($to !== '') {
            $query->whereDate('issued_at', '<=', $to);
        }

        $sort = ListSort::resolve(
            $request,
            ['recipient_name', 'type', 'uuid', 'issued_at', 'revoked'],
            'issued_at',
            'desc'
        );

        $issued = $query->with('participant')
            ->orderBy($sort['sort'], $sort['dir'])
            ->orderBy('id', 'desc')
            ->paginate(20)->withQueryString();

        $stats = $edition ? [
            'total' => Attestation::where('edition_id', $edition->id)->count(),
            'valid' => Attestation::where('edition_id', $edition->id)->where('revoked', false)->count(),
            'revoked' => Attestation::where('edition_id', $edition->id)->where('revoked', true)->count(),
            'byType' => Attestation::where('edition_id', $edition->id)->where('revoked', false)
                ->selectRaw('type, count(*) as c')->groupBy('type')->pluck('c', 'type'),
        ] : ['total' => 0, 'valid' => 0, 'revoked' => 0, 'byType' => collect()];

        return [
            'issued' => $issued,
            'issuedStats' => $stats,
            'type' => $type,
            'state' => $state,
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
        $body = <<<'HTML'
        <html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:w="urn:schemas-microsoft-com:office:word">
        <head><meta charset="utf-8"><title>Modèle de base - Livre des résumés</title>
        <style>body{font-family:'Times New Roman',serif;font-size:11pt;line-height:1.4;}
        h1{font-family:Jost,Arial,sans-serif;font-size:24pt;color:#223ea4;text-align:center;}
        h2{font-family:Jost,Arial,sans-serif;font-size:16pt;color:#223ea4;}
        h3{font-size:12pt;} .tag{color:#3730a3;font-family:Consolas,monospace;} .muted{color:#5a6577;}</style></head>
        <body>
        <h1>{{edition_name}}</h1>
        <p style="text-align:center;" class="muted">Livre des résumés · Université de Kara</p>
        <hr>
        <h2>{{axis}}</h2>
        <h3>{{abstract_title}}</h3>
        <p><em>{{authors}}</em></p>
        <p>{{abstract_body}}</p>
        <hr>
        <p class="muted">Balises de fusion disponibles :
        <span class="tag">{{edition_name}}</span>, <span class="tag">{{axis}}</span>,
        <span class="tag">{{abstract_title}}</span>, <span class="tag">{{authors}}</span>,
        <span class="tag">{{abstract_body}}</span>, <span class="tag">{{session}}</span>.</p>
        <p class="muted">Dupliquez le bloc « Axe → Résumé » autant que nécessaire, puis importez ce fichier
        comme modèle personnalisé.</p>
        </body></html>
        HTML;

        return response($body, 200, [
            'Content-Type' => 'application/msword; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="ukc-livre-base.doc"',
        ]);
    }

    /**
     * Sous-onglet « Badges prêts » : les participants de l'édition dont le badge
     * peut être imprimé. Un badge n'est pas stocké (il est rendu à la demande à
     * partir du modèle) : « prêt » signifie donc « participant éligible ».
     *
     * @return array<string, mixed>
     */
    private function badgeReadyList(Request $request, ?Edition $edition): array
    {
        $query = $edition
            ? Participant::where('edition_id', $edition->id)
            : Participant::query()->whereRaw('1 = 0');

        // Par défaut on ne montre que les inscriptions réglées : ce sont elles dont
        // le badge est réellement à imprimer.
        $paidOnly = $request->query('paid', '1') !== '0';
        if ($paidOnly) {
            $query->whereHas('payments', fn ($q) => $q->where('status', 'paid'));
        }

        $badgeSearch = trim((string) $request->query('bsearch', ''));
        if ($badgeSearch !== '') {
            $term = '%'.$badgeSearch.'%';
            $query->where(fn ($q) => $q->where('first_name', 'like', $term)
                ->orWhere('last_name', 'like', $term)
                ->orWhere('email', 'like', $term));
        }

        return [
            'badgeReady' => $query->orderBy('last_name')->orderBy('first_name')
                ->paginate(20)->withQueryString(),
            'badgePaidOnly' => $paidOnly,
            'badgeSearch' => $badgeSearch,
            'badgeTemplate' => $edition
                ? DocumentTemplate::where('edition_id', $edition->id)->where('kind', 'badge')->first()
                : null,
        ];
    }

    /**
     * Destinataires potentiels des attestations du module Formation, toutes
     * formations de l'édition confondues : inscrits éligibles (réglés, ou
     * inscrits si la formation est gratuite) et formateurs.
     *
     * @return array{formation: int, formation_animation: int}
     */
    private function formationTargetCounts(Edition $edition): array
    {
        $trainees = 0;
        $trainers = 0;

        foreach (Formation::where('edition_id', $edition->id)->get() as $formation) {
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
    private function targetsFor(Edition $edition, string $type): Collection
    {
        $base = fn () => Participant::where('edition_id', $edition->id);

        return match ($type) {
            'soumission' => $base()->whereHas('submissions', fn ($q) => $q->where('revision_of', 0))->get(),
            'acceptation' => $this->acceptedCorrespondingTargets($edition),
            // 'communication' est géré à part (par communication) via CommunicationAttestations.
            'moderation' => $base()->where('role', 'moderator')->get(),
            'rapporteur' => $base()->where('role', 'rapporteur')->get(),
            // 'formation' et 'formation_animation' sont gérées par formation
            // (émission depuis la page Formations, cf. Admin\FormationController).
            'participation' => $this->participationTargets($edition),
            default => $base()->whereHas('payments', fn ($q) => $q->where('status', 'paid'))->get(),
        };
    }

    /**
     * Participants éligibles à l'attestation de participation : réglés, et - si
     * l'option « présence requise » est active - effectivement présents (pointés
     * à l'accueil ou à au moins une séance).
     */
    private function participationTargets(Edition $edition): Collection
    {
        $paid = Participant::where('edition_id', $edition->id)
            ->whereHas('payments', fn ($q) => $q->where('status', 'paid'))
            ->get();

        if (! (bool) EditionSettings::get($edition, 'attestations.require_presence', false)) {
            return $paid;
        }

        return $paid->filter(fn (Participant $p) => $p->wasPresent())->values();
    }

    /** Auteurs correspondants d'une communication acceptée (lettre d'acceptation). */
    private function acceptedCorrespondingTargets(Edition $edition): Collection
    {
        $accepted = Submission::where('edition_id', $edition->id)
            ->where('revision_of', 0)->where('status', 'accepted')->get();

        return Participant::where('edition_id', $edition->id)
            ->whereIn('id', $accepted->pluck('participant_id')->filter()->unique())->get();
    }

    private function currentEdition(Request $request): ?Edition
    {
        $editions = $this->accessibleEditions($request, 'documents');
        // `input` (et non `query`) pour résoudre aussi l'edition_id posté en POST
        // par les formulaires d'options (réglages documents scopés par édition).
        $id = (string) $request->input('edition_id', '');

        if ($id === '') {
            return $editions->firstWhere('status', 'open') ?? $editions->first();
        }

        return $editions->firstWhere('id', (int) $id);
    }
}
