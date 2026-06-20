<?php
session_start();
include('includes/common.php');

// Enable error reporting for debugging
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(E_ALL);

// Check if the user is logged in and has a valid session
if (!isset($_SESSION['session_user_id'])) {
    header("Location: login.php");
    exit();
}

$currentPassword = getTableField('password', 'tblUsers', $_SESSION['session_user_id']);
if (!isset($_SESSION['setup_pending']) && !empty($currentPassword)) {
    header("Location: index.php");
    exit();
}

// Function to validate the password
function validatePassword($password) {
    $pattern = '/^(?=.*[0-9])(?=.*[a-z])(?=.*[A-Z])(?=.*[@#$%^&+=!])(?=\S+$).{8,}$/';
    return preg_match($pattern, $password);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate the new password
    if (!empty($_POST['password']) && !empty($_POST['confirm_password']) && $_POST['password'] === $_POST['confirm_password']) {
        if (validatePassword($_POST['password'])) {
            $newPassword = password_hash($_POST['password'], PASSWORD_BCRYPT);
            $userId = $_SESSION['session_user_id'];

            // Update the user's password in the database
            try {
                $database = new Database();
                $conn = $database->connect();
                $stmt = $conn->prepare("UPDATE tblUsers SET password = :password WHERE id = :id");
                $stmt->bindParam(':password', $newPassword);
                $stmt->bindParam(':id', $userId, PDO::PARAM_INT);
                $stmt->execute();
                consumeSetupToken($userId);
                unset($_SESSION['setup_pending']);

                // Redirect to dashboard or login page
                header("Location: index.php");
                exit();
            } catch (Exception $e) {
                $error = "An error occurred: " . $e->getMessage();
            }
        } else {
            $error = "Password must be at least 8 characters long and include at least one digit, one lowercase letter, one uppercase letter, one special character, and must not contain spaces or non-ASCII characters.";
        }
    } else {
        $error = "Passwords do not match or are empty.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
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
  </style>
</head>
<body>
  <div class="container pt-3">
    <div class="row justify-content-center">
      <div class="col-sm-12 col-md-6 col-lg-4">
        <div class="card border-info text-center">
          <div class="card-body">
			  <h4 class="text-center"><?php echo getTableField('company_name','tblCompany',1); ?></h4>
            <h5 class="text-center">Update Password</h5>
            <form class="form-signin" role="form" method="post" action="">
              <input type="password" class="form-control mb-2" placeholder="Password" id="password" name="password" required>
              <input type="password" class="form-control mb-2" placeholder="Confirm Password" id="confirm_password" name="confirm_password" required>
              <button class="btn btn-lg btn-secondary btn-block mb-1" type="submit">Confirm</button>
              <br>
              <?php if (isset($error)): ?>
                <p style="color: red;"><?php echo $error; ?></p>
              <?php endif; ?>
            </form>
          </div>
        </div>
      </div>
    </div>
  </div>
</body>
</html>
