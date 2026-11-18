# Modernization & Standards Alignment Plan

Evidence-based audit of legacy patterns and standards gaps across the shop app and its
packages, with prioritized actions. Findings were grepped (not estimated) on 2026-11-17
against `laravel.shop` main, vendored `platform` 1.0.8 and `shop-module` 0.1.107.

> Ownership rules from SHOP-IMPROVEMENT-PLAN.md apply: package items are PRs in
> `nreljed/platform` / `nreljed/shop-module`, never edits in `vendor/`. Host items are PRs
> here.

## 1. Audit snapshot (grep evidence)

### Already modern — no action

| Area | Evidence |
|------|----------|
| React 19 / shadcn 4.x conventions | 0× `forwardRef`, 0× `asChild`, 0× `React.FC`, 0× `defaultProps`, 0× `import React from "react"` (platform ui + shop-module + host) |
| Toast | 0× `sonner` anywhere (Base UI `toast.add()` everywhere) |
| Styling | 0× `clsx`/`tailwind-merge` imports (official `cn` pkg), 0× `ring-3`, 0× `process.env` in frontend, 0 PostCSS/tailwind.config leftovers |
| Hardcoded colors in platform + host | 0× `text-green-500`-style (status tokens used) |
| PHP syntax | 0× old `array(` syntax, 0× `list(`, 0× `dd(`/`dump(`, 0 legacy patterns in host `app/` |
| PHP 8.4 features in shop-module | 23× readonly promoted props, 82× first-class callables |
| Migrations | 82 migrations, all `foreignId()`/`id()` style — 0 legacy `unsignedInteger` FK patterns |
| Tooling files | `postcss.config.*` / `tailwind.config.js` correctly absent; ESLint flat config present |

### Gaps found (actions below)

