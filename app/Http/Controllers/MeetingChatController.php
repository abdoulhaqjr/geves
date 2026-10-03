<?php

namespace App\Http\Controllers;

use App\Meetings\Contracts\Meetable;
use App\Models\EventSession;
use App\Models\Formation;
use App\Models\MeetingMessage;
use App\Models\MeetingMessageReaction;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
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
    /**
     * Objets dont la salle porte un fil.
     *
     * ⚠️ Liste FERMÉE : le type vient de l'URL, et on ne résout jamais une classe
     * à partir de ce que le client a écrit.
     *
     * @var array<string, class-string<Meetable&Model>>
     */
    public const TYPES = ['session' => EventSession::class, 'formation' => Formation::class];

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
        $salle = $this->salle($type, $id);

        $data = $request->validate([
            'uid' => ['required', 'string', 'max:32', 'alpha_num'],
            'body' => ['required', 'string', 'max:2000'],
            'reply_uid' => ['nullable', 'string', 'max:32', 'alpha_num'],
            'reply_author' => ['nullable', 'string', 'max:120'],
            'reply_excerpt' => ['nullable', 'string', 'max:160'],
        ]);

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
        $salle = $this->salle($type, $id);

        $data = $request->validate([
            'emoji' => ['required', 'string', 'in:'.implode(',', MeetingMessage::EMOJIS)],
        ]);

        $message = MeetingMessage::query()
            ->where('meetable_type', $salle::class)
            ->where('meetable_id', $salle->getKey())
            ->where('uid', $uid)
            ->firstOrFail();

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

        return $staff ? 's:'.$staff : 'anon';
    }

    /** La salle visée, si elle existe et si l'on a le droit d'y entrer. */
    private function salle(string $type, int $id): Meetable&Model
    {
        $classe = self::TYPES[$type] ?? null;
        abort_if($classe === null, 404);

        /** @var (Meetable&Model)|null $salle */
        $salle = $classe::query()->find($id);
        abort_if($salle === null, 404);

        // Le fil suit l'accès à la salle : qui ne peut pas entrer ne lit pas ce
        // qui s'y dit, et n'y écrit pas.
        abort_unless(
            $salle->canJoin(Auth::guard('account')->user(), Auth::guard('web')->check()),
            403,
        );

        return $salle;
    }
}
