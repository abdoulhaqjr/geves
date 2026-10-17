<?php

use App\Support\EventStorage;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * **Range les fichiers déjà stockés sous le NOM de leur événement.**
 *
 * Les pièces jointes vivaient sous l'identifiant - `submissions/2/…`,
 * `events/2/…` - et l'habillage à plat dans `events/`. Un dossier nommé `2` ne
 * dit rien, et deux événements mêlaient leurs visuels dans le même répertoire.
 * Le code écrit désormais sous `submissions/nom-de-l-evenement-annee/` et
 * `events/nom-de-l-evenement-annee/` ({@see EventStorage}) ; cette migration
 * déplace ce qui existait déjà, et réécrit dans le même mouvement les URL
 * enregistrées en base - un fichier déplacé sans son URL est un fichier perdu.
 *
 * ⚠️ **Le déplacement est piloté par la BASE, jamais par le contenu des
 * dossiers.** Il restait un `events/1/` peuplé des affiches du colloque de
 * l'eau, du temps où cet événement portait l'identifiant 1 : déplacer les
 * répertoires par leur nom aurait rangé ces affiches sous le dossier de
 * l'événement 1 d'aujourd'hui - « Innover pour un avenir durable ». Chaque
 * ligne, elle, sait de quel événement elle relève. Ce qui ne figure dans aucune
 * ligne n'est pas touché : un orphelin se constate, il ne se devine pas.
 *
 * ⚠️ **Forme d'arrivée : `<racine>/<dossier>[/camera-ready]/<fichier>`.** Les
 * segments intermédiaires sont abandonnés, parce qu'ils redisent l'événement :
 * l'import de l'eau rangeait sous `submissions/2/eau-togo-2026/`, ce qui
 * donnerait `submissions/premier-colloque-eau-togo-2026/eau-togo-2026/`. C'est
 * aussi **exactement** ce que le code écrit maintenant : sans cela, le prochain
 * passage du seeder d'import ne retrouverait pas ses 47 pièces Word et les
 * réécrirait à côté.
 *
 * ⚠️ **On ne réécrit une URL que si le fichier est VRAIMENT à destination** :
 * déplacé par nous, ou déjà en place (rejeu). Fichier introuvable des deux
 * côtés - une base dont les téléversements n'ont pas été repris, ce qui est le
 * cas courant en développement -, on laisse l'URL telle quelle : la corriger
 * ferait pointer la base vers un fichier qui n'existe nulle part.
 *
 * ⚠️ **Et on n'écrase jamais une destination occupée.** Deux fichiers de même
 * nom venus de sous-dossiers différents resteraient alors l'un sur l'autre.
 *
 * ⚠️ **Deux formes cohabitent en base, et il faut les deux** : une **URL**
 * absolue (`submissions.file_url`, `media.url`, `settings.hero_image`) et un
 * **chemin nu** (`formations.flyer_path`, `settings['livret.cover_logo']`, les
 * gabarits du livre). Ne traiter que la première laissait les secondes pointer
 * sur un fichier déplacé.
 */
