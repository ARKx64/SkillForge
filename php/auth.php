<?php
// ============================================================
//  SkillForge - Authentication Handler
//  File: php/auth.php
//  Handles: Register, Login, Logout (AJAX/form POST)
// ============================================================

require_once '../includes/config.php';
startSession();

header('Content-Type: application/json');

$action = $_POST['action'] ?? '';

switch ($action) {

    // ── REGISTER ──────────────────────────────────────────
    case 'register':
        $name     = sanitize($_POST['name']     ?? '');
        $email    = sanitize($_POST['email']    ?? '');
        $password = $_POST['password']           ?? '';
        $role     = sanitize($_POST['role']     ?? 'freelancer');

        // Basic validation
        if (!$name || !$email || !$password) {
            echo json_encode(['success' => false, 'message' => 'All fields are required.']);
            exit;
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            echo json_encode(['success' => false, 'message' => 'Invalid email address.']);
            exit;
        }
        if (strlen($password) < 6) {
            echo json_encode(['success' => false, 'message' => 'Password must be at least 6 characters.']);
            exit;
        }
        if (!in_array($role, ['freelancer', 'client', 'both'])) {
            $role = 'freelancer';
        }

        // Check if email exists
        $stmt = $pdo->prepare("SELECT user_id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            echo json_encode(['success' => false, 'message' => 'Email already registered. Please login.']);
            exit;
        }

        // Insert user
        $hash = password_hash($password, PASSWORD_BCRYPT);
        $stmt = $pdo->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, ?)");
        $stmt->execute([$name, $email, $hash, $role]);
        $userId = $pdo->lastInsertId();

        // Welcome notification
        addNotification($pdo, $userId, "Welcome to SkillForge, $name! Start by adding your skills.", 'success');

        echo json_encode(['success' => true, 'message' => 'Account created successfully! Please login.']);
        break;


    // ── LOGIN ─────────────────────────────────────────────
    case 'login':
        $email    = sanitize($_POST['email']    ?? '');
        $password = $_POST['password']           ?? '';

        if (!$email || !$password) {
            echo json_encode(['success' => false, 'message' => 'Email and password are required.']);
            exit;
        }

        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? AND is_active = 1");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if (!$user || !password_verify($password, $user['password'])) {
            echo json_encode(['success' => false, 'message' => 'Invalid email or password.']);
            exit;
        }

        // Set session
        $_SESSION['user_id'] = $user['user_id'];
        $_SESSION['user_name'] = $user['name'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['user_role'] = $user['role'];
        $_SESSION['user_avatar'] = $user['avatar'];
        session_regenerate_id(true);

        echo json_encode([
            'success'  => true,
            'message'  => 'Login successful!',
            'redirect' => app_url('dashboard.php'),
            'role'     => $user['role']
        ]);
        break;


    // ── LOGOUT ────────────────────────────────────────────
    case 'logout':
        session_destroy();
        echo json_encode(['success' => true, 'redirect' => app_url('index.php')]);
        break;


    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action.']);
}
