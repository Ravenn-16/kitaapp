# Responsive UI

The existing Blade/DC/React interface uses `public/styles.css` and the shared helpers in `public/app.js`. No frontend framework, backend route, schema, or business-rule changes were introduced.

## Layout conventions

- Desktop navigation remains visible at 1024px and above. Below 1024px it becomes a drawer with a backdrop, close control, Escape handling, focus trapping, and focus restoration. Background controls are inert while the drawer is open.
- Mobile layouts apply below 768px. Cards and forms stack, controls have larger touch targets, and inputs use 16px text. The shared grid adapts at intermediate widths; desktop content has a maximum width of 1800px.
- Shared cards, grids, flex rows, fields, and headings allow their contents to shrink and wrap. Fractional grid tracks use `minmax(0, ...)`.
- Tables retain their columns inside focusable horizontal scroll regions. Column widths protect legibility rather than shrinking everything to the phone width.
- Dialog margins and maximum heights share `--dialog-gutter`. Short landscape viewports use smaller margins. Long dialog contents scroll; picker lists retain usable height.
- Screen-only breakpoints do not affect print. The purchase report has scroll regions for the order, delivery, and inspection tables, with print overrides that expose all table contents and repeat headers.

## Verification

Verified in installed Chrome using Playwright at:

| Width | Height |
| --- | --- |
| 320 | 740 |
| 375 | 812 |
| 425 | 900 |
| 768 | 1024 |
| 1024 | 768 |
| 1366 | 768 |
| 1440 | 900 |
| 1920 | 1080 |
| 812 | 375 |

Results: **585 layout checks passed**, with no detected page/component horizontal overflow, out-of-viewport dialogs, empty screens, or JavaScript runtime errors in the tested states.

Coverage includes all main cashier, manager, admin, and super-admin screens; inventory tabs; populated purchase requests, reviews, receiving and history; checkout and receipts; refund forms; account, category, supplier, product-picker, discount, damage and price dialogs; promotions; notifications; payment return pages; and the real purchase-report Blade template.

Additional checks cover password Enter submission (one request), drawer focus trapping, Escape and focus restoration, print navigation hiding, and printable table overflow. Representative desktop/mobile screenshots were reviewed. Purchase history and purchase-report PDFs were generated.

The existing Laravel suite passed: **66 tests, 620 assertions**. JavaScript syntax and Blade compilation also passed.

These checks use synthetic records and intercept API writes. They do not create real users, sign into real accounts, or alter inventory. Browser verification was in Chrome; physical-device and Safari/Firefox testing were not performed.

## Rerunning

From the project root, with PHP, Python, and Chrome installed:

```powershell
python -m pip install --target .tmp/responsive-tools playwright
php artisan serve --host=127.0.0.1 --port=8765
```

In another terminal:

```powershell
python tests/browser/responsive.py
php artisan test
```

Set `KITA_TEST_URL` to use another local server address. The browser runner renders a synthetic report using `tests/browser/render-report.php`. Results, screenshots, PDFs, and temporary browser dependencies stay under the ignored `.tmp` directory. The app's existing React and font CDN dependencies require network access for browser checks.
