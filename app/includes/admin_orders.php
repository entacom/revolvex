<style>
.ui-autocomplete {
    z-index: 1060  !important; /* Bootstrap modal z-index is 1040 */
}
.drag-helper {
    background-color: rgba(255, 255, 255, 0.8);
    border: 1px solid #ccc;
    padding: 5px;
    border-radius: 3px;
    text-align: center;
}

.order-hero-card {
    border: 0;
    border-radius: 8px;
    box-shadow: 0 12px 30px rgba(33, 37, 41, 0.10);
    overflow: visible;
    position: relative;
    z-index: 20;
}

.order-hero-card::before {
    background: linear-gradient(90deg, #4154f1, #2eca6a, #ff771d);
    content: "";
    height: 4px;
    inset: 0 0 auto 0;
    position: absolute;
}

.order-hero-body {
    padding: 1rem 1.15rem 0.95rem;
}

.order-hero-title {
    color: #263238;
    font-size: 1rem;
    font-weight: 800;
    line-height: 1.25;
}

.order-hero-subtitle {
    color: #6c757d;
    font-size: 0.78rem;
    line-height: 1.35;
    margin-top: 0.2rem;
}

.order-hero-label {
    color: #8a94a6;
    font-size: 0.68rem;
    font-weight: 800;
    letter-spacing: 0.02em;
    text-transform: uppercase;
}

.order-hero-value {
    color: #263238;
    font-size: 0.9rem;
    font-weight: 700;
}

.order-hero-meta {
    background: #f8f9fc;
    border: 1px solid #edf0f7;
    border-radius: 8px;
    padding: 0.65rem 0.75rem;
}

.order-hero-icon {
    background: #eef0ff;
    color: #4154f1;
    height: 44px;
    width: 44px;
}

.order-hero-status {
    background: #eef0ff;
    border: 1px solid #dfe4ff;
    border-radius: 999px;
    color: #4154f1;
    display: inline-flex;
    font-size: 0.78rem;
    font-weight: 800;
    gap: 0.25rem;
    padding: 0.28rem 0.7rem;
    white-space: nowrap;
}

.order-hero-actions .btn {
    box-shadow: 0 4px 12px rgba(33, 37, 41, 0.08);
}

.order-hero-actions .dropdown-menu {
    z-index: 3000;
}
</style>
<link rel="stylesheet" href="https://unpkg.com/dropzone@5/dist/min/dropzone.min.css" type="text/css" />
<script src="https://unpkg.com/dropzone@5/dist/min/dropzone.min.js"></script>


<main id="main" class="main order-page-main">
<div class="card order-hero-card mb-4">
  <div class="card-body order-hero-body">
    <div class="row align-items-center g-3">
      <div class="col-lg-5">
        <div class="d-flex align-items-start gap-3">
            <div class="order-hero-icon rounded-circle d-flex align-items-center justify-content-center">
                <i class="bi bi-person-lines-fill"></i>
            </div>
            <div class="min-width-0">
                <div id="infobar_address_full" class="order-hero-title"></div>
                <div id="infobar_order_number" class="order-hero-subtitle"></div>
                <div id="infobar_order_id" class="order-hero-subtitle"></div>
            </div>
        </div>
      </div>

      <div class="col-lg-4">
        <div class="row g-2">
            <div class="col-sm-6">
                <div class="order-hero-meta h-100">
                    <div class="order-hero-label">Delivery</div>
                    <div id="infobar_delivery_date" class="order-hero-value"></div>
                </div>
            </div>
            <div class="col-sm-6">
                <div class="order-hero-meta h-100">
                    <div class="order-hero-label">Note</div>
                    <div id="infobar_note" class="order-hero-value text-truncate"></div>
                </div>
            </div>
        </div>
      </div>

      <div class="col-lg-3">
        <div class="d-flex align-items-center justify-content-lg-end flex-wrap gap-2 order-hero-actions">
            <div id="infobar_status"></div>
            <div id="infobar_order_status"></div>
            <div id="printOrder"></div>
        </div>
      </div>
    </div>
  </div>
</div>

		

            <!-- Styled Tabs with Boxicons and Text -->
<div class="d-none d-lg-block">
    <ul class="nav nav-tabs custom-tabs" id="myTab" role="tablist">
        <li class="nav-item" role="presentation">
            <a class="nav-link active" id="home-tab" data-bs-toggle="tab" onclick="Loadtab('home')" role="tab" aria-controls="home" aria-selected="true">
                <i class='bx bxs-home'></i> Home
            </a>
        </li>
        <li class="nav-item" role="presentation">
            <a class="nav-link" id="order_items-tab" data-bs-toggle="tab" onclick="Loadtab('order_items')" role="tab" aria-controls="order_items" aria-selected="false">
                <i class='bx bx-cart'></i> Items
            </a>
        </li>
        <li class="nav-item" role="presentation">
            <a class="nav-link" id="activity-tab" data-bs-toggle="tab" onclick="Loadtab('activity')" role="tab" aria-controls="activity" aria-selected="false">
                <i class='bx bx-line-chart'></i> Activity
            </a>
        </li>
         <li class="nav-item" role="presentation">
            <a class="nav-link" id="attachments-tab" data-bs-toggle="tab" onclick="Loadtab('attachments')" role="tab" aria-controls="attachments" aria-selected="false">
                <i class='bx bx-paperclip'></i> Attachments
            </a>
        </li>
<li class="nav-item" role="presentation" id="tab-pack">
    <a class="nav-link" id="pack_tab-tab" data-bs-toggle="tab" onclick="Loadtab('pack_tab')" role="tab" aria-controls="pack_tab" aria-selected="false">
        <i class='bx bx-package'></i> Pack
    </a>
</li>
<li class="nav-item" role="presentation" id="tab-production">
    <a class="nav-link" id="production-tab" data-bs-toggle="tab" onclick="Loadtab('production')" role="tab" aria-controls="production" aria-selected="false">
        <i class='bx bxs-factory'></i> Production Card
    </a>
</li>
<li class="nav-item" role="presentation" id="tab-invoice">
    <a class="nav-link" id="invoice-tab" data-bs-toggle="tab" onclick="Loadtab('invoice')" role="tab" aria-controls="invoice" aria-selected="false">
        <i class='bx bx-receipt'></i> Invoice
    </a>
</li>

    </ul>
</div>

            <div id="jobs_tab_body"></div>
			 
        </div>
    </div>
	
</main>


<!-- Modal Structure -->

<div class="modal fade" id="add_item_order_modal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-body">
                <input name="has_items_id_tag" type="hidden" id="has_items_id_tag">
                <div class="mb-3">
                    <label for="add_item_part_number" class="form-label">Add Items to Order</label>
                    <div class="input-group">
                        <input name="add_item_part_number" type="text" id="add_item_part_number" class="form-control" placeholder="Part Number">
                    </div>
                </div>
                <div class="mb-3" id="description_field" style="display: none;">
                    <label for="add_item_description" class="form-label">Description</label>
                    <div class="input-group">
                        <input name="add_item_description" tabindex="-1" type="text" id="add_item_description" class="form-control">
                    </div>
                </div>
                <div class="mb-3" id="qty_field" style="display: none;">
                    <label for="add_item_qty" class="form-label">Qty</label>
                    <div class="input-group">
                        <input name="add_item_qty" tabindex="-1" type="text" id="add_item_qty" class="form-control">
                    </div>
                </div>
                <div class="mb-3" id="price_field" style="display: none;">
                    <label for="add_item_rate" class="form-label">Price Ex</label>
                    <div class="input-group">
                        <input name="add_item_rate" tabindex="-1" type="text" id="add_item_rate" class="form-control">
                        <span class="input-group-append">
                            <button type="button" class="btn btn-outline-secondary" onclick="AddOrderItems()">Add</button>
                        </span>
                    </div>
                </div>

                <div class="mb-3" id="units_field" style="display: none;">
                    <label for="add_item_units" class="form-label">Quantity x Length, Mark</label>
                    <div class="input-group mb-2">
                        <input name="add_item_units" type="number" id="add_item_units" class="form-control" placeholder="Qty">
                        <button type="button" class="btn btn-secondary" tabindex="-1">X</button>
                        <input name="add_item_units_qty" type="number" id="add_item_units_qty" class="form-control" placeholder="Length Lm">
                        <input name="add_item_mark" type="text" id="add_item_mark" class="form-control" placeholder="Mark">
                    </div>
                    <div class="input-group">
                        <input name="add_item_punch" type="text" id="add_item_punch" class="form-control" placeholder="Punch">
                        <button type="button" class="btn btn-outline-secondary" onclick="AddOrderItems()">Add</button>
                    </div>
                </div>
                <div id="has_sub_items"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" tabindex="-1" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>


<div class="modal fade" id="edit_invoice_item_modal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-body">
                <input type="hidden" id="edit_invoice_item_id">
                <div class="mb-3">
                    <label for="edit_item_description" class="form-label">Description</label>
                    <div class="input-group">
                        <input name="edit_invoice_description" tabindex="-1" type="text" id="edit_invoice_description" class="form-control">
                    </div>
                </div>
                <div class="mb-3">
                    <label for="edit_item_qty" class="form-label">Qty</label>
                    <div class="input-group">
                        <input name="edit_invoice_qty" tabindex="-1" type="text" id="edit_invoice_qty" class="form-control">
                    </div>
                </div>
                <div class="mb-3">
                    <label for="edit_item_rate" class="form-label">Price Ex</label>
                    <div class="input-group">
                        <input name="edit_invoice_rate" tabindex="-1" type="text" id="edit_invoice_rate" class="form-control">
                    </div>
                </div>
            </div>
            <div class="modal-footer justify-content-between">
                <button type="button" onclick="delInvoiceId()" class="btn btn-danger">Delete</button>
                <button type="button" onclick="saveInvoiceItem()" class="btn btn-secondary">Save</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Structure -->
<div class="modal fade" id="edit_order_item_modal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-body">
                <input type="hidden" id="edit_item_id">
                <div class="mb-3">
                    <label for="edit_part_number" class="form-label">Edit Items</label>
                    <div class="input-group">
                        <input name="edit_part_number" disabled type="text" id="edit_part_number" class="form-control" placeholder="Part Number">
                    </div>
                </div>
                <div class="mb-3">
                    <label for="edit_item_description" class="form-label">Description</label>
                    <div class="input-group">
                        <input name="edit_item_description" tabindex="-1" type="text" id="edit_item_description" class="form-control">
                    </div>
                </div>
                <div id="edit_has_item">
                <div class="mb-3">
                    <label for="edit_item_qty" class="form-label">Qty</label>
                    <div class="input-group">
                        <input name="edit_item_qty" tabindex="-1" type="text" id="edit_item_qty" class="form-control">
                    </div>
                </div>
                  </div>
                <div class="mb-3">
                    <label for="edit_item_rate" class="form-label">Price Ex</label>
                    <div class="input-group">
                        <input name="edit_item_rate" tabindex="-1" type="text" id="edit_item_rate" class="form-control">
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <div class="d-flex w-100">
                    <button type="button" onclick="deleteOrderItem()" class="btn btn-danger me-auto">Delete</button>
                    <button type="button" onclick="SaveOrderItems()" class="btn btn-secondary ms-auto">Save</button>
                </div>
            </div>
        </div>
    </div>
</div>


<!-- AMENDED BLOCK: edit_order_sub_item_modal -->
<div class="modal fade" id="edit_order_sub_item_modal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-body">
                <input type="hidden" id="edit_sub_item_id">
                <div class="mb-3">
                    <label for="edit_item_mark" class="form-label">Mark</label>
                    <div class="input-group">
                        <input name="edit_item_mark" tabindex="1" type="text" id="edit_item_mark" class="form-control">
                    </div>
                </div>
                <div class="mb-3">
                    <label for="edit_item_punch" class="form-label">Punch</label>
                    <div class="input-group">
                        <input name="edit_item_punch" tabindex="2" type="text" id="edit_item_punch" class="form-control">
                    </div>
                </div>
                <div class="mb-3">
                    <label for="edit_item_units" class="form-label">Items Name and Quantity</label>
                    <div class="input-group">
                        <input name="edit_item_units" tabindex="3" type="number" id="edit_item_units" class="form-control" placeholder="Qty">
                        <button type="button" class="btn btn-secondary" tabindex="-1">X</button>
                        <input name="edit_item_units_qty" tabindex="4" type="number" id="edit_item_units_qty" class="form-control" placeholder="Length Lm">
                        <button type="button" class="btn btn-secondary" tabindex="5" onclick="SaveOrderSubItems()">Save</button>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" onclick="deleteOrderSubItem()" tabindex="-1" class="btn btn-danger">Delete</button>
                <button type="button" class="btn btn-secondary ms-auto" tabindex="-1" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>



<div class="modal fade" id="modalAddOrderActivity" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="exampleModalLabel">Add Activity</h5>
              
            </div>
			<div class="modal-body">
                <div class="row mb-0">
					<label for="inputText" class="col-sm-12 col-form-label">Activity</label>
					<div class="col-sm-12">
						<textarea type="text" rows="4" id="add_activity_description" class="form-control form-control-sm" placeholder="Add a new activity for this order.."></textarea>
					</div>
				</div>

				
				<div class="row mb-0">
                  <label for="inputDate" class="col-sm-2 col-form-label">Date(optional)</label>
                  <div class="col-sm-12">
                    <input type="date" id="add_date_activity" class="form-control">
                  </div>
	
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" onclick="AddOrderActivity()" class="btn btn-secondary">Add</button>
            </div>
        </div>
    </div>
</div>
<div class="modal fade" id="modalEditOrderActivity" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="exampleModalLabel">Edit Activity</h5>
              <input id ="edit_activity_id" type="text"/>
            </div>
			<div class="modal-body">
                <div class="row mb-0">
					<label for="inputText" class="col-sm-12 col-form-label">Activity</label>
					<div class="col-sm-12">
						<textarea type="text" rows="4" id="edit_activity_description" class="form-control form-control-sm" placeholder="Add a new activity for this order.."></textarea>
					</div>
				</div>

				
				<div class="row mb-0">
                  <label for="inputDate" class="col-sm-2 col-form-label">Date(optional)</label>
                  <div class="col-sm-12">
                    <input type="text" id="edit_date_activity" class="form-control">
                  </div>
	
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" onclick="SaveOrderActivity()" class="btn btn-secondary">Save</button>
            </div>
        </div>
    </div>
</div>
<div class="modal fade" id="ProcessInvoiceModal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">

            <div class="modal-header">
                <h5 class="modal-title" id="exampleModalLabel">Process Invoice</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <input type="hidden" id="process_order_id">

            <div class="modal-body">
                <div class="row mb-6">
                    <label for="edit_date_invoice" class="col-sm-4 col-form-label">Invoice Date</label>
                    <div class="col-sm-5">
                        <input type="text" id="edit_date_invoice" class="edit_date_invoice form-control">
                    </div>
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" onclick="processInvButton()" class="btn btn-primary">Process</button>
            </div>

        </div>
    </div>
</div>
<div class="modal fade" id="ProcessOrderModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Process Order</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <input type="hidden" id="process_workflow_order_id">
            <div class="modal-body">
                <div class="list-group process-order-list">
                    <div class="list-group-item d-flex justify-content-between align-items-center gap-3">
                        <div>
                            <div class="fw-bold">Order Confirmation</div>
                            <div class="small text-muted">Email or print the order confirmation document.</div>
                            <div class="small text-success process-activity-summary" data-workflow-summary="order_confirmation_emailed"></div>
                            <div class="small text-success process-activity-summary" data-workflow-summary="order_confirmation_printed"></div>
                        </div>
                        <div class="btn-group">
                            <button type="button" class="btn btn-sm btn-outline-primary" onclick="ProcessOrderEmailConfirmation()">Email</button>
                            <button type="button" class="btn btn-sm btn-outline-secondary" onclick="ProcessOrderPrintConfirmation()">Print</button>
                        </div>
                    </div>
                    <div class="list-group-item d-flex justify-content-between align-items-center gap-3">
                        <div>
                            <div class="fw-bold">Production Cards</div>
                            <div class="small text-muted">Print all production cards for this order.</div>
                            <div class="small text-success process-activity-summary" data-workflow-summary="production_cards_printed"></div>
                        </div>
                        <button type="button" class="btn btn-sm btn-outline-secondary" onclick="ProcessOrderPrintProductionCards()">Print</button>
                    </div>
                    <div class="list-group-item d-flex justify-content-between align-items-center gap-3">
                        <div>
                            <div class="fw-bold">Production CSV Files</div>
                            <div class="small text-muted">Save machine CSV files for manufactured items on this order.</div>
                            <div class="small text-success process-activity-summary" data-workflow-summary="production_csv_saved"></div>
                        </div>
                        <button type="button" id="process_csv_button" class="btn btn-sm btn-outline-secondary" onclick="ProcessOrderSaveProductionCsvs()">Save</button>
                    </div>
                    <div class="list-group-item d-flex justify-content-between align-items-center gap-3">
                        <div>
                            <div class="fw-bold">Labels</div>
                            <div class="small text-muted">Print order labels for packed items.</div>
                            <div class="small text-success process-activity-summary" data-workflow-summary="labels_dymo_printed"></div>
                            <div class="small text-success process-activity-summary" data-workflow-summary="labels_zebra_printed"></div>
                        </div>
                        <div class="btn-group">
                            <button type="button" class="btn btn-sm btn-outline-secondary" onclick="ProcessOrderPrintLabels('dymo')">Dymo</button>
                            <button type="button" class="btn btn-sm btn-outline-primary" onclick="ProcessOrderPrintLabels('zebra')">Zebra</button>
                        </div>
                    </div>
                    <div class="list-group-item d-flex justify-content-between align-items-center gap-3">
                        <div>
                            <div class="fw-bold">Delivery Docket</div>
                            <div class="small text-muted">Print the delivery docket.</div>
                            <div class="small text-success process-activity-summary" data-workflow-summary="delivery_docket_printed"></div>
                        </div>
                        <button type="button" class="btn btn-sm btn-outline-secondary" onclick="ProcessOrderPrintDelivery()">Print</button>
                    </div>
                    <div class="list-group-item d-flex justify-content-between align-items-center gap-3 bg-light">
                        <div>
                            <div class="fw-bold">Process</div>
                            <div class="small text-muted">Move this order to In Production and add an activity record.</div>
                            <div class="small text-success process-activity-summary" data-workflow-summary="order_processed"></div>
                        </div>
                        <button type="button" id="process_order_button" class="btn btn-sm btn-success" onclick="ProcessOrderButton()">Process</button>
                    </div>
                </div>
            </div>
            <div class="modal-footer justify-content-end">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>
<div class="modal fade" id="ProcessQuoteModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Process Quote</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <input type="hidden" id="process_quote_order_id">
            <div class="modal-body">
                <div class="list-group process-order-list">
                    <div class="list-group-item d-flex justify-content-between align-items-center gap-3">
                        <div>
                            <div class="fw-bold">Payment Required</div>
                            <div class="small text-muted">Mark this quote as requiring payment before it can become an order.</div>
                            <div class="small text-success process-activity-summary" data-quote-workflow-summary="quote_payment_required"></div>
                        </div>
                        <div class="form-check form-switch m-0">
                            <input class="form-check-input" type="checkbox" id="quote_payment_required_checkbox" onchange="ProcessQuotePaymentRequiredChanged()">
                        </div>
                    </div>
                    <div class="list-group-item d-flex justify-content-between align-items-center gap-3">
                        <div>
                            <div class="fw-bold">Quote Document</div>
                            <div class="small text-muted">Email or print the quote document.</div>
                            <div class="small text-success process-activity-summary" data-quote-workflow-summary="quote_emailed"></div>
                            <div class="small text-success process-activity-summary" data-quote-workflow-summary="quote_printed"></div>
                        </div>
                        <div class="btn-group">
                            <button type="button" class="btn btn-sm btn-outline-primary" onclick="ProcessQuoteEmail()">Email</button>
                            <button type="button" class="btn btn-sm btn-outline-secondary" onclick="ProcessQuotePrint()">Print</button>
                        </div>
                    </div>
                    <div class="list-group-item d-flex justify-content-between align-items-center gap-3 bg-light">
                        <div>
                            <div class="fw-bold">Convert To Order</div>
                            <div class="small text-muted">Confirm payment if required, then convert this quote to an order.</div>
                            <div class="small text-success process-activity-summary" data-quote-workflow-summary="quote_payment_received"></div>
                            <div class="small text-success process-activity-summary" data-quote-workflow-summary="quote_converted_to_order"></div>
                        </div>
                        <div class="d-flex align-items-center gap-3">
                            <div class="form-check m-0">
                                <input class="form-check-input" type="checkbox" id="quote_payment_received_checkbox" onchange="ProcessQuotePaymentReceivedChanged()">
                                <label class="form-check-label small" for="quote_payment_received_checkbox">Payment received</label>
                            </div>
                            <button type="button" id="convert_quote_button" class="btn btn-sm btn-success" onclick="ProcessQuoteConvertToOrder()">Convert</button>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer justify-content-end">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>
<div class="modal fade" id="email_sales_order" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <!-- Modal Header -->
            <div class="modal-header">
                <h5 class="modal-title">Email Order</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <input name="email_order_id" type="hidden" id="email_order_id" class="form-control">
            <input name="email_document_type" type="hidden" id="email_document_type" class="form-control">
            <!-- Modal Body -->
            <div class="modal-body">
                <div class="row mb-3">
                    <div class="col-12">
                        <label for="edit_qty" class="form-label">Email</label>
                        <input name="email_address_po1" type="text" id="email_address_po1" class="form-control" >
                    </div>
                </div>

                <div class="row">
                    <div class="col-12">
                        <label for="edit_rate" class="form-label">Email (optional)</label>
                        <input name="email_address_po2" type="text" id="email_address_po2" class="form-control">
                    </div>
                </div>

                <div class="row mt-3">
                    <div class="col-12">
                        <label for="email_subject" class="form-label">Subject</label>
                        <input name="email_subject" type="text" id="email_subject" class="form-control">
                    </div>
                </div>

                <div class="row mt-3">
                    <div class="col-12">
                        <label for="email_body" class="form-label">Body</label>
                        <textarea name="email_body" id="email_body" class="form-control" rows="7"></textarea>
                    </div>
                </div>
            </div>

            <!-- Modal Footer -->
            <div class="modal-footer justify-content-end">
                <button type="button" class="btn btn-secondary me-2" data-bs-dismiss="modal">Close</button>
                <button type="button" onclick="SendEmailOrder()" class="btn btn-primary">Send</button>
            </div>
        </div>
    </div>
</div>
<div class="modal fade" id="DelInvoiceModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <!-- Modal Header -->
            <div class="modal-header">
                <h5 class="modal-title">Delete Invoice</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <!-- Explanation -->
            <div class="modal-body">
                <div class="alert alert-warning mb-3">
                    <strong>Important:</strong> Deleting this invoice will
                    <u>only</u> remove it from this system.
                    It will <u>not</u> delete or change anything in your linked accounting software.
                </div>
                <input name="del_in_id" type="hidden" id="del_in_id" class="form-control" placeholder="Order ID">
            </div>

            <!-- Modal Footer -->
            <div class="modal-footer justify-content-end">
                <button type="button" class="btn btn-secondary me-2" data-bs-dismiss="modal">Close</button>
                <button type="button" onclick="deleteInvoice()" class="btn btn-danger">Confirm</button>
            </div>
        </div>
    </div>
</div>
<div class="modal fade" id="copyOrderItemsModal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">

            <div class="modal-header">
                <h5 class="modal-title" id="exampleModalLabel">Copy Order Items to an Existing Order</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <input type="hidden" disabled id="this_order_id" class="form-control">
            <div class="modal-body">
                <div class="row">
                    <label for="search_order_copy" class="col-sm-4 col-form-label">Search Existing Order</label>
                    <div class="col-sm-8">
                        <input type="text" id="search_order_copy" class="form-control" placeholder="Order ID, Customer Order#">
                    </div>
                </div>
                <div class="row mt-2">
                    <label for="order_id" class="col-sm-4 col-form-label">Order ID</label>
                    <div class="col-sm-8">
                        <input type="text" disabled id="copy_order_id" class="form-control">
                    </div>
                </div>
                <div class="row mt-2">
                    <label for="order_customer" class="col-sm-4 col-form-label">Customer</label>
                    <div class="col-sm-8">
                        <input type="text" disabled id="order_customer" class="form-control">
                    </div>
                </div>
                <div class="row mt-2">
                    <label for="customer_order" class="col-sm-4 col-form-label">Order#</label>
                    <div class="col-sm-8">
                        <input type="text" disabled id="customer_order" class="form-control">
                    </div>
                </div>

                <div class="card mt-4">
                    <div class="card-header py-2">
                        <h6 class="mb-0">Items in Selected Order</h6>
                    </div>
                    <div class="card-body py-2 px-3">
                        <div class="table-responsive mb-0">
                            <table class="table table-sm table-bordered mb-0">
                                <thead>
                                    <tr>
                                        <th>Part Number</th>
                                        <th>Description</th>
                                    </tr>
                                </thead>
                                <tbody id="order_items_body">
                                    <!-- Filled dynamically -->
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" onclick="copyOrderItemsButton()" class="btn btn-primary">Copy</button>
            </div>

        </div>
    </div>
</div>
<div class="modal fade" id="copyOrdePurModal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="exampleModalLabel">Copy Items to PO</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" disabled id="this_order_id_pid" class="form-control mb-3">
                <div class="row mb-3">
                    <label for="search_pi_copy" disabled class="col-sm-4 col-form-label">Append to Existing PO</label>
                    <div class="col-sm-8">
                        <input type="text" id="search_pi_copy" disabled class="form-control" placeholder="Search ID, Vendor Order#">
                    </div>
                </div>
                <div class="row mb-3">
                    <div class="col-12 text-center">
                        <span class="fw-bold">Create PO for vendor</span>
                    </div
                </div>
                <div class="row mb-3">
                    <label for="search_vendor" class="col-sm-4 col-form-label">Search Vendor</label>
                    <div class="col-sm-8">
                        <select id="search_vendor" class="form-select">
                            <option value="">-- Select Vendor --</option>
                        </select>
                    </div>
                    <input type="hidden" id="po_id">
</div>

            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" onclick="copyOrderPurchaseButton()" class="btn btn-primary">Copy</button>
            </div>
        </div>
    </div>
</div>
<script type="text/javascript" src="includes_pages/admin_orders/scripts.js?n=<? echo date('h:i');?>"></script> 
