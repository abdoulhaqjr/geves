<?php

namespace App\Console\Commands;

use App\Documents\AttestationCertifier;
use App\Models\Attestation;
use Illuminate\Console\Command;

/**
 * Signe (ou re-signe) les attestations existantes non encore certifiées, une
 * fois une clé de signature configurée. Utile pour rattraper le stock émis avant
 * l'activation de la certification.
 *
 * Usage :
 *   php artisan attestations:sign            # attestations non signées seulement
 *   php artisan attestations:sign --force    # re-signe TOUTES les attestations
 */
class AttestationSignCommand extends Command
{
    protected $signature = 'attestations:sign {--force : Re-signe même les attestations déjà signées}';

    protected $description = 'Scelle les attestations existantes avec la signature de l’émetteur';

    public function handle(AttestationCertifier $certifier): int
    {
        if (! $certifier->enabled()) {
            $this->error('Aucune clé de signature configurée (ATTESTATION_SIGNING_KEY). Lancez d’abord attestations:keygen.');

            return self::FAILURE;
        }

        $query = Attestation::query();
        if (! $this->option('force')) {
            $query->whereNull('credential');
        }

        $signed = 0;
        $failed = 0;

        $query->with(['event.series'])->chunkById(200, function ($attestations) use ($certifier, &$signed, &$failed) {
            foreach ($attestations as $attestation) {
                try {
                    $result = $certifier->sign($attestation);
                } catch (\Throwable $e) {
                    report($e);
                    $failed++;

                    continue;
                }

                if ($result === null) {
                    $failed++;

                    continue;
                }

                $attestation->forceFill([
                    'credential' => $result['jws'],
                    'key_id' => $result['kid'],
                    'signed_at' => now(),
                ])->save();
                $signed++;
            }
        });

        $this->info(sprintf('Attestations signées : %d - échecs : %d.', $signed, $failed));

        return self::SUCCESS;
    }
}
