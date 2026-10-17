<?php

namespace App\Http\Controllers\Admin;

use App\Documents\BadgeLayout;
use App\Documents\LayoutRenderer;
use App\Documents\LayoutSanitizer;
use App\Documents\LivretAppearance;
use App\Documents\LivretDocx;
use App\Documents\LivretTemplate;
use App\Http\Controllers\Controller;
use App\Models\DocumentTemplate;
use App\Models\Event;
use App\Models\Submission;
use App\Support\BadgeCatalog;
use App\Support\EventSettings;
use App\Support\SessionDocLayout;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;

/**
 * Documents imprimables d'un événement : livret des résumés et planche de badges.
 * (Imprimer / Enregistrer en PDF depuis le navigateur.)
 */
class DocumentController extends Controller
{
    /** Livret des résumés : communications acceptées, groupées par axe. */
    public function livret(Event $event, Request $request): View|Response
    {
        // ⚠️ Source unique de « ce que le livre contient » : le programme ci-dessous
        // s'y aligne aussi, sans quoi il annoncerait des communications absentes
        // de la section Résumés (cf. Submission::scopeForBooklet).
        $groups = $event->submissions()
            ->acceptedCanonical()
            ->orderBy('track')
            ->get()
            ->groupBy(fn ($s) => $s->track ?: 'Autres');

        if ($request->query('format') === 'docx') {
            $base = 'livret-'.($event->slug ?: 'edition-'.$event->id).'.docx';
            // Modèle .docx personnalisé sélectionné ? On remplit ses balises ; sinon
            // on retombe sur le générateur intégré paramétrable.
            if ($template = $this->selectedLivretTemplate($event)) {
                return (new LivretTemplate($event, $groups, (string) $template['path'], $request->boolean('harmonize')))
                    ->download($base);
            }

            return (new LivretDocx($event, $groups, $request->boolean('harmonize'), $this->livretStyle($event)))->download($base);
        }

        if ($request->query('format') === 'pdf') {
            $pdf = Pdf::loadView('documents.livret-pdf', $this->livretViewData($event, $groups))
                ->setPaper('a4');
            $file = 'livret-'.($event->slug ?: 'edition-'.$event->id).'.pdf';

            // `inline` → aperçu dans le navigateur (vrai rendu final) ; sinon téléchargement.
            return $request->boolean('inline') ? $pdf->stream($file) : $pdf->download($file);
        }

        return view('documents.livret', [
            'event' => $event,
            'groups' => $groups,
        ]);
    }

    /**
     * Modèle .docx personnalisé sélectionné pour cet événement, ou null (générateur
     * intégré). Un modèle n'est retenu que si son fichier existe réellement.
     *
     * @return array{name?: string, path?: string}|null
     */
    private function selectedLivretTemplate(Event $event): ?array
    {
        $selected = (string) EventSettings::get($event, 'livret.template', 'default');
        // 'default'/'base'/'fss' = styles intégrés (générateur LivretDocx), pas un
        // modèle .docx importé.
        if (in_array($selected, ['', 'default', 'base', ...array_keys(LivretDocx::STYLES)], true)) {
            return null;
        }

        $templates = EventSettings::get($event, 'livret.templates');
        foreach (is_array($templates) ? $templates : [] as $tpl) {
            if (($tpl['name'] ?? null) === $selected
                && ! empty($tpl['path'])
                && Storage::disk('public')->exists($tpl['path'])) {
                return $tpl;
            }
        }

        return null;
    }

    /** Style intégré actif pour le .docx : 'fss' si sélectionné, sinon 'default'. */
    private function livretStyle(Event $event): string
    {
        $selected = (string) EventSettings::get($event, 'livret.template', 'default');

        return array_key_exists($selected, LivretDocx::STYLES) ? $selected : 'default';
    }

