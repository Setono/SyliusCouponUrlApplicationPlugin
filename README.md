# Sylius Coupon URL Application Plugin

[![Latest Version][ico-version]][link-packagist]
[![Software License][ico-license]](LICENSE)
[![Build Status][ico-github-actions]][link-github-actions]
[![Code Coverage][ico-code-coverage]][link-code-coverage]

Apply coupons by going to `example.com/coupon` or directly from the URL by going to
`example.com/coupon?coupon=CODE&redirect=URL`.

## Installation

```bash
composer require setono/sylius-coupon-url-application-plugin
```

### Import configuration

```yaml
# config/packages/setono_sylius_coupon_url_application.yaml
imports:
    # ...
    - { resource: "@SetonoSyliusCouponUrlApplicationPlugin/Resources/config/app/config.yaml" }
```

### Import routing

```yaml
# config/routes/setono_sylius_coupon_url_application.yaml
setono_sylius_coupon_url_application:
    resource: "@SetonoSyliusCouponUrlApplicationPlugin/Resources/config/routes.yaml"
```

or if your app doesn't use locales:

```yaml
# config/routes/setono_sylius_coupon_url_application.yaml
setono_sylius_coupon_url_application:
    resource: "@SetonoSyliusCouponUrlApplicationPlugin/Resources/config/routes_no_locale.yaml"
```

### Install assets

```bash
php bin/console assets:install
```

[ico-version]: https://poser.pugx.org/setono/sylius-coupon-url-application-plugin/v/stable
[ico-license]: https://poser.pugx.org/setono/sylius-coupon-url-application-plugin/license
[ico-github-actions]: https://github.com/Setono/SyliusCouponUrlApplicationPlugin/workflows/build/badge.svg
[ico-code-coverage]: https://codecov.io/gh/Setono/SyliusCouponUrlApplicationPlugin/branch/master/graph/badge.svg

[link-packagist]: https://packagist.org/packages/setono/sylius-coupon-url-application-plugin
[link-github-actions]: https://github.com/Setono/SyliusCouponUrlApplicationPlugin/actions
[link-code-coverage]: https://codecov.io/gh/Setono/SyliusCouponUrlApplicationPlugin
