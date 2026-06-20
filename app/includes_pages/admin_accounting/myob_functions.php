<?
session_start();
include("../../includes/common.php");
require_once '/home/revolvexcom/web_config_ft.php';
$company_id = $_SESSION['session_company_id'];


$refresh_token = getFieldColumn('refresh_token', 'tblAccounting', 'company_id', $company_id);
$client_id = MYOB_KEY;

if (empty($client_id)) {
    die("Error: client_id is empty. Cannot proceed with token refresh.");
}

if(getFieldColumn('access_token_expire', 'tblAccounting', 'company_id', $company_id) - time() < 100 || getFieldColumn('access_token', 'tblAccounting', 'company_id', $company_id) == ''){
    RefreshToken($client_id, $refresh_token);
    sleep(2);
}

function UpdateMyobAccessToken($access_token, $refresh_token) {
    $company_id = $_SESSION['session_company_id'];
    $database = new Database();
    $conn = $database->connect();
    $access_token_expire = time() + 1200;

    $job_query = "UPDATE tblAccounting SET 
        access_token = :access_token,
        refresh_token = :refresh_token,
        access_token_expire = :access_token_expire
        WHERE company_id = :company_id";

    $job_result = $conn->prepare($job_query);
    $job_result->bindParam(':access_token', $access_token, PDO::PARAM_STR);
    $job_result->bindParam(':refresh_token', $refresh_token, PDO::PARAM_STR);
    $job_result->bindParam(':access_token_expire', $access_token_expire, PDO::PARAM_INT);
    $job_result->bindParam(':company_id', $company_id, PDO::PARAM_INT);
    
    $job_result->execute();
    getCompanyFileId($company_id);
}

function RefreshToken($client_id, $refresh_token) {
    $curl = curl_init();
    $postFields = "client_id=" . $client_id . "&client_secret=" . MYOB_SECRET . "&grant_type=refresh_token&refresh_token=" . $refresh_token;

    // Debugging: log the post fields
    //echo "Post Fields: " . $postFields . "<br>";

    curl_setopt_array($curl, array(
        CURLOPT_URL => "https://secure.myob.com/oauth2/v1/authorize/",
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => "",
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => "POST",
        CURLOPT_POSTFIELDS => $postFields,
        CURLOPT_HTTPHEADER => array(
            "Content-Type: application/x-www-form-urlencoded"
        ),
    ));

    $response = curl_exec($curl);
    $curl_error = curl_error($curl);
    curl_close($curl);
    $result = json_decode($response, true);
    
    //echo '<pre>';
    //print_r($result);
    //echo '</pre>';

    if (isset($result['access_token']) && isset($result['refresh_token'])) {
        UpdateMyobAccessToken($result['access_token'], $result['refresh_token']);
    } else {
        echo "Error refreshing token: ";
        if (isset($result['error'])) {
            echo $result['error'];
        }
        if (isset($result['error_description'])) {
            echo " - " . $result['error_description'];
        }
    }
}



if (isset($_GET['get_new_access_token'])) {
    $curl = curl_init();
    $code = $_GET['code'];
    curl_setopt_array($curl, array(
        CURLOPT_URL => 'https://secure.myob.com/oauth2/v1/authorize/',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'POST',
        CURLOPT_POSTFIELDS => "client_id=" . MYOB_KEY . "&client_secret=" . MYOB_SECRET . "&grant_type=authorization_code&code=" . $code . "&redirect_uri=" . MYOB_REDIRECT_URI,
        CURLOPT_HTTPHEADER => array(
            "Content-Type: application/x-www-form-urlencoded"
        ),
    ));

    $response = curl_exec($curl);
    $result = json_decode($response, true);
    //echo $response;
    echo '<pre>';
    print_r($result);
    echo '</pre>';

    if (isset($result['access_token']) && isset($result['refresh_token'])) {
        UpdateMyobAccessToken($result['access_token'], $result['refresh_token']);
    } 
}



function getCompanyFileId($company_id) {
  // if (isset($_GET['cf'])) {
     //  $company_id=2;
    $curl = curl_init();
    curl_setopt_array($curl, array(
        CURLOPT_URL => 'https://api.myob.com/accountright',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'GET',
        CURLOPT_HTTPHEADER => array(
            'x-myobapi-key: ' . MYOB_KEY,
            'x-myobapi-version: v2',
            'Accept-Encoding: gzip,deflate',
            "Authorization: Bearer " . getFieldColumn('access_token', 'tblAccounting', 'company_id', $company_id)
        ),
    ));

    $response = curl_exec($curl);
    $response_data = json_decode($response, true);


    echo '<pre>';
    print_r($response_data);
    echo '</pre>';

    if (isset($response_data[0]['Id'])) {
        $company_file_id = $response_data[0]['Id'];
  
        UpdateCompanyFileId($company_file_id);
    } 
}

function UpdateCompanyFileId($company_file_id) {
    $company_id = $_SESSION['session_company_id'];
    $database = new Database();
    $conn = $database->connect();

    $job_query = "UPDATE tblAccounting SET 
        company_file_id = :company_file_id
        WHERE company_id = :company_id";

    $job_result = $conn->prepare($job_query);
    $job_result->bindParam(':company_file_id', $company_file_id, PDO::PARAM_STR);
    $job_result->bindParam(':company_id', $company_id, PDO::PARAM_INT);

    if ($job_result->execute()) {
        echo "Company File ID updated successfully.";
    } else {
        echo "Error: Failed to update Company File ID.";
    }
}