    /**
     * Données partagées du livret (page de garde, avant-propos, comités, programme,
     * index des auteurs, options) pour les rendus PDF (dompdf) et HTML.
     *
     * @param  Collection<int|string, \Illuminate\Database\Eloquent\Collection<int, Submission>>  $groups
     * @return array<string, mixed>
     */
    private function livretViewData(Event $event, Collection $groups): array
    {
        $g = fn (string $key, mixed $default = null): mixed => EventSettings::get($event, $key, $default);
        $a = fn (string $key): mixed => LivretAppearance::get($event, $key);
        $font = fn (string $value): string => match ($value) {
            'Times New Roman' => "'Times New Roman', Times, serif",
            'Jost' => "'Jost', sans-serif",
            'Playfair Display' => "'Playfair Display', serif",
            'Georgia' => 'Georgia, serif',
            'Arial' => 'Arial, sans-serif',
            'Garamond' => 'Garamond, serif',
            default => 'Times, serif',
        };
        $size = fn (string $key, int $min, int $max): int => min($max, max($min, (int) $a($key)));
        $color = fn (string $key, string $default): string => preg_match('/^#[0-9a-fA-F]{6}$/', (string) $a($key))
            ? (string) $a($key) : $default;
        // Alignement (réglage FR) -> valeur CSS text-align.
        $css = fn (mixed $v): string => match ((string) $v) {
            'Gauche' => 'left', 'Droite' => 'right', 'Justifié' => 'justify', default => 'center',
        };

        // ⚠️ Le style intégré sélectionné pilote AUSSI l'aperçu PDF pour « general »
        // (comité d'organisation en tableau + « Communications en ateliers »), afin
        // que l'aperçu reflète fidèlement le .docx. Les autres styles gardent la
        // mise en page par défaut dans le PDF. Résolu EN TÊTE : la casse des auteurs
        // de l'index en dépend (cf. LivretDocx::authorCase).
        $general = $this->livretStyle($event) === 'general';

        // Index des auteurs : nom => références des résumés (renvoi), même règle que le .docx.
        $authorIndex = [];
        $n = 0;
        foreach ($groups as $submissions) {
            foreach ($submissions as $s) {
                $n++;
                $ref = trim((string) $s->ref_code) ?: '#'.$n;
                // ⚠️ **La variable de boucle ne s'appelle PAS `$a`** : c'est le nom
                // de la fermeture qui lit l'apparence du livre, déclarée plus haut
                // dans la MÊME portée. Un `foreach` l'écrase - et ce n'est pas une
                // ombre passagère, elle reste écrasée après la boucle : plus loin,
                // `$a('cover_logo')` appelait alors un tableau d'auteur, « Array
                // callback must have exactly two elements », et l'aperçu du livret
                // comme son PDF tombaient en 500 dès qu'une communication portait
                // un auteur - c'est-à-dire toujours.
                foreach (collect($s->authors ?? []) as $auteur) {
                    // ⚠️ Casse SELON LE STYLE, via la source unique partagée avec le
                    // .docx (majuscules pour default/fss, « NOM Prénom » tel quel pour
                    // general) - ne pas remettre un mb_strtoupper inconditionnel ici.
                    $name = LivretDocx::authorCase((string) ($auteur['name'] ?? ''), $general);
                    if ($name !== '') {
                        $authorIndex[$name][] = $ref;
                    }
                }
            }
        }
        ksort($authorIndex, SORT_NATURAL | SORT_FLAG_CASE);
        $authorIndex = array_map(fn ($refs) => implode(', ', array_values(array_unique($refs))), $authorIndex);

        // Programme : communications placées (créneaux) regroupées par jour.
        // ⚠️ **Un créneau dont la communication n'est pas au livre n'y figure pas
        // non plus** : planifiée puis refusée, elle gardait son créneau et le
        // programme imprimé annonçait son titre et ses auteurs, introuvables dans
        // la section Résumés. On l'écarte, on ne la replie pas sur l'intitulé de
        // sa séance - la ligne n'existait que pour elle, et les autres créneaux
        // de la même séance la représentent déjà.
        $programme = $event->slots()->with(['submission', 'session'])
            ->whereNotNull('starts_at')->orderBy('starts_at')->get()
            ->filter(fn ($sl) => $sl->submission
                ? $sl->submission->isAcceptedCanonical()
                : trim((string) $sl->session_label) !== '')
            ->groupBy(fn ($sl) => $sl->starts_at?->format('Y-m-d') ?? '')
            ->sortKeys();

        // Ordre + libellés des comités : SOURCE UNIQUE partagée avec le .docx
        // (« general » suit le document de référence, sinon l'ordre UK/UP).
        $committeeRoles = LivretDocx::committeeOrder($general);

        // Programme : « Communications en ateliers » (par axe) pour le style general,
        // sinon « Programme » par jour. Le regroupement par axe/jour vient de la
        // SOURCE UNIQUE `LivretDocx::ateliersGroups()` (même structure que le .docx) ;
        // ici on ne compose que l'intitulé « ATELIER n : … ».
        if ($general) {
            $programmeTitle = 'Communications en ateliers';
            $programmeGroups = LivretDocx::ateliersGroups($event)
                ->map(fn (array $g, int $i) => [
                    'heading' => 'ATELIER '.($i + 1).' : '.mb_strtoupper($g['axis'], 'UTF-8'),
                    'days' => $g['days'],
                ])->values();
        } else {
            $programmeTitle = 'Programme';
            $programmeGroups = $programme->isNotEmpty()
                ? collect([['heading' => null, 'days' => $programme]])
                : collect();
        }

        $synthesis = $event->settings['program_synthesis'] ?? null;
        $logo = (string) $a('cover_logo');
        $logoData = null;
        if ($logo !== '' && Storage::disk('public')->exists($logo)) {
            $mime = Storage::disk('public')->mimeType($logo);
            if (in_array($mime, ['image/png', 'image/jpeg', 'image/webp', 'image/svg+xml'], true)) {
                $logoData = 'data:'.$mime.';base64,'.base64_encode(Storage::disk('public')->get($logo));
            }
        }

        return [
            'event' => $event,
            'groups' => $groups,
            'authorIndex' => $authorIndex,
            // Programme par jour (défaut) : conservé pour la garde « hors livre »
            // qui vérifie que le programme n'annonce pas de communication refusée.
            'programme' => $programme,
            'general' => $general,
            // Palette du style « general », source unique (cf. LivretDocx) partagée
            // avec le .docx pour que les couleurs ne divergent pas.
            'palette' => LivretDocx::generalCssPalette(),
            'commissionsTitle' => $general
                ? 'Les différentes commissions et programmes du colloque'
                : 'Différentes commissions et programmes du colloque',
            'programmeTitle' => $programmeTitle,
            'programmeGroups' => $programmeGroups,
            'programSynthesis' => is_array($synthesis) ? $synthesis : ['title' => '', 'days' => []],
            'committees' => $event->committeeMembers()->orderBy('ordre')->get()->groupBy('role'),
            'committeeRoles' => $committeeRoles,
            'dayLabel' => fn (string $d) => $d !== '' ? ucfirst(Carbon::parse($d)->translatedFormat('l j F Y')) : 'À programmer',
            'opts' => [
                'foreword_title' => trim((string) $g('livret.foreword_title', 'Avant-propos')) ?: 'Avant-propos',
                'foreword_text' => trim((string) $g('livret.foreword_text', '')),
                'signature' => trim((string) $g('livret.signature', '')),
                // Sections de tête génériques (rendues seulement si remplies).
                'patronage' => trim((string) $g('livret.patronage', '')),
                'ack_title' => trim((string) $g('livret.ack_title', 'Remerciements')) ?: 'Remerciements',
                'acknowledgements' => trim((string) $g('livret.acknowledgements', '')),
                'welcome_title' => trim((string) $g('livret.welcome_title', 'Mot de bienvenue')) ?: 'Mot de bienvenue',
                'welcome_text' => trim((string) $g('livret.welcome_text', '')),
                'welcome_signature' => trim((string) $g('livret.welcome_signature', '')),
                'president_title' => trim((string) $g('livret.president_title', 'Mot du président')) ?: 'Mot du président',
                'president_text' => trim((string) $g('livret.president_text', '')),
                'president_signature' => trim((string) $g('livret.president_signature', '')),
                'cover_mention' => trim((string) $a('cover_mention')) ?: 'LIVRE DES RÉSUMÉS',
                'show_theme' => (bool) $a('show_theme'),
                'show_dates' => (bool) $a('show_dates'),
                'number' => (bool) $a('number_abstracts'),
                'author_index' => (bool) $a('author_index'),
                'color_primary' => $color('color_primary', '#223ea4'),
                'color_secondary' => $color('color_secondary', '#009933'),
                'font_body' => $font((string) $a('font_body')),
                'font_head' => $font((string) $a('font_head')),
                'size_body' => $size('size_body', 8, 16),
                'size_cover' => $size('size_cover', 14, 48),
                'size_axis' => $size('size_axis', 12, 28),
                'size_abstract' => $size('size_abstract', 10, 18),
                'size_authors' => $size('size_authors', 8, 14),
                'line_height' => in_array((string) $a('line_height'), ['1.0', '1.15', '1.5', '2.0'], true)
                    ? (string) $a('line_height') : '1.15',
                'page_numbers' => (bool) $a('page_numbers'),
                'cover_logo_data' => $logoData,
                'align_body' => $a('align_body') === 'Gauche' ? 'left' : 'justify',
                'align_titles' => $css($a('align_titles')),
                'align_axis' => $css($a('align_axis')),
                'align_authors' => $css($a('align_authors')),
            ],
        ];
    }

