<?php global $pdo;
/**
 * Login Page
 * Landing page with login form for students and admin
 */

// Include configurations
require_once 'config/database.php';
require_once 'config/constants.php';
require_once 'includes/session.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php';

// If already logged in, redirect to appropriate dashboard
if (isLoggedIn()) {
    if (isAdmin()) {
        header('Location: admin/index.php');
    } else {
        header('Location: student/index.php');
    }
    exit;
}

// Handle login form submission
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = sanitize($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    $result = loginUser($pdo, $email, $password);

    if ($result['success']) {
        header('Location: ' . $result['redirect']);
        exit;
    } else {
        $error = $result['message'];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Lost & Found System</title>

    <!-- BOOTSTRAP -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- BOOTSTRAP ICONS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">

    <!-- GOOGLE FONTS -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&family=Space+Grotesk:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <!-- CUSTOM CSS -->
    <style>
        :root {
            --university-blue: #003366;
            --university-gold: #FFB81C;
            --navy: #002D72;
            --burgundy: #8B1538;
            --forest-green: #1B5E20;
            --slate: #455A64;
            --light-blue: #E3F2FD;
            --light-gold: #FFF8E1;
            --text-dark: #1a1a2e;
            --text-light: #6B7280;
            --white: #FFFFFF;
            --border-color: #E5E7EB;
            --success: #10B981;
            --error: #EF4444;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: #FFFFFF;
            color: var(--text-dark);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            overflow-x: hidden;
        }

        h1, h2, h3, h4, h5, h6 {
            font-family: 'Space Grotesk', sans-serif;
            font-weight: 700;
        }

        /* HEADER */
        header {
            background: linear-gradient(135deg, var(--university-blue) 0%, var(--navy) 100%);
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.2);
            padding: 2.5rem 0;
            border-bottom: 4px solid var(--university-gold);
            animation: slideDown 0.8s cubic-bezier(0.4, 0, 0.2, 1);
        }

        header h1 {
            font-size: 3rem;
            font-weight: 900;
            color: #ffffff;
            margin-bottom: 0.5rem;
            letter-spacing: -1.5px;
            text-shadow: 0 2px 10px rgba(0, 0, 0, 0.2);
        }

        header h1 i {
            color: var(--university-gold);
            filter: drop-shadow(0 2px 4px rgba(0, 0, 0, 0.2));
        }

        header p {
            font-size: 1.15rem;
            color: #E3F2FD;
            font-weight: 500;
            margin: 0;
            text-shadow: 0 1px 3px rgba(0, 0, 0, 0.2);
        }

        /* MAIN CONTAINER */
        main {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 3rem 1rem;
            animation: fadeIn 1s ease-out;
            background: #FFFFFF;
        }

        /* LOGIN SECTION */
        .login-container {
            max-width: 550px;
            width: 100%;
        }

        .login-card {
            background: var(--white);
            border-radius: 0;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            padding: 4rem 3.5rem;
            border: 2px solid var(--border-color);
            border-top: 6px solid var(--university-blue);
            animation: fadeInUp 0.8s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            overflow: hidden;
        }

        .login-card h2 {
            color: var(--university-blue);
            font-weight: 900;
            font-size: 2.5rem;
            text-align: center;
            margin-bottom: 3rem;
            letter-spacing: -1px;
            text-transform: uppercase;
            font-family: 'Space Grotesk', sans-serif;
        }

        .login-card h2 i {
            color: var(--university-gold);
            font-size: 2.2rem;
        }

        /* FORM ELEMENTS */
        .form-group-custom {
            margin-bottom: 2rem;
        }

        .form-label {
            font-weight: 700;
            color: var(--text-dark);
            margin-bottom: 0.8rem;
            font-size: 0.95rem;
            display: flex;
            align-items: center;
            text-transform: uppercase;
            letter-spacing: 0.03em;
        }

        .form-label i {
            margin-right: 0.5rem;
            color: var(--university-blue);
        }

        .input-wrapper {
            position: relative;
        }

        .input-icon {
            position: absolute;
            left: 1.3rem;
            top: 50%;
            transform: translateY(-50%);
            color: var(--university-blue);
            font-size: 1.2rem;
            z-index: 2;
        }

        .form-control {
            border: 2px solid var(--border-color);
            border-radius: 4px;
            padding: 1.1rem 1.3rem 1.1rem 3.5rem;
            font-size: 1rem;
            transition: all 0.3s ease;
            background: var(--white);
            font-weight: 500;
        }

        .form-control:focus {
            border-color: var(--university-blue);
            box-shadow: 0 0 0 4px rgba(0, 51, 102, 0.1);
            outline: none;
        }

        .form-control::placeholder {
            color: #9CA3AF;
        }

        /* BUTTON */
        .btn-login {
            background: var(--university-blue);
            border: none;
            border-radius: 4px;
            padding: 1.2rem 3rem;
            font-weight: 800;
            font-size: 1.1rem;
            color: var(--white);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            box-shadow: 0 4px 12px rgba(0, 51, 102, 0.3);
            letter-spacing: 0.5px;
            width: 100%;
            text-transform: uppercase;
            margin-top: 1rem;
        }

        .btn-login:hover {
            background: var(--navy);
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(0, 51, 102, 0.4);
        }

        .btn-login:active {
            transform: translateY(-1px);
        }

        /* ALERT */
        .alert {
            border: none;
            border-radius: 4px;
            padding: 1.2rem 1.5rem;
            margin-bottom: 2rem;
            font-weight: 600;
            animation: slideInAlert 0.5s ease-out;
        }

        .alert-danger {
            background: #FEE2E2;
            color: #991B1B;
            border-left: 5px solid var(--error);
        }

        .alert-success {
            background: #D1FAE5;
            color: #065F46;
            border-left: 5px solid var(--success);
        }

        /* FOOTER */
        footer {
            background: linear-gradient(135deg, var(--university-blue) 0%, var(--navy) 100%);
            color: var(--white);
            padding: 2rem 0;
            box-shadow: 0 -2px 8px rgba(0, 0, 0, 0.2);
            animation: slideUp 0.8s cubic-bezier(0.4, 0, 0.2, 1);
            margin-top: auto;
            border-top: 4px solid var(--university-gold);
        }

        footer p {
            margin: 0;
            font-weight: 600;
            font-size: 0.95rem;
            color: var(--white);
        }

        footer i {
            margin-right: 0.5rem;
            color: var(--university-gold);
        }

        /* HELPER TEXT */
        .helper-text {
            text-align: center;
            margin-top: 2rem;
            color: var(--text-light);
            font-size: 0.9rem;
            font-weight: 500;
        }

        .helper-text i {
            color: var(--university-blue);
        }

        /* ANIMATIONS */
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(50px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes slideDown {
            from {
                transform: translateY(-100%);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }

        @keyframes slideUp {
            from {
                transform: translateY(100%);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }

        @keyframes slideInAlert {
            from {
                transform: translateX(-100%);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }

        /* RESPONSIVE */
        @media (max-width: 768px) {
            header h1 {
                font-size: 2.2rem;
            }

            header p {
                font-size: 1rem;
            }

            .login-card {
                padding: 3rem 2rem;
            }

            .login-card h2 {
                font-size: 2rem;
            }
        }

        @media (max-width: 576px) {
            main {
                padding: 2rem 1rem;
            }

            .login-card {
                padding: 2.5rem 1.5rem;
                border-radius: 0;
            }

            .form-control {
                padding: 1rem 1rem 1rem 3.2rem;
            }

            .btn-login {
                padding: 1rem 2rem;
            }
        }

        /* LOADING STATE */
        .btn-login.loading {
            position: relative;
            color: transparent;
        }

        .btn-login.loading::after {
            content: '';
            position: absolute;
            width: 24px;
            height: 24px;
            top: 50%;
            left: 50%;
            margin-left: -12px;
            margin-top: -12px;
            border: 3px solid var(--white);
            border-radius: 50%;
            border-top-color: transparent;
            animation: spin 0.6s linear infinite;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }
        .toggle-password {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            color: #6c757d;
        }

        .toggle-password:hover {
            color: #000;
        }

    </style>
    <script>
        function togglePassword() {
            const passwordInput = document.getElementById('password');
            const icon = document.querySelector('.toggle-password');

            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                icon.classList.remove('bi-eye-fill');
                icon.classList.add('bi-eye-slash-fill');
            } else {
                passwordInput.type = 'password';
                icon.classList.remove('bi-eye-slash-fill');
                icon.classList.add('bi-eye-fill');
            }
        }
    </script>

    <script src="assets/js/main.js" defer></script>
    <script src="assets/js/validation.js" defer></script>
</head>
<body>

<!-- HEADER -->
<header class="text-center">
    <div class="container">
        <h1><i class="bi bi-box-seam"></i> Campus Lost & Found System</h1>
        <p>Welcome! Please login with your institutional account</p>
    </div>
</header>

<main>
    <div class="login-container">
        <!-- LOGIN CARD -->
        <div class="login-card">
            <h2><i class="bi bi-shield-lock"></i> Sign In</h2>

            <!-- ERROR ALERT -->
            <?php if (!empty($error)): ?>
                <div class="alert alert-danger">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i>
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="" id="loginForm">
                <div class="form-group-custom">
                    <label for="email" class="form-label">
                        <i class="bi bi-envelope"></i>Email Address
                    </label>
                    <div class="input-wrapper">
                        <i class="bi bi-envelope-fill input-icon"></i>
                        <input
                                type="email"
                                id="email"
                                name="email"
                                class="form-control"
                                placeholder="your.email@institution.edu"
                                required
                        >
                    </div>
                </div>

                <div class="form-group-custom">
                    <label for="password" class="form-label">
                        <i class="bi bi-lock"></i>Password
                    </label>

                    <div class="input-wrapper">
                        <i class="bi bi-lock-fill input-icon"></i>

                        <input
                                type="password"
                                id="password"
                                name="password"
                                class="form-control"
                                placeholder="Enter your password"
                                required
                        >

                        <i
                                class="bi bi-eye-fill toggle-password"
                                onclick="togglePassword()"
                        ></i>
                    </div>
                </div>


                <button type="submit" class="btn-login">
                    <i class="bi bi-box-arrow-in-right me-2"></i>Login to Dashboard
                </button>
            </form>

            <!-- JS Script -->
            <script>
                document.getElementById('loginForm').addEventListener('submit', async function(e) {
                    e.preventDefault();

                    if (!FormValidation.validateLogin(this)) {
                        return;
                    }

                    const submitBtn = this.querySelector('button[type="submit"]');
                    const originalText = submitBtn.innerHTML;
                    LSF.UI.showLoading(submitBtn);

                    const formData = {
                        email: document.getElementById('email').value,
                        password: document.getElementById('password').value
                    };

                    const result = await LSF.API.post('/lsf_system/api/auth/login.php', formData);

                    LSF.UI.hideLoading(submitBtn, originalText);

                    // Clear any existing alerts before showing new one
                    const existingAlerts = document.querySelectorAll('.alert');
                    existingAlerts.forEach(alert => alert.remove());

                    if (result.success) {
                        LSF.UI.showAlert('Login successful! Redirecting...', 'success');
                        setTimeout(() => {
                            window.location.href = result.redirect;
                        }, 1000);
                    } else {
                        LSF.UI.showAlert(result.message, 'error');
                    }
                });
            </script>

            <p class="helper-text">
                <i class="bi bi-info-circle me-2"></i>
                Use your institutional email and password to access the system
            </p>
        </div>
    </div>
</main>

<footer class="text-center">
    <div class="container">
        <p><i class="bi bi-shield-check"></i> &copy; 2024 Campus Lost & Found System. All rights reserved.</p>
    </div>
</footer>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>