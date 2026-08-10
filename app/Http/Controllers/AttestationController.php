<?php

namespace App\Http\Controllers;

use App\Models\Attestation;
use Illuminate\Contracts\View\View;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

/**
 * Attestations : document imprimable (avec QR de vérification) et page publique
 * de vérification par UUID.
 */
class AttestationController extends Controller
{
    /** Document imprimable de l'attestation (QR → page de vérification). */
    public function document(string $uuid): View
    {
        $attestation = Attestation::with(['edition.colloque', 'participant'])
            ->where('uuid', $uuid)->firstOrFail();

        $verifyUrl = route('attestation.verify', $attestation->uuid);
        $qr = QrCode::format('svg')->size(150)->margin(0)->generate($verifyUrl);

        return view('attestations.document', [
            'attestation' => $attestation,
            'qr' => $qr,
            'verifyUrl' => $verifyUrl,
        ]);
    }

    /** Page publique de vérification d'authenticité. */
    public function verify(string $uuid): View
    {
        $attestation = Attestation::with(['edition.colloque'])
            ->where('uuid', $uuid)->first();

        return view('attestations.verify', [
            'attestation' => $attestation,
        ]);
    }
}
