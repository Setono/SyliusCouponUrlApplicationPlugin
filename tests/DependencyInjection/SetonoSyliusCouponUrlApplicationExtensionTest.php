<?php

declare(strict_types=1);

namespace Setono\SyliusCouponUrlApplicationPlugin\Tests\DependencyInjection;

use Matthias\SymfonyDependencyInjectionTest\PhpUnit\AbstractExtensionTestCase;
use Setono\SyliusCouponUrlApplicationPlugin\DependencyInjection\SetonoSyliusCouponUrlApplicationExtension;

/**
 * See examples of tests and configuration options here: https://github.com/SymfonyTest/SymfonyDependencyInjectionTest
 */
final class SetonoSyliusCouponUrlApplicationExtensionTest extends AbstractExtensionTestCase
{
    protected function getContainerExtensions(): array
    {
        return [
            new SetonoSyliusCouponUrlApplicationExtension(),
        ];
    }

    /**
     * @test
     */
    public function after_loading_the_correct_parameter_has_been_set(): void
    {
        $this->load();
    }
}
