<?php

namespace Database\Seeders;

use App\Models\Edition;
use App\Support\Settings;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Peuple l'édition vitrine « edition-2026 » avec tout le contenu attendu par la
 * page publique du colloque (chiffres, dates, ateliers, programme, intervenants,
 * comités, tarifs, infos pratiques, partenaires, FAQ, éditions précédentes) pour
 * que TOUTES les sections du mockup s'affichent, avec les valeurs exactes du
 * mockup « page-colloque-amelioree.html ». Idempotent sur les collections liées.
 */
class ColloqueShowcaseSeeder extends Seeder
{
    public function run(): void
    {
        $edition = Edition::where('slug', 'edition-2026')->first()
            ?? Edition::where('status', 'open')->latest('id')->first();

        if (! $edition) {
            $this->command?->warn('Aucune édition « edition-2026 » ou ouverte trouvée.');

            return;
        }

        $this->seedBranding();
        $this->seedSiteSocial();
        $this->seedCore($edition);
        $this->seedSettings($edition);
        $this->seedCommittees($edition);
        $this->seedProgramme($edition);
        $this->seedSibling($edition);

        $this->command?->info("Contenu vitrine ajouté à l'édition #{$edition->id} ({$edition->title}).");
    }

    /** Logo officiel de l'Université de Kara : copie sur le disque public + réglage couverture livret. */
    private function seedBranding(): void
    {
        $source = public_path('img/logo-uk.png');
        if (! is_file($source)) {
            return;
        }
        $dest = 'branding/logo-uk.png';
        if (! Storage::disk('public')->exists($dest)) {
            Storage::disk('public')->put($dest, (string) file_get_contents($source));
        }
        if (! Settings::get('livret.cover_logo')) {
            Settings::set('livret.cover_logo', $dest);
        }
    }

    /** Réseaux sociaux au niveau site (source de repli pour les footers de colloque). */
    private function seedSiteSocial(): void
    {
        foreach ([
            'site.facebook' => 'https://www.facebook.com/univkara',
            'site.x' => 'https://x.com/univkara',
            'site.youtube' => 'https://www.youtube.com/@univkara',
            'site.linkedin' => 'https://www.linkedin.com/school/univkara',
        ] as $key => $url) {
            if (! Settings::get($key)) {
                Settings::set($key, $url);
            }
        }
    }

    /** Champs de cœur alignés sur le mockup (titre, thème, lieu, dates, deadline). */
    private function seedCore(Edition $edition): void
    {
        $edition->update([
            'title' => 'VIIIe Colloque scientifique international conjoint Université de Parakou & Université de Kara',
            'subtitle' => 'Innover pour un avenir durable : enjeux, défis et rôles des universités africaines',
            'theme' => 'Innover pour un avenir durable : enjeux, défis et rôles des universités africaines',
            'location' => 'Campus universitaire de Parakou · Bénin',
            'date_start' => Carbon::create(2026, 9, 15),
            'date_end' => Carbon::create(2026, 9, 18),
            'submission_open' => true,
            // Deadline maintenue dans le futur proche pour que la vitrine reste dans
            // l'état « soumissions ouvertes » du mockup (compte à rebours actif).
            'submission_deadline' => now()->addDays(21)->setTime(23, 59),
            'year' => 2026,
        ]);
    }

