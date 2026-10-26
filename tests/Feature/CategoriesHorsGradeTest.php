<?php

use App\Models\Event;
use App\Models\Participant;
use App\Models\User;
use App\Support\Fees;
use Illuminate\Support\Str;

/*
|--------------------------------------------------------------------------
| Catégories tarifaires HORS GRADE (ONG, entreprise, association…)
|--------------------------------------------------------------------------
|
| Règle du projet : « le tarif ne se choisit pas, il se déduit du grade ».
| Elle tient toujours - SAUF pour ces catégories-là, qu'aucun grade ne désigne
| et que le participant déclare donc lui-même. C'est la seule exception, et
| tout ce qui suit sert à la borner.
|
*/

/** Un événement avec deux catégories liées au grade et deux hors grade. */
function evenementAvecHorsGrade(): Event
{
    $event = openEdition();
    $settings = $event->settings ?? [];
    $settings['fees'] = [
        ['key' => 'doctorants', 'label' => 'Doctorants', 'amount' => 10000, 'grades' => ['Doctorant'], 'default' => false],
        ['key' => 'autres', 'label' => 'Autres participants', 'amount' => 50000, 'grades' => [], 'default' => true],
        ['key' => 'ong', 'label' => 'ONG & associations', 'amount' => 75000, 'selectable' => true],
        ['key' => 'entreprises', 'label' => 'Entreprises', 'amount' => 150000, 'selectable' => true],
    ];
    $event->settings = $settings;
    $event->save();

    return $event->fresh();
}

function participantAvec(Event $event, ?string $grade, ?string $categorie = null): Participant
{
    // ⚠️ Pas `verifiedAccount()` : son téléphone est FIXE et `users.phone` est
    // unique - deux participants dans le même cas tomberaient sur la contrainte.
    $account = User::factory()->create(['phone' => null]);
    $account->markEmailAsVerified();
    $p = $event->participants()->create([
        'user_id' => $account->id, 'first_name' => 'Awa', 'last_name' => 'Kodjo',
        'email' => $account->email, 'title' => $grade, 'role' => 'attendee',
    ]);
    if ($categorie !== null) {
        $p->setFeeCategory($categorie);
        $p->save();
    }

    return $p->fresh();
}

it('ne rattache une catégorie hors grade à AUCUN grade, ni au défaut', function () {
    // ⚠️ On le force à la normalisation plutôt que de s'en remettre au
    // formulaire : les deux se contrediraient un jour, et la cascade
    // automatique appliquerait « ONG » à quelqu'un qui ne l'a jamais déclaré.
    $event = evenementAvecHorsGrade();
    $settings = $event->settings;
    $settings['fees'][2]['grades'] = ['Doctorant'];   // un gestionnaire coche quand même
    $settings['fees'][2]['default'] = true;
    $event->settings = $settings;
    $event->save();

    $ong = collect(Fees::grid($event->fresh()))->firstWhere('key', 'ong');

    expect($ong['grades'])->toBe([])
        ->and($ong['default'])->toBeFalse()
        ->and($ong['selectable'])->toBeTrue();
});

it('ne l’applique JAMAIS par déduction', function () {
    $event = evenementAvecHorsGrade();

    // Un grade couvert garde son tarif ; un grade inconnu tombe sur le défaut.
    expect(Fees::resolveAmount($event, 'Doctorant'))->toBe(10000.0)
        ->and(Fees::resolveAmount($event, 'Grade inconnu'))->toBe(50000.0)
        // Même en demandant le tarif par le LIBELLÉ de la catégorie hors grade.
        ->and(Fees::resolveAmount($event, 'Entreprises'))->toBe(50000.0);
});

it('exclut les catégories hors grade du repli « montant le plus élevé »', function () {
    // ⚠️⚠️ Ce repli frappe qui n'a rien déclaré et dont le grade n'est couvert
    // par rien : lui appliquer le tarif « Entreprises » parce qu'il se trouve
    // être le plus cher facturerait un tarif d'entreprise à un doctorant.
    $event = evenementAvecHorsGrade();

    expect(Fees::highestAmount($event))->toBe(50000.0);
});

it('fait passer la catégorie déclarée DEVANT le grade', function () {
    $event = evenementAvecHorsGrade();
    $sansRien = participantAvec($event, 'Doctorant');
    $declare = participantAvec($event, 'Doctorant', 'entreprises');

    expect(Fees::forParticipant($sansRien))->toBe(10000.0)
        ->and(Fees::forParticipant($declare))->toBe(150000.0)
        ->and(Fees::labelForParticipant($declare))->toBe('Entreprises')
        ->and(Fees::labelForParticipant($sansRien))->toBe('Doctorants');
});

it('retombe sur le grade quand la catégorie disparaît de la grille', function () {
    // Plutôt que de facturer un montant que la grille n'annonce plus. Le choix
    // reste enregistré : l'organisation peut rétablir la catégorie.
    $event = evenementAvecHorsGrade();
    $p = participantAvec($event, 'Doctorant', 'entreprises');

    $settings = $event->settings;
    unset($settings['fees'][3]);
    $settings['fees'] = array_values($settings['fees']);
    $event->settings = $settings;
    $event->save();

    $p = $p->fresh();
    expect(Fees::forParticipant($p))->toBe(10000.0)
        ->and($p->feeCategoryKey())->toBe('entreprises');
});