| # | Gap | Where | Count |
|---|-----|-------|-------|
| G1 | Hardcoded status colors (`text-red-500`-style) instead of `status-*` tokens | **shop-module** | 23 |
| G2 | `any` types eroding TS strictness | **shop-module** 26 (AttributeSelector.tsx ×12, analytics.ts ×9), **platform** 7 | 33 |
| G3 | String `const STATUS_*` groups instead of backed enums | **shop-module** models | 12 const groups |
| G4 | ESLint doesn't *enforce* the modern-API rules (no bans on legacy patterns) | host (and package CI copies) | — |
| G5 | No PHPStan/Larastan static analysis in host or shop-module composer | host + shop-module | — |
| G6 | Stale stack claims in docs (tailwindcss-animate, Radix UI, old versions) | laravel.shop AGENTS.md, docs/integration/* | 2+ rows |

Note: the 6 `isset(...) ? ... :` hits in shop-module are *correct* usage (Stripe SDK
dynamic objects, cast-after-null-check), not legacy — do not mechanically rewrite to `??`.

## 2. Recommended actions

### Phase A — Standards enforcement (host, ~half a day) — P1

- [x] **A1. Ban legacy APIs in ESLint** (host `eslint.config.js`, then mirror into
      shop-module's strict-gate config when convenient):
      `no-restricted-imports` → `@radix-ui/*`, `radix-ui`, `sonner`, `clsx`,
      `tailwind-merge`, `tailwindcss-animate`;
      `no-restricted-syntax` → `forwardRef` calls, `React.FC`/`FunctionComponent`,
      `defaultProps`, `var`; `no-restricted-globals` → `process` (frontend,
      playwright/vite configs exempt).
      The codebase is already compliant (0 hits) — this codifies it so regressions fail CI.
      **Done 2026-11-17.**
- [x] **A2. Adopt PHPStan (Larastan) at a modest level** — **Done 2026-11-17.**
      `larastan/larastan v3.15.0`, level 5, `composer analyze` script,
      `phpstan.neon` + `phpstan-baseline.neon` (1 baseline entry, see B4).
      Key discovery: **`databaseMigrationsPath`** — Larastan infers model columns
      from migrations, so the config must point at the package migrations
      (`vendor/nreljed/*/database/migrations`) too, or every package-model
      property access is `property.notFound` (this took the count 106 → 40).
      Migrations themselves stay out of `paths` (one shop-module migration
      chains `->after()` on a `ForeignKeyDefinition` — legacy smell to fix in
      the package, not worth analyzing here).
      Genuine host bugs it caught and fixed: `$item->price` → `unit_price` in
      GenerateDemoData (silent 0.0 refunds in demo data), string-typed
      `number_format` arg, dead nullsafe/ternary patterns, speculative
      `?->value` on plain-string statuses, dead `@extends` tag on UserFactory.
      Remaining host-side package-typing gaps handled with targeted
      `@var` docblocks (Shipment, ProductVariation, OrderItem, User).
- [x] **A3. Pint preset check** — **Done 2026-11-17.**
      Platform already ran `vendor/bin/pint --test` in its CI. Shop-module now does
      too: added `laravel/pint: ^1.32` to `require-dev` and a `Laravel Pint (style
      gate)` step (`vendor/bin/pint --test`) to `.github/workflows/ci.yml`. Ran
      Pint over the entire shop-module tree (src, config, database, scripts, tests)
      — ~120 files fixed; `vendor/bin/pint --test` now passes. Default preset
      (PSR-12 + Laravel) only, no custom legacy rules.
      - shop-module #77 (v0.1.115): pint dep + CI step + full source cleanup.
      - Host fan-out #282 (v0.19.21 pending deploy): shop-module 0.1.115.

### Phase B — shop-module package PRs (~1–2 days) — P1/P2

- [x] **B1. Semantic status colors → `status-*` tokens** — **Done 2026-11-17.**
      The platform kit ships `status-success/error/info/warning` + `status-badge.tsx`;
      all status-semantic surfaces across the three packages now consume the tokens
      (manual `dark:` pairs deleted — the CSS variables adapt in dark mode):
      - shop-module #70 (v0.1.109): ProductSeoPanel severity map + over-length
        counters, VariationsManager stock badges/info box/sale-price/chips,
        StarRating fill (`warning`), WishlistButton heart (`accent-rose`),
        Dashboard KPI deltas + low-stock card.
      - platform #67 (v1.0.9): SeoScore gauge (text+stroke), SerpPreview
        over-limit counter, NotificationItem `account.deleted`, system-health
        SeverityIcon.
      - blog #15 (v0.1.13): SeoPanel severityColor map.
      - shop-module #71 (v0.1.110): Wishlist/Index page-heading heart
        (`accent-rose`) — straggler found when the host bundle still emitted
        `text-red-500`.
      - Host fan-out: laravel.shop #263 (shop-module 0.1.109) + #266 (platform
        1.0.9, blog 0.1.13, shop-module 0.1.110) → production v0.19.15;
        live CSS verified (`stroke-red-500` gone, token rules emitted).
      **Audit correction:** the original "23 occurrences" came from a
      PowerShell glob (`Select-String -Path dir\**\*.tsx`) that silently misses
      nested files — a recursive sweep found far more (see B1b).
- [x] **B1b. Full palette-color sweep** — **Done 2026-11-17.**
      All three packages now have **0 hardcoded palette colors** in their frontend
      sources (recursive sweep), and the host bundle emits 0 palette color class
      rules (verified on the live production CSS, app-CBW0E055.css).
      - shop-module #72 (v0.1.111): 44 replacements; `src/Resources/js` 0 palette.
      - platform #68 (v1.0.10): 475 replacements across 41 files; `resources/js`
        0 palette (charts/sparklines untouched — no palette there).
      - platform #69 (v1.0.11): starter theme preview banner.
      - blog #16 (v0.1.14): 553 replacements across src; `src/Resources/js` 0
        palette.
      - Host fan-out #272 (v0.19.18): platform 1.0.11, blog 0.1.14,
        shop-module 0.1.111; host-local `Blog/Public/Show.tsx` preview banner
        converted to `warning` tokens (was the last host-side palette usage).
      Password-strength meters keep 4 distinct hues (destructive -> warning ->
      accent-cyan -> status-success); notification icons, status borders, info
      boxes, badges and form errors all use semantic tokens now. Manual `dark:`
      pairs retained but reference the same token (tokens adapt via CSS vars).
- [x] **B2. Introduce backed enums for status fields** (G3) — **Done 2026-11-18.**
      All status/type fields now have backed enums:
      - Returns: `ReturnStatus` (#74, v0.1.112)
      - Const groups: `AddressType`, `ProductRelationType`, `LoyaltyTransactionType`,
        `ProductFieldType` (#75, v0.1.113)
      - Order/Payment: `OrderStatus`, `PaymentStatus` (#78, v0.1.116) — OrderStatusService,
        UpdateOrderStatusRequest, controllers, resources, host code updated.
      - Dead `Statuses/Types` state machine removed (#79, v0.1.117) — was unused.
      Remaining: none (subscription status family did not exist as a separate system).
- [x] **B3. Type the `any`s** (G2) — **Done 2026-11-17.**
      `src/Resources/js` now has **0 type `any`** (the only remaining `any` word is in
      a test description string, not a type). Strict-gate typecheck passes.
      - shop-module #76 (v0.1.114): analytics.ts (typed env + Window augmentation),
        AttributeSelector.tsx (12 casts), QuickView, VariationsManager (Promise<unknown>
        + typed record), WishlistButton, Settings ToggleRow, Compare, Returns
        (STATUS_COLORS variant union).
      - Host fan-out #279 (v0.19.20 pending deploy): shop-module 0.1.114.
- [x] **B4. Package model annotations for static analysis** — **Done 2026-11-17.**
      shop-module #69 (v0.1.108) added Larastan 3.x `@return` generics
      (`HasMany<X, $this>` / `BelongsTo<X, $this>` / `BelongsToMany<X, $this>`)
      to the relations consumers touch most: `Order` (user, shippingMethod,
      cart, items, processor, actions, refunds, notes, shipments), `Product`
      (attributeTerms, variations, priceRows), `ProductReview` (product, user,
      approver). Host follow-up laravel.shop #260: deleted
      `phpstan-baseline.neon` (the `HasMany::active()` entry resolves through
      the typed relation + `scopeActive`), removed all four temporary `@var`
      casts, bumped the lock to 0.1.108. PHPStan now runs at level 5 with
      **0 errors and no baseline**. Platform `User` needed no annotations (the
      host's `instanceof PlatformUser` guard covers it). Remaining untyped
      relations across the package surface can be annotated opportunistically
      when consumers trip over them — the shared `consumer-ci.yml` PHPStan step
      (platform #66) is `hashFiles('phpstan.neon') != ''`-conditional, so each
      consumer opts in by shipping its own `phpstan.neon`.

### Phase C — Docs & standards alignment (host, quick) — P2

- [x] **C1. Fix stale stack claims** in `AGENTS.md` (line 36–37): `tailwindcss-animate` →
      `tw-animate-css` (v4-native), `Radix UI` → `Base UI` (`@base-ui/react` 1.9, never
      Radix). Done 2026-11-17 (this PR).
- [x] **C2. Refresh stale version tables** in `docs/integration/shadcn-ui.md` (done
      2026-11-17 in #256) and record the shadcn 4.x audit results (done in #256).
- [x] **C3. Add a "docs age check" habit**: when a package release bumps a dependency's
      minor/major, the same PR must touch the owning package's `docs/integration/*` table —
      prevents the 2-version staleness this audit found twice in one week.
      **Done 2026-11-18.** `scripts/check-docs-versions.mjs` parses the version tables
      in `docs/integration/*.md` and fails if any claim ≠ the installed version in
      `package-lock.json`. Wired into `ci.yml` as a `docs-age-check` job; the habit is
      documented in AGENTS.md. Stale claims fixed in the same PR (react 19.2.7→19.3.1,
      react-dom, @inertiajs/react 3.4.0→3.9.1, @tailwindcss/vite 4.3.x→4.3.3).

### Phase D — Deferred / not legacy (context) — P3

- [ ] **D1. PHP property hooks (8.4)** and `#[\Override]`: not yet needed by any code
      path; adopt opportunistically in new code rather than as a sweep.
- [ ] **D2.isset-ternaries**: leave as-is (correct semantics for Stripe SDK objects) —
      recorded here so a future sweep doesn't "modernize" them into bugs.
- [ ] **D3. `data-state=` hits**: audited — all five are legitimate (TanStack table's own
      API, sidebar's internal convention, attachment passthrough). Not Base UI violations.

## 3. Execution order

1. A1 (ESLint bans) + C1/C2 (docs) - host PR, zero-risk, immediate standard codification. (done)
2. A2 (PHPStan) - host + shop-module PRs, level 5 first. (done)
3. A3 (Pint) - shop-module #77 (v0.1.115): pint dep + CI step + full source cleanup. (done)
4. B1 + B1b (status + palette tokens) - done.
5. B2 (enums) - returns (#74) + const groups (#75) + order/payment (#78) + dead state machine removal (#79) done. All B2 complete.
6. B3 (any-typing) - done (#76).

## 4. Success metrics

- 0 hardcoded status colors in all trees (grep-verified)
- 0 `any` in shop-module/platform `Resources/js`
- `composer analyze` (PHPStan) green in all three repos' CI
- ESLint bans green with zero suppression comments
- `AGENTS.md` / integration docs versions match installed dependency versions on every release
