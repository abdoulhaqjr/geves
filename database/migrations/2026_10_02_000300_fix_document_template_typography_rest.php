<?php

use App\Models\DocumentTemplate;
use Illuminate\Database\Migrations\Migration;

/**
 * Suite de 2026_10_02_000100 : la reprise des modèles livrés avait laissé passer
 * les lignes courtes, visibles sur l'attestation délivrée - « Theme du colloque :
 * », « Fait a Kara, le », « Reference : ». Mêmes règles que la première passe :
 * on ne réécrit que ces amorces exactes, et il n'y a pas de retour arrière.
 */
return new class extends Migration
{
    private const PHRASES = [
        'Theme du colloque :' => 'Thème du colloque :',
        'Fait a Kara, le' => 'Fait à Kara, le',
        'Reference : [certificate.uuid]' => 'Référence : [certificate.uuid]',
        // Libellés de calque, visibles dans le panneau « Calques ».
        'Theme label' => 'Thème (libellé)',
        'Reference' => 'Référence',
        'Theme' => 'Thème',
    ];

    public function up(): void
    {
        DocumentTemplate::query()->each(function (DocumentTemplate $tpl): void {
            $layout = $this->fix($tpl->layout);
            $state = $this->fix($tpl->editor_state);

            if ($layout !== $tpl->layout || $state !== $tpl->editor_state) {
                $tpl->forceFill(['layout' => $layout, 'editor_state' => $state])->save();
            }
        });
    }

    public function down(): void
    {
        // Rien : on ne restaure pas un texte fautif.
    }

    /** Réécrit récursivement les chaînes de la structure. */
    private function fix(mixed $value): mixed
    {
        if (is_string($value)) {
            return strtr($value, self::PHRASES);
        }

        if (is_array($value)) {
            return array_map(fn ($v) => $this->fix($v), $value);
        }

        return $value;
    }
};
