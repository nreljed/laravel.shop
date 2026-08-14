# Shop Module (`nreljed/shop-module`)

A self-contained e-commerce feature provider for the Laravel dashboard. Ships as a standalone Composer package so the host app stays generic — the same pattern as `nreljed/dashboard-core`.

**Namespace:** `Nreljed\ShopModule\` (PSR-4 → `src/`)
**Composer package:** `nreljed/shop-module` (path repository in the host, `"symlink": false`)
**Feature slug:** `shop`

## Requirements

| Requirement | Version |
|-------------|---------|
| PHP | ^8.4 |
| laravel/framework | ^13.0 |
| nreljed/dashboard-core | ^0.1 |
| barryvdh/laravel-dompdf | ^3.1 (order invoices) |
| stripe/stripe-php | ^20.0 (Stripe payments) |

## What it provides

Manage your online store: products (simple / variable / grouped / external / digital / service), inventory, orders, customers, coupons, reviews, shipping, taxes, currencies, wishlist, abandoned-cart recovery, webhooks, and a public storefront.

## Installation

```bash
composer require nreljed/shop-module
php artisan permissions:sync
php artisan migrate
```

1. **Composer:** the host declares the package as a path repository:
   ```json
   "repositories": [
       { "type": "path", "url": "packages/shop-module",
         "options": { "symlink": false, "versions": { "nreljed/shop-module": "0.1.0" } } },
       ...
   ]
   ```
2. **Provider:** registered in `bootstrap/providers.php` (or `config/app.php`) alongside the core provider.
3. **Feature registration:** the host adds the provider class to the `features` array in `config/core.php` so the core `FeatureRegistry` aggregates its nav, permissions, email templates, notification types, gallery config, and dashboard data.
4. **Migrations / views / config:** auto-loaded — `loadMigrationsFrom(__DIR__.'/../database/migrations')`, `loadViewsFrom(..., 'shop')` + `View::addLocation(...)` (so default-namespace `view('email.templates.*')` resolves package blades), and `mergeConfigFrom(..., 'shop')`.

### Frontend wiring (host)

- **Vite alias:** `@shop` → `packages/shop-module/src/Resources/js/` (also aliased to the vendor copy in production).
- **Page registration:** the host glob-imports `@shop/Pages/**/*.tsx` with `projectPrefix: "shop"`, so pages render under `shop/*` Inertia component keys.
- **Tailwind:** the host's `resources/css/app.css` uses `@source` directives; the shop JS must be included (currently `@source "../../vendor/nreljed/shop-module/src/Resources/js/"`). Because Tailwind config uses `source(none)`, a missing `@source` silently drops every shop utility class.

## FeatureProvider integration

`ShopServiceProvider` implements `Nreljed\DashboardCore\Contracts\FeatureProvider` via `InteractsWithFeatures`:

| Contract method | What it provides |
|-----------------|------------------|
| `name()`, `slug()`, `version()`, `dependencies()` | Feature identity (`Shop` / `shop` / `1.0.0`) |
| `permissions()` | 16 permissions (see below) — synced via `permissions:sync` |
| `getNavItems()` | Sidebar: full **Shop** group for managers, **My Account** group for customers |
| `getNavGroupLabel()` | Group heading "Manage Shop" / "My Account" depending on permissions |
| `getDashboardData()` | KPI block: products, published, orders, pending, low stock |
| `getEmailTemplates()` | Template samples for the email-template simulator |
| `getNotificationTypes()` | 4 notification types (see below) |
| `getGalleryConfig()` | `product_gallery` (max 10 images, hidden from media library) |
| `getMetaFormattingConfig()` | `price_fields` formatted with currency |
| `getSettingsRoute()` | Points admin Settings at the general settings tab |
| `hasSettings()` | Enables the settings tab |
| `onUserDelete($userId)` | Orphans orders (sets `user_id` to null) instead of deleting them |

The provider also:

- Registers 9 policies via `Gate::policy()`.
- Registers 5 artisan commands.
- Shares `currencies`, `selectedCurrency`, `currencyPosition`, `cart`, and `wishlistIds` via `Inertia::share()`.
- Self-loads all routes (11 files) — no host `routes/shop*.php` files exist.
- Registers `ShopService` as a singleton.

## Directory layout

```
packages/shop-module/
├── composer.json
├── config/shop.php                # merged as config('shop')
├── database/migrations/           # 57 migrations, auto-loaded
├── resources/views/email/templates/  # 7 blade templates (auto-location)
└── src/
    ├── Commands/                  # 5 artisan commands
    ├── Http/
    │   ├── Controllers/           # admin + storefront + api controllers
    │   │   └── Api/               # public REST controllers
    │   ├── Middleware/            # (if any)
    │   └── Controllers/... 
    ├── Models/                    # 30 Eloquent models
    ├── Policies/                  # 9 authorization policies
    ├── Requests/                  # FormRequests (validation)
    ├── Resources/js/Pages/        # Inertia React pages (@shop/Pages/...)
    ├── Routes/                    # 11 route files, self-loaded
    ├── Services/                  # 13 domain services
    └── ShopServiceProvider.php
```

## Data model (30 models)

| Model | Table | Notes |
|-------|-------|-------|
| `Product` | `products` | Simple/variable/grouped/external/digital/service; pricing, inventory, SEO, sale scheduling |
| `ProductCategory` | `product_categories` | Hierarchical categories (`parent_id`) |
| `ProductTag` | `product_tags` | Tags (pivot `product_tag_product`) |
| `ProductBrand` | `product_brands` | Brands (pivot `product_product_brand`) |
| `ProductReview` | `product_reviews` | Ratings with approve/reject workflow, rating cache |
| `ProductDownload` | `product_downloads` | Downloadable files for digital products |
| `Attribute` | `attributes` | Global attributes with `display_type` (text/color/image/button) |
| `AttributeTerm` | `attribute_terms` | Attribute values with optional `swatch_value` |
| `ProductAttribute` | `product_attributes` | Product↔attribute join with `visible`/`variation` flags |
| `ProductVariation` | `product_variations` | One row per attribute-term combination |
| `ProductVariationAttributeTerm` | `product_variation_attribute_term` | Queryable pivot (source of truth) |
| `Cart` | `carts` | Guest/auth cart, coupon, stacking; `$appends = ['subtotal', 'total']` |
| `CartItem` | `cart_items` | Cart line items (product + variation) |
| `Wishlist` | `wishlists` | Per-user saved products |
| `Order` | `orders` | Billing/shipping, status workflow, refunds, applied coupons |
| `OrderItem` | `order_items` | Line items (product + variation) |
| `OrderRefund` | `order_refunds` | Partial refunds with line-item breakdown |
| `OrderNote` | `order_notes` | Public + private order notes |
| `OrderAction` | `order_actions` | Order timeline / audit trail |
| `Coupon` | `coupons` | percentage/fixed/free_shipping/buy_x_get_y/fixed_product/percentage_product/tiered; automatic discounts, BOGOF + product-scoped + quantity tiers, stacking, limits |
| `CouponUsage` | `coupon_usages` | Coupon usage tracking |
| `ShippingMethod` | `shipping_methods` | Flat-rate methods |
| `ShippingZone` | `shipping_zones` | Country-based zones, per-method override pricing |
| `TaxRate` | `tax_rates` | Per-country rates with default fallback |
| `Currency` | `currencies` | Multi-currency with exchange rates |
| `StockAdjustment` | `stock_adjustments` | Inventory audit trail (who/what/why) |
| `Webhook` | `webhooks` | Outbound webhooks, HMAC-SHA256 signing |
| `WebhookCall` | `webhook_calls` | Webhook delivery log |
| `BackInStockSubscription` | `back_in_stock_subscriptions` | "Notify me" requests |
| `ProductBrand`/`ProductDownload` | see above | Taxonomy / digital assets |

### Variations (two-layer storage)

1. **Pivot `product_variation_attribute_term`** — authoritative, indexed, SQL-queryable:
   ```
   product_variation_id | attribute_id | attribute_term_id
   ```
2. **JSON `attributes` column** on `product_variations` — read-only cache for API/frontend responses.

```php
ProductVariation::whereAttributeTerm($redTermId)->get();
Product::whereVariationAttributeTerm($redTermId)
    ->whereVariationAttributeTerm($sizeMTermId)->get();
```

## Services (13)

| Service | Purpose |
|---------|---------|
| `ShopService` | Singleton; dashboard stats (uses `Order::distinct('user_id')` — the core User model has **no** `orders()` relation) |
| `InventoryService` | Central stock mutations: `adjust()` (Product or ProductVariation), `recordSaleForOrder()`, `recordRefundForOrder()`, manual adjust, history, low-stock + stock-changed webhooks |
| `CheckoutService` | Checkout orchestration: validate stock, record sale, tax, coupon, convert cart, send notifications |
| `CouponService` | `validateAndApply()`, `calculateDiscount()`, `calculateStackedDiscount()`, `recordUsage()`, `getAutoDiscounts()`, `validateStack()` — applies auto-discounts at checkout |
| `OrderStatusService` | 10 statuses + transition map + `canTransition()`/`validateTransition()` |
| `OrderActionService` | Order timeline entries (created/status changed/refunded/edited/notes) |
| `ShippingService` | Zone-matched methods (`whereJsonContains('countries', …)`), fallback to all active |
| `TaxService` | Country-matched rates with `is_default` fallback → `{ tax_total, breakdown }` |
| `WebhookService` | HMAC-signed dispatch, retries, auto-deactivation after failures, webhook calls log |
| `CurrencyService` | Active currencies, default detection, Accept-Language detection |
| `CartMergeService` | Merges guest cart into user cart on login |
| `AbandonedCartService` | Abandonment detection + 3-step email sequence with signed recovery URLs |
| `StripeService` | Stripe PaymentIntent creation/confirmation/refunds |

### Order statuses (`OrderStatusService`)

`draft`, `pending`, `on-hold`, `confirmed`, `processing`, `completed`, `cancelled`, `refunded`, `failed`, `trash` — with a validated transition map. `OrdersController` enforces transitions and records the timeline via `OrderActionService`.

## Controllers

- **Admin (`dashboard.shop.*`, permission `shop.view`):** `ProductsController` (CRUD, status, import/export, stock history, adjust stock, images), `OrdersController` (CRUD, status, mark-paid, partial refund, invoice PDF, notes, draft conversion, search products/customers), `CategoriesController`, `TagsController`, `BrandsController`, `AttributesController`, `ProductVariationsController`, `CustomersController` (CRUD + export), `ReportsController`, `CouponsController`, `ReviewsController`, `ShippingMethodsController`, `ShippingZonesController`, `TaxRatesController`, `CurrenciesController`, `WebhooksController`.
- **Admin settings (`admin.shop.*`, admin + `shop.view`):** `ShopSettingsController` (`PUT /admin/shop/settings`).
- **Storefront (guest):** `StorefrontController` (grid/detail/category), `CartController` (JSON cart CRUD + coupons), `CheckoutController`, `WishlistController`, `NotifyBackInStockController`, `AbandonedCartRecoveryController` (signed), `StripeWebhookController` (`POST /store/webhook/stripe`, signature-verified).
- **Customer portal (`dashboard.my.*`, auth):** my orders, my checkout, guest check-in.
- **API (`/api/...`, `auth:sanctum`):** `Api\ProductsController` (index/show), `Api\OrdersController` (index/show).

## Routes (11 files, self-loaded)

| File | Prefix | Middleware | Purpose |
|------|--------|-----------|---------|
| `customer-portal.php` | `/dashboard` (`dashboard.`) | web, auth, verified | My orders / my cart / my checkout |
| `web.php` | `/dashboard/shop` (`dashboard.shop.`) | web, auth, verified, `permission:shop.view` | All admin management incl. order invoice/refund/notes, product import/export |
| `admin.php` | `/admin/shop` (`admin.shop.`) | web, auth, verified, admin, `permission:shop.view` | Shop settings |
| `storefront.php` | `/store` (`store.`) | web (guest) | Product grid, detail, category |
| `store-cart.php` | `/store/cart` (`store.cart.`) | web (guest) | Guest cart CRUD + coupons (JSON) |
| `store-checkout.php` | `/store/checkout` (`store.checkout.`) + `store.webhook.stripe` | web (guest) | Checkout for all users + Stripe listener |
| `store-wishlist.php` | `/store/wishlist` (`store.wishlist.`) | web (guest) | Wishlist add/remove |
| `store-notify.php` | `/store/notify-back-in-stock` | web (guest) | Back-in-stock subscription |
| `recovery.php` | `/store/cart/recover/{cart}` (`store.cart.recover`) | signed | Abandoned-cart recovery link |
| `api.php` | `/api` (declares its own `api.` names) | api | Public products index/show |
| `api-auth.php` | `/api` (declares its own `api.` names) | api, `auth:sanctum` | Authenticated orders index/show |

## Policies & permissions

**Policies (9):** `ProductPolicy`, `OrderPolicy`, `CouponPolicy`, `CurrencyPolicy`, `ProductReviewPolicy`, `ShippingMethodPolicy`, `ShippingZonePolicy`, `TaxRatePolicy`, `WebhookPolicy`.

**Permissions (16)** — synced with `php artisan permissions:sync`:

| Slug |
|------|
| `shop.view` |
| `shop.products.create` / `shop.products.edit` / `shop.products.delete` |
| `shop.orders.view` / `shop.orders.edit` / `shop.orders.refund` |
| `shop.tags.manage` |
| `shop.brands.manage` |
| `shop.coupons.manage` |
| `shop.reviews.moderate` |
| `shop.shipping.manage` |
| `shop.taxes.manage` |
| `shop.webhooks.manage` |
| `shop.currencies.manage` |

## Commands (5)

| Command | Schedule | Purpose |
|---------|----------|---------|
| `shop:release-expired-holds` | every 5 min | Cancels unpaid pending orders past `hold_stock` window, restores stock |
| `shop:abandoned-cart-process` | hourly | Marks abandoned carts + sends 1h/24h/72h recovery emails |
| `shop:process-pending-webhooks` | — | Retries pending webhook calls |
| `shop:process-scheduled-sales` | — | Applies/ends scheduled sale pricing |
| `shop:product-seed` | — | Seeds demo products |

`schedule:publish` (published products/posts/properties) lives in the core `dashboard-core` package.

## Email templates (7)

`order-confirmation`, `order-shipped`, `order-invoice`, `low-stock-alert`, `cart-recovery-1h`, `cart-recovery-24h`, `cart-recovery-72h`.

Registered in `getEmailTemplates()` (simulator samples): `order-confirmation`, `order-shipped`, `low-stock-alert`.

## Notification types (4)

| Event | Default channels | Template |
|-------|------------------|----------|
| `order.placed` | in_app, email | order-confirmation |
| `order.status_changed` | in_app, email | order-shipped |
| `low_stock` | in_app | — |
| `product.stock_changed` | in_app (disabled by default) | — |

## Webhooks

- **Events** are plain strings (not centrally enumerated): `order.created`, `order.status_changed`, `order.refunded`, `product.stock_changed`, `product.low_stock`, plus `order.placed`, `order.updated`, `order.cancelled`, `product.created`, `product.updated`, `product.deleted`, `customer.created`, `customer.updated`, `webhook.test` fired from controllers.
- `WebhookService::dispatch()` fans out to all active webhooks subscribed to the event; each send runs through the queue with HMAC-SHA256 signature in `X-Webhook-Signature`, logged to `webhook_calls`, auto-deactivated after consecutive failures.
- **Stripe:** `POST /store/webhook/stripe` verifies the signature, handles `checkout.session.completed` / `charge.refunded`, and raises internal events.

## Key config (`config/shop.php`, merged as `config('shop')`)

```php
'currency_symbol'  => env('SHOP_CURRENCY_SYMBOL', '€'),
'abandoned_cart'   => [ 'enabled' => env('ABANDONED_CART_ENABLED', true),
                        'hours_until_abandoned' => env('ABANDONED_CART_HOURS', 1),
                        'sequence_hours' => [1, 24, 72] ],
'hold_stock'       => [ 'enabled' => env('HOLD_STOCK_ENABLED', true),
                        'default_minutes' => (int) env('HOLD_STOCK_MINUTES', 60) ],
'inventory'        => [ 'default_low_stock_threshold' => (int) env('DEFAULT_LOW_STOCK_THRESHOLD', 5), 'enabled' => true ],
'sale_scheduling'  => [ 'enabled' => env('SALE_SCHEDULING_ENABLED', true), 'normalize_to_day_boundaries' => true ],
'storefront.hero'  => title / subtitle / cta (env-driven)
```

Shop admin settings (store profile, selling locations, currency & pricing, tax options, inventory, checkout, units, features) are stored in the `settings` table with `group = 'Shop'` via `ShopSettingsController` at `GET|PUT /admin/shop/settings` (page key `shop/settings/index`). Typed access via `ShopSettingsService` (defaults, `currencyFormat()`, `isCountryAllowed()`); country options come from the `Countries` ISO 3166 support class.

## Inertia shared props (from the provider)

`currencies`, `selectedCurrency` (with Accept-Language auto-detect), `currencyPosition`, `cart` (`{ id, items[], subtotal, total, coupon_code, discount }` with product/variation detail), `wishlistIds`, `shopSettings` (full settings array), `currencyFormat` (`{ position, thousands_separator, decimal_separator, decimals }`).

## Frontend pages

Inertia React pages under `src/Resources/js/Pages/` (aliased `@shop/Pages/...`, registered under `shop/*` keys):

`Products/` (Index, Form, Show, StockHistory), `Orders/` (Index, Form/Show, My orders), `Cart/`, `Checkout`, `Categories/`, `Tags/`, `Brands/`, `Attributes/`, `Customers/` (Index, Show), `Coupons/` (Index, Form), `Reviews/`, `ShippingMethods/`, `ShippingZones/`, `TaxRates/`, `Currencies/`, `Webhooks/`, `Wishlist/`, `My/` (orders, cart), `Settings/` (Index), `Storefront/` (ProductGrid, ProductDetail, ShoppingCart, AttributeSelector).

**Note:** the package JS is currently excluded from the host `tsconfig` `include` (parity with the pre-extraction state; ~150 pre-existing TS errors live in these files). `@shop` path mapping is in place for when that is tackled.

## Development workflow (in the host repo)

The package lives at `packages/shop-module` and is **mirrored** (not symlinked) into `vendor/nreljed/shop-module`.

```bash
# After editing package PHP, re-mirror into the app:
Remove-Item vendor/nreljed/shop-module -Recurse -Force
composer update nreljed/shop-module --no-scripts

# Frontend: rebuild (the app runs the production build — no `npm run dev`)
npm run build

# Quality gate
vendor/bin/pint
php artisan permissions:sync
php artisan test
npm run typecheck
```

Reminder: a missing Tailwind `@source` for the shop JS directory silently strips all shop utility classes from the built CSS.
