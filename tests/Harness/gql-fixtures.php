<?php

// Stethoscope harness: GraphQL schemas + tokens (idempotent). Token = "stethoscope{name}" padded to 32.
//   full  the primary site (+ stethoscopefr when it exists), the stethoscopeLog section, the summary component
//   none  the same, without the summary component
// Run with: ddev artisan tinker --execute "require 'storage/stethoscope-gql-fixtures.php';"

use CraftCms\Cms\Gql\Data\GqlSchema;
use CraftCms\Cms\Gql\Data\GqlToken;
use CraftCms\Cms\Support\Facades\Gql;
use CraftCms\Cms\Support\Facades\Sections;
use CraftCms\Cms\Support\Facades\Sites;

$sites = ['sites.'.Sites::getPrimarySite()->uid.':read'];
if ($fr = Sites::getSiteByHandle('stethoscopefr', true)) {
    $sites[] = 'sites.'.$fr->uid.':read';
}
$section = 'sections.'.Sections::getSectionByHandle('stethoscopeLog')->uid.':read';

foreach (['full' => [...$sites, $section, 'stethoscope.summary:read'], 'none' => [...$sites, $section]] as $name => $scope) {
    $schema = collect(Gql::getSchemas())->first(fn ($s) => $s->name === "stethoscope_{$name}") ?? new GqlSchema;
    $schema->name = "stethoscope_{$name}";
    $schema->scope = $scope;
    Gql::saveSchema($schema) || throw new RuntimeException(json_encode($schema->errors()->all()));

    $token = collect(Gql::getTokens())->first(fn ($t) => $t->name === "stethoscope_{$name}") ?? new GqlToken;
    $token->name = "stethoscope_{$name}";
    $token->schemaId = $schema->id;
    $token->accessToken = str_pad("stethoscope{$name}", 32, '0');
    Gql::saveToken($token) || throw new RuntimeException(json_encode($token->errors()->all()));
}

Gql::invalidateCaches();
echo 'ok';
