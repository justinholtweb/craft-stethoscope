<?php

declare(strict_types=1);

namespace JustinHoltWeb\Stethoscope\Recorders;

use CraftCms\Cms\Asset\Events\AfterGenerateTransform;
use CraftCms\Cms\Image\Data\ImageTransform;
use JustinHoltWeb\Stethoscope\Stethoscope;
use Throwable;

/**
 * Image transforms generated, per named transform (or “custom”) and volume.
 * A spike here after a deploy means transforms are being regenerated.
 */
class Transforms
{
    public function __construct(private readonly Stethoscope $stethoscope) {}

    public function generated(AfterGenerateTransform $event): void
    {
        if (! $this->stethoscope->sampled()) {
            return;
        }

        try {
            $volume = $event->asset->getVolume()->handle;
        } catch (Throwable) {
            $volume = null;
        }

        $this->stethoscope->record(Stethoscope::TRANSFORM, [
            self::name($event->transform),
            $volume,
        ]);
    }

    /** @param  ImageTransform|string|array<string, mixed>|null  $transform */
    public static function name(ImageTransform|string|array|null $transform): string
    {
        return match (true) {
            $transform instanceof ImageTransform => $transform->handle ?: 'custom',
            is_string($transform) && preg_match('/^[a-zA-Z][\w\-]{0,63}$/D', $transform) === 1 => $transform,
            default => 'custom',
        };
    }
}
