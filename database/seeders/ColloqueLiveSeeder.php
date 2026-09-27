<?php

namespace Database\Seeders;

use App\Models\Account;
use App\Models\Colloque;
use App\Models\Edition;
use App\Models\EventSession;
use App\Models\Participant;
use App\Models\Slot;
use App\Models\Submission;
use App\Support\Settings;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * Simulation d'un colloque « en déroulement » : une édition dont les dates
 * encadrent aujourd'hui (phase « en cours »), avec une séance plénière active
 * chaque jour de 8 h à 23 h (visio Jitsi en direct, publique). Alimente la page
 * publique /deroulement. Idempotent (repère l'édition par son slug).
 */
class ColloqueLiveSeeder extends Seeder
{
    public function run(): void
    {
        $start = Carbon::create(2026, 9, 3);
        $end = Carbon::create(2026, 9, 7);

        // Arrière-plans virtuels proposés par défaut (modifiables dans Réglages ·
        // Visio). Uniquement si l'admin n'en a pas encore configuré.
        if (blank(Settings::get('meetings.backgrounds'))) {
            Settings::set('meetings.backgrounds', implode("\n", [
                'https://images.unsplash.com/photo-1497366216548-37526070297c?w=640&q=60',
                'https://images.unsplash.com/photo-1562774053-701939374585?w=640&q=60',
                'https://images.unsplash.com/photo-1557683316-973673baf926?w=640&q=60',
                'https://images.unsplash.com/photo-1503676260728-1c00da094a0b?w=640&q=60',
            ]));
        }

        $colloque = Colloque::updateOrCreate(
            ['slug' => 'colloque-live-uk'],
            ['name' => 'Colloque scientifique de l’Université de Kara - Session en direct'],
        );

        $edition = Edition::updateOrCreate(
            ['slug' => 'colloque-live-2026'],
            [
                'colloque_id' => $colloque->id,
                'title' => 'Colloque UK - Édition en direct',
                'subtitle' => 'Retransmission des séances plénières',
                'theme' => 'Sciences, innovation et développement durable',
                'location' => 'Amphithéâtre principal · Université de Kara',
                'year' => 2026,
                'status' => 'open',
                'submission_open' => false,
                'date_start' => $start,
                'date_end' => $end,
                'settings' => [
                    'intro' => 'Colloque en cours : les séances plénières sont diffusées en direct chaque jour de 8 h à 23 h.',
                    'tracks' => ['Sciences de la santé', 'Sciences & technologies', 'Sciences humaines & sociales'],
                    // Modale « Informations du colloque ».
                    'organisers' => 'Université de Kara · Université de Parakou · DRI',
                    'formats' => 'Plénières, ateliers parallèles, posters, table ronde',
                    'visio' => 'LiveKit Cloud (défaut) · Jitsi self-host (option)',
                    // Modale « Annonces & indications » (temps réel).
                    'announcements' => [
                        ['when' => '09:28', 'urgent' => true, 'title' => 'Changement de salle - Atelier 2', 'text' => 'La session Gouvernance se tient en Salle C (et non Salle B).'],
                        ['when' => '09:15', 'urgent' => false, 'title' => 'Visio Atelier 3 ouverte', 'text' => 'Les auditeurs et communicateurs peuvent rejoindre la salle LiveKit.'],
                        ['when' => '08:50', 'urgent' => false, 'title' => 'Accueil badges', 'text' => 'Remise des badges au hall principal jusqu’à 10 h.'],
                        ['when' => '08:20', 'urgent' => false, 'title' => 'Rappel communicateurs', 'text' => 'Présentez-vous 10 minutes avant votre créneau auprès du modérateur.'],
                    ],
                    // Modale « Indications - salles & lieux » (plan de repérage du campus).
                    'venue_guide' => [
                        ['type' => 'Plénière', 'sessions' => 'Cérémonie d’ouverture · Conférences inaugurales · Clôture', 'room' => 'Amphi principal', 'landmark' => 'Bâtiment central · entrée hall A'],
                        ['type' => 'Table ronde', 'sessions' => 'Partenariats universitaires', 'room' => 'Amphi principal', 'landmark' => 'Même lieu que les plénières'],
                        ['type' => 'Atelier', 'sessions' => 'Atelier 1 - Environnement / Économie verte', 'room' => 'Salle A', 'landmark' => 'Aile est · 1er niveau'],
                        ['type' => 'Atelier', 'sessions' => 'Atelier 2 - Santé & société / Gouvernance', 'room' => 'Salle C', 'landmark' => 'Aile ouest · RDC (changement annoncé le cas échéant)'],
                        ['type' => 'Atelier', 'sessions' => 'Atelier 3 - Innovations pédagogiques', 'room' => 'Salle B', 'landmark' => 'Aile est · 2e niveau'],
                        ['type' => 'Posters', 'sessions' => 'Session posters · pauses café', 'room' => 'Hall posters', 'landmark' => 'Hall d’accueil · rez-de-chaussée'],
                        ['type' => 'Accueil', 'sessions' => 'Remise des badges & orientation', 'room' => 'Hall principal', 'landmark' => 'Entrée campus · stands UK Colloques'],
                        ['type' => 'Secrétariat', 'sessions' => 'Point information · assistance', 'room' => 'Bureau DRI / secrétariat', 'landmark' => 'À côté du hall principal'],
                    ],
                ],
            ],
        );

        // Une séance plénière active par jour (08:00 → 23:00), visio LiveKit en direct.
        $edition->sessions()->delete();

        $sessionsByDay = [];  // 'Y-m-d' => EventSession
        $day = $start->copy();
        $i = 0;
        while ($day->lte($end)) {
            $i++;
            $sessionsByDay[$day->toDateString()] = EventSession::create([
                'edition_id' => $edition->id,
                'type' => 'plenary',
                'label' => 'Séance plénière - Jour '.$i.' ('.$day->translatedFormat('d/m').')',
                'room' => 'Amphithéâtre principal',
                'session_day' => $day->copy()->startOfDay(),
                'starts_at' => $day->copy()->setTime(8, 0),
                'ends_at' => $day->copy()->setTime(23, 0),
                'moderator_name' => 'Comité d’organisation',
                'ordre' => $i,
                // LiveKit (visio type Teams/BBB, fidèle au mockup colloque-visio).
                // Sans identifiants LiveKit configurés, le registre retombe
                // proprement sur Jitsi : la salle reste fonctionnelle.
                'meeting_provider' => 'livekit',
                'meeting_room' => 'ukc-live-2026-j'.$i,
                'meeting_status' => 'live',
                'meeting_visibility' => 'public',
            ]);
            $day->addDay();
        }

        // Enrôle le compte de démonstration à ce colloque (visible dans son espace).
        $account = Account::where('email', 'participant@demo.tg')->first();
        if ($account) {
            $participant = Participant::firstOrCreate(
                ['edition_id' => $edition->id, 'account_id' => $account->id],
                ['first_name' => $account->first_name, 'last_name' => $account->last_name, 'email' => $account->email, 'role' => 'author', 'access_token' => Str::random(64)],
            );

            $this->seedDemoCommunications($edition, $participant, $sessionsByDay);
        }

        $this->command?->info("Colloque en direct simulé : édition #{$edition->id} ({$edition->date_start->format('d/m')}-{$edition->date_end->format('d/m/Y')}), {$i} séances plénières 8 h-23 h.");
    }

    /**
     * Trois communications du compte démo programmées les 4, 5 et 6/09/2026
     * (créneau relié à la séance plénière du jour), chacune sur un axe distinct -
     * pour tester le filtrage « sessions du jour liées à mes communications / mon axe ».
     *
     * @param  array<string, EventSession>  $sessionsByDay
     */
    private function seedDemoCommunications(Edition $edition, Participant $participant, array $sessionsByDay): void
    {
        $tracks = $edition->settings['tracks'] ?? ['Sciences de la santé', 'Sciences & technologies', 'Sciences humaines & sociales'];

        $comms = [
            ['2026-09-04', 'Télémédecine et accès aux soins en milieu rural', $tracks[0] ?? 'Sciences de la santé'],
            ['2026-09-05', 'Énergies renouvelables pour les campus universitaires', $tracks[1] ?? 'Sciences & technologies'],
            ['2026-09-06', 'Langues nationales et réussite éducative', $tracks[2] ?? 'Sciences humaines & sociales'],
        ];

        foreach ($comms as $idx => [$date, $title, $track]) {
            $session = $sessionsByDay[$date] ?? null;

            $submission = Submission::firstOrCreate(
                ['edition_id' => $edition->id, 'participant_id' => $participant->id, 'title' => $title, 'revision_of' => 0],
                [
                    'ref_code' => 'UKC-LIVE-'.($idx + 1),
                    'type' => 'oral',
                    'track' => $track,
                    'status' => 'accepted',
                    'abstract' => 'Communication de démonstration présentée lors de la séance plénière du '
                        .Carbon::parse($date)->translatedFormat('d/m/Y').', sur l’axe « '.$track.' ».',
                    'keywords' => Str::slug($track, ', '),
                    'authors' => [['name' => $participant->first_name.' '.$participant->last_name, 'affiliation' => 'Université de Kara', 'corresponding' => true]],
                ],
            );

            if ($session) {
                Slot::updateOrCreate(
                    ['edition_id' => $edition->id, 'submission_id' => $submission->id],
                    [
                        'session_id' => $session->id,
                        'session_label' => $session->label,
                        'room' => $session->room,
                        'participant_id' => $participant->id,
                        'starts_at' => Carbon::parse($date)->setTime(9, 0),
                        'ends_at' => Carbon::parse($date)->setTime(9, 20),
                        'chosen_by_participant' => true,
                    ],
                );
            }
        }
    }
}
