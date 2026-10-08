<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

/** Point d'entrée de la prise en main du back-office. */
class OnboardingController extends Controller
{
    public function __invoke(): View
    {
        return view('admin.onboarding');
    }
}
