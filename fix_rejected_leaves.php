<?php
/**
 * Fix Rejected Leaves Utility
 * 
 * This script restores leave balances for leaves that were rejected in the past,
 * as the original system incorrectly deducted leave balances for rejected leaves.
 * 
 * IMPORTANT: Run this script only once to fix historical data.
 */

require_once 'db_connect.php';
require_once 'auth_function.php';

// Only allow administrators to run this script
checkAdminLogin();

$messages = [];
$errors = [];
$fixed_count = 0;

// Check if we should process the fix
if (isset($_POST['fix_balances'])) {
    // Start a transaction for data consistency
    $pdo->beginTransaction();
    
    try {
        // Get all rejected leaves that haven't been fixed yet
        $get_rejected_leaves = $pdo->query("
            SELECT l.leave_id, l.employee_id, l.leave_type, l.leave_start_date, l.leave_end_date,
                   CONCAT(e.employee_first_name, ' ', e.employee_last_name) as employee_name,
                   lt.leave_type_name
            FROM elms_leave l
            JOIN elms_employee e ON l.employee_id = e.employee_id
            JOIN elms_leave_type lt ON l.leave_type = lt.leave_type_id
            WHERE l.leave_status = 'Reject' AND (l.balance_fixed IS NULL OR l.balance_fixed = 0)
        ");
        
        $rejected_leaves = $get_rejected_leaves->fetchAll(PDO::FETCH_ASSOC);
        
        if (empty($rejected_leaves)) {
            $messages[] = "No rejected leaves found that need to be fixed.";
        } else {
            // Add 'balance_fixed' column if it doesn't exist
            try {
                $pdo->exec("
                    ALTER TABLE elms_leave 
                    ADD COLUMN IF NOT EXISTS balance_fixed TINYINT(1) NOT NULL DEFAULT 0
                ");
            } catch (PDOException $e) {
                // Column might already exist or there's a database permission issue
                // We'll continue with the script anyway
            }
            
            foreach ($rejected_leaves as $leave) {
                // Calculate the leave days
                $start = new DateTime($leave['leave_start_date']);
                $end = new DateTime($leave['leave_end_date']);
                $interval = $start->diff($end);
                $leave_days = $interval->days + 1; // Including both start and end days
                
                // Add the days back to the leave balance
                $restore_balance = $pdo->prepare("
                    UPDATE elms_leave_balance 
                    SET leave_balance = leave_balance + :days 
                    WHERE employee_id = :employee_id 
                    AND leave_type_id = :leave_type_id
                ");
                
                $restore_balance->execute([
                    ':days' => $leave_days,
                    ':employee_id' => $leave['employee_id'],
                    ':leave_type_id' => $leave['leave_type']
                ]);
                
                // Mark this leave as fixed
                $mark_fixed = $pdo->prepare("
                    UPDATE elms_leave 
                    SET balance_fixed = 1 
                    WHERE leave_id = :leave_id
                ");
                
                $mark_fixed->execute([':leave_id' => $leave['leave_id']]);
                
                $fixed_count++;
                $messages[] = "Restored {$leave_days} days to {$leave['employee_name']}'s {$leave['leave_type_name']} leave balance.";
            }
            
            $messages[] = "Successfully fixed {$fixed_count} rejected leave applications.";
        }
        
        // Commit the transaction
        $pdo->commit();
        
    } catch (Exception $e) {
        // Roll back the transaction if anything fails
        $pdo->rollBack();
        $errors[] = "Error: " . $e->getMessage();
    }
}

// Include the header
include('header.php');
?>

<h1 class="mt-4">Fix Rejected Leave Balances</h1>
<ol class="breadcrumb mb-4">
    <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
    <li class="breadcrumb-item active">Fix Rejected Leave Balances</li>
</ol>

<div class="row">
    <div class="col-md-8">
        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger">
                <ul>
                    <?php foreach ($errors as $error): ?>
                        <li><?= htmlspecialchars($error) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
        
        <?php if (!empty($messages)): ?>
            <div class="alert alert-success">
                <ul>
                    <?php foreach ($messages as $message): ?>
                        <li><?= htmlspecialchars($message) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
        
        <div class="card mb-4">
            <div class="card-header">
                <i class="fas fa-tools me-1"></i>
                Fix Rejected Leave Balances
            </div>
            <div class="card-body">
                <div class="alert alert-info">
                    <p><strong>Important:</strong> This utility will restore leave balances for all rejected leave applications where the balance was incorrectly deducted.</p>
                    <p>This should only be run once to fix historical data.</p>
                </div>
                
                <form method="POST" onsubmit="return confirm('Are you sure you want to restore leave balances for all rejected applications? This should only be done once.');">
                    <button type="submit" name="fix_balances" class="btn btn-primary">
                        <i class="fas fa-sync me-1"></i> Fix Rejected Leave Balances
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php
include('footer.php');
?> 