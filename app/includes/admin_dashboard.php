<?php
$dashboardStatusCounts = array(
    'Quote' => 0,
    'Quoted' => 0,
    'Order' => 0
);
$dashboardStatusIds = array(
    'Quote' => '',
    'Quoted' => '',
    'Order' => ''
);
$purchaseStatusCounts = array(
    'Order' => 0
);
$purchaseStatusIds = array(
    'Order' => ''
);

try {
    $database = new Database();
    $conn = $database->connect();
    $statusQuery = "
        SELECT os.id, os.description, COUNT(o.order_id) AS order_count
        FROM tblOrderStatus os
        LEFT JOIN tblOrders o
            ON o.order_status_id = os.id
            AND o.company_id = :order_company_id
        WHERE os.company_id = :status_company_id
            AND LOWER(os.description) IN ('quote', 'quoted', 'order')
        GROUP BY os.id, os.description
    ";
    $statusStmt = $conn->prepare($statusQuery);
    $statusStmt->bindValue(':order_company_id', $_SESSION['session_company_id'], PDO::PARAM_INT);
    $statusStmt->bindValue(':status_company_id', $_SESSION['session_company_id'], PDO::PARAM_INT);
    $statusStmt->execute();

    while ($statusRow = $statusStmt->fetch(PDO::FETCH_ASSOC)) {
        $statusName = strtolower(trim($statusRow['description']));
        if ($statusName === 'quote') {
            $dashboardStatusCounts['Quote'] = (int)$statusRow['order_count'];
            $dashboardStatusIds['Quote'] = (int)$statusRow['id'];
        }
        if ($statusName === 'quoted') {
            $dashboardStatusCounts['Quoted'] = (int)$statusRow['order_count'];
            $dashboardStatusIds['Quoted'] = (int)$statusRow['id'];
        }
        if ($statusName === 'order') {
            $dashboardStatusCounts['Order'] = (int)$statusRow['order_count'];
            $dashboardStatusIds['Order'] = (int)$statusRow['id'];
        }
    }

    $purchaseStatusQuery = "
        SELECT ps.id, ps.description, COUNT(po.id) AS order_count
        FROM tblPurchaseStatus ps
        LEFT JOIN tblPurchaseOrders po
            ON po.order_status_id = ps.id
            AND po.company_id = :purchase_company_id
        WHERE ps.company_id = :purchase_status_company_id
            AND LOWER(ps.description) = 'order'
        GROUP BY ps.id, ps.description
    ";
    $purchaseStatusStmt = $conn->prepare($purchaseStatusQuery);
    $purchaseStatusStmt->bindValue(':purchase_company_id', $_SESSION['session_company_id'], PDO::PARAM_INT);
    $purchaseStatusStmt->bindValue(':purchase_status_company_id', $_SESSION['session_company_id'], PDO::PARAM_INT);
    $purchaseStatusStmt->execute();

    while ($purchaseStatusRow = $purchaseStatusStmt->fetch(PDO::FETCH_ASSOC)) {
        $purchaseStatusCounts['Order'] = (int)$purchaseStatusRow['order_count'];
        $purchaseStatusIds['Order'] = (int)$purchaseStatusRow['id'];
    }
} catch (Exception $e) {
    error_log('Dashboard status count error: ' . $e->getMessage());
}

