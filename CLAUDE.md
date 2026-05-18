# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## What this is

A Sylius 2.x plugin that lets shop visitors apply a promotion coupon either by visiting `/coupon` (a small form page) or by appending `?coupon=CODE` to any URL on the site. Active development happens on `*.x` branches (e.g. `3.x` for the Sylius 2 line, `2.x` for the Sylius 1 maintenance line). PRs targeting Sylius 2 use `--base 3.x`.

## Common commands

Composer scripts (defined in `composer.json`):

- `composer phpunit` — run the unit + functional test suites (PHPUnit 11, bootstraps `tests/Application/config/bootstrap.php`)
- `composer analyse` — PHPStan at `level: max` (`phpstan.neon`)
- `composer check-style` / `composer fix-style` — ECS using the `sylius-labs/coding-standard` ruleset (`ecs.php`)

Tools invoked directly from `vendor/bin/`:

- `vendor/bin/phpunit --filter <TestName>` — run a single test method/class
- `vendor/bin/phpunit --testsuite unit` / `--testsuite functional` — run one suite
- `vendor/bin/rector process --dry-run` — Rector check (target: `LevelSetList::UP_TO_PHP_82`)
- `vendor/bin/composer-dependency-analyser` — dependency hygiene check
- `vendor/bin/infection` — mutation testing (`infection.json5`); MSI floor is intentionally `0.0` until the test suite grows

Test-app console — run from the project root via `./tests/Application/bin/console <cmd>` (no `cd` round-trip):

- `./tests/Application/bin/console lint:container`
- `./tests/Application/bin/console lint:yaml config`
- `./tests/Application/bin/console lint:twig templates`
- `./tests/Application/bin/console doctrine:database:create --if-not-exists`
- `./tests/Application/bin/console doctrine:schema:create`
- `./tests/Application/bin/console doctrine:schema:validate`

Default `DATABASE_URL` is `mysql://root@127.0.0.1/setono_sylius_coupon_url_application_test?serverVersion=11.6.2-MariaDB` with `APP_ENV=test`. **Functional tests and `doctrine:schema:*` require a running MySQL/MariaDB** — SQLite is not supported.

## Booting the test app locally (UI smoke tests)

```bash
./tests/Application/bin/console doctrine:database:create --if-not-exists
./tests/Application/bin/console doctrine:schema:create
./tests/Application/bin/console sylius:fixtures:load default --no-interaction  # admin login: sylius / sylius
./tests/Application/bin/console lexik:jwt:generate-keypair --skip-if-exists
./tests/Application/bin/console assets:install tests/Application/public --symlink
(cd tests/Application && yarn install && yarn build)
(cd tests/Application && symfony server:status) || (cd tests/Application && symfony server:start -d)
# https://127.0.0.1:8000/admin
```

Notes:

- Always run `symfony server:status` first — `server:start` fails noisily if a server is already up. `server:status` is **per-project** (it inspects cwd), so `cd tests/Application` before checking.
- When you change a UI surface (admin form, grid, page, JS), verify via the Playwright MCP after booting the test app. PHPUnit/PHPStan/ECS don't catch broken Twig hooks, missing JS, or layout regressions.

## Architecture

The plugin has two entry points for applying a coupon, plus an admin grid enhancement.

**1. Query-string application — `src/EventSubscriber/ApplyCouponSubscriber.php`**
Listens on `KernelEvents::REQUEST` (main request only). If `?coupon=CODE` is present, it loads the `PromotionCoupon` and runs a deliberately two-staged eligibility check:

- **Coupon-level** (`PromotionCouponEligibilityCheckerInterface`, service `sylius.checker.promotion_coupon_eligibility`) gates attachment — duration, total usage limit, per-customer usage limit, channel. Failure → error flash, coupon not attached.
- **Promotion-level** (`PromotionEligibilityCheckerInterface`, service `sylius.checker.promotion_eligibility`) gates the **flash type** after attachment — it adds the promotion-rules check (cart total, taxon allow-list, etc.) on top of the coupon-level checks. Pass → `coupon_applied` success flash. Fail → `coupon_applied_not_fulfilled` info flash (coupon stays attached, discount activates as soon as the cart qualifies).

This split is intentional: the plugin's main use case is "send a coupon link in an email — the customer clicks, and the coupon waits in their cart until they qualify." See `README.md` for the consumer-facing explanation. The subscriber assigns the coupon to the cart, re-runs the `OrderProcessor`, and **persists+flushes the cart** — important because a user may arrive via a coupon link before any cart exists. Flash messages use translation keys under `setono_sylius_coupon_url_application.*`. Failures throw `RuntimeException` whose message is itself a translation key. Doctrine access goes through `Setono\Doctrine\ORMTrait::getManager($cart)`.

