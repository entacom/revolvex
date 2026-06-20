<?php
session_start();
include('includes/common.php');
if (isset($_POST['action']) && $_POST['action'] == "Login") {
    $login_user_id = confirmUsers($_POST['username'], $_POST['password']);
    if ($login_user_id != 0) {
        session_regenerate_id(true);
        startUserSession($login_user_id);
    }
}

// One-time setup links use setup_token and only work for users without a password.
if (!empty($_GET['setup_token'])) {
    $setup_user_id = verifySetupToken($_GET['setup_token']);
    if ($setup_user_id) {
        session_regenerate_id(true);
        startUserSession($setup_user_id);
        $_SESSION['setup_pending'] = 1;
        header("Location: complete_signup.php");
        exit;
    }
    $login_error = "This setup link is invalid or has expired. Please request a new invite.";
}

if (!empty($_GET['token'])) {
    $login_error = "This login link is no longer supported. Please sign in with your username and password.";
}

if (isset($_GET['p']) && $_GET['p'] == "logout") {
	session_destroy();
    $_SESSION = array(); 
}
if(!isset($_SESSION['session_user_id'] )) {
 	include("login.php");
}

else {


?>	
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta content="width=device-width, initial-scale=1.0" name="viewport">

  <title><?php echo getAppDocumentTitle(); ?></title>
  <meta content="" name="description">
  <meta content="" name="keywords">

  <!-- Favicons -->
  <link href="assets/img/favicon.png" rel="icon">
  <link href="assets/img/apple-touch-icon.png" rel="apple-touch-icon">

  <!-- Google Fonts -->
  <link href="https://fonts.gstatic.com" rel="preconnect">
  <link href="https://fonts.googleapis.com/css?family=Open+Sans:300,300i,400,400i,600,600i,700,700i|Nunito:300,300i,400,400i,600,600i,700,700i|Poppins:300,300i,400,400i,500,500i,600,600i,700,700i" rel="stylesheet">

  <!-- Vendor CSS Files -->
  <link href="assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
  <link href="assets/vendor/bootstrap-icons/bootstrap-icons.css" rel="stylesheet">
  <link href="assets/vendor/boxicons/css/boxicons.min.css" rel="stylesheet">
  <a href="#" class="back-to-top d-flex align-items-center justify-content-center"><i class="bi bi-arrow-up-short"></i></a>
  <script src="assets/vendor/apexcharts/apexcharts.min.js"></script>
  <script src="assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
  <script src="assets/vendor/echarts/echarts.min.js"></script>
  <!-- Template Main JS File -->


  <!-- Template Main CSS File -->
  <link href="assets/css/style.css" rel="stylesheet">
  <link href="assets/css/custom.css?v=<? echo date('h:i');?>" rel="stylesheet">

<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.4/jquery.min.js"></script>
  <script src="assets/vendor/jquery/jquery-ui.min.js"></script>
  <link rel="stylesheet" href="assets/vendor/jquery/jquery-ui.min.css">
<!-- Custom Main JS File -->
<script src="assets/js/site.js?n=<? echo date('h:i');?>"></script> 
     
<body>
<html>


	<?
	 if ($_SESSION['session_group_id'] == 1 ) {
		 include('includes/super_admin_menu.php');
	 }
	if ($_SESSION['session_group_id'] >= 11 && $_SESSION['session_group_id'] <= 30) {
		include('includes/admin_menu.php');
	 }

	  if(isset($_GET['p'])) {
			retrievePage($_GET['p']);
		} else {
			// Handle the case when 'p' is not provided in the URL
			retrievePage(); 
		}

 }
if(isset($_SESSION['token'])){
	?>

  <footer id="footer" class="footer">
    <div class="credits">
       <a href="<? echo getTableField('company_url','tblCompany',1) ;?>"><? echo getTableField('company_name','tblCompany',1) ;?></a>
			<?php } ?>
    </div>
  </footer><!-- End Footer -->
</body>
</html>
	<script src="assets/js/main.js"></script><!-- Needs to be here or causes issues with sidebar -->
