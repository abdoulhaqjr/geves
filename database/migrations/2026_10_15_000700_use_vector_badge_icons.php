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
                    $state = json_decode((string) $template->editor_state, true);
                    $values = [];

                    if (is_array($layout) && is_array($layout['elements'] ?? null)) {
                        $changed = false;
                        foreach ($layout['elements'] as &$element) {
                            $src = $element['src'] ?? null;
                            if (in_array($src, ['/img/badges/icon-location.png', '/img/badges/icon-calendar.png'], true)) {
                                $element['src'] = substr($src, 0, -4).'.svg';
                                $changed = true;
                            }
                            if (($element['type'] ?? null) === 'rect'
                                && ($element['x'] ?? null) === 108 && ($element['y'] ?? null) === 417
                                && ($element['w'] ?? null) === 181 && ($element['h'] ?? null) === 4
                                && ! isset($element['shape'])) {
                                $element['shape'] = 'line-h';
                                $changed = true;
                            }
                        }
                        unset($element);
                        if ($changed) {
                            $values['layout'] = json_encode($layout, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                        }
                    }

                    if (is_array($state) && is_array($state['els'] ?? null)) {
                        $changed = false;
                        foreach ($state['els'] as &$element) {
                            $html = (string) ($element['html'] ?? '');
                            $updated = str_replace(
                                ['/img/badges/icon-location.png', '/img/badges/icon-calendar.png'],
                                ['/img/badges/icon-location.svg', '/img/badges/icon-calendar.svg'],
                                $html
                            );
                            if ($updated !== $html) {
                                $element['html'] = $updated;
                                $changed = true;
                            }
                            if (($element['label'] ?? null) === 'Séparateur'
                                && ($element['style']['width'] ?? null) === '181px'
                                && ($element['style']['height'] ?? null) === '4px'
                                && ! isset($element['shape'])) {
                                $element['shape'] = 'line-h';
                                $changed = true;
                            }
                        }
                        unset($element);
                        if ($changed) {
                            $values['editor_state'] = json_encode($state, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                        }
                    }

                    if ($values !== []) {
                        DB::table('document_templates')->where('id', $template->id)->update($values);
                    }
                }
            });
    }

    public function down(): void
    {
        // Les utilisateurs peuvent modifier les pictogrammes et le filet après cette migration.
    }
};
