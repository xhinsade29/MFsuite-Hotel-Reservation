<?php
session_start();
$theme_preference = $_SESSION['theme_preference'] ?? 'dark';
error_reporting(E_ALL);
ini_set('display_errors', 1);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Register - MFsuite Hotel</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet"/>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" />
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
      max-width: 500px;
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
    .btn-outline-secondary {
      border-color: #ff8c00;
      color: #ff8c00;
      border-radius: 8px;
      font-weight: 500;
      transition: background 0.2s, color 0.2s;
    }
    .btn-outline-secondary:hover {
      background: #ff8c00;
      color: #fff;
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
    .strength-meter {
      height: 6px;
      border-radius: 4px;
      background: #eee;
      margin-top: 4px;
      margin-bottom: 10px;
      overflow: hidden;
    }
    .strength-bar {
      height: 100%;
      transition: width 0.3s;
    }
    .strength-weak { width: 33%; background: #dc3545; }
    .strength-medium { width: 66%; background: #ffc107; }
    .strength-strong { width: 100%; background: #28a745; }
  </style>
</head>
<body class="<?php echo ($theme_preference === 'light') ? 'light-mode' : ''; ?>">

  <!-- Toast Container -->
  <div class="toast-container">
    <?php if (isset($_SESSION['success'])): ?>
    <div class="toast toast-success" role="alert" aria-live="assertive" aria-atomic="true" data-bs-delay="3000">
      <div class="toast-header">
        <i class="bi bi-check-circle-fill text-success me-2"></i>
        <strong class="me-auto">Success</strong>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="toast" aria-label="Close"></button>
      </div>
      <div class="toast-body">
        <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
      </div>
    </div>
    <?php endif; ?>
    <?php if (isset($_SESSION['error'])): ?>
    <div class="toast toast-error" role="alert" aria-live="assertive" aria-atomic="true" data-bs-delay="3000">
      <div class="toast-header">
        <i class="bi bi-exclamation-circle-fill text-danger me-2"></i>
        <strong class="me-auto">Error</strong>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="toast" aria-label="Close"></button>
      </div>
      <div class="toast-body">
        <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
      </div>
    </div>
    <?php endif; ?>
  </div>
  <div class="container">
    <div class="row justify-content-center align-items-center min-vh-100">
      <div class="col-lg-7 col-md-8">
        <div class="glass-card shadow">
          <div class="text-center mb-4">
            <img src="../assets/MFsuites_logo.png" alt="Hotel Logo" class="logo mb-3" />
            <h3 class="mt-3">Create Account</h3>
            <div class="welcome">Join us and enjoy your stay at MF Suites Hotel!</div>
          </div>
          <form action="process_register.php" method="POST" id="registerForm" autocomplete="off">
            <div class="row mb-3">
              <div class="col-md-4 mb-2 mb-md-0">
                <div class="form-floating">
                  <input type="text" class="form-control" name="firstname" id="firstname" placeholder="First Name" required value="<?php echo isset($_SESSION['form_data']['firstname']) ? htmlspecialchars($_SESSION['form_data']['firstname']) : ''; ?>" />
                  <label for="firstname"><i class="bi bi-person me-1"></i> First Name</label>
                </div>
              </div>
              <div class="col-md-4 mb-2 mb-md-0">
                <div class="form-floating">
                  <input type="text" class="form-control" name="middlename" id="middlename" placeholder="Middle Name" value="<?php echo isset($_SESSION['form_data']['middlename']) ? htmlspecialchars($_SESSION['form_data']['middlename']) : ''; ?>" />
                  <label for="middlename"><i class="bi bi-person-lines-fill me-1"></i> Middle Name</label>
                </div>
              </div>
              <div class="col-md-4">
                <div class="form-floating">
                  <input type="text" class="form-control" name="lastname" id="lastname" placeholder="Last Name" required value="<?php echo isset($_SESSION['form_data']['lastname']) ? htmlspecialchars($_SESSION['form_data']['lastname']) : ''; ?>" />
                  <label for="lastname"><i class="bi bi-person-badge me-1"></i> Last Name</label>
                </div>
              </div>
            </div>
            <div class="mb-3">
              <div class="form-floating">
                <input type="tel" class="form-control" name="phone" id="phone" placeholder="Phone Number" required value="<?php echo isset($_SESSION['form_data']['phone']) ? htmlspecialchars($_SESSION['form_data']['phone']) : ''; ?>" />
                <label for="phone"><i class="bi bi-telephone me-1"></i> Phone Number</label>
              </div>
            </div>
            <div class="mb-3">
              <div class="form-floating">
                <input type="email" class="form-control" name="email" id="email" placeholder="Email Address" required value="<?php echo isset($_SESSION['form_data']['email']) ? htmlspecialchars($_SESSION['form_data']['email']) : ''; ?>" />
                <label for="email"><i class="bi bi-envelope-at me-1"></i> Email Address</label>
              </div>
            </div>
            <div class="mb-3">
              <div class="form-floating">
                <textarea class="form-control" name="address" id="address" placeholder="Address" rows="2" required style="height: 60px"><?php echo isset($_SESSION['form_data']['address']) ? htmlspecialchars($_SESSION['form_data']['address']) : ''; ?></textarea>
                <label for="address"><i class="bi bi-geo-alt me-1"></i> Address</label>
              </div>
            </div>
            <div class="mb-3">
              <div class="form-floating position-relative">
                <input type="password" class="form-control" name="password" id="password" placeholder="Password" required />
                <label for="password"><i class="bi bi-lock me-1"></i> Password</label>
                <button class="btn position-absolute end-0 top-0 mt-2 me-2" type="button" tabindex="-1" onclick="togglePassword('password', this)">
                  <i class="bi bi-eye"></i>
                </button>
              </div>
              <div class="strength-meter" id="strengthMeter"><div class="strength-bar" id="strengthBar"></div></div>
            </div>
            <div class="mb-3">
              <div class="form-floating position-relative">
                <input type="password" class="form-control" name="confirm_password" id="confirm_password" placeholder="Confirm Password" required />
                <label for="confirm_password"><i class="bi bi-lock-fill me-1"></i> Confirm Password</label>
                <button class="btn position-absolute end-0 top-0 mt-2 me-2" type="button" tabindex="-1" onclick="togglePassword('confirm_password', this)">
                  <i class="bi bi-eye"></i>
                </button>
              </div>
            </div>
            <div class="d-grid gap-2 mt-3">
              <button type="submit" class="btn btn-primary" id="registerBtn">Register <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span></button>
              <a href="../pages/login.php" class="btn btn-outline-secondary">Already have an account? Login</a>
            </div>
          </form>
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
    // Password strength meter
    const passwordInput = document.getElementById('password');
    const strengthBar = document.getElementById('strengthBar');
    passwordInput.addEventListener('input', function() {
      const val = passwordInput.value;
      let strength = 0;
      if (val.length > 7) strength++;
      if (val.match(/[A-Z]/)) strength++;
      if (val.match(/[0-9]/)) strength++;
      if (val.match(/[^A-Za-z0-9]/)) strength++;
      if (strength <= 1) {
        strengthBar.className = 'strength-bar strength-weak';
      } else if (strength === 2 || strength === 3) {
        strengthBar.className = 'strength-bar strength-medium';
      } else {
        strengthBar.className = 'strength-bar strength-strong';
      }
    });
    // Show spinner on register
    document.getElementById('registerForm').addEventListener('submit', function(e) {
      var btn = document.getElementById('registerBtn');
      btn.classList.add('btn-loading');
    });
    // Initialize all toasts
    document.addEventListener('DOMContentLoaded', function() {
      var toastElList = [].slice.call(document.querySelectorAll('.toast'));
      var toastList = toastElList.map(function(toastEl) {
        return new bootstrap.Toast(toastEl, {
          autohide: true,
          delay: 3000
        });
      });
      toastList.forEach(toast => toast.show());
    });
  </script>
</body>
</html>
