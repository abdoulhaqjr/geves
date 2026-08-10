<?php

namespace App\Support;

use Symfony\Component\HttpFoundation\Response;
use ZipArchive;

/**
 * Générateur de classeur Excel (.xlsx) en PHP pur — aucune dépendance runtime.
 * Produit un fichier OOXML SpreadsheetML minimal mais valide : plusieurs
 * feuilles, ligne d'en-tête en gras, cellules texte (chaînes en ligne) ou
 * numériques (détection automatique des int/float).
 *
 * Usage :
 *   (new XlsxWriter())
 *       ->sheet('Synthèse', ['Indicateur', 'Valeur'], [['Participants', 42]])
 *       ->sheet('Paiements', $headers, $rows)
 *       ->download('rapport.xlsx');
 */
class XlsxWriter
{
    /** @var array<int, array{name:string, rows:array<int, array<int, mixed>>, hasHeader:bool}> */
    private array $sheets = [];

    /**
     * Ajoute une feuille. Les valeurs int/float sont écrites en numérique,
     * tout le reste en texte.
     *
     * @param  list<string>  $headers  Ligne d'en-tête (vide = aucune)
     * @param  array<int, array<int|string, mixed>>  $rows
     */
    public function sheet(string $name, array $headers, array $rows): self
    {
        $data = [];

        if ($headers !== []) {
            $data[] = array_values($headers);
        }

        foreach ($rows as $row) {
            $data[] = array_values($row);
        }

        $this->sheets[] = [
            'name' => $this->safeName($name),
            'rows' => $data,
            'hasHeader' => $headers !== [],
        ];

        return $this;
    }

    /** Contenu binaire du classeur .xlsx. */
    public function contents(): string
    {
        if ($this->sheets === []) {
            $this->sheet('Feuille1', [], []);
        }

        $tmp = tempnam(sys_get_temp_dir(), 'ukc_xlsx_');
        $zip = new ZipArchive;
        $zip->open($tmp, ZipArchive::OVERWRITE);

        $zip->addFromString('[Content_Types].xml', $this->contentTypes());
        $zip->addFromString('_rels/.rels', $this->rootRels());
        $zip->addFromString('xl/workbook.xml', $this->workbook());
        $zip->addFromString('xl/_rels/workbook.xml.rels', $this->workbookRels());
        $zip->addFromString('xl/styles.xml', $this->styles());

        foreach ($this->sheets as $i => $sheet) {
            $zip->addFromString('xl/worksheets/sheet'.($i + 1).'.xml', $this->worksheet($sheet));
        }

        $zip->close();
        $contents = (string) file_get_contents($tmp);
        @unlink($tmp);

        return $contents;
    }

    /** Réponse HTTP de téléchargement. */
    public function download(string $filename): Response
    {
        return response($this->contents(), 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="'.addslashes($filename).'"',
        ]);
    }

    // --- Fabrication des parties OOXML -------------------------------------

    private function contentTypes(): string
    {
        $overrides = '';
        foreach ($this->sheets as $i => $sheet) {
            $overrides .= '<Override PartName="/xl/worksheets/sheet'.($i + 1)
                .'.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>';
        }

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
            .'<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
            .'<Default Extension="xml" ContentType="application/xml"/>'
            .'<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>'
            .'<Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>'
            .$overrides
            .'</Types>';
    }

    private function rootRels(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            .'<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>'
            .'</Relationships>';
    }

    private function workbook(): string
    {
        $sheets = '';
        foreach ($this->sheets as $i => $sheet) {
            $sheets .= '<sheet name="'.$this->esc($sheet['name']).'" sheetId="'.($i + 1)
                .'" r:id="rId'.($i + 1).'"/>';
        }

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"'
            .' xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
            .'<sheets>'.$sheets.'</sheets></workbook>';
    }

    private function workbookRels(): string
    {
        $rels = '';
        $count = count($this->sheets);
        foreach ($this->sheets as $i => $sheet) {
            $rels .= '<Relationship Id="rId'.($i + 1)
                .'" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet"'
                .' Target="worksheets/sheet'.($i + 1).'.xml"/>';
        }
        // La feuille de styles porte un identifiant distinct des feuilles.
        $rels .= '<Relationship Id="rId'.($count + 1)
            .'" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>';

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            .$rels.'</Relationships>';
    }

    private function styles(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            .'<fonts count="2">'
            .'<font><sz val="11"/><name val="Calibri"/></font>'
            .'<font><b/><sz val="11"/><name val="Calibri"/></font>'
            .'</fonts>'
            .'<fills count="2"><fill><patternFill patternType="none"/></fill>'
            .'<fill><patternFill patternType="gray125"/></fill></fills>'
            .'<borders count="1"><border/></borders>'
            .'<cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>'
            .'<cellXfs count="2">'
            .'<xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/>'
            .'<xf numFmtId="0" fontId="1" fillId="0" borderId="0" xfId="0" applyFont="1"/>'
            .'</cellXfs>'
            .'</styleSheet>';
    }

    /** @param array{name:string, rows:array<int, array<int, mixed>>, hasHeader:bool} $sheet */
    private function worksheet(array $sheet): string
    {
        $rowsXml = '';
        foreach ($sheet['rows'] as $r => $cells) {
            $isHeader = $sheet['hasHeader'] && $r === 0;
            $cellsXml = '';
            foreach ($cells as $c => $value) {
                $ref = $this->columnLetter($c).($r + 1);
                $cellsXml .= $this->cell($ref, $value, $isHeader);
            }
            $rowsXml .= '<row r="'.($r + 1).'">'.$cellsXml.'</row>';
        }

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            .'<sheetData>'.$rowsXml.'</sheetData></worksheet>';
    }

    private function cell(string $ref, mixed $value, bool $header): string
    {
        $style = $header ? ' s="1"' : '';

        if (is_int($value) || is_float($value)) {
            return '<c r="'.$ref.'"'.$style.'><v>'.$value.'</v></c>';
        }

        $text = $this->esc((string) $value);

        return '<c r="'.$ref.'"'.$style.' t="inlineStr"><is><t xml:space="preserve">'.$text.'</t></is></c>';
    }

    private function columnLetter(int $index): string
    {
        $letter = '';
        $index++;
        while ($index > 0) {
            $mod = ($index - 1) % 26;
            $letter = chr(65 + $mod).$letter;
            $index = intdiv($index - 1, 26);
        }

        return $letter;
    }

    private function safeName(string $name): string
    {
        $name = str_replace(['[', ']', ':', '*', '?', '/', '\\'], ' ', $name);

        return mb_substr(trim($name) ?: 'Feuille', 0, 31);
    }

    private function esc(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_XML1, 'UTF-8');
    }
}
