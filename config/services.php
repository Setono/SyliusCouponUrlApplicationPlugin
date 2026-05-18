<?php

declare(strict_types=1);

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use Setono\SyliusCouponUrlApplicationPlugin\Controller\Action\ApplyCouponAction;
use Setono\SyliusCouponUrlApplicationPlugin\EventSubscriber\ApplyCouponSubscriber;
use Setono\SyliusCouponUrlApplicationPlugin\Form\Type\ApplyCouponType;

return static function (ContainerConfigurator $container): void {
    $services = $container->services();

    $services->set(ApplyCouponAction::class)
        ->args([
            service('form.factory'),
            service('twig'),
            service('sylius.context.cart'),
        ])
        ->public()
        ->tag('controller.service_arguments')
    ;

    $services->set(ApplyCouponType::class)
        ->tag('form.type')
    ;

    $services->set(ApplyCouponSubscriber::class)
        ->args([
            service('sylius.repository.promotion_coupon'),
            service('sylius.checker.promotion_coupon_eligibility'),
            service('sylius.checker.promotion_eligibility'),
            service('sylius.context.cart'),
            service('sylius.order_processing.order_processor'),
            service('doctrine'),
        ])
        ->tag('kernel.event_subscriber')
    ;
};
