<?php
require '../db.php';
header('Content-Type: text/plain; charset=utf-8');

error_reporting(0);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo "ERROR: Invalid request method";
    exit;
}

if (empty($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'student') {
    echo "ERROR: Not authorized";
    exit;
}

$student_id = (int) $_SESSION['user_id'];
$session_id = isset($_POST['session_id']) ? (int) $_POST['session_id'] : 0;
$location_note = trim($_POST['location_note'] ?? '');

if ($session_id <= 0 || $location_note === '') {
    echo "ERROR: Invalid input";
    exit;
}

$stmt = $pdo->prepare("
    SELECT s.*, c.id AS course_id, c.code, c.title
    FROM class_sessions s
    JOIN courses c ON s.course_id = c.id
    WHERE s.id = ?
");
$stmt->execute([$session_id]);
$session = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$session) {
    echo "ERROR: Session not found";
    exit;
}

$enrolled = $pdo->prepare("SELECT 1 FROM enrollments WHERE student_id = ? AND course_id = ?");
$enrolled->execute([$student_id, $session['course_id']]);
if (!$enrolled->fetch()) {
    echo "ERROR: You are not enrolled in this course";
    exit;
}

$classroom = strtolower($session['classroom']);
if ($classroom && strpos(strtolower($location_note), $classroom) === false) {
    echo "ERROR: Not in approved area (location must match classroom: {$session['classroom']})";
    exit;
}

$today = (new DateTime('now'))->format('Y-m-d');
if ($today !== $session['session_date']) {
    echo "ERROR: Check-in only allowed on the session date ({$session['session_date']})";
    exit;
}


try {
    $stmt = $pdo->prepare("
        INSERT INTO attendance_records (student_id, class_session_id, status, checkin_time, location_note)
        VALUES (?, ?, 'present', NOW(), ?)
        ON DUPLICATE KEY UPDATE
          status = 'present',
          checkin_time = NOW(),
          location_note = VALUES(location_note)
    ");
    $stmt->execute([$student_id, $session_id, $location_note]);

    echo "OK: Location verified and check-in successful.";
} catch (Exception $e) {
    echo "ERROR: Database error while saving attendance";
}
exit;
?>
