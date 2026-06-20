# RevolveX Product Requirements Document

Last updated: 2026-06-21

## Purpose
RevolveX is the operational system for managing quotes, orders, production, purchases, inventory, reports, document generation, email sending, and accounting integrations for Featherstone Steel & Purlins.

The current product goal is to keep improving the system as a practical business tool while reducing production security risk. Changes now use a GitHub-backed workflow with cPanel hosting: develop locally in `G:\Hosted Sites\revolvex`, commit/push to `entacom/revolvex`, then deploy through the in-app `Git Update` page where possible.

## Current Objectives
1. Make daily order, purchase, inventory, report, and dashboard workflows faster and clearer.
2. Record meaningful order and purchase activity so staff can see who changed what and when.
3. Keep document printing/emailing reliable for quotes, order confirmations, invoices, purchase orders, delivery, picking, labels, and production paperwork.
4. Reduce public attack surface from exposed diagnostics, logs, backups, old endpoints, and third-party libraries.
5. Continue hardening authentication, authorization, upload handling, SQL/input paths, and accounting endpoints without breaking production.

## Users
- Internal admin/sales users who create quotes, orders, invoices, purchases, and customer documents.
- Production users who need clean production queues, labels, picking, packing, and purchased-item exclusions.
- Purchasing users who manage supplier purchase orders, receiving, invoice/bill conversion, and purchase activity.
- Management users who need useful dashboards, invoice performance, recent orders, and recent activity.
- Accounting users who connect MYOB/Xero and need reliable invoice/bill/customer sync flows.

## Product Principles
- Business-first: dashboards, lists, and reports should answer real operational questions, not just display raw tables.
- Fast to scan: list pages should show strong hierarchy, status, dates, values, and next action.
- Traceable: important changes should create activity records using the logged-in session user.
- Safe by default: direct endpoints, uploads, generated files, and public libraries should require appropriate guards.
- Conservative changes: avoid large rewrites unless a targeted change cannot safely solve the issue.

## Recently Completed
- Dashboard: richer status cards, purchase order card, invoice performance chart, cached closed-month invoice summaries, last 3 recent orders, and last 5 recent activities.
- Orders: stronger order header, separate Print and Email dropdowns, Process Order workflow modal with per-action activity history, Process Quote modal with payment tracking and convert-to-order, compact order activity grid, permission-backed activity deletion, email quote/order confirmation support, richer recent order data, order activity logging, sorted/restyled order list.
- Purchases: purchase activity table/workflow, Process Purchase modal with delivery docket, confirmation request/received tracking, confirmation file upload, estimated arrival date, purchase order print/email, transient email attachments, restyled purchasing list, sortable purchase columns, activity logging for key purchasing changes.
- Production: purchased items copied to PO are excluded from production and production exports; production rows can also be manually marked as purchased from the queue.
- Reports and Inventory: refreshed layouts, report inputs, inventory row styling, stock status pills, and Finished filter on stock report.
- Uploads/files: order attachment upload validation, safer JSON upload errors, S3 presigned URL normalization, and ownership-checked order attachment deletion.
- Security: disabled dynamic table API, disabled diagnostic/test endpoints, blocked public metadata/log/backup/cert files, removed Dreamweaver `_notes`, removed `?d=` session dump, added first-pass auth guards to high-risk endpoints.
- Dependencies: updated TCPDF to `6.7.8`, PHPMailer to `6.12.0`, Bootstrap to `5.3.8`, jQuery UI to `1.14.2`; removed unused public Chart.js, Quill, PHP Email Form, Remix Icon, Simple DataTables, TinyMCE, and public AWS SDK copy.
- Deployment: initialized Git, pushed the app to GitHub at `entacom/revolvex`, added `.gitignore`, added `.cpanel.yml`, connected cPanel Git at `/home/revolvexcom/revolvex`, and added the in-app `Git Update` page. Because cPanel disables `exec` and `shell_exec`, the app deployer now downloads the GitHub ZIP and copies the `app/` folder to `/home/revolvexcom/public_html` using PHP.

## Priority Backlog

### P0 - Security And Stability
1. Harden accounting endpoints.
   - Remove raw `print_r`/token/API output from MYOB/Xero handlers.
   - Convert GET-triggered mutations to POST where practical.
   - Add logged-in, role/company, and CSRF checks.
   - Remove placeholder bearer-token branches.

