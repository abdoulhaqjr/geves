<?php

use App\Models\Participant;

/**
 * **Tout rôle que le code attribue a son libellé.**
 *
 * ⚠️⚠️ **UN RÔLE SANS LIBELLÉ SE RETOURNE EN ANGLAIS, SANS RIEN CASSER.**
 * `Participant::roleLabel()` retombe sur `ucfirst($role)` : `trainee` et
 * `trainer`, posés par l'inscription à une formation et par l'ajout d'un
 * formateur, s'affichaient **« Trainee »** et **« Trainer »** - dans la fiche
 * d'un participant, dans l'export CSV du registre, dans la répartition par rôle
 * des rapports, et jusque dans le jeton `{role}` d'une **attestation**. Aucune
 * erreur, aucun test rouge : juste de l'anglais au milieu d'un document
 * officiel en français.
 */
it('donne un libellé français à chaque rôle attribué par le code', function () {
    $attribues = [];

    $fichiers = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator(app_path(), FilesystemIterator::SKIP_DOTS)
    );

    foreach ($fichiers as $f) {
        if (! $f instanceof SplFileInfo || $f->getExtension() !== 'php') {
            continue;
        }

        // ⚠️ On lit le CODE, commentaires retirés : `Participant.php` EXPLIQUE la
        // règle en citant `role:`, et un commentaire ne doit pas se faire passer
        // pour une attribution (même esprit que `DegradeHerosTest`).
        $code = '';
        foreach (token_get_all((string) file_get_contents($f->getPathname())) as $t) {
            if (is_array($t) && in_array($t[0], [T_COMMENT, T_DOC_COMMENT], true)) {
                continue;
            }
            $code .= is_array($t) ? $t[1] : $t;
        }

        // ⚠️ L'argument NOMMÉ `role:` n'est employé que pour attribuer un rôle de
        // participant (`UpsertParticipantForAccount::handle`) : le viser évite les
        // faux positifs des autres tables qui ont aussi une colonne `role`
        // (`event_user`, `submission_authors`, les comités).
        if (preg_match_all("/\brole:\s*'([a-z_]+)'/", $code, $m) === 0) {
            continue;
        }

        foreach ($m[1] as $role) {
            $attribues[$role] = $f->getFilename();
        }
    }

    // Le balayage doit trouver quelque chose, sinon la garde ne garde rien.
    expect($attribues)->not->toBeEmpty();

    $sansLibelle = array_diff_key($attribues, Participant::ROLES);

    expect($sansLibelle)->toBe([]);
});

/** ⚠️ Et les libellés sont en FRANÇAIS : `ucfirst('trainee')` rendait « Trainee ». */
it('nomme en français les rôles du module Formation', function () {
    $stagiaire = new Participant(['role' => 'trainee']);
    $formateur = new Participant(['role' => 'trainer']);

    expect($stagiaire->roleLabel())->toBe('Inscrit à une formation');
    expect($formateur->roleLabel())->toBe('Formateur');

    expect($stagiaire->roleLabel())->not->toBe('Trainee');
    expect($formateur->roleLabel())->not->toBe('Trainer');
});

/**
 * ⚠️ Le repli reste, et c'est voulu : d'anciennes lignes peuvent porter un rôle
 * retiré depuis. Mieux vaut un mot approximatif qu'une colonne vide.
 */
it('garde un repli pour un rôle inconnu', function () {
    expect((new Participant(['role' => 'inconnu']))->roleLabel())->toBe('Inconnu');
});
