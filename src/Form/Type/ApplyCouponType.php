<?php

declare(strict_types=1);

namespace Setono\SyliusCouponUrlApplicationPlugin\Form\Type;

use Setono\SyliusCouponUrlApplicationPlugin\Controller\Action\ApplyCouponCommand;
use Sylius\Bundle\ResourceBundle\Form\DataTransformer\ResourceToIdentifierTransformer;
use Sylius\Component\Promotion\Repository\PromotionCouponRepositoryInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @extends AbstractType<ApplyCouponCommand>
 */
final class ApplyCouponType extends AbstractType
{
    public function __construct(private PromotionCouponRepositoryInterface $promotionCouponRepository)
    {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('coupon', TextType::class, [
                'label' => 'setono_sylius_coupon_url_application.form.apply_coupon.code',
            ])->add('redirect', HiddenType::class)
        ;

        $builder->get('coupon')
            ->addModelTransformer(new ResourceToIdentifierTransformer($this->promotionCouponRepository, 'code'))
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => ApplyCouponCommand::class,
            'validation_groups' => [
                'setono_sylius_coupon_url_application',
            ],
        ]);
    }
}
