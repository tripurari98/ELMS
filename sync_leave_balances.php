<?php
require_once 'db_connect.php';
require_once 'auth_function.php';

// Only admin can run this script
checkAdminLogin();

$action = isset($_GET['action']) ? $_GET['action'] : '';
$message = '';
$error = '';
$success = '';

// Begin transaction
$pdo->beginTransaction();

try {
    if ($action === 'sync') {
        // Get all active employees
        $employeeStmt = $pdo->prepare("SELECT employee_id FROM elms_employee WHERE employee_status = 'Active'");
        $employeeStmt->execute();
        $employees = $employeeStmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Get all active leave types
        $leaveTypeStmt = $pdo->prepare("SELECT leave_type_id, days_allowed FROM elms_leave_type WHERE leave_type_status = 'Active'");
        $leaveTypeStmt->execute();
        $leaveTypes = $leaveTypeStmt->fetchAll(PDO::FETCH_ASSOC);
        
        $balancesAdded = 0;
        
        foreach ($employees as $employee) {
            foreach ($leaveTypes as $leaveType) {
                // Check if this employee already has a balance for this leave type
                $checkStmt = $pdo->prepare("SELECT COUNT(*) FROM elms_leave_balance 
                                          WHERE employee_id = :employee_id 
                                          AND leave_type_id = :leave_type_id");
                $checkStmt->execute([
                    'employee_id' => $employee['employee_id'],
                    'leave_type_id' => $leaveType['leave_type_id']
                ]);
                
                $balanceExists = $checkStmt->fetchColumn() > 0;
                
                // If balance doesn't exist, create it
                if (!$balanceExists) {
                    $insertStmt = $pdo->prepare("INSERT INTO elms_leave_balance 
                                              (employee_id, leave_type_id, leave_balance) 
                                              VALUES (:employee_id, :leave_type_id, :leave_balance)");
                    $insertStmt->execute([
                        'employee_id' => $employee['employee_id'],
                        'leave_type_id' => $leaveType['leave_type_id'],
                        'leave_balance' => $leaveType['days_allowed']
                    ]);
                    
                    $balancesAdded++;
                }
            }
        }
        
        // Commit transaction
        $pdo->commit();
        
        $success = "$balancesAdded new leave balances have been created. All active employees now have balances for all active leave types.";
    }
} catch (PDOException $e) {
    // Rollback on error
    $pdo->rollBack();
    $error = "Database error: " . $e->getMessage();
}

include('header.php');
?>

<h1 class="mt-4">Synchronize Leave Balances</h1>
<ol class="breadcrumb mb-4">
    <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
    <li class="breadcrumb-item active">Synchronize Leave Balances</li>
</ol>

<div class="row">
    <div class="col-md-8">
        <?php if ($error): ?>
            <?php echo getMsg('danger', $error); ?>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <?php echo getMsg('success', $success); ?>
        <?php endif; ?>
        
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Synchronize Leave Balances</h5>
            </div>
            <div class="card-body">
                <p>
                    This utility ensures that all active employees have leave balances created for all active leave types.
                    It's useful when:
                </p>
                <ul>
                    <li>New leave types have been added and existing employees don't have balances for them</li>
                    <li>New employees have been added and don't have balances for existing leave types</li>
                    <li>You need to ensure data consistency between employees and leave types</li>
                </ul>
                
                <?php if ($action !== 'sync'): ?>
                <div class="alert alert-warning">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    Running this synchronization will create leave balances for all active employees who don't already have them.
                    For each new balance, the default value will be set to the number of days allowed for that leave type.
                </div>
                
                <a href="sync_leave_balances.php?action=sync" class="btn btn-primary">
                    <i class="fas fa-sync-alt me-2"></i> Run Synchronization
                </a>
                <?php else: ?>
                <a href="leave_type.php" class="btn btn-primary">
                    <i class="fas fa-arrow-left me-2"></i> Back to Leave Types
                </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <div class="col-md-4">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Help</h5>
            </div>
            <div class="card-body">
                <p>
                    <strong>How Leave Balances Work:</strong>
                </p>
                <p>
                    Each employee should have a balance for each active leave type in the system.
                </p>
                <p>
                    When new leave types are added or new employees are created, the system should automatically
                    create the appropriate leave balances, but sometimes manual synchronization may be needed.
                </p>
                <p>
                    <strong>Note:</strong> This utility only adds missing balances, it does not modify existing balances.
                </p>
            </div>
        </div>
    </div>
</div>

<?php
include('footer.php');
?> 