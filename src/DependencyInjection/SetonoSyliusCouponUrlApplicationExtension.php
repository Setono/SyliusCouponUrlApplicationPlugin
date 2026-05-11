<?php

declare(strict_types=1);

namespace Setono\SyliusCouponUrlApplicationPlugin\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\DependencyInjection\Loader\PhpFileLoader;

final class SetonoSyliusCouponUrlApplicationExtension extends Extension implements PrependExtensionInterface
{
    public function load(array $configs, ContainerBuilder $container): void
    {
        $loader = new PhpFileLoader($container, new FileLocator(__DIR__ . '/../../config'));
        $loader->load('services.php');
    }

    public function prepend(ContainerBuilder $container): void
    {
        $container->prependExtensionConfig('sylius_grid', [
            'grids' => [
                'sylius_admin_promotion_coupon' => [
                    'fields' => [
                        'url' => [
                            'type' => 'twig',
                            'label' => 'setono_sylius_coupon_url_application.ui.url',
                            'path' => '.',
                            'options' => [
                                'template' => '@SetonoSyliusCouponUrlApplicationPlugin/admin/promotion_coupon/grid/field/url.html.twig',
                            ],
                        ],
                    ],
                ],
            ],
        ]);

        $container->prependExtensionConfig('sylius_twig_hooks', [
            'hooks' => [
                'sylius_admin.promotion_coupon.index#javascripts' => [
                    'setono_sylius_coupon_url_application_scripts' => [
                        'template' => '@SetonoSyliusCouponUrlApplicationPlugin/admin/promotion_coupon/_javascripts.html.twig',
                    ],
                ],
            ],
        ]);
    }
}
