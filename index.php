<?php
session_start();
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Login - Classroom Check-In</title>
    <link rel="stylesheet" href="assets/style.css">
</head>

<body>

<div class="navbar">
    <strong style="color:white;">Classroom Check-In</strong>
</div>

<div class="container">
    <h2>Login</h2>

    <?php
    if (isset($_GET['error'])) {
        echo '<div class="alert">Invalid username or password.</div>';
    }
    ?>

    <form action="process/login_process.php" method="POST">
        <label>Email</label>
        <input type="email" name="email" required>

        <label>Password</label>
        <input type="password" name="password" required>

        <button class="btn" type="submit">Log In</button>
    </form>

    <p style="text-align:center; margin-top:15px;">
        No account? <a href="register.php">Register here</a>
    </p>
</div>

</body>
</html>