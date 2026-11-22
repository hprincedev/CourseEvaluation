<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header('Location: ../index.php');
    exit;
}

require_once __DIR__ . '/../config/db.php';

$course_id = intval($_GET['course_id'] ?? 0);
$lecturer_id = intval($_GET['lecturer_id'] ?? 0);

$info = null;
if ($course_id && $lecturer_id) {
    $stmt = $pdo->prepare("
        SELECT c.course_code, c.course_name, l.name AS lecturer_name
        FROM courses c
        JOIN lecturers l ON l.id = ?
        WHERE c.id = ?
    ");
    $stmt->execute([$lecturer_id, $course_id]);
    $info = $stmt->fetch();
}

$page_title = 'Submission Confirmation';
$base_path = '../';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="card" style="text-align: center; max-width: 650px; margin: 40px auto; padding: 35px 25px;">
    <div style="font-size: 50px; color: var(--success); margin-bottom: 10px;">&#10004;</div>
    <h2 style="color: var(--success);">Evaluation Submitted Successfully!</h2>
    <p style="color: var(--text-muted); font-size: 1.05rem; margin-top: 10px;">
        Thank you for taking the time to provide constructive feedback. Your responses have been securely recorded.
    </p>

    <?php if ($info): ?>
        <div style="background: #f8f9fa; border: 1px solid var(--border-color); border-radius: 8px; padding: 18px; margin: 25px 0; text-align: left;">
            <p><strong>Module:</strong> <?= htmlspecialchars($info['course_code']) ?> - <?= htmlspecialchars($info['course_name']) ?></p>
            <p style="margin-top: 8px;"><strong>Lecturer:</strong> <?= htmlspecialchars($info['lecturer_name']) ?></p>
            <p style="margin-top: 8px;"><strong>Status:</strong> <span style="color: var(--success); font-weight: bold;">Recorded</span></p>
        </div>
    <?php endif; ?>

    <div style="margin-top: 25px;">
        <a href="dashboard.php" class="btn btn-primary">Return to My Courses</a>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
