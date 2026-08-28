<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * Connexion du personnel de gestion (guard « web »), contrôleur + Blade.
 * L'accès aux pages est ensuite filtré par les permissions UKC.
 */
class LoginController extends Controller
{
    public function create(): View
    {
        return view('admin.auth.login');
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ]);
        $this->ensureIsNotRateLimited($request);

        if (! Auth::guard('web')->attempt(
            ['email' => $request->input('email'), 'password' => $request->input('password')],
            $request->boolean('remember')
        )) {
            RateLimiter::hit($this->throttleKey($request));

            throw ValidationException::withMessages([
                'email' => __('Ces identifiants sont incorrects.'),
            ]);
        }

        RateLimiter::clear($this->throttleKey($request));
        $request->session()->regenerate();

        // Activité de connexion (page Utilisateurs) : date + IP.
        Auth::guard('web')->user()?->forceFill([
            'last_login_at' => now(),
            'last_login_ip' => $request->ip(),
        ])->save();

        return redirect()->intended(route('admin.dashboard'));
    }

    protected function ensureIsNotRateLimited(Request $request): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey($request), 5)) {
            return;
        }

        event(new Lockout($request));

        $seconds = RateLimiter::availableIn($this->throttleKey($request));

        throw ValidationException::withMessages([
            'email' => __('Trop de tentatives. Réessayez dans :sec secondes.', ['sec' => $seconds]),
        ]);
    }

    protected function throttleKey(Request $request): string
    {
        return 'admin|'.Str::transliterate(Str::lower((string) $request->input('email')).'|'.$request->ip());
    }
}
