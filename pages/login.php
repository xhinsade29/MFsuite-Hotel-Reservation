<?php session_start();
$theme_preference = $_SESSION['theme_preference'] ?? 'dark'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Login - MF Suites Hotel</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
  <style>
    body {
      min-height: 100vh;
      background: linear-gradient(135deg, #23234a 0%, #ff8c00 100%);
      font-family: 'Poppins', sans-serif;
      color: #fff;
      display: flex;
      align-items: center;
      justify-content: center;
    }
    .glass-card {
      background: rgba(31, 29, 46, 0.85);
      border-radius: 18px;
      box-shadow: 0 8px 32px 0 rgba(31, 38, 135, 0.18);
      backdrop-filter: blur(8px);
      -webkit-backdrop-filter: blur(8px);
      border: 1px solid rgba(255,255,255,0.08);
      padding: 2.5rem 2rem 2rem 2rem;
      max-width: 400px;
      width: 100%;
      margin: 0 auto;
      position: relative;
    }
    .glass-card .logo {
      max-width: 120px;
      margin-bottom: 1.2rem;
      filter: drop-shadow(0 2px 8px #ff8c0033);
    }
    .glass-card h3 {
      font-weight: 700;
      margin-bottom: 0.5rem;
      letter-spacing: 1px;
    }
    .glass-card .welcome {
      color: #ffa533;
      font-size: 1.1rem;
      margin-bottom: 1.5rem;
    }
    .form-floating > .form-control, .form-floating > .form-label {
      color: #23234a;
    }
    .form-floating > .form-control {
      background: rgba(255,255,255,0.95);
      border-radius: 8px;
      border: 1px solid #eee;
      font-size: 1.1rem;
      box-shadow: none;
      transition: border-color 0.2s;
    }
    .form-floating > .form-control:focus {
      border-color: #ff8c00;
      box-shadow: 0 0 0 2px #ff8c0033;
    }
    .form-floating > label {
      color: #888;
      font-weight: 500;
    }
    .input-group .btn {
      background: transparent;
      color: #ff8c00;
      border: none;
      font-size: 1.2rem;
      transition: color 0.2s;
    }
    .input-group .btn:focus, .input-group .btn:hover {
      color: #e67c00;
      background: transparent;
    }
    .btn-primary {
      background: linear-gradient(90deg, #ff8c00 60%, #ffa533 100%);
      border: none;
      border-radius: 8px;
      font-weight: 600;
      font-size: 1.1rem;
      letter-spacing: 1px;
      box-shadow: 0 2px 8px #ff8c0033;
      transition: background 0.2s, transform 0.1s;
    }
    .btn-primary:active {
      transform: scale(0.98);
    }
    .btn-primary:disabled {
      opacity: 0.7;
      pointer-events: none;
    }
    .forgot-link {
      color: #ffa533;
      font-size: 0.98rem;
      text-decoration: underline;
      margin-top: 0.5rem;
      display: inline-block;
      transition: color 0.2s;
    }
    .forgot-link:hover {
      color: #ff8c00;
    }
    .toast-container {
      position: fixed;
      top: 20px;
      right: 20px;
      z-index: 1050;
    }
    .toast {
      background: #1f1d2e;
      color: #fff;
      border: 1px solid rgba(255, 255, 255, 0.1);
      border-radius: 10px;
      min-width: 260px;
    }
    .toast-header {
      background: rgba(255, 255, 255, 0.05);
      color: #fff;
      border-bottom: 1px solid rgba(255, 255, 255, 0.1);
    }
    .toast-success { border-left: 4px solid #28a745; }
    .toast-error { border-left: 4px solid #dc3545; }
    .spinner-border {
      width: 1.2rem;
      height: 1.2rem;
      vertical-align: middle;
      margin-left: 0.5rem;
      display: none;
    }
    .btn-loading .spinner-border {
      display: inline-block;
    }
  </style>
</head>
<body class="<?php echo ($theme_preference === 'light') ? 'light-mode' : ''; ?>">

<!-- Toast Notification -->
<div aria-live="polite" aria-atomic="true" class="position-fixed top-0 end-0 p-3" style="z-index: 2000; min-width: 320px;">
  <?php if (isset($_SESSION['success'])): ?>
    <div class="toast align-items-center text-bg-success border-0 show" id="successToast" role="alert" aria-live="assertive" aria-atomic="true">
      <div class="d-flex">
        <div class="toast-body">
          <i class="bi bi-check-circle me-2"></i> <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
        </div>
        <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
      </div>
    </div>
  <?php endif; ?>
  <?php if (isset($_SESSION['error'])): ?>
    <div class="toast align-items-center text-bg-danger border-0 show" id="errorToast" role="alert" aria-live="assertive" aria-atomic="true">
      <div class="d-flex">
        <div class="toast-body">
          <i class="bi bi-exclamation-triangle me-2"></i> <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
        </div>
        <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
      </div>
    </div>
  <?php endif; ?>
</div>
  <div class="container">
    <div class="row justify-content-center align-items-center min-vh-100">
      <div class="col-md-5 col-lg-4">
        <div class="glass-card shadow">
          <div class="text-center mb-4">
            <img src="../assets/MFsuites_logo.png" alt="Hotel Logo" class="logo mb-3">
            <h3 class="mt-3 text-white">Login</h3>
            <div class="welcome">Welcome back! Please login to your account.</div>
          </div>
          <form action="process_login.php" method="POST" id="loginForm" autocomplete="off">
            <div class="form-floating mb-3">
              <input type="email" class="form-control" name="user_email" id="user_email" placeholder="Email address" required>
              <label for="user_email"><i class="bi bi-envelope-at me-1"></i> Email address</label>
            </div>
            <div class="form-floating mb-3 position-relative">
              <input type="password" class="form-control" name="password" id="password" placeholder="Password" required>
              <label for="password"><i class="bi bi-lock me-1"></i> Password</label>
              <button class="btn position-absolute end-0 top-0 mt-2 me-2" type="button" tabindex="-1" onclick="togglePassword('password', this)">
                <i class="bi bi-eye"></i>
              </button>
            </div>
            <div class="d-flex justify-content-between align-items-center mb-3">
              <a href="forgot_password.php" class="forgot-link">Forgot password?</a>
            </div>
            <button type="submit" class="btn btn-primary w-100 mb-2" id="loginBtn">
              Login
              <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
            </button>
          </form>
          <div class="text-center mt-3">
            <a href="register.php" class="btn btn-outline-secondary w-100">Create New Account</a>
          </div>
        </div>
      </div>
    </div>
  </div>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  <script>
    function togglePassword(inputId, btn) {
      const input = document.getElementById(inputId);
      const icon = btn.querySelector('i');
      if (input.type === 'password') {
        input.type = 'text';
        icon.className = 'bi bi-eye-slash';
      } else {
        input.type = 'password';
        icon.className = 'bi bi-eye';
      }
    }
    // Show spinner on login
    document.getElementById('loginForm').addEventListener('submit', function(e) {
      var btn = document.getElementById('loginBtn');
      btn.classList.add('btn-loading');
    });
    document.addEventListener('DOMContentLoaded', function() {
      var successToast = document.getElementById('successToast');
      if (successToast) {
        var toast = new bootstrap.Toast(successToast, { delay: 3500 });
        toast.show();
      }
      var errorToast = document.getElementById('errorToast');
      if (errorToast) {
        var toast = new bootstrap.Toast(errorToast, { delay: 3500 });
        toast.show();
      }
    });
  </script>
</body>
</html>
