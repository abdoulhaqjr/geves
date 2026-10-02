<?php

use App\Models\DocumentTemplate;
use Illuminate\Database\Migrations\Migration;

/**
 * À l'ouverture du concepteur, le nom du modèle était reconstruit en JavaScript :
 * « Attestation de » + le titre enregistré - qui commençait déjà par « Attestation
 * de ». Chaque enregistrement empilait donc un préfixe de plus, jusqu'à des titres
 * comme « Attestation de Attestation de formation ».
 *
 * La cause est corrigée (le concepteur affiche désormais le titre tel quel), mais
 * les noms déjà empilés restent en base : on les ramène à une seule occurrence.
 *
 * ⚠️ Aucun retour arrière : réempiler des préfixes n'aurait aucun intérêt.
 */
return new class extends Migration
{
    /** Préfixes que le concepteur ajoutait en double. */
    private const PREFIXES = ['Attestation de ', 'Attestation d’', 'Badge · '];

    public function up(): void
    {
        DocumentTemplate::query()->each(function (DocumentTemplate $tpl): void {
            $title = $this->collapse((string) $tpl->title);

            if ($title !== (string) $tpl->title) {
                $tpl->forceFill(['title' => $title])->save();
            }
        });
    }

    public function down(): void
    {
        // Rien : on ne restaure pas un titre fautif.
    }

    /** Retire les préfixes répétés, en conservant le premier. */
    private function collapse(string $title): string
    {
        foreach (self::PREFIXES as $prefix) {
            while (str_starts_with($title, $prefix.$prefix)) {
                $title = substr($title, strlen($prefix));
            }
        }

        return $title;
    }
};
