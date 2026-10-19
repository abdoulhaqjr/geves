# Mettre GEVES en ligne sur o2switch

Cible : **https://geves.univkara.tg**. Hébergement mutualisé cPanel, avec SSH et
git. Le sous-domaine et la base de données existent déjà.

Comptez **une heure** la première fois. Chaque étape donne la commande, **ce que
vous devez voir**, et quoi faire si ce n'est pas ça.

---

## Le principe, en trois phrases

Une application Laravel n'est **pas** un dossier qu'on dépose dans
`public_html`. Le code vit **en dehors** de la zone servie par le web, et on
demande au sous-domaine de pointer vers son seul dossier public. Deux tâches
automatiques tournent en permanence : l'une déclenche les traitements
programmés, l'autre expédie les e-mails en attente.

```
/home/UTILISATEUR/
├── apps/geves/              ← le dépôt, INVISIBLE depuis le web
│   ├── .env                   secrets (base, e-mail, clés)
│   ├── app/ config/ ...       le code
│   ├── storage/               fichiers téléversés, journaux, cache
│   └── public/              ← SEUL dossier exposé
│        └── index.php          geves.univkara.tg pointe ICI
└── public_html/             ← on n'y touche pas
```

⚠️ **C'est le point qui fait échouer la plupart des mises en ligne.** Si le
sous-domaine pointe sur `apps/geves` au lieu de `apps/geves/public`, votre
fichier `.env` devient téléchargeable : identifiants de base de données, clés de
paiement, clé de signature des attestations.

---

## Avant de commencer : relever trois choses

Connectez-vous en SSH. Si la connexion est refusée, allez d'abord dans
**cPanel > Accès SSH** (ou « Autorisation SSH ») et autorisez votre adresse IP.

```bash
whoami
php -v
```

Notez :

1. **Votre nom d'utilisateur** (la sortie de `whoami`). Il remplacera
   `UTILISATEUR` partout dans ce document.
2. **La version de PHP.** Il faut **8.4.1 ou plus**.
   - Si c'est bon, le binaire est simplement `php`.
   - Sinon, changez-la dans **cPanel > Sélectionner la version de PHP**, puis
     retestez. Si le CLI reste en retard, cherchez le bon binaire :
     ```bash
     ls -d /opt/*/*php8[45]*/ 2>/dev/null
     ```
     et utilisez le chemin complet (par exemple
     `/opt/cpanel/ea-php84/root/usr/bin/php`) partout où ce document écrit `php`.
3. **Les identifiants de la base** créée dans cPanel : nom, utilisateur, mot de
   passe. cPanel préfixe les noms par votre compte (`UTILISATEUR_geves`).

⚠️ **Pourquoi 8.4.1 et pas 8.3 ?** Le fichier `composer.lock` du projet a figé
les composants **Symfony 8.x**, qui exigent 8.4.1. `composer.json` annonce
`^8.3` et Laravel 13 s'en contenterait, mais ce sont les versions verrouillées
qui s'installent. **Ne contournez pas avec `--ignore-platform-reqs`** : vous
installeriez du code qui planterait à la première requête. Si seul 8.3 est
disponible, dites-le moi : il faut re-verrouiller le projet sur Symfony 7.4 et
relancer toute la suite de tests, ce n'est pas une manipulation à improviser.

---

# Phase A. Donner au serveur l'accès au dépôt

Le dépôt GitHub est **privé**. Le serveur a donc besoin de sa propre clé, en
lecture seule.

### A1. Créer la clé sur le serveur

```bash
ssh-keygen -t ed25519 -C "geves-o2switch" -f ~/.ssh/geves_deploy -N ""
cat ~/.ssh/geves_deploy.pub
```

**Ce que vous devez voir** : une ligne commençant par `ssh-ed25519 AAAA...` et
finissant par `geves-o2switch`. C'est la clé **publique**, elle se partage.

⚠️ Ne copiez jamais `geves_deploy` (sans `.pub`) : c'est la clé privée, elle ne
quitte pas le serveur.

### A2. Déclarer la clé sur GitHub

Dans le navigateur : dépôt **abdoulhaqjr/geves > Settings > Deploy keys >
Add deploy key**.

- **Title** : `o2switch - geves.univkara.tg`
- **Key** : collez la ligne copiée
- **Allow write access** : ⚠️ **laissez décoché**. Le serveur doit pouvoir lire
  le code, jamais le modifier.

