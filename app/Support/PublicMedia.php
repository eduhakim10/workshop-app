<?php

namespace App\Support;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Absolute / embeddable URLs for public disk files on workshop-app.
 * Used by print & SR before/after pages so images still work when HTML is
 * proxied through the customer portal.
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
        if (! filled($path) || Str::startsWith($path, ['http://', 'https://', 'data:'])) {
            return filled($path) && Str::startsWith($path, 'data:') ? $path : null;
        }

        $full = Storage::disk('public')->path(self::normalize($path));

        if (! is_file($full)) {
            return null;
        }

        $mime = mime_content_type($full) ?: 'application/octet-stream';

        return 'data:' . $mime . ';base64,' . base64_encode((string) file_get_contents($full));
    }

    /** Prefer data URI (works offline / proxy); fallback to absolute workshop URL. */
    public static function src(?string $path): ?string
    {
        return self::dataUri($path) ?? self::absoluteUrl($path);
    }

    private static function normalize(string $path): string
    {
        $normalized = ltrim(str_replace('\\', '/', $path), '/');

        return (string) preg_replace('#^storage/#', '', $normalized);
    }
}
