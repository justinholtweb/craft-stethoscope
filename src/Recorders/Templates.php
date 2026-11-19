<?php

declare(strict_types=1);

namespace JustinHoltWeb\Stethoscope\Recorders;

use CraftCms\Cms\View\Events\PageTemplateRendered;
use CraftCms\Cms\View\Events\PageTemplateRendering;
use CraftCms\Cms\View\Events\TemplateRendered;
use CraftCms\Cms\View\Events\TemplateRendering;
use CraftCms\Cms\View\TemplateMode;
use JustinHoltWeb\Stethoscope\Stethoscope;

/**
 * Front-end template render times (Twig and Blade alike: Craft's TemplateManager
 * fires the same events for both). Page templates are timed from
 * PageTemplateRendering to PageTemplateRendered; other renders through Craft
 * (`renderTemplate`, element partials) from TemplateRendering to TemplateRendered.
 * `{% include %}`s happen inside one render and aren't seen separately; wrap them
 * in `{% stethoscope %}` to time them.
 */
class Templates
{
    /** @var list<array{string, int, bool}> template, hrtime, is page */
    private array $stack = [];

    public function __construct(private readonly Stethoscope $stethoscope) {}

    /** The template being rendered right now (for unnamed `{% cache %}` blocks). */
    public function current(): ?string
    {
        $top = end($this->stack);

        return $top === false ? null : $top[0];
    }

    public function pageRendering(PageTemplateRendering $event): void
    {
        $this->push($event->template, $event->templateMode, true);
    }

    public function pageRendered(PageTemplateRendered $event): void
    {
        $this->pop($event->template, $event->templateMode, true);
    }

    public function rendering(TemplateRendering $event): void
    {
        $this->push($event->template, $event->templateMode, false);
    }

    public function rendered(TemplateRendered $event): void
    {
        $this->pop($event->template, $event->templateMode, false, $event->rendererName);
    }

    private function push(string $template, TemplateMode $mode, bool $page): void
    {
        if ($mode === TemplateMode::Site) {
            $this->stack[] = [$template, hrtime(true), $page];
        }
    }

    private function pop(string $template, TemplateMode $mode, bool $page, ?string $renderer = null): void
    {
        if ($mode !== TemplateMode::Site) {
            return;
        }

        // Find the matching frame; a cancelled render (isValid = false) leaves no pair behind.
        for ($i = count($this->stack) - 1; $i >= 0; $i--) {
            if ($this->stack[$i][0] === $template && $this->stack[$i][2] === $page) {
                [, $start] = $this->stack[$i];
                array_splice($this->stack, $i);

                // A page template's inner render is the same file: count the page once.
                if (! $page && $this->current() === $template) {
                    return;
                }

                $this->recordRender($template, Stethoscope::since($start), $page);

                return;
            }
        }
    }

    private function recordRender(string $template, int $ms, bool $page): void
    {
        if ($this->stethoscope->isBackgroundRequest() || ! $this->stethoscope->sampled()) {
            return;
        }

        $key = [Stethoscope::templateLabel($template), Stethoscope::siteHandle(), $page ? 'page' : 'partial'];
        $this->stethoscope->record(Stethoscope::RENDER, $key, $ms, ['count', 'avg', 'max']);

        if ($ms >= $this->stethoscope->settings()->slowTemplateThreshold()) {
            $this->stethoscope->record(Stethoscope::SLOW_RENDER, $key, $ms, ['count', 'max']);
        }
    }
}
