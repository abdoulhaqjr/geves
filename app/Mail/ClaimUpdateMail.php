<?php

namespace App\Mail;

use App\Models\Claim;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ClaimUpdateMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Claim $claim,
        public bool $resolved,
        public ?string $response,
    ) {}

    public function envelope(): Envelope
    {
        $subject = $this->resolved
            ? 'Votre dossier '.$this->claim->reference.' a été traité'
            : 'Nouveau message concernant votre dossier '.$this->claim->reference;

        return new Envelope(subject: $subject);
    }

    public function content(): Content
    {
        return new Content(markdown: 'emails.claim-update');
    }
}
