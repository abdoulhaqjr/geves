<?php

namespace App\Documents;

use App\Models\Event;
use App\Models\Submission;
use App\Support\EventSettings;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;

/**
 * Ce que le livre des résumés CONTIENT, assemblé une seule fois.
 *
 * ⚠️⚠️ **DEUX LECTEURS, UN SEUL ASSEMBLAGE.** Le gestionnaire le relit depuis le
 * back-office, le participant le télécharge depuis son espace - et c'est le
 * **même** livre : même couverture, mêmes comités, même programme, mêmes résumés.
 * Ces données ont vécu dans le contrôleur de l'administration, hors d'atteinte de
 * l'espace participant, qui rendait donc une version appauvrie - sans comités ni
 * programme - sans que rien ne le signale. Deux assemblages se décalent toujours ;
 * celui-là l'avait déjà fait.
 *
 * ⚠️ Le `.docx`, lui, reste au back-office : c'est un **gabarit à retoucher**,
 * pas le document qu'on remet.
 */
final class LivretView
{
    /** Style intégré actif pour le .docx : 'fss' si sélectionné, sinon 'default'. */
    public static function style(Event $event): string
    {
        $selected = (string) EventSettings::get($event, 'livret.template', 'default');

        return array_key_exists($selected, LivretDocx::STYLES) ? $selected : 'default';
    }

    /**
     * Données partagées du livret (page de garde, avant-propos, comités, programme,
     * index des auteurs, options) pour les rendus PDF (dompdf) et HTML.
     *
     * ⚠️ Le modèle est NOMMÉ EN ENTIER : hors de `App\Models`, un `Submission` nu
     * se résoudrait dans ce namespace-ci, où il n'existe pas - l'analyse le dit,
     * mais le code, lui, tournerait sans rien signaler.
     *
     * @param  Collection<int|string, \Illuminate\Database\Eloquent\Collection<int, Submission>>  $groups
     * @return array<string, mixed>
     */
    public static function data(Event $event, Collection $groups): array
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
        $general = self::style($event) === 'general';

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
}
