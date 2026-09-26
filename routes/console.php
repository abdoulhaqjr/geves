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

// Sauvegardes automatiques (BDD + fichiers) via spatie/laravel-backup.
Schedule::command('backup:clean')->daily()->at('01:00');
Schedule::command('backup:run')->daily()->at('01:30');
Schedule::command('backup:monitor')->daily()->at('02:00');
