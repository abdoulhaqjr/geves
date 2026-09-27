<?php

use App\Support\ParticipantTypes;
use App\Support\Settings;

// adminStaff() -> tests/Pest.php

it('propose par défaut les grades académiques usuels', function () {
    foreach (['Professeur', 'Maître de conférences', 'Maître-assistant', 'Enseignant / Chercheur',
        'Enseignant', 'Doctorant', 'Étudiant en master', 'Autre participant'] as $grade) {
        expect(ParticipantTypes::all())->toContain($grade);
    }
});

it('laisse l’institution définir sa propre liste depuis les Réglages', function () {
    Settings::set('participant_types', "Professeur titulaire\nAssistant de recherche\nAuditeur libre");

    expect(ParticipantTypes::all())->toBe(['Professeur titulaire', 'Assistant de recherche', 'Auditeur libre']);

    // Le formulaire d'inscription suit la liste configurée.
    $this->get(route('account.register'))->assertOk()->assertSee('Auditeur libre');
});

it('ignore les lignes vides et les doublons, et ne rend jamais une liste vide', function () {
    Settings::set('participant_types', "Doctorant\n\n  Doctorant  \n\nEnseignant\n");
    expect(ParticipantTypes::all())->toBe(['Doctorant', 'Enseignant']);

    // Une liste vidée par erreur bloquerait l'inscription : on revient au défaut.
    Settings::set('participant_types', "\n  \n");
    expect(ParticipantTypes::all())->toBe(ParticipantTypes::DEFAULTS);
});

it('enregistre la liste depuis la page Réglages', function () {
    $this->actingAs(adminStaff(), 'web')
        ->post(route('admin.settings.update'), [
            'participant_types' => "Professeur\nDoctorant",
            'payments_default' => 'manual', 'fedapay_mode' => 'test', 'ai_default' => 'openai',
            'meeting_default' => 'jitsi', 'sms_default' => 'log',
        ])
        ->assertRedirect();

    expect(ParticipantTypes::all())->toBe(['Professeur', 'Doctorant']);
});
