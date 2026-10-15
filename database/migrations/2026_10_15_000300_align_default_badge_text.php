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
                    if (! is_array($layout)
                        || ($layout['width'] ?? null) !== 397
                        || ($layout['height'] ?? null) !== 560
                        || ($layout['bg_image'] ?? null) !== '/img/badges/badge-default-bg.png'
                        || ! is_array($layout['elements'] ?? null)) {
                        continue;
                    }

                    $changed = [];
                    foreach ($layout['elements'] as &$element) {
                        if (($element['type'] ?? null) !== 'text') {
                            continue;
                        }

                        $token = mb_strtolower((string) ($element['text'] ?? ''));
                        $x = (int) ($element['x'] ?? -1);
                        $y = (int) ($element['y'] ?? -1);
                        $w = (int) ($element['w'] ?? -1);
                        $h = (int) ($element['h'] ?? -1);

                        if (in_array($token, ['[recipient.name]', '{name}'], true)
                            && $x === 20 && in_array($y, [232, 272], true) && $w === 357
                            && $h === 40 && ($element['size'] ?? null) === 24
                            && ($element['weight'] ?? null) === 800) {
                            $element['size'] = 22;
                            $changed[] = [$token, $x, $y, 'fontSize', '22px'];
                        }

                        if (in_array($token, ['[edition.name]', '{event}'], true)
                            && $x === 24 && in_array($y, [108, 150], true) && $w === 349
                            && $h > 0 && $h < 54) {
                            $element['h'] = 54;
                            $changed[] = [$token, $x, $y, 'height', '54px'];
                        }

                        $themeHeight = $y === 350 ? 70 : 80;
                        if (in_array($token, ['[edition.theme]', '{theme}'], true)
                            && $x === 40 && in_array($y, [322, 350], true) && $w === 317
                            && $h > 0 && $h < $themeHeight) {
                            $element['h'] = $themeHeight;
                            $changed[] = [$token, $x, $y, 'height', $themeHeight.'px'];
                        }
                    }
                    unset($element);

                    if ($changed === []) {
                        continue;
                    }

                    $values = ['layout' => json_encode($layout, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)];
                    $state = json_decode((string) $template->editor_state, true);
                    if (is_array($state) && isset($state['els']) && is_array($state['els'])) {
                        foreach ($state['els'] as &$item) {
                            foreach ($changed as [$token, $x, $y, $property, $value]) {
                                $style = $item['style'] ?? [];
                                if (str_starts_with(mb_strtolower((string) ($item['html'] ?? '')), $token)
                                    && (int) ($style['left'] ?? -1) === $x
                                    && (int) ($style['top'] ?? -1) === $y) {
                                    $item['style'][$property] = $value;
                                }
                            }
                        }
                        unset($item);
                        $values['editor_state'] = json_encode($state, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                    }

                    DB::table('document_templates')->where('id', $template->id)->update($values);
                }
            });
    }

    public function down(): void
    {
        // Les modèles peuvent être édités ensuite : ne pas rétablir l'ancienne taille.
    }
};
