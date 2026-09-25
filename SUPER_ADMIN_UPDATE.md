Super Admin implementation report

Implemented in the existing application without replacing its Laravel authentication, account model, or operational controllers.

Files added:

- `app/Http/Controllers/SuperAdminDashboardController.php`: restricted dashboard endpoint with database measurements, account counts, daily audit totals, and recent activity.
- `app/Http/Middleware/EnsureActiveAccount.php`: web-wide inactive-account session enforcement.
- `tests/Feature/SuperAdminTest.php`: authentication, lifecycle, dashboard, permissions, and failure-state coverage.

Files modified:

- `app/Models/User.php`: shared exact deactivation message.
- `app/Http/Controllers/OtpLoginController.php`: inactive credential validation, pending OTP identity checks, inactive OTP completion prevention, used-code rejection, attempt limits, and Super Admin dashboard destination.
- `app/Http/Controllers/AccountController.php`: retained OTP login timestamps, canonical status responses, and serialized Super Admin changes with case-normalized active-account counting.
- `app/Http/Middleware/EnsureRole.php`: consistent inactive-session handling through the new middleware.
- `bootstrap/app.php`: registers the account-status middleware in the web stack.
- `routes/web.php`: adds GET `/api/super-admin/dashboard` under role middleware.
- `resources/views/kita.blade.php`: passes the flashed deactivation error to the login interface.
- `public/app.js`: dashboard, user filters, status badges, created/OTP login dates, confirmed account status changes, revoked-session handling, Security Settings removal, and loading/empty/error states.
- `public/styles.css`: responsive blue/white dashboard, activity bars, status badges, filters, and error/loading styles.
- `tests/Feature/OtpLoginTest.php`: updates the expected Super Admin landing screen to the implemented dashboard.
- `tests/frontend-workflows.cjs`: adds Super Admin navigation, filters, status actions, dashboard states, and session revocation checks.

Database changes: none. Existing `users.status`, `users.created_at`, `login_otps.used_at`, and `audit_logs` are reused. No migration, data import, account deletion, or production data rewrite is required.

Dashboard metrics:

- Total, active, and inactive account counts queried from `users`.
- Average audit events per day: audit records dated within the previous seven completed calendar days, divided by seven. Days without events count as zero. The calculation uses the application calendar and is explicitly labeled as recorded audit activity, not logins or unique active users.
- A daily activity bar chart and ten most recent audit events.
- Application operational status for a successful dashboard request and database connected status following a successful query.
- Database round-trip time measured with a monotonic timer around `SELECT 1`.
- Dashboard calculation time measured within the controller, excluding browser/network latency and response serialization.
- Failed dashboard queries return HTTP 503 and an unavailable message, without fabricated timing or count values.

Inactive account enforcement:

- Incorrect credentials retain the generic credential error.
- Correct credentials for an inactive account return exactly: "Your account has been deactivated. Please contact your operator."
- Such accounts do not receive an OTP or authenticated session. Account status is checked again before OTP login completion.
- Authenticated web requests check account status. Inactive users are logged out, the session is invalidated, and the CSRF token is regenerated. HTML requests redirect to `/` with the exact message; API requests return HTTP 401 with a deactivation flag and new CSRF token. The frontend clears the authenticated interface and shows the login error.
- Revocation takes effect on the next server request; an idle open tab is not remotely closed.

Account lifecycle and authorization:

- Search, role/status filtering, uppercase status badges, creation dates, and retained OTP login dates are displayed.
- Both direct status actions and status changes through Edit require confirmation.
- Existing backend status validation and role restrictions remain enforced.
- Self-deactivation remains forbidden. Super Admin mutations are serialized using the existing account sequence lock, and the last-active-Super-Admin check uses normalized status/role values.
- Existing restrictions against deactivating an account with unfinished transactions remain enforced.
- No account deletion endpoint existed in the audited backend. None was added. DELETE requests to account URLs return HTTP 405, including Super Admin targets. The frontend has no user deletion action; an obsolete delete-confirmation state field was removed.
- The dashboard endpoint independently verifies the Super Admin role in addition to route middleware.

Security Settings removal: removed its navigation entry, screen renderer, title/render mappings, and permissions-matrix entry. There was no dedicated backend Security Settings route to remove. OTP, manager PIN, password handling, session security, and authorization remain in place.

Verification:

- `php artisan test`: 49 tests passed, 381 assertions. Includes existing checkout, payment, refund, inventory, account, and manager approval coverage.
- `node --check public/app.js`: passed.
- `node tests/frontend-workflows.cjs`: existing and new workflow checks passed.
- `php artisan view:cache`: Blade compilation passed.
- Route inspection confirmed the new dashboard endpoint; source search found no remaining Security Settings or user-delete controls in `public/app.js`.

Limits:

- Backend tests use an isolated SQLite database. Production database concurrency and external payment/mail services were not exercised live.
- Frontend checks render component trees and exercise handlers; no interactive browser visual review was available in this session.
- Last OTP login is based on retained records matching the current account email. Cashier password-only logins are not recorded, and email changes or OTP record cleanup can leave the value unavailable.
- No complete login history or reliably mapped live-session count is presented. No CPU, RAM, uptime, or availability-percentage values are invented.
- Existing Roles & Permissions and Backup Configuration screens were outside this requested change and retain their prior behavior; this work does not turn their frontend controls into new backend configuration capabilities.
