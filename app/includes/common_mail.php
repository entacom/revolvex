<?php

error_reporting(E_ALL);
ini_set('display_errors', 0);
session_start();

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

include("common.php");

// Include PHPMailer classes
require $_SERVER['DOCUMENT_ROOT'].'/assets/vendor/php-mailer/src/PHPMailer.php';
require $_SERVER['DOCUMENT_ROOT'].'/assets/vendor/php-mailer/src/Exception.php';
require $_SERVER['DOCUMENT_ROOT'].'/assets/vendor/php-mailer/src/SMTP.php';

/**
 * SINGLE SMTP factory for system emails (cPanel mailbox).
 * Keeps SPF/DKIM/DMARC aligned and avoids dupe config everywhere.
 */
function newSystemMailer(string $fromName, ?string $replyToEmail = null): PHPMailer {
    $mail = new PHPMailer(true);
    $smtp = getSystemSmtpConfig();

    // ---- cPanel SMTP ----
    $mail->isSMTP();
    $mail->Host       = $smtp['host'];
    $mail->SMTPAuth   = true;
    $mail->Username   = $smtp['username'];
    $mail->Password   = $smtp['password'];
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS; // STARTTLS on 587
    $mail->Port       = $smtp['port'];

    // Encoding / charset
    $mail->CharSet    = 'UTF-8';
    $mail->Encoding   = 'base64';

    // From / identity
    $mail->setFrom($smtp['from'], $fromName);

    // Envelope sender / Return-Path (ensure this mailbox/alias exists or forwards)
    $mail->Sender = $smtp['bounce'];

    // Reply-To (optional)
    if (!empty($replyToEmail)) {
        $mail->addReplyTo($replyToEmail, $fromName);
    }

    // Helpful headers
    $mail->addCustomHeader('X-Mailer', 'PHPMailer');


    return $mail;
}

function getSystemSmtpConfig(): array {
    $host = defined('SMTP_HOST') ? SMTP_HOST : 'mail.revolvex.com.au';
    $port = defined('SMTP_PORT') ? (int)SMTP_PORT : 587;
    $username = defined('SMTP_USERNAME') ? SMTP_USERNAME : '';
    $password = defined('SMTP_PASSWORD') ? SMTP_PASSWORD : '';
    $from = defined('SMTP_FROM') ? SMTP_FROM : $username;
    $bounce = defined('SMTP_BOUNCE') ? SMTP_BOUNCE : $from;

    if (($username === '' || $password === '') && !empty($_SESSION['session_company_id'])) {
        $company_id = $_SESSION['session_company_id'];
        $username = $username !== '' ? $username : (string)getTableField('smtp_username', 'tblCompany', $company_id);
        $password = $password !== '' ? $password : (string)getTableField('smtp_password', 'tblCompany', $company_id);
        $from = $from !== '' ? $from : $username;
        $bounce = $bounce !== '' ? $bounce : $from;
    }

    if ($username === '' || $password === '') {
        throw new Exception('System SMTP credentials are not configured.');
    }

    return array(
        'host' => $host,
        'port' => $port,
        'username' => $username,
        'password' => $password,
        'from' => $from,
        'bounce' => $bounce
    );
}

/* =========================
   Email headers and footers
   ========================= */

function getEmailHeader() {
    $header = '<html>
        <head><title>Email</title></head>
        <body>
            <div style="background:#fff;padding:10px;"></div>
            <div style="padding:20px;">';
    return $header;
}

function getEmailFooter() {
    if (!isset($_SESSION['session_company_id']) || empty($_SESSION['session_company_id'])) {
        return 'Error: Company ID is not set in session.';
    }

    $companyName  = getTableField('company_name', 'tblCompany', $_SESSION['session_company_id']);
    $companyEmail = getTableField('company_email', 'tblCompany', $_SESSION['session_company_id']);
    $companyPhone = getTableField('company_phone', 'tblCompany', $_SESSION['session_company_id']);

    if ($companyName === false || $companyEmail === false || $companyPhone === false) {
        return 'Error: Unable to retrieve company details.';
    }

    $footer = '</div>
        <div style="background:#fff;padding:20px;">
            <div>Regards;</div>
            <div>' . htmlspecialchars($companyName) . '</div>
            <div>Email: ' . htmlspecialchars($companyEmail) . '</div>
            <div>Phone: ' . htmlspecialchars($companyPhone) . '</div>
        </div>
    </body>
    </html>';

    return $footer;
}

