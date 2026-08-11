<?php

namespace App\Support;

use SimpleSoftwareIO\QrCode\Facades\QrCode;

/**
 * Moteur TOTP autonome (RFC 6238) — sans dépendance Composer, dans l'esprit
 * « PHP pur » du projet (cf. XlsxWriter). Compatible Google Authenticator,
 * Authy, Microsoft Authenticator, 1Password… Paramètres standards : SHA1,
 * 6 chiffres, période 30 s. Porté depuis le pattern bizzcart.
 */
class Totp
{
    private const PERIOD = 30;

    private const DIGITS = 6;

    private const ALGO = 'sha1';

    private const B32 = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';

    /** Génère un secret partagé encodé en base32 (20 octets = 160 bits, recommandé). */
    public static function generateSecret(int $bytes = 20): string
    {
        return self::base32Encode(random_bytes($bytes));
    }

    /** Construit l'URI otpauth:// à encoder dans le QR code. */
    public static function otpauthUri(string $secret, string $account, ?string $issuer = null): string
    {
        $issuer = $issuer ?: self::issuer();
        $label = rawurlencode($issuer).':'.rawurlencode($account);
        $query = http_build_query([
            'secret' => $secret,
            'issuer' => $issuer,
            'algorithm' => strtoupper(self::ALGO),
            'digits' => self::DIGITS,
            'period' => self::PERIOD,
        ], '', '&', PHP_QUERY_RFC3986);

        return 'otpauth://totp/'.$label.'?'.$query;
    }

    /** QR code SVG (chaîne HTML inline) provisionnant le secret. */
    public static function qrCodeSvg(string $secret, string $account, int $size = 200): string
    {
        return (string) QrCode::format('svg')->size($size)->margin(1)
            ->generate(self::otpauthUri($secret, $account));
    }

    /** Émetteur affiché dans l'application d'authentification. */
    public static function issuer(): string
    {
        return (string) config('app.name', 'GESCO');
    }

    /**
     * Vérifie un code saisi, avec une fenêtre de tolérance (± $window périodes)
     * pour absorber le décalage d'horloge.
     */
    public static function verify(string $secret, string $code, int $window = 1): bool
    {
        $code = (string) preg_replace('/\s+/', '', $code);

        if (! preg_match('/^\d{'.self::DIGITS.'}$/', $code) || $secret === '') {
            return false;
        }

        $timeSlice = (int) floor(time() / self::PERIOD);
        for ($i = -$window; $i <= $window; $i++) {
            if (hash_equals(self::codeAt($secret, $timeSlice + $i), $code)) {
                return true;
            }
        }

        return false;
    }

    /** Calcule le code TOTP pour une tranche de temps donnée. */
    public static function codeAt(string $secret, int $timeSlice): string
    {
        $key = self::base32Decode($secret);

        // Compteur 8 octets big-endian (poids fort à 0 jusqu'en 2106).
        $binTime = pack('N', 0).pack('N', $timeSlice);

        $hash = hash_hmac(self::ALGO, $binTime, $key, true);
        $offset = ord($hash[strlen($hash) - 1]) & 0x0F;

        $truncated = ((ord($hash[$offset]) & 0x7F) << 24)
            | ((ord($hash[$offset + 1]) & 0xFF) << 16)
            | ((ord($hash[$offset + 2]) & 0xFF) << 8)
            | (ord($hash[$offset + 3]) & 0xFF);

        $otp = $truncated % (10 ** self::DIGITS);

        return str_pad((string) $otp, self::DIGITS, '0', STR_PAD_LEFT);
    }

    /** Encodage base32 (RFC 4648, sans padding). */
    public static function base32Encode(string $data): string
    {
        if ($data === '') {
            return '';
        }

        $binary = '';
        foreach (str_split($data) as $char) {
            $binary .= str_pad(decbin(ord($char)), 8, '0', STR_PAD_LEFT);
        }

        $out = '';
        foreach (str_split($binary, 5) as $chunk) {
            $chunk = str_pad($chunk, 5, '0', STR_PAD_RIGHT);
            $out .= self::B32[bindec($chunk)];
        }

        return $out;
    }

    /** Décodage base32 (RFC 4648). Ignore les caractères invalides et le padding. */
    public static function base32Decode(string $b32): string
    {
        $b32 = rtrim(strtoupper($b32), '=');
        if ($b32 === '') {
            return '';
        }

        $binary = '';
        foreach (str_split($b32) as $char) {
            $pos = strpos(self::B32, $char);
            if ($pos === false) {
                continue;
            }
            $binary .= str_pad(decbin($pos), 5, '0', STR_PAD_LEFT);
        }

        $out = '';
        foreach (str_split($binary, 8) as $byte) {
            if (strlen($byte) < 8) {
                break;
            }
            $out .= chr(bindec($byte));
        }

        return $out;
    }
}
