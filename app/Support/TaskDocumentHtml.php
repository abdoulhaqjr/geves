<?php

namespace App\Support;

use DOMDocument;
use DOMElement;
use DOMNode;
use DOMXPath;

/**
 * Assainissement du HTML d'un document de tâche.
 *
 * ⚠️⚠️ **POURQUOI PAS `App\Support\Html` :** sa liste blanche sert les RÉSUMÉS
 * scientifiques - gras, italique, listes, rien d'autre, aucun attribut. Un
 * document de travail porte des titres, des liens et des citations ; l'élargir
 * élargirait du même coup la surface des résumés, déposés par des milliers de
 * personnes du dehors. Deux usages, deux listes.
 *
 * ⚠️⚠️ **ON MARCHE DANS L'ARBRE, ON NE FILTRE PAS À L'EXPRESSION RÉGULIÈRE.**
 * Dès qu'un attribut est autorisé - ici `href`, sans lequel un document de
 * travail ne vaut plus grand-chose - un filtre par motif devient une liste
 * noire, donc fausse par construction : il suffit d'un `<a\n href=...>`, d'une
 * entité ou d'un attribut en casse mixte pour passer au travers. `DOMDocument`
 * lit le balisage comme le navigateur le lira.
 *
 * ⚠️ Une balise refusée est **DÉPLIÉE**, pas supprimée : son texte reste. Jeter
 * le nœud entier ferait disparaître un paragraphe parce qu'il portait une balise
 * inconnue - et c'est le travail de quelqu'un.
 */
final class TaskDocumentHtml
{
    /** Balises conservées (celles que produit l'éditeur). */
    private const ALLOWED = [
        'p', 'br', 'hr',
        'h1', 'h2', 'h3', 'h4',
        'strong', 'b', 'em', 'i', 'u', 's', 'code',
        'ul', 'ol', 'li',
        'blockquote', 'pre',
        'a',
    ];

    /**
     * Seuls attributs conservés, balise par balise.
     *
     * @var array<string, list<string>>
     */
    private const ATTRIBUTES = ['a' => ['href']];

    /**
     * Attributs qui portent une ADRESSE, et doivent donc passer le contrôle de
     * schéma.
     *
     * ⚠️ Liste SÉPARÉE de celle des attributs conservés, bien qu'elles se
     * confondent aujourd'hui : « cet attribut est autorisé » et « cet attribut
     * est une URL » sont deux questions, et le jour où une seconde balise garde
     * un attribut quelconque, confondre les deux ferait sauter le contrôle de
     * schéma sans que rien ne le signale.
     *
     * @var array<string, list<string>>
     */
    private const URL_ATTRIBUTES = ['a' => ['href']];

    /**
     * Schémas d'URL acceptés dans un `href`.
     *
     * ⚠️ **Liste blanche, et `javascript:` n'y est pas.** Un lien est le seul
     * endroit du document où le rédacteur fournit une adresse ; sans ce
     * contrôle, « cliquer sur le lien » exécuterait son code chez tous les
     * autres rédacteurs, dans le back-office.
     */
    private const SCHEMES = ['http', 'https', 'mailto'];

    /** Plafond de taille, en octets du HTML assaini. */
    public const MAX_BYTES = 2_000_000;

    public static function clean(?string $html): string
    {
        $html = trim((string) $html);
        if ($html === '') {
            return '';
        }

        $doc = new DOMDocument;

        // ⚠️ Sans le préambule d'encodage, DOMDocument lit l'UTF-8 comme du
        // latin-1 et rend « Ã© » là où il y avait « é » - sur un document que
        // des gens viennent d'écrire.
        $ok = @$doc->loadHTML(
            '<?xml encoding="UTF-8"?><div id="ukc-racine">'.$html.'</div>',
            LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD | LIBXML_NOERROR | LIBXML_NOWARNING
        );

        if (! $ok) {
            return '';
        }

        // ⚠️ Pas `getElementById()` : sans DTD déclarant l'attribut ID - et on
        // charge justement sans DTD - il rend `null`, donc un document VIDE.
        $trouves = (new DOMXPath($doc))->query('//div[@id="ukc-racine"]');
        $racine = $trouves === false ? null : $trouves->item(0);
        if (! $racine instanceof DOMElement) {
            return '';
        }

        self::nettoieEnfants($racine);

        $sortie = '';
        foreach ($racine->childNodes as $enfant) {
            $sortie .= $doc->saveHTML($enfant);
        }

        return mb_strcut(trim($sortie), 0, self::MAX_BYTES);
    }

    /** Parcourt les enfants d'un nœud en les assainissant (copie : l'arbre bouge). */
    private static function nettoieEnfants(DOMNode $parent): void
    {
        foreach (iterator_to_array($parent->childNodes) as $noeud) {
            self::nettoie($noeud);
        }
    }

    private static function nettoie(DOMNode $noeud): void
    {
        // Texte : rien à faire, il est échappé à la sérialisation.
        if (! $noeud instanceof DOMElement) {
            // ⚠️ Commentaires et instructions de traitement partent : un
            // commentaire conditionnel reste du code pour certains moteurs.
            if ($noeud->nodeType === XML_COMMENT_NODE || $noeud->nodeType === XML_PI_NODE) {
                $noeud->parentNode?->removeChild($noeud);
            }

            return;
        }

        $nom = strtolower($noeud->nodeName);

        // ⚠️ `script` et `style` sont les deux seules balises qu'on jette AVEC
        // leur contenu : déplier un script rendrait son code visible comme du
        // texte, mais déplier un style recracherait des règles CSS en plein
        // milieu du document.
        if ($nom === 'script' || $nom === 'style') {
            $noeud->parentNode?->removeChild($noeud);

            return;
        }

        self::nettoieEnfants($noeud);

        if (! in_array($nom, self::ALLOWED, true)) {
            self::deplie($noeud);

            return;
        }

        $gardes = self::ATTRIBUTES[$nom] ?? [];
        foreach (iterator_to_array($noeud->attributes ?? []) as $attribut) {
            $cle = strtolower($attribut->nodeName);
            if (! in_array($cle, $gardes, true)) {
                $noeud->removeAttribute($attribut->nodeName);

                continue;
            }
            $porteUneUrl = in_array($cle, self::URL_ATTRIBUTES[$nom] ?? [], true);
            if ($porteUneUrl && ! self::lienSur((string) $attribut->nodeValue)) {
                $noeud->removeAttribute($attribut->nodeName);
            }
        }

        // Un lien vers l'extérieur ne doit pas donner la main sur notre onglet.
        if ($nom === 'a' && $noeud->hasAttribute('href')) {
            $noeud->setAttribute('rel', 'noopener nofollow ugc');
            $noeud->setAttribute('target', '_blank');
        }
    }

    /** Remplace un élément par ses enfants (le texte survit, pas la balise). */
    private static function deplie(DOMElement $element): void
    {
        $parent = $element->parentNode;
        if (! $parent) {
            return;
        }

        while ($element->firstChild) {
            $parent->insertBefore($element->firstChild, $element);
        }

        $parent->removeChild($element);
    }

    private static function lienSur(string $href): bool
    {
        $href = trim($href);
        if ($href === '') {
            return false;
        }

        // Lien relatif ou ancre : il reste sur la plateforme.
        if (str_starts_with($href, '/') || str_starts_with($href, '#')) {
            return true;
        }

        $scheme = strtolower((string) parse_url($href, PHP_URL_SCHEME));

        return in_array($scheme, self::SCHEMES, true);
    }
}
