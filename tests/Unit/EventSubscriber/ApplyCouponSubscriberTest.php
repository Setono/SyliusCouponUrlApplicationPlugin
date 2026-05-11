<?php

declare(strict_types=1);

namespace Setono\SyliusCouponUrlApplicationPlugin\Tests\Unit\EventSubscriber;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Setono\SyliusCouponUrlApplicationPlugin\EventSubscriber\ApplyCouponSubscriber;
use Sylius\Component\Core\Model\OrderInterface;
use Sylius\Component\Order\Context\CartContextInterface;
use Sylius\Component\Order\Processor\OrderProcessorInterface;
use Sylius\Component\Promotion\Checker\Eligibility\PromotionCouponEligibilityCheckerInterface;
use Sylius\Component\Promotion\Checker\Eligibility\PromotionEligibilityCheckerInterface;
use Sylius\Component\Promotion\Model\PromotionCouponInterface;
use Sylius\Component\Promotion\Model\PromotionInterface;
use Sylius\Component\Promotion\Repository\PromotionCouponRepositoryInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\KernelEvents;

final class ApplyCouponSubscriberTest extends TestCase
{
    use ProphecyTrait;

    /** @var ObjectProphecy<PromotionCouponRepositoryInterface> */
    private ObjectProphecy $couponRepository;

    /** @var ObjectProphecy<PromotionCouponEligibilityCheckerInterface> */
    private ObjectProphecy $couponEligibilityChecker;

    /** @var ObjectProphecy<PromotionEligibilityCheckerInterface> */
    private ObjectProphecy $promotionEligibilityChecker;

    /** @var ObjectProphecy<CartContextInterface> */
    private ObjectProphecy $cartContext;

    /** @var ObjectProphecy<OrderProcessorInterface> */
    private ObjectProphecy $orderProcessor;

    /** @var ObjectProphecy<ManagerRegistry> */
    private ObjectProphecy $managerRegistry;

    /** @var ObjectProphecy<EntityManagerInterface> */
    private ObjectProphecy $entityManager;

    /** @var ObjectProphecy<HttpKernelInterface> */
    private ObjectProphecy $kernel;

    protected function setUp(): void
    {
        $this->couponRepository = $this->prophesize(PromotionCouponRepositoryInterface::class);
        $this->couponEligibilityChecker = $this->prophesize(PromotionCouponEligibilityCheckerInterface::class);
        $this->promotionEligibilityChecker = $this->prophesize(PromotionEligibilityCheckerInterface::class);
        $this->cartContext = $this->prophesize(CartContextInterface::class);
        $this->orderProcessor = $this->prophesize(OrderProcessorInterface::class);
        $this->managerRegistry = $this->prophesize(ManagerRegistry::class);
        $this->entityManager = $this->prophesize(EntityManagerInterface::class);
        $this->kernel = $this->prophesize(HttpKernelInterface::class);

        $this->managerRegistry
            ->getManagerForClass(Argument::any())
            ->willReturn($this->entityManager->reveal())
        ;
    }

    #[Test]
    public function it_subscribes_to_kernel_request(): void
    {
        self::assertSame(
            [KernelEvents::REQUEST => 'apply'],
            ApplyCouponSubscriber::getSubscribedEvents(),
        );
    }

    #[Test]
    public function it_does_nothing_on_a_sub_request(): void
    {
        $this->couponRepository->findOneBy(Argument::any())->shouldNotBeCalled();
        $this->cartContext->getCart()->shouldNotBeCalled();

        $event = $this->event($this->request('CHRISTMAS_SALE'), HttpKernelInterface::SUB_REQUEST);
        $this->subscriber()->apply($event);

        self::assertSame([], $this->flashes($event));
    }

    #[Test]
    public function it_does_nothing_when_the_coupon_query_parameter_is_absent(): void
    {
        $this->couponRepository->findOneBy(Argument::any())->shouldNotBeCalled();
        $this->cartContext->getCart()->shouldNotBeCalled();

        $event = $this->event($this->request());
        $this->subscriber()->apply($event);

        self::assertSame([], $this->flashes($event));
    }

    #[Test]
    public function it_does_nothing_when_the_coupon_query_parameter_is_empty(): void
    {
        $this->couponRepository->findOneBy(Argument::any())->shouldNotBeCalled();
        $this->cartContext->getCart()->shouldNotBeCalled();

        $event = $this->event($this->request(''));
        $this->subscriber()->apply($event);

        self::assertSame([], $this->flashes($event));
    }

    #[Test]
    public function it_does_nothing_when_no_coupon_matches_the_submitted_code(): void
    {
        $this->couponRepository->findOneBy(['code' => 'NONEXISTENT'])->willReturn(null);
        $this->cartContext->getCart()->shouldNotBeCalled();

        $event = $this->event($this->request('NONEXISTENT'));
        $this->subscriber()->apply($event);

        self::assertSame([], $this->flashes($event));
    }

    #[Test]
    public function it_flashes_an_error_when_the_coupon_is_not_eligible(): void
    {
        $cart = $this->prophesize(OrderInterface::class);
        $coupon = $this->prophesize(PromotionCouponInterface::class);

        $this->couponRepository->findOneBy(['code' => 'EXPIRED'])->willReturn($coupon->reveal());
        $this->cartContext->getCart()->willReturn($cart->reveal());
        $this->couponEligibilityChecker
            ->isEligible($cart->reveal(), $coupon->reveal())
            ->willReturn(false)
        ;

        $cart->setPromotionCoupon(Argument::any())->shouldNotBeCalled();
        $this->orderProcessor->process(Argument::any())->shouldNotBeCalled();
        $this->entityManager->persist(Argument::any())->shouldNotBeCalled();
        $this->entityManager->flush()->shouldNotBeCalled();
        $this->promotionEligibilityChecker->isEligible(Argument::any(), Argument::any())->shouldNotBeCalled();

        $event = $this->event($this->request('EXPIRED'));
        $this->subscriber()->apply($event);

        self::assertSame(
            ['error' => ['setono_sylius_coupon_url_application.coupon_not_eligible']],
            $this->flashes($event),
        );
    }

