<main id="main" class="main">
    <div class="card border-0">
        <div class="card-body">
                <input type="hidden" id="match_id">
                <input type="hidden" id="order_id_x">
                <input type="hidden" id="part_number_x">
                <input type="hidden" id="product_source_id_x">
            <h4 class="card-title" id="company_name_text">Production</h4>
            <ul class="nav nav-tabs nav-underline custom-tabs" id="myTab" role="tablist">
                <li class="nav-item" role="presentation">
                    <a class="nav-link active" id="production-tab" data-bs-toggle="tab" onclick="Loadtab('production')" role="tab" aria-controls="production" aria-selected="true">
                        <i class='bx bxs-home'></i> Production
                    </a>
                </li>
                 <li class="nav-item" role="presentation">
                    <a class="nav-link" id="production_history-tab" data-bs-toggle="tab" onclick="Loadtab('production_history')" role="tab" aria-controls="production_history" aria-selected="false">
                        <i class='bx bxs-home'></i> History
                    </a>
                </li>
            </ul>
            <div id="tab_body"></div>
        </div>
    </div>
</main>
                


<div class="modal fade" id="match_product_modal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-body p-2">

                <div class="row">
                    <div class="col-md-6">
                        <button type="button" onclick="fromStockModal()" class="btn btn-secondary btn-sm">From Stock</button>
                        <div id="coil_stock_list"></div>
                        <div id="order_body"></div>
                    </div>
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-body">
                                <div class="mb-1 d-flex align-items-center">
                                    <label for="order_qty" class="col-form-label col-4 pe-1">Order Qty:</label>
                                    <input name="order_qty" type="text" id="order_qty" disabled class="form-control form-control-sm" >
                                </div>
                                <div class="mb-1 d-flex align-items-center">
                                    <label for="order_qty" class="col-form-label col-4 pe-1">Selected Qty:</label>
                                    <input name="selected_qty" type="text" id="selected_qty" disabled class="form-control form-control-sm" onchange="calculateQuantities()">
                                </div>
                                <div class="mb-1 d-flex align-items-center">
                                    <label for="date_used" class="col-form-label col-4 pe-1">Date:</label>
                                    <input name="date_used" type="text" id="date_used" class="date_used form-control form-control-sm" value="<?php echo date('d-m-Y'); ?>">
                                </div>
                                <div class="mb-1 d-flex align-items-center">
                                    <label for="stock_in_qty" class="col-form-label col-4 pe-1">Stock Qty:</label>
                                    <input name="stock_in_qty" disabled type="text" id="stock_in_qty" class="form-control form-control-sm" onchange="calculateQuantities()">
                                </div>
                                <div class="mb-1 d-flex align-items-center">
                                    <label for="waste_qty" class="col-form-label col-4 pe-1">Waste:</label>
                                    <input name="waste_qty" type="text" id="waste_qty" class="form-control form-control-sm" onchange="calculateQuantities()" value='0'>
                                </div>
                                <div class="mb-1 d-flex align-items-center">
                                    <label for="stock_from_coil" class="col-form-label col-4 pe-1">From Coil:</label>
                                    <input name="stock_from_coil" disabled type="text" id="stock_from_coil" class="form-control form-control-sm" readonly>
                                </div>
                                <div class="mb-1 d-flex align-items-center">
                                    <label for="stock_total_in_qty" class="col-form-label col-4 pe-1">Total Stock In:</label>
                                    <input name="stock_total_in_qty" disabled type="text" id="stock_total_in_qty" class="form-control form-control-sm" readonly>
                                </div>
                                <div class="mb-1 d-flex align-items-center">
                                    <label for="coil_finished" class="col-form-label col-4 pe-1">Coil Finished:</label>
                                    <input type="checkbox" class="form-check-input" id="coil_finished">
                                </div>
                            </div>
                        </div>
                        <div class="card">
                            <div class="card-body">
                                <div id="production_stock_in"></div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer p-2">
                    <button type="button" onclick="recordProduction()" class="btn btn-secondary btn-sm">Record</button>
                </div>
            </div>
            <div id="site_message_modal"></div>
        </div>
    </div>
