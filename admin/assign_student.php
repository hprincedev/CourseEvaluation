<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../index.php');
    exit;
}

require_once __DIR__ . '/../config/db.php';

$success = '';
$error = '';

// Handle unenrollment
if (isset($_GET['remove'])) {
    $assign_id = intval($_GET['remove']);
    $pdo->prepare("DELETE FROM student_courses WHERE id = ?")->execute([$assign_id]);
    $success = "Student un-enrolled from course successfully.";
}

// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['assign_student'])) {
    $student_id = intval($_POST['student_id'] ?? 0);
    $course_id = intval($_POST['course_id'] ?? 0);

    if (empty($student_id) || empty($course_id)) {
        $error = "Please select both a student and a course.";
    } else {
        // Enforce department matching rule
        $stmtS = $pdo->prepare("SELECT department_id, name FROM students WHERE id = ?");
        $stmtS->execute([$student_id]);
        $student = $stmtS->fetch();

        $stmtC = $pdo->prepare("SELECT department_id, course_code FROM courses WHERE id = ?");
        $stmtC->execute([$course_id]);
        $course = $stmtC->fetch();

        if (!$student || !$course) {
            $error = "Invalid student or course selected.";
        } elseif ($student['department_id'] !== $course['department_id']) {
            $error = "Department mismatch: Students can only be assigned to courses belonging to their department!";
        } else {
            try {
                $stmt = $pdo->prepare("INSERT INTO student_courses (student_id, course_id) VALUES (?, ?)");
                $stmt->execute([$student_id, $course_id]);
                $success = "Student successfully enrolled into the course.";
            } catch (PDOException $e) {
                if ($e->getCode() == 23000) {
                    $error = "This student is already enrolled in this course.";
                } else {
                    $error = "Enrollment failed: " . $e->getMessage();
                }
            }
        }
    }
}

// Fetch students and courses
$students = $pdo->query("
    SELECT s.*, d.code AS department_code 
    FROM students s 
    JOIN departments d ON s.department_id = d.id 
    ORDER BY s.name ASC
")->fetchAll();

$courses = $pdo->query("
    SELECT c.*, d.code AS department_code 
    FROM courses c 
    JOIN departments d ON c.department_id = d.id 
    ORDER BY c.course_code ASC
")->fetchAll();

// Fetch current enrollments
$enrollments = $pdo->query("
    SELECT sc.id, s.reg_no, s.name AS student_name,
           c.course_code, c.course_name, d.code AS department_code
    FROM student_courses sc
    JOIN students s ON sc.student_id = s.id
    JOIN courses c ON sc.course_id = c.id
    JOIN departments d ON c.department_id = d.id
    ORDER BY d.code ASC, c.course_code ASC, s.name ASC
")->fetchAll();

$page_title = 'Assign Student to Course';
$base_path = '../';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="card">
    <h2>Enroll Student into Module</h2>
    <p style="color: var(--text-muted);">
        Assign students to modules they study so they can submit course evaluations.
        <strong>Rule:</strong> Students must be enrolled in courses of their respective department.
    </p>

    <?php if ($success): ?><div class="alert alert-success"><?= htmlspecialchars($success) ?></div><?php endif; ?>
    <?php if ($error): ?><div class="alert alert-danger"><?= htmlspecialchars($error) ?></div><?php endif; ?>

    <form action="assign_student.php" method="POST">
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap: 15px;">
            <div class="form-group">
                <label for="student_id">Select Student</label>
                <select name="student_id" id="student_id" class="form-control" required>
                    <option value="">-- Choose Student --</option>
                    <?php foreach ($students as $s): ?>
                        <option value="<?= $s['id'] ?>">
                            <?= htmlspecialchars($s['name']) ?> (<?= htmlspecialchars($s['reg_no']) ?> - <?= htmlspecialchars($s['department_code']) ?>)
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

        <button type="submit" name="assign_student" class="btn btn-primary" style="margin-top: 10px;">Enroll Student</button>
    </form>
</div>

<div class="card">
    <h3>Current Student Enrollments (<?= count($enrollments) ?>)</h3>
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Department</th>
                    <th>Reg No</th>
                    <th>Student Name</th>
                    <th>Course Code</th>
                    <th>Course Name</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($enrollments)): ?>
                    <tr><td colspan="7" style="text-align: center; color: var(--text-muted);">No student enrollments created yet.</td></tr>
                <?php else: ?>
                    <?php foreach ($enrollments as $idx => $e): ?>
                        <tr>
                            <td><?= $idx + 1 ?></td>
                            <td><span style="background: #e2e8f0; padding: 2px 8px; border-radius: 4px; font-weight: bold;"><?= htmlspecialchars($e['department_code']) ?></span></td>
                            <td><?= htmlspecialchars($e['reg_no']) ?></td>
                            <td><strong><?= htmlspecialchars($e['student_name']) ?></strong></td>
                            <td><?= htmlspecialchars($e['course_code']) ?></td>
                            <td><?= htmlspecialchars($e['course_name']) ?></td>
                            <td>
                                <a href="assign_student.php?remove=<?= $e['id'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('Remove this student enrollment?');">Unenroll</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