/* ================================
   Example/basic helper email funcs
   ================================ */

function sendNewUserEmail($email_to) {
    $header = getEmailHeader();
    $footer = getEmailFooter();

    $emailContent = '
        <h2>New User Added</h2>
        <p>This is a basic email message.</p>
    ';

    $completeEmailContent = $header . $emailContent . $footer;

    $companyName  = getTableField('company_name', 'tblCompany', $_SESSION['session_company_id']);
    $replyToEmail = getTableField('company_email', 'tblCompany', $_SESSION['session_company_id']);

    $mail = newSystemMailer($companyName, $replyToEmail);

    try {
        $mail->addAddress($email_to);
        $mail->isHTML(true);
        $mail->Subject = 'New user added';
        $mail->Body    = $completeEmailContent;
        $mail->AltBody = "A new user has been added to {$companyName}.";

        $mail->send();
    } catch (Exception $e) {
        // error_log("Email could not be sent. Error: {$mail->ErrorInfo}");
    }
}

if (isset($_GET['notify_user_added'])) {
    $header = getEmailHeader();
    $footer = getEmailFooter();
    $server_url = $_SERVER['HTTP_HOST'];
    $setup_token = createSetupToken($_POST['userId']);
    if (!$setup_token) {
        echo json_encode(['success' => false, 'message' => 'Could not create setup token']);
        exit;
    }
    $link_token = "https://" . $server_url . "?setup_token=" . urlencode($setup_token);

    $emailContent = '
        <p>Hello, We are excited to announce that you now have access to our online portal.</p>
        <p>To access your portal, please use the following secure link: <a href="' . $link_token . '">PLEASE CLICK HERE TO COMPLETE THE SETUP</a></p>
        <p>Should you require any assistance or have any questions, feel free to contact us.</p>
        <p>Your Username: '.getTableField('username', 'tblUsers', $_POST['userId']).'</p>';

    $completeEmailContent = $header . $emailContent . $footer;

    $email_to       = getTableField('email', 'tblUsers', $_POST['userId']);
    $email_to_name  = getTableField('first_lastname', 'tblUsers', $_POST['userId']);
    $email_extra    = getTableField('email', 'tblUsers', $_SESSION['session_user_id']);
    $email_extra_nm = getTableField('first_lastname', 'tblUsers', $_SESSION['session_user_id']);

    $companyName  = getTableField('company_name', 'tblCompany', $_SESSION['session_company_id']);
    $replyToEmail = getTableField('company_email', 'tblCompany', $_SESSION['session_company_id']);

    $mail = newSystemMailer($companyName, $replyToEmail);

    try {
        $mail->addAddress($email_to, $email_to_name);
        if (!empty($email_extra)) $mail->addAddress($email_extra, $email_extra_nm); // keep as TO

        $mail->isHTML(true);
        $mail->Subject = 'Welcome to our Portal at ' . $companyName;
        $mail->Body    = $completeEmailContent;
        $mail->AltBody = "Welcome to our Portal at {$companyName}.\n\nSetup link: {$link_token}";

        $mail->send();
        echo json_encode(['success' => true, 'message' => 'Email sent successfully']);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Email could not be sent. Error: ' . $mail->ErrorInfo]);
    }
}

function sendVerificationEmail($verificationCode){
    $footer       = getEmailFooter();
    $emailContent = '<p>Device Verification Code: '.$verificationCode.'</p>';
    $complete     = $emailContent . $footer;

    $email_extra      = getTableField('email', 'tblUsers', $_SESSION['session_user_id']);
    $email_extra_name = getTableField('first_lastname', 'tblUsers', $_SESSION['session_user_id']);

    $companyName  = getTableField('company_name', 'tblCompany', $_SESSION['session_company_id']);
    $replyToEmail = getTableField('company_email', 'tblCompany', $_SESSION['session_company_id']);

    $mail = newSystemMailer($companyName, $replyToEmail);

    try {
        if (!empty($email_extra)) $mail->addAddress($email_extra, $email_extra_name);
        $mail->isHTML(true);
        $mail->Subject = 'Verification Code to the Project Portal ' . $companyName;
        $mail->Body    = $complete;
        $mail->AltBody = "Your verification code: {$verificationCode}";

        $mail->send();
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Email could not be sent. Error: ' . $mail->ErrorInfo]);
    }
}

