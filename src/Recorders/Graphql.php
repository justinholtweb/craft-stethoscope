<?php

declare(strict_types=1);

namespace JustinHoltWeb\Stethoscope\Recorders;

use CraftCms\Cms\Gql\Events\ExecutedGqlQuery;
use CraftCms\Cms\Gql\Events\GqlQueryExecuting;
use JustinHoltWeb\Stethoscope\Stethoscope;

/**
 * GraphQL queries by schema and operation name, with duration (cached answers
 * included: they're what the client waited for). Never the query text or
 * variables: those can carry search terms, emails or tokens.
 */
class Graphql
{
    /** @var list<int> */
    private array $started = [];

    public function __construct(private readonly Stethoscope $stethoscope) {}

    public function executing(GqlQueryExecuting $event): void
    {
        $this->started[] = hrtime(true);
    }

    public function executed(ExecutedGqlQuery $event): void
    {
        $start = array_pop($this->started);

        if ($start === null || ! $this->stethoscope->sampled()) {
            return;
        }

        $ms = Stethoscope::since($start);
        $key = [
            $event->schema->name ?: ($event->schema->isPublic ? 'Public Schema' : 'Schema'),
            self::operation($event->operationName),
            Stethoscope::siteHandle(),
        ];

        $this->stethoscope->record(Stethoscope::GRAPHQL, $key, $ms, ['count', 'avg', 'max']);

        if ($ms >= $this->stethoscope->settings()->slowGraphqlThreshold()) {
            $this->stethoscope->record(Stethoscope::SLOW_GRAPHQL, $key, $ms, ['count', 'max']);
        }
    }

    /** GraphQL names are `[_A-Za-z][_0-9A-Za-z]*`; anything else (or nothing) is “(anonymous)”. */
    public static function operation(?string $name): string
    {
        return $name !== null && preg_match('/^[_A-Za-z][_0-9A-Za-z]{0,99}$/D', $name) ? $name : '(anonymous)';
    }
}
