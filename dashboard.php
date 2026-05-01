<?php
// Dashboard - Protected page (must be logged in)
require_once 'includes/config.php';
startSession();
if (!isLoggedIn()) redirect(app_url('login.php'));

$user     = currentUser();
$userName = htmlspecialchars($user['user_name']);
$userRole = htmlspecialchars($user['user_role']);
$userInit = strtoupper(mb_substr($user['user_name'], 0, 1));

$avatarSrc = avatar_url($user['user_avatar'] ?? null);

$isClient     = in_array($userRole, ['client', 'both']);
$isFreelancer = in_array($userRole, ['freelancer', 'both']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Dashboard – SkillForge</title>
  <link rel="stylesheet" href="css/style.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
</head>
<body data-app-url="<?= htmlspecialchars(APP_URL, ENT_QUOTES, 'UTF-8') ?>">

<!-- Toast Container -->
<div id="toastContainer" class="toast-container"></div>

<!-- ── Navbar ───────────────────────────────────────────── -->
<nav class="navbar">
  <div style="display:flex;align-items:center;gap:0.75rem;">
    <button id="menuToggle" style="display:none;background:none;border:none;color:var(--text-2);font-size:1.2rem;cursor:pointer;padding:0.3rem;">
      <i class="fas fa-bars"></i>
    </button>
    <a href="index.php" class="nav-brand">
      <div class="logo-icon"><i class="fas fa-bolt"></i></div>
      Skill<span>Forge</span>
    </a>
  </div>

  <div class="nav-right">
    <!-- Notifications -->
    <button class="notif-btn" onclick="toggleNotifPanel()" title="Notifications">
      <i class="fas fa-bell"></i>
      <span class="notif-badge" id="notifBadge" style="display:none;">0</span>
    </button>

    <!-- User Menu -->
    <div class="user-menu" onclick="activateSidebarLink('profile')">
      <div class="user-avatar">
        <?php if ($avatarSrc): ?>
          <img src="<?= $avatarSrc ?>" alt="Avatar">
        <?php else: ?>
          <?= $userInit ?>
        <?php endif; ?>
      </div>
      <span class="user-name-nav"><?= $userName ?></span>
      <i class="fas fa-chevron-down" style="font-size:0.7rem;color:var(--text-3);"></i>
    </div>

    <button class="btn btn-outline btn-sm" onclick="handleLogout()">
      <i class="fas fa-sign-out-alt"></i>
    </button>
  </div>
</nav>

<!-- ── Notification Panel ───────────────────────────────── -->
<div class="notif-panel" id="notifPanel">
  <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:1rem;">
    <h4>Notifications</h4>
    <button onclick="toggleNotifPanel()" style="background:none;border:none;color:var(--text-3);cursor:pointer;"><i class="fas fa-times"></i></button>
  </div>
  <div class="notif-list"></div>
</div>

<!-- ── Sidebar ───────────────────────────────────────────── -->
<aside class="sidebar">
  <!-- Freelancer Nav -->
  <?php if ($isFreelancer): ?>
  <div class="sidebar-section">
    <div class="sidebar-label">Freelancer</div>
    <button class="sidebar-link active" data-section="dashboard" onclick="activateSidebarLink('dashboard')">
      <i class="fas fa-tachometer-alt"></i> Dashboard
    </button>
    <button class="sidebar-link" data-section="skills" onclick="activateSidebarLink('skills')">
      <i class="fas fa-award"></i> My Skills
    </button>
    <button class="sidebar-link" data-section="projects" onclick="activateSidebarLink('projects')">
      <i class="fas fa-search"></i> Browse Projects
    </button>
    <button class="sidebar-link" data-section="applications" onclick="activateSidebarLink('applications')">
      <i class="fas fa-file-alt"></i> My Applications
    </button>
  </div>
  <?php endif; ?>

  <!-- Client Nav -->
  <?php if ($isClient): ?>
  <?php if ($isFreelancer): ?><div class="sidebar-divider"></div><?php endif; ?>
  <div class="sidebar-section">
    <div class="sidebar-label">Client</div>
    <?php if (!$isFreelancer): ?>
    <button class="sidebar-link active" data-section="dashboard" onclick="activateSidebarLink('dashboard')">
      <i class="fas fa-tachometer-alt"></i> Dashboard
    </button>
    <?php endif; ?>
    <button class="sidebar-link" data-section="my-projects" onclick="activateSidebarLink('my-projects')">
      <i class="fas fa-folder"></i> My Projects
    </button>
    <button class="sidebar-link" data-section="post-project-section" onclick="activateSidebarLink('post-project-section')">
      <i class="fas fa-plus-circle"></i> Post Project
    </button>
  </div>
  <?php endif; ?>

  <div class="sidebar-divider"></div>
  <div class="sidebar-section">
    <div class="sidebar-label">Account</div>
    <button class="sidebar-link" data-section="profile" onclick="activateSidebarLink('profile')">
      <i class="fas fa-user-circle"></i> Profile
    </button>
    <button class="sidebar-link" onclick="handleLogout()" style="color:var(--red);">
      <i class="fas fa-sign-out-alt"></i> Sign Out
    </button>
  </div>
</aside>

<!-- ── Main Content ──────────────────────────────────────── -->
<main class="main-content">
  <div class="content-area">

    <!-- ═══ DASHBOARD SECTION ═══════════════════════════ -->
    <section id="section-dashboard" class="page-section">
      <div style="margin-bottom:2rem;">
        <h1 style="font-size:1.8rem;">Good day, <?= $userName ?> 👋</h1>
        <p>Here's what's happening on your SkillForge account.</p>
      </div>

      <!-- Stats Grid -->
      <div class="stats-grid">
        <?php if ($isFreelancer): ?>
        <div class="stat-card">
          <div class="stat-icon gold"><i class="fas fa-award"></i></div>
          <div><div class="stat-num" id="statTotalSkills">—</div><div class="stat-label">Skills Enlisted</div></div>
        </div>
        <div class="stat-card">
          <div class="stat-icon green"><i class="fas fa-check-circle"></i></div>
          <div><div class="stat-num" id="statVerified">—</div><div class="stat-label">Verified Skills</div></div>
        </div>
        <div class="stat-card">
          <div class="stat-icon blue"><i class="fas fa-paper-plane"></i></div>
          <div><div class="stat-num" id="statApplied">—</div><div class="stat-label">Applications Sent</div></div>
        </div>
        <div class="stat-card">
          <div class="stat-icon purple"><i class="fas fa-handshake"></i></div>
          <div><div class="stat-num" id="statAccepted">—</div><div class="stat-label">Accepted</div></div>
        </div>
        <?php endif; ?>
        <?php if ($isClient): ?>
        <div class="stat-card">
          <div class="stat-icon gold"><i class="fas fa-folder"></i></div>
          <div><div class="stat-num" id="statProjects">—</div><div class="stat-label">Projects Posted</div></div>
        </div>
        <div class="stat-card">
          <div class="stat-icon green"><i class="fas fa-briefcase"></i></div>
          <div><div class="stat-num" id="statOpenProjects">—</div><div class="stat-label">Open Projects</div></div>
        </div>
        <div class="stat-card">
          <div class="stat-icon blue"><i class="fas fa-users"></i></div>
          <div><div class="stat-num" id="statReceived">—</div><div class="stat-label">Total Applications Received</div></div>
        </div>
        <?php endif; ?>
      </div>

      <!-- Role Badge -->
      <div class="card card-gold" style="display:flex;align-items:center;gap:1.25rem;flex-wrap:wrap;">
        <div style="width:50px;height:50px;background:var(--gold-dim);border:1px solid var(--border-gold);border-radius:12px;display:grid;place-items:center;font-size:1.3rem;color:var(--gold);">
          <i class="fas fa-id-card"></i>
        </div>
        <div style="flex:1;">
          <h4 style="margin-bottom:0.25rem;">Account Role: <span class="text-gold"><?= ucfirst($userRole) ?></span></h4>
          <p style="font-size:0.85rem;margin:0;">
            <?php if ($isFreelancer && $isClient): ?>
              You have full access: enlist skills, take tests, apply for projects, and post your own.
            <?php elseif ($isFreelancer): ?>
              Enlist your skills, get verified, and apply to open projects.
            <?php else: ?>
              Post projects and find verified freelancers to work with.
            <?php endif; ?>
          </p>
        </div>
        <button class="btn btn-outline btn-sm" onclick="activateSidebarLink('profile')">
          <i class="fas fa-edit"></i> Edit Role
        </button>
      </div>

      <!-- Quick Actions -->
      <div style="margin-top:2rem;">
        <h3 style="margin-bottom:1rem;">Quick Actions</h3>
        <div style="display:flex;flex-wrap:wrap;gap:0.75rem;">
          <?php if ($isFreelancer): ?>
          <button class="btn btn-gold" onclick="activateSidebarLink('skills')"><i class="fas fa-plus"></i> Add Skills</button>
          <button class="btn btn-outline" onclick="activateSidebarLink('projects')"><i class="fas fa-search"></i> Browse Projects</button>
          <?php endif; ?>
          <?php if ($isClient): ?>
          <button class="btn btn-gold" onclick="openPostProjectModal()"><i class="fas fa-rocket"></i> Post a Project</button>
          <button class="btn btn-outline" onclick="activateSidebarLink('my-projects')"><i class="fas fa-folder"></i> My Projects</button>
          <?php endif; ?>
        </div>
      </div>
    </section>

    <!-- ═══ SKILLS SECTION ═══════════════════════════════ -->
    <section id="section-skills" class="page-section hidden">
      <div class="tabs" data-tab-group="skillsTabs">
        <button class="tab-btn active" onclick="switchTab('mySkillsTab','skillsTabs');loadUserSkills()">My Enlisted Skills</button>
        <button class="tab-btn" onclick="switchTab('marketplaceTab','skillsTabs');loadAllSkills()">Skills Marketplace</button>
      </div>

      <!-- My Skills Tab -->
      <div id="mySkillsTab" class="tab-content active" data-tab-group="skillsTabs">
        <div class="section-header">
          <h2 class="section-title">My Skills</h2>
        </div>
        <div id="userSkillsList"><div class="spinner"></div></div>
      </div>

      <!-- Marketplace Tab -->
      <div id="marketplaceTab" class="tab-content" data-tab-group="skillsTabs">
        <div class="section-header">
          <h2 class="section-title">Skills Marketplace</h2>
          <p style="font-size:0.87rem;">Click any skill to enlist it, then take the verification test.</p>
        </div>
        <div class="skills-grid" id="skillsMarketplace"><div class="spinner"></div></div>
      </div>
    </section>

    <!-- ═══ BROWSE PROJECTS SECTION ══════════════════════ -->
    <section id="section-projects" class="page-section hidden">
      <div class="section-header">
        <h2 class="section-title">Browse Projects</h2>
        <span class="badge badge-open" id="projectCount"></span>
      </div>

      <!-- Filters -->
      <div class="filter-bar">
        <input type="text" class="form-control" id="searchProjects" placeholder="🔍 Search projects...">
        <select class="form-control skill-filter-select" id="filterSkill" style="max-width:200px;" onchange="filterProjects()">
          <option value="">All Skills</option>
        </select>
        <input type="number" class="form-control" id="filterMinBudget" placeholder="Min $" style="max-width:100px;" oninput="filterProjects()">
        <input type="number" class="form-control" id="filterMaxBudget" placeholder="Max $" style="max-width:100px;" oninput="filterProjects()">
        <button class="btn btn-outline btn-sm" onclick="filterProjects()"><i class="fas fa-filter"></i></button>
      </div>

      <div id="projectsContainer"><div class="spinner"></div></div>
    </section>

    <!-- ═══ MY APPLICATIONS SECTION ══════════════════════ -->
    <section id="section-applications" class="page-section hidden">
      <div class="section-header">
        <h2 class="section-title">My Applications</h2>
      </div>
      <div id="myApplicationsContainer"><div class="spinner"></div></div>
    </section>

    <!-- ═══ MY PROJECTS (CLIENT) SECTION ═════════════════ -->
    <section id="section-my-projects" class="page-section hidden">
      <div class="section-header">
        <h2 class="section-title">My Posted Projects</h2>
        <button class="btn btn-gold" onclick="openPostProjectModal()">
          <i class="fas fa-plus"></i> Post New Project
        </button>
      </div>
      <div id="myProjectsContainer"><div class="spinner"></div></div>
    </section>

    <!-- ═══ POST PROJECT SECTION ══════════════════════════ -->
    <section id="section-post-project-section" class="page-section hidden">
      <div class="section-header">
        <h2 class="section-title">Post a New Project</h2>
      </div>
      <div class="card" style="max-width:680px;">
        <div class="form-group">
          <label class="form-label">Project Title *</label>
          <input type="text" class="form-control" id="inlinePTitle" placeholder="e.g. Build a Landing Page">
        </div>
        <div class="form-group">
          <label class="form-label">Description *</label>
          <textarea class="form-control" id="inlinePDesc" rows="6" placeholder="Describe what you need done in detail..."></textarea>
        </div>
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Budget (USD) *</label>
            <input type="number" class="form-control" id="inlinePBudget" placeholder="500" min="1">
          </div>
          <div class="form-group">
            <label class="form-label">Deadline *</label>
            <input type="date" class="form-control" id="inlinePDeadline">
          </div>
        </div>
        <div class="form-group">
          <label class="form-label">Required Skill (Optional)</label>
          <select class="form-control skill-filter-select" id="inlinePSkill"></select>
        </div>
        <button class="btn btn-gold btn-lg" onclick="submitInlineProject()">
          <i class="fas fa-rocket"></i> Post Project
        </button>
      </div>
    </section>

    <!-- ═══ PROFILE SECTION ═══════════════════════════════ -->
    <section id="section-profile" class="page-section hidden">
      <h2 class="section-title" style="margin-bottom:1.5rem;">My Profile</h2>

      <div class="profile-header">
        <div class="profile-avatar-large" id="profileAvatarLarge">
          <?php if ($avatarSrc): ?>
            <img src="<?= $avatarSrc ?>" alt="Avatar">
          <?php else: ?>
            <?= $userInit ?>
          <?php endif; ?>
        </div>
        <div>
          <h2 id="profileName"><?= $userName ?></h2>
          <div style="color:var(--text-3);font-size:0.85rem;" id="profileEmail"><?= htmlspecialchars($user['user_email']) ?></div>
          <div class="profile-meta">
            <span id="profileRole"><span class="badge badge-gold-role"><?= ucfirst($userRole) ?></span></span>
          </div>
          <p id="profileBio" style="font-size:0.87rem;margin-top:0.5rem;"></p>
        </div>
      </div>

      <!-- Profile Stats -->
      <div class="stats-grid" style="max-width:500px;margin-bottom:2rem;">
        <div class="stat-card"><div class="stat-icon green"><i class="fas fa-check-circle"></i></div>
          <div><div class="stat-num" id="pStatVerified">—</div><div class="stat-label">Verified Skills</div></div></div>
        <div class="stat-card"><div class="stat-icon gold"><i class="fas fa-trophy"></i></div>
          <div><div class="stat-num" id="pStatWon">—</div><div class="stat-label">Projects Won</div></div></div>
        <div class="stat-card"><div class="stat-icon blue"><i class="fas fa-folder"></i></div>
          <div><div class="stat-num" id="pStatPosted">—</div><div class="stat-label">Projects Posted</div></div></div>
      </div>

      <div class="tabs" data-tab-group="profileTabs">
        <button class="tab-btn active" onclick="switchTab('editProfileTab','profileTabs')">Edit Profile</button>
        <button class="tab-btn" onclick="switchTab('changePassTab','profileTabs')">Change Password</button>
      </div>

      <!-- Edit Profile -->
      <div id="editProfileTab" class="tab-content active" data-tab-group="profileTabs">
        <form onsubmit="saveProfile(event)" enctype="multipart/form-data" style="max-width:520px;">
          <div class="form-group">
            <label class="form-label">Full Name</label>
            <input type="text" class="form-control" name="name" id="editName" required>
          </div>
          <div class="form-group">
            <label class="form-label">Bio</label>
            <textarea class="form-control" name="bio" id="editBio" rows="3" placeholder="Tell clients and freelancers about yourself..."></textarea>
          </div>
          <div class="form-group">
            <label class="form-label">Account Role</label>
            <select class="form-control" name="role" id="editRole">
              <option value="freelancer">Freelancer</option>
              <option value="client">Client</option>
              <option value="both">Both</option>
            </select>
          </div>
          <div class="form-group">
            <label class="form-label">Profile Picture</label>
            <input type="file" class="form-control" name="avatar" accept="image/*">
            <div class="form-hint">Supported: JPG, PNG, WEBP. Max 2MB. Stored in assets/images/</div>
          </div>
          <button type="submit" class="btn btn-gold"><i class="fas fa-save"></i> Save Changes</button>
        </form>
      </div>

      <!-- Change Password -->
      <div id="changePassTab" class="tab-content" data-tab-group="profileTabs">
        <form onsubmit="changePassword(event)" style="max-width:420px;">
          <div class="form-group">
            <label class="form-label">Current Password</label>
            <input type="password" class="form-control" name="current_password" required>
          </div>
          <div class="form-group">
            <label class="form-label">New Password</label>
            <input type="password" class="form-control" name="new_password" required>
          </div>
          <div class="form-group">
            <label class="form-label">Confirm New Password</label>
            <input type="password" class="form-control" name="confirm_password" required>
          </div>
          <button type="submit" class="btn btn-gold"><i class="fas fa-key"></i> Update Password</button>
        </form>
      </div>
    </section>

  </div><!-- /content-area -->
</main>

<script>
window.SKILLFORGE_CONFIG = {
  appUrl: <?= json_encode(APP_URL) ?>
};
</script>
<script src="js/app.js"></script>
<script>
// Inline project submit (from the dedicated "Post Project" page section)
async function submitInlineProject() {
  const data = {
    action:      'post_project',
    title:       document.getElementById('inlinePTitle').value,
    description: document.getElementById('inlinePDesc').value,
    budget:      document.getElementById('inlinePBudget').value,
    deadline:    document.getElementById('inlinePDeadline').value,
    skill_id:    document.getElementById('inlinePSkill').value,
  };
  if (!data.title || !data.description || !data.budget || !data.deadline) {
    showToast('Please fill all required fields.', 'error'); return;
  }
  const res = await apiCall(`${APP_URL}/php/projects.php`, data);
  showToast(res.message, res.success ? 'success' : 'error');
  if (res.success) {
    document.getElementById('inlinePTitle').value = '';
    document.getElementById('inlinePDesc').value = '';
    document.getElementById('inlinePBudget').value = '';
    document.getElementById('inlinePDeadline').value = '';
    loadDashboardStats();
    activateSidebarLink('my-projects');
  }
}

// Mobile responsiveness
if (window.innerWidth <= 900) {
  document.getElementById('menuToggle').style.display = 'block';
}
window.addEventListener('resize', () => {
  document.getElementById('menuToggle').style.display = window.innerWidth <= 900 ? 'block' : 'none';
});
</script>
</body>
</html>
