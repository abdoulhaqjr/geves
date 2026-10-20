<?php

use App\Models\ProjectTaskDocument;
use App\Models\User;
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