</div>

<div class="modal fade" id="match_stock_modal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-body p-2">
                <div class="row">
                    <div class="col-md-6">
                        <button type="button" onclick="fromCoilModal()" class="btn btn-secondary btn-sm">From Coil</button>
                        
                        <div id="order_stock_body"></div>
                    </div>
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-body">
                                <div id="stock_available"></div>
                                <div class="mb-1 d-flex align-items-center">
                                    <label for="total_from_order" class="col-form-label col-6 pe-1">Selected Order:</label>
                                    <input disabled name="stock_order_selected_qty" type="text" id="stock_order_selected_qty" class="form-control form-control-sm" >
                                </div>
                                <div class="mb-1 d-flex align-items-center">
                                    <label for="total_from_stock_value" class="col-form-label col-6 pe-1">Selected Stock:</label>
                                    <input disabled name="stock_selected_qty" type="text" id="stock_selected_qty" class="form-control form-control-sm">
                                </div>
                                <div class="mb-1 d-flex align-items-center">
                                    <label for="waste_qty" class="col-form-label col-6 pe-1">Waste:</label>
                                    <input name="waste_qty" type="text" id="stock_waste_qty" class="form-control form-control-sm" onchange="calculateStockFinalTotal()" value='0'>
                                </div>
                                <div class="mb-1 d-flex align-items-center">
                                    <label for="total_from_stock" class="col-form-label col-6 pe-1">Remaining:</label>
                                    <input name="total_from_stock" disabled type="text" id="total_from_stock" class="form-control form-control-sm" readonly>
                                </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer p-2">
                    <button type="button" onclick="recordStock()" class="btn btn-secondary btn-sm">Record</button>
                </div>
            </div>
            <div id="site_message_modal_stock"></div>
        </div>
    </div>
</div>
</div>

<div class="modal fade" id="split_product_modal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content" style="background-color: #e9ecef;"> <!-- Example light gray background -->
            <div class="modal-body">
                <input type="hidden" id="item_group_id">
                <div class="mb-3">
                    <label for="edit_item_group" class="form-label">Original Qty</label>
                    <div class="input-group">
                       <input name="original_qty" disabled type="number" id="original_qty" class="form-control" placeholder="Qty">
                        <button type="button" class="btn btn-secondary" tabindex="-1">X</button>
                        <input name="original_qty_units" disabled type="number" id="original_qty_units" class="form-control" placeholder="Length Lm">
                           </div>
                </div>
                
                <div class="mb-3">
                    <label for="edit_item_group" class="form-label">Split Qty</label>
                    <div class="input-group">
                        <input name="split_qty" type="text" id="split_qty" class="form-control" placeholder="">
                        <button type="button" class="btn btn-outline-secondary" onclick="SplitItemGroup()">Split</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="add_production_modal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content" style="background-color: #e9ecef;"> 
            <div class="modal-body">
                <input type="hidden" id="item_group_id">
                <div class="mb-3">
                    <label for="edit_item_group" class="form-label">Add Stock </label>
                    <div class="input-group">
                       <input name="add_stock_qty"  type="number" id="add_stock_qty" class="form-control" placeholder="Qty">
                        <button type="button" class="btn btn-outline-secondary" tabindex="-1">X</button>
                        <input name="add_stock_qty_units"  type="number" id="add_stock_qty_units" class="form-control" placeholder="Length Lm">
                        <button type="button" class="btn btn-outline-secondary" onclick="addStock()">Add</button>
                           </div>
                </div>
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>


<!-- Modal Structure -->
<script type="text/javascript" src="includes_pages/admin_production/script.js?n=<?php echo date('h:i:s');?>"></script> 



