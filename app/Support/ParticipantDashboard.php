<?php

namespace App\Support;

use App\Enums\EventPhase;
use App\Http\Controllers\Account\SubmissionController;
use App\Models\Attestation;
use App\Models\Event;
use App\Models\EventSession;
use App\Models\Formation;
use App\Models\FormationEnrollment;
use App\Models\Participant;
use App\Models\Payment;
use App\Models\Review;
use App\Models\Slot;
use App\Models\Submission;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Assemble le tableau de bord du participant pour UN événement de contexte.
 *
 * ⚠️ **L'écran bascule tout seul selon le moment du parcours**
 * ({@see EventPhase::participantView()}) : « avant » on dépose, on corrige et on
 * règle ; « pendant » on passe en séance et on suit le programme ; « après » on
 * retire ses documents. Les composants sont les mêmes, l'ordre et le contenu
 * changent - sans quoi « 3. Profil renseigné ✓ » occupe le tiers de l'écran face
 * à une séance qui commence dans dix minutes.
 *
 * ⚠️ **Cette classe est la source unique des grandeurs du tableau de bord.** Deux
 * d'entre elles avaient déjà divergé de la page qui les détaille :
 *  - les communications dont le compte est **co-auteur** ne comptaient pas (le
 *    compteur disait « 1 » quand « Mes communications » en listait trois) ;
 *  - le prochain **passage** était calculé puis réduit à un booléen `$hasAgenda`,
 *    qui ne servait qu'à afficher un bouton de téléchargement : l'écran savait
 *    quand et où le participant présente, et ne le disait pas.
 *
 * `AgendaEntree` est la forme d'une ligne d'agenda personnel. ⚠️ Les trois
 * sources (passages, séances, formations) passent par {@see entree()} pour
 * produire **exactement** cette forme : sans cela chacune rendait une forme
 * voisine mais distincte, et le type de la collection groupée ne tenait plus -
 * les gabarits de `Collection` ne sont pas covariants.
 *
 * @phpstan-type AgendaEntree array{genre: string, role: string, debut: Carbon|null, fin: Carbon|null, titre: string, lieu: string, detail: string, ref: string, url: string|null, visio: string|null, fait: bool}
 */
class ParticipantDashboard
{
    /** Entrées d'agenda personnel au maximum (une semaine de colloque en tient 40). */
    private const AGENDA_MAX = 60;

    /** @var Collection<int, int>|null */
    private ?Collection $participantIds = null;

    /** @var Collection<int, Submission>|null */
    private ?Collection $submissions = null;

    /** @var Collection<int, Slot>|null */
    private ?Collection $passages = null;

    public function __construct(
        public User $account,
        public ?Event $event = null,
    ) {}

    // ===================================================== Le moment du parcours

    public function phase(): ?EventPhase
    {
        return $this->event?->phase();
    }

    /**
     * « avant » | « pendant » | « apres ». Sans événement de contexte (compte neuf),
     * on se place AVANT : il n'y a rien à présenter, tout à déposer.
     */
    public function vue(): string
    {
        return $this->phase()?->participantView() ?? 'avant';
    }

    /**
     * « Jour 2 sur 5 » pendant le déroulement, null le reste du temps.
     *
     * @return array{n: int, total: int}|null
     */
    public function jour(): ?array
    {
        if ($this->vue() !== 'pendant' || ! $this->event?->date_start || ! $this->event->date_end) {
            return null;
        }

        $debut = $this->event->date_start->copy()->startOfDay();
        $total = (int) $debut->diffInDays($this->event->date_end->copy()->startOfDay()) + 1;
        $n = (int) $debut->diffInDays(Carbon::today()) + 1;

        return ['n' => max(1, min($n, $total)), 'total' => $total];
    }

    // ===================================================== Communications

    /** @return Collection<int, int> */
    private function participantIds(): Collection
    {
        return $this->participantIds ??= Participant::where('user_id', $this->account->id)
            ->when($this->event, fn ($q) => $q->where('event_id', $this->event->id))
            ->pluck('id');
    }

