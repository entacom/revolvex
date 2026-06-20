<style>
.ui-autocomplete {
    z-index: 1060  !important; /* Bootstrap modal z-index is 1040 */
}


</style>
<main id="main" class="main">
    <div class="d-none d-lg-block">
        <ul class="nav nav-tabs nav-underline custom-tabs" id="myTab" role="tablist">
            <li class="nav-item" role="presentation">
                <a class="nav-link active" id="home-tab" data-bs-toggle="tab" onclick="Loadtab('home')" role="tab" aria-controls="home" aria-selected="true">
                    <i class='bx bxs-home'></i> Home
                </a>
            </li>
        </ul>
    </div>
    <div id="jobs_tab_body"></div>

    <div class="d-none d-lg-block">
        <ul class="nav nav-tabs nav-underline custom-tabs" id="myTab" role="tablist">
            <li class="nav-item" role="presentation">
                <a class="nav-link active" id="ordered_items-tab" data-bs-toggle="tab" onclick="LoadSubtab('ordered_items')" role="tab" aria-controls="ordered_items" aria-selected="true">
                    <i class='bx bxs-cart'></i> Purchase Order
                </a>
            </li>

            <li class="nav-item" role="presentation">
                <a class="nav-link" id="order_bill-tab" data-bs-toggle="tab" onclick="LoadSubtab('order_bill')" role="tab" aria-controls="order_bill" aria-selected="false">
                    <i class='bx bxs-receipt'></i> Receive Items
                </a>
            </li>
            <li class="nav-item" role="presentation">
                <a class="nav-link" id="order_invoice-tab" data-bs-toggle="tab" onclick="LoadSubtab('order_invoice')" role="tab" aria-controls="order_invoice" aria-selected="false">
                    <i class='bx bxs-receipt'></i> Invoice
                </a>
            </li>
            <li class="nav-item" role="presentation">
                <a class="nav-link" id="purchase_activity-tab" data-bs-toggle="tab" onclick="LoadSubtab('purchase_activity')" role="tab" aria-controls="purchase_activity" aria-selected="false">
                    <i class='bx bx-line-chart'></i> Activity
                </a>
            </li>
        </ul>
    </div>
    <div id="sub_items_body"></div>
</main>

