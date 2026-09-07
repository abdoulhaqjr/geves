<?php

use App\Support\Orcid;

it('valide un ORCID correct et le normalise', function () {
    expect(Orcid::isValid('0000-0002-1825-0097'))->toBeTrue()
        ->and(Orcid::normalize('0000-0002-1825-0097'))->toBe('0000-0002-1825-0097');
});

it('accepte le chiffre de contrôle X', function () {
    expect(Orcid::isValid('0000-0002-1694-233X'))->toBeTrue();
});

it('normalise depuis une URL ou sans tirets', function () {
    expect(Orcid::normalize('https://orcid.org/0000-0002-1825-0097'))->toBe('0000-0002-1825-0097')
        ->and(Orcid::normalize('0000000218250097'))->toBe('0000-0002-1825-0097');
});

it('rejette un checksum ou un format invalide', function () {
    expect(Orcid::isValid('0000-0002-1825-0098'))->toBeFalse() // mauvais chiffre de contrôle
        ->and(Orcid::isValid('1234'))->toBeFalse()
        ->and(Orcid::normalize('pas-un-orcid'))->toBeNull();
});

it('construit un lien vérifiable', function () {
    expect(Orcid::url('0000-0002-1825-0097'))->toBe('https://orcid.org/0000-0002-1825-0097')
        ->and(Orcid::url('invalide'))->toBeNull();
});
