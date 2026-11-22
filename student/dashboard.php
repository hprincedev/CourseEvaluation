<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header('Location: ../index.php');
    exit;
}

require_once __DIR__ . '/../config/db.php';

$student_id = $_SESSION['student_id'] ?? null;

// Query courses assigned to this student with assigned lecturers
$courses = [];
if ($student_id) {
    $stmt = $pdo->prepare("
        SELECT 
            c.id AS course_id,
            c.course_code,
            c.course_name,
            d.name AS department_name,
            l.id AS lecturer_id,
            l.name AS lecturer_name,
            e.id AS evaluation_id
        FROM student_courses sc
        JOIN courses c ON sc.course_id = c.id
        JOIN departments d ON c.department_id = d.id
        LEFT JOIN lecturer_courses lc ON lc.course_id = c.id
        LEFT JOIN lecturers l ON lc.lecturer_id = l.id
        LEFT JOIN evaluations e ON (e.student_id = ? AND e.course_id = c.id AND (e.lecturer_id = l.id OR l.id IS NULL))
        WHERE sc.student_id = ?
        ORDER BY c.course_code ASC
    ");
    $stmt->execute([$student_id, $student_id]);
    $courses = $stmt->fetchAll();
}

$page_title = 'Student Dashboard';
$base_path = '../';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="card">
    <h2>Student Dashboard</h2>
    <p style="color: var(--text-muted);">
        Welcome, <strong><?= htmlspecialchars($_SESSION['student_name'] ?? $_SESSION['username']) ?></strong>.
        Below are the modules assigned to you for evaluation.
    </p>
</div>

<div class="card">
    <h3>My Assigned Modules</h3>
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th>Module Code</th>
                    <th>Module Name</th>
                    <th>Department</th>
                    <th>Lecturer</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($courses)): ?>
                    <tr>
                        <td colspan="6" style="text-align: center; color: var(--text-muted);">
                            No courses have been assigned to you yet. Please check back later.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($courses as $c): ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($c['course_code']) ?></strong></td>
                            <td><?= htmlspecialchars($c['course_name']) ?></td>
                            <td><?= htmlspecialchars($c['department_name']) ?></td>
                            <td><?= htmlspecialchars($c['lecturer_name'] ?? 'Not yet assigned') ?></td>
                            <td>
                                <?php if ($c['evaluation_id']): ?>
                                    <span style="color: var(--success); font-weight: bold;">&#10004; Evaluated</span>
                                <?php else: ?>
                                    <span style="color: #e67e22; font-weight: bold;">&#9679; Pending</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($c['evaluation_id']): ?>
                                    <button class="btn btn-secondary btn-sm" disabled>Completed</button>
                                <?php elseif (empty($c['lecturer_id'])): ?>
                                    <button class="btn btn-secondary btn-sm" disabled title="No lecturer assigned to this module yet">Evaluate</button>
                                <?php else: ?>
                                    <a href="evaluate.php?course_id=<?= $c['course_id'] ?>&lecturer_id=<?= $c['lecturer_id'] ?>" class="btn btn-primary btn-sm">Evaluate</a>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
