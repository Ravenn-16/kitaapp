Purchase workflow implementation

The existing Laravel application now saves and connects purchase requests, Admin review, Manager stock receiving, inventory movements, transaction history, and printable receiving reports.

Using the workflow:

1. Manager → Purchase Request → Create Request. Select one supplier, add inventory items, and enter positive quantities in the displayed inventory units. Add Supplier, Add New Item, and Add Category are available in the same workflow. New items start with zero stock and are added to the request after registration.
2. Submit the request. Its unique PO ID is retained through approval, receiving, history, and reporting. The initial status is Pending Approval. The creating manager and timestamp are saved.
3. Admin → Purchase Requests. Review all lines, edit quantities or remove lines, then save edits, approve, or decline with a reason. Edits update the original request. Approved orders become Approved / Waiting for Delivery; approval does not add stock.
4. Manager → Stock Receiving. Open the approved PO after delivery, enter actual delivered quantities, supplier delivery reference, date, and remarks, then confirm. Leave undelivered lines blank. Submitted quantities must be positive whole numbers.
5. Each accepted delivery updates inventory, the PO's cumulative received quantities, receiving history, and stock movements atomically. Short deliveries remain Partially Received. Exact cumulative matches become Fully Received. Excess deliveries require remarks and remain explicitly flagged; they never become Fully Received while quantities differ.
6. Manager → Transaction History or Admin → Receiving History. Search and filter records, then select View / Print Report. The separate authenticated report page includes the KITA logo, PO ID, supplier, request/approval/receiving dates and names, quantities, discrepancies, delivery transactions, notes, totals, and signature lines. Print CSS hides controls and formats A4 pages.

Purchase listings refresh when opened and every 30 seconds while visible. Automatic refresh pauses during Admin edits and receiving entry to preserve the user's work. Manual Refresh is also available.

Data integrity:

- A request and its new approved PO share the same ID. Receiving records have separate receipt IDs for individual deliveries, each linked to that original PO ID. Stock movements link to the corresponding receipt.
- One supplier per PO is enforced on the backend. Several categories can be represented by individual item lines.
- Request IDs are unique. The database assigns sequential numeric-only PO IDs. The browser retains a separate UUID submission key after a network failure; a successful retry returns the original saved PO number without duplicating the request. Client-supplied PO IDs are rejected.
- Approval uses row locks and request revisions; already reviewed requests cannot be approved again. New approved PO quantities cannot be changed through the legacy add-items endpoint.
- Receiving uses row locks, an idempotency key, a unique supplier delivery reference per PO, and a receiving version. An identical successful retry returns the saved result without posting more stock. Reused keys with different payloads, duplicate delivery references, stale versions, and invalid quantities are rejected.
- Inactive actors, inactive products/categories, changed inventory units, unknown items, dates before approval, and future receipt dates are rejected where applicable.
- Inventory, receipt lines, PO totals/status, request status, audit records, and stock movements are committed in one database transaction. Validation failure rolls the transaction back.
- Item names, categories, units, supplier names, and actor identities are retained for reporting. Audit entries preserve the pre-edit lines and submitted review changes.
- Existing role and session middleware remain in place. Cashiers cannot access purchasing APIs or reports; Managers cannot approve requests; Admins review receiving history but cannot post Manager receipts. Existing Super Admin access remains supported.
- Existing account deactivation checks recognize Pending Approval and completed receiving statuses.

Database migration:

`database/migrations/2026_09_24_000001_complete_purchase_workflow.php` was applied successfully to the configured database (batch 8). It adds nullable timestamps, actor and item/supplier snapshots, revision/version fields, receiving idempotency/delivery-reference indexes, and a supplier ID sequence. It does not replace tables or rewrite existing IDs. Its rollback deliberately retains audit and idempotency data.

Existing imported orders keep their original request/PO relationships, even when those IDs differ. Legacy Draft POs can receive only when their linked request is already Approved. Missing historical names, units, or timestamps are displayed as not recorded rather than fabricated. Legacy POs with duplicate item lines require reconciliation before receiving.

Files added:

- `app/Services/PurchaseWorkflow.php`
- `app/Http/Controllers/PurchasingController.php`
- `database/migrations/2026_09_24_000001_complete_purchase_workflow.php`
- `resources/views/purchase-report.blade.php`
- `tests/Feature/PurchaseWorkflowTest.php`
- `tests/purchase-workflows.cjs`

Files updated:

- `app/Http/Controllers/OperationsController.php`: delegates request creation/review to the workflow service and preserves legacy draft-item handling.
- `app/Http/Controllers/AccountController.php`: recognizes the new purchase lifecycle statuses.
- `routes/web.php`: purchasing data, supplier creation, receiving, and report routes.
- `public/app.js`: database-backed purchasing screens, inline creation, filters, receiving, auto-refresh, history, and real report links; removes the simulated receiving approval actions.
- `public/styles.css`: purchasing forms, filters, actions, and responsive layout.
- `tests/Feature/ManagementWorkflowTest.php`: checks the required Pending Approval status, consistent IDs, immutable new approvals, and preserved legacy draft editing.

Verification results:

- `php artisan test`: 59 tests passed, 548 assertions.
- `node --check public/app.js`: passed.
- `node tests/purchase-workflows.cjs`: passed.
- `node tests/frontend-workflows.cjs`: passed, including existing Super Admin and operational frontend checks.
- `php artisan view:cache`: report and application Blade compilation passed.
- Migration status confirms the migration is applied.
- Read-only query against the configured database succeeded; it currently contains zero purchase requests and zero purchase orders. No sample orders or stock changes were inserted into that database.

Verification limits: end-to-end database tests use isolated SQLite data. Actual concurrent production database requests, interactive browser layout, and a physical printer were not tested. Receiving quantities intentionally use inventory units; purchase-package conversion is not silently applied. The receiving history is recorded in the existing receiving tables and stock ledger, separate from POS sales transactions.

Numeric PO update: `2026_09_24_000002_numeric_purchase_ids.php` adds a locked database sequence and request submission key/hash fields. New PO IDs contain digits only. Existing historical IDs remain unchanged so linked records and reports retain their original references. Supplier, product/category creation, requests, reviews, receiving, stock movements, history, and report reads use the existing database-backed endpoints.