2. Remove dead legacy upload blocks from `app/api/crud.php`.
   - Keep the current hardened order upload flow.
   - Remove disabled/obsolete upload paths to reduce future mistakes.

3. Add CSRF support endpoint-by-endpoint.
   - Start with order, purchasing, inventory, production, report, profile, and accounting mutations.
   - Add tokens to existing AJAX scripts before enforcing server-side checks.

4. Continue direct endpoint authorization.
   - Ensure every PDF/CSV/API/action endpoint checks login and company/object ownership.
   - Add role checks where actions are admin-only.

### P1 - SQL/Input Hardening
1. Replace dynamic SQL helpers in `app/includes/common.php`.
   - Use allowlisted table/column maps or purpose-built query helpers.
   - Prioritize helpers reachable from request parameters.

2. Review accounting and CRUD SQL.
   - Focus on raw interpolated values, dynamic column names, delete/update paths, exports, and status changes.

3. Keep `app/api/json_data.php` disabled.
   - Rebuild only as specific authenticated endpoints with allowlisted queries.

### P2 - Dependency And Production Hygiene
1. Verify server Composer packages under `/home/revolvexcom/vendor`.
   - Confirm AWS SDK for PHP version.
   - Update server Composer packages outside public web root.

2. Retest Bootstrap and jQuery UI dependent workflows after dependency upload.
   - Orders, purchases, accounting autocomplete, datepickers, setup sortable, drag/drop pack flow, modals, dropdowns, tabs.

3. Clean browser console output.
   - Remove raw `console.log` data from order, purchasing, production, company, profile, dashboard, reports, and accounting scripts.

4. Replace short PHP tags after server compatibility confirmation.
   - Move from `<?` to `<?php` in app-owned files.

### P3 - Product Improvements
1. Dashboard polish.
   - Add more meaningful recent activity categories if required.
   - Consider dashboard warnings for overdue delivery, missing PO, unreceived purchases, or un-invoiced orders.

2. Orders workflow.
   - Improve purchased-item visibility and production exclusion confidence.
   - Add clearer attachment/email/document history if needed.
   - Refine the Process Order/Quote modals once exact payment, stock-deduction, and upload-original timing are confirmed.

3. Purchases workflow.
   - Improve purchase activity visibility and filter/search.
   - Decide whether purchase process attachments should be stored permanently or remain email-only.
   - Consider a dashboard queue for POs whose supplier confirmation has not arrived within 48 hours.
   - Add dashboard/purchase summary cards if useful.

4. Reports.
   - Expand useful business reports once security and dependency work settles.
   - Keep report queries efficient and indexed.

## Non-Goals For Now
- No full framework rewrite.
- No large database schema redesign unless a specific feature or security fix requires it.
- No new public diagnostic/debug endpoints.
- No storing reports or generated scratch files in the FTP mirror unless explicitly requested.

## Deployment And Backup Requirements
- Local development path: `G:\Hosted Sites\revolvex`.
- GitHub remote: `https://github.com/entacom/revolvex.git`.
- Production cPanel clone: `/home/revolvexcom/revolvex`.
- Production public web root: `/home/revolvexcom/public_html`.
- Normal deployment path: commit locally, push to GitHub, then use the app menu item `Git Update` to download and deploy the latest GitHub version.
- cPanel Git deploy remains the fallback/bootstrapping path if the live Git Update page itself needs repair.
- Keep file changes scoped and commits clear.
- Before high-risk code edits or schema-sensitive changes, create timestamped local backups under `G:\Hosted Sites\revolvex_backups`.
- Do not repeat credential values from `web_config_ft.php`.
- Treat `web_config_ft.php` as reference-only and keep it outside public web access.
- PHP lint changed PHP files with `C:\php-8.5.6\php.exe -l` where possible.
- For dependency changes, list folders/files to upload/delete or include them in the Git deploy, depending on the safest path.

## Acceptance Criteria
- Daily pages remain usable after every change: dashboard, order list, order detail, purchasing list, purchase detail, inventory, reports, production, PDF generation, and email sending.
- Security fixes do not expose secret values or raw server paths to users.
- Activity records show the logged-in user, not a hardcoded user.
- Uploads accept only intended file types and return controlled JSON errors.
- Public vendor folders contain only required assets/libraries and block direct access to PHP/metadata files where possible.
- `AGENTS.md` and this PRD stay updated as priorities change.
