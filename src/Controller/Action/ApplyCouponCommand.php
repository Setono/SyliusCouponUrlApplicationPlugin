<?php

declare(strict_types=1);

namespace Setono\SyliusCouponUrlApplicationPlugin\Controller\Action;

use Sylius\Component\Promotion\Model\PromotionCouponInterface;

final class ApplyCouponCommand
{
    /**
     * The coupon code to activate
     */
    public ?PromotionCouponInterface $coupon = null;

    /**
     * The URL where the visitor should be redirected if the coupon was successfully applied to the order
     */
    public ?string $redirect = null;
}
