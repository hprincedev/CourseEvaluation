<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header('Location: ../index.php');
    exit;
}

require_once __DIR__ . '/../config/db.php';

$student_id = $_SESSION['student_id'] ?? null;
if (!$student_id) {
    die("Student profile not found. Please contact administrator.");
}

$course_id = intval($_GET['course_id'] ?? $_POST['course_id'] ?? 0);
$lecturer_id = intval($_GET['lecturer_id'] ?? $_POST['lecturer_id'] ?? 0);

if (empty($course_id) || empty($lecturer_id)) {
    header('Location: dashboard.php');
    exit;
}

// 1. Verify student is enrolled in this course
$stmtEnrolled = $pdo->prepare("SELECT * FROM student_courses WHERE student_id = ? AND course_id = ?");
$stmtEnrolled->execute([$student_id, $course_id]);
if (!$stmtEnrolled->fetch()) {
    die("Access denied: You are not enrolled in this module.");
}

// 2. Fetch Course, Lecturer, and Department details
$stmtInfo = $pdo->prepare("
    SELECT 
        c.course_code, c.course_name,
        d.name AS department_name, d.code AS department_code,
        l.name AS lecturer_name, l.email AS lecturer_email
    FROM courses c
    JOIN departments d ON c.department_id = d.id
    JOIN lecturer_courses lc ON lc.course_id = c.id
    JOIN lecturers l ON lc.lecturer_id = l.id
    WHERE c.id = ? AND l.id = ?
");
$stmtInfo->execute([$course_id, $lecturer_id]);
$info = $stmtInfo->fetch();

if (!$info) {
    die("Invalid course-lecturer assignment.");
}

// 3. Verify that student has not already evaluated this course & lecturer
$stmtCheck = $pdo->prepare("SELECT id FROM evaluations WHERE student_id = ? AND lecturer_id = ? AND course_id = ?");
$stmtCheck->execute([$student_id, $lecturer_id, $course_id]);
$alreadyEvaluated = $stmtCheck->fetch();

$error = '';

// Handle Evaluation Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_evaluation'])) {
    if ($alreadyEvaluated) {
        $error = "You have already submitted an evaluation for this module and lecturer.";
    } else {
        $ratings = [];
        $isValid = true;

        for ($i = 1; $i <= 8; $i++) {
            $val = intval($_POST["q$i"] ?? 0);
            if ($val < 1 || $val > 5) {
                $isValid = false;
                break;
            }
            $ratings["q$i"] = $val;
        }

        $comments = trim($_POST['comments'] ?? '');

        if (!$isValid) {
            $error = "Please provide a rating (1 to 5) for all 8 criteria.";
        } else {
            try {
                $stmtInsert = $pdo->prepare("
                    INSERT INTO evaluations 
                    (student_id, lecturer_id, course_id, q1, q2, q3, q4, q5, q6, q7, q8, comments) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");
                $stmtInsert->execute([
                    $student_id,
                    $lecturer_id,
                    $course_id,
                    $ratings['q1'],
                    $ratings['q2'],
                    $ratings['q3'],
                    $ratings['q4'],
                    $ratings['q5'],
                    $ratings['q6'],
                    $ratings['q7'],
                    $ratings['q8'],
                    $comments
                ]);

                header("Location: confirmation.php?course_id=$course_id&lecturer_id=$lecturer_id");
                exit;
            } catch (PDOException $e) {
                if ($e->getCode() == 23000) {
                    $error = "Duplicate evaluation: You have already evaluated this lecturer for this course.";
                } else {
                    $error = "Failed to record evaluation: " . $e->getMessage();
                }
            }
        }
    }
}

// The 8 official assessment criteria
$criteria = [
    1 => "The lecturer demonstrates adequate knowledge of the module.",
    2 => "The lecturer explains concepts clearly.",
    3 => "The lecturer prepares adequately for lessons.",
    4 => "The lecturer uses appropriate teaching methods.",
    5 => "The lecturer encourages student participation.",
    6 => "The lecturer respects and supports students.",
    7 => "The lecturer completes the module content according to the plan.",
    8 => "The learning materials provided are useful."
];

$page_title = 'Evaluate Course Module';
$base_path = '../';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="card">
    <h2>Student Course Evaluation Form</h2>
    <p style="color: var(--text-muted);">
        Please evaluate the lecturer and course objectively. Your honest feedback helps RP Musanze College maintain high standards of teaching and learning.
    </p>

    <!-- Header Details -->
    <div style="background: #f8f9fa; border: 1px solid var(--border-color); border-radius: 6px; padding: 16px; margin: 15px 0 20px;">
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 12px;">
            <div>
                <strong style="color: var(--text-muted); font-size: 0.85rem; text-transform: uppercase;">Department:</strong><br>
                <span><?= htmlspecialchars($info['department_name']) ?> (<?= htmlspecialchars($info['department_code']) ?>)</span>
            </div>
            <div>
                <strong style="color: var(--text-muted); font-size: 0.85rem; text-transform: uppercase;">Module:</strong><br>
                <strong><?= htmlspecialchars($info['course_code']) ?></strong> - <?= htmlspecialchars($info['course_name']) ?>
            </div>
            <div>
                <strong style="color: var(--text-muted); font-size: 0.85rem; text-transform: uppercase;">Lecturer:</strong><br>
                <span><?= htmlspecialchars($info['lecturer_name']) ?></span>
            </div>
        </div>
    </div>

    <?php if ($alreadyEvaluated): ?>
        <div class="alert alert-info">
            <strong>&#10004; Evaluation Completed:</strong> You have already submitted an evaluation for this course and lecturer. Duplicate submissions are not allowed.
        </div>
        <a href="dashboard.php" class="btn btn-primary">Return to My Courses</a>
    <?php else: ?>

        <?php if ($error): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <div style="background: #eef2f7; padding: 10px 15px; border-radius: 6px; margin-bottom: 20px; font-size: 0.9rem;">
            <strong>Rating Scale:</strong> 
            <span style="margin-left: 10px;">1 = Very Poor</span> | 
            <span>2 = Poor</span> | 
            <span>3 = Average</span> | 
            <span>4 = Good</span> | 
            <span>5 = Excellent</span>
        </div>

        <form action="evaluate.php" method="POST" id="evalForm">
            <input type="hidden" name="course_id" value="<?= $course_id ?>">
            <input type="hidden" name="lecturer_id" value="<?= $lecturer_id ?>">

            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th style="width: 40px;">#</th>
                            <th>Assessment Criteria</th>
                            <th style="text-align: center; width: 60px;">1<br><small style="font-weight:normal; font-size: 11px;">Very Poor</small></th>
                            <th style="text-align: center; width: 60px;">2<br><small style="font-weight:normal; font-size: 11px;">Poor</small></th>
                            <th style="text-align: center; width: 60px;">3<br><small style="font-weight:normal; font-size: 11px;">Average</small></th>
                            <th style="text-align: center; width: 60px;">4<br><small style="font-weight:normal; font-size: 11px;">Good</small></th>
                            <th style="text-align: center; width: 60px;">5<br><small style="font-weight:normal; font-size: 11px;">Excellent</small></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($criteria as $num => $criterion): ?>
                            <tr>
                                <td><?= $num ?></td>
                                <td><?= htmlspecialchars($criterion) ?></td>
                                <?php for ($r = 1; $r <= 5; $r++): ?>
                                    <td style="text-align: center;">
                                        <input type="radio" name="q<?= $num ?>" value="<?= $r ?>" required 
                                            <?= (isset($_POST["q$num"]) && $_POST["q$num"] == $r) ? 'checked' : '' ?>>
                                    </td>
                                <?php endfor; ?>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <div class="form-group" style="margin-top: 25px;">
                <label for="comments">
                    <strong>Additional Comments or Recommendations:</strong>
                    <span style="color: var(--text-muted); font-weight: normal; font-size: 0.85rem;">(Suggestions for improvement, strengths, general feedback)</span>
                </label>
                <textarea name="comments" id="comments" rows="4" class="form-control" placeholder="Write any constructive feedback or recommendations here..."><?= htmlspecialchars($_POST['comments'] ?? '') ?></textarea>
            </div>

            <div style="margin-top: 20px;">
                <button type="submit" name="submit_evaluation" class="btn btn-primary" onclick="return confirm('Are you sure you want to submit this evaluation? Once submitted, it cannot be edited.');">
                    Submit Evaluation
                </button>
                <a href="dashboard.php" class="btn btn-secondary">Cancel</a>
            </div>
        </form>

    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