<div class="modal fade" id="ProcessPurchaseModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Process Purchase Order</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <input type="hidden" id="process_purchase_pid">
            <div class="modal-body">
                <div class="list-group process-order-list">
                    <div class="list-group-item d-flex justify-content-between align-items-center gap-3">
                        <div>
                            <div class="fw-bold">Delivery Docket</div>
                            <div class="small text-muted">Print the purchase delivery docket.</div>
                            <div class="small text-success purchase-process-summary" data-purchase-process-summary="purchase_delivery_docket_printed"></div>
                        </div>
                        <button type="button" class="btn btn-sm btn-outline-secondary" onclick="ProcessPurchasePrintDelivery()">Print</button>
                    </div>
                    <div class="list-group-item d-flex justify-content-between align-items-center gap-3">
                        <div class="flex-grow-1">
                            <div class="fw-bold">Attach Files</div>
                            <div class="small text-muted">Attach supplier files such as punching detail or order forms to the PO email.</div>
                            <div class="small text-muted" id="purchase_process_attachment_summary">No files selected.</div>
                        </div>
                        <button type="button" class="btn btn-sm btn-outline-secondary" onclick="ProcessPurchaseChooseAttachments()">Choose</button>
                    </div>
                    <div class="list-group-item d-flex justify-content-between align-items-center gap-3">
                        <div>
                            <div class="fw-bold">Order Confirmation Required</div>
                            <div class="small text-muted">Mark this PO as needing confirmation from the supplier.</div>
                            <div class="small text-success purchase-process-summary" data-purchase-process-summary="purchase_confirmation_requested"></div>
                            <div class="small text-danger d-none" id="purchase_confirmation_overdue_notice">Confirmation has not been received within 48 hours.</div>
                        </div>
                        <div class="form-check form-switch m-0">
                            <input class="form-check-input" type="checkbox" id="purchase_confirmation_requested_checkbox" onchange="ProcessPurchaseConfirmationChanged()">
                        </div>
                    </div>
                    <div class="list-group-item d-flex justify-content-between align-items-start gap-3">
                        <div class="flex-grow-1">
                            <div class="fw-bold">Confirmation Received</div>
                            <div class="small text-muted">Upload the supplier confirmation and enter the estimated arrival date.</div>
                            <div class="row g-2 mt-1">
                                <div class="col-md-5">
                                    <input type="date" id="purchase_confirmation_eta" class="form-control form-control-sm">
                                </div>
                                <div class="col-md-7">
                                    <input type="file" id="purchase_confirmation_file" class="form-control form-control-sm" accept=".pdf,.jpg,.jpeg,.png,.doc,.docx,.xls,.xlsx,.csv,.txt">
                                </div>
                            </div>
                            <div class="small text-success purchase-process-summary mt-1" data-purchase-process-summary="purchase_confirmation_received"></div>
                            <div class="small text-muted" id="purchase_confirmation_file_summary"></div>
                        </div>
                        <button type="button" class="btn btn-sm btn-success mt-4" onclick="ProcessPurchaseSaveConfirmation()">Save</button>
                    </div>
                    <div class="list-group-item d-flex justify-content-between align-items-center gap-3 bg-light">
                        <div>
                            <div class="fw-bold">Purchase Order</div>
                            <div class="small text-muted">Print or email the purchase order and selected attachments.</div>
                            <div class="small text-success purchase-process-summary" data-purchase-process-summary="purchase_order_printed"></div>
                            <div class="small text-success purchase-process-summary" data-purchase-process-summary="purchase_order_emailed"></div>
                        </div>
                        <div class="btn-group">
                            <button type="button" class="btn btn-sm btn-outline-secondary" onclick="ProcessPurchasePrintOrder()">Print</button>
                            <button type="button" class="btn btn-sm btn-outline-primary" onclick="ProcessPurchaseEmailOrder()">Email</button>
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



<div class="modal fade" id="add_item_order_modal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-body">
                <input name="has_items_id_tag" type="hidden" id="has_items_id_tag" >
                <div class="mb-3">
                    <label for="add_item_part_number" class="form-label">Add Items to Order</label>
                    <div class="input-group">
                        <input name="add_item_part_number" type="text" id="add_item_part_number" class="form-control" placeholder="Part Number">
                    </div>
                </div>
                <div class="mb-3" id="description_field" style="display: none;">
                    <label for="add_item_description" class="form-label">Description</label>
                    <div class="input-group">
                        <!-- SKIP this field in tab order -->
                        <input name="add_item_description" tabindex="-1" type="text" id="add_item_description" class="form-control">
                    </div>
                </div>
                <div class="mb-3" id="qty_field" style="display: none;">
                    <label for="add_item_qty" class="form-label">Qty</label>
                    <div class="input-group">
                        <input name="add_item_qty" type="text" id="add_item_qty" class="form-control">
                    </div>
                </div>
               
                <label for="add_item_rate" class="form-label">Price Ex</label>
                <div class="input-group">
                    <input name="add_item_rate" type="number" id="add_item_rate" class="form-control">
                    <div id="price_field" style="display: none;">
                        <span class="input-group-append">
                            <button type="button" class="btn btn-outline-secondary" onclick="AddOrderItems()">Add</button>
                        </span>
                    </div>
                </div>
                
                <div class="mb-3" id="units_field" style="display: none;">
                    <label for="add_item_units" class="form-label"> Quantity x Length</label>
                    <div class="input-group">
                        <input name="add_item_units" type="number" id="add_item_units" class="form-control" placeholder="Qty">
                        <button type="button" class="btn btn-secondary" tabindex="-1">X</button>
                        <input name="add_item_units_qty" type="number" id="add_item_units_qty" class="form-control" placeholder="Length Lm">
                        <input name="add_item_mark" type="text" id="add_item_mark" class="form-control" placeholder="Mark">
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

