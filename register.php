<?php
require_once 'includes/config.php';
startSession();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = sanitize($_POST['name'] ?? '');
    $email = sanitize($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $role = sanitize($_POST['role'] ?? 'freelancer');

    if (!$name || !$email || !$password) {
        setFlash('error', 'All fields are required.');
        redirect(app_url('register.php'));
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        setFlash('error', 'Please enter a valid email address.');
        redirect(app_url('register.php'));
    }

    if (strlen($password) < 6) {
        setFlash('error', 'Password must be at least 6 characters.');
        redirect(app_url('register.php'));
    }

    if (!in_array($role, ['freelancer', 'client', 'both'], true)) {
        $role = 'freelancer';
    }

    $stmt = $pdo->prepare("SELECT user_id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    if ($stmt->fetch()) {
        setFlash('error', 'Email already registered. Please login.');
        redirect(app_url('register.php'));
    }

    $hash = password_hash($password, PASSWORD_BCRYPT);
    $stmt = $pdo->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, ?)");
    $stmt->execute([$name, $email, $hash, $role]);

    $userId = (int) $pdo->lastInsertId();
    addNotification($pdo, $userId, "Welcome to SkillForge, $name! Start by adding your skills.", 'success');
    setFlash('success', 'Account created successfully. Please sign in.');
    redirect(app_url('login.php'));
}

if (isLoggedIn()) redirect(app_url('dashboard.php'));
$defaultRole = sanitize($_GET['role'] ?? 'freelancer');
$flash = getFlash();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Register – SkillForge</title>
  <link rel="stylesheet" href="css/style.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
</head>
<body data-app-url="<?= htmlspecialchars(APP_URL, ENT_QUOTES, 'UTF-8') ?>">
<div class="auth-wrapper">
  <div class="auth-card">
    <div class="auth-logo">
      <a href="index.php" class="brand" style="font-family:var(--font-head);font-size:1.6rem;font-weight:700;color:var(--text-1);">
        <i class="fas fa-bolt text-gold"></i> Skill<span style="color:var(--gold);">Forge</span>
      </a>
    </div>

    <h2 class="auth-title">Create Your Account</h2>
    <p class="auth-subtitle">Join the marketplace of verified professionals.</p>

    <?php if ($flash): ?>
      <div style="margin-bottom:1rem;padding:0.9rem 1rem;border-radius:12px;border:1px solid <?= $flash['type'] === 'error' ? 'rgba(220,53,69,0.35)' : 'rgba(25,135,84,0.35)' ?>;background:<?= $flash['type'] === 'error' ? 'rgba(220,53,69,0.08)' : 'rgba(25,135,84,0.08)' ?>;color:#f5f5f5;">
        <?= htmlspecialchars($flash['message'], ENT_QUOTES, 'UTF-8') ?>
      </div>
    <?php endif; ?>

    <form id="registerForm" method="post" action="" onsubmit="return handleRegister(event)" novalidate>
      <div class="form-group">
        <label class="form-label"><i class="fas fa-user"></i> Full Name</label>
        <input type="text" class="form-control" name="name" placeholder="John Doe" required autocomplete="name">
      </div>
      <div class="form-group">
        <label class="form-label"><i class="fas fa-envelope"></i> Email Address</label>
        <input type="email" class="form-control" name="email" placeholder="john@example.com" required autocomplete="email">
      </div>
      <div class="form-group">
        <label class="form-label"><i class="fas fa-lock"></i> Password</label>
        <input type="password" class="form-control" name="password" placeholder="At least 6 characters" required autocomplete="new-password">
      </div>
      <div class="form-group">
        <label class="form-label"><i class="fas fa-id-badge"></i> I am a...</label>
        <select class="form-control" name="role" required>
          <option value="freelancer" <?= $defaultRole === 'freelancer' ? 'selected' : '' ?>>Freelancer – I offer skills & services</option>
          <option value="client"     <?= $defaultRole === 'client'     ? 'selected' : '' ?>>Client – I need work done</option>
          <option value="both"       <?= $defaultRole === 'both'       ? 'selected' : '' ?>>Both – I do both</option>
        </select>
        <div class="form-hint">You can change this later in your profile settings.</div>
      </div>
      <button type="submit" class="btn btn-gold btn-full btn-lg">
        <i class="fas fa-rocket"></i> Create Account
      </button>
    </form>

    <div class="auth-switch">
      Already have an account? <a href="login.php">Sign In</a>
    </div>
  </div>
</div>

<div id="toastContainer" class="toast-container"></div>
<script>
  window.SKILLFORGE_CONFIG = {
    appUrl: <?= json_encode(APP_URL) ?>
  };
</script>
<script src="js/app.js"></script>
</body>
</html>
