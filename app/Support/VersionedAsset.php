<?php

declare(strict_types=1);

namespace App\Support;

final class VersionedAsset
{
    /**
     * Build a public asset URL that changes when the file changes.
     */
    public static function url(string $path): string
    {
        $relativePath = mb_ltrim($path, '/');
        $fullPath = public_path($relativePath);
        $version = is_file($fullPath) ? (string) filemtime($fullPath) : (string) time();

        return asset($relativePath).'?v='.$version;
    }
}
