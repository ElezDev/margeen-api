<?php

namespace App\Support;

use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Facades\Storage;

class PublicStorage
{
    public const DISK = 'public';

    public static function disk(): Filesystem
    {
        return Storage::disk(self::DISK);
    }

    public static function url(?string $path): ?string
    {
        if (! $path || ! self::disk()->exists($path)) {
            return null;
        }

        return self::relativeUrl($path);
    }

    public static function relativeUrl(string $path): string
    {
        return '/storage/'.ltrim(str_replace('\\', '/', $path), '/');
    }

    public static function contents(?string $path): ?string
    {
        if (! $path || ! self::disk()->exists($path)) {
            return null;
        }

        return self::disk()->get($path);
    }

    public static function mimeType(?string $path): string
    {
        $extension = strtolower(pathinfo($path ?? '', PATHINFO_EXTENSION));

        return match ($extension) {
            'png' => 'image/png',
            'webp' => 'image/webp',
            'gif' => 'image/gif',
            'svg' => 'image/svg+xml',
            default => 'image/jpeg',
        };
    }

    /** @return resource|null */
    public static function readStream(?string $path)
    {
        if (! $path || ! self::disk()->exists($path)) {
            return null;
        }

        return self::disk()->readStream($path);
    }
}
