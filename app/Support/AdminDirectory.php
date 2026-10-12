<?php

namespace App\Support;

use App\Models\Event;
use Illuminate\Support\Str;

/**
 * Annuaire NOMINATIF d'un événement pour le copilote d'administration (« Marc ») :
 * participants, inscrits, paiements et auteurs avec noms, prénoms et e-mails. De
 * quoi répondre « quel est l'e-mail de X », « liste les participants non réglés »,
 * « qui a payé le reçu REC-… ».
 *
 * ⚠️ RÉSERVÉ AU COPILOTE ADMIN (`AskAdmin`), JAMAIS à l'assistante publique Gesco :
 * ce sont des données personnelles de tiers. Le prompt admin cadre déjà la réponse
 * sur la FONCTION de l'utilisateur.
 *
 * ⚠️ Borné (le prompt est reconstruit à CHAQUE message) : au-delà des plafonds, on
 * renvoie vers les filtres/export des pages dédiées plutôt que de gonfler le contexte.
 * Réutilise {@see EventReport} (source unique des lignes nominatives d'export).
 */
class AdminDirectory
{
    public static function digest(?Event $event, int $maxParticipants = 200, int $maxPayments = 120): string
    {
        if (! $event) {
            return '';
        }

        $report = new EventReport($event);
        $blocks = array_filter([
            self::participants($report, $maxParticipants),
            self::payments($report, $maxPayments),
            self::submissions($report, $maxParticipants),
        ]);

        return implode("\n\n", $blocks);
    }

    private static function participants(EventReport $report, int $max): string
    {
        // participantRows : [0]=nom [1]=prénom [2]=email [3]=tél [4]=institution
        // [5]=pays [6]=rôle [7]=grade [8]=payé(Oui/Non) [9]=montant [10]=invité
        $rows = $report->participantRows();
        if ($rows === []) {
            return '';
        }

        $total = count($rows);
        $lines = ['ANNUAIRE DES PARTICIPANTS (nominatif - '.$total.') : « Prénom Nom <e-mail> - grade - rôle - institution - état »'];
        foreach (array_slice($rows, 0, $max) as $r) {
            $name = trim(((string) $r[1]).' '.((string) $r[0]));
            $meta = array_filter([
                ((string) $r[2]) !== '' ? '<'.$r[2].'>' : null,
                ((string) $r[7]) !== '' ? (string) $r[7] : null,
                (string) $r[6],
                ((string) $r[4]) !== '' ? (string) $r[4] : null,
                $r[8] === 'Oui' ? 'payé' : 'à régler',
            ]);
            $lines[] = '- '.($name !== '' ? $name : 'Sans nom').' : '.implode(' - ', $meta);
        }
        if ($total > $max) {
            $lines[] = '… ('.($total - $max).' autre(s) non listé(s) - utiliser les filtres/export de la page Participants)';
        }

        return implode("\n", $lines);
    }

    private static function payments(EventReport $report, int $max): string
    {
        // paymentRows : [0]=reçu [1]=participant [2]=email [3]=montant [4]=devise
        // [5]=passerelle [6]=méthode [7]=statut [8]=date
        $rows = $report->paymentRows();
        if ($rows === []) {
            return '';
        }

        $total = count($rows);
        $lines = ['PAIEMENTS (nominatif, les plus récents - '.$total.') : « reçu - Payeur <e-mail> - montant devise - méthode - statut - date »'];
        foreach (array_slice($rows, 0, $max) as $r) {
            $payer = (string) $r[1];
            $meta = array_filter([
                ((string) $r[0]) !== '' ? (string) $r[0] : null,
                $payer !== '' ? $payer.(((string) $r[2]) !== '' ? ' <'.$r[2].'>' : '') : null,
                number_format((float) $r[3], 0, ',', ' ').' '.((string) $r[4]),
                (string) $r[6],
                (string) $r[7],
                ((string) $r[8]) !== '' ? (string) $r[8] : null,
            ]);
            $lines[] = '- '.implode(' - ', $meta);
        }
        if ($total > $max) {
            $lines[] = '… ('.($total - $max).' autre(s) non listé(s) - utiliser les filtres/export de la page Paiements)';
        }

        return implode("\n", $lines);
    }

    private static function submissions(EventReport $report, int $max): string
    {
        // submissionRows : [0]=réf [1]=titre [2]=auteur corresp. [3]=email [4]=axe
        // [5]=type [6]=statut [7]=note [8]=version
        $rows = $report->submissionRows();
        if ($rows === []) {
            return '';
        }

        $total = count($rows);
        $lines = ['COMMUNICATIONS & AUTEURS CORRESPONDANTS (nominatif - '.$total.') : « réf - « titre » - Auteur <e-mail> - axe - statut »'];
        foreach (array_slice($rows, 0, $max) as $r) {
            $meta = array_filter([
                ((string) $r[0]) !== '' ? (string) $r[0] : null,
                '« '.Str::limit((string) $r[1], 70).' »',
                ((string) $r[2]) !== '' ? ((string) $r[2]).(((string) $r[3]) !== '' ? ' <'.$r[3].'>' : '') : null,
                ((string) $r[4]) !== '' ? (string) $r[4] : null,
                (string) $r[6],
            ]);
            $lines[] = '- '.implode(' - ', $meta);
        }
        if ($total > $max) {
            $lines[] = '… ('.($total - $max).' autre(s) non listée(s) - utiliser les filtres/export de la page Soumissions)';
        }

        return implode("\n", $lines);
    }
}
