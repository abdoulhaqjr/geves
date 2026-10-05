<?php

namespace App\Models;

use App\Enums\EventType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Série d'événements scientifiques : la récurrence (ex-« colloque »). Chaque
 * occurrence datée est un {@see Event}. La série porte le TYPE ({@see EventType} :
 * colloque, séminaire, journées scientifiques…), hérité par ses événements.
 *
 * @property EventType $type
 */
class EventSeries extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['type' => EventType::class];
    }

    public function typeLabel(): string
    {
        return ($this->type ?? EventType::Colloque)->label();
    }

    /** @return HasMany<Event, $this> */
    public function events(): HasMany
    {
        return $this->hasMany(Event::class);
    }
}
