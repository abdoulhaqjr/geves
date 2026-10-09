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

        auth('web')->login($demoUser->ensure());
        $request->session()->regenerate();

        return redirect()->away($target);
    }
}
