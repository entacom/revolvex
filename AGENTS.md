# RevolveX Working Instructions

## Project Location
- Work in `G:\Hosted Sites\revolvex`.
- This folder is now the local Git working copy for the RevolveX app.
- The GitHub remote is `https://github.com/entacom/revolvex.git` on branch `main`.
- The production cPanel clone lives at `/home/revolvexcom/revolvex`.
- The production public web root is `/home/revolvexcom/public_html`.
- Dreamweaver/FTP history matters, but the current preferred deployment path is GitHub plus the in-app `Git Update` page.

## Deployment Safety
- Commit and push completed changes to GitHub after local checks.
- For normal production updates, use the app menu item `Git Update`, which downloads the latest GitHub ZIP and deploys the `app/` folder to `/home/revolvexcom/public_html`.
- cPanel Git deploy is only needed to bootstrap/fix the Git Update page itself if the live copy cannot self-update.
- The cPanel account has `exec` and `shell_exec` disabled, so do not rely on PHP running `git`, `rsync`, or shell commands in production.
- The in-app deploy requires cURL or `allow_url_fopen`, `ZipArchive`, a writable temp folder, and writable `/home/revolvexcom/public_html`.
- Be careful with any direct Dreamweaver save/upload because it can bypass Git history.
- For high-risk edits or large refactors, create timestamped local backup copies before editing.
- Do not create reports, scratch files, or generated artifacts inside the FTP mirror unless explicitly requested.

## Server Layout Notes
- `web_config_ft.php` is a private/reference config file and should not be deployed into `public_html`.
- The user expects the private config to live outside the public web root, under the server home area.
- The current code references `/home/revolvexcom/web_config_ft.php`; verify server path before changing config includes.

## Current Review Mode
- Default to fixing requested issues directly when the user asks for implementation, while keeping changes scoped and production-safe.
- For broad security review requests, report issues clearly and prioritize them before large patches.
- Do not repeat secret values in reports or chat.
- Prioritize security findings by Critical, High, Medium, and Low.
- Keep this file updated with the active priority plan and confirmed findings.
- Use `PRD.md` for the product-level roadmap, priorities, acceptance criteria, and completed-work summary.

## Security Review Focus
- Exposed secrets, config files, logs, backups, Dreamweaver `_notes`, diagnostic pages, certificates, and private keys.
- Authentication, session handling, token login, logout, CSRF, and role/company authorization.
- SQL injection risks, especially dynamic table/field/column names.
- File upload, file deletion, S3 upload paths, MIME/extension validation, and direct endpoint access.
- Public dependency exposure under `app/assets/vendor`.

## Known Initial Findings
- `app/info.php` exposes `phpinfo()`.
- `app/api/json_data.php` accepts table names from query string.
- `app/api/crud.php` has upload/delete handlers triggered by request parameters.
- `app/index.php` supports token login through a URL parameter.
- `app/assets/vendor` contains public PHP libraries and sample/vendor files.
- Many files enable `display_errors` in production-facing code.

