<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Login - MF Suites Hotel</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
  <style>
    :root {
      --primary: #FF8C00;
      --background: #11101d;
      --input-bg:rgb(255, 255, 255);
      --text-light:rgb(0, 0, 0);
      --text-muted: rgb(255, 255, 255);
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

    /* Toast Styles */
    .toast-container {
      position: fixed;
      top: 20px;
      right: 20px;
      z-index: 1050;
    }

    .toast {
      background: #1f1d2e;
      color: var(--text-light);
      border: 1px solid rgba(255, 255, 255, 0.1);
    }

    .toast-header {
      background: rgba(255, 255, 255, 0.05);
      color: var(--text-light);
      border-bottom: 1px solid rgba(255, 255, 255, 0.1);
    }

    .toast-success {
      border-left: 4px solid #28a745;
    }

    .toast-error {
      border-left: 4px solid #dc3545;
    }
  </style>
</head>
<body>
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
      <div class="col-md-5 col-lg-4">
        <div class="card shadow">
          <div class="card-body">
            <div class="text-center mb-4">
              <img src="../assets/MFsuites_logo.png" alt="Hotel Logo" class="img-fluid mb-3" style="max-width: 140px;">
              <h3 class="mt-3 text-white" style="font-weight: 600;">Login</h3>
              <h4 class="fw-semibold"></h4>
              <p class="text-muted" style="font-size: 0.9rem;">Login to your account</p>
            </div>

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

            <div class="text-center">
              <a href="register.php" class="btn btn-outline-secondary w-100">Create New Account</a>
            </div>
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

    // Initialize all toasts
    document.addEventListener('DOMContentLoaded', function() {
      console.log('DOM Content Loaded');
      var toastElList = [].slice.call(document.querySelectorAll('.toast'));
      console.log('Found toasts:', toastElList.length);
      
      var toastList = toastElList.map(function(toastEl) {
        console.log('Initializing toast:', toastEl);
        return new bootstrap.Toast(toastEl, {
          autohide: true,
          delay: 3000
        });
      });
      
      toastList.forEach(toast => {
        console.log('Showing toast');
        toast.show();
      });
    });
  </script>
</body>
</html>