if (isset($_GET['basic_email'])){
    $header = getEmailHeader();
    $footer = getEmailFooter();

    $emailContent = '
        <h2>Basic Email Content</h2>
        <p>This is a basic email message.</p>
    ';
    $complete = $header . $emailContent . $footer;

    $companyName  = getTableField('company_name', 'tblCompany', $_SESSION['session_company_id']);
    $replyToEmail = getTableField('company_email', 'tblCompany', $_SESSION['session_company_id']);

    $mail = newSystemMailer($companyName, $replyToEmail);

    try {
        $mail->addAddress('scott@xxx.com.au', 'Recipient Name');
        $mail->isHTML(true);
        $mail->Subject = 'Subject of Basic Email';
        $mail->Body    = $complete;
        $mail->AltBody = "Basic Email Content\n\nThis is a basic email message.";

        $mail->send();
        echo 'Basic Email has been sent successfully!';
    } catch (Exception $e) {
        echo "Basic Email could not be sent. Error: {$mail->ErrorInfo}";
    }
}

function sendExtendedEmail() {
    $header = getEmailHeader();
    $footer = getEmailFooter();

    $emailContent = '
        <h2>Extended Email Content</h2>
        <p>This is an extended email message with more details.</p>
        <p>Additional information goes here...</p>
    ';
    $complete = $header . $emailContent . $footer;

    $companyName  = getTableField('company_name', 'tblCompany', $_SESSION['session_company_id']);
    $replyToEmail = getTableField('company_email', 'tblCompany', $_SESSION['session_company_id']);

    $mail = newSystemMailer($companyName, $replyToEmail);

    try {
        $mail->addAddress('recipient@example.com', 'Recipient Name');

        $mail->isHTML(true);
        $mail->Subject = 'Subject of Extended Email';
        $mail->Body    = $complete;
        $mail->AltBody = "Extended Email Content\n\nThis is an extended email message with more details.";

        $mail->send();
        echo 'Extended Email has been sent successfully!';
    } catch (Exception $e) {
        echo "Extended Email could not be sent. Error: {$mail->ErrorInfo}";
    }
}

/* ==================================
   PURCHASE ORDER EMAIL (POST Action)
   ================================== */

if (isset($_POST['action']) && $_POST['action'] === 'send_purchase_order_email') {
    $order_id  = $_POST['order_id'];
    $pdfPath   = $_POST['pdf_path'];
    $email_to1 = $_POST['email_to1'];
    $email_to2 = $_POST['email_to2'] ?? null;

    sendPurchaseOrderEmail($order_id, $pdfPath, $email_to1, $email_to2);
}

