<?php

declare(strict_types=1);

namespace JustinHoltWeb\Stethoscope\Recorders;

use CraftCms\Cms\Asset\Elements\Asset;
use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Element\Events\ElementSaved;
use CraftCms\Cms\Element\Events\ElementSaving;
use CraftCms\Cms\Entry\Elements\Entry;
use JustinHoltWeb\Stethoscope\Stethoscope;
use Throwable;

/**
 * Element saves per element type, section/volume (or Matrix field) and site, with
 * how long each save took. Revisions are skipped (they ride along with a save);
 * drafts are counted separately from live saves.
 */
class ElementSaves
{
    /** @var array<int, int> spl_object_id → hrtime */
    private array $started = [];

    public function __construct(private readonly Stethoscope $stethoscope) {}

    public function saving(ElementSaving $event): void
    {
        $this->started[spl_object_id($event->element)] = hrtime(true);
    }

    public function saved(ElementSaved $event): void
    {
        $id = spl_object_id($event->element);
        $start = $this->started[$id] ?? null;
        unset($this->started[$id]);

        $element = $event->element;

        if ($start === null || ! $this->stethoscope->sampled() || self::isRevision($element)) {
            return;
        }

        $this->stethoscope->record(Stethoscope::ELEMENT_SAVE, [
            self::type($element),
            self::group($element),
            Stethoscope::siteHandle($element->siteId ?? null),
            self::isDraft($element) ? 'draft' : 'live',
        ], Stethoscope::since($start), ['count', 'avg', 'max']);
    }

    public static function type(ElementInterface $element): string
    {
        return self::typeFromClass($element::class);
    }

    public static function typeFromClass(string $class): string
    {
        $handle = is_subclass_of($class, ElementInterface::class) ? $class::refHandle() : null;

        return $handle ?? strtolower(class_basename($class));
    }

    /** Section, volume or (for nested entries) the owner's field — never titles or user data. */
    public static function group(ElementInterface $element): ?string
    {
        try {
            return match (true) {
                $element instanceof Entry => $element->getSection()->handle
                    ?? ($element->getField() !== null ? 'field:'.$element->getField()->handle : null),
                $element instanceof Asset => $element->getVolumeId() !== null ? $element->getVolume()->handle : null,
                default => null,
            };
        } catch (Throwable) {
            return null;
        }
    }

    private static function isRevision(ElementInterface $element): bool
    {
        return $element->getIsRevision();
    }

    private static function isDraft(ElementInterface $element): bool
    {
        return $element->getIsDraft();
    }
}
