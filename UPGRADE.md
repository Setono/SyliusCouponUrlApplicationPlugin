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

### Admin coupon list customisation moved to Twig hooks

In 2.x the plugin added the **URL** column and the "Use other base URL" input
to the admin promotion-coupon grid via a `sylius_ui` event override and a
runtime `GridDefinitionConverterEvent` subscriber. Sylius 2.x removed
`sylius_ui` events; both pieces are now registered automatically via the
plugin's bundle extension:

- The **URL** column is appended to `sylius_admin_promotion_coupon` via
  `$container->prependExtensionConfig('sylius_grid', [...])`.
- The "Use other base URL" JS partial is hooked into
  `sylius_admin.promotion_coupon.index#javascripts` via
  `$container->prependExtensionConfig('sylius_twig_hooks', [...])`.

You do not need to import or configure anything in your app — the plugin
ships both registrations from `SetonoSyliusCouponUrlApplicationExtension::prepend()`.

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

### Doctrine

`ApplyCouponSubscriber` now uses `Setono\Doctrine\ORMTrait` from
`setono/doctrine-orm-trait` instead of
`Setono\DoctrineObjectManagerTrait\ORM\ORMManagerTrait` from
`setono/doctrine-object-manager-trait`. The constructor signature is
unchanged; the trait name is the only thing that moved.
