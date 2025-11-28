<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsAppService
{
    /**
     * Send WhatsApp message via Fonnte API.
     *
     * @return array{success: bool, status?: string, detail?: mixed}
     */
    public function sendMessage(string $phone, string $message): array
    {
        $fonnteToken = (string) config('services.fonnte.token', env('FONNTE_TOKEN'));
        if ($fonnteToken === '') {
            return [
                'success' => false,
                'detail' => 'Fonnte token belum dikonfigurasi',
            ];
        }

        try {
            $response = Http::withHeaders([
                'Authorization' => $fonnteToken,
            ])->asForm()->post('https://api.fonnte.com/send', [
                'target' => $phone,
                'message' => $message,
                'preview' => true,
            ]);

            $ok = $response->ok();
            $body = $response->json();

            if ($ok && isset($body['status']) ? (bool) $body['status'] : true) {
                return [
                    'success' => true,
                    'status' => 'sent',
                ];
            }

            return [
                'success' => false,
                'status' => 'failed',
                'detail' => $body,
            ];
        } catch (\Throwable $e) {
            Log::error('WhatsApp sending failed', ['error' => $e->getMessage(), 'phone' => $phone]);

            return [
                'success' => false,
                'status' => 'error',
                'detail' => $e->getMessage(),
            ];
        }
    }

    /**
     * Check if Fonnte token is configured.
     */
    public function isTokenConfigured(): bool
    {
        $token = (string) config('services.fonnte.token', env('FONNTE_TOKEN'));

        return $token !== '';
    }
}