    /**
     * « Mes communications » = celles que j'ai **déposées** et celles dont je suis
     * **co-auteur**.
     *
     * ⚠️ Exactement la règle de `/compte/communications`
     * ({@see SubmissionController::index()}) : le
     * tableau de bord n'en avait qu'une moitié, et annonçait donc un chiffre plus
     * petit que la page qu'il résume.
     *
     * @return Collection<int, Submission>
     */
    public function communications(): Collection
    {
        return $this->submissions ??= Submission::query()
            ->canonical()
            ->with(['event', 'authorLinks'])
            ->where(function ($q): void {
                $q->whereHas('participant', fn ($p) => $p->where('user_id', $this->account->id))
                    ->orWhereHas(
                        'authorLinks',
                        fn ($l) => $l->where('user_id', $this->account->id)->where('status', 'linked')
                    );
            })
            ->when($this->event, fn ($q) => $q->where('event_id', $this->event->id))
            ->latest('id')
            ->get();
    }

    /**
     * Je porte cette communication : je l'ai déposée, ou j'en suis l'auteur
     * correspondant. Sinon, je n'y suis que co-auteur.
     *
     * ⚠️⚠️ **ELLE NE DEMANDAIT QUE « SUIS-JE LE DÉPOSANT ».** Un auteur
     * correspondant dont la communication a été déposée par quelqu'un d'autre -
     * un secrétariat, un collègue - s'affichait donc en **co-auteur de sa propre
     * communication** : « 0 déposée - 1 en co-auteur » sur le tableau de bord, et
     * « Vous co-présentez » sur son propre passage.
     */
    public function estCorrespondant(Submission $submission): bool
    {
        return $submission->isEditableBy($this->account);
    }

    /** Je l'ai déposée moi-même (distinct de « j'en suis l'auteur correspondant »). */
    public function aiDepose(Submission $submission): bool
    {
        return $submission->wasDepositedBy($this->account);
    }

    /** @return Collection<int, Submission> */
    public function acceptees(): Collection
    {
        return $this->communications()->filter(fn (Submission $s) => $s->isAcceptedCanonical());
    }

    // ===================================================== Passages (créneaux datés)

    /**
     * Créneaux de passage DATÉS du compte sur l'événement de contexte, du plus
     * proche au plus lointain.
     *
     * ⚠️ **Les communications déposées ET celles dont le compte est co-auteur.**
     * Un co-auteur ne présente pas seul, mais il **co-présente** : il est en salle
     * à l'heure dite, et c'est précisément ce qu'il vient chercher ici. L'écarter
     * reviendrait à ne rien lui dire du moment où sa communication passe. Son rôle
     * est en revanche NOMMÉ partout où le créneau s'affiche
     * ({@see roleSur()}) - « Je présente » pour l'auteur correspondant,
     * « Je co-présente » pour le co-auteur.
     *
     * @return Collection<int, Slot>
     */
    public function passages(): Collection
    {
        if ($this->passages !== null) {
            return $this->passages;
        }

        $mesCommunications = $this->communications()->pluck('id');

        return $this->passages = Slot::query()
            ->whereNotNull('starts_at')
            ->when($this->event, fn ($q) => $q->where('event_id', $this->event->id))
            ->where(fn ($q) => $q
                ->whereIn('submission_id', $mesCommunications)
                ->orWhereIn('participant_id', $this->participantIds()))
            ->with(['submission', 'session.atelier', 'event'])
            ->orderBy('starts_at')
            ->get();
    }

    /**
     * Le prochain passage : celui qui n'est pas encore terminé. À défaut - tout est
     * passé - le dernier, pour que « après » puisse dire ce qui a eu lieu.
     */
    public function prochainPassage(): ?Slot
    {
        $now = Carbon::now();

        return $this->passages()
            ->first(fn (Slot $s) => ($s->ends_at ?? $s->starts_at)?->gte($now) ?? false)
            ?? $this->passages()->last();
    }

