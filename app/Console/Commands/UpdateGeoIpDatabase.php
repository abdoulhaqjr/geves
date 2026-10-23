<?php

namespace App\Console\Commands;

use GeoIp2\Database\Reader;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class UpdateGeoIpDatabase extends Command
{
    protected $signature = 'geoip:update {--month= : Édition YYYY-MM de DB-IP Country Lite}';

    protected $description = 'Télécharger et installer la base locale DB-IP Country Lite';

    public function handle(): int
    {
        $month = (string) ($this->option('month') ?: now('UTC')->format('Y-m'));
        if (! preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $month)) {
            $this->error('Le mois doit être au format YYYY-MM.');

            return self::FAILURE;
        }

        $path = (string) config('geoip.database_path');
        $directory = dirname($path);
        if (! is_dir($directory) && ! mkdir($directory, 0755, true) && ! is_dir($directory)) {
            $this->error('Impossible de créer le dossier de la base GeoIP.');

            return self::FAILURE;
        }

        $suffix = bin2hex(random_bytes(6));
        $archive = $path.'.'.$suffix.'.gz';
        $candidate = $path.'.'.$suffix.'.new';
        $backup = $path.'.'.$suffix.'.bak';
        $url = "https://download.db-ip.com/free/dbip-country-lite-{$month}.mmdb.gz";
        $replaced = false;

        try {
            $response = Http::timeout(180)->withOptions(['sink' => $archive])->get($url);
            if (! $response->successful()) {
                throw new \RuntimeException('Téléchargement refusé (HTTP '.$response->status().').');
            }
            if (! is_file($archive) || filesize($archive) > 20 * 1024 * 1024) {
                throw new \RuntimeException('Archive GeoIP absente ou anormalement volumineuse.');
            }

            $input = gzopen($archive, 'rb');
            $output = fopen($candidate, 'wb');
            if ($input === false || $output === false) {
                throw new \RuntimeException('Impossible de décompresser la base téléchargée.');
            }

            try {
                $expanded = 0;
                while (! gzeof($input)) {
                    $chunk = gzread($input, 65536);
                    if ($chunk === false || $chunk === '') {
                        throw new \RuntimeException('Fichier GeoIP incomplet.');
                    }
                    $expanded += strlen($chunk);
                    if ($expanded > 64 * 1024 * 1024 || fwrite($output, $chunk) !== strlen($chunk)) {
                        throw new \RuntimeException('Fichier GeoIP trop volumineux ou incomplet.');
                    }
                }
            } finally {
                gzclose($input);
                fclose($output);
            }

            $reader = new Reader($candidate);
            try {
                if (! str_contains($reader->metadata()->databaseType, 'Country') || $reader->metadata()->nodeCount < 1) {
                    throw new \RuntimeException('Le fichier téléchargé n’est pas une base de pays valide.');
                }
                if ($reader->country('8.8.8.8')->country->isoCode !== 'US') {
                    throw new \RuntimeException('La base GeoIP ne résout pas correctement les adresses.');
                }
            } finally {
                $reader->close();
            }

            if (is_file($path) && ! rename($path, $backup)) {
                throw new \RuntimeException('Impossible de remplacer la base GeoIP existante.');
            }
            if (! rename($candidate, $path)) {
                if (is_file($backup)) {
                    rename($backup, $path);
                }
                throw new \RuntimeException('Impossible d’installer la nouvelle base GeoIP.');
            }
            $replaced = true;
            if (is_file($backup)) {
                unlink($backup);
            }

            $this->info("Base GeoIP {$month} installée : {$path}");

            return self::SUCCESS;
        } catch (\Throwable $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        } finally {
            if (is_file($archive)) {
                unlink($archive);
            }
            if (is_file($candidate)) {
                unlink($candidate);
            }
            if (! $replaced && is_file($backup) && ! is_file($path)) {
                rename($backup, $path);
            }
        }
    }
}
