<?php

namespace App\Documents;

use App\Models\Attestation;
use App\Support\EditionSettings;
use App\Support\Settings;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

/**
 * Certification cryptographique des attestations (identité vérifiable).
 *
 * Chaque attestation est scellée par une **signature EdDSA (Ed25519)** de
 * l'émetteur, sous la forme d'un **Verifiable Credential** W3C sécurisé en JWS
 * compact (« VC secured using JOSE », alias VC-JWT). On s'appuie sur
 * firebase/php-jwt (crypto vérifiée, libsodium) - jamais de crypto maison.
 *
 * La clé PRIVÉE de l'émetteur est une graine Ed25519 hors dépôt
 * (config `attestations.signing_key`, via l'environnement). La clé PUBLIQUE est
 * publiée via un document `did:web` et un JWKS pour permettre la vérification
 * par un tiers, hors-ligne.
 *
 * Modèle de confiance :
 *  - la SIGNATURE prouve l'authenticité et l'intégrité (émetteur + non-altération) ;
 *  - la RÉVOCATION reste un état en base (une signature ne peut pas être « défaite »),
 *    donc la validité courante = signature valide ET attestation non révoquée.
 */
class AttestationCertifier
{
    /** Algorithme JOSE (EdDSA sur courbe Ed25519). */
    public const ALG = 'EdDSA';

    /** Type d'en-tête JOSE pour un Verifiable Credential porté par un JWS. */
    private const HEADER_TYP = 'vc+jwt';

    /** Émetteur DID par défaut (repli des réglages d'édition). */
    public const DEFAULT_ISSUER = 'did:web:univkara.tg:colloques';

    /** La certification est-elle activée (clé de signature configurée et valide) ? */
    public function enabled(): bool
    {
        return $this->seed() !== null;
    }

    /**
     * Signe une attestation et renvoie le JWS compact + le kid utilisé, ou null
     * si la certification est désactivée (aucune clé configurée).
     *
     * @return array{jws: string, kid: string}|null
     */
    public function sign(Attestation $attestation): ?array
    {
        $seed = $this->seed();
        if ($seed === null) {
            return null;
        }

        $secretKey = sodium_crypto_sign_secretkey(sodium_crypto_sign_seed_keypair($seed));
        $kid = $this->kidFor($attestation);

        $jws = JWT::encode(
            $this->buildCredential($attestation),
            JWT::urlsafeB64Encode($secretKey),
            self::ALG,
            $kid,
            ['typ' => self::HEADER_TYP],
        );

        sodium_memzero($secretKey);
        sodium_memzero($seed);

        return ['jws' => $jws, 'kid' => $kid];
    }

    /**
     * Vérifie la signature d'une attestation déjà émise.
     *
     * @return array{
     *     signed: bool,
     *     valid: bool,
     *     reason: string,
     *     payload: array<string, mixed>|null
     * }
     */
    public function verify(Attestation $attestation): array
    {
        $jws = (string) $attestation->credential;
        if ($jws === '') {
            return ['signed' => false, 'valid' => false, 'reason' => 'unsigned', 'payload' => null];
        }

        $publicKey = $this->publicKeyForJws($jws);
        if ($publicKey === null) {
            return ['signed' => true, 'valid' => false, 'reason' => 'unknown_key', 'payload' => null];
        }

        try {
            $decoded = JWT::decode($jws, new Key(JWT::urlsafeB64Encode($publicKey), self::ALG));
        } catch (\Throwable $e) {
            return ['signed' => true, 'valid' => false, 'reason' => 'invalid_signature', 'payload' => null];
        }

        /** @var array<string, mixed> $payload */
        $payload = json_decode((string) json_encode($decoded), true);

        // La signature doit couvrir CETTE attestation (anti-substitution).
        if (($payload['id'] ?? null) !== 'urn:uuid:'.$attestation->uuid) {
            return ['signed' => true, 'valid' => false, 'reason' => 'subject_mismatch', 'payload' => $payload];
        }

        return ['signed' => true, 'valid' => true, 'reason' => 'ok', 'payload' => $payload];
    }

    /**
     * Construit le Verifiable Credential (modèle de données W3C VC 2.0) qui sera
     * signé. Claims JOSE enregistrées (iss/jti/iat) alignées sur les champs VC
     * pour rester lisible par un vérificateur générique.
     *
     * @return array<string, mixed>
     */
    public function buildCredential(Attestation $attestation): array
    {
        $edition = $attestation->edition;
        $issuer = $this->issuerFor($attestation);
        $issued = $attestation->issued_at ?? $attestation->freshTimestamp();
        $meta = $attestation->meta ?? [];

        $subject = array_filter([
            'name' => (string) $attestation->recipient_name,
            'attestationType' => (string) $attestation->type,
            'attestationLabel' => $attestation->typeLabel(),
            'event' => array_filter([
                'title' => (string) $edition->title,
                'year' => $edition->year ? (string) $edition->year : '',
                'organization' => (string) $edition->colloque->name,
                'location' => (string) $edition->location,
            ], fn ($v) => $v !== ''),
        ], fn ($v) => $v !== '' && $v !== []);

        // Communication concernée (attestations de communication uniquement).
        $communicationTitle = (string) ($meta['communication_title'] ?? '');
        if ($communicationTitle !== '') {
            $subject['communication'] = array_filter([
                'title' => $communicationTitle,
                'ref' => (string) ($meta['communication_ref'] ?? ''),
            ], fn ($v) => $v !== '');
        }

        $credentialId = 'urn:uuid:'.$attestation->uuid;

        return [
            '@context' => ['https://www.w3.org/ns/credentials/v2'],
            'id' => $credentialId,
            'type' => ['VerifiableCredential', 'AttestationCredential'],
            'issuer' => $issuer,
            'validFrom' => $issued->toIso8601String(),
            'credentialSubject' => $subject,
            'credentialStatus' => [
                'id' => route('attestation.verify', $attestation->uuid),
                'type' => 'GevesRegistryStatus2026',
            ],
            // Claims JOSE enregistrées, cohérentes avec le VC ci-dessus.
            'iss' => $issuer,
            'jti' => $credentialId,
            'sub' => $credentialId,
            'iat' => $issued->getTimestamp(),
            'nbf' => $issued->getTimestamp(),
        ];
    }

