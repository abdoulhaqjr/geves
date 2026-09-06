<?php

namespace App\Http\Controllers\Admin;

use App\Actions\IssueAttestation;
use App\AI\AiManager;
use App\Http\Controllers\Controller;
use App\Models\Attestation;
use App\Models\Edition;
use App\Models\Participant;
use App\Models\Submission;
use App\Support\Settings;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

/**
 * Hub Documents d'une édition (contrôleur + Blade, fidèle au mockup
 * admin-documents) : trois volets — Attestations (émission par type + options),
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
            'formation' => ['title' => 'Attestation de formation', 'sub' => 'Ateliers en marge', 'scope' => 'Par formation'],
        ];

        $rows = [];
        if ($edition) {
            foreach ($typeMeta as $key => $meta) {
                $eligible = $this->targetsFor($edition, $key)->count();
                $rows[$key] = [
                    'label' => Attestation::TYPES[$key],
                    'title' => $meta['title'],
                    'sub' => $meta['sub'],
                    'scope' => $meta['scope'],
                    'eligible' => $eligible,
                    'issued' => $attestations->where('type', $key)->where('revoked', false)->count(),
                    'status' => $eligible > 0 ? 'Actif' : 'Brouillon',
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

        return view('admin.documents.attestations', [
            'editions' => $this->accessibleEditions($request, 'documents'),
            'edition' => $edition,
            'edition_id' => $edition ? (string) $edition->id : '',
            'types' => Attestation::TYPES,
            'attestations' => $attestations,
            'rows' => $rows,
            'counts' => $counts,
            'opts' => [
                'attPrefix' => (string) Settings::get('attestations.prefix', 'UKC-ATT-2026-'),
                'attAuto' => (bool) Settings::get('attestations.auto', true),
                'attNotify' => (bool) Settings::get('attestations.notify', true),
                'attIncludeQr' => (bool) Settings::get('attestations.include_qr', true),
                'attAllowBeforeValidation' => (bool) Settings::get('attestations.allow_before_validation', false),
                // Identité vérifiable (DIDs) — mockup admin-documents.
                'attDidIssuer' => (string) Settings::get('attestations.did_issuer', 'did:web:univkara.tg:colloques'),
                'attDidMethod' => (string) Settings::get('attestations.did_method', 'did:web:univkara.tg:colloques#key-ed25519-1'),
                'attCryptoSuite' => (string) Settings::get('attestations.crypto_suite', 'Ed25519Signature2020'),
                'badgePrefix' => (string) Settings::get('badges.prefix', 'UKC-2026-'),
                'badgeFormat' => (string) Settings::get('badges.format', 'A6 Portrait'),
                'badgePhotoRequired' => (bool) Settings::get('badges.photo_required', true),
                'badgeQrAccess' => (bool) Settings::get('badges.qr_access', true),
                'badgeGenerateAfterPayment' => (bool) Settings::get('badges.generate_after_payment', true),
                'badgeBatchPrint' => (bool) Settings::get('badges.batch_print', false),
                'forewordTitle' => (string) Settings::get('livret.foreword_title', 'Avant-propos'),
                'forewordText' => (string) Settings::get('livret.foreword_text', ''),
                'forewordSignature' => (string) Settings::get('livret.signature', ''),
                'livretShowTheme' => (bool) Settings::get('livret.show_theme', true),
                'livretShowDates' => (bool) Settings::get('livret.show_dates', true),
                'livretNumber' => (bool) Settings::get('livret.number_abstracts', false),
                // Apparence du livre (modèle par défaut) — mockup admin-documents.
                'livretFontBody' => (string) Settings::get('livret.font_body', 'Times New Roman'),
                'livretFontHead' => (string) Settings::get('livret.font_head', 'Jost'),
                'livretSizeBody' => (int) Settings::get('livret.size_body', 11),
                'livretSizeCover' => (int) Settings::get('livret.size_cover', 24),
                'livretSizeAxis' => (int) Settings::get('livret.size_axis', 16),
                'livretSizeAbstract' => (int) Settings::get('livret.size_abstract', 12),
                'livretSizeAuthors' => (int) Settings::get('livret.size_authors', 10),
                'livretLineHeight' => (string) Settings::get('livret.line_height', '1.15'),
                'livretColorPrimary' => (string) Settings::get('livret.color_primary', '#223ea4'),
                'livretColorSecondary' => (string) Settings::get('livret.color_secondary', '#009933'),
                'livretAlignTitles' => (string) Settings::get('livret.align_titles', 'Centré'),
                'livretAlignBody' => (string) Settings::get('livret.align_body', 'Justifié'),
                'livretCoverMention' => (string) Settings::get('livret.cover_mention', ''),
                'livretCoverLogo' => (string) Settings::get('livret.cover_logo', ''),
                'livretTemplate' => (string) Settings::get('livret.template', 'default'),
            ],
            'templates' => is_array(Settings::get('livret.templates')) ? Settings::get('livret.templates') : [],
            // Badges : 4 types fidèles au mockup, destinataires réels.
            'badgeRows' => $edition ? [
                ['type' => 'Participant', 'title' => 'Badge participant', 'sub' => 'Bandeau bleu UK', 'format' => 'A6 Portrait', 'count' => $counts['participants'], 'status' => 'Actif'],
                ['type' => 'Communicant', 'title' => 'Badge communicant', 'sub' => 'Bandeau vert', 'format' => 'A6 Portrait', 'count' => $counts['accepted'], 'status' => 'Actif'],
                ['type' => 'Staff', 'title' => 'Badge organisation', 'sub' => 'Staff / orga', 'format' => 'A6 Portrait', 'count' => Participant::where('edition_id', $edition->id)->whereIn('role', ['committee', 'staff', 'moderator', 'rapporteur'])->count(), 'status' => 'Actif'],
                ['type' => 'Carte', 'title' => 'Badge carte CR80', 'sub' => '85,6 × 54 mm', 'format' => 'CR80', 'count' => 0, 'status' => 'Brouillon'],
            ] : [],
            'livreState' => [
                'lastGen' => Settings::get('livret.last_generated_at'),
                'file' => (string) Settings::get('livret.last_file', ''),
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
        foreach ($this->targetsFor($edition, $type) as $participant) {
            $action->handle($participant, $type);
            $count++;
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
        Settings::setMany([
            'attestations.prefix' => (string) $request->input('attPrefix'),
            'attestations.auto' => $request->boolean('attAuto'),
            'attestations.notify' => $request->boolean('attNotify'),
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
        Settings::setMany([
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
        Settings::setMany([
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
        $provider = $ai->resolve();

        if (! $edition) {
            return back()->with('ukc_error', 'Sélectionnez une édition.');
        }
        if (! $provider) {
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
            trim((string) Settings::get('livret.signature', '')) ? 'Signataire : '.Settings::get('livret.signature') : null,
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
            $res = $provider->extract($instruction);
        } catch (\Throwable $e) {
            report($e);

            return back()->with('ukc_error', 'Échec de la génération IA : '.$e->getMessage());
        }

        $text = trim((string) ($res['foreword'] ?? ''));
        if ($text === '') {
            return back()->with('ukc_error', 'L’IA n’a pas renvoyé d’avant-propos exploitable.');
        }

        Settings::setMany([
            'livret.foreword_text' => $text,
            'livret.foreword_title' => trim((string) Settings::get('livret.foreword_title', 'Avant-propos')) ?: 'Avant-propos',
        ]);

        return back()->with('ukc_status', 'Avant-propos généré par IA — vérifiez puis enregistrez.');
    }

    /** Apparence du livre (typographie, couleurs, page de garde) → modèle par défaut. */
    public function saveLivretApparence(Request $request): RedirectResponse
    {
        Settings::setMany([
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
            Settings::set('livret.cover_logo', $request->file('livretCoverLogo')->store('livret', 'public'));
        }

        return back()->with('ukc_status', 'Apparence du livre enregistrée.');
    }

    /** Modèle actif + import optionnel d'un modèle .docx personnalisé. */
    public function saveLivretTemplate(Request $request): RedirectResponse
    {
        $request->validate([
            'livretTemplate' => ['nullable', 'string', 'max:100'],
            'templateFile' => ['nullable', 'file', 'mimes:doc,docx', 'max:5120'],
        ]);

        $templates = is_array(Settings::get('livret.templates')) ? Settings::get('livret.templates') : [];

        if ($request->hasFile('templateFile')) {
            $file = $request->file('templateFile');
            $path = $file->store('livret-templates', 'public');
            $templates[] = [
                'name' => $file->getClientOriginalName(),
                'path' => $path,
                'size' => $file->getSize(),
                'date' => now()->format('d/m/Y'),
            ];
            Settings::set('livret.templates', array_values($templates));
        }

        if ($request->filled('livretTemplate')) {
            Settings::set('livret.template', (string) $request->input('livretTemplate'));
        }

        return back()->with('ukc_status', 'Modèle du livre enregistré.');
    }

    /**
     * Page dédiée « Attestations délivrées » : liste des attestations émises pour
     * l'édition (bénéficiaire, type, date, état) avec révocation / rétablissement.
     * L'émission et les templates restent gérés dans le hub Documents.
     */
    public function issued(Request $request): View
    {
        $edition = $this->currentEdition($request);

        $query = $edition ? Attestation::where('edition_id', $edition->id) : Attestation::query()->whereRaw('1 = 0');

        if (($type = (string) $request->query('type', '')) !== '' && array_key_exists($type, Attestation::TYPES)) {
            $query->where('type', $type);
        }
        if (($state = (string) $request->query('state', '')) === 'valid') {
            $query->where('revoked', false);
        } elseif ($state === 'revoked') {
            $query->where('revoked', true);
        }

        $attestations = $query->with('participant')->latest('id')->paginate(20)->withQueryString();

        $stats = $edition ? [
            'total' => Attestation::where('edition_id', $edition->id)->count(),
            'valid' => Attestation::where('edition_id', $edition->id)->where('revoked', false)->count(),
            'revoked' => Attestation::where('edition_id', $edition->id)->where('revoked', true)->count(),
            'byType' => Attestation::where('edition_id', $edition->id)->where('revoked', false)
                ->selectRaw('type, count(*) as c')->groupBy('type')->pluck('c', 'type'),
        ] : ['total' => 0, 'valid' => 0, 'revoked' => 0, 'byType' => collect()];

        return view('admin.attestations.index', [
            'editions' => $this->accessibleEditions($request, 'documents'),
            'edition' => $edition,
            'edition_id' => $edition ? (string) $edition->id : '',
            'types' => Attestation::TYPES,
            'type' => $type,
            'state' => $state,
            'attestations' => $attestations,
            'stats' => $stats,
        ]);
    }

    /**
     * Télécharge un modèle de base .docx (starter) pour le livre des résumés :
     * document Word minimal montrant la structure et les balises de fusion.
     */
    public function baseTemplate(): Response
    {
        $body = <<<'HTML'
        <html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:w="urn:schemas-microsoft-com:office:word">
        <head><meta charset="utf-8"><title>Modèle de base — Livre des résumés</title>
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

    /** Participants éligibles pour un type d'attestation donné. */
    /** @return Collection<int, Participant> */
    private function targetsFor(Edition $edition, string $type): Collection
    {
        $base = fn () => Participant::where('edition_id', $edition->id);

        return match ($type) {
            'soumission' => $base()->whereHas('submissions', fn ($q) => $q->where('revision_of', 0))->get(),
            'acceptation' => $this->acceptedCorrespondingTargets($edition),
            'communication' => $this->communicantTargets($edition),
            'moderation' => $base()->where('role', 'moderator')->get(),
            'rapporteur' => $base()->where('role', 'rapporteur')->get(),
            'formation' => $base()->where('role', 'trainee')->get(),
            default => $base()->whereHas('payments', fn ($q) => $q->where('status', 'paid'))->get(),
        };
    }

    /** Auteurs correspondants d'une communication acceptée (lettre d'acceptation). */
    private function acceptedCorrespondingTargets(Edition $edition): Collection
    {
        $accepted = Submission::where('edition_id', $edition->id)
            ->where('revision_of', 0)->where('status', 'accepted')->get();

        return Participant::where('edition_id', $edition->id)
            ->whereIn('id', $accepted->pluck('participant_id')->filter()->unique())->get();
    }

    /** Communicants : auteurs correspondants acceptés + co-auteurs payés. */
    private function communicantTargets(Edition $edition): Collection
    {
        $accepted = Submission::where('edition_id', $edition->id)
            ->where('revision_of', 0)->where('status', 'accepted')->get();

        $corresponding = Participant::where('edition_id', $edition->id)
            ->whereIn('id', $accepted->pluck('participant_id')->filter()->unique())->get();

        $coauthorEmails = $accepted
            ->flatMap(fn (Submission $s) => collect($s->authors ?? [])
                ->reject(fn ($a) => $a['corresponding'] ?? false)->pluck('email'))
            ->filter()->map(fn ($e) => mb_strtolower(trim((string) $e)))->unique();

        $paidCoauthors = Participant::where('edition_id', $edition->id)
            ->whereHas('payments', fn ($q) => $q->where('status', 'paid'))->get()
            ->filter(fn (Participant $p) => $coauthorEmails->contains(mb_strtolower(trim((string) $p->email))));

        return $corresponding->concat($paidCoauthors)->unique('id')->values();
    }

    private function currentEdition(Request $request): ?Edition
    {
        $editions = $this->accessibleEditions($request, 'documents');
        $id = (string) $request->query('edition_id', '');

        if ($id === '') {
            return $editions->firstWhere('status', 'open') ?? $editions->first();
        }

        return $editions->firstWhere('id', (int) $id);
    }
}
