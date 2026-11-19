<?php

declare(strict_types=1);

use JustinHoltWeb\Stethoscope\Recorders\Graphql;
use JustinHoltWeb\Stethoscope\Recorders\Searches;
use JustinHoltWeb\Stethoscope\Recorders\Transforms;
use JustinHoltWeb\Stethoscope\Stethoscope;

it('keeps only valid GraphQL operation names', function (?string $in, string $out) {
    expect(Graphql::operation($in))->toBe($out);
})->with([
    ['GetNews', 'GetNews'],
    ['_private1', '_private1'],
    [null, '(anonymous)'],
    ['', '(anonymous)'],
    ['bad name', '(anonymous)'],
    ['1starts', '(anonymous)'],
    [str_repeat('a', 101), '(anonymous)'],
]);

it('names transforms without leaking parameters', function (mixed $in, string $out) {
    expect(Transforms::name($in))->toBe($out);
})->with([
    ['thumb', 'thumb'],
    [['width' => 100], 'custom'],
    [null, 'custom'],
    ['../../etc', 'custom'],
]);

it('normalises recorded search terms', function () {
    expect(Searches::term("  Hello\n  World  "))->toBe('hello world')
        ->and(mb_strlen(Searches::term(str_repeat('é', 300))))->toBe(100);
});

it('labels the index template', function () {
    expect(Stethoscope::templateLabel(''))->toBe('index')
        ->and(Stethoscope::templateLabel('blog/_entry'))->toBe('blog/_entry');
});

it('lists every entry type once, all prefixed', function () {
    expect(Stethoscope::TYPES)->toHaveCount(count(array_unique(Stethoscope::TYPES)))
        ->and(array_filter(Stethoscope::TYPES, fn (string $t) => ! str_starts_with($t, 'stethoscope_')))->toBe([]);
});