    /**
     * Document DID (did:web) publiant la clé publique de l'émetteur.
     *
     * @return array<string, mixed>|null
     */
    public function didDocument(): ?array
    {
        $jwk = $this->publicJwk();
        if ($jwk === null) {
            return null;
        }

        $issuer = $this->defaultIssuer();
        $vmId = $issuer.'#'.$this->activeKid();

        return [
            '@context' => [
                'https://www.w3.org/ns/did/v1',
                'https://w3id.org/security/suites/jws-2020/v1',
            ],
            'id' => $issuer,
            'verificationMethod' => [[
                'id' => $vmId,
                'type' => 'JsonWebKey2020',
                'controller' => $issuer,
                'publicKeyJwk' => $jwk,
            ]],
            'assertionMethod' => [$vmId],
        ];
    }

    /**
     * JWKS (JSON Web Key Set) de la clé publique active + clés retirées.
     *
     * @return array{keys: array<int, array<string, mixed>>}|null
     */
    public function jwks(): ?array
    {
        $active = $this->publicJwk();
        if ($active === null) {
            return null;
        }

        $keys = [$active];
        foreach (config('attestations.retired_public_keys', []) as $kid => $b64) {
            $keys[] = [
                'kty' => 'OKP',
                'crv' => 'Ed25519',
                'x' => (string) $b64,
                'kid' => (string) $kid,
                'alg' => self::ALG,
                'use' => 'sig',
            ];
        }

        return ['keys' => $keys];
    }

    /**
     * JWK de la clé publique active, ou null si la certification est désactivée.
     *
     * @return array<string, mixed>|null
     */
    public function publicJwk(): ?array
    {
        $public = $this->activePublicKey();
        if ($public === null) {
            return null;
        }

        return [
            'kty' => 'OKP',
            'crv' => 'Ed25519',
            'x' => JWT::urlsafeB64Encode($public),
            'kid' => $this->activeKid(),
            'alg' => self::ALG,
            'use' => 'sig',
        ];
    }

    /** Graine privée Ed25519 (32 octets) décodée, ou null si absente/invalide. */
    private function seed(): ?string
    {
        $b64 = (string) config('attestations.signing_key', '');
        if ($b64 === '') {
            return null;
        }

        try {
            $seed = JWT::urlsafeB64Decode($b64);
        } catch (\Throwable) {
            return null;
        }

        return strlen($seed) === SODIUM_CRYPTO_SIGN_SEEDBYTES ? $seed : null;
    }

    /** Clé publique active (32 octets) dérivée de la graine, ou null. */
    private function activePublicKey(): ?string
    {
        $seed = $this->seed();
        if ($seed === null) {
            return null;
        }

        return sodium_crypto_sign_publickey(sodium_crypto_sign_seed_keypair($seed));
    }

    /**
     * Sélectionne la clé publique (32 octets) permettant de vérifier un JWS,
     * d'après le fragment kid de son en-tête (support de la rotation).
     */
    private function publicKeyForJws(string $jws): ?string
    {
        $parts = explode('.', $jws);
        if (count($parts) !== 3) {
            return null;
        }

        try {
            /** @var array<string, mixed> $header */
            $header = json_decode((string) JWT::urlsafeB64Decode($parts[0]), true) ?: [];
        } catch (\Throwable) {
            return null;
        }

        $fragment = $this->fragment((string) ($header['kid'] ?? ''));

        if ($fragment === $this->activeKid() || $fragment === '') {
            return $this->activePublicKey();
        }

        $retired = config('attestations.retired_public_keys', []);
        if (isset($retired[$fragment])) {
            try {
                return JWT::urlsafeB64Decode((string) $retired[$fragment]);
            } catch (\Throwable) {
                return null;
            }
        }

        return null;
    }

    /** Émetteur DID d'une attestation (réglage d'édition, repli global). */
    private function issuerFor(Attestation $attestation): string
    {
        return (string) EditionSettings::get($attestation->edition, 'attestations.did_issuer', $this->defaultIssuer());
    }

    /** Émetteur DID par défaut (réglage global, puis constante). */
    private function defaultIssuer(): string
    {
        return (string) Settings::get('attestations.did_issuer', self::DEFAULT_ISSUER);
    }

    /** Identifiant de méthode de vérification (kid) complet pour une attestation. */
    private function kidFor(Attestation $attestation): string
    {
        return $this->issuerFor($attestation).'#'.$this->activeKid();
    }

    /** Fragment de clé actif (config). */
    private function activeKid(): string
    {
        return (string) config('attestations.active_kid', 'key-ed25519-1');
    }

    /** Fragment (après « # ») d'un identifiant de méthode de vérification. */
    private function fragment(string $kid): string
    {
        $pos = strrpos($kid, '#');

        return $pos === false ? $kid : substr($kid, $pos + 1);
    }
}
