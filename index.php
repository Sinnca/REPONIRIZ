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

    <!-- LOGIN CARD -->
    <div class="row justify-content-center mb-5">
        <div class="col-md-5">

            <div class="card shadow-sm">
                <div class="card-body p-4">

                    <h2 class="text-center mb-4">Login</h2>

                    <!-- ERROR ALERT -->
                    <?php if (!empty($error)): ?>
                        <div class="alert alert-danger">
                            <?php echo htmlspecialchars($error); ?>
                        </div>
                    <?php endif; ?>

                    <form method="POST" action="" id="loginForm">

                        <div class="mb-3">
                            <label for="email" class="form-label">Email Address</label>
                            <input
                                    type="email"
                                    id="email"
                                    name="email"
                                    class="form-control"
                                    placeholder="your.email@institution.edu"
                                    required
                            >
                        </div>

                        <div class="mb-3">
                            <label for="password" class="form-label">Password</label>
                            <input
                                    type="password"
                                    id="password"
                                    name="password"
                                    class="form-control"
                                    placeholder="Enter your password"
                                    required
                            >
                        </div>

                        <button type="submit" class="btn btn-primary w-100">
                            Login
                        </button>

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
            </div>

        </div>
    </div>

    <!-- ABOUT SECTIONS -->
    <div class="row g-4">

        <!-- Students -->
        <div class="col-md-6">
            <div class="card shadow-sm p-3 h-100">
                <h3>For Students</h3>
                <ul>
                    <li>Report lost items</li>
                    <li>Browse found items</li>
                    <li>Submit found items</li>
                    <li>Request claims</li>
                    <li>Track your submissions</li>
                </ul>
            </div>
        </div>

        <!-- Admin -->
        <div class="col-md-6">
            <div class="card shadow-sm p-3 h-100">
                <h3>For Admin</h3>
                <ul>
                    <li>Verify submitted items</li>
                    <li>Approve claim requests</li>
                    <li>Schedule pickup</li>
                    <li>Manage system data</li>
                    <li>View statistics</li>
                </ul>
            </div>
        </div>

        <!-- HOW IT WORKS -->
        <div class="col-md-6">
            <div class="card shadow-sm p-3 h-100">
                <h3>Lost Something?</h3>
                <ol>
                    <li>Login</li>
                    <li>Report lost item</li>
                    <li>Wait for verification</li>
                    <li>Check for matches</li>
                    <li>Request claim</li>
                    <li>Pick up item</li>
                </ol>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card shadow-sm p-3 h-100">
                <h3>Found Something?</h3>
                <ol>
                    <li>Login</li>
                    <li>Submit found item</li>
                    <li>Admin verifies</li>
                    <li>Owner notified</li>
                    <li>Help return items!</li>
                </ol>
            </div>
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
