// ============================================================
//  SkillForge - Main JavaScript
//  File: js/app.js
//  Handles: AJAX calls, UI rendering, modals, quiz logic
// ============================================================

const APP_URL =
  window.SKILLFORGE_CONFIG?.appUrl ||
  document.body?.dataset?.appUrl ||
  `${window.location.origin}${window.location.pathname.replace(/\/[^/]*$/, '')}`;

/* ─────────────────────────────────────────────────────────
   TOAST NOTIFICATIONS
───────────────────────────────────────────────────────── */
function showToast(message, type = 'success') {
  const icons = { success: 'check-circle', error: 'times-circle', warning: 'exclamation-triangle', info: 'info-circle' };
  const container = document.getElementById('toastContainer') || createToastContainer();
  const toast = document.createElement('div');
  toast.className = `toast ${type}`;
  toast.innerHTML = `
    <i class="fas fa-${icons[type] || 'info-circle'} toast-icon"></i>
    <span class="toast-msg">${message}</span>
  `;
  container.appendChild(toast);
  setTimeout(() => { toast.style.opacity = '0'; toast.style.transform = 'translateX(20px)'; setTimeout(() => toast.remove(), 300); }, 3500);
}

function createToastContainer() {
  const div = document.createElement('div');
  div.id = 'toastContainer';
  div.className = 'toast-container';
  document.body.appendChild(div);
  return div;
}

/* ─────────────────────────────────────────────────────────
   AJAX HELPER
───────────────────────────────────────────────────────── */
async function apiCall(url, data = null, method = 'POST') {
  try {
    const opts = { method };
    if (data) {
      if (data instanceof FormData) {
        opts.body = data;
      } else {
        opts.headers = { 'Content-Type': 'application/x-www-form-urlencoded' };
        opts.body = new URLSearchParams(data).toString();
      }
    }
    const res = await fetch(url, opts);
    return await res.json();
  } catch (err) {
    console.error('API Error:', err);
    return { success: false, message: 'Network error. Please try again.' };
  }
}

/* ─────────────────────────────────────────────────────────
   MODAL HELPERS
───────────────────────────────────────────────────────── */
function openModal(id) { document.getElementById(id)?.classList.remove('hidden'); }
function closeModal(id) { document.getElementById(id)?.classList.add('hidden'); }

function createModal(id, title, bodyHTML, footerHTML = '', size = '') {
  const existing = document.getElementById(id);
  if (existing) existing.remove();

  const overlay = document.createElement('div');
  overlay.className = 'modal-overlay';
  overlay.id = id;
  overlay.innerHTML = `
    <div class="modal ${size}">
      <div class="modal-header">
        <h3>${title}</h3>
        <button class="modal-close" onclick="document.getElementById('${id}').remove()"><i class="fas fa-times"></i></button>
      </div>
      <div class="modal-body">${bodyHTML}</div>
      ${footerHTML ? `<div class="modal-footer">${footerHTML}</div>` : ''}
    </div>
  `;
  overlay.addEventListener('click', e => { if (e.target === overlay) overlay.remove(); });
  document.body.appendChild(overlay);
  return overlay;
}

/* ─────────────────────────────────────────────────────────
   AUTH: REGISTER & LOGIN
───────────────────────────────────────────────────────── */
async function handleRegister(e) {
  e.preventDefault();
  const btn = e.target.querySelector('button[type="submit"]');
  const origText = btn.innerHTML;
  btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Creating account...';
  btn.disabled = true;

  const data = Object.fromEntries(new FormData(e.target));
  data.action = 'register';

  const res = await apiCall(`${APP_URL}/php/auth.php`, data);
  btn.innerHTML = origText;
  btn.disabled = false;

  showToast(res.message, res.success ? 'success' : 'error');
  if (res.success) {
    setTimeout(() => { window.location.href = `${APP_URL}/login.php`; }, 1500);
  }

  return false;
}

async function handleLogin(e) {
  e.preventDefault();
  const btn = e.target.querySelector('button[type="submit"]');
  btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Logging in...';
  btn.disabled = true;

  const data = Object.fromEntries(new FormData(e.target));
  data.action = 'login';

  const res = await apiCall(`${APP_URL}/php/auth.php`, data);
  btn.innerHTML = '<i class="fas fa-sign-in-alt"></i> Sign In';
  btn.disabled = false;

  showToast(res.message, res.success ? 'success' : 'error');
  if (res.success) {
    setTimeout(() => { window.location.href = res.redirect; }, 1000);
  }

  return false;
}

async function handleLogout() {
  const res = await apiCall(`${APP_URL}/php/auth.php`, { action: 'logout' });
  if (res.success) window.location.href = res.redirect || `${APP_URL}/index.php`;
}

