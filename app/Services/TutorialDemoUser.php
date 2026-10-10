<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;

/** Compte non humain, isolé des données réelles, utilisé uniquement pour les captures tutoriel. */
class TutorialDemoUser
{
    public function ensure(): User
    {
        $user = User::query()->firstOrNew(['email' => 'captures-tutoriels@geves.invalid']);
        $user->fill([
            'name' => 'Démonstration GEVES',
            'first_name' => 'Démonstration',
            'last_name' => 'GEVES',
            'is_active' => true,
            'access_level' => User::ACCESS_SUPER_ADMIN,
            'notes' => 'Compte technique réservé aux captures vidéo de prise en main.',
        ]);

        if (! $user->exists) {
            $user->password = Hash::make(Str::password(48));
        }

        $user->save();

        $role = Role::query()->where('name', 'administrator')->where('guard_name', 'web')->first();
        if ($role !== null && ! $user->hasRole($role)) {
            $user->assignRole($role);
        }

        return $user;
    }

    /** Identité non privilégiée pour filmer les parcours de l'espace participant. */
    public function ensureParticipant(): User
    {
        $user = User::query()->firstOrNew(['email' => 'captures-participant@geves.invalid']);
        $user->fill([
            'name' => 'Participant de démonstration',
            'first_name' => 'Participant',
            'last_name' => 'Démonstration',
            'affiliation' => 'Université de démonstration',
            'is_active' => true,
            'access_level' => User::ACCESS_USER,
            'notes' => 'Compte technique réservé aux captures des parcours participants.',
        ]);

        if (! $user->exists) {
            $user->password = Hash::make(Str::password(48));
            $user->email_verified_at = now();
        }

        $user->save();

        return $user;
    }
}