    /** Fiches de session imprimables : chaque session avec ses communications. */
    public function sessionSheets(Event $event): View
    {
        $sessions = $event->sessions()
            ->with('atelier')
            ->orderBy('session_day')
            ->orderBy('ordre')
            ->get();

        $commsBySession = $event->slots()
            ->where('submission_id', '>', 0)
            ->with('submission')
            ->orderBy('starts_at')
            ->get()
            ->groupBy('session_id');

        return view('documents.session-sheets', [
            'event' => $event,
            'sessions' => $sessions,
            'commsBySession' => $commsBySession,
        ]);
    }

    /** PV / procès-verbaux imprimables : entête + communications + synthèse du rapport. */
    public function sessionPv(Event $event): View
    {
        $sessions = $event->sessions()
            ->with(['atelier', 'report'])
            // Présents RÉELLEMENT pointés par le modérateur / le rapporteur : le PV
            // doit préférer un comptage à l'affluence estimée à la main. En
            // `withCount` pour ne pas rouvrir une requête par séance.
            ->withCount(['attendances as present_count' => fn ($q) => $q->where('status', 'present')])
            ->orderBy('session_day')->orderBy('ordre')->get();

        $commsBySession = $event->slots()
            ->where('submission_id', '>', 0)
            ->with('submission')
            ->orderBy('starts_at')->get()
            ->groupBy('session_id');

        return view('documents.session-pv', [
            'event' => $event,
            'sessions' => $sessions,
            'commsBySession' => $commsBySession,
        ]);
    }