if (isset($_GET['myob_sync_customers'])) {
    $company_id = $_SESSION['session_company_id'];
    $current_time = date('Y-m-d H:i:s'); // Current timestamp

    // Database connection
    $database = new Database();
    $conn = $database->connect();

    // Get the last modified timestamp
    $stmt = $conn->prepare("SELECT MAX(last_modified) AS last_modified_time FROM tblCustomers");
    $stmt->execute();
    $last_modified_time = $stmt->fetch(PDO::FETCH_ASSOC)['last_modified_time'];

    // Prepare the URL for fetching records modified since last sync
    $url = "https://api.myob.com/accountright/" . getFieldColumn('company_file_id', 'tblAccounting', 'company_id', $company_id) . "/Contact/Customer/";
    if ($last_modified_time) {
        $url .= "?\$filter=LastModified gt datetime'$last_modified_time'";
    }

    // Sync from MYOB to Local Database
    $curl = curl_init();
    curl_setopt_array($curl, array(
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => "",
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => "GET",
        CURLOPT_HTTPHEADER => array(
            'x-myobapi-key: ' . MYOB_KEY,
            "x-myobapi-version: v2",
            "Accept-Encoding: gzip,deflate",
            "Authorization: Bearer " . getFieldColumn('access_token', 'tblAccounting', 'company_id', $company_id)
        ),
    ));

    $response = curl_exec($curl);
    curl_close($curl);

    if ($response === false) {
        echo json_encode(['error' => 'Failed to retrieve data from MYOB API']);
        exit;
    }

    $result = json_decode($response, true);
    if (isset($result['Items']) && is_array($result['Items'])) {
        foreach ($result['Items'] as $item) {
            $is_individual = isset($item['IsIndividual']) ? $item['IsIndividual'] : false;

            if ($is_individual) {
                $lastname = isset($item['LastName']) ? $item['LastName'] : '';
                $firstname = isset($item['FirstName']) ? $item['FirstName'] : '';
                $company = $firstname . ' ' . $lastname;
            } else {
                $company = isset($item['CompanyName']) ? $item['CompanyName'] : '';
                $lastname = isset($item['Addresses'][0]['ContactLastName']) ? $item['Addresses'][0]['ContactLastName'] : '';
                $firstname = isset($item['Addresses'][0]['ContactFirstName']) ? $item['Addresses'][0]['ContactFirstName'] : '';
            }

            $uid = isset($item['UID']) ? $item['UID'] : '';
            $display_id = isset($item['DisplayID']) ? $item['DisplayID'] : '';
            $phone1 = isset($item['Addresses'][0]['Phone1']) ? $item['Addresses'][0]['Phone1'] : '';
            $email = isset($item['Addresses'][0]['Email']) ? $item['Addresses'][0]['Email'] : '';
            $address = isset($item['Addresses'][0]['Street']) ? $item['Addresses'][0]['Street'] : '';
            $suburb = isset($item['Addresses'][0]['City']) ? $item['Addresses'][0]['City'] : '';
            $state = isset($item['Addresses'][0]['State']) ? $item['Addresses'][0]['State'] : '';
            $postcode = isset($item['Addresses'][0]['PostCode']) ? $item['Addresses'][0]['PostCode'] : '';
            $payment_terms = isset($item['SellingDetails']['PaymentIsDue']) ? $item['SellingDetails']['PaymentIsDue'] : '';
            $credit_on_hold = isset($item['SellingDetails']['Credit']['onHold']) ? $item['SellingDetails']['Credit']['onHold'] : 0;
            $last_modified = isset($item['LastModified']) ? $item['LastModified'] : $current_time;

            // Check if customer exists in local DB
            $stmt = $conn->prepare("SELECT id FROM tblCustomers WHERE uid = :uid");
            $stmt->execute(['uid' => $uid]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($result) {
                // Update existing customer
                $stmt = $conn->prepare("UPDATE tblCustomers SET company = :company, lastname = :lastname, firstname = :firstname, display_id = :display_id, phone1 = :phone1, email = :email, address = :address, suburb = :suburb, state = :state, postcode = :postcode, is_individual = :is_individual, payment_terms = :payment_terms, credit_on_hold = :credit_on_hold, last_sync = :last_sync, last_modified = :last_modified WHERE uid = :uid");
                $stmt->execute([
                    'company' => $company,
                    'lastname' => $lastname,
                    'firstname' => $firstname,
                    'display_id' => $display_id,
                    'phone1' => $phone1,
                    'email' => $email,
                    'address' => $address,
                    'suburb' => $suburb,
                    'state' => $state,
                    'postcode' => $postcode,
                    'is_individual' => $is_individual,
                    'payment_terms' => $payment_terms,
                    'credit_on_hold' => $credit_on_hold,
                    'last_sync' => $current_time,
                    'last_modified' => $last_modified,
                    'uid' => $uid
                ]);
            } else {
                // Insert new customer
                $stmt = $conn->prepare("INSERT INTO tblCustomers (uid, company, lastname, firstname, display_id, phone1, email, address, suburb, state, postcode, is_individual, payment_terms, credit_on_hold, last_sync, last_modified) VALUES (:uid, :company, :lastname, :firstname, :display_id, :phone1, :email, :address, :suburb, :state, :postcode, :is_individual, :payment_terms, :credit_on_hold, :last_sync, :last_modified)");
                $stmt->execute([
                    'uid' => $uid,
                    'company' => $company,
                    'lastname' => $lastname,
                    'firstname' => $firstname,
                    'display_id' => $display_id,
                    'phone1' => $phone1,
                    'email' => $email,
                    'address' => $address,
                    'suburb' => $suburb,
                    'state' => $state,
                    'postcode' => $postcode,
                    'is_individual' => $is_individual,
                    'payment_terms' => $payment_terms,
                    'credit_on_hold' => $credit_on_hold,
                    'last_sync' => $current_time,
                    'last_modified' => $last_modified
                ]);
            }
        }
    } else {
        echo json_encode(['error' => 'No items found']);
    }

    // Sync from Local Database to MYOB
    $stmt = $conn->prepare("SELECT * FROM tblCustomers WHERE uid IS NULL");
    $stmt->execute();
    $new_customers = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($new_customers as $customer) {
        $customer_data = [
            'IsIndividual' => $customer['is_individual'],
            'CompanyName' => $customer['is_individual'] ? null : $customer['company'],
            'FirstName' => $customer['firstname'],
            'LastName' => $customer['lastname'],
            'Addresses' => [
                [
                    'Phone1' => $customer['phone1'],
                    'Email' => $customer['email'],
                    'Street' => $customer['address'],
                    'City' => $customer['suburb'],
                    'State' => $customer['state'],
                    'PostCode' => $customer['postcode']
                ]
            ],
            'SellingDetails' => [
                'PaymentIsDue' => $customer['payment_terms'],
                'Credit' => [
                    'onHold' => $customer['credit_on_hold']
                ]
            ]
        ];

        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => "https://api.myob.com/accountright/" . getFieldColumn('company_file_id', 'tblAccounting', 'company_id', $company_id) . "/Contact/Customer/",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_POSTFIELDS => json_encode($customer_data),
            CURLOPT_HTTPHEADER => array(
                'x-myobapi-key: ' . MYOB_KEY,
                "x-myobapi-version: v2",
                "Content-Type: application/json",
                "Authorization: Bearer " . getFieldColumn('access_token', 'tblAccounting', 'company_id', $company_id)
            ),
        ));

        $response = curl_exec($curl);
        curl_close($curl);

        if ($response === false) {
            echo json_encode(['error' => 'Failed to add customer to MYOB API']);
            exit;
        }

        $result = json_decode($response, true);
        if (isset($result['UID'])) {
            // Update local database with MYOB UID
            $uid = $result['UID'];
            $stmt = $conn->prepare("UPDATE tblCustomers SET uid = :uid, last_sync = :last_sync, last_modified = :last_modified WHERE id = :id");
            $stmt->execute([
                'uid' => $uid,
                'last_sync' => $current_time,
                'last_modified' => $current_time,
                'id' => $customer['id']
            ]);
        } else {
            echo json_encode(['error' => 'Failed to retrieve UID from MYOB API']);
        }
    }

    echo json_encode(['success' => 'Customers synced successfully']);
}




