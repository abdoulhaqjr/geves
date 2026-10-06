<?php

namespace App\Support;

use App\Http\Controllers\Admin\ProgramController;
use App\Http\Controllers\MeetingChatController;
use App\Meetings\Contracts\Meetable;
use App\Models\MeetingMessage;
use Illuminate\Database\Eloquent\Model;

/**
 * Mise en forme TEXTE du fil de discussion d'une salle (compte rendu de séance).
 *
 * Source unique, partagée par l'export depuis la salle ({@see MeetingChatController::export})
 * et par la page back-office des discussions ({@see ProgramController::discussionExport}) :
 * deux mises en forme finiraient par diverger sur ce qui est, justement, une trace.
 */
class MeetingTranscript
{
    /** Le fil complet (ordre chronologique) en texte brut daté. */
    public static function toText(Meetable&Model $meetable): string
    {
        $messages = MeetingMessage::query()
            ->where('meetable_type', $meetable::class)
            ->where('meetable_id', $meetable->getKey())
            ->with('reactions')
            ->orderBy('id')
            ->get();

        $lignes = [
            'Fil de discussion - salle '.$meetable->meetingRoom(),
            'Export du '.now()->translatedFormat('d F Y à H:i'),
            str_repeat('-', 60),
            '',
        ];

        foreach ($messages as $m) {
            $entete = '['.($m->created_at?->format('d/m H:i') ?? '').'] '.$m->author_name;
            if ($m->reply_uid) {
                $entete .= ' (en réponse à '.$m->reply_author.' « '.$m->reply_excerpt.' »)';
            }
            $lignes[] = $entete.' :';
            $lignes[] = '    '.str_replace("\n", "\n    ", (string) $m->body);

            $reactions = $m->reactions->groupBy('emoji')->map(fn ($g, $e) => $e.' '.$g->count())->implode(', ');
            if ($reactions !== '') {
                $lignes[] = '    réactions : '.$reactions;
            }
            $lignes[] = '';
        }

        if ($messages->isEmpty()) {
            $lignes[] = '(aucun message)';
        }

        return implode("\n", $lignes);
    }
}
