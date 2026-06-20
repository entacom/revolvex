<style>
.purchase-list-shell {
    background: linear-gradient(180deg, #f8fbff 0%, #ffffff 48%);
    border: 0;
    border-radius: 8px;
    box-shadow: 0 12px 28px rgba(33, 37, 41, 0.08);
}
.purchase-list-titlebar {
    border-bottom: 1px solid #e7edf5;
    padding-bottom: 0.75rem;
}
.purchase-list-title {
    color: #0b3158;
    font-size: 1.15rem;
    font-weight: 800;
    margin: 0;
}
.purchase-list-subtitle {
    color: #6c757d;
    font-size: 0.78rem;
    margin-top: 0.15rem;
}
</style>

<script type="text/javascript" src="includes_pages/admin_purchasing_list/script.js?n=<? echo date('h:i');?>"></script>
<main id="main" class="main">
    <div class="card purchase-list-shell">
        <div class="card-body">
            <div class="purchase-list-titlebar d-flex justify-content-between align-items-center">
                <div>
                    <h5 class="purchase-list-title"><i class="bx bx-purchase-tag"></i> Purchase Orders</h5>
                    <div class="purchase-list-subtitle">Search, filter, sort, and open supplier orders.</div>
                </div>
            </div>
            <div id="tab_body"></div>
        </div>
    </div>
</main>
