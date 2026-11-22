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
    $lecturer_id = intval($_GET['delete']);
    try {
        $stmt = $pdo->prepare("DELETE FROM lecturers WHERE id = ?");
        $stmt->execute([$lecturer_id]);
        $success = "Lecturer removed successfully.";
    } catch (PDOException $e) {
        $error = "Could not delete lecturer (may have existing evaluations or courses).";
    }
}

// Handle Add / Edit
$edit_mode = false;
$edit_lecturer = null;

if (isset($_GET['edit'])) {
    $edit_id = intval($_GET['edit']);
    $stmt = $pdo->prepare("SELECT * FROM lecturers WHERE id = ?");
    $stmt->execute([$edit_id]);
    $edit_lecturer = $stmt->fetch();
    if ($edit_lecturer) {
        $edit_mode = true;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $department_id = intval($_POST['department_id'] ?? 0);

    if (empty($name) || empty($email) || empty($department_id)) {
        $error = "Please fill in all fields.";
    } else {
        if (isset($_POST['update_lecturer'])) {
            $id = intval($_POST['id']);
            try {
                $stmt = $pdo->prepare("UPDATE lecturers SET name = ?, email = ?, department_id = ? WHERE id = ?");
                $stmt->execute([$name, $email, $department_id, $id]);
                $success = "Lecturer updated successfully.";
                $edit_mode = false;
            } catch (PDOException $e) {
                $error = "Update failed: " . $e->getMessage();
            }
        } elseif (isset($_POST['add_lecturer'])) {
            try {
                $stmt = $pdo->prepare("INSERT INTO lecturers (name, email, department_id) VALUES (?, ?, ?)");
                $stmt->execute([$name, $email, $department_id]);
                $success = "Lecturer added successfully.";
            } catch (PDOException $e) {
                $error = "Failed to add lecturer: " . $e->getMessage();
            }
        }
    }
}

// Fetch departments for dropdown
$departments = $pdo->query("SELECT * FROM departments ORDER BY code ASC")->fetchAll();

// Fetch all lecturers with department
$lecturers = $pdo->query("
    SELECT l.*, d.name AS department_name, d.code AS department_code 
    FROM lecturers l
    JOIN departments d ON l.department_id = d.id
    ORDER BY l.name ASC
")->fetchAll();

$page_title = 'Manage Lecturers';
$base_path = '../';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="card">
    <h2><?= $edit_mode ? 'Edit Lecturer' : 'Register New Lecturer' ?></h2>
    <?php if ($success): ?><div class="alert alert-success"><?= htmlspecialchars($success) ?></div><?php endif; ?>
    <?php if ($error): ?><div class="alert alert-danger"><?= htmlspecialchars($error) ?></div><?php endif; ?>

    <form action="lecturers.php" method="POST">
        <?php if ($edit_mode): ?>
            <input type="hidden" name="id" value="<?= $edit_lecturer['id'] ?>">
        <?php endif; ?>

        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 15px;">
            <div class="form-group">
                <label for="name">Full Name</label>
                <input type="text" name="name" id="name" class="form-control" required 
                       value="<?= htmlspecialchars($edit_mode ? $edit_lecturer['name'] : '') ?>" placeholder="e.g. Dr. Alexis Kwizera">
            </div>

            <div class="form-group">
                <label for="email">Email Address</label>
                <input type="email" name="email" id="email" class="form-control" required 
                       value="<?= htmlspecialchars($edit_mode ? $edit_lecturer['email'] : '') ?>" placeholder="e.g. alexis.k@rp.ac.rw">
            </div>

            <div class="form-group">
                <label for="department_id">Department</label>
                <select name="department_id" id="department_id" class="form-control" required>
                    <option value="">-- Select Department --</option>
                    <?php foreach ($departments as $dept): ?>
                        <option value="<?= $dept['id'] ?>" <?= ($edit_mode && $edit_lecturer['department_id'] == $dept['id']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($dept['name']) ?> (<?= htmlspecialchars($dept['code']) ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <button type="submit" name="<?= $edit_mode ? 'update_lecturer' : 'add_lecturer' ?>" class="btn btn-primary" style="margin-top: 10px;">
            <?= $edit_mode ? 'Save Changes' : 'Add Lecturer' ?>
        </button>
        <?php if ($edit_mode): ?>
            <a href="lecturers.php" class="btn btn-secondary" style="margin-top: 10px;">Cancel</a>
        <?php endif; ?>
    </form>
</div>

<div class="card">
    <h3>Registered Lecturers (<?= count($lecturers) ?>)</h3>
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Department</th>
                    <th>Registered At</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($lecturers)): ?>
                    <tr><td colspan="6" style="text-align: center; color: var(--text-muted);">No lecturers registered yet.</td></tr>
                <?php else: ?>
                    <?php foreach ($lecturers as $idx => $l): ?>
                        <tr>
                            <td><?= $idx + 1 ?></td>
                            <td><strong><?= htmlspecialchars($l['name']) ?></strong></td>
                            <td><?= htmlspecialchars($l['email']) ?></td>
                            <td><?= htmlspecialchars($l['department_code']) ?></td>
                            <td><?= htmlspecialchars(date('M d, Y', strtotime($l['created_at']))) ?></td>
                            <td>
                                <a href="lecturers.php?edit=<?= $l['id'] ?>" class="btn btn-secondary btn-sm">Edit</a>
                                <a href="lecturers.php?delete=<?= $l['id'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure you want to remove this lecturer?');">Delete</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
