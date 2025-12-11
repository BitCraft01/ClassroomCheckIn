<?php
require '../db.php';
require_login();
$user = current_user();
if ($user['role'] !== 'instructor') {
    header('Location: ../student/courses.php');
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM courses WHERE instructor_id = ? ORDER BY code");
$stmt->execute([$user['id']]);
$courses = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmt2 = $pdo->prepare("
    SELECT s.*, c.code, c.title
    FROM class_sessions s
    JOIN courses c ON s.course_id = c.id
    WHERE c.instructor_id = ?
    ORDER BY s.session_date, s.start_time
");
$stmt2->execute([$user['id']]);
$sessions = $stmt2->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Instructor Dashboard – Classroom Check-In</title>
    <link rel="stylesheet" href="../assets/css/styles.css">
</head>
<body>
<header>
    <div class="left">Classroom Check-In</div>
    <div class="right">
        <span><?php echo htmlspecialchars($user['name']); ?> (instructor)</span>
        <a href="dashboard.php">Dashboard</a>
        <a href="../logout.php">Logout</a>
    </div>
</header>
<main>
    <div class="card">
        <h2>My Courses</h2>
        <p><a class="link-btn" href="edit_course.php">+ Create Course</a></p>
        <table>
            <tr>
                <th>Code</th>
                <th>Title</th>
                <th>Actions</th>
            </tr>
            <?php foreach ($courses as $c): ?>
                <tr>
                    <td><?php echo htmlspecialchars($c['code']); ?></td>
                    <td><?php echo htmlspecialchars($c['title']); ?></td>
                    <td>
                        <a class="link-btn" href="edit_course.php?id=<?php echo $c['id']; ?>">Edit</a>
                        <a class="link-btn" href="edit_session.php?course_id=<?php echo $c['id']; ?>">Add Session</a>
                    </td>
                </tr>
            <?php endforeach; ?>
        </table>
    </div>

    <div class="card" style="margin-top:20px;">
        <h2>Upcoming Class Sessions</h2>
        <table>
            <tr>
                <th>Course</th>
                <th>Date</th>
                <th>Time</th>
                <th>Classroom</th>
                <th>Attendance</th>
            </tr>
            <?php foreach ($sessions as $s): ?>
                <tr>
                    <td><?php echo htmlspecialchars($s['code'].' – '.$s['title']); ?></td>
                    <td><?php echo htmlspecialchars($s['session_date']); ?></td>
                    <td><?php echo htmlspecialchars(substr($s['start_time'],0,5)); ?>–<?php echo htmlspecialchars(substr($s['end_time'],0,5)); ?></td>
                    <td><?php echo htmlspecialchars($s['classroom']); ?></td>
                    <td>
                        <a class="link-btn" href="view_attendance.php?session_id=<?php echo $s['id']; ?>">View attendance</a>
                    </td>
                </tr>
            <?php endforeach; ?>
        </table>
    </div>
</main>
</body>
</html>
