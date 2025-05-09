<?php
session_start();
require_once 'db_connect.php';

function getMsg($type, $message) {
    return '<div class="alert alert-' . htmlspecialchars($type) . '">' . $message . '</div>';
}

$step = 1; // Step 1: email input
$success = false;
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['check_email'])) {
        $email = trim($_POST['email']);

        if (empty($email)) {
            $errors[] = 'Email is required, please enter email !';
        } else {
            $stmt = $pdo->prepare("SELECT * FROM elms_employee WHERE employee_email = ?");
            $stmt->execute([$email]);
            $employee = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($employee) {
                $step = 2; // Show password reset form
                $_SESSION['reset_email'] = $email; // store email in session temporarily
            } else {
                $errors[] = 'Email not found, please enter correct email !';
            }
        }
    }

    // Handle password reset
    if (isset($_POST['reset_password'])) {
        $new_password = trim($_POST['new_password']);
        $confirm_password = trim($_POST['confirm_password']);
        $email = $_SESSION['reset_email'] ?? '';

        if (empty($new_password) || empty($confirm_password)) {
            $errors[] = 'Both password fields are required.';
        } elseif ($new_password !== $confirm_password) {
            $errors[] = 'Passwords do not match.';
        } elseif (empty($email)) {
            $errors[] = 'Session expired. Please try again.';
            $step = 1;
        }

        if (empty($errors)) {
            $hashed = password_hash($new_password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE elms_employee SET employee_password = ? WHERE employee_email = ?");
            $stmt->execute([$hashed, $email]);
            $success = true;
            unset($_SESSION['reset_email']);
            $step = 3; // Done
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ELMS - Reset Password</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="asset/vendor/bootstrap/bootstrap.min.css" rel="stylesheet" crossorigin="anonymous">
    <style>
        :root {
            --primary: #3498db;
            --primary-dark: #2980b9;
            --secondary: #2c3e50;
            --accent: #f39c12;
            --light: #ecf0f1;
            --dark: #1a252f;
            --success: #2ecc71;
            --danger: #e74c3c;
            --gray: #95a5a6;
            --text: #333;
            --text-light: #7f8c8d;
            --shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            --transition: all 0.3s ease;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f8f9fa;
            line-height: 1.6;
            color: var(--text);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
        
        .reset-container {
            width: 100%;
            max-width: 1400px;
            margin: 0 auto;
            padding: 2rem 1rem;
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .reset-card {
            background-color: white;
            padding: 2.5rem;
            border-radius: 20px;
            box-shadow: var(--shadow);
            border-top: 4px solid var(--primary);
            width: 100%;
            max-width: 480px;
        }
        
        .reset-header {
            text-align: center;
            margin-bottom: 2rem;
        }
        
        .reset-logo {
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 1rem;
        }
        
        .logo-icon {
            color: var(--primary);
            font-size: 2.5rem;
            margin-right: 0.5rem;
        }
        
        .logo-text {
            font-size: 1.8rem;
            font-weight: 700;
            color: var(--secondary);
        }
        
        .logo-text span {
            color: var(--primary);
        }
        
        .reset-title {
            font-size: 1.5rem;
            font-weight: 600;
            color: var(--secondary);
            margin-bottom: 0.5rem;
        }
        
        .reset-subtitle {
            color: var(--text-light);
            font-size: 0.95rem;
        }
        
        .reset-step {
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 1.5rem;
        }
        
        .step-item {
            display: flex;
            flex-direction: column;
            align-items: center;
            width: 80px;
        }
        
        .step-number {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            font-size: 1rem;
            margin-bottom: 0.5rem;
            position: relative;
            z-index: 1;
        }
        
        .step-active .step-number {
            background-color: var(--primary);
            color: white;
        }
        
        .step-completed .step-number {
            background-color: var(--success);
            color: white;
        }
        
        .step-pending .step-number {
            background-color: var(--light);
            color: var(--text-light);
        }
        
        .step-label {
            font-size: 0.8rem;
            color: var(--text-light);
            text-align: center;
        }
        
        .step-active .step-label {
            color: var(--primary);
            font-weight: 500;
        }
        
        .step-completed .step-label {
            color: var(--success);
            font-weight: 500;
        }
        
        .step-line {
            flex: 1;
            height: 3px;
            background-color: var(--light);
            margin: 0 5px;
            margin-top: -32px;
            z-index: 0;
        }
        
        .step-line-active {
            background-color: var(--primary);
        }
        
        .form-group {
            margin-bottom: 1.5rem;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
            color: var(--secondary);
        }
        
        .form-control {
            width: 100%;
            padding: 0.75rem 1rem;
            font-size: 1rem;
            border: 1px solid #ddd;
            border-radius: 8px;
            transition: var(--transition);
            font-family: 'Poppins', sans-serif;
        }
        
        .form-control:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.2);
            outline: none;
        }
        
        .input-icon-wrapper {
            position: relative;
        }
        
        .input-icon {
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            left: 1rem;
            color: var(--gray);
        }
        
        .input-with-icon {
            padding-left: 2.8rem;
        }
        
        .btn {
            display: inline-block;
            padding: 0.75rem 1.5rem;
            font-size: 1rem;
            font-weight: 500;
            text-align: center;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            transition: var(--transition);
            font-family: 'Poppins', sans-serif;
        }
        
        .btn-primary {
            background-color: var(--primary);
            color: white;
            width: 100%;
        }
        
        .btn-primary:hover {
            background-color: var(--primary-dark);
            transform: translateY(-3px);
            box-shadow: 0 10px 15px rgba(0, 0, 0, 0.1);
        }
        
        .btn-outline {
            background-color: transparent;
            border: 2px solid var(--primary);
            color: var(--primary);
        }
        
        .btn-outline:hover {
            background-color: var(--primary);
            color: white;
        }
        
        .form-footer {
            margin-top: 1.5rem;
            text-align: center;
        }
        
        .form-footer a {
            color: var(--primary);
            text-decoration: none;
            font-weight: 500;
            transition: var(--transition);
        }
        
        .form-footer a:hover {
            color: var(--primary-dark);
        }
        
        .alert {
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
        }
        
        .alert-danger {
            background-color: rgba(231, 76, 60, 0.1);
            border-left: 4px solid var(--danger);
            color: var(--danger);
        }
        
        .alert-success {
            background-color: rgba(46, 204, 113, 0.1);
            border-left: 4px solid var(--success);
            color: var(--success);
        }
        
        .alert ul {
            margin-bottom: 0;
            padding-left: 1.5rem;
        }
        
        .home-link {
            position: absolute;
            top: 1.5rem;
            left: 1.5rem;
            color: var(--secondary);
            text-decoration: none;
            display: flex;
            align-items: center;
            font-weight: 500;
            transition: var(--transition);
        }
        
        .home-link i {
            margin-right: 0.5rem;
        }
        
        .home-link:hover {
            color: var(--primary);
        }
        
        .success-icon {
            font-size: 4rem;
            color: var(--success);
            margin-bottom: 1rem;
        }
        
        .success-message {
            text-align: center;
            margin-bottom: 2rem;
        }
        
        .success-message h3 {
            font-size: 1.5rem;
            color: var(--secondary);
            margin-bottom: 1rem;
        }
        
        .success-message p {
            color: var(--text-light);
        }
        
        @media (max-width: 576px) {
            .reset-card {
                padding: 2rem 1.5rem;
            }
            
            .step-item {
                width: 60px;
            }
        }
    </style>
</head>
<body>
    <a href="index.php" class="home-link">
        <i class="fas fa-chevron-left"></i> Back to Home
    </a>
    
    <div class="reset-container">
        <div class="reset-card">
            <div class="reset-header">
                <div class="reset-logo">
                    <div class="logo-icon"><i class="fas fa-calendar-check"></i></div>
                    <div class="logo-text">ELMS <span>Portal</span></div>
                </div>
                <h1 class="reset-title">Reset Your Password</h1>
                <p class="reset-subtitle">Follow the steps to reset your password</p>
            </div>
            
            <div class="reset-step">
                <div class="step-item <?php echo ($step >= 1) ? 'step-active' : 'step-pending'; echo ($step > 1) ? ' step-completed' : ''; ?>">
                    <div class="step-number">
                        <?php if ($step > 1): ?>
                            <i class="fas fa-check"></i>
                        <?php else: ?>
                            1
                        <?php endif; ?>
                    </div>
                    <div class="step-label">Verify Email</div>
                </div>
                
                <div class="step-line <?php echo ($step > 1) ? 'step-line-active' : ''; ?>"></div>
                
                <div class="step-item <?php echo ($step >= 2) ? 'step-active' : 'step-pending'; echo ($step > 2) ? ' step-completed' : ''; ?>">
                    <div class="step-number">
                        <?php if ($step > 2): ?>
                            <i class="fas fa-check"></i>
                        <?php else: ?>
                            2
                        <?php endif; ?>
                    </div>
                    <div class="step-label">New Password</div>
                </div>
                
                <div class="step-line <?php echo ($step > 2) ? 'step-line-active' : ''; ?>"></div>
                
                <div class="step-item <?php echo ($step >= 3) ? 'step-active' : 'step-pending'; ?>">
                    <div class="step-number">
                        <?php if ($step === 3): ?>
                            <i class="fas fa-check"></i>
                        <?php else: ?>
                            3
                        <?php endif; ?>
                    </div>
                    <div class="step-label">Complete</div>
                </div>
            </div>
            
            <?php
            if (count($errors) > 0) {
                echo '<div class="alert alert-danger"><ul>';
                foreach ($errors as $error) {
                    echo '<li>' . htmlspecialchars($error) . '</li>';
                }
                echo '</ul></div>';
            }
            ?>
            
            <?php if ($step === 1): ?>
                <form method="POST">
                    <div class="form-group">
                        <label for="email">Email Address</label>
                        <div class="input-icon-wrapper">
                            <i class="fas fa-envelope input-icon"></i>
                            <input type="email" name="email" id="email" class="form-control input-with-icon" placeholder="Enter your registered email" required>
                        </div>
                    </div>
                    
                    <button type="submit" name="check_email" class="btn btn-primary">
                        <i class="fas fa-arrow-right"></i> Continue
                    </button>
                    
                    <div class="form-footer">
                        <a href="employee_login.php">Back to Login</a>
                    </div>
                </form>
            
            <?php elseif ($step === 2): ?>
                <form method="POST">
                    <div class="form-group">
                        <label for="new_password">New Password</label>
                        <div class="input-icon-wrapper">
                            <i class="fas fa-lock input-icon"></i>
                            <input type="password" name="new_password" id="new_password" class="form-control input-with-icon" placeholder="Enter your new password" required>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="confirm_password">Confirm Password</label>
                        <div class="input-icon-wrapper">
                            <i class="fas fa-lock input-icon"></i>
                            <input type="password" name="confirm_password" id="confirm_password" class="form-control input-with-icon" placeholder="Confirm your new password" required>
                        </div>
                    </div>
                    
                    <button type="submit" name="reset_password" class="btn btn-primary">
                        <i class="fas fa-key"></i> Reset Password
                    </button>
                    
                    <div class="form-footer">
                        <a href="employee_reset_password.php">Start Over</a>
                    </div>
                </form>
            
            <?php elseif ($step === 3 && $success): ?>
                <div class="success-message">
                    <div class="success-icon">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <h3>Password Reset Complete!</h3>
                    <p>Your password has been successfully reset. You can now log in with your new password.</p>
                </div>
                
                <a href="employee_login.php" class="btn btn-primary">
                    <i class="fas fa-sign-in-alt"></i> Go to Login
                </a>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>