    /** Éditeur de mise en page (entêtes + colonnes) des documents de séance. */
    public function sessionLayout(Event $event): View
    {
        return view('admin.documents.session-layout', [
            'event' => $event,
            'columns' => SessionDocLayout::COLUMNS,
            'headerFields' => SessionDocLayout::HEADER_FIELDS,
            'pvSections' => SessionDocLayout::PV_SECTIONS,
            'sel' => [
                'sheet_columns' => array_column(SessionDocLayout::sheetColumns($event), 'key'),
                'sheet_header' => array_column(SessionDocLayout::sheetHeader($event), 'key'),
                'pv_columns' => array_column(SessionDocLayout::pvColumns($event), 'key'),
                'pv_header' => array_column(SessionDocLayout::pvHeader($event), 'key'),
                'pv_sections' => array_column(SessionDocLayout::pvSections($event), 'key'),
            ],
            'labels' => [
                'sheet_columns' => (array) EventSettings::get($event, 'session_sheets.columns_labels', []),
                'sheet_header' => (array) EventSettings::get($event, 'session_sheets.header_labels', []),
                'pv_columns' => (array) EventSettings::get($event, 'session_pv.columns_labels', []),
                'pv_header' => (array) EventSettings::get($event, 'session_pv.header_labels', []),
                'pv_sections' => (array) EventSettings::get($event, 'session_pv.sections_labels', []),
            ],
        ]);
    }

