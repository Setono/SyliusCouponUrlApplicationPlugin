<?php

declare(strict_types=1);

namespace Setono\SyliusCouponUrlApplicationPlugin\Tests\Unit\Form\Type;

use PHPUnit\Framework\Attributes\Test;
use Setono\SyliusCouponUrlApplicationPlugin\Form\Type\ApplyCouponType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Test\TypeTestCase;

/**
 * @see https://symfony.com/doc/6.4/form/unit_testing.html
 */
final class ApplyCouponTypeTest extends TypeTestCase
{
    #[Test]
    public function it_submits_a_coupon_code_and_returns_synchronized_form_data(): void
    {
        $formData = ['coupon' => 'CHRISTMAS_SALE'];

        $form = $this->factory->create(ApplyCouponType::class);
        $form->submit($formData);

        self::assertTrue($form->isSynchronized());
        self::assertSame($formData, $form->getData());
    }

    #[Test]
    public function it_keeps_the_form_synchronized_when_no_coupon_is_submitted(): void
    {
        $form = $this->factory->create(ApplyCouponType::class);
        $form->submit(['coupon' => '']);

        self::assertTrue($form->isSynchronized());
        self::assertSame(['coupon' => null], $form->getData());
    }

    #[Test]
    public function it_exposes_a_single_coupon_field_of_text_type(): void
    {
        $form = $this->factory->create(ApplyCouponType::class);

        self::assertTrue($form->has('coupon'));
        self::assertSame(TextType::class, $form->get('coupon')->getConfig()->getType()->getInnerType()::class);
    }

    #[Test]
    public function it_labels_the_coupon_field_with_the_plugin_translation_key(): void
    {
        $form = $this->factory->create(ApplyCouponType::class);

        self::assertSame(
            'setono_sylius_coupon_url_application.form.apply_coupon.code',
            $form->get('coupon')->getConfig()->getOption('label'),
        );
    }

    #[Test]
    public function it_pre_populates_the_coupon_field_from_initial_data(): void
    {
        $form = $this->factory->create(ApplyCouponType::class, ['coupon' => 'BLACK_FRIDAY']);

        self::assertSame('BLACK_FRIDAY', $form->get('coupon')->getData());
        self::assertSame('BLACK_FRIDAY', $form->createView()->children['coupon']->vars['value']);
    }
}