    /** Contenu enrichi (settings JSON) fidèle au mockup. */
    private function seedSettings(Edition $edition): void
    {
        $s = $edition->settings ?? [];

        $s['languages'] = 'Français & Anglais';
        $s['short_name'] = 'UP–UK';

        $s['intro'] = 'Organisé conjointement par l’Université de Parakou (Bénin) et l’Université de Kara (Togo), '
            .'ce colloque offre un cadre de réflexion et de dialogue entre universitaires, chercheurs, étudiants, '
            .'décideurs publics et acteurs du secteur privé.';

        $s['objectives'] = [
            'Documenter les expériences innovantes d’universités africaines en matière de durabilité',
            'Identifier les obstacles limitant leur contribution au développement durable',
            'Favoriser des partenariats multi-acteurs ancrés dans les besoins territoriaux',
            'Repenser les curricula pour former aux métiers de la durabilité',
            'Renforcer la coopération inter-universitaire africaine (Agenda 2063, ODD)',
        ];

        // Chiffres clés.
        $s['stats'] = [
            ['num' => 'VIIIe', 'label' => 'Édition'],
            ['num' => '6', 'label' => 'Ateliers disciplinaires'],
            ['num' => '2', 'label' => 'Universités organisatrices'],
            ['num' => '4', 'label' => 'Jours de colloque'],
        ];

        // Ateliers disciplinaires (titre + description), rendus dans la section « Axes ».
        $s['workshops'] = [
            ['title' => 'Sciences médicales & sanitaires', 'desc' => 'Santé, sport et innovations médicinales'],
            ['title' => 'Sciences naturelles & agronomiques', 'desc' => 'Biologie, agronomie et ressources naturelles'],
            ['title' => 'Langues, lettres & SHS', 'desc' => 'Sciences sociales et humaines'],
            ['title' => 'Sciences juridiques & politiques', 'desc' => 'Droit, administration et innovations'],
            ['title' => 'Économie, gestion & entrepreneuriat', 'desc' => 'Développement économique et innovation'],
            ['title' => 'Mathématiques, physique & chimie', 'desc' => 'Sciences exactes et innovations technologiques'],
        ];
        // « tracks » (libellés courts) reste cohérent pour le reste de l'app.
        $s['tracks'] = array_map(fn ($w) => $w['title'], $s['workshops']);

        // Intervenants invités.
        $s['speakers'] = [
            ['name' => 'Pr. Afiwa Mensah', 'role' => 'Keynote inaugurale', 'affil' => 'Université de Kara', 'talk' => 'Innovation et développement durable en Afrique de l’Ouest'],
            ['name' => 'Dr. Koffi Tchalim', 'role' => 'Conférencier invité', 'affil' => 'Université de Lomé', 'talk' => 'Intelligence artificielle et agriculture de précision'],
            ['name' => 'Dr. Sena Bakou', 'role' => 'Conférencière invitée', 'affil' => 'CIRAD / IRD', 'talk' => 'One Health : approches intégrées en Afrique'],
            ['name' => 'Pr. Jean Ouattara', 'role' => 'Conférencier invité', 'affil' => 'Université Félix Houphouët-Boigny', 'talk' => 'Gouvernance et transition écologique'],
        ];

        // Frais de participation (FCFA).
        $s['fees'] = [
            ['label' => 'Doctorants & assimilés', 'amount' => 15000],
            ['label' => 'Enseignants & chercheurs', 'amount' => 35000],
            ['label' => 'Autres participants', 'amount' => 50000],
        ];
        $s['fees_note'] = 'Ateliers de formation sur l’innovation : 5 000 FCFA.';

        // Partenaires (URLs + logos identiques au mockup).
        $s['partners'] = [
            ['name' => 'Agence Universitaire de la Francophonie', 'url' => 'https://www.auf.org/', 'logo_url' => 'https://univkara.tg/wp-content/uploads/2026/01/auflogo_0-e1769130572998.jpg'],
            ['name' => 'Programme des Nations Unies pour le développement', 'url' => 'https://www.undp.org/', 'logo_url' => 'https://univkara.tg/wp-content/uploads/2026/01/pnud-e1769130617842.jpg'],
            ['name' => 'DAAD', 'url' => 'https://www.daad.de/', 'logo_url' => 'https://univkara.tg/wp-content/uploads/2026/01/daad-scholarship-e1769130678868.png'],
            ['name' => 'CAMES', 'url' => 'https://www.lecames.org/', 'logo_url' => 'https://univkara.tg/wp-content/uploads/2026/01/cameslogo_0-e1769130716354.png'],
            ['name' => 'Partenaire', 'url' => 'https://univkara.tg/', 'logo_url' => 'https://univkara.tg/wp-content/uploads/2026/01/LOGO-COULEUR-TRANSP-scaled-e1769130750648.png'],
        ];

        // Infos pratiques : 6 cartes fidèles au mockup.
        $s['practical'] = [
            'venue' => "Université de Kara — Campus Sud\nRoute de l’ex-ENI, BP 43, Kara — Togo",
            'languages' => "Français\nAnglais\nCommunications orales ou posters",
            'accommodation' => "Hôtels partenaires à tarifs préférentiels\nListe communiquée après inscription\nPossibilité de résidence universitaire (places limitées)",
            'hybrid' => 'Sessions plénières retransmises en direct. Lien de connexion envoyé aux participants distants 48 h avant.',
            'visa' => 'Les participants internationaux peuvent demander une lettre d’invitation officielle via leur espace personnel après inscription.',
            'contacts' => "colloque@univkara.tg\n(+228) 26 68 52 28",
        ];

        // Plan du campus (OpenStreetMap).
        $s['map'] = [
            'embed' => 'https://www.openstreetmap.org/export/embed.html?bbox=1.15%2C9.53%2C1.22%2C9.58&layer=mapnik&marker=9.555%2C1.185',
            'link' => 'https://www.openstreetmap.org/?mlat=9.555&mlon=1.185#map=15/9.555/1.185',
        ];

        // Contact (barre d'info).
        $s['contact'] = array_merge([
            'email' => 'colloque@univkara.tg',
            'phone' => '(+228) 26 68 52 28',
            'address' => 'Université de Kara — Campus Sud, Kara, Togo',
        ], is_array($s['contact'] ?? null) ? $s['contact'] : []);

        // Pied de page propre au colloque (barre d'info).
        $s['format'] = "Présentiel & hybride\nSessions en français";

        $s['documents'] = [
            ['label' => 'Appel à communications (PDF)', 'url' => 'https://colloque.annales-up.com/'],
            ['label' => 'Programme prévisionnel', 'url' => '#programme'],
        ];

        $s['social'] = [
            'facebook' => 'https://www.facebook.com/',
            'x' => 'https://x.com/',
            'youtube' => 'https://www.youtube.com/',
            'linkedin' => 'https://www.linkedin.com/',
        ];

        $s['faq'] = [
            ['question' => 'Comment soumettre une communication ?', 'answer' => 'Soumettez votre résumé (250 mots max., FR ou EN) via le site du colloque avant le 15 juin 2026. Structure imposée : titre, auteurs, atelier, type (orale/poster), objectifs, méthodes, résultats, mots-clés.'],
            ['question' => 'Quels modes de paiement sont acceptés ?', 'answer' => 'Paiement en ligne via FedaPay (Mobile Money MTN, Moov, Togocom et cartes Visa/Mastercard) ou paiement manuel sur place après validation.'],
            ['question' => 'Le colloque est-il hybride ?', 'answer' => 'Oui. Les sessions plénières sont retransmises en direct. Les participants distants reçoivent un lien de connexion 48 h avant le début.'],
            ['question' => 'Quand recevrai-je mon attestation ?', 'answer' => 'L’attestation est disponible dans votre espace personnel dès la fin du colloque (ou après validation de votre participation pour les auditeurs distants).'],
            ['question' => 'Puis-je obtenir une lettre d’invitation pour un visa ?', 'answer' => 'Oui. Après inscription et paiement, une lettre d’invitation officielle peut être demandée depuis votre tableau de bord.'],
            ['question' => 'Y a-t-il des tarifs préférentiels pour les étudiants ?', 'answer' => 'Oui. Le tarif étudiant est de 15 000 FCFA (justificatif de scolarité requis).'],
        ];

        $edition->update(['settings' => $s]);
    }

