<?php
require_once 'db_connect.php';
require_once 'auth_function.php';

checkAdminLogin();

if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $department_id = $_GET['id'];
    
    // Begin transaction to ensure data consistency
    $conn->begin_transaction();
    
    try {
        // First delete any leave records associated with employees in this department
        $stmt = $conn->prepare("DELETE FROM elms_leave WHERE employee_id IN 
                              (SELECT employee_id FROM elms_employee WHERE employee_department = ?)");
        $stmt->bind_param("i", $department_id);
        $stmt->execute();
        $stmt->close();
        
        // Delete leave balances for employees in this department
        $stmt = $conn->prepare("DELETE FROM elms_leave_balance WHERE employee_id IN 
                              (SELECT employee_id FROM elms_employee WHERE employee_department = ?)");
        $stmt->bind_param("i", $department_id);
        $stmt->execute();
        $stmt->close();
        
        // Delete notifications related to employees in this department
        $stmt = $conn->prepare("DELETE FROM elms_notifications WHERE recipient_id IN 
                              (SELECT employee_id FROM elms_employee WHERE employee_department = ?) 
                              AND recipient_role = 'Employee'");
        $stmt->bind_param("i", $department_id);
        $stmt->execute();
        $stmt->close();
        
        // Delete employees in this department
        $stmt = $conn->prepare("DELETE FROM elms_employee WHERE employee_department = ?");
        $stmt->bind_param("i", $department_id);
        $stmt->execute();
        $stmt->close();
        
        // Finally delete the department
        $stmt = $conn->prepare("DELETE FROM elms_department WHERE department_id = ?");
        $stmt->bind_param("i", $department_id);
        $stmt->execute();
        $stmt->close();
        
        // Commit transaction
        $conn->commit();
        header("Location: department.php?success=Department and associated employees deleted successfully.");
    } catch (Exception $e) {
        // Rollback transaction on error
        $conn->rollback();
        header("Location: department.php?error=Failed to delete department: " . $e->getMessage());
    }
} else {
    header("Location: department.php?error=Invalid department ID.");
}
exit();
