<?php

namespace App\AI\Concerns;

/**
 * Extraction robuste d'un objet JSON depuis une réponse texte de modèle
 * (retire d'éventuelles clôtures ```json et isole le premier objet {...}).
 */
trait ParsesJsonResponse
{
    protected function decodeJson(string $text): array
    {
        $text = trim($text);

        // Retire les clôtures Markdown ```json … ```
        if (str_starts_with($text, '```')) {
            $text = preg_replace('/^```[a-zA-Z]*\s*|\s*```$/', '', $text);
        }

        $decoded = json_decode((string) $text, true);
        if (is_array($decoded)) {
            return $decoded;
        }

        // Repli : isole le premier objet JSON de la chaîne.
        if (preg_match('/\{.*\}/s', (string) $text, $m)) {
            $decoded = json_decode($m[0], true);
            if (is_array($decoded)) {
                return $decoded;
            }
        }

        return [];
    }
}
