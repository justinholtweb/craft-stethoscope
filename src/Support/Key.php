<?php

declare(strict_types=1);

namespace JustinHoltWeb\Stethoscope\Support;

/**
 * Pulse keys are strings; Stethoscope's are JSON arrays of short labels
 * (element type, section, site, …) so cards can split them back into columns.
 */
final class Key
{
    public const int MAX_PART_LENGTH = 200;

    /** @param  list<string|int|null>  $parts */
    public static function encode(array $parts, bool $redact = true): string
    {
        $parts = array_map(function (string|int|null $part) use ($redact): ?string {
            if ($part === null) {
                return null;
            }

            $part = mb_substr(trim((string) $part), 0, self::MAX_PART_LENGTH);

            return $redact ? Redactor::scrub($part) : $part;
        }, $parts);

        return json_encode($parts, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE | JSON_THROW_ON_ERROR);
    }

    /** @return list<string|null> */
    public static function decode(string $key): array
    {
        $parts = json_decode($key, true);

        if (! is_array($parts)) {
            return [$key];
        }

        return array_values(array_map(fn (mixed $part): ?string => is_scalar($part) ? (string) $part : null, $parts));
    }

    /**
     * Collapses digits in a free-form label (`nav-42` → `nav-*`) so per-ID cache keys
     * and timings group into one row instead of thousands.
     */
    public static function group(string $label): string
    {
        return (string) preg_replace('/\d+/', '*', $label);
    }
}
