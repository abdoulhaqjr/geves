<?php

use App\Models\EventSeries;
use App\Models\User;
use App\Support\Money;
use App\Support\MontantEnLettres;

/**
 * ⚠️⚠️ **LE CODE ISO ET LE MOT NE SONT PAS LE MÊME OBJET.** La devise est `XOF` :
 * c'est ce que la norme exige, ce que la base stocke et ce que le prestataire
 * attend (`currency: {iso: XOF}`). Mais personne, ici, n'écrit « 100 XOF » - on
 * écrit **« 100 FCFA »**. Le code est pour les machines, le mot pour les lecteurs.
 */
it('écrit FCFA là où la base dit XOF', function () {
    expect(Money::label('XOF'))->toBe('FCFA');
    expect(Money::label('xof'))->toBe('FCFA');
    expect(Money::label('XAF'))->toBe('FCFA');

    // ⚠️ Une devise inconnue se rend TELLE QUELLE : mieux vaut « EUR » que rien
    // du tout sur un montant.
    expect(Money::label('EUR'))->toBe('EUR');

    // Sans argument, celle de la plateforme.
    config(['payments.currency' => 'XOF']);
    expect(Money::label())->toBe('FCFA');
    expect(Money::format(15000))->toBe('15 000 FCFA');
});

/**
 * ⚠️⚠️ **LA SOMME EN TOUTES LETTRES FIGE LE REÇU** : un chiffre se retouche d'un
 * trait de stylo, pas une ligne de mots. C'est pour cela que tout reçu officiel
 * francophone la porte - et c'est elle qui fait foi en cas de désaccord.
 *
 * ⚠️ Les accords sont la moitié du travail : « quatre-vingts » prend son `s`
 * seul mais le perd devant « mille », « cent » de même, et « mille » est
 * invariable quand « million » ne l'est pas.
 */
it('écrit les sommes en toutes lettres, accords compris', function (int $n, string $attendu) {
    expect(MontantEnLettres::nombre($n))->toBe($attendu);
})->with([
    [0, 'zéro'],
    [1, 'un'],
    [16, 'seize'],
    [17, 'dix-sept'],
    [18, 'dix-huit'],
    [77, 'soixante-dix-sept'],
    [97, 'quatre-vingt-dix-sept'],
    [21, 'vingt et un'],
    [71, 'soixante et onze'],
    [80, 'quatre-vingts'],
    [81, 'quatre-vingt-un'],
    [91, 'quatre-vingt-onze'],
    [100, 'cent'],
    [200, 'deux cents'],
    [201, 'deux cent un'],
    [1000, 'mille'],
    [1001, 'mille un'],
    [15000, 'quinze mille'],
    [35000, 'trente-cinq mille'],
    // ⚠️ « quatre-vingt mille » et « deux cent mille » : invariables devant mille.
    [80000, 'quatre-vingt mille'],
    [200000, 'deux cent mille'],
    [100000, 'cent mille'],
    [1000000, 'un million'],
    [2000000, 'deux millions'],
    // ⚠️ Mais « deux cents millions » : « million » est un nom, il n'ôte pas le `s`.
    [200000000, 'deux cents millions'],
]);

it('accorde le nom de la devise avec la somme', function () {
    expect(MontantEnLettres::avecDevise(100, 'XOF'))->toBe('cent francs CFA');
    expect(MontantEnLettres::avecDevise(1, 'XOF'))->toBe('un franc CFA');
    expect(MontantEnLettres::avecDevise(15000, 'XOF'))->toBe('quinze mille francs CFA');
});

/**
 * ⚠️ Le reçu est le document que le participant garde : il dit la somme deux
 * fois - en chiffres et en lettres - et jamais en code ISO.
 */
it('le reçu porte la devise écrite et la somme en toutes lettres', function () {
    $series = EventSeries::create(['name' => 'UK', 'slug' => 'uk-recu']);
    $event = $series->events()->create(['title' => 'UK 2026', 'year' => 2026, 'status' => 'open', 'slug' => 'uk-2026-recu']);
    $account = User::create(['first_name' => 'Awa', 'last_name' => 'Kodjo', 'email' => 'awa.recu@example.tg',
        'password' => 'pass12345', 'affiliation' => 'UK', 'grade' => 'Doctorant']);
    $account->markEmailAsVerified();
    $participant = $event->participants()->create(['user_id' => $account->id, 'first_name' => 'Awa',
        'last_name' => 'Kodjo', 'email' => $account->email, 'title' => 'Doctorant']);
    $payment = $event->payments()->create(['participant_id' => $participant->id, 'amount' => 15000,
        'currency' => 'XOF', 'method' => 'cash', 'gateway' => 'manual', 'status' => 'paid',
        'receipt_no' => 'REC-2026-0001', 'paid_at' => now()]);

    $page = $this->actingAs(adminStaff())->get(route('admin.payments.receipt', $payment));

    $page->assertOk();
    $page->assertSee('15 000 FCFA');
    $page->assertSee('quinze mille francs CFA');
    $page->assertDontSee('15 000 XOF');
});

/**
 * ⚠️⚠️ **LE PDF ET LA PAGE SONT LE MÊME DOCUMENT.** Ils ont vécu en deux fichiers
 * jumeaux et ils ont divergé - l'un portait la devise, l'autre non. L'enveloppe
 * PDF ne fait qu'inclure le reçu ; `pdf: true` ne change que l'embarquement du
 * logo (dompdf ne va chercher aucune image distante), jamais une ligne du texte.
 */
it('le PDF du reçu rend le même document que la page', function () {
    $vue = (string) file_get_contents(resource_path('views/pdf/receipt.blade.php'));

    expect($vue)->toContain("@include('receipts._document'");
    expect($vue)->toContain("'pdf' => true");

    // Et le document ne redessine pas un second reçu de son côté.
    expect(substr_count($vue, 'Reçu de paiement'))->toBe(0);
});
