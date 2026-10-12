<?php

namespace App\Support;

use App\Models\Event;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * Base de connaissance factuelle d'un événement, destinée aux agents IA (Gesco et
 * copilote admin). Rassemble en un texte compact les informations officielles
 * réellement configurées (identité, axes, échéances, règles de soumission,
 * tarifs, infos pratiques, contact, FAQ) pour que l'assistant réponde avec
 * précision au lieu d'approximer. Toutes les sources sont optionnelles : seules
 * les informations réellement renseignées sont incluses.
 */
class EventKnowledge
{
    /**
     * Brief factuel de l'événement (sections non vides uniquement). Retourne une
     * chaîne vide si l'événement est nulle.
     */
    public static function brief(?Event $event): string
    {
        if (! $event) {
            return '';
        }

        $s = (array) ($event->settings ?? []);
        $sections = array_filter([
            self::identity($event, $s),
            self::tracks($s),
            self::deadlines($event, $s),
            self::submissionRules($event, $s),
            self::fees($event, $s),
            self::practical($s),
            // Vue d'ensemble du programme (séances datées, salles, horaires) : info
            // publique, utile aussi bien au participant qu'au copilote admin.
            ProgramKnowledge::overview($event),
            self::contact($s),
            self::faq($s),
        ], fn ($block) => trim((string) $block) !== '');

        return implode("\n\n", $sections);
    }

    /** @param array<string,mixed> $s */
    private static function identity(Event $event, array $s): string
    {
        $name = $event->title ?: $event->series?->name ?: 'Événement';
        $lines = ['IDENTITÉ'];
        $lines[] = '- Événement : '.$name.($event->year ? ' ('.$event->year.')' : '');
        if (filled($event->theme ?? null)) {
            $lines[] = '- Thème : '.$event->theme;
        }
        if ($event->date_start) {
            $dates = $event->date_start->translatedFormat('d/m/Y')
                .($event->date_end ? ' - '.$event->date_end->translatedFormat('d/m/Y') : '');
            $lines[] = '- Dates : '.$dates;
        }
        if (filled($event->location ?? null)) {
            $lines[] = '- Lieu : '.$event->location;
        }
        $intro = trim((string) ($s['intro'] ?? ''));
        if ($intro !== '') {
            $lines[] = '- Présentation : '.Str::limit(self::plain($intro), 500);
        }

        return implode("\n", $lines);
    }

    /** @param array<string,mixed> $s */
    private static function tracks(array $s): string
    {
        $tracks = array_values(array_filter(array_map(
            fn ($t) => trim((string) (is_array($t) ? ($t['label'] ?? $t['name'] ?? '') : $t)),
            (array) ($s['tracks'] ?? []),
        )));
        if ($tracks === []) {
            return '';
        }

        return "AXES THÉMATIQUES\n".implode("\n", array_map(fn ($t) => '- '.$t, $tracks));
    }

    /** @param array<string,mixed> $s */
    private static function deadlines(Event $event, array $s): string
    {
        $lines = ['ÉCHÉANCES & DATES CLÉS'];

        // État réel des soumissions : « ouvertes » seulement si le délai n'est pas dépassé.
        $open = $event->submission_open
            && (! $event->submission_deadline || $event->submission_deadline->isFuture());
        $lines[] = $open
            ? '- Soumissions : OUVERTES'.($event->submission_deadline
                ? ' jusqu’au '.$event->submission_deadline->translatedFormat('d/m/Y à H\hi')
                : '')
            : '- Soumissions : FERMÉES'.($event->submission_deadline
                ? ' (délai dépassé le '.$event->submission_deadline->translatedFormat('d/m/Y').')'
                : '');

        // Jalons de la fiche d'événement (appel, notifications, inscriptions…).
        foreach ((array) ($s['key_dates'] ?? $s['program'] ?? []) as $row) {
            $label = trim((string) ($row['label'] ?? ''));
            if ($label === '') {
                continue;
            }
            $date = trim((string) ($row['date'] ?? ''));
            $when = '';
            if ($date !== '') {
                try {
                    $when = Carbon::parse($date)->translatedFormat('d/m/Y').' : ';
                } catch (\Throwable) {
                    $when = $date.' : ';
                }
            }
            $lines[] = '- '.$when.$label;
        }

        return count($lines) > 1 ? implode("\n", $lines) : '';
    }