/* ─────────────────────────────────────────────────────────
   DASHBOARD STATS
───────────────────────────────────────────────────────── */
async function loadDashboardStats() {
  const res = await apiCall(`${APP_URL}/php/user.php?action=dashboard_stats`, null, 'GET');
  if (!res.success) return;
  const s = res.stats;

  const setEl = (id, val) => { const el = document.getElementById(id); if (el) el.textContent = val; };
  setEl('statTotalSkills',   s.total_skills);
  setEl('statVerified',      s.verified_skills);
  setEl('statApplied',       s.total_applications);
  setEl('statAccepted',      s.accepted);
  setEl('statProjects',      s.total_projects);
  setEl('statOpenProjects',  s.open_projects);
  setEl('statReceived',      s.total_received);
}

/* ─────────────────────────────────────────────────────────
   SKILLS MODULE
───────────────────────────────────────────────────────── */
let allSkills = [];

async function loadAllSkills() {
  const res = await apiCall(`${APP_URL}/php/skills.php?action=get_all_skills`, null, 'GET');
  if (!res.success) return;
  allSkills = res.skills;
  renderSkillsMarketplace(allSkills);
  populateSkillSelect(allSkills);
}

async function loadUserSkills() {
  const res = await apiCall(`${APP_URL}/php/skills.php?action=get_user_skills`, null, 'GET');
  if (!res.success) return;
  renderUserSkills(res.skills);
}

function renderSkillsMarketplace(skills) {
  const container = document.getElementById('skillsMarketplace');
  if (!container) return;

  if (!skills.length) {
    container.innerHTML = emptyState('layer-group', 'No Skills Found', 'Skills will appear here.');
    return;
  }
  container.innerHTML = skills.map(s => `
    <div class="skill-card" onclick="enlistSkill(${s.skill_id}, '${escHtml(s.skill_name)}')">
      <div class="skill-icon-wrap"><i class="fas fa-${s.icon || 'code'}"></i></div>
      <h4>${escHtml(s.skill_name)}</h4>
      <div class="category">${escHtml(s.category || '')}</div>
      <div class="badge badge-open">+ Enlist</div>
    </div>
  `).join('');
}

function renderUserSkills(skills) {
  const container = document.getElementById('userSkillsList');
  if (!container) return;

  if (!skills.length) {
    container.innerHTML = emptyState('award', 'No Skills Enlisted', 'Browse the marketplace and enlist your skills.');
    return;
  }
  container.innerHTML = skills.map(s => {
    const badgeClass = s.verification_status === 'Verified' ? 'badge-verified' : s.verification_status === 'Failed' ? 'badge-failed' : 'badge-pending';
    const verIcon = s.verification_status === 'Verified' ? 'check-circle' : s.verification_status === 'Failed' ? 'times-circle' : 'clock';
    const canTest = s.verification_status !== 'Verified';
    return `
    <div class="card mb-2" style="display:flex;align-items:center;justify-content:space-between;gap:1rem;flex-wrap:wrap;">
      <div style="display:flex;align-items:center;gap:1rem;">
        <div class="skill-icon-wrap" style="width:44px;height:44px;margin:0;border-radius:10px;font-size:1.1rem;">
          <i class="fas fa-${s.icon || 'code'}"></i>
        </div>
        <div>
          <h4 style="margin-bottom:0.2rem;">${escHtml(s.skill_name)}</h4>
          <div class="text-muted" style="font-size:0.78rem;">${escHtml(s.category || '')}</div>
        </div>
      </div>
      <div style="display:flex;align-items:center;gap:0.75rem;">
        <span class="badge ${badgeClass}"><i class="fas fa-${verIcon}"></i> ${s.verification_status}</span>
        ${canTest ? `<button class="btn btn-gold btn-sm" onclick="openSkillTest(${s.skill_id}, '${escHtml(s.skill_name)}')">
          <i class="fas fa-pen-to-square"></i> Take Test</button>` : ''}
      </div>
    </div>`;
  }).join('');
}

async function enlistSkill(skillId, skillName) {
  const confirmed = await confirmDialog(`Enlist <strong>${skillName}</strong>?`, "You'll need to take a verification test to get certified in this skill.", 'Enlist Skill', 'btn-gold');
  if (!confirmed) return;

  const res = await apiCall(`${APP_URL}/php/skills.php`, { action: 'enlist', skill_id: skillId });
  showToast(res.message, res.success ? 'success' : 'error');
  if (res.success) { loadUserSkills(); loadDashboardStats(); }
}

function populateSkillSelect(skills) {
  const selects = document.querySelectorAll('.skill-filter-select, #projectSkillId');
  selects.forEach(sel => {
    sel.innerHTML = '<option value="">All Skills / Any</option>' + skills.map(s => `<option value="${s.skill_id}">${escHtml(s.skill_name)}</option>`).join('');
  });
}

/* ─────────────────────────────────────────────────────────
   SKILL TEST / QUIZ
───────────────────────────────────────────────────────── */
let quizState = { skillId: 0, testId: 0, questions: [], current: 0, answers: {}, timer: null, seconds: 0 };

