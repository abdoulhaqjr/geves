<?php

use App\Models\User;

// fakeAiProvider(), bindFakeAi() -> GenerateReportTest ; adminStaff() -> helpers ; openEdition() -> tests/Pest.php

it('génère un document du secrétariat via IA et le rend téléchargeable', function () {
    bindFakeAi(fakeAiProvider([
        'title' => 'Liste de présence',
        'sections' => [['heading' => 'Participants', 'body' => 'Colonnes : Nom, Institution, Signature.']],
    ]));

    $event = openEdition();

    $response = $this->actingAs(adminStaff(), 'web')->post(route('admin.secretariat.assist'), [
        'event_id' => $event->id,
        'preset' => 'presence',
        'prompt' => 'Génère une liste de présence.',
        'format' => 'pdf',
        'dataset' => 'none',
    ]);
    $response->assertRedirect()->assertSessionHas('sec_doc');

    $doc = $response->getSession()->get('sec_doc');
    expect($doc['title'])->toBe('Liste de présence');

    $this->actingAs(adminStaff(), 'web')
        ->get(route('admin.secretariat.assist.download', [$event->id, $doc['id']]))
        ->assertOk();
});

it('refuse la génération sans la capacité documents sur l’événement', function () {
    $event = openEdition();
    $staff = User::create(['first_name' => 'Sans', 'last_name' => 'Accès', 'email' => 'sansacces@example.tg', 'password' => 'pass12345']);

    $this->actingAs($staff, 'web')
        ->post(route('admin.secretariat.assist'), ['event_id' => $event->id, 'prompt' => 'x'])
        ->assertForbidden();
});