if (isset($_GET['myob_get_suppliers'])) {
    $company_id = $_SESSION['session_company_id'];
    $search_field = $_GET['term'];
    $curl = curl_init();
    $filter1 = '$filter=startswith(CompanyName,%20\'' . $search_field . '\')%20or%20startswith(LastName,%20\'' . $search_field . '\')';
    $return_arr = array();

    curl_setopt_array($curl, array(
        CURLOPT_URL => "https://api.myob.com/accountright/" . getFieldColumn('company_file_id', 'tblAccounting', 'company_id', $company_id) . "/Contact/Supplier/?" . $filter1,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => "",
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => "GET",
        CURLOPT_HTTPHEADER => array(
            'x-myobapi-key: ' . MYOB_KEY,
            "x-myobapi-version: v2",
            "Accept-Encoding: gzip,deflate",
            "Authorization: Bearer " . getFieldColumn('access_token', 'tblAccounting', 'company_id', $company_id)
        ),
    ));

    $response = curl_exec($curl);
    curl_close($curl);

    if ($response === false) {
        echo json_encode(['error' => 'Failed to retrieve data from MYOB API']);
        exit;
    }

    $result = json_decode($response, true);
    if (isset($result['Items']) && is_array($result['Items'])) {
        foreach ($result['Items'] as $item) {
            $row_array = array(
                'value' => (isset($item['CompanyName']) ? $item['CompanyName'] : '') . ' ' . (isset($item['FirstName']) ? $item['FirstName'] : '') . ' ' . (isset($item['LastName']) ? $item['LastName'] : ''),
                'company' => isset($item['CompanyName']) ? $item['CompanyName'] : '',
                'lastname' => isset($item['LastName']) ? $item['LastName'] : '',
                'firstname' => isset($item['FirstName']) ? $item['FirstName'] : '',
                'uid' => isset($item['UID']) ? $item['UID'] : '',
                'dsplay_id' => isset($item['DisplayID']) ? $item['DisplayID'] : '',
                'phone1' => isset($item['Addresses'][0]['Phone1']) ? $item['Addresses'][0]['Phone1'] : '',
                'email' => isset($item['Addresses'][0]['Email']) ? $item['Addresses'][0]['Email'] : '',
                'address' => isset($item['Addresses'][0]['Street']) ? $item['Addresses'][0]['Street'] : '',
                'suburb' => isset($item['Addresses'][0]['City']) ? $item['Addresses'][0]['City'] : '',
                'state' => isset($item['Addresses'][0]['State']) ? $item['Addresses'][0]['State'] : '',
                'postcode' => isset($item['Addresses'][0]['PostCode']) ? $item['Addresses'][0]['PostCode'] : ''
            );

            array_push($return_arr, $row_array);
        }

        echo json_encode($return_arr);
    } else {
        echo json_encode(['error' => 'No items found']);
    }
}
if (isset($_GET['myob_get_customers'])) {
    $company_id = $_SESSION['session_company_id'];
    $search_field = $_GET['term'];
    $curl = curl_init();
    $filter1 = '$filter=startswith(CompanyName,%20\'' . $search_field . '\')%20or%20startswith(LastName,%20\'' . $search_field . '\')';
    $return_arr = array();

    curl_setopt_array($curl, array(
        CURLOPT_URL => "https://api.myob.com/accountright/" . getFieldColumn('company_file_id', 'tblAccounting', 'company_id', $company_id) . "/Contact/Customer/?" . $filter1,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => "",
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => "GET",
        CURLOPT_HTTPHEADER => array(
            'x-myobapi-key: ' . MYOB_KEY,
            "x-myobapi-version: v2",
            "Accept-Encoding: gzip,deflate",
            "Authorization: Bearer " . getFieldColumn('access_token', 'tblAccounting', 'company_id', $company_id)
        ),
    ));

    $response = curl_exec($curl);
    curl_close($curl);

    if ($response === false) {
        echo json_encode(['error' => 'Failed to retrieve data from MYOB API']);
        exit;
    }

    $result = json_decode($response, true);
    if (isset($result['Items']) && is_array($result['Items'])) {
        foreach ($result['Items'] as $item) {
            $is_individual = isset($item['IsIndividual']) ? $item['IsIndividual'] : false;

            if ($is_individual) {
                $lastname = isset($item['LastName']) ? $item['LastName'] : '';
                $firstname = isset($item['FirstName']) ? $item['FirstName'] : '';
                $company = $firstname . ' ' . $lastname;
            } else {
                $company = isset($item['CompanyName']) ? $item['CompanyName'] : '';
                $lastname = isset($item['Addresses'][0]['ContactLastName']) ? $item['Addresses'][0]['ContactLastName'] : '';
                $firstname = isset($item['Addresses'][0]['ContactFirstName']) ? $item['Addresses'][0]['ContactFirstName'] : '';
            }

            $row_array = array(
                'value' => $company,
                'company' => $company,
                'lastname' => $lastname,
                'firstname' => $firstname,
                'uid' => isset($item['UID']) ? $item['UID'] : '',
                'display_id' => isset($item['DisplayID']) ? $item['DisplayID'] : '',
                'phone1' => isset($item['Addresses'][0]['Phone1']) ? $item['Addresses'][0]['Phone1'] : '',
                'email' => isset($item['Addresses'][0]['Email']) ? $item['Addresses'][0]['Email'] : '',
                'address' => isset($item['Addresses'][0]['Street']) ? $item['Addresses'][0]['Street'] : '',
                'suburb' => isset($item['Addresses'][0]['City']) ? $item['Addresses'][0]['City'] : '',
                'state' => isset($item['Addresses'][0]['State']) ? $item['Addresses'][0]['State'] : '',
                'postcode' => isset($item['Addresses'][0]['PostCode']) ? $item['Addresses'][0]['PostCode'] : '',
                'is_individual' => $is_individual,
                
               'item_price_level' => isset($item['SellingDetails']['ItemPriceLevel']) ? $item['SellingDetails']['ItemPriceLevel'] : '',

            );

            array_push($return_arr, $row_array);
        }

        echo json_encode($return_arr);
    } else {
        echo json_encode(['error' => 'No items found']);
    }
}



