<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Models\Participant;
use App\Models\Testimonial;
use App\Support\Notifier;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

/**
 * Dépôt d'un témoignage par un participant connecté. Le témoignage part en
 * modération (`pending`) : rien n'apparaît sur l'accueil sans approbation de la
 * plateforme. Consentement de publication obligatoire.
 */
class TestimonialController extends Controller
{
    public function index(): View
    {
        $account = Auth::guard('account')->user();

        // Événements auxquelles la personne a participé (contexte optionnel du témoignage).
        $events = Participant::where('user_id', $account->id)
            ->with('event')
            ->get()
            ->pluck('event')
            ->filter()
            ->unique('id')
            ->values();

        return view('account.testimonials.index', [
            'account' => $account,
            'mine' => Testimonial::where('user_id', $account->id)->orderByDesc('id')->get(),
            'events' => $events,
            'pendingExists' => Testimonial::where('user_id', $account->id)->where('status', 'pending')->exists(),
        ]);
    }

    public function store(): RedirectResponse
    {
        $account = Auth::guard('account')->user();

        // Un seul témoignage en attente de modération à la fois.
        if (Testimonial::where('user_id', $account->id)->where('status', 'pending')->exists()) {
            return back()->with('ukc_error', 'Vous avez déjà un témoignage en attente de modération.');
        }

        $data = request()->validate([
            'quote' => ['required', 'string', 'min:20', 'max:2000'],
            'author_role' => ['nullable', 'string', 'max:160'],
            'author_affiliation' => ['nullable', 'string', 'max:200'],
            'event_id' => ['nullable', 'integer'],
            'rating' => ['nullable', 'integer', 'between:1,5'],
            'consent' => ['accepted'],
        ]);

        // Le contexte d'événement doit être un événement de la personne (anti-forgerie).
        $editionId = null;
        if (! empty($data['event_id'])) {
            $editionId = Participant::where('user_id', $account->id)
                ->where('event_id', (int) $data['event_id'])->exists()
                ? (int) $data['event_id'] : null;
        }

        $name = trim($account->first_name.' '.$account->last_name) ?: (string) $account->name;

        Testimonial::create([
            'user_id' => $account->id,
            'event_id' => $editionId,
            'author_name' => $name,
            'author_role' => $data['author_role'] ?? $account->grade,
            'author_affiliation' => $data['author_affiliation'] ?? $account->affiliation,
            'quote' => $data['quote'],
            'rating' => $data['rating'] ?? null,
            'status' => 'pending',
            'consent' => true,
        ]);

        Notifier::staff(
            kind: 'testimonial',
            title: 'Nouveau témoignage',
            body: $name.' a déposé un témoignage à modérer.',
            url: route('admin.testimonials.index'),
            icon: 'sparkles',
        );

        return back()->with('ukc_status', 'Merci ! Votre témoignage a été envoyé et sera publié après validation.');
    }
}
