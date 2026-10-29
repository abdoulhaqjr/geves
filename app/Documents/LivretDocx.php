<?php

namespace App\Documents;

use App\AI\AiManager;
use App\Models\CommitteeMember;
use App\Models\Event;
use App\Models\EventSession;
use App\Models\Slot;
use App\Models\Submission;
use App\Support\EventNameFormat;
use App\Support\EventSettings;
use App\Support\ReviewerExpertise;
use App\Support\SubmissionFormConfig;
use App\Support\SubmissionKeywords;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use PhpOffice\PhpWord\Element\Footer;
use PhpOffice\PhpWord\Element\Section;
use PhpOffice\PhpWord\Element\Table;
use PhpOffice\PhpWord\Element\TextRun;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\Settings;
use PhpOffice\PhpWord\Shared\Converter;
use PhpOffice\PhpWord\Writer\Word2007;
use Symfony\Component\HttpFoundation\Response;

/**
 * Livret des résumés au format Word éditable (.docx), portant la logique du
 * projet source uk-colloques (booklet) : page de garde « LIVRE DES RÉSUMÉS »,
 * avant-propos, comités, sommaire (TOC Word), programme, puis résumés regroupés
 * par axe - chaque résumé avec sa référence, ses auteurs et affiliations
 * numérotées (exposants), l'auteur correspondant, le libellé « RÉSUMÉ : » et les
 * mots-clés. Rendu déterministe ; l'apparence est pilotée par les réglages
 * `livret.*` et le texte échappé (Word refuse un XML invalide).
 */
class LivretDocx
{
    /** Espace de noms WordprocessingML (extraction des fragments OOXML pour les modèles). */
    public const WNS = 'http://schemas.openxmlformats.org/wordprocessingml/2006/main';

    // Charte Université de Kara (littéraux : Word ne lit pas les tokens CSS).
    private const BLUE = '223EA4';

    private const GREEN = '009933';

    private const MUTED = '6B7280';

    // Palette EXACTE du document de référence « Document général du colloque »
    // (style « general ») : titres noirs, ateliers en terracotta, tableaux à
    // bandeau bleu et en-tête gris - mesurés dans le .docx fourni.
    private const BLACK = '000000';

    private const TERRACOTTA = 'C0504D';

    private const REF_DAY_FILL = '95B3D7';

    private const REF_HEAD_FILL = 'F2F2F2';

    private const REF_GROUP_FILL = 'F2F2F2';

    /** @var array<string, string> rôle => libellé de comité (ordre d'affichage). */
    private const COMMITTEE_ROLES = [
        'organisation' => 'Comité d’organisation',
        'scientifique' => 'Comité scientifique',
        'supervision' => 'Comité de supervision',
    ];

    /** @var array<string, string> map IA original => nom harmonisé */
    private array $nameMap = [];

    /** @var array<string, string> map IA original => affiliation harmonisée */
    private array $affMap = [];

    /** @var array<string, list<string>> nom d'auteur affiché => références des résumés (index des auteurs). */
    private array $authorIndex = [];

    /** Styles intégrés disponibles (modèle actif « default », « fss » ou « general »). */
    public const STYLES = [
        'default' => 'Modèle par défaut',
        'fss' => 'Modèle 2 - Journées scientifiques',
        'general' => 'Modèle 3 - Document général du colloque',
    ];

    /**
     * @param  Collection<int|string, \Illuminate\Database\Eloquent\Collection<int, Submission>>  $groups  soumissions groupées par axe
     * @param  string  $style  style intégré : 'default' (UK/UP) ou 'fss' (Journées scientifiques)
     */
    public function __construct(
        private readonly Event $event,
        private readonly Collection $groups,
        private readonly bool $harmonize = false,
        private readonly string $style = 'default',
    ) {}

    /** Le document est-il rendu dans le style « Journées scientifiques (FSS) » ? */
    private function isFss(): bool
    {
        return $this->style === 'fss';
    }

    /** Style « Document général du colloque » (comité d'organisation en tableau + ateliers) ? */
    private function isGeneral(): bool
    {
        return $this->style === 'general';
    }

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

    /** Binaire utilisé lorsque la couverture DocSpace doit remplacer la couverture intégrée. */
    public function render(): string
    {
        $path = tempnam(sys_get_temp_dir(), 'livret-docx-');
        try {
            (new Word2007($this->build()))->save($path);
            $binary = file_get_contents($path);
            if ($binary === false) {
                throw new \RuntimeException('Impossible de lire le livre Word généré.');
            }

            return $binary;
        } finally {
            @unlink($path);
        }
    }

    /** Construit le document phpword complet (ordre du booklet uk-colloques). */
    public function build(): PhpWord
    {
        // PhpWord n'échappe PAS le texte par défaut : sans ceci, tout « & », « < » ou « > »
        // (ex. « Santé & environnement ») produit un document.xml malformé → .docx illisible.
        Settings::setOutputEscapingEnabled(true);

        $doc = new PhpWord;

        $doc->setDefaultFontName((string) LivretAppearance::get($this->event, 'font_body', 'Times New Roman'));
        $doc->setDefaultFontSize((int) LivretAppearance::get($this->event, 'size_body', 11));
        $doc->setDefaultParagraphStyle(['lineHeight' => (float) LivretAppearance::get($this->event, 'line_height', '1.15')]);

        // Les titres sont des paragraphes en gras (addText), PAS des addTitle : Word
        // ajoutait un numéro après chaque titre (style Titre indexé) et exigeait un TOC.
        // EXCEPTION : le style « général » rend AUSSI les axes/ateliers et les codes de
        // communication en véritables styles de titre Word (Heading1/Heading2), pour que
        // le .docx soit navigable dans le volet de navigation et un sommaire. L'allure
        // est identique à celle qu'avaient ces titres en addText (axes/ateliers centrés
        // 14 pt, code à droite 11 pt) : l'alignement et les tailles sont repris dans le
        // style de titre, la couleur terracotta d'un atelier reste portée par son TextRun.
        if ($this->isGeneral()) {
            $this->registerGeneralHeadingStyles($doc);
        }

        $section = $doc->addSection([
            'marginTop' => Converter::cmToTwip(2.2),
            'marginBottom' => Converter::cmToTwip(2.2),
            'marginLeft' => Converter::cmToTwip(2.2),
            'marginRight' => Converter::cmToTwip(2.2),
        ]);

        // Pied de page paginé (vide sur la page de garde). Réglage `livret.page_numbers`.
        if ((bool) LivretAppearance::get($this->event, 'page_numbers', true)) {
            $section->addFooter(Footer::FIRST); // couverture : sans numéro
            $footer = $section->addFooter();
            $footer->addPreserveText('{PAGE}', ['size' => 9, 'color' => self::MUTED], ['alignment' => 'center']);
        }

        // Harmonisation IA (noms + affiliations) : maps appliquées au rendu des résumés.
        if ($this->harmonize) {
            $this->buildAiMaps();
        }

        $this->addCover($section);
        // Sections de tête GÉNÉRIQUES, affichées seulement si le champ est rempli
        // (haut patronage, remerciements, mots signés) - sinon rien ne s'insère.
        $this->addPatronage($section);
        $this->addAcknowledgements($section);
        $this->addForeword($section);
        $this->addMessage(
            $section,
            trim((string) EventSettings::get($this->event, 'livret.welcome_title', 'Mot de bienvenue')) ?: 'Mot de bienvenue',
            (string) EventSettings::get($this->event, 'livret.welcome_text', ''),
            (string) EventSettings::get($this->event, 'livret.welcome_signature', ''),
        );
        $this->addMessage(
            $section,
            trim((string) EventSettings::get($this->event, 'livret.president_title', 'Mot du président')) ?: 'Mot du président',
            (string) EventSettings::get($this->event, 'livret.president_text', ''),
            (string) EventSettings::get($this->event, 'livret.president_signature', ''),
        );
        if ($this->isGeneral()) {
            // « Document général du colloque » : commissions (comité d'organisation
            // en TABLEAU) + programme synthétique, puis « Communications en ateliers »
            // (planning par atelier), puis les résumés.
            $num = $this->addCommissionsGeneral($section);
            $this->addAteliers($section, $num + 1);
        } else {
            $this->addCommissionsProgramme($section);
            $this->isFss() ? $this->addProgrammeFss($section) : $this->addProgramme($section);
        }
        $this->addBody($section);

        // Index des auteurs (renvoie au numéro de résumé). Réglage `livret.author_index`.
        if ((bool) LivretAppearance::get($this->event, 'author_index', true)) {
            $this->addAuthorIndex($section);
        }

        return $doc;
    }

    // --- Modèles .docx personnalisés (balises remplies) : valeurs + fragments OOXML ---

    /**
     * Valeurs des balises « en ligne » d'un modèle ([[TITRE]], [[DATES]]…).
     *
     * @return array<string, string>
     */
    public function inlineValues(): array
    {
        $e = $this->event;
        $dates = '';
        if ($e->date_start) {
            $dates = $e->date_start->format('d/m/Y');
            if ($e->date_end) {
                $dates .= ' - '.$e->date_end->format('d/m/Y');
            }
        }
        $count = 0;
        foreach ($this->groups as $g) {
            $count += count($g);
        }

        return [
            '[[TITRE]]' => (string) $e->title,
            '[[EDITION]]' => (string) ($e->event_label ?? ''),
            '[[THEME]]' => (string) ($e->theme ?? ''),
            '[[DATES]]' => $dates,
            '[[LIEU]]' => (string) ($e->location ?? ''),
            '[[ANNEE]]' => (string) ($e->date_start?->format('Y') ?? $e->year ?? ''),
            '[[NB_RESUMES]]' => (string) $count,
        ];
    }

