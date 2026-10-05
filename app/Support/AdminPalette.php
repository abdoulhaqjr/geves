<?php

namespace App\Support;

use App\Models\User;

/**
 * Destinations de la palette de commandes (⌘K) du back-office admin,
 * filtrées par les permissions du membre du staff connecté.
 */
class AdminPalette
{
    /**
     * @return array<int, array{label: string, group: string, icon: string, url: string}>
     */
    public static function items(?User $staff): array
    {
        $items = [];
        $push = function (bool $cond, string $label, string $group, string $icon, string $url) use (&$items): void {
            if ($cond) {
                $items[] = ['label' => $label, 'group' => $group, 'icon' => $icon, 'url' => $url];
            }
        };

        $push(true, 'Tableau de bord', 'Pilotage', 'home', route('admin.dashboard'));
        $push((bool) $staff?->canAnyEdition('editions_read'), 'Événements', 'Scientifique', 'calendar', route('admin.evenements.index'));
        $push((bool) $staff?->canAnyEdition('submissions_list'), 'Soumissions', 'Scientifique', 'inbox', route('admin.submissions.index'));
        $push((bool) $staff?->canAnyEdition('submissions_list'), 'Attribution auto', 'Scientifique', 'refresh', route('admin.submissions.auto-assign'));
        $push((bool) $staff?->canAnyEdition('participants'), 'Participants', 'Scientifique', 'users', route('admin.participants.index'));
        $push((bool) $staff?->canAnyEdition('editions_read'), 'Programme', 'Scientifique', 'calendar', route('admin.program.index'));
        $push((bool) $staff?->canAnyEdition('editions_read'), 'Créneaux', 'Scientifique', 'clock', route('admin.program.index', ['tab' => 'creneaux']));
        $push((bool) $staff?->canAnyEdition('editions_read'), 'Rapports de séance', 'Scientifique', 'sheet', route('admin.program.index', ['tab' => 'rapports']));
        $push((bool) $staff?->canAnyEdition('payments'), 'Paiements', 'Gestion', 'card', route('admin.payments.index'));
        $push((bool) $staff?->canAnyEdition('documents'), 'Documents', 'Gestion', 'folder', route('admin.documents.attestations'));
        $push((bool) $staff?->canAnyEdition('documents'), 'Attestations délivrées', 'Gestion', 'diploma', route('admin.documents.attestations', ['tab' => 'attestations', 'stab' => 'delivrees']));
        $push((bool) $staff?->canAnyEdition('emails'), 'E-mails', 'Gestion', 'mail', route('admin.emails.index'));
        $push((bool) $staff?->canAnyEdition('view_dashboard'), 'Rapports', 'Pilotage', 'chart', route('admin.reports.index'));
        $push((bool) $staff?->canAnyEdition('view_dashboard'), 'Assistant IA', 'Pilotage', 'sparkles', route('admin.ai'));
        $push((bool) $staff?->can('users_roles'), 'Utilisateurs', 'Système', 'shield', route('admin.users.index'));
        $push((bool) $staff?->can('settings'), 'Réglages', 'Système', 'settings', route('admin.settings'));

        return $items;
    }
}
