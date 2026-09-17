<?php

use App\Actions\AskGesco;
use App\AI\Contracts\AiProvider;

// fakeAiProvider(), bindFakeAi() -> GenerateReportTest

it('répond à une question via l’agent Gesco', function () {
    bindFakeAi(fakeAiProvider(['answer' => 'Pour soumettre, connectez-vous à votre espace participant.']));

    $result = app(AskGesco::class)->handle([], 'Comment soumettre une communication ?');

    expect($result['answer'])->toContain('espace participant');
});

it('affiche la page dédiée de l’assistante Gesco', function () {
    $this->get(route('public.gesco'))
        ->assertOk()
        ->assertSee('Gesco');
});

it('répond dans le chat via l’endpoint JSON', function () {
    bindFakeAi(fakeAiProvider(['answer' => 'Les tarifs dépendent de votre catégorie.']));

    $this->postJson(route('gesco.ask'), ['question' => 'Quels sont les tarifs ?'])
        ->assertOk()
        ->assertJsonPath('answer', 'Les tarifs dépendent de votre catégorie.');
});

it('conserve l’historique de conversation', function () {
    bindFakeAi(fakeAiProvider(['answer' => 'Le programme est publié sur la page du colloque.']));

    $this->postJson(route('gesco.ask'), [
        'question' => 'Où est le programme ?',
        'history' => [
            ['role' => 'bot', 'text' => 'Bonjour'],
            ['role' => 'user', 'text' => 'Salut'],
        ],
    ])
        ->assertOk()
        ->assertJsonPath('answer', 'Le programme est publié sur la page du colloque.');
});

it('refuse un message vide', function () {
    $this->postJson(route('gesco.ask'), ['question' => ''])
        ->assertStatus(422);
});

it('gère proprement l’absence de fournisseur IA', function () {
    bindFakeAi(null);

    $this->postJson(route('gesco.ask'), ['question' => 'Bonjour'])
        ->assertOk()
        ->assertJsonFragment(['answer' => 'L’assistance Gesco est momentanément indisponible.']);
});

it('injecte les données personnelles du participant connecté dans le prompt', function () {
    // Fournisseur qui capture l'instruction reçue (le prompt).
    $captured = null;
    $provider = new class($captured) implements AiProvider
    {
        public function __construct(public ?string &$captured) {}

        public function key(): string
        {
            return 'fake';
        }

        public function label(): string
        {
            return 'Fake';
        }

        public function isAvailable(): bool
        {
            return true;
        }

        public function supportsPdf(): bool
        {
            return true;
        }

        public function extract(string $instruction, array $files = []): array
        {
            $this->captured = $instruction;

            return ['answer' => 'Voici l’état de votre soumission.'];
        }
    };
    bindFakeAi($provider);

    $edition = openEdition();
    $account = verifiedAccount();
    $participant = $edition->participants()->create([
        'user_id' => $account->id, 'first_name' => 'Awa', 'last_name' => 'Kodjo', 'email' => $account->email,
    ]);
    $edition->submissions()->create([
        'participant_id' => $participant->id, 'ref_code' => '26-XYZ', 'title' => 'Ma communication test',
        'status' => 'accepted', 'revision_of' => 0,
    ]);

    $this->actingAs($account, 'account')
        ->postJson(route('gesco.ask'), ['question' => 'Où en est ma soumission ?'])
        ->assertOk();

    expect($provider->captured)
        ->toContain('DONNÉES PERSONNELLES')
        ->toContain('Ma communication test')
        ->toContain('26-XYZ');
});

it('injecte la base de connaissance factuelle (tarifs, FAQ) dans le prompt', function () {
    $captured = null;
    $provider = new class($captured) implements AiProvider
    {
        public function __construct(public ?string &$captured) {}

        public function key(): string
        {
            return 'fake';
        }

        public function label(): string
        {
            return 'Fake';
        }

        public function isAvailable(): bool
        {
            return true;
        }

        public function supportsPdf(): bool
        {
            return true;
        }

        public function extract(string $instruction, array $files = []): array
        {
            $this->captured = $instruction;

            return ['answer' => 'Le tarif doctorant est de 15 000 FCFA.'];
        }
    };
    bindFakeAi($provider);

    openEdition([
        'fees' => [
            ['label' => 'Doctorant', 'amount' => 15000],
            ['label' => 'Enseignant-chercheur', 'amount' => 25000],
        ],
        'faq' => [
            ['question' => 'Quels moyens de paiement ?', 'answer' => 'FedaPay (Mobile Money) ou sur place.'],
        ],
    ]);

    $this->postJson(route('gesco.ask'), ['question' => 'Quels sont les tarifs ?'])->assertOk();

    expect($provider->captured)
        ->toContain('BASE DE CONNAISSANCE')
        ->toContain('TARIFS')
        ->toContain('15 000 FCFA')
        ->toContain('Enseignant-chercheur')
        ->toContain('FOIRE AUX QUESTIONS')
        ->toContain('Quels moyens de paiement');
});

it('ne fuite aucune donnée personnelle pour un visiteur anonyme', function () {
    $captured = null;
    $provider = new class($captured) implements AiProvider
    {
        public function __construct(public ?string &$captured) {}

        public function key(): string
        {
            return 'fake';
        }

        public function label(): string
        {
            return 'Fake';
        }

        public function isAvailable(): bool
        {
            return true;
        }

        public function supportsPdf(): bool
        {
            return true;
        }

        public function extract(string $instruction, array $files = []): array
        {
            $this->captured = $instruction;

            return ['answer' => 'Connectez-vous à votre espace.'];
        }
    };
    bindFakeAi($provider);

    $this->postJson(route('gesco.ask'), ['question' => 'Où en est ma soumission ?'])->assertOk();

    expect($provider->captured)
        ->not->toContain('DONNÉES PERSONNELLES')
        ->toContain('pas connecté');
});
