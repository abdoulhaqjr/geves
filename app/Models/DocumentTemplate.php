<?php

namespace App\Models;

use App\Documents\LayoutSanitizer;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Modèle (mise en page JSON) d'un badge ou d'une attestation, par édition et type.
 *
 * @property array<string, mixed>|null $layout
 * @property array<string, mixed>|null $editor_state
 * @property Edition|null $edition
 */
class DocumentTemplate extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['layout' => 'array', 'editor_state' => 'array'];
    }

    /**
     * Modèle enregistré pour ce couple (édition, kind, type), créé au besoin à
     * partir de la mise en page standard.
     *
     * ⚠️ Il n'y a **plus de repli implicite** à la charte au moment du rendu :
     * tout document est produit à partir d'un modèle réellement enregistré, donc
     * visible, modifiable et supprimable dans le back-office. Supprimer un modèle
     * le réinitialise (il est recréé ici à la prochaine utilisation).
     */
    public static function ensureFor(int $editionId, string $kind, string $type = 'default', ?string $title = null): self
    {
        $template = self::firstOrNew(['edition_id' => $editionId, 'kind' => $kind, 'type' => $type]);

        if (! $template->exists || blank($template->layout)) {
            $template->layout = LayoutSanitizer::default($kind, $type);
        }
        if (blank($template->title)) {
            $template->title = $title ?: ($kind === 'badge' ? 'Badge' : (Attestation::TYPES[$type] ?? ucfirst($type)));
        }
        $template->save();

        return $template;
    }

    /** @return BelongsTo<Edition, $this> */
    public function edition(): BelongsTo
    {
        return $this->belongsTo(Edition::class);
    }
}
