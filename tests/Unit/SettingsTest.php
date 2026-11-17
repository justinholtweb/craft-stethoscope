<?php

declare(strict_types=1);

use JustinHoltWeb\Stethoscope\Settings;

it('has sensible defaults', function () {
    $settings = new Settings;

    expect($settings->sampleRate)->toBe(1);
});
