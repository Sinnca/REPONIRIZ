<?php
/**
 * Authentication Functions
 * User registration, login, and password management
 */

/**
 * Register new user
 * @return array ['success' => bool, 'message' => string]
 */
function registerUser($pdo, $name, $email, $password, $role = ROLE_STUDENT) {
    // Validate inputs
    if (empty($name) || empty($email) || empty($password)) {
        return ['success' => false, 'message' => 'All fields are required.'];
    }

    if (!isValidEmail($email)) {
        return ['success' => false, 'message' => 'Invalid email format.'];
    }

    if (strlen($password) < 6) {
        return ['success' => false, 'message' => 'Password must be at least 6 characters.'];
    }

    // Check if email already exists
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$email]);

    if ($stmt->fetch()) {
        return ['success' => false, 'message' => 'Email already registered.'];
    }

    // Hash password
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

    // Insert user
    try {
        $stmt = $pdo->prepare("
            INSERT INTO users (name, email, password, role) 
            VALUES (?, ?, ?, ?)
        ");
        $stmt->execute([$name, $email, $hashedPassword, $role]);

        return ['success' => true, 'message' => 'Registration successful!'];
    } catch (PDOException $e) {
        return ['success' => false, 'message' => 'Registration failed. Please try again.'];
    }
}

/**
 * Login user
 * @return array ['success' => bool, 'message' => string, 'redirect' => string]
 */
function loginUser($pdo, $email, $password) {
    // Validate inputs
    if (empty($email) || empty($password)) {
        return ['success' => false, 'message' => 'Email and password are required.'];
    }

    // Get user from database
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    // Verify user exists and password is correct
    if (!$user || !password_verify($password, $user['password'])) {
        return ['success' => false, 'message' => 'Invalid email or password.'];
    }

    // Set session variables
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['user_name'] = $user['name'];
    $_SESSION['user_email'] = $user['email'];
    $_SESSION['role'] = $user['role'];
    $_SESSION['login_time'] = time();

    // Determine redirect based on role
    $redirect = ($user['role'] === ROLE_ADMIN)
        ? BASE_URL . 'admin/index.php'
        : BASE_URL . 'student/index.php';

    return [
        'success' => true,
        'message' => 'Login successful!',
        'redirect' => $redirect
    ];
}

/**
 * Get user by ID
 */
function getUserById($pdo, $userId) {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    return $stmt->fetch();
}

/**
 * Update user profile
 */
function updateUserProfile($pdo, $userId, $name, $email) {
    // Validate inputs
    if (empty($name) || empty($email)) {
        return ['success' => false, 'message' => 'Name and email are required.'];
    }

    if (!isValidEmail($email)) {
        return ['success' => false, 'message' => 'Invalid email format.'];
    }

    // Check if email is taken by another user
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
    $stmt->execute([$email, $userId]);

    if ($stmt->fetch()) {
        return ['success' => false, 'message' => 'Email already in use.'];
    }

    // Update user
    try {
        $stmt = $pdo->prepare("UPDATE users SET name = ?, email = ? WHERE id = ?");
        $stmt->execute([$name, $email, $userId]);

        // Update session
        $_SESSION['user_name'] = $name;
        $_SESSION['user_email'] = $email;

        return ['success' => true, 'message' => 'Profile updated successfully!'];
    } catch (PDOException $e) {
        return ['success' => false, 'message' => 'Update failed. Please try again.'];
    }
}

/**
 * Change user password
 */
function changePassword($pdo, $userId, $currentPassword, $newPassword) {
    // Validate inputs
    if (empty($currentPassword) || empty($newPassword)) {
        return ['success' => false, 'message' => 'All fields are required.'];
    }

    if (strlen($newPassword) < 6) {
        return ['success' => false, 'message' => 'New password must be at least 6 characters.'];
    }

    // Get current user
    $user = getUserById($pdo, $userId);

    if (!$user) {
        return ['success' => false, 'message' => 'User not found.'];
    }

    // Verify current password
    if (!password_verify($currentPassword, $user['password'])) {
        return ['success' => false, 'message' => 'Current password is incorrect.'];
    }

    // Hash new password
    $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);

    // Update password
    try {
        $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
        $stmt->execute([$hashedPassword, $userId]);

        return ['success' => true, 'message' => 'Password changed successfully!'];
    } catch (PDOException $e) {
        return ['success' => false, 'message' => 'Password change failed. Please try again.'];
    }
}
?>