    /**
     * Mon rôle sur un créneau : `correspondant` (je présente) ou `coauteur` (je
     * co-présente). ⚠️ Le créneau porte le `participant_id` du DÉPOSANT, jamais
     * celui du co-auteur : le rôle se lit donc sur la communication, pas sur le
     * créneau - sinon tout le monde serait co-auteur de son propre passage.
     */
    public function roleSur(Slot $slot): string
    {
        $submission = $slot->submission;

        // Créneau rattaché directement au participant, sans communication : il
        // est à lui, pas à quelqu'un dont il serait co-auteur.
        if (! $submission) {
            return 'correspondant';
        }

        return $this->estCorrespondant($submission) ? 'correspondant' : 'coauteur';
    }

    // ===================================================== Agenda personnel

    /**
     * Programme PERSONNEL, jour par jour : ses passages, les séances qui le
     * concernent et ses formations. **Pas le programme du colloque**, qui a sa page.
     *
     * ⚠️ « Les séances qui le concernent » = celles où il présente **et** les
     * plénières (cérémonies, tables rondes : elles concernent tout le monde). On
     * n'y verse pas toutes les séances de ses axes : six salles en parallèle sur
     * cinq jours rendraient la liste aussi illisible que le programme complet.
     *
     * ⚠️ **Un tableau, pas une `Collection`.** Le gabarit `TValue` de `Collection`
     * est INVARIANT : une collection dont la valeur porte une forme précise n'est
     * jamais acceptée comme telle par l'analyse, même quand le type attendu et le
     * type rendu s'écrivent caractère pour caractère à l'identique. Les tableaux,
     * eux, sont covariants - et la vue ne fait que parcourir.
     *
     * @return array<string, array<int, AgendaEntree>>
     */
    public function agenda(): array
    {
        if (! $this->event) {
            return [];
        }

        $event = $this->event;

        // ⚠️ Un tableau PHP, converti en collection à la toute fin : `groupBy` sur
        // une `collect()` vide rend une collection dont la valeur est `mixed`, et
        // le type de retour annoncé ne tient plus.
        $entrees = [];

        foreach ($this->passages() as $slot) {
            $entrees[] = self::entree(
                genre: 'passage',
                debut: $slot->starts_at,
                fin: $slot->ends_at,
                titre: (string) ($slot->submission?->title ?: __('Ma communication')),
                role: $this->roleSur($slot),
                lieu: (string) ($slot->room ?: $slot->session?->room ?: ''),
                detail: trim((string) ($slot->session?->label ?: $slot->session_label)),
                // ⚠️ `data_get`, pas `?->… ?? ''` : Larastan type les relations
                // `BelongsTo` comme NON nulles, donc il voit un `?->` inutile là
                // où la relation est bel et bien nullable au runtime (créneau
                // libre, sans communication).
                ref: (string) data_get($slot, 'submission.ref_code', ''),
                url: $slot->submission
                    ? route('account.submissions.show', $slot->submission)
                    : null,
                visio: $slot->session && $slot->session->isLive()
                    ? route('session.meeting', $slot->session)
                    : null,
                fait: $slot->wasPresented(),
            );
        }

        /** @var Collection<int, EventSession> $seances */
        // ⚠️ Les plénières SEULEMENT. La séance qui contient un de mes passages
        // était listée en plus du passage : à 11h30 le participant lisait quatre
        // lignes pour deux choses, et la seconde ne disait rien que la première
        // ne dise déjà (la ligne du passage nomme sa séance et sa salle). Son
        // horaire de fin complet reste sur « Programme complet ».
        $seances = EventSession::where('event_id', $event->id)
            ->whereNotNull('starts_at')
            ->where('type', 'plenary')
            ->with('atelier')
            ->orderBy('starts_at')
            ->get();

        foreach ($seances as $seance) {
            $entrees[] = self::entree(
                genre: $seance->type === 'plenary' ? 'pleniere' : 'seance',
                debut: $seance->starts_at,
                fin: $seance->ends_at,
                titre: (string) ($seance->atelier?->title ?: $seance->label ?: __('Séance')),
                lieu: (string) $seance->room,
                detail: $seance->type === 'plenary' ? __('Séance plénière') : __('Ma séance'),
                visio: $seance->isLive() && $seance->canJoin($this->account)
                    ? route('session.meeting', $seance)
                    : null,
            );
        }

        foreach ($this->formations() as $inscription) {
            $formation = $inscription->formation;
            if (! $formation || ! $formation->starts_at) {
                continue;
            }
            $entrees[] = self::entree(
                genre: 'formation',
                debut: $formation->starts_at,
                fin: $formation->ends_at,
                titre: (string) $formation->title,
                lieu: (string) $formation->room,
                detail: $inscription->isPaid() ? __('Formation - inscription réglée') : __('Formation - à régler'),
                url: route('account.formations'),
                visio: $formation->isLive()
                    ? route('account.formations.visio', $formation)
                    : null,
            );
        }

        return collect($entrees)
            ->sortBy(fn (array $e) => optional($e['debut'])->timestamp ?? PHP_INT_MAX)
            ->take(self::AGENDA_MAX)
            ->groupBy(fn (array $e) => optional($e['debut'])->format('Y-m-d') ?? 'sans-date')
            ->map(fn (Collection $jour) => $jour->values()->all())
            ->all();
    }

