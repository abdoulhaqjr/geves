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
                    if (! is_array($layout) || ($layout['width'] ?? null) !== 397
                        || ($layout['height'] ?? null) !== 560
                        || ($layout['bg_image'] ?? null) !== '/img/badges/badge-default-bg.png') {
                        continue;
                    }

                    $elements = $layout['elements'] ?? [];
                    if (! is_array($elements)) {
                        continue;
                    }
                    $oldFooter = ($elements[6]['x'] ?? null) === 110 && ($elements[7]['x'] ?? null) === 110
                        && ($elements[8]['x'] ?? null) === 306 && ($elements[8]['y'] ?? null) === 430
                        && ($elements[8]['size'] ?? null) === 66;
                    $intermediateFooter = ($elements[6]['x'] ?? null) === 104 && ($elements[7]['x'] ?? null) === 104
                        && ($elements[8]['x'] ?? null) === 295 && ($elements[8]['y'] ?? null) === 438
                        && ($elements[8]['size'] ?? null) === 78;
                    if (count($elements) !== 9
                        || ($elements[6]['type'] ?? null) !== 'text'
                        || ! in_array($elements[6]['text'] ?? null, ['{location}', '[edition.location]'], true)
                        || ($elements[6]['y'] ?? null) !== 434
                        || ($elements[7]['type'] ?? null) !== 'text'
                        || ! in_array($elements[7]['text'] ?? null, ['{dates}', '[edition.dates]'], true)
                        || ($elements[7]['y'] ?? null) !== 472
                        || ($elements[8]['type'] ?? null) !== 'qr'
                        || (! $oldFooter && ! $intermediateFooter)) {
                        continue;
                    }

                    $layout['elements'][6]['x'] = 98;
                    $layout['elements'][6]['w'] = 190;
                    $layout['elements'][7]['x'] = 98;
                    $layout['elements'][7]['w'] = 190;
                    $layout['elements'][8]['x'] = 295;
                    $layout['elements'][8]['y'] = 438;
                    $layout['elements'][8]['size'] = 78;
                    if (in_array($layout['elements'][8]['value'] ?? null, ['{ref}', '{verify_url}', ''], true)) {
                        $layout['elements'][8]['value'] = '{verify_url}';
                    }

                    DB::table('document_templates')->where('id', $template->id)
                        ->update(['layout' => json_encode($layout, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)]);
                }
            });
    }

    public function down(): void
    {
        // Une mise en page peut être modifiée après cette migration : ne pas l'écraser.
    }
};
