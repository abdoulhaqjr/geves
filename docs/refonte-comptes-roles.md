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
| **1** | Identité unifiée : migrer `accounts` → `users` (dédoublonnage par e-mail), ajouter les colonnes profil manquantes à `users`, rebrancher `participants.account_id`→`user_id`, sessions/questions/polls, notifications. Migration réversible + tests. | Élevé | ⏳ |
| **2** | Connexion unique (`/connexion`), redirection post-login selon rôles, fusion inscription/vérif e-mail/reset, impersonation. | Moyen | — |
| **3** | 3 niveaux globaux (super_admin / colloque_admin / user) en remplacement du rôle unique `administrator`. | Moyen | — |
| **4** | Rôles par colloque cumulables : pivot `(user, edition, role)` fusionnant `edition_user` + `participants.role`. Catalogue rôles + capacités, 2 comités distincts. | Élevé | — |
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

**Points à trancher en Phase 1** : gestion des collisions de mot de passe (un e-mail à la fois staff et participant) ; `name` unique vs first/last name.
