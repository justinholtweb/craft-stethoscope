<?php

declare(strict_types=1);

use JustinHoltWeb\Stethoscope\Support\Key;

it('encodes and decodes key parts', function () {
    $key = Key::encode(['entry', 'news', 'default', null]);

    expect($key)->toBe('["entry","news","default",null]')
        ->and(Key::decode($key))->toBe(['entry', 'news', 'default', null]);
});

it('keeps slashes and unicode readable', function () {
    expect(Key::encode(['blog/_entry', 'Français']))->toBe('["blog/_entry","Français"]');
});

it('scrubs personal data unless told not to', function () {
    expect(Key::encode(['ada@example.com']))->toBe('["[email]"]')
        ->and(Key::encode(['ada@example.com'], redact: false))->toBe('["ada@example.com"]');
});

it('trims and caps long parts', function () {
    $decoded = Key::decode(Key::encode(['  '.str_repeat('x', 500).'  ']));

    expect(mb_strlen((string) $decoded[0]))->toBe(Key::MAX_PART_LENGTH);
});

it('decodes a non-JSON key as one part', function () {
    expect(Key::decode('plain'))->toBe(['plain']);
});

it('groups digits in free-form labels', function () {
    expect(Key::group('nav-42-footer-7'))->toBe('nav-*-footer-*');
});
