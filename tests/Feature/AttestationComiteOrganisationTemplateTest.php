<?php

use App\Documents\LayoutSanitizer;
use App\Models\Attestation;
use App\Models\User;

/**
 * Attestation du COMITÉ D'ORGANISATION (membres du comité, pendant de l'attestation
 * d'appui « organisation »). Modèle officiel UK x Parakou ; bénéficiaires = les
 * membres de l'équipe portant le rôle `comite_organisation`.
 */

// openEdition()/adminStaff() -> tests/Pest.php

it('a un modèle par défaut propre au type « comité d’organisation »', function () {
    $tpl = LayoutSanitizer::default('attestation', 'comite_organisation');

    expect($tpl['bg_image'])->toBe('/img/attestations/attestation-communication-bg.png');

    $texts = collect($tpl['elements'])->pluck('text')->implode(' | ');
    expect($texts)
        ->toContain('ATTESTATION DU COMITÉ D’ORGANISATION')
        ->toContain('pour avoir été membre du comité d’organisation')
        ->toContain('[recipient.civility] [recipient.name]')
        ->toContain('[edition.name]')
        ->not->toContain('[communication.title]');

    // Titre à la taille standard (28 px) : il tient dans la boîte de 660 px.
    $title = collect($tpl['elements'])->firstWhere('text', 'ATTESTATION DU COMITÉ D’ORGANISATION');
    expect($title['font'])->toBe('times');
    expect($title['size'])->toBe(28);
});

it('émet l’attestation aux membres du comité d’organisation (appui exclu)', function () {
    $staff = adminStaff();
    $event = openEdition();

    $mk = function (string $slug) {
        $n = substr(md5($slug), 0, 6);

        return User::create([
            'name' => $slug, 'first_name' => 'T', 'last_name' => $slug,
            'email' => $slug.'@uk.tg', 'password' => 'pass12345',
            'phone' => '+22890'.substr(preg_replace('/\D/', '', $n).'000000', 0, 6),
        ]);
    };

    $orga = $mk('orga');     // comité d'organisation -> INCLUS
    $mixte = $mk('mixte');   // appui + comité d'organisation -> INCLUS (porte le rôle)
    $appui = $mk('appui');   // appui seul -> EXCLU
    $sci = $mk('sci');       // comité scientifique -> EXCLU

    $event->team()->attach($orga->id, ['role' => 'comite_organisation']);
    $event->team()->attach($mixte->id, ['role' => 'support']);
    $event->team()->attach($mixte->id, ['role' => 'comite_organisation']);
    $event->team()->attach($appui->id, ['role' => 'support']);
    $event->team()->attach($sci->id, ['role' => 'scientific']);

    foreach ([$orga, $mixte, $appui, $sci] as $u) {
        $event->participants()->create([
            'user_id' => $u->id, 'first_name' => 'T', 'last_name' => $u->last_name,
            'email' => $u->email, 'role' => 'staff',
        ]);
    }

    $this->actingAs($staff, 'web')->post(route('admin.documents.attestations.issue'), [
        'event_id' => (string) $event->id, 'type' => 'comite_organisation',
    ]);

    $emails = Attestation::where('event_id', $event->id)->where('type', 'comite_organisation')
        ->where('revoked', false)->pluck('recipient_email')->sort()->values()->all();

    expect($emails)->toBe(['mixte@uk.tg', 'orga@uk.tg']);
});
