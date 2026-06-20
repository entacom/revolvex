<?php
session_start();
include("../../includes/common.php");
require_once '/home/revolvexcom/web_config_ft.php';

$company_id = $_SESSION['session_company_id'];

// Refresh token if expired or missing
$access_token_expire = getFieldColumn('access_token_expire', 'tblAccounting', 'company_id', $company_id);
$access_token = getFieldColumn('access_token', 'tblAccounting', 'company_id', $company_id);
$refresh_token = getFieldColumn('refresh_token', 'tblAccounting', 'company_id', $company_id);

if ((int)$access_token_expire - time() < 100 || empty($access_token)) {
    refreshXeroToken($refresh_token);
    sleep(1);
}

if (isset($_GET['get_new_access_token'])) {
    $code = $_GET['code'];
    $ch = curl_init();

    curl_setopt_array($ch, array(
        CURLOPT_URL => XERO_TOKEN_URL,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => http_build_query(array(
            'grant_type' => 'authorization_code',
            'code' => $code,
            'redirect_uri' => XERO_REDIRECT_URI,
            'client_id' => XERO_CLIENT_ID,
            'client_secret' => XERO_CLIENT_SECRET
        )),
        CURLOPT_HTTPHEADER => array('Content-Type: application/x-www-form-urlencoded')
    ));

    $response = curl_exec($ch);
    curl_close($ch);

    $tokenData = json_decode($response, true);
    echo "<pre>";
    print_r($tokenData);
    echo "</pre>";

    if (isset($tokenData['access_token']) && isset($tokenData['refresh_token'])) {
        $tenantId = getXeroTenantId($tokenData['access_token']);
        if ($tenantId) {
            updateXeroAccessToken($tokenData['access_token'], $tokenData['refresh_token'], $tenantId, $tokenData['expires_in']);
        } else {
            echo "Failed to retrieve Xero tenant ID.";
        }
    } else {
        echo "Error retrieving tokens.";
    }
}

function refreshXeroToken($refresh_token) {
    $ch = curl_init();

    curl_setopt_array($ch, array(
        CURLOPT_URL => XERO_TOKEN_URL,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => http_build_query(array(
            'grant_type' => 'refresh_token',
            'refresh_token' => $refresh_token,
            'client_id' => XERO_CLIENT_ID,
            'client_secret' => XERO_CLIENT_SECRET
        )),
        CURLOPT_HTTPHEADER => array('Content-Type: application/x-www-form-urlencoded')
    ));

    $response = curl_exec($ch);
    curl_close($ch);

    $tokenData = json_decode($response, true);
    if (isset($tokenData['access_token']) && isset($tokenData['refresh_token'])) {
        $tenantId = getXeroTenantId($tokenData['access_token']);
        if ($tenantId) {
            updateXeroAccessToken($tokenData['access_token'], $tokenData['refresh_token'], $tenantId, $tokenData['expires_in']);
        }
    }
}

function updateXeroAccessToken($access_token, $refresh_token, $tenant_id, $expires_in) {
    $company_id = $_SESSION['session_company_id'];
    $access_token_expire = time() + (int)$expires_in;

    $database = new Database();
    $conn = $database->connect();

    $query = "UPDATE tblAccounting SET 
        access_token = :access_token,
        refresh_token = :refresh_token,
        xero_tenant_id = :tenant_id,
        access_token_expire = :access_token_expire
        WHERE company_id = :company_id";

    $stmt = $conn->prepare($query);
    $stmt->bindParam(':access_token', $access_token, PDO::PARAM_STR);
    $stmt->bindParam(':refresh_token', $refresh_token, PDO::PARAM_STR);
    $stmt->bindParam(':tenant_id', $tenant_id, PDO::PARAM_STR);
    $stmt->bindParam(':access_token_expire', $access_token_expire, PDO::PARAM_INT);
    $stmt->bindParam(':company_id', $company_id, PDO::PARAM_INT);

    $stmt->execute();
    echo "Tokens saved successfully.";
}

