<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('document_templates')
            ->where('kind', 'badge')
            ->whereNull('editor_state')
            ->orderBy('id')
            ->chunkById(100, function ($templates): void {
                foreach ($templates as $template) {
                    $layout = json_decode((string) $template->layout, true);
                    if (! is_array($layout)
                        || ($layout['width'] ?? null) !== 397
                        || ($layout['height'] ?? null) !== 560
                        || ($layout['bg_image'] ?? null) !== '/img/badges/badge-default-bg.png') {
                        continue;
                    }

                    $elements = $layout['elements'] ?? null;
                    if (! is_array($elements) || count($elements) !== 9
                        || ($elements[6]['type'] ?? null) !== 'text'
                        || ! in_array($elements[6]['text'] ?? null, ['{location}', '[edition.location]'], true)
                        || ($elements[6]['x'] ?? null) !== 98
                        || ($elements[6]['y'] ?? null) !== 434
                        || ($elements[7]['type'] ?? null) !== 'text'
                        || ! in_array($elements[7]['text'] ?? null, ['{dates}', '[edition.dates]'], true)
                        || ($elements[7]['x'] ?? null) !== 98
                        || ($elements[7]['y'] ?? null) !== 472
                        || ($elements[8]['type'] ?? null) !== 'qr'
                        || ($elements[8]['x'] ?? null) !== 295
                        || ($elements[8]['y'] ?? null) !== 438
                        || ($elements[8]['size'] ?? null) !== 78) {
                        continue;
                    }

                    array_splice($elements, 6, 0, [
                        ['type' => 'image', 'x' => 68, 'y' => 432, 'w' => 28, 'h' => 30, 'src' => '/img/badges/icon-location.png'],
                        ['type' => 'image', 'x' => 69, 'y' => 470, 'w' => 25, 'h' => 25, 'src' => '/img/badges/icon-calendar.png'],
                    ]);
                    $layout['elements'] = $elements;

                    DB::table('document_templates')->where('id', $template->id)
                        ->update(['layout' => json_encode($layout, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)]);
                }
            });
    }

    public function down(): void
    {
        // Un modèle peut être modifié ensuite : ne pas retirer des calques sauvegardés.
    }
};
