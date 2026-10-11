# Déploiement de GEVES sur o2switch

Cible : **https://geves.univkara.tg** (sous-domaine déjà créé, base de données
déjà préparée). Hébergement mutualisé cPanel, accès SSH et git disponibles.

Ce document se lit **dans l'ordre**. Chaque étape dit ce qu'elle fait et ce qui
casse si on la saute. Les valeurs à remplacer sont en `MAJUSCULES`.

---

## 0. Ce que l'hébergement doit fournir

| Besoin | Pourquoi | Vérification |
| --- | --- | --- |
| **PHP ≥ 8.4.1** | Le `composer.lock` a résolu **Symfony 8.x**, qui l'exige. Laravel 13 accepterait 8.3, mais pas ce verrou-ci. | `php -v` en SSH |
| Extensions `pdo_mysql`, `mbstring`, `openssl`, `tokenizer`, `xml`, `ctype`, `json`, `bcmath`, `fileinfo` | Socle Laravel | `php -m` |
| Extension **`sodium`** | Signature Ed25519 des attestations | `php -m \| grep sodium` |
| Extension **`zip`** | Livret des résumés (.docx, PhpWord) | `php -m \| grep zip` |
| Extension **`gd`** | QR codes et images des PDF | `php -m \| grep gd` |
| **Cron à la minute** | Les e-mails et la file d'attente en dépendent | cPanel > Tâches planifiées |
| **MySQL / MariaDB** | Les migrations sont portables, elles ont été écrites pour | cPanel > Bases de données |

⚠️ **Si `php -v` affiche moins que 8.4.1**, ne pas forcer avec
`--ignore-platform-reqs` : le code installé planterait à l'exécution. Il faut
re-verrouiller le projet sur Symfony 7.4 (`composer config platform.php 8.3`
puis `composer update`), **et relancer toute la suite de tests** sur ce verrou.
Demander avant de s'y engager.

---

## 1. Clé de déploiement GitHub (le dépôt est privé)

Sur le serveur, en SSH :

```bash
ssh-keygen -t ed25519 -C "geves-o2switch" -f ~/.ssh/geves_deploy -N ""
cat ~/.ssh/geves_deploy.pub
```

Copier la clé publique affichée, puis sur GitHub :
**dépôt `abdoulhaqjr/geves` > Settings > Deploy keys > Add deploy key**,
coller, **laisser « Allow write access » décoché** (le serveur ne doit jamais
pousser).

Puis déclarer la clé pour les connexions à GitHub :

```bash
cat >> ~/.ssh/config <<'EOF'
Host github.com
    IdentityFile ~/.ssh/geves_deploy
    IdentitiesOnly yes
EOF
chmod 600 ~/.ssh/config
ssh -T git@github.com   # doit répondre « Hi abdoulhaqjr/geves! You've successfully authenticated »
```

---

## 2. Récupérer le code

⚠️ **Le dépôt ne va PAS dans le dossier servi par le web.** Seul `public/` doit
être atteignable : sinon `.env`, `storage/` et `vendor/` sont téléchargeables.

```bash
mkdir -p ~/apps && cd ~/apps
git clone git@github.com:abdoulhaqjr/geves.git geves
cd ~/apps/geves
git log --oneline -1    # vérifier qu'on est bien sur master, à jour
```

---

## 3. Pointer le sous-domaine sur `public/`

cPanel > **Domaines** > ligne `geves.univkara.tg` > **Gérer** >
**Racine du document** :

```
/home/UTILISATEUR/apps/geves/public
```

⚠️ La valeur pré-remplie par cPanel est celle du dossier créé avec le
sous-domaine ; **elle est fausse pour une application Laravel**. Sans ce
changement, le visiteur voit l'arborescence du projet au lieu du site.

Le `public/.htaccess` livré par Laravel suffit : rien à écrire de plus.

---

## 4. Dépendances PHP

Repérer d'abord le bon binaire PHP (le PHP du CLI n'est pas forcément celui
choisi pour le web) :

```bash
php -v            # si ce n'est pas du 8.4+, chercher le chemin dans
                  # cPanel > Sélectionner la version de PHP
```

Puis :

```bash
cd ~/apps/geves
composer install --no-dev --optimize-autoloader --no-interaction
```

⚠️ `--no-dev` est volontaire : Pest, PHPStan et Pint n'ont rien à faire en
ligne. ⚠️ Ne **jamais** lancer `composer update` sur le serveur : il
résoudrait d'autres versions que celles testées.

---

## 5. Le fichier `.env`

```bash
cd ~/apps/geves
cp .env.production.example .env
php artisan key:generate
nano .env     # remplir : base de données, e-mail, ADMIN_EMAIL, ADMIN_PASSWORD
```

À remplir impérativement : `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`,
`MAIL_*`, `ADMIN_EMAIL`, `ADMIN_PASSWORD`. Le modèle commente chaque bloc.

⚠️ `APP_DEBUG=false` et `APP_ENV=production` : avec `true`, la page d'erreur
affiche la trace **et les variables d'environnement**, donc les mots de passe.

```bash
chmod 600 .env
```

---

## 6. Base de données

```bash
php artisan migrate --force
php artisan db:seed --force
```

`db:seed` installe les **rôles, les permissions et le super-administrateur**.
⚠️ Les données de démonstration sont automatiquement **ignorées** en
production (`DatabaseSeeder` le vérifie) : la base reste vide de faux colloques.

⚠️ Si `ADMIN_PASSWORD` est vide, le seeder **s'arrête avec une erreur** au lieu
de créer un compte « password ». C'est voulu. Remplir et relancer.

⚠️ Les relances ultérieures de `db:seed` ne retouchent jamais le mot de passe
d'un administrateur déjà en place.

