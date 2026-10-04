<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Un geste posé sur une attestation délivrée : ouverture, téléchargement,
 * partage, départ vers LinkedIn.
 *
 * ⚠️ **Journal en ajout seul** : on n'y revient jamais pour corriger. La
 * synthèse lisible (`attestations.first_viewed_at`, `views_count`…) s'en déduit
 * et peut être recalculée ; l'inverse est faux.
 *
 * @property Carbon|null $occurred_at
 */
class AttestationEvent extends Model
{
    protected $guarded = ['id'];

    /**
     * Les gestes que l'on sait constater, et ce qu'ils veulent VRAIMENT dire.
     *
     * ⚠️ `linkedin` ne dit pas que l'attestation figure au profil : LinkedIn ne
     * nous rappelle jamais. Il dit que le titulaire est parti vers le formulaire
     * « Ajouter au profil », pré-rempli par nous. C'est une intention constatée,
     * pas un résultat - les libellés le disent partout où ils s'affichent.
     */
    public const KINDS = [
        'view' => 'Ouverture de la page',
        'download' => 'Téléchargement du PDF',
        'share' => 'Partage par la feuille du système',
        'copy' => 'Lien copié',
        'linkedin' => 'Départ vers « Ajouter au profil » LinkedIn',
    ];

    /** Qui a posé le geste. */
    public const ACTOR_HOLDER = 'holder';

    public const ACTOR_PUBLIC = 'public';

    public const ACTOR_STAFF = 'staff';

    public function kindLabel(): string
    {
        return self::KINDS[$this->kind] ?? (string) $this->kind;
    }

    protected function casts(): array
    {
        return ['occurred_at' => 'datetime'];
    }

    /** @return BelongsTo<Attestation, $this> */
    public function attestation(): BelongsTo
    {
        return $this->belongsTo(Attestation::class);
    }
}