### A3. Dire à git d'utiliser cette clé

```bash
cat >> ~/.ssh/config <<'EOF'
Host github.com
    IdentityFile ~/.ssh/geves_deploy
    IdentitiesOnly yes
EOF
chmod 600 ~/.ssh/config
ssh -T git@github.com
```

**Ce que vous devez voir** :
`Hi abdoulhaqjr/geves! You've successfully authenticated, but GitHub does not
provide shell access.` Ce message est un **succès**, malgré sa formulation.

**Si vous voyez `Permission denied (publickey)`** : la clé n'a pas été collée
entièrement (elle tient sur une seule ligne), ou elle a été ajoutée au mauvais
dépôt.

---

# Phase B. Poser le code

### B1. Cloner

```bash
mkdir -p ~/apps
cd ~/apps
git clone git@github.com:abdoulhaqjr/geves.git geves
cd ~/apps/geves
git log --oneline -1
```

**Ce que vous devez voir** : le clonage se termine sans erreur, puis la dernière
ligne de l'historique s'affiche.

### B2. Installer les dépendances PHP

```bash
composer install --no-dev --optimize-autoloader --no-interaction
```

Comptez une à deux minutes.

**Ce que vous devez voir** : une liste de paquets installés, puis
`Generating optimized autoload files` et `Package manifest generated
successfully`.

⚠️ `--no-dev` est voulu : Pest, PHPStan et Pint n'ont rien à faire en ligne.
⚠️ **Ne lancez jamais `composer update` sur le serveur.** Il résoudrait des
versions différentes de celles qui ont été testées.

**Si vous voyez `requires php >=8.4.1 ... your php version (8.3.x) does not
satisfy`** : revenez à « Avant de commencer », point 2.

---

# Phase C. Brancher le sous-domaine

Dans cPanel : **Domaines** > ligne `geves.univkara.tg` > **Gérer** >
**Racine du document**.

Remplacez la valeur proposée par :

```
/home/UTILISATEUR/apps/geves/public
```

Enregistrez.

⚠️ La valeur pré-remplie par cPanel pointe sur le dossier créé avec le
sous-domaine. **Elle est fausse pour Laravel** : il faut descendre d'un cran,
jusqu'à `public`.

**Vérifiez tout de suite** en ouvrant `https://geves.univkara.tg` : vous devez
voir une **erreur Laravel** (page sombre, « 500 » ou un message sur `APP_KEY`).
C'est bon signe, cela prouve que PHP s'exécute. Si vous voyez une **liste de
dossiers** (`app`, `config`, `vendor`...), la racine est encore fausse.

Le fichier `public/.htaccess` livré avec Laravel suffit. N'en écrivez pas
d'autre.

---

# Phase D. Configurer l'application

### D1. Créer le fichier `.env`

```bash
cd ~/apps/geves
cp .env.production.example .env
php artisan key:generate
```

**Ce que vous devez voir** : `Application key set successfully.`

### D2. Le remplir

```bash
nano .env
```

Les champs à renseigner, et rien d'autre pour démarrer :

| Champ | Valeur |
| --- | --- |
| `DB_DATABASE` | `UTILISATEUR_geves` (nom exact donné par cPanel) |
| `DB_USERNAME` | l'utilisateur MySQL créé |
| `DB_PASSWORD` | son mot de passe |
| `MAIL_HOST` | `mail.univkara.tg` (ou ce qu'indique cPanel > Comptes de messagerie > Connecter des appareils) |
| `MAIL_USERNAME` | l'adresse complète de la boîte |
| `MAIL_PASSWORD` | son mot de passe |
| `MAIL_FROM_ADDRESS` | la même adresse |
| `ADMIN_EMAIL` | **votre** adresse d'administrateur |
| `ADMIN_PASSWORD` | un mot de passe long, **provisoire** (on le retirera en G4) |

`APP_URL` est déjà rempli, `APP_ENV=production` et `APP_DEBUG=false` aussi.

Dans `nano` : `Ctrl+O` puis `Entrée` pour enregistrer, `Ctrl+X` pour sortir.

```bash
chmod 600 .env
```

⚠️ **Ne passez jamais `APP_DEBUG` à `true` en ligne**, même cinq minutes pour
déboguer : la page d'erreur de Laravel affiche la trace **et toutes les
variables d'environnement**, donc vos mots de passe et vos clés.

### D3. Créer les tables

```bash
php artisan migrate --force
```

**Ce que vous devez voir** : une longue liste de migrations suivies de `DONE`.

**Si vous voyez `SQLSTATE[HY000] [1045] Access denied`** : les identifiants de
`DB_*` sont faux, ou l'utilisateur MySQL n'a pas été **rattaché** à la base
(cPanel > Bases de données MySQL > « Ajouter un utilisateur à une base »).

### D4. Installer les rôles et votre compte administrateur

```bash
php artisan config:clear
php artisan db:seed --force
```

⚠️ **`config:clear` d'abord, et ce n'est pas une précaution de principe.** Dès
qu'une configuration a été mise en cache, Laravel **ne relit plus le `.env`** :
vous pouvez y écrire ce que vous voulez, l'application continue de lire
l'ancienne version. Le symptôme accuse le fichier (« pourtant j'ai bien
renseigné le mot de passe ») alors que la cause est le cache.

