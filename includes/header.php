<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$base_path = $base_path ?? '';
$page_title = $page_title ?? 'Course Evaluation Management System';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($page_title) ?> - RP Musanze College</title>
    <link rel="stylesheet" href="<?= $base_path ?>style.css">
</head>
<body>
<nav class="navbar">
    <a href="<?= $base_path ?>index.php" class="brand">CEMsystem - RP Musanze College</a>
    <div class="nav-links">
        <?php if (isset($_SESSION['user_id'])): ?>
            <?php if ($_SESSION['role'] === 'admin'): ?>
                <a href="<?= $base_path ?>admin/dashboard.php">Dashboard</a>
                <a href="<?= $base_path ?>admin/departments.php">Departments</a>
                <a href="<?= $base_path ?>admin/lecturers.php">Lecturers</a>
                <a href="<?= $base_path ?>admin/students.php">Students</a>
                <a href="<?= $base_path ?>admin/courses.php">Courses</a>
                <a href="<?= $base_path ?>admin/assign_lecturer.php">Assign Lecturer</a>
                <a href="<?= $base_path ?>admin/assign_student.php">Assign Student</a>
            <?php elseif ($_SESSION['role'] === 'hod'): ?>
                <a href="<?= $base_path ?>hod/dashboard.php">Dashboard</a>
                <a href="<?= $base_path ?>hod/lecturer_reports.php">Lecturer Reports</a>
                <a href="<?= $base_path ?>hod/course_reports.php">Course Reports</a>
                <a href="<?= $base_path ?>hod/evaluation_results.php">Evaluation Results</a>
            <?php elseif ($_SESSION['role'] === 'student'): ?>
                <a href="<?= $base_path ?>student/dashboard.php">My Courses</a>
            <?php endif; ?>
            
            <span class="user-info">
                <?= htmlspecialchars($_SESSION['username']) ?> (<?= strtoupper($_SESSION['role']) ?>)
            </span>
            <a href="<?= $base_path ?>logout.php" style="background: rgba(220,53,69,0.8);">Logout</a>
        <?php else: ?>
            <a href="<?= $base_path ?>index.php">Login</a>
        <?php endif; ?>
    </div>
</nav>
<div class="container">
