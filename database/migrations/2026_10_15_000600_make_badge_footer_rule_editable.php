<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('document_templates')->where('kind', 'badge')->orderBy('id')
            ->chunkById(100, function ($templates): void {
                foreach ($templates as $template) {
                    $layout = json_decode((string) $template->layout, true);
                    if (! is_array($layout) || ($layout['width'] ?? null) !== 397
                        || ($layout['height'] ?? null) !== 560
                        || ($layout['bg_image'] ?? null) !== '/img/badges/badge-default-bg.png'
                        || ! empty($layout['erase_default_rule'])
                        || ! is_array($layout['elements'] ?? null)) {
                        continue;
                    }

                    $icons = [];
                    $texts = [];
                    foreach ($layout['elements'] as $index => $element) {
                        if (($element['type'] ?? null) === 'rect'
                            && ($element['x'] ?? null) === 108 && ($element['y'] ?? null) === 417) {
                            continue 2;
                        }
                        $src = $element['src'] ?? null;
                        $text = $element['text'] ?? null;
                        if ($src === '/img/badges/icon-location.png' || $src === '/img/badges/icon-calendar.png') {
                            $icons[$src] = $index;
                        }
                        if ($text === '[edition.location]' || $text === '[edition.dates]') {
                            $texts[$text] = $index;
                        }
                    }
                    $location = $icons['/img/badges/icon-location.png'] ?? null;
                    $calendar = $icons['/img/badges/icon-calendar.png'] ?? null;
                    $locationText = $texts['[edition.location]'] ?? null;
                    $dateText = $texts['[edition.dates]'] ?? null;
                    if ($location === null || $calendar === null || $locationText === null || $dateText === null
                        || ! $this->at($layout['elements'][$location], 70, 434)
                        || ! $this->at($layout['elements'][$calendar], 70, 472)
                        || ! $this->at($layout['elements'][$locationText], 98, 438)
                        || ! $this->at($layout['elements'][$dateText], 98, 476)) {
                        continue;
                    }

                    $state = json_decode((string) $template->editor_state, true);
                    $stateIconIndex = null;
                    if (is_array($state) && is_array($state['els'] ?? null)) {
                        foreach ($state['els'] as $index => $item) {
                            if (str_contains((string) ($item['html'] ?? ''), '/img/badges/icon-location.png')) {
                                $stateIconIndex = $index;
                                break;
                            }
                        }
                        if ($stateIconIndex === null
                            || ($state['els'][$stateIconIndex]['style']['left'] ?? null) !== '70px'
                            || ($state['els'][$stateIconIndex]['style']['top'] ?? null) !== '434px') {
                            continue;
                        }
                    }

                    $rule = ['type' => 'rect', 'x' => 108, 'y' => 417, 'w' => 181, 'h' => 4,
                        'color' => '#2e3440', 'fill' => true, 'radius' => 2, 'border' => 0];
                    array_splice($layout['elements'], $location, 0, [$rule]);
                    $layout['erase_default_rule'] = true;
                    foreach ($layout['elements'] as &$element) {
                        if (in_array($element['src'] ?? null, ['/img/badges/icon-location.png', '/img/badges/icon-calendar.png'], true)) {
                            $element['color'] ??= '#2e3440';
                        }
                    }
                    unset($element);
                    $values = ['layout' => json_encode($layout, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)];

                    if ($stateIconIndex !== null) {
                        $ruleState = [
                            'id' => 'el-footer-rule-'.$template->id,
                            'html' => '', 'className' => 'ed-el shape-box', 'label' => 'Séparateur',
                            'locked' => '0', 'hidden' => '0', 'group' => null, 'outline' => null,
                            'blend' => 'normal', 'qrUrl' => null,
                            'style' => ['left' => '108px', 'top' => '417px', 'width' => '181px',
                                'height' => '4px', 'background' => 'rgb(46, 52, 64)',
                                'borderRadius' => '2px', 'opacity' => '1'],
                        ];
                        array_splice($state['els'], $stateIconIndex, 0, [$ruleState]);
                        $state['bg'] = trim((string) ($state['bg'] ?? 'ed-page').' bg-co-line');
                        $values['editor_state'] = json_encode($state, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                    }

                    DB::table('document_templates')->where('id', $template->id)->update($values);
                }
            });
    }

    private function at(array $element, int $x, int $y): bool
    {
        return ($element['x'] ?? null) === $x && ($element['y'] ?? null) === $y;
    }

    public function down(): void
    {
        // Les calques peuvent être déplacés ou recolorés après la migration.
    }
};
