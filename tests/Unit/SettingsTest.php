<?php

declare(strict_types=1);

use JustinHoltWeb\Stethoscope\Settings;

it('has privacy-first defaults', function () {
    $settings = new Settings;

    expect($settings->sampleRate)->toBe(1)
        ->and($settings->redactPersonalData)->toBeTrue()
        ->and($settings->recordSearchTerms)->toBeFalse()
        ->and($settings->showUserEmails)->toBeFalse()
        ->and($settings->enableGraphql)->toBeFalse();
});

it('clamps the sample rate and thresholds', function (?int $in, int $out) {
    $settings = new Settings;
    $settings->sampleRate = $in;

    expect($settings->rate())->toBe($out);
})->with([[null, 1], [0, 1], [-5, 1], [1, 1], [10, 10], [5000, 1000]]);

it('falls back to default thresholds when blank', function () {
    $settings = new Settings;
    $settings->slowTemplateMs = null;
    $settings->slowGraphqlMs = -3;

    expect($settings->slowTemplateThreshold())->toBe(250)
        ->and($settings->slowGraphqlThreshold())->toBe(0);
});

it('treats a blank sidebar label as the plugin name', function () {
    $settings = new Settings;
    $settings->cpNavLabel = '   ';

    expect($settings->navLabel())->toBeNull();

    $settings->cpNavLabel = ' Health ';
    expect($settings->navLabel())->toBe('Health');
});