    /**
     * Fragment OOXML (paragraphes/tableaux) d'une balise « bloc » du modèle,
     * en formatage DIRECT (indépendant des styles du modèle).
     */
    public function blockXml(string $tag): string
    {
        if ($tag === 'RESUMES' && $this->harmonize) {
            $this->buildAiMaps();
        }

        Settings::setOutputEscapingEnabled(true);
        $doc = new PhpWord;
        $doc->setDefaultFontName((string) LivretAppearance::get($this->event, 'font_body', 'Times New Roman'));
        $doc->setDefaultFontSize((int) LivretAppearance::get($this->event, 'size_body', 11));
        $doc->setDefaultParagraphStyle(['lineHeight' => (float) LivretAppearance::get($this->event, 'line_height', '1.15')]);
        $section = $doc->addSection();

        match ($tag) {
            'AVANT_PROPOS' => $this->tplForeword($section),
            'COMITES' => $this->tplCommittees($section),
            'SOMMAIRE' => $this->tplSommaire($section),
            'PROGRAMME' => $this->tplProgramme($section),
            'RESUMES' => $this->tplResumes($section),
            default => null,
        };

        return self::bodyInnerXml($doc);
    }

    /** Sérialise les enfants de <w:body> (hors <w:sectPr>) d'un document phpword. */
    private static function bodyInnerXml(PhpWord $doc): string
    {
        $tmp = tempnam(sys_get_temp_dir(), 'ukcfrag');
        (new Word2007($doc))->save($tmp);
        $zip = new \ZipArchive;
        if ($zip->open($tmp) !== true) {
            @unlink($tmp);

            return '';
        }
        $xml = (string) $zip->getFromName('word/document.xml');
        $zip->close();
        @unlink($tmp);

        $dom = new \DOMDocument;
        if (! @$dom->loadXML($xml)) {
            return '';
        }
        $body = $dom->getElementsByTagNameNS(self::WNS, 'body')->item(0);
        if (! $body) {
            return '';
        }
        $out = '';
        foreach (iterator_to_array($body->childNodes) as $child) {
            if ($child->nodeType === XML_ELEMENT_NODE && $child->localName === 'sectPr') {
                continue;
            }
            $out .= (string) $dom->saveXML($child);
        }

        return $out;
    }

    /** Bloc Avant-propos (sans titre : le modèle fournit l'en-tête). */
    private function tplForeword(Section $section): void
    {
        $text = trim((string) EventSettings::get($this->event, 'livret.foreword_text', ''));
        foreach ($text !== '' ? (preg_split('/\R{2,}/', $text) ?: [$text]) : [] as $paragraph) {
            if (trim((string) $paragraph) !== '') {
                $section->addText(trim((string) $paragraph), ['size' => 11], ['alignment' => 'both', 'spaceAfter' => 120]);
            }
        }
        if ($signature = trim((string) EventSettings::get($this->event, 'livret.signature', ''))) {
            $section->addText($signature, ['italic' => true, 'size' => 11, 'color' => self::MUTED], ['alignment' => 'right', 'spaceBefore' => 160]);
        }
    }

    /** Bloc Comités (libellés de rôle en gras, membres en dessous). */
    private function tplCommittees(Section $section): void
    {
        $byRole = $this->event->committeeMembers()->orderBy('ordre')->get()->groupBy('role');
        foreach (self::COMMITTEE_ROLES as $role => $label) {
            $list = $byRole->get($role);
            if (! $list || $list->isEmpty()) {
                continue;
            }
            $section->addText($label, ['bold' => true, 'size' => 13, 'color' => self::BLUE], ['spaceBefore' => 160, 'spaceAfter' => 60]);
            foreach ($list as $m) {
                $bits = array_filter([trim((string) $m->person_name), trim((string) $m->person_title), trim((string) $m->institution)]);
                $section->addText(implode(' - ', $bits), ['size' => 11], ['spaceAfter' => 40]);
            }
        }
    }

    /** Bloc Sommaire (liste déterministe des axes des résumés). */
    private function tplSommaire(Section $section): void
    {
        foreach ($this->groups as $track => $submissions) {
            $run = $section->addTextRun(['spaceAfter' => 40]);
            $run->addText((string) $track, ['size' => 11, 'bold' => true]);
            $run->addText('   ('.count($submissions).')', ['size' => 11, 'color' => self::MUTED]);
        }
    }

    /** Bloc Programme (communications placées par jour) - sans titre (le modèle le fournit). */
    private function tplProgramme(Section $section): void
    {
        foreach ($this->programmeByDay() as $day => $slots) {
            $this->programmeDayTable($section, (string) $day, $slots);
        }
    }

    /** Bloc Résumés (axe en gras direct + résumés), réutilise addAbstract. */
    private function tplResumes(Section $section): void
    {
        $sizeAxis = (int) LivretAppearance::get($this->event, 'size_axis', 16);
        $primary = self::hex((string) LivretAppearance::get($this->event, 'color_primary', '#223ea4'), self::BLUE);
        $fontHead = (string) LivretAppearance::get($this->event, 'font_head', 'Jost');
        $sizeAbstract = (int) LivretAppearance::get($this->event, 'size_abstract', 12);
        $sizeAuthors = (int) LivretAppearance::get($this->event, 'size_authors', 10);
        $sizeBody = (int) LivretAppearance::get($this->event, 'size_body', 11);
        $alignBody = LivretAppearance::get($this->event, 'align_body', 'Justifié') === 'Gauche' ? 'left' : 'both';
        $number = (bool) LivretAppearance::get($this->event, 'number_abstracts', false);

        $n = 0;
        foreach ($this->groups as $track => $submissions) {
            $section->addText(mb_strtoupper((string) $track, 'UTF-8'), ['bold' => true, 'size' => $sizeAxis, 'color' => $primary, 'name' => $fontHead], ['spaceBefore' => 320, 'spaceAfter' => 160]);
            foreach ($submissions as $s) {
                $n++;
                $this->addAbstract($section, $s, $n, $number, $sizeAbstract, $sizeAuthors, $sizeBody, $alignBody);
            }
        }
    }

    /** Page de garde : logo, bandeau, colloque, titre, thème, dates, « LIVRE DES RÉSUMÉS ». */
    private function addCover(Section $section): void
    {
        $coverSize = (int) LivretAppearance::get($this->event, 'size_cover', 24);
        $showTheme = (bool) LivretAppearance::get($this->event, 'show_theme', true);
        $showDates = (bool) LivretAppearance::get($this->event, 'show_dates', true);
        $primary = self::hex((string) LivretAppearance::get($this->event, 'color_primary', '#223ea4'), self::BLUE);
        $secondary = self::hex((string) LivretAppearance::get($this->event, 'color_secondary', '#009933'), self::BLUE);

        // En-tête : nom du colloque en majuscules.
        if ($name = trim((string) $this->event->series?->name)) {
            $section->addText(mb_strtoupper($name, 'UTF-8'), ['bold' => true, 'size' => 12, 'color' => $primary], ['alignment' => 'center', 'spaceAfter' => 80]);
        }

        // Logo(s) de couverture (réglage d'apparence), s'ils sont fournis et lisibles.
        $logo = (string) LivretAppearance::get($this->event, 'cover_logo', '');
        if ($logo !== '' && Storage::disk('public')->exists($logo)) {
            try {
                $section->addImage(Storage::disk('public')->path($logo), ['width' => 140, 'alignment' => 'center']);
                $section->addTextBreak(1);
            } catch (\Throwable $e) {
                // Logo illisible : on poursuit sans image.
            }
        }

        // Couverture SOBRE (sans bandeau tricolore) pour les deux styles.
        $section->addTextBreak(2);

        if ($title = trim((string) $this->event->title)) {
            $section->addText($title, ['bold' => true, 'size' => $coverSize, 'color' => '111827'], ['alignment' => 'center', 'spaceBefore' => 160, 'spaceAfter' => 40]);
        }

        if ($label = trim((string) $this->event->event_label)) {
            $section->addText($label, ['size' => 13, 'color' => self::MUTED], ['alignment' => 'center', 'spaceAfter' => 20]);
        }

        // Thème de l'événement (réglage d'apparence).
        if ($showTheme && ($theme = trim((string) $this->event->theme)) !== '') {
            $section->addText('« '.$theme.' »', ['italic' => true, 'size' => 12, 'color' => $primary], ['alignment' => 'center', 'spaceBefore' => 60]);
        }

        // Dates + lieu (réglage d'apparence).
        if ($showDates && $this->event->date_start) {
            $dates = $this->event->date_start->format('d/m/Y');
            if ($this->event->date_end) {
                $dates .= ' - '.$this->event->date_end->format('d/m/Y');
            }
            if ($location = trim((string) $this->event->location)) {
                $dates .= '   -   '.$location;
            }
            $section->addText($dates, ['size' => 11, 'color' => self::MUTED], ['alignment' => 'center', 'spaceBefore' => 80]);
        }

        // Libellé de couverture (réglage `cover_mention`, défaut « LIVRE DES RÉSUMÉS »).
        $coverLabel = trim((string) LivretAppearance::get($this->event, 'cover_mention', '')) ?: 'LIVRE DES RÉSUMÉS';
        $section->addText($coverLabel, ['bold' => true, 'size' => 13, 'color' => $secondary], ['alignment' => 'center', 'spaceBefore' => 240]);

        $section->addPageBreak();
    }

    /** Avant-propos signé (réglages `livret.*`). */
    private function addForeword(Section $section): void
    {
        $this->addMessage(
            $section,
            trim((string) EventSettings::get($this->event, 'livret.foreword_title', 'Avant-propos')) ?: 'Avant-propos',
            (string) EventSettings::get($this->event, 'livret.foreword_text', ''),
            (string) EventSettings::get($this->event, 'livret.signature', ''),
        );
    }

