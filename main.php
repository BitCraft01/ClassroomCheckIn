<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Main Dashboard</title>
    <link rel="stylesheet" href="assets/style.css">
</head>

<body>

<div class="navbar">
    <div style="color:white; font-weight:bold;">Classroom Check-In</div>

    <div>
        <a href="main.php">Home</a>
        <a href="profile.php">Profile</a>
        <a href="logout.php">Logout</a>
    </div>
</div>

<div class="dashboard">
    <h1>Welcome, <?php echo $_SESSION['user_name']; ?>!</h1>
    <p>Manage your check-ins and view your data below.</p>

    <div class="table-container">
        <h2>Your Attendance Records</h2>

        <table>
            <tr>
                <th>Date</th>
                <th>Status</th>
            </tr>

            <!-- Example row (replace with database data) -->
            <tr>
                <td>2025-12-10</td>
                <td>Present</td>
            </tr>
        </table>
    </div>
</div>

</body>
</html>