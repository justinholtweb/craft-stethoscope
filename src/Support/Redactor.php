<?php

declare(strict_types=1);

namespace JustinHoltWeb\Stethoscope\Support;

/**
 * Scrubs personal data out of strings that end up in Pulse's tables: email
 * addresses, IPv4/IPv6 addresses and URL query strings. Pure, so it's unit-tested.
 */
final class Redactor
{
    public const string EMAIL = '[email]';

    public const string IP = '[ip]';

    public static function scrub(string $value): string
    {
        if ($value === '') {
            return $value;
        }

        $value = (string) preg_replace('/[A-Z0-9._%+\-]+@[A-Z0-9\-]+(?:\.[A-Z0-9\-]+)*\.[A-Z]{2,}/i', self::EMAIL, $value);
        // IPv4, not part of a longer dotted number (versions like 1.2.3.4.5 are left alone).
        $value = (string) preg_replace('/(?<![\d.])(?:(?:25[0-5]|2[0-4]\d|1?\d?\d)\.){3}(?:25[0-5]|2[0-4]\d|1?\d?\d)(?![\d.])/', self::IP, $value);

        // IPv6: the full eight-group form, or a `::`-compressed one. Times (12:30:45), `Class::method`
        // and Craft's `template::key::1` cache keys don't match.
        return (string) preg_replace('/(?<![0-9A-Za-z])(?<!::)(?:(?:[0-9a-f]{1,4}:){7}[0-9a-f]{1,4}|(?:[0-9a-f]{1,4}:){1,6}:(?:[0-9a-f]{1,4}(?::[0-9a-f]{1,4}){0,5})?)(?![\w:])/i', self::IP, $value);
    }

    /** A URL (or `METHOD url`) without its query string, fragment or user info. */
    public static function stripQuery(string $url): string
    {
        // user:password@ after `//` (or JSON's escaped `\/\/`).
        $url = (string) preg_replace('#(//|\\\\/\\\\/)[^/@\s"\\\\]*@#', '$1', $url);

        return (string) preg_replace('/[?#][^\s"]*/', '', $url);
    }
}