    /**
     * Bloc de message signé GÉNÉRIQUE (avant-propos, mot de bienvenue, mot du
     * président…) : titre + paragraphes justifiés + signature. Ne rend RIEN si le
     * texte est vide (section masquée). Sert tous les « mots » du livret.
     */
    private function addMessage(Section $section, string $title, string $text, string $signature): void
    {
        $text = trim($text);
        if ($text === '') {
            return;
        }

        $this->partTitle($section, $title);

        foreach (preg_split('/\R{2,}/', $text) ?: [$text] as $paragraph) {
            $paragraph = trim((string) $paragraph);
            if ($paragraph !== '') {
                $section->addText($paragraph, ['size' => 11], ['alignment' => 'both', 'spaceAfter' => 120]);
            }
        }

        if (($signature = trim($signature)) !== '') {
            $section->addText($signature, ['italic' => true, 'size' => 11, 'color' => self::MUTED], ['alignment' => 'right', 'spaceBefore' => 160]);
        }

        $section->addPageBreak();
    }

    /**
     * Haut patronage (réglage `livret.patronage`) : lignes centrées sur une page
     * dédiée (ex. « Sous le haut patronage de… »). Masqué si vide.
     */
    private function addPatronage(Section $section): void
    {
        $text = trim((string) EventSettings::get($this->event, 'livret.patronage', ''));
        if ($text === '') {
            return;
        }

        foreach (preg_split('/\R+/', $text) ?: [$text] as $i => $line) {
            $line = trim((string) $line);
            if ($line !== '') {
                // Première ligne en gras (intitulé du patronage), suivantes en dessous.
                $section->addText($line, ['size' => 12, 'bold' => $i === 0, 'color' => '111827'],
                    ['alignment' => 'center', 'spaceBefore' => $i === 0 ? 240 : 0, 'spaceAfter' => 60]);
            }
        }

        $section->addPageBreak();
    }

    /**
     * Remerciements (réglage `livret.acknowledgements`, une entrée par ligne) :
     * titre + liste à puces. Masqué si vide.
     */
    private function addAcknowledgements(Section $section): void
    {
        $text = trim((string) EventSettings::get($this->event, 'livret.acknowledgements', ''));
        if ($text === '') {
            return;
        }

        $title = trim((string) EventSettings::get($this->event, 'livret.ack_title', 'Remerciements')) ?: 'Remerciements';
        $this->partTitle($section, $title);

        foreach (preg_split('/\R+/', $text) ?: [$text] as $line) {
            $line = trim((string) $line);
            if ($line !== '') {
                $section->addListItem($line, 0, ['size' => 11], null, ['spaceAfter' => 40]);
            }
        }

        $section->addPageBreak();
    }

    /** @var array<string, string> comités dans l'ordre de référence (supervision → scientifique). */
    private const COMMITTEE_ORDER = [
        'supervision' => 'Comité de supervision',
        'organisation' => 'Comité d’organisation',
        'scientifique' => 'Comité scientifique',
    ];

    /**
     * « Différentes commissions et programmes du colloque » : comités numérotés
     * (1. Supervision, 2. Organisation, 3. Scientifique) puis « Programme
     * synthétique du colloque » - structure du livret de référence UK/UP.
     */
    private function addCommissionsProgramme(Section $section): void
    {
        $byRole = $this->event->committeeMembers()->orderBy('ordre')->get()->groupBy('role');
        $committees = collect(self::COMMITTEE_ORDER)
            ->filter(fn ($label, $role) => ($byRole->get($role)?->isNotEmpty() ?? false));

        // Style FSS : titre « Comités », rôles SANS numérotation, et PAS de programme
        // synthétique (le livret de référence n'a que le programme détaillé qui suit).
        // Style par défaut UK/UP : « Différentes commissions… » + programme synthétique.
        $fss = $this->isFss();

        $synthesis = $this->event->settings['program_synthesis'] ?? null;
        $synDays = $fss ? collect() : (is_array($synthesis) ? collect($synthesis['days'] ?? []) : collect());

        if ($committees->isEmpty() && $synDays->isEmpty()) {
            return;
        }

        $this->partTitle($section, $fss ? 'Comités' : 'Différentes commissions et programmes du colloque');

        $num = 0;
        foreach ($committees as $role => $label) {
            $num++;
            $heading = $fss ? $label : $num.'. '.$label;
            $section->addText($heading, ['bold' => true, 'size' => 13, 'color' => self::BLUE], ['spaceBefore' => 200, 'spaceAfter' => 70, 'keepNext' => true]);
            foreach ($byRole->get($role) ?? [] as $m) {
                $lead = trim(trim((string) $m->person_title).' '.trim((string) $m->person_name));
                $tail = trim((string) $m->institution);
                $section->addText($tail !== '' ? $lead.', '.$tail : $lead, ['size' => 11], ['spaceAfter' => 30]);
            }
        }

        if ($synDays->isNotEmpty()) {
            $num++;
            $synHeading = $fss ? 'Programme synthétique du colloque' : $num.'. Programme synthétique du colloque';
            $section->addText($synHeading, ['bold' => true, 'size' => 13, 'color' => self::BLUE], ['spaceBefore' => 240, 'spaceAfter' => 70, 'keepNext' => true]);
            $this->addProgrammeSynthetique($section, $synDays->all());
        }

        $section->addPageBreak();
    }

    /** Programme synthétique saisi (jours + lignes « horaire : libellé »), rendu texte. */
    private function addProgrammeSynthetique(Section $section, array $days): void
    {
        $hm = fn ($t) => trim((string) $t) === '' ? '' : str_replace(':', 'h', substr((string) $t, 0, 5));
        foreach ($days as $day) {
            $heading = trim((string) ($day['heading'] ?? ''));
            $date = trim((string) ($day['date'] ?? ''));
            $dayLabel = trim(($date !== '' ? ucfirst(Carbon::parse($date)->translatedFormat('l d/m/Y')) : '').($heading !== '' ? '  '.$heading : ''));
            if ($dayLabel !== '') {
                $section->addText($dayLabel, ['bold' => true, 'size' => 11, 'color' => self::BLUE], ['spaceBefore' => 120, 'spaceAfter' => 30]);
            }
            foreach ($day['rows'] ?? [] as $row) {
                $s = $hm($row['start'] ?? '');
                $e = $hm($row['end'] ?? '');
                $time = $e !== '' ? $s.' - '.$e : $s;
                $label = trim((string) ($row['label'] ?? ''));
                $text = $time !== '' ? trim($time.' : '.$label) : $label;
                if ($text !== '') {
                    $section->addText($text, ['size' => 11], ['spaceAfter' => 20]);
                }
            }
        }
    }

    /** @var array<string, string> comités du style « Document général » (ordre du document de référence). */
    private const GENERAL_COMMITTEE_ORDER = [
        'supervision' => 'Comité de supervision',
        'scientifique' => 'Comité scientifique',
        'organisation' => 'Comité d’organisation',
    ];

    /**
     * « Les différentes commissions et programmes du colloque » (style général) :
     * comités dans l'ordre du document de référence (supervision, scientifique,
     * organisation), le comité d'organisation rendu en TABLEAU
     * (N° / Nom & Prénom / Grade / Contacts / Qualité), puis le programme synthétique.
     */
    private function addCommissionsGeneral(Section $section): int
    {
        $byRole = $this->event->committeeMembers()->orderBy('ordre')->get()->groupBy('role');
        $committees = collect(self::GENERAL_COMMITTEE_ORDER)
            ->filter(fn ($label, $role) => ($byRole->get($role)?->isNotEmpty() ?? false));

        $synthesis = $this->event->settings['program_synthesis'] ?? null;
        $synDays = is_array($synthesis) ? collect($synthesis['days'] ?? []) : collect();

        if ($committees->isEmpty() && $synDays->isEmpty()) {
            return 0;
        }

        // « A) LES DIFFÉRENTES COMMISSIONS ET PROGRAMMES DU COLLOQUE »
        // (gras, 14 pt, noir, à gauche, capitales - doc de référence).
        $this->generalSectionTitle($section, 'A) Les différentes commissions et programmes du colloque', 14, 'left');

        $num = 0;
        foreach ($committees as $role => $label) {
            $num++;
            // « 1. COMITÉ DE SUPERVISION » : gras 14 pt noir, en capitales.
            $this->generalHeading($section, $num.'. '.mb_strtoupper($label, 'UTF-8'));

            if ($role === 'organisation') {
                $this->committeeOrganisationTable($section, $byRole->get($role) ?? collect());

                continue;
            }

            foreach ($byRole->get($role) ?? [] as $m) {
                $lead = trim(trim((string) $m->person_title).' '.trim((string) $m->person_name));
                $tail = trim((string) $m->institution);
                // Lignes de membres : justifiées, 11 pt noir (doc de référence).
                $section->addText($tail !== '' ? $lead.', '.$tail : $lead,
                    ['size' => 11, 'color' => self::BLACK], ['alignment' => 'both', 'spaceAfter' => 30]);
            }
        }

        if ($synDays->isNotEmpty()) {
            $num++;
            $this->generalHeading($section, $num.'. PROGRAMME SYNTHÉTIQUE DU COLLOQUE');
            $this->addProgrammeSynthetique($section, $synDays->all());
        }

        return $num;
    }

    /**
     * Styles de titre Word du « Document général » (navigabilité du .docx).
     *
     * On ne s'en sert QUE pour les items de contenu (axes/ateliers, codes de
     * communication), pas pour les titres de section A)/B) : ceux-ci gardent des
     * alignements différents (A) à gauche, B) centré) qu'un style de titre, lié à
     * une profondeur, ne saurait porter à la fois. Les deux profondeurs reprennent
     * l'allure exacte qu'avaient ces titres en addText, donc aucune régression.
     *  - Heading1 : axes « N- … » et ateliers « ATELIER n … » (centré, 14 pt).
     *  - Heading2 : code de communication « AVD-xxx » (à droite, 11 pt).
     * Word reconnaît les identifiants Heading1/Heading2 comme des titres à niveau
     * de plan → ils peuplent le volet de navigation et alimentent un sommaire.
     */
    private function registerGeneralHeadingStyles(PhpWord $doc): void
    {
        $font = (string) LivretAppearance::get($this->event, 'font_head', 'Jost');
        $doc->addTitleStyle(1,
            ['bold' => true, 'size' => 14, 'color' => self::BLACK, 'name' => $font],
            ['alignment' => 'center', 'spaceBefore' => 300, 'spaceAfter' => 160, 'keepNext' => true]);
        $doc->addTitleStyle(2,
            ['bold' => true, 'size' => 11, 'color' => self::BLACK],
            ['alignment' => 'right', 'spaceBefore' => 220, 'spaceAfter' => 20, 'keepNext' => true]);
    }

