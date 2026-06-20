<?

if(isset($_GET['vendor_id'])){

}
//order
if(isset($_GET['order_id'])){
    $id = $_GET['order_id'];

    $pickup_checkbox = getTabFieCol('pickup_checkbox', 'tblOrders', 'order_id', $id, $_SESSION['session_company_id']);
    $cash_sale = getTabFieCol('cash_sale', 'tblOrders', 'order_id', $id, $_SESSION['session_company_id']);
    $deliver_instructions = getTabFieCol('deliver_instructions', 'tblOrders', 'order_id', $id, $_SESSION['session_company_id']);

    if ($cash_sale == 1) {
        $customer = "Cash Sale";
        $customer_address = "";
        $customer_suburb = "";
        $customer_state = "";
        $customer_postcode = "";
        $customer_phone = "";
    } else {
        $customer = getTabFieCol('customer_company', 'tblOrders', 'order_id', $id, $_SESSION['session_company_id']);
        $customer_address = getTabFieCol('customer_address', 'tblOrders', 'order_id', $id, $_SESSION['session_company_id']);
        $customer_suburb = getTabFieCol('customer_suburb', 'tblOrders', 'order_id', $id, $_SESSION['session_company_id']);
        $customer_state = getTabFieCol('customer_state', 'tblOrders', 'order_id', $id, $_SESSION['session_company_id']);
        $customer_postcode = getTabFieCol('customer_postcode', 'tblOrders', 'order_id', $id, $_SESSION['session_company_id']);
        $customer_phone = getTabFieCol('customer_phone', 'tblOrders', 'order_id', $id, $_SESSION['session_company_id']);
    }

    if (!$pickup_checkbox) {
        $site_contact = getTabFieCol('site_contact', 'tblOrders', 'order_id', $id, $_SESSION['session_company_id']);
        $site_address = getTabFieCol('site_address', 'tblOrders', 'order_id', $id, $_SESSION['session_company_id']);
        $site_suburb = getTabFieCol('site_suburb', 'tblOrders', 'order_id', $id, $_SESSION['session_company_id']);
        $site_phone = getTabFieCol('site_phone', 'tblOrders', 'order_id', $id, $_SESSION['session_company_id']);
    } else {
        $site_contact = getTabFieCol('site_contact', 'tblOrders', 'order_id', $id, $_SESSION['session_company_id']);
        $site_address = getTabFieCol('site_suburb', 'tblOrders', 'order_id', $id, $_SESSION['session_company_id']);
        $site_suburb = "Pickup";
    }

    $company = getTableColField('company_name', 'tblCompany', 'id', $_SESSION['session_company_id']);
    $company_address = getTableColField('company_address', 'tblCompany', 'id', $_SESSION['session_company_id']);
    $company_suburb = getTableColField('company_suburb', 'tblCompany', 'id', $_SESSION['session_company_id']);
    $company_state = getTableColField('company_state', 'tblCompany', 'id', $_SESSION['session_company_id']);
    $company_postcode = getTableColField('company_postcode', 'tblCompany', 'id', $_SESSION['session_company_id']);
    $company_phone = getTableColField('company_phone', 'tblCompany', 'id', $_SESSION['session_company_id']);
    $order_status_id = getTabFieCol('order_status_id', 'tblOrders', 'order_id', $id, $_SESSION['session_company_id']);
    $order_status = getTabFieCol('description', 'tblOrderStatus', 'id', $order_status_id, $_SESSION['session_company_id']);

    $order_user_id = getTabFieCol('order_user_id', 'tblOrders', 'order_id', $id, $_SESSION['session_company_id']);
    $order_user = getTabFieCol('first_lastname', 'tblUsers', 'id', $order_user_id, $_SESSION['session_company_id']);
    $order_number = getTabFieCol('order_number', 'tblOrders', 'order_id', $id, $_SESSION['session_company_id']);
    $order_date = getTabFieCol('order_date', 'tblOrders', 'order_id', $id, $_SESSION['session_company_id']);

    $order_delivery_date_x = getTabFieCol('delivery_date', 'tblOrders', 'order_id', $id, $_SESSION['session_company_id']);
    $order_delivery_note = getTabFieCol('deliver_note', 'tblOrders', 'order_id', $id, $_SESSION['session_company_id']);
    if ($order_delivery_date_x) {
        $order_delivery_date = date('d-m-Y', $order_delivery_date_x);
        $order_delivery_short = date('D', $order_delivery_date_x);
    } else {
        $order_delivery_date = '';
        $order_delivery_short = '';
    }
    $order_delivery_rate = getTabFieCol('delivery_rate', 'tblOrders', 'order_id', $id, $_SESSION['session_company_id']);

    // Invoicing
        $raw_due_date     = getTabFieCol('transaction_due_date', 'tblInvoice', 'order_id', $id, $_SESSION['session_company_id']);
        $raw_invoice_date = getTabFieCol('invoice_date',          'tblInvoice', 'order_id', $id, $_SESSION['session_company_id']);

        // transaction_due_date may be a string date → convert to timestamp
        $invoice_due_date = !empty($raw_due_date) ? strtotime($raw_due_date) : time();

        // invoice_date should be a unix timestamp in DB; if empty, default to now
        $invoice_date = !empty($raw_invoice_date) ? (int)$raw_invoice_date : time();



    if (empty($invoice_due_date) ) {
        $invoice_due_date = time();
    }
        if ( empty($invoice_date)) {
        $invoice_date = time();
    }

    // banking
    $bank_account_name = getTableField('bank_account_name', 'tblCompany', $_SESSION['session_company_id']);
    $bank_name = getTableField('bank_name', 'tblCompany',  $_SESSION['session_company_id']);
    $bank_branch = getTableField('bank_branch', 'tblCompany', $_SESSION['session_company_id']);
    $bank_bsb = getTableField('bank_bsb', 'tblCompany', $_SESSION['session_company_id']);
    $bank_account = getTableField('bank_account', 'tblCompany',  $_SESSION['session_company_id']);
}