    #[Test]
    public function it_attaches_the_coupon_and_flashes_success_when_promotion_rules_are_satisfied(): void
    {
        $cart = $this->prophesize(OrderInterface::class);
        $promotion = $this->prophesize(PromotionInterface::class);
        $coupon = $this->prophesize(PromotionCouponInterface::class);
        $coupon->getPromotion()->willReturn($promotion->reveal());

        $this->couponRepository->findOneBy(['code' => 'CHRISTMAS_SALE'])->willReturn($coupon->reveal());
        $this->cartContext->getCart()->willReturn($cart->reveal());
        $this->couponEligibilityChecker
            ->isEligible($cart->reveal(), $coupon->reveal())
            ->willReturn(true)
        ;
        $this->promotionEligibilityChecker
            ->isEligible($cart->reveal(), $promotion->reveal())
            ->willReturn(true)
        ;

        $cart->setPromotionCoupon($coupon->reveal())->shouldBeCalled();
        $this->orderProcessor->process($cart->reveal())->shouldBeCalled();
        $this->entityManager->persist($cart->reveal())->shouldBeCalled();
        $this->entityManager->flush()->shouldBeCalled();

        $event = $this->event($this->request('CHRISTMAS_SALE'));
        $this->subscriber()->apply($event);

        self::assertSame(
            ['success' => ['setono_sylius_coupon_url_application.coupon_applied']],
            $this->flashes($event),
        );
    }

    #[Test]
    public function it_attaches_the_coupon_and_flashes_info_when_promotion_rules_are_unmet(): void
    {
        $cart = $this->prophesize(OrderInterface::class);
        $promotion = $this->prophesize(PromotionInterface::class);
        $coupon = $this->prophesize(PromotionCouponInterface::class);
        $coupon->getPromotion()->willReturn($promotion->reveal());

        $this->couponRepository->findOneBy(['code' => 'CHRISTMAS_SALE'])->willReturn($coupon->reveal());
        $this->cartContext->getCart()->willReturn($cart->reveal());
        $this->couponEligibilityChecker
            ->isEligible($cart->reveal(), $coupon->reveal())
            ->willReturn(true)
        ;
        $this->promotionEligibilityChecker
            ->isEligible($cart->reveal(), $promotion->reveal())
            ->willReturn(false)
        ;

        $cart->setPromotionCoupon($coupon->reveal())->shouldBeCalled();
        $this->orderProcessor->process($cart->reveal())->shouldBeCalled();
        $this->entityManager->persist($cart->reveal())->shouldBeCalled();
        $this->entityManager->flush()->shouldBeCalled();

        $event = $this->event($this->request('CHRISTMAS_SALE'));
        $this->subscriber()->apply($event);

        self::assertSame(
            ['info' => ['setono_sylius_coupon_url_application.coupon_applied_not_fulfilled']],
            $this->flashes($event),
        );
    }

    #[Test]
    public function it_flashes_info_when_the_coupon_has_no_associated_promotion(): void
    {
        $cart = $this->prophesize(OrderInterface::class);
        $coupon = $this->prophesize(PromotionCouponInterface::class);
        $coupon->getPromotion()->willReturn(null);

        $this->couponRepository->findOneBy(['code' => 'ORPHAN'])->willReturn($coupon->reveal());
        $this->cartContext->getCart()->willReturn($cart->reveal());
        $this->couponEligibilityChecker
            ->isEligible($cart->reveal(), $coupon->reveal())
            ->willReturn(true)
        ;
        $this->promotionEligibilityChecker->isEligible(Argument::any(), Argument::any())->shouldNotBeCalled();

        $cart->setPromotionCoupon($coupon->reveal())->shouldBeCalled();
        $this->orderProcessor->process($cart->reveal())->shouldBeCalled();
        $this->entityManager->persist($cart->reveal())->shouldBeCalled();
        $this->entityManager->flush()->shouldBeCalled();

        $event = $this->event($this->request('ORPHAN'));
        $this->subscriber()->apply($event);

        self::assertSame(
            ['info' => ['setono_sylius_coupon_url_application.coupon_applied_not_fulfilled']],
            $this->flashes($event),
        );
    }

    private function subscriber(): ApplyCouponSubscriber
    {
        return new ApplyCouponSubscriber(
            $this->couponRepository->reveal(),
            $this->couponEligibilityChecker->reveal(),
            $this->promotionEligibilityChecker->reveal(),
            $this->cartContext->reveal(),
            $this->orderProcessor->reveal(),
            $this->managerRegistry->reveal(),
        );
    }

    private function request(?string $couponCode = null): Request
    {
        $url = null === $couponCode ? '/' : '/?coupon=' . urlencode($couponCode);

        $request = Request::create($url);
        $request->setSession(new Session(new MockArraySessionStorage()));

        return $request;
    }

    private function event(Request $request, int $type = HttpKernelInterface::MAIN_REQUEST): RequestEvent
    {
        return new RequestEvent($this->kernel->reveal(), $request, $type);
    }

    private function flashes(RequestEvent $event): array
    {
        $session = $event->getRequest()->getSession();
        self::assertInstanceOf(Session::class, $session);

        return $session->getFlashBag()->all();
    }
}
