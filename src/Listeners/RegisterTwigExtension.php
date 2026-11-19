<?php

declare(strict_types=1);

namespace JustinHoltWeb\Stethoscope\Listeners;

use CraftCms\Cms\Twig\Events\TwigCreated;
use CraftCms\Cms\View\TemplateMode;
use JustinHoltWeb\Stethoscope\Twig\StethoscopeTwigExtension;

/** `{% stethoscope %}` for site templates (Blade gets `@stethoscope` in Plugin::registerBlade()). */
class RegisterTwigExtension
{
    public function handle(TwigCreated $event): void
    {
        if ($event->templateMode === TemplateMode::Site) {
            $event->twig->addExtension(new StethoscopeTwigExtension);
        }
    }
}
