<?php

namespace App\Support;

use App\Models\Event;
use App\Models\Participant;
use App\Models\Submission;
use App\Models\User;

/** Ordre de présentation des noms, propre à chaque édition. */
class EventNameFormat
{
    public const FIRST_LAST = 'first_last';

    public const LAST_FIRST = 'last_first';

    /** @var array<int, string> */
    private static array $orders = [];

    public static function order(?Event $event): string
    {
        $value = $event?->settings['name_order'] ?? null;

        return $value === self::LAST_FIRST ? self::LAST_FIRST : self::FIRST_LAST;
    }

    public static function orderForId(?int $eventId): string
    {
        if (! $eventId) {
            return self::FIRST_LAST;
        }

        return self::$orders[$eventId] ??= self::order(Event::query()->find($eventId, ['id', 'settings']));
    }

    public static function parts(string $firstName, string $lastName, string $order, string $fallback = ''): string
    {
        $firstName = trim($firstName);
        $lastName = trim($lastName);
        if ($firstName === '' && $lastName === '') {
            return trim($fallback);
        }

        $lastName = mb_strtoupper($lastName, 'UTF-8');

        return trim($order === self::LAST_FIRST
            ? $lastName.' '.$firstName
            : $firstName.' '.$lastName);
    }

    public static function participant(Participant $participant): string
    {
        return self::parts(
            (string) $participant->first_name,
            (string) $participant->last_name,
            $participant->relationLoaded('event')
                ? self::order($participant->getRelation('event'))
                : self::orderForId($participant->event_id),
        );
    }

    public static function user(Event $event, User $user): string
    {
        return self::parts(
            (string) $user->first_name,
            (string) $user->last_name,
            self::order($event),
            (string) ($user->name ?: $user->email),
        );
    }

    /** Les anciens auteurs sans champs séparés suivent l'ordre si la casse permet de reconnaître le nom. */
    public static function author(?Event $event, array $author): string
    {
        $firstName = (string) ($author['first_name'] ?? '');
        $lastName = (string) ($author['last_name'] ?? '');

        if (trim($firstName) === '' && trim($lastName) === '') {
            return self::legacyAuthor((string) ($author['name'] ?? ''), self::order($event));
        }

        return self::parts(
            $firstName,
            $lastName,
            self::order($event),
            (string) ($author['name'] ?? ''),
        );
    }

    /** Les auteurs anciens peuvent être reliés à un compte sans avoir de prénom/nom dans le JSON. */
    public static function submissionAuthor(Event $event, Submission $submission, array $author, int $index): string
    {
        if (filled($author['first_name'] ?? null) || filled($author['last_name'] ?? null)) {
            return self::author($event, $author);
        }

        $submission->loadMissing('authorLinks.user');
        $user = $submission->authorLinks->firstWhere('author_index', $index)?->user;
        if ($user && filled($user->first_name) && filled($user->last_name)) {
            return self::user($event, $user);
        }

        return self::author($event, $author);
    }

    /** Réordonne seulement les noms anciens dont le bloc de nom de famille en capitales est identifiable. */
    private static function legacyAuthor(string $name, string $order): string
    {
        $name = trim(preg_replace('/\s+/u', ' ', $name) ?? $name);
        $words = preg_split('/\s+/u', $name) ?: [];
        if (count($words) < 2) {
            return $name;
        }

        $isSurname = static fn (string $word): bool => preg_match('/\p{L}/u', $word) === 1
            && $word === mb_strtoupper($word, 'UTF-8');

        if ($order === self::LAST_FIRST && ! $isSurname($words[0])) {
            $last = count($words);
            while ($last > 0 && $isSurname($words[$last - 1])) {
                $last--;
            }
            if ($last > 0 && $last < count($words)) {
                return implode(' ', array_slice($words, $last)).' '.implode(' ', array_slice($words, 0, $last));
            }
        }

        if ($order === self::FIRST_LAST && $isSurname($words[0])) {
            $first = 0;
            while ($first < count($words) && $isSurname($words[$first])) {
                $first++;
            }
            if ($first > 0 && $first < count($words)) {
                return implode(' ', array_slice($words, $first)).' '.implode(' ', array_slice($words, 0, $first));
            }
        }

        return $name;
    }
}