if (isset($_GET['get_all_accounts'])) {
    // myob Asset, Liability, Equity, Income, Cost of Sales, Expense, Other Income and Other Expense
    // CostOfSales
    
    $company_id = $_SESSION['session_company_id']; 
    $classification = $_GET['classification'];
    $curl = curl_init();
    
    curl_setopt_array($curl, array(
        CURLOPT_URL => "https://api.myob.com/accountright/" . getFieldColumn('company_file_id', 'tblAccounting', 'company_id', $company_id) . "/GeneralLedger/Account",
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => "",
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => "GET",
        CURLOPT_HTTPHEADER => array(
            'x-myobapi-key: ' . MYOB_KEY,
            "x-myobapi-version: v2",
            "Accept-Encoding: gzip,deflate",
            "Authorization: Bearer " . getFieldColumn('access_token', 'tblAccounting', 'company_id', $company_id)
        ),
    ));

    $response = curl_exec($curl);
    curl_close($curl);
    $result = json_decode($response, true);

    if (isset($result['Items']) && is_array($result['Items'])) {
        $filteredAccounts = array_filter($result['Items'], function($account) use ($classification) {
            return isset($account['IsActive']) && $account['IsActive'] == 1 && isset($account['Classification']) && $account['Classification'] == $classification;
        });

        $accounts = array_map(function($account) {
            return [
                'label' => $account['Name'].' ('.$account['DisplayID'].')',
                'uid' => $account['UID'],
                'classification' => $account['Classification'],
                'display_id' => $account['DisplayID'],
                'description' => $account['Description']
            ];
        }, $filteredAccounts);

        header('Content-Type: application/json');
        echo json_encode($accounts);
    } else {
         echo json_encode(['success' => true, 'message' => 'Error .']);
    }
}
if (isset($_GET['create_invoice'])) {
    $order_id = $_POST['invoice_id'];
    $invoice_date = $_POST['invoice_date'];
    
    $company_file_id = getFieldColumn('company_file_id', 'tblAccounting', 'company_id', $_SESSION['session_company_id']);
    $access_token = getFieldColumn('access_token', 'tblAccounting', 'company_id', $_SESSION['session_company_id']);
    
    $curl = curl_init();
    curl_setopt_array($curl, array(
        CURLOPT_URL => "https://api.myob.com/accountright/" . $company_file_id . "/Sale/Invoice/Service?returnBody=true",
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => "",
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => "POST",
        CURLOPT_POSTFIELDS => insert_invoice($order_id,$invoice_date),
        CURLOPT_HTTPHEADER => array(
            'x-myobapi-key: ' . MYOB_KEY,
            "x-myobapi-version: v2",
            "Accept-Encoding: gzip,deflate",
            "Authorization: Bearer " . $access_token,
            "Content-Type: application/json"
        ),
    ));
    
    $response = curl_exec($curl);
    $http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    $curl_error = curl_error($curl);
    curl_close($curl);

    // Log the response for debugging
    error_log("HTTP Code: " . $http_code);
    error_log("CURL Error: " . $curl_error);
    error_log("Response: " . $response);

    $result = json_decode($response, true);
if (isset($result['UID'])) {
    // Convert DueDate to epoch time
    $due_date = strtotime($result['Terms']['DueDate']);
    $invoice_date = strtotime($invoice_date);
    
    $database = new Database();
    $conn = $database->connect();
    $sql = $conn->prepare("UPDATE tblInvoice SET invoice_date = :invoice_date,  transaction_uid = :transaction_uid, transaction_invoice_date = :transaction_invoice_date, transaction_due_date = :transaction_due_date WHERE order_id = :order_id AND company_id = :company_id");
    $sql->bindValue(':transaction_uid', $result['UID'], PDO::PARAM_STR);
    $sql->bindValue(':transaction_invoice_date', $invoice_date, PDO::PARAM_INT);
    $sql->bindValue(':invoice_date', $invoice_date, PDO::PARAM_INT);
    $sql->bindValue(':transaction_due_date', $due_date, PDO::PARAM_INT);
    $sql->bindValue(':order_id', $order_id, PDO::PARAM_INT);
    $sql->bindValue(':company_id', $_SESSION['session_company_id'], PDO::PARAM_INT);
    $sql->execute();

    $statusUpdate = $conn->prepare("UPDATE tblOrders 
        SET order_status_id = 16 
        WHERE order_id = :order_id AND company_id = :company_id");
    $statusUpdate->bindValue(':order_id', $order_id, PDO::PARAM_INT);
    $statusUpdate->bindValue(':company_id', $_SESSION['session_company_id'], PDO::PARAM_INT);
    $statusUpdate->execute();

    addOrderActivity(
        $order_id,
        $_SESSION['session_company_id'],
        5,
        'Invoice processed: MYOB invoice sent for ' . date('d-m-Y', $invoice_date),
        $_SESSION['session_user_id'],
        0
    );
}


    echo json_encode([
        'http_code' => $http_code,
        'curl_error' => $curl_error,
        'response' => $response
    ]);
}




function get_invoice_lines($order_id) {
    $database = new Database();
    $conn = $database->connect();
    $query = "SELECT description, qty, rate FROM tblInvoice WHERE order_id = :order_id AND company_id = :company_id";
    $stmt = $conn->prepare($query);
    $stmt->bindValue(':order_id', $order_id, PDO::PARAM_INT);
    $stmt->bindValue(':company_id',  $_SESSION['session_company_id'], PDO::PARAM_INT);
    $stmt->execute();
    
    $return_arr = array();
    
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $total_line = $row['qty'] * $row['rate'];
        $total_with_tax = $total_line * 1.1;
        
        $row_data = array(
            'Type' => "Transaction",
            'Description' => $row['description'],
            'Account' => array(
                'UID' => getFieldColumn('receivable_account_code', 'tblAccounting', 'company_id', $_SESSION['session_company_id']) // Ensure this is a valid GUID
            ),
            'Total' => number_format($total_with_tax, 2, '.', ''), // Ensure total is formatted correctly
            'TaxCode' => array(
                'UID' => getFieldColumn('receivable_account_tax_code', 'tblAccounting', 'company_id', $_SESSION['session_company_id'])// Ensure this is a valid GUID
            )
        );
        
        array_push($return_arr, $row_data);
    }
    
    $stmt->closeCursor();
    $conn = null;
    return $return_arr;
}

