<?php

namespace App\Services;

use App\Models\TutorialVideoRequest;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Throwable;

/** Produit et publie un rendu Hyperframes depuis les API IA configurées dans GEVES. */
class TutorialVideoAgent
{
    public function __construct(
        private readonly TutorialVideoCreativePlanner $planner,
        private readonly TutorialVideoScreenshotService $screenshots,
        private readonly TutorialVideoRenderer $renderer,
    ) {}

    public function produce(TutorialVideoRequest $request): void
    {
        $guide = $request->guide;
        $directory = storage_path('app/tutorial-video-production/request-'.$request->id);
        File::ensureDirectoryExists($directory);
        $context = $directory.DIRECTORY_SEPARATOR.'production-context.json';
        $result = $directory.DIRECTORY_SEPARATOR.'result.json';

        $this->progress($request, 'planning', 15, 'Préparation du scénario avec les assistants IA.');
        $request->update(['status' => 'running']);
        $guide->update(['video_generation_status' => 'generating']);

        try {
            $planning = $this->planner->prepare($request);
            $this->progress($request, 'captures', 35, 'Capture des écrans réels de GEVES avec le compte de démonstration.');
            $captures = $this->screenshots->capture($guide, $directory);
            $meta = array_merge($request->meta ?? [], [
                'creative' => $planning['creative'],
                'captures' => $captures,
            ]);
            unset($meta['failure']);
            $providers = $planning['providers'];
            $request->update(['meta' => $meta, 'providers' => $providers]);
            $request->refresh();

            File::put($context, json_encode($request->meta, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR));
            $this->progress($request, 'rendering', 70, 'Montage Hyperframes, musique et mouvements de l’interface.');
            $payload = $this->renderer->render($request->meta ?? [], $directory);
            $this->progress($request, 'publishing', 94, 'Contrôle et publication de la vidéo.');
            File::put($result, json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR));
            $source = $this->validatedVideoPath((string) ($payload['video'] ?? ''), $directory);
            $target = 'tutorial-guides/'.$guide->key.'-'.now()->format('YmdHis').'.mp4';
            Storage::disk('public')->put($target, File::get($source));

            if (filled($guide->video_path) && $guide->video_path !== $target) {
                Storage::disk('public')->delete($guide->video_path);
            }

            $guide->update([
                'video_path' => $target,
                'video_url' => null,
                'video_generation_status' => 'ready',
                'video_generation_prompt' => $request->prompt,
            ]);
            $request->update([
                'status' => 'ready',
                'meta' => array_merge($meta, ['result' => [
                    'video' => $target,
                    'poster' => $payload['poster'] ?? null,
                    'summary' => $payload['summary'] ?? null,
                    'completed_at' => now()->toIso8601String(),
                ], 'progress' => ['stage' => 'ready', 'percent' => 100, 'label' => 'Vidéo créée et publiée.']]),
            ]);
        } catch (Throwable $exception) {
            $request->update([
                'status' => 'failed',
                'meta' => array_merge($request->meta ?? [], ['failure' => [
                    'message' => $this->shortError($exception->getMessage()),
                    'at' => now()->toIso8601String(),
                ], 'progress' => ['stage' => 'failed', 'percent' => 100, 'label' => 'La production vidéo a échoué.']]),
            ]);

            if (! $guide->hasVideo()) {
                $guide->update(['video_generation_status' => 'failed']);
            } else {
                $guide->update(['video_generation_status' => 'ready']);
            }
        }
    }

    private function validatedVideoPath(string $candidate, string $directory): string
    {
        $source = realpath($candidate);
        $root = realpath($directory);

        if ($source === false || $root === false || ! str_starts_with($source, $root.DIRECTORY_SEPARATOR) || strtolower(pathinfo($source, PATHINFO_EXTENSION)) !== 'mp4') {
            throw new \RuntimeException('Le rendu Hyperframes est introuvable ou hors de son répertoire de production.');
        }

        return $source;
    }

    private function shortError(string $message): string
    {
        return str($message)->squish()->limit(1000, '…')->toString() ?: 'Le rendu vidéo n’a pas abouti.';
    }

    private function progress(TutorialVideoRequest $request, string $stage, int $percent, string $label): void
    {
        $request->update(['meta' => array_merge($request->meta ?? [], [
            'progress' => compact('stage', 'percent', 'label'),
        ])]);
        $request->refresh();
    }
}
