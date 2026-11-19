<?php

declare(strict_types=1);

namespace JustinHoltWeb\Stethoscope\Gql;

use CraftCms\Cms\Gql\GqlHelper;
use CraftCms\Cms\Support\Facades\Sites;
use CraftCms\Cms\View\CacheCollectors\DependencyCollector;
use GraphQL\Error\UserError;
use Illuminate\Support\Facades\Log;
use JustinHoltWeb\Stethoscope\Settings;
use JustinHoltWeb\Stethoscope\Summary;
use Throwable;

class SummaryResolver
{
    public const string COMPONENT = 'stethoscope.summary';

    public static function enabled(): bool
    {
        return app(Settings::class)->enableGraphql;
    }

    public static function allowed(): bool
    {
        return self::enabled() && GqlHelper::canSchema(self::COMPONENT);
    }

    /**
     * Limited to the sites the schema can read: per-site rows outside them are dropped, and
     * site-less totals are null unless the schema reads every site.
     *
     * @param  array<string, mixed>  $args
     * @return array<string, mixed>
     */
    public static function resolve(mixed $source, array $args): array
    {
        if (! self::allowed()) {
            throw new UserError('This schema can’t read Stethoscope’s summary.');
        }

        $period = isset($args['period']) ? (string) $args['period'] : '1_hour';

        if (! isset(Summary::PERIODS[$period])) {
            throw new UserError('Unknown period “'.mb_substr($period, 0, 20).'”. Use one of: '.implode(', ', array_keys(Summary::PERIODS)).'.');
        }

        $site = null;
        $allowed = array_map(fn ($s): string => $s->handle, GqlHelper::getAllowedSites());

        if (isset($args['site'])) {
            $handle = (string) $args['site'];
            $site = in_array($handle, $allowed, true) ? $handle : null;

            // Unknown and not-allowed read the same, so a schema can't probe for site handles.
            if ($site === null) {
                throw new UserError('This schema can’t read the “'.mb_substr($handle, 0, 64).'” site.');
            }
        }

        // Live numbers: let Craft cache this answer for a few seconds at most.
        app(DependencyCollector::class)->setExpiryDate(now()->addSeconds(5));

        try {
            // No site asked for: all sites only when the schema can read every one; otherwise
            // just the readable ones, and nothing that isn't recorded per site.
            $every = array_map(fn ($s): string => $s->handle, Sites::getAllSites(true)->all());
            $sites = $site === null && array_diff($every, $allowed) !== [] ? $allowed : null;

            return app(Summary::class)->for($period, $site, $sites);
        } catch (Throwable $e) {
            Log::warning('Stethoscope summary failed: '.$e->getMessage());

            throw new UserError('Stethoscope’s data is temporarily unavailable.');
        }
    }
}