it('rattache le choix à la CLÉ, pas au libellé', function () {
    // ⚠️⚠️ Rapproché sur le libellé, renommer « Entreprises » ferait
    // silencieusement retomber tous ceux qui l'avaient déclarée sur leur grade,
    // donc sur un autre montant. (Même leçon que les axes d'expertise.)
    $event = evenementAvecHorsGrade();
    $p = participantAvec($event, 'Doctorant', 'entreprises');

    $settings = $event->settings;
    $settings['fees'][3]['label'] = 'Entreprises & industries';
    $event->settings = $settings;
    $event->save();

    expect(Fees::forParticipant($p->fresh()))->toBe(150000.0);
});

it('laisse le participant déclarer sa catégorie, et la retirer', function () {
    $event = evenementAvecHorsGrade();
    $p = participantAvec($event, 'Doctorant');
    $account = $p->user;

    $this->actingAs($account, 'account')
        ->put(route('account.participation.fee-category.update', $event), ['fee_category' => 'ong'])
        ->assertRedirect();

    expect(Fees::forParticipant($p->fresh()))->toBe(75000.0);

    // La chaîne vide est un choix : « aucune, appliquez mon grade ».
    $this->actingAs($account, 'account')
        ->put(route('account.participation.fee-category.update', $event), ['fee_category' => ''])
        ->assertRedirect();

    expect($p->fresh()->feeCategoryKey())->toBeNull()
        ->and(Fees::forParticipant($p->fresh()))->toBe(10000.0);
});

it('refuse une catégorie qui n’est pas proposée', function () {
    // ⚠️ On ne choisit pas parmi TOUTE la grille : « Doctorants » est déduite
    // du grade, la déclarer reviendrait à choisir son tarif.
    $event = evenementAvecHorsGrade();
    $p = participantAvec($event, 'Autre');

    $this->actingAs($p->user, 'account')
        ->put(route('account.participation.fee-category.update', $event), ['fee_category' => 'doctorants'])
        ->assertSessionHasErrors('fee_category');

    expect($p->fresh()->feeCategoryKey())->toBeNull();
});

it('ferme la déclaration une fois les frais réglés', function () {
    // Le tarif est acquis : le laisser changer ferait diverger la somme
    // encaissée et la catégorie affichée sur le reçu.
    $event = evenementAvecHorsGrade();
    $p = participantAvec($event, 'Doctorant');
    $p->payments()->create([
        'event_id' => $event->id, 'amount' => 10000, 'currency' => 'XOF',
        'status' => 'paid', 'method' => 'manual', 'gateway' => 'manual',
    ]);

    $this->actingAs($p->user, 'account')
        ->put(route('account.participation.fee-category.update', $event), ['fee_category' => 'ong'])
        ->assertForbidden();
});

it('propose les catégories au participant, et les annonce au public', function () {
    $event = evenementAvecHorsGrade();
    $p = participantAvec($event, 'Doctorant');

    $this->actingAs($p->user, 'account')->get(route('account.payment', $event))
        ->assertOk()
        ->assertSee('Catégorie tarifaire')
        ->assertSee('ONG &amp; associations', false)
        ->assertSee('Entreprises');

    // ⚠️ Côté public, la ligne porte son intitulé et son montant, et ne liste
    // AUCUN grade - l'écrire serait faux. Pas d'appel à l'action non plus :
    // cette page renseigne, elle ne vend pas.
    $html = (string) $this->get(route('public.colloque', $event->slug))
        ->assertOk()
        ->assertSee('ONG &amp; associations', false)
        ->assertSee('75 000')
        ->getContent();

    // La ligne hors grade ne porte pas de bloc « grades concernés ».
    $ligne = Str::between($html, 'ONG &amp; associations', '</li>');
    expect($ligne)->not->toContain('Grades concernés');
});

it('n’affiche rien quand l’événement n’a aucune catégorie hors grade', function () {
    // La règle générale reste « le tarif se déduit du grade » : pas de case à
    // cocher sur un événement qui n'en a pas décidé autrement.
    $event = openEdition();
    $settings = $event->settings ?? [];
    $settings['fees'] = [['label' => 'Tous', 'amount' => 20000, 'grades' => [], 'default' => true]];
    $event->settings = $settings;
    $event->save();

    $p = participantAvec($event->fresh(), 'Doctorant');

    expect(Fees::selectable($event->fresh()))->toBe([]);

    $this->actingAs($p->user, 'account')->get(route('account.payment', $event))
        ->assertOk()
        ->assertDontSee('Catégorie tarifaire');
});

it('résout le montant par UN SEUL point d’entrée', function () {
    // ⚠️⚠️ Le montant AFFICHÉ et le montant FACTURÉ doivent sortir du même
    // calcul. Un appelant resté sur le seul grade afficherait un tarif que le
    // règlement ne reprendrait pas - et personne ne le verrait avant la caisse.
    $fautifs = [];
    foreach (['app'] as $racine) {
        $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(base_path($racine)));
        foreach ($it as $f) {
            if (! $f->isFile() || $f->getExtension() !== 'php') {
                continue;
            }
            $code = (string) file_get_contents($f->getPathname());
            if (str_contains($code, 'resolveAmount(') && str_contains($code, '->title')
                && ! str_contains($f->getPathname(), 'Fees.php')) {
                $fautifs[] = str_replace(base_path().DIRECTORY_SEPARATOR, '', $f->getPathname());
            }
        }
    }

    expect($fautifs)->toBe([]);
});
