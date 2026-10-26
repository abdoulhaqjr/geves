<?php

use App\Models\ProjectTaskDocument;
use App\Models\User;
use App\Support\Realtime;
use Illuminate\Support\Facades\Broadcast;

/*
|--------------------------------------------------------------------------
| Autorisation des canaux temps réel
|--------------------------------------------------------------------------
|
| ⚠️⚠️ **CE FICHIER EST LA FRONTIÈRE, PAS LA PAGE.** Les frappes d'un document
| circulent en « événements client » : elles passent d'un navigateur à l'autre
| À TRAVERS le service WebSocket, sans repasser par PHP. Le seul moment où le
| serveur décide quelque chose, c'est ICI, quand un poste demande à rejoindre
| le canal. Une page bien gardée dont le canal ne l'est pas laisserait lire la
| rédaction en cours depuis la console du navigateur.
|
| ⚠️ Canal de PRÉSENCE : le tableau rendu ci-dessous est diffusé aux autres
| membres (c'est ce qui affiche « qui est en train d'écrire »). On n'y met donc
| que ce qui peut se voir - nom et initiales. Jamais d'adresse e-mail, jamais de
| rôle système.
*/

Broadcast::channel('ukc.doc.{document}', function (User $user, int $document) {
    $doc = ProjectTaskDocument::with('task')->find($document);

    if (! $doc || ! $doc->isVisibleTo($user)) {
        return false;
    }

    return [
        'id' => $user->id,
        'name' => $user->salutationName() ?: $user->name,
        // ⚠️ Dit au client s'il doit laisser écrire : l'éditeur passe en lecture
        // seule pour qui n'est pas associé à la tâche. Le serveur le redit de
        // toute façon à l'enregistrement - masquer un champ n'a jamais protégé
        // personne.
        'canWrite' => $doc->isEditableBy($user),
    ];
});

/*
 * Qui a GEVES ouvert, maintenant. Tout compte connecté y entre - c'est le
 * principe même d'un compteur de connectés - et les deux espaces y entrent
 * donc par leur guard respectif.
 *
 * ⚠️⚠️ **ON N'Y MET RIEN QUI DÉSIGNE QUELQU'UN.** Le tableau rendu ici est
 * diffusé à TOUS les autres membres, et ici ils sont l'ensemble des personnes
 * connectées à la plateforme : un nom, une adresse ou un rôle donnerait à
 * chaque participant la liste de qui est en ligne. On ne publie que
 * l'empreinte qui sert déjà d'identité de membre, et qui ne se remonte pas.
 *
 * ⚠️ **Un tableau VIDE serait un refus** : le socle n'autorise le canal que
 * sur une valeur vraie, et `[]` est faux en PHP.
 */
Broadcast::channel(Realtime::presenceChannel(), function (User $user) {
    return ['id' => $user->getAuthIdentifierForBroadcasting()];
}, ['guards' => ['account', 'web']]);

// Une notification ne peut être annoncée qu'au compte qui la possède. Les deux
// espaces utilisent le même modèle User, mais chacun a son propre guard.
Broadcast::channel('ukc.notifications.{userId}', function (User $user, string $userId) {
    return (string) $user->getKey() === $userId;
}, ['guards' => ['account', 'web']]);