**Ce que vous devez voir** : quelques lignes de seeders, dont un avertissement
disant que les données de démonstration sont ignorées. C'est voulu : votre base
reste vide de faux colloques.

⚠️ **Si vous voyez `ADMIN_PASSWORD est vide : refus de créer le
super-administrateur`**, c'est le garde-fou qui fonctionne. Remplissez
`ADMIN_PASSWORD` dans `.env` et relancez. Ce refus existe parce que, sans lui,
le compte à tous les pouvoirs serait créé avec le mot de passe du jeu de
démonstration, sur un site public.

### D5. Rendre les fichiers téléversés accessibles

```bash
php artisan storage:link
```

**Ce que vous devez voir** : `The [public/storage] link has been connected.`

Sans ce lien, les logos, les photos de profil et les modèles de documents
renvoient une erreur 404.

---

# Phase E. Construire l'habillage, puis mettre en cache

### E1. Construire les feuilles de style et les scripts

Le dossier `public/build/` n'est pas dans le dépôt : il se fabrique. o2switch
fournit Node sans qu'il soit nécessaire de créer une application Node.js.

```bash
cd ~/apps/geves
export PATH="/opt/alt/alt-nodejs24/root/usr/bin:$PATH"
node -v
npm ci
npm run build
```

**Ce que vous devez voir** : `v24.x` pour `node -v`, puis, à la fin du build,
`✓ built in ...` et une liste de fichiers sous `public/build/`.

⚠️ Sans cette étape, le site s'affiche **sans aucun style**, ou lève une erreur
« Vite manifest not found ».

Le build fini, les sources de Node ne servent plus :

```bash
rm -rf node_modules
```

### E2. Mettre la configuration en cache

```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache
```

⚠️ **À refaire après chaque modification du `.env`.** Une fois la configuration
en cache, Laravel **ne relit plus** le fichier `.env` : une correction qui n'est
pas suivie de `config:cache` n'a aucun effet, ce qui donne des heures de
débogage pour rien. C'est **la** source de confusion de ce déploiement : si une
commande vous dit qu'une valeur est vide alors qu'elle figure dans le `.env`,
lancez `php artisan config:clear` et recommencez.

### E3. Droits d'écriture

```bash
chmod -R 755 storage bootstrap/cache
```

Sur o2switch, PHP tourne sous votre propre compte : il n'y a pas d'utilisateur
`www-data` à qui donner des droits.

---

# Phase F. Les deux tâches automatiques

cPanel > **Tâches planifiées** (section Avancé). Ajoutez **deux** lignes.
Remplacez `UTILISATEUR`, et `php` par le chemin complet si vous en avez relevé
un au début.

### F1. Le planificateur, chaque minute

Réglage **Commun** : « Une fois par minute », ou les champs `* * * * *`.

```
cd /home/UTILISATEUR/apps/geves && php artisan schedule:run >> /dev/null 2>&1
```

Il porte l'envoi des e-mails programmés (chaque minute), la vérification des
paiements en ligne (10 min), le passage des visios en direct puis en terminé
(5 min) et les sauvegardes (vers 1h du matin).

### F2. Le travailleur de file d'attente, chaque minute