---

## 7. Fichiers téléversés

```bash
php artisan storage:link
```

Crée `public/storage` → `storage/app/public` : sans lui, les logos, les photos
de profil et les modèles de documents renvoient 404.

---

## 8. Construire les feuilles de style et les scripts

`public/build/` n'est pas dans le dépôt : il faut le fabriquer sur le serveur.
o2switch fournit Node sans qu'on ait à créer d'application Node.js.

```bash
cd ~/apps/geves
export PATH="/opt/alt/alt-nodejs24/root/usr/bin:$PATH"
node -v          # doit afficher v24.x
npm ci
npm run build
```

⚠️ Sans cette étape, le site s'affiche **sans aucun style** : Laravel cherche
`public/build/manifest.json` et lève une erreur Vite.

Une fois le build fait, `node_modules/` ne sert plus à rien en ligne :
`rm -rf node_modules` libère de la place.

---

## 9. Mettre en cache la configuration

```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache
```

⚠️ **À refaire après chaque modification du `.env`** : une fois la
configuration en cache, le fichier `.env` n'est plus relu.

---

## 10. Droits d'écriture

```bash
cd ~/apps/geves
chmod -R 755 storage bootstrap/cache
```

Sur o2switch, PHP tourne sous votre compte : il n'y a pas de `www-data` à qui
donner des droits. Si une erreur « Permission denied » apparaît dans
`storage/logs/`, c'est le chemin, pas le propriétaire.

---

## 11. Les deux tâches planifiées

cPanel > **Tâches planifiées (cron)**. Remplacer `/usr/local/bin/php` par le
binaire PHP 8.4 relevé à l'étape 4.

**a) Le planificateur Laravel — toutes les minutes**

```
* * * * * cd /home/UTILISATEUR/apps/geves && /usr/local/bin/php artisan schedule:run >> /dev/null 2>&1
```

Il porte : l'envoi des e-mails programmés (chaque minute), la réconciliation
des paiements (10 min), le statut des visios (5 min) et les sauvegardes
(1h00-2h00).

**b) Le travailleur de file d'attente — toutes les minutes**

```
* * * * * cd /home/UTILISATEUR/apps/geves && /usr/local/bin/php artisan queue:work --stop-when-empty --max-time=55 --tries=3 >> /dev/null 2>&1
```

⚠️ **Sans ce second cron, aucun e-mail ne part.** Dix des onze classes d'e-mail
du projet sont des jobs (`ShouldQueue`) : elles sont écrites en base et
attendent un travailleur. `--stop-when-empty` et `--max-time=55` évitent le
processus permanent, interdit sur un mutualisé.

---

## 12. Re-signer les attestations

⚠️ **Étape obligatoire, et facile à oublier.** Une attestation signée inscrit
**l'adresse de vérification** dans sa signature. Celles produites en
développement déclarent `geves.test`, qui ne résout pour personne : un
vérificateur tiers tomberait sur une erreur.

```bash
php artisan attestations:keygen     # si ATTESTATION_SIGNING_KEY est vide
                                    # (reporter la graine affichée dans .env)
php artisan config:cache            # relire le .env
php artisan attestations:sign --force
```

`--force` re-signe **tout le stock**, y compris les attestations non signées.

⚠️ Conserver la graine `ATTESTATION_SIGNING_KEY` ailleurs qu'en ligne : la
perdre rend invérifiables toutes les attestations déjà délivrées.

---

## 13. Vérifications avant d'annoncer l'ouverture

- [ ] `https://geves.univkara.tg` répond, **avec les styles**
- [ ] `https://geves.univkara.tg/.env` renvoie **403 ou 404** (si le fichier
      s'affiche, la racine du document est fausse : reprendre l'étape 3)
- [ ] Connexion au back-office avec `ADMIN_EMAIL`
- [ ] **Changer le mot de passe depuis le profil**, puis vider `ADMIN_PASSWORD`
      du `.env` et refaire `php artisan config:cache`
- [ ] Un e-mail de test arrive (créer un participant et l'inviter) — sinon
      vérifier le cron (b) et `storage/logs/laravel.log`
- [ ] `php artisan about` : connexion base OK, cache de config actif
- [ ] Le certificat HTTPS couvre bien le sous-domaine (cPanel > SSL/TLS)

---

## 14. Mettre à jour le site plus tard

```bash
cd ~/apps/geves
php artisan down                      # page de maintenance
git pull --ff-only
composer install --no-dev --optimize-autoloader --no-interaction
php artisan migrate --force
export PATH="/opt/alt/alt-nodejs24/root/usr/bin:$PATH" && npm ci && npm run build
php artisan config:cache && php artisan route:cache && php artisan view:cache && php artisan event:cache
php artisan queue:restart             # les travailleurs relisent le code neuf
php artisan up
```

⚠️ `queue:restart` n'est pas facultatif : un travailleur déjà lancé garde
l'ancien code en mémoire jusqu'à la fin de son passage.

---

## 15. Ce qui ne fonctionnera pas sur un mutualisé

- ⚠️ **La production de vidéos de prise en main.** Le rendu réclame **FFmpeg**
  et un processus long ; o2switch n'offre ni l'un ni l'autre. Les guides
  **texte** fonctionnent normalement — seule la fabrication des vidéos est hors
  de portée. Ne pas la déclencher depuis le back-office.
- **Les captures d'écran automatiques** des tutoriels, pour la même raison
  (navigateur sans affichage).
- **Les sauvegardes** (`spatie/laravel-backup`) écrivent sur le disque du
  compte : surveiller la place, et prévoir une copie hors de l'hébergement.
- **Pas de Redis** : sessions, cache et file d'attente passent par MySQL. C'est
  prévu dans `.env.production.example`.