    private function seedCommittees(Edition $edition): void
    {
        if ($edition->committeeMembers()->exists()) {
            return;
        }

        $members = [
            ['organisation', 'Pr. Afiwa Mensah', 'Présidente', 'Université de Kara'],
            ['organisation', 'Dr. Koffi Tchalim', 'Secrétaire scientifique', 'Université de Kara'],
            ['organisation', 'Dr. Sena Bakou', 'Communications', 'Université de Kara'],
            ['organisation', 'M. Jean Ouattara', 'Logistique', 'Université de Kara'],
            ['scientifique', 'Pr. Yawovi Kuevi', 'Président', 'Université de Lomé'],
            ['scientifique', 'Pr. Mariam Adjei', 'Membre', 'University of Ghana'],
            ['scientifique', 'Dr. Paul Dossou', 'Membre', 'Université d’Abomey-Calavi'],
            ['scientifique', 'Pr. Fatou Ndiaye', 'Membre', 'UCAD Dakar'],
        ];

        foreach ($members as $i => [$role, $name, $title, $institution]) {
            $edition->committeeMembers()->create([
                'person_name' => $name,
                'person_title' => $title,
                'institution' => $institution,
                'role' => $role,
                'ordre' => $i,
            ]);
        }
    }

    private function seedProgramme(Edition $edition): void
    {
        if ($edition->sessions()->exists()) {
            return;
        }

        $day1 = Carbon::create(2026, 9, 15);
        $day2 = Carbon::create(2026, 9, 16);
        $day3 = Carbon::create(2026, 9, 17);

        // Jour 1.
        $this->session($edition, 'plenary', 'Enregistrement des participants & café de bienvenue', $day1, 8, 30, 'Hall d’accueil', 0);
        $this->session($edition, 'plenary', 'Ouverture officielle', $day1, 9, 30, 'Amphithéâtre principal', 1, 'Présidence de l’Université');
        $this->session($edition, 'plenary', 'Conférence inaugurale', $day1, 10, 30, 'Amphithéâtre principal', 2, 'Pr. Afiwa Mensah');

        // Jour 2.
        $this->session($edition, 'plenary', 'Conférence invitée', $day2, 9, 0, 'Amphithéâtre principal', 3, 'Dr. Koffi Tchalim');
        $this->session($edition, 'plenary', 'Table ronde : « Recherche et politiques publiques »', $day2, 17, 0, 'Amphithéâtre principal', 4);

        // Jour 3.
        $this->session($edition, 'plenary', 'Séance de clôture & remise des prix', $day3, 11, 0, 'Amphithéâtre principal', 5);

        // Deux ateliers disciplinaires avec sessions parallèles.
        $tracks = ['Environnement · Technologies · Santé', 'Économie · Sciences exactes · Humanités'];
        foreach ($tracks as $i => $track) {
            $atelier = $edition->ateliers()->create([
                'title' => 'Ateliers thématiques '.($i === 0 ? 'A1 – A3' : 'B1 – B3'),
                'tracks' => [$track],
                'ordre' => $i,
            ]);

            $edition->sessions()->create([
                'type' => 'atelier',
                'atelier_id' => $atelier->id,
                'label' => 'Sessions parallèles — '.$track,
                'session_day' => ($i === 0 ? $day1 : $day2)->toDateString(),
                'starts_at' => ($i === 0 ? $day1 : $day2)->copy()->setTime(14, 0),
                'ends_at' => ($i === 0 ? $day1 : $day2)->copy()->setTime(16, 0),
                'room' => 'Salles A – C',
                'moderator_name' => $i === 0 ? 'Pr. Yawovi Kuevi' : 'Dr. Paul Dossou',
                'rapporteur_name' => $i === 0 ? 'Dr. Koffi Tchalim' : 'Pr. Mariam Adjei',
                'ordre' => 10 + $i,
            ]);
        }
    }