## Priority Work Queue
0. Current feature work:
   - GitHub/cPanel deployment is now active:
     - Local repo: `G:\Hosted Sites\revolvex`.
     - GitHub repo: `entacom/revolvex`.
     - cPanel clone: `/home/revolvexcom/revolvex`.
     - Public deploy target: `/home/revolvexcom/public_html`.
     - `.cpanel.yml` copies `app/` to public_html for cPanel deploys.
     - In-app `Git Update` page now deploys via GitHub ZIP download and PHP file copy because shell command execution is disabled.
     - `.gitignore` excludes secrets, generated files, logs, backups, cert/key files, Dreamweaver notes, and editor junk.
   - Automatic order activity entries added for status changes, delivery date changes, invoice processing, and customer/details updates.
   - Activity descriptions are grouped by category: status, delivery date, created date, notes, invoice processing, and customer/details.
   - Admin dashboard Recent Activity now surfaces order/customer context from `tblOrderActivity` instead of plain descriptions only.
   - Order activity `user_id` should always be the logged-in session user who made the change.
   - Admin order email sends now record activity for quotes, order confirmations, and invoices.
   - Browser tab titles should reflect the active page, including order and purchase IDs where available.
   - Invoice Performance dashboard should use cached monthly SQL summaries for closed months and calculate only the current month live.
   - Admin dashboard Recent Orders now shows the last 3 orders.
   - Admin dashboard Recent Activity now shows only the latest 5 order activities.
   - Purchase activity should use `tblPurchaseActivity` and record the logged-in session user for purchase edits, receiving, invoice/bill conversion, stock receive/reversal, and accounting sends.
   - Stock Report now shows `tblInventory.item_finished` as a Finished tick, filters All / Finished / Not Finished, filters by inventory group, and filters open/closed coils from `tblInventoryItems.coil_finished`.
   - Admin Orders List has been restyled with a stronger header/filter area, row-card table treatment, status pills, and secondary order details.
   - Admin Purchasing List has been restyled to match the Orders List and now supports sortable PO number, order date, and required date columns.
   - Copy Tagged Items To PO now writes the generated purchase order id into `tblOrderItems.purchased_item`, clears the tag, shows a PO badge on the order item row, and records order/purchase activity.
   - Admin Production now excludes order sub-items whose parent `tblOrderItems.purchased_item` is set, including production CSV export and match-item selection lists.
   - Admin Production now has a per-row Purchased checkbox that sets `tblOrderItems.purchased_item = 1`, records order activity, and removes the item from the production queue.
   - Order item completion is being tracked through `tblOrderItems.item_completed`, with manual item checkboxes, order/list completion tallies, due-date red warning styling, and linked PO receive/reverse receive updates.
   - Global admin header now includes a page-aware Help button and large modal explaining the current page and workflow.
   - Admin order header actions now use separate Print and Email dropdown buttons.
   - Admin order header now includes a Process Order button in the action area. The modal groups upload original, email/print order confirmation, print production cards, print labels, print delivery docket, and process-to-production actions.
   - Quote workflow now has its own Process Quote modal with payment-required/payment-received tracking, quote print/email activity, and convert-to-order handling.
   - Process Order modal actions now record typed order workflow activity and show the latest action/date/user under each modal group.
   - Purchase orders now have a Process Purchase modal with delivery docket print, order-confirmation request tracking, purchase order print/email activity, and transient email attachments.
   - Purchase confirmation workflow now supports required/received tracking, confirmation file upload, estimated arrival date, 48-hour missing-confirmation flagging, and linked PO colour badges on order items.
   - Order Activity tab is now a compact grid and activity deletion is permission-backed with server-side company ownership checks.
   - Admin Reports main page has been restyled with a stronger shell, report tabs, consistent inputs/buttons, and cleaner report/table panel styling.
   - Admin Inventory page has been restyled with a stronger header/filter shell, card-style inventory rows, stock status pills, cleaner actions, refreshed modal chrome, and quieter inventory JavaScript.
   - Security quick wins applied:
     - `app/api/json_data.php` is disabled with HTTP 410 and should also be blocked by `.htaccess`.
     - `app/includes/test_autoload.php` and `app/pdf/first_test.php` are disabled with HTTP 404.
     - Public Dreamweaver `_notes` folders were removed from `app/`.
     - Root and app `.htaccess` now deny `_notes`, config, logs, SQL dumps, cert/key files, backup/orig files, zip files, and the disabled diagnostic endpoints.
     - `app/index.php` no longer exposes session variables via the `?d=` debug flag.
   - First auth guard pass applied:
     - Added shared `isLoggedInSession()`, `requireLoggedInJson()`, and `requireLoggedInDownload()` helpers in `app/includes/common.php`.
     - Added logged-in session checks to the global API CRUD endpoint, order/purchase/inventory/report/production AJAX endpoints, order CSV export, production CSV exports, and active PDF document endpoints.
     - This pass intentionally does not enforce CSRF yet, to avoid breaking existing AJAX calls until each script sends a token.
