<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Support\CurrentEvent;
use App\Support\TutorialGuideCatalog;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** Guides réservés aux fonctions du participant dans le colloque sélectionné. */
class OnboardingController extends Controller
{
    public function __invoke(Request $request): View
    {
        $account = $request->user('account');
        $event = CurrentEvent::current($account);
        TutorialGuideCatalog::ensureDefaults();

        return view('account.onboarding', [
            'event' => $event,
            'guides' => TutorialGuideCatalog::forParticipant($account, $event),
        ]);
    }
}
