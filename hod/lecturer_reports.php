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

// Fetch all lecturers in this HoD's department
$stmtLecturers = $pdo->prepare("SELECT * FROM lecturers WHERE department_id = ? ORDER BY name ASC");
$stmtLecturers->execute([$dept_id]);
$lecturers = $stmtLecturers->fetchAll();

// Selected lecturer filter
$selected_lecturer_id = intval($_GET['lecturer_id'] ?? 0);
if ($selected_lecturer_id === 0 && !empty($lecturers)) {
    $selected_lecturer_id = $lecturers[0]['id'];
}

$lecturerDetails = null;
$stats = null;
$comments = [];

if ($selected_lecturer_id > 0) {
    // Enforce department security restriction
    $stmtL = $pdo->prepare("SELECT * FROM lecturers WHERE id = ? AND department_id = ?");
    $stmtL->execute([$selected_lecturer_id, $dept_id]);
    $lecturerDetails = $stmtL->fetch();

    if ($lecturerDetails) {
        // Compute average for each question Q1 to Q8 & overall
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
            WHERE lecturer_id = ?
        ");
        $stmtStats->execute([$selected_lecturer_id]);
        $stats = $stmtStats->fetch();

        // Fetch student written feedback comments
        $stmtComments = $pdo->prepare("
            SELECT e.comments, e.created_at, c.course_code, c.course_name
            FROM evaluations e
            JOIN courses c ON e.course_id = c.id
            WHERE e.lecturer_id = ? AND e.comments IS NOT NULL AND TRIM(e.comments) != ''
            ORDER BY e.created_at DESC
        ");
        $stmtComments->execute([$selected_lecturer_id]);
        $comments = $stmtComments->fetchAll();
    }
}

$criteriaLabels = [
    'avg_q1' => '1. Subject Knowledge',
    'avg_q2' => '2. Clarity of Explanations',
    'avg_q3' => '3. Preparation for Lessons',
    'avg_q4' => '4. Teaching Methods',
    'avg_q5' => '5. Encouraging Participation',
    'avg_q6' => '6. Respect & Support for Students',
    'avg_q7' => '7. Syllabus Completion',
    'avg_q8' => '8. Quality of Learning Materials'
];

$page_title = 'Lecturer Evaluation Reports';
$base_path = '../';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="card">
    <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px;">
        <div>
            <h2>Lecturer Evaluation Reports</h2>
            <p style="color: var(--text-muted);">
                Department of <strong><?= htmlspecialchars($department['name']) ?> (<?= htmlspecialchars($department['code']) ?>)</strong>
            </p>
        </div>
        <div>
            <button onclick="window.print();" class="btn btn-secondary btn-sm">&#128438; Print Report</button>
        </div>
    </div>

    <!-- Filter by Lecturer -->
    <form method="GET" action="lecturer_reports.php" style="margin-top: 20px;">
        <div style="display: flex; gap: 10px; align-items: center; flex-wrap: wrap;">
            <label for="lecturer_id" style="font-weight: 600;">Select Lecturer:</label>
            <select name="lecturer_id" id="lecturer_id" class="form-control" style="max-width: 350px;" onchange="this.form.submit();">
                <?php foreach ($lecturers as $l): ?>
                    <option value="<?= $l['id'] ?>" <?= $selected_lecturer_id == $l['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($l['name']) ?> (<?= htmlspecialchars($l['email']) ?>)
                    </option>
                <?php endforeach; ?>
            </select>
            <button type="submit" class="btn btn-primary btn-sm">Filter</button>
        </div>
    </form>
</div>

<?php if ($lecturerDetails && $stats): ?>
    <div class="grid">
        <div class="stat-card">
            <div class="stat-title">Evaluations Received</div>
            <div class="stat-value"><?= $stats['total_evals'] ?></div>
        </div>
        <div class="stat-card" style="border-left-color: var(--success);">
            <div class="stat-title">Overall Score Average</div>
            <div class="stat-value" style="color: var(--success);"><?= $stats['overall_avg'] ? $stats['overall_avg'] . ' / 5.0' : 'No ratings' ?></div>
        </div>
    </div>

    <!-- Criteria Breakdown with Visual Rating Bars -->
    <div class="card">
        <h3>Assessment Criteria Breakdown (Scale: 1.0 to 5.0)</h3>
        <?php if ($stats['total_evals'] == 0): ?>
            <p style="color: var(--text-muted); padding: 10px 0;">No student evaluations submitted for this lecturer yet.</p>
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

    <!-- Written Feedback & Comments -->
    <div class="card">
        <h3>Student Feedback & Recommendations (<?= count($comments) ?>)</h3>
        <?php if (empty($comments)): ?>
            <p style="color: var(--text-muted); padding: 10px 0;">No written feedback comments submitted yet.</p>
        <?php else: ?>
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th style="width: 25%;">Module</th>
                            <th>Student Comment</th>
                            <th style="width: 15%;">Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($comments as $c): ?>
                            <tr>
                                <td><strong><?= htmlspecialchars($c['course_code']) ?></strong><br><small><?= htmlspecialchars($c['course_name']) ?></small></td>
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
        <p style="color: var(--text-muted);">Please select a lecturer to view their evaluation report.</p>
    </div>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
