<?php

// Removes the `stethoscopefr` site (and only that site). Run under bin/harness-lock.
// Run with: ddev artisan tinker --execute "require 'storage/stethoscope-multisite-cleanup.php';"

use CraftCms\Cms\Support\Facades\Sites;

if ($fr = Sites::getSiteByHandle('stethoscopefr', true)) {
    Sites::deleteSite($fr) || throw new RuntimeException('could not delete stethoscopefr');
}

echo 'cleaned';
