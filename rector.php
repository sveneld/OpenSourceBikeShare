<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;

return RectorConfig::configure()
    ->withPaths([
        __DIR__ . '/connectors',
        __DIR__ . '/src',
        __DIR__ . '/tests',
    ])
    // uncomment to reach your current PHP version
    ->withPhpVersion(\Rector\ValueObject\PhpVersion::PHP_74)
    ->withSets([
        \Rector\Set\ValueObject\SetList::PHP_74,
    ]);
