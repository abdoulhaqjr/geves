<?php

namespace App\Support;

use App\Models\Event;
use App\Models\TutorialGuide;

/**
 * Écrans réels à intégrer aux tutoriels. Chaque chemin cible une vraie route
 * GEVES ; la connexion technique temporaire est effectuée par le pont signé.
 */
final class TutorialCaptureCatalog
{
    /** @return list<array{title: string, url: string}> */
    public static function targetsFor(TutorialGuide $guide): array
    {
        $event = Event::query()->where('status', 'open')->latest('id')->first()
            ?? Event::query()->latest('id')->first();
        $eventId = $event?->id;

        $targets = match ($guide->key) {
            'fonctionnement' => [
                ['Tableau de bord', route('admin.dashboard')],
                ['Événements', route('admin.evenements.index')],
                ['Prise en main', route('admin.onboarding')],
            ],
            'soumission' => [
                ['Soumissions', route('admin.submissions.index')],
                ['Relecteurs', route('admin.submissions.reviewers')],
                ['Options des soumissions', route('admin.submissions.options')],
            ],
            'visio' => [
                ['Sessions', route('admin.program.sessions', ['event_id' => $eventId])],
                ['Programme', route('admin.program.index', ['event_id' => $eventId, 'tab' => 'sessions'])],
                ['Rapports de séance', route('admin.program.reports', ['event_id' => $eventId])],
            ],
            'rapport' => [
                ['Rapports de séance', route('admin.program.reports', ['event_id' => $eventId])],
                ['Secrétariat', route('admin.secretariat', ['event_id' => $eventId])],
                ['Suivi des rapports', route('admin.secretariat.reports', ['event_id' => $eventId])],
            ],
            'moderation' => [
                ['Sessions', route('admin.program.sessions', ['event_id' => $eventId])],
                ['Programme', route('admin.program.index', ['event_id' => $eventId, 'tab' => 'planning'])],
                ['Rapports de séance', route('admin.program.reports', ['event_id' => $eventId])],
            ],
            'colloque' => [
                ['Événements', route('admin.evenements.index')],
                ['Projet', route('admin.project.index', ['event_id' => $eventId])],
                ['Programme', route('admin.program.index', ['event_id' => $eventId])],
            ],
            'configuration' => [
                ['Événements', route('admin.evenements.index')],
                ['Configuration de l’événement', $event ? route('admin.evenements.edit', $event) : route('admin.evenements.index')],
                ['Soumissions', route('admin.submissions.options')],
            ],
            'secretariat' => [
                ['Secrétariat', route('admin.secretariat', ['event_id' => $eventId])],
                ['Documents', route('admin.secretariat.documents', ['event_id' => $eventId])],
                ['Rapports', route('admin.secretariat.reports', ['event_id' => $eventId])],
            ],
            'chat-public' => [
                ['Accueil public', url('/')],
                ['Assistante publique', url('/assistante')],
                ['Informations publiques', url('/')],
            ],
            'chat-admin' => [
                ['Assistant IA', route('admin.ai', ['event_id' => $eventId])],
                ['Tableau de bord', route('admin.dashboard')],
                ['Prise en main', route('admin.onboarding')],
            ],
            default => [
                ['Tableau de bord', route('admin.dashboard')],
                ['Prise en main', route('admin.onboarding')],
                ['Événements', route('admin.evenements.index')],
            ],
        };

        // `$targets` est une liste et `array_map` sur une liste en rend une :
        // `array_values` n'y changeait rien.
        return array_map(
            fn (array $target): array => ['title' => $target[0], 'url' => $target[1]],
            $targets,
        );
    }

    public static function targetFor(TutorialGuide $guide, int $step): ?string
    {
        return self::targetsFor($guide)[$step - 1]['url'] ?? null;
    }
}
