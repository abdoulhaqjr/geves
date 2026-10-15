# Passation - GEVES - Gestion des ÉVénements Scientifiques (Université de Kara)

> Document de passation pour la personne qui reprend le développement.
> Dernière mise à jour : 2026-09-22 (**module Témoignages** - recueil auprès des participants, modération plateforme, sélection « à la une » sur l'accueil proposée par l'IA (impact) et ajustable par l'admin ; voir [§11](#11-évolutions-depuis-le-2026-08-11-professionnalisation) - **certification cryptographique des attestations** - signature EdDSA/Ed25519, Verifiable Credential en JWS compact (VC-JWT), publication `did:web`/JWKS, vérification effective ; voir [§11](#11-évolutions-depuis-le-2026-08-11-professionnalisation) ; attestations de communication **par communication + plafonds** ; refonte du wizard de soumission (CKEditor 5, blocage d'étape, auteur correspondant éditable + **grade par colloque**, correction du résumé avant évaluation) ; renommage **GEVES** + nom dynamique, publication GitHub, champs téléphone intl-tel-input, résilience/routage/failover IA, copilote à actions exécutables, persistance du chat, limites du chatbot public, normalisation Pint du projet. **Voir le journal détaillé en [§11](#11-évolutions-depuis-le-2026-08-11-professionnalisation).**)
>
> 👥 **Projet développé à plusieurs.** Chaque contributeur utilise Claude Code, dont la mémoire locale ne se transfère pas : **`CLAUDE.md` et ce fichier sont la mémoire/le contexte collectifs**. Tout agent doit les **tenir à jour** à chaque tâche notable (fonctionnalité, décision d'archi, changement de convention/workflow), puis committer et pousser (`git pull --rebase` avant `git push`).
>
> ⚠️ **Le projet s'appelle désormais GEVES** (ex-« Gesco »). Le nom d'affichage est **dynamique** (`config('app.name')`, surchargé via Réglages) - ne jamais coder un littéral « Gesco »/« GEVES » dans l'UI. Le **dossier local** reste `Herd/gesco` et le **domaine de dev** est désormais `geves.test` ; certaines classes historiques gardent le nom `Gesco` (ex. `AskGesco`, `GescoController`).

Ce fichier résume **l'état du projet**, la **mise en route**, l'**architecture**, les **conventions**, le **travail réalisé**, ce qui **reste à faire** et les **pièges** à connaître.

---

## 1. Vue d'ensemble

### Cas d'essai du paiement : accepté mais pas réglé, à 100 FCFA (2026-10-13)

Le jeu de démonstration n'avait pas le parcours qu'on vient le plus souvent
éprouver : **une communication acceptée, aucun paiement, et un montant qui se
déduit du grade**. Les six comptes `commN@test.tg` réglaient tous sauf un, mais
celui-là est au grade « Doctorant » et sa communication est déjà **programmée**.

**M. TOURE Abdoul** (`tabdoulhaq@gmail.com`, mot de passe `password`), **étudiant
en master**, est semé par `ColloqueAvenirSeeder::buildCommunicantNonRegle()` avec
une communication **acceptée** (`UK-2025-007`, axe « Sciences économiques, de
gestion et entreprenariat ») et **zéro paiement**. Le tarif des doctorants et
étudiants en master passe à **100 FCFA** : c'est le montant sur lequel on éprouve
un vrai règlement mobile money de bout en bout. Les deux autres catégories gardent
un tarif réaliste (35 000 / 50 000) - un barème entièrement à 100 F n'éprouverait
plus la grille.

⚠️ **Les paiements de démonstration du seeder de base résolvaient 15 000 F écrit en
dur.** Avec un tarif à 100, ils auraient affiché des encaissements sans rapport avec
la grille. Ils passent par `Fees::resolveAmount()`, comme le seeder de volume et
comme la page de paiement.

⚠️ **Son profil est COMPLET, et ce n'est pas du zèle** : sans prénom, nom,
téléphone, affiliation et e-mail vérifié, `User::profileComplete()` est faux et la
plateforme **refuse d'engager le paiement**. Le cas d'essai serait bloqué avant même
d'atteindre la passerelle.

⚠️ **Rien n'est au programme pour lui**, et c'est cohérent : son inscription n'est
pas confirmée. Sa référence est hors de la série `AVD-` du seeder de volume, dont
les gardes vérifient que les 200 communications acceptées sont toutes planifiées.

⚠️ **`users.phone` est un identifiant UNIQUE, et le numéro était déjà pris.** Il
était saisi **à la main** sur le super-administrateur de la base de développement
(`admin@univkara.tg`) : posé tel quel, le scénario tombait sur la contrainte
d'unicité et **aucune** donnée de démonstration ne se semait - pour un numéro de
téléphone, exactement le piège déjà documenté pour le seeder de volume. Arbitré
avec l'équipe : le numéro va au cas d'essai du paiement, qui en a besoin ; l'autre
compte le perd, et le seeder **le dit** (`command->warn`). Un numéro retiré sans un
mot est une surprise, et le super-administrateur peut y tenir sa double
authentification par SMS - celui de la base de développement n'en avait pas.

**Garde** : `CompteNonRegleTest` - profil complet, grade porté sur le profil ET sur
l'inscription, 100 FCFA résolus (et les autres catégories intactes), communication
acceptée et rattachée au compte, aucun paiement, **la page « Inscription & paiement »
annonce bien 100**, et la libération du numéro. **Six sabotages vérifiés** :
téléphone retiré, tarif remis à 15 000, communication non acceptée, paiement ajouté,
rattachement de l'auteur rompu, libération du numéro désamorcée.

### Colloque de démonstration déplacé au 12-16 octobre 2026 (2026-10-13)

Le VIIIe colloque « Innover pour un avenir durable » se tient désormais du **lundi
12 au vendredi 16 octobre 2026** (il était du 5 au 9). Comme le prévoyait le seeder,
l'opération s'est faite en changeant **les deux lignes `$start`/`$end`** de
`ColloqueAvenirSeeder` : tout le reste - plénières, programme synthétique publié,
formation, séances d'atelier du seeder de volume - se cale dessus par décalage en
jours. La fenêtre reste de **5 jours** (la clôture est à J+4), et l'événement est de
nouveau **en cours**.

**Demande précise, et c'est elle qui a dicté le reste** : il faut des créneaux
programmés dans **au moins deux ateliers fonctionnels simultanément, avec des
communications, le mardi 13/10 entre 14h et 18h**. Le bloc J+1 du seeder de volume
était une **matinée** (08:30-11:30) : il est porté à **14:00-17:00**. Ce jour-là, les
**six** ateliers tiennent donc leur séance 2 en parallèle, dans six salles distinctes,
avec **33 communications** et la visioconférence armée (`scheduled`, donc ouverte
automatiquement à 13:30). La matinée du 13/10 ne garde que les deux séances de
11:30-12:30 du seeder de base.

⚠️ **Le programme PUBLIÉ a été déplacé avec le bloc.** Les séances vivent dans
`event_sessions`, la page du colloque lit `settings.program_synthesis`, et rien
n'oblige structurellement les deux à rester d'accord : déplacer l'un sans l'autre
ferait annoncer des communications là où aucune salle n'ouvre. La journée J+1 du
programme synthétique est donc passée de « Journée d'ateliers, 08:30-11:30 » à
« Après-midi, 14:00-17:00 ».

⚠️ **« Fonctionnels » demandait plus que « programmés ».** Les 24 séances d'atelier
n'avaient **ni modérateur ni rapporteur** : sans adresse e-mail rattachée à un compte,
ni l'espace « Séances à modérer », ni la présence, ni la validation du passage, ni le
rapport de séance ne s'ouvrent à personne. Les **ateliers 1 et 2** sont désormais
confiés aux comptes de démonstration (`moderateur1`/`rapporteur1`,
`moderateur2`/`rapporteur2`) sur leurs **quatre** blocs
(`ColloqueAvenirVolumeSeeder::equipesDeSeance()`). **Deux et pas six** : les six
ateliers étant simultanés, en confier plus ferait du même compte le modérateur de
trois salles à la même heure - exactement le genre de programme faux que le reste du
jeu de démonstration s'applique à éviter.

**Garde** : `ColloqueAvenirVolumeSeederTest`, cas « fait tourner au moins deux ateliers
en parallèle le 13/10 de 14h à 18h ». Il éprouve la **simultanéité** (regroupement par
horaire identique, et non le simple nombre de séances du jour), des **ateliers et des
salles distinctes**, la présence de **communications**, la **visio**, les **séances
confiées** (≥ 2, e-mails résolus en comptes existants), l'**absence de modérateur
ubiquitaire**, et l'**accord du programme publié**. Le cas des dates passe de 5-9 à
12-16 octobre. **Cinq sabotages vérifiés** : bloc remis le matin, salle unique,
équipes retirées, même modérateur sur deux salles, programme publié laissé le matin.

⚠️ **Défaut PRÉEXISTANT, repéré au passage et NON corrigé** : les deux séances de
démonstration calées sur l'**horloge** (une en direct, une terminée) peuvent recouvrir
les séances de 11:30-12:30 du même jour, qui partagent leur modérateur et leur
rapporteur (`$moderators[$i % 2]`). Selon l'heure du `db:seed`, `moderateur1` se
retrouve donc dans deux salles à la fois - c'était le cas ce 13/10 à 11h. Hors de la
fenêtre 14h-18h demandée, et il n'y a que **deux** comptes de modérateur dans le jeu de
démonstration : le corriger proprement demande un troisième binôme, ou de décaler les
séances de 11:30. À arbitrer.

### Sécurité des formulaires publics (2026-10-12)

L'inscription participant et le contact de la page d'accueil utilisent Cloudflare Turnstile. Configurer la clé du site et la clé secrète dans **Réglages → Sécurité** (secret chiffré en base et non réaffiché), ou via `CLOUDFLARE_TURNSTILE_SITE_KEY` et `CLOUDFLARE_TURNSTILE_SECRET_KEY`, puis enregistrer les domaines du site dans le widget Cloudflare. Le composant Blade `x-turnstile` affiche le défi et `App\Support\Turnstile` vérifie le jeton avec Siteverify côté serveur (succès, action, hostname) avant toute création de compte ou tout e-mail. Sans configuration complète, l'envoi est désactivé dans les vues et rejeté sur les endpoints. Le contact garde son honeypot et sa limitation par IP.

### Inscription et paiement côté participant (2026-10-12)

La page `/compte/paiement/{event}` présente un parcours en quatre étapes, l’état du dossier et l’action utile avant la grille des tarifs. Sans fiche de participation, elle oriente vers le suivi ou le dépôt d’une communication ; avec une communication acceptée, elle met le montant et le formulaire de paiement au premier plan. La grille des six catégories éventuelles est repliable sans participation et met en évidence le tarif personnel uniquement quand le grade correspond explicitement à une entrée de `Fees::grid()`. Le moyen de paiement est un radio `gateway` relié à Alpine pour que le sélecteur d’opérateur suive le choix. La règle serveur d’éligibilité et les passerelles restent inchangées.

### Libellé des liens vers une page d'événement (2026-10-12)

`Event::publicPageLabel()` produit « Page du colloque », « Page du séminaire », « Page de la journée scientifique » ou « Page des journées scientifiques » selon le type de la série et, pour les journées, la formulation de l'édition puis sa durée ; `Autre` conserve « Page de l'événement ». Le bouton de l'accueil, les cartes des événements à venir, le menu participant et le tableau de bord partagent ce libellé. Les nouvelles chaînes ont leur traduction anglaise dans `lang/en.json`.

### Accueil public : section À propos (2026-10-12)

La section `#apropos` présente désormais deux panneaux complémentaires : ce que GEVES rassemble dans un parcours d'événement, puis la responsabilité de la DRI et le caractère institutionnel du service. Le panneau DRI affiche le **logo large pour fond sombre** de `Branding`, et non le monogramme compact. Les trois cartes qui répétaient mission, cycle et absence de vocation commerciale ont été retirées ; les détails fonctionnels restent dans la section suivante. Les deux accès de contact renvoient respectivement à `#contact` et à l'adresse de la DRI. Les nouveaux textes français ont une traduction dans `lang/en.json`.

**GEVES** (Gestion des ÉVénements Scientifiques - Direction de la Recherche et de l'Innovation, **Université de Kara**) est le portage **Laravel** d'une extension WordPress existante (`uk-colloques`). La plateforme gère de bout en bout non seulement des **colloques** mais tout **événement scientifique** (journées scientifiques, séminaires…) : séries → éditions datées → soumissions de résumés → relecture en aveugle → paiements → créneaux/ateliers → livret/badges/attestations → e-mails → rapports → comptes participants. Assistance **IA** intégrée (chatbot public « Gesco » + copilote back-office à actions exécutables).

- **But** : plateforme professionnelle, autonome (sans WordPress).
- **Stack** : Laravel 13 (PHP 8.4), **Blade + Livewire 4 + Alpine 3**, Tailwind v4 (+ design system maison), Vite 8, SQLite en dev, Pest pour les tests.
- **Dépôt** : **GitHub privé** `https://github.com/abdoulhaqjr/geves` (branche `master`). Voir le workflow Git en [§8](#8-pièges--bonnes-pratiques-à-lire-avant-de-coder).
- **Périmètre couvert à ce jour** : fondations, comptes participants, back-office éditions, page publique, **soumission**, **relecture**, **resoumission**, **paiement**, **ateliers/sessions/créneaux**, **participants**, **documents (livret/badges/attestations QR)**, **paiement en ligne (CashPay/FedaPay)** (voir §6).

### Sources de référence (hors dépôt, dans `Downloads/`)
- `Site Web/uk-colloques/` - **plugin WordPress source** (27 modules, ~19 600 lignes). Référence fonctionnelle.
  - `uk-colloques/README.md` - modules 1 → 27 (spécifications fonctionnelles).
  - `uk-colloques/inc/install.php` - schéma SQL d'origine (`wp_ukc_*`).
  - `Site Web/Documentation-Projet/PASSATION.md` - passation du plugin WP (contexte métier, MoneyFusion, FedaPay, HAL…).
- `gesco-mockup/` - maquettes HTML/CSS portant la charte (espace-participant, admin-documents, badges, attestations…).
- `Herd/bizzcart.com/` - autre app Laravel dont on **réutilise des patterns** (couche `app/Services`, passerelles de paiement `Services/FinanceAI/Gateways`, spatie/permission, dompdf).

---

## 2. Environnement & mise en route

### Poste local (Windows + Laravel Herd)
⚠️ **PHP, Composer et artisan ne sont PAS dans le PATH** (ni bash ni PowerShell). Ils sont fournis par **Laravel Herd** dans `~/.config/herd/bin` (= `C:\Users\TOURE\.config\herd\bin`), sous forme de shims `.bat`.

Pour toute commande (Git Bash) :
```bash
export PATH="$HOME/.config/herd/bin:$PATH"
cd "C:/Users/TOURE/Herd/gesco"
php.bat artisan ...        # PHP 8.4
composer.bat ...           # Composer 2.10
```
Node/npm, eux, sont dans le PATH (node v24, npm 12).

- Le site est servi par **Herd** sur **https://geves.test** (HTTP 200).
- Base de données : **SQLite** (`database/database.sqlite`, ignorée par git). Migrations pensées **portables MySQL** pour la prod.

### Installation à froid
```bash
export PATH="$HOME/.config/herd/bin:$PATH"
cd "C:/Users/TOURE/Herd/gesco"
composer.bat install
cp .env.example .env        # puis renseigner
php.bat artisan key:generate
php.bat artisan migrate --seed   # crée le schéma + rôles + admin + colloque de démo
php.bat artisan storage:link     # fichiers de soumission (disk public)
npm install && npm run build
```

### Comptes & données de démo (seeders)
- **Back-office** : https://geves.test/admin/connexion → `admin@univkara.tg` / `password` (à changer en prod).
- **Page publique de démo** : https://geves.test/colloque/avenir-durable-2025.
- Seeders : `RolesAndAdminSeeder` (rôles `administrator`/`editor` + permission `manage_colloques` + admin), puis **un seul** colloque de démo à l'échelle réelle - `ColloqueAvenirSeeder` (colloque + édition ouverte `avenir-durable-2025`, **5 → 9 octobre 2026**) + `ColloqueAvenirVolumeSeeder` (200 communications co-signées). Les anciens seeders de colloques de démo (Showcase, Live, DemoColloque, DemoScenario, DemoStaff) ont été **supprimés**.

### Lancer les tests & la qualité
```bash
php.bat artisan test                 # ≈568 tests (base :memory:)
php.bat artisan test --filter=PaymentTest
php.bat vendor/bin/phpstan analyse   # analyse statique (Larastan niveau 5)
php.bat vendor/bin/pint              # style (Laravel Pint)
composer.bat check                   # pint --test + phpstan + tests (= le CI)
```
> ⚠️ Le lanceur affiche une sortie **JSON compacte** (plugin `laravel/pao`) - pas la trace complète. Pour déboguer une exception dans un test, capter l'exception dans le test (try/catch + `fwrite(STDERR, …)`), ou utiliser `->assertOk()`/dumps ciblés. `vendor/bin/phpunit` **ne fonctionne pas** (Pest requis).

**Visio interactive des sessions - fondations backend (fait, UI en attente du mockup)** : objectif = **présentations en ligne** (les communicants présentent leur communication en direct), pas de la diffusion à sens unique. Choix plateforme : **Jitsi Meet** (gratuit, embarquable, rôles via JWT en self-host - réutilise `firebase/php-jwt`). Groundwork **données + domaine, sans UI** (le mockup définira le formulaire back-office et la page « rejoindre »). *(Un 1ᵉʳ jet « broadcast » - enums `Stream*`, colonnes `stream_*` - a été **pivoté** ; migration `pivot_sessions_streaming_to_meeting`.)* Migration : `event_sessions` ← `meeting_provider` (jitsi|custom), `meeting_room` (nom de salle Jitsi, auto-généré si vide), `meeting_url` (lien collé pour « custom »), `meeting_status` (none|scheduled|live|ended), `meeting_visibility` (public|participants), `recording_url` (replay) - additifs/nullables. Enums `App\Enums\MeetingStatus`/`MeetingVisibility`/`MeetingRole` (les deux 1ers en `HasLabels` ; « en direct » = pastille rouge). **Abstraction fournisseur** `App\Meetings\` calquée sur la couche paiements : contrat `Contracts\MeetingProvider` (`key`/`label`/`isAvailable`/`joinUrl`), `MeetingProviderRegistry` (`default()` réglage `meetings.default`, `for($session)`), `Providers\JitsiProvider` (mode public `meet.jit.si` avec `#userInfo.displayName` ; mode self-host **JWT HS256** matérialisant `moderator` si `JITSI_APP_ID/SECRET` configurés). Config `config/meetings.php`. Helpers `EventSession` : `hasMeeting()` (statut≠none), `isLive()`, `meetingRoom()` (auto `prefix-{edition}-{id}`), `meetingStatusEnum()`/`meetingVisibilityEnum()`, `presenterAccountIds()` (communicants programmés via `slots`), **`meetingRoleFor(?Account, isStaff)`** = **rôle calculé** (staff / e-mail=modérateur assigné → Moderator ; communicant programmé → Presenter ; inscrit ou public → Attendee ; sinon None), `canJoin()`, `joinUrl()` (via le registry), scope `live()`. Statut/visibilité = strings en base. ⚠️ Gating « payés seulement » à décider avec le mockup. *(commit `9d3d003`)*

**Visio - UI provisoire (fait, à affiner au mockup)** : *Back-office* - contrôles par session dans `Admin\Program\Sessions` (partiel `_meeting.blade.php` inclus dans les listes plénières/ateliers) : pastille d'état + boutons Programmer/En direct/Terminer/Aucune (`setMeetingStatus`), Public/Participants (`setMeetingVisibility`), bouton « Rejoindre (modérateur) » si live. *Page « Rejoindre »* - `MeetingController@join` + vue `meeting.join`, route **publique** `/session/{session}/visio` (`session.meeting`, l'accès est géré dans le contrôleur via `meetingRoleFor`/`canJoin`) : si live+autorisé → **iframe** Jitsi (`allow="camera; microphone; fullscreen; display-capture"`), sinon message adapté (pas en cours + replay / connexion requise / accès réservé). *Points d'entrée* - badge « EN DIRECT » + bouton « Rejoindre la visio » sur le programme public (`_session.blade.php`), et bloc « Sessions en cours » sur le dashboard participant (sessions live des éditions du compte qu'il peut rejoindre). **Vérifié en réel** (iframe → `meet.jit.si/{room}`). *(commit `9d9dbfe`)*

**Un tableau par communication (fait)** : le présentateur **n'attend pas le modérateur** - il ouvre le tableau quand il veut, et c'est **son** tableau. Quand un autre présentateur l'ouvre, c'est une **surface vierge** : le schéma du précédent n'a rien à faire sous l'exposé du suivant. Rouvrir le sien le retrouve (on referme parfois par mégarde). ⚠️ D'où le fait que la fermeture **garde le propriétaire** : sans lui, rouvrir le sien l'effacerait. **Reprendre le tableau d'un autre se confirme** - cela efface son contenu chez tout le monde, et rien ne le rend. *(commit à venir)*

**Le présentateur retrouve le tableau (fait)** : ⚠️ **régression de la veille.** La scène et le crayon calculaient « qui présente » chacun de leur côté - la scène prenait « parole donnée OU rôle présentateur », le crayon exigeait la parole donnée. Un communicateur affiché **« Présentateur » en grand sur la scène** trouvait donc le bouton Tableau **grisé** : la salle lui disait deux choses contraires. Une seule définition désormais (`celuiQuiPresente`, règle pure), partagée par les deux, et le droit se recalcule depuis `refreshPeople()` - c'est la liste des personnes qui dit qui présente, et tout y passe. **3 cas Vitest** (73 au total). *(commit à venir)*

**Le bandeau des participants part replié (fait)** : il se dépliait tout seul à chaque arrivée, par-dessus la séance, et il fallait le refermer à la main. Son en-tête porte déjà le compte ; l'ouvrir est un geste. La bascule **Vue** n'y touche plus non plus - elle le refermait de force au retour en vue présentateur, y compris pour qui venait de l'ouvrir. Un cas Pest fige le défaut. *(commit à venir)*

**Le paiement co-auteur sans compte est retiré (fait)** : `/colloque/{slug}/co-auteur` doublonnait le chemin par compte - celui que `LinkSubmissionAuthors` ouvre dès la soumission (liaison au compte, sinon invitation à en créer un, réactivée à l'inscription). ⚠️ **Les deux créaient chacun leur participant** - l'un par e-mail sans `user_id`, l'autre par `user_id` - et **rien ne les rapproche**, faute d'unicité sur `(edition_id, email)`. **Mesuré** : payer sur la page publique puis créer son compte laissait **deux participants** pour la même personne ; chiffres faussés, et surtout l'attestation émise sur le participant sans compte, donc **introuvable dans l'espace de l'intéressé**. Dans l'autre ordre, pas de doublon - le défaut dépendait de l'ordre, donc il passait inaperçu. La page devient une **porte d'entrée** : elle confirme que des communications acceptées vous citent, puis envoie créer son compte ou s'y connecter (un visiteur connecté file droit sur son règlement). ⚠️ **L'URL est conservée** - les e-mails d'acceptation déjà partis la portent, un lien mort aurait coûté des règlements - et l'état « frais déjà réglés » reste, le retour de passerelle d'un paiement co-auteur ancien y atterrissant encore. ⚠️ L'e-mail d'acceptation est **gardé** et repointé, contrairement à ce que je proposais : lui seul annonce que les frais sont dus, l'invitation envoyée à la soumission ne le dit pas. `UpsertCoauthorParticipant` supprimée. **Un cas Pest suit le chemin complet** : cité dans une soumission → compte créé → **un seul** participant, et son espace propose le tarif de son grade. **4 cas Pest.** ⚠️ **Présentation reprise** : la page était un bloc centré sans en-tête ni pied de page - on y arrive par un lien reçu par e-mail, sans savoir de quel colloque il s'agit ni comment en sortir. Elle emprunte désormais la grammaire des autres pages publiques (en-tête commun, `page-hero`, barre d'étapes, pied de page du colloque) et explique à quoi elle sert quand on n'a encore rien cherché. ⚠️ **Deux défauts mesurés en chemin** : `background: currentColor` posé dans le même bloc que `color:#fff` résout le fond sur ce `color` - la pastille de l'étape active sortait **blanche sur blanc** ; et `.ukc-card h2`, réglé en `space-between` pour le back-office, renvoyait le titre à l'autre bout de la carte sur les pages publiques (« Vérifier une attestation » en souffrait aussi). **5 cas Pest.** *(commits `3c30198` + `d9f37d2`)*

**Le tarif ne se choisit pas, et le back-office se densifie (fait)** : la grille publique était une grille d'ACHAT - un bouton « Choisir » par carte, une pastille « Recommandé » posée sur celle du milieu (un index, pas un conseil), et les trois mêmes avantages répétés qui ne distinguaient rien. Or le tarif se **déduit du grade** (`Fees::resolveAmount`). Les cartes renseignent désormais : les grades couverts dedans, les avantages dits une fois dessous, **un seul** appel à l'action. ⚠️ **Le bandeau de rappel au-dessus de la grille a été retiré ensuite** (votre demande) : la règle se lit dans les cartes, il doublait ce qu'elles montrent. Un cas Pest l'empêche de revenir. ⚠️ **Même défaut, en pire, côté co-auteur** (`/colloque/{slug}/co-auteur`) : le menu proposait les catégories tarifaires **avec leur montant en face** - on y prenait la moins chère, et le grade déclaré dans la soumission ne servait à rien. Le grade connu fait foi et n'est plus repostable ; quand il manque, c'est un **grade** qu'on demande, jamais une catégorie, jamais un prix. ⚠️ **Trouvé en chemin, à regarder** : les trois catégories de l'édition de démonstration ne rattachent **aucun grade** et aucune n'est « par défaut » - tout le monde y paie donc le montant le plus élevé (50 000) par repli, doctorants compris. L'onglet « Soumissions & tarifs » le dit maintenant en clair, mais **les grades restent à cocher**. Enfin les deux back-offices passent à **90%** (`html.ukc-dense { zoom:.9 }`) : ⚠️ posé sur `<html>` et non sur le shell, sinon `100vh` ne couvrirait plus que 90% de l'écran - vérifié, barre latérale 698,4px pour une fenêtre de 698px. ⚠️ **Piège Blade payé une 4e fois** : un `@php(…)` inline s'apparie avec le `@endphp` d'un bloc plus bas et avale tout ce qui les sépare - dans un fichier qui contient des blocs, on écrit des blocs. **9 cas Pest.** *(commits `5ccf04a` + `b58630d`)*

**Logo, ordre et téléversement des partenaires (fait)** : le cadre du logo était figé à 76px et s'arrêtait au milieu de la seconde ligne de champs - il suit désormais la hauteur des champs. Il est aussi devenu le **bouton de téléversement** : on téléverse un fichier OU on colle une URL, **l'aperçu s'affiche dans les deux cas**, et une adresse qui ne charge pas se dit (« Image introuvable ») au lieu de l'icône cassée du navigateur. Enfin la liste **se réordonne** à la flèche, et son ordre est celui du bandeau public. ⚠️ **Deux pièges payés ici** : côté client la clé de `x-for` est l'identifiant de la ligne et non son index - sinon le fichier déjà choisi reste sur place pendant que la ligne se déplace, et **le logo d'un partenaire part chez son voisin** (vérifié au navigateur : il suit bien sa ligne) ; côté serveur le ménage des fichiers orphelins compare les **deux listes entières** (`EditionArtwork::forgetMissing`), jamais ligne à ligne, puisqu'après un déplacement l'index 0 ne désigne plus le même partenaire. ⚠️ **Pas de SVG** téléversé : servi depuis notre domaine, il peut porter du script. ⚠️ **Mesuré** : cadre 129,2px = champs 129,2px, écart nul. **6 cas Pest.** ⚠️ **Repéré au passage, non corrigé** : à 375px, la barre d'actions de l'en-tête admin (`.ukc-admin-head`, « ← Liste » / « Aperçu ») déborde de 71px - elle est commune à toutes les pages admin, donc hors de cette tâche. *(commit `19cf98f`)*

**`/verifier` rejoint la famille de la page d'attestation (fait)** : elle portait l'en-tête et le pied de page du site, comme une page d'information quelconque, alors qu'on y arrive **un diplôme à la main**. Elle emprunte désormais `.ukc-cred` - fond dégradé, barre de marque, pastille « Registre officiel », cartes à surtitre - comme la page qu'elle ouvre, et explique en trois temps ce qu'on y fait. ⚠️ **Trouvé en chemin** : `--ukc-mono` n'est **pas** une chasse fixe, `ukc.css` la définit comme un alias de la police du site ; tout ce qui s'en remet à elle (références, numéros de reçu, identifiants d'attestation) s'affiche en proportionnel malgré son nom. J'ai nommé une vraie pile monospace **pour ce champ seulement** - dites-moi si vous voulez que la variable soit corrigée partout. ⚠️ **Mesuré à 375px** : les 36 caractères de l'identifiant ne tenaient pas dans un champ de 302px, le code collé défilait dans sa boîte ; la taille suit maintenant la largeur de l'écran. La colonne a ensuite été élargie à **920px** (cartes de 840, +24%) : ⚠️ ce sont les **cartes** qui s'élargissent, pas les lignes - à 760px la prose était déjà à 74 caractères par ligne, le haut du confortable ; elle est bornée à 62ch tandis que le champ et le bouton prennent toute la carte. **2 cas Pest.** *(commit `4b0ebcc` + ce commit)*

**La requête morte du programme public est retirée (fait)** : `EventSeriesController` chargeait **136 créneaux et 136 communications** à chaque affichage de la page publique (mesuré) pour un `$commsBySession` que la vue n'utilisait pas, et le partial `public/_session.blade.php` qui l'aurait consommé n'était inclus **nulle part** depuis la refonte Blade-first. Les deux sont supprimés : une requête de moins par vue publique, et plus d'arme chargée - ce partial rendait titres **et noms d'auteurs**, sans filtre. ⚠️ **`commsBySession` existe AUSSI, et vit bel et bien, dans `DocumentController`** (PV de séance et fiches imprimables du secrétariat) : ne pas confondre. Ces deux-là ne filtrent pas non plus le statut, mais ce sont des documents **internes** décrivant ce qui est programmé dans une salle - audience et intention différentes, laissés tels quels. ⚠️ **Trouvé en chemin, et ce n'était pas à nous** : la vérification complète d'après rebase a révélé 2 erreurs PHPStan venues du commit `03433707` d'un autre contributeur - `TutorialGuide` caste `steps` sans l'annoter, donc l'analyse comparait « une chaîne à un tableau » et déclarait la détection de guide modifié toujours vraie. Même cause qu'une heure plus tôt sur `TutorialVideoRequest` : annotation ajoutée (⚠️ `array<int, string>` - une étape est une ligne de texte, pas une structure). **1097 tests, Pint et PHPStan verts.** *(ce commit)*

**Le programme public, lui, n'affichait aucune communication - mais deux chiffres publics mentaient (fait)** : vérification demandée après le correctif du livret. ⚠️ **Le programme public ne montre AUCUN détail de communication** - c'est une décision du projet, et elle est déjà gardée par un cas (`ProgramPublicTest`, `assertDontSee` le titre d'une communication). Rien à corriger de ce côté. ⚠️ **En revanche, deux comptes PUBLICS étaient faux** : l'assistante **Gesco** annonçait le nombre de communications d'une séance **sans regarder leur statut** (mesuré : « 2 communications » pour une séance qui n'en tient qu'une), et le compteur des **éditions passées** filtrait le statut **mais pas** les resoumissions - une version corrigée acceptée comptait en plus de son original. La règle du livret a donc été **généralisée et renommée** : `Submission::scopeAcceptedCanonical()` / `isAcceptedCanonical()`, partagée par les quatre endroits du livret et les deux chiffres publics. ⚠️ **Trouvé en chemin** : dans la fermeture d'un `whereHas`, le builder est générique et le scope du modèle n'y est pas visible (ni pour PHPStan, ni pour qui relit) - on passe par une sous-requête plutôt que de recopier les conditions. **2 cas Pest**, ⚠️ vérifiés en retirant le correctif : tous deux échouent, le digest affichant bien « 2 communications ». 1095 tests verts, Pint et PHPStan OK. ⚠️ **Reste une arme chargée, signalée et non touchée** : `EventSeriesController` construit un `$commsBySession` **sans filtre** (136 créneaux + 136 communications chargés à chaque vue de la page publique, mesuré) pour un partial `public/_session.blade.php` que **plus rien n'inclut** depuis la refonte Blade-first. Inoffensif aujourd'hui, mais réinclure ce partial exposerait titres et auteurs de communications non acceptées. *(ce commit)*

**Le livre des résumés ne contient plus que des communications acceptées (fait)** : la section Résumés filtrait bien, mais le **programme** du livret lisait les créneaux **sans rien vérifier**. ⚠️ Une communication **planifiée puis refusée** gardait son créneau : le livre imprimé annonçait son titre et ses auteurs à l'heure dite, alors que son résumé n'y figurait pas - deux sections du même livre se contredisaient. Une **resoumission** acceptée passait pareillement, en doublon de sa version canonique. ⚠️ **Trois constructions de programme** étaient concernées, et corriger l'une n'aurait rien dit des deux autres : `LivretDocx::programmeByDay()` (style par défaut), `LivretDocx::addProgrammeFss()` (style FSS) et `DocumentController::livretViewData()` (PDF et aperçu HTML). La règle a maintenant **une seule définition** - `Submission::scopeForBooklet()` et `belongsInBooklet()` - que les quatre endroits partagent. ⚠️ Un créneau hors livre est **écarté**, pas replié sur l'intitulé de sa séance : la ligne n'existait que pour cette communication, et les autres créneaux de la séance la représentent déjà. ⚠️ **Invisible dans les données de démo** (le seeder ne planifie que des acceptées) : mesuré, 0 créneau concerné sur 136 - c'est un chemin de code non gardé, pas un symptôme observable. **2 cas Pest**, et ⚠️ **vérifiés en retirant le correctif** : tous deux échouent alors sur l'assertion exacte. 1093 tests verts, Pint et PHPStan OK. *(ce commit)*

**`composer check` repasse entièrement au vert (fait)** : Pint échouait sur `routes/web.php` (un contrôleur appelé en nom complet au lieu d'un `use`), et PHPStan signalait deux erreurs dans le travail vidéo récent. ⚠️ **Cause de la première erreur** : `TutorialVideoRequest` caste `meta` en `array` mais ne l'ANNOTAIT pas - l'analyse lit alors le schéma, où une colonne `json` est un `text`, donc une chaîne, et refuse `$request->meta['guide']`. Le cast et l'annotation vont par paire, comme dans tous les autres modèles. La seconde était un `array_values()` sur ce qu'`array_map` rend déjà comme liste : retiré, le contrat `@return list<…>` de la méthode garde le reste. **1091 tests, Pint et PHPStan niveau 5 : tout est vert.** *(ce commit)*

**Les deux cas FFmpeg éprouvent le code, plus le poste (fait)** : ils exigeaient `toEndWith('ffmpeg.exe')` sur la résolution de FFmpeg et FFprobe. ⚠️ **Ils ne tenaient que sur une machine où FFmpeg est installé par WinGet** (paquet `Gyan.FFmpeg`) : vérifié, il n'est installé **nulle part** sur ce poste - ni WinGet, ni Chocolatey, ni Scoop, ni le PATH. Le résolveur rendait donc `ffmpeg` tout court, en laissant le PATH trancher à l'exécution, **ce qui est exactement le comportement voulu** - et le cas échouait quand même. Le résolveur n'avait rien à corriger ; ce sont les cas qui ont été réécrits. Ils fabriquent désormais l'arborescence à éprouver (fichiers temporaires, `putenv('LOCALAPPDATA=…')`) et couvrent **sept** comportements au lieu de deux : chemin configuré prioritaire, chemin configuré inexistant ignoré, découverte WinGet, **version la plus récente** d'abord, repli sur le PATH quand rien n'est installé, FFprobe pris à côté de FFmpeg, et repli de FFprobe. Le cas WinGet est marqué `->onlyOnWindows()`. ⚠️ **Vérifié qu'ils mordent** : avec `rsort()` au lieu de `rsort(…, SORT_NATURAL)` le résolveur sert `Gyan.FFmpeg_7.0` au lieu de `10.0` et le cas échoue ; en retirant la garde `is_file()` sur le chemin configuré, le cas échoue aussi. Résolveur restauré ensuite. **1091 tests verts** - la suite est entièrement au vert. *(ce commit)*

**Le test de garde des dates du seeder tient maintenant dans le temps (fait)** : il échouait depuis le 10 octobre, et ⚠️ **ce n'était pas une régression du seeder mais le calendrier qui a dépassé le colloque**. Les deux séances de démonstration calées sur l'**horloge** (une en direct, une terminée avec replay - une séance « terminée » posée à une date fixe s'annoncerait terminée avant d'avoir eu lieu) tombent le jour du seed : tant qu'on semait pendant les 5 au 9 octobre elles étaient dans la fenêtre, et depuis le 10 elles en sortent. **Mesuré** : les 32 séances du **programme** sont toutes dans la fenêtre et couvrent les 5 jours ; seules ces deux-là en sortent. Le cas les écarte donc, ⚠️ **mais compte qu'il n'y en a que deux** - sans ce compte, une vraie séance hors fenêtre se cacherait derrière l'exception le jour où quelqu'un marquerait tout un bloc « terminé ». ⚠️ **Corrigé au passage** : le contrôle « aucun jour vide » disait compter les seules séances du programme, et comptait en fait **toutes** les séances - une séance d'horloge tombant un jour du colloque aurait masqué le trou qu'on cherche. ⚠️ **Vérifié que le cas garde son mordant** : en repoussant la clôture à J+6 comme au temps du défaut d'origine, il échoue toujours. **1086 tests**, 2 échecs restants sans rapport (résolution de FFmpeg/FFprobe sous Windows, côté tutoriels vidéo), antérieurs. *(ce commit)*

**Les filtres de liste ne rechargent plus la page (fait)** : les huit barres de filtres du back-office (Utilisateurs, Événements, Participants, Paiements, Soumissions, Programme > Sessions **et** Créneaux, Attestations délivrées, Badges à imprimer) filtrent **en AJAX**. ⚠️ Le défaut tenait moins au rechargement qu'à ce qu'il coûtait : un champ de recherche obligeait à **taper PUIS valider** (`onkeydown` sur Entrée), et chaque essai rechargeait toute la page - focus perdu, position de défilement perdue, attente. Or chercher, c'est **tâtonner** (« aw », « awa », « awa k »), et au prix d'un aller-retour complet par essai on préfère parcourir la liste à la main. La saisie filtre maintenant **à la frappe** (pause de 350 ms, une seule requête pour huit caractères - mesuré), les listes déroulantes et les dates filtrent net. Mécanisme : le formulaire porte `data-filter="<groupe>"`, chaque région qui en dépend porte `data-filter-part="<groupe>-<nom>"` (tableau, pagination, barre d'export, **et le lien « Réinitialiser »** - il apparaît selon les filtres, donc il lui faut sa propre région). ⚠️ **Le formulaire reste hors des régions échangées** : le remplacer sous les doigts de qui tape lui prendrait le focus et replacerait le curseur au début. ⚠️ **Trouvés en chemin** : `onchange="this.form.submit()"` **contourne** toute interception (`form.submit()` n'émet aucun évènement `submit`) - un test de garde scanne désormais les vues ; les onglets de **Programme** portent **deux** barres de filtres dans le **même document**, donc les régions sont cloisonnées par préfixe, sans quoi filtrer les créneaux réécrivait aussi le tableau des sessions avec les filtres par défaut de celles-ci (vérifié : tableau des sessions **intact au octet près**) ; et comme on tape plus vite que le réseau ne répond, un jeton de fraîcheur écarte les réponses arrivées dans le désordre. ⚠️ **Les filtres restent dans l'URL** (`replaceState`, jamais `pushState` : une entrée d'historique par caractère rendrait « précédent » inutilisable). **Mesuré au navigateur** : page non rechargée, focus et curseur conservés en position 8, 20 lignes → 10, URL à jour, « Réinitialiser » apparu ; Tom Select et les dates passent par le même chemin. **4 cas Pest.** ⚠️ **Hors périmètre, et dit** : `/relectures` (espace participant) garde sa validation par Entrée - son tableau est rendu par Alpine depuis un jeu de données chargé une fois, donc échanger du HTML n'y filtrerait rien ; le bon correctif y est un filtre **client**, comme Programme > Ateliers. *(ce commit)*

**Le colloque de démonstration se tient du 5 au 9 octobre 2026 (fait)** : la fenêtre passe de 7 à 5 jours. ⚠️ **Changer `$start`/`$end` seuls ne suffisait pas** : les plénières, les blocs d'ateliers et le programme synthétique se calent par décalage en jours, et la clôture (J+6) serait tombée le **11 octobre, deux jours après la fin de l'édition**. Tout est replié (table ronde J+2, formation J+3, clôture J+4), et deux tests de garde refusent désormais une séance ou une journée de programme hors fenêtre. Trois demandes de fond ont suivi : **(1) des co-auteurs** - les 200 communications n'en portaient aucune, ce qui n'éprouvait ni la liaison auteurs ↔ comptes, ni « Mes communications » chez quelqu'un qui n'a rien déposé, ni l'association différée d'un invité ; chacune porte son correspondant, **deux co-auteurs inscrits** et, une sur trois, un **non inscrit resté « invité »** (685 liens en tout). ⚠️ Les seeders **n'appellent pas `LinkSubmissionAuthors`** : elle notifie, soit six cents e-mails et SMS pour un seed - ils écrivent les liaisons dans la forme exacte que l'action leur aurait donnée, jetons compris (déterministes, sinon les liens de déclinaison changeraient à chaque rejeu). **(2) 5 communications minimum par séance** - ⚠️ c'est ce minimum qui **dicte le nombre de blocs** : 130 acceptées sur 42 séances en laissaient 3, et une séance à trois ne démontre ni la modération, ni le rapportage, ni le passage en salle ; **24 séances** en portent 5 ou 6, et 6 × 30 min remplit exactement le bloc de 3 h. La séance se choisit **au tour de rôle** et non plus par `id % n`, qui répartissait des identifiants non contigus. **(3) la visio sur toutes les séances** - deux blocs seulement l'ouvraient. ⚠️ **Trouvés en chemin** : `updateOrCreate` ne sait pas qu'un plan a RÉTRÉCI (les 18 séances supprimées seraient restées, avec leurs créneaux orphelins - la règle des 5 aurait été fausse en silence) ; le créneau était identifié par **séance + communication**, si bien qu'un plan modifié créait un second créneau au lieu de déplacer le premier ; et `users.phone` étant **unique**, un compte semé **renommé à la main** en base de dev faisait échouer toute la transaction - 200 communications perdues pour un numéro fictif. **Mesuré après re-seed** : 5 au 9 octobre, 34 séances dont **aucune sans visio**, 24 séances d'atelier de **5 à 6 communications**, 136 créneaux **sans doublon**, 685 liens d'auteurs. **6 cas Pest** (10 sur le fichier), 1010 tests verts. *(ce commit)*

**Le nom court ne porte plus l'année, et l'écart de la marque est corrigé (fait)** : la barre collante du colloque annonçait **« Colloque Avenir durable 2025 - 2026 »**. Le code faisait son travail (`nom court - année`) ; c'est le **nom court saisi** qui portait déjà « 2025 ». ⚠️ **Et l'exemple proposé sous le champ était « CSIUK 2026 »** - il montrait la faute, donc elle a été commise. Le nom court de l'édition est corrigé (« Colloque Avenir durable »), l'exemple aussi, et le champ affiche maintenant **la ligne telle qu'elle sortira** + un avertissement si le nom finit par une année. ⚠️ Le bloc est **gardé** : c'est aussi le lien « retour en haut » et la pastille d'état, et le seul rappel du colloque une fois le bandeau passé au défilement. Enfin, dans la marque des barres latérales, le **cadre blanc du monogramme est invisible** sur fond clair : ses 6px de rembourrage s'ajoutaient aux 10px de gouttière, mettant le logo à 16px du filet quand le texte n'en était qu'à 10. Le cadre épouse l'image - **mesuré 10 / 10**. *(commit `71be90a`)*

**Le monogramme des barres latérales grossit (fait)** : 36px, il se perdait à côté du nom de la plateforme. Porté à **50px** (+39%), côté gestion comme côté participant - les deux partagent le même verrou. ⚠️ **Mesuré avant d'agrandir** : la marque dispose de 228px utiles (260 de barre moins deux fois 16 de retrait) et le nom + son sous-titre en occupent 117 ; le monogramme pouvait aller jusqu'à ~80px, 62 en laisse 37 de jeu pour un nom de plateforme plus long. **Vérifié aussi en barre réduite** (78px) : 7px de marge de chaque côté, aucun débordement, et le sous-titre n'est pas tronqué. *(commit `42378fc`)*

**La barre du colloque ne tranche plus le chatbot (fait)** : panneau Gesco ouvert, une **bande blanche le coupait en deux** - la barre collante de la page colloque (`.ukc-nav-bar`) était à `z-index: 100`, le rang des **modales**, au-dessus du panneau (55) ; « Mon espace » et FR/EN paraissaient flotter par-dessus la conversation. Elle est ramenée à **50**, l'étage de `.site-header` : c'est du décor de page, pas une surface modale. ⚠️ **Le réflexe inverse aurait été faux** : remonter le chat l'aurait fait recouvrir les modales et les slideovers, ce qu'un correctif antérieur avait précisément défait (le bouton « Confirmer » passait sous la bulle). Quand un élément collant masque une surface flottante, c'est presque toujours **lui** qui est trop haut. **Vérifié** : au point de croisement, `elementFromPoint` rend bien le champ de saisie du chat ; la barre reste collante et cliquable au-dessus du contenu défilé. *(commit `b95bc4d`)*

**Le jour du programme est posé sur une surface (fait)** : le panneau s'étalait sur **toute** la largeur du conteneur (1316px mesurés à 1391px de fenêtre), sans respiration latérale - la date collée à gauche, « 1 moment » collé à droite, à quatorze cents pixels l'un de l'autre, alors que les onglets au-dessus sont centrés et que les autres sections du colloque vivent dans des cartes. Le jour devient une **carte blanche** (≈30px de marge intérieure) et le bloc est **borné à 960px** : passé ~900px, l'œil perd la ligne entre l'heure et son libellé. ⚠️ L'aperçu de l'onglet admin **garde la même surface**, sur un fond teinté - sa raison d'être est de montrer exactement ce qui est publié. ⚠️ **Et il prend enfin toute la carte** : il était bridé à 760px dans une carte de 1114, soit plus de quatre cents pixels de vide à droite ; c'est désormais la largeur de la page publique qui décide, et c'est bien elle qu'il doit montrer. ⚠️ **Mesuré** : à 20px de rembourrage, sept onglets de jour faisaient 958,7px dans une piste de 960 - un pixel de marge en public, 13px de trop dans l'aperçu admin (le zoom 90% ne rend pas les mêmes largeurs), où « Jour 7 » tombait seul sur une seconde ligne. À 16px, les deux gardent une centaine de pixels de jeu. *(commits `8837b8f` + `130703c`)*

**Le programme synthétique passe au JOUR (fait)** : les onglets publics reprenaient un bloc de saisie chacun - d'où huit pastilles dont quatre « Matin », et aucun moyen de retrouver le 3 octobre. Un onglet = **un jour** (« Jour 2 / Sam. 03 oct. »), et les blocs d'une même date s'y rangent en **intertitres** (« Matin », « Après-midi »). Présentation revue : en-tête du jour avec la date en toutes lettres et le nombre de moments, filet de couleur par nature (plénière, atelier), pauses en retrait. ⚠️ **Jamais trié** - l'ordre reste celui que l'admin a rangé à la flèche ; un bloc sans date garde son propre onglet, rien ne disant à quel jour il appartient. ⚠️ **Mesuré** : le surtitre « Jour N » à `opacity:.72` tombait à 2,8:1 sur les onglets inactifs - l'opacité est retirée, tout est ≥ 7,5:1. **2 cas Pest.** *(commit `0f18e24`)*

**Le formulaire des partenaires repris (fait)** : la ligne était quatre champs sans libellé autour d'un carré gris marqué « LOGO ». Elle reprend la grammaire des autres répéteurs de la fiche - une carte, un aperçu à gauche, des champs **étiquetés** au centre (une indication de saisie disparaît dès qu'on tape : plus rien ne disait laquelle des deux adresses était celle du site), la pastille destructive **partagée** avec le programme. L'aperçu **reproduit le rendu public** (logo, ou nom à défaut) et se replie si l'URL du logo ne charge pas - une erreur fréquente qui affichait l'icône d'image cassée. ⚠️ **Le champ « Niveau » ne sort nulle part** : le bandeau public n'affiche que logo, nom et lien ; il porte désormais une pastille « interne » - à vous de dire s'il faut l'exploiter ou le retirer. ⚠️ **Harnais** : à 375px, la grille à trois colonnes réduisait les champs à 144px et tronquait les noms longs au milieu d'un mot ; sous 560px l'aperçu prend toute la ligne. ⚠️ **Piège Blade** : `@error` sur une image est compilé comme la directive de validation Blade - on écrit `x-on:error`. *(commit `b35d6ad`)*

**Réorganisation des onglets de l'édition (fait)** : **« Intégrations »** réunit *Paiement* et *Visioconférence* - deux formulaires de même nature (brancher l'édition sur un service extérieur) qui faisaient chercher au même endroit deux fois ; chacun garde sa sauvegarde, les fondre ferait d'une correction de clé d'API une réécriture des deux configurations. **« Traductions » rejoint « Apparence »**, les deux faces de ce que le visiteur voit du site public ; le panneau reste hors du formulaire principal (une locale par formulaire) et le dit, sinon on croit que « Enregistrer » les emporte. ⚠️ Les **anciens liens** (`?tab=payment|meetings|translations`) sont traduits vers les onglets qui les ont repris - sans cela, un favori ouvrait une page sans aucun panneau affiché. ⚠️ **Piège Blade payé deux fois** : écrire les directives de bloc PHP en toutes lettres dans un commentaire Blade les fait exécuter - Blade extrait les blocs de code AVANT de retirer les commentaires - et la page tombe sur une erreur très loin de sa cause. Le fichier n'utilise que la forme en ligne. **3 cas Pest.** *(commit `0f18e24`)*

**Le papier côté bénéficiaire : spécimen, et toutes ses attestations (fait)** : en délivrance physique, le participant **voit** son attestation dans son espace et peut la **vérifier**, mais ne peut ni la télécharger ni la partager - l'exemplaire qui fait foi est celui qu'on lui remet, et deux documents identiques ne doivent pas circuler. Le document affiché est **celui qui sera imprimé** - même modèle, même moteur, même géométrie - **plus la mention SPÉCIMEN** posée par-dessus ; un encadré dit pourquoi, et la ligne indique « Remise le … » ou « À retirer au secrétariat ». ⚠️ Un cas gèle la fidélité (géométrie **et** texte identiques entre l'écran et le PDF) ; seule exception documentée, le QR est un SVG à l'écran et des blocs dans le PDF, dompdf ne sachant pas rendre le SVG. ⚠️ `/attestation/{uuid}/pdf` est **fermé au public** en mode papier (sinon la restriction serait cosmétique) mais **ouvert au back-office**, qui imprime. ⚠️ Le filigrane se pose **par-dessus** le rendu, jamais dans la mise en page : le même modèle sert au lot imprimé. **Harnais** : `font-size: 13%` se calcule sur la police héritée et non sur la largeur - le filigrane restait figé à 42px ; il suit maintenant `--doc-w`. Et « Mes documents » liste enfin **toutes** les attestations : une par communication (elles étaient indexées par type, donc la dernière s'affichait en face de toutes) et une **par formation suivie ou animée**, avec son intitulé figé à l'émission. **5 cas Pest.** *(commit à venir)*

**Le volet papier des attestations (fait)** : le mode physique existait sans le geste qui le rend tenable - il fallait ouvrir les attestations **une par une** à leur adresse de vérification (deux cents communications, deux cents onglets). Quatre ajouts : **lot PDF** prêt à imprimer (une par page), **liste d'émargement**, **remise en main propre** tracée (`delivered_at`), et un **garde-fou chiffré** avant l'émission en masse. ⚠️ Les exports portent sur **les filtres de l'écran** (`AttestationQuery`, partagé avec la liste) : on imprime ce qu'on regarde. ⚠️ **Un seul type par lot** - la taille de page vient du modèle, et mêler A4 paysage et A5 portrait sortirait rogné. ⚠️ **Lots bornés à 50** : un millier d'attestations d'un coup épuiserait la mémoire du générateur au milieu du rendu. ⚠️ La remise est **réversible** (se tromper de ligne au guichet est banal), contrairement au passage d'une communication. **Défaut corrigé au passage** : le bouton « Émettre & générer les PDF » ne générait aucun PDF - il émettait et rendait la main. **9 cas Pest.** *(commit à venir)*

**Attestations : en ligne ou sur papier, et le passage qui les ouvre (fait)** : une édition choisit son **mode de délivrance** (`AttestationPolicy`, en ligne par défaut). **En ligne**, le modérateur **valide le passage** d'une communication pendant la séance et ses auteurs reçoivent aussitôt leur attestation (in-app, e-mail, SMS si ouvert), chacun la retrouvant dans « Mes documents ». **Sur papier**, le passage se constate quand même mais **rien n'est émis ni envoyé** : le secrétariat émet le lot et l'imprime - l'attestation reste consultable dans l'espace du participant. Nouvelle option : l'attestation de communication va à **tous les auteurs concernés** ou aux **seuls pointés présents**. ⚠️ Le passage devient un **fait tenu en base** (`slots.presented_at`) : la salle le marquait déjà, mais seulement dans les navigateurs - la marque mourait avec la séance. Deux portes (visio **et** page de modération, pour les séances en salle), un seul contrôleur, et le créneau doit appartenir à la séance - sinon un modérateur enverrait les attestations d'une communication tenue ailleurs. ⚠️ Irréversible et idempotent : une attestation signée ne se reprend pas, l'erreur se révoque au back-office. ⚠️ **SMS fermé par défaut** : chaque envoi est facturé. **8 cas Pest.** *(commit à venir)*

**Retour direct dans la visio depuis la page de présence (fait)** : on vient d'y pointer des gens pendant qu'on anime, et repasser par la liste des séances était un détour. ⚠️ L'onglet de la salle **porte un nom** (`window.name`, via `MeetableRegistry::tabFor()` - une seule définition partagée) et le lien de retour le pose en `target` : le navigateur réutilise l'onglet existant au lieu d'ouvrir une **seconde salle** (deux connexions, deux vignettes, de l'écho). Mieux : si la page a été ouverte depuis la salle, `window.opener` permet de remettre cet onglet devant **sans recharger** la salle - un rechargement ferait sortir puis rentrer le modérateur sous les yeux de tous. D'où le retrait délibéré de `rel="noopener"` sur ce lien (page nôtre, même origine). ⚠️ `focus()` sur un autre onglet peut être **ignoré** : on vérifie 400 ms plus tard que la page a bien été masquée, sinon on suit le lien - un bouton qui ne fait rien serait pire que le rechargement qu'on évitait. **1 cas Pest.** *(commit à venir)*

**Présence : deux verdicts, et les étiquettes filtrent (fait)** : « Excusé » est retiré - il ne pesait **rien en aval** (seul `present` compte dans le PV et dans l'attestation) et ajoutait un troisième choix à trancher pendant qu'on appelle des gens à la voix. Les lignes déjà posées sont versées dans `absent` (migration `2026_10_04_000100`) et **non** remises à « non pointé » : un modérateur avait regardé sa salle, c'est un verdict, pas un blanc. Un motif d'absence se dit désormais dans le compte rendu. ⚠️ `SessionAttendance::STATUSES` est la source unique - les deux contrôleurs valident sur ses clés, donc y remettre une valeur l'ouvrirait partout, salle comprise. Les quatre étiquettes (Attendus, Présents, Absents, À confirmer) **filtrent la liste** : on lit « Absents 3 » et on veut voir lesquels. « Attendus » est le chemin de retour, non pointés compris. ⚠️ « À confirmer » peut disparaître sous le doigt - c'est souvent le dernier clic qui l'escamote - alors le filtre **se replie** sur « Attendus », sinon il resterait une liste vide et plus aucune étiquette pour en sortir. **4 cas Vitest + 1 Pest.** *(commit à venir)*

**La présence se tient depuis la salle, et le pointage automatique se confirme (fait)** : le verdict actif ne se **voyait pas** - les boutons portaient bien `.on`, mais pas l'attribut `data-st` dont dépend la couleur, si bien que le modérateur voyait « Présents 1 » au-dessus de trois boutons identiques. Corrigé, et la question de fond tranchée : un pointage posé par la visio compte **déjà** dans « Présents » (rien n'est perdu si personne ne le confirme) mais s'affiche **à mi-chemin** - bouton en pointillé, mention « entré en visio - à confirmer », pastille « À confirmer N » qui disparaît une fois tout vu. Un clic du modérateur le remplit : la ligne passe de sa main (`source` → `manual`, `marked_by` posé), parce que c'est lui qui endosse le chiffre dans le PV. ⚠️ Sur une ligne automatique, le même clic **confirme** au lieu de retirer - sans quoi le geste le plus naturel effaçait la présence. Retirer un pointage sur quelqu'un encore en salle le fait revenir en pointillé : le fait machine reste vrai, seul l'aval a été retiré ; pour écarter quelqu'un on pose « Absent », que rien n'écrase. **5 cas Vitest + 1 Pest.** *(commit à venir)*

**Tableau : icônes, refaire, et le crayon au présentateur (fait)** : la barre du tableau passe en **icône + mot** (même grammaire que la barre de la salle ; le mot s'efface sous 640px, `title`/`aria-label` restent). **« Refaire » a été ajouté** - on annulait un trait et rien ne le rendait ; il ne rend que **ses** traits, repart **entier** (les autres postes l'avaient retiré), et sa pile se vide dès qu'on redessine ou que quelqu'un efface. ⚠️ **Le crayon suit la PAROLE, plus le rôle** : le serveur donne `presenter` à chaque auteur de communication, donc les six communicateurs d'une séance pouvaient tous écrire sur le diaporama de celui qui parle. Il va maintenant à qui **présente en ce moment** (parole donnée, ou partage d'écran) ; le modérateur le garde, sans quoi le tableau blanc serait inutilisable tant que personne ne présente. C'est une règle de **conduite** : la frontière vérifiable reste le rôle signé dans le jeton - qui tient la parole se décide sur le canal de données et ne s'authentifie pas, et un récepteur qui filtrerait là-dessus perdrait des traits légitimes en silence. Le **harnais** a trouvé un défaut de contraste : la pastille du mode actif paraissait vide (bleu foncé sur fond sombre, deux règles de même spécificité qui se marchaient dessus) - contraste remonté à 8,4:1. **6 cas Vitest** (70 au total). *(commit à venir)*

**Les échanges sortent du panneau (fait)** : ils avaient leur onglet dans la colonne latérale, partagée avec le fil, les personnes et la présence - 400px ne tiennent pas une file de questions ET des sondages. Ils ont maintenant leur **bouton** (`#btnEch`, avec pastille) et leur **surface** (`#ech`) : deux colonnes au-dessus de 860px, empilées en dessous, chacune avec son propre défilement. Améliorations : ⚠️ **tri serveur, ce qui attend passe devant** (une question répondue mais très votée restait en tête de file toute la séance) ; **filtres** Toutes / Sans réponse / Les miennes, avec un vide filtré qui se dit autrement qu'un vide réel ; **pastille** qui ne compte que ce qui attend et respire trois fois quand une question arrive surface fermée ; **deux rythmes de relecture** (10 s ouverte, 45 s fermée - fermée on relit quand même, sinon la pastille mentirait). **3 cas Vitest + 2 Pest** (64 / 953). *(commit à venir)*

**Annoter la présentation (fait)** : le tableau gagne un mode **Annoter** - la couche devient transparente et le cadre se cale sur l'**image présentée** et non sur un rectangle 16/9 (un diaporama 4/3 annoté dans un cadre 16/9 verrait toutes ses flèches décalées). Le mode est **deviné à l'ouverture** : un écran partagé s'ouvre en annotation. ⚠️ La salle force `object-fit:contain` pendant ce temps - en `cover` l'image est rognée, et pas pareil selon le format de chaque fenêtre : la flèche de l'un tomberait à côté chez l'autre. ⚠️ L'**export compose** la présentation PUIS les traits, à la taille intrinsèque de la vidéo. Mesuré au harnais : alignement à moins de 2px sur un diaporama 4/3, rapport exact. **4 cas Vitest** (61 au total). *(commit à venir)*

**Deux corrections après retour d'usage (fait)** : *(1)* ⚠️ **la salle se coupait plus vite qu'avant, et c'était ma faute.** Le défaut de qualité vidéo avait été porté à **720p pour toute la plateforme** : les trois couches simulcast additionnées passaient d'environ **1,45 Mb/s à 2,35** de liaison montante, et sur un réseau modeste ce n'est plus une image qui saccade, c'est la salle qu'on perd. Le défaut revient à **`md` (960x540, 24 i/s)**, qui est *exactement* ce que la salle faisait avant d'être réglable ; « Standard » (720p) et « Haute » (1080p) restent à un clic dans l'onglet Visioconférence de l'édition. Ajout de `degradationPreference: 'balanced'`, pour que l'encodeur puisse céder sur la définition **et** sur le mouvement au lieu de s'obstiner. Un cas Pest fige la régression. *(2)* **L'écran d'entrée ne se repose plus à chaque actualisation** : reposé à chaque venue il devenait un péage, franchi sans rien lire, et traversé au pire moment - juste après avoir perdu la salle. `entreeDirecte` laisse entrer si on est déjà passé par là **pour cette salle** et que les autorisations tiennent ; ⚠️ `prompt` n'est pas `granted`, donc l'écran revient dès qu'une autorisation n'est plus acquise (c'est précisément là qu'il sert, et qu'il explique le cadenas). **4 cas Vitest** + 1 Pest (57 / 951). *(commit à venir)*

**Tableau blanc (fait)** : un bouton **Tableau** ouvre une surface de dessin partagée qui recouvre la scène chez tout le monde - crayons, gomme, épaisseur, annuler, effacer, et **enregistrement en image**. Tout passe par le canal de données : **rien n'est envoyé au serveur**, le tableau vit le temps de la séance (le jour où il doit rejoindre le compte rendu, c'est un point d'entrée à ajouter, pas ce module à refaire). ⚠️ **Le cadre a un rapport fixe (16/9) et se centre** : un tableau est un document, il doit être identique partout - normalisé sur la scène de chacun, un cercle tracé sur un portable deviendrait une ellipse sur l'écran d'à côté. ⚠️ Réservé à qui **anime** (le tableau cache les visages de toute la salle), et le récepteur le vérifie ; `traitRecu` assainit en plus le CONTENU (couleur de la palette, épaisseur bornée, coordonnées dans le cadre, `Infinity` écarté - il passe `!isNaN` et fige le canevas de tout le monde). ⚠️ C'est l'**arrivant** qui demande le tableau, pas le propriétaire qui devine : l'instantané partirait sinon avant qu'il n'écoute. L'instantané se découpe **par points**, sans quoi il dépasse la taille d'un message et n'arrive pas du tout. Le **harnais visuel** a trouvé deux défauts : la barre d'outils et le titre recouvraient le bas de la zone de dessin, et `left:50%` en positionnement absolu ne laissait à la barre que la moitié de la largeur, la repliant sur deux lignes. **12 cas Vitest** (53 au total). **À décider** : faut-il garder le tableau dans le compte rendu de la séance, comme la question posée pour le fil de discussion ? *(commit à venir)*

**Réagir dans la salle (fait)** : un bouton **Réagir** à côté de « Main » envoie un emoji qui **monte sur l'écran de présentation** chez tout le monde, et qui se pose **en même temps sur la carte de qui a réagi** (opacité réduite, coin bas-droit) - c'est la carte qui désigne la personne, pas un nom accroché à la bulle. Tout se joue sur le canal de données : rien n'est envoyé au serveur, rien n'est conservé. ⚠️ Le canal étant public, le **récepteur** filtre l'emoji sur la liste connue (`MeetingMessage::EMOJIS`) - sinon n'importe quel connecté afficherait en grand, chez tout le monde, le texte de son choix. ⚠️ L'anti-rafale s'applique **des deux côtés**, par expéditeur. ⚠️ `REACTIONS_MAX` et `placeBulle` sont solidaires : autant de places que de bulles vivantes, sinon deux bulles encore à l'écran se superposent exactement. Le **harnais visuel** a trouvé trois défauts qu'aucune relecture n'avait vus : un `*/` en trop qui cassait **toute la feuille de style** à partir du bandeau, des rangées trop serrées, et - à 375px - un emoji posé sur le micro parce que la règle du doigt venait **avant** celle du bandeau, à spécificité égale. **6 cas Vitest** (41 au total).

**Le nom du bandeau revient en douceur (fait)** : au survol, le nom se dépliait en .18s linéaire - un à-coup. Il s'ouvre maintenant en **.32s** sur une courbe qui décélère, l'**opacité en retrait** sur la largeur (sans quoi le texte apparaît d'un bloc dans une pastille encore en train de s'ouvrir), et **.12s de délai** pour qu'en longeant le bandeau on n'allume pas les cinq cartes au passage. Le repli, lui, reste court (.16s) : on a quitté la carte, on ne l'attend plus. ⚠️ Au clavier le délai tombe à 0 - la tabulation a visé, elle n'a pas longé. Mesuré au harnais : 62 → 117 → 139 → 147 → 150px. *(commit à venir)*

**Visioconférence par édition + qualité vidéo (fait)** : sur le modèle de la passerelle de paiement, un onglet **Visioconférence** dans la fiche d'édition permet de choisir le fournisseur, de renseigner les identifiants du serveur (LiveKit / Jitsi) et de régler la **qualité vidéo**. `edition.settings['meetings']`, seules les valeurs remplies conservées, repli global pour le reste (`App\Support\EditionMeetingConfig`). ⚠️ Toutes les portes d'entrée passent par `using()`, qui **restaure** ensuite : une requête peut toucher plusieurs éditions, et sans restauration une salle s'ouvrirait sur le serveur d'un autre colloque. Le socle commun l'applique dans `JoinsMeetings::avecReglagesVisio()` ; l'**Egress**, l'**occupation réelle de la salle** et le bouton d'enregistrement le font explicitement. Nouveau au contrat : `Meetable::meetingEdition()`. **Qualité** : ⚠️ la salle plafonnait à **960x540 à 24 i/s**, et c'était la couche simulcast la plus haute - rien ne pouvait y être plus net, quelle que soit la caméra. Défaut porté à **1280x720 à 30 i/s**, avec un **débit explicite** (laissé au SDK, il se calait sur l'ancienne définition et une capture plus fine serait sortie en bouillie). Les couches basses (180p, 360p) restent publiées : elles servent les vignettes et les connexions faibles, et rendent la couche haute abordable. Le coût retombe sur la liaison **montante** de qui diffuse, d'où le réglage par édition (« Économe » 360p pour un réseau modeste). **13 cas Pest** + **3 cas Vitest** (35 au total). *(commit à venir)*

**Les échanges entrent dans la salle (fait)** : questions votées et sondages se tenaient sur une **autre page**, atteinte par un lien de la barre du haut de la visio - poser une question obligeait à **quitter la visio**, donc à cesser de voir et d'entendre ce dont on voulait parler. Ils sont maintenant un **onglet « Échanges »** du panneau (sondages en cours puis file des questions). ⚠️ **Aucune règle n'est réécrite** : la salle appelle les **mêmes points d'entrée JSON** que la page autonome (`SessionEngagementController`), posés sous la même garde `canJoin()` ; c'est le serveur qui dit qui peut poser, voter, modérer. La **page autonome demeure** (séance en présentiel sans visio) ; seul le lien **dans la salle** disparaît. Rafraîchissement toutes les 10 s **panneau ouvert sur cet onglet seulement**, plus une annonce sur le canal de données **bridée à une relecture toutes les 2 s** (n'importe qui peut émettre dessus). Compteur de l'onglet = questions **en attente**. ⚠️ Les onglets du panneau **défilent désormais à toutes les largeurs** : ils sont jusqu'à cinq, et « Communicateurs » à 80px se serait coupé en plein mot. **2 cas Pest** de plus dans `MeetingTest` (adresses présentes pour qui peut entrer, absentes sinon, lien sortant disparu). *(commit à venir)*

**Espace modérateur : fermé, renommé, et retour de visio (fait)** : **(1)** la page n'était gardée que par `auth:account` - **tout compte connecté l'ouvrait**, vide mais ouverte. Elle exige désormais d'avoir une séance confiée (`abort_unless(isModeratorSomewhere)`). **(2) Renommage** : `/animation` → **`/moderation`**, « Séances à animer » → **« Séances à modérer »**. Le rôle s'appelle modérateur partout ailleurs (`moderator_email`, `MeetingRole::Moderator`, `ModerationController`) ; « animer » était le seul endroit à employer un troisième verbe. **« Séances » et non « Sessions »** : c'est le mot du projet (« Séances à rapporter », `/seances/{id}/presence`) et le français pour une séance de colloque. Ancienne adresse en **redirection**. **(3)** Quitter la visio ramène le modérateur à **ses séances** au lieu de la page publique de déroulement. ⚠️ Le test porte sur `EventSession::isModeratedBy()` (séance confiée) et **non sur le rôle en salle** : le staff y est modérateur aussi et n'a pas d'espace personnel - il atterrirait sur un 403. ⚠️ Le contenu suit le colloque sélectionné, **sauf** quand la sélection ne veut rien dire : un modérateur est rapproché par e-mail et n'est pas forcément inscrit à l'édition qu'il modère, donc le sélecteur ne la lui proposerait jamais et la page resterait vide à jamais. **8 cas Pest** (`ModerationPageTest`) ; un ancien test qui attendait l'état vide attend maintenant le 403. *(commit à venir)*

**Visio - le nombre de connectés se voit (fait)** : il ne se lisait **nulle part** en mosaïque - le compte du bandeau (« Participants (N) ») n'existe qu'en vue présentateur et seulement une fois le bandeau déplié. Pastille dans la **barre du haut**, qui ne disparaît dans aucune disposition ni à aucune largeur. ⚠️ Le compte vient de `people` (qui suit arrivées et départs) et non du nombre de vignettes, lequel dépend de ce qui est affiché. Le chiffre se lit seul, la phrase qui le nomme est dans `title`/`aria-label` (`phraseConnectes`, accord du pluriel testé). **2 cas Vitest** (32 au total). Largeurs vérifiées au banc d'essai en plein écran : à 375px le titre de la séance garde 141px, à 320px il en garde 86, sans débordement - à condition que « Questions & sondages » soit bien parti dans la feuille « Plus d'options », ce que fait `rangeSecondaires` sous 767px. *(commit à venir)*

**Visio - le nom se replie dans le bandeau (fait)** : sur une carte de 150px, l'étiquette du nom occupait presque toute la largeur pour un nom tronqué au tiers (« Modeste Mod… »). Ne reste que l'état du micro - c'est ce qu'on surveille là - et le nom revient **au survol** de la carte, comme avant. ⚠️ `max-width` et non `display:none` : replié, pas retiré, donc toujours lu par un lecteur d'écran et trouvable par la recherche du navigateur ; `focus-within` pour le clavier ; et **au doigt, le nom reste affiché**, le survol n'y existant pas. Vérifié au banc d'essai, survol compris. *(commit à venir)*

**Visio - audit visuel, quatrième lot : entrer et tenir l'horaire (fait)** : les deux points qui demandaient un arbitrage. **(1) Écran d'entrée** avant la connexion - aperçu caméra, **niveau du micro** (la seule preuve qu'il entend quelque chose), choix des périphériques, mémorisés. On tombait dans la salle et la demande d'autorisation du navigateur arrivait par-dessus une séance déjà commencée. ⚠️ Réservé à qui **diffuse** : pour un auditeur ce serait un mur sans objet. ⚠️ **Correction d'une hypothèse fausse** : je croyais que les communicateurs entraient muets - la salle ouvrait en réalité **caméra ET micro d'office**. Les défauts de l'écran reprennent donc exactement ce comportement ; il ne change rien, il le montre et le rend révocable avant que cela n'arrive. ⚠️ L'aperçu est arrêté **avant** de connecter, sinon la caméra reste prise et LiveKit ne peut plus l'ouvrir. **(2) Temps de parole** : la durée prévue vient du **créneau du programme** (`minutes`, nouveau dans `scheduledCommunications()`, `null` sans horaire de fin), l'alerte tombe au dernier cinquième et non au dépassement - à ce moment-là la personne conclut déjà. ⚠️ Le modérateur tient le compte et diffuse un **nombre de secondes**, pas une heure de départ : deux machines n'ont pas la même horloge. ⚠️ `floor_time` est **réservé au modérateur**, et le chronomètre n'est visible que pour lui et pour qui a la parole. **4 cas Vitest** (30 au total) et **2 cas Pest** (37 dans MeetingTest). ⚠️ **Vérifié au banc d'essai** (écran d'entrée et les quatre états du chronomètre, avec la feuille de style réelle) ; **pas en salle réelle** - l'aperçu demande une caméra et le chronomètre un modérateur qui donne la parole. *(commit à venir)*

**Visio - audit visuel, troisième lot : confort et accessibilité (fait)** : **(1) `prefers-reduced-motion`** était ignoré dans la salle alors que le reste du projet le respecte - deux animations tournaient **en boucle** toute la séance (halo du bouton d'enregistrement, pastille d'état). Rien n'est caché, seul le mouvement s'arrête. **(2) Ce que la salle énonce** : `role="status"` sur l'infobulle et l'état de connexion, `role="alert"` sur le bandeau de reprise - « Votre micro a été coupé par le modérateur » ne parvenait à personne qui ne regarde pas l'écran. **(3) Échap** ferme désormais ce qui est ouvert, du plus intérieur au plus extérieur (confirmation, choix de réaction, menus, réponse, puis le tiroir sous 900px) ; il ne fermait que la confirmation. **(4) Le choix de réaction** bascule sous le message quand le cadre du fil le rognerait. ⚠️ Mais **l'audit se trompait** sur ce dernier point : il n'est PAS rogné sur le premier message du fil, les 14px de marge l'absorbent (mesuré dans le navigateur). Le vrai cas est un fil remonté où le bord haut d'un message affleure celui du panneau : le message reste lisible, on clique « Réagir » dessus, et le choix part 4px au-dessus du cadre. Le correctif vaut donc, pour ce cas-là. Bloc `prefers-reduced-motion` vérifié **parsé** par le navigateur (4 règles, sélecteurs relus), pas seulement écrit. *(commit à venir)*

**Visio - audit visuel, second lot : ce que la salle doit distinguer (fait)** : **(1) une teinte par personne**, tirée de son nom - les pastilles portaient toutes le même dégradé bleu-vert, et huit caméras fermées faisaient huit disques identiques. Seule la teinte varie, la clarté reste basse pour que les initiales blanches gardent leur contraste, et un seul `poseAvatar()` sert les trois pastilles (vignette, liste, bulle). **(2) Le rôle sur la vignette** : les tuiles distantes n'en portaient aucun, rien ne disait qui animait la séance ; pastille pour le modérateur et le communicateur seulement, masquée dans le bandeau. **(3) La ligne de la liste** se range par un écart au lieu d’un point médian - quatre séparateurs, c'était 40 px pris au nom. **(4) La barre d'outils porte des mots** (`data-nom`), les intitulés ne vivant que dans `title`, qui n'existe pas au doigt : sur téléphone, six ronds muets. Les mots s'effacent en téléphone couché. **1 règle pure de plus et 3 cas Vitest** (26 au total). ⚠️ **Un piège de cascade attrapé en lisant les couleurs calculées** : `.person .meta span` sans chevron est plus spécifique que les règles du rang, du micro et du réseau qu'elle contient, et les repeignait en gris (rang gris sur fond or) - c'est `> span`. ⚠️ Deux « états vides » annoncés à l'audit **n'existent pas** : le fil porte toujours son message d'ouverture et la liste contient toujours soi-même ; seul le panneau des communicateurs pouvait s'ouvrir nu. ⚠️ **Reste** : dans la vue modérateur, la ligne d'une personne qui a la main levée ET un réseau dégradé déborde encore de 9 px sur 400 px (deux boutons d'action) - la poignée de largeur du panneau la remet sur une ligne. *(commit à venir)*

**Visio - audit visuel, premier lot : ce que la salle doit montrer (fait)** : quatre manques repérés à la relecture du gabarit et du câblage, et qui ne tenaient pas à une règle mais à ce que l'œil cherche en séance. **(1) Qui parle** - `ActiveSpeakersChanged` n'était écouté nulle part : en mosaïque à six tuiles, on coupait quelqu'un sans le savoir. Liseré vert sur la vignette et ligne teintée dans le panneau ; la règle vient APRÈS `hand-up` (une main levée est une demande, la parole est un fait) et la fonction déplace une classe au lieu de reconstruire la liste. **(2) Son propre reflet** - aucun `scaleX(-1)` dans tout le module : on se voyait inversé, et lever la main droite l'affichait à gauche. Jamais sur un partage d'écran ni sur l'image des autres ; la bulle PiP porte tantôt sa caméra tantôt celle d'un présentateur distant, d'où la classe `soi`. **(3) Le réseau** - `ConnectionQualityChanged` n'était pas écouté : une image gelée ne disait pas qui décrochait. Rien ne s'affiche tant que tout va bien, et **deux dessins** au lieu d'un seul recoloré (à 13 px la couleur ne sépare pas « instable » de « perdu »). Pour soi, l'information remonte dans la pastille du haut, sauf pendant une reprise - la salle annoncerait « Connecté » en train de se rattraper. **(4) Le fil ne s'arrache plus** : il ne suivait le bas qu'en forçant, on remontait lire et un message arrivait. Il ne suit que si on y était déjà, sinon un bandeau annonce ce qui attend. 3 règles pures de plus et **5 cas Vitest** (23 au total). ⚠️ Vérifié sur un banc d'essai bâti avec la feuille de style RÉELLE (états qu'il faudrait sinon trois personnes et un mauvais réseau pour obtenir) ; **pas en salle réelle** - le liseré de parole et le réseau dégradé demandent plusieurs participants. ⚠️ **Reste** : la ligne du panneau passe sur deux lignes quand s'accumulent rang, micro, réseau et deux boutons dans 400 px (densité d'avant ce lot, candidate au §2). *(commit à venir)*

**Visio - audit, quatrième lot : les règles de la salle deviennent vérifiables (fait)** : ajout de **Vitest** (`npm run test:js`), premier outillage de test JS du projet - **aucune ligne de JavaScript n'était couverte**. Les règles de la salle qui ne dépendent ni du DOM ni de LiveKit sortent de la fermeture de `meeting.js` (~1 600 lignes) vers **`resources/js/meeting-pure.js`** : initiales, troncature, identifiants, lecture de la métadonnée du jeton, **table des ordres autorisés**, file des mains levées, grille de la mosaïque. **18 cas** dans `meeting-pure.test.js`, chacun figeant un défaut constaté en salle réelle (ordre forgé par un simple participant, quatre visages écrasés sur une rangée, rang de main laissant un trou, nom hostile dans des initiales). `meeting.js` ne garde que des adaptateurs. ⚠️ `meeting.js` ne passe pas `prettier --check` et ne le passait pas avant : le reformater en bloc noierait toute relecture ; les fichiers neufs sont conformes. ⚠️ **Reste** : le gros de `meeting.js` (câblage DOM et LiveKit) demeure non couvert - un découpage en modules serait le prochain pas, à faire à froid et non pendant une série de demandes. *(commit à venir)*

**Visio - audit, troisième lot : ce qu'un fil conservé exige (fait)** : (1) **messages non lus** - compteur sur l'onglet Chat et pastille sur « Panneau », le tiroir étant fermé par défaut sous 900px (le fil rejoué ne compte pas) ; (2) **modération** (`meeting.chat.delete`, modérateur de la séance) - un message déplacé resterait sinon pour toutes les prochaines arrivées ; le retrait part aussi sur le canal de données et disparaît en direct ; (3) **export texte du fil** (`meeting.chat.export`, modérateur) pour le compte rendu de séance ; (4) ⚠️ **rattachement d'une communication à son auteur par identifiant** - la métadonnée du jeton porte désormais `accountId`, là où `matchComm` cherchait une sous-chaîne du nom de famille : deux homonymes suffisaient à donner la parole à la mauvaise personne (le nom reste en filet pour les co-auteurs sans compte) ; (5) **micro en icône** dans la liste des participants, texte conservé en `title`/`aria-label` ; (6) `#chatLog` passe en zone `role="log" aria-live="polite"`. Tests dans `MeetingChatTest` (retrait réservé, export réservé, `accountId` dans la métadonnée). ⚠️ **Reste du §4** : `meeting.js` fait ~1 600 lignes dans une seule fermeture et **aucun test JS ne le couvre** - décision d'outillage à prendre (Vitest). *(commit à venir)*

**Visio - audit, second lot : tenue en conditions réelles (fait)** : (1) **délai sur l'API Egress** (`connectTimeout(5)`/`timeout(15)`) - un service d'enregistrement muet immobilisait le worker PHP sans limite ; (2) **reprise après coupure** - `Disconnected` veut dire que le SDK a renoncé, et il ne restait qu'à recharger la page : un bandeau propose « Reconnecter », qui **redemande un jeton** (route `meeting.token`, droits recalculés, car celui d'origine expire au bout de 2 h) et republie les pistes locales. ⚠️ Défaut trouvé **en essayant** : après reconnexion il faut reprendre l'état de la salle (`adopteParticipantsPresents`), les événements d'arrivée ne rejouant pas pour les présents - sans quoi la salle revenait vide de tout le monde sauf de soi ; (3) **débit limité par personne et non par IP** (`visio-chat` 30/min, `visio-reaction` 60/min) - une cérémonie publique se suit depuis le campus, un limiteur par IP ferait taire toute la salle ; (4) **uid unique par salle** + envoi **idempotent** - un réseau qui bégaie créait deux messages de même identifiant, qu'une réaction ne savait plus départager ; (5) **écriture réservée à la séance en cours** (la lecture reste ouverte à qui peut entrer). Nouveau `App\Meetings\MeetableRegistry` : seul endroit qui traduit URL ↔ objet. *(commit à venir)*

**Visio - audit de sécurité, premier lot (fait)** : trois défauts trouvés en auditant le module. (1) **XSS stocké** : les listes participants / communicateurs / présence se construisaient par concaténation en `innerHTML` avec des noms et des titres venus de la base, alors que le profil n'impose qu'une longueur maximale - un inscrit nommé `<img src=x onerror=…>` exécutait son code chez **tous les participants** et dans le panneau du modérateur. Tout passe par `elt()` (`textContent`) ; la boîte de confirmation, seul composeur de balisage restant, échappe via `texteEchappe()` ; `initials()` filtre aux lettres et chiffres. **Vérifié charge en place** : rendu littéral, aucun élément injecté, titre de page intact. (2) **Ordres non vérifiés** : `onData` exécutait `mod_mute`, `mod_role`, `comm_done`, `recording` **sans regarder l'émetteur** - n'importe quel participant pouvait couper le micro de toute la salle depuis sa console. `ordreRecevable()` exige désormais le rang lu dans la **métadonnée du jeton** (`roleAnnonce`), jamais `people[].role` que ces mêmes messages modifient. (3) **Réactions anonymes confondues** : tous les visiteurs sans compte partageaient la clé `anon`, si bien que le second **retirait** la réaction du premier ; clé désormais dérivée (hachée) de la session HTTP. Tests dans `MeetingChatTest`. ⚠️ **Reste à traiter** (§2 de l'audit) : délai sur l'API Egress, renouvellement du jeton et reconnexion après coupure, limitation de débit du chat, unicité `(salle, uid)`, écriture hors séance. *(commit à venir)*

**Visio - le fil de discussion se retrouve, et se commente (fait)** : le chat ne vivait que sur le canal de données de LiveKit, donc **rien n'était conservé** - qui rejoignait en cours de séance, ou rechargeait sa page, trouvait une salle muette. Nouvelle table **`meeting_messages`** (morph : séance du programme OU formation) + **`meeting_message_reactions`**, `MeetingChatController` (routes `meeting.chat.list/post/react`, type résolu depuis une liste fermée), et la salle **rejoue le fil à l'arrivée** avec l'heure de chaque message et un trait « Vous rejoignez la salle ». ⚠️ Le nom de l'auteur est **calculé par le serveur** (`Meetable::meetingDisplayName` passe publique et entre au contrat) : reçu du client, chacun signerait du nom d'un autre. **Réactions** : liste d'emojis fermée (👍 ❤️ 😄 👏 ❓), unicité `(message, réacteur, emoji)` tenue par la base, réacteur = le compte (ou le staff) et jamais l'identité LiveKit qui change à chaque connexion ; affichage optimiste puis décompte du serveur. Le fil suit l'accès à la salle (`canJoin`). Tests `tests/Feature/MeetingChatTest.php`. *(commit à venir)*

**Visio - répondre à un message du chat (fait)** : bouton « Répondre » sur chaque message, bandeau « Réponse à… » au-dessus du champ (Échap ou croix pour abandonner sans vider ce qu'on a écrit), citation affichée au-dessus de la réponse et cliquable pour remonter à l'original (qui se surligne). ⚠️ La citation **voyage avec le message** (auteur + extrait) plutôt que d'être retrouvée par identifiant : rien n'est stocké côté serveur, donc un participant arrivé après l'original ne comprendrait pas la réponse. Au passage, le chat se construit désormais en `textContent` : il injectait le message en `innerHTML` après avoir remplacé `<` et `>`, une liste noire fausse par construction. *(commit à venir)*

**Visio - panneau réglable et micro à portée de clic (fait)** : le panneau latéral passe de 340px fixes à une **largeur réglable** (variable `--vside` + poignée `#sideGrip`) - 400 par défaut, 360 au minimum, jamais plus de la moitié de la salle, retenue en `localStorage`, double-clic pour revenir au défaut, flèches au clavier. Sur les vignettes, le nom passe en 11px (il occupait la moitié de la largeur et se tronquait au tiers) et gagne une **pastille micro ouvert / coupé, cliquable** : le modérateur coupe ou rend un micro depuis la vignette, chacun depuis la sienne. L'infobulle de sa propre main levée ne répète plus le numéro (il est déjà sur la vignette). *(commit à venir)*

**Visio - conduite de séance (fait)** : quatre comportements demandés par le PO. (1) **Le micro s'ouvre avec la présentation** : « Donner la présentation » promeut en `presenter`, et le poste visé ouvre son micro (`ouvreMonMicro`) - la personne reste libre de se recouper. (2) **L'état des micros se lit de la salle** (`syncMicStates`/`microActif` sur `TrackMuted`/`TrackUnmuted`/`TrackPublished`/`TrackUnpublished`) : `micOn` ne reflétait que ce que le modérateur avait **demandé**, si bien que quelqu'un qui se coupait restait affiché « micro ouvert ». La liste affiche désormais « micro ouvert / coupé » et le bouton suit. (3) **« Marquer effectuée » demande confirmation** (boîte `#vModal` + `confirme()`, et non `window.confirm` qui gèle la page) : la marque part à toute la salle et ne se retire pas. (4) **Mains levées numérotées** : badge ✋ n sur la tuile, rang dans la liste, compteur sur l'onglet Participants, infobulle à chaque main levée ; rang issu d'un compteur local (les horloges de deux postes ne sont pas comparables) et **recalculé**, donc une main baissée renumérote les suivantes. *(commit à venir)*

**Visio - le rôle de chacun est enfin connu des autres (fait)** : le jeton LiveKit ne portait **aucune métadonnée**, et LiveKit ne diffuse rien d'autre (les droits du jeton ne sont connus que du serveur et de son porteur). Chaque client retombait donc sur « auditeur » pour tout le monde : le modérateur de la séance apparaissait auditeur dans la liste des participants, aucun présentateur n'était identifiable, et la scène annonçait « en attente de la caméra » ou « caméra désactivée » **pendant que les caméras tournaient** dans le bandeau. `LiveKitProvider::token()` ajoute `metadata` = `{"role":…}`, vocabulaire centralisé dans `MeetingRole::roomRole()` (`moderator`/`presenter`/`audience`) et partagé avec la config du lecteur. ⚠️ Les connexions **en cours** gardent leurs anciens rôles jusqu'au rechargement. Côté salle : la scène montre quelqu'un qui diffuse vraiment à défaut de présentateur désigné, ne dit « caméra désactivée » que si la publication est coupée, et écoute enfin `TrackMuted`/`TrackUnmuted` - au passage, couper sa caméra rendait **muet** (`clearMedia` emportait l'`<audio>` attaché à la même tuile, d'où `clearVideo`). Tests paramétrés sur les trois rôles dans `MeetingTest`. *(commit à venir)*

**Salle de visio - téléphone et tablette (fait)** : la salle se tient enfin sur un écran étroit. Le panneau latéral devient un **tiroir** sous 900px (voile + croix, **fermé au départ** - il s'ouvrait tout seul par-dessus la scène, l'adaptation ≤900px étant écrite **avant** `.side{display:flex}` dans la feuille, donc écrasée), la barre d'outils se compacte sous **767px** (seuil choisi pour laisser l'iPad en portrait avec sa barre complète) et ses commandes secondaires sont **déplacées** - jamais recopiées - dans une feuille « Plus d'options » dont les étiquettes viennent de leur `title` ; la barre du haut se réduit sous 640px (la droite réclamait 405px sur 375 et écrasait le titre de la séance **à zéro pixel**). Hauteurs des barres passées en variables CSS (`--vtop`/`--vtoolbar`) dont dépendent bandeau d'état, menus et infobulle ; `100dvh` + `env(safe-area-inset-*)` (la barre du navigateur recouvrait « Quitter ») ; bloc **téléphone couché**. Retire au passage un `@endverbatim` **orphelin** que Blade écrivait en clair comme premier texte de la page. Garde-fou dans `MeetingTest`. *(commit à venir)*

**Visio - salle/lien custom côté admin (fait)** : par session, bouton « Salle » (partiel `_meeting`) ouvre un panneau inline (`editMeeting`/`saveMeeting`/`cancelMeetingEdit`, une session à la fois) : **fournisseur Jitsi** (nom de salle libre, vide = auto ; validé `[A-Za-z0-9 _-]`) ou **Lien externe** (`meeting_url`, URL requise). Nouveau `App\Meetings\Providers\CustomProvider` (renvoie `meeting_url`, **non embarquable**) enregistré dans le registry ; contrat étendu de `embeddable()`. `EventSession::meetingEmbeddable()`. Page « Rejoindre » : Jitsi → **IFrame API officielle** (voir ci-dessous) ; lien externe → carte **« Rejoindre (nouvel onglet) »** (les services type Zoom/Meet bloquent l'iframe) ; état « salle non configurée » si live sans lien. *(commit à venir)*

**Visio - fournisseur LiveKit (fait, défaut)** : ajout de `App\Meetings\Providers\LiveKitProvider` au registry (défaut `meetings.default` = **livekit**). ⚠️ LiveKit n'a **pas de mode public** : requiert un serveur (Cloud ou self-host) + `LIVEKIT_URL` (wss), `LIVEKIT_API_KEY`, `LIVEKIT_API_SECRET` (`config/meetings.php`). `isAvailable()` = ces 3 valeurs présentes ; **sinon le registry retombe sur Jitsi** (`for()`/`default()` vérifient `isAvailable()`, plus de salle cassée). Le token d'accès est un **JWT HS256** (firebase/php-jwt) porté par `LiveKitProvider::token()` : claims LiveKit `video.{room, roomJoin, canPublish, canSubscribe, canPublishData, roomAdmin}` - **droits dérivés du rôle** (Moderator → roomAdmin+publish ; Presenter → publish ; Attendee → subscribe seul). `joinUrl()` = `{meet_url}?liveKitUrl=…&token=…` (app LiveKit Meet, `meet_url` par défaut `https://meet.livekit.io/custom`, remplaçable par une app self-host). Admin : LiveKit ajouté au sélecteur de `_meeting` (salle nommée comme Jitsi). **Identifiants configurables depuis l'admin** (comme CashPay/FedaPay/IA - pattern `settings` + `applySettingsToConfig`) : section « Visio des sessions » dans `Admin\Settings\Index` (`/admin/reglages`) → fournisseur par défaut + `LIVEKIT_URL`/`API_KEY`/`API_SECRET`/`meet_url` ; clés `settings` `meetings.default` + `livekit.*` appliquées au boot par `AppServiceProvider::applySettingsToConfig()` (repli `.env`/config). Plus besoin de toucher au `.env`.

**Visio - lecteur LiveKit inline (fait, charte UK)** : `LiveKitProvider::embeddable()` = **true** ; `EventSession::liveKitEmbedConfig(?Account, isStaff)` → `{url, token, userName, canPublish}` (token = droits `video.*` selon le rôle). La page « Rejoindre » (`meeting.join`, branche `@if ($liveKit)`) embarque un **lecteur maison en `livekit-client`** - **SDK installé via npm et bundlé par Vite** (⚠️ **pas** de CDN) : `resources/js/meeting.js` (import `Room`/`RoomEvent`) est **code-splitté** en chunk `meeting-*.js` (~491 Ko) chargé à la demande par `resources/js/app.js` (`import('./meeting.js')` uniquement si `#lk-config` présent). La config (`{url, token, canPublish, backUrl}`) est passée par un `<script type="application/json" id="lk-config">@json($liveKit)</script>` (⚠️ construite **dans le contrôleur** - un `@php`/`@json([...])` multi-ligne dans la vue cassait le compilateur Blade). UI : grille vidéo sombre (navy UK), tuiles initiales/nom, **barre de contrôles** micro/caméra/partage d'écran (seulement si `canPublish`) + quitter, aux couleurs `ukc` ; gère `TrackSubscribed/Unsubscribed`, `Participant(Dis)Connected`, `LocalTrackPublished`, `Disconnected`→retour ; `beforeunload`→disconnect. ⚠️ **Rebuild requis** (`npm run build`) après modif de `meeting.js`. Contrôleur : 3 branches (`meetingEmbedConfig` Jitsi / `liveKitEmbedConfig` LiveKit / `joinUrl` externe). **Vérifié en réel** avec les identifiants de l'admin : page HTTP 200, SDK chargé, `wss://` + vrai JWT (405 car.) injectés, `canPublish` gaté par rôle. ⚠️ **Non vérifiable sans interaction** : la connexion média WebRTC réelle (caméra/micro + serveur) se teste en cliquant « Rejoindre ». Le lien LiveKit Meet (`joinUrl()`) reste dispo mais n'est plus utilisé par la page. *(commit à venir)*

**Visio - intégration via l'IFrame API Jitsi (fait)** : d'après le handbook Jitsi (l'iframe brute + params de hash n'est pas la méthode officielle), l'embed Jitsi passe par **`external_api.js` + `JitsiMeetExternalAPI`**. `JitsiProvider::embedConfig()` → `{domain, roomName, jwt, userName}` ; `EventSession::meetingEmbedConfig(?Account, isStaff)` (null si fournisseur ≠ Jitsi ou accès refusé). La vue `meeting.join` charge `https://{domain}/external_api.js` puis instancie l'API dans `#jitsi-container` (`userInfo.displayName`, `jwt` self-host, `configOverwrite.prejoinPageEnabled`), et **`readyToClose` → retour** à la page précédente. `joinUrl()` (URL directe) reste utilisée pour le partage et le fournisseur externe. *(commit à venir)*

**Système de notifications in-app** (les deux guards) - notifications **base de données Laravel** (table `notifications`, trait `Notifiable` déjà présent sur `Account`/`User` ; socle idiomatique, cf. bizzcart). Une seule classe paramétrée `App\Notifications\SystemNotification` (canal `database`, `ShouldQueue`, data = `kind/title/body/url/icon`). Émission via `App\Support\Notifier::account()` / `::staff()` (staff = permission `manage_colloques` ; ⚠️ `staff()` vérifie l'existence de la permission avant le scope spatie, sinon `PermissionDoesNotExist` en contexte webhook/test). **Déclencheurs** branchés sur les événements/Actions existants : *participant* → décision de soumission (nouveau listener auto-découvert `NotifyAccountOfSubmissionStatus` sur `SubmissionStatusChanged`), paiement confirmé (`ConfirmPayment`), attestation émise (`IssueAttestation`), invitation (`Admin\Participants\Index`) ; *staff* → nouvelle soumission (`Account\Submissions\Create`), paiement réglé (`ConfirmPayment`), message de contact (`Public\ContactForm`). **UI** : cloche `App\Livewire\Notifications\Bell` (îlot guard-aware, badge non-lus, `wire:poll.60s`, marque-lu + redirige) dans les **deux topbars** ; page `App\Livewire\Notifications\Center` montée sur `/mes-notifications` (account) et `/admin/notifications` (web), layout choisi selon le guard. Les e-mails transactionnels restent inchangés (in-app additif). ⚠️ Un **worker de queue** est requis en prod (notifs `ShouldQueue`) ; en test `QUEUE_CONNECTION=sync` → écriture directe en base. Bonus qualité : typer génériquement `Payment/Submission::participant()` et `Participant::account()` (`@return BelongsTo<…, $this>`) a **résorbé ~84 lignes de baseline** (inférence Eloquent sur toute la couche paiement/rapports). *(commit `93b728f`)*

**Notifications - préférences + canal e-mail + relecteur (fait)** : trois extensions du système ci-dessus. (1) **Préférences par type/canal** : registre unique `App\Notifications\NotificationKinds` (kind → libellé, audience account|staff|both, défauts in-app/e-mail, **`hasMail`** = Mailable dédié existant) ; trait `App\Models\Concerns\HasNotificationPreferences` sur Account/User (colonne JSON `notification_preferences`, cast array) → `notificationChannels($kind)`/`wantsNotification()`/`saveNotificationPreferences()` ; UI = panneau pliable dans la page `Center` (partagé par les deux guards, filtre les types selon l'audience). (2) **Canal e-mail unifié - « interrupteur unique »** : la préférence e-mail par type est le **seul** gate. `SystemNotification::via()` n'ajoute le mail **générique** (`toMail()`) que pour les types **sans** Mailable dédié (`!usesDedicatedMailFor`) ; pour les types **avec** Mailable dédié (soumission, reçu, invitation, relecture) c'est le **Mailable riche** qui part, mais désormais **gaté par la préférence** via `Notifier::emailAllowed(?Account, $kind)` (un destinataire **sans compte** reçoit toujours l'e-mail). ⚠️ Les Mailables dédiés étant tous côté participant, `User` (staff) **surcharge** `usesDedicatedMailFor()` → false (reçoit le mail générique). **Aucun doublon.** Défauts e-mail : **ON** côté participant (submission_status/review_assigned/attestation/invitation/payment - comportement d'avant préservé, mais désormais désactivable), **OFF** pour les deux types staff bruyants (submission_new, contact). ⚠️ `payment` (audience both) a un défaut e-mail unique ON → le staff reçoit aussi le mail générique paiement par défaut (désactivable ; pas de défaut par-audience dans le registre). (3) **Relecteur assigné** : `AssignReviewer` notifie le compte du relecteur (si lié) au 1ᵉʳ assignement (`wasRecentlyCreated`), lien portail par jeton. ⚠️ Génériques de relations ajoutés (`Review::reviewer`) → `?->` sur une relation BelongsTo peut déclencher un faux positif Larastan `nullsafe.neverNull` (le générique masque la nullabilité du FK) : extraire une variable + `->` quand la relation est de fait toujours présente (cf. `ConfirmPayment`). *(commit `6b6f126` puis affinage)*

**Page publique du colloque : Livewire → contrôleur + Blade** (audit Livewire vs Blade). Sur 38 composants, un seul était en lecture seule *et* public/SEO : `Public\ColloquePage`. Converti en `Http\Controllers\Public\ColloqueController` (invokable) + vue `resources/views/public/colloque.blade.php` (wrappée dans `x-layouts.app`). Pattern « coquille statique + îlots » : la page n'a **aucun `wire:`**, l'interactivité reste isolée - formulaire de contact via `@livewire('public.contact-form')`, compte à rebours/scrollspy via Alpine. Bénéfice : page **cacheable en HTTP**, plus de snapshot Livewire externe (vérifié : 1 seul `wire:id`, celui de l'îlot contact). Les 6 autres composants lecture-seule (dashboards, listes, aperçu, portail) restent en Livewire (authentifiés → pas de gain, cohérence privilégiée). ⚠️ Après suppression d'un composant, **régénérer le baseline PHPStan** (sinon chemins orphelins → erreur). Bonus : annoter `@property array<string,mixed>|null $settings` sur `Edition` a résorbé ~100 lignes de baseline (inférence JSON) à travers tout le code.

**Espace participant - « Mes documents » & « Sécurité » (2FA)** : sidebar participant complétée (`components/layouts/participant.blade.php`) avec deux entrées. *Mes documents* (`Account\Documents`, `/mes-documents`) : page consolidée = attestations + reçus + documents des éditions (ressources `media` + livret HTML/.docx), reprend la logique du dashboard. *Sécurité* (`Account\Security`, `/mon-compte/securite`) : changement de mot de passe (mot de passe actuel exigé) + **double authentification TOTP**. Moteur **`App\Support\Totp`** = RFC 6238 **PHP pur, zéro dépendance** (porté du pattern bizzcart `Services/Auth/App/Helpers/Totp`), QR via `simplesoftwareio/simple-qrcode` déjà présent (⚠️ google2fa a été **retiré** au profit de ce moteur maison). Colonnes `accounts.two_factor_secret`/`two_factor_recovery_codes`/`two_factor_confirmed_at` (convention Fortify), **chiffrées** (`encrypted`/`encrypted:array`) - ⚠️ Larastan infère la colonne `text` comme `string|null` : **annoter `@property`** sur le modèle (fait). Méthodes modèle alignées bizzcart : `hasTotpEnabled`/`generateRecoveryCodes`/`countRecoveryCodes`/`consumeRecoveryCode`/`disableTotp`. Flux : setup (secret provisoire **en session**, jamais dans l'état Livewire) → confirm (code TOTP → active + codes de secours affichés **une seule fois**) → désactiver/régénérer (mot de passe exigé). **Challenge à la connexion** intégré dans `Account\Login` : mot de passe validé mais connexion différée si `hasTotpEnabled()` (identifiant en session `account.2fa.login_id`), `confirmChallenge` accepte un code TOTP **ou** un code de récupération à usage unique. 14 tests dédiés (`AccountSecurityTest`, `AccountDocumentsPageTest`). **213 tests verts.**

**Observers & Events (patterns bizzcart)** : *Observers* - invariants garantis à la création (sans écraser une valeur fournie) : `SubmissionObserver` (`ref_code` AA-NNN + `access_token`), `ParticipantObserver` (`access_token`), `AttestationObserver` (`uuid`) ; enregistrés via `#[ObservedBy]` sur les modèles ; appelants allégés (`Submissions\Create` - plus de `generateRefCode()`, Upsert*, `IssueAttestation`). ⚠️ La resoumission (`Account\Submissions\Show`) fournit `ref_code` explicitement (conservé). *Events* - `SubmissionStatusChanged` + listeners `NotifyAuthorOfStatus` / `InviteCoauthorsOnAcceptance` **auto-découverts** par Laravel 13 (⚠️ **ne pas** aussi `Event::listen` manuellement → double envoi). `Admin\Submissions\Show::applyStatus` dispatche l'event. *(commit `1bf8ab1`)*

**Impersonation staff → participant** (`lab404/laravel-impersonate`) : vraie « connexion en tant que », **cross-guard** (`web` → `account`). Trait `Impersonate` sur `User` (`canImpersonate()` = permission `manage_colloques`) et `Account`. `Admin\ImpersonationController` : `take` (`POST /admin/participants/{p}/impersonate`, bouton « Se connecter en tant que » sur l'aperçu si compte lié) → impersonifie le compte, redirige vers l'espace ; `leave` (`POST /impersonation/quitter`, `auth:account`). Bannière persistante dans le layout participant (`app('impersonate')->isImpersonating()`). Pendant l'impersonation, le guard `web` est déconnecté → admin inaccessible (retour par « quitter »). ⚠️ Un `composer require --no-scripts` laisse le provider hors du cache de découverte (config `session_key` null → 500) : refaire `php artisan package:discover` après. *(commit `bcc6869`)*

**Réutilisation patterns bizzcart (lots 1-3)** : **PDF serveur** via `barryvdh/laravel-dompdf` - vues autonomes `pdf.attestation` (A4 paysage, QR SVG rendu par dompdf) / `pdf.receipt` ; `AttestationController@pdf` (`/attestation/{uuid}/pdf`) et reçus admin/participant en `?pdf=1` (⚠️ dompdf ne lit ni JS ni CSS-variables → vues dédiées à couleurs littérales, pas les vues `.ukc-*`). **Enums de statut** `App\Enums\SubmissionStatus`/`PaymentStatus` (backed, `label()`/`pill()`, trait `HasLabels` : `values/options/pills`) : les modèles exposent `statuses()`/`statusPills()`/`statusEnum()` (source unique = l'enum) ; `status` **reste une string en base** (pas de cast, pour ne pas casser les comparaisons `=== 'accepted'`). **Sauvegardes** `spatie/laravel-backup` : `config/backup.php` publié + `backup:clean/run/monitor` planifiés. **186 tests verts.**

**Outillage qualité (revue technique)** : contraintes composer **pinnées** (plus de `*`) ; **Larastan** niveau 5 (`phpstan.neon`) avec **baseline** (`phpstan-baseline.neon`, dette figée - surtout inférence Eloquent générique/JSON, pas des bugs ; le CI ne bloque que sur les nouvelles erreurs) ; **CI GitHub Actions** (`.github/workflows/ci.yml` : Pint + PHPStan + build + Pest). **Model factories** dispo pour Colloque/Edition/Account/Participant/Submission/Payment (`Model::factory()`, states). ⚠️ Le style **Pint** est appliqué **projet-wide** (concat sans espaces `'a'.$b`) - lancer `pint` avant de commiter. E-mails transactionnels en **file (ShouldQueue)** → un **worker** est requis en prod (`queue:work`/`queue:listen` ; `composer dev` en lance un). Écritures multi-étapes clés sous **`DB::transaction`** (paiement anti-doublon avec `lockForUpdate`, sync comités, soumission).

### Vérification visuelle
Le **navigateur intégré exige une approbation par action** sur `geves.test`. Se rabattre sur `curl` (statut HTTP + `grep` du contenu attendu + absence d'erreur) comme méthode de contrôle rapide.

### Fidélité au mockup - couverture des 31 maquettes
La correspondance **maquette → route → vue Blade → niveau de test** est tenue à jour dans [`docs/COUVERTURE-FIDELITE.md`](docs/COUVERTURE-FIDELITE.md) : les 31 maquettes du dossier de référence `~/Downloads/gesco-mockup/*.html` sont toutes portées sur une route implémentée. La suite de fumée `tests/Feature/SmokeTest.php` parcourt chaque famille de routes et asserte un rendu `200` (verrou de non-régression).

---

## 3. Architecture - points de repère

```
app/
├─ Livewire/
│  ├─ Account/           # Front participant (guard « account »)
│  │  ├─ Login, Register, ForgotPassword, ResetPassword, VerifyEmailNotice, Profile, Dashboard
│  │  ├─ Submissions/    # Create, Index (« Mes communications »), Show (fiche + resoumission)
│  │  └─ Payment/Pay     # Paiement des frais
│  ├─ Admin/             # Back-office (guard « web » + permission manage_colloques)
│  │  ├─ Login, Dashboard
│  │  ├─ Editions/       # Index, Form (CRUD éditions)
│  │  ├─ Submissions/    # Index, Show (workflow + relecteurs)
│  │  └─ Payments/       # Index, Record (encaissement manuel)
│  ├─ Reviewer/          # Portail relecteur par jeton : Portal, Evaluate
│  └─ Public/CoauthorPayment, ContactForm   # ColloquePage → Http/Controllers/Public/ColloqueController (Blade)
├─ Actions/              # Logique métier réutilisable (voir §5)
│  ├─ UpsertParticipantForAccount, NotifySubmissionStatus, AssignReviewer, ConfirmPayment
├─ Mail/                 # SubmissionStatusMail, ReviewerInvitationMail, PaymentReceiptMail
├─ Payments/             # Abstraction passerelles (interface + registre + gateways)
│  ├─ Contracts/PaymentGateway
│  ├─ GatewayRegistry
│  └─ Gateways/          # ManualGateway, CashpayGateway (SEMOA), FedaPayGateway ; contrat OnlineGateway
├─ Support/Fees          # Résolution des frais depuis settings.fees
├─ Models/               # 14 modèles Eloquent + User
└─ Http/Controllers/     # Account/ (verify, logout), Admin/ (logout, ReceiptController)

resources/
├─ css/ukc.css           # ★ Design system (tokens --ukc-*, composants .ukc-*)
├─ views/
│  ├─ components/
│  │  ├─ ukc/icon.blade.php          # ★ Icônes SVG <x-ukc.icon name=…>
│  │  └─ layouts/                    # app, auth, participant, admin
│  ├─ livewire/…                     # vues des composants (miroir de app/Livewire)
│  ├─ emails/…                       # gabarits markdown des Mailables
│  └─ receipts/payment.blade.php     # reçu imprimable

routes/web.php           # toutes les routes (front, admin, relecteur, public)
config/payments.php      # passerelle par défaut
database/migrations/     # 14 tables métier + permissions spatie
database/seeders/        # RolesAndAdminSeeder + ColloqueAvenirSeeder/VolumeSeeder
```

### Modèle de données (14 tables)
`colloques`, `editions`, `participants`, `submissions`, `reviews`, `payments`, `slots`, **`event_sessions`** (⚠️ pas `sessions`, réservé par Laravel), `ateliers`, `attestations`, `emails`, **`committee_members`**, `media`, `accounts` (+ `account_password_reset_tokens`). Champs libres en JSON : `editions.settings`, `participants.meta`, `submissions.authors/meta`, `payments.meta`, `ateliers.tracks`.

### Authentification - multi-guard
- **`web`** : personnel du back-office (table `users`), permissions via **spatie/laravel-permission** (rôles `administrator`/`editor`, permission `manage_colloques`). Routes admin protégées par `auth:web` + `can:manage_colloques`.
- **`account`** : comptes participants (table `accounts`, auth propre, e-mail vérifié, reset dédié). `Account::canSubmit()` = e-mail vérifié **et** affiliation renseignée.
- **Relecteurs** : pas de compte - accès par **jeton** (`participants.access_token`, rôle `reviewer`).
- Redirection des invités **selon la zone** dans `bootstrap/app.php` (`/admin` → `admin.login`, sinon `account.login`).

---

## 4. Conventions de design (à respecter)

- **Charte Université de Kara** : bleu `#223ea4` (foncé `#1b3182`), or `#ffd700`, vert `#009933`, police **Jost** (auto-hébergée via le plugin bunny dans `vite.config.js`).
- **Tokens & composants** : source unique dans `resources/css/ukc.css` (`--ukc-*`, `.ukc-btn`/`--primary|green|gold|outline|ghost|danger|sm`, `.ukc-card`, `.ukc-pill--ok|warn|info|muted|err`, `.ukc-table`, `.ukc-field`, `.ukc-steps`, `.ukc-empty`, shell `.ukc-shell/.ukc-sidebar/.ukc-topbar`). Préférer ces classes aux littéraux.
- **Icônes = SVG uniquement** via `<x-ukc.icon name="…" :size="18" />` (jeu Feather dans `resources/views/components/ukc/icon.blade.php`). **Jamais d'emoji dans l'UI.** Exception assumée : les **e-mails** peuvent garder des emojis.
- **Layouts** : `x-layouts.app` (squelette + Livewire), `auth` (carte centrée), `participant` et `admin` (shell sidebar auto-suffisant). Les composants full-page passent le titre via `->layout('components.layouts.x', ['title' => …])`.
- **Sécurité systématique** : validation Livewire (`rules()`/`#[Validate]`), garde d'autorisation dans `mount()` (`abort_unless`), permissions spatie côté admin, jetons aléatoires (`Str::random(64)`), gardes métier dans les Actions.

---

## 5. Logique métier réutilisable (Actions & Support)

- **`UpsertParticipantForAccount`** - crée/retrouve le participant d'un compte sur une édition. **Identité imposée par le compte** (nom infalsifiable), **rôle fort non rétrogradé** (`reviewer`/`committee`/`moderator`/`rapporteur`), `access_token` généré.
- **`NotifySubmissionStatus`** - envoie `SubmissionStatusMail` à l'auteur correspondant + **journalise** dans `emails`. Utilisé pour l'accusé de réception et chaque décision.
- **`AssignReviewer`** - crée/retrouve un participant relecteur (jeton de portail) + `Review`, passe la soumission en `under_review`.
- **`ConfirmPayment`** - passe un paiement à `paid` avec **garde anti-double-paiement** (un seul `paid` par participant/édition), génère `receipt_no` (`REC-{année}-{seq}`), envoie `PaymentReceiptMail`.
- **`Support\Fees`** - `resolveAmount(edition, grade)` (insensible casse), `highestAmount(edition)`, `grid(edition)` depuis `settings.fees`. ⚠️ `grid()` passe désormais par **`Fees::normalize($raw)`** : la grille canonique est une **liste de `['label' => string, 'amount' => float]`**, et toute entrée malformée (scalaire, libellé vide, ancien format associatif `grade => montant`) est **ignorée** au lieu de casser le rendu. Utiliser `Fees::grid()`/`Fees::normalize()` partout où on lit `settings.fees` (jamais l'accès brut).
- **Passerelles** - `GatewayRegistry` résout les passerelles **disponibles** (`isAvailable()` = identifiants configurés) et la **par défaut** (`config('payments.default')`, repli `manual`). `ManualGateway` (hors-ligne), `CashpayGateway` (SEMOA) et `FedaPayGateway` sont **implémentées** ; contrat `OnlineGateway` (verify + parseWebhook) pour le flux en ligne (voir §6 module 11).

---

## 6. Travail réalisé (modules)

Tout est **couvert par des tests Pest** (42 tests, 144 assertions au 2026-08-10). Correspondance avec les modules WP entre parenthèses.

1. **Fondations** - stack Livewire, design system, 14 migrations + modèles, comptes participants (inscription/connexion/reset/vérif e-mail/profil). *(commit `54263ae`)*
2. **Back-office éditions + page publique** - CRUD éditions (statuts, dates, soumissions, axes, grille tarifaire, slug auto), page `/colloque/{slug}` visible si publiée. Auth staff + spatie/permission. *(commit `54263ae`)*
3. **Soumission liée au compte** (Modules 2, 17, 25) - formulaire `/colloque/{slug}/soumettre` (bandeau compte, gating `canSubmit`, axes/type/mots-clés/résumé/co-auteurs/fichier), garde-fou nb max de communications, `ref_code` + `access_token`, accusé de réception. Liste « Mes communications ». *(commit `cfbcf4c`)*
4. **Workflow admin des soumissions** (Module 2) - liste filtrable, fiche détaillée, actions **en relecture / correction / accepter / refuser** avec message + e-mail à l'auteur (méthode `applyStatus`, ⚠️ `transition` réservé Livewire). *(commit `f83324b`)*
5. **Resoumission / versionnage** (Module 2) - fiche participant `/mes-communications/{submission}` : retour relecteurs (après décision) + resoumission quand `revision`. Une ligne **canonique** (`revision_of=0`) + **archives** (`revision_of=id`), `version++`. *(commit `f83324b`)*
6. **Relecture / peer review** (Module 3) - assignation depuis la fiche admin (panneau Relecteurs + synthèse note moyenne/recommandations), **portail relecteur en aveugle par jeton** `/relecture/{token}` + évaluation `/relecture/{token}/{review}` (recommandation, note /20, commentaires auteur + confidentiels), invitation par e-mail. *(commit `f83324b`)*
7. **Paiement** (Modules 4, 12, 17, 18) - encaissement **manuel** back-office (liste + totaux + reçu imprimable + e-mail + marquer réglé/rembourser), parcours **participant** (`/mon-compte/paiement/{edition}`, éligible si communication acceptée, montant du grade), **abstraction passerelle** (manuel ; CashPay/FedaPay implémentées au module 11). Garde anti-double-paiement partout. *(commit `f83324b`)*
8. **Ateliers, sessions & créneaux** (Modules 7, 26) - admin : ateliers (titre + axes), sessions (plénière/atelier, jour/horaire/salle, modérateur/rapporteur), créneaux + assignation des acceptées. Participant : choix d'un créneau libre sur sa fiche. *(commit `0144165`)*
9. **Back-office Participants** (Modules 5, 27) - liste par édition (rôle, paiement, invité), filtres, invitation individuelle + groupée (payés non invités), `ParticipantInvitationMail`. *(commit `16bedeb`)*
10. **Documents** (Modules 6, 8, 16, 19) - **attestations vérifiables par QR** (`IssueAttestation` UUID idempotent, émission par type/audience, révocation ; document imprimable avec QR → page publique `/verifier/{uuid}`), **livret** des résumés (par axe) et **badges** (A4, couleur par rôle) imprimables. Lib `simplesoftwareio/simple-qrcode` (SVG). *(commit `1f9d6c0`)*
11. **Paiement en ligne** (Modules 16, 18, 19) - contrat `OnlineGateway` (verify + parseWebhook **fail-closed**) ; `PaymentController` retour (`/paiement/retour/{gateway}`) + webhook (`/webhooks/paiement/{gateway}`, hors CSRF) avec confirmation via `ConfirmPayment` (le statut vient **toujours** d'une vérification serveur). **CashPay (SEMOA)** : OAuth token caché, `POST /orders` (bill_url), `GET /orders/{ref}`, callback **JWT HS256** vérifié (`firebase/php-jwt`). **FedaPay** : `POST /transactions` + `/token`, `GET /transactions/{id}` (approved), webhook **HMAC-SHA256** (`X-FEDAPAY-Signature`). Registre = CashPay + FedaPay + Manuel. *(commit `dbc1435`)*

11bis. **Passerelle de paiement par édition** - le gestionnaire/co-gestionnaire renseigne
    les identifiants de passerelle (FedaPay/CashPay + passerelle par défaut) pour **son
    édition** via l'onglet **Paiement** du formulaire d'édition (`admin.editions.payment`,
    cap `editions_write`). Persistés dans `edition.settings['payment']` (seules les valeurs
    **remplies** sont stockées). `App\Support\EditionPaymentConfig::apply($edition)`
    surcharge `payments.default` + `services.fedapay/cashpay.*` au runtime, avec **repli
    global** champ par champ. Appelé en tête de chaque parcours (`Account\PaymentController`,
    `Public\CoauthorController`, `Account\FormationController` - `show`/`pay`/`enroll`) et
    dans `PaymentController::return` via `$payment->edition`. Pour enchaîner plusieurs
    configurations, `EditionPaymentConfig::using()` applique **puis restaure** (sinon fuite
    d'identifiants d'un candidat à l'autre). Tests : `EditionPaymentConfigTest`.

11ter. **Webhooks fiables + réconciliation** (corrige « j'ai payé mais le système dit non »)
    - **Webhook multi-édition** : la signature devant être vérifiée avant de connaître
      l'édition, `PaymentController::webhook` essaie la config **globale** puis **chaque
      édition** ayant ses propres identifiants (`EditionPaymentConfig::candidates()`),
      chaque essai isolé par `using()`. **Garde d'étanchéité** : le marchand d'une édition
      ne peut confirmer que les paiements de SON édition. Toujours **fail-closed**.
    - **URL dédiée par édition** : `payment.webhook.edition`
      (`/webhooks/paiement/{gateway}/{edition}`), **affichée et copiable** dans l'onglet
      Paiement de l'édition (composant `<x-ukc.webhook-url>`) - à déclarer chez le marchand.
      L'URL générique continue de fonctionner.
    - **Journalisation** : canal de log **`payments`** (`storage/logs/payments.log`, 90 j) -
      webhook traité, signature non vérifiée, référence inconnue, édition signataire ≠
      édition du paiement. Première chose à consulter en cas de litige.
    - **Réconciliation (filet de sécurité)** : `App\Actions\ReconcilePayments` +
      `php artisan payments:reconcile [--days=7] [--payment=id]`, **planifié toutes les
      10 min**. Revérifie via `OnlineGateway::verify()` (avec les identifiants de l'édition)
      les paiements `pending` ayant une `gateway_ref` → confirme ceux réellement réglés,
      même si aucun webhook n'est jamais arrivé. `ReconcilePayments::settle()` devient la
      **source unique** d'application d'un statut vérifié (retour, webhook, réconciliation).
    - **Back-office** : bouton **« Revérifier »** sur un paiement en ligne en attente
      (`admin.payments.recheck`) → interroge la passerelle à la demande.
    - Tests : 7 cas dans `PaymentGatewayTest` (secret propre à l'édition, URL dédiée,
      étanchéité inter-éditions, réconciliation + identifiants d'édition, « Revérifier »).

12. **Programme public & fiches de session** (Modules 7, 26) - section « Programme » sur la page publique (plénières + ateliers + communications programmées via les créneaux, partiel `_session.blade.php`) ; **fiches de session imprimables** (`DocumentController@sessionSheets`, `/admin/editions/{edition}/fiches-session`). *(commit `502a78f`)*

13. **Réglages back-office** - table `settings` (clé/valeur JSON) + `App\Support\Settings` (caché) ; `AppServiceProvider::applySettingsToConfig()` applique au boot (mail SMTP, `services.cashpay/fedapay`, `services.openai/anthropic/gemini` + `services.ai.default`, `payments.default`) en repli sur `.env`. `Admin\Settings\Index` (Général, SMTP + e-mail de test, Paiement + identifiants, **IA** : fournisseur par défaut + clés OpenAI/Claude/Gemini). *(commits `6a249c6`, `f41b1a7`)*
14. **Espace participant enrichi** - dashboard : stepper (participation/attestation dérivées), blocs **Mes communications**, **Paiements & reçus**, **Mes attestations** (téléchargement par UUID) ; reçu participant `Account\ReceiptController` (`/mon-compte/recu/{payment}`, contrôle de propriété). *(commit `6a249c6`)*

15. **Page publique enrichie** (Modules 15, 20, 21) - comités (groupés par rôle), partenaires (logos par niveau), FAQ (accordéon), **compte à rebours** dans le hero (Alpine `ukcCountdown` dans `resources/js/app.js`). Gérés depuis le formulaire d'édition (comités = table `committee_members` sync ; partenaires/FAQ = `settings`). *(commit `d55865c`)*

16. **Paramétrage assisté d'édition par IA** (Modules 22, 23) - couche `App\AI` : contrat `AiProvider` (extract multimodal), providers Anthropic/OpenAI/Grok/Gemini (lisent `services.<provider>.key/.model`), `AiManager` (résolution défaut + repli + préférence PDF), parsing JSON robuste. Action `ExtractEditionFromDocuments` (instruction + schéma → normalisation). Carte « Paramétrage assisté (IA) » sur le formulaire d'édition : upload PDF/images → pré-remplissage des champs trouvés (révisable). *(commit `0682f9e`)*

17. **Modèles de badges/attestations + génération IA** (Modules 16, 19) - table `document_templates` (edition, kind badge|attestation, type, layout JSON). `App\Documents\LayoutSanitizer` (validation **déterministe** : bornage, liste blanche, défauts) + `LayoutRenderer` (HTML positionné + variables `{…}` + QR). Action `GenerateDocumentLayout` (l'IA produit **uniquement le JSON**). `Admin\Documents\TemplateDesigner` (génération IA + aperçu live + couleur d'accent + JSON éditable). Rendu branché : attestation + badges utilisent le modèle de l'édition si présent (repli sur le design fixe). *(commit `4a587db`)*

27. **Page publique : bannière promo + infos pratiques** (mockup WP) - bannière promo (bandeau doré en tête, texte + lien/CTA optionnels, masquée si vide ; `settings.promo`) ; section « Infos pratiques » (Lieu/Hébergement/Transport en cartes, ancrée dans la nav scrollspy ; `settings.practical`). Champs ajoutés au formulaire d'édition (save fusionne les settings, cf. correctif module 22). *(commit `e3e7bfe`)*

26. **Rôles agrégés inter-éditions** (Module WP 27) - les rôles ne sont plus figés par participation mais **dérivés** de l'activité de la personne sur **toutes les éditions**, par rapprochement d'e-mail (insensible casse). `App\Support\AccountActivity` : communications (auteur correspondant ou co-auteur via `authors` JSON, LIKE + filtre PHP), sessions modérées/rapportées (`moderator_email`/`rapporteur_email`), comités (`committee_members.email`, repli nom complet), relecteur (participation `role=reviewer`). `roles()` agrège Communicant/Relecteur/Modérateur/Rapporteur/Membre de comité, repli **Auditeur**. `Account\Dashboard` : carte « Mes rôles & activité ». `Admin\Participants\Preview` : pastilles « Rôles (toutes éditions) » si compte lié. *(commit `0606411`)*

25. **Modèles d'e-mails éditables** (Modules WP 12/13) - `App\Support\EmailTemplates` : 6 modèles (5 statuts de soumission + invitation participant) avec défauts, override via le réglage `email_templates` (champ vide = défaut), placeholders `{prenom} {nom_complet} {ref} {titre} {edition}`, helpers `forSubmission`/`forParticipant`. `SubmissionStatusMail` + `ParticipantInvitationMail` tirent sujet/titre/intro d'EmailTemplates (⚠️ ancienne const `SubmissionStatusMail::MESSAGES` supprimée ; `NotifySubmissionStatus` lit toujours `$mail->meta['subject']`). Seuls sujet/titre/intro sont éditables - les détails structurés (réf, dates, boutons) et le gabarit de marque restent fixes. `Admin\Emails\Templates` (`/admin/emails/modeles`, lien page E-mails) : édition + bouton « Défaut ». *(commit `c069947`)*

24. **Documents de l'édition côté participant** (Modules WP 10/11) - la table `media` (jusqu'ici inutilisée) est désormais gérée. `Media::TYPES` (document/livret/vidéo/photo/lien) + `typeLabel`. `Admin\Editions\Documents` (`/admin/editions/{edition}/documents`, lien liste éditions) : ajout/retrait de ressources (type/intitulé/URL). `Account\DocumentController@livret` (`/mon-compte/edition/{edition}/livret`) : livret des résumés avec **contrôle de propriété** (abort 403 si non-participant). `Account\Dashboard` : bloc « Documents de mes éditions » (médias + lien livret si ≥1 communication acceptée, pour chaque édition où le compte participe). *(commit `cd2f113`)*

23. **HAL - étape 1 (autocomplétion des affiliations)** - `App\Support\HalClient` interroge le référentiel des structures **AuréHAL** (API publique), normalise (label/acronyme/pays), **cache 1 j**, **fail-closed** (erreur/timeout → liste vide, saisie libre préservée), requêtes < 3 car. ignorées. `config/services.php` → `services.hal.enabled` (`HAL_ENABLED`) + endpoint ; appliqué depuis le réglage `hal.enabled` au boot. `Account\Profile` : autocomplétion du champ affiliation quand activé (`updatedAffiliation`/`chooseAffiliation`, click-outside). Réglages back-office : bloc « Interconnexion HAL » (toggle). **OFF par défaut.** ⚠️ Étape 2 (dépôt **SWORD** du texte intégral) non faite : nécessite un compte HAL + une étape « article complet » PDF pour les acceptés. *(commit `323da2f`)*

22. **Finitions page publique + impersonation admin** - *Page publique* : formulaire de **contact anti-spam** (`Public\ContactForm` + `ContactMessageMail` : honeypot, délai minimal anti-bot, throttle IP `RateLimiter`, envoi à `settings.contact.email` repli `mail.from`, journalisé) ; **hero média** (image/vidéo de fond depuis `settings.hero_image`/`hero_video`, overlay, repli bloc-couleur) ; **scrollspy** (`ukcScrollspy` IntersectionObserver dans `resources/js/app.js`) + smooth-scroll + lien Contact nav. Champs hero/contact ajoutés au formulaire d'édition. ⚠️ **Fix** : `Editions\Form::save()` **fusionne** désormais les settings existants (avant : il écrasait `submission_form` et toute clé non listée). *Impersonation* : `Admin\Participants\Preview` (`/admin/participants/{participant}/apercu`) - « Sa page » **lecture seule** (identité, communications, paiements & reçus, attestations), bandeau, aucune action ; lien depuis la liste participants. *(commit `a31c2f0`)*

21. **Formulaire de soumission paramétrable** (Module WP 14, adapté archi comptes) - l'identité vient du compte ; le formulaire porte sur titre/axe/type/mots-clés/résumé/fichier/co-auteurs. `App\Support\SubmissionFormConfig` (libellés, aides, requis/activé, bornes résumé min/max, champs personnalisés depuis `settings.submission_form`, défauts alignés). `Admin\Editions\SubmissionFormEditor` (`/admin/editions/{edition}/formulaire`, lien liste éditions) : champs standard + champs personnalisés (texte/texte long/liste/e-mail/nombre/case ; options une par ligne). `Account\Submissions\Create` consomme la config (libellés dynamiques, champs activables, obligatoires configurables, validation `accepted`/`in:`, stockage dans `submission.meta.custom_fields` + affichage fiche admin). *(commit `32a5be5`)*

20. **Parcours co-auteurs** (Modules WP 19-20) - les co-auteurs communiquants règlent leurs frais et deviennent éligibles à l'attestation de communication. `Public\CoauthorPayment` (`/colloque/{slug}/co-auteur`, **public sans compte**) : recherche par e-mail → communications acceptées associées → correction du grade (grille tarifaire) → paiement au tarif du grade via passerelles ; lien pré-rempli `?email=` + entrée discrète sur la page publique (section tarifs). `App\Actions\UpsertCoauthorParticipant` (participant co-auteur par e-mail, identité figée, rôle non rétrogradé, grade corrigeable). `PaymentController::return` branché sur la page publique co-auteur quand `meta.flow=coauthor`. Notification à l'acceptation : `App\Actions\NotifyCoauthorsForPayment` + `CoauthorPaymentInvitationMail` (lien pré-rempli, journalisée `emails`, dédoublonnage), câblée dans `Admin\Submissions\Show::applyStatus('accepted')`. Attestation « communication » : `Attestations::communicantTargets()` inclut les co-auteurs **ayant payé**. *(commit `497bd0a`)*

19. **Rapports xlsx/pdf** (Module 9) - exports par édition en **PHP pur** (aucune dépendance). `App\Support\XlsxWriter` : générateur .xlsx natif (ZipArchive + OOXML SpreadsheetML), multi-feuilles, en-tête gras, cellules texte (`inlineStr`) / numériques (auto), échappement XML, colonnes > Z. `App\Support\EditionReport` : agrégateur unique (synthèse + Participants/Communications/Paiements, note moyenne via `withAvg('reviews','score')`) réutilisé par xlsx/pdf/Livewire. `Admin\ReportController` : `xlsx` (4 feuilles) + `pdf` (HTML imprimable `@media print`). `Admin\Reports\Index` (`/admin/rapports`) : sélecteur édition, tuiles, boutons Excel/PDF ; lien sidebar. *(commit `614d9b4`)*

18. **E-mails programmés** (Modules 7, 11) - campagnes ciblées + rappels planifiés. `App\Support\EmailAudience` (audiences : tous / payés / non réglés / communication acceptée / relecteurs / non invités ; dédoublonnage par e-mail, exclusion des sans-adresse). `App\Mail\CampaignMail` + gabarit `emails.campaign` (sujet/corps libres). Action `QueueEmailCampaign` (une ligne `emails` par destinataire, statut `queued`, sujet/corps personnalisés via `{prenom} {nom} {nom_complet} {edition}`). Action `SendDueEmails` (envoie les `queued` dus `scheduled_at<=now`, marque `sent`/`failed`, **idempotent**). Commande `emails:send` + `Schedule::command('emails:send')->everyMinute()->withoutOverlapping()` (routes/console.php). `Admin\Emails\Index` (`/admin/emails`) : composer (immédiat/programmé), compteur d'audience live, journal filtrable, envoi des dus, annulation. Lien sidebar (remplace le placeholder « Documents » désactivé). *(commit `851cd61`)*

**État git** : `…`, `502a78f`, `6a249c6`, `d55865c`, `51dcd2a`, `0682f9e` (paramétrage assisté IA), `4a587db` (modèles badges/attestations IA), `851cd61` (e-mails programmés), `614d9b4` (rapports xlsx/pdf), `497bd0a` (parcours co-auteurs), `32a5be5` (formulaire de soumission paramétrable), `a31c2f0` (finitions page publique + impersonation), `323da2f` (HAL étape 1), `cd2f113` (documents de l'édition côté participant), `c069947` (modèles d'e-mails éditables), `0606411` (rôles agrégés inter-éditions), `e3e7bfe` (bannière promo + infos pratiques). Arbre propre. **171 tests verts**.

---

## 7. Ce qui reste à faire

### Priorité haute
1. **Validation des paiements en ligne au sandbox** - CashPay (SEMOA) et FedaPay sont **implémentés** (voir §6 module 11). Reste à : renseigner les identifiants (`.env` : `CASHPAY_*`, `FEDAPAY_*`, `PAYMENT_GATEWAY`), **tester un vrai paiement** en sandbox, confirmer l'enveloppe de réponse FedaPay (`v1/transaction`) et le format exact du corps du callback CashPay (JWT brut vs champ `token`). Puis exposer les webhooks publiquement et **déclarer l'URL côté marchand** - de préférence l'URL **dédiée à l'édition** affichée dans l'onglet Paiement (`/webhooks/paiement/{gateway}/{edition}`). ⚠️ En dev local, aucun webhook ne peut arriver : c'est la **réconciliation** (`payments:reconcile`, module 11ter) qui confirmera le paiement - vérifier que le scheduler tourne (`php artisan schedule:work`).
2. ~~**Livret .docx « vrai »** via `phpoffice/phpword`~~ **fait** - `App\Documents\LivretDocx` (page de garde charte + résumés acceptés groupés par axe : titre/auteurs/résumé) ; téléchargement via `?format=docx` sur les routes livret **admin** (`admin.documents.livret`) et **participant** (`account.documents.livret`, contrôle de propriété conservé), bouton « Word (.docx) » dans la vue `documents.livret`. ⚠️ phpword veut des **twips entiers** (`(int) Converter::cmToTwip(...)`). Tests : le `.docx` est dézippé (`word/document.xml`) pour vérifier titre/axe/communication.

### Ensuite
3. **E-mails** (Modules 7, 11) - ~~e-mails programmés (rappels à une audience via une commande planifiée)~~ **fait** (module 18, commit `851cd61`). Reste : brancher un vrai SMTP en prod (déjà réglable dans /admin/reglages) et lancer le planificateur (`php artisan schedule:work` en dev, ou cron `schedule:run` en prod) pour que `emails:send` tourne. En dev `MAIL_MAILER=log`, l'envoi immédiat marche sans planificateur (flush direct dans le composant).
4. ~~**Rapports** (Module 9) - export Excel (.xlsx) multi-feuilles + PDF.~~ **Fait** (module 19, commit `614d9b4`) : générateur maison `App\Support\XlsxWriter` (PHP pur, ZipArchive) + rapport PDF imprimable.
5. ~~**Page publique - finitions**~~ **fait** : contact anti-spam, hero photo/vidéo, scrollspy/menu sticky (module 22) + **bannière promo & infos pratiques** (module 27, commit `e3e7bfe`). Reste éventuellement : tarifs en cartes mises en avant (offre « à la une »).
6. ~~**Documents de l'édition côté participant**~~ **fait** (module 24, commit `cd2f113`) : gestion admin des médias (`media`) + bloc « Documents de mes éditions » (livret + ressources) sur le dashboard participant.
7. **Back-office Participants** - ~~« Sa page » (impersonation lecture seule)~~ (module 22) et ~~rôles dérivés inter-éditions~~ (module 26, commit `0606411`) **faits**.
8. ~~**Formulaire de soumission paramétrable** (module WP 14)~~ **fait** (module 21, commit `32a5be5`). Non porté volontairement (widgets front spécifiques WP) : tél. intl-tel-input, résumé WYSIWYG Quill, tags Select2 - le stack GESCO reste en champs simples.
9. **HAL** - ~~autocomplétion des affiliations (AuréHAL)~~ **fait** (étape 1, module 23, commit `323da2f`). Reste **étape 2** : dépôt **SWORD** du texte intégral vers une collection HAL (nécessite compte HAL + étape « article complet » PDF pour les acceptés). Optionnel : brancher l'autocomplétion aussi sur les co-auteurs du formulaire de soumission (aujourd'hui : profil compte seulement).

> **Comparaison WP↔GESCO (27 modules)** faite le 2026-08-10. Couverts en plus depuis : e-mails programmés (18), rapports xlsx/pdf (19), parcours co-auteurs (20), formulaire de soumission paramétrable (21), finitions page publique + impersonation (22), HAL étape 1 (23), documents de l'édition côté participant (24), modèles d'e-mails éditables (25), rôles agrégés inter-éditions (26), **bannière promo + infos pratiques (27)**. **Portage des 27 modules WP terminé.** Reste surtout : HAL étape 2 (SWORD, compte HAL requis) et les validations dépendant de l'utilisateur (sandbox paiements, clés IA réelles, SMTP + planificateur en prod).

---

## 8. Pièges & bonnes pratiques (à lire avant de coder)

- **Workflow Git (à plusieurs)** : on travaille sur `master`. Depuis l'arrivée d'un 2ᵉ contributeur, **toujours `git pull --rebase` avant chaque `git push`** (évite les commits de merge et les conflits), et **pousser vers GitHub à la fin de chaque tâche** (ne pas laisser de travail terminé uniquement en local). Commits en **français**. Dépôt privé `abdoulhaqjr/geves`.
- **Toolchain Herd** : toujours `export PATH="$HOME/.config/herd/bin:$PATH"` puis `php.bat`/`composer.bat` (§2).
- **2FA - méthode exclusive** : `twoFactorMethod()` donne la **priorité au SMS** (`hasSmsEnabled()` avant `hasTotpEnabled()`). Toute activation doit donc **positionner `two_factor_method`** et purger l'autre méthode, sinon une bascule SMS → application laisse le compte en `sms` : la connexion envoie un SMS et **refuse le code de l'authenticator** (symptôme « la 2FA ne fonctionne pas »). Corrigé des deux côtés (`Account\SecurityController`, `Admin\ProfileController`). En dev, `sms.default = log` → les codes SMS sont dans `storage/logs/laravel.log`, jamais envoyés.
- **URLs de l'espace participant** : tout vit sous **`/compte/...`**. En cas de renommage, laisser une `Route::permanentRedirect` : les notifications stockent une **URL absolue figée à la création**, donc d'anciennes lignes pointent vers l'ancien chemin.
- **QR code / SVG généré** : le SVG produit par `Totp::qrCodeSvg()` porte **sa propre taille** (200 px). Ne jamais le poser dans un cadre à taille fixe sans contraindre le SVG (`.qr-frame svg { width:100%; height:100% }`), sinon il **déborde** sur le contenu voisin (bug vécu : QR par-dessus la clé secrète). Les tests HTTP ne voient pas ce genre de défaut - **vérifier au navigateur**.
- **Livewire 4 - méthodes réservées** : ne pas nommer une action `transition` (collision avec `Livewire\Component::transition`). Utiliser un autre nom (ex. `applyStatus`).
- **Livewire 4 - variable de vue `$slots` réservée** : Livewire injecte `$slots` (slots nommés) dans chaque vue ; ne jamais passer une donnée de vue nommée `slots` (elle est shadowée par un `SlotProxy`). Renommer (ex. `creneaux`). Idem prudence avec d'autres noms magiques.
- **Colonnes `nullable` avec défaut « 0 » applicatif** : `slots.submission_id`/`participant_id` sont `nullable` mais le code traite « 0 = libre ». À la création d'un créneau, forcer `submission_id => 0` (sinon `null` ≠ `0` et les requêtes `where(...,0)` le manquent) - même piège que `revision_of`.
- **Paiement en ligne - confiance zéro dans l'URL** : le statut « payé » vient **toujours** d'une vérification serveur (`OnlineGateway::verify`) ou d'un webhook **à signature vérifiée** (`parseWebhook`, **fail-closed** = retourne `null` si invalide). Ne jamais confirmer un paiement d'après un paramètre d'URL de retour. `ConfirmPayment` reste idempotent + anti-double-paiement.
- **Certification des attestations - ne pas improviser la crypto** : la signature = **EdDSA/Ed25519** sur un **Verifiable Credential en JWS compact** (VC-JWT) via `firebase/php-jwt` (libsodium), **jamais** de canonicalisation JSON-LD maison (pas de vrai `Ed25519Signature2020`). Clé privée = graine **hors dépôt** (`ATTESTATION_SIGNING_KEY`), **jamais en base**. **Fail-open** à l'émission (sans clé → non signée), **fail-closed** sur la preuve (n'afficher « vérifié » que si la signature vérifie). La révocation reste un **état DB** (une signature ne se défait pas). Cf. `App\Documents\AttestationCertifier`, §11.
- **`firebase/php-jwt` - clés EdDSA** : la clé passée à `JWT::encode/decode` est une **chaîne base64url** (dernier `\n`-segment, décodé `urlsafe`) - clé **secrète 64 o** pour signer, clé **publique 32 o** pour vérifier (cf. `validateEdDSAKey`). On stocke la **graine 32 o** et on dérive le couple avec `sodium_crypto_sign_seed_keypair`.
- **`firebase/php-jwt` (HS256)** : refuse les clés trop courtes (`Provided key is too short`). En test, utiliser une clé d'API ≥ ~32 caractères (les vraies clés SEMOA le sont). Le corps du callback CashPay peut être le JWT brut **ou** un champ `token` - `CashpayGateway::parseWebhook` gère les deux ; à confirmer au sandbox.
- **Webhooks hors CSRF** : les routes `/webhooks/*` sont exclues de la vérification CSRF dans `bootstrap/app.php` (`validateCsrfTokens(except: ['webhooks/*'])`).
- **Livewire 4 - « Invalid snapshot structure » en test** = une exception/`abort()` **avalée pendant le render initial** (le snapshot devient NULL, l'erreur ne remonte qu'au 1ᵉʳ `->set()`/`->call()`). Chercher un `abort_unless` faux dans `mount()`/`render()`, pas le trait `WithFileUploads`. Astuce diagnostic : dumper `$c->html()` et vérifier la présence de `wire:id`.
- **Modèle fraîchement créé ≠ rechargé** : `Model::create([...])` **ne reflète pas les valeurs par défaut SQL** en mémoire (ex. `revision_of` reste `null`, pas `0`). Une comparaison stricte `=== 0` casse alors. → **caster** (`'revision_of' => 'integer'`) et comparer en `(int)`, ou passer `->fresh()`. (Incident réel corrigé sur la resoumission.)
- **Composants Livewire = classe** (`--class`), pas les single-file components de Livewire 4 (préfixe emoji `⚡` peu maniable pour l'outillage). Générer avec `php.bat artisan make:livewire Chemin/Nom --class`.
- **Nouvelle classe/action métier** : privilégier une **Action** dans `app/Actions` (réutilisable + testable) plutôt que de la logique dans le composant.
- **Nouvelle table « à couplage lâche »** : suivre le style des migrations existantes - FK `constrained` sur les parents forts (colloque, édition), simple index pour les références optionnelles.
- **E-mails** : Mailables markdown (`x-mail::message`), templates dans `resources/views/emails`. En dev `MAIL_MAILER=log` → les e-mails partent dans `storage/logs/laravel.log`. Toujours journaliser côté métier quand c'est pertinent (table `emails`).
- **Reçus / documents imprimables** : vue Blade + `window.print()` (règle `@media print`), pas de dépendance obligatoire. Passer à dompdf si un vrai PDF serveur est requis.
- **CSS - reset de liens vs boutons `<a>`** : le reset `.ukc-scope a { … }` a une spécificité (0,1,1) qui **bat** `.ukc-btn--primary { color:#fff }` (0,1,0) → les liens-boutons héritaient de `--ukc-text` (texte navy foncé) au lieu de leur couleur (glaring sur `--primary`/`--green` = **noir sur bleu/vert**). Corrigé en excluant les boutons du reset : `.ukc-scope a:not(.ukc-btn) { … }`. ⚠️ Règle générale : tout sélecteur de reset ciblant `a`/`button` doit exclure `.ukc-btn`, ou rester sous la spécificité des variantes de bouton. Diagnostic fiable : `getComputedStyle` en navigateur (le fond bleu s'appliquait mais pas la couleur → une autre règle gagnait sur `color`).
- **Front servi par Vite dev** : `public/hot` présent = `@vite` pointe vers le serveur Vite (`http://[::1]:5173`, IPv6). Si le serveur est **éteint**, toutes les pages sont **non stylées** (CSS 404) - relancer `npm run dev` (ou `composer dev`). Vérifier le vrai rendu = navigateur, pas seulement la source (cf. piège CSS ci-dessus).
- **Tests** : `RefreshDatabase` est activé globalement (`tests/Pest.php`), base `:memory:`. Les **fabriques partagées** `openEdition()`, `verifiedAccount()`, `adminStaff()`, `seededSubmission()` sont désormais centralisées dans **`tests/Pest.php`** (globales quel que soit l'ordre de chargement → suites exécutables en isolation). Ne pas les redéclarer dans un fichier de test (sinon « cannot redeclare »). `payEdition()` reste local à `PaymentTest`.

---

## 9. Déploiement

**Mode opératoire complet : [`DEPLOIEMENT.md`](DEPLOIEMENT.md)** (cible o2switch,
`https://geves.univkara.tg`), et modèle d'environnement :
`.env.production.example`. Ce qu'il faut retenir :

- ⚠️ **Le `composer.lock` exige PHP ≥ 8.4.1.** `composer.json` dit `^8.3` et
  Laravel 13 l'accepterait, mais le verrou a résolu **Symfony 8.x**. Viser 8.3
  imposerait de re-verrouiller sur Symfony 7.4 **et de relancer toute la suite**.
- ⚠️ **Deux crons, pas un.** `schedule:run` chaque minute, ET
  `queue:work --stop-when-empty` chaque minute : **dix des onze classes d'e-mail
  sont des jobs**, sans travailleur rien ne part.
- ⚠️ **`APP_URL` change l'adresse inscrite DANS les attestations signées** :
  `php artisan attestations:sign --force` après la mise en ligne (déjà noté
  dans CLAUDE.md).
- ⚠️ **La racine du document du sous-domaine doit viser `public/`**, et le dépôt
  vivre hors du dossier servi - sinon `.env` est téléchargeable.
- **Indisponible sur mutualisé** : production des vidéos de prise en main
  (FFmpeg + processus long). Les guides texte fonctionnent.

---

## 10. Ressources

- `PASSATION.md` (ce fichier) - état et reprise du projet Laravel.
- `README.md` - squelette Laravel par défaut (à enrichir).
- Sources externes : `Downloads/Site Web/uk-colloques/README.md` (modules 1→27), `Downloads/Site Web/Documentation-Projet/PASSATION.md` (métier), `Downloads/gesco-mockup/` (maquettes), `Herd/bizzcart.com/` (patterns Laravel réutilisables).

---

*Fil conducteur : cohérence de la charte (tokens + icônes SVG), sécurité (validation/autorisation/gardes métier dans les Actions), et **tests systématiques** (chaque module a ses tests Pest). Déployer souvent, par petits lots.*

---

## 11. Évolutions depuis le 2026-08-11 (professionnalisation)

Phase de **professionnalisation** post-portage. Tout est testé (**≈568 tests Pest verts** au dernier lancement, **PHPStan niveau 5** propre) et poussé sur GitHub. Résumé par thème (les détails fins vivent dans l'historique Git et la mémoire projet locale).

### Identité & publication
- **Renommage GEVES + nom dynamique** - le nom d'affichage est **`config('app.name')`**, surchargeable via **Réglages** (champ « Nom de la plateforme », clé `app_name` → `app.name` dans `SettingController::MAP`, appliqué au boot par `AppServiceProvider::applySettingsToConfig()`). Placeholder de traduction **`:app`** pour les libellés. Persona des assistants IA aligné sur le nom dynamique. **Ne jamais** écrire un littéral de marque dans l'UI.
- **Publication GitHub** - dépôt **privé** `https://github.com/abdoulhaqjr/geves` (branche `master`), `README.md` rédigé, `.env.example` (`APP_NAME=GEVES`), `phpunit.xml` (`APP_NAME=GEVES`). Identité Git : TOURE / touremouhariabdoulhaq@gmail.com.

### Assistance IA
- **Prise en main, finition visuelle** - les deux pages de prise en main gardent les
  pilules et les espacements des modules Programme, Projet et Paiements. Les cartes
  ne s’étirent plus à la hauteur d’une vidéo voisine. Côté participant, les catégories
  filtrent le parcours sans rechargement. Côté administration, la modale d’édition
  utilise toute sa largeur utile, groupe les rôles dans une grille lisible et garde les
  actions Enregistrer/Annuler accessibles pendant le défilement.
- **Parcours de prise en main** - onze guides détaillés et ordonnés couvrent les
  présentations distinctes participant/admin, le dépôt et le suivi d’un résumé,
  la visio, les missions de rapporteur et modérateur, la gestion/configuration
  d’un colloque, le secrétariat et les deux assistants. `TutorialGuideCatalog`
  remplace automatiquement les seuls dix anciens textes encore intacts et conserve
  les modifications humaines. Une vidéo attachée à un guide révisé passe en état
  `stale` : elle reste inspectable par l’admin, disparaît de l’espace participant
  et entre dans la prochaine production groupée. `TutorialCaptureCatalog` aligne
  une cible de capture sur chaque étape ; les parcours participant passent par
  `captures-participant@geves.invalid`, sans droits d’administration. Vérifier
  le contexte de démonstration avant de produire les vidéos de visio, présence
  et rapport : ces écrans dépendent d’affectations et de séances réelles.
- **Prise en main admin et participant** - `admin.onboarding` (`/admin/prise-en-main`) initialise et lit les dix guides stockés dans `tutorial_guides`. Tout staff peut les consulter ; seuls `isColloqueAdmin()` et les super-administrateurs peuvent modifier texte, étapes, audience, rôles, vidéo téléversée ou URL et visibilité participant. Les demandes de création/mise à jour sont persistées dans `tutorial_video_requests`. Le travailleur choisit Gemini, Claude ou OpenAI parmi les clés stockées dans **Réglages**, écrit un scénario JSON et le fait relire par un autre fournisseur disponible ; les sorties sont conservées dans `meta.creative`, sans bloquer le rendu si une API est indisponible. `TutorialVideoRenderer` crée une composition **Hyperframes**, synthétise un lit musical original avec FFmpeg, valide la composition et rend le MP4 côté serveur : aucun compte IA installé ou connecté chez l’administrateur n’est requis. La clé Gemini a été validée par un appel réel. `TutorialVideoProduction` renseigne `meta` avec un contrat inspiré de **Brag** : logo UK (`public/img/logo-uk.png`), palette `#223ea4/#1a3080/#2f52c8/#009933`, police Jost, musique originale audible et mouvements UI sobres ; narration désactivée par défaut. Chaque vidéo dure au minimum 36 secondes : elle montre l’introduction exacte du guide et une scène dédiée à chaque étape, dans leur ordre. Le rendu courant compose des repères illustrés à partir du guide : ne pas le présenter comme une capture d’un écran authentifié. `GenerateTutorialVideo` traite la file `tutorial-videos`; `TutorialVideoAgent` valide le MP4 et le publie dans le guide, et `TutorialVideoWorkerLauncher` démarre le travailleur de façon cachée sous Windows. Son verrou contient le PID renvoyé par PowerShell et il vérifie ce PID avant de se considérer déjà actif : un démarrage raté ne bloque donc plus la file pendant une heure. `tutorial-videos:dispatch-pending` reprend les anciennes demandes. Les livrables restent dans `storage/app/tutorial-video-production/request-{id}/`, avec un JSON de résultat validé ; les vidéos `video_visible_to_participants = false` restent exclusivement admin. L’espace participant est `account.onboarding` (`/prise-en-main`), entré depuis son menu, et filtre le catalogue sur l’événement courant et ses rôles (`author`, `moderator`, `rapporteur`, etc.).
- **Résilience & routage & failover** - relance transparente **3×** sur erreurs transitoires (503) via `RetriesTransientFailures` + `AiUnavailableException` ; **routage automatique** fast/deep selon la complexité (`App\AI\AiRouter`) ; **bascule entre fournisseurs** (`AiManager::extract`) selon la capacité (lecture PDF) et le quota, avec repli sur le modèle par défaut si un palier est refusé. Modèles Gemini passés en alias `-latest` (les versions datées 2.5 renvoyaient 404) + `licenseKey`.
- **Copilote back-office à actions exécutables** - `App\AI\Copilot` : le copilote **exécute de vraies actions système** après **autorisation explicite** de l'utilisateur (endpoint `admin.ai.execute`, **capacité RBAC revérifiée** côté serveur). Il s'appuie sur le **colloque sélectionné** et le **ciblage réel de l'action**, pas seulement sur les statistiques du tableau de bord. Extensible.
- **Réclamations & incidents** - le chatbot public enregistre les demandes non résolues → page back-office (capacité `claims`) avec analyse IA de traitement. ⚠️ **TODO** : améliorer la page `admin/reclamations/` (à cadrer avec le PO).
  - ⚠️ **Une discussion = UNE réclamation** (`claims.conversation`) : au fil d'un même fil de chat, les tours suivants - souvent des **précisions que Gesco a demandées**, pas une nouvelle plainte - **mettent à jour** la réclamation existante (sujet/message/catégorie rafraîchis, **même référence**, pas de notification staff répétée) au lieu d'en créer une à chaque tour. L'identifiant de conversation est généré côté client (`gescoChat.conversationId`, sessionStorage, régénéré par « Nouvelle discussion »), envoyé à `gesco.ask` (`conversation_id`) et rapproché dans `GescoController::recordClaim`, **scopé au même demandeur** (garde-fou). Une **nouvelle** réclamation n'est créée que pour une nouvelle conversation ou quand la précédente est déjà **résolue/clôturée** (statut/affectation du staff jamais rétrogradés). Tests : `ClaimsTest`.
- **Persistance du chat** - la discussion est conservée pendant la session (**sessionStorage scopé par utilisateur**, `gescoChat` + `adminCopilot`) + bouton « Nouvelle discussion » (reset). Correctif : fuite inter-utilisateurs après déconnexion.
- **Horloge** - les agents IA reçoivent la date/heure courantes (`now()`) dans le prompt (questions temporelles). Placeholders des chatbots nettoyés (suppression de « Maj+Entrée = saut de ligne »).
- **Limites du chatbot public** - dans `GescoController::ask()` : **rate-limit court terme** (anti-flood, `AI_PUBLIC_RATE_LIMIT`/h) **+ plafond journalier** (`AI_PUBLIC_DAILY_LIMIT`/jour), par utilisateur (par IP si anonyme). **Admins/gestionnaires exemptés** (`isColloqueSuperAdmin()` ou capacité `editions_write`). Config dans `config/services.php` (`services.ai.public_rate_limit`/`public_daily_limit`).

### RBAC & espaces
- **RBAC par édition** - 2 systèmes de rôles : **staff `User` global** (spatie) vs **`Participant` par édition**. Équipe par édition (pivot `edition_user`, onglet Équipe), étanchéité « pure » (capacités = fonction affectée) via middleware `edition.cap` ; l'admin est super-admin. Capacités type `editions_write`, `announcements`, `claims`. Espaces de fonction (modérateur/rapporteur/relecteur) côté participant (⚠️ colonne `account_id` morte → utiliser `user_id`).
- **Annonces** - gestion des annonces depuis l'admin (capacité `announcements`, accordée à admin/gerant/co_gerant/secretariat), affichées sur la page de déroulement (onglet Annonces).

### Formulaire de soumission (refonte)
- **Wizard de soumission** - étapes dynamiques ; **résumé = CKEditor 5 WYSIWYG** (npm `ckeditor5` v48, import dynamique, `licenseKey: 'GPL'`, plugin WordCount → **comptage en MOTS**, badge « powered by » masqué en CSS, texte **justifié**) ; **mots-clés** déplacés dans l'onglet Résumé (**Tom Select**, sous le résumé) ; **étape Fichiers conditionnelle** (masquée si upload non activé) ; **titre min/max caractères par édition** (défaut 0/256, `SubmissionFormConfig::titleMin()/titleMax()`) ; l'écran de **validation affiche le contenu réel du résumé + les mots-clés** (état Alpine réactif alimenté par l'événement `ukc:abstract` et le `change` Tom Select - le DOM du textarea n'est pas réactif). Fichiers : `resources/views/account/submissions/create.blade.php`, `partials/abstract-editor.blade.php`, `resources/js/app.js` (`initAbstractEditors`, `submissionWizard`).
- **Blocage d'étape** - `submissionWizard` (`next()`/`goTo()`/`validateStep()`) empêche d'avancer tant que les champs obligatoires de l'étape courante manquent ; les erreurs s'affichent en **toast** (`ukcToast`). Champs perso obligatoires repérés par `data-custom-req`. **Unicité des auteurs** : un co-auteur ne peut pas avoir le même e-mail que l'auteur correspondant (le compte connecté si « self »), ni deux co-auteurs le même e-mail (contrôle client `validateStep(2)` + serveur `StoreSubmissionRequest::withValidator`, erreurs sur `coauthors` → étape 2). La validation serveur reste la source de vérité.
- **Auteur correspondant éditable + grade PAR COLLOQUE** - l'étape « Auteur correspondant » affiche des champs préremplis avec le compte (nom, e-mail, affiliation, **grade** `<select>`), éditables ; interrupteur `.ukc-switch` « Je suis l'auteur correspondant » (défaut ON → identité nom/e-mail **forcée côté serveur** depuis le compte, non falsifiable ; OFF → saisie libre pour un tiers). Le **déposant connecté reste propriétaire** (paiement/attestation/suivi inchangés). `CreateSubmission` construit `authors[0]` (avec `is_submitter`) et, quand *self*, enregistre **grade + affiliation sur le participant de l'édition** (`UpsertParticipantForAccount::handle(..., $grade, $affiliation)` → `participant.title`/`institution`), permettant des grades différents d'un colloque à l'autre. Grades = **`App\Support\ParticipantTypes`** (liste unique partagée avec le profil).
- **Correction du résumé avant évaluation** - sur la fiche `account/submissions/show.blade.php`, si `Submission::isAuthorEditable()` (canonique, statut `submitted`, **aucune relecture**, pas de camera-ready), un panneau CKEditor corrige **titre/résumé/mots-clés en place** (pas de nouvelle version) via `SubmissionController::updateContent` (route `account.submissions.update`). Après la 1ʳᵉ intervention du staff → resoumission officielle (`canBeRevised`). Le résumé affiché sur la fiche passe par **`App\Support\Html::render`** (HTML assaini, `&nbsp;` corrigé, justifié). Tests : `tests/Feature/SubmissionAuthorEditTest.php`.

### Liaison auteurs ↔ comptes (une communication, plusieurs propriétaires)
- **But** : rattacher une communication aux **comptes** de ses auteurs/co-auteurs pour qu'elle apparaisse dans « Mes communications » de **chacun** (plus seulement du déposant). Table **`submission_authors`** (`Submission::authorLinks()`) : une ligne par auteur - `user_id` (nullable), `role` (corresponding|coauthor), snapshot name/email/phone/affiliation, `match_type` (self|email|phone|name), `status` (linked|invited|unmatched|declined), `token`.
- **Moteur `App\Support\AuthorMatcher`** : signal **fort** = e-mail ou téléphone exact (auto-liaison ferme) ; **nom** = signal faible, départagé par l'**affiliation**, avec **IA en renfort** (`pickWithAi`, seuil de confiance 0,8, s'abstient sinon). Une liaison par nom est **déclinable**.
- **`App\Actions\LinkSubmissionAuthors`** (appelée par `CreateSubmission`, hors transaction) : crée les liens, lie les comptes trouvés (+ participant via `UpsertParticipantForAccount`), **notifie** (in-app kind `authorship` + `AuthorLinkedMail` + **SMS best-effort** si `sms.default` ≠ log) ; pour un inconnu avec e-mail → `AuthorInviteMail` (invitation à créer un compte, statut `invited`).
- **Auto-liaison à l'inscription** : listener **`LinkPendingAuthorships`** (auto-découvert, sur `Registered`) rattache les lignes `invited/unmatched` correspondant au nouveau compte (e-mail/téléphone fort, ou nom+affiliation), sur les **éditions non clôturées**, + notif.
- **Déclinaison** : `AuthorLinkController` (routes publiques `author.decline` / `author.decline.confirm`, landing par jeton + confirmation POST) passe le lien en `declined` (retiré de « Mes communications »).
- **Lecture seule co-auteur** : `SubmissionController::show` accepte le déposant OU un auteur lié (`authorizeView` → `$isOwner`) ; toutes les actions de modification de `show.blade.php` sont gardées par `$isOwner` (les mutations restent `authorizeOwnership`, 404 sinon). « Mes communications » = déposées **OU** liées (`status=linked`). Tests : `tests/Feature/SubmissionAuthorLinkTest.php`.
- **Indice AJAX discret (à la saisie)** : `AuthorLookupController::check` (route `account.authors.check`, wizard `submissionWizard` → `lookupCorr`/`lookupCoauthor` au `@blur`) confirme, à partir de l'**e-mail saisi**, si un compte correspond (« sera associé & notifié ») ou non (« sera invité »), et propose une **mise en forme du nom** (`AuthorMatcher::formatName`) - **sans jamais révéler l'existence d'un compte sur un nom seul** (vie privée). Quand l'e-mail correspond à un compte, l'endpoint renvoie aussi ses champs (`account: {name, affiliation, grade}`) pour **auto-compléter les champs VIDES** de l'auteur côté client (`_fillFrom`, ne jamais écraser une saisie). **Détection de doublon à la saisie** (symétrique auteur correspondant ↔ co-auteurs) : alerte rouge prioritaire si co-auteur = correspondant, deux co-auteurs identiques, ou (correspondant « pas moi ») e-mail = déposant / co-auteur. L'interrupteur « Je suis l'auteur correspondant » **vide** les champs quand on décoche (saisie d'un tiers) et les **rétablit** depuis le compte quand on coche. Le rapprochement réel (nom + affiliation + IA) reste fait au dépôt. Tests : `tests/Feature/AuthorLookupTest.php`.

### Divers UI
- **⚠️ `data-ajax` + bouton porteur de l'action** : l'utilitaire `ukcAjaxSubmit` envoie `new FormData(form, e.submitter)` - SANS le 2ᵉ argument, la valeur du **bouton cliqué** (ex. `name="status"`) est perdue → 400 « Action impossible ». Toujours transmettre le `submitter`.
- **Popup de confirmation (remplace `confirm()`)** : `window.ukcConfirm(message, {title, confirmLabel, cancelLabel, danger})` → Promise<boolean>, style Tailwise (`.ukc-confirm-overlay/.ukc-confirm-box`, dans `ukc.css`). Déclenché par **`data-confirm`** sur le **bouton** (formulaires multi-boutons / data-ajax) ou sur le **formulaire** (mono-bouton natif). Le handler de soumission global gère data-ajax ET soumission native (en préservant `name/value` + `formaction/formmethod` du bouton). Ne plus utiliser `onclick="return confirm(...)"`.
- **Décision admin sans relecture** : `Admin\SubmissionController::applyStatus` n'impose AUCUN passage par la relecture - l'admin peut accepter/refuser/demander une correction **directement** depuis l'état « déposée » (débloque les cas où les relecteurs ne répondent pas). Un **encart d'aide** dans le panneau « Décision du comité » le signale quand aucune relecture n'est lancée. Test : `AdminSubmissionWorkflowTest` (« accepte directement une soumission déposée »).
- **Espace participant pleine largeur** : `.ukc-content` n'a plus de `max-width` côté participant (`.ukc-shell:not(.ukc-shell--admin) .ukc-content { max-width: none }`) ; l'admin garde sa largeur bornée.
- **Auteurs - Nom/Prénom séparés + téléphone (correspondant ET co-auteurs)** : l'auteur correspondant et chaque co-auteur ont des champs **Nom** (`last_name`, MAJUSCULES) + **Prénom** (`first_name`) séparés, **e-mail**, **téléphone** (intl-tel-input), **affiliation**, **grade**. Co-auteurs sur **une seule ligne** (`.coauthor-row`, responsive : 2 colonnes < 900px). Nom+prénom+e-mail+affiliation **obligatoires** (client `validateStep(2)` + serveur `StoreSubmissionRequest::withValidator` ; identité du correspondant forcée depuis le compte quand « self »). `CreateSubmission` compose `name` = « Prénom NOM » et stocke `first_name`/`last_name`/`phone` pour chaque auteur. L'indice de compte se déclenche **en live** (input debouncé) dès qu'un e-mail valide.
- **⚠️ Téléphone (intl-tel-input) dans un formulaire Alpine** : NE PAS mettre `x-model` sur un `input[data-ukc-tel]` (intl-tel-input possède la valeur → conflit `[object Object]` au changement de pays). On rend la valeur initiale via `x-init="$el.value = …"` (co-auteurs) ou `value="…"` (correspondant), et la normalisation E.164 se fait à la soumission (`initPhoneInputs`). `autoPlaceholder: 'aggressive'` + `placeholderNumberType: 'MOBILE'` affichent un exemple de numéro selon le pays. L'**indicatif** (`.iti__selected-dial-code`) est aligné sur la taille/le poids du champ (14px normal ; 13.5px en co-auteur). Reset du champ correspondant à la bascule « self » : impératif via `#corr-phone` dans `onSelfToggle`. Le bloc auteur correspondant est en **3 colonnes** (`.ukc-form-grid-3`).
- **Plafond de co-auteurs** : `settings.coauthors_max` (défaut 8) limite le nombre de co-auteurs à la soumission - le bouton « Ajouter un co-auteur » notifie (toast) si la limite est atteinte (`submissionWizard.addCoauthor`), et le serveur refuse au-delà (`StoreSubmissionRequest::withValidator`). (Ce réglage servait déjà aux attestations de co-auteurs.)
- **Nom de famille en MAJUSCULES (projet)** : tout champ `last_name` (ou `[last_name]`, ou `data-uppercase`) est affiché en capitales (**CSS** `text-transform: uppercase`) et sa **valeur** est mise en majuscules à la frappe (**handler délégué** dans `app.js`, accents inclus). Pas de mutator modèle (le stock/les seeds existants ne sont pas modifiés) ; les nouveaux co-auteurs sont stockés en MAJUSCULES par `CreateSubmission`. Convention notée dans `CLAUDE.md`.
- **Autocomplétion des affiliations = API ROR** : `App\Support\RorClient` (api.ror.org, libre, sans clé, cache 1 j, fail-closed) → endpoint `account.affiliations` (`SubmissionController::affiliations`, repli sur les affiliations locales connues) → le wizard (`affiliationSuggest`) alimente en direct le `<datalist id="ukc-affiliations">` partagé par les champs affiliation. Tests : `tests/Feature/AffiliationSuggestTest.php`.
- **Vue back-office** : la fiche admin d'une communication (`admin/submissions/show.blade.php`) affiche pour **chaque auteur** son statut de rattachement (pastille **Déposant / Compte rattaché / Invité(e) / Association déclinée / Non rattaché**), le **compte** lié et le **mode de rapprochement** (e-mail/téléphone/nom/manuel). `Admin\SubmissionController::show` charge `authorLinks` (avec `user`).
- **Rattachement manuel (staff)** : depuis la fiche, le staff peut **rattacher** un auteur à un compte (par e-mail : `Admin\SubmissionController::linkAuthor`, `match_type='manual'` + participant + notif/e-mail) ou **détacher** un compte mal rapproché (`detachAuthor` → `unmatched` ; le déposant est intouchable). Routes `admin.submissions.author.link` / `.detach` (cap `submissions_list`).
- **Filtre & indicateur (liste)** : `Admin\SubmissionController::index` - filtre **« Co-auteurs non rattachés »** (`?unlinked=1`, `whereHas authorLinks` role coauthor & statut invited/unmatched/declined) + compteur par ligne (`withCount unlinked_authors_count`) affiché en pastille. Tests dans `tests/Feature/SubmissionAuthorLinkTest.php`.
- **Affichage du résumé** : l'aperçu (slideover liste) montre désormais les **co-auteurs** et le **résumé intégral** ; les boîtes de résumé n'ont plus de barre de défilement (classe `.abstract-box--full` + override `.admin-submission-detail .abstract-box`). Le panneau de correction du résumé côté participant affiche aussi la **liste des auteurs** (lecture seule).
- **Comparaison de similarité** : dans « Similarité interne » (fiche admin), cliquer sur une soumission proche ouvre une **modale côte à côte** (au lieu de naviguer) montrant les deux résumés avec **surlignage des mots partagés** (≥ 5 lettres). Composant Alpine `simCompare` (`resources/js/app.js`) + CSS `.sim-modal`/`.sim-compare-grid`/`.sim-abstract mark`.

### Composants transverses
- **Champ téléphone** - tous les champs numéro de téléphone passent par le composant **`<x-ukc.phone>`** (intl-tel-input v29 : drapeau + indicatif, valeur **E.164** à la soumission via `getNumber()`). À réutiliser pour tout nouveau champ téléphone.
- **Config via Réglages (DB)** - mail/paiements/IA/visio/SMS/nom de plateforme surchargés **au runtime depuis la base** (`AppServiceProvider::applySettingsToConfig()`), pas le `.env` (`MAIL_MAILER=log` prioritaire en dev ; `QUEUE_CONNECTION=database` → worker requis pour livrer).
- **Suppression** - la fonctionnalité **scan de badge** a été retirée du projet. **Admin « Mon profil » → « Mon compte »** (aligné sur l'espace participant).

### Règles métier de soumission
- **Sélection du colloque avant saisie** - le contrôle d'éligibilité se fait **avant** le formulaire (pas à l'enregistrement). Sélecteur (350px) : groupe **« Appels ouverts »** en premier, puis **« Mes évènements »**. Colloque **sélectionné par défaut** = celui **en cours**, sinon le **prochain**, sinon le **dernier** auquel l'utilisateur a participé. On **soumet dans le colloque sélectionné**. Le rappel de quota de soumission a été retiré de l'étape de validation.

### Attestations de communication - par communication + plafonds (2026-09-22)

Règle métier clarifiée et **implémentée** (la fonctionnalité était prévue dans les réglages/UI de l'édition - carte « Attestations & co-auteurs » - mais jamais branchée dans l'émission ; `attestation_max`/`coauthors_max` étaient des réglages morts).

- **Limite de communications** (à la soumission) : inchangée et déjà correcte - `Submission::usedCountFor()` ne compte que les communications où le compte est **auteur correspondant** (participant déposant) ; en **co-auteur**, illimité.
- **Attestation de communication = par communication ET par personne** : sur chaque communication acceptée, l'**auteur correspondant** et chaque **co-auteur payé** ont une attestation **à leur nom**, reliée à la communication.
  - Nouvelle colonne `attestations.submission_id` (nullable - les autres types restent liés au seul participant). Relation `Attestation::submission()`.
  - `IssueAttestation::handle($participant, $type, ?Submission $submission = null)` : idempotence par `(édition, participant, type, submission_id)` ; `meta.communication_title`/`communication_ref` dénormalisés pour le PDF (vue `pdf/attestation.blade.php` nomme la communication).
  - **Plafonds** (depuis `edition.settings`) : `coauthors_max` (défaut 8) = co-auteurs attestés / communication (ordre de la liste `authors`) ; `attestation_max` (défaut 5) = attestations de communication / personne - au-delà, on **privilégie les communications où la personne est auteur correspondant**, puis les autres par **ordre chronologique** (id de soumission).
  - **Co-auteurs payés uniquement** (comportement module 20 conservé) ; l'auteur correspondant n'a pas de filtre paiement additionnel ici.
  - Calcul centralisé : `App\Support\CommunicationAttestations::entitlements($edition)` / `::count($edition)`. Branché dans `Admin\DocumentHubController` (aperçu + `issueType`, le type `communication` sort de `targetsFor()`). Tests : `tests/Feature/CommunicationAttestationTest.php`.
  - **Modèles personnalisés** : la vue par défaut `pdf/attestation.blade.php` nomme la communication ; pour les modèles conçus dans le designer (`AttestationController` → `LayoutRenderer`), les variables **`{communication}` / `{communication_ref}`** ont été ajoutées (`TemplateDesignerController::VARIABLES` + `sampleVars`) → un modèle perso peut aussi nommer la communication. `Attestation.meta` typé (`@property array`). **593 tests verts.**

### Module Témoignages - accueil + sélection assistée par IA (2026-09-22)

Recueil des témoignages des participants et affichage d'une sélection sur la **page d'accueil** (auparavant : 3 cartes en dur dans `home.blade.php`).

- **Périmètre** : **global plateforme** (pas par édition). Table `testimonials` (modèle `App\Models\Testimonial`) : `user_id?`, `edition_id?` (contexte optionnel), `author_name/role/affiliation` (dénormalisés), `quote`, `rating?`, `status` (`pending`/`approved`/`rejected`), `featured`, `position`, `impact_score?`, `impact_reason?`, `consent`, `moderated_at`. Scope `forHome()` = `approved` + `featured`, trié `position` puis `impact_score`.
- **Dépôt** : par un **participant connecté** (guard `account`, `/mes-temoignages`, `Account\TestimonialController`) → `pending`, **consentement obligatoire** (`accepted`), un seul en attente à la fois, `edition_id` vérifié contre les participations (anti-forgerie), nom pré-rempli depuis le compte. Notifie le staff (`Notifier::staff`, nouveau `NotificationKind` `testimonial`). L'admin peut aussi **saisir** un témoignage (approuvé d'office, il en répond).
- **Back-office** (`Admin\TestimonialController`, **capacité globale `testimonials`** ajoutée à `StaffRoles::PERMISSIONS` + rôle `administrator` ; routes `/admin/temoignages` sous `can:testimonials`) : approuver / refuser, **basculer la mise en avant** (uniquement si approuvé), **réordonner** (`order[]`, UI Alpine ▲▼), supprimer.
- **IA à la demande** : bouton « Proposer la sélection (IA) » → `App\AI\ScoreTestimonials` envoie les témoignages **approuvés** à `AiManager::extract()` (JSON), attribue `impact_score` (0-100) + `impact_reason`, et **pré-remplit** `featured`/`position` pour les plus percutants. **L'IA ne fait que proposer** : l'admin ajuste ensuite. Dégradation propre si aucun fournisseur IA (bouton désactivé + `AiUnavailableException` → message).
- **Accueil** : `HomeController` charge `Testimonial::forHome()->take(6)` ; `home.blade.php` rend les cartes dynamiquement (nom + rôle - affiliation), **section masquée si vide** (plus de faux témoignage).
- **Sécurité/contenu** : modération obligatoire (rien de public sans `approved`), consentement requis côté participant, texte échappé par Blade. **Choix v1** : pas d'upload de photo (avatar SVG), note (`rating`) optionnelle et non affichée sur l'accueil.
- **Tests** : `tests/Feature/TestimonialTest.php` (13 cas : dépôt/consentement/unicité/anti-forgerie édition, modération, mise en avant gardée, réordonnancement, saisie admin, application de la proposition IA (AiManager mocké), IA indisponible, accueil ne montre que approved+featured). **616 tests verts, PHPStan niveau 5 propre.**

### Certification cryptographique des attestations - identité vérifiable (2026-09-22)

La « signature / identité vérifiable » (DID/Ed25519) n'était qu'un **placeholder d'UI** (réglages `attestations.did_issuer`/`did_method`/`crypto_suite` non branchés) ; la vérification se limitait à l'existence de l'UUID + le flag `revoked`. Elle est désormais **réelle**.

- **Format - décision de sécurité** : les réglages annonçaient `Ed25519Signature2020` (Data Integrity proof **JSON-LD**), dont l'implémentation correcte exige une **canonicalisation RDF (URDNA2015)** sans librairie PHP éprouvée → écarté (= improviser la crypto). Retenu à la place : **modèle de données W3C Verifiable Credential** sécurisé en **JWS compact EdDSA/Ed25519** (standard W3C « Securing VCs using JOSE/COSE », alias **VC-JWT**), signé via **`firebase/php-jwt`** (EdDSA/libsodium, crypto vérifiée). Le libellé `crypto_suite` a été corrigé (`EdDSA (VC-JWT)`).
- **Cœur** : `App\Documents\AttestationCertifier` - `sign()` (VC-JWT compact), `verify()` (signature + anti-substitution : le VC `id` doit valoir `urn:uuid:{uuid}`), `didDocument()`, `jwks()`, `publicJwk()`. `config/attestations.php`.
- **Clés** : couple **Ed25519** ; graine privée **hors dépôt** (`ATTESTATION_SIGNING_KEY`, base64url 32 o, générée par `php artisan attestations:keygen`) - **jamais** en base ni committée. **Rotation** via `kid` (header JWS) + `ATTESTATION_RETIRED_KEYS` (clés publiques retirées conservées pour vérifier l'ancien stock). Clé publique publiée pour vérification tierce : **`did:web`** (`/.well-known/did.json`, `/colloques/did.json`) + **`/.well-known/jwks.json`** (routes → `AttestationController@didDocument`/`jwks`).
- **Modèle de données** : migration `…000200_add_credential_to_attestations` → colonnes **`credential`** (JWS compact = source de vérité), **`key_id`**, **`signed_at`** (nullable). `Attestation::isSigned()`.
- **Émission** : `IssueAttestation` signe à la création (dépendance `AttestationCertifier` **optionnelle** → l'Action reste `new`-able ; résolution paresseuse via `app()`). **Fail-open** : sans clé, l'attestation est émise **non signée** (jamais de blocage du workflow). Rattrapage du stock : `php artisan attestations:sign [--force]`.
- **Vérification** : `/verifier/{uuid}` **vérifie la signature** (clé publique locale par `kid`) puis existence + non-révocation. **Fail-closed sur la preuve** : « Signature cryptographique vérifiée » n'apparaît que si elle vérifie réellement ; une signature présente mais invalide affiche « Signature non vérifiée » (pas « authentique ») ; sans signature → « Vérifié par le registre officiel ». VC signé téléchargeable en JSON : `/attestation/{uuid}/credential.json` (`attestation.credential`).
- **Réglages effectifs** : `did_issuer` → `iss`/DID ; `crypto_suite` relibellé ; statut de certification (clé configurée ou non) affiché dans l'admin Documents. La clé de signature est **globale** (institution), les identifiants DID restent réglables par édition (`EditionSettings`).
- **Tests** : `tests/Feature/AttestationCertificationTest.php` (**12 cas**) : signe/vérifie, **altération de signature déterministe** (on décode le segment signature et on inverse un bit d'un octet central -> rejet garanti ; fin du flaky, commit `67b32c1`), **payload falsifié** (intégrité : le contenu re-signé ne correspond plus -> `invalid_signature`) et **clé inconnue** (kid absent du JWKS -> `unknown_key`) rejetés, anti-substitution, fail-open, page publique, révoqué malgré signature, DID+JWKS, VC JSON, 404. Confirmé : `verify()` s'appuie sur `firebase/php-jwt` (v7.1.0, protection anti-confusion d'algorithme) qui vérifie la signature sur la chaîne exacte `head.payload` -> **vérification stricte, pas de faiblesse** ; l'ancien flaky venait d'un aléa du TEST (flip d'un caractère base64url pouvant tomber sur les bits de bourrage), pas de `verify()`. Clé figée à vide en test (`phpunit.xml`) → les tests de certif activent une clé éphémère via `config()`.

### Module Formation - Phase 1 : fondations (2026-09-23)

Nouveau module **Formation** (activable par édition), construit **par phases**. Décisions cadrées avec le PO : **plusieurs formations par édition** (table dédiée), **formateurs par e-mail** (liés au compte unifié `users` s'il existe, sinon saisie libre), **frais uniques par formation** (0 = gratuite), **visio réservée aux inscrits payés + formateurs** (phases suivantes).

**Phase 1 livrée (fondations + admin CRUD)** :
- Tables `formations` (edition_id, title, theme, objective, location, room, `fee` decimal 0=gratuite, starts_at/ends_at, capacity, `published`, `public`, colonnes `meeting_*` prêtes pour la visio, meta) et `formation_trainers` (formation_id, **`user_id`** nullable = compte unifié, name, email, affiliation, `is_moderator`, meta). Modèles `App\Models\Formation` (`isFree()`, `trainers()`) et `FormationTrainer` (`user()`).
- **Capacité RBAC `formation`** ajoutée à `App\Support\StaffRoles` (admin/gérant/co-gérant). Middleware `edition.cap:formation`.
- **Activation par édition** : `settings['doc']['formation.enabled']` via `EditionSettings` (toggle porté par la page Formations, pas le gros formulaire d'édition).
- **Admin** : `Admin\FormationController`, routes `admin.formations*`, lien sidebar (icône `book`, capacité `formation`). ⚠️ **Refonte liste/détail (2026-09-26)** : `admin/formations` = **table compacte** (bandeau miniature, dates, lieu, tarif, nb formateurs, inscrits/réglés, état, « Ouvrir ») ; **`admin/formations/{formation}`** (`admin.formations.show`, vue `show.blade.php`) = page de la formation **à onglets** (`.ukc-tabs` + Alpine, ancre `#inscrits`/`#formateurs`/`#communication`/`#visio` mémorisée en `sessionStorage` car les actions repassent par `back()`) : bandeau, 4 KPI (inscrits, réglés, recette, formateurs), **formateurs** (ajout/édition en ligne + rapprochement compte), **table nominative des inscrits** (e-mail, téléphone, montant, référence de paiement, statut), **contacter les inscrits**, **visio**, **attestations**, et le slideover d'édition. Motif : la carte unique ne pouvait pas porter la liste des inscrits. `store` redirige vers la page de la formation, `destroy` vers la liste.
- Tests `tests/Feature/FormationModuleTest.php`. **626 tests verts, PHPStan niveau 5 OK.**
- ⚠️ Rappel **compte unifié (option B)** : participants = `App\Models\User` (les guards `web` et `account` résolvent la table `users`). Toute nouvelle table liée à un participant utilise **`user_id`** (le modèle/colonne `Account`/`account_id` est **legacy et mort**).

**Phase 2 livrée (inscription + paiement)** :
- Table `formation_enrollments` (formation_id, edition_id, **`user_id`**, participant_id nullable, `status` registered→paid, `amount`, `payment_id`, unique(formation_id,user_id)). Modèle `App\Models\FormationEnrollment` (`isPaid()`).
- **Paiement de nature « formation »** : `Payment.meta.flow='formation'` (+ `formation_id`, `enrollment_id`), distinct de participation / co-auteur. Helper `Payment::flow()` (`meta.flow`, défaut `participation`).
- ⚠️ **`ConfirmPayment` rendu flow-aware** : la garde anti-double-paiement (« un seul règlement par participant/édition ») ne s'applique **qu'aux frais de participation** (et ne compte que les paiements de flow `participation`) → participation, formation(s) et co-auteur **coexistent** sans se bloquer. Après confirmation d'un paiement `formation`, l'inscription passe `paid` (chemin universel : retour, webhook, encaissement manuel).
- `PaymentController::return` : branche `formation` → retour sur l'espace « Mes formations ».
- **Espace participant** : `Account\FormationController` (`account.formations` = `/formations`, `enroll` = inscription ; ⚠️ pas de `mes-`/`mon-`/`ma-` dans les URLs, convention projet). ⚠️ **Scopé sur l'édition sélectionnée** (`CurrentColloque::current()`) : `Formation::scopePublishedIn($edition)` liste, `Formation::offeredIn(?$edition)` (module activé + ≥1 formation publiée) conditionne **à la fois** le lien du menu latéral et l'accès à la page (**404** sinon) ; `enroll` exige aussi le module activé. L'ancien `scopeVisibleTo(User)` (toutes éditions ouvertes) a été **retiré**. Page refondue en **cartes larges** (`.fcard` : visuel à gauche ~320px, contenu à droite, empilé sous 860px) : pastilles tarif / EN DIRECT, méta avec icônes, formateurs en avatars, pied d'action. **Accès visio** : un inscrit d'une formation **payante non réglée n'a aucun accès** (le bouton n'apparaît pas et la salle refuse) ; un **formateur est exempté** (`Formation::isTrainer()`) - il rejoint en modérateur, n'est jamais invité à payer (badge « Vous animez cette formation ») et `enroll` neutralise sa tentative d'inscription. **Paiement** : profil complet exigé (`User::profileComplete()`), sinon redirection vers le profil + bandeau listant les champs manquants ; formations publiées des éditions ouvertes ou où le compte participe ; **gratuite → inscription directe**, **payante → passerelle** (upsert du participant en rôle `trainee` via `UpsertParticipantForAccount`). Vue `account/formations/index` + partiel `_pay`, lien nav participant.
- Tests `tests/Feature/FormationEnrollmentTest.php` (dont garde anti-double préservée). **633 tests verts, PHPStan niveau 5 OK.**

**Phase 3 livrée (section page publique)** :
- `Public\ColloqueController` charge les formations **publiées ET publiques** de l'édition **si le module est activé** (`EditionSettings::get(..., 'formation.enabled')`), sinon collection vide.
- Section `#formations` dans `resources/views/public/colloque.blade.php` (après la section Tarifs) : cartes formation (titre, thème, dates, lieu/salle, objectif, formateurs, frais/gratuite) + CTA « S'inscrire » → `account.formations` (l'auth participant redirige au besoin). Lien nav scrollspy conditionnel.
- Tests `tests/Feature/FormationPublicPageTest.php` (visible si module activé + formation publique ; masquée si module off ou formation non publique). **636 tests verts, PHPStan niveau 5 OK.**
- ✅ **Bug résolu** : la section Tarifs plantait (`colloque.blade.php`, `$fee['label']`) quand `settings.fees` contenait une entrée non-tableau. Corrigé par **`Fees::normalize()`** (source unique de robustesse), `ColloqueController` alimente `'fees'` via `Fees::grid($edition)`, `SaveEdition` filtre `is_array($f)`, et le seed `DemoScenarioSeeder` (édition `cip-uk-2023`) écrit désormais la grille au format liste (auparavant `$this->grades`, associatif → source des données malformées). ⚠️ Les bases de dev déjà seedées gardent l'ancienne donnée : la page ne plante plus (normalisée à la lecture) mais `cip-uk-2023` n'affiche ses tarifs qu'après un re-seed. Tests : `tests/Feature/PublicFeesTest.php`.

**Phase 4 livrée (e-mails)** :
- **Confirmation d'inscription** : `App\Mail\FormationEnrollmentMail` (markdown `emails/formation-enrollment`, `ShouldQueue`) envoyée à la création de l'inscription (`wasRecentlyCreated`), **gatée par la préférence** (`Notifier::emailAllowed`) + notification in-app `Notifier::account(kind: 'formation')`. Nouveau kind **`formation`** dans `NotificationKinds` (audience account, `hasMail` true → pas de double e-mail).
- **Message admin aux inscrits** (convocation / rappel) : `Admin\FormationController::message` (`admin.formations.message`) réutilise **`CampaignMail`** (sujet/corps libres) ; cible **tous** ou **réglés** (`audience`), journalise dans `emails` (template `formation`, batch_id). Formulaire « Contacter les inscrits » + compteurs d'inscrits (`withCount('enrollments')` + `paid_count`) dans la vue admin.
- Relation `Formation::enrollments()`. Tests `tests/Feature/FormationEmailTest.php`. **PHPStan niveau 5 OK.**

**Phase 5 livrée (attestations)** :
- Type d'attestation `formation` **repointé** sur le module (émission par formation, plus par rôle `trainee`) + **nouveau type `formation_animation`** (formateurs). Les deux apparaissent automatiquement dans le **designer** (`Attestation::TYPES`).
- Colonne `attestations.formation_id` (nullable, comme `submission_id`) + relation `Attestation::formation()`. `IssueAttestation::handle($participant, $type, ?Submission, ?Formation)` : idempotence par `(édition, participant, type, submission_id, formation_id)`, `meta.formation_title` dénormalisé. Variable **`{formation}`** dans le designer + `AttestationController` (vues HTML/PDF) ; la vue PDF par défaut nomme la formation (« pour la formation / pour l'animation de la formation »).
- **Émission depuis la page Formations** : `Admin\FormationController::issueAttestations` (`admin.formations.attestations`, bouton « Attestations ») → **participation** aux inscrits éligibles (réglés, ou inscrits si gratuite) + **animation** aux formateurs (participant support créé par compte lié, sinon e-mail/nom). L'envoi s'appuie sur la notification d'attestation existante (in-app + e-mail générique pour les comptes). L'ancienne émission `formation` par édition (rôle `trainee`) a été **retirée** de `DocumentHubController`.
- ⚠️ **Infra** : `phpunit.xml` reçoit `memory_limit=512M` (la suite dépassait 128M en grandissant → OOM dompdf). Tests `tests/Feature/FormationAttestationTest.php`. **647 tests verts, PHPStan niveau 5 OK.**
- Reste sur les attestations formateurs sans compte : pas d'e-mail direct (seulement l'attestation vérifiable par lien) - à raffiner si besoin.

**Phase 6 livrée (visio)** :
- ⚠️ **Refactor `App\Meetings`** : introduction de l'interface **`Meetable`** (`meetingRoom()`, `meetingUrl()`, `meetingProviderKey()`) implémentée par **`EventSession` ET `Formation`**. Les type-hints des providers (Jitsi/LiveKit/Custom) et du registry passent de `EventSession` à `Meetable` (aucune logique changée - les providers n'utilisaient que `meetingRoom()`/`meeting_url`). La visio des sessions du programme reste intacte (tests verts).
- **Formation** porte ses helpers visio (colonnes `meeting_*` déjà là depuis la phase 1) : `hasMeeting/isLive/meetingRoom/meetingRoleFor/canJoin/joinUrl`. **Rôle** : staff & **formateurs = modérateur** ; **inscrit éligible** (réglé, ou inscrit si gratuite) = participant ; sinon aucun (pas d'accès public).
- **Admin** : `Admin\FormationController::setMeeting` (`admin.formations.meeting`) → fournisseur (défaut/livekit/jitsi/custom), salle, lien externe, état (aucune/programmée/en direct/terminée), visibilité ; bloc « Visio » par formation.
- **Participant** : `account.formations.visio` → **la page du module visio** (`MeetingController::joinFormation`, vue `meeting.join`), **la même que pour les sessions du programme** : embarquement Jitsi en iframe, lecteur LiveKit inline, ou lien externe, avec arrière-plans virtuels et rôles. Bouton « Rejoindre la visio » sur la carte quand la formation est en direct **et** que le compte y a accès (`canJoin`).
  - ⚠️ **Mutualisation (2026-09-26)** : le socle commun a été extrait dans le trait **`App\Meetings\Concerns\JoinsMeetings`** (`canJoin`, `joinUrl`, `meetingEmbeddable`, `meetingEmbedConfig`, `liveKitEmbedConfig`, nom affiché) utilisé par `EventSession` **et** `Formation` ; le contrat **`Meetable`** décrit désormais tout ce dont la page « Rejoindre » a besoin (état, rôle, embarquement). `MeetingController::room()` rend la salle pour n'importe quel `Meetable` ; la vue `meeting.join` ne référence plus `$session` mais des variables normalisées (`$label`, `$editionTitle`, `$isLive`, `$statusLabel`, `$startsAt`, `$recordingUrl`, `$backUrl`, `$engagementUrl`). **Effet de bord corrigé** : `EventSession` utilisait `$account?->full_name`, qui n'existe pas sur `User` (c'est un champ de `Participant`) → les participants apparaissaient « Invité » dans les salles ; le trait utilise `users.name`.
- Tests `tests/Feature/FormationVisioTest.php`. **653 tests verts, PHPStan niveau 5 OK.**

**Module Formation : demande initiale bouclée** (activation par édition, définition complète, page publique, e-mails, paiements distincts, attestations participation + animation, visio). **Suivi admin nominatif** des inscrits ajouté : `Admin\FormationController::index` charge `enrollments.user` ; la page Formations affiche par formation une liste dépliable « Liste des inscrits » (nom - e-mail - Réglé/Inscrit) en plus des compteurs.

**Raffinements admin (2026-09-23)** :
- **Activation dans les paramètres de l'édition** : le toggle « Module Formations activé » vit dans le **formulaire d'édition** (onglet Options, `settings.formation_enabled` via `SaveEdition`), plus sur la page Formations. Lecture : `Edition::formationEnabled()`. (⚠️ ancien stockage `settings.doc.formation.enabled` + `toggleModule`/route retirés.)
- **Slideovers (comme la page Projet)** : bouton « Nouvelle formation » **dans l'en-tête** (à droite du sélecteur d'édition, style `ukc-btn--blue ukc-btn--sm`) ouvrant un `<x-ukc.slideover>` (création, POST multipart) ; **édition = un slideover par carte** (`editOpen` local, bouton « Modifier ») → fini les champs pleine largeur. La liste des formations est compacte et pleine largeur. Bouton « Paramètres de l'édition » retiré de la page (l'activation se fait dans le formulaire d'édition).
- **Flyer / bannière** : colonne `formations.flyer_path` + `Formation::flyerUrl()` (disque public) ; **bannière = image PNG/JPG** (validée `image|mimes:png,jpg,jpeg`). Champ **en haut du formulaire** = zone de dépôt **cover** (Alpine inline) : l'image remplit la zone (pas de nom de fichier), boutons **pen** (changer) / **trash** (retirer) en overlay. La **bannière s'affiche sur la carte** de formation (admin, page publique, espace participant). Formulaires en `.ukc-form-row` + grilles `minmax(0,1fr)` (évite le scroll horizontal du slideover), pied **Annuler / Enregistrer** (bouton `form="…"`), sélecteur d'édition élargi (460px).
- **Formateurs enrichis** : colonnes `formation_trainers.title` (grade M./Mme/Pr./Dr.…), `specialty` (spécialité/fonction), `photo_path`. `FormationTrainer::photoUrl()` = photo propre → **photo du compte lié** (`user.photo_path` si e-mail = compte) → sinon avatar par défaut (initiale). Ajout via formulaire **sur une ligne** (grille façon co-auteur du formulaire de soumission : avatar + grade + nom + spécialité + e-mail + Ajouter). **Rapprochement compte (comme les co-auteurs, 2026-09-26)** : colonne `formation_trainers.phone` ; endpoint `admin.formations.trainers.lookup` (`lookupTrainer`) + composant Alpine `trainerLookup` → à la saisie de l'e-mail **ou** du téléphone, les autres champs sont pré-remplis et le nom verrouillé. ⚠️ Côté serveur, `trainerAccount()` (identifiants forts uniquement, jamais par nom) + `trainerAttributes()` **imposent** nom/e-mail/téléphone du compte : impossible d'enregistrer un formateur sous l'e-mail d'un autre utilisateur. Grade et spécialité restent propres à la formation. **Clic sur l'avatar d'un formateur** = changer sa photo en **AJAX** (composant Alpine `formationTrainer`, XHR vers `admin.formations.trainers.photo` qui renvoie `{ok,url}` en JSON via `expectsJson()`) avec **barre de progression** sur l'avatar et **contrôle d'anomalie** client (type PNG/JPG + taille ≤ 4 Mo) ; le bouton **crayon** ouvre l'**édition en ligne** des infos du formateur (grade/nom/spécialité/e-mail/modérateur, `admin.formations.trainers.update` PUT, re-liaison compte par e-mail). ⚠️ Les photos/bannières téléversées s'affichent via `/storage/…` → **`php artisan storage:link` requis** (fait en dev).
- **Paramétrage assisté IA** (comme l'édition) : action `App\Actions\ExtractFormationFromDocuments` (schéma titre/thème/objectif/lieu/salle/frais/dates), endpoint JSON `Admin\FormationController::aiAssist` (`admin.formations.ai`), panneau IA (Alpine inline) en tête du slideover → analyse document/consigne puis remplit les champs du formulaire par `name`. Affiché seulement si `aiAvailable` (`AiManager::resolve()`).
- Tests : `FormationFlyerTest`, `FormationAiTest` + `FormationModuleTest` adaptés. **658 tests verts, PHPStan niveau 5 OK.**

**Reste optionnel** : un **lecteur visio inline** pour la formation (au lieu de la redirection).

### Cycle de vie de la relecture - « le moteur propose, l'humain valide » (2026-09-27)

**Constat de l'audit** : l'attribution était bonne (planner déterministe, COI,
candidatures, charge) mais la relecture vivait dans deux états seulement
(`assigned`, `completed`). Conséquences : un relecteur ne pouvait **ni accepter
ni refuser**, une relecture confiée la veille était comptée « en retard » à
égalité avec une oubliée depuis deux mois, retirer une assignation **effaçait**
la trace, et un refus ne libérait pas la place.

**Mise en œuvre** (migration `2026_09_27_000200_add_lifecycle_to_reviews.php` :
`assigned_at`, `assigned_by_id`, `assign_reason`, `due_at`, `responded_at`,
`decline_reason`, index `(status, due_at)`, bascule `assigned` → `invited`) :

- **États** : `Review::STATUSES` invited → accepted/declined → completed, plus
  cancelled ; `ACTIVE_STATUSES` = ce qui occupe une place ; helpers
  `statusLabel()`, `statusPill()`, `isPending()`, `isActive()`, `isLate()`,
  `daysLate()`, et **`Review::summarize()`** (phrase d'état du comité).
- **Échéance** : `ReviewGrid::dueDays($edition)` (réglage `review_due_days`,
  1-180 j, champ dans l'onglet Relecteurs de la fiche d'édition, enregistré avec
  la grille par `EditionFormController::saveReviewGrid`) ; `AssignReviewer` pose
  `due_at` à l'assignation et **rouvre** la même ligne (plutôt que d'en créer une
  seconde) quand le comité ré-attribue après un refus/une annulation.
- **Attribution auto** : `AutoAssignReviewers` ne compte que les relectures
  actives pour la couverture **et** la charge - un refus libère réellement la
  place - tout en continuant d'exclure du plan un relecteur déjà sollicité. La
  **justification du moteur suit la paire** (champ caché `reasons[sub:rev]` dans
  l'aperçu → `assign_reason` sur la relecture) : on sait *pourquoi* untel a été
  sollicité.
- **Espace relecteur** (`/relectures`) : colonnes **Échéance** et **État**,
  boutons **Accepter / Refuser** (motif facultatif dans un slideover, route
  `account.reviews.respond`), KPI « Invitations à confirmer » et « En retard ».
  `submit()` refuse (422) une relecture `declined`/`cancelled` et pose
  `responded_at` (rendre vaut accepter).
- **Fiche admin d'une soumission** : phrase `Review::summarize()`, pastille
  d'état par relecture, échéance ou retard en jours, motif du refus ou
  justification d'attribution. Le bouton « retirer » **annule** (statut
  `cancelled` + notification au relecteur) au lieu de supprimer.
- **Rapports** : `EditionReport::reviewStats()` renvoie `pending`/`late`/
  `declined` (le « late » d'avant était l'inachèvement) ; `reviewsByReviewer()`
  ne fait plus peser un refus sur la complétion et affiche « N refus ».
- Notification staff `review_response` (registre `NotificationKinds`).
- Tests : **`ReviewLifecycleTest`** (16 cas). **748 tests verts, PHPStan niveau 5
  OK, Pint OK.**

**Choix assumés** (les deux questions ouvertes de l'audit, tranchées faute de
réponse) : **échéance par édition** (durée standard paramétrable, 21 j par
défaut) plutôt que par soumission, et **un refus libère la place sans
remplacement automatique** - cohérent avec la philosophie du module, où le
moteur propose et l'humain valide.

### Relecture - cohérence d'ensemble, design et réglages (2026-09-27)

Revue de **toutes** les surfaces qui embarquent de la relecture, après la mise en
place du cycle de vie. Les écarts trouvés et corrigés :

- **Statut hors vocabulaire en base** : les jeux de démonstration créaient des
  relectures en `pending`, absent de `Review::STATUSES`. Avant le cycle de vie
  « tout ce qui n'est pas rendu » était traité pareil, donc la valeur passait
  inaperçue ; ensuite elle sortait de tous les compteurs. Migration
  `2026_09_27_000300_normalize_review_statuses` (statut inconnu → `invited`,
  échéance manquante = assignation + durée standard) + seeder aligné.
- **Liste des soumissions** : nouvelle colonne **Relectures** (`1/2`, plus une
  pastille rouge « +N j » si retard) via `Review::progressOf()` - le comité ne
  devait plus ouvrir chaque fiche pour savoir où en est une relecture.
- **E-mail d'invitation** : il annonçait la relecture sans dire l'échéance ni
  qu'on peut refuser ; il porte maintenant les deux (`ReviewerInvitationMail`
  reçoit la `Review`).
- **Espace relecteur** : sous-navigation `?tab=relectures|candidatures` (l'appel
  à candidatures n'était plus visible sous la table filtrée), barre de filtres
  (état, événement, **axe** en Tom Select, recherche) identique aux listes admin,
  et **KPI disjoints** - « à évaluer » comptait aussi les invitations, donc les
  nombres ne s'additionnaient pas ; chaque carte porte désormais son propre
  retard. Les filtres portent sur la liste, jamais sur les compteurs.
- **Conflit « même institution » désactivable** par édition (onglet Options).
- **Design** : la pastille d'état ne vire plus au rouge quand la relecture est en
  retard (le retard a son propre marqueur) ; sur la fiche d'une soumission,
  l'état se lit **sous le nom** du relecteur et l'action « retirer » est un
  bouton fantôme qui ne rougit qu'au survol (avant : trois niveaux à droite et un
  « × » orphelin) ; l'échéance disparaît dès que la relecture n'est plus due.
- **Kit Tailwise partagé** : KPI, barre de filtres et boutons `--sm` passent de
  `.admin-page` à `.ukc-shell`, donc l'espace participant a enfin le même rendu
  que l'admin (il retombait sur la carte centrée d'origine et sur la grille
  4 colonnes du site public).
- **Bulle du chatbot** : masquée pendant une surface modale, effacée quand elle
  recouvre une action, `z-index` ramené de 9999 à 55. Elle recouvrait le bouton
  « Confirmer » des slideovers et la dernière ligne des tableaux.

⚠️ **Piège Blade rencontré** : un `@php(...)` **en ligne placé avant** un bloc
`@php … @endphp` dans la même vue produit `<?php(` non terminé - tout ce qui suit
devient du PHP brut et la page tombe sur un « Undefined variable » très loin de
la cause. Forme bloc partout dans une vue qui en contient déjà une.

**755 tests verts** (`ReviewLifecycleTest` : 20 cas), PHPStan niveau 5 OK, Pint OK.

**Vocabulaire harmonisé (2026-09-27)** - « avis » a disparu de l'UI et du code
au profit de **relecture** (la tâche) et **évaluation** (son contenu) :
tableau de bord admin (« en attente de relecture », « N soumission(s) en
relecture à décider »), rapports (« Relectures rendues », « Rendues »),
historique d'une soumission (« Relecture de X rendue »), espace auteur
(« Relectures rendues », « Les évaluations détaillées restent confidentielles »),
`StaffRoles` (« Évaluer (relectures) »), et le compteur de l'espace relecteur
passe de « Évaluées » à **« Rendues »** (le même mot que la pastille d'état).
En le faisant, un **troisième** `status != 'completed'` est apparu : le tableau
de bord du participant comptait ses relectures refusées dans « à évaluer ». D'où
`Review::PENDING_STATUSES` et un **test de garde** qui scanne `app/` et
`resources/views/`. **757 tests verts.**

### Module Soumissions - réorganisation en onglets (2026-09-27)

Les réglages liés aux soumissions et à la relecture étaient éclatés sur quatre
écrans, et les mêmes relecteurs s'affichaient à trois endroits avec trois
définitions. Tout est regroupé dans **un module à cinq onglets** :

| Onglet | Route | Contenu |
|---|---|---|
| Soumissions | `admin.submissions.index` | la liste |
| Formulaire de soumission | `admin.editions.form` | composition des champs |
| Attribution auto | `admin.submissions.auto-assign` | plan proposé + charge |
| Relecteurs & expertise | `admin.submissions.reviewers` | axes évaluables par relecteur |
| Options | `admin.submissions.options` | règles : dépôt, attribution, évaluation |

- La sous-navigation vit dans **un partiel unique** (`admin/submissions/_nav`)
  au lieu d'être recopiée dans chaque vue.
- **Supprimés** : l'onglet *Relecteurs/Évaluation* de la fiche d'édition et
  l'onglet *Règles & limites* de l'éditeur de formulaire. Leur contenu est dans
  **Options**, avec les paramètres du moteur d'attribution qui n'étaient que des
  **paramètres d'URL** (donc non persistants) : nouveaux réglages
  `review_target` / `review_max_load` (`ReviewGrid::target()` / `maxLoad()`).
- ⚠️ `admin.editions.form.update` reconstruit champs et champs personnalisés
  depuis la requête : les règles ont donc leur **endpoint dédié**
  (`admin.editions.form.rules`), sinon enregistrer les champs les effacerait.
  Test de garde : « ne perd pas les règles quand on enregistre les champs ».
- Les onglets vivent sous `submissions_list` mais écrivent dans l'édition :
  affichage en **lecture seule** sans `editions_write`.

⚠️ **Bug de fond découvert au passage** : l'appariement par axe ne fonctionnait
**jamais**. L'expertise stocke une copie du libellé de l'axe ; en base les
relecteurs portaient un tiret cadratin là où l'édition écrivait un tiret simple,
si bien que l'égalité stricte échouait en silence et que le moteur
proposait toujours « hors axe (équilibrage) ». Correction : comparaison par clé
normalisée (`ReviewerExpertise::axisKey()` / `covers()`, utilisée aussi par
`ReviewerAssignmentPlanner`), et l'enregistrement recanonise l'axe sur le libellé
de l'édition.

**764 tests verts**, PHPStan niveau 5 OK, Pint OK.

> **Mémoire projet** : le contexte fin (décisions, conventions, TODO différés - DOI/DataCite, connecteur Google anti-plagiat, amélioration `admin/reclamations`) est aussi consigné dans la **mémoire locale de Claude Code** (`~/.claude/…/memory/`, non versionnée). Comme cette mémoire ne se partage pas entre contributeurs, **le contexte partageable doit vivre dans `CLAUDE.md` + ce fichier** (versionnés) : les tenir à jour à chaque tâche notable.

## Tutoriels vidéo - captures authentifiées et lancement serveur autonome (2026-10-09)

- `admin/prise-en-main` démarre désormais un processus serveur dédié qui lit les
  demandes `queued` dans la base et les rend avec Gemini/Claude/OpenAI puis
  Hyperframes. Les clics administrateur ne requièrent ni Codex ni un worker de
  queue installé sur leur poste.
- Les compositions utilisent le logo compact configurable (`app_logo_mark`), agrandi
  dans le verrou de marque pour correspondre au bloc de texte voisin et les **vraies
  pages GEVES** à chaque étape du guide. **Uplifting Corporate Loop**
  (`resources/media/tutorials/uplifting-corporate-loop.mp3`) est la musique locale
  par défaut. L’administrateur peut choisir `calm`, `dynamic`, sans musique, ou
  téléverser un MP3/M4A/AAC/WAV de 25 Mo maximum (le fichier est prioritaire, bouclé
  à la durée du guide et normalisé par FFmpeg).
  `TutorialVideoScreenshotService` génère pour chaque scène une URL signée, ouvre le
  pont `tutorial.capture`, connecte dans le navigateur Hyperframes le compte technique
  `captures-tutoriels@geves.invalid`, puis copie la capture dans le dossier de la
  demande. Ce compte est créé à la volée, n’est jamais utilisé par une personne et
  évite toute capture de session administrateur réelle. La capture dépend de la CLI
  Hyperframes déjà utilisée par le rendu (`npx hyperframes capture`) ; conservez Node,
  npx, FFmpeg et l’accès de l’hôte de rendu à l’URL configurée dans `APP_URL`.
- Le modal de demande distingue la première production de la mise à jour et précise
  que les écrans proviennent de GEVES. Dans le menu staff, « Prise en main » vit sous
  **Système**.
- Sous Windows, le lanceur ne se fie plus au seul PID mis en cache : il contrôle la
  ligne de commande du processus (`tutorial-videos:render-pending`). Un PID réutilisé
  par Windows ne peut donc plus laisser une demande `queued` bloquée indéfiniment.
- La demande est asynchrone côté serveur, mais **synchrone à l’usage** : l’envoi AJAX
  garde l’administrateur sur la page et ouvre le calque bleu de production. Le point
  `admin.onboarding.videos.status` restitue `meta.progress` (scénario, captures,
  montage, publication) toutes les deux secondes ; à `ready`, la page se recharge et
  affiche le MP4 sans intervention manuelle.
- Le formulaire de production conserve un champ caché `action`. Pour poster en AJAX,
  l’URL doit venir de `form.getAttribute('action')` et non de `form.action`, lequel
  est masqué par le champ et produirait `/admin/[object HTMLInputElement]`.
- Sur Windows, `TutorialVideoWorkerLauncher` choisit `php.exe` voisin de `PHP_BINARY`
  puis vérifie le processus avant de garder son PID en cache. PHP-FPM peut déclarer
  `php-cgi.exe`, lequel crée un PID temporaire mais ne peut pas exécuter Artisan.
- `TutorialVideoRenderer` résout `ffmpeg.exe` et son compagnon `ffprobe.exe`, puis les
  transmet à Hyperframes avec `HYPERFRAMES_FFMPEG_PATH` et
  `HYPERFRAMES_FFPROBE_PATH`, car le worker détaché n’a pas toujours le `PATH` de la
  session administrateur. Le rendu sort nativement en **Full HD 1920×1080** ; les
  captures authentifiées sont produites dans ce même format et restent donc nettes.
  Le worker reste unique et utilise le mode faible mémoire : la charge est portée par
  le serveur Laravel qui rend la vidéo, pas par le navigateur de l’administrateur.
  Les variables `TUTORIAL_VIDEO_RENDER_WIDTH` et
  `TUTORIAL_VIDEO_RENDER_HEIGHT` sont réglées à 1920×1080.

**Tests SQLite** : `bootstrap/cache/config.php` peut pointer vers la base locale
malgré `DB_DATABASE=:memory:`. `phpunit.xml` force donc un autre chemin de cache
(`bootstrap/cache/config.testing.php`) pour que les tests utilisent la mémoire.
Ne pas retirer ce réglage ; lors d'une exécution manuelle, fixer également
`DB_CONNECTION=sqlite` et `DB_DATABASE=:memory:`.

## Prise en main - refonte visuelle (2026-10-10)

- L'espace participant `/prise-en-main` affiche un bandeau de bienvenue, un résumé du parcours, des catégories cohérentes avec le back-office et une carte mise en avant pour le premier guide avec vidéo. Chaque guide garde son objectif, son nombre d'étapes, ses étapes dépliables et le lien vers le module concerné ; la vidéo reste réservée aux rôles autorisés.
- Le back-office `/admin/prise-en-main` conserve ses commandes d'édition et de production, avec des cartes plus lisibles et des étapes repliables. Les styles communs vivent sous `.ukc-onb` dans `resources/css/admin.css`, avec des adaptations mobiles propres à la page participant.
- Vérification visuelle effectuée dans le navigateur intégré aux formats bureau et téléphone. La couleur du texte du bouton du bandeau doit garder une spécificité supérieure à `.ukc-scope a:not(.ukc-btn)` ; sinon le texte devient blanc sur blanc.
- Le modal individuel « Produire / Mettre à jour la vidéo » utilise la largeur standard de 720 px, avec corps défilant et pied de formulaire toujours visible et correctement espacé. Le bloc explicatif « Captures réelles » a été retiré du modal. Le bandeau « Production vidéo assistée » a ensuite été retiré de la page admin pour laisser les guides directement sous les indicateurs.
- Dans les vidéos rendues par `resources/scripts/tutorial-video-renderer.mjs`, la scène de chaque étape donne la majorité de sa largeur à la capture 16:9 et la montre en entier. Le bandeau « Écran réel GEVES » n'est plus incrusté ; le texte d'étape est plus petit et bleu ardoise. Les MP4 déjà publiés gardent leur ancien montage jusqu'à une nouvelle demande « Mettre à jour la vidéo ».

## Premier Colloque Scientifique sur la Problématique de l’Eau au Togo (2026-10-10)

- `database/seeders/ColloqueEauTogoSeeder.php` est un **unique fichier autonome** pour importer le colloque, ses six ateliers thématiques, 44 personnes, 47 résumés et 47 pièces Word. L'export XLSX du 10/10/2026 et l'archive des réponses sont intégrés dans le fichier ; aucune lecture du dossier Téléchargements à l'exécution. Les trois secondes soumissions ont été rapprochées de leurs documents par leur titre. Commande volontairement manuelle : `php artisan db:seed --class=ColloqueEauTogoSeeder`.
- Source de l'en-tête du formulaire : échéance du 15 octobre 2026, trois numéros de téléphone et `colloque_eau_uk@univkara.tg`. L'organisateur a confirmé les dates du **11 au 13 novembre 2026**, qui correspondent aux affiches officielles. Le lieu, Université de Kara, est mentionné dans les documents. Les six axes proviennent des réponses. Pas de paiements réglés, décisions scientifiques, comptes utilisateur ou affectations staff inventés.
- Les coordonnées originales, préférences de présentation et de paiement, liens Drive et champs libres de co-auteurs sont conservés dans `meta`. Le seeder crée ou réutilise les comptes globaux des 44 auteurs répondants et relie leurs fiches et 47 liens de soumission. Les nouveaux comptes ont un mot de passe aléatoire inaccessible et `must_reset_password` ; les titulaires devront utiliser « Mot de passe oublié » et vérifier leur adresse. Une adresse source invalide reçoit un compte réservé `@geves.invalid` à corriger manuellement ; aucun message n'est envoyé par l'import. Les co-auteurs en texte libre ne sont pas transformés arbitrairement en personnes distinctes. Le seeder est idempotent et vérifie SHA-256 des pièces jointes ; il ne figure pas dans `DatabaseSeeder` parce qu'il s'agit de données réelles. **Ne pas utiliser `colloques:reseed` après cet import** : cette commande efface tous les événements avant de recréer la démo.
- La correction de date du 11 novembre est appliquée aussi lors d'une nouvelle exécution sur un événement déjà importé, sans écraser ses autres réglages. Vérification sur une copie isolée (premier passage, relance, correction d'une ancienne date), puis import dans la base locale : 44 participants, 47 soumissions, 47 liens d'auteur, six ateliers et 47 fichiers Word contrôlés par SHA-256 ; `PRAGMA quick_check = ok`.
- Deux affiches officielles ont été remises ensuite. Elles sont embarquées en JPEG dans le seeder avec contrôle SHA-256 et enregistrées comme documents de l'événement. Le seeder reprend le thème officiel, l'appel lancé le 22 mai 2026, la notification du 16 octobre, les six catégories tarifaires (35 000 / 30 000 / 10 000 / 45 000 / 50 000 / 100 000 FCFA), la limite de résumé à 250 mots et les consignes Times New Roman 12/interligne simple. Le lien Google Forms reste une référence de source ; GEVES garde son propre parcours de soumission. Les affiches indiquent **11-13 novembre**, conformément à la date confirmée. Elles restent des documents de l'événement car leur format portrait serait fortement recadré comme bannière publique.
- La relance du seeder sur la base locale a ajouté les deux médias et la grille tarifaire sans dupliquer les 44 participants ni les 47 soumissions ; les deux JPEG stockés correspondent à leur SHA-256 et la base passe `PRAGMA quick_check`. Les profils « Professionnel », « Association », « ONG » et « Entreprise privée du secteur de l’eau » ne figurent pas dans les types de participant globaux par défaut : les ajouter dans Réglages avant de compter sur l'affectation automatique de leurs tarifs. Le seeder ne modifie pas ces réglages globaux partagés par les autres événements.
- Le nom court devient « Colloque Eau au Togo ». Pour les mots-clés, l'éditeur du champ propose virgule, point-virgule ou les deux par événement ; le seeder sélectionne les deux pour cet import. `SubmissionKeywords` est utilisé dans la validation, Tom Select, les badges et les sorties documentaires afin de ne pas afficher les délimiteurs comme des mots-clés.

## Tableau de bord participant - bascule sur les dates (2026-10-10)

`/compte` ne montre plus la même page du début à la fin du colloque : **il bascule
tout seul** selon le moment du parcours.

- **Une seule définition de la bascule** : `EventPhase::participantView()` rend
  `avant` / `pendant` / `apres`, dérivé de `Event::phase()` - celui dont le site
  public se sert déjà (`publicView()`). ⚠️ **Ne jamais recomparer des dates dans
  le contrôleur ou la vue** : deux règles finiraient par se contredire sur le même
  écran. Elle suit aussi l'**archivage explicite**, pas seulement l'horloge.
  - `avant` (brouillon, appel ouvert, évaluation, inscriptions) : échéance, dépôt,
    parcours complet en sept étapes, tarif applicable.
  - `pendant` (`Ongoing`) : **mon prochain passage**, mon programme du jour,
    séances en direct. Le déroulé en étapes disparaît - il n'annoncerait qu'un
    travail déjà fait.
  - `apres` (`Closed`) : les documents passent en carte principale.
- **Assemblage dans `App\Support\ParticipantDashboard`** (lecture seule, testable
  sans HTTP) : `vue()`, `jour()`, `prochainPassage()`, `passages()`, `agenda()`,
  `communications()`, `indicateurs()`, `aFaire()`, `etapes()`, `documents()`.
  Le contrôleur ne fait plus que résoudre l'événement de contexte et passer la main.
- **Deux manques comblés, tous deux mesurés sur la base de démonstration :**
  - ⚠️ Le tableau de bord **savait** quand et où le participant présente et ne le
    disait pas : le contrôleur calculait les créneaux datés pour n'en garder qu'un
    booléen `$hasAgenda`, qui n'affichait qu'un bouton « Télécharger (.ics) ».
  - ⚠️ Le compteur de communications ignorait celles dont le compte est
    **co-auteur** : il annonçait « 1 » là où `/compte/communications` en listait
    trois. Même règle désormais (dépôt **ou** `authorLinks` confirmée).
- ⚠️ **Vocabulaire : « co-auteur », jamais « co-signataire ».** C'est le mot de la
  fiche de soumission et de la colonne « Mon rôle ». (Reste « co-signé » dans
  `ConflictOfInterest`, où il décrit une publication commune entre deux
  personnes - un autre objet, à trancher si on veut l'aligner aussi.)
- ⚠️ **Le prochain passage retient AUSSI les communications en co-auteur** : un
  co-auteur ne présente pas seul, mais il **co-présente** - il est en salle à
  l'heure dite. L'écarter revenait à ne rien lui dire du moment où sa
  communication passe. Son rôle est en revanche nommé partout
  (`ParticipantDashboard::roleSur()`) : « Je présente » pour l'auteur
  correspondant, « Je co-présente » pour le co-auteur, distingués par la clarté
  autant que par la teinte. ⚠️ Le créneau porte le `participant_id` du
  **déposant** : le rôle se lit sur la communication, jamais sur le créneau.
- ⚠️ **L'export `.ics` part des MÊMES créneaux** : `AgendaController::events()`
  appelle `ParticipantDashboard::passages()`. Les deux décidaient chacun de leur
  côté de ce qui compte comme un passage, et un bouton « Ajouter à mon agenda »
  posé sous un créneau que le fichier n'exporte pas rend un agenda amputé sans
  rien dire.
- ⚠️ **L'état vide ne réclame plus un profil déjà complet** (défaut constaté en
  production) : son appel à l'action sort de `aFaire()`, et quand la liste est
  vide il annonce « Votre profil est complet » au lieu d'inventer une tâche.
  Deux cas l'encadrent, un par branche.
- ⚠️ **Une fonction ne vient pas avec une inscription** : modérateur, rapporteur et
  relecteur sont rapprochés par **e-mail** et peuvent n'avoir aucun `Participant`,
  donc aucun événement de contexte. L'état vide ne s'affiche que si le compte n'a
  **ni participation, ni appel ouvert, ni fonction** - sans cette garde, un
  modérateur tombait sur « votre espace se remplira bientôt ». Défaut introduit
  pendant la refonte, attrapé par `AccountActivityTest`.
- **Les indicateurs portent quatre CHIFFRES.** Deux cartes affichaient une coche
  (« Profil complet ✓ ») ou des points de suspension : une tâche n'est pas une
  grandeur, elle ne se compare à rien et n'était pas cliquable. Elle vit dans
  « À faire », qui ne détaille que ce qui **reste**, avec une barre d'avancement.
- **Styles sous `.tdb-*`** (`resources/css/ukc.css`, fin de fichier) : la vue
  portait **62 styles en ligne** contre 10 sur le tableau de bord admin. Il en
  reste un seul, la largeur calculée de la barre d'avancement.
- La règle qui centrait l'icône des cartes KPI de l'espace participant a été
  **retirée** de `tailwise.css` : les cartes sont désormais identiques à celles du
  back-office, badge d'angle compris.
- Vues : `account/dashboard.blade.php` + partials `_tdb-agenda` et `_tdb-documents`.
  Tests : `tests/Feature/ParticipantDashboardTest.php` (17 cas). Mesuré au
  navigateur : aucun débordement horizontal à 375px ni à 708px, contrastes de 5,02
  à 11,81:1, pastilles de document à 48px.

## Tableau de bord participant - passe visuelle sur données réelles (2026-10-11)

Les 17 cas automatisés passaient, mais **aucun œil n'avait vu l'écran rempli**.
Passe menée sur le compte de démonstration le plus fourni (3 communications dont
2 en co-auteur, 3 passages, 2 paiements, 3 documents), dans les trois états.

- ⚠️ **Comment voir l'écran d'un participant sans toucher à une session.** Le
  navigateur de travail est connecté en back-office et l'usurpation est refusée :
  le contrôleur a été **rendu en ligne de commande** (`Auth::guard('account')
  ->setUser()` puis `->render()`), le HTML déposé sous `public/_passe-visuelle/`
  le temps de la passe, puis **supprimé**. Servi par `geves.test`, donc même
  origine : CSS et Alpine réels. L'état « pendant » s'obtient avec
  `Carbon::setTestNow()`, **sans toucher aux dates en base**.
- **« Salle Salle 5 ».** `slots.room` contient déjà « Salle 5 », « Auditorium »,
  « Grand amphithéâtre » : le préfixe `__('Salle')` doublait le mot. Retiré des
  trois endroits du tableau de bord, **puis de `public/deroulement` et de
  `account/submissions/show`** (trois autres endroits), qui affichaient « Salle :
  Salle 5 » et « Salle Auditorium ». La règle est tenue par
  `tests/Feature/SalleLibelleTest.php`, **un cas par ENDROIT** (pas par écran) :
  elle se perd endroit par endroit, et rien ne la rappelait. ⚠️ La liste des
  créneaux au choix a son cas à elle, parce qu'elle ne s'affiche QUE sans
  créneau attribué - le cas du créneau attribué ne pouvait pas la couvrir.
  Vérifié en ne sabotant QUE cette ligne : un seul cas tombe. Les deux derniers endroits ont suivi :
  `SecretariatController::programContext()` (texte de contexte des documents) et
  le message d'association d'un lieu à un atelier. Là, l'étiquette est
  **nécessaire** - une suite de champs nommés, une phrase - donc elle devient
  « Lieu ». ⚠️ Et elle doit être **masculine** : l'accord du participe porte sur
  le nom générique, pas sur la valeur, et « Salle « Auditorium » associée »
  était faux. Le message est sous garde dans `ProgramSlotsTest`, par l'absence
  du doublon plutôt que par le libellé exact.
- **Quatre lignes pour deux choses.** « Mon programme » listait le passage **et**
  la séance qui le contient, laquelle ne disait rien de plus (la ligne du passage
  nomme sa séance et sa salle). Seules les plénières s'ajoutent désormais.
- **Le liseré de 22px n'est pas un débordement.** `ol.tdb-ag` mesure
  `scrollWidth - clientWidth = 22` à toutes les largeurs : c'est la marge négative
  qui fait saigner la ligne surlignée jusqu'au bord de la carte, égale au
  rembourrage de `.ukc-card`. Vérifié : rien ne dépasse le viewport, à 375px comme
  à 1366px.
- ⚠️ **Le volet navigateur rend la page À L'ÉCHELLE** : `getBoundingClientRect()`
  y rendait 32px là où le CSS pose `min-height: 36px`, et `border-width` ressortait
  à `0.888889px`. Lire la largeur de bordure calculée pour connaître le facteur
  **avant** de conclure qu'une cible tactile est trop petite - j'ai failli
  « corriger » une règle correcte.
- **« Mes axes » : un axe par ligne, coupé par le CSS.** Les intitulés étaient
  rognés à 48 caractères PUIS joints par un point médian dans la colonne étroite des
  autres faits : un intitulé d'atelier en fait couramment 70, la coupe tombait
  en plein mot et les points de suspension se mêlaient aux séparateurs. La ligne
  passe en pleine largeur, chaque axe est une pastille à part, et la coupe est
  faite par le CSS (`line-clamp: 2`, `title` porte l'intitulé entier). Mesuré :
  les trois axes du jeu de démonstration tiennent **en entier**, un axe de 170
  caractères se coupe à deux lignes, et la carte fait exactement la hauteur de
  la carte haute (376px) au lieu de la dépasser.
  ⚠️ Le premier cas écrit pour cette règle **ne mordait pas** : `assertSee($long)`
  passe même quand l'affichage est rogné, puisque l'intitulé entier reste dans
  l'attribut `title`. C'est `assertDontSee(Str::limit($long, 48))` qui tient la
  règle. Un cas qui ne tombe pas sous sabotage ne garde rien.
- Reste en l'état : la carte haute de l'état « après » est un peu creuse (elle
  s'aligne sur le volet de droite). Choix d'affichage, pas un défaut.

## Tableau de bord participant - seconde passe visuelle, en impersonation (2026-10-11)

Première passe faite sur du HTML rendu hors navigateur ; celle-ci sur le site,
connecté **en tant que** participant depuis `Admin > Participants > Sa page >
Se connecter en tant que`. Trois défauts que le rendu hors session n'avait pas
montrés, plus un signalé par la relecture.

- **Le dégradé de la carte haute n'était pas celui du site.** J'avais ajouté
  `.tdb-next--calme` (`#1f2a52` -> bleu foncé) pour « calmer » l'après-colloque.
  Or `--ukc-hero` est le dégradé **canonique** et son propre commentaire dit
  pourquoi : une seule source, pour écarter les teintes quasi noires des anciens
  dégradés. J'avais donc rouvert le défaut que le jeton avait fermé, en ignorant
  un commentaire qui l'expliquait. Supprimé ; un cas relit `ukc.css` et refuse
  tout `linear-gradient` en dur sous `.tdb-`.
- **La pastille « Mon badge » annonçait « PDF » et ouvrait une page.** Le badge
  n'est pas un fichier : il est dessiné sur « Mes documents ». Elle dit « Voir »
  et vise l'ancre `#mon-badge` (ajoutée sur cette page), au lieu du haut de
  l'écran. L'indicateur disait « Prêts à télécharger » pour trois pièces dont une
  ne se télécharge pas : « Prêts à retirer », le mot de la carte haute.
- **L'en-tête du volet de droite se tassait.** Dans 270px, « LE COLLOQUE » et la
  pastille « Appel à communications » se partageaient la largeur et passaient
  **chacun sur deux lignes**. `flex-wrap` + `white-space: nowrap` : la pastille
  descend d'une ligne s'il n'y a pas la place. Mesuré : 2 lignes chacun avant,
  1 ligne chacun après, même largeur.
- ⚠️ **Deux fausses alertes, écartées par la mesure.** Le menu latéral compact
  paraissait écrire ses libellés par-dessus ses icônes : c'était une image prise
  **pendant la transition de 260 ms**, la barre s'ouvre correctement après une
  seconde d'attente. Et l'en-tête semblait rogné à gauche : le DOM dit que non,
  c'est le volet qui rend à l'échelle (cf. la passe précédente).
- **Comment refaire la passe** : l'impersonation est le chemin, pas un rendu
  hors session. Penser à **quitter l'impersonation** à la fin, et à remettre le
  colloque courant sur celui qu'on a trouvé en arrivant.

## Dégradé de héros - revue de tous les écrans (2026-10-11)

Après la correction du tableau de bord, revue de **tous** les dégradés de
`resources/css` et des vues. `--ukc-hero` est le jeton canonique
(`#1a3080 -> #223ea4 58% -> #2f52c8`) ; son commentaire dit déjà pourquoi. Trois
écarts, aucun tenu par quoi que ce soit.

| Écran | Avant | Verdict |
| --- | --- | --- |
| Accueil `.hero`, pages internes `.page-hero`, direct `.live-hero` | `var(--ukc-hero)` | conforme |
| Fiche colloque `.detail-hero` | `var(--ukc-hero-navy)` + voile sur photo | conforme (aplat voulu) |
| **Prise en main (participant) `.onb-hero`** | `115deg, #172b78, #2447a8 60%, #096b75` | **corrigé** |
| **Voile d'analyse `.ukc-ai-veil`** | `var(--ukc-hero, <ancien dégradé #14205a>)` | **corrigé** |
| **Accueil, repli de bannière** | `120deg, #0f1a33, #1a3080 55%, #0a7a2f` | **corrigé** |
| Bannière admin `.admin-dash .edition-banner` | `135deg, #223ea4, #1a3080 55%, #009933 160%` | **exception voulue, inchangée** |

- ⚠️ **Le pire était le plus visible.** `#0f1a33` est la teinte que le
  commentaire du jeton nomme **explicitement** comme à écarter, et elle servait
  encore de repli aux bannières d'événement **sur l'accueil**. Remplacée par le
  dégradé de remplacement d'image qui existait déjà deux fois dans `public.css`,
  plutôt que par un troisième.
- ⚠️ **« Prise en main » finissait sur un #096b75 PÉTROLE**, ni bleu ni vert de
  la charte - sans doute un bleu poussé vers le vert en un seul saut. C'est la
  page que le tableau de bord met en lien : deux identités côte à côte. Mesuré
  après correction : identique au jeton.
- ⚠️ **Un repli jamais atteint reste un modèle.** `.ukc-ai-veil` écrivait
  `var(--ukc-hero, <ancien dégradé>)`. La valeur ne servait pas, mais elle
  restait à portée de copier-coller.
- ⚠️ **La bannière admin N'EST PAS un écart : c'est une intention.** Je l'ai
  « alignée » sur le jeton, puis remise. C'est la **jonction entre la charte de
  l'université et le bundle Tailwise** : elle part du bleu de la charte et va
  chercher son vert, là où les héros restent dans les bleus. Les deux signes que
  j'avais lus comme des défauts en étaient les moyens - les valeurs écrites à la
  main parce qu'elle ne suit pas le jeton, et l'arrêt **à 160%** parce qu'au-delà
  de 100% le vert n'est jamais atteint en pleine valeur : il ne donne qu'un
  virage en bout de bande, pas un aplat. Le commentaire est désormais dans
  `admin.css`, et la règle ne la réclame plus.
- **Ce que ça change pour la règle** : elle porte sur les **héros**, pas sur tout
  bandeau coloré. J'avais ajouté une liste nommée pour y faire entrer la
  bannière ; elle est retirée.
- **Fausse piste écartée** : `.gesco-chat .chat-hero .av` est une pastille de
  64px dans la fenêtre d'assistance, pas une bannière. La règle regarde donc le
  **dernier maillon** du sélecteur.
- ⚠️ **Le garde-fou lisait aussi les commentaires.** Il prend pour sélecteur
  tout ce qui précède une accolade : un commentaire citant `--ukc-hero` juste
  avant une règle la faisait passer pour un héros. Découvert en documentant
  l'exception ci-dessus - le commentaire a déclenché la règle qu'il expliquait.
  Les feuilles sont maintenant lues **commentaires retirés**.

## Tableau de bord participant - troisième passe : l'anglais (2026-10-11)

Passe refaite en impersonation, après les corrections précédentes. Le français
était bon partout : dégradé canonique, axes en pastilles, pastille de badge
honnête, en-tête du volet sur une ligne, pas de débordement à 375px ni à 1366px,
contrastes de 4,74:1 (filet or) à 11,81:1. Une seule chose, mais grosse.

- ⚠️ **L'anglais était à moitié mort.** La refonte a remplacé une page dont les
  chaînes étaient traduites par une page qui en portait **145 de neuves, aucune
  traduite**. Sous `EN`, l'écran mélangeait « Dashboard », « My documents » et
  « View all » avec « CE QU'IL VOUS RESTE À RETIRER », « Prêts à retirer » et
  « MES COMMUNICATIONS ». Une page à moitié traduite se lit **plus mal** qu'une
  page qui ne l'est pas du tout. Invisible en français : c'est pour ça que trois
  passes ne l'avaient pas vue - je regardais toujours la même langue.
- **Ce qu'il a fallu** : 37 chaînes de `ParticipantDashboard` n'étaient même pas
  enveloppées dans `__()` (libellés d'indicateurs, pastilles de document, étapes
  du parcours) ; elles ne pouvaient donc pas être traduites. Puis 114 clés
  ajoutées à `lang/en.json`, au glossaire maison (colloque -> conference,
  communication -> paper, atelier -> workshop, axe -> track, attestation ->
  certificate, passage -> presentation).
- ⚠️ **`lang/en.json` n'est PAS exactement trié** : il est globalement
  alphabétique, casse ignorée, mais quelques clés ont dérivé. Un `uksort` global
  déplacerait des lignes que personne n'a demandé de toucher. Les clés neuves
  sont donc **insérées à leur place** sans retrier : le diff est de 114 ajouts
  et **zéro suppression**. (Vérifié : un retri complet donnait 162/48.)
- ⚠️ **Ce qui reste en français, et pourquoi je n'y ai pas touché** :
  - `EventPhase::label()` (« Événement passé ») est partagé avec le site public
    **et les documents générés** : traduire pourrait sortir de l'anglais dans un
    PDF français.
  - `User::missingProfileFields()` rend des libellés français **qu'on compare** :
    `ParticipantDashboard` filtre sur la chaîne `'E-mail vérifié'`. Les traduire
    sans séparer d'abord la clé du libellé casserait ce filtre en silence sous
    une session anglaise. C'est un refactor du modèle, pas une traduction.
  - La pastille d'état d'une soumission et le bandeau d'impersonation, tous deux
    partagés.
- **Observation, pas défaut** : dans l'état « après », l'écran annonce « 3
  documents » à trois endroits (carte haute, indicateur, carte de liste). La
  bande d'indicateurs est un récapitulatif et la carte le détail - c'est la
  convention d'un tableau de bord. À trancher si vous préférez un quatrième
  indicateur différent après le colloque.

## Premier déploiement - ce que MySQL a refusé (2026-10-11)

La première `migrate --force` sur o2switch s'est arrêtée à
`2026_09_11_000300_add_user_id_to_session_votes`. Trois défauts, tous
invisibles sous SQLite, dont **deux que le serveur n'avait pas encore
rencontrés**.

- ⚠️ **Erreur 1553, « Cannot drop index: needed in a foreign key
  constraint ».** L'unicité `(question_id, account_id)` soutenait la clé
  étrangère sur `question_id` (ses colonnes en forment le préfixe), et la
  migration la supprimait avant de poser la nouvelle. Corrigé en inversant
  l'ordre : le nouvel index `(question_id, user_id)` commence par la même
  colonne, il prend le relais, l'ancien devient libre. Aucune clé étrangère à
  toucher.
- ⚠️ **Le même défaut attendait deux migrations plus loin**, dans
  `2026_10_03_000300` sur `meeting_message_reactions`. Trouvé en auditant tous
  les `up()` plutôt qu'en attendant le prochain échec.
- ⚠️ **Un nom d'index de 65 caractères**, soit un de trop pour MySQL, dans
  `2026_10_03_000200`. Trouvé en mesurant les noms réels dans la base de dev, et
  non en relisant le code : c'est Laravel qui déduit le nom, pas l'auteur.
  `SchemaPortableMySqlTest` les mesure désormais à chaque exécution.
- **Les migrations sont maintenant rejouables** (`Schema::hasIndex`) : l'échec
  s'était produit à mi-chemin, la colonne `user_id` était déjà posée.
- **Méthode** : pas de MySQL en local pour reproduire. L'audit a porté sur les
  `up()` seulement (un `down()` ne tourne pas à l'installation), ce qui a ramené
  cinq migrations à examiner sur quarante, dont deux déjà passées sur le
  serveur. Les noms d'index ont été mesurés sur la base de dev réelle.

## Colloque Eau au Togo - équipe d'organisation (2026-10-11)

Le gestionnaire et le co-gestionnaire du premier colloque sur l'eau sont
désormais **dans le seeder d'import** (`ColloqueEauTogoSeeder::equipe()`), et
non posés à la main : l'import est la source de ce colloque, l'équipe en fait
partie.

- **Seyf-Laye ALFA-SIKA MANDE** - gestionnaire (`gerant`)
- **Eralakaza OURO BITASSE** - co-gestionnaire (`co_gerant`)

- ⚠️ **Ce qui ouvre l'accès, c'est la ligne dans `event_user`, pas le rôle
  spatie.** `User::capabilitiesOnEdition()` lit le pivot et rien d'autre : un
  rôle `gerant` posé seul ne donnerait accès à rien. Le rôle global est posé en
  plus, pour la page Utilisateurs et la matrice.
- ⚠️ **Et c'est aussi ce qui LIMITE l'accès.** Vérifié par un cas dédié : ni
  l'un ni l'autre n'atteint le VIIIe Colloque. `access_level` vaut
  `colloque_admin` (« crée et gère des colloques »), ce qui n'ouvre aucun
  colloque où la personne n'est pas affectée.
- ⚠️ **Aucun mot de passe dans le dépôt** : les deux comptes naissent avec
  `must_reset_password`, comme les 44 comptes auteurs de cet import. Ils
  s'ouvrent par réinitialisation.
- Idempotent : compte réutilisé s'il existe déjà (la personne peut figurer
  parmi les auteurs - « Seyf-Laye ALFA-SIKA MANDE » apparaît déjà comme
  co-auteur dans un résumé), fonction non dupliquée. Le pivot porte une unicité
  `(event_id, user_id, role)` : un second passage non gardé ferait échouer tout
  l'import.
- Tests : `EauTogoEquipeTest` (4 cas, dont la portée et l'absence de mot de
  passe), vérifiés en retirant l'appel à `equipe()`.

## 2026-10-11 - Page publique d'un événement : barème, rangées orphelines, bannière vide

Point de départ : « la section des frais de participation n'est pas bien faite »,
capture à l'appui. Mesuré avant de corriger, sur le colloque de l'eau, dans une
fenêtre de 1280px.

**Ce que la grille de cartes faisait vraiment.** 4 cartes puis une rangée
orpheline de 2 ; des cartes de 388px dont 194px de vide dans la plus courte
(la grille égalise les hauteurs de rangée, les catégories couvrent de 1 à 6
grades) ; six montants à six abscisses différentes. Et, dans le pied de
section, `text-align: center` avec des enfants `inline-flex` ne les empilait
pas : la phrase « tous les tarifs comprennent… » et le bouton d'inscription se
posaient sur la MÊME ligne (phrase à x=168, bouton à x=877). L'appel à l'action
était devenu la queue d'une phrase grise.

**Ce que j'ai fait.** Le barème est passé en liste : une ligne par catégorie,
intitulé et grades couverts à gauche, montant à droite en `tabular-nums`, le
tout plafonné à 960px (la ligne de contenu la plus longue en fait 740, plus les
marges, l'écart et le montant : 953). Plus de rangée orpheline, plus de vide,
et les montants dans une colonne unique - ce qu'on vient y chercher. Le pied de
section est passé en colonne.

**Deux voisins du même défaut, trouvés en balayant la page.** Les ateliers
disciplinaires avaient la même grille `auto-fit` et le même 4 + 2 : flux souple
centré. Et la bannière officielle affichait, à défaut de visuel, un bloc coloré
de 420px intitulé « BANNIÈRE OFFICIELLE » répétant le titre et les dates du
bandeau - un emplacement vide montré au public, avec son étiquette
d'emplacement. Aucun des deux colloques n'ayant de visuel, c'est ce que voyait
tout le monde. La section ne s'affiche plus sans image.

**Et l'anglais.** Audit des `__()` de la vue : douze chaînes sans traduction,
dont six dans le barème que je venais de refaire. Ajoutées en place dans
`lang/en.json` (12 insertions, 0 déplacement - un tri global avait déjà prouvé
qu'il rend la revue illisible). Un cas Pest le gèle désormais.

**Gardes.** `tests/Feature/PageColloquePubliqueTest.php`, 5 cas. Six sabotages
vérifiés un par un : barème remis en grille `auto-fit`, `tabular-nums` retiré,
ateliers remis en `auto-fit`, substitut de bannière réintroduit, clé anglaise
supprimée, barème remis en cartes. Chacun fait tomber exactement le cas prévu,
et la restauration est vérifiée bit pour bit. ⚠️ Le cas CSS retire les
commentaires avant de lire : mes propres commentaires expliquent pourquoi
`auto-fit` est abandonné, ils citent donc le mot et feraient tomber la règle -
c'est exactement ce qui était arrivé sur `--ukc-hero`.

**Laissé de côté, volontairement.** La carte « Contacts » des informations
pratiques affiche un texte libre où l'e-mail et les téléphones ne sont pas
cliquables ; les rendre actifs demande d'analyser du texte saisi, c'est une
fonctionnalité, pas une correction de mise en page. Les sections `#chiffres`,
`#intervenants`, `#comites`, `#partenaires`, `#faq` et `#events` n'ont de
données dans aucun des deux colloques de développement : elles n'ont donc pas
pu être revues à l'écran.

1140 tests / 4022 assertions, Pint et PHPStan niveau 5 sans erreur.

**Suite, même jour.** La phrase « tous les tarifs comprennent l'accès aux
sessions, les communications & ateliers et l'attestation de participation » a
été retirée du pied du barème, à la demande. Elle était écrite en dur dans la
vue, donc affirmée à l'identique pour TOUS les événements, sans qu'aucun
réglage ne la soutienne : une promesse que le logiciel ne tient pas. Ce qui
vaut pour un événement donné se dit dans la note de tarifs
(`settings.fees_note`), affichée sous le titre de la section, où l'organisation
l'écrit elle-même. Règles CSS `.incl` et clé anglaise retirées dans la foulée :
elles n'avaient plus d'objet.

## 2026-10-11 - Bandeaux du site réglables, et la page co-auteur élargie

**Évolution du repli (2026-10-12).** Le réglage `hero_event_default_image`,
affiché comme « Bandeau par défaut des pages », couvre aussi les héros des
archives, des archives d'événement, du direct et de la page co-auteur. La clé
reste inchangée pour conserver les images déjà téléversées. Un visuel propre
à l'événement prime toujours, puis vient le repli commun ; sans fichier, le
dégradé de la charte reste visible.

**Deux demandes.** Élargir `.coauthor-wrap` de 720 à 1120px (fait, mesuré à
1120 dans une fenêtre de 1350), et pouvoir renseigner depuis `admin/reglages`
le bandeau de la page d'accueil et le bandeau par défaut des événements.

**Ce que j'ai construit.** Un onglet **Bannières** (deuxième, après Général) et
`App\Support\SiteBanners`, calqué sur `Branding` : clé sans point parce qu'elle
sert aussi de nom de champ, remplacement du fichier précédent, `null` quand le
fichier a disparu. L'accueil pose `.hero--image` et une variable CSS ; les
événements reçoivent le repli par `$settings['hero_image'] ?? eventDefault()` -
dans cet ordre, car un bandeau de site qui écraserait l'affiche d'un colloque
serait pire que pas de bandeau.

**Un effet de bord que j'ai corrigé.** Les deux voiles du bandeau d'événement
étaient écrits **en ligne** dans la vue, en #0f1a33 - la teinte quasi noire que
la charte écarte depuis le travail sur `--ukc-hero`. Écrits en ligne, ils
échappaient à `DegradeHerosTest`, qui relit les feuilles de style. Tant que de
rares événements téléversaient un visuel, cela se voyait peu ; avec la bannière
par défaut, c'est le voile de **tous**. Passés sur `--ukc-hero-navy`, celui que
`ukc.css` désigne pour cet usage.

**Et une leçon de test, payée sur place.** Ma garde contre ce retour s'écrivait
`expect($src)->not->toContain('rgba(15,26,51', "explication")`. Le second
argument de `toContain()` n'est **pas un message, c'est une seconde aiguille** :
nié, cela veut dire « ne contient pas (A et B) », ce qui passe dès que
l'explication manque - par construction. Le cas ne mordait **jamais**. Seul le
sabotage l'a montré, exactement comme l'`assertSee($long)` qui passait sur un
texte tronqué. Puis sa correction s'est révélée **trop large** (elle dénonçait
sept ombres et voiles de modale légitimes) : la règle vise les **dégradés**, pas
la teinte, que la charte autorise en aplat. Une garde qui crie sur du code
correct finit par être désactivée.

**Gardes.** `tests/Feature/SiteBannersTest.php`, 10 cas, 4 sabotages vérifiés
(voile quasi-noir réintroduit, repli d'événement retiré, bandeau d'accueil
ignoré, enregistrement débranché) - chacun fait tomber ce qu'il doit, et la
restauration est vérifiée bit pour bit.

1150 tests / 4075 assertions, Pint et PHPStan niveau 5 sans erreur.

## 2026-10-11 - « [email protected] », une page 500 et le ménage de l'accueil

**La question posée.** « Pourquoi la recherche remplace les e-mails par
[email protected] ? » Ce n'est pas GEVES : `Server: cloudflare` sur
geves.univkara.tg, et l'option **Email Address Obfuscation** y est active - le
HTML de production contient deux `__cf_email__`, zéro adresse en clair, et le
script `email-decode.min.js`. Ce script ne tourne **qu'au chargement** : nos
régions remplacées en AJAX (`data-filter`) arrivent masquées et le restent.
D'où le symptôme exact : adresses correctes au chargement, substitut dès la
première frappe. Filet côté client (`ukcDecodeCfEmails`, le même XOR que
Cloudflare), appelé par les deux mécanismes de remplacement. Vérifié en
simulant la réécriture sur la réponse AJAX : 97 adresses masquées, 0 restante
après l'échange.

**Une page 500 trouvée en voulant vérifier.** `/admin/utilisateurs` tombait en
local : `htmlspecialchars(): must be of type string, array given`, depuis
`__('Pagination')`. Laravel lit le mot avant le premier point comme un
**groupe** de traduction, et le framework livre `lang/en/pagination.php` ; sur
un disque **insensible à la casse** (Windows, macOS), `Pagination.php` tombe
dessus et `__()` rend le **tableau**. Donc : 500 en anglais sur tout poste de
développement, et rien en ligne, sous Linux. `__('Validation')`, l'autre clé
d'un seul mot, est sauve parce qu'elle figure dans `lang/en.json` - la
recherche JSON court-circuite le groupe.

**Le ménage demandé sur l'accueil.** Les sections de données étaient déjà
conditionnées ; ce qui ne l'était pas, c'est ce qui les **annonce**. Le menu
offrait « En cours » et « À venir » vers des ancres absentes - clic sans effet
sur geves.univkara.tg. Gardé dans l'en-tête, **et dans le pied de page**, que
j'avais oublié et que le cas Pest a trouvé, **et** sur le bouton de Déroulement.
S'y ajoutent le bandeau d'indicateurs (masqué quand les quatre compteurs sont à
zéro : « 0 participant » sur un site neuf donne une plateforme à l'abandon) et
le « +228 XX XX XX XX » de la section Contact, un gabarit de saisie montré au
public.

**Et le carré « DRI »** de la carte À propos est devenu le logo compact
(`Branding::markWhite()`, carte sombre). Trois lettres dessinées dans une vue
ne suivent pas le logo téléversé dans Réglages - exactement ce qui faisait
mentir la configuration dans la salle de visio.

**Deux gardes qui ne mordaient pas, trouvées par sabotage.** Celle du logo
cherchait l'adresse du monogramme **dans toute la page**, où elle figure aussi
dans le `<link rel="apple-touch-icon">` : `src` vidé, rien ne tombait. Elle
regarde maintenant dans la pastille. Et plus tôt dans la journée, le même
travers avec `toContain('motif', 'explication')`. Un cas qu'on n'a pas vu
échouer n'est pas un cas.

1158 tests / 4110 assertions, Pint et PHPStan niveau 5 sans erreur.

**Suite, meme jour.** La bulle de l'assistante Gesco est passee de **64 a
84px** (72px de mascotte), et **68px** sous 560px. Le diametre est devenu le
jeton `--ukc-cb-fab` parce que quatre regles s'y accrochent, dont le decalage
et la hauteur du panneau : agrandir la bulle sans les suivre la faisait
recouvrir par son propre panneau. Mesure apres coup : 84x84, panneau a 12px
au-dessus, aucun chevauchement, et rien qui deborde a 375px.

## 2026-10-11 - Quatre questions sur l'accueil, et deux chiffres qui mentaient

**« 44 établissements représentés, c'est vérifié ? »** Non. Mesuré avant de
répondre : sur le colloque de l'eau, 44 inscrits et 44 chaînes distinctes, même
après normalisation - parce que le compteur comptait des **chaînes libres**, et
que chacun saisit son laboratoire, pas son établissement. Deux laboratoires
d'une même université comptaient pour deux, et le total suivait mécaniquement
le nombre d'inscrits : d'où « 44 participants » à côté de « 44 établissements »
sur la capture. Compter de vrais établissements demanderait de rapprocher ces
textes d'un référentiel (ROR est déjà dans le projet) : c'est une
fonctionnalité, pas un compteur.

**« Colloques & séries est-il pertinent ? »** Non plus : il comptait les
séries, que le visiteur ne distingue pas des événements, et le mot contredit le
modèle typé. Les quatre indicateurs racontent maintenant le parcours de la
plateforme - événements, communications déposées, participants, attestations
délivrées - une carte à zéro ne s'affiche pas, et la grille est passée de
quatre colonnes fixes à des cartes plafonnées et centrées (sinon une carte
masquée creuse un trou). Trois accords faux corrigés au passage.

**Le formulaire de contact débordait sur téléphone.** Deux causes, et il
fallait les deux : `min-width: auto` d'un `<input>` vaut sa taille intrinsèque,
donc les pistes `1fr 1fr` restaient à 188px chacune - 388px de rangée dans une
carte de 293 ; et la grille était posée en **style en ligne**, qui l'emporte
sur toute media query. Mesuré en forçant la carte à 343px : la grille ne
bougeait pas. Après : une colonne de 293px, aucun débordement de page.

**L'assistante s'appelle Peace.** Le nom était recopié en dur à **dix endroits**
- dont le message « L'assistance Gesco est momentanément indisponible » et
l'étiquette de ses propres tours dans l'historique envoyé au modèle. Le
changer dans Réglages n'en changeait aucun. Source unique
`AiAgents::publicName()` ; la clé `gesco` reste, c'est un identifiant.

⚠️ **Ma garde ne lisait que `resources/views`** : ce sont deux cas existants
qui sont tombés sur « Gesco » et m'ont montré le message d'`AskGesco`. Elle
balaie maintenant `app/` aussi, commentaires de fin de ligne compris. Troisième
garde trop étroite de la journée.

1167 tests / 4145 assertions, Pint et PHPStan niveau 5 sans erreur.

**Suite, meme jour.** La barre publique perd « Verifier une attestation »,
recueillie dans les liens utiles du pied de page : cette page existe pour un
tiers qui controle une attestation recue, la retirer de toute navigation la
rendrait introuvable. Et « Passes » vise desormais l'ancre `#passes` de
l'accueil au lieu de la page d'archives - donc elle prend la meme garde que
ses deux voisines (`navHasPast`), sans quoi on reintroduisait exactement le
lien mort corrige le matin meme. La page d'archives reste atteignable depuis
la section (« Toutes les archives ») et depuis le pied de page.

## 2026-10-11 - « Evenement », pas « colloque », sur tout le site public

La plateforme gere trois types d'evenements ; le site public n'en nommait
qu'un. « Colloques a venir », « Colloques passes », « Infos du colloque »,
« Tarifs officiels du colloque », « votre assistante colloque »... une
trentaine de libelles, repris en « evenement ».

**Ce qui NE bouge pas**, et c'est la moitie du travail : la route
`/colloque/{slug}` (des liens sont deja partis par e-mail), les classes CSS,
le partial de pied de page, l'adresse colloque@univkara.tg - des noms de
choses - et surtout « colloque » quand il designe le TYPE : l'enumeration
« seminaires, colloques et journees scientifiques » et le filtre des
archives. Une regle qui interdirait le mot partout effacerait aussi le type.

**58 cles anglaises** ajoutees : renommer un libelle cree une cle neuve, et
sans traduction la version anglaise redevient un panachage. L'audit a sorti
au passage quatre accords faux (« evenements cloturees », « Evenements
archivees », « a venir ou passees », « la date du colloque »).

**Quatre cas existants sont tombes** sur les anciens libelles - c'est le
signe que la garde porte : ils assertaient « Colloques a venir », « Galerie -
dernier colloque », « Colloques passes », « Aucun colloque en cours ».

Garde : `VocabulairePublicTest`, avec liste d'exception explicite. Quatre
sabotages verifies.

1172 tests / 4165 assertions, Pint et PHPStan niveau 5 sans erreur.

## 2026-10-12 - Le point médian quitte le code

Le point médian U+00B7 servait de séparateur dans **135 fichiers, 519 fois** :
« Chrome - Windows », « PDF - Déposé le », « d/m/Y - H:i » s'écrivaient avec
lui. C'est un caractère qu'on ne tape pas au
clavier - il arrive par copier-coller et repart en mojibake dès qu'un maillon
perd l'UTF-8. Il devient `-`. La règle porte sur **le code, pas sur les
données** : ce qu'une personne saisit lui appartient.

**507 occurrences sont tombées mécaniquement. Les 12 autres ne sont pas des
séparateurs**, et un `-` y aurait dit autre chose :

- **Écriture inclusive** (le point médian portant le genre dans « Invité_e »,
  « inscrit_e », « non rattaché_s ») :
  forme parenthésée du projet - « Invité(e) ». La *même ligne* de
  `admin/submissions/index` portait déjà « Co-auteur(s) non rattaché(s) » dans
  son `title` : la convention existait, elle n'était pas suivie.
- **Multiplication** : « s'éloigne du centre en 0,55 fois la taille », où le
  point médian valait le signe multiplier (poignée visio).
  « 0,55-taille » se lit comme une soustraction. Devient « 0,55 x taille ».
- **Le caractère cité comme sujet du propos** (4 commentaires et entrées de
  journal, dont deux qui racontent un changement ayant justement *retiré* le
  point médian) : nommé en toutes lettres. Y écrire `-` aurait fait dire au
  journal l'inverse de ce qui s'est passé.

**Ce que la substitution a cassé, et que seul un rendu sur données réelles
montre.** La page publique affichait « Professeur - Maître de conférences -
Maître-assistant - Enseignant / Chercheur - Enseignant - Chercheur ». Or
« Enseignant » et « Chercheur » sont **deux grades distincts** : le tiret les
soude en un seul. Mesuré en base : 30 intitulés de séance sur 30 portent déjà
un tiret (« Session - Atelier 1 - Sciences… »), 6 axes sur 12, 1 grade sur 14.
Ces trois listes passent à la **virgule**. Les paires de champs différents
(`rôle - affiliation`) gardent le tiret : il y marque une apposition, pas une
énumération.

⚠️ **Une valeur en base portait encore le caractère**, invisible au premier
balayage : `json_encode` échappe le non-ASCII, elle était donc stockée sous sa
séquence d'échappement JSON, et non sous sa forme UTF-8, dans
`events.settings`. Écrite par `ColloqueEauTogoSeeder`, donc par nous :
corrigée en dev après vérification qu'elle n'avait pas été éditée à la main.
**Sur la prod, à vérifier si ce seeder y est passé.**

Garde : `tests/Feature/PointMedianTest.php` - sources (762 fichiers), HTML
rendu et `lang/*.json`. ⚠️ Le cas « rendu » a d'abord **passé à tort** : il
rendait l'accueil sur une base vide, où les sections ne s'affichent pas. Un
sabotage l'a montré. Il crée désormais trois événements et vérifie des points
d'appui avant de conclure. Trois sabotages vérifiés, restauration bit pour bit.

1175 tests / 4174 assertions, Pint et PHPStan niveau 5 sans erreur.

## 2026-10-12 - Le tiret demi-cadratin, et deux tolérances bornées

Même règle, même remède : U+2013 devient `-`. Il marquait les plages
(« 08:30 - 11:30 », « 11 - 13 novembre », pagination « 1 - 20 sur 48 »).
**66 occurrences : 45 remplacées, 21 gardées volontairement.**

**Ce qui reste, et pourquoi.** Deux endroits ne *nomment* pas, ils
**transcrivent un texte qui existe ailleurs et qu'il faut retrouver tel quel** :

- **20 dans le heredoc `RESPONSES_JSON`** du seeder Eau-Togo : résumés,
  mots-clés et `coauthors_raw` importés du formulaire, donc le texte que **les
  auteurs ont tapé** - « Abomey - Calavi » y est écrit au demi-cadratin. Le
  réécrire falsifierait des soumissions.
- **1 dans la CLÉ** de la table de correspondance de
  `fix_document_template_typography`. Elle apparie le texte déjà en base pour le
  corriger, et sa **valeur porte déjà un tiret simple** : la migration convertit
  précisément ce demi-cadratin. Changer la clé aurait désactivé en silence la
  correction de cette phrase - sans rien casser de visible.

⚠️ **Les tolérances sont BORNÉES, pas des blancs-seings de fichier.** Le reste
du seeder reste tenu par la règle (ses deux `poster_source` ont bien été
corrigés), et un sabotage le prouve : un demi-cadratin posé hors du heredoc est
attrapé. Un second cas vérifie que chaque tolérance **sert encore** - fichier
disparu, heredoc renommé ou caractère parti, elle doit être retirée.

`PointMedianTest` devient `TypographieTest` : une table `caracteresProscrits()`
au lieu d'un caractère en dur, les quatre cas portant sur les deux. Quatre
sabotages vérifiés (code, hors-heredoc, heredoc renommé, HTML rendu),
restauration bit pour bit.

⚠️ **Le tiret cadratin U+2014 reste, lui, énoncé mais non gardé : 70
occurrences.** Surtout des titres de `PASSATION.md` et `docs/`, mais aussi
`.env.example`, `eslint.config.js`, `phpstan.neon` et quelques vues. Une ligne
dans `caracteresProscrits()` suffirait, avec une tolérance pour
`AutoAssignReviewersTest` dont le jeu d'essai **doit** en porter un.

1176 tests / 4175 assertions, Pint et PHPStan niveau 5 sans erreur.

## 2026-10-12 - Le tiret cadratin, et la tolérance bornée par caractère

La règle existait dans `CLAUDE.md` « depuis toujours » et comptait **70
violations** : elle n'avait jamais eu de garde. 65 remplacées, 4 reformulées,
1 gardée.

⚠️ **Le cadratin ne coûtait pas que de l'allure.** L'expertise d'un relecteur
stocke une **copie** du libellé de l'axe. Un cadratin recopié depuis Word
faisait échouer l'égalité stricte **en silence** : plus aucun appariement par
axe, et l'attribution automatique proposait *systématiquement* « hors axe ».
C'est ce bug qui avait fait naître `ReviewerExpertise::axisKey()`, lequel
normalise toute la famille des tirets typographiques. La règle de CLAUDE.md
avait donc déjà coûté cher une fois, faute d'être tenue.

**Les 4 reformulations** sont les endroits qui **citent** le caractère pour
décrire ce bug (le commentaire de `ReviewerExpertise`, deux passages de
`CLAUDE.md`, un de `PASSATION.md`) : y écrire `-` aurait donné « les relecteurs
avaient *Atelier 1 - …* et l'édition *Atelier 1 - …* », c'est-à-dire plus de
différence, donc plus de bug. Le caractère y est nommé en toutes lettres.

**La 1 gardée** est le jeu d'essai de `AutoAssignReviewersTest` : il *doit*
porter un cadratin, sinon `covers()` compare deux chaînes identiques et le cas
n'atteste plus rien.

⚠️ **Les tolérances sont désormais bornées PAR CARACTÈRE autant que par
région.** `AutoAssignReviewersTest` n'est toléré que pour le cadratin : un
sabotage y pose un point médian, et la garde mord quand même. Trois tolérances
en tout, aucune de fichier entier pour tous les caractères.

Trois sabotages vérifiés (cadratin dans notre code, point médian dans le
fichier toléré, cadratin retiré du jeu d'essai - la tolérance devient caduque
et le cas le dit), restauration bit pour bit.

1176 tests / 4175 assertions, 80 cas Vitest, Pint et PHPStan niveau 5 sans
erreur. Plus aucun des trois caractères dans le code.

## 2026-10-12 - Marc répond sur les comptes, à qui peut déjà les voir

Nouveau `App\Support\UserDirectory`, injecté dans `AskAdmin` : le personnel avec
niveaux d'accès, rôles, état et dernière connexion ; la répartition des comptes ;
les derniers créés ; les non vérifiés ou désactivés. Marc répond désormais à
« qui est gérant ? », « combien de comptes ne sont pas vérifiés ? », « quand
untel s'est-il connecté ? ».

⚠️ **L'écart trouvé avant d'écrire la moindre ligne.** La demande disait « si
c'est un admin ou un super-admin ». Or `AskAdmin` est joignable par **tout** le
staff ayant « Tableau de bord » sur l'événement - relecteur, secrétariat,
comptabilité - tandis que la page `admin/utilisateurs` est gardée par la
permission `users_roles`, que **seul le rôle Administrateur** porte. Dans la base
de dev, les deux administrateurs **de colloque** (gérant, co-gérant) sont donc en
**403 sur cette page**. Les leur servir par l'assistant aurait fait de Marc la
porte la plus large de la plateforme. Arbitrage retenu après question :
**`users_roles`, la porte exacte de la page**. Pour ouvrir l'annuaire à
quelqu'un, on coche la permission dans la matrice d'accès - et il voit la page ET
l'annuaire, du même mouvement.

**Trois décisions de conception qui tiennent la règle toute seule :**
- La garde vit **dans** `UserDirectory`, pas chez l'appelant : `AskAdmin`
  reconstruit son prompt à chaque message, et une garde posée au point d'appel
  finit oubliée au premier déplacement de bloc.
- Qui n'y a pas droit n'en entend **même pas parler** : `resume()` rend une
  chaîne vide. Décrire à un modèle une donnée qu'on lui refuse, c'est l'inviter à
  la deviner.
- **Ni plus ni moins que la page** : le contenu est calqué sur les colonnes
  d'`admin/utilisateurs`. Règle simple à tenir et à vérifier.

Borné (60 comptes de personnel, 25 par liste) : les 263 comptes participants d'un
colloque importé n'ont rien à faire dans un prompt - leurs données nominatives
viennent déjà d'`AdminDirectory`, bornées à l'événement.

Garde : `AnnuaireComptesTest` (9 cas). Quatre sabotages vérifiés : permission
retirée, porte élargie au niveau d'accès (le choix écarté), assistante publique
qui s'en approche, plafond supprimé. ⚠️ Le second ne mordait **qu'indirectement**
au premier essai : un cas dédié encode maintenant la décision noir sur blanc.

⚠️ **Deux cas rouges trouvés sur master, venus d'ailleurs.** Les commits amont du
pied de page avaient (1) retiré « Vérifier une attestation » des liens utiles -
or cette page sert un TIERS, un recruteur qui contrôle une attestation reçue, et
elle a déjà quitté la barre du haut : sans le pied de page elle devient
introuvable ; (2) ajouté deux chaînes sans traduction anglaise. `PageAccueilTest`
et `VocabulairePublicTest` les ont attrapés. Lien restauré avec son commentaire,
deux clés ajoutées.

1189 tests / 4217 assertions, Pint et PHPStan niveau 5 sans erreur.

## 2026-10-12 - Le menu participant prend la règle du menu admin

`bb3a9206` avait recatégorisé le menu du back-office en cinq groupes cohérents,
**chacun masqué si aucun de ses liens n'est accessible**, icônes différenciées.
Le menu participant, lui, entassait six entrées sous « Mon dossier » et
annonçait une catégorie « Colloque » qui contenait surtout des liens de
plateforme.

Il porte maintenant : **Tableau de bord** (hors catégorie), **Ma participation**
(Soumissions, Inscription & paiement, Formations, Documents), **Mes fonctions**
(inchangé, déjà bien gardé), **L'événement** (Page de l'événement, Programme),
**La plateforme** (Déroulement, Archives, **Témoignage**, Accueil, Assistante),
**Mon compte** (Mon compte, Prise en main).

⚠️ **« Témoignage » est passé de « Ma participation » à « La plateforme »** (sur
arbitrage) : il alimente la page d'accueil, il ne fait pas partie du dossier de
participation. Son libellé perd le « Mon » - qui reste au titre de la page. Et
c'est ce qui a montré un **trou dans la garde** : les cas n'éprouvaient que les
intitulés, leur ordre et leur non-vacuité, si bien qu'un lien pouvait changer de
catégorie sans qu'aucun ne bronche. Un cas de **composition** épingle désormais
le contenu exact de chaque groupe. ⚠️ Son jeu d'essai active le module formations
et publie une formation : sans cela le menu rendu n'est pas le menu complet, et le
cas croirait la catégorie juste en ne regardant que quatre liens sur cinq.

⚠️ **Le déplacement qui fait marcher la garde.** « Déroulement » était rangé
avec les liens de l'événement. Or sa route est GLOBALE (`public.deroulement`) et
reste utile sans événement suivi : en le laissant là, « L'événement » ne pouvait
pas partager la garde `@if ($colloqueEdition)` de ses deux autres liens sans
laisser « Déroulement » orphelin sous un intitulé masqué. Déplacé dans « La
plateforme », la catégorie « L'événement » disparaît ENTIÈRE quand le compte
n'en suit aucun - la règle du menu admin, appliquée pour de vrai.

⚠️ **Icônes : trois doublons corrigés.** `book` servait à Prise en main,
Formations ET Archives ; `sparkles` à Mon témoignage ET l'assistante. Replié
(`ukc-compact`), l'icône est TOUT ce qui reste d'un lien : deux identiques
rendent deux entrées indiscernables. Formations -> `award`, Mon témoignage ->
`message`, Archives -> `layers`. Un cas compte les formes SVG et interdit les
doublons.

Le menu avait aussi échappé au renommage « colloque » -> « événement » :
`VocabulairePublicTest` ne balaie que le site public, pas les gabarits de
l'espace connecté. Corrigé, avec trois clés anglaises ajoutées.

Garde : `MenuParticipantTest` (7 cas). Cinq sabotages vérifiés : intitulé
sorti de sa garde, icône dupliquée, « colloque » de retour, ordre des catégories
changé, « Témoignage » remis dans « Ma participation ». ⚠️ Le cas du vocabulaire
éprouve le **texte visible** et non le HTML : la route `/colloque/{slug}`,
délibérément conservée, apparaît dans chaque `href`. ⚠️ Et le helper s'appelle `compteDuMenu()` et non `compteParticipant()` :
les fonctions d'un fichier de cas sont GLOBALES, et le nom évident était déjà
pris par `ParticipantDashboardTest` - la redéclaration fait tomber la suite
entière sur une erreur fatale muette.

Vérifié à l'écran en impersonation d'un participant réel, menu déployé.

⚠️ **Reste** : le tableau de bord participant dit encore « Le colloque est
terminé », « LE COLLOQUE », « Page du colloque ». Le renommage s'est arrêté au
site public ; l'espace connecté n'a pas de garde de vocabulaire.

Pint et PHPStan niveau 5 sans erreur, `MenuParticipantTest` et `TypographieTest`
verts.

⚠️ **La suite complète, elle, est rouge de 6 cas qui ne viennent pas d'ici** :
`cb693027` (Turnstile) refuse inscription et contact quand les clés ne sont pas
configurées - voir l'entrée suivante.

## 2026-10-12 - À TRAITER : Turnstile bloque inscription et contact

`cb693027` protège l'inscription et le formulaire de contact par Cloudflare
Turnstile. ⚠️ `Turnstile::validate()` **refuse tout quand les clés ne sont pas
configurées** (« La vérification de sécurité est temporairement indisponible »).

**Conséquence en production** : si `CLOUDFLARE_TURNSTILE_SITE_KEY` /
`CLOUDFLARE_TURNSTILE_SECRET_KEY` ne sont ni dans le `.env` ni dans Réglages sur
geves.univkara.tg, **l'inscription et le contact sont hors service**. Les clés
sont lisibles depuis le tableau de bord Cloudflare du domaine.

**Conséquence sur la suite** : 6 cas rouges (`AccountAuthTest`,
`NotificationTest`, `PublicFinishingTest`, `UserPhoneUniquenessTest` x3).

Deux issues, et c'est un arbitrage, pas un oubli de frappe :
1. **Garder le refus** (fail-closed) et configurer des clés d'essai dans la
   suite, avec `Http::fake()` sur l'appel à `siteverify` ;
2. **Laisser passer** quand Turnstile n'est pas configuré (fail-open), au prix
   d'une protection absente sur toute installation non configurée.

Rien n'a été touché dans ce commit : la décision appartient à l'auteur.

## 2026-10-12 - Le modèle des rôles, écrit une fois pour toutes

La relation entre un compte et ses rôles était dispersée entre quatre mécanismes
sans qu'aucun texte ne dise lequel fait quoi. Elle est désormais énoncée dans
CLAUDE.md et tenue par `ModeleDesRolesTest`.

**Le modèle.** Un compte porte des rôles de deux natures. (1) **Trois niveaux
système** et pas un de plus, sur `users.access_level` : `super_admin`,
`colloque_admin` (il crée les événements et y nomme gestionnaire /
co-gestionnaire) et `user`. (2) **Tout le reste est relatif à un événement** :
l'équipe (`event_user.role` + `StaffRoles`), le programme (modérateur,
rapporteur) et l'évaluation (relecteur). ⚠️ Ces trois derniers **ne sont pas
stockés comme rôles** : ils se déduisent du travail confié.

⚠️ **Le défaut trouvé en écrivant le modèle, et mesuré.** Deux gardes de routes
cohabitent : `edition.cap:` (16 groupes), qui passe par `canOnEdition()` donc par
`isColloqueSuperAdmin()`, et `can:` (3 groupes : Réglages, Utilisateurs & rôles,
Témoignages), qui passe par le Gate spatie. Ce Gate n'interrogeait QUE le rôle
spatie `administrator` et la permission `manage_colloques`. Résultat sondé : un
compte `access_level = super_admin` sans rôle spatie a
`isColloqueSuperAdmin() = true`, ouvre les 16 groupes `edition.cap:` et tombe en
**403 sur les 3 autres**. Le modèle disait « super-administrateur », le portail
disait non.

Le défaut **ne se voyait pas** : le seul super-admin de la base porte aussi le
rôle spatie `administrator`. Il aurait surgi au premier compte promu par le seul
niveau d'accès - c'est-à-dire exactement par l'écran prévu pour ça.
`Gate::before` interroge désormais `isColloqueSuperAdmin()`, source unique.

Garde : `ModeleDesRolesTest` (7 cas), un sabotage vérifié (ancien portail remis,
les trois capacités retombent).

⚠️ **Deux cas tombés au passage, venus d'ailleurs.** `8be7ccf4` (section À
propos) a réintroduit un point médian - corrigé. Et `13fee7de` accorde désormais
le libellé du lien public au TYPE de l'événement (`Event::publicPageLabel()` :
« Page du colloque », « Page du séminaire »…) : `MenuParticipantTest` figeait
« Page de l'événement » et interdisait le mot « colloque » dans tout le menu. Les
deux cas ont été repris pour **laisser vivre l'accord** - le premier demande le
libellé au modèle, le second éprouve un événement de type « Autre », et un
nouveau cas vérifie les trois accords. ⚠️ C'est la règle du vocabulaire
appliquée : « colloque » reste juste quand il nomme le TYPE.

1210 tests / 4301 assertions, Pint et PHPStan niveau 5 sans erreur.

## 2026-10-12 - L'onglet Rôles compte ce qu'il annonce

Quatre corrections sur `admin/utilisateurs`, onglet Rôles - l'écran qui liste les
**fonctions d'équipe** (et non les niveaux système, qui n'y figurent pas).

⚠️ **Le compteur répondait à une autre question.** Il lisait les rôles GLOBAUX
(spatie) alors qu'une fonction se confie par événement : votre base a un
secrétariat et une comptabilité affectés sur le colloque, et l'écran annonçait
« 0 utilisateur(s) » pour les deux. Il affiche désormais **« N personne(s) sur M
événement(s) »** (`user_id` distincts : tenir la même fonction sur deux
événements ne fait pas deux personnes), puis **« N rôle global » seulement s'il y
en a** - ce qui rend visible d'un coup d'œil qu'« Administrateur colloque » est un
rôle de plateforme et non une fonction d'événement.

**Les familles s'appellent « Organisation » et « Appui ».** « Système » entrait en
collision avec les trois niveaux système du modèle, qui n'ont rien à voir avec ce
classement - deux sens du même mot à un écran d'écart. Et **une seule couleur par
famille** : les « Appui » reprenaient la couleur propre de chaque fonction, si
bien que Comptabilité ressortait en vert comme les « Organisation ». La couleur ne
portait rien, elle suggérait le faux.

⚠️ **Le rôle legacy `editor` n'était pas une scorie inoffensive.** Il portait
`manage_colloques`, la super-capacité qui court-circuite `Gate::before` et ouvre
TOUT le back-office - sans figurer dans `StaffRoles`, donc ni dans l'onglet Rôles
ni dans la matrice d'accès. Un rôle à tous les pouvoirs que les écrans de
surveillance ne montraient pas. Retiré du seeder ; migration
`drop_legacy_editor_role` pour les bases existantes, qui ne supprime **que** s'il
n'est attribué à personne.

⚠️ **Un piège Blade attrapé en chemin, deux fois.** `admin/users/index.blade.php`
emploie la forme EN LIGNE de la directive PHP (ligne 73). Y ouvrir un bloc PHP
casse la compilation du fichier entier : Blade apparie la première ouverture à la
première fermeture et avale tout ce qui les sépare. Le calcul est donc passé dans
le contrôleur (`roleUsageLines`). **Et le commentaire qui expliquait ce piège l'a
reproduit** : il nommait les directives en toutes lettres, or Blade les compile
AVANT de retirer les commentaires. La note a cassé le fichier qu'elle décrivait.

Garde : `OngletRolesTest` (6 cas), quatre sabotages vérifiés - compteur global
remis, pastille reprenant la couleur de la fonction, `editor` recréé, rôle global
affiché même à zéro.

⚠️ **Trois cas tombés, venus d'ailleurs** (commits arrivés au rebase précédent) :
`b4529ad6` a renommé « Soumissions » en « Communications » dans l'espace
participant (`LocaleTest` attendait « Submissions », c'est « Papers ») ;
`bd79df10` a **délibérément rendu le menu participant générique** - « Page de
l'événement » quel que soit le type - alors que `13fee7de` venait d'introduire
l'accord au type, qui reste juste pour le SITE PUBLIC ; `8be7ccf4` a remplacé le
monogramme compact de la carte « À propos » par le logo large. Les trois cas ont
été alignés sur ces décisions, pas l'inverse. ⚠️ `bd79df10` annonçait « Corrige
MenuParticipantTest » mais n'a touché que le gabarit.

1220 tests / 4343 assertions, Pint et PHPStan niveau 5 sans erreur.

### 2026-10-14 — Tableau des modèles de badges

Le tableau **Documents → Badges → Modèles** ne reprend plus les quatre lignes du
mockup. Il lit le modèle `badge/default` effectivement rendu, déduit son format
des dimensions du layout et son QR des éléments du layout. « Destinataires »
compte uniquement les participants dont un paiement de **participation** est
réglé. Le badge communicant distinct n'existe pas : un communicant réglé reçoit
le même badge participant. La liste « Badges prêts » et la planche admin utilisent
le même filtre, y compris si une ancienne URL porte `paid=0`. Les variantes
« Communicant » et « Staff » ont été retirées de la palette du designer pour
éviter de changer le rôle imprimé sur le modèle unique.

### 2026-10-14 — Badges de fonction par catégorie

Le modèle participant `badge/default` reste réservé aux inscriptions dont le
paiement de participation est réglé ; il n'existe toujours pas de badge
communicant. `App\Support\BadgeCatalog` ajoute des modèles indépendants pour les
modérateurs, rapporteurs, l'appui à l'organisation, l'accueil et les comités de
supervision, d'organisation et scientifique. Leurs destinataires sont déduits
des séances, des affectations `event_user` et des membres de comité, sans
condition de paiement. Le tableau Modèles affiche le décompte par type ; les
liens de conception et d'aperçu portent `type`, tout comme la planche admin.
« Badges prêts » filtre la catégorie et liste ses destinataires réels. Le
contrôleur du designer conserve désormais le type demandé au lieu de forcer
`default`, afin que chaque mise en page soit sauvegardée séparément.

La page participant « Mes documents » affiche maintenant tous les badges d'un
même compte pour l'événement, avec le titre « Mon badge » ou « Mes badges » selon
leur nombre. `BadgeCatalog::forAccount()` retrouve les fonctions dans les mêmes
sources que les planches administrateur, par correspondance d'adresse e-mail
avec le compte ou sa fiche participant. Chaque aperçu utilise le modèle et la
référence de son type ; le badge participant reste verrouillé jusqu'au paiement,
les badges de fonction sont disponibles indépendamment de celui-ci. La pastille
du tableau de bord suit le même décompte.

### 2026-10-14 — Modalités de dépôt d'une communication

Le champ `type` des soumissions reste **oral/poster**, présenté comme « Type de communication ». Un nouveau champ `participation_mode` (**online/onsite**) représente « Mode de participation » et s'enregistre séparément. Le formulaire public limite ses options selon `Event.settings.format` ; la requête les valide côté serveur. Le formulaire administrateur expose les deux champs, et `SubmissionFormConfig` remplace à la lecture l'ancien libellé « Mode de présentation » des événements déjà configurés. La migration reprend les valeurs Présentiel/Distantiel des métadonnées importées du colloque Eau au Togo ; les autres soumissions historiques restent sans mode tant qu'il n'est pas connu. Le seeder alimente désormais la nouvelle colonne à la création et lors d'une relance pour ses lignes existantes.

L'axe thématique, le type de communication et le mode de participation partagent une ligne sur grand écran dans le formulaire public. Leur grille est un frère de la grille du titre, pas son enfant : elle occupe ainsi toute la largeur disponible. Le comité retrouve le mode dans la liste des soumissions (filtre, aperçu, export CSV) et dans les créneaux du programme (pastille et filtre), afin d'identifier les communicants à distance.

### 2026-10-14 — Mode de participation propre au participant

`participants.participation_mode` est désormais la seule source du mode pour une personne sur un événement. La migration `2026_10_14_000200` reprend le dernier mode connu de ses soumissions (ou la réponse importée), puis retire la colonne des soumissions. Le dépôt suivant préremplit ce choix ; si la personne le modifie, `CreateSubmission` actualise sa fiche participant dans la transaction. Les anciennes et nouvelles communications affichent aussitôt cette même valeur. Les filtres du back-office et du programme lisent la fiche participant. L'import Eau au Togo renseigne la fiche sans écraser un mode déjà personnalisé lors d'une relance.

Le participant disposant déjà d'une fiche retrouve ce réglage dans **Inscription & paiement → Ma participation**, même avant l'ouverture du paiement. Le formulaire de communication propose un lien vers cette carte. Le formulaire envoie son choix à `account.participation.mode.update`, qui vérifie la propriété de la fiche et les modes autorisés par le format de l'événement ; il revient ensuite à la carte de l'événement concerné. Le profil global ne contient plus ce choix propre à chaque événement.

### 2026-10-14 — Présentation de chaque communication distincte de la présence au colloque

La décision précédente d'utiliser le mode du participant pour toutes ses communications est remplacée : `participants.participation_mode` décrit uniquement sa présence au colloque, et `submissions.presentation_mode` décrit la présentation de **chaque** communication. Le formulaire de dépôt nomme maintenant ce second choix « Mode de présentation » ; il n'écrit plus sur la fiche participant. La clé `participation_mode` du paramétrage historique du formulaire est conservée pour relire les réglages déjà enregistrés. La migration `2026_10_14_000300` reprend d'abord `meta.presentation_mode` des résumés importés (choix original), puis le mode actuel du participant quand l'historique individuel n'existe plus. Modifier la carte **Inscription & paiement** ne change pas les communications ; une fiche de communication propose une modification indépendante à son propriétaire.

La liste, la fiche et le CSV administrateur montrent séparément la présentation et la participation au colloque. Le filtre des créneaux du programme suit le mode de présentation de la communication, qui détermine comment l'intervention aura lieu. Lors d'une resoumission, l'archive garde ce mode. L'import Eau au Togo renseigne `presentation_mode` à la création et sur ses anciens dépôts seulement si la colonne est encore vide.

### 2026-10-15 — Designer de badges aligné sur le designer d'attestations

`badge-designer.blade.php`, `badge-designer.js` et `badge-designer.css` reprennent les libellés français, le panneau Calques ouvert par défaut, les logos officiels, les formes et styles de texte supplémentaires, le zoom au défilement et les réglages de mise en page persistés. Les images et fonds importés passent par `templates.upload` : ne pas réintroduire de `data:` dans la mise en page, car `LayoutSanitizer` les refuse. Le QR de vérification utilise `{verify_url}` pour chaque titulaire. Les cadres sans remplissage gardent leur contour via `rect.fill=false` dans `LayoutSanitizer` et `LayoutRenderer`. L'assistant du badge appelle désormais `templates.generate` et affiche son erreur si aucun fournisseur IA n'est configuré. Les formats et champs propres aux badges restent distincts de ceux des attestations.

Les champs du badge suivent maintenant les jetons affichés dans la palette (`[recipient.*]`, `[badge.ref]`, `[edition.*]`) jusque dans le modèle par défaut JavaScript et PHP. Les anciens jetons `{name}`, `{event}`, etc. restent compris pour les modèles déjà enregistrés. `[recipient.photo]` ajoute un vrai élément `photo` : `LayoutSanitizer` le conserve, `LayoutRenderer` lit la photo du bénéficiaire et affiche ses initiales en son absence. L'aperçu du designer utilise les mêmes champs d'exemple que le rendu délivré ; ne pas revenir à une photo convertie depuis un calque texte au seul moment de la délivrance.

Le pied du badge A6 standard garde lieu et dates à gauche, avec le QR de vérification agrandi à 78 px à droite (`x=295, y=438`), entouré de marge. `LayoutSanitizer::defaultBadge()` et `badge-designer.js` portent les mêmes coordonnées. La migration `2026_10_15_000100` ne reprend que les modèles automatiques strictement reconnaissables (sans `editor_state`) ; un modèle personnalisé reste intact. Le QR ancien `{ref}` est converti en URL de vérification à la délivrance pour compatibilité.

Les pictogrammes de localisation et calendrier du badge A6 sont désormais deux images indépendantes (`public/img/badges/icon-location.png`, `icon-calendar.png`) dans les calques et la palette « Éléments ». Le PNG historique du cadre les contenait déjà : le designer et `LayoutRenderer` couvrent leurs anciennes copies uniquement lorsque les deux nouveaux calques sont présents, pour préserver les modèles personnalisés antérieurs. La migration `2026_10_15_000200` ajoute les calques aux seuls modèles automatiques reconnus, sans `editor_state` ; garder le modèle JavaScript et `LayoutSanitizer::defaultBadge()` synchronisés.

Les noms imprimés sur tous les types de badges suivent désormais le profil courant du compte lié. `BadgeCatalog::recipients()` résout le compte des modérateurs, rapporteurs et membres de comité par e-mail avant de préparer la liste et la planche ; le badge participant utilise la relation `Participant::user`. `BadgeCatalog::forAccount()` et `Account\DocumentController` privilégient explicitement l'identité du compte connecté. Les noms historiques des séances et des fiches d'inscription restent en base pour leurs autres usages ; une affectation sans compte ou sans adresse correspondante conserve son nom d'origine.

Les textes des badges délivrés ne sont plus coupés par la hauteur d'un jeton court enregistré dans le designer : `LayoutRenderer::render(..., badge: true)` conserve les lignes complètes pour l'aperçu participant et la planche d'impression. Le nom du bénéficiaire est réduit seulement si sa longueur ne tient pas dans sa boîte avant le rôle. Le modèle A6 du designer fixe maintenant explicitement les hauteurs des champs Événement, Nom, Rôle et Thème, comme `LayoutSanitizer::defaultBadge()` ; les modèles déjà sauvegardés avec une petite hauteur restent lisibles au rendu sans modifier leurs données.

Le nom du bénéficiaire du modèle A6 standard démarre à **22 px** (avec ou sans photo), dans `LayoutSanitizer` et les variantes `co`/`co-photo` du designer. La migration `2026_10_15_000300` met à 22 px les noms encore à 24 px dans les modèles A6 enregistrés aux coordonnées standard, ainsi que leur instantané d'éditeur ; elle redonne aussi aux seuls blocs standard Événement/Thème trop courts leurs hauteurs de 54/80 px (70 px avec photo). Les autres choix typographiques personnalisés sont conservés. Le texte ne déplace pas automatiquement les autres calques : vérifier la composition en changeant de design ou avec une valeur exceptionnellement longue.

La catégorie de badge participant n'a plus qu'un seul modèle `default`. La variante à photo reste une carte du designer (`co-photo`) et, lorsqu'elle est choisie, se sauvegarde sur ce même modèle. `BadgeCatalog::TYPES` ne publie plus `participant_photo` : la liste des modèles, les badges prêts et leurs compteurs ne doublent plus les mêmes inscrits. L'espace participant rend toujours `default`, avec le calque photo du modèle seulement lorsque le profil fournit une image ; il n'offre plus de préférence individuelle de photo. L'ancien modèle `participant_photo` reste stocké pour préserver les données historiques mais n'est plus utilisé ; `LayoutSanitizer` sait encore lire sa maquette par compatibilité. La migration `2026_10_15_000400` promeut sa mise en page si elle avait été personnalisée et que `default` ne l'avait pas été.

### 2026-10-15 — Fenêtres et icônes des deux designers

Les dialogues « Modifications non enregistrées » des designers de badges et d'attestations utilisent les mêmes dimensions, police, en-tête, pied et boutons arrondis que la confirmation commune du projet. Leur style partagé se trouve dans `resources/css/designer-tailwise.css` ; les trois actions et la gestion du focus restent dans leurs scripts respectifs. Les deux vues autonomes chargent les favicons 16/32 px et l'icône d'accueil dynamique (`Branding::mark()`) comme le layout principal.

### 2026-10-15 — Badge sans photo de profil

Un modèle de catégorie peut contenir un calque photo, mais chaque badge se rend selon le profil de son destinataire. `BadgeLayout::forRecipient()` garde le modèle enregistré si la photo existe et que son titulaire choisit de l'afficher ; sinon, il choisit la maquette officielle sans image et conserve le format sélectionné. Ce choix vaut pour « Mes documents » et la planche administrative. `BadgeCatalog::recipients()` transmet maintenant aussi la photo du compte lié pour les fonctions de modération et de comité. Le repli sur un cercle vide ou les initiales n'est plus le comportement du badge délivré.

Lorsque le modèle d'un type contient un calque photo et que le compte possède une photo de profil, « Mes documents » propose maintenant « Avec photo » ou « Sans photo » pour **chaque badge** de ce type. `Account\DocumentController::updateBadgePhoto()` enregistre le choix dans `participants.meta.badge_photo_preferences[type]` après contrôle du compte, de l'événement et du type. La planche administrative applique le même choix ; en absence de photo de profil, la variante sans image reste obligatoire et la page renvoie au profil pour en ajouter une.
