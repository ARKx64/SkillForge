<?php
// ============================================================
//  SkillForge - Skills Handler
//  File: php/skills.php
//  Handles: Enlist skill, fetch user skills, fetch all skills
// ============================================================

require_once '../includes/config.php';
startSession();

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized. Please login.']);
    exit;
}

$userId = $_SESSION['user_id'];
$action = $_POST['action'] ?? $_GET['action'] ?? '';

switch ($action) {

    // ── GET ALL SKILLS ────────────────────────────────────
    case 'get_all_skills':
        $stmt = $pdo->query("SELECT s.*, st.test_id FROM skills s LEFT JOIN skill_tests st ON s.skill_id = st.skill_id ORDER BY s.category, s.skill_name");
        $skills = $stmt->fetchAll();
        echo json_encode(['success' => true, 'skills' => $skills]);
        break;


    // ── GET USER SKILLS ───────────────────────────────────
    case 'get_user_skills':
        $uid = (int)($_GET['user_id'] ?? $userId);
        $stmt = $pdo->prepare("
            SELECT us.*, s.skill_name, s.category, s.icon,
                   (SELECT COUNT(*) FROM test_results tr 
                    JOIN skill_tests st ON tr.test_id = st.test_id 
                    WHERE tr.user_id = ? AND st.skill_id = us.skill_id) as attempt_count
            FROM user_skills us
            JOIN skills s ON us.skill_id = s.skill_id
            WHERE us.user_id = ?
            ORDER BY us.verification_status, s.skill_name
        ");
        $stmt->execute([$uid, $uid]);
        $skills = $stmt->fetchAll();
        echo json_encode(['success' => true, 'skills' => $skills]);
        break;


    // ── ENLIST SKILL ──────────────────────────────────────
    case 'enlist':
        $skillId = (int)($_POST['skill_id'] ?? 0);
        if (!$skillId) {
            echo json_encode(['success' => false, 'message' => 'Invalid skill.']);
            exit;
        }

        // Check if already enlisted
        $stmt = $pdo->prepare("SELECT user_skill_id, verification_status FROM user_skills WHERE user_id = ? AND skill_id = ?");
        $stmt->execute([$userId, $skillId]);
        $existing = $stmt->fetch();

        if ($existing) {
            echo json_encode(['success' => false, 'message' => 'You have already enlisted this skill.', 'status' => $existing['verification_status']]);
            exit;
        }

        $stmt = $pdo->prepare("INSERT INTO user_skills (user_id, skill_id) VALUES (?, ?)");
        $stmt->execute([$userId, $skillId]);

        // Fetch skill name for notification
        $s = $pdo->prepare("SELECT skill_name FROM skills WHERE skill_id = ?");
        $s->execute([$skillId]);
        $skill = $s->fetch();

        addNotification($pdo, $userId, "You've enlisted '{$skill['skill_name']}'. Take the verification test to get verified!", 'info');

        echo json_encode(['success' => true, 'message' => "Skill enlisted! Now take the verification test."]);
        break;


    // ── GET TEST FOR SKILL ────────────────────────────────
    case 'get_test':
        $skillId = (int)($_GET['skill_id'] ?? 0);
        if (!$skillId) {
            echo json_encode(['success' => false, 'message' => 'Invalid skill.']);
            exit;
        }

        // Check if user has this skill enlisted
        $stmt = $pdo->prepare("SELECT * FROM user_skills WHERE user_id = ? AND skill_id = ?");
        $stmt->execute([$userId, $skillId]);
        $userSkill = $stmt->fetch();
        if (!$userSkill) {
            echo json_encode(['success' => false, 'message' => 'Please enlist this skill first.']);
            exit;
        }

        // Get test info
        $stmt = $pdo->prepare("SELECT st.*, s.skill_name FROM skill_tests st JOIN skills s ON st.skill_id = s.skill_id WHERE st.skill_id = ?");
        $stmt->execute([$skillId]);
        $test = $stmt->fetch();
        if (!$test) {
            echo json_encode(['success' => false, 'message' => 'No test found for this skill.']);
            exit;
        }

        // Get questions (hide correct answer!)
        $stmt = $pdo->prepare("SELECT question_id, question_text, option_a, option_b, option_c, option_d FROM test_questions WHERE test_id = ? ORDER BY RAND() LIMIT 10");
        $stmt->execute([$test['test_id']]);
        $questions = $stmt->fetchAll();

        echo json_encode(['success' => true, 'test' => $test, 'questions' => $questions]);
        break;


    // ── SUBMIT TEST ───────────────────────────────────────
    case 'submit_test':
        $skillId  = (int)($_POST['skill_id']  ?? 0);
        $answers  = $_POST['answers'] ?? [];   // ['question_id' => 'A/B/C/D']

        if (!$skillId || empty($answers)) {
            echo json_encode(['success' => false, 'message' => 'Invalid submission.']);
            exit;
        }

        // Check user skill
        $stmt = $pdo->prepare("SELECT * FROM user_skills WHERE user_id = ? AND skill_id = ?");
        $stmt->execute([$userId, $skillId]);
        $userSkill = $stmt->fetch();
        if (!$userSkill) {
            echo json_encode(['success' => false, 'message' => 'Skill not enlisted.']);
            exit;
        }

        // Get test
        $stmt = $pdo->prepare("SELECT * FROM skill_tests WHERE skill_id = ?");
        $stmt->execute([$skillId]);
        $test = $stmt->fetch();
        if (!$test) {
            echo json_encode(['success' => false, 'message' => 'Test configuration not found for this skill.']);
            exit;
        }

        // Get correct answers
        $qIds = array_keys($answers);
        if (empty($qIds)) {
            echo json_encode(['success' => false, 'message' => 'Please answer at least one question before submitting.']);
            exit;
        }
        $placeholders = implode(',', array_fill(0, count($qIds), '?'));

        $stmt = $pdo->prepare("SELECT question_id, correct_ans, marks FROM test_questions WHERE question_id IN ($placeholders) AND test_id = ?");
        $stmt->execute(array_merge($qIds, [$test['test_id']]));
        $questions = $stmt->fetchAll();
        if (!$questions) {
            echo json_encode(['success' => false, 'message' => 'Unable to validate submitted answers for this test.']);
            exit;
        }

        $score = 0;
        foreach ($questions as $q) {
            $qId = $q['question_id'];
            if (isset($answers[$qId]) && strtoupper($answers[$qId]) === $q['correct_ans']) {
                $score += $q['marks'];
            }
        }

        // Determine pass/fail
        $status = ($score >= $test['passing_marks']) ? 'Pass' : 'Fail';

        // Attempt count
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM test_results WHERE user_id = ? AND test_id = ?");
        $stmt->execute([$userId, $test['test_id']]);
        $attemptNo = (int)$stmt->fetchColumn() + 1;

        // Save result
        $stmt = $pdo->prepare("INSERT INTO test_results (user_id, test_id, score, status, attempt_no) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$userId, $test['test_id'], $score, $status, $attemptNo]);

        // Update user skill verification status
        $newStatus = ($status === 'Pass') ? 'Verified' : 'Failed';
        $stmt = $pdo->prepare("UPDATE user_skills SET verification_status = ? WHERE user_id = ? AND skill_id = ?");
        $stmt->execute([$newStatus, $userId, $skillId]);

        // Notification
        $skillName = $pdo->prepare("SELECT skill_name FROM skills WHERE skill_id = ?");
        $skillName->execute([$skillId]);
        $sName = $skillName->fetchColumn();

        if ($status === 'Pass') {
            addNotification($pdo, $userId, "🎉 Congratulations! You passed the '$sName' test with $score/{$test['total_marks']}. You're now Verified!", 'success');
        } else {
            addNotification($pdo, $userId, "You scored $score/{$test['total_marks']} on '$sName'. Need {$test['passing_marks']} to pass. Try again!", 'warning');
        }

        echo json_encode([
            'success'      => true,
            'score'        => $score,
            'total'        => $test['total_marks'],
            'passing'      => $test['passing_marks'],
            'status'       => $status,
            'verification' => $newStatus,
            'message'      => $status === 'Pass'
                ? "🎉 You passed! Score: $score/{$test['total_marks']}. Skill is now Verified!"
                : "❌ You scored $score/{$test['total_marks']}. Minimum passing score is {$test['passing_marks']}. Try again!"
        ]);
        break;


    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action.']);
}