    /** Titre de section lettré du style général (« A) … », « B) … ») : gras, noir. */
    private function generalSectionTitle(Section $section, string $text, int $size, string $align): void
    {
        $font = (string) LivretAppearance::get($this->event, 'font_head', 'Jost');
        $section->addText($text, ['bold' => true, 'size' => $size, 'color' => self::BLACK, 'name' => $font],
            ['alignment' => $align, 'spaceBefore' => 300, 'spaceAfter' => 160, 'keepNext' => true]);
    }

    /** Titre numéroté du style général (comités, programme) : gras 14 pt noir. */
    private function generalHeading(Section $section, string $text): void
    {
        $font = (string) LivretAppearance::get($this->event, 'font_head', 'Jost');
        $section->addText($text, ['bold' => true, 'size' => 14, 'color' => self::BLACK, 'name' => $font],
            ['spaceBefore' => 220, 'spaceAfter' => 80, 'keepNext' => true]);
    }

    /**
     * Comité d'organisation en tableau (document de référence) :
     * N° | Nom & Prénom(s) | Grade | Contacts (tél. + e-mail) | Qualité.
     *
     * @param  Collection<int, CommitteeMember>  $members
     */
    private function committeeOrganisationTable(Section $section, Collection $members): void
    {
        $cols = ['N°' => 1.0, 'Nom & Prénom(s)' => 5.3, 'Grade' => 2.2, 'Contacts' => 5.0, 'Qualité' => 3.8];
        $totalTwip = (int) Converter::cmToTwip(array_sum($cols));

        $table = $section->addTable(['bordercolor' => 'BFBFBF', 'borderSize' => 4, 'cellMargin' => 60]);
        $table->addRow();
        foreach ($cols as $head => $cm) {
            // En-tête : fond gris F2F2F2, texte noir gras (doc de référence).
            $table->addCell((int) Converter::cmToTwip($cm), ['bgColor' => self::REF_HEAD_FILL])
                ->addText(mb_strtoupper($head, 'UTF-8'), ['bold' => true, 'size' => 10, 'color' => self::BLACK],
                    ['alignment' => 'center']);
        }

        // Le doc de référence scinde le comité d'organisation en « COORDINATION »
        // (bureau : président, vice-présidents, rapporteurs…) puis « MEMBRES ».
        $coordination = $members->filter(fn (CommitteeMember $m) => $this->isCoordinationQuality((string) $m->qualite));
        $membres = $members->reject(fn (CommitteeMember $m) => $this->isCoordinationQuality((string) $m->qualite));
        $grouped = $coordination->isNotEmpty() && $membres->isNotEmpty();

        $i = 0;
        $emitGroup = function (string $label) use ($table, $totalTwip): void {
            $table->addRow();
            $table->addCell($totalTwip, ['gridSpan' => 5, 'bgColor' => self::REF_GROUP_FILL])
                ->addText($label, ['bold' => true, 'size' => 10, 'color' => self::BLACK], ['alignment' => 'center']);
        };
        $emitMember = function (CommitteeMember $m, int $i) use ($table, $cols): void {
            $contacts = array_values(array_filter([trim((string) $m->phone), trim((string) $m->email)]));
            $table->addRow();
            $table->addCell((int) Converter::cmToTwip($cols['N°']))->addText((string) $i, ['size' => 10, 'color' => self::BLACK], ['alignment' => 'center']);
            $table->addCell((int) Converter::cmToTwip($cols['Nom & Prénom(s)']))->addText(trim((string) $m->person_name) ?: '-', ['size' => 10, 'color' => self::BLACK]);
            $table->addCell((int) Converter::cmToTwip($cols['Grade']))->addText(trim((string) $m->person_title), ['size' => 10, 'color' => self::BLACK], ['alignment' => 'center']);
            $table->addCell((int) Converter::cmToTwip($cols['Contacts']))->addText(implode('  ', $contacts), ['size' => 9, 'color' => self::BLACK]);
            $table->addCell((int) Converter::cmToTwip($cols['Qualité']))->addText(trim((string) $m->qualite), ['size' => 10, 'color' => self::BLACK]);
        };

        if ($grouped) {
            $emitGroup('COORDINATION');
            foreach ($coordination as $m) {
                $emitMember($m, ++$i);
            }
            $emitGroup('MEMBRES');
            foreach ($membres as $m) {
                $emitMember($m, ++$i);
            }
        } else {
            foreach ($members as $m) {
                $emitMember($m, ++$i);
            }
        }
    }

    /** Qualité relevant du bureau (coordination) du comité d'organisation. */
    private function isCoordinationQuality(string $qualite): bool
    {
        $q = Str::ascii(mb_strtolower(trim($qualite), 'UTF-8'));

        foreach (['president', 'vice', 'rapporteur', 'coordinat', 'secretaire', 'tresorier'] as $needle) {
            if (str_contains($q, $needle)) {
                return true;
            }
        }

        return false;
    }

    /**
     * « Communications en ateliers » (style général) : pour chaque axe/atelier,
     * le planning de ses communications orales programmées (horaire, titre,
     * intervenant), regroupé par jour. Les axes suivent l'ordre configuré de
     * l'événement, puis les éventuels axes hors liste.
     */
    private function addAteliers(Section $section, int $number = 0): void
    {
        // Structure PARTAGÉE avec l'aperçu PDF (acceptées + canoniques, par axe
        // puis par jour, ordre des axes réglés) : une seule source de vérité.
        $groups = self::ateliersGroups($this->event);

        if ($groups->isEmpty()) {
            return;
        }

        // « 5. COMMUNICATIONS EN ATELIERS » : item numéroté de la section A, centré,
        // gras 14 pt noir (doc de référence).
        $heading = ($number > 0 ? $number.'. ' : '').'COMMUNICATIONS EN ATELIERS';
        $font = (string) LivretAppearance::get($this->event, 'font_head', 'Jost');
        $section->addText($heading, ['bold' => true, 'size' => 14, 'color' => self::BLACK, 'name' => $font],
            ['alignment' => 'center', 'spaceBefore' => 300, 'spaceAfter' => 160, 'keepNext' => true]);

        foreach ($groups as $group) {
            // Titre d'atelier en TERRACOTTA (#C0504D), centré, gras 14 pt - couleur
            // signature du document de référence. addTitle (Heading1) → navigable ;
            // la couleur terracotta reste portée par le TextRun (le style est noir).
            // Le rang vient de l'atelier (`number`) ; le sous-titre « : … » n'apparaît
            // que s'il reste quelque chose après le préfixe « Atelier n ».
            $label = 'ATELIER '.$group['number']
                .($group['axis'] !== '' ? ' : '.mb_strtoupper($group['axis'], 'UTF-8') : '');
            $atelierRun = new TextRun;
            $atelierRun->addText($label,
                ['bold' => true, 'size' => 14, 'color' => self::TERRACOTTA, 'name' => $font]);
            $section->addTitle($atelierRun, 1);

            foreach ($group['days'] as $day => $slots) {
                $this->programmeDayTable($section, (string) $day, $slots);
            }
        }

        $section->addPageBreak();
    }

    /** Programme : sessions datées regroupées par jour (tableau Horaire / Session / Salle). */
    private function addProgramme(Section $section): void
    {
        $byDay = $this->programmeByDay();
        if ($byDay->isEmpty()) {
            return;
        }

        $this->partTitle($section, 'Programme');

        foreach ($byDay as $day => $slots) {
            $this->programmeDayTable($section, (string) $day, $slots);
        }

        $section->addPageBreak();
    }

    /**
     * Programme « Journées scientifiques » (fidèle au livret FSS) : par JOUR, puis
     * par SALLE, chaque salle enchaînant ses SÉANCES (horaire, intitulé, modérateur)
     * et, sous chacune, la liste des communications « Titre, INTERVENANT ».
     */
    private function addProgrammeFss(Section $section): void
    {
        $sessions = $this->event->sessions()->with(['slots.submission'])->get()
            ->filter(fn (EventSession $s) => $s->starts_at || $s->session_day)
            ->sortBy(fn (EventSession $s) => optional($s->starts_at ?: $s->session_day)->timestamp ?? PHP_INT_MAX)
            ->values();

        if ($sessions->isEmpty()) {
            // Repli : aucune séance horodatée → on garde le programme par jour.
            $this->addProgramme($section);

            return;
        }

        $this->partTitle($section, 'Programme');

        $byDay = $sessions->groupBy(fn (EventSession $s) => optional($s->starts_at ?: $s->session_day)->format('Y-m-d') ?? '');
        foreach ($byDay as $day => $daySessions) {
            $label = $day !== '' ? ucfirst(Carbon::parse($day)->translatedFormat('l j F Y')) : 'À programmer';
            $section->addText($label, ['bold' => true, 'size' => 14, 'color' => self::BLUE],
                ['spaceBefore' => 200, 'spaceAfter' => 80, 'keepNext' => true]);

            $byRoom = $daySessions->groupBy(fn (EventSession $s) => trim((string) $s->room) ?: 'Salle à définir');
            foreach ($byRoom as $room => $roomSessions) {
                $section->addText((string) $room, ['bold' => true, 'italic' => true, 'size' => 12, 'color' => '111827'],
                    ['spaceBefore' => 120, 'spaceAfter' => 40, 'keepNext' => true]);

                // Tableau « Horaire | Déroulé » à la manière du livret FSS.
                $table = $section->addTable(['bordercolor' => 'DDDDDD', 'borderSize' => 4, 'cellMargin' => 60]);
                foreach ($roomSessions as $sess) {
                    $this->programmeSessionRow($table, $sess);
                }
            }
        }

        $section->addPageBreak();
    }

