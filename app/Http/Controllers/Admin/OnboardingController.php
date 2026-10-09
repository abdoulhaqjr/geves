<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\TutorialGuide;
use App\Models\TutorialVideoRequest;
use App\Services\TutorialVideoWorkerLauncher;
use App\Support\TutorialGuideCatalog;
use App\Support\TutorialVideoProduction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

/** Point d'entrée de la prise en main du back-office. */
class OnboardingController extends Controller
{
    public function __invoke(Request $request): View
    {
        TutorialGuideCatalog::ensureDefaults();

        return view('admin.onboarding', [
            'guides' => TutorialGuide::query()->with(['videoRequests' => fn ($query) => $query->latest()->limit(1)])->orderBy('sort_order')->get(),
            'canManage' => $request->user()?->isColloqueAdmin() ?? false,
        ]);
    }

    public function update(Request $request, TutorialGuide $guide): RedirectResponse
    {
        $this->authorizeManagement($request);
        $data = $request->validate([
            'title' => ['required', 'string', 'max:180'],
            'intro' => ['required', 'string', 'max:3000'],
            'steps_text' => ['required', 'string', 'max:6000'],
            'audience' => ['required', 'in:admin,participant,both'],
            'participant_roles' => ['nullable', 'array'],
            'participant_roles.*' => ['in:all,author,coauthor,reviewer,committee,moderator,rapporteur,attendee'],
            'video_url' => ['nullable', 'url', 'max:2000'],
            'video' => ['nullable', 'file', 'mimetypes:video/mp4,video/webm,video/quicktime', 'max:512000'],
            'video_visible_to_participants' => ['nullable', 'boolean'],
            'remove_video' => ['nullable', 'boolean'],
        ]);

        $steps = collect(preg_split('/\r\n|\r|\n/', $data['steps_text']))
            ->map(fn ($step) => trim((string) $step))
            ->filter()->values()->all();

        if ($steps === []) {
            return back()->withErrors(['steps_text' => 'Ajoutez au moins une étape.'])->withInput();
        }

        $attributes = [
            'title' => $data['title'],
            'intro' => $data['intro'],
            'steps' => $steps,
            'audience' => $data['audience'],
            'participant_roles' => $data['participant_roles'] ?? [],
            'video_visible_to_participants' => $request->boolean('video_visible_to_participants'),
        ];

        if ($request->boolean('remove_video')) {
            if (filled($guide->video_path)) {
                Storage::disk('public')->delete($guide->video_path);
            }
            $attributes['video_path'] = null;
            $attributes['video_url'] = null;
        } elseif ($request->hasFile('video')) {
            if (filled($guide->video_path)) {
                Storage::disk('public')->delete($guide->video_path);
            }
            $attributes['video_path'] = $request->file('video')->store('tutorial-guides', 'public');
            $attributes['video_url'] = null;
            $attributes['video_generation_status'] = 'ready';
        } elseif (filled($data['video_url'] ?? null)) {
            $attributes['video_path'] = null;
            $attributes['video_url'] = $data['video_url'];
            $attributes['video_generation_status'] = 'ready';
        }

        $guide->update($attributes);

        return redirect()->route('admin.onboarding', ['tab' => $request->input('tab', 'all')])->with('success', 'Le guide a été mis à jour.');
    }

    public function requestVideo(Request $request, TutorialGuide $guide): RedirectResponse|JsonResponse
    {
        $this->authorizeManagement($request);
        $data = $request->validate([
            'action' => ['required', 'in:generate,update'],
            'prompt' => ['nullable', 'string', 'max:3000'],
            'music_choice' => ['nullable', 'in:signature,calm,dynamic,none'],
            'music' => ['nullable', 'file', 'mimetypes:audio/mpeg,audio/mp4,audio/aac,audio/wav,audio/x-wav', 'max:25600'],
        ]);

        $videoRequest = $this->queueVideoRequest($guide, $request, $data['action'], $data['prompt'] ?? null, $this->music($request, $data));
        app(TutorialVideoWorkerLauncher::class)->launch();

        if ($request->expectsJson()) {
            return response()->json(['request' => $this->statusPayload($videoRequest)], 202);
        }

        return redirect()->route('admin.onboarding', ['tab' => $request->input('tab', 'all')])
            ->with('success', 'La demande vidéo a été enregistrée : le rendu démarre automatiquement sur le serveur GEVES.');
    }

