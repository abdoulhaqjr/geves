{{--
    Pages RÉSERVÉES pour les documents ajoutés par le gestionnaire.

    ⚠️⚠️ Ce ne sont pas des pages blanches décoratives : elles tiennent la place
    exacte des pages ajoutées pendant que dompdf numérote le livre. Sans elles,
    une page glissée après coup décalerait tous les numéros imprimés qui la
    suivent - et rien ne le signalerait.

    ⚠️ Le lien invisible est le REPÈRE : dompdf en fait une annotation que
    pdf-lib relit pour savoir sur quelle page la réservation est tombée. C'est
    la seule mesure fiable, la longueur d'une section dépendant de son contenu.
    Ne pas le retirer, ni le rendre visible.

    ⚠️ Le bloc est posé HORS de la condition d'affichage de sa section : un
    avant-propos vide ne rend rien, mais la page qu'on a rangée « avant
    l'avant-propos » doit tout de même paraître, à la place où cette section
    serait venue. Autrement, la page disparaîtrait du livre en silence.
--}}
@foreach (($inserts[$slot] ?? []) as $reserve)
    @for ($k = 0; $k < $reserve['pages']; $k++)
        <div class="page-break">
            <a href="{{ $reserve['uri'] }}/{{ $k }}"
               style="color:#fff;text-decoration:none;font-size:1pt;">.</a>
        </div>
    @endfor
@endforeach
