<?php

declare(strict_types=1);

namespace JustinHoltWeb\Stethoscope\Twig;

use Twig\Extension\AbstractExtension;

class StethoscopeTwigExtension extends AbstractExtension
{
    public function getTokenParsers(): array
    {
        return [new StethoscopeTokenParser];
    }
}