<!-- Modal Structure -->
<div class="modal fade" id="edit_item_modal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-body">
                <input type="hidden" id="edit_item_id">
                
                <div class="mb-3">
                    <label for="edit_part_number" class="form-label">Edit Purchase Part#</label>
                    <div class="input-group">
                        <input name="edit_part_number" type="text" id="edit_part_number" class="form-control" placeholder="Part Number">
                    </div>
                </div>
                <div class="mb-3">
                    <label for="edit_description" class="form-label">Description</label>
                    <div class="input-group">
                        <input name="edit_description" type="text" id="edit_description" class="form-control">
                    </div>
                </div>
                <div id="has_item_div">
                 <div class="mb-3 row">
                    <div class="col-md-4">
                        <label for="edit_qty" class="form-label">Qty</label>
                        <input name="edit_qty" type="text" id="edit_qty" class="form-control">
                    </div>
                </div>
                    </div>
                <div class="mb-3 row">
                    <div class="col-md-4">
                        <label for="edit_rate" class="form-label">Price Ex</label>
                        <input name="edit_rate" type="number" id="edit_rate" class="form-control">
                    </div>
                </div>
                
            </div>
            <div class="modal-footer d-flex justify-content-between">
                <button type="button" onclick="deleteOrderItem()" class="btn btn-danger">Delete</button>
                <button type="button" onclick="SaveItem()" class="btn btn-secondary">Save</button>
            </div>
        </div>
    </div>
</div>
<div class="modal fade" id="edit_sub_item_modal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-body">
                <input type="hidden" id="edit_sub_item_id" >
        
                <label for="edit_sub_mark" class="form-label">Mark</label>
                <div class="input-group">
                    <input name="edit_sub_mark" tabindex="1" type="text" id="edit_sub_mark" class="form-control">
                </div>
              
                <label for="edit_sub_qty" class="form-label"> Quantity x Length</label>
                <div class="input-group">
                    <input name="edit_sub_qty" type="number" id="edit_sub_qty" class="form-control" placeholder="Qty">
                    <button type="button" class="btn btn-secondary" tabindex="-1">X</button>
                    <input name="edit_sub_qty_unit" type="number" id="edit_sub_qty_unit" class="form-control" placeholder="Length Lm">
                    <button type="button" class="btn btn-outline-secondary" onclick="SaveSubItem()">Save</button>
                </div>
            </div>
            <div class="modal-footer d-flex justify-content-between">
                <!-- Left side delete button -->
                <button type="button" class="btn btn-danger" onclick="deleteOrderSubItem()">Delete</button>

                <!-- Right side close button -->
                <button type="button" class="btn btn-secondary" tabindex="-1" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Structure -->
<div class="modal fade" id="edit_bill_sub_item_modal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-body">
				<input type="hidden" id="edit_bill_sub_item_id" >
                <label for="edit_sub_mark" class="form-label">Mark</label>
                    <div class="input-group">
                        <input name="edit_bill_sub_mark" tabindex="1" type="text" id="edit_bill_sub_mark" class="form-control">
                    </div>
                    <label for="add_add_bill_rate_units" class="form-label"> Quantity x Length</label>
                    <div class="input-group">
                        <input name="edit_bill_sub_qty" type="number" id="edit_bill_sub_qty" class="form-control" placeholder="Qty">
                        <button type="button" class="btn btn-secondary" tabindex="-1">X</button>
                        <input name="edit_bill_sub_qty_unit" type="number" id="edit_bill_sub_qty_unit" class="form-control" placeholder="Length Lm">
                       
                        <button type="button" class="btn btn-outline-secondary" onclick="SaveBillSubItem()">Save</button>
                    </div>
            </div>
            <div class="modal-footer">
                <button type="button" onclick="DeleteBillSubItem()" class="btn btn-danger me-auto">Delete</button>
                <button type="button" class="btn btn-secondary" tabindex="-1" data-bs-dismiss="modal">Close</button>
            </div>

        </div>
    </div>
