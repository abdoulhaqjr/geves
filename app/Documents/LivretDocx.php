<?php

namespace App\Documents;

use App\Models\Edition;
use App\Models\Submission;
use Illuminate\Support\Collection;
use PhpOffice\PhpWord\Element\Section;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\Shared\Converter;
use PhpOffice\PhpWord\Writer\Word2007;
use Symfony\Component\HttpFoundation\Response;

/**
 * Livret des résumés au format Word éditable (.docx), pendant du rendu
 * imprimable HTML→PDF (`documents.livret`). Génère un document Office Open XML
 * via phpoffice/phpword : page de garde aux couleurs de la charte, puis les
 * communications acceptées groupées par axe (titre, auteurs, résumé).
 */
class LivretDocx
{
    // Charte Université de Kara (littéraux : Word ne lit pas les tokens CSS).
    private const BLUE = '223EA4';

    private const GOLD = 'FFD700';

    private const GREEN = '009933';

    private const MUTED = '6B7280';

    /**
     * @param  Collection<string, Collection<int, Submission>>  $groups
     */
    public function __construct(
        private readonly Edition $edition,
        private readonly Collection $groups,
    ) {}

    /** Réponse HTTP de téléchargement du .docx. */
    public function download(string $filename): Response
    {
        $writer = new Word2007($this->build());

        return response()->streamDownload(function () use ($writer) {
            $writer->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        ]);
    }

    /** Construit le document phpword complet. */
    public function build(): PhpWord
    {
        $doc = new PhpWord;
        $doc->getSettings()->setUpdateFields(true);

        $doc->addTitleStyle(1, ['size' => 18, 'bold' => true, 'color' => self::BLUE], ['spaceAfter' => 240]);
        $doc->addTitleStyle(2, ['size' => 14, 'bold' => true, 'color' => self::BLUE], ['spaceBefore' => 360, 'spaceAfter' => 160]);

        $section = $doc->addSection([
            'marginTop' => Converter::cmToTwip(2.2),
            'marginBottom' => Converter::cmToTwip(2.2),
            'marginLeft' => Converter::cmToTwip(2.2),
            'marginRight' => Converter::cmToTwip(2.2),
        ]);

        $this->addCover($section);
        $this->addBody($section);

        return $doc;
    }

    /** Page de garde : bandeau tricolore, colloque, titre, édition, dates. */
    private function addCover(Section $section): void
    {
        // Bandeau tricolore (trois cellules colorées). Twips = entiers (1pt = 20tw, 1cm ≈ 567tw).
        $banner = $section->addTable();
        $banner->addRow((int) Converter::pointToTwip(6));
        foreach ([self::BLUE, self::GOLD, self::GREEN] as $color) {
            $banner->addCell((int) Converter::cmToTwip(4), ['bgColor' => $color])->addText('');
        }

        $section->addTextBreak(2);

        if ($name = $this->edition->colloque?->name) {
            $section->addText($name, ['bold' => true, 'size' => 12, 'color' => self::BLUE], ['alignment' => 'center']);
        }

        $section->addText('Livret des résumés', ['bold' => true, 'size' => 24, 'color' => '111827'], ['alignment' => 'center', 'spaceBefore' => 160, 'spaceAfter' => 80]);

        if ($title = $this->edition->title) {
            $section->addText($title, ['size' => 13, 'color' => self::MUTED], ['alignment' => 'center']);
        }

        if ($this->edition->date_start) {
            $dates = $this->edition->date_start->format('d/m/Y');
            if ($this->edition->date_end) {
                $dates .= ' – '.$this->edition->date_end->format('d/m/Y');
            }
            $section->addText($dates, ['size' => 11, 'color' => self::MUTED], ['alignment' => 'center', 'spaceBefore' => 80]);
        }

        $section->addPageBreak();
    }

    /** Corps : communications acceptées groupées par axe. */
    private function addBody(Section $section): void
    {
        if ($this->groups->isEmpty()) {
            $section->addText('Aucune communication acceptée.', ['italic' => true, 'color' => self::MUTED]);

            return;
        }

        foreach ($this->groups as $track => $submissions) {
            $section->addTitle((string) $track, 2);

            foreach ($submissions as $s) {
                $section->addText((string) $s->title, ['bold' => true, 'size' => 12], ['spaceBefore' => 160, 'spaceAfter' => 20]);

                $authors = collect($s->authors ?? [])->pluck('name')->filter()->join(', ');
                if ($authors !== '') {
                    $section->addText($authors, ['italic' => true, 'size' => 10, 'color' => self::MUTED], ['spaceAfter' => 40]);
                }

                if ($abstract = trim((string) $s->abstract)) {
                    $section->addText($abstract, ['size' => 11], ['alignment' => 'both', 'spaceAfter' => 120]);
                }
            }
        }
    }
}
