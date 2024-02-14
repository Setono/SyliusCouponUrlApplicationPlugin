<?php

declare(strict_types=1);

namespace Setono\SyliusCouponUrlApplicationPlugin\Controller\Action;

use Setono\SyliusCouponUrlApplicationPlugin\Form\Type\ApplyCouponType;
use Sylius\Component\Core\Model\OrderInterface;
use Sylius\Component\Order\Context\CartContextInterface;
use Sylius\Component\Order\Model\OrderInterface as BaseOrderInterface;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;
use Twig\Environment;
use Webmozart\Assert\Assert;

final class ApplyCouponAction
{
    public function __construct(
        private readonly FormFactoryInterface $formFactory,
        private readonly Environment $twig,
        private readonly CartContextInterface $cartContext,
        private readonly RequestStack $requestStack,
    ) {
    }

    public function __invoke(Request $request): Response
    {
        $isMainRequest = $request === $this->requestStack->getMainRequest();

        /** @var OrderInterface|BaseOrderInterface $cart */
        $cart = $this->cartContext->getCart();
        Assert::isInstanceOf($cart, OrderInterface::class);

        $coupon = $cart->getPromotionCoupon()?->getCode();

        $form = $this->formFactory->createNamed('', ApplyCouponType::class, ['coupon' => $coupon], [
            'csrf_protection' => false,
        ]);

        if ($isMainRequest && null !== $coupon) {
            $session = $request->getSession();
            if ($session instanceof Session) {
                $session->getFlashBag()->add('info', [
                    'message' => 'setono_sylius_coupon_url_application.coupon_already_applied',
                    'parameters' => ['%code%' => $coupon],
                ]);
            }
        }

        $template = $isMainRequest ? '@SetonoSyliusCouponUrlApplicationPlugin/shop/coupon.html.twig' : '@SetonoSyliusCouponUrlApplicationPlugin/shop/partial/coupon.html.twig';

        return new Response($this->twig->render($template, [
            'form' => $form->createView(),
        ]));
    }
}
