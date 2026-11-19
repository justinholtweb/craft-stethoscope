<?php

declare(strict_types=1);

namespace JustinHoltWeb\Stethoscope\Listeners;

use CraftCms\Cms\Gql\Events\GqlSchemaComponentsResolving;
use CraftCms\Cms\User\Elements\User;
use JustinHoltWeb\Stethoscope\Gql\SummaryResolver;
use JustinHoltWeb\Stethoscope\Plugin;

use function CraftCms\Cms\t;

/**
 * “View Stethoscope’s operational summary” — only offered when the plugin's
 * `enableGraphql` setting is on. User-authenticated GraphQL gets it only with
 * the same permission that opens the dashboard.
 */
class RegisterGqlComponents
{
    public function handle(GqlSchemaComponentsResolving $event): void
    {
        if (! SummaryResolver::enabled()) {
            return;
        }

        $event->queries['Stethoscope'] = [
            SummaryResolver::COMPONENT.':read' => [
                'label' => t('View Stethoscope’s operational summary', category: 'stethoscope'),
                'info' => t('Adds the `stethoscopeSummary` query: element saves, renders, cache, queue, GraphQL and search counts. Operational data — give it to trusted schemas only.', category: 'stethoscope'),
                'authenticated' => fn (User $user): bool => $user->can(Plugin::PERMISSION),
            ],
        ];
    }
}
