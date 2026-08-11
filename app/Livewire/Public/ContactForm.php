<?php

namespace App\Livewire\Public;

use App\Mail\ContactMessageMail;
use App\Models\Edition;
use App\Models\Email;
use App\Support\Notifier;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Livewire\Component;

/**
 * Formulaire de contact public d'une édition, avec anti-spam :
 * - honeypot (champ « website » invisible qui doit rester vide) ;
 * - délai minimal de remplissage (rejette les envois quasi-instantanés) ;
 * - throttle par IP (RateLimiter).
 * Envoie le message à l'adresse de contact de l'édition (repli sur l'expéditeur
 * applicatif) et le journalise dans la table emails.
 */
class ContactForm extends Component
{
    public int $editionId;

    public string $name = '';

    public string $email = '';

    public string $message = '';

    /** Honeypot : doit rester vide (rempli par les robots). */
    public string $website = '';

    /** Horodatage de rendu (anti-envoi instantané). */
    public int $loadedAt = 0;

    public bool $sent = false;

    public function mount(int $editionId): void
    {
        $this->editionId = $editionId;
        $this->loadedAt = time();
    }

    protected function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:150'],
            'email' => ['required', 'email', 'max:200'],
            'message' => ['required', 'string', 'min:10', 'max:5000'],
        ];
    }

    public function submit(): void
    {
        // Honeypot rempli ou envoi trop rapide (< 3 s) : on ignore silencieusement.
        if ($this->website !== '' || (time() - $this->loadedAt) < 3) {
            $this->reset('name', 'email', 'message', 'website');
            $this->sent = true;

            return;
        }

        $data = $this->validate();

        $key = 'contact:'.request()->ip();
        if (RateLimiter::tooManyAttempts($key, 5)) {
            $this->addError('message', 'Trop de messages envoyés. Réessayez plus tard.');

            return;
        }
        RateLimiter::hit($key, 3600);

        $edition = Edition::find($this->editionId);
        if (! $edition) {
            $this->addError('message', 'Édition introuvable.');

            return;
        }

        $recipient = $edition->settings['contact']['email'] ?? config('mail.from.address');

        Mail::to($recipient)->send(
            new ContactMessageMail($edition, $data['name'], $data['email'], $data['message'])
        );

        Email::create([
            'edition_id' => $edition->id,
            'participant_id' => null,
            'template' => 'contact',
            'recipient' => (string) $recipient,
            'subject' => 'Contact — '.($edition->title ?: 'Colloque'),
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

        $this->reset('name', 'email', 'message', 'website');
        $this->sent = true;
    }

    public function render()
    {
        return view('livewire.public.contact-form');
    }
}