    /**
     * Fabrique d'une ligne d'agenda : **le seul endroit** qui en construit la
     * forme, pour que les trois sources (passage, séance, formation) rendent
     * strictement le même tableau.
     *
     * @return AgendaEntree
     */
    private static function entree(
        string $genre,
        ?Carbon $debut,
        ?Carbon $fin,
        string $titre,
        string $role = '',
        string $lieu = '',
        string $detail = '',
        string $ref = '',
        ?string $url = null,
        ?string $visio = null,
        bool $fait = false,
    ): array {
        return [
            'genre' => $genre,
            'role' => $role,
            'debut' => $debut,
            'fin' => $fin,
            'titre' => $titre,
            'lieu' => $lieu,
            'detail' => $detail,
            'ref' => $ref,
            'url' => $url,
            'visio' => $visio,
            'fait' => $fait,
        ];
    }

    /**
     * Le jour d'agenda à ouvrir : aujourd'hui s'il y a quelque chose, sinon le
     * prochain jour qui porte quelque chose, sinon le premier.
     */
    public function jourOuvert(): ?string
    {
        $jours = array_keys($this->agenda());
        if ($jours === []) {
            return null;
        }

        $aujourdhui = Carbon::today()->format('Y-m-d');

        foreach ($jours as $jour) {
            if ($jour >= $aujourdhui) {
                return $jour;
            }
        }

        return end($jours);
    }

    // ===================================================== Formations, séances en direct

    /** @return Collection<int, FormationEnrollment> */
    public function formations(): Collection
    {
        $event = $this->event;
        if (! $event || ! Formation::offeredIn($event)) {
            return collect();
        }

        return FormationEnrollment::where('user_id', $this->account->id)
            ->whereHas('formation', fn ($f) => $f->where('event_id', $event->id))
            ->with('formation')
            ->get();
    }

    /**
     * Séances EN COURS maintenant qui le concernent : il y présente, ou la séance
     * porte sur l'un de ses axes.
     *
     * @return Collection<int, EventSession>
     */
    public function seancesEnDirect(): Collection
    {
        $event = $this->event;
        if (! $event) {
            return collect();
        }

        $mesSeances = $this->passages()->pluck('session_id')->filter()->unique();
        $mesAxes = $this->communications()
            ->pluck('track')->filter()
            ->map(fn ($t) => mb_strtolower(trim((string) $t)))->unique();
        $now = Carbon::now();

        return EventSession::query()->live()
            ->where('event_id', $event->id)
            ->with(['event', 'atelier'])
            ->get()
            ->filter(function (EventSession $s) use ($now, $mesSeances, $mesAxes) {
                $enCours = $s->starts_at && $s->ends_at
                    && $s->starts_at->lte($now) && $s->ends_at->gte($now);
                if (! $enCours || ! $s->canJoin($this->account)) {
                    return false;
                }
                if ($mesSeances->contains($s->id)) {
                    return true;
                }
                $axes = collect(data_get($s, 'atelier.tracks', []))
                    ->map(fn ($t) => mb_strtolower(trim((string) $t)));

                return $axes->intersect($mesAxes)->isNotEmpty();
            })
            ->values();
    }

