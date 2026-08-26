<?php

namespace App\Support;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Absolute / embeddable URLs for public disk files on workshop-app.
 * Used by print & SR before/after pages so images still work when HTML is
 * proxied through the customer portal (different host).
 */
class PublicMedia
{
    public static function absoluteUrl(?string $path): ?string
    {
        if (! filled($path)) {
            return null;
        }

        if (Str::startsWith($path, ['http://', 'https://', 'data:'])) {
            return $path;
        }

        $normalized = self::normalize($path);

        return rtrim((string) config('app.url'), '/') . '/storage/' . $normalized;
    }

    public static function dataUri(?string $path): ?string
    {
        if (! filled($path)) {
            return null;
        }

        if (Str::startsWith($path, 'data:')) {
            return $path;
        }

        if (Str::startsWith($path, ['http://', 'https://'])) {
            return self::dataUriFromUrl($path);
        }

        $full = Storage::disk('public')->path(self::normalize($path));

        if (! is_file($full)) {
            return null;
        }

        $mime = mime_content_type($full) ?: 'application/octet-stream';

        return 'data:' . $mime . ';base64,' . base64_encode((string) file_get_contents($full));
    }

    /**
     * Prefer data URI so print works when HTML is served from customer portal
     * (different domain than workshop storage). Falls back to absolute workshop URL.
     */
    public static function src(?string $path): ?string
    {
        $embedded = self::dataUri($path);

        if ($embedded) {
            return $embedded;
        }

        $absolute = self::absoluteUrl($path);
        $fromAbsolute = $absolute ? self::dataUriFromUrl($absolute) : null;

        return $fromAbsolute ?? $absolute;
    }

    private static function dataUriFromUrl(string $url): ?string
    {
        try {
            $response = Http::timeout(8)
                ->withOptions(['verify' => false])
                ->get($url);

            if (! $response->successful()) {
                return null;
            }

            $body = $response->body();

            if ($body === '' || strlen($body) > 5_000_000) {
                return null;
            }

            $mime = $response->header('Content-Type') ?: 'image/png';

            // Strip charset / parameters from Content-Type
            $mime = trim(explode(';', $mime)[0]);

            if (! Str::startsWith($mime, 'image/')) {
                $mime = 'image/png';
            }

            return 'data:' . $mime . ';base64,' . base64_encode($body);
        } catch (\Throwable) {
            return null;
        }
    }

    private static function normalize(string $path): string
    {
        $normalized = ltrim(str_replace('\\', '/', $path), '/');

        return (string) preg_replace('#^storage/#', '', $normalized);
    }
}
