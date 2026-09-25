# Provision demo accounts on Render

Pushing source code does not insert rows into the hosted database. After deploying this commit, configure these private variables in Render and deploy/restart the service:

| Variable | Value |
| --- | --- |
| `SEED_DEMO_USERS` | `true` for provisioning |
| `DEMO_USERS_PASSWORD` | A new private password, 12–72 ASCII characters; do not reuse SMTP or database credentials |
| `DEMO_SUPERADMIN_EMAIL` | An email address you control |
| `DEMO_ADMIN_EMAIL` | Another email address you control |
| `DEMO_MANAGER_EMAIL` | Another email address you control |
| `DEMO_CASHIER_EMAIL` | Another distinct email address |

For a Gmail mailbox you control, you can use `yourname+kita-superadmin@gmail.com`, `yourname+kita-admin@gmail.com`, `yourname+kita-manager@gmail.com`, and `yourname+kita-cashier@gmail.com`. Replace `yourname` with your actual mailbox name. All four aliases receive mail in that inbox. Other providers must support plus addressing before you use aliases.

Log in at your Render site with the corresponding email and the password you chose. These are real active accounts with the normal role permissions. Super Admin, Admin and Manager still require emailed OTPs; Cashier uses password login. Configure working production mail delivery before testing the privileged roles. Manager approval PINs are configured through the existing manager account workflow; no shared PIN is seeded.

The database must already have the application's reviewed migrations applied, including `users` and `account_sequences`. No migrations are implicitly enabled by this feature. You can instead run `php artisan accounts:seed-demo` once from the Render shell after setting the email/password variables (config caching must reflect the current environment).

After the logs show `Created 4 demo accounts`, set `SEED_DEMO_USERS=false`, remove the five `DEMO_*` credential/address variables and redeploy. Accounts remain in MySQL. You can then set individual passwords using account management. Repeating the command skips existing accounts of the same role without changing their passwords/status. An email assigned to a different role aborts and rolls back the new accounts.

No sample credentials are committed, and this feature does not add stock, transactions, suppliers or other business records. Deactivate demo accounts when finished testing.
