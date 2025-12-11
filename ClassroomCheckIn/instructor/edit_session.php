<?php
require '../db.php';
require_login();
$user = current_user();
if ($user['role'] !== 'instructor') {
    header('Location: ../student/courses.php');
    exit;
}

$course_id = isset($_GET['course_id']) ? (int) $_GET['course_id'] : 0;
if (!$course_id)
    die('Course id required.');

$stmt = $pdo->prepare("SELECT * FROM courses WHERE id = ? AND instructor_id = ?");
$stmt->execute([$course_id, $user['id']]);
$course = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$course)
    die('Course not found.');

$msg = '';
$default_start = $course['meeting_start'] ?? '';
$default_end = $course['meeting_end'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $date = $_POST['session_date'] ?? '';
    $start = $_POST['start_time'] ?? '';
    $end = $_POST['end_time'] ?? '';
    $classroom = trim($_POST['classroom'] ?? '');

    if (!$date || !$start || !$end || $classroom === '') {
        $msg = "All fields are required.";
    } else {
        $ins = $pdo->prepare("
            INSERT INTO class_sessions (course_id, session_date, start_time, end_time, classroom)
            VALUES (?,?,?,?,?)
        ");
        $ins->execute([$course_id, $date, $start, $end, $classroom]);
        $msg = "Class session created.";
    }
}
?>
<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <title>Add Session – Classroom Check-In</title>
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
        <div class="card" style="max-width:500px;margin:40px auto;">
            <h2>Add Session for <?php echo htmlspecialchars($course['code'] . ' – ' . $course['title']); ?></h2>
            <p class="small">
                Meeting pattern: <?php echo htmlspecialchars($course['meeting_days'] ?? ''); ?>
                <?php if ($course['meeting_start'] && $course['meeting_end']): ?>
                    (<?php echo htmlspecialchars(substr($course['meeting_start'], 0, 5)); ?>–<?php echo htmlspecialchars(substr($course['meeting_end'], 0, 5)); ?>)
                <?php endif; ?>
            </p>
            <?php if ($msg): ?>
                <div class="message message-ok"><?php echo htmlspecialchars($msg); ?></div>
            <?php endif; ?>
            <form method="post">
                <label>Session Date</label>
                <input type="date" name="session_date" required>

                <label>Start Time</label>
                <input type="time" name="start_time" value="<?php echo htmlspecialchars($default_start); ?>" required>

                <label>End Time</label>
                <input type="time" name="end_time" value="<?php echo htmlspecialchars($default_end); ?>" required>

                <label>Classroom</label>
                <input type="text" name="classroom" required>

                <button type="submit">Create Session</button>
            </form>
        </div>
    </main>
</body>

</html>
