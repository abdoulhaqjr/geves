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
use App\Support\ParticipantTypes;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Parcours public de paiement des co-auteurs (contrôleur + Blade, sans compte).
 * Le co-auteur se retrouve par e-mail (recherche en GET), voit les
 * communications acceptées qui l'associent, et règle **au tarif de son grade**.
 * Un co-auteur ayant payé devient éligible à l'attestation de communication.
 *
 * ⚠️ **Le tarif ne se choisit pas, il se déduit du grade.** La page proposait
 * les *catégories tarifaires* dans un menu, montant affiché en face : un
 * co-auteur y prenait la moins chère, et le grade déclaré dans la soumission
 * - qui est l'information dont le logiciel se sert partout ailleurs - était
 * ignoré. Le grade **connu fait foi** et n'est plus repostable ; il n'est
 * demandé que lorsqu'il manque, et alors c'est un GRADE qu'on demande
 * (`ParticipantTypes`), jamais une catégorie, jamais un prix.
 */
class CoauthorController extends Controller
{
    public function show(Request $request, string $slug): View
    {
        $edition = $this->edition($slug);
        EditionPaymentConfig::apply($edition); // passerelle propre à l'édition
        $email = trim((string) $request->query('email', ''));
        $ctx = $email !== '' ? $this->resolve($edition, $email) : null;

        $grade = trim((string) ($ctx['grade'] ?? ''));
        $plusEleve = Fees::highestAmount($edition);

        // Montant par GRADE, et non par catégorie : la vue n'a jamais à faire
        // le rapprochement elle-même, `Fees` reste la seule à le savoir.
        // ⚠️ Le grade connu y figure MÊME s'il ne vient pas de la liste de
        // référence (une soumission ancienne a pu porter un intitulé libre, et
        // la liste est éditable en Réglages). Sans lui, la vue ne trouverait
        // aucun montant et les boutons de paiement resteraient désactivés.
        $parGrade = collect(ParticipantTypes::all())
            ->when($grade !== '', fn ($c) => $c->push($grade))
            ->unique()
            ->mapWithKeys(fn ($g) => [$g => Fees::resolveAmount($edition, $g) ?? $plusEleve])
            ->all();

        return view('public.coauthor', [
            'edition' => $edition,
            'email' => $email,
            'searched' => $email !== '',
            'foundSubmissions' => $ctx['found'] ?? [],
            'grade' => $grade,
            'gradeConnu' => $grade !== '',
            'gradeOptions' => ParticipantTypes::all(),
            'amountByGrade' => $parGrade,
            'amount' => $grade !== '' ? (Fees::resolveAmount($edition, $grade) ?? $plusEleve) : null,
            // Aucune catégorie ne couvre ce grade : le montant affiché est un
            // repli, et le co-auteur a le droit de le savoir avant de payer.
            'amountIsFallback' => $grade !== '' && Fees::resolveAmount($edition, $grade) === null,
            'participant' => $ctx['participant'] ?? null,
            'hasPaid' => ($ctx['participant'] ?? null)?->hasPaid() ?? false,
            'paidReceipt' => ($ctx['participant'] ?? null)?->participationPayments()->where('status', 'paid')->latest('id')->first(),
            'gateways' => app(GatewayRegistry::class)->available(),
        ]);
    }

    public function pay(Request $request, string $slug, UpsertCoauthorParticipant $upsert, GatewayRegistry $registry): RedirectResponse
    {
        $edition = $this->edition($slug);
        EditionPaymentConfig::apply($edition); // passerelle propre à l'édition

        $email = mb_strtolower(trim((string) $request->input('email')));
        $back = route('public.coauthor', $edition->slug).'?email='.urlencode($email);

        $ctx = $email !== '' ? $this->resolve($edition, $email) : ['found' => []];

        if (empty($ctx['found'])) {
            return redirect()->to($back)->withErrors(['pay' => 'Paiement indisponible.']);
        }

        // ⚠️ Le grade DÉJÀ CONNU fait foi - celui du participant, sinon celui
        // déclaré dans la soumission. Le champ posté ne sert qu'à le renseigner
        // quand il manque, et seul un grade de la liste de référence est reçu :
        // sinon n'importe qui ajustait son tarif en postant un autre libellé.
        // (le garde ci-dessus a déjà écarté le contexte vide)
        $grade = trim($ctx['grade']);
        if ($grade === '') {
            $propose = trim((string) $request->input('grade'));
            $grade = in_array($propose, ParticipantTypes::all(), true) ? $propose : '';
        }

        if ($grade === '') {
            return redirect()->to($back)->withErrors(['grade' => 'Indiquez votre grade : c’est lui qui détermine le tarif.']);
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
