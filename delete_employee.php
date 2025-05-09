<?php
require_once 'db_connect.php';
require_once 'auth_function.php';

checkAdminLogin();

if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $employee_id = $_GET['id'];
    
    // Begin transaction to ensure data consistency
    $conn->begin_transaction();
    
    try {
        // Delete leave records associated with this employee
        $stmt = $conn->prepare("DELETE FROM elms_leave WHERE employee_id = ?");
        $stmt->bind_param("i", $employee_id);
        $stmt->execute();
        $stmt->close();
        
        // Delete leave balances for this employee
        $stmt = $conn->prepare("DELETE FROM elms_leave_balance WHERE employee_id = ?");
        $stmt->bind_param("i", $employee_id);
        $stmt->execute();
        $stmt->close();
        
        // Delete notifications related to this employee
        $stmt = $conn->prepare("DELETE FROM elms_notifications WHERE recipient_id = ? AND recipient_role = 'Employee'");
        $stmt->bind_param("i", $employee_id);
        $stmt->execute();
        $stmt->close();
        
        // Finally delete the employee
        $stmt = $conn->prepare("DELETE FROM elms_employee WHERE employee_id = ?");
        $stmt->bind_param("i", $employee_id);
        $stmt->execute();
        $stmt->close();
        
        // Commit transaction
        $conn->commit();
        header("Location: employee.php?success=Employee deleted successfully");
    } catch (Exception $e) {
        // Rollback transaction on error
        $conn->rollback();
        header("Location: employee.php?error=Failed to delete employee: " . $e->getMessage());
    }
} else {
    header("Location: employee.php?error=Invalid employee ID");
}
exit();
?> 