    private function session(Edition $edition, string $type, string $label, Carbon $day, int $h, int $m, string $room, int $ordre, ?string $moderator = null): void
    {
        $edition->sessions()->create([
            'type' => $type,
            'atelier_id' => 0,
            'label' => $label,
            'session_day' => $day->toDateString(),
            'starts_at' => $day->copy()->setTime($h, $m),
            'ends_at' => $day->copy()->setTime($h + 1, $m),
            'room' => $room,
            'moderator_name' => $moderator,
            'ordre' => $ordre,
        ]);
    }

    private function seedSibling(Edition $edition): void
    {
        $colloque = $edition->colloque;
        if (! $colloque) {
            return;
        }

        $banner = 'https://staging.univkara.tg/wp-content/uploads/2026/04/site-bandeau-jpg.jpeg';

        // Trois éditions précédentes comme le mockup.
        $past = [
            [2025, 'VIIe Colloque conjoint UP–UK', 'Édition 2025 — affiche et programme'],
            [2023, 'VIe Colloque conjoint UP–UK', 'Édition 2023 — affiche et programme'],
            [2021, 'Ve Colloque conjoint UP–UK', 'Édition 2021 — affiche et programme'],
        ];

        foreach ($past as [$year, $title, $subtitle]) {
            if (Edition::where('colloque_id', $colloque->id)->where('year', $year)->exists()) {
                continue;
            }
            $start = Carbon::create($year, 11, 20);
            $colloque->editions()->create([
                'title' => $title,
                'slug' => 'edition-'.$year.'-'.Str::random(5),
                'year' => $year,
                'subtitle' => $subtitle,
                'theme' => 'Recherche et innovation au service du développement',
                'location' => 'Campus de l’Université de Kara, Togo',
                'status' => 'archived',
                'date_start' => $start,
                'date_end' => $start->copy()->addDays(2),
                'submission_open' => false,
                'banner_url' => $banner,
                'settings' => ['tracks' => $edition->settings['tracks'] ?? []],
            ]);
        }
    }
}
