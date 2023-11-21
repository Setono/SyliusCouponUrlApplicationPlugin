<?php

declare(strict_types=1);

namespace Setono\SyliusCouponUrlApplicationPlugin\Controller\Action;

use Doctrine\Persistence\ManagerRegistry;
use Setono\DoctrineObjectManagerTrait\ORM\ORMManagerTrait;
use Setono\SyliusCouponUrlApplicationPlugin\Form\Type\ApplyCouponType;
use Sylius\Component\Core\Model\OrderInterface;
use Sylius\Component\Order\Context\CartContextInterface;
use Sylius\Component\Order\Processor\OrderProcessorInterface;
use Sylius\Component\Promotion\Checker\Eligibility\PromotionCouponEligibilityCheckerInterface;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Twig\Environment;
use Webmozart\Assert\Assert;

final class ApplyCouponAction
{
    use ORMManagerTrait;

    public function __construct(
        private FormFactoryInterface $formFactory,
        private Environment $twig,
        private CartContextInterface $cartContext,
        private OrderProcessorInterface $orderProcessor,
        ManagerRegistry $managerRegistry,
        private UrlGeneratorInterface $urlGenerator,
        private ?PromotionCouponEligibilityCheckerInterface $promotionCouponEligibilityChecker = null,
        private string $defaultRedirectRoute = 'sylius_shop_cart_summary',
    ) {
        $this->managerRegistry = $managerRegistry;

        if (null === $promotionCouponEligibilityChecker) {
            trigger_deprecation(
                'setono/sylius-coupon-url-application-plugin',
                '1.1',
                'Not passing a %s as the 7th argument is deprecated and will not be allowed in 2.0',
                PromotionCouponEligibilityCheckerInterface::class,
            );
        }
    }

    public function __invoke(Request $request): Response
    {
        $command = new ApplyCouponCommand(); // todo set the default redirect to referrer?

        $form = $this->formFactory->createNamed('', ApplyCouponType::class, $command, [
            'csrf_protection' => false,
        ]);

        /** @var OrderInterface $cart */
        $cart = $this->cartContext->getCart();
        Assert::isInstanceOf($cart, OrderInterface::class);

        /**
         * The reason we need this check is that Sylius has overridden Symfonys implementation of \Symfony\Component\Form\RequestHandlerInterface.
         *
         * Symfonys implementation will not submit a form if no fields are submitted, but Sylius' implementation will.
         *
         * See \Sylius\Bundle\ResourceBundle\Form\Extension\HttpFoundation\HttpFoundationRequestHandler
         * and \Symfony\Component\Form\Extension\HttpFoundation\HttpFoundationRequestHandler respectively
         */
        if ($request->query->count() > 0) {
            $form->handleRequest($request);

            if ($form->isSubmitted() && $form->isValid()) {
                Assert::notNull($command->coupon);

                if (null !== $this->promotionCouponEligibilityChecker && !$this->promotionCouponEligibilityChecker->isEligible($cart, $command->coupon)) {
                    $this->addFlash($request, 'error', 'setono_sylius_coupon_url_application.coupon_not_eligible');

                    return new RedirectResponse($this->urlGenerator->generate('setono_sylius_coupon_url_application_shop_apply_coupon'));
                }

                $cart->setPromotionCoupon($command->coupon);

                $totalBefore = $cart->getTotal();
                $this->orderProcessor->process($cart);
                $totalAfter = $cart->getTotal();

                $this->getManager($cart)->flush();

                $totalDiff = abs($totalBefore - $totalAfter);

                $request->getSession()->set('sscua_coupon_applied', true);

                $this->addFlash($request, 'success', 'setono_sylius_coupon_url_application.coupon_applied');

                if (0 === $totalDiff) {
                    $this->addFlash($request, 'info', 'setono_sylius_coupon_url_application.coupon_applied_not_fulfilled');
                }

                return $this->redirect($command);
            }
        }

        // We don't want to show the notice about existing coupons if the user just applied a new coupon
        // hence we set a session when applying a coupon and then check for that session here
        if ($cart->getPromotionCoupon() !== null && $request->getSession()->get('sscua_coupon_applied') !== true) {
            $this->addFlash(
                $request,
                'info',
                [
                    'message' => 'setono_sylius_coupon_url_application.coupon_already_applied',
                    'parameters' => ['%code%' => (string) $cart->getPromotionCoupon()?->getCode()],
                ],
            );
        }

        $request->getSession()->remove('sscua_coupon_applied');

        return new Response($this->twig->render('@SetonoSyliusCouponUrlApplicationPlugin/shop/apply_coupon.html.twig', [
            'form' => $form->createView(),
        ]));
    }

    private function redirect(ApplyCouponCommand $command): RedirectResponse
    {
        return new RedirectResponse($command->redirect ?? $this->urlGenerator->generate($this->defaultRedirectRoute));
    }

    /**
     * @param string|array{message: string, parameters: array<string, string>} $message
     */
    private function addFlash(Request $request, string $type, string|array $message): void
    {
        $session = $request->getSession();
        if ($session instanceof Session) {
            $session->getFlashBag()->add($type, $message);
        }
    }
}
