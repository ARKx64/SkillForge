<?php
// ============================================================
//  SkillForge - Projects Handler
//  File: php/projects.php
//  Handles: Post project, list projects, apply, manage apps
// ============================================================

require_once '../includes/config.php';
startSession();

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized.']);
    exit;
}

$userId = $_SESSION['user_id'];
$role   = $_SESSION['user_role'];
$action = $_POST['action'] ?? $_GET['action'] ?? '';

switch ($action) {

    // ── GET ALL PROJECTS (with search/filter) ─────────────
    case 'get_projects':
        $search   = sanitize($_GET['search']   ?? '');
        $skillId  = (int)($_GET['skill_id']   ?? 0);
        $statusF  = sanitize($_GET['status']   ?? 'Open');
        $minBudget= (float)($_GET['min_budget'] ?? 0);
        $maxBudget= (float)($_GET['max_budget'] ?? 0);

        $sql = "SELECT p.*, u.name AS client_name, u.avatar AS client_avatar,
                       s.skill_name AS required_skill,
                       (SELECT COUNT(*) FROM applications a WHERE a.project_id = p.project_id) AS app_count
                FROM projects p
                JOIN users u ON p.client_id = u.user_id
                LEFT JOIN skills s ON p.required_skill_id = s.skill_id
                WHERE 1=1";
        $params = [];

        if ($statusF) {
            $sql .= " AND p.status = ?";
            $params[] = $statusF;
        }
        if ($search) {
            $sql .= " AND (p.title LIKE ? OR p.description LIKE ?)";
            $params[] = "%$search%";
            $params[] = "%$search%";
        }
        if ($skillId) {
            $sql .= " AND p.required_skill_id = ?";
            $params[] = $skillId;
        }
        if ($minBudget > 0) {
            $sql .= " AND p.budget >= ?";
            $params[] = $minBudget;
        }
        if ($maxBudget > 0) {
            $sql .= " AND p.budget <= ?";
            $params[] = $maxBudget;
        }
        $sql .= " ORDER BY p.created_at DESC";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $projects = $stmt->fetchAll();

        // Check if current user applied to each
        foreach ($projects as &$proj) {
            $a = $pdo->prepare("SELECT status FROM applications WHERE project_id = ? AND user_id = ?");
            $a->execute([$proj['project_id'], $userId]);
            $proj['my_application'] = $a->fetch() ?: null;
        }

        echo json_encode(['success' => true, 'projects' => $projects]);
        break;


    // ── GET SINGLE PROJECT ────────────────────────────────
    case 'get_project':
        $projectId = (int)($_GET['project_id'] ?? 0);
        $stmt = $pdo->prepare("
            SELECT p.*, u.name AS client_name, u.avatar, s.skill_name AS required_skill
            FROM projects p
            JOIN users u ON p.client_id = u.user_id
            LEFT JOIN skills s ON p.required_skill_id = s.skill_id
            WHERE p.project_id = ?
        ");
        $stmt->execute([$projectId]);
        $project = $stmt->fetch();
        if (!$project) {
            echo json_encode(['success' => false, 'message' => 'Project not found.']);
            exit;
        }
        echo json_encode(['success' => true, 'project' => $project]);
        break;


    // ── POST PROJECT (Client only) ────────────────────────
    case 'post_project':
        if (!in_array($role, ['client', 'both'])) {
            echo json_encode(['success' => false, 'message' => 'Only clients can post projects.']);
            exit;
        }
        $title       = sanitize($_POST['title']       ?? '');
        $description = sanitize($_POST['description'] ?? '');
        $budget      = (float)($_POST['budget']       ?? 0);
        $deadline    = sanitize($_POST['deadline']    ?? '');
        $skillId     = (int)($_POST['skill_id']       ?? 0) ?: null;

        if (!$title || !$description || !$budget || !$deadline) {
            echo json_encode(['success' => false, 'message' => 'All required fields must be filled.']);
            exit;
        }
        if (strtotime($deadline) <= time()) {
            echo json_encode(['success' => false, 'message' => 'Deadline must be a future date.']);
            exit;
        }

        $stmt = $pdo->prepare("INSERT INTO projects (client_id, title, description, budget, deadline, required_skill_id) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$userId, $title, $description, $budget, $deadline, $skillId]);

        addNotification($pdo, $userId, "Your project '$title' has been posted successfully!", 'success');

        echo json_encode(['success' => true, 'message' => 'Project posted successfully!', 'project_id' => $pdo->lastInsertId()]);
        break;


    // ── MY PROJECTS (Client) ──────────────────────────────
    case 'my_projects':
        $stmt = $pdo->prepare("
            SELECT p.*, s.skill_name,
                   (SELECT COUNT(*) FROM applications a WHERE a.project_id = p.project_id) AS app_count
            FROM projects p
            LEFT JOIN skills s ON p.required_skill_id = s.skill_id
            WHERE p.client_id = ?
            ORDER BY p.created_at DESC
        ");
        $stmt->execute([$userId]);
        echo json_encode(['success' => true, 'projects' => $stmt->fetchAll()]);
        break;


    // ── APPLY TO PROJECT (Freelancer only) ────────────────
    case 'apply':
        if (!in_array($role, ['freelancer', 'both'])) {
            echo json_encode(['success' => false, 'message' => 'Only freelancers can apply to projects.']);
            exit;
        }
        $projectId   = (int)($_POST['project_id']   ?? 0);
        $coverLetter = sanitize($_POST['cover_letter'] ?? '');

        if (!$projectId) {
            echo json_encode(['success' => false, 'message' => 'Invalid project.']);
            exit;
        }

        // Get project info
        $stmt = $pdo->prepare("SELECT * FROM projects WHERE project_id = ? AND status = 'Open'");
        $stmt->execute([$projectId]);
        $project = $stmt->fetch();
        if (!$project) {
            echo json_encode(['success' => false, 'message' => 'Project not found or not open.']);
            exit;
        }

        // Prevent applying to own project
        if ($project['client_id'] == $userId) {
            echo json_encode(['success' => false, 'message' => 'You cannot apply to your own project.']);
            exit;
        }

        // Check if already applied
        $stmt = $pdo->prepare("SELECT application_id FROM applications WHERE project_id = ? AND user_id = ?");
        $stmt->execute([$projectId, $userId]);
        if ($stmt->fetch()) {
            echo json_encode(['success' => false, 'message' => 'You have already applied to this project.']);
            exit;
        }

        // If required skill, check verification
        if ($project['required_skill_id']) {
            $stmt = $pdo->prepare("SELECT verification_status FROM user_skills WHERE user_id = ? AND skill_id = ?");
            $stmt->execute([$userId, $project['required_skill_id']]);
            $skillStatus = $stmt->fetchColumn();
            if ($skillStatus !== 'Verified') {
                echo json_encode(['success' => false, 'message' => 'You need to be verified in the required skill to apply.']);
                exit;
            }
        } else {
            // Check if user has at least one verified skill
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM user_skills WHERE user_id = ? AND verification_status = 'Verified'");
            $stmt->execute([$userId]);
            if ($stmt->fetchColumn() == 0) {
                echo json_encode(['success' => false, 'message' => 'You need at least one verified skill to apply.']);
                exit;
            }
        }

        $stmt = $pdo->prepare("INSERT INTO applications (project_id, user_id, cover_letter) VALUES (?, ?, ?)");
        $stmt->execute([$projectId, $userId, $coverLetter]);

        // Notify client
        $applicantName = $_SESSION['user_name'];
        addNotification($pdo, $project['client_id'], "New application for '{$project['title']}' from $applicantName!", 'info');
        addNotification($pdo, $userId, "Your application for '{$project['title']}' has been submitted!", 'success');

        echo json_encode(['success' => true, 'message' => 'Application submitted successfully!']);
        break;


    // ── MY APPLICATIONS (Freelancer) ──────────────────────
    case 'my_applications':
        $stmt = $pdo->prepare("
            SELECT a.*, p.title AS project_title, p.budget, p.deadline, p.status AS project_status,
                   u.name AS client_name
            FROM applications a
            JOIN projects p ON a.project_id = p.project_id
            JOIN users u ON p.client_id = u.user_id
            WHERE a.user_id = ?
            ORDER BY a.applied_at DESC
        ");
        $stmt->execute([$userId]);
        echo json_encode(['success' => true, 'applications' => $stmt->fetchAll()]);
        break;


    // ── GET APPLICANTS (Client) ───────────────────────────
    case 'get_applicants':
        $projectId = (int)($_GET['project_id'] ?? 0);
        // Verify ownership
        $stmt = $pdo->prepare("SELECT client_id FROM projects WHERE project_id = ?");
        $stmt->execute([$projectId]);
        $proj = $stmt->fetch();
        if (!$proj || $proj['client_id'] != $userId) {
            echo json_encode(['success' => false, 'message' => 'Unauthorized.']);
            exit;
        }

        $stmt = $pdo->prepare("
            SELECT a.*, u.name, u.email, u.avatar,
                   GROUP_CONCAT(s.skill_name ORDER BY s.skill_name SEPARATOR ', ') AS verified_skills
            FROM applications a
            JOIN users u ON a.user_id = u.user_id
            LEFT JOIN user_skills us ON us.user_id = u.user_id AND us.verification_status = 'Verified'
            LEFT JOIN skills s ON us.skill_id = s.skill_id
            WHERE a.project_id = ?
            GROUP BY a.application_id
            ORDER BY a.applied_at DESC
        ");
        $stmt->execute([$projectId]);
        echo json_encode(['success' => true, 'applicants' => $stmt->fetchAll()]);
        break;


    // ── UPDATE APPLICATION STATUS (Client) ───────────────
    case 'update_app_status':
        $appId     = (int)($_POST['application_id'] ?? 0);
        $newStatus = sanitize($_POST['status'] ?? '');

        if (!in_array($newStatus, ['Accepted', 'Rejected'])) {
            echo json_encode(['success' => false, 'message' => 'Invalid status.']);
            exit;
        }

        // Get application with project ownership check
        $stmt = $pdo->prepare("SELECT a.*, p.title, p.client_id FROM applications a JOIN projects p ON a.project_id = p.project_id WHERE a.application_id = ?");
        $stmt->execute([$appId]);
        $app = $stmt->fetch();

        if (!$app || $app['client_id'] != $userId) {
            echo json_encode(['success' => false, 'message' => 'Unauthorized.']);
            exit;
        }

        $pdo->beginTransaction();

        $stmt = $pdo->prepare("UPDATE applications SET status = ? WHERE application_id = ?");
        $stmt->execute([$newStatus, $appId]);

        if ($newStatus === 'Accepted') {
            $stmt = $pdo->prepare("
                SELECT application_id, user_id
                FROM applications
                WHERE project_id = ? AND application_id <> ? AND status = 'Applied'
            ");
            $stmt->execute([$app['project_id'], $appId]);
            $otherApplicants = $stmt->fetchAll();

            $stmt = $pdo->prepare("
                UPDATE applications
                SET status = 'Rejected'
                WHERE project_id = ? AND application_id <> ? AND status = 'Applied'
            ");
            $stmt->execute([$app['project_id'], $appId]);

            $stmt = $pdo->prepare("UPDATE projects SET status = 'In Progress' WHERE project_id = ?");
            $stmt->execute([$app['project_id']]);
        }

        $pdo->commit();

        $msg = $newStatus === 'Accepted'
            ? "Your application for '{$app['title']}' has been accepted."
            : "Your application for '{$app['title']}' was not selected this time.";
        $type = $newStatus === 'Accepted' ? 'success' : 'warning';
        addNotification($pdo, $app['user_id'], $msg, $type);

        if ($newStatus === 'Accepted' && !empty($otherApplicants)) {
            foreach ($otherApplicants as $otherApplicant) {
                addNotification(
                    $pdo,
                    (int) $otherApplicant['user_id'],
                    "Another freelancer has been selected for '{$app['title']}'.",
                    'warning'
                );
            }
        }

        echo json_encode(['success' => true, 'message' => "Application $newStatus."]);
        break;


    // ── UPDATE PROJECT STATUS (Client) ───────────────────
    case 'update_project_status':
        $projectId = (int)($_POST['project_id'] ?? 0);
        $newStatus = sanitize($_POST['status'] ?? '');

        if (!in_array($newStatus, ['Open', 'In Progress', 'Closed'])) {
            echo json_encode(['success' => false, 'message' => 'Invalid status.']);
            exit;
        }

        $stmt = $pdo->prepare("SELECT client_id FROM projects WHERE project_id = ?");
        $stmt->execute([$projectId]);
        $proj = $stmt->fetch();

        if (!$proj || $proj['client_id'] != $userId) {
            echo json_encode(['success' => false, 'message' => 'Unauthorized.']);
            exit;
        }

        $stmt = $pdo->prepare("UPDATE projects SET status = ? WHERE project_id = ?");
        $stmt->execute([$newStatus, $projectId]);

        echo json_encode(['success' => true, 'message' => "Project status updated to $newStatus."]);
        break;


    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action.']);
}
