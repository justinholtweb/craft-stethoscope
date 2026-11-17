<?php

declare(strict_types=1);

namespace JustinHoltWeb\Stethoscope;

use CraftCms\Cms\Plugin\PluginSettings;

/**
 * Blank-able properties are nullable: Laravel's ConvertEmptyStringsToNull middleware
 * turns an empty field into null before Craft assigns it.
 */
class Settings extends PluginSettings
{
    /** Sample rate. Record 1 in N requests (1 = every request). */
    public int $sampleRate = 1;

    /** Slow template threshold (ms). Template renders slower than this are recorded. */
    public int $slowTemplateMs = 250;
}