    // ===================================================== Argent & documents

    /** Paiement de PARTICIPATION réglé (jamais une formation : cf. `forParticipation`). */
    public function paiement(): ?Payment
    {
        return Payment::whereIn('participant_id', $this->participantIds())
            ->forParticipation()->where('status', 'paid')
            ->latest('id')->first();
    }

    /** Événement dont les frais restent dus (communication acceptée, rien de réglé). */
    public function fraisDus(): ?Event
    {
        // ⚠️ **Sous-requête, pas `whereHas`** : dans la fermeture d'un `whereHas`
        // le builder est générique et le scope du modèle n'y est pas visible - ni
        // pour l'analyse statique, ni pour qui relit. La règle « les
        // communications que l'événement présente » reste ainsi nommée une seule
        // fois (`Submission::scopeAcceptedCanonical`), au lieu d'être recopiée.
        $participant = Participant::whereIn('id', $this->participantIds())
            ->whereIn('id', Submission::query()->acceptedCanonical()->select('participant_id'))
            ->whereDoesntHave('participationPayments', fn ($q) => $q->where('status', 'paid'))
            ->with('event')
            ->first();

        return $participant?->event;
    }

    /** @return Collection<int, Attestation> */
    public function attestations(): Collection
    {
        return Attestation::whereIn('participant_id', $this->participantIds())
            ->where('revoked', false)
            ->with(['event', 'formation'])
            ->orderBy('id')
            ->get();
    }

    /**
     * Pastilles de « Mes documents » : reçu, badge, attestations, livret - un seul
     * rang, au lieu de trois cartes en fin de page.
     *
     * @return list<array{libelle: string, detail: string, url: string, icone: string, format: string}>
     */
    public function documents(): array
    {
        $out = [];

        if ($paiement = $this->paiement()) {
            $out[] = [
                'libelle' => __('Reçu :no', ['no' => $paiement->receipt_no ?: __('de paiement')]),
                'detail' => number_format((float) $paiement->amount, 0, ',', ' ').' '.$paiement->currency,
                'url' => route('account.receipt', $paiement),
                'icone' => 'receipt',
                'format' => 'PDF',
            ];
        }

        // Une personne peut porter plusieurs fonctions et donc plusieurs badges.
        // La pastille pointe vers leur aperçu, y compris avant paiement lorsque
        // les badges de fonction sont déjà disponibles.
        $event = $this->event;
        $participant = $event
            ? Participant::whereIn('id', $this->participantIds())->first()
            : null;
        if ($event !== null && $participant !== null) {
            $badgeCount = BadgeCatalog::forAccount($event, $this->account, $participant)->count();
            if ($badgeCount > 0) {
                $out[] = [
                    'libelle' => $badgeCount === 1 ? __('Mon badge') : __('Mes badges'),
                    'detail' => __('À présenter à l’accueil'),
                    'url' => route('account.documents').'#mon-badge',
                    'icone' => 'card',
                    'format' => __('Voir'),
                ];
            }
        }

        foreach ($this->attestations() as $attestation) {
            $out[] = [
                'libelle' => $attestation->typeLabel(),
                'detail' => (string) ($attestation->formationTitle() ?: $attestation->event?->year ?: ''),
                'url' => route('attestation.document', $attestation->uuid),
                'icone' => 'diploma',
                'format' => 'PDF',
            ];
        }

        if ($this->event && $this->event->submissions()->acceptedCanonical()->exists()) {
            $out[] = [
                // ⚠️ « Livret », comme « Mes documents » (`account/documents`) :
                // le back-office dit « Livre des résumés », l'espace participant
                // « Livret ». On ne change pas le vocabulaire d'un espace au
                // passage d'une refonte de mise en page.
                'libelle' => __('Livret des résumés'),
                'detail' => (string) ($this->event->title ?: $this->event->year),
                'url' => route('account.documents.livret', $this->event),
                'icone' => 'book',
                'format' => 'PDF',
            ];
        }

        return $out;
    }

