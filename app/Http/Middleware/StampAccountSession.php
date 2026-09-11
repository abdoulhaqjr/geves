<?php

namespace App\Http\Middleware;

use App\Http\Controllers\Account\SecurityController;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

/**
 * Rattache la session courante au compte participant connecté (guard « account »),
 * afin de pouvoir lister/révoquer les sessions actives d'un compte
 * ({@see SecurityController}). N'agit que si le driver de session est
 * « database » et une session existe déjà côté base.
 */
class StampAccountSession
{
    public function handle(Request $request, Closure $next): Response
    {
        $accountId = Auth::guard('account')->id();

        if ($accountId && config('session.driver') === 'database') {
            DB::table(config('session.table', 'sessions'))
                ->where('id', $request->session()->getId())
                ->update(['user_id' => $accountId]);
        }

        return $next($request);
    }
}
