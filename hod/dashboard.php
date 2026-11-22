<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'hod') {
    header('Location: ../index.php');
    exit;
}

require_once __DIR__ . '/../config/db.php';

$dept_id = intval($_SESSION['department_id'] ?? 0);

// Department isolation check
$stmtDept = $pdo->prepare("SELECT * FROM departments WHERE id = ?");
$stmtDept->execute([$dept_id]);
$department = $stmtDept->fetch();

if (!$department) {
    die("Access denied: Department not assigned to your account.");
}

// 1. Department Metrics
$stmtL = $pdo->prepare("SELECT COUNT(*) FROM lecturers WHERE department_id = ?");
$stmtL->execute([$dept_id]);
$totalLecturers = $stmtL->fetchColumn();

$stmtC = $pdo->prepare("SELECT COUNT(*) FROM courses WHERE department_id = ?");
$stmtC->execute([$dept_id]);
$totalCourses = $stmtC->fetchColumn();

$stmtE = $pdo->prepare("
    SELECT 
        COUNT(e.id) AS total_evaluations,
        AVG((e.q1 + e.q2 + e.q3 + e.q4 + e.q5 + e.q6 + e.q7 + e.q8) / 8) AS overall_avg
    FROM evaluations e
    JOIN courses c ON e.course_id = c.id
    WHERE c.department_id = ?
");
$stmtE->execute([$dept_id]);
$evalStats = $stmtE->fetch();

$totalEvaluations = $evalStats['total_evaluations'] ?? 0;
$overallAvg = $evalStats['overall_avg'] !== null ? round($evalStats['overall_avg'], 2) : 0;

// 2. Performance Summary per Lecturer
$stmtLecturerSummary = $pdo->prepare("
    SELECT 
        l.id, l.name, l.email,
        COUNT(e.id) AS eval_count,
        ROUND(AVG((e.q1 + e.q2 + e.q3 + e.q4 + e.q5 + e.q6 + e.q7 + e.q8) / 8), 2) AS avg_score
    FROM lecturers l
    LEFT JOIN evaluations e ON e.lecturer_id = l.id
    WHERE l.department_id = ?
    GROUP BY l.id
    ORDER BY avg_score DESC
");
$stmtLecturerSummary->execute([$dept_id]);
$lecturerSummaries = $stmtLecturerSummary->fetchAll();

// 3. Performance Summary per Course
$stmtCourseSummary = $pdo->prepare("
    SELECT 
        c.id, c.course_code, c.course_name,
        (SELECT COUNT(*) FROM student_courses sc WHERE sc.course_id = c.id) AS enrolled_students,
        COUNT(e.id) AS eval_count,
        ROUND(AVG((e.q1 + e.q2 + e.q3 + e.q4 + e.q5 + e.q6 + e.q7 + e.q8) / 8), 2) AS avg_score
    FROM courses c
    LEFT JOIN evaluations e ON e.course_id = c.id
    WHERE c.department_id = ?
    GROUP BY c.id
    ORDER BY c.course_code ASC
");
$stmtCourseSummary->execute([$dept_id]);
$courseSummaries = $stmtCourseSummary->fetchAll();

$page_title = 'HoD Dashboard';
$base_path = '../';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="card">
    <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px;">
        <div>
            <h2>Head of Department (HoD) Dashboard</h2>
            <p style="color: var(--text-muted); margin-top: 4px;">
                Department: <strong style="color: var(--primary);"><?= htmlspecialchars($department['name']) ?> (<?= htmlspecialchars($department['code']) ?>)</strong>
            </p>
        </div>
        <div>
            <button onclick="window.print();" class="btn btn-secondary btn-sm">&#128438; Print Summary</button>
        </div>
    </div>
</div>

<div class="grid">
    <div class="stat-card">
        <div class="stat-title">Lecturers in Department</div>
        <div class="stat-value"><?= $totalLecturers ?></div>
    </div>
    <div class="stat-card">
        <div class="stat-title">Modules Offered</div>
        <div class="stat-value"><?= $totalCourses ?></div>
    </div>
    <div class="stat-card">
        <div class="stat-title">Evaluations Received</div>
        <div class="stat-value"><?= $totalEvaluations ?></div>
    </div>
    <div class="stat-card" style="border-left-color: var(--success);">
        <div class="stat-title">Department Average</div>
        <div class="stat-value" style="color: var(--success);"><?= $overallAvg > 0 ? $overallAvg . ' / 5.0' : 'N/A' ?></div>
    </div>
</div>

<!-- Lecturer Performance Overview -->
<div class="card">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px;">
        <h3>Lecturer Performance Overview</h3>
        <a href="lecturer_reports.php" class="btn btn-primary btn-sm">Detailed Lecturer Reports &rarr;</a>
    </div>
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th>Lecturer Name</th>
                    <th>Email</th>
                    <th>Evaluations Received</th>
                    <th>Average Score</th>
                    <th>Rating Bar</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($lecturerSummaries)): ?>
                    <tr><td colspan="5" style="text-align: center; color: var(--text-muted);">No lecturers found in this department.</td></tr>
                <?php else: ?>
                    <?php foreach ($lecturerSummaries as $ls): ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($ls['name']) ?></strong></td>
                            <td><?= htmlspecialchars($ls['email']) ?></td>
                            <td><?= htmlspecialchars($ls['eval_count']) ?></td>
                            <td><strong><?= $ls['avg_score'] ? $ls['avg_score'] . ' / 5.0' : 'No ratings yet' ?></strong></td>
                            <td style="width: 180px;">
                                <?php $pct = $ls['avg_score'] ? ($ls['avg_score'] / 5) * 100 : 0; ?>
                                <div style="background: #e9ecef; border-radius: 4px; height: 16px; width: 100%; overflow: hidden;">
                                    <div style="background: <?= $pct >= 70 ? '#28a745' : ($pct >= 50 ? '#ffc107' : '#dc3545') ?>; height: 100%; width: <?= $pct ?>%;"></div>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Course / Module Evaluation Overview -->
<div class="card">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px;">
        <h3>Module Evaluation Overview</h3>
        <a href="course_reports.php" class="btn btn-secondary btn-sm">Detailed Module Reports &rarr;</a>
    </div>
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th>Module Code</th>
                    <th>Module Name</th>
                    <th>Enrolled Students</th>
                    <th>Evaluations Submitted</th>
                    <th>Average Score</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($courseSummaries)): ?>
                    <tr><td colspan="5" style="text-align: center; color: var(--text-muted);">No modules found in this department.</td></tr>
                <?php else: ?>
                    <?php foreach ($courseSummaries as $cs): ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($cs['course_code']) ?></strong></td>
                            <td><?= htmlspecialchars($cs['course_name']) ?></td>
                            <td><?= htmlspecialchars($cs['enrolled_students']) ?></td>
                            <td><?= htmlspecialchars($cs['eval_count']) ?></td>
                            <td><strong><?= $cs['avg_score'] ? $cs['avg_score'] . ' / 5.0' : 'No ratings yet' ?></strong></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
