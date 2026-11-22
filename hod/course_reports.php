<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'hod') {
    header('Location: ../index.php');
    exit;
}

require_once __DIR__ . '/../config/db.php';

$dept_id = intval($_SESSION['department_id'] ?? 0);

// Fetch department
$stmtDept = $pdo->prepare("SELECT * FROM departments WHERE id = ?");
$stmtDept->execute([$dept_id]);
$department = $stmtDept->fetch();
if (!$department) {
    die("Department not found.");
}

// Fetch all courses in this department
$stmtCourses = $pdo->prepare("SELECT * FROM courses WHERE department_id = ? ORDER BY course_code ASC");
$stmtCourses->execute([$dept_id]);
$courses = $stmtCourses->fetchAll();

// Selected course filter
$selected_course_id = intval($_GET['course_id'] ?? 0);
if ($selected_course_id === 0 && !empty($courses)) {
    $selected_course_id = $courses[0]['id'];
}

$courseDetails = null;
$stats = null;
$comments = [];

if ($selected_course_id > 0) {
    // Security: Ensure course belongs to this HoD's department
    $stmtC = $pdo->prepare("
        SELECT c.*,
               (SELECT l.name FROM lecturer_courses lc JOIN lecturers l ON lc.lecturer_id = l.id WHERE lc.course_id = c.id LIMIT 1) AS lecturer_name,
               (SELECT COUNT(*) FROM student_courses sc WHERE sc.course_id = c.id) AS enrolled_students
        FROM courses c 
        WHERE c.id = ? AND c.department_id = ?
    ");
    $stmtC->execute([$selected_course_id, $dept_id]);
    $courseDetails = $stmtC->fetch();

    if ($courseDetails) {
        // Compute question averages
        $stmtStats = $pdo->prepare("
            SELECT 
                COUNT(id) AS total_evals,
                ROUND(AVG(q1), 2) AS avg_q1,
                ROUND(AVG(q2), 2) AS avg_q2,
                ROUND(AVG(q3), 2) AS avg_q3,
                ROUND(AVG(q4), 2) AS avg_q4,
                ROUND(AVG(q5), 2) AS avg_q5,
                ROUND(AVG(q6), 2) AS avg_q6,
                ROUND(AVG(q7), 2) AS avg_q7,
                ROUND(AVG(q8), 2) AS avg_q8,
                ROUND(AVG((q1 + q2 + q3 + q4 + q5 + q6 + q7 + q8) / 8), 2) AS overall_avg
            FROM evaluations
            WHERE course_id = ?
        ");
        $stmtStats->execute([$selected_course_id]);
        $stats = $stmtStats->fetch();

        // Comments
        $stmtComments = $pdo->prepare("
            SELECT comments, created_at 
            FROM evaluations 
            WHERE course_id = ? AND comments IS NOT NULL AND TRIM(comments) != ''
            ORDER BY created_at DESC
        ");
        $stmtComments->execute([$selected_course_id]);
        $comments = $stmtComments->fetchAll();
    }
}

$criteriaLabels = [
    'avg_q1' => '1. Lecturer Knowledge of Module',
    'avg_q2' => '2. Clarity of Explanations',
    'avg_q3' => '3. Preparation for Lessons',
    'avg_q4' => '4. Teaching Methods',
    'avg_q5' => '5. Student Participation',
    'avg_q6' => '6. Respect & Support for Students',
    'avg_q7' => '7. Syllabus Completion',
    'avg_q8' => '8. Quality of Learning Materials'
];

$page_title = 'Module Evaluation Reports';
$base_path = '../';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="card">
    <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px;">
        <div>
            <h2>Module / Course Evaluation Reports</h2>
            <p style="color: var(--text-muted);">
                Department of <strong><?= htmlspecialchars($department['name']) ?> (<?= htmlspecialchars($department['code']) ?>)</strong>
            </p>
        </div>
        <div>
            <button onclick="window.print();" class="btn btn-secondary btn-sm">&#128438; Print Report</button>
        </div>
    </div>

    <!-- Filter by Course -->
    <form method="GET" action="course_reports.php" style="margin-top: 20px;">
        <div style="display: flex; gap: 10px; align-items: center; flex-wrap: wrap;">
            <label for="course_id" style="font-weight: 600;">Select Module:</label>
            <select name="course_id" id="course_id" class="form-control" style="max-width: 350px;" onchange="this.form.submit();">
                <?php foreach ($courses as $c): ?>
                    <option value="<?= $c['id'] ?>" <?= $selected_course_id == $c['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($c['course_code']) ?> - <?= htmlspecialchars($c['course_name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <button type="submit" class="btn btn-primary btn-sm">Filter</button>
        </div>
    </form>
</div>

<?php if ($courseDetails && $stats): ?>
    <div class="grid">
        <div class="stat-card">
            <div class="stat-title">Assigned Lecturer</div>
            <div style="font-size: 1.15rem; font-weight: 700; color: var(--primary); margin-top: 8px;">
                <?= htmlspecialchars($courseDetails['lecturer_name'] ?? 'Not Assigned') ?>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-title">Enrolled Students</div>
            <div class="stat-value"><?= $courseDetails['enrolled_students'] ?></div>
        </div>
        <div class="stat-card">
            <div class="stat-title">Evaluations Received</div>
            <div class="stat-value"><?= $stats['total_evals'] ?></div>
        </div>
        <div class="stat-card" style="border-left-color: var(--success);">
            <div class="stat-title">Module Average Rating</div>
            <div class="stat-value" style="color: var(--success);"><?= $stats['overall_avg'] ? $stats['overall_avg'] . ' / 5.0' : 'No ratings' ?></div>
        </div>
    </div>

    <!-- Assessment Criteria Breakdown -->
    <div class="card">
        <h3>Assessment Criteria Breakdown (Scale: 1.0 to 5.0)</h3>
        <?php if ($stats['total_evals'] == 0): ?>
            <p style="color: var(--text-muted); padding: 10px 0;">No student evaluations submitted for this module yet.</p>
        <?php else: ?>
            <div style="margin-top: 15px;">
                <?php foreach ($criteriaLabels as $key => $label): 
                    $score = $stats[$key] ?? 0;
                    $percent = ($score / 5) * 100;
                ?>
                    <div style="margin-bottom: 16px;">
                        <div style="display: flex; justify-content: space-between; font-size: 0.95rem; margin-bottom: 5px;">
                            <span><strong><?= $label ?></strong></span>
                            <span><strong><?= $score ?></strong> / 5.0</span>
                        </div>
                        <div style="background: #e9ecef; border-radius: 6px; height: 14px; width: 100%; overflow: hidden;">
                            <div style="background: <?= $percent >= 70 ? '#28a745' : ($percent >= 50 ? '#ffc107' : '#dc3545') ?>; height: 100%; width: <?= $percent ?>%; transition: width 0.3s;"></div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Comments & Recommendations -->
    <div class="card">
        <h3>Student Feedback & Recommendations (<?= count($comments) ?>)</h3>
        <?php if (empty($comments)): ?>
            <p style="color: var(--text-muted); padding: 10px 0;">No written comments submitted for this module yet.</p>
        <?php else: ?>
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th style="width: 50px;">#</th>
                            <th>Student Comment</th>
                            <th style="width: 150px;">Date Submitted</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($comments as $idx => $c): ?>
                            <tr>
                                <td><?= $idx + 1 ?></td>
                                <td><em>"<?= nl2br(htmlspecialchars($c['comments'])) ?>"</em></td>
                                <td><small style="color: var(--text-muted);"><?= date('M d, Y', strtotime($c['created_at'])) ?></small></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
<?php else: ?>
    <div class="card">
        <p style="color: var(--text-muted);">Please select a module to view its evaluation report.</p>
    </div>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
