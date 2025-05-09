<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - MFsuite Hotel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
</head>
<body class="bg-light">
    <div class="container">
        <div class="row justify-content-center align-items-center min-vh-100">
            <div class="col-md-4">
                <div class="card shadow">
                    <div class="card-body">
                        <div class="text-center mb-4">
                            <img src="../assets/MFsuites_logo.png" alt="Hotel Logo" class="img-fluid" style="max-width: 150px;">
                            <h4 class="mt-3">Welcome Back</h4>
                        </div>
                        <form action="process_login.php" method="POST">
                            <div class="mb-3">
                                <input type="email" class="form-control" name="email" placeholder="Email address" required>
                            </div>
                            <div class="mb-3">
                                <div class="input-group">
                                    <input type="password" class="form-control" name="password" id="password" placeholder="Password" required>
                                    <button class="btn btn-outline-secondary" type="button" onclick="togglePassword('password')">
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
    </script>
</body>
</html>
