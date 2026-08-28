<?php

declare(strict_types=1);

use Symplify\EasyCodingStandard\Config\ECSConfig;

return ECSConfig::configure()
    ->withPaths([
        __DIR__ . '/src',
        __DIR__ . '/templates',
        __DIR__ . '/tests',
        __DIR__ . '/ecs.php',
        __DIR__ . '/standards-sync.php',
    ])
    ->withSets([
        __DIR__ . '/templates/package/ecs.php',
    ]);
