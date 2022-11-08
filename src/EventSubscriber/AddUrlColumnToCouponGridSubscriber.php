<?php

declare(strict_types=1);

namespace Setono\SyliusCouponUrlApplicationPlugin\EventSubscriber;

use Sylius\Component\Grid\Definition\Field;
use Sylius\Component\Grid\Event\GridDefinitionConverterEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

final class AddUrlColumnToCouponGridSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            'sylius.grid.admin_promotion_coupon' => 'add',
        ];
    }

    public function add(GridDefinitionConverterEvent $event): void
    {
        $field = Field::fromNameAndType('url', 'twig');
        $field->setOptions([
            'template' => '@SetonoSyliusCouponUrlApplicationPlugin/admin/promotion_coupon/grid/field/url.html.twig',
        ]);
        $field->setPath('.');
        $field->setLabel('setono_sylius_coupon_url_application.ui.url');
        $event->getGrid()->addField($field);
    }
}
