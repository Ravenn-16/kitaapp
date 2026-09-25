# KITA revision report

Verified on 2026-09-16. The user confirmed that the existing KITA project should be used as the reference. No Appendix was available, so visual conformity to an Appendix cannot be certified.

## Architecture and scope

The existing Laravel 12 / PHP application, MySQL database, Blade shell, and React/DCLogic frontend in `public/app.js` were retained. The imported business tables use Laravel's query builder. Existing numeric product IDs, named categories, composite user identity `(role, id)`, role dashboards, and visual patterns were preserved.

## Changes and requirement comparison

| Requirement | Previous issue | Implemented behavior |
| --- | --- | --- |
| Login | OTP flow and inconsistent identifiers | One email/password login, normalized unique email, password verification, inactive-account rejection, login throttling, session regeneration, existing role dashboards. Old OTP endpoints are unavailable. |
| Super Admin | Imported accounts and role aliases required careful handling | Existing accounts retained; normalized role authorization and first-account provisioning command. Self-deactivation and removal of the last active Super Admin are blocked. No default password was introduced. |
| Accounts | Local UI actions did not consistently persist or enforce rules | Server-backed creation/editing with role limits, unique email and hashed passwords. No account Delete control or deletion API. Ongoing transactions, requests, adjustments, and related open purchase orders block deactivation. |
| Manager items | Incomplete validation and ambiguous cost handling | Required name, active category, barcode, units, valid quantities and prices. Registration cost is calculated as unit price × initial quantity on both client and server. Direct edits cannot bypass stock-adjustment history. |
| Categories | No perishable classification | Existing category module supports Perishable / Non-Perishable, active-reference validation, and case-insensitive duplicate protection. |
| Purchase orders | Missing persistent Add Item and unreliable totals | Add active items to eligible purchase orders; combine repeated items with compatible costs; validate positive whole quantities and monetary precision; recompute line and order totals under a database lock. Purchase-request approvals persist edited quantities. |
| Stock IDs | Unsafe maximum-ID allocation and lookup assumptions | Existing product primary key protects Stock ID uniqueness. Optional numeric manual ID; automatic allocation uses a locked sequence. Separate unique barcode index and exact barcode lookup. Duplicate errors are readable. |
| Adjustments | Local stock changes and incomplete approval/history | Increase/decrease requests require valid quantity and reason, then Admin approval. Approval rechecks locked stock, prevents negative results or repeated approval, and records stock movements and audit history atomically. |
| Checkout | Client totals, duplicate requests, and stock consistency | Server-calculated prices/discounts/totals, stock locks, atomic sale and immutable line snapshots, stable request UUID, duplicate protection, cash tender validation, authoritative inventory updates. |
| Discount approval | Discount could bypass manager approval | Active manager PIN authorization is checked server-side, throttled and audited. Manager PINs are hashed. Approval is cleared in the UI when relevant cart/discount choices change. |
| Refund | Missing reliable purchased-quantity history and partial limits | Refunds reference immutable original lines. Remaining quantities and amounts are checked; duplicate request keys and provider references are protected. Partial refunds accumulate without overwriting original totals. Optional returned-stock restoration is audited. |
| Void | Repetition and invalid-status/restoration risks | Eligible same-day completed cash sales can be voided once with manager approval and reason. Partial/refunded/ineligible transactions are blocked; stock is restored atomically and original records remain. |
| Wallet payments | Client redirects could be mistaken for payment confirmation | Stock reservation is pending until a signed webhook and canonical provider lookup confirm payment. Repeated webhooks are idempotent. Cancellation releases stock only after provider-confirmed expiry. Wallet refunds require a matching successful provider refund reference. |
| Security and queries | Incomplete route protection and identity ambiguity | Server role enforcement, protected catalog, active-session checks, CSRF protection except the signed webhook, parameterized query-builder operations, composite actor identity, atomic writes, and database uniqueness protections. |

## Modified and added files

Paths below are relative to the project root. This workspace has no Git metadata, so this is an implementation inventory rather than a Git diff.

### Backend

