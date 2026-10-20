<?php

namespace App\Http\Middleware;

use App\Support\FirstPartyCookieNames;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Symfony\Component\HttpFoundation\Cookie;

class PrefixedCsrfCookie extends PreventRequestForgery
{
    protected function newCookie($request, $config)
    {
        if (! FirstPartyCookieNames::securePrefixEnabled()) {
            return parent::newCookie($request, $config);
        }

        return new Cookie(
            FirstPartyCookieNames::csrf(),
            $request->session()->token(),
            $this->availableAt(60 * $config['lifetime']),
            $config['path'],
            $config['domain'],
            true,
            false,
            false,
            $config['same_site'] ?? null,
            $config['partitioned'] ?? false
        );
    }
}
