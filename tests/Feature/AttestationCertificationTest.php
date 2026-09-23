<?php

use App\Actions\IssueAttestation;
use App\Documents\AttestationCertifier;
use App\Models\Edition;
use App\Models\Participant;
use App\Models\User;

/** Active la certification avec une clé Ed25519 éphémère (isolée au test). */
function enableAttestationCertification(): void
{
    $seed = random_bytes(SODIUM_CRYPTO_SIGN_SEEDBYTES);
    $b64 = rtrim(strtr(base64_encode($seed), '+/', '-_'), '=');

    config()->set('attestations.signing_key', $b64);
    config()->set('attestations.active_kid', 'key-ed25519-1');
    config()->set('attestations.retired_public_keys', []);
}

function certParticipant(Edition $edition, string $email = 'awa@example.tg'): Participant
{
    $user = User::create([
        'name' => 'Awa Kodjo',
        'first_name' => 'Awa', 'last_name' => 'Kodjo',
        'email' => $email, 'password' => 'pass12345',
        'grade' => 'Doctorante', 'affiliation' => 'Université de Kara',
    ]);
    $user->markEmailAsVerified();

    return Participant::create([
        'edition_id' => $edition->id,
        'user_id' => $user->id,
        'first_name' => 'Awa',
        'last_name' => 'Kodjo',
        'email' => $email,
        'role' => 'author',
    ]);
}

it('signe l’attestation à l’émission et la signature est vérifiable', function () {
    enableAttestationCertification();
    $participant = certParticipant(openEdition());

    $attestation = app(IssueAttestation::class)->handle($participant, 'participation');

    expect($attestation->credential)->not->toBeNull()
        ->and($attestation->key_id)->toContain('#key-ed25519-1')
        ->and($attestation->signed_at)->not->toBeNull();

    $proof = app(AttestationCertifier::class)->verify($attestation);
    expect($proof['signed'])->toBeTrue()
        ->and($proof['valid'])->toBeTrue()
        ->and($proof['payload']['id'])->toBe('urn:uuid:'.$attestation->uuid);
});

it('rejette une signature altérée', function () {
    enableAttestationCertification();
    $attestation = app(IssueAttestation::class)->handle(certParticipant(openEdition()), 'participation');

    // On corrompt RÉELLEMENT les octets de la signature : on décode le segment,
    // on inverse un bit d'un octet central, puis on ré-encode. Altération garantie
    // (contrairement au flip du dernier caractère base64url, dont les 4 bits de
    // bourrage rendaient certaines mutations sans effet sur les octets décodés).
    [$h, $p, $s] = explode('.', (string) $attestation->credential);
    $bin = sodium_base642bin($s, SODIUM_BASE64_VARIANT_URLSAFE_NO_PADDING);
    $mid = intdiv(strlen($bin), 2);
    $bin[$mid] = $bin[$mid] ^ "\x01";
    $tampered = sodium_bin2base64($bin, SODIUM_BASE64_VARIANT_URLSAFE_NO_PADDING);
    $attestation->credential = $h.'.'.$p.'.'.$tampered;

    $proof = app(AttestationCertifier::class)->verify($attestation);
    expect($proof['valid'])->toBeFalse()
        ->and($proof['reason'])->toBe('invalid_signature');
});

it('rejette une signature valide portant sur une autre attestation (anti-substitution)', function () {
    enableAttestationCertification();
    $edition = openEdition();
    $a = app(IssueAttestation::class)->handle(certParticipant($edition, 'a@example.tg'), 'participation');
    $b = app(IssueAttestation::class)->handle(certParticipant($edition, 'b@example.tg'), 'participation');

    // On recolle la preuve de A sur B (uuid différent).
    $b->credential = $a->credential;

    $proof = app(AttestationCertifier::class)->verify($b);
    expect($proof['valid'])->toBeFalse()
        ->and($proof['reason'])->toBe('subject_mismatch');
});

it('émet sans signature quand aucune clé n’est configurée (fail-open)', function () {
    config()->set('attestations.signing_key', '');
    $attestation = app(IssueAttestation::class)->handle(certParticipant(openEdition()), 'participation');

    expect($attestation->credential)->toBeNull()
        ->and($attestation->isSigned())->toBeFalse();

    $proof = app(AttestationCertifier::class)->verify($attestation);
    expect($proof['signed'])->toBeFalse()->and($proof['valid'])->toBeFalse();
});

it('affiche la signature vérifiée sur la page publique de vérification', function () {
    enableAttestationCertification();
    $attestation = app(IssueAttestation::class)->handle(certParticipant(openEdition()), 'participation');

    $this->get(route('attestation.verify', $attestation->uuid))
        ->assertOk()
        ->assertSee('Attestation authentique')
        ->assertSee('Signature cryptographique vérifiée');
});

it('marque une attestation révoquée comme invalide malgré une signature valide', function () {
    enableAttestationCertification();
    $attestation = app(IssueAttestation::class)->handle(certParticipant(openEdition()), 'participation');
    $attestation->update(['revoked' => true]);

    $this->get(route('attestation.verify', $attestation->uuid))
        ->assertOk()
        ->assertSee('révoquée')
        ->assertDontSee('Signature cryptographique vérifiée');
});

it('publie la clé publique via le document DID et le JWKS', function () {
    enableAttestationCertification();

    $expected = $this->app->make(AttestationCertifier::class)->publicJwk()['x'];

    $did = $this->get(route('attestation.did'))->assertOk()->json();
    expect($did['verificationMethod'][0]['publicKeyJwk']['x'])->toBe($expected)
        ->and($did['verificationMethod'][0]['publicKeyJwk']['crv'])->toBe('Ed25519');

    $jwks = $this->get(route('attestation.jwks'))->assertOk()->json();
    expect($jwks['keys'][0]['x'])->toBe($expected);
});

it('permet de télécharger le Verifiable Credential signé en JSON', function () {
    enableAttestationCertification();
    $attestation = app(IssueAttestation::class)->handle(certParticipant(openEdition()), 'participation');

    $json = $this->get(route('attestation.credential', $attestation->uuid))->assertOk()->json();

    expect($json['format'])->toBe('vc+jwt')
        ->and($json['jws'])->toBe($attestation->credential)
        ->and($json['credential']['id'])->toBe('urn:uuid:'.$attestation->uuid);
});

it('renvoie 404 pour le VC JSON d’une attestation non signée', function () {
    config()->set('attestations.signing_key', '');
    $attestation = app(IssueAttestation::class)->handle(certParticipant(openEdition()), 'participation');

    $this->get(route('attestation.credential', $attestation->uuid))->assertNotFound();
});

it('publie un document DID absent (404) sans clé configurée', function () {
    config()->set('attestations.signing_key', '');

    $this->get(route('attestation.did'))->assertNotFound();
    $this->get(route('attestation.jwks'))->assertNotFound();
});
