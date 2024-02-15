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

        $this->assertContainerBuilderHasService('setono_sylius_coupon_url_application.controller.action.apply_coupon');
        $this->assertContainerBuilderHasService('setono_sylius_coupon_url_application.event_subscriber.admin.add_url_column_to_coupon_grid');
        $this->assertContainerBuilderHasService('setono_sylius_coupon_url_application.event_subscriber.apply_coupon');
        $this->assertContainerBuilderHasService('setono_sylius_coupon_url_application.form.type.apply_coupon');
    }
}
