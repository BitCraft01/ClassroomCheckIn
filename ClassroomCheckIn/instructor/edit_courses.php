<?php
require '../db.php';
require_login();
$user = current_user();
if ($user['role'] !== 'instructor') {
    header('Location: ../student/courses.php');
    exit;
}

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$code = '';
$title = '';
$meeting_days = '';
$meeting_start = '';
$meeting_end = '';
$msg = '';

if ($id) {
    $stmt = $pdo->prepare("SELECT * FROM courses WHERE id = ? AND instructor_id = ?");
    $stmt->execute([$id, $user['id']]);
    $course = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$course) {
        die('Course not found.');
    }
    $code = $course['code'];
    $title = $course['title'];
    $meeting_days = $course['meeting_days'] ?? '';
    $meeting_start = $course['meeting_start'] ?? '';
    $meeting_end = $course['meeting_end'] ?? '';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $code = trim($_POST['code'] ?? '');
    $title = trim($_POST['title'] ?? '');
    $meeting_days = trim($_POST['meeting_days'] ?? '');
    $meeting_start = $_POST['meeting_start'] ?? '';
    $meeting_end = $_POST['meeting_end'] ?? '';

    if ($code === '' || $title === '') {
        $msg = "Course code and title are required.";
    } else {
        if ($id) {
            $upd = $pdo->prepare("
                UPDATE courses 
                SET code = ?, title = ?, meeting_days = ?, meeting_start = ?, meeting_end = ?
                WHERE id = ? AND instructor_id = ?
            ");
            $upd->execute([$code, $title, $meeting_days ?: null, $meeting_start ?: null, $meeting_end ?: null, $id, $user['id']]);
            $msg = "Course updated.";
        } else {
            $ins = $pdo->prepare("
                INSERT INTO courses (code, title, instructor_id, meeting_days, meeting_start, meeting_end)
                VALUES (?,?,?,?,?,?)
            ");
            $ins->execute([
                $code,
                $title,
                $user['id'],
                $meeting_days ?: null,
                $meeting_start ?: null,
                $meeting_end ?: null
            ]);
            $id = $pdo->lastInsertId();
            $msg = "Course created.";
        }
    }
}
?>
<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <title><?php echo $id ? 'Edit Course' : 'Create Course'; ?> – Classroom Check-In</title>
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
            <h2><?php echo $id ? 'Edit Course' : 'Create Course'; ?></h2>
            <?php if ($msg): ?>
                <div class="message message-ok"><?php echo htmlspecialchars($msg); ?></div>
            <?php endif; ?>
            <form method="post">
                <label>Course Code</label>
                <input type="text" name="code" value="<?php echo htmlspecialchars($code); ?>" required>

                <label>Course Title</label>
                <input type="text" name="title" value="<?php echo htmlspecialchars($title); ?>" required>

                <label>Meeting Days <span class="small">(e.g. Tue/Thu or Mon/Wed)</span></label>
                <input type="text" name="meeting_days" value="<?php echo htmlspecialchars($meeting_days); ?>">

                <label>Meeting Start Time <span class="small">(e.g. 14:00)</span></label>
                <input type="time" name="meeting_start" value="<?php echo htmlspecialchars($meeting_start); ?>">

                <label>Meeting End Time <span class="small">(e.g. 15:15)</span></label>
                <input type="time" name="meeting_end" value="<?php echo htmlspecialchars($meeting_end); ?>">

                <button type="submit"><?php echo $id ? 'Save Changes' : 'Create Course'; ?></button>
            </form>
        </div>
    </main>
</body>

</html>