- `app/Http/Controllers/AccountController.php` (new)
- `app/Http/Controllers/CategoryController.php` (new)
- `app/Http/Controllers/KitaDataController.php`
- `app/Http/Controllers/ManagerPinController.php`
- `app/Http/Controllers/OperationsController.php`
- `app/Http/Controllers/OtpLoginController.php` (filename retained; now password login)
- `app/Http/Controllers/PayMongoController.php`
- `app/Http/Controllers/ProductController.php`
- `app/Http/Controllers/TransactionController.php`
- `app/Http/Middleware/EnsureRole.php`
- `app/Models/User.php`
- `app/Services/DiscountApproval.php`
- `app/Services/InventoryRules.php` (new)
- `app/Services/ManagerApproval.php` (new)
- `app/Services/SaleCheckout.php` (new)
- `app/Services/StockMovement.php` (new)
- `bootstrap/app.php`
- `config/cache.php`
- `routes/web.php`
- `routes/console.php`

### Frontend

- `public/app.js`
- `resources/views/kita.blade.php`
- `resources/views/payment-success.blade.php`
- `resources/views/payment-cancelled.blade.php`

### Tests and documentation

- `tests/Feature/OtpLoginTest.php`
- `tests/Feature/CheckoutStockTest.php`
- `tests/Feature/ManagementWorkflowTest.php` (new)
- `tests/frontend-workflows.cjs` (new)
- `phpunit.xml`
- `docs/REVISION_REPORT.md` (new)

## Database changes

All migrations are applied to the local database. Existing records were retained.

| Migration | Change |
| --- | --- |
| `0001_01_01_000000_create_users_table.php` | Guard existing imported framework/account tables so migration works with both existing and fresh databases. |
| `2026_09_13_000001_add_manager_approvals.php` | Manager PIN and transaction approval support from the preceding approval work. |
| `2026_09_13_000002_hash_plain_manager_pins.php` | Hash legacy approval PINs; formatting normalized. |
| `2026_09_14_000001_create_discount_approvals_table.php` | Persist discount approvals. |
| `2026_09_14_090000_create_missing_legacy_tables.php` | Reproduce missing imported business tables for clean installations and tests; leaves existing tables untouched. Rollback deliberately preserves these business tables. |
| `2026_09_14_100001_secure_account_management.php` | Account status and compatible management fields, timestamps, normalized unique email, account ID sequence. |
| `2026_09_14_100002_protect_inventory_identifiers_and_unit_costs.php` | Unit price, original registration quantity, category classification and normalized unique name, product ID sequence, unique barcode, PO line totals, composite actor fields. |
| `2026_09_14_100003_add_sale_lines_and_refund_history.php` | Sale snapshots, ownership, totals and payment reference, partial-return lines and amounts, unique refund request/provider reference, stock movements. |
| `2026_09_15_000001_preserve_audit_identity.php` | Audit/name lengths compatible with accounts and role identity on approvals. |

Product Stock ID already had a primary-key uniqueness constraint; it was retained. Preflight found no conflicting existing emails, category names, or barcodes. Migrations do not silently delete duplicates to force an index.

## Validation and business rules

- Quantities are whole numbers because the existing stock schema uses integers. Item registration and purchase quantities must be positive. Adjustment direction determines the sign of a strictly positive entered quantity.
- Prices must be numeric, nonnegative and have at most two decimal places. Calculations use cents and respect the destination column's amount range.
- Product/category/supplier references must exist and be active where applicable. Stock IDs cannot change after creation.
- Account identity uses both role and ID because imported IDs repeat between roles. Historical actor-name references remain supported for ongoing-work checks.
- Manager manages Cashiers; Admin manages Cashiers and Managers; Super Admin manages all roles. Existing Super Admin authorization override is retained.
- Stock movement, transaction/refund details, and related status changes are committed together. Repeated checkout, adjustment approval, refund, and void requests cannot apply stock twice.
- Cash status `Unused` is retained as the project's completed-sale status. Wallet completion uses `Paid`; partial returns use `Partially Refunded`; void/exchange retains the existing `Closed` status with distinguishing audit records.

## Verification results

