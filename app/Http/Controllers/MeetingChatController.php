<?php

namespace App\Http\Controllers;

use App\Meetings\Contracts\Meetable;
use App\Meetings\MeetableRegistry;
use App\Models\EventSession;
use App\Models\MeetingMessage;
use App\Models\MeetingMessageReaction;
use App\Support\MeetingTranscript;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;

/**
 * Fil de discussion d'une salle de visio : archivé, donc **retrouvable**.
 *
 * ⚠️ Le chat ne vivait que sur le canal de données de LiveKit. Qui arrivait en
 * cours de séance, ou rechargeait sa page, trouvait une salle muette : les
 * questions posées avant lui n'existaient plus. Le canal de données reste pour
 * l'instantané (les personnes déjà connectées) ; cette API garde le fil et le
 * rejoue à l'arrivée.
 *
 * Commun à tous les objets `Meetable` - séance du programme ET formation -
 * comme le reste du module visio.
 */
class MeetingChatController extends Controller
{
    /** Fenêtre rejouée à l'arrivée : de quoi reprendre le fil, pas toute l'histoire. */
    private const LIMITE = 200;

    /** Le fil, dans l'ordre, avec les réactions déjà posées. */
    public function index(string $type, int $id): JsonResponse
    {
        $salle = $this->salle($type, $id);
        $moi = $this->signature();

        $messages = MeetingMessage::query()
            ->where('meetable_type', $salle::class)
            ->where('meetable_id', $salle->getKey())
            ->with('reactions')
            ->latest('id')
            ->limit(self::LIMITE)
            ->get()
            ->reverse()
            ->values();

        $compte = Auth::guard('account')->id();
        $monNom = $salle->meetingDisplayName(Auth::guard('account')->user(), Auth::guard('web')->check(), null);

        return response()->json([
            'messages' => $messages->map(fn (MeetingMessage $m) => [
                'uid' => $m->uid,
                'who' => $m->author_name,
                'body' => $m->body,
                'at' => $m->created_at?->toIso8601String(),
                // À défaut de compte (le staff n'en a pas), le nom d'affichage
                // suffit à reconnaître ses propres messages.
                'mine' => $m->user_id ? ((int) $m->user_id === (int) $compte) : ($m->author_name === $monNom),
                'replyTo' => $m->reply_uid ? [
                    'id' => $m->reply_uid,
                    'who' => (string) $m->reply_author,
                    'text' => (string) $m->reply_excerpt,
                ] : null,
                'reactions' => $this->compte($m, $moi),
            ])->all(),
        ]);
    }

    /** Ajoute un message au fil. */
    public function store(Request $request, string $type, int $id): JsonResponse
    {
        $salle = $this->salle($type, $id, true);

        $data = $request->validate([
            'uid' => ['required', 'string', 'max:32', 'alpha_num'],
            'body' => ['required', 'string', 'max:2000'],
            'reply_uid' => ['nullable', 'string', 'max:32', 'alpha_num'],
            'reply_author' => ['nullable', 'string', 'max:120'],
            'reply_excerpt' => ['nullable', 'string', 'max:160'],
        ]);

        // ⚠️ Idempotent : un réseau qui bégaie rejoue l'envoi, et le même
        // message apparaîtrait deux fois dans le fil - avec deux lignes portant
        // le même identifiant, dont une réaction ne saurait plus laquelle viser.
        $existant = MeetingMessage::query()
            ->where('meetable_type', $salle::class)
            ->where('meetable_id', $salle->getKey())
            ->where('uid', $data['uid'])
            ->first();

        if ($existant) {
            return response()->json(['ok' => true, 'uid' => $existant->uid]);
        }

        $message = new MeetingMessage($data);
        $message->user_id = Auth::guard('account')->id();
        // ⚠️ Le nom de l'auteur est calculé ICI, jamais reçu du client : sinon
        // n'importe qui signerait ses messages du nom d'un autre.
        $message->author_name = $salle->meetingDisplayName(
            Auth::guard('account')->user(),
            Auth::guard('web')->check(),
            null,
        );
        $message->meetable()->associate($salle);
        $message->save();

        return response()->json(['ok' => true, 'uid' => $message->uid]);
    }

    /** Pose ou retire sa réaction sur un message. */
    public function react(Request $request, string $type, int $id, string $uid): JsonResponse
    {
        $salle = $this->salle($type, $id, true);

        $data = $request->validate([
            'emoji' => ['required', 'string', 'in:'.implode(',', MeetingMessage::EMOJIS)],
        ]);

        $message = $this->message($salle, $uid);

        $moi = $this->signature();
        // ⚠️ UNE SEULE réaction par personne et par message : on cherche la
        // sienne quel que soit son emoji. Sans cela, la même personne empilait
        // 👍 ET ❤️ ET 👏, et pesait à elle seule plus que trois autres.
        $pose = MeetingMessageReaction::query()
            ->where('meeting_message_id', $message->id)
            ->where('reactor', $moi)
            ->first();
        $ancien = $pose?->emoji;
        $meme = $ancien === $data['emoji'];

        // Le même emoji retire la sienne (un seul geste pour poser et retirer) ;
        // un autre emoji la REMPLACE.
        $pose?->delete();
        if (! $meme) {
            MeetingMessageReaction::create([
                'meeting_message_id' => $message->id,
                'reactor' => $moi,
                'emoji' => $data['emoji'],
            ]);
        }

        return response()->json([
            'uid' => $message->uid,
            'emoji' => $data['emoji'],
            'on' => ! $meme,
            // L'emoji retiré au passage : les autres salles ajustent leur
            // décompte sans avoir à recharger tout le fil.
            'replaced' => $meme ? null : $ancien,
            'reactions' => $this->compte($message->load('reactions'), $moi),
        ]);
    }

