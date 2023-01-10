<?php

declare(strict_types=1);

namespace Setono\SyliusCouponUrlApplicationPlugin\Controller\Action;

use Doctrine\Persistence\ManagerRegistry;
use Setono\DoctrineObjectManagerTrait\ORM\ORMManagerTrait;
use Setono\SyliusCouponUrlApplicationPlugin\Form\Type\ApplyCouponType;
use Sylius\Component\Core\Model\OrderInterface;
use Sylius\Component\Order\Context\CartContextInterface;
use Sylius\Component\Order\Processor\OrderProcessorInterface;
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
    ) {
        $this->managerRegistry = $managerRegistry;
    }

    public function __invoke(Request $request): Response
    {
        $command = new ApplyCouponCommand(); // todo set the default redirect to referrer?

        $form = $this->formFactory->createNamed('', ApplyCouponType::class, $command, [
            'csrf_protection' => false,
        ]);

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
                /** @var OrderInterface $cart */
                $cart = $this->cartContext->getCart();
                Assert::isInstanceOf($cart, OrderInterface::class);

                $cart->setPromotionCoupon($command->coupon);

                $this->orderProcessor->process($cart);

                $this->getManager($cart)->flush();

                $session = $request->getSession();
                if ($session instanceof Session) {
                    $session->getFlashBag()->add('success', 'setono_sylius_coupon_url_application.coupon_applied');
                }

                return new RedirectResponse($command->redirect ?? $this->urlGenerator->generate('setono_sylius_coupon_url_application_shop_apply_coupon'));
            }
        }

        return new Response($this->twig->render('@SetonoSyliusCouponUrlApplicationPlugin/shop/apply_coupon.html.twig', [
            'form' => $form->createView(),
        ]));
    }
}