1. Critical exposure cleanup plan:
   - Remove or block `app/info.php`.
   - `app/api/json_data.php` is blocked/disabled; rebuild only if a future authenticated, allowlisted endpoint is needed.
   - Public `_notes` folders were removed from `app/`; keep `.htaccess` blocks in place because Dreamweaver may recreate them.
   - Remove or block any future `*_orig.php`, `.log`, sample cert/key files, and vendor examples from the public app tree.
   - Confirm `web_config_ft.php` is never uploaded to public web space.
2. Upload and file handling audit:
   - Review `app/api/crud.php` upload handlers for `upload_user_photo`, `upload_job_photo`, `upload_order_file`, and `upload_company_file`.
   - Add/require top-level auth, role/company checks, CSRF for browser-origin mutations, and POST-only mutation handling before any patch.
   - Validate file size, MIME, extension, image decoding, S3 key construction, stored filenames, and delete/unlink paths.
   - Ensure uploaded or generated files cannot execute as PHP and cannot overwrite/delete files outside approved directories.
3. Third-party dependency audit and update plan:
   - Inventory every dependency under `app/assets/vendor` and every CDN dependency in `app/index.php`.
   - Prioritize vulnerable or public PHP libraries: TCPDF, TinyMCE, jQuery UI, Quill, Bootstrap, AWS SDK for PHP.
   - Move PHP libraries out of public `assets/vendor` where possible; publish only browser CSS/JS/font assets.
4. Auth, session, and CSRF hardening:
   - Replace URL token login with expiring one-time invite/reset flow.
   - Regenerate session ID after login and ensure logout fully clears session cookies.
   - Standardize CSRF validation across all state-changing endpoints.
   - Shared login guards have been added to the highest-risk API, PDF, CSV, and order/purchase/inventory/report/production endpoints; continue applying role/company ownership guards and CSRF endpoint-by-endpoint.
5. SQL/input safety:
   - Replace dynamic table/field/column SQL helpers with allowlisted maps or purpose-built queries.
   - Review accounting, export, PDF, delete, and status-update endpoints for GET-triggered state changes and missing authorization.
6. Production hygiene:
   - Turn off `display_errors` in production-facing files and log errors privately.
   - Standardize server paths, especially `/home/revolvexcom/` versus the expected server home path.
   - Replace short PHP tags `<?` with `<?php` after confirming server compatibility.

## Confirmed Dependency Inventory
- Bootstrap `5.3.8` from `app/assets/vendor/bootstrap/css/bootstrap.css`.
- Bootstrap Icons version not yet pinned from local header; identify from source package or file hash before update.
- Boxicons version not yet pinned from local header.
- ApexCharts `3.37.1` from `app/assets/vendor/apexcharts/apexcharts.js`.
- Chart.js removed from the public vendor tree; no app usage found.
- ECharts version not yet pinned from local header; inspect minified header/package source before update.
- jQuery UI `1.14.2` from `app/assets/vendor/jquery/jquery-ui.min.js`.
- Quill removed from the public vendor tree; no app usage found.
- Remix Icon removed from the public vendor tree; no app usage found.
- PHPMailer `6.12.0` from `app/assets/vendor/php-mailer/VERSION`.
- TCPDF `6.7.8` from `app/assets/vendor/tcpdf/VERSION`.
- TinyMCE removed from the public vendor tree; no app usage found.
- PHP Email Form Validation removed from the public vendor tree; no app usage found.
- Dropzone `5` is loaded from `https://unpkg.com/dropzone@5/dist/min/dropzone.min.js`.
- Popper `1.12.9` CDN load removed; Bootstrap bundle includes Popper.
- jQuery `3.6.4` is loaded from Google CDN.
- jQuery UI theme `1.14.2` is now served locally from `app/assets/vendor/jquery/jquery-ui.min.css`.
- Public `app/assets/vendor/aws-php` was removed because app code uses `/home/revolvexcom/vendor/autoload.php`; verify and update the server Composer AWS SDK separately.

