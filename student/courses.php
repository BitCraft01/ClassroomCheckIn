<?php
require '../db.php';
require_login();

$user = current_user();
if ($user['role'] !== 'student') {
    header('Location: ../instructor/dashboard.php');
    exit;
}

$msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['enroll_course_id'])) {
        $course_id = (int) $_POST['enroll_course_id'];
        try {
            $ins = $pdo->prepare("INSERT IGNORE INTO enrollments (student_id, course_id) VALUES (?,?)");
            $ins->execute([$user['id'], $course_id]);
            $msg = "Enrolled successfully.";
        } catch (Exception $e) {
            $msg = "Could not enroll in course.";
        }
    }

    if (isset($_POST['unenroll_course_id'])) {
        $course_id = (int) $_POST['unenroll_course_id'];
        try {
            $del = $pdo->prepare("DELETE FROM enrollments WHERE student_id = ? AND course_id = ?");
            $del->execute([$user['id'], $course_id]);
            $msg = "You have been unenrolled from the course.";
        } catch (Exception $e) {
            $msg = "Could not unenroll from course.";
        }
    }
}

$stmt = $pdo->prepare("
    SELECT c.*, 
           (SELECT COUNT(*) FROM enrollments e 
             WHERE e.course_id = c.id AND e.student_id = ?) AS is_enrolled
    FROM courses c
    ORDER BY c.code
");
$stmt->execute([$user['id']]);
$courses = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmt2 = $pdo->prepare("
    SELECT c.*, s.id AS session_id, s.session_date, s.start_time, s.end_time, s.classroom
    FROM enrollments e
    JOIN courses c ON e.course_id = c.id
    LEFT JOIN class_sessions s
      ON s.course_id = c.id AND s.session_date = CURDATE()
    WHERE e.student_id = ?
");
$stmt2->execute([$user['id']]);
$my_courses = $stmt2->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <title>My Courses – Classroom Check-In</title>
    <link rel="stylesheet" href="../assets/css/styles.css">
</head>

<body>
    <header>
        <div class="left">Classroom Check-In</div>
        <div class="right">
            <span><?php echo htmlspecialchars($user['name']); ?> (student)</span>
            <a href="courses.php">My Courses</a>
            <a href="../logout.php">Logout</a>
        </div>
    </header>
    <main>
        <div class="card">
            <h2>My Courses & Today’s Sessions</h2>
            <?php if ($msg): ?>
                <div class="message message-ok"><?php echo htmlspecialchars($msg); ?></div>
            <?php endif; ?>
            <table>
                <tr>
                    <th>Course</th>
                    <th>Today’s Session</th>
                    <th>Classroom</th>
                    <th>Action</th>
                </tr>
                <?php foreach ($my_courses as $c): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($c['code'] . ' – ' . $c['title']); ?></td>
                        <td>
                            <?php if ($c['session_id']): ?>
                                <?php echo htmlspecialchars($c['session_date']); ?>
                                (<?php echo htmlspecialchars(substr($c['start_time'], 0, 5)); ?>–<?php echo htmlspecialchars(substr($c['end_time'], 0, 5)); ?>)
                            <?php else: ?>
                                No session today
                            <?php endif; ?>
                        </td>
                        <td><?php echo htmlspecialchars($c['classroom'] ?? '-'); ?></td>
                        <td>
                            <?php if ($c['session_id']): ?>
                                <a class="link-btn" href="checkin.php?session_id=<?php echo $c['session_id']; ?>">Check In</a>
                            <?php else: ?>
                                —
                            <?php endif; ?>

                            <form method="post" style="display:inline;margin-left:6px;">
                                <input type="hidden" name="unenroll_course_id" value="<?php echo $c['id']; ?>">
                                <button type="submit">Unenroll</button>
                            </form>
                        </td>

                    </tr>
                <?php endforeach; ?>
            </table>
        </div>

        <div class="card" style="margin-top:20px;">
            <h2>Available Courses</h2>
            <table>
                <tr>
                    <th>Course</th>
                    <th>Instructor</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
                <?php foreach ($courses as $c): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($c['code'] . ' – ' . $c['title']); ?></td>
                        <td>
                            <?php
                            $inst = $pdo->prepare("SELECT name FROM users WHERE id = ?");
                            $inst->execute([$c['instructor_id']]);
                            echo htmlspecialchars($inst->fetchColumn() ?: 'Unknown');
                            ?>
                        </td>
                        <td><?php echo $c['is_enrolled'] ? 'Enrolled' : 'Not enrolled'; ?></td>
                        <td>
                            <?php if (!$c['is_enrolled']): ?>
                                <form method="post" style="display:inline">
                                    <input type="hidden" name="enroll_course_id" value="<?php echo $c['id']; ?>">
                                    <button type="submit">Enroll</button>
                                </form>
                            <?php else: ?>
                                —
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </table>
        </div>
    </main>
</body>

</html>
