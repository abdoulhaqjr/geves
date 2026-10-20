<?php

namespace App\Support;

final class FirstPartyCookieNames
{
    public static function securePrefixEnabled(): bool
    {
        return str_starts_with((string) config('session.cookie'), '__Secure-');
    }

    public static function csrf(): string
    {
        return self::securePrefixEnabled() ? '__Secure-XSRF-TOKEN' : 'XSRF-TOKEN';
    }

    public static function notice(string $name): string
    {
        return self::securePrefixEnabled() && ! str_starts_with($name, '__Secure-')
            ? '__Secure-'.$name
            : $name;
    }
}
