<?php

namespace App\Documents;

use App\Models\DocumentTemplate;
use App\Models\Event;
use App\Support\BadgeCatalog;
use Barryvdh\DomPDF\Facade\Pdf as PdfFacade;
use Barryvdh\DomPDF\PDF;

/** Rend une planche de 48 badges au plus, pour HTTP ou pour un export différé. */
final class BadgePdfRenderer
{
    /** @param list<array{type: string, people: list<array<string, mixed>>}> $groups */
    public function pdf(Event $event, array $groups): PDF
    {
        $rendus = [];
        foreach ($groups as $group) {
            $rendus[] = $this->renderGroup($event, $group['type'], $group['people']);
        }

        // ⚠️⚠️ UNE FEUILLE N'A QU'UNE ORIENTATION. Un format peut gagner à la tourner
        // - l'A6 porte-badge (108 × 155 mm) n'entre qu'UNE fois sur une A4 portrait,
        // mais DEUX sur une A4 paysage. Un lot « toutes catégories » peut donc mêler
        // des orientations : on impose alors le PORTRAIT et on RECALCULE les plans
        // concernés, sinon leurs positions viseraient une feuille qui n'est pas
        // celle du PDF et les badges sortiraient hors page.
        $orientations = array_unique(array_map(fn (array $r) => (string) $r['plan']['orientation'], $rendus));
        $orientation = count($orientations) === 1 ? (string) reset($orientations) : 'portrait';

        $sheets = [];
        foreach ($rendus as $rendered) {
            $plan = $rendered['plan'];
            if ((string) $plan['orientation'] !== $orientation) {
                $plan = BadgeSheet::plan((int) $plan['cellW'], (int) $plan['cellH'], $orientation);
            }
            foreach (array_chunk($rendered['badges'], max(1, (int) $plan['perPage'])) as $page) {
                $sheets[] = [
                    'cols' => (int) $plan['cols'],
                    'cellW' => (int) $plan['cellW'],
                    'cellH' => (int) $plan['cellH'],
                    'gutterX' => (int) $plan['gutterX'],
                    'gutterY' => (int) $plan['gutterY'],
                    'originLeft' => (int) $plan['originLeft'],
                    'originTop' => (int) $plan['originTop'],
                    'pageW' => (int) $plan['pageW'],
                    'pageH' => (int) $plan['pageH'],
                    'badges' => $page,
                ];
            }
        }

        return BrandedPdf::stamp(
            PdfFacade::loadView('pdf.badges-sheet', ['sheets' => $sheets])->setPaper('a4', $orientation),
            'Badges - '.$event->title,
        );
    }

    /** @param list<array<string, mixed>> $people
     * @return array{plan: array<string, mixed>, badges: list<string>}
     */
    private function renderGroup(Event $event, string $type, array $people): array
    {
        $template = DocumentTemplate::ensureFor((int) $event->id, 'badge', $type, BadgeCatalog::TYPES[$type]['title']);
        $layout = LayoutSanitizer::sanitize((array) $template->layout, 'badge', $type);
        $plan = BadgeSheet::plan((int) $layout['width'], (int) $layout['height']);

        $badges = array_map(function (array $person) use ($event, $type, $layout, $plan): string {
            $recipientLayout = BadgeLayout::forRecipient($layout, $type, $person['photo'], $person['photo_mode'] !== 'without');

            return LayoutRenderer::render(
                BadgeSheet::scaleLayout($recipientLayout, $plan['scale'], $plan['cellW'], $plan['cellH']),
                $this->badgeVars($event, $type, $person),
                pdf: true,
                badge: true,
            );
        }, $people);

        return ['plan' => $plan, 'badges' => $badges];
    }

    /** @param array<string, mixed> $person
     * @return array<string, string>
     */
    private function badgeVars(Event $event, string $type, array $person): array
    {
        return [
            // ⚠️ Larastan type un BelongsTo comme NON-NUL : `?->` y déclenche
            // `nullsafe.neverNull`, alors que la série est bien nullable au runtime.
            'org' => (string) data_get($event, 'series.name', ''),
            'event' => (string) ($event->title ?? ''),
            'year' => (string) ($event->year ?: ''),
            'theme' => (string) ($event->theme ?? ''),
            'dates' => $event->dateRangeLabel() ?? '',
            'location' => (string) ($event->location ?? ''),
            'name' => (string) $person['name'],
            'role' => BadgeCatalog::role($type),
            'institution' => (string) $person['institution'],
            'ref' => (string) $person['ref'],
            'verify_url' => url('/verifier?ref='.urlencode((string) $person['ref']).'&ed='.$event->id),
            'recipient.photo' => (string) $person['photo'],
        ];
    }
}
