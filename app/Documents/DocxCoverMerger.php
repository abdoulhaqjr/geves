<?php

namespace App\Documents;

use DOMDocument;
use DOMElement;
use DOMXPath;
use RuntimeException;
use ZipArchive;

/** Remplace la première page du livret Word en conservant ses styles comme destination. */
final class DocxCoverMerger
{
    private const W = 'http://schemas.openxmlformats.org/wordprocessingml/2006/main';
    private const R = 'http://schemas.openxmlformats.org/officeDocument/2006/relationships';
    private const P = 'http://schemas.openxmlformats.org/package/2006/relationships';
    private const C = 'http://schemas.openxmlformats.org/package/2006/content-types';

    public static function merge(string $bookBinary, string $coverBinary): string
    {
        $book = self::unzip($bookBinary);
        $cover = self::unzip($coverBinary);
        $bookDoc = self::xml($book['word/document.xml'] ?? null, 'livret');
        $coverDoc = self::xml($cover['word/document.xml'] ?? null, 'couverture');
        $bookBody = $bookDoc->getElementsByTagNameNS(self::W, 'body')->item(0);
        $coverBody = $coverDoc->getElementsByTagNameNS(self::W, 'body')->item(0);
        if (! $bookBody || ! $coverBody) {
            throw new RuntimeException('Le livre ou sa couverture Word ne contient pas de corps de document.');
        }

        $bookXp = new DOMXPath($bookDoc);
        $bookXp->registerNamespace('w', self::W);
        $break = $bookXp->query('./w:p[.//w:br[@w:type="page"]]', $bookBody)->item(0);
        if (! $break) {
            throw new RuntimeException('La première page du modèle Word ne se termine pas par un saut de page.');
        }
        while ($bookBody->firstChild) {
            $node = $bookBody->firstChild;
            $isBreak = $node->isSameNode($break);
            $bookBody->removeChild($node);
            if ($isBreak) {
                break;
            }
        }

        $bookRels = self::xml($book['word/_rels/document.xml.rels'] ?? null, 'relations du livret');
        $coverRels = self::xml($cover['word/_rels/document.xml.rels'] ?? null, 'relations de la couverture');
        $types = self::xml($book['[Content_Types].xml'] ?? null, 'types du livret');
        $coverTypes = self::xml($cover['[Content_Types].xml'] ?? null, 'types de la couverture');
        $relationMap = [];
        $mediaIndex = 0;
        $reference = $bookBody->firstChild;

        foreach (iterator_to_array($coverBody->childNodes) as $sourceNode) {
            if ($sourceNode->nodeType !== XML_ELEMENT_NODE || $sourceNode->localName === 'sectPr') {
                continue;
            }
            $node = $bookDoc->importNode($sourceNode, true);
            $bookBody->insertBefore($node, $reference);
            $nodeXp = new DOMXPath($bookDoc);
            $nodeXp->registerNamespace('w', self::W);
            foreach (iterator_to_array($nodeXp->query('.//w:pStyle | .//w:rStyle | .//w:tblStyle | .//w:numPr | .//w:sectPr', $node)) as $style) {
                $style->parentNode?->removeChild($style);
            }
            // La séparation avec le corps du livret est ajoutée une seule fois ci-dessous.
            foreach (iterator_to_array($nodeXp->query('.//w:br[@w:type="page"]', $node)) as $pageBreak) {
                $pageBreak->parentNode?->removeChild($pageBreak);
            }

            foreach (self::elements($node) as $element) {
                foreach (iterator_to_array($element->attributes ?? []) as $attribute) {
                    if ($attribute->namespaceURI !== self::R) {
                        continue;
                    }
                    $oldId = $attribute->value;
                    if (! isset($relationMap[$oldId])) {
                        $relationMap[$oldId] = self::copyRelationship($oldId, $coverRels, $bookRels, $cover, $book, $types, $coverTypes, ++$mediaIndex);
                    }
                    $attribute->value = $relationMap[$oldId];
                }
            }
        }

        $separator = $bookDoc->createElementNS(self::W, 'w:p');
        $run = $bookDoc->createElementNS(self::W, 'w:r');
        $page = $bookDoc->createElementNS(self::W, 'w:br');
        $page->setAttributeNS(self::W, 'w:type', 'page');
        $run->appendChild($page);
        $separator->appendChild($run);
        $bookBody->insertBefore($separator, $reference);

        $book['word/document.xml'] = (string) $bookDoc->saveXML();
        $book['word/_rels/document.xml.rels'] = (string) $bookRels->saveXML();
        $book['[Content_Types].xml'] = (string) $types->saveXML();

        return self::rezip($book);
    }

