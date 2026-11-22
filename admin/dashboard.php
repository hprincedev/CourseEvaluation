<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../index.php');
    exit;
}

require_once __DIR__ . '/../config/db.php';

// Fetch summary metrics
$countDepts = $pdo->query("SELECT COUNT(*) FROM departments")->fetchColumn();
$countLecturers = $pdo->query("SELECT COUNT(*) FROM lecturers")->fetchColumn();
$countStudents = $pdo->query("SELECT COUNT(*) FROM students")->fetchColumn();
$countCourses = $pdo->query("SELECT COUNT(*) FROM courses")->fetchColumn();
$countEvaluations = $pdo->query("SELECT COUNT(*) FROM evaluations")->fetchColumn();

$page_title = 'Admin Dashboard';
$base_path = '../';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="card">
    <h2>System Administrator Dashboard</h2>
    <p style="color: var(--text-muted);">Welcome, <?= htmlspecialchars($_SESSION['username']) ?>! Manage college records, assignments, and courses.</p>
</div>

<div class="grid">
    <div class="stat-card">
        <div class="stat-title">Departments</div>
        <div class="stat-value"><?= $countDepts ?></div>
    </div>
    <div class="stat-card">
        <div class="stat-title">Lecturers</div>
        <div class="stat-value"><?= $countLecturers ?></div>
    </div>
    <div class="stat-card">
        <div class="stat-title">Students</div>
        <div class="stat-value"><?= $countStudents ?></div>
    </div>
    <div class="stat-card">
        <div class="stat-title">Courses / Modules</div>
        <div class="stat-value"><?= $countCourses ?></div>
    </div>
    <div class="stat-card">
        <div class="stat-title">Submitted Evaluations</div>
        <div class="stat-value"><?= $countEvaluations ?></div>
    </div>
</div>

<div class="card">
    <h3>Quick Management Actions</h3>
    <div style="display: flex; gap: 12px; flex-wrap: wrap; margin-top: 15px;">
        <a href="departments.php" class="btn btn-primary">Manage Departments</a>
        <a href="lecturers.php" class="btn btn-primary">Manage Lecturers</a>
        <a href="students.php" class="btn btn-primary">Manage Students</a>
        <a href="courses.php" class="btn btn-primary">Manage Courses</a>
        <a href="assign_lecturer.php" class="btn btn-secondary">Assign Lecturers</a>
        <a href="assign_student.php" class="btn btn-secondary">Assign Students</a>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