    // ===================================================== Indicateurs

    /**
     * Quatre indicateurs, **tous chiffrés**, dont le contenu suit le moment du
     * parcours.
     *
     * ⚠️ Deux des quatre cartes affichaient une coche (« Profil complet ✓ ») ou des
     * points de suspension. Une **tâche n'est pas une grandeur** : elle est passée
     * dans « À faire », où elle est cliquable. Une rangée d'indicateurs doit se lire
     * d'un coup d'œil, et une coche ne se compare à rien.
     *
     * @return list<array{libelle: string, valeur: string, unite: string, detail: string, icone: string, ton: string}>
     */
    public function indicateurs(): array
    {
        // ⚠️⚠️ **TROIS POSITIONS, PAS DEUX.** Le détail opposait « déposée » à
        // « en co-auteur » : l'auteur correspondant dont quelqu'un d'autre a
        // déposé la communication n'entrait dans aucune des deux, et tombait
        // donc en co-auteur. On ne montre que les parts non nulles.
        $communications = $this->communications();
        $deposees = $communications->filter(fn (Submission $s) => $this->aiDepose($s))->count();
        $correspondant = $communications->filter(
            fn (Submission $s) => ! $this->aiDepose($s) && $this->estCorrespondant($s)
        )->count();
        $coauteur = $communications->count() - $deposees - $correspondant;

        $parts = [];
        if ($deposees > 0) {
            $parts[] = trans_choice(':count déposée|:count déposées', $deposees, ['count' => $deposees]);
        }
        if ($correspondant > 0) {
            $parts[] = trans_choice(':count en auteur correspondant|:count en auteur correspondant', $correspondant, ['count' => $correspondant]);
        }
        if ($coauteur > 0) {
            $parts[] = trans_choice(':count en co-auteur|:count en co-auteur', $coauteur, ['count' => $coauteur]);
        }

        $carteCommunications = [
            'libelle' => __('Mes communications'),
            'valeur' => (string) $communications->count(),
            'unite' => '',
            'detail' => $parts === []
                ? trans_choice(':count déposée|:count déposées', 0, ['count' => 0])
                : implode(' - ', $parts),
            'icone' => 'file',
            'ton' => 'blue',
        ];

        $documents = count($this->documents());
        $carteDocuments = [
            'libelle' => __('Mes documents'),
            'valeur' => (string) $documents,
            'unite' => '',
            // ⚠️ « À retirer », pas « à télécharger » : le badge se montre à
            // l'accueil, il ne se télécharge pas. Même mot que la carte haute
            // de l'après-colloque (« ce qu'il vous reste à retirer »).
            'detail' => $documents > 0 ? __('Prêts à retirer') : __('Rien à retirer pour l’instant'),
            'icone' => 'folder',
            'ton' => 'amber',
        ];

        $paiement = $this->paiement();
        $cartePaiement = $paiement
            ? [
                'libelle' => __('Frais d’inscription'),
                'valeur' => number_format((float) $paiement->amount, 0, ',', ' '),
                'unite' => (string) $paiement->currency,
                'detail' => __('Réglés').($paiement->receipt_no ? ' - '.__('reçu :no', ['no' => $paiement->receipt_no]) : ''),
                'icone' => 'card',
                'ton' => 'green',
            ]
            : [
                'libelle' => __('Frais d’inscription'),
                'valeur' => $this->fraisDus() ? __('À régler') : '-',
                'unite' => '',
                'detail' => $this->fraisDus() ? __('Dus après acceptation') : __('Rien à régler aujourd’hui'),
                'icone' => 'card',
                'ton' => $this->fraisDus() ? 'amber' : 'slate',
            ];

        if ($this->vue() === 'avant') {
            $echeance = $this->event?->submission_deadline;
            $jours = $echeance && $echeance->isFuture()
                ? (int) ceil(Carbon::now()->diffInDays($echeance, false))
                : null;
            $manquants = $this->account->missingProfileFields();

            return [
                $carteCommunications,
                [
                    'libelle' => __('Avant la clôture'),
                    'valeur' => $jours !== null ? (string) $jours : '-',
                    'unite' => $jours !== null ? trans_choice('jour|jours', $jours) : '',
                    'detail' => $echeance
                        ? $echeance->translatedFormat('l j F Y')
                        : __('Aucune échéance annoncée'),
                    'icone' => 'clock',
                    'ton' => 'amber',
                ],
                [
                    'libelle' => __('Mon dossier'),
                    'valeur' => (string) (5 - count($manquants)),
                    'unite' => '/ 5',
                    'detail' => $manquants === []
                        ? __('Complet')
                        : __(':champs à renseigner', ['champs' => implode(', ', $manquants)]),
                    'icone' => 'user',
                    'ton' => $manquants === [] ? 'green' : 'slate',
                ],
                $cartePaiement,
            ];
        }

        $acceptees = $this->acceptees()->count();

        return [
            $carteCommunications,
            [
                'libelle' => __('Acceptées'),
                'valeur' => (string) $acceptees,
                'unite' => '',
                'detail' => $this->vue() === 'apres'
                    ? trans_choice(':count passage constaté|:count passages constatés', $this->passagesConstates(), ['count' => $this->passagesConstates()])
                    : trans_choice(':count passage programmé|:count passages programmés', $this->passages()->count(), ['count' => $this->passages()->count()]),
                'icone' => 'check-circle',
                'ton' => 'green',
            ],
            $cartePaiement,
            $carteDocuments,
        ];
    }

