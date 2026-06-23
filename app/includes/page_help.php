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
            'subtitle' => 'Use this page as the daily snapshot for orders, purchases, invoices, and recent activity.',
            'sections' => array(
                'Main Flow' => array(
                    'Check the order and quote cards first to see current workload.',
                    'Use recent orders to jump back into the newest active jobs.',
                    'Use recent activity to see who changed status, delivery dates, notes, emails, or process actions.',
                    'Invoice performance is cached for previous months and calculated live for the current month.'
                ),
                'What To Watch' => array(
                    'Orders or purchases with warning colours need attention before delivery or supplier deadlines.',
                    'Recent activity should show the logged-in user who performed the action.',
                    'Dashboard numbers are summaries; click into Orders, Purchases, or Reports to investigate detail.'
                )
            )
        ),
        'admin_quotes_list' => array(
            'title' => 'Quotes Help',
            'subtitle' => 'Quotes are the start of the customer workflow before an order is approved.',
            'sections' => array(
                'Quote Flow' => array(
                    'Create or open a quote, confirm customer and delivery details, then add items.',
                    'Use Process Quote to print/email the quote and record payment-required or payment-received steps.',
                    'Convert the quote to an order once the customer approves and any required payment is received.'
                ),
                'Tips' => array(
                    'Search by customer, address, order number, or notes.',
                    'Use the status filter if you need only Quote or Quoted records.',
                    'After conversion, continue from the order page and use Process Order.'
                )
            )
        ),
        'admin_orders_list' => array(
            'title' => 'Orders List Help',
            'subtitle' => 'This is the main dispatch view for active customer orders.',
            'sections' => array(
                'Order Flow' => array(
                    'Open an order to review customer, delivery, items, packs, activity, attachments, production cards, and invoice.',
                    'The item completion tally shows completed lines against total order lines.',
                    'If an order is not fully completed close to the delivery date, the status and date show red/bold.',
                    'Sort by created or delivery date to plan workload.'
                ),
                'Status Meaning' => array(
                    'Order means the job is active and not yet fully processed.',
                    'In Production means the order has been sent into the production workflow.',
                    'Awaiting Delivery/Collection means all order lines are complete and the order is ready for the next delivery step.'
                )
            )
        ),
        'admin_orders' => array(
            'title' => 'Order Page Help',
            'subtitle' => 'This page controls the full customer order workflow from details through production and delivery.',
            'sections' => array(
                'Home Tab' => array(
                    'Check customer details, sales user, order number, status, delivery date, address, and notes.',
                    'Save customer/details changes to record activity such as status, delivery, or note updates.',
                    'Use Print and Email dropdowns for customer documents.'
                ),
                'Items And Purchasing' => array(
                    'Add manufactured and purchased items in the Items tab.',
                    'Tag purchased items, then Copy Tagged Items To PO to create a supplier purchase order.',
                    'Linked purchased items are excluded from production and can complete automatically when the PO is received.',
                    'Use the Done checkbox to manually mark an order line complete when production or purchasing is confirmed.'
                ),
                'Process Order' => array(
                    'Use Process Order as the checklist once the order is ready.',
                    'Email or print order confirmation, print labels, save production CSV files, print production cards, and print delivery docket.',
                    'Labels need packs first. Production cards print manufactured items only.',
                    'Process moves the order to In Production and records the action.'
                ),
                'Completion' => array(
                    'The order tally shows completed items against total items.',
                    'When all items are complete the order can move to Awaiting Delivery/Collection.',
                    'Orders due soon but not complete are highlighted red in the order header and order list.'
                )
            )
        ),
        'admin_purchasing_list' => array(
            'title' => 'Purchases List Help',
            'subtitle' => 'Use this page to track supplier purchase orders and supplier confirmations.',
            'sections' => array(
                'Purchase Flow' => array(
                    'Open a purchase order to review vendor details, required date, ETA, internal notes, receive items, invoice, and activity.',
                    'Default list filtering hides invoiced/closed items until you search.',
                    'Use status colours to spot Draft, Ordered, Confirmed, Overdue, Received, and Invoiced POs.'
                ),
                'Confirmation Tracking' => array(
                    'Confirmation Required means the supplier must send confirmation.',
                    'If confirmation is not received within 48 hours, the PO is flagged.',
                    'Upload confirmation files and ETA in the Process Purchase modal.'
                )
            )
        ),
        'admin_purchasing' => array(
            'title' => 'Purchase Order Help',
            'subtitle' => 'This page manages supplier purchase orders, confirmations, receiving, and invoice conversion.',
            'sections' => array(
                'Process Purchase' => array(
                    'Print delivery docket, attach supplier files, request confirmation, upload received confirmation, and print/email the PO.',
                    'Email can include selected extra documents.',
                    'Uploaded confirmation documents are saved against the PO and shown in Activity documents.'
                ),
                'Receiving' => array(
                    'Use Receive Items when supplier stock arrives.',
                    'Receiving creates inventory rows and marks linked customer order lines complete.',
                    'Reverse Receive removes those inventory rows and reopens linked customer order lines.'
                ),
                'Invoice Flow' => array(
                    'Convert the purchase order to bill/invoice once supplier paperwork is ready.',
                    'Purchasing activity records who changed status, dates, confirmations, receives, and invoice steps.'
                )
            )
        ),
        'admin_production' => array(
            'title' => 'Production Help',
            'subtitle' => 'Production shows manufactured order items that still need production work.',
            'sections' => array(
                'Production Flow' => array(
                    'Only manufactured items appear here; purchased items are filtered out.',
                    'Match production against stock/coils and update quantities as items are produced.',
                    'Use the Purchased checkbox only when an item should be removed from production and handled through purchasing.'
                ),
                'Outputs' => array(
                    'Production cards and CSV files are generated from the order page process modal.',
                    'Pack/label work happens from the order Pack tab and Process Order modal.'
                )
            )
        ),
        'admin_inventory' => array(
            'title' => 'Inventory Help',
            'subtitle' => 'Inventory stores parts, raw material, coils, hardware, finished flags, stock rows, and pricing fields.',
            'sections' => array(
                'Inventory Flow' => array(
                    'Use groups to classify items such as Coil, Hardware, manufactured product, or other stock classes.',
                    'Use Finished to identify completed/finished stock items.',
                    'Open an item to edit part details, pricing, units, raw material, sub-items, and stock rows.'
                ),
                'Stock And Production' => array(
                    'Production and reports depend on inventory group, unit, metre unit, weight unit, and raw material settings.',
                    'Coil stock rows can be open or closed, which feeds the Stock Report and Closed Coils reporting.'
                )
            )
        ),
        'admin_reports' => array(
            'title' => 'Reports Help',
            'subtitle' => 'Reports provide stock, coil, sales, invoice, and export views.',
            'sections' => array(
                'Stock Report' => array(
                    'Filter by inventory group, Finished / Not Finished, and Coil Closed / Open.',
                    'Use this to review available stock and coil value by part and serial number.'
                ),
                'Other Reports' => array(
                    'Items Invoice and Sales reports focus on invoiced sales history.',
                    'Coil reports help review coil movement and closed coils.',
                    'CSV buttons export the selected report data where available.'
                )
            )
        ),
        'admin_git_update' => array(
            'title' => 'Git Update Help',
            'subtitle' => 'Use this page to deploy the latest approved GitHub version to cPanel.',
            'sections' => array(
                'Deploy Flow' => array(
                    'Local changes are committed and pushed to GitHub first.',
                    'Git Update downloads the latest GitHub ZIP and copies the app folder to public_html.',
                    'The page shows current version, remote status, recent commits, and deploy output.'
                ),
                'Safety' => array(
                    'Use this instead of direct FTP when possible so changes stay in history.',
                    'If deployment fails, read the Deploy Output panel before retrying.'
                )
            )
        ),
        'admin_company' => array(
            'title' => 'Company Setup Help',
            'subtitle' => 'Company setup controls users, groups, business details, and configuration lists.',
            'sections' => array(
                'Setup Flow' => array(
                    'Manage users, access groups, company details, item units, item groups, order statuses, purchase statuses, and sources.',
                    'Changes here affect dropdowns and workflows across orders, purchases, inventory, and reports.'
                ),
                'Caution' => array(
                    'Do not remove statuses or groups currently used by orders, purchases, or inventory.',
                    'Permission and deletion settings should be changed carefully.'
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
            'General Flow' => array(
                'Use the page controls to search, filter, open records, and save changes.',
                'Important order and purchase changes should create activity records.',
                'If something looks wrong, check the related Activity tab or dashboard recent activity.'
            )
        )
    );
}

function renderPageHelpButtonAndModal() {
    $help = pageHelpData(pageHelpCurrentKey());
    ?>
    <style>
        .page-help-btn {
            border-radius: 999px;
            font-weight: 700;
            padding: 0.35rem 0.7rem;
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
            margin-bottom: 0.35rem;
        }
    </style>
    <li class="nav-item me-2">
        <button type="button" class="btn btn-sm btn-outline-primary page-help-btn" data-bs-toggle="modal" data-bs-target="#pageHelpModal">
            <i class="bx bx-help-circle"></i> Help
        </button>
    </li>
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
