<?php

declare(strict_types=1);

namespace Setono\SyliusCouponUrlApplicationPlugin\Tests\Unit\Controller\Action;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Setono\SyliusCouponUrlApplicationPlugin\Controller\Action\ApplyCouponAction;
use Setono\SyliusCouponUrlApplicationPlugin\Form\Type\ApplyCouponType;
use Sylius\Component\Core\Model\OrderInterface;
use Sylius\Component\Order\Context\CartContextInterface;
use Sylius\Component\Promotion\Model\PromotionCouponInterface;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Twig\Environment;

final class ApplyCouponActionTest extends TestCase
{
    use ProphecyTrait;

    private const TEMPLATE = '@SetonoSyliusCouponUrlApplicationPlugin/shop/coupon.html.twig';

    /** @var ObjectProphecy<FormFactoryInterface> */
    private ObjectProphecy $formFactory;

    /** @var ObjectProphecy<Environment> */
    private ObjectProphecy $twig;

    /** @var ObjectProphecy<CartContextInterface> */
    private ObjectProphecy $cartContext;

    protected function setUp(): void
    {
        $this->formFactory = $this->prophesize(FormFactoryInterface::class);
        $this->twig = $this->prophesize(Environment::class);
        $this->cartContext = $this->prophesize(CartContextInterface::class);
    }

    #[Test]
    public function it_renders_the_coupon_form_when_the_cart_has_no_coupon(): void
    {
        $cart = $this->prophesize(OrderInterface::class);
        $cart->getPromotionCoupon()->willReturn(null);
        $this->cartContext->getCart()->willReturn($cart->reveal());

        $formView = new FormView();
        $form = $this->prophesize(FormInterface::class);
        $form->createView()->willReturn($formView);

        $this->formFactory
            ->createNamed('', ApplyCouponType::class, ['coupon' => null], ['csrf_protection' => false])
            ->willReturn($form->reveal())
        ;

        $this->twig
            ->render(self::TEMPLATE, ['form' => $formView])
            ->willReturn('<rendered>')
        ;

        $response = ($this->action())();

        self::assertSame(200, $response->getStatusCode());
        self::assertSame('<rendered>', $response->getContent());
    }

    #[Test]
    public function it_pre_populates_the_form_with_the_existing_cart_coupon_code(): void
    {
        $coupon = $this->prophesize(PromotionCouponInterface::class);
        $coupon->getCode()->willReturn('CHRISTMAS_SALE');

        $cart = $this->prophesize(OrderInterface::class);
        $cart->getPromotionCoupon()->willReturn($coupon->reveal());
        $this->cartContext->getCart()->willReturn($cart->reveal());

        $formView = new FormView();
        $form = $this->prophesize(FormInterface::class);
        $form->createView()->willReturn($formView);

        $this->formFactory
            ->createNamed('', ApplyCouponType::class, ['coupon' => 'CHRISTMAS_SALE'], ['csrf_protection' => false])
            ->willReturn($form->reveal())
        ;

        $this->twig
            ->render(self::TEMPLATE, ['form' => $formView])
            ->willReturn('<rendered with prefilled coupon>')
        ;

        $response = ($this->action())();

        self::assertSame(200, $response->getStatusCode());
        self::assertSame('<rendered with prefilled coupon>', $response->getContent());
    }

    private function action(): ApplyCouponAction
    {
        return new ApplyCouponAction(
            $this->formFactory->reveal(),
            $this->twig->reveal(),
            $this->cartContext->reveal(),
        );
    }
}
