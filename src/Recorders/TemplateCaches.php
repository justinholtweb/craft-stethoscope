<?php

declare(strict_types=1);

namespace JustinHoltWeb\Stethoscope\Recorders;

use CraftCms\Cms\Cms;
use CraftCms\Cms\View\TemplateCaches as CraftTemplateCaches;
use JustinHoltWeb\Stethoscope\Stethoscope;
use JustinHoltWeb\Stethoscope\Support\Key;
use Override;
use Throwable;

/**
 * Craft's template-cache service with hit/miss recording. Bound in place of
 * Craft's (scoped, like core's), only when nothing else has replaced it. Both
 * `{% cache %}` and Blade's `@craftCache` resolve the service from the container.
 */
class TemplateCaches extends CraftTemplateCaches
{
    #[Override]
    public function getTemplateCache(string $key, bool $global, bool $registerResources = false): ?string
    {
        $body = parent::getTemplateCache($key, $global, $registerResources);

        try {
            if ($this->cachingEnabled($global)) {
                $stethoscope = app(Stethoscope::class);

                if (! $stethoscope->isBackgroundRequest() && $stethoscope->sampled()) {
                    $stethoscope->record($body === null ? Stethoscope::CACHE_MISS : Stethoscope::CACHE_HIT, [
                        self::label($key),
                        Stethoscope::siteHandle(),
                    ]);
                }
            }
        } catch (Throwable) {
            // Never let monitoring break a cached block.
        }

        return $body;
    }

    /**
     * Unnamed `{% cache %}` / `@craftCache` blocks get a random compile-time key: label those by the
     * template they're in. Named keys have digits collapsed (`nav-42` → `nav-*`).
     */
    private static function label(string $key): string
    {
        if (preg_match('/^([A-Za-z0-9]{36}|blade:[0-9a-f]{32})$/D', $key)) {
            return '(unnamed) '.(app(Templates::class)->current() ?? '?');
        }

        return Key::group($key);
    }

    /** Mirrors core's private isTemplateCachingEnabled(): a null body is only a miss when caching is on. */
    private function cachingEnabled(bool $global): bool
    {
        if (! Cms::config()->enableTemplateCaching) {
            return false;
        }

        $request = request();

        if ($request->isPreview() || $request->getHadToken()) {
            return false;
        }

        return $global || ! app()->runningInConsole();
    }
}
