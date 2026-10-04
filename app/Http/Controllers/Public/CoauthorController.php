<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Edition;
use App\Models\Participant;
use App\Models\Submission;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

/**
 * **Porte d'entrée** des co-auteurs vers le règlement de leurs frais.
 *
 * Un co-auteur se retrouve ici par e-mail (recherche en GET), vérifie que des
 * communications acceptées le citent bien, puis **rejoint son espace** pour
 * régler au tarif de son grade.
 *
 * ⚠️ **Ce parcours n'encaisse plus lui-même.** Il doublonnait le chemin par
 * compte - celui que `LinkSubmissionAuthors` ouvre dès la soumission (liaison au
 * compte existant, sinon invitation à en créer un, réactivée à l'inscription par
 * `LinkPendingAuthorships`). Les deux créaient leur propre participant : l'un par
 * e-mail sans `user_id`, l'autre par `user_id`, et aucune contrainte d'unicité ne
 * les rapprochait. **Mesuré** : payer ici puis créer son compte laissait **deux
 * participants** pour la même personne sur la même édition - chiffres faussés, et
 * surtout l'attestation émise sur le participant sans compte, donc
 * **introuvable dans l'espace de l'intéressé**.
 *
 * ⚠️ L'URL est **conservée** : les e-mails d'acceptation déjà partis la portent,
 * et un lien mort aurait coûté des règlements. Elle oriente désormais. Le retour
 * de passerelle d'un règlement co-auteur **ancien** (`meta.flow = coauthor`)
 * atterrit toujours ici, d'où l'état « frais déjà réglés » conservé.
 */
class CoauthorController extends Controller
{
    public function show(Request $request, string $slug): View|RedirectResponse
    {
        $edition = $this->edition($slug);
        $email = trim((string) $request->query('email', ''));

        // Déjà connecté : on ne lui redemande rien, il règle dans son espace -
        // où sa communication l'attend déjà.
        if (Auth::guard('account')->check()) {
            return redirect()->route('account.payment', $edition);
        }

        $ctx = $email !== '' ? $this->resolve($edition, $email) : null;
        $participant = $ctx['participant'] ?? null;

        return view('public.coauthor', [
            'edition' => $edition,
            'email' => $email,
            'searched' => $email !== '',
            'foundSubmissions' => $ctx['found'] ?? [],
            'hasPaid' => $participant?->hasPaid() ?? false,
            'paidReceipt' => $participant?->participationPayments()->where('status', 'paid')->latest('id')->first(),
            // L'adresse part avec l'inscription : c'est elle qui rattache la
            // communication au compte créé (cf. LinkPendingAuthorships).
            'registerUrl' => route('account.register', $email !== '' ? ['email' => $email] : []),
            'loginUrl' => route('account.login'),
        ]);
    }

    private function edition(string $slug): Edition
    {
        $edition = Edition::where('slug', $slug)->first();
        abort_unless($edition && $edition->isPublished(), 404);

        return $edition;
    }

    /**
     * Contexte co-auteur pour un e-mail : communications acceptées qui
     * l'associent, et participant existant éventuel.
     *
     * @return array{found: list<array{ref: string, title: string}>, participant: ?Participant}
     */
    private function resolve(Edition $edition, string $email): array
    {
        $email = mb_strtolower(trim($email));

        $accepted = Submission::where('edition_id', $edition->id)
            ->where('revision_of', 0)->where('status', 'accepted')->get();

        $mine = $accepted->filter(fn (Submission $s) => $this->isCoauthor($s, $email))->values();

        return [
            'found' => $mine->map(fn (Submission $s) => ['ref' => (string) $s->ref_code, 'title' => (string) $s->title])->all(),
            'participant' => Participant::where('edition_id', $edition->id)
                ->whereRaw('LOWER(email) = ?', [$email])->first(),
        ];
    }

    /** L'e-mail figure-t-il parmi les auteurs NON correspondants de la soumission ? */
    private function isCoauthor(Submission $submission, string $emailLower): bool
    {
        foreach ($submission->authors ?? [] as $author) {
            if ($author['corresponding'] ?? false) {
                continue;
            }
            if (mb_strtolower(trim($author['email'] ?? '')) === $emailLower) {
                return true;
            }
        }

        return false;
    }
}
