<?php

namespace App\Mail;

use App\Models\Email as EmailLog;
use App\Models\Submission;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Invitation d'un co-auteur à régler ses frais après l'acceptation d'une
 * communication à laquelle il est associé. Contient un lien pré-rempli vers le
 * parcours de paiement dédié aux co-auteurs.
 *
 * ⚠️⚠️ **LE JOURNAL SUIT LE TRANSPORT, PAS LA MISE EN FILE.** Cet e-mail est
 * une tâche de fond (`ShouldQueue`) : `Mail::to()->send()` ne fait que
 * l'EMPILER. L'action qui l'envoyait écrivait pourtant la ligne d'`emails` à
 * « sent », horodatée, dans la foulée - donc le back-office affirmait « envoyé »
 * alors que rien n'était parti, et il le disait encore si le travailleur
 * échouait ensuite. C'est exactement le symptôme « c'est prévu, mais le mail
 * n'arrive pas » : l'écran couvrait la panne au lieu de la montrer. Même
 * grammaire que {@see SubmissionStatusMail} : la ligne naît « queued » et c'est
 * le transport qui la fait passer à « sent » ou « failed ».
 */
class CoauthorPaymentInvitationMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public Submission $submission,
        public string $coauthorName,
        public string $paymentUrl,
        public ?int $emailLogId = null,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: __('Communication acceptée - réglez vos frais de co-auteur'));
    }

    public function content(): Content
    {
        return new Content(markdown: 'emails.coauthor-payment');
    }

    public function send($mailer)
    {
        $sent = parent::send($mailer);

        if ($this->emailLogId !== null) {
            try {
                EmailLog::query()->whereKey($this->emailLogId)->update($sent === null
                    ? ['status' => 'failed', 'error' => 'Envoi interrompu avant le transport.']
                    : ['status' => 'sent', 'sent_at' => now(), 'error' => null]);
            } catch (Throwable $exception) {
                // ⚠️ Une panne du journal ne doit pas relancer un e-mail déjà transmis.
                Log::warning('Invitation co-auteur non journalisée après envoi.', [
                    'email_id' => $this->emailLogId,
                    'message' => $exception->getMessage(),
                ]);
            }
        }

        return $sent;
    }

    public function failed(Throwable $exception): void
    {
        if ($this->emailLogId !== null) {
            EmailLog::query()->whereKey($this->emailLogId)->update([
                'status' => 'failed',
                'error' => mb_substr($exception->getMessage(), 0, 500),
            ]);
        }
    }
}
