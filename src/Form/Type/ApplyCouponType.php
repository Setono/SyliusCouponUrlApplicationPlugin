<?php

declare(strict_types=1);

namespace Setono\SyliusCouponUrlApplicationPlugin\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;

final class ApplyCouponType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('coupon', TextType::class, [
            'label' => 'setono_sylius_coupon_url_application.form.apply_coupon.code',
        ]);
    }
}