if(isset($_GET['pid'])){
// Purchases
$freight=getTableColField('freight', 'tblPurchaseOrders', 'id', $_GET['pid']);
$purchase_order_notes=getTableColField('order_notes', 'tblPurchaseOrders', 'id', $_GET['pid']);
$vendor=getTabFieCol('vendor_name', 'tblPurchaseOrders', 'id', $_GET['pid'], $_SESSION['session_company_id']);
$purchaser_user_id=getTabFieCol('purchaser_user_id', 'tblPurchaseOrders', 'id', $_GET['pid'], $_SESSION['session_company_id']);
$purchaser_user=getTabFieCol('first_lastname', 'tblUsers', 'id', $purchaser_user_id, $_SESSION['session_company_id']);
$vendor_address=getTabFieCol('vendor_address', 'tblPurchaseOrders', 'id', $_GET['pid'], $_SESSION['session_company_id']);
$vendor_suburb=getTabFieCol('vendor_suburb', 'tblPurchaseOrders', 'id', $_GET['pid'], $_SESSION['session_company_id']);
$vendor_state=getTabFieCol('vendor_state', 'tblPurchaseOrders', 'id', $_GET['pid'], $_SESSION['session_company_id']);
$vendor_postcode=getTabFieCol('vendor_postcode', 'tblPurchaseOrders', 'id', $_GET['pid'], $_SESSION['session_company_id']);
$vendor_phone=getTabFieCol('vendor_phone', 'tblPurchaseOrders', 'id', $_GET['pid'], $_SESSION['session_company_id']);
    $order_date_required_raw = getTabFieCol('order_date_required', 'tblPurchaseOrders', 'id', $_GET['pid'], $_SESSION['session_company_id']);
    $order_date_required = '';
    if (!empty($order_date_required_raw)) {
        $ts = (int)$order_date_required_raw;
        if ($ts > 0) {
            $order_date_required = date('d-m-Y', $ts);
        }
    }  
    
$delivery_address= getTableColField('delivery_address_line1', 'tblPurchaseOrders', 'id', $_GET['pid'], $_SESSION['session_company_id']);
    
if($delivery_address) {
    $company=getTableColField('company_name', 'tblCompany', 'id', $_SESSION['session_company_id']);
    $company_address=getTableColField('delivery_address_line1', 'tblPurchaseOrders', 'id', $_GET['pid']);
    $company_suburb=getTableColField('delivery_address_suburb', 'tblPurchaseOrders', 'id', $_GET['pid']);
    $company_state=getTableColField('delivery_state', 'tblPurchaseOrders', 'id', $_GET['pid']);
    $company_postcode=getTableColField('delivery_postcode', 'tblPurchaseOrders', 'id', $_GET['pid']);
    } 
    else{
        $company=getTableColField('company_name', 'tblCompany', 'id', $_SESSION['session_company_id']);
        $company_address=getTableColField('company_address', 'tblCompany', 'id', $_SESSION['session_company_id']);
        $company_suburb=getTableColField('company_suburb', 'tblCompany', 'id', $_SESSION['session_company_id']);
        $company_state=getTableColField('company_state', 'tblCompany', 'id', $_SESSION['session_company_id']);
        $company_postcode=getTableColField('company_postcode', 'tblCompany', 'id', $_SESSION['session_company_id']);
        $company_phone=getTableColField('company_phone', 'tblCompany', 'id', $_SESSION['session_company_id']);
        }
    }
?>