    /**
     * Retire un message du fil.
     *
     * ⚠️ Le fil est désormais CONSERVÉ : un message déplacé y resterait pour
     * toutes les prochaines arrivées. Réservé au modérateur de la séance - c'est
     * lui qui répond de ce qui se dit dans sa salle.
     */
    public function destroy(string $type, int $id, string $uid): JsonResponse
    {
        $salle = $this->salle($type, $id);
        abort_unless(
            $salle->meetingRoleFor(Auth::guard('account')->user(), Auth::guard('web')->check())->isModerator(),
            403,
        );

        $this->message($salle, $uid)->delete();

        return response()->json(['ok' => true, 'uid' => $uid]);
    }

    /**
     * Le fil en texte, pour le compte rendu de séance.
     *
     * Les questions posées en séance ont une valeur pour le PV, et rien ne
     * permettait de les en sortir. Réservé au modérateur, comme la présence.
     */
    public function export(string $type, int $id): Response
    {
        $salle = $this->salle($type, $id);
        abort_unless(
            $salle->meetingRoleFor(Auth::guard('account')->user(), Auth::guard('web')->check())->isModerator(),
            403,
        );

        return response(MeetingTranscript::toText($salle), 200, [
            'Content-Type' => 'text/plain; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="fil-visio-'.$type.'-'.$id.'.txt"',
        ]);
    }

    /** Un message de CETTE salle, ou 404. */
    private function message(Meetable&Model $salle, string $uid): MeetingMessage
    {
        return MeetingMessage::query()
            ->where('meetable_type', $salle::class)
            ->where('meetable_id', $salle->getKey())
            ->where('uid', $uid)
            ->firstOrFail();
    }

    /**
     * Décompte par emoji, et ce que la personne a elle-même posé.
     *
     * @return array<string, array{count: int, mine: bool}>
     */
    private function compte(MeetingMessage $message, string $moi): array
    {
        $par = [];
        foreach ($message->reactions as $r) {
            $par[$r->emoji] ??= ['count' => 0, 'mine' => false];
            $par[$r->emoji]['count']++;
            if ($r->reactor === $moi) {
                $par[$r->emoji]['mine'] = true;
            }
        }

        return $par;
    }

    /**
     * Qui réagit, sous une forme stable d'une connexion à l'autre.
     *
     * ⚠️ Surtout pas l'identité LiveKit : elle change à chaque connexion, et la
     * même personne compterait deux fois en rechargeant sa page.
     */
    private function signature(): string
    {
        $compte = Auth::guard('account')->id();
        if ($compte) {
            return 'u:'.$compte;
        }

        $staff = Auth::guard('web')->id();
        if ($staff) {
            return 's:'.$staff;
        }

        // ⚠️ Un visiteur sans compte - une cérémonie publique en accueille - doit
        // lui aussi être distinct des autres. Ils partageaient tous la clé
        // « anon » : le deuxième visiteur RETIRAIT la réaction du premier. Sa
        // session HTTP le distingue, et elle est HACHÉE pour ne pas recopier un
        // identifiant de session en clair dans une table.
        return 'a:'.substr(hash('sha256', (string) session()->getId()), 0, 30);
    }

    /**
     * La salle visée, si elle existe et si l'on a le droit d'y entrer.
     *
     * `$pourEcrire` ajoute la condition de la PAGE : on n'écrit que dans une
     * séance en cours. ⚠️ L'API ne vérifiait que le rôle, alors que la salle
     * exige rôle **et** séance en direct : on pouvait donc alimenter le fil
     * d'une séance terminée, ou pas encore ouverte, en appelant l'adresse
     * directement.
     */
    private function salle(string $type, int $id, bool $pourEcrire = false): Meetable&Model
    {
        $salle = MeetableRegistry::find($type, $id);
        abort_if($salle === null, 404);

        // Réconciliation paresseuse : une séance dont l'horaire est passé n'est plus
        // « en direct », donc on n'y écrit plus (la lecture du fil archivé reste
        // permise à qui peut encore entrer).
        if ($salle instanceof EventSession) {
            $salle->syncMeetingStatus();
        }

        // Le fil suit l'accès à la salle : qui ne peut pas entrer ne lit pas ce
        // qui s'y dit, et n'y écrit pas.
        abort_unless(
            $salle->canJoin(Auth::guard('account')->user(), Auth::guard('web')->check()),
            403,
        );
        abort_if($pourEcrire && ! $salle->isLive(), 403, 'La visio n’est pas en cours.');

        return $salle;
    }
}
