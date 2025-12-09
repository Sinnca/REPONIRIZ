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
            background: linear-gradient(135deg, #F0F7FF 0%, #FFFFFF 50%, #E6F0FF 100%);
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
            background: var(--white);
            box-shadow: 0 2px 24px rgba(0, 102, 255, 0.08);
            padding: 2rem 0;
            border-bottom: 1px solid var(--border-color);
            animation: slideDown 0.8s cubic-bezier(0.4, 0, 0.2, 1);
        }

        header h1 {
            font-size: 2.8rem;
            font-weight: 900;
            background: linear-gradient(135deg, var(--primary-blue) 0%, var(--dark-blue) 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 0.5rem;
            letter-spacing: -1px;
        }

        header p {
            font-size: 1.1rem;
            color: var(--text-light);
            font-weight: 500;
            margin: 0;
        }

        /* MAIN CONTAINER */
        main {
            flex: 1;
            animation: fadeIn 1s ease-out;
        }

        /* LOGIN SECTION */
        .login-container {
            max-width: 1400px;
            margin: 3rem auto;
            padding: 0 1rem;
        }

        .login-card {
            background: var(--white);
            border-radius: 24px;
            box-shadow: 0 8px 40px rgba(0, 102, 255, 0.12);
            padding: 3.5rem;
            margin-bottom: 3rem;
            border: 1px solid var(--border-color);
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
            height: 5px;
            background: linear-gradient(90deg, var(--primary-blue) 0%, var(--accent-blue) 100%);
        }

        .login-card h2 {
            color: var(--primary-blue);
            font-weight: 800;
            font-size: 2.2rem;
            text-align: center;
            margin-bottom: 2.5rem;
            letter-spacing: -0.5px;
        }

        .login-card h2 i {
            margin-right: 0.5rem;
            font-size: 2rem;
        }

        /* FORM ELEMENTS */
        .form-group-custom {
            margin-bottom: 1.8rem;
        }

        .form-label {
            font-weight: 600;
            color: var(--text-dark);
            margin-bottom: 0.6rem;
            font-size: 0.95rem;
            display: block;
        }

        .input-wrapper {
            position: relative;
        }

        .input-icon {
            position: absolute;
            left: 1.2rem;
            top: 50%;
            transform: translateY(-50%);
            color: var(--primary-blue);
            font-size: 1.1rem;
            z-index: 2;
        }

        .form-control {
            border: 2px solid var(--border-color);
            border-radius: 12px;
            padding: 0.95rem 1.2rem 0.95rem 3.2rem;
            font-size: 0.95rem;
            transition: all 0.3s ease;
            background: var(--white);
            font-weight: 500;
        }

        .form-control:focus {
            border-color: var(--primary-blue);
            box-shadow: 0 0 0 4px rgba(0, 102, 255, 0.1);
            outline: none;
        }

        .form-control::placeholder {
            color: #9CA3AF;
        }

        /* BUTTON */
        .btn-login {
            background: linear-gradient(135deg, var(--primary-blue) 0%, var(--accent-blue) 100%);
            border: none;
            border-radius: 12px;
            padding: 1rem 3rem;
            font-weight: 700;
            font-size: 1.05rem;
            color: var(--white);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            box-shadow: 0 4px 16px rgba(0, 102, 255, 0.3);
            letter-spacing: 0.3px;
            min-width: 200px;
        }

        .btn-login:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 24px rgba(0, 102, 255, 0.4);
            background: linear-gradient(135deg, var(--dark-blue) 0%, var(--primary-blue) 100%);
        }

        .btn-login:active {
            transform: translateY(-1px);
        }

        /* ALERT */
        .alert {
            border: none;
            border-radius: 12px;
            padding: 1rem 1.5rem;
            margin-bottom: 1.5rem;
            font-weight: 500;
            animation: slideInAlert 0.5s ease-out;
        }

        .alert-danger {
            background: linear-gradient(135deg, #FEE2E2 0%, #FECACA 100%);
            color: #991B1B;
            border-left: 4px solid var(--error);
        }

        .alert-success {
            background: linear-gradient(135deg, #D1FAE5 0%, #A7F3D0 100%);
            color: #065F46;
            border-left: 4px solid var(--success);
        }

        /* INFO CARDS SECTION */
        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 2rem;
            margin-bottom: 3rem;
            animation: fadeInUp 1s cubic-bezier(0.4, 0, 0.2, 1) 0.2s backwards;
        }

        .info-card {
            background: var(--white);
            border-radius: 20px;
            padding: 2.5rem;
            box-shadow: 0 4px 24px rgba(0, 102, 255, 0.08);
            border: 1px solid var(--border-color);
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            overflow: hidden;
        }

        .info-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, var(--primary-blue) 0%, var(--accent-blue) 100%);
            transform: scaleX(0);
            transition: transform 0.4s ease;
        }

        .info-card:hover::before {
            transform: scaleX(1);
        }

        .info-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 12px 40px rgba(0, 102, 255, 0.16);
            border-color: var(--primary-blue);
        }

        .info-card h3 {
            color: var(--primary-blue);
            font-weight: 800;
            font-size: 1.4rem;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            letter-spacing: -0.5px;
        }

        .info-card h3 i {
            font-size: 2rem;
            margin-right: 0.8rem;
            background: linear-gradient(135deg, var(--primary-blue) 0%, var(--accent-blue) 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .info-card ul, .info-card ol {
            padding-left: 1.5rem;
            margin: 0;
        }

        .info-card li {
            margin-bottom: 0.8rem;
            color: var(--text-light);
            font-weight: 500;
            font-size: 0.95rem;
            line-height: 1.6;
        }

        .info-card li::marker {
            color: var(--primary-blue);
            font-weight: 700;
        }

        /* FOOTER */
        footer {
            background: linear-gradient(135deg, var(--dark-blue) 0%, var(--primary-blue) 100%);
            color: var(--white);
            padding: 2rem 0;
            box-shadow: 0 -2px 24px rgba(0, 102, 255, 0.12);
            animation: slideUp 0.8s cubic-bezier(0.4, 0, 0.2, 1);
            margin-top: auto;
        }

        footer p {
            margin: 0;
            font-weight: 500;
            font-size: 0.95rem;
        }

        footer i {
            margin-right: 0.5rem;
        }

        /* HELPER TEXT */
        .helper-text {
            text-align: center;
            margin-top: 1.5rem;
            color: var(--text-light);
            font-size: 0.9rem;
            font-weight: 500;
        }

        /* ANIMATIONS */
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(40px);
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
                font-size: 2rem;
            }

            header p {
                font-size: 0.95rem;
            }

            .login-card {
                padding: 2rem;
            }

            .login-card h2 {
                font-size: 1.8rem;
            }

            .info-grid {
                grid-template-columns: 1fr;
                gap: 1.5rem;
            }

            .info-card {
                padding: 2rem;
            }

            .btn-login {
                width: 100%;
                min-width: auto;
            }
        }

        @media (max-width: 576px) {
            .login-container {
                margin: 1.5rem auto;
            }

            .login-card {
                padding: 1.5rem;
                margin-bottom: 2rem;
            }

            .form-control {
                padding: 0.85rem 1rem 0.85rem 3rem;
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
            width: 20px;
            height: 20px;
            top: 50%;
            left: 50%;
            margin-left: -10px;
            margin-top: -10px;
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
            width: 300px;
            height: 300px;
            background: radial-gradient(circle, rgba(0, 102, 255, 0.05) 0%, transparent 70%);
            border-radius: 50%;
            top: -150px;
            right: -150px;
            pointer-events: none;
        }
    </style>

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
            <h2><i class="bi bi-shield-lock"></i>Sign In</h2>

            <!-- ERROR ALERT -->
            <?php if (!empty($error)): ?>
                <div class="alert alert-danger">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i>
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="" id="loginForm">
                <div class="row justify-content-center">
                    <div class="col-lg-5 col-md-6">
                        <div class="form-group-custom">
                            <label for="email" class="form-label">
                                <i class="bi bi-envelope me-1"></i>Email Address
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
                    </div>
                    <div class="col-lg-5 col-md-6">
                        <div class="form-group-custom">
                            <label for="password" class="form-label">
                                <i class="bi bi-lock me-1"></i>Password
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
                    </div>
                </div>

                <div class="text-center">
                    <button type="submit" class="btn-login">
                        <i class="bi bi-box-arrow-in-right me-2"></i>Login to Dashboard
                    </button>
                </div>
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
                <i class="bi bi-info-circle me-1"></i>
                Use your institutional email and password to access the system
            </p>
        </div>

        <!-- INFO CARDS GRID -->
        <div class="info-grid">
            <!-- Students Card -->
            <div class="info-card">
                <h3><i class="bi bi-person-circle"></i>For Students</h3>
                <ul>
                    <li>Report lost items quickly</li>
                    <li>Browse found items database</li>
                    <li>Submit items you've found</li>
                    <li>Request claims for your items</li>
                    <li>Track all your submissions</li>
                </ul>
            </div>

            <!-- Admin Card -->
            <div class="info-card">
                <h3><i class="bi bi-shield-check"></i>For Administrators</h3>
                <ul>
                    <li>Verify submitted items</li>
                    <li>Approve claim requests</li>
                    <li>Schedule item pickups</li>
                    <li>Manage system data</li>
                    <li>View comprehensive statistics</li>
                </ul>
            </div>

            <!-- Lost Something -->
            <div class="info-card">
                <h3><i class="bi bi-search"></i>Lost Something?</h3>
                <ol>
                    <li>Login to your account</li>
                    <li>Report the lost item</li>
                    <li>Wait for admin verification</li>
                    <li>Check for potential matches</li>
                    <li>Submit a claim request</li>
                    <li>Schedule and pick up your item</li>
                </ol>
            </div>

            <!-- Found Something -->
            <div class="info-card">
                <h3><i class="bi bi-hand-thumbs-up"></i>Found Something?</h3>
                <ol>
                    <li>Login to your account</li>
                    <li>Submit the found item</li>
                    <li>Admin verifies the item</li>
                    <li>Owner gets notified</li>
                    <li>Help reunite items with owners!</li>
                </ol>
            </div>
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