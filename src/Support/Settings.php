<?php

namespace ClassAtlas\USms\Support;

/**
 * Reads `config/usms.php` with the types the package needs. A value from the
 * environment arrives as a string, and an app's published copy of the config
 * may be older than this one, so a scalar is converted and anything else falls
 * back to the default.
 *
 * @internal
 */
final class Settings
{
    public static function string(string $key, string $default = ''): string
    {
        $value = config($key, $default);

        return is_scalar($value) ? (string) $value : $default;
    }

    public static function nullableString(string $key): ?string
    {
        $value = config($key);

        return is_scalar($value) && (string) $value !== '' ? (string) $value : null;
    }

    public static function integer(string $key, int $default): int
    {
        $value = config($key, $default);

        return is_scalar($value) ? (int) $value : $default;
    }
}
