<?php

declare(strict_types=1);

use Symplify\EasyCodingStandard\Config\ECSConfig;

return static function (ECSConfig $configurator): void {
    $configurator->import('vendor/sylius-labs/coding-standard/ecs.php');
    $configurator->paths([
        'src',
        'tests',
    ]);
    $configurator->skip([
        'tests/Application/node_modules/**',
        'tests/Application/var/**',
    ]);
};
