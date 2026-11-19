<?php

declare(strict_types=1);

namespace JustinHoltWeb\Stethoscope\Gql;

use CraftCms\Cms\Gql\GqlEntityRegistry;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;

/** The shape `stethoscopeSummary` returns: counts and timings only, no paths, names or people. */
class SummaryType
{
    public static function getType(): ObjectType
    {
        return GqlEntityRegistry::getOrCreate('StethoscopeSummary', fn () => new ObjectType([
            'name' => 'StethoscopeSummary',
            'description' => 'Stethoscope’s operational summary for a period (counts scaled by the sample rate).',
            'fields' => fn () => [
                'period' => ['type' => Type::nonNull(Type::string())],
                'site' => ['type' => Type::string(), 'description' => 'The site handle the figures are limited to, or null for all sites.'],
                'sampleRate' => ['type' => Type::nonNull(Type::int()), 'description' => '1 in N requests recorded.'],
                'totals' => ['type' => Type::nonNull(self::totalsType())],
                'elementSaves' => ['type' => Type::nonNull(Type::listOf(Type::nonNull(self::savesType()))), 'description' => 'Saves per element type, section/volume and site.'],
            ],
        ]));
    }

    private static function totalsType(): ObjectType
    {
        return GqlEntityRegistry::getOrCreate('StethoscopeTotals', fn () => new ObjectType([
            'name' => 'StethoscopeTotals',
            'description' => 'Totals for the period. Null when not recorded per site and a site was asked for.',
            'fields' => [
                'elementSaves' => ['type' => Type::int()],
                'elementQueries' => ['type' => Type::int()],
                'pageRenders' => ['type' => Type::int()],
                'avgPageRenderMs' => ['type' => Type::float()],
                'slowRenders' => ['type' => Type::int()],
                'templateCacheHits' => ['type' => Type::int()],
                'templateCacheMisses' => ['type' => Type::int()],
                'queueJobs' => ['type' => Type::int()],
                'failedJobs' => ['type' => Type::int()],
                'graphqlQueries' => ['type' => Type::int()],
                'searches' => ['type' => Type::int()],
                'transforms' => ['type' => Type::int()],
            ],
        ]));
    }

    private static function savesType(): ObjectType
    {
        return GqlEntityRegistry::getOrCreate('StethoscopeElementSaves', fn () => new ObjectType([
            'name' => 'StethoscopeElementSaves',
            'fields' => [
                'elementType' => ['type' => Type::nonNull(Type::string()), 'description' => 'entry, asset, user, …'],
                'group' => ['type' => Type::string(), 'description' => 'Section or volume handle (`field:<handle>` for nested entries).'],
                'site' => ['type' => Type::string()],
                'drafts' => ['type' => Type::nonNull(Type::boolean()), 'description' => 'Whether this row counts draft saves.'],
                'count' => ['type' => Type::nonNull(Type::int())],
                'avgMs' => ['type' => Type::nonNull(Type::float())],
                'maxMs' => ['type' => Type::nonNull(Type::int())],
            ],
        ]));
    }
}
