<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Série de colloques : l'événement scientifique récurrent. Chaque occurrence
 * datée est une {@see Edition}.
 */
class Colloque extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    /** @return HasMany<Edition, $this> */
    public function editions(): HasMany
    {
        return $this->hasMany(Edition::class);
    }
}
