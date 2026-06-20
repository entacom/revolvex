<?php
session_start();
require_once '/home/revolvexcom/web_config_ft.php';
error_reporting(E_ALL);
ini_set('display_errors', 'Off');

$company_id = $_SESSION['session_company_id'];
if (empty($_SESSION['xero_oauth_state'])) {
    $_SESSION['xero_oauth_state'] = bin2hex(random_bytes(16));
}

$token_exists = getFieldColumn('access_token', 'tblAccounting', 'company_id', $company_id);
$token_expire = getFieldColumn('access_token_expire', 'tblAccounting', 'company_id', $company_id);
if ($token_exists) {
    $xero_status = "XERO API Connection Active";
    $xero_style = "btn-success";
} else {
    $xero_status = "XERO Token Missing or Error";
    $xero_style = "btn-danger";
}

$database = new Database();
$conn = $database->connect();
$query = "SELECT * FROM tblAccounting WHERE company_id = :company_id";
$result = $conn->prepare($query);
$result->bindParam(':company_id', $company_id);
$result->execute();
while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
?>
<main id="main" class="main">
    <div class="container mt-5">
        <div class="card">
            <div class="card-header">
                <h4>SETUP XERO</h4>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <button class="btn <?php echo $xero_style; ?>"><?php echo $xero_status; ?></button>
                </div>

                <div class="row mb-3">
                    <label for="payable_account_name" class="col-md-1 col-lg-1 col-form-label">Expire on</label>
                    <div class="col-md-2 col-lg-2">
                        <input name="token_expire" type="text" class="form-control" disabled id="token_expire" value="<?php echo $token_expire ? date('d-m-Y H:i', $token_expire) : ''; ?>"/>
                    </div>
                </div>

                <div class="mt-4">
                    <h5><b>If you have a Token Missing or Error</b></h5>
                    <ol>
                        <li class="active">
                            <a href="<?php echo XERO_AUTHORIZE_URL; ?>?response_type=code&client_id=<?php echo XERO_CLIENT_ID; ?>&redirect_uri=<?php echo urlencode(XERO_REDIRECT_URI); ?>&scope=offline_access accounting.transactions accounting.contacts accounting.settings&state=<?php echo urlencode($_SESSION['xero_oauth_state']); ?>" target="_self">
                                Step 1: Click here to generate a new access code & allow access when prompted.
                            </a>
                        </li>
                        <div class="mb-3">
                            <input type="text" name="code_text" disabled id="code_text" value="<?php echo isset($_GET['code']) ? htmlspecialchars($_GET['code'], ENT_QUOTES, 'UTF-8') : ''; ?>" class="form-control" />
                            <input type="hidden" id="xero_state" value="<?php echo isset($_GET['state']) ? htmlspecialchars($_GET['state'], ENT_QUOTES, 'UTF-8') : ''; ?>">
                        </div>
                        <li class="active">
                            <button id="generate-tokens" class="btn btn-secondary">Step 2: Click here to generate new access and refresh tokens</button>
                        </li>
                    </ol>
                    <div id="ajax-response" class="mt-3"></div>
                </div>
            </div>
        </div>
    </div>
</main>

<script>
    $(document).ready(function() {
        $('#generate-tokens').click(function() {
            var code = $('#code_text').val();
            $.ajax({
                url: 'includes_pages/admin_accounting/xero_functions.php',
                method: 'POST',
                data: {
                    get_new_access_token: true,
                    code: code,
                    state: $('#xero_state').val()
                },
                success: function(response) {
                    $('#ajax-response').html(response);
                    if (response.includes('Tokens updated successfully')) {
                        $('.btn.btn-danger').removeClass('btn-danger').addClass('btn-success').text('XERO API Connection Token Ok');
                    }
                },
                error: function(xhr, status, error) {
                    $('#ajax-response').html('<div class="alert alert-danger">An error occurred: ' + error + '</div>');
                }
            });
        });
    });
</script>
<?php
}
?>
