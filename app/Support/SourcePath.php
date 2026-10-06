<?php

namespace App\Support;

use Illuminate\Support\Str;

/**
 * Helpers to display absolute paths from monitored apps relative to their project root.
 */
final class SourcePath
{
    private const MARKERS = ['/vendor/', '/app/', '/resources/', '/storage/', '/routes/', '/bootstrap/', '/database/', '/config/'];

    /**
     * Guess the deployment root (e.g. /var/www/shop/releases/42) from a set of absolute paths.
     *
     * @param  iterable<?string>  $paths
     */
    public static function root(iterable $paths): ?string
    {
        $paths = array_values(array_filter(is_array($paths) ? $paths : iterator_to_array($paths, false), 'is_string'));

        // vendor/ is the most reliable marker, so every path is checked for it first.
        foreach (self::MARKERS as $marker) {
            foreach ($paths as $path) {
                if (str_contains($path, $marker)) {
                    $root = Str::before($path, $marker);

                    return $root !== '' ? $root : null;
                }
            }
        }

        return null;
    }

    public static function relative(?string $path, ?string $root): ?string
    {
        if ($path === null || $root === null || ! str_starts_with($path, $root.'/')) {
            return $path;
        }

        return substr($path, strlen($root) + 1);
    }

    public static function isVendor(?string $path, ?string $root): bool
    {
        $relative = self::relative($path, $root);

        return $relative !== null && (str_starts_with($relative, 'vendor/') || str_contains($relative, '/vendor/'));
    }
}