## Confirmed Dependency Risk Notes
- TinyMCE was removed earlier because no app usage was found.
- TCPDF was upgraded to `6.7.8`; this stays past the CVE-2024-51058 fix while avoiding the newer 6.8+/6.11+ cURL-extension dependency that fails on the local PHP CLI.
- jQuery UI was upgraded to `1.14.2`; retest autocomplete, datepicker, sortable, draggable, and droppable flows after upload.
- Quill was removed because no app usage was found.
- Bootstrap was upgraded to `5.3.8`; retest dropdowns, modals, tabs, collapse, and layout after upload.
- AWS SDK for PHP still needs server-side Composer verification/update under `/home/revolvexcom/vendor`, not the public mirror.

## Dependency Usage Review
- Clearly used and currently required:
  - Bootstrap CSS/JS is used across the app, login, signup, menus, modals, collapse, dropdowns, and layout.
  - Bootstrap Icons are used heavily through `bi` classes.
  - Boxicons are used heavily through `bx` classes.
  - jQuery and jQuery UI are used for autocomplete, datepicker, sortable, and draggable behavior in orders, purchasing, accounting, reports, setup, and production scripts.
  - Dropzone is used for order/company/user upload widgets.
  - ApexCharts is used in `app/includes/admin_dashboard.php` and `app/includes/company_dashboard.php`.
  - ECharts is used in `app/includes/admin_dashboard.php` and `app/includes/company_dashboard.php`.
  - TCPDF is required by many `app/pdf/*.php` scripts.
  - PHPMailer is required by `app/includes/common_mail.php`.
  - AWS SDK is required by `app/includes/common.php` for S3 upload/download/presigned URLs, but it is loaded from `/home/revolvexcom/vendor/autoload.php`, not public `assets/vendor`.
- Removed after backup/testing:
  - TinyMCE: no non-vendor references found; not loaded by `app/index.php`.
  - Remix Icon, PHP Email Form Validation, Chart.js, Simple DataTables, Quill, and public AWS SDK copy have been removed from `app/assets/vendor` after backup.
- Dependency cleanup priority:
  1. Keep Bootstrap, Bootstrap Icons, Boxicons, jQuery, jQuery UI, Dropzone, ApexCharts, ECharts, TCPDF, and PHPMailer.
  2. Verify and update server Composer packages under `/home/revolvexcom/vendor`, especially AWS SDK for PHP.
  3. Retest the screens that rely on Bootstrap and jQuery UI after upload.

## Upload/File Handling Findings To Verify First
- `app/api/crud.php` has no obvious top-level login/role guard before upload/delete actions.
- `upload_user_photo` appends `user_id` in the query string from JavaScript and updates that user image path.
- `upload_job_photo`, `upload_order_file`, and `upload_company_file` rely on session company/user IDs but should still enforce object ownership.
- Client-side Dropzone restrictions are not security controls; server-side validation must be authoritative.
- File handlers should reject empty/failed uploads, enforce size limits server-side, normalize extensions, verify decoded images, and store only random filenames.
- Deletion paths from database values must be constrained to approved directories or S3 keys before `unlink`.

## Confirmed High-Priority Audit Backlog
1. Critical public exposure items:
   - `app/info.php` exposes `phpinfo()`.
   - `app/api/json_data.php` previously exposed dynamic table reads using request-controlled table names; it is now disabled and blocked.
   - Public legacy/debug artifacts existed and have been partly removed: `app/api/api_orig.php`, `app/api/crud_orig.php`, `app/includes_pages/admin_accounting/myob_api_response.log`, and `app/includes_pages/admin_orders/php_debug.log` are absent locally. Dreamweaver `_notes/dwsync.xml` folders were removed from `app/` and blocked in `.htaccess`.
