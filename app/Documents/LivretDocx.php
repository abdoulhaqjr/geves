<?php

namespace App\Documents;

use App\AI\AiManager;
use App\Models\Event;
use App\Models\EventSession;
use App\Models\Slot;
use App\Models\Submission;
use App\Support\EventSettings;
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

    /** Styles intégrés disponibles (modèle actif « default » ou « fss »). */
    public const STYLES = [
        'default' => 'Modèle par défaut',
        'fss' => 'Modèle 2 - Journées scientifiques',
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

    /** Construit le document phpword complet (ordre du booklet uk-colloques). */
    public function build(): PhpWord
    {
        // PhpWord n'échappe PAS le texte par défaut : sans ceci, tout « & », « < » ou « > »
        // (ex. « Santé & environnement ») produit un document.xml malformé → .docx illisible.
        Settings::setOutputEscapingEnabled(true);

        $doc = new PhpWord;

        $doc->setDefaultFontName((string) EventSettings::get($this->event, 'livret.font_body', 'Times New Roman'));
        $doc->setDefaultFontSize((int) EventSettings::get($this->event, 'livret.size_body', 11));

        // Les titres sont des paragraphes en gras (addText), PAS des addTitle : Word
        // ajoutait un numéro après chaque titre (style Titre indexé) et exigeait un TOC.

        $section = $doc->addSection([
            'marginTop' => Converter::cmToTwip(2.2),
            'marginBottom' => Converter::cmToTwip(2.2),
            'marginLeft' => Converter::cmToTwip(2.2),
            'marginRight' => Converter::cmToTwip(2.2),
        ]);

        // Pied de page paginé (vide sur la page de garde). Réglage `livret.page_numbers`.
        if ((bool) EventSettings::get($this->event, 'livret.page_numbers', true)) {
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
        $this->addCommissionsProgramme($section);
        $this->isFss() ? $this->addProgrammeFss($section) : $this->addProgramme($section);
        $this->addBody($section);

        // Index des auteurs (renvoie au numéro de résumé). Réglage `livret.author_index`.
        if ((bool) EventSettings::get($this->event, 'livret.author_index', true)) {
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
        $doc->setDefaultFontName((string) EventSettings::get($this->event, 'livret.font_body', 'Times New Roman'));
        $doc->setDefaultFontSize((int) EventSettings::get($this->event, 'livret.size_body', 11));
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
        $sizeAxis = (int) EventSettings::get($this->event, 'livret.size_axis', 16);
        $primary = self::hex((string) EventSettings::get($this->event, 'livret.color_primary', '#223ea4'), self::BLUE);
        $fontHead = (string) EventSettings::get($this->event, 'livret.font_head', 'Jost');
        $sizeAbstract = (int) EventSettings::get($this->event, 'livret.size_abstract', 12);
        $sizeAuthors = (int) EventSettings::get($this->event, 'livret.size_authors', 10);
        $sizeBody = (int) EventSettings::get($this->event, 'livret.size_body', 11);
        $alignBody = EventSettings::get($this->event, 'livret.align_body', 'Justifié') === 'Gauche' ? 'left' : 'both';
        $number = (bool) EventSettings::get($this->event, 'livret.number_abstracts', false);

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
        $coverSize = (int) EventSettings::get($this->event, 'livret.size_cover', 24);
        $showTheme = (bool) EventSettings::get($this->event, 'livret.show_theme', true);
        $showDates = (bool) EventSettings::get($this->event, 'livret.show_dates', true);

        // En-tête : nom du colloque en majuscules.
        if ($name = trim((string) $this->event->series?->name)) {
            $section->addText(mb_strtoupper($name, 'UTF-8'), ['bold' => true, 'size' => 12, 'color' => self::BLUE], ['alignment' => 'center', 'spaceAfter' => 80]);
        }

        // Logo(s) de couverture (réglage d'apparence), s'ils sont fournis et lisibles.
        $logo = (string) EventSettings::get($this->event, 'livret.cover_logo', '');
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
            $section->addText('« '.$theme.' »', ['italic' => true, 'size' => 12, 'color' => self::BLUE], ['alignment' => 'center', 'spaceBefore' => 60]);
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
        $coverLabel = trim((string) EventSettings::get($this->event, 'livret.cover_mention', '')) ?: 'LIVRE DES RÉSUMÉS';
        $section->addText($coverLabel, ['bold' => true, 'size' => 13, 'color' => self::BLUE], ['alignment' => 'center', 'spaceBefore' => 240]);

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
        $corr = $authors->firstWhere('corresponding', true) ?? $authors->first();

        return $corr ? $this->displayName($this->mapName(trim((string) ($corr['name'] ?? '')))) : '';
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
     * @param  \Illuminate\Database\Eloquent\Collection<int, Slot>  $slots
     */
    private function programmeDayTable(Section $section, string $day, Collection $slots): void
    {
        $label = $day !== '' ? ucfirst(Carbon::parse($day)->translatedFormat('l j F Y')) : 'À programmer';
        $section->addText($label, ['bold' => true, 'size' => 13, 'color' => self::BLUE], ['spaceBefore' => 160, 'spaceAfter' => 60, 'keepNext' => true]);

        $table = $section->addTable(['bordercolor' => 'DDDDDD', 'borderSize' => 4, 'cellMargin' => 60]);
        $table->addRow();
        foreach (['Horaire' => 2.6, 'Communication' => 9.4, 'Salle / Session' => 4] as $head => $cm) {
            $table->addCell((int) Converter::cmToTwip($cm), ['bgColor' => 'F1F5F9'])
                ->addText($head, ['bold' => true, 'size' => 10, 'color' => self::BLUE]);
        }
        foreach ($slots as $sl) {
            $h = $sl->starts_at?->format('H:i') ?? '-';
            if ($sl->ends_at) {
                $h .= '-'.$sl->ends_at->format('H:i');
            }
            $title = trim((string) ($sl->submission?->title ?: $sl->session_label));
            $authors = $this->slotAuthors($sl);
            $room = trim((string) ($sl->room ?: $sl->session?->room));
            $sessLabel = trim((string) ($sl->session?->label ?: $sl->session_label));

            $table->addRow();
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

        return collect($sl->submission->authors ?? [])->map(function ($a) {
            $name = $this->displayName($this->mapName(trim((string) ($a['name'] ?? ''))));
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
        $this->partTitle($section, 'Résumés des communications');

        if ($this->groups->isEmpty()) {
            $section->addText('Aucune communication acceptée pour le moment.', ['italic' => true, 'color' => self::MUTED]);

            return;
        }

        $sizeAbstract = (int) EventSettings::get($this->event, 'livret.size_abstract', 12);
        $sizeAuthors = (int) EventSettings::get($this->event, 'livret.size_authors', 10);
        $sizeBody = (int) EventSettings::get($this->event, 'livret.size_body', 11);
        $alignBody = EventSettings::get($this->event, 'livret.align_body', 'Justifié') === 'Gauche' ? 'left' : 'both';
        $number = (bool) EventSettings::get($this->event, 'livret.number_abstracts', false);
        $sizeAxis = (int) EventSettings::get($this->event, 'livret.size_axis', 16);
        $primary = self::hex((string) EventSettings::get($this->event, 'livret.color_primary', '#223ea4'), self::BLUE);
        $fontHead = (string) EventSettings::get($this->event, 'livret.font_head', 'Jost');
        $alignAxis = $this->align('livret.align_axis');

        $n = 0;
        foreach ($this->groups as $track => $submissions) {
            $section->addText(mb_strtoupper((string) $track, 'UTF-8'),
                ['bold' => true, 'size' => $sizeAxis, 'color' => $primary, 'name' => $fontHead],
                ['alignment' => $alignAxis, 'spaceBefore' => 320, 'spaceAfter' => 160, 'keepNext' => true]);

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
        $refLabel = trim((string) $s->ref_code);
        $indexRef = $refLabel !== '' ? $refLabel : '#'.$n;

        if ($fss) {
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
        $people = collect($s->authors ?? [])->map(fn ($a) => [
            'name' => $this->displayName($this->mapName((string) ($a['name'] ?? ''))),
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

            // Ligne des auteurs avec exposants (alignée selon `livret.align_authors`).
            $run = $section->addTextRun(['alignment' => $this->align('livret.align_authors'), 'spaceAfter' => $affs === [] ? 80 : 20]);
            $first = true;
            foreach ($people as $p) {
                if (! $first) {
                    $run->addText(', ', ['size' => $sizeAuthors, 'color' => self::MUTED]);
                }
                $first = false;
                $run->addText($p['name'], ['size' => $sizeAuthors, 'color' => self::MUTED]);
                if ($p['aff'] !== '') {
                    $run->addText((string) $affIndex[mb_strtolower($p['aff'], 'UTF-8')], ['size' => $sizeAuthors, 'color' => self::MUTED, 'superScript' => true]);
                }
                if ($p['corr']) {
                    $run->addText('*', ['size' => $sizeAuthors, 'color' => self::MUTED, 'superScript' => true]);
                }
            }

            // Affiliations numérotées.
            foreach ($affs as $i => $aff) {
                $affRun = $section->addTextRun(['spaceAfter' => 10]);
                $affRun->addText((string) ($i + 1).' ', ['size' => 9, 'color' => self::MUTED, 'superScript' => true]);
                $affRun->addText($aff, ['size' => 9, 'italic' => true, 'color' => self::MUTED]);
            }

            // Auteur correspondant : nom (e-mail : … ; Tél : …).
            $corr = $people->firstWhere('corr', true);
            if ($corr && ($contact = $this->contactLine($corr)) !== '') {
                $section->addText('*Auteur correspondant : '.$corr['name'].'   '.$contact,
                    ['size' => 9, 'color' => self::MUTED], ['spaceBefore' => 20, 'spaceAfter' => 60]);
            }
        }

        // Corps du résumé (préserve gras/italique de base). Le style FSS n'affiche PAS
        // de libellé « RÉSUMÉ : » (le texte suit directement, comme dans le livret).
        $paragraphs = $this->paragraphs((string) $s->abstract);
        if ($paragraphs !== []) {
            if (! $fss) {
                $section->addText('RÉSUMÉ :', ['bold' => true, 'size' => 10, 'color' => self::BLUE], ['spaceBefore' => 40, 'spaceAfter' => 20]);
            }
            foreach ($paragraphs as $i => $paragraphHtml) {
                $run = $section->addTextRun(['alignment' => $alignBody, 'spaceBefore' => ($fss && $i === 0) ? 40 : 0, 'spaceAfter' => 80]);
                $this->addInlineRuns($run, $paragraphHtml, $sizeBody);
            }
        }

        // Mots-clés (trait d'union dans le style FSS, conforme au livret).
        if ($keywords = SubmissionKeywords::join($s->keywords, (new SubmissionFormConfig($s->event))->keywordSeparator())) {
            $kwRun = $section->addTextRun(['spaceBefore' => 40, 'spaceAfter' => 40]);
            $kwRun->addText($fss ? 'Mots-clés : ' : 'Mots clés : ', ['bold' => true, 'size' => $sizeBody]);
            $kwRun->addText($keywords, ['italic' => true, 'size' => $sizeBody, 'color' => self::MUTED]);
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
        return match ((string) EventSettings::get($this->event, $key, $default)) {
            'Gauche' => 'left',
            'Droite' => 'right',
            'Justifié' => 'both',
            default => 'center',
        };
    }

    /** Titre de section (paragraphe gras, aligné selon `livret.align_titles`). */
    private function partTitle(Section $section, string $text): void
    {
        $primary = self::hex((string) EventSettings::get($this->event, 'livret.color_primary', '#223ea4'), self::BLUE);
        $fontHead = (string) EventSettings::get($this->event, 'livret.font_head', 'Jost');
        $section->addText($text, ['bold' => true, 'size' => 18, 'color' => $primary, 'name' => $fontHead],
            ['alignment' => $this->align('livret.align_titles'), 'spaceBefore' => 240, 'spaceAfter' => 160, 'keepNext' => true]);
    }

    /** Nom d'auteur pour l'affichage : en MAJUSCULES (convention du livret). */
    private function displayName(string $name): string
    {
        return mb_strtoupper(trim($name), 'UTF-8');
    }

    /** Normalise une couleur CSS (#rrggbb) en hex Word (RRGGBB), avec repli. */
    private static function hex(string $value, string $fallback): string
    {
        $v = ltrim(trim($value), '#');

        return preg_match('/^[0-9a-fA-F]{6}$/', $v) ? strtoupper($v) : $fallback;
    }
}
