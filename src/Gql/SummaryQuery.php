<?php

declare(strict_types=1);

namespace JustinHoltWeb\Stethoscope\Gql;

use CraftCms\Cms\Gql\Queries\Query;
use GraphQL\Type\Definition\FieldDefinition;
use GraphQL\Type\Definition\Type;

/**
 * `stethoscopeSummary` — off unless the `enableGraphql` setting is on AND the
 * schema has “View Stethoscope’s operational summary”.
 *
 * @phpstan-import-type UnnamedFieldDefinitionConfig from FieldDefinition
 */
class SummaryQuery extends Query
{
    /** @return array<string, UnnamedFieldDefinitionConfig> */
    public static function getQueries(bool $checkToken = true): array
    {
        if (! SummaryResolver::enabled() || ($checkToken && ! SummaryResolver::allowed())) {
            return [];
        }

        return [
            'stethoscopeSummary' => [
                'type' => Type::nonNull(SummaryType::getType()),
                'args' => [
                    'period' => ['name' => 'period', 'type' => Type::string(), 'description' => '`1_hour` (default), `6_hours`, `24_hours` or `7_days`.'],
                    'site' => ['name' => 'site', 'type' => Type::string(), 'description' => 'A site handle the schema can read; omit for all sites.'],
                ],
                'resolve' => [SummaryResolver::class, 'resolve'],
                'description' => 'Stethoscope’s operational summary (Laravel Pulse data): counts and timings, no paths or personal data.',
            ],
        ];
    }
}