    /** Une ligne de séance du tableau FSS : « Horaire » | intitulé + modérateur + communications. */
    private function programmeSessionRow(Table $table, EventSession $sess): void
    {
        $time = '';
        if ($sess->starts_at) {
            $time = $sess->starts_at->format('H\hi');
            if ($sess->ends_at) {
                $time .= '-'.$sess->ends_at->format('H\hi');
            }
        }

        $table->addRow();
        $table->addCell((int) Converter::cmToTwip(2.8), ['valign' => 'top'])
            ->addText($time !== '' ? $time : '-', ['bold' => true, 'size' => 9, 'color' => self::GREEN]);

        $cell = $table->addCell((int) Converter::cmToTwip(13.2), ['valign' => 'top']);
        $head = $cell->addTextRun(['spaceAfter' => 40]);
        $head->addText($sess->label !== '' ? $sess->label : 'Séance', ['bold' => true, 'size' => 10, 'color' => self::BLUE]);
        if (($mod = trim((string) $sess->moderator_name)) !== '') {
            $head->addText('   (Modérateur : '.$mod.')', ['size' => 9, 'italic' => true, 'color' => self::MUTED]);
        }

        foreach ($sess->slots->sortBy(fn (Slot $sl) => optional($sl->starts_at)->timestamp ?? 0) as $sl) {
            // ⚠️ Même règle que partout ailleurs dans le livre : seules les
            // communications retenues s'annoncent (cf. `isAcceptedCanonical`).
            if (! $sl->submission || ! $sl->submission->isAcceptedCanonical()) {
                continue;
            }
            $title = trim((string) $sl->submission->title);
            if ($title === '') {
                continue;
            }
            $presenter = $this->slotPresenter($sl);
            $line = $cell->addTextRun(['spaceAfter' => 20]);
            $line->addText($title, ['size' => 9]);
            if ($presenter !== '') {
                $line->addText(', '.$presenter, ['size' => 9, 'bold' => true, 'color' => self::MUTED]);
            }
        }
    }

    /** Intervenant d'une communication : auteur correspondant (ou 1er auteur), en MAJUSCULES. */
    private function slotPresenter(Slot $sl): string
    {
        if (! $sl->submission) {
            return '';
        }
        $authors = collect($sl->submission->authors ?? []);
        $index = $authors->search(fn ($author) => (bool) ($author['corresponding'] ?? false));
        $index = $index === false ? 0 : (int) $index;
        $corr = $authors->get($index);

        return $corr ? $this->authorName($corr, $sl->submission, $index) : '';
    }

    /**
     * Créneaux placés (communications), groupés et triés par jour. C'est le
     * programme réel des communications, comme le livret uk-colloques.
     *
     * @return Collection<int|string, \Illuminate\Database\Eloquent\Collection<int, Slot>>
     */
    private function programmeByDay(): Collection
    {
        // ⚠️ Même règle que la section Résumés (`Submission::isAcceptedCanonical`) :
        // une communication refusée après avoir été planifiée gardait son créneau,
        // et le programme imprimé l'annonçait alors qu'elle n'est pas au livre.
        return $this->event->slots()->with(['submission', 'session'])
            ->whereNotNull('starts_at')->orderBy('starts_at')->get()
            ->filter(fn (Slot $sl) => $sl->submission
                ? $sl->submission->isAcceptedCanonical()
                : trim((string) $sl->session_label) !== '')
            ->groupBy(fn (Slot $sl) => $sl->starts_at?->format('Y-m-d') ?? '')
            ->sortKeys();
    }

    /**
     * Tableau d'un jour du programme : Horaire | Communication (titre + auteurs) |
     * Salle / Session.
     *
     * Reçoit n'importe quelle collection de créneaux (programme par jour, ou un
     * regroupement par atelier) : la méthode ne fait que les PARCOURIR, d'où le
     * type `iterable` - qui accepte aussi bien un groupe Eloquent qu'un groupe de
     * `groupBy` (Support), sans heurter l'invariance de clé des génériques.
     *
     * @param  iterable<int|string, Slot>  $slots
     */
    private function programmeDayTable(Section $section, string $day, iterable $slots): void
    {
        $label = $day !== '' ? ucfirst(Carbon::parse($day)->translatedFormat('l j F Y')) : 'À programmer';
        $general = $this->isGeneral();

        // Style « Document général » : la colonne de tête est le CODE de la
        // communication (comme la colonne « Codes » du document de référence) ; le
        // programme ne répète ni les auteurs ni leurs affiliations (ils figurent
        // dans la section Résumés). Les autres styles gardent Horaire | Communication
        // (titre + auteurs) | Salle.
        $cols = $general
            ? ['Code' => 2.2, 'Horaire' => 2.6, 'Communication' => 7.4, 'Salle' => 3.8]
            : ['Horaire' => 2.6, 'Communication' => 9.4, 'Salle / Session' => 4];
        $headFill = $general ? self::REF_HEAD_FILL : 'F1F5F9';
        $headColor = $general ? self::BLACK : self::BLUE;

        $table = $section->addTable(['bordercolor' => $general ? 'BFBFBF' : 'DDDDDD', 'borderSize' => 4, 'cellMargin' => 60]);

        // ⚠️ Le bandeau de jour + l'en-tête du tableau sont des LIGNES D'EN-TÊTE
        // répétées (`tblHeader`) et insécables (`cantSplit`) : Word ne les laisse
        // jamais seuls en bas de page, le tableau démarre à la page suivante.
        $headerRow = ['tblHeader' => true, 'cantSplit' => true];

        if ($general) {
            // Bandeau de jour en bleu 95B3D7, pleine largeur (doc de référence).
            $table->addRow(null, $headerRow);
            $table->addCell((int) Converter::cmToTwip(array_sum($cols)), ['gridSpan' => count($cols), 'bgColor' => self::REF_DAY_FILL])
                ->addText($label, ['bold' => true, 'size' => 11, 'color' => self::BLACK], ['alignment' => 'center']);
        } else {
            $section->addText($label, ['bold' => true, 'size' => 13, 'color' => self::BLUE], ['spaceBefore' => 160, 'spaceAfter' => 60, 'keepNext' => true]);
        }

        $table->addRow(null, $general ? $headerRow : null);
        foreach ($cols as $head => $cm) {
            $table->addCell((int) Converter::cmToTwip($cm), ['bgColor' => $headFill])
                ->addText($head, ['bold' => true, 'size' => 10, 'color' => $headColor]);
        }
        foreach ($slots as $sl) {
            $h = $sl->starts_at?->format('H:i') ?? '-';
            if ($sl->ends_at) {
                $h .= '-'.$sl->ends_at->format('H:i');
            }
            $title = trim((string) ($sl->submission?->title ?: $sl->session_label));
            $room = trim((string) ($sl->room ?: $sl->session?->room));
            $sessLabel = trim((string) ($sl->session?->label ?: $sl->session_label));

            $table->addRow();
            if ($general) {
                $code = trim((string) $sl->submission?->ref_code);
                $table->addCell((int) Converter::cmToTwip($cols['Code']))->addText($code !== '' ? $code : '-', ['size' => 10, 'bold' => true, 'color' => self::BLACK]);
                $table->addCell((int) Converter::cmToTwip($cols['Horaire']))->addText($h, ['size' => 10, 'color' => self::BLACK]);
                $table->addCell((int) Converter::cmToTwip($cols['Communication']))->addText($title !== '' ? self::bindPunctuation($title) : '-', ['size' => 10, 'color' => self::BLACK]);
                $table->addCell((int) Converter::cmToTwip($cols['Salle']))->addText($room !== '' ? $room : '-', ['size' => 10, 'color' => self::BLACK]);

                continue;
            }

            $authors = $this->slotAuthors($sl);
            $table->addCell((int) Converter::cmToTwip(2.6))->addText($h, ['size' => 10]);
            $cell = $table->addCell((int) Converter::cmToTwip(9.4));
            $cell->addText($title !== '' ? $title : '-', ['size' => 10]);
            if ($authors !== '') {
                $cell->addText($authors, ['size' => 9, 'italic' => true, 'color' => self::MUTED]);
            }
            $cell3 = $table->addCell((int) Converter::cmToTwip(4));
            if ($room !== '') {
                $cell3->addText($room, ['size' => 10]);
            }
            if ($sessLabel !== '' && $sessLabel !== $title) {
                $cell3->addText($sessLabel, ['size' => 9, 'color' => self::MUTED]);
            }
        }
    }

    /** Auteurs d'un créneau : « Nom (Affiliation); … » (harmonisation appliquée). */
    private function slotAuthors(Slot $sl): string
    {
        if (! $sl->submission) {
            return '';
        }

        return collect($sl->submission->authors ?? [])->map(function ($a, $index) use ($sl) {
            $name = $this->authorName($a, $sl->submission, (int) $index);
            $aff = $this->mapAff(trim((string) ($a['affiliation'] ?? '')));

            return $name === '' ? '' : ($aff !== '' ? $name.' ('.$aff.')' : $name);
        })->filter()->implode('; ');
    }

    /** Coordonnées de l'auteur correspondant : « Email : … Tél : … » (parties présentes). */
    private function contactLine(array $corr): string
    {
        $bits = [];
        if (($corr['email'] ?? '') !== '') {
            $bits[] = 'Email : '.$corr['email'];
        }
        if (($corr['phone'] ?? '') !== '') {
            $bits[] = 'Tél : '.$corr['phone'];
        }

        return implode('   ', $bits);
    }

