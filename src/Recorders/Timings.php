<?php

declare(strict_types=1);

namespace JustinHoltWeb\Stethoscope\Recorders;

use JustinHoltWeb\Stethoscope\Stethoscope;

/**
 * `{% stethoscope 'label' %}…{% endstethoscope %}` and `@stethoscope('label')…@endstethoscope`:
 * time any part of a template (an include, a loop, a macro call). Both engines
 * call start()/stop() here, so they record identically.
 */
class Timings
{
    /** @var list<array{string, int}|null> */
    private array $stack = [];

    public function __construct(
        private readonly Stethoscope $stethoscope,
        private readonly Templates $templates,
    ) {}

    public function start(mixed $label): void
    {
        $label = is_scalar($label) ? trim((string) $label) : '';
        $this->stack[] = $label === '' ? null : [$label, hrtime(true)];
    }

    public function stop(): void
    {
        $frame = array_pop($this->stack);

        if ($frame === null || $this->stethoscope->isBackgroundRequest() || ! $this->stethoscope->sampled()) {
            return;
        }

        [$label, $start] = $frame;

        $this->stethoscope->record(Stethoscope::TIMING, [
            $label,
            Stethoscope::siteHandle(),
            $this->templates->current(),
        ], Stethoscope::since($start), ['count', 'avg', 'max']);
    }
}
