<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../index.php');
    exit;
}

require_once __DIR__ . '/../config/db.php';

$success = '';
$error = '';

// Handle Delete
if (isset($_GET['delete'])) {
    $course_id = intval($_GET['delete']);
    try {
        $stmt = $pdo->prepare("DELETE FROM courses WHERE id = ?");
        $stmt->execute([$course_id]);
        $success = "Course module deleted successfully.";
    } catch (PDOException $e) {
        $error = "Could not delete course (may have associated assignments or evaluations).";
    }
}

// Handle Edit Mode
$edit_mode = false;
$edit_course = null;

if (isset($_GET['edit'])) {
    $edit_id = intval($_GET['edit']);
    $stmt = $pdo->prepare("SELECT * FROM courses WHERE id = ?");
    $stmt->execute([$edit_id]);
    $edit_course = $stmt->fetch();
    if ($edit_course) {
        $edit_mode = true;
    }
}

// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $course_code = strtoupper(trim($_POST['course_code'] ?? ''));
    $course_name = trim($_POST['course_name'] ?? '');
    $department_id = intval($_POST['department_id'] ?? 0);

    if (empty($course_code) || empty($course_name) || empty($department_id)) {
        $error = "Course code, Course name, and Department are all required.";
    } else {
        if (isset($_POST['update_course'])) {
            $id = intval($_POST['id']);
            try {
                $stmt = $pdo->prepare("UPDATE courses SET course_code = ?, course_name = ?, department_id = ? WHERE id = ?");
                $stmt->execute([$course_code, $course_name, $department_id, $id]);
                $success = "Course module updated successfully.";
                $edit_mode = false;
            } catch (PDOException $e) {
                $error = "Update failed: " . $e->getMessage();
            }
        } elseif (isset($_POST['add_course'])) {
            try {
                $stmt = $pdo->prepare("INSERT INTO courses (course_code, course_name, department_id) VALUES (?, ?, ?)");
                $stmt->execute([$course_code, $course_name, $department_id]);
                $success = "Course module registered successfully.";
            } catch (PDOException $e) {
                $error = "Failed to add course: " . $e->getMessage();
            }
        }
    }
}

// Fetch departments for dropdown
$departments = $pdo->query("SELECT * FROM departments ORDER BY code ASC")->fetchAll();

// Fetch all courses
$courses = $pdo->query("
    SELECT c.*, d.name AS department_name, d.code AS department_code,
           (SELECT COUNT(*) FROM student_courses sc WHERE sc.course_id = c.id) AS enrolled_students,
           (SELECT l.name FROM lecturer_courses lc JOIN lecturers l ON lc.lecturer_id = l.id WHERE lc.course_id = c.id LIMIT 1) AS assigned_lecturer
    FROM courses c
    JOIN departments d ON c.department_id = d.id
    ORDER BY c.department_id ASC, c.course_code ASC
")->fetchAll();

$page_title = 'Manage Courses';
$base_path = '../';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="card">
    <h2><?= $edit_mode ? 'Edit Course / Module' : 'Register New Course / Module' ?></h2>
    <?php if ($success): ?><div class="alert alert-success"><?= htmlspecialchars($success) ?></div><?php endif; ?>
    <?php if ($error): ?><div class="alert alert-danger"><?= htmlspecialchars($error) ?></div><?php endif; ?>

    <form action="courses.php" method="POST">
        <?php if ($edit_mode): ?>
            <input type="hidden" name="id" value="<?= $edit_course['id'] ?>">
        <?php endif; ?>

        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 15px;">
            <div class="form-group">
                <label for="course_code">Module Code</label>
                <input type="text" name="course_code" id="course_code" class="form-control" required
                       value="<?= htmlspecialchars($edit_mode ? $edit_course['course_code'] : '') ?>" placeholder="e.g. ICT301 or EEE201">
            </div>

            <div class="form-group">
                <label for="course_name">Module Name / Title</label>
                <input type="text" name="course_name" id="course_name" class="form-control" required
                       value="<?= htmlspecialchars($edit_mode ? $edit_course['course_name'] : '') ?>" placeholder="e.g. Web Applications Development">
            </div>

            <div class="form-group">
                <label for="department_id">Department</label>
                <select name="department_id" id="department_id" class="form-control" required>
                    <option value="">-- Select Department --</option>
                    <?php foreach ($departments as $dept): ?>
                        <option value="<?= $dept['id'] ?>" <?= ($edit_mode && $edit_course['department_id'] == $dept['id']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($dept['name']) ?> (<?= htmlspecialchars($dept['code']) ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <button type="submit" name="<?= $edit_mode ? 'update_course' : 'add_course' ?>" class="btn btn-primary" style="margin-top: 10px;">
            <?= $edit_mode ? 'Save Changes' : 'Register Course' ?>
        </button>
        <?php if ($edit_mode): ?>
            <a href="courses.php" class="btn btn-secondary" style="margin-top: 10px;">Cancel</a>
        <?php endif; ?>
    </form>
</div>

<div class="card">
    <h3>Registered Courses / Modules (<?= count($courses) ?>)</h3>
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Code</th>
                    <th>Course Title</th>
                    <th>Department</th>
                    <th>Assigned Lecturer</th>
                    <th>Enrolled Students</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($courses)): ?>
                    <tr><td colspan="7" style="text-align: center; color: var(--text-muted);">No courses registered yet.</td></tr>
                <?php else: ?>
                    <?php foreach ($courses as $idx => $c): ?>
                        <tr>
                            <td><?= $idx + 1 ?></td>
                            <td><strong><?= htmlspecialchars($c['course_code']) ?></strong></td>
                            <td><?= htmlspecialchars($c['course_name']) ?></td>
                            <td><?= htmlspecialchars($c['department_code']) ?></td>
                            <td><?= htmlspecialchars($c['assigned_lecturer'] ?? '— Unassigned —') ?></td>
                            <td><?= htmlspecialchars($c['enrolled_students']) ?></td>
                            <td>
                                <a href="courses.php?edit=<?= $c['id'] ?>" class="btn btn-secondary btn-sm">Edit</a>
                                <a href="courses.php?delete=<?= $c['id'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure you want to delete this course module?');">Delete</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
