<?php

namespace App\Support;

use GeoIp2\Database\Reader;
use GeoIp2\Exception\AddressNotFoundException;

class IpCountry
{
    /**
     * Résout les pays depuis la base locale, sans transmettre les IP à un tiers.
     *
     * @param  iterable<string|null>  $ips
     * @return array<string, string>
     */
    public function namesFor(iterable $ips): array
    {
        $path = (string) config('geoip.database_path');
        if (! is_file($path)) {
            return [];
        }

        try {
            $reader = new Reader($path, ['fr', 'en']);
        } catch (\Throwable $e) {
            report($e);

            return [];
        }

        $countries = [];
        try {
            foreach ($ips as $ip) {
                if (! is_string($ip) || isset($countries[$ip]) || ! filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                    continue;
                }

                try {
                    $country = $reader->country($ip)->country;
                    $name = $country->name ?: $country->isoCode;
                    if ($name) {
                        $countries[$ip] = $name;
                    }
                } catch (AddressNotFoundException) {
                    // Certaines plages publiques ne figurent pas dans l'édition Lite.
                } catch (\Throwable $e) {
                    report($e);
                }
            }
        } finally {
            $reader->close();
        }

        return $countries;
    }
}