</div>
<!-- Modal Structure -->
<div class="modal fade" id="add_bill_item_modal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-body">
                <input name="has_bill_id_tag" type="hidden" id="has_bill_id_tag" >
                <div class="mb-3">
                    <label for="add_bill_part_number" class="form-label">Add Items to Bill</label>
                    <div class="input-group">
                        <input name="add_bill_part_number" type="text" id="add_bill_part_number" class="form-control" placeholder="Part Number" tabindex="2">
                    </div>
                </div>
                <div class="mb-3" id="description_bill_field" style="display: none;">
                    <label for="add_item_description" class="form-label">Description</label>
                    <div class="input-group">
                        <input name="add_bill_description" tabindex="3" type="text" id="add_bill_description" class="form-control">
                    </div>
                </div>
                <div class="mb-3" id="serial_field" style="display: none;">
                    <label for="add_bill_serial_number" class="form-label">Serial Number</label>
                    <div class="input-group">
                        <input name="add_bill_serial_number" tabindex="4" type="text" id="add_bill_serial_number" class="form-control">
                    </div>
                </div>
                <div class="mb-3" id="qty_bill_field" style="display: none;">
                    <label for="add_bill_qty" class="form-label">Qty</label>
                    <div class="input-group">
                        <input name="add_bill_qty" tabindex="5" type="text" id="add_bill_qty" class="form-control">
                    </div>
                </div>

                <div class="mb-3">
                    <label for="add_bill_rate" class="form-label">Price Ex</label>
                    <div class="input-group">
                        <input name="add_bill_rate" tabindex="6" type="number" id="add_bill_rate" class="form-control">
                        <div id="price_bill_field" style="display: none;">
                            <span class="input-group-append">
                                <button type="button" class="btn btn-outline-secondary" onclick="AddBillItems()" tabindex="7">Add</button>
                            </span>
                        </div>
                    </div>
                </div>

                <div class="mb-3" id="units_bill_field" style="display: none;">
                     <label for="edit_sub_mark" class="form-label">Mark</label>
                    <div class="input-group">
                        <input name="add_add_mark" tabindex="1" type="text" id="add_add_mark" class="form-control">
                    </div>
                    
                    <label for="qty_le" class="form-label"> Quantity x Length</label>
                    <div class="input-group">
                        <input name="add_item_units" type="number" id="add_add_bill_rate_units" class="form-control" placeholder="Qty" tabindex="8">
                        <button type="button" class="btn btn-secondary" tabindex="-1">X</button>
                        <input name="add_item_units_qty" type="number" id="add_add_bill_rate_units_qty" class="form-control" placeholder="Length Lm" tabindex="9">
                        <button type="button" class="btn btn-outline-secondary" onclick="AddBillItems()" tabindex="10">Add</button>
                    </div>
                </div>
                <div id="site_message_modal"></div>
                <div id="has_sub_items"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" tabindex="11" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Structure -->
