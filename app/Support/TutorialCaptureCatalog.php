<?php

namespace App\Support;

use App\Models\Event;
use App\Models\TutorialGuide;

/** Pages réelles correspondant, dans l'ordre, aux étapes de chaque guide. */
final class TutorialCaptureCatalog
{
    /** @return list<array{title: string, url: string, guard: 'guest'|'account'|'web'}> */
    public static function targetsFor(TutorialGuide $guide): array
    {
        $event = Event::query()->where('status', 'open')->latest('id')->first()
            ?? Event::query()->latest('id')->first();
        $eventId = $event?->id;
        $submissionForm = $event && $event->isPublished()
            ? route('account.submissions.create', $event->slug)
            : route('account.submissions.index');
        $eventForm = $event ? route('admin.evenements.edit', $event) : route('admin.evenements.index');

        // Le troisième champ fixe l'identité de capture. Un tutoriel participant
        // ne doit jamais montrer un écran du back-office administrateur.
        $targets = match ($guide->key) {
            'fonctionnement-participant' => [
                ['Accueil des colloques', url('/'), 'guest'],
                ['Créer un compte participant', route('account.register'), 'guest'],
                ['Compléter mon profil', route('account.profile'), 'account'],
                ['Tableau de bord participant', route('account.dashboard'), 'account'],
                ['Mes soumissions', route('account.submissions.index'), 'account'],
                ['Mes documents', route('account.documents'), 'account'],
            ],
            'fonctionnement' => [
                ['Tableau de bord', route('admin.dashboard'), 'web'],
                ['Événements accessibles', route('admin.evenements.index'), 'web'],
                ['Créer ou modifier un événement', route('admin.evenements.index'), 'web'],
                ['Soumissions scientifiques', route('admin.submissions.index'), 'web'],
                ['Programme du colloque', route('admin.program.index', ['event_id' => $eventId]), 'web'],
                ['Secrétariat', route('admin.secretariat', ['event_id' => $eventId]), 'web'],
                ['Projet et pilotage', route('admin.project.index', ['event_id' => $eventId]), 'web'],
            ],
            'soumission' => [
                ['Inscription participant', route('account.register'), 'guest'],
                ['Profil du participant', route('account.profile'), 'account'],
                ['Mes soumissions', route('account.submissions.index'), 'account'],
                ['Dépôt : informations générales', $submissionForm, 'account'],
                ['Dépôt : auteurs', $submissionForm, 'account'],
                ['Dépôt : résumé et fichiers', $submissionForm, 'account'],
                ['Suivi des soumissions', route('account.submissions.index'), 'account'],
                ['Décision et programmation', route('account.submissions.index'), 'account'],
            ],
            'visio' => [
                ['Ma prochaine séance', route('account.dashboard'), 'account'],
                ['Programme en direct', route('public.deroulement'), 'account'],
                ['Accès à la séance', route('public.deroulement'), 'account'],
                ['Commandes de la salle', route('public.deroulement'), 'account'],
                ['Caméra de la salle', route('public.deroulement'), 'account'],
                ['Présentation à partager', route('public.deroulement'), 'account'],
                ['Fin de la séance', route('account.dashboard'), 'account'],
            ],
            'rapport' => [
                ['Mes séances à rapporter', route('account.reports'), 'account'],
                ['Programme de la séance', route('public.deroulement'), 'account'],
                ['Présences de la séance', route('account.reports'), 'account'],
                ['Interventions et échanges', route('account.reports'), 'account'],
                ['Rédaction du rapport', route('account.reports'), 'account'],
                ['Suivi du rapport soumis', route('account.reports'), 'account'],
            ],
            'moderation' => [
                ['Mes séances à modérer', route('account.moderation'), 'account'],
                ['Programme des communications', route('account.moderation'), 'account'],
                ['Présences à pointer', route('account.moderation'), 'account'],
                ['Animation de la séance', route('public.deroulement'), 'account'],
                ['Salle de visioconférence', route('public.deroulement'), 'account'],
                ['Passage des communications', route('account.moderation'), 'account'],
                ['Clôture de la séance', route('account.moderation'), 'account'],
            ],
            'colloque' => [
                ['Liste des événements', route('admin.evenements.index'), 'web'],
                ['Configuration de l’événement', $eventForm, 'web'],
                ['Soumissions', route('admin.submissions.index'), 'web'],
                ['Programme', route('admin.program.index', ['event_id' => $eventId]), 'web'],
                ['Participants et secrétariat', route('admin.secretariat', ['event_id' => $eventId]), 'web'],
                ['Projet', route('admin.project.index', ['event_id' => $eventId]), 'web'],
                ['Rapports', route('admin.reports.index', ['event_id' => $eventId]), 'web'],
            ],
            'configuration' => [
                ['Événements', route('admin.evenements.index'), 'web'],
                ['Informations publiques', $eventForm, 'web'],
                ['Formulaire de soumission', $event ? route('admin.evenements.form', $event) : route('admin.evenements.index'), 'web'],
                ['Tarifs et paiements', $eventForm, 'web'],
                ['Visioconférence', $eventForm, 'web'],
                ['Équipe de l’événement', $eventForm, 'web'],
                ['Contrôle des réglages', $eventForm, 'web'],
            ],
            'secretariat' => [
                ['Secrétariat', route('admin.secretariat', ['event_id' => $eventId]), 'web'],
                ['Documents logistiques', route('admin.secretariat.documents', ['event_id' => $eventId]), 'web'],
                ['Badges et attestations', route('admin.documents.attestations', ['event_id' => $eventId]), 'web'],
                ['Kit de séance', route('admin.secretariat.documents', ['event_id' => $eventId]), 'web'],
                ['Suivi des participants', route('admin.secretariat', ['event_id' => $eventId]), 'web'],
                ['Rapports de séance', route('admin.secretariat.reports', ['event_id' => $eventId]), 'web'],
                ['Secrétariat assisté', route('admin.secretariat.assistant', ['event_id' => $eventId]), 'web'],
            ],
            'chat-public' => [
                ['Ouvrir l’assistante publique', route('public.gesco'), 'guest'],
                ['Questions sur le colloque', route('public.gesco'), 'guest'],
                ['Questions sur mon dossier', route('public.gesco'), 'account'],
                ['Déposer une réclamation', route('public.gesco'), 'account'],
                ['Suivi d’une demande', route('public.gesco'), 'account'],
            ],
            'chat-admin' => [
                ['Assistant IA du colloque', route('admin.ai', ['event_id' => $eventId]), 'web'],
                ['Formuler une demande', route('admin.ai', ['event_id' => $eventId]), 'web'],
                ['Contrôler les résultats', route('admin.ai', ['event_id' => $eventId]), 'web'],
                ['Approuver une action', route('admin.ai', ['event_id' => $eventId]), 'web'],
                ['Vérifier le résultat', route('admin.ai', ['event_id' => $eventId]), 'web'],
            ],
            default => [['Tableau de bord', route('admin.dashboard'), 'web']],
        };

        $mapped = array_map(
            fn (array $target): array => ['title' => $target[0], 'url' => $target[1], 'guard' => $target[2]],
            $targets,
        );

        $last = end($mapped);

        return array_map(fn (int $index): array => $mapped[$index] ?? $last, range(0, max(1, count($guide->steps ?: [])) - 1));
    }

    /** @return array{title: string, url: string, guard: 'guest'|'account'|'web'}|null */
    public static function targetFor(TutorialGuide $guide, int $step): ?array
    {
        return self::targetsFor($guide)[$step - 1] ?? null;
    }
}
