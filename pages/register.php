<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Register - MFsuite Hotel</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet"/>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" />
  <style>
    :root {
      --primary: #FF8C00;
      --background: #11101d;
      --input-bg:rgb(255, 255, 255);
      --text-light: #ffffff;
      --text-muted: rgba(255, 255, 255, 0.6);
    }

    body {
      background: var(--background);
      font-family: 'Poppins', sans-serif;
      color: var(--text-light);
      min-height: 100vh;
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

    .card-header h3 {
      margin-top: 1rem;
      font-weight: 600;
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
  </style>
</head>
<body>

  <div class="container">
    <div class="row justify-content-center">
      <div class="col-lg-7 col-md-8">
        <div class="card shadow">
          <div class="card-header text-center">
            <img src="../assets/MFsuites_logo.png" alt="Hotel Logo" class="img-fluid" style="max-height: 100px;" />
            <h3 class="mt-3">Create Account</h3>
          </div>
          <div class="card-body">
            <form action="process_register.php" method="POST">
              <div class="row mb-3">
                <div class="col-md-4 mb-2 mb-md-0">
                  <input type="text" class="form-control" name="firstname" placeholder="First Name" required />
                </div>
                <div class="col-md-4 mb-2 mb-md-0">
                  <input type="text" class="form-control" name="middlename" placeholder="Middle Name" />
                </div>
                <div class="col-md-4">
                  <input type="text" class="form-control" name="lastname" placeholder="Last Name" required />
                </div>
              </div>
              <div class="mb-3">
                <input type="tel" class="form-control" name="phone" placeholder="Phone Number" required />
              </div>
              <div class="mb-3">
                <input type="email" class="form-control" name="email" placeholder="Email Address" required />
              </div>
              <div class="mb-3">
                <textarea class="form-control" name="address" placeholder="Address" rows="2" required></textarea>
              </div>
              <div class="mb-3">
                <div class="input-group">
                  <input type="password" class="form-control" name="password" id="password" placeholder="Password" required />
                  <button class="btn" type="button" onclick="togglePassword('password')">
                    <i class="bi bi-eye"></i>
                  </button>
                </div>
              </div>
              <div class="mb-3">
                <div class="input-group">
                  <input type="password" class="form-control" name="confirm_password" id="confirm_password" placeholder="Confirm Password" required />
                  <button class="btn" type="button" onclick="togglePassword('confirm_password')">
                    <i class="bi bi-eye"></i>
                  </button>
                </div>
              </div>
              <div class="d-grid gap-2">
                <button type="submit" class="btn btn-primary">Register</button>
                <a href="login.php" class="btn btn-outline-secondary">Already have an account? Login</a>
              </div>
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
