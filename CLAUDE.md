# CLAUDE.md - Guide de contribution GEVES

Ce fichier est lu automatiquement par Claude Code. Il résume l'essentiel pour être
productif tout de suite. Le contexte détaillé (état, architecture, modules, pièges)
est dans **[`PASSATION.md`](PASSATION.md)** - à lire avant de coder sur un domaine
que vous ne connaissez pas.

## 👥 Mémoire collective (projet à plusieurs)

Le projet est développé **à plusieurs**, chacun avec Claude Code. La **mémoire locale
de Claude Code ne se transfère pas** entre postes/agents : **`CLAUDE.md` et
`PASSATION.md` (versionnés) sont la mémoire et le contexte partagés**. À la fin de
**chaque tâche notable** (nouvelle fonctionnalité, décision d'archi, changement de
convention ou de workflow), **mettez-les à jour** puis committez/poussez avec le
reste. C'est ce qui permet à tous les agents d'avoir le même contexte.

## Le projet en une phrase

**GEVES** (Gestion des ÉVénements Scientifiques - Direction de la Recherche et de
l'Innovation, **Université de Kara**) : plateforme **Laravel 13** qui gère de bout en
bout des événements scientifiques (colloques, journées, séminaires) - soumissions,
relecture en aveugle, paiements, programme/ateliers, documents (badges/attestations/
livret), e-mails, rapports, comptes participants, et assistance **IA**.

Stack : PHP 8.4, Blade + **Livewire 4** + Alpine 3, Tailwind v4 + design system maison
(`resources/css/ukc.css`), Vite 8, SQLite en dev, **Pest** pour les tests, PHPStan/
Larastan **niveau 5**.

### Terminologie de l'évaluation

- Dans les textes affichés, employer **évaluateur** et **évaluation**. Les identifiants techniques historiques (`reviewer`, `review`, chemins et clés de routes contenant `relecteur` ou `relecture`) restent stables pour préserver les liens et données existants.
- Retirer un évaluateur révoque son attestation `instruction_resumes` et l'exclut des statistiques d'évaluation actives. Une réinscription ou réattribution peut rétablir l'attestation révoquée par ce retrait si une évaluation terminée existe ; une révocation manuelle reste manuelle.
- Les onglets de `/admin/rapports` utilisent `?event_id=…&tab=…` et le composant `.ukc-nav-docs`. Dans cette vue, déclarer les tableaux PHP avec `@php … @endphp` : la forme courte `@php(...)` a déjà absorbé le bloc suivant à la compilation Blade.
- La couverture DocSpace du livret est propre à l'événement et facultative. Le PDF fusionné utilise `pdf-lib` via `scripts/merge-livret-cover.mjs` et le binaire `LIVRET_PDF_NODE_BINARY` (défaut `node`). Sans couverture DocSpace active, la couverture automatique reste utilisée.
- La page Utilisateurs lit le pays de la dernière IP depuis la base locale DB-IP Country Lite (`storage/app/geoip/dbip-country-lite.mmdb`) ; ce fichier est ignoré par Git. L'installer après déploiement avec `php artisan geoip:update` ; le planificateur le renouvelle le 3 de chaque mois. La page porte l'attribution DB-IP exigée par la licence. Voir « Le pays d'une IP » ci-dessous pour les trois causes d'une colonne vide.
- Les exemples Office transmis au repli IA OpenRouter sont lus localement ; l'extracteur refuse les archives dépassant 32 Mo après décompression. Les clés API restent dans les réglages chiffrés ou l'environnement.

### Comptes et droits (2026-10-20)

- Le lien « Espace participant » du menu admin cible directement `/compte`. Les routes participant acceptent les guards `account` ou `web`, puis `SyncStaffParticipantSession` ouvre le guard `account` pour le même utilisateur avant le contrôleur. Préserver l'impersonation : ne jamais remplacer le compte emprunté pendant sa session.
- Un compte désactivé ne peut plus se connecter ; `active.user` coupe aussi sa session à la requête suivante, dans les espaces participant, admin et les routes partagées. Le challenge 2FA vérifie à nouveau le statut.
- Seul un super administrateur peut attribuer `access_level=super_admin` ou modifier la matrice des permissions. La visibilité des super administrateurs couvre aussi les anciens rôles `administrator` et la permission `manage_colloques`, y compris avant une usurpation de compte.
- Les fonctions de `event_user` lisent la matrice Spatie enregistrée via `StaffRoles::permissionsFor()`. Le seeder installe les valeurs par défaut à la création d'un rôle sans écraser une matrice déjà personnalisée. Les compteurs de la page Utilisateurs lisent les niveaux d'accès, les fiches évaluateur et les affectations d'équipe, avec dédoublonnage des personnes.
- Les candidatures spontanées se règlent par événement dans **Soumissions → Options** (`settings.reviewer_volunteering`, désactivé par défaut). L'admissibilité reprend `participants.title` (grade de l'édition), puis `users.grade`. `reviewer_applications` conserve les demandes en attente ; seul le rôle `participants.role=reviewer` ouvre l'attribution et l'espace d'évaluation après acceptation dans **Évaluateurs & expertise**. L'acceptation déclenche immédiatement la notification in-app `reviewer_application_accepted` ; `ReviewerRegistrationMail` envoie l'e-mail transactionnel distinct, aussi lors d'un ajout direct par le gestionnaire. Le type de notification a `hasMail=true` pour éviter le doublon. `LinkPendingReviewers` rattache à leur fiche d'évaluateur les personnes ajoutées par e-mail qui créent ensuite un compte avec cette adresse.

### Ajouter ses propres pages au livre des résumés (2026-10-30)

Même grammaire que la **page de garde** : un .docx par page, composé dans
l'éditeur Word en ligne (DocSpace), propre à l'événement. La seule chose en plus
- et c'est tout l'objet du module - est que la page **ne va plus forcément en
tête** : le gestionnaire dit où elle se pose. *Documents › Livre des résumés ›
Pages ajoutées*.

- **Stockage** : `event.settings['doc']['livret.pages']`, lu par
  **`App\Documents\LivretPages`** (id, intitulé, fichier DocSpace, jeton chiffré,
  mode, ancre, position, numéro, actif). Plafonné à **`LivretPages::MAX` = 10** -
  chaque page coûte une conversion DocSpace **à chaque export**, et le livre est
  déjà le document le plus lourd de la plateforme.
- ⚠️⚠️ **LECTURE DIRECTE DES RÉGLAGES, JAMAIS `EventSettings::get`.** Celui-ci
  retombe sur le réglage **global** quand l'événement n'a rien : des pages
  déposées une fois au niveau plateforme s'inviteraient dans le livre de **tous**
  les événements qui n'en ont pas. Un livre ne se remplit pas des pages d'un
  autre colloque. Même précaution que `LivretCover::storedFileId()`.
- ⚠️⚠️ **DEUX FAÇONS DE DIRE OÙ, ET ELLES NE SE VALENT PAS.**
  - **Par section** (`mode = section`) : « avant les Résumés », « après le
    Programme ». L'ancre suit le livre - on ajoute un résumé, le livre grossit,
    la page reste à sa place.
  - **Par numéro** (`mode = page`) : le livre est **reconstruit à chaque
    export**, donc la page 7 d'hier n'est plus celle d'aujourd'hui. Le réglage
    reste exact, c'est ce qu'il y a autour qui a bougé. L'écran le dit **avant**
    le choix.
- ⚠️⚠️ **RIEN NE PASSE DEVANT LA COUVERTURE.** « Avant la page de garde » ferait
  de la page ajoutée la première page du livre, et la couverture DocSpace - qui
  prend la place de la **première** page - s'écraserait sur elle.
  `LivretPages::normalise()` **force** `apres` sur l'ancre `garde` plutôt que de
  s'en remettre au formulaire.
- ⚠️⚠️ **ON RÉSERVE, ON NE GLISSE PAS** (`App\Documents\LivretAssembly`). Le
  livre **numérote** ses pages : une page glissée au milieu décale tous les
  numéros imprimés qui la suivent, **sans que rien ne le signale**. D'où trois
  temps dont **l'ordre ne se permute pas** :
  1. convertir chaque page ajoutée en PDF et **compter** ses pages ;
  2. rendre le livre en lui faisant **réserver** ce nombre de pages blanches à
     l'endroit voulu ;
  3. **remplacer** les pages réservées par les vraies.
  Le comptage vient donc **avant** le rendu : personne ne sait combien de pages
  fait un .docx avant de l'avoir converti, et c'est ce nombre qui dit combien de
  place réserver. ⚠️ Les pages placées **par numéro** font exception - elles ne
  peuvent rien réserver, donc elles décalent ; c'est la rançon de ce mode.
- ⚠️⚠️ **LE REPÈRE EST UN LIEN INVISIBLE, ET C'EST LA SEULE MESURE FIABLE.**
  dompdf transforme un `<a href="ukc-livret://<id>/<k>">` en **annotation**, que
  `pdf-lib` relit pour connaître le **numéro de page exact** de chaque
  réservation. Aucune autre méthode ne tient : la longueur d'une section dépend
  de son contenu, et personne ne peut la prédire avant le rendu.
  ⚠️ **L'inline PHP de dompdf (`<script type="text/php">`) est EXCLU** : la vue
  rend le résumé des auteurs en `{!! !!}`, donc un déposant pourrait faire
  exécuter du PHP sur le serveur en l'écrivant dans son résumé.
  ⚠️ L'assemblage **s'arrête** si les pages réservées ne se suivent pas, ou si le
  document a changé de longueur en route : mieux vaut un export qui échoue qu'un
  export qui écrase du contenu du livre.
- ⚠️ **Le repère est posé HORS de la condition d'affichage de sa section** : un
  avant-propos vide ne rend rien, mais la page rangée « avant l'avant-propos »
  doit tout de même paraître, là où cette section serait venue. Sinon elle
  disparaîtrait du livre en silence.
- ⚠️⚠️ **UNE SEULE LISTE DE POINTS D'ANCRAGE** (`LivretPages::SECTIONS`) : le PDF
  (`documents/livret-pdf`, via le partial `documents/_livret-reserve`) et le
  .docx (`LivretDocx::addPageMarkers()`) posent leurs repères **aux mêmes
  endroits, sous les mêmes clés**. Deux listes se décaleraient, et la page
  atterrirait à un endroit dans le PDF, à un autre dans le Word. Un cas de garde
  balaie les deux fichiers.
- ⚠️ **Le gabarit Word les porte aussi** (`DocxCoverMerger::insert()`, repères
  `[[UKC-PAGE:<id>]]`) : sans cela, celui qui reprend le .docx travaillerait sur
  un livre incomplet sans qu'aucun écran le dise. **Un repère introuvable est
  laissé EN CLAIR** plutôt que la page glissée au jugé. **Deux limites assumées,
  et annoncées à l'écran** : un **modèle .docx personnalisé** ne porte aucun
  repère (ses pages restent dans le PDF), et une page placée **par numéro**
  atterrit en **fin de gabarit** - « page 7 » ne veut rien dire dans Word.
- ⚠️ **Un `select` Ajoutée/Retirée, pas une case à cocher**, lu par
  `input('pageActive', '1')` et non `boolean()` : même piège que la visibilité du
  livre et le bandeau de cookies - décochée, une case n'envoie **rien**, et un
  enregistrement retirerait la page du livre dans le dos du gestionnaire.
- ⚠️ **Retirer n'est pas supprimer** : une page désactivée reste en base, le temps
  d'une relecture, sans refaire sa mise en page. La **suppression**, elle, retire
  la ligne **avant** le fichier DocSpace - un refus côté prestataire ne doit pas
  laisser dans le livre une page qu'on vient d'en retirer.
- ⚠️ **Les deux lecteurs passent par `LivretAssembly::pdf()`** - back-office et
  espace participant. C'est la règle « deux lecteurs, un seul assemblage » de
  `LivretView`, qui avait déjà été payée une fois.
- **Node** : `scripts/livret-assemble.mjs` (pdf-lib), deux actions `count` et
  `assemble`, binaire `LIVRET_PDF_NODE_BINARY`. ⚠️ **La spécification part dans un
  fichier JSON, jamais en arguments** - chemins et titres portent espaces,
  accents et guillemets, que cmd recoupe à sa façon.

Gardes : `PagesAjouteesLivretTest` (17 cas, **7 sabotages vérifiés**).
⚠️ **Piège payé ici** : tous les sous-onglets du livre sont rendus dans la MÊME page (les autres sont seulement masqués). Un cas qui cherche un texte dans la page entière le trouve aussi dans le panneau de la page de garde - il reste vert alors que le panneau visé ne dit plus rien. On borne des DEUX côtés, comme les deux cas de `CookiesTest`.
⚠️ **Débordement horizontal PRÉEXISTANT à 375 px sur cet onglet** (mesuré :
`scrollWidth` 424 pour 375) : il vient de `.ukc-scope-cue`
(`<x-ukc.scope-edition>`), partagé par les autres sous-onglets du livre - l'onglet
Badges, qui ne le porte pas, est à 375 pile. **À corriger dans le composant**, pas
ici.

### Le livre des résumés se MONTRE ou se CACHE, comme les badges (2026-10-30)

Le livre compile les résumés acceptés. Tant que le programme n'est pas arrêté,
ou que des corrections d'auteurs arrivent encore, l'organisation ne veut pas
qu'il circule : **un livre lu trop tôt est un livre qu'on devra démentir.** Il
se règle donc comme les badges - *Documents › Livre des résumés › Contenu du
livre*, sélecteur **Affiché / Caché**.

- **Réglage** : `livret.visible_to_participants` par `EventSettings` (propre à
  l'événement, repli sur le réglage global de la plateforme), lu par
  **`LivretView::visibleToParticipant()`** - la classe qui répond déjà « ce que
  le participant reçoit ». Même grammaire que
  `BadgeCatalog::visibleToParticipant()`.
- ⚠️ **Ouvert par défaut** : c'était le comportement jusqu'ici, et un défaut à
  « caché » retirerait le livre à **tous les événements déjà en base** sans que
  personne ne l'ait décidé.
- ⚠️⚠️ **ON FERME LA PORTE, PAS SEULEMENT LA CARTE.** Retirer le lien de « Mes
  documents » n'empêche personne de poster l'adresse : `livret()` porte la même
  garde. Sans elle, le livre resterait téléchargeable alors que l'organisation
  l'a fermé - et c'est précisément le cas où elle ne veut pas qu'il circule.
  Même doctrine que les passerelles de paiement et le choix d'opérateur.
- ⚠️⚠️ **LE BACK-OFFICE N'EST JAMAIS CONCERNÉ.** Le gestionnaire garde son
  livre, ses exports et son aperçu : lui fermer la porte lui retirerait
  précisément l'outil qui sert à décider si le livre est prêt. Un cas de garde
  le vérifie.
- ⚠️ **Deux conditions pour la carte**, et les deux comptent : le livre doit
  AVOIR du contenu (au moins un résumé accepté canonique) **et** être ouvert.
- ⚠️ **Un `select`, pas une case à cocher**, et la valeur est lue par
  `input('livretVisible', '1')` et non `boolean()` : décochée, une case n'envoie
  RIEN, et on ne distinguerait plus « caché » de « champ absent » - un
  enregistrement du formulaire qui ne porterait pas le champ fermerait le livre
  dans le dos du gestionnaire. Même piège que la case du bandeau de cookies.

Gardes : `LivretVisibiliteTest` (9 cas, **5 sabotages vérifiés**).

### Catégories tarifaires HORS GRADE (2026-10-26)

ONG, entreprise, association : **aucun grade ne les désigne**. Il n'existe
aucune valeur de `ParticipantTypes` qui permette de les déduire - donc rien à
déduire, et c'est l'intéressé qui l'annonce. C'est la **seule exception** à la
règle ci-dessus, et tout ce qui suit sert à la borner.

- **Une catégorie porte `selectable: true`** (case « Catégorie hors grade » dans
  le répéteur des tarifs de la fiche d'événement). Elle est alors proposée au
  participant sur sa page **Inscription & paiement**, à l'inscription comme
  ensuite tant qu'il n'a pas réglé.
- ⚠️⚠️ **ELLE NE COUVRE AUCUN GRADE ET N'EST JAMAIS LE DÉFAUT**, et
  `Fees::normalize()` le **force** plutôt que de s'en remettre au formulaire :
  les deux se contrediraient un jour, et la cascade automatique appliquerait
  « ONG » à quelqu'un qui ne l'a jamais déclaré. Elle est aussi écartée de la
  correspondance « libellé = grade ».
- ⚠️⚠️ **ET DU REPLI `highestAmount()`.** Ce repli frappe qui n'a rien déclaré
  et dont le grade n'est couvert par rien : lui appliquer le tarif
  « Entreprises » parce qu'il se trouve être le plus cher de la grille
  facturerait un tarif d'entreprise à un doctorant.
- ⚠️⚠️ **UN SEUL POINT DE RÉSOLUTION : `Fees::forParticipant()`** (catégorie
  déclarée d'abord, grade ensuite). Le montant **affiché** et le montant
  **facturé** doivent sortir du même calcul : un appelant resté sur
  `resolveAmount($event, $participant->title)` afficherait un tarif que le
  règlement ne reprendrait pas, et **personne ne le verrait avant la caisse**.
  Les cinq appelants (page de paiement, `pay()`, guichet admin, relances du
  copilote, assistante publique) y sont passés, et **un cas de garde balaie
  `app/`** pour que le motif ne revienne pas.
- ⚠️⚠️ **LE CHOIX SE RATTACHE À LA CLÉ, JAMAIS AU LIBELLÉ** (`fees[].key`,
  **persistée** par `SaveEvent`). Rapproché sur le libellé, renommer
  « Entreprises » en « Entreprises & industries » ferait **silencieusement**
  retomber tous ceux qui l'avaient déclarée sur leur grade - donc sur un autre
  montant. Même leçon que les axes d'expertise, comparés par clé. Les grilles
  d'avant ce champ en reçoivent une dérivée du libellé, et deux libellés
  identiques ne la partagent pas.
- ⚠️ **Le choix vit sur le participant, par événement**
  (`participants.meta['fee_category']`, `Participant::feeCategoryKey()`) -
  comme le grade : on vient en entreprise à un colloque et en doctorante à un
  autre.
- ⚠️ **Le grade reste exigé** même quand une catégorie est déclarée : il sert au
  badge, au registre et aux attestations, et **il reprend la main** si
  l'organisation retire la catégorie - on retombe alors sur le grade plutôt que
  de facturer un montant que la grille n'annonce plus. Le choix, lui, reste
  enregistré : la catégorie peut être rétablie.
- ⚠️ **La déclaration se ferme une fois réglé** : le tarif est acquis, et la
  laisser changer ferait diverger la somme encaissée et la catégorie du reçu.
- ⚠️ **On ne choisit pas parmi TOUTE la grille** : seules les catégories
  `selectable` sont proposées, et le serveur refuse les autres - déclarer
  « Doctorants » reviendrait à choisir son tarif.
- ⚠️ **Côté public, la ligne ne liste AUCUN grade** (l'écrire serait faux) et
  n'ouvre **aucun appel à l'action** : son intitulé dit déjà qui elle concerne,
  et cette page renseigne, elle ne vend pas.
- ⚠️ Le champ d'inscription s'appelle désormais **« Mon grade »** et non « Ma
  catégorie » : deux choses différentes ne peuvent pas porter le même mot sur
  le même écran.

Gardes : `CategoriesHorsGradeTest` (12 cas, **8 sabotages vérifiés**).

### Le grade se rapproche d'un tarif PAR CLÉ, jamais par chaîne brute (2026-10-27)

⚠️⚠️ **UN ENSEIGNANT-CHERCHEUR FACTURÉ AU TARIF ENTREPRISE.** Constaté en
production : un « Enseignant-Chercheur » voyait **100 000 FCFA** (le tarif le plus
cher de la grille) au lieu des 35 000 de sa catégorie. Le grade vit en **DEUX**
endroits qui doivent s'accorder : la **liste éditable des grades**
(`ParticipantTypes`, Réglages) que porte le participant, et le `grades` de chaque
catégorie tarifaire - une **COPIE** du libellé choisie par l'organisation dans
l'éditeur de tarifs. Quelqu'un avait renommé le grade dans les Réglages
(« Enseignant / Chercheur » -> « Enseignant-Chercheur ») : la grille gardait
l'ancien texte, `Fees::resolveAmount()` comparait les deux **à la chaîne** (casse
insensible seulement), plus aucune correspondance, et le participant retombait
**EN SILENCE** sur le repli (catégorie par défaut, ou `highestAmount()`).

- ⚠️ **Même famille de piège que les axes d'expertise** (`ReviewerExpertise::axisKey()`)
  et que la clé des catégories (`fees[].key`) : un rapprochement par **libellé libre**
  casse au premier renommage, sans rien signaler. La règle du projet « le tarif se
  déduit du grade » suppose que les deux listes s'accordent, et rien ne le garantit.
- **Remède : `Fees::gradeKey($g)` = `Str::slug($g, '')`** - plie la casse,
  translittère les accents (« Maître » saisi « Maitre » se rapproche) et retire
  tiret/barre oblique/espaces. « Enseignant-Chercheur », « Enseignant / Chercheur »
  et « enseignant chercheur » donnent la **même clé** ; « Enseignant » (grade
  distinct) garde la sienne. Employé dans `resolveAmount()` pour les **deux** boucles
  (grades couverts ET libellé = grade des grilles anciennes).
- ⚠️ **Les accents divergent AUSSI** : un grade stocké « Maitre de Conférences »
  (sans circonflexe) ne tombait pas sur « Maître de conférences » de la grille.
  `Str::slug` les plie tous deux - inclus dans le remède.
- ⚠️ **Effet de bord à connaître sur l'ÉDITEUR** (non corrigé ici, à surveiller) :
  les cases de grade de la fiche d'événement cochent `ParticipantTypes::all()` ; un
  `grades` stocké sur un libellé **disparu** de la liste n'y est plus coché, et
  ré-enregistrer la grille **sans le re-cocher** perdrait le rattachement. La
  normalisation couvre la **facturation** (lecture), pas ce ménage de l'éditeur.
- Gardes : `FeesGradeMatchingTest` (4 cas : séparateur, accents, grades distincts non
  confondus, et la clé elle-même).

### Corriger les auteurs d'une communication (2026-10-26)

⚠️⚠️ **LES AUTEURS NE SONT PAS RÉÉDITABLES PAR LE DÉPOSANT**, et c'est voulu :
sa page « Corriger & renvoyer » ne reprend que titre, résumé, mots-clés et
fichier. Or l'omission d'un co-auteur est la correction la PLUS demandée. Elle
se répare donc au back-office : bouton **« Corriger les auteurs »** sur la fiche
d'une soumission (`admin.submissions.authors.update`, action
**`UpdateSubmissionAuthors`**) - ajouter, corriger une orthographe, réordonner,
retirer.

- ⚠️⚠️ **C'EST UN GESTE DE GESTIONNAIRE : `editions_write`, PAS
  `submissions_list`.** Cette dernière ouvre la LISTE des soumissions et elle
  est portée **aussi par l'évaluateur, le secrétariat, la comptabilité, l'appui
  et l'accueil** - réécrire la paternité d'un travail scientifique n'est pas de
  leur ressort. ⚠️ **Le rattachement manuel d'un auteur à un compte
  (`author.link` / `author.detach`) vivait sous cette capacité trop large** : un
  évaluateur pouvait rattacher un auteur au compte de son choix. Les trois
  routes sont désormais sous `editions_write`.
- ⚠️⚠️ **`author_index` EST UN POINTEUR, ET IL DOIT SUIVRE.** Chaque ligne de
  `submission_authors` désigne sa place dans le JSON `authors` par ce nombre, et
  `EventNameFormat::submissionAuthor()` s'en sert pour retomber sur le nom du
  COMPTE quand le JSON n'a ni prénom ni nom. Un index laissé en arrière après un
  retrait ou un déplacement **n'écrit aucune erreur** : il affiche le nom de
  quelqu'un d'autre en face d'un auteur, dans le livret comme dans le programme.
  D'où la mécanique `origine` (la place ACTUELLE, portée par le formulaire) →
  nouvelle place, et le **retrait AVANT le replacement** (deux lignes sur la même
  place, et `keyBy` n'en garderait qu'une à la relecture).
- ⚠️⚠️ **UN NOM QUE PERSONNE N'A TOUCHÉ N'EST PAS RÉÉCRIT.** Beaucoup d'auteurs
  n'ont **que `name`** (imports, anciens dépôts) : le formulaire doit donc
  PROPOSER un découpage prénom/nom (`EventNameFormat::decouper()`, bloc de
  capitales, sinon dernier mot), sinon il s'ouvrirait avec des champs « Nom »
  vides et obligatoires et il faudrait retaper chaque nom pour ajouter un seul
  co-auteur. Mais ce découpage **peut se tromper** sur un nom composé : sans
  comparaison avec ce qui a été proposé, ouvrir le panneau et enregistrer
  suffirait à passer en capitales - et parfois à réordonner - le nom de TOUS les
  auteurs. On ne réécrit que ce qui a vraiment changé. **Mesuré en conditions
  réelles** : « Pauline Invitée » et « Communicant1 Test » sont restés intacts
  pendant que le co-auteur ajouté sortait en « Ama DOUMBIA ».
- ⚠️ **L'auteur correspondant n'est ni retiré ni relégué ici.** Il est l'ancre de
  la communication - `Submission::authorName()` et `authorEmail()` lisent
  l'index 0, et c'est lui que l'organisation contacte. Le formulaire ne lui
  donne ni flèche ni corbeille, **et le serveur refuse** : masquer un bouton n'a
  jamais protégé personne. Le remplacer est un autre geste, qui touche au
  participant et aux droits d'écriture.
- ⚠️ **Un co-auteur ajouté reçoit EXACTEMENT ce qu'il aurait reçu au dépôt** :
  `LinkSubmissionAuthors::rapprocher()` est le même chemin (rapprochement,
  liaison, fiche participant, notification in-app, e-mail, SMS best-effort, ou
  invitation à créer un compte). Recopier cette cascade aurait fait diverger ce
  qu'on reçoit selon qu'on a été cité le premier jour ou ajouté après - et c'est
  le même évènement pour l'intéressé. **Et l'écran l'annonce avant d'envoyer.**
- ⚠️ **Corriger une orthographe ne renotifie personne** : ce n'est pas un
  évènement pour l'intéressé. Seuls les auteurs **ajoutés** sont prévenus.
- ⚠️ **Le rôle ne se saisit pas**, il se déduit de la place : confié au
  formulaire, on se retrouverait avec deux correspondants, ou aucun.
- ⚠️ **La correction laisse une trace** (`meta['authors_log']` : qui, quand,
  combien d'ajouts et de retraits, 20 entrées au plus). Une liste d'auteurs sort
  dans le **livret** et dans les **attestations** : une correction sans trace est
  une correction que personne ne peut expliquer six mois plus tard.
- ⚠️ **Conséquence à connaître** : un co-auteur ajouté devient éligible aux
  attestations de communication (plafonné par `coauthors_max`, et sous réserve de
  paiement) et la liste change le **livret**, qui lit `submission.authors`.
  C'est l'effet recherché - on répare une omission - mais ce n'est pas anodin
  après l'émission des documents.

Gardes : `CorrigerLesAuteursTest` (15 cas, **4 sabotages vérifiés** : l'index
figé, le correspondant relégué, un nom intact réécrit, et la capacité rouverte
au secrétariat).

### Le pays d'une IP, et le nombre de connectés EN DIRECT (2026-10-26)

Deux colonnes de `/admin/utilisateurs` ne disaient pas ce qu'on croyait.

**1. « Pays (IP) » restait vide, et TROIS causes se confondaient** derrière le
même tiret : la base locale pas installée, une adresse privée, ou une plage
inconnue de l'édition gratuite. L'échec était **muet** - `IpCountry::namesFor()`
rendait un tableau vide sans qu'aucun écran ne le signale, et il n'existait
**aucun test** sur ce chemin.

- ⚠️⚠️ **LA CAUSE DE PRODUCTION EST AILLEURS : L'IP ENREGISTRÉE EST CELLE DU
  MANDATAIRE.** `TrustProxies` n'était pas configuré. Derrière le proxy de
  l'hébergement, `$request->ip()` rend l'adresse du **proxy** : toutes les
  connexions s'enregistrent sous la même IP, donc sous le même pays - une
  colonne qui dit la même chose pour tout le monde est pire qu'une colonne vide.
  Réglage : **`config/trustedproxy.php`**, clé `proxies` (`TRUSTED_PROXIES`).
  - ⚠️ **Le nom du fichier et celui de la clé ne se choisissent pas** :
    `TrustProxies`, déjà dans la pile globale, lit `config('trustedproxy.proxies')`
    **à chaque requête** quand aucun mandataire ne lui a été fixé. C'est ce qui
    permet de ne pas y toucher dans `bootstrap/app.php`, où la configuration
    n'est **pas encore chargée** quand la pile se compose - et où `env()` est de
    toute façon proscrit (`EnvHorsConfigTest`). Un cas éprouve le branchement de
    bout en bout **et** la présence de la clé dans le fichier : renommer l'un ou
    l'autre couperait le fil sans rien casser.
  - ⚠️⚠️ **`*` NE VAUT QUE DERRIÈRE UN VRAI MANDATAIRE.** `X-Forwarded-For` est
    écrit par le client : servi en direct, tout visiteur choisirait l'adresse que
    GEVES enregistre sur sa connexion - la seule trace dont on dispose pour dire
    d'où quelqu'un s'est connecté. **Vide par défaut.**
- ⚠️⚠️ **`composer install` N'EST PAS RÉSERVÉ À LA PREMIÈRE INSTALLATION**, et
  c'est ce qui s'est payé en ligne le 26/10/2026 : `php artisan geoip:update`
  répondait **`Class "GeoIp2\Database\Reader" not found`**. La dépendance
  `geoip2/geoip2` est arrivée le 23/10 et le déploiement suivant n'avait fait
  qu'un `git pull` - le message accuse le code, la cause est le déploiement.
  Même famille que `npm run build` : le dépôt porte le code, pas ce qu'il lui
  faut pour tourner. La commande **le dit maintenant** au lieu de tomber sur une
  fatale, l'écran des utilisateurs distingue les **deux manques** (la base se
  télécharge, la bibliothèque s'installe - deux gestes différents), et
  `DEPLOIEMENT.md` porte enfin l'étape d'installation de la base, qui n'y
  figurait nulle part.
  ⚠️ **Et un cas tient la dépendance en `require`** : déplacée en `require-dev`,
  elle ne s'installerait jamais en production (`--no-dev`) et la colonne
  resterait vide pour toujours.
- ⚠️ **En développement, la colonne ne montrera JAMAIS rien**, et ce n'est pas
  une panne : la seule IP de la base est `127.0.0.1`. Installer la base en local
  n'y changerait rien - une adresse de bouclage n'a pas de pays. La page affiche
  donc **« Réseau local »** plutôt qu'un tiret, qui se lisait comme un échec.
- ⚠️ **La base absente se DIT** (bandeau d'alerte citant `php artisan
  geoip:update`), au lieu de laisser croire que personne n'est localisable.
- **Repli hébergé, à la manière de Bizzcart** (dont le pilote par défaut est un
  service HTTP sans clé, ce qui explique qu'il « marche tout de suite ») :
  `geoip.fallback`, un seul fournisseur (ipapi.co, HTTPS, sans clé), consulté
  **uniquement** pour les adresses que la base locale n'a pas su lire.
  - ⚠️⚠️ **IL EST ÉTEINT PAR DÉFAUT, ET CE N'EST PAS UN OUBLI.** Le docblock
    d'origine d'`IpCountry` promettait « sans transmettre les IP à un tiers » :
    allumer ce repli envoie l'adresse des participants chez quelqu'un d'autre.
    C'est une décision qui engage leurs données, pas un réglage de confort.
  - ⚠️ **Plafonné par affichage** (10) et **mis en cache** (30 j pour un pays
    trouvé, 1 j pour une absence) : une liste de vingt comptes ne doit pas
    devenir vingt appels sortants au rendu d'une page. Un échec du service
    n'empêche jamais la liste de s'afficher.

**2. « En ligne » comptait autre chose que ce qu'on lui demandait.** Le chiffre
venait de la table des sessions, par un sondage toutes les 30 s : il disait quels
comptes avaient été **actifs dans les cinq dernières minutes**, pas qui était là.
Un **canal de présence** (`Realtime::presenceChannel()`, `ukc.enligne`) répond à
la vraie question, et la carte se renomme alors **« En ligne maintenant »**.

- ⚠️⚠️ **IL NE COÛTE AUCUNE CONNEXION DE PLUS.** La cloche de notifications
  ouvre **déjà** un WebSocket sur chaque page authentifiée (les deux layouts la
  portent) : le canal se greffe sur cette connexion-là. Un service hébergé
  facture les **connexions**, pas les canaux - en ouvrir un second doublerait la
  note pour le même chiffre. C'est pourquoi l'abonnement vit dans
  `ukcNotificationBell` : elle ne s'en sert pas, elle le **porte**, et émet
  l'évènement `ukc-en-ligne`.
- ⚠️⚠️ **CE CANAL EST UN SIGNAL, PAS UNE SOURCE DE CHIFFRE** (corrigé le
  2026-10-26, quelques heures après l'avoir posé autrement - et la correction
  est venue d'une demande d'écran, pas d'une relecture). Il sait **combien** de
  personnes tiennent une page ouverte, jamais **lesquelles**, puisqu'on n'y
  publie que des empreintes. Or la page des utilisateurs a maintenant besoin des
  **deux** : un compteur ET une pastille par ligne. Afficher le compte du canal
  au-dessus de pastilles calculées depuis les sessions ferait dire **deux choses
  au même écran**, pour deux mesures qui diffèrent vraiment - un onglet ouvert
  mais inactif depuis dix minutes compte pour le canal, pas pour les sessions.
  Le canal dit donc seulement « quelqu'un vient d'arriver ou de partir,
  redemande au serveur » (freiné à 400 ms, une arrivée groupée émettant en
  rafale), et **une seule requête** rend le nombre ET la liste.
  ⚠️ **Contrepartie assumée** : fermer un onglet ne fait pas passer la pastille
  à « Hors ligne » tout de suite - la session court encore jusqu'à cinq minutes.
  On préfère un écran cohérent à un écran plus prompt mais qui se contredit.
- ⚠️⚠️ **LA LISTE DES MEMBRES EST LUE PAR TOUS LES AUTRES MEMBRES**, et sur ce
  canal-là ils sont l'ensemble des personnes connectées à la plateforme. D'où
  **`User::getAuthIdentifierForBroadcasting()`**, qui rend une **empreinte**
  (`Realtime::presenceMemberId()`) et non la clé primaire : publier l'identifiant
  donnerait à chaque participant la liste de qui est en ligne - sur un site qui
  organise des évaluations en aveugle, ce n'est pas un détail. Elle est **stable
  par personne**, ce qui fait que deux onglets comptent pour **une** personne.
  ⚠️ Elle vaut pour **tous** les canaux de présence, le canal de document
  compris : son cas de `DocumentDeTacheTest` a été repris en conséquence.
- ⚠️⚠️ **UNE SEULE DÉFINITION POUR LE COMPTEUR ET POUR LA COLONNE**
  (`UserController::enLigne()`) : le nombre de la carte est la **taille** de la
  liste qui peint les pastilles. Calculés chacun de leur côté, ils finiraient
  par se contredire, et une carte qui annonce un chiffre que le tableau ne
  confirme pas se lit comme une panne. Le sondage de 30 s reste comme filet
  quand le temps réel n'est pas réglé.
- ⚠️⚠️ **LA COLONNE S'APPELLE « PRÉSENCE », PAS « ÉTAT »** : la colonne voisine
  s'appelle déjà **Statut** et dit tout autre chose (compte actif ou désactivé).
  Deux intitulés voisins pour deux notions différentes se confondent à la
  lecture.
- ⚠️ **`null` ne veut pas dire « personne », mais « on ne sait pas »** : hors
  sessions en base, il n'existe aucune source par personne. La colonne affiche
  alors un tiret et le dit en infobulle, au lieu d'annoncer tout le monde hors
  ligne - ce serait une affirmation fausse.
- ⚠️⚠️ **LA CARTE EST CLIQUABLE, ET CE N'EST PAS UN ORNEMENT.** Avec 72 comptes
  sur quatre pages, « 1 en ligne » au-dessus d'une page où tout le monde est
  hors ligne se lit comme une panne : le chiffre doit mener aux personnes qu'il
  compte (`?presence=online`). Même grammaire que les indicateurs de Soumissions
  - le lien **bascule**, il **emporte les autres filtres** (sinon on perd l'axe
  ou la recherche en cours), la rangée est une **région échangée**
  (`users-kpis`, sans quoi le lien resterait pointé sur les filtres précédents
  après un filtrage en AJAX), et un **champ caché** porte la présence dans la
  barre - sinon taper une recherche la reperdrait. Une valeur inconnue est
  **ignorée**. Et le sélecteur nomme l'élément (`a.ukc-stat-card.is-on`) : à
  spécificité égale Tailwise gagnerait, et l'état allumé ne se verrait jamais.
- ⚠️ **Un tableau VIDE serait un refus** : le socle n'autorise un canal que sur
  une valeur vraie, et `[]` est faux en PHP. On rend donc l'empreinte.
- Les identifiants PieSocket/Pusher se règlent dans **Réglages › Intégrations**
  (déjà en place) : ce canal est le **second consommateur** réel de la couche
  temps réel, après la cloche.

Gardes : `PaysParIpTest` (14 cas) et `PresenceEnLigneTest` (12 cas),
**19 sabotages vérifiés**.
⚠️ **Piège rencontré ici** : `php.bat` ne transmet pas les arguments
**multi-lignes** (cmd les aplatit) - un motif de sabotage doit tenir sur une
ligne, sinon la substitution échoue et on croit la garde en cause.
⚠️ **Et un sabotage de la configuration ne mord que si le cas lit le FICHIER** :
poser `config(['trustedproxy.proxies' => …])` dans un cas éprouve le socle, pas
notre déclaration - il a fallu un second cas pour couvrir celle-ci.

### Cookies : un bandeau qui INFORME, parce qu'il n'y a rien à refuser

⚠⚠ **GEVES NE POSE QUE DES COOKIES STRICTEMENT NÉCESSAIRES** : la session
Laravel, le jeton CSRF, et l'accusé de lecture du bandeau lui-même. Ni mesure
d'audience, ni régie, ni contenu tiers embarqué sur le site public (les YouTube
du pied de page sont des **icônes de lien**, pas des lecteurs). Le bandeau
**informe** donc, avec un seul bouton « J'ai compris » - il ne demande pas un
consentement qui ne gouvernerait rien.

- ⚠⚠ **ON N'A PAS REPRIS LE « REFUSER » DU BANDEAU DE RÉFÉRENCE** (Bizzcart, via
  `spatie/laravel-cookie-consent`) : son `consentWithoutCookies()` **efface tous
  les cookies du domaine** puis invite à recharger la page. Transposé ici, il
  déconnecterait le participant et ferait échouer sa requête suivante sur le jeton
  CSRF - pour refuser des cookies qui n'existent pas. Le jour où un outil de
  mesure arrive, le choix s'ajoute dans **`App\Support\CookieConsent`** et nulle
  part ailleurs ; un cas de garde (« n'annonce AUCUN cookie de mesure ») tombe
  alors et oblige à revoir le texte du bandeau, qui promet aujourd'hui l'inverse.
- ⚠⚠ **LE BALISAGE NE VARIE PAS D'UN VISITEUR À L'AUTRE.** Les pages publiques
  sont faites pour être **cacheables en HTTP** : rendre le bandeau « seulement si
  le cookie manque » ferait entrer l'état d'UN visiteur dans le cache de TOUS. Le
  serveur rend toujours le même HTML, le bandeau part **`hidden`**, et un script
  le révèle si le cookie manque. ⚠️ Dans l'autre sens - visible puis masqué - il
  clignoterait à chaque page pour qui l'a déjà lu.
- ⚠️ **La place de la bulle de l'assistante est RÉSERVÉE, pas devinée** : les deux
  sont fixés en bas et se recouvrent par construction. Le dégagement reprend le
  jeton **`--ukc-cb-fab`** (même leçon que le panneau du chatbot) ;
  `initChatbotPoliteness()` ne reste qu'un filet - **au doigt il n'y a pas de
  pointeur** pour déclencher la politesse.
- **La page `/cookies` (`public.cookies`) est la SUBSTANCE du bandeau** : son
  inventaire est construit depuis la **configuration réelle**
  (`CookieConsent::inventory()` lit `session.cookie`, `session.lifetime`,
  `cookies.lifetime_days`), jamais recopié à la main - une liste écrite en dur
  annoncerait autre chose que ce que le navigateur reçoit. **Turnstile n'y figure
  que s'il est configuré** : sans ses deux clés, le widget ne se charge nulle part.
- ⚠️ **Elle est liée depuis le PIED DE PAGE**, pas seulement depuis le bandeau :
  sinon elle n'existerait que pour qui a cliqué une fois, et plus jamais après.
  Le bandeau ne s'affiche pas sur cette page - il masquerait son propre inventaire.
- Activable dans **Réglages › Sécurité** (`cookies.notice_enabled`), surchargé au
  runtime par `applySettingsToConfig()` - l'équivalent du `ConfigTrait` de la
  référence. ⚠️ **Une case à cocher ne passe pas par `filled()`** : décochée elle vaut
  `false`, que `filled()` rejette, donc « ne pas afficher » ne serait jamais
  appliqué. On regarde si la clé EXISTE.
- ⚠⚠ **LA PHRASE DU BANDEAU TIENT SUR UNE LIGNE, ET C'EST MESURÉ.** Elle en
  occupait deux. Budget du texte = plafond de la barre (1200) - rembourrage gauche
  (16) - rembourrage droit, qui **réserve la bulle de l'assistante**
  (`--ukc-cb-fab` 84 + 28 = 112) - goutttière (16) - les deux actions (202,4) =
  **853,6 px**. L'ancienne phrase en réclamait **981,8**, l'actuelle **692** - une
  ligne jusqu'à ~1040 px de fenêtre. ⚠️ **Mesuré sur la VRAIE FONTE** (FontLib,
  déjà là pour dompdf : on somme les chasses de `jost-400.ttf`), pas à tant de
  pixels par caractère - une estimation se trompe de dizaines de pixels sur une
  phrase, exactement là où se joue le passage à la seconde ligne. Un cas Pest
  **relit la phrase dans la vue** et la remesure : la rallonger le fait tomber.
  ⚠️ Contrepartie assumée : « aucune mesure d'audience, aucun suivi publicitaire »
  ne tenait pas dans ce budget - le bandeau dit « Aucun traceur » (le terme de la
  CNIL, qui couvre les deux) et la page `/cookies` garde la précision entière.
- ⚠⚠ **LA PAGE `/cookies` OCCUPE SA LARGEUR.** Sa première version bornait tout
  à 960 px et la prose à 62ch : dans une carte de 1135 px, **cinq cents pixels
  restaient vides à droite** - le travers déjà payé sur l'aperçu du programme.
  Les **chiffres** (`.ukc-cookie-facts`) et le **tableau** s'étendent ; seule la
  PROSE garde sa mesure, parce qu'au-delà de ~75 caractères par ligne l'œil perd
  le retour à la ligne.
  - ⚠️ **Les trois chiffres se DÉDUISENT de l'inventaire** (nombre de cookies,
    0 traceur, durée la plus longue) : écrits en dur ils mentiraient dès qu'un
    cookie s'ajoute - et c'est précisément la page où l'on vient vérifier un compte.
  - ⚠️ **L'inventaire reste un `<table>`** : quatre colonnes comparables, que des
    cartes désaligneraient (leçon du barème). Seule sa présentation change.
    **Sous 720 px il S'EMPILE** au lieu de défiler - une barre horizontale cache
    la moitié du tableau - et chaque cellule reprend alors son intitulé via
    `data-th` + `content: attr(data-th)`, sans quoi on lit des valeurs sans savoir
    ce qu'elles sont.
  - ⚠⚠ **« TIERCE PARTIE » EST UNE DONNÉE (`third`), PAS UNE DÉDUCTION DEPUIS LE
    LIBELLÉ.** La vue comparait `party` à `__('Première')` : sous session anglaise
    la comparaison échouait et Cloudflare perdait sa distinction visuelle - la
    seule ligne qui engage quelqu'un d'autre que nous. C'est `VocabulairePublicTest`
    qui l'a trouvé, en refusant cette clé d'un seul mot.
- Tests : `tests/Feature/CookiesTest.php` (14 cas, **9 sabotages vérifiés**).
  ⚠️ **DEUX cas ne mordaient pas**, et seul le sabotage l'a montré : celui du lien
  du pied cherchait l'adresse dans la **page entière**, où le bandeau porte la
  même (« En savoir plus ») - puis, une fois borné à partir de `<footer`, il
  l'embarquait encore, le bandeau étant rendu **après** `</footer>`. On borne des
  DEUX côtés.

### Protection des formulaires publics

- L'inscription participant (`/inscription`) et le formulaire de contact de l'accueil utilisent Cloudflare Turnstile. Renseigner les deux clés dans **Réglages → Sécurité** (secret chiffré en base) ou dans `CLOUDFLARE_TURNSTILE_SITE_KEY` et `CLOUDFLARE_TURNSTILE_SECRET_KEY`, avec les domaines autorisés dans Cloudflare. Sans les deux clés, les boutons d'envoi restent désactivés ; le serveur refuse aussi les requêtes directes.
- `<x-turnstile action="registration|contact" />` affiche le widget. `App\Support\Turnstile::validate()` appelle Siteverify côté serveur avant la création du compte ou l'envoi de l'e-mail, puis contrôle le succès, l'action et le domaine. Garder la clé secrète uniquement côté serveur ; ne pas contourner ce contrôle pour les POST directs. Le contact conserve aussi son honeypot et sa limite par IP.

### Soumission : type de communication et deux modes distincts

- `submissions.type` conserve **oral/poster** (« Type de communication »). `submissions.presentation_mode` conserve **online/onsite** (« Mode de présentation »), propre à chaque communication. `participants.participation_mode` conserve **online/onsite** (« Mode de participation au colloque »), propre à la personne et à l'événement. Changer l'un ne modifie pas l'autre. Les deux champs respectent `Event.settings.format` et sont validés côté serveur.
- `SubmissionFormConfig` conserve la clé de configuration historique `participation_mode` pour les formulaires enregistrés, mais présente « Mode de présentation ». Le POST du formulaire et la colonne des soumissions utilisent `presentation_mode`. La migration `2026_10_14_000300` reprend le mode original de chaque résumé importé depuis `meta.presentation_mode`, sinon le dernier mode connu du participant pour les dépôts historiques.
- Les trois choix (axe, type, présentation) occupent une ligne sur grand écran dans `account/submissions/create.blade.php`. Leur grille `submission-core-fields` est distincte de celle du titre pour utiliser toute la largeur ; conserver cette séparation. La présentation est visible et filtrable dans les soumissions du back-office et les créneaux du programme ; le CSV exporte séparément présentation et participation au colloque.
- Le participant peut modifier son mode de participation dans **Inscription & paiement**, même avant paiement, via `account.participation.mode.update`. Il peut modifier le mode de présentation d'une seule communication depuis sa fiche via `account.submissions.presentation-mode.update`. Les deux routes vérifient la propriété et les choix permis par le format de l'événement.

### Page participant Inscription & paiement

- `resources/views/account/payment/pay.blade.php` affiche d’abord l’état réel de la participation et la prochaine action ; la grille tarifaire est un `<details>` secondaire. Si le paiement est ouvert, le montant et les moyens disponibles passent en premier. Le choix de passerelle est transmis par le radio `name="gateway"` ; conserver le `x-model="gw"` utilisé par le sélecteur d’opérateur. Les tarifs sont fournis par `Fees`, et un libellé de catégorie ne doit être présenté comme personnel que si `Fees::resolveAmount()` a trouvé une correspondance explicite.

### Accueil public : À propos

- La section `#apropos` de `home.blade.php` est une présentation concise en deux panneaux : rôle de la plateforme et responsabilité de la DRI. Le panneau bleu utilise le **logo large pour fond sombre** (`Branding::wideWhite()` + secours `logo-auto-white`), adapté à sa largeur. Les fonctionnalités détaillées restent dans `#fonctionnalites` ; éviter de réintroduire des cartes qui répètent le même contenu. Les textes sont traduits dans `lang/en.json` et les styles résident dans `resources/css/public.css`.

### Colloque Eau au Togo - import réel

- `database/seeders/ColloqueEauTogoSeeder.php` est un import **manuel et autonome** : **46 réponses Google Forms, 49 résumés et les 49 Word** intégrés au même fichier (export du 18/10/2026). Lancer `php artisan db:seed --class=ColloqueEauTogoSeeder` seulement sur la base voulue ; ne pas l'ajouter à `DatabaseSeeder` (données personnelles réelles). ⚠️ **Mise à jour = régénérer `RESPONSES_JSON` + `ARCHIVE_BASE64`/`ARCHIVE_SHA256` depuis le nouvel export** (Excel + ZIP des Word), et ajuster les compteurs durs de `run()`/`validateDocuments` (46, 49, `range(1, 49)`). Le mapping réponse→Word se fait par l'**identifiant Drive stable** de la colonne « Version électronique » (repris de l'ancien `drive_url`→`file_sha256`), pas par le nom de fichier. ⚠️ L'export « File responses » peut **manquer** un Word (un déposant avait fourni le sien à part, réintégré à l'archive) : chaque résumé doit garder sa pièce, sinon relâcher la contrainte 1-Word-par-résumé. Garde end-to-end : `EauTogoEquipeTest` (il **exécute le seeder**, deux fois pour l'idempotence).
- Le seeder conserve les réponses libres de co-auteurs et les préférences de paiement en `meta`. Il crée ou retrouve le compte global de chacun des 44 auteurs répondants, relie leur fiche et leurs soumissions, avec mot de passe aléatoire et réinitialisation obligatoire. Une adresse source invalide reçoit un compte réservé `@geves.invalid` à corriger manuellement avant accès ; aucun e-mail de vérification n'est envoyé par l'import. Les co-auteurs libres ne sont pas provisionnés sans identité fiable. Aucun paiement ni décision n'est créé. `colloques:reseed` efface tout le domaine des événements, y compris ce colloque : ne pas l'utiliser pour actualiser cet import.
- ⚠️⚠️ **UN SECOND EXPORT NE SE RÉIMPORTE PAS, IL SE COMPLÈTE**
  (`ColloqueEauTogoComplementSeeder`, export du 26/10/2026 : **13 résumés**
  arrivés après celui du 18/10). Régénérer `RESPONSES_JSON` + `ARCHIVE_BASE64`
  du seeder d'origine ferait repasser un import sur 49 communications **déjà en
  production**, que le comité scientifique a pu toucher. Le complément ne
  connaît que le DELTA, n'écrit que des lignes neuves, et **ne modifie rien
  d'existant**. Lancement : `php artisan db:seed
  --class=ColloqueEauTogoComplementSeeder` (jamais depuis `DatabaseSeeder`).
  - ⚠️⚠️ **UNE PERSONNE CHANGE D'ADRESSE ENTRE DEUX EXPORTS, ET C'EST ARRIVÉ.**
    La ligne 31 revient avec un e-mail neuf, le **même téléphone** et un second
    résumé. La chercher par la seule adresse aurait créé un **second compte**
    pour quelqu'un que la plateforme connaît déjà - et l'insertion serait de
    toute façon tombée sur l'unicité de `users.phone`, le piège documenté plus
    bas. La personne se résout donc par **compte, puis adresse, puis téléphone
    normalisé**, pour le `User` comme pour le `Participant`.
  - ⚠️ **La fiche sans compte compte aussi** : le secrétariat en ouvre au
    guichet (`/admin/paiements/creer`), sans `user_id`. Si cette personne dépose
    ensuite depuis le formulaire avec une autre adresse, seul le téléphone les
    rapproche - d'où le repli par téléphone **sur le `Participant`**, et pas
    seulement sur le compte.
  - ⚠️⚠️ **LA NUMÉROTATION SE CALCULE, ELLE N'EST PAS ÉCRITE EN DUR.** La
    production porte déjà `26-050` - une communication saisie au **back-office**,
    donc numérotée par `SubmissionObserver` au format `AA-NNN`, à ne pas
    confondre avec les `EAU26-###` de l'import. Le complément repart du plus
    grand `EAU26-###` trouvé en base : il ne peut écraser aucune référence, ni
    les siennes ni celles d'un humain.
  - ⚠️ **L'idempotence tient à l'empreinte du Word** (`meta.file_sha256`), pas à
    la référence : un rejeu reconnaît un résumé déjà importé et passe. ⚠️ Lue
    **en PHP**, jamais en SQL - les fonctions de chemin JSON diffèrent entre
    MySQL et SQLite, et le défaut se découvrirait en ligne.
  - ⚠️ **Les axes se lisent sur l'ÉVÉNEMENT** (`settings['tracks']`), jamais
    d'une liste recopiée : deux listes divergeraient, et un axe inventé
    passerait inaperçu.
  - ⚠️ **Ce que le complément n'applique PAS, délibérément** : la ligne 31 a
    aussi changé d'adresse, d'affiliation et reformulé le titre de son premier
    résumé. Changer une adresse, c'est changer un identifiant de connexion -
    c'est un geste de back-office, pas de migration.
  - ⚠️ **L49 a soumis DEUX FOIS le même travail** (même axe, mêmes mots-clés, le
    champ résumé du second ne contenant que son titre). Les deux sont importés
    **fidèlement** : un seeder importe, il n'arbitre pas, et une communication
    se refuse d'un clic alors qu'elle ne se recrée pas. Le comité a donc un
    doublon à fusionner.
  - ⚠️ **L'appariement résumé→Word se fait par le CONTENU**, le ZIP ne portant
    aucun identifiant Drive : le titre est cherché **accents compris** dans le
    texte du .docx, puis sans accents en repli. Deux titres jumeaux de L49 ne se
    séparaient QUE par leurs accents - une normalisation trop zélée les aurait
    confondus. L'ordre du ZIP, lui, n'explique rien (vérifié contre les 49
    appariements déjà établis).
  - Gardes : `EauTogoComplementTest` (8 cas, **5 sabotages vérifiés**) - il
    exécute le seeder de base **puis** le complément, comme la production.
- ⚠️⚠️ **LES CO-AUTEURS SE COMPLÈTENT APRÈS COUP, ET C'EST UN SEEDER À PART**
  (`ColloqueEauTogoCoauthorsSeeder`). Les deux imports stockaient le texte libre
  de la colonne « Les autres auteurs et affiliations » dans
  `submission.meta['coauthors_raw']` mais ne le transformaient **jamais** en
  auteurs : `submission.authors` ne portait que l'auteur correspondant. Ce seeder
  le complète. Lancement, **après** base + complément :
  `php artisan db:seed --class=ColloqueEauTogoCoauthorsSeeder` (jamais depuis
  `DatabaseSeeder`).
  - ⚠️⚠️ **LE TEXTE EST 100% LIBRE, ET IL A ÉTÉ RELU À LA MAIN.** Noms et
    affiliations mêlés, listes numérotées, « Néant/Aucun/RAS », parfois des
    mots-clés collés par erreur, le déposant qui se re-liste, une coquille de
    source (« Mozimwè AN » pour ANI). Un parseur automatique en abîme ~1 sur 8
    (initiale mangée, noms collés, titre coupé) - **inacceptable sur un livret
    officiel**. La liste embarquée (`CURATED_JSON`, 40 communications,
    127 co-auteurs) a donc été extraite **puis corrigée et relue**, cas par cas,
    et c'est elle qui fait foi : elle se lit dans le diff. Ne pas la régénérer
    par regex sans relire.
  - ⚠️ **Appariement par `sha256(meta.coauthors_raw)`** : autonome (pas besoin du
    xlsx à l'exécution), et il couvre les **deux** imports d'un coup - les textes
    co-auteurs des exports 18/10 et 26/10 coïncident au octet près (couverture
    **40/40** vérifiée avant livraison). Une cellule dont le texte aurait été
    modifié après la relecture ne s'apparierait plus : on ne devine rien, on
    passe.
  - ⚠️ **On ne touche QUE `submission.authors`** (affichage : livret, programme,
    fiche). **Aucune** ligne `submission_authors`, **aucun** compte, **aucune**
    notification : un co-auteur libre sans e-mail fiable n'est pas provisionné
    comme compte (la règle d'origine du colloque). L'auteur correspondant
    (`authors[0]`) n'est **jamais** modifié.
  - ⚠️ **Le déposant re-listé en co-auteur est dédoublonné par ENSEMBLE DE MOTS**
    (accents, casse et ordre ignorés) : « Abba Amba BRAHIM » ne s'ajoute pas
    derrière le correspondant « ABBA AMBA BRAHIM ». Conséquence : **39**
    communications gagnent un co-auteur, pas 40 - la 40e (KUEVIAKOE) ne « listait »
    que le déposant lui-même, et garde donc son seul auteur correspondant.
  - **Idempotent** : il RECONSTRUIT `authors` = correspondant + co-auteurs relus
    et n'écrit que si ça change ; un second passage ne touche rien.
  - Gardes : `EauTogoCoauthorsTest` (5 cas) - il exécute base + complément + ce
    seeder, comme la production.
- Le séparateur des mots-clés est réglable par événement dans le champ « Mots-clés » du formulaire de soumission : virgule, point-virgule ou les deux. `SubmissionKeywords` centralise le découpage des badges, de l'export et des documents ; le seeder Eau au Togo choisit les deux, car les réponses sources mélangent les séparateurs. Le nom court de cet événement est « Colloque Eau au Togo ».
- Les deux affiches JPEG officielles sont intégrées au même seeder. Une relance ajoute leurs médias, le thème exact, les échéances, la grille de six tarifs et la limite de 250 mots au formulaire, en conservant les réglages humains déjà personnalisés. Les affiches et la date confirmée par l'organisateur indiquent **11-13 novembre 2026**. Ces affiches portrait restent des documents de l'événement ; le format de bannière publique recadrerait leur texte.

### Prise en main back-office

- La page **Prise en main** (`/admin/prise-en-main`, `admin.onboarding`) est ouverte
  à tout membre du staff connecté. Les administrateurs de colloque et super
  administrateurs y modifient les textes, les rôles ciblés, les vidéos téléversées
  ou référencées et leur visibilité dans l’espace participant. Les guides sont dans
  `tutorial_guides` ; ne jamais réintroduire leur contenu en dur dans la vue.
- Les demandes de production ou de mise à jour vidéo sont stockées dans
  `tutorial_video_requests`. Le travailleur choisit Gemini, Claude ou OpenAI parmi
  les clés saisies dans **Réglages**, puis fait relire le scénario par un autre
  fournisseur disponible. `TutorialVideoRenderer` rend ensuite le MP4 avec Node,
  FFmpeg et Hyperframes sur le serveur : aucun compte Codex/Claude/Gemini ouvert
  sur le poste de l’administrateur n’est nécessaire. Le contrat persistant
  `meta` est construit par `TutorialVideoProduction` et s’inspire de **Brag** : logo
  compact UK configurable, palette et police GEVES, lit musical original audible et
  mouvements sobres.
  Une vidéo dure au moins 36 secondes : elle reprend l’introduction exacte du guide,
  puis une scène séquentielle pour chaque étape, dans leur ordre. Avant le rendu,
  `TutorialVideoScreenshotService` appelle la capture native Hyperframes sur les
  vraies pages GEVES au moyen du pont signé `tutorial.capture` et du compte
  `captures-tutoriels@geves.invalid`. Ce compte est créé automatiquement, isolé des
  comptes humains et ne sert qu’à la session de navigateur éphémère. Les captures
  ne doivent pas être remplacées par des maquettes. L’agent rattache le MP4 au guide
  après contrôle. Les livrables sont sous
  `storage/app/tutorial-video-production/request-{id}/` : plan, brief de
  composition, poster, MP4 et texte de partage. Chaque demande déclenche `GenerateTutorialVideo` sur la file
  `tutorial-videos`, puis `TutorialVideoWorkerLauncher` démarre un travailleur caché
  sous Windows. Il conserve le PID du worker et contrôle qu'il existe encore avant
  de réutiliser le verrou : ne jamais remettre un verrou booléen d'une heure avant
  d'avoir obtenu ce PID, car une demande resterait alors indéfiniment en attente.
  `TutorialVideoAgent` lance le rendu Hyperframes, valide le MP4 produit et
  le publie dans le disque public. Le modal permet de conserver la **Signature
  GEVES** par défaut (`resources/media/tutorials/uplifting-corporate-loop.mp3`), de choisir les variantes locale `calm` ou `dynamic`,
  de demander une vidéo sans musique, ou de téléverser une piste MP3/M4A/AAC/WAV
  (25 Mo, prioritaire sur le choix). La commande `tutorial-videos:dispatch-pending`
  reprend les demandes créées avant l’activation de ce pont.
- L’espace participant expose `/prise-en-main` (`account.onboarding`). Il filtre les
  guides selon le colloque courant et les rôles réels (participant, modérateur,
  rapporteur) ; une vidéo masquée ne doit jamais y être rendue. Ses onglets de
  catégories filtrent aussi les cartes sans rechargement, avec la même présentation
  en pilules que le back-office.
- Le catalogue initial contient **onze guides** : deux présentations distinctes
  de GEVES (participant et administrateur), puis les parcours soumission, visio,
  rapporteur, modération, gestion de colloque, configuration, secrétariat et les
  deux assistants. Les textes et étapes sont dans `TutorialGuideCatalog`, les
  pages réelles à capturer dans `TutorialCaptureCatalog`. `ensureDefaults()` ne
  remplace que les dix anciens guides restés strictement inchangés : préserver
  les adaptations humaines. Lorsqu'un texte change, l'ancienne vidéo devient
  `stale`, est masquée aux participants, puis peut être actualisée via la
  production individuelle ou groupée. Le navigateur de capture emploie un
  compte de démonstration distinct et non privilégié pour les parcours participant.

## Toolchain - ⚠️ PHP/Composer/artisan ne sont PAS dans le PATH

Ils sont fournis par **Laravel Herd** sous forme de shims `.bat`. Pour toute commande
(Git Bash) :

```bash
export PATH="$HOME/.config/herd/bin:$PATH"
php.bat artisan ...        # PHP 8.4
composer.bat ...           # Composer
```

Node/npm sont dans le PATH. Le site est servi par **Herd** sur **https://geves.test**
(le dossier local reste `gesco`, malgré le renommage GEVES ; `gesco.test` ne répond plus).

### Commandes utiles

```bash
php.bat artisan test                       # suite Pest (base :memory:)
php.bat artisan test --filter=NomTest      # un fichier
php.bat vendor/bin/phpstan analyse         # analyse statique (niveau 5)
php.bat vendor/bin/pint                     # style (lancer AVANT de commiter)
composer.bat check                          # pint --test + phpstan + tests (= le CI)
npm run build                               # build des assets (requis pour voir le rendu)
```

⚠️ `vendor/bin/phpunit` ne marche pas (Pest requis). Le lanceur affiche une sortie
JSON compacte, pas la trace complète.

⚠️ Les tests doivent ignorer `bootstrap/cache/config.php` : ce cache local peut
pointer vers la base SQLite de développement malgré `DB_DATABASE=:memory:`.
`phpunit.xml` définit `APP_CONFIG_CACHE=bootstrap/cache/config.testing.php` pour
isoler le processus de test. Garder cette protection et, pour un test manuel,
définir explicitement `DB_CONNECTION=sqlite` et `DB_DATABASE=:memory:`.

⚠️ **PHPStan et les chemins relatifs** : lancé depuis la racine, `phpstan analyse`
se termine en **code 1 sans rien écrire** - ni erreurs, ni « No errors », ni fatale.
Le `paths: - app` relatif de `phpstan.neon` en est la cause ; en chemins absolus la
même analyse s'exécute normalement. Un silence n'est donc **pas** un succès : si la
commande ne dit rien, rejouer l'analyse avec une configuration temporaire reprenant
`phpstan.neon` avec `paths` et `includes` en absolu.

## Voir le rendu

Builder les assets (`npm run build`) puis ouvrir **geves.test** - **ne pas** compter
sur le serveur Vite dev (bloqué en cross-origin ici). Le navigateur intégré exige une
approbation par action sur `geves.test` ; `curl` (statut HTTP + `grep`) est un contrôle
rapide fiable.

## Workflow Git

- On travaille directement sur **`master`** (pas de branches de fonctionnalité sauf
  demande explicite).
- **`git pull --rebase` avant chaque `git push`** (on est plusieurs : évite les merges
  et les conflits).
- ⚠️ **La vérification qui compte est celle d'APRÈS le rebase.** `composer check` lancé
  avant le `pull` ne dit rien de l'état qu'on pousse : le travail d'un autre contributeur
  arrive avec le rebase, et c'est le tout qui doit être vert. Deux erreurs PHPStan sont
  ainsi apparues sur `master` sans qu'aucun des deux côtés ne soit fautif séparément.
- **Pousser vers GitHub à la fin de chaque tâche** - ne pas laisser du travail terminé
  seulement en local. Dépôt privé : `https://github.com/abdoulhaqjr/geves`.
- **Commits en français.** Éviter les heredoc PowerShell dans l'outil Bash.

## Conventions qui comptent

### Vocabulaire : ÉVÉNEMENT / SÉRIE / TYPE (ex-édition / ex-colloque)

Depuis la refonte « événements typés », le modèle a **deux niveaux** :

- **`App\Models\EventSeries`** (table `event_series`, ex-`Colloque`) = la **série
  récurrente**. Elle **porte le TYPE** : colonne `type` + enum **`App\Enums\EventType`**
  (`colloque` / `seminaire` / `journees_scientifiques` / `autre`, défaut `colloque`).
- **`App\Models\Event`** (table `events`, FK `event_id`, ex-`Edition`) = l'**« événement »**
  daté, le hub opérationnel (participants, soumissions, paiements, programme, documents…).
  Relation **`Event::series()`** (FK **explicite** `event_series_id` - le nom de méthode
  « series » ferait sinon déduire `series_id`) ; inverse **`EventSeries::events()`**.
  L'**accesseur `Event::type()`** renvoie le type hérité de la série (défaut `colloque`).
  Pour un lien vers sa page publique, utiliser **`Event::publicPageLabel()`** : le
  libellé accorde colloque, séminaire et journée(s) scientifique(s). Pour ces
  dernières, le titre/libellé de l'édition prime sur les dates, puis sur la série.

- **Le type se choisit dans la fiche de l'événement**, bloc *Série de rattachement*
  (onglet Général) : sélecteur **`series_type`** (`admin/evenements/form.blade.php`),
  persisté par `SaveEvent` (création d'une série OU mise à jour de la série choisie).
  Toutes les éditions d'une série héritent du type. ⚠️ Pas encore d'écran dédié de
  gestion des séries (phase 2 possible).
- ⚠️ **Classes support / contrôleurs / factories / vues renommés** : `Edition*`→`Event*`,
  `Colloque*`→`EventSeries*`, `CurrentColloque`→`CurrentEvent`, dossier de vues
  `admin/evenements`, noms de routes `admin.evenements.*`. UI en français : « événement »,
  « série », « type ».
- ⚠️ **Exceptions ASSUMÉES qui gardent « edition/colloque » (NE PAS renommer)** : la
  couche RBAC par édition - capacités **`editions_read`/`editions_write`**, alias
  middleware **`edition.cap`**, **`EnsureEditionAccess`**, **`ScopesEditionAccess`**,
  **`User::canAnyEdition()`/`canOnEdition()`**, super-capacité **`manage_colloques`** ; la
  **route publique `/colloque/{slug}`** (`public.colloque`) et le mot « colloque » comme
  **libellé d'affichage / valeur de type** ; les **jetons du concepteur** `[edition.name]`
  / `[edition.theme]` / `[edition.dates]` (`DocumentVars`) ; le champ de merge e-mail
  `{{edition_name}}`. Ce sont des clés/identifiants, pas de l'affichage « édition ».
- ⚠️ **Relations imbriquées** : charger via `event.series` et `submission.event` (jamais
  `edition.colloque` / `submission.edition`, qui n'existent plus).

### Nom de la plateforme = dynamique

Ne **jamais** écrire un littéral « Gesco »/« GEVES » dans l'UI. Le nom d'affichage est
**`config('app.name')`**, surchargeable via **Réglages** (clé `app_name`). Pour les
traductions, utiliser le placeholder **`:app`**. (Certaines classes historiques gardent
le nom `Gesco` : `AskGesco`, `GescoController` - c'est du code, pas de l'affichage.)

### Charte & design system

- Source unique dans `resources/css/ukc.css` (tokens `--ukc-*`, composants `.ukc-*` :
  `.ukc-btn--primary|green|gold|outline|ghost|danger`, `.ukc-card`, `.ukc-pill--ok|warn|
  info|muted|err`, `.ukc-table`, `.ukc-field`, `.ukc-steps`, shell `.ukc-shell/-sidebar/
  -topbar`). Préférer ces classes aux littéraux.
- **Icônes = SVG uniquement** via `<x-ukc.icon name="…" :size="18" />`. **Jamais
  d'emoji dans l'UI** (exception assumée : les e-mails).
- ⚠️⚠️ **UN LIEN QUI N'EST PAS `.ukc-btn` HÉRITE SA COULEUR.** La règle
  **`.ukc-scope a:not(.ukc-btn) { color: inherit }`** a une spécificité de
  **(0,2,1)** - `:not()` compte son argument. Tout sélecteur à **deux classes**
  (`.ukc-shell .mon-bouton`) y perd, la déclaration de couleur est ignorée, et un
  lien posé sur un fond sombre sort **blanc sur blanc, texte invisible**. Le piège
  a été payé **deux fois** : sur le bouton du bandeau de Prise en main, puis sur
  les boutons du tableau de bord participant. Remède : nommer l'élément **et** le
  bloc (`.ukc-shell .tdb a.tdb-btn-light`, soit (0,3,1)) - ou donner au lien la
  classe `.ukc-btn`. Se vérifie en lisant la **couleur calculée**, pas à l'œil :
  `getComputedStyle(el).color`.
- **Trois caractères proscrits, un seul remède : le tiret simple `-`.** Le
  **point médian U+00B7** séparait (« Chrome - Windows »), le **demi-cadratin
  U+2013** marquait les plages (« 08:30 - 11:30 »), le **cadratin U+2014**
  coupait les incises et titrait les sections. On ne les tape pas au clavier :
  ils arrivent par copier-coller et repartent en mojibake dès qu'un maillon perd
  l'UTF-8. La liste vit dans `caracteresProscrits()`
  (`tests/Feature/TypographieTest.php`), qui balaie les sources, le HTML rendu
  **et** `lang/*.json`.
  - ⚠️ **Ce n'est pas qu'une affaire d'allure.** Un cadratin recopié depuis Word
    dans l'expertise d'un relecteur faisait échouer l'égalité stricte **en
    silence** : plus aucun appariement par axe. D'où `ReviewerExpertise::axisKey()`,
    qui normalise toute la famille des tirets typographiques.
  - ⚠️ **Trois tolérances, toutes BORNÉES** - par région *et* par caractère ;
    aucun blanc-seing de fichier :
    1. le heredoc `RESPONSES_JSON` du seeder Eau-Togo (résumés et affiliations
       **tels que les auteurs les ont tapés**, « Abomey - Calavi » compris) ;
    2. la **clé** de la table de correspondance de
       `fix_document_template_typography` (elle transcrit le texte déjà en base
       qu'il faut retrouver ; sa valeur, elle, porte bien un tiret simple) ;
    3. `AutoAssignReviewersTest`, **pour le cadratin seul** : son jeu d'essai
       doit en porter un, sinon le cas compare deux chaînes identiques.
    Un cas dédié vérifie que chaque tolérance **sert encore** : fichier disparu,
    heredoc renommé ou caractère parti, elle doit être retirée.
  - ⚠️ **La règle porte sur le CODE, pas sur les données.** Ce qu'une personne
    saisit lui appartient et traverse la plateforme tel quel.
  - ⚠️ **Trois emplois ne sont pas des séparateurs** et ne deviennent pas `-` :
    l'**écriture inclusive** (le point médian entre « Invité » et « e »), qui
    prend la forme parenthésée du projet - « Invité(e) », déjà employée par
    `Co-auteur(s)` ; la **multiplication** (« 0,55 fois la taille ») ; et le
    caractère **cité comme sujet du propos** dans un commentaire, qu'on nomme en
    toutes lettres plutôt que de le remplacer - sinon la phrase « stocké en X
    alors que l'événement dit X » perd son bug, et le journal dit l'inverse de
    ce qui s'est passé.
  - ⚠️ **Une liste dont les éléments portent déjà un tiret se joint par une
    virgule**, pas par `-`. Mesuré : 30 intitulés de séance sur 30 (« Session -
    Atelier 1 - Sciences… »), 6 axes sur 12, et surtout « Enseignant » et
    « Chercheur », deux grades distincts que « Enseignant - Chercheur » soude en
    un seul à la lecture. Concerne `implode()` / `join()` sur des listes
    d'éléments de même nature, pas les paires de champs différents
    (`rôle - affiliation`), qui restent au tiret.
- Charte UK : bleu `#223ea4`, or `#ffd700`, vert `#009933`, police **Jost**.
- **Dégradé des héros publics = un seul token `--ukc-hero`** (`ukc.css`, bleu-foncé →
  bleu → `--ukc-blue-bright`). Tous les héros publics (accueil, pages internes, colloque,
  live, auth) l'utilisent : **ne jamais recopier un dégradé bleu en dur** dans un héros.
  Proscrire le navy quasi-noir `#0f1a33`/`#0f1f5c` sur le public (fonds pleins / voiles →
  `--ukc-hero-navy` `#14205a`). Les reflets or/vert viennent des calques `::before`.

### Kit Tailwise (back-office & espace participant)

Les en-têtes des layouts admin et participant restent visibles au défilement :
`.ukc-admin-head` (enfant direct de `.ukc-main`, dans `tailwise.css`) et
`.ukc-topbar` (dans `ukc.css`) sont collés en haut. Ne pas appliquer cette règle
aux en-têtes internes des formulaires.

Le **back-office admin ET l'espace participant** sont habillés avec le kit dashboard
**Tailwise** (bundle ThemeForest acheté, dans `Downloads/…/Tailwise Bundle`). ⚠️ On
**n'utilise pas** la chaîne d'outils Tailwind v3 du kit : Tailwise est adopté comme
**système de design dans le pipeline Tailwind v4 de GEVES**, via
`resources/css/tailwise.css` (importé en dernier), **100 % scopé sous `.ukc-shell`**
(police Public Sans, cartes `.box`, sidebar, KPI, etc.).

- ⚠️ **Le public reste sur la charte UK** (fidèle aux maquettes) - **ne JAMAIS**
  re-skinner le public avec Tailwise (le public n'a pas `.ukc-shell`).
- Composants Tailwise déjà portés et **à réutiliser** côté back-office : menu latéral
  rétractable à icônes, topbar + **palette ⌘K** (`<x-ukc.command-palette>`,
  `App\Support\AdminPalette`), widgets stat à **tendance réelle** (`<x-ukc.trend>`,
  `App\Support\Trend`), **tri par en-tête** (`<x-ukc.th>`, `App\Support\ListSort` - liste
  blanche anti-injection), **Tom Select**, **slideover** (`<x-ukc.slideover>`),
  **filedrop** (`<x-ukc.filedrop>`), **FullCalendar v6** (⚠️ pas v7 : classes CSS hachées).
- Éditeurs de documents (badge/template) = **pages autonomes hors `.ukc-shell`**
  (overlay `resources/css/designer-tailwise.css`, tokens surchargés via `html:root`).
- Vérif visuelle : viewport ≥1024px pour voir le menu déployé (reset `desktop` après).
- ⚠️ **Catégories du menu latéral** (`components/layouts/admin.blade.php`, `<span class="nav-label">`),
  chacune masquée si aucun de ses liens n'est accessible : **Scientifique** (Événements,
  Soumissions, Programme, Formations) - **Gestion** (Participants, Paiements, Secrétariat,
  Documents) - **Communication** (E-mails, Annonces, Témoignages, Réclamations) - **Pilotage**
  (Projet, Rapports, Assistant IA) - **Système** (Utilisateurs, Réglages). En ajoutant un lien,
  le ranger dans la bonne catégorie **et** inclure sa capacité dans la garde du `nav-label` du
  groupe (sinon un label vide peut apparaître).
- ⚠️ **Même règle côté participant** (`components/layouts/participant.blade.php`) : **Tableau
  de bord** hors catégorie, puis **Ma participation** (Soumissions, Inscription & paiement,
  Formations, Documents) - **Mes fonctions** (modérer, rapporter, relire ; seulement pour qui
  en porte) - **L'événement** (Page de l'événement, Programme) - **La plateforme**
  (Déroulement, Archives, **Témoignage**, Accueil, Assistante) - **Mon compte** (Mon compte,
  Prise en main).
  - ⚠️ **« Témoignage » est dans « La plateforme », pas dans « Ma participation »** : il
    alimente la page d'accueil, il ne fait pas partie du dossier de participation. Et le
    libellé du menu est « Témoignage » tout court - le « Mon » reste au titre de la page.
  - ⚠️ **« Déroulement » appartient à « La plateforme », pas à « L'événement »** : sa route
    est globale (`public.deroulement`) et reste utile sans événement suivi. C'est ce qui
    permet à « L'événement » de partager la garde `@if ($colloqueEdition)` de ses deux liens
    et de disparaître ENTIÈRE, sans laisser un lien orphelin sous un intitulé masqué.
  - ⚠️ **Icônes toutes distinctes dans un même menu** : replié (`ukc-compact`), l'icône est
    tout ce qui reste d'un lien ; deux identiques rendent deux entrées indiscernables.
  - Garde : `MenuParticipantTest` (ordre des catégories, **composition de chacune**, aucun
    intitulé sans lien, « L'événement » masqué sans événement, aucune icône répétée,
    vocabulaire, traductions). ⚠️ Le cas de **composition** est celui qui tient le rangement :
    sans lui, déplacer un lien d'une catégorie à l'autre passe au vert. ⚠️ Son jeu d'essai
    active le module formations ET publie une formation, sinon le menu rendu n'est pas le menu
    complet. ⚠️ Le cas du vocabulaire éprouve le **texte visible**, pas le HTML : la route
    `/colloque/{slug}` apparaît dans chaque `href` et reste délibérément conservée.

### Composants transverses à réutiliser

- **Téléphone** : tout champ numéro de téléphone = `<x-ukc.phone>` (intl-tel-input,
  drapeau + indicatif, valeur **E.164** à la soumission).
- **Résumé de soumission** : CKEditor 5 (WYSIWYG, comptage en **mots**, `licenseKey:
  'GPL'`, texte justifié). **Mots-clés** : **Tom Select** (`data-tom data-tom-create
  data-tom-max`). Affichage d'un résumé stocké : **`App\Support\Html::render()`** (HTML
  assaini, jamais `strip_tags` brut).
- **Affiliations (autocomplétion)** : API libre **ROR** (`App\Support\RorClient`, api.ror.org, sans clé, cache + fail-closed) via l'endpoint `account.affiliations` (repli local) ; le wizard alimente un `<datalist>` en direct. Ne pas revenir à un datalist statique alimenté par la base.
- **Grades de participant** : liste unique **`App\Support\ParticipantTypes::all()`**,
  désormais **éditable dans Réglages** (clé `participant_types`, un grade par ligne ;
  `ParticipantTypes::DEFAULTS` sert de point de départ et de repli - la liste n'est
  jamais vide, sinon l'inscription serait bloquée). ⚠️ **Ne jamais redéclarer une
  liste de grades ailleurs** : le formulaire d'inscription l'avait fait, et les grades
  ainsi créés (« Doctorant / étudiant ») ne correspondaient à aucune catégorie
  tarifaire - le montant restait « à préciser ». Un test de garde
  (`PaymentRightsGuardTest`) échoue si une liste parallèle réapparaît.
  (profil + auteur correspondant). Le **grade est capturé par édition** sur le
  participant (`participant.title`) → une personne peut avoir des grades différents au
  fil des colloques ; ne pas le lire uniquement depuis le profil global.
- **Civilités** : liste unique **`App\Support\Civilities::all()`** = **`M.` / `Mme` /
  `Mlle`** (inscription, profil participant, profil staff, fabrique). ⚠️ **Ne pas la
  redéclarer** : trois contrôleurs la recopiaient et divergeaient dans l'ordre. La
  civilité reste **libre en base** (`max:20`, pas de `in:`) : d'anciennes valeurs
  (« Dr », « Pr ») restent valides. Le **titre académique** se capte ailleurs (grade,
  `trainer.title` des formateurs - datalist Pr./Dr. distincte, ne pas confondre).
- **Wizard de soumission** (`resources/js/app.js` → `submissionWizard`) : blocage
  d'étape (`validateStep`), erreurs en **toast** (`window.ukcToast`), auteur
  correspondant éditable (identité forcée côté serveur quand « self », cf.
  `StoreSubmissionRequest::prepareForValidation`). L'auteur peut corriger son résumé en
  place tant que `Submission::isAuthorEditable()` (aucune relecture) - sinon
  resoumission officielle (`canBeRevised`).
- **Liaison auteurs ↔ comptes** : table `submission_authors` (`Submission::authorLinks()`),
  moteur `App\Support\AuthorMatcher` (fort = e-mail/téléphone ; faible = nom+affiliation,
  IA en renfort, **déclinable**), action `LinkSubmissionAuthors` (appelée par
  `CreateSubmission` : lie + notifie `authorship`/`AuthorLinkedMail`/SMS best-effort, ou
  invite via `AuthorInviteMail`), listener `LinkPendingAuthorships` (auto-liaison à
  l'inscription). Une communication liée apparaît dans « Mes communications » de chaque
  auteur ; seul le déposant (`$isOwner`) peut la modifier.
- **Confirmations** : `window.ukcConfirm(msg, opts)` (popup Tailwise, Promise<boolean>) via l'attribut **`data-confirm`** sur le bouton (ou le formulaire) - **jamais** `onclick="return confirm()"`. Le handler `data-ajax` transmet `e.submitter` à `FormData` (sinon la valeur du bouton cliqué, ex. `status`, est perdue → 400).
- **Pagination + tri d'un tableau = CÔTÉ SERVEUR** (patron unique de tout le back-office,
  cf. `admin/soumissions`) : le contrôleur `->paginate(N)->withQueryString()` (rendu par
  `resources/views/vendor/pagination/ukc.blade.php`, `{{ $page->links() }}`) et **`ListSort`**
  (liste blanche anti-injection) + en-têtes **`<x-ukc.th sort="…" :current :dir>`** pour le
  tri. Les filtres = **formulaire GET**, **en AJAX** (voir ci-dessous), pas de filtre client
  sur une liste paginée (il ne verrait que la page affichée). Utilisé : Soumissions (liste,
  **Attribution auto**), Programme > **Sessions** et **Créneaux** (20/page).
  - ⚠️ **Une barre de filtres NE RECHARGE PLUS LA PAGE** (`data-filter`, cf. `app.js`). Un
    champ de recherche obligeait à **taper PUIS valider** (`onkeydown` sur Entrée), et chaque
    essai rechargeait tout : focus perdu, position de défilement perdue, attente. Or chercher,
    c'est tâtonner - « aw », « awa », « awa k ». La saisie filtre **à la frappe** (pause de
    350 ms), les listes déroulantes et les dates filtrent **net**.
    - Le formulaire porte **`data-filter="<groupe>"`**, et chaque région qui dépend des
      filtres porte **`data-filter-part="<groupe>-<nom>"`** : tableau, pagination, barre
      d'export, **et le lien « Réinitialiser »** (il apparaît selon les filtres, donc il a sa
      propre région - sinon il ne se montrerait qu'au prochain rechargement complet).
    - ⚠️ **Le formulaire reste HORS des régions échangées** : le remplacer sous les doigts de
      qui tape lui prendrait le focus et replacerait le curseur au début.
    - ⚠️ **Le groupe est un PRÉFIXE, et il est obligatoire** : les onglets de Programme
      portent **deux** barres de filtres dans le même document (Sessions et Créneaux, l'une
      masquée). Sans cloisonnement, filtrer les créneaux réécrirait aussi le tableau des
      sessions, avec les filtres par défaut de celles-ci.
    - ⚠️ **Jamais `onchange="this.form.submit()"`** : `form.submit()` n'émet **aucun**
      évènement `submit`, donc il contourne l'interception et recharge la page. Un test de
      garde (`ListFilterAjaxTest`) scanne les vues et échoue si le motif revient.
    - ⚠️ **Les filtres restent dans l'URL** (`replaceState`, pas `pushState` : une entrée
      d'historique par caractère frappé rendrait le bouton « précédent » inutilisable) : une
      liste filtrée se partage toujours par lien, et la pagination échangée porte les bons
      liens. Les valeurs **vides** sont retirées de l'URL.
    - ⚠️ **Réponses dans le désordre** : on tape plus vite que le réseau ne répond. Un jeton
      de fraîcheur écarte la réponse d'un filtre déjà abandonné - sinon la liste afficherait
      un résultat qui ne correspond pas au texte visible dans la barre.
    - Côté serveur, **rien de spécial** : c'est la même page, rendue par le même contrôleur,
      dont on ne reprend que les régions. Un rendu partiel dédié finirait par diverger.
  - ⚠️ **Deux tableaux triables/paginés sur une même page** (Programme > Sessions **et**
    Créneaux vivent dans une seule vue, onglets en `?tab=`) : chacun a ses paramètres
    préfixés (`s_sort`/`s_dir`/`s_page`, `c_sort`/`c_dir`/`c_page`) ; `<x-ukc.th>` et
    `ListSort::resolve()` prennent des noms de paramètres personnalisés (`param`/`dirParam`/
    `pageParam`). Le board Planning garde les collections **complètes** ; les onglets liste
    filtrent/trient/paginent ces collections **en mémoire** via `ProgramController::sessionsTable()`
    / `slotsTable()` + `paginateCollection()` (pas de requête dédiée).
    ⚠️ **`Collection::get()` est typé NON-NUL** par l'extension, alors qu'il rend
    bien `null` pour une clé absente : `?->prop ?? …` y déclenche
    `nullsafe.neverNull`. Passer par **`data_get()`** (cf. `BadgeCatalog`).
    ⚠️ Larastan croit les
    dates castées et relations **non-nulles** : dans un `sortBy` par clé, passer par
    `optional()`/`data_get()` (elles sont bien nullables au runtime : séance sans horaire,
    plénière sans atelier, créneau libre) - sinon `?->prop ?? …` déclenche `nullsafe.neverNull`.
  - **`tableFilters(keys)`** (Alpine, `app.js`) = filtre **client instantané SANS pagination**
    (lignes `data-filter-row` + `data-<clé>` + `data-text`, gouvernées par
    `x-show="visibleIds.includes(ID)"`). Réservé aux listes **non paginées** (ex. Programme >
    Ateliers, pool « À planifier »). ⚠️ Ne PAS y réintroduire de pagination client
    (`perPage`/`pageIds`/`<x-ukc.pager>`) : c'était une divergence du reste du projet, retirée
    au profit de la pagination serveur ci-dessus.
- **Créneaux : heure de fin** figée à la création/modif (`Slot::ends_at`) d'après la durée
  présumée (`slotMinutes()` : `poster_min` pour un poster, sinon `oral_min`) - `generateSlots`
  la posait déjà ; `storeSlot`/`updateSlot` la posent désormais aussi. La liste des créneaux
  affiche **« début - fin »** (repli sur `oral_min` pour d'anciens créneaux sans `ends_at`).
- **Config runtime** : mail/paiements/IA/visio/SMS/nom sont surchargés **depuis la base**
  (`AppServiceProvider::applySettingsToConfig()`), pas le `.env`.
- **Passerelle de paiement par édition** : le gestionnaire/co-gestionnaire peut renseigner
  les identifiants de passerelle (FedaPay + passerelle par défaut) pour **son
  édition** via l'onglet **Paiement** du formulaire d'édition (`admin.editions.payment`,
  cap `editions_write`). Stockés dans `edition.settings['payment']` (seules les valeurs
  **remplies** sont conservées). `App\Support\EditionPaymentConfig::apply($edition)`
  surcharge `payments.default` + `services.fedapay.*` au runtime, **repli sur la
  config globale** quand un champ est vide. Appelé en tête de chaque parcours de paiement
  (Account/Public/Formation `show`+`pay`, `PaymentController::return` avec
  `$payment->edition`). Pour essayer plusieurs configurations d'affilée, utiliser
  **`EditionPaymentConfig::using($edition, fn () => …)`** qui **restaure** la config
  ensuite (sinon les identifiants d'un candidat fuient sur le suivant).
- **Webhooks de paiement (à ne pas casser)** : la signature doit être vérifiée **avant** de
  savoir à quelle édition le paiement se rattache. `PaymentController::webhook` essaie donc
  la config globale **puis chaque édition** ayant ses propres identifiants
  (`EditionPaymentConfig::candidates()`), chaque essai isolé par `using()`. Garde
  d'étanchéité : **le marchand d'une édition ne peut confirmer que SES paiements**. URL
  générique `/webhooks/paiement/{gateway}` **ou** dédiée
  `/webhooks/paiement/{gateway}/{edition}` (affichée + copiable dans l'onglet Paiement).
  Toujours **fail-closed**, et tous les rejets sont journalisés dans le canal de log
  **`payments`** (`storage/logs/payments.log`, 90 j) - c'est la pièce à consulter pour un
  « j'ai payé mais le système dit non ».
  - ⚠️⚠️ **LE `{event}` DE L'URL DÉDIÉE N'EST PLUS LIÉ À UN MODÈLE** (2026-10-29,
    souci FedaPay en production). Lié implicitement, un **id périmé** dans l'URL du
    prestataire - réimport qui change les id, édition supprimée, id saisi à la main -
    faisait répondre **404 EN AMONT du contrôleur** : FedaPay le lit comme un échec de
    livraison et réessaie en boucle (« nous ne parvenons pas à transmettre… »), alors
    que le contrôleur, une fois atteint, **répond toujours 200**. Le paramètre est donc
    typé **`?string`** et résolu à la main (`Event::find`) : s'il ne correspond à rien,
    on **retombe sur `candidates()`** (toutes les configs, la signature restant le juge)
    au lieu de 404. Un id externe obsolète ne doit jamais faire perdre un paiement. La
    forme **générique reste recommandée** (insensible à l'id). ⚠️ Ne pas re-typer
    `{event}` en `Event` (le 404 reviendrait). Garde : `PaymentGatewayTest` « une URL de
    webhook dédiée à une édition INEXISTANTE ne 404 plus et confirme quand même ».
- **Filet de sécurité paiements** : `App\Actions\ReconcilePayments` + `php artisan
  payments:reconcile` (planifié **toutes les 10 min**) revérifient auprès de la passerelle
  les paiements `pending` ayant une `gateway_ref`, et les confirment si réglés - couvre le
  cas « le webhook n'est jamais arrivé ». `ReconcilePayments::settle()` est la **source
  unique** d'application d'un statut vérifié (retour navigateur, webhook, réconciliation).
  Bouton **« Revérifier »** sur un paiement en ligne en attente (`admin.payments.recheck`).

### Le déposant n'est pas toujours l'auteur (2026-10-24)

⚠️⚠️ **TROIS POSITIONS, ET LE CODE N'EN CONNAISSAIT QUE DEUX.** Tout ne demandait
qu'une chose - « ce compte a-t-il **déposé** ? » - et rangeait tout le reste en
**co-auteur**. Or un secrétariat dépose pour une équipe : l'**auteur
correspondant**, quand il créait enfin son compte, s'affichait alors en
**co-auteur de sa propre communication** (« 0 déposée - 1 en co-auteur » sur le
tableau de bord, « Vous co-présentez » sur son propre passage), lisait « Seul le
déposant peut la modifier » sur sa fiche, et n'avait **aucun moyen de répondre à
une demande de correction** - alors que c'est précisément son travail.

Source unique, trois prédicats sur `Submission` :

- **`wasDepositedBy()`** - c'est lui qui a déposé (le `participant_id` de la
  communication) ;
- **`isCorrespondingAuthor()`** - il porte la communication, rattaché par
  `submission_authors` (`role = 'corresponding'`, `status = 'linked'`) ;
- **`isEditableBy()`** - l'un **ou** l'autre peut la modifier.

⚠️ **LES DEUX, et pas l'un à la place de l'autre.** C'est l'auteur correspondant
qui doit répondre au comité, mais il peut très bien ne **jamais** créer de
compte : le déposant garde donc la main, sans quoi une communication déposée par
un secrétariat deviendrait incorrigible le jour où personne ne se connecte.

⚠️ **`status = 'linked'` est obligatoire** : une invitation en attente ou
déclinée n'ouvre rien. Un rattachement proposé n'est pas un rattachement accepté.

⚠️ **La relation est lue CHARGÉE quand elle l'est** (`relationLoaded('authorLinks')`) :
le tableau de bord pose la question sur chaque ligne de la liste, et une requête
par ligne y ferait un N+1 silencieux - d'où le `with(['event', 'authorLinks'])`
de `ParticipantDashboard::communications()`.

⚠️ **L'indicateur compte TROIS parts, pas deux** (« N déposée - N en auteur
correspondant - N en co-auteur », celles à zéro masquées) : opposer « déposée » à
« en co-auteur » ne laissait aucune place à celui qui porte une communication
qu'il n'a pas déposée.

⚠️ `authorizeOwnership()` est le point de passage des **six** actions d'écriture
(correction, resoumission, réponse aux évaluateurs, choix de créneau, mode de
présentation, modification libre) : la règle s'y pose une fois.

Gardes : `AuteurCorrespondantTest` (9 cas, **4 sabotages vérifiés**).

### S'inscrire sans présenter de communication (2026-10-24)

**Le participant simple a enfin une porte.** On ne devenait `Participant` qu'en
déposant un résumé, en étant rapproché comme co-auteur, en s'inscrivant à une
formation, ou en étant saisi au guichet (`/admin/paiements/creer`, qui crée déjà
la fiche en `role => 'attendee'`). Celui qui vient **seulement écouter** n'avait
aucun chemin - et `PaymentController::pay()` exigeait de toute façon une
**communication acceptée**, donc il n'aurait rien pu régler. Le rôle
« Participant simple » existait pourtant déjà dans `Participant::ROLES`, et la
page publique promettait noir sur blanc de pouvoir « s'inscrire comme
participant simple » pendant que la page d'inscription renvoyait vers
« contactez l'organisation ».

- **OÙ** : sur la page **« Inscription & paiement »** qui existait déjà
  (`/compte/paiement/{event}`), là où le montant s'affiche ensuite.
  ⚠️ **Pas de second formulaire ailleurs** : ce serait un second chemin de
  création de participant, et c'est exactement ce qui avait produit **deux
  fiches pour une même personne** sur le parcours co-auteur. Le bouton
  « S'inscrire » de la page publique y mène directement ; un visiteur non
  connecté est ramené par `auth:account` puis `redirect()->intended()`.
- **QUAND** : `Event::registrationIsOpen()`, une fenêtre **propre aux
  inscriptions** (`settings.registration_open` + `registration_deadline`, carte
  « Inscriptions » de l'onglet Soumissions & tarifs).
  ⚠️ **Surtout pas `submission_open`** : l'appel à communications ferme des mois
  avant l'événement, les inscriptions courent jusqu'au jour même - on s'inscrit
  sur place le matin. C'est l'**archivage** qui ferme, pas l'horloge.
  ⚠️ **Ouvert par DÉFAUT** : le réglage n'existait pas, et tous les événements
  déjà en base ont un `settings` sans cette clé - lu à faux, tout naîtrait éteint.
  ⚠️ La case porte un `<input type="hidden" value="0">` devant elle : sans lui,
  décocher n'envoie rien et on ne distingue plus « fermé » de « champ absent ».
- **COMMENT** : `App\Actions\RegisterAttendee`, qui **délègue la création** à
  `UpsertParticipantForAccount` - seul endroit qui fabrique une fiche, et qui
  **ne rétrograde pas un rôle fort** (un évaluateur qui s'inscrit reste
  évaluateur). Le formulaire ne demande que trois choses, dont une seule décide :
  le **grade**, qui résout le tarif (`Fees::resolveAmount()`) ; l'affiliation et
  le mode de participation sont pré-remplis depuis le profil.

⚠️⚠️ **QUI DOIT DES FRAIS ? PAS « QUI A UNE COMMUNICATION ACCEPTÉE ».** Le
paiement était gardé par `hasAcceptedSubmission()` seul. L'invariant est
**`Participant::owesParticipationFees()`** : on doit des frais quand une
**communication a été retenue**, ou quand on **s'est inscrit ET que
l'organisation a ouvert le paiement aux participants simples**.

⚠️⚠️ **S'INSCRIRE ET PAYER SONT DEUX MOMENTS, ET ILS ONT CHACUN LEUR RÉGLAGE.**
L'usage est d'ouvrir le paiement des participants simples **après la clôture des
dépôts** : jusque-là, l'organisation ne sait pas encore combien de communicants
seront retenus. D'où **`Event::attendeePaymentIsOpen()`**
(`settings.attendee_payment_open`, case « Ouvrir le paiement aux participants
simples », carte Inscriptions).

- ⚠️ **Fermé par DÉFAUT**, contrairement à la fenêtre d'inscription : ouvrir un
  encaissement est un geste délibéré, pas un état qu'on subit.
- ⚠️ **Le communicant n'en dépend PAS** : sa décision est tombée, ses frais sont
  dus. L'y soumettre bloquerait le parcours qui marchait déjà.
- ⚠️ **Et on le DIT à l'intéressé** : inscrit mais paiement fermé, il lit
  « Votre inscription est enregistrée » et la raison - le message générique lui
  parlait d'une communication à faire accepter, ce qui ne le concerne pas. Le
  formulaire d'inscription annonce déjà la suite telle qu'elle sera, plutôt que
  de promettre un paiement « ensuite » qui n'ouvrira qu'après la clôture.
- ⚠️⚠️ **ET ON LE PRÉVIENT QUAND ÇA OUVRE** (`NotifyAttendeePaymentOpen`, genre
  de notification `payment_open`) : sans ce message, « plus tard » voudrait dire
  « revenez voir tous les jours ».
  - ⚠️ **C'est une BASCULE qu'on guette, pas un état.** La fiche d'événement
    poste TOUS ses onglets à chaque enregistrement : lu sur l'état courant, le
    message repartirait à chaque sauvegarde. L'ancien état se lit donc **avant**
    l'écriture, dans `SaveEvent`.
  - ⚠️ **L'envoi vit dans l'action qu'un humain déclenche**, jamais dans un
    évènement de modèle - un seed ou un rejeu idempotent enverrait des centaines
    de messages. Même règle que `NotifySlotRescheduled`.
  - ⚠️ **On ne prévient que ceux que ça concerne** : inscrits, pas encore
    réglés, rattachés à un compte. Un **communicant accepté en est exclu** - ses
    frais étaient dus dès l'acceptation, et lui annoncer une « ouverture »
    sèmerait le doute sur ce qu'il a déjà réglé.
  - ⚠️ **Le montant n'est annoncé que si la grille n'en porte qu'un** : il se
    déduit du grade, donc il diffère d'une personne à l'autre. Annoncer le tarif
    du voisin serait pire que de n'en annoncer aucun.
  - ⚠️ `hasMail => false` sur le genre : il n'y a **pas** de Mailable dédié,
    c'est l'e-mail générique de la notification qui part. Posé à `true`, la
    notification supprimerait son propre envoi en attendant un Mailable qui
    n'existe pas - et personne ne serait prévenu.

⚠️⚠️ **ET L'INSCRIPTION EST UNE DATE, PAS UN RÔLE** (`meta['registered_at']`,
`isRegisteredAttendee()`). Un participant simple qui dépose ensuite un résumé
passe en `author` : lu sur le rôle, il perdrait d'un coup le droit de régler
l'inscription qu'il venait de prendre, et jusqu'à l'acceptation de son résumé.
Une inscription est un fait daté ; elle ne se défait pas parce qu'on a changé de
casquette. Et la date **ne se repousse pas** à une seconde inscription.

⚠️ **Piège de test mesuré ici** : `toIso8601String()` est précis à la SECONDE.
Enchaînés, deux appels tombent dans la même seconde, et une écriture
systématique rend **exactement la même chaîne** qu'une écriture unique - le cas
« ne repousse pas la date » restait VERT avec le `??=` remplacé par une
affectation sèche. Il fait désormais voyager l'horloge (`$this->travel(2)->days()`).

⚠️⚠️ **UN RÔLE SANS LIBELLÉ SE RETOURNE EN ANGLAIS, SANS RIEN CASSER**
(corrigé le 2026-10-24). `Participant::roleLabel()` retombe sur
`ucfirst($role)` : `trainee` et `trainer` - posés par l'inscription à une
formation et par l'ajout d'un formateur - s'affichaient **« Trainee »** et
**« Trainer »**, dans la fiche d'un participant, dans l'export CSV du registre,
dans la répartition par rôle des rapports, et **jusque dans le jeton `{role}`
d'une attestation** ({@see App\Documents\AttestationVars}). Aucune erreur, aucun
test rouge : de l'anglais au milieu d'un document officiel en français.

- ⚠️ **Tout rôle que le code attribue doit avoir sa ligne dans
  `Participant::ROLES`.** `RolesDeParticipantTest` balaie `app/` à la recherche
  de l'argument nommé `role: '…'` - le seul employé pour attribuer un rôle de
  participant, ce qui évite les faux positifs des autres tables qui ont aussi
  une colonne `role` (`event_user`, `submission_authors`, les comités).
- ⚠️ **Le repli `ucfirst()` RESTE** : d'anciennes lignes peuvent porter un rôle
  retiré depuis, et mieux vaut un mot approximatif qu'une colonne vide.
- ⚠️ Ces deux rôles ne disent **rien de la participation à l'événement** : on
  suit un atelier sans s'être inscrit au colloque, et un paiement de formation
  n'ouvre aucun droit ({@see App\Models\Participant::participationPayments()}).

⚠️ **`Participant::PARTICIPATION_ROLES` a été SUPPRIMÉE** (2026-10-24). Elle
listait `author`, `coauthor`, `attendee` sous l'intitulé « rôles affichés dans le
registre des participants » et n'était **lue nulle part**. Son seul effet
mesurable aura été de faire croire - dans cette documentation même - qu'un rôle
absent de la liste disparaissait du registre, ce qui était faux et a envoyé une
correction au mauvais endroit. Un commentaire qui décrit une règle que rien
n'applique est pire qu'un silence : on le croit. Le registre n'a jamais filtré
sur les rôles ; il n'y a donc rien à remettre en place.

Gardes : `InscriptionParticipantSimpleTest` (24 cas, **14 sabotages vérifiés**).

### Tableau de bord participant = il BASCULE sur les dates

`/compte` ne montre pas la même chose du début à la fin du colloque.

- **Une seule définition** : **`EventPhase::participantView()`** → `avant` /
  `pendant` / `apres`, dérivée de **`Event::phase()`** (celle du site public,
  `publicView()`). ⚠️ **Ne jamais recomparer des dates** dans le contrôleur ou la
  vue : deux règles finiraient par se contredire sur le même écran. Elle suit
  aussi l'**archivage explicite**, pas seulement l'horloge.
- **Assemblage dans `App\Support\ParticipantDashboard`** (lecture seule → Blade) :
  le contrôleur ne fait que résoudre l'événement de contexte. Tout chiffre du
  tableau de bord sort de cette classe - c'est ce qui l'empêche de diverger de la
  page qu'il résume.
- ⚠️ **Le mot est « CO-AUTEUR », jamais « co-signataire »** (c'est déjà celui de
  la fiche de soumission et de la colonne « Mon rôle »). Un seul mot par chose.
- ⚠️ **Le compteur de communications inclut celles dont le compte est co-auteur**
  (dépôt **ou** `authorLinks` confirmée), exactement comme
  `/compte/communications` : il annonçait « 1 » là où la page d'à côté en
  listait trois.
- ⚠️ **« Mon prochain passage » retient AUSSI les communications en co-auteur** :
  un co-auteur ne présente pas seul, mais il **co-présente** - il est en salle à
  l'heure dite, et c'est ce qu'il vient chercher. Son rôle est NOMMÉ partout
  (`ParticipantDashboard::roleSur()` → « Je présente » / « Je co-présente »).
  ⚠️ Le créneau porte le `participant_id` du **déposant** : le rôle se lit sur la
  communication, jamais sur le créneau. ⚠️ L'export **`.ics` part des mêmes
  créneaux** (`AgendaController` appelle `passages()`) - un bouton « Ajouter à mon
  agenda » sous un créneau que le fichier n'exporterait pas serait un piège.
- ⚠️ **Ne jamais réclamer ce qui est déjà fait** : l'état vide proposait
  « Compléter mon profil » à tout le monde, profil complet compris. Tout appel à
  l'action de cet écran sort de **`aFaire()`** ; quand la liste est vide, on le
  dit au lieu d'inventer une tâche.
- ⚠️ **Une fonction ne vient pas avec une inscription** : modérateur / rapporteur /
  relecteur sont rapprochés par **e-mail** et peuvent n'avoir aucun `Participant`.
  L'état vide exige donc « ni participation, ni appel ouvert, **ni fonction** ».
- ⚠️ **Les indicateurs portent des CHIFFRES, jamais une coche** : une tâche n'est
  pas une grandeur, elle va dans « À faire », qui ne liste que ce qui reste.
- ⚠️ **`slots.room` / `sessions.room` NOMMENT déjà le lieu** (« Salle 5 », mais
  aussi « Auditorium », « Grand amphithéâtre ») : **ne jamais préfixer par
  « Salle »**, on obtient « Salle Salle 5 » et « Salle Grand amphithéâtre ».
  L'icône `pin` porte le sens. Règle valable sur **tout le site**, écrans comme
  messages et textes générés - tenue par `tests/Feature/SalleLibelleTest.php`
  (**un cas par endroit**, pas par écran : la liste des créneaux au choix ne
  s'affiche que sans créneau attribué) et par `ProgramSlotsTest`.
  - **À l'écran, pas d'étiquette** : la valeur est seule, l'icône ou la colonne
    dit ce que c'est (tableau de bord, `public/deroulement`,
    `account/submissions/show`).
  - **Quand une étiquette est nécessaire, c'est « Lieu »** : suite de champs
    nommés lue par le moteur de rédaction
    (`SecretariatController::programContext()`), ou phrase où un nom générique
    porte l'accord (`ProgramController` : « Lieu « Auditorium » **associé** »).
    ⚠️ Le nom générique doit être **masculin** : avec « Salle », « Auditorium »
    donnait « associée ».
- ⚠️ **« Mon programme » ne dit chaque chose qu'une fois** : la séance qui
  contient un de mes passages n'y figure PAS en plus du passage - à 11h30 on
  lisait quatre lignes pour deux choses, et la ligne du passage nomme déjà sa
  séance et sa salle. Seules les **plénières** s'ajoutent aux passages et aux
  formations.
- ⚠️ **Ne jamais tronquer un intitulé en PHP dans cet écran** : `Str::limit()`
  coupe en plein mot et, joint par un point médian, rend un pavé. La coupe revient au CSS
  (`line-clamp`), qui coupe au bord de la boîte, avec l'intitulé entier dans
  `title`. Vaut pour « Mes axes », un axe par ligne.
- ⚠️ **Aucun dégradé maison** : la carte haute porte **`var(--ukc-hero)`**. Ce
  qui distingue les trois moments, c'est le **libellé, l'icône et les boutons**,
  pas la teinte. Règle valable partout - voir ci-dessous.
- ⚠️ **Une pastille de document ne promet un format que si c'en est un** : le
  badge n'est pas un fichier (il est dessiné sur « Mes documents »), sa pastille
  dit « Voir » et vise l'ancre `#mon-badge`. D'où « Prêts à **retirer** », pas
  « à télécharger ».
- ⚠️ **Le site est BILINGUE : toute chaîne affichée passe par `__()`**, y compris
  celles que `ParticipantDashboard` fabrique (libellés d'indicateurs, pastilles
  de document, étapes du parcours). La refonte en avait posé 145 sans aucune
  traduction : en français rien ne se voyait, en anglais l'écran devenait un
  panachage. Glossaire maison : colloque -> conference, communication -> paper,
  atelier -> workshop, axe -> track, créneau -> slot, attestation -> certificate,
  reçu -> receipt, relecture -> review, résumé -> abstract, passage ->
  presentation, lieu -> venue. Tenu par `TableauDeBordTraduitTest`.
- ⚠️ **`User::missingProfileFields()` rend des libellés FRANÇAIS, et on les
  compare** : `ParticipantDashboard` filtre sur la chaîne `'E-mail vérifié'`.
  Les traduire sans séparer d'abord la clé du libellé casserait ce filtre en
  silence sous une session anglaise.
- Styles sous **`.tdb-*`** (fin de `ukc.css`). Tests : `ParticipantDashboardTest`.

### Un seul dégradé de héros = `--ukc-hero`

Le jeton porte son mode d'emploi dans `ukc.css` : **une seule source pour tous
les héros**, pour les harmoniser et pour écarter la teinte quasi noire
(`#0f1a33`) des anciens dégradés.

- ⚠️ **Un héros ne pose jamais sa propre couleur.** `background: var(--ukc-hero)`
  - accueil, pages internes, direct, prise en main, tableau de bord participant.
  Les reflets or/vert restent apportés par les calques `::before`.
- ⚠️ **UNE exception, voulue : `.admin-dash .edition-banner`.** La bannière du
  tableau de bord admin est la **jonction entre la charte de l'université et le
  bundle Tailwise** : elle part du bleu de la charte et va chercher son vert, là
  où les héros restent dans les bleus. L'arrêt du vert **à 160%** est délibéré -
  au-delà de 100% il n'est jamais atteint en pleine valeur, et ne donne qu'un
  virage en bout de bande. **Ne pas l'« aligner »** : je l'ai fait une fois,
  c'était effacer une intention.
- ⚠️ **`#0f1a33` n'a plus droit à un dégradé** (elle reste permise en aplat,
  c'est le fond de la barre latérale).
- ⚠️ **Pas de repli dans `var(--ukc-hero, …)`** : un repli jamais atteint reste
  un modèle que l'on recopie - celui qui était écrit était une copie de l'ancien
  dégradé.
- **Familles voisines, à ne pas confondre avec un héros** : bandeaux d'appel
  (`.cta-band`, `.ukc-callout`, `.ukc-final-cta`, bleu -> bleu foncé) et
  remplacement d'image (`145deg, #1a3080, #223ea4 55%, #0a7a2f` :
  `.edition-card .cover.no-img`, `.gallery-item.is-more`, le repli de bannière
  de l'accueil). Reprendre l'existant plutôt que d'en écrire un troisième.
- Tenu par **`tests/Feature/DegradeHerosTest.php`**, qui relit les sources : un
  commentaire ne retient personne, trois écrans s'en étaient écartés sans que
  rien ne tombe. La règle vise le **dernier maillon** du sélecteur - le héros
  lui-même, pas l'avatar posé dedans - et elle lit les feuilles **commentaires
  retirés**, sinon un commentaire citant `--ukc-hero` fait passer la règle
  suivante pour un héros.

### Mise en ligne = `DEPLOIEMENT.md`

Les en-têtes HTTP de production sont répartis entre `public/.htaccess`
(protections communes) et `App\Http\Middleware\ContentSecurityPolicy` (HSTS de
30 jours pour GEVES si l'origine est en HTTPS, CSP HTML minimale + Report-Only).
Si `APP_URL` est en HTTPS, `config/session.php` préfixe les cookies de session
avec `__Secure-` et impose l'attribut `Secure`. Le middleware CSRF émet
`__Secure-XSRF-TOKEN` ; `StartSessionWithLegacyCookie` reprend les anciennes
sessions à leur première requête. Le cookie du bandeau suit le même préfixe.
Le bilan `POST /csp-report` est borné et dédupliqué. Voir « En-têtes de sécurité
HTTP » dans `DEPLOIEMENT.md` avant d'augmenter HSTS ou d'activer une CSP complète ;
la visio, Turnstile et le paiement dépendent de permissions/sources externes.

⚠️ **Une modification de CSS seule ne se déploie PAS par `git pull`.**
`public/build/` est dans `.gitignore` : le dépôt porte les sources
(`resources/css/*.css`), pas l'habillage construit. Sur le serveur, `git pull`
rapporte donc le HTML neuf et laisse la feuille de style de la version
précédente. Le site ne tombe pas, il s'affiche de travers : les classes neuves
n'ont aucune règle, la section concernée part en texte brut pendant que le
reste de la page reste normal. Il faut `npm ci && npm run build` sur le
serveur, à chaque version. Vu le 11/10/2026 sur le barème des tarifs.

Le mode opératoire o2switch vit dans **`DEPLOIEMENT.md`**, l'environnement type
dans **`.env.production.example`**. Les pièges qui se paient en ligne :

- ⚠️ **Le `composer.lock` exige PHP ≥ 8.4.1** (Symfony 8.x), bien que
  `composer.json` annonce `^8.3`.
- ⚠️ **`RolesAndAdminSeeder` tourne AUSSI en production.** Il posait le mot de
  passe « password » : en production il lit désormais `ADMIN_EMAIL` /
  `ADMIN_PASSWORD` et **échoue** si le second est vide. Il ne retouche jamais
  un mot de passe déjà changé. Tenu par `SuperAdminSeedTest`.
- ⚠️⚠️ **LES E-MAILS SONT DES JOBS : sans `queue:work`, rien ne part.** Dix des
  onze types d'e-mail sont des tâches de fond (`ShouldQueue`) : l'application
  les écrit en base et considère son travail fini. Le mode opératoire du
  travailleur est en **§F2 de `DEPLOIEMENT.md`** (cron d'une minute,
  `--stop-when-empty`). Pour trancher en trente secondes quand « le mail ne part
  pas » : `DB::table('jobs')->count()` qui monte sans redescendre désigne le
  travailleur ; `php artisan queue:failed` désigne le transport (SMTP, domaine
  d'envoi).
  - ⚠️⚠️ **ET LE JOURNAL `emails` NE DOIT JAMAIS ANNONCER L'ENVOI AVANT LE
    TRANSPORT.** `Mail::to()->send()` sur un mailable `ShouldQueue` ne fait
    qu'**empiler** : `NotifyCoauthorsForPayment` écrivait pourtant sa ligne à
    `sent`, horodatée, dans la foulée - donc le back-office affirmait « envoyé »
    alors que rien n'était parti, **et il le maintenait quand le travailleur
    échouait ensuite**. C'est le pire des deux mondes : l'écran COUVRAIT la
    panne au lieu de la montrer, et c'est ainsi qu'une file à l'arrêt reste
    invisible pendant des semaines. La ligne naît **`queued`** et c'est le
    transport qui la fait passer à `sent` ou `failed`, par `send()` et
    `failed()` sur le mailable (la grammaire que `SubmissionStatusMail` suivait
    déjà). ⚠️ Une panne du **journal** ne doit pas faire échouer un e-mail déjà
    transmis : l'écriture est sous `try` et se contente d'un avertissement.
  - ⚠️ **Une acceptation écrit à TOUT LE MONDE, et c'est vérifié** :
    `SubmissionStatusChanged` déclenche `NotifyAuthorOfStatus` (auteur
    correspondant, `SubmissionStatusMail`) **et** `InviteCoauthorsOnAcceptance`
    (chaque co-auteur, `CoauthorPaymentInvitationMail` - y compris ceux qui
    n'ont pas de compte). Mesuré sur une communication à quatre auteurs : quatre
    e-mails en file. Gardes dans `CoauthorPaymentTest` (3 sabotages vérifiés,
    dont « un seul co-auteur prévenu »).
- ⚠️ **`config/app.php` fixe le fuseau à `UTC` EN DUR** : une `APP_TIMEZONE`
  dans le `.env` ne serait pas lue.

### `env()` ne s'appelle QUE depuis `config/`

⚠️ Dès que `php artisan config:cache` a tourné - la règle en production -
Laravel ne charge plus le `.env`, et **`env()` rend `null` partout ailleurs que
dans les fichiers de `config/`**, qui sont lus au moment de fabriquer le cache.
Un seeder lisait `env('ADMIN_PASSWORD')` : en production il refusait un mot de
passe pourtant bien écrit dans le `.env`, et a bloqué un déploiement. Le
symptôme accuse le fichier, la cause est dans le code. Toute valeur
d'environnement passe par un fichier de configuration
(`config/super-admin.php` pour le compte admin et `SEED_DEMO_DATA`). Tenu par
`EnvHorsConfigTest`, qui balaie `app/`, `database/seeders/`, `routes/` et
`bootstrap/`.

### Migrations = elles tournent sur MySQL, pas seulement sur SQLite

On développe sur SQLite, on déploie sur MySQL. Deux limites n'existent que du
côté MySQL : elles ne se découvrent donc qu'en ligne, au milieu d'un
déploiement. Les deux se sont produites.

- ⚠️ **Créer le nouvel index AVANT de supprimer l'ancien.** MySQL exige que
  chaque clé étrangère reste soutenue par un index dont ses colonnes forment le
  **préfixe**. Une unicité `(question_id, account_id)` soutient donc la clé
  étrangère sur `question_id` : la retirer d'abord échoue en **erreur 1553**
  (« Cannot drop index: needed in a foreign key constraint »). Poser d'abord le
  remplaçant, qui commence par la même colonne, puis retirer l'ancien.
- ⚠️ **64 caractères maximum pour un nom d'index.** Le nom déduit par Laravel
  pour une unicité à trois colonnes peut dépasser ; il en faisait 65 sur
  `meeting_message_reactions`. Passer un **nom explicite** en second argument.
  Tenu par `SchemaPortableMySqlTest`.
- ⚠️ **Une migration doit pouvoir être REJOUÉE.** Celle qui a échoué l'avait
  fait à mi-chemin, colonne déjà ajoutée : sans garde (`Schema::hasIndex`,
  `Schema::hasColumn`), la reprise rejoue un geste déjà fait et échoue à son
  tour.

### Fichiers stockés = `<type>/<nom-de-l-evenement-annee>/`

Source unique : **`App\Support\EventStorage`**. Les pièces jointes vivaient sous
l'**identifiant** de l'événement (`submissions/2/…`, `events/2/…`) et une partie
de l'habillage à plat dans `events/` : un dossier nommé `2` n'apprend rien à qui
ouvre le disque, et deux événements mêlaient leurs visuels dans le même
répertoire.

⚠️⚠️ **LA « VERSION DÉFINITIVE » (camera-ready) EST SORTIE DU PROCESSUS**
(18/10/2026) : la **demande de correction** la remplace, et l'équipe ne dépose
plus de « prête à publier ». Le dépôt, sa liste de contrôle, sa route, son action,
ses accesseurs de modèle et ses deux encarts - côté participant **et** côté
back-office - ont été retirés, plutôt que laissés dormants : du code qu'aucun
chemin n'emprunte se périme sans qu'on s'en aperçoive.
⚠️ **Mais la migration continue de déplacer les fichiers `camera-ready/` déjà
déposés**, et le dossier reste décrit ci-dessous : un fichier qu'on cesse de
suivre est un fichier perdu, et c'est en production qu'on s'en apercevrait.
⚠️ `Submission::isAuthorEditable()` ne regarde donc plus que deux conditions -
statut initial et aucune relecture.

**Cinq racines sont rangées par événement** (`EventStorage::ROOTS`) :
`submissions/` (pièces jointes, plus `camera-ready/` en dessous - **plus alimenté**,
conservé pour l'existant), `events/`
(bandeau, bannière, vidéo de fond, logos de partenaires), `formations/` (flyer),
`livret/` (logo de couverture) et `livret-templates/` (gabarits .docx importés).

- ⚠️ **Le nom vient du SLUG, pas du titre** : c'est déjà l'identifiant public
  (`/colloque/{slug}`), il est **unique en base** - donc deux événements ne
  peuvent pas partager un dossier - et déjà normalisé, ce qu'un titre n'est pas
  (« VIIIe Colloque - Innover pour un avenir durable » ferait un répertoire de
  48 caractères).
- ⚠️ **L'année n'est ajoutée que si le nom ne la porte pas déjà.** Les deux
  colloques de la base la portent dans leur slug ; ajouter `year` sans regarder
  donnait `avenir-durable-2025-2026`, deux millésimes contradictoires dans le
  même nom - la colonne dit 2026 là où le slug dit 2025.
- ⚠️ **Jamais de segment VIDE** : il écrirait dans le dossier parent, et tous les
  événements mêleraient leurs fichiers. Repli sur le titre, puis sur
  l'identifiant, qui existe toujours.
- ⚠️ **Un chemin déjà enregistré ne se recalcule JAMAIS à la lecture.** La base
  garde l'adresse du fichier et c'est elle qu'on relit. Le slug étant
  **modifiable**, un dossier déduit après un renommage pointerait dans le vide :
  les anciens fichiers restent où ils sont, les nouveaux vont dans le nouveau
  dossier, et rien ne casse.
- ⚠️ **À la CRÉATION d'un événement, la ligne n'existe pas encore** quand
  l'habillage est téléversé : `SaveEvent` résout donc le slug **avant** l'appel à
  `EventArtwork`, et passe le dossier en argument (`dir:`). `EventArtwork` ne
  déduit plus le dossier lui-même.
- ⚠️ **Deux formes cohabitent en base** : une **URL** absolue
  (`submissions.file_url`, `media.url`, `settings.hero_image`) et un **chemin
  nu** (`formations.flyer_path`, `settings['livret.cover_logo']`, les gabarits du
  livre). Tout code qui touche à ces valeurs doit traiter les deux.
- **Ce qui reste à plat, et pourquoi** : `branding/`, `ai-agents/`,
  `tutorial-*/`, `documents/templates/` appartiennent à la **plateforme** ;
  `account-photos/`, `staff-photos/`, `formation-trainers/`,
  `formation-signatures/` à une **personne** - sa photo ne change pas d'un
  colloque à l'autre, et la ranger par événement la dupliquerait.

⚠️⚠️ **UN RÉSUMÉ DÉPOSÉ PORTE LE NOM DE SA RÉFÉRENCE** - `EAU26-029.pdf`, pas
les quarante caractères aléatoires que rend `store()`. Le participant le lit dans
« Fichiers joints », le gestionnaire le télécharge par paquets, et un dossier de
`x8HFrERkQmjt9DGaJi3Ln5VzPUb8JNExBzSwr5Z8.pdf` ne se trie ni ne se relie à rien.
C'est déjà ainsi que l'import Eau-Togo nomme ses 49 pièces. Source unique :
**`EventStorage::renameSubmissionFile()`**, appelée par le dépôt
(`CreateSubmission`) et par la resoumission (`SubmissionController::resubmit`).

- ⚠️ **On dépose puis on RENOMME**, on ne nomme pas à l'écriture : `ref_code` est
  posé par `SubmissionObserver::creating()`, donc il n'existe pas encore au
  moment du téléversement. Déplacer l'écriture du fichier après la transaction
  créerait la communication même quand le dépôt du résumé échoue.
- ⚠️⚠️ **LA VERSION EST SUFFIXÉE AU-DELÀ DE LA PREMIÈRE** (`EAU26-029-v2.pdf`),
  et ce n'est pas cosmétique : la resoumission **archive** l'exemplaire précédent
  en lui laissant son `file_url`. Réécrire le même nom écraserait sur le disque
  le fichier que la version archivée désigne encore - on perdrait l'original en
  croyant déposer une correction.
- ⚠️ **On ne renomme jamais par-dessus un fichier existant** : on garde alors le
  nom actuel. Un homonyme (référence ressaisie, reprise d'import) coûterait la
  pièce de quelqu'un d'autre, et un nom moins lisible vaut mieux qu'un fichier
  perdu. Un renommage qui échoue laisse de même le fichier en place.
- ⚠️ **La référence est ASSAINIE** : elle finit en nom de fichier, et un « / »
  y ouvrirait un chemin.
- ⚠️ **Les fichiers DÉJÀ déposés gardent leur nom de hachage** : rien ne les
  renomme rétroactivement. **À faire** si on veut l'historique aligné (migration,
  sur le modèle de `classer_les_fichiers_par_evenement`).

Gardes : `ClassementDesFichiersTest` (4 cas, **4 sabotages vérifiés**).

**Migration `2026_10_17_000100_classer_les_fichiers_par_evenement`** : elle a
déplacé l'existant ET réécrit les adresses enregistrées dans le même passage -
un fichier déplacé sans son adresse est un fichier perdu.

- ⚠️ **Elle est pilotée par la BASE, jamais par le contenu des dossiers.** Il
  restait un `events/1/` peuplé des affiches du colloque de l'eau, du temps où
  cet événement portait l'identifiant 1 : déplacer les répertoires par leur nom
  aurait rangé ces affiches sous l'événement 1 d'aujourd'hui, « Innover pour un
  avenir durable ». Chaque ligne, elle, sait de quel événement elle relève.
- ⚠️ **Forme d'arrivée `<racine>/<dossier>[/camera-ready]/<fichier>`** : les
  segments intermédiaires sont abandonnés, parce qu'ils redisaient l'événement
  (l'import de l'eau rangeait sous `submissions/2/eau-togo-2026/`). C'est aussi
  **exactement** ce que le code écrit maintenant : sans cela, le prochain passage
  du seeder d'import ne retrouverait pas ses 47 pièces Word et les réécrirait à
  côté.
- ⚠️ **Elle ne réécrit une adresse que si le fichier est VRAIMENT à destination**
  (déplacé, ou déjà en place au rejeu), et **n'écrase jamais** une destination
  occupée. Un fichier introuvable des deux côtés garde son adresse : la corriger
  ferait pointer la base vers un fichier qui n'existe nulle part, et on perdrait
  la seule piste qui reste pour le retrouver.
- ⚠️ **L'hôte de l'adresse est conservé** : les URL stockées sont absolues et ont
  pu l'être sous un autre domaine. On ne touche qu'au chemin, repéré par le
  marqueur `/storage/<racine>/`.
- ⚠️ Son `down()` est **vide** : reculer ne rendrait pas un dossier nommé `2`
  plus lisible, et le code n'écrit plus que la nouvelle forme.

Garde : **`ClassementDesFichiersTest`** (règles de nommage, les trois chemins
d'écriture, le flyer, le déplacement, le rejeu, l'adresse laissée intacte quand
le fichier manque, et le dossier orphelin qu'aucune ligne ne réclame) - 7
sabotages vérifiés, dont celui qui déplace les répertoires par leur nom.

### ⚠️⚠️ `Html::render()` rend un `HtmlString`, et Alpine n'en fait rien

**Mesuré sur la modale d'évaluation** (2026-10-23) : `ReviewWorkspaceController`
passait `'abstract' => Html::render($sub?->abstract)` dans la liste sérialisée en
JSON pour Alpine. Un `HtmlString` s'y encode en **`{}`**, que `x-html` affiche
**« [object Object] »** : l'évaluateur notait une communication **dont il ne
voyait pas le résumé**.

⚠️ **Rien ne tombe, rien ne prévient.** En Blade, le même appel se rend
parfaitement - `{!! !!}` convertit l'objet en chaîne. Le défaut n'existe qu'au
passage par JSON. **Toute valeur qui part dans `@js()` se caste en `string`.**

⚠️ **Deux autres défauts du même bloc, de la même famille - une donnée qui n'est
pas ce que la vue croit :**

- la vue lisait **`cur.edition`**, une clé qui n'existe plus depuis le renommage
  « événements typés » (le contrôleur fournit `event`). Elle rendait une
  **pastille grise vide** à côté du type : un `x-text` sur une clé absente
  n'avertit de rien.
- les **mots-clés** partaient en chaîne stockée, séparateur compris, et se
  lisaient d'un bloc (« qualité de l’eau;eau de boisson;cantines scolaires »).
  Ils partent en **liste**, et la modale en fait des badges.

⚠️ **Les badges de mots-clés sont le composant partagé `.ukc-keywords`**
(`ukc.css`). La même pastille était recopiée dans `ukc.css` (fiche du
participant) et dans `admin.css` (fiche du back-office), et les deux divergeaient
déjà d'une graisse et de deux pixels de marge ; la modale en aurait fait une
troisième. Une définition, trois surfaces.

Garde : `ReviewLifecycleTest` (1 cas, **4 sabotages vérifiés**).

### Corriger & renvoyer une communication = une PAGE, pas un bloc de fiche

`/communications/{submission}/corriger` (`account.submissions.revise`). Le POST qui
applique la correction reste `account.submissions.resubmit` : la page montre le
formulaire, la resoumission l'enregistre.

⚠️⚠️ **Le formulaire vivait AU BAS DE LA FICHE**, après l'historique, les documents
liés et le créneau de passage ; le bouton « Corriger & renvoyer » n'était qu'une ancre
**`#resubmit`**. On changeait de tâche sans changer d'écran - une page de consultation
qui se mettait soudain à demander une saisie - et une erreur de validation renvoyait
l'auteur retrouver le bloc lui-même.

⚠️ **Une page, pas une fenêtre.** Le formulaire porte l'éditeur de résumé (CKEditor +
compteur de mots), le sélecteur de mots-clés et un dépôt de fichier : trois composants
qu'une modale enfermerait dans une boîte, sur une fiche qui tient déjà son propre état
Alpine et sa modale de créneau. Et le **dépôt est une page** : corriger doit se
présenter comme déposer.

⚠️ **Les champs sont CEUX DU DÉPÔT, pas une approximation.** Le résumé passe par le
même `partials.abstract-editor`, les mots-clés par le même Tom Select, le fichier par
le même `<x-ukc.filedrop>`. Il était rendu par un **`<textarea>` nu, sans compteur**,
alors que `ResubmitSubmissionRequest` refuse hors des bornes de mots de l'événement :
on corrigeait à l'aveugle, et la mise en forme du résumé se perdait au passage.

⚠️ **Seuls les champs que la resoumission APPLIQUE sont proposés** (titre, résumé,
mots-clés, fichier). L'axe, le type et les auteurs ne sont pas rééditables par ce
circuit : les montrer en saisie promettrait un enregistrement qui n'a pas lieu. Ils
sont rappelés en lecture, et la page **dit** qu'ils ne se changent pas ici.

⚠️ **Les retours du comité sont SUR cette page**, en colonne de droite : on corrige
avec les remarques sous les yeux. C'est le point de l'écran - elles vivaient en haut
d'une fiche longue, et corriger imposait l'aller-retour de mémoire.

⚠️ **Mesuré à 375px** : `min-width: auto` d'un élément de grille vaut la largeur
INTRINSÈQUE de son contenu, et la piste `1fr` s'étire pour l'absorber. Le champ caché
de Tom Select (201px) et l'input du dépôt de fichier (248px) gonflaient la colonne à
517px dans un conteneur de 385 - **119px de défilement latéral sur toute la page**.
D'où `min-width: 0` sur les enfants de `.rev-grid`, la même ligne que `.layout-plan > *`.

⚠️ `.kv` et `.msg-box` n'étaient portés que par `.account-submission-detail` : la page
de correction les reprend, d'où la liste de sélecteurs. **Pas de remontée vers
`.ukc-shell`** - « kv » est un nom trop commun pour être lâché dans tout le back-office.

⚠️⚠️ **UNE CLASSE QUI N'EXISTE PAS SE REND, SIMPLEMENT SANS STYLE.** La carte de
rappel portait `<dl class="ukc-dl">` - une classe qu'aucune feuille ne déclare.
Rien ne tombe, rien ne prévient : le `<dl>` retombe sur le défaut du navigateur,
lui-même aplati par le reset Preflight de Tailwind, et les huit lignes sortent de
même taille, même graisse et même couleur - l'intitulé ne se distingue plus de sa
valeur. Les paires vivent sous **`.rev-recap`**.

- ⚠️ **Les paires sont EMPILÉES, pas en grille « intitulé | valeur »** : la
  colonne ne fait que ~378px, et « Type de communication » s'y enroule à lui
  seul. C'est la typographie qui les sépare - intitulé en capitales fines et
  grises (10,5px/800), valeur en corps appuyé (13,5px/600).
- ⚠️ **Pas de chasse fixe pour la référence, et c'est une DÉCISION du projet** :
  `--ukc-mono` pointe sur la police courante (cf. son commentaire dans `:root`),
  donc le `font-family: var(--ukc-mono)` qu'elle portait ne faisait rien du tout.
  Elle se distingue par la graisse et l'interlettrage (`.rev-ref`), comme
  `.secret-code`.
- ⚠️ **La note est DÉTACHÉE** (filet + icône, corps plus petit) : collée sous la
  liste dans la même graisse, on la lisait comme une cinquième paire.

Gardes : `ReviewRevisionTest` (5 cas, **4 sabotages vérifiés** : la garde
`canBeRevised()` retirée, le résumé redevenu un textarea nu, les remarques retirées de
la page, et le formulaire remis au bas de la fiche).
### URLs de l'espace participant = sous `/compte`

Les pages personnelles du compte vivent sous **`/compte/...`** (`/compte`, `/compte/profil`,
`/compte/notifications`, `/compte/preferences`, `/compte/securite`, `/compte/paiement/...`).
Toute nouvelle page « Mon compte » suit cette règle. Quand une URL change, **laisser une
`Route::permanentRedirect`** depuis l'ancienne : les notifications stockent une **URL
absolue** figée à la création (`Notifier::account(url: route(...))`), donc d'anciennes
lignes en base pointent encore vers l'ancien chemin.

### 2FA : la méthode est EXCLUSIVE (`totp` **ou** `sms`)

`hasSmsEnabled()` est **prioritaire** sur `hasTotpEnabled()` dans `twoFactorMethod()`.
Toute activation doit donc **poser `two_factor_method`** et nettoyer l'autre méthode -
sinon un compte qui bascule du SMS vers l'application reste en méthode `sms`, reçoit un
SMS à la connexion et voit son code d'authenticator refusé. Vaut pour
`Account\SecurityController::confirmTwoFactor` **et** `Admin\ProfileController` (même
correctif des deux côtés). ⚠️ En dev, `sms.default` = **`log`** : les codes SMS ne partent
pas, ils atterrissent dans `storage/logs/laravel.log`.

### Comptes = `User` unifié (option B) - ⚠️ pas `Account`

Les **participants sont des `App\Models\User`** : depuis la refonte « compte unifié »,
les guards **`web`** (back-office) et **`account`** (espace participant) résolvent la
**même table `users`** (seul le *contexte* diffère). Le modèle/colonne `Account`/`account_id`
est **legacy et mort**. Toute nouvelle table liée à un participant référence **`user_id`**
(users), jamais `account_id`. `Participant` (par édition) porte `user_id`.

- ⚠️ **`User` utilise `SoftDeletes`** (colonne `deleted_at`) : une suppression de compte
  est **réversible**. Le fournisseur d'authentification Eloquent et la liaison de route
  appliquent le scope SoftDeleting → un compte supprimé **ne peut plus se connecter** ni
  être retrouvé (404). ⚠️ `users.email`/`users.phone` restent **uniques en base** : un
  compte soft-deleted garde son adresse/numéro et bloque la réinscription tant qu'il n'est
  pas restauré ou purgé (pas d'écran de restauration pour l'instant).
- **Suppression depuis le back-office** (`admin.users.destroy`) : modale sécurisée (liste
  **et** fiche, partial `admin/users/_delete-modal.blade.php`) exigeant la saisie du mot
  **« Supprimer »** ET du **mot de passe de l'administrateur** (`Hash::check`), en plus des
  gardes « pas soi-même » / « pas le dernier administrateur ». Messages via flash (toast).
  Tests : `AdminUsersTest`.

### Rôles : TROIS niveaux système, tout le reste est PAR ÉVÉNEMENT

⚠️ **C'est le modèle de référence.** Un compte porte des rôles de **deux natures**, et
les confondre est la source d'erreur numéro un de ce projet.

**1. Le niveau d'accès SYSTÈME** - `users.access_level`, trois valeurs et pas une de
plus (`User::accessLevels()`), sur la plateforme entière :

| Niveau | Ce qu'il fait |
| --- | --- |
| `super_admin` | Tout ce qu'ouvre un administrateur, **plus** la seule chose qui lui est refusée : nommer un super administrateur, et voir ces comptes. |
| `colloque_admin` | **Accès à TOUT** - tous les événements (sans affectation), les comptes, les rôles, les réglages. **Crée les événements** et y nomme gestionnaire / co-gestionnaire. **Ne peut pas** nommer de super administrateur. |
| `user` | Aucun accès global. **C'est le défaut**, et c'est le niveau de l'immense majorité. |

**2. Tous les autres rôles sont RELATIFS À UN ÉVÉNEMENT**, en trois familles :

- **L'équipe de l'événement** - `event_user.role` (clé unique `event_id + user_id + role`,
  donc plusieurs fonctions possibles sur un même événement). Les fonctions et leurs
  capacités vivent dans **`StaffRoles::ROLES`** / `PERMISSIONS` (gestionnaire,
  co-gestionnaire, comités, relecteur, organisateur, secrétariat, comptabilité, appui,
  accueil). Elles se lisent par `rolesOnEdition()` et donnent `capabilitiesOnEdition()`.
- **Le programme** - **modérateur** et **rapporteur** d'une séance. ⚠️ **Ce ne sont pas
  des rôles stockés** : ils se DÉDUISENT des affectations du programme
  (`isModeratorSomewhere()`, `isRapporteurSomewhere()`). Les chercher dans `event_user`
  est une erreur.
- **L'évaluation** - **relecteur**, déduit des relectures confiées
  (`isReviewerSomewhere()`, via `participants.role = 'reviewer'`).

⚠️ **Un même compte est « utilisateur » au système ET gestionnaire d'un événement,
relecteur sur un autre, rien du tout sur un troisième.** Gérer un événement n'élève
personne au rang d'administrateur de la plateforme.

⚠️⚠️ **LES LIBELLÉS DISENT « ÉVÉNEMENT », PAS « COLLOQUE »** (2026-10-19). GEVES gère
aussi des **séminaires** et des **journées scientifiques** (`EventType`) : les rôles
étaient pourtant nommés comme si le colloque était le seul objet du système.
`colloque_admin` → **« Administrateur »**, `gerant` → **« Gestionnaire de l'événement »**,
`co_gerant` → **« Co-gestionnaire de l'événement »**, et les descriptions de `StaffRoles`
suivent. ⚠️ **Seuls les LIBELLÉS changent** : les clés (`colloque_admin`,
`manage_colloques`) sont des identifiants enregistrés en base, et la convention ci-dessus
les protège. Même règle que pour le site public, et pour la même raison.

⚠️ **« Administrateur » tout court, et surtout PAS « Administrateur d'événements »** :
ce niveau n'est pas borné aux événements. Il **crée les événements et y attache leur
gestionnaire**, et il a **accès à tout** - événements, comptes, rôles, réglages. Le seul
niveau au-dessus de lui est le super administrateur, et **la seule chose qui les sépare**
est de pouvoir en nommer un (voir plus bas).

⚠️ Le rôle spatie `administrator` s'appelle donc **« Administrateur (rôle hérité) »** :
il porte `manage_colloques`, donc `isColloqueSuperAdmin()` - il ouvre **TOUT**, au même
titre que le niveau super administrateur. Le confondre avec le niveau « Administrateur »,
qui est le cran en dessous, reviendrait à donner la plateforme entière en croyant nommer
un administrateur ordinaire. Plus rien ne l'attribue.

⚠️⚠️ **UN SUPER ADMINISTRATEUR N'EST VISIBLE QUE D'UN SUPER ADMINISTRATEUR.**
L'administrateur a la main sur les comptes - c'est lui qui distribue les rôles - mais pas
sur le seul niveau placé au-dessus du sien. Source unique : le scope
**`User::scopeVisiblesPar($viewer)`** et son pendant **`User::voitLeCompte()`**.

- Appliqué à la **liste** d'`admin/utilisateurs`, à ses **compteurs** et aux compteurs de
  l'onglet Rôles : un compte qu'on laisse apparaître dans un total est un compte dont on
  apprend l'existence.
- ⚠️ **Masquer une ligne ne ferme pas une porte** : `UserController::garde()` est posée
  sur les **cinq** actions qui prennent un `{user}` (fiche, enregistrement, renvoi de
  vérification, réinitialisation de mot de passe, suppression). Sans elle, l'identifiant
  suffisait encore à tout faire. **404, pas 403** : un 403 confirmerait que le compte
  existe.
- ⚠️ **Et l'annuaire du copilote suit la même règle** (`UserDirectory`, qui reçoit
  désormais le demandeur) : masquer dans la page sans fermer l'assistant, c'est ne rien
  masquer. Les quatre blocs et tous leurs compteurs sont filtrés.
- ⚠️ `!=` **écarte les NULL en SQL**, et `access_level` est nullable : sans
  `orWhereNull`, tout compte au niveau non renseigné disparaissait de la liste.

⚠️⚠️ **DEUX PRÉDICATS QUI SE RESSEMBLENT, ET QUI NE DISENT PLUS LA MÊME CHOSE**
(2026-10-20). C'est la confusion la plus facile du module, et elle ne se voit pas :
une page répond 403 à un seul niveau d'accès, sur un seul écran.

- **`User::isColloqueAdmin()` = LA PORTÉE.** Administrateur **ou** super administrateur :
  les deux passent partout. C'est lui qu'interrogent `Gate::before`, `EnsureEditionAccess`,
  `ScopesEditionAccess` et les **cinq** portes d'accès aux événements du modèle
  (`canAccessEdition`, `capabilitiesOnEdition`, `canOnEdition`, `accessibleEditionIds`,
  `canAnyEdition`).
- **`User::isColloqueSuperAdmin()` = LA RÉSERVE**, et plus rien d'autre : nommer ou
  révoquer un super administrateur (`niveauxAttribuablesPar()`) et voir ces comptes
  (`scopeVisiblesPar()`, `voitLeCompte()`). **Hors de `User`, l'écrire est presque
  toujours une erreur** - un cas de `DroitsDeLAdministrateurTest` balaie `app/`,
  commentaires retirés, et échoue si le nom ressort du modèle.

⚠️⚠️ **ET LA MÊME CONFUSION EXISTE EN SQL** (21/10/2026). Un vivier d'affectation
dressé sur la seule colonne **`access_level`** écarte un compte qui tient ses droits
du **rôle hérité `administrator`** ou de la permission **`manage_colloques`** : le
modèle le dit administrateur, la requête l'ignore, et il disparaît d'une liste sans
que rien ne le signale. C'est exactement ce qui est arrivé au vivier des réclamations
- le compte de `adminStaff()` (permission, `access_level` non renseigné) n'y entrait
plus, et `ClaimsTest` tombait sur « null is identical to 1 ». **Deux scopes, et une
seule moitié commune** (`User::ouAdministrateurHerite()`) : **`administrateurs()`** =
la forme SQL d'`isColloqueAdmin()`, **`superAdministrateurs()`** = celle
d'`isColloqueSuperAdmin()`. Ne jamais recopier `whereIn('access_level', …)` pour
désigner « les administrateurs ».

- ⚠️ **Il reste UNE négation écrite à la main** : `UserDirectorySearch::find()`
  écarte les administrateurs globaux de la liste des personnes d'un événement, et
  réénumère pour cela les trois chemins. À ramener sur
  `whereNot(fn ($q) => $q->administrateurs())` le jour où l'on y retouche - **sans
  oublier qu'`access_level` est nullable** : une négation sans `orWhereNull` écarte
  aussi les comptes au niveau non renseigné (c'est le piège du `!=` ci-dessus).

⚠️ **Deux gardes de routes, une seule source de vérité.** `edition.cap:<capacité>` (16
groupes) passe par `canOnEdition()` ; `can:<capacité>` (3 groupes : Réglages,
Utilisateurs & rôles, Témoignages) passe par le Gate spatie. **Les deux doivent
s'accorder** : `Gate::before` interroge donc `isColloqueAdmin()`, qui couvre le niveau
d'accès, le rôle spatie `administrator` et la permission `manage_colloques`. Avant ce
branchement, un compte `access_level = super_admin` sans rôle spatie ouvrait 16 groupes
sur 19 et tombait en **403 sur les 3 autres** - le modèle disait « super-administrateur »,
le portail disait non. Le défaut ne se voyait pas, le seul super-admin de la base portant
aussi le rôle spatie. **Et le même défaut valait pour l'administrateur**, jusqu'à ce que
ces trois groupes lui soient ouverts.

⚠️ **L'ANNUAIRE DU COPILOTE N'A PAS BOUGÉ D'UNE LIGNE, et c'est la preuve que la
condition était à la bonne place** : `UserDirectory::autorise()` demande toujours
`can('users_roles')`, exactement ce qu'exige la page. L'administrateur la franchit
maintenant par son niveau ; le staff d'un événement, non. Une garde écrite là-bas sur
le niveau d'accès aurait dû être retrouvée et corrigée - et c'est celle qu'on aurait
oubliée.

⚠️ **Créer un événement n'inscrit plus l'administrateur dans son équipe**
(`EventFormController::store`) : il les ouvre tous, et il crée l'événement pour y
attacher SON gestionnaire. Le geste reste pour les autres - un gestionnaire qui crée un
événement en devient le gestionnaire, sinon il perdrait ce qu'il vient d'ouvrir.

⚠️⚠️ **CONSÉQUENCE SUR LES DONNÉES.** `ColloqueEauTogoSeeder` posait `colloque_admin` sur
les **deux gestionnaires réels** du colloque de l'eau, sur la foi d'un commentaire qui
promettait « sans voir les colloques où il n'est pas affecté ». C'était vrai tant que ce
niveau n'ouvrait rien ; aujourd'hui, c'était leur donner les comptes, les rôles et les
réglages. Le seeder pose désormais `ACCESS_USER` - leur pouvoir vient de `event_user`,
conformément à la règle « gérer un événement n'élève personne ».

⚠️ **Le seeder étant idempotent, le corriger ne corrige RIEN en base** : il ne retouche
jamais un compte existant. D'où la migration
**`2026_10_20_000100_retirer_le_niveau_administrateur_aux_comptes_importes`**, qui
redescend les comptes déjà importés. Elle ne vise **que ce que l'import a promu** - la
marque `meta.import_source`, que le seeder écrit dans le même geste que le niveau : un
compte promu à la main depuis l'écran des niveaux n'en porte pas et n'est pas touché. Son
`down()` est **vide** : une remontée de privilèges ne se « restaure » pas. ⚠️ Elle lit le
JSON **en PHP**, pas en SQL - les fonctions de chemin JSON diffèrent entre MySQL et
SQLite, et le défaut se découvrirait en ligne, au milieu d'un déploiement.

⚠️ Le défaut `'user'` d'`access_level` vit **en base**, pas dans le modèle : une
instance tout juste créée porte `null` jusqu'au rechargement. Inoffensif (les
comparaisons `===` retombent du bon côté), mais ne lisez pas l'attribut juste après un
`create()`.

⚠️ **L'onglet Rôles (`admin/utilisateurs`) montre les FONCTIONS D'ÉQUIPE**, pas les
niveaux système - qui n'y figurent pas du tout. Trois pièges levés le 2026-10-12 :

- Son compteur lisait les rôles **globaux** (spatie) alors qu'une fonction se confie par
  événement : il annonçait « 0 utilisateur » pour un secrétariat et une comptabilité à
  l'œuvre sur un colloque. Il affiche désormais **« N personne(s) sur M événement(s) »**
  (`UserController::roleUsageLines`, `user_id` distincts), puis **« N rôle global »
  seulement s'il y en a** - ce qui rend visible qu'« Administrateur colloque » est un rôle
  de plateforme.
- Les deux familles s'appellent **« Organisation » / « Appui »** : « Système » entrait en
  collision avec les trois niveaux système, qui n'ont rien à voir. Une seule couleur par
  famille (`StaffRoles::categoryPill()`) ; `pill()` reste la couleur de la FONCTION,
  employée dans la liste des utilisateurs.
- Le rôle legacy **`editor` est supprimé** : il portait `manage_colloques` (accès complet
  par court-circuit de `Gate::before`) **sans figurer dans `StaffRoles`**, donc ni dans cet
  onglet ni dans la matrice d'accès. Un rôle à tous les pouvoirs invisible des écrans qui
  servent à surveiller les pouvoirs. Migration `drop_legacy_editor_role`, qui ne retire que
  s'il n'est attribué à personne.

⚠️ **`admin/users/index.blade.php` emploie la forme EN LIGNE de la directive PHP de Blade
(ligne 73).** N'y ouvrez pas de bloc PHP : Blade apparie la première ouverture à la
première fermeture et avale tout ce qui les sépare. Et ne nommez pas ces directives en
toutes lettres, même dans un commentaire - Blade les compile **avant** de retirer les
commentaires. Toute logique de cet écran se calcule dans le contrôleur.

⚠️⚠️ **LA FICHE D'UN COMPTE (`admin/utilisateurs/{user}/modifier`) NE DISTRIBUE PLUS
AUCUN RÔLE GLOBAL.** Elle offrait les **douze fonctions de `StaffRoles` en cases à
cocher**, à l'échelle de la plateforme : elle contredisait donc le modèle ci-dessus,
où les **seuls** rôles globaux sont les **trois niveaux d'accès**. Le reste -
gestionnaire, co-gestionnaire, comités, secrétariat, comptabilité, appui, accueil - se
confie **par événement** (`event_user`). Les cases ne faisaient rien d'autre que poser
une étiquette : les capacités d'une fonction ne sont lues que par `edition.cap:` →
`canOnEdition()`, donc depuis l'affectation.

- **Le parcours est celui-ci** : l'**administrateur de colloque** crée l'événement et y
  nomme **gestionnaire** et/ou **co-gestionnaire** ; ce sont eux qui paramètrent
  l'événement, forment l'équipe et montent le programme. Rien de tout cela ne passe par
  la fiche d'un compte.
- ⚠️ **`store()`/`update()` n'appellent plus `syncRoles()`**, et `validateUser()` n'a
  plus de règle `roles` - un champ qu'on valide est un champ qu'on promet d'accepter. Les
  rôles **déjà posés en base** sont laissés en place (ils alimentent encore les pastilles
  et le filtre de la liste) : plus rien ne les crée, rien ne les efface non plus dans le
  dos de qui corrige une adresse. ⚠️ **Reste à trancher** : purger ces rôles et retirer
  le filtre de la liste, l'onglet Rôles et la matrice d'accès.
- ⚠️ **Le NIVEAU D'ACCÈS a sa carte** : c'était un `<select>` entre « Nouveau mot de
  passe » et « Notes internes », sous une légende d'une ligne pour les trois choix
  confondus - alors que `super_admin` ouvre TOUT par `Gate::before`. Trois choix, chacun
  disant ce qu'il ouvre (`User::accessLevelHints()`). ⚠️ **Pas de chapeau au-dessus
  d'eux** : la carte en portait un qui redisait le modèle (« le seul rôle global… »), et
  trois indices sous trois choix se passent d'un quatrième paragraphe.
- ⚠️ **La fiche montre l'ACCÈS RÉEL** : les affectations `event_user` du compte
  (événement, fonction, nombre de capacités, lien vers l'onglet Équipe), en lecture
  seule. On y réglait des accès sans jamais voir ceux dont le compte dispose.
- ⚠️ **DEUX COLONNES : ce qui se modifie à gauche, ce qui se constate et s'actionne à
  droite** (`.uf-grid`). Vérification d'adresse, mot de passe et suppression vivaient
  **sous le bouton « Enregistrer »**, après la fin apparente de la page : on les lisait
  comme une suite du formulaire. Elles ne peuvent pas y entrer - chacune a le sien, et
  deux formulaires ne s'imbriquent pas - d'où la colonne. Elle repasse **sous** le
  formulaire en dessous de 1040px. ⚠️ **Et la fiche n'a PAS de `max-width`** : bridée,
  elle laissait un tiers de l'écran vide à droite sur un grand moniteur.
- ⚠️⚠️ **`destroy()` gardait la porte, `update()` laissait la fenêtre ouverte.** La
  suppression interdit depuis toujours d'effacer son propre compte et le dernier
  administrateur ; l'enregistrement de la fiche ne vérifiait **rien**, alors qu'il mène au
  même endroit : rétrograder le dernier super-administrateur, ou se désactiver soi-même -
  après quoi plus personne n'ouvre `admin/utilisateurs` pour revenir en arrière.
  `UserController::refusDeFermeture()` les refuse. ⚠️ **Sur soi-même c'est interdit même
  quand d'autres administrateurs existent**, comme la suppression de son propre compte :
  un collègue le fait, en connaissance de cause.
- ⚠️ **`User::$access_level` est annoncé `string|null`** : son défaut `'user'` vit en
  BASE, donc une instance tout juste créée le porte à `null`. Annoncé `string`, il faisait
  passer tout contrôle de nullité pour « toujours vrai » à l'analyse - qui invitait donc à
  retirer précisément la garde qui tient ce cas.
- ⚠️ La fiche était écrite en **styles EN LIGNE** (une trentaine de `style=`), donc
  inadaptable en étroit (un style en ligne l'emporte sur toute règle, media query
  comprise) : sa mise en page vit sous `.admin-user-form` dans `admin.css`.

⚠️ **Nommer un gestionnaire n'envoie AUCUN e-mail** (`EventFormController::attachTeamMember`
attache la ligne et redirige) : la personne n'apprend sa nomination que si on la prévient
à la main. **À faire.**

Gardes : **`DroitsDeLAdministrateurTest`** (11 cas, **5 sabotages vérifiés**) pour la
ligne entre les deux niveaux - les trois groupes `can:` qui s'ouvrent, l'événement ouvert
sans affectation, la contre-épreuve du gestionnaire qui ne voit pas l'événement d'à côté,
le niveau super admin ni proposé ni **accepté**, et la garde de source sur
`isColloqueSuperAdmin()`. **`ModeleDesRolesTest`** - trois niveaux et pas plus, qui crée
un événement, l'équipe attachée à UN événement, les rôles déduits et non stockés, et
l'accord des deux portails. **`OngletRolesTest`** pour la liste : compteurs, familles,
couleur par famille, absence du rôle `editor`. Et **`FicheDuCompteStaffTest`** pour la
fiche (17 cas, 6 sabotages vérifiés) : aucun rôle global proposé **ni accepté**, les
actions du compte hors du formulaire, et `routes/web.php` qui ne garde que `settings`,
`testimonials` et `users_roles` par `can:`, **aucune fonction d'équipe** ne les portant.

### E-mails de compte = rédigés, LOCALISÉS (défaut français) + charte UK

Les e-mails d'**authentification** (confirmation d'adresse à l'inscription, mot de
passe oublié, vérification staff) étaient les **notifications par défaut de Laravel**,
en **anglais générique** et sans habillage. Ils sont désormais rédigés, **localisés**
et **habillés à la charte de l'Université de Kara** :

- **`User implements HasLocalePreference`** → `preferredLocale()` = `Locales::resolve($this->locale)`
  (repli **français**). Laravel enveloppe l'envoi de **toute notification** dans cette
  locale, donc l'e-mail part dans la langue choisie par la personne. ⚠️ Vaut pour les
  notifications (canal mail) ; les `Mailable` ne s'auto-localisent PAS ainsi.
- Notifications dédiées : **`App\Notifications\AccountVerifyEmail`** (inscription = aussi
  un mot de bienvenue), **`AccountResetPassword`** (mot de passe oublié ; comptes unifiés
  → staff **et** participants), **`StaffVerifyEmail`** (back-office). `User::sendEmailVerificationNotification`
  / `sendPasswordResetNotification` les envoient. Un **admin peut déclencher la
  réinitialisation** depuis `/admin/utilisateurs` (bouton clé → `admin.users.password-reset`
  → `Password::broker('users')->sendResetLink`, donc le même e-mail localisé). ⚠️ **L'URL signée reste fournie par les
  `VerifyEmail::createUrlUsing` / `ResetPassword::createUrlUsing`** d'`AppServiceProvider`
  (routes `account.*`) : les notifications ne redéfinissent QUE `buildMailMessage` (le texte).
- Rédaction via **`__('texte FR')`** (clé = français), traductions dans **`lang/en.json`**.
  ⚠️ Ajouter une ligne d'e-mail = ajouter la clé FR **et** sa traduction `en`.
- **Accueil personnalisé** : l'accueil (« Bonjour, » de la réinitialisation/vérification
  staff **et** la bienvenue à l'inscription « Bienvenue sur :app ! ») salue par
  **civilité + nom** quand ils sont connus (« Bonjour Dr Awa Doumbia, », « Bienvenue sur
  :app, Dr Awa Doumbia ! »), sinon repli générique. Source
  unique **`App\Notifications\Greeting::for($user)`** + **`User::salutationName()`**
  (⚠️ jamais `name`, qui peut valoir l'e-mail faute de prénom/nom). Les notifications
  captent le destinataire dans **`toMail($notifiable)`** (buildMailMessage ne le reçoit
  pas) pour le passer au helper.
- **Texte de repli sous le bouton** (subcopy « copiez-collez l'adresse… ») : c'était
  une chaîne **anglaise du framework**. La vue de notification est publiée
  (`resources/views/vendor/notifications/email.blade.php`) et localisée (clé FR + en.json),
  replis de greeting/salutation compris.
- Locales prises en charge : `config('app.available_locales')` = `fr` (défaut) / `en`.
- **Charte UK = thème markdown partagé** (`config('mail.markdown.theme') = 'geves'`),
  donc **TOUS les e-mails markdown** l'héritent (notifications ET mailables de
  `resources/views/emails/*` via `<x-mail::message>`) : bandeau **bleu `#223ea4`** +
  filet **or `#ffd700`**, **logo en en-tête**, bouton d'action au bleu charte (vert
  `#009933` = succès), police **Jost** (repli sans-serif), pied de page français.
  - Composants publiés dans **`resources/views/vendor/mail`** : on ne garde que ceux
    qu'on personnalise (`html/layout`, `html/header`, `html/message`, `text/message`,
    `html/themes/geves.css`) ; les autres **retombent sur le framework** (ne pas
    republier tout le kit). ⚠️ Le thème est en **aplats** (pas de dégradé : Outlook ne
    les rend pas) et l'inlining CSS fige les couleurs.
  - **Le logo vient de `App\Support\Branding::wideWhite()`** (réglé dans Réglages ›
    Général), en **URL absolue** (hébergée) sur le bandeau sombre ; repli sur le fichier
    livré, déjà dessiné en blanc. ⚠️ Ne pas écrire un logo en dur dans l'en-tête.
  - ⚠️ **Jamais le point médian (U+00B7) dans un sujet/pied d'e-mail** : c'est un
    **caractère proscrit** (cf. TypographieTest), au même titre que le cadratin (U+2014)
    et le demi-cadratin (U+2013). Séparateur = tiret simple ` - `. Les sujets des
    notifications l'employaient : repris en tiret simple.
  - ⚠️⚠️ **LE BOUTON SE DÉFEND DU MODE SOMBRE DE GMAIL** (2026-10-19). Gmail
    (application Android/iOS) **repeint** ce qu'il trouve : il éclaircit un fond
    sombre et assombrit le texte clair posé dessus. Le bouton bleu `#223ea4` à
    texte blanc ressortait en **lavande pâle à texte bleu foncé**, quasi illisible -
    constaté sur un vrai envoi, capture à l'appui.
    - **Le fond est doublé d'un `background-image`** (dégradé d'une seule couleur,
      donc un aplat à l'œil) : Gmail ne retouche **pas** les images de fond.
      ⚠️ Ce n'est pas un décor, et la règle « pas de dégradé » ci-dessus tient
      toujours : Outlook ignore les dégradés et retombe sur `background-color`, de
      la même couleur exactement.
    - ⚠️ **Le rembourrage est un vrai `padding`**, plus le jeu de bordures de la
      couleur du fond que posait Laravel : une bordure se recolore indépendamment
      du fond, et c'est elle qui dessinait le halo pâle. **Contrepartie assumée** :
      le moteur Word d'Outlook n'applique pas le rembourrage d'un lien, le bouton y
      est donc serré (la `line-height`, qu'il honore, lui rend sa hauteur). On
      choisit l'écran où le défaut a été CONSTATÉ. Le remède complet serait un
      bouton en `<td>` (VML), donc un `button.blade.php` publié - **pas** le retour
      des bordures.
    - ⚠️ **Rien de tout cela ne se vérifie en local** : il faut un envoi réel lu
      dans Gmail, en mode sombre, sur téléphone.
  - ⚠️ **`word-break: break-all` ne vaut que pour l'adresse copiée-collée**
    (`.break-all`, sous le bouton). Posée sur tous les liens de la carte, elle
    s'appliquait à l'intitulé du bouton, qui se coupait en plein mot.
  - ⚠️ **L'espace intérieur est SERRÉ** : 36px de rembourrage partout mangeaient un
    cinquième d'un écran de téléphone, et le pied en ajoutait autant sous la carte
    (corps `26px 24px`, pied `16px 24px 20px`, bandeau `18px`, paragraphes
    `line-height 1.5` + marge basse **écrite** à 14px - laissée au client, elle vaut
    `1em` et chacun en décide autrement).
  - ⚠️⚠️ **LE BANDEAU EST DANS LA CARTE**, pas au-dessus d'elle (`layout.blade.php` :
    la ligne d'en-tête vit dans `table.inner-body`). Posé dans la table extérieure,
    il s'étirait sur **toute la largeur de la fenêtre** pendant que le message
    restait à 570px - l'e-mail se lisait comme deux objets empilés. Il épouse donc
    la largeur du contenu et les deux coins hauts de la carte (Outlook ignore
    l'arrondi : angles droits, le bandeau reste un bandeau).
  - ⚠️ **Sur téléphone, l'e-mail va BORD À BORD** (media query ≤600px) : la carte
    prend toute la largeur, perd ses coins arrondis, son ombre, le cadre gris de la
    page (`background #ffffff`) **et son rembourrage haut**, et son espace intérieur
    tombe à `20px 16px`. **Mesuré dans l'aperçu à 380px : carte et bandeau à
    `x=0, y=0, largeur 380`, `body` sans marge ni rembourrage** - notre HTML ne pose
    donc **aucune** marge sur les quatre côtés.
    - ⚠️⚠️ **LA BORDURE BLANCHE QUI RESTE DANS GMAIL EST CELLE DE GMAIL** (≈16dp
      autour de tout message, dans son application). Aucun gabarit ne l'enlève, et
      ce n'est pas faute d'avoir cherché - on l'a mesurée des deux côtés. La seule
      manière de ne plus la **voir** serait de supprimer la couleur au bord : un
      en-tête blanc (logo en version couleur) sur téléphone, où le gouttière blanche
      de Gmail se fondrait. C'est un choix de présentation, pas un correctif.
  - ⚠️ **Sur grand écran, le gris de la page respire au-dessus de la carte**
    (`.body { padding: 24px 0 0 }`) : collée au bord, elle donnait l'impression d'un
    bandeau bleu posé à même la boîte de réception.
- Tests : `tests/Feature/AccountMailLocalizationTest.php` (langue suit le compte + rendu
  FR/EN) ; `AccountAuthTest` asserte `AccountVerifyEmail::class` à l'inscription ;
  `TypographieTest` garde l'absence des caractères proscrits (sujets compris) ;
  **`ApercuEmailTest`** (aperçu + bouclier de mode sombre + espacements).

### Aperçu d'un modèle d'e-mail = le VRAI message, rendu par le serveur

⚠️⚠️ L'aperçu de `admin/emails` affichait l'objet et le corps **en texte brut** : on
y relisait sa prose, on n'y voyait ni la mise en page, ni ce que les balises de
fusion allaient donner. Il POSTE désormais sur **`admin.emails.templates.preview`**
(`EmailTemplateController::preview`, cap `emails`), qui rend le **même mailable**
(`CampaignMail`) avec le **même thème de marque** - bandeau, bouton, pied compris -
et le pose dans un cadre isolé. Même grammaire que l'aperçu verrouillé du reçu.

- ⚠️⚠️ **La substitution est CELLE DE L'ENVOI** : `QueueEmailCampaign::fill()` est
  devenue publique et l'aperçu l'appelle sur un participant d'exemple
  (`EmailTemplates::sampleParticipant()`, non enregistré, relation `event` **posée**).
  Une seconde table de valeurs propre à l'aperçu finirait par promettre autre chose
  que ce qui part.
- ⚠️ **Une balise que l'envoi ne sait pas remplir se voit VIDE** - et c'est
  l'information la plus utile de cet écran : **sept balises de la palette sur douze**
  (`{{communication_title}}`, `{{amount}}`, `{{payment_ref}}`, `{{attestation_url}}`,
  `{{badge_url}}`, `{{session_date}}`, `{{session_room}}`) sont encore remplacées par
  du vide. L'éditeur les propose pourtant en puces cliquables : « Montant dû :
  {{amount}} FCFA » part en « Montant dû :  FCFA ». **À trancher** : les alimenter,
  ou les retirer de la palette.
- ⚠️ **`{{login_url}}` pointait sur `/mon-espace`, qui n'existe pas** : toute
  campagne l'employant envoyait ses destinataires sur une page d'erreur. C'est
  `route('account.dashboard')`.
- ⚠️ Le rendu arrive par `fetch` + `srcdoc`, pas par le `target` d'un formulaire :
  le corps peut faire 5 000 caractères (donc pas de GET), et un cadre cloisonné visé
  par un formulaire se fait **bloquer** par le navigateur (mesuré).
- ⚠️ Le cadre porte **`sandbox="allow-same-origin"` SEUL** : scripts, formulaires et
  navigation restent interdits (c'est `allow-scripts` qui les ouvrirait), mais le
  document garde notre origine - sans quoi le **logo du bandeau ne se charge pas** et
  l'aperçu montre une image cassée là où le destinataire verra la marque.

### Impersonation (« se connecter en tant que »)

`lab404/laravel-impersonate`, cross-guard (`web` → `account`), via
`Admin\ImpersonationController` (`take`/`leave`). La bannière + le bouton « Quitter »
vivent dans `components/layouts/participant.blade.php`.

- ⚠⚠ **LE GESTE PART DE `/admin/utilisateurs`, PAS DES PARTICIPANTS** (2026-10-20).
  On emprunte un **COMPTE**, pas une inscription à un événement : le bouton est une
  action de la ligne, dans **Utilisateurs & rôles** (`admin.users.impersonate`), et le
  contrôleur reçoit un `User`. L'ancienne route `admin.participants.impersonate` est
  **supprimée** - une route laissée en place sans bouton serait une seconde porte que
  personne ne surveille - et la fiche `admin/participants/preview` ne porte plus rien.
  - ⚠️ **Ce déplacement ATTEINT ce que l'ancienne page ne pouvait pas** : elle partait
    d'un `Participant`, donc un modérateur rapproché par e-mail ou un membre du staff -
    inscrit à aucun événement - restait hors d'atteinte. La question « ce compte a-t-il
    une fiche participant ? » ne se pose plus.
  - ⚠️ **Et il RESSERRE l'accès** : la page est gardeée par `can:users_roles`, que
    l'admin de colloque n'a pas par défaut - en pratique, seul le super-administrateur
    impersonifie désormais. `canImpersonate()` reste la règle du modèle et le contrôleur
    la revérifie : pour l'ouvrir à l'admin de colloque, c'est la **matrice d'accès** qu'on
    coche, pas le code.
  - ⚠️ **On ne s'impersonifie pas soi-même** : cela déconnecterait l'admin du back-office
    pour le poser dans son propre espace participant, avec « quitter » pour seul retour -
    et sa ligne est juste là dans la liste. Refusé côté serveur, bouton masqué côté vue.
  - ⚠️ **Quitter ramène aux COMPTES** (`admin.users.index`), par les deux chemins :
    `ImpersonationController::leave` **et** le filet d'`Account\LogoutController`.
- ⚠️ **Qui peut impersonifier : UNIQUEMENT admin de colloque + super-admin**
  (`User::canImpersonate()` = **`isColloqueAdmin()`**). Un gestionnaire ou le secrétariat,
  même avec la capacité `participants`, ne le peut pas. Le bouton « Se connecter en tant
  que » est **gardé par `canImpersonate()`** en plus de la permission de la page.
- ⚠️ **En impersonation, « se déconnecter » QUITTE l'impersonation, il ne déconnecte
  PAS.** Staff et compte impersonifié partagent la même session : `session()->invalidate()`
  déconnecterait aussi l'admin. `Account\LogoutController` détecte l'impersonation
  (`app('impersonate')->isImpersonating()`) et appelle `leaveImpersonation()` au lieu de
  vider la session (filet de sécurité couvrant tout chemin de déconnexion). Le bouton de
  la barre participant pointe en plus directement sur `impersonation.leave` dans ce cas.
- Tests : `tests/Feature/ImpersonationTest.php`.

### Module Formation (en cours, par phases)

Module **activable par édition** (`settings['doc']['formation.enabled']`, via `EditionSettings`),
capacité RBAC **`formation`**. Tables `formations` (plusieurs par édition ; `fee` 0=gratuite ;
colonnes `meeting_*` pour la visio) + `formation_trainers` (`user_id` + repli libre) +
`formation_enrollments` (inscription `user_id`, `status` registered→paid). Admin :
`Admin\FormationController` (`admin.formations*`). Participant : `Account\FormationController`
(`account.formations` = `/formations`, inscription gratuite/payante). ⚠️ **Une formation
appartient à une édition (= l'événement)** : l'espace participant se scope sur **l'édition
sélectionnée** (`CurrentColloque::current()`). Si cette édition ne propose pas de formation
(module désactivé **ou** aucune formation publiée → `Formation::offeredIn($edition)` faux),
le **lien du menu est masqué ET la page renvoie 404**. **Paiement de nature
`formation`** : `Payment.meta.flow='formation'` (helper `Payment::flow()`) ; ⚠️ **`ConfirmPayment`
est flow-aware** - la garde anti-double ne vaut que pour la **participation**, donc participation +
formation(s) + co-auteur coexistent. Page publique : section `#formations` (formations `public`)
via `Public\ColloqueController`. E-mails : confirmation d'inscription (`FormationEnrollmentMail`,
kind notif `formation`) + message admin aux inscrits (`admin.formations.message`, réutilise
`CampaignMail`). Attestations : type `formation` (inscrits) + nouveau `formation_animation`
(formateurs), émises depuis la page Formations (`admin.formations.attestations`) ; colonne
`attestations.formation_id`, variable `{formation}`. **Visio = le module visio, pas une reimplémentation** : contrat
`App\Meetings\Contracts\Meetable` + trait **`App\Meetings\Concerns\JoinsMeetings`** (URL de
connexion, embarquement Jitsi, lecteur LiveKit) partagés par `EventSession` **et** `Formation` ;
seul **`meetingRoleFor()`** est propre à chaque modèle. La page « Rejoindre » est **la même**
(`MeetingController`, vue `meeting.join`) : `session.meeting` pour une session,
`account.formations.visio` pour une formation. Tout nouveau modèle portant une visio
**implémente `Meetable` + `use JoinsMeetings`** et passe par `MeetingController`, jamais par
une redirection maison. **Module complet (phases 1-6)** + raffinements admin :
**activation via le formulaire d'édition** (onglet Options, `settings.formation_enabled`, `Edition::formationEnabled()`),
**ajout via slideover** (bouton « Nouvelle formation »), **flyer** (`formations.flyer_path`, `<x-ukc.filedrop>`),
**paramétrage assisté IA** (`ExtractFormationFromDocuments`, `admin.formations.ai`). Suivi admin nominatif des inscrits fait.
Reste optionnel : lecteur visio inline. Détails en [§11 de PASSATION.md](PASSATION.md).

### Cycle de vie d'une relecture (2026-09-27)

Une relecture n'est plus un simple « assignée / rendue » : `Review::STATUSES` =
**`invited`** (confiée, sans réponse) → **`accepted`** / **`declined`** →
**`completed`**, plus **`cancelled`** (retirée par le comité).

- **Une place n'est occupée que par un statut actif** (`Review::ACTIVE_STATUSES`
  = invited/accepted/completed) : un refus ou une annulation libère la place et
  la soumission redevient à couvrir dans l'**attribution auto**. ⚠️ Le relecteur
  qui a refusé reste néanmoins **exclu** du plan (on ne le re-sollicite pas tout
  seul) - c'est le comité qui décide, le moteur ne fait que proposer.
- **Échéance réelle** : `due_at` est posée à l'assignation depuis
  **`ReviewGrid::dueDays($edition)`** (réglage `edition.settings['review_due_days']`,
  champ dans l'onglet **Relecteurs** de la fiche d'édition ; repli
  `Review::DEFAULT_DUE_DAYS` = 21 j). ⚠️ Ce réglage vit à la **racine** de
  `settings` comme `review_criteria`, **pas** dans le sac `doc` d'`EditionSettings`
  (réservé aux documents).
- **« En retard » ≠ « pas encore rendue »** : `Review::isLate()` = encore due
  **et** échéance dépassée. `EditionReport::reviewStats()` distingue donc
  `pending` / `late` / `declined` (avant, tout ce qui n'était pas `completed`
  était compté « en retard »).
- **Traçabilité** : `assigned_at`, `assigned_by_id`, `assign_reason` (la
  justification du moteur d'attribution auto **suit la paire** jusqu'à la
  relecture), `responded_at`, `decline_reason`.
- **Côté relecteur** (`/relectures`) : boutons **Accepter / Refuser** (motif
  facultatif, slideover) sur une invitation ; l'évaluation n'est proposée
  qu'ensuite. Rendre une évaluation **vaut acceptation** (`responded_at` posé) et
  une relecture `declined`/`cancelled` **n'est plus évaluable** (422).
- ⚠️ **Retirer une assignation n'efface rien** :
  `Admin\SubmissionController::removeReview` passe la relecture en `cancelled`
  (elle reste dans l'historique, hors compteurs) et **notifie le relecteur**. Ne
  pas revenir à `$review->delete()` : qui a été sollicité, et quand, fait partie
  du dossier.
- Phrase d'état partagée : **`Review::summarize($reviews)`** → « 2 relectures
  demandées - 1 rendue, 1 en retard de 3 j (1 refus) », affichée sur la fiche
  admin de la soumission.
- Notification staff dédiée : kind **`review_response`** (acceptation / refus).
- Tests : `tests/Feature/ReviewLifecycleTest.php`.

- **Cohérence des pages de relecture** (2026-09-27) : le vocabulaire et la
  grammaire sont les mêmes partout. Liste des soumissions → colonne
  **Relectures** (`rendues/demandées` + retard, via `Review::progressOf()`) ;
  fiche d'une soumission → `Review::summarize()` + état/échéance par relecture ;
  **Attribution auto** → couverture calculée sur les seules relectures actives ;
  **Rapports** → `pending`/`late`/`declined` distincts ; **espace relecteur**
  (`/relectures`) → sous-navigation `?tab=relectures|candidatures`, KPI
  **disjoints** (à confirmer + à évaluer + évaluées + refusées = total) et barre
  de filtres identique aux listes admin. L'**e-mail d'invitation** annonce
  l'échéance et la possibilité de refuser.
- **Conflit d'intérêts « même institution » : IGNORÉ par défaut** (case cochée
  dans l'onglet **Options** de la fiche d'édition,
  `settings['options']['coi_ignore_same_institution']`, lue par
  `ConflictOfInterest::ignoresSameInstitution()` - **défaut `true`**). La
  plateforme sert une université unique : la règle signalerait presque toutes
  les paires, elle n'a de sens que si on la **réactive** (décocher). Les autres
  règles restent actives. ⚠️ Défaut à `true` **côté moteur ET côté formulaire** :
  les deux doivent rester d'accord. Les tests qui veulent EXERCER la détection
  « même institution » posent explicitement `coi_ignore_same_institution => false`
  (cf. `ConflictOfInterestTest`, `ReviewLifecycleTest`).
- **Kit Tailwise = les DEUX back-offices** : les cartes KPI (`.ukc-stats`,
  `.ukc-stat-card`, `.lbl`/`.val`/`.hint`), la barre de filtres (`.filter-bar`)
  et la taille compacte `.ukc-btn--sm` étaient scopées à `.admin-page` ; elles le
  sont désormais à **`.ukc-shell`**, donc l'espace participant en hérite. ⚠️ Ne
  pas re-scoper ces règles à `.admin-page` : l'espace participant retomberait sur
  la carte centrée d'origine et sur la grille 4 colonnes du **site public**
  (`public.css` définit `.ukc-stats` sans scope).
- **Bulle du chatbot** : elle est fixée en bas à droite, là où les tableaux
  posent leur colonne « Action » et les slideovers leurs boutons. Deux garde-fous
  dans `resources/js/app.js` : `initChatbotOverlayGuard()` la masque tant qu'une
  surface modale est ouverte (`body.ukc-overlay-open`), et
  `initChatbotPoliteness()` l'efface (`.is-shy`) quand elle recouvre une action,
  la ramenant dès que le pointeur s'approche. Son `z-index` est **55** (au-dessus
  du contenu, sous slideover 60 / confirmation 90 / modales 100) - ne pas le
  remonter.
  - ⚠️ **Le décor de page reste sous 55.** `.ukc-nav-bar` (barre collante de la
    page colloque) était à **100**, le rang des modales : elle **tranchait** le
    panneau ouvert du chatbot, une bande blanche en travers de la conversation,
    et « Mon espace » / FR-EN paraissaient flotter par-dessus. Ramenée à **50**,
    l'étage de `.site-header` - c'est du décor, pas une surface modale. Quand un
    élément collant masque une surface flottante, **c'est presque toujours lui
    qui est trop haut**, pas la surface qui est trop basse : remonter le chat
    l'aurait fait recouvrir les modales, ce qu'un correctif antérieur avait
    précisément défait.

- **Vocabulaire de la relecture (un seul mot par chose)** : la **tâche** est une
  **« relecture »** (statuts `Review::STATUSES` : *Invitée / Acceptée / Refusée /
  Rendue / Annulée*), son **contenu** une **« évaluation »** (grille,
  recommandation, « Évaluer », « Grille d'évaluation », « Délai d'évaluation »).
  ⚠️ **Ne pas réintroduire « avis »** : c'était un troisième mot pour les mêmes
  objets, qui cohabitait avec les deux autres sur les mêmes écrans (« Avis
  rendus » à côté de « Rendue »). Les compteurs disent **« Rendues »**, jamais
  « Évaluées » ni « Avis rendus ».
- ⚠️ **« Relecture en attente » ≠ « pas encore rendue »** : un
  `where('status', '!=', 'completed')` compte aussi les relectures **refusées**
  et **annulées** - le relecteur garde un badge pour un travail qu'il n'a plus à
  faire et le comité voit une charge fantôme. Passer par
  **`Review::PENDING_STATUSES`/`isPending()`** (encore dû),
  **`ACTIVE_STATUSES`/`isActive()`** (occupe une place) ou **`isLate()`**. Le
  motif a déjà été écrit à trois endroits : un **test de garde**
  (`ReviewLifecycleTest`, « ne déduit jamais l'attente de son inachèvement »)
  scanne `app/` et `resources/views/` et échoue s'il revient.

### La file de travail du comité : le filtre « Évaluations » (2026-10-21)

La liste des soumissions portait la colonne **Évaluations** (« 1/1 », via
`Review::progressOf`) mais **aucun filtre ne portait dessus** : pour trouver ce
qui attend une décision, le comité filtrait « En évaluation » et lisait les
`0/1` et `1/1` à l'œil, page après page. Tenable sur quarante communications,
pas sur plusieurs centaines.

- **`Submission::scopeReviewsState()`** est la source unique du filtre, et
  `Submission::REVIEW_STATES` sa liste de choix : **Toutes rendues** (la file de
  décision), **Partiellement rendues**, **Aucune rendue**, **En retard**.
  ⚠️ Les trois premiers **partitionnent** la liste ; `late` les recoupe, c'est
  une alerte, pas une quatrième tranche.
- ⚠️⚠️ **ON NE DÉDUIT JAMAIS L'ATTENTE DE L'INACHÈVEMENT** (le piège d'au-dessus,
  en version requête) : « toutes rendues » = au moins une **rendue** et **aucune
  encore due**, par `Review::PENDING_STATUSES` - un `status != 'completed'`
  retiendrait une communication dont une évaluation a été **refusée** ou
  **annulée**, alors que plus personne ne lui doit rien. Un cas de garde porte
  exactement ce jeu d'essai (une rendue + un refus + une annulation), et un
  autre compare le filtre à ce que la **colonne affiche** pour le même lot :
  les deux doivent dire la même chose, sinon on filtre sur autre chose que le
  « 1/1 » qu'on lit à l'écran.
- ⚠️ Une valeur inconnue de `?reviews=` est **ignorée** : appliquée, elle
  viderait la liste et ferait apparaître « Réinitialiser » pour un filtre que
  la barre ne montre nulle part - l'écran dirait deux choses contraires.

**Les indicateurs du haut sont CLIQUABLES** (`SubmissionController::indicateurs()`,
un `<a class="ukc-stat-card">` par carte) et une sixième carte est apparue :
**« À décider »** = encore dans le circuit **et** toutes les évaluations rendues.
C'est le seul indicateur qui appelle un geste ; les autres décrivent un état.

- ⚠️⚠️ **UNE CARTE CLIQUABLE DOIT OUVRIR EXACTEMENT CE QU'ELLE COMPTE.**
  « En revue » additionne **deux** statuts (déposée + en évaluation) : visée par
  un `status=under_review`, elle rendait **moins de lignes que son propre
  chiffre**, et un total qui baisse sans explication se lit comme une panne.
  D'où le filtre composite **`Submission::STATUS_IN_REVIEW`**.
  ⚠️ **Le cas de garde doit SUIVRE le lien de la carte**, jamais reconstruire
  ses paramètres : écrit à la main, il éprouve le filtre et laisse le lien
  mentir - c'est exactement ce qui est arrivé, le premier sabotage est passé au
  vert avant correction du cas.
- ⚠️ Les chiffres restent calculés sur le **périmètre de l'événement**, pas sur
  les filtres courants : ce sont des repères de navigation. S'ils suivaient le
  filtre, la carte qu'on vient de cliquer afficherait le total de sa propre
  sélection et les autres tomberaient à zéro.
- ⚠️⚠️ **MAIS LE LIEN D'UNE CARTE EMPORTE LES AUTRES FILTRES** (`$contexte` :
  axe, type, présentation, participation, recherche, non-rattachés). Il ne
  portait que l'événement et son statut : on filtrait un axe, on cliquait une
  carte, et **l'axe disparaissait sans un mot** - mesuré, chaque lien revenait
  à `?event_id=4&status=accepted`. Une carte change le statut, elle ne défait
  pas le travail de qui l'a cliquée.
  ⚠️ **Et cliquer la carte ALLUMÉE l'éteint** (`$active ? [] : $filtre`) :
  c'est la sortie du filtre sans passer par « Total », qui emporterait aussi
  l'axe qu'on regardait.
  ⚠️ **Conséquence : la rangée est une RÉGION ÉCHANGÉE**
  (`data-filter-part="submissions-kpis"`). Les chiffres ne bougent pas, mais
  les liens suivent les filtres : hors région, les six cartes resteraient
  pointées sur l'axe précédent après un filtre en AJAX, et cliquer l'une
  d'elles y ramènerait.
- ⚠️⚠️ **LA LISTE DÉROULANTE DES STATUTS A ÉTÉ RETIRÉE** (les cartes la disent
  déjà, en annonçant leur nombre), **mais le statut reste un CHAMP CACHÉ du
  formulaire** - comme l'événement. La barre poste **tout** le formulaire : sans
  ce champ, taper une recherche ou changer d'axe reperdrait le statut posé par
  la carte, et la liste se réélargirait sous les doigts. Un cas de garde lit le
  formulaire rendu et exige le champ (sabotage vérifié).
  ⚠️ **Prix assumé, puis RENDU** : `submitted` et `under_review` ne se
  séparaient plus à l'écran (mesuré sur le colloque de l'eau : **19 déposées**
  contre **27 en évaluation**, fondues dans « En revue » = 46).
- ⚠️⚠️ **« DÉPOSÉES » / « EN ÉVALUATION » REVIENNENT EN PASTILLES CONTEXTUELLES**
  (`SubmissionController::sousStatuts()`, grammaire `.ukc-nav-docs` +
  `.nav-count`, déjà stylée et pourvue d'un état actif). Trois pastilles -
  *Toutes / Déposées / En évaluation* - **sous la rangée, et seulement quand on
  est dans « En revue »** : une septième carte permanente ferait une rangée de
  plus pour un filtre qu'on ne regarde que depuis celle-là, et une seconde
  liste déroulante ferait deux commandes pour un même filtre.
  ⚠️⚠️ **ELLES SE COMPTENT SOUS LES FILTRES COURANTS, À L'INVERSE DES CARTES.**
  Une carte est un repère sur tout l'événement ; une pastille **subdivise la
  sélection affichée**, et son lien emporte l'avancement des évaluations comme
  le reste de la barre. Comptée sur le périmètre entier, « Toutes » annoncerait
  46 là où son lien en ouvre 6 - exactement le travers que « En revue » a déjà
  payé. D'où `filtre($request, $query, avecStatut: false)` : le statut courant
  est écarté du comptage, sinon « Déposées » tomberait à zéro dès qu'on regarde
  « En évaluation » et la pastille d'à côté deviendrait injoignable. La requête
  n'a lieu **que si la rangée s'affiche**.
  ⚠️ **Une carte reste allumée sur ses SUBDIVISIONS** (clé `allume`) : raffiner
  « En revue » en « Déposées » ne doit pas éteindre la rangée, sinon on se
  retrouve dans un filtre que plus aucune carte n'annonce.
- ⚠️ La carte allumée nomme son sélecteur **avec son élément**
  (`.ukc-shell a.ukc-stat-card.is-on`, soit (0,2,1)) : `tailwise.css` peint la
  même carte à (0,2,0) **et** se charge après `admin.css` - à spécificité égale,
  l'état allumé ne se verrait jamais. Même piège que `.tdb a.tdb-btn-light`.
- ⚠️ **Les filtres de la liste et de ses EXPORTS ont une seule construction**
  (`SubmissionController::filtre()`) : écrits deux fois, ils ont déjà divergé
  ailleurs, et le CSV exportait autre chose que ce qu'on regardait. Un cas de
  garde sort le CSV sous filtre et vérifie qui s'y trouve.
  ⚠️⚠️ **ET L'ADRESSE DU LIEN CSV AUSSI** (`$exportUrl`, bâti dans le
  contrôleur depuis `$contexte` + statut + évaluations). La vue la composait à
  la main par un `request()->only(...)` qui avait **oublié deux paramètres** -
  `reviews` et `unlinked` : `filtre()` les lit tous les deux, seul le lien ne
  les écrivait pas, donc exporter depuis « Toutes rendues » sortait **toute la
  sélection de statut**, sans que rien ne le signale. Une seule source, elle ne
  peut plus diverger.
- ⚠️⚠️ **« RÉINITIALISER » A SA PROPRE RÉGION ÉCHANGÉE**
  (`data-filter-part="submissions-reset"`), comme la règle du projet l'exige
  depuis toujours - et il ne l'avait pas : dernier enfant du `<form>`, il était
  **hors de toute région**, donc il ne se montrait qu'au prochain rechargement
  complet. Mesuré : taper « eau » filtrait bien la liste et l'URL, et le lien
  restait absent - on ne pouvait plus sortir du filtre sans vider les champs un
  par un.
  ⚠️ **Une région IMBRIQUÉE dans le formulaire est sans danger**, malgré la
  règle « le formulaire reste hors des régions échangées » : `app.js` ne
  remplace que le **contenu** de la région (`courante.innerHTML =
  fraiche.innerHTML`), donc aucun champ n'est touché et le curseur de qui tape
  reste en place - c'est la raison même de cette règle.
- **Mesuré à l'écran** (1440 px, vrai colloque de 210 communications) : les six
  cartes tiennent sur **une rangée**, « À décider » annonce 6 et son lien rend
  **6 lignes**, le filtre s'applique **sans recharger la page** (`data-filter`)
  et l'URL suit. À 375 px : trois rangées de deux, aucun débordement horizontal.
  ⚠️ Sous ~1200 px la grille partagée `.ukc-stats` retombe à quatre colonnes et
  la sixième carte ouvre une rangée orpheline (4 + 2) - le travers d'`auto-fit`
  déjà documenté, qui existait avec cinq cartes (4 + 1). La grille est partagée
  par tout le back-office : ne pas la retoucher pour cette seule page.
  ⚠️ La **barre de filtres défile chez elle** (`overflow-x: auto`, le patron du
  projet) : mesurée à 1440 px, elle réclame **1463 px pour 1280** - donc moins
  qu'avant l'ajout du filtre, le retrait de la liste des statuts ayant rendu
  269 px. Ne pas y ajouter un contrôle sans regarder ce chiffre. ⚠️ C'est
  **pourquoi les pastilles de sous-statut sont posées au-dessus**, avec les
  cartes, et non dans la barre : elles y auraient repris les 269 px rendus.
- **Mesuré de nouveau après la reprise des filtres** (colloque de l'eau, 210
  communications) : « En revue » 46 = **19 déposées + 27 en évaluation**, et
  chaque pastille ouvre exactement son chiffre (19, 27, 46 lignes). Sous
  « À décider » elles **se recomptent** - 6 / 0 / 6 - au lieu d'annoncer 46
  pour 6 lignes. Taper « eau » fait **apparaître « Réinitialiser » sans
  rechargement**, le curseur reste dans le champ (position 3 sur 3 caractères)
  et les six cartes reprennent `search=eau` ; la carte allumée, elle, retombe
  sur `?event_id=4&search=eau`. Le lien CSV porte `reviews=done`. À 375 px,
  aucun débordement (`scrollWidth` = `clientWidth` = 375 ; `body` 417 = 375/0,9
  à cause de `html.ukc-dense`) et les trois pastilles s'enroulent sur deux
  lignes. Contraste de la pastille allumée : 9,16:1 pour le libellé, **4,93:1
  pour son compteur** une fois son alpha composé sur le bleu - ⚠️ mesuré sans
  composer l'alpha, on lit « 1:1 » et on croit à un défaut.
- Gardes : **`FileDuComiteTest`** (21 cas, **12 sabotages vérifiés**).
  ⚠️ **Deux cas ne mordaient pas**, et c'est le sabotage qui l'a montré : ils
  extrayaient une région par `Str::after('data-filter-part=…')`, qui rend la
  **chaîne entière** quand l'aiguille manque - retirer la région laissait donc
  le cas au vert. On exige la présence de l'attribut **avant** d'en lire le
  contenu. Même famille de piège que les deux cas de `CookiesTest`.

- **Module Soumissions = cinq onglets** (2026-09-27) : *Soumissions - Formulaire
  de soumission - Attribution auto - Relecteurs & expertise - Options*, définis
  **une seule fois** dans `resources/views/admin/submissions/_nav.blade.php`
  (⚠️ ajouter un onglet = un seul fichier à éditer ; chaque page passe sa clé via
  `['current' => '…']`). Chaque page porte le **sélecteur d'édition** puis la
  sous-navigation, comme partout ailleurs.
  - **Relecteurs & expertise** (`admin.submissions.reviewers`) : matrice des axes
    évaluables + charge (relectures actives seulement). L'ajout d'un évaluateur
    se fait ici au niveau de l'événement, avant toute assignation :
    `admin.evenements.reviewers.store` crée ou promeut sa participation et peut
    enregistrer ses axes. Il devient alors sélectionnable sur les fiches de
    soumission et dans l'attribution automatique ; l'invitation part lors de
    l'assignation, pas lors de l'ajout au vivier. La liste reste visible même
    lorsque l'événement n'a pas encore d'axes. L'ajout s'ouvre dans
    `<x-ukc.slideover>` ; la recherche par e-mail, prénom ou nom partage
    `UserDirectorySearch` avec le Programme. La sélection d'un compte renseigne
    l'identité et l'affiliation ; le serveur vérifie l'e-mail lié et reprend les
    noms du compte lorsqu'il existe.
  - **Options** (`admin.submissions.options`) : **toutes** les règles de l'édition
    pour ce module - *dépôt* (limites du formulaire), *attribution* (relecteurs
    visés, charge max), *évaluation* (grille, délai).
  - ⚠️ La fiche d'édition **n'a plus** d'onglet Relecteurs/Évaluation, et
    l'éditeur de formulaire **n'a plus** d'onglet « Règles & limites » : ces
    réglages vivent au plus près de ce qu'ils pilotent. Les formulaires postent
    toujours sur les routes `editions_*` (capacité **`editions_write`**) et
    s'affichent en **lecture seule** pour qui ne l'a pas.
  - Réglages à la racine de `edition.settings` : `review_target`,
    `review_max_load`, `review_criteria`, `review_due_days` (`ReviewGrid`) ;
    les limites de dépôt restent dans `settings['submission_form']['rules']`.
  - ⚠️ **`admin.editions.form.update` reconstruit champs + champs personnalisés**
    depuis la requête : ne jamais y poster les seules règles (elles seraient
    perdues). Endpoint dédié **`admin.editions.form.rules`** ; un test de garde
    (« ne perd pas les règles quand on enregistre les champs ») le protège.
  - **Éditeur du formulaire = enregistrement AUTOMATIQUE, sans bouton** (Alpine
    `formEditor`, `submission-form-editor.blade.php`) : l'état vit entièrement
    côté client ; **toute** action (réordonner un champ, ajouter, éditer,
    supprimer) **poste en AJAX** sur `admin.editions.form.update` (qui répond en
    **JSON** quand `wantsJson()`) et affiche un toast. Il n'y a **plus** de bouton
    « Enregistrer » (ni en haut ni en bas). L'**édition d'un champ** se fait dans
    un **slideover** (`<x-ukc.slideover>`, même schéma que « Nouvelle tâche » du
    Projet) : ouvrir → modifier → *Enregistrer* commit + persiste ; *Supprimer*
    (champ personnalisé) dans le pied du panneau. Ne pas réintroduire un `<form>`
    global avec bouton submit.
- ⚠️ **Un axe thématique se compare par CLÉ, jamais par chaîne brute** :
  l'expertise d'un relecteur stocke une **copie** du libellé de l'axe, et ce
  libellé est du texte libre. Un tiret cadratin recopié suffisait à faire échouer
  l'égalité stricte **en silence** - en base, les axes des relecteurs portaient un
  tiret **cadratin** et ceux de l'édition un tiret simple, donc l'attribution
  automatique proposait *systématiquement* « hors axe ». Passer par
  **`ReviewerExpertise::covers()`** / **`axisKey()`** (tirets typographiques,
  casse et espaces normalisés) ; l'enregistrement **recanonise** sur le libellé de
  l'édition, donc un axe renommé ne casse plus l'expertise en silence.
- **En-tête d'une page admin** : `title` = le nom de l'onglet, `subtitle` = une
  courte indication de ce qu'on y fait (« Communications & résumés »), **jamais**
  le nom de l'édition - il est déjà dans le sélecteur juste en dessous, et il
  faisait passer l'en-tête sur trois lignes. ⚠️ `subtitle` est une valeur PHP
  échappée à l'affichage : y écrire `&amp;` produit un `&amp;amp;` visible.
  - ⚠️ **La barre d'actions doit pouvoir SE COMPRIMER.** `.head-actions` est
    `flex-shrink:0` pour tenir sur la ligne du titre ; en dessous de **640px** une
    media query la rend de nouveau compressible (`flex-shrink:1; min-width:0`).
    Sans cela elle garde sa largeur idéale et **toute la page défile latéralement** :
    sur « Modifier l'événement », « ← Liste / Importer / Exporter / Enregistrer »
    réclamait 367px dans un corps qui en offre 354 (`scrollWidth` 402 pour un
    `clientWidth` de 375). Compressible, elle enroule ses boutons toute seule.
    Même règle que la barre de la salle de visio : rien ne doit se comprimer jusqu'à
    zéro, **mais rien ne doit non plus refuser de se comprimer**.
  - ⚠️ **Jamais la mise en page de l'en-tête en style EN LIGNE** : un style en ligne
    l'emporte sur **toute** règle de la feuille, **media query comprise**. Un
    `flex-wrap:nowrap` posé sur la balise neutralisait l'adaptation étroite sur cette
    seule page, et une barre d'actions recopiée en ligne au lieu de porter
    `.head-actions` n'était joignable par aucune règle. L'en-tête sans la carte
    blanche est la **variante `.ukc-admin-head--plain`**, pas un `style=`.
  - **Se mesurer, pas se juger à l'œil** : `document.documentElement.scrollWidth`
    vs `clientWidth` à 375px. ⚠️ `html.ukc-dense` (`zoom:.9`) fausse la lecture
    naïve - **`body.scrollWidth` vaut 417 (= 375/0.9) SANS aucun débordement**.
- **Débordement horizontal du back-office : les quatre causes déjà rencontrées.**
  Un seul élément trop large fait défiler **toute la page** ; la sonde utile liste
  les débordants dont **aucun ancêtre ne déborde** et **ignore** ceux qui vivent
  dans un conteneur `overflow-x:auto` (eux défilent chez eux, c'est voulu).
  - ⚠️ **`min-width:auto` sur un élément de grille ou de flex** : sa largeur
    minimale est celle de son **contenu**, et une piste `1fr` s'étire pour
    l'absorber. Le plateau de planification (840px) gonflait la colonne à 841px
    dans un conteneur de 332 - alors que `.plan-scroll` savait déjà défiler.
    D'où **`.layout-plan > * { min-width: 0 }`** : c'est la ligne qui rend le
    défilement au bon élément. Vrai aussi pour les `<input>`, dont le `min-width`
    automatique vaut la **taille intrinsèque** (189px), jamais zéro.
  - ⚠️ **Un conteneur qui s'enroule n'enroule pas ses enfants** : `.ukc-pagination`
    était `flex-wrap:wrap` mais `.ukc-pagination__list` restait `nowrap` et gardait
    ses 479px. Au-delà d'une dizaine de pages, /admin/soumissions et
    /admin/participants défilaient. **La liste aussi doit s'enrouler.**
  - ⚠️ **Une pastille qui porte une PHRASE ne peut pas être `nowrap`** :
    `.admin-page .ukc-pill` l'est à juste titre (« Soumissions ouvertes » ne se
    coupe pas), mais les pastilles de `.chip-list` portent le nom complet d'un axe
    et réclamaient 386px. Exception explicite, `overflow-wrap` compris.
    - ⚠️ **Et la réciproque vaut DANS UNE TABLE** : une pastille d'état coupée en
      deux (« Correction / demandée », colonne Statut de `/communications`) double
      la hauteur de sa rangée. `.ukc-shell .ukc-table .ukc-pill` est donc `nowrap`,
      et la cellule d'actions `.ukc-cell-actions` avec elle - elle tenait sa ligne
      par un `style=` en ligne, qui l'emporte sur toute media query. Les pastilles
      à phrase ne vivent pas dans une table : la règle ne les atteint pas.
      Garde : `AccountSubmissionTest`, qui lit la feuille **commentaires retirés**.
  - ⚠️ **Une barre dont la hauteur sert d'ancrage est une VARIABLE** : les
    concepteurs (`template-designer.css`, `badge-designer.css` - **deux fichiers
    jumeaux, corriger les deux**) accrochent `.ed-shell` à
    `calc(100vh - var(--edtop))`. Écrite en dur, la hauteur laissait la scène
    décalée dès que la barre passait sur deux lignes. Même leçon que `--vtop` /
    `--vtoolbar` dans la salle de visio.

- ⚠️ **Listes dans le chat** : `ukcMarkdownLite` produit de vrais `<ul>`/`<ol>`,
  mais le **reset Preflight de Tailwind** met `list-style: none` sur toutes les
  listes. Les trois surfaces de chat (`.admin-ai` copilote, `.gesco-chat` page
  `/assistante`, `.ukc-cb-msg` bulle flottante) doivent donc **rétablir**
  `list-style: disc|decimal outside` - sinon il ne reste qu'un texte indenté,
  sans puce ni numéro. Même piège pour toute liste stylée à la main.

### Édition archivée = lecture seule pour les participants

**`App\Support\EditionLock`** : `frozen($edition)` (statut **`archived`**) et
`guard($edition)` (403 explicite). Appelé dans les actions d'**écriture** de
l'espace participant : dépôt et corrections d'une communication
(`Account\SubmissionController::authorizeOwnership`, point de passage des cinq
actions), relecture (`submit`, `respond`, candidatures), rapport de séance,
paiement (`pay`), inscription à une formation.

- ⚠️ **Le déclencheur est `archived`, pas `closed` ni la date de fin.** `closed`
  signifie « dépôts clos » : c'est précisément la période où les relecteurs
  travaillent. Une édition dont les dates sont passées mais non archivée peut
  encore recevoir une relecture ou une correction. L'archivage est la
  décision explicite qui fige le dossier.
- ⚠️ **Le gel n'enlève jamais la LECTURE** : le relecteur consulte l'évaluation
  qu'il a rendue (bouton « Consulter », panneau `inert`, pas d'enregistrement),
  le rapporteur relit son rapport (branche « verrouillé » de la vue), le
  participant garde la page de paiement et son reçu. On masque les actions et on
  dit pourquoi - on ne renvoie pas un 403 sur un bouton resté cliquable.
- Le **back-office n'est pas concerné** : l'admin et les gestionnaires gardent la
  main (corriger une décision, émettre une attestation oubliée, rembourser). Leurs
  droits restent portés par le RBAC par édition.
- Tests : `tests/Feature/ArchivedEditionLockTest.php`.

### Identité d'événement FIGÉE à l'archivage (documents authentiques)

Les badges se dessinent **en direct** depuis le compte (nom, photo, affiliation…) :
sans gel, une correction de profil **après la clôture** retouchait rétroactivement
les badges d'un événement terminé (badges erronés, attestations incohérentes). À
l'**archivage** (`status = archived`, le même déclencheur que `EditionLock`), on
**gèle** l'identité des participants.

- **Instantané par participant** : `participants.meta['identity']` (nom, civilité,
  grade, affiliation, téléphone, e-mail, `photo_path`), écrit par
  **`App\Actions\FreezeEventIdentities`**. La **photo est RECOPIÉE** dans
  `event-identity/{event}/{participant}.ext` (disque public) : sans copie, une
  suppression/un remplacement ultérieur de la photo de profil casserait le badge
  gelé.
- **Déclencheur = hook sur le modèle `Event`** (`booted()` → `updated`, transition
  vers `archived`) : couvre TOUT chemin (formulaire, import, commande), synchrone
  et tolérant (un échec de gel n'empêche pas l'archivage). ⚠️ **Écrase** l'instantané
  existant à chaque archivage → désarchiver / corriger / ré-archiver **applique** la
  correction. Regel manuel : `php artisan events:freeze-identities {event?}`.
- **Résolveur unique = `Participant::eventIdentity()`** (et `displayName()` /
  `displayInstitution()` / `displayPhotoUrl()`) : l'instantané s'il existe
  (`isIdentityFrozen()`), sinon le direct (`liveIdentity()` : compte d'abord, repli
  colonnes du participant). ⚠️ **Tous les rendus de badge lisent ce résolveur** -
  `BadgeCatalog::recipients()`/`forAccount()`/`withCurrentAccountNames()` ET
  `Account\DocumentController::renderBadge()` (qui lisait nom/photo en direct). Avant
  archivage, `displayName()` == l'ancien `accountName($user)` → comportement inchangé.
- ⚠️ **Les ATTESTATIONS figent leur identité à l'ÉMISSION, pas à l'archivage** : un
  document signé dit ce qu'il disait le jour de sa délivrance. `recipient_name` était
  déjà une colonne figée (et c'est elle qui est **signée**, cf. `AttestationCertifier`) ;
  `IssueAttestation` fige désormais aussi **civilité + affiliation** dans `meta`
  (`recipient_civility`/`recipient_institution`), et `AttestationVars` les lit depuis
  `meta` (repli direct pour les attestations antérieures). Indépendant de l'instantané
  du participant.
- ⚠️ **Le LIVRE DES RÉSUMÉS n'en dépend pas** : `LivretDocx` lit les auteurs **saisis
  à la soumission** (`submission.authors`), pas le compte - il est donc déjà figé à la
  soumission (seule une harmonisation IA de casse peut s'appliquer).
- ⚠️ **`?->` INTERDIT sur `Participant::user`** dans ce code : Larastan type la
  relation BelongsTo comme non-nulle → `$user?->x` déclenche `nullsafe.neverNull`. On
  passe par une branche explicite `if ($user)` (cf. `liveIdentity()`).
- Tests : `tests/Feature/EventIdentityFreezeTest.php`.
- ⚠️ **Téléphone = identifiant UNIQUE** : `users.phone` porte un index unique, et
  **`App\Support\Phone::normalize()`** en donne la forme canonique (séparateurs
  retirés, `+` conservé ; **aucun indicatif deviné**). La normalisation est
  appliquée par le **mutateur `User::phone`** et **avant** chaque contrôle
  d'unicité - sinon « +228 90 01 52 84 » passerait la validation puis
  entrerait en collision à l'écriture. Trois écrans écrivent ce champ et doivent
  tous porter la règle : **inscription**, **profil** et **2FA par SMS**
  (`SecurityController::sendSmsSetupCode`, sans quoi on s'appropriait le numéro
  d'un tiers). `AuthorMatcher::normPhone()` délègue à la même classe : comparer
  et stocker suivent la même règle.
  Pourquoi : le téléphone est un **signal fort** de rapprochement (auteurs,
  formateurs) et le serveur **impose** ensuite l'identité du compte trouvé - un
  doublon rattachait donc une communication au mauvais compte, en silence.
  Diagnostic : **`php artisan users:phones`** (rapport, `--fix` pour normaliser).
  Tests : `tests/Feature/UserPhoneUniquenessTest.php`.
- **Module Programme = un seul écran, neuf onglets** : *Programme - Planning -
  Ateliers - Sessions - Créneaux - Espaces - Discussions - Affectation auto - Options*,
  pilotés par l'URL (`?tab=`) et définis **une seule fois** dans
  `resources/views/admin/program/_nav.blade.php`. Route canonique
  **`admin.program.index`** (`/admin/programme`). Onglet par défaut = **`apercu`**.
- ⚠️ **Modérateur / rapporteur d'une session = rattachés à un COMPTE par e-mail**
  (`event_sessions.moderator_email` / `rapporteur_email`) : c'est l'e-mail qui ouvre
  les espaces `/moderation` et `/rapports` de la personne. Le formulaire de session
  (`_modals.blade.php`) ne postait QUE le nom (`sModerator`/`sRapporteur`) : à chaque
  modification, `moderator_email` repassait à null et **le lien au compte était coupé**
  (le seed le posait pourtant). Désormais ces deux champs sont une **auto-complétion**
  (Alpine `searchPeople`/`pickPerson` dans le `x-data` de `admin/program/index.blade.php`,
  endpoint **`admin.program.people`** qui cherche les comptes par prénom/nom/e-mail) :
  choisir une personne fixe le nom ET envoie son e-mail dans un **champ caché**
  (`sModeratorEmail`/`sRapporteurEmail`, déjà lus par `storeSession`/`updateSession`).
  Un indice « Lié à … » / « Choisissez une personne… » dit si le lien est effectif.
  ⚠️ **Les TROIS payloads `openSession(...)` doivent porter `moderator_email`/
  `rapporteur_email`** (`$sessJsFor`, `$sessJs`, et l'inline du bouton « Modifier » de la
  table) : sinon le panneau rouvre « non lié » alors que la base a l'e-mail. Taper un nom
  à la main **sans** choisir une suggestion **ne lie personne** (l'e-mail est vidé à la
  saisie). Styles combobox : `.ukc-combo-menu` (en flux, pas absolu : le corps du
  slideover a `overflow-y:auto`). Tests : `ProgramPeoplePickerTest`.
  - **Onglet « Programme » (`?tab=apercu`, par défaut) = ÉDITEUR du programme
    synthétique** : un **titre** + des **jours** (`date` + intitulé libre) contenant des
    **lignes** typées (`type` = plénière/atelier/pause/autre, `start`, `end`, `label`,
    `highlight`). Repeater Alpine `programSynthesis` (`app.js`), `<form>` postant les
    tableaux imbriqués `days[i][date]` / `days[i][rows][j][…]`. Stocké dans
    **`edition.settings['program_synthesis']`** (`['title', 'days' => [['date','heading','rows' => [['type','start','end','label','highlight']]]]]`).
    - ⚠️ **C'est CE contenu saisi qui s'affiche** dans la section `#programme` de la
      page publique, **en onglets jour par jour** : admin (aperçu) et public incluent le
      **même partial** `resources/views/partials/program-synthesis.blade.php` (reçoit
      `$synthesis` ; charte UK `.ukc-tabs-scroll`/`.ukc-program-day`, `public.css` chargé
      aussi en admin donc rendu identique). Le contrôleur public passe
      `settings['program_synthesis']` en `$programSynthesis` ; section masquée si vide.
    - **Génération auto (bouton « Enregistrer & générer sessions/créneaux »)** :
      `saveSynthesis` (`admin.program.synthesis`) enregistre ; `generateFromSynthesis`
      (`admin.program.synthesis.generate`) enregistre PUIS crée les sessions/créneaux -
      une ligne *Plénière*/*Autre* datée → 1 session plénière ; une ligne *Ateliers en
      parallèle* → 1 session par atelier (salles distinctes) découpée en créneaux libres
      via `sliceSessionSlots()` (helper extrait de `generateSlots`, réutilisé) ; *Pause*
      ignorée. ⚠️ **Ne remplace QUE ce qu'il avait généré** : les ids créés sont mémorisés
      dans `settings['program_synthesis_generated']` et purgés au prochain « générer » ;
      les sessions/créneaux saisis à la main sont conservés. Le seed pose un exemple (sans
      lancer la génération : la base a déjà son planning détaillé).
  - ⚠️ Le programme vivait sur **deux pages** (`/programme` et
    `/programme/creneaux`) qui géraient les **mêmes ateliers et les mêmes
    sessions** avec deux interfaces, et la première n'était atteignable **ni par
    le menu ni par la palette** - alors qu'elle portait seule la **visio**.
    `admin.program.sessions|slots|reports` sont conservées en **redirection**
    (liens du secrétariat, des documents, signets) : ne pas les supprimer sans
    vérifier les appelants.
  - La **visio** d'une séance se déplie depuis l'onglet Sessions. ⚠️ Son panneau
    est une ligne **sœur** de la ligne de séance : l'état Alpine se pose sur un
    **`<tbody>` par séance** (un `x-data` sur la ligne ne couvre pas sa sœur).
  - « Non planifiées » est devenu la suite de **Créneaux**, « Paramètres
    créneaux » l'onglet **Options**, et l'**Agenda** (FullCalendar) se déplie
    dans **Planning**.
  - ⚠️ **Les rapports de séance appartiennent au SECRÉTARIAT**
    (`admin.secretariat.reports`, poste de travail unifié : lecture / amélioration /
    validation dans un panneau latéral ; la validation verrouille le rapport côté
    rapporteur). Le module Programme n'en porte **aucune copie** - deux écrans pour
    le même objet, c'est deux endroits où chercher. `admin.program.reports` redirige
    vers le Secrétariat.
  - Chaque page de sélecteur d'édition utilise **`currentEditionId()`** du trait
    `ScopesEditionAccess` (il était privé à un seul contrôleur).
- **Fiche d'édition : plus d'onglet « Programme »** (2026-09-28). **Dates clés**
  est passé dans l'onglet **Général**. ⚠️ L'ancien déroulé éditorial `settings['programme']`
  a été supprimé (personne ne le lisait). Le programme public (`#programme` de la page
  colloque) affiche désormais le **programme synthétique saisi** dans l'onglet
  **Programme** du module (`settings['program_synthesis']`), via le partial partagé
  **`partials.program-synthesis`** (`colloque.blade.php` passe `$programSynthesis`).
  - ⚠️ **Le public ne montre PAS le détail des communications** : le programme
    synthétique est un déroulé de haut niveau (cérémonies, pauses, « communications
    en ateliers »…), **pas** la liste des communications ni les modérateurs/rapporteurs.
    Ne pas réinjecter `$commsBySession` ni les rôles dans `#programme`. Test :
    `ProgramPublicTest` (`assertDontSee` le titre d'une communication).

- **BASE vs données de colloque : seeders scindés** (réamorçage ciblé). La BASE que
  possède le super-administrateur - rôles/permissions + compte super-admin
  (`RolesAndAdminSeeder`) et les **réglages** (`settings`) - est **séparée** des
  données de démonstration de colloque, regroupées dans **`ColloqueDemoSeeder`**
  (`ColloqueAvenirSeeder` + `ColloqueAvenirVolumeSeeder`). `DatabaseSeeder` appelle
  RolesAndAdmin **puis** ColloqueDemo.
  - **`php artisan colloques:reseed`** recrée les données de colloque **sans
    `migrate:fresh`** : il vide le domaine « colloque » (liste ordonnée enfant→parent
    dans `App\Console\Commands\ReseedColloques::EVENT_TABLES` : events, event_series,
    event_user, participants, submissions, slots, payments, reviews, formations,
    attestations, claims, meeting_messages, sessions/polls/questions, project_*,
    committee_members…), **purge les comptes de démo** (domaines `@test.tg` /
    `@avenir-durable.tg` / `@example.org`, `--keep-accounts` pour les garder) puis
    rejoue `ColloqueDemoSeeder`. ⚠️ **Ne touche JAMAIS** `settings`, le super-admin
    (`admin@univkara.tg`), les rôles/permissions, `tutorial_*`, `document_templates`,
    `emails`, `media`, `testimonials` : c'est tout l'intérêt vs `migrate:fresh --seed`.
    `--force` (sans confirmation / autorise en production, refusé sinon),
    `--class=` (rejouer un autre seeder de colloque). ⚠️ En AJOUTANT une table au
    domaine « colloque », l'ajouter à `EVENT_TABLES` (sinon elle survit au réamorçage) ;
    une table de **configuration** (réglage/design du super-admin) N'y va jamais.
    Tests : `tests/Feature/ColloqueReseedTest.php`.
- **Un seul colloque de démo, à l'échelle** : `migrate:fresh --seed` ne crée plus
  qu'**un** colloque (le VIIIe conjoint Parakou-Kara, `avenir-durable-2025`), au
  volume d'un vrai événement. `DatabaseSeeder` enchaîne `ColloqueAvenirSeeder`
  (colloque + édition **12 → 16 octobre 2026**, ateliers, staff, relecteurs,
  direct) puis `ColloqueAvenirVolumeSeeder` (**200 communications** réf. `AVD-###`,
  un correspondant **et des co-auteurs** chacune ; statuts variés ; créneaux pour les acceptées ;
  relectures dans tous les états ; **93 % de paiements de participation validés
  manuellement**, passerelle `manual`, reçu numéroté). Montants via
  `Fees::resolveAmount()`, grades via `ParticipantTypes::all()`. Seeders idempotents ;
  le seed prend ~45 s (200 comptes). Un test de garde vérifie les 200 et les 93 %.
  ⚠️ **Les titres de communication NE portent PAS de numéro « (N) » en suffixe**
  (`title()` rend l'intitulé seul, l'unicité vient du `ref_code` `AVD-###`), et le co-auteur
  non inscrit a une affiliation **propre** (« Laboratoire associé », jamais « (hors plateforme) ») :
  ces deux artefacts ressortaient tels quels dans le livret/programme et ne figurent pas dans le
  document de référence.
  ⚠️ Les anciens seeders de colloques de démo (Showcase, Live, DemoColloque,
  DemoScenario, DemoStaff) ont été **supprimés** : ce colloque est la référence unique.
  - **Module Formations** semé (`ColloqueAvenirSeeder::buildFormation()`) :
    `settings['formation_enabled'] = true` + **une formation publiée** (« Méthodologie
    de la recherche… », **payante 10 000 FCFA**, jeu. 15/10 14h-17h, visio `scheduled`),
    **deux formateurs** (`formateur1@test.tg` modérateur, `formateur2@test.tg`) et
    **6 inscrits** pris parmi les auteurs - **4 réglés** (paiement manuel de nature
    `formation`, reçu `REC-FORM-…`) et **2 inscrits non réglés**. Idempotent. De quoi
    exercer l'espace Formations admin/participant et la page publique `#formations`.
  - **Le cas d'essai du PAIEMENT : accepté, mais pas réglé**
    (`ColloqueAvenirSeeder::buildCommunicantNonRegle()`). **M. TOURE Abdoul**
    (`tabdoulhaq@gmail.com`, mot de passe `password`), **étudiant en master**, a une
    communication **acceptée** (`UK-2025-007`, axe 5) et **aucun paiement** : son tarif
    se déduit de son grade, soit **100 FCFA**. Les six comptes `commN@test.tg` réglaient
    tous sauf un, mais au grade « Doctorant » et avec leur communication **programmée** -
    il manquait ce parcours-là.
    - ⚠️ **Son profil est COMPLET** (prénom, nom, **téléphone**, affiliation, e-mail
      vérifié) : sans cela `User::profileComplete()` est faux et la plateforme refuse
      d'engager le paiement - le cas serait bloqué avant la passerelle.
    - ⚠️ **Le grade est porté DEUX fois** : `users.grade` (profil) et
      `participants.title` (inscription). C'est le **second** que lit
      `Fees::resolveAmount()`.
    - ⚠️ **Rien n'est au programme** pour lui, et c'est cohérent : son inscription n'est
      pas confirmée. Sa référence est hors de la série `AVD-` du seeder de volume, dont
      les gardes vérifient que les 200 acceptées sont toutes planifiées.
    - ⚠️ **`users.phone` est un identifiant UNIQUE**, et le numéro était saisi À LA MAIN
      sur le super-administrateur de la base de développement : posé tel quel, le
      scénario tombait sur la contrainte d'unicité et **aucune** donnée de démonstration
      ne se semait. Arbitré avec l'équipe : le numéro va à ce compte (il lui faut un
      numéro joignable), l'autre le perd, et le seeder **le dit** (`command->warn`) -
      un numéro retiré sans un mot est une surprise, et le super-administrateur peut y
      tenir sa double authentification par SMS.
    - Garde : `CompteNonRegleTest` (profil complet, grade des deux côtés, 100 FCFA
      résolus, communication acceptée et rattachée, aucun paiement, **la page de
      paiement annonce bien 100**, et la libération du numéro ; 6 sabotages vérifiés).
  - ⚠️ **La grille tarifaire du seed LIE les grades aux catégories** (`fees[].grades`)
    et marque « Autres participants » **par défaut** : sans cela `Fees::resolveAmount()`
    renvoyait `null` et les 200 paiements de participation étaient semés **à 0 F**. Les
    montants sortent donc par grade (**100** / 35 000 / 50 000 FCFA). ⚠️ **Les 100 FCFA
    des doctorants et étudiants en master sont VOULUS** : c'est le tarif sur lequel on
    éprouve un vrai règlement mobile money de bout en bout (montant minimal
    encaissable), et ce n'est pas une faute de frappe pour 100 000. Les deux autres
    catégories gardent un tarif réaliste - un barème entièrement à 100 F n'éprouverait
    plus la grille. ⚠️ Les paiements de démonstration de `ColloqueAvenirSeeder`
    résolvent désormais leur montant par `Fees::resolveAmount()` au lieu d'un 15 000
    écrit en dur, qui aurait affiché des encaissements sans rapport avec la grille.
  - **Programme du seed = ateliers + plénières SANS conflit** (l'exclusivité des
    plénières est respectée dans les données de démo). Les **ateliers** de volume
    occupent « Salle 1 » à « Salle 6 » (6 en parallèle) par blocs après-midi
    **14:00-17:00** (J+0, **J+1**, J+2) et matin **08:30-11:30** (J+3)
    (`ColloqueAvenirVolumeSeeder::buildSessions()`, **4 blocs** du **lun. 12/10 au
    jeu. 15/10**, 24 séances de 3 h - un bloc par jour, un jour vide au milieu d'un
    programme se lisant comme un trou de données). Les **4 sessions
    plénières** (`ColloqueAvenirSeeder::buildPlenaries()`, salle « Grand amphithéâtre »,
    `type=plenary`) sont calées **hors** de ces fenêtres : inauguration **lun. 12/10
    09:00-10:30**, table ronde **mer. 14/10 08:00-10:00**, formation **jeu. 15/10
    14:00-17:00**, clôture **ven. 16/10 11:00-12:30** - dernier jour de l'édition, d'où
    l'absence de bloc d'ateliers ce matin-là. Les **séances de démo** de la base
    (`Session - {atelier}`, rapports/Q&A) sont à **11:30-12:30** (juste avant la pause
    déjeuner 12:30-14:00, laissé libre par le volume), et les séances **en direct** /
    **terminée** sont dans des auditoriums dédiés : aucune ne collisionne en salle avec le
    volume ni ne tombe sur le déjeuner. ⚠️ En touchant à ces horaires, garder ces fenêtres
    disjointes (ateliers, plénières ET pause déjeuner) - sinon la vue Planning affiche des
    conflits dès le seed.
  - ⚠️ **Le mardi 13/10 (J+1) est un APRÈS-MIDI d'ateliers, et c'est demandé** : il faut
    des créneaux programmés dans **au moins deux ateliers fonctionnels simultanément**,
    avec des communications, **entre 14h et 18h** ce jour-là. Les six ateliers y tiennent
    leur séance 2 de **14:00 à 17:00**, dans six salles distinctes (33 communications) ;
    la matinée de ce jour ne porte plus que les séances de 11:30-12:30 du seeder de base.
    ⚠️ **Deux ateliers sur six sont CONFIÉS** - ateliers 1 et 2, modérateur ET rapporteur
    rattachés **par e-mail** aux comptes de démonstration
    (`ColloqueAvenirVolumeSeeder::equipesDeSeance()`), ce qui ouvre pour de bon « Séances
    à modérer », la présence, la validation du passage et le rapport de séance. Deux et
    pas six : les six ateliers étant **simultanés**, confier plus ferait du même compte le
    modérateur de trois salles à la même heure. ⚠️ Le **programme synthétique publié**
    annonce la même heure (`ColloqueAvenirSeeder`) : déplacer le bloc sans le programme
    ferait annoncer des communications là où aucune salle n'ouvre. Garde :
    `ColloqueAvenirVolumeSeederTest` (« fait tourner au moins deux ateliers en parallèle
    le 13/10 de 14h à 18h » - simultanéité, salles distinctes, communications, visio,
    séances confiées, aucun modérateur ubiquitaire, et accord du programme publié ;
    5 sabotages vérifiés).
  - ⚠️ **Tout se cale sur `$start`** (`ColloqueAvenirSeeder`, décalages J+0 à J+4) et le
    seeder de volume **relit `date_start`**. Déplacer le colloque = changer `$start`/`$end`,
    jamais des dates en dur ailleurs. Garder une fenêtre de **5 jours** : la clôture est à J+4.
    ⚠️ **RÉTRÉCIR la fenêtre ne se fait PAS en changeant ces deux lignes seules** : en passant
    de 7 à 5 jours il a fallu replier les décalages des plénières (table ronde J+3 → J+2,
    formation J+4 → J+3, clôture J+6 → J+4), les blocs d'ateliers et le **programme
    synthétique** - sinon la clôture tombait deux jours après la fin de l'édition. Deux tests
    de garde : aucune séance **du programme** hors fenêtre, et aucune journée du programme
    publié hors fenêtre.
  - ⚠️ **Le colloque de démonstration ne sera DANS LE PASSÉ qu'à partir du 17 octobre
    2026**, et les deux séances calées sur l'**horloge** (en direct, terminée) tombent le
    jour du seed : semées hors de la fenêtre, elles en sortent **par construction**, et le
    test de garde les écarte dans tous les cas.
    C'est le prix assumé de « les trois états de visio se voient sans rien attendre » - les
    y ramener de force donnerait une séance « en direct » qui ne l'est pas. ⚠️ Le cas vérifie
    qu'il n'y en a que **DEUX** : sans ce compte, une vraie séance hors fenêtre se cacherait
    derrière l'exception le jour où quelqu'un marquerait tout un bloc « terminé ».
  - ⚠️ **5 communications MINIMUM par séance d'atelier** (`MIN_PAR_SEANCE`), et c'est ce
    minimum qui **dicte le nombre de blocs**, pas le calendrier : 130 communications
    acceptées sur 42 séances en laissaient 3 par séance, et une séance à trois ne démontre
    ni la modération, ni le rapportage, ni le passage en salle. 24 séances en portent 5 ou 6
    - et 6 × 30 min remplit exactement le bloc de 3 h, donc un bloc de plus redescendrait
    à 4. ⚠️ La séance se choisit **au tour de rôle**, pas par `id % n` : le modulo de
    l'identifiant répartissait des identifiants non contigus (une communication sur trois
    est acceptée) et laissait des séances à 3 quand d'autres en avaient 8.
  - ⚠️ **Le créneau est identifié par sa COMMUNICATION** (`edition_id` + `submission_id`),
    pas par le couple séance + communication : une communication se présente une fois, et
    l'ancienne clé créait un SECOND créneau au lieu de déplacer le premier dès que le plan
    changeait.
  - ⚠️ **Le plan peut RÉTRÉCIR**, et `updateOrCreate` ne sait que créer ou mettre à jour :
    `buildSessions()` **supprime** ses séances d'atelier hors plan (et leurs créneaux -
    `slots.session_id` n'a pas de clé étrangère, ils survivraient orphelins). Sans ce
    ménage, un rejeu sur une base déjà semée gardait les 42 anciennes séances et la règle
    des 5 minimum devenait fausse en silence.
  - **Co-auteurs** (`attachCoauthors()` / `attachDemoCoauthors()`, en **seconde passe** :
    une communication ne peut citer que des comptes déjà créés). Chaque communication porte
    son correspondant **et deux co-auteurs inscrits**, plus - une fois sur trois - un
    co-auteur **non inscrit** resté `invited`, qui donne sa matière à l'association différée
    (`LinkPendingAuthorships`, à la création du compte). Les six comptes `commN@test.tg` ont
    chacun un dépôt à eux et deux co-signatures.
    ⚠️ **Les seeders n'appellent PAS `LinkSubmissionAuthors`** : cette action **notifie** -
    un e-mail et un SMS par rapprochement, soit six cents envois pour un seed. Ils écrivent
    les lignes `submission_authors` directement, **dans la forme exacte** que l'action leur
    aurait donnée (c'est `status = 'linked'` + `user_id` que lit « Mes communications »).
    ⚠️ **Jetons déterministes** (`hash('sha256', …)`) : le jeton porte le lien de
    déclinaison, et un `Str::random()` le changerait à chaque rejeu.
  - ⚠️ **`users.phone` est UNIQUE**, et un compte semé peut avoir été **renommé à la main**
    en base de développement : il garde son numéro, et l'insertion du compte attendu échouait
    sur la contrainte. Le scénario étant dans une transaction, c'étaient **200 communications
    qui ne se semaient pas** pour un numéro fictif. Le seeder cède le numéro (compte sans
    téléphone) au lieu de tomber.
  - **Visioconférences dans le jeu de démo** : les trois états sont représentés sans rien
    attendre - une séance **en direct**, une **terminée avec replay** (toutes deux calées sur
    l'**horloge**, pas sur une date fixe : une séance « terminée » posée à date fixe
    s'annoncerait terminée avant d'avoir eu lieu), et des séances **programmées**.
    ⚠️ **TOUTES les séances ouvrent une salle** (`meeting_status` jamais `none`) : deux blocs
    seulement la portaient, et on ne pouvait pas vérifier la visio depuis une communication
    prise au hasard du programme. Les 130 créneaux de communication sont donc tous en
    visio. Cérémonies en visibilité **publique** (seul endroit qui exerce
    `MeetingVisibility::Public`), ateliers réservés aux inscrits.
    ⚠️ **Le seeder n'impose AUCUN `meeting_provider`** : il suit `meetings.default`, et
    `MeetingProviderRegistry` retombe seul sur Jitsi public quand ce défaut n'est pas
    configuré. Le forcer en base écraserait la configuration du site - c'est ce qui
    masquait LiveKit une fois ses identifiants renseignés.
### Écran d'entrée : montré quand il sert, franchi sinon

Il a sa raison d'être la **première fois** : on tombait dans la salle, et la
demande d'autorisation du navigateur arrivait par-dessus une séance déjà
commencée.

- ⚠️ **Mais reposé à chaque actualisation et à chaque reconnexion, il devient un
  péage** : on le franchit sans rien lire, et on le traverse justement au pire
  moment - quand on vient de perdre la salle en pleine séance. `entreeDirecte`
  (règle pure testée) laisse entrer directement si on est **déjà passé par là
  POUR CETTE SALLE** (`localStorage`, clé `vu:<roomName>`) **et** que le
  navigateur accorde encore ce qu'on lui demande.
- ⚠️ **`prompt` n'est pas `granted`**, et c'est tout l'intérêt : un navigateur
  qui n'a pas encore demandé, ou qui a oublié, repasse par l'écran - sinon la
  fenêtre d'autorisation s'ouvre par-dessus la séance, ce que cet écran existe
  pour éviter. Firefox ne connaît pas ces noms de permission : on rend `prompt`,
  et l'écran s'affiche. Mieux vaut un écran de trop que la mauvaise surprise.
- Entrer sans caméra ni micro ne demande aucune autorisation : rien à vérifier.

### Les échanges (Q&A voté, sondages) ont leur SURFACE et leur bouton

⚠️ Ils ont d'abord vécu sur une autre page (poser une question faisait quitter
la visio), puis comme **onglet du panneau latéral** - et c'était encore trop
peu : une colonne de 400px partagée avec le fil, les personnes et la présence ne
tient pas une file de questions **et** des sondages en cours. On passait son
temps à faire défiler, et sur téléphone le tiroir recouvrait la séance.

- **`#btnEch`** dans la barre (pastille `#echCount`), **`#ech`** comme surface :
  deux colonnes au-dessus de 860px, empilées en dessous, **chacune avec son
  propre défilement** - sinon répondre à un sondage oblige à remonter par-dessus
  trente questions.
- ⚠️ **Tri côté serveur : ce qui ATTEND passe devant.** Une question répondue
  mais portée par vingt votes restait en tête de file pour le reste de la
  séance. L'animateur lit de haut en bas : ce qui est fait descend, et les
  masquées (que seul le modérateur voit) descendent plus bas encore.
- **Filtres** (`questionsFiltrees`, règle pure) : Toutes / Sans réponse / Les
  miennes. ⚠️ Un vide **filtré** ne se dit pas comme un vide : annoncer « aucune
  question » alors qu'il y en a trente derrière un filtre, c'est la salle qui
  ment.
- ⚠️ **La pastille ne compte que ce qui attend** (`compteEnAttente`) : une file
  de quarante dont trente-huit sont traitées n'appelle pas l'animateur. Elle
  **respire trois fois** quand une question arrive alors que la surface est
  fermée, puis se tait - un signal permanent cesse d'être un signal.
- ⚠️ **Deux rythmes de relecture** : 10 s surface ouverte (un vote qui monte doit
  se voir), 45 s fermée. Fermée, on relit **quand même** - sinon la pastille
  mentirait jusqu'à ce qu'on pense à ouvrir, et c'est précisément ce qu'elle
  existe pour éviter.
- Rien n'est réécrit côté règles : ce sont les points d'entrée JSON de la page
  autonome, et c'est le **serveur** qui dit qui peut poser, voter et modérer.

### Tableau blanc (bouton « Tableau »)

Une surface de dessin partagée qui recouvre la scène chez tout le monde.
Crayons, gomme, épaisseur, annuler, effacer, et **enregistrement en image**.
Tout passe par le **canal de données** : rien n'est envoyé au serveur, le
tableau vit le temps de la séance. Le jour où il devra rejoindre le compte
rendu, c'est un point d'entrée à ajouter - pas ce module à refaire.

- ⚠️ **Le cadre a un rapport FIXE (16/9) et se centre** (`cadreTableau`). C'est
  la décision qui tient tout le reste : un tableau est un **document**, il doit
  être identique partout. Si chaque poste normalisait sur SA scène, un cercle
  tracé sur un portable deviendrait une ellipse sur l'écran d'à côté, et un
  schéma annoté deviendrait faux. Les marges sont le prix à payer.
- ⚠️ **L'épaisseur se rapporte à la largeur du cadre**, pas au pixel, pour la
  même raison.
- ⚠️ **Le crayon suit la PAROLE, pas le rôle** (`droitDeTracer`, règle pure).
  Le serveur donne `presenter` à **chaque auteur** de communication de la
  séance (`presenterAccountIds()`) : avec `canPublish()`, les six
  communicateurs d'une séance pouvaient tous écrire sur le diaporama de
  celui qui parle. Le crayon va à qui **présente en ce moment** - celui à qui
  la parole a été donnée (`isFloor`, posé à la réception de `mod_role`) ou qui
  partage son écran. Le **modérateur** le garde : il conduit la séance, et
  sans lui le tableau blanc serait inutilisable tant que personne ne présente.
  ⚠️⚠️ **Une SEULE définition de « qui présente »** : `celuiQuiPresente()`,
  partagée par la **scène** et par le **crayon**. Elles l'ont calculée chacune
  de leur côté - la scène prenait « parole donnée OU rôle présentateur », le
  crayon exigeait la parole donnée - et un communicateur affiché
  « Présentateur » **en grand sur la scène** trouvait le bouton Tableau grisé.
  La salle lui disait deux choses contraires en même temps.
  ⚠️ `majDroitDeTracer()` est rappelé depuis **`refreshPeople()`** : c'est la
  liste des personnes qui dit qui présente, et tout y passe (arrivée, départ,
  parole donnée, rôle retiré). L'accrocher aux seuls événements qu'on avait en
  tête en oubliait toujours un.
- ⚠️ **C'est une règle de CONDUITE, pas une barrière.** La frontière
  vérifiable reste le **rôle signé dans le jeton** (`ORDRES_RESERVES`, niveau
  `presenter`), qui interdit déjà le tableau à l'assistance. Qui tient la
  parole se décide sur le canal de données, donc ne s'authentifie pas - et un
  récepteur qui filtrerait là-dessus écarterait des traits légitimes dès que
  sa vue de la parole diffère d'un poste à l'autre. **Un tableau qui perd des
  traits en silence est pire que le mal qu'on soigne.**
- La lecture seule masque la barre **et** les écouteurs vérifient
  `peutTracer()` - sinon il suffisait d'ouvrir la console.
- ⚠️ **`ordreRecevable` dit d'où vient un message, pas ce qu'il contient.**
  `traitRecu` assainit : couleur prise dans la palette, épaisseur bornée,
  coordonnées ramenées dans le cadre, points plafonnés, `Infinity` écarté
  (il passe `!isNaN` et fige le canevas de toute la salle).
- ⚠️ **C'est l'ARRIVANT qui demande** (`board_ask`), pas le propriétaire qui
  devine : envoyé à l'annonce d'arrivée, l'instantané partirait peut-être avant
  que l'arrivant n'écoute. La demande part dans
  `adopteParticipantsPresents()` - le seul endroit qui passe à l'entrée **et**
  après une reconnexion. Seul celui qui a **ouvert** répond (sinon trois
  présentateurs envoient trois instantanés), avec un garde-fou de 3 s par
  demandeur. Revers assumé : s'il a quitté la salle, un arrivant ne reçoit plus
  le tableau.
- ⚠️ **L'instantané se découpe par POINTS** (`lotsDeTraits`) : un tableau d'une
  heure dépasse la taille d'un message du canal, et envoyé d'un bloc il n'arrive
  pas du tout. Un trait plus long qu'un lot part seul - le couper en ferait deux
  traits avec un blanc au milieu.
- ⚠️ **Le dernier message d'un trait porte le trait ENTIER** (`net`), allégé à
  la levée du crayon : sans ce remplacement, les autres postes garderaient la
  version brute et le document ne serait plus le même partout.
- ⚠️ **On ne redessine pas tout à chaque point** (`traceDernierSegment`). À deux
  cents traits, chaque mouvement du crayon en redessinerait deux cents, soixante
  fois par seconde.
- ⚠️ **Pas de `pointerleave`** pour finir un trait : atteindre le bord du cadre
  le couperait en pleine écriture. `pointerup` + `pointercancel`, plus un
  `pointerup` sur la fenêtre si la capture a échoué.
- ⚠️ **Mesuré au harnais** : le titre et la barre d'outils ne flottent pas
  au-dessus du tableau, leur place est **retirée avant** le calcul du cadre -
  posés en surimpression, ils recouvraient le bas de la zone de dessin. Et la
  barre est centrée par `left:0;right:0;margin:auto`, **pas** par `left:50%` +
  `translateX(-50%)` : en positionnement absolu, `left:50%` ne laisse que la
  moitié de la largeur disponible, et la barre se repliait sur deux lignes alors
  que son contenu tenait largement.
- **Barre en icône + mot**, même grammaire que la barre de la salle : le
  `title` seul **n'existe pas au doigt**, et une rangée de six dessins sans
  légende se devine mal. Le mot s'efface sous 640px, où la place rendue au
  tableau vaut mieux qu'une légende ; `title` et `aria-label` restent.
  ⚠️ **Mesuré au harnais** : la pastille du mode actif paraissait **vide** -
  `#visio-room .bd-outils button.on` (fond or à 16%) a la même spécificité
  que la règle du mode et vient plus bas dans la feuille, elle gagnait donc
  le fond en laissant à l'autre la seule couleur du texte. Bleu foncé sur
  fond sombre. D'où le chevron `.bd-outils .bd-mode button.on` (contraste
  remonté à 8,4:1).
- **Annuler / Refaire** (`indexDuDernierTraitDe`, règle pure) : on ne défait
  **que les siens** - deux personnes annotent la même présentation, et
  « Annuler » ne doit pas effacer par surprise la flèche du voisin. Le
  préfixe se compare **avec son tiret**, sinon `alice` reprendrait les traits
  d'`alice2`. ⚠️ Le trait rendu repart **entier** (`net`) : les autres postes
  l'avaient retiré, leur envoyer la suite ne suffit pas. ⚠️ Un trait neuf
  **vide la pile** (la branche reprise est abandonnée), et un `board_clear`
  aussi - rendre un trait sur un tableau qu'on vient de vider ferait
  réapparaître un fantôme.
- ⚠️ **UN TABLEAU APPARTIENT À LA COMMUNICATION DE QUI L'A OUVERT.** Le
  présentateur n'attend personne : il ouvre quand il veut, et c'est SON
  tableau. Quand **quelqu'un d'autre** l'ouvre, on repart d'une surface
  **vierge** - le schéma du communicateur précédent n'a rien à faire sous
  l'exposé du suivant. Rouvrir le sien, en revanche, le retrouve : on referme
  parfois par mégarde, et perdre son tableau pour un clic serait rude.
  ⚠️ C'est pourquoi `fermeTableau` **ne remet pas `parQui` à zéro** : sans
  propriétaire mémorisé, rouvrir le sien reviendrait à l'ouvrir pour la
  première fois - et l'effacerait.
- **Reprendre le tableau d'un autre se confirme** : l'ouvrir pour sa propre
  communication efface son contenu **chez tout le monde**, et rien ne le rend
  (son auteur peut l'emporter en image tant qu'il est à l'écran). Un tableau
  vide se reprend sans rien demander - il n'y a rien à perdre.
- « Effacer » **se confirme** aussi : il emporte le travail de tout le monde.
- Tests : `meeting-pure.test.js` (12 cas).

**Deux modes** (bouton « Tableau | Annoter » dans la barre du tableau) :

- **Tableau** : surface blanche, cadre 16/9.
- **Annoter** : la couche devient transparente et le cadre se cale sur l'**image
  présentée** (`cadreMedia`). Le mode est **deviné à l'ouverture** - un écran
  partagé s'ouvre en annotation, c'est presque toujours pour cela qu'on prend le
  crayon.
- ⚠️ **La salle force `object-fit:contain` pendant qu'on annote.** En `cover`,
  l'image est ROGNÉE, et pas pareil selon le format de chaque fenêtre : deux
  personnes ne verraient pas la même portion du document, et la flèche de l'une
  tomberait à côté chez l'autre. Mesuré au harnais : cadre aligné sur un
  diaporama 4/3 à moins de 2px près, rapport exact.
- ⚠️ **Le repère se calcule à l'identique partout** parce qu'il ne dépend que de
  la taille **intrinsèque** du média (la même pour tous) et de la boîte locale.
- ⚠️ **`loadedmetadata`, pas seulement l'appel direct** : à l'instant où la piste
  est attachée, `videoWidth` vaut zéro. Sans cette attente, l'annotation
  retombait sur le 16/9 du tableau alors qu'un diaporama 4/3 arrivait une
  fraction de seconde plus tard.
- ⚠️ **La toile reste transparente en annotation** : la remplir de blanc
  masquerait la présentation. Revers assumé : la gomme y laisse une trace
  blanche au lieu d'effacer - « Annuler » est l'outil qui défait.
- ⚠️ **L'export compose** l'image de la scène PUIS les traits, à la taille
  **intrinsèque** de la vidéo : exporter la seule couche de dessin rendrait des
  traits flottant sur du vide, et exporter à la taille du canevas rendrait un
  diaporama illisible sur un petit écran. Une piste WebRTC ne salit pas le
  canevas, `toBlob` reste autorisé.
- ⚠️ **La barre d'outils ne peut plus être retirée du cadre** (il doit coller à
  l'image) : elle se loge dans la bande laissée par le format quand il y en a
  une, et s'efface à 40% sinon, jusqu'à ce qu'on la vise.

### Réactions de salle (bouton « Réagir »)

Un emoji qui **monte sur l'écran de présentation**, chez tout le monde, et qui se
pose **en même temps sur la carte de qui a réagi** (opacité réduite, coin
bas-droit). Bouton voisin de « Main » : même intention, deux intensités.
Purement client (canal de données LiveKit) - **rien n'est envoyé au serveur et
rien n'est conservé** : la salle n'a pas à garder trace de qui a applaudi quoi.

- ⚠️ **La bulle ne porte PAS de nom.** C'est la carte qui dit de qui vient la
  réaction, à l'endroit où l'œil cherche déjà la personne ; un nom accroché à
  chaque bulle encombrait la scène pour redire la même chose.
- ⚠️ La marque va sur **SA** carte (`tileLocal` / `tile-<identity>`), jamais sur
  la grande tuile : celle-ci montre qui **parle**, et y poser la réaction de
  quelqu'un d'autre désignerait la mauvaise personne.
- ⚠️ **Le minuteur de la marque précédente est annulé** avant d'en poser une
  nouvelle : sinon il retire la marque de la **seconde**, encore toute fraîche.
- ⚠️ Le **nom recule** (`.tile.a-reagi .name`) le temps de la réaction, sinon
  l'emoji se pose sur la fin d'un nom long. Dans le **bandeau** le nom est
  replié : il ne recule qu'une fois déplié (survol, clavier) - et **au doigt**,
  où il reste ouvert faute de survol. Cette dernière règle doit venir **après**
  celle du bandeau : à spécificité égale, posée plus haut, elle perdait et
  l'emoji se posait sur le micro (mesuré à 375px).

- ⚠️ **À ne pas confondre avec les réactions du fil** (`basculeReaction`,
  `.chip`), qui se posent SUR un message, se comptent et survivent au
  rechargement. Une réaction de salle est un applaudissement : elle passe.
- ⚠️ **Le canal de données est public.** Le récepteur filtre l'emoji sur la
  liste connue (`MeetingMessage::EMOJIS`, déjà exposée par `cfg.chat.emojis`) :
  sans cela, n'importe quel connecté afficherait **en grand, chez tout le
  monde**, le texte de son choix. `textContent`, jamais `innerHTML`.
- ⚠️ **L'anti-rafale (`peutReagir`) s'applique des DEUX côtés** : chez
  l'émetteur pour le bouton, chez le récepteur **par expéditeur** - un client
  modifié ne se prive pas d'un verrou qui ne vivrait que dans sa propre page.
  Il laisse passer une horloge qui recule : c'est un anti-rafale, pas un verrou.
- ⚠️ **`REACTIONS_MAX` et `placeBulle` sont solidaires** : il y a exactement
  autant de places que de bulles vivantes (8). Quand la suite reboucle avant le
  plafond, deux bulles encore à l'écran se posent **exactement** l'une sur
  l'autre. Le test énonce l'invariant.
- ⚠️ **Mesuré au harnais visuel, pas estimé** : 42px entre deux rangées (la
  bulle en fait 38 de haut), 26% d'écart entre deux places d'une même rangée
  pour une bulle large d'au plus 25%, et `min-width:0` sur le nom - sans lui, un
  élément de flex refuse de descendre sous la largeur de son texte et
  `max-width` restait sans effet.
- ⚠️ La couche vit dans **`.stage-main`**, pas dans la page : c'est elle qui
  passe en plein écran. `pointer-events:none`, sinon elle avale les clics.
- ⚠️ **Mouvement réduit** : la bulle ne voyage plus mais **ne disparaît pas** -
  `animation:none` la laisserait figée pour toujours. Elle s'efface sur place,
  et le retrait s'appuie de toute façon sur un **minuteur**, pas sur
  `animationend` (qui n'arrive jamais si l'animation ne se joue pas).
- `aria-hidden` sur la couche : vingt bulles énoncées une à une noieraient le
  lecteur d'écran. Qui veut dire quelque chose **lève la main** - celle-là est
  annoncée, et listée.
- Tests : `meeting-pure.test.js` (6 cas).

### Visioconférence par édition (onglet « Visioconférence » de la fiche)

Même patron que la **passerelle de paiement par édition** : le gestionnaire
renseigne, pour **son** édition, le fournisseur, les identifiants du serveur
(LiveKit / Jitsi) et la **qualité vidéo**. Stocké dans
`edition.settings['meetings']` (seules les valeurs **remplies** sont conservées) ;
**`App\Support\EditionMeetingConfig::apply($edition)`** surcharge
`meetings.default` + `meetings.livekit.*` / `meetings.jitsi.*` au runtime, avec
**repli sur la configuration globale** pour tout champ vide.

- ⚠️ **Toutes les portes d'entrée passent par `EditionMeetingConfig::using()`**,
  qui **restaure** ensuite : une même requête peut toucher plusieurs éditions (une
  liste de séances), et `apply()` n'écrase que les champs remplis - sans
  restauration, une salle s'ouvrirait sur le serveur d'un autre colloque. Le socle
  commun l'applique dans **`JoinsMeetings::avecReglagesVisio()`** (URL de
  connexion, embarquement Jitsi, lecteur LiveKit) ; les trois autres appels
  LiveKit le font explicitement : **Egress** (enregistrer sur le bon serveur),
  **occupation réelle de la salle** (`SessionPresenceApiController`) et le bouton
  « Enregistrer » de la salle.
- **`Meetable::meetingEdition()`** (nouveau au contrat) dit de quelle édition
  relève une salle - sans lui, le socle ne saurait pas quelle configuration poser.

**Qualité vidéo** (`meetings.quality`, défaut **`md`** = 960x540 à 24 i/s) :

- ⚠️⚠️ **RÉGRESSION VÉCUE, puis corrigée.** Le défaut avait été porté à 720p pour
  toute la plateforme. Les trois couches additionnées passaient d'environ
  **1,45 Mb/s à 2,35** de liaison montante : sur un réseau modeste, ce n'est plus
  une image qui saccade, **c'est la salle qu'on perd**. Le défaut est revenu à
  `md`, qui est **exactement** ce que la salle faisait avant d'être réglable.
  Monter reste possible - mais c'est un **choix d'édition**, pas un défaut
  imposé à tout le monde. Ne pas refaire le trajet inverse sans avoir mesuré la
  liaison montante réelle des salles concernées.
- ⚠️ `profilVideo` est une règle pure testée, et le cas Pest
  « garde « md » comme défaut de la plateforme » **fige cette régression**.
- ⚠️ `degradationPreference: 'balanced'` : sans elle, l'encodeur s'obstine sur un
  seul critère quand la liaison faiblit - il tient la définition et hache le
  mouvement, ou l'inverse. Une image qui se dégrade vaut mieux qu'une salle
  qu'on perd.
- ⚠️ **Un débit explicite accompagne la définition** (`videoEncoding.maxBitrate`) :
  laissé au SDK, il se calait sur l'ancienne définition et une capture plus fine
  serait sortie en bouillie.
- ⚠️ Les **couches basses** (180p, 360p) restent publiées : ce sont elles que
  reçoivent les petites vignettes et les connexions faibles, et c'est ce qui rend
  une couche haute abordable. Ne pas les retirer en croyant « simplifier ».
- ⚠️ Le coût retombe sur la **liaison montante de qui diffuse** - d'où un réglage
  **par édition** : sur un réseau modeste, « Économe » (360p) donne une image plus
  stable qu'une haute définition qui saccade.
- Tests : `tests/Feature/EditionMeetingConfigTest.php`.

### Les échanges (Q&A + sondages) = EXCLUSIVEMENT dans la visio

Questions et sondages vivaient sur une **page autonome** (`/session/{id}/echanges`),
atteinte par un lien de la barre du haut de la visio : ⚠️ poser une question
obligeait à **quitter la visio**, donc à cesser de voir et d'entendre ce dont on
voulait parler. Ils sont un **onglet du panneau** (« Échanges »), au-dessus du
chat et des participants.

- ⚠️ **La page autonome a été SUPPRIMÉE** (route `session.engagement`, méthode
  `SessionEngagementController::page`, vue `session/engagement.blade.php` -
  toutes retirées) : les échanges se tiennent **exclusivement dans la salle de
  visio**. `SessionEngagementController` n'expose plus que des **points d'entrée
  JSON** (`questions`/`ask`/`vote`/`moderate`/`polls`/`createPoll`/`votePoll`/
  `togglePoll`), consommés par l'onglet « Échanges » de `resources/js/meeting.js`.
  Ne pas recréer une page HTML : un sondage se répond là où se tient la séance.
- ⚠️ **Aucune règle JSON n'est réécrite** : c'est le serveur qui dit qui peut
  poser, voter et modérer (`abort_unless($role->canJoin())` / `isModerator()`).
  Masquer un bouton n'a jamais protégé personne. L'onglet n'apparaît que si le
  serveur a donné les adresses (`cfg.engagement`, posées sous la même garde
  `canJoin()` que la salle). Tests : `SessionQaTest` (garde sur les endpoints JSON).
- **Rafraîchissement** : toutes les 10 s **panneau ouvert sur cet onglet
  seulement** - la salle entière interrogeant le serveur pour un panneau que
  personne ne regarde, c'est du bruit pur. Entre-temps, une annonce sur le canal
  de données (`type: 'engagement'`) fait relire aussitôt. ⚠️ Elle est **bridée à
  une relecture toutes les 2 s** : n'importe qui peut émettre sur ce canal, et
  sans ce frein un client modifié ferait relire la liste à toute la salle en
  boucle. Ce n'est PAS un ordre réservé - poser une question est le droit de tous.
- **Le compteur de l'onglet** = les questions **en attente**, pas le total : c'est
  la file de la séance, pas l'archive.
- ⚠️ **Les onglets du panneau DÉFILENT à toutes les largeurs** (c'était la règle
  sous 900px) : ils sont désormais jusqu'à cinq, et « Communicateurs » à 80px se
  serait coupé en plein mot.
- Tout est construit en `textContent` (`elt()`) : question, nom d'auteur et
  intitulé de sondage viennent de participants.

### « Séances à modérer » (`/moderation`) = un espace de travail, pas une page

Un seul mot pour une seule chose : le rôle s'appelle **modérateur** partout
ailleurs (`moderator_email`, `MeetingRole::Moderator`, pastille de la salle,
`ModerationController`). L'interface disait « animer » - d'où `/animation` et
« Séances à animer », seuls endroits à employer un troisième verbe. Tout est
aligné sur **modérer** ; `/animation` reste en **redirection** (signets, courriels).

- ⚠️ **La page est fermée à qui ne modère rien** (`abort_unless(isModeratorSomewhere)`).
  Elle n'était gardée que par `auth:account` : tout compte connecté l'ouvrait - vide,
  mais ouverte, et avec elle l'idée qu'il y a là des outils qui ne le regardent pas.
- **Le contenu suit le colloque sélectionné**, comme toutes les pages personnelles.
  ⚠️ **Sauf quand la sélection ne veut rien dire ici** : un modérateur est rapproché
  par **e-mail** et n'est pas forcément **inscrit** à l'édition qu'il modère - le
  sélecteur ne la lui proposerait donc jamais (`CurrentColloque::selectable()` part
  des `Participant`), et la page resterait vide à jamais. Dans ce cas on montre tout
  ce qu'il modère, chaque carte portant déjà le nom de son colloque.
- **Quitter la visio ramène le modérateur à ses séances** (`backUrl`), pas à la page
  publique de déroulement : il sort d'une séance pour enchaîner sur la suivante ou
  pour en écrire le rapport. ⚠️ Le test porte sur **`EventSession::isModeratedBy()`**
  (la séance confiée) et **non sur le rôle en salle** : le staff y est modérateur
  aussi, et n'a pas d'espace personnel où atterrir - il tomberait sur un 403.
- ⚠️ **Les cartes ne portent PLUS le bouton « Espace d'échange (Q&A / sondages) », ni la
  LISTE des communications programmées** (elle pouvait être longue), ni les formulaires
  « Valider le passage » par communication. Les communications d'une séance vivent sur
  leur **propre page** (`account.passage.edit`, `/compte/seances/{session}/passage`), sur
  le modèle de la Présence : un bouton **« Communications programmées »** par carte (avec
  le NOMBRE de communications ; **toujours consultable** - on peut voir le programme de sa
  séance avant qu'elle commence), qui ouvre une page listant les communications ET
  permettant d'en valider le passage. `SessionPassageController::edit` (garde
  `SessionPresence::canManage` : modérateur **ou** rapporteur) ; le constat passe toujours
  par `session.passage` (même garde horaire serveur). (Les échanges Q&A/sondages se
  tiennent **exclusivement dans la salle de visio** - la page autonome a été supprimée,
  cf. « Les échanges = EXCLUSIVEMENT dans la visio ».)
- ⚠️ **Un modérateur ne valide pas un passage avant l'heure de sa séance** : avant le
  début, la page affiche la liste des communications (consultable) mais **masque les
  boutons de validation** (bandeau « Validation pas encore ouverte », pastille « À valider
  dès le début » par ligne) ; `SessionPassageController::store` refuse de toute façon
  (`abort_unless($session->hasStarted())`). Tests : `CommunicationPassageTest` (page +
  garde horaire), `ModerationPageTest` (le bouton renvoie vers la page).
- Tests : `tests/Feature/ModerationPageTest.php`.

### Visio : clôture automatique, isolation des salles, historique des fils

- **Statut de visio réconcilié sur l'HORLOGE** (`programmée → en direct → terminée`),
  sans intervention manuelle. ⚠️ `EventSession::isLive()` lit une colonne **stockée**
  `meeting_status` : rien ne la faisait évoluer avec le temps, donc une séance restait
  « Programmée » jusqu'à ce qu'on la bascule à la main, et une séance de la veille restait
  joignable en « En direct ». Source unique : **`EventSession::syncMeetingStatus()`**, qui
  s'appuie sur deux fenêtres :
  - **Ouverture** (`meetingLiveDue()`) : une visio **programmée** passe « en direct » dès
    `starts_at - meetings.auto_live_lead_minutes` (défaut **30 min** avant le début ; repli
    début de journée si l'heure manque).
  - **Clôture** (`meetingWindowOver()`) : une visio **programmée ou en direct** dont la fin
    effective (`ends_at` ?? fin de journée de `session_day`/`starts_at` ?? `starts_at+2h`) est
    dépassée de `meetings.auto_close_grace_minutes` (défaut **120 min**) passe « terminée »,
    ce qui coupe l'accès (`canJoin`/token/chat-write → 403).
  ⚠️ **N'avance jamais à rebours** : on ne redescend pas « en direct » → « programmée » (le
  staff peut ouvrir une salle en avance), et on ne touche ni à « aucune » (pas de visio) ni à
  « terminée ». Une séance **sans horaire** n'est ni ouverte ni fermée. Deux déclencheurs : la
  **commande planifiée** `meetings:sync` (`routes/console.php`, toutes les 5 min) **et**
  l'**auto-réparation à l'accès** (`MeetingController::join`/`token`, `MeetingChatController`
  chemin écriture, `ModerationController`, onglet Programme **Sessions** et **Discussions**) -
  pour que l'état suive l'horloge même sans planificateur (ex. en dev). ⚠️ `isLive()` **n'est
  pas** modifié : c'est le statut stocké, désormais tenu à jour, qui décide. Tests :
  `MeetingAutoCloseTest`.
- ⚠️ **Accès à la visio d'une séance = PUBLIC par défaut.** `EventSession::meetingVisibilityEnum()`
  retombe sur `MeetingVisibility::Public` (et la colonne `event_sessions.meeting_visibility`
  naît à `public`, migration `2026_10_06_000200`) : tant que le gestionnaire n'a pas restreint
  l'accès « Participants inscrits » (onglet VISIO du programme, `admin.program.sessions.meeting-visibility`),
  la salle est ouverte à tous. ⚠️ **La FORMATION, elle, reste réservée aux inscrits** par
  nature (`Formation::meetingVisibilityEnum()` défaut `Participants`, et sa visio n'a pas de
  mode public) - ne pas aligner les deux. Un test qui veut exercer l'accès restreint d'une
  séance doit poser `meeting_visibility => 'participants'` explicitement (cf. `SessionQaTest`,
  `MeetingTest`). Tests : `MeetingTest`.
- **Page « hors salle » = SALLE D'ATTENTE adaptée au statut** (`meeting/join.blade.php`,
  branche hors LiveKit). Elle n'affiche plus un message générique « La visio n'est pas en
  cours / Revenez à l'heure prévue » : le contrôleur passe `meetingStatus` (enum) et la vue
  rend une carte par état - **programmée** (icône agenda + « Début prévu » + la page
  **se recharge toute seule toutes les 60 s** pour entrer dès l'ouverture auto de la salle),
  **terminée** (replay si dispo), **réservée** (accès inscrits), **connexion requise**
  (invité sur séance live réservée), **salle en préparation** (live sans lien fournisseur),
  **aucune visio**. Pastille de statut dans l'en-tête (`MeetingStatus::pill()/label()`),
  charte UK (`.mlobby-*`, styles en tête de la branche). ⚠️ **Pas de `use` dans le bloc
  `@php` d'une vue** (erreur de compilation « unexpected token use ») : l'enum est écrite en
  nom complet `\App\Enums\MeetingStatus::…`, y compris dans les `@if`.
- ⚠️ **Nom de salle TOUJOURS unique par séance** (`EventSession::meetingRoom()` /
  `Formation::meetingRoom()`). `meeting_room` n'est qu'une **étiquette** : s'il était employé
  tel quel comme nom de salle LiveKit/Jitsi, deux séances portant la même (« Salle 3 »
  recopié) partageraient la même salle - participants, chat instantané et tableau blanc de
  l'une débordant chez l'autre. On suffixe donc toujours par l'événement + la séance : avec
  **2 à 6 salles simultanées**, aucune ne collisionne. (Salles LiveKit éphémères → renommer
  n'a pas d'effet de bord.) Le « reset » au changement de salle est déjà assuré par
  l'architecture (onglet par séance `geves-visio-<key>-<id>`, rechargement complet,
  `room.disconnect()` au départ, fil archivé **par séance**). Tests : `MeetingTest`.
- **Historique des fils de discussion (back-office)** : onglet **« Discussions »** du module
  Programme (`?tab=discussions`, cap `editions_read`). Liste les séances ayant une visio ou
  au moins un message (nombre de messages, dernier) ; `admin.program.discussions.show`
  rend le fil complet, `admin.program.discussions.export` le `.txt`. ⚠️ **Lecture directe de
  `MeetingMessage`** (indépendante de `canJoin`/live) → consultable **après la visio ET après
  l'événement**, là où l'API de la salle (`meeting.chat.*`) reste gardée par l'accès à la
  salle. Mise en forme texte partagée : **`App\Support\MeetingTranscript::toText()`** (réutilisée
  par l'export de la salle). Tests : `ProgramDiscussionsTest`.

- **Le nombre de connectés, dans la barre du haut.** Il ne se lisait **nulle
  part** en mosaïque : le compte du bandeau n'existe qu'en vue présentateur, et
  seulement une fois celui-ci déplié. La barre du haut, elle, ne disparaît dans
  aucune disposition ni à aucune largeur. ⚠️ Le compte vient de `people`, qui
  suit les arrivées et les départs - pas des vignettes, dont le nombre dépend de
  ce qui est AFFICHÉ. Le chiffre se lit seul ; la phrase qui le nomme
  (`phraseConnectes`, accord compris) est dans son `title` / `aria-label`.
- ⚠️ **Le bandeau des participants part REPLIÉ, et rien ne l'ouvre tout seul.**
  Il se dépliait à chaque arrivée - pour qu'une scène vide ne cache pas trois
  personnes connectées - mais il s'ouvrait alors par-dessus la séance sans
  qu'on l'ait demandé, et il fallait le refermer à la main. Son en-tête porte
  déjà le compte (« Participants (3) ») ; l'ouvrir est un geste. La bascule
  **Vue** n'y touche plus non plus : elle le **refermait de force** au retour
  en vue présentateur, y compris pour qui venait de l'ouvrir. Son ouverture
  n'appartient qu'à son propre bouton. Cas de garde dans `MeetingTest`.
- **Le nom se replie dans le bandeau** : la carte n'y fait que 150px (118 puis
  96 sur téléphone), et l'étiquette l'occupait presque entière pour un nom
  tronqué au tiers. Ne reste que l'état du micro ; le nom revient **au survol**
  de la carte. ⚠️ `max-width` et non `display:none` - il est replié, pas retiré,
  donc toujours énonçable par un lecteur d'écran. ⚠️ `focus-within` pour le
  clavier (couper le micro de quelqu'un sans le nommer), et **au doigt il reste
  affiché** : là où le survol n'existe pas, un nom qui ne se montre qu'au survol
  est introuvable.
- **Entrer et tenir l'horaire** (audit visuel, quatrième lot) :
  - **Écran d'entrée** (`ecranDEntree`) : aperçu caméra, **niveau du micro**,
    choix des périphériques, avant de se connecter. ⚠️ Seulement pour qui
    **diffuse** : un auditeur ne publie ni son ni image, lui dresser un mur
    retarderait tout le monde pour rien. ⚠️ Et les défauts **reprennent ce que la
    salle faisait déjà** - elle ouvrait caméra ET micro d'office, sans rien
    demander ; l'écran ne change pas ce qui se passe, il le montre et le rend
    révocable. ⚠️ L'aperçu doit être **arrêté avant de connecter** (`fermeApercu`)
    sinon la caméra reste prise et LiveKit ne peut plus l'ouvrir.
  - Le périphérique choisi est porté dans `videoCaptureDefaults.deviceId` /
    `audioCaptureDefaults.deviceId` : sans cela, LiveKit reprend celui que le
    navigateur juge par défaut - pas celui qu'on vient de vérifier. Les choix sont
    gardés en `localStorage`, lecture ET écriture sous `try` (navigation privée).
  - **Temps de parole** (`chrono`) : le créneau du programme donne la durée prévue
    (`minutes`, `null` sans horaire de fin - il n'y a alors rien à dépasser).
    L'alerte tombe au **dernier cinquième**, pas au dépassement : à ce moment-là,
    la personne conclut déjà.
    - ⚠️ Le modérateur tient le compte et diffuse un **nombre de secondes**, pas
      une heure de départ : deux machines n'ont pas la même horloge, et un écart
      afficherait un dépassement imaginaire. Réémis toutes les 5 s, et aussitôt
      pour un arrivant.
    - ⚠️ `floor_time` est un **ordre réservé au modérateur** (cf. `ORDRES_RESERVES`)
      - sinon n'importe qui affiche « 25 min » chez tout le monde et fait écourter
      une communication dans les temps.
    - ⚠️ Visible pour le modérateur et pour **qui a la parole** seulement : les
      deux qui peuvent agir dessus.
- **Ce que la salle doit ÉNONCER et épargner** (audit visuel, troisième lot) :
  - **`prefers-reduced-motion`** était ignoré dans la salle, alors que le reste du
    projet le respecte (`ukc.css`, `admin.css`, `public.css`). Deux animations
    tournaient **en boucle** pendant toute une séance : le halo du bouton
    d'enregistrement et la pastille d'état. ⚠️ Rien n'est caché, seul le mouvement
    s'arrête : le rouge et le mot « REC » disent l'enregistrement, le texte à côté
    de la pastille dit ce qu'on attend. L'anneau reprend son contour entier, un
    anneau amputé n'ayant plus de sens une fois immobile.
  - **Ce que la salle dit à voix haute** : `role="status"` sur l'infobulle et sur
    l'état de connexion, `role="alert"` sur le bandeau de reprise - perdre la salle
    interrompt ce qu'on faisait, une coupure de micro décidée par le modérateur
    non. « Votre micro a été coupé » ne parvenait à personne qui ne regarde pas.
  - **Échap ferme ce qui est ouvert**, du plus intérieur au plus extérieur :
    confirmation, choix de réaction, menus, réponse en cours, puis le tiroir
    (**sous 900px seulement** : au-dessus il fait partie de la page). Il ne fermait
    que la confirmation. ⚠️ L'ordre est la règle : on ne referme pas le panneau de
    quelqu'un qui voulait fermer un menu.
  - ⚠️ **Le choix de réaction n'était PAS rogné sur le premier message** comme
    l'annonçait l'audit : les 14px de marge du fil l'absorbent. Le vrai cas,
    mesuré, est un fil **remonté** où le bord haut d'un message affleure celui du
    panneau. D'où une mesure dans `ouvreChoixReaction`, et non un comptage.
- **Ce que la salle doit DISTINGUER** (audit visuel, second lot) :
  - **Une teinte par personne**, tirée de son nom (`teinteDuNom`). Les pastilles
    portaient toutes le même dégradé : huit caméras fermées, huit disques
    identiques. ⚠️ Seule la **teinte** varie ; la feuille de style fixe saturation
    et clarté basse pour que le blanc des initiales garde son contraste quelle que
    soit la couleur tirée. Et la pose passe par **un seul** `poseAvatar()` : une
    teinte écrite à deux endroits sur trois donnerait deux couleurs à la même
    personne dans la même salle.
  - **Le rôle sur la vignette**, pastille dans la ligne du nom, pour le modérateur
    et le communicateur **seulement** - « Auditeur » répété quarante fois n'apprend
    rien et mange la place du nom. Masqué dans le bandeau (150 px : le nom passe
    d'abord).
  - **La ligne de la liste se range par un ÉCART**, plus par des points médians.
    ⚠️ Et la règle s'écrit `#visio-room .person .meta > span`, avec le **chevron** :
    sans lui elle est plus spécifique que celles du rang, du micro et du réseau
    qu'elle contient, et les repeint tous en gris - un rang gris sur fond or.
    Vérifié en lisant les couleurs calculées, pas à l'œil.
  - **La barre d'outils porte des mots** (`data-nom`, un mot) : les intitulés ne
    vivaient que dans `title`, qui **n'existe pas au doigt**. ⚠️ `data-nom` et non
    `title` : « Plus d'options » sous une icône de 44 px disloquerait la barre ; le
    `title` entier reste pour le survol, le lecteur d'écran et la feuille « Plus
    d'options ». Les mots s'effacent en téléphone couché, où la hauteur rendue à la
    scène vaut mieux qu'un mot.
  - ⚠️ **Deux « états vides » annoncés à l'audit n'existent pas** : le fil porte
    toujours son message d'ouverture, et la liste contient toujours au moins
    soi-même. Seul le panneau des communicateurs pouvait s'ouvrir nu.
- **Ce que la salle doit MONTRER** (audit visuel, premier lot). Quatre manques qui
  ne tenaient pas à une règle mais à ce que l'œil cherche pendant une séance :
  - **Qui parle** (`ActiveSpeakersChanged`, qui n'était pas écouté) : liseré vert
    sur la vignette, ligne teintée dans le panneau. ⚠️ La règle est posée APRÈS
    `hand-up` dans la feuille de style : à spécificité égale, la dernière gagne -
    une main levée est une demande, la parole est un fait. Et la fonction
    **ne reconstruit rien**, elle déplace une classe (l'événement tombe plusieurs
    fois par seconde ; rebâtir la liste ferait sauter le défilement du panneau).
  - **Son propre reflet** : `scaleX(-1)` sur sa seule image. Sans lui, lever la
    main droite l'affiche à gauche. ⚠️ Jamais sur un partage d'écran (le texte
    partirait à l'envers) ni sur l'image des autres ; la bulle PiP porte TANTÔT sa
    caméra TANTÔT celle du présentateur distant, d'où la classe `soi`, posée sur
    une comparaison de pistes et non sur une devinette.
  - **Le réseau d'un participant** (`ConnectionQualityChanged`, pas écouté non
    plus) : une image qui gèle ne disait pas si l'autre décrochait ou soi.
    ⚠️ Rien ne s'affiche tant que tout va bien (cf. `niveauReseau`) : un indicateur
    posé en permanence sur tout le monde ne se remarque plus. Et **deux dessins**,
    pas un seul recoloré - à 13 px la couleur seule ne sépare pas « instable » de
    « perdu », et l'ambre des barres faibles est celui de la main levée d'à côté.
  - **Le fil ne s'arrache plus** : il ne suit le bas que si on y était déjà
    (`enBasDuFil`, mesuré AVANT d'ajouter le message), sinon un bandeau annonce ce
    qui attend. ⚠️ Deux comptes distincts à ne pas confondre : `nonLus` (le fil
    n'était pas sous les yeux, pastille sur l'onglet) et `aLireEnBas` (il l'était,
    mais on lisait plus haut).
- **Tests JavaScript : Vitest** (`npm run test:js`, `npm run test:js:watch`).
  Premier outillage de test côté client du projet - jusque-là, **aucune ligne de
  JS n'était couverte**.
  - **`resources/js/meeting-pure.js`** rassemble les règles de la salle qui ne
    dépendent ni du DOM, ni de LiveKit, ni d'une connexion : initiales, troncature,
    identifiants, lecture de la métadonnée, **table des ordres autorisés**, file des
    mains levées, grille de la mosaïque. ⚠️ Elles vivaient dans la fermeture de
    `meeting.js` (~1 600 lignes), donc hors d'atteinte de tout test - alors que ce
    sont elles qui portent les décisions les plus corrigées de ce module.
  - **Déplacer une décision dans ce fichier, c'est la rendre vérifiable.** Les cas
    de `meeting-pure.test.js` ne sont pas décoratifs : chacun fige un défaut
    constaté en salle réelle (un ordre forgé par un simple participant, quatre
    visages écrasés sur une rangée, un rang de main qui laisse un trou, un nom
    hostile dans des initiales).
  - `meeting.js` ne garde que des **adaptateurs** minuscules (par exemple
    `ordreRecevable(type, participant)` qui donne le rang à la règle pure).
  - ⚠️ `meeting.js` **ne passe pas `prettier --check`**, et ne le passait pas avant :
    le reformater en bloc noierait toute relecture. Les fichiers neufs, eux, sont
    conformes.
- **Le fil, une fois conservé, demande ce qui va avec** (troisième lot de l'audit) :
  - **Messages non lus** : compteur sur l'onglet Chat **et** pastille sur le bouton
    « Panneau ». ⚠️ Le tiroir est fermé par défaut sous 900px et le panneau s'ouvre
    souvent sur un autre onglet : un message arrivait sans que rien ne le signale.
    Le fil **rejoué** à l'arrivée ne compte pas comme non lu.
  - **Modération** (`meeting.chat.delete`, modérateur de la séance) : un message
    déplacé resterait sinon dans le fil pour **toutes les prochaines arrivées**. Le
    retrait part aussi sur le canal de données, pour disparaître en direct.
  - **Compte rendu** (`meeting.chat.export`, modérateur) : le fil s'emporte en texte
    daté. Les questions posées en séance ont une valeur pour le PV, et rien ne
    permettait de les en sortir.
  - ⚠️ **Une communication se rattache à son auteur par IDENTIFIANT**
    (`metadonnee()` → `accountId` dans la métadonnée du jeton, `matchComm`). Le
    rattachement se faisait par **sous-chaîne du nom de famille** : deux homonymes
    dans la salle, et le modérateur donnait la parole à la mauvaise personne. Le nom
    ne subsiste qu'en filet, pour les co-auteurs sans compte.
  - **Le micro se lit en icône** dans la liste, pas en toutes lettres : « micro
    coupé » répété sur chaque ligne pesait plus que le nom qu'on y cherche. Le texte
    reste dans `title`/`aria-label`. ⚠️ Le `innerHTML` du porteur d'icône ne
    contredit pas la règle des listes : il ne reçoit qu'une **constante du code**.
  - `#chatLog` est une zone `role="log" aria-live="polite"` : un lecteur d'écran
    annonce les messages sans qu'on ait à revenir lire le panneau.
- **Tenue de la salle en conditions réelles** (second lot de l'audit) :
  - **`App\Meetings\MeetableRegistry`** est le seul endroit qui dit quels objets ont
    une salle (`session`, `formation`) et traduit dans les deux sens, URL ↔ objet.
  - ⚠️ **On n'écrit dans le fil que pendant la séance.** L'API ne vérifiait que le
    rôle, alors que la page exige rôle **et** séance en direct : on pouvait
    alimenter le fil d'une séance terminée en appelant l'adresse directement. La
    LECTURE, elle, reste ouverte à qui peut entrer.
  - ⚠️ **L'envoi d'un message est idempotent** (`uid` unique par salle, index en
    base) : un réseau qui bégaie rejouait l'envoi, et deux lignes portaient le même
    identifiant - une réaction ne savait plus laquelle viser.
  - ⚠️ **Le débit du fil se compte PAR PERSONNE, jamais par IP**
    (`AppServiceProvider::limiteLeFilDeVisio`, limiteurs `visio-chat` et
    `visio-reaction`). Une cérémonie publique se suit depuis le campus, donc depuis
    quelques adresses partagées : un limiteur par IP ferait taire toute une salle
    parce qu'une personne écrit vite.
  - **Reprise après coupure** (route `meeting.token`, `reconnecte()`) : `Disconnected`
    signifie que le SDK a **renoncé**, et il ne restait qu'à recharger la page en
    pleine séance. Un bandeau propose « Reconnecter », qui **redemande un jeton**
    (celui d'origine a pu expirer - 2 h - et c'est précisément le cas des séances
    longues) et republie les pistes locales. Les droits sont recalculés : un rôle
    retiré ne revient pas par la porte du renouvellement.
  - ⚠️ **Après une reconnexion, il faut REPRENDRE l'état de la salle**
    (`adopteParticipantsPresents`) : les événements d'arrivée ne rejouent pas pour
    les personnes déjà présentes. Sans cela la salle revenait vide de tout le monde
    sauf de soi - défaut trouvé en essayant la reconnexion, pas en relisant le code.
  - **Un délai borne l'API Egress** (`connectTimeout(5)`/`timeout(15)`) : sans lui,
    un service d'enregistrement muet immobilisait le worker PHP, et c'est le
    modérateur qui attendait en pleine séance.
- **Sécurité de la salle de visio** - trois règles qui ne se négocient pas :
  - ⚠️ **Aucune liste de la salle ne se construit en collant du texte dans du
    balisage.** Participants, communicateurs et présence étaient composés par
    concaténation puis posés en `innerHTML`, avec dedans des **noms et des titres
    venus de la base**. Le profil n'impose qu'une longueur maximale : un inscrit
    qui écrivait `<img src=x onerror=…>` dans son nom faisait exécuter son code
    chez **tous les participants**, et dans le panneau du modérateur. Tout passe
    par `elt()` (`textContent`). Le seul endroit qui compose encore du balisage
    est la boîte de confirmation, dont le texte inséré passe par `texteEchappe()`.
    `initials()` filtre aux lettres et chiffres, pour la même raison.
  - ⚠️ **Les ordres reçus sur le canal de données sont vérifiés** (`ORDRES_RESERVES`,
    `ordreRecevable`). Rien ne les contrôlait : n'importe quel participant pouvait,
    depuis la console de son navigateur, couper le micro de toute la salle, retirer
    la parole, marquer une communication effectuée ou afficher un faux bandeau
    « enregistrement ». Le canal est **public pour tous les connectés** : c'est au
    RÉCEPTEUR de ne pas obéir à n'importe qui. Le rang est lu dans la **métadonnée
    du jeton** (`roleAnnonce`) et surtout **pas** dans `people[].role`, que ces mêmes
    messages modifient - on authentifierait un ordre avec ce que le précédent a écrit.
  - ⚠️ **Un visiteur sans compte doit rester distinct des autres**
    (`MeetingChatController::signature`) : tous partageaient la clé `anon`, si bien
    que le deuxième visiteur **retirait** la réaction du premier. La session HTTP les
    distingue, hachée pour ne pas recopier un identifiant de session dans une table.
- **Conduite de séance dans la salle** (`resources/js/meeting.js`) :
  - **Le micro s'ouvre AVEC la présentation.** Donner la parole promeut en
    `presenter` (message `mod_role`) : le poste visé ouvre alors son micro de
    lui-même (`ouvreMonMicro`). ⚠️ Sinon la salle entière regarde quelqu'un parler
    en silence, et personne ne sait d'où vient la panne. La personne reste libre
    de se recouper juste après : c'est son micro. (À l'entrée dans la salle, un
    modérateur ou un communicateur a déjà micro **et** caméra ouverts.)
  - ⚠️ **L'état des micros se LIT de la salle, il ne se devine pas**
    (`syncMicStates` / `microActif`, sur `TrackMuted`/`TrackUnmuted`/
    `TrackPublished`/`TrackUnpublished`). `micOn` n'était écrit que par les actions
    du modérateur : il disait ce qu'il avait **demandé**, pas ce que la personne
    faisait. Quelqu'un qui se coupait restait affiché « micro ouvert », et le
    bouton proposait de couper un micro déjà coupé.
  - **Mains levées numérotées** (`leveLaMain` / `mainsLevees` / `refreshHands`) :
    badge **✋ n** sur la tuile, rang dans la liste, compteur sur l'onglet
    Participants, et une infobulle à chaque main qui se lève. ⚠️ Le rang vient d'un
    **compteur local**, pas de l'horloge : deux postes n'ont pas la même heure,
    alors que les messages arrivent dans l'ordre d'émission. Il se **recalcule** à
    chaque affichage, donc une main baissée renumérote les suivantes au lieu de
    laisser un trou. ⚠️ Une main levée dans un panneau fermé n'existe pas : d'où la
    tuile et l'onglet, pas seulement la liste.
  - **Les gestes sans retour se confirment** (`confirme()`, boîte `#vModal`) :
    « Marquer effectuée » part à toute la salle et désactive le bouton. ⚠️ Pas
    `window.confirm` : il gèle la page (la visio continue sans qu'on puisse rien
    faire), sort visuellement de la salle et n'est pas garanti en contexte embarqué.
  - **Le panneau latéral se règle en largeur** (variable `--vside`, poignée
    `#sideGrip`, `poseLargeurPanneau`) : 400px par défaut, **360 au minimum**,
    jamais plus de la moitié de la salle, retenue d'une séance à l'autre
    (`localStorage`), double-clic pour revenir au défaut, flèches au clavier.
    ⚠️ 340px fixes ne suffisaient pas : ce panneau porte trois usages très
    différents (chat, participants, pointage de présence avec nom **et**
    institution), et aucune largeur ne les sert tous. ⚠️ Au redimensionnement de
    la fenêtre on repart de la largeur **retenue** et non de la courante : réduire
    la fenêtre borne le panneau, mais ne doit pas effacer le choix de la personne
    quand la place revient.
  - **L'étiquette des vignettes porte le micro** (`.mic-dot`, `majMicroTuiles`) :
    nom en 11px (12,5 occupait la moitié de la vignette et tronquait les noms au
    tiers) suivi d'une pastille micro ouvert / coupé. ⚠️ Elle est **cliquable** :
    le modérateur coupe ou rend un micro depuis la vignette, chacun depuis la
    sienne (qui repasse par le bouton de la barre, lequel porte les autorisations
    navigateur). Couper un micro demandait sinon d'ouvrir le panneau, de trouver
    la personne, puis son bouton.
  - **Le fil du chat est ARCHIVÉ et rejoué à l'arrivée** (`meeting_messages`,
    `MeetingChatController`, routes `meeting.chat.*`). ⚠️ Le chat ne vivait que sur
    le canal de données : qui rejoignait en cours de séance - ou rechargeait sa
    page - trouvait une salle muette, et les questions posées avant lui
    n'existaient plus nulle part. Le canal de données reste pour l'**instantané**
    (les personnes déjà connectées), la base pour la **mémoire**. Un trait
    « Vous rejoignez la salle » sépare le fil rejoué de la séance en cours.
  - ⚠️ **Le nom de l'auteur est calculé par le SERVEUR**
    (`Meetable::meetingDisplayName`, désormais publique et au contrat) : reçu du
    client, chacun signerait ses messages du nom d'un autre. Même règle que le nom
    affiché dans la salle, sinon le même message aurait deux auteurs.
  - **Réactions** (`meeting_message_reactions`, `MeetingMessage::EMOJIS`) : liste
    d'emojis **fermée** côté serveur, et **une seule réaction par personne et par
    message** - unicité `(message, réacteur)` tenue par la **base**. ⚠️ L'unicité
    portait d'abord aussi sur l'emoji : la même personne empilait 👍 ET ❤️ ET 👏,
    et pesait à elle seule plus que trois autres. Le même emoji retire la sienne,
    un autre la **remplace** (la réponse porte `replaced`, pour que les autres
    salles ajustent leur décompte sans recharger le fil). ⚠️ Le « réacteur » est
    le **compte** (ou le membre du staff), jamais l'identité LiveKit : celle-ci
    change à chaque connexion, et la même personne compterait deux fois en
    rechargeant sa page. L'affichage est optimiste, puis écrasé par le décompte
    du serveur.
  - **Le fil suit l'accès à la salle** : `Meetable::canJoin()`, et un `type`
    résolu depuis une **liste fermée** (`MeetingChatController::TYPES`) - jamais
    une classe construite à partir de l'URL.
  - **Chat : on répond à un message** (`preparerReponse` / `addChat`). La citation
    **voyage avec le message** (auteur + extrait de 120 car.) au lieu d'être
    retrouvée par identifiant : ⚠️ un participant arrivé APRÈS l'original n'a pas
    l'historique de la salle (rien n'est stocké côté serveur) et ne comprendrait
    pas la réponse. Cliquer la citation remonte à l'original quand on l'a.
  - ⚠️ **Le chat se construit en `textContent`, jamais en `innerHTML`** : message,
    nom d'affichage et citation viennent d'autres participants. L'ancienne version
    remplaçait `<` et `>` dans une chaîne injectée en `innerHTML` - une liste
    noire, donc fausse par construction.
  - ⚠️ Ces ordres passent par le **canal de données** (coopératif, tous les postes
    exécutant notre code), pas par l'API serveur de LiveKit : un client modifié
    pourrait les ignorer. Acceptable pour une conduite de séance, à revoir si la
    coupure de micro devient une mesure de police.
- **Rôle dans la salle de visio : il voyage en MÉTADONNÉE du jeton**
  (`LiveKitProvider::token()` → `metadata` = `{"role":…}`, vocabulaire unique dans
  **`MeetingRole::roomRole()`** : `moderator` / `presenter` / `audience`).
  - ⚠️ **LiveKit ne diffuse rien d'autre.** Les droits du jeton (`video.canPublish`,
    `roomAdmin`) ne sont connus que du serveur et du porteur : **les autres
    participants ne les voient pas**. Sans métadonnée, chaque client retombait sur
    « auditeur » **pour tout le monde** - le modérateur de la séance apparaissait
    auditeur dans la liste, et la scène, n'ayant aucun présentateur à montrer,
    annonçait « en attente de la caméra » **pendant que les caméras tournaient**.
  - ⚠️ Un changement de jeton ne rattrape **pas** les connexions en cours : une
    salle ouverte avant la correction garde ses anciens rôles jusqu'au rechargement.
  - **La scène ne doit jamais mentir sur ce qu'elle voit** (`showCurrentPresenterInStage`) :
    à défaut de présentateur désigné elle montre **quelqu'un qui diffuse vraiment**
    (`premierDiffuseur`, modérateur d'abord) plutôt qu'un écran vide ; et elle ne dit
    « caméra désactivée » que si la publication est **coupée** - une piste pas encore
    abonnée donne « connexion de la caméra… », corrigée seule à l'arrivée de l'image.
  - ⚠️ **Couper sa caméra n'est pas se désabonner** : `TrackMuted`/`TrackUnmuted`
    doivent être écoutés, sinon la tuile garde une image gelée et la scène reste sur
    l'état précédent. Et on retire alors l'image avec **`clearVideo`, jamais
    `clearMedia`** : le son de la personne est attaché à la **même tuile** et partait
    avec l'image - éteindre sa caméra rendait muet.
- **Salle de visio sur téléphone et tablette** (`resources/views/meeting/join.blade.php`,
  `resources/js/meeting.js`) : la salle est une **application plein écran** (`position:fixed`),
  pas une page qui défile. Trois seuils, dans cet ordre dans la feuille :
  **≤900px** le panneau latéral devient un **tiroir** par-dessus la scène (voile + croix,
  fermé au départ), **≤767px** la barre d'outils se compacte et ses commandes secondaires
  passent dans une feuille **« Plus d'options »**, **≤640px** la barre du haut se réduit
  (pastille d'état sans texte, « Quitter » du haut retiré - il est déjà en bas).
  Un bloc **téléphone couché** (`max-height:480px`) amincit les deux barres : c'est
  l'orientation dans laquelle on regarde une visio.
  - ⚠️ **Les hauteurs des deux barres sont des variables** (`--vtop`, `--vtoolbar`) :
    une dizaine de règles s'y accrochent (bandeau d'état, menus, infobulle, panneau de
    volume). Écrites en dur, elles laissaient tous ces éléments à l'ancienne hauteur dès
    que les barres se compactaient.
  - ⚠️ **L'adaptation étroite doit venir EN DERNIER dans la feuille.** Elle était écrite
    avant `.side{display:flex}` : à spécificité égale c'est la dernière règle qui gagne,
    donc la base rouvrait le tiroir. Sur téléphone, le panneau s'affichait **tout seul
    par-dessus les visages** dès l'entrée dans la salle, et la bascule marchait à l'envers.
    Une adaptation responsive ne se place pas « près de la règle qu'elle corrige ».
  - ⚠️ **Les commandes de la feuille ne sont pas RECOPIÉES, elles sont DÉPLACÉES**
    (`rangeSecondaires`, `SECONDAIRES`) : un second jeu de boutons voudrait dire deux
    écouteurs et deux états à tenir d'accord (micro coupé, enregistrement en cours).
    Leur `title` sert d'étiquette dans la feuille (`content:attr(title)`) - rien à écrire
    deux fois. **Au retour en grand écran, on remet du dernier au premier** : l'ancre d'un
    bouton est souvent le bouton suivant, qui doit déjà avoir retrouvé sa place.
  - ⚠️ **Rien ne doit se comprimer jusqu'à zéro** : la droite de la barre du haut
    (`flex-shrink:0`) réclamait 405px sur 375 et écrasait le **titre de la séance à zéro
    pixel** tout en débordant de l'écran. D'où `flex:1` à gauche **et** du contenu retiré
    à droite - l'un sans l'autre ne suffit pas.
  - **`100dvh` + `env(safe-area-inset-*)`** : `inset:0` fait confiance au grand viewport,
    la barre du navigateur recouvrait la barre d'outils et « Quitter » devenait
    inatteignable. `overscroll-behavior:contain` évite qu'un défilement du chat déclenche
    « tirer pour recharger ».
  - Seuil de la barre d'outils à **767px et non 640** : les dix commandes réclament près
    de 700px, ce que l'iPad en portrait (768) offre encore.
  - Garde-fou : `tests/Feature/MeetingTest.php` vérifie la présence des crochets
    (`sideScrim`, `btnCloseSide`, `btnMore`, `moreSlot`) - sans eux le script ne peut plus
    ni fermer le tiroir ni déplacer les boutons.
- **Logos de la plateforme** : réglables dans **Réglages › Général**, servis par
  **`App\Support\Branding`**. **Deux formes**, chacune avec une version pour
  fonds sombres **facultative** :
  - **`wide()` / `wideWhite()`** - lockup complet (marque + nom), là où la largeur
    existe : **pied de page public**, **connexion** et **création de compte**,
    **bandeau (hero) des pages d'événement**.
  - **`mark()` / `markWhite()`** - **monogramme carré**, là où le logo côtoie du
    texte ou se réduit à une colonne d'icônes : **en-tête public**, les **deux
    menus latéraux** (back-office, espace participant) y compris réduits, et la
    **barre de la salle de visio** (fond sombre → `markWhite()`). C'est
    aussi l'`apple-touch-icon` (icône carrée ; les favicons 16/32 restent figés,
    ils exigent ces tailles).
  - ⚠️ **Aucune surface ne redessine la marque elle-même.** La salle de visio
    affichait un « UK » écrit en dur dans le gabarit : le logo réglé dans
    Réglages changeait partout sauf là. Un carré de texte est vite écrit, et c'est
    exactement ce qui fait mentir la configuration.
  - ⚠️ **Choisir la forme par la place disponible, pas par le fond** : un lockup
    large dans un menu réduit à 78 px est illisible, un monogramme seul dans un
    pied de page perd le nom de l'établissement.
  - **Blanchiment automatique, en SECOURS seulement** : sans version claire
    téléversée, la surface sombre sert le **même fichier** avec la classe
    **`.logo-auto-white`** (`brightness(0) invert(1)`), posée quand
    **`Branding::whitensOnDark($cléClaire)`** est vrai. ⚠️ **Ce filtre met TOUTE
    la matière opaque en blanc** : les **évidements** (lettres détourées dans un
    écusson) disparaissent, le logo devient une silhouette pleine. Une vraie
    version claire est un **dessin** - aucun filtre ne la déduit du logo couleur,
    d'où la case facultative. Faux dès qu'une version claire existe (on respecte
    le dessin de l'admin) et faux sur les fichiers livrés : `logo-uk-white.png`
    est déjà dessiné en blanc, et blanchir le monogramme livré en ferait un aplat.
  - **Composition « monogramme + filet + nom »** (en-tête public et menus latéraux
    déployés) : `<img class="brand-logo--mark">` + `<span class="brand-rule">` +
    le texte. Le filet (2 px de large, 26 px de haut, centré) et le nom reprennent
    **`var(--ukc-blue)`** (`#223ea4`), la couleur du logo - c'est ce qui aligne
    les deux. Menu **réduit** : le filet et le texte disparaissent avec
    `.logo-sub` (même règle de masquage), il ne reste que le monogramme, centré.
  - **Repli** sur les fichiers livrés dans `public/img` si rien n'est téléversé ou
    si le fichier réglé a disparu - jamais d'image cassée. Les fichiers vivent sur
    le disque `public` (`branding/`) et le précédent est **supprimé** à chaque
    remplacement (un logo n'a pas d'historique, et l'ancien resterait servi
    publiquement). ⚠️ Ne plus écrire `asset('img/logo-uk.png')` en dur.
  - **Écran de réglages = une carte par forme** (`.ukc-logo-card`), chacune avec
    ses deux dépôts (`.ukc-logo-upload`, même grammaire que la photo de profil) :
    aperçu **sur le fond réel** - blanchiment compris, donc un mauvais rendu se
    voit avant d'enregistrer -, puis *Remplacer* et *Supprimer* (= rendre la main
    au logo livré ; il n'y a jamais « pas de logo »). La remise à zéro passe par
    un **champ caché** `{clé}_remove` piloté par le bouton, qui met aussi l'aperçu
    à jour. ⚠️ La **clé du réglage sert aussi de nom de champ** (`Branding::KEYS`,
    `Branding::FORMS` pour l'appariement clair → sombre) : une seule source.
  Tests : `tests/Feature/PlatformLogoTest.php`.
- **Habillage d'une édition = bandeau (hero) + bannière officielle**, réglés dans
  l'onglet **Apparence** de la fiche d'édition, l'un sous l'autre. Chacun accepte
  un **téléversement OU une URL** (une partie des éditions pointe vers une affiche
  hébergée ailleurs) ; **le fichier l'emporte**, c'est l'action la plus explicite.
  Source unique : **`App\Support\EditionArtwork::resolve()`** (fichier, URL saisie,
  valeur précédente, drapeau de suppression → valeur à stocker).
  - **Où ça vit** : le bandeau dans `edition.settings['hero_image']`, la bannière
    dans la **colonne `editions.banner_url`** (c'est une image de l'édition
    elle-même, reprise dans la liste des éditions passées).
  - ⚠️ **On ne supprime du disque que NOS fichiers** (`editions/` sur le disque
    `public`) : une URL externe ne nous appartient pas. Et ré-enregistrer la fiche
    sans toucher au champ ne doit rien supprimer - d'où la comparaison avec la
    valeur précédente dans `resolve()`.
  - ⚠️ La fiche d'édition poste **tous les onglets d'un coup** : un enregistrement
    qui ne renvoie pas le champ image ne doit pas effacer l'habillage (test de
    garde « garde l'habillage quand on enregistre un autre onglet »).
  - ⚠️ Le `<form id="editionForm">` est en **`enctype="multipart/form-data"`** :
    sans ça, les fichiers n'arrivent jamais et l'échec est silencieux.
  - **Bannière activable** : case `banner_enabled` (settings), **dans le bloc de
    l'image qu'elle gouverne** - elle vivait sous les couleurs, on la croyait
    absente. Le bloc passe en gris quand elle est décochée (`.artwork-field.is-off`).
    Section publique `#banniere` masquée en conséquence (défaut : affichée).
  - La bannière publique a un **espacement interne** (`padding` sur
    `.ukc-banner-frame`) : l'image est **montée dans le cadre**, le dégradé de la
    charte faisant passe-partout, plutôt que collée aux bords.
  - ⚠️ **`aspect-ratio` + `max-height` RÉDUIT la largeur** : `.ukc-banner-frame`
    (21/9, plafond 420 px) se dessinait à 980 px (= 420 x 21/9) et calée à gauche
    de son conteneur. Il faut une **largeur définie** (`width: 100%`) pour que ce
    soit la hauteur qui se recadre, l'image étant en `object-fit: cover`. Le piège
    vaut pour toute boîte à ratio dans un conteneur flex.
  - **Vidéo de fond du bandeau** : même bloc d'habillage (téléversement ou URL),
    MP4/WEBM. ⚠️ Trois branchements manquaient et la fonctionnalité ne marchait
    pas : la case **« Activer la vidéo de fond » était ignorée** (toute URL
    enregistrée se jouait), **`hero_video_muted` n'était jamais lu**, et le
    `type` du `<source>` était **codé en dur en `video/mp4`** (un .webm ne se
    lisait pas). Le contrôleur public ne passe `heroVideo` que si
    `appearance.hero_video_enabled`, et l'image du bandeau sert de **`poster`**
    pendant le chargement.
  - ⚠️ **Le plafond de téléversement est celui de PHP**, pas une constante :
    `EditionArtwork::maxUploadKb()` lit `upload_max_filesize`/`post_max_size` et
    sert **à la fois** la règle `max:` et l'aide affichée. Une règle plus
    généreuse serait un piège : PHP coupe la requête avant la validation et
    l'admin reçoit une page vide au lieu d'un message. En dev (Herd) la limite
    est de **2 Mo** - pour une vraie vidéo de fond, relever
    `upload_max_filesize` et `post_max_size` dans le `php.ini` de Herd, ou
    héberger la vidéo ailleurs et coller son URL.
  - ⚠️ **L'« Actualité (encart du hero) » a été SUPPRIMÉE** (`news_enabled`,
    `news_text`, `news_url`) : ces trois réglages s'enregistraient et se
    relisaient dans le formulaire, mais **aucune vue publique ne les affichait** -
    un aller-retour admin → admin, comme l'ancien déroulé éditorial `programme`.
    Ne pas les réintroduire : le besoin est déjà couvert deux fois, par le
    **bandeau promotionnel** (message court épinglé en haut de la page colloque)
    et par le **module Annonces** (`admin.announcements.*`, actualités datées,
    affichées sur la page Déroulement).
  - Le **bandeau promotionnel** (`settings['promo']['text'|'url'|'cta']`) était le
    cas inverse : **affiché** en haut de la page du colloque mais **éditable nulle
    part**. Il a désormais sa section dans l'onglet **Apparence**, à la place de
    l'encart supprimé. Vide, il ne s'affiche pas.
  Tests : `tests/Feature/EditionArtworkTest.php`.
### Concepteur de documents : la maquette DOIT survivre à l'enregistrement

Le document délivré ne ressemblait pas à celui dessiné dans le concepteur, pour
deux raisons distinctes.

1. **Le modèle enregistré était plus pauvre que la maquette.**
   `serializeLayout()` (`template-designer.js`) aplatit le DOM du concepteur en
   quatre types (`text`, `rect`, `image`, `qr`) : ⚠️ **tout ce qui n'est pas
   repris y est perdu**. Étaient jetés - et sont désormais conservés de bout en
   bout (JS → `LayoutSanitizer` → `LayoutRenderer`) : la **police**
   (`LayoutSanitizer::FONTS`, liste blanche par **clé** - jamais une déclaration
   CSS libre, qui finirait dans un attribut `style`), l'**italique**, le
   **souligné**, les **capitales**, l'**interlignage**, l'**interlettrage**, les
   **coins arrondis** et les **filets** d'un rectangle, et le **fond de page**
   (`bg` était codé en dur à `#ffffff`). ⚠️ Avant d'ajouter une possibilité au
   concepteur, l'ajouter aussi aux trois couches - sinon elle ne survit pas à
   « Enregistrer ».
   ⚠️ Une image n'était retenue que si son `src` était une **URL absolue** : un
   logo téléversé (`/storage/…`) retombait dans la branche « texte » et
   disparaissait. `LayoutSanitizer::src()` accepte désormais les chemins servis
   par le site, et refuse `data:`.
   ⚠️ **Et les images téléversées étaient embarquées en `data:` base64.** Elles
   survivaient à l'instantané de l'éditeur - donc elles restaient visibles dans
   le concepteur - mais `src()` refuse `data:` : l'élément était **jeté de la
   mise en page enregistrée**, et les logos disparaissaient du document délivré,
   en silence. Le concepteur **téléverse** donc ses images
   (`admin.documents.templates.upload` → disque `public`, `documents/templates/`)
   et n'enregistre que leur adresse. Une migration
   (`2026_10_02_000400_restore_document_template_images`) répare les modèles déjà
   enregistrés en récupérant les images de l'instantané.
   ⚠️ **L'image de FOND n'était pas enregistrée du tout** (`bg` ne portait que la
   couleur) : une attestation dessinée sur un fond illustré - le cadre couleurs
   UK, par exemple - ressortait sur du blanc. Elle a désormais sa clé `bg_image`
   (JS → sanitizer → renderer), et la migration
   `2026_10_02_000600_restore_document_template_background` la récupère depuis
   l'instantané pour les modèles déjà enregistrés (seul endroit où le fond avait
   survécu).
   ⚠️ **dompdf ne télécharge aucune image distante** (`enable_remote` est faux) :
   `LayoutRenderer::render(..., pdf: true)` résout les adresses servies par le
   site en **chemins de fichier**, sans quoi les logos sortent du PDF alors
   qu'ils s'affichent sur la page.
   ⚠️ **Trois autres propriétés que dompdf n'applique pas**, et qui faisaient
   diverger le PDF téléchargé de la page :
   - **`object-fit`** : il étire l'image aux dimensions de sa boîte. Un logo
     large dans une boîte plus carrée sortait écrasé. En mode PDF,
     `renderImage()` calcule la boîte lui-même (`getimagesize()`) et centre
     l'image à ses proportions.
     ⚠️ **Conséquence : une image N'A PAS le même coin haut-gauche à l'écran
     et au PDF**, et c'est voulu - le recentrage est ce qui donne le même
     rendu visuel. Le cas « le spécimen a la géométrie exacte du document
     imprimé » (`AttestationPageTest`) compare donc les **centres** pour les
     images (tolérance 1 px, l'arrondi du recentrage) et le **coin au pixel**
     pour tout le reste. Il a cassé net quand les modèles officiels ont
     apporté leurs deux logos : mesuré, 135+230 à l'écran contre 141+219 au
     PDF, soit le même centre. Exiger le même coin reviendrait à exiger du
     PDF qu'il déforme les logos.
     ⚠️ Et le même cas compare le TEXTE en posant **un espace à chaque balise
     retirée** : `strip_tags()` colle le texte de deux éléments voisins dès
     que le balisage ne les sépare pas d'un blanc, et le PDF rendait
     « ...de droit.Fait à Kara » là où l'écran rendait « ...de droit. Fait à
     Kara » - pour un contenu identique.
   - **`text-transform`** : les capitales se perdaient. En mode PDF, le texte
     est mis en capitales **côté serveur**.
   - **les dégradés CSS** : ⚠️ dompdf **ne trace aucun dégradé** (il ne peint que
     la couleur de fond). Trois fonds du concepteur - « Bandes UK », « Simple »
     et « Or discret » - étaient des dégradés posés par une **classe** sur la
     page : le concepteur les affichait, mais la mise en page enregistrée ne
     retient qu'une **image** de fond, donc le document délivré ressortait blanc.
     Ils sont désormais des images (`public/img/bg-attestation-{bands,simple,gold}.png`,
     reproduites au pixel depuis la CSS), comme les deux autres fonds. Migration
     `2026_10_02_000700_restore_document_template_preset_background`.
     ⚠️ **Un fond ajouté au concepteur doit être une image**, jamais un dégradé.
   - **les graisses** : ⚠️ dompdf ne garde que **quatre variantes par famille**
     (normale, grasse, italique, grasse italique). Les six graisses de Jost s'y
     écrasaient deux à deux : un texte dessiné en 500 ressortait en 400, un titre
     en 900 en 700. `App\Documents\DocumentFonts` (source unique des fontes, lue
     aussi par `documents/_fonts`) déclare pour le PDF **une famille par fonte**
     (« Jost 500 »), que `renderText()` vise directement avec `font-weight:normal`.
     ⚠️ Et la pile de polices ne passe **pas** par `e()` : il échappe l'apostrophe
     en `&#039;` et le moteur ne reconnaît plus la famille (`cssValue()`).
   - **le SVG** : ⚠️ dompdf **ne rend pas le SVG** du QR - ni en ligne, ni par
     `<img>`, ni en `data:`. Le QR était donc **absent du PDF téléchargé** alors
     qu'il s'affiche sur la page, et une attestation imprimée sans QR n'est plus
     vérifiable. En mode PDF, `qrBoxes()` dessine la **même matrice**
     (BaconQrCode, niveau L, comme le SVG) en rectangles, les modules d'une même
     ligne étant fusionnés.
   - **`line-height`** : ⚠️ dompdf ne le rapporte **pas au corps du texte** mais
     à la hauteur propre de la police - `(ascendante - descendante + interligne)
     / em`, multipliée par son `font_height_ratio` (1,1). **Avec Jost (1,445 em)
     l'interligne sortait 59 % trop grand**, et un paragraphe de deux lignes
     perdait la seconde, coupée par sa boîte - c'est ainsi qu'une phrase entière
     disparaissait du PDF sans un mot. `fontHeightRatio()` lit la métrique dans
     le fichier de police (FontLib) et l'interligne est compensé ; le texte ne
     **rogne plus** en mode PDF (mieux vaut déborder que disparaître).
     ⚠️ Une police ajoutée à `LayoutSanitizer::FONTS` **et hébergée** doit être
     déclarée dans `LayoutRenderer::PDF_FONTS`, sinon son interligne repart de
     travers dans le PDF.
2. **Les polices n'étaient chargées que dans le concepteur.** Il tirait Jost et
   Playfair Display de Google Fonts ; ni l'aperçu, ni l'attestation délivrée, ni
   le PDF ne le faisaient - la maquette ressortait en police système, avec
   d'autres métriques et du texte qui ne tenait plus dans sa boîte. D'où le
   partial **`documents/_fonts.blade.php`**, à inclure **partout** où une mise en
   page du concepteur est rendue (aperçu, attestation, planche de badges, carte
   du participant, PDF).
   - Les fichiers sont **hébergés par le site** (`public/fonts`), pas tirés d'un
     CDN : ⚠️ **dompdf ne va chercher aucune feuille de styles distante**, et
     c'est lui qui produit le PDF téléchargé. Deux formats pour la même raison :
     **woff2** (navigateurs) et **ttf** (dompdf, qui ne lit pas le woff2).
   - `@include('documents._fonts', ['pdf' => true])` n'émet que des **chemins de
     fichier absolus**, que dompdf résout sur le disque (dans son `chroot`, la
     racine du projet).
   - ⚠️ **`storage/fonts` doit exister et être inscriptible** : dompdf y met en
     cache les métriques (`.ufm`) de chaque police embarquée, et son absence fait
     tomber la génération du PDF en erreur 500.
   - Les graisses déclarées sont **exactement** celles du concepteur : une graisse
     absente serait synthétisée par le moteur, ce qui change encore le rendu.
   Tests : `tests/Feature/DocumentTemplateTest.php`.
3. **Les jetons de la palette n'étaient pas servis.** Deux syntaxes cohabitent -
   `{cle}` (schéma historique des attestations) et **`[espace.cle]`** (celle que
   propose la palette du concepteur et qu'emploient ses modèles livrés). Les
   contrôleurs ne fournissaient que la forme plate : un modèle dessiné à la
   palette sortait avec `[recipient.name]` **vide**, et `[certificate.issued_on]`
   **affiché tel quel** (l'expression régulière ignorait le souligné).
   **`App\Documents\DocumentVars::expand()`** est la table d'alias unique, et
   elle est appliquée **dans `LayoutRenderer::render()`** - pas chez les
   appelants, qui avaient chacun leur liste partielle. ⚠️ Un jeton ajouté à la
   palette doit l'être aussi dans `DocumentVars`, sinon il ne vaudra que dans
   l'aperçu.
4. ⚠️ **Le nom du modèle ne s'enregistrait pas** : le champ de l'en-tête du
   concepteur n'était ni transmis par le JS ni écrit par `save()`, alors que le
   message de confirmation citait le nouveau nom. Il l'est désormais, et un nom
   **vide n'écrase pas** celui déjà enregistré.
   Tests : `tests/Feature/DocumentDesignerTest.php`.
5. ⚠️ **« Des caractères ne s'affichent pas » = la source n'en avait pas.** Les
   modèles livrés par le concepteur étaient rédigés en **ASCII** : ni accents ni
   apostrophes (« Decernee a », « l Universite », « la presente attestation lui
   est delivree », « comite d organisation »). Rien n'était filtré à l'affichage -
   le texte d'origine était ainsi, et les attestations délivrées aussi. Source
   corrigée, **et migration `2026_10_02_000100_fix_document_template_typography`**
   pour les modèles **déjà enregistrés**, qui gardaient l'ancien texte (elle ne
   réécrit QUE ces phrases exactes : un texte personnalisé par un gestionnaire ne
   doit pas changer sous ses pieds). En français, **les capitales s'accentuent**
   (UNIVERSITÉ, MODÉRATION).
6. **Le concepteur parle français.** Son interface était un mélange d'anglais
   (« Save », « Layers », « Add heading », « Paper Size ») et de français sans
   accents, alors que tout le back-office est en français. Libellés, infobulles
   et messages uniformisés.
7. **La barre d'outils SE SYNCHRONISE sur l'élément sélectionné** (`selectEl` →
   `syncTextControls`, **les deux concepteurs** `template-designer.js` /
   `badge-designer.js`) : police, graisse, couleur, gras/italique/souligné et
   alignement reflètent l'élément qu'on vient de choisir - avant, le champ police
   gardait la valeur du précédent. ⚠️ La police s'apparie **par clé** (`fontKey`,
   et `fontKeyB` côté badge), jamais par chaîne CSS brute : le navigateur normalise
   guillemets et espaces, une égalité stricte échouerait.
8. **Garde « modifications non enregistrées ».** Toute modification passe par
   `pushHistory()` (qui pose `dirty = true`) ; les réglages de barre d'outils qui ne
   créent pas d'entrée d'historique (couleur/opacité en continu) posent `markDirty()`.
   ⚠️ L'enregistrement réussi **et** la fin de l'hydratation initiale remettent
   `dirty = false` (la construction du canevas marque sinon le document « modifié »).
   Quitter avec des modifications : le bouton **« ← Documents »** ouvre un **modal
   maison** (Annuler / Quitter sans enregistrer / Enregistrer et quitter), dont le
   style compact est partagé par les deux concepteurs dans `designer-tailwise.css`
   et reprend la confirmation commune de `ukc.css` ; un
   **rechargement / fermeture d'onglet / retour navigateur** déclenche l'avertissement
   **natif** `beforeunload` (seul moyen pour ces cas - un navigateur n'autorise pas de
   modal maison là). ⚠️ En **mode aperçu verrouillé** (`cfg.preview`), la garde est
   désactivée : rien n'y est modifiable. Le `onclick` inline du bouton retour est
   remplacé en JS (`backBtn.onclick = null` puis `addEventListener`).

### `/attestation/{uuid}` = la page que le bénéficiaire partage

C'est l'adresse que porte le **QR code** du document et celle qu'un titulaire
envoie à un recruteur : elle doit tenir debout seule. Elle n'affiche donc plus
seulement le document imprimable, mais **le document, qui l'a délivré, ce qu'il
certifie et comment le vérifier** (modèle : les pages de vérification type
Certifier/Credsverse).

- **Le document d'abord**, rendu par le modèle enregistré comme ailleurs
  (`LayoutRenderer` + `documents._fonts`), puis **Bénéficiaire** (partage, ajout
  au profil LinkedIn, PDF, copie du lien), **Vérification** (état, date
  d'émission, identifiant, contrôle sur place) et **Délivré par** +
  **Ce que certifie ce document**.
- ⚠️ **Une attestation n'a QU'UNE adresse** : `/attestation/{uuid}`. Une
  attestation **révoquée** l'annonce en tête - un document révoqué ne doit pas
  pouvoir se présenter comme valide.
- ⚠️ **Un intitulé sans valeur n'est pas affiché** (`documentFacts()` filtre les
  vides) : une ligne « Thème : » vide donne l'impression d'un gabarit cassé.
- Le document est **mis à l'échelle de son cadre** par un petit script de la vue
  (`.ukc-cred-doc`, largeur réelle en `data-doc-width`) : une mise en page est
  dessinée à une largeur fixe, et on n'impose pas de défilement horizontal au
  visiteur. Le cadre **épouse** le document (`--doc-w`), sinon une bande blanche
  reste à côté d'une mise en page plus étroite que la colonne.
- Styles `.ukc-cred-*` dans `resources/css/public.css` (charte UK : le public
  n'est jamais habillé en Tailwise). `@media print` ne laisse sortir que le
  document.
- **Signataire** : `[signatory.name]` / `[signatory.role]` n'avaient aucune
  source - la ligne de signature sortait **vide** sur le document délivré alors
  que le concepteur montrait un exemple. Ils viennent des réglages
  `attestations.signatory` / `signatory_role` de l'édition (onglet **Options**
  des attestations).
- ⚠️ **Papier blanc sur page blanche** : rien ne distinguait le document de la
  page. Le cadre porte un filet et une ombre portée, et le fond de page un gris
  franc (`#eef1f6`). Colonne de gestes à **350 px**, page à **1300 px**.
- ⚠️ **Les valeurs de rendu ont une source unique** : `App\Documents\AttestationVars`.
  La page et le PDF dressaient la même liste chacun de leur côté, donc un jeton
  ajouté à l'une manquait à l'autre.
- ⚠️ **La vérification se fait SUR CETTE PAGE** : le bouton interrogeait une
  autre adresse, et le visiteur quittait le document pour lire un verdict. Il
  appelle `attestation.verify.json` et le résultat s'écrit dans un panneau de
  la page. Le contrôle est **refait** (registre + signature), ce n'est pas un
  repli du texte déjà affiché. Pour la même raison, **le QR du document pointe
  vers `/attestation/{uuid}`** et non plus vers `/verifier/{uuid}` : on scannait
  une attestation pour atterrir sur un verdict, sans le document.
- ⚠️ **`/verifier/{uuid}` N'EXISTE PLUS** (route supprimée) : elle faisait double
  emploi avec la page de l'attestation, qui porte le document **et** sa
  vérification. Tout ce qui la citait désigne maintenant `/attestation/{uuid}` -
  **y compris le `credentialStatus.id` des attestations signées**
  (`AttestationCertifier::buildCredential()`) et le `verify_url` du VC
  téléchargeable.
- ⚠️ **Une signature déclare une ADRESSE : la changer périme les signatures.** Les
  attestations signées avant ce changement déclaraient `/verifier/{uuid}`, qui ne
  résout plus - un vérificateur tiers suivant ce `credentialStatus.id` tombait sur
  un 404. Le stock (5 attestations) a été **re-signé**. ⚠️ **Le même travail sera
  à refaire à la mise en production** : `APP_URL` passera de `geves.test` au vrai
  domaine, et toutes les signatures déclareront encore `geves.test`. Remède :
  `php artisan attestations:sign --force` (il re-signe TOUT le stock, y compris
  les attestations non signées - l'émission est en fail-open).
- `/verifier` (**saisie d'un code**) demeure : on y arrive sans identifiant, elle
  ne double rien. Elle mène à `/attestation/{uuid}`, et ⚠️ **vérifie que le code
  existe avant d'y envoyer** : un code inconnu reste sur le formulaire avec un
  message et la saisie sous les yeux - la faute de frappe se corrige là où elle a
  été faite, pas sur un 404.
- **Gestes au survol du document** (partage, LinkedIn, PDF, vérification), en
  surimpression. ⚠️ Le survol n'existe pas au doigt : la colonne de gauche porte
  les mêmes actions, et la surimpression est masquée en `@media (hover: none)`.
  Un seul partial, `attestations/_actions.blade.php`, pour que les deux ne
  divergent pas. ⚠️ **Voile légèrement sombre, sans flou** : flouter le document
  revient à cacher ce qu'on vient regarder.
- **Voir en grand** : réduit à la largeur d'un téléphone, un document dessiné à
  900 px n'est plus lisible. Le bouton loupe l'ouvre à l'échelle de l'écran, et
  il reste **visible en permanence** sans survol.
- ⚠️ **Le QR de vérification encodait une adresse FIXE et sans identifiant**
  (`DEFAULT_QR_URL` = « …/attestation-verify ») : scanner deux attestations
  différentes menait au même endroit, qui ne vérifiait rien. Il encode le jeton
  `{verify_url}`, et l'aperçu du concepteur affiche l'adresse d'exemple (un
  jeton ne se scanne pas). Migration
  `2026_10_02_000500_fix_document_template_qr_target` pour les modèles
  enregistrés ; une adresse **personnalisée** (QR « Custom ») n'est pas touchée.
  Un test de garde interdit le retour d'une adresse codée en dur.
- ⚠️ **L'instantané de l'éditeur ne retenait pas l'adresse du QR** (`qrUrl`) : à
  la réouverture le jeton était perdu et l'image gardait l'adresse d'alors. Elle
  fait désormais l'aller-retour, et le QR est redessiné à la restauration.
  Tests : `tests/Feature/AttestationPageTest.php`, `tests/Feature/DocumentDesignerTest.php`.
### Le site PUBLIC dit « evenement », pas « colloque » (2026-10-11)

La plateforme gere des colloques, des seminaires ET des journees
scientifiques (`EventType`) : « Colloques a venir » annoncait donc moins que
ce que le site contient. Les sections, les titres de page, les boutons et les
phrases de l'assistante disent **evenement**.

⚠️ **« Colloque » reste legitime a deux endroits, et seulement la :**

- quand il designe **le TYPE** : l'enumeration « seminaires, colloques et
  journees scientifiques », le filtre et la pastille des archives ;
- dans les **identifiants** : la route `/colloque/{slug}` (des liens sont
  deja partis par e-mail), les classes CSS `.colloque-grid` /
  `.colloque-card`, le partial `partials.colloque-footer`, la variable
  `$colloqueUrl`, l'adresse colloque@univkara.tg, `GescoController`.

⚠️ **Renommer un libelle cree une cle neuve** : sans traduction, la version
anglaise redevient un panachage. 58 cles ajoutees a `lang/en.json` dans la
foulee. Quatre accords faux corriges au passage (« evenements cloturees »,
« Evenements archivees », « a venir ou passees », et le sous-titre des
evenements a venir).

Tenu par `tests/Feature/VocabulairePublicTest.php` : liste d'exception
EXPLICITE pour les deux usages legitimes, et garde de traduction sur toute la
surface publique.

### Indicateurs de l'accueil : ce qu'ils mesurent VRAIMENT (2026-10-11)

⚠️ **Deux des quatre étaient faux ou inutiles**, et les clés mentaient :
`event_series` portait des **événements**, `formats` des **séries**.

- ⚠️ **« Établissements représentés » ne comptait pas des établissements** mais
  des **chaînes libres distinctes**. Mesuré sur le colloque de l'eau : 44
  inscrits, 44 chaînes distinctes - même après normalisation. Parce que chacun
  saisit son LABORATOIRE (« Laboratoire Hydrosciences Lavoisier, Université de
  Bangui », « Groupe de recherche … de l'Université Joseph KI ZERBO »), deux
  laboratoires d'une même université comptaient pour deux. Le chiffre suivait
  donc le nombre d'inscrits, et l'accueil affichait **44 à côté de 44**.
  Compter de vrais établissements demanderait de rapprocher ces textes libres
  d'un référentiel - ROR est déjà là (`RorClient`) - : c'est une
  fonctionnalité, pas un compteur. **Ne pas réintroduire ce `distinct()`.**
- ⚠️ **« Colloques & séries »** comptait les séries, que le visiteur ne
  distingue pas des événements, et le mot « colloque » contredit le modèle
  typé (séminaires, journées scientifiques).
- Les quatre chiffres racontent maintenant le parcours de la plateforme :
  **événements, communications déposées, participants, attestations
  délivrées**.
- ⚠️ **Une carte à zéro ne s'affiche pas** (même règle que le bandeau entier),
  et la grille est passée de `repeat(4, 1fr)` à des cartes plafonnées et
  centrées : à quatre colonnes fixes, une carte masquée creusait un trou.
- L'accord « Événements organisées » était faux, comme « Prochaine événement à
  venir » et « Mémoire des événements précédentes ».

### Le nom de l'assistante publique se RÈGLE, il ne se recopie pas

Par défaut **« Peace »** (`AiAgents::defaults()`). ⚠️ La **clé** reste `gesco` :
c'est un identifiant, enregistré tel quel dans les réglages des installations
existantes, et il sert à retrouver l'agent (`AiAgents::find('gesco')`, route
`public.gesco`, `GescoController`, `AskGesco`). **Seul le nom s'affiche.**

⚠️ Le nom était recopié en dur à **dix endroits** : bouton du widget, pied de
page, deux fois sur l'accueil, deux fois dans l'espace participant, la page de
connexion, deux vues de réclamations, et - celui qui a le plus échappé - le
message « L'assistance Gesco est momentanément indisponible » d'`AskGesco`,
plus l'étiquette des tours de l'assistante dans l'historique envoyé au modèle.
Changer le nom dans **Réglages › Agents IA** n'en changeait aucun.

Source unique : **`AiAgents::publicAgent()`** et **`AiAgents::publicName()`**.
Tenu par `tests/Feature/NomAssistanteTest.php`, qui balaie `resources/views`
**et `app/`** - une première version ne lisait que les vues et laissait passer
le message d'`AskGesco` ; ce sont deux cas existants qui l'ont révélé, pas la
garde. Les commentaires (y compris en fin de ligne) et `AiAgents` lui-même sont
exclus : l'un explique, l'autre est la source.
### ⚠️ Un `<input>` dans une grille `1fr 1fr` déborde sur téléphone

La rangée « Votre nom / Votre e-mail » du formulaire de contact débordait.
Deux causes, et il fallait les deux :

1. **`min-width: auto` d'un `<input>` vaut sa taille INTRINSÈQUE** (188px
   mesurés) : les deux pistes `1fr` refusaient de descendre sous 188, soit
   388px de rangée dans une carte de 293 sur un écran de 375. D'où
   `min-width: 0` sur les enfants - la même ligne que `.layout-plan > *`.
2. La grille était posée en **style EN LIGNE**, qui l'emporte sur toute
   règle de feuille, **media query comprise** : l'adaptation étroite
   n'aurait eu aucun effet tant que la grille restait sur la balise. D'où
   la classe `.contact-form-row`, qui passe à une colonne sous 560px.

### Bulle de l'assistante : son diametre est un JETON

`--ukc-cb-fab` (dans `:root`, `ukc.css`) : **84px**, ramene a **68px** sous
560px - sur un telephone la bulle recouvre une part bien plus grande de
l'ecran.

⚠️ **Quatre regles s'y accrochent** : la bulle, la mascotte qu'elle contient
(`diametre - 12px`, la bordure blanche comprise), le decalage ET la hauteur
du panneau pose au-dessus d'elle, et le degagement laisse en bas des pages
qui la portent (`:has(.ukc-cb-root)`, **deux** regles). Ecrites en dur, elles
se decalent des qu'on touche au diametre : un `bottom: 76px` fige laissait le
panneau **recouvrir** une bulle de 84. Meme lecon que `--vtop` dans la salle
de visio et `--edtop` dans les concepteurs.

⚠️ Le garde-fou de politesse (`initChatbotPoliteness`) **mesure** la bulle
(`getBoundingClientRect`) : il suit le jeton sans rien a changer. Ne pas y
reintroduire de taille en dur.

Tests : `tests/Feature/BulleAssistanteTest.php` (4 sabotages ; l'un a montre
qu'un `str_contains` sur le fichier entier ne voyait pas qu'UNE des deux
regles de degagement avait ete remise en dur - on examine chaque bloc).

### Page d'accueil : on n'annonce que ce qu'on a (2026-10-11)

- ⚠️ **Un lien vers une ancre que la page ne rend pas ne fait RIEN.** « En
  cours », « À venir » et « Passés » visent `#en-cours` / `#a-venir` /
  `#passes`, dont les sections n'existent qu'en présence d'événements.
  (« Passés » pointait d'abord vers la PAGE d'archives ; elle reste
  atteignable depuis la section elle-même et depuis le pied de page.) Sur geves.univkara.tg, qui n'a aucun
  colloque en cours, le menu promettait une section absente. Les **trois**
  surfaces concernées sont gardées par `navHasOngoing` / `navHasUpcoming`
  (composeur de `AppServiceProvider`) : l'**en-tête**, le **pied de page** et
  le bouton « Colloques à venir » de **Déroulement**. ⚠️ Une seule oubliée et
  le lien mort revient par là - c'est le pied de page qui avait été oublié, et
  c'est le cas Pest qui l'a trouvé.
- ⚠️ **Le bandeau d'indicateurs tombe quand tout est à zéro.** Sur un site
  neuf, « 0 événement - 0 colloque - 0 participant - 0 établissement » donne
  une plateforme à l'abandon. On masque le bandeau **entier** : retirer deux
  cartes sur quatre laisserait une rangée trouée (même raison que pour les
  ateliers).
- ⚠️ **Un renseignement qu'on n'a pas ne se remplace pas par sa forme.** La
  section Contact affichait « +228 XX XX XX XX » au public. Ligne retirée ; le
  reste du bloc (adresse, e-mails) est écrit en dur et porte de vraies valeurs.
  Pour un vrai numéro, il faudra un réglage.
- ⚠️ **Une marque ne se redessine pas dans une vue.** La carte « À propos »
  portait un carré avec « DRI » écrit à la main : le logo téléversé dans
  Réglages ne s'y appliquait pas. Elle porte désormais `Branding::markWhite()`
  (carte sombre), avec `logo-auto-white` en secours. Même travers que le « UK »
  en dur de la salle de visio.
- ⚠️ **« Vérifier une attestation » n'est plus dans la barre du haut**, mais
  elle reste dans les **liens utiles du pied de page** : cette page existe
  pour un TIERS - un recruteur qui contrôle une attestation reçue - et la
  retirer de toute navigation la rendrait introuvable à qui n'a pas le lien.
- Tests : `tests/Feature/PageAccueilTest.php` (sabotages vérifiés).
  ⚠️ L'un d'eux ne mordait pas : il cherchait l'adresse du monogramme **dans
  toute la page**, où elle figure aussi dans le `<link rel="apple-touch-icon">`
  et dans la marque de l'en-tête. Vidé de son `src`, le logo de la carte
  disparaissait sans que rien ne tombe. On regarde DANS la pastille.

### ⚠️ Une clé `__()` d'un seul mot peut rendre un TABLEAU

Laravel lit la partie d'une clé qui précède le premier point comme un
**groupe** de traduction. `__('Pagination')` cherche la clé dans
`lang/<locale>.json` ; faute de l'y trouver, il charge le **groupe**
`pagination` - et le framework en livre quatre : `auth`, `pagination`,
`passwords`, `validation`. Il rend alors le tableau entier, et
`htmlspecialchars()` lève « must be of type string, array given ».

⚠️ Le défaut n'apparaît **que dans une langue** (celle qui a un fichier de
groupe, donc l'anglais) et **que sur un disque insensible à la casse**
(Windows, macOS), où `lang/en/Pagination.php` tombe sur `pagination.php`.
Résultat : `/admin/utilisateurs` rendait une **erreur 500 en anglais sur tout
poste de développement** et fonctionnait en ligne, sous Linux.

La parade : une clé qui porte le nom d'un groupe **doit** exister dans
`lang/en.json` (la recherche JSON passe en premier et court-circuite le
groupe). C'est le cas de « Validation », l'étape du dépôt, qui reste valide ;
l'aria-label de la pagination, lui, est devenu « Navigation des pages ».
Tenu par `tests/Feature/ClesDeTraductionTest.php`.

### ⚠️ Cloudflare masque les e-mails, et son script ne tourne qu'une fois

Derrière Cloudflare, l'option **« Email Address Obfuscation »** (Scrape Shield)
réécrit toute adresse de la page en
`<a class="__cf_email__" data-cfemail="HEX">[email protected]</a>`, et injecte un
script qui les redéchiffre dans le navigateur. **Ce script ne tourne qu'au
chargement.** Une région remplacée en AJAX arrive donc masquée et le reste :
`/admin/utilisateurs` affichait de vraies adresses au chargement, et
« [email protected] » partout dès la première recherche.

- ⚠️ **Rien de tel en local** : geves.test est servi par nginx, sans proxy
  devant. Le défaut ne se voit qu'en ligne, d'où une garde qui lit la SOURCE.
- `ukcDecodeCfEmails(racine)` (`app.js`) rejoue le déchiffrement de Cloudflare
  (premier octet de `data-cfemail` = clé, le reste en XOR) et est appelée par
  les **deux** mécanismes de remplacement du projet, `data-ajax` et
  `data-filter`, **avant** `Alpine.initTree` - un composant qui lit le texte
  doit voir l'adresse, pas le substitut.
- Le correctif de fond est côté Cloudflare : désactiver l'option, ou poser une
  règle de configuration qui l'écarte sur `/admin/*`. Le filet côté client rend
  le back-office correct quel que soit ce réglage.
- Tests : `tests/Feature/EmailsMasquesCloudflareTest.php`.
### Bandeaux du site = Réglages › Bannières (2026-10-11)

Deux images de niveau **plateforme**, servies par **`App\Support\SiteBanners`** :
`hero_home_image` (bandeau de la page d'accueil) et `hero_event_default_image`
(**repli des autres pages publiques**, clé conservée pour les réglages existants).
Les pages d'événement gardent la priorité à leur visuel propre ; les pages
Archives, archive d'événement, direct et co-auteur utilisent aussi ce repli.
Même grammaire que `Branding` pour les logos :
la **clé du réglage sert aussi de nom de champ**, un fichier **remplace** le
précédent, et un réglage pointant vers un fichier disparu rend `null`.

- ⚠️ **Les clés n'ont PAS de point**, contrairement à `site.facebook` : Laravel
  lit un point comme un niveau de tableau, et `hasFile('hero.home')` ne
  trouverait rien.
- ⚠️ **Le repli ne passe JAMAIS devant le choix d'un événement** :
  `EventSeriesController` écrit `($settings['hero_image'] ?? null) ?: SiteBanners::pageDefault()`,
  dans cet ordre. Un cas Pest le gèle - un bandeau de site qui écraserait
  l'affiche d'un colloque serait pire que pas de bandeau du tout.
- ⚠️ **`SiteBanners::pageDefault()` rend une URL**, pas un chemin : c'est ce
  que stocke `settings.hero_image` (cf. `EventArtwork`), et le repli doit
  pouvoir s'y substituer sans conversion.
- **Accueil** : `home.blade.php` pose `.hero--image` + la variable `--hero-bg`,
  et `public.css` ne surcharge que le `background-image` du calque `::before`.
  Le dégradé `--ukc-hero` reste en fond de `.hero` : une image lente, ou
  absente, ne laisse jamais une bande blanche.
- ⚠️ **Le voile d'un bandeau est bâti sur `--ukc-hero-navy` (#14205a)**, celui
  de `.detail-hero .bg::after`. Les deux voiles du bandeau d'événement
  portaient #0f1a33, la teinte que la charte écarte - **écrits EN LIGNE dans la
  vue**, ils échappaient à `DegradeHerosTest`, qui relit les feuilles de style.
  Tant que de rares événements téléversaient un visuel, cela se voyait peu ;
  avec la bannière par défaut, c'est le voile de **tous**. Un cas de
  `SiteBannersTest` interdit le retour d'un **dégradé** en #0f1a33 - la teinte
  reste permise en **aplat** (ombres portées, pastille de vignette, voile de
  modale : sept usages légitimes dans `public.css`).
- ⚠️ **Le plafond de téléversement suit le serveur**
  (`SettingController::bannerMaxKb()`, 4 Mo ou moins) : une règle `max:` plus
  généreuse que `upload_max_filesize` ne sert à rien, PHP coupe la requête avant
  la validation. Et **pas de SVG** : l'image est recadrée en `cover`.
- ⚠️ **Après un téléversement, la page revient sur l'onglet « Bannières »** et
  non sur « Général » : sinon l'admin perd le seul retour qui compte, l'aperçu
  de ce qu'il vient de poser. L'aperçu vide montre le **dégradé de la charte**,
  c'est-à-dire ce que le fichier va remplacer.
- Tests : `tests/Feature/SiteBannersTest.php` (10 cas, 4 sabotages vérifiés).

⚠️ **Piège Pest rencontré ici** : `toContain()` prend **plusieurs aiguilles**,
pas un message. `expect($x)->not->toContain('motif', "explication")` nie « A ET
B », donc passe dès que l'explication manque - **par construction**. Le cas ne
mordait jamais ; seul un sabotage l'a montré. Relever les fautifs dans un
tableau et comparer à `[]` : le message d'échec nomme alors le fichier et la
ligne.
### Onglets d'une page = pilules + URL

Toutes les sous-navigations du back-office sont en **pilules**
(`.ukc-nav-docs`) et **portées par l'URL** (`?tab=`), donc partageables par lien.
⚠️ `.ukc-tabs` (onglets soulignés) ne subsiste que sur **Documents ›
Attestations/Livret** : ne pas s'en servir de modèle.

Deux mises en œuvre, selon la page :

1. **Navigation serveur** (Programme, Soumissions) : chaque onglet est une URL
   rendue par le contrôleur, définie une seule fois dans un `_nav.blade.php`.
   C'est le cas général.
2. **Réécriture d'URL sans rechargement** - composant Alpine **`ukcUrlTabs`**
   (`app.js`) : le clic bascule le panneau et réécrit l'URL (`pushState`), et les
   flèches du navigateur suivent (`popstate`). ⚠️ **Réservé aux pages dont tous
   les panneaux vivent dans UN SEUL formulaire** : **Réglages** et la **fiche
   d'édition**. Une vraie navigation y perdrait les saisies non enregistrées des
   autres onglets. Les onglets restent de vrais `<a href>` (ouvrables dans un
   nouvel onglet), neutralisés par `@click.prevent`.

- **Réglages** : onglets et libellés dans **`SettingController::TAB_LABELS`**
  (`TABS` pour la validation de `?tab=`), parcourus par
  `admin/settings/_nav.blade.php`. ⚠️ `?tab=` **filtré sur les clés connues** :
  une clé fantaisiste n'afficherait aucun panneau et la page paraîtrait vide.
- ⚠️ **Les Agents IA sont un ONGLET** (`?tab=agents-ia`), plus une page : la
  route `/admin/reglages/agents-ia` est une **`permanentRedirect`** (signets,
  liens déjà envoyés), `SettingAgentController` n'a plus que son `update()`, et
  l'éditeur vit dans `admin/settings/_agents.blade.php`. Son **formulaire est
  distinct** de celui des réglages (PUT sur sa propre route) : il est donc placé
  **hors** du formulaire général - deux formulaires imbriqués ne s'enregistrent
  pas. Le bouton « Enregistrer les réglages » est masqué sur cet onglet, sans
  quoi deux boutons d'enregistrement voisinaient sans dire ce qu'ils couvrent.
  Tests : `tests/Feature/AdminSettingsTest.php`, `tests/Feature/AiAgentsTest.php`.
- ⚠️ **Avatar d'agent = galerie fournie OU image téléversée (tuile « + »).** Les huit
  visages vivent dans **`public/img/agents/`** (`marc-*` hommes, `awa-*` femmes), déclarés
  dans **`AiAgents::GALLERY`**. Marc (copilote admin) a `marc-1` par défaut, l'agent public
  (Gesco) `awa-1` ; pour un nouvel agent, l'admin pioche parmi les huit (`galleryForJs()`
  alimente l'éditeur Alpine `agentsEditor`) **ou téléverse la sienne** via la **tuile « + »**
  en fin de galerie (aperçu local `agent.upload` = objectURL, non posté ; seul le fichier
  `avatars.$i` l'est). On stocke donc soit un **chemin de galerie** (`img/agents/…`), soit
  un **chemin de téléversement** (`ai-agents/…` sur le disque `public`), soit vide.
  `SettingAgentController` valide le champ texte `avatar` ∈ galerie ∪ '' ∪ avatars **déjà
  enregistrés** (⚠️ tolérance depuis les agents STOCKÉS, jamais la requête qui s'autoriserait
  elle-même) ; un **fichier** envoyé l'emporte, et l'**ancien fichier** devenu orphelin est
  supprimé (jamais une image de galerie). Rendu via **`AiAgents::avatarUrl()`** (source
  unique) : `img/…` → `asset()`, URL absolue telle quelle, sinon repli `Storage::url()`.
  L'**aperçu de l'éditeur est agrandi (96px)** pour le distinguer des vignettes (52px).
  ⚠️ Avatar téléversé : **pas de SVG** (servi depuis notre domaine, peut porter du script).

### Actions IA du back-office = AJAX + voile de travail

Une analyse IA dure dix à soixante secondes. Sans retour visuel, l'admin
recliquait ou quittait la page en croyant à un blocage ; et une génération qui
rechargeait la page faisait perdre la saisie en cours.

- **Voile de travail** : **`window.ukcAiVeil`** (`show({title, steps})`,
  `done(message, ok)`, `hide()`) couvre l'écran du bleu de la charte, fait
  défiler les étapes et compte le temps écoulé. ⚠️ `z-index: 120`, **au-dessus
  des modales (100) et des slideovers (60)** : une analyse lancée depuis un
  panneau doit le couvrir. **Aucun clic ne le ferme** - on ne propose pas
  d'annuler ce qu'on ne sait pas interrompre côté serveur. Compteur `depth`
  pour les appels imbriqués, et retrait forcé au retour arrière (bfcache).
- **Déclenchement déclaratif** : l'attribut **`data-ai`** sur un formulaire *ou*
  sur le bouton cliqué suffit ; `data-ai-title` et `data-ai-steps` (étapes
  séparées par `|`) règlent le texte. ⚠️ Avec `data-confirm`, le voile se lève
  **après** la popup (sinon il la recouvrirait, et resterait affiché en cas de
  renoncement) : le point d'accroche est dans le gestionnaire de confirmation,
  car `form.submit()` ne déclenche aucun évènement `submit`.
- **Passage en AJAX** : ajouter `data-ajax` + `data-ajax-target="<clé>"` au
  formulaire et envelopper le bloc de résultat dans `data-ajax-root="<clé>"`.
  Fait pour **Rapports › Export personnalisé (IA)** (`ai-report`) et
  **Secrétariat › Générer un document** (`sec-doc`). Les panneaux de la **fiche
  d'édition** et des **formations** étaient déjà en `fetch` : ils appellent
  désormais le voile.
- ⚠️ **`ukcAjaxSubmit` lit la réponse du POST**, qui est déjà la page
  rafraîchie (fetch suit la redirection), **messages flash compris**. Le second
  GET d'avant consommait le flash et vidait le bloc de résultat - c'est ce qui
  aurait cassé toutes ces actions IA, dont le résultat passe précisément par un
  flash. Repli sur un GET seulement si la racine est absente de la réponse.
- ⚠️ Un bloc rafraîchi **ne doit pas contenir le formulaire** quand la saisie
  doit survivre à une erreur ; sinon, flasher l'entrée (`back()->withInput()`)
  et repartir de `old()` dans l'état Alpine (fait pour le Secrétariat).
- **Paramétrage assisté de la fiche d'édition** : glisser-déposer réel (la zone
  s'appelait « Déposer un document » mais ne réagissait qu'au clic), documents
  cumulatifs et retirables un par un (8 maximum), et surtout un **rappel
  explicite** après « Appliquer au formulaire » - ⚠️ l'IA **ne fait que remplir
  les champs**, la fiche reste à enregistrer, et l'admin partait en croyant
  l'édition créée.
- ⚠️ `admin.editions.ai-assist` (variante non-JSON de `aiAssistJson`) **n'a plus
  aucun appelant** depuis le passage du panneau en fetch : ne pas s'en servir de
  modèle pour une nouvelle action.

### Présence d'une séance = pointée par le modérateur OU le rapporteur

- ⚠️ **Deux verdicts : présent ou absent.** « Excusé » a existé et a été retiré
  (migration `2026_10_04_000100`, lignes versées dans `absent`). Il ne pesait
  **rien en aval** - seul `present` compte dans le PV (`SessionReportController`)
  et dans l'attestation (`DocumentController`) - mais il ajoutait un troisième
  choix à trancher pendant qu'on appelle des gens à la voix. Un motif d'absence
  se dit dans le compte rendu, pas dans une colonne qui ne sert à aucun calcul.
  ⚠️ Les anciennes lignes deviennent `absent` et **non** « non pointé » : un
  modérateur avait regardé sa salle, c'est un verdict, pas un blanc - et le
  remettre à zéro ferait remonter le compteur des gens à appeler.
  ⚠️ `SessionAttendance::STATUSES` est la **source unique** : les deux
  contrôleurs valident sur ses clés, donc y remettre une valeur l'ouvre partout.

`/compte/seances/{session}/presence` (`Account\SessionAttendanceController`,
table `session_attendances`, modèle `SessionAttendance`). Entrées depuis
**Séances à animer** et **Séances à rapporter**.

- **Les deux rôles y ont droit.** Selon les séances, c'est le modérateur qui
  anime pendant que le rapporteur tient la liste, ou l'inverse. Imposer un seul
  des deux aurait condamné la moitié des séances au papier.
- ⚠️ **On ne pointe QUE les auteurs des communications de la séance**
  (`App\Support\SessionRoster`), **jamais l'effectif de l'édition**. La première
  version listait les 215 inscrits : illisible, et elle demandait au modérateur de
  se prononcer sur des gens qui n'ont rien à faire dans sa salle. La liste tient
  donc sur un écran - **ni recherche ni pagination**, on appelle des gens, on ne
  fouille pas un annuaire.
  - C'est aussi la **garde d'écriture** : `update()` n'accepte que les inscrits de
    la liste de la séance, pas ceux de l'édition. Un identifiant posté n'est pas
    une autorisation.
  - Une personne qui signe **deux communications** de la même séance n'a qu'**une**
    ligne : deux donneraient deux verdicts contradictoires sur une même présence.
  - ⚠️ Un **co-auteur sans inscription** sur l'édition est affiché mais **non
    pointable** : le cacher ferait croire à une liste d'auteurs tronquée, et il n'y
    a aucune inscription à laquelle rattacher une présence.
  - Une séance **sans communication** (plénière, cérémonie) affiche un état vide
    qui l'explique - elle ne retombe pas sur la liste de l'édition.
- **La page de présence RAMÈNE dans la salle** (`MeetableRegistry::tabFor()`) :
  on vient d'y pointer des gens pendant qu'on anime, repasser par la liste des
  séances est un détour que personne ne fait.
  - ⚠️ **L'onglet de la salle porte un nom** (`window.name`, posé par
    `meeting/join.blade.php` pour les DEUX lecteurs), et le lien de retour le
    pose en `target` : le navigateur **réutilise** cet onglet au lieu d'ouvrir
    une seconde salle - deux connexions, deux vignettes et de l'écho. Le nom a
    **une seule définition** ; deux chaînes construites chacune de leur côté se
    décaleraient un jour, et l'onglet cesserait d'être retrouvé sans que rien ne
    casse visiblement.
  - ⚠️ **Pas de `rel="noopener"`** sur le lien de la salle vers la page, et
    c'est délibéré : la page ouverte est la nôtre, même origine, et elle a
    besoin de `window.opener` pour remettre l'onglet de la salle devant **sans
    la recharger**. Un rechargement en ferait sortir puis rentrer le modérateur
    sous les yeux de toute l'assistance.
  - ⚠️ `focus()` sur un autre onglet **peut être ignoré** selon le navigateur.
    D'où une vérification et non une supposition : 400 ms plus tard, si la page
    est encore `visible`, c'est que le clic n'a rien fait - on suit alors le
    lien. **Un bouton qui ne fait rien est pire que le rechargement qu'on
    voulait éviter.**
- **La présence se tient AUSSI depuis la salle** (onglet « Présence » du lecteur,
  `SessionPresenceApiController` + `resources/js/meeting.js`) : l'aller-retour vers
  `/compte/seances/{id}/presence` obligeait à quitter la séance au moment précis
  où l'on regarde les gens. ⚠️ **Les règles ne sont pas redéfinies là-bas** : qui
  peut pointer (`SessionPresence::canManage()`), quand (`::open()`) et sur qui
  (`SessionRoster`) ont **une seule** implémentation - trois endroits qui
  décideraient chacun de leur côté finiraient par diverger. L'onglet n'apparaît que
  si le serveur a donné les adresses : c'est lui qui décide, pas le navigateur.
- **Entrer dans la visio VAUT présence** (`App\Support\SessionPresence`), par deux
  chemins complémentaires : `MeetingController::join()` au passage de la porte, et
  **`::markConnected()` sur l'occupation RÉELLE de la salle** lue par
  `App\Meetings\LiveKitRoom` (API LiveKit `ListParticipants`), rafraîchie tant que
  le panneau est ouvert. Même cadrage et mêmes bornes que le pointage manuel.
  - ⚠️ **Le rapprochement passe par l'IDENTITÉ LiveKit**, `ukp-{participant}-{aléa}`
    (`LiveKitRoom::identityFor()`), **jamais par le nom affiché** : celui-ci se
    change depuis la salle. L'aléa est obligatoire - LiveKit refuse deux connexions
    de même identité, et on ouvre la salle sur deux appareils.
  - ⚠️ **Le jeton d'admin doit nommer la salle EXACTE** : `room: '*'` est refusé
    (401 « permissions denied »). Et un **404 « room does not exist » n'est pas une
    panne** : LiveKit ne crée la salle qu'à la première connexion, donc 404 = salle
    vide (`[]`), tandis qu'une vraie panne rend `null` = **on ne sait pas**. Le
    panneau n'affiche « En salle » que si l'information est connue : ne jamais
    laisser croire que personne n'est là quand on l'ignore.
  - ⚠️ Sans LiveKit configuré, il ne reste que l'entrée depuis GEVES : **Jitsi
    public n'expose aucune API de liste de participants**. D'où le libellé « Entré
    en visio » - on ne fait pas dire à une donnée plus qu'elle ne sait.
  - ⚠️ **Un verdict déjà posé n'est JAMAIS écrasé.** Quelqu'un a regardé la salle ;
    un clic sur « Rejoindre » ne vaut pas contre ce constat, et faire repasser un
    « absent » à « présent » dans le dos du modérateur lui ferait signer un chiffre
    qu'il n'a pas vu.
- **Le pointage automatique s'affiche À MI-CHEMIN, et se confirme d'un clic**
  (`gestePointage`, règle pure). Il compte **déjà** dans « Présents » : rien
  n'est perdu si personne ne le confirme. Mais il dit seulement que quelqu'un
  est entré dans la salle, pas que le modérateur l'a constaté - et c'est lui qui
  endosse le chiffre dans le PV. D'où le bouton **plein** (constaté) contre
  **pointillé** (déduit, `aria-pressed="mixed"`), la pastille « À confirmer N »
  qui disparaît une fois tout vu, et la bascule :
  - ligne **automatique**, même verdict → **confirme** (`source` passe à `manual`,
    `marked_by` prend le modérateur) ;
  - ligne **de sa main**, même verdict → **retire** (défaire une erreur sans
    quitter la salle) ;
  - autre verdict → **pose**, dans les deux cas.
  ⚠️ Sans cette distinction, le geste le plus naturel - appuyer sur le verdict
  qu'on approuve - **effaçait** la présence au lieu de l'endosser.
  ⚠️ Retirer un pointage sur quelqu'un **encore en salle** le fait revenir en
  pointillé au rafraîchissement suivant : `markConnected()` reconstate une
  connexion qui est toujours vraie. Ce n'est pas un bug - le fait machine tient,
  seul l'aval humain a été retiré. Pour écarter quelqu'un, on pose « Absent »,
  qui est un verdict et que rien n'écrase.
- **Les étiquettes SONT les filtres** (`presencesFiltrees`, `filtrePresenceTenable`,
  règles pures). On lit « Absents 3 » et on veut voir lesquels : demander de le
  redire ailleurs serait une étape pour rien. « Attendus » est le chemin de
  retour vers la liste entière, non pointés compris - ce sont eux qu'il reste à
  appeler.
  ⚠️ **« À confirmer » peut disparaître sous le doigt** : c'est souvent le
  dernier clic du modérateur qui l'escamote, pendant qu'il la regarde. Le filtre
  **se replie** alors sur « Attendus » - sinon il resterait une liste vide et
  plus aucune étiquette pour en sortir.
  ⚠️ `compteAConfirmer()` est défini **par** le filtre, pas à côté : le nombre
  sur l'étiquette et la longueur de la liste qu'elle ouvre doivent être le même
  calcul.
  ⚠️ Le filtre choisi **survit au rafraîchissement** (20 s) : repartir sur
  « Attendus » toutes les 20 secondes ferait sauter la liste sous les yeux.
  ⚠️ Bordure transparente sur TOUTES les étiquettes : réservée à l'allumée, elle
  décalerait la rangée au moment du clic.
  ⚠️ **Défaut gelé par un cas** : les trois boutons portaient `.on` sans
  `data-st`, alors que la feuille colore `.on[data-st=…]`. Le verdict était
  enregistré **sans jamais se voir** - trois boutons identiques sous un compteur
  qui disait « Présents 1 ». Les deux moitiés de l'état (classe et attribut) se
  posent désormais au même endroit.
  - Colonne **`source`** (`manual` / `meeting`) : la page signale les lignes posées
    par la visio, parce que le modérateur **endosse ces chiffres dans le PV**.
    Dès qu'il enregistre, la ligne repasse `manual` - même s'il ne fait que
    confirmer, elle est de sa main.
- ⚠️ **Une table `session_attendances` a existé puis été supprimée**
  (migration `2026_09_17_000200`) : elle n'était alimentée QUE par le **scan de
  badge**, retiré. Elle revient avec une source humaine - ne pas la relire comme
  un reliquat du scan.
- ⚠️ **Trois verdicts, et « non pointé » n'en est pas un** : `present`, `absent`,
  `excused`. **Pas de ligne = pas encore appelé**, ce qui n'est pas la même
  information qu'un absent **constaté**. D'où le compteur `unmarked`, rapporté aux
  **attendus de la séance** : « 12 présents » ne dit rien sans savoir 12 sur combien.
- ⚠️ **L'enregistrement est INCRÉMENTAL et non destructif** : `update()` ne touche
  QUE les participants présents dans la requête. On pointe par petits lots pendant
  la séance (liste paginée, recherche en cours) - une sauvegarde qui remettrait à
  zéro ce qui n'est pas à l'écran effacerait le lot précédent. La vue le **dit** à
  l'utilisateur, sinon la prudence lui ferait tout ressaisir.
- **Fenêtre** : ouvert au début de la séance (`hasStarted()`), fermé par l'archivage
  de l'édition et par la **validation du rapport** par le secrétariat - le PV cite
  ces chiffres, ils ne doivent plus bouger après. Lecture toujours possible.
- ⚠️ **`Participant::wasPresent()` s'appuie dessus**, et c'était un trou : depuis le
  retrait du scan, **rien n'écrivait `checked_in_at`**, donc l'option « présence
  requise » des attestations de participation ne laissait passer **personne**. La
  présence en séance est désormais la source réelle. Le filtrage se fait **en SQL**
  (`participationTargets()`), pas par `filter()` sur une collection : une édition
  compte des centaines d'inscrits.
- ⚠️ **Un comptage l'emporte sur une estimation** : le PV (`documents.session-pv`,
  section `presents`) affiche les présents **pointés** dès qu'il y en a, et
  l'« affluence estimée » du rapporteur seulement à défaut - en disant lequel des
  deux il montre. Le champ estimé reste : une plénière de 300 ne se pointe pas.
- Tests : `tests/Feature/SessionAttendanceTest.php`.

### Programme synthétique = un onglet par JOUR

- `partials/program-synthesis.blade.php`, **partagé** par la page publique et
  l'aperçu de l'onglet Programme : l'admin doit voir exactement ce qui est publié.
- ⚠️ **Un onglet = un JOUR, pas un bloc de saisie.** Une journée se saisit souvent
  en deux blocs (« Matin », « Après-midi ») : un onglet par bloc affichait quatre
  pastilles « Matin » côte à côte, et le visiteur qui cherchait le 3 octobre ne
  savait pas laquelle ouvrir. Les blocs d'une même date sont **regroupés** et leur
  intitulé devient un **intertitre** dans le jour.
- ⚠️ **Jamais trié** : les jours sortent dans l'ordre où l'admin a rangé ses blocs,
  flèches à l'appui. C'est lui qui décide de la succession.
- ⚠️ Un bloc **sans date** garde son propre onglet : rien ne dit à quel jour il
  appartient, et le coller au précédent inventerait une information.
- ⚠️ **Mesuré** : le surtitre « Jour N » à `opacity: .72` tombait à **2,8:1** sur
  les onglets inactifs (gris clair sur blanc, en 10,5px). La hiérarchie se fait à
  la taille et à la graisse, qui ne coûtent aucun contraste - tout est ≥ 7,5:1.
- ⚠️ **Mesuré** : le jour s'étalait sur TOUTE la largeur du conteneur (1316px à
  1391px de fenêtre), sans respiration latérale - la date collée à gauche,
  « 1 moment » collé à droite, à quatorze cents pixels l'un de l'autre. Le jour
  est posé sur une **surface** (carte blanche, ~30px de marge intérieure) et le
  bloc est **borné à 960px** : passé ~900px, l'œil perd la ligne entre l'heure et
  son libellé. La borne vaut pour les onglets, qui sont dans le même bloc.
- ⚠️ L'aperçu de l'onglet admin **garde la même surface**, sur un fond teinté : sa
  raison d'être est de montrer exactement ce qui est publié, et l'y retirer parce
  qu'il vit déjà dans une carte ferait diverger les deux rendus - ce que le
  partage de ce partial sert précisément à éviter.
- ⚠️ **Mesuré** : `.prog-public-preview` était bridé à **760px** dans une carte de
  **1114px** - plus de quatre cents pixels de vide à droite. Le bridage est levé :
  c'est la largeur de la page publique (960px) qui décide, et c'est bien elle que
  l'aperçu doit montrer. Un bridage propre à l'admin le ferait mentir.
- ⚠️ **Mesuré** : à 20px de rembourrage, sept onglets de jour totalisaient 958,7px
  dans une piste de 960 - **un pixel** de marge en public, et 13px de trop dans
  l'aperçu admin (le zoom 90% ne rend pas les mêmes largeurs), où « Jour 7 »
  tombait seul sur une seconde ligne. À 16px, les deux gardent ≈ 100px de jeu.

### `/verifier` est de la famille de la page d'attestation

Les deux répondent à la même question - « ce document est-il authentique ? » -
et l'une mène à l'autre : la saisie de code emprunte donc `.ukc-cred` (fond
dégradé, barre de marque, pastille d'état, cartes à surtitre), et non l'en-tête
et le pied de page du site. On y arrive un diplôme à la main, pas en naviguant.

- ⚠️ **`--ukc-mono` n'est PAS une chasse fixe** : `ukc.css` la définit comme
  `var(--ukc-font)`, un alias de la police du site. Tout ce qui s'en remet à
  elle - références, numéros de reçu, identifiants d'attestation - s'affiche en
  proportionnel malgré le nom. Le champ de `/verifier` nomme donc sa pile
  monospace : y recopier 36 caractères est le propos même du champ.
- ⚠️ **Mesuré à 375px** : ces 36 caractères ne tenaient pas dans un champ de
  302px à 14,5px - le code collé défilait dans sa boîte et ne pouvait plus se
  relire en entier. La taille suit la largeur de l'écran (`clamp`).
- ⚠️ La colonne fait **920px** (cartes de 840) : ce sont les CARTES qui
  s'élargissent, pas les lignes de texte. À 760px la prose était déjà à **74
  caractères par ligne**, le haut du confortable ; elle est donc bornée à 62ch
  tandis que le champ et le bouton prennent toute la carte - un formulaire se
  remplit, il ne se lit pas. Mesuré après : 62 caractères.

### Le nom court se saisit SANS l'année

La barre collante de la page colloque affiche `nom court - année` : l'année est
ajoutée par la vue, elle ne se saisit pas.

- ⚠️ L'exemple proposé sous le champ était « CSIUK 2026 », ce qui poussait
  justement à la saisir - une édition s'est retrouvée annoncée **« Colloque
  Avenir durable 2025 - 2026 »**, deux années contradictoires sur la même ligne.
  Un placeholder est un **exemple à copier** : s'il montre la faute, elle est
  commise.
- Le champ affiche désormais **la ligne telle qu'elle sortira**, et prévient
  quand le nom court se termine par une année.
- Ce bloc est aussi le lien **« retour en haut »** de la page et porte la pastille
  d'état : le retirer coûterait plus que l'année qu'on y lisait en double.

### Onglets de la fiche d'édition

- **Onze onglets dans UN seul écran** (`admin/editions/form.blade.php`), pilotés
  par `?tab=` **sans navigation** : les panneaux vivent dans le même formulaire,
  recharger perdrait les saisies des autres onglets.
- **« Intégrations » réunit paiement et visioconférence** : deux formulaires de
  même nature - brancher l'édition sur un service extérieur - qui faisaient
  chercher au même endroit deux fois. Chacun **garde sa sauvegarde** : les fondre
  ferait d'une correction de clé d'API une réécriture des deux configurations.
- **« Traductions » a rejoint « Apparence »** : les deux faces de ce que le
  visiteur voit du site public.
  - ⚠️ Le panneau des traductions reste **hors du formulaire principal** (une
    locale par formulaire, un bouton par langue) : imbriquer un formulaire dans
    un autre n'existe pas en HTML. D'où **deux panneaux frères** qui s'allument
    sur le même onglet - et un rappel explicite que les traductions ont leur
    propre bouton, sinon on croit que « Enregistrer » les emporte.
- ⚠️ **Les anciens noms d'onglets sont traduits** (`payment`/`meetings` →
  `integrations`, `translations` → `appearance`). Un lien partagé, un favori ou
  une session ouverte porte l'ancien nom : sans cela, la page s'ouvre sur un
  onglet qui n'existe plus - aucune pilule allumée, **aucun panneau affiché**.
- ⚠️ **Le cadre blanc d'un monogramme est INVISIBLE sur une barre claire** : son
  rembourrage ne se lit pas comme une marge intérieure mais comme du vide, qui
  s'ajoute à la gouttière du groupe. Dans la marque des barres latérales, 6px de
  rembourrage + 10px de gouttière mettaient le logo à 16px du filet quand le
  texte n'en était qu'à 10 - l'écart de gauche paraissait moitié plus grand. Le
  cadre épouse l'image, et seule la gouttière espace. **Mesuré : 10 / 10.**
  (L'en-tête public, lui, n'a pas de cadre : il était déjà à 12 / 12.)
- **Répéteurs de la fiche** (membres de comité, tarifs, partenaires) : une carte
  par entrée, champs **étiquetés** (pas seulement des indications de saisie, qui
  disparaissent une fois remplies), et la pastille destructive **partagée**
  (`ukc-btn--icon danger`, déjà utilisée par le programme) - une couleur inventée
  au cas par cas, et deux écrans ne diraient plus la même chose du même geste.
  - Le cadre du logo d'un partenaire **est le bouton de téléversement** : on
    téléverse un fichier OU on colle une URL, et **l'aperçu s'affiche dans les
    deux cas** - c'est lui qui dit si l'adresse pointe bien sur une image, un
    logo absent du bandeau public se découvrant sinon après publication. Une URL
    qui ne charge pas se dit (« Image introuvable ») au lieu de laisser l'icône
    cassée du navigateur, qu'on prend pour une panne de la fiche.
  - ⚠️ **Hauteur du cadre = hauteur des champs** (`align-items: stretch` sur la
    ligne, `flex: 1` sur le cadre), jamais une valeur fixe : à 76px la colonne de
    gauche s'arrêtait au milieu de la seconde ligne de champs.
  - ⚠️ **La liste se réordonne** (flèches), et son ordre **est** celui du bandeau
    public. Deux conséquences, toutes deux coûteuses si on les oublie :
    - Côté client, la clé de `x-for` est **l'identifiant de la ligne** (`p.uid`),
      jamais son index : Alpine recycle les nœuds à clé égale, et le fichier déjà
      choisi dans un champ de téléversement resterait sur place pendant que la
      ligne se déplacerait - **on enregistrerait le logo d'un partenaire chez son
      voisin**. Vérifié au navigateur : le fichier suit bien sa ligne.
    - Côté serveur, le ménage des logos devenus orphelins se fait sur les **deux
      listes entières** (`EditionArtwork::forgetMissing`), jamais ligne à ligne :
      après un déplacement, l'index 0 ne désigne plus le même partenaire.
  - ⚠️ **Pas de SVG en téléversement** : il serait servi depuis notre domaine et
    peut porter du script - un logo de partenaire vient d'un tiers.
  - ⚠️ Le survol rouge générique de la fiche vise les **retraits** ; sous `.p-acts`
    il est ramené au bleu pour les flèches, qui ne détruisent rien.
  - ⚠️ Le champ **« Niveau » ne sort nulle part** : le bandeau public n'affiche que
    logo, nom et lien. Il porte donc une pastille « interne » - sans elle, on croit
    à un classement qui n'existe pas.
  - ⚠️ **Mesuré au harnais** : à 375px, la grille à trois colonnes réduisait les
    champs à 144px et tronquait les noms longs **au milieu d'un mot**. Sous 560px,
    l'aperçu prend donc toute la ligne.
- ⚠️ **PIÈGE BLADE, payé trois fois ici** : ne jamais écrire les directives de bloc
  PHP de Blade en toutes lettres dans un `{{-- commentaire --}}`. Blade extrait
  les **blocs de code avant** de retirer les commentaires : il prend le texte du
  commentaire pour du vrai code, avale tout ce qui précède, et la page tombe sur
  une « variable indéfinie » ou un `endif` orphelin **très loin de la cause**. De
  même, un bloc `php … endphp` posé juste après une directive PHP en ligne se
  télescope avec elle : ce fichier n'utilise **que** la forme en ligne.
  - ⚠️ Et **jamais la forme courte d'Alpine avec une arobase** quand le nom
    d'événement est aussi une directive Blade : `@error` sur une image a été
    compilé comme l'erreur de validation de Blade, ouvrant une condition jamais
    refermée. On écrit `x-on:error`.

### Lettre d'invitation PDF (type de document `invitation`) (2026-10-28)

L'e-mail d'invitation d'un participant (`ParticipantInvitationMail`, boutons
individuel et groupé de **Participants**) porte désormais en **pièce jointe une
lettre PDF personnalisée**, rendue depuis un **modèle éditable du concepteur**.

- ⚠️⚠️ **CE N'EST PAS UNE ATTESTATION.** C'est un **type de document à part**,
  `invitation`, édité dans le concepteur (`?kind=attestation&type=invitation`)
  comme les attestations, mais **jamais offert ni émis** comme attestation : il
  n'est **pas** dans `Attestation::TYPES` ni dans le `$typeMeta` du hub. Il est
  autorisé explicitement dans `TemplateDesignerController::kindType()` (allowlist
  `ATTESTATION_TYPES` **+ `invitation`**). Pas de QR, pas d'identifiant, pas de
  signature cryptographique : on **convie**, on n'atteste pas.
- ⚠️⚠️ **MAQUETTE A4 PORTRAIT (794 × 1123)**, contrairement aux attestations
  (paysage 900 × 636). La page du concepteur est landscape par défaut : la taille
  portrait est posée par `loadTemplate('invitation')` **et round-trippée dans le
  snapshot** (`pageW`/`pageH` dans `snapshot()`/`restoreSnapshot()` de
  `template-designer.js`) - sans cela, rouvrir le modèle le ramenait au paysage et
  l'enregistrement suivant l'écrasait.
- ⚠️ **Twin PHP ↔ JS obligatoire** (règle du projet) : la maquette est décrite
  DEUX fois à l'identique - `LayoutSanitizer::invitationLetter()` (défaut PHP,
  utilisé pour le PDF délivré quand aucun modèle n'est enregistré) et
  `loadTemplate('invitation')` dans `template-designer.js` (édition). Logos +
  texte seulement (pas de `rect` : `createEl` impose au `line-h` un 3px noir, qui
  divergerait du défaut). Toucher à l'un, c'est toucher à l'autre.
- **Rendu** : `App\Documents\InvitationLetter::pdf(Participant)` - même pipeline
  que `AttestationController::pdf` (`ensureFor('attestation','invitation')` →
  `LayoutSanitizer::sanitize` → `LayoutRenderer::render(pdf:true)` → dompdf, page à
  la taille du layout). Valeurs de fusion : `App\Documents\InvitationVars::for()`
  (calqué sur `AttestationVars`, SANS verify/uuid/communication/formation ;
  identité via `Participant::eventIdentity()`, signataire via `attestations.signatory*`).
  Jetons `[espace.cle]` résolus par `DocumentVars::expand()` dans le renderer.
- **Pièce jointe** : `ParticipantInvitationMail::attachments()` (le rendu dompdf
  tourne sur le worker, le mailable est `ShouldQueue`). Le corps markdown de
  l'e-mail est inchangé.
- ⚠️ **Destinataires : TOUS les non-invités par défaut.** L'envoi groupé
  (`ParticipantController::inviteBulk`) invitait seulement les **payés** ; il
  invite désormais tout non-invité, **sauf** si l'événement a coché « n'inviter
  que les payés » (`EventSettings`, clé **`invitation.restrict_paid`**, case sur la
  page Participants, `saveInvitationOptions`). L'invitation individuelle reste
  ouverte à tout participant.
- Gardes : `InvitationLetterTest` (layout portrait sans QR, valeurs de fusion, PDF
  `%PDF`, pièce jointe, périmètre par défaut vs. `restrict_paid`).
  `AdminParticipantsTest` : le périmètre par défaut de l'envoi groupé a changé.

### Règles métier - limites communications & attestations

- **Limite de communications** (`settings.max_communications`) : compte **seulement** les
  communications où le compte est **auteur correspondant** (participant déposant,
  `Submission::usedCountFor()`). Illimité en tant que **co-auteur**.
- **Types d'attestation proposés PAR DÉFAUT** (hub Documents, `DocumentHubController::$typeMeta`) :
  communication, participation, modération, rapporteur, **participation à l'organisation**,
  **comité d'organisation**, **instruction de résumés** (relecteurs), formation, animation de formation. ⚠️ **« soumission » et « acceptation » ne sont PLUS
  offerts d'office** - ils restent dans `Attestation::TYPES` (données existantes) mais ne
  sont ni créés (`DocumentTemplate::ensureFor`) ni listés. Ne pas les réintroduire dans
  `$typeMeta` sans demande.
  - ⚠️ **LES MENUS SE DRESSENT SUR `$typeMeta`, PAS SUR `Attestation::TYPES`.** Les
    deux listes déroulantes de la page les prenaient encore de la constante, donc elles
    proposaient toujours « Soumission » et « Acceptation » : on pouvait créer un modèle
    d'un type que rien n'émet plus, et filtrer les délivrées sur deux types qui n'y
    figurent jamais. `$offerts = array_intersect_key(Attestation::TYPES, $typeMeta)` est
    la liste proposée - l'ordre et les libellés courts restent ceux de la constante.
  - ⚠️ **MAIS LE FILTRE PORTE SUR CE QUI EXISTE** : `filterTypes` ajoute aux types
    offerts ceux **réellement délivrés sur cet événement**, sinon une attestation
    « soumission » déjà en base deviendrait introuvable. ⚠️ Il se lit sur **toutes**
    les attestations, pas sur le décompte `byType` qui écarte les révoquées - un type
    dont tout est révoqué doit rester filtrable. Un type retiré ne revient donc jamais
    dans le menu de **création**, seulement dans celui du **filtre**, et seulement s'il
    a servi.
  - ⚠️ **`TemplateDesignerController::SCOPES` doit couvrir chaque type offert** : la
    portée s'affiche en lecture seule à la création d'un modèle, et `instruction_resumes`
    y manquait - il retombait sur « Participant », c'est-à-dire sur une portée fausse.
    Les clés retirées y restent : un ancien modèle s'ouvre encore dans le concepteur.
  Garde : `DocumentsTest` (« ne propose que les types d'attestation offerts… »,
  3 sabotages vérifiés).
- **Attestations adossées à l'ÉQUIPE** (`event_user`), rapprochées vers les `Participant` par
  `user_id` via **`DocumentHubController::teamRoleTargets($event, $include, $exclude)`** :
  `$include` → doit porter au moins un de ces rôles ; `$exclude` → n'en porte aucun
  (l'exclusion l'emporte sur l'inclusion). Un membre d'équipe sans fiche `Participant` sur
  l'événement n'est pas atteint (comme modération/rapporteur). Deux types l'utilisent :
  - **Participation à l'organisation** (type `organisation`, appui au comité) : **toute
    l'équipe SAUF les trois comités** (`exclude: scientific, comite_supervision,
    comite_organisation`). Test : `AttestationOrganisationTemplateTest`.
  - **Comité d'organisation** (type `comite_organisation`, les membres du comité) :
    `include: comite_organisation`. Un compte qui porte ce rôle **et** un rôle d'appui le
    reçoit (l'inclusion suffit). Test : `AttestationComiteOrganisationTemplateTest`.
  ⚠️ Les deux sont complémentaires : « les autres ont travaillé en appui au comité ».
- **Modération / rapporteur = UNE attestation par PERSONNE et par événement** : le
  bénéficiaire est une personne qui a **modéré** (resp. **rapporté**) **au moins une
  séance**, dédoublonnée. Éligibilité via `DocumentHubController::sessionRoleTargets()`
  (rapprochement par e-mail sur `event_sessions.moderator_email` / `rapporteur_email`,
  pas sur `participant.role`), émission idempotente par (événement, participant, type).
  ⚠️ Ce n'est PAS « par séance » : quelqu'un qui modère trois séances n'a qu'une
  attestation. Test : `DocumentsTest` (« émet UNE attestation de modération par
  personne… »).
- **Attestation de communication = par communication ET par personne** : sur chaque
  communication acceptée, l'auteur correspondant **et** chaque co-auteur **payé** ont une
  attestation à leur nom, liée à la communication (`attestations.submission_id`). Deux
  plafonds : `settings.coauthors_max` (co-auteurs attestés / communication) et
  `settings.attestation_max` (attestations de communication / personne - quand on dépasse,
  on **privilégie les communications où la personne est auteur correspondant**, puis les
  autres par ordre chronologique). Calcul dans `App\Support\CommunicationAttestations` ;
  émission via `IssueAttestation::handle($participant, 'communication', $submission)`.
- **En ligne ou sur papier** (`App\Support\AttestationPolicy`, option
  `attestations.delivery` par édition, **en ligne par défaut**) :
  - **En ligne** : le modérateur **valide le passage** d'une communication pendant
    la séance, et ses auteurs reçoivent aussitôt leur attestation (in-app,
    e-mail, SMS si ouvert) ; chacun la retrouve dans « Mes documents ».
  - **Papier** : le passage se constate quand même - il dit qui est monté à la
    tribune - mais **rien n'est émis ni envoyé** : le secrétariat émet le lot,
    le relit et l'imprime. L'attestation reste **consultable** dans l'espace du
    participant : lui fermer aussi cette porte n'y renverrait que ses questions.
  - ⚠️ **Un seul endroit décide** (`AttestationPolicy`). Trois chemins lisent ce
    choix - émission en masse, validation du passage, annonce au bénéficiaire -
    et chacun en tirerait son interprétation si le réglage était relu brut.
  - ⚠️ **SMS fermé par défaut** (`attestations.sms`) : chaque envoi est facturé, et
    une édition de deux cents communications en enverrait autant sans que personne
    ne l'ait décidé. Rien ne part non plus sans passerelle configurée - le
    fournisseur « log » est celui du développement.
- **Passage d'une communication = fait tenu en base** (`slots.presented_at` /
  `presented_by`, `ValidateCommunicationPassage`). La salle le marquait déjà
  (« Marquer effectuée ») mais **seulement dans les navigateurs** : la marque
  partait sur le canal de données et mourait avec la séance.
  - ⚠️ **Deux portes, un seul geste** : l'onglet Communicateurs de la visio **et**
    la page de modération (une séance en salle n'a pas de visio, et c'est pourtant
    là que les communications ont lieu). Même contrôleur, même garde.
  - ⚠️ **Le créneau doit appartenir à la séance** : sans cette vérification, le
    modérateur d'une salle validerait le passage d'une communication tenue
    ailleurs - et en enverrait les attestations.
  - ⚠️ **Irréversible côté séance**, et idempotent. Une attestation partie ne se
    reprend pas : elle est signée et porte un QR de vérification. L'erreur se
    répare au back-office, en **révoquant**.
  - ⚠️ Le constat est posé **avant** l'émission et séparément : si l'émission
    échoue à mi-parcours, le passage reste constaté (il a bien eu lieu). L'inverse
    ferait disparaître le fait parce qu'un envoi a échoué.
  - ⚠️ Mêmes ayants droit que la présence (`SessionPresence::canManage`) :
    modérateur **ou** rapporteur. Le réserver au seul modérateur laisserait sans
    recours une séance qu'il a dû quitter.
- **Qui reçoit une attestation de communication** (`attestations.communication_scope`) :
  **tous les auteurs concernés** (défaut) ou **seulement les pointés présents** à la
  séance où elle est programmée. ⚠️ Le filtre s'applique **avant** le plafond par
  personne : écarter d'abord les absents laisse la place à une communication où la
  personne était là. ⚠️ Une communication **sans séance au programme** n'est jamais
  écartée - c'est au programme qu'il manque quelque chose, pas à l'auteur.
  ⚠️ Les deux chemins (masse et séance) lisent la même option, sinon le bouton
  utilisé changerait la liste des bénéficiaires.
  ⚠️ **Le plafond par personne se compte différemment** selon le chemin : en masse,
  on classe tout (priorité auteur correspondant) ; en séance, on compte le **déjà
  émis**, parce qu'on ne voit pas les passages à venir et que ce qui est parti est
  parti. **Premier passé, premier servi.**
- **Côté bénéficiaire, le papier donne un SPÉCIMEN** : l'attestation se **voit**
  et se **vérifie** dans son espace, mais ne se télécharge ni ne se partage -
  l'exemplaire qui fait foi est celui qu'on lui remet. Sans cette coupure, deux
  documents identiques circuleraient, dont un que l'émetteur n'a jamais délivré.
  - ⚠️ **`/attestation/{uuid}/pdf` est fermé au public** en mode papier, sinon la
    restriction serait cosmétique. Le **back-office garde l'accès** : c'est lui
    qui imprime (cf. le lot).
  - ⚠️ **Le spécimen EST le document imprimé, plus la mention.** C'est sa mise en
    page que le bénéficiaire vient regarder : un aperçu qui diffère de l'original
    ne lui apprend rien et lui ferait découvrir le vrai document le jour de la
    remise. Les deux sortent du **même modèle enregistré**, par le **même
    moteur** ; le drapeau `pdf` de `LayoutRenderer` ne change que l'embarquement
    des images et des polices (dompdf ne va rien chercher à distance) - **jamais
    une position, une taille ni un contenu**. Gelé par un cas qui compare les
    deux rendus, géométrie et texte.
    - ⚠️ **Seule exception connue, et documentée** : le QR est un **SVG** à
      l'écran et des **blocs** dans le PDF (dompdf ne rend pas le SVG, cf.
      `qrBoxes()`). Même cadre, même emplacement, autre tracé - le cas compare
      donc les positions **entières** (éléments de mise en page et cadre du QR)
      et laisse de côté les modules, aux coordonnées décimales.
  - ⚠️ **La mention se pose PAR-DESSUS le rendu**, jamais dans la mise en page :
    le même modèle sert au lot que le secrétariat imprime, et une marque qui y
    entrerait sortirait sur les **originaux**.
  - ⚠️ **Mesuré au harnais** : `font-size: clamp(42px, 13%, 150px)` se calcule sur
    la police **héritée**, pas sur la largeur - le filigrane restait figé à 42px
    sur un document de 1000px. Il se dimensionne désormais sur `--doc-w`, que la
    vue porte déjà (et qu'il a fallu poser **aussi** sur la vue agrandie).
  - ⚠️ Le sens n'est **pas porté par le filigrane seul** (il est `aria-hidden`,
    et volontairement pâle pour ne pas masquer le texte) : un encadré l'explique,
    et la ligne de « Mes documents » dit si l'exemplaire a été remis ou reste à
    retirer.
- **« Mes documents » montre TOUTES les attestations de l'édition** : une par
  communication (`keyBy('submission_id')`), une par formation suivie **ou
  animée**, et **une par FONCTION exercée** - modération, rapport, comité
  d'organisation, appui à l'organisation, instruction de résumés.
  - ⚠️⚠️ **Une personne cumule les fonctions, et ses attestations avec.** La
    page ne rendait que `participation` : les autres types étaient bien
    **chargés** par le contrôleur - ils tombaient dans `$atts` - mais **aucune
    ligne ne les affichait**. Un modérateur ne pouvait ni voir ni retirer
    l'attestation qu'on lui avait pourtant délivrée. Section « Mes fonctions sur
    cet événement ».
  - ⚠️ **La liste part des attestations ÉMISES, pas d'une énumération de types**
    (`whereNotIn` sur les quatre déjà rendus par leurs propres sections) : elle
    couvre ainsi tout ce qui est délivré, y compris les types retirés des menus
    mais encore en base (« soumission », « acceptation ») et ceux qu'on
    ajoutera. Énumérer les types, c'est s'engager à penser au suivant.
  - ⚠️ **Elle n'annonce AUCUNE attestation à venir** : sans document délivré,
    pas de ligne. Pour une ligne « Après le colloque » comme en porte la
    participation, il faudrait les règles d'éligibilité du hub
    (`sessionRoleTargets`, `teamRoleTargets`, `reviewerTargets`) dans une source
    partagée - elles sont aujourd'hui privées, côté back-office. **À faire** si
    le besoin se confirme ; les recopier serait la divergence assurée.
  - ⚠️ **L'intitulé complet a UNE source : `Attestation::titles()`.** Le hub du
    back-office dressait sa propre liste (`$typeMeta`) et l'espace participant
    allait en inventer une seconde - elles divergeaient déjà sur l'animation de
    formation (« Attestation d'animation » contre « … de formation »).
    `Attestation::TYPES` reste le libellé COURT, celui des filtres et des
    pastilles : les deux ne se remplacent pas.
  - ⚠️ **Défauts gelés par des cas** : indexées par *type*, l'attestation de la
    dernière communication s'affichait en face de **toutes** les autres (deux
    communications, un seul document) ; et la page ne montrait qu'**une** ligne
    « Attestation de formation », sans dire laquelle - qui avait suivi trois
    ateliers pouvait croire les deux autres perdues.
  - ⚠️ Le titre de la formation vient de **`meta`** (`Attestation::formationTitle()`),
    figé à l'émission : une attestation doit dire ce qu'elle disait le jour où
    elle a été délivrée, pas ce que le catalogue dit aujourd'hui.
- **Sortie PAPIER** (`AttestationExportController`) : le mode physique existait
  sans le geste qui le rend tenable - il fallait ouvrir les attestations **une par
  une** à leur adresse. Trois sorties, et une trace :
  - **Lot PDF** (`admin.documents.attestations.lot`) : une attestation par page,
    prêt à imprimer. ⚠️ **Un seul type par fichier** - la taille de page vient de
    la mise en page du modèle, et un lot mêlant A4 paysage et A5 portrait
    sortirait rogné. ⚠️ **Lots bornés** (`AttestationExportController::LOT`) :
    chaque attestation est composée élément par élément, et un millier d'un coup
    épuiserait la mémoire du générateur **au milieu** du rendu - page blanche
    après dix minutes. Un lot borné échoue vite, ou réussit vite.
  - **Liste d'émargement** (`…attestations.emargement`) : le pendant papier de
    l'accusé de réception. Pas de découpage en lots (c'est un tableau), en-tête
    répété sur chaque page - une liste qui se signe sur plusieurs feuilles ne se
    lit plus sans ses titres de colonnes.
  - **Remise en main propre** (`attestations.delivered_at` / `delivered_by`) :
    ⚠️ **émettre n'est pas remettre.** Sans cette trace, une attestation imprimée
    attend dans une chemise sans que personne sache ce qui reste à distribuer.
    **Réversible**, contrairement au passage d'une communication : se tromper de
    ligne au guichet est banal, et une remise fausse ferait croire que quelqu'un a
    déjà son document.
  - ⚠️ **Les exports portent sur les filtres de l'écran** (`App\Support\AttestationQuery`,
    partagé avec la liste) : on imprime ce qu'on regarde. Deux constructions de
    requête finiraient par diverger, et le lot ne correspondrait plus à la liste -
    sans rien pour s'en apercevoir.
- **La page qui émet dit ce que l'émission déclenche** : bandeau du mode de
  délivrance, nombre de destinataires **et** d'écartés (`CommunicationAttestations::preview()`),
  confirmation chiffrée. ⚠️ Un nombre qui baisse sans explication se lit comme une
  panne - et on réimprime pour rien. ⚠️ Le bouton « Émettre et exporter » ne
  générait **aucun PDF** : il émettait et rendait la main. Il mène désormais à la
  liste délivrée filtrée sur le type, où les lots attendent.
- **Documents = modèles éditables** : badges/attestations utilisent le **modèle conçu par
  l'admin** (concepteur `admin/modeles`, `TemplateDesignerController` : génération IA +
  JSON éditable + aperçu ; `DocumentTemplate` par édition/kind/type, rendu
  `App\Documents\LayoutRenderer` avec variables `{…}`). ⚠️ **Plus aucun repli implicite** :
  tout document vient d'un modèle **enregistré**, créé au besoin par
  **`DocumentTemplate::ensureFor()`** à partir de `LayoutSanitizer::default()`. Les anciennes
  vues de repli (`attestations.document`, `documents.badges`, `pdf.attestation`) ont été
  **supprimées** - ne pas les réintroduire : un document que l'admin ne peut ni voir ni
  modifier dans le back-office est un piège. Supprimer un modèle le **réinitialise**. Placeholders dispo dans `TemplateDesignerController::VARIABLES` - dont
  **`{communication}` / `{communication_ref}` / `{communication_type}`** pour nommer la
  communication (titre, référence, **format** orale/affichée), et
  **`{formation}` / `{formation_theme}` / `{formateurs}`** pour une attestation de
  formation.
  - ⚠️ **Le défaut dépend du TYPE** : `LayoutSanitizer::default('attestation', $type)` a un
    `match` ; les types **`communication`** (`attestationCommunication()`), **`participation`**,
    **`moderation`**, **`rapporteur`**, **`organisation`**, **`comite_organisation`** et
    **`instruction_resumes`** renvoient
    le **modèle officiel UK x Université de Parakou**. Chaque modèle PHP (rendu quand aucun modèle
    n'est enregistré) et son jumeau du **concepteur** (`loadTemplate('communication'|'participation'
    |'moderation'|'rapporteur'|'organisation'|'comite_organisation'|'instruction_resumes')` dans
    `template-designer.js`,
    aussi chargés à l'ouverture d'un type sans `editor_state`) décrivent la **MÊME maquette** :
    toucher à l'un, c'est toucher à l'autre. Gardes : `AttestationCommunicationTemplateTest`,
    `AttestationParticipationTemplateTest`, `AttestationModerationTemplateTest`,
    `AttestationRapporteurTemplateTest`, `AttestationOrganisationTemplateTest`,
    `AttestationComiteOrganisationTemplateTest`, `AttestationInstructionResumesTemplateTest`.
    - **`instruction_resumes` = attestation d'INSTRUCTION DE RÉSUMÉS** (relecteurs) : même
      maquette officielle (via `officialAttestation`), titre « ATTESTATION D'INSTRUCTION DE
      RÉSUMÉS » (Times 28 px ≈ 618 px), corps « pour avoir contribué à l'évaluation, la sélection
      et l'annotation des résumés scientifiques soumis au [edition.name]… ». Bénéficiaires =
      participants ayant **rendu** au moins une relecture (`Review` `completed`),
      dédoublonnés (`DocumentHubController::reviewerTargets()`, une par relecteur).
    - **Participation / modération / rapporteur / organisation = même maquette**, factorisée :
      `officialAttestation($title, $body, $titleSize = 28)` (PHP) et `officialTpl(title, body,
      titleSize)` (JS). Même cadre (image de fond **partagée**), même en-tête (logos, titre Times,
      « Décernée à », destinataire), même typographie (Times titre + Lato corps), même Thème +
      formule, même QR bas-gauche et même bloc signataire. **Seuls le titre et le corps varient**.
      ⚠️ **Le titre long d'`organisation`** (« ATTESTATION DE PARTICIPATION À L'ORGANISATION »)
      déborderait à 28 px : il est ramené à **Times 24 px** (mesuré ≈ 642 px, tient dans la boîte
      de 660 px) via le paramètre `$titleSize`. La **communication** garde sa propre méthode (elle
      ajoute les lignes « Titre / Type de communication »).
  - **Cadre = image de fond ; logos et texte = éléments éditables.** Le cadre décoratif
    (motifs kente, hélice ADN) **seul** est cuit dans
    `public/img/attestations/attestation-communication-bg.png` (`bg_image`), reconstruit depuis
    le PDF officiel (`Modele AT/Communication.pdf`) en **effaçant le texte ET les deux logos**.
    Les **logos sont des éléments image** : l'**UK suit le logo large des Réglages**
    (`Branding::wide()`, chemin relatif via `parse_url(..., PHP_URL_PATH)` → indépendant du
    domaine), **Parakou** est l'asset livré `public/img/attestations/logo-parakou.png`. Le
    texte est en éléments (jetons). Tout est donc éditable, et l'UK se met à jour avec le logo
    de l'établissement. Les URL des logos sont passées au concepteur via `UKC_EDITOR.logos` ;
    dans le panneau **Images**, les deux vignettes « UK - Univ. Kara » / « Univ. Parakou »
    **affichent la vraie image** (`.ed-thumb--logo` : `<img>` + légende, plus de libellé texte
    seul) et, au clic, insèrent ce logo sur la page. ⚠️ En production, `php artisan storage:link`
    pour qu'un logo UK **téléversé** (`/storage/...`) sorte aussi dans le PDF.
  - **Polices du modèle, fidèles à la maquette** : titre en **Times** (clé `times` →
    `'Times New Roman', Times, serif` ; dompdf a Times en natif, l'écran a Times New Roman),
    corps en **Lato** (clé `lato`, **hébergée** dans `public/fonts/lato-*` et déclarée dans
    `DocumentFonts::FACES`/`FAMILIES` - woff2 écran + ttf dompdf, cf. `documents/_fonts`). Le
    concepteur charge Lato depuis Google Fonts (lien dans `template-designer.blade.php`).
    `createEl` applique des **styles en ligne** (`font`/`size`/`weight`/`color`/`italic`/
    `align`/`leading`…), prioritaires sur les classes `.heading`/`.sub`/`.attr` (dont l'allure
    ne convient pas à ce modèle). ⚠️ **La taille du titre se mesure sur la maquette** (ici
    Times 28 px = ~508 px de large, capitales ~19 px), pas au jugé : un titre à 31 px Georgia
    faisait 612 px, trop large.
  - **Jetons = ceux de la palette** (`[espace.cle]`), pas la forme plate : `[recipient.name]`,
    `[recipient.civility]`, `[edition.name]`/`[edition.dates]`/`[edition.location]`/
    `[edition.theme]`, `[communication.title]`/`[communication.type]`, `[signatory.role]`/
    `[signatory.name]`, `[certificate.issued_on]`/`[certificate.uuid]`. ⚠️ **`[recipient.civility]`
    (vraie civilité, lue sur le compte) et `[edition.location]` ont été AJOUTÉS** à la palette
    (`DocumentVars::ALIASES`, `VARIABLES`, `sampleVars`, et les `ed-attr` du blade) : plus de
    « Mme/M. » générique, on écrit `[recipient.civility] [recipient.name]`.
  - **QR de vérification** : élément `qr` (valeur `{verify_url}` → `/attestation/{uuid}`), posé
    en bas à gauche. Le bloc signataire (« Fait à … », rôle, nom) est **aligné à gauche** sur
    une même colonne, comme la maquette.
  - ⚠️ **La date (`[certificate.issued_on]`) est rédigée en toutes lettres** (« 12 octobre
    2026 »), jamais `12/10/2026` : c'est un document officiel. `AttestationVars` formate
    `issued_at->locale('fr')->translatedFormat('j F Y')` (locale forcée au français,
    indépendante de la langue d'interface) ; l'aperçu du concepteur (`sampleVars`) fait de
    même. Gelé par `AttestationCommunicationTemplateTest`.
  - ⚠️ **`[communication.title]` et `[communication.type]` sont rendus TELS QUELS** (droit,
    non italique) : on affiche la valeur enregistrée sans style. Le corps et la formule sont
    en **14 px** (ni 13 ni plus) ; le titre reste en Times 28 px.
  - ⚠️ **`[communication.type]` = le FORMAT** (orale / affichée), **figé à l'émission** dans
    `meta` (`IssueAttestation` : `poster → « Affichée »`, sinon `« Orale »`), comme le titre.
    La référence de la communication reste en `[communication.ref]` / `{communication_ref}`.
  - **Attestation de FORMATION = modèle à part** (`attestationFormation()` / `loadTemplate('formation')`,
    pas la maquette `officialAttestation`) : **cadre différent** - design « moderne » à coins
    colorés sur carte grise (`/img/attestations/attestation-formation-bg.png`, reconstruit du PDF
    officiel en ne gardant que les décorations vives), titre **Times 38**, rédaction à la **première
    personne** (« Je soussigné, [signatory.name], [signatory.role], atteste que »), sujet de la
    formation **`[formation.title]`** + thème du colloque `[edition.theme]` dans le corps. Jetons
    de formation **ajoutés à la palette** (`DocumentVars` : `[formation.title]`→`formation`,
    `[formation.theme]`, `[formation.trainers]`). Gardes : `FormationAttestationTest`.
  - ⚠️ **Deux signatures sur l'attestation de formation** : le **responsable** de l'événement
    (bloc texte à droite : `Le [signatory.role],` + `[signatory.name]`) ET, en plus, **jusqu'à
    DEUX formateurs** via l'élément **`signatures`** (à gauche). Le plafond de 2 est posé à
    l'émission (`IssueAttestation` : `->take(2)` sur `formation_signatures`). Sans signature de
    formateur téléversée, l'élément est vide et seul le responsable signe.
    - Chaque signature de formateur porte **nom PUIS fonction/spécialité** (sous le nom, en plus
      petit) : `formation_signatures[]` = `{name, role, src}` où `role` = `formation_trainers.specialty`,
      rendu par `renderSignatures()`. Corps (« Je soussigné… », « a suivi… », « En foi de quoi… »)
      en **14 px**. ⚠️ **Les deux logos d'en-tête sont à la MÊME hauteur** (comme la maquette
      officielle : la largeur suit le format de chaque logo, pas l'inverse) - UK `204×56`, Parakou
      `228×56` ; une hauteur de boîte différente faisait paraître l'un plus grand que l'autre.
      Le **QR est dans le coin bas-droit** (hors du bloc signataire du milieu), et l'écart
      fonction -> nom du signataire suit la maquette officielle (~80 px).
  - ⚠️ **Le concepteur gère l'élément `signatures`** (ajouté : `createEl` en pose un repère à deux
    emplacements, `serializeLayout` le préserve via `el.dataset.sig`). Sans cela, « Enregistrer »
    dans l'éditeur le reconvertissait en texte et les signatures de formateurs disparaissaient du
    modèle enregistré. **Signatures des formateurs** : élément de mise en page **`signatures`**
  (`LayoutSanitizer::ELEMENT_TYPES`) rendu par `LayoutRenderer::renderSignatures()` à
  partir de `$vars['signatures']` - seule valeur **structurée** des variables (les
  autres sont des chaînes). La signature de chaque formateur se téléverse sur la page
  de la formation (`formation_trainers.signature_path`) et est **figée dans
  `attestations.meta` à l'émission** : l'attestation ne change pas si l'équipe évolue.
  - **Attestation d'ANIMATION DE FORMATION = modèle à part** (`attestationFormationAnimation()`
    / `loadTemplate('formation_animation')`), **délivrée au FORMATEUR** : **même cadre que la
    formation** (coins colorés, carte grise, `attestation-formation-bg.png`, mêmes logos
    204×56/228×56) mais rédaction « Décernée à … » comme la maquette officielle (titre Times
    **30 px** car long, « pour avoir animé une formation en marge des travaux du [edition.name]…
    sur le thème : « [edition.theme] » », ligne **« Thème de la formation : [formation.title] »**,
    « En foi de quoi… », signataire à droite, référence bas-gauche, QR bas-gauche). ⚠️ **Aucune
    signature de formateur** (le formateur EST le bénéficiaire ; seul le responsable signe) -
    donc pas d'élément `signatures`, contrairement à l'attestation de formation. Gardes :
    `FormationAttestationTest`.
  - **Aperçu attestation/badge = le CONCEPTEUR en mode APERÇU VERROUILLÉ** (pour voir
    EXACTEMENT le design du modèle, polices/logos/mise en forme compris) : le bouton « Aperçu »
    ouvre `admin.documents.templates.preview` (`TemplateDesignerController::preview` →
    `renderDesigner($request, true)`), qui sert la **même page d'éditeur** avec
    `UKC_EDITOR.preview = true`. Le JS (`template-designer.js` / `badge-designer.js`) bâtit le
    canevas (depuis `editor_state` ou le template intégré du type) puis **ouvre automatiquement
    l'overlay d'aperçu** (clone exact du canevas, jetons remplis par `fillTokens` avec
    `sampleVars()`), chrome d'édition masqué ; « Fermer » revient aux Documents (`backUrl`).
    ⚠️ On NE reconstruit PAS le rendu côté serveur (`LayoutRenderer`) pour l'aperçu : il serait
    approximatif (logos/typographie). Le **reçu** suit la **même grammaire d'aperçu verrouillé**
    (`admin.payments.receipt.sample`, `ReceiptController::sample`, Payment fabriqué non persisté) :
    le reçu réel (partial unique `receipts/_document.blade.php`, partagé avec la vue imprimable)
    est rendu avec des valeurs d'exemple dans un **cadre sombre** (`#1e293b`), tag « Aperçu du reçu -
    valeurs d'exemple », bouton **« Fermer »** (+ Échap, clic sur le fond) qui ramène aux paiements
    (`backUrl`), actions imprimer/PDF masquées. ⚠️ Pas besoin de concepteur côté reçu : son design
    EST la vue Blade, donc la rendre suffit à montrer le final. Le **livret** ouvre son **PDF en
    inline** (`?format=pdf&inline=1`, `DocumentController` `stream()` vs `download()`). Tests :
    `DocumentPreviewTest`.
- ⚠️ **UNE SEULE règle pour « les communications que l'événement présente »** :
  **`Submission::scopeAcceptedCanonical()`** (canonique + statut `accepted`) et son pendant
  sur un modèle chargé, **`isAcceptedCanonical()`**. Elle vaut pour le **livret** ET pour
  tout **chiffre public** portant sur les communications - elle a déjà divergé trois fois.
  Deux de ces trois endroits étaient **publics** : l'assistante **Gesco** comptait les
  communications d'une séance **sans regarder leur statut** (« 2 communications » pour une
  séance qui n'en tient qu'une), et le compteur des **éditions passées** filtrait le statut
  **mais pas** les resoumissions, qui comptaient double. ⚠️ Dans un `whereHas`, le builder
  est générique et le scope du modèle n'y est **pas** visible : passer par une sous-requête
  (`whereIn('submission_id', Submission::query()->acceptedCanonical()->select('id'))`)
  plutôt que de recopier les conditions.
- ⚠️ **Le livret ne contient QUE des communications acceptées - dans TOUTES ses sections.**
  Les résumés
  filtraient bien ; le **programme**, lui, lisait les créneaux sans rien vérifier - une
  communication planifiée **puis refusée** gardait son créneau, et le livre imprimé
  annonçait son titre et ses auteurs à l'heure dite alors que son résumé n'y figurait pas.
  Une **resoumission** acceptée passait de même, en doublon de sa version canonique.
  ⚠️ **Trois constructions de programme** à tenir d'accord : `LivretDocx::programmeByDay()`
  (style par défaut), `LivretDocx::addProgrammeFss()` (style FSS) et
  `DocumentController::livretViewData()` (PDF + aperçu HTML) - corriger l'une ne dit rien
  des deux autres. ⚠️ Un créneau dont la communication n'est pas au livre est **écarté**,
  pas replié sur l'intitulé de sa séance : la ligne n'existait que pour elle, et les autres
  créneaux de la même séance représentent déjà la séance. Tests : `LivretTest`.
- ⚠️⚠️ **LE LIVRE QU'ON LAISSE AU PARTICIPANT EST LE PDF** (`account.documents.livret`),
  et c'est **le même** que celui du back-office : les données du livre sont
  assemblées une seule fois par **`App\Documents\LivretView::data()`**. Elles
  vivaient dans le contrôleur de l'administration, hors d'atteinte de l'espace
  participant, qui rendait donc une version appauvrie - **sans les comités ni le
  programme** - sans que rien ne le signale. ⚠️ **Et le `.docx` ne descend pas
  jusqu'au participant** : c'est un **gabarit**, fait pour être retouché dans Word
  puis réimporté ; proposé à un lecteur, il donne à croire que le livre se modifie,
  et il s'ouvre différemment d'une machine à l'autre. Un lien ancien portant
  `?format=docx` ne tombe pas en erreur - il rend le PDF.
- **Livre des résumés (livret)** : communications acceptées groupées par axe, trois sorties
  depuis `admin.documents.livret` (`DocumentController::livret`) : **aperçu HTML**,
  **.docx** (`?format=docx`) et **PDF** (`?format=pdf`, dompdf, vue autonome
  `documents/livret-pdf.blade.php`). Le .docx est **`App\Documents\LivretDocx`** (porté de
  uk-colloques `booklet.php`). ⚠️ **Structure par défaut calée sur le livret de référence
  UK/UP** : couverture tricolore → avant-propos (optionnel, IA possible) → **« Différentes
  commissions et programmes du colloque »** (comités **numérotés** dans l'ordre supervision/
  organisation/scientifique, puis **« N. Programme synthétique du colloque »** rendu depuis
  `settings['program_synthesis']`) → **Programme** détaillé (communications/créneaux par jour,
  « Horaire | Communication + auteurs | Salle / Session ») → **Résumés** par axe (réf., titre,
  auteurs + affiliations en exposant, **« Auteur correspondant : … Email : … Tél : … »**,
  « RÉSUMÉ : », mots-clés) → **index des auteurs** (renvoi vers la réf.) → **pied de page
  paginé**. Pas de sommaire TOC Word par défaut (l'ancien `addToc` a été retiré).
  Options `livret.*` dans l'onglet **Apparence du livre** (vue `admin/documents/attestations.blade.php`) :
  polices, tailles, couleurs, alignements, `number_abstracts`, `author_index`, `page_numbers`,
  thème/dates, logo de couverture. ⚠️ **PhpWord n'échappe pas** le texte : `Settings::setOutputEscapingEnabled(true)` est obligatoire (un « & » casse le .docx).
  - **Sections de tête GÉNÉRIQUES, masquées si vides** (onglet **Livre des résumés → Contenu du livre**,
    `saveLivretOptions`) : en plus de l'avant-propos, le livret peut porter un **haut patronage**
    (`livret.patronage`, lignes centrées), des **remerciements** (`livret.acknowledgements`, une ligne
    par destinataire → liste à puces ; titre `livret.ack_title`), un **mot de bienvenue**
    (`livret.welcome_title`/`welcome_text`/`welcome_signature`) et un **mot du président**
    (`livret.president_title`/`president_text`/`president_signature`). ⚠️ **Chaque section ne s'affiche
    que si son champ est rempli** (sinon rien ne s'insère) - c'est générique (tout colloque), pas
    spécifique au modèle FSS qui a inspiré ces champs. Rendu dans **`LivretDocx`** (helper unique
    `addMessage()` pour tous les « mots » signés ; `addPatronage()`/`addAcknowledgements()`) **et** dans
    le **PDF** (`documents/livret-pdf.blade.php`). L'aperçu HTML (`documents/livret.blade.php`) reste
    volontairement minimal (il n'affiche déjà ni comités ni programme) : le .docx et le PDF sont les
    sorties fidèles. Les **modèles .docx personnalisés** ne reçoivent pas ces blocs (l'admin met ce
    contenu directement dans son propre .docx).
  - **Couverture SOBRE** (plus de bandeau tricolore) dans les trois rendus (.docx, PDF, aperçu HTML).
  - **Trois styles intégrés SÉLECTIONNABLES** (réglage `livret.template`, `LivretDocx::STYLES`,
    sélecteur « Modèle actif » de l'onglet **Modèles de document**) : **`default`** = « Modèle par
    défaut » (commissions numérotées « Différentes commissions… », **programme par jour** en tableau) ;
    **`fss`** = « Modèle 2 - Journées scientifiques » (comités en titres simples « Comités »,
    **programme par jour → salle → séance** fidèle au livret FSS 2021 : chaque séance = horaire +
    intitulé + modérateur, puis ses communications « Titre, INTERVENANT » depuis `event_sessions`/
    `slots`) ; **`general`** = « Modèle 3 - Document général du colloque » (voir ci-dessous).
    `LivretDocx` prend un paramètre `$style` ; `DocumentController::livretStyle()` le résout,
    et `selectedLivretTemplate()` traite `default`/`base` + toutes les clés de `STYLES` comme des
    styles intégrés (≠ modèle .docx importé). ⚠️ **Ajouter un style = l'ajouter à `STYLES` ET à la
    liste `livretBuiltins` de `DocumentHubController::livretViewExtras()`** (sinon il est généré mais
    pas proposé dans le sélecteur). ⚠️ **Chaque modèle intégré a son bouton « Télécharger »**
    (`livretBuiltins[].download`), qui rend le **MODÈLE** (gabarit .docx à balises de fusion à
    personnaliser dans Word puis réimporter), **jamais le livre peuplé des communications**.
    Les trois styles (`default`/`fss`/`general`) sont générés par le code (pas de fichier .docx
    propre) : leurs quatre cartes pointent donc toutes sur `admin.documents.livret.base-template`
    (le même gabarit de base pour l'instant - choix produit assumé : « gabarit identique pour les
    trois styles »). ⚠️ **Ne PAS faire pointer ces téléchargements sur `?format=docx`** : ça
    sortirait le livre peuplé, pas le modèle. Un modèle .docx **importé** se télécharge par son
    URL de stockage directe et se **supprime** (bouton corbeille, `livret.template.delete`).
    Gardes : `LivretTest` (un bouton par modèle vers le gabarit de base ; le gabarit est un vrai
    .docx à balises `[[RESUMES]]`/`[[PROGRAMME]]`).
    ⚠️ Les styles `default`/`fss` n'agissent que sur le **.docx** ;
    l'aperçu HTML et le PDF gardent alors la mise en page par défaut. **EXCEPTION : `general` est
    aussi reflété dans l'aperçu PDF** (`DocumentController::livretViewData()` branche sur
    `livretStyle()` : comité d'organisation en tableau, ordre supervision → scientifique →
    organisation, et « Communications en ateliers » au lieu de « Programme ») - `documents/livret-pdf.blade.php`
    rend ces variantes. L'aperçu HTML (`documents/livret.blade.php`) reste minimal (ni comités ni
    programme). Les styles partagent les sections de tête génériques ci-dessus. Les titres portent
    `keepNext` (pas de titre orphelin).
  - ⚠️ **`general` = « Document général du colloque »** (calqué sur `document_general_colloque_005.docx`
    UK/UP) : **A) commissions et programmes** puis **B) résumés**. Comités dans l'ordre supervision →
    scientifique → **organisation** ; le **comité d'organisation est un TABLEAU** (N° / Nom & Prénom /
    Grade / Contacts / Qualité) via `committeeOrganisationTable()`. Puis **programme synthétique**
    (`settings['program_synthesis']`), puis **« Communications en ateliers »** (`addAteliers()`) : un
    groupe **PAR ATELIER** (`Atelier::tracks`, ordre `ateliers.ordre`/`id`), chaque atelier listant
    ses communications orales programmées, regroupées par **SÉANCE** - créneaux acceptés-canoniques
    seulement, même règle que les résumés. ⚠️⚠️ **UN ATELIER REGROUPE PLUSIEURS AXES ; LE LIVRET
    GROUPAIT PAR AXE** (`submission->track`) et sortait donc **un « atelier » par axe** - six pour un
    colloque qui en compte deux (2026-10-29). `ateliersGroups()` rattache chaque communication à SON
    atelier par la **clé normalisée de son axe** (`ReviewerExpertise::axisKey`, la grammaire de tout le
    projet). L'intitulé est « ATELIER n » (rang porté par `number`, pas un compteur local - il ne
    dérive pas si un atelier n'a aucune communication) + « : SOUS-TITRE » seulement si le titre de
    l'atelier dépasse le préfixe « Atelier n ». ⚠️ **Repli si l'événement n'a AUCUN atelier déclaré** :
    un groupe par axe (ancien comportement), d'où le cas `general` historique qui tient toujours.
    ⚠️⚠️ **UN ATELIER SE DÉROULE SÉANCE PAR SÉANCE** (2026-10-29, demande organisateur). Sous chaque
    atelier, `LivretDocx::atelierSessions()` découpe ses créneaux par `EventSession` (triées par
    horaire ; les créneaux sans séance sont regroupés par jour). Chaque séance porte un **bandeau de
    séance** (« Session : HHhMM - HHhMM - Salle »), une ligne **Modérateur : … Rapporteur : …**
    (toujours affichée, « - » à défaut, lues sur `event_sessions.moderator_name`/`rapporteur_name`)
    puis un tableau **`Horaire | Code | Auteur(s) | Titre de la communication`** - la colonne
    **Auteur(s) = l'auteur correspondant** (`slotPresenter`, casse du style). Le bandeau de jour (bleu
    `95B3D7`) n'apparaît qu'au CHANGEMENT de jour ; le bandeau de séance en bleu clair
    (`REF_SESSION_FILL` `DCE6F1`, dans `generalCssPalette()` sous `session_fill`). ⚠️ Ancien tableau
    `Code | Horaire | Communication | Salle` (sans auteurs ni séances) **retiré** ; `programmeDayTable`
    ne sert plus QU'au Programme par jour des autres styles. Les autres styles gardent
    `Horaire | Communication (titre + auteurs) | Salle`.
    ⚠️ **Les titres passent par `LivretDocx::bindPunctuation()`** (espace insécable U+00A0 avant
    `? ! : ; »`) : dans une colonne étroite, un « ? » (ou « : ») tombait seul sur la dernière ligne.
    C'est aussi la règle typographique française. Appliqué au titre de la communication (programme
    général) et au titre du résumé (style général), en .docx ET en PDF. ⚠️ U+00A0 n'est pas proscrit
    (seuls U+00B7/U+2013/U+2014 le sont) ; le code porte l'échappement `\u{00A0}`, pas le caractère.
    ⚠️ **Noms d'auteurs affichés au format « NOM Prénom »** (nom de famille en capitales,
    prénom en casse normale, comme le livre de référence) - ⚠️ **uniquement pour le style
    `general`** : `LivretDocx::displayName()` est **dépendant du style** (il rend le nom TEL QUEL
    pour `general`, mais le met **ENTIÈREMENT en majuscules** pour `default`/`fss`, convention
    historique du livret - cf. `DocumentsTest` « JEAN DUPONT » et l'index `AWA KODJO`). Le seeder
    écrit l'auteur en `authorName($first, $last)` = `strtoupper($last).' '.$first` (le nom du COMPTE
    reste « Prénom Nom »). ⚠️ Chaque auteur de la
    ligne du PDF est enveloppé dans un `<span white-space:nowrap>` : sans cela l'exposant d'affiliation
    (« 4 ») se détachait du nom et retombait seul à la ligne ; la coupure ne se fait qu'aux virgules.
  - ⚠️ **Word et PDF = DEUX moteurs (PhpWord / dompdf), donc DEUX rendus à ne pas laisser
    diverger.** Le PDF n'est PAS produit à partir du .docx (pas de LibreOffice sur le mutualisé
    o2switch) : `DocumentController::livretViewData()` alimente la vue dompdf `documents/livret-pdf`
    en parallèle du générateur `LivretDocx`. Pour limiter la dérive, deux **sources uniques
    partagées** par les deux rendus : **`LivretDocx::authorCase($nom, $general)`** (la casse des
    auteurs - corps, programme, correspondant, index - MAJUSCULES pour `default`/`fss`, tel quel pour
    `general`) et **`LivretDocx::generalCssPalette()`** (les couleurs du style `general` : `black`,
    `terracotta`, `day_fill`, `session_fill`, `head_fill`, `border` - la vue lit ces valeurs, pas des
    hexadécimaux en dur). ⚠️ Ne pas réintroduire dans la vue un `mb_strtoupper`/`trim` isolé ni une couleur en dur :
    c'est ainsi que la casse des auteurs du PDF (affichée TELLE QUELLE même en `default`) divergeait du
    .docx. Garde : `LivretTest` « casse des auteurs : le .docx et le PDF concordent selon le style »
    (rend les DEUX et compare).
    ⚠️ **La STRUCTURE du style `general` est elle aussi factorisée** (elle vivait en double dans
    `LivretDocx` et `livretViewData`) : **`LivretDocx::committeeOrder($general)`** (ordre + libellés
    des comités), **`LivretDocx::ateliersGroups($event)`** (communications par ATELIER puis par SÉANCE -
    `Atelier::tracks`, repli par axe sans atelier ; `->toBase()` pour rendre des collections Support
    homogènes ; chaque groupe porte `number` et une collection `sessions` composée par
    `atelierSessions()` : `day`/`time`/`room`/`moderator`/`rapporteur`/`label`/`slots`)
    et **`LivretDocx::ateliersAxisLabel($track)`**
    (dé-préfixage « Atelier n - » des AXES, côté résumés). Le .docx (`addAteliers`, `addBody`),
    `livretViewData` et la vue PDF appellent ces trois sources ; chaque appelant ne compose QUE
    l'intitulé « ATELIER n[ : … ] »/« n- … » depuis `number`/`axis`. Ne pas recopier la requête de
    créneaux, la regex de dé-préfixage ni l'ordre des comités ailleurs. Gardes : `LivretTest`
    « regroupe les communications par ATELIER, pas par axe » et « déroule un atelier SÉANCE PAR
    SÉANCE avec modérateur, rapporteur et colonne auteurs » (vérifie .docx ET PDF).
    ⚠️ **Coupures de page du programme général (dompdf)** : le bandeau de jour + l'en-tête de colonnes
    + la **1re ligne de données** vivent dans un même `<tbody class="prog-start">` **insécable**
    (`page-break-inside: avoid`), les lignes suivantes dans un second `<tbody>`. L'en-tête ne reste
    donc JAMAIS seul en bas de page (il descend avec sa 1re ligne) et le tableau remplit la page après
    le titre (pas de trou). ⚠️ **On n'utilise PAS `<thead>`** : dompdf le place volontiers SEUL en bas
    de page (en-tête sans aucune ligne) - c'était le défaut constaté. ⚠️ **On n'applique PAS
    `page-break-inside: avoid` au `<table>`** : dompdf déplace alors le tableau ENTIER à la page
    suivante → trou avant lui. Revers assumé : un tableau de jour qui déborde sur 2 pages n'a pas
    d'en-tête répété sur la 2e (rare : la plupart des tableaux de (atelier, jour) tiennent sur une
    page). Le titre d'atelier porte `page-break-after: avoid`. L'en-tête du résumé (réf.→correspondant)
    et le bloc « Résumé » + contenu portent `page-break-inside: avoid`.
    Le préfixe « ATELIER n : » est
    posé par le livret ; un axe déjà nommé « Atelier 2 - … » est dé-préfixé pour ne pas le doubler.
    Les **résumés par axe** et l'**index des auteurs** sont partagés avec les autres styles (`addBody`).
  - ⚠️ **Le .docx `general` est NAVIGABLE** (volet de navigation Word + sommaire) : les **axes
    « n- … »** et **ateliers « ATELIER n … »** sont des **Heading1**, les **codes de communication
    « AVD-xxx »** des **Heading2** - vrais styles de titre Word (`addTitleStyle(1|2)` enregistrés
    par `registerGeneralHeadingStyles()`, posés par `addTitle()`, un signet par titre). ⚠️ **Seul
    le style `general`** les enregistre (`isGeneral()` dans `build()`) : le reste du projet garde la
    règle « titres = addText, pas addTitle » (Word numérotait sinon les titres et exigeait un TOC).
    ⚠️ **Seulement ces trois là, PAS les sections A)/B)/comités** : un style de titre est lié à une
    profondeur, donc à UN alignement, or A) est à gauche et B) centré. On ne retient donc que les
    items déjà de même alignement (axes/ateliers **centrés**, codes **à droite**) → l'allure reste
    rigoureusement identique à l'ancien addText (Heading1 = Jost 14 pt noir centré ; Heading2 = 11 pt
    noir à droite ; la couleur **terracotta** d'un atelier reste portée par son `TextRun`, le style
    étant noir). ⚠️ **« Email »/« Résumé » NE sont PAS des titres** (le doc de référence les y mettait,
    ce qui encombrait le volet) : navigation par code et par axe/atelier seulement. Pas de navigabilité
    côté **PDF** (dompdf rend le même visuel, sans volet). Garde `LivretTest` : le .docx contient
    `w:val="Heading1"`, `w:val="Heading2"`, `<w:bookmarkStart`.
  - ⚠️ **PALETTE EXACTE du document de référence** (fidélité « couleurs + style de titre » demandée,
    mesurée dans le .docx fourni, appliquée au **.docx ET à l'aperçu PDF**) : sections lettrées
    **« A) … »** gras **14 pt NOIR** à gauche / **« B) Résumés… »** gras **16 pt NOIR centré** ; comités
    **« 1. COMITÉ … »** gras 14 pt noir, en CAPITALES ; **titres d'atelier « ATELIER n : … » en
    TERRACOTTA `#C0504D`**, centrés, gras 14 pt (couleur signature) ; **« n. COMMUNICATIONS EN
    ATELIERS »** gras 14 pt noir centré (item numéroté de la section A - `addCommissionsGeneral()`
    renvoie le dernier n, passé à `addAteliers($num+1)`) ; **axes des résumés « n- AXE »** gras 14 pt
    noir centré ; **tableau du comité d'organisation** en-tête gris **`#F2F2F2`** (texte noir), scindé
    **COORDINATION** (président/vice/rapporteur/…) **/ MEMBRES** en lignes-groupes fusionnées ;
    **bandeau de jour des ateliers en bleu `#95B3D7`** pleine largeur ; auteurs des résumés en **gras
    italique 12 pt noir**, libellé **« Résumé »** (pas « RÉSUMÉ : » bleu), mots-clés noirs. Toutes ces
    règles ne valent que pour `general` (constantes `BLACK`/`TERRACOTTA`/`REF_DAY_FILL`/`REF_HEAD_FILL`
    dans `LivretDocx`, bloc `@if ($isGen)` de styles dans `livret-pdf.blade.php`) - `default`/`fss`
    gardent le bleu de la charte. ⚠️ Garde `LivretTest` : le .docx doit contenir `C0504D`, `95B3D7`,
    `F2F2F2`, `A) …`, `B) …`, `COMITÉ D'ORGANISATION` (capitales), `1- …`.
  - ⚠️ **Le tableau du comité d'organisation a exigé deux colonnes** sur `committee_members` :
    **`phone`** et **`qualite`** (fonction : Président, Rapporteur…), migration
    `2026_10_16_000100_add_contact_qualite_to_committee_members` ; l'e-mail existait déjà, le grade est
    `person_title`. Saisies dans le répéteur « Comité d'organisation » de la fiche d'événement
    (`admin/evenements/form.blade.php`, seulement pour `comOrg`), persistées par `SaveEvent` et
    rechargées par `EventFormController`. Gardes : `LivretTest`.
  - **Gabarits .docx personnalisés** (porté de `booklet-templates.php` `ukc_bt_render`) :
    l'admin télécharge le **modèle de base** à balises (`admin.documents.livret.base-template`
    → `LivretTemplate::downloadBase`, vrai .docx), le personnalise dans Word en gardant les
    balises, le re-téléverse (`saveLivretTemplate` → `edition.settings['livret.templates']`),
    puis le sélectionne (`livret.template`). À la génération .docx, **`App\Documents\LivretTemplate`**
    ouvre le modèle (ZipArchive + DOM), remplace les balises **bloc** seules sur leur ligne
    (`[[AVANT_PROPOS]]`, `[[COMITES]]`, `[[SOMMAIRE]]`, `[[PROGRAMME]]`, `[[RESUMES]]`) par les
    **fragments OOXML** de `LivretDocx::blockXml()` (formatage DIRECT, indépendant des styles
    du modèle), et les balises **en ligne** (`[[TITRE]]`, `[[EDITION]]`, `[[THEME]]`, `[[DATES]]`,
    `[[LIEU]]`, `[[ANNEE]]`, `[[NB_RESUMES]]`, via `LivretDocx::inlineValues()`). Repli sur
    `LivretDocx` quand aucun modèle n'est sélectionné/présent (`selectedLivretTemplate()`).
    Un modèle importé se **supprime** (`deleteLivretTemplate`, route `admin.documents.livret.template.delete` ;
    fichier effacé + repli sur `default` s'il était actif).
    Tests : `tests/Feature/LivretTest.php` (contenu réel du .docx/PDF, fusion des balises).
  - **UI du volet (onglet Documents → Livre des résumés, 3 sous-onglets)** : *Livre des résumés*
    (panneau de génération Word/PDF/aperçu/harmonisation + modèle actif en pastille, bandeau KPI,
    « Contenu du livre » en cartes par section avec badge Affiché/Masqué), *Modèles de document*
    (modèles intégrés vs personnalisés, actif mis en évidence, import/suppression), *Apparence du livre*.
    ⚠️ La vue `admin/documents/attestations.blade.php` utilise des **`@php(...)` en ligne** : n'y
    ajoutez **pas** de bloc `@php … @endphp` (mélange = compilation Blade cassée, cf. Pièges) - préparez
    la donnée dans le contrôleur (`DocumentHubController::livretViewExtras()`).
  - **Apparence par modèle actif** : `App\Documents\LivretAppearance` conserve les options dans
    `edition.settings['doc']['livret.appearances'][nom_du_modèle]`. Le premier changement de modèle
    capture les anciennes options `livret.*` sur le modèle alors actif ; les autres modèles
    commencent avec leurs valeurs par défaut. Le formulaire affiche et enregistre uniquement
    celles du modèle sélectionné. `LivretDocx` et l'aperçu PDF relisent cette même portée.
  - **Aperçu PDF avec identité GEVES** : `format=pdf&inline=1` ouvre la page
    `documents/pdf-preview.blade.php` (favicon, titre, monogramme, téléchargement),
    qui intègre le PDF brut via `raw=1`. Le téléchargement `format=pdf` reste direct.
    `public/favicon.ico` contient les mêmes icônes UK que les favicons PNG pour
    les navigations sans page HTML ; `BrandedPdf` renseigne les métadonnées des
    PDF serveur (livre, attestations, reçus).
- **Remboursement → révocation des attestations** : une attestation atteste d'un droit
  acquis par un paiement. `App\Actions\RevokeAttestationsOnRefund` (appelée par
  `admin.payments.refund`) **révoque** (jamais ne supprime - l'attestation reste
  vérifiable publiquement, signalée révoquée) : *participation* + *communication* si
  le remboursement porte sur les frais de participation, *formation* si c'est une
  formation. ⚠️ **Seulement s'il ne reste aucun paiement réglé couvrant le même
  droit** (double règlement, ou autre formation encore payée : la vérification est
  faite `formation_id` par `formation_id`). L'attestation *formation_animation* n'est
  jamais concernée : un formateur ne paie pas. Le remboursement remet aussi
  l'inscription à la formation en « registered ».
- ⚠️⚠️ **REMBOURSER DANS GEVES NE RENVOIE PAS L'ARGENT, ET NE LE PEUT PAS.**
  Le prestataire **ne rembourse que depuis son tableau de bord**, et seulement
  **pour un opérateur (MTN)** - c'est écrit dans son assistance, et son API le
  confirme : aucune adresse de remboursement (`/refunds` répond 401 à nos clés,
  `/transactions/{id}/refunds` n'existe pas, `/payouts` répond 403). Le geste est
  donc **comptable** : il reprend les droits ouverts (attestations, inscription
  formation) et marque la ligne, pendant qu'un humain fait le virement ailleurs.
  ⚠️ **L'écran le dit deux fois** - dans la confirmation avant le clic, et dans le
  message après - parce qu'un gestionnaire qui croit l'argent reparti laisse un
  participant attendre. ⚠️ Et il en reste une **trace** (`meta.refunded_at`,
  `meta.refunded_by_name`, journal `payments`) : « remboursé » chez nous contre
  « approuvé » chez le prestataire, sans savoir qui demander, c'est une enquête
  pour rien.
- ⚠️⚠️ **CE QUE LE PRESTATAIRE A ENREGISTRÉ EST RELEVÉ ET GARDÉ**
  (`OnlineGateway::details()` → `meta.gateway_detail`, posé par
  `ReconcilePayments::settle()` au passage à « payé », donc par les trois chemins
  à la fois - retour navigateur, webhook, réconciliation). On y garde l'opérateur
  **employé**, son libellé, la **référence opérateur**, le **numéro qui a payé**,
  la somme **réellement débitée**, les frais, l'heure du paiement et la date de
  transfert prévue. **Pourquoi** : mesuré en production le 18/10/2026, le payeur
  n'est pas toujours le participant - inscription au nom d'un numéro togolais,
  règlement depuis un AUTRE numéro, chez un AUTRE opérateur que celui déduit. Sans
  ces lignes, un paiement contesté ne se raccroche à rien. Le back-office les
  affiche sous le moyen et sous le montant (⚠️ le payeur a vu passer une autre
  somme : le prestataire ajoute ses frais, et c'est ce chiffre-là qu'il cite).
  ⚠️ **`include=payment_method` n'est pas facultatif** : sans lui le numéro qui a
  payé n'est pas dans la réponse, et `/payment_methods/{id}` répond 404.
  ⚠️ **Ni le jeton ni l'URL du reçu n'entrent en base** (la seconde porte un jeton
  dans son adresse), et ⚠️ **un relevé illisible ne bloque JAMAIS la
  confirmation** : le participant a payé, il ne perdra pas sa place pour une ligne
  de confort.
- ⚠️⚠️ **LE CODE ISO ET LE MOT NE SONT PAS LE MÊME OBJET** (`App\Support\Money`).
  La devise est **`XOF`** : c'est ce que la norme désigne, ce que la base stocke et
  ce que le prestataire attend (`currency: {iso: XOF}`). Mais personne n'écrit
  « 100 XOF » - on écrit **« 100 FCFA »**. `Money::label()` fait la traduction,
  `Money::format()` le montant entier, `Money::noun()` le nom accordé pour la
  somme en lettres. ⚠️ **C'est une correspondance, pas un réglage** : deux zones
  franc coexistent (UEMOA `XOF`, CEMAC `XAF`), elles s'écrivent du même mot et ne
  portent pas le même code - un libellé à tenir à la main dériverait du premier
  jour où quelqu'un changerait la devise. ⚠️ Un code **inconnu** se rend tel quel
  (« EUR ») : mieux vaut le code que rien du tout sur un montant. ⚠️ Le champ
  « Devise » des Réglages reste le **code ISO** - un cas de `PaymentGatewayTest`
  vérifie que c'est bien lui, et jamais le mot, qui part au prestataire.
- ⚠️⚠️ **LE REÇU EST UN SEUL DOCUMENT, POUR DEUX SORTIES**
  (`receipts/_document.blade.php`, inclus par la page imprimable, l'aperçu
  verrouillé **et** `pdf/receipt.blade.php`). Ils ont vécu en deux fichiers
  jumeaux et ils ont divergé. ⚠️ Donc tout y est écrit en **tableaux, styles en
  ligne et couleurs littérales** : dompdf ne connaît ni flexbox, ni grille, ni
  variable CSS - ce qui s'y rend se rend partout, l'inverse n'est pas vrai. Le
  drapeau `pdf` ne change **que** l'embarquement du logo (dompdf ne va chercher
  aucune image distante : il lui faut un chemin de fichier, d'où le
  `storage:link` en production).
  ⚠️ **La somme y est écrite DEUX FOIS, dont une en toutes lettres**
  (`App\Support\MontantEnLettres`) : un chiffre se retouche d'un trait de stylo,
  pas une ligne de mots - c'est pour cela que tout reçu officiel francophone la
  porte, et c'est elle qui fait foi en cas de désaccord. Écrite à la main plutôt
  que confiée à `NumberFormatter` (intl) : l'extension peut manquer sur
  l'hébergement, et un reçu qui tombe en erreur est un reçu qu'on ne peut plus
  délivrer. Les accords sont tenus par un jeu de cas (« quatre-vingts » seul mais
  « quatre-vingt mille », « deux cents » mais « deux cent mille », « deux cents
  millions »).
  ⚠️ **Une cellule de tableau fait la hauteur de SA LIGNE DE TEXTE**, pas celle
  qu'on lui donne : le bandeau tricolore de 4px en rendait dix. `font-size:0;
  line-height:0` sur la cellule, et les séparateurs sont des `border-top`, pas des
  bandes de couleur.
  ⚠️ **L'aperçu stockait `currency => 'FCFA'`** - le mot d'affichage écrit dans la
  donnée : il montrait une devise que la base ne contient jamais, et la somme en
  lettres s'achevait sur « FCFA » au lieu de « francs CFA ».
- **Certification cryptographique des attestations** (identité vérifiable) : chaque
  attestation est scellée à l'émission par une **signature EdDSA (Ed25519)** de
  l'émetteur, sous forme de **Verifiable Credential W3C sécurisé en JWS compact**
  (« VC-JWT », via `firebase/php-jwt` - pas de crypto maison ; **pas** de JSON-LD
  `Ed25519Signature2020` faute de canonicalisation RDF fiable en PHP). Cœur :
  `App\Documents\AttestationCertifier` (sign/verify/didDocument/jwks). Clé privée =
  **graine hors dépôt** (`config/attestations.php` ← `ATTESTATION_SIGNING_KEY`, générée par
  `php artisan attestations:keygen`, **jamais** en base) ; clé publique publiée via
  `did:web` (`/.well-known/did.json`, `/colloques/did.json`) + `/.well-known/jwks.json`.
  Preuve stockée en colonnes dédiées (`attestations.credential`/`key_id`/`signed_at`).
  **Fail-open à l'émission** (sans clé → attestation émise non signée) ; **fail-closed
  sur la preuve** (« signature vérifiée » seulement si elle vérifie vraiment). La
  révocation reste un état DB (une signature ne se « défait » pas). Page publique
  `/attestation/{uuid}` : vérifie la signature + existence + non-révocation, et propose le
  **VC JSON téléchargeable** (`/attestation/{uuid}/credential.json`). Rattrapage du stock :
  `php artisan attestations:sign`. Réglages `attestations.did_*` / `crypto_suite`
  (EditionSettings) désormais **effectifs**.
- **Témoignages** (page d'accueil) : table `testimonials` (modèle `App\Models\Testimonial`,
  scope `forHome()`). **Dépôt par un participant connecté** (`/mes-temoignages`,
  `Account\TestimonialController`) → statut `pending` + consentement obligatoire ; l'admin
  peut aussi saisir directement. **Modération plateforme** (`Admin\TestimonialController`,
  capacité globale **`testimonials`** dans `StaffRoles`, route `/admin/temoignages`) :
  approuver/refuser, **mettre à la une** + **réordonner**. Bouton **« Proposer la sélection
  (IA) »** = `App\AI\ScoreTestimonials` (à la demande, `AiManager::extract`) note l'impact et
  **pré-remplit** `featured`/`position` ; **l'admin garde le dernier mot**. L'accueil
  ([home.blade.php](resources/views/home.blade.php), `HomeController`) n'affiche que les
  `approved` + `featured` (triés `position`, `impact_score`) ; section masquée si vide.

### Badge par défaut = badge de PARTICIPATION (calqué sur le comité d'organisation, UK x Parakou)

**`LayoutSanitizer::defaultBadge()`** rend un badge **de participation**, calqué sur la
maquette du badge du comité d'organisation (docx d'origine
`Badges/CO/conception_initiale.docx`), adapté en badge **par personne**.

⚠️⚠️ **Il est dessiné au format MODÈLE 303 : 333 × 472 px, soit 88 × 125 mm à 96 dpi** -
la taille d'un porte-badge du commerce, donc une **dimension de SORTIE** : le badge
s'imprime à cette taille pour entrer dans sa pochette, et l'A4 en reçoit **4**.
⚠️ **L'A6 papier (397 × 560) a été abandonné comme défaut** : 2 × 105 mm font la largeur
entière d'une A4, il ne restait rien pour les marges d'imprimante - on n'en imprimait
donc qu'**UN par page**. ⚠️ **Un modèle DÉJÀ ENREGISTRÉ garde sa taille** (`ensureFor()`
ne recrée que si `layout` est vide) : pour qu'un événement existant passe au 303, il faut
**supprimer son modèle** dans Documents, qui se recrée depuis le nouveau défaut.

⚠️⚠️ **LA MAQUETTE EST COMPOSÉE PAR FORMAT, JAMAIS ÉTIRÉE.** Les trois porte-badges n'ont
pas le même rapport - **303** 88 × 125 (0,704), **304** 100 × 135 (0,741), **106**
65 × 95 (0,684) - donc agrandir l'un pour obtenir l'autre **ovaliserait le QR** et
déformerait les boîtes. `LayoutSanitizer::defaultBadge($withPhoto, $format)` décrit la
maquette **une seule fois en proportions** (`LayoutSanitizer::BADGE_FORMATS` donne les
tailles) : positions et boîtes suivent **chacune leur axe**, tandis que les **carrés (QR,
photo, pictogrammes) et les corps de texte suivent le PLUS PETIT rapport** - sinon le QR
cesse d'être carré et le texte déborde. Par feuille A4 : **303 → 4**, **304 → 4**,
**106 → 9**, **A6 porte-badge (108 × 155) → 1**, **CR80 → 10**.

⚠️⚠️ **UN PIXEL TRANSPARENT PORTE QUAND MÊME UNE COULEUR, ET dompdf LA MÉLANGE.**
À l'impression, deux **traits gris barraient le badge** en haut et en bas des logos -
absents du concepteur, donc introuvables en relisant le code. Ils ne sont dans
`badge-default-bg.png` **ni comme trait ni comme couleur** : le cadre porte, derrière
les logos, un **rectangle de blanc opaque** entouré de transparence, et ses pixels
transparents portaient du **noir** sous un alpha nul. Réduit de 794 px à 333, le
filtre de dompdf moyenne alors ce blanc avec ce noir invisible et laisse un liseré
gris d'**un tiers de pixel** - invisible à 600 dpi, bien visible à l'impression. Le
navigateur, lui, compose autrement : d'où « ce n'est pas là au moment de la
conception ».

- ⚠️ **Le remède ne RETIRE rien** : les pixels transparents portent désormais du
  **blanc**. Mesuré, **aucun alpha et aucun pixel visible n'a changé** - un alpha nul
  ne se peint pas - et le mélange se fait blanc sur blanc. Effacer le rectangle blanc
  aurait marché aussi, mais c'est retirer de la matière pour soigner un symptôme.
- ⚠️ **Le PNG a été réécrit en RGBA sans perte**, par décodage/réencodage explicite :
  passer par une bibliothèque qui **prémultiplie** l'alpha abîmerait les 932 pixels
  **semi-transparents** des coins arrondis (constaté : 559 pixels visibles modifiés
  au premier essai).
- ⚠️ **La règle vaut pour toute image que dompdf réduit** : `logo-parakou.png` et les
  deux pictogrammes portent encore du noir sous leur transparence. Ils ne donnent pas
  de trait (pas de grande surface blanche opaque à côté), seulement un liseré possible
  sur leurs propres contours - à mesurer avant d'y toucher.
- Garde : `BadgeDefaultTemplateTest` (« ne laisse aucun noir sous les pixels
  transparents du cadre », lue en GD, sabotage vérifié avec l'ancien fichier).

⚠️⚠️ **LE FILET VERT DE DROITE DE LA CARTE EST UN ÉLÉMENT, PAS LE CADRE.** Sur le
portrait, les deux filets verts sont **cuits dans `badge-default-bg.png`** et il ne
faut pas les repasser en `rect` (ils laisseraient un jour à gauche). Mais le cadre
s'étire en `100% 100%` : sur un format **PAYSAGE**, mesuré, celui de droite se réduit
à un **moignon de 13 px collé au bord**, à cent vingt pixels du logo de Parakou - il
ne relie plus rien. `badgeCarte()` le **prolonge** donc par un `rect` `#00be62` qui
part après le logo et **va jusqu'au bord** (`$w`) : il mord sur le cadre, s'y soude,
et **recouvre le moignon** - sinon on lirait deux traits parallèles. ⚠️ Même vert
`#00be62` que le contour, **jamais** le `#009933` de la charte. Les **logos de la
carte sont plus grands** que ceux du portrait rapportés à sa largeur, et **à la même
hauteur** l'un que l'autre, comme sur l'attestation. Garde :
`BadgeDefaultTemplateTest` (« soude le filet vert… », 3 sabotages vérifiés).

⚠️⚠️ **LE CR80 EST EN PAYSAGE (85,6 × 54 mm) ET A SA PROPRE MAQUETTE** :
`badgeCarte()`, pas `badgePortrait()`. La pile verticale - logos, événement, nom,
rôle, thème, pied - n'y tient pas ; la carte met les logos en tête, le texte dans la
colonne de gauche et le QR à droite. ⚠️ **Le thème du colloque n'y figure PAS, et
c'est voulu** : cent caractères descendraient sous 2 mm de haut sur une carte de
crédit - on afficherait de l'illisible pour faire nombre. Ne pas l'y remettre. Le
choix se fait sur `$w > $h`, donc tout futur format paysage suit la carte.
⚠️ **L'A6 porte-badge n'entre qu'UNE fois par A4 en portrait** (2 × 108 = 216 mm,
plus large que la feuille) : la planche **tourne alors la feuille** et en pose deux
en paysage (cf. « LA FEUILLE SE TOURNE… »). ⚠️ Le **303 sort exactement** comme la version écrite à la main avant cette
composition : c'est la référence, et `BadgeDefaultTemplateTest` la gèle.
⚠️ **Le jumeau du concepteur compose à l'identique** (`T.officiel(format, withPhoto)` dans
`badge-designer.js`, appelé par `co` / `co-photo` avec le format cible) : choisir un
format puis réappliquer le design **recompose** au lieu d'étirer. Un cas compare les
**proportions des deux fichiers** et échoue si elles divergent (sabotage vérifié) - c'est
la garde qui rend tenable la règle « toucher à l'un, c'est toucher à l'autre ».

Le badge porte un cadre décoratif **vert/bleu** (coins diagonaux, filet d'en-tête, séparateur,
pictogrammes **lieu** + **dates**) **cuit dans l'image de fond**
`public/img/badges/badge-default-bg.png` ; puis **nom** (grand, bleu `#253c98`), **rôle**,
**thème** (capitales), **lieu**, **dates**, et un **QR** (`{ref}`).

- ⚠️ **Un communicant est d'abord un PARTICIPANT** : le badge de type `default`
  revient à tout inscrit ayant réglé sa participation, communicant ou non, avec
  le rôle imprimé « Participant ». L'espace participant montre son aperçu
  verrouillé avant paiement et le débloque après règlement ; aucun badge
  communicant distinct n'existe.
- ⚠️ **Un seul modèle de badge participant** (`default`) : avec ou sans photo
  se choisit dans le designer, parmi les variantes `co` et `co-photo`. Le modèle
  enregistré pour `default` sert à tous les participants de l'événement. Au
  rendu, `BadgeLayout::forRecipient()` choisit la variante officielle sans
  photo quand le modèle contient un calque photo mais que le compte n'a pas
  renseigné de photo ou que ce participant l'a refusée pour ce type de badge.
  Les autres bénéficiaires gardent le modèle enregistré.
  Le type historique `participant_photo` peut rester en base pour conserver
  d'anciennes mises en page, mais il n'est plus listé ni utilisé pour les badges
  délivrés. Le choix individuel, par type de badge et par événement, est dans
  « Mes documents » seulement si le modèle contient une photo et que le compte
  en a renseigné une ; il est stocké dans `participants.meta.badge_photo_preferences`.
  L'option générale « Photo obligatoire » reste retirée. Les anciens liens du
  designer ouvrent `default`. La migration
  `2026_10_15_000400` reprend la maquette à photo personnalisée si le modèle
  `default` n'a jamais été personnalisé.
  - ⚠️ **La maquette à photo est AUSSI une variante de design du concepteur**
    (`badge-designer.blade.php`, carte `data-tpl="co-photo"` « Cadre vert/bleu avec
    photo ») : un gestionnaire peut l'appliquer à **n'importe quel type** de badge
    (modérateur, comités…), pas seulement au participant. Le modèle JS `co-photo`
    décrit la MÊME maquette que `LayoutSanitizer::defaultBadge(true)`. Garde :
    `DocumentDesignerTest` (« propose la variante de design avec photo »).
  - ⚠️ **Les cartes « Modèles de design » montrent l'APERÇU RÉEL du modèle**
    (`badge-designer.js`, `buildVariantThumbs`) : chaque `.mini` est un **clone de la
    page** construit depuis `T[tpl]` et rempli des valeurs d'exemple (`fillBadgeTokens`),
    mis à l'échelle - même rendu que « Mes badges », une seule source de vérité (pas de
    vignette dessinée à la main dans le blade). ⚠️ Construit **une seule fois à l'init**
    (page vierge) : `loadTemplate` mute la page, le refaire après écraserait le travail en
    cours. Chaque aperçu est rendu au **format natif** du modèle (`thumbNativeFormat`) pour
    ne pas le déformer. `fitThumb`/`fitThumbs` (re)calcule l'échelle quand le panneau
    « Modèles » s'ouvre (la largeur d'un `.mini` masqué vaut 0). La carte active est
    **remise en évidence après l'init** (le filtrage par format pendant la construction la
    retirait).
  - ⚠️ **Repère de l'élément photo dans le concepteur = une ICÔNE d'avatar**
    (silhouette SVG dans `.photo-inner`), pas le mot « Photo ». À l'**aperçu**, la
    photo d'exemple est un avatar de la galerie des assistantes
    (`sampleVars` → `recipient.photo` = `img/agents/awa-3.webp`, seulement pour un
    badge). Le badge délivré prend la vraie photo de profil (`recipient.photo`).
    Sans photo, `BadgeLayout` utilise la maquette sans image dans l'espace
    participant comme sur la planche administrative.
- ⚠️ **Un destinataire de badge porte TOUJOURS `photo` ET `photo_mode`**
  (`BadgeCatalog::recipients()`), chaîne vide et « with » à défaut. La forme
  annoncée les taisait (`photo?`) alors que la planche les lit : l'analyse en
  déduisait que `photo_mode` n'existe pas, donc que le comparer à « without »
  est **toujours vrai** - soit, sur le papier, un choix « badge sans ma photo »
  jamais respecté. Les poser partout vaut mieux que les tester à chaque lecture.
  - ⚠️ **Et les deux aides (`withCurrentAccountNames`, `unique`) travaillent sur
    des TABLEAUX, pas des collections** : `Collection` n'est pas covariant pour
    l'analyse statique, donc une liste dont l'institution est la chaîne vide ne
    « vaut » pas une liste dont l'institution est une chaîne. Les tableaux, eux,
    le sont. C'est le même remède que pour `UserController::roleUsageLines` -
    types array dans l'aide, `->all()` au point d'appel, `collect()` à la fin.
- ⚠️ **Le SÉPARATEUR du badge est un élément `rect`, les filets verts NON.** Les
  deux filets d'en-tête sont cuits dans l'image de fond, soudés au contour (posés
  en élément, ils laissaient un jour à gauche dans le concepteur) ; le séparateur,
  lui, est redevenu un élément pour que son **épaisseur soit réglable**. Un cas qui
  exigeait « aucun `rect` » tombait donc sur lui : la garde distingue désormais les
  deux (aucun `rect` vert, aucun dans la bande d'en-tête, et exactement un
  séparateur `line-h`).
  - ⚠️ **Assistant IA du concepteur = `GenerateDocumentLayout::instruction()`**, la
    consigne envoyée au modèle. Elle décrit TOUT ce que le concepteur sait rendre
    (types `text`/`image`/`photo`/`qr`/`rect`/`signatures`, mise en forme du texte,
    filets/coins, `bg_image`), liste les **clés de polices** (`LayoutSanitizer::FONTS`),
    les **bons jetons** par `kind` (palette `[recipient.name]`…, pas de valeurs en dur),
    les **chemins des assets réels** (cadre `badge-default-bg.png`, logos UK/Parakou,
    pictogrammes), et surtout **injecte la maquette officielle du type**
    (`LayoutSanitizer::default($kind, $type)`) comme BASE à améliorer - le modèle garde
    le cadre, les logos et le QR au lieu de repartir d'une page blanche. ⚠️ Ajouter une
    capacité au concepteur = l'ajouter aussi à cette consigne, sinon l'IA ne la connaît
    pas. Même endpoint `admin.documents.templates.generate` pour badge ET attestation.
    Gardes : `BadgeAiGenerationTest`.
  - ⚠️ **Repli du cadre quand l'IA est muette** : `LayoutSanitizer::sanitize()` retombe
    sur les éléments **ET le `bg_image`** de la maquette par défaut dès que l'IA ne rend
    aucun élément exploitable - sinon les éléments par défaut flottaient sur un fond blanc
    parce que le JSON de l'IA avait omis `bg_image`.
- **Badges de fonction distincts** : `BadgeCatalog` définit les types et leurs
  destinataires réels (modérateurs et rapporteurs des séances ; équipe d'appui et
  accueil via `event_user` ; comités de supervision, d'organisation et scientifique
  via `event_user` et `committee_members`). Aucun paiement de participation n'est
  requis pour ces fonctions. Chaque type a son propre `document_templates` et sa
  planche d'impression `admin.documents.badges?type=...`. Le tableau Modèles,
  le designer, l'aperçu et Badges prêts utilisent le même type ; ne jamais
  afficher une ligne de rôle sans raccorder aussi ses destinataires et son rendu.
  Pour modérateur et rapporteur, une séance sans adresse e-mail de fonction ne
  crée pas de badge prêt : son libellé peut être collectif ou historique. Les
  affectations liées par e-mail sont dédoublonnées par personne.
- **Un compte peut cumuler des badges sur le même événement** :
  `BadgeCatalog::forAccount()` conserve le badge participant et ajoute chaque
  fonction dont l'adresse e-mail correspond au compte ou à sa fiche participant.
  La page `account.documents` affiche le modèle et la référence propres à chaque
  type ; son titre et la pastille du tableau de bord passent de « Mon badge » à
  « Mes badges » dès qu'il y en a plusieurs. Une fonction ne dépend pas du paiement.
- ⚠️ **Logos OFFICIELS = les mêmes que les attestations** : UK large (`Branding::wide()`,
  chemin relatif) + **logo large de Parakou** (`/img/attestations/logo-parakou.png`). Deux
  boîtes à la **MÊME hauteur** (`h40`, UK w146 x34 / Parakou w158 x204, `y53`), côte à côte
  et centrées **sur le badge**, **agrandies** pour bien remplir l'en-tête (espacement
  resserré pour laisser de la longueur aux filets). ⚠️
  L'ancien **sceau rond** (`/img/badges/parakou-seal.png`) a été **retiré** : utiliser
  l'asset officiel partagé, pas une image propre au badge.
- ⚠️ **Deux filets verts au niveau des logos** : un à gauche (cadre→UK, `x0 w30`), un à
  droite (Parakou→cadre, `x367 w30`), `y69 h8`. **PAS de segment entre les deux logos** (le
  trait du milieu n'était pas voulu). ⚠️ **Chaque filet part du bord même** (`x0` / finit à
  `x397`) pour **MORDRE sur le cadre vert** cuit dans le fond (il occupe `x0..12` à gauche,
  `x384..397` à droite) et fusionner avec lui - sinon il flotte et paraît détaché. Assez
  **épais (`h8`)** pour que la jonction avec le cadre se lise nettement (un `h4` fin
  paraissait décollé même à `x0`). Ce sont des **ÉLÉMENTS** (pas cuits dans le fond, qui a
  été nettoyé) → ils se redimensionnent au changement de format. ⚠️ **Couleur `#00be62` =
  EXACTEMENT celle du cadre (le contour), PAS `#009933`** (la charte). PHP : 2 `rect` dans
  `defaultBadge()` ; concepteur : deux `barEl()` (shape `line-h` + fond `#00be62`, sérialisés
  en `rect`). Garde : `BadgeDefaultTemplateTest` (2 rects `#00be62`).
  - ⚠️ **Le badge DÉLIVRÉ rend un `DocumentTemplate` SAUVEGARDÉ** (`ensureFor()` ne recrée
    que si `layout` est vide) : modifier `defaultBadge()` ne met PAS à jour un modèle déjà
    enregistré. L'aperçu du concepteur, lui, suit le modèle JS `co` en direct. Après une
    refonte du défaut, **réinitialiser le modèle** (Documents → supprimer le modèle = il se
    recrée depuis le nouveau défaut) pour que le badge délivré colle à l'aperçu.
- ⚠️ **Changer de format RÉAJUSTE la maquette** (`badge-designer.js`, `scaleEls(rx, ry)`
  dans le gestionnaire des chips `[data-size]`) : à chaque changement de format, position
  et taille de **chaque élément** sont mises à l'échelle du changement de dimensions (x et
  largeur par le rapport des largeurs, y et hauteur par celui des hauteurs ; **QR, photo et
  corps de texte** suivent le plus petit rapport pour ne pas se déformer), **puis un passage
  `fitText()` garantit que chaque texte tient dans sa boîte** au nouveau format (il REDUIT la
  police tant que le texte déborde en largeur, plancher 6 px ; filet de sécurité par-dessus
  l'échelle proportionnelle, jamais pour grossir). Sans l'échelle, les éléments restaient à
  leurs coordonnées A6 et débordaient d'un format plus petit. Formats dans
  `badge-designer.css` (A6 portrait 397×560 défaut, A6 paysage, A7 portrait, carte, CR80).
  ⚠️ L'image de fond, elle, s'étire en `100% 100%` (cadre baké) - c'est pourquoi le filet,
  lui, est un élément.
- ⚠️ **Jetons PLATS** (`{event}`/`{name}`/`{role}`/`{theme}`/`{location}`/`{dates}`/`{ref}`) :
  les deux chemins de rendu de badge fournissent des clés plates (pas la palette
  `[espace.cle]`). `theme`/`dates`/`location` sont dans les deux cartes de variables
  (`Admin\DocumentController::badges()` **et** `Account\DocumentController::renderBadge()`) -
  sans elles, thème/lieu/dates sortaient vides sur le badge.
- ⚠️ **Le concepteur de badges doit décrire la MÊME maquette** : `badge-designer.js`,
  modèle **`co`** (init par défaut), cadre via la classe CSS **`bg-co`**
  (`badge-designer.css`, référence l'image de fond). ⚠️ `serializeLayout()` **émet
  `bg_image`** quand la page porte `bg-co` - sinon enregistrer un badge depuis le
  concepteur **perdait le cadre** (il n'émettait jamais de fond, contrairement au
  concepteur d'attestations). ⚠️ Le concepteur de badges reste une base plus pauvre que
  celui des attestations (pas d'`upper`/`leading`/interlettrage dans le snapshot) : à
  aligner si on y revient. Gardes : `BadgeDefaultTemplateTest`.
- ⚠️⚠️ **LA PLANCHE DE BADGES EST UN PDF PRODUIT PAR dompdf**, plus une page HTML que
  l'admin imprimait depuis son navigateur. Les trois sorties
  (`DocumentController::badges` par catégorie, `badgesAll` toutes catégories,
  `badgesSelection` les destinataires cochés) rendent `pdf/badges-sheet.blade.php` par
  `Pdf::loadView(...)->setPaper('a4','portrait')` + `BrandedPdf::stamp()` et le
  **téléchargent**. Les anciennes vues `documents/badges-template`, `documents/badges-all`
  et `documents/_badge-sheet` ont été **supprimées** : ne pas les recréer.
  - ⚠️⚠️ **UN BADGE S'IMPRIME À SA TAILLE RÉELLE. ON NE LE RÉTRÉCIT PAS.** Ce sont
    des **dimensions de SORTIE** : le badge entre dans une **pochette**, donc 88 mm
    doivent faire 88 mm sur le papier. Notre travail est de les **DISPOSER** sur l'A4
    en tenant compte de la **zone non imprimable** des imprimantes
    (`BadgeSheet::MIN_MARGIN_MM` = **5 mm**). `BadgeSheet::plan()` calcule combien il
    en tient, l'espace de coupe et l'origine **centrée** ; `scale` vaut **1.0**.
    ⚠️ Le seul rétrécissement admis est un **filet de sécurité** : un format plus
    grand que la feuille, qui sortirait rogné. `trueSize` dit lequel des deux cas.
  - **Formats de porte-badges, et ce qu'ils donnent par feuille A4** (mesuré) :
    **Modèle 303** 88 × 125 mm → **4/page** ; **Modèle 304** 100 × 135 mm →
    **4/page** ; **Modèle 106** 65 × 95 mm → **9/page** ; **CR80** 85,6 × 54 mm →
    **10/page** ; **A6 porte-badge** 108 × 155 mm → **2/page en PAYSAGE** (2 × 108 =
    216 mm, plus large qu'une A4 portrait - mais la feuille se TOURNE, cf. ci-dessous) ;
    **A6 papier**
    105 × 148 mm → **1/page** (2 × 105 = 210 mm = la feuille entière, il ne reste
    rien pour les marges). Ces formats sont des **pastilles du concepteur**
    (`badge-designer.blade.php` + `.ed-page.size-*` dans `badge-designer.css`), et
    **la planche suit ce que le modèle déclare** - c'est là qu'on change la taille.
  - ⚠️⚠️ **LA FEUILLE SE TOURNE QUAND ELLE REND DAVANTAGE DE BADGES.**
    `BadgeSheet::plan()` calcule les **deux** orientations et garde la meilleure
    (clé **`orientation`**). L'A6 porte-badge n'entrait qu'**une** fois par A4
    portrait - une feuille gâchée par badge - et en tient **deux** en paysage.
    ⚠️ **À ÉGALITÉ on garde le PORTRAIT** : c'est l'orientation attendue d'une
    planche, et tourner la feuille sans rien y gagner surprendrait. Un appelant
    peut imposer l'orientation : `plan($w, $h, 'portrait')`.
  - ⚠️⚠️ **UNE FEUILLE N'A QU'UNE ORIENTATION**, et c'est `BadgePdfRenderer::pdf()`
    qui en décide pour tout le document (`setPaper('a4', $orientation)`). Un lot
    **« tous les badges prêts »** mêle des catégories, donc des formats : quand
    leurs plans ne s'accordent pas, on **impose le portrait ET on RECALCULE** les
    plans concernés. Sans ce recalcul, leurs coordonnées viseraient une feuille qui
    n'est pas celle du PDF et les badges sortiraient hors page - le même défaut que
    les positions absolues qui ignoraient `@page { margin }`.
  - ⚠️ **Deux badges peuvent partager leur trait de coupe** (`BadgeSheet::axe()`) :
    on essaie d'abord avec l'espace souhaité (5 mm), puis **sans**, parce que c'est
    souvent ce qui fait tenir une colonne de plus - un badge de 100 mm donne
    2 × 100 = 200 mm, soit exactement la feuille moins ses marges. Une tolérance
    d'**1 px** (0,26 mm) évite qu'un arrondi coûte une colonne.
  - ⚠️ **Les modèles DÉJÀ ENREGISTRÉS étaient tous à 105 × 148 mm** (397 × 560 px,
    la taille de BASE de l'éditeur, une demi-feuille A4) : un format que le
    concepteur ne propose plus, et qui ne sortait qu'à **1 par feuille**. La
    migration **`2026_10_19_000600_retarget_badge_templates_to_pouch_formats`** les
    recompose au **modèle 303**. ⚠️ Elle ne touche **que ce que personne n'a
    ouvert** (`editor_state IS NULL`, même règle que
    `consolidate_participant_badge_models`) et laisse intact tout modèle déjà posé
    sur un format offert - ce qui la rend **rejouable** sans effet. Un modèle
    personnalisé resté sur l'ancien format se change dans le concepteur, qui lui
    ajoute une option **« (ancien format) »**.
  - ⚠️⚠️ **« PROPOSÉ » ET « CONNU » SONT DEUX CHOSES.** **Quatre formats** -
    `a6-portrait` (105 × 148), `a6-landscape` (148 × 105), `a7-portrait` (74 × 105)
    et `card-landscape` (90 × 55) - ne sont **plus proposés** : ce sont des formats
    de feuille ou de carte générique, pas des porte-badges. **Seuls les cinq
    porte-badges sont offerts** (303, 304, 106, A6 porte-badge, CR80). Ils restent
    **déclarés dans `FORMAT_DIMENSIONS`**, et il ne faut pas les en retirer :
    `a6-portrait` est la **taille de BASE** de `.ed-page`, et un badge enregistré
    à ce format **n'a aucune classe** dans son `editor_state` ; les trois autres
    sont portés par la classe d'un badge déjà enregistré. Les effacer rouvrirait ces
    badges à la mauvaise taille, éléments débordant d'une page plus petite - et un
    enregistrement depuis cet écran abîmerait le modèle.
    ⚠️ **Tout format proposé doit avoir au moins un modèle de design**
    (`data-formats` des `.ed-tpl-card`), sinon le panneau « Modèles de design »
    s'ouvre VIDE sur ce format - c'était le cas du CR80, qui n'avait pas la maquette
    officielle alors qu'elle sait se composer en carte. Un cas le vérifie.
    ⚠️ Quand la page porte un format qu'on ne propose plus, `syncFormatControls()`
    ajoute une option **« … (ancien format) »** : sans elle, le menu afficherait un
    AUTRE format que celui de la page, donc mentirait sur ce qu'on modifie.
    Garde : `DocumentDesignerTest` exige que tout format **proposé** soit **connu**,
    et que les deux A6 papier soient **connus sans être proposés** (sabotage vérifié).
  - ⚠️⚠️ **UN FORMAT SE DÉCLARE À UN SEUL ENDROIT : `FORMAT_DIMENSIONS` +
    `FORMAT_LABELS` (`badge-designer.js`).** Les classes à retirer (`SIZE_CLASSES`)
    et la détection du format courant s'en **déduisent**. Il y avait QUATRE listes à
    tenir d'accord - pastilles, menu déroulant, dimensions, libellés - et c'est le
    **menu déroulant** qui a fini par ignorer les formats ajoutés : il proposait
    encore les cinq anciens pendant que les pastilles en montraient neuf. Ajouter un
    format = une ligne dans chacune des deux tables, une pastille, une option, une
    règle `.ed-page.size-*`. Garde : `DocumentDesignerTest` compare les quatre
    listes et exige la règle CSS (sabotage vérifié).
    ⚠️ Et les **cartes de design** (`data-formats`) doivent lister le nouveau format,
    sinon le panneau « Modèles de design » s'ouvre vide sur ce format.
  - ⚠️ **`BadgeSheet::formatLabel()` est la source unique du nom d'un format**
    (« Modèle 303 - 88 × 125 mm ») : le hub l'affiche avec le **nombre par page**,
    pour qu'on voie tout de suite qu'un modèle mal dimensionné n'en sort qu'un.
  - ⚠️⚠️ **LA RÉDUCTION, QUAND ELLE A LIEU, RECALCULE LA MAQUETTE** et n'applique
    PAS `transform: scale()`. **`BadgeSheet::scaleLayout()`** multiplie toute la
    géométrie (x, y, w, h, corps du texte, côté du QR, rayon, filet, interlettrage)
    et rend le badge **dessiné** à la taille voulue. Un `transform` CSS aurait été le
    réflexe : dompdf ne l'applique pas de façon fiable, et c'est **lui** qui produit
    le document imprimé. ⚠️ **`leading` est un RAPPORT, pas une longueur** : le
    mettre à l'échelle diviserait l'interligne. Tout nouvel attribut géométrique du
    concepteur doit être ajouté à `scaleLayout()`.
  - ⚠️⚠️ **DEUX PIÈGES dompdf ONT FAIT DÉBORDER LA PLANCHE DE LA FEUILLE** (un badge
    coupé à droite, la moitié basse de la page vide). Les deux sont invisibles à la
    lecture du code et ne se voient qu'en RENDANT le PDF :
    1. **dompdf n'ENROULE PAS les `float`.** Il alignait les quatre badges sur UNE
       seule rangée, qui sortait de l'A4. La planche se pose donc en
       **`position: absolute`**, jamais en flottant - c'est d'ailleurs déjà ainsi
       que le badge lui-même est dessiné, et dompdf le rend très bien.
    2. **Un élément absolu est placé par rapport à la FEUILLE, pas à la zone
       imprimable** : dompdf ignore `@page { margin }` pour ces éléments, et
       l'encre se collait au bord gauche (0 mm mesuré). La marge d'impression est
       donc portée par les **coordonnées** (`marginPx`), et `@page` est à **0**.
    ⚠️ Un conteneur `position: relative` ne sert pas de référence non plus : ne pas
    compter dessus pour recentrer la grille, le décalage se met dans chaque cellule.
  - **Vérifié en MESURANT le PDF rendu**, pas à l'œil : encre 197,6 × 276,4 mm sur
    A4, marges 6,1 mm à gauche / 6,3 mm à droite / 10,4 mm en haut / 10,3 mm en bas.
    (Outil : `python -m pip install pymupdf`, puis rendu de la page en PNG et
    recherche du premier pixel encré. Aucun convertisseur PDF n'est installé sur le
    poste - `pdftoppm`, Ghostscript et ImageMagick manquent.)
  - **Gardes** : `DocumentsTest` éprouve la **VUE** (elle portait le défaut, pas le
    plan) - chaque cellule rendue doit tenir dans la zone imprimable - et interdit
    le retour d'un `float` dans `pdf/badges-sheet.blade.php`. Sabotage vérifié.
  - ⚠️ **Le MODÈLE Word de référence**, mesuré (`Badges/CO/template.docx`,
    `Moderateurs/template.docx`) : A4, marges de page **4,3 mm**, quatre cadres de
    **104,0 × 139,0 mm** posés en 2×2 (x = 1,5 et 104,2 mm ; y = 10,1 et 151,4 mm).
    Nos badges sortent un peu plus petits (**96,3 × 135,7 mm**) et c'est **le format
    du modèle qui l'explique**, pas le tuilage : le concepteur dessine en **A6
    portrait (397×560 px, rapport 0,709)** quand le cadre Word a un rapport de
    **0,748**. À rapport A6, 104 mm de large imposeraient 146,7 mm de haut, soit
    293 mm pour deux rangées - plus de place pour les marges. **Le format se choisit dans le
    concepteur** (pastilles Modèle 303 / 304 / 106 / A6 porte-badge / CR80) et la
    planche suit : elle dispose à la taille réelle ce que le modèle déclare.
  - **Planche « tous les badges prêts »** (`admin.documents.badges.all`) : toutes les
    catégories ayant des destinataires, **groupées par format**, chaque format remplissant
    ses propres pages. ⚠️ **On ne mêle jamais deux formats sur une même feuille** (même
    règle que le lot d'attestations : la taille de page vient de la mise en page du modèle).
  - ⚠️⚠️ **LE LOT EST BORNÉ : `BadgeSheet::LOT` = 48 badges** (jusqu'à 48 feuilles
    A4 quand le format n'en permet qu'un par page). **Mesure sur l'ancien format** :
    environ **2,5 Mo de mémoire par badge** et 6,9 s pour 48 ; une édition
    de 191 inscrits faisait tomber dompdf **au milieu** du rendu (128 Mo dépassés), donc
    une page blanche après l'attente. Un lot borné échoue vite ou réussit vite - même
    raison que `AttestationExportController::LOT`. `prepareBadgeExport()` relève
    **avant le dessin des badges** la limite à 512 Mo et le délai PHP à 300 s :
    le rendu Dompdf d'une grande sélection dépassait le défaut de 30 s.
    Pour obtenir un seul fichier de toute une catégorie, `badge_exports` conserve
    l'état d'une demande privée du gestionnaire. `GenerateBadgeExport` dessine
    chaque tranche de 48 avec `BadgePdfRenderer`, puis assemble les PDF avec FPDI.
    `BadgeExportWorkerLauncher` démarre un travailleur éphémère caché sous Windows ;
    la connexion `badge_exports` a un `retry_after` supérieur au timeout du job.
    L'interface vérifie l'état et propose le téléchargement du fichier privé une
    fois prêt. La pagination des badges prêts accepte 20, 48, 100 ou 200 lignes,
    sans changer la limite de 48 par rendu PDF.
    ⚠️ Le découpage de `badgesAll` se fait sur la liste **à plat** (catégorie,
    destinataire) : un plafond appliqué catégorie par catégorie en laisserait en arrière.
    ⚠️ **L'interface ANNONCE les lots** (`_badge-lots.blade.php`, « Lot 1/5 ») dès qu'il y
    en a plus d'un - les cacher laisserait croire que « Imprimer » sort tout le monde.
  - **Imprimer une SÉLECTION** (`admin.documents.badges.selection`, POST) : on coche des
    destinataires dans « Badges prêts » et on n'imprime qu'eux. ⚠️ La clé est la
    **référence du badge** (`BadgeCatalog::reference()`), déjà celle du QR : stable, opaque,
    et elle ne divulgue aucun identifiant interne. Une sélection vide **le dit** au lieu de
    rendre un PDF vide - comme une catégorie sans destinataire.
  - ⚠️ **Le texte d'un PDF dompdf est compressé** (polices sous-ensemblées) : aucun test ne
    peut l'y lire. Que la planche lise bien le **modèle enregistré** se gèle donc par une
    preuve OBSERVABLE - un modèle de 700×900 n'en laisse tenir qu'**un par page**, là où
    l'A6 par défaut en met quatre, donc deux destinataires donnent deux pages
    (`DocumentsTest`). La substitution des jetons s'éprouve un cran plus bas, sur
    `LayoutRenderer` (`DocumentDesignerTest`).

- ⚠️ **Le hub Badges (`?tab=badges&stab=modeles`) est un STUDIO : une liste à gauche,
  l'APERÇU DU MODÈLE CHOISI à droite, sur la même page.** Cliquer une ligne montre le badge
  en grand (260 px, rendu par le moteur et les jetons de la planche via
  `DocumentHubController::badgePreviewVars()`), avec son format, son nombre de destinataires
  et son QR. Bandeau KPI `tw-stat` (catégories, destinataires prêts, catégories prêtes, avec
  QR) pour la **parité** avec les onglets Attestations et Livre.
  - ⚠️ **Trois colonnes ont été RETIRÉES, ne pas les réintroduire** : « Aperçu » (une
    vignette de 86 px n'apprend rien - c'est ce qui a motivé l'aperçu sur la page),
    « Type » (le libellé le porte déjà) et « QR » (il se lit dans l'aperçu). Restent
    *Libellé - Format - Destinataires - Statut - Actions*. Un cas vise l'**en-tête exact**
    du tableau des badges : la page rend aussi les onglets Attestations et Livre, donc une
    assertion d'absence globale mordrait sur eux.
  - ⚠️ **Plus de bouton « œil »** : il ouvrait un onglet pour ce que l'aperçu montre
    désormais sur place. Les actions se réduisent à **imprimer (PDF)** et **modifier le
    modèle** ; elles portent `@click.stop`, sinon le clic de la ligne les doublerait.
  - Styles `.badge-studio*` / `.badge-preview` dans `admin.css` ; sous 1100 px la colonne
    d'aperçu repasse **sous** la liste (à 292 px elle écraserait le badge).
  - ⚠️ L'aperçu ne charge pas `documents/_fonts` (Jost est déjà la police de l'admin) ;
    une attestation, elle, l'exigerait.
  - ⚠️ **La sélection et la pagination de « Badges prêts » vivent DANS la région échangée**
    (`data-filter-part="badges-table"`) : changer de filtre change la liste, donc la
    sélection repart de zéro - cocher des gens qu'on ne voit plus serait pire. (Au passage,
    la pagination n'était rattachée à **aucune** région : son compteur ne suivait pas les
    filtres.) Le formulaire de sélection (POST) est **frère** de la barre de filtres (GET) :
    deux formulaires ne s'imbriquent pas.
  - Gardes : `DocumentsTest` (KPI, aperçu sur la page, colonnes retirées, cases à cocher,
    PDF par catégorie / global / sélection, tuilage et mise à l'échelle).

L'onglet **Planning** (`admin/programme?tab=planning`) est un **board CSS grid** : colonnes =
salles (une par atelier daté), lignes = tranches de 30 min (axe horaire à gauche). Les
**plénières** sont des **bandeaux pleine largeur** (dégradé `--ukc-blue-dark→blue→bright`,
badge or) traversant toutes les colonnes → l'exclusivité est visible à l'œil ; les
**ateliers** occupent leur colonne de salle **en parallèle**. Classes `.plan-*` dans
`admin.css` (thème clair admin, Public Sans/Jost via `.ukc-shell`). Placement calculé en
Blade (`$rowLine`, colonnes = salles distinctes triées). Sessions sans horaire listées à
part (« Sessions sans horaire »). Pool **« À planifier »** filtrable (via `tableFilters`).
Bascule **Jour / Semaine** (Alpine `pview`, mémorisé en `localStorage['ukcPlanView']`) :
la **vue Semaine** est un **agenda par jour** (`.plan-weekgrid`, une **colonne par jour**,
classes `.wk-*`), **PAS** une grille time-alignée. ⚠️ L'ancienne vue Semaine time-alignée
(`.plan-week`/`.week-*`, chaque bloc positionné par `grid-row` selon l'heure) **empilait les
bandes qui se chevauchent l'une SUR l'autre** (ex. 08:30-11:30 et 09:00-11:00) : elles se
cachaient et les chips débordaient. Désormais chaque colonne-jour empile ses **blocs dans
l'ordre chronologique** (aucun chevauchement possible) : plénières = blocs bleus (badge or),
ateliers = blocs blancs regroupés par tranche horaire, avec **une chip par atelier** libellée
**« Atelier N - 📍 Salle X »** (`.wk-chip .a` = nom court de l'atelier via `Str::before(title, ' -')`,
`.wk-chip .r` = salle avec pictogramme). ⚠️ Ne PAS remettre le compteur brut `placés/total`
sur la chip : il était incompris (« 1/1 », « 2/2 ») - il vit désormais dans l'**infobulle**
(survol). Les **conflits de salle** du jour (même salle réelle, horaires qui se
chevauchent) sont recalculés côté Semaine et **bordés d'orange** (chip `.wk-chip.conflict`,
bloc `.wk-block--band.room-conflict`), comme en vue Jour. C'est une vue d'ensemble en
lecture/édition-session (le placement des communications reste en Jour).
- **Salle (vue Jour)** : la **plénière** (bandeau plein largeur, sans colonne de salle)
  affiche sa salle dans sa ligne meta (`📍 room`). Les **cartes d'atelier** n'affichent
  **pas** la salle (⚠️ ne pas la remettre) : la colonne du board porte déjà le nom de salle
  (en-tête collant), donc c'est redondant.
- ⚠️⚠️ **UN CRÉNEAU PREND LE CADRE DE SA SÉANCE** : son **jour**, sa **salle** et une
  **heure de sa fenêtre**, et il n'accueille que les communications **encore à placer
  dans les axes de son atelier**. Tout cela se saisissait à la main, donc tout cela
  pouvait contredire la séance choisie juste au-dessus.
  - **Le formulaire propose** (`resources/js/program-pure.js`, règles pures couvertes
    par `program-pure.test.js` ; composant Alpine `ukcSlotForm` ; données servies par
    `ProgramController::slotFormMeta()`) : jour et salle remplis **en lecture seule**,
    `min`/`max` sur l'heure, liste des communications restreinte.
    - ⚠️ **Aucune légende sous les champs** : les trois qui expliquaient la déduction,
      la fenêtre horaire et le nombre de communications écartées ont été **retirées**
      à la demande de l'équipe - le panneau en portait plus que de champs. Ce sont
      les champs eux-mêmes qui disent la règle (lecture seule, bornes du sélecteur
      d'heure, liste courte). ⚠️ Revers assumé : une liste **vide** - toutes les
      acceptées déjà placées - ne s'explique plus. Ne pas les réintroduire sans le
      demander.
  - ⚠️ **Le serveur décide** (`ProgramController::slotFrame()`, appelé par `storeSlot`
    **et** `updateSlot`) : un champ en lecture seule se retire en deux clics. Le jour et
    la salle viennent de la séance dès qu'elle en porte, et une heure hors fenêtre est
    **refusée** (`ukc_error`), pas rognée en silence.
  - ⚠️ **L'heure doit tomber DANS la séance, mais le créneau n'a pas à y tenir en
    entier** : un poster dure `poster_min` (90 min par défaut), bien plus que la séance
    qui l'accueille - exiger que la fin y entre refuserait des placements normaux.
  - ⚠️ **Le choix courant reste TOUJOURS proposé**, même déjà placé (on modifie le
    créneau qui le porte) ou **hors axe** (on vient du vivier « Assigner » :
    communication d'abord, séance ensuite). Il est **marqué « hors axe »**, jamais
    supprimé en silence - et hors axe n'est pas interdit, l'affectation automatique s'y
    replie elle aussi.
  - ⚠️ **Une séance sans axes ne filtre rien** (une plénière ne porte pas d'atelier) :
    filtrer sur une liste vide ne laisserait rien à choisir.
  - ⚠️ **Un créneau peut être rangé dans SA PROPRE plénière** : il en est le contenu,
    pas une activité concurrente. `blockingSession()` reçoit donc `ignoreSessionId`.
    Sans cela, choisir une plénière devenait impossible dès que le créneau en prenait
    l'horaire - c'est-à-dire toujours, depuis qu'il en hérite.
  - ⚠️ **La liste brute des acceptées n'est plus passée à la vue** (`$accepted`) : le
    formulaire était son seul lecteur, et il ne propose plus toutes les acceptées.
  - Gardes : `CreneauDansSaSeanceTest` (9 cas, 4 sabotages vérifiés) + `program-pure.test.js`
    (16 cas, 2 sabotages vérifiés).
- ⚠️⚠️ **UNE SALLE EMPLOYÉE PAR LE PROGRAMME EST UN ESPACE DÉCLARÉ**
  (`App\Support\ProgramSpaces`). Les salles vivaient en deux listes voisines : les
  **espaces** de `settings.program.spaces`, et les « salles utilisées (dérivées du
  programme) », simples pastilles qu'on ne pouvait **ni renseigner ni renommer**.
  Les neuf salles du colloque de démonstration étaient dans la seconde.
  - ⚠️⚠️ **LE NOM EST LE SEUL LIEN** : `event_sessions.room`, `slots.room` et la
    carte `atelier_rooms` portent le **nom** de la salle, pas un identifiant. Deux
    conséquences, et ce sont elles qui portent tout le reste.
  - **Déclaration d'office, AU RENDU** de `ProgramController::index()`, comme la
    réconciliation du statut de visio juste au-dessus : les salles arrivent par cinq
    chemins (séance, créneau, salle d'atelier, génération depuis le programme
    synthétique, import), et un seul oublié laisserait des salles orphelines sans
    que rien ne le signale. L'écriture n'a lieu **que s'il en manque**.
  - ⚠️ **Renommer un espace renomme la salle PARTOUT** (`ProgramSpaces::rename()` :
    séances, créneaux, `atelier_rooms`). Écrire la seule ligne de configuration les
    laisserait sur l'ancien nom, **aussitôt redéclaré en espace** - on aurait
    renommé une fiche et créé un doublon, pendant que le programme continue
    d'annoncer l'ancien nom.
  - ⚠️ **La réécriture passe par le query builder**, donc sans `Slot::updated` ni
    journal de reprogrammation - **et c'est voulu** : renommer une salle ne déplace
    personne, et l'assistance ne doit pas annoncer à deux cents auteurs un
    changement de lieu qui n'a pas eu lieu.
  - ⚠️ **Renommer SUR un espace existant le REJOINT** (c'est ainsi qu'on répare
    « Salle1 » en « Salle 1 ») : la fiche d'arrivée garde ses renseignements, la
    fiche renommée disparaît, et le message le dit.
  - ⚠️ **Un espace au programme ne se supprime pas** : il serait redéclaré au rendu
    suivant, et on verrait revenir ce qu'on vient d'effacer. Le bouton n'est donc
    **pas offert** sur ces fiches - `data-ajax-msg` se dit sur **toute** réponse, et
    un refus serveur aurait quand même affiché « Espace supprimé. »
  - ⚠️ **L'index du premier espace vaut 0, et 0 est faux** : le panneau éprouve
    `form.i !== ''` (pas `form.id ? …`), sinon modifier la première salle en
    ajoutait une seconde.
  - ⚠️ **Hors périmètre, et c'est dit** : `formations.room` est une saisie libre,
    sans rapport avec les espaces - renommer un espace ne la touche pas (elle ne
    figurait pas davantage dans les « salles utilisées »).
  - Gardes : `EspacesDuProgrammeTest` (9 cas, 5 sabotages vérifiés).
- **Tous les formulaires du Programme = panneau latéral (slideover)**, plus de `.ukc-modal` :
  `openAtelier/openSession/openSlot/openSpace/openRoom` posent `modal='…'` et
  `_modals.blade.php` rend un `<x-ukc.slideover open="modal==='…'">` par cas (atelier, session,
  créneau, salle, espace), chacun avec un `<form id="…Form">` et le bouton *Enregistrer* dans
  le pied via `form=`. Même schéma que « Nouvelle tâche » (Projet) / l'éditeur de formulaire.
- ⚠️ **Plus de bouton « Nouveau créneau »** dans la barre d'actions de `admin/programme` (et
  l'évènement `ukc-new-slot` a été retiré) : on crée/place les créneaux depuis le board
  (clic sur un créneau libre, glisser-déposer) et l'onglet Créneaux.
- **Pause déjeuner = exclusive, comme une plénière** : dessinée en **bandeau plein largeur**
  (`.plan-break`, vue Jour ; bloc `.wk-block--break` dans le fil de la vue Semaine) d'après
  les paramètres de créneaux (`settings.program.params.lunch{,_start,_end}`), et **bloquée à
  la sauvegarde** de toute session/créneau qui la chevauche via
  **`ProgramConflicts::lunchClash()`** + `breakMessage()` (appelé après le contrôle plénière
  dans `storeSession/updateSession/storeSlot/updateSlot`). Rien ne se tient en parallèle du
  déjeuner.
- **Les paramètres de créneaux (onglet Options) SONT appliqués** : `generateSlots()` lit
  `settings.program.params` et respecte la durée orale (`oral_min`), la **transition**
  (`gap_min`) entre créneaux, la **pause-café** après N créneaux (`auto_break`/`break_after`/
  `break_min`) et **saute la pause déjeuner** (`lunch*`) - aucun créneau généré ne tombe sur
  une pause. Tests : `ProgramSlotsTest` (pause déjeuner + pause-café).
Les créneaux « libres » et le pool ouvrent le slideover (`openSlot`/`openSession`).
L'ancien agenda FullCalendar de cet onglet (+ `program-calendar.js`, `_session-block`,
`$calendarEvents`) a été **supprimé** (redondant).
- **Glisser-déposer** (app.js, listeners délégués globaux) : on tire une communication
  (item du pool `[data-sub-id]` OU créneau placé) vers un créneau **libre**
  (`.plan-slot.free[data-assign-url]`). Le drop crée un formulaire **`data-ajax`
  éphémère** (POST `slots.assign`, CSRF depuis `<meta name="csrf-token">`) ciblant
  `data-ajax-root="prog-planning"` → board + pool rafraîchis d'un bloc. HTML5 DnD =
  **desktop** ; le **clic** (modale) reste le repli tactile. La règle plénière est déjà
  garantie (un créneau libre n'existe que dans un atelier).
- **Surlignages de conflit (vue Jour uniquement, calculés en Blade)** : deux repérages
  visuels distincts, sans bloquer l'affichage. (1) **Conflit d'auteur** (`$conflictSlots`,
  **rouge**) : un même auteur correspondant sur deux créneaux placés qui se chevauchent
  (télescopage physique) → le créneau (`.plan-slot.placed.conflict`) est surligné + drapeau.
  (2) **Salle en double** (`$roomConflicts`, **orange**) : deux **séances** horodatées qui
  se chevauchent dans la **même salle réelle** (`$realRoom` = `room` ou salle de l'atelier ;
  une salle « À définir » n'est **pas** comptée) → la carte de séance
  (`.plan-ev--atelier.room-conflict` / `.plan-ev--plenary.room-conflict`) est bordée
  d'orange + icône `alert-triangle`. Chacun a son bandeau récap en tête de jour
  (`.plan-conflict-banner` rouge / `.plan-conflict-banner--room` orange). Rappel métier :
  les ateliers **parallèles** ne sont légitimes que dans des salles **distinctes** ; deux
  ateliers dans la même salle au même moment = double-booking. Ce cas **n'est pas bloqué à
  la sauvegarde** (seule la règle plénière l'est) : la détection le rend visible. Le seed de
  démo en contient (deux séances d'un même atelier partagent une salle sur un créneau qui se
  chevauche).

### Programme - exclusivité des plénières / ateliers parallèles

Règle métier : une **session plénière est EXCLUSIVE** (aucune autre activité au même
moment) ; les **ateliers peuvent se dérouler en parallèle** entre eux. Source unique :
**`App\Support\ProgramConflicts::blockingSession()`** (chevauchement interdit dès qu'une
plénière est impliquée ; deux ateliers peuvent se chevaucher). Appliqué **en blocage** à
la sauvegarde des sessions et des créneaux (`ProgramController::storeSession`/`updateSession`/
`storeSlot`/`updateSlot` → `back()->with('ukc_error', …)`). `generateSlots` ne découpe **pas**
les plénières (exclusives, sans communication orale). L'affectation auto autorise les
ateliers simultanés mais **jamais un même auteur sur deux créneaux qui se chevauchent**.
Côté public (page colloque + Déroulement), les ateliers simultanés portent un badge
**« En parallèle »**. Tests : `ProgramPlenaryExclusivityTest`.

### Secrétariat = TROIS onglets portés par l'URL

`SecretariatController` + `admin/secretariat/_nav.blade.php` (pilules `.ukc-nav-docs`,
`['current' => …]`). **Ordre des onglets : Documents - Secrétariat assisté - Rapports de
séance.**

⚠️ **L'entrée du module (`admin.secretariat`, `/admin/secretariat`) RENVOIE vers
l'onglet Documents.** C'est celui qu'on vient ouvrir le plus souvent - imprimer une
liste de présence, une feuille d'émargement, un ordre du jour - et le seul qui ne
dépende d'aucun travail en cours : hors période de colloque, arriver sur les rapports
montrait un tableau vide. **Un renvoi, pas une seconde page** : l'onglet Documents a
déjà son adresse, et la rendre aussi à l'entrée ferait deux URL pour le même écran.

- **Rapports de séance** (`admin.secretariat.reports`, vue `admin/secretariat.blade.php`) =
  **poste de travail unifié**, **scopé par événement** (sélecteur « Événement » en tête,
  `?event_id=`, défaut `currentEditionId()` - comme les onglets Documents et Assisté).
  Quatre cartes KPI **cliquables = filtres** (`à valider` / `en attente` / `validés` /
  `toutes`) sur **un tableau unique** (séance - rapporteur - statut - date - action),
  filtré côté client (Alpine). Statut calculé une fois dans `index()` (`to_validate` →
  `awaiting` → `scheduled` → `validated`, rang de priorité). ⚠️ **Cliquer « Validés »
  montre TOUS les rapports validés de l'événement** (il n'y a plus de « 6 récents » ni de
  second écran).
  - ⚠️ **Les trois onglets du Secrétariat portent le même sélecteur d'événement en tête**
    (Rapports `?event_id=`, Documents `?doc_event=`, Assisté `?assist_event=`) : le
    vocabulaire est « Événement », jamais « Colloque » (le modèle est typé).
  - ⚠️ **Lecture / amélioration / VALIDATION se font SUR PLACE**, dans un **panneau
    latéral** (`<x-ukc.slideover>`, état `{ open, cur }`, `show(row)` pose `cur` via
    `@js($rowData)` et `open=true`). Le panneau édite synthèse/points/affluence
    (`<form :action="cur.action">` → `admin.program.reports.save`) et, pour un rapport
    validé, propose **Rouvrir au rapporteur** (`cur.reopen` → `admin.program.reports.reopen`,
    `data-confirm`). Mêmes endpoints de mutation qu'avant (dans `ProgramController`).
  - ⚠️ **Il n'y a qu'UNE page de rapports, et c'est celle-ci.** Il en a existé une
    seconde, par événement, qui doublait ce tableau de bord (deux écrans « Rapports de
    séance » avec cartes + liste) : elle a été retirée, et sa vue
    `admin/secretariat/reports.blade.php` **supprimée**. Ne pas la recréer.
  - ⚠️ **`/secretariat` et `/secretariat/rapports` ont ÉCHANGÉ leur rôle** : l'entrée
    du module menait au tableau de bord et `/secretariat/rapports` y renvoyait ; c'est
    désormais l'inverse. Ce sont les URL qui ont bougé, pas le nombre d'écrans. Tout ce
    qui VEUT les rapports vise donc `admin.secretariat.reports` - la redirection de
    `admin.program.reports` comprise, sans quoi une adresse qui promet un rapport
    déposerait sur des documents à imprimer.
  - ⚠️ **« Relancer » = notification SILENCIEUSE, pas un `mailto`** (POST
    `admin.secretariat.remind` → `SecretariatController::remind`). Au clic : une
    notification in-app au compte du rapporteur (`Notifier::account`, kind
    **`report_reminder`**) + un e-mail rendu depuis le **modèle éditable**
    `report_reminder` (`EmailTemplates::forReportReminder`, `CampaignMail`), gaté par la
    préférence e-mail. ⚠️ Le kind est déclaré **`hasMail => true`** dans
    `NotificationKinds` pour que la notification n'envoie PAS son e-mail générique (sinon
    doublon avec le modèle). Le modèle de relance se personnalise **parmi les modèles de
    mails** (onglet E-mails). Ne pas revenir à un `mailto:` que l'admin composerait.
  - ⚠️ **Les rapports de séance s'EXPORTENT depuis le module Rapports** (`/admin/rapports`) :
    dataset **`session_reports`** dans « Exports prêts » (CSV/XLSX, `ReportController::export`)
    et périmètre **`reports`** de l'« Export personnalisé (IA) » (lu par l'IA, inclus aussi
    dans le périmètre « Événement »). Lignes/en-têtes dans
    `EventReport::sessionReportRows()`/`sessionReportHeaders()` (une ligne par séance avec
    rapporteur : état, affluence, synthèse, points saillants).
- **Documents** (`admin.secretariat.documents`, vue `admin/secretariat/documents.blade.php`)
  = cinq documents logistiques **sans IA**, remplis depuis les données réelles et
  **imprimables** : liste de présence, feuille d'émargement, affiche directionnelle,
  ordre du jour, chevalets nominatifs. Sélecteur d'événement (`?doc_event=`), une carte
  par document ouvrant un **slideover** de paramètres (quelques champs : séance/jour,
  en-tête libre, lignes vierges, « pour qui »…). Le slideover est un `<form method="GET"
  target="_blank">` vers **`admin.secretariat.documents.print` (`/…/{event}/{type}/imprimer`)**
  qui rend une page autonome `secretariat/print/{type}.blade.php` (layout partagé
  `_layout`, barre Imprimer/Fermer `window.print()`, `@media print`). Données par type :
  `SecretariatController::printData()` (participants, séances du jour, salles groupées,
  modérateurs/rapporteurs distincts). Types + libellés : `SecretariatController::PRINT_TYPES`.
  ⚠️ Ces documents sont des **pages HTML imprimables** (pas de dompdf) : rapides,
  déterministes. Tests : `SecretariatDocumentsTest`.
  - **En-tête des documents conçu une fois, PAR ÉVÉNEMENT** : carte « En-tête des
    documents » en tête de l'onglet (slideover `panel==='header'`, POST
    `admin.secretariat.documents.header` → `saveDocHeader`). Réglages
    `secretariat.doc_org` (surtitre/organisation, repli `config('app.name')`),
    `doc_subtitle` (mention), `doc_logo` (bool, `Branding::mark()`), `doc_align`
    (left/center), via `EventSettings`. Résolus par `SecretariatController::docHeader()`
    et rendus par le **partial partagé `secretariat/print/_head.blade.php`** (inclus par
    presence/emargement/ordre_jour avec `title`+`sub`). ⚠️ Ne pas réécrire un en-tête en
    dur « {app} - {type} » dans une vue d'impression : passer par `_head`.
  - **Liste de présence / émargement : case « Liste vierge »** (`blank=1` + `rows`) =
    aucun participant pré-rempli, uniquement des lignes numérotées vides à remplir à la
    main. Sinon participants triés par nom (émargement garde ses `extra` lignes en plus).
    Lu par `printData()` ; `extraRows` sert aussi bien aux lignes vierges qu'au mode
    pré-rempli.
  - ⚠️ **La barre de `secretariat/print/_layout` est au bleu de la charte** (dégradé
    `--navy #14205a → --blue #223ea4`, comme les héros), pas l'ardoise. Bouton Imprimer
    en or charte.
  - ⚠️ **`tw-stat__icon` est en `position: absolute`** (badge d'angle des cartes stat :
    `top/right`). Dans une carte qui n'est **pas** `position: relative` (ici
    `.sec-doc-card`), l'icône s'échappe vers le **coin haut-droit de la page** et se pose
    par-dessus l'avatar - c'était le « carré gris » signalé. Toute carte réutilisant
    `tw-stat__icon` en icône **inline** doit la repasser en `position: static` (cf. règle
    `.sec-doc-card .tw-stat__icon` dans `tailwise.css`), ou poser `position: relative` sur
    la carte.
- **Secrétariat assisté** (`admin.secretariat.assistant` GET + `admin.secretariat.assist`
  POST, vue `admin/secretariat/assistant.blade.php`) = génération **IA** sur mesure
  (prompt/modèle joint), conservée pour les documents non standard. Presets
  `SecretariatController::PRESETS`.

### Réclamations : notification CIBLÉE + transfert (2026-10-26)

Une réclamation (ou incident) ne diffuse plus à tout le back-office : elle
remonte à qui la traite. Source unique : **`App\Actions\NotifyClaimRecipients`**.

- ⚠️ **Rattachée à un événement → gestionnaire + co-gestionnaire de CET
  événement** (`event_user.role` ∈ `gerant`, `co_gerant`), eux seuls. **Sans
  événement (réclamation générale) → les administrateurs** (`User::administrateurs()`,
  pas la seule colonne `access_level`). ⚠️ **Filet** : un événement sans aucun
  gestionnaire retombe sur les administrateurs - sinon la réclamation n'aurait
  aucun destinataire.
- **Type de notification `claim_new`** (`NotificationKinds`) : audience `staff`,
  `inapp => true`, `email => true`, **`hasMail => false`**. Donc **in-app TOUJOURS +
  e-mail générique**, et le destinataire peut **décocher l'e-mail** dans ses
  préférences (apparaît dans l'écran des préférences staff). ⚠️ Posé à
  `hasMail => true`, la notification supprimerait son propre e-mail en attendant un
  Mailable qui n'existe pas, et personne ne serait prévenu.
- **Création** : la diffusion `Notifier::staff(kind: 'contact', …)` de
  `GescoController` est remplacée par `NotifyClaimRecipients::handle($record)`. Le
  kind `contact` reste pour le formulaire de contact de l'accueil, pas pour les
  réclamations.
- **Transfert** : `admin.claims.transfer` (`ClaimController::transfer`), **réservé à
  l'administrateur** (`abort_unless(isColloqueAdmin)`). Rattache la réclamation à un
  événement et **notifie les gestionnaires de l'événement d'arrivée**
  (`handle(..., transferred: true)`). Carte « Rattachement » sur la fiche
  (`admin/claims/show.blade.php`), visible des seuls admins ; `show()` ne charge la
  liste des événements que pour eux.
- Gardes : `ClaimsTest` (6 cas ajoutés : ciblage gestionnaires vs admins, repli sans
  gérant, coupure de l'e-mail sans toucher l'in-app, transfert admin + notification,
  transfert refusé au gestionnaire).

### Documents de tâche : un DOCX ou un XLSX, rédigé à plusieurs

Une tâche du module Projet porte **un responsable ET des personnes associées**
(`project_task_collaborators`), et ces personnes travaillent ensemble sur des
**documents bureautiques** (`project_task_documents`), chacun dans une page à lui
(`admin.project.documents.edit`).

⚠️⚠️ **UN DOCUMENT DE TÂCHE EST UN FICHIER DOCX OU XLSX, ET RIEN D'AUTRE**
(21/10/2026). Il vit chez **ONLYOFFICE DocSpace**, où se tiennent aussi la
coédition et l'historique des versions ; GEVES ne conserve que son identifiant,
son titre, son format et le jeton de son lien d'édition. Le panneau d'une tâche
le crée vierge ou l'importe (20 Mo) ; **`App\Support\TaskDocSpace`** crée au
besoin un dossier par tâche dans la salle configurée, puis le fichier, puis un
**lien d'édition propre à CE fichier**, et le SDK Embed l'ouvre dans la page.
Réglages : `DOCSPACE_URL`, `DOCSPACE_API_KEY`, `DOCSPACE_ROOM_ID` et
`DOCSPACE_FILE_LINKS_ENABLED` (**faux par défaut** : aucun lien n'est créé tant
qu'un administrateur ne l'a pas autorisé). Mise en service, salle et limites :
**`docs/docspace-documents-taches.md`**.

- ⚠️⚠️ **LE RESPONSABLE N'EST PAS REMPLACÉ.** `project_tasks.assignee_id` reste :
  une tâche garde **quelqu'un qui en répond**, et réunit autour de lui ceux qui y
  travaillent. Fondre les deux ferait disparaître la redevabilité, qui est
  précisément ce qu'un tableau de projet sert à tenir. Source unique de « qui
  travaille là-dessus » : **`ProjectTask::associateIds()`**.
- ⚠️ **Les collaborateurs se bornent au MÊME vivier que le responsable**
  (`ProjectController::assigneePool()`). Ils ouvrent le plan de travail de
  l'événement : un identifiant posté n'est pas une autorisation.
  ⚠️ **Ce vivier est l'ÉQUIPE de l'événement, plus l'administrateur connecté
  LUI-MÊME** (21/10/2026). Un accès global ne vaut pas affectation au projet -
  dérouler tous les administrateurs de la plateforme désignerait des gens qui
  n'y travaillent pas - mais c'est l'administrateur qui **monte** l'événement,
  et sans lui la liste s'ouvre **vide** avant qu'aucune équipe n'existe : il ne
  pourrait pas même se confier la première tâche. Il n'y entre donc que lui,
  jamais ses pairs. Les deux moitiés de la règle sont sabotées dans `ProjectTest`.
- **Lecture et écriture ne se confondent pas** : lire = qui ouvre le module
  Projet de l'événement (`ProjectTaskDocument::isVisibleTo()`), écrire = les
  personnes associées, le créateur du document, l'**administrateur**
  (`isEditableBy()`). ⚠️ L'administrateur, pas le seul super administrateur : le
  modèle lui donne accès à tout, et `isColloqueSuperAdmin()` dans une garde
  refermerait ce qu'on vient de lui ouvrir (cf. `DroitsDeLAdministrateurTest`,
  qui interdit cet appel hors de `User`). Sans cette distinction, « associer
  quelqu'un » ne voudrait rien dire, puisque tout le monde écrirait déjà.
  ⚠️ `isEditableBy()` vérifie l'accès au module **en premier** : quelqu'un retiré
  de l'équipe ne doit plus écrire, même si son nom est resté dans une vieille tâche.
- **Le gestionnaire retire un document qu'il n'a pas rédigé**
  (`isDeletableBy()`) : il n'écrit pas dans la tâche s'il n'y est pas associé,
  mais il répond du plan de travail.

#### Ce qui circule, et par où

- ⚠️ **La CLÉ de l'API ne descend JAMAIS dans la page** : seul le `requestToken`
  du fichier part au SDK, et il est **chiffré en base** (cast `encrypted`). Deux
  cas de garde l'éprouvent, l'un sur le HTML rendu, l'autre sur la colonne brute.
- ⚠️⚠️ **CE JETON EST PARTAGÉ PAR LES RÉDACTEURS DE LA TÂCHE, ET IL VAUT UN DROIT
  D'ÉCRITURE CHEZ LE PRESTATAIRE.** Il n'est pas remis aux lecteurs, mais qui l'a
  reçu une fois peut s'en servir **hors de GEVES** jusqu'à ce qu'il soit
  renouvelé. D'où **deux** parades, et il faut les deux :
  - **le renouvellement d'office au retrait d'une personne associée**
    (`ProjectController::renouvelerLesLiens()`, appelé par `updateTask`) - sans
    lui, retirer quelqu'un d'une tâche ne lui retirait pas le fichier ;
  - **le bouton « Renouveler le lien d'édition »** sur la page du document,
    réservé à l'administrateur, pour ce qu'aucune règle ne couvre : un lien
    recopié dans un courriel, un poste perdu.
  ⚠️ Le renouvellement **vérifie le nouveau lien AVANT de couper l'ancien** :
  l'ordre inverse laisserait toute l'équipe devant un document inaccessible.
  ⚠️ Et il est fait **DANS la requête, pas en file** : un renouvellement confié à
  une file qui ne tourne pas laisserait l'ancien jeton vivant sans que personne
  ne le sache - exactement le défaut qu'on vient de fermer. Le prix est l'attente
  au retrait d'un collaborateur, bornée par `MAX_PAR_TACHE`.
- ⚠️⚠️ **SUPPRIMER UNE TÂCHE EMPORTE SES FICHIERS ET SON DOSSIER**
  (`ProjectController::oublierLesFichiers()`). La clé étrangère est en **cascade** :
  les lignes de documents partent au niveau de la base, sans passer par le
  modèle. Sans ce passage, chaque DOCX, son dossier **et son lien d'édition**
  restaient vivants chez le prestataire, sans plus rien dans GEVES pour les
  retrouver - donc sans plus personne pour les révoquer.
- ⚠️ **Le fichier distant part AVANT la ligne** à la suppression d'un document :
  l'inverse laisserait un fichier que plus rien ne référence, lien d'édition
  compris. Le prix est qu'on ne supprime pas pendant une panne de DocSpace, et
  l'écran le **dit** au lieu de rendre une page d'erreur.
- ⚠️ **L'adresse de téléchargement rendue par le prestataire est BORNÉE à son
  hôte** (`TaskDocSpace::downloadUrl()`) : suivie sans contrôle, elle ferait de
  GEVES un relais vers n'importe quel hôte, et son contenu ressortirait au
  demandeur sous les traits d'un .docx. Cas de garde, sabotage vérifié.
- ⚠️ **Un import est jugé sur son CONTENU** (`TaskDocSpace::validFile()`) :
  l'extension et le type MIME viennent du navigateur. On ouvre l'archive, on y
  cherche la pièce propre au format, et on borne le nombre d'entrées comme la
  taille réclamée au dépliage - une archive minuscule qui se déplie en centaines
  de mégaoctets remplirait le disque du prestataire.
- ⚠️ **Renommer dans GEVES renomme chez le prestataire** (au mieux, journalisé
  en cas de refus) : sans ce rappel, on chercherait « Note de cadrage » dans une
  salle qui affiche encore l'ancien titre. Le titre de GEVES fait foi.

#### Sans DocSpace, il n'y a pas de document de tâche

⚠️⚠️ **C'EST UNE EXCEPTION ASSUMÉE À LA DÉGRADATION GRACIEUSE DU PROJET**
(21/10/2026). Partout ailleurs - Turnstile, LiveKit, FedaPay - l'absence de clés
laisse la fonctionnalité debout, amputée de ce que le service apportait. Ici il
n'y a rien à laisser debout : le document **est** un fichier chez le prestataire.
Les deux boutons du panneau sont donc **désactivés** tant que DocSpace n'est pas
configuré, et le serveur refuse avec un message qui nomme ce qui manque. Ne pas
« rétablir » un repli local : c'est précisément le chemin qui a été retiré.

#### L'ancien éditeur texte (Tiptap + Yjs) a été RETIRÉ

⚠️⚠️ **DÉCISION DU 21/10/2026.** Un document de tâche s'écrivait en HTML dans un
éditeur maison, avec fusion CRDT (Yjs) et frappes portées d'un navigateur à
l'autre par événements client. L'arrivée des vrais DOCX/XLSX en a fait un second
éditeur pour le même objet ; c'est celui-là qui part.

- **Ce qui a disparu** : `resources/js/task-document*.js`, son CSS, son entrée
  Vite, les points d'entrée `state` / `save` / `versions.store` /
  `versions.show`, et avec eux le refus de concurrence par `revision` et le
  versement espacé des versions. Le build passe de 50 s à 5 s.
- ⚠️⚠️ **MAIS LES PAQUETS NPM SONT TOUJOURS DÉCLARÉS** (`@tiptap/*`, `yjs`,
  `y-prosemirror`, `y-protocols`), et il ne faut PAS les retirer à la main de
  `package.json` : le déploiement passe par **`npm ci`**, qui **refuse** un
  `package-lock.json` désaccordé du manifeste. Les retirer sans régénérer le
  verrou casserait la mise en ligne. Un seul geste, quand le réseau le permet :
  `npm uninstall @tiptap/core @tiptap/extension-collaboration @tiptap/extension-collaboration-caret @tiptap/pm @tiptap/starter-kit y-prosemirror y-protocols yjs`.
  Plus rien ne les importe entre-temps, donc ils ne pèsent sur aucun paquet servi.
- ⚠️⚠️ **CE QUI RESTE, ET POURQUOI : LES ANCIENS DOCUMENTS SE RELISENT.** Leur
  page les rend **en lecture seule**, contenu courant **et versions** comprises -
  un texte qu'on cesse d'afficher est un texte perdu, et c'est en production
  qu'on s'en apercevrait (même leçon que les fichiers « camera-ready », dont la
  migration continue de suivre les dépôts après le retrait du processus). Le HTML
  est assaini à l'AFFICHAGE par **`App\Support\TaskDocumentHtml`**, qui reste
  pour cette seule raison : il a été écrit par des rédacteurs et il est relu dans
  le back-office.
- ⚠️ **Les colonnes `html`, `state`, `revision` et `file_path` ne sont plus
  ALIMENTÉES**, et la table des versions non plus. On ne les retire pas tant
  qu'une installation peut en porter ; les migrations `2026_10_20_000300` à
  `000500` sont désormais **rejouables** (gardes `Schema::hasColumn`).
- ⚠️ **`ProjectTaskDocument::isOfficeFile()` est le SEUL prédicat** : il n'existe
  plus de fichier local, donc « fichier Office » et « fichier chez DocSpace » ne
  sont plus deux questions. Faux pour un ancien document texte.
- ⚠️⚠️ **LA COUCHE TEMPS RÉEL EST CONSERVÉE EN RÉSERVE, ET C'EST UNE DÉCISION,
  PAS UN OUBLI.** `App\Support\Realtime`, le canal `ukc.doc.{document}` de
  `routes/channels.php`, `config/broadcasting.php`, l'écran **Réglages ›
  Intégrations** et les paquets `laravel-echo` / `pusher-js` restent en place
  avec les identifiants PieSocket déjà réglés, pour un prochain usage (présence,
  notifications en direct). Elle n'a **aucun consommateur aujourd'hui** : un cas
  de garde la maintient correcte en attendant (secret jamais sorti du serveur,
  trois identifiants exigés, nom de canal à définition unique, autorisation du
  canal), parce qu'un socle qu'on garde sans l'éprouver se périme, et qu'on le
  découvrirait le jour où l'on s'en sert.
- ⚠️ **Le pont « ONLYOFFICE Docs » auto-hébergé a été retiré au même moment** :
  il n'avait plus aucun appelant. `App\Support\TaskOffice`,
  `OfficeDocumentController` et ses **deux routes publiques**
  (`/_office/documents/{document}/fichier`, `/webhooks/onlyoffice/{document}`)
  ne valaient que pour un document à `file_path` local, qu'aucun chemin de
  création ne posait plus. Seule sa validation OOXML a survécu, dans
  `TaskDocSpace::validFile()`. Un cas de garde vérifie que ces routes **n'existent
  plus** : une route laissée sans écran est une seconde porte que personne ne
  surveille.

#### Ce que l'écran a appris (21/10/2026)

Les pages ont été ouvertes sur **gesco.test** (le navigateur intégré refusait
`geves.test`), contre la **vraie salle DocSpace** et de vrais fichiers.

- ⚠️⚠️ **LA CHAÎNE EST CONFIRMÉE DE BOUT EN BOUT, MAIS PAS ENTIÈREMENT.** Un DOCX
  de la base s'ouvre pour de bon : le jeton enregistré est accepté, le SDK monte
  son cadre dans la page et `onAppReady` passe l'état à « Éditeur prêt ». Restent
  **non confirmées** la *création* d'un lien (les documents essayés en portaient
  déjà un) et l'import par morceaux au-delà de 10 Mo.
- ⚠️⚠️ **UN LIEN DE PARTAGE EST ANONYME : L'ÉDITEUR RÉCLAMAIT UN NOM À CHAQUE
  OUVERTURE.** Une boîte « Entrez un nom à utiliser pour la collaboration »
  barrait le document, alors que GEVES sait qui vient d'entrer. On la coupe et on
  donne le nom nous-mêmes, par `editorCustomization.anonymous`
  (`request: false`, `label` = le nom d'affichage du compte) dans
  `docspaceConfig`. **Vérifié à l'écran** : la boîte ne revient plus et l'éditeur
  s'ouvre directement en mode Édition.
  ⚠️ **Ce nom est DÉCLARATIF, ce n'est pas une identification** : le prestataire
  ne voit toujours qu'un porteur de jeton. C'est GEVES qui tient l'accès, et
  c'est le renouvellement du lien qui le reprend.
- ⚠️⚠️ **DEUX PIÈGES MAISON, PAYÉS UNE FOIS DE PLUS ICI**, tous deux invisibles à
  la lecture du code et mesurés dans le navigateur :
  - **Le titre d'une carte part à l'autre bout.** `.admin-page .ukc-card h2` est
    en `space-between` **pour le back-office**, où une action vit à droite du
    titre ; ces cartes n'en ont aucune, et l'icône restait seule à gauche pendant
    que le mot filait à droite. Mesuré : « Contenu » commençait à **1077 px dans
    une carte de 1152**. Le remède du projet n'est pas une guerre de spécificité
    (`.admin-page .ukc-card h2` pèse (0,2,1) et gagne) mais le **`<span>`**
    qu'attend `.admin-page .ukc-card h2 > span`. Les cinq titres sont repassés
    à 19 px.
  - **Le lien d'un ancien document n'était plus un lien.** `.ukc-scope
    a:not(.ukc-btn) { color: inherit }` pèse (0,2,1) - `:not()` compte son
    argument - et bat un sélecteur à une classe : mesuré, le lien sortait
    **exactement de la couleur du texte, sans soulignement**, sur la seule page
    qui existe pour relire ce qui a été écrit. On nomme le bloc ET l'élément,
    soit (0,3,1). Contraste repassé à 9,16:1.
- **Mesuré à 375 px**, sur les deux pages : aucun débordement horizontal
  (`scrollWidth` = `clientWidth` = 375 ; ⚠️ `body` vaut 417, soit 375/0,9, à
  cause de `html.ukc-dense`), et la grille des détails repasse bien sur une
  colonne.
- ⚠️ **Sur une fenêtre basse, le bandeau d'en-tête sort de l'écran** : le cadre
  de l'éditeur a un plancher de 720 px, l'en-tête collant en prend 71, et le
  cadre tire le défilement à lui en prenant le focus. Ce n'est pas un défaut -
  un éditeur de document ne tient pas dans 400 px - mais l'état et le bouton
  « Plein écran » demandent alors de remonter.

#### Pièges déjà payés

- ⚠️⚠️ **LE DOCUMENT EST IMBRIQUÉ SOUS SA TÂCHE**
  (`/projet/taches/{task}/documents/{document}`), et ce n'est pas cosmétique :
  `edition.cap:project` rattache la requête à son événement en lisant `event_id`
  sur le **premier modèle lié** de la route. Un document n'en porte pas - une
  route `/projet/documents/{document}` tomberait en **403 pour tout le monde**
  sauf le super-administrateur. `scopeBindings()` exige en plus que le document
  appartienne à **cette** tâche : sans lui, une tâche accessible plus
  l'identifiant d'un document d'un autre événement ouvriraient le second.
- ⚠️⚠️ **LA FORME DES RÉPONSES DE DOCSPACE RESTE EN PARTIE INCONFIRMÉE**
  (l'ouverture d'un fichier, elle, est éprouvée - voir ci-dessus ; la
  **création** d'un lien et l'import par morceaux ne le sont pas). Le jeton d'un
  lien se lit donc sous quatre formes connues
  (`TaskDocSpace::linkEntry()`) : sous `sharedLink`, sous `sharedTo`, à la
  racine, ou à défaut dans le paramètre `share` de l'adresse de partage -
  `sharedTo.shareLink` est tantôt un objet, tantôt la **chaîne** de l'URL, et
  indexer une chaîne par un nom ne rend rien. Toute forme inconnue échoue
  **FERMÉE** : on ne devine pas un jeton. Un jeu de données éprouve les cinq cas.
- ⚠️ **`App\Support\Html` ne conviendrait pas** pour l'assainissement : sa liste
  blanche sert les **résumés**, déposés par des milliers de personnes du dehors -
  l'élargir élargirait leur surface. `TaskDocumentHtml` **marche dans l'arbre
  DOM** : dès qu'un attribut est autorisé (`href`), un filtre par expression
  régulière devient une liste noire, donc faux par construction. Schémas d'URL en
  liste blanche - `javascript:` n'y est pas, sans quoi « cliquer sur le lien »
  exécuterait le code d'un ancien rédacteur chez qui relit la page. Une balise
  refusée est **dépliée**, pas supprimée : son texte est le travail de quelqu'un.
- ⚠️ **Entrée Vite SÉPARÉE** (`resources/css/task-office.css`) : tout le
  back-office et tout l'espace participant la téléchargeraient depuis `app.js`
  pour une page qu'ils n'ouvrent pas.
- ⚠️ **Le choix des personnes associées est une rangée de pastilles**, pas un
  `<select multiple>` : ce dernier oblige à maintenir Ctrl enfoncé, geste que
  presque personne ne connaît et qui efface toute la sélection quand on le rate.
- ⚠️ **Piège Pest rencontré ici : `Http::fake()` AJOUTE ses motifs** au lieu de
  les remplacer. Un appel sans argument pose un fourre-tout `*` qui **coiffe**
  tout ce qu'on enregistre ensuite, et les appels suivants reçoivent une réponse
  vide - donc « Réponse inattendue de DocSpace » pour un motif pourtant bien
  écrit. Pour n'attendre aucun appel, c'est `Http::preventStrayRequests()`.

Tests : `tests/Feature/DocumentDeTacheTest.php` (20 cas : droits, cloisonnement,
anciens documents en lecture, couche temps réel en réserve, assainissement) et
`tests/Feature/DocumentBureautiqueDeTacheTest.php` (26 cas, **6 sabotages
vérifiés** : chiffrement du jeton, jeton refusé aux lecteurs, adresse de
téléchargement bornée, aucun appel avant la garde des liens, et l'ordre
vérification-puis-révocation, et le nom donné à l'éditeur).

### Le registre des participants est une liste d'OBJETS VALEUR (2026-10-27)

⚠️⚠️ **`EventParticipantRoster::forEvent()` rend `Collection<int, RosterRow>`,
et c'est le remède à une erreur PHPStan tenace.** La forme d'avant - une
`Collection` dont le `TValue` était un **tableau-forme à champ union**
(`participationMode: string|null`…) - déclenchait un faux positif de covariance
(`Collection` n'est pas covariante) : le message annonçait « devrait renvoyer X
mais renvoie X », deux types identiques. Ni le passage par tableau nommé ni le
typage de `createdAt` ne l'avaient levé. Une classe à **une seule identité** pour
`TValue` écarte le problème à la racine.

- ⚠️ **C'est l'INVERSE du choix fait pour les destinataires de badge**
  (`BadgeCatalog`, plus haut), où le remède était des **tableaux** à champs tous
  d'un type simple. Les deux soignent la même non-covariance : objet valeur quand
  un champ est à type union (ici), tableau quand aucun ne l'est. Ne pas « aligner »
  l'un sur l'autre.
- **`App\Support\RosterRow`** = objet à propriétés publiques `readonly` typées.
  Les propriétés gardent `Collection::where()` / `whereIn()` / `firstWhere()` /
  `pluck()` opérants (ils lisent l'objet via `data_get`), donc les agrégats du
  contrôleur (`$all->where('paid', true)`…) n'ont pas bougé.
- ⚠️ **Les consommateurs lisent l'objet en PROPRIÉTÉ, jamais en tableau** :
  `RosterRow` n'implémente **pas** `ArrayAccess` (ce serait une béquille). Les
  fermetures du contrôleur sont typées `RosterRow` (plus `array`), la vue
  `admin/participants/index.blade.php` écrit `$person->role`, et les cas
  d'`AdminParticipantsTest` aussi. Un `firstWhere('email', …)` rend donc
  `RosterRow|null`.
- Gardes : `AdminParticipantsTest` (déjà en place) exerce le registre **et** le
  rendu de la page d'index.

### Architecture & sécurité

- **RBAC à deux niveaux** : staff `User` global (spatie/permission) vs `Participant`
  **par édition** (capacités = fonction affectée, middleware `edition.cap` ; l'admin est
  super-admin). Toujours vérifier la capacité côté serveur.
- Multi-guard : **`web`** (back-office) et **`account`** (participants).
- Logique métier réutilisable → une **Action** dans `app/Actions` (testable), pas dans
  le composant Livewire. Gardes d'autorisation dans `mount()` (`abort_unless`).
- Écritures multi-étapes clés sous `DB::transaction` (paiement anti-doublon avec
  `lockForUpdate`).
- ⚠️ **Un cast et son annotation vont par PAIRE.** Un `'meta' => 'array'` dans `casts()`
  ne suffit pas à l'analyse statique : sans `@property array<string, mixed>|null $meta`
  sur le modèle, elle lit le SCHÉMA (une colonne `json` est un `text`, donc une chaîne) et
  refuse `$modele->meta['cle']`, pourtant valide à l'exécution. Tous les modèles du projet
  annotent leurs colonnes castées (cf. `Attestation`).
- **IA** : couche `App\AI` (contrat `AiProvider`, `AiManager` = résolution + failover,
  `AiRouter` = routage fast/deep). Le copilote back-office exécute de vraies actions
  (`App\AI\Copilot`) **après autorisation explicite** + capacité RBAC revérifiée.
  - ⚠️ **Copilote admin (« Marc ») = UN SEUL chemin : l'action exécutable.** Le
    mécanisme de « proposition » descriptive (boutons *Approuver / Modifier avant /
    Refuser* + route `admin.ai.decide` + clé `proposal` dans la réponse de `AskAdmin`)
    a été **retiré** : approuver une proposition ne faisait que journaliser une ligne,
    sans rien exécuter - un cul-de-sac incompris. Désormais `AskAdmin::handle` renvoie
    `{ answer, action }` (plus de `proposal`), et le prompt impose à Marc : dès que la
    demande correspond à une action de `CopilotActions`, il **propose cette action**
    (carte *Autoriser et exécuter* → `admin.ai.execute`, qui exécute réellement côté
    serveur) au lieu de décrire une démarche manuelle ; sinon il répond **en texte**
    (marche à suivre, onglet concerné), sans faux bouton à valider. Ne pas réintroduire
    la « proposition » : un conseil se donne dans le texte, une opération s'exécute via
    une action. Tests : `AdminAiAssistantTest`, `CopilotActionsTest`.
  - ⚠️ **Cohérence des nombres (prompt `AskAdmin`)** : quand Marc propose une action, le
    chiffre qui fait foi est le **compte réel de l'action** (son « état actuel »), pas un
    sous-total recalculé par le modèle. Le prompt lui interdit de **fabriquer un
    sous-découpage** qu'il ne peut pas justifier exactement (« N ont une communication
    refusée », listes nominatives de « ceux qui… ») - c'est là qu'apparaissent les comptes
    faux (nombre annoncé ≠ noms cités). Garde-fou de prompt (non déterministe) : le test
    `AdminAiAssistantTest` vérifie seulement qu'il est **injecté** dans le prompt.

### Contexte des assistants IA : programme, séances, salles, créneaux

Ce que chaque assistant « sait » vient de blocs de contexte injectés dans son prompt.
Sources, pour ne pas les redéclarer à chaque fois :

- **`App\Support\EventKnowledge::brief($event)`** = base de connaissance **publique**
  partagée (identité, axes, échéances, règles, tarifs, pratique, **programme**, contact,
  FAQ). Lue par l'assistante **Gesco** (`AskGesco`) ET le **copilote admin** (`AskAdmin`).
- **`App\Support\ProgramKnowledge`** (⚠️ même namespace `App\Support`) :
  - **`overview($event)`** = vue d'ensemble PUBLIQUE des séances (par jour : type,
    libellé, horaire, salle, modérateur/rapporteur, nb de communications). **Incluse dans
    `EventKnowledge::brief`** → les deux assistants l'ont.
  - **`planning($event, $max=80)`** = planning DÉTAILLÉ des créneaux de communication
    (horaire, salle, titre + réf + auteur, drapeaux REPROGRAMMÉ / passage constaté).
    **Réservé au copilote admin** (`AskAdmin` l'ajoute aux « CHIFFRES RÉELS ») - ce sont
    des données de travail, pas de l'info publique. Borné pour tenir dans le budget de
    contexte. ⚠️ Nommer les auteurs via `data_get($slot,'submission.participant.user.full_name')`
    (relations traitées non-nulles par Larastan → pas de `?->` à gauche d'un `??`).
- **Assistante publique - programme PERSONNEL** (`GescoController::scheduleContext`) :
  pour un participant CONNECTÉ, ses créneaux de passage (« quand/où je communique »,
  séance, salle, horaire), + le passage constaté, + **la reprogrammation** (avant →
  après, et quand). Le prompt de Gesco l'autorise explicitement à répondre là-dessus.
  ⚠️ Jamais les créneaux d'un AUTRE participant (filtré sur ses `participant_id`).
- **Assistante publique - ENGAGEMENTS personnels** (`GescoController::engagementContext`) :
  toujours pour le participant CONNECTÉ, ses **formations** suivies (titre, date, réglée
  ou non), ses **rôles de séance** (modérateur / rapporteur, rapprochés par e-mail), ses
  **relectures** confiées (à rendre / en retard, `reviewer_id` = id Participant), ses
  **réclamations / incidents** déposés (référence + état). Même garde d'étanchéité : ne
  lit QUE les données du compte (par `user_id` / e-mail / ses `participant_id`).
- **Copilote admin - ANNUAIRE NOMINATIF** (`App\Support\AdminDirectory::digest`, injecté
  dans `AskAdmin` uniquement) : noms, prénoms et **e-mails** des participants (grade, rôle,
  institution, payé/à régler), des **paiements** (reçu, payeur + e-mail, montant, méthode,
  statut) et des **auteurs correspondants** (réf, titre, e-mail, axe, statut). Réutilise les
  lignes d'export d'`EventReport` (source unique). ⚠️ **JAMAIS côté Gesco** (données de
  tiers) ; le prompt admin autorise explicitement à les communiquer DANS le périmètre de
  fonction et du colloque. ⚠️ **Borné** (participants 200, paiements 120, auteurs 200 ; le
  prompt est reconstruit à chaque message) : au-delà, renvoi vers les filtres/export des
  pages dédiées plutôt que gonfler le contexte. Test : `ProgramKnowledgeTest` (présent côté
  admin, absent côté Gesco).
- **Copilote admin - ANNUAIRE DES COMPTES** (`App\Support\UserDirectory::digest`, injecté
  dans `AskAdmin` **hors** du bloc `if ($event)` : un compte n'appartient pas à un
  colloque). Quatre blocs : le **personnel** (nom, e-mail, niveau d'accès, rôles UKC, état,
  dernière connexion + IP), la **répartition** (total, actifs, désactivés, non vérifiés,
  jamais connectés, par rôle, par niveau), les **derniers comptes créés** et les **comptes
  non vérifiés ou désactivés**.
  - ⚠️ **La porte est la permission `users_roles`**, celle du middleware
    `can:users_roles` qui garde `admin/utilisateurs` - **pas** un niveau d'accès.
    `AskAdmin` est joignable par tout le staff ayant « Tableau de bord » (relecteur,
    secrétariat, comptabilité) ; et un **administrateur de colloque** sans cette permission
    est en 403 sur la page. L'assistant ne doit ouvrir aucune porte que l'interface tient
    fermée. Pour l'ouvrir à quelqu'un : cocher la permission dans la **matrice d'accès**,
    rien à toucher dans le code.
  - ⚠️ **La garde vit DANS `UserDirectory`**, pas chez l'appelant : `digest()` et
    `resume()` rendent une chaîne vide à qui n'y a pas droit. Une garde posée au point
    d'appel finit oubliée au premier déplacement de bloc.
  - ⚠️ **Rien n'est même MENTIONNÉ** à qui n'y a pas droit : décrire à un modèle une donnée
    qu'on lui refuse, c'est l'inviter à la deviner.
  - ⚠️ **Marc lit, il n'écrit pas** : aucune création, modification ni désactivation de
    compte ; le prompt le renvoie vers l'onglet Utilisateurs & rôles.
  - **Marc n'en dit ni plus ni moins que la page** : le contenu est calqué sur les colonnes
    d'`admin/utilisateurs` et ses compteurs. Si une donnée n'est pas sur la page, elle n'est
    pas dans l'annuaire. ⚠️ Borné (60 comptes de personnel, 25 par liste).
  - Tests : `AnnuaireComptesTest` (servi à l'habilité, vide pour le staff ordinaire ET pour
    un admin de colloque sans la permission, suit la matrice d'accès, borné, présent/absent
    du prompt réel, cité nulle part ailleurs qu'`AskAdmin`).
- **Copilote admin - pilotage élargi** (`AskAdmin`, bloc « CHIFFRES RÉELS ») : en plus des
  soumissions/paiements/recettes, il reçoit les **relectures** (`EventReport::reviewStats`),
  les **rapports de séance** (validés / attendus, `SessionReport` + `counts()`), les
  **formations** (si `Formation::offeredIn`, inscriptions réglées), les **réclamations
  ouvertes** et le **projet EN RETARD** (`ProjectMilestone`/`ProjectTask::isOverdue()`,
  jalons + tâches dont l'échéance est dépassée). Le prompt cadre déjà la réponse sur la
  FONCTION de l'utilisateur (un comptable reste sur les finances) : enrichir le contexte
  ne contourne pas ce périmètre. ⚠️ Réutiliser `EventReport` (source unique des agrégats),
  ne pas recompter à la main.
- **Assistante publique - MONTANT dû** (`GescoController::paymentDueContext`) : pour chaque
  événement où le participant a une communication acceptée non réglée, le **montant exact**
  (résolu depuis son grade par `Fees::resolveAmount`, repli `highestAmount` - EXACTEMENT
  comme la page de paiement), le **tarif applicable** et l'échéance pratique (début de
  l'événement, `date_start` - il n'y a pas de colonne d'échéance de paiement dédiée). Gesco
  ne disait auparavant que « à régler » sans chiffre.
- **Historique de reprogrammation = `slots.schedule_log`** (JSON, migration
  `2026_10_06_000100`). Capturé **automatiquement** par l'évènement `Slot::updated` :
  dès qu'un créneau portant une communication (`submission_id > 0`) et **déjà horodaté**
  voit son horaire, sa salle ou sa séance changer, une entrée `{at, from, to}` est
  ajoutée (écriture `saveQuietly`, 10 max). ⚠️ Un **placement initial** (pas d'horaire
  précédent) n'est PAS une reprogrammation. ⚠️ Capture dans le **modèle**, pas dans les
  contrôleurs → toute modif (manuelle, glisser-déposer, affectation auto) est tracée ;
  une mise à jour en **masse** par le query builder (`whereIn()->update()`) ne déclenche
  pas l'évènement (cas marginal : attribution de salle en lot). Helpers
  `Slot::wasRescheduled()` / `lastReschedule()`. Tests : `ProgramKnowledgeTest`.
- **Notification PROACTIVE de reprogrammation** (`App\Actions\NotifySlotRescheduled`,
  kind **`schedule_change`**, in-app + e-mail générique selon préférence, icône
  `calendar`, URL vers la fiche de la communication). ⚠️ **Jamais déclenchée depuis
  l'évènement du modèle** (le seed de 200 communications et les rejeux spammeraient -
  même piège que `LinkSubmissionAuthors`) : l'évènement pose seulement le **drapeau
  transitoire `Slot::$rescheduledNow`**, et c'est le contrôleur déclenché par un humain
  (`ProgramController::updateSlot`) qui appelle l'action. L'action est **sans effet** si
  le drapeau est faux (placement initial, pas un déplacement) ou si l'auteur n'a pas de
  compte. L'affectation auto (`autoAssignSlots`) ne fait que du **placement initial** et
  ne notifie donc pas. Pour notifier depuis un nouveau chemin de reprogrammation,
  rappeler `NotifySlotRescheduled` après l'`update()` (ne pas recâbler l'évènement).

### Tests

- `RefreshDatabase` global (base `:memory:`). Fabriques partagées dans `tests/Pest.php`
  (`openEdition()`, `verifiedAccount()`, `adminStaff()`, `seededSubmission()`) - ne pas
  les redéclarer dans un fichier de test.
- **Ajouter des tests Pest** pour toute nouvelle fonctionnalité. Après suppression d'un
  composant, régénérer la baseline PHPStan.
- ⚠️ **Un cas éprouve le CODE, jamais le POSTE.** Deux cas exigeaient
  `toEndWith('ffmpeg.exe')` sur la résolution de FFmpeg : ils tenaient seulement sur une
  machine où FFmpeg est installé **par WinGet** (paquet `Gyan.FFmpeg`). Sans lui - en
  intégration continue, sous Linux, ou avec un FFmpeg posé par Chocolatey, Scoop ou à la
  main - le résolveur rend `ffmpeg` et laisse le PATH trancher, **ce qui est le
  comportement voulu**, et le cas échouait quand même. On fabrique donc l'arborescence
  qu'on veut éprouver (fichiers temporaires, `putenv('LOCALAPPDATA=…')`) au lieu de
  supposer celle du poste. Un cas propre à une plateforme se marque `->onlyOnWindows()`.
- ⚠️ **Un cas « réparé » qui ne mord plus n'est pas réparé.** Avant de clore une
  correction de test, le casser volontairement et vérifier qu'il échoue : c'est ainsi
  qu'on a su que l'exemption des séances d'horloge n'avait pas affaibli la garde des
  dates du seeder, et que le tri **naturel** des versions WinGet était bien couvert
  (trié comme des chaînes, le résolveur servait `7.0` au lieu de `10.0`).
  - ⚠️⚠️ **NE JAMAIS PASSER DE CODE PHP EN ARGUMENT DE `php.bat`.** `cmd`
    reparse l'argument : un `->` y devient une **redirection**, et le script de
    sabotage écrit son « ok » dans un fichier parasite (vu ici :
    `boolean('livretVisible')` apparu à la racine du dépôt) au lieu de
    substituer quoi que ce soit. Le sabotage ne s'applique pas, le cas passe au
    **rouge pour une autre raison**, et on croit la garde vérifiée - c'est
    arrivé, et le sabotage rejoué proprement ne mordait PAS. Les arguments
    **multi-lignes** sont aplatis de la même façon. Remède : écrire la
    substitution dans un **fichier JSON** (heredoc à délimiteur quoté) que le
    script PHP lit lui-même - aucun code sur la ligne de commande.
  - ⚠️ **Un sabotage doit CHANGER le fichier** : le harnais compare le md5 avant
    et après, et refuse de conclure si rien n'a bougé. Sans ce contrôle, un
    motif qui ne s'applique pas se lit comme une garde qui tient.
  - ⚠️ **Restaurer par une COPIE du fichier, jamais par `git checkout --`.** Le
    sabotage porte par définition sur du travail **pas encore commité** :
    `git checkout --` ne remet pas le fichier d'avant le sabotage, il le remet au
    dernier commit - donc il efface la correction qu'on est en train d'éprouver.
    Vu ici, sur la mise en page du parcours : le sabotage suivant s'est exécuté
    contre l'ancien code et a « réussi » pour la mauvaise raison. Copier le
    fichier de côté avant, le recopier après, et **vérifier la restauration au
    md5** - sans quoi on ne sait pas ce qu'on a repoussé.

## Pièges fréquents

Voir la liste complète en [§8 de PASSATION.md](PASSATION.md). Les plus courants :

- **Livewire 4** : ne pas nommer une action `transition` (réservé) ni une variable de
  vue `slots` (injectée par Livewire).
- ⚠️ **Une variable de `foreach` ÉCRASE une fermeture du même nom, et l'écrasement
  SURVIT à la boucle.** `DocumentController::livretViewData()` déclare
  `$a = fn (string $key) => LivretAppearance::get(...)`, puis une boucle plus bas
  parcourait les auteurs dans `$a`. Après elle, `$a('cover_logo')` appelait un
  tableau : « Array callback must have exactly two elements », et **l'aperçu du
  livret comme son PDF tombaient en 500 dès qu'une communication portait un
  auteur** - c'est-à-dire toujours. Une fermeture d'une lettre est une invitation
  à la collision : nommer les deux, et jamais pareil.
- **Colonnes `nullable` traitées comme « 0 »** (`slots.submission_id`, `revision_of`) :
  forcer `=> 0` à la création, un modèle fraîchement `create()` ne reflète pas les
  défauts SQL en mémoire.
- **Paiement en ligne** : le statut « payé » vient **toujours** d'une vérification
  serveur ou d'un webhook à signature vérifiée (fail-closed), jamais d'un paramètre
  d'URL de retour.
- **Ne jamais nommer l'agrégateur de paiement côté payeur** (FedaPay…) : les
  libellés de `PaymentGateway::label()` et les textes des pages de
  paiement restent génériques (« Mobile money / carte bancaire »). Le nom de
  l'agrégateur n'apparaît que dans la **configuration admin** de l'édition. Un test
  (`PaymentOperatorTest`) garde cette règle.
- **Nature d'un paiement** : `Payment::flow()` reste la valeur **technique**
  (`participation` par défaut, `formation`, `coauthor`) qui pilote la garde
  anti-double de `ConfirmPayment` - **ne pas la changer**. Côté affichage il n'y a
  que **deux natures** (`Payment::FLOWS`) : **`displayFlow()`** range le co-auteur
  avec la **participation** (il règle pour participer), la nuance n'apparaissant
  que dans `flowDetail()`. La page `admin/paiements` affiche une colonne **Objet**
  et un **filtre par nature** (Participation englobe les co-auteurs) ;
  les moyens sont regroupés (`Payment::methodGroups()`) pour ne plus mélanger
  encaissement manuel et parcours en ligne.
- **Moyen de paiement** : ⚠️ **la passerelle choisie EST le moyen de paiement.**
  Prendre « en ligne », c'est aller sur la page du prestataire, qui propose ce
  que le compte marchand accepte - mobile money ET carte. On ne fait plus
  choisir d'opérateur chez nous (cf. la section « Le choix de l'opérateur a été
  retiré »), et **aucun `mode` ne part dans la requête**.
  `OnlineGateway::mobileOperators()` ne subsiste que pour **lire** le code que
  le prestataire rapporte sur un encaissement abouti.
- **Téléphone obligatoire à l'inscription ET au profil** (`account.register.store`,
  `account.profile.update`) : il sert
  d'identifiant fort pour le rapprochement (auteurs, formateurs) et pré-remplit la
  passerelle. Les tests qui postent le profil doivent l'inclure.
- **Identité venant d'un compte = jamais la saisie libre** : dès qu'un identifiant
  fort (e-mail **ou** téléphone) correspond à un `User`, le serveur **impose** le
  nom/e-mail/téléphone du compte (co-auteurs via `AuthorMatcher`, formateurs via
  `Admin\FormationController::trainerAttributes()`). Cela évite les fautes de nom et
  l'usage de l'e-mail d'un tiers. Le pré-remplissage côté client n'est qu'un confort.
- ⚠️ **« A payé » = paiement de PARTICIPATION** : un paiement de formation est
  rattaché au **même `Participant`**, donc un `payments()->where('status','paid')`
  brut le compte et débloque à tort badge, attestations et accès. Toujours passer
  par **`Participant::participationPayments()`** (ou le scope
  **`Payment::forParticipation()`**), qui exclut `meta.flow = 'formation'` et
  **inclut le co-auteur** (il règle bien pour participer). Les totaux **financiers**
  (recette encaissée) gardent au contraire tous les paiements.
  **`PaymentRightsGuardTest` scanne `app/` et `resources/views/`** et échoue sur tout
  contrôle « payé » non filtré (il donne fichier:ligne). Un total financier légitime
  s'ajoute à la liste `$allowed` de ce test, avec sa justification.
- **Avant d'engager un paiement** : le **profil doit être complet** -
  **`User::profileComplete()`** (prénom, nom, **téléphone**, affiliation + e-mail
  vérifié ; `missingProfileFields()` donne les libellés manquants). Les coordonnées
  envoyées à la passerelle passent par **`Payment::payerContact()`** (participant,
  **repli sur le compte unifié**) pour **pré-remplir** sa page - le téléphone est
  indispensable au mobile money.
- **`@php(...)`/`@json([...])` multi-ligne** dans une vue peut casser le compilateur
  Blade - préparer la donnée dans le contrôleur/composant.
- ⚠️ **`@php(...)` en ligne AVANT un bloc `@php … @endphp`** dans la même vue :
  Blade traite les blocs bruts en premier et avale le `@php(` en ligne comme
  ouverture du bloc → il compile `<?php(` **non terminé**, tout ce qui suit
  devient du PHP brut (les `@if` ne sont plus compilés) et la page tombe sur un
  « Undefined variable » à des dizaines de lignes de là. Dans une vue qui
  contient déjà un bloc `@php … @endphp`, **utiliser la forme bloc partout**
  (`@php $x = …; @endphp`). L'inverse (en ligne *après* le bloc) fonctionne.
- **Directive Blade collée à un mot** : `texte@if ($x)` **n'est pas compilé** (Blade
  exige un caractère non-alphanumérique avant `@`), mais le `@endif` l'est → `endif`
  orphelin et **fatal `unexpected token "elseif"`** plus loin dans la vue. Mettre une
  espace avant la directive, ou (mieux) sortir la condition dans une expression
  `{{ $x ? ... : ... }}`.
- **`settings.fees`** = **liste de `['label', 'amount', 'grades', 'default']`** : ⚠️ le
  **`label` est un intitulé libre** (« Doctorants et assimilés »), il ne sert PAS au
  rapprochement. C'est **`grades`** (liste de `ParticipantTypes::all()`) qui rattache
  la catégorie aux participants, et **`default`** qui désigne le tarif de repli.
  `Fees::resolveAmount()` cherche dans cet ordre : grades rattachés → libellé
  exactement égal au grade (grilles anciennes) → catégorie par défaut. Une catégorie
  sans grade ni défaut n'est jamais appliquée (l'écran d'édition le signale), et
  **sans AUCUNE catégorie par défaut, un grade non couvert tombe sur le montant le
  plus élevé** de la grille (repli `Fees::highestAmount()` chez tous les appelants) :
  un doctorant se retrouve facturé au tarif « Autres participants » sans que
  personne le voie. L'onglet « Soumissions & tarifs » le dit maintenant en clair.
  Ancienne forme `['label' => string, 'amount' => float]` (jamais un
  tableau associatif `grade => montant`). Toujours lire via **`App\Support\Fees::grid()`**
  / **`Fees::normalize()`** (qui ignorent les entrées malformées) - jamais l'accès brut,
  sinon `$fee['label']` sur une valeur scalaire fait planter la page publique en 500.
### ⚠️ Le tarif NE SE CHOISIT PAS, il se déduit du grade

C'est la règle du logiciel (`Fees::resolveAmount`) ; trois écrans disaient
l'inverse et ont été repris. ⚠️ **Elle souffre UNE exception, bornée : les
catégories HORS GRADE** (voir la section suivante) - et seulement celles que
l'organisation a marquées comme telles.

- **Page publique, barème des tarifs** : un bouton « Choisir » par carte et une
  pastille « Recommandé » posée sur la carte du MILIEU - un index, pas un
  conseil. ⚠️ La règle se lit **dans le barème lui-même** (les grades couverts y
  figurent) : le bandeau qui la répétait au-dessus a été retiré, il doublait ce
  que le barème montre déjà. Ne pas le réintroduire (un cas Pest le gèle). Le
  barème **renseigne** (qui est concerné, à quel prix), avec les grades couverts
  sur chaque ligne et **un seul** appel à l'action. ⚠️ Pas de phrase « tous les
  tarifs comprennent… » sous le barème : écrite en dur, elle affirmait la même
  chose pour TOUS les événements sans qu'aucun réglage ne la soutienne. Ce qui
  vaut pour un événement donné se dit dans la note de tarifs
  (`settings.fees_note`), que l'organisation écrit elle-même. Quand aucune catégorie ne déclare de grade, la page le dit au
  visiteur : le tarif lui sera confirmé à l'inscription - sinon il reste devant
  trois prix sans savoir lequel est le sien.
- **Co-auteurs** : voir la section suivante - ce parcours n'encaisse plus.
- ⚠️ **Rien de personnalisé sur la page publique** (pas de « votre tarif » pour
  qui est connecté) : elle doit rester identique pour tout le monde, donc
  cacheable en HTTP - cf. l'en-tête de `ColloqueController`.

### ⚠️ Page publique d'un événement : trois travers de mise en page (2026-10-11)

Tous les trois étaient invisibles en lisant le code et bien visibles à l'écran.
`tests/Feature/PageColloquePubliqueTest.php` les retient.

**1. Un barème est une LISTE, pas une grille de cartes.** La grille tarifaire
était `grid-template-columns: repeat(auto-fit, minmax(260px, 1fr))`. Mesuré sur
le colloque de l'eau (6 catégories, fenêtre de 1280px) :

- une rangée de 4 cartes puis une rangée **orpheline** de 2, la moitié droite
  vide ;
- des cartes hautes de 388px dont **194px de vide** dans la plus courte : une
  grille égalise les hauteurs de rangée, et les catégories couvrent de 1 à 6
  grades ;
- six montants à **six abscisses différentes**, donc incomparables - alors que
  comparer est la seule chose qu'on fasse devant un barème.

Une liste (`.ukc-fees` / `.ukc-fee`) n'a ni rangée orpheline (2 ou 12 catégories
tiennent pareil), ni vide à combler (chaque ligne fait sa hauteur), et elle
empile les montants dans une colonne unique. ⚠️ `font-variant-numeric:
tabular-nums` sur le montant, sinon les chiffres de « 100 000 » et de « 10 000 »
ne tombent pas les uns sous les autres. ⚠️ Les grades couverts tiennent sur UNE
ligne qui s'enroule : en puces, la catégorie qui en couvre six gonflait sa
rangée entière, et c'est ce gonflement qui creusait les voisines.

**2. `auto-fit` troue la dernière rangée.** Même cause pour les ateliers
disciplinaires (6 ateliers -> 4 + 2). `auto-fit` choisit un nombre de colonnes
sans savoir combien d'éléments il reste. Remplacé par un flux souple centré
(`display: flex; flex-wrap: wrap; justify-content: center`) avec un plafond de
largeur, pour que le reliquat se place au milieu sans s'étirer au double des
rangées du dessus. Un cas Pest interdit le retour d'`auto-fit` sur ces deux
blocs - **commentaires retirés avant lecture**, sinon la documentation de la
règle fait tomber la règle (déjà vu sur `--ukc-hero`).

**3. Un emplacement vide ne se montre pas au public.** Sans visuel téléversé, la
page affichait à la place de la bannière officielle un bloc coloré de 420px
intitulé « BANNIÈRE OFFICIELLE » qui répétait le titre et les dates déjà écrits
dans le bandeau, 400px plus haut. Aucun des deux colloques n'ayant téléversé de
visuel, **c'est ce substitut que voyait tout visiteur**. La section ne s'affiche
plus que s'il y a vraiment une image ; le réglage « afficher la bannière » reste
décisif par-dessus.

⚠️ **La page publique existe aussi en anglais**, et le sélecteur de langue est
dans sa propre barre de navigation. Douze chaînes n'avaient pas de traduction,
dont six dans le seul barème. Un cas Pest lit les `__()` de la vue et exige
leur clé dans `lang/en.json`. Les intitulés de catégorie et les noms de grades
restent en français : ce sont des **données** saisies par l'organisation, pas
des chaînes d'interface.

### Paiement SANS REDIRECTION : le formulaire s'ouvre sur notre page (2026-10-18)

Le payeur ne quitte plus GEVES : le formulaire du prestataire s'affiche dans une
fenêtre de la page, avec le montant et le colloque toujours sous les yeux. C'est
le **CheckoutJS** de FedaPay (`cdn.fedapay.com/checkout.js`), posé dans un
conteneur (`FedaPay.init({ container })`).

⚠️⚠️ **LE NAVIGATEUR NE DIT JAMAIS QU'UN PAIEMENT A RÉUSSI**, et c'est ici qu'on
pourrait l'oublier : le rappel de fin (`onComplete`) s'exécute **chez le
payeur**. La route **`payment.verify`** ne lit aucun statut de la requête - elle
interroge le prestataire, exactement comme le retour navigateur. Le navigateur
ne fait que **donner l'heure** (« regardez maintenant »), et c'est tout ce qu'on
lui accorde. Lire son verdict reviendrait à laisser le payeur écrire lui-même
qu'il a payé.

⚠️ **Si ce rappel n'arrive jamais** (onglet fermé, réseau coupé), le paiement se
confirme quand même : `payments:reconcile` repasse toutes les 10 minutes. Cette
route **accélère, elle ne décide pas** - ne jamais en faire le seul chemin.

⚠️ **La transaction est créée CÔTÉ SERVEUR**, et le formulaire ne fait que la
reprendre par son identifiant (`transaction: { id }`). Le montant et l'objet
sont les nôtres : laisser le formulaire les composer reviendrait à laisser le
payeur fixer ce qu'il règle.

⚠️ **La clé PUBLIQUE décide, et elle décide seule.** Renseignée
(`services.fedapay.public_key`, Réglages › Paiement, ou par événement), le
formulaire s'ouvre sur notre page ; vide, on repart en redirection. **Pas
d'interrupteur de plus** : il pourrait dire « activé » sans clé, et il faudrait
le tenir d'accord avec la réalité. ⚠️ C'est la clé **publique** - elle part dans
le navigateur, c'est son rôle ; la **secrète** signe les appels serveur et ne
doit jamais atteindre la page (un cas le garde).

⚠️ **La page hébergée reste accessible**, en pied de fenêtre. Si le script du
prestataire ne charge pas - réseau, blocage, coupure de son CDN - le payeur doit
pouvoir finir quand même. Un formulaire embarqué sans porte de sortie, c'est un
paiement perdu. Le message d'échec est **écrit dans la page** (donc traduit), pas
posé en `content:` dans la feuille de style.

⚠️ **UNE SEULE fenêtre par page** : `<x-ukc.pay-embed>` est posée une fois sur
`account/payment/pay` et une fois sur `account/formations/index` - jamais dans la
boucle des formations, qui en afficherait autant que de lignes, toutes sur la
même transaction. La charge est construite par **`App\Support\PaymentEmbed`**,
source unique des deux parcours.

⚠️⚠️ **LA ZONE DU FORMULAIRE DOIT POUVOIR SE COMPRIMER** (`min-height: 0`),
sinon elle pousse le pied hors du panneau - qui le rogne (`overflow: hidden`) -
et c'est le pied qui porte la **porte de sortie**. Mesuré deux fois, à deux
ans d'intervalle de code : d'abord sur 422px de haut, puis sur 800x460, où un
plancher de 62dvh posé sur la zone laissait 151px à un en-tête et un pied qui en
réclament 164. C'est la **zone** qui défile, pas la fenêtre.

⚠️⚠️ **LA FENÊTRE FAIT EXACTEMENT LA TAILLE DE SA CARTE : 700x542.** Sa carte est
figée (`width: 100%; max-width: 700px; margin: 0 auto`, relevé sur sa page), et
**tout ce que la fenêtre prend au-delà, il le comble en gris** - un gris qui
s'affiche chez nous. 940px lui laissaient deux bandes mortes de plus de cent
pixels, 790px une quarantaine, 700px aucune. Le cadre fait donc **542px de haut,
fixes** : plus haut, il montrerait du gris sous elle ; plus court, sa page
défilerait en dedans.

⚠️⚠️ **ET 700px NOUS PLACE SOUS SON SEUIL DE 768px, CE QUI RÈGLE LE HAUT.** Sa
page porte `d-md-flex d-block`. **Au-dessus de 768px**, elle centre sa carte à la
verticale (`align-items: center`) dans un bloc haut de `100vh` du cadre : dès que
le cadre est plus court qu'elle, le débordement se partage en haut ET en bas, et
le haut devient **inatteignable** - mesuré sur sa page, cadre de 480px, carte à
`top: -31px`, `scrollTop` bloqué à 0. Le payeur perdait l'en-tête de sa
transaction - marchand, identifiant - sans qu'aucune barre de défilement ne l'y
ramène. **En dessous de 768px**, elle repasse en `display: block` : la carte
commence EN HAUT, et son débordement redevient un défilement ordinaire. Le
rognage n'est alors plus possible du tout.

⚠️ **La hauteur du cadre ne s'exprime donc JAMAIS en `dvh`.** Le plancher a valu
`min(560px, 68dvh)` : sous 824px de fenêtre, c'est `68dvh` qui l'emportait - 491px
sur un écran de 722 - soit moins que sa carte, et c'est ce qui rognait le haut en
production. ⚠️ Sa carte s'allonge quand on la rétrécit : 542px à 700 et 686 de
large, **562px** à 620 et en dessous, le texte se repliant. En plein écran
(≤ 560px), le cadre prend toute la hauteur et c'est sa page qui défile.

⚠️ **Ses deux colonnes tiennent bien plus bas qu'on ne l'a cru** : mesurées côte à
côte à 686, 620, 575 et 520px. Ce qui tombait sous la ligne de flottaison tenait
à la HAUTEUR du cadre, pas à sa largeur - l'arbitrage « 790px entre deux écueils »
reposait sur une prémisse fausse.

⚠️⚠️ **DEUX MORCEAUX DE SA PAGE SONT DÉCOUPÉS DU CADRE** (`clip-path` sur
l'`iframe`). **Sa croix** (`.modal-close`, 32px posée à `top: -15px;
right: -15px`) appartient à SA fenêtre modale : embarquée, elle dépasse de sa
carte et laissait un quart de pastille blanche collé dans le coin, juste sous la
nôtre - deux croix pour une fenêtre, dont une qui n'est pas à nous. **Sa bande du
bas** (`.secured-by`, 22px) laisse voir le voile de sa page
(`body { background: rgba(0,0,0,.6) }`) : une barre grise sous un formulaire blanc
partout ailleurs.

⚠️⚠️ **ON DÉCOUPE LE CADRE, ON NE POSE PAS UN CACHE DESSUS.** Les pourcentages
d'un `clip-path` se comptent sur la largeur RÉELLE du cadre, qui perd 17px dès que
la zone sort sa barre de défilement : un cache ancré à droite se décalerait
d'autant et découvrirait la croix. ⚠️ Et un élément posé DANS la zone ne
survivrait pas - leur script y écrit `innerHTML`. ⚠️ Les 17x17 du coin sont la
marge droite de sa carte : son texte « MARCHAND » s'arrête à 684px sur 700, mesuré
au pixel près. ⚠️ **On ne raccourcit pas le cadre** pour supprimer sa bande : un
cadre plus court que sa carte lui ferait sortir sa propre barre de défilement. En
plein écran, seule la croix part - sa bande y suit le bas de SON contenu, et
découper le bas rognerait le formulaire.

⚠️ **Ses COINS laissent passer le même voile** : ses blocs blancs sont arrondis de
3.8px (`.modal-header` en haut, ses deux colonnes en bas), et en dehors de
l'arrondi le fond sombre reparaît - quatre petites échardes, dont une se voyait à
l'œil nu en haut à gauche, contre notre bandeau. D'où le **cran de 4px** à ce coin
et les **25px du bas** plutôt que 22 : ses coins bas s'arrêtent à 517px sur 542.
Le coin haut droit part déjà avec sa croix. Rang 100 (surface modale), au-dessus du chatbot (55) et des
slideovers (60) : un formulaire de paiement à moitié recouvert est un paiement
abandonné.

⚠️⚠️ **LE BANDEAU NE RÉPÈTE NI L'OBJET NI LE MONTANT**, et c'est un revirement
assumé. Il les a portés - « Frais de participation » et « 100 FCFA » sous un
surtitre *Paiement sécurisé* - parce qu'une fenêtre de paiement doit dire ce
qu'on engage. Sauf que **le formulaire du prestataire l'annonce déjà, et qu'il y
AJOUTE ses frais** : « 100 FCFA » chez nous, « PAYER 102 CFA » chez lui, deux
chiffres différents sur le même écran. Un seul vaut mieux que deux, et c'est le
sien qui sera débité. Il ne reste donc que le surtitre, la croix, et le pied.

⚠️ **Et le pied ne porte plus QUE la porte de sortie.** Il assurait aussi qu'on
ne quittait pas la plateforme ; depuis que le cadre du prestataire va bord à
bord et signe sa propre page, la phrase expliquait une inquiétude que l'écran ne
soulevait plus. Sous un formulaire de paiement, une ligne de moins est une
hésitation de moins - mais **jamais celle-là** : le lien vers la page hébergée
reste, et il reste seul.

⚠️ **Le titre portait le nom accessible de la fenêtre** (`aria-labelledby`) : en
le retirant, la boîte de dialogue serait devenue **anonyme** pour un lecteur
d'écran. Elle se nomme désormais elle-même (`aria-label`). Un attribut qui pointe
sur un élément disparu ne produit aucune erreur - il produit un silence.

⚠️ Le nom de la plateforme passe par le placeholder `:app`, jamais en dur.

⚠️⚠️ **UN PAIEMENT EN ATTENTE A PU ÊTRE OUVERT AVEC UNE AUTRE PASSERELLE.**
`Payment::firstOrCreate` ne pose `method`/`gateway` qu'à la **création** : qui
avait d'abord choisi « sur place », puis revenait régler en ligne, repartait sur
une ligne restée `manual`. Deux défauts d'un coup, tous deux silencieux, et
constatés sur un vrai paiement :

- **`payments:reconcile` ne repasse que par la passerelle enregistrée** : un
  règlement en ligne resté `manual` n'aurait jamais été revérifié - le filet de
  sécurité ne couvrait pas cette ligne ;
- la **comptabilité** annonçait un encaissement au guichet pour un paiement en
  ligne.

D'où le réalignement **avant** d'engager la passerelle. ⚠️ Et la
**référence** est abandonnée au passage, après journalisation dans le canal
`payments` : elle appartenait à l'autre prestataire, et la garder reviendrait à
lui demander des nouvelles d'une transaction qui n'est pas la sienne.

⚠️⚠️ **LE FORMULAIRE EMBARQUÉ NE DEVINE PAS LE PAYS, ET IL RETOMBE SUR LE
BÉNIN.** Il ne s'ouvre pas sur la page hébergée : `checkout.js` POSTE ses
options à `checkout.fedapay.com` et c'est tout ce qu'il sait. Mesuré en
production : un numéro **togolais** (+228 90…) arrivait sur « Mtn Bénin »,
drapeau béninois compris - le payeur devait corriger à la main ce que nous
savions déjà. On lui passe donc le **`customer`**, pays compris
(`phone_number.country`, option documentée de `FedaPay.init`).

⚠️⚠️ **ET CELA NE SUFFIT PAS : L'OPÉRATEUR RESTE SUR « MTN BÉNIN ».** Corrigé
ici le **31/10/2026**, capture à l'appui : nous envoyons bien
`phone_number.country = 'tg'`, leur formulaire **pré-remplit le numéro**
(`+22870323810`) - donc il lit bien notre `customer` - mais sa liste
« Choisissez votre opérateur » s'ouvre quand même sur **Mtn Bénin**. Le pays
gouverne le champ du numéro, **pas le choix de l'opérateur**. La phrase
ci-dessus laissait croire le contraire : elle décrivait l'intention, pas le
résultat mesuré.
⚠️ **Conséquence à ne pas sous-estimer** : un payeur qui ne change pas la liste
règle avec un opérateur qui n'est pas le sien, et le paiement échoue. Rien dans
notre code ne peut l'empêcher - la liste appartient à leur formulaire, dans un
cadre d'un autre domaine. À poser au prestataire ; en attendant, c'est une perte
de paiement possible à chaque règlement.

⚠️ **C'est le MÊME `customer` que celui de la transaction**, rendu par
`initiate()`, pas une seconde construction : deux assemblages se décaleraient, et
le formulaire finirait par contredire la transaction qu'il règle.

⚠️⚠️ **`mode` NE PRÉ-SÉLECTIONNE RIEN SUR LA PAGE DE PAIEMENT**, et c'est la
leçon la plus coûteuse de ce module - trois essais en conditions réelles pour
l'établir. Le champ est bien envoyé (mesuré en base : `meta.operator` posé,
`mode` dans la requête de création), et la page du prestataire affiche quand
même son sélecteur, sur **« Mtn Bénin »** par défaut.

⚠️ **`mode` ne sert qu'au PAIEMENT DIRECT**, celui qui se passe entièrement de
page : `POST /v1/{mode}` avec le jeton rendu par `POST /transactions/{id}/token`
(c'est le `sendNowWithToken($mode, $token)` de leur SDK). Le payeur reçoit alors
la demande d'approbation **sur son téléphone**, sans formulaire - donc sans
sélecteur d'opérateur et sans case « Je veux recevoir mon reçu par mail », qui
appartiennent tous deux à la page du prestataire et nous sont inaccessibles
(cadre d'un autre domaine).

### ⚠️⚠️ N'ESSAYEZ PAS D'IMPOSER L'OPÉRATEUR AU PRESTATAIRE (2026-10-18)

**`mode` ne pré-sélectionne RIEN sur la page de paiement.** C'est la leçon la
plus coûteuse de ce module : trois essais en conditions réelles pour l'établir.
Le champ part bien - mesuré en base (`meta.operator` posé) et dans la requête de
création - et la page du prestataire affiche quand même son sélecteur, sur
**« Mtn Bénin »** par défaut.

⚠️ `mode` ne sert qu'au **paiement direct**, celui qui se passe entièrement de
page : `POST /v1/{mode}` avec le jeton de `POST /transactions/{id}/token` (le
`sendNowWithToken($mode, $token)` de leur SDK). Le payeur reçoit alors la demande
**sur son téléphone**, sans aucun formulaire.

⚠️⚠️ **Ce parcours A ÉTÉ ÉCRIT, ÉPROUVÉ, PUIS RETIRÉ** (commits `eb74b470`,
`b4940f95`, `5d35acca` - à reprendre là si besoin). Éprouvé contre le compte de
production, le prestataire répond :

```
{"transaction":113755521,"operateur":"togocel","statut":400,
 "reponse":"{\"message\":\"Opération non autorisée\",\"errors\":{},\"model\":null}"}
```

**400 « Opération non autorisée »**. Le refus porte sur l'**autorisation**, pas
sur la requête - l'adresse, le verbe et le corps sont ceux de leur propre SDK.
C'est une demande à leur adresser, pas un correctif à écrire. Retiré à la demande
de l'équipe plutôt que laissé dormant : du code qu'aucun chemin n'emprunte se
périme sans qu'on s'en aperçoive.

⚠️⚠️ **CORRECTION DU 18/10/2026 : `POST /v1/{mode}` N'EST PAS UNE OPTION, C'EST
LE PAIEMENT.** On a cru un temps qu'il s'agissait d'un parcours « direct » en
supplément, à faire activer comme la carte, et que la page hébergée, elle,
fonctionnait. **C'est faux** : la lecture de leur propre `bundle` de paiement
montre que le bouton PAYER de leur page appelle exactement
`POST /v1/{mode}` avec `{token, phone_number, customer}` - et qu'il affiche en
rouge le `message` de l'API, mot pour mot. Le « Opération non autorisée » que
voit le payeur **est la même réponse** que celle de notre essai serveur. Aucune
de nos clés n'entre dans cet appel : il ne porte que le jeton.

⚠️ **Ce que les sondes établissent** (toutes en lecture seule, aucun paiement
engagé) :

- un **jeton invalide** donne « Le jeton de transaction est invalide » sur tous
  les modes, et un mode inventé donne 404 : le refus vient donc **après** le
  jeton, sur l'opération elle-même ;
- le **même jeton et le même mode** passent sans broncher sur
  `GET /v1/transactions/fees` (200, 4 F de frais, commission 3,5 %) : ni le
  jeton ni l'opérateur ne sont en cause ;
- l'historique (`GET /v1/transactions/search` - ⚠️ `GET /v1/transactions` est
  **obsolète** et répond 400) compte 63 transactions : la dernière encaissée est
  **112910339, togocel, 100 F, `transferred`, le 02/09/2026**, et `GET /v1/balances`
  porte toujours ce `togocel=100`. Depuis le 26/09/2026, **plus rien n'a abouti** -
  que du `pending` et de l'`expired` ;
- la clé lit `transactions`, `balances`, `customers`, `webhooks`, `currencies`,
  mais `GET /v1/payouts` répond **403 « Opération non autorisée »** et
  `/accounts`, `/api_keys`, `/settings` répondent 401.

⚠️⚠️ **ET LE COUPABLE EST L'OPÉRATEUR : `togocel`, LUI SEUL.** Deux pistes
avaient été avancées le même jour - une clé `sk_live_` restreinte, ou
l'encaissement du compte fermé - et **un essai réel les a réfutées toutes les
deux** : le 18/10/2026 à 18h29, transaction **113761896, `moov_tg`, 100 F,
`approved`**, même compte, même clé, même intégration, même montant. GEVES l'a
enregistrée (paiement 196 passé à `paid`, puis remboursé à la main depuis le
back-office) : **toute la chaîne fonctionne**, jusqu'au retour de vérification.

⚠️ Ce qui reste refusé, c'est **Togocel T-Money** (`togocel`), et il l'était déjà
le 02/09/2026 - la dernière somme encaissée par ce canal. Rien à corriger chez
nous : c'est une réouverture à demander au prestataire. ⚠️ **Mais la portée est
lourde** : l'université est à Kara, et un payeur Togocel ne peut pas régler du
tout - et depuis que le moyen se choisit sur la page du prestataire, il n'y a
plus même un écran de chez nous pour l'en avertir : il lit « Opération non
autorisée » en rouge, dans un cadre qui n'est pas à nous.

⚠️ **Deux écrans resteront donc ceux du prestataire** tant que l'activation n'est
pas accordée : son **sélecteur d'opérateur** et sa case **« Je veux recevoir mon
reçu par mail »**. Ils vivent dans un cadre d'un autre domaine - aucune de ses
options documentées (`public_key`, `transaction`, `customer`, `currency`,
`locale`, `button`) ne les pilote, et son `checkout.js` ne transmet rien d'autre.
Inutile de chercher : c'est vérifié dans sa source.

⚠️ **Les codes d'opérateur sont CEUX DU PRESTATAIRE, et quatre étaient faux** :
`mtn_open_ci` (le code documenté est **`mtn_ci`**), plus `bmo`, `coris_money` et
`wave_direct_ci`, absents de toute liste officielle. Écrits d'après l'API
publique sans jamais rencontrer un vrai compte marchand, et **ignorés en
silence** : le payeur croyait choisir, et rien ne partait. La liste documentée
est `mtn_open`, `moov`, `sbin`, `mtn_ci`, `moov_tg`, `togocel`, `free_sn`,
`airtel_ne`, `mtn_open_gn` - un cas de `PaymentGatewayTest` interdit d'en
proposer un autre.

⚠️ **« Je choisis sur la page de paiement » reste un choix du PAYEUR** : aucun
opérateur ne lui est imposé, même déduit de son numéro - la portabilité peut
avoir démenti la déduction, et il a dit qu'il trancherait là-bas. Le **pays**,
lui, part toujours : il ne décide de rien, il évite seulement qu'on propose le
Bénin à un Togolais.

⚠️ **LA FENÊTRE N'APPARAÎT QUE LORSQUE LE FORMULAIRE EST LÀ.** Elle s'ouvrait
vide, et le payeur regardait un cadre blanc se remplir pendant une à deux
secondes - au moment du parcours où l'on doute le plus, sur un écran où une page
blanche se lit comme une panne. On montre d'abord une **attente animée** (le
ruban du voile IA, `ukcAiSlide` : une seule animation d'attente dans le projet),
puis la fenêtre, qui arrive en fondu avec son objet, son montant et son
formulaire d'un seul tenant.

⚠️⚠️ **L'attente a un PLANCHER, et l'échec OUVRE la fenêtre.** C'est la
contrepartie indispensable : le signal peut ne jamais venir (cadre déjà chargé,
donc `load` qui ne rejouera pas ; script absent ; CDN coupé). Trois filets -
`load` du cadre, 4 s après sa pose, 12 s en tout - et `data-failed` **montre** la
fenêtre, parce que c'est elle qui porte le message et la **porte de sortie**.
Sans cela l'attente tournerait à l'infini devant un payeur qui n'a plus aucune
issue : un paiement perdu, pour avoir voulu épargner un cadre blanc. ⚠️ Pour la
même raison, un `window.FedaPay` absent **lève une erreur** au lieu de rendre la
main en silence.

⚠️ **ÉPROUVÉ EN CONDITIONS RÉELLES le 2026-10-18** (compte marchand en
production, clés `live`) : le formulaire s'ouvre bien sur `/compte/paiement/{id}`
sans aucune redirection, il porte le bon marchand, notre identifiant de
transaction, et il arrive **pré-rempli** de l'e-mail et du téléphone du payeur.

⚠️ **Deux choses mesurées là, et seulement là :**

- **Tout réglage enregistré doit être listé dans `applySettingsToConfig()`.** La
  clé publique se saisissait, se rangeait en base et se réaffichait dans le
  formulaire - sans jamais atteindre la passerelle : le formulaire embarqué
  restait muet et le paiement repartait en redirection, **sans un mot**. Un
  réglage absent de cette liste ment. Un cas d'`AdminSettingsTest` le tient.
- **Le prestataire pose son cadre à 150px** et le laisse défiler en dedans : le
  formulaire tenait dans une meurtrière, avec sa propre barre de défilement DANS
  la nôtre. On ne lui impose pas de hauteur, on lui donne un **plancher** - il
  reste libre de grandir.

⚠️ **La CARTE dépend du compte marchand, et elle n'était pas ouverte** : le
formulaire ne proposait que Mobile Money. Rien à régler chez nous depuis que le
payeur choisit son moyen là-bas (cf. la section suivante) - c'est une couverture
à demander au prestataire.
Cinq cas dans `PaymentGatewayTest`, trois sabotages vérifiés.

### La passerelle réglée est la SEULE proposée (2026-10-24)

⚠️⚠️ **LE RÉGLAGE DISAIT UNE CHOSE, LA PAGE EN PROPOSAIT DEUX.** Les écrans de
paiement partaient de `GatewayRegistry::available()`, c'est-à-dire de **tout ce
qui est utilisable** - donc toujours le paiement manuel, qui ne demande aucun
identifiant. Un événement réglé sur FedaPay offrait quand même « Paiement sur
place / virement » au participant, et récoltait des règlements que **personne
n'attendait au guichet**.

Source unique : **`GatewayRegistry::offered()`** - la passerelle réglée, en
liste (les écrans en font des cartes) - et **`offers($key)`** pour la garde
d'écriture. Choisir FedaPay, c'est n'encaisser qu'en ligne ; choisir le manuel,
c'est n'encaisser qu'au guichet.

- ⚠️ **Le repli vit dans `default()`**, et il est essentiel : une passerelle
  choisie mais **pas configurée** retombe sur le manuel. On ne laisse jamais le
  payeur devant zéro moyen de régler - **une clé oubliée ne doit pas fermer la
  caisse**.
- ⚠️ **On ferme la porte, pas seulement le formulaire** : `pay()` et `enroll()`
  refusent une passerelle qui n'est pas celle de l'événement. Ne plus afficher
  une carte n'empêche pas d'en poster la clé, et on enregistrerait un règlement
  au guichet sur un événement qui n'encaisse qu'en ligne, sans que personne ne
  sache d'où il vient. Même doctrine que le retrait du choix d'opérateur.
- ⚠️⚠️ **LA CONFIGURATION DE L'ÉVÉNEMENT VAUT DÈS L'AFFICHAGE.**
  `FormationController::index()` n'appelait **pas** `EventPaymentConfig::apply()` :
  la page annonçait la passerelle de la **plateforme** pendant qu'`enroll()` en
  employait une autre. Inoffensif tant que les deux étaient proposées ; décisif
  dès lors que le réglage décide.
- ⚠️ **On ne dit « choisir » que s'il y a un choix** : la légende du groupe passe
  à « Moyen de paiement » avec une seule passerelle, et la page de formation
  **nomme** le moyen à côté du bouton - un champ caché seul envoyait le
  participant chez le prestataire sans qu'un mot l'ait annoncé.
- ⚠️ **Le guichet n'est pas fermé pour autant** : le secrétariat saisit toujours
  un règlement depuis `/admin/paiements/creer`. Ce qui disparaît, c'est le
  **participant** qui s'annonce payeur au guichet d'un événement qui n'en veut
  pas.
- ⚠️ **Les libellés d'écran suivent** : « Passerelle **par défaut** » devenait
  faux - ce n'est plus un défaut qu'on peut contredire, c'est la passerelle.
  Réglages › Paiement et l'onglet Intégrations d'un événement disent désormais
  ce que le participant verra.

⚠️⚠️ **ET L'ÉCRAN A SUIVI, PARCE QU'UNE SEULE CARTE NE SE PRÉSENTE PAS COMME
DEUX.** Deux travers, tous deux **mesurés** sur un harnais isolé à 1600px :

- **`repeat(auto-fit, minmax(140px, 1fr))` étirait la carte du moyen de paiement
  sur toute la largeur** - **1298px** pour un pictogramme et deux mots, centrés
  au milieu d'un désert. La piste est **bornée** (`minmax(180px, 360px)`) et
  calée à gauche : **360px** mesurés, même allure à une comme à deux cartes.
  C'est la famille de piège déjà payée sur le barème et les ateliers de la page
  publique - `auto-fit` ne sait pas combien d'éléments il reste.
- **La phrase d'accroche annonçait une décision qui n'existe pas** :
  « Choisissez un moyen de paiement. Votre reçu sera disponible… » ouvrait sur un
  choix retiré, et ses **94 caractères** retombaient à la ligne sur le mot
  « confirmation. » tout seul. La première phrase ne sort plus qu'à partir de
  deux passerelles ; le reste tient sur **une ligne (348px dans une boîte de
  520)**.

⚠️⚠️ **ET ON N'ANNONCE PLUS LA CARTE BANCAIRE** (2026-10-24). Le libellé
promettait « Mobile money / **carte bancaire** » : elle demande une couverture
que le compte marchand n'a pas, et le formulaire du prestataire n'offre que du
mobile money. Un payeur venu pour la carte y renonçait devant un écran qui ne la
montre nulle part - **dans un cadre qui n'est pas le nôtre**, donc sans un mot
d'explication possible. La règle porte sur **tout ce que le payeur lit** :
`FedaPayGateway::label()`, `Payment::ONLINE_METHODS` (ce libellé finit sur son
**reçu**) et l'étape « Paiement » du parcours de la page publique. Le jour où la
couverture est accordée, c'est dans `label()` qu'on la rajoute - et nulle part
ailleurs.

- ⚠️ **Les deux passerelles en ligne portent désormais le même libellé**, et
  c'est voulu : le payeur ne doit pas pouvoir dire quel agrégateur a servi. Qui
  a besoin de les distinguer lit la colonne `gateway`, qui ne lui est pas
  montrée.
- ⚠️ Rien à régler pour autant : la case « Proposer la carte bancaire » avait
  déjà été retirée la veille, faute de commander quoi que ce soit.

⚠️ **Mesuré sur la VRAIE police** : `.account-payment` impose
`font-family: var(--ukc-font)` à 13.5px, donc **Jost** - pas le Public Sans du
reste du `.ukc-shell`. Un harnais qui chargerait l'autre fonte donnerait un autre
chiffre.

⚠️ **Piège de test** : `configureFedapay()` ne posait que les identifiants.
Depuis que la passerelle se **règle** en plus, des clés sans sélection laissent
l'événement au guichet - c'est le réglage même qu'on veut éprouver. L'aide pose
donc `payments.default` avec.

Gardes : `PaymentGatewayTest` (37 cas), `FormationEnrollmentTest` (10 cas) et
`PaymentOperatorTest` (14 cas), **9 sabotages vérifiés** - `offered()` rendu à
`available()`, la garde d'écriture retirée des deux parcours, la légende figée
sur « Choisir », `EventPaymentConfig` retiré de l'affichage des formations, la
piste rendue à `1fr`, la phrase d’accroche remise à « Choisissez… », et la carte
bancaire repromise dans le libellé puis dans le parcours public.

### Le choix de l'opérateur a été RETIRÉ (2026-10-24)

⚠️⚠️ **LA PASSERELLE CHOISIE EST LE MOYEN DE PAIEMENT.** Prendre « Mobile money
/ carte bancaire », c'est aller sur la page du prestataire, qui propose tout ce
que le compte marchand accepte. Le payeur désignait d'abord son opérateur chez
nous - et la carte, pour qu'il sache qu'elle était acceptée - sur un écran de
plus. C'était un second choix pour la même chose.

⚠️ **Et il ne servait à rien : `mode` NE PRÉ-SÉLECTIONNE RIEN**, mesuré en
conditions réelles (voir la section précédente). Le champ partait bien, et la
page du prestataire affichait quand même son propre sélecteur. On faisait donc
trancher le payeur chez nous pour qu'il retranche aussitôt dans un cadre qui
n'est pas le nôtre.

**Ce qui a disparu** : le composant `<x-ukc.pay-method>` et ses deux emplois
(page de paiement, inscription à une formation), `Payment::setOperator()`,
`OnlineGateway::paymentMethods()`, `OnlineGateway::operatorFromPhone()`,
`App\Support\MobileNetwork` et ses cinq plans de numérotation,
`FedaPayGateway::CARD`, le `mode` de la requête de création **et** celui du
formulaire embarqué (`PaymentEmbed`, `app.js`), le réglage **« Proposer la carte
bancaire »** (`services.fedapay.card`, `FEDAPAY_CARD`) - devenu inerte, et un
interrupteur qui ne commande plus rien est pire qu'une absence d'interrupteur -
et le fichier `OperateurDeduitTest`.

⚠️ **On retire la SAISIE **et** ce qu'on accepte.** Fermer le formulaire sans
fermer la porte laisserait un paramètre invisible piloter la page du
prestataire : qui le poste à la main dirigerait le paiement, et personne ne le
verrait - ni à l'écran, ni dans les gardes. D'où une garde de source qui balaie
`app/` et `resources/views/` (commentaires retirés) et refuse le retour de
`setOperator` comme de `input('operator')`.

⚠️ **CE QUI RESTE, ET POURQUOI : `mobileOperators()`.** Elle ne sert plus à
faire choisir, elle sert à **LIRE** : le prestataire rapporte un `mode` sur un
encaissement abouti ({@see OnlineGateway::details()}), et sans ce lexique le
back-office et le reçu afficheraient un code brut. C'est aussi pour cela que le
cas « ne propose que des codes opérateur documentés » reste : ces libellés
finissent sous les yeux d'un gestionnaire.

⚠️ **ET LE PAYS, LUI, PART TOUJOURS** (`customer.phone_number.country`) : sans
lui le formulaire embarqué retombe sur le Bénin, quel que soit l'indicatif -
mesuré en production sur un numéro togolais. Il ne décide de rien, il évite
seulement qu'on propose le mauvais pays. Ne pas le retirer avec le reste.

⚠️ `meta['operator']` n'est donc plus **alimenté** que par ce que le prestataire
rapporte ; d'anciennes lignes en portent encore un, et `initiate()` ne le lit
plus. La colonne n'est pas purgée : c'est la trace d'un choix que quelqu'un a
fait.

⚠️ **Piège Pest payé ici** : `expect($js)->not->toContain('cfg.mode')` mord sur
**`cfg.moderator`**, qui appartient à la salle de visio. L'aiguille porte donc
`mode: cfg.mode`.

Gardes : `PaymentOperatorTest` (13 cas) et `PaymentGatewayTest` (31 cas),
**5 sabotages vérifiés** - le `mode` remis dans la requête, un `<select
name="operator">` remis sur la page, l'opérateur réenregistré à l'inscription
à une formation, la garde de source contournée, et le `mode` remis dans la
charge du formulaire embarqué.

### ⚠️ CashPay (SEMOA) a été RETIRÉE des moyens de paiement (2026-10-18)

Il ne reste que **`manual`** (encaissement hors ligne) et **`fedapay`**.

⚠️ **SEMOA n'était pas une passerelle à part : c'est la société derrière
CashPay.** Le projet écrivait « CashPay (SEMOA) » dans les deux listes de
l'admin, et son API vivait sur `api.semoa-payments.ovh`. Retirer « SEMOA »
sans retirer CashPay n'aurait donc rien retiré du tout.

Ce qui a disparu : la classe `CashpayGateway`, son entrée au registre, le bloc
`services.cashpay`, les six variables `CASHPAY_*`, les deux listes déroulantes
(plateforme et par événement) avec leurs champs d'identifiants, la surcharge
runtime (`EventPaymentConfig::CASHPAY_KEYS`, `AppServiceProvider`), les règles
de validation (`in:manual,fedapay`) et six cas de `PaymentGatewayTest`.

⚠️ **Ce qui RESTE, et ne doit pas être « nettoyé » : le libellé `cashpay` dans
`Payment::ONLINE_METHODS`.** Un règlement déjà encaissé par elle garde sa clé en
base ; sans ce libellé, l'historique des paiements l'afficherait **sans nom**.
Effacer la trace d'un encaissement pour faire disparaître une passerelle, ce
serait réécrire des comptes. Le registre, lui, ne connaît plus la clé : elle
n'est plus sélectionnable nulle part.

⚠️ **Les identifiants enregistrés ont été SUPPRIMÉS de la base** (migration
`2026_10_18_000100_retirer_les_identifiants_cashpay`) : mot de passe, client
secret et clé de vérification du callback sont des secrets marchands, et plus
rien ne les lit. Deux dépôts à nettoyer, pas un : les réglages de la
**plateforme** (`settings`, clés `cashpay.*`) **et** ceux propres à chaque
**événement** (`events.settings`, sous `payment.cashpay`). La passerelle par
défaut qui désignait CashPay revient à `manual` - sinon le réglage pointerait
sur une passerelle absente et le registre y retomberait en silence. `down()`
est vide : on ne restaure pas des secrets supprimés.

⚠️ **`firebase/php-jwt` RESTE une dépendance** : elle sert aussi aux jetons
LiveKit et à la certification des attestations.

Gardes dans `PaymentOperatorTest` : la clé absente du registre, le fichier
supprimé, les deux formulaires qui la refusent, le libellé d'historique
conservé, et les trois cas de la migration.

### ⚠️ Parcours en 5 étapes : une classe CSS portée par deux composants (2026-10-17)

**`.ukc-step` est défini DEUX fois, et le parcours héritait du mauvais.**
`ukc.css` en fait une **ligne flex** (pastille à gauche, libellé à droite) pour
les boîtes d'étape du tableau de bord et du dépôt de communication ; `public.css`
en fait le **jalon vertical** du parcours de la page colloque. Le second
neutralisait bien le fond, le cadre et les marges du premier - mais **pas son
`display`**. Résultat à l'écran : la pastille, le titre et la précision des cinq
étapes se rangeaient bout à bout sur **une seule ligne** (« 1 Compte Création &
vérification 2 Soumission… »), traversée à mi-hauteur par le filet de liaison.
Rien ne tombait : aucun cas ne lisait la feuille de style.

⚠️ **Ce qu'une classe partagée impose, il faut le défaire en entier** - `display`
compris, et pas seulement ce qui se voit dans le composant d'origine.

⚠️ **Le filet relie deux pastilles VOISINES** (`.ukc-step + .ukc-step::before`,
de `left: -50%` à `right: 50%`), il ne se tend plus d'un bord à l'autre du
conteneur. `left: 10%; right: 10%` ne tombait sur le centre des pastilles
extrêmes que pour **cinq** étapes exactement : en ajouter ou en retirer une le
laissait flotter à côté d'elles.

⚠️ **La pastille passe devant le filet, et `position: relative` n'y suffit pas.**
Le filet appartient à l'étape de DROITE et déborde sur celle de gauche : à rang
égal, c'est l'ordre du document qui tranche, donc il se peignait par-dessus la
moitié droite de la pastille précédente - un trait en travers de chacun des cinq
chiffres. D'où un rang **explicite** de part et d'autre (`z-index: 1` sur la
pastille, `0` sur le filet). Les étapes ne créant pas de contexte d'empilement
(`position: relative` sans `z-index`), les deux rangs se comparent bien d'une
étape à l'autre.

⚠️ **Sur mobile, le jalon est une GRILLE, pas une ligne flex** : en flex, la
pastille, le titre et la précision sont trois frères de rang égal, donc trois
colonnes - le même travers qu'en plein écran, en plus serré. La grille
(`grid-template-columns: auto 1fr`, pastille sur `grid-row: 1 / span 2`) empile
le texte en face de la pastille sans bloc enveloppant de plus. Et le filet s'y
**redresse** au lieu de disparaître : empilées sans lui, les étapes ne se lisent
plus comme un parcours mais comme une liste de services.

⚠️ **Aucune étape n'est « active ».** La première portait la pastille pleine de
l'étape en cours, sur une page **publique qui ne sait pas qui la lit** : elle
annonçait « vous en êtes là » à quelqu'un qui n'a peut-être rien commencé, ou
qui a déjà tout terminé - et qui par ailleurs ne doit rien voir de personnalisé
ici (la page est cacheable en HTTP). Les cinq étapes **décrivent** le parcours ;
c'est le tableau de bord qui y situe quelqu'un.

Le balisage est une **liste ordonnée** (`<ol class="ukc-stepper">`), parce que
l'ordre est tout le propos et que cinq `div` ne le portaient qu'à l'œil. Les
chiffres des pastilles sont `aria-hidden` : la liste les dit déjà.

Trois cas dans `PageColloquePubliqueTest.php` (feuille de style lue
**commentaires retirés**, cf. `cssPublic()`), vérifiés par sabotage.
### ⚠️ UN SEUL chemin de règlement pour un co-auteur : son compte

`/colloque/{slug}/co-auteur` est une **porte d'entrée**, plus un parcours de
paiement. Elle confirme par e-mail que des communications acceptées citent la
personne, puis l'envoie créer son compte ou s'y connecter ; un visiteur déjà
connecté est redirigé droit sur `account.payment`.

- **Pourquoi** : le chemin par compte existe déjà et il est plus riche -
  `LinkSubmissionAuthors` rapproche chaque co-auteur des comptes dès la
  soumission (sinon invitation à en créer un, réactivée à l'inscription par
  `LinkPendingAuthorships`), la communication apparaît dans « Mes
  communications », l'autorité se décline, l'attestation se retrouve dans
  « Mes documents ».
- ⚠️ **Les deux parcours créaient chacun leur participant** - l'un par e-mail
  **sans `user_id`** (`UpsertCoauthorParticipant`, supprimée), l'autre par
  `user_id` (`UpsertParticipantForAccount`) - et **rien ne les rapproche** : il
  n'existe aucune contrainte d'unicité sur `(edition_id, email)`. **Mesuré** :
  payer sur la page publique puis créer son compte laissait **deux
  participants** pour la même personne sur la même édition. Chiffres faussés, et
  surtout l'attestation émise sur le participant sans compte - donc
  **introuvable dans l'espace de l'intéressé**. Dans l'autre ordre il n'y avait
  pas de doublon, la page réutilisant la ligne existante : le défaut dépendait
  de l'ordre, donc il passait inaperçu.
- ⚠️ **L'URL est conservée** : les e-mails d'acceptation déjà partis la portent,
  et un lien mort aurait coûté des règlements. L'e-mail d'acceptation
  (`NotifyCoauthorsForPayment`) est **gardé aussi** - c'est lui qui annonce
  « votre communication est acceptée, vos frais sont dus », ce que l'invitation
  envoyée à la soumission ne dit pas - mais il pointe désormais sur la porte
  d'entrée et non plus sur un encaissement parallèle.
- ⚠️ L'état **« frais déjà réglés »** reste sur la page : le retour de passerelle
  d'un règlement co-auteur **ancien** (`meta.flow = coauthor`) y atterrit encore
  (cf. `PaymentController::return`).
- L'adresse voyage dans le lien d'inscription (`?email=`) : c'est elle qui
  rattache les communications au compte créé.
- **Présentation** : la page emprunte la grammaire des autres pages publiques -
  `partials.public-header`, `section.page-hero`, `ukc-section`, barre d'étapes
  `ukc-steps`, puis `partials.colloque-footer`. On y arrive par un lien reçu par
  e-mail, souvent **sans savoir de quel colloque il s'agit ni comment en sortir** :
  le bloc centré sans en-tête ni pied de page qu'elle était ne le disait pas.
- ⚠️ **MESURÉ** : `background: currentColor` dans le même bloc que `color: #fff`
  résout le fond sur le `color` qu'on vient de poser - la pastille de l'étape
  active sortait **blanche sur blanc**, numéro invisible. On nomme chaque fond,
  état par état (9,16:1 pour l'étape active).
- ⚠️ `.ukc-card h2` est en `space-between` **pour le back-office**, où une action
  vit à droite du titre. Sur une page publique il n'y en a aucune et le titre
  partait à l'autre bout de la carte, loin de son icône : les pages publiques
  (portée `.site-header ~ …`) le regroupent à gauche. Corrige aussi
  « Vérifier une attestation », qui portait le même défaut.
### Densité des back-offices : zoom 90%

`html.ukc-dense { zoom: .9 }` (`ukc.css`), posé par les layouts `admin` et
`participant` via l'attribut `html-class` de `x-layouts.app`.

- ⚠️ **Sur `<html>`, jamais sur le shell** : le `zoom` d'un sous-arbre met à
  l'échelle les longueurs qu'il contient **sans toucher au viewport**, donc un
  `min-height: 100vh` ne couvrirait plus que 90% de l'écran et la barre latérale
  fixe s'arrêterait avant le bas. Sur la racine, c'est le bloc conteneur initial
  qui est mis à l'échelle - exactement ce que fait le zoom du navigateur, qui
  reste d'ailleurs cumulable. **Vérifié** : barre latérale 698,4px pour une
  fenêtre de 698px, aucun débordement horizontal.
- ⚠️ Les **media queries** continuent de mesurer la fenêtre réelle : les points
  de rupture se déclenchent un peu tôt, dans le sens prudent (mise en page
  étroite avec de la place en trop).
- Le **public** garde l'échelle du navigateur du visiteur.
- **Pint** applique un style projet-wide (concat sans espaces `'a'.$b`) - lancer `pint`
  avant de commiter.

### Scrollbar du back-office = Perfect Scrollbar (overlay façon bizzcart)

Deux mécanismes, complémentaires :

- **Perfect Scrollbar** (lib JS, `app.js` → `initPerfectScrollbars`) pour les
  **conteneurs défilants à hauteur bornée** : la **navigation latérale**
  (`.ukc-sidebar-nav`) du back-office **et** de l'espace participant, plus tout
  élément portant **`data-ps`**. C'est lui qui donne l'overlay fin qui
  **apparaît en fondu au survol / pendant le défilement** (comportement identique
  à la barre latérale de l'admin bizzcart). Instance mémorisée sur l'élément
  (`el.__ps`), mise à jour via un `ResizeObserver` + au `resize` fenêtre, et PS
  est **désactivé sur pointeur grossier** (`pointer: coarse`) où le défilement
  tactile natif suffit. Thème dans `ukc.css` (bloc « Perfect Scrollbar ») : rail
  transparent, pouce 6px arrondi - **clair** `rgba(255,255,255,.28/.45)` sur la
  nav sombre (un pouce noir bizzcart y serait invisible), **noir** `rgba(0,0,0,.2/.35)`
  pour les `[data-ps]` sur fond clair. ⚠️ Nos sélecteurs portent `.ukc-shell`
  (2 classes) → ils priment sur ceux de la lib (`.ps__thumb-y`, 1 classe) quel que
  soit l'ordre des feuilles. ⚠️ **Jamais sur la fenêtre** : PS ne gère que des
  conteneurs à `overflow` masqué qu'il pose lui-même.
- **Scrollbar natif stylé** (CSS pur, `ukc.css`, `html.ukc-dense` + `.ukc-shell *`)
  pour tout le reste, **le défilement de page compris** (que PS ne peut pas
  prendre) : même palette bizzcart (pouce `rgba(0,0,0,.2)`, rayon 6px, piste
  transparente). C'est le repli quand PS ne s'applique pas (public = scrollbar
  natif du navigateur, ni `ukc-dense` ni `.ukc-shell`).

### Tutoriels vidéo : rendu autonome, captures authentifiées et musique (2026-10-09)

- Une demande depuis `admin/prise-en-main` ne dépend plus d’un worker Laravel
  externe : `TutorialVideoWorkerLauncher` lance `tutorial-videos:render-pending`,
  lequel revendique puis produit les demandes `queued` directement en base.
  Ainsi, un administrateur déclenche le rendu serveur sans intervention Codex.
- Chaque vidéo reprend le **monogramme compact** (`app_logo_mark`, avec repli sur
  `public/img/apple-touch-icon.png`), jamais le lockup large.
- Chaque étape porte une **capture authentifiée** de l’interface GEVES via le
  compte technique de démonstration ; ne pas remettre de maquette à la place.
  `Uplifting Corporate Loop` est le lit musical local par défaut. `calm`, `dynamic`,
  `none` et un fichier administrateur sont gérés par `meta.music` ; le fichier
  téléversé est prioritaire et FFmpeg le boucle, le normalise puis l’atténue en fin.
  Après l’envoi, l’admin ne doit pas être redirigé : `admin.onboarding.videos.status`
  expose l’état persistant et `meta.progress` (`queued`, `planning`, `captures`,
  `rendering`, `publishing`, `ready`/`failed`). La vue conserve le calque bleu de
  suivi, interroge ce point toutes les deux secondes, puis recharge uniquement quand
  le MP4 est publié.
  Le formulaire comporte volontairement un champ caché `action` : en JavaScript,
  récupérer l’URL avec `form.getAttribute('action')`, car `form.action` désignerait
  ce champ HTML et conduirait à la route invalide `/admin/[object HTMLInputElement]`.
  Sous Windows, le lanceur résout le voisin `php.exe` de `PHP_BINARY` avant de créer
  le processus : une requête servie par PHP-FPM peut exposer `php-cgi.exe`, qui quitte
  sans exécuter Artisan. Le PID n’est mis en cache qu’après vérification de la ligne
  de commande du worker.
- Le renderer résout également `ffmpeg.exe` **et `ffprobe.exe`** puis transmet leurs
  chemins à Hyperframes (`HYPERFRAMES_FFMPEG_PATH` / `HYPERFRAMES_FFPROBE_PATH`) : le
  processus détaché Windows n’hérite pas toujours du `PATH` utilisateur. Les tutoriels sont encodés nativement en **1920×1080 Full HD** : les captures sont déjà créées dans ce format et ne sont donc plus réduites au montage. Le moteur conserve un seul worker et le mode faible mémoire ; il utilise les ressources du serveur qui exécute Laravel, jamais celles du navigateur de l’administrateur. Les variables
  `TUTORIAL_VIDEO_FFMPEG_BIN` et `TUTORIAL_VIDEO_FFPROBE_BIN` permettent un chemin
  explicite si les binaires ne viennent pas de WinGet. ⚠️ Un chemin configuré qui
  **n'existe pas** est ignoré (`is_file()`) : laissé dans un `.env` après une
  désinstallation, il ferait échouer le rendu sur « commande introuvable » au lieu de
  retomber sur le PATH, où FFmpeg est peut-être. ⚠️ Et le résolveur **n'exige pas**
  FFmpeg : sans WinGet ni chemin configuré il rend `ffmpeg` tout court, et c'est le PATH
  qui tranche à l'exécution - ne pas écrire de test qui suppose l'inverse (cf. §Tests). `TUTORIAL_VIDEO_RENDER_WIDTH`
  et `TUTORIAL_VIDEO_RENDER_HEIGHT` documentent le format attendu (1920×1080).

## Choix de la photo sur les badges participants

Dans `/documents`, le choix « Avec photo / Sans photo » est un interrupteur dans la
colonne des détails, juste avant le QR code. Il s'enregistre en AJAX pour chaque type
de badge via `Account\DocumentController::updateBadgePhoto()` et remplace le seul
aperçu concerné ; garder la soumission classique si JavaScript est indisponible.

## Références des badges

`BadgeCatalog::reference()` émet une clé stable `BDG-XXXX-XXXX-XXXX-XXXX` à partir
de l'ancienne identité interne, pour l'aperçu participant et la planche admin.
Garder les deux rendus et le QR alignés sur cette référence ; la planche passe
`{verify_url}` au modèle. La route `/verifier` n'a pas encore de contrôle des badges.

## Échéance des soumissions

Utiliser `Event::submissionsAreOpen()` (ou le scope `acceptingSubmissions()` pour une
requête) au lieu de lire seul `submission_open` : l'appel ferme à l'instant de sa
date limite. Le scheduler `events:close-submissions` persiste ensuite la fermeture
chaque minute. `SaveEvent` interdit d'enregistrer « ouvert » avec un délai passé ;
le formulaire admin décoche la case en direct et permet une réouverture après
prolongation de la date.

## Pied des badges par défaut

Le concepteur JS (`co` et `co-photo`) et `LayoutSanitizer::defaultBadge()` doivent
garder les mêmes coordonnées pour les icônes de lieu et de calendrier, et les deux
textes voisins. Les modèles standard déjà enregistrés sont ajustés par la migration
`2026_10_15_000300_align_default_badge_footer`. Le badge délivré utilise
`Event::dateRangeLabel()` pour les dates, comme l'aperçu du concepteur.

Le trait sombre du pied est désormais un calque `rect` horizontal : conserver
`erase_default_rule` avec les modèles fondés sur `badge-default-bg.png` pour
masquer le trait historique intégré au PNG. La migration
`2026_10_15_000600_make_badge_footer_rule_editable` met à jour les modèles
standards sauvegardés et leur `editor_state`. Les pictogrammes du pied sont des
images à `color` optionnel ; le designer affiche une teinte par masque et
`LayoutRenderer` produit un PNG recoloré pour le HTML et le PDF.

Le designer expose « Épaisseur » pour les lignes horizontales et verticales ;
`rect.shape` garde leur orientation dans le modèle et la valeur de `h` ou `w`
porte l'épaisseur exacte (1 à 40 px). Les pictogrammes du pied sont désormais
des SVG vectoriels recolorables. `2026_10_15_000700_use_vector_badge_icons`
convertit les sources PNG des modèles enregistrés, et `LayoutRenderer` conserve
la prise en charge des anciens PNG pour les documents historiques.

## Cache Blade sous Herd/Windows

`AppServiceProvider` fournit `WindowsSafeFilesystem` seulement sur Windows.
Dans `storage/framework/views`, `replace()` sérialise les écritures par fichier
et reprend brièvement un renommage si PHP-CGI garde la cible ouverte. Cela évite
les erreurs `rename(...): Accès refusé (code: 5)` lors de compilations Blade
concurrentes, y compris celles du rendu d'exception. Les autres remplacements
de fichiers gardent le comportement de Laravel.

## Paramétrage assisté des événements

L'édition d'un événement utilise `ai-assist-json` pour proposer des champs depuis une
description, des PDF ou des images. `ExtractEventFromDocuments::handle($files, $prompt)`
transmet la description dans l'instruction textuelle de l'IA : ne pas l'ajouter aux
fichiers, car les fournisseurs interprètent tout fichier non PDF comme une image.
Les axes sont renvoyés en tableau, comme l'attend le panneau Alpine. L'administrateur
choisit les propositions à appliquer ; celles qui correspondent à des champs déjà
remplis sont décochées par défaut. « Appliquer » ne sauvegarde jamais l'événement.
L'interface et le serveur acceptent six PDF/images au plus, 10 Mo par fichier et
20 Mo au total. `public/.user.ini` porte les limites PHP-CGI de Herd à 20/24 Mo
et la mémoire à 256 Mo pour l'encodage des documents.

La route d'analyse porte sa seule limite PHP à 300 s : les fournisseurs HTTP
peuvent attendre jusqu'à 120 s pour lire un PDF, alors que Herd coupe normalement
PHP après 30 s. Les erreurs internes ne sont pas renvoyées telles quelles au
panneau. Ne pas répéter une clé API refusée (401) sur un autre modèle du même
fournisseur ; un délai de connexion expiré passe directement au fournisseur
suivant, tandis que les réponses transitoires 429/5xx restent réessayées.
Si tous les replis échouent, `AiManager` signale quels fournisseurs ont refusé
leur clé (401/403) pour guider la correction des réglages.

Après l'analyse, le panneau replie les sources et ouvre une modale de revue.
Elle regroupe les propositions en deux colonnes, les aperçus longs via « Voir tout »,
la sélection globale et « Appliquer la sélection » dans un pied toujours visible.
Fermer la modale conserve le résultat ; le panneau permet de la rouvrir sans relancer
l'analyse. Le panneau emploie le bleu et le vert de `--ukc-blue`/`--ukc-green`.

En local, `AppServiceProvider` privilégie le manifeste Vite compilé s'il existe :
un `public/hot` abandonné ne doit plus casser le style. Pour utiliser le HMR,
définir `VITE_USE_DEV_SERVER=true` dans `.env`, puis lancer `npm run dev`.

L'ancienne URL GET `/admin/editions/{event}` redirige vers
`admin.evenements.edit` avec la capacité `editions_write`. Le même chemin en
DELETE reste réservé à la suppression ; les nouveaux liens doivent utiliser
la route nommée de modification.

## Format des noms par événement

Le formulaire général d'une édition enregistre `settings.name_order` : `first_last`
(défaut historique) ou `last_first`. `App\Support\EventNameFormat` est la source
commune de présentation pour les participants, utilisateurs et auteurs ayant des
champs prénom/nom distincts. Les listes, le programme, les badges, le livre des
résumés, les reçus et les nouveaux courriels/attestations utilisent cette règle.
Les anciens auteurs ne possédant qu'un champ `name` conservent leur texte saisi.
Les attestations déjà émises gardent leur `recipient_name` signé.
Dans les tests, le nom de famille affiché est en majuscules (`Awa KODJO`).
Pour vérifier les badges prêts, cibler la ligne du destinataire : l'aperçu du
modèle peut afficher le même nom d'exemple même si aucun badge n'est prêt.

## Pages d'erreur HTTP

Les statuts 400, 403, 404, 500 et 503 ont chacun une vue dans
`resources/views/errors/`. Elles partagent `errors/page.blade.php` et la feuille
statique `public/error-pages.css` : les pages 500/503 ne doivent pas dépendre
de Vite, du widget ou d'une lecture en base pour rester affichables pendant
une panne. Laravel conserve les réponses JSON des requêtes API/fetch ; en
développement, `APP_DEBUG=true` affiche toujours la trace des erreurs 500.

### Un refus du prestataire doit DIRE pourquoi (2026-10-31)

⚠️⚠️ **400 EN PRODUCTION, ET AUCUNE TRACE DE LA RAISON.** Le 30/10/2026, la page
de paiement affichait « Le service de paiement est momentanément indisponible »
et le journal ne portait que `HTTP request returned status code 400:` -
`FedaPayGateway.php(135)`, soit `POST /transactions`. La clé était donc acceptée
(ce serait 401) et le prestataire répondait (ce ne serait pas 5xx) : **il
refusait notre requête**, et il écrit la raison dans le corps de sa réponse -
que `->throw()` jetait. **On ne diagnostique pas un refus qu'on n'enregistre pas.**

- **`FedaPayGateway::appel()`** est désormais le seul chemin des deux appels de
  `initiate()` : il consigne dans le canal **`payments`** le **statut** et le
  **corps** de toute réponse en échec, puis relance. Comportement inchangé pour
  le payeur.
- ⚠️ **Le jeton n'entre dans aucun journal** (`sansJeton()` remplace `token` et
  `url` - l'adresse de paiement porte le jeton dans son adresse). Règle du
  projet, tenue par un cas.
- ⚠️⚠️ **SANS IDENTIFIANT, ON S'ARRÊTE AVANT D'ENGAGER LE PAYEUR.** `$id` laissé
  à `null` était écrit tel quel dans `gateway_ref`, puis recollé dans
  `/transactions//token` - 404, donc « service indisponible » pour une **URL
  malformée**. Pire : la transaction créée chez le prestataire n'était plus
  rattachable, **ni par le webhook ni par `payments:reconcile`**, qui cherchent
  tous deux par la référence. Un paiement sans référence est un paiement perdu.
- ⚠️ **Le canal `payments` est en rotation QUOTIDIENNE** : le fichier s'appelle
  `payments-AAAA-MM-JJ.log`, pas `payments.log`. Un `tail payments.log` qui
  échoue n'est pas une panne.
- ⚠️ **Lire un 400 ne dit pas encore lequel** : la cause réelle du refus reste à
  établir avec le corps que ce correctif fait enfin apparaître.

Gardes : `PaymentGatewayTest` (3 cas ajoutés, **2 sabotages vérifiés**).

### La cause du 400 : le TÉLÉPHONE du payeur (2026-10-31)

Le corps enfin consigné (section précédente) a nommé la cause en une ligne :

```
"errors": {"phone_number.number": ["n'est pas valide"],
           "phone_number.country": ["doit être rempli(e)"]}, "model": "V1::Customer"
```

⚠️⚠️ **LE PAYS EST OBLIGATOIRE, ET SON ABSENCE FAIT REFUSER TOUTE LA
TRANSACTION.** On envoyait le numéro **tel qu'il est stocké** et on **retirait**
le champ `country` quand `countryFromE164()` ne savait pas le déduire
(`array_filter`). Un seul payeur au numéro mal formé suffisait à faire lire
« service momentanément indisponible » - un message qui dit à l'intéressé
d'attendre une réparation qui ne viendrait jamais.

- ⚠️⚠️ **`participants.phone` NE PASSE PAS par le mutateur de `User::phone`.**
  C'est une colonne à part, remplie par un import, par le guichet
  (`/admin/paiements/creer`) ou à la main : elle arrive en « 90 01 52 84 », en
  « 0022890015284 », dans toutes les formes. Et `payerContact()` la lit **en
  priorité**, avant celle du compte. Supposer l'E.164 là était faux.
- **Remède : `Phone::toE164()`**, et le pays part **toujours**
  (`countryFromE164($e164) ?? Phone::DEFAULT_COUNTRY`).
- ⚠️⚠️ **`toE164()` N'EST PAS `normalize()`, ET LA DISTINCTION EST LE CŒUR DU
  SUJET.** `normalize()` sert à **identifier** (unicité de `users.phone`,
  rapprochement d'auteurs) et n'invente **jamais** d'indicatif : en inventer un
  rattacherait deux personnes différentes au même numéro. `toE164()` ne
  reconnaît personne - elle rend un numéro **composable** à un prestataire qui
  refuse un numéro sans pays. Ne pas fondre les deux.
- ⚠️ **`Phone::DEFAULT_COUNTRY` (`tg`) doit rester d'accord avec
  `initialCountry` de `resources/js/app.js`** : sinon l'écran propose un pays et
  le paiement en suppose un autre.
- ⚠️⚠️ **CE QUE LE PAYEUR PEUT CORRIGER SE DIT**
  ({@see App\Payments\PayerInputException}) : un numéro inexploitable lui demande
  désormais de le corriger **dans son profil, au format international**, au lieu
  d'annoncer une indisponibilité. Le message ne nomme pas le prestataire
  (convention du projet) et il est affiché tel quel - les deux parcours
  (participation et formation) l'attrapent avant le `catch` générique.

Gardes : `PaymentGatewayTest` (6 cas au total sur cet incident, **5 sabotages
vérifiés**).

### Et ce n'est PAS une mise à jour de l'API du prestataire (vérifié le 2026-10-31)

Question posée au bon moment, et la réponse est **non** - autant l'écrire une
fois pour toutes plutôt que refaire la recherche :

- **Changelog FedaPay** : dernière entrée **septembre 2026** (« New Balance
  Interface »). **Rien** en octobre ni novembre, et rien touchant la validation
  des numéros, les `customers`, la création de transactions, ni le Togo.
- **Format du numéro** : leur documentation est **elle-même incohérente** - un
  exemple PHP montre `number: "+22966666600"` avec `country: 'bj'`, un exemple
  Ruby `number: '66000001'` avec le même pays. Les deux formes sont donc
  admises, et **la nôtre est prouvée** : deux paiements ont abouti le 29/10 avec
  exactement ce code. ⚠️ **Ne pas « corriger » vers le numéro national** sur la
  foi d'un exemple de documentation : on casserait ce qui marche.
- **Conclusion** : quand le refus porte sur `phone_number`, c'est **le numéro de
  ce payeur-là** qui ne convient pas, pas notre intégration.

### Le téléphone du PAIEMENT vient du compte, pas de la fiche (2026-10-31)

⚠️⚠️ **LA CORRECTION DU PAYEUR RESTAIT SANS EFFET.** Le numéro refusé a été
corrigé dans le profil, et le journal a continué d'envoyer l'**ancien** :
`Payment::payerContact()` lisait `participants.phone` **en premier**, le compte
seulement en repli. Or :

- `users.phone` est **normalisé** par le mutateur du modèle et il est **unique** ;
  `participants.phone` est une copie libre, posée par un import ou par le
  guichet, normalisée nulle part ;
- c'est le seul des deux que l'intéressé peut corriger, et **aucun écran du
  back-office ne modifie `participants.phone`** : la fiche gagnait un bras de fer
  que personne ne pouvait arbitrer ;
- il sert à **joindre** quelqu'un maintenant - le paiement mobile sonne sur ce
  numéro - pas à dire qui il était à l'inscription.

⚠️ **Seul le téléphone est inversé.** Nom, prénom et e-mail gardent l'ordre
`participant` d'abord : ceux-là décrivent la personne **telle qu'inscrite à cet
événement**, et c'est la grammaire de l'identité figée
({@see Participant::eventIdentity()}). La fiche reste le **repli** du téléphone :
un participant saisi au guichet n'a pas forcément de compte.

⚠️ **À surveiller** : `participants.phone` n'étant modifiable par aucun écran, un
numéro fautif sur une fiche **sans compte** (guichet) n'est corrigeable qu'en
base. À ouvrir le jour où le cas se présente.
