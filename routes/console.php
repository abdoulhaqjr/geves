<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Envoi des e-mails programmés (campagnes/rappels) arrivés à échéance.
Schedule::command('emails:send')->everyMinute()->withoutOverlapping();

// Filet de sécurité des paiements en ligne : revérifie auprès des passerelles
// les paiements restés « en attente » (webhook jamais arrivé ou tardif).
Schedule::command('payments:reconcile')->everyTenMinutes()->withoutOverlapping();

// Statut des visios suivi sur l'horloge : une séance « programmée » passe « en
// direct » à l'approche de son horaire, et une séance passée (+ marge) passe
// « terminée » (ce qui coupe l'accès à la salle) - sans intervention manuelle.
Schedule::command('meetings:sync')->everyFiveMinutes()->withoutOverlapping();

// Sauvegardes automatiques (BDD + fichiers) via spatie/laravel-backup.
Schedule::command('backup:clean')->daily()->at('01:00');
Schedule::command('backup:run')->daily()->at('01:30');
Schedule::command('backup:monitor')->daily()->at('02:00');

// Reprend les demandes vidéo créées avant l'activation du pont Brag/Codex.
Artisan::command('tutorial-videos:dispatch-pending {--retry : Reprendre aussi les demandes en échec}', function () {
    $statuses = $this->option('retry') ? ['queued', 'failed'] : ['queued'];
    $requests = App\Models\TutorialVideoRequest::query()->whereIn('status', $statuses)->pluck('id');

    if ($this->option('retry')) {
        App\Models\TutorialVideoRequest::query()->whereIn('id', $requests)->where('status', 'failed')->update(['status' => 'queued']);
    }

    foreach ($requests as $requestId) {
        App\Jobs\GenerateTutorialVideo::dispatch($requestId)->onQueue('tutorial-videos');
    }

    if ($requests->isNotEmpty()) {
        app(App\Services\TutorialVideoWorkerLauncher::class)->launch(force: true);
    }

    $this->info($requests->count().' demande(s) vidéo envoyée(s) à l’agent.');
})->purpose('Démarre les rendus Brag des guides de prise en main en attente');