2. Authentication and authorization:
   - `app/index.php` logs users in from a persistent `?token=` URL parameter.
   - Direct endpoints under `app/api`, `app/includes_pages/*/crud.php`, `app/pdf`, and export CSV scripts need shared auth, role, and company-object ownership guards.
   - `app/trust_verification.php` stores `trusted_device` as an unsigned user id cookie without Secure, HttpOnly, or SameSite flags.
3. CSRF and request method issues:
   - State-changing handlers are commonly triggered by GET flags, including upload/accounting/profile actions.
   - `app/includes_pages/super_admin_profile/crud.php` validates CSRF by passing the session token back into `verifyCsrfToken()`, which does not prove the request supplied a token.
   - `update_user_password` in `app/includes_pages/super_admin_profile/crud.php` changes a password before any visible CSRF check.
4. SQL and input safety:
   - `app/includes/common.php` contains many helpers that interpolate table, column, and field names directly into SQL.
   - `app/includes_pages/admin_accounting/myob_functions.php` contains raw interpolated SQL around MYOB job/item updates.
   - `app/includes_pages/admin_support_request/content.php` concatenates `$_SESSION['session_company_id']` directly into SQL.
5. Accounting integration exposure:
   - MYOB and Xero token generation handlers print token/API response data with `print_r` or raw `echo`.
   - Accounting mutation handlers use GET flags such as `get_new_access_token`, `create_invoice`, `create_bill`, `create_customer`, and sync actions.
   - Xero code contains a placeholder `Authorization: Bearer YOUR_ACCESS_TOKEN_HERE` branch that should be removed or corrected.
6. PDF/CSV/report endpoints:
   - PDF scripts take direct GET IDs and generate or save files under public paths.
   - Purchase order PDF scripts save files and return filesystem/web paths from GET-triggered requests.
   - CSV export endpoints need auth/company checks and output escaping review.
7. Production hygiene:
   - Many production-facing PHP files enable `display_errors`.
   - Many JavaScript files log raw AJAX/accounting/order data to the browser console.
   - Short PHP open tags exist and should be replaced with `<?php` after compatibility confirmation.
   - Local PHP CLI is not available in PATH, so syntax checks have not been run locally.

## Completed Security Fixes
- Replaced web `?token=` login with `?setup_token=` one-time setup links.
- Added setup-token helpers in `app/includes/common.php`:
  - setup token generation with a 24-hour expiry,
  - hash-only storage in `tblUsers.token`,
  - expiry/password checks on verification,
  - token clearing after password setup.
- Updated `app/includes/common_mail.php` to create fresh setup links when sending new-user invite emails.
- Updated `app/index.php` so old `?token=` links no longer create a web session.
- Added session ID regeneration on normal login and setup-token login.
- Updated `app/complete_signup.php` to clear setup tokens after password creation and block already-passworded sessions from using the setup page.
- Backup copies for this fix were stored outside the FTP mirror under `D:\Hosted Sites\revolvex_backups\token_login_fix_20260518_164935`.
- Removed unused TinyMCE from `app/assets/vendor/tinymce` after backing it up.
- Removed global Dropzone loading from `app/index.php`; Dropzone is now loaded only by the Orders page include.
- Disabled non-order upload endpoints in `app/api/crud.php` for user photo, job photo, and company file uploads.
- Hardened order attachment uploads in `app/api/crud.php` with POST-only handling, session checks, order/company ownership checks, 10 MB size limit, strict extension/MIME checks, PDF/JPEG/MSG file signature validation, randomized S3 filenames, JSON responses, `Cache-Control: no-store`, and `X-Content-Type-Options: nosniff`.
- Removed public old upload clone `app/api/crud_orig.php` after backup, because it would otherwise preserve the disabled upload endpoints.
- Backup copies for the dependency/upload cleanup were stored outside the FTP mirror under `D:\Hosted Sites\revolvex_backups\dependency_dropzone_tinymce_20260518_170434`.
- Follow-up upload error fix:
  - `app/api/crud.php` now suppresses PHP display errors for upload requests, buffers upload output, and returns JSON for fatal upload errors where PHP permits shutdown handling.
  - Replaced PHP 7.1 array destructuring in the upload handler with `list(...)` for older PHP compatibility.
  - Allowed `application/x-pdf` as a PDF MIME variant while still requiring `%PDF-` file signature.
  - `app/includes_pages/admin_orders/scripts.js` now catches invalid JSON responses and shows a controlled upload error.
  - Backup copies for this fix were stored under `D:\Hosted Sites\revolvex_backups\order_upload_error_fix_20260518_171115`.
