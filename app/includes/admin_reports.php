<style>
.reports-shell {
    background: linear-gradient(180deg, #f8fbff 0%, #ffffff 48%);
    border: 0;
    border-radius: 8px;
    box-shadow: 0 12px 28px rgba(33, 37, 41, 0.08);
}
.reports-titlebar {
    border-bottom: 1px solid #e7edf5;
    padding-bottom: 0.9rem;
}
.reports-title {
    color: #0b3158;
    font-size: 1.2rem;
    font-weight: 800;
    margin: 0;
}
.reports-subtitle {
    color: #6c757d;
    font-size: 0.78rem;
    margin-top: 0.18rem;
}
.reports-tabs {
    border-bottom: 0;
    gap: 0.35rem;
    margin-top: 1rem;
}
.reports-tabs .nav-link {
    background: #fff;
    border: 1px solid #e1e8f0;
    border-radius: 8px;
    color: #546070;
    font-size: 0.85rem;
    font-weight: 750;
    padding: 0.55rem 0.75rem;
}
.reports-tabs .nav-link.active,
.reports-tabs .nav-link:hover {
    background: #0b3158;
    border-color: #0b3158;
    color: #fff;
}
.reports-tabs .nav-link i {
    margin-right: 0.25rem;
}
#body_content {
    margin-top: 1rem;
}
.reports-shell #body_content > .card,
.reports-shell .report-panel,
.reports-shell #body_content .card {
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    box-shadow: 0 8px 18px rgba(33, 37, 41, 0.05);
    overflow: hidden;
}
.reports-shell .card-header {
    background: #f6f8fb;
    border-bottom: 1px solid #e2e8f0;
    color: #0b3158;
    font-weight: 800;
}
.report-filter-bar {
    background: #fff;
    border: 1px solid #e1e8f0;
    border-radius: 8px;
    box-shadow: 0 8px 18px rgba(33, 37, 41, 0.05);
    margin-bottom: 1rem;
    padding: 0.85rem;
}
.reports-shell .form-control,
.reports-shell .form-select {
    border-color: #d5dee9;
    font-size: 0.9rem;
}
.reports-shell .form-control:focus,
.reports-shell .form-select:focus {
    border-color: #3f80ea;
    box-shadow: 0 0 0 0.15rem rgba(63, 128, 234, 0.12);
}
.reports-shell .btn-primary,
.reports-shell .btn-secondary {
    background: #0b3158;
    border-color: #0b3158;
    color: #fff;
}
.reports-shell .btn-primary:hover,
.reports-shell .btn-secondary:hover {
    background: #154a80;
    border-color: #154a80;
    color: #fff;
}
.reports-shell .table {
    margin-bottom: 0;
}
.reports-shell .table thead th {
    background: #f6f8fb;
    border-bottom: 1px solid #dbe3ed;
    color: #546070;
    font-size: 0.74rem;
    font-weight: 800;
    letter-spacing: 0.02em;
    padding: 0.7rem 0.8rem;
    text-transform: uppercase;
}
.reports-shell .table tbody td {
    color: #1f2d3d;
    font-size: 0.86rem;
    padding: 0.65rem 0.8rem;
    vertical-align: middle;
}
.reports-shell .table tbody tr:hover td {
    background: #f7faff;
}
.reports-shell .table tfoot th {
    background: #eef4ff;
    color: #0b3158;
    font-size: 0.86rem;
    padding: 0.75rem 0.8rem;
}
.report-empty {
    color: #6c757d;
    padding: 1rem;
}
</style>

<main id="main" class="main">
    <div class="card reports-shell">
        <div class="card-body">
            <div class="reports-titlebar d-flex justify-content-between align-items-center">
                <div>
                    <h5 class="reports-title"><i class="bx bx-line-chart"></i> Reports</h5>
                    <div class="reports-subtitle">Review sales, coils, stock, and export business data.</div>
                </div>
            </div>

    <div class="d-none d-lg-block">
        <ul class="nav reports-tabs" id="myTab" role="tablist">
            <li class="nav-item" role="presentation">
                <a class="nav-link active" id="item_report-tab" data-bs-toggle="tab" onclick="Loadtab('item_report')" role="tab" aria-controls="item_report" aria-selected="true">
                    <i class="bx bx-receipt"></i> Items Invoice
                </a>
            </li>
            <li class="nav-item" role="presentation">
                <a class="nav-link" id="sales_report-tab" data-bs-toggle="tab" onclick="SalesReport()" role="tab" aria-controls="sales_report" aria-selected="false">
                    <i class="bx bx-trending-up"></i> Sales 12 Month
                </a>
            </li>
            <li class="nav-item" role="presentation">
                <a class="nav-link" id="coil_report-tab" data-bs-toggle="tab" onclick="CoilReport()" role="tab" aria-controls="coil_report" aria-selected="false">
                    <i class="bx bx-cylinder"></i> Coil 12 Month
                </a>
            </li>
            <li class="nav-item" role="presentation">
                <a class="nav-link" id="stock_report-tab" data-bs-toggle="tab" onclick="StockReport()" role="tab" aria-controls="stock_report" aria-selected="false">
                    <i class="bx bx-package"></i> Stock
                </a>
            </li>
            <li class="nav-item" role="presentation">
                <a class="nav-link" id="closed_coil_report-tab" data-bs-toggle="tab" onclick="ClosedCoilReport()" role="tab" aria-controls="closed_coil_report" aria-selected="false">
                    <i class="bx bx-check-circle"></i> Closed Coils
                </a>
            </li>
        </ul>
    </div>

    <div id="body_content"></div>
        </div>
    </div>
</main>

<script type="text/javascript" src="includes_pages/admin_reports/scripts.js?n=<?php echo date('h:i:s'); ?>"></script>
