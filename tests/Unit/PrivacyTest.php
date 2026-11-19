<?php

declare(strict_types=1);

use Illuminate\Contracts\Auth\Authenticatable;
use JustinHoltWeb\Stethoscope\Settings;
use JustinHoltWeb\Stethoscope\Support\Privacy;
use Laravel\Pulse\Entry;

function stethoscopeUser(array $attributes): Authenticatable
{
    return new class($attributes) implements Authenticatable
    {
        public function __construct(private array $attributes) {}

        public function __get(string $name): mixed
        {
            return $this->attributes[$name] ?? null;
        }

        public function __isset(string $name): bool
        {
            return isset($this->attributes[$name]);
        }

        public function getAuthIdentifierName(): string
        {
            return 'id';
        }

        public function getAuthIdentifier(): mixed
        {
            return $this->attributes['id'];
        }

        public function getAuthPasswordName(): string
        {
            return 'password';
        }

        public function getAuthPassword(): string
        {
            return '';
        }

        public function getRememberToken(): ?string
        {
            return null;
        }

        public function setRememberToken($value): void {}

        public function getRememberTokenName(): string
        {
            return '';
        }
    };
}

it('scrubs every Pulse entry key, keeping the entry', function () {
    $entry = new Entry(time(), 'cache_hit', 'login|ada@example.com|10.0.0.1');

    expect((new Privacy(new Settings))($entry))->toBeTrue()
        ->and($entry->key)->toBe('login|[email]|[ip]');
});

it('drops query strings from URL entry types only', function () {
    $outgoing = new Entry(time(), 'slow_outgoing_request', '["GET","https:\/\/api.example.com\/x?key=s3cret"]');
    $cache = new Entry(time(), 'cache_hit', 'page?x=1');
    $privacy = new Privacy(new Settings);
    $privacy($outgoing);
    $privacy($cache);

    expect($outgoing->key)->toBe('["GET","https:\/\/api.example.com\/x"]')
        ->and($cache->key)->toBe('page?x=1');
});

it('leaves keys alone when redaction is off', function () {
    $settings = new Settings;
    $settings->redactPersonalData = false;
    $entry = new Entry(time(), 'cache_hit', 'ada@example.com');
    (new Privacy($settings))($entry);

    expect($entry->key)->toBe('ada@example.com');
});

it('shows users by name, never email or Gravatar', function (array $attributes, string $name) {
    $user = (new Privacy(new Settings))->user(stethoscopeUser(['id' => 7, 'email' => 'ada@example.com', ...$attributes]));

    expect($user)->toBe(['name' => $name, 'extra' => '#7', 'avatar' => null]);
})->with([
    [['fullName' => 'Ada Lovelace', 'username' => 'ada'], 'Ada Lovelace'],
    [['username' => 'ada'], 'ada'],
    [['username' => 'ada@example.com'], 'User #7'],
    [[], 'User #7'],
]);

it('shows emails only when switched on', function () {
    $settings = new Settings;
    $settings->showUserEmails = true;

    expect((new Privacy($settings))->user(stethoscopeUser(['id' => 7, 'email' => 'ada@example.com']))['extra'])->toBe('ada@example.com');
});
