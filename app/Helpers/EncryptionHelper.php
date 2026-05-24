<?php

namespace App\Helpers;

class EncryptionHelper
{
    private const SECRET_SALT = '357312';

    /*
    |--------------------------------------------------------------------------
    | ENCRYPT ID
    |--------------------------------------------------------------------------
    */
    public static function encryptId($id): string
    {
        $plain = self::SECRET_SALT . ':' . $id;

        return rtrim(
            strtr(
                base64_encode($plain),
                '+/',
                '-_'
            ),
            '='
        );
    }

    /*
    |--------------------------------------------------------------------------
    | DECRYPT ID
    |--------------------------------------------------------------------------
    */
    public static function decryptId($encrypted): ?string
    {
        try {

            if (empty($encrypted)) {
                return null;
            }

            /*
            |--------------------------------------------------------------------------
            | URL SAFE BASE64
            |--------------------------------------------------------------------------
            */
            $base64 = strtr($encrypted, '-_', '+/');

            $padding = strlen($base64) % 4;

            if ($padding > 0) {
                $base64 .= str_repeat('=', 4 - $padding);
            }

            $decoded = base64_decode($base64, true);

            if ($decoded === false) {
                return null;
            }

            /*
            |--------------------------------------------------------------------------
            | VALIDASI FORMAT
            |--------------------------------------------------------------------------
            */
            if (!str_contains($decoded, ':')) {
                return null;
            }

            [$salt, $id] = explode(':', $decoded, 2);

            /*
            |--------------------------------------------------------------------------
            | VALIDASI SALT
            |--------------------------------------------------------------------------
            */
            if ($salt !== self::SECRET_SALT) {
                return null;
            }

            /*
            |--------------------------------------------------------------------------
            | VALIDASI ANGKA
            |--------------------------------------------------------------------------
            */
            if (!preg_match('/^[0-9]+$/', $id)) {
                return null;
            }

            return $id;

        } catch (\Throwable $e) {
            return null;
        }
    }
}