function sendPurchaseOrderEmail($order_id, $pdfPath, $email_to1, $email_to2 = null) {
    $company_id   = $_SESSION['session_company_id'];
    $companyName  = getTableField('company_name', 'tblCompany', $company_id);
    $replyToEmail = getTableField('company_email', 'tblCompany', $company_id);

    // session user email (KEEP AS TO, per original behaviour)
    $email_extra  = getTableField('email', 'tblUsers', $_SESSION['session_user_id']);

    $header = getEmailHeader();
    $footer = getEmailFooter();

    $emailContent = '<h2>Purchase Order</h2><p>Please find the attached purchase order for your reference.</p>';
    $complete = $header . $emailContent . $footer;

    $absolutePdfPath = $_SERVER['DOCUMENT_ROOT'] . $pdfPath;
    if (!file_exists($absolutePdfPath)) {
        echo json_encode(['success' => false, 'message' => 'PDF not found.']);
        return;
    }

    $mail = newSystemMailer($companyName, $replyToEmail);

    try {
        // TO recipients: customer + session user (internal copy as TO)
        if (!empty($email_extra)) $mail->addAddress($email_extra);
        $mail->addAddress($email_to1);
        if (!empty($email_to2)) $mail->addAddress($email_to2);

        $mail->Subject = 'Purchase Order from ' . $companyName;
        $mail->isHTML(true);
        $mail->Body    = $complete;
        $mail->AltBody = "Purchase Order from {$companyName}.\n\nPlease see the attached purchase order.";

        $mail->addAttachment($absolutePdfPath, "purchase_order_{$order_id}.pdf");

        if (!empty($_FILES['attachments']) && is_array($_FILES['attachments']['name'])) {
            $allowedExtensions = array('pdf', 'jpg', 'jpeg', 'png', 'doc', 'docx', 'xls', 'xlsx', 'csv', 'txt');
            $maxBytes = 10 * 1024 * 1024;

            foreach ($_FILES['attachments']['name'] as $index => $name) {
                if ($_FILES['attachments']['error'][$index] === UPLOAD_ERR_NO_FILE) {
                    continue;
                }

                if ($_FILES['attachments']['error'][$index] !== UPLOAD_ERR_OK) {
                    echo json_encode(['success' => false, 'message' => 'One of the attachments failed to upload.']);
                    return;
                }

                if ((int)$_FILES['attachments']['size'][$index] > $maxBytes) {
                    echo json_encode(['success' => false, 'message' => 'Attachment is too large. Maximum size is 10 MB.']);
                    return;
                }

                $extension = strtolower(pathinfo($name, PATHINFO_EXTENSION));
                if (!in_array($extension, $allowedExtensions, true)) {
                    echo json_encode(['success' => false, 'message' => 'Attachment type not allowed: ' . htmlspecialchars($name)]);
                    return;
                }

                $safeName = preg_replace('/[^A-Za-z0-9._-]+/', '_', basename($name));
                $mail->addAttachment($_FILES['attachments']['tmp_name'][$index], $safeName);
            }
        }

        $mail->send();

        // cleanup after success
        @unlink($absolutePdfPath);

        addPurchaseActivity(
            $order_id,
            $company_id,
            5,
            'Email sent: Purchase order sent to ' . $email_to1,
            $_SESSION['session_user_id'],
            0
        );

        echo json_encode(['success' => true, 'message' => 'Email sent successfully!']);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Email could not be sent. Error: ' . $mail->ErrorInfo]);
    }
}

/* =================================
   SALES ORDER / INVOICE EMAIL (POST)
   ================================= */

if (isset($_POST['action']) && $_POST['action'] === 'send_sales_order_email') {
    $order_id  = $_POST['order_id'];
    $pdfPath   = $_POST['pdf_path'];
    $email_to1 = $_POST['email_to1'];
    $email_to2 = $_POST['email_to2'] ?? null;
    $subject   = $_POST['email_subject'] ?? null;
    $body      = $_POST['email_body'] ?? null;
    $document_type = $_POST['document_type'] ?? 'invoice';

    sendSalesOrderEmail($order_id, $pdfPath, $email_to1, $email_to2, $subject, $body, $document_type);
}

