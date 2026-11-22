<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../index.php');
    exit;
}

require_once __DIR__ . '/../config/db.php';

$success = '';
$error = '';

// Handle Add Department (Though system is restricted to ICT and EEE, admin can manage them)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_department'])) {
    $name = trim($_POST['name'] ?? '');
    $code = strtoupper(trim($_POST['code'] ?? ''));

    if (empty($name) || empty($code)) {
        $error = "Both department name and code are required.";
    } else {
        try {
            $stmt = $pdo->prepare("INSERT INTO departments (name, code) VALUES (?, ?)");
            $stmt->execute([$name, $code]);
            $success = "Department added successfully.";
        } catch (PDOException $e) {
            $error = "Failed to add department: " . $e->getMessage();
        }
    }
}

// Fetch all departments with lecturer and course counts
$stmt = $pdo->query("
    SELECT 
        d.*, 
        COUNT(DISTINCT l.id) AS lecturer_count,
        COUNT(DISTINCT c.id) AS course_count
    FROM departments d
    LEFT JOIN lecturers l ON l.department_id = d.id
    LEFT JOIN courses c ON c.department_id = d.id
    GROUP BY d.id
    ORDER BY d.id ASC
");
$departments = $stmt->fetchAll();

$page_title = 'Manage Departments';
$base_path = '../';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="card">
    <h2>Manage Departments</h2>
    <p style="color: var(--text-muted);">
        The college course evaluation system supports two primary departments: 
        <strong>ICT</strong> and <strong>EEE</strong>.
    </p>

    <?php if ($success): ?><div class="alert alert-success"><?= htmlspecialchars($success) ?></div><?php endif; ?>
    <?php if ($error): ?><div class="alert alert-danger"><?= htmlspecialchars($error) ?></div><?php endif; ?>

    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th># ID</th>
                    <th>Department Code</th>
                    <th>Department Name</th>
                    <th>Registered Lecturers</th>
                    <th>Registered Modules</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($departments as $d): ?>
                    <tr>
                        <td><?= htmlspecialchars($d['id']) ?></td>
                        <td><strong><?= htmlspecialchars($d['code']) ?></strong></td>
                        <td><?= htmlspecialchars($d['name']) ?></td>
                        <td><?= htmlspecialchars($d['lecturer_count']) ?></td>
                        <td><?= htmlspecialchars($d['course_count']) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