    /** Enregistre la mise en page (colonnes/entêtes/sections) dans les réglages d'événement. */
    public function saveSessionLayout(Request $request, Event $event): RedirectResponse
    {
        $values = array_merge(
            $this->layoutGroup($request, 'sheet_columns', 'session_sheets.columns', SessionDocLayout::COLUMNS),
            $this->layoutGroup($request, 'sheet_header', 'session_sheets.header', SessionDocLayout::HEADER_FIELDS),
            $this->layoutGroup($request, 'pv_columns', 'session_pv.columns', SessionDocLayout::COLUMNS),
            $this->layoutGroup($request, 'pv_header', 'session_pv.header', SessionDocLayout::HEADER_FIELDS),
            $this->layoutGroup($request, 'pv_sections', 'session_pv.sections', SessionDocLayout::PV_SECTIONS),
        );
        EventSettings::setMany($event, $values);

        return back()->with('ukc_status', 'Mise en page des documents de séance enregistrée.');
    }

    /**
     * Construit la sélection (clés cochées, dans l'ordre du catalogue) + libellés
     * personnalisés d'un groupe du formulaire de mise en page.
     *
     * @param  array<string, string>  $catalog
     * @return array<string, mixed>
     */
    private function layoutGroup(Request $request, string $field, string $key, array $catalog): array
    {
        $checked = array_map('strval', (array) $request->input($field, []));
        $selected = array_values(array_filter(array_keys($catalog), fn ($k) => in_array($k, $checked, true)));

        $labelsInput = (array) $request->input($field.'_label', []);
        $labels = [];
        foreach ($selected as $k) {
            $v = trim((string) ($labelsInput[$k] ?? ''));
            if ($v !== '' && $v !== $catalog[$k]) {
                $labels[$k] = $v;
            }
        }

        return [$key => $selected, $key.'_labels' => $labels];
    }

    /** Planche A4 du type de badge demandé, depuis ses destinataires réels. */
    public function badges(Request $request, Event $event): View
    {
        $type = (string) $request->query('type', 'default');
        if ($type === 'participant_photo') {
            $type = 'default'; // Ancien lien : un seul modèle participant désormais.
        }
        abort_unless(BadgeCatalog::valid($type), 404);
        $people = BadgeCatalog::recipients($event, $type);

        // Toujours un modèle enregistré (créé au besoin) : plus de planche de repli
        // que l'admin ne pouvait ni voir ni modifier.
        $template = DocumentTemplate::ensureFor((int) $event->id, 'badge', $type, BadgeCatalog::TYPES[$type]['title']);

        $layout = LayoutSanitizer::sanitize((array) $template->layout, 'badge', $type);
        // ⚠️ Plus de valeur de repli : `BadgeCatalog::recipients()` pose TOUJOURS
        // la photo (chaîne vide à défaut) et son mode (« with » à défaut). Un `??`
        // ici laisserait croire que la préférence peut manquer - et c'est en la
        // croyant absente qu'on finit par ne jamais la respecter.
        $badges = $people->map(fn (array $person) => LayoutRenderer::render(BadgeLayout::forRecipient($layout, $type, $person['photo'], $person['photo_mode'] !== 'without'), [
            'org' => $event->series?->name ?? '',
            'event' => $event->title ?? '',
            'year' => (string) ($event->year ?: ''),
            'theme' => (string) ($event->theme ?? ''),
            'dates' => $event->dateRangeLabel() ?? '',
            'location' => (string) ($event->location ?? ''),
            'name' => $person['name'],
            'role' => BadgeCatalog::role($type),
            'institution' => $person['institution'],
            'ref' => $person['ref'],
            'verify_url' => url('/verifier?ref='.urlencode($person['ref']).'&ed='.$event->id),
            // Photo de profil pour le modèle « Participant avec photo » (élément photo).
            'recipient.photo' => $person['photo'],
        ], badge: true));

        return view('documents.badges-template', [
            'event' => $event,
            'badges' => $badges,
            'badgeType' => BadgeCatalog::label($type),
            'width' => $layout['width'],
            'height' => $layout['height'],
        ]);
    }
}