    private static function copyRelationship(string $id, DOMDocument $source, DOMDocument $target, array $cover, array &$book, DOMDocument $types, DOMDocument $coverTypes, int $number): string
    {
        if (! preg_match('/^[A-Za-z][A-Za-z0-9_.-]{0,100}$/', $id)) {
            throw new RuntimeException('La couverture contient une relation Word invalide.');
        }
        $sourceXp = new DOMXPath($source);
        $sourceXp->registerNamespace('p', self::P);
        $relationship = $sourceXp->query('/p:Relationships/p:Relationship[@Id="'.$id.'"]')->item(0);
        if (! $relationship instanceof DOMElement) {
            throw new RuntimeException('La couverture contient une relation Word manquante.');
        }
        $targetPath = $relationship->getAttribute('Target');
        $external = $relationship->getAttribute('TargetMode') === 'External';
        if (! $external) {
            if (! str_starts_with($targetPath, 'media/') || str_contains($targetPath, '..')) {
                throw new RuntimeException('La couverture contient un élément Word qui ne peut pas être joint au livret.');
            }
            $sourcePath = 'word/'.$targetPath;
            if (! isset($cover[$sourcePath])) {
                throw new RuntimeException('Une image de la couverture est manquante.');
            }
            $extension = strtolower(pathinfo($targetPath, PATHINFO_EXTENSION));
            if (! preg_match('/^[a-z0-9]{1,8}$/', $extension)) {
                throw new RuntimeException('Le format d’une image de la couverture est invalide.');
            }
            $newName = 'cover-'.$number.'.'.$extension;
            $book['word/media/'.$newName] = $cover[$sourcePath];
            $targetPath = 'media/'.$newName;
            self::copyContentType($extension, $types, $coverTypes);
        }

        $newId = 'rIdCover'.bin2hex(random_bytes(6));
        $new = $target->createElementNS(self::P, 'Relationship');
        $new->setAttribute('Id', $newId);
        $new->setAttribute('Type', $relationship->getAttribute('Type'));
        $new->setAttribute('Target', $targetPath);
        if ($external) {
            $new->setAttribute('TargetMode', 'External');
        }
        $target->documentElement->appendChild($new);

        return $newId;
    }

    private static function copyContentType(string $extension, DOMDocument $target, DOMDocument $source): void
    {
        $targetXp = new DOMXPath($target);
        $targetXp->registerNamespace('c', self::C);
        if ($targetXp->query('/c:Types/c:Default[@Extension="'.$extension.'"]')->length > 0) {
            return;
        }
        $sourceXp = new DOMXPath($source);
        $sourceXp->registerNamespace('c', self::C);
        $entry = $sourceXp->query('/c:Types/c:Default[@Extension="'.$extension.'"]')->item(0);
        if (! $entry) {
            throw new RuntimeException('Le type d’une image de la couverture est inconnu.');
        }
        $target->documentElement->appendChild($target->importNode($entry, true));
    }

    /** @return list<DOMElement> */
    private static function elements(DOMElement $root): array
    {
        $result = [$root];
        foreach ($root->getElementsByTagName('*') as $child) {
            $result[] = $child;
        }

        return $result;
    }

    /** @return array<string, string> */
    private static function unzip(string $binary): array
    {
        $path = tempnam(sys_get_temp_dir(), 'livret-zip-');
        try {
            file_put_contents($path, $binary);
            $zip = new ZipArchive;
            if ($zip->open($path) !== true) {
                throw new RuntimeException('Le fichier Word est invalide.');
            }
            if ($zip->numFiles > 5000) {
                $zip->close();
                throw new RuntimeException('Le fichier Word contient trop d’éléments.');
            }
            $files = [];
            $expanded = 0;
            for ($i = 0; $i < $zip->numFiles; $i++) {
                $expanded += (int) ($zip->statIndex($i)['size'] ?? 0);
                if ($expanded > 200 * 1024 * 1024) {
                    $zip->close();
                    throw new RuntimeException('Le fichier Word dépasse la taille autorisée.');
                }
                $name = (string) $zip->getNameIndex($i);
                $files[$name] = (string) $zip->getFromIndex($i);
            }
            $zip->close();

            return $files;
        } finally {
            @unlink($path);
        }
    }

    private static function xml(?string $content, string $label): DOMDocument
    {
        $document = new DOMDocument;
        if ($content === null || ! @$document->loadXML($content, LIBXML_NONET)) {
            throw new RuntimeException('Le XML de '.$label.' est invalide.');
        }

        return $document;
    }

    /** @param array<string, string> $files */
    private static function rezip(array $files): string
    {
        $path = tempnam(sys_get_temp_dir(), 'livret-merge-');
        try {
            $zip = new ZipArchive;
            if ($zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
                throw new RuntimeException('Impossible de créer le livre Word.');
            }
            foreach ($files as $name => $content) {
                $zip->addFromString($name, $content);
            }
            $zip->close();
            $binary = file_get_contents($path);
            if ($binary === false) {
                throw new RuntimeException('Impossible de lire le livre Word fusionné.');
            }

            return $binary;
        } finally {
            @unlink($path);
        }
    }
}