- Temporary upload diagnostic added:
  - Upload fatal JSON temporarily included a diagnostic id and the PHP fatal file/line/message so production errors could be identified without FTP log access.
  - The main order upload flow is wrapped in `try/catch` for normal exceptions.
  - Backup copies for the diagnostic patch were stored under `D:\Hosted Sites\revolvex_backups\upload_diagnostic_20260518_171431`.
  - The user-facing PHP file/line/message detail has now been removed; current upload errors return only a generic message plus diagnostic id.
- Upload MIME compatibility fix:
  - Production PHP reported `Call to undefined function mime_content_type()`.
  - Added `detectUploadMimeType()` in `app/api/crud.php` to prefer `finfo_file()`, fall back to `mime_content_type()` only if available, then default to `application/octet-stream`.
  - Backup copies for this fix were stored under `D:\Hosted Sites\revolvex_backups\upload_mime_compat_20260518_171622`.
- S3 presigned URL fix:
  - AWS returned `SignatureDoesNotMatch` when opening order attachments.
  - Normalized S3 object keys in `generatePreSignedUrl()` by removing leading `./` and slashes before signing.
  - Updated order attachment links to request `order_files/...` instead of `./order_files/...`.
  - Escaped rendered presigned URLs in order/company download links.
  - Backup copies for this fix were stored under `D:\Hosted Sites\revolvex_backups\s3_presign_fix_20260518_171945`.
- Order attachment delete fix:
  - Order attachment rows now render `data-file-id` and `data-order-id`.
  - Delete requests now post JSON to `app/api/crud.php` with `action=delete_order_file`, `file_id`, and `order_id`.
  - Server checks logged-in session, `tblOrderFiles.id`, `order_id`, and session `company_id` before deleting the row.
  - This currently deletes the database attachment record; S3 object deletion should be added once delete permissions/key handling are confirmed.
  - Backup copies for this fix were stored under `D:\Hosted Sites\revolvex_backups\order_file_delete_fix_20260518_172251`.
- Production error display fix:
  - Flipped active `ini_set('display_errors', 'On')` and `ini_set('display_errors', 1)` calls to `Off`/`0` across production-facing PHP files.
  - Flipped active `display_startup_errors` calls to `0`.
  - Backup copies for this fix were stored under `D:\Hosted Sites\revolvex_backups\display_errors_off_20260518_175912`.
  - PHP CLI is still not available locally, so upload and smoke-test the changed files on the server.
- Upload diagnostic/log cleanup:
  - `app/api/crud.php` no longer returns fatal PHP file, line, or message details to the browser. It now returns only a generic upload failure plus a diagnostic id while logging details privately.
  - Removed public log files `app/includes_pages/admin_accounting/myob_api_response.log` and `app/includes_pages/admin_orders/php_debug.log` from the mirror.
  - Backup copies for this fix were stored under `D:\Hosted Sites\revolvex_backups\remove_upload_diag_logs_20260518_181500`.
