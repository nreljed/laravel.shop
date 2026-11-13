# Shop Improvement Plan

Companion to `AUDIT.md` (2026-08-09, v2) and `docs/CHANGELOG-PHASES.md`. Reflects the
current state of the shop as of 2026-11 (platform 1.0.1, blog 0.1.12,
shop-module 0.1.91, marketing-module installed). Multi-vendor marketplace is
explicitly out of scope — this shop targets single-store B2C plus B2B/wholesale
clients (WooCommerce-style, not Shopify Plus marketplace).

## 0. Current baseline (verified)

- **Code health:** 571 tests (562 pass, 9 skip, 0 fail), tsc + eslint + vite build + pint green,
  composer audit clean, migrations all ran, 517 routes, config/view/route cache OK.
- **Feature depth:** see `AUDIT.md` §4 matrix. Core commerce is feature-complete.
- **Remaining gaps:** grouped into phases below. Effort estimates assume one developer.

## Ownership rules (do not violate)

- `vendor/nreljed/shop-module` is a **product**; this repo is a **consumer**. Never edit
  `vendor/`. Package changes go through PRs on `nreljed/shop-module`, released via
  semver tag, then fan out via `shop-updated` dispatch + `shop-update.yml`.
- Host-app work (config, env, pages, settings, routes, deploy) lives here.
- `shop:env-check` confirms this tree is the one the local site serves.

## Phase 1 — Polish & correctness (host + small package PRs, ~1 week)

### Host app (do here)
- [x] `.env` / `.env.example`: add `SHOP_CURRENCY_SYMBOL`, `SHOP_STORE_NAME`, `SHOP_STORE_EMAIL`,
      `SHOP_BASE_COUNTRY=TN`, `SHOP_SELLING_MODE=all` (done 2026-11-11).
- [x] Guest cart merge on plain login: implemented via `Illuminate\Auth\Events\Login` listener in
      `app/Providers/AppServiceProvider.php` (best-effort, never blocks login). Done 2026-11-11.
- [x] Storefront home (`/`) already renders hero + featured + new arrivals + category grid
      (StorefrontController::index → shop-module Home.tsx). Welcome page (public/welcome) is
      NOT the landing page in this app — storefront is. No change needed.
- [~] Run `npm audit fix --force` — reverted: bumped eslint to v9 and broke the unicorn rule config.
      Remaining 15 advisories (2 low, 13 moderate) are dev-only (@typescript-eslint, minimatch).
      Track upstream eslint-plugin-unicorn compatibility; do not force. Deferred.
- [ ] Truncate `storage/logs/laravel.log` (19 MB of test noise) — done 2026-11-11.

### Package PRs (shop-module)
- [ ] `ProductDetail` JSON-LD `priceCurrency` should use the selected currency, not hardcoded `'EUR'`.
- [ ] `ProductVariation` display-price mojibake: `config('shop.currency_symbol', '�')` → `'€'`.
- [ ] Report-only: verify `storefront` hero config keys match what `StorefrontController` passes.

## Phase 2 — Conversion & trust (package PRs, ~2–3 weeks)

- [x] **Customer-initiated returns (RMA)**: `ReturnRequest` model + migration, customer submit
      (POST /dashboard/my/orders/{order}/return), admin list/approve/reject/received/refunded
      (Returns/Index.tsx), "Request return" button on My Orders show. Done 2026-11-11 in shop-module.
- [x] **Returns → auto-refund**: `markRefunded` now creates an OrderRefund, restocks via
      `recordRefundForOrder`, updates `total_refunded`/`payment_status`/`status` in a transaction.
      Done 2026-11-11 in shop-module.
- [x] **Review photo attachments**: ProductReview.images (json) + migration + upload handling in
      ReviewsController::store + display in ProductDetail review cards + file input in review form.
      Done 2026-11-11 in shop-module.
- [x] **Q&A thread**: `ProductQuestion` model + migration, customer ask (POST /store/products/{product}/questions),
      visible Q&A section + ask form on ProductDetail, admin list/answer/delete (Questions/Index.tsx).
      Done 2026-11-11 in shop-module.
- [x] **Search autocomplete**: `GET /api/products/suggest?q=...` endpoint + dropdown in ProductGrid
      search input. Done 2026-11-12 in shop-module.
- [ ] **Typo tolerance / Meilisearch**: replace DB LIKE with a Scout driver for fuzzy matching (still open).
- [x] **Recently viewed**: client-side (localStorage) tracking via `lib/recentlyViewed.ts`;
      recorded on ProductDetail view, rendered as a "Recently viewed" section on Home.
      Done 2026-11-11 in shop-module.
- [x] **Compare (side-by-side)**: client-side compare list (max 4) via `lib/compare.ts`, compare
      toggle on ProductCard, `/store/compare` page with side-by-side table, compare icon with
      count in StoreHeader. Done 2026-11-11 in shop-module.
