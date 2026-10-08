<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\GenerateTutorialVideo;
use App\Models\TutorialGuide;
use App\Models\TutorialVideoRequest;
use App\Support\TutorialGuideCatalog;
use App\Support\TutorialVideoProduction;
use App\Services\TutorialVideoWorkerLauncher;
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

    public function requestVideo(Request $request, TutorialGuide $guide): RedirectResponse
    {
        $this->authorizeManagement($request);
        $data = $request->validate([
            'action' => ['required', 'in:generate,update'],
            'prompt' => ['nullable', 'string', 'max:3000'],
        ]);

        $videoRequest = $this->queueVideoRequest($guide, $request, $data['action'], $data['prompt'] ?? null);
        GenerateTutorialVideo::dispatch($videoRequest->id)->onQueue('tutorial-videos');
        app(TutorialVideoWorkerLauncher::class)->launch();

        return redirect()->route('admin.onboarding', ['tab' => $request->input('tab', 'all')])
            ->with('success', 'La demande vidéo a été enregistrée et est prête pour l’agent Brag.');
    }

    public function requestAllVideos(Request $request): RedirectResponse
    {
        $this->authorizeManagement($request);
        $data = $request->validate(['prompt' => ['nullable', 'string', 'max:3000']]);
        TutorialGuideCatalog::ensureDefaults();

        $created = 0;
        $requests = [];
        TutorialGuide::query()->whereNull('video_path')->whereNull('video_url')->each(function (TutorialGuide $guide) use ($request, $data, &$created, &$requests): void {
            $alreadyQueued = $guide->videoRequests()->whereIn('status', ['queued', 'running'])->exists();
            if (! $alreadyQueued) {
                $requests[] = $this->queueVideoRequest($guide, $request, 'generate', $data['prompt'] ?? null);
                $created++;
            }
        });

        foreach ($requests as $videoRequest) {
            GenerateTutorialVideo::dispatch($videoRequest->id)->onQueue('tutorial-videos');
        }
        if ($requests !== []) {
            app(TutorialVideoWorkerLauncher::class)->launch();
        }

        return redirect()->route('admin.onboarding')->with('success', $created > 0
            ? $created.' demande(s) vidéo ont été enregistrées.'
            : 'Aucune nouvelle demande vidéo n’était nécessaire.');
    }

    private function authorizeManagement(Request $request): void
    {
        abort_unless($request->user()?->isColloqueAdmin(), 403);
    }

    private function queueVideoRequest(TutorialGuide $guide, Request $request, string $action, ?string $prompt): TutorialVideoRequest
    {
        $videoRequest = TutorialVideoRequest::create([
            'tutorial_guide_id' => $guide->id,
            'requested_by_user_id' => $request->user()->id,
            'action' => $action,
            'prompt' => blank($prompt) ? null : $prompt,
            'status' => 'queued',
            'providers' => ['codex'],
            'meta' => TutorialVideoProduction::briefFor($guide, $prompt),
        ]);

        $guide->update([
            'video_generation_status' => 'queued',
            'video_generation_prompt' => blank($prompt) ? null : $prompt,
        ]);

        return $videoRequest;
    }
}
