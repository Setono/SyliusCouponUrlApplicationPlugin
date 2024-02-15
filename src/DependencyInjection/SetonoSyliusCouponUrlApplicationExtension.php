<?php

declare(strict_types=1);

namespace Setono\SyliusCouponUrlApplicationPlugin\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;

final class SetonoSyliusCouponUrlApplicationExtension extends Extension implements PrependExtensionInterface
{
    public function load(array $configs, ContainerBuilder $container): void
    {
        $loader = new XmlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
        $loader->load('services.xml');
    }

    public function prepend(ContainerBuilder $container): void
    {
        $container->prependExtensionConfig('sylius_ui', [
            'events' => [
                'sylius.admin.promotion_coupon.index.content' => [
                    'blocks' => [
                        'javascripts' => [
                            'template' => '@SetonoSyliusCouponUrlApplicationPlugin/admin/promotion_coupon/_javascripts.html.twig',
                        ],
                    ],
                ],
            ],
        ]);
    }
}