function sendSalesOrderEmailxx($order_id, $pdfPath, $email_to1, $email_to2 = null) {
    $email_extra = getTableField('email', 'tblUsers', $_SESSION['session_user_id']);
    $email_from = getTableField('company_email', 'tblCompany', $_SESSION['session_company_id']);
    $company_name = getTableField('company_name', 'tblCompany', $_SESSION['session_company_id']);
    $company_address = getTableField('company_address', 'tblCompany', $_SESSION['session_company_id']);
    $company_phone = getTableField('company_phone', 'tblCompany', $_SESSION['session_company_id']);

    $header = getEmailHeader();
    //$footer = getEmailFooter();

    // More human-friendly content
    $emailContent = '<h2>Your document from ' . htmlspecialchars($company_name) . '</h2>';
    $emailContent .= '<p>We have prepared your document #' . $order_id . ' and attached it to this email.</p>';
    $emailContent .= '<p>If you have trouble opening the file, contact us using the details below.</p>';



    $completeEmailContent = $header . $emailContent . $footer;

    try {
        $mail = newSystemMailer($company_name, $email_from);

        $mail->addAddress($email_extra);
        $mail->addAddress($email_to1);
        if ($email_to2) {
            $mail->addAddress($email_to2);
        }

        // Add unsubscribe header to help filtering
        $mail->addCustomHeader('List-Unsubscribe', '<mailto:unsubscribe@revolvex.com.au>, <https://revolvex.com.au/unsubscribe>');

        $mail->isHTML(true);
        $mail->Subject = $company_name . ' Document #' . $order_id;
        $mail->Body = $completeEmailContent;
        $mail->AltBody = "Your document #{$order_id} from {$company_name} is attached.\n"
            . "If you have trouble opening it, contact us:\n"
            . "{$company_name}\n{$company_address}\nPhone: {$company_phone}\nEmail: {$email_from}";

        $absolutePdfPath = $_SERVER['DOCUMENT_ROOT'] . $pdfPath;
        if (!file_exists($absolutePdfPath)) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'PDF not found.']);
            exit;
        }

        // Rename attachment for clarity
        $mail->addAttachment($absolutePdfPath, "{$company_name}-Document-{$order_id}.pdf");

        $mail->send();

        if (file_exists($absolutePdfPath)) {
            unlink($absolutePdfPath);
        }

        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'message' => 'Email sent successfully!']);
    } catch (Exception $e) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Email could not be sent. Error: ' . $mail->ErrorInfo]);
    }
}

function generateSalesEmailPdfIfMissing($order_id, $document_type, $absolutePdfPath) {
    $filesDir = dirname($absolutePdfPath);
    if (!is_dir($filesDir)) {
        mkdir($filesDir, 0755, true);
    }

    if (is_file($absolutePdfPath)) {
        return true;
    }

    $pdfScript = 'sales_invoice_v2.php';
    if ($document_type === 'quote') {
        $pdfScript = 'sales_quote_v1.php';
    } elseif ($document_type === 'order_confirmation') {
        $pdfScript = 'sales_order_v1.php';
    }

    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $url = $scheme . '://' . $host . '/pdf/' . $pdfScript . '?order_id=' . urlencode((string)$order_id) . '&s=1';

    $headers = "Cookie: " . session_name() . "=" . session_id() . "\r\n";
    $context = stream_context_create([
        'http' => [
            'method' => 'GET',
            'header' => $headers,
            'timeout' => 20,
            'ignore_errors' => true,
        ],
    ]);

    @file_get_contents($url, false, $context);

    return is_file($absolutePdfPath);
}