$quoteListUrl = '?p=admin_orders_list&order_status_id=' . urlencode($dashboardStatusIds['Quote']);
$quotedListUrl = '?p=admin_orders_list&order_status_id=' . urlencode($dashboardStatusIds['Quoted']);
$orderListUrl = '?p=admin_orders_list&order_status_id=' . urlencode($dashboardStatusIds['Order']);
$purchaseOrderListUrl = '?p=admin_purchasing_list&order_status_id=' . urlencode($purchaseStatusIds['Order']);
?>
<style>
    .dashboard-status-card {
        border: 0;
        border-radius: 8px;
        overflow: hidden;
        position: relative;
        box-shadow: 0 8px 20px rgba(33, 37, 41, 0.08);
        transition: transform 0.15s ease, box-shadow 0.15s ease;
    }

    .dashboard-status-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 14px 28px rgba(33, 37, 41, 0.14);
    }

    .dashboard-status-card::before {
        content: "";
        position: absolute;
        inset: 0 0 auto 0;
        height: 4px;
        background: var(--status-accent);
    }

    .dashboard-status-card .card-body {
        min-height: 132px;
    }

    .dashboard-status-card .card-title {
        padding: 18px 0 10px 0;
    }

    .dashboard-status-card h6 {
        font-size: 2rem;
        line-height: 1;
    }

    .dashboard-status-card .card-icon {
        width: 52px;
        height: 52px;
        background: var(--status-soft);
        color: var(--status-accent);
    }

    .dashboard-status-card .status-card-link-text {
        color: #6c757d;
        font-size: 0.78rem;
        font-weight: 600;
        text-transform: uppercase;
    }

    .dashboard-status-card.status-quote {
        --status-accent: #4154f1;
        --status-soft: #eef0ff;
    }

    .dashboard-status-card.status-quoted {
        --status-accent: #2eca6a;
        --status-soft: #e8f8ef;
    }

    .dashboard-status-card.status-order {
        --status-accent: #ff771d;
        --status-soft: #fff0e6;
    }

    .dashboard-status-card.status-purchase {
        --status-accent: #6f42c1;
        --status-soft: #f1ebfb;
    }

    .dashboard-finance-card {
        border: 0;
        border-radius: 8px;
        box-shadow: 0 8px 20px rgba(33, 37, 41, 0.08);
        overflow: hidden;
        position: relative;
    }

    .dashboard-finance-card::before {
        background: linear-gradient(90deg, #4154f1, #2eca6a);
        content: "";
        height: 4px;
        inset: 0 0 auto 0;
        position: absolute;
    }

    .finance-card-badge {
        background: #eef0ff;
        border-radius: 999px;
        color: #4154f1;
        font-size: 0.72rem;
        font-weight: 800;
        padding: 0.22rem 0.65rem;
        text-transform: uppercase;
    }

    .finance-metric {
        background: #f8f9fc;
        border: 1px solid #edf0f7;
        border-radius: 8px;
        padding: 0.75rem;
        min-height: 96px;
    }

    .finance-metric-label {
        color: #8a94a6;
        font-size: 0.68rem;
        font-weight: 800;
        letter-spacing: 0.02em;
        text-transform: uppercase;
    }

    .finance-metric-value {
        color: #263238;
        font-size: 1.35rem;
        font-weight: 800;
        line-height: 1.2;
        margin-top: 0.25rem;
    }

    .finance-metric-help {
        color: #6c757d;
        font-size: 0.76rem;
        margin-top: 0.2rem;
    }

    .dashboard-activity-card {
        border: 0;
        border-radius: 8px;
        box-shadow: 0 8px 20px rgba(33, 37, 41, 0.08);
        overflow: hidden;
    }

    .dashboard-activity-card .card-body {
        padding-bottom: 1.1rem;
    }

    .activity-count-badge {
        background: #eef0ff;
        border-radius: 999px;
        color: #4154f1;
        font-size: 0.75rem;
        font-weight: 800;
        padding: 0.22rem 0.62rem;
    }

    .dashboard-activity-list {
        display: grid;
        gap: 0.7rem;
        margin-top: 1rem;
    }

    .dashboard-activity-item {
        align-items: flex-start;
        background: #fff;
        border: 1px solid #edf0f7;
        border-left: 4px solid var(--activity-accent);
        border-radius: 8px;
        color: inherit;
        display: flex;
        gap: 0.75rem;
        padding: 0.82rem;
        text-decoration: none;
        transition: transform 0.15s ease, box-shadow 0.15s ease, border-color 0.15s ease;
    }

    .dashboard-activity-item:hover {
        box-shadow: 0 12px 24px rgba(33, 37, 41, 0.12);
        color: inherit;
        transform: translateY(-1px);
    }

    .dashboard-activity-item.activity-status {
        --activity-accent: #4154f1;
        --activity-soft: #eef0ff;
    }

    .dashboard-activity-item.activity-delivery {
        --activity-accent: #ff771d;
        --activity-soft: #fff0e6;
    }

    .dashboard-activity-item.activity-invoice {
        --activity-accent: #2eca6a;
        --activity-soft: #e8f8ef;
    }

    .dashboard-activity-item.activity-customer {
        --activity-accent: #6f42c1;
        --activity-soft: #f1ebfb;
    }

    .dashboard-activity-item.activity-general {
        --activity-accent: #6c757d;
        --activity-soft: #f1f3f5;
    }

    .activity-icon {
        align-items: center;
        background: var(--activity-soft);
        border-radius: 8px;
        color: var(--activity-accent);
        display: flex;
        flex: 0 0 38px;
        height: 38px;
        justify-content: center;
        width: 38px;
    }

    .activity-main {
        min-width: 0;
        width: 100%;
    }

    .activity-topline {
        align-items: center;
        display: flex;
        gap: 0.5rem;
        justify-content: space-between;
        margin-bottom: 0.28rem;
    }

    .activity-time {
        color: #8a94a6;
        font-size: 0.72rem;
        font-weight: 800;
        text-transform: uppercase;
    }

    .activity-status-pill {
        background: #f8f9fc;
        border: 1px solid #edf0f7;
        border-radius: 999px;
        color: #52606d;
        font-size: 0.68rem;
        font-weight: 800;
        max-width: 112px;
        overflow: hidden;
        padding: 0.12rem 0.5rem;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .activity-description {
        color: #263238;
        font-size: 0.88rem;
        font-weight: 800;
        line-height: 1.28;
    }

    .activity-order {
        color: #52606d;
        font-size: 0.78rem;
        font-weight: 700;
        margin-top: 0.25rem;
        overflow-wrap: anywhere;
    }

    .activity-order span {
        color: #8a94a6;
        font-weight: 700;
    }

    .activity-meta {
        color: #6c757d;
        display: flex;
        flex-wrap: wrap;
        font-size: 0.72rem;
        gap: 0.45rem 0.7rem;
        line-height: 1.35;
        margin-top: 0.45rem;
    }

    .activity-meta i {
        color: var(--activity-accent);
        font-size: 0.76rem;
    }
</style>
<main id="main" class="main">
    <div class="pagetitle">
        <h1>ADMIN</h1>
        <nav>
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="index.html">Home</a></li>
                <li class="breadcrumb-item active">Dashboard</li>
            </ol>
        </nav>
    </div>
    <section class="section dashboard">
        <div class="row">
            <div class="col-xxl-3 col-md-6">
                <a href="<?php echo htmlspecialchars($quoteListUrl); ?>" class="text-decoration-none">
                <div class="card info-card dashboard-status-card status-quote">
                    <div class="card-body">
                        <h5 class="card-title">Quotes <span>| Current</span></h5>
                        <div class="d-flex align-items-center">
                            <div class="card-icon rounded-circle d-flex align-items-center justify-content-center">
                                <i class="bi bi-file-earmark-text"></i>
                            </div>
                            <div class="ps-3">
                                <h6><?php echo number_format($dashboardStatusCounts['Quote']); ?></h6>
                                <span class="status-card-link-text">Open quotes</span>
                            </div>
                        </div>
                    </div>
                </div>
                </a>
            </div>
            <div class="col-xxl-3 col-md-6">
                <a href="<?php echo htmlspecialchars($quotedListUrl); ?>" class="text-decoration-none">
                <div class="card info-card dashboard-status-card status-quoted">
                    <div class="card-body">
                        <h5 class="card-title">Quoted <span>| Current</span></h5>
                        <div class="d-flex align-items-center">
                            <div class="card-icon rounded-circle d-flex align-items-center justify-content-center">
                                <i class="bi bi-send-check"></i>
                            </div>
                            <div class="ps-3">
                                <h6><?php echo number_format($dashboardStatusCounts['Quoted']); ?></h6>
                                <span class="status-card-link-text">Sent quotes</span>
                            </div>
                        </div>
                    </div>
                </div>
                </a>
            </div>
            <div class="col-xxl-3 col-md-6">
                <a href="<?php echo htmlspecialchars($orderListUrl); ?>" class="text-decoration-none">
                <div class="card info-card dashboard-status-card status-order">
                    <div class="card-body">
                        <h5 class="card-title">Orders <span>| Current</span></h5>
                        <div class="d-flex align-items-center">
                            <div class="card-icon rounded-circle d-flex align-items-center justify-content-center">
                                <i class="bi bi-clipboard-check"></i>
                            </div>
                            <div class="ps-3">
                                <h6><?php echo number_format($dashboardStatusCounts['Order']); ?></h6>
                                <span class="status-card-link-text">Sales orders</span>
                            </div>
                        </div>
                    </div>
                </div>
                </a>
            </div>
            <div class="col-xxl-3 col-md-6">
                <a href="<?php echo htmlspecialchars($purchaseOrderListUrl); ?>" class="text-decoration-none">
                <div class="card info-card dashboard-status-card status-purchase">
                    <div class="card-body">
                        <h5 class="card-title">Purchase Orders <span>| Current</span></h5>
                        <div class="d-flex align-items-center">
                            <div class="card-icon rounded-circle d-flex align-items-center justify-content-center">
                                <i class="bi bi-cart-check"></i>
                            </div>
                            <div class="ps-3">
                                <h6><?php echo number_format($purchaseStatusCounts['Order']); ?></h6>
                                <span class="status-card-link-text">Supplier orders</span>
                            </div>
                        </div>
                    </div>
                </div>
                </a>
            </div>
            <div class="col-lg-8">
                <div class="row">
                    <div class="col-12">
                        <div class="card recent-sales overflow-auto">
                            <div class="filter">
                                <a class="icon" href="#" data-bs-toggle="dropdown"><i class="bi bi-three-dots"></i></a>
                                <ul class="dropdown-menu dropdown-menu-end dropdown-menu-arrow">
                                    <li class="dropdown-header text-start"><h6>Filter</h6></li>
                                    <li><a class="dropdown-item" href="#">Today</a></li>
                                    <li><a class="dropdown-item" href="#">This Month</a></li>
                                    <li><a class="dropdown-item" href="#">This Year</a></li>
                                </ul>
                            </div>
                            <div class="card-body">
                                <h5 class="card-title">Recent Orders <span>| Last 3</span></h5>
                                <div id="recent_order_table"></div>
                            </div>
                        </div>
                    </div>
                    <div class="col-12">
                        <div class="card dashboard-finance-card">
                            <div class="card-body">
                                <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                                    <h5 class="card-title mb-0">Invoice Performance <span>| Last 12 months</span></h5>
                                    <span class="finance-card-badge">Inc GST</span>
                                </div>
                                <div class="row g-2 my-3">
                                    <div class="col-md-4">
                                        <div class="finance-metric">
                                            <div class="finance-metric-label">Invoiced Value</div>
                                            <div class="finance-metric-value" id="invoice_total_value">$0.00</div>
                                            <div class="finance-metric-help">Last 12 months</div>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="finance-metric">
                                            <div class="finance-metric-label">Invoices</div>
                                            <div class="finance-metric-value" id="invoice_total_count">0</div>
                                            <div class="finance-metric-help">Orders invoiced</div>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="finance-metric">
                                            <div class="finance-metric-label">Average Invoice</div>
                                            <div class="finance-metric-value" id="invoice_average_value">$0.00</div>
                                            <div class="finance-metric-help"><span id="invoice_current_month">0</span> this month</div>
                                        </div>
                                    </div>
                                </div>
                                <div id="invoicePerformanceChart"></div>
                                <script>
                                    $(document).ready(function() {
                                        $.ajax({
                                            url: '/includes_pages/admin_dashboard/crud.php',
                                            type: 'POST',
                                            contentType: 'application/json',
                                            dataType: 'json',
                                            data: JSON.stringify({ action: 'read_invoice_performance' }),
                                            success: function(response) {
                                                if (response.error) {
                                                    console.error('Error from server:', response.error);
                                                    return;
                                                }

                                                function money(value) {
                                                    return new Intl.NumberFormat('en-AU', {
                                                        style: 'currency',
                                                        currency: 'AUD'
                                                    }).format(Number(value || 0));
                                                }

                                                $('#invoice_total_value').text(money(response.summary.total_value));
                                                $('#invoice_total_count').text(response.summary.total_invoices || 0);
                                                $('#invoice_average_value').text(money(response.summary.average_invoice_value));
                                                $('#invoice_current_month').text(money(response.summary.current_month_value) + ' / ' + (response.summary.current_month_count || 0) + ' invoices');

                                                new ApexCharts(document.querySelector("#invoicePerformanceChart"), {
                                                    series: [
                                                        {
                                                            name: 'Invoiced value',
                                                            type: 'column',
                                                            data: response.invoice_values
                                                        },
                                                        {
                                                            name: 'Invoice count',
                                                            type: 'line',
                                                            data: response.invoice_counts
                                                        }
                                                    ],
                                                    chart: {
                                                        height: 350,
                                                        type: 'line',
                                                        toolbar: { show: false }
                                                    },
                                                    colors: ['#4154f1', '#2eca6a'],
                                                    stroke: {
                                                        width: [0, 3],
                                                        curve: 'smooth'
                                                    },
                                                    plotOptions: {
                                                        bar: {
                                                            borderRadius: 4,
                                                            columnWidth: '48%'
                                                        }
                                                    },
                                                    dataLabels: { enabled: false },
                                                    xaxis: {
                                                        categories: response.categories
                                                    },
                                                    yaxis: [
                                                        {
                                                            labels: {
                                                                formatter: function(value) {
                                                                    return '$' + Math.round(value / 1000) + 'k';
                                                                }
                                                            },
                                                            title: { text: 'Value' }
                                                        },
                                                        {
                                                            opposite: true,
                                                            labels: {
                                                                formatter: function(value) {
                                                                    return Math.round(value);
                                                                }
                                                            },
                                                            title: { text: 'Count' }
                                                        }
                                                    ],
                                                    tooltip: {
                                                        shared: true,
                                                        y: [
                                                            {
                                                                formatter: function(value) {
                                                                    return money(value);
                                                                }
                                                            },
                                                            {
                                                                formatter: function(value) {
                                                                    return Math.round(value) + ' invoices';
                                                                }
                                                            }
                                                        ]
                                                    },
                                                    legend: {
                                                        position: 'top',
                                                        horizontalAlign: 'right'
                                                    }
                                                }).render();
                                            },
                                            error: function(xhr, status, error) {
                                                console.error('There was a problem with the AJAX request:', error);
                                                console.error('Response text:', xhr.responseText);
                                            }
                                        });
                                    });
                                </script>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-4">
                <div id="recent_activity_table"></div>
                <div class="card">
                    <div class="card-body pb-0">
                        <h5 class="card-title">Orders Source</h5>
                        <div id="trafficChart" style="min-height: 400px;" class="echart"></div>
                        <script>
                            document.addEventListener("DOMContentLoaded", () => {
                                $.ajax({
                                    url: '/includes_pages/admin_dashboard/crud.php',
                                    type: 'POST',
                                    contentType: 'application/json',
                                    dataType: 'json',
                                    data: JSON.stringify({ action: 'read_orders_source' }),
                                    success: function(response) {
                                        console.log('Raw response:', response);

                                        if (response.error) {
                                            console.error('Error from server:', response.error);
                                            return;
                                        }

                                        var source_data = response.source_counts;

                                        echarts.init(document.querySelector("#trafficChart")).setOption({
                                            tooltip: { trigger: 'item' },
                                            legend: {
                                                top: '5%',
                                                left: 'center'
                                            },
                                            series: [{
                                                name: 'Access From',
                                                type: 'pie',
                                                radius: ['40%', '70%'],
                                                avoidLabelOverlap: false,
                                                label: { show: false, position: 'center' },
                                                emphasis: {
                                                    label: {
                                                        show: true,
                                                        fontSize: '18',
                                                        fontWeight: 'bold'
                                                    }
                                                },
                                                labelLine: { show: false },
                                                data: source_data
                                            }]
                                        });
                                    },
                                    error: function(xhr, status, error) {
                                        console.error('There was a problem with the AJAX request:', error);
                                        console.error('Response text:', xhr.responseText);
                                    }
                                });
                            });
                        </script>
                    </div>
                </div>
            </div>
        </div>
    </section>
</main>
<script type="text/javascript" src="includes_pages/admin_dashboard/scripts.js?n=<?php echo date('h:i'); ?>"></script>
