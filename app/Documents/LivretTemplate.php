<?php

namespace App\Documents;

use App\Models\Event;
use App\Models\Submission;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\Settings;
use PhpOffice\PhpWord\Shared\Converter;
use PhpOffice\PhpWord\Writer\Word2007;
use Symfony\Component\HttpFoundation\Response;

/**
 * Génération du livre des résumés À PARTIR D'UN MODÈLE .docx téléversé (logique du
 * projet source uk-colloques, `ukc_bt_render`) : l'administrateur personnalise la
 * couverture, la charte et les pages liminaires dans Word en conservant des balises
 * entre doubles crochets ; à la génération, on ouvre le modèle et on remplace :
 *  - les balises « bloc » seules sur leur ligne ([[AVANT_PROPOS]], [[COMITES]],
 *    [[SOMMAIRE]], [[PROGRAMME]], [[RESUMES]]) par le fragment OOXML généré ;
 *  - les balises « en ligne » ([[TITRE]], [[EDITION]], [[THEME]], [[DATES]],
 *    [[LIEU]], [[ANNEE]], [[NB_RESUMES]]) par leur valeur.
 * Le design du modèle est conservé ; le contenu vient de {@see LivretDocx}.
 */
class LivretTemplate
{
    /** Balise bloc (texte seul du paragraphe) => nom de bloc pour LivretDocx::blockXml(). */
    private const BLOCK_TAGS = [
        '[[AVANT_PROPOS]]' => 'AVANT_PROPOS',
        '[[COMITES]]' => 'COMITES',
        '[[SOMMAIRE]]' => 'SOMMAIRE',
        '[[PROGRAMME]]' => 'PROGRAMME',
        '[[RESUMES]]' => 'RESUMES',
    ];

    /**
     * @param  Collection<int|string, \Illuminate\Database\Eloquent\Collection<int, Submission>>  $groups
     */
    public function __construct(
        private readonly Event $event,
        private readonly Collection $groups,
        private readonly string $templatePath,
        private readonly bool $harmonize = false,
    ) {}

    /** Réponse HTTP de téléchargement du .docx fusionné. */
    public function download(string $filename): Response
    {
        $binary = $this->render();

        return response()->streamDownload(function () use ($binary) {
            echo $binary;
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        ]);
    }

    /** Fusionne le contenu généré dans le modèle et renvoie le binaire .docx. */
    public function render(): string
    {
        $path = Storage::disk('public')->path($this->templatePath);

        $zin = new \ZipArchive;
        if (! is_file($path) || $zin->open($path) !== true) {
            throw new \RuntimeException('Modèle de livret introuvable ou illisible.');
        }
        $files = [];
        for ($i = 0; $i < $zin->numFiles; $i++) {
            $name = (string) $zin->getNameIndex($i);
            $files[$name] = (string) $zin->getFromIndex($i);
        }
        $zin->close();

        if (! isset($files['word/document.xml'])) {
            throw new \RuntimeException('Modèle .docx invalide (word/document.xml manquant).');
        }

        $livret = new LivretDocx($this->event, $this->groups, $this->harmonize);
        $inline = $livret->inlineValues();

        $dom = new \DOMDocument;
        $dom->preserveWhiteSpace = true;
        if (! @$dom->loadXML($files['word/document.xml'])) {
            throw new \RuntimeException('Le document.xml du modèle est illisible.');
        }
        $xp = new \DOMXPath($dom);
        $xp->registerNamespace('w', LivretDocx::WNS);

        $blockCache = [];
        foreach (iterator_to_array($xp->query('//w:body//w:p')) as $p) {
            /** @var \DOMElement $p */
            $text = $this->paragraphText($xp, $p);
            $trim = trim($text);

            // 1) Balise bloc seule sur sa ligne -> remplace tout le paragraphe.
            if (isset(self::BLOCK_TAGS[$trim])) {
                $tag = self::BLOCK_TAGS[$trim];
                $xmlFrag = $blockCache[$tag] ??= $livret->blockXml($tag);
                foreach ($this->importFragment($dom, $xmlFrag) as $node) {
                    $p->parentNode?->insertBefore($node, $p);
                }
                $p->parentNode?->removeChild($p);

                continue;
            }

            // 2) Balises en ligne (ou balise bloc mal placée dans du texte).
            $hasTag = false;
            foreach (array_keys($inline) as $tok) {
                if (str_contains($text, $tok)) {
                    $hasTag = true;
                    break;
                }
            }
            foreach (array_keys(self::BLOCK_TAGS) as $tok) {
                if (str_contains($text, $tok)) {
                    $hasTag = true;
                    break;
                }
            }
            if (! $hasTag) {
                continue;
            }

            $new = str_replace(array_keys($inline), array_values($inline), $text);
            // Balises bloc mal placées dans un texte : retirées (contenu injecté ailleurs).
            $new = str_replace(array_keys(self::BLOCK_TAGS), '', $new);

            // Redistribue : le 1er <w:t> reçoit tout le texte, les autres sont vidés.
            $tnodes = iterator_to_array($xp->query('.//w:t', $p));
            if ($tnodes !== []) {
                /** @var \DOMElement $first */
                $first = $tnodes[0];
                $first->nodeValue = $new;
                $first->setAttribute('xml:space', 'preserve');
                for ($k = 1, $c = count($tnodes); $k < $c; $k++) {
                    $tnodes[$k]->nodeValue = '';
                }
            }
        }

        $files['word/document.xml'] = (string) $dom->saveXML();
        $files = $this->ensureUpdateFields($files);

        return $this->rezip($files);
    }

