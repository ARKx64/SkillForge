<?php
// ============================================================
//  SkillForge - User & Notifications Handler
//  File: php/user.php
// ============================================================

require_once '../includes/config.php';
startSession();

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized.']);
    exit;
}

$userId = $_SESSION['user_id'];
$action = $_POST['action'] ?? $_GET['action'] ?? '';

switch ($action) {

    // ── GET PROFILE ───────────────────────────────────────
    case 'get_profile':
        $uid = (int)($_GET['user_id'] ?? $userId);
        $stmt = $pdo->prepare("SELECT user_id, name, email, role, avatar, bio, created_at FROM users WHERE user_id = ?");
        $stmt->execute([$uid]);
        $user = $stmt->fetch();
        if (!$user) {
            echo json_encode(['success' => false, 'message' => 'User not found.']);
            exit;
        }
        $user['avatar_url'] = avatar_url($user['avatar']);
        // Stats
        $stats = [];
        $stmt2 = $pdo->prepare("SELECT COUNT(*) FROM user_skills WHERE user_id = ? AND verification_status = 'Verified'");
        $stmt2->execute([$uid]);
        $stats['verified_skills'] = (int)$stmt2->fetchColumn();

        $stmt2 = $pdo->prepare("SELECT COUNT(*) FROM applications WHERE user_id = ? AND status = 'Accepted'");
        $stmt2->execute([$uid]);
        $stats['projects_won'] = (int)$stmt2->fetchColumn();

        $stmt2 = $pdo->prepare("SELECT COUNT(*) FROM projects WHERE client_id = ?");
        $stmt2->execute([$uid]);
        $stats['projects_posted'] = (int)$stmt2->fetchColumn();

        echo json_encode(['success' => true, 'user' => $user, 'stats' => $stats]);
        break;


    // ── UPDATE PROFILE ────────────────────────────────────
    case 'update_profile':
        $name = sanitize($_POST['name'] ?? '');
        $bio  = sanitize($_POST['bio']  ?? '');
        $role = sanitize($_POST['role'] ?? '');

        if (!$name) {
            echo json_encode(['success' => false, 'message' => 'Name is required.']);
            exit;
        }
        if (!in_array($role, ['freelancer', 'client', 'both'])) {
            $role = $_SESSION['user_role'];
        }

        // Handle avatar upload
        $avatarPath = null;
        if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
            $allowed = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
            $ftype   = mime_content_type($_FILES['avatar']['tmp_name']);
            if (!in_array($ftype, $allowed)) {
                echo json_encode(['success' => false, 'message' => 'Invalid image type. Use JPG, PNG, GIF or WEBP.']);
                exit;
            }
            $ext      = pathinfo($_FILES['avatar']['name'], PATHINFO_EXTENSION);
            $filename = 'avatar_' . $userId . '_' . time() . '.' . $ext;
            $destDir  = __DIR__ . '/../assets/images/';
            if (!is_dir($destDir)) mkdir($destDir, 0755, true);
            move_uploaded_file($_FILES['avatar']['tmp_name'], $destDir . $filename);
            $avatarPath = $filename;
        }

        if ($avatarPath) {
            $stmt = $pdo->prepare("UPDATE users SET name=?, bio=?, role=?, avatar=? WHERE user_id=?");
            $stmt->execute([$name, $bio, $role, $avatarPath, $userId]);
        } else {
            $stmt = $pdo->prepare("UPDATE users SET name=?, bio=?, role=? WHERE user_id=?");
            $stmt->execute([$name, $bio, $role, $userId]);
        }

        $_SESSION['user_name'] = $name;
        $_SESSION['user_role'] = $role;
        if ($avatarPath) {
            $_SESSION['user_avatar'] = $avatarPath;
        }

        echo json_encode(['success' => true, 'message' => 'Profile updated successfully!']);
        break;


    // ── CHANGE PASSWORD ───────────────────────────────────
    case 'change_password':
        $current = $_POST['current_password'] ?? '';
        $new     = $_POST['new_password']     ?? '';
        $confirm = $_POST['confirm_password'] ?? '';

        if (!$current || !$new || !$confirm) {
            echo json_encode(['success' => false, 'message' => 'All password fields are required.']);
            exit;
        }
        if ($new !== $confirm) {
            echo json_encode(['success' => false, 'message' => 'New passwords do not match.']);
            exit;
        }
        if (strlen($new) < 6) {
            echo json_encode(['success' => false, 'message' => 'New password must be at least 6 characters.']);
            exit;
        }

        $stmt = $pdo->prepare("SELECT password FROM users WHERE user_id = ?");
        $stmt->execute([$userId]);
        $hash = $stmt->fetchColumn();

        if (!password_verify($current, $hash)) {
            echo json_encode(['success' => false, 'message' => 'Current password is incorrect.']);
            exit;
        }

        $newHash = password_hash($new, PASSWORD_BCRYPT);
        $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE user_id = ?");
        $stmt->execute([$newHash, $userId]);

        echo json_encode(['success' => true, 'message' => 'Password changed successfully!']);
        break;


    // ── GET NOTIFICATIONS ─────────────────────────────────
    case 'get_notifications':
        $stmt = $pdo->prepare("SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 20");
        $stmt->execute([$userId]);
        $notifs = $stmt->fetchAll();

        $unread = array_filter($notifs, fn($n) => !$n['is_read']);
        echo json_encode(['success' => true, 'notifications' => $notifs, 'unread_count' => count($unread)]);
        break;


    // ── MARK NOTIFICATIONS READ ───────────────────────────
    case 'mark_read':
        $stmt = $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ?");
        $stmt->execute([$userId]);
        echo json_encode(['success' => true]);
        break;


    // ── DASHBOARD STATS ───────────────────────────────────
    case 'dashboard_stats':
        $stats = [];

        // Freelancer stats
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM user_skills WHERE user_id = ?");
        $stmt->execute([$userId]);
        $stats['total_skills'] = (int)$stmt->fetchColumn();

        $stmt = $pdo->prepare("SELECT COUNT(*) FROM user_skills WHERE user_id = ? AND verification_status = 'Verified'");
        $stmt->execute([$userId]);
        $stats['verified_skills'] = (int)$stmt->fetchColumn();

        $stmt = $pdo->prepare("SELECT COUNT(*) FROM applications WHERE user_id = ?");
        $stmt->execute([$userId]);
        $stats['total_applications'] = (int)$stmt->fetchColumn();

        $stmt = $pdo->prepare("SELECT COUNT(*) FROM applications WHERE user_id = ? AND status = 'Accepted'");
        $stmt->execute([$userId]);
        $stats['accepted'] = (int)$stmt->fetchColumn();

        // Client stats
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM projects WHERE client_id = ?");
        $stmt->execute([$userId]);
        $stats['total_projects'] = (int)$stmt->fetchColumn();

        $stmt = $pdo->prepare("SELECT COUNT(*) FROM projects WHERE client_id = ? AND status = 'Open'");
        $stmt->execute([$userId]);
        $stats['open_projects'] = (int)$stmt->fetchColumn();

        $stmt = $pdo->prepare("SELECT COUNT(*) FROM applications a JOIN projects p ON a.project_id = p.project_id WHERE p.client_id = ?");
        $stmt->execute([$userId]);
        $stats['total_received'] = (int)$stmt->fetchColumn();

        echo json_encode(['success' => true, 'stats' => $stats]);
        break;


    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action.']);
}
