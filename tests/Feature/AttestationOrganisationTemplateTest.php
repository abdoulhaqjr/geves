<?php

use App\Documents\LayoutSanitizer;
use App\Models\Attestation;
use App\Models\User;

/**
 * Attestation de PARTICIPATION À L'ORGANISATION (soutien au comité d'organisation).
 *
 * Nouveau type : modèle officiel UK x Parakou (titre long ramené à Times 24 px) et
 * bénéficiaires = toute l'équipe SAUF les trois comités (organisation, scientifique,
 * supervision).
 */

// openEdition()/adminStaff() -> tests/Pest.php

it('a un modèle par défaut propre au type « organisation »', function () {
    $org = LayoutSanitizer::default('attestation', 'organisation');

    expect($org['bg_image'])->toBe('/img/attestations/attestation-communication-bg.png');

    $texts = collect($org['elements'])->pluck('text')->implode(' | ');
    expect($texts)
        ->toContain('ATTESTATION DE PARTICIPATION À L’ORGANISATION')
        ->toContain('pour avoir travaillé en appui au comité d’organisation')
        ->toContain('[recipient.civility] [recipient.name]')
        ->toContain('[edition.name]')
        ->toContain('Thème du colloque :')
        ->not->toContain('[communication.title]');

    // Titre long : ramené à 24 px (Times) pour tenir sur une ligne (660 px de boîte).
    $title = collect($org['elements'])->firstWhere('text', 'ATTESTATION DE PARTICIPATION À L’ORGANISATION');
    expect($title['font'])->toBe('times');
    expect($title['size'])->toBe(24);

    $images = collect($org['elements'])->where('type', 'image')->pluck('src');
    expect($images)->toHaveCount(2);
    $qr = collect($org['elements'])->firstWhere('type', 'qr');
    expect($qr)->not->toBeNull();
});

it('émet l’attestation d’organisation à l’équipe SAUF les trois comités', function () {
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

    $appui = $mk('appui');          // rôle d'appui -> INCLUS
    $orga = $mk('orga');            // comité d'organisation -> EXCLU
    $sci = $mk('sci');              // comité scientifique -> EXCLU
    $mixte = $mk('mixte');          // appui + comité d'organisation -> EXCLU (membre du comité)
    $gerant = $mk('gerant');        // gestionnaire (hors comités) -> INCLUS

    $event->team()->attach($appui->id, ['role' => 'support']);
    $event->team()->attach($orga->id, ['role' => 'comite_organisation']);
    $event->team()->attach($sci->id, ['role' => 'scientific']);
    $event->team()->attach($mixte->id, ['role' => 'support']);
    $event->team()->attach($mixte->id, ['role' => 'comite_organisation']);
    $event->team()->attach($gerant->id, ['role' => 'gerant']);

    foreach ([$appui, $orga, $sci, $mixte, $gerant] as $u) {
        $event->participants()->create([
            'user_id' => $u->id, 'first_name' => 'T', 'last_name' => $u->last_name,
            'email' => $u->email, 'role' => 'staff',
        ]);
    }
    // Un participant ordinaire sans rôle d'équipe : jamais concerné.
    $event->participants()->create(['first_name' => 'Simple', 'last_name' => 'Inscrit', 'email' => 'simple@uk.tg', 'role' => 'author']);

    $this->actingAs($staff, 'web')->post(route('admin.documents.attestations.issue'), [
        'event_id' => (string) $event->id, 'type' => 'organisation',
    ]);

    $atts = Attestation::where('event_id', $event->id)->where('type', 'organisation')->where('revoked', false)->get();
    $emails = $atts->pluck('recipient_email')->sort()->values()->all();

    expect($emails)->toBe(['appui@uk.tg', 'gerant@uk.tg']);
});