async function openSkillTest(skillId, skillName) {
  const loadingModal = createModal('quizLoadingModal', '⏳ Loading Test', '<div class="spinner"></div><p class="text-center text-muted">Preparing your test...</p>');
  const res = await apiCall(`${APP_URL}/php/skills.php?action=get_test&skill_id=${skillId}`, null, 'GET');
  document.getElementById('quizLoadingModal')?.remove();

  if (!res.success) { showToast(res.message, 'error'); return; }

  quizState = { skillId, testId: res.test.test_id, questions: res.questions, current: 0, answers: {}, timer: null, seconds: res.test.time_limit * 60 };

  renderQuizModal(res.test, skillName);
}

function renderQuizModal(test, skillName) {
  const bodyHTML = `
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:1rem;flex-wrap:wrap;gap:0.5rem;">
      <div>
        <div style="font-size:0.78rem;color:var(--text-3);margin-bottom:0.25rem;">Skill Test</div>
        <strong>${escHtml(skillName)}</strong>
        <span class="badge badge-pending" style="margin-left:0.5rem;">Pass: ${test.passing_marks}/${test.total_marks}</span>
      </div>
      <div class="quiz-timer" id="quizTimer"><i class="fas fa-clock"></i> <span id="timerDisplay">${formatTime(quizState.seconds)}</span></div>
    </div>
    <div class="quiz-progress-bar"><div class="quiz-progress-fill" id="quizProgress" style="width:10%"></div></div>
    <div id="quizQuestionContainer"></div>
    <div style="display:flex;justify-content:space-between;align-items:center;margin-top:1.5rem;gap:0.75rem;">
      <button class="btn btn-outline" id="quizPrev" onclick="quizNav(-1)" disabled><i class="fas fa-chevron-left"></i> Prev</button>
      <span id="quizCounter" style="font-size:0.85rem;color:var(--text-3);">1 / ${quizState.questions.length}</span>
      <button class="btn btn-gold" id="quizNext" onclick="quizNav(1)">Next <i class="fas fa-chevron-right"></i></button>
    </div>
  `;
  createModal('quizModal', '📝 Skill Verification Test', bodyHTML, '', 'modal-lg');
  quizRenderQuestion();
  startTimer();
}

function quizRenderQuestion() {
  const q = quizState.questions[quizState.current];
  const total = quizState.questions.length;
  const isLast = quizState.current === total - 1;

  document.getElementById('quizProgress').style.width = `${((quizState.current + 1) / total) * 100}%`;
  document.getElementById('quizCounter').textContent = `${quizState.current + 1} / ${total}`;
  document.getElementById('quizPrev').disabled = quizState.current === 0;
  document.getElementById('quizNext').innerHTML = isLast
    ? '<i class="fas fa-paper-plane"></i> Submit Test'
    : 'Next <i class="fas fa-chevron-right"></i>';
  document.getElementById('quizNext').onclick = isLast ? submitQuiz : () => quizNav(1);

  const options = [
    { letter: 'A', text: q.option_a },
    { letter: 'B', text: q.option_b },
    { letter: 'C', text: q.option_c },
    { letter: 'D', text: q.option_d },
  ];

  document.getElementById('quizQuestionContainer').innerHTML = `
    <div class="quiz-question">Q${quizState.current + 1}. ${escHtml(q.question_text)}</div>
    <div class="quiz-options">
      ${options.map(opt => `
        <button class="option-btn ${quizState.answers[q.question_id] === opt.letter ? 'selected' : ''}"
          onclick="selectAnswer(${q.question_id}, '${opt.letter}', this)">
          <span class="option-letter">${opt.letter}</span>
          ${escHtml(opt.text)}
        </button>
      `).join('')}
    </div>
  `;
}

function selectAnswer(qId, letter, btn) {
  quizState.answers[qId] = letter;
  btn.closest('.quiz-options').querySelectorAll('.option-btn').forEach(b => b.classList.remove('selected'));
  btn.classList.add('selected');
}

function quizNav(dir) {
  const newIdx = quizState.current + dir;
  if (newIdx >= 0 && newIdx < quizState.questions.length) {
    quizState.current = newIdx;
    quizRenderQuestion();
  }
}

function startTimer() {
  clearInterval(quizState.timer);
  quizState.timer = setInterval(() => {
    quizState.seconds--;
    const display = document.getElementById('timerDisplay');
    const timerEl = document.getElementById('quizTimer');
    if (display) display.textContent = formatTime(quizState.seconds);
    if (timerEl && quizState.seconds < 120) timerEl.classList.add('danger');
    if (quizState.seconds <= 0) { clearInterval(quizState.timer); submitQuiz(); }
  }, 1000);
}

function formatTime(seconds) {
  const m = Math.floor(seconds / 60).toString().padStart(2, '0');
  const s = (seconds % 60).toString().padStart(2, '0');
  return `${m}:${s}`;
}

