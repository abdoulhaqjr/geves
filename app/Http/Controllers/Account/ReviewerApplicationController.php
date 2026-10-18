<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\ReviewerApplication;
use App\Support\ReviewerVolunteering;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;

class ReviewerApplicationController extends Controller
{
    public function store(Event $event): RedirectResponse
    {
        $account = Auth::guard('account')->user();
        abort_unless(ReviewerVolunteering::eligible($event, $account), 403);

        if (ReviewerApplication::where('event_id', $event->id)->where('user_id', $account->id)->exists()) {
            return redirect()->route('account.dashboard')
                ->with('ukc_status', 'Votre candidature pour cet événement est déjà enregistrée.');
        }

        ReviewerApplication::firstOrCreate(
            ['event_id' => $event->id, 'user_id' => $account->id],
            ['grade' => ReviewerVolunteering::grade(ReviewerVolunteering::participant($event, $account), $account), 'status' => 'pending'],
        );

        return redirect()->route('account.dashboard')
            ->with('ukc_status', 'Votre candidature d’évaluateur a été envoyée. Elle attend la validation du gestionnaire.');
    }
}