<div class="modal fade" id="edit_bill_item_modal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-body">
                <input type="hidden" id="edit_bill_item_id">
                
                <div class="mb-3">
                    <label for="edit_part_number" class="form-label">Edit Bill Item</label>
                    <div class="input-group">
                        <input name="edit_billpart_number" type="text" id="edit_bill_part_number" class="form-control" placeholder="Part Number">
                    </div>
                </div>
                <div class="mb-3">
                    <label for="edit_description" class="form-label">Description</label>
                    <div class="input-group">
                        <input name="edit_bill_description" type="text" id="edit_bill_description" class="form-control">
                    </div>
                </div>
				<div class="mb-3">
                    <label for="edit_description" class="form-label">Serial Number</label>
                    <div class="input-group">
                        <input name="edit_bill_serial_number" type="text" id="edit_bill_serial_number" class="form-control">
                    </div>
                </div>
                
                <div class="mb-3 row">

                    <div class="col-md-4">
                        <label for="edit_qty" class="form-label">Qty</label>
                        <input name="edit_bill_qty" type="text" id="edit_bill_qty" class="form-control">
                    </div>
                    <div class="col-md-4">
                        <label for="edit_rate" class="form-label">Rate</label>
                        <input name="edit_bill_rate" type="text" id="edit_bill_rate" class="form-control">
                    </div>
                </div>
                
            </div>
            <div class="modal-footer d-flex justify-content-between">
                <button type="button" onclick="DeleteBillItem()" class="btn btn-danger">Delete</button>
                <button type="button" onclick="SaveBillItem()" class="btn btn-secondary">Save</button>
            </div>
        </div>
    </div>
</div>


<!-- Modal Structure -->

<div class="modal fade" id="email_purchase_order" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <!-- Modal Header -->
            <div class="modal-header">
                <h5 class="modal-title">Email Order</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <input name="email_pid" type="hidden" id="email_pid" class="form-control">
            <!-- Modal Body -->
            <div class="modal-body">
                <div class="row mb-3">
                    <div class="col-12">
                        <label for="edit_qty" class="form-label">Email</label>
                        <input name="email_address_po1" type="text" id="email_address_po1" class="form-control">
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
                        <label for="purchase_email_attachments" class="form-label">Attachments (optional)</label>
                        <input name="purchase_email_attachments[]" type="file" id="purchase_email_attachments" class="form-control" multiple>
                        <div class="form-text">Selected files are attached to this email only.</div>
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

