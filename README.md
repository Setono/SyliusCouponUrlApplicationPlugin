# Sylius Coupon URL Application Plugin

[![Latest Version][ico-version]][link-packagist]
[![Software License][ico-license]](LICENSE)
[![Build Status][ico-github-actions]][link-github-actions]
[![Code Coverage][ico-code-coverage]][link-code-coverage]
[![Mutation testing][ico-infection]][link-infection]

Apply coupons by going to `example.com/coupon` or directly from the URL by going to
`example.com/?coupon=CODE`.

## Installation

```bash
composer require setono/sylius-coupon-url-application-plugin
```

### Import routing

```yaml
# config/routes/setono_sylius_coupon_url_application.yaml
setono_sylius_coupon_url_application:
    resource: "@SetonoSyliusCouponUrlApplicationPlugin/config/routes.yaml"
```

or if your app doesn't use locales:

```yaml
# config/routes/setono_sylius_coupon_url_application.yaml
setono_sylius_coupon_url_application:
    resource: "@SetonoSyliusCouponUrlApplicationPlugin/config/routes_no_locale.yaml"
```

### Install assets

```bash
php bin/console assets:install
```

## How coupon application works

The plugin's main use case is sending coupon links from email campaigns,
newsletters, ads, etc. A customer clicks `https://example.com/?coupon=CHRISTMAS`
and the coupon is **attached to their cart immediately** — even before they
have added a single item. As soon as the cart matches the promotion's rules
(e.g. a minimum cart total, a required taxon), the discount activates.

To support this flow, the plugin runs the coupon application in two stages:

1. **Coupon-level eligibility** (`PromotionCouponEligibilityCheckerInterface`)
   — gates whether the coupon is attached at all. This checks the coupon's
   start/end dates, total usage limit, per-customer usage limit, and channel
   applicability. If any of these fail, the coupon is rejected with an error
   flash and **not** attached to the cart.

2. **Promotion-level eligibility** (`PromotionEligibilityCheckerInterface`)
   — determines which flash the customer sees after the coupon is attached.
   This includes the underlying promotion's rules (cart-total threshold,
   taxon/product allow-lists, etc.):

   - **Cart already qualifies** → success flash
     `setono_sylius_coupon_url_application.coupon_applied`
     ("The coupon code is now activated in your cart")
   - **Cart doesn't qualify yet** → info flash
     `setono_sylius_coupon_url_application.coupon_applied_not_fulfilled`
     ("The coupon code is saved in your cart, but the discount is not active
     yet because your cart does not contain the items, amounts or meet other
     criteria that the coupon requires…")

This deliberately differs from Sylius's built-in cart coupon widget, which
rejects coupons up-front when the cart doesn't satisfy promotion rules. The
plugin's leniency is the point — customers should be able to click the link
in an email *before* shopping, not after.

[ico-version]: https://poser.pugx.org/setono/sylius-coupon-url-application-plugin/v/stable
[ico-license]: https://poser.pugx.org/setono/sylius-coupon-url-application-plugin/license
[ico-github-actions]: https://github.com/Setono/SyliusCouponUrlApplicationPlugin/workflows/build/badge.svg
[ico-code-coverage]: https://codecov.io/gh/Setono/SyliusCouponUrlApplicationPlugin/graph/badge.svg
[ico-infection]: https://img.shields.io/endpoint?style=flat&url=https%3A%2F%2Fbadge-api.stryker-mutator.io%2Fgithub.com%2FSetono%2FSyliusCouponUrlApplicationPlugin%2F3.x

[link-packagist]: https://packagist.org/packages/setono/sylius-coupon-url-application-plugin
[link-github-actions]: https://github.com/Setono/SyliusCouponUrlApplicationPlugin/actions
[link-code-coverage]: https://codecov.io/gh/Setono/SyliusCouponUrlApplicationPlugin
[link-infection]: https://dashboard.stryker-mutator.io/reports/github.com/Setono/SyliusCouponUrlApplicationPlugin/3.x
