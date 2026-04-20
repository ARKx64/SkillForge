<?php
require_once 'includes/config.php';
startSession();
if (isLoggedIn()) redirect(app_url('dashboard.php'));
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>SkillForge - The Professional Skills Marketplace</title>
  <link rel="stylesheet" href="css/style.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
  <style>
    .features-section { padding: 6rem 0; }
    .features-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap: 1.5rem; margin-top: 3rem; }
    .feature-card {
      background: linear-gradient(180deg, rgba(17, 29, 53, 0.95), rgba(10, 18, 34, 0.92));
      border: 1px solid var(--border);
      border-radius: var(--radius);
      padding: 2rem;
      text-align: center;
      transition: var(--transition);
      position: relative;
      overflow: hidden;
    }
    .feature-card::before {
      content: '';
      position: absolute;
      inset: 0;
      background: linear-gradient(140deg, rgba(255, 255, 255, 0.06), transparent 38%, transparent 62%, rgba(212, 175, 55, 0.08));
      opacity: 0;
      transition: var(--transition);
    }
    .feature-card:hover { border-color: var(--border-gold); transform: translateY(-6px); box-shadow: var(--shadow); }
    .feature-card:hover::before { opacity: 1; }
    .feature-icon { width: 64px; height: 64px; background: var(--gold-dim); border: 1px solid var(--border-gold); border-radius: 16px; display: grid; place-items: center; margin: 0 auto 1.25rem; font-size: 1.5rem; color: var(--gold); position: relative; z-index: 1; }
    .feature-card h3, .feature-card p { position: relative; z-index: 1; }
    .feature-card h3 { margin-bottom: 0.75rem; font-size: 1.05rem; }
    .how-section { padding: 6rem 0; background: linear-gradient(180deg, rgba(8, 13, 26, 0.98), rgba(13, 21, 38, 0.98)); }
    .steps-list { display: flex; flex-direction: column; gap: 1.5rem; max-width: 700px; margin: 3rem auto 0; }
    .step-item { display: flex; align-items: flex-start; gap: 1.25rem; }
    .step-num { width: 44px; height: 44px; border-radius: 50%; background: var(--gold-dim); border: 1px solid var(--border-gold); color: var(--gold); font-family: var(--font-head); font-weight: 700; display: grid; place-items: center; flex-shrink: 0; box-shadow: 0 0 25px rgba(212, 175, 55, 0.12); }
    .cta-section { padding: 7rem 0; text-align: center; }
    .cta-box { background: linear-gradient(135deg, rgba(17, 29, 53, 0.96) 0%, rgba(212, 175, 55, 0.08) 100%); border: 1px solid var(--border-gold); border-radius: var(--radius-lg); padding: 4rem 2rem; max-width: 680px; margin: 0 auto; box-shadow: var(--shadow-gold); }
    .skills-scroll { display: flex; gap: 1rem; overflow-x: auto; padding: 1.5rem 0; margin-top: 3rem; -ms-overflow-style: none; scrollbar-width: none; }
    .skills-scroll::-webkit-scrollbar { display: none; }
    .skill-pill { background: rgba(17, 29, 53, 0.92); border: 1px solid var(--border); border-radius: 999px; padding: 0.65rem 1.2rem; white-space: nowrap; font-size: 0.85rem; color: var(--text-2); display: flex; align-items: center; gap: 0.5rem; }
    .nav-public { display: flex; align-items: center; gap: 0.75rem; }
    footer { padding: 2rem; text-align: center; border-top: 1px solid var(--border); color: var(--text-3); font-size: 0.82rem; }
    @media (max-width: 900px) {
      .features-grid { grid-template-columns: 1fr; }
    }
  </style>
</head>
<body data-app-url="<?= htmlspecialchars(APP_URL, ENT_QUOTES, 'UTF-8') ?>">

<nav class="navbar">
  <a href="index.php" class="nav-brand">
    <div class="logo-icon"><i class="fas fa-bolt"></i></div>
    Skill<span>Forge</span>
  </a>
  <ul class="nav-links" style="display:none;" id="navLinks">
    <li><a href="#features">Features</a></li>
    <li><a href="#how">How It Works</a></li>
  </ul>
  <div class="nav-public">
    <a href="login.php" class="btn btn-outline">Login</a>
    <a href="register.php" class="btn btn-gold">Get Started <i class="fas fa-arrow-right"></i></a>
  </div>
