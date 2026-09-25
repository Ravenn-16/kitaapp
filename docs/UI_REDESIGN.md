# KITA interface redesign

## Audit before implementation

The active interface is `resources/views/kita.blade.php`, `public/styles.css`,
`public/app.js`, and the generated `public/support.js` renderer. The resource
CSS/JS entrypoints are Vite scaffolding and are not included by the active KITA
view. No additional frontend framework or dependency was introduced.

Blade sections: password login, OTP verification, authenticated shell, sidebar,
header, manager notifications, dynamic active screen, and toast notifications.
Payment success/cancelled are separate Blade pages with identical status-check
and cancellation behavior.

Existing modules by role:
- Cashier: checkout, refunds/exchanges/voids, shift transactions.
- Manager: dashboard and approval PIN, checkout, purchase requests and request
  detail/history, stock receiving and PO detail, inventory adjustments,
  write-offs, recall, reconciliation, expiry monitoring, inventory table,
  categories, barcode/item registration, archive, accounts, promotions, suppliers.
- Admin: dashboard/analytics, purchase orders, forwarded/disapproved requests,
  receiving/inventory approvals, supplier records, reports, archive, accounts,
  notifications.
- Super admin: accounts, roles/permissions, security, backups, audit logs and
  field history.

Existing dialogs: product lookup, senior/PWD, price override, request item
picker, price edit, damage item picker, category create/edit, promotion item
picker, supplier product picker, supplier create/edit, archive deletion
confirmation, and account creation/editing. Existing dialog contents and
handlers were retained.

## Integration contracts

- Renderer selectors: `x-dc`, `script[data-dc-script]`, generated `data-dc-tpl`
  attributes and custom `sc-if`/`sc-for` template tags. `support.js` is unchanged.
- Authentication selectors: `.auth-card--login input[type="email"]` and
  `.auth-card--login input[type="password"]`; CSRF meta selector is unchanged.
- Existing IDs: `manual-category`, `reg-product-options`, `payment-status`,
  `resume-payment`, `cancel-payment`. Receipt classes remain in use.
- Forms are controlled fields and JavaScript handlers rather than HTML form
  actions. Existing value/change/click/keyboard bindings remain in place.
- Authentication requests: `/login`, `/otp/verify`, `/logout`; existing Laravel
  OTP request route remains available. CSRF refresh and credentials unchanged.
- Data/account requests: `/api/kita-data`, `/api/accounts` and account update paths.
- Checkout/returns: `/api/transactions` and transaction UUID paths.
- Wallet payments: `/api/payments/paymongo/checkout` and UUID cancellation paths;
  payment pages continue fetching transaction status. Webhooks unchanged.
- Manager PIN: `/manager/approval-pin`.
- Catalog: `/api/products`, `/api/categories` and existing update paths.
- Purchasing: `/api/purchase-requests`, approval/update paths and
  `/api/purchase-orders/{id}/items`.
- Inventory: `/api/inventory/adjustments` and approval paths.

No controllers, services, routes, migrations or database records were changed
for this redesign. Discounts, approvals, prices, unit costs, stock movements,
transaction persistence, OTP and payment business logic remain as before.

## Presentation changes

- Original supplied JPEG copied byte-for-byte to `public/images/kita-logo.jpg`.
  Blade uses Laravel's asset helper; the logo is not recreated or edited.
- Shared blue/white design tokens, readable headings, inputs, cards, buttons,
  badges, tables, notifications, and responsive modal containers.
- White sidebar with blue selected state, semantic navigation buttons, user/role
  header, and mobile drawer with overlay and Escape dismissal.
- Reused shared rendering helpers style every existing module; centralized
  presentation classes cover existing inline grid/flex/modal layouts.
- Wide-screen POS places cart and payment controls alongside each other.
  Narrow grids stack; tables scroll horizontally; dialog content scrolls.
- Branded password/OTP and payment pages, input labels and focus indicators,
  reduced-motion rules, toast severity styling, and receipt print rules.

## Verification

- `php artisan test --compact`: 40 passed, 319 assertions.
- `node tests/frontend-workflows.cjs`: passed, including added checks for
  selected navigation, mobile drawer selection and role-specific navigation.
- `node --check public/app.js`: passed.
- `php artisan view:cache`: Blade compilation passed.
- Source review: original selectors and payment IDs retained; template bindings
  checked against renderVals, with original API methods and payloads preserved.

## Remaining limitations

Browser runtime discovery returned no available browsers. Actual viewport,
console, keyboard and visual screenshot inspection still need a browser pass;
source checks and automated workflow tests do not replace that verification.

Pre-existing local/demo behavior remains in areas including supplier editing,
promotions, receiving approval, archives, permission toggles and backup actions.
The redesign does not implement persistence for those controls. Existing
external font and renderer CDN dependencies are unchanged.


## Second refinement pass

- Added shared SVG navigation icons and a section breadcrumb using existing
  role-specific navigation definitions.
- Added a controlled record-list component for inventory, categories, accounts,
  purchases, requests, transactions, suppliers, promotions, notifications,
  reports, expiry, archives, adjustments and audit/history lists. Search filters
  already-loaded records; page size and result counts are explicit. Existing
  row objects and callbacks remain intact. Cart and request-entry lines keep
  their full editable tables.
- Added consistent KPI cards and proportional best-seller bars driven by the
  existing sales log. Admin KPIs now use current catalog/sales data; the three
  pre-existing hard-coded percentages are retained in a clearly labeled
  reference section. Business-day sales uses the supplied BUSINESS_DATE.
- Organized registration into product details, stock/units, pricing and
  traceability, using the existing field state, handlers and validation. Added
  associated labels, required markers, help text and visible save progress.
- Category dialog now has a semantic title, labeled fields, inline alert and
  Escape dismissal. Mobile navigation supports keyboard focus wrapping.
- Catalog/account fetches expose loading feedback and correctly count concurrent
  requests while preserving responses and errors.
- Added regression coverage for pagination, search, empty results, dataset
  shrinkage, preservation of row action targets, and registration labels.
- Re-ran frontend workflow checks, JavaScript syntax and Blade compilation.
  Laravel suite remains 40 passed / 319 assertions.
- Browser discovery again returned an empty list; visual/console testing is
  still unverified. No RFID or forecasting implementation was found or added.
