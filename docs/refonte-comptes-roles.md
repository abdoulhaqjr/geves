# Refonte comptes & rôles — « un participant est avant tout un utilisateur »

> Chantier structurant validé le 11 sept. 2026. Modèle cible : **option B** (compte unifié).
> Maquette de référence : https://claude.ai/code/artifact/732b3fd7-0cbf-41bb-a6bc-58495d16f6f5
> Branche de sauvegarde : `sauvegarde/pre-rbac` (créée à `5674218`).

## 1. Modèle cible

- **Une personne = un `User`, une seule connexion** (fusion de `Account` dans `users`).
- **3 niveaux d'accès globaux** : `super_admin` · `colloque_admin` (crée + gère/co-gère des colloques) · `user` (aucun accès global).
- **Rôles par colloque cumulables** (une personne peut en avoir plusieurs), 2 natures :
  - **Scientifiques** : Communicant, Co-auteur, Relecteur, Modérateur, Rapporteur, **Comité scientifique**, Auditeur.
  - **Organisation** : Gérant/Co-gérant (réservé `colloque_admin`), **Comité d'organisation**, Secrétariat, Comptabilité, Accueil, Appui.
- **Relecture = rôle à part entière** (accès portail d'évaluation) **+** affectation des soumissions par-dessus.
- **`participants` reste la fiche d'inscription** (paiement, badge, présence) ; seul son champ `role` migre vers les rôles cumulables.
- **Une seule page de connexion** ; orientation post-login selon les rôles.

## 2. Cartographie de l'existant (Phase 0)

### Guards & modèles (`config/auth.php`)
| Guard | Provider | Modèle | Table |
|---|---|---|---|
| `web` | users | `App\Models\User` | `users` |
| `account` | accounts | `App\Models\Account` | `accounts` |

- **`auth('account')` / `guard('account')`** : ~32 fichiers (tous les `Http/Controllers/Account/*`, + `SetLocale`, `StampAccountSession`, `Impersonation`, `Notification`, `Meeting`, `SessionEngagement`, `Locale`, `Egress`, `Public/Gesco`, requests Account…).
- **spatie** (`hasRole/HasRoles`) : `UserController`, `User`, `AppServiceProvider` seulement.

### Colonnes
- **`accounts`** (profil riche) : civility, first_name, last_name, grade, email(unique), password, email_verified_at, remember_token, phone, affiliation, country, bio, photo_path, meta(json), + 2FA (totp/sms/recovery), ORCID (orcid, orcid_verified_at), city, locale, notification prefs.
- **`users`** (léger) : name, email(unique), password, email_verified_at, remember_token, + champs staff (`add_staff_fields_to_users`), notification prefs. **Pas** de first/last name (un seul `name`).

### FK vers `accounts` (à rebrancher → `user_id`)
- `participants.account_id`  → cœur (relation `Account::participations()`)
- `sessions.account_id` (table de session Laravel — porte aussi `user_id`)
- `session_questions.account_id`
- `session_polls.account_id`
- Notifications : `Account` est `Notifiable` (table `notifications` polymorphe `notifiable_type/id`) → repointer vers `User`.

### Ce qui NE bouge pas
- `submissions.participant_id`, `payments.participant_id` → pointent vers `participants`, pas vers `accounts`. Conservés tels quels.
- `edition_user` (pivot staff↔édition) : fusionnera avec `participants.role` en Phase 4.

## 3. Plan par phases

| Phase | Contenu | Risque | État |
|---|---|---|---|
| **0** | Branche `sauvegarde/pre-rbac` + cette cartographie | Nul | ✅ fait |
| **1** | Identité unifiée : migrer `accounts` → `users` (dédoublonnage par e-mail), ajouter les colonnes profil manquantes à `users`, rebrancher `participants.account_id`→`user_id`, sessions/questions/polls, notifications. Migration réversible + tests. | Élevé | ✅ fait |
| **2** | Connexion unique + retrait du guard `account`. **Découpé** (voir ci-dessous). | Moyen→Élevé | 🔄 en cours |
| — 2a | Capacités participant portées sur `User` via trait `ParticipantIdentity` (additif, sans changement runtime). | Faible | ✅ fait |
| — 2b | Bascule du runtime participant vers l'identité `users` + retrait guard `account` (~32 fichiers app + ~35 fichiers de test). | Élevé | ✅ fait |
| — 2c | Enforcement `must_reset_password` + suppression de la table/model `Account` (reste : `accounts` conservée en legacy). | Moyen | — |

### Phase 2b — réalisé
- **Guard `account` repointé sur le provider `users`** (config/auth.php) : les deux guards résolvent la même table `users` ; le guard « account » n'est plus qu'un CONTEXTE (espace participant) vs « web » (back-office).
- **Connexion unique** (`LoginController`) : authentifie un `User` ; si accès back-office (`isColloqueSuperAdmin() || canAnyEdition()`) → guard web + `/admin`, sinon → 2FA puis guard account + espace participant.
- **~32 fichiers app** convertis `Account`→`User`, `account_id`→`user_id`, `->account`→`->user` (contrôleurs Account/*, actions, mails, listeners, `EventSession`, `CurrentColloque`, `AccountActivity`, `SessionEngagement`, session Q&A). `Register`/`Login` créent/authentifient des `User`.
- **Migration `..._000300_add_user_id_to_session_votes`** : `user_id` + unicité sur `session_question_votes` / `session_poll_votes` (backfill via `merged_account_id`).
- **User** : `guard_name='web'` (spatie sans ambiguïté multi-guard) ; `booted()` remplit `name` depuis prénom/nom ; `usesDedicatedMailFor` via le registre (comme Account) ; vérif e-mail participant par défaut + `sendStaffEmailVerificationNotification()` explicite au back-office ; profil fillable.
- **~35 fichiers de test** migrés `Account`→`User` (helper central `verifiedAccount()` renvoie un User ; `UserFactory` gagne first/last/affiliation + `incomplete()`).
- **484 tests verts, PHPStan clean.** `Account`/`accounts` restent en base (données legacy) — suppression en 2c.

### Phase 3 — réalisé
- Migration `..._000400_add_access_level_to_users` : colonne `access_level` (super_admin / colloque_admin / user, défaut user) ; backfill super_admin depuis le rôle `administrator` / la permission `manage_colloques`. Réversible.
- `User` : constantes `ACCESS_*`, `accessLevels()`, `isColloqueSuperAdmin()` (via access_level + repli spatie), `isColloqueAdmin()`, `canCreateColloque()`.
- `EnsureEditionAccess` : la création d'un colloque (route sans édition en contexte) est autorisée aux **administrateurs de colloque** (`canCreateColloque()`).
- `EditionFormController::store` : le créateur non super-admin est **auto-affecté gérant** de la nouvelle édition (fonction `administrator` dans `edition_user`) → capacités pleines dessus.
- UI page Utilisateurs : sélecteur **« Niveau d'accès »** (form) + badge dans la liste.
- Tests : `AccessLevelTest` (résolution des niveaux, création par admin de colloque + gérance, refus utilisateur simple). 487 tests verts, PHPStan clean.

### Phase 4 — réalisé (cœur)
- Catalogue `StaffRoles::ROLES` enrichi : **gerant** + **co_gerant** (capacités pleines sur le colloque), **comite_organisation** (org), aux côtés de **scientific** (« Comité scientifique ») → **2 comités distincts**. `teamRoles()` les propose ; `reviewer`/`administrator` restent exclus (relecture = affectation ; administrator = global).
- Migration `..._000500_rename_edition_admin_to_gerant` : `edition_user.role` `administrator`→`gerant`. `EditionFormController::store` affecte le créateur comme **gerant**.
- **Cumulabilité** : `edition_user` porte plusieurs fonctions d'organisation par personne/édition (déjà cumulable) ; les rôles **scientifiques** (Communicant, Relecteur, Modérateur, Rapporteur, Membre de comité, Auditeur) sont **dérivés cumulablement** par `AccountActivity` (affichés dans l'aperçu participant). `capabilitiesOnEdition` via `permissionsFor` couvre les nouveaux rôles.
- Team UI + matrice Utilisateurs reflètent le catalogue automatiquement. Tests catalogue + co-gérant. **489 tests verts, PHPStan clean.**
- **Reste (4 — polish)** : fusion PHYSIQUE de `participants.role` dans un store cumulable unique (aujourd'hui rôle « primaire » + dérivation) ; renommer le participant `committee`→comité scientifique ; affichage cumulable dans la LISTE participants (aujourd'hui aperçu seulement).
| **3** | 3 niveaux globaux (super_admin / colloque_admin / user) en remplacement du rôle unique `administrator`. | Moyen | ✅ fait |
| **4** | Rôles par colloque cumulables : catalogue à 2 natures, gérant/co-gérant, 2 comités distincts (org cumulable via `edition_user` ; scientifiques dérivés cumulablement via `AccountActivity`). | Élevé | ✅ fait (cœur) |
| **5** | Relecture : rôle « Relecteur » (portail) + affectation soumissions par-dessus. | Faible | — |
| **6** | UI & renommages (Niveau d'accès, Rôles dans ce colloque cumulables + capacités). | Faible | — |
| **7** | Nettoyage : retrait guard `account`, anciennes routes/libellés, non-régression. | Moyen | — |

### Phase 1 — approche détaillée (à valider avant exécution)
1. **Colonnes** : ajouter à `users` les champs profil d'`accounts` (first_name, last_name, civility, grade, phone, affiliation, country, city, bio, photo_path, meta, 2FA, orcid…). Garder `name` (rempli depuis first+last).
2. **Migration de données** (réversible) : pour chaque `account`, chercher un `user` de même e-mail →
   - existe : fusionner le profil dans ce user (ne pas écraser un mot de passe staff sans règle claire — à trancher) ;
   - sinon : créer un `user` (niveau global `user`).
   - Conserver une table de correspondance `account_id → user_id` le temps de la bascule.
3. **Rebranchement FK** : `participants.account_id`→`user_id` ; `sessions`, `session_questions`, `session_polls` ; `notifications` (notifiable).
4. **Modèle** : `Participant::participations()` via `user()` ; helpers d'`Account` (full_name, initials, 2FA, orcid) portés sur `User`.
5. **Tests** : suite complète verte à chaque étape ; garder le guard `account` fonctionnel jusqu'en Phase 2 (bascule connexion).

**Décisions Phase 1** : collision e-mail staff+participant → **réinitialisation forcée** (`users.must_reset_password`, à honorer en Phase 2) ; migration **qui préserve les données** (réversible, testée sur le seed).

### Phase 1 — réalisé
- Migration `2026_09_11_000100_add_profile_columns_to_users` : colonnes profil + 2FA + ORCID + `merged_account_id` + `must_reset_password` sur `users` ; `user_id` sur `participants`, `session_questions`, `session_polls`. **down() réversible** (drop de l'index avant la colonne — SQLite).
- Migration de données `..._000200_migrate_accounts_into_users` : accounts → users (dédoublonnage e-mail, création sinon), backfill `participants.user_id` + questions/sondages + repointage des notifications (morph). Réversible.
- Modèles : `User::participations()`, `Participant::user()` (coexiste avec `account()`), casts profil.
- **Vérifié sur le seed** : 1 compte participant → +1 user, 3 fiches participant rebranchées, 0 collision ; rollback/re-migrate OK ; 484 tests + PHPStan verts. `accounts` et le guard `account` restent fonctionnels (bascule en Phase 2).
