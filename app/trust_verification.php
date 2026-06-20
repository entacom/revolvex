<?php
//ini_set('display_errors', 0);
//ini_set('display_startup_errors', 0);
//error_reporting(E_ALL);

session_start();
include('includes/common_mail.php');

$verificationCodeSent = false;
$verificationFailed = false;

if (!isset($_SESSION['session_user_id'])) {
    header("Location: index.php");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $inputCode = $_POST['verification_code'];

    if ($inputCode == $_SESSION['verification_code']) {
        if (isset($_POST['trust_device'])) {
            setcookie('trusted_device', $_SESSION['session_user_id'], time() + (86400 * 90), "/");
        }
        header("Location: index.php");
        exit;
    } else {
        $verificationFailed = true;
        $verificationCodeSent = true; // Ensure form is shown again for reattempt
    }
} else {
    $verificationCode = rand(100000, 999999);
    $_SESSION['verification_code'] = $verificationCode;

    // Check SMS balance
    $balance = checkSmsBalance();
    if ($balance && $balance > 100) {
        $phoneNumber = getTableField('mobile', 'tblUsers', $_SESSION['session_user_id']);
        $phoneNumber = str_replace(' ', '', $phoneNumber);
        sendVerificationSMS($phoneNumber, $verificationCode);
    } else {
        sendVerificationEmail($verificationCode);
    }
    $verificationCodeSent = true;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verification Page</title>
    <link href="assets/vendor/bootstrap/css/bootstrap.css" rel="stylesheet">
    <style>
        body {
            background-image: url('assets/img/login_wallpaper2.png');
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            background-attachment: fixed;
            margin: 0;
            height: 100vh;
        }
        .verification-form {
            padding-top: 3rem;
        }
        .card {
            border: 1px solid #17a2b8;
        }
        .btn-verify {
            background-color: #007bff;
            color: white;
        }
    </style>
</head>
<body>
    <div class="container verification-form">
        <div class="row justify-content-center">
            <div class="col-sm-12 col-md-6 col-lg-4">
                <div class="card text-center">
                    <div class="card-body">
                        <h4 class="text-center"><?php echo getTableField('company_name', 'tblCompany', 1); ?> - Device Verification</h4>
                        <?php if ($verificationCodeSent): ?>
                            <?php if ($verificationFailed): ?>
                                <p class="text-danger">Incorrect verification code. Please try again.</p>
                            <?php endif; ?>
                            <form action="trust_verification.php" method="post">
                                <input type="text" name="verification_code" class="form-control mb-2" placeholder="Verification Code">
                                <div class="checkbox mb-2">
                                    <label>
                                        <input type="checkbox" name="trust_device" id="trust_device"> Trust this device for 90 days
                                    </label>
                                </div>
                                <button type="submit" class="btn btn-verify btn-block">Verify</button>
                            </form>
                        <?php elseif (isset($_GET['email_sent'])): ?>
                            <p>A verification code has been sent to your email. Please check your email and enter the code here.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