function insert_invoice($order_id,$invoice_date) {
   // $customer_uid = '882ce0d3-b8fa-4522-b245-adf635a3375d'; // Ensure this is a valid GUID
    $customer_uid = getFieldColumn('customer_uid', 'tblOrders', 'order_id', $order_id);
    $order_number = getFieldColumn('order_number', 'tblOrders', 'order_id', $order_id);
    $data_string = array(
        'Number' => strval($order_id), // Ensure order number is a string
        //'Date' => date('Y-m-d') . 'T' . date('H:i:s'),  
        'Date' => date('Y-m-d', strtotime($invoice_date)) . 'T' . date('H:i:s'),

        'CustomerPurchaseOrderNumber' => strval($order_number), // Ensure this is a string
        "Customer" => array(
            'UID' => $customer_uid
        ),   
        'ShipToAddress' => "",
        'IsTaxInclusive' => true, // Ensure this is a boolean
        'Lines' => get_invoice_lines($order_id),
        'Category' => null, 
        'Comment' => "", 
        'ShippingMethod' => null, 
        'JournalMemo' => "DB API " . date('d-m-Y H:i'),
        'BillDeliveryStatus' => "Print",
        'AppliedToDate' => 0, 
        'Status' => "Open",
        'LastPaymentDate' => null, 
        'Order' => null, 
        'ForeignCurrency' => null,
		'Freight' => getTabFieCol('delivery_rate', 'tblOrders', 'order_id', $order_id, $_SESSION['session_company_id']),
		
    );

    return json_encode($data_string);
}


if (isset($_GET['create_bill'])) {
    $pid = $_POST['pid'];
    
    $database = new Database();
    $conn = $database->connect();
    
 // Check if a transaction has already been completed (i.e., transaction_uid is NOT NULL)
    $checkQuery = "SELECT COUNT(*) FROM tblPurchaseInvoice WHERE company_id = :company_id AND pid = :pid AND transaction_uid =''";
    $checkStmt = $conn->prepare($checkQuery);
    $checkStmt->bindValue(':company_id', $_SESSION['session_company_id'], PDO::PARAM_INT);
    $checkStmt->bindValue(':pid', $pid, PDO::PARAM_INT);
    $checkStmt->execute();
    $exists = $checkStmt->fetchColumn();

    // If `transaction_uid` is NULL, then the order has not been invoiced yet, so it should proceed.
    if ($exists == 0) {
        echo json_encode(['warning' => true, 'message' => 'This order has already been invoiced.']);
        exit;
    }
    $company_file_id = getFieldColumn('company_file_id', 'tblAccounting', 'company_id', $_SESSION['session_company_id']);
    $access_token = getFieldColumn('access_token', 'tblAccounting', 'company_id', $_SESSION['session_company_id']);
    
    $curl = curl_init();
    curl_setopt_array($curl, array(
        CURLOPT_URL => "https://api.myob.com/accountright/" . $company_file_id . "/Purchase/Bill/Service?returnBody=true",
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => "",
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => "POST",
        CURLOPT_POSTFIELDS => insert_bill($pid),
        CURLOPT_HTTPHEADER => array(
            'x-myobapi-key: ' . MYOB_KEY,
            "x-myobapi-version: v2",
            "Accept-Encoding: gzip,deflate",
            "Authorization: Bearer " . $access_token,
            "Content-Type: application/json"
        ),
    ));
    
    $response = curl_exec($curl);
    $http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    $curl_error = curl_error($curl);
    curl_close($curl);

    $result = json_decode($response, true);
    if (isset($result['UID'])) {
        // Convert DueDate to epoch time
        $bill_date = strtotime($result['Date']);
        $due_date = strtotime($result['Terms']['DueDate']);
        $order_status_id=8;
        $database = new Database();
        $conn = $database->connect();
        $sql = $conn->prepare("UPDATE tblPurchaseInvoice SET transaction_uid = :transaction_uid, bill_date = :bill_date, transaction_due_date = :transaction_due_date, order_status_id= :order_status_id  WHERE pid = :pid AND company_id = :company_id");
        $sql->bindValue(':transaction_uid', $result['UID'], PDO::PARAM_STR);
        $sql->bindValue(':bill_date', $bill_date, PDO::PARAM_INT);
        $sql->bindValue(':transaction_due_date', $due_date, PDO::PARAM_INT);
        $sql->bindValue(':pid', $pid, PDO::PARAM_INT);
        $sql->bindValue(':company_id', $_SESSION['session_company_id'], PDO::PARAM_INT);
        $sql->bindValue(':order_status_id', $order_status_id, PDO::PARAM_INT);
        $sql->execute();
        addPurchaseActivity($pid, $_SESSION['session_company_id'], 5, 'Bill processed: MYOB bill sent to accounting', $_SESSION['session_user_id'], 0);
    }

    echo json_encode([
        'http_code' => $http_code,
        'curl_error' => $curl_error,
        'response' => $response
    ]);
}

