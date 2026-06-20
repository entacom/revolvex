<?php
error_reporting(E_ALL);
ini_set('display_errors', 'Off');
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>RevolveX Login</title>
  <link href="assets/vendor/bootstrap/css/bootstrap.css" rel="stylesheet">
  <style>
    body {
      margin: 0;
      height: 100vh;
      background: linear-gradient(rgba(0,0,0,0.5), rgba(0,0,0,0.5)),
                  url('assets/img/roof-purlin-factory2.png') no-repeat center center/cover;
      display: flex;
      justify-content: center;
      align-items: center;
      font-family: Arial, sans-serif;
    }

    .login-card {
      background: rgba(255, 255, 255, 0.9);
      border-radius: 12px;
      box-shadow: 0 4px 20px rgba(0,0,0,0.3);
      padding: 2rem;
      max-width: 380px;
      width: 100%;
    }

    .login-logo {
      display: block;
      margin: 0 auto 1rem auto;
      max-width: 200px;
    }

    .form-label {
      font-weight: 600;
    }

    .form-control {
      border-radius: 8px;
      padding: 0.75rem;
      font-size: 1rem;
    }

    .btn-login {
      border-radius: 8px;
      padding: 0.75rem;
      font-size: 1rem;
    }
  </style>
</head>
<body>
  <div class="login-card text-center">
    <?php if (!empty($login_error)): ?>
      <div class="alert alert-danger text-start" role="alert"><?php echo htmlspecialchars($login_error); ?></div>
    <?php endif; ?>

    <form method="post" action="index.php">
      <div class="mb-3 text-start">
        <label for="username" class="form-label">Username</label>
        <input type="email" class="form-control" id="username" name="username" autocomplete="username" required>
      </div>
      <div class="mb-3 text-start">
        <label for="password" class="form-label">Password</label>
        <input type="password" class="form-control" id="password" name="password" autocomplete="current-password" required>
      </div>
      <button class="btn btn-secondary w-100 btn-login" type="submit" name="action" value="Login">Sign in</button>
    </form>
  </div>
</body>
</html>
