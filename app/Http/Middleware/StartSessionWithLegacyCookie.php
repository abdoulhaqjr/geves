<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Illuminate\Session\Middleware\StartSession;

class StartSessionWithLegacyCookie extends StartSession
{
    public function getSession(Request $request)
    {
        $name = (string) config('session.cookie');

        if (str_starts_with($name, '__Secure-') && ! $request->cookies->get($name)) {
            $legacyName = substr($name, strlen('__Secure-'));

            if ($legacyCookie = $request->cookies->get($legacyName)) {
                // EncryptCookies a déjà validé le cookie historique avant cette étape.
                $request->cookies->set($name, $legacyCookie);
            }
        }

        return parent::getSession($request);
    }
}