**2. `/coupon` page — `src/Controller/Action/ApplyCouponAction.php`**
Routed by `config/routes/shop.yaml`, exposed via either `config/routes.yaml` (with `/{_locale}` prefix) or `config/routes_no_locale.yaml` — consumers pick one in their app routing config. The action is pure-render: it pulls the cart from `sylius.context.cart`, builds an `ApplyCouponType` form pre-populated with the cart's current coupon code (if any), and renders `templates/shop/coupon.html.twig` (extends the Sylius shop layout). It never fires flashes — flash messaging lives entirely in `ApplyCouponSubscriber` so the same outcomes surface regardless of whether the customer applied a coupon via `?coupon=` or via the form on this page. For inline coupon input embedded in other shop pages, Sylius 2's cart Live Component already exposes a native widget on `/cart` (hookpoint `sylius_shop.cart.index.content.form.sections.general#left`, key `coupon`) — there's no plugin-side partial template for `{{ render(...) }}` embedding.

**3. Admin "Show URL" item action + modal — declared from the bundle extension's `prepend()`**
`SetonoSyliusCouponUrlApplicationExtension::prepend()` registers two pieces of configuration with no consumer-side wiring required:

- A `sylius_grid` block adds a per-row **Show URL** item action to `sylius_admin_promotion_coupon`. The action template (`templates/admin/promotion_coupon/grid/action/show_url.html.twig`) renders a Bootstrap 5 modal-trigger button carrying `data-coupon-code="{{ data.code }}"`.
- A `sylius_twig_hooks` block hooks two partials into `sylius_admin.promotion_coupon.index#javascripts`: `_modal.html.twig` (the shared modal markup, with a base-URL input, a read-only URL display, and a copy button) and `_javascripts.html.twig` (a one-liner that loads `public/js/coupon-url-modal.js`). The JS wires the modal lifecycle: on `show.bs.modal` it reads the trigger's coupon code, seeds the base-URL input from `localStorage` (falling back to the modal's `data-default-base-url` set to `sylius_shop_homepage`), rebuilds the URL on every `input` event, persists the base URL back to `localStorage`, and copies to clipboard via `navigator.clipboard` (with an `execCommand('copy')` fallback for non-secure contexts).

There is **no** `AddUrlColumnToCouponGridSubscriber` anymore — the runtime `GridDefinitionConverterEvent` approach used in 2.x was replaced by static `sylius_grid` config in 3.0. There is also no inline URL column on the grid; the URL is shown on demand in the modal.

**Wiring.** All services are PHP DSL (`config/services.php`). The extension only loads `services.php` and prepends `sylius_grid` + `sylius_twig_hooks`; there is no user-facing bundle config. The extension test (`tests/Unit/DependencyInjection/SetonoSyliusCouponUrlApplicationExtensionTest.php`) asserts the three FQCN-keyed services exist — when adding a service, update both `config/services.php` and that test.

## CI matrix (what must keep working)

CI runs the `setono/sylius-plugin/<job>@v2` composite GitHub Actions across:

- PHP **8.2 + 8.3 + 8.4**
- Symfony **6.4 + 7.4**
- Dependencies **lowest + highest**

Jobs: `backwards-compatibility`, `coding-standards`, `dependency-analysis`, `static-code-analysis`, `unit-tests`. Functional tests are not wired into CI today (no MySQL service); the test app boots locally for Playwright UI verification.

## Working in this repo

- **Never commit or push unless the user explicitly asks.** Make the edits, run the quality tools, leave the working tree dirty, and report what's ready. The user reviews the diff before the commit happens. This applies even after a previous "commit and push" — that authorization stands only for the scope just requested, not for subsequent changes.
- Prefer **relative paths** in shell commands. Absolute paths inside this working directory trigger a Claude Code permission prompt; relative paths run without one. If you `cd` into `tests/Application/` for a step, `cd` back to the project root before subsequent commands rather than chaining absolute paths.
- Run the test-app console from the project root via `./tests/Application/bin/console <cmd>` instead of `cd tests/Application && bin/console <cmd>` — same result, no `cd` round-trip.
- Before each commit, run `composer fix-style`, `composer analyse`, and `composer phpunit` and fix what they flag. CI runs all three across the PHP/Symfony matrix, so catching failures locally is cheaper than waiting for the runner.
- When adding or renaming a translation key, update **every** locale file in `translations/` listed in the **Translations** section below (both `messages.*.yaml` and `flashes.*.yaml`). Missing entries silently render the raw key in the shop UI. Run `./tests/Application/bin/console lint:yaml translations` after editing to catch syntax breaks.
- When you change a UI surface — the `/coupon` page, the admin Show URL action, the modal, or the JS in `public/js/coupon-url-modal.js` — verify it in a browser via the Playwright MCP. PHPUnit/PHPStan/ECS won't catch broken Twig hooks, missing JS, or layout regressions.
- When you make a breaking change a plugin user would need to act on during a major-version upgrade (default-value flips, renamed/removed public classes or services, template-path moves, changed model APIs, removed config keys), document it in `UPGRADE.md` under the relevant "Upgrading from X to Y" section. Skip changes that consumers don't have to react to: dev tooling, listener-priority tweaks, translation-key additions, and other internal refactors.
- When you ship a change that affects what the plugin does for end users — new feature surfaces, new configuration keys, behavior shifts, removed UI — update `README.md` to describe the current state.

