<?php
session_start();
$logout_toast = false;
if (isset($_SESSION['admin_logout_success'])) {
    $logout_toast = true;
    unset($_SESSION['admin_logout_success']);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Login Admin - MF Suites Hotel</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
  <style>
    :root {
      --primary: #FF8C00;
      --background: #11101d;
      --input-bg:rgb(255, 255, 255);
      --text-light:rgb(0, 0, 0);
      --text-muted: rgba(255, 255, 255, 0.6);
    }

    body {
      background: var(--background);
      font-family: 'Poppins', sans-serif;
      color: var(--text-light);
      height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
    }

    .card {
      background: #1f1d2e;
      border: none;
      color: var(--text-light);
      border-radius: 12px;
      padding: 2rem;
    }

    .form-control {
      background: var(--input-bg);
      border: 1px solid rgba(255, 255, 255, 0.1);
      color: var(--text-light);
    }

    .form-control:focus {
      background: var(--input-bg);
      color: var(--text-light);
      border-color: var(--primary);
      box-shadow: 0 0 0 0.2rem rgba(255, 140, 0, 0.25);
    }

    .btn-primary {
      background-color: var(--primary);
      border-color: var(--primary);
    }

    .btn-primary:hover {
      background-color: #e67c00;
      border-color: #e67c00;
    }

    .btn-outline-secondary {
      border-color: var(--primary);
      color: var(--primary);
    }

    .btn-outline-secondary:hover {
      background-color: var(--primary);
      color: #fff;
    }

    .input-group .btn {
      border-color: rgba(255, 255, 255, 0.1);
      background: var(--input-bg);
      color: var(--text-muted);
    }

    .input-group .btn:hover {
      background: rgba(255, 255, 255, 0.05);
    }

    a {
      text-decoration: none;
    }
  </style>
</head>
<body>

<?php if ($logout_toast): ?>
<div class="position-fixed top-0 end-0 p-3" style="z-index: 1100;">
    <div id="logoutToast" class="toast align-items-center text-bg-success border-0 show" role="alert" aria-live="assertive" aria-atomic="true" data-bs-delay="3000">
        <div class="d-flex">
            <div class="toast-body">
                You have successfully logged out.
            </div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
        </div>
    </div>
</div>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        var toastEl = document.getElementById('logoutToast');
        if (toastEl) {
            var toast = new bootstrap.Toast(toastEl, { delay: 3000 });
            toast.show();
        }
    });
</script>
<?php endif; ?>

  <div class="container">
    <div class="row justify-content-center align-items-center min-vh-100">
      <div class="col-md-5 col-lg-4">
        <div class="card shadow">
          <div class="card-body">
            <div class="text-center mb-4">
              <img src="../assets/MFsuites_logo.png" alt="Hotel Logo" class="img-fluid mb-3" style="max-width: 140px;">
              <h4 class="fw-semibold">Admin Login</h4>
              <p class="text-muted" style="font-size: 0.9rem;">Login to your account</p>
            </div>

            <?php
            include '../pages/notification.php';
            $msg = $_SESSION['msg'] ?? '';
            $type = $_SESSION['msg_type'] ?? '';
            unset($_SESSION['msg'], $_SESSION['msg_type']);
            ?>

            <?php if ($msg): ?>
                <?php show_toast($msg, $type ?: 'info'); ?>
            <?php endif; ?>

            <form action="process_login.php" method="POST">
              <div class="mb-3">
                <input type="email" class="form-control" name="email" placeholder="Email address" required>
              </div>
              <div class="mb-3">
                <div class="input-group">
                  <input type="password" class="form-control" name="password" id="password" placeholder="Password" required>
                  <button class="btn" type="button" onclick="togglePassword('password')">
                    <i class="bi bi-eye"></i>
                  </button>
                </div>
              </div>
              <button type="submit" class="btn btn-primary w-100 mb-3">Login</button>
            </form>
          </div>
        </div>
      </div>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  <script>
    function togglePassword(inputId) {
      const input = document.getElementById(inputId);
      const icon = event.currentTarget.querySelector('i');
      if (input.type === 'password') {
        input.type = 'text';
        icon.className = 'bi bi-eye-slash';
      } else {
        input.type = 'password';
        icon.className = 'bi bi-eye';
      }
    }
  </script>

</body>
</html>