</nav>

<section class="hero">
  <div class="container hero-shell">
    <div class="hero-content">
      <div class="hero-badge"><i class="fas fa-bolt"></i> The Future of Freelancing</div>
      <h1>Where Skills Meet<br><span class="gradient">Opportunity</span></h1>
      <p>SkillForge connects verified professionals with clients who need their expertise. Earn your badge. Land your dream project.</p>
      <div class="hero-btns">
        <a href="register.php" class="btn btn-gold btn-lg"><i class="fas fa-rocket"></i> Start as Freelancer</a>
        <a href="register.php?role=client" class="btn btn-outline btn-lg"><i class="fas fa-briefcase"></i> Hire Talent</a>
      </div>
      <div class="hero-stats">
        <div>
          <div class="hero-stat-num">10+</div>
          <div class="hero-stat-label">Skill Categories</div>
        </div>
        <div>
          <div class="hero-stat-num">30</div>
          <div class="hero-stat-label">Questions Per Skill</div>
        </div>
        <div>
          <div class="hero-stat-num">>=60%</div>
          <div class="hero-stat-label">Test Pass Rate</div>
        </div>
        <div>
          <div class="hero-stat-num">24/7</div>
          <div class="hero-stat-label">Platform Access</div>
        </div>
      </div>
    </div>

    <div class="hero-showcase">
      <div class="showcase-panel">
        <div class="showcase-toolbar">
          <div class="showcase-dots">
            <span></span><span></span><span></span>
          </div>
          <span>Verified Talent Radar</span>
        </div>
        <div class="showcase-grid">
          <article class="showcase-card">
            <div class="showcase-icon gold"><i class="fas fa-shield-halved"></i></div>
            <h3>Trust-First Profiles</h3>
            <p>Verification badges highlight freelancers who passed the skill benchmark.</p>
          </article>
          <article class="showcase-card">
            <div class="showcase-icon blue"><i class="fas fa-clipboard-question"></i></div>
            <h3>30-Question Banks</h3>
            <p>Every skill now has a deeper question pool, with 10 random MCQs per attempt.</p>
          </article>
          <article class="showcase-card wide">
            <div class="showcase-strip">
              <span><i class="fas fa-user-check"></i> Verified Badges</span>
              <span><i class="fas fa-briefcase"></i> Live Marketplace</span>
              <span><i class="fas fa-bell"></i> Instant Updates</span>
            </div>
          </article>
        </div>
        <div class="floating-chip chip-verified"><i class="fas fa-award"></i> Skill Badges</div>
        <div class="floating-chip chip-clients"><i class="fas fa-briefcase"></i> Client Jobs</div>
      </div>
    </div>
  </div>
</section>

<section style="padding: 0 0 3rem; overflow: hidden;">
  <div class="container">
    <p class="text-center text-muted" style="font-size:0.8rem;letter-spacing:0.1em;text-transform:uppercase;margin-bottom:0.5rem;">Available Skill Tracks</p>
    <div class="skills-scroll">
      <?php
      $skills = [
        ['laptop-code','Frontend Development'],['server','Backend Development'],['python','Python Development'],
        ['brain','AI & ML Expert'],['palette','UI/UX Design'],['mobile-alt','Mobile Development'],
        ['database','Database Design'],['cloud','DevOps & Cloud'],['paint-brush','Graphic Design'],['pen-nib','Content Writing'],
      ];
      foreach ($skills as $s) {
          echo "<div class='skill-pill'><i class='fas fa-{$s[0]}'></i> {$s[1]}</div>";
      }
      ?>
    </div>
  </div>
</section>

