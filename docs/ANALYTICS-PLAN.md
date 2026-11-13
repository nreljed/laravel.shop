# Analytics Plan — Shop Module

Companion to `docs/SHOP-IMPROVEMENT-PLAN.md`. Current analytics live in
`shop-module/src/Http/Controllers/ReportsController.php` and
`shop-module/src/Resources/js/Pages/Reports/Index.tsx`. They are basic: revenue,
orders, AOV, products sold, top products, sales-over-time bar chart, 7/30/90/365-day ranges.

## Free / open-source solutions (no paid services)

The shop runs on shared hosting (DirectAdmin, PHP + MySQL, FTP fallback, no SSH), so
self-hosted options must run on PHP + MySQL. That constraint eliminates most modern
analytics stacks. Ranked for this environment:

| Solution | Hosting fit | Cost | E-commerce tracking | Best for |
|----------|-------------|------|---------------------|----------|
| **Matomo** (self-hosted) | ✅ PHP + MySQL, runs on shared hosting | Free (GPL) | ✅ Native: orders, revenue, products, cart abandonment, goals | **Recommended primary** — full e-commerce analytics, data stays on your server |
| **Google Analytics 4** | Cloud (no hosting needed) | Free | ✅ E-commerce events (purchase, add_to_cart, view_item) | Free cloud option; data goes to Google (privacy/GDPR trade-off) |
| **Umami** (self-hosted) | ❌ Needs Node.js + Postgres/MySQL | Free (MIT) | ❌ Page views + custom events only (no native e-commerce) | Lightweight traffic analytics if Node.js is available |
| **GoatCounter** (self-hosted) | ✅ Go binary + SQLite/Postgres | Free (BSD) | ❌ Basic page views + events | Simplest self-hosted traffic counter |
| **PostHog** (self-hosted) | ❌ Needs Docker + ClickHouse | Free (MIT) | ✅ Funnels, cohorts, session replay, feature flags | Product analytics for SaaS — too heavy for shared hosting |
| **Open Web Analytics (OWA)** | ✅ PHP + MySQL | Free (GPL) | 🟡 Basic e-commerce goals | Older, less maintained than Matomo |
| **Ackee** (self-hosted) | ❌ Needs Node.js + MongoDB | Free (MIT) | ❌ Page views only | Minimalist, not e-commerce focused |

### Recommendation for this shop

1. **Primary: Matomo (self-hosted)** — best fit for PHP/MySQL shared hosting. Native
   e-commerce tracking (orders, revenue, products, categories, cart abandonment),
   custom dimensions, goals, and GDPR-compliant (data never leaves your server).
   Install via the DirectAdmin one-click installer or upload the zip via FTP.
   Embed the tracking snippet in `StoreLayout.tsx` and fire e-commerce events on
   checkout completion.

2. **Secondary (optional): GA4** — free cloud analytics for traffic-source attribution
   and Google Ads integration. Only if the store runs paid Google campaigns. Adds a
   second tracking snippet; consider consent banner for EU visitors.

3. **Internal reports (Phase A–D below)** — the shop's own `ReportsController` uses
   existing order data at zero cost. Enhance it (Phase A) before adding external tools;
   it covers revenue, orders, top products, and (with Phase C event tracking) funnel
   and conversion without any external service.

### Privacy note
If targeting EU customers, Matomo self-hosted avoids GDPR consent requirements for
analytics cookies (with cookieless mode). GA4 requires a consent banner. Matomo
can also be configured cookieless.

### Integration with the internal reports system
- Phase C (event tracking) can write events to the local `shop_events` table AND
  forward them to Matomo via its HTTP API — no double work.
- Matomo's e-commerce API (`trackEcommerceOrder`, `addEcommerceItem`) can be called
  from `CheckoutController::store()` after order creation, mirroring the existing
  webhook dispatch pattern.

## 0. Current state (verified)

- **Backend:** `ReportsController::index()` aggregates completed orders only.
  - Summary: total_revenue, total_orders, avg_order_value, total_products_sold.
  - Sales over time: daily COUNT + SUM(total).
  - Top products: by qty sold + revenue (limit 10).
- **Frontend:** 4 KPI cards, a CSS bar chart, a top-products table. Range selector.
- **Gaps vs Shopify/GA4:** no conversion funnel, no cohort/LTV, no customer segments,
  no product performance (views→cart→purchase), no traffic sources, no refunds/cancellation
  impact, no inventory turnover, no AOV trend, no repeat-purchase rate, no GA4 events,
  no export.

## Phase A — Foundation (backend + frontend, ~1 week)

### A1. Expand the summary block
Add to `ReportsController`:
- `total_refunds` / `refund_rate` (from `OrderRefund`).
- `cancelled_orders` / `cancellation_rate`.
- `gross_revenue` vs `net_revenue` (revenue − refunds).
- `new_customers` vs `returning_customers` (distinct user_ids in completed orders;
  guest orders counted as new).
- `repeat_purchase_rate` (% of customers with ≥2 completed orders).
- `avg_items_per_order`.
- `conversion inputs` placeholder: cart count, checkout count (needs event tracking — Phase C).

### A2. Time-series upgrades
- Add `revenue_by_day` (already) + `orders_by_day` (already) + `aov_by_day`.
- Add `cumulative_revenue` running total for the period.
- Support grouping by week/month (new `group` param: day|week|month).

### A3. Top products — richer
- Add `conversion_rate` per product (needs view tracking — Phase C; ship as null until then).
- Add `revenue_share` (% of total).
- Add `return_rate` per product (refunds / sales).
- Sort options: by revenue, by qty, by margin (cost_price).