- Dependency refresh:
  - Upgraded TCPDF from `6.7.5` to `6.7.8`.
  - Upgraded PHPMailer from `6.9.1` to `6.12.0` while staying on the compatible 6.x branch.
  - Upgraded Bootstrap from `5.2.3` to `5.3.8`.
  - Upgraded jQuery UI from `1.12.1` to `1.14.2` and moved the jQuery UI theme CSS from CDN to local `app/assets/vendor/jquery/jquery-ui.min.css`.
  - Removed unused public vendor folders: `app/assets/vendor/chart.js`, `app/assets/vendor/quill`, `app/assets/vendor/php-email-form`, `app/assets/vendor/remixicon`, `app/assets/vendor/simple-datatables`, and `app/assets/vendor/aws-php`.
  - Removed unused `Chart.js`, `Quill`, PHP Email Form, Popper 1.12.9 CDN, and old jQuery UI CDN references from `app/index.php`.
  - Removed old Quill/SimpleDataTables template initialization code from `app/assets/js/main.js`.
  - Added `app/assets/vendor/.htaccess` to block direct web access to PHP, metadata, config, certificate, and log-like files under public vendor assets while allowing CSS/JS/font/image assets.
  - Backup copies for this dependency refresh were stored under `D:\Hosted Sites\revolvex_backups\dependency_refresh_20260523_071900`.

## Completed Dashboard Work
- Added top-level admin dashboard status boxes in `app/includes/admin_dashboard.php` for current counts of `Quote`, `Quoted`, and `Order` statuses.
- Counts are scoped to the logged-in `session_company_id` and use `tblOrderStatus.description` joined to `tblOrders.order_status_id`.
- Backup copy for this dashboard change was stored under `D:\Hosted Sites\revolvex_backups\admin_dashboard_status_boxes_20260522_000000`.
- Made those dashboard status boxes clickable, linking to `admin_orders_list` with `order_status_id` in the URL.
- Updated `app/includes/admin_orders_list.php` and `app/includes_pages/admin_orders_list/script.js` so the existing status filter is selected and applied on first load when `order_status_id` is present.
- Backup copy for this link/filter change was stored under `D:\Hosted Sites\revolvex_backups\dashboard_status_links_20260522_000000`.
- Restyled the dashboard status cards with stronger hover, accent, icon, and layout treatment.
- Added a `Purchase Orders` dashboard card for purchase status `Order`, linked to `admin_purchasing_list` with the purchase status filter applied.
- Fixed `Recent Orders` dashboard data so it is ordered by `order_date DESC, order_id DESC`.
- Added Created/Date sort toggles to `admin_orders_list`, preserving search/status state across sort and pagination.
- Updated purchase list filtering so incoming `order_status_id` selects the existing status dropdown on first load.
- Backup copy for this dashboard/list upgrade was stored under `D:\Hosted Sites\revolvex_backups\dashboard_cards_sort_purchase_20260522_000000`.
- Reworked the Recent Orders dashboard table in `app/includes_pages/admin_dashboard/content.php` so it shows richer order details: order/customer, site/contact, status, item count, created date, delivery date, and estimated total including GST.
- Recent Orders now has stronger card/table styling, smaller supporting text, and hover treatment while keeping the main row text readable.
- Backup copy for this Recent Orders upgrade was stored under `D:\Hosted Sites\revolvex_backups\recent_orders_rich_rows_20260522_000000`.
- Replaced the old `Order by Month Status` dashboard chart with an `Invoice Performance` business chart.
- Added `read_invoice_performance` to `app/includes_pages/admin_dashboard/crud.php`, grouping `tblInvoice` by order/month so invoice counts are not inflated by line items.
- New chart shows monthly invoiced value as columns and invoice count as a line, with KPI tiles for 12-month value, invoice count, average invoice value, and current month value/count.
- Backup copy for this invoice-performance dashboard change was stored under `D:\Hosted Sites\revolvex_backups\dashboard_invoice_performance_20260522_000000`.

## Completed Order Page Work
- Restyled the top `admin_orders.php` customer/order info strip into a stronger order header card with accent bar, customer icon, status chip, delivery/note panels, and action area.
- Updated `app/includes_pages/admin_orders/scripts.js` so the header fields populate cleanly into the new layout.
- Backup copy for this order header refresh was stored under `D:\Hosted Sites\revolvex_backups\admin_orders_header_refresh_20260522_000000`.
- Fixed the order page print dropdown stacking issue by allowing the new header card to overflow visibly and raising the print menu z-index.
- Backup copy for this dropdown fix was stored under `D:\Hosted Sites\revolvex_backups\admin_orders_print_dropdown_zindex_20260522_000000`.

