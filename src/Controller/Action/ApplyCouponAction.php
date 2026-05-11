<?php

declare(strict_types=1);

namespace Setono\SyliusCouponUrlApplicationPlugin\Controller\Action;

use Setono\SyliusCouponUrlApplicationPlugin\Form\Type\ApplyCouponType;
use Sylius\Component\Core\Model\OrderInterface;
use Sylius\Component\Order\Context\CartContextInterface;
use Sylius\Component\Order\Model\OrderInterface as BaseOrderInterface;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\Response;
use Twig\Environment;
use Webmozart\Assert\Assert;

final readonly class ApplyCouponAction
{
    public function __construct(
        private FormFactoryInterface $formFactory,
        private Environment $twig,
        private CartContextInterface $cartContext,
    ) {
    }

    public function __invoke(): Response
    {
        /** @var OrderInterface|BaseOrderInterface $cart */
        $cart = $this->cartContext->getCart();
        Assert::isInstanceOf($cart, OrderInterface::class);

        $coupon = $cart->getPromotionCoupon()?->getCode();

        $form = $this->formFactory->createNamed('', ApplyCouponType::class, ['coupon' => $coupon], [
            'csrf_protection' => false,
        ]);

        return new Response($this->twig->render('@SetonoSyliusCouponUrlApplicationPlugin/shop/coupon.html.twig', [
            'form' => $form->createView(),
        ]));
    }
}