async function submitQuiz() {
  clearInterval(quizState.timer);
  const unanswered = quizState.questions.length - Object.keys(quizState.answers).length;
  if (unanswered > 0) {
    const go = await confirmDialog('Unanswered Questions', `You have ${unanswered} unanswered question(s). Submit anyway?`, 'Yes, Submit', 'btn-gold');
    if (!go) return;
  }
  document.getElementById('quizModal')?.remove();

  const formData = new FormData();
  formData.append('action', 'submit_test');
  formData.append('skill_id', quizState.skillId);
  for (const [qId, ans] of Object.entries(quizState.answers)) {
    formData.append(`answers[${qId}]`, ans);
  }

  const loadingModal = createModal('quizSubmitModal', '⏳ Submitting', '<div class="spinner"></div><p class="text-center text-muted">Calculating your score...</p>');
  const res = await apiCall(`${APP_URL}/php/skills.php`, formData);
  document.getElementById('quizSubmitModal')?.remove();

  if (!res.success) {
    showToast(res.message || 'Unable to calculate test result right now.', 'error');
    loadUserSkills();
    return;
  }

  // Show result
  const isPass = res.status === 'Pass';
  const bodyHTML = `
    <div class="text-center" style="padding:1rem 0">
      <div style="font-size:3.5rem;margin-bottom:1rem;">${isPass ? '🎉' : '😢'}</div>
      <h2 style="margin-bottom:0.5rem;color:${isPass ? 'var(--green)' : 'var(--red)'}">
        ${isPass ? 'Congratulations!' : 'Better Luck Next Time'}
      </h2>
      <div style="font-size:3rem;font-family:var(--font-head);font-weight:700;margin:1rem 0;">
        <span style="color:${isPass ? 'var(--green)' : 'var(--red)'};">${res.score}</span>
        <span style="color:var(--text-3);font-size:1.5rem;"> / ${res.total}</span>
      </div>
      <p style="margin-bottom:0.5rem;">${res.message}</p>
      <span class="badge ${isPass ? 'badge-verified' : 'badge-failed'}" style="font-size:0.85rem;padding:0.4rem 1rem;">
        <i class="fas fa-${isPass ? 'check-circle' : 'times-circle'}"></i> 
        Skill ${isPass ? 'Verified' : 'Not Verified'}
      </span>
    </div>
  `;
  createModal('quizResultModal', 'Test Result', bodyHTML, `<button class="btn btn-gold" onclick="document.getElementById('quizResultModal').remove();loadUserSkills();loadDashboardStats();">Done</button>`);
}

/* ─────────────────────────────────────────────────────────
   PROJECTS MODULE
───────────────────────────────────────────────────────── */
async function loadProjects(filters = {}) {
  const container = document.getElementById('projectsContainer');
  if (!container) return;
  container.innerHTML = '<div class="spinner"></div>';

  const params = new URLSearchParams({ action: 'get_projects', ...filters }).toString();
  const res = await apiCall(`${APP_URL}/php/projects.php?${params}`, null, 'GET');
  if (!res.success) { container.innerHTML = emptyState('exclamation-triangle', 'Error', res.message); return; }
  renderProjects(res.projects, container);
}

function renderProjects(projects, container) {
  if (!projects.length) {
    container.innerHTML = emptyState('folder-open', 'No Projects Found', 'Try adjusting your filters.');
    return;
  }
  container.innerHTML = `<div class="projects-grid">${projects.map(p => projectCard(p)).join('')}</div>`;
}

function projectCard(p) {
  const budgetFmt = parseFloat(p.budget).toLocaleString('en-US', { style: 'currency', currency: 'USD', maximumFractionDigits: 0 });
  const deadline  = new Date(p.deadline).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
  const statusBadge = p.status === 'Open' ? 'badge-open' : 'badge-pending';
  const myApp = p.my_application;
  const appBadge = myApp ? `<span class="badge badge-${myApp.status.toLowerCase()}">${myApp.status}</span>` : '';

  return `
  <div class="project-card" onclick="openProjectDetail(${p.project_id})">
    <div class="project-card-header">
      <div>
        <h3>${escHtml(p.title)}</h3>
        <div class="text-muted" style="font-size:0.78rem;margin-top:0.2rem;">by ${escHtml(p.client_name)}</div>
      </div>
      <span class="badge ${statusBadge}">${p.status}</span>
    </div>
    <p>${escHtml(p.description)}</p>
    <div class="project-meta">
      ${p.required_skill ? `<span class="meta-item"><i class="fas fa-tag"></i> ${escHtml(p.required_skill)}</span>` : ''}
      <span class="meta-item"><i class="fas fa-calendar"></i> ${deadline}</span>
      <span class="meta-item"><i class="fas fa-users"></i> ${p.app_count} applicants</span>
    </div>
    <div class="project-footer">
      <div class="project-budget">${budgetFmt}</div>
      <div>${appBadge || '<span class="btn btn-outline btn-sm">View Details</span>'}</div>
    </div>
  </div>`;
}

