<?php
function pageHelpCurrentKey() {
    $page = isset($_GET['p']) ? trim((string)$_GET['p']) : 'admin_dashboard';

    if ($page === 'admin_orders_list' && isset($_GET['t']) && (int)$_GET['t'] === 1) {
        return 'admin_quotes_list';
    }

    return $page;
}

function pageHelpData($pageKey) {
    $help = array(
        'admin_dashboard' => array(
            'title' => 'Dashboard Help',
            'subtitle' => 'The dashboard is the first-stop overview for daily work. Use it to see what needs attention before drilling into orders, purchases, reports, or activity.',
            'sections' => array(
                'What This Page Is For' => array(
                    'Use the dashboard at the start of the day to understand how many quotes, orders, quoted jobs, and purchase orders are currently active.',
                    'The cards at the top are shortcuts. Clicking a card should take you to the matching filtered list, such as current Orders or current Quotes.',
                    'Recent Orders shows the newest customer jobs so you can jump back into work without searching.',
                    'Recent Activity shows the latest recorded changes across orders, including who performed the action and what changed.'
                ),
                'How To Read It' => array(
                    'Order and quote cards show count-based workload. Use them to spot whether sales/admin work is building up.',
                    'Purchase order cards show supplier-side workload. Use this to check if bought-in items may hold up customer jobs.',
                    'Invoice Performance is a management view. Previous months are cached because they are history, while the current month is calculated live.',
                    'If recent activity looks wrong, open the related order and check its Activity tab for the full timeline.'
                ),
                'Common Next Steps' => array(
                    'If a job needs action, open it from Recent Orders or the Orders list.',
                    'If supplier items are overdue or unconfirmed, open Purchases and check the Process Purchase modal.',
                    'If stock or coil value looks wrong, go to Reports or Inventory and filter the stock data.',
                    'If the dashboard numbers look stale after a deploy, refresh the browser once and re-open the page.'
                )
            )
        ),
        'admin_quotes_list' => array(
            'title' => 'Quotes Help',
            'subtitle' => 'Quotes are customer jobs that have not yet become active orders. This page is used to find, open, and progress quote records.',
            'sections' => array(
                'Basic Quote Flow' => array(
                    'Start by creating or opening a quote from this list.',
                    'On the quote page, fill in customer details, site/delivery details, order number or customer reference, sales user, and notes.',
                    'Add quoted items in the Items tab. These are the products or materials the customer is being quoted for.',
                    'Use the Print or Email controls to send the quote document to the customer.',
                    'Use Process Quote when you need to record quote workflow steps such as quote printed, quote emailed, payment required, or payment received.',
                    'When the customer approves the quote, convert it to an order. From that point, continue the work through the normal Order workflow.'
                ),
                'Searching And Filtering' => array(
                    'Use the search box to find a quote by customer name, delivery address, order number/reference, phone, email, or notes.',
                    'Use the filter dropdown when you only want a specific status, such as Quote or Quoted.',
                    'If you cannot find a quote, clear the search/filter first, then try a shorter search term.'
                ),
                'Things To Watch' => array(
                    'Do not convert a quote to an order until the customer has approved it.',
                    'If payment is required, use Process Quote to tick Payment Required and only convert once Payment Received is recorded.',
                    'Keep notes clear because they become part of the job history and help the next person understand what happened.'
                )
            )
        ),
        'admin_orders_list' => array(
            'title' => 'Orders List Help',
            'subtitle' => 'The Orders list is the main work queue for active customer jobs. Use it to find orders, check delivery pressure, and open jobs that need action.',
            'sections' => array(
                'What You See In The List' => array(
                    'Each row is a customer order. The row shows the order ID, customer, customer reference/order number, created date, delivery location, delivery date, status, and item completion tally.',
                    'The item completion tally, for example 3/6, means 3 order lines are complete out of 6 total order lines.',
                    'The coloured status pill shows the order stage, such as Order, In Production, Awaiting Delivery/Collection, or Invoiced.',
                    'If the delivery date is close and not all items are complete, the status/date area turns red and bold so it stands out.'
                ),
                'How To Use This Page' => array(
                    'Use search when you know part of the customer name, address, phone, email, order number, or notes.',
                    'Use Reset to clear search and filters and return to the normal list.',
                    'Use the filter dropdown to focus on a specific status.',
                    'Click Created or Date headers to sort ascending/descending.',
                    'Click a row to open the full order page.'
                ),
                'Recommended Daily Flow' => array(
                    'Sort by delivery date to see the most urgent work first.',
                    'Open red or bold rows first because they are close to delivery and not complete.',
                    'Check the completion tally before opening a job. A low tally means production, packing, purchasing, or receiving may still need work.',
                    'If a row appears complete but still has the wrong status, open the order and check the Items tab and Activity tab.'
                )
            )
        ),
        'admin_orders' => array(
            'title' => 'Order Page Help',
            'subtitle' => 'The Order page is the main control centre for one customer job. It covers customer details, items, purchasing, packing, production, documents, activity, delivery, and invoice flow.',
            'sections' => array(
                'Start On The Home Tab' => array(
                    'Check the customer name/contact, delivery address, suburb, delivery date, delivery note, sales user, order number/reference, and order status.',
                    'If the customer is a cash sale, use the Cash Sale toggle so the page treats the order correctly.',
                    'Use Save after changing customer, delivery, status, notes, or date details. Important changes are written to the Activity tab.',
                    'The top header shows the customer/job, delivery date, note, status, and item completion tally.'
                ),
                'Items Tab' => array(
                    'Add each product or material line to the order from the Items tab.',
                    'Manufactured items are used for production cards, labels, packs, production CSV files, and production workflow.',
                    'Bought-in/purchased items can be tagged with the green tag button and copied to a supplier PO using Copy Tagged Items To PO.',
                    'When tagged items are copied to a PO, the order item gets linked to that purchase order and is removed from production lists.',
                    'Use the Done checkbox when a line is complete. Manufactured lines can be ticked when production has been matched/finished. Purchased lines can be completed automatically when the linked PO is received.'
                ),
                'Pack Tab' => array(
                    'Use packs when the order needs labels or packing groups.',
                    'Create packs, then drag order sub-items into the correct pack.',
                    'The pack list shows pack number and calculated weight.',
                    'All Dymo and All Zebra print labels for all packs. Individual buttons print/download labels for a single pack.',
                    'If labels will not print from Process Order, check that at least one pack exists and has items assigned.'
                ),
                'Process Order Button' => array(
                    'Process Order is a checklist for the steps normally done once the order is ready to move forward.',
                    'Order Confirmation lets you email or print the customer order confirmation.',
                    'Labels prints Dymo or Zebra labels for packed items. Labels require packs first.',
                    'Production CSV Files saves machine CSV files for manufactured items.',
                    'Production Cards prints cards for manufactured items only, not purchased items.',
                    'Delivery Docket prints the delivery docket.',
                    'Process moves the order to In Production and records that action.'
                ),
                'Activity Tab' => array(
                    'Activity is the job history. It records manual notes and system actions such as status changes, delivery changes, emails, prints, process actions, and item completion.',
                    'The user shown should be the logged-in user who performed the action.',
                    'Use Add Activity for manual notes that other staff need to see.',
                    'Only users with the delete permission should be able to delete activity records.'
                ),
                'Completion And Delivery Warning' => array(
                    'The order completion tally is based on completed order item lines.',
                    'When all lines are complete, the order can move to Awaiting Delivery/Collection if that status exists.',
                    'If the delivery date is today/tomorrow and the job is not 100% complete, the status and delivery date are highlighted red/bold.',
                    'If the tally looks wrong, open Items and check which lines are unticked or linked to an unreceived purchase order.'
                )
            )
        ),
        'admin_purchasing_list' => array(
            'title' => 'Purchases List Help',
            'subtitle' => 'Purchases is the supplier-side work queue. Use it to track purchase orders, confirmations, received stock, ETA, and invoicing.',
            'sections' => array(
                'What You See In The List' => array(
                    'Each row is a supplier purchase order.',
                    'The row shows supplier/vendor details, purchase order date, required date, ETA/not confirmed information, internal notes/detail, and status.',
                    'Normal list view hides invoiced or closed purchase orders until you search, so active supplier work stays visible.',
                    'Status colours help identify the PO stage: Draft has no colour, Ordered is yellow, Confirmed is orange, Overdue is red, Received is green, and Invoiced is blue.'
                ),
                'How To Use This Page' => array(
                    'Search by vendor, PO number, delivery/reference, notes, or related text.',
                    'Use date/status sorting to focus on urgent supplier work.',
                    'Open a PO to review supplier details, upload confirmations, receive items, attach files, or invoice the purchase.',
                    'If a PO is overdue or not confirmed, open it and check Process Purchase.'
                ),
                'Confirmation Tracking' => array(
                    'Confirmation Required means the supplier should send a confirmation back.',
                    'If confirmation has not been received within 48 hours of request, the PO is flagged.',
                    'When confirmation arrives, upload it in Process Purchase and enter the estimated arrival date.',
                    'Uploaded confirmation files are saved against the PO and are visible/downloadable from the Activity/Documents area.'
                )
            )
        ),
        'admin_purchasing' => array(
            'title' => 'Purchase Order Help',
            'subtitle' => 'This page manages one supplier purchase order, including supplier details, purchase items, confirmations, receiving, invoice conversion, and activity.',
            'sections' => array(
                'Home Tab' => array(
                    'Check vendor name, address, email, purchase order date, required date, estimated arrival, freight, vendor invoice number, delivery details, and internal notes.',
                    'Use internal notes for staff-only information. These are not intended as customer-facing notes.',
                    'Save changes to record status/date/detail updates into purchase activity.'
                ),
                'Process Purchase Button' => array(
                    'Print Delivery Docket prints supplier delivery paperwork.',
                    'Attach Files lets you add supplier files such as punching detail, order forms, or extra documents for the PO email.',
                    'Order Confirmation Required marks that the supplier must send confirmation.',
                    'Confirmation Received lets you upload the supplier confirmation file and enter estimated arrival date.',
                    'Purchase Order Print/Email sends or prints the purchase order. Email can include selected attachments.'
                ),
                'Receive Items' => array(
                    'Use Receive Items when supplier goods arrive.',
                    'Receiving creates stock/inventory rows based on the purchase items.',
                    'If the PO was created from a customer order, receiving the PO marks the linked customer order lines complete.',
                    'Reverse Receive removes the created stock rows and reopens linked customer order lines.'
                ),
                'Invoice And Activity' => array(
                    'Use Invoice/Bill actions after supplier paperwork is ready.',
                    'Purchase Activity records confirmation, receiving, invoice conversion, status changes, and file uploads.',
                    'Saved PO files are shown in the Activity/Documents area so staff can download them later.'
                )
            )
        ),
        'admin_production' => array(
            'title' => 'Production Help',
            'subtitle' => 'Production shows manufactured order work that still needs to be made or matched. Bought-in/purchased items should not appear here.',
            'sections' => array(
                'What Appears Here' => array(
                    'Only manufactured order sub-items should appear in the production queue.',
                    'Items linked to a purchase order through purchased_item are filtered out because they are supplier work, not production work.',
                    'The queue is driven by order items, order sub-items, inventory group settings, and order status.'
                ),
                'How To Work Through Production' => array(
                    'Review the item, order, customer, quantity, and any pack/mark information.',
                    'Match production against available stock/coils where the workflow allows it.',
                    'Use production quantity updates carefully because they affect what is left to produce.',
                    'If a line should actually be bought in, use the Purchased checkbox to remove it from production and record activity.'
                ),
                'Documents' => array(
                    'Production cards are normally printed from the Order page Process Order modal.',
                    'Production CSV files are also saved from the Process Order modal.',
                    'Labels are based on packs from the order Pack tab, not directly from this page.'
                )
            )
        ),
        'admin_inventory' => array(
            'title' => 'Inventory Help',
            'subtitle' => 'Inventory controls the master list of parts, groups, units, pricing, raw materials, stock rows, and coil/finished settings used by orders, purchases, production, and reports.',
            'sections' => array(
                'Inventory Master Items' => array(
                    'Each inventory item has a part number, description, group, unit, pricing/rate fields, raw material setting, and production-related fields.',
                    'The inventory group is important. It tells the system whether an item behaves like coil, hardware, manufactured product, or another stock class.',
                    'The Finished flag identifies completed/finished stock categories for reporting.',
                    'Use Add Inventory Item to create new master items. Use Edit to update existing items.'
                ),
                'Stock Rows And Coils' => array(
                    'Stock rows sit under inventory master items and represent actual available stock, coil serials, received items, or quantities.',
                    'Coil rows can be open or closed. Closed coils are filtered in reports and production stock selection.',
                    'Purchase receiving can create inventory stock rows automatically.',
                    'Be careful editing stock rows because production, reports, and stock valuation depend on them.'
                ),
                'Fields That Matter To Other Pages' => array(
                    'Unit and metre/unit settings affect quantity calculations.',
                    'Weight unit affects pack weights and production summaries.',
                    'Raw material and group settings affect production queues and stock reports.',
                    'Buy rate and sell/rate fields affect report values and order pricing.'
                )
            )
        ),
        'admin_reports' => array(
            'title' => 'Reports Help',
            'subtitle' => 'Reports turn order, invoice, coil, stock, and inventory data into views for checking business performance and stock position.',
            'sections' => array(
                'Stock Report' => array(
                    'Use the Stock tab to review stock by inventory item and stock/coil row.',
                    'Group filter lets you narrow the report to classes such as Coil or Hardware.',
                    'Finished filter lets you show All, Finished, or Not Finished items from tblInventory.item_finished.',
                    'Coil Closed filter lets you show all coils/stock rows, only closed rows, or only open rows from tblInventoryItems.coil_finished.',
                    'The value column is calculated from quantity, metre/unit, and buy rate where possible.'
                ),
                'Invoice And Sales Reports' => array(
                    'Items Invoice reports are for invoiced item history and can be filtered by date/part where available.',
                    'Sales 12 Month shows sales trend information over the last year.',
                    'Use CSV export buttons where available when you need spreadsheet analysis.'
                ),
                'Coil Reports' => array(
                    'Coil 12 Month is for coil movement/reporting over time.',
                    'Closed Coils helps review coils that have been marked closed.',
                    'If a coil appears in the wrong report, check the inventory stock row and its coil closed/finished settings.'
                )
            )
        ),
        'admin_git_update' => array(
            'title' => 'Git Update Help',
            'subtitle' => 'Git Update deploys the latest approved app version from GitHub to the cPanel public_html folder.',
            'sections' => array(
                'Normal Deploy Flow' => array(
                    'Code changes are made locally, committed to Git, and pushed to GitHub first.',
                    'Open Git Update inside RevolveX after the GitHub push is complete.',
                    'Check Remote Status. If it says an update is available, click Download & Deploy.',
                    'The deployer downloads the GitHub ZIP and copies the app folder into public_html.',
                    'After deploy, refresh the target app page and test the changed area.'
                ),
                'What The Panels Mean' => array(
                    'Current Version shows the version currently deployed to the app.',
                    'Remote Status tells you whether GitHub has newer code.',
                    'Recent Changes lists recent commits so you can confirm what you are about to deploy.',
                    'Deploy Output shows diagnostics and any error messages if deployment fails.'
                ),
                'Safety Notes' => array(
                    'Use Git Update instead of direct FTP where possible so changes stay traceable.',
                    'If deployment fails, do not keep clicking blindly. Read the Deploy Output first.',
                    'If the Git Update page itself is broken, cPanel deploy may be needed to restore it.'
                )
            )
        ),
        'admin_company' => array(
            'title' => 'Company Setup Help',
            'subtitle' => 'Company Setup controls shared lists and configuration used throughout the app.',
            'sections' => array(
                'What Is Managed Here' => array(
                    'Company details and branding are used across documents, headers, emails, and business settings.',
                    'Users and groups control who can access the system and what actions they can perform.',
                    'Item Units and Item Groups control inventory classification and calculations.',
                    'Order Status and Purchase Status control workflow stages shown in lists and pages.',
                    'Source controls sales/source dropdown values on orders.'
                ),
                'Important Caution' => array(
                    'Do not delete or rename statuses without checking active orders and purchases first.',
                    'Do not remove inventory groups that are already used by inventory items.',
                    'Permission changes can affect who can delete records, update activity, or access deployment tools.',
                    'If a dropdown looks wrong elsewhere, check the matching setup list here.'
                )
            )
        )
    );

    if (isset($help[$pageKey])) {
        return $help[$pageKey];
    }

    return array(
        'title' => 'Page Help',
        'subtitle' => 'This page is part of the RevolveX workflow.',
        'sections' => array(
            'How To Use This Page' => array(
                'Use the page controls to search, filter, open records, and save changes.',
                'Important order and purchase changes should create activity records.',
                'If something looks wrong, check the related Activity tab or dashboard recent activity.',
                'If you are unsure what a button does, stop and check whether the action prints, emails, saves, deletes, receives, invoices, or changes status.'
            )
        )
    );
}

