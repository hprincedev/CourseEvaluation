<?php
session_start();
require_once __DIR__ . '/config/db.php';

// Redirect if already logged in
if (isset($_SESSION['user_id']) && isset($_SESSION['role'])) {
    if ($_SESSION['role'] === 'admin') {
        header('Location: admin/dashboard.php');
        exit;
    } elseif ($_SESSION['role'] === 'hod') {
        header('Location: hod/dashboard.php');
        exit;
    } elseif ($_SESSION['role'] === 'student') {
        header('Location: student/dashboard.php');
        exit;
    }
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($password)) {
        $error = "Please enter both username and password.";
    } else {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            // Login successful
            session_regenerate_id(true);
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['department_id'] = $user['department_id'];

            // If student, attach student table record
            if ($user['role'] === 'student') {
                $stmtStudent = $pdo->prepare("SELECT * FROM students WHERE user_id = ?");
                $stmtStudent->execute([$user['id']]);
                $student = $stmtStudent->fetch();
                if ($student) {
                    $_SESSION['student_id'] = $student['id'];
                    $_SESSION['student_name'] = $student['name'];
                    $_SESSION['department_id'] = $student['department_id'];
                }
                header('Location: student/dashboard.php');
                exit;
            } elseif ($user['role'] === 'hod') {
                header('Location: hod/dashboard.php');
                exit;
            } else {
                header('Location: admin/dashboard.php');
                exit;
            }
        } else {
            $error = "Invalid username or password.";
        }
    }
}

$page_title = 'Login';
$base_path = '';
require_once __DIR__ . '/includes/header.php';
?>

<div class="login-wrapper">
    <div class="login-card">
        <h2 style="text-align: center; margin-bottom: 8px;">Welcome Back</h2>
        <p style="text-align: center; color: var(--text-muted); margin-bottom: 24px; font-size: 0.9rem;">
            Sign in to RP Musanze Course Evaluation System
        </p>

        <?php if (!empty($error)): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form action="index.php" method="POST">
            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" name="username" id="username" class="form-control" placeholder="e.g. admin or student1" required autofocus value="<?= htmlspecialchars($_POST['username'] ?? '') ?>">
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" name="password" id="password" class="form-control" placeholder="Enter your password" required>
            </div>

            <button type="submit" class="btn btn-primary" style="width: 100%; margin-top: 10px;">Login</button>
        </form>

        <div style="margin-top: 25px; background: #e9ecef; padding: 12px; border-radius: 6px; font-size: 0.85rem;">
            <strong>Demo Accounts:</strong><br>
            • Admin: <code>admin</code> / <code>admin123</code><br>
            • HoD ICT: <code>hod_ict</code> / <code>hod123</code><br>
            • HoD EEE: <code>hod_eee</code> / <code>hod123</code><br>
            • Student: <code>student1</code> / <code>student123</code>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