    /** @param array<string,mixed> $s */
    private static function submissionRules(Event $event, array $s): string
    {
        $cfg = new SubmissionFormConfig($event);
        $rules = $cfg->rules();

        $lines = ['RÈGLES DE SOUMISSION'];
        $min = $cfg->abstractMin();
        $lines[] = '- Résumé : '.($min > 0 ? 'entre '.$min.' et ' : 'maximum ').$cfg->abstractMax().' mots.';
        $lines[] = '- Mots-clés : jusqu’à '.$cfg->keywordsMax().'.';
        $lines[] = '- Co-auteurs : jusqu’à '.((int) ($rules['max_authors'] ?? 8)).' auteurs.';
        $lines[] = '- Fichier : formats '.trim((string) ($rules['file_formats'] ?? 'pdf, docx'))
            .', max '.((int) ($rules['file_max_mb'] ?? 5)).' Mo.';
        $lines[] = '- Types de présentation : communication orale ou poster.';
        if (isset($s['max_communications']) && (int) $s['max_communications'] > 0) {
            $lines[] = '- Chaque auteur peut soumettre au plus '.((int) $s['max_communications']).' communication(s).';
        }
        if (! empty($rules['require_account'])) {
            $lines[] = '- Un compte participant (e-mail vérifié) est requis pour soumettre.';
        }

        return implode("\n", $lines);
    }

    /** @param array<string,mixed> $s */
    private static function fees(Event $event, array $s): string
    {
        $grid = Fees::grid($event);
        if ($grid === []) {
            return '';
        }

        $lines = ['TARIFS D’INSCRIPTION'];
        foreach ($grid as $fee) {
            $label = trim((string) ($fee['label'] ?? ''));
            if ($label === '') {
                continue;
            }
            $lines[] = '- '.$label.' : '.number_format((float) ($fee['amount'] ?? 0), 0, ',', ' ').' FCFA';
        }
        $note = trim((string) ($s['fees_note'] ?? ''));
        if ($note !== '') {
            $lines[] = '- Note : '.Str::limit(self::plain($note), 300);
        }

        return count($lines) > 1 ? implode("\n", $lines) : '';
    }

    /** @param array<string,mixed> $s */
    private static function practical(array $s): string
    {
        $p = (array) ($s['practical'] ?? []);
        $map = [
            'venue' => 'Lieu',
            'languages' => 'Langues / formats',
            'accommodation' => 'Hébergement',
            'hybrid' => 'Hybride / distanciel',
            'visa' => 'Visa / lettre d’invitation',
        ];

        $lines = ['INFORMATIONS PRATIQUES'];
        foreach ($map as $key => $label) {
            $val = trim((string) ($p[$key] ?? ''));
            if ($val !== '') {
                $lines[] = '- '.$label.' : '.Str::limit(self::plain($val), 260);
            }
        }

        return count($lines) > 1 ? implode("\n", $lines) : '';
    }

    /** @param array<string,mixed> $s */
    private static function contact(array $s): string
    {
        $c = (array) ($s['contact'] ?? []);
        $bits = array_filter([
            filled($c['email'] ?? null) ? 'e-mail '.$c['email'] : null,
            filled($c['phone'] ?? null) ? 'tél. '.$c['phone'] : null,
            filled($c['address'] ?? null) ? $c['address'] : null,
        ]);

        return $bits === [] ? '' : 'CONTACT ORGANISATION : '.implode(' - ', $bits);
    }

    /** @param array<string,mixed> $s */
    private static function faq(array $s): string
    {
        $faq = (array) ($s['faq'] ?? []);
        $items = [];
        foreach ($faq as $row) {
            $q = trim((string) ($row['question'] ?? $row['q'] ?? ''));
            $a = trim((string) ($row['answer'] ?? $row['a'] ?? ''));
            if ($q === '' || $a === '') {
                continue;
            }
            $items[] = 'Q: '.$q."\nR: ".Str::limit(self::plain($a), 400);
        }

        return $items === [] ? '' : "FOIRE AUX QUESTIONS (réponses officielles)\n".implode("\n", $items);
    }

    /** Normalise un texte libre (HTML/retours multiples) en une ligne lisible. */
    private static function plain(string $text): string
    {
        return trim(preg_replace('/\s+/u', ' ', strip_tags(str_replace(["\r", "\n"], ' ', $text))) ?? '');
    }
}
