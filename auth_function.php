<?php

session_start();

function checkAdminLogin() {
    if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'Admin') {
        header('Location: index.php');
        exit;
    } 
}

function redirectIfLoggedIn() {
    if(isset($_SESSION['user_logged_in'])){
        header('Location: dashboard.php');
    }
}

function checkAdminOrUserLogin(){
    if (!isset($_SESSION['user_type'])) {
        header('Location: index.php');
        exit;
    }
}

function getConfigData($pdo){
    $stmt = $pdo->query('SELECT * FROM pos_configuration');
    $stmt->execute();
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC)[0];
    return $data;
}

function getCategoryName($pdo, $category_id){
    $stmt = $pdo->query('SELECT category_name FROM pos_category WHERE category_id = "'.$category_id.'"');
    $stmt->execute();
    return $stmt->fetchColumn();
}

// Function to sanitize inputs
function sanitizeInput($data) {
    return htmlspecialchars(stripslashes(trim($data)));
}

function getMsg($type, $msg){
    return '
    <div class="alert alert-'.$type.' alert-dismissible fade show" role="alert">
        '.$msg.'
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    ';
}

function redirectIfEmpLoggedIn() {
    if(isset($_SESSION['employee_id'])){
        header('Location: employee_dashboard.php');
    }
}

function checkEmployeeLogin(){
    global $pdo;
    
    if (!isset($_SESSION['employee_id'])) {
        header('Location: employee_login.php');
        exit;
    }
    
    // Check if employee account is still active
    try {
        $check_status = $pdo->prepare("
            SELECT 
                e.employee_status, 
                d.department_status 
            FROM 
                elms_employee e
            LEFT JOIN 
                elms_department d ON e.employee_department = d.department_id
            WHERE 
                e.employee_id = :employee_id
        ");
        $check_status->execute([':employee_id' => $_SESSION['employee_id']]);
        $status_data = $check_status->fetch(PDO::FETCH_ASSOC);
        
        if ($status_data && ($status_data['employee_status'] === 'Inactive' || $status_data['department_status'] === 'Inactive')) {
            // Destroy session if status is inactive
            session_unset();
            session_destroy();
            
            // Redirect with message
            $_SESSION['temp_error'] = "Your account or department is inactive. Please contact your administrator.";
            header('Location: employee_login.php');
            exit;
        }
    } catch (Exception $e) {
        // Log the error but continue (fail gracefully)
    }
}

function checkAdminAndEmployeeLogin(){
    if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'Admin') {
        
    }
    if (!isset($_SESSION['employee_id'])) {
        header('Location: employee_login.php');
        exit;
    }
}


?>