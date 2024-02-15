<?php

declare(strict_types=1);

namespace Setono\SyliusCouponUrlApplicationPlugin\EventSubscriber;

use Doctrine\Persistence\ManagerRegistry;
use Setono\DoctrineObjectManagerTrait\ORM\ORMManagerTrait;
use Sylius\Component\Core\Model\OrderInterface;
use Sylius\Component\Order\Context\CartContextInterface;
use Sylius\Component\Order\Model\OrderInterface as BaseOrderInterface;
use Sylius\Component\Order\Processor\OrderProcessorInterface;
use Sylius\Component\Promotion\Checker\Eligibility\PromotionCouponEligibilityCheckerInterface;
use Sylius\Component\Promotion\Model\PromotionCouponInterface;
use Sylius\Component\Promotion\Repository\PromotionCouponRepositoryInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Webmozart\Assert\Assert;

final class ApplyCouponSubscriber implements EventSubscriberInterface
{
    use ORMManagerTrait;

    public function __construct(
        private readonly PromotionCouponRepositoryInterface $promotionCouponRepository,
        private readonly PromotionCouponEligibilityCheckerInterface $promotionCouponEligibilityChecker,
        private readonly CartContextInterface $cartContext,
        private readonly OrderProcessorInterface $orderProcessor,
        ManagerRegistry $managerRegistry,
    ) {
        $this->managerRegistry = $managerRegistry;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => 'apply',
        ];
    }

    public function apply(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();

        $couponCode = $request->query->get('coupon');
        if (!is_string($couponCode) || '' === $couponCode) {
            return;
        }

        $coupon = $this->promotionCouponRepository->findOneBy(['code' => $couponCode]);
        Assert::nullOrIsInstanceOf($coupon, PromotionCouponInterface::class);

        if (null === $coupon) {
            return;
        }

        /** @var OrderInterface|BaseOrderInterface $cart */
        $cart = $this->cartContext->getCart();
        Assert::isInstanceOf($cart, OrderInterface::class);

        try {
            if (!$this->promotionCouponEligibilityChecker->isEligible($cart, $coupon)) {
                throw new \RuntimeException('setono_sylius_coupon_url_application.coupon_not_eligible');
            }

            $cart->setPromotionCoupon($coupon);

            $this->orderProcessor->process($cart);

            $manager = $this->getManager($cart);

            // the reason we persist here is that it's quite possible that the user clicked a link with a coupon code,
            // but wasn't active on the site beforehand, and therefore, didn't have a cart yet.
            $manager->persist($cart);
            $manager->flush();

            self::addFlash($request, 'success', 'setono_sylius_coupon_url_application.coupon_applied');
        } catch (\RuntimeException $e) {
            self::addFlash($request, 'error', $e->getMessage());
        }
    }

    private static function addFlash(Request $request, string $type, string $message): void
    {
        $session = $request->getSession();
        if ($session instanceof Session) {
            $session->getFlashBag()->add($type, $message);
        }
    }
}
