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

// Fetch all lecturers and courses in this department for filters
$lecturers = $pdo->prepare("SELECT * FROM lecturers WHERE department_id = ? ORDER BY name ASC");
$lecturers->execute([$dept_id]);
$lecturerList = $lecturers->fetchAll();

$courses = $pdo->prepare("SELECT * FROM courses WHERE department_id = ? ORDER BY course_code ASC");
$courses->execute([$dept_id]);
$courseList = $courses->fetchAll();

// Filter parameters
$filter_lecturer = intval($_GET['lecturer_id'] ?? 0);
$filter_course = intval($_GET['course_id'] ?? 0);

$query = "
    SELECT 
        e.*,
        s.reg_no, s.name AS student_name,
        l.name AS lecturer_name,
        c.course_code, c.course_name,
        ROUND((e.q1 + e.q2 + e.q3 + e.q4 + e.q5 + e.q6 + e.q7 + e.q8) / 8, 2) AS average_score
    FROM evaluations e
    JOIN students s ON e.student_id = s.id
    JOIN lecturers l ON e.lecturer_id = l.id
    JOIN courses c ON e.course_id = c.id
    WHERE c.department_id = ?
";

$params = [$dept_id];

if ($filter_lecturer > 0) {
    $query .= " AND e.lecturer_id = ?";
    $params[] = $filter_lecturer;
}

if ($filter_course > 0) {
    $query .= " AND e.course_id = ?";
    $params[] = $filter_course;
}

$query .= " ORDER BY e.id DESC";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$results = $stmt->fetchAll();

$page_title = 'Evaluation Results';
$base_path = '../';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="card">
    <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px;">
        <div>
            <h2>Department Evaluation Records</h2>
            <p style="color: var(--text-muted);">
                Department: <strong><?= htmlspecialchars($department['name']) ?> (<?= htmlspecialchars($department['code']) ?>)</strong>
            </p>
        </div>
        <div>
            <button onclick="window.print();" class="btn btn-secondary btn-sm">&#128438; Print Results</button>
        </div>
    </div>

    <!-- Filter Bar -->
    <form method="GET" action="evaluation_results.php" style="margin-top: 15px; display: flex; gap: 12px; flex-wrap: wrap; align-items: center;">
        <div>
            <label for="lecturer_id" style="font-size: 0.85rem; font-weight: 600;">Filter Lecturer:</label>
            <select name="lecturer_id" id="lecturer_id" class="form-control" style="width: auto;">
                <option value="0">-- All Lecturers --</option>
                <?php foreach ($lecturerList as $l): ?>
                    <option value="<?= $l['id'] ?>" <?= $filter_lecturer == $l['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($l['name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div>
            <label for="course_id" style="font-size: 0.85rem; font-weight: 600;">Filter Module:</label>
            <select name="course_id" id="course_id" class="form-control" style="width: auto;">
                <option value="0">-- All Modules --</option>
                <?php foreach ($courseList as $c): ?>
                    <option value="<?= $c['id'] ?>" <?= $filter_course == $c['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($c['course_code']) ?> - <?= htmlspecialchars($c['course_name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <button type="submit" class="btn btn-primary btn-sm" style="margin-top: 18px;">Apply Filter</button>
        <?php if ($filter_lecturer > 0 || $filter_course > 0): ?>
            <a href="evaluation_results.php" class="btn btn-secondary btn-sm" style="margin-top: 18px;">Reset</a>
        <?php endif; ?>
    </form>
</div>

<div class="card">
    <h3>Submitted Evaluations (<?= count($results) ?>)</h3>
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Module</th>
                    <th>Lecturer</th>
                    <th>Student (Reg No)</th>
                    <th title="Scores Q1 to Q8">Criteria (Q1–Q8)</th>
                    <th>Average Score</th>
                    <th>Comments</th>
                    <th>Date</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($results)): ?>
                    <tr><td colspan="8" style="text-align: center; color: var(--text-muted);">No evaluation records found matching criteria.</td></tr>
                <?php else: ?>
                    <?php foreach ($results as $idx => $r): ?>
                        <tr>
                            <td><?= $idx + 1 ?></td>
                            <td><strong><?= htmlspecialchars($r['course_code']) ?></strong></td>
                            <td><?= htmlspecialchars($r['lecturer_name']) ?></td>
                            <td><?= htmlspecialchars($r['student_name']) ?><br><small style="color: var(--text-muted);"><?= htmlspecialchars($r['reg_no']) ?></small></td>
                            <td>
                                <span style="font-size: 0.85rem; background: #eef2f7; padding: 2px 6px; border-radius: 4px;">
                                    <?= "{$r['q1']}, {$r['q2']}, {$r['q3']}, {$r['q4']}, {$r['q5']}, {$r['q6']}, {$r['q7']}, {$r['q8']}" ?>
                                </span>
                            </td>
                            <td><strong style="color: var(--primary);"><?= $r['average_score'] ?> / 5.0</strong></td>
                            <td><?= $r['comments'] ? '<em>"' . htmlspecialchars(substr($r['comments'], 0, 60)) . (strlen($r['comments']) > 60 ? '...' : '') . '"</em>' : '<span style="color: var(--text-muted);">None</span>' ?></td>
                            <td><small style="color: var(--text-muted);"><?= date('M d, Y', strtotime($r['created_at'])) ?></small></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
