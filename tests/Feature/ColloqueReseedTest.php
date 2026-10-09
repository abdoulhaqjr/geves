<?php

use App\Models\Event;
use App\Models\Participant;
use App\Models\Setting;
use App\Models\User;
use App\Support\Settings;
use Database\Seeders\ColloqueAvenirSeeder;
use Database\Seeders\RolesAndAdminSeeder;

/**
 * `colloques:reseed` recrée les données de colloque SANS réinitialiser la base
 * du super-administrateur : réglages, rôles et compte super-admin survivent.
 *
 * ⚠️ On rejoue le seeder LÉGER (ColloqueAvenirSeeder, via --class) et non le
 * groupe complet : le volume (200 communications) rendrait ce test interminable.
 */
it('recrée les colloques sans toucher aux réglages ni au super-administrateur', function () {
    $this->seed(RolesAndAdminSeeder::class);
    $this->seed(ColloqueAvenirSeeder::class);

    // Un réglage et une personnalisation du super-admin posés « à la main ».
    Settings::set('app_name', 'GEVES Kara');
    $admin = User::where('email', 'admin@univkara.tg')->firstOrFail();
    $admin->forceFill(['first_name' => 'Patron'])->save();

    expect(Event::count())->toBeGreaterThan(0);
    expect(User::where('email', 'like', '%@test.tg')->count())->toBeGreaterThan(0);

    $this->artisan('colloques:reseed', [
        '--force' => true,
        '--class' => ColloqueAvenirSeeder::class,
    ])->assertExitCode(0);

    // La BASE est intacte.
    expect(Settings::get('app_name'))->toBe('GEVES Kara');
    expect(Setting::where('key', 'app_name')->exists())->toBeTrue();
    $admin->refresh();
    expect($admin->first_name)->toBe('Patron');
    expect($admin->hasRole('administrator'))->toBeTrue();

    // Les données de colloque sont recréées (pas en double).
    expect(Event::whereHas('series', fn ($q) => $q->where('slug', 'parakou-kara'))->count())->toBe(1);
    expect(Participant::where('role', 'moderator')->count())->toBeGreaterThan(0);
});

it('purge les comptes de démonstration mais garde le super-administrateur', function () {
    $this->seed(RolesAndAdminSeeder::class);
    $this->seed(ColloqueAvenirSeeder::class);

    // Un vrai compte (hors domaines de démo) ne doit pas être purgé.
    $vrai = User::create([
        'name' => 'Vrai Gestionnaire', 'first_name' => 'Vrai', 'last_name' => 'Gestionnaire',
        'email' => 'gestionnaire@univkara.tg', 'password' => 'secret-pass-123', 'phone' => '+22899887766',
    ]);

    $this->artisan('colloques:reseed', [
        '--force' => true,
        '--class' => ColloqueAvenirSeeder::class,
    ])->assertExitCode(0);

    expect(User::where('email', 'admin@univkara.tg')->exists())->toBeTrue();
    expect(User::whereKey($vrai->id)->exists())->toBeTrue();
});

it('conserve les comptes de démonstration avec --keep-accounts', function () {
    $this->seed(RolesAndAdminSeeder::class);
    $this->seed(ColloqueAvenirSeeder::class);

    $this->artisan('colloques:reseed', [
        '--force' => true,
        '--keep-accounts' => true,
        '--class' => ColloqueAvenirSeeder::class,
    ])->assertExitCode(0);

    // Rejoué à l'identique : pas de doublon de compte (updateOrCreate par e-mail).
    expect(User::where('email', 'moderateur1@test.tg')->count())->toBe(1);
});
