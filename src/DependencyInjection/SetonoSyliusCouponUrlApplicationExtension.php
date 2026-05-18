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
                    'actions' => [
                        'item' => [
                            'show_url' => [
                                'type' => 'default',
                                'label' => 'setono_sylius_coupon_url_application.ui.show_url',
                                'icon' => 'tabler:link',
                                'template' => '@SetonoSyliusCouponUrlApplicationPlugin/admin/promotion_coupon/grid/action/show_url.html.twig',
                            ],
                        ],
                    ],
                ],
            ],
        ]);

        $container->prependExtensionConfig('sylius_twig_hooks', [
            'hooks' => [
                'sylius_admin.promotion_coupon.index#javascripts' => [
                    'setono_sylius_coupon_url_application_modal' => [
                        'template' => '@SetonoSyliusCouponUrlApplicationPlugin/admin/promotion_coupon/_modal.html.twig',
                    ],
                    'setono_sylius_coupon_url_application_scripts' => [
                        'template' => '@SetonoSyliusCouponUrlApplicationPlugin/admin/promotion_coupon/_javascripts.html.twig',
                    ],
                ],
            ],
        ]);
    }
}
