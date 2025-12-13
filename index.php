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
            --primary-blue: #0066FF;
            --secondary-blue: #E6F0FF;
            --dark-blue: #003D99;
            --light-blue: #F0F7FF;
            --accent-blue: #3385FF;
            --purple: #7C3AED;
            --light-purple: #EDE9FE;
            --cyan: #06B6D4;
            --light-cyan: #CFFAFE;
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

        /* Replace the existing body background styles with this */

/* Replace the existing body and header background styles with this */

body {
    font-family: 'Inter', sans-serif;
    background: linear-gradient(135deg, #1e3a8a 0%, #3b82f6 25%, #60a5fa 50%, #93c5fd 75%, #dbeafe 100%);
    color: var(--text-dark);
    min-height: 100vh;
    display: flex;
    flex-direction: column;
    overflow-x: hidden;
    position: relative;
}

body::before {
    content: '';
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: linear-gradient(135deg, #1e3a8a 0%, #2563eb 50%, #3b82f6 100%);
    pointer-events: none;
    z-index: 0;
}

/* HEADER with complementary gradient */
header {
    background: linear-gradient(135deg, #1e3a8a 0%, #2563eb 50%, #3b82f6 100%);
    backdrop-filter: blur(10px);
    box-shadow: 0 4px 30px rgba(0, 0, 0, 0.2);
    padding: 2.5rem 0;
    border-bottom: 3px solid #60a5fa;
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
    color: #93c5fd;
    filter: drop-shadow(0 2px 4px rgba(0, 0, 0, 0.2));
}

header p {
    font-size: 1.15rem;
    color: #dbeafe;
    font-weight: 500;
    margin: 0;
    text-shadow: 0 1px 3px rgba(0, 0, 0, 0.2);
}

        main, header, footer {
            position: relative;
            z-index: 1;
        }

        h1, h2, h3, h4, h5, h6 {
            font-family: 'Space Grotesk', sans-serif;
            font-weight: 700;
        }

        /* HEADER */
    

        /* MAIN CONTAINER */
        main {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 3rem 1rem;
            animation: fadeIn 1s ease-out;
        }

        /* LOGIN SECTION */
        .login-container {
            max-width: 550px;
            width: 100%;
        }

        .login-card {
            background: rgba(255, 255, 255, 0.98);
            backdrop-filter: blur(20px);
            border-radius: 32px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.2);
            padding: 4rem 3.5rem;
            border: 2px solid rgba(255, 255, 255, 0.5);
            animation: fadeInUp 0.8s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            overflow: hidden;
        }

        .login-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 6px;
            background: linear-gradient(90deg, var(--purple) 0%, var(--primary-blue) 50%, var(--cyan) 100%);
        }

        .login-card h2 {
            background: linear-gradient(135deg, var(--purple) 0%, var(--primary-blue) 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            font-weight: 900;
            font-size: 2.5rem;
            text-align: center;
            margin-bottom: 3rem;
            letter-spacing: -1px;
        }

        .login-card h2 i {
            background: linear-gradient(135deg, var(--purple) 0%, var(--primary-blue) 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
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
        }

        .form-label i {
            margin-right: 0.5rem;
            color: var(--purple);
        }

        .input-wrapper {
            position: relative;
        }

        .input-icon {
            position: absolute;
            left: 1.3rem;
            top: 50%;
            transform: translateY(-50%);
            background: linear-gradient(135deg, var(--purple) 0%, var(--primary-blue) 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            font-size: 1.2rem;
            z-index: 2;
        }

        .form-control {
            border: 2px solid var(--border-color);
            border-radius: 16px;
            padding: 1.1rem 1.3rem 1.1rem 3.5rem;
            font-size: 1rem;
            transition: all 0.3s ease;
            background: var(--white);
            font-weight: 500;
        }

        .form-control:focus {
            border-color: var(--purple);
            box-shadow: 0 0 0 5px rgba(124, 58, 237, 0.1);
            outline: none;
        }

        .form-control::placeholder {
            color: #9CA3AF;
        }

        /* BUTTON */
        .btn-login {
            background: linear-gradient(135deg, var(--purple) 0%, var(--primary-blue) 50%, var(--cyan) 100%);
            border: none;
            border-radius: 16px;
            padding: 1.2rem 3rem;
            font-weight: 800;
            font-size: 1.1rem;
            color: var(--white);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            box-shadow: 0 8px 24px rgba(124, 58, 237, 0.4);
            letter-spacing: 0.5px;
            width: 100%;
            text-transform: uppercase;
            margin-top: 1rem;
        }

        .btn-login:hover {
            transform: translateY(-4px);
            box-shadow: 0 12px 32px rgba(124, 58, 237, 0.5);
        }

        .btn-login:active {
            transform: translateY(-2px);
        }

        /* ALERT */
        .alert {
            border: none;
            border-radius: 16px;
            padding: 1.2rem 1.5rem;
            margin-bottom: 2rem;
            font-weight: 600;
            animation: slideInAlert 0.5s ease-out;
        }

        .alert-danger {
            background: linear-gradient(135deg, #FEE2E2 0%, #FECACA 100%);
            color: #991B1B;
            border-left: 5px solid var(--error);
        }

        .alert-success {
            background: linear-gradient(135deg, #D1FAE5 0%, #A7F3D0 100%);
            color: #065F46;
            border-left: 5px solid var(--success);
        }

        /* FOOTER */
        footer {
            background: rgba(255, 255, 255, 0.98);
            backdrop-filter: blur(10px);
            color: var(--text-dark);
            padding: 2rem 0;
            box-shadow: 0 -4px 30px rgba(0, 0, 0, 0.1);
            animation: slideUp 0.8s cubic-bezier(0.4, 0, 0.2, 1);
            margin-top: auto;
            border-top: 3px solid var(--purple);
        }

        footer p {
            margin: 0;
            font-weight: 600;
            font-size: 0.95rem;
            color: var(--text-light);
        }

        footer i {
            margin-right: 0.5rem;
            background: linear-gradient(135deg, var(--purple) 0%, var(--primary-blue) 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
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
            color: var(--purple);
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
                border-radius: 24px;
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

        /* DECORATIVE ELEMENTS */
        .login-card::after {
            content: '';
            position: absolute;
            width: 400px;
            height: 400px;
            background: radial-gradient(circle, rgba(124, 58, 237, 0.08) 0%, transparent 70%);
            border-radius: 50%;
            top: -200px;
            right: -200px;
            pointer-events: none;
        }

        /* Floating animation for decorative element */
        @keyframes float {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-20px); }
        }
    </style>

    <script src="assets/js/main.js" defer></script>
    <script src="assets/js/validation.js" defer></script>
</head>
<body>

<!-- HEADER -->
<header class="text-center">
    <div class="container" >
        <h1><i class="bi bi-box-seam"></i> Campus Lost & Found System</h1>
        <p>Welcome! Please login with your institutional account</p>
    </div>
</header>

<main>
    <div class="login-container">
        <!-- LOGIN CARD -->
        <div class="login-card">
            <h2><i class="bi bi-shield-lock"></i>Sign In</h2>

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