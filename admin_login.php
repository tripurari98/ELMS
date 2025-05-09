<?php

//index.php

if (!file_exists('db_connect.php')) {
    header('Location: install.php');
    exit;
}

require_once 'db_connect.php';

require_once 'auth_function.php';

redirectIfLoggedIn();

$errors = [];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $user_name = trim($_POST['user_name']);
    $user_password = trim($_POST['user_password']);

    if (empty($user_name)) {
        $errors[] = "Username is required.";
    }

    if (empty($user_password)) {
        $errors[] = "Password is required.";
    }

    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare("SELECT * FROM elms_admin WHERE admin_user_name = ?");
            $stmt->execute([$user_name]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            if($user){
                if(password_verify($user_password, $user['admin_password'])){
                    $_SESSION['admin_id'] = $user['admin_id'];
                    $_SESSION['user_type'] = 'Admin';
                    $_SESSION['admin_logged_in'] = true;
                    header('Location: dashboard.php');
                } else {
                    $errors[] = "Wrong Password.";
                }
            } else {
                $errors[] = "Wrong Email";
            }
        } catch (PDOException $e) {
            $errors[] = "DB ERROR: " . $e->getMessage();
        }
    }
}


?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ELMS Admin Login</title>
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
        
        .login-container {
            width: 100%;
            max-width: 1400px;
            margin: 0 auto;
            padding: 2rem 1rem;
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .login-grid {
            display: block;
            width: 100%;
            max-width: 480px;
            margin: 0 auto;
        }
        
        .login-image {
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            border-radius: 20px;
            box-shadow: var(--shadow);
        }
        
        .login-image img {
            width: 100%;
            height: auto;
            object-fit: cover;
        }
        
        .login-form-container {
            background-color: white;
            padding: 2.5rem;
            border-radius: 20px;
            box-shadow: var(--shadow);
            border-top: 4px solid var(--secondary);
        }
        
        .login-header {
            text-align: center;
            margin-bottom: 2rem;
        }
        
        .login-logo {
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
        
        .login-title {
            font-size: 1.5rem;
            font-weight: 600;
            color: var(--secondary);
            margin-bottom: 0.5rem;
        }
        
        .login-subtitle {
            color: var(--text-light);
            font-size: 0.95rem;
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
        
        .btn-secondary {
            background-color: var(--secondary);
            color: white;
            width: 100%;
        }
        
        .btn-secondary:hover {
            background-color: var(--dark);
            transform: translateY(-3px);
            box-shadow: 0 10px 15px rgba(0, 0, 0, 0.1);
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
        
        .form-divider {
            display: flex;
            align-items: center;
            margin: 1.5rem 0;
        }
        
        .form-divider span {
            flex: 1;
            height: 1px;
            background-color: #ddd;
        }
        
        .form-divider-text {
            padding: 0 1rem;
            color: var(--text-light);
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
        
        .admin-badge {
            display: inline-block;
            background-color: var(--secondary);
            color: white;
            font-size: 0.75rem;
            font-weight: 600;
            padding: 0.25rem 0.5rem;
            border-radius: 20px;
            margin-left: 0.5rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        @media (max-width: 768px) {
            .login-form-container {
                padding: 2rem 1.5rem;
            }
        }
    </style>
</head>
<body>
    <a href="index.php" class="home-link">
        <i class="fas fa-chevron-left"></i> Back to Home
    </a>
    
    <div class="login-container">
        <div class="login-grid">
            <div class="login-form-container" style="max-width: 480px; margin: 0 auto;">
                <div class="login-header">
                    <div class="login-logo">
                        <div class="logo-icon"><i class="fas fa-calendar-check"></i></div>
                        <div class="logo-text">ELMS <span>Portal</span></div>
                    </div>
                    <h1 class="login-title">Admin Login <span class="admin-badge">Secure</span></h1>
                    <p class="login-subtitle">Access the administrative dashboard</p>
                </div>
                
                <?php if (!empty($errors)) { ?>
                    <div class="alert alert-danger">
                        <ul class="list-unstyled mb-0">
                            <?php foreach ($errors as $error) { ?>
                                <li><?php echo htmlspecialchars($error); ?></li>
                            <?php } ?>
                        </ul>
                    </div>
                <?php } ?>
                
                <form method="post" action="">
                    <div class="form-group">
                        <label for="user_name">Username</label>
                        <div class="input-icon-wrapper">
                            <i class="fas fa-user input-icon"></i>
                            <input type="text" id="user_name" name="user_name" class="form-control input-with-icon" placeholder="Enter your username" value="<?php echo isset($_POST['user_name']) ? htmlspecialchars($_POST['user_name']) : ''; ?>">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="user_password">Password</label>
                        <div class="input-icon-wrapper">
                            <i class="fas fa-lock input-icon"></i>
                            <input type="password" id="user_password" name="user_password" class="form-control input-with-icon" placeholder="Enter your password">
                        </div>
                    </div>
                    
                    <button type="submit" class="btn btn-secondary">
                        <i class="fas fa-sign-in-alt"></i> Admin Login
                    </button>
                    
                    <div class="form-divider">
                        <span></span>
                        <div class="form-divider-text">or</div>
                        <span></span>
                    </div>
                    
                    <div class="form-footer">
                        <a href="employee_login.php">Login as Employee</a>
                    </div>
                </form>
            </div>
            
            <!-- Image container removed -->
        </div>
    </div>
</body>
</html>