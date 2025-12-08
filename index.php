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

    <!-- FONT AWESOME FOR ICONS -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">

    <!-- GOOGLE FONTS FOR MODERN FONT STYLES -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <!-- CUSTOM CSS FOR MODERN BLUE AND WHITE THEME WITH GRADIENTS AND ANIMATIONS -->
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #e3f2fd 0%, #ffffff 100%);
            color: #333;
            overflow-x: hidden;
        }

        /* HEADER STYLING */
        header {
            background: linear-gradient(135deg, #1976d2 0%, #42a5f5 100%);
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
            animation: slideDown 1s ease-out;
        }

        header h1 {
            font-weight: 700;
            font-size: 2.5rem;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
            color: #ffffff;
        }

        header p {
            font-weight: 300;
            font-size: 1.1rem;
            color: #ffffff;
        }

        /* MAIN CONTAINER */
        main {
            animation: fadeIn 1.5s ease-in;
        }

        /* LOGIN CARD - FULL WIDTH LAYOUT */
        .login-section {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            padding: 3rem;
            margin-bottom: 3rem;
            animation: fadeInUp 1s ease-out;
        }

        .login-section h2 {
            color: #1976d2;
            font-weight: 600;
            text-align: center;
            margin-bottom: 2rem;
        }

        /* FORM ELEMENTS */
        .form-label {
            font-weight: 500;
            color: #555;
        }

        .input-group-text {
            background: #f8f9fa;
            border: 2px solid #e0e0e0;
            border-right: none;
            color: #1976d2;
        }

        .form-control {
            border: 2px solid #e0e0e0;
            border-left: none;
            border-radius: 0 10px 10px 0;
            padding: 0.75rem;
            transition: border-color 0.3s ease, box-shadow 0.3s ease;
            background: #ffffff;
        }

        .form-control:focus {
            border-color: #1976d2;
            box-shadow: 0 0 0 0.2rem rgba(25, 118, 210, 0.25);
        }

        .input-group:focus-within .input-group-text {
            border-color: #1976d2;
        }

        .btn-primary {
            background: linear-gradient(135deg, #1976d2 0%, #42a5f5 100%);
            border: none;
            border-radius: 10px;
            padding: 0.75rem;
            font-weight: 600;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            color: #ffffff;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(25, 118, 210, 0.4);
        }

        /* ALERT */
        .alert {
            border-radius: 10px;
            animation: slideIn 0.5s ease-out;
            position: relative;
            z-index: 10;
        }

        /* ABOUT SECTIONS - HORIZONTAL LAYOUT */
        .about-section {
            display: flex;
            flex-wrap: wrap;
            gap: 2rem;
            justify-content: center;
        }

        .about-card {
            flex: 1 1 300px;
            max-width: 400px;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            background: #ffffff;
            padding: 2rem;
            animation: fadeInUp 1s ease-out;
        }

        .about-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 40px rgba(0, 0, 0, 0.15);
        }

        .about-card h3 {
            color: #1976d2;
            font-weight: 600;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
        }

        .about-card h3 i {
            margin-right: 0.5rem;
            font-size: 1.5rem;
        }

        .about-card ul, .about-card ol {
            padding-left: 1.5rem;
        }

        .about-card li {
            margin-bottom: 0.5rem;
            color: #666;
        }

        /* FOOTER */
        footer {
            background: linear-gradient(135deg, #0d47a1 0%, #1976d2 100%);
            box-shadow: 0 -4px 20px rgba(0, 0, 0, 0.1);
            animation: slideUp 1s ease-out;
            color: #ffffff;
        }

        /* ANIMATIONS */
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(30px); }
            to { opacity: 1; transform: translateY(0); }
        }

        @keyframes slideDown {
            from { transform: translateY(-100%); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }

        @keyframes slideUp {
            from { transform: translateY(100%); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }

        @keyframes slideIn {
            from { transform: translateX(-100%); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }

        /* RESPONSIVE ADJUSTMENTS */
        @media (max-width: 768px) {
            .login-section {
                padding: 2rem;
            }
            .about-section {
                flex-direction: column;
                align-items: center;
            }
            header h1 {
                font-size: 2rem;
            }
        }
    </style>

    <script src="assets/js/main.js" defer></script>
    <script src="assets/js/validation.js" defer></script>
</head>
<body class="bg-light">

<!-- HEADER -->
<header class="py-4 bg-primary text-white text-center">
    <h1 class="mb-0">Campus Lost & Found System</h1>
    <p class="mb-0">Welcome! Please login with your institutional account</p>
</header>

<main class="container my-5">

    <!-- LOGIN SECTION - FULL WIDTH -->
    <div class="login-section">
        <h2>Login</h2>

        <!-- ERROR ALERT -->
        <?php if (!empty($error)): ?>
            <div class="alert alert-danger">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="" id="loginForm" class="row g-3 justify-content-center">
            <div class="col-md-6">
                <label for="email" class="form-label">Email Address</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="fas fa-envelope"></i></span>
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
            <div class="col-md-6">
                <label for="password" class="form-label">Password</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="fas fa-lock"></i></span>
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
            <div class="col-12 text-center">
                <button type="submit" class="btn btn-primary w-50">
                    Login
                </button>
            </div>
        </form>

        <!-- JS Script Inside -->
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

        <p class="text-center mt-3 text-muted">
            Use your institutional email and password to access the system.
        </p>
    </div>

    <!-- ABOUT SECTIONS - HORIZONTAL FLEX LAYOUT -->
    <div class="about-section">
        <!-- Students -->
        <div class="about-card">
            <h3><i class="fas fa-user-graduate"></i> For Students</h3>
            <ul>
                <li>Report lost items</li>
                <li>Browse found items</li>
                <li>Submit found items</li>
                <li>Request claims</li>
                <li>Track your submissions</li>
            </ul>
        </div>

        <!-- Admin -->
        <div class="about-card">
            <h3><i class="fas fa-user-shield"></i> For Admin</h3>
            <ul>
                <li>Verify submitted items</li>
                <li>Approve claim requests</li>
                <li>Schedule pickup</li>
                <li>Manage system data</li>
                <li>View statistics</li>
            </ul>
        </div>

        <!-- HOW IT WORKS -->
        <div class="about-card">
            <h3><i class="fas fa-question-circle"></i> Lost Something?</h3>
            <ol>
                <li>Login</li>
                <li>Report lost item</li>
                <li>Wait for verification</li>
                <li>Check for matches</li>
                <li>Request claim</li>
                <li>Pick up item</li>
            </ol>
        </div>

        <!-- Found Something -->
        <div class="about-card">
            <h3><i class="fas fa-search"></i> Found Something?</h3>
            <ol>
                <li>Login</li>
                <li>Submit found item</li>
                <li>Admin verifies</li>
                <li>Owner notified</li>
                <li>Help return items!</li>
            </ol>
        </div>
    </div>

</main>

<footer class="text-center py-3 mt-5 bg-dark text-white">
    <p class="mb-0">&copy; 2024 Campus Lost & Found System. All rights reserved.</p>
</footer>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>
