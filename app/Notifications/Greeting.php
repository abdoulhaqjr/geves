<?php

namespace App\Notifications;

use App\Models\User;

/**
 * Ligne d'accueil des e-mails de compte : on salue la personne par sa civilité
 * et son nom quand ils sont connus (« Bonjour Dr Awa Doumbia, »), sinon on
 * retombe sur un accueil générique (« Bonjour, »). Localisé (clé FR + en.json).
 *
 * Source unique pour toutes les notifications de compte (réinitialisation,
 * vérification staff...) : un seul endroit décide de la formulation.
 */
class Greeting
{
    public static function for(?User $user): string
    {
        $name = $user?->salutationName();

        return $name !== null
            ? __('Bonjour :name,', ['name' => $name])
            : __('Bonjour,');
    }
}