    public function passagesConstates(): int
    {
        return $this->passages()->filter(fn (Slot $s) => $s->wasPresented())->count();
    }

    // ===================================================== À faire & parcours

    /**
     * Ce qu'il RESTE à faire, chaque entrée cliquable.
     *
     * ⚠️ Le déroulé en six étapes restait affiché en entier, coches comprises,
     * jusqu'à la fin du colloque. Il renseigne **avant** (il dit ce qui vient),
     * mais pendant le déroulement il occupait le tiers de l'écran pour annoncer un
     * travail déjà fait. Ici on ne détaille que ce qui attend ; l'avancement global
     * tient dans une barre ({@see avancement()}).
     *
     * @return list<array{titre: string, detail: string, url: string, ton: string}>
     */
    public function aFaire(): array
    {
        $out = [];

        if (! $this->account->hasVerifiedEmail()) {
            $out[] = [
                'titre' => __('Confirmer mon adresse e-mail'),
                'detail' => __('Sans elle, impossible de déposer une communication.'),
                'url' => route('account.verification.notice'),
                'ton' => 'warn',
            ];
        }

        $manquants = array_values(array_diff($this->account->missingProfileFields(), ['E-mail vérifié']));
        if ($manquants !== []) {
            $out[] = [
                'titre' => __('Compléter mon profil : :champs', ['champs' => implode(', ', $manquants)]),
                'detail' => __('Ces informations figurent sur votre attestation et dans le livre des résumés.'),
                'url' => route('account.profile'),
                'ton' => 'warn',
            ];
        }

        if ($dus = $this->fraisDus()) {
            $out[] = [
                'titre' => __('Régler mes frais d’inscription'),
                'detail' => __('Votre communication est acceptée pour « :event ».', ['event' => $dus->title]),
                'url' => route('account.payment', $dus),
                'ton' => 'warn',
            ];
        }

        foreach ($this->formations() as $inscription) {
            $formation = $inscription->formation;
            if (! $inscription->isPaid() && $formation && (float) $formation->fee > 0) {
                $out[] = [
                    'titre' => __('Régler ma formation'),
                    'detail' => (string) $formation->title,
                    'url' => route('account.formations'),
                    'ton' => 'warn',
                ];
            }
        }

        $pendingReviews = $this->relecturesEnAttente();
        if ($pendingReviews > 0) {
            $out[] = [
                'titre' => trans_choice(':count évaluation à rendre|:count évaluations à rendre', $pendingReviews, ['count' => $pendingReviews]),
                'detail' => __('Le comité attend votre évaluation.'),
                'url' => route('account.reviews'),
                'ton' => 'info',
            ];
        }

        if ($this->vue() === 'apres' && $this->acceptees()->isNotEmpty()) {
            $out[] = [
                'titre' => __('Laisser un témoignage'),
                'detail' => __('Votre retour peut figurer sur la page d’accueil.'),
                'url' => route('account.testimonials'),
                'ton' => 'info',
            ];
        }

        return $out;
    }

