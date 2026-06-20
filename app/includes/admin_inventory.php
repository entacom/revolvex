<script type="text/javascript" src="includes_pages/admin_inventory/script.js?n=<? echo date('h:i');?>"></script> 
<style>
    .inventory-shell {
        border: 0;
        border-radius: 8px;
        background: transparent;
    }

    .inventory-titlebar {
        position: relative;
        overflow: hidden;
        border-radius: 8px;
        padding: 18px 20px;
        margin-bottom: 14px;
        background: linear-gradient(135deg, #ffffff 0%, #f6f9ff 55%, #eef7f2 100%);
        border: 1px solid #dfe7f3;
        box-shadow: 0 14px 34px rgba(15, 42, 70, 0.10);
    }

    .inventory-titlebar:before {
        content: "";
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 4px;
        background: linear-gradient(90deg, #315ccf, #21a67a, #f28c28);
    }

    .inventory-title {
        margin: 0;
        color: #0b3158;
        font-size: 1.35rem;
        font-weight: 800;
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .inventory-title i {
        width: 36px;
        height: 36px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 8px;
        color: #315ccf;
        background: #eef2ff;
        font-size: 1.3rem;
    }

    .inventory-subtitle {
        color: #5d6a7d;
        font-size: 0.92rem;
        margin-top: 5px;
    }

    .inventory-filter-bar {
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
        align-items: center;
        padding: 12px;
        margin-bottom: 14px;
        border: 1px solid #dfe7f3;
        border-radius: 8px;
        background: #ffffff;
        box-shadow: 0 10px 24px rgba(15, 42, 70, 0.07);
    }

    .inventory-filter-bar label {
        margin: 0;
        color: #0b3158;
        font-size: 0.78rem;
        font-weight: 800;
        letter-spacing: 0;
        text-transform: uppercase;
    }

    .inventory-filter-bar .form-select,
    .inventory-filter-bar .form-control {
        min-width: 260px;
        max-width: 420px;
        border-color: #cdd7e5;
        border-radius: 7px;
        min-height: 40px;
    }

    .inventory-modal .modal-content,
    #inventoryItemEditModal .modal-content,
    #inventoryAddModal .modal-content,
    #inventoryEditModal .modal-content,
    #inventorySubItemEditModal .modal-content {
        border: 0;
        border-radius: 8px;
        box-shadow: 0 22px 55px rgba(12, 35, 60, 0.22);
    }

    #inventoryAddModal h2,
    #inventoryEditModal h2 {
        color: #0b3158;
        font-size: 1.2rem;
        font-weight: 800;
        margin-bottom: 14px;
    }

    #inventoryAddModal .card,
    #inventoryEditModal .card {
        border: 1px solid #e0e7f0;
        border-radius: 8px;
        box-shadow: none;
    }

    #inventoryAddModal .nav-tabs .nav-link,
    #inventoryEditModal .nav-tabs .nav-link {
        color: #526174;
        border-radius: 7px 7px 0 0;
        font-weight: 700;
    }

    #inventoryAddModal .nav-tabs .nav-link.active,
    #inventoryEditModal .nav-tabs .nav-link.active {
        color: #0b3158;
        background: #f5f8fc;
    }
</style>
<main id="main" class="main">
    <div class="card inventory-shell">
        <div class="card-body p-0">
            <div class="inventory-titlebar">
                <h4 class="inventory-title" id="company_name_text"><i class='bx bx-package'></i> Inventory</h4>
                <div class="inventory-subtitle">Stock levels, raw materials, buy-in items, groups, units, and pricing.</div>
            </div>

            <ul class="nav nav-tabs nav-underline custom-tabs mb-3" id="myTab" role="tablist">
                <li class="nav-item" role="presentation">
                    <a class="nav-link active" id="inventory-tab" data-bs-toggle="tab" onclick="Loadtab('inventory_list')" role="tab" aria-controls="company" aria-selected="true">
                        <i class='bx bxs-grid-alt'></i> Inventory
                    </a>
                </li>
            </ul>

            <div class="inventory-filter-bar">
                <label for="filter_group">Group Filter</label>
                <select id="filter_group" class="form-select"></select>
                <input type="hidden" id="filter_group_id">
            </div>
            <div id="tab_body"></div>
        </div>
    </div>
</main>