function getXeroTenantId($access_token) {
    $ch = curl_init();

    curl_setopt_array($ch, array(
        CURLOPT_URL => 'https://api.xero.com/connections',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => array(
            'Authorization: Bearer ' . $access_token,
            'Accept: application/json'
        )
    ));

    $response = curl_exec($ch);
    curl_close($ch);

    $connections = json_decode($response, true);

    if (isset($connections[0]['tenantId'])) {
        return $connections[0]['tenantId'];
    }

    return null;
}
if (isset($_GET['get_all_accounts'])) {
    $company_id = $_SESSION['session_company_id'];
    $classification = $_GET['classification']; // e.g., "ASSET", "LIABILITY", etc.

    $database = new Database();
    $conn = $database->connect();

    $query = "SELECT access_token, xero_tenant_id FROM tblAccounting WHERE company_id = :company_id";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':company_id', $company_id, PDO::PARAM_INT);
    $stmt->execute();
    $data = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$data || empty($data['access_token']) || empty($data['xero_tenant_id'])) {
        echo json_encode(['success' => false, 'message' => 'Xero token or tenant ID missing']);
        exit;
    }

    $access_token = $data['access_token'];
    $tenant_id = $data['xero_tenant_id'];

    $curl = curl_init();
    curl_setopt_array($curl, array(
        CURLOPT_URL => XERO_API_URL . "Accounts",
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CUSTOMREQUEST => "GET",
        CURLOPT_HTTPHEADER => array(
            "Authorization: Bearer $access_token",
            "Xero-tenant-id: $tenant_id",
            "Accept: application/json"
        ),
    ));

    $response = curl_exec($curl);
    curl_close($curl);

    $result = json_decode($response, true);

    if (isset($result['Accounts']) && is_array($result['Accounts'])) {
            $filteredAccounts = array_filter($result['Accounts'], function($account) use ($classification) {
                return isset($account['Status']) && $account['Status'] === 'ACTIVE'
                    && isset($account['Type']) && strtoupper($account['Type']) === strtoupper($classification);
            });


        $accounts = array_map(function($account) {
            return [
                'label' => $account['Name'] . ' (' . $account['Code'] . ')',
                'uid' => $account['AccountID'],
                'classification' => $account['Class'],
                'display_id' => $account['Code'],
                'description' => $account['Description']
            ];
        }, $filteredAccounts);

        header('Content-Type: application/json');
        echo json_encode($accounts);
    } else {
        echo json_encode(['success' => false, 'message' => 'No accounts returned from Xero']);
    }
}
if (isset($_GET['get_all_tax'])) {
    $company_id = $_SESSION['session_company_id'];

    $database = new Database();
    $conn = $database->connect();

    $query = "SELECT access_token, xero_tenant_id FROM tblAccounting WHERE company_id = :company_id";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':company_id', $company_id, PDO::PARAM_INT);
    $stmt->execute();
    $data = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$data || empty($data['access_token']) || empty($data['xero_tenant_id'])) {
        echo json_encode(['success' => false, 'message' => 'Xero token or tenant ID missing']);
        exit;
    }

    $access_token = $data['access_token'];
    $tenant_id = $data['xero_tenant_id'];

    $curl = curl_init();
    curl_setopt_array($curl, array(
        CURLOPT_URL => XERO_API_URL . "TaxRates",
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CUSTOMREQUEST => "GET",
        CURLOPT_HTTPHEADER => array(
            "Authorization: Bearer $access_token",
            "Xero-tenant-id: $tenant_id",
            "Accept: application/json"
        ),
    ));

    $response = curl_exec($curl);
    curl_close($curl);

    header('Content-Type: application/json');
    echo $response;
}
if (isset($_GET['xero_get_customers'])) {
    $company_id = $_SESSION['session_company_id'];
    $search_field = urlencode(trim($_GET['term']));

    $access_token = getFieldColumn('access_token', 'tblAccounting', 'company_id', $company_id);
    $tenant_id = getFieldColumn('xero_tenant_id', 'tblAccounting', 'company_id', $company_id);

    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => "https://api.xero.com/api.xro/2.0/Contacts?searchTerm=$search_field&order=Name",
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            "Authorization: Bearer $access_token",
            "Xero-tenant-id: $tenant_id",
            "Accept: application/json"
        ]
    ]);

    $response = curl_exec($ch);
    curl_close($ch);

    $result = json_decode($response, true);
    $return_arr = [];

    if (isset($result['Contacts']) && is_array($result['Contacts'])) {
        foreach ($result['Contacts'] as $item) {
            $is_individual = !empty($item['FirstName']) || !empty($item['LastName']);
            $row_array = [
                'value' => $item['Name'],
                'company' => $item['Name'],
                'firstname' => $item['FirstName'] ?? '',
                'lastname' => $item['LastName'] ?? '',
                'uid' => $item['ContactID'],
                'display_id' => $item['AccountNumber'] ?? '',
                'phone1' => $item['Phones'][0]['PhoneNumber'] ?? '',
                'email' => $item['EmailAddress'] ?? '',
                'address' => $item['Addresses'][0]['AddressLine1'] ?? '',
                'suburb' => $item['Addresses'][0]['City'] ?? '',
                'state' => $item['Addresses'][0]['Region'] ?? '',
                'postcode' => $item['Addresses'][0]['PostalCode'] ?? '',
                'is_individual' => $is_individual,
                'item_price_level' => '',
                'discount' => $item['Discount'] ?? 0,
                'payment_terms' => $item['PaymentTerms']['Sales'] ?? []
            ];


            $return_arr[] = $row_array;
        }
    }

    header('Content-Type: application/json');
    echo json_encode($return_arr);
}
if (isset($_GET['xero_get_suppliers'])) {
    $company_id = $_SESSION['session_company_id'];
    $search_field = $_GET['term'];

    $database = new Database();
    $conn = $database->connect();

    $query = "SELECT access_token, xero_tenant_id FROM tblAccounting WHERE company_id = :company_id";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':company_id', $company_id, PDO::PARAM_INT);
    $stmt->execute();
    $data = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$data || empty($data['access_token']) || empty($data['xero_tenant_id'])) {
        echo json_encode([]);
        exit;
    }

    $access_token = $data['access_token'];
    $tenant_id = $data['xero_tenant_id'];

    $url = "https://api.xero.com/api.xro/2.0/Contacts?searchTerm=" . urlencode($search_field);

    $curl = curl_init();
    curl_setopt_array($curl, array(
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CUSTOMREQUEST => "GET",
        CURLOPT_HTTPHEADER => array(
            "Authorization: Bearer $access_token",
            "Xero-tenant-id: $tenant_id",
            "Accept: application/json"
        )
    ));

    $response = curl_exec($curl);
    curl_close($curl);

    $result = json_decode($response, true);
    $return_arr = [];

    if (isset($result['Contacts'])) {
        foreach ($result['Contacts'] as $contact) {
            if (!empty($contact['IsSupplier']) && $contact['IsSupplier'] === true) {
                $address = $contact['Addresses'][0] ?? [];

                $payment_terms = $contact['PaymentTerms'] ?? [];
                $bills_due = $payment_terms['Bills']['Day'] ?? null;
                $bills_due_type = $payment_terms['Bills']['Type'] ?? null;

                $row_array = array(
                    'value' => $contact['Name'],
                    'company' => $contact['Name'],
                    'uid' => $contact['ContactID'],
                    'address' => $address['AddressLine1'] ?? '',
                    'suburb' => $address['City'] ?? '',
                    'state' => $address['Region'] ?? '',
                    'postcode' => $address['PostalCode'] ?? '',
                    'phone1' => $address['Phone'] ?? '',
                    'email' => $contact['EmailAddress'] ?? '',
                    'payment_terms_day' => $bills_due,
                    'payment_terms_type' => $bills_due_type
                );


                $return_arr[] = $row_array;
            }
        }
    }

    echo json_encode($return_arr);
}
if (isset($_GET['create_invoice'])) {
    $order_id = $_POST['invoice_id'];
    $invoice_date = $_POST['invoice_date'];
    $company_id = $_SESSION['session_company_id'];

    // Fetch and decode payment terms
    $payment_terms_json = getTabFieCol('payment_terms', 'tblOrders', 'order_id', $order_id, $company_id);
    $payment_terms = json_decode($payment_terms_json, true);

    $database = new Database();
    $conn = $database->connect();

    $query = "SELECT access_token, xero_tenant_id FROM tblAccounting WHERE company_id = :company_id";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':company_id', $company_id);
    $stmt->execute();
    $auth = $stmt->fetch(PDO::FETCH_ASSOC);

    $invoice_body = createXeroInvoicePayload($order_id, $invoice_date);

    $ch = curl_init();
    curl_setopt_array($ch, array(
        CURLOPT_URL => XERO_API_URL . "Invoices?summarizeErrors=false",
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode(['Invoices' => [$invoice_body]]),
        CURLOPT_HTTPHEADER => array(
            "Authorization: Bearer " . $auth['access_token'],
            "Xero-tenant-id: " . $auth['xero_tenant_id'],
            "Content-Type: application/json",
            "Accept: application/json"
        )
    ));

    $response = curl_exec($ch);
    curl_close($ch);

    $json = json_decode($response, true);
    $invoice = $json['Invoices'][0] ?? null;

    if ($invoice && isset($invoice['InvoiceID'])) {
        $invoice_id = $invoice['InvoiceID'];

        // Use calculateXeroDueDate to get due date
        $terms_day = $payment_terms['Day'] ?? 0;
        $terms_type = $payment_terms['Type'] ?? null;
        $due_date_str = calculateXeroDueDate($invoice_date, $terms_day, $terms_type);
        $due_date = date('d-m-Y', strtotime($due_date_str));
 

        $invoice_date_epoch = strtotime($invoice_date);

        $sql = $conn->prepare("UPDATE tblInvoice SET invoice_date = :invoice_date, transaction_uid = :transaction_uid, transaction_invoice_date = :transaction_invoice_date, transaction_due_date = :transaction_due_date WHERE order_id = :order_id AND company_id = :company_id");
        $sql->bindParam(':transaction_uid', $invoice_id);
        $sql->bindParam(':transaction_invoice_date', $invoice_date_epoch);
        $sql->bindParam(':invoice_date', $invoice_date_epoch);
        $sql->bindParam(':transaction_due_date', $due_date);
        $sql->bindParam(':order_id', $order_id);
        $sql->bindParam(':company_id', $company_id);
        $sql->execute();

        // Update order status
        $statusUpdate = $conn->prepare("UPDATE tblOrders 
            SET order_status_id = 16 
            WHERE order_id = :order_id AND company_id = :company_id");
        $statusUpdate->bindParam(':order_id', $order_id);
        $statusUpdate->bindParam(':company_id', $company_id);
        $statusUpdate->execute();

        addOrderActivity(
            $order_id,
            $company_id,
            5,
            'Invoice processed: Xero invoice sent for ' . date('d-m-Y', $invoice_date_epoch),
            $_SESSION['session_user_id'],
            0
        );
    }

    echo json_encode($invoice);
}






