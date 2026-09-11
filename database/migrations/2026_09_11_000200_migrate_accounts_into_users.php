<?php

use App\Models\Account;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Refonte comptes & rôles — Phase 1 (identité unifiée), migration de DONNÉES.
 *
 * Fusionne chaque `account` (participant) dans `users` :
 *  - e-mail déjà présent dans `users` (même personne staff + participant) →
 *    on complète les champs vides du user et on force la réinitialisation du
 *    mot de passe (`must_reset_password`), les deux hashes étant différents ;
 *  - sinon → on crée un nouvel utilisateur (niveau global « utilisateur »),
 *    en conservant son mot de passe et son profil.
 *
 * Puis on rebranche vers `users` : `participants.user_id`, questions/sondages
 * de session, et les notifications (morph `notifiable`). `accounts` et le guard
 * `account` restent en place jusqu'à la Phase 2. Réversible (down).
 */
return new class extends Migration
{
    /** Champs de profil recopiés tels quels d'`accounts` vers un nouvel `users`. */
    private array $profile = [
        'civility', 'first_name', 'last_name', 'grade', 'phone', 'affiliation',
        'orcid', 'orcid_verified_at', 'country', 'city', 'bio', 'photo_path',
        'meta', 'locale', 'notification_preferences',
        'two_factor_secret', 'two_factor_recovery_codes', 'two_factor_confirmed_at',
        'two_factor_method', 'two_factor_otp', 'two_factor_otp_expires_at',
    ];

    public function up(): void
    {
        if (! DB::getSchemaBuilder()->hasTable('accounts')) {
            return;
        }

        $map = []; // account_id => user_id

        DB::table('accounts')->orderBy('id')->each(function ($acc) use (&$map) {
            $existing = DB::table('users')->whereRaw('LOWER(email) = ?', [mb_strtolower($acc->email)])->first();

            if ($existing) {
                // Collision staff + participant : compléter les vides, forcer le reset.
                $fill = [];
                foreach ($this->profile as $col) {
                    if (($existing->{$col} ?? null) === null && ($acc->{$col} ?? null) !== null) {
                        $fill[$col] = $acc->{$col};
                    }
                }
                if (($existing->name ?? '') === '' || $existing->name === null) {
                    $fill['name'] = trim(($acc->first_name ?? '').' '.($acc->last_name ?? '')) ?: $acc->email;
                }
                $fill['must_reset_password'] = true;
                $fill['updated_at'] = now();
                DB::table('users')->where('id', $existing->id)->update($fill);
                $map[$acc->id] = $existing->id;

                return;
            }

            // Nouvel utilisateur créé à partir du compte participant.
            $row = [
                'name' => trim(($acc->first_name ?? '').' '.($acc->last_name ?? '')) ?: $acc->email,
                'email' => $acc->email,
                'password' => $acc->password,
                'email_verified_at' => $acc->email_verified_at,
                'is_active' => true,
                'merged_account_id' => $acc->id,
                'must_reset_password' => false,
                'created_at' => $acc->created_at ?? now(),
                'updated_at' => $acc->updated_at ?? now(),
            ];
            foreach ($this->profile as $col) {
                $row[$col] = $acc->{$col} ?? null;
            }

            $map[$acc->id] = DB::table('users')->insertGetId($row);
        });

        // Rebranchement des références participant → user.
        foreach ($map as $accountId => $userId) {
            DB::table('participants')->where('account_id', $accountId)->update(['user_id' => $userId]);
            foreach (['session_questions', 'session_polls'] as $t) {
                if (DB::getSchemaBuilder()->hasColumn($t, 'account_id')) {
                    DB::table($t)->where('account_id', $accountId)->update(['user_id' => $userId]);
                }
            }
            DB::table('notifications')
                ->where('notifiable_type', Account::class)
                ->where('notifiable_id', $accountId)
                ->update(['notifiable_type' => User::class, 'notifiable_id' => $userId]);
        }
    }

    public function down(): void
    {
        // Ré-adresser les notifications vers les comptes participant restaurés.
        foreach (DB::table('users')->whereNotNull('merged_account_id')->get(['id', 'merged_account_id']) as $u) {
            DB::table('notifications')
                ->where('notifiable_type', User::class)
                ->where('notifiable_id', $u->id)
                ->update(['notifiable_type' => Account::class, 'notifiable_id' => $u->merged_account_id]);
        }

        // Vider les user_id rebranchés (colonnes supprimées ensuite par le schéma).
        DB::table('participants')->whereNotNull('user_id')->update(['user_id' => null]);
        foreach (['session_questions', 'session_polls'] as $t) {
            if (DB::getSchemaBuilder()->hasColumn($t, 'user_id')) {
                DB::table($t)->whereNotNull('user_id')->update(['user_id' => null]);
            }
        }

        // Supprimer uniquement les utilisateurs créés à partir d'un compte
        // (les collisions pré-existantes gardent merged_account_id à null).
        DB::table('users')->whereNotNull('merged_account_id')->delete();
        DB::table('users')->where('must_reset_password', true)->update(['must_reset_password' => false]);
    }
};