<div class="modal fade" id="inventoryItemEditModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-body">
                <input name="edit_inventory_item_id" type="text" id="edit_inventory_item_id" hidden>
                
                <div class="mb-3 row">
                    <label for="add_item_part_number" class="col-sm-4 col-form-label">Serial Number</label>
                    <div class="col-sm-8">
                        <input name="edit_serial_number" type="text" id="edit_item_serial_number" class="form-control" placeholder="Serial Number">
                    </div>
                </div>
                
                <div class="mb-3 row">
                    <label for="add_item_description" class="col-sm-4 col-form-label">Qty</label>
                    <div class="col-sm-8">
                        <input name="edit_qty" tabindex="-1" type="text" id="edit_item_qty" class="form-control">
                    </div>
                </div>
                <div class="mb-3 row">
                    <label for="coil_finished" class="col-sm-4 col-form-label">Coil Finished</label>
                    <div class="col-sm-8">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="edit_coil_finished" name="coil_finished">
                        </div>
                    </div>
                </div>
                
            </div>
            <div class="modal-footer">
                <div class="d-flex justify-content-end">
                    <button type="submit" class="btn btn-outline-secondary me-2" onclick="saveInventoryItem()">Save</button>
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>
</div>


<div class="modal fade" id="inventoryAddModal" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-body">
        <h2>Add Inventory Item</h2>
        <section class="section profile">
          <div class="row">
            <div class="col-xl-12">
              <div class="card">
                <div class="card-body pt-3">

                  <!-- Tabs -->
                  <ul class="nav nav-tabs nav-underline custom-tabs" id="addInventoryTabs" role="tablist">
                    <li class="nav-item">
                      <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#add-details" type="button" role="tab">
                        <i class='bx bxs-user'></i> Details
                      </button>
                    </li>
                    <li class="nav-item">
                      <button class="nav-link" data-bs-toggle="tab" data-bs-target="#add-price-levels" type="button" role="tab">
                        <i class='bx bxs-dollar-circle'></i> Price Levels
                      </button>
                    </li>
                    <li class="nav-item">
                      <button class="nav-link" data-bs-toggle="tab" data-bs-target="#add-spare" type="button" role="tab">
                        <i class='bx bxs-cog'></i> Spare
                      </button>
                    </li>
                  </ul>

                  <!-- Tab Content -->
                  <div class="tab-content pt-3">

                    <!-- DETAILS TAB -->
                    <div class="tab-pane fade show active" id="add-details" role="tabpanel">
                      <div class="row mb-1">
                        <label class="col-md-3 col-lg-3 col-form-label">Group</label>
                        <div class="col-md-8 col-lg-7">
                          <select id="add_group" class="form-control"></select>
                          <input type="hidden" id="add_group_id">
                        </div>
                      </div>

                      <div class="row mb-1 align-items-center">
                        <label class="col-sm-3 col-form-label">Raw Material</label>
                        <div class="col-sm-9 d-flex align-items-center">
                          <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="raw_material_checkbox">
                          </div>
                        </div>
                      </div>

                      <div class="row mb-1">
                        <label class="col-md-3 col-lg-3 col-form-label">Item Number</label>
                        <div class="col-md-6 col-lg-7">
                          <input type="text" id="part_number" class="form-control">
                        </div>
                      </div>

                      <div class="row mb-1">
                        <label class="col-md-3 col-lg-3 col-form-label">Product Source</label>
                        <div class="col-md-8 col-lg-7">
                          <select id="product_source" class="form-control"></select>
                          <input type="hidden" id="product_source_id">
                        </div>
                      </div>

                      <div class="row mb-1">
                        <label class="col-md-3 col-lg-3 col-form-label">Description</label>
                        <div class="col-md-8 col-lg-7">
                          <textarea id="description" class="form-control"></textarea>
                        </div>
                      </div>

                      <div class="row mb-1">
                        <label class="col-md-3 col-lg-3 col-form-label">Unit</label>
                        <div class="col-md-8 col-lg-7">
                          <select id="add_unit" class="form-control"></select>
                          <input type="hidden" id="add_unit_id">
                        </div>
                      </div>

                      <div class="row mb-1">
                        <label class="col-md-3 col-lg-3 col-form-label">Metre Per Unit</label>
                        <div class="col-md-8 col-lg-7">
                          <input type="text" id="metre_unit" class="form-control">
                        </div>
                      </div>

                      <div class="row mb-1">
                        <label class="col-md-3 col-lg-3 col-form-label">Weight Per Metre</label>
                        <div class="col-md-8 col-lg-7">
                          <input type="text" id="weight_unit" class="form-control">
                        </div>
                      </div>

                      <div class="row mb-1">
                        <label class="col-md-3 col-lg-3 col-form-label">Min Stock</label>
                        <div class="col-md-8 col-lg-7">
                          <input type="number" id="min_qty" class="form-control">
                        </div>
                      </div>

                      <div class="row mb-3">
                        <label class="col-md-3 col-lg-3 col-form-label">Qty (On Hand)</label>
                        <div class="col-md-8 col-lg-7">
                          <input type="number" id="qty" class="form-control" disabled>
                        </div>
                      </div>

                      <div class="row mb-1">
                        <label class="col-md-3 col-lg-3 col-form-label">Sell Rate</label>
                        <div class="col-md-8 col-lg-7">
                          <input type="text" id="rate" class="form-control">
                        </div>
                      </div>

                      <div class="row mb-1">
                        <label class="col-md-3 col-lg-3 col-form-label">Buy Rate</label>
                        <div class="col-md-8 col-lg-7">
                          <input type="text" id="buy_rate" class="form-control">
                        </div>
                      </div>
                    </div>

                    <!-- PRICE LEVELS TAB -->
                    <div class="tab-pane fade" id="add-price-levels" role="tabpanel">
                      <div class="row mb-2">
                        <label class="col-md-3 col-form-label">Level A Rate</label>
                        <div class="col-md-8 col-lg-7">
                          <input type="text" id="add_rate_level_a" class="form-control">
                        </div>
                      </div>
                      <div class="row mb-2">
                        <label class="col-md-3 col-form-label">Level B Rate</label>
                        <div class="col-md-8 col-lg-7">
                          <input type="text" id="add_rate_level_b" class="form-control">
                        </div>
                      </div>
                      <div class="row mb-2">
                        <label class="col-md-3 col-form-label">Level C Rate</label>
                        <div class="col-md-8 col-lg-7">
                          <input type="text" id="add_rate_level_c" class="form-control">
                        </div>
                      </div>
                      <div class="row mb-2">
                        <label class="col-md-3 col-form-label">Level D Rate</label>
                        <div class="col-md-8 col-lg-7">
                          <input type="text" id="add_rate_level_d" class="form-control">
                        </div>
                      </div>
                      <div class="row mb-2">
                        <label class="col-md-3 col-form-label">Level E Rate</label>
                        <div class="col-md-8 col-lg-7">
                          <input type="text" id="add_rate_level_e" class="form-control">
                        </div>
                      </div>
                      <div class="row mb-2">
                        <label class="col-md-3 col-form-label">Level F Rate</label>
                        <div class="col-md-8 col-lg-7">
                          <input type="text" id="add_rate_level_f" class="form-control">
                        </div>
                      </div>
                      <div class="row mb-2">
                        <label class="col-md-3 col-form-label">Level G Rate</label>
                        <div class="col-md-8 col-lg-7">
                          <input type="text" id="add_rate_level_g" class="form-control">
                        </div>
                      </div>
                      <div class="row mb-2">
                        <label class="col-md-3 col-form-label">Level H Rate</label>
                        <div class="col-md-8 col-lg-7">
                          <input type="text" id="add_rate_level_h" class="form-control">
                        </div>
                      </div>
                      <div class="row mb-2">
                        <label class="col-md-3 col-form-label">Level I Rate</label>
                        <div class="col-md-8 col-lg-7">
                          <input type="text" id="add_rate_level_i" class="form-control">
                        </div>
                      </div>
                      <div class="row mb-3">
                        <label class="col-md-3 col-form-label">Level J Rate</label>
                        <div class="col-md-8 col-lg-7">
                          <input type="text" id="add_rate_level_j" class="form-control">
                        </div>
                      </div>
                          <small id="price_level_note" style="display: block; color: #666; margin-top: 5px;">
                          Price level is set using Xero's <strong>Discount</strong> field (as a label, not a percentage):<br>
                          1 = Level A, 2 = Level B, 3 = Level C, 4 = Level D, 5 = Level E, 6 = Level F, 7 = Level G, 8 = Level H, 9 = Level I, 10 = Level J
                        </small>
                    </div>

                    <!-- SPARE TAB -->
                    <div class="tab-pane fade" id="add-spare" role="tabpanel">
                      <div class="p-3">
                         <div class="row mb-3">
                          <label class="col-md-3 col-form-label">Gauge</label>
                          <div class="col-md-8 col-lg-7">
                            <input type="text" id="add_gauge" class="form-control">
                          </div>
                        </div>
                             <div class="row mb-3">
                          <label class="col-md-3 col-form-label">Manufacturer's Code</label>
                          <div class="col-md-8 col-lg-7">
                            <input type="text" id="add_manufacture_code" class="form-control">
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>

                  <div class="d-flex justify-content-end pt-3">
                    <button type="submit" class="btn btn-outline-secondary me-2" onclick="addInventory()">Save</button>
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button>
                  </div>
                    <div id="site_message_modal"></div>

                </div>
              </div>
            </div>
          </div>
        </section>
      </div>
    </div>
  </div>