if (isset($_GET['run_invoice_due_date_update'])) {
    updateInvoiceDueDates($_SESSION['session_company_id']);
    exit;
}

function updateInvoiceDueDates($company_id) {
    $database = new Database();
    $conn = $database->connect();

    $sql = $conn->prepare("
        SELECT o.order_id, o.payment_terms, i.invoice_date 
        FROM tblOrders o 
        INNER JOIN tblInvoice i ON o.order_id = i.order_id 
        WHERE o.company_id = :company_id 
          AND o.payment_terms IS NOT NULL 
          AND o.payment_terms != ''
    ");
    $sql->bindParam(':company_id', $company_id);
    $sql->execute();

    $rows = $sql->fetchAll(PDO::FETCH_ASSOC);
    $updated = 0;

    echo "<pre>";

    foreach ($rows as $row) {
        $order_id = $row['order_id'];
        $invoice_date_raw = $row['invoice_date'];
        $payment_terms_json = $row['payment_terms'];

        if (empty($invoice_date_raw)) {
            echo " Skipping order ID {$order_id} (missing invoice_date)\n";
            continue;
        }

        // Decode once
        $terms = json_decode($payment_terms_json, true);

        // If still a string (double-encoded), decode again
        if (is_string($terms)) {
            $terms = json_decode($terms, true);
        }

        if (!is_array($terms) || !isset($terms['Type'], $terms['Day'])) {
            echo "⛔ Skipping order ID {$order_id} (invalid payment_terms: {$payment_terms_json})\n";
            continue;
        }

        $terms_type = $terms['Type'];
        $terms_day = $terms['Day'];

        // Convert invoice_date to Y-m-d
        $invoice_date = is_numeric($invoice_date_raw)
            ? date('Y-m-d', $invoice_date_raw)
            : date('Y-m-d', strtotime($invoice_date_raw));

        // Calculate due date
        $due_date_str = calculateXeroDueDate($invoice_date, $terms_day, $terms_type);
        $due_date_formatted = date('d-m-Y', strtotime($due_date_str));

        // Update only the due date
        $update = $conn->prepare("
            UPDATE tblInvoice 
            SET transaction_due_date = :due_date 
            WHERE order_id = :order_id AND company_id = :company_id
        ");
        $update->execute([
            ':due_date' => $due_date_formatted,
            ':order_id' => $order_id,
            ':company_id' => $company_id
        ]);

        echo "✅ Order ID {$order_id} → Due Date: {$due_date_formatted} (Terms: {$terms_type}, Day: {$terms_day})\n";
        $updated++;
    }

    echo "\n✅ Total updated: {$updated}\n</pre>";
}



/*
if (isset($_GET['create_invoice'])) {
    $order_id = $_POST['invoice_id'];
    $invoice_date = $_POST['invoice_date'];
     $payment_terms   = getTabFieCol('payment_terms', 'tblOrders', 'order_id', $row['order_id'], $_SESSION['session_company_id']);
    $company_id = $_SESSION['session_company_id'];

    $database = new Database();
    $conn = $database->connect();

    $query = "SELECT access_token, xero_tenant_id FROM tblAccounting WHERE company_id = :company_id";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':company_id', $company_id);
    $stmt->execute();
    $auth = $stmt->fetch(PDO::FETCH_ASSOC);

    $invoice_body = createXeroInvoicePayload($order_id, $invoice_date);

    $ch = curl_init();
    curl_setopt_array($ch, array(
        CURLOPT_URL => XERO_API_URL . "Invoices?summarizeErrors=false",
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode(['Invoices' => [$invoice_body]]),
        CURLOPT_HTTPHEADER => array(
            "Authorization: Bearer " . $auth['access_token'],
            "Xero-tenant-id: " . $auth['xero_tenant_id'],
            "Content-Type: application/json",
            "Accept: application/json"
        )
    ));

    $response = curl_exec($ch);
    curl_close($ch);

    $json = json_decode($response, true);
    $invoice = $json['Invoices'][0] ?? null;

if ($invoice && isset($invoice['InvoiceID'])) {
    $invoice_id = $invoice['InvoiceID'];
    $due_date = strtotime($invoice['DueDate']);
    $invoice_date_epoch = $invoice_date;

    $sql = $conn->prepare("UPDATE tblInvoice SET invoice_date = :invoice_date, transaction_uid = :transaction_uid, transaction_invoice_date = :transaction_invoice_date, transaction_due_date = :transaction_due_date WHERE order_id = :order_id AND company_id = :company_id");
    $sql->bindParam(':transaction_uid', $invoice_id);
    $sql->bindParam(':transaction_invoice_date', $invoice_date_epoch);
    $sql->bindParam(':invoice_date', $invoice_date_epoch);
    $sql->bindParam(':transaction_due_date', $due_date);
    $sql->bindParam(':order_id', $order_id);
    $sql->bindParam(':company_id', $company_id);
    $sql->execute();

    //  Just this bit added
    $statusUpdate = $conn->prepare("UPDATE tblOrders 
        SET order_status_id = 16 
        WHERE order_id = :order_id AND company_id = :company_id");
    $statusUpdate->bindParam(':order_id', $order_id);
    $statusUpdate->bindParam(':company_id', $company_id);
    $statusUpdate->execute();
}


    echo json_encode($invoice);
}
*/
function createXeroInvoicePayload($order_id, $invoice_date) {
    $customer_uid = getFieldColumn('customer_uid', 'tblOrders', 'order_id', $order_id);
    $payment_terms_raw = getFieldColumn('payment_terms', 'tblOrders', 'order_id', $order_id);
    $first_decode = json_decode($payment_terms_raw, true);

        if (is_string($first_decode)) {
            // It was double-encoded
            $terms = json_decode($first_decode, true);
        } elseif (is_array($first_decode)) {
            // It was stored correctly
            $terms = $first_decode;
        } else {
            // Fallback
            $terms = [];
        }



    $order_number = getFieldColumn('order_number', 'tblOrders', 'order_id', $order_id);
    $freight = (float)getTabFieCol('delivery_rate', 'tblOrders', 'order_id', $order_id, $_SESSION['session_company_id']);

    $invoice_ts = strtotime($invoice_date);
    $due_ts = $invoice_ts; // Default fallback to invoice date

    if (!empty($terms) && isset($terms['Type'], $terms['Day'])) {
        switch ($terms['Type']) {
            case 'DAYSAFTERBILLDATE':
            case 'DAYSAFTERINVOICEDATE':
                $due_ts = strtotime("+{$terms['Day']} days", $invoice_ts);
                break;

            case 'OFFOLLOWINGMONTH':
                $month = (int)date('n', $invoice_ts) + 1;
                $year = (int)date('Y', $invoice_ts);
                if ($month > 12) {
                    $month = 1;
                    $year++;
                }
                $day = min((int)$terms['Day'], cal_days_in_month(CAL_GREGORIAN, $month, $year));
                $due_ts = mktime(0, 0, 0, $month, $day, $year);
                break;

            case 'OFCURRENTMONTH':
                $month = (int)date('n', $invoice_ts);
                $year = (int)date('Y', $invoice_ts);
                $day = min((int)$terms['Day'], cal_days_in_month(CAL_GREGORIAN, $month, $year));
                $due_ts = mktime(0, 0, 0, $month, $day, $year);
                break;

            default:
                $due_ts = $invoice_ts;
                break;
        }
    }

    return [
        'Type' => 'ACCREC',
        'Contact' => ['ContactID' => $customer_uid],
        'Date' => date('Y-m-d', $invoice_ts),
        'DueDate' => date('Y-m-d', $due_ts),
        'InvoiceNumber' => (string)$order_id,
        'Reference' => (string)$order_number,
        'Status' => 'AUTHORISED', // Change to 'AUTHORISED' when going live
        'LineAmountTypes' => 'Exclusive',
        'LineItems' => getXeroInvoiceLines($order_id),
        'SubTotal' => null,
        'TotalTax' => null,
        'Total' => null,
        'CurrencyCode' => 'AUD',
        'BrandingThemeID' => null,
        'Url' => null,
        'CurrencyRate' => null,
        'ContactGroup' => null,
        'HasAttachments' => false,
        'FullyPaidOnDate' => null,
        'AmountDue' => null,
        'AmountPaid' => null,
        'AmountCredited' => null,
        'SentToContact' => false,
        'ExpectedPaymentDate' => null,
        'PlannedPaymentDate' => null,
        'Freight' => ['Amount' => $freight],
    ];
}


function getXeroInvoiceLines($order_id) {
    $database = new Database();
    $conn = $database->connect();
    $stmt = $conn->prepare("SELECT description, qty, rate FROM tblInvoice WHERE order_id = :order_id AND company_id = :company_id");
    $stmt->bindValue(':order_id', $order_id);
    $stmt->bindValue(':company_id', $_SESSION['session_company_id']);
    $stmt->execute();

    $lines = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $qty = (float)$row['qty'];
        $rate = (float)$row['rate'];
        $total = $qty * $rate;
        $lines[] = [
    'Description' => $row['description'],
    'Quantity' => $qty,
    'UnitAmount' => $rate,
    'AccountCode' => getFieldColumn('receivable_account_code', 'tblAccounting', 'company_id', $_SESSION['session_company_id']),
    'TaxType' => getFieldColumn('receivable_account_tax_code', 'tblAccounting', 'company_id', $_SESSION['session_company_id']),
    // Let Xero calculate LineAmount from Quantity x UnitAmount
];

    }

    return $lines;
}

if (isset($_GET['create_xero_bill'])) {
    $pid = $_POST['pid'];
    $company_id = $_SESSION['session_company_id'];

    // Fetch auth tokens
    $database = new Database();
    $conn = $database->connect();
    $stmt = $conn->prepare("SELECT access_token, xero_tenant_id FROM tblAccounting WHERE company_id = :company_id");
    $stmt->bindParam(':company_id', $company_id);
    $stmt->execute();
    $auth = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$auth['access_token'] || !$auth['xero_tenant_id']) {
        echo json_encode(['error' => 'Missing Xero authentication']);
        exit;
    }

    // Prepare payload
    $supplier_uid = getFieldColumn('vendor_uid', 'tblPurchaseInvoice', 'pid', $pid);
    $vendor_name = getTableColField('vendor_name', 'tblPurchaseOrders', 'id', $pid);
    $invoice_ref = getTableColField('invoice_ref', 'tblPurchaseOrders', 'id', $pid);
    $payment_terms_day = getTableColField('payment_terms_day', 'tblPurchaseOrders', 'id', $pid);
    $payment_terms_type = getTableColField('payment_terms_type', 'tblPurchaseOrders', 'id', $pid);
    $invoice_date_raw = getTableColField('invoice_date', 'tblPurchaseOrders', 'id', $pid);
    $invoice_date = (is_numeric($invoice_date_raw) && $invoice_date_raw > 0)
    ? date('Y-m-d', $invoice_date_raw)
    : date('Y-m-d');


    // on card on  order creation
    
    $payload = [
        'Type' => 'ACCPAY',
        'Contact' => ['ContactID' => $supplier_uid],
        'Date' => $invoice_date,
        //'DueDate' => date('Y-m-d', strtotime('+30 days', strtotime($invoice_date))),
        'DueDate' => calculateXeroDueDate($invoice_date, $payment_terms_day, $payment_terms_type),

        'InvoiceNumber' => (string)$invoice_ref,
        'LineAmountTypes' => 'Exclusive',
        'Status' => 'AUTHORISED',
        //'Status' => 'DRAFT',
        'LineItems' => [],
    ];

$stmt = $conn->prepare("SELECT description, qty_total, rate, part_number FROM tblPurchaseInvoice WHERE pid = :pid AND company_id = :company_id");
$stmt->bindValue(':pid', $pid);
$stmt->bindValue(':company_id', $company_id);
$stmt->execute();

while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $group_id = getFieldColumn('group_id', 'tblInventory', 'part_number', $row['part_number']);
    $account_code = getFieldColumn('account_code', 'tblInventoryGroup', 'id', $group_id);

    // fallback
    if (empty($account_code) || !is_numeric($account_code)) {
        $account_code = '631';
    }

    $payload['LineItems'][] = [
        'Description' => $row['description'],
        'Quantity' => (float)$row['qty_total'],
        'UnitAmount' => (float)$row['rate'],
        'AccountCode' => $account_code,
        'TaxType' => getFieldColumn('payable_account_tax_code', 'tblAccounting', 'company_id', $company_id),
    ];
}

    // Send to Xero
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => XERO_API_URL . "Invoices?summarizeErrors=false",
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode(['Invoices' => [$payload]]),
        CURLOPT_HTTPHEADER => [
            "Authorization: Bearer {$auth['access_token']}",
            "Xero-tenant-id: {$auth['xero_tenant_id']}",
            "Content-Type: application/json"
        ]
    ]);
    $response = curl_exec($ch);
    curl_close($ch);

    // Convert XML or JSON response
    if (substr(trim($response), 0, 1) === '<') {
        $xml = simplexml_load_string($response);
        $json = json_decode(json_encode($xml), true);
        $invoice = $json['Invoices']['Invoice'] ?? null;
    } else {
        $json = json_decode($response, true);
        $invoice = $json['Invoices'][0] ?? null;
    }

    // Handle result
    if ($invoice && isset($invoice['InvoiceID']) && (empty($invoice['HasErrors']) || $invoice['HasErrors'] === 'false')) {
        $sql = $conn->prepare("UPDATE tblPurchaseInvoice SET transaction_uid = :uid WHERE pid = :pid AND company_id = :company_id");
        $sql->execute([
            ':uid' => $invoice['InvoiceID'],
            ':pid' => $pid,
            ':company_id' => $company_id
        ]);
        UpdatePurStatus($pid,8);
        addPurchaseActivity($pid, $company_id, 5, 'Bill processed: Xero bill sent to accounting', $_SESSION['session_user_id'], 0);
        echo json_encode(['success' => true, 'invoice_id' => $invoice['InvoiceID']]);
        exit;
    }

    echo json_encode([
        'error' => 'Xero API validation or transport error',
        'xero_response' => $json ?? null,
        'raw_response' => $response
    ]);
    exit;
}
function calculateXeroDueDate($invoice_date, $payment_terms_day, $payment_terms_type) {
    // Defaults
    $day  = (is_numeric($payment_terms_day) && (int)$payment_terms_day > 0) ? (int)$payment_terms_day : 30;
    $type = (!empty($payment_terms_type)) ? strtoupper(trim($payment_terms_type)) : 'OFFOLLOWINGMONTH';

    // Normalise common variants
    $type = str_replace([' ', '_'], '', $type); // "OF FOLLOWING MONTH" -> "OFFOLLOWINGMONTH"
    if ($type === '') {
        $type = 'OFFOLLOWINGMONTH';
    }

    // Parse invoice date safely
    $base = strtotime($invoice_date);
    if ($base === false) {
        $base = time();
    }

    // Default required by you: 30 OFFOLLOWINGMONTH
    if ($type === 'OFFOLLOWINGMONTH') {
        // Due date is the <day>th of the following month
        $firstOfThisMonth = strtotime(date('Y-m-01', $base));
        $firstOfNextMonth = strtotime('+1 month', $firstOfThisMonth);
        $dueTs = strtotime('+' . ($day - 1) . ' days', $firstOfNextMonth);
        return date('Y-m-d', $dueTs);
    }

    // Fallback for any unknown type: 30 OFFOLLOWINGMONTH
    $firstOfThisMonth = strtotime(date('Y-m-01', $base));
    $firstOfNextMonth = strtotime('+1 month', $firstOfThisMonth);
    $dueTs = strtotime('+' . (30 - 1) . ' days', $firstOfNextMonth);
    return date('Y-m-d', $dueTs);
}


