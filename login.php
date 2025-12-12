<?php
require_once 'config.php';

if (is_logged_in()) {
    redirect('dashboard.php');
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['login'])) {
    $email = sanitize_input($_POST['email']);
    $password = $_POST['password'];
    
    if (empty($email) || empty($password)) {
        $error = "Please fill in all fields.";
    } elseif (!validate_email($email)) {
        $error = "Please enter a valid email address.";
    } else {
        $stmt = $conn->prepare("SELECT user_id, first_name, last_name, email, password, user_type FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();
            
            if (password_verify($password, $user['password'])) {
                $_SESSION['user_id'] = $user['user_id'];
                $_SESSION['first_name'] = $user['first_name'];
                $_SESSION['last_name'] = $user['last_name'];
                $_SESSION['email'] = $user['email'];
                $_SESSION['user_type'] = $user['user_type'];
                
                redirect('dashboard.php');
            } else {
                $error = "Invalid email or password.";
            }
        } else {
            $error = "Invalid email or password.";
        }
        $stmt->close();
    }
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['register'])) {
    $first_name = sanitize_input($_POST['first_name']);
    $last_name = sanitize_input($_POST['last_name']);
    $email = sanitize_input($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $user_type = sanitize_input($_POST['user_type']);
    
    if (empty($first_name) || empty($last_name) || empty($email) || empty($password) || empty($user_type)) {
        $error = "Please fill in all fields.";
    } elseif (!validate_email($email)) {
        $error = "Please enter a valid university email address.";
    } elseif (!validate_password($password)) {
        $error = "Password must be at least 8 characters with uppercase, lowercase, and number.";
    } elseif ($password !== $confirm_password) {
        $error = "Passwords do not match.";
    } else {
        $stmt = $conn->prepare("SELECT user_id FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $error = "An account with this email already exists.";
        } else {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("INSERT INTO users (first_name, last_name, email, password, user_type) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("sssss", $first_name, $last_name, $email, $hashed_password, $user_type);
            
            if ($stmt->execute()) {
                $success = "Registration successful! Please log in.";
            } else {
                $error = "Registration failed. Please try again.";
            }
        }
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Classroom Check-in System - Login</title>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }
        
        .container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            max-width: 450px;
            width: 100%;
            overflow: hidden;
        }
        
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }
        
        .header h1 {
            font-size: 28px;
            margin-bottom: 10px;
        }
        
        .header p {
            font-size: 14px;
            opacity: 0.9;
        }
        
        .form-container {
            padding: 30px;
        }
        
        .tabs {
            display: flex;
            margin-bottom: 30px;
            border-bottom: 2px solid #e0e0e0;
        }
        
        .tab {
            flex: 1;
            padding: 15px;
            text-align: center;
            cursor: pointer;
            background: none;
            border: none;
            font-size: 16px;
            font-weight: 600;
            color: #666;
            transition: all 0.3s;
        }
        
        .tab.active {
            color: #667eea;
            border-bottom: 3px solid #667eea;
            margin-bottom: -2px;
        }
        
        .form-content {
            display: none;
        }
        
        .form-content.active {
            display: block;
            animation: fadeIn 0.5s;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #333;
            font-weight: 500;
            font-size: 14px;
        }
        
        .form-group input,
        .form-group select {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 14px;
            transition: all 0.3s;
        }
        
        .form-group input:focus,
        .form-group select:focus {
            outline: none;
            border-color: #667eea;
        }
        
        .form-group input.error {
            border-color: #ff4757;
        }
        
        .error-message {
            color: #ff4757;
            font-size: 12px;
            margin-top: 5px;
            display: none;
        }
        
        .alert {
            padding: 12px 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 14px;
        }
        
        .alert-error {
            background-color: #ffe0e0;
            color: #cc0000;
            border-left: 4px solid #ff4757;
        }
        
        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border-left: 4px solid #28a745;
        }
        
        .btn {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }
        
        .btn:active {
            transform: translateY(0);
        }
        
        .password-strength {
            height: 4px;
            background: #e0e0e0;
            border-radius: 2px;
            margin-top: 8px;
            overflow: hidden;
        }
        
        .password-strength-bar {
            height: 100%;
            width: 0;
            transition: all 0.3s;
        }
        
        .strength-weak { width: 33%; background: #ff4757; }
        .strength-medium { width: 66%; background: #ffa502; }
        .strength-strong { width: 100%; background: #26de81; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>📚 Classroom Check-in</h1>
            <p>Streamlined Attendance Management System</p>
        </div>
        
        <div class="form-container">
            <?php if ($error): ?>
                <div class="alert alert-error"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="alert alert-success"><?php echo $success; ?></div>
            <?php endif; ?>
            
            <div class="tabs">
                <button class="tab active" data-tab="login">Login</button>
                <button class="tab" data-tab="register">Register</button>
            </div>
            
            <div class="form-content active" id="login-form">
                <form method="POST" action="" id="loginForm">
                    <div class="form-group">
                        <label for="login-email">University Email</label>
                        <input type="email" id="login-email" name="email" placeholder="your.name@university.edu" required>
                        <span class="error-message" id="login-email-error"></span>
                    </div>
                    
                    <div class="form-group">
                        <label for="login-password">Password</label>
                        <input type="password" id="login-password" name="password" placeholder="Enter your password" required>
                        <span class="error-message" id="login-password-error"></span>
                    </div>
                    
                    <button type="submit" name="login" class="btn">Login</button>
                </form>
            </div>
            
            <div class="form-content" id="register-form">
                <form method="POST" action="" id="registrationForm">
                    <div class="form-group">
                        <label for="first-name">First Name</label>
                        <input type="text" id="first-name" name="first_name" placeholder="John" required>
                        <span class="error-message" id="first-name-error"></span>
                    </div>
                    
                    <div class="form-group">
                        <label for="last-name">Last Name</label>
                        <input type="text" id="last-name" name="last_name" placeholder="Doe" required>
                        <span class="error-message" id="last-name-error"></span>
                    </div>
                    
                    <div class="form-group">
                        <label for="register-email">University Email</label>
                        <input type="email" id="register-email" name="email" placeholder="your.name@university.edu" required>
                        <span class="error-message" id="register-email-error"></span>
                    </div>
                    
                    <div class="form-group">
                        <label for="register-password">Password</label>
                        <input type="password" id="register-password" name="password" placeholder="Min 8 chars, uppercase, lowercase, number" required>
                        <div class="password-strength">
                            <div class="password-strength-bar" id="strength-bar"></div>
                        </div>
                        <span class="error-message" id="register-password-error"></span>
                    </div>
                    
                    <div class="form-group">
                        <label for="confirm-password">Confirm Password</label>
                        <input type="password" id="confirm-password" name="confirm_password" placeholder="Re-enter password" required>
                        <span class="error-message" id="confirm-password-error"></span>
                    </div>
                    
                    <div class="form-group">
                        <label for="user-type">I am a:</label>
                        <select id="user-type" name="user_type" required>
                            <option value="">Select...</option>
                            <option value="student">Student</option>
                            <option value="instructor">Instructor</option>
                        </select>
                        <span class="error-message" id="user-type-error"></span>
                    </div>
                    
                    <button type="submit" name="register" class="btn">Register</button>
                </form>
            </div>
        </div>
    </div>
    
    <script>
        $(document).ready(function() {
            $('.tab').click(function() {
                const tabName = $(this).data('tab');
                
                $('.tab').removeClass('active');
                $(this).addClass('active');
                
                $('.form-content').removeClass('active');
                $('#' + tabName + '-form').addClass('active');
            });
            
            function validateEmail(email) {
                const regex = /^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/;
                return regex.test(email);
            }
            
            $('#register-password').on('input', function() {
                const password = $(this).val();
                const strengthBar = $('#strength-bar');
                
                let strength = 0;
                if (password.length >= 8) strength++;
                if (/[a-z]/.test(password)) strength++;
                if (/[A-Z]/.test(password)) strength++;
                if (/[0-9]/.test(password)) strength++;
                if (/[^a-zA-Z0-9]/.test(password)) strength++;
                
                strengthBar.removeClass('strength-weak strength-medium strength-strong');
                
                if (strength <= 2) {
                    strengthBar.addClass('strength-weak');
                } else if (strength <= 3) {
                    strengthBar.addClass('strength-medium');
                } else {
                    strengthBar.addClass('strength-strong');
                }
            });
            
            $('#login-email').on('blur', function() {
                const email = $(this).val();
                const errorMsg = $('#login-email-error');
                
                if (!email) {
                    $(this).addClass('error');
                    errorMsg.text('Email is required').show();
                } else if (!validateEmail(email)) {
                    $(this).addClass('error');
                    errorMsg.text('Please enter a valid email').show();
                } else {
                    $(this).removeClass('error');
                    errorMsg.hide();
                }
            });
            
            $('#register-email').on('blur', function() {
                const email = $(this).val();
                const errorMsg = $('#register-email-error');
                
                if (!email) {
                    $(this).addClass('error');
                    errorMsg.text('Email is required').show();
                } else if (!validateEmail(email)) {
                    $(this).addClass('error');
                    errorMsg.text('Please enter a valid university email').show();
                } else {
                    $(this).removeClass('error');
                    errorMsg.hide();
                }
            });
            
            $('#confirm-password').on('blur', function() {
                const password = $('#register-password').val();
                const confirm = $(this).val();
                const errorMsg = $('#confirm-password-error');
                
                if (confirm && password !== confirm) {
                    $(this).addClass('error');
                    errorMsg.text('Passwords do not match').show();
                } else {
                    $(this).removeClass('error');
                    errorMsg.hide();
                }
            });
            
            $('#loginForm').on('submit', function(e) {
                let isValid = true;
                
                const email = $('#login-email').val();
                if (!email || !validateEmail(email)) {
                    $('#login-email').addClass('error');
                    $('#login-email-error').text('Valid email required').show();
                    isValid = false;
                }
                
                const password = $('#login-password').val();
                if (!password) {
                    $('#login-password').addClass('error');
                    $('#login-password-error').text('Password required').show();
                    isValid = false;
                }
                
                if (!isValid) {
                    e.preventDefault();
                }
            });
            
            $('#registrationForm').on('submit', function(e) {
                let isValid = true;
                
                const firstName = $('#first-name').val();
                const lastName = $('#last-name').val();
                const email = $('#register-email').val();
                const password = $('#register-password').val();
                const confirm = $('#confirm-password').val();
                const userType = $('#user-type').val();
                
                if (!firstName) {
                    $('#first-name').addClass('error');
                    isValid = false;
                }
                
                if (!lastName) {
                    $('#last-name').addClass('error');
                    isValid = false;
                }
                
                if (!email || !validateEmail(email)) {
                    $('#register-email').addClass('error');
                    $('#register-email-error').text('Valid email required').show();
                    isValid = false;
                }
                
                const passwordRegex = /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).{8,}$/;
                if (!password || !passwordRegex.test(password)) {
                    $('#register-password').addClass('error');
                    $('#register-password-error').text('Password must be 8+ chars with uppercase, lowercase, and number').show();
                    isValid = false;
                }
                
                if (password !== confirm) {
                    $('#confirm-password').addClass('error');
                    $('#confirm-password-error').text('Passwords must match').show();
                    isValid = false;
                }
                
                if (!userType) {
                    $('#user-type').addClass('error');
                    isValid = false;
                }
                
                if (!isValid) {
                    e.preventDefault();
                }
            });
        });
    </script>
</body>
</html>