    public function requestAllVideos(Request $request): RedirectResponse|JsonResponse
    {
        $this->authorizeManagement($request);
        $data = $request->validate([
            'prompt' => ['nullable', 'string', 'max:3000'],
            'music_choice' => ['nullable', 'in:signature,calm,dynamic,none'],
            'music' => ['nullable', 'file', 'mimetypes:audio/mpeg,audio/mp4,audio/aac,audio/wav,audio/x-wav', 'max:25600'],
        ]);
        $music = $this->music($request, $data);
        TutorialGuideCatalog::ensureDefaults();

        $created = 0;
        $requests = [];
        TutorialGuide::query()->whereNull('video_path')->whereNull('video_url')->each(function (TutorialGuide $guide) use ($request, $data, $music, &$created, &$requests): void {
            $alreadyQueued = $guide->videoRequests()->whereIn('status', ['queued', 'running'])->exists();
            if (! $alreadyQueued) {
                $requests[] = $this->queueVideoRequest($guide, $request, 'generate', $data['prompt'] ?? null, $music);
                $created++;
            }
        });

        if ($requests !== []) {
            app(TutorialVideoWorkerLauncher::class)->launch();
        }

        if ($request->expectsJson()) {
            return response()->json([
                'requests' => array_map(fn (TutorialVideoRequest $videoRequest) => $this->statusPayload($videoRequest), $requests),
                'message' => $created > 0 ? $created.' production(s) lancée(s).' : 'Aucune nouvelle production n’était nécessaire.',
            ], 202);
        }

        return redirect()->route('admin.onboarding')->with('success', $created > 0
            ? $created.' demande(s) vidéo ont été enregistrées.'
            : 'Aucune nouvelle demande vidéo n’était nécessaire.');
    }

    private function authorizeManagement(Request $request): void
    {
        abort_unless($request->user()?->isColloqueAdmin(), 403);
    }

    /** Le navigateur interroge ce point pendant le rendu sans recharger la page. */
    public function videoStatus(Request $request, TutorialVideoRequest $videoRequest): JsonResponse
    {
        $this->authorizeManagement($request);

        return response()->json(['request' => $this->statusPayload($videoRequest)]);
    }

    /** @param array{choice: string, path: string|null} $music */
    private function queueVideoRequest(TutorialGuide $guide, Request $request, string $action, ?string $prompt, array $music): TutorialVideoRequest
    {
        $videoRequest = TutorialVideoRequest::create([
            'tutorial_guide_id' => $guide->id,
            'requested_by_user_id' => $request->user()->id,
            'action' => $action,
            'prompt' => blank($prompt) ? null : $prompt,
            'status' => 'queued',
            'providers' => [],
            'meta' => array_merge(TutorialVideoProduction::briefFor($guide, $prompt, $music), [
                'progress' => ['stage' => 'queued', 'percent' => 5, 'label' => 'Demande enregistrée, en attente du moteur vidéo.'],
            ]),
        ]);

        $guide->update([
            'video_generation_status' => 'queued',
            'video_generation_prompt' => blank($prompt) ? null : $prompt,
        ]);

        return $videoRequest;
    }

    /** @param array<string, mixed> $data @return array{choice: string, path: string|null} */
    private function music(Request $request, array $data): array
    {
        return [
            'choice' => (string) ($data['music_choice'] ?? 'signature'),
            'path' => $request->hasFile('music') ? $request->file('music')->store('tutorial-music', 'public') : null,
        ];
    }

    /** @return array<string, mixed> */
    private function statusPayload(TutorialVideoRequest $videoRequest): array
    {
        $videoRequest->loadMissing('guide');
        $progress = data_get($videoRequest->meta, 'progress', []);
        $fallbacks = [
            'queued' => ['stage' => 'queued', 'percent' => 5, 'label' => 'Demande enregistrée, en attente du moteur vidéo.'],
            'running' => ['stage' => 'planning', 'percent' => 15, 'label' => 'Préparation du scénario avec les assistants IA.'],
            'ready' => ['stage' => 'ready', 'percent' => 100, 'label' => 'Vidéo créée et publiée.'],
            'failed' => ['stage' => 'failed', 'percent' => 100, 'label' => 'La production vidéo a échoué.'],
        ];
        $progress = array_merge($fallbacks[$videoRequest->status] ?? $fallbacks['queued'], is_array($progress) ? $progress : []);

        return [
            'id' => $videoRequest->id,
            'status' => $videoRequest->status,
            'stage' => $progress['stage'],
            'percent' => (int) $progress['percent'],
            'label' => (string) $progress['label'],
            'error' => data_get($videoRequest->meta, 'failure.message'),
            'guide_title' => $videoRequest->guide?->title,
            'video_url' => $videoRequest->status === 'ready' ? $videoRequest->guide?->videoUrl() : null,
            'status_url' => route('admin.onboarding.videos.status', $videoRequest),
        ];
    }
}