/*
function createXeroBillPayload($pid) {
    global $conn, $company_id;

    // Fetch purchase order data
    $sql = $conn->prepare("SELECT * FROM tblPurchaseOrders WHERE pid = :pid AND company_id = :company_id");
    $sql->bindParam(':pid', $pid);
    $sql->bindParam(':company_id', $company_id);
    $sql->execute();
    $po = $sql->fetch(PDO::FETCH_ASSOC);

    if (!$po) {
        echo json_encode(['error' => 'Purchase Order not found']);
        exit;
    }

    // Build invoice payload for Xero
    $payload = [
        'Type' => 'ACCPAY',
        'Contact' => [
            'ContactID' => $po['vendor_uid']
        ],
        'Date' => date('Y-m-d'),
        'DueDate' => date('Y-m-d', strtotime('+14 days')),
        'LineAmountTypes' => 'Exclusive',
        'InvoiceNumber' => $po['pid'],
        'LineItems' => [],
    ];

    // Fetch PO line items
    $sql = $conn->prepare("SELECT * FROM tblPurchaseOrderItems WHERE po_id = :pid AND company_id = :company_id");
    $sql->bindParam(':pid', $pid);
    $sql->bindParam(':company_id', $company_id);
    $sql->execute();
    $items = $sql->fetchAll(PDO::FETCH_ASSOC);

    foreach ($items as $item) {
        $payload['LineItems'][] = [
            'Description' => $item['item_desc'],
            'Quantity' => (float)$item['quantity'],
            'UnitAmount' => (float)$item['unit_price'],
            'AccountCode' => '400', // <-- Replace with actual valid Xero account code if needed
            'TaxType' => 'INPUT'
        ];
    }

    // Send to Xero
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "https://api.xero.com/api.xro/2.0/Invoices");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['Invoices' => [$payload]]));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Authorization: Bearer <xero_access_token>",
        "Content-Type: application/json",
        "Accept: application/json"
    ]);
    $response = curl_exec($ch);
    curl_close($ch);

    // Handle response: XML or JSON
    $invoice = null;
    if (str_starts_with(trim($response), '<')) {
        $xml = simplexml_load_string($response);
        $json = json_decode(json_encode($xml), true);
        $invoice = $json['Invoices']['Invoice'] ?? null;
    } else {
        $json = json_decode($response, true);
        $invoice = $json['Invoices'][0] ?? null;
    }

    // Handle success
    if ($invoice && isset($invoice['InvoiceID']) && (empty($invoice['HasErrors']) || $invoice['HasErrors'] === 'false')) {
        try {
            $sql = $conn->prepare("
                UPDATE tblPurchaseInvoice 
                SET transaction_uid = :uid 
                WHERE pid = :pid AND company_id = :company_id
            ");
            $sql->bindParam(':uid', $invoice['InvoiceID']);
            $sql->bindParam(':pid', $pid);
            $sql->bindParam(':company_id', $company_id);
            $sql->execute();

            echo json_encode([
                'success' => true,
                'invoice_id' => $invoice['InvoiceID'],
                'xero_response' => $invoice
            ]);
            exit;
        } catch (PDOException $e) {
            echo json_encode([
                'error' => 'Database error: ' . $e->getMessage(),
                'raw_response' => $response
            ]);
            exit;
        }
    }

    // Fallback: return error with raw data
    echo json_encode([
        'error' => 'Xero API error or invalid invoice returned',
        'xero_response' => $json ?? null,
        'raw_response' => $response
    ]);
    exit;
}


function getXeroBillLines($pid) {
    $database = new Database();
    $conn = $database->connect();

    $stmt = $conn->prepare("SELECT description, qty_total, rate FROM tblPurchaseInvoice WHERE pid = :pid AND company_id = :company_id");
    $stmt->bindValue(':pid', $pid);
    $stmt->bindValue(':company_id', $_SESSION['session_company_id']);
    $stmt->execute();

    $tax_code = getFieldColumn('payable_account_tax_code', 'tblAccounting', 'company_id', $_SESSION['session_company_id']);

    $lines = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $lines[] = [
            'Description' => $row['description'],
            'Quantity' => (float)$row['qty_total'],
            'UnitAmount' => (float)$row['rate'],
            'AccountCode' => '400', // Replace with your valid Xero numeric account code
            'TaxType' => $tax_code
        ];
    }

    return $lines;
}
*/



?>
