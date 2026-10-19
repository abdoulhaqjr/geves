<?php

namespace App\Support;

use App\Models\Event;
use App\Models\Participant;
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

    /** Les anciens auteurs sans prénom/nom séparés gardent leur nom saisi. */
    public static function author(?Event $event, array $author): string
    {
        return self::parts(
            (string) ($author['first_name'] ?? ''),
            (string) ($author['last_name'] ?? ''),
            self::order($event),
            (string) ($author['name'] ?? ''),
        );
    }
}
