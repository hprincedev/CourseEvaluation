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
    $student_id = intval($_GET['delete']);
    try {
        // Fetch user_id before deletion
        $stmtU = $pdo->prepare("SELECT user_id FROM students WHERE id = ?");
        $stmtU->execute([$student_id]);
        $uid = $stmtU->fetchColumn();

        $stmt = $pdo->prepare("DELETE FROM students WHERE id = ?");
        $stmt->execute([$student_id]);

        if ($uid) {
            $pdo->prepare("DELETE FROM users WHERE id = ?")->execute([$uid]);
        }
        $success = "Student record deleted successfully.";
    } catch (PDOException $e) {
        $error = "Could not delete student: " . $e->getMessage();
    }
}

// Handle Edit Mode
$edit_mode = false;
$edit_student = null;

if (isset($_GET['edit'])) {
    $edit_id = intval($_GET['edit']);
    $stmt = $pdo->prepare("SELECT * FROM students WHERE id = ?");
    $stmt->execute([$edit_id]);
    $edit_student = $stmt->fetch();
    if ($edit_student) {
        $edit_mode = true;
    }
}

// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $reg_no = trim($_POST['reg_no'] ?? '');
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $department_id = intval($_POST['department_id'] ?? 0);

    if (empty($reg_no) || empty($name) || empty($department_id)) {
        $error = "Registration number, Name, and Department are required.";
    } else {
        if (isset($_POST['update_student'])) {
            $id = intval($_POST['id']);
            try {
                $stmt = $pdo->prepare("UPDATE students SET reg_no = ?, name = ?, email = ?, department_id = ? WHERE id = ?");
                $stmt->execute([$reg_no, $name, $email, $department_id, $id]);
                $success = "Student updated successfully.";
                $edit_mode = false;
            } catch (PDOException $e) {
                $error = "Update failed: " . $e->getMessage();
            }
        } elseif (isset($_POST['add_student'])) {
            $username = trim($_POST['username'] ?? '');
            $password = $_POST['password'] ?? '';

            if (empty($username) || empty($password)) {
                $error = "Please provide login username and password for the student.";
            } else {
                try {
                    $pdo->beginTransaction();

                    // Create user login account
                    $hashed = password_hash($password, PASSWORD_DEFAULT);
                    $stmtUser = $pdo->prepare("INSERT INTO users (username, password, role, department_id) VALUES (?, ?, 'student', ?)");
                    $stmtUser->execute([$username, $hashed, $department_id]);
                    $new_user_id = $pdo->lastInsertId();

                    // Create student profile
                    $stmtStudent = $pdo->prepare("INSERT INTO students (user_id, reg_no, name, email, department_id) VALUES (?, ?, ?, ?, ?)");
                    $stmtStudent->execute([$new_user_id, $reg_no, $name, $email, $department_id]);

                    $pdo->commit();
                    $success = "Student and login account created successfully.";
                } catch (PDOException $e) {
                    $pdo->rollBack();
                    $error = "Failed to add student: " . $e->getMessage();
                }
            }
        }
    }
}

// Fetch departments for dropdown
$departments = $pdo->query("SELECT * FROM departments ORDER BY code ASC")->fetchAll();

// Fetch all students
$students = $pdo->query("
    SELECT s.*, d.name AS department_name, d.code AS department_code, u.username
    FROM students s
    JOIN departments d ON s.department_id = d.id
    LEFT JOIN users u ON s.user_id = u.id
    ORDER BY s.id DESC
")->fetchAll();

$page_title = 'Manage Students';
$base_path = '../';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="card">
    <h2><?= $edit_mode ? 'Edit Student Details' : 'Register New Student' ?></h2>
    <?php if ($success): ?><div class="alert alert-success"><?= htmlspecialchars($success) ?></div><?php endif; ?>
    <?php if ($error): ?><div class="alert alert-danger"><?= htmlspecialchars($error) ?></div><?php endif; ?>

    <form action="students.php" method="POST">
        <?php if ($edit_mode): ?>
            <input type="hidden" name="id" value="<?= $edit_student['id'] ?>">
        <?php endif; ?>

        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 15px;">
            <div class="form-group">
                <label for="reg_no">Registration Number (Reg No)</label>
                <input type="text" name="reg_no" id="reg_no" class="form-control" required
                       value="<?= htmlspecialchars($edit_mode ? $edit_student['reg_no'] : '') ?>" placeholder="e.g. 2024/ICT/002">
            </div>

            <div class="form-group">
                <label for="name">Full Name</label>
                <input type="text" name="name" id="name" class="form-control" required
                       value="<?= htmlspecialchars($edit_mode ? $edit_student['name'] : '') ?>" placeholder="e.g. Jean Pierre Niyonsaba">
            </div>

            <div class="form-group">
                <label for="email">Email Address</label>
                <input type="email" name="email" id="email" class="form-control"
                       value="<?= htmlspecialchars($edit_mode ? $edit_student['email'] : '') ?>" placeholder="e.g. jean@example.com">
            </div>

            <div class="form-group">
                <label for="department_id">Department</label>
                <select name="department_id" id="department_id" class="form-control" required>
                    <option value="">-- Select Department --</option>
                    <?php foreach ($departments as $dept): ?>
                        <option value="<?= $dept['id'] ?>" <?= ($edit_mode && $edit_student['department_id'] == $dept['id']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($dept['name']) ?> (<?= htmlspecialchars($dept['code']) ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <?php if (!$edit_mode): ?>
            <h4 style="margin: 15px 0 10px; color: var(--primary);">Student Login Credentials</h4>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 15px;">
                <div class="form-group">
                    <label for="username">Login Username</label>
                    <input type="text" name="username" id="username" class="form-control" placeholder="e.g. jniyonsaba" required>
                </div>
                <div class="form-group">
                    <label for="password">Login Password</label>
                    <input type="password" name="password" id="password" class="form-control" placeholder="Password for student" required>
                </div>
            </div>
        <?php endif; ?>

        <button type="submit" name="<?= $edit_mode ? 'update_student' : 'add_student' ?>" class="btn btn-primary" style="margin-top: 10px;">
            <?= $edit_mode ? 'Save Changes' : 'Register Student' ?>
        </button>
        <?php if ($edit_mode): ?>
            <a href="students.php" class="btn btn-secondary" style="margin-top: 10px;">Cancel</a>
        <?php endif; ?>
    </form>
</div>

<div class="card">
    <h3>Registered Students (<?= count($students) ?>)</h3>
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Reg No</th>
                    <th>Full Name</th>
                    <th>Department</th>
                    <th>Email</th>
                    <th>Login Username</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($students)): ?>
                    <tr><td colspan="7" style="text-align: center; color: var(--text-muted);">No students registered yet.</td></tr>
                <?php else: ?>
                    <?php foreach ($students as $idx => $s): ?>
                        <tr>
                            <td><?= $idx + 1 ?></td>
                            <td><strong><?= htmlspecialchars($s['reg_no']) ?></strong></td>
                            <td><?= htmlspecialchars($s['name']) ?></td>
                            <td><?= htmlspecialchars($s['department_code']) ?></td>
                            <td><?= htmlspecialchars($s['email'] ?? '—') ?></td>
                            <td><code><?= htmlspecialchars($s['username'] ?? '—') ?></code></td>
                            <td>
                                <a href="students.php?edit=<?= $s['id'] ?>" class="btn btn-secondary btn-sm">Edit</a>
                                <a href="students.php?delete=<?= $s['id'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure you want to delete this student?');">Delete</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
