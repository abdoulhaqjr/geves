<?php

namespace App\Documents;

use App\AI\AiManager;
use App\Models\Edition;
use App\Models\Submission;
use App\Support\EditionSettings;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpWord\Element\Section;
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
 * par axe — chaque résumé avec sa référence, ses auteurs et affiliations
 * numérotées (exposants), l'auteur correspondant, le libellé « RÉSUMÉ : » et les
 * mots-clés. Rendu déterministe ; l'apparence est pilotée par les réglages
 * `livret.*` et le texte échappé (Word refuse un XML invalide).
 */
class LivretDocx
{
    // Charte Université de Kara (littéraux : Word ne lit pas les tokens CSS).
    private const BLUE = '223EA4';

    private const GOLD = 'FFD700';

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

    /**
     * @param  Collection<int|string, \Illuminate\Database\Eloquent\Collection<int, Submission>>  $groups  soumissions groupées par axe
     */
    public function __construct(
        private readonly Edition $edition,
        private readonly Collection $groups,
        private readonly bool $harmonize = false,
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

    /** Construit le document phpword complet (ordre du booklet uk-colloques). */
    public function build(): PhpWord
    {
        // PhpWord n'échappe PAS le texte par défaut : sans ceci, tout « & », « < » ou « > »
        // (ex. « Santé & environnement ») produit un document.xml malformé → .docx illisible.
        Settings::setOutputEscapingEnabled(true);

        $doc = new PhpWord;
        $doc->getSettings()->setUpdateFields(true);

        $fontHead = (string) EditionSettings::get($this->edition, 'livret.font_head', 'Jost');
        $primary = self::hex((string) EditionSettings::get($this->edition, 'livret.color_primary', '#223ea4'), self::BLUE);
        $sizeAxis = (int) EditionSettings::get($this->edition, 'livret.size_axis', 16);

        $doc->setDefaultFontName((string) EditionSettings::get($this->edition, 'livret.font_body', 'Times New Roman'));
        $doc->setDefaultFontSize((int) EditionSettings::get($this->edition, 'livret.size_body', 11));

        // Niveau 1 = sections (Avant-propos, Comités, Programme, Résumés) ; niveau 2 = axes.
        $doc->addTitleStyle(1, ['size' => 18, 'bold' => true, 'color' => $primary, 'name' => $fontHead], ['spaceBefore' => 240, 'spaceAfter' => 200]);
        $doc->addTitleStyle(2, ['size' => $sizeAxis, 'bold' => true, 'color' => $primary, 'name' => $fontHead], ['spaceBefore' => 320, 'spaceAfter' => 160]);

        $section = $doc->addSection([
            'marginTop' => Converter::cmToTwip(2.2),
            'marginBottom' => Converter::cmToTwip(2.2),
            'marginLeft' => Converter::cmToTwip(2.2),
            'marginRight' => Converter::cmToTwip(2.2),
        ]);

        // Harmonisation IA (noms + affiliations) : maps appliquées au rendu des résumés.
        if ($this->harmonize) {
            $this->buildAiMaps();
        }

        $this->addCover($section);
        $this->addForeword($section);
        $this->addCommittees($section);
        $this->addToc($section);
        $this->addProgramme($section);
        $this->addBody($section);

        return $doc;
    }

    /** Page de garde : logo, bandeau, colloque, titre, thème, dates, « LIVRE DES RÉSUMÉS ». */
    private function addCover(Section $section): void
    {
        $coverSize = (int) EditionSettings::get($this->edition, 'livret.size_cover', 24);
        $showTheme = (bool) EditionSettings::get($this->edition, 'livret.show_theme', true);
        $showDates = (bool) EditionSettings::get($this->edition, 'livret.show_dates', true);

        // En-tête : nom du colloque en majuscules.
        if ($name = trim((string) $this->edition->colloque?->name)) {
            $section->addText(mb_strtoupper($name, 'UTF-8'), ['bold' => true, 'size' => 12, 'color' => self::BLUE], ['alignment' => 'center', 'spaceAfter' => 80]);
        }

        // Logo(s) de couverture (réglage d'apparence), s'ils sont fournis et lisibles.
        $logo = (string) EditionSettings::get($this->edition, 'livret.cover_logo', '');
        if ($logo !== '' && Storage::disk('public')->exists($logo)) {
            try {
                $section->addImage(Storage::disk('public')->path($logo), ['width' => 140, 'alignment' => 'center']);
                $section->addTextBreak(1);
            } catch (\Throwable $e) {
                // Logo illisible : on poursuit sans image.
            }
        }

        // Bandeau tricolore (trois cellules colorées). Twips = entiers (1pt = 20tw, 1cm ≈ 567tw).
        $banner = $section->addTable();
        $banner->addRow((int) Converter::pointToTwip(6));
        foreach ([self::BLUE, self::GOLD, self::GREEN] as $color) {
            $banner->addCell((int) Converter::cmToTwip(4), ['bgColor' => $color])->addText('');
        }

        $section->addTextBreak(2);

        if ($title = trim((string) $this->edition->title)) {
            $section->addText($title, ['bold' => true, 'size' => $coverSize, 'color' => '111827'], ['alignment' => 'center', 'spaceBefore' => 160, 'spaceAfter' => 40]);
        }

        if ($label = trim((string) $this->edition->edition_label)) {
            $section->addText($label, ['size' => 13, 'color' => self::MUTED], ['alignment' => 'center', 'spaceAfter' => 20]);
        }

        // Thème de l'édition (réglage d'apparence).
        if ($showTheme && ($theme = trim((string) $this->edition->theme)) !== '') {
            $section->addText('« '.$theme.' »', ['italic' => true, 'size' => 12, 'color' => self::BLUE], ['alignment' => 'center', 'spaceBefore' => 60]);
        }

        // Dates + lieu (réglage d'apparence).
        if ($showDates && $this->edition->date_start) {
            $dates = $this->edition->date_start->format('d/m/Y');
            if ($this->edition->date_end) {
                $dates .= ' – '.$this->edition->date_end->format('d/m/Y');
            }
            if ($location = trim((string) $this->edition->location)) {
                $dates .= '   ·   '.$location;
            }
            $section->addText($dates, ['size' => 11, 'color' => self::MUTED], ['alignment' => 'center', 'spaceBefore' => 80]);
        }

        // Libellé de couverture (réglage `cover_mention`, défaut « LIVRE DES RÉSUMÉS »).
        $coverLabel = trim((string) EditionSettings::get($this->edition, 'livret.cover_mention', '')) ?: 'LIVRE DES RÉSUMÉS';
        $section->addText($coverLabel, ['bold' => true, 'size' => 13, 'color' => self::BLUE], ['alignment' => 'center', 'spaceBefore' => 240]);

        $section->addPageBreak();
    }

    /** Avant-propos signé (réglages `livret.*`). */
    private function addForeword(Section $section): void
    {
        $text = trim((string) EditionSettings::get($this->edition, 'livret.foreword_text', ''));
        if ($text === '') {
            return;
        }

        $title = trim((string) EditionSettings::get($this->edition, 'livret.foreword_title', 'Avant-propos')) ?: 'Avant-propos';
        $section->addTitle($title, 1);

        foreach (preg_split('/\R{2,}/', $text) ?: [$text] as $paragraph) {
            $paragraph = trim((string) $paragraph);
            if ($paragraph !== '') {
                $section->addText($paragraph, ['size' => 11], ['alignment' => 'both', 'spaceAfter' => 120]);
            }
        }

        if ($signature = trim((string) EditionSettings::get($this->edition, 'livret.signature', ''))) {
            $section->addText($signature, ['italic' => true, 'size' => 11, 'color' => self::MUTED], ['alignment' => 'right', 'spaceBefore' => 160]);
        }

        $section->addPageBreak();
    }

    /** Comités (organisation / scientifique / supervision) : « Nom — Titre — Institution ». */
    private function addCommittees(Section $section): void
    {
        $members = $this->edition->committeeMembers()->orderBy('ordre')->get();
        if ($members->isEmpty()) {
            return;
        }

        $byRole = $members->groupBy('role');
        $printed = false;
        foreach (self::COMMITTEE_ROLES as $role => $label) {
            $list = $byRole->get($role);
            if (! $list || $list->isEmpty()) {
                continue;
            }
            $section->addTitle($label, 1);
            $printed = true;
            foreach ($list as $m) {
                $bits = array_filter([
                    trim((string) $m->person_name),
                    trim((string) $m->person_title),
                    trim((string) $m->institution),
                ]);
                $section->addText(implode(' — ', $bits), ['size' => 11], ['spaceAfter' => 40]);
            }
        }

        if ($printed) {
            $section->addPageBreak();
        }
    }

    /** Sommaire : champ TOC Word (mis à jour à l'ouverture). */
    private function addToc(Section $section): void
    {
        // Titre simple (non indexé) : évite que « Sommaire » figure dans son propre sommaire.
        $section->addText('Sommaire', ['bold' => true, 'size' => 18, 'color' => self::hex((string) EditionSettings::get($this->edition, 'livret.color_primary', '#223ea4'), self::BLUE), 'name' => (string) EditionSettings::get($this->edition, 'livret.font_head', 'Jost')], ['spaceBefore' => 240, 'spaceAfter' => 200]);
        $section->addTOC(['size' => 11, 'name' => (string) EditionSettings::get($this->edition, 'livret.font_head', 'Jost')], ['spaceAfter' => 60], 1, 2);
        $section->addPageBreak();
    }

    /** Programme : sessions datées regroupées par jour (tableau Horaire / Session / Salle). */
    private function addProgramme(Section $section): void
    {
        $sessions = $this->edition->sessions()->get()
            ->filter(fn ($s) => $s->starts_at || $s->session_day)
            ->sortBy(fn ($s) => optional($s->starts_at)?->timestamp ?? 0)
            ->values();
        if ($sessions->isEmpty()) {
            return;
        }

        $section->addTitle('Programme', 1);

        $byDay = $sessions->groupBy(fn ($s) => optional($s->session_day ?? $s->starts_at)?->format('Y-m-d') ?? '');
        foreach ($byDay->sortKeys() as $day => $daySessions) {
            $dayLabel = $day !== '' ? ucfirst(Carbon::parse($day)->translatedFormat('l j F Y')) : 'À programmer';
            $section->addText($dayLabel, ['bold' => true, 'size' => 13, 'color' => self::BLUE], ['spaceBefore' => 160, 'spaceAfter' => 60]);

            $table = $section->addTable(['bordercolor' => 'DDDDDD', 'borderSize' => 4, 'cellMargin' => 60]);
            $table->addRow();
            foreach (['Horaire', 'Session', 'Salle'] as $i => $head) {
                $table->addCell((int) Converter::cmToTwip([3, 9, 4][$i]), ['bgColor' => 'F1F5F9'])
                    ->addText($head, ['bold' => true, 'size' => 10, 'color' => self::BLUE]);
            }
            foreach ($daySessions as $s) {
                $h = $s->starts_at?->format('H:i') ?? '—';
                if ($s->ends_at) {
                    $h .= '–'.$s->ends_at->format('H:i');
                }
                $table->addRow();
                $table->addCell((int) Converter::cmToTwip(3))->addText($h, ['size' => 10]);
                $table->addCell((int) Converter::cmToTwip(9))->addText((string) $s->label, ['size' => 10]);
                $table->addCell((int) Converter::cmToTwip(4))->addText((string) $s->room, ['size' => 10]);
            }
        }

        $section->addPageBreak();
    }

    /** Résumés des communications, regroupés par axe (titres en MAJUSCULES). */
    private function addBody(Section $section): void
    {
        $section->addTitle('Résumés des communications', 1);

        if ($this->groups->isEmpty()) {
            $section->addText('Aucune communication acceptée pour le moment.', ['italic' => true, 'color' => self::MUTED]);

            return;
        }

        $sizeAbstract = (int) EditionSettings::get($this->edition, 'livret.size_abstract', 12);
        $sizeAuthors = (int) EditionSettings::get($this->edition, 'livret.size_authors', 10);
        $sizeBody = (int) EditionSettings::get($this->edition, 'livret.size_body', 11);
        $alignBody = EditionSettings::get($this->edition, 'livret.align_body', 'Justifié') === 'Gauche' ? 'left' : 'both';
        $number = (bool) EditionSettings::get($this->edition, 'livret.number_abstracts', false);

        $n = 0;
        foreach ($this->groups as $track => $submissions) {
            $section->addTitle(mb_strtoupper((string) $track, 'UTF-8'), 2);

            foreach ($submissions as $s) {
                $n++;
                $this->addAbstract($section, $s, $n, $number, $sizeAbstract, $sizeAuthors, $sizeBody, $alignBody);
            }
        }
    }

    /** Rendu d'un résumé : référence, titre, auteurs+affiliations, correspondant, corps, mots-clés. */
    private function addAbstract(Section $section, Submission $s, int $n, bool $number, int $sizeAbstract, int $sizeAuthors, int $sizeBody, string $alignBody): void
    {
        // Référence (ex. UKC-S-0001), centrée.
        if ($ref = trim((string) $s->ref_code)) {
            $section->addText($ref, ['bold' => true, 'size' => 10, 'color' => self::MUTED], ['alignment' => 'center', 'spaceBefore' => 200]);
        }

        // Titre (numéroté si l'option est active).
        $title = $number ? $n.'. '.$s->title : (string) $s->title;
        $section->addText($title, ['bold' => true, 'size' => $sizeAbstract, 'color' => self::BLUE], ['spaceAfter' => 40]);

        // Auteurs + affiliations numérotées (exposants) + auteur correspondant (*).
        $people = collect($s->authors ?? [])->map(fn ($a) => [
            'name' => $this->mapName((string) ($a['name'] ?? '')),
            'aff' => $this->mapAff(trim((string) ($a['affiliation'] ?? ''))),
            'email' => trim((string) ($a['email'] ?? '')),
            'corr' => (bool) ($a['corresponding'] ?? false),
        ])->filter(fn ($p) => $p['name'] !== '')->values();

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

            // Ligne des auteurs avec exposants.
            $run = $section->addTextRun(['spaceAfter' => $affs === [] ? 80 : 20]);
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

            // Auteur correspondant : nom (e-mail : …).
            $corr = $people->firstWhere('corr', true);
            if ($corr && $corr['email'] !== '') {
                $section->addText('*Auteur correspondant : '.$corr['name'].' (e-mail : '.$corr['email'].')',
                    ['size' => 9, 'color' => self::MUTED], ['spaceBefore' => 20, 'spaceAfter' => 60]);
            }
        }

        // Corps du résumé (préserve gras/italique de base) — libellé masqué si vide.
        $paragraphs = $this->paragraphs((string) $s->abstract);
        if ($paragraphs !== []) {
            $section->addText('RÉSUMÉ :', ['bold' => true, 'size' => 10, 'color' => self::BLUE], ['spaceBefore' => 40, 'spaceAfter' => 20]);
            foreach ($paragraphs as $paragraphHtml) {
                $run = $section->addTextRun(['alignment' => $alignBody, 'spaceAfter' => 80]);
                $this->addInlineRuns($run, $paragraphHtml, $sizeBody);
            }
        }

        // Mots-clés.
        if ($keywords = trim((string) $s->keywords)) {
            $kwRun = $section->addTextRun(['spaceBefore' => 40, 'spaceAfter' => 40]);
            $kwRun->addText('Mots clés : ', ['bold' => true, 'size' => $sizeBody]);
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

    /** Normalise une couleur CSS (#rrggbb) en hex Word (RRGGBB), avec repli. */
    private static function hex(string $value, string $fallback): string
    {
        $v = ltrim(trim($value), '#');

        return preg_match('/^[0-9a-fA-F]{6}$/', $v) ? strtoupper($v) : $fallback;
    }
}
