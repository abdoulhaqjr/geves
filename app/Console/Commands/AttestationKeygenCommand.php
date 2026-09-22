<?php

namespace App\Console\Commands;

use Firebase\JWT\JWT;
use Illuminate\Console\Command;

/**
 * Génère un couple de clés Ed25519 pour la certification des attestations.
 *
 * La GRAINE PRIVÉE est affichée pour être copiée dans `.env`
 * (`ATTESTATION_SIGNING_KEY`) — elle ne doit JAMAIS être committée ni stockée en
 * base. La clé publique est publiée automatiquement via le document `did:web` et
 * le JWKS une fois la graine en place.
 *
 * Usage : `php artisan attestations:keygen`
 */
class AttestationKeygenCommand extends Command
{
    protected $signature = 'attestations:keygen';

    protected $description = 'Génère un couple de clés Ed25519 pour signer les attestations (identité vérifiable)';

    public function handle(): int
    {
        $seed = random_bytes(SODIUM_CRYPTO_SIGN_SEEDBYTES);
        $keypair = sodium_crypto_sign_seed_keypair($seed);
        $public = sodium_crypto_sign_publickey($keypair);

        $seedB64 = JWT::urlsafeB64Encode($seed);
        $publicB64 = JWT::urlsafeB64Encode($public);

        sodium_memzero($seed);

        $this->info('Couple de clés Ed25519 généré pour la certification des attestations.');
        $this->newLine();
        $this->line('  Ajoutez cette ligne à votre fichier .env (ne la committez jamais) :');
        $this->newLine();
        $this->line('    ATTESTATION_SIGNING_KEY='.$seedB64);
        $this->newLine();
        $this->line('  Clé publique (base64url, publiée automatiquement via did:web / JWKS) :');
        $this->line('    '.$publicB64);
        $this->newLine();
        $this->warn('Conservez la graine privée en lieu sûr : elle ne peut pas être régénérée.');
        $this->warn('Une rotation de clé exige de conserver l’ancienne clé publique dans ATTESTATION_RETIRED_KEYS.');

        return self::SUCCESS;
    }
}