return new class extends Migration
{
    public function up(): void
    {
        $dossiers = [];
        foreach (DB::table('events')->select('id', 'slug', 'year', 'title')->get() as $event) {
            $dossiers[(int) $event->id] = EventStorage::folder(
                (string) ($event->slug ?: $event->title) ?: 'evenement-'.$event->id,
                $event->year,
            );
        }

        if ($dossiers === []) {
            return;
        }

        // --- Pièces jointes des communications -----------------------------
        foreach (DB::table('submissions')->select('id', 'event_id', 'file_url', 'meta')->get() as $row) {
            $dossier = $dossiers[(int) $row->event_id] ?? null;
            if ($dossier === null) {
                continue;
            }

            $maj = [];

            $fichier = $this->range((string) $row->file_url, $dossier);
            if ($fichier !== null) {
                $maj['file_url'] = $fichier;
            }

            // La version définitive vit dans `meta.camera_ready.file_url`.
            $meta = json_decode((string) $row->meta, true);
            if (is_array($meta) && isset($meta['camera_ready']['file_url'])) {
                $definitive = $this->range((string) $meta['camera_ready']['file_url'], $dossier);
                if ($definitive !== null) {
                    $meta['camera_ready']['file_url'] = $definitive;
                    $maj['meta'] = json_encode($meta);
                }
            }

            if ($maj !== []) {
                DB::table('submissions')->where('id', $row->id)->update($maj);
            }
        }

        // --- Médias de l'événement (affiches, galerie) ----------------------
        foreach (DB::table('media')->select('id', 'event_id', 'url')->get() as $row) {
            $dossier = $dossiers[(int) $row->event_id] ?? null;
            if ($dossier === null) {
                continue;
            }

            $url = $this->range((string) $row->url, $dossier);
            if ($url !== null) {
                DB::table('media')->where('id', $row->id)->update(['url' => $url]);
            }
        }

        // --- Flyer d'une formation (chemin brut) ----------------------------
        foreach (DB::table('formations')->select('id', 'event_id', 'flyer_path')->get() as $row) {
            $dossier = $dossiers[(int) $row->event_id] ?? null;
            if ($dossier === null) {
                continue;
            }

            $flyer = $this->rangeChemin((string) $row->flyer_path, $dossier);
            if ($flyer !== null) {
                DB::table('formations')->where('id', $row->id)->update(['flyer_path' => $flyer]);
            }
        }

        // --- Habillage : bannière, bandeau, vidéo de fond, logos ------------
        foreach (DB::table('events')->select('id', 'banner_url', 'settings')->get() as $row) {
            $dossier = $dossiers[(int) $row->id];
            $maj = [];

            $banner = $this->range((string) $row->banner_url, $dossier);
            if ($banner !== null) {
                $maj['banner_url'] = $banner;
            }

            $settings = json_decode((string) $row->settings, true);
            if (is_array($settings)) {
                $touche = false;

                // Bandeau et vidéo de fond : des URL.
                foreach (['hero_image', 'hero_video'] as $cle) {
                    if (! isset($settings[$cle])) {
                        continue;
                    }
                    $range = $this->range((string) $settings[$cle], $dossier);
                    if ($range !== null) {
                        $settings[$cle] = $range;
                        $touche = true;
                    }
                }

                // ⚠️ **Le livre des résumés stocke des CHEMINS BRUTS**, pas des URL
                // (`livret/xxx.png`, `livret-templates/xxx.docx`) : les passer au
                // rangement des URL ne les reconnaîtrait pas, faute de marqueur
                // `/storage/`. Deux formes cohabitent en base, il faut les deux.
                if (isset($settings['livret.cover_logo'])) {
                    $range = $this->rangeChemin((string) $settings['livret.cover_logo'], $dossier);
                    if ($range !== null) {
                        $settings['livret.cover_logo'] = $range;
                        $touche = true;
                    }
                }

                foreach ($settings['livret.templates'] ?? [] as $i => $gabarit) {
                    if (! is_array($gabarit) || ! isset($gabarit['path'])) {
                        continue;
                    }
                    $range = $this->rangeChemin((string) $gabarit['path'], $dossier);
                    if ($range !== null) {
                        $settings['livret.templates'][$i]['path'] = $range;
                        $touche = true;
                    }
                }

                foreach ($settings['partners'] ?? [] as $i => $partenaire) {
                    if (! is_array($partenaire) || ! isset($partenaire['logo_url'])) {
                        continue;
                    }
                    $range = $this->range((string) $partenaire['logo_url'], $dossier);
                    if ($range !== null) {
                        $settings['partners'][$i]['logo_url'] = $range;
                        $touche = true;
                    }
                }

                if ($touche) {
                    $maj['settings'] = json_encode($settings);
                }
            }

            if ($maj !== []) {
                DB::table('events')->where('id', $row->id)->update($maj);
            }
        }
    }

    /**
     * Rien : les fichiers sont désormais rangés, et les reculer ne rendrait pas
     * un dossier nommé `2` plus lisible. Le code n'écrit plus que la nouvelle
     * forme - une marche arrière laisserait la base en désaccord avec lui.
     */
    public function down(): void {}

    /**
     * Déplace le fichier d'une URL stockée et rend sa nouvelle URL, ou `null`
     * s'il n'y a rien à changer.
     *
     * ⚠️ **L'hôte de l'URL est conservé tel quel.** Les URL enregistrées sont
     * absolues (`https://…/storage/…`) et ont pu l'être sous un autre domaine
     * que celui du moment : on ne touche qu'au chemin, repéré par le marqueur
     * `/storage/<racine>/` - donc sans dépendre ni de l'hôte, ni de la
     * configuration du disque au moment où l'on passe.
     */
    private function range(string $url, string $dossier): ?string
    {
        $racines = implode('|', EventStorage::ROOTS);

        // ⚠️ L'alternance des racines est GROUPÉE : sans le `(?:…)`, le `/.+` ne
        // s'attache qu'à la dernière, et seule `livret-templates/` était reconnue.
        if (preg_match('#^(.*/storage/)((?:'.$racines.')/.+)$#', $url, $m) !== 1) {
            return null;
        }

        $cible = $this->rangeChemin($m[2], $dossier);

        return $cible === null ? null : $m[1].$cible;
    }

    /**
     * Même travail sur un chemin NU - la forme que stockent le flyer d'une
     * formation et les fichiers du livre des résumés.
     */
    private function rangeChemin(string $chemin, string $dossier): ?string
    {
        $racines = implode('|', EventStorage::ROOTS);

        if (preg_match('#^('.$racines.')/(.+)$#', $chemin, $m) !== 1) {
            return null;
        }

        [, $racine, $reste] = $m;

        $segments = explode('/', $reste);
        $fichier = (string) array_pop($segments);
        if ($fichier === '') {
            return null;
        }

        $cible = $racine.'/'.$dossier
            .(in_array('camera-ready', $segments, true) ? '/camera-ready' : '')
            .'/'.$fichier;
        $source = $racine.'/'.$reste;

        if ($source === $cible) {
            return null;
        }

        $disque = Storage::disk('public');

        if (! $disque->exists($cible)) {
            // Introuvable des deux côtés : on ne réécrit rien (cf. le docblock).
            if (! $disque->exists($source)) {
                return null;
            }

            $disque->move($source, $cible);
        }

        return $cible;
    }
};