async function openProjectDetail(projectId) {
  const loadModal = createModal('projLoadModal', 'Loading...', '<div class="spinner"></div>');
  const res = await apiCall(`${APP_URL}/php/projects.php?action=get_project&project_id=${projectId}`, null, 'GET');
  document.getElementById('projLoadModal')?.remove();
  if (!res.success) { showToast(res.message, 'error'); return; }

  const p = res.project;
  const budget = parseFloat(p.budget).toLocaleString('en-US', { style: 'currency', currency: 'USD' });
  const deadline = new Date(p.deadline).toLocaleDateString('en-US', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' });

  const bodyHTML = `
    <div style="display:flex;gap:0.75rem;margin-bottom:1rem;flex-wrap:wrap;">
      <span class="badge ${p.status === 'Open' ? 'badge-open' : 'badge-pending'}">${p.status}</span>
      ${p.required_skill ? `<span class="badge badge-gold-role"><i class="fas fa-tag"></i> ${escHtml(p.required_skill)}</span>` : ''}
    </div>
    <h2 style="margin-bottom:0.5rem;">${escHtml(p.title)}</h2>
    <p style="font-size:0.82rem;color:var(--text-3);margin-bottom:1.5rem;">Posted by <strong style="color:var(--text-2);">${escHtml(p.client_name)}</strong></p>
    <div style="background:var(--bg-darkest);border-radius:var(--radius-sm);padding:1rem;margin-bottom:1.5rem;">
      <p style="white-space:pre-wrap;line-height:1.7;">${escHtml(p.description)}</p>
    </div>
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;margin-bottom:1.5rem;">
      <div class="card" style="padding:1rem;text-align:center;">
        <div class="text-muted" style="font-size:0.75rem;">Budget</div>
        <div style="font-size:1.4rem;font-weight:700;color:var(--gold);font-family:var(--font-head);">${budget}</div>
      </div>
      <div class="card" style="padding:1rem;text-align:center;">
        <div class="text-muted" style="font-size:0.75rem;">Deadline</div>
        <div style="font-size:0.9rem;font-weight:600;">${deadline}</div>
      </div>
    </div>
    ${p.status === 'Open' ? `
    <div class="form-group">
      <label class="form-label">Cover Letter (Optional)</label>
      <textarea class="form-control" id="coverLetter" rows="4" placeholder="Tell the client why you're the perfect fit..."></textarea>
    </div>` : ''}
  `;

  const footerHTML = p.status === 'Open'
    ? `<button class="btn btn-outline" onclick="document.getElementById('projDetailModal').remove()">Cancel</button>
       <button class="btn btn-gold" onclick="applyToProject(${projectId})"><i class="fas fa-paper-plane"></i> Apply Now</button>`
    : `<button class="btn btn-gold" onclick="document.getElementById('projDetailModal').remove()">Close</button>`;

  createModal('projDetailModal', '📋 Project Details', bodyHTML, footerHTML, 'modal-lg');
}

async function applyToProject(projectId) {
  const coverLetter = document.getElementById('coverLetter')?.value || '';
  document.getElementById('projDetailModal')?.remove();

  const res = await apiCall(`${APP_URL}/php/projects.php`, { action: 'apply', project_id: projectId, cover_letter: coverLetter });
  showToast(res.message, res.success ? 'success' : 'error');
  if (res.success) { loadProjects(); loadDashboardStats(); }
}

/* ─────────────────────────────────────────────────────────
   POST PROJECT
───────────────────────────────────────────────────────── */
function openPostProjectModal() {
  const bodyHTML = `
    <div class="form-group">
      <label class="form-label">Project Title *</label>
      <input type="text" class="form-control" id="pTitle" placeholder="e.g. Build a React dashboard" maxlength="200">
    </div>
    <div class="form-group">
      <label class="form-label">Description *</label>
      <textarea class="form-control" id="pDesc" rows="5" placeholder="Describe your project requirements in detail..."></textarea>
    </div>
    <div class="form-row">
      <div class="form-group">
        <label class="form-label">Budget (USD) *</label>
        <input type="number" class="form-control" id="pBudget" placeholder="500" min="1">
      </div>
      <div class="form-group">
        <label class="form-label">Deadline *</label>
        <input type="date" class="form-control" id="pDeadline" min="${new Date().toISOString().split('T')[0]}">
      </div>
    </div>
    <div class="form-group">
      <label class="form-label">Required Skill (Optional)</label>
      <select class="form-control" id="pSkill">
        <option value="">Any Skill</option>
      </select>
    </div>
  `;
  createModal('postProjectModal', '📤 Post a New Project', bodyHTML, `
    <button class="btn btn-outline" onclick="document.getElementById('postProjectModal').remove()">Cancel</button>
    <button class="btn btn-gold" onclick="submitProject()"><i class="fas fa-rocket"></i> Post Project</button>
  `, 'modal-lg');

  // Populate skill select
  const sel = document.getElementById('pSkill');
  allSkills.forEach(s => { const opt = new Option(s.skill_name, s.skill_id); sel.add(opt); });
}

async function submitProject() {
  const data = {
    action:      'post_project',
    title:       document.getElementById('pTitle').value,
    description: document.getElementById('pDesc').value,
    budget:      document.getElementById('pBudget').value,
    deadline:    document.getElementById('pDeadline').value,
    skill_id:    document.getElementById('pSkill').value,
  };

  if (!data.title || !data.description || !data.budget || !data.deadline) {
    showToast('Please fill all required fields.', 'error'); return;
  }

  const res = await apiCall(`${APP_URL}/php/projects.php`, data);
  showToast(res.message, res.success ? 'success' : 'error');
  if (res.success) { document.getElementById('postProjectModal')?.remove(); loadMyProjects(); loadDashboardStats(); }
}

/* ─────────────────────────────────────────────────────────
   MY PROJECTS & APPLICATIONS (Client/Freelancer)
───────────────────────────────────────────────────────── */
async function loadMyProjects() {
  const container = document.getElementById('myProjectsContainer');
  if (!container) return;
  container.innerHTML = '<div class="spinner"></div>';

  const res = await apiCall(`${APP_URL}/php/projects.php?action=my_projects`, null, 'GET');
  if (!res.success || !res.projects.length) {
    container.innerHTML = emptyState('folder-plus', 'No Projects Yet', 'Post your first project to find talented freelancers.'); return;
  }
  container.innerHTML = `
    <div class="table-wrapper">
      <table>
        <thead><tr><th>Project</th><th>Budget</th><th>Deadline</th><th>Applicants</th><th>Status</th><th>Actions</th></tr></thead>
        <tbody>${res.projects.map(p => {
          const budget = parseFloat(p.budget).toLocaleString('en-US', { style: 'currency', currency: 'USD', maximumFractionDigits: 0 });
          const dl = new Date(p.deadline).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
          return `<tr>
            <td><strong style="color:var(--text-1);">${escHtml(p.title)}</strong>${p.skill_name ? `<br><span class="text-muted" style="font-size:0.75rem;">${escHtml(p.skill_name)}</span>` : ''}</td>
            <td class="text-gold fw-600">${budget}</td>
            <td>${dl}</td>
            <td><span class="badge badge-open">${p.app_count}</span></td>
            <td><span class="badge ${p.status === 'Open' ? 'badge-open' : 'badge-pending'}">${p.status}</span></td>
            <td style="display:flex;gap:0.5rem;flex-wrap:wrap;">
              <button class="btn btn-outline btn-sm" onclick="viewApplicants(${p.project_id})"><i class="fas fa-users"></i></button>
              <select class="form-control" style="padding:0.3rem 0.5rem;font-size:0.78rem;width:auto;" onchange="updateProjStatus(${p.project_id}, this.value)">
                <option ${p.status==='Open'?'selected':''} value="Open">Open</option>
                <option ${p.status==='In Progress'?'selected':''} value="In Progress">In Progress</option>
                <option ${p.status==='Closed'?'selected':''} value="Closed">Closed</option>
              </select>
            </td>
          </tr>`;
        }).join('')}</tbody>
      </table>
    </div>`;
}

async function viewApplicants(projectId) {
  const loadModal = createModal('appsLoadModal', 'Loading Applicants...', '<div class="spinner"></div>');
  const res = await apiCall(`${APP_URL}/php/projects.php?action=get_applicants&project_id=${projectId}`, null, 'GET');
  document.getElementById('appsLoadModal')?.remove();

  if (!res.success) { showToast(res.message, 'error'); return; }
  const apps = res.applicants;

  const bodyHTML = apps.length === 0
    ? emptyState('user-slash', 'No Applicants Yet', 'Applications will appear here.')
    : `<div class="table-wrapper"><table>
        <thead><tr><th>Name</th><th>Verified Skills</th><th>Status</th><th>Actions</th></tr></thead>
        <tbody>${apps.map(a => `
          <tr>
            <td><strong style="color:var(--text-1);">${escHtml(a.name)}</strong><br><span class="text-muted" style="font-size:0.75rem;">${escHtml(a.email)}</span></td>
            <td>${a.verified_skills ? `<span class="badge badge-verified" style="font-size:0.72rem;">${escHtml(a.verified_skills)}</span>` : '<span class="text-muted">—</span>'}</td>
            <td><span class="badge badge-${a.status.toLowerCase()}">${a.status}</span></td>
            <td style="display:flex;gap:0.4rem;">
              ${a.status === 'Applied' ? `
                <button class="btn btn-success btn-sm" onclick="updateAppStatus(${a.application_id}, 'Accepted')"><i class="fas fa-check"></i></button>
                <button class="btn btn-danger btn-sm"  onclick="updateAppStatus(${a.application_id}, 'Rejected')"><i class="fas fa-times"></i></button>
              ` : '—'}
            </td>
          </tr>`).join('')}
        </tbody></table></div>`;

  createModal('applicantsModal', `👥 Applicants`, bodyHTML, `<button class="btn btn-gold" onclick="document.getElementById('applicantsModal').remove()">Close</button>`, 'modal-lg');
}

async function updateAppStatus(appId, status) {
  const res = await apiCall(`${APP_URL}/php/projects.php`, { action: 'update_app_status', application_id: appId, status });
  showToast(res.message, res.success ? 'success' : 'error');
  if (res.success) { document.getElementById('applicantsModal')?.remove(); }
}

async function updateProjStatus(projectId, status) {
  const res = await apiCall(`${APP_URL}/php/projects.php`, { action: 'update_project_status', project_id: projectId, status });
  showToast(res.message, res.success ? 'success' : 'error');
}

async function loadMyApplications() {
  const container = document.getElementById('myApplicationsContainer');
  if (!container) return;
  container.innerHTML = '<div class="spinner"></div>';

  const res = await apiCall(`${APP_URL}/php/projects.php?action=my_applications`, null, 'GET');
  if (!res.success || !res.applications.length) {
    container.innerHTML = emptyState('file-alt', 'No Applications Yet', 'Browse projects and apply to start working.'); return;
  }
  container.innerHTML = `
    <div class="table-wrapper">
      <table>
        <thead><tr><th>Project</th><th>Client</th><th>Budget</th><th>Status</th><th>Applied</th></tr></thead>
        <tbody>${res.applications.map(a => {
          const budget = parseFloat(a.budget).toLocaleString('en-US', { style: 'currency', currency: 'USD', maximumFractionDigits: 0 });
          const date = new Date(a.applied_at).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
          return `<tr>
            <td><strong style="color:var(--text-1);">${escHtml(a.project_title)}</strong></td>
            <td>${escHtml(a.client_name)}</td>
            <td class="text-gold">${budget}</td>
            <td><span class="badge badge-${a.status.toLowerCase()}">${a.status}</span></td>
            <td class="text-muted">${date}</td>
          </tr>`;
        }).join('')}</tbody>
      </table>
    </div>`;
}

/* ─────────────────────────────────────────────────────────
   NOTIFICATIONS
───────────────────────────────────────────────────────── */
async function loadNotifications() {
  const res = await apiCall(`${APP_URL}/php/user.php?action=get_notifications`, null, 'GET');
  if (!res.success) return;

  const badge = document.getElementById('notifBadge');
  if (badge) {
    badge.textContent = res.unread_count;
    badge.style.display = res.unread_count > 0 ? 'grid' : 'none';
  }

  const panel = document.getElementById('notifPanel');
  if (!panel) return;
  if (!res.notifications.length) {
    panel.querySelector('.notif-list').innerHTML = '<p class="text-muted text-center" style="padding:1rem;">No notifications yet.</p>';
    return;
  }
  panel.querySelector('.notif-list').innerHTML = res.notifications.map(n => {
    const time = new Date(n.created_at).toLocaleDateString('en-US', { month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' });
    return `<div class="notif-item ${!n.is_read ? 'unread' : ''} ${n.type}">
      <div>${escHtml(n.message)}</div>
      <div class="notif-time">${time}</div>
    </div>`;
  }).join('');
}

function toggleNotifPanel() {
  const panel = document.getElementById('notifPanel');
  panel?.classList.toggle('open');
  if (panel?.classList.contains('open')) {
    apiCall(`${APP_URL}/php/user.php`, { action: 'mark_read' });
    setTimeout(() => { const b = document.getElementById('notifBadge'); if (b) b.style.display = 'none'; }, 500);
  }
}

/* ─────────────────────────────────────────────────────────
   PROFILE
───────────────────────────────────────────────────────── */
async function loadProfile() {
  const res = await apiCall(`${APP_URL}/php/user.php?action=get_profile`, null, 'GET');
  if (!res.success) return;
  const u = res.user;
  const s = res.stats;

  const nameEl = document.getElementById('profileName');
  const emailEl = document.getElementById('profileEmail');
  const roleEl = document.getElementById('profileRole');
  const bioEl = document.getElementById('profileBio');

  if (nameEl)  nameEl.textContent  = u.name;
  if (emailEl) emailEl.textContent = u.email;
  if (roleEl)  roleEl.innerHTML    = `<span class="badge badge-gold-role">${u.role}</span>`;
  if (bioEl)   bioEl.textContent   = u.bio || 'No bio yet.';

  // Fill form fields
  const fName = document.getElementById('editName');
  const fBio  = document.getElementById('editBio');
  const fRole = document.getElementById('editRole');
  if (fName) fName.value = u.name;
  if (fBio)  fBio.value  = u.bio || '';
  if (fRole) fRole.value = u.role;

  // Stats
  const setEl = (id, v) => { const el = document.getElementById(id); if (el) el.textContent = v; };
  setEl('pStatVerified',  s.verified_skills);
  setEl('pStatWon',       s.projects_won);
  setEl('pStatPosted',    s.projects_posted);
}

async function saveProfile(e) {
  e.preventDefault();
  const formData = new FormData(e.target);
  formData.append('action', 'update_profile');
  const res = await apiCall(`${APP_URL}/php/user.php`, formData);
  showToast(res.message, res.success ? 'success' : 'error');
  if (res.success) {
    loadProfile();
    setTimeout(() => window.location.reload(), 500);
  }
}

async function changePassword(e) {
  e.preventDefault();
  const data = Object.fromEntries(new FormData(e.target));
  data.action = 'change_password';
  const res = await apiCall(`${APP_URL}/php/user.php`, data);
  showToast(res.message, res.success ? 'success' : 'error');
  if (res.success) e.target.reset();
}

/* ─────────────────────────────────────────────────────────
   TABS & NAVIGATION
───────────────────────────────────────────────────────── */
function switchTab(tabId, groupId = 'mainTabs') {
  document.querySelectorAll(`[data-tab-group="${groupId}"] .tab-content`).forEach(c => c.classList.remove('active'));
  document.querySelectorAll(`[data-tab-group="${groupId}"] .tab-btn`).forEach(b => b.classList.remove('active'));
  document.getElementById(tabId)?.classList.add('active');
  document.querySelector(`[onclick*="${tabId}"]`)?.classList.add('active');
}

function activateSidebarLink(section) {
  document.querySelectorAll('.sidebar-link').forEach(l => l.classList.remove('active'));
  document.querySelectorAll('.page-section').forEach(s => s.classList.add('hidden'));
  const target = document.getElementById(`section-${section}`);
  if (target) target.classList.remove('hidden');
  document.querySelector(`[data-section="${section}"]`)?.classList.add('active');

  // Load data for section
  const loaders = {
    'skills':       () => { loadAllSkills(); loadUserSkills(); },
    'projects':     () => { loadProjects(); loadAllSkills(); },
    'my-projects':  () => { loadMyProjects(); loadAllSkills(); },
    'applications': () => loadMyApplications(),
    'profile':      () => loadProfile(),
    'dashboard':    () => loadDashboardStats(),
  };
  if (loaders[section]) loaders[section]();
}

/* ─────────────────────────────────────────────────────────
   UTILITIES
───────────────────────────────────────────────────────── */
function escHtml(str) {
  if (!str) return '';
  return String(str).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;').replace(/'/g, '&#39;');
}

function emptyState(icon, title, text) {
  return `<div class="empty-state"><i class="fas fa-${icon}"></i><h3>${title}</h3><p>${text}</p></div>`;
}

function confirmDialog(title, message, confirmText = 'Confirm', confirmClass = 'btn-primary') {
  return new Promise(resolve => {
    const bodyHTML = `<p style="color:var(--text-2);">${message}</p>`;
    const footerHTML = `
      <button class="btn btn-outline" onclick="this.closest('.modal-overlay').remove();window._resolveConfirm(false)">Cancel</button>
      <button class="btn ${confirmClass}" onclick="this.closest('.modal-overlay').remove();window._resolveConfirm(true)">${confirmText}</button>
    `;
    window._resolveConfirm = resolve;
    createModal('confirmModal', title, bodyHTML, footerHTML);
  });
}

/* ─────────────────────────────────────────────────────────
   SEARCH / FILTER
───────────────────────────────────────────────────────── */
function filterProjects() {
  const search   = document.getElementById('searchProjects')?.value || '';
  const skillId  = document.getElementById('filterSkill')?.value || '';
  const minBudget = document.getElementById('filterMinBudget')?.value || '';
  const maxBudget = document.getElementById('filterMaxBudget')?.value || '';
  loadProjects({ search, skill_id: skillId, min_budget: minBudget, max_budget: maxBudget, status: 'Open' });
}

/* ─────────────────────────────────────────────────────────
   INIT
───────────────────────────────────────────────────────── */
document.addEventListener('DOMContentLoaded', () => {
  // Init default section
  const defaultSection = document.querySelector('.sidebar-link.active')?.dataset?.section || 'dashboard';
  activateSidebarLink(defaultSection);

  // Load notifications
  loadNotifications();
  setInterval(loadNotifications, 60000); // refresh every minute

  // Search debounce
  let searchTimeout;
  document.getElementById('searchProjects')?.addEventListener('input', () => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(filterProjects, 400);
  });

  // Mobile sidebar toggle
  document.getElementById('menuToggle')?.addEventListener('click', () => {
    document.querySelector('.sidebar')?.classList.toggle('open');
  });

  // Close sidebar on outside click (mobile)
  document.addEventListener('click', e => {
    const sidebar = document.querySelector('.sidebar');
    const toggle  = document.getElementById('menuToggle');
    if (sidebar?.classList.contains('open') && !sidebar.contains(e.target) && e.target !== toggle) {
      sidebar.classList.remove('open');
    }
  });
});