function get_bill_lines($pid) {
    $database = new Database();
    $conn = $database->connect();
    $query = "SELECT description, qty_total, rate FROM tblPurchaseInvoice WHERE pid = :pid AND company_id = :company_id";
    $stmt = $conn->prepare($query);
    $stmt->bindValue(':pid', $pid, PDO::PARAM_INT);
    $stmt->bindValue(':company_id', $_SESSION['session_company_id'], PDO::PARAM_INT);
    $stmt->execute();
    
    $return_arr = array();
    
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $total_line = $row['qty_total'] * $row['rate'];
        $total_with_tax = $total_line * 1.1;
        
        $row_data = array(
            'Type' => "Transaction",
            'Description' => $row['description'],
            'Account' => array(
                'UID' => getFieldColumn('payable_account_code', 'tblAccounting', 'company_id', $_SESSION['session_company_id']) // Ensure this is a valid GUID
            ),
            'Total' => number_format($total_with_tax, 2, '.', ''), // Ensure total is formatted correctly
            'TaxCode' => array(
                'UID' => getFieldColumn('payable_account_tax_code', 'tblAccounting', 'company_id', $_SESSION['session_company_id']) // Ensure this is a valid GUID
            )
        );
        
        array_push($return_arr, $row_data);
    }
    
    $stmt->closeCursor();
    $conn = null;
    return $return_arr;
}

function insert_bill($pid) {
    $supplier_uid = getFieldColumn('vendor_uid', 'tblPurchaseInvoice', 'pid', $pid);
    $freight_tax_code = getFieldColumn('payable_account_tax_code', 'tblAccounting', 'company_id', $_SESSION['session_company_id']);
    $freight=getTableColField('freight', 'tblPurchaseOrders', 'id', $pid);
    $ven_inv_number=getTableColField('ven_inv_number', 'tblPurchaseOrders', 'id', $pid);
    $invoice_date=getTableColField('invoice_date', 'tblPurchaseOrders', 'id', $pid);
    
    $company_address = getFieldColumn('company_address', 'tblCompany', 'id', $_SESSION['session_company_id']);
    $company_suburb = getFieldColumn('company_suburb', 'tblCompany', 'id', $_SESSION['session_company_id']);
    $company_state = getFieldColumn('company_state', 'tblCompany', 'id', $_SESSION['session_company_id']);
    $company_postcode = getFieldColumn('company_postcode', 'tblCompany', 'id', $_SESSION['session_company_id']);
    $ship_to_address=$company_address.', '.$company_suburb.' '.$company_state.' '.$company_postcode;
    
    $data_string = array(
        'Number' => strval($pid), // Ensure order number is a string
        'Date' => date('Y-m-d\TH:i:s', $invoice_date), // Convert epoch to 'Y-m-dTH:i:s' format
        'SupplierInvoiceNumber' => strval($ven_inv_number), // Ensure this is a string
        "Supplier" => array(
            'UID' => $supplier_uid
        ),   
        'ShipToAddress' => $ship_to_address,
        'IsTaxInclusive' => true, // Ensure this is a boolean
        'Lines' => get_bill_lines($pid),
        'Category' => null, 
        'Comment' => "", 
        'ShippingMethod' => null, 
        'JournalMemo' => "DB API " . date('d-m-Y H:i'),
        'BillDeliveryStatus' => "Print",
        'AppliedToDate' => 0, 
        'Status' => "Open",
        'LastPaymentDate' => null, 
        'Order' => null, 
        'ForeignCurrency' => null,
        'Freight' => $freight, // Adjust as needed
        'FreightTaxCode' => array(
            'UID' => $freight_tax_code
        )
    );

    return json_encode($data_string);
}




if(isset($_GET['get_all_tax'])){
$curl = curl_init();
curl_setopt_array($curl, array(
  CURLOPT_URL => "https://api.myob.com/accountright/".getFieldColumn('company_file_id', 'tblAccounting', 'company_id', $company_id)."/GeneralLedger/TaxCode",
  CURLOPT_RETURNTRANSFER => true,
  CURLOPT_ENCODING => "",
  CURLOPT_MAXREDIRS => 10,
  CURLOPT_TIMEOUT => 0,
  CURLOPT_FOLLOWLOCATION => true,
  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
  CURLOPT_CUSTOMREQUEST => "GET",
  CURLOPT_HTTPHEADER => array(
            'x-myobapi-key: ' . MYOB_KEY,
            "x-myobapi-version: v2",
            "Accept-Encoding: gzip,deflate",
            "Authorization: Bearer " . getFieldColumn('access_token', 'tblAccounting', 'company_id', $company_id),
            "Content-Type: application/json" // Ensure content type is set to JSON
        ),
));

    $response = curl_exec($curl);
    curl_close($curl);

    $result = json_decode($response, true);
    echo $response;
}

//above here all works

 if(isset($_GET['create_customer'])){ 
     $customer_company=strtoupper($_POST['customer_company']);
     $lastname=strtoupper($_POST['customer_lastname']);
     $firstname=strtoupper($_POST['customer_firstname']);
     $mobile=$_POST['customer_mobile'];
     $email=$_POST['customer_email'];
     $address=$_POST['customer_address'];
     $suburb=$_POST['customer_suburb'];
     $isindividual=$_POST['customer_type_individual'];
     
    
  $curl = curl_init();
  //$return_arr = array();
  curl_setopt_array($curl, array(
  CURLOPT_URL => "https://api.myob.com/accountright/".getSiteDefaults('myob_company_file_id')."/Contact/Customer?returnBody=true",
  CURLOPT_RETURNTRANSFER => true,
  CURLOPT_ENCODING => "",
  CURLOPT_MAXREDIRS => 10,
  CURLOPT_TIMEOUT => 0,
  CURLOPT_FOLLOWLOCATION => true,
  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
  CURLOPT_CUSTOMREQUEST => "POST",
  CURLOPT_POSTFIELDS =>insert_customer($customer_company,$lastname,$firstname,$isindividual,$address,$suburb,$mobile,$email),
  CURLOPT_HTTPHEADER => array(
    "x-myobapi-key: ".getSiteDefaults('myob_client_key'),
    "x-myobapi-version: v2",
    "Accept-Encoding: gzip,deflate",
    "Content-Type: application/json",  
    "Authorization: Bearer ".getSiteDefaults('myob_access_token')
  ),
));

$response = curl_exec($curl);
curl_close($curl);
//$result = json_decode($response, true);
//$customer_uid = $result['UID'];
//$customer_uid = ['UID']; 
     $row_array['customer_myob_uid'] = $result['UID'];;
     //array_push($return_arr,$row_array);
     echo $response;   
 }
