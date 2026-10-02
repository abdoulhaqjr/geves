<?php

use App\Models\DocumentTemplate;
use Illuminate\Database\Migrations\Migration;

/**
 * Le QR de vérification encodait une adresse **fixe et sans identifiant**
 * (« …/attestation-verify »), posée comme valeur par défaut par le concepteur :
 * scanner deux attestations différentes menait au même endroit, qui ne
 * vérifiait rien.
 *
 * Il encode désormais le jeton `{verify_url}`, que le rendu remplace par
 * l'adresse de l'attestation délivrée. Cette migration reprend les modèles déjà
 * enregistrés, mise en page et instantané de l'éditeur.
 *
 * ⚠️ Une adresse **personnalisée** (QR « Custom », vers un site, un programme…)
 * n'est pas touchée : seule l'adresse de vérification fixe l'est.
 */
return new class extends Migration
{
    private const TOKEN = '{verify_url}';

    public function up(): void
    {
        DocumentTemplate::query()->each(function (DocumentTemplate $tpl): void {
            $layout = (array) $tpl->layout;
            $state = (array) $tpl->editor_state;
            $changed = false;

            foreach ((array) ($layout['elements'] ?? []) as $i => $el) {
                if (($el['type'] ?? '') === 'qr' && $this->isFixedVerifyUrl((string) ($el['value'] ?? ''))) {
                    $layout['elements'][$i]['value'] = self::TOKEN;
                    $changed = true;
                }
            }

            foreach ((array) ($state['els'] ?? []) as $i => $el) {
                if ($this->isFixedVerifyUrl((string) ($el['qrUrl'] ?? ''))) {
                    $state['els'][$i]['qrUrl'] = self::TOKEN;
                    $changed = true;
                }
            }

            if ($changed) {
                $tpl->forceFill(['layout' => $layout, 'editor_state' => $state])->save();
            }
        });
    }

    public function down(): void
    {
        // Rien : réencoder une adresse qui ne vérifie rien n'aurait aucun intérêt.
    }

    /** Adresse de vérification générique, sans identifiant d'attestation. */
    private function isFixedVerifyUrl(string $value): bool
    {
        return $value !== '' && (bool) preg_match('#^https?://[^/]+/attestation-verify/?$#i', $value);
    }
};
