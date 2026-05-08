<?php
require_once __DIR__ . '/config/auth.php';
require_once __DIR__ . '/config/database.php';

// Redirect if already logged in
if (isLoggedIn()) {
    header('Location: index.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($password)) {
        $error = 'Please enter both username and password.';
    } elseif (login($username, $password)) {
        header('Location: index.php');
        exit;
    } else {
        $error = 'Invalid username or password.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login &mdash; <?= BARANGAY_NAME ?></title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        body {
            min-height: 100vh;
            background: linear-gradient(135deg, #0f2d6b 0%, #1a56db 50%, #e02424 100%);
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .login-wrapper {
            width: 100%;
            max-width: 420px;
            padding: 1rem;
        }

        .login-card {
            border: none;
            border-radius: 1rem;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.35);
            overflow: hidden;
        }

        .login-header {
            background: linear-gradient(135deg, #0f2d6b 0%, #1a56db 100%);
            padding: 2rem 2rem 1.5rem;
            text-align: center;
            position: relative;
        }

        /* Tricolor bar at top of login header */
        .login-header::before {
            content: '';
            display: block;
            height: 6px;
            background: linear-gradient(to right, #1a56db 33.3%, #e02424 33.3% 66.6%, #f59e0b 66.6%);
            position: absolute;
            top: 0; left: 0; right: 0;
        }

        .login-seal {
            width: 72px;
            height: 72px;
            background: rgba(255, 255, 255, 0.15);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1rem;
            border: 2px solid rgba(255, 255, 255, 0.3);
        }

        .login-body {
            padding: 2rem;
            background: #fff;
        }

        .form-control:focus {
            border-color: #1a56db;
            box-shadow: 0 0 0 0.2rem rgba(26, 86, 219, 0.15);
        }

        .btn-login {
            background: linear-gradient(135deg, #0f2d6b 0%, #1a56db 100%);
            border: none;
            color: #fff;
            font-weight: 600;
            letter-spacing: 0.5px;
            padding: 0.65rem;
            transition: opacity 0.2s;
        }

        .btn-login:hover {
            opacity: 0.9;
            color: #fff;
        }

        .input-group-text {
            background: #f8f9fa;
            border-right: none;
        }

        .form-control {
            border-left: none;
        }

        .form-control:focus {
            border-left: none;
        }

        .input-group:focus-within .input-group-text {
            border-color: #1a56db;
        }

        .login-footer {
            background: #f8f9fa;
            padding: 0.75rem 2rem;
            text-align: center;
            border-top: 1px solid #e9ecef;
        }

        .toggle-password {
            cursor: pointer;
            border-left: none;
            background: #f8f9fa;
        }

        .toggle-password:hover {
            background: #e9ecef;
        }
    </style>
</head>
<body>
    <div class="login-wrapper">
        <div class="login-card card">
            <!-- Header -->
            <div class="login-header">
                <div class="login-seal">
                    <i class="bi bi-shield-fill-check fs-2 text-warning"></i>
                </div>
                <h5 class="text-white fw-bold mb-0"><?= BARANGAY_NAME ?></h5>
                <small class="text-white-50"><?= MUNICIPALITY ?>, <?= PROVINCE ?></small>
                <div class="mt-2">
                    <span class="badge bg-warning text-dark fw-semibold px-3 py-1">
                        <i class="bi bi-building me-1"></i>SK Barangay Management System
                    </span>
                </div>
            </div>

            <!-- Body -->
            <div class="login-body">
                <h6 class="fw-semibold text-dark mb-1">Welcome back</h6>
                <p class="text-muted small mb-4">Sign in to access the system.</p>

                <?php if ($error): ?>
                    <div class="alert alert-danger alert-dismissible d-flex align-items-center gap-2 py-2" role="alert">
                        <i class="bi bi-exclamation-triangle-fill flex-shrink-0"></i>
                        <div class="small"><?= htmlspecialchars($error) ?></div>
                        <button type="button" class="btn-close btn-close-sm ms-auto" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <form method="POST" action="login.php" autocomplete="off" novalidate>
                    <!-- Username -->
                    <div class="mb-3">
                        <label for="username" class="form-label small fw-semibold text-dark">Username</label>
                        <div class="input-group">
                            <span class="input-group-text">
                                <i class="bi bi-person text-muted"></i>
                            </span>
                            <input
                                type="text"
                                id="username"
                                name="username"
                                class="form-control"
                                placeholder="Enter your username"
                                value="<?= htmlspecialchars($_POST['username'] ?? '') ?>"
                                required
                                autofocus
                            >
                        </div>
                    </div>

                    <!-- Password -->
                    <div class="mb-4">
                        <label for="password" class="form-label small fw-semibold text-dark">Password</label>
                        <div class="input-group">
                            <span class="input-group-text">
                                <i class="bi bi-lock text-muted"></i>
                            </span>
                            <input
                                type="password"
                                id="password"
                                name="password"
                                class="form-control"
                                placeholder="Enter your password"
                                required
                            >
                            <button type="button" class="btn btn-outline-secondary toggle-password" id="togglePassword" tabindex="-1">
                                <i class="bi bi-eye" id="toggleIcon"></i>
                            </button>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-login w-100 rounded-2">
                        <i class="bi bi-box-arrow-in-right me-2"></i>Sign In
                    </button>
                </form>
            </div>

            <!-- Footer -->
            <div class="login-footer">
                <small class="text-muted">
                    <i class="bi bi-lock-fill me-1"></i>
                    Default: <code>admin</code> / <code>admin123</code>
                </small>
            </div>
        </div>

        <p class="text-center text-white-50 small mt-3">
            &copy; <?= date('Y') ?> <?= BARANGAY_NAME ?> &mdash; BMS v1.0
        </p>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Toggle password visibility
        document.getElementById('togglePassword').addEventListener('click', function () {
            const pwd = document.getElementById('password');
            const icon = document.getElementById('toggleIcon');
            if (pwd.type === 'password') {
                pwd.type = 'text';
                icon.classList.replace('bi-eye', 'bi-eye-slash');
            } else {
                pwd.type = 'password';
                icon.classList.replace('bi-eye-slash', 'bi-eye');
            }
        });
    </script>
</body>
</html>
