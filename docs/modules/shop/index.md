# Shop Module

> **Canonical documentation:** [`nreljed/shop-module`](https://github.com/nreljed/shop-module) (`README.md`) — the package README is the authoritative, always-current reference. This page is a condensed quick-reference for the host repo.

Manage your online store: products, orders, inventory, and customers.

- **Slug**: `shop`
- **Version**: `1.0.0`
- **Dependencies**: None
- **Has Settings**: Yes
- **Nav Group Label**: Shop

## Sub-Documents

| Document | Description |
|----------|-------------|
| [Controllers](controllers.md) | Controller methods reference |
| [Frontend](frontend.md) | Frontend pages and components |

## Quick Reference

| Attribute | Value |
|-----------|-------|
| **Permissions** | `shop.view`, `shop.products.create/edit/delete`, `shop.orders.view/edit/refund`, `shop.tags.manage`, `shop.brands.manage`, `shop.coupons.manage`, `shop.reviews.moderate`, `shop.shipping.manage`, `shop.taxes.manage`, `shop.webhooks.manage`, `shop.currencies.manage` |
| **Route Files** | `web.php` (admin), `admin.php` (admin settings), `customer-portal.php` (auth), `storefront.php`, `store-cart.php`, `store-checkout.php`, `store-wishlist.php`, `store-notify.php`, `recovery.php` (guest), `api.php`, `api-auth.php` (sanctum) |
| **Dashboard Stats** | `total_products`, `published_products`, `total_orders`, `pending_orders`, `low_stock_count` |
| **Gallery Config** | `product_gallery` (max 10, hidden from library) |
| **Email Templates** | `order-confirmation`, `order-shipped`, `order-invoice`, `low-stock-alert`, `cart-recovery-1h/24h/72h` |
| **Notification Types** | `order.placed`, `order.status_changed`, `low_stock`, `product.stock_changed` |
| **Console Commands** | `shop:release-expired-holds` (5min), `shop:abandoned-cart-process` (hourly), `shop:process-pending-webhooks`, `shop:process-scheduled-sales`, `shop:product-seed`, `schedule:publish` (1min, core package) |
| **Migration Paths** | `[__DIR__.'/../database/migrations']` — 57 auto-loaded migrations for products, orders, cart, attributes, variations, coupons, reviews, shipping, tax, webhooks, stock adjustments, brands, currencies, refunds, notes |
| **Boot** | Registers `ShopService` as singleton; shares `currencies`, `selectedCurrency`, `currencyPosition`, `cart`, `wishlistIds`; registers 9 policies + 5 commands |
| **Config** | `config/shop.php` — hold stock, abandoned cart, inventory defaults, sale scheduling, storefront hero |

## Data Model

### Core Models

| Model | Table | Description |
|-------|-------|-------------|
| `Product` | `products` | Simple and variable products with pricing, inventory, SEO |
| `ProductCategory` | `product_categories` | Hierarchical product categories |
| `ProductTag` | `product_tags` | Product tags |
| `ProductBrand` | `product_brands` | Product brands (BelongsToMany via `product_product_brand`) |
| `ProductReview` | `product_reviews` | Product reviews with approval workflow |
| `Attribute` | `attributes` | Global attributes (Color, Size, Material) |
| `AttributeTerm` | `attribute_terms` | Attribute values (Red, XL, Cotton) |
| `ProductAttribute` | `product_attributes` | Product-attribute join with visibility/variation flags |

### Variation System

**Two-layer storage:**

1. **Pivot `product_variation_attribute_term`** — authoritative source:
   ```
   product_variation_id | attribute_id | attribute_term_id
   ```
   Enables SQL-level filtering (`WHERE EXISTS`), FK constraints, and efficient queries.

2. **JSON `attributes` column** on `product_variations` — read-only cache for API responses (same shape as before, no frontend changes needed).

**Relationships:**

```
Attribute ──1:N──> AttributeTerm
    │                    │
    │                    │  (via product_variation_attribute_term)
    │                    ├──> ProductVariation ──N:1──> Product
    │                    │
    └── (via product_attributes)
         └──> Product (with visible/variation flags)
```

**ProductAttribute** (`product_attributes`):
- Links a global or custom attribute to a product
- Flags: `visible` (filterable on shop), `variation` (used for variation generation)
- Custom attributes store pipe-separated options in `options` JSON column

**ProductVariation** (`product_variations`):
- Each row = one specific combination of attribute terms
- Fields: price, compare_price, sale_price, SKU, barcode, stock, weight, dimensions, images, description, manage_stock, allow_backorders, is_active
- `attributes` JSON column = denormalized cache `[{attribute_id, attribute_term_id, name}]`
- Pivot `product_variation_attribute_term` = queryable source of truth

**Queryable scopes:**
```php
// Find all Red variations
ProductVariation::whereAttributeTerm($redTermId)->get();

// Find all products with Red + Size M variation
Product::whereVariationAttributeTerm($redTermId)
    ->whereVariationAttributeTerm($sizeMTermId)
    ->get();
```

### Shop Models

| Model | Table | Description |
|-------|-------|-------------|
| `Order` | `orders` | Order with billing/shipping, 10-status workflow, refunds, applied coupons |
| `OrderItem` | `order_items` | Order line items (product + variation) |
| `OrderRefund` | `order_refunds` | Partial refunds with line-item breakdown |
| `OrderNote` | `order_notes` | Public + private order notes |
| `OrderAction` | `order_actions` | Order timeline / audit trail |
| `CartItem` | `cart_items` | Cart line items |
| `Cart` | `carts` | Guest/auth cart with coupon + stacking support, `$appends = ['subtotal', 'total']` |
| `Coupon` | `coupons` | Discount coupons (percentage/fixed/free_shipping/buy_x_get_y/fixed_product/percentage_product/tiered, automatic, stacking) |
| `CouponUsage` | `coupon_usages` | Coupon usage tracking |
| `ShippingMethod` | `shipping_methods` | Flat-rate shipping methods |
| `ShippingZone` | `shipping_zones` | Country-based shipping zones with override pricing |
| `TaxRate` | `tax_rates` | Per-country tax rates |
| `Currency` | `currencies` | Multi-currency with exchange rates |
| `Webhook` | `webhooks` | Outbound webhooks with HMAC signing |
| `WebhookCall` | `webhook_calls` | Webhook delivery logs |
| `StockAdjustment` | `stock_adjustments` | Inventory change audit trail |
| `Wishlist` | `wishlists` | Per-user saved products |
| `BackInStockSubscription` | `back_in_stock_subscriptions` | "Notify me" requests |
| `ProductDownload` | `product_downloads` | Downloadable files for digital products |

## Routes

| File | Prefix | Middleware | Purpose |
|------|--------|------------|---------|
| `Routes/web.php` | `/dashboard/shop` | `auth`, `verified`, `shop.view` | Products, Orders, Cart, Customers, Attributes, Coupons, Reviews, Shipping, Tax |
| `Routes/admin.php` | `/admin/shop` | `admin`, `shop.view` | Settings |
| `Routes/webhooks.php` | `/dashboard/shop/webhooks` + `/stripe/webhook` | Auth + public | Webhook management + Stripe listener |
| `Routes/storefront.php` | `/store` | Guest | Storefront product grid, detail, category |
| `Routes/store-cart.php` | `/store/cart` | Guest | Guest cart CRUD (JSON API) |
| `Routes/store-checkout.php` | `/store/checkout` | Guest | Checkout flow (billing, shipping, payment) — used by all users |
| `Routes/customer-portal.php` | `/dashboard/my` | Auth | Customer portal (my orders, my checkout) |

### Key Variation Routes

| Method | Route | Controller | Purpose |
|--------|-------|------------|---------|
| GET | `/products/{product}/variations` | `index()` | List all variations |
| POST | `/products/{product}/variations` | `store()` | Add single variation |
| POST | `/products/{product}/variations/generate` | `generate()` | Cartesian generation from attribute term IDs |
| POST | `/products/{product}/variations/bulk` | `storeBulk()` | Atomic bulk save (create/update/delete) |
| PUT | `/products/{product}/variations/{variation}` | `update()` | Update variation fields + sync attribute terms |
| POST | `/products/{product}/variations/reorder` | `reorder()` | Bulk update sort_order for drag-drop reorder |
| DELETE | `/products/{product}/variations/{variation}` | `destroy()` | Delete variation, revert to simple if last |

### Storefront Routes (Guest)

| Method | Route | Purpose |
|--------|-------|---------|
| GET | `/store` | Product grid with search, filter, sort, pagination |
| GET | `/store/{product}` | Product detail with reviews, rating, variation selector |
| GET | `/store/category/{slug}` | Products by category |
| GET/POST | `/store/cart/...` | Full guest cart CRUD + coupon + checkout |

## Nav Items

```
Manage Shop             (sidebar group label, for managers)
  └─ Shop          (collapsible parent)
      ├─ Dashboard
      ├─ All Products
      ├─ Add Product
      ├─ Orders
      ├─ Create Order
      ├─ Categories
      ├─ Tags
      ├─ Brands
      ├─ Attributes
      ├─ Customers
      ├─ Reports
      ├─ Coupons
      ├─ Reviews
      ├─ Shipping Methods
      ├─ Shipping Zones
      ├─ Tax Rates
      ├─ Currencies
      └─ Webhooks

My Account              (group label, for customers with shop.view)
  └─ My Account
      ├─ My Orders
      ├─ My Cart
      └─ My Account (dashboard account page)
```

## Policies

| Policy | Model | Scope |
|--------|-------|-------|
| `ProductPolicy` | `Product` | Permission-based (`shop.products.*`) |
| `OrderPolicy` | `Order` | Permission-based (`shop.orders.view/edit`) |
| `CouponPolicy` | `Coupon` | Permission-based (`shop.coupons.manage`) |
| `CurrencyPolicy` | `Currency` | Permission-based (`shop.currencies.manage`) |
| `ProductReviewPolicy` | `ProductReview` | Permission-based (`shop.reviews.moderate`) |
| `ShippingMethodPolicy` | `ShippingMethod` | Permission-based (`shop.shipping.manage`) |
| `ShippingZonePolicy` | `ShippingZone` | Permission-based (`shop.shipping.manage`) |
| `TaxRatePolicy` | `TaxRate` | Permission-based (`shop.taxes.manage`) |
| `WebhookPolicy` | `Webhook` | Permission-based (`shop.webhooks.manage`) |

## Services

| Service | File | Purpose |
|---------|------|---------|
| `ShopService` | `Services/ShopService.php` | Singleton; dashboard stats |
| `InventoryService` | `Services/InventoryService.php` | Stock adjustments, sale/refund recording, variation support |
| `CouponService` | `Services/CouponService.php` | Coupon validation, discount calculation, auto-discounts |
| `ShippingService` | `Services/ShippingService.php` | Zone-matched shipping methods |
| `TaxService` | `Services/TaxService.php` | Per-country tax calculation |
| `WebhookService` | `Services/WebhookService.php` | Webhook dispatch with HMAC signing |
| `CheckoutService` | `Services/CheckoutService.php` | Checkout orchestration (stock, tax, coupon, cart conversion) |
| `OrderStatusService` | `Services/OrderStatusService.php` | 10 statuses + transition map validation |
| `OrderActionService` | `Services/OrderActionService.php` | Order timeline / audit trail |
| `CurrencyService` | `Services/CurrencyService.php` | Active currencies, default + Accept-Language detection |
| `CartMergeService` | `Services/CartMergeService.php` | Guest cart → user cart merge on login |
| `AbandonedCartService` | `Services/AbandonedCartService.php` | Abandonment detection + 3-step recovery emails |
| `StripeService` | `Services/StripeService.php` | Stripe PaymentIntent create/confirm/refund |

### InventoryService

Central stock management. All stock mutations go through this service.

**Core methods:**
- `adjust($entity, string $type, int $quantity, ...)`: Caps stock at 0, updates entity, records `StockAdjustment`
- `recordSale($entity, int $quantity)`: Negative adjustment named "sale"
- `recordRefund($entity, int $quantity)`: Positive adjustment named "refund"
- `recordSaleForOrder(Order $order)`: Iterates items, calls `recordSale()` per line (product or variation)
- `recordRefundForOrder(Order $order)`: Same pattern for refunds
- `manualAdjust(Product $product, int $quantity, string $reason)`: Manual adjustment
- `getHistory(Product $product, int $perPage = 20)`: Paginated adjustment log

**Variation support:** `adjust()` accepts `Product|ProductVariation`. `StockAdjustment` has optional `variation_id` FK.

**Integration:** Called from `CheckoutService::checkout()`, `OrdersController::updateStatus()`, `OrdersController::refund()`, `ReleaseExpiredHolds` command.

### CouponService

- `validateAndApply(string $code, float $subtotal, ?int $userId)`: Validates dates/limits/per-user, returns `Coupon` or throws `ValidationException`
- `calculateDiscount(Coupon $coupon, float $subtotal, ?float $shippingCost, ?Collection $lineItems)`: Delegates to model (supports percentage/fixed/free_shipping/buy_x_get_y/fixed_product/percentage_product/tiered)
- `calculateStackedDiscount(Collection $coupons, float $subtotal, ?float $shippingCost, ?Collection $lineItems)`: Applies multiple coupons; only percentage/fixed/tiered reduce the running subtotal
- `validateStack(Coupon $newCoupon, array $existingCoupons, float $subtotal)`: Prevents duplicate / conflicting (e.g. two free_shipping) stack combinations
- `recordUsage(Coupon $coupon, int $orderId, ?int $userId, float $discountAmount)`: Creates `CouponUsage` + increments `used_count`
- `getAutoDiscounts(float $subtotal)`: Returns active automatic coupons valid for subtotal; auto-discounts are merged into checkout even without a code

### ShippingService

- `getAvailableMethods(?array $billingAddress)`: Matches country to zone via `whereJsonContains('countries', $country)`, returns zone methods with override prices. Falls back to all active methods if no zone match.

### TaxService

- `calculate(float $subtotal, ?array $billingAddress, bool $isTaxable)`: Finds rates matching billing address country, falls back to `is_default`. Returns `{ tax_total, breakdown }`.

## Order Workflow (Phase 21)

Fully implemented — statuses, partial refunds, editing, draft orders, invoice PDF, and notes all live in `OrdersController` + `OrderStatusService`.

- **Statuses (10):** `draft`, `pending`, `on-hold`, `confirmed`, `processing`, `completed`, `cancelled`, `refunded`, `failed`, `trash` — transitions validated by `OrderStatusService::validateTransition()`.
- **Partial refunds:** `OrderRefund` (line items JSON breakdown, `shipping_refund`, `created_by`), `total_refunded` accumulation, auto `refunded` status + payment status.
- **Order editing:** `updateAdmin()` with item add/remove/change, stock reconciliation, timeline entry.
- **Draft orders:** `draft` status, admin creation, `convertFromDraft()`.
- **Invoice PDF:** `GET /dashboard/shop/orders/{order}/invoice` via dompdf.
- **Order notes:** `OrderNote` (public + private), store/destroy routes, rendered in the timeline.
- **Timeline:** `OrderActionService` records created/status-changed/refunded/edited/note events.
- **Extra routes:** `mark-paid`, `search-products`, `search-customers`.

## Settings

Stored in `settings` table with `group = 'shop'`. Managed via `ShopSettingsController` at `GET|PUT /admin/shop/settings` — dedicated page `shop/settings/index` (`Settings/Index.tsx`). Typed access via `ShopSettingsService`.

Grouped into accordion sections on the **Shop → Settings** page:

### Store Profile
| Field | Type | Default |
|-------|------|---------|
| `store_name` | text | — |
| `store_email` | email | — |
| `store_address_line1` / `line2` / `city` / `postcode` | text | — |
| `base_country` | select (ISO 3166) | `TN` |

### Selling Locations
| Field | Type | Default |
|-------|------|---------|
| `selling_locations_mode` | select: all/specific/except | `all` |
| `selling_locations` | multi-country | `[]` |

### Currency & Pricing
| Field | Type | Default |
|-------|------|---------|
| `currency` | select: USD/EUR/GBP/TND | `EUR` |
| `currency_symbol` | text | `€` |
| `currency_position` | select: before/after/space_before/space_after | `before` |
| `thousands_separator` / `decimal_separator` | text | `,` / `.` |
| `price_decimals` | number | `2` |

### Tax Options
| Field | Type | Default |
|-------|------|---------|
| `prices_include_tax` | bool toggle | `0` |
| `tax_based_on` | select: billing/shipping | `billing` |
| `tax_shipping` | bool toggle | `0` |
| `tax_display` | select: exc/inc | `exc` |

### Inventory Management
| Field | Type | Default |
|-------|------|---------|
| `default_product_status` | select: draft/published | `draft` |
| `backorder_default` | select: do_not_allow/allow_notify/allow | `do_not_allow` |
| `inventory_enabled` | bool toggle | `1` |
| `low_stock_threshold` | number 0-999 | `5` |
| `hold_stock_enabled` | bool toggle | `1` |
| `hold_stock_minutes` | number 1-10080 | `60` |

### Checkout
| Field | Type | Default |
|-------|------|---------|
| `enable_guest_checkout` | bool toggle | `1` |
| `require_company_field` | bool toggle | `0` |
| `require_terms` | bool toggle | `0` |
| `checkout_default_country` | select (or "None") | `null` |

### Units of Measure
| Field | Type | Default |
|-------|------|---------|
| `weight_unit` | select: kg/g/lb/oz | `kg` |
| `dimension_unit` | select: cm/m/in/ft | `cm` |

### Features & Automation
| Field | Type | Default |
|-------|------|---------|
| `show_out_of_stock` | bool toggle | `1` |
| `enable_reviews` | bool toggle | `1` |
| `enable_wishlist` | bool toggle | `1` |
| `auto_complete_orders` | bool toggle | `0` |
| `abandoned_cart_enabled` / `abandoned_cart_hours_until_abandoned` / `abandoned_cart_sequence_hours` | — | `1` / `1` / `1,24,72` |
| `sale_scheduling_enabled` / `sale_scheduling_normalize_to_day_boundaries` | bool toggle | `1` / `1` |

## Console Commands

| Command | Schedule | Description |
|---------|----------|-------------|
| `shop:release-expired-holds` | Every 5 min | Cancels unpaid pending orders older than `hold_stock.default_minutes`, refunds stock |
| `shop:abandoned-cart-process` | Hourly | Marks abandoned carts, sends 3-step recovery email sequence (1h/24h/72h) |
| `shop:process-pending-webhooks` | — | Retries pending webhook calls |
| `shop:process-scheduled-sales` | — | Applies/ends scheduled sale pricing |
| `shop:product-seed` | — | Seeds demo products |
| `schedule:publish` | Every minute | Publishes scheduled products/posts/properties (core dashboard-core package) |

## Config (`config/shop.php`)

```php
'currency_symbol' => env('SHOP_CURRENCY_SYMBOL', '€'),

'abandoned_cart' => [
    'enabled' => env('ABANDONED_CART_ENABLED', true),
    'hours_until_abandoned' => env('ABANDONED_CART_HOURS', 1),
    'sequence_hours' => [1, 24, 72],
],

'hold_stock' => [
    'enabled' => env('HOLD_STOCK_ENABLED', true),
    'default_minutes' => (int) env('HOLD_STOCK_MINUTES', 60),
],

'inventory' => [
    'default_low_stock_threshold' => (int) env('DEFAULT_LOW_STOCK_THRESHOLD', 5),
    'enabled' => true,
],
```

## Hold Stock Data Flow

```
Order placed → CheckoutService → recordSaleForOrder() → stock decremented
  ↓
Order is "pending" (unpaid). Stock is "held".
  ↓
[shop:release-expired-holds runs every 5 min]
  → Finds pending unpaid orders older than hold_stock_minutes
  → recordRefundForOrder() restores stock
  → Order cancelled
```

## Meta Formatting

- `price_fields`: `['price', 'compare_price', 'cost_price', 'unit_price', 'total', 'subtotal']`

## User Cleanup

On user deletion, order `user_id` is set to null (orders kept for history).
