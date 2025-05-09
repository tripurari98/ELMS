<?php

require_once 'db_connect.php';
require_once 'auth_function.php';

checkAdminLogin();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $leave_type_name = trim($_POST['leave_type_name']);
    $leave_type_status = trim($_POST['leave_type_status']);
    $days_allowed = trim($_POST['days_allowed']);
    $error = '';

    // Validate inputs
    if (empty($leave_type_name)) {
        $error = 'Leave Type name is required.';
    } else if(empty($days_allowed)) {
        $error = 'Days Allowed per Year is required';
    } else {
        // Check if Leave Type is already exists
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM elms_leave_type WHERE leave_type_name = :leave_type_name");
        $stmt->execute(['leave_type_name' => $leave_type_name]);
        $count = $stmt->fetchColumn();

        if ($count > 0) {
            $error = 'Leave Type with this name already exists.';
        } else {
            // Begin transaction to ensure data consistency
            $pdo->beginTransaction();
            
            try {
                // Insert new leave type
                $stmt = $pdo->prepare("INSERT INTO elms_leave_type (leave_type_name, leave_type_status, days_allowed) VALUES (:leave_type_name, :leave_type_status, :days_allowed)");
                $stmt->execute([
                    'leave_type_name'   => $leave_type_name,
                    'leave_type_status' => $leave_type_status,
                    'days_allowed'      => $days_allowed
                ]);
                
                // Get the newly created leave type ID
                $leave_type_id = $pdo->lastInsertId();
                
                // Get all active employees to assign the leave balance
                $stmt = $pdo->prepare("SELECT employee_id FROM elms_employee WHERE employee_status = 'Active'");
                $stmt->execute();
                $employees = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                // Create leave balances for all active employees
                if (!empty($employees)) {
                    $insertBalanceStmt = $pdo->prepare("INSERT INTO elms_leave_balance (employee_id, leave_type_id, leave_balance) VALUES (:employee_id, :leave_type_id, :leave_balance)");
                    
                    foreach ($employees as $employee) {
                        $insertBalanceStmt->execute([
                            'employee_id' => $employee['employee_id'],
                            'leave_type_id' => $leave_type_id,
                            'leave_balance' => $days_allowed // Initial balance equals days allowed
                        ]);
                    }
                }
                
                // Commit the transaction
                $pdo->commit();
                
                $_SESSION['message'] = "Leave Type Data added successfully and leave balances have been assigned to all active employees!";
                header('location:leave_type.php');
                exit();
            } catch (PDOException $e) {
                // Rollback the transaction on error
                $pdo->rollBack();
                $error = 'Database error: ' . $e->getMessage();
            }
        }
    }
}


include('header.php');
?>

<h1 class="mt-4">Add Leave Type</h1>
<ol class="breadcrumb mb-4">
    <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
    <li class="breadcrumb-item"><a href="leave_type.php">Leave Type Management</a></li>
    <li class="breadcrumb-item active">Add Leave Type</li>
</ol>

<div class="row">
    <div class="col-md-4">
        <?php
        if(isset($error) && $error !== ''){
            echo getMsg('alert', $error);
        }
        ?>
        <div class="card">
            <div class="card-header">Add Leave Type</div>
            <div class="card-body">
            <form method="post" action="add_leave_type.php">
                <div class="mb-3">
                    <label for="leave_type_name">Leave Type Name</label>
                    <input type="text" id="leave_type_name" name="leave_type_name" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label for="days_allowed">Allowed Leave Day Per Year</label>
                    <input type="number" id="days_allowed" name="days_allowed" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label for="leave_type_status">Leave Type Status</label>
                    <select id="leave_type_status" name="leave_type_status" class="form-select">
                        <option value="Active">Active</option>
                        <option value="Inactive">Inactive</option>
                    </select>
                </div>
                <input type="submit" value="Add Leave Type" class="btn btn-primary">
            </form>
        </div>
    </div>
</div>

<?php
include('footer.php');
?>