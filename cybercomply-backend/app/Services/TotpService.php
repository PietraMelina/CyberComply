<?php

namespace App\Services;

class TotpService
{
    private const BASE32_ALPHABET = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';

    public function generateSecret(int $length = 32): string
    {
        $chars = self::BASE32_ALPHABET;
        $secret = '';

        for ($i = 0; $i < $length; $i++) {
            $secret .= $chars[random_int(0, strlen($chars) - 1)];
        }

        return $secret;
    }

    public function makeOtpAuthUri(string $issuer, string $accountName, string $secret): string
    {
        $encodedIssuer = rawurlencode($issuer);
        $encodedAccount = rawurlencode($accountName);

        return sprintf(
            'otpauth://totp/%s:%s?secret=%s&issuer=%s&algorithm=SHA1&digits=6&period=30',
            $encodedIssuer,
            $encodedAccount,
            $secret,
            $encodedIssuer
        );
    }

    public function verify(string $secret, string $code, int $window = 1): bool
    {
        $code = preg_replace('/\s+/', '', $code);

        if (!preg_match('/^\d{6}$/', $code)) {
            return false;
        }

        $timeSlice = (int) floor(time() / 30);

        for ($i = -$window; $i <= $window; $i++) {
            if (hash_equals($this->codeForSlice($secret, $timeSlice + $i), $code)) {
                return true;
            }
        }

        return false;
    }

    private function codeForSlice(string $secret, int $timeSlice): string
    {
        $key = $this->base32Decode($secret);
        $time = pack('N*', 0) . pack('N*', $timeSlice);
        $hmac = hash_hmac('sha1', $time, $key, true);
        $offset = ord(substr($hmac, -1)) & 0x0F;
        $part = substr($hmac, $offset, 4);
        $value = unpack('N', $part)[1] & 0x7FFFFFFF;

        return str_pad((string) ($value % 1000000), 6, '0', STR_PAD_LEFT);
    }

    private function base32Decode(string $input): string
    {
        $input = strtoupper($input);
        $input = preg_replace('/[^A-Z2-7]/', '', $input);

        $bits = '';
        $output = '';

        for ($i = 0; $i < strlen($input); $i++) {
            $value = strpos(self::BASE32_ALPHABET, $input[$i]);
            if ($value === false) {
                continue;
            }
            $bits .= str_pad(decbin($value), 5, '0', STR_PAD_LEFT);
        }

        for ($i = 0; $i + 8 <= strlen($bits); $i += 8) {
            $output .= chr(bindec(substr($bits, $i, 8)));
        }

        return $output;
    }
}
