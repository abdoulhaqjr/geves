<?php

namespace App\Support;

use App\Models\Event;
use App\Models\EventSession;
use App\Models\Slot;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * Synthèse TEXTUELLE du programme d'un événement, destinée aux assistants IA.
 *
 * - {@see overview()} : vue d'ensemble PUBLIQUE (séances par jour, salle, horaire,
 *   modérateur / rapporteur, nombre de communications). Pas de détail nominatif
 *   par communication. Injectée dans la base de connaissance partagée
 *   ({@see EventKnowledge}) : l'assistante publique peut répondre « quand a lieu
 *   l'atelier X, dans quelle salle ».
 * - {@see planning()} : planning DÉTAILLÉ des créneaux de communication (horaire,
 *   salle, titre + réf + auteur). Réservé au copilote admin (données de travail).
 *
 * Les deux sorties sont bornées pour rester dans un budget de contexte raisonnable.
 */
class ProgramKnowledge
{
    /** Vue d'ensemble publique : séances datées, par jour. */
    public static function overview(?Event $event): string
    {
        if (! $event) {
            return '';
        }

        /** @var Collection<int, EventSession> $sessions */
        $sessions = $event->sessions()->with('atelier')
            ->orderBy('starts_at')->orderBy('ordre')->get();

        if ($sessions->isEmpty()) {
            return '';
        }

        // Nombre de communications placées par séance (créneaux horodatés).
        $counts = $event->slots()->where('submission_id', '>', 0)
            ->whereNotNull('session_id')->selectRaw('session_id, count(*) as n')
            ->groupBy('session_id')->pluck('n', 'session_id');

        $rooms = [];
        $byDay = [];
        foreach ($sessions as $s) {
            $dayKey = optional($s->session_day ?? $s->starts_at)?->format('Y-m-d') ?? 'sans-date';
            $byDay[$dayKey][] = $s;
            $room = self::room($s);
            if ($room !== '') {
                $rooms[$room] = true;
            }
        }
        ksort($byDay);

        $lines = ['PROGRAMME (séances)'];
        foreach ($byDay as $dayKey => $daySessions) {
            $lines[] = '• '.self::dayLabel($dayKey).' :';
            foreach ($daySessions as $s) {
                $type = $s->type === 'plenary' ? 'Plénière' : 'Atelier';
                $name = self::sessionName($s);
                $hours = self::hours($s->starts_at, $s->ends_at);
                $room = self::room($s);
                $n = (int) ($counts[$s->id] ?? 0);
                $meta = array_filter([
                    $hours,
                    $room !== '' ? 'salle '.$room : '',
                    filled($s->moderator_name) ? 'modérateur '.$s->moderator_name : '',
                    filled($s->rapporteur_name) ? 'rapporteur '.$s->rapporteur_name : '',
                    $n > 0 ? $n.' communication'.($n > 1 ? 's' : '') : '',
                ]);
                $lines[] = '   - '.$type.' « '.$name.' »'.($meta !== [] ? ' : '.implode(' · ', $meta) : '');
            }
        }

        if ($rooms !== []) {
            $lines[] = 'Salles utilisées : '.implode(', ', array_keys($rooms)).'.';
        }

        return implode("\n", $lines);
    }

    /**
     * Planning détaillé des créneaux de communication (admin).
     *
     * @param  int  $max  nombre maximal de créneaux listés (budget de contexte)
     */
    public static function planning(?Event $event, int $max = 80): string
    {
        if (! $event) {
            return '';
        }

        /** @var Collection<int, Slot> $slots */
        $slots = $event->slots()->where('submission_id', '>', 0)
            ->whereNotNull('starts_at')
            ->with(['submission.participant.user', 'session.atelier'])
            ->orderBy('starts_at')->get();

        if ($slots->isEmpty()) {
            return '';
        }

        $total = $slots->count();
        $byDay = $slots->groupBy(fn (Slot $sl) => optional($sl->starts_at)->format('Y-m-d') ?? 'sans-date');

        $lines = ['PLANNING DÉTAILLÉ DES CRÉNEAUX DE COMMUNICATION ('.$total.' placé'.($total > 1 ? 's' : '').')'];
        $shown = 0;
        foreach ($byDay as $dayKey => $daySlots) {
            $lines[] = '• '.self::dayLabel((string) $dayKey).' :';
            foreach ($daySlots as $sl) {
                if ($shown >= $max) {
                    $lines[] = '   … ('.($total - $shown).' autre'.(($total - $shown) > 1 ? 's' : '').' créneau'.(($total - $shown) > 1 ? 'x' : '').' non listé'.(($total - $shown) > 1 ? 's' : '').')';
                    break 2;
                }
                $shown++;
                $hours = self::hours($sl->starts_at, $sl->ends_at);
                $room = filled($sl->room) ? (string) $sl->room : self::room($sl->session);
                $title = Str::limit((string) (data_get($sl, 'submission.title') ?? 'Communication'), 80);
                $ref = data_get($sl, 'submission.ref_code');
                $author = data_get($sl, 'submission.participant.user.full_name')
                    ?? data_get($sl, 'participant.user.full_name');
                $meta = array_filter([
                    $hours,
                    $room !== '' ? 'salle '.$room : '',
                    $ref ? 'réf '.$ref : '',
                    $author ? 'auteur '.$author : '',
                    $sl->wasRescheduled() ? 'REPROGRAMMÉ' : '',
                    $sl->wasPresented() ? 'passage constaté' : '',
                ]);
                $lines[] = '   - « '.$title.' »'.($meta !== [] ? ' : '.implode(' · ', $meta) : '');
            }
        }

        return implode("\n", $lines);
    }

    /** Nom lisible d'une séance (atelier de rattachement, sinon libellé). */
    private static function sessionName(EventSession $s): string
    {
        $name = $s->atelier?->title ?: $s->label;

        return $name !== '' ? (string) $name : 'Séance';
    }

    /** Salle d'une séance (colonne `room`), sinon chaîne vide. */
    private static function room(?EventSession $s): string
    {
        if (! $s) {
            return '';
        }

        return filled($s->room) ? (string) $s->room : '';
    }

    /** « 08:30–11:30 », « à partir de 09:00 », ou chaîne vide. */
    private static function hours(?Carbon $start, ?Carbon $end): string
    {
        if (! $start) {
            return '';
        }

        return $end
            ? $start->format('H:i').'–'.$end->format('H:i')
            : 'à partir de '.$start->format('H:i');
    }

    /** Libellé français d'un jour « Y-m-d », ou « date à préciser ». */
    private static function dayLabel(string $dayKey): string
    {
        if ($dayKey === 'sans-date' || $dayKey === '') {
            return 'Date à préciser';
        }

        try {
            return Str::ucfirst(Carbon::parse($dayKey)->locale('fr')->translatedFormat('l j F Y'));
        } catch (\Throwable) {
            return $dayKey;
        }
    }
}
