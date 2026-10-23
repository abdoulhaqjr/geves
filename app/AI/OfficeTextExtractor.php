<?php

namespace App\AI;

use App\AI\Exceptions\AiUnavailableException;
use DOMDocument;
use DOMXPath;
use ZipArchive;

/** Lit localement le texte des exemples Office avant leur envoi à une IA. */
class OfficeTextExtractor
{
    public static function extract(string $mime, string $bytes): string
    {
        $path = tempnam(sys_get_temp_dir(), 'ai-office-');
        if ($path === false) {
            throw new AiUnavailableException('Impossible de lire la pièce jointe Office.');
        }
        if (file_put_contents($path, $bytes) === false) {
            @unlink($path);
            throw new AiUnavailableException('Impossible de lire la pièce jointe Office.');
        }

        $zip = new ZipArchive;
        $opened = false;
        try {
            if ($zip->open($path) !== true) {
                throw new AiUnavailableException('La pièce jointe Office est invalide.');
            }
            $opened = true;
            self::checkArchiveSize($zip);

            $text = str_contains($mime, 'wordprocessingml')
                ? self::docx($zip)
                : self::xlsx($zip);

            if ($text === '') {
                throw new AiUnavailableException('La pièce jointe Office ne contient pas de texte lisible.');
            }

            return mb_strlen($text) > 60000
                ? mb_substr($text, 0, 60000).'\n[Document tronqué après 60 000 caractères]'
                : $text;
        } finally {
            if ($opened) {
                $zip->close();
            }
            @unlink($path);
        }
    }

    /** Empêche qu'une petite archive Office épuise la mémoire après décompression. */
    private static function checkArchiveSize(ZipArchive $zip): void
    {
        if ($zip->numFiles > 1000) {
            throw new AiUnavailableException('La pièce jointe Office contient trop de fichiers.');
        }

        $expanded = 0;
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $size = $zip->statIndex($i)['size'] ?? null;
            if (! is_int($size) || $size < 0) {
                throw new AiUnavailableException('La pièce jointe Office est incomplète.');
            }
            $expanded += $size;
            if ($expanded > 32 * 1024 * 1024) {
                throw new AiUnavailableException('La pièce jointe Office est trop volumineuse après décompression.');
            }
        }
    }

    private static function docx(ZipArchive $zip): string
    {
        $xml = self::entry($zip, 'word/document.xml');
        $xpath = self::xpath($xml, 'w', 'http://schemas.openxmlformats.org/wordprocessingml/2006/main');
        $lines = [];
        foreach ($xpath->query('//w:p') ?: [] as $paragraph) {
            $parts = [];
            foreach ($xpath->query('.//w:t | .//w:tab', $paragraph) ?: [] as $node) {
                $parts[] = $node->localName === 'tab' ? "\t" : $node->textContent;
            }
            if (($line = trim(implode('', $parts))) !== '') {
                $lines[] = $line;
            }
        }

        return implode("\n", $lines);
    }

    private static function xlsx(ZipArchive $zip): string
    {
        $namespace = 'http://schemas.openxmlformats.org/spreadsheetml/2006/main';
        $shared = [];
        if ($zip->locateName('xl/sharedStrings.xml') !== false) {
            $strings = self::xpath(self::entry($zip, 'xl/sharedStrings.xml'), 'x', $namespace);
            foreach ($strings->query('//x:si') ?: [] as $item) {
                $parts = [];
                foreach ($strings->query('.//x:t', $item) ?: [] as $node) {
                    $parts[] = $node->textContent;
                }
                $shared[] = implode('', $parts);
            }
        }

        $lines = [];
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $name = $zip->getNameIndex($i);
            if (! is_string($name) || ! preg_match('~^xl/worksheets/sheet\d+\.xml$~', $name)) {
                continue;
            }
            $sheet = self::xpath(self::entry($zip, $name), 'x', $namespace);
            $lines[] = '['.$name.']';
            foreach ($sheet->query('//x:row') ?: [] as $row) {
                $cells = [];
                foreach ($sheet->query('./x:c', $row) ?: [] as $cell) {
                    $valueNodes = $sheet->query('./x:v | ./x:is', $cell);
                    $valueNode = $valueNodes === false ? null : $valueNodes->item(0);
                    $value = trim($valueNode === null ? '' : $valueNode->textContent);
                    $cells[] = $cell->attributes?->getNamedItem('t')?->nodeValue === 's'
                        ? ($shared[(int) $value] ?? '')
                        : $value;
                }
                if ($cells !== []) {
                    $lines[] = implode("\t", $cells);
                }
            }
        }

        return trim(implode("\n", $lines));
    }

    private static function entry(ZipArchive $zip, string $name): string
    {
        $stat = $zip->statName($name);
        if ($stat === false || $stat['size'] > 8 * 1024 * 1024) {
            throw new AiUnavailableException('La pièce jointe Office est trop volumineuse ou incomplète.');
        }
        $contents = $zip->getFromName($name);
        if ($contents === false) {
            throw new AiUnavailableException('La pièce jointe Office est incomplète.');
        }

        return $contents;
    }

    private static function xpath(string $xml, string $prefix, string $namespace): DOMXPath
    {
        $document = new DOMDocument;
        if (! @$document->loadXML($xml, LIBXML_NONET)) {
            throw new AiUnavailableException('La pièce jointe Office contient un XML invalide.');
        }
        $xpath = new DOMXPath($document);
        $xpath->registerNamespace($prefix, $namespace);

        return $xpath;
    }
}
