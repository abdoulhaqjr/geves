<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Mail\ContactMessageMail;
use App\Models\Email;
use App\Models\Event;
use App\Support\Notifier;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

/**
 * Formulaire de contact public d'un événement (Blade + POST classique, sans
 * Livewire). Anti-spam conservé :
 * - honeypot (champ « website » invisible qui doit rester vide) ;
 * - délai minimal de remplissage (rejette les envois quasi-instantanés) ;
 * - throttle par IP (RateLimiter).
 * Envoie le message à l'adresse de contact de l'événement (repli sur l'expéditeur
 * applicatif) et le journalise dans la table emails.
 */
class ContactController extends Controller
{
    public function send(Request $request, Event $event): RedirectResponse
    {
        // Honeypot rempli ou envoi trop rapide (< 3 s) : on ignore silencieusement
        // en affichant tout de même l'accusé (ne pas renseigner les robots).
        $loadedAt = (int) $request->input('loaded_at', 0);
        if ($request->filled('website') || (time() - $loadedAt) < 3) {
            return back()->withFragment('contact')->with('contact_sent', true);
        }

        $data = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'email' => ['required', 'email', 'max:200'],
            'message' => ['required', 'string', 'min:10', 'max:5000'],
        ]);

        $key = 'contact:'.$request->ip();
        if (RateLimiter::tooManyAttempts($key, 5)) {
            return back()->withInput()
                ->withErrors(['message' => 'Trop de messages envoyés. Réessayez plus tard.']);
        }
        RateLimiter::hit($key, 3600);

        $recipient = $event->settings['contact']['email'] ?? config('mail.from.address');

        Mail::to($recipient)->send(
            new ContactMessageMail($event, $data['name'], $data['email'], $data['message'])
        );

        Email::create([
            'event_id' => $event->id,
            'participant_id' => null,
            'template' => 'contact',
            'recipient' => (string) $recipient,
            'subject' => 'Contact - '.($event->title ?: 'Événement'),
            'body' => $data['message'],
            'sent_at' => now(),
            'status' => 'sent',
        ]);

        // Notification in-app au back-office : un message de contact reçu.
        Notifier::staff(
            kind: 'contact',
            title: 'Message de contact',
            body: $data['name'].' : '.Str::limit($data['message'], 80),
            url: null,
            icon: 'mail',
        );

        return back()->withFragment('contact')->with('contact_sent', true);
    }
}