function insert_customer($customer_company,$lastname,$firstname,$isindividual,$address,$suburb,$mobile,$email){
  /*  if($isindividual==true){
        $i_lastname='';
        $i_firstname=''; 
        $customer_company='';
        }
        else{
            $customer_company=$customer_company;
            $i_firstname=$firstname;
            $i_lastname=$lastname;
        }
    */
$data_string = array(
                 'CompanyName' => $customer_company,
			     'LastName' => $lastname, 
                 'Firstname' => $firstname, 
                 'IsIndividual' => $isindividual,
                 //'DisplayID' => "API", 
    
                     "Addresses"=>array(
                        array(
                         'Street' => $address,
                         'City' => $suburb,   
                         'Phone1' => $mobile,
                         'State' => "Tasmania", 
                         'Country' => "Australia",
                         'Email' => $email,
                         'ContactName' => $firstname. ' '.$lastname,   
                         
                     ),    
                    ),
                "SellingDetails"=>array(
                            "TaxCode"=>array(
				 	              'UID' =>  getSiteDefaults('myob_tax_account'),
                            ),   
                            "FreightTaxCode"=>array(
                                'UID' =>  getSiteDefaults('myob_tax_account'),
                            ),   
                ),

            );
	return json_encode($data_string);

}

 if(isset($_GET['myob_get_inventory'])){            
$search_field= $_GET['term'];           
$curl = curl_init();
//$filter1='$filter=LastName%20eq%20';
//$filter1='$filter=startswith(Name,%20\''.$search_field.'\')';
$search_field1 = str_replace(' ', '%20', $search_field);  
$filter1='$filter=substringof(tolower(\''.$search_field1.'\'),tolower(Name))%20and%20IsSold%20eq%20true%20and%20IsActive%20eq%20true';     
//$filter2='$filter=startswith(LastName,%20\''.$lastname.'\')';
//$filter=endswith(Number,%20\'1234\')';
 $return_arr = array();
curl_setopt_array($curl, array(
  CURLOPT_URL => "https://api.myob.com/accountright/".getSiteDefaults('myob_company_file_id')."/Inventory/Item/?$filter1",
  CURLOPT_RETURNTRANSFER => true,
  CURLOPT_ENCODING => "",
  CURLOPT_MAXREDIRS => 10,
  CURLOPT_TIMEOUT => 0,
  CURLOPT_FOLLOWLOCATION => true,
  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
  CURLOPT_CUSTOMREQUEST => "GET",
  CURLOPT_HTTPHEADER => array(
    "x-myobapi-key: ".getSiteDefaults('myob_client_key'),
    "x-myobapi-version: v2",
    "Accept-Encoding: gzip,deflate",
    "Authorization: Bearer ".getSiteDefaults('myob_access_token')
  ),
));

$response = curl_exec($curl);
curl_close($curl);
$result = json_decode($response, true);
$result_count=count($result['Items']);
$n=0;
for ($x = 1; $x <= $result_count; $x++) {
        $row_array['value'] = $result['Items'][$n]['Name'].' ('.$result['Items'][$n]['Number'].')';
        $row_array['sell_price'] = number_format($result['Items'][$n]['SellingDetails']['BaseSellingPrice'],2);
        $row_array['myob_uid'] = $result['Items'][$n]['UID'];  
    
    $n++;
    array_push($return_arr,$row_array);
}
  echo json_encode($return_arr);    
   
//echo '<pre>';
//print_r($result);
//echo '</pre>';
  //echo json_encode($return_arr);    
 }
 if(isset($_GET['myob_get_item'])){            
$search_field= $_GET['myob_item_uid'];           
$curl = curl_init();
$filter1=$search_field;
 $return_arr = array();
curl_setopt_array($curl, array(
  CURLOPT_URL => "https://api.myob.com/accountright/".getSiteDefaults('myob_company_file_id')."/Inventory/Item/$filter1",
  CURLOPT_RETURNTRANSFER => true,
  CURLOPT_ENCODING => "",
  CURLOPT_MAXREDIRS => 10,
  CURLOPT_TIMEOUT => 0,
  CURLOPT_FOLLOWLOCATION => true,
  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
  CURLOPT_CUSTOMREQUEST => "GET",
  CURLOPT_HTTPHEADER => array(
    "x-myobapi-key: ".getSiteDefaults('myob_client_key'),
    "x-myobapi-version: v2",
    "Accept-Encoding: gzip,deflate",
    "Authorization: Bearer ".getSiteDefaults('myob_access_token')
  ),
));

$response = curl_exec($curl);
curl_close($curl);
$result = json_decode($response, true);
     $UID = $result['UID']; 
     $IsInventoried = $result['IsInventoried'];
     $on_hand = $result['QuantityOnHand']; 
  //   return $on_hand;
//echo '<pre>';
//print_r($result);
//echo '</pre>';
 }
function myob_save_customer($customer_uid,$customer_lastname,$customer_firstname,$customer_phone1,$customer_email){  
   
        //$customer_uid = '08e47888-b0a9-4c6b-a55c-0ffa873564da';
        //$customer_lastname= 'Scott';
       // $customer_firstname= 'Campbelsssl';
$curl = curl_init();
$return_arr = array();
curl_setopt_array($curl, array(
  CURLOPT_URL => "https://api.myob.com/accountright/".getSiteDefaults('myob_company_file_id')."/Contact/Customer/".$customer_uid,
  CURLOPT_RETURNTRANSFER => true,
  CURLOPT_ENCODING => "",
  CURLOPT_MAXREDIRS => 10,
  CURLOPT_TIMEOUT => 0,
  CURLOPT_FOLLOWLOCATION => true,
  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
  CURLOPT_CUSTOMREQUEST => "PUT",
  CURLOPT_POSTFIELDS => customer_fields($customer_uid,$customer_lastname,$customer_firstname,$customer_phone1,$customer_email) ,
  CURLOPT_HTTPHEADER => array(
     "x-myobapi-key: ".getSiteDefaults('myob_client_key'),
    "x-myobapi-version: v2",
    "Accept-Encoding: gzip,deflate",
    "Content-Type: application/json",
    "Authorization: Bearer ".getSiteDefaults('myob_access_token')
  ),
     
));
$response = curl_exec($curl);
curl_close($curl);
echo '<pre>';
print_r($response);
echo '</pre>';

} 
function customer_fields($customer_uid,$customer_lastname,$customer_firstname,$customer_phone1,$customer_email){
$row_version=myob_get_customer($customer_uid);
$data_string = array(
			'UID' => $customer_uid, 
            'LastName' => $customer_lastname,   
            'FirstName' => $customer_firstname, 
            'IsIndividual'=> 'true', 
            "SellingDetails"=>array(
                    "TaxCode"=>array(
				 	   'UID' => getSiteDefaults('myob_tax_account'),
                      ),
                    "FreightTaxCode"=>array(
				 	   'UID' => getSiteDefaults('myob_tax_account'),
                      ),
                ),
            "Addresses"=>array(
                        array(
                         'Phone1' => $customer_phone1,
                         'State' => "Tasmania", 
                         'Country' => "Australia",
                         'Email' => $customer_email,
                         
                     ),    
                    ), 
            'RowVersion' => $row_version,

   );
	return json_encode($data_string);
}
function myob_get_customer($customer_uid){  
      //  $customer_uid = '08e47888-b0a9-4c6b-a55c-0ffa873564da';
$curl = curl_init();
 $return_arr = array();
curl_setopt_array($curl, array(
  CURLOPT_URL => "https://api.myob.com/accountright/".getSiteDefaults('myob_company_file_id')."/Contact/Customer/".$customer_uid,
  CURLOPT_RETURNTRANSFER => true,
  CURLOPT_ENCODING => "",
  CURLOPT_MAXREDIRS => 10,
  CURLOPT_TIMEOUT => 0,
  CURLOPT_FOLLOWLOCATION => true,
  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
  CURLOPT_CUSTOMREQUEST => "GET",
  CURLOPT_HTTPHEADER => array(
    "x-myobapi-key: ".getSiteDefaults('myob_client_key'),
    "x-myobapi-version: v2",
    "Accept-Encoding: gzip,deflate",
    "Authorization: Bearer ".getSiteDefaults('myob_access_token')
  ),
));
$response = curl_exec($curl);
curl_close($curl);
$result = json_decode($response, true);
 return $result['RowVersion'];
 }

