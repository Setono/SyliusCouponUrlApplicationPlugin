# Upgrade

## Upgrading from 2.x to 3.0

### Requirements

- PHP `>= 8.2`
- Symfony `6.4` or `7.4`
- Sylius `~2.2`

The plugin no longer supports Sylius 1.x, Symfony < 6.4, or PHP < 8.2.

### Plugin file layout

The Sylius 1.x `Resources/{config,translations,views}` convention has been
replaced with the Sylius 2.x plugin layout. If you imported routing files
or extended template paths, update them as follows:

| 2.x                                              | 3.0                            |
| ------------------------------------------------ | ------------------------------ |
| `@SetonoSyliusCouponUrlApplicationPlugin/Resources/config/routes.yaml`           | `@SetonoSyliusCouponUrlApplicationPlugin/config/routes.yaml`           |
| `@SetonoSyliusCouponUrlApplicationPlugin/Resources/config/routes_no_locale.yaml` | `@SetonoSyliusCouponUrlApplicationPlugin/config/routes_no_locale.yaml` |
| `@SetonoSyliusCouponUrlApplicationPlugin/Resources/views/...`                    | `@SetonoSyliusCouponUrlApplicationPlugin/...`                          |

### Admin coupon list: "Show URL" action replaces the URL column

In 2.x the plugin added a **URL** column and a "Use other base URL" text input
to the admin promotion-coupon grid via a `sylius_ui` event override and a
runtime `GridDefinitionConverterEvent` subscriber. Sylius 2.x removed
`sylius_ui` events, and the column-plus-inline-input pattern doesn't fit the
Sylius 2 admin look. The plugin now ships a per-row **Show URL** item action
instead:

- The action is added to `sylius_admin_promotion_coupon` via
  `$container->prependExtensionConfig('sylius_grid', [...])` with a custom
  template that renders a Bootstrap 5 modal trigger button.
- A single shared modal (`templates/admin/promotion_coupon/_modal.html.twig`)
  and the supporting JS (`public/js/coupon-url-modal.js`) are injected into
  the `sylius_admin.promotion_coupon.index#javascripts` hookpoint.
- The modal lets the admin edit the base URL inline, copy the resulting URL
  to the clipboard, and remembers the last-used base URL across rows in
  `localStorage`.

You do not need to import or configure anything in your app — the plugin
ships all registrations from `SetonoSyliusCouponUrlApplicationExtension::prepend()`.

### Removed services

Snake-cased service ids were replaced by FQCN ids. Anyone fetching from the
container by id needs to update.

| Removed                                                                              | Replacement                                                                                  |
| ------------------------------------------------------------------------------------ | -------------------------------------------------------------------------------------------- |
| `setono_sylius_coupon_url_application.controller.action.apply_coupon`                | `Setono\SyliusCouponUrlApplicationPlugin\Controller\Action\ApplyCouponAction`                |
| `setono_sylius_coupon_url_application.event_subscriber.apply_coupon`                 | `Setono\SyliusCouponUrlApplicationPlugin\EventSubscriber\ApplyCouponSubscriber`              |
| `setono_sylius_coupon_url_application.event_subscriber.admin.add_url_column_to_coupon_grid` | (none — the URL column is now declared via `sylius_grid` config from the bundle's `prepend()`) |
| `setono_sylius_coupon_url_application.form.type.apply_coupon`                        | `Setono\SyliusCouponUrlApplicationPlugin\Form\Type\ApplyCouponType`                          |
| `config/services.xml`                                                                | `config/services.php` (PHP DSL via `ContainerConfigurator`)                                  |

### Removed classes

| Removed                                                                                                 | Replacement                                                                              |
| ------------------------------------------------------------------------------------------------------- | ---------------------------------------------------------------------------------------- |
| `Setono\SyliusCouponUrlApplicationPlugin\EventSubscriber\Admin\AddUrlColumnToCouponGridSubscriber`      | (none — column is declared via `sylius_grid` config from the bundle's `prepend()`)       |

### Removed shop route, template, and sub-request branching

The `/_partial/coupon` route (`setono_sylius_coupon_url_application_shop_partial_apply_coupon`) and its template `templates/shop/partial/coupon.html.twig` have been removed. They existed in 2.x so that consumers embedding the coupon form via `{{ render(path('setono_sylius_coupon_url_application_shop_apply_coupon')) }}` would render the form without the shop layout wrapper.

In Sylius 2 the cart page (`/cart`) ships its own native coupon input via the Sylius admin's cart Live Component (hook `sylius_shop.cart.index.content.form.sections.general#left`, template `@SyliusShop/cart/index/content/form/sections/general/coupon.html.twig`). Use that instead — it integrates directly with the cart form's `promotionCoupon` field and re-renders inline without a sub-request.

As a side effect, `ApplyCouponAction` no longer injects `RequestStack` or branches on `isMainRequest`; it always renders `@SetonoSyliusCouponUrlApplicationPlugin/shop/coupon.html.twig`.

### Flash-message behaviour change for `?coupon=…`

When a customer clicks a coupon URL on a cart that doesn't yet satisfy the
underlying promotion's rules (e.g. minimum cart total, taxon allow-list,
etc.), 2.x always fired the `success` flash `coupon_applied`. 3.x now runs
a second eligibility check (`PromotionEligibilityCheckerInterface`,
service `sylius.checker.promotion_eligibility`) after attaching the coupon
and fires the `info` flash `coupon_applied_not_fulfilled` instead in that
case — the coupon is still attached, but the discount won't activate
until the cart qualifies. See `README.md` for the consumer-facing
explanation.

Translation impact:

- `coupon_already_applied` translation key has been removed entirely.
  `ApplyCouponAction` no longer surfaces "already applied" as a flash;
  the form simply pre-populates with the current cart coupon code, and
  any further reapply attempts produce one of the existing
  `coupon_applied` / `coupon_applied_not_fulfilled` / `coupon_not_eligible`
  outcomes. If you overrode `setono_sylius_coupon_url_application.coupon_already_applied`
  in your app's `translations/`, you can delete that override.
- `coupon_applied_not_fulfilled` wording has been rewritten across all
  shipped locales to lead with the positive outcome ("The coupon code is
  saved in your cart, but the discount is not active yet…") instead of
  the previous rejection-sounding wording. If you overrode this key in
  your app, review the new shipped phrasing and update your override.

### Doctrine

`ApplyCouponSubscriber` now uses `Setono\Doctrine\ORMTrait` from
`setono/doctrine-orm-trait` instead of
`Setono\DoctrineObjectManagerTrait\ORM\ORMManagerTrait` from
`setono/doctrine-object-manager-trait`. The constructor signature is
unchanged; the trait name is the only thing that moved.
