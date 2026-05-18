<?php

declare(strict_types=1);

namespace Setono\SyliusCouponUrlApplicationPlugin\Tests\Unit\DependencyInjection;

use Matthias\SymfonyDependencyInjectionTest\PhpUnit\AbstractExtensionTestCase;
use PHPUnit\Framework\Attributes\Test;
use Setono\SyliusCouponUrlApplicationPlugin\Controller\Action\ApplyCouponAction;
use Setono\SyliusCouponUrlApplicationPlugin\DependencyInjection\SetonoSyliusCouponUrlApplicationExtension;
use Setono\SyliusCouponUrlApplicationPlugin\EventSubscriber\ApplyCouponSubscriber;
use Setono\SyliusCouponUrlApplicationPlugin\Form\Type\ApplyCouponType;

final class SetonoSyliusCouponUrlApplicationExtensionTest extends AbstractExtensionTestCase
{
    protected function getContainerExtensions(): array
    {
        return [
            new SetonoSyliusCouponUrlApplicationExtension(),
        ];
    }

    #[Test]
    public function after_loading_the_correct_parameter_has_been_set(): void
    {
        $this->load();

        $this->assertContainerBuilderHasService(ApplyCouponAction::class);
        $this->assertContainerBuilderHasService(ApplyCouponSubscriber::class);
        $this->assertContainerBuilderHasService(ApplyCouponType::class);
    }
}
