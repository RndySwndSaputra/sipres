<?php

namespace App\Services;

class PhoneNormalizationService
{
    /**
     * Normalize phone number to international format (62).
     *
     * @return string|null Returns normalized phone number or null if invalid
     */
    public function normalize(string $phone): ?string
    {
        // Remove all non-digit characters
        $phone = preg_replace('/\D+/', '', $phone);
        
        if ($phone === null || $phone === '') {
            return null;
        }

        // Convert to international format
        if (str_starts_with($phone, '0')) {
            $phone = '62' . substr($phone, 1);
        } elseif (str_starts_with($phone, '8')) {
            $phone = '62' . $phone;
        }

        return $phone;
    }
}