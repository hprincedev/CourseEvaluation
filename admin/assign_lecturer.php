<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../index.php');
    exit;
}

require_once __DIR__ . '/../config/db.php';

$success = '';
$error = '';

// Handle unassigning a lecturer
if (isset($_GET['remove'])) {
    $assign_id = intval($_GET['remove']);
    $pdo->prepare("DELETE FROM lecturer_courses WHERE id = ?")->execute([$assign_id]);
    $success = "Lecturer assignment removed successfully.";
}

// Handle Assign form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['assign_lecturer'])) {
    $lecturer_id = intval($_POST['lecturer_id'] ?? 0);
    $course_id = intval($_POST['course_id'] ?? 0);

    if (empty($lecturer_id) || empty($course_id)) {
        $error = "Please select both a lecturer and a course.";
    } else {
        // Enforce department matching rule: Ensure that lecturers teach courses belonging to their departments
        $stmtL = $pdo->prepare("SELECT department_id, name FROM lecturers WHERE id = ?");
        $stmtL->execute([$lecturer_id]);
        $lecturer = $stmtL->fetch();

        $stmtC = $pdo->prepare("SELECT department_id, course_code FROM courses WHERE id = ?");
        $stmtC->execute([$course_id]);
        $course = $stmtC->fetch();

        if (!$lecturer || !$course) {
            $error = "Invalid lecturer or course selected.";
        } elseif ($lecturer['department_id'] !== $course['department_id']) {
            $error = "Department mismatch: A lecturer can only be assigned to courses belonging to their own department!";
        } else {
            try {
                // Insert or replace assignment
                $stmt = $pdo->prepare("INSERT INTO lecturer_courses (lecturer_id, course_id) VALUES (?, ?) ON DUPLICATE KEY UPDATE lecturer_id = VALUES(lecturer_id)");
                $stmt->execute([$lecturer_id, $course_id]);
                $success = "Lecturer assigned to course successfully.";
            } catch (PDOException $e) {
                $error = "Assignment failed: " . $e->getMessage();
            }
        }
    }
}

// Fetch lecturers and courses
$lecturers = $pdo->query("
    SELECT l.*, d.code AS department_code 
    FROM lecturers l 
    JOIN departments d ON l.department_id = d.id 
    ORDER BY l.name ASC
")->fetchAll();

$courses = $pdo->query("
    SELECT c.*, d.code AS department_code 
    FROM courses c 
    JOIN departments d ON c.department_id = d.id 
    ORDER BY c.course_code ASC
")->fetchAll();

// Fetch existing assignments
$assignments = $pdo->query("
    SELECT lc.id, l.name AS lecturer_name, l.email AS lecturer_email,
           c.course_code, c.course_name, d.name AS department_name, d.code AS department_code
    FROM lecturer_courses lc
    JOIN lecturers l ON lc.lecturer_id = l.id
    JOIN courses c ON lc.course_id = c.id
    JOIN departments d ON c.department_id = d.id
    ORDER BY d.code ASC, c.course_code ASC
")->fetchAll();

$page_title = 'Assign Lecturer to Course';
$base_path = '../';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="card">
    <h2>Assign Lecturer to Module</h2>
    <p style="color: var(--text-muted);">
        Assign qualified lecturers to teach departmental courses. 
        <strong>Rule:</strong> A lecturer can only teach courses in their own department (ICT or EEE).
    </p>

    <?php if ($success): ?><div class="alert alert-success"><?= htmlspecialchars($success) ?></div><?php endif; ?>
    <?php if ($error): ?><div class="alert alert-danger"><?= htmlspecialchars($error) ?></div><?php endif; ?>

    <form action="assign_lecturer.php" method="POST">
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap: 15px;">
            <div class="form-group">
                <label for="lecturer_id">Select Lecturer</label>
                <select name="lecturer_id" id="lecturer_id" class="form-control" required>
                    <option value="">-- Choose Lecturer --</option>
                    <?php foreach ($lecturers as $l): ?>
                        <option value="<?= $l['id'] ?>">
                            <?= htmlspecialchars($l['name']) ?> (Dept: <?= htmlspecialchars($l['department_code']) ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="course_id">Select Course / Module</label>
                <select name="course_id" id="course_id" class="form-control" required>
                    <option value="">-- Choose Module --</option>
                    <?php foreach ($courses as $c): ?>
                        <option value="<?= $c['id'] ?>">
                            [<?= htmlspecialchars($c['department_code']) ?>] <?= htmlspecialchars($c['course_code']) ?> - <?= htmlspecialchars($c['course_name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <button type="submit" name="assign_lecturer" class="btn btn-primary" style="margin-top: 10px;">Assign Lecturer</button>
    </form>
</div>

<div class="card">
    <h3>Current Lecturer Course Assignments (<?= count($assignments) ?>)</h3>
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Department</th>
                    <th>Module Code</th>
                    <th>Module Name</th>
                    <th>Assigned Lecturer</th>
                    <th>Lecturer Email</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($assignments)): ?>
                    <tr><td colspan="7" style="text-align: center; color: var(--text-muted);">No lecturer assignments created yet.</td></tr>
                <?php else: ?>
                    <?php foreach ($assignments as $idx => $a): ?>
                        <tr>
                            <td><?= $idx + 1 ?></td>
                            <td><span style="background: #e2e8f0; padding: 2px 8px; border-radius: 4px; font-weight: bold;"><?= htmlspecialchars($a['department_code']) ?></span></td>
                            <td><strong><?= htmlspecialchars($a['course_code']) ?></strong></td>
                            <td><?= htmlspecialchars($a['course_name']) ?></td>
                            <td><?= htmlspecialchars($a['lecturer_name']) ?></td>
                            <td><?= htmlspecialchars($a['lecturer_email']) ?></td>
                            <td>
                                <a href="assign_lecturer.php?remove=<?= $a['id'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('Remove this lecturer assignment?');">Unassign</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
