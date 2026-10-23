<?php

use App\AI\AiManager;
use App\AI\Contracts\AiProvider;
use App\AI\Exceptions\AiUnavailableException;
use App\Models\Account;
use App\Models\Event;
use App\Models\EventSeries;
use App\Models\EventSession;
use App\Models\Formation;
use App\Models\Participant;
use App\Models\Submission;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "pest()" function to bind different classes or traits.
|
*/

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Feature');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

/*
|--------------------------------------------------------------------------
| Fabriques de test partagées
|--------------------------------------------------------------------------
|
| Helpers réutilisés par plusieurs suites (soumissions, back-office, smoke
| tests). Définis ici pour être globaux quel que soit l'ordre de chargement
| des fichiers de test (et donc utilisables même en exécution isolée).
|
*/

function openEdition(array $settings = []): Event
{
    $series = EventSeries::create(['name' => 'EventSeries UK', 'slug' => 'uk']);

    return $series->events()->create([
        'title' => 'UK 2026',
        'event_label' => '3e événement',
        'year' => 2026,
        'status' => 'open',
        'slug' => 'uk-2026',
        'submission_open' => true,
        'settings' => array_merge(['tracks' => ['Sciences', 'Santé'], 'max_communications' => 3], $settings),
    ]);
}

/** Formation proposée dans un événement dont le module est activé. */
function publishedFormation(Event $event, float $fee): Formation
{
    $event->update(['settings' => array_merge($event->settings ?? [], ['formation_enabled' => true])]);

    return Formation::create([
        'event_id' => $event->id,
        'title' => $fee > 0 ? 'Formation payante' : 'Formation gratuite',
        'fee' => $fee,
        'published' => true,
    ]);
}

/** Fournisseur IA factice partagé, disponible aussi quand une suite est lancée seule. */
function fakeAiProvider(array $result): AiProvider
{
    return new class($result) implements AiProvider
    {
        public function __construct(private array $result) {}

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

        public function extract(string $instruction, array $files = [], ?string $tier = null): array
        {
            return $this->result;
        }
    };
}

function bindFakeAi(?AiProvider $provider): void
{
    $manager = Mockery::mock(AiManager::class);
    $manager->shouldReceive('resolve')->andReturn($provider);

    if ($provider === null) {
        $manager->shouldReceive('extract')->andThrow(new AiUnavailableException('Aucun assistant IA n’est configuré (voir Réglages).'));
    } else {
        $manager->shouldReceive('extract')->andReturnUsing(
            fn (string $instruction, array $files = [], ?string $tier = null, ?string $preferKey = null) => $provider->extract($instruction, $files, $tier)
        );
    }

    app()->instance(AiManager::class, $manager);
}

/**
 * Identité participant vérifiée (refonte « compte unifié » : c'est un User,
 * connecté via le guard « account » qui résout désormais la table `users`).
 */
function verifiedAccount(): User
{
    $user = User::create([
        'name' => 'Awa Kodjo',
        'first_name' => 'Awa', 'last_name' => 'Kodjo',
        'email' => 'awa@example.tg', 'password' => 'pass12345',
        'grade' => 'Doctorante', 'affiliation' => 'Université de Kara',
        // Profil complet (cf. User::profileComplete) : requis pour payer.
        'phone' => '+22890000000',
    ]);
    $user->markEmailAsVerified();

    return $user;
}

function adminStaff(): User
{
    Permission::firstOrCreate(['name' => 'manage_colloques', 'guard_name' => 'web']);
    app(PermissionRegistrar::class)->forgetCachedPermissions();

    $user = User::factory()->create();
    $user->givePermissionTo('manage_colloques');

    return $user;
}

function seededSubmission(string $status = 'submitted'): Submission
{
    $event = openEdition();
    $account = verifiedAccount();
    $participant = $event->participants()->create([
        'user_id' => $account->id, 'first_name' => 'Awa', 'last_name' => 'Kodjo', 'email' => $account->email,
    ]);

    return $event->submissions()->create([
        'participant_id' => $participant->id,
        'ref_code' => '26-001',
        'title' => 'Innovation durable',
        'abstract' => str_repeat('Contexte. ', 10),
        'track' => 'Sciences',
        'type' => 'oral',
        'status' => $status,
        'authors' => [['name' => 'Awa Kodjo', 'email' => 'awa@example.tg', 'corresponding' => true]],
    ]);
}

function sessionFor(Event $event): EventSession
{
    return EventSession::create([
        'event_id' => $event->id, 'label' => 'Séance plénière',
        'session_day' => '2026-09-03', 'starts_at' => '2026-09-03 09:00', 'ends_at' => '2026-09-03 11:00',
    ]);
}

function participantAccount(Event $event): User
{
    $account = verifiedAccount();
    Participant::create([
        'event_id' => $event->id, 'user_id' => $account->id,
        'first_name' => 'Awa', 'last_name' => 'Kodjo', 'email' => $account->email, 'role' => 'author',
    ]);

    return $account;
}
