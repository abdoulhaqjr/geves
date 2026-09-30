<?php

// Helpers globaux : adminStaff() / openEdition() / verifiedAccount() / seededSubmission()
// sont définis dans d'autres fichiers de test et chargés pour toute la suite.

/**
 * Tests de fumée : chaque route clé doit rendre sans erreur (200 / statut attendu).
 * Verrou de non-régression contre les 500 introduits par un changement de vue/contrôleur.
 */
it('les pages publiques répondent sans erreur', function () {
    $edition = openEdition(); // édition ouverte → page colloque publiée

    $routes = [
        route('home'),
        route('public.archives'),
        route('public.deroulement'),
        route('public.gesco'),
        route('attestation.verify.landing'),
        route('public.colloque', $edition->slug),
    ];

    foreach ($routes as $url) {
        $this->get($url)->assertOk();
    }
});

it('les pages du back-office répondent sans erreur (staff)', function () {
    $staff = adminStaff();
    openEdition(); // au moins une édition pour les écrans qui en dépendent

    $this->actingAs($staff, 'web');

    $routes = [
        route('admin.dashboard'),
        route('admin.submissions.index'),
        route('admin.submissions.auto-assign'),
        route('admin.submissions.export'),
        route('admin.payments.index'),
        route('admin.payments.export'),
        route('admin.payments.create'),
        route('admin.documents.attestations'),
        route('admin.documents.templates'),
        route('admin.editions.index'),
        route('admin.editions.create'),
        route('admin.emails.index'),
        route('admin.reports.index'),
        route('admin.users.index'),
        route('admin.users.create'),
        route('admin.program.index'),
        route('admin.settings'),
        route('admin.ai'),
        route('admin.participants.index'),
    ];

    foreach ($routes as $url) {
        $this->get($url)->assertOk();
    }
});

it('les documents générés d’une édition répondent sans erreur (staff)', function () {
    $staff = adminStaff();
    $submission = seededSubmission('accepted'); // édition avec une communication acceptée
    $edition = $submission->edition;
    $edition->participants()->firstOrCreate(
        ['email' => 'badge@example.tg'],
        ['first_name' => 'B', 'last_name' => 'Adge', 'role' => 'author']
    );

    $this->actingAs($staff, 'web');

    foreach ([
        route('admin.documents.livret', $edition),
        route('admin.documents.livret', [$edition, 'format' => 'docx']),
        route('admin.documents.badges', $edition),
        route('admin.documents.sessions', $edition),
        route('admin.reports.xlsx', $edition),
        route('admin.reports.pdf', $edition),
        route('admin.editions.edit', $edition),
        route('admin.editions.form', $edition),
    ] as $url) {
        $this->get($url)->assertOk();
    }
});

it('les pages de l’espace participant répondent sans erreur (compte)', function () {
    $account = verifiedAccount();

    $this->actingAs($account, 'account');

    foreach ([
        route('account.dashboard'),
        route('account.submissions.index'),
        route('account.documents'),
        route('account.profile'),
        route('account.security'),
    ] as $url) {
        $this->get($url)->assertOk();
    }
});

it('la fiche et l’éditeur de modèles répondent sans erreur (staff)', function () {
    $staff = adminStaff();
    $submission = seededSubmission('under_review');

    $this->actingAs($staff, 'web');

    $this->get(route('admin.submissions.show', $submission))->assertOk();
    $this->get(route('admin.documents.templates', ['kind' => 'attestation', 'type' => 'participation']))->assertOk();
    $this->get(route('admin.documents.templates', ['kind' => 'badge']))->assertOk();
});
