<?php

namespace App\Http\Controllers\Public;

use App\Actions\UpsertCoauthorParticipant;
use App\Http\Controllers\Controller;
use App\Models\Edition;
use App\Models\Participant;
use App\Models\Payment;
use App\Models\Submission;
use App\Payments\GatewayRegistry;
use App\Support\EditionPaymentConfig;
use App\Support\Fees;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Parcours public de paiement des co-auteurs (contrôleur + Blade, sans compte).
 * Le co-auteur se retrouve par e-mail (recherche en GET), voit les
 * communications acceptées qui l'associent, choisit son grade (montant calculé
 * côté client depuis la grille tarifaire) puis paie. Un co-auteur ayant payé
 * devient éligible à l'attestation de communication.
 */
class CoauthorController extends Controller
{
    public function show(Request $request, string $slug): View
    {
        $edition = $this->edition($slug);
        EditionPaymentConfig::apply($edition); // passerelle propre à l'édition
        $email = trim((string) $request->query('email', ''));
        $ctx = $email !== '' ? $this->resolve($edition, $email) : null;

        $grid = array_map(
            fn ($f) => ['label' => (string) ($f['label'] ?? ''), 'amount' => (float) ($f['amount'] ?? 0)],
            Fees::grid($edition),
        );

        return view('public.coauthor', [
            'edition' => $edition,
            'email' => $email,
            'searched' => $email !== '',
            'foundSubmissions' => $ctx['found'] ?? [],
            'grade' => $ctx['grade'] ?? '',
            'participant' => $ctx['participant'] ?? null,
            'hasPaid' => ($ctx['participant'] ?? null)?->hasPaid() ?? false,
            'paidReceipt' => ($ctx['participant'] ?? null)?->payments()->where('status', 'paid')->latest('id')->first(),
            'feeGrid' => $grid,
            'feeMap' => collect($grid)->mapWithKeys(fn ($f) => [$f['label'] => $f['amount']])->all(),
            'highestAmount' => Fees::highestAmount($edition),
            'gateways' => app(GatewayRegistry::class)->available(),
        ]);
    }

    public function pay(Request $request, string $slug, UpsertCoauthorParticipant $upsert, GatewayRegistry $registry): RedirectResponse
    {
        $edition = $this->edition($slug);
        EditionPaymentConfig::apply($edition); // passerelle propre à l'édition

        $email = mb_strtolower(trim((string) $request->input('email')));
        $grade = trim((string) $request->input('grade'));
        $back = route('public.coauthor', $edition->slug).'?email='.urlencode($email);

        $ctx = $email !== '' ? $this->resolve($edition, $email) : ['found' => []];

        if (empty($ctx['found'])) {
            return redirect()->to($back)->withErrors(['pay' => 'Paiement indisponible.']);
        }
        if ($grade === '') {
            return redirect()->to($back)->withErrors(['grade' => 'Choisissez votre grade.']);
        }

        $amount = Fees::resolveAmount($edition, $grade) ?? Fees::highestAmount($edition);
        if (! $amount) {
            return redirect()->to($back)->withErrors(['pay' => 'Aucun tarif ne correspond à votre grade. Contactez l’organisation.']);
        }

        $gateway = $registry->get((string) $request->input('gateway'));
        if (! $gateway || ! $gateway->isAvailable()) {
            return redirect()->to($back)->withErrors(['pay' => 'Ce moyen de paiement n’est pas disponible.']);
        }

        $participant = $upsert->handle(
            $edition,
            $email,
            (string) ($ctx['name'] ?? $email),
            $ctx['affiliation'] ?? null,
            $grade,
        );

        if ($participant->hasPaid()) {
            return redirect()->to($back)->withErrors(['pay' => 'Vous avez déjà réglé vos frais.']);
        }

        $payment = Payment::firstOrCreate(
            ['edition_id' => $edition->id, 'participant_id' => $participant->id, 'status' => 'pending'],
            [
                'amount' => $amount,
                'currency' => config('payments.currency', 'XOF'),
                'method' => $gateway->key(),
                'gateway' => $gateway->key(),
                'meta' => ['flow' => 'coauthor'],
            ],
        );

        // Opérateur choisi chez nous (pré-sélection sur la page du prestataire).
        $payment->setOperator($request->input('operator'));

        try {
            $result = $gateway->initiate($payment);
        } catch (\Throwable $e) {
            report($e);

            return redirect()->to($back)->withErrors(['pay' => 'Le service de paiement est momentanément indisponible. Réessayez plus tard.']);
        }

        if ($result['type'] === 'redirect' && ! empty($result['url'])) {
            return redirect()->away($result['url']);
        }

        return redirect()->to($back)->with('pay_instructions', $result['message'] ?? 'Votre demande de paiement est enregistrée.');
    }

    private function edition(string $slug): Edition
    {
        $edition = Edition::where('slug', $slug)->first();
        abort_unless($edition && $edition->isPublished(), 404);

        return $edition;
    }

    /**
     * Contexte co-auteur pour un e-mail : communications acceptées associées,
     * nom / affiliation / grade déduits, et participant existant éventuel.
     *
     * @return array{found: list<array{ref:string, title:string}>, name: ?string, affiliation: ?string, grade: string, participant: ?Participant}
     */
    private function resolve(Edition $edition, string $email): array
    {
        $email = mb_strtolower(trim($email));

        $accepted = Submission::where('edition_id', $edition->id)
            ->where('revision_of', 0)->where('status', 'accepted')->get();

        $mine = $accepted->filter(fn (Submission $s) => $this->coauthorEntry($s, $email) !== null)->values();

        $found = $mine->map(fn (Submission $s) => ['ref' => (string) $s->ref_code, 'title' => (string) $s->title])->all();
        $entry = $mine->isNotEmpty() ? $this->coauthorEntry($mine->first(), $email) : null;

        $participant = Participant::where('edition_id', $edition->id)
            ->whereRaw('LOWER(email) = ?', [$email])->first();

        return [
            'found' => $found,
            'name' => $entry['name'] ?? null,
            'affiliation' => $entry['affiliation'] ?? null,
            'grade' => $participant?->title ?: (string) ($entry['grade'] ?? ''),
            'participant' => $participant,
        ];
    }

    /**
     * Entrée co-auteur (non correspondant) d'une soumission correspondant à
     * l'e-mail, ou null.
     *
     * @return array<string, mixed>|null
     */
    private function coauthorEntry(Submission $submission, string $emailLower): ?array
    {
        foreach ($submission->authors ?? [] as $author) {
            if ($author['corresponding'] ?? false) {
                continue;
            }
            if (mb_strtolower(trim($author['email'] ?? '')) === $emailLower) {
                return $author;
            }
        }

        return null;
    }
}
