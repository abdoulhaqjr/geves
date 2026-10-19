<?php

namespace App\Jobs;

use App\Documents\BadgePdfRenderer;
use App\Documents\BadgeSheet;
use App\Models\BadgeExport;
use App\Models\Event;
use App\Support\BadgeCatalog;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use setasign\Fpdi\Fpdi;

/** Rend des lots de 48, puis assemble un unique PDF hors de la requête HTTP. */
class GenerateBadgeExport implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 3500;

    public int $tries = 1;

    public function __construct(public readonly string $exportId)
    {
        $this->onConnection('badge_exports')->onQueue('badge-exports');
    }

    public function handle(BadgePdfRenderer $renderer): void
    {
        $export = BadgeExport::query()->find($this->exportId);
        if (! $export || $export->status !== 'queued') {
            return;
        }

        $export->update(['status' => 'running', 'error' => null]);
        @set_time_limit(0);
        @ini_set('memory_limit', '1024M');
        $disk = Storage::disk('local');
        $directory = 'badge-exports/'.$export->id;

        try {
            $event = Event::query()->findOrFail($export->event_id);
            $people = BadgeCatalog::recipients($event, (string) $export->type);
            if ($people->isEmpty()) {
                throw new \RuntimeException('Aucun badge prêt pour cette catégorie.');
            }

            $parts = [];
            foreach ($people->chunk(BadgeSheet::LOT) as $index => $batch) {
                $pdf = $renderer->pdf($event, [['type' => (string) $export->type, 'people' => $batch->values()->all()]]);
                $part = $directory.'/part-'.($index + 1).'.pdf';
                if (! $disk->put($part, $pdf->output())) {
                    throw new \RuntimeException('Impossible d’enregistrer un lot de badges.');
                }
                $parts[] = $part;
                unset($pdf);
                gc_collect_cycles();
                $export->update(['processed_count' => min($people->count(), ($index + 1) * BadgeSheet::LOT)]);
            }

            $merged = new Fpdi;
            $merged->SetTitle('Badges - '.$event->title.' - '.config('app.name', 'GEVES'));
            $merged->SetAuthor(config('app.name', 'GEVES').' - Université de Kara');
            foreach ($parts as $part) {
                $pageCount = $merged->setSourceFile($disk->path($part));
                for ($page = 1; $page <= $pageCount; $page++) {
                    $template = $merged->importPage($page);
                    $size = $merged->getTemplateSize($template);
                    $merged->AddPage($size['orientation'], [$size['width'], $size['height']]);
                    $merged->useTemplate($template);
                }
            }

            $path = $directory.'/badges-'.$export->type.'-complets.pdf';
            if (! $disk->put($path, $merged->Output('S'))) {
                throw new \RuntimeException('Impossible d’enregistrer le PDF complet.');
            }
            foreach ($parts as $part) {
                $disk->delete($part);
            }
            $export->update(['status' => 'ready', 'path' => $path, 'badge_count' => $people->count()]);
        } catch (\Throwable $exception) {
            $disk->deleteDirectory($directory);
            $export->update(['status' => 'failed', 'error' => $exception->getMessage()]);
            Log::error('Échec de l’export complet des badges.', ['export_id' => $export->id, 'exception' => $exception]);
        }
    }

    public function failed(?\Throwable $exception): void
    {
        BadgeExport::query()->whereKey($this->exportId)->whereIn('status', ['queued', 'running'])
            ->update(['status' => 'failed', 'error' => $exception?->getMessage() ?? 'Le traitement a été interrompu.']);
    }
}
