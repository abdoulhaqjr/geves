<?php

namespace App\Models\Concerns;

use App\Notifications\NotificationKinds;

/**
 * Préférences de notification par type et par canal, portées par la colonne
 * JSON `notification_preferences`. Une valeur absente retombe sur le défaut du
 * registre {@see NotificationKinds}. Canaux logiques : « inapp » (base de
 * données) et « email » (mail). Le modèle qui l'utilise doit caster
 * `notification_preferences` en `array`.
 */
trait HasNotificationPreferences
{
    /** L'utilisateur veut-il ce type sur ce canal logique ('inapp'|'email') ? */
    public function wantsNotification(string $kind, string $channel): bool
    {
        $prefs = (array) ($this->notification_preferences ?? []);
        $default = NotificationKinds::defaults($kind);

        return (bool) ($prefs[$kind][$channel] ?? $default[$channel] ?? false);
    }

    /**
     * Canaux Laravel activés pour un type (database / mail). L'e-mail générique
     * n'est ajouté que si aucun Mailable dédié ne couvre déjà ce type pour ce
     * destinataire — sinon c'est le Mailable dédié (gaté par la préférence) qui
     * porte l'e-mail, pour éviter le doublon.
     *
     * @return array<int, string>
     */
    public function notificationChannels(string $kind): array
    {
        $channels = [];
        if ($this->wantsNotification($kind, 'inapp')) {
            $channels[] = 'database';
        }
        if ($this->wantsNotification($kind, 'email') && ! $this->usesDedicatedMailFor($kind)) {
            $channels[] = 'mail';
        }

        return $channels;
    }

    /**
     * Un Mailable dédié couvre-t-il déjà l'e-mail de ce type pour CE
     * destinataire ? Vrai pour les comptes participants (Mailables riches
     * existants) ; le staff surcharge à false (aucun Mailable dédié côté staff).
     */
    protected function usesDedicatedMailFor(string $kind): bool
    {
        return NotificationKinds::hasMail($kind);
    }

    /**
     * Préférences résolues (défauts fusionnés) pour l'audience du modèle, pour
     * l'affichage dans l'UI. Clé = kind, valeur = { label, inapp, email }.
     *
     * @return array<string, array{label: string, inapp: bool, email: bool}>
     */
    public function resolvedNotificationPreferences(string $audience): array
    {
        $out = [];
        foreach (NotificationKinds::forAudience($audience) as $kind => $def) {
            $out[$kind] = [
                'label' => $def['label'],
                'inapp' => $this->wantsNotification($kind, 'inapp'),
                'email' => $this->wantsNotification($kind, 'email'),
            ];
        }

        return $out;
    }

    /**
     * Enregistre les préférences depuis l'UI (map kind => { inapp, email }),
     * restreinte aux types de l'audience donnée.
     *
     * @param  array<string, array{inapp?: bool, email?: bool}>  $prefs
     */
    public function saveNotificationPreferences(array $prefs, string $audience): void
    {
        $allowed = NotificationKinds::forAudience($audience);
        $clean = [];

        foreach ($allowed as $kind => $def) {
            $clean[$kind] = [
                'inapp' => (bool) ($prefs[$kind]['inapp'] ?? false),
                'email' => (bool) ($prefs[$kind]['email'] ?? false),
            ];
        }

        // Conserve d'éventuelles préférences d'une autre audience (compte staff mixte).
        $existing = (array) ($this->notification_preferences ?? []);
        $this->forceFill(['notification_preferences' => array_merge($existing, $clean)])->save();
    }
}