## Translations

Source locale is English. Following the Setono plugin skeleton, the plugin is shipped translated into:

- Nordic: Danish (`da`), Swedish (`sv`), Norwegian (`no`), Finnish (`fi`)
- Large EU: German (`de`), French (`fr`), Spanish (`es`), Italian (`it`), Dutch (`nl`), Polish (`pl`)
- Other common Sylius locales: Portuguese (`pt`), Czech (`cs`), Hungarian (`hu`), Romanian (`ro`), Ukrainian (`uk`)

Translation files live in `translations/` and follow Symfony's `<domain>.<locale>.<format>` naming (`messages.<locale>.yaml`, `flashes.<locale>.yaml`).

**Whenever you add a new translation key, you must add it to every locale above — not just `en`.** Missing locale entries are silently rendered as the raw key in admin/shop UI. The same rule applies in reverse: if you remove or rename a key, do it in all locale files in the same change. Run `./tests/Application/bin/console lint:yaml translations` after editing to catch syntax breaks across the set.

## Conventions

- All PHP files use `declare(strict_types=1);`. Classes are `final` (skeleton convention); entities (none today) would be the exception.
- Translation keys are namespaced under `setono_sylius_coupon_url_application.*`. See the **Translations** section above for the full locale matrix.
- When touching `config/` or `templates/`, remember CI lints YAML and Twig via the test Sylius app — keep paths and the bundle alias `@SetonoSyliusCouponUrlApplicationPlugin` intact.
- **Service configuration is PHP DSL** (`config/services.php`). Each services file declares `namespace Symfony\Component\DependencyInjection\Loader\Configurator;` at the top so `service()`, `param()`, etc. resolve as bare function calls without `use function` imports. Don't ship XML service config — that's the v1 layout, gone in 3.0.
- **All new services use their FQCN as the service id** (e.g. `$services->set(ApplyCouponAction::class)`), not snake-cased aliases. This matches Symfony's autowiring conventions and lets consumers override or decorate by class name.
- **When the extension needs to configure another bundle** (`sylius_grid`, `sylius_twig_hooks`, etc.), do it from `SetonoSyliusCouponUrlApplicationExtension::prepend()` with the config inlined as a PHP array passed to `prependExtensionConfig()`. Don't ship a YAML file consumers have to import, and don't read YAML from disk inside `prepend()`.
- **Doctrine access goes through `Setono\Doctrine\ORMTrait`** from `setono/doctrine-orm-trait`. Inject `Doctrine\Persistence\ManagerRegistry` plus the relevant `class-string` (or use the trait's `$obj` overload that resolves the manager from the entity instance) and `use ORMTrait;` so the manager is resolved lazily via `$this->getManager(...)`. Don't inject `EntityManagerInterface` directly.
- **Mocks: always use Prophecy** (`Prophecy\PhpUnit\ProphecyTrait` + `$this->prophesize(...)->reveal()`). Don't mix with PHPUnit's native `createMock()` / `createStub()` — keep doubles consistent across the suite. The `setono/sylius-plugin` toolchain ships `jangregor/phpstan-prophecy` so PHPStan understands `prophesize()` return types.
- **Form-type tests**: extend `Symfony\Component\Form\Test\TypeTestCase` (see <https://symfony.com/doc/6.4/form/unit_testing.html>).
- **Constraint validators**: extend `Symfony\Component\Validator\Test\ConstraintValidatorTestCase` with `createValidator(): ConstraintValidatorInterface`; assert with `$this->buildViolation(...)->atPath(...)->setParameter(...)->assertRaised()` and `$this->assertNoViolation()`.
