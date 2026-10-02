<?php

use App\Models\DocumentTemplate;
use Illuminate\Database\Migrations\Migration;

/**
 * Les modèles livrés par le concepteur étaient rédigés en ASCII : ni accents ni
 * apostrophes (« Decernee a », « l Universite », « presente attestation lui est
 * delivree »). Ce n'était pas un caractère « ignoré » à l'affichage - la source
 * n'en comportait pas, et les attestations délivrées non plus.
 *
 * La source est corrigée, mais les modèles **déjà enregistrés** gardent l'ancien
 * texte : cette migration les réécrit, dans la mise en page comme dans
 * l'instantané de l'éditeur.
 *
 * ⚠️ Aucun retour arrière : remettre du texte fautif n'aurait aucun intérêt.
 * ⚠️ On ne touche QUE ces phrases exactes : un texte personnalisé par un
 * gestionnaire ne doit pas être réécrit sous ses pieds.
 */
return new class extends Migration
{
    private const PHRASES = [
        'Je soussigne, Prof. Padabo KADOUZA, President du Comite d Organisation, atteste que' => 'Je soussigné, Prof. Padabo KADOUZA, Président du Comité d’Organisation, atteste que',
        'a suivi avec diligence la formation sur la valorisation des resultats de recherche, tenue en marge du 6e colloque scientifique international de l Universite de Kara, en collaboration avec l Universite de Parakou (06 – 10 octobre 2025, Kara).' => 'a suivi avec diligence la formation sur la valorisation des résultats de recherche, tenue en marge du 6e colloque scientifique international de l’Université de Kara, en collaboration avec l’Université de Parakou (06 - 10 octobre 2025, Kara).',
        'a suivi avec diligence la formation sur la valorisation des resultats de recherche, tenue en marge des travaux du colloque scientifique international de l Universite de Kara, en collaboration avec l Universite de Parakou ([edition.dates]), sur le theme : [edition.theme].' => 'a suivi avec diligence la formation sur la valorisation des résultats de recherche, tenue en marge des travaux du colloque scientifique international de l’Université de Kara, en collaboration avec l’Université de Parakou ([edition.dates]), sur le thème : [edition.theme].',
        'a suivi avec diligence et reussite la formation sur l organisation de la recherche et innovation, la protection de la propriete intellectuelle, le transfert de technologie et la valorisation economique des resultats de recherche ([edition.dates]).' => 'a suivi avec diligence et réussite la formation sur l’organisation de la recherche et de l’innovation, la protection de la propriété intellectuelle, le transfert de technologie et la valorisation économique des résultats de recherche ([edition.dates]).',
        'pour avoir participe et communique au Colloque Scientifique International de l Universite de Kara, en collaboration avec l Universite de Parakou, du 06 au 10 octobre 2025 au palais des congres de Kara, au Togo.' => 'pour avoir participé et communiqué au Colloque Scientifique International de l’Université de Kara, en collaboration avec l’Université de Parakou, du 06 au 10 octobre 2025 au palais des congrès de Kara, au Togo.',
        'pour avoir modere une session lors du Colloque Scientifique International de l Universite de Kara, en collaboration avec l Universite de Parakou, du 06 au 10 octobre 2025 au palais des congres de Kara, au Togo.' => 'pour avoir modéré une session lors du Colloque Scientifique International de l’Université de Kara, en collaboration avec l’Université de Parakou, du 06 au 10 octobre 2025 au palais des congrès de Kara, au Togo.',
        'pour avoir participe au Colloque Scientifique International de l Universite de Kara, en collaboration avec l Universite de Parakou, du 06 au 10 octobre 2025 au palais des congres de Kara, au Togo.' => 'pour avoir participé au Colloque Scientifique International de l’Université de Kara, en collaboration avec l’Université de Parakou, du 06 au 10 octobre 2025 au palais des congrès de Kara, au Togo.',
        'En foi de quoi, la presente attestation lui est delivree pour servir et valoir ce que de droit.' => 'En foi de quoi, la présente attestation lui est délivrée pour servir et valoir ce que de droit.',
        'Je soussigne, [signatory.name], President du Comite d Organisation, atteste que' => 'Je soussigné, [signatory.name], Président du Comité d’Organisation, atteste que',
        'Je soussigne, [signatory.name], Coordinateur du projet For-VaRRITO, atteste que' => 'Je soussigné, [signatory.name], Coordinateur du projet For-VaRRITO, atteste que',
        'Le President du Comite d Organisation,' => 'Le Président du Comité d’Organisation,',
        'Le President du comite d organisation' => 'Le Président du comité d’organisation',
        'Decernee a' => 'Décernée à',
        // En français, les capitales s'accentuent.
        'UNIVERSITE' => 'UNIVERSITÉ',
        'MODERATION' => 'MODÉRATION',
    ];

    public function up(): void
    {
        DocumentTemplate::query()->each(function (DocumentTemplate $tpl): void {
            $layout = $this->fix($tpl->layout);
            $state = $this->fix($tpl->editor_state);

            if ($layout !== $tpl->layout || $state !== $tpl->editor_state) {
                $tpl->forceFill(['layout' => $layout, 'editor_state' => $state])->save();
            }
        });
    }

    public function down(): void
    {
        // Rien : on ne restaure pas un texte fautif.
    }

    /** Réécrit récursivement les chaînes de la structure. */
    private function fix(mixed $value): mixed
    {
        if (is_string($value)) {
            return strtr($value, self::PHRASES);
        }

        if (is_array($value)) {
            return array_map(fn ($v) => $this->fix($v), $value);
        }

        return $value;
    }
};
