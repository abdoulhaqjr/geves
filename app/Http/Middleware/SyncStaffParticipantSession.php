<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/** Synchronise les guards des espaces, sauf pendant l'impersonation d'un compte. */
class SyncStaffParticipantSession
{
    public function handle(Request $request, Closure $next): Response
    {
        $staff = Auth::guard('web')->user();

        if ($staff !== null && ! app('impersonate')->isImpersonating()
            && Auth::guard('account')->id() !== $staff->getAuthIdentifier()) {
            Auth::guard('account')->login($staff);
        }

        Auth::shouldUse('account');

        return $next($request);
    }
}
