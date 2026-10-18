<?php

namespace App\Http\Controllers\Account;

use App\Enums\EventPhase;
use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\Participant;
use App\Models\ReviewerApplication;
use App\Models\Submission;
use App\Support\AccountActivity;
use App\Support\CurrentEvent;
use App\Support\ParticipantDashboard;
use App\Support\ReviewerVolunteering;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

/**
 * Tableau de bord du participant.
 *
 * ⚠️ **L'écran BASCULE tout seul selon les dates de l'événement** : « avant » on
 * dépose, on corrige et on règle ; « pendant » on passe en séance et on suit son
 * programme ; « après » on retire ses documents. La bascule n'a qu'une définition,
 * {@see EventPhase::participantView()}, dérivée de `Event::phase()` -
 * celle dont le site public se sert déjà. **Ne pas recomparer des dates ici** :
 * deux règles finiraient par se contredire sur le même écran.
 *
 * L'assemblage des données vit dans {@see ParticipantDashboard} : lecture seule,
 * donc Blade, et une classe testable sans passer par HTTP.
 */
class DashboardController extends Controller
{
    public function __invoke(): View
    {
        $account = Auth::guard('account')->user();

        // Événement courant (sélecteur) : tout le tableau de bord se scope dessus.
        $current = CurrentEvent::current($account);

        // Appel ouvert, pour un compte qui ne participe encore à rien : il n'a pas
        // de colloque à sélectionner, et c'est pourtant là qu'il doit déposer.
        $openEvent = Event::acceptingSubmissions()
            ->latest('id')
            ->first();

        $context = $current ?? $openEvent;
        $tdb = new ParticipantDashboard($account, $context);
        $reviewerApplication = $context
            ? ReviewerApplication::where('event_id', $context->id)->where('user_id', $account->id)->first()
            : null;

        // On soumet TOUJOURS dans le colloque sélectionné ; contrôle de la place
        // restante en amont, avant même d'ouvrir le formulaire.
        $submitEvent = $context;
        $submitRemaining = $submitEvent ? Submission::remainingFor($submitEvent, $account) : 0;

        // Rôles dérivés (inter-événements, par rapprochement d'e-mail) : ils ne se
        // scopent PAS sur l'événement courant - un modérateur n'est pas forcément
        // inscrit à l'événement qu'il modère.
        $activity = new AccountActivity($account);

        // ⚠️ **Une fonction ne vient pas forcément avec une inscription.** Un
        // modérateur, un rapporteur ou un évaluateur est rapproché par E-MAIL : il
        // peut n'avoir aucun `Participant`, donc aucun colloque sélectionnable,
        // donc aucun événement de contexte. L'état vide le renverrait alors sur
        // « votre espace se remplira bientôt » en lui cachant les séances qu'on
        // lui a confiées - c'est exactement le défaut que garde un cas d'
        // `AccountActivityTest`.
        $fonctions = $activity->moderatedSessions()->isNotEmpty()
            || $activity->reportedSessions()->isNotEmpty()
            || $activity->committees()->isNotEmpty()
            || $activity->isReviewer();

        return view('account.dashboard', [
            'account' => $account,
            'event' => $context,
            'phase' => $tdb->phase(),
            'vue' => $tdb->vue(),
            'jour' => $tdb->jour(),

            'liveSessions' => $tdb->seancesEnDirect(),
            'prochainPassage' => $tdb->prochainPassage(),
            'passages' => $tdb->passages(),
            'agenda' => $tdb->agenda(),
            'jourOuvert' => $tdb->jourOuvert(),

            'indicateurs' => $tdb->indicateurs(),
            'communications' => $tdb->communications(),
            'tdb' => $tdb,

            'aFaire' => $tdb->aFaire(),
            'etapes' => $tdb->etapes(),
            'avancement' => $tdb->avancement(),
            'documents' => $tdb->documents(),

            'fraisDus' => $tdb->fraisDus(),
            'submitEvent' => $submitEvent,
            'submitRemaining' => $submitRemaining,
            'deadline' => $context?->submission_deadline,

            // Documents de l'événement (médias téléversés par l'organisation).
            'media' => $context
                ? $context->media()->orderBy('ordre')->orderBy('id')->get()
                : collect(),

            'roles' => $activity->roles(),
            'moderatedSessions' => $activity->moderatedSessions(),
            'reportedSessions' => $activity->reportedSessions(),
            'committees' => $activity->committees(),
            'isReviewer' => $activity->isReviewer(),
            'pendingReviews' => $tdb->relecturesEnAttente(),
            'canVolunteerAsReviewer' => $reviewerApplication === null && ReviewerVolunteering::eligible($context, $account),
            'reviewerApplication' => $reviewerApplication,

            // Un compte sans aucune participation, ni appel ouvert, ni fonction :
            // l'espace est vide, et le dire vaut mieux que des cartes à zéro.
            'vierge' => $context === null
                && ! $fonctions
                && Participant::where('user_id', $account->id)->doesntExist(),
        ]);
    }
}