    /** Construit un modèle de base (.docx) à balises, à télécharger puis personnaliser. */
    public static function baseModel(): PhpWord
    {
        Settings::setOutputEscapingEnabled(true);
        $doc = new PhpWord;
        $doc->setDefaultFontName('Times New Roman');
        $doc->setDefaultFontSize(11);
        $section = $doc->addSection([
            'marginTop' => Converter::cmToTwip(2.2), 'marginBottom' => Converter::cmToTwip(2.2),
            'marginLeft' => Converter::cmToTwip(2.2), 'marginRight' => Converter::cmToTwip(2.2),
        ]);

        // Couverture d'exemple (librement modifiable dans Word).
        $banner = $section->addTable();
        $banner->addRow((int) Converter::pointToTwip(6));
        foreach (['223EA4', 'FFD700', '009933'] as $color) {
            $banner->addCell((int) Converter::cmToTwip(4), ['bgColor' => $color])->addText('');
        }
        $section->addTextBreak(2);
        $section->addText('[[TITRE]]', ['bold' => true, 'size' => 24, 'color' => '111827'], ['alignment' => 'center']);
        $section->addText('[[EDITION]]', ['size' => 13, 'color' => '6B7280'], ['alignment' => 'center']);
        $section->addText('« [[THEME]] »', ['italic' => true, 'size' => 12, 'color' => '223EA4'], ['alignment' => 'center']);
        $section->addText('[[DATES]]   ·   [[LIEU]]', ['size' => 11, 'color' => '6B7280'], ['alignment' => 'center']);
        $section->addTextBreak(2);
        $section->addText('LIVRE DES RÉSUMÉS', ['bold' => true, 'size' => 13, 'color' => '223EA4'], ['alignment' => 'center']);
        $section->addPageBreak();

        // Pages liminaires : un en-tête (modifiable) + sa balise bloc.
        foreach ([
            'Avant-propos' => '[[AVANT_PROPOS]]',
            'Comités' => '[[COMITES]]',
            'Sommaire' => '[[SOMMAIRE]]',
            'Programme' => '[[PROGRAMME]]',
            'Résumés des communications' => '[[RESUMES]]',
        ] as $heading => $blockTag) {
            $section->addText($heading, ['bold' => true, 'size' => 18, 'color' => '223EA4', 'name' => 'Jost'], ['spaceBefore' => 240, 'spaceAfter' => 160]);
            $section->addText($blockTag, ['size' => 11]);
        }

        // Notice.
        $section->addPageBreak();
        $section->addText('Comment utiliser ce modèle', ['bold' => true, 'size' => 14, 'color' => '223EA4'], ['spaceAfter' => 120]);
        $section->addText('Personnalisez librement la mise en page, les polices, les couleurs et la couverture dans Word. Conservez les balises entre doubles crochets : elles seront remplacées automatiquement à la génération.', ['size' => 11], ['spaceAfter' => 80]);
        $section->addText('Balises « bloc » (seules sur leur ligne) : [[AVANT_PROPOS]], [[COMITES]], [[SOMMAIRE]], [[PROGRAMME]], [[RESUMES]].', ['size' => 10, 'color' => '6B7280']);
        $section->addText('Balises « en ligne » : [[TITRE]], [[EDITION]], [[THEME]], [[DATES]], [[LIEU]], [[ANNEE]], [[NB_RESUMES]].', ['size' => 10, 'color' => '6B7280']);

        return $doc;
    }

    /** Réponse de téléchargement du modèle de base. */
    public static function downloadBase(string $filename): Response
    {
        $writer = new Word2007(self::baseModel());

        return response()->streamDownload(function () use ($writer) {
            $writer->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        ]);
    }

    /** Texte concaténé des <w:t> d'un paragraphe. */
    private function paragraphText(\DOMXPath $xp, \DOMElement $p): string
    {
        $text = '';
        foreach ($xp->query('.//w:t', $p) as $t) {
            $text .= $t->nodeValue;
        }

        return $text;
    }

    /**
     * Importe un fragment OOXML (chaîne de <w:p>/<w:tbl>) en nœuds du document cible.
     *
     * @return list<\DOMNode>
     */
    private function importFragment(\DOMDocument $dom, string $xmlFrag): array
    {
        if (trim($xmlFrag) === '') {
            return [];
        }
        $frag = new \DOMDocument;
        $wrapped = '<w:root xmlns:w="'.LivretDocx::WNS.'">'.$xmlFrag.'</w:root>';
        if (! @$frag->loadXML($wrapped)) {
            return [];
        }
        $nodes = [];
        $root = $frag->documentElement;
        if ($root) {
            foreach (iterator_to_array($root->childNodes) as $child) {
                $nodes[] = $dom->importNode($child, true);
            }
        }

        return $nodes;
    }

    /**
     * Garantit word/settings.xml avec updateFields (met à jour un éventuel TOC du modèle).
     *
     * @param  array<string, string>  $files
     * @return array<string, string>
     */
    private function ensureUpdateFields(array $files): array
    {
        if (isset($files['word/settings.xml'])) {
            if (! str_contains($files['word/settings.xml'], '<w:updateFields')) {
                $files['word/settings.xml'] = (string) preg_replace(
                    '/(<w:settings\b[^>]*>)/',
                    '$1<w:updateFields w:val="true"/>',
                    $files['word/settings.xml'],
                    1,
                );
            }
        }

        return $files;
    }

    /**
     * Réécrit le .docx à partir des entrées en mémoire.
     *
     * @param  array<string, string>  $files
     */
    private function rezip(array $files): string
    {
        $tmp = tempnam(sys_get_temp_dir(), 'ukctpl');
        $zout = new \ZipArchive;
        $zout->open($tmp, \ZipArchive::OVERWRITE);
        foreach ($files as $name => $content) {
            $zout->addFromString($name, $content);
        }
        $zout->close();
        $data = (string) file_get_contents($tmp);
        @unlink($tmp);

        return $data;
    }
}