function sendSalesOrderEmail($order_id, $pdfPath, $email_to1, $email_to2 = null, $subject = null, $body = null, $document_type = 'invoice') {
    // Always return JSON to your AJAX
    if (!headers_sent()) header('Content-Type: application/json');

    // Basic guards (no assumptions beyond what you already have)
    if (empty($_SESSION['session_company_id']) || empty($_SESSION['session_user_id'])) {
        echo json_encode(['success' => false, 'message' => 'Session not initialised.']); exit;
    }

    $company_id      = $_SESSION['session_company_id'];
    $user_email      = getTableField('email', 'tblUsers', $_SESSION['session_user_id']);

    // Company profile bits (for the body only)
    $company_name    = getTableField('company_name',    'tblCompany', $company_id) ?: 'Your Company';
    $company_address = getTableField('company_address', 'tblCompany', $company_id) ?: '';
    $company_phone   = getTableField('company_phone',   'tblCompany', $company_id) ?: '';
    $company_email   = getTableField('company_email',   'tblCompany', $company_id) ?: '';

    // REQUIRED per-company SMTP creds (you said they live here)
    $smtp_username   = getTableField('smtp_username', 'tblCompany', $company_id); // e.g. system@revolvex.com.au
    $smtp_password   = getTableField('smtp_password', 'tblCompany', $company_id);

    if (empty($smtp_username) || empty($smtp_password)) {
        echo json_encode(['success' => false, 'message' => 'SMTP username/password missing for this company.']); exit;
    }

    $subject = trim((string)$subject);
    $body = trim((string)$body);

    if ($subject === '') {
        $subject = $company_name . ' Document ' . $order_id;
    }

    if ($body === '') {
        $body = "Please find attached your document {$order_id}.";
    }

    $header = getEmailHeader();
    $footer = getEmailFooter();
    $emailContent = nl2br(htmlspecialchars($body, ENT_QUOTES, 'UTF-8'));
    $completeEmailContent = $header . $emailContent . $footer;

    // PDF must exist
    if (!preg_match('#^/files/[A-Za-z0-9._-]+\.pdf$#', $pdfPath)) {
        echo json_encode(['success' => false, 'message' => 'Invalid PDF path.']); exit;
    }

    $absolutePdfPath = $_SERVER['DOCUMENT_ROOT'] . $pdfPath;
    if (!is_file($absolutePdfPath)) {
        generateSalesEmailPdfIfMissing($order_id, $document_type, $absolutePdfPath);
    }

    if (!is_file($absolutePdfPath)) {
        echo json_encode(['success' => false, 'message' => 'PDF not found. The system could not generate the attachment.']); exit;
    }

    try {
        $mail = new PHPMailer(true);
        $mail->CharSet    = 'UTF-8';
        $mail->isSMTP();

        // DO NOT assume custom host/port from DB — use your cPanel relay here
        $mail->Host       = 'mail.revolvex.com.au';
        $mail->SMTPAuth   = true;
        $mail->Username   = $smtp_username;                 // per-company identity
        $mail->Password   = $smtp_password;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS; // STARTTLS on 587
        $mail->Port       = 587;
        $mail->Hostname   = 'dns2.entacom.com.au';          // HELO that matches PTR

        // Align From/Reply-To/Return-Path to the authenticated user
        $mail->setFrom($smtp_username, $company_name);
        $mail->addReplyTo($smtp_username, $company_name);
        $mail->Sender = $smtp_username; // sets Return-Path

        // Recipients (only add non-empty)
        if (!empty($user_email))  $mail->addAddress($user_email);
        if (!empty($email_to1))   $mail->addAddress($email_to1);
        if (!empty($email_to2))   $mail->addAddress($email_to2);

        // Content
        $mail->isHTML(true);
        $mail->Subject = $company_name . ' Document ' . $order_id; // avoid spammy “Invoice #”
        $mail->Body    = $completeEmailContent;
        $mail->AltBody = $body . "\n\nRegards;\n{$company_name}\nEmail: {$company_email}\nPhone: {$company_phone}";

        $mail->Subject = $subject;
        $mail->Body    = $completeEmailContent;
        $mail->AltBody = $body;

        // Attachment (safe filename)
        $safeCompany = preg_replace('/[^A-Za-z0-9\-]+/', '-', (string)$company_name);
        $mail->addAttachment($absolutePdfPath, "{$safeCompany}-{$order_id}.pdf");

        // Send
        $mail->send();
        @unlink($absolutePdfPath);

        if ($document_type === 'quote') {
            addOrderActivity(
                $order_id,
                $company_id,
                5,
                'Email sent: Quote sent to ' . $email_to1,
                $_SESSION['session_user_id'],
                0,
                'quote_emailed'
            );
        } elseif ($document_type === 'order_confirmation') {
            addOrderActivity(
                $order_id,
                $company_id,
                5,
                'Email sent: Order confirmation sent to ' . $email_to1,
                $_SESSION['session_user_id'],
                0,
                'order_confirmation_emailed'
            );
        } else {
            addOrderActivity(
                $order_id,
                $company_id,
                5,
                'Email sent: Invoice sent to ' . $email_to1,
                $_SESSION['session_user_id'],
                0,
                'invoice_emailed'
            );
        }

        echo json_encode(['success' => true, 'message' => 'Email sent successfully!']); exit;
    } catch (PHPMailer\PHPMailer\Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Mailer error: ' . $mail->ErrorInfo]); exit;
    } catch (Throwable $t) {
        echo json_encode(['success' => false, 'message' => 'Error: ' . $t->getMessage()]); exit;
    }
}


?>
