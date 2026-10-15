<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('document_templates')
            ->where('kind', 'badge')
            ->orderBy('id')
            ->chunkById(100, function ($templates): void {
                foreach ($templates as $template) {
                    $layout = json_decode((string) $template->layout, true);
                    if (! is_array($layout) || ($layout['width'] ?? null) !== 397
                        || ($layout['height'] ?? null) !== 560
                        || ($layout['bg_image'] ?? null) !== '/img/badges/badge-default-bg.png') {
                        continue;
                    }

                    $elements = $layout['elements'] ?? null;
                    if (! is_array($elements)) {
                        continue;
                    }

                    $locationIcon = null;
                    $calendarIcon = null;
                    $locationText = null;
                    $dateText = null;
                    foreach ($elements as $index => $element) {
                        if (($element['src'] ?? null) === '/img/badges/icon-location.png') {
                            $locationIcon = $index;
                        } elseif (($element['src'] ?? null) === '/img/badges/icon-calendar.png') {
                            $calendarIcon = $index;
                        } elseif (($element['text'] ?? null) === '[edition.location]') {
                            $locationText = $index;
                        } elseif (($element['text'] ?? null) === '[edition.dates]') {
                            $dateText = $index;
                        }
                    }

                    if ($locationIcon === null || $calendarIcon === null || $locationText === null || $dateText === null
                        || ! $this->matches($elements[$locationIcon], 68, 432, 28, 30)
                        || ! $this->matches($elements[$calendarIcon], 69, 470, 25, 25)
                        || ! $this->matches($elements[$locationText], 98, 434)
                        || ! $this->matches($elements[$dateText], 98, 472)) {
                        continue;
                    }

                    $state = json_decode((string) $template->editor_state, true);
                    $statePositions = [];
                    if (is_array($state) && is_array($state['els'] ?? null)) {
                        foreach ($state['els'] as $index => $item) {
                            $html = (string) ($item['html'] ?? '');
                            $token = trim(strip_tags($html));
                            if (str_contains($html, '/img/badges/icon-location.png')) {
                                $statePositions['locationIcon'] = $index;
                            } elseif (str_contains($html, '/img/badges/icon-calendar.png')) {
                                $statePositions['calendarIcon'] = $index;
                            } elseif ($token === '[edition.location]') {
                                $statePositions['locationText'] = $index;
                            } elseif ($token === '[edition.dates]') {
                                $statePositions['dateText'] = $index;
                            }
                        }
                        if (count($statePositions) !== 4
                            || ! $this->matchesStyle($state['els'][$statePositions['locationIcon']], 68, 432, 28, 30)
                            || ! $this->matchesStyle($state['els'][$statePositions['calendarIcon']], 69, 470, 25, 25)
                            || ! $this->matchesStyle($state['els'][$statePositions['locationText']], 98, 434)
                            || ! $this->matchesStyle($state['els'][$statePositions['dateText']], 98, 472)) {
                            continue;
                        }
                    }

                    foreach ([
                        $locationIcon => ['x' => 70, 'y' => 434, 'w' => 24, 'h' => 24],
                        $calendarIcon => ['x' => 70, 'y' => 472, 'w' => 24, 'h' => 24],
                        $locationText => ['y' => 438],
                        $dateText => ['y' => 476],
                    ] as $index => $geometry) {
                        $elements[$index] = array_replace($elements[$index], $geometry);
                    }

                    $layout['elements'] = $elements;
                    $values = ['layout' => json_encode($layout, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)];
                    if ($statePositions !== []) {
                        foreach ([
                            'locationIcon' => ['left' => '70px', 'top' => '434px', 'width' => '24px', 'height' => '24px'],
                            'calendarIcon' => ['left' => '70px', 'top' => '472px', 'width' => '24px', 'height' => '24px'],
                            'locationText' => ['top' => '438px'],
                            'dateText' => ['top' => '476px'],
                        ] as $key => $style) {
                            $index = $statePositions[$key];
                            $state['els'][$index]['style'] = array_replace($state['els'][$index]['style'], $style);
                        }
                        $values['editor_state'] = json_encode($state, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                    }
                    DB::table('document_templates')->where('id', $template->id)
                        ->update($values);
                }
            });
    }

    private function matches(array $element, int $x, int $y, ?int $w = null, ?int $h = null): bool
    {
        return ($element['x'] ?? null) === $x
            && ($element['y'] ?? null) === $y
            && ($w === null || ($element['w'] ?? null) === $w)
            && ($h === null || ($element['h'] ?? null) === $h);
    }

    private function matchesStyle(array $item, int $x, int $y, ?int $w = null, ?int $h = null): bool
    {
        $style = $item['style'] ?? [];

        return ($style['left'] ?? null) === $x.'px'
            && ($style['top'] ?? null) === $y.'px'
            && ($w === null || ($style['width'] ?? null) === $w.'px')
            && ($h === null || ($style['height'] ?? null) === $h.'px');
    }

    public function down(): void
    {
        // Les positions peuvent avoir été personnalisées après la migration.
    }
};