```
cd /home/UTILISATEUR/apps/geves && php artisan queue:work --stop-when-empty --max-time=55 --tries=3 >> /dev/null 2>&1
```

⚠️ **Sans cette seconde ligne, aucun e-mail ne partira jamais.** Dix des onze
types d'e-mail du projet sont des tâches de fond : l'application les écrit en
base et considère son travail fini. C'est un travailleur qui les expédie. Le
symptôme est trompeur : l'interface affiche « invitation envoyée », et rien
n'arrive.

`--stop-when-empty` et `--max-time=55` font que le processus se termine de
lui-même : un hébergement mutualisé n'autorise pas de démon permanent.

---

# Phase G. Vérifier avant d'annoncer l'ouverture

### G1. Le site répond, avec son habillage

Ouvrez `https://geves.univkara.tg`. La page d'accueil doit s'afficher **mise en
forme** (bandeau bleu dégradé, logo). Sans style, reprenez E1.

### G2. Les secrets ne sont pas exposés

Ouvrez `https://geves.univkara.tg/.env`.

**Vous devez obtenir une erreur 403 ou 404.** Si le contenu du fichier
s'affiche, **arrêtez tout** : la racine du document est fausse (phase C), et vos
identifiants sont publics. Corrigez, puis changez tous les mots de passe et
régénérez `APP_KEY`.

### G3. Connexion au back-office

Allez sur `https://geves.univkara.tg/admin` et connectez-vous avec
`ADMIN_EMAIL` et le mot de passe provisoire.

### G4. Refermer la porte

Une fois connecté, changez votre mot de passe depuis **Mon compte**. Puis, en
SSH :

```bash
nano .env          # vider ADMIN_PASSWORD (laisser la clé, sans valeur)
php artisan config:cache
```

Le mot de passe provisoire ne doit pas rester en clair dans un fichier.

### G5. Un e-mail part vraiment

Dans le back-office, invitez un participant à une adresse à vous.

**Si rien n'arrive au bout de deux minutes** :

```bash
cd ~/apps/geves
php artisan queue:work --stop-when-empty      # forcer un passage à la main
tail -n 40 storage/logs/laravel.log
```

Si l'e-mail part avec la commande manuelle, c'est le cron F2 qui est mal réglé.
Si le journal montre une erreur SMTP, ce sont les `MAIL_*` du `.env` (et pensez
à `config:cache` après correction).

### G6. Les attestations déclarent la bonne adresse

⚠️ **Étape obligatoire, et la plus facile à oublier.** Une attestation signée
inscrit **dans sa signature** l'adresse où la vérifier. Celles produites en
développement déclarent `geves.test`, qui ne mène nulle part : un vérificateur
extérieur tomberait sur une erreur.

```bash
php artisan attestations:keygen    # seulement si ATTESTATION_SIGNING_KEY est vide
                                   # recopier la graine affichée dans .env
php artisan config:cache
php artisan attestations:sign --force
```

⚠️ **Conservez la graine `ATTESTATION_SIGNING_KEY` ailleurs qu'en ligne.** La
perdre rendrait invérifiables toutes les attestations déjà délivrées.

### G7. Dernier contrôle

```bash
php artisan about
```

Vérifiez : `Environment: production`, `Debug Mode: OFF`, `Config: CACHED`, et la
connexion à la base.

---

# Plus tard : mettre à jour le site

À chaque nouvelle version, en SSH :

```bash
cd ~/apps/geves
php artisan down
git pull --ff-only
composer install --no-dev --optimize-autoloader --no-interaction
php artisan migrate --force
export PATH="/opt/alt/alt-nodejs24/root/usr/bin:$PATH" && npm ci && npm run build && rm -rf node_modules
php artisan config:cache && php artisan route:cache && php artisan view:cache && php artisan event:cache
php artisan queue:restart
php artisan up
```

⚠️ **La ligne `npm run build` n'est jamais facultative, même pour une
modification purement visuelle.** `public/build/` n'est pas dans le dépôt :
`git pull` rapporte le HTML neuf mais laisse en place la feuille de style
**déjà construite**. Le site ne tombe pas, il s'affiche de travers - une
section nouvelle apparaît en texte brut, sans cadres ni colonnes, pendant que
le reste de la page reste normal. C'est arrivé le 11/10/2026 sur le barème des
tarifs : le symptôme accuse la page, la cause est l'habillage resté en arrière.

