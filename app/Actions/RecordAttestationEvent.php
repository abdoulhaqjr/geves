<?php

namespace App\Actions;

use App\Models\Attestation;
use App\Models\AttestationEvent;
use App\Models\Participant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * **Constate un geste posé sur une attestation délivrée** et tient sa synthèse.
 *
 * ⚠️ Une seule porte pour les quatre endroits qui constatent (page du document,
 * PDF, partage, LinkedIn) : chacun en tirerait sinon sa propre idée de ce qui
 * compte, et les compteurs de la liste ne voudraient plus rien dire.
 */
class RecordAttestationEvent
{
    /**
     * Fenêtre de dédoublonnage, en minutes, par geste.
     *
     * ⚠️ **Sans elle, un F5 vaut une ouverture.** Une personne qui relit son
     * attestation trois fois dans l'après-midi n'a pas reçu trois attestations,
     * et le gestionnaire lirait « 12 ouvertures » là où une seule personne est
     * passée. Les gestes volontaires (partage, copie, LinkedIn) ont une fenêtre
     * courte : elle neutralise le double-clic sans masquer un vrai second
     * partage, dix minutes plus tard, à une autre personne.
     */
    private const FENETRES = ['view' => 30, 'download' => 30, 'share' => 1, 'copy' => 1, 'linkedin' => 1];

    /** Gestes qui valent « l'attestation a été ouverte ». */
    private const CONSULTATIONS = ['view', 'download'];

    /** Gestes qui valent « l'attestation a été partagée ». */
    private const PARTAGES = ['share', 'copy'];

    /**
     * Agents qui lisent la page sans que personne ne la regarde.
     *
     * ⚠️ **Le cas le plus courant n'est pas un moteur de recherche** : c'est le
     * robot d'aperçu de WhatsApp, de LinkedIn ou de Slack. Le titulaire colle le
     * lien de son attestation dans une conversation, le robot va chercher le
     * titre et l'image - et l'attestation passerait pour « ouverte » avant que
     * le destinataire ait seulement vu le message. Le gestionnaire lirait
     * « ouverte » pour une attestation que personne n'a lue, ce qui est pire que
     * de ne rien afficher.
     */
    private const ROBOTS = [
        'bot', 'crawler', 'spider', 'preview', 'facebookexternalhit', 'whatsapp',
        'slackbot', 'telegram', 'discord', 'embedly', 'quora link preview',
        'curl/', 'wget', 'python-requests', 'go-http-client', 'headless',
    ];

    public function __invoke(Attestation $attestation, string $kind, Request $request): ?AttestationEvent
    {
        if (! array_key_exists($kind, AttestationEvent::KINDS)) {
            return null;
        }

        if ($this->estUnRobot($request)) {
            return null;
        }

        $acteur = $this->acteur($attestation);
        $empreinte = $this->empreinte($request);

        if ($this->dejaConstate($attestation, $kind, $empreinte)) {
            return null;
        }

        $evenement = AttestationEvent::create([
            'attestation_id' => $attestation->id,
            'kind' => $kind,
            'actor' => $acteur,
            'fingerprint' => $empreinte,
            'referer_host' => $this->hote($request),
            'occurred_at' => now(),
        ]);

        // ⚠️ **Le staff ne compte pas.** La liste des attestations délivrées
        // ouvre le document dans un onglet : si le clic du gestionnaire comptait,
        // toute attestation qu'il relit passerait pour « ouverte », et la colonne
        // dirait l'activité du secrétariat au lieu de celle du bénéficiaire. Le
        // geste reste au journal - c'est lui qui prouve que le compteur est
        // propre - mais il ne touche pas la synthèse.
        if ($acteur !== AttestationEvent::ACTOR_STAFF) {
            $this->cumule($attestation, $kind, $acteur);
        }

        return $evenement;
    }

    /** Lecture machine : rien n'a été ouvert par personne (cf. ROBOTS). */
    private function estUnRobot(Request $request): bool
    {
        $agent = mb_strtolower((string) $request->userAgent());

        if ($agent === '') {
            return false;
        }

        foreach (self::ROBOTS as $signature) {
            if (str_contains($agent, $signature)) {
                return true;
            }
        }

        return false;
    }

