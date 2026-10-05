<?php

namespace App\Support;

use App\Models\Setting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Throwable;

/**
 * Accès aux réglages éditables (clé/valeur), avec cache. Les clés sont plates
 * (ex. « mail.host », « cashpay.username », « payments.default »).
 */
class Settings
{
    protected const CACHE_KEY = 'app.settings';

    /** @return array<string, mixed> */
    public static function all(): array
    {
        // ⚠️ Lu AU BOOT (AppServiceProvider::applySettingsToConfig). La base peut
        // ne pas exister encore - `composer install` lance `package:discover`
        // avant la création du fichier SQLite en CI, et `Schema::hasTable()` lui-
        // même lève alors une QueryException (fichier absent, pas juste la table).
        // Sans base joignable, on repart des valeurs de config/.env : pas de
        // surcharge, jamais une page ni une commande qui tombe.
        try {
            if (! Schema::hasTable('settings')) {
                return [];
            }

            return Cache::rememberForever(self::CACHE_KEY, function () {
                return Setting::all()
                    ->mapWithKeys(fn (Setting $s) => [$s->key => json_decode((string) $s->value, true)])
                    ->all();
            });
        } catch (Throwable) {
            return [];
        }
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        $value = self::all()[$key] ?? null;

        return $value === null || $value === '' ? $default : $value;
    }

    public static function set(string $key, mixed $value): void
    {
        Setting::updateOrCreate(['key' => $key], ['value' => json_encode($value)]);
        Cache::forget(self::CACHE_KEY);
    }

    /** @param array<string, mixed> $values */
    public static function setMany(array $values): void
    {
        foreach ($values as $key => $value) {
            Setting::updateOrCreate(['key' => $key], ['value' => json_encode($value)]);
        }
        Cache::forget(self::CACHE_KEY);
    }
}