<section class="features-section" id="features">
  <div class="container">
    <div class="text-center">
      <div class="hero-badge" style="margin:0 auto 1rem;"><i class="fas fa-star"></i> Platform Features</div>
      <h2>Everything You Need to Succeed</h2>
      <p style="max-width:500px;margin:0 auto;">Built for professionals who take their craft seriously.</p>
    </div>
    <div class="features-grid">
      <div class="feature-card">
        <div class="feature-icon"><i class="fas fa-shield-alt"></i></div>
        <h3>Skill Verification</h3>
        <p>Every skill is verified through rigorous MCQ tests. Pass >=60% to earn your verification badge.</p>
      </div>
      <div class="feature-card">
        <div class="feature-icon"><i class="fas fa-search-dollar"></i></div>
        <h3>Smart Project Matching</h3>
        <p>Filter projects by skill, budget, and deadline. Apply only to opportunities that match your expertise.</p>
      </div>
      <div class="feature-card">
        <div class="feature-icon"><i class="fas fa-bell"></i></div>
        <h3>Real-Time Notifications</h3>
        <p>Stay updated on application status changes, new projects, and platform announcements instantly.</p>
      </div>
      <div class="feature-card">
        <div class="feature-icon"><i class="fas fa-store"></i></div>
        <h3>Open Marketplace</h3>
        <p>Clients post projects with budgets and deadlines. Browse like a marketplace. Apply in one click.</p>
      </div>
      <div class="feature-card">
        <div class="feature-icon"><i class="fas fa-users-cog"></i></div>
        <h3>Applicant Management</h3>
        <p>Clients get a full dashboard to review applicants, view their verified skills, and accept or reject.</p>
      </div>
      <div class="feature-card">
        <div class="feature-icon"><i class="fas fa-user-check"></i></div>
        <h3>Dual Role Accounts</h3>
        <p>One account to rule them all. Be a freelancer, a client, or both and switch roles effortlessly.</p>
      </div>
    </div>
  </div>
</section>

<section class="how-section" id="how">
  <div class="container">
    <div class="text-center">
      <div class="hero-badge" style="margin:0 auto 1rem;"><i class="fas fa-map-signs"></i> Process</div>
      <h2>How SkillForge Works</h2>
    </div>
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:4rem;max-width:900px;margin:3rem auto 0;flex-wrap:wrap;">
      <div>
        <h3 style="color:var(--gold);margin-bottom:1.5rem;"><i class="fas fa-user-tie"></i> For Freelancers</h3>
        <div class="steps-list" style="margin:0;">
          <div class="step-item"><div class="step-num">1</div><div><strong>Register</strong><p style="margin:0;font-size:0.87rem;">Create your account in under a minute.</p></div></div>
          <div class="step-item"><div class="step-num">2</div><div><strong>Enlist Skills</strong><p style="margin:0;font-size:0.87rem;">Choose from 10+ professional skill tracks.</p></div></div>
          <div class="step-item"><div class="step-num">3</div><div><strong>Take Tests</strong><p style="margin:0;font-size:0.87rem;">Prove your expertise. Score >=60% to verify.</p></div></div>
          <div class="step-item"><div class="step-num">4</div><div><strong>Apply & Earn</strong><p style="margin:0;font-size:0.87rem;">Browse open projects and apply with confidence.</p></div></div>
        </div>
      </div>
      <div>
        <h3 style="color:var(--accent);margin-bottom:1.5rem;"><i class="fas fa-building"></i> For Clients</h3>
        <div class="steps-list" style="margin:0;">
          <div class="step-item"><div class="step-num">1</div><div><strong>Register as Client</strong><p style="margin:0;font-size:0.87rem;">Set up your client account instantly.</p></div></div>
          <div class="step-item"><div class="step-num">2</div><div><strong>Post a Project</strong><p style="margin:0;font-size:0.87rem;">Define your requirements, budget & deadline.</p></div></div>
          <div class="step-item"><div class="step-num">3</div><div><strong>Review Applicants</strong><p style="margin:0;font-size:0.87rem;">See verified skills and cover letters.</p></div></div>
          <div class="step-item"><div class="step-num">4</div><div><strong>Accept & Collaborate</strong><p style="margin:0;font-size:0.87rem;">Choose the best talent and get things done.</p></div></div>
        </div>
      </div>
    </div>
  </div>
</section>

<section class="cta-section">
  <div class="container">
    <div class="cta-box">
      <h2>Ready to Forge Your Future?</h2>
      <p style="margin: 1rem 0 2rem;">Join hundreds of skilled professionals and growing businesses on SkillForge.</p>
      <a href="register.php" class="btn btn-gold btn-lg"><i class="fas fa-bolt"></i> Create Free Account</a>
    </div>
  </div>
</section>

<footer>
  <p>&copy; <?= date('Y') ?> <strong>SkillForge</strong> - Online Skills Marketplace &nbsp;|&nbsp; Built with PHP + MySQL + Vanilla JS</p>
</footer>

<script>
  window.SKILLFORGE_CONFIG = {
    appUrl: <?= json_encode(APP_URL) ?>
  };
</script>
</body>
</html>