### A4. New sections on the Reports page
- **Revenue breakdown**: gross vs refunds vs net (3 KPIs + mini bar).
- **Customer metrics**: new vs returning, repeat rate, AOV.
- **Top products table**: add revenue share + (placeholder) conversion.
- **Export CSV** button (revenue, orders, top products).

## Phase B — Cohorts & customers ✅ (implemented 2026-11-13)

Cohort analysis (group customers by first-purchase month, track revenue/orders per cohort),
CLV segments (VIP/regular/one-time/at-risk), refund/cancellation impact. Backend:
`ReportsController::cohortAnalysis()`, `clvSegments()`. Frontend: Cohort Analysis table + Customer Segments table.

### B1. Cohort analysis
- Group customers by first-purchase month.
- For each cohort: size, revenue in M0/M1/M3/M6/M12, retention (repeat rate).
- Backend: new `ReportsController::cohorts()` or extend index with `?section=cohorts`.

### B2. Customer lifetime value (CLV)
- Per-customer: total spent, order count, first/last order, AOV.
- Segment: VIP (top 10%), regular, one-time, at-risk (no order in 90 days).
- Frontend: customer segments table + "at-risk" list.

### B3. Refund & cancellation impact
- Refund reasons (from OrderRefund.reason or a new column).
- Refund trend over time.
- Top refunded products.

## Phase C — Event tracking ✅ (implemented 2026-11-13)

`shop_events` table + `ShopEvent` model + `POST /api/events` endpoint (throttled, allowlisted events).
Frontend `lib/analytics.ts` with `trackEvent()` (GA4/Meta/TikTok forwarding + internal API).
Event emission: ProductDetail (product.viewed, product.added_to_cart), ProductGrid (search.performed,
product.viewed from autocomplete), ShoppingCart (cart.viewed), Checkout (checkout.started),
Confirmation (checkout.completed).

Requires a new `shop_events` table (or reuse the platform's analytics). Events to capture:
- `product.viewed` (product_id, user_id/session, source)
- `product.added_to_cart` (product_id, variant_id, qty)
- `cart.viewed`
- `checkout.started`
- `checkout.completed` (order_id)
- `search.performed` (query, results_count)
- `review.submitted`

Backend:
- `POST /api/events` (throttled, anonymous OK, session-keyed).
- `ShopEvent` model + migration.
- Aggregation queries for funnel + search terms.

Frontend:
- Emit events from ProductDetail (view), ProductCard (click), Cart (add), Checkout (start/complete), Search (performed).

## Phase D — Funnel & conversion ✅ (implemented 2026-11-13)

Conversion funnel (product views → add to cart → checkout started → completed),
cart abandonment rate, search terms report, product performance. Backend:
`ReportsController::funnelData()`, `searchTerms()`. Frontend: Conversion Funnel bars + Top Search Terms table.

- **Conversion funnel**: product views → add-to-cart → checkout started → checkout completed,
  with drop-off % at each step.
- **Cart abandonment rate**: carts created vs orders completed.
- **Search terms report**: top queries, zero-result queries, conversion per query.
- **Product performance**: views, add-to-cart rate, purchase rate, revenue per view.

## Phase E — External integrations ✅ (implemented 2026-11-13)

GA4 + Meta Pixel + TikTok Pixel snippets in StoreLayout (conditional on env vars).
GA4 e-commerce events (view_item, add_to_cart, purchase) via analytics.ts trackEvent().
Meta Pixel events (ViewContent, AddToCart, Purchase).
TikTok Pixel events (ViewContent, AddToCart, CompletePayment).

- **GA4 e-commerce events**: emit `view_item`, `add_to_cart`, `begin_checkout`, `purchase` via gtag.
- **Meta Pixel**: `ViewContent`, `AddToCart`, `Purchase`.
- **TikTok Pixel**: `ViewContent`, `AddToCart`, `CompletePayment`.
- **Server-side tracking** (CAPI) for ad-blocker resilience.

## Phase F - Export & scheduled reports (completed 2026-11-13)

CSV export for orders, products, customers (ReportsController + export buttons on Reports page).
`shop:send-scheduled-report` command (weekly/monthly) emails a summary to the shop owner.
Cron wiring in routes/console.php (weekly Monday 08:00, monthly 1st 08:00).

- CSV export for: orders, products, customers, refunds, events.
- Scheduled email reports (weekly/monthly) to shop owner (reuse mail template system).
- Dashboard widgets (embed top KPIs in the shop dashboard).

## Cross-cutting

- **Currency**: all revenue figures must use the order's stored currency / convert to base.
  Currently hardcoded to EUR in the Reports page — fix to use the selected/base currency.
- **Caching**: cache aggregates for 5–15 min (Redis/file) to avoid heavy queries on dashboard load.
- **Permissions**: `shop.reports.view` already exists; add `shop.reports.export`.
- **Tests**: add feature tests for each new aggregate + a smoke test for the CSV export.

## Suggested execution order

1. Phase A (foundation) — biggest immediate value, no external deps.
2. Phase C (event tracking) — prerequisite for funnel/conversion.
3. Phase D (funnel) — depends on C.
4. Phase B (cohorts/CLV) — high value for retention.
5. Phase F (export/scheduled) — low effort, high utility.
6. Phase E (external pixels) — only if marketing spend justifies it.

## Success metrics

- Reports page loads in < 500 ms with 10k+ orders (currently unmeasured).
- Conversion funnel visible within 2 weeks of enabling event tracking.
- Zero hardcoded `currency: "EUR"` in Reports.tsx (use the selected currency).
- CSV export returns in < 5 s for 10k rows.
