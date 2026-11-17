<?php

declare(strict_types=1);

namespace JustinHoltWeb\Stethoscope;

use CraftCms\Cms\Plugin\Plugin as BasePlugin;
use CraftCms\Cms\Ui\Controls\Number;
use CraftCms\Cms\Ui\Nodes\Field;
use CraftCms\Cms\Ui\Ui;
use CraftCms\Cms\Ui\UiContext;
use Override;

use function CraftCms\Cms\t;

/**
 * Stethoscope — Laravel Pulse cards for Craft — element queries, slow templates, cache hit rates and queue depth, per section.
 *
 * See PLAN.md for the build phases.
 *
 * @method Settings getSettings()
 */
class Plugin extends BasePlugin
{
    public bool $hasCpSettings = true;

    #[Override]
    protected static function createSettings(): Settings
    {
        return new Settings;
    }

    #[Override]
    public function settingsUi(UiContext $context = new UiContext): Ui
    {
        return Ui::make([
            Field::make(t('Sample rate', category: 'stethoscope'), Number::make('sampleRate'))
                ->instructions(t('Record 1 in N requests (1 = every request).', category: 'stethoscope')),
            Field::make(t('Slow template threshold (ms)', category: 'stethoscope'), Number::make('slowTemplateMs'))
                ->instructions(t('Template renders slower than this are recorded.', category: 'stethoscope')),
        ]);
    }
}
