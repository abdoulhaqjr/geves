<?php

use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * ⚠️⚠️ **DEUX COMPTES RÉELS PORTAIENT LE NIVEAU ADMINISTRATEUR SANS QUE PERSONNE
 * NE L'AIT DÉCIDÉ.**
 *
 * `ColloqueEauTogoSeeder` posait `colloque_admin` sur les gestionnaires du
 * colloque de l'eau, sur la foi d'un commentaire qui promettait « crée et gère
 * des colloques, **sans voir ceux où il n'est pas affecté** ». C'était exact
 * tant que ce niveau n'ouvrait rien d'autre ; depuis que l'administrateur a
 * accès à TOUTE la plateforme (2026-10-20), c'était leur avoir donné les
 * comptes, les rôles et les réglages - pour une nomination sur UN colloque.
 *
 * Le seeder pose désormais `ACCESS_USER`, mais **il est idempotent et ne
 * retouche jamais un compte existant** : les comptes déjà importés gardent leur
 * ancien niveau. D'où cette migration, qui les redescend.
 *
 * ⚠️ **Elle ne vise QUE ce que l'import a promu**, et c'est la marque
 * `meta.import_source` qui l'établit : le seeder écrit le niveau et cette marque
 * dans le même geste, à la création du compte. Un compte promu par un humain
 * depuis l'écran des niveaux n'en porte pas, et n'est donc pas touché - on ne
 * défait pas une décision prise en connaissance de cause.
 *
 * ⚠️ **La marque seule ne suffirait pas** : le même import crée aussi les 44
 * comptes d'auteurs, qui naissent en `ACCESS_USER`. C'est la conjonction des
 * deux conditions qui désigne exactement l'équipe.
 *
 * ⚠️ **Leur pouvoir ne bouge pas d'un pouce** : il vient de `event_user`, où
 * leurs fonctions `gerant` / `co_gerant` restent intactes. C'est la règle du
 * projet - « gérer un événement n'élève personne au rang d'administrateur de la
 * plateforme ».
 *
 * ⚠️ **On lit le JSON en PHP, pas en SQL** : les fonctions de chemin JSON
 * diffèrent entre MySQL (production) et SQLite (développement), et ce filtre ne
 * porte que sur une poignée de lignes. Un `whereJsonContains` se découvrirait
 * en ligne, au milieu d'un déploiement.
 */
return new class extends Migration
{
    /**
     * ⚠️ La valeur est RECOPIÉE, pas lue sur `ColloqueEauTogoSeeder::SOURCE` :
     * une migration est une page d'histoire, elle doit continuer de dire ce
     * qu'elle a fait même si la constante est renommée plus tard.
     */
    private const MARQUE_IMPORT = 'google-forms-eau-togo-2026';

    public function up(): void
    {
        $candidats = DB::table('users')
            ->where('access_level', User::ACCESS_COLLOQUE_ADMIN)
            ->get(['id', 'meta']);

        $ids = [];

        foreach ($candidats as $compte) {
            $meta = json_decode((string) ($compte->meta ?? ''), true);

            if (is_array($meta) && ($meta['import_source'] ?? null) === self::MARQUE_IMPORT) {
                $ids[] = (int) $compte->id;
            }
        }

        if ($ids === []) {
            return;
        }

        // ⚠️ Rejouable : le filtre sur `access_level` ne trouve plus rien au
        // second passage. Une migration qui a échoué à mi-chemin se reprend.
        DB::table('users')->whereIn('id', $ids)->update([
            'access_level' => User::ACCESS_USER,
            'updated_at' => now(),
        ]);
    }

    /**
     * ⚠️ **Vide, et délibérément.** Reculer, ici, consisterait à rendre la
     * plateforme entière à deux comptes qui ne l'ont jamais demandée : une
     * remontée de privilèges ne se « restaure » pas. Et le seeder ne repose
     * plus ce niveau, donc rien ne le redonnera par inadvertance.
     */
    public function down(): void {}
};