    /** Résumés des communications, regroupés par axe (titres en MAJUSCULES). */
    private function addBody(Section $section): void
    {
        $general = $this->isGeneral();
        if ($general) {
            // « B) Résumés des communications » : gras 16 pt noir, centré (doc de référence).
            $this->generalSectionTitle($section, 'B) Résumés des communications', 16, 'center');
        } else {
            $this->partTitle($section, 'Résumés des communications');
        }

        if ($this->groups->isEmpty()) {
            $section->addText('Aucune communication acceptée pour le moment.', ['italic' => true, 'color' => self::MUTED]);

            return;
        }

        $sizeAbstract = (int) LivretAppearance::get($this->event, 'size_abstract', 12);
        $sizeAuthors = (int) LivretAppearance::get($this->event, 'size_authors', 10);
        $sizeBody = (int) LivretAppearance::get($this->event, 'size_body', 11);
        $alignBody = LivretAppearance::get($this->event, 'align_body', 'Justifié') === 'Gauche' ? 'left' : 'both';
        $number = (bool) LivretAppearance::get($this->event, 'number_abstracts', false);
        $sizeAxis = (int) LivretAppearance::get($this->event, 'size_axis', 16);
        $primary = self::hex((string) LivretAppearance::get($this->event, 'color_primary', '#223ea4'), self::BLUE);
        $fontHead = (string) LivretAppearance::get($this->event, 'font_head', 'Jost');
        $alignAxis = $this->align('livret.align_axis');

        $n = 0;
        $axisNo = 0;
        foreach ($this->groups as $track => $submissions) {
            $axisNo++;
            if ($general) {
                // Axe « 1- DEMOGRAPHIE … » : gras 14 pt noir, centré, préfixé (doc de
                // référence). addTitle (Heading1) → navigable (volet + sommaire) ;
                // l'allure (Jost 14 pt noir centré) est portée par le style Heading1.
                // Dé-préfixage par la source unique partagée avec le programme/PDF.
                $section->addTitle($axisNo.'- '.mb_strtoupper(self::ateliersAxisLabel((string) $track), 'UTF-8'), 1);
            } else {
                $section->addText(mb_strtoupper((string) $track, 'UTF-8'),
                    ['bold' => true, 'size' => $sizeAxis, 'color' => $primary, 'name' => $fontHead],
                    ['alignment' => $alignAxis, 'spaceBefore' => 320, 'spaceAfter' => 160, 'keepNext' => true]);
            }

            foreach ($submissions as $s) {
                $n++;
                $this->addAbstract($section, $s, $n, $number, $sizeAbstract, $sizeAuthors, $sizeBody, $alignBody);
            }
        }
    }

    /** Index des auteurs : noms triés alphabétiquement, suivis des références de leurs résumés. */
    private function addAuthorIndex(Section $section): void
    {
        if ($this->authorIndex === []) {
            return;
        }

        $section->addPageBreak();
        $this->partTitle($section, 'Index des auteurs');

        $names = array_keys($this->authorIndex);
        usort($names, fn ($a, $b) => strnatcasecmp(
            Str::ascii($a),
            Str::ascii($b),
        ));

        foreach ($names as $name) {
            $refs = array_values(array_unique($this->authorIndex[$name]));
            natcasesort($refs);
            $run = $section->addTextRun(['spaceAfter' => 40]);
            $run->addText($name.'  ', ['size' => 10, 'bold' => true]);
            $run->addText(implode(', ', $refs), ['size' => 10, 'color' => self::MUTED]);
        }
    }

    /** Rendu d'un résumé : référence, titre, auteurs+affiliations, correspondant, corps, mots-clés. */
    private function addAbstract(Section $section, Submission $s, int $n, bool $number, int $sizeAbstract, int $sizeAuthors, int $sizeBody, string $alignBody): void
    {
        $fss = $this->isFss();
        $general = $this->isGeneral();
        $refLabel = trim((string) $s->ref_code);
        $indexRef = $refLabel !== '' ? $refLabel : '#'.$n;

        if ($general) {
            // Style « Document général » : code à DROITE (gras noir), titre CENTRÉ
            // (gras noir), auteurs/affiliations CENTRÉS - dispositions exactes du
            // document de référence (Titre1 jc=right, Titre2/Titre4 jc=center).
            if ($refLabel !== '') {
                // Code de communication « AVD-xxx » : à droite, gras 11 pt noir.
                // addTitle (Heading2) → chaque résumé est atteignable par son code
                // dans le volet de navigation et le sommaire du .docx.
                $section->addTitle($refLabel, 2);
            }
            $title = $number ? $n.'. '.$s->title : (string) $s->title;
            $section->addText(self::bindPunctuation($title), ['bold' => true, 'size' => $sizeAbstract, 'color' => self::BLACK],
                ['alignment' => 'center', 'spaceAfter' => 40, 'keepNext' => true]);
        } elseif ($fss) {
            // Style FSS : « RÉF. TITRE EN MAJUSCULES » sur un seul paragraphe (comme le livret).
            $prefix = $refLabel !== '' ? $refLabel.'. ' : ($number ? $n.'. ' : '');
            $run = $section->addTextRun(['alignment' => $this->align('livret.align_titles'), 'spaceBefore' => 240, 'spaceAfter' => 40, 'keepNext' => true]);
            if ($prefix !== '') {
                $run->addText($prefix, ['bold' => true, 'size' => $sizeAbstract, 'color' => self::BLUE]);
            }
            $run->addText(mb_strtoupper((string) $s->title, 'UTF-8'), ['bold' => true, 'size' => $sizeAbstract, 'color' => self::BLUE]);
        } else {
            // Style par défaut : référence centrée, puis titre.
            if ($refLabel !== '') {
                $section->addText($refLabel, ['bold' => true, 'size' => 10, 'color' => self::MUTED], ['alignment' => 'center', 'spaceBefore' => 200, 'keepNext' => true]);
            }
            // keepNext : le titre ne doit pas être séparé de ses auteurs par une coupure.
            $title = $number ? $n.'. '.$s->title : (string) $s->title;
            $section->addText($title, ['bold' => true, 'size' => $sizeAbstract, 'color' => self::BLUE], ['alignment' => $this->align('livret.align_titles'), 'spaceAfter' => 40, 'keepNext' => true]);
        }

        // Auteurs + affiliations numérotées (exposants) + auteur correspondant (*).
        // ⚠️ Nom affiché en MAJUSCULES (convention du livret).
        $people = collect($s->authors ?? [])->map(fn ($a, $index) => [
            'name' => $this->authorName($a, $s, (int) $index),
            'aff' => $this->mapAff(trim((string) ($a['affiliation'] ?? ''))),
            'email' => trim((string) ($a['email'] ?? '')),
            'phone' => trim((string) ($a['phone'] ?? '')),
            'corr' => (bool) ($a['corresponding'] ?? false),
        ])->filter(fn ($p) => $p['name'] !== '')->values();

        // Index des auteurs : on mémorise la référence de ce résumé pour chaque auteur.
        foreach ($people as $p) {
            $this->authorIndex[$p['name']][] = $indexRef;
        }

        if ($people->isNotEmpty()) {
            // Numérotation des affiliations distinctes de ce résumé.
            $affs = [];
            $affIndex = [];
            foreach ($people as $p) {
                if ($p['aff'] === '') {
                    continue;
                }
                $key = mb_strtolower($p['aff'], 'UTF-8');
                if (! isset($affIndex[$key])) {
                    $affIndex[$key] = count($affs) + 1;
                    $affs[] = $p['aff'];
                }
            }

            // Style des auteurs : doc de référence = gras italique 12 pt noir ;
            // sinon gris discret à la taille configurée.
            $authColor = $general ? self::BLACK : self::MUTED;
            $authSize = $general ? 12 : $sizeAuthors;
            $authFmt = $general ? ['bold' => true, 'italic' => true] : [];
            $affColor = $general ? self::BLACK : self::MUTED;
            $affSize = $general ? 10 : 9;

            // Ligne des auteurs avec exposants : CENTRÉE pour le style général (doc de
            // référence), sinon selon `livret.align_authors`. keepNext : l'en-tête
            // (réf. -> correspondant) reste solidaire, pas de code seul en bas de page.
            $authAlign = $general ? 'center' : $this->align('livret.align_authors');
            $run = $section->addTextRun(['alignment' => $authAlign, 'spaceAfter' => $affs === [] ? 80 : 20, 'keepNext' => true]);
            $first = true;
            foreach ($people as $p) {
                if (! $first) {
                    $run->addText(', ', ['size' => $authSize, 'color' => $authColor] + $authFmt);
                }
                $first = false;
                $run->addText($p['name'], ['size' => $authSize, 'color' => $authColor] + $authFmt);
                if ($p['aff'] !== '') {
                    $run->addText((string) $affIndex[mb_strtolower($p['aff'], 'UTF-8')], ['size' => $authSize, 'color' => $authColor, 'superScript' => true]);
                }
                if ($p['corr']) {
                    $run->addText('*', ['size' => $authSize, 'color' => $authColor, 'superScript' => true]);
                }
            }

            // Affiliations numérotées (centrées, italiques dans le style général).
            $affPara = $general ? ['alignment' => 'center', 'spaceAfter' => 10, 'keepNext' => true] : ['spaceAfter' => 10, 'keepNext' => true];
            foreach ($affs as $i => $aff) {
                $affRun = $section->addTextRun($affPara);
                $affRun->addText((string) ($i + 1).' ', ['size' => $affSize, 'color' => $affColor, 'superScript' => true]);
                $affRun->addText($aff, ['size' => $affSize, 'italic' => true, 'color' => $affColor]);
            }

            // Auteur correspondant : nom (e-mail : … ; Tél : …) - centré dans le style général.
            $corr = $people->firstWhere('corr', true);
            if ($corr && ($contact = $this->contactLine($corr)) !== '') {
                $corrPara = ['spaceBefore' => 20, 'spaceAfter' => 60];
                if ($general) {
                    $corrPara['alignment'] = 'center';
                }
                $section->addText('*Auteur correspondant : '.$corr['name'].'   '.$contact,
                    ['size' => $general ? 10 : 9, 'italic' => $general, 'color' => $affColor], $corrPara);
            }
        }

        // Corps du résumé (préserve gras/italique de base). Le style FSS n'affiche PAS
        // de libellé « RÉSUMÉ : » (le texte suit directement, comme dans le livret).
        $paragraphs = $this->paragraphs((string) $s->abstract);
        if ($paragraphs !== []) {
            // keepNext : le libellé « Résumé » reste collé au début de son contenu, il
            // ne se retrouve jamais seul en bas de page (le contenu, lui, peut se couper).
            if ($general) {
                // Doc de référence : libellé « Résumé » noir gras (pas « RÉSUMÉ : » bleu).
                $section->addText('Résumé', ['bold' => true, 'size' => 11, 'color' => self::BLACK], ['spaceBefore' => 40, 'spaceAfter' => 20, 'keepNext' => true]);
            } elseif (! $fss) {
                $section->addText('RÉSUMÉ :', ['bold' => true, 'size' => 10, 'color' => self::BLUE], ['spaceBefore' => 40, 'spaceAfter' => 20, 'keepNext' => true]);
            }
            foreach ($paragraphs as $i => $paragraphHtml) {
                $run = $section->addTextRun(['alignment' => $alignBody, 'spaceBefore' => ($fss && $i === 0) ? 40 : 0, 'spaceAfter' => 80]);
                $this->addInlineRuns($run, $paragraphHtml, $sizeBody);
            }
        }

        // Mots-clés (trait d'union dans le style FSS, conforme au livret).
        if ($keywords = SubmissionKeywords::join($s->keywords, (new SubmissionFormConfig($s->event))->keywordSeparator())) {
            $kwLabelFmt = ['bold' => true, 'size' => $sizeBody];
            if ($general) {
                $kwLabelFmt['color'] = self::BLACK;
            }
            $kwRun = $section->addTextRun(['spaceBefore' => 40, 'spaceAfter' => 40]);
            $kwRun->addText($fss ? 'Mots-clés : ' : 'Mots clés : ', $kwLabelFmt);
            $kwRun->addText($keywords, ['italic' => true, 'size' => $sizeBody, 'color' => $general ? self::BLACK : self::MUTED]);
        }
    }

