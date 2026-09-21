<?php

namespace App\Support;

use Illuminate\Support\Carbon;

/**
 * Export calendrier des passages d'un participant : fichier ICS (RFC 5545, avec
 * rappel VALARM) importable dans n'importe quel agenda, et lien « Ajouter à
 * Google Agenda ». Gratuit, sans dépendance externe.
 *
 * Chaque événement : ['uid'=>string, 'summary'=>string, 'start'=>Carbon,
 *   'end'=>Carbon|null, 'location'=>string, 'description'=>string].
 */
class CalendarExport
{
    /** Minutes de rappel avant l'événement (VALARM). */
    private const REMINDER_MINUTES = 60;

    /**
     * Document ICS (VCALENDAR) pour une liste d'événements.
     *
     * @param  list<array{uid:string, summary:string, start:Carbon, end:Carbon|null, location?:string, description?:string}>  $events
     */
    public function ics(array $events, string $calendarName = 'Mon agenda'): string
    {
        $lines = [
            'BEGIN:VCALENDAR',
            'VERSION:2.0',
            'PRODID:-//GEVES//Agenda//FR',
            'CALSCALE:GREGORIAN',
            'METHOD:PUBLISH',
            'X-WR-CALNAME:'.$this->escape($calendarName),
        ];

        $stamp = now()->utc()->format('Ymd\THis\Z');
        foreach ($events as $e) {
            $end = $e['end'] ?? $e['start']->copy()->addMinutes(20);
            $lines[] = 'BEGIN:VEVENT';
            $lines[] = 'UID:'.$this->escape($e['uid']);
            $lines[] = 'DTSTAMP:'.$stamp;
            $lines[] = 'DTSTART:'.$e['start']->copy()->utc()->format('Ymd\THis\Z');
            $lines[] = 'DTEND:'.$end->copy()->utc()->format('Ymd\THis\Z');
            $lines[] = 'SUMMARY:'.$this->escape($e['summary']);
            if (($e['location'] ?? '') !== '') {
                $lines[] = 'LOCATION:'.$this->escape($e['location']);
            }
            if (($e['description'] ?? '') !== '') {
                $lines[] = 'DESCRIPTION:'.$this->escape($e['description']);
            }
            $lines[] = 'BEGIN:VALARM';
            $lines[] = 'ACTION:DISPLAY';
            $lines[] = 'DESCRIPTION:'.$this->escape('Rappel : '.$e['summary']);
            $lines[] = 'TRIGGER:-PT'.self::REMINDER_MINUTES.'M';
            $lines[] = 'END:VALARM';
            $lines[] = 'END:VEVENT';
        }

        $lines[] = 'END:VCALENDAR';

        return implode("\r\n", array_map([$this, 'fold'], $lines))."\r\n";
    }

    /**
     * Lien « Ajouter à Google Agenda » pour un événement.
     *
     * @param  array{summary:string, start:Carbon, end:Carbon|null, location?:string, description?:string}  $e
     */
    public function googleUrl(array $e): string
    {
        $end = $e['end'] ?? $e['start']->copy()->addMinutes(20);

        return 'https://calendar.google.com/calendar/render?'.http_build_query([
            'action' => 'TEMPLATE',
            'text' => $e['summary'],
            'dates' => $e['start']->copy()->utc()->format('Ymd\THis\Z').'/'.$end->copy()->utc()->format('Ymd\THis\Z'),
            'location' => $e['location'] ?? '',
            'details' => $e['description'] ?? '',
        ]);
    }

    /** Échappement RFC 5545 (backslash, virgule, point-virgule, retour ligne). */
    private function escape(string $value): string
    {
        return str_replace(
            ['\\', ',', ';', "\r\n", "\n", "\r"],
            ['\\\\', '\\,', '\\;', '\\n', '\\n', '\\n'],
            $value,
        );
    }

    /** Repli des lignes > 75 octets (RFC 5545 : continuation par espace). */
    private function fold(string $line): string
    {
        if (strlen($line) <= 75) {
            return $line;
        }

        $out = '';
        $chunk = '';
        foreach (mb_str_split($line) as $char) {
            if (strlen($chunk) + strlen($char) > 74) {
                $out .= ($out === '' ? '' : "\r\n ").$chunk;
                $chunk = '';
            }
            $chunk .= $char;
        }

        return $out.($out === '' ? '' : "\r\n ").$chunk;
    }
}
