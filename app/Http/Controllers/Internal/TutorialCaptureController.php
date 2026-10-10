<?php

namespace App\Http\Controllers\Internal;

use App\Http\Controllers\Controller;
use App\Models\TutorialGuide;
use App\Services\TutorialDemoUser;
use App\Support\TutorialCaptureCatalog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/** Ouvre une route GEVES réelle dans la session isolée du navigateur de capture. */
class TutorialCaptureController extends Controller
{
    public function __invoke(Request $request, TutorialGuide $guide, int $step, TutorialDemoUser $demoUser): RedirectResponse
    {
        $target = TutorialCaptureCatalog::targetFor($guide, $step);
        abort_if($target === null, 404);

        // Chaque capture démarre dans le rôle prévu par le guide. La page
        // d'inscription doit notamment rester visible après une capture de
        // l'espace participant dans la même session de navigateur.
        auth('web')->logout();
        auth('account')->logout();

        if ($target['guard'] === 'web') {
            auth('web')->login($demoUser->ensure());
        } elseif ($target['guard'] === 'account') {
            auth('account')->login($demoUser->ensureParticipant());
        }
        $request->session()->regenerate();

        return redirect()->away($target['url']);
    }
}
