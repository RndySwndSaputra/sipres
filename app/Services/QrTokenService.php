<?php

namespace App\Services;

class QrTokenService
{
    /**
     * Generate a QR token for a participant.
     */
    public function makeQrToken(string $acaraId, string $nip): string
    {
        $payload = ['a' => $acaraId, 'n' => $nip, 't' => time()];
        $json = json_encode($payload, JSON_UNESCAPED_UNICODE);
        $b64 = rtrim(strtr(base64_encode($json), '+/', '-_'), '=');
        $sig = hash_hmac('sha256', $b64, (string) config('app.key'));

        return $b64 . '.' . $sig;
    }

    /**
     * Parse and validate a QR token.
     * [PERBAIKAN]: Ubah int $acaraId menjadi string $acaraId untuk mendukung UUID
     *
     * @return array{a: string, n: string, t: int}
     */
    public function parseQrToken(string $acaraId, string $token): array
    {
        $parts = explode('.', $token, 2);
        if (count($parts) !== 2) {
            abort(404);
        }
        [$b64, $sig] = $parts;
        $expected = hash_hmac('sha256', $b64, (string) config('app.key'));
        if (! hash_equals($expected, $sig)) {
            abort(404);
        }
        // base64url decode with padding
        $raw = strtr($b64, '-_', '+/');
        $pad = strlen($raw) % 4;
        if ($pad) {
            $raw .= str_repeat('=', 4 - $pad);
        }
        $json = base64_decode($raw, true);
        if ($json === false) {
            abort(404);
        }
        $data = json_decode((string) $json, true);
        
        // [PERBAIKAN]: Validasi String UUID (Case Insensitive jika perlu)
        if (! is_array($data) || (string)($data['a'] ?? '') !== (string)$acaraId) {
            abort(404);
        }
        
        // Optional: expire after 30 days
        if ((time() - (int) ($data['t'] ?? 0)) > 60 * 60 * 24 * 30) {
            abort(410);
        }

        return $data; // ['a'=>uuid, 'n'=>nip, 't'=>timestamp]
    }
}