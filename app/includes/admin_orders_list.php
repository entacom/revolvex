<!-- ======= BEGIN AMENDED BLOCK: main HTML ======= -->
<script type="text/javascript" src="includes_pages/admin_orders_list/script.js?n=<?php echo date('h:i'); ?>"></script>
<?php

$database = new Database();
$conn = $database->connect();	
$selectedOrderStatusId = isset($_GET['order_status_id']) ? (int)$_GET['order_status_id'] : 0;
$statusOptions = [];
try {
    $stmt = $conn->prepare("SELECT id, description FROM tblOrderStatus WHERE active = 1 ORDER BY description");
    $stmt->execute();
    $statusOptions = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $statusOptions = [];
}
?>
<style>
.orders-list-shell {
    background: linear-gradient(180deg, #f8fbff 0%, #ffffff 48%);
    border: 0;
    border-radius: 8px;
    box-shadow: 0 12px 28px rgba(33, 37, 41, 0.08);
}
.orders-list-titlebar {
    border-bottom: 1px solid #e7edf5;
    padding-bottom: 0.75rem;
}
.orders-list-title {
    color: #0b3158;
    font-size: 1.15rem;
    font-weight: 800;
    margin: 0;
}
.orders-list-subtitle {
    color: #6c757d;
    font-size: 0.78rem;
    margin-top: 0.15rem;
}
.orders-filter-bar {
    background: #ffffff;
    border: 1px solid #e1e8f0;
    border-radius: 8px;
    box-shadow: 0 8px 18px rgba(33, 37, 41, 0.05);
    padding: 0.85rem;
}
.orders-filter-bar .form-control,
.orders-filter-bar .form-select {
    border-color: #d5dee9;
    font-size: 0.92rem;
}
.orders-filter-bar .form-control:focus,
.orders-filter-bar .form-select:focus {
    border-color: #3f80ea;
    box-shadow: 0 0 0 0.15rem rgba(63, 128, 234, 0.12);
}
.orders-primary-action {
    background: #0b3158;
    border-color: #0b3158;
    color: #ffffff;
}
.orders-primary-action:hover {
    background: #154a80;
    border-color: #154a80;
    color: #ffffff;
}
</style>
<main id="main" class="main">
    <div class="card orders-list-shell">
        <div class="card-body">

            <!-- Styled Tabs with Boxicons and Text -->
            <div class="orders-list-titlebar d-flex justify-content-between align-items-center">
                <div>
                    <h5 class="orders-list-title"><i class="bx bx-list-ul"></i> <span id="tabTitleText">Orders</span></h5>
                    <div class="orders-list-subtitle">Search, filter, sort, and open active jobs.</div>
                </div>
            </div>

            <!-- Controls are now persistent on the page (not inside AJAX content) -->
            <div class="orders-filter-bar mt-3">
                <div class="row align-items-center g-2">
                    <div class="col-12 col-md-6 col-lg-5">
                        <input type="text" id="customerSearch" onkeypress="handleKeyPress(event)" class="form-control" placeholder="Search by Customer, Address, Order# Notes...">
                    </div>
                    <div class="col-auto">
                        <button type="button" class="btn btn-outline-secondary" onclick="resetSearch()">Reset</button>
                    </div>
                    <div class="col-auto">
                        <select id="orderStatusFilter" class="form-select" onchange="filterByStatus()">
                            <option value="">Filter</option>
                            <?php foreach ($statusOptions as $opt): ?>
                                <option value="<?php echo (int)$opt['id']; ?>" <?php echo ((int)$opt['id'] === $selectedOrderStatusId) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($opt['description']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-auto">
                        <button type="button" id="addNewLeadBtn" class="btn orders-primary-action">Add New Order</button>
                    </div>
                </div>
            </div>

            <!-- AJAX injects only the table + pagination here -->
            <div id="tab_body" class="mt-3"></div>
        </div>
    </div>
</main>
<!-- ======= END AMENDED BLOCK: main HTML ======= -->
