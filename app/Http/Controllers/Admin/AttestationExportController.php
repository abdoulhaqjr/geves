<?php

namespace App\Http\Controllers\Admin;

use App\Documents\AttestationVars;
use App\Documents\BrandedPdf;
use App\Documents\LayoutRenderer;
use App\Documents\LayoutSanitizer;
use App\Http\Controllers\Controller;
use App\Models\Attestation;
use App\Models\DocumentTemplate;
use App\Models\Event;
use App\Support\AttestationQuery;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * **Sortie papier des attestations** : le lot à imprimer, la liste d'émargement,
 * et la trace de ce qui a été remis.
 *
 * ⚠️ Sans cela, la délivrance physique revenait à ouvrir les attestations **une
 * par une** à leur adresse de vérification. Pour deux cents communications,
 * c'est deux cents onglets - le mode papier existait sans le geste qui le rend
 * tenable.
 *
 * ⚠️ Les exports portent sur **les filtres de l'écran** (voir
 * {@see AttestationQuery}) : on imprime ce qu'on regarde. Un export qui
 * refiltrerait de son côté produirait un lot différent de la liste, sans rien
 * pour s'en apercevoir.
 */
class AttestationExportController extends Controller
{
    /**
     * Attestations par fichier.
     *
     * ⚠️ Un plafond, et il est volontaire : chaque attestation est une page
     * composée élément par élément, et un millier d'un coup épuiserait la mémoire
     * du générateur **au milieu** du rendu - on obtiendrait une page blanche après
     * dix minutes d'attente. Le lot borné échoue vite ou réussit vite.
     */
    public const LOT = 50;

    /** Lot d'attestations prêt à imprimer, une par page. */
    public function pdf(Request $request): Response|RedirectResponse
    {
        $event = Event::find($request->query('event_id'));

        if (! $event) {
            return back()->with('ukc_error', 'Sélectionnez un événement.');
        }

        // ⚠️ Un seul type par fichier : la taille de page vient de la mise en page
        // du modèle, et un lot mêlant une A4 paysage et une A5 portrait sortirait
        // avec des documents rognés. Le dire plutôt que de produire ce lot-là.
        $type = (string) $request->query('type', '');

        if (! array_key_exists($type, Attestation::TYPES)) {
            return back()->with('ukc_error', 'Choisissez un type d’attestation : un lot PDF n’a qu’une seule taille de page.');
        }

        $lot = max(1, (int) $request->query('lot', 1));

        $attestations = AttestationQuery::filtered($request, $event)
            ->with(['event.series', 'participant'])
            ->orderBy('recipient_name')
            ->orderBy('id')
            ->forPage($lot, self::LOT)
            ->get();

        if ($attestations->isEmpty()) {
            return back()->with('ukc_error', 'Aucune attestation à exporter pour ce filtre.');
        }

        $template = DocumentTemplate::ensureFor((int) $event->id, 'attestation', $type);
        $layout = LayoutSanitizer::sanitize((array) $template->layout, 'attestation', $type);

        $pages = $attestations->map(fn (Attestation $a) => LayoutRenderer::render(
            $layout,
            // Le QR mène à la page de l'attestation, qui porte le document ET sa
            // vérification - comme pour le PDF unitaire.
            AttestationVars::for($a, route('attestation.document', $a->uuid)),
            pdf: true,
        ))->all();

        $w = (int) $layout['width'];
        $h = (int) $layout['height'];

        return BrandedPdf::stamp(
            Pdf::loadView('pdf.attestations-lot', ['pages' => $pages])->setPaper([0, 0, $w * 0.75, $h * 0.75]),
            'Lot d’attestations · '.$event->title,
        )
            ->download('attestations-'.$type.'-lot'.$lot.'.pdf');
    }

    /**
     * Liste d'émargement : le pendant papier de l'accusé de réception.
     *
     * ⚠️ Toutes les lignes du filtre, sans découpage en lots : c'est un tableau,
     * pas une page par personne - et une liste coupée en morceaux se perd. Elle
     * sert au guichet, en face du lot imprimé.
     */
    public function emargement(Request $request): Response|RedirectResponse
    {
        $event = Event::find($request->query('event_id'));

        if (! $event) {
            return back()->with('ukc_error', 'Sélectionnez un événement.');
        }

        $attestations = AttestationQuery::filtered($request, $event)
            ->with('participant')
            ->orderBy('recipient_name')
            ->orderBy('id')
            ->get();

        if ($attestations->isEmpty()) {
            return back()->with('ukc_error', 'Aucune attestation à porter sur la liste d’émargement.');
        }

        return BrandedPdf::stamp(Pdf::loadView('pdf.attestations-emargement', [
            'event' => $event,
            'attestations' => $attestations,
            'typeLabel' => Attestation::TYPES[(string) $request->query('type', '')] ?? 'Tous types',
        ])->setPaper('a4', 'portrait'), 'Liste d’émargement · '.$event->title)
            ->download('emargement-attestations.pdf');
    }

    /**
     * Pose ou retire la remise en main propre.
     *
     * ⚠️ Réversible, contrairement au passage d'une communication : se tromper de
     * ligne au guichet est banal, et une remise fausse ferait croire que
     * quelqu'un a son document alors qu'il attend encore.
     */
    public function deliver(Request $request, Attestation $attestation): RedirectResponse
    {
        $remettre = ! $attestation->delivered_at;

        $attestation->forceFill([
            'delivered_at' => $remettre ? now() : null,
            'delivered_by' => $remettre ? Auth::guard('web')->id() : null,
        ])->save();

        return back()->with('ukc_status', $remettre ? 'Attestation marquée remise.' : 'Remise annulée.');
    }
}