<div class="modal fade" id="receive_item_modal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <!-- Modal Header -->
            <div class="modal-header">
                <h5 class="modal-title">Receive Order</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
         
            <!-- Modal Body -->
            <div class="modal-body">
                <div class="row mb-3">
                    <div class="col-12">
                        <label for="edit_qty" class="form-label">Receive Date</label>
                        <input name="order_receive_date" type="text" id="order_receive_date" class="form-control">
                    </div>
                </div>

                <div class="row">
                    <div class="col-12">
                        <label for="edit_rate" class="form-label">Reference (Docket#)</label>
                        <input name="order_receive_ref" type="text" id="order_receive_ref" class="form-control">
                    </div>
                </div>
                 <div class="row">
                    <div class="col-12">
                        <label for="edit_rate" class="form-label">Receive Notes</label>
                        <textarea name="order_receive_note" type="text" id="order_receive_note" class="form-control"></textarea>
                    </div>
                </div>
            </div>

            <!-- Modal Footer -->
            <div class="modal-footer d-flex justify-content-between">
                    <button type="button" onclick="reverseReceiveItems()" class="btn btn-danger">Reverse Receive</button>

                    <div>
                        <button type="button" class="btn btn-secondary me-2" data-bs-dismiss="modal">Close</button>
                        <button type="button" onclick="receiveItems()" class="btn btn-primary">Save</button>
                    </div>
                </div>
        </div>
    </div>
</div>
<div class="modal fade" id="delete_invoice_modal" tabindex="-1" aria-labelledby="deleteInvoiceLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">

      <!-- Modal Header -->
      <div class="modal-header">
        <h5 class="modal-title" id="deleteInvoiceLabel">Delete invoice</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>

      <input name="process_pid" type="hidden" id="process_pid" class="form-control">

      <!-- Modal Body -->
      <div class="modal-body">
        <p class="mb-2">
          Are you sure you want to delete this invoice? This action may not be reversible.
        </p>



        <div id="invoice_message_modal" class="mt-3"></div>
      </div>

      <!-- Modal Footer -->
      <!-- Delete LEFT, Close RIGHT -->
      <div class="modal-footer justify-content-between">
        <button type="button" class="btn btn-danger" onclick="deleteInvoice()">
          Delete
        </button>
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
          Close
        </button>
      </div>

    </div>
  </div>
</div>

<div class="modal fade" id="receive_invoice_modal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <!-- Modal Header -->
            <div class="modal-header">
                <h5 class="modal-title">Receive Invoice</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
         <input name="process_pid" type="hidden" id="process_pid" class="form-control">
            <!-- Modal Body -->
            <div class="modal-body">
                <div class="row mb-3">
                    <div class="col-12">
                        <label for="edit_qty" class="form-label">Invoice Date</label>
                        <input name="invoice_date" type="text" id="invoice_date" class="form-control">
                    </div>
                </div>

                <div class="row">
                    <div class="col-12">
                        <label for="edit_rate" class="form-label">Invoice#</label>
                        <input name="invoice_ref" type="text" id="invoice_ref" class="form-control">
                    </div>
                </div>
                 <div class="row">
                    <div class="col-12">
                        <label for="edit_rate" class="form-label">Invoice Notes</label>
                        <textarea name="invoice_note" type="text" id="invoice_note" class="form-control"></textarea>
                    </div>
                </div>
            </div>
            <div id="invoice_message_modal"></div>
            <!-- Modal Footer -->
            <div class="modal-footer justify-content-end">
                <button type="button" class="btn btn-secondary me-2" data-bs-dismiss="modal">Close</button>
                <button type="button" onclick="receiveInvoice()" class="btn btn-primary">Save</button>
            </div>
        </div>
    </div>
</div>
<div class="modal fade" id="copyOrderItemsModal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">

            <div class="modal-header">
                <h5 class="modal-title" id="exampleModalLabel">Copy Items from an Existing Purchase</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <input type="text" disabled id="this_order_id" class="form-control">
            <div class="modal-body">
                <div class="row">
                    <label for="search_order_copy" class="col-sm-4 col-form-label">Search Existing Purchase</label>
                    <div class="col-sm-8">
                        <input type="text" id="search_order_copy" class="form-control" placeholder="Purchase ID">
                    </div>
                </div>
                <div class="row mt-2">
                    <label for="order_id" class="col-sm-4 col-form-label">Purchase ID</label>
                    <div class="col-sm-8">
                        <input type="text" disabled id="copy_order_id" class="form-control">
                    </div>
                </div>
                <div class="row mt-2">
                    <label for="ven_inv_number" class="col-sm-4 col-form-label">Vendor</label>
                    <div class="col-sm-8">
                        <input type="text" disabled id="vendor_name" class="form-control">
                    </div>
                </div>
                <div class="row mt-2">
                    <label for="customer_order" class="col-sm-4 col-form-label">Inv#</label>
                    <div class="col-sm-8">
                        <input type="text" disabled id="ven_inv_number" class="form-control">
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

<div class="modal fade" id="modalAddPurchaseActivity" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add Activity</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <label class="col-form-label">Activity</label>
                <textarea rows="4" id="add_purchase_activity_description" class="form-control form-control-sm" placeholder="Add a new activity for this purchase.."></textarea>
                <label class="col-form-label mt-2">Date</label>
                <input type="date" id="add_purchase_date_activity" class="form-control">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" onclick="AddPurchaseActivity()" class="btn btn-primary">Add</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalEditPurchaseActivity" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit Activity</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <input id="edit_purchase_activity_id" type="hidden">
                <label class="col-form-label">Activity</label>
                <textarea rows="4" id="edit_purchase_activity_description" class="form-control form-control-sm" placeholder="Add a new activity for this purchase.."></textarea>
                <label class="col-form-label mt-2">Date</label>
                <input type="text" id="edit_purchase_date_activity" class="form-control">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" onclick="SavePurchaseActivity()" class="btn btn-primary">Save</button>
            </div>
        </div>
    </div>
</div>



<script type="text/javascript" src="includes_pages/admin_purchasing/scripts.js?n=<? echo date('h:i');?>"></script> 
