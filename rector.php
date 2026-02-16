<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\PHPUnit\Set\PHPUnitSetList;
use Rector\Set\ValueObject\SetList;
use Rector\ValueObject\PhpVersion;

return RectorConfig::configure()
    ->withPaths([
        __DIR__ . '/config',
        __DIR__ . '/src',
        __DIR__ . '/tests',
    ])
    ->withPhpVersion(PhpVersion::PHP_84)
    ->withSets([
        SetList::PHP_84,
        PHPUnitSetList::PHPUNIT_120,
        SetList::CODE_QUALITY,
        SetList::CODING_STYLE,
        SetList::PHP_POLYFILLS,
    ])
    ->withComposerBased(
        twig: true,
        symfony: true,
        phpunit: false,
    )
    ->withImportNames(
        importNames: true,
        importDocBlockNames: true,
        importShortClasses: false,
        removeUnusedImports: true,
    )
;