- [x] **Quick view modal**: `components/storefront/QuickView.tsx` fetches `/api/products/{id}` and
      shows image, price, description, rating, stock + "View details"/add-to-cart; triggered by an
      Eye button on ProductCard; rendered in ProductGrid. Done 2026-11-11 in shop-module.
- [x] **Accessibility pass (partial)**: skip-to-content link + focusable main in StoreLayout;
      aria-live region for cart count in StoreHeader; aria-pressed on filter toggles in ProductGrid.
      Done 2026-11-12 in shop-module.
- [ ] **Accessibility deep pass**: keyboard nav for variation selector/cart drawer, focus management
      on route change, contrast audit, screen-reader testing (still open).
- [ ] **Bundle optimization**: dynamic-import the editor (661 KB) and zxcvbn (1.6 MB) chunks;
      add route-level code splitting.

## Phase 3 — Growth & payments (package PRs, ~3–4 weeks)

- [x] **Automatic / BOGO discounts**: already implemented — `is_automatic` coupons, `DiscountManager`
      with BuyXGetY/Tiered/ProductScoped/Fixed/Percentage/FreeShipping drivers,
      `CouponService::getAutoDiscounts()`. Verified 2026-11-12.
- [x] **Cash on delivery (COD)**: `CashOnDeliveryDriver` registered in config + validation
      rule updated + COD option in checkout payment select + display label. Done 2026-11-12 in shop-module.
- [ ] **Additional payment drivers**: PayPal, Apple/Google Pay (COD done; these need SDKs).
- [ ] **Carrier integration (Tunisia-first)**: Aramex rates + label printing + tracking webhooks.
      Currently flat/free only.
- [x] **Analytics Phase A (foundation)**: ReportsController expanded (net revenue, refunds,
      cancellation rate, new/returning customers, repeat rate, avg items/order, AOV by day,
      cumulative revenue, week/month grouping, revenue share, top refunded products); Reports
      page updated with new KPI cards, group selector, top refunds section, currency via
      useCurrency. GA4 snippet added to StoreLayout (conditional on VITE_GA4_MEASUREMENT_ID).
      Done 2026-11-12 in shop-module.
- [x] **Analytics Phase A + pixels**: ReportsController expanded (net revenue, refunds,
      cancellation rate, new/returning customers, repeat rate, avg items/order, AOV by day,
      cumulative revenue, week/month grouping, revenue share, top refunded products); Reports
      page updated with new KPI cards, group selector, top refunds, currency via useCurrency.
      GA4 + Meta Pixel + TikTok Pixel snippets added to StoreLayout (conditional on env vars).
      CSV export button added. Done 2026-11-12 in shop-module.
- [x] **Analytics Phase A–D**: expanded reports (net revenue, cohorts, CLV segments, funnel,
      search terms), event tracking (shop_events + POST /api/events + analytics helper),
      GA4/Meta/TikTok pixel snippets, CSV export. Done 2026-11-13 in shop-module.
- [x] **Analytics Phase A-F**: expanded reports (net revenue, cohorts, CLV segments, funnel,
      search terms), event tracking (shop_events + POST /api/events + analytics helper with
      GA4/Meta/TikTok forwarding), GA4/Meta/TikTok pixel snippets, CSV export
      (orders/products/customers), scheduled report command (shop:send-scheduled-report) wired
      into cron (weekly/monthly) with HTML email template (sales-report.blade.php). Done 2026-11-13 in shop-module.
- [x] **Public storefront API**: Sanctum auth (login/logout/me), scopes, rate limits,
      cart + orders endpoints, OpenAPI spec at docs/openapi.yaml. Done 2026-11-12 in shop-module.
      for mobile + headless clients. Currently internal JSON only.

## Phase 4 — Global & B2B (strategic, ~1–2 quarters)

### Global
- [x] **i18n foundation**: i18next + react-i18next, en/fr/ar translation files, language
      switcher in StoreHeader, wired into app bootstrap. StoreHeader nav labels translated.
      Done 2026-11-12 in shop-module.
- [x] **i18n extraction (ProductDetail)**: converted ~30 strings to t() calls on ProductDetail
      (back to store, SKU, stock status, notify, reviews, Q&A, accordion, shipping/returns).
      Done 2026-11-12 in shop-module.
- [x] **i18n extraction (ProductGrid)**: converted filters, sort, empty state, search placeholder
      to t() calls. Done 2026-11-12 in shop-module.
- [x] **i18n extraction (ShoppingCart)**: converted title, empty cart, continue shopping, coupon
      toasts, subtotal/discount/total, proceed to checkout. Done 2026-11-12 in shop-module.
