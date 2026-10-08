<?php

namespace App\Jobs;

use App\Models\TutorialVideoRequest;
use App\Services\TutorialVideoAgent;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/** Exécute un rendu Hyperframes autonome sans bloquer le back-office. */
class GenerateTutorialVideo implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 3600;

    public int $tries = 1;

    public function __construct(public readonly int $requestId) {}

    public function handle(TutorialVideoAgent $agent): void
    {
        $request = TutorialVideoRequest::query()->with('guide')->find($this->requestId);

        if ($request === null || $request->status !== 'queued' || $request->guide === null) {
            return;
        }

        $agent->produce($request);
    }
}
