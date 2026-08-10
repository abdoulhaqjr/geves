<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * E-mail de campagne / rappel : sujet et corps libres (déjà personnalisés au
 * moment de la mise en file). Le corps est du texte simple, rendu paragraphe
 * par paragraphe dans le gabarit markdown.
 */
class CampaignMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public string $subjectLine, public string $bodyText) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: $this->subjectLine !== '' ? $this->subjectLine : 'Message — Colloques UK');
    }

    public function content(): Content
    {
        return new Content(markdown: 'emails.campaign', with: [
            'bodyText' => $this->bodyText,
        ]);
    }
}