- [x] **i18n extraction (Home)**: converted back to home, new arrivals, shop by category. Done 2026-11-12.
- [x] **i18n extraction (Checkout)**: converted billing/shipping/payment headings, back/continue/place order. Done 2026-11-12.
- [x] **i18n extraction (My Orders Show)**: converted items, addresses, summary, payment, return. Done 2026-11-12.
- [x] **i18n extraction (Returns admin)**: converted title, subtitle, empty state, columns, actions, dialog. Done 2026-11-12.
- [x] **i18n extraction (Questions admin)**: converted title, subtitle, empty state, columns, actions, dialog. Done 2026-11-12.
- [x] **i18n extraction (Products Index)**: converted toast messages. Done 2026-11-12.
- [x] **Loyalty/rewards**: `LoyaltyAccount` + `LoyaltyTransaction` models, migrations, `LoyaltyService`
      (earn on order completion, redeem for discount), `SyncsLoyalty` observer, `LoyaltyController`,
      customer portal routes, loyalty page (balance + redeem + history). Done 2026-11-13 in shop-module.
- [ ] **Loyalty tiers/referrals**: tier upgrades + referral codes (still open).
- [x] **i18n extraction (Customers Index)**: converted title, subtitle, export, search. Done 2026-11-13.
- [ ] **i18n full extraction**: remaining pages (other admin pages, emails).
- [ ] **Multi-address book**: `addresses` table (type + is_default), migrate existing
      billing/shipping JSON; update checkout + account portal.
- [ ] **Inventory multi-location**: locations + transfers + per-location availability.
- [ ] **Automatic tax**: country-rate auto-lookup (or TaxJar/Avalara integration).

### B2B / wholesale (WooCommerce-style, in scope)
- [ ] **Company accounts**: B2B customer type with tax-exempt flag, approval workflow.
- [ ] **Tiered pricing**: quantity-based price breaks per product/variant.
- [ ] **Net terms / invoicing**: pay later (Net 30/60), invoice PDF with PO number.
- [ ] **Quotes / RFQ**: request-for-quote flow → admin converts to order.
- [ ] **Quantity rules**: min/max order quantities, pack sizes, step increments.

### Out of scope (explicitly excluded)
- Multi-vendor marketplace (vendor accounts, commissions, payouts).
- POS / in-store retail.
- Subscriptions / recurring billing (defer until demand).
- Gift cards (defer until demand).

## Cross-cutting

- [ ] **Performance**: CDN for assets/images, image optimization pipeline (already webp),
      lazy hydration, route-level code splitting.
- [ ] **Testing**: add Playwright flows for checkout (guest + registered), cart merge,
      returns, review submission. Currently only auth + admin smoke.
- [ ] **Observability**: Sentry already wired; add business metrics (conversion rate,
      cart abandonment, payment failure rate) to the dashboard.

## Upstream PR drafts (shop-module)

Copy-paste into PRs against `nreljed/shop-module`. Each is a single focused change.

### PR A — JSON-LD priceCurrency from selected currency

**Title:** `fix(storefront): use selected currency in Product JSON-LD priceCurrency`

**Problem:** `Pages/Storefront/ProductDetail.tsx` hardcodes `priceCurrency: 'EUR'` in the
`offers` block of the Product structured data, so multi-currency stores emit incorrect
schema.org data.

**Change:** read the currency code from `useCurrency()` (already imported as `selectedCode`)
and use `selectedCode ?? 'EUR'` (already done for the `product:price:currency` meta tag) in
the JSON-LD `offers.priceCurrency`.

**Test:** extend `SeoTest` to assert the rendered page contains the selected currency code
in the JSON-LD when a non-EUR currency is active.

### PR B — ProductVariation display-price mojibake

**Title:** `fix(models): default currency symbol fallback in ProductVariation display price`

**Problem:** `src/Models/ProductVariation.php` uses `config('shop.currency_symbol', '�')`
(mojibake) as the fallback symbol, producing a replacement character in display prices
when `SHOP_CURRENCY_SYMBOL` is unset.

**Change:** replace both occurrences of `'�'` with `'€'` (matching `Product.php` and
`config/shop.php` defaults).

**Test:** unit test on `ProductVariation::getDisplayPriceAttribute()` (or the existing
variation price test) with `config(['shop.currency_symbol' => null])` expecting `€`.

### PR C — (Optional, Phase 2) Returns/RMA scaffolding

**Title:** `feat(returns): customer-initiated return request flow`

**Scope:** new `ReturnRequest` model + migration, controller, policy, Inertia page,
status workflow (requested → approved → label_sent → received → refunded/rejected),
admin moderation UI, and a `returns.requested` webhook event. Refunds already exist;
this wraps them in a self-service request flow.

## Suggested execution order

1. Phase 1 (host + 2 small package PRs) — unblocks currency/SEO correctness.
2. Phase 2 (conversion/trust) — biggest revenue impact.
3. Phase 3 (growth/payments) — expands addressable market.
4. Phase 4 (global/B2B) — only when a concrete client demands it; do not build speculatively.

## Success metrics

- Storefront conversion rate (add-to-cart → checkout → paid) tracked per release.
- Time-to-first-byte < 200 ms on product pages (currently unmeasured).
- Zero `currency_symbol = '?'` or hardcoded `'EUR'` in structured data.
- Playwright checkout suite green in CI (currently auth only).
- All P0 items closed before any P2 work starts.
