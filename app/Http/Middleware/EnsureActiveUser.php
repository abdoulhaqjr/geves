<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureActiveUser
{
    public function handle(Request $request, Closure $next): Response
    {
        foreach (['account', 'web'] as $name) {
            $user = Auth::guard($name)->user();
            if (! $user instanceof User || User::query()->whereKey($user->getKey())
                ->where('is_active', true)->exists()) {
                continue;
            }

            Auth::guard('account')->logout();
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            if ($request->expectsJson()) {
                abort(403, 'Ce compte est désactivé.');
            }

            return redirect()->route('account.login')
                ->with('ukc_error', 'Ce compte est désactivé. Contactez un administrateur.');
        }

        return $next($request);
    }
}
