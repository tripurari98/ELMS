<?php
require_once 'db_connect.php';
require_once 'auth_function.php';

checkAdminLogin();

if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $leave_type_id = $_GET['id'];
    
    // Begin transaction to ensure data consistency
    $conn->begin_transaction();
    
    try {
        // First check if there are existing leave applications using this leave type
        $check_stmt = $conn->prepare("SELECT COUNT(*) as count FROM elms_leave WHERE leave_type = ?");
        $check_stmt->bind_param("i", $leave_type_id);
        $check_stmt->execute();
        $result = $check_stmt->get_result();
        $row = $result->fetch_assoc();
        $check_stmt->close();
        
        // If leave applications exist with this type, don't allow deletion
        if ($row['count'] > 0) {
            header("Location: leave_type.php?error=Cannot delete leave type that is used in leave applications");
            exit();
        }
        
        // Delete leave balances related to this leave type
        $stmt = $conn->prepare("DELETE FROM elms_leave_balance WHERE leave_type_id = ?");
        $stmt->bind_param("i", $leave_type_id);
        $stmt->execute();
        $stmt->close();
        
        // Delete the leave type
        $stmt = $conn->prepare("DELETE FROM elms_leave_type WHERE leave_type_id = ?");
        $stmt->bind_param("i", $leave_type_id);
        $stmt->execute();
        $stmt->close();
        
        // Commit transaction
        $conn->commit();
        header("Location: leave_type.php?success=Leave type deleted successfully");
    } catch (Exception $e) {
        // Rollback transaction on error
        $conn->rollback();
        header("Location: leave_type.php?error=Failed to delete leave type: " . $e->getMessage());
    }
} else {
    header("Location: leave_type.php?error=Invalid leave type ID");
}
exit();
?> 