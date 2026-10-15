<?php

use App\Models\Event;
use App\Models\TutorialVideoRequest;
use App\Services\TutorialVideoAgent;
use App\Services\TutorialVideoWorkerLauncher;
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

// La date limite ferme réellement l'option, même sans nouvelle modification admin.
Artisan::command('events:close-submissions', function (): void {
    $closed = Event::query()
        ->where('submission_open', true)
        ->whereNotNull('submission_deadline')
        ->where('submission_deadline', '<=', now())
        ->update(['submission_open' => false]);

    $this->info("{$closed} appel(s) à communications fermé(s).");
})->purpose('Fermer les soumissions dont la date limite est atteinte');
Schedule::command('events:close-submissions')->everyMinute()->withoutOverlapping();

// Sauvegardes automatiques (BDD + fichiers) via spatie/laravel-backup.
Schedule::command('backup:clean')->daily()->at('01:00');
Schedule::command('backup:run')->daily()->at('01:30');
Schedule::command('backup:monitor')->daily()->at('02:00');

// Rend les demandes de tutoriels directement depuis la base. Ce processus est
// démarré par l'interface d'administration : aucune queue worker externe n'est
// requise sur le poste qui a demandé la vidéo.
Artisan::command('tutorial-videos:render-pending {--limit=20 : Nombre maximal de vidéos à rendre}', function (TutorialVideoAgent $agent) {
    $processed = 0;
    $limit = max(1, (int) $this->option('limit'));

    while ($processed < $limit) {
        $request = TutorialVideoRequest::query()
            ->with('guide')
            ->where('status', 'queued')
            ->oldest('id')
            ->first();

        if ($request === null) {
            break;
        }

        $latestId = TutorialVideoRequest::query()
            ->where('tutorial_guide_id', $request->tutorial_guide_id)
            ->max('id');

        // Une demande plus récente remplace l'ancienne : elle seule peut publier
        // une vidéo et évite les rendus coûteux après plusieurs clics successifs.
        if ($latestId !== $request->id) {
            $request->update(['status' => 'superseded']);

            continue;
        }

        // La transition atomique évite que deux lanceurs rendent le même guide.
        $claimed = TutorialVideoRequest::query()
            ->whereKey($request->id)
            ->where('status', 'queued')
            ->update(['status' => 'running']);

        if ($claimed !== 1) {
            continue;
        }

        $request->refresh()->load('guide');
        $agent->produce($request);
        $processed++;
    }

    $this->info($processed.' vidéo(s) traitée(s).');
})->purpose('Rend les guides de prise en main en attente avec Gemini, Claude ou OpenAI et Hyperframes');

// Reprend les demandes vidéo créées avant l'activation du moteur Hyperframes.
Artisan::command('tutorial-videos:dispatch-pending {--retry : Reprendre aussi les demandes en échec}', function () {
    $statuses = $this->option('retry') ? ['queued', 'failed'] : ['queued'];
    $requests = TutorialVideoRequest::query()->whereIn('status', $statuses)->pluck('id');

    if ($this->option('retry')) {
        TutorialVideoRequest::query()->whereIn('id', $requests)->where('status', 'failed')->update(['status' => 'queued']);
    }

    if ($requests->isNotEmpty()) {
        app(TutorialVideoWorkerLauncher::class)->launch();
    }

    $this->info($requests->count().' demande(s) vidéo envoyée(s) à l’agent.');
})->purpose('Démarre les rendus Hyperframes des guides de prise en main en attente');
