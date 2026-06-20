<?


if (isset($_GET['myob_get_suppliers'])) {
    $company_id = $_SESSION['session_company_id'];
    $search_field = $_GET['term'];
    $return_arr = array();
	$database = new Database();
	$conn = $database->connect();
    // Step 1: Search local database first using PDO
    $stmt = $conn->prepare("SELECT accounting_uid, company, address, suburb, state, postcode, phone1, email FROM tblVendors WHERE company LIKE CONCAT('%', :search_field, '%')");
    $search_term = '%' . $search_field . '%';
    $stmt->bindParam(':search_field', $search_term, PDO::PARAM_STR);
    $stmt->execute();
    $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($result as $row) {
        $row_array = array(
            'uid' => $row['accounting_uid'],
            'company' => $row['company'],
            'lastname' => "x",
            'firstname' => "x",
            'address' => $row['address'],
            'suburb' => $row['suburb'],
            'state' => $row['state'],
            'postcode' => $row['postcode'],
            'phone1' => $row['phone1'],
            'email' => $row['email'],
        );
        array_push($return_arr, $row_array);
    }

    // Step 2: If no local results, fetch from MYOB API
    if (empty($return_arr)) {
        $curl = curl_init();
        $filter1 = '$filter=startswith(CompanyName,%20\'' . $search_field . '\')%20or%20startswith(LastName,%20\'' . $search_field . '\')';

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
                    'uid' => isset($item['UID']) ? $item['UID'] : '',
                    'company' => isset($item['CompanyName']) ? $item['CompanyName'] : '',
                    'lastname' => isset($item['LastName']) ? $item['LastName'] : '',
                    'firstname' => isset($item['FirstName']) ? $item['FirstName'] : '',
                    'phone1' => isset($item['Addresses'][0]['Phone1']) ? $item['Addresses'][0]['Phone1'] : '',
                    'email' => isset($item['Addresses'][0]['Email']) ? $item['Addresses'][0]['Email'] : '',
                    'address' => isset($item['Addresses'][0]['Street']) ? $item['Addresses'][0]['Street'] : '',
                    'suburb' => isset($item['Addresses'][0]['City']) ? $item['Addresses'][0]['City'] : '',
                    'state' => isset($item['Addresses'][0]['State']) ? $item['Addresses'][0]['State'] : '',
                    'postcode' => isset($item['Addresses'][0]['PostCode']) ? $item['Addresses'][0]['PostCode'] : ''
                );

                array_push($return_arr, $row_array);
            }
        } else {
            echo json_encode(['error' => 'No items found']);
            exit;
        }
    }

    echo json_encode($return_arr);
}



?>