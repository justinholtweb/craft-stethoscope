<?php

declare(strict_types=1);

namespace JustinHoltWeb\Stethoscope\Support;

use Illuminate\Contracts\Auth\Authenticatable;
use JustinHoltWeb\Stethoscope\Settings;
use Laravel\Pulse\Entry;
use Laravel\Pulse\Value;

/**
 * What Stethoscope does about personal data in Pulse, including Pulse's own
 * recorders: a filter that rewrites every entry key before it is stored, and
 * how users appear on Pulse's user cards.
 */
final class Privacy
{
    /** Entry types whose keys hold URLs. */
    private const array URL_TYPES = ['slow_request', 'slow_outgoing_request'];

    public function __construct(private readonly Settings $settings) {}

    /**
     * A Pulse filter (`Pulse::filter()`). Pulse filters can only keep or drop, but
     * entry keys are public, so this scrubs them in place and keeps everything.
     */
    public function __invoke(Entry|Value $entry): bool
    {
        if (! $this->settings->redactPersonalData) {
            return true;
        }

        $key = $entry->key;

        if (in_array($entry->type, self::URL_TYPES, true)) {
            $key = Redactor::stripQuery($key);
        }

        $entry->key = Redactor::scrub($key);

        return true;
    }

    /**
     * Pulse's user cards (`Pulse::user()`): a name, never an email-shaped username,
     * and no Gravatar (whose URL is a hash of the email address).
     *
     * @return array{name: string, extra: string, avatar: null}
     */
    public function user(Authenticatable $user): array
    {
        $id = $user->getAuthIdentifier();
        $fullName = trim((string) self::attribute($user, 'fullName'));
        $username = trim((string) self::attribute($user, 'username'));
        $email = (string) self::attribute($user, 'email');

        $name = match (true) {
            $fullName !== '' => $fullName,
            $username !== '' && ! str_contains($username, '@') => $username,
            default => "User #{$id}",
        };

        return [
            'name' => $name,
            'extra' => $this->settings->showUserEmails && $email !== '' ? $email : "#{$id}",
            'avatar' => null,
        ];
    }

    private static function attribute(Authenticatable $user, string $name): mixed
    {
        return rescue(fn () => $user->{$name} ?? null, null, false);
    }
}
