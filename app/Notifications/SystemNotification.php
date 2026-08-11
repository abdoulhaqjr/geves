<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

/**
 * Notification in-app générique (canal base de données). Une seule classe
 * paramétrée porte toutes les notifications de la plateforme : le champ `kind`
 * distingue le type (pour l'icône/le filtrage), les autres champs décrivent le
 * contenu et le lien cible. En file d'attente comme les Mailables (un worker est
 * requis en prod). Inspiré du pattern bizzcart, simplifié au seul canal in-app.
 *
 * @phpstan-type Payload array{kind: string, title: string, body: string, url: ?string, icon: string}
 */
class SystemNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public string $kind,
        public string $title,
        public string $body = '',
        public ?string $url = null,
        public string $icon = 'bell',
    ) {}

    /** Canal unique : base de données (in-app). */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * Charge utile stockée dans `notifications.data`.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'kind' => $this->kind,
            'title' => $this->title,
            'body' => $this->body,
            'url' => $this->url,
            'icon' => $this->icon,
        ];
    }
}
