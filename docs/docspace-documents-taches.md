# Documents de travail dans ONLYOFFICE DocSpace

Un nouveau document de tâche est un DOCX, XLSX, PPTX ou TXT chez DocSpace
(21/10/2026) : l'ancien éditeur texte Tiptap/Yjs et le pont ONLYOFFICE Docs
auto-hébergé ont été retirés. Ces fichiers sont créés ou importés directement
dans DocSpace. GEVES conserve l'identifiant du fichier, le dossier de la tâche et le
jeton du lien d'édition de chaque fichier (chiffré en base). Le SDK Embed ouvre le fichier
dans la page du projet. Les personnes sans droit de rédaction dans GEVES peuvent
télécharger le fichier, mais ne reçoivent pas le jeton d'édition.

## Mise en service

1. Utiliser une salle personnalisée dédiée aux documents de travail GEVES.
   La salle transmise le 20 octobre 2026, « geves.univkara.tg », porte
   l'identifiant `4706751` ; l'API confirme qu'il s'agit d'une salle
   personnalisée. Les dossiers de tâche seront créés sous cette salle.
2. Dans **Paramètres → Outils pour développeurs → Embed SDK**, autoriser les
   origines exactes `https://gesco.test` et `https://geves.univkara.tg`.
   Ces deux origines ont été ajoutées le 20 octobre 2026 après autorisation.
   L'API confirme aussi la conservation des entrées préexistantes
   (`https://univkara.tg`, `geves.test` et
   `https://259e29d8.docs.onlyoffice.com`).
3. La clé API transmise dans la conversation est utilisée temporairement dans
   le `.env` local, ignoré par Git. La remplacer dans chaque environnement par
   une nouvelle clé avec droit d'écriture **avant** de révoquer l'ancienne ;
   sinon les opérations DocSpace s'arrêteront. Ne jamais placer la clé dans le
   navigateur ou dans le dépôt Git.
4. Renseigner `DOCSPACE_URL`, `DOCSPACE_API_KEY` et `DOCSPACE_ROOM_ID` dans
   l'environnement du serveur. Sans ces trois valeurs, **aucun document de tâche
   ne peut être créé** : le panneau désactive ses deux boutons et le serveur
   refuse en nommant ce qui manque.
5. Après autorisation explicite des liens externes d'édition pour les fichiers
   de tâche, définir `DOCSPACE_FILE_LINKS_ENABLED=true`. La valeur par défaut
   est `false` et empêche toute création automatique de nouveau lien.
6. Appliquer les migrations `2026_10_20_000300`, `2026_10_20_000400` et
   `2026_10_20_000500`, puis
   recharger la configuration Laravel si elle est mise en cache. Elles ont été
   appliquées sur la base locale ; elles restent à appliquer en production.

Lorsqu'un document est créé, GEVES crée au besoin un dossier dans cette salle,
un fichier DOCX, XLSX, PPTX ou TXT et un lien d'édition vérifié pour ce fichier.
La création d'un TXT utilise l'API texte de DocSpace avec une ligne vide initiale.
Les importations
jusqu'à 20 Mo sont acceptées ; au-delà de 10 Mo, elles utilisent l'API de transfert
par morceaux. Un import est jugé sur son **contenu** et non sur son extension.
Renommer un document dans GEVES renomme le fichier dans DocSpace ; supprimer un
document retire le fichier, et supprimer la tâche retire aussi son dossier.

Les documents texte rédigés avant cette date avec l'ancien éditeur restent
**consultables en lecture seule** sur leur page, versions comprises. Plus rien ne
les écrit, et il n'existe plus de transfert automatique vers DocSpace : pour
reprendre ce travail, on crée un nouveau fichier adapté et on y recopie ce qui doit
continuer.

## Accès et limites

Le lien d'édition est partagé par document entre les collaborateurs autorisés
de la tâche : DocSpace ne distingue pas de façon
fiable les collaborateurs GEVES qui utilisent ce même jeton. Une personne qui
a conservé le lien peut encore l'utiliser hors de GEVES tant que le lien n'est
pas révoqué dans DocSpace. Le contrôle des droits dans GEVES empêche l'affichage
du jeton aux lecteurs, mais ne révoque pas un jeton déjà divulgué. Le retrait d'une personne associée à la
tâche **renouvelle d'office** le lien de chacun de ses documents, dans la requête
même ; un administrateur peut aussi le renouveler à la main depuis la page du
document, pour ce qu'aucune règle ne couvre (un lien recopié dans un courriel).
Le nouveau lien est vérifié avant que l'ancien soit révoqué. Les documents créés
avec l'ancien lien de dossier reçoivent un lien de fichier lors de leur première
ouverture après activation de `DOCSPACE_FILE_LINKS_ENABLED`. Lorsque tous les
fichiers d'une tâche ont été migrés, GEVES révoque l'ancien lien de dossier.

Le lien de salle communiqué donnait auparavant le droit **Édition**
(`access=10`) sur toute la salle. Il a été révoqué le 20 octobre 2026 après
autorisation ; l'API ne liste plus aucun lien sur la salle. L'ancienne URL
partagée ne doit plus être utilisée. Les liens d'édition de dossier créés par
GEVES resteront distincts par tâche. Une personne déjà invitée comme membre de
la salle dans DocSpace peut toutefois conserver des droits propres à son
compte ; la révocation du lien ne supprime pas une telle invitation.

Le SDK exige un lien de fichier pour `initEditor()` ; un jeton de lien de dossier
ne suffit pas. Une réponse 403 de l'API signale notamment que la clé ou la salle
ne permet pas ce type de lien. Aucun secret API n'est exposé au JavaScript :
seul le `requestToken` du document est envoyé au SDK.