## Completed Purchasing Activity Work
- Added `addPurchaseActivity()` in `app/includes/common.php` for `tblPurchaseActivity`.
- Added a purchasing Activity tab and add/edit/delete activity modals in `app/includes/admin_purchasing.php`.
- Added purchase activity rendering in `app/includes_pages/admin_purchasing/content.php`.
- Added purchase activity JavaScript actions in `app/includes_pages/admin_purchasing/scripts.js`.
- Added automatic activity records in `app/includes_pages/admin_purchasing/crud.php` for:
  - new purchase creation,
  - status, required date, vendor/details, delivery details, and notes changes,
  - receive details changes,
  - invoice details changes,
  - bill conversion,
  - invoice conversion,
  - stock received,
  - receive reversal.
- Added accounting activity records when MYOB or Xero bill sends succeed.
- Backup copies for this purchasing activity work were stored under `D:\Hosted Sites\revolvex_backups\purchase_activity_20260522_171023`.
- Updated the order confirmation email modal/defaults:
  - subject defaults to `Featherstones ORDER CONFIRMATION #{order_id}`,
  - body references the internal order id, customer purchase order number, and order date,
  - editable modal body no longer includes business footer details,
  - sales document emails append the normal company footer at send time.
- Backup copies for this email format change were stored under `D:\Hosted Sites\revolvex_backups\email_order_confirmation_format_20260522_172039`.

## Rescan Snapshot 2026-05-18
- Confirmed locally absent after cleanup:
  - `app/info.php`
  - `app/api/api_orig.php`
  - `app/api/crud_orig.php`
  - `app/assets/vendor/tinymce`
  - `app/includes/test.php`
- Still present and high priority:
  - `app/api/json_data.php` is now disabled and blocked; rebuild only if replaced with auth plus allowlists.
  - Dreamweaver `_notes/dwsync.xml` files were removed from the public tree and blocked by `.htaccess`; Dreamweaver may recreate them locally later.
  - TCPDF example certificate files still exist under `app/assets/vendor/tcpdf/examples/data/cert/`.
  - `app/api/crud.php` still contains old lower upload-handler code blocks for disabled non-order uploads; current early handlers bypass them, but the dead code should be removed to reduce future mistakes.
- Active `display_errors`/`display_startup_errors` settings now scan as disabled in app-owned files.
- Debug-output backlog remains:
  - MYOB/Xero PHP handlers still contain active `print_r` output.
  - Many app JavaScript files still contain `console.log` statements that can expose order/accounting/profile data in the browser console.
  - Dashboard/report PHP still has verbose `error_log` statements that may log raw request/report data.
- SQL/input backlog remains:
  - `app/includes/common.php` still contains dynamic SQL helpers interpolating table/field/column names.
  - `app/api/json_data.php` has been disabled as the most direct dynamic-table exposure.
  - Accounting endpoints and many CRUD endpoints still need method, CSRF, role, and company ownership review.

## Next Fix Priority
1. Replace any future need for `app/api/json_data.php` with purpose-built, authenticated endpoints.
2. Keep public Dreamweaver `_notes` metadata blocked/removed from the mirror/server.
3. Remove dead legacy upload blocks from `app/api/crud.php`.
4. Harden accounting endpoints: remove `print_r` token/API output, convert GET mutations to POST, add CSRF and company checks.
5. Replace dynamic SQL helpers in `app/includes/common.php` with allowlisted table/column maps or specific query helpers.
6. Verify/update server Composer packages under `/home/revolvexcom/vendor`, especially AWS SDK for PHP.
7. Clean browser `console.log` output from order, purchasing, production, company, profile, and accounting scripts.