Pour en avoir le cœur net après une mise à jour, cherchez dans l'habillage
construit une classe introduite par la version que vous venez de déployer :

```bash
grep -l "ukc-fee__amount" ~/apps/geves/public/build/assets/*.css
```

Un nom de fichier s'affiche : l'habillage est à jour. Rien ne s'affiche : le
build n'a pas tourné, relancez la ligne `npm ci && npm run build`.

⚠️ `queue:restart` n'est pas facultatif : un travailleur déjà lancé garde
l'ancien code en mémoire jusqu'à la fin de son passage, et traiterait les
nouvelles tâches avec l'ancienne version.

⚠️ `php artisan down` affiche une page de maintenance pendant l'opération. Sans
elle, un visiteur peut tomber sur un site dont le code est à jour mais pas la
base.

---

# En-têtes de sécurité HTTP

`public/.htaccess` pose les en-têtes communs sur les pages, fichiers et erreurs :
`nosniff`, `SAMEORIGIN`, politique de référent et permissions non utilisées.
Caméra et micro restent libres pour la visio intégrée, notamment Jitsi. La
syntaxe conditionnelle Apache de `Header` est évitée : o2switch peut utiliser
LiteSpeed, qui ne la traite pas toujours comme Apache.

Le middleware Laravel ne pose HSTS que pour `geves.univkara.tg`, si la
connexion **à l'origine** est elle-même en HTTPS, avec `max-age=300` pour la
première mise en service. Si Cloudflare sert le HTTPS au navigateur mais contacte
l'origine en HTTP, HSTS reste absent : passer d'abord Cloudflare en **Full
(strict)**, puis vérifier le certificat de l'origine. Ne pas ajouter en
parallèle une seconde règle HSTS Cloudflare sans retirer celle de Laravel. Ne
pas appliquer `includeSubDomains`/`preload` à `univkara.tg`.

`App\Http\Middleware\ContentSecurityPolicy` pose sur les réponses HTML une CSP
immédiate limitée (`base-uri`, `object-src`, `frame-ancestors`) et une politique
plus stricte en **Report-Only**. Les rapports vont à `POST /csp-report` (sans
CSRF, limité à 20 requêtes/minute/IP). Seuls la directive et le domaine de la
ressource bloquée sont inscrits dans les journaux, une fois par combinaison et
par heure ; aucune URL de page, paramètre ou charge utile brute n'est journalisée.

Après le déploiement, vérifier les en-têtes sur l'accueil, une page admin, une
page participant, un PDF, un fichier statique et une erreur HTTP. `curl -sSI`
permet de lire les en-têtes sans afficher le corps. Sur les pages HTML, vérifier
`Content-Security-Policy` et `Content-Security-Policy-Report-Only` séparément ;
sur les fichiers statiques, les en-têtes de `.htaccess` suffisent. Contrôler
Turnstile, FedaPay, Jitsi/LiveKit, le livre PDF et les éditeurs de modèles dans
le navigateur. Lire ensuite les entrées `CSP report-only violation` des journaux
Laravel et corriger les sources, scripts et styles intégrés avant de faire
respecter une CSP complète.

Si HSTS apparaît bien et que HTTPS reste stable, augmenter sa durée par paliers
(`300` → `86400` → `31536000` secondes). Ne pas augmenter la durée avant d'avoir
vérifié la production après chaque palier.

---

# Ce qui ne fonctionnera pas sur un mutualisé

- ⚠️ **La production des vidéos de prise en main.** Le rendu réclame **FFmpeg**
  et un processus long ; o2switch n'offre ni l'un ni l'autre. Les guides
  **texte** fonctionnent normalement, côté participant comme côté
  administration. Ne déclenchez pas la production vidéo depuis le back-office :
  la demande resterait en attente indéfiniment.
- **Les captures d'écran automatiques** des tutoriels, pour la même raison.
- **Les sauvegardes** écrivent sur le disque du compte. Surveillez la place, et
  prévoyez une copie **hors** de l'hébergement : une sauvegarde qui dort à côté
  de la base ne protège pas d'une perte du compte.
- **Pas de Redis** : sessions, cache et file d'attente passent par MySQL. C'est
  déjà ce que prévoit `.env.production.example`.