    /**
     * ⚠️ « À traiter » = invitée ou acceptée ({@see Review::PENDING_STATUSES}).
     * Un `status != completed` compterait aussi les évaluations refusées ou
     * annulées : l’évaluateur garderait un badge pour un travail qu'il n'a plus
     * à faire.
     */
    public function relecturesEnAttente(): int
    {
        $ids = Participant::where('user_id', $this->account->id)
            ->where('role', 'reviewer')->pluck('id');

        if ($ids->isEmpty()) {
            return 0;
        }

        return Review::whereIn('reviewer_id', $ids)
            ->whereIn('status', Review::PENDING_STATUSES)->count();
    }

    /**
     * Le parcours complet, de la création du compte à l'attestation. Affiché en
     * entier **avant** le colloque, où il renseigne.
     *
     * @return list<array{titre: string, detail: string, etat: string}>
     */
    public function etapes(): array
    {
        $communications = $this->communications();
        $deposee = $communications->isNotEmpty();
        $tranchee = $communications->contains(
            fn (Submission $s) => in_array($s->status, ['accepted', 'rejected'], true)
        );
        $paye = $this->paiement() !== null;
        $passe = $this->passagesConstates() > 0;
        $verifie = $this->account->hasVerifiedEmail();
        $profil = array_diff($this->account->missingProfileFields(), ['E-mail vérifié']) === [];

        return [
            ['titre' => __('Compte créé'), 'detail' => '', 'etat' => 'fait'],
            [
                'titre' => __('E-mail vérifié'),
                'detail' => '',
                'etat' => $verifie ? 'fait' : 'encours',
            ],
            [
                'titre' => __('Profil renseigné'),
                'detail' => '',
                'etat' => $profil ? 'fait' : ($verifie ? 'encours' : 'attente'),
            ],
            [
                'titre' => __('Résumé déposé'),
                'detail' => $deposee ? (string) data_get($communications->first(), 'ref_code', '') : '',
                'etat' => $deposee ? 'fait' : ($verifie && $profil ? 'encours' : 'attente'),
            ],
            [
                'titre' => __('Notification d’acceptation'),
                'detail' => '',
                'etat' => $tranchee ? 'fait' : ($deposee ? 'encours' : 'attente'),
            ],
            [
                'titre' => __('Inscription & paiement'),
                'detail' => '',
                'etat' => $paye ? 'fait' : ($this->fraisDus() ? 'encours' : 'attente'),
            ],
            [
                'titre' => __('Passage en séance & attestation'),
                'detail' => '',
                'etat' => $passe ? 'fait' : ($paye ? 'encours' : 'attente'),
            ],
        ];
    }

    /**
     * Étapes franchies / total, pour la barre d'avancement.
     *
     * @return array{faites: int, total: int}
     */
    public function avancement(): array
    {
        $etapes = $this->etapes();
        $faites = count(array_filter($etapes, fn (array $e) => $e['etat'] === 'fait'));

        return ['faites' => $faites, 'total' => count($etapes)];
    }
}