if(isset($_GET['del_trans'])){
    $uid = $_POST['uid'];
$curl = curl_init();
curl_setopt_array($curl, array(
  //CURLOPT_URL => "https://ar2.api.myob.com/accountright/".getSiteDefaults('myob_company_file_id')."/Purchase/Bill/Professional/".$myob_uid,
  CURLOPT_URL => "https://api.myob.com/accountright/".getSiteDefaults('myob_company_file_id')."/Sale/Invoice/Item/".$uid."?returnBody=true",
  CURLOPT_RETURNTRANSFER => true,
  CURLOPT_ENCODING => "",
  CURLOPT_MAXREDIRS => 10,
  CURLOPT_TIMEOUT => 0,
  CURLOPT_FOLLOWLOCATION => true,
  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
  CURLOPT_CUSTOMREQUEST => "DELETE",
  CURLOPT_POSTFIELDS =>"{\n\t\"UID\": \"$uid\"\n}",
  CURLOPT_HTTPHEADER => array(
    "x-myobapi-key: ".getSiteDefaults('myob_client_key'),
    "x-myobapi-version: v2",
    "Accept-Encoding: gzip,deflate",
    "Content-Type: application/json",
    "Authorization: Bearer ".getSiteDefaults('myob_access_token')
  ),
));
    
$response = curl_exec($curl);

curl_close($curl);
$result = json_decode($response, true);
echo $result;
}
/*
function get_invoice_myob($job_id){
$filter='$filter=CustomerPurchaseOrderNumber%20eq%20';
$search=$job_id;
$curl = curl_init();
	
	
curl_setopt_array($curl, array(
  //CURLOPT_URL => "https://ar1.api.myob.com/accountright/d8d88e90-f2bf-41c3-bf36-c9d1b5499eee/Sale/Invoice/?$filter'$search'",
	CURLOPT_URL => "https://api.myob.com/accountright/".getSiteDefaults('myob_company_file_id')."/Sale/Invoice/?$filter'$search'",
  CURLOPT_RETURNTRANSFER => true,
  CURLOPT_ENCODING => "",
  CURLOPT_MAXREDIRS => 10,
  CURLOPT_TIMEOUT => 0,
  CURLOPT_FOLLOWLOCATION => true,
  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
  CURLOPT_CUSTOMREQUEST => "GET",
  CURLOPT_HTTPHEADER => array(
    "x-myobapi-key: ".getSiteDefaults('myob_client_key'),
    "x-myobapi-version: v2",
    "Accept-Encoding: gzip,deflate",
    "Authorization: Bearer ".getSiteDefaults('myob_access_token')
  ),
));
$response = curl_exec($curl);
curl_close($curl);
$result = json_decode($response, true);
$UID = $result['Items'][0]['UID'];   
 global $conn;
     $sql = $conn->prepare("UPDATE tblJobs SET
       myob_invoice_date = '".time()."',
	   myob_transaction_uid = '".$UID."'
	   WHERE id = '".$job_id."'");
	   $sql->execute();

}
function myob_get_item($item_uid){            
$search_field= $item_uid;           
$curl = curl_init();
$filter1=$search_field;
 $return_arr = array();
curl_setopt_array($curl, array(
  CURLOPT_URL => "https://api.myob.com/accountright/".getSiteDefaults('myob_company_file_id')."/Inventory/Item/$filter1",
  CURLOPT_RETURNTRANSFER => true,
  CURLOPT_ENCODING => "",
  CURLOPT_MAXREDIRS => 10,
  CURLOPT_TIMEOUT => 0,
  CURLOPT_FOLLOWLOCATION => true,
  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
  CURLOPT_CUSTOMREQUEST => "GET",
  CURLOPT_HTTPHEADER => array(
    "x-myobapi-key: ".getSiteDefaults('myob_client_key'),
    "x-myobapi-version: v2",
    "Accept-Encoding: gzip,deflate",
    "Authorization: Bearer ".getSiteDefaults('myob_access_token')
  ),
));

$response = curl_exec($curl);
curl_close($curl);
$result = json_decode($response, true);
     $UID = $result['UID']; 
     $is_inventoried = $result['IsInventoried'];
     $on_hand = $result['QuantityOnHand']; 
    global $conn;
        $sql = $conn->prepare("UPDATE tblJobsItems SET
	   on_hand_now = '".$on_hand ."'
	   WHERE myob_uid = '".$item_uid ."'");
	   $sql->execute();
    
        if($is_inventoried){
            return $on_hand; 
        }
        else{
            return 999; 
        }
     
//echo '<pre>';
//print_r($result);
//echo '</pre>';
 }
 */
?>