    /**
     * Découpe un contenu (HTML ou texte) en paragraphes bruts (fragments HTML inline conservés).
     *
     * @return list<string>
     */
    private function paragraphs(string $content): array
    {
        $content = trim($content);
        if ($content === '') {
            return [];
        }

        // Paragraphes/ retours HTML → séparateurs, puis on scinde.
        $normalized = preg_replace('#<\s*/?\s*(p|div)[^>]*>#i', "\n\n", $content);
        $normalized = preg_replace('#<\s*br\s*/?>#i', "\n", (string) $normalized);
        $parts = preg_split('/\R{2,}/', (string) $normalized) ?: [$normalized];

        $out = [];
        foreach ($parts as $p) {
            $p = trim((string) $p);
            if ($p !== '') {
                $out[] = $p;
            }
        }

        return $out !== [] ? $out : [$content];
    }

    /** Ajoute à un TextRun le fragment (gras/italique de base conservés, autres balises retirées). */
    private function addInlineRuns(TextRun $run, string $html, int $size): void
    {
        // Tokenise sur <b>/<strong>/<i>/<em> (ouvrants et fermants).
        $tokens = preg_split('#(</?(?:b|strong|i|em)\b[^>]*>)#i', $html, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY) ?: [];
        $bold = false;
        $italic = false;
        foreach ($tokens as $tok) {
            if (preg_match('#^<(/?)(b|strong|i|em)\b#i', $tok, $m)) {
                $closing = $m[1] === '/';
                $isBold = in_array(strtolower($m[2]), ['b', 'strong'], true);
                if ($isBold) {
                    $bold = ! $closing;
                } else {
                    $italic = ! $closing;
                }

                continue;
            }
            // Retire uniquement les vraies balises (celles commençant par une lettre),
            // pour préserver les « < » / « > » nus (ex. seuils « < 5 », « > 1 »).
            $text = html_entity_decode((string) preg_replace('#</?[a-zA-Z][^>]*>#', '', $tok), ENT_QUOTES | ENT_HTML5, 'UTF-8');
            if ($text === '') {
                continue;
            }
            $run->addText($text, array_filter(['size' => $size, 'bold' => $bold, 'italic' => $italic]));
        }
    }

    /** Nom harmonisé : map IA si disponible, sinon normalisation déterministe, sinon original. */
    private function mapName(string $name): string
    {
        $name = trim($name);
        if ($name === '') {
            return '';
        }
        if (isset($this->nameMap[$name])) {
            return $this->nameMap[$name];
        }

        return $this->harmonize ? $this->harmonizeName($name) : $name;
    }

    /** Affiliation harmonisée : map IA si disponible, sinon original. */
    private function mapAff(string $aff): string
    {
        $aff = trim($aff);

        return $aff !== '' && isset($this->affMap[$aff]) ? $this->affMap[$aff] : $aff;
    }

    /**
     * Harmonisation IA (logique uk-colloques) : collecte tous les noms et affiliations
     * des résumés, demande à l'IA une correspondance normalisée (JSON), et remplit les
     * maps. En cas d'IA indisponible ou d'échec, les maps restent vides (repli déterministe).
     */
    private function buildAiMaps(): void
    {
        $manager = app(AiManager::class);
        if (! $manager->resolve()) {
            return;
        }

        $names = [];
        $affs = [];
        foreach ($this->groups as $submissions) {
            foreach ($submissions as $s) {
                foreach (collect($s->authors ?? []) as $a) {
                    $n = trim((string) ($a['name'] ?? ''));
                    $f = trim((string) ($a['affiliation'] ?? ''));
                    if ($n !== '') {
                        $names[$n] = true;
                    }
                    if ($f !== '') {
                        $affs[$f] = true;
                    }
                }
            }
        }
        if ($names === [] && $affs === []) {
            return;
        }

        $instruction = "Tu normalises des noms d'auteurs et des affiliations d'un livre des résumés de colloque.\n"
            ."- Noms : mets le NOM de famille en capitales et le prénom en casse normale, un seul espace (ex. « awa kodjo » → « Awa KODJO »).\n"
            ."- Affiliations : unifie les variantes d'une même institution, corrige la casse et la ponctuation.\n"
            ."N'invente rien, ne traduis pas, ne supprime aucune entrée.\n\n"
            .'Noms : '.json_encode(array_keys($names), JSON_UNESCAPED_UNICODE)."\n"
            .'Affiliations : '.json_encode(array_keys($affs), JSON_UNESCAPED_UNICODE)."\n\n"
            .'Réponds UNIQUEMENT en JSON : {"names": {"original": "normalisé", …}, "affiliations": {"original": "normalisé", …}}.';

        try {
            $res = $manager->extract($instruction);
        } catch (\Throwable $e) {
            report($e);

            return;
        }

        foreach ((array) ($res['names'] ?? []) as $orig => $norm) {
            if (isset($names[$orig]) && is_string($norm) && trim($norm) !== '') {
                $this->nameMap[(string) $orig] = trim($norm);
            }
        }
        foreach ((array) ($res['affiliations'] ?? []) as $orig => $norm) {
            if (isset($affs[$orig]) && is_string($norm) && trim($norm) !== '') {
                $this->affMap[(string) $orig] = trim($norm);
            }
        }
    }

    /**
     * Harmonisation déterministe d'un nom d'auteur : espaces normalisés, casse propre
     * (Prénom Nom), ponctuation resserrée. Repli sobre de l'« harmonisation IA ».
     */
    private function harmonizeName(string $name): string
    {
        $name = trim(preg_replace('/\s+/u', ' ', $name));
        $name = preg_replace('/\s*,\s*/', ', ', $name);
        $name = preg_replace('/\s*-\s*/', '-', $name);

        return preg_replace_callback('/\p{L}[\p{L}\'’-]*/u', function ($m) {
            $word = $m[0];
            if (in_array(mb_strtolower($word), ['de', 'du', 'des', 'van', 'von', 'da', 'di', 'la', 'le'], true)) {
                return mb_strtolower($word);
            }

            return mb_convert_case($word, MB_CASE_TITLE, 'UTF-8');
        }, $name);
    }

    /** Alignement OOXML d'après un réglage d'apparence (Centré/Gauche/Droite/Justifié). */
    private function align(string $key, string $default = 'Centré'): string
    {
        return match ((string) LivretAppearance::get($this->event, str_replace('livret.', '', $key), $default)) {
            'Gauche' => 'left',
            'Droite' => 'right',
            'Justifié' => 'both',
            default => 'center',
        };
    }

    /** Titre de section (paragraphe gras, aligné selon `livret.align_titles`). */
    private function partTitle(Section $section, string $text): void
    {
        $primary = self::hex((string) LivretAppearance::get($this->event, 'color_primary', '#223ea4'), self::BLUE);
        $fontHead = (string) LivretAppearance::get($this->event, 'font_head', 'Jost');
        $section->addText($text, ['bold' => true, 'size' => 18, 'color' => $primary, 'name' => $fontHead],
            ['alignment' => $this->align('livret.align_titles'), 'spaceBefore' => 240, 'spaceAfter' => 160, 'keepNext' => true]);
    }