    /** Un même visiteur, un même geste, dans la fenêtre : déjà constaté. */
    private function dejaConstate(Attestation $attestation, string $kind, string $empreinte): bool
    {
        return AttestationEvent::where('attestation_id', $attestation->id)
            ->where('kind', $kind)
            ->where('fingerprint', $empreinte)
            ->where('occurred_at', '>=', now()->subMinutes(self::FENETRES[$kind] ?? 30))
            ->exists();
    }

    /** Report du geste sur la synthèse que lit la liste du back-office. */
    private function cumule(Attestation $attestation, string $kind, string $acteur): void
    {
        $maintenant = now();
        $maj = [];

        if (in_array($kind, self::CONSULTATIONS, true)) {
            $maj['first_viewed_at'] = $attestation->first_viewed_at ?? $maintenant;
            $maj['last_viewed_at'] = $maintenant;
            $maj['views_count'] = (int) $attestation->views_count + 1;

            // ⚠️ **L'accusé de réception, c'est ICI et nulle part ailleurs** : le
            // titulaire, identifié par son compte, a ouvert SON document. Une
            // ouverture anonyme ne vaut pas réception - le lien circule, et un
            // recruteur qui le suit n'est pas le bénéficiaire qui reçoit.
            if ($acteur === AttestationEvent::ACTOR_HOLDER) {
                $maj['acknowledged_at'] = $attestation->acknowledged_at ?? $maintenant;
            }
        } elseif (in_array($kind, self::PARTAGES, true)) {
            $maj['shared_at'] = $maintenant;
            $maj['shares_count'] = (int) $attestation->shares_count + 1;
        } elseif ($kind === 'linkedin') {
            // Le PREMIER départ : porter l'attestation à son profil se fait une
            // fois. Les suivants restent au journal.
            $maj['linkedin_at'] = $attestation->linkedin_at ?? $maintenant;
        }

        if ($maj !== []) {
            $attestation->forceFill($maj)->save();
        }
    }

    /**
     * Qui pose le geste.
     *
     * ⚠️ **L'impersonation est du staff**, jamais le titulaire. Le secrétariat
     * qui dépanne quelqu'un est connecté au guard `account` sous son identité :
     * sans cette garde, l'assistance fabriquerait l'accusé de réception de la
     * personne qu'elle aide.
     */
    private function acteur(Attestation $attestation): string
    {
        if (app()->bound('impersonate') && app('impersonate')->isImpersonating()) {
            return AttestationEvent::ACTOR_STAFF;
        }

        if (Auth::guard('web')->check()) {
            return AttestationEvent::ACTOR_STAFF;
        }

        $compte = Auth::guard('account')->user();

        if ($compte && $attestation->participant_id
            && Participant::where('id', $attestation->participant_id)
                ->where('user_id', $compte->getAuthIdentifier())->exists()) {
            return AttestationEvent::ACTOR_HOLDER;
        }

        return AttestationEvent::ACTOR_PUBLIC;
    }

    /**
     * Empreinte opaque du visiteur.
     *
     * ⚠️ **Aucune adresse IP n'est conservée** : un HMAC tronqué sur la clé de
     * l'application ne se renverse pas, et il suffit à ne pas compter dix fois
     * la même personne. Un journal de consultation d'un document nominatif n'a
     * pas besoin de savoir d'où l'on vient, seulement qu'on est déjà venu.
     */
    private function empreinte(Request $request): string
    {
        return substr(hash_hmac(
            'sha256',
            (string) $request->ip().'|'.(string) $request->userAgent(),
            (string) config('app.key'),
        ), 0, 16);
    }

    /** Domaine d'où vient le visiteur - jamais l'adresse complète. */
    private function hote(Request $request): ?string
    {
        $referent = (string) $request->headers->get('referer', '');

        if ($referent === '') {
            return null;
        }

        $hote = (string) (parse_url($referent, PHP_URL_HOST) ?: '');

        return $hote !== '' ? mb_substr($hote, 0, 100) : null;
    }
}