</div>

</div><!-- End Extra Large Modal-->
<div class="modal fade" id="inventoryEditModal" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-body">
        <h2>Edit Inventory Item</h2>
        <input type="hidden" id="edit_inventory_id">

        <ul class="nav nav-tabs nav-underline custom-tabs" id="myTab" role="tablist">
          <li class="nav-item">
            <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tab-details" type="button" role="tab">
              <i class='bx bxs-user'></i> Details
            </button>
          </li>
          <li class="nav-item">
            <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-price-levels" type="button" role="tab">
              <i class='bx bxs-group'></i> Price Levels
            </button>
          </li>
          <li class="nav-item">
            <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-spare" type="button" role="tab">
              <i class='bx bxs-cog'></i> Spare
            </button>
          </li>
        </ul>

        <div class="tab-content pt-3">
          <!-- DETAILS TAB -->
          <div class="tab-pane fade show active" id="tab-details" role="tabpanel">
            <div class="row mb-1">
              <label class="col-md-3 col-lg-3 col-form-label">Group</label>
              <div class="col-md-8 col-lg-7">
                <select id="edit_group" class="form-control"></select>
                <input type="hidden" id="edit_group_id">
              </div>
            </div>

            <div class="row mb-1 align-items-center">
              <label class="col-sm-3 col-form-label">Raw Material</label>
              <div class="col-sm-9 d-flex align-items-center">
                <div class="form-check form-switch">
                  <input class="form-check-input" type="checkbox" id="edit_raw_material_checkbox">
                </div>
              </div>
            </div>

            <div class="row mb-1">
              <label class="col-md-3 col-lg-3 col-form-label">Item Number</label>
              <div class="col-md-6 col-lg-7">
                <input type="text" id="edit_part_number" class="form-control">
              </div>
            </div>

            <div class="row mb-1">
              <label class="col-md-3 col-lg-3 col-form-label">Product Source</label>
              <div class="col-md-8 col-lg-7">
                <select id="edit_product_source" class="form-control"></select>
                <input type="hidden" id="edit_product_source_id">
              </div>
            </div>

            <div class="row mb-1">
              <label class="col-md-3 col-lg-3 col-form-label">Description</label>
              <div class="col-md-8 col-lg-7">
                <textarea id="edit_description" class="form-control"></textarea>
              </div>
            </div>

            <div class="row mb-1">
              <label class="col-md-3 col-lg-3 col-form-label">Unit</label>
              <div class="col-md-8 col-lg-7">
                <select id="edit_unit" class="form-control"></select>
                <input type="hidden" id="edit_unit_id">
              </div>
            </div>

            <div class="row mb-1">
              <label class="col-md-3 col-lg-3 col-form-label">Metre Per Unit</label>
              <div class="col-md-8 col-lg-7">
                <input type="text" id="edit_metre_unit" class="form-control">
              </div>
            </div>

            <div class="row mb-1">
              <label class="col-md-3 col-lg-3 col-form-label">Weight Per Metre</label>
              <div class="col-md-8 col-lg-7">
                <input type="text" id="edit_weight_unit" class="form-control">
              </div>
            </div>

            <div class="row mb-1">
              <label class="col-md-3 col-lg-3 col-form-label">Min Stock</label>
              <div class="col-md-8 col-lg-7">
                <input type="number" id="edit_min_qty" class="form-control">
              </div>
            </div>

            <div class="row mb-3">
              <label class="col-md-3 col-lg-3 col-form-label">Qty (On Hand)</label>
              <div class="col-md-8 col-lg-7">
                <input type="number" id="edit_qty" class="form-control" disabled>
              </div>
            </div>

            <div class="row mb-1">
              <label class="col-md-3 col-lg-3 col-form-label">Base Rate</label>
              <div class="col-md-8 col-lg-7">
                <input type="text" id="edit_rate" class="form-control">
              </div>
            </div>

            <div class="row mb-1">
              <label class="col-md-3 col-lg-3 col-form-label">Buy Rate</label>
              <div class="col-md-8 col-lg-7">
                <input type="text" id="edit_buy_rate" class="form-control">
              </div>
            </div>
          </div>

          <!-- PRICE LEVELS TAB -->
          <div class="tab-pane fade" id="tab-price-levels" role="tabpanel">
            <div class="row mb-2">
              <label class="col-md-3 col-form-label">Level A Rate</label>
              <div class="col-md-8 col-lg-7">
                <input type="text" id="edit_rate_level_a" class="form-control">
              </div>
            </div>
            <div class="row mb-2">
              <label class="col-md-3 col-form-label">Level B Rate</label>
              <div class="col-md-8 col-lg-7">
                <input type="text" id="edit_rate_level_b" class="form-control">
              </div>
            </div>
            <div class="row mb-2">
              <label class="col-md-3 col-form-label">Level C Rate</label>
              <div class="col-md-8 col-lg-7">
                <input type="text" id="edit_rate_level_c" class="form-control">
              </div>
            </div>
            <div class="row mb-2">
              <label class="col-md-3 col-form-label">Level D Rate</label>
              <div class="col-md-8 col-lg-7">
                <input type="text" id="edit_rate_level_d" class="form-control">
              </div>
            </div>
            <div class="row mb-2">
              <label class="col-md-3 col-form-label">Level E Rate</label>
              <div class="col-md-8 col-lg-7">
                <input type="text" id="edit_rate_level_e" class="form-control">
              </div>
            </div>
            <div class="row mb-2">
              <label class="col-md-3 col-form-label">Level F Rate</label>
              <div class="col-md-8 col-lg-7">
                <input type="text" id="edit_rate_level_f" class="form-control">
              </div>
            </div>
            <div class="row mb-2">
              <label class="col-md-3 col-form-label">Level G Rate</label>
              <div class="col-md-8 col-lg-7">
                <input type="text" id="edit_rate_level_g" class="form-control">
              </div>
            </div>
            <div class="row mb-2">
              <label class="col-md-3 col-form-label">Level H Rate</label>
              <div class="col-md-8 col-lg-7">
                <input type="text" id="edit_rate_level_h" class="form-control">
              </div>
            </div>
            <div class="row mb-2">
              <label class="col-md-3 col-form-label">Level I Rate</label>
              <div class="col-md-8 col-lg-7">
                <input type="text" id="edit_rate_level_i" class="form-control">
              </div>
            </div>
            <div class="row mb-3">
              <label class="col-md-3 col-form-label">Level J Rate</label>
              <div class="col-md-8 col-lg-7">
                <input type="text" id="edit_rate_level_j" class="form-control">
              </div>
            </div>
            <small id="price_level_note" style="display: block; color: #666; margin-top: 5px;">
              Price level is set using Xero's <strong>Discount</strong> field (as a label, not a percentage):<br>
              1 = Level A, 2 = Level B, 3 = Level C, 4 = Level D, 5 = Level E, 6 = Level F, 7 = Level G, 8 = Level H, 9 = Level I, 10 = Level J
            </small>

          </div>
            

          <!-- SPARE TAB -->
          <div class="tab-pane fade" id="tab-spare" role="tabpanel">
            <div class="p-3">
               <div class="row mb-3">
              <label class="col-md-3 col-form-label">Gauge</label>
              <div class="col-md-8 col-lg-7">
                <input type="text" id="edit_gauge" class="form-control">
              </div>
            </div>
                 <div class="row mb-3">
              <label class="col-md-3 col-form-label">Manufacturer's Code</label>
              <div class="col-md-8 col-lg-7">
                <input type="text" id="edit_manufacture_code" class="form-control">
              </div>
            </div>
            </div>
          </div>
        </div>

        <div class="d-flex justify-content-end pt-3">
          <button type="submit" class="btn btn-outline-secondary me-2" onclick="SaveInventory()">Save</button>
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button>
        </div>

      </div><!-- /.modal-body -->
    </div><!-- /.modal-content -->
  </div><!-- /.modal-dialog -->
</div><!-- /.modal -->

<div class="modal fade" id="inventorySubItemEditModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-body">
                <input type="hidden" id="edit_inventory_sub_item_id" >
                    <label for="add_add_bill_rate_units" class="form-label"> Quantity x Length</label>
                    <div class="input-group">
                        
                        <input name="edit_sub_item_qty" type="number" id="edit_sub_item_qty" class="form-control" placeholder="Qty">
                        <button type="button" class="btn btn-secondary" tabindex="-1">X</button>
                        <input name="edit_sub_item_qty_unit" type="number" id="edit_sub_item_qty_unit" class="form-control" placeholder="Length Lm">
                        <button type="button" class="btn btn-outline-secondary" onclick="saveInventorySubItem()">Save</button>
                    </div>
             
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" tabindex="-1" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>