function renderPageHelpStyles() {
    ?>
    <style>
        .page-help-btn {
            border-radius: 999px;
            font-weight: 700;
            padding: 0.35rem 0.7rem;
        }
        .page-help-modal {
            z-index: 1065;
        }
        .page-help-modal .modal-content {
            border: 0;
            border-radius: 8px;
            box-shadow: 0 24px 70px rgba(12, 35, 60, 0.28);
        }
        .page-help-modal .modal-header {
            background: linear-gradient(135deg, #f8fbff 0%, #eef7f2 100%);
            border-bottom: 1px solid #dfe7f3;
        }
        .page-help-modal .modal-title {
            color: #0b3158;
            font-weight: 800;
        }
        .page-help-subtitle {
            color: #5d6a7d;
            font-size: 0.9rem;
            line-height: 1.4;
        }
        .page-help-section {
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            margin-bottom: 0.8rem;
            padding: 0.95rem 1rem;
        }
        .page-help-section h6 {
            color: #0b3158;
            font-weight: 800;
            margin-bottom: 0.55rem;
        }
        .page-help-section li {
            line-height: 1.45;
            margin-bottom: 0.45rem;
        }
    </style>
    <?php
}

function renderPageHelpButton() {
    renderPageHelpStyles();
    ?>
    <li class="nav-item me-2">
        <button type="button" class="btn btn-sm btn-outline-primary page-help-btn" data-bs-toggle="modal" data-bs-target="#pageHelpModal">
            <i class="bx bx-help-circle"></i> Help
        </button>
    </li>
    <?php
}

function renderPageHelpModal() {
    $help = pageHelpData(pageHelpCurrentKey());
    ?>
    <div class="modal fade page-help-modal" id="pageHelpModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <div>
                        <h5 class="modal-title"><?php echo htmlspecialchars($help['title']); ?></h5>
                        <div class="page-help-subtitle"><?php echo htmlspecialchars($help['subtitle']); ?></div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <?php foreach ($help['sections'] as $sectionTitle => $items) { ?>
                        <div class="page-help-section">
                            <h6><?php echo htmlspecialchars($sectionTitle); ?></h6>
                            <ul class="mb-0">
                                <?php foreach ($items as $item) { ?>
                                    <li><?php echo htmlspecialchars($item); ?></li>
                                <?php } ?>
                            </ul>
                        </div>
                    <?php } ?>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>
    <?php
}
?>
