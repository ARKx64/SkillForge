<?php
require_once 'includes/config.php';
startSession();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = sanitize($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (!$email || !$password) {
        setFlash('error', 'Email and password are required.');
        redirect(app_url('login.php'));
    }

    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? AND is_active = 1");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if (!$user || !password_verify($password, $user['password'])) {
        setFlash('error', 'Invalid email or password.');
        redirect(app_url('login.php'));
    }

    $_SESSION['user_id'] = $user['user_id'];
    $_SESSION['user_name'] = $user['name'];
    $_SESSION['user_email'] = $user['email'];
    $_SESSION['user_role'] = $user['role'];
    $_SESSION['user_avatar'] = $user['avatar'];
    session_regenerate_id(true);

    redirect(app_url('dashboard.php'));
}

if (isLoggedIn()) redirect(app_url('dashboard.php'));
$flash = getFlash();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Login – SkillForge</title>
  <link rel="stylesheet" href="css/style.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
</head>
<body data-app-url="<?= htmlspecialchars(APP_URL, ENT_QUOTES, 'UTF-8') ?>">
<div class="auth-wrapper">
  <div class="auth-card">
    <div class="auth-logo">
      <a href="index.php" style="font-family:var(--font-head);font-size:1.6rem;font-weight:700;color:var(--text-1);text-decoration:none;">
        <i class="fas fa-bolt text-gold"></i> Skill<span style="color:var(--gold);">Forge</span>
      </a>
    </div>

    <h2 class="auth-title">Welcome Back</h2>
    <p class="auth-subtitle">Sign in to access your dashboard.</p>

    <?php if ($flash): ?>
      <div style="margin-bottom:1rem;padding:0.9rem 1rem;border-radius:12px;border:1px solid <?= $flash['type'] === 'error' ? 'rgba(220,53,69,0.35)' : 'rgba(25,135,84,0.35)' ?>;background:<?= $flash['type'] === 'error' ? 'rgba(220,53,69,0.08)' : 'rgba(25,135,84,0.08)' ?>;color:#f5f5f5;">
        <?= htmlspecialchars($flash['message'], ENT_QUOTES, 'UTF-8') ?>
      </div>
    <?php endif; ?>

    <form id="loginForm" method="post" action="" onsubmit="return handleLogin(event)" novalidate>
      <div class="form-group">
        <label class="form-label"><i class="fas fa-envelope"></i> Email Address</label>
        <input type="email" class="form-control" name="email" placeholder="john@example.com" required autocomplete="email">
      </div>
      <div class="form-group">
        <label class="form-label"><i class="fas fa-lock"></i> Password</label>
        <input type="password" class="form-control" name="password" placeholder="Your password" required autocomplete="current-password">
      </div>
      <button type="submit" class="btn btn-gold btn-full btn-lg">
        <i class="fas fa-sign-in-alt"></i> Sign In
      </button>
    </form>

    <!-- Demo Credentials hint (remove in production) -->
    <div style="background:var(--gold-dim);border:1px solid var(--border-gold);border-radius:var(--radius-sm);padding:0.85rem;margin-top:1.25rem;font-size:0.82rem;">
      <strong style="color:var(--gold);"><i class="fas fa-info-circle"></i> Demo</strong><br>
      <span style="color:var(--text-2);">Register a new account to get started.</span>
    </div>

    <div class="auth-switch">
      Don't have an account? <a href="register.php">Create one free</a>
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
