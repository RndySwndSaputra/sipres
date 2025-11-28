<?php

namespace App\Services;

use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\Image\GdImageBackEnd;
use BaconQrCode\Renderer\Image\ImagickImageBackEnd;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Illuminate\Support\Facades\Log;

class QrGenerationService
{
    /**
     * Generate QR code as SVG data URI.
     */
    public function generateSvgDataUri(string $payload): string
    {
        try {
            $renderer = new ImageRenderer(new RendererStyle(400), new SvgImageBackEnd());
            $writer = new Writer($renderer);
            $svg = $writer->writeString($payload);
            $qrDataUri = 'data:image/svg+xml;base64,' . base64_encode($svg);

            return $qrDataUri;
        } catch (\Throwable $e) {
            Log::error('QR SVG generation failed', ['error' => $e->getMessage()]);

            return $this->getErrorSvgDataUri();
        }
    }

    /**
     * Generate QR code as SVG string.
     */
    public function generateSvg(string $payload): string
    {
        try {
            $backend = new SvgImageBackEnd();
            $renderer = new ImageRenderer(new RendererStyle(400), $backend);
            $writer = new Writer($renderer);

            return $writer->writeString($payload);
        } catch (\Throwable $e) {
            Log::error('QR SVG generation failed', ['error' => $e->getMessage()]);

            return $this->getErrorSvg();
        }
    }

    /**
     * Generate QR code as PNG binary data.
     *
     * @return string|null Returns PNG binary data or null if generation fails
     */
    public function generatePng(string $payload): ?string
    {
        try {
            // Prefer Imagick, fallback to GD
            if (class_exists(ImagickImageBackEnd::class) && extension_loaded('imagick')) {
                $backend = new ImagickImageBackEnd();
            } else {
                $backend = new GdImageBackEnd();
            }
            
            $renderer = new ImageRenderer(new RendererStyle(400), $backend);
            $writer = new Writer($renderer);

            return $writer->writeString($payload);
        } catch (\Throwable $e) {
            Log::warning('PNG generation failed', ['error' => $e->getMessage()]);

            return null;
        }
    }

    /**
     * Get error SVG as data URI.
     */
    public function getErrorSvgDataUri(): string
    {
        return 'data:image/svg+xml;base64,' . base64_encode($this->getErrorSvg());
    }

    /**
     * Get error SVG string.
     */
    public function getErrorSvg(): string
    {
        return '<svg xmlns="http://www.w3.org/2000/svg" width="300" height="300"><rect width="100%" height="100%" fill="#f3f4f6"/><text x="50%" y="50%" dominant-baseline="middle" text-anchor="middle" font-family="sans-serif" font-size="14" fill="#6b7280">QR Error</text></svg>';
    }
}