    /**
     * Casse d'un nom d'auteur selon le style. ⚠️ SOURCE UNIQUE partagée par le
     * .docx (ce générateur) ET le PDF (vue dompdf `documents/livret-pdf`), pour
     * que les deux rendus ne divergent JAMAIS sur la casse :
     *  - « general » (Document général du colloque) : nom TEL QU'IL EST SAISI,
     *    au format « NOM Prénom » du document de référence (nom de famille en
     *    capitales, prénom en casse normale) - pas de passage en majuscules.
     *  - autres styles (défaut, FSS) : nom ENTIÈREMENT en majuscules, convention
     *    historique du livret (corps du résumé, programme et index des auteurs).
     * Le PDF l'appelle en statique ; ne pas réintroduire un `mb_strtoupper` ou un
     * `trim` isolé dans la vue (c'est ainsi que la divergence était née).
     */
    public static function authorCase(string $name, bool $general): string
    {
        $name = trim($name);

        return $general ? $name : mb_strtoupper($name, 'UTF-8');
    }

    /**
     * Palette CSS (#rrggbb) du style « general », SOURCE UNIQUE partagée avec le
     * PDF : la vue dompdf lit ces valeurs au lieu de recopier les hexadécimaux,
     * donc changer une couleur ici la change dans les DEUX rendus. Les clés
     * `black`/`terracotta`/`day_fill`/`head_fill` reprennent les constantes du
     * .docx ; `border` est le filet de tableau (propre au PDF).
     *
     * @return array<string, string>
     */
    public static function generalCssPalette(): array
    {
        return [
            'black' => '#'.self::BLACK,
            'terracotta' => '#'.self::TERRACOTTA,
            'day_fill' => '#'.self::REF_DAY_FILL,
            'head_fill' => '#'.self::REF_HEAD_FILL,
            'border' => '#bfbfbf',
        ];
    }

    private function displayName(string $name): string
    {
        return self::authorCase($name, $this->isGeneral());
    }

    /** Applique l'ordre de l'événement avant la casse du modèle de livre. */
    private function authorName(array $author, ?Submission $submission = null, ?int $index = null): string
    {
        $original = trim((string) ($author['name'] ?? ''));
        $hasParts = filled($author['first_name'] ?? null) || filled($author['last_name'] ?? null);
        if (! $hasParts) {
            $author['name'] = $this->mapName($original);
        }
        $name = $submission && $index !== null
            ? EventNameFormat::submissionAuthor($this->event, $submission, $author, $index)
            : EventNameFormat::author($this->event, $author);

        return $this->displayName($name);
    }

    /**
     * Ordre + libellés des comités selon le style. SOURCE UNIQUE partagée par le
     * .docx et l'aperçu PDF : « general » suit le document de référence
     * (supervision → scientifique → organisation), les autres l'ordre UK/UP.
     *
     * @return array<string, string>
     */
    public static function committeeOrder(bool $general): array
    {
        return $general ? self::GENERAL_COMMITTEE_ORDER : self::COMMITTEE_ORDER;
    }

    /**
     * Libellé d'axe d'un atelier : le préfixe « Atelier n - » (quel que soit le
     * tiret) est retiré pour ne pas le doubler avec « ATELIER n : » posé par le
     * livret. SOURCE UNIQUE du dé-préfixage (programme général ET section résumés,
     * .docx comme PDF).
     */
    public static function ateliersAxisLabel(string $track): string
    {
        $axis = trim((string) preg_replace('/^\s*atelier\s*\d*\s*[-:\x{2013}\x{2014}]?\s*/iu', '', $track));

        return $axis !== '' ? $axis : trim($track);
    }

    /**
     * Ateliers du style « general » : communications orales programmées (acceptées
     * + canoniques) regroupées par ATELIER puis par JOUR. SOURCE UNIQUE partagée par
     * le .docx (`addAteliers`) et l'aperçu PDF (`DocumentController::livretViewData`) :
     * on ne rend QUE la structure, l'intitulé « ATELIER n : … » étant composé par
     * chaque appelant (style de titre Word d'un côté, `<p>` de l'autre).
     *
     * ⚠️⚠️ **UN ATELIER REGROUPE PLUSIEURS AXES** (`Atelier::tracks`), ce n'est PAS
     * un axe. Le livret groupait par `submission->track` (l'axe) et sortait donc **un
     * atelier par axe** - six « ateliers » pour un colloque qui en compte deux. On
     * rattache chaque communication à SON atelier par la clé normalisée de son axe
     * (`ReviewerExpertise::axisKey`, la même grammaire que partout ailleurs : un tiret
     * cadratin ou une casse ne doit pas rompre l'appariement). `number` porte le rang
     * de l'atelier pour que l'intitulé ne dérive pas si un atelier n'a aucune
     * communication programmée.
     *
     * Repli (aucun atelier déclaré) : un groupe PAR AXE, comme avant, dans l'ordre
     * des axes réglés (`settings['tracks']`) puis les axes hors liste.
     *
     * @return Collection<int, array{number: int, track: string, axis: string, days: Collection<int|string, Collection<int, Slot>>}>
     */
    public static function ateliersGroups(Event $event): Collection
    {
        // `toBase()` : on travaille en collections de base (Support), pas Eloquent -
        // c'est une structure de données, pas un jeu de modèles, et le type de retour
        // reste homogène pour les deux appelants.
        $slots = $event->slots()->with(['submission', 'session'])
            ->whereNotNull('starts_at')->orderBy('starts_at')->get()->toBase()
            ->filter(fn (Slot $sl) => $sl->submission && $sl->submission->isAcceptedCanonical());

        $ateliers = $event->ateliers()->orderBy('ordre')->orderBy('id')->get();

        if ($ateliers->isNotEmpty()) {
            // Clé normalisée de l'axe -> rang (0-based) de l'atelier qui le couvre.
            $atelierOf = [];
            foreach ($ateliers as $i => $atelier) {
                foreach ((array) $atelier->tracks as $t) {
                    $k = ReviewerExpertise::axisKey((string) $t);
                    if ($k !== '' && ! isset($atelierOf[$k])) {
                        $atelierOf[$k] = $i;
                    }
                }
            }

            // -1 = communication dont l'axe n'est rattaché à aucun atelier.
            // `$sl->submission` est garanti non nul par le filtre ci-dessus (d'où `->`,
            // pas `?->` : Larastan type la relation BelongsTo comme non-nulle).
            $byAtelier = $slots->groupBy(
                fn (Slot $sl) => $atelierOf[ReviewerExpertise::axisKey((string) $sl->submission->track)] ?? -1
            );

            $groups = [];
            foreach ($ateliers as $i => $atelier) {
                if (! $byAtelier->has($i)) {
                    continue; // atelier sans communication programmée
                }
                $groups[] = [
                    'number' => $i + 1,
                    'track' => (string) $atelier->title,
                    // Sous-titre = le titre de l'atelier débarrassé de son préfixe
                    // « Atelier n » : vide quand il n'y a rien d'autre (l'intitulé
                    // reste alors « ATELIER n » tout court, sans « : »).
                    'axis' => trim((string) preg_replace('/^\s*atelier\s*\d*\s*[-:\x{2013}\x{2014}]?\s*/iu', '', (string) $atelier->title)),
                    'days' => $byAtelier[$i]
                        ->groupBy(fn (Slot $sl) => $sl->starts_at?->format('Y-m-d') ?? '')->sortKeys(),
                ];
            }

            if ($byAtelier->has(-1)) {
                $groups[] = [
                    'number' => count($groups) + 1,
                    'track' => 'Autres',
                    'axis' => 'Autres communications',
                    'days' => $byAtelier[-1]
                        ->groupBy(fn (Slot $sl) => $sl->starts_at?->format('Y-m-d') ?? '')->sortKeys(),
                ];
            }

            return collect($groups);
        }

        // Repli : aucun atelier déclaré -> un groupe par axe (comportement historique).
        $byTrack = $slots->groupBy(fn (Slot $sl) => trim((string) $sl->submission?->track) ?: 'Autres');

        $order = collect($event->settings['tracks'] ?? [])
            ->map(fn ($t) => trim((string) $t))->filter()->values();
        $keys = $byTrack->keys()
            ->sortBy(fn ($track) => ($i = $order->search((string) $track, true)) === false ? PHP_INT_MAX : $i)
            ->values();

        // ⚠️ Accès `$byTrack[$track]` (et non `get() ?? collect()`) : chaque clé
        // vient de `$byTrack`, donc la valeur est le groupe de créneaux typé - le
        // repli `?? collect()` ferait perdre le type Slot à l'analyse statique.
        $groups = [];
        foreach ($keys as $track) {
            $days = $byTrack[$track]
                ->groupBy(fn (Slot $sl) => $sl->starts_at?->format('Y-m-d') ?? '')->sortKeys();
            if ($days->isEmpty()) {
                continue;
            }
            $groups[] = [
                'number' => count($groups) + 1,
                'track' => (string) $track,
                'axis' => self::ateliersAxisLabel((string) $track),
                'days' => $days,
            ];
        }

        return collect($groups);
    }

    /**
     * Lie la ponctuation haute française (? ! : ; »), et la rare majuscule isolée,
     * au mot qui précède par une ESPACE INSÉCABLE (U+00A0) : sans cela, dans une
     * colonne étroite, un « ? » (ou « : ») se retrouve seul sur la dernière ligne.
     * C'est aussi la règle typographique française (espace insécable avant ces signes).
     */
    public static function bindPunctuation(string $text): string
    {
        return preg_replace('/ +([?!:;»])/u', "\u{00A0}$1", $text) ?? $text;
    }

    /** Normalise une couleur CSS (#rrggbb) en hex Word (RRGGBB), avec repli. */
    private static function hex(string $value, string $fallback): string
    {
        $v = ltrim(trim($value), '#');

        return preg_match('/^[0-9a-fA-F]{6}$/', $v) ? strtoupper($v) : $fallback;
    }
}