- `php artisan test`: **37 tests passed, 280 assertions**. Covers all roles at login; invalid/inactive login; account creation, uniqueness, no deletion and ongoing-work deactivation; valid/invalid items and IDs; category classification; PO quantities and totals; stock increases/decreases and approval rechecks; cash checkout and stock rollback; discount PIN authorization; partial/excessive/duplicate refunds; stock restoration; void restrictions; signed wallet confirmation, cancellation and provider refund validation.
- `tests/frontend-workflows.cjs`: passed using the available V8 execution environment with a filesystem shim. Exercises cost recomputation, amount validation, duplicate purchase items, discount rounding, wallet refund lookup, account controls, and revised screen construction. Node is unavailable locally; the same dependency-free script can be run with `node tests/frontend-workflows.cjs` where Node is installed.
- Laravel Pint: checked application, routes, tests, migrations, bootstrap, and cache configuration; formatting issues were corrected.
- `php artisan view:cache`: passed.
- `php artisan migrate:status`: all migrations marked Ran.
- Local MySQL catalog smoke check: successful response. Local HTTP smoke checks: homepage 200, unauthenticated catalog 401, invalid login with CSRF 422, login without CSRF 419.
- Local application is available at `http://127.0.0.1:8000` while the development server is running.

## Assumptions and manual confirmation

### Follow-up verification fixes

- Cash tender must be present, numeric, nonnegative, within the currency limit, and have no more than two decimal places. Invalid input is no longer replaced with the sale total by the frontend.
- Invalid item conversion factors no longer silently default to one. Refund fields reject negative, fractional, and excessive quantities before submission.
- Supplier and promotion unit-cost comparisons use `unitPrice`; registration `cost` remains the computed total. Item price edits compute registration cost using the original quantity, independently of later stock movements.
- Sale receipt lines now save the existing numeric product ID as their Stock ID.
- Exchange replacement validation now checks archived products, inactive/archived categories, and missing/invalid prices. Failed exchanges preserve original stock and transaction records.
- Pending payments have a Review payment action in My Shift, with resume/cancel options on the status page. Status API access remains owner-checked; completed payments do not return a resumable URL. Shift ownership uses stable role/ID where available, so changing an email does not hide owned transactions.
- Rapid wallet checkout clicks are guarded before the asynchronous state update. Logout clears the prior checkout UUID, receipt, tender, and discounts.
- Added regression coverage for these cases; frontend checks, PHP formatting, and compiled Blade views pass. This follow-up requires no new database migration.

### Items requiring manual confirmation

1. **Appendix:** the existing project is the accepted reference; no separate Appendix or asterisk-marked requirements were available. Branding remains KITA.
2. **Historical cost:** imported `cost` values were treated as per-unit purchase costs when initializing `unitPrice`. New registrations store both unit price and computed initial cost. Unknown historical registration quantities remain null; old totals are preserved during edits. Confirm imported cost semantics before relying on historical cost reports.
3. **Classification:** existing categories remain unclassified until assigned Perishable or Non-Perishable; no classification was guessed from names.
4. **Historical sales:** imported transactions without trustworthy receipt lines cannot safely be refunded or voided automatically. They require reconciliation; the implementation does not invent purchased quantities.
5. **Returns:** manager approval is required. Void retains same-day cash restrictions using the configured application timezone. The existing single-item exchange flow is supported for a whole-sale, equal-value replacement; other exchanges use refund and a new sale.
6. **Payments:** tests use mocked PayMongo responses; no live charge or refund was performed. Configure and verify the test-mode webhook and perform an end-to-end provider test. Wallet refund initiation occurs in the provider dashboard; the application verifies the successful refund reference before updating local records. Ambiguous provider timeouts preserve the reservation for reconciliation.
7. **UI:** screen-construction checks passed, but a real-browser visual and click-through review was not available. Existing unrelated demonstration features were not rebuilt.
8. **Super Admin:** existing credentials were retained. For a new installation with no active Super Admin, run `php artisan accounts:superadmin admin@example.com --name="Super Admin"`; the command prompts securely for a password and refuses to create a bootstrap account when one already exists.

Provider behavior was checked against the official [webhook guide](https://docs.paymongo.com/docs/developer-tools-webhook-setup-management), [checkout expiry reference](https://docs.paymongo.com/reference/expire-a-checkout-session), and [refund retrieval reference](https://docs.paymongo.com/reference/retrieve-a-refund).
