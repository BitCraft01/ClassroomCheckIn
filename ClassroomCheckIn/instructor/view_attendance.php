<?php
require '../db.php';
require_login();
$user = current_user();
if ($user['role'] !== 'instructor') {
    header('Location: ../student/courses.php');
    exit;
}

$session_id = isset($_GET['session_id']) ? (int)$_GET['session_id'] : 0;

$stmt = $pdo->prepare("
    SELECT s.*, c.code, c.title, c.instructor_id, c.id AS course_id
    FROM class_sessions s
    JOIN courses c ON s.course_id = c.id
    WHERE s.id = ?
");
$stmt->execute([$session_id]);
$session = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$session || $session['instructor_id'] != $user['id']) {
    die('Not authorized for this session.');
}

$msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!empty($_POST['update_id']) && isset($_POST['new_status'])) {
        $aid = (int)$_POST['update_id'];
        $st  = ($_POST['new_status'] === 'absent') ? 'absent' : 'present';
        $up  = $pdo->prepare("UPDATE attendance_records SET status = ? WHERE id = ?");
        $up->execute([$st, $aid]);
        $msg = "Attendance status updated.";
    }
    if (!empty($_POST['delete_id'])) {
        $aid = (int)$_POST['delete_id'];
        $del = $pdo->prepare("DELETE FROM attendance_records WHERE id = ?");
        $del->execute([$aid]);
        $msg = "Attendance record deleted.";
    }
}

$stmt = $pdo->prepare("
    SELECT u.id AS student_id, u.name,
           ar.id AS attendance_id, ar.status, ar.checkin_time, ar.location_note
    FROM enrollments e
    JOIN users u ON e.student_id = u.id
    LEFT JOIN attendance_records ar
      ON ar.student_id = u.id AND ar.class_session_id = ?
    WHERE e.course_id = ?
");
$stmt->execute([$session_id, $session['course_id']]);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>View Attendance – Classroom Check-In</title>
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
        <h2>Attendance – <?php echo htmlspecialchars($session['code'].' '.$session['title']); ?>
            (<?php echo htmlspecialchars($session['session_date']); ?>)</h2>
        <?php if ($msg): ?>
            <div class="message message-ok"><?php echo htmlspecialchars($msg); ?></div>
        <?php endif; ?>
        <table>
            <tr>
                <th>Student</th>
                <th>Status</th>
                <th>Check-in time</th>
                <th>Location</th>
                <th>Actions</th>
            </tr>
            <?php foreach ($rows as $r): ?>
                <tr>
                    <td><?php echo htmlspecialchars($r['name']); ?></td>
                    <td><?php echo $r['attendance_id'] ? htmlspecialchars($r['status']) : 'absent (no record)'; ?></td>
                    <td><?php echo $r['checkin_time'] ?: '-'; ?></td>
                    <td><?php echo htmlspecialchars($r['location_note'] ?? '-'); ?></td>
                    <td>
                        <?php if ($r['attendance_id']): ?>
                            <form method="post" style="display:inline">
                                <input type="hidden" name="update_id" value="<?php echo $r['attendance_id']; ?>">
                                <select name="new_status">
                                    <option value="present" <?php echo $r['status'] === 'present' ? 'selected' : ''; ?>>Present</option>
                                    <option value="absent"  <?php echo $r['status'] === 'absent'  ? 'selected' : ''; ?>>Absent</option>
                                </select>
                                <button type="submit">Update</button>
                            </form>
                            <form method="post" style="display:inline" onsubmit="return confirm('Delete this record?');">
                                <input type="hidden" name="delete_id" value="<?php echo $r['attendance_id']; ?>">
                                <button class="btn-danger" type="submit">Delete</button>
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
