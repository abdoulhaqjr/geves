<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Réglage clé/valeur (valeur en JSON). Manipulé via {@see \App\Support\Settings}.
 */
class Setting extends Model
{
    protected $primaryKey = 'key';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $guarded = [];
}
