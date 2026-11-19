<?php

declare(strict_types=1);

use JustinHoltWeb\Stethoscope\Support\Redactor;

it('scrubs email addresses', function (string $in, string $out) {
    expect(Redactor::scrub($in))->toBe($out);
})->with([
    ['login|ada@example.com|1.2.3.4', 'login|[email]|[ip]'],
    ['key:Ada.Lovelace+craft@mail.example.co.uk:x', 'key:[email]:x'],
    ['no email here', 'no email here'],
    ['', ''],
]);

it('scrubs IPv4 addresses but not version numbers or class names', function (string $in, string $out) {
    expect(Redactor::scrub($in))->toBe($out);
})->with([
    ['throttle:10.0.0.255', 'throttle:[ip]'],
    ['craft 6.0.0.1.2', 'craft 6.0.0.1.2'],
    ['999.1.1.1', '999.1.1.1'],
    ['App\\Jobs\\Sync::handle', 'App\\Jobs\\Sync::handle'],
    ['template::abc::1::/blog', 'template::abc::1::/blog'],
]);

it('scrubs IPv6 addresses', function (string $in, string $out) {
    expect(Redactor::scrub($in))->toBe($out);
})->with([
    ['rate:2001:db8:85a3:0:0:8a2e:370:7334', 'rate:[ip]'],
    ['rate|fe80::1|x', 'rate|[ip]|x'],
    ['rate|::1', 'rate|::1'],
]);

it('strips query strings, fragments and credentials from URLs', function (string $in, string $out) {
    expect(Redactor::stripQuery($in))->toBe($out);
})->with([
    ['https://api.example.com/v1/x?token=s3cret', 'https://api.example.com/v1/x'],
    ['["GET","https:\/\/user:pw@api.example.com\/a?b=c#frag"]', '["GET","https:\/\/api.example.com\/a"]'],
    ['/blog/post', '/blog/post'],
]);

it('leaves times and short colon lists alone', function () {
    expect(Redactor::scrub('ran at 12:30:45'))->toBe('ran at 12:30:45')
        ->and(Redactor::scrub('a:b:c'))->toBe('a:b